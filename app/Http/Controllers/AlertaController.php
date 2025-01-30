<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlertaController extends Controller
{


    public function index()
    {
        try {
            DB::beginTransaction();

            // Obtener alertas ordenadas por fecha
            $alertas = Alerta::orderBy('created_at', 'desc')->paginate(10);

            // Obtener alertas no leídas antes de marcarlas como leídas
            $alertasNoLeidas = Alerta::where('leida', false)->get();

            // Marcar todas las alertas como leídas
            Alerta::whereIn('id', $alertasNoLeidas->pluck('id'))->update(['leida' => true]);

            DB::commit();

            return view('alertas.index', compact('alertas', 'alertasNoLeidas'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('alertas.index')->with('error', 'Ocurrió un error al cargar las alertas.');
        }
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
