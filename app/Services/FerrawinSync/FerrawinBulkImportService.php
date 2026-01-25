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
use App\Services\EtiquetaEnsamblajeService;
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
        protected \App\Services\PlanillaImport\PlanillaProcessor $processor,
        protected EtiquetaEnsamblajeService $etiquetaEnsamblajeService
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

        // Configuración de reintentos para deadlocks
        $maxIntentos = 3;
        $intento = 0;
        $ultimoError = null;

        while ($intento < $maxIntentos) {
            $intento++;

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
                    'intento' => $intento,
                ]);

                return [
                    'planillas_creadas' => $this->stats['planillas_creadas'],
                    'planillas_actualizadas' => $this->stats['planillas_actualizadas'],
                    'planillas_omitidas' => $this->stats['planillas_omitidas'],
                    'elementos_creados' => $this->stats['elementos_creados'],
                    'etiquetas_creadas' => $this->stats['etiquetas_creadas'],
                    'entidades_creadas' => $this->stats['entidades_creadas'],
                    'etiquetas_ensamblaje_creadas' => $this->stats['etiquetas_ensamblaje_creadas'],
                    'advertencias' => $this->advertencias,
                    'duracion' => $duracion,
                ];

            } catch (\Throwable $e) {
                DB::rollBack();
                $this->codigoService->resetearContadorBatch();
                $ultimoError = $e;

                // Verificar si es un deadlock (código 40001 o mensaje contiene "Deadlock")
                $esDeadlock = $this->esErrorDeadlock($e);

                if ($esDeadlock && $intento < $maxIntentos) {
                    // Reset stats para el siguiente intento
                    $this->resetStats();

                    // Espera exponencial antes de reintentar
                    $esperaMs = 100 * pow(2, $intento - 1); // 100ms, 200ms, 400ms
                    Log::channel('ferrawin_sync')->warning("⚠️ [BULK] Deadlock detectado, reintentando en {$esperaMs}ms (intento {$intento}/{$maxIntentos})", [
                        'error' => $e->getMessage(),
                    ]);
                    usleep($esperaMs * 1000);
                    continue;
                }

                // No es deadlock o se agotaron los reintentos
                Log::channel('ferrawin_sync')->error("❌ [BULK] Error en importación", [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'intento' => $intento,
                    'es_deadlock' => $esDeadlock,
                ]);

                $this->registrarLog(
                    round(microtime(true) - $inicio, 2),
                    'error',
                    $e->getMessage()
                );

                throw $e;
            }
        }

        // Si llegamos aquí, se agotaron los reintentos
        throw $ultimoError;
    }

    /**
     * Determina si un error es un deadlock de MySQL.
     */
    protected function esErrorDeadlock(\Throwable $e): bool
    {
        $mensaje = $e->getMessage();

        // Código SQLSTATE para deadlock
        if (str_contains($mensaje, '40001')) {
            return true;
        }

        // Código de error MySQL para deadlock
        if (str_contains($mensaje, '1213')) {
            return true;
        }

        // Mensaje explícito
        if (stripos($mensaje, 'deadlock') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Importa una planilla individual con sus elementos.
     */
    protected function importarPlanilla(array $data): void
    {
        $codigo = $data['codigo'];
        $elementos = $data['elementos'] ?? [];
        $sinElementos = $data['sin_elementos'] ?? false;

        // Si tiene elementos, procesarla normalmente
        if (!empty($elementos)) {
            $this->importarPlanillaConElementos($data);
            return;
        }

        // Si no tiene elementos pero es una planilla válida, importar solo la cabecera
        if ($sinElementos) {
            $this->importarPlanillaSinElementos($data);
            return;
        }

        // Si no tiene elementos y no viene marcada como sin_elementos, advertir
        $this->advertencias[] = "Planilla {$codigo}: sin elementos";
    }

    /**
     * Importa una planilla que tiene elementos.
     */
    protected function importarPlanillaConElementos(array $data): void
    {
        $codigo = $data['codigo'];
        $elementos = $data['elementos'];

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

            // 4b. Generar etiquetas de ensamblaje para cada entidad
            $etiquetasGeneradas = $this->etiquetaEnsamblajeService->generarParaPlanilla($planilla);
            $this->stats['etiquetas_ensamblaje_creadas'] = ($this->stats['etiquetas_ensamblaje_creadas'] ?? 0) + $etiquetasGeneradas->count();

            Log::channel('ferrawin_sync')->debug("   🏷️ [BULK] {$etiquetasGeneradas->count()} etiquetas de ensamblaje generadas");
        }

        // 5. Agrupar elementos por etiqueta y crear bulk (con mapa de entidades)
        $this->crearElementosBulk($planilla, $elementos, $mapaEntidades);

        // 6. Asignar máquinas
        $this->asignador->repartirPlanilla($planilla->id);

        // 7. Aplicar política de subetiquetas (igual que importación manual)
        $this->processor->aplicarPoliticaSubetiquetasPostAsignacion($planilla);

        // 8. Crear órdenes
        $this->ordenService->crearOrdenParaPlanilla($planilla->id);

        // 9. Actualizar tiempo total
        $this->actualizarTiempoTotal($planilla);
    }

    /**
     * Importa una planilla sin elementos (solo cabecera).
     */
    protected function importarPlanillaSinElementos(array $data): void
    {
        $codigo = $data['codigo'];

        Log::channel('ferrawin_sync')->debug("📥 [BULK] Procesando planilla {$codigo} (sin elementos - solo cabecera)");

        // Resolver cliente y obra usando los datos de la cabecera
        [$cliente, $obra] = $this->resolverClienteYObra([
            'codigo_cliente' => $data['codigo_cliente'] ?? '',
            'nombre_cliente' => $data['nombre_cliente'] ?? '',
            'codigo_obra' => $data['codigo_obra'] ?? '',
            'nombre_obra' => $data['nombre_obra'] ?? '',
        ]);

        if (!$cliente || !$obra) {
            $this->advertencias[] = "Planilla {$codigo}: sin elementos y no se pudo resolver cliente/obra";
            return;
        }

        // Crear planilla sin elementos pero con cliente/obra
        $planilla = Planilla::create([
            'users_id' => 1, // Sistema
            'cliente_id' => $cliente->id,
            'obra_id' => $obra->id,
            'codigo' => $codigo,
            'descripcion' => $data['descripcion'] ?? null,
            'seccion' => $data['seccion'] ?? '-',
            'ensamblado' => $data['ensamblado'] ?? '-',
            'peso_total' => 0,
            'fecha_creacion_ferrawin' => $data['fecha_creacion_ferrawin'] ?? null,
            'fecha_estimada_entrega' => null,
            'revisada' => false,
            'aprobada' => false,
        ]);

        $this->stats['planillas_creadas']++;
        $this->advertencias[] = "Planilla {$codigo}: importada sin elementos (solo cabecera)";
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
            // Normalizar igual que en crearEntidades(): quitar ceros a la izquierda
            $filaNormalizada = ltrim((string)$fila, '0');
            if (empty($filaNormalizada)) {
                $filaNormalizada = '0';
            }
            $planillaEntidadId = $mapaEntidades[$filaNormalizada] ?? null;
        }

        // Determinar si requiere elaboración (igual que PlanillaProcessor)
        $diametro = (int)($elem['diametro'] ?? 0);
        $elaborado = $this->determinarElaborado($doblesBarra, $diametro, $longitud);

        // Obtener ferrawin_id si viene en los datos
        $ferrawinId = $elem['ferrawin_id'] ?? null;

        // Truncar campos de texto para evitar "Data too long" error
        $figura = isset($elem['figura']) ? mb_substr($elem['figura'], 0, 50) : null;
        $descripcionFila = isset($elem['descripcion_fila']) ? mb_substr($elem['descripcion_fila'], 0, 500) : null;
        $marca = isset($elem['marca']) ? mb_substr($elem['marca'], 0, 255) : null;
        $etiquetaVal = isset($elem['etiqueta']) ? mb_substr($elem['etiqueta'], 0, 50) : null;
        $ferrawinIdTrunc = $ferrawinId ? mb_substr($ferrawinId, 0, 50) : null;

        Elemento::create([
            'codigo' => Elemento::generarCodigo(),
            'planilla_id' => $planilla->id,
            'ferrawin_id' => $ferrawinIdTrunc,
            'planilla_entidad_id' => $planillaEntidadId,
            'etiqueta_id' => $etiquetaPadre->id,
            'etiqueta_sub_id' => null,
            'maquina_id' => null,
            'figura' => $figura,
            'descripcion_fila' => $descripcionFila,
            'fila' => $fila,
            'marca' => $marca,
            'etiqueta' => $etiquetaVal,
            'diametro' => (int)($elem['diametro'] ?? 0),
            'longitud' => $longitud,
            'barras' => $barras,
            'dobles_barra' => $doblesBarra,
            'peso' => (float)($elem['peso'] ?? 0),
            'dimensiones' => $elem['dimensiones'] ?? null,
            'tiempo_fabricacion' => $this->calcularTiempo($barras, $doblesBarra),
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
     * Actualiza una planilla existente (reimportación con upsert por ferrawin_id).
     *
     * Lógica:
     * - Elementos pendientes con ferrawin_id que existe en FerraWin → actualizar datos
     * - Elementos pendientes con ferrawin_id que NO existe en FerraWin → eliminar (técnico los borró)
     * - Elementos nuevos en FerraWin que no existen en BD → crear
     * - Elementos fabricando/fabricado/completado → NUNCA tocar
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

            // Regenerar etiquetas de ensamblaje (solo las pendientes se eliminan)
            $etiquetasGeneradas = $this->etiquetaEnsamblajeService->regenerarParaPlanilla($planilla);
            $this->stats['etiquetas_ensamblaje_creadas'] = ($this->stats['etiquetas_ensamblaje_creadas'] ?? 0) + $etiquetasGeneradas->count();
        }

        // Construir mapa de entidades
        $mapaEntidades = [];
        $entidadesCreadas = PlanillaEntidad::where('planilla_id', $planilla->id)->get();
        foreach ($entidadesCreadas as $entidad) {
            $lineaNormalizada = ltrim($entidad->linea, '0');
            if (empty($lineaNormalizada)) {
                $lineaNormalizada = '0';
            }
            $mapaEntidades[$lineaNormalizada] = $entidad->id;
        }

        // Obtener elementos de FerraWin
        $elementosFerrawin = $data['elementos'] ?? [];

        if (empty($elementosFerrawin)) {
            Log::channel('ferrawin_sync')->debug("⏭️ [BULK] Planilla {$codigo}: sin elementos en FerraWin");
            $this->stats['planillas_actualizadas']++;
            return;
        }

        // Crear mapa de ferrawin_id → datos de FerraWin
        $ferrawinIds = [];
        foreach ($elementosFerrawin as $elem) {
            $ferrawinId = $elem['ferrawin_id'] ?? null;
            if ($ferrawinId) {
                $ferrawinIds[$ferrawinId] = $elem;
            }
        }

        // Obtener elementos NO ELABORADOS actuales con ferrawin_id
        $elementosPendientes = $planilla->elementos()
            ->where(function($q) {
                $q->where('elaborado', '!=', 1)->orWhereNull('elaborado');
            })
            ->whereNotNull('ferrawin_id')
            ->get()
            ->keyBy('ferrawin_id');

        // Obtener elementos NO ELABORADOS sin ferrawin_id (de sync anterior sin esta feature)
        $elementosSinFerrawinId = $planilla->elementos()
            ->where(function($q) {
                $q->where('elaborado', '!=', 1)->orWhereNull('elaborado');
            })
            ->whereNull('ferrawin_id')
            ->count();

        $actualizados = 0;
        $creados = 0;
        $eliminados = 0;

        // 1. Actualizar o crear elementos
        foreach ($ferrawinIds as $ferrawinId => $elemData) {
            if ($elementosPendientes->has($ferrawinId)) {
                // Actualizar elemento existente (solo datos que pueden cambiar)
                $elemento = $elementosPendientes->get($ferrawinId);
                $this->actualizarElementoExistente($elemento, $elemData, $mapaEntidades);
                $actualizados++;
            } else {
                // Crear nuevo elemento (no existe en BD o ya estaba elaborado)
                $existeElaborado = $planilla->elementos()
                    ->where('ferrawin_id', $ferrawinId)
                    ->where('elaborado', 1)
                    ->exists();

                if (!$existeElaborado) {
                    // Crear usando el flujo existente (respetando agrupación por etiqueta)
                    $this->crearElementoIndividual($planilla, $elemData, $mapaEntidades);
                    $creados++;
                }
            }
        }

        // 2. Eliminar elementos pendientes que ya no existen en FerraWin
        foreach ($elementosPendientes as $ferrawinId => $elemento) {
            if (!isset($ferrawinIds[$ferrawinId])) {
                // El técnico eliminó este elemento en FerraWin
                $elemento->forceDelete();
                $eliminados++;
            }
        }

        // 3. Si hay elementos sin ferrawin_id (de sync anterior), eliminarlos y reimportar
        if ($elementosSinFerrawinId > 0) {
            Log::channel('ferrawin_sync')->info("🔄 [BULK] Planilla {$codigo}: {$elementosSinFerrawinId} elementos sin ferrawin_id, reimportando");
            $planilla->elementos()
                ->where(function($q) {
                    $q->where('elaborado', '!=', 1)->orWhereNull('elaborado');
                })
                ->whereNull('ferrawin_id')
                ->forceDelete();
            $eliminados += $elementosSinFerrawinId;
        }

        // Eliminar etiquetas huérfanas (verificando por etiqueta_id, no por relación elementos)
        $etiquetasHuerfanas = Etiqueta::where('planilla_id', $planilla->id)
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('elementos')
                    ->whereColumn('elementos.etiqueta_id', 'etiquetas.id');
            })
            ->pluck('id');

        if ($etiquetasHuerfanas->isNotEmpty()) {
            Etiqueta::whereIn('id', $etiquetasHuerfanas)->delete();
        }

        // Reasignar máquinas solo si hubo cambios
        if ($creados > 0 || $eliminados > 0) {
            $this->asignador->repartirPlanilla($planilla->id);
            $this->processor->aplicarPoliticaSubetiquetasPostAsignacion($planilla);
        }

        // Actualizar totales
        $this->actualizarTiempoTotal($planilla);

        $this->stats['planillas_actualizadas']++;
        $this->stats['elementos_creados'] += $creados;

        Log::channel('ferrawin_sync')->debug("🔄 [BULK] Planilla {$codigo} actualizada", [
            'actualizados' => $actualizados,
            'creados' => $creados,
            'eliminados' => $eliminados,
        ]);
    }

    /**
     * Actualiza un elemento existente con nuevos datos de FerraWin.
     * Solo actualiza campos que pueden cambiar, NO toca estado/maquina/etiqueta.
     */
    protected function actualizarElementoExistente(Elemento $elemento, array $data, array $mapaEntidades): void
    {
        $fila = isset($data['fila']) ? trim($data['fila']) : null;
        $planillaEntidadId = null;

        if ($fila !== null && !empty($mapaEntidades)) {
            // Normalizar igual que en crearEntidades(): quitar ceros a la izquierda
            $filaNormalizada = ltrim((string)$fila, '0');
            if (empty($filaNormalizada)) {
                $filaNormalizada = '0';
            }
            $planillaEntidadId = $mapaEntidades[$filaNormalizada] ?? null;
        }

        $doblesBarra = (int)($data['dobles_barra'] ?? 0);
        $barras = (int)($data['barras'] ?? 0);
        $longitud = (float)($data['longitud'] ?? 0);
        $diametro = (int)($data['diametro'] ?? 0);

        // Truncar campos de texto para evitar "Data too long" error
        $figura = isset($data['figura']) ? mb_substr($data['figura'], 0, 50) : $elemento->figura;
        $descripcionFila = isset($data['descripcion_fila']) ? mb_substr($data['descripcion_fila'], 0, 500) : $elemento->descripcion_fila;
        $marca = isset($data['marca']) ? mb_substr($data['marca'], 0, 255) : $elemento->marca;

        $elemento->update([
            'planilla_entidad_id' => $planillaEntidadId,
            'figura' => $figura,
            'descripcion_fila' => $descripcionFila,
            'fila' => $fila,
            'marca' => $marca,
            'diametro' => $diametro,
            'longitud' => $longitud,
            'barras' => $barras,
            'dobles_barra' => $doblesBarra,
            'peso' => (float)($data['peso'] ?? $elemento->peso),
            'dimensiones' => $data['dimensiones'] ?? $elemento->dimensiones,
            'tiempo_fabricacion' => $this->calcularTiempo($barras, $doblesBarra),
            'elaborado' => $this->determinarElaborado($doblesBarra, $diametro, $longitud),
        ]);
    }

    /**
     * Crea un elemento individual (sin agrupar por etiqueta).
     * Se usa para elementos nuevos durante la actualización.
     */
    protected function crearElementoIndividual(Planilla $planilla, array $elem, array $mapaEntidades): void
    {
        // Buscar o crear etiqueta basada en descripcion_fila + marca
        $descripcionFila = trim($elem['descripcion_fila'] ?? '');
        $marca = trim($elem['marca'] ?? '');
        $nombreEtiqueta = $descripcionFila ?: $marca ?: 'Sin nombre';

        // Buscar etiqueta existente con mismo nombre y marca
        $etiqueta = Etiqueta::where('planilla_id', $planilla->id)
            ->where('nombre', $nombreEtiqueta)
            ->where('marca', $marca ?: null)
            ->first();

        if (!$etiqueta) {
            // Crear nueva etiqueta
            $etiqueta = $this->crearEtiquetaPadreConRetry($planilla, [$elem], 0);
            if (!$etiqueta) {
                Log::channel('ferrawin_sync')->error("❌ [BULK] No se pudo crear etiqueta para elemento nuevo");
                return;
            }
            $this->stats['etiquetas_creadas']++;
        }

        // Crear el elemento
        $this->crearElemento($planilla, $etiqueta, $elem, $mapaEntidades);
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

        // Normalizar códigos: quitar ceros a la izquierda ("0042" -> "42")
        // Esto evita duplicados cuando la importación manual ya normalizó el código
        $codClienteNormalizado = ltrim($codCliente, '0') ?: '0';
        $codObraNormalizado = ltrim($codObra, '0') ?: '0';

        // Usar cache
        if (!isset($this->cacheClientes[$codClienteNormalizado])) {
            $this->cacheClientes[$codClienteNormalizado] = Cliente::firstOrCreate(
                ['codigo' => $codClienteNormalizado],
                ['empresa' => $nomCliente]
            );
        }
        $cliente = $this->cacheClientes[$codClienteNormalizado];

        if (!isset($this->cacheObras[$codObraNormalizado])) {
            $this->cacheObras[$codObraNormalizado] = Obra::firstOrCreate(
                ['cod_obra' => $codObraNormalizado],
                [
                    'cliente_id' => $cliente->id,
                    'obra' => $nomObra,
                ]
            );
        }
        $obra = $this->cacheObras[$codObraNormalizado];

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
                    // Normalizar: quitar ceros a la izquierda
                    $codNormalizado = ltrim($elem['codigo_cliente'], '0') ?: '0';
                    $codigosClientes[] = $codNormalizado;
                }
                if (!empty($elem['codigo_obra'])) {
                    // Normalizar: quitar ceros a la izquierda
                    $codNormalizado = ltrim($elem['codigo_obra'], '0') ?: '0';
                    $codigosObras[] = $codNormalizado;
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
            'etiquetas_ensamblaje_creadas' => 0,
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

            // Truncar campos de texto para evitar "Data too long" error
            $linea = isset($entidad["linea"]) ? mb_substr($entidad["linea"], 0, 20) : null;
            $marca = isset($entidad["marca"]) ? mb_substr($entidad["marca"], 0, 100) : "SIN MARCA";
            $situacion = isset($entidad["situacion"]) ? mb_substr($entidad["situacion"], 0, 255) : "SIN SITUACION";
            $modelo = isset($entidad["modelo"]) ? mb_substr($entidad["modelo"], 0, 50) : null;
            $cotas = isset($entidad["cotas"]) ? mb_substr($entidad["cotas"], 0, 255) : null;

            $entidadesInsert[] = [
                "planilla_id" => $planilla->id,
                "linea" => $linea,
                "marca" => $marca,
                "situacion" => $situacion,
                "cantidad" => (int)($entidad["cantidad"] ?? 1),
                "miembros" => (int)($entidad["miembros"] ?? 1),
                "modelo" => $modelo,
                "cotas" => $cotas,
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