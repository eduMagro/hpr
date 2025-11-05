<?php

namespace App\Services\PlanillaImport;

use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Services\AsignarMaquinaService;
use App\Services\OrdenPlanillaService;
use App\Services\PlanillaImport\ExcelValidator;
use App\Services\PlanillaImport\ExcelReader;
use App\Services\PlanillaImport\PlanillaProcessor;
use App\Services\PlanillaImport\DTOs\DatosImportacion;
use App\Services\PlanillaImport\DTOs\ImportResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PlanillaImport\CodigoEtiqueta;

/**
 * Servicio principal para importación de planillas - VERSIÓN OPTIMIZADA
 * 
 * Mejoras de rendimiento:
 * - Batch processing de múltiples planillas
 * - Bulk inserts para elementos y etiquetas
 * - Reducción de queries N+1
 * - Cache de máquinas y validaciones
 * - ✅ CORREGIDO: Códigos de etiqueta correlativos en batch
 */
class PlanillaImportService
{
    protected array $cacheMaquinas = [];
    protected array $cacheClientes = [];
    protected array $cacheObras = [];

    public function __construct(
        protected ExcelValidator $validator,
        protected ExcelReader $reader,
        protected PlanillaProcessor $processor,
        protected AsignarMaquinaService $asignador,
        protected OrdenPlanillaService $ordenService,
        protected CodigoEtiqueta $codigoService  // ✅ NUEVO
    ) {}
    /**
     * Importa planillas desde un archivo Excel.
     *
     * @param UploadedFile $file
     * @return ImportResult
     */
    public function importar(UploadedFile $file): ImportResult
    {
        $nombreArchivo = $file->getClientOriginalName();

        Log::channel('planilla_import')->info("📥 Iniciando importación de archivo: {$nombreArchivo}");

        // 1. VALIDACIÓN PRE-PROCESAMIENTO
        $validacion = $this->validator->validar($file);

        if (!$validacion->esValido()) {
            Log::channel('planilla_import')->warning("❌ Validación fallida: {$nombreArchivo}", $validacion->errores());
            return ImportResult::error(
                $validacion->errores(),
                $validacion->advertencias(),
                $nombreArchivo
            );
        }

        // 2. LECTURA Y PREPARACIÓN DE DATOS
        $datos = $this->reader->leer($file);

        if ($datos->estaVacio()) {
            return ImportResult::error([
                "{$nombreArchivo} no contiene filas válidas tras filtrado."
            ], [], $nombreArchivo);
        }

        Log::channel('planilla_import')->info("📊 Datos leídos", [
            'total_filas' => $datos->totalFilas(),
            'filas_validas' => $datos->filasValidas(),
            'planillas_detectadas' => $datos->planillasDetectadas(),
        ]);

        // 3. VERIFICAR DUPLICADOS (una sola query)
        $duplicados = $this->verificarDuplicados($datos->codigosPlanillas());

        $advertenciasIniciales = [];
        $datosFiltrados = $datos;

        if (!empty($duplicados)) {
            // ⚠️ ADVERTIR sobre duplicados pero CONTINUAR
            $advertenciasIniciales[] = "Las siguientes planillas ya existen y fueron omitidas: " . implode(', ', $duplicados);
            $advertenciasIniciales[] = "Use el botón 'Reimportar' para actualizar planillas existentes.";

            Log::channel('planilla_import')->warning("⚠️ Planillas duplicadas detectadas, serán omitidas", [
                'duplicados' => $duplicados,
            ]);

            // FILTRAR las planillas duplicadas del procesamiento
            $datosFiltrados = $datos->filtrarPlanillas($duplicados);

            // Si después de filtrar no queda nada, entonces sí es error
            if ($datosFiltrados->estaVacio()) {
                return ImportResult::error([
                    "Todas las planillas del archivo ya existen en el sistema."
                ], $advertenciasIniciales, $nombreArchivo);
            }

            Log::channel('planilla_import')->info("📊 Continuando con planillas no duplicadas", [
                'planillas_a_procesar' => $datosFiltrados->planillasDetectadas(),
            ]);
        }

        $this->precargarCaches($datosFiltrados);

        // ✅ 5. INICIALIZAR SERVICIO DE CÓDIGOS
        $this->codigoService->inicializarContadorBatch();
        Log::channel('planilla_import')->info("🔢 Servicio de códigos inicializado");

        // 6. PROCESAMIENTO OPTIMIZADO CON BATCH PROCESSING
        $resultado = $this->procesarPlanillasBatch($datosFiltrados, $advertenciasIniciales);

        // ✅ 7. RESETEAR SERVICIO DE CÓDIGOS
        $this->codigoService->resetearContadorBatch();
        Log::channel('planilla_import')->info("🔄 Servicio de códigos reseteado");

        // ✅ 7. RESETEAR CONTADOR DE ETIQUETAS DESPUÉS DEL BATCH
        // Esto libera memoria y fuerza nueva consulta en próximas importaciones
        $this->codigoService->resetearContadorBatch();

        Log::channel('planilla_import')->info("🔄 [BATCH] Contador de etiquetas reseteado");

        Log::channel('planilla_import')->info("✅ Importación completada", [
            'total_en_archivo' => $datos->planillasDetectadas(),
            'duplicadas_omitidas' => count($duplicados),
            'exitosas' => count($resultado['exitosas']),
            'fallidas' => count($resultado['fallidas']),
        ]);

        return ImportResult::success(
            $resultado['exitosas'],
            $resultado['fallidas'],
            $resultado['advertencias'],
            $resultado['estadisticas'],
            $nombreArchivo
        );
    }

    /**
     * Reimporta elementos pendientes de una planilla existente.
     * 
     * - Elimina solo elementos en estado 'pendiente'
     * - Mantiene fecha de entrega y datos originales de la planilla
     * - Procesa solo las filas correspondientes al código de planilla
     *
     * @param UploadedFile $file
     * @param Planilla $planilla
     * @return ImportResult
     */
    public function reimportar(UploadedFile $file, Planilla $planilla): ImportResult
    {
        $nombreArchivo = $file->getClientOriginalName();

        Log::channel('planilla_import')->info("🔄 Iniciando reimportación", [
            'archivo' => $nombreArchivo,
            'planilla' => $planilla->codigo,
        ]);

        // 1. VALIDACIÓN PRE-PROCESAMIENTO
        $validacion = $this->validator->validar($file);

        if (!$validacion->esValido()) {
            Log::channel('planilla_import')->warning("❌ Validación fallida: {$nombreArchivo}", $validacion->errores());
            return ImportResult::error(
                $validacion->errores(),
                $validacion->advertencias(),
                $nombreArchivo
            );
        }

        // 2. LECTURA Y PREPARACIÓN DE DATOS
        $datos = $this->reader->leer($file);

        if ($datos->estaVacio()) {
            return ImportResult::error([
                "{$nombreArchivo} no contiene filas válidas tras filtrado."
            ], [], $nombreArchivo);
        }

        // 3. FILTRAR SOLO LAS FILAS DE LA PLANILLA A REIMPORTAR
        $datosFiltrados = $datos->filtrarPlanillas(
            array_diff($datos->codigosPlanillas(), [$planilla->codigo])
        );

        if ($datosFiltrados->estaVacio()) {
            return ImportResult::error([
                "El archivo no contiene datos para la planilla '{$planilla->codigo}'."
            ], [], $nombreArchivo);
        }

        Log::channel('planilla_import')->info("📊 Datos filtrados para reimportación", [
            'planilla' => $planilla->codigo,
            'filas_validas' => $datosFiltrados->filasValidas(),
        ]);

        try {
            // ✅ INICIALIZAR CONTADOR DE ETIQUETAS PARA REIMPORTACIÓN
            // Aunque es una sola planilla, necesitamos el contador para mantener
            // la correlatividad con otras etiquetas del mes
            // ✅ INICIALIZAR SERVICIO DE CÓDIGOS
            $this->codigoService->inicializarContadorBatch();

            Log::channel('planilla_import')->info("🔢 [REIMPORT] Contador de etiquetas inicializado");

            DB::beginTransaction();

            // 4. ELIMINAR SOLO ELEMENTOS PENDIENTES
            $elementosEliminados = $planilla->elementos()
                ->where('estado', 'pendiente')
                ->count();

            $planilla->elementos()
                ->where('estado', 'pendiente')
                ->delete();

            Log::channel('planilla_import')->info("🗑️ Elementos pendientes eliminados", [
                'cantidad' => $elementosEliminados,
            ]);

            // 5. ELIMINAR ETIQUETAS HUÉRFANAS (sin elementos)
            Etiqueta::where('planilla_id', $planilla->id)
                ->whereDoesntHave('elementos')
                ->delete();

            // 6. PRE-CARGAR DATOS EN CACHE
            $this->precargarCaches($datosFiltrados);

            // 7. PROCESAR NUEVOS ELEMENTOS (reutilizando lógica existente)
            $advertencias = [];
            $filasPlanilla = $datosFiltrados->agruparPorPlanilla()[$planilla->codigo] ?? [];

            if (empty($filasPlanilla)) {
                throw new \Exception("No se encontraron filas para procesar.");
            }

            // Procesar con el processor existente (pasando planilla existente)
            $resultado = $this->processor->procesar(
                $planilla->codigo,
                $filasPlanilla,
                $advertencias,
                $planilla  // ✅ Pasar planilla existente para reimportación
            );

            // 8. ASIGNAR MÁQUINAS
            $this->asignador->repartirPlanilla($planilla->id);

            // 9. ✅ APLICAR POLÍTICA DE SUBETIQUETAS (MÉTODO CORRECTO)
            $this->processor->aplicarPoliticaSubetiquetasPostAsignacion($planilla);

            // 10. CREAR/ACTUALIZAR ORDEN_PLANILLAS
            $ordenesCreadas = $this->ordenService->crearOrdenParaPlanilla($planilla->id);

            // 11. RECALCULAR TOTALES (pero mantener fecha de entrega)
            $elementos = $planilla->fresh()->elementos;
            $pesoTotal = $elementos->sum('peso');
            $tiempoTotal = $elementos->sum('tiempo_fabricacion') +
                ($elementos->count() * config('planillas.importacion.tiempo_setup_elemento', 1200));

            $planilla->update([
                'peso_total' => $pesoTotal,
                'tiempo_fabricacion' => $tiempoTotal,
                // NO actualizar fecha_estimada_entrega
            ]);


            // ✅ RESETEAR CONTADOR DESPUÉS DE REIMPORTACIÓN
            DB::commit();

            // ✅ RESETEAR SERVICIO
            $this->codigoService->resetearContadorBatch();

            Log::channel('planilla_import')->info("🔄 [REIMPORT] Contador de etiquetas reseteado");

            Log::channel('planilla_import')->info("✅ Reimportación completada", [
                'planilla' => $planilla->codigo,
                'elementos_eliminados' => $elementosEliminados,
                'elementos_creados' => $resultado->elementosCreados,
                'etiquetas_creadas' => $resultado->etiquetasCreadas,
            ]);

            return ImportResult::success(
                [$planilla->codigo],
                [],
                $advertencias,
                [
                    'elementos_eliminados' => $elementosEliminados,
                    'elementos_creados' => $resultado->elementosCreados,
                    'etiquetas_creadas' => $resultado->etiquetasCreadas,
                    'ordenes_creadas' => $ordenesCreadas,
                ],
                $nombreArchivo
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            // ✅ RESETEAR EN CASO DE ERROR
            $this->codigoService->resetearContadorBatch();

            Log::channel('planilla_import')->error("❌ Error en reimportación", [
                'planilla' => $planilla->codigo,
                'archivo' => $nombreArchivo,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return ImportResult::error([
                "Error al reimportar: {$e->getMessage()}"
            ], [], $nombreArchivo);
        }
    }

    /**
     * Verifica qué códigos de planilla ya existen en la base de datos.
     *
     * @param array $codigos
     * @return array Códigos que ya existen
     */
    protected function verificarDuplicados(array $codigos): array
    {
        if (empty($codigos)) {
            return [];
        }

        return Planilla::whereIn('codigo', $codigos)
            ->pluck('codigo')
            ->toArray();
    }

    /**
     * Pre-carga clientes, obras y máquinas para evitar queries repetitivas.
     *
     * @param DatosImportacion $datos
     * @return void
     */
    protected function precargarCaches(DatosImportacion $datos): void
    {
        $filas = $datos->filas();

        if (empty($filas)) {
            return;
        }

        // Extraer códigos únicos
        $codigosClientes = array_unique(array_filter(array_column($filas, 0)));
        $codigosObras = array_unique(array_filter(array_column($filas, 2)));

        // Pre-cargar clientes
        if (!empty($codigosClientes)) {
            $this->cacheClientes = \App\Models\Cliente::whereIn('codigo', $codigosClientes)
                ->get()
                ->keyBy('codigo')
                ->toArray();
        }

        // Pre-cargar obras
        if (!empty($codigosObras)) {
            $this->cacheObras = \App\Models\Obra::whereIn('cod_obra', $codigosObras)
                ->get()
                ->keyBy('cod_obra')
                ->toArray();
        }

        // Pre-cargar máquinas (se usan en AsignarMaquinaService)
        $this->cacheMaquinas = \App\Models\Maquina::naveA()
            ->get()
            ->keyBy('id')
            ->toArray();

        Log::channel('planilla_import')->info("🗄️ Caches precargados", [
            'clientes' => count($this->cacheClientes),
            'obras' => count($this->cacheObras),
            'maquinas' => count($this->cacheMaquinas),
        ]);
    }

    /**
     * Procesa planillas en lotes para mejor rendimiento.
     * 
     * OPTIMIZACIÓN: Procesa múltiples planillas en una sola transacción
     * cuando sea posible, reduciendo overhead de commits.
     *
     * @param DatosImportacion $datos
     * @param array $advertenciasIniciales Advertencias previas (ej: duplicados)
     * @return array
     */
    protected function procesarPlanillasBatch(DatosImportacion $datos, array $advertenciasIniciales = []): array
    {
        $exitosas = [];
        $fallidas = [];
        $advertencias = $advertenciasIniciales;
        $estadisticas = [
            'tiempo_total' => 0,
            'elementos_creados' => 0,
            'etiquetas_creadas' => 0,
            'ordenes_creadas' => 0,
        ];

        $porPlanilla = $datos->agruparPorPlanilla();
        $batchSize = config('planillas.importacion.batch_size', 5);

        // Dividir en lotes
        $batches = array_chunk($porPlanilla, $batchSize, true);

        Log::channel('planilla_import')->info("📦 [BATCH] Iniciando procesamiento", [
            'total_planillas' => count($porPlanilla),
            'num_batches' => count($batches),
            'batch_size' => $batchSize,
        ]);

        foreach ($batches as $batchIndex => $batch) {
            $inicioBatch = microtime(true);

            try {
                DB::beginTransaction();

                Log::channel('planilla_import')->info("📦 [BATCH {$batchIndex}] Procesando " . count($batch) . " planillas");

                foreach ($batch as $codigoPlanilla => $filasPlanilla) {
                    $inicioPlanilla = microtime(true);

                    try {
                        // 1️⃣ Procesar planilla (SIN aplicar política de subetiquetas)
                        $resultado = $this->processor->procesar(
                            $codigoPlanilla,
                            $filasPlanilla,
                            $advertencias,
                            null,
                            false  // ✅ NO aplicar política aquí
                        );

                        // 2️⃣ Asignar máquinas
                        $this->asignador->repartirPlanilla($resultado->planilla->id);

                        // 3️⃣ ✅ AHORA SÍ aplicar política de subetiquetas (MÉTODO CORRECTO)
                        $this->processor->aplicarPoliticaSubetiquetasPostAsignacion($resultado->planilla);

                        // 4️⃣ Crear orden_planillas
                        $ordenesCreadas = $this->ordenService->crearOrdenParaPlanilla($resultado->planilla->id);

                        $exitosas[] = $codigoPlanilla;

                        $estadisticas['elementos_creados'] += $resultado->elementosCreados;
                        $estadisticas['etiquetas_creadas'] += $resultado->etiquetasCreadas;
                        $estadisticas['ordenes_creadas'] += $ordenesCreadas;
                        $estadisticas['tiempo_total'] += (microtime(true) - $inicioPlanilla);

                        Log::channel('planilla_import')->debug("✅ Planilla {$codigoPlanilla}", [
                            'elementos' => $resultado->elementosCreados,
                            'etiquetas' => $resultado->etiquetasCreadas,
                            'tiempo' => round(microtime(true) - $inicioPlanilla, 2) . 's',
                        ]);
                    } catch (\Throwable $e) {
                        $fallidas[] = [
                            'codigo' => $codigoPlanilla,
                            'error' => $e->getMessage(),
                        ];

                        Log::channel('planilla_import')->error("❌ Error en planilla {$codigoPlanilla}: {$e->getMessage()}");
                    }
                }

                DB::commit();

                Log::channel('planilla_import')->info("✅ [BATCH {$batchIndex}] Completado", [
                    'planillas' => count($batch),
                    'exitosas' => count(array_filter($batch, fn($k) => in_array($k, $exitosas), ARRAY_FILTER_USE_KEY)),
                    'tiempo' => round(microtime(true) - $inicioBatch, 2) . 's',
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();

                foreach ($batch as $codigoPlanilla => $filasPlanilla) {
                    if (!in_array($codigoPlanilla, $exitosas)) {
                        $fallidas[] = [
                            'codigo' => $codigoPlanilla,
                            'error' => "Error en batch: {$e->getMessage()}",
                        ];
                    }
                }

                Log::channel('planilla_import')->error("❌ [BATCH {$batchIndex}] Error crítico", [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        $advertenciasUnicas = array_values(array_unique($advertencias));

        return [
            'exitosas' => $exitosas,
            'fallidas' => $fallidas,
            'advertencias' => $advertenciasUnicas,
            'estadisticas' => $estadisticas,
        ];
    }
}
