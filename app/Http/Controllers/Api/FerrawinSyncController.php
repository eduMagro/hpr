<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FerrawinSync\FerrawinBulkImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API Controller para recibir sincronizaciones de FerraWin.
 *
 * Este endpoint es llamado desde el PC local que tiene acceso a FerraWin.
 */
class FerrawinSyncController extends Controller
{
    public function __construct(
        protected FerrawinBulkImportService $bulkImportService
    ) {}

    /**
     * Recibe datos de sincronización desde el PC local.
     *
     * POST /api/ferrawin/sync
     * Headers:
     *   - Authorization: Bearer {FERRAWIN_API_TOKEN}
     *   - Content-Encoding: gzip (opcional)
     * Body: JSON con planillas y elementos
     */
    public function sync(Request $request)
    {
        $inicio = microtime(true);

        Log::channel('ferrawin_sync')->info('📥 [API] Recibiendo sincronización', [
            'ip' => $request->ip(),
            'content_length' => $request->header('Content-Length'),
            'compressed' => $request->header('Content-Encoding') === 'gzip',
        ]);

        try {
            // Validar estructura básica
            $request->validate([
                'planillas' => 'required|array|min:1',
                'planillas.*.codigo' => 'required|string',
                'planillas.*.elementos' => 'nullable|array',
                'metadata' => 'nullable|array',
            ]);

            $planillas = $request->input('planillas');
            $metadata = $request->input('metadata', []);

            // Contar elementos totales
            $totalElementos = collect($planillas)->sum(fn($p) => count($p['elementos'] ?? []));

            Log::channel('ferrawin_sync')->info("📊 [API] Datos recibidos", [
                'planillas' => count($planillas),
                'elementos_total' => $totalElementos,
            ]);

            // Procesar con bulk import
            $resultado = $this->bulkImportService->importar($planillas, $metadata);

            $duracion = round(microtime(true) - $inicio, 2);

            Log::channel('ferrawin_sync')->info("✅ [API] Sincronización completada", [
                'duracion' => $duracion,
                'planillas_creadas' => $resultado['planillas_creadas'],
                'elementos_creados' => $resultado['elementos_creados'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sincronización completada',
                'data' => [
                    'planillas_recibidas' => count($planillas),
                    'planillas_creadas' => $resultado['planillas_creadas'],
                    'planillas_actualizadas' => $resultado['planillas_actualizadas'],
                    'planillas_omitidas' => $resultado['planillas_omitidas'],
                    'elementos_creados' => $resultado['elementos_creados'],
                    'duracion_segundos' => $duracion,
                ],
                'advertencias' => $resultado['advertencias'] ?? [],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('ferrawin_sync')->warning('⚠️ [API] Validación fallida', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            Log::channel('ferrawin_sync')->error('❌ [API] Error en sincronización', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno',
                'error' => config('app.debug') ? $e->getMessage() : 'Error procesando sincronización',
            ], 500);
        }
    }

    /**
     * Verifica el estado del endpoint y la conexión.
     *
     * GET /api/ferrawin/status
     */
    public function status(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => '1.0',
        ]);
    }

    /**
     * Backfill descripcion_fila para elementos existentes.
     *
     * POST /api/ferrawin/backfill-descripcion-fila
     */
    public function backfillDescripcionFila(Request $request)
    {
        try {
            $request->validate([
                'codigo_planilla' => 'required|string',
                'elementos' => 'required|array',
                'elementos.*.fila' => 'required|string',
                'elementos.*.descripcion_fila' => 'nullable|string',
            ]);

            $codigoPlanilla = $request->input('codigo_planilla');
            $elementosData = $request->input('elementos');

            // Buscar la planilla
            $planilla = \App\Models\Planilla::where('codigo', $codigoPlanilla)->first();

            if (!$planilla) {
                return response()->json([
                    'success' => false,
                    'message' => "Planilla {$codigoPlanilla} no encontrada",
                    'actualizados' => 0,
                ]);
            }

            // Crear mapa fila -> descripcion_fila
            $mapaDescripciones = [];
            foreach ($elementosData as $elem) {
                $fila = trim($elem['fila']);
                $mapaDescripciones[$fila] = $elem['descripcion_fila'] ?? null;
            }

            // Actualizar elementos de la planilla
            $actualizados = 0;
            $elementos = \App\Models\Elemento::where('planilla_id', $planilla->id)->get();

            foreach ($elementos as $elemento) {
                $fila = trim($elemento->fila ?? '');

                if (isset($mapaDescripciones[$fila]) && $mapaDescripciones[$fila]) {
                    $elemento->descripcion_fila = $mapaDescripciones[$fila];
                    $elemento->save();
                    $actualizados++;
                }
            }

            Log::channel('ferrawin_sync')->info("📝 [API] Backfill descripcion_fila", [
                'planilla' => $codigoPlanilla,
                'elementos_recibidos' => count($elementosData),
                'actualizados' => $actualizados,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Backfill completado',
                'actualizados' => $actualizados,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            Log::channel('ferrawin_sync')->error('❌ [API] Error en backfill', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno',
            ], 500);
        }
    }

    /**
     * Devuelve los códigos de planillas existentes en la base de datos.
     * Se usa para sincronización incremental (solo planillas nuevas).
     *
     * GET /api/ferrawin/codigos-existentes
     */
    public function codigosExistentes(Request $request)
    {
        try {
            $codigos = \App\Models\Planilla::pluck('codigo')->toArray();

            return response()->json([
                'success' => true,
                'total' => count($codigos),
                'codigos' => $codigos,
            ]);

        } catch (\Throwable $e) {
            Log::channel('ferrawin_sync')->error('❌ [API] Error obteniendo códigos existentes', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno',
            ], 500);
        }
    }

    /**
     * Devuelve elementos de una planilla para hacer matching con FerraWin.
     * Incluye los campos necesarios para identificar cada elemento.
     *
     * GET /api/ferrawin/elementos-para-matching/{codigo}
     */
    public function elementosParaMatching(Request $request, string $codigo)
    {
        try {
            $planilla = \App\Models\Planilla::where('codigo', $codigo)->first();

            if (!$planilla) {
                return response()->json([
                    'success' => false,
                    'message' => "Planilla {$codigo} no encontrada",
                ], 404);
            }

            $elementos = \App\Models\Elemento::where('planilla_id', $planilla->id)
                ->select('id', 'fila', 'ferrawin_id', 'diametro', 'longitud', 'barras', 'dobles_barra', 'peso', 'marca', 'figura', 'estado')
                ->orderBy('fila')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'planilla_id' => $planilla->id,
                'codigo' => $codigo,
                'total' => $elementos->count(),
                'elementos' => $elementos,
            ]);

        } catch (\Throwable $e) {
            Log::channel('ferrawin_sync')->error('❌ [API] Error obteniendo elementos para matching', [
                'codigo' => $codigo,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno',
            ], 500);
        }
    }

    /**
     * Actualiza ferrawin_id de elementos existentes.
     * Recibe un array de {elemento_id, ferrawin_id} para actualizar.
     *
     * POST /api/ferrawin/actualizar-ferrawin-ids
     */
    public function actualizarFerrawinIds(Request $request)
    {
        try {
            $request->validate([
                'actualizaciones' => 'required|array|min:1',
                'actualizaciones.*.elemento_id' => 'required|integer',
                'actualizaciones.*.ferrawin_id' => 'required|string',
            ]);

            $actualizaciones = $request->input('actualizaciones');
            $actualizados = 0;
            $errores = [];

            foreach ($actualizaciones as $act) {
                $elemento = \App\Models\Elemento::find($act['elemento_id']);

                if (!$elemento) {
                    $errores[] = "Elemento {$act['elemento_id']} no encontrado";
                    continue;
                }

                $elemento->ferrawin_id = $act['ferrawin_id'];
                $elemento->save();
                $actualizados++;
            }

            Log::channel('ferrawin_sync')->info('✅ [API] ferrawin_id actualizados', [
                'recibidos' => count($actualizaciones),
                'actualizados' => $actualizados,
                'errores' => count($errores),
            ]);

            return response()->json([
                'success' => true,
                'actualizados' => $actualizados,
                'errores' => $errores,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            Log::channel('ferrawin_sync')->error('❌ [API] Error actualizando ferrawin_ids', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno',
            ], 500);
        }
    }
}
