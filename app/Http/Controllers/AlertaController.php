<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    public function index()
    {
        // Obtener alertas ordenadas por fecha
        $alertas = Alerta::orderBy('created_at', 'desc')->paginate(10);

        // Marcar todas las alertas como leídas
        Alerta::where('leida', false)->update(['leida' => true]);

        return view('alertas.index', compact('alertas'));
    }

    /**
     * Devuelve la cantidad de alertas sin leer (para mostrar la exclamación en la navbar).
     */
    public function alertasSinLeer()
    {
        $alertasSinLeer = Alerta::where('leida', 0)->count();
        return response()->json(['cantidad' => $alertasSinLeer]);
    }
}
