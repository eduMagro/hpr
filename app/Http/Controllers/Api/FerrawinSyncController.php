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
                'planillas.*.elementos' => 'required|array',
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
}
