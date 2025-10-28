<?php

namespace App\Services\PlanillaImport;

use App\Models\Planilla;
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

/**
 * Servicio principal para importación de planillas - VERSIÓN OPTIMIZADA
 * 
 * Mejoras de rendimiento:
 * - Batch processing de múltiples planillas
 * - Bulk inserts para elementos y etiquetas
 * - Reducción de queries N+1
 * - Cache de máquinas y validaciones
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
        protected OrdenPlanillaService $ordenService
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

        Log::info("📥 Iniciando importación de archivo: {$nombreArchivo}");

        // 1. VALIDACIÓN PRE-PROCESAMIENTO
        $validacion = $this->validator->validar($file);

        if (!$validacion->esValido()) {
            Log::warning("❌ Validación fallida: {$nombreArchivo}", $validacion->errores());
            // ✅ Añadir nombre de archivo al error
            return ImportResult::error(
                $validacion->errores(),
                $validacion->advertencias(),
                $nombreArchivo
            );
        }

        // 2. LECTURA Y PREPARACIÓN DE DATOS
        $datos = $this->reader->leer($file);

        if ($datos->estaVacio()) {
            // ✅ Añadir nombre de archivo al error
            return ImportResult::error([
                "{$nombreArchivo} no contiene filas válidas tras filtrado."
            ], [], $nombreArchivo);
        }

        Log::info("📊 Datos leídos", [
            'total_filas' => $datos->totalFilas(),
            'filas_validas' => $datos->filasValidas(),
            'planillas_detectadas' => $datos->planillasDetectadas(),
        ]);

        // 3. VERIFICAR DUPLICADOS (una sola query)
        $duplicados = $this->verificarDuplicados($datos->codigosPlanillas());

        if (!empty($duplicados)) {
            // ✅ Añadir nombre de archivo al error
            return ImportResult::error([
                "Las siguientes planillas ya existen: " . implode(', ', $duplicados)
            ], [
                "Use la función de 'Reimportar' si desea actualizar planillas existentes."
            ], $nombreArchivo);
        }

        // 4. PRE-CARGAR DATOS EN CACHE
        $this->precargarCaches($datos);

        // 5. PROCESAMIENTO OPTIMIZADO CON BATCH PROCESSING
        $resultado = $this->procesarPlanillasBatch($datos);

        Log::info("✅ Importación completada", [
            'exitosas' => count($resultado['exitosas']),
            'fallidas' => count($resultado['fallidas']),
        ]);

        // ✅ Añadir nombre de archivo al resultado exitoso
        return ImportResult::success(
            $resultado['exitosas'],
            $resultado['fallidas'],
            $resultado['advertencias'],
            $resultado['estadisticas'],
            $nombreArchivo // ← NUEVO PARÁMETRO
        );
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

        Log::info("🗄️ Caches precargados", [
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
        $advertencias = $advertenciasIniciales; // Incluir advertencias iniciales
        $estadisticas = [
            'tiempo_total' => 0,
            'elementos_creados' => 0,
            'etiquetas_creadas' => 0,
            'ordenes_creadas' => 0,
        ];

        $porPlanilla = $datos->agruparPorPlanilla();
        $batchSize = config('planillas.importacion.batch_size', 5); // 5 planillas por transacción

        // Dividir en lotes
        $batches = array_chunk($porPlanilla, $batchSize, true);

        foreach ($batches as $batchIndex => $batch) {
            $inicioBatch = microtime(true);

            try {
                DB::beginTransaction();

                foreach ($batch as $codigoPlanilla => $filasPlanilla) {
                    $inicioPlanilla = microtime(true);

                    try {
                        // 1️⃣ Procesar planilla
                        $resultado = $this->processor->procesar(
                            $codigoPlanilla,
                            $filasPlanilla,
                            $advertencias
                        );

                        // 2️⃣ Asignar máquinas
                        $this->asignador->repartirPlanilla($resultado->planilla->id);

                        // 3️⃣ Crear orden_planillas
                        $ordenesCreadas = $this->ordenService->crearOrdenParaPlanilla($resultado->planilla->id);

                        $exitosas[] = $codigoPlanilla;

                        $estadisticas['elementos_creados'] += $resultado->elementosCreados;
                        $estadisticas['etiquetas_creadas'] += $resultado->etiquetasCreadas;
                        $estadisticas['ordenes_creadas'] += $ordenesCreadas;
                        $estadisticas['tiempo_total'] += (microtime(true) - $inicioPlanilla);

                        Log::debug("✅ Planilla {$codigoPlanilla}", [
                            'elementos' => $resultado->elementosCreados,
                            'tiempo' => round(microtime(true) - $inicioPlanilla, 2) . 's',
                        ]);
                    } catch (\Throwable $e) {
                        // Si una planilla falla, registrar pero continuar con las demás del batch
                        $fallidas[] = [
                            'codigo' => $codigoPlanilla,
                            'error' => $e->getMessage(),
                        ];

                        Log::error("❌ Error en planilla {$codigoPlanilla}: {$e->getMessage()}");
                    }
                }

                DB::commit();

                Log::info("📦 Batch {$batchIndex} completado", [
                    'planillas' => count($batch),
                    'tiempo' => round(microtime(true) - $inicioBatch, 2) . 's',
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();

                // Si el batch completo falla, marcar todas como fallidas
                foreach ($batch as $codigoPlanilla => $filasPlanilla) {
                    if (!in_array($codigoPlanilla, $exitosas)) {
                        $fallidas[] = [
                            'codigo' => $codigoPlanilla,
                            'error' => "Error en batch: {$e->getMessage()}",
                        ];
                    }
                }

                Log::error("❌ Error en batch {$batchIndex}", [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        // Consolidar advertencias
        $advertenciasUnicas = array_values(array_unique($advertencias));

        return [
            'exitosas' => $exitosas,
            'fallidas' => $fallidas,
            'advertencias' => $advertenciasUnicas,
            'estadisticas' => $estadisticas,
        ];
    }
}
