<?php

namespace App\Services\FerrawinSync;

use App\Models\Planilla;
use App\Models\FerrawinSyncLog;
use App\Services\PlanillaImport\PlanillaImportService;
use App\Services\PlanillaImport\DTOs\DatosImportacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * Servicio de sincronización automática FerraWin → Manager
 *
 * Este servicio:
 * - Conecta a la base de datos SQL Server de FerraWin
 * - Obtiene planillas nuevas o actualizadas
 * - Las importa a Manager usando el servicio existente
 * - Registra logs de cada sincronización
 */
class FerrawinSyncService
{
    protected FerrawinQueryBuilder $queryBuilder;
    protected PlanillaImportService $importService;

    /** @var array Estadísticas de la sincronización actual */
    protected array $stats = [
        'planillas_encontradas' => 0,
        'planillas_nuevas' => 0,
        'planillas_actualizadas' => 0,
        'planillas_sincronizadas' => 0,
        'planillas_fallidas' => 0,
        'elementos_creados' => 0,
        'errores' => [],
        'advertencias' => [],
    ];

    public function __construct(
        FerrawinQueryBuilder $queryBuilder,
        PlanillaImportService $importService
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->importService = $importService;
    }

    /**
     * Ejecuta la sincronización completa.
     *
     * @param int $diasAtras Días hacia atrás para buscar planillas (por defecto 7)
     * @param bool $soloNuevas Si es true, solo sincroniza planillas que no existen en Manager
     * @return FerrawinSyncResult
     */
    public function sincronizar(int $diasAtras = 7, bool $soloNuevas = false): FerrawinSyncResult
    {
        $inicio = microtime(true);
        $this->resetStats();

        Log::channel('ferrawin_sync')->info("🔄 Iniciando sincronización FerraWin", [
            'dias_atras' => $diasAtras,
            'solo_nuevas' => $soloNuevas,
        ]);

        try {
            // 1. Verificar conexión a FerraWin
            if (!$this->verificarConexion()) {
                throw new \Exception("No se pudo conectar a la base de datos FerraWin");
            }

            // 2. Obtener códigos de planillas desde FerraWin
            $codigosFerrawin = $this->queryBuilder->obtenerCodigosPlanillas($diasAtras);
            $this->stats['planillas_encontradas'] = count($codigosFerrawin);

            Log::channel('ferrawin_sync')->info("📋 Planillas encontradas en FerraWin: " . count($codigosFerrawin));

            if (empty($codigosFerrawin)) {
                return $this->crearResultado($inicio, 'sin_datos');
            }

            // 3. Filtrar planillas que ya existen en Manager
            $codigosExistentes = Planilla::whereIn('codigo', $codigosFerrawin)
                ->pluck('codigo')
                ->toArray();

            $codigosNuevos = array_diff($codigosFerrawin, $codigosExistentes);
            $this->stats['planillas_nuevas'] = count($codigosNuevos);

            Log::channel('ferrawin_sync')->info("📊 Análisis de planillas", [
                'total_ferrawin' => count($codigosFerrawin),
                'existentes_manager' => count($codigosExistentes),
                'nuevas' => count($codigosNuevos),
            ]);

            // 4. Si solo sincronizamos nuevas
            if ($soloNuevas) {
                $codigosASincronizar = $codigosNuevos;
            } else {
                // Sincronizar nuevas + verificar actualizaciones recientes
                $codigosActualizados = $this->detectarActualizaciones($codigosExistentes, $diasAtras);
                $this->stats['planillas_actualizadas'] = count($codigosActualizados);
                $codigosASincronizar = array_unique(array_merge($codigosNuevos, $codigosActualizados));
            }

            if (empty($codigosASincronizar)) {
                Log::channel('ferrawin_sync')->info("✅ No hay planillas nuevas para sincronizar");
                return $this->crearResultado($inicio, 'sin_cambios');
            }

            // 5. Sincronizar cada planilla
            foreach ($codigosASincronizar as $codigo) {
                $this->sincronizarPlanilla($codigo, in_array($codigo, $codigosExistentes));
            }

            // 6. Registrar log de sincronización
            $this->registrarLog($inicio);

            return $this->crearResultado($inicio, 'completado');

        } catch (\Throwable $e) {
            Log::channel('ferrawin_sync')->error("❌ Error en sincronización", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->stats['errores'][] = $e->getMessage();
            $this->registrarLog($inicio, 'error');

            return $this->crearResultado($inicio, 'error', $e->getMessage());
        }
    }

    /**
     * Sincroniza una planilla individual.
     */
    protected function sincronizarPlanilla(string $codigo, bool $esActualizacion = false): void
    {
        try {
            Log::channel('ferrawin_sync')->info("📥 Sincronizando planilla: {$codigo}" . ($esActualizacion ? " (actualización)" : " (nueva)"));

            // Obtener datos de FerraWin
            $datosFerrawin = $this->queryBuilder->obtenerDatosPlanilla($codigo);

            if (empty($datosFerrawin)) {
                $this->stats['advertencias'][] = "Planilla {$codigo}: sin datos en FerraWin";
                return;
            }

            // Convertir a formato compatible con el importador
            $datosFormateados = $this->formatearParaImportacion($datosFerrawin, $codigo);

            if ($esActualizacion) {
                // Si es actualización, eliminar la existente y reimportar
                $planillaExistente = Planilla::where('codigo', $codigo)->first();
                if ($planillaExistente) {
                    // Usar reimportación del servicio existente
                    $resultado = $this->reimportarPlanilla($planillaExistente, $datosFormateados);
                }
            } else {
                // Importar como nueva
                $resultado = $this->importarNueva($datosFormateados);
            }

            if ($resultado['exito']) {
                $this->stats['planillas_sincronizadas']++;
                $this->stats['elementos_creados'] += $resultado['elementos'] ?? 0;
                Log::channel('ferrawin_sync')->info("✅ Planilla {$codigo} sincronizada correctamente");
            } else {
                $this->stats['planillas_fallidas']++;
                $this->stats['errores'][] = "Planilla {$codigo}: " . ($resultado['error'] ?? 'Error desconocido');
            }

        } catch (\Throwable $e) {
            $this->stats['planillas_fallidas']++;
            $this->stats['errores'][] = "Planilla {$codigo}: {$e->getMessage()}";
            Log::channel('ferrawin_sync')->error("❌ Error sincronizando planilla {$codigo}: {$e->getMessage()}");
        }
    }

    /**
     * Formatea los datos de FerraWin al formato que espera el importador de Manager.
     *
     * El importador espera un array con índices específicos (ver PlanillaProcessor).
     */
    protected function formatearParaImportacion(array $datosFerrawin, string $codigo): array
    {
        $filas = [];

        foreach ($datosFerrawin as $row) {
            // Mapear campos de FerraWin a los índices que espera Manager
            $fila = array_fill(0, 48, null);

            // Datos del cliente/obra
            $fila[0] = $row['ZCODCLI'] ?? '';              // Código Cliente
            $fila[1] = $row['ZCLIENTE'] ?? '';             // Nombre Cliente
            $fila[2] = $row['ZCODIGO_OBRA'] ?? '';         // Código Obra
            $fila[3] = $row['ZNOMBRE_OBRA'] ?? '';         // Nombre Obra
            $fila[4] = $row['ZMODULO'] ?? '';              // Ensamblado

            // Sección
            $fila[7] = $row['ZSECCION'] ?? $row['ZMODULO'] ?? '';

            // Código de planilla (CRÍTICO)
            $fila[10] = $codigo;

            // Descripción
            $fila[12] = $row['ZNOMBRE_PLANILLA'] ?? '';

            // Datos del elemento
            $fila[21] = $row['ZCODLIN'] ?? '';             // Fila
            $fila[22] = $row['ZDESCRIPCION_FILA'] ?? '';   // Descripción (nombre etiqueta)
            $fila[23] = $row['ZMARCA'] ?? '';              // Marca
            $fila[25] = $row['ZDIAMETRO'] ?? 0;            // Diámetro
            $fila[26] = $row['ZCODMODELO'] ?? '';          // Figura
            $fila[27] = $row['ZLONGTESTD'] ?? 0;           // Longitud
            $fila[30] = $row['ZETIQUETA'] ?? '';           // Número de etiqueta
            $fila[32] = $row['ZCANTIDAD'] ?? 0;            // Barras
            $fila[33] = $row['ZNUMBEND'] ?? 0;             // Dobles por barra
            $fila[34] = $row['ZPESOTESTD'] ?? 0;           // Peso

            // Dimensiones (CRÍTICO - columna 47)
            $fila[47] = $this->construirDimensiones($row);

            $filas[] = $fila;
        }

        return $filas;
    }

    /**
     * Construye el campo dimensiones a partir de los datos de FerraWin.
     */
    protected function construirDimensiones(array $row): string
    {
        $numDobleces = (int)($row['ZNUMBEND'] ?? 0);

        if ($numDobleces === 0) {
            // Barra recta: solo la longitud
            return (string)($row['ZLONGTESTD'] ?? '');
        }

        // Barra con dobleces: usar ZFIGURA si está disponible
        $zfigura = $row['ZFIGURA'] ?? '';

        if (!empty($zfigura) && strpos($zfigura, "\t") !== false) {
            return trim($zfigura);
        }

        // Fallback: parsear desde ZOBJETO si está disponible
        // (lógica simplificada - el script PowerShell original tiene la lógica completa)
        return $zfigura ?: (string)($row['ZLONGTESTD'] ?? '');
    }

    /**
     * Importa una planilla nueva usando el servicio de importación existente.
     */
    protected function importarNueva(array $datos): array
    {
        try {
            // Crear DatosImportacion manualmente
            $datosImportacion = new DatosImportacion($datos, [
                'total_filas' => count($datos),
                'filas_validas' => count($datos),
            ]);

            // Usar el procesador directamente
            $processor = app(\App\Services\PlanillaImport\PlanillaProcessor::class);
            $asignador = app(\App\Services\AsignarMaquinaService::class);
            $ordenService = app(\App\Services\OrdenPlanillaService::class);

            $advertencias = [];
            $porPlanilla = $datosImportacion->agruparPorPlanilla();

            $elementosCreados = 0;

            DB::beginTransaction();

            foreach ($porPlanilla as $codigoPlanilla => $filasPlanilla) {
                $resultado = $processor->procesar(
                    $codigoPlanilla,
                    $filasPlanilla,
                    $advertencias,
                    null,
                    false
                );

                // Asignar máquinas
                $asignador->repartirPlanilla($resultado->planilla->id);

                // Aplicar política de subetiquetas
                $processor->aplicarPoliticaSubetiquetasPostAsignacion($resultado->planilla);

                // Crear orden_planillas
                $ordenService->crearOrdenParaPlanilla($resultado->planilla->id);

                $elementosCreados += $resultado->elementosCreados;
            }

            DB::commit();

            return [
                'exito' => true,
                'elementos' => $elementosCreados,
                'advertencias' => $advertencias,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'exito' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reimporta una planilla existente (actualización).
     */
    protected function reimportarPlanilla(Planilla $planilla, array $datos): array
    {
        try {
            DB::beginTransaction();

            // Eliminar elementos pendientes
            $planilla->elementos()->where('estado', 'pendiente')->forceDelete();

            // Eliminar etiquetas huérfanas
            \App\Models\Etiqueta::where('planilla_id', $planilla->id)
                ->whereDoesntHave('elementos')
                ->delete();

            // Reimportar
            $processor = app(\App\Services\PlanillaImport\PlanillaProcessor::class);
            $asignador = app(\App\Services\AsignarMaquinaService::class);

            $advertencias = [];
            $resultado = $processor->procesar(
                $planilla->codigo,
                $datos,
                $advertencias,
                $planilla,
                false
            );

            $asignador->repartirPlanilla($planilla->id);
            $processor->aplicarPoliticaSubetiquetasPostAsignacion($planilla);

            DB::commit();

            return [
                'exito' => true,
                'elementos' => $resultado->elementosCreados,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'exito' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Detecta planillas que han sido actualizadas en FerraWin.
     */
    protected function detectarActualizaciones(array $codigosExistentes, int $diasAtras): array
    {
        if (empty($codigosExistentes)) {
            return [];
        }

        // Obtener fechas de modificación de FerraWin
        $actualizados = [];

        foreach ($codigosExistentes as $codigo) {
            $fechaFerrawin = $this->queryBuilder->obtenerFechaModificacion($codigo);

            if (!$fechaFerrawin) {
                continue;
            }

            $planillaManager = Planilla::where('codigo', $codigo)->first();

            if ($planillaManager && $fechaFerrawin > $planillaManager->updated_at) {
                $actualizados[] = $codigo;
            }
        }

        return $actualizados;
    }

    /**
     * Verifica la conexión a FerraWin.
     */
    protected function verificarConexion(): bool
    {
        try {
            DB::connection('ferrawin')->getPdo();
            return true;
        } catch (\Throwable $e) {
            Log::channel('ferrawin_sync')->error("❌ Error de conexión a FerraWin: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Registra el log de sincronización.
     */
    protected function registrarLog(float $inicio, string $estado = 'completado'): void
    {
        $duracion = round(microtime(true) - $inicio, 2);

        FerrawinSyncLog::create([
            'fecha_ejecucion' => now(),
            'estado' => $estado,
            'planillas_encontradas' => $this->stats['planillas_encontradas'],
            'planillas_nuevas' => $this->stats['planillas_nuevas'],
            'planillas_actualizadas' => $this->stats['planillas_actualizadas'],
            'planillas_sincronizadas' => $this->stats['planillas_sincronizadas'],
            'planillas_fallidas' => $this->stats['planillas_fallidas'],
            'elementos_creados' => $this->stats['elementos_creados'],
            'errores' => json_encode($this->stats['errores']),
            'advertencias' => json_encode($this->stats['advertencias']),
            'duracion_segundos' => $duracion,
        ]);
    }

    /**
     * Crea el resultado de la sincronización.
     */
    protected function crearResultado(float $inicio, string $estado, ?string $error = null): FerrawinSyncResult
    {
        return new FerrawinSyncResult(
            estado: $estado,
            stats: $this->stats,
            duracion: round(microtime(true) - $inicio, 2),
            error: $error
        );
    }

    /**
     * Resetea las estadísticas.
     */
    protected function resetStats(): void
    {
        $this->stats = [
            'planillas_encontradas' => 0,
            'planillas_nuevas' => 0,
            'planillas_actualizadas' => 0,
            'planillas_sincronizadas' => 0,
            'planillas_fallidas' => 0,
            'elementos_creados' => 0,
            'errores' => [],
            'advertencias' => [],
        ];
    }
}
