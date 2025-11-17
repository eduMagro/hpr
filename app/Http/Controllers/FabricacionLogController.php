<?php

namespace App\Http\Controllers;

use App\Services\ProductionLogParser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controlador para visualizar logs de fabricación
 */
class FabricacionLogController extends Controller
{
    /**
     * Obtiene los detalles de fabricación de una etiqueta
     */
    public function getDetallesEtiqueta(Request $request): JsonResponse
    {
        $etiquetaId = $request->input('etiqueta_id');
        $month = $request->input('month', date('Y_m'));

        if (!$etiquetaId) {
            return response()->json([
                'success' => false,
                'message' => 'ID de etiqueta requerido'
            ], 400);
        }

        // Obtener logs de asignación de coladas
        $asignacionColadas = ProductionLogParser::getAsignacionColadasForEtiqueta($etiquetaId, $month);

        // Obtener logs de consumo de stock
        $consumoStock = ProductionLogParser::getConsumoStockForEtiqueta($etiquetaId, $month);

        // Obtener todos los logs relacionados
        $todosLogs = ProductionLogParser::getLogsForEtiqueta($etiquetaId, $month);

        return response()->json([
            'success' => true,
            'data' => [
                'etiqueta_id' => $etiquetaId,
                'asignacion_coladas' => $asignacionColadas,
                'consumo_stock' => $consumoStock,
                'logs_completos' => $todosLogs->toArray(),
            ]
        ]);
    }

    /**
     * Busca elementos por número de colada
     */
    public function buscarPorColada(Request $request): JsonResponse
    {
        $colada = $request->input('colada');
        $month = $request->input('month', date('Y_m'));

        if (!$colada) {
            return response()->json([
                'success' => false,
                'message' => 'Número de colada requerido'
            ], 400);
        }

        $elementos = ProductionLogParser::getElementsByColada($colada, $month);

        return response()->json([
            'success' => true,
            'data' => [
                'colada' => $colada,
                'elementos_encontrados' => $elementos->count(),
                'elementos' => $elementos->toArray(),
            ]
        ]);
    }

    /**
     * Obtiene estadísticas de un mes
     */
    public function getEstadisticas(Request $request): JsonResponse
    {
        $month = $request->input('month', date('Y_m'));

        $stats = ProductionLogParser::getStats($month);

        return response()->json([
            'success' => true,
            'data' => [
                'mes' => $month,
                'estadisticas' => $stats,
            ]
        ]);
    }

    /**
     * Obtiene lista de meses disponibles
     */
    public function getMesesDisponibles(): JsonResponse
    {
        $meses = ProductionLogParser::getAvailableMonths();

        return response()->json([
            'success' => true,
            'data' => [
                'meses' => $meses->toArray(),
            ]
        ]);
    }

    /**
     * Vista principal de trazabilidad
     */
    public function index()
    {
        $meses = ProductionLogParser::getAvailableMonths();
        $mesActual = date('Y_m');
        $stats = ProductionLogParser::getStats($mesActual);

        return view('panel.fabricacion.trazabilidad', [
            'meses' => $meses,
            'mes_actual' => $mesActual,
            'estadisticas' => $stats,
        ]);
    }
}
