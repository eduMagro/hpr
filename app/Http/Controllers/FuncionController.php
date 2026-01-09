<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FuncionController extends Controller
{
    public function __construct()
    {
        //
    }

    public function index()
    {
        $solicitudes = \App\Models\Solicitud::with(['creador', 'asignado'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Usuarios del departamento "Programador" (id = 7)
        $users = \App\Models\User::whereHas('departamentos', function ($q) {
            $q->where('departamentos.id', 7);
        })
            ->orderBy('name')
            ->get();

        $prioridades = ['Alta', 'Media', 'Baja'];
        $estados = ['Nueva', 'Lanzada', 'En progreso', 'En revisión', 'Merged', 'Completada'];

        return view('solicitudes.index', compact('solicitudes', 'users', 'prioridades', 'estados'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'prioridad' => 'required|string',
            'asignado_a' => 'nullable|exists:users,id',
            'comentario' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['estado'] = 'Nueva';

        $solicitud = \App\Models\Solicitud::create($validated);
        $solicitud->load(['creador', 'asignado']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($solicitud);
        }

        return redirect()->route('funciones.index')->with('success', 'Función creada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $solicitud = \App\Models\Solicitud::findOrFail($id);

        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'prioridad' => 'sometimes|string',
            'estado' => 'sometimes|string',
            'comentario' => 'nullable|string',
            'asignado_a' => 'nullable|exists:users,id',
        ]);

        $solicitud->update($validated);

        if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->isJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('funciones.index')->with('success', 'Función actualizada.');
    }

    public function destroy($id)
    {
        $solicitud = \App\Models\Solicitud::findOrFail($id);
        $solicitud->delete();

        return redirect()->route('funciones.index')->with('success', 'Función eliminada.');
    }
}
