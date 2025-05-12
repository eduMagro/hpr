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

        $stockOptimo = $this->getStockOptimo();

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
            'pesoPorPlanilleroPorDia',
            'stockOptimo'
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
        // Definir manualmente los diámetros que queremos incluir
        $diametrosDefinidos = collect([5, 8, 10, 12, 16, 20, 25, 32]); // Agrega más si es necesario

        // Obtener los pesos totales por diámetro desde la base de datos
        $pesoTotal = Elemento::whereHas('planilla', function ($query) {
            $query->where('estado', 'pendiente');
        })
            ->selectRaw('diametro, SUM(peso) AS peso_total')
            ->groupBy('diametro')
            ->pluck('peso_total', 'diametro'); // Devuelve [diametro => peso]

        // Crear una colección asegurando que todos los diámetros existen, con 0 si no hay datos
        return $diametrosDefinidos->mapWithKeys(function ($diametro) use ($pesoTotal) {
            return [(int) $diametro => (float) ($pesoTotal[$diametro] ?? 0)];
        });
    }


    private function getStockEncarretado()
    {
        $diametrosDefinidos = collect([5, 8, 10, 12, 16, 20, 25, 32]);

        $productos = Producto::with('productoBase')
            ->where('estado', 'almacenado')
            ->whereHas('productoBase', fn($q) => $q->where('tipo', 'encarretado'))
            ->get();

        $agrupado = $productos->groupBy(fn($p) => $p->productoBase->diametro)
            ->map(fn($grupo) => $grupo->sum('peso_stock'));

        return $diametrosDefinidos->map(function ($diametro) use ($agrupado) {
            return (object) [
                'diametro' => (int) $diametro,
                'stock' => (float) ($agrupado[$diametro] ?? 0),
            ];
        });
    }

    private function getStockBarras()
    {
        $diametrosDefinidos = collect([5, 8, 10, 12, 16, 20, 25, 32]);

        // ⚠️ En lugar de usar selectRaw y pluck, usamos ->get() con ->with()
        $productos = Producto::with('productoBase')
            ->where('estado', 'almacenado')
            ->whereHas('productoBase', fn($q) => $q->where('tipo', 'barra'))
            ->get();

        // Mapeamos los productos a objetos con datos requeridos
        return $productos->map(function ($producto) {
            return (object) [
                'productoBase' => $producto->productoBase,
                'stock' => (float) $producto->peso_stock,
            ];
        });
    }

    private function getStockReal()
    {
        // Obtener stock encarratado y barras
        $stockEncarretado = $this->getStockEncarretado()->keyBy('diametro');
        $stockBarras = $this->getStockBarras()->groupBy('diametro');

        // Unir los datos sumando los valores de cada diámetro
        $stockReal = collect();

        foreach ($stockEncarretado as $diametro => $encarretado) {
            $stockReal[$diametro] = $encarretado->stock + ($stockBarras->get($diametro)?->sum('stock') ?? 0);
        }

        return $stockReal;
    }
    private function compararStockConPeso($pesoTotalPorDiametro, $stockEncarretado, $stockBarras)
    {
        $mensajeDeAdvertencia = [];

        foreach ($pesoTotalPorDiametro as $diametro => $pesoTotal) { // ✅ Ahora iteramos con clave => valor
            // Buscar stock por diámetro en las categorías de "encarretado" y "barra"
            $stockEncarretadoPorDiametro = $stockEncarretado->firstWhere('diametro', $diametro);
            $stockBarrasPorDiametro = $stockBarras->where('diametro', $diametro)->sum('stock');

            // Calcular el stock total disponible
            $stockTotalDisponible = ($stockEncarretadoPorDiametro ? $stockEncarretadoPorDiametro->stock : 0) + $stockBarrasPorDiametro;

            // Comparar si el stock disponible es menor que el peso total requerido
            if ($stockTotalDisponible < $pesoTotal) {
                $mensajeDeAdvertencia[] = "🔴 **Advertencia:** El stock disponible para el diámetro {$diametro} es insuficiente. Faltan " . ($pesoTotal - $stockTotalDisponible) . " kg.";
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

    private function getStockOptimo()
    {
        $fechaInicio = Elemento::where('estado', 'completado')->min('created_at');
        $fechaFin = Carbon::now();

        if (!$fechaInicio) {
            return collect();
        }

        $diasTotales = Carbon::parse($fechaInicio)->diffInDays($fechaFin) ?: 1;
        $stockDeSeguridad = 1000;
        $tiempoReposicion = 5;
        $mensajes = [];

        // ✅ Asegurar que el stock real tenga claves correctas
        $stockReal = $this->getStockReal()->mapWithKeys(fn($value, $key) => [(int) $key => $value]);

        // Obtener los kilos necesarios para la próxima semana (planillas con entrega en 7 días)
        $stockSemana = $this->getDatosPorPlanilla()->mapWithKeys(fn($value, $key) => [(int) $key => $value])
            ->where('planilla.estado', 'pendiente')
            ->groupBy('diametro')
            ->map(function ($planillas) {
                return $planillas->sum('peso_por_planilla');
            });

        $stockOptimo = Elemento::where('estado', 'completado')
            ->select('diametro')
            ->selectRaw('SUM(peso) / ? as consumo_promedio', [$diasTotales])
            ->groupBy('diametro')
            ->get()
            ->map(function ($item) use ($stockDeSeguridad, $tiempoReposicion, $stockReal, $stockSemana, &$mensajes) {
                $stockDeseado = (float) $item->consumo_promedio * 14;
                $stockOptimo = (float) max(
                    $stockDeseado,
                    $stockDeSeguridad + ($item->consumo_promedio * $tiempoReposicion)
                );

                // Obtener stock real y stock necesario a una semana
                $stockRealPorDiametro = (float) ($stockReal->get((int) $item->diametro) ?? 0);
                $stockSemanaPorDiametro = (float) ($stockSemana->get($item->diametro) ?? 0);

                // Formatear valores a enteros sin decimales
                $stockDeseadoFmt = number_format($stockDeseado, 0);
                $stockOptimoFmt = number_format($stockOptimo, 0);
                $stockRealFmt = number_format($stockRealPorDiametro, 0);
                $stockSemanaFmt = number_format($stockSemanaPorDiametro, 0);

                // Generar mensajes con valores sin decimales
                if ($stockDeseado > $stockOptimo) {
                    $mensajes[] = "⚠️ **Atención:** La demanda en diámetro {$item->diametro} ha aumentado. Se requieren {$stockDeseadoFmt} kg, superando el Stock Óptimo ({$stockOptimoFmt} kg). Es recomendable revisar la reposición.";
                } elseif ($stockOptimo > $stockDeseado) {
                    $mensajes[] = "✅ **Stock adecuado** para el diámetro {$item->diametro}. Se mantiene por encima del nivel de seguridad, no se requiere reabastecimiento inmediato.";
                }

                if ($stockRealPorDiametro < $stockDeseado) {
                    $mensajes[] = "🔴 **Alerta crítica:** Stock insuficiente para el diámetro {$item->diametro}. Solo hay {$stockRealFmt} kg disponibles, pero se requieren {$stockDeseadoFmt} kg. Se recomienda hacer un pedido de material **de inmediato**.";
                } elseif ($stockRealPorDiametro < $stockOptimo) {
                    $mensajes[] = "🟠 **Advertencia:** El Stock Real ({$stockRealFmt} kg) del diámetro {$item->diametro} está por debajo del Stock Óptimo ({$stockOptimoFmt} kg). Es recomendable programar una reposición pronto.";
                } else {
                    $mensajes[] = "✅ **Stock seguro:** El Stock Real ({$stockRealFmt} kg) del diámetro {$item->diametro} está en un nivel óptimo. No es necesario realizar pedidos por ahora.";
                }

                if ($stockSemanaPorDiametro > 0) {
                    $mensajes[] = "📅 **Próxima demanda:** En los próximos 7 días se necesitarán {$stockSemanaFmt} kg del diámetro {$item->diametro} según las planillas pendientes. Verifica si el stock actual será suficiente.";
                }

                return (object)[
                    'diametro' => (int) $item->diametro,
                    'consumo_promedio' => (int) round($item->consumo_promedio, 0),
                    'stock_optimo' => (int) $stockOptimo,  // 🔹 Convertimos a int
                    'stock_deseado' => (int) $stockDeseado,  // 🔹 Convertimos a int
                    'stock_real' => (int) $stockRealPorDiametro,  // 🔹 Convertimos a int
                    'stock_semana' => (int) $stockSemanaPorDiametro // 🔹 Convertimos a int
                ];
            });

        session()->flash('alertas_stock', $mensajes);

        return $stockOptimo;
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
