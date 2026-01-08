<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FuncionController extends Controller
{
    public function __construct()
    {
        // Restringir acceso a usuarios del departamento_id = 7
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (!$user || !$user->departamentos()->where('departamentos.id', 7)->exists()) {
                return redirect('/');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $solicitudes = \App\Models\Solicitud::with(['creador', 'asignado'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

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
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'prioridad' => 'required|string',
            'asignado_a' => 'nullable|exists:users,id',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['estado'] = 'Nueva';

        \App\Models\Solicitud::create($validated);

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
