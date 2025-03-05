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

        // Obtener todas las salidas de paquetes con estado 'completada'
        $salidasPaquetes = SalidaPaquete::with('paquete.planilla')
            ->join('salidas', 'salidas.id', '=', 'salidas_paquetes.salida_id') // Asegúrate de que 'salida_id' sea la clave foránea correcta
            ->where('salidas.estado', 'completada') // Filtrar por estado 'completada' en la tabla salidas
            ->get();

        // Agrupar los paquetes por nom_obra y sumar el peso de los paquetes
        $pesoPorObra = $salidasPaquetes->groupBy(function ($salidaPaquete) {
            // Acceder al paquete y su planilla
            $planilla = $salidaPaquete->paquete->planilla;

            // Si no tiene planilla, retornar un valor predeterminado
            return $planilla ? $planilla->nom_obra : 'Sin obra asociada';
        })->map(function ($salidas) {
            // Sumar el peso de todos los paquetes asociados a esa obra
            return $salidas->sum(function ($salidaPaquete) {
                return $salidaPaquete->paquete->peso;
            });
        });



        // Pasar los datos a la vista
        return view('estadisticas.index', compact(
            'datosPorPlanilla',
            'pesoTotalPorDiametro',
            'stockEncarretado',
            'stockBarras',
            'pesoPorObra'
        ));
    }
}
