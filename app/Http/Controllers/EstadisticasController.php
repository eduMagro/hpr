<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Elemento;
use App\Models\Producto;

class EstadisticasController extends Controller
{

    public function index()
    {
        // Datos agrupados por planilla y diámetro (pendiente)
        $datosPorPlanilla = Elemento::whereHas('planilla', function ($query) {
                $query->where('estado', 'pendiente');
            })
            ->selectRaw('diametro, planilla_id, SUM(peso) AS peso_por_planilla')
            ->groupBy('diametro', 'planilla_id')
            ->orderBy('diametro')
            ->orderBy('planilla_id')
            ->get();
    
        // Datos agrupados por diámetro (peso total requerido para pendientes)
        $pesoTotalPorDiametro = Elemento::whereHas('planilla', function ($query) {
                $query->where('estado', 'pendiente');
            })
            ->selectRaw('diametro, SUM(peso) AS peso_total')
            ->groupBy('diametro')
            ->orderBy('diametro')
            ->get();
    
        // Stock de productos "encarretado" por diámetro (estado "almacenado")
        $stockEncarretado = Producto::where('estado', 'almacenado')
            ->where('tipo', 'encarretado')
            ->selectRaw('diametro, SUM(peso_stock) AS stock')
            ->groupBy('diametro')
            ->orderBy('diametro')
            ->get();
    
        // Stock de productos "barras" por diámetro y longitud (estado "almacenado")
        $stockBarras = Producto::where('estado', 'almacenado')
            ->where('tipo', 'barra')
            ->selectRaw('diametro, longitud, SUM(peso_stock) AS stock')
            ->groupBy('diametro', 'longitud')
            ->orderBy('diametro')
            ->orderBy('longitud')
            ->get();

        // Pasar los datos a la vista
        return view('estadisticas.index', compact(
            'datosPorPlanilla', 
            'pesoTotalPorDiametro', 
            'stockEncarretado', 
            'stockBarras'
        ));
    }
    
    
}
