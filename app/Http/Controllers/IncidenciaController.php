<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IncidenciaController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Incidencia::with(['maquina', 'user'])->latest('fecha_reporte');

        if ($request->has('ver_inactivas')) {
            $query->where('estado', 'resuelta');
        } else {
            $query->where('estado', '!=', 'resuelta');
        }

        $incidencias = $query->paginate(10);

        // Count active incidents for the header
        $activasCount = \App\Models\Incidencia::where('estado', '!=', 'resuelta')->count();

        // Get machines for the create modal
        $maquinas = \App\Models\Maquina::with('obra:id,obra')->orderBy('nombre')->get(['id', 'nombre', 'codigo', 'imagen', 'obra_id']);

        return view('incidencias.index', compact('incidencias', 'activasCount', 'maquinas'));
    }

    public function listAjax(Request $request)
    {
        $query = \App\Models\Incidencia::with(['maquina', 'user'])->latest('fecha_reporte');

        if ($request->has('ver_inactivas')) {
            $query->where('estado', 'resuelta');
        } else {
            $query->where('estado', '!=', 'resuelta');
        }

        $incidencias = $query->paginate(10);

        return view('incidencias.partials.lista', compact('incidencias'))->render();
    }

    public function show($id)
    {
        $incidencia = \App\Models\Incidencia::with(['maquina', 'user', 'resolver'])->findOrFail($id);

        // Load history for the same machine
        $historial = \App\Models\Incidencia::where('maquina_id', $incidencia->maquina_id)
            ->where('id', '!=', $id)
            ->latest('fecha_reporte')
            ->limit(5)
            ->get();

        return view('incidencias.show', compact('incidencia', 'historial'));
    }

    public function create(Request $request)
    {
        $maquina_id = $request->get('maquina_id');
        $maquinas = \App\Models\Maquina::select('id', 'codigo', 'nombre')->get();
        return view('incidencias.create', compact('maquinas', 'maquina_id'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
            'descripcion' => 'nullable|string', // Changed to nullable as per modal optional hint
            'titulo' => 'required',
            'fotos.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240', // Increased max size
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // Added validation for single file
            'estado_maquina' => 'nullable|in:activa,averiada,pausa,mantenimiento'
        ]);

        $paths = [];

        // Helper to save file to public directory
        $saveFile = function ($file) use (&$paths) {
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('incidencias-archivos'), $filename);
            $paths[] = 'incidencias-archivos/' . $filename;
        };

        // Handle multiple files
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $file) {
                $saveFile($file);
            }
        }

        // Handle single file (from mobile modal)
        if ($request->hasFile('imagen')) {
            $saveFile($request->file('imagen'));
        }

        $incidencia = \App\Models\Incidencia::create([
            'maquina_id' => $request->maquina_id,
            'user_id' => auth()->id(),
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion ?? '',
            'fotos' => $paths,
            'prioridad' => $request->prioridad ?? 'media',
            'estado' => 'abierta',
            'fecha_reporte' => now(),
        ]);

        // Update machine status if provided
        $maquina = \App\Models\Maquina::find($request->maquina_id);
        if ($maquina) {
            // Default to 'averiada' only if not provided, for backward compatibility or safety
            $nuevoEstado = $request->input('estado_maquina', 'averiada');
            $maquina->estado = $nuevoEstado;
            $maquina->save();
        }

        return redirect()->route('incidencias.show', $incidencia->id)->with('success', 'Incidencia reportada correctamente');
    }

    public function resolve(Request $request, $id)
    {
        $incidencia = \App\Models\Incidencia::findOrFail($id);

        $incidencia->update([
            'estado' => 'resuelta',
            'resolucion' => $request->resolucion,
            'fecha_resolucion' => now(),
            'resuelto_por' => auth()->id()
        ]);

        // Check if machine has other active incidents, if not, set to 'activa'
        $activeIncidents = \App\Models\Incidencia::where('maquina_id', $incidencia->maquina_id)
            ->where('estado', '!=', 'resuelta')
            ->exists();

        if (!$activeIncidents) {
            $maquina = $incidencia->maquina;
            $maquina->estado = 'activa';
            $maquina->save();
        }

        return redirect()->back()->with('success', 'Incidencia marcada como resuelta');
    }
}
