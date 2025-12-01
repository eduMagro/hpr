<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\TasaSeguridadSocial;
use App\Models\Convenio;
use App\Models\Turno;
use App\Models\TasaIrpf;
use App\Models\Categoria;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index()
    {
        $empresas = Empresa::all();
        $porcentajes_ss = TasaSeguridadSocial::all();
        $tramos = TasaIrpf::all();
        $convenio = Convenio::all();
        $turnos = Turno::all();
        $categorias = Categoria::all();
        return view('empresas.index', compact('empresas', 'porcentajes_ss', 'tramos', 'convenio', 'turnos', 'categorias'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string',
            // añade otros campos aquí
        ]);

        Empresa::create($request->all());

        return redirect()->route('empresas.index')->with('success', 'Empresa creada correctamente.');
    }

    public function update(Request $request, Empresa $empresa)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string',
            // añade otros campos aquí
        ]);

        $empresa->update($request->all());

        return redirect()->route('empresas.index')->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroy(Empresa $empresa)
    {
        $empresa->delete();
        return redirect()->route('empresas.index')->with('success', 'Empresa eliminada correctamente.');
    }

    /**
     * Actualiza un campo de categoría en línea
     */
    public function updateCategoriaField(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:categorias,id',
                'field' => 'required|string|in:nombre',
                'value' => 'required|string|max:255',
            ]);

            $categoria = Categoria::findOrFail($validated['id']);
            $categoria->{$validated['field']} = $validated['value'];
            $categoria->save();

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error actualizando categoría: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea una nueva categoría
     */
    public function storeCategoria(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255|unique:categorias,nombre',
            ]);

            $categoria = new Categoria();
            $categoria->nombre = $validated['nombre'];
            $categoria->save();

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada correctamente.',
                'categoria' => $categoria
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creando categoría: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina una categoría
     */
    public function destroyCategoria(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:categorias,id',
            ]);

            $categoria = Categoria::findOrFail($validated['id']);

            // Verificar si tiene usuarios asociados
            if ($categoria->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar: hay usuarios asignados a esta categoría.'
                ], 400);
            }

            $categoria->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error eliminando categoría: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría.'
            ], 500);
        }
    }
}
