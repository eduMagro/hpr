<?php

namespace App\Http\Controllers;

use App\Models\DocumentoAyuda;
use App\Services\AyudaRAGService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AyudaController extends Controller
{
    private AyudaRAGService $ragService;

    public function __construct(AyudaRAGService $ragService)
    {
        $this->ragService = $ragService;
    }

    /**
     * Vista principal del centro de ayuda
     */
    public function index()
    {
        $documentos = DocumentoAyuda::orderBy('categoria')
            ->orderBy('orden')
            ->get();

        $categorias = DocumentoAyuda::categorias();

        return view('ayuda.index', compact('documentos', 'categorias'));
    }

    /**
     * Procesa una pregunta usando RAG
     */
    public function preguntar(Request $request): JsonResponse
    {
        $request->validate([
            'pregunta' => 'required|string|min:3|max:500',
        ]);

        $resultado = $this->ragService->procesarPregunta($request->pregunta);

        return response()->json([
            'success' => $resultado['success'],
            'data' => [
                'respuesta' => $resultado['respuesta'],
                'metodo' => $resultado['metodo'] ?? 'rag',
                'documentos' => $resultado['documentos_usados'] ?? [],
            ],
        ]);
    }

    /**
     * Obtiene sugerencias de preguntas basadas en los documentos
     */
    public function sugerencias(): JsonResponse
    {
        $categorias = DocumentoAyuda::activos()
            ->select('categoria', 'titulo')
            ->orderBy('categoria')
            ->get()
            ->groupBy('categoria')
            ->map(function ($docs, $categoria) {
                return [
                    'categoria' => ucfirst($categoria),
                    'ejemplos' => $docs->take(3)->pluck('titulo')->map(function ($titulo) {
                        return "¿Cómo " . lcfirst(str_replace(['Cómo ', '¿', '?'], '', $titulo)) . "?";
                    })->toArray(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $categorias,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // CRUD PARA DOCUMENTOS DE AYUDA (Panel Admin)
    // ─────────────────────────────────────────────────────────────

    /**
     * Lista todos los documentos (para el panel admin)
     */
    public function listarDocumentos(): JsonResponse
    {
        $documentos = DocumentoAyuda::orderBy('categoria')
            ->orderBy('orden')
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'categoria' => $doc->categoria,
                    'titulo' => $doc->titulo,
                    'contenido' => $doc->contenido,
                    'keywords' => $doc->keywords,
                    'tags' => $doc->tags,
                    'activo' => $doc->activo,
                    'orden' => $doc->orden,
                    'tiene_embedding' => !empty($doc->embedding),
                    'updated_at' => $doc->updated_at?->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'documentos' => $documentos,
            'categorias' => DocumentoAyuda::categorias(),
        ]);
    }

    /**
     * Crea un nuevo documento
     */
    public function crearDocumento(Request $request): JsonResponse
    {
        $request->validate([
            'categoria' => 'required|string|max:50',
            'titulo' => 'required|string|max:200',
            'contenido' => 'required|string',
            'keywords' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'activo' => 'boolean',
            'orden' => 'integer|min:0',
        ]);

        try {
            $documento = $this->ragService->crearDocumento([
                'categoria' => strtolower($request->categoria),
                'titulo' => $request->titulo,
                'contenido' => $request->contenido,
                'keywords' => $request->keywords,
                'tags' => $request->tags,
                'activo' => $request->activo ?? true,
                'orden' => $request->orden ?? 0,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento creado correctamente',
                'documento' => [
                    'id' => $documento->id,
                    'categoria' => $documento->categoria,
                    'titulo' => $documento->titulo,
                    'tiene_embedding' => !empty($documento->embedding),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al crear documento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza un documento existente
     */
    public function actualizarDocumento(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'categoria' => 'string|max:50',
            'titulo' => 'string|max:200',
            'contenido' => 'string',
            'keywords' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'activo' => 'boolean',
            'orden' => 'integer|min:0',
        ]);

        $documento = DocumentoAyuda::findOrFail($id);

        try {
            $datosActualizar = array_filter([
                'categoria' => $request->categoria ? strtolower($request->categoria) : null,
                'titulo' => $request->titulo,
                'contenido' => $request->contenido,
                'keywords' => $request->keywords,
                'tags' => $request->tags,
                'activo' => $request->activo,
                'orden' => $request->orden,
                'updated_by' => Auth::id(),
            ], fn($v) => $v !== null);

            $documento = $this->ragService->actualizarDocumento($documento, $datosActualizar);

            return response()->json([
                'success' => true,
                'message' => 'Documento actualizado correctamente',
                'documento' => [
                    'id' => $documento->id,
                    'categoria' => $documento->categoria,
                    'titulo' => $documento->titulo,
                    'tiene_embedding' => !empty($documento->embedding),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar documento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un documento
     */
    public function eliminarDocumento(int $id): JsonResponse
    {
        $documento = DocumentoAyuda::findOrFail($id);
        $titulo = $documento->titulo;
        $documento->delete();

        return response()->json([
            'success' => true,
            'message' => "Documento '{$titulo}' eliminado correctamente",
        ]);
    }

    /**
     * Cambia el estado activo/inactivo de un documento
     */
    public function toggleActivo(int $id): JsonResponse
    {
        $documento = DocumentoAyuda::findOrFail($id);
        $documento->activo = !$documento->activo;
        $documento->save();

        return response()->json([
            'success' => true,
            'activo' => $documento->activo,
            'message' => $documento->activo ? 'Documento activado' : 'Documento desactivado',
        ]);
    }

    /**
     * Regenera el embedding de un documento específico
     */
    public function regenerarEmbedding(int $id): JsonResponse
    {
        $documento = DocumentoAyuda::findOrFail($id);

        try {
            $texto = $documento->titulo . ' ' . $documento->contenido;
            $documento->embedding = $this->ragService->generarEmbedding($texto);
            $documento->save();

            return response()->json([
                'success' => true,
                'message' => 'Embedding regenerado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al regenerar embedding: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenera todos los embeddings
     */
    public function regenerarTodosEmbeddings(): JsonResponse
    {
        try {
            $count = $this->ragService->regenerarTodosLosEmbeddings();

            return response()->json([
                'success' => true,
                'message' => "Se regeneraron {$count} embeddings correctamente",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al regenerar embeddings: ' . $e->getMessage(),
            ], 500);
        }
    }
}
