<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;

class SalidaController extends Controller
{
    public function index()
    {
        // Obtener planillas COMPLETADAS (100% progreso)
        $planillasCompletadas = Planilla::whereHas('elementos', function ($query) {
            $query->where('estado', 'completado');
        })->with('user')->get();
    
        // Obtener todas las salidas registradas
        $salidas = Salida::with(['camion', 'planillas'])->latest()->get();
    
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
    
}
