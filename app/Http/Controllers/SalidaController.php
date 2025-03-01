<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Salida;
use App\Models\Paquete;
use App\Models\Etiqueta;
use App\Models\Elemento;

class SalidaController extends Controller
{
    public function index()
    {
        // Obtener planillas COMPLETADAS (100% progreso)
        $planillasCompletadas = Planilla::whereHas('elementos', function ($query) {
            $query->where('estado', 'completado');
        })->with('user')->get();
    
        // Obtener todas las salidas registradas
        $salidas = Salida::with(['planillas'])->latest()->get();
        
        return view('salidas.index', compact('planillasCompletadas', 'salidas'));
    }
    
    // Método para almacenar una nueva salida
    public function store(Request $request)
    {
        $request->validate([
            'camion' => 'required|string|max:255',
            'planillas' => 'required|array',
            'planillas.*' => 'exists:planillas,id'
        ]);
    
        $salida = Salida::create([
            'camion' => $request->camion
        ]);
    
        $salida->planillas()->attach($request->planillas);
    
        return redirect()->route('salidas.index')->with('success', 'Salida registrada correctamente.');
    }
    

    public function marcarSubido(Request $request)
    {
        $codigo = $request->codigo;

        // Buscar en paquetes, etiquetas o elementos
        $paquete = Paquete::where('id', $codigo)->first();
        $etiqueta = Etiqueta::where('id', $codigo)->first();
        $elemento = Elemento::where('id', $codigo)->first();

        if ($paquete) {
            $paquete->subido = true;
            $paquete->save();
            return response()->json(['success' => true, 'mensaje' => 'Paquete marcado como subido.']);
        }

        if ($etiqueta) {
            $etiqueta->subido = true;
            $etiqueta->save();
            return response()->json(['success' => true, 'mensaje' => 'Etiqueta marcada como subida.']);
        }

        if ($elemento) {
            $elemento->subido = true;
            $elemento->save();
            return response()->json(['success' => true, 'mensaje' => 'Elemento marcado como subido.']);
        }

        return response()->json(['success' => false, 'mensaje' => 'Código no encontrado.'], 404);
    }
}
