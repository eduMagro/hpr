<?php

namespace App\Services\FerrawinSync;

use App\Models\Planilla;
use App\Models\PlanillaEntidad;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\FerrawinSyncLog;
use App\Models\ProductoBase;
use App\Services\AsignarMaquinaService;
use App\Services\OrdenPlanillaService;
use App\Services\PlanillaImport\CodigoEtiqueta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\ZobjetoParser;

/**
 * Servicio de importación masiva optimizado para FerraWin.
 *
 * Diseñado para alto rendimiento con bulk inserts y transacciones.
 */
class FerrawinBulkImportService
{
    protected array $stats = [];
    protected array $advertencias = [];
    protected array $cacheClientes = [];
    protected array $cacheObras = [];
    protected array $productosBase = [];

    public function __construct(
        protected CodigoEtiqueta $codigoService,
        protected AsignarMaquinaService $asignador,
        protected OrdenPlanillaService $ordenService,
        protected \App\Services\PlanillaImport\PlanillaProcessor $processor
    ) {
        // Cargar productos base (barras) igual que PlanillaProcessor
        $this->productosBase = ProductoBase::where('tipo', 'barra')
            ->whereNotNull('longitud')
            ->whereNotNull('diametro')
            ->get(['diametro', 'longitud'])
            ->map(fn($p) => [
                'diametro' => (int)$p->diametro,
                'longitud' => (float)$p->longitud * 1000, // Convertir a mm
            ])
            ->toArray();
    }

    /**
     * Importa múltiples planillas de forma optimizada.
     *
     * @param array $planillasData Array de planillas con sus elementos
     * @param array $metadata Metadatos de la sincronización
     * @return array Resultado de la importación
     */
    public function importar(array $planillasData, array $metadata = []): array
    {
        $this->resetStats();
        $inicio = microtime(true);

        Log::channel('ferrawin_sync')->info("🚀 [BULK] Iniciando importación masiva", [
            'planillas' => count($planillasData),
        ]);

        try {
            // 1. Pre-cargar datos en cache
            $this->precargarCaches($planillasData);

            // 2. Filtrar planillas que ya existen
            $codigos = array_column($planillasData, 'codigo');
            $existentes = Planilla::whereIn('codigo', $codigos)->pluck('codigo')->toArray();

            $planillasNuevas = array_filter($planillasData, fn($p) => !in_array($p['codigo'], $existentes));
            $planillasActualizar = array_filter($planillasData, fn($p) => in_array($p['codigo'], $existentes));

            $this->stats['planillas_omitidas'] = count($existentes);

            Log::channel('ferrawin_sync')->info("📊 [BULK] Análisis de planillas", [
                'nuevas' => count($planillasNuevas),
                'existentes' => count($existentes),
            ]);

            // 3. Inicializar contador de etiquetas
            $this->codigoService->inicializarContadorBatch();

            // 4. Procesar en transacción
            DB::beginTransaction();

            // 4a. Importar planillas nuevas
            foreach ($planillasNuevas as $planillaData) {
                $this->importarPlanilla($planillaData);
            }

            // 4b. Actualizar planillas existentes (solo si tienen cambios)
            foreach ($planillasActualizar as $planillaData) {
                $this->actualizarPlanilla($planillaData);
            }

            DB::commit();

            // 5. Resetear contador
            $this->codigoService->resetearContadorBatch();

            // 6. Registrar log
            $duracion = round(microtime(true) - $inicio, 2);
            $this->registrarLog($duracion, 'completado');

            Log::channel('ferrawin_sync')->info("✅ [BULK] Importación completada", [
                'duracion' => $duracion,
                'planillas_creadas' => $this->stats['planillas_creadas'],
                'elementos_creados' => $this->stats['elementos_creados'],
            ]);

            return [
                'planillas_creadas' => $this->stats['planillas_creadas'],
                'planillas_actualizadas' => $this->stats['planillas_actualizadas'],
                'planillas_omitidas' => $this->stats['planillas_omitidas'],
                'elementos_creados' => $this->stats['elementos_creados'],
                'etiquetas_creadas' => $this->stats['etiquetas_creadas'],
                'entidades_creadas' => $this->stats['entidades_creadas'],
                'advertencias' => $this->advertencias,
                'duracion' => $duracion,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->codigoService->resetearContadorBatch();

            Log::channel('ferrawin_sync')->error("❌ [BULK] Error en importación", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->registrarLog(
                round(microtime(true) - $inicio, 2),
                'error',
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Importa una planilla individual con sus elementos.
     */
    protected function importarPlanilla(array $data): void
    {
        $codigo = $data['codigo'];
        $elementos = $data['elementos'] ?? [];

        if (empty($elementos)) {
            $this->advertencias[] = "Planilla {$codigo}: sin elementos";
            return;
        }

        Log::channel('ferrawin_sync')->debug("📥 [BULK] Procesando planilla {$codigo} con " . count($elementos) . " elementos");

        // 1. Resolver cliente y obra
        $primerElemento = $elementos[0];
        [$cliente, $obra] = $this->resolverClienteYObra($primerElemento);

        if (!$cliente || !$obra) {
            $this->advertencias[] = "Planilla {$codigo}: no se pudo resolver cliente/obra";
            return;
        }

        // 2. Calcular peso total
        $pesoTotal = array_sum(array_column($elementos, 'peso'));

        // 3. Crear planilla
        $planilla = Planilla::create([
            'users_id' => 1, // Sistema
            'cliente_id' => $cliente->id,
            'obra_id' => $obra->id,
            'codigo' => $codigo,
            'descripcion' => $data['descripcion'] ?? $primerElemento['descripcion_planilla'] ?? null,
            'seccion' => $data['seccion'] ?? $primerElemento['seccion'] ?? '-',
            'ensamblado' => $data['ensamblado'] ?? $primerElemento['ensamblado'] ?? '-',
            'peso_total' => $pesoTotal,
            'fecha_creacion_ferrawin' => $data['fecha_creacion_ferrawin'] ?? null,
            'fecha_estimada_entrega' => null, // Se establecerá cuando el técnico apruebe
            'revisada' => false,
            'aprobada' => false,
        ]);

        $this->stats['planillas_creadas']++;

        // 4. Crear entidades PRIMERO (para poder vincular elementos)
        $entidades = $data["entidades"] ?? [];
        $mapaEntidades = [];
        if (!empty($entidades)) {
            $mapaEntidades = $this->crearEntidades($planilla, $entidades);
        }

        // 5. Agrupar elementos por etiqueta y crear bulk (con mapa de entidades)
        $this->crearElementosBulk($planilla, $elementos, $mapaEntidades);

        // 5. Asignar máquinas
        $this->asignador->repartirPlanilla($planilla->id);

        // 6. Aplicar política de subetiquetas (igual que importación manual)
        $this->processor->aplicarPoliticaSubetiquetasPostAsignacion($planilla);

        // 7. Crear órdenes
        $this->ordenService->crearOrdenParaPlanilla($planilla->id);

        // 8. Actualizar tiempo total
        $this->actualizarTiempoTotal($planilla);
    }

    /**
     * Crea elementos para una planilla (mismo sistema que importación manual).
     *
     * @param Planilla $planilla
     * @param array $elementos
     * @param array $mapaEntidades Mapa de linea normalizada -> planilla_entidad_id
     */
    protected function crearElementosBulk(Planilla $planilla, array $elementos, array $mapaEntidades = []): void
    {
        // Normalizar texto igual que ExcelReader
        $normalizar = fn($t) => mb_strtoupper(
            preg_replace('/\s+/u', ' ', trim((string)$t)),
            'UTF-8'
        ) ?: '—SIN VALOR—';

        // Agrupar por DESCRIPCIÓN + MARCA (igual que ExcelReader.autocompletarEtiquetas)
        $porEtiqueta = [];
        $grupoNum = [];
        $siguiente = 1;

        foreach ($elementos as $elem) {
            $descripcion = $normalizar($elem['descripcion_fila'] ?? '');
            $marca = $normalizar($elem['marca'] ?? '');

            // Clave compuesta: descripción + marca (igual que importación manual)
            $claveGrupo = $descripcion . '|' . $marca;

            // Asignar número de grupo si es nueva combinación
            if (!isset($grupoNum[$claveGrupo])) {
                $grupoNum[$claveGrupo] = $siguiente++;
                Log::channel('ferrawin_sync')->debug("   📌 Grupo {$grupoNum[$claveGrupo]} = '{$descripcion}' + '{$marca}'");
            }

            $numEtiqueta = $grupoNum[$claveGrupo];

            if (!isset($porEtiqueta[$numEtiqueta])) {
                $porEtiqueta[$numEtiqueta] = [];
            }
            $porEtiqueta[$numEtiqueta][] = $elem;
        }

        Log::channel('ferrawin_sync')->info("📦 [BULK] Planilla {$planilla->codigo}: " . count($porEtiqueta) . " grupos de etiquetas (por descripción+marca)");

        // Crear etiquetas y elementos (igual que PlanillaProcessor)
        foreach ($porEtiqueta as $numEtiqueta => $filasEtiqueta) {
            // Crear etiqueta padre con retry logic (igual que importación manual)
            $etiquetaPadre = $this->crearEtiquetaPadreConRetry($planilla, $filasEtiqueta, $numEtiqueta);

            if (!$etiquetaPadre) {
                Log::channel('ferrawin_sync')->error("❌ [BULK] No se pudo crear etiqueta para grupo {$numEtiqueta}");
                continue;
            }

            $this->stats['etiquetas_creadas']++;

            // Crear elementos uno por uno (igual que importación manual)
            foreach ($filasEtiqueta as $elem) {
                $this->crearElemento($planilla, $etiquetaPadre, $elem, $mapaEntidades);
            }

            Log::channel('ferrawin_sync')->debug("   ✅ Etiqueta {$etiquetaPadre->codigo} con " . count($filasEtiqueta) . " elementos");
        }
    }

    /**
     * Crea etiqueta padre con retry logic (igual que PlanillaProcessor).
     */
    protected function crearEtiquetaPadreConRetry(Planilla $planilla, array $filasEtiqueta, $numEtiqueta, int $maxIntentos = 3): ?Etiqueta
    {
        $intento = 0;

        // Obtener nombre y marca del primer elemento del grupo
        $primerElem = $filasEtiqueta[0];
        $descripcionFila = trim($primerElem['descripcion_fila'] ?? '');
        $marca = trim($primerElem['marca'] ?? '');

        // Usar descripcion_fila como nombre, o marca si está vacío
        $nombreEtiqueta = $descripcionFila ?: $marca ?: 'Sin nombre';

        while ($intento < $maxIntentos) {
            try {
                $codigoPadre = $this->codigoService->generarCodigoPadre();

                Log::channel('ferrawin_sync')->debug("🏷️ [BULK] Grupo {$numEtiqueta} → Código: {$codigoPadre} (intento " . ($intento + 1) . ")");

                $etiquetaPadre = Etiqueta::create([
                    'codigo' => $codigoPadre,
                    'planilla_id' => $planilla->id,
                    'nombre' => $nombreEtiqueta,
                    'marca' => $marca ?: null,
                ]);

                return $etiquetaPadre;

            } catch (\Illuminate\Database\QueryException $e) {
                $intento++;

                if ($e->errorInfo[0] === '23000' && str_contains($e->getMessage(), 'codigo')) {
                    Log::channel('ferrawin_sync')->warning("⚠️ [BULK] Código duplicado, reintentando ({$intento}/{$maxIntentos})");

                    if ($intento >= $maxIntentos) {
                        Log::channel('ferrawin_sync')->error("❌ [BULK] No se pudo generar código único después de {$maxIntentos} intentos");
                        return null;
                    }

                    usleep(100 * pow(2, $intento - 1) * 1000);
                    continue;
                }

                Log::channel('ferrawin_sync')->error("❌ [BULK] Error creando etiqueta: " . $e->getMessage());
                throw $e;
            }
        }

        return null;
    }

    /**
     * Crea un elemento individual (igual que PlanillaProcessor).
     *
     * @param Planilla $planilla
     * @param Etiqueta $etiquetaPadre
     * @param array $elem
     * @param array $mapaEntidades Mapa de linea normalizada -> planilla_entidad_id
     */
    protected function crearElemento(Planilla $planilla, Etiqueta $etiquetaPadre, array $elem, array $mapaEntidades = []): void
    {
        $doblesBarra = (int)($elem['dobles_barra'] ?? 0);
        $barras = (int)($elem['barras'] ?? 0);
        $longitud = (float)($elem['longitud'] ?? 0);

        // Buscar planilla_entidad_id usando la fila del elemento
        $planillaEntidadId = null;
        $fila = isset($elem['fila']) ? trim($elem['fila']) : null;

        // Convertir fila vacía a null
        if ($fila === '' || $fila === null) {
            $fila = null;
        }

        if ($fila !== null && !empty($mapaEntidades)) {
            $filaNormalizada = (string)$fila;
            $planillaEntidadId = $mapaEntidades[$filaNormalizada] ?? null;
        }

        // Determinar si requiere elaboración (igual que PlanillaProcessor)
        $diametro = (int)($elem['diametro'] ?? 0);
        $elaborado = $this->determinarElaborado($doblesBarra, $diametro, $longitud);

        Elemento::create([
            'codigo' => Elemento::generarCodigo(),
            'planilla_id' => $planilla->id,
            'planilla_entidad_id' => $planillaEntidadId,
            'etiqueta_id' => $etiquetaPadre->id,
            'etiqueta_sub_id' => null,
            'maquina_id' => null,
            'figura' => $elem['figura'] ?? null,
            'descripcion_fila' => $elem['descripcion_fila'] ?? null,
            'fila' => $fila,
            'marca' => $elem['marca'] ?? null,
            'etiqueta' => $elem['etiqueta'] ?? null,
            'diametro' => (int)($elem['diametro'] ?? 0),
            'longitud' => $longitud,
            'barras' => $barras,
            'dobles_barra' => $doblesBarra,
            'peso' => (float)($elem['peso'] ?? 0),
            'dimensiones' => $elem['dimensiones'] ?? null,
            'tiempo_fabricacion' => $this->calcularTiempo($barras, $doblesBarra),
            'estado' => 'pendiente',
            'elaborado' => $elaborado,
        ]);

        $this->stats['elementos_creados']++;
    }

    /**
     * Determina si un elemento necesita elaboración (igual que PlanillaProcessor).
     *
     * Un elemento NO necesita elaboración (elaborado=0) si:
     * 1. No tiene dobleces (dobles_barra = 0)
     * 2. Existe un producto base con mismo diámetro y longitud
     *
     * @return int 0 = no necesita elaboración, 1 = necesita elaboración
     */
    protected function determinarElaborado(int $doblesBarra, int $diametro, float $longitudMm): int
    {
        // Si tiene dobleces, siempre necesita elaboración
        if ($doblesBarra > 0) {
            return 1;
        }

        // Buscar si existe un producto base exacto (diámetro + longitud)
        foreach ($this->productosBase as $producto) {
            if ($producto['diametro'] === $diametro && abs($producto['longitud'] - $longitudMm) < 10) {
                return 0; // Existe producto base, no necesita elaboración
            }
        }

        return 1; // No existe producto base, necesita elaboración
    }

    /**
     * Actualiza una planilla existente (reimportación).
     */
    protected function actualizarPlanilla(array $data): void
    {
        $codigo = $data['codigo'];
        $planilla = Planilla::where('codigo', $codigo)->first();

        if (!$planilla) {
            return;
        }

        // Actualizar entidades siempre (eliminar existentes y crear nuevas)
        $entidades = $data['entidades'] ?? [];
        if (!empty($entidades)) {
            PlanillaEntidad::where('planilla_id', $planilla->id)->delete();
            $this->crearEntidades($planilla, $entidades);
        }

        // Solo actualizar elementos si hay pendientes
        $pendientes = $planilla->elementos()->where('estado', 'pendiente')->count();

        if ($pendientes === 0) {
            Log::channel('ferrawin_sync')->debug("⏭️ [BULK] Planilla {$codigo}: entidades actualizadas, elementos sin cambios");
            $this->stats['planillas_actualizadas']++;
            return;
        }

        // Eliminar elementos pendientes y reimportar
        $planilla->elementos()->where('estado', 'pendiente')->forceDelete();

        // Eliminar etiquetas huérfanas
        Etiqueta::where('planilla_id', $planilla->id)
            ->whereDoesntHave('elementos')
            ->delete();

        // Reimportar elementos con el mapa de entidades actualizado
        $mapaEntidades = [];
        $entidadesCreadas = PlanillaEntidad::where('planilla_id', $planilla->id)->get();
        foreach ($entidadesCreadas as $entidad) {
            $lineaNormalizada = ltrim($entidad->linea, '0');
            if (empty($lineaNormalizada)) {
                $lineaNormalizada = '0';
            }
            $mapaEntidades[$lineaNormalizada] = $entidad->id;
        }

        $this->crearElementosBulk($planilla, $data['elementos'] ?? [], $mapaEntidades);

        // Reasignar máquinas
        $this->asignador->repartirPlanilla($planilla->id);

        // Aplicar política de subetiquetas (FALTABA!)
        $this->processor->aplicarPoliticaSubetiquetasPostAsignacion($planilla);

        // Actualizar totales
        $this->actualizarTiempoTotal($planilla);

        $this->stats['planillas_actualizadas']++;

        Log::channel('ferrawin_sync')->debug("🔄 [BULK] Planilla {$codigo} actualizada");
    }

    /**
     * Resuelve o crea cliente y obra.
     */
    protected function resolverClienteYObra(array $elemento): array
    {
        $codCliente = trim($elemento['codigo_cliente'] ?? '');
        $nomCliente = trim($elemento['nombre_cliente'] ?? 'Cliente sin nombre');
        $codObra = trim($elemento['codigo_obra'] ?? '');
        $nomObra = trim($elemento['nombre_obra'] ?? 'Obra sin nombre');

        if (!$codCliente || !$codObra) {
            return [null, null];
        }

        // Usar cache
        if (!isset($this->cacheClientes[$codCliente])) {
            $this->cacheClientes[$codCliente] = Cliente::firstOrCreate(
                ['codigo' => $codCliente],
                ['empresa' => $nomCliente]
            );
        }
        $cliente = $this->cacheClientes[$codCliente];

        if (!isset($this->cacheObras[$codObra])) {
            $this->cacheObras[$codObra] = Obra::firstOrCreate(
                ['cod_obra' => $codObra],
                [
                    'cliente_id' => $cliente->id,
                    'obra' => $nomObra,
                ]
            );
        }
        $obra = $this->cacheObras[$codObra];

        return [$cliente, $obra];
    }

    /**
     * Pre-carga clientes y obras en cache.
     */
    protected function precargarCaches(array $planillasData): void
    {
        $codigosClientes = [];
        $codigosObras = [];

        foreach ($planillasData as $planilla) {
            foreach ($planilla['elementos'] ?? [] as $elem) {
                if (!empty($elem['codigo_cliente'])) {
                    $codigosClientes[] = $elem['codigo_cliente'];
                }
                if (!empty($elem['codigo_obra'])) {
                    $codigosObras[] = $elem['codigo_obra'];
                }
            }
        }

        $codigosClientes = array_unique($codigosClientes);
        $codigosObras = array_unique($codigosObras);

        if (!empty($codigosClientes)) {
            $this->cacheClientes = Cliente::whereIn('codigo', $codigosClientes)
                ->get()
                ->keyBy('codigo')
                ->all();
        }

        if (!empty($codigosObras)) {
            $this->cacheObras = Obra::whereIn('cod_obra', $codigosObras)
                ->get()
                ->keyBy('cod_obra')
                ->all();
        }
    }

    /**
     * Calcula tiempo de fabricación.
     */
    protected function calcularTiempo(int $barras, int $dobles): float
    {
        if ($dobles > 0) {
            return $barras * $dobles * 1.5;
        }
        return $barras * 2;
    }

    /**
     * Actualiza el tiempo total de la planilla.
     */
    protected function actualizarTiempoTotal(Planilla $planilla): void
    {
        $elementos = $planilla->elementos()->get();
        $tiempoSetup = config('planillas.importacion.tiempo_setup_elemento', 1200);

        $tiempoTotal = $elementos->sum('tiempo_fabricacion') + ($elementos->count() * $tiempoSetup);
        $pesoTotal = $elementos->sum('peso');

        $planilla->update([
            'tiempo_fabricacion' => $tiempoTotal,
            'peso_total' => $pesoTotal,
        ]);
    }

    /**
     * Registra log de sincronización.
     */
    protected function registrarLog(float $duracion, string $estado, ?string $error = null): void
    {
        FerrawinSyncLog::create([
            'fecha_ejecucion' => now(),
            'estado' => $estado,
            'planillas_encontradas' => $this->stats['planillas_creadas'] + $this->stats['planillas_omitidas'],
            'planillas_nuevas' => $this->stats['planillas_creadas'],
            'planillas_actualizadas' => $this->stats['planillas_actualizadas'],
            'planillas_sincronizadas' => $this->stats['planillas_creadas'] + $this->stats['planillas_actualizadas'],
            'planillas_fallidas' => 0,
            'elementos_creados' => $this->stats['elementos_creados'],
            'errores' => $error ? json_encode([$error]) : null,
            'advertencias' => !empty($this->advertencias) ? json_encode($this->advertencias) : null,
            'duracion_segundos' => $duracion,
        ]);
    }

    /**
     * Resetea estadísticas.
     */
    protected function resetStats(): void
    {
        $this->stats = [
            'planillas_creadas' => 0,
            'planillas_actualizadas' => 0,
            'planillas_omitidas' => 0,
            'elementos_creados' => 0,
            'etiquetas_creadas' => 0,
            'entidades_creadas' => 0,
        ];
        $this->advertencias = [];
        $this->cacheClientes = [];
        $this->cacheObras = [];
    }

    /**
     * Crea las entidades/ensamblajes de una planilla.
     *
     * @return array Mapa de linea normalizada -> planilla_entidad_id
     */
    protected function crearEntidades(Planilla $planilla, array $entidades): array
    {
        if (empty($entidades)) {
            return [];
        }

        $now = now();
        $entidadesInsert = [];

        foreach ($entidades as $entidad) {
            // Construir datos de dibujo a partir de los elementos de composición
            $dibujoData = $this->construirDibujoData($entidad);

            $entidadesInsert[] = [
                "planilla_id" => $planilla->id,
                "linea" => $entidad["linea"] ?? null,
                "marca" => $entidad["marca"] ?? "SIN MARCA",
                "situacion" => $entidad["situacion"] ?? "SIN SITUACION",
                "cantidad" => (int)($entidad["cantidad"] ?? 1),
                "miembros" => (int)($entidad["miembros"] ?? 1),
                "modelo" => $entidad["modelo"] ?? null,
                "cotas" => $entidad["cotas"] ?? null,
                "longitud_ensamblaje" => $entidad["resumen"]["longitud_ensamblaje"] ?? null,
                "peso_total" => $entidad["resumen"]["peso_total"] ?? null,
                "total_barras" => $entidad["resumen"]["total_barras"] ?? 0,
                "total_estribos" => $entidad["resumen"]["total_estribos"] ?? 0,
                "composicion" => json_encode($entidad["composicion"] ?? []),
                "distribucion" => json_encode($entidad["distribucion"] ?? []),
                "dibujo_data" => json_encode($dibujoData),
                "created_at" => $now,
                "updated_at" => $now,
            ];
        }

        // Bulk insert
        foreach (array_chunk($entidadesInsert, 50) as $chunk) {
            PlanillaEntidad::insert($chunk);
        }

        $this->stats["entidades_creadas"] = ($this->stats["entidades_creadas"] ?? 0) + count($entidadesInsert);

        Log::channel("ferrawin_sync")->debug("   [BULK] " . count($entidadesInsert) . " entidades creadas para planilla " . $planilla->codigo);

        // Construir mapa de linea normalizada -> id
        $mapaEntidades = [];
        $entidadesCreadas = PlanillaEntidad::where('planilla_id', $planilla->id)->get();

        foreach ($entidadesCreadas as $entidad) {
            $lineaNormalizada = ltrim($entidad->linea, '0');
            if (empty($lineaNormalizada)) {
                $lineaNormalizada = '0';
            }
            $mapaEntidades[$lineaNormalizada] = $entidad->id;
        }

        return $mapaEntidades;
    }

    /**
     * Construye los datos de dibujo a partir de la entidad.
     *
     * Procesa los campos ZOBJETO y ZFIGURA de cada elemento para extraer
     * coordenadas y parámetros de representación gráfica.
     */
    protected function construirDibujoData(array $entidad): array
    {
        $dibujoData = [
            'elementos' => [],
            'canvas' => [
                'width' => 0,
                'height' => 0,
            ],
        ];

        $maxWidth = 0;
        $maxHeight = 0;

        // Procesar barras
        $barras = $entidad['composicion']['barras'] ?? [];
        foreach ($barras as $index => $barra) {
            $zobjeto = $barra['zobjeto'] ?? null;
            $figura = $barra['figura'] ?? null;

            $elementoDibujo = ZobjetoParser::buildDibujoData($zobjeto, $figura, [
                'tipo' => 'barra',
                'index' => $index,
                'diametro' => $barra['diametro'] ?? null,
                'cantidad' => $barra['cantidad'] ?? 1,
                'longitud' => $barra['longitud'] ?? null,
                'dobleces' => 0,
            ]);

            // Actualizar dimensiones máximas del canvas
            if (($elementoDibujo['canvas']['width'] ?? 0) > $maxWidth) {
                $maxWidth = $elementoDibujo['canvas']['width'];
            }
            if (($elementoDibujo['canvas']['height'] ?? 0) > $maxHeight) {
                $maxHeight = $elementoDibujo['canvas']['height'];
            }

            $dibujoData['elementos'][] = $elementoDibujo;
        }

        // Procesar estribos
        $estribos = $entidad['composicion']['estribos'] ?? [];
        foreach ($estribos as $index => $estribo) {
            $zobjeto = $estribo['zobjeto'] ?? null;
            $figura = $estribo['figura'] ?? null;

            $elementoDibujo = ZobjetoParser::buildDibujoData($zobjeto, $figura, [
                'tipo' => 'estribo',
                'index' => $index,
                'diametro' => $estribo['diametro'] ?? null,
                'cantidad' => $estribo['cantidad'] ?? 1,
                'longitud' => $estribo['longitud'] ?? null,
                'dobleces' => $estribo['dobleces'] ?? 0,
            ]);

            if (($elementoDibujo['canvas']['width'] ?? 0) > $maxWidth) {
                $maxWidth = $elementoDibujo['canvas']['width'];
            }
            if (($elementoDibujo['canvas']['height'] ?? 0) > $maxHeight) {
                $maxHeight = $elementoDibujo['canvas']['height'];
            }

            $dibujoData['elementos'][] = $elementoDibujo;
        }

        $dibujoData['canvas']['width'] = $maxWidth;
        $dibujoData['canvas']['height'] = $maxHeight;

        return $dibujoData;
    }
}