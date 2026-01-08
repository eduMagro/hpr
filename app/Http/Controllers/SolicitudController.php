<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SolicitudController extends Controller
{
    public function index()
    {
        $solicitudes = \App\Models\Solicitud::with(['creador', 'asignado'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Intentamos buscar usuarios en departamentos técnicos o con rol oficina
        $users = \App\Models\User::where('rol', 'oficina')
            ->orWhereHas('departamentos', function ($q) {
                $q->where('nombre', 'like', '%Programación%')
                    ->orWhere('nombre', 'like', '%Sistemas%');
            })
            ->orderBy('name')
            ->get();

        $prioridades = ['Alta', 'Media', 'Baja'];
        $estados = ['Nueva', 'Lanzada', 'En revisión', 'Merged', 'Completada'];

        return view('solicitudes.index', compact('solicitudes', 'users', 'prioridades', 'estados'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'prioridad' => 'required|string',
            'asignado_a' => 'nullable|exists:users,id',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['estado'] = 'Nueva';

        \App\Models\Solicitud::create($validated);

        return redirect()->route('solicitudes.index')->with('success', 'Solicitud creada correctamente.');
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

        return redirect()->route('solicitudes.index')->with('success', 'Solicitud actualizada.');
    }

    public function destroy($id)
    {
        $solicitud = \App\Models\Solicitud::findOrFail($id);
        $solicitud->delete();

        return redirect()->route('solicitudes.index')->with('success', 'Solicitud eliminada.');
    }

}
