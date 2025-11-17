<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductionLogger;
use Illuminate\Support\Facades\Storage;

class ProductionLogController extends Controller
{
    /**
     * Muestra la interfaz de espionaje de producción
     */
    public function index()
    {
        $logFiles = ProductionLogger::listLogFiles();

        return view('production-logs.index', [
            'logFiles' => $logFiles
        ]);
    }

    /**
     * Devuelve los últimos registros del archivo CSV actual en formato JSON
     * Para actualización en tiempo real
     */
    public function getLatestLogs(Request $request)
    {
        $limit = $request->get('limit', 50); // Últimos 50 registros por defecto
        $logPath = ProductionLogger::getCurrentLogPath();

        if (!file_exists($logPath)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No hay registros aún este mes'
            ]);
        }

        try {
            $file = fopen($logPath, 'r');
            $headers = fgetcsv($file, 0, ';');
            $rows = [];

            while (($row = fgetcsv($file, 0, ';')) !== false) {
                $rows[] = array_combine($headers, $row);
            }

            fclose($file);

            // Devolver los últimos N registros en orden inverso (más recientes primero)
            $latestRows = array_slice(array_reverse($rows), 0, $limit);

            return response()->json([
                'success' => true,
                'data' => $latestRows,
                'total' => count($rows),
                'showing' => count($latestRows)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al leer el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descarga el archivo CSV completo
     */
    public function downloadLog($fileName)
    {
        $filePath = 'produccion_piezas/' . $fileName;

        if (!Storage::exists($filePath)) {
            abort(404, 'Archivo no encontrado');
        }

        return Storage::download($filePath);
    }
}
