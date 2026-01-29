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
use Illuminate\Support\Carbon;
use App\Services\ImportProgress;
use App\Helpers\FechaEntregaHelper;

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
    public function importar(UploadedFile $file, ?Carbon $fechaAprobacion = null, ?string $importId = null): ImportResult
    {
        $nombreArchivo = $file->getClientOriginalName();
        Log::channel('planilla_import')->info("📥 Iniciando importación: {$nombreArchivo}");

        $validacion = $this->validator->validar($file);
        if (!$validacion->esValido()) {
            if ($importId) ImportProgress::setError($importId, 'Validación fallida.');
            return ImportResult::error($validacion->errores(), $validacion->advertencias(), $nombreArchivo);
        }

        $datos = $this->reader->leer($file);
        if ($datos->estaVacio()) {
            if ($importId) ImportProgress::setError($importId, 'El archivo no contiene filas válidas.');
            return ImportResult::error(["{$nombreArchivo} no contiene filas válidas tras filtrado."], [], $nombreArchivo);
        }

        // Total de filas válidas (x = número de consultas/filas)
        $totalFilas = (int) $datos->filasValidas();
        if ($importId) {
            ImportProgress::init($importId, $totalFilas, "Filas totales: {$totalFilas}");
        }

        // Duplicados
        $duplicados = $this->verificarDuplicados($datos->codigosPlanillas());
        $advertenciasIniciales = [];
        $datosFiltrados = $datos;

        if (!empty($duplicados)) {
            $advertenciasIniciales[] = "Omitidas (ya existen): " . implode(', ', $duplicados);
            $datosFiltrados = $datos->filtrarPlanillas($duplicados);

            // Recalcular total real a procesar tras filtrar
            if ($importId) {
                $totalFilas = max(1, (int) $datosFiltrados->filasValidas());
                ImportProgress::init($importId, $totalFilas, "Filas a procesar: {$totalFilas}");
            }

            if ($datosFiltrados->estaVacio()) {
                if ($importId) ImportProgress::setError($importId, "Todo ya existía.");
                return ImportResult::error(["Todas las planillas del archivo ya existen."], $advertenciasIniciales, $nombreArchivo);
            }
        }

        $this->precargarCaches($datosFiltrados);

        // 🔢 contador de códigos/etiquetas, etc.
        $this->codigoService->inicializarContadorBatch();

        // => procesamos en lotes, avanzando progreso por fila
        $resultado = $this->procesarPlanillasBatchConProgreso($datosFiltrados, $advertenciasIniciales, $fechaAprobacion, $importId);

        $this->codigoService->resetearContadorBatch();

        $importResult = ImportResult::success(
            $resultado['exitosas'],
            $resultado['fallidas'],
            $resultado['advertencias'],
            $resultado['estadisticas'],
            $nombreArchivo
        );

        // Guardar resultado completo en el progreso para que el frontend lo muestre
        if ($importId) {
            ImportProgress::setDone($importId, 'Importación finalizada.', [
                'resultado' => [
                    'planillas_creadas' => count($resultado['exitosas']),
                    'elementos_creados' => $resultado['estadisticas']['elementos'] ?? 0,
                ],
                'advertencias' => $resultado['advertencias'],
                'mensaje_completo' => $importResult->mensaje(),
                'tiene_advertencias' => $importResult->tieneAdvertencias(),
            ]);
        }

        return $importResult;
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

            // 4. ELIMINAR SOLO ELEMENTOS NO ELABORADOS (forceDelete para evitar conflictos de código único)
            $elementosEliminados = $planilla->elementos()
                ->where(function($q) {
                    $q->where('elaborado', '!=', 1)->orWhereNull('elaborado');
                })
                ->count();

            $planilla->elementos()
                ->where(function($q) {
                    $q->where('elaborado', '!=', 1)->orWhereNull('elaborado');
                })
                ->forceDelete();

            Log::channel('planilla_import')->info("🗑️ Elementos pendientes eliminados", [
                'cantidad' => $elementosEliminados,
            ]);

            // 5. ELIMINAR ETIQUETAS HUÉRFANAS (verificando por etiqueta_id, no por relación elementos)
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
     * También elimina definitivamente las planillas soft-deleted para evitar
     * errores de "Duplicate entry" en la base de datos.
     *
     * @param array $codigos
     * @return array Códigos que ya existen (activos, no eliminados)
     */
    protected function verificarDuplicados(array $codigos): array
    {
        if (empty($codigos)) {
            return [];
        }

        // Primero, eliminar definitivamente las planillas soft-deleted que coincidan
        // para evitar errores de "Duplicate entry" al crear nuevas
        $softDeleted = Planilla::onlyTrashed()
            ->whereIn('codigo', $codigos)
            ->get();

        if ($softDeleted->isNotEmpty()) {
            $codigosSoftDeleted = $softDeleted->pluck('codigo')->toArray();

            Log::channel('planilla_import')->info("🗑️ Eliminando definitivamente planillas soft-deleted para reimportación", [
                'codigos' => $codigosSoftDeleted,
            ]);

            // Eliminar elementos asociados primero (force delete)
            foreach ($softDeleted as $planilla) {
                $planilla->elementos()->withTrashed()->forceDelete();
                // Eliminar etiquetas asociadas
                Etiqueta::where('planilla_id', $planilla->id)->delete();
            }

            // Eliminar definitivamente las planillas
            Planilla::onlyTrashed()
                ->whereIn('codigo', $codigos)
                ->forceDelete();
        }

        // Ahora verificar cuáles existen realmente (activas)
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
     * @param DatosImportacion $datos
     * @param array $advertenciasIniciales
     * @param \Illuminate\Support\Carbon|null $fechaAprobacion  // si viene, se usa para fijar fecha_estimada_entrega = +7 días
     * @return array
     */
    protected function procesarPlanillasBatch(
        DatosImportacion $datos,
        array $advertenciasIniciales = [],
        ?Carbon $fechaAprobacion = null
    ): array {
        $exitosas     = [];
        $fallidas     = [];
        $advertencias = $advertenciasIniciales;
        $estadisticas = [
            'tiempo_total'       => 0,
            'elementos_creados'  => 0,
            'etiquetas_creadas'  => 0,
            'ordenes_creadas'    => 0,
        ];

        $porPlanilla = $datos->agruparPorPlanilla();
        $batchSize   = config('planillas.importacion.batch_size', 5);

        // Dividir en lotes manteniendo claves (códigos de planilla)
        $batches = array_chunk($porPlanilla, $batchSize, true);

        Log::channel('planilla_import')->info("📦 [BATCH] Iniciando procesamiento", [
            'total_planillas' => count($porPlanilla),
            'num_batches'     => count($batches),
            'batch_size'      => $batchSize,
        ]);

        foreach ($batches as $batchIndex => $batch) {
            $inicioBatch = microtime(true);

            try {
                DB::beginTransaction();

                Log::channel('planilla_import')->info("📦 [BATCH {$batchIndex}] Procesando " . count($batch) . " planillas");

                foreach ($batch as $codigoPlanilla => $filasPlanilla) {
                    $inicioPlanilla = microtime(true);

                    try {
                        // 1) Procesar planilla (crea planilla + etiquetas + elementos)
                        //    NOTA: false => no aplicar política de subetiquetas aún.
                        $resultado = $this->processor->procesar(
                            $codigoPlanilla,
                            $filasPlanilla,
                            $advertencias,
                            null,
                            false
                        );

                        // 1.1) Si viene fecha de aprobación, fijamos fecha_estimada_entrega = aprobación + 7 días (ajustada)
                        if ($fechaAprobacion) {
                            $resultado->planilla->fecha_estimada_entrega = FechaEntregaHelper::calcular($fechaAprobacion, 7);
                            $resultado->planilla->save();
                        }


                        // 2) Asignar máquinas
                        $this->asignador->repartirPlanilla($resultado->planilla->id);

                        // 3) Aplicar política de subetiquetas post-asignación
                        $this->processor->aplicarPoliticaSubetiquetasPostAsignacion($resultado->planilla);

                        // 4) Crear orden_planillas
                        $ordenesCreadas = $this->ordenService->crearOrdenParaPlanilla($resultado->planilla->id);

                        // 5) Métricas
                        $exitosas[] = $codigoPlanilla;
                        $estadisticas['elementos_creados'] += $resultado->elementosCreados;
                        $estadisticas['etiquetas_creadas'] += $resultado->etiquetasCreadas;
                        $estadisticas['ordenes_creadas']   += $ordenesCreadas;
                        $estadisticas['tiempo_total']      += (microtime(true) - $inicioPlanilla);

                        Log::channel('planilla_import')->debug("✅ Planilla {$codigoPlanilla}", [
                            'elementos' => $resultado->elementosCreados,
                            'etiquetas' => $resultado->etiquetasCreadas,
                            'tiempo'    => round(microtime(true) - $inicioPlanilla, 2) . 's',
                        ]);
                    } catch (\Throwable $e) {
                        $fallidas[] = [
                            'codigo' => $codigoPlanilla,
                            'error'  => $e->getMessage(),
                        ];

                        Log::channel('planilla_import')->error("❌ Error en planilla {$codigoPlanilla}: {$e->getMessage()}", [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ]);
                    }
                }

                DB::commit();

                Log::channel('planilla_import')->info("✅ [BATCH {$batchIndex}] Completado", [
                    'planillas' => count($batch),
                    'exitosas'  => count(array_filter(array_keys($batch), fn($k) => in_array($k, $exitosas, true))),
                    'tiempo'    => round(microtime(true) - $inicioBatch, 2) . 's',
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();

                // Marcar como fallidas las que no entraron en exitosas
                foreach ($batch as $codigoPlanilla => $_) {
                    if (!in_array($codigoPlanilla, $exitosas, true)) {
                        $fallidas[] = [
                            'codigo' => $codigoPlanilla,
                            'error'  => "Error en batch: {$e->getMessage()}",
                        ];
                    }
                }

                Log::channel('planilla_import')->error("❌ [BATCH {$batchIndex}] Error crítico", [
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                ]);
            }
        }

        $advertenciasUnicas = array_values(array_unique($advertencias));

        return [
            'exitosas'     => $exitosas,
            'fallidas'     => $fallidas,
            'advertencias' => $advertenciasUnicas,
            'estadisticas' => $estadisticas,
        ];
    }

    protected function procesarPlanillasBatchConProgreso(
        DatosImportacion $datos,
        array $advertenciasIniciales = [],
        ?Carbon $fechaAprobacion = null,
        ?string $importId = null
    ): array {
        $exitosas     = [];
        $fallidas     = [];
        $advertencias = $advertenciasIniciales;
        $estadisticas = [
            'tiempo_total'       => 0,
            'elementos_creados'  => 0,
            'etiquetas_creadas'  => 0,
            'ordenes_creadas'    => 0,
        ];

        $porPlanilla = $datos->agruparPorPlanilla();
        $batchSize   = config('planillas.importacion.batch_size', 5);
        $batches     = array_chunk($porPlanilla, $batchSize, true);

        foreach ($batches as $batchIndex => $batch) {
            $inicioBatch = microtime(true);
            try {
                DB::beginTransaction();

                foreach ($batch as $codigoPlanilla => $filasPlanilla) {
                    $inicioPlanilla = microtime(true);
                    $filasDeEstaPlanilla = is_array($filasPlanilla) ? count($filasPlanilla) : 0;

                    try {
                        $resultado = $this->processor->procesar(
                            $codigoPlanilla,
                            $filasPlanilla,
                            $advertencias,
                            null,
                            false
                        );

                        if ($fechaAprobacion) {
                            $resultado->planilla->fecha_estimada_entrega = FechaEntregaHelper::calcular($fechaAprobacion, 7);
                            $resultado->planilla->save();
                        }

                        $this->asignador->repartirPlanilla($resultado->planilla->id);
                        $this->processor->aplicarPoliticaSubetiquetasPostAsignacion($resultado->planilla);
                        $ordenesCreadas = $this->ordenService->crearOrdenParaPlanilla($resultado->planilla->id);

                        $exitosas[] = $codigoPlanilla;
                        $estadisticas['elementos_creados'] += $resultado->elementosCreados;
                        $estadisticas['etiquetas_creadas'] += $resultado->etiquetasCreadas;
                        $estadisticas['ordenes_creadas']   += $ordenesCreadas;
                        $estadisticas['tiempo_total']      += (microtime(true) - $inicioPlanilla);

                        // progreso por filas (avanza en bloque por planilla)
                        if ($importId && $filasDeEstaPlanilla > 0) {
                            ImportProgress::advance($importId, $filasDeEstaPlanilla, "Procesada {$codigoPlanilla}");
                        }
                    } catch (\Throwable $e) {
                        $fallidas[] = ['codigo' => $codigoPlanilla, 'error' => $e->getMessage()];
                        Log::channel('planilla_import')->error("❌ Error en planilla {$codigoPlanilla}: {$e->getMessage()}", [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ]);

                        // Incluso si falla, avanza para no estancar la barra (contamos sus filas)
                        if ($importId && $filasDeEstaPlanilla > 0) {
                            ImportProgress::advance($importId, $filasDeEstaPlanilla, "Error en {$codigoPlanilla}");
                        }
                    }
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                foreach ($batch as $codigoPlanilla => $_) {
                    if (!in_array($codigoPlanilla, $exitosas, true)) {
                        $fallidas[] = ['codigo' => $codigoPlanilla, 'error' => "Error en batch: {$e->getMessage()}"];
                    }
                }
                if ($importId) ImportProgress::advance($importId, 0, 'Error en lote, continuando...');
            }
        }

        $advertenciasUnicas = array_values(array_unique($advertencias));

        return [
            'exitosas'     => $exitosas,
            'fallidas'     => $fallidas,
            'advertencias' => $advertenciasUnicas,
            'estadisticas' => $estadisticas,
        ];
    }
}
