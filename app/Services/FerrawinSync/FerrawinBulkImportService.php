<?php

namespace App\Services\FerrawinSync;

use App\Models\Planilla;
use App\Models\PlanillaEntidad;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\FerrawinSyncLog;
use App\Services\AsignarMaquinaService;
use App\Services\OrdenPlanillaService;
use App\Services\PlanillaImport\CodigoEtiqueta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    protected int $contadorElementos = 0;

    public function __construct(
        protected CodigoEtiqueta $codigoService,
        protected AsignarMaquinaService $asignador,
        protected OrdenPlanillaService $ordenService
    ) {}

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

            // 3. Inicializar contadores
            $this->codigoService->inicializarContadorBatch();
            $this->inicializarContadorElementos();

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
            'fecha_estimada_entrega' => now()->addDays(7)->setTime(10, 0, 0),
            'revisada' => false,
        ]);

        $this->stats['planillas_creadas']++;

        // 4. Agrupar elementos por etiqueta y crear bulk
        $this->crearElementosBulk($planilla, $elementos);

        // 4b. Crear entidades si vienen en los datos
        $entidades = $data["entidades"] ?? [];
        if (!empty($entidades)) {
            $this->crearEntidades($planilla, $entidades);
        }

        // 5. Asignar máquinas
        $this->asignador->repartirPlanilla($planilla->id);

        // 6. Crear órdenes
        $this->ordenService->crearOrdenParaPlanilla($planilla->id);

        // 7. Actualizar tiempo total
        $this->actualizarTiempoTotal($planilla);
    }

    /**
     * Crea elementos en bulk para una planilla.
     */
    protected function crearElementosBulk(Planilla $planilla, array $elementos): void
    {
        // Agrupar por descripción (etiqueta)
        $porEtiqueta = [];
        foreach ($elementos as $elem) {
            $nombreEtiqueta = $elem['descripcion_fila'] ?? $elem['nombre_etiqueta'] ?? 'Sin nombre';
            $marca = $elem['marca'] ?? '';
            $clave = $nombreEtiqueta . '|' . $marca;

            if (!isset($porEtiqueta[$clave])) {
                $porEtiqueta[$clave] = [
                    'nombre' => $nombreEtiqueta,
                    'elementos' => [],
                ];
            }
            $porEtiqueta[$clave]['elementos'][] = $elem;
        }

        // Crear etiquetas y elementos
        foreach ($porEtiqueta as $grupo) {
            // Crear etiqueta padre
            $codigoPadre = $this->codigoService->generarCodigoPadre();
            $etiqueta = Etiqueta::create([
                'codigo' => $codigoPadre,
                'planilla_id' => $planilla->id,
                'nombre' => $grupo['nombre'],
            ]);

            $this->stats['etiquetas_creadas']++;

            // Preparar datos para bulk insert
            $elementosInsert = [];
            $now = now();

            foreach ($grupo['elementos'] as $elem) {
                $elementosInsert[] = [
                    'codigo' => $this->generarCodigoElementoBatch(),
                    'planilla_id' => $planilla->id,
                    'etiqueta_id' => $etiqueta->id,
                    'figura' => $elem['figura'] ?? null,
                    'fila' => $elem['fila'] ?? null,
                    'marca' => $elem['marca'] ?? null,
                    'diametro' => (int)($elem['diametro'] ?? 0),
                    'longitud' => (float)($elem['longitud'] ?? 0),
                    'barras' => (int)($elem['barras'] ?? 0),
                    'dobles_barra' => (int)($elem['dobles_barra'] ?? 0),
                    'peso' => (float)($elem['peso'] ?? 0),
                    'dimensiones' => $elem['dimensiones'] ?? null,
                    'tiempo_fabricacion' => $this->calcularTiempo(
                        (int)($elem['barras'] ?? 0),
                        (int)($elem['dobles_barra'] ?? 0)
                    ),
                    'estado' => 'pendiente',
                    'elaborado' => ($elem['dobles_barra'] ?? 0) > 0 ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Bulk insert en chunks de 100
            foreach (array_chunk($elementosInsert, 100) as $chunk) {
                Elemento::insert($chunk);
                $this->stats['elementos_creados'] += count($chunk);
            }
        }
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

        // Solo actualizar si hay elementos pendientes
        $pendientes = $planilla->elementos()->where('estado', 'pendiente')->count();

        if ($pendientes === 0) {
            Log::channel('ferrawin_sync')->debug("⏭️ [BULK] Planilla {$codigo}: sin elementos pendientes, omitida");
            return;
        }

        // Eliminar elementos pendientes y reimportar
        $planilla->elementos()->where('estado', 'pendiente')->forceDelete();

        // Eliminar etiquetas huérfanas
        Etiqueta::where('planilla_id', $planilla->id)
            ->whereDoesntHave('elementos')
            ->delete();

        // Reimportar elementos
        $this->crearElementosBulk($planilla, $data['elementos'] ?? []);

        // Reasignar máquinas
        $this->asignador->repartirPlanilla($planilla->id);

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
     * Inicializa el contador de elementos obteniendo el último usado.
     */
    protected function inicializarContadorElementos(): void
    {
        $prefijo = 'EL' . now()->format('ym');

        $ultimo = Elemento::where('codigo', 'like', "$prefijo%")
            ->orderByDesc(DB::raw("CAST(SUBSTRING(codigo, LENGTH('$prefijo') + 1) AS UNSIGNED)"))
            ->value('codigo');

        if ($ultimo) {
            $this->contadorElementos = (int)substr($ultimo, strlen($prefijo));
        } else {
            $this->contadorElementos = 0;
        }
    }

    /**
     * Genera código único para elemento en batch.
     */
    protected function generarCodigoElementoBatch(): string
    {
        $this->contadorElementos++;
        $prefijo = 'EL' . now()->format('ym');
        return $prefijo . str_pad($this->contadorElementos, 5, '0', STR_PAD_LEFT);
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
        $this->contadorElementos = 0;
    }

    /**
     * Crea las entidades/ensamblajes de una planilla.
     */
    protected function crearEntidades(Planilla $planilla, array $entidades): void
    {
        if (empty($entidades)) {
            return;
        }

        $now = now();
        $entidadesInsert = [];

        foreach ($entidades as $entidad) {
            $entidadesInsert[] = [
                "planilla_id" => $planilla->id,
                "linea" => $entidad["linea"] ?? null,
                "marca" => $entidad["marca"] ?? "SIN MARCA",
                "situacion" => $entidad["situacion"] ?? "SIN SITUACION",
                "cantidad" => (int)($entidad["cantidad"] ?? 1),
                "miembros" => (int)($entidad["miembros"] ?? 1),
                "modelo" => $entidad["modelo"] ?? null,
                "longitud_ensamblaje" => $entidad["resumen"]["longitud_ensamblaje"] ?? null,
                "peso_total" => $entidad["resumen"]["peso_total"] ?? null,
                "total_barras" => $entidad["resumen"]["total_barras"] ?? 0,
                "total_estribos" => $entidad["resumen"]["total_estribos"] ?? 0,
                "composicion" => json_encode($entidad["composicion"] ?? []),
                "distribucion" => json_encode($entidad["distribucion"] ?? []),
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
    }
}