<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use Illuminate\Support\Facades\Log;

class SalidaController extends Controller
{
    /**
     * Muestra la lista de planillas con sus paquetes y el progreso de peso.
     */
    public function index()
    {

            // Cargar las relaciones necesarias
            $planillas = Planilla::with([
                'paquetes:id,planilla_id,peso,ubicacion_id',
                'etiquetas' => function ($query) {
                    $query->whereNull('paquete_id')
                          ->where('estado', 'completado')
                          ->select('id', 'planilla_id', 'estado', 'peso');
                },
                'elementos' => function ($query) {
                    $query->whereNull('paquete_id')
                          ->where('estado', 'completado')
                          ->select('id', 'planilla_id', 'estado', 'peso');
                }
            ])->get();

            // Procesar los cálculos en el servidor
            $planillasCalculadas = $planillas->map(function ($planilla) {
                $pesoTotalPaquetes = $planilla->paquetes->sum('peso');
                $pesoElementosNoEmpaquetados = $planilla->elementos->sum('peso');
                $pesoEtiquetasNoEmpaquetadas = $planilla->etiquetas->sum('peso');

                $pesoAcumulado = $pesoTotalPaquetes + $pesoElementosNoEmpaquetados + $pesoEtiquetasNoEmpaquetadas;
                $pesoRestante = max(0, $planilla->peso_total - $pesoAcumulado);
                $progreso = ($planilla->peso_total > 0) ? ($pesoAcumulado / $planilla->peso_total) * 100 : 0;

                return [
                    'planilla' => $planilla,
                    'pesoTotalPaquetes' => $pesoTotalPaquetes,
                    'pesoElementosNoEmpaquetados' => $pesoElementosNoEmpaquetados,
                    'pesoEtiquetasNoEmpaquetadas' => $pesoEtiquetasNoEmpaquetadas,
                    'pesoAcumulado' => $pesoAcumulado,
                    'pesoRestante' => $pesoRestante,
                    'progreso' => $progreso
                ];
            });

            return view('salidas.index', compact('planillasCalculadas'));

        
    }
}
