<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;

class SalidaController extends Controller
{
    /**
     * Muestra la lista de planillas con sus paquetes y el progreso de peso.
     */
    public function index()
    {
        // Obtener todas las planillas con los paquetes asociados
        $planillas = Planilla::with(['paquetes' => function ($query) {
            $query->select('id', 'planilla_id', 'peso');
        }])->get();

        return view('planillas.index', compact('planillas'));
    }
}
