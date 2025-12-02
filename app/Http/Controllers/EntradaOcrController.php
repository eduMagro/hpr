<?php

namespace App\Http\Controllers;

use App\Models\EntradaImportLog;
use App\Services\AlbaranOcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntradaOcrController extends Controller
{
    public function parse(Request $request, AlbaranOcrService $service): JsonResponse
    {
        $request->validate([
            'albaran_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        try {
            $log = $service->parseAndLog($request->file('albaran_file'), auth()->id());

            return response()->json([
                'success' => true,
                'log_id' => $log->id,
                'raw_text' => $log->raw_text,
                'parsed' => $log->parsed_payload,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Marca un log como rechazado manualmente (opcional).
     */
    public function reject(Request $request): JsonResponse
    {
        $request->validate([
            'log_id' => 'required|exists:entrada_import_logs,id',
        ]);

        $log = EntradaImportLog::findOrFail($request->log_id);
        $log->status = 'rejected';
        $log->reviewed_at = now();
        $log->save();

        return response()->json(['success' => true]);
    }
}
