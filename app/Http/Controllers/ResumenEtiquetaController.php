<?php

namespace App\Http\Controllers;

use App\Services\ResumenEtiquetaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResumenEtiquetaController extends Controller
{
    protected ResumenEtiquetaService $resumenService;

    public function __construct(ResumenEtiquetaService $resumenService)
    {
        $this->resumenService = $resumenService;
    }

    /**
     * Vista previa de los grupos que se crearían.
     * GET /api/etiquetas/resumir/preview
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'planilla_id' => 'required|integer|exists:planillas,id',
            'maquina_id' => 'nullable|integer|exists:maquinas,id',
        ]);

        $resultado = $this->resumenService->previsualizar(
            $request->integer('planilla_id'),
            $request->integer('maquina_id')
        );

        return response()->json($resultado);
    }

    /**
     * Ejecuta el resumen de etiquetas.
     * POST /api/etiquetas/resumir
     */
    public function resumir(Request $request): JsonResponse
    {
        $request->validate([
            'planilla_id' => 'required|integer|exists:planillas,id',
            'maquina_id' => 'nullable|integer|exists:maquinas,id',
        ]);

        $resultado = $this->resumenService->resumir(
            $request->integer('planilla_id'),
            $request->integer('maquina_id'),
            auth()->id()
        );

        return response()->json($resultado);
    }

    /**
     * Desagrupa un grupo específico.
     * POST /api/etiquetas/resumir/{grupo}/desagrupar
     */
    public function desagrupar(int $grupoId): JsonResponse
    {
        $resultado = $this->resumenService->desagrupar($grupoId);

        return response()->json($resultado);
    }

    /**
     * Desagrupa todos los grupos de una planilla.
     * POST /api/etiquetas/resumir/desagrupar-todos
     */
    public function desagruparTodos(Request $request): JsonResponse
    {
        $request->validate([
            'planilla_id' => 'required|integer|exists:planillas,id',
            'maquina_id' => 'nullable|integer|exists:maquinas,id',
        ]);

        $resultado = $this->resumenService->desagruparTodos(
            $request->integer('planilla_id'),
            $request->integer('maquina_id')
        );

        return response()->json($resultado);
    }

    /**
     * Obtiene las etiquetas de un grupo para imprimir.
     * GET /api/etiquetas/resumir/{grupo}/imprimir
     */
    public function etiquetasParaImprimir(int $grupoId): JsonResponse
    {
        try {
            $resultado = $this->resumenService->obtenerEtiquetasParaImprimir($grupoId);

            return response()->json([
                'success' => true,
                ...$resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener etiquetas: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Obtiene los grupos activos de una planilla.
     * GET /api/etiquetas/resumir/grupos
     */
    public function grupos(Request $request): JsonResponse
    {
        $request->validate([
            'planilla_id' => 'required|integer|exists:planillas,id',
            'maquina_id' => 'nullable|integer|exists:maquinas,id',
        ]);

        $grupos = $this->resumenService->obtenerGrupos(
            $request->integer('planilla_id'),
            $request->integer('maquina_id')
        );

        return response()->json([
            'success' => true,
            'grupos' => $grupos,
            'total' => $grupos->count(),
        ]);
    }

    /**
     * Cambia el estado de todas las etiquetas de un grupo.
     * PUT /api/etiquetas/resumir/{grupo}/estado
     */
    public function cambiarEstado(Request $request, int $grupoId): JsonResponse
    {
        $request->validate([
            'maquina_id' => 'required|integer|exists:maquinas,id',
            'longitud_seleccionada' => 'nullable|integer|min:0',
        ]);

        $resultado = $this->resumenService->cambiarEstadoGrupo(
            $grupoId,
            $request->integer('maquina_id'),
            $request->integer('longitud_seleccionada', 0),
            auth()->id()
        );

        return response()->json($resultado);
    }

    // ==================== ENDPOINTS MULTI-PLANILLA ====================

    /**
     * Vista previa del resumen multi-planilla.
     * GET /api/etiquetas/resumir/multiplanilla/preview
     */
    public function previewMultiplanilla(Request $request): JsonResponse
    {
        $request->validate([
            'maquina_id' => 'required|integer|exists:maquinas,id',
        ]);

        $resultado = $this->resumenService->previsualizarMultiplanilla(
            $request->integer('maquina_id')
        );

        return response()->json($resultado);
    }

    /**
     * Ejecuta el resumen multi-planilla.
     * POST /api/etiquetas/resumir/multiplanilla
     */
    public function resumirMultiplanilla(Request $request): JsonResponse
    {
        $request->validate([
            'maquina_id' => 'required|integer|exists:maquinas,id',
        ]);

        $resultado = $this->resumenService->resumirMultiplanilla(
            $request->integer('maquina_id'),
            auth()->id()
        );

        return response()->json($resultado);
    }

    /**
     * Desagrupa todos los grupos multi-planilla de una máquina.
     * POST /api/etiquetas/resumir/multiplanilla/desagrupar-todos
     */
    public function desagruparTodosMultiplanilla(Request $request): JsonResponse
    {
        $request->validate([
            'maquina_id' => 'required|integer|exists:maquinas,id',
        ]);

        $resultado = $this->resumenService->desagruparTodosMaquina(
            $request->integer('maquina_id')
        );

        return response()->json($resultado);
    }

    /**
     * Obtiene los grupos multi-planilla activos de una máquina.
     * GET /api/etiquetas/resumir/multiplanilla/grupos
     */
    public function gruposMultiplanilla(Request $request): JsonResponse
    {
        $request->validate([
            'maquina_id' => 'required|integer|exists:maquinas,id',
        ]);

        $grupos = $this->resumenService->obtenerGruposMultiplanilla(
            $request->integer('maquina_id')
        );

        return response()->json([
            'success' => true,
            'grupos' => $grupos,
            'total' => $grupos->count(),
        ]);
    }
}
