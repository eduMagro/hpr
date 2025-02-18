<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;


class EstadisticasController extends Controller
{

    public function index()
    {

        // Datos agrupados por planilla y diámetro
        $datosPorPlanilla = DB::table('elementos')
            ->join('planillas', 'elementos.planilla_id', '=', 'planillas.id')
            ->select(
                'elementos.diametro',
                'elementos.planilla_id',
                DB::raw('SUM(elementos.peso) AS peso_por_planilla')
            )
            ->where('planillas.estado', 'pendiente')
            ->groupBy('elementos.diametro', 'elementos.planilla_id')
            ->orderBy('elementos.diametro')
            ->orderBy('elementos.planilla_id')
            ->get();

        // Datos agrupados por diámetro (peso total por diámetro)
        $pesoTotalPorDiametro = DB::table('elementos')
            ->join('planillas', 'elementos.planilla_id', '=', 'planillas.id')
            ->select(
                'elementos.diametro',
                DB::raw('SUM(elementos.peso) AS peso_total')
            )
            ->where('planillas.estado', 'pendiente')
            ->groupBy('elementos.diametro')
            ->orderBy('elementos.diametro')
            ->get();

       // Nuevo: Datos agrupados por diámetro (stock total por diámetro con estado almacenado)
    $stockPorDiametro = DB::table('elementos')
    ->join('planillas', 'elementos.planilla_id', '=', 'planillas.id')
    ->select(
        'elementos.diametro',
        DB::raw('SUM(elementos.peso) AS stock')
    )
    ->where('planillas.estado', 'almacenado')
    ->groupBy('elementos.diametro')
    ->orderBy('elementos.diametro')
    ->get();

// Pasar los datos a la vista
return view('estadisticas.index', compact(['pesoTotalPorDiametro', 'datosPorPlanilla', 'stockPorDiametro']));
    }
}
