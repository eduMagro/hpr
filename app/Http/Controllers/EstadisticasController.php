<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Elemento;
use App\Models\Producto;
use App\Models\Planilla;
use App\Models\Obra;
use App\Models\SalidaPaquete;
use Carbon\Carbon;

class EstadisticasController extends Controller
{
    public function index()
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('dashboard')->with('abort', 'No tienes los permisos necesarios.');
        }
        // Calcular el stockaje
        $datosPorPlanilla = $this->getDatosPorPlanilla();
        $pesoTotalPorDiametro = $this->getPesoTotalPorDiametro();
        $stockEncarretado = $this->getStockEncarretado();
        $stockBarras = $this->getStockBarras();
        $mensajeDeAdvertencia = $this->compararStockConPeso($pesoTotalPorDiametro, $stockEncarretado, $stockBarras);

        // Calcular el stock deseado para dos semanas
        $stockDeseado = $this->getStockDeseadoParaDosSemanas();

        // Calcular peso suministrado a cada obra
        $salidasPaquetes = $this->getSalidasPaquetesCompletadas();
        $pesoPorObra = $this->agruparPaquetesPorObra($salidasPaquetes);

        // Obtener el peso importado por cada usuario
        $pesoPorPlanillero = $this->getPesoPorPlanillero();
        $pesoPorPlanilleroPorDia = $this->getPesoPorPlanilleroPorDia();

        // Pasar los mensajes a la sesión
        $this->handleSessionMessages($mensajeDeAdvertencia);

        // Pasar los datos a la vista
        return view('estadisticas.index', compact(
            'datosPorPlanilla',
            'pesoTotalPorDiametro',
            'stockEncarretado',
            'stockBarras',
            'pesoPorObra',
            'pesoPorPlanillero',
            'pesoPorPlanilleroPorDia'
        ));
    }
    // ---------------------------------------------------------------- Funciones para calcular el stockaje
    private function getDatosPorPlanilla()
    {
        return Elemento::whereHas('planilla', function ($query) {
            $query->where('estado', 'pendiente');
        })
            ->selectRaw('diametro, planilla_id, SUM(peso) AS peso_por_planilla')
            ->groupBy('diametro', 'planilla_id')
            ->orderBy('diametro')
            ->orderBy('planilla_id')
            ->get();
    }

    private function getPesoTotalPorDiametro()
    {
        return Elemento::whereHas('planilla', function ($query) {
            $query->where('estado', 'pendiente');
        })
            ->selectRaw('diametro, SUM(peso) AS peso_total')
            ->groupBy('diametro')
            ->orderBy('diametro')
            ->get();
    }

    private function getStockEncarretado()
    {
        return Producto::where('estado', 'almacenado')
            ->where('tipo', 'encarretado')
            ->selectRaw('diametro, SUM(peso_stock) AS stock')
            ->groupBy('diametro')
            ->orderBy('diametro')
            ->get();
    }

    private function getStockBarras()
    {
        return Producto::where('estado', 'almacenado')
            ->where('tipo', 'barra')
            ->selectRaw('diametro, longitud, SUM(peso_stock) AS stock')
            ->groupBy('diametro', 'longitud')
            ->orderBy('diametro')
            ->orderBy('longitud')
            ->get();
    }

    private function compararStockConPeso($pesoTotalPorDiametro, $stockEncarretado, $stockBarras)
    {
        $mensajeDeAdvertencia = [];

        foreach ($pesoTotalPorDiametro as $pesoDiametro) {
            // Buscar stock por diámetro en las categorías de "encarretado" y "barra"
            $stockEncarretadoPorDiametro = $stockEncarretado->firstWhere('diametro', $pesoDiametro->diametro);
            $stockBarrasPorDiametro = $stockBarras->where('diametro', $pesoDiametro->diametro)->sum('stock');

            // Calcular el stock total disponible
            $stockTotalDisponible = ($stockEncarretadoPorDiametro ? $stockEncarretadoPorDiametro->stock : 0) + $stockBarrasPorDiametro;

            // Comparar si el stock disponible es menor que el peso total requerido
            if ($stockTotalDisponible < $pesoDiametro->peso_total) {
                $mensajeDeAdvertencia[] = "Advertencia: El stock disponible para el diámetro {$pesoDiametro->diametro} es insuficiente. Faltan " . ($pesoDiametro->peso_total - $stockTotalDisponible) . " kg.";
            }
        }

        return $mensajeDeAdvertencia;
    }
    private function handleSessionMessages($mensajeDeAdvertencia)
    {
        if (!empty($mensajeDeAdvertencia)) {
            session()->flash('advertencia', $mensajeDeAdvertencia);
        } else {
            session()->flash('exito', 'Todo el stock requerido está disponible.');
        }
    }
    // ---------------------------------------------------------------- Función para calcular el stock deseado
    private function getStockDeseadoParaDosSemanas()
    {
        $fechaInicio = Elemento::where('estado', 'completado')->min('created_at');
        $fechaFin = Carbon::now();

        if (!$fechaInicio) {
            return collect(); // Retorna colección vacía si no hay datos
        }

        $diasTotales = Carbon::parse($fechaInicio)->diffInDays($fechaFin) ?: 1;

        return Elemento::where('estado', 'completado')
            ->select('diametro')
            ->selectRaw('SUM(peso) / ? as promedio_diario', [$diasTotales])
            ->groupBy('diametro')
            ->get()
            ->map(function ($item) {
                return (object)[
                    'diametro' => $item->diametro,
                    'stock_deseado' => round($item->promedio_diario * 14, 2)
                ];
            });
    }
    // ---------------------------------------------------------------- Funciones para calcular peso suministrado a obras
    private function getSalidasPaquetesCompletadas()
    {
        return SalidaPaquete::with('paquete.planilla')
            ->join('salidas', 'salidas.id', '=', 'salidas_paquetes.salida_id')
            ->where('salidas.estado', 'completada')
            ->get();
    }

    private function agruparPaquetesPorObra($salidasPaquetes)
    {
        return $salidasPaquetes->groupBy(function ($salidaPaquete) {
            $planilla = $salidaPaquete->paquete->planilla;
            return $planilla && $planilla->obra ? $planilla->obra->obra : 'Sin obra asociada';
        })->map(function ($salidas) {
            return $salidas->sum(function ($salidaPaquete) {
                return $salidaPaquete->paquete->peso;
            });
        });
    }

    // ---------------------------------------------------------------- Estadisticas de usuarios
    private function getPesoPorPlanillero()
    {
        return Planilla::where('estado', 'pendiente') // Filtrar solo planillas pendientes
            ->with('user:id,name') // Cargar el usuario con su nombre
            ->select('users_id', DB::raw('SUM(peso_total) as peso_importado')) // Agrupar por usuario
            ->groupBy('users_id')
            ->get()
            ->map(function ($planilla) {
                return (object)[
                    'users_id' => $planilla->users_id,
                    'name' => $planilla->user->name,
                    'peso_importado' => $planilla->peso_importado
                ];
            });
    }
    private function getPesoPorPlanilleroPorDia()
    {
        $primerDiaMes = now()->startOfMonth();
        $ultimoDiaMes = now()->endOfMonth();

        return Planilla::where('estado', 'pendiente')
            ->whereBetween('created_at', [$primerDiaMes, $ultimoDiaMes]) // Filtrar solo el mes actual
            ->with('user:id,name') // Cargar la relación con los usuarios
            ->select('users_id', DB::raw('DATE(created_at) as fecha'), DB::raw('SUM(peso_total) as peso_importado'))
            ->groupBy('users_id', 'fecha')
            ->orderBy('fecha', 'asc')
            ->get()
            ->map(function ($planilla) {
                return (object) [ // Convertimos en objeto para evitar que sea un array
                    'users_id' => $planilla->users_id,
                    'name' => optional($planilla->user)->name, // Evitar errores si no hay usuario
                    'fecha' => $planilla->fecha,
                    'peso_importado' => $planilla->peso_importado
                ];
            });
    }
}
