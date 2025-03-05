<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Elemento;
use App\Models\Producto;
use App\Models\Planilla;
use App\Models\Obra;
use App\Models\SalidaPaquete;

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
        // Obtener todas las obras
        $obras = Obra::all();

        // Calcular el peso entregado por cada obra
        $pesoEntregadoPorObra = $obras->map(function ($obra) {
            // Obtener todos los paquetes cuya planilla esté asociada a esta obra
            $paquetes = SalidaPaquete::whereHas('elementos.planilla', function ($query) use ($obra) {
                $query->where('nom_obra', $obra->nom_obra);
            })
                ->with('elementos')
                ->get();

            // Sumar el peso de todos los paquetes
            $pesoTotal = $paquetes->sum(function ($paquete) {
                return $paquete->peso_total; // Asegúrate de que 'peso_total' es el campo correcto en 'salidas_paquetes'
            });

            return [
                'obra' => $obra->nom_obra,
                'peso_total' => $pesoTotal,
            ];
        });
        // Pasar los datos a la vista
        return view('estadisticas.index', compact(
            'datosPorPlanilla',
            'pesoTotalPorDiametro',
            'stockEncarretado',
            'stockBarras',
            'pesoEntregadoPorObra'
        ));
    }
}
