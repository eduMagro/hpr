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
    try {
        // Construir la consulta pero NO ejecutarla todavía
        $query = Planilla::with([
            'paquetes:id,planilla_id,peso',
            'etiquetas' => function ($query) {
                $query->whereNull('paquete_id')
                      ->where('estado', 'completado')
                      ->select('id', 'planilla_id', 'estado');
            },
            'elementos' => function ($query) {
                $query->whereNull('paquete_id')
                      ->where('estado', 'completado')
                      ->select('id', 'planilla_id', 'estado', 'peso');
            }
        ]);

        // 🔍 Mostrar la consulta exacta antes de ejecutarla
        \Log::info('Consulta generada: ' . $query->toSql());

        $planillas = $query->get();

        return view('salidas.index', compact('planillas'));

    } catch (\Exception $e) {
      
            return redirect()->route('dashboard')->with('error', 'Ocurrió un error al cargar las salidas.');
    }
}


}
