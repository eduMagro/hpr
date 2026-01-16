<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsistenteInforme;
use App\Services\Asistente\InformeService;
use App\Services\Asistente\ReportePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsistenteReportController extends Controller
{
    protected InformeService $informeService;
    protected ReportePdfService $pdfService;

    public function __construct(InformeService $informeService, ReportePdfService $pdfService)
    {
        $this->informeService = $informeService;
        $this->pdfService = $pdfService;
    }

    /**
     * Lista los informes del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $informes = AsistenteInforme::delUsuario(Auth::id())
            ->vigentes()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($informe) => [
                'id' => $informe->id,
                'tipo' => $informe->tipo,
                'titulo' => $informe->titulo,
                'tiene_pdf' => $informe->tienePdf(),
                'expira_at' => $informe->expira_at->format('d/m/Y H:i'),
                'created_at' => $informe->created_at->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'informes' => $informes,
        ]);
    }

    /**
     * Genera un nuevo informe
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => 'required|string|in:' . implode(',', array_keys(AsistenteInforme::TIPOS)),
            'parametros' => 'nullable|array',
            'generar_pdf' => 'nullable|boolean',
        ]);

        try {
            $informe = $this->informeService->generarInforme(
                $request->tipo,
                Auth::id(),
                $request->parametros ?? []
            );

            // Generar PDF si se solicita
            if ($request->generar_pdf) {
                $this->pdfService->generarPdf($informe);
                $informe->refresh();
            }

            return response()->json([
                'success' => true,
                'informe' => [
                    'id' => $informe->id,
                    'tipo' => $informe->tipo,
                    'titulo' => $informe->titulo,
                    'datos' => $informe->datos,
                    'resumen' => $informe->resumen,
                    'tiene_pdf' => $informe->tienePdf(),
                    'expira_at' => $informe->expira_at->format('d/m/Y H:i'),
                ],
                'mensaje_formateado' => $this->informeService->formatearParaChat($informe),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al generar el informe: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Muestra un informe específico
     */
    public function show(int $id): JsonResponse
    {
        $informe = AsistenteInforme::delUsuario(Auth::id())
            ->findOrFail($id);

        if ($informe->haExpirado()) {
            return response()->json([
                'success' => false,
                'error' => 'Este informe ha expirado.',
            ], 410);
        }

        return response()->json([
            'success' => true,
            'informe' => [
                'id' => $informe->id,
                'tipo' => $informe->tipo,
                'titulo' => $informe->titulo,
                'datos' => $informe->datos,
                'resumen' => $informe->resumen,
                'parametros' => $informe->parametros,
                'tiene_pdf' => $informe->tienePdf(),
                'expira_at' => $informe->expira_at->format('d/m/Y H:i'),
                'created_at' => $informe->created_at->format('d/m/Y H:i'),
            ],
            'mensaje_formateado' => $this->informeService->formatearParaChat($informe),
        ]);
    }

    /**
     * Descarga el PDF de un informe
     */
    public function descargarPdf(int $id)
    {
        $informe = AsistenteInforme::delUsuario(Auth::id())
            ->findOrFail($id);

        if ($informe->haExpirado()) {
            abort(410, 'Este informe ha expirado.');
        }

        try {
            return $this->pdfService->descargarPdf($informe);
        } catch (\Exception $e) {
            abort(500, 'Error al descargar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Genera el PDF para un informe existente
     */
    public function generarPdf(int $id): JsonResponse
    {
        $informe = AsistenteInforme::delUsuario(Auth::id())
            ->findOrFail($id);

        if ($informe->haExpirado()) {
            return response()->json([
                'success' => false,
                'error' => 'Este informe ha expirado.',
            ], 410);
        }

        try {
            $this->pdfService->generarPdf($informe);
            $informe->refresh();

            return response()->json([
                'success' => true,
                'tiene_pdf' => true,
                'url_descarga' => route('asistente.informes.pdf', $informe->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al generar el PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un informe
     */
    public function destroy(int $id): JsonResponse
    {
        $informe = AsistenteInforme::delUsuario(Auth::id())
            ->findOrFail($id);

        // Eliminar PDF si existe
        if ($informe->tienePdf() && $informe->archivo_pdf) {
            \Storage::delete($informe->archivo_pdf);
        }

        $informe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Informe eliminado correctamente.',
        ]);
    }

    /**
     * Obtiene los tipos de informes disponibles
     */
    public function tipos(): JsonResponse
    {
        $tipos = collect(AsistenteInforme::TIPOS)->map(fn($nombre, $tipo) => [
            'tipo' => $tipo,
            'nombre' => $nombre,
            'descripcion' => $this->obtenerDescripcionTipo($tipo),
        ])->values();

        return response()->json([
            'success' => true,
            'tipos' => $tipos,
        ]);
    }

    /**
     * Obtiene la descripción de un tipo de informe
     */
    protected function obtenerDescripcionTipo(string $tipo): string
    {
        $descripciones = [
            'stock_general' => 'Stock completo por diámetro, tipo y nave',
            'stock_critico' => 'Productos bajo mínimo con recomendaciones de reposición',
            'produccion_diaria' => 'Kilos y elementos fabricados hoy por máquina',
            'produccion_semanal' => 'Resumen semanal con comparativa vs semana anterior',
            'consumo_maquinas' => 'Consumo de materia prima por máquina en el periodo',
            'peso_obra' => 'Kilos fabricados y entregados a cada obra',
            'planilleros' => 'Ranking de producción por operario',
            'planillas_pendientes' => 'Planillas pendientes y atrasadas por estado',
        ];

        return $descripciones[$tipo] ?? 'Sin descripción';
    }
}
