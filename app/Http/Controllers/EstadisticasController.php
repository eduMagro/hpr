<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Elemento;
use App\Models\Movimiento;
use App\Models\Maquina;
use App\Models\Producto;
use App\Models\ProductoBase;
use App\Models\Pedido;
use App\Models\Planilla;
use App\Models\Obra;
use App\Models\SalidaPaquete;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EstadisticasController extends Controller
{
    /* ───────────────────────── Helpers ───────────────────────── */

    private function abortIfNoOffice()
    {
        if (Auth::user()->rol !== 'oficina') {
            // 303 para redirección con mensaje flash 
            return redirect()
                ->route('dashboard')
                ->with('abort', 'No tienes los permisos necesarios.');
        }
    }

    /* ───────────────────────── Panel STOCK ───────────────────────── */

    public function stock()
    {
        if ($redir = $this->abortIfNoOffice()) {
            return $redir;
        }

        $datosStock = $this->obtenerDatosStock();

        return view('estadisticas.stock', $datosStock);
    }


    /* ───────────────────────── Panel OBRAS ───────────────────────── */

    public function obras()
    {
        if ($redir = $this->abortIfNoOffice()) {
            return $redir;
        }

        $salidasPaquetes = $this->getSalidasPaquetesCompletadas();
        $pesoPorObra     = $this->agruparPaquetesPorObra($salidasPaquetes);

        return view('estadisticas.obras', compact('pesoPorObra'));
    }

    /* ──────────────────────── Panel PLANILLEROS ───────────────────── */

    public function tecnicosDespiece(Request $request)
    {
        if ($redir = $this->abortIfNoOffice()) {
            return $redir;
        }

        $modo = $request->input('modo', 'dia'); // puede ser: dia, mes, anio, origen

        $pesoPorUsuario       = $this->getPesoPorPlanillero(); // total acumulado
        $pesoAgrupado         = $this->getPesoPorPlanilleroAgrupado($modo);

        return view('estadisticas.tecnicos-despiece', compact(
            'pesoPorUsuario',
            'pesoAgrupado',
            'modo'
        ));
    }


    /* ─────────────────────── Panel CONSUMO MÁQUINAS ───────────────── */

    public function consumoMaquinas(Request $request)
    {
        if ($redir = $this->abortIfNoOffice()) {
            return $redir;
        }

        // Filtros
        $desde = $request->input('desde');   // Formato yyyy-mm-dd o null
        $hasta = $request->input('hasta');
        $modo  = $request->input('modo', 'dia');  // por defecto “día”

        // Datos principales
        $datosConsumo       = $this->consumoKgPorMaquina($desde, $hasta, $modo);
        $labels             = $datosConsumo['labels'];
        $datasets           = $datosConsumo['datasets'];
        $tablaConsumoTotales = $datosConsumo['totales'];
        $kilosPorTipoDiametro = $this->kilosPorMaquinaTipoDiametro($desde, $hasta, $modo);


        return view('estadisticas.consumo-maquinas', compact(
            'labels',
            'datasets',
            'tablaConsumoTotales',
            'kilosPorTipoDiametro',
            'desde',
            'hasta',
            'modo'
        ));
    }

    /* ─────────────────── Ruta de comodín → redirige a Stock ───────── */

    public function index()
    {
        // Por si alguien entra en /estadisticas sin sub-ruta
        return redirect()->route('estadisticas.stock');
    }
    // ---------------------------------------------------------------- Funciones para calcular el stockaje
    private function obtenerDatosStock()
    {
        $diametrosFijos = [8, 10, 12, 16, 20, 25, 32];

        $elementosPendientes = Elemento::with('maquina')
            ->where('estado', 'pendiente')
            ->get()
            ->filter(fn($e) => $e->maquina && $e->maquina->tipo && $e->diametro)
            ->groupBy(fn($e) => $e->maquina->tipo_material . '-' . intval($e->diametro))
            ->map(fn($group) => $group->sum('peso'));

        $necesarioPorDiametro = collect($diametrosFijos)->mapWithKeys(function ($diametro) use ($elementosPendientes) {
            $encarretado = $elementosPendientes["encarretado-$diametro"] ?? 0;
            $barrasPorLongitud = collect([12, 14, 15, 16])->mapWithKeys(fn($l) => [$l => 0]);
            $barrasPorLongitud[12] = $elementosPendientes["barra-$diametro"] ?? 0;
            $barrasTotal = $barrasPorLongitud->sum();
            return [$diametro => [
                'encarretado' => $encarretado,
                'barras' => $barrasPorLongitud,
                'barras_total' => $barrasTotal,
                'total' => $barrasTotal + $encarretado,
            ]];
        });

        $pedidosPendientes = Pedido::with('productos')
            ->where('estado', 'pendiente')
            ->get()
            ->flatMap(fn($pedido) => $pedido->productos->map(fn($p) => [
                'tipo' => $p->tipo,
                'diametro' => $p->diametro,
                'cantidad' => $p->pivot->cantidad,
            ]))
            ->groupBy(fn($i) => "{$i['tipo']}-{$i['diametro']}")
            ->map(fn($g) => collect($g)->sum('cantidad'));

        $pedidosPorDiametro = collect($diametrosFijos)->mapWithKeys(function ($diametro) use ($pedidosPendientes) {
            $encarretado = $pedidosPendientes["encarretado-$diametro"] ?? 0;
            $barrasPorLongitud = collect([12, 14, 15, 16])->mapWithKeys(fn($l) => [$l => 0]);
            $barrasPorLongitud[12] = $pedidosPendientes["barra-$diametro"] ?? 0;
            $barrasTotal = $barrasPorLongitud->sum();
            return [$diametro => [
                'encarretado' => $encarretado,
                'barras' => $barrasPorLongitud,
                'barras_total' => $barrasTotal,
                'total' => $encarretado + $barrasTotal,
            ]];
        });

        $productos = Producto::with('productoBase')->where('estado', 'almacenado')->get();

        $stockPorProductoBase = $productos
            ->whereNotNull('producto_base_id')
            ->groupBy('producto_base_id')
            ->map(fn($grupo) => round($grupo->sum('peso_stock'), 2));

        $stockData = collect($diametrosFijos)->mapWithKeys(function ($diametro) use ($productos) {
            $grupo = $productos->filter(fn($p) => intval($p->productoBase->diametro) === $diametro);
            $encarretado = $grupo->where('productoBase.tipo', 'encarretado')->sum('peso_stock');
            $barras = $grupo->where('productoBase.tipo', 'barra');
            $barrasPorLongitud = $barras->groupBy(fn($p) => $p->productoBase->longitud)->map(fn($g) => $g->sum('peso_stock'));
            $barrasTotal = $barrasPorLongitud->sum();
            return [$diametro => [
                'encarretado' => $encarretado,
                'barras' => $barrasPorLongitud,
                'barras_total' => $barrasTotal,
                'total' => $barrasTotal + $encarretado,
            ]];
        });

        $comparativa = [];

        foreach ($stockData as $diametro => $data) {
            foreach (['barra', 'encarretado'] as $tipo) {
                $clave = "{$tipo}-{$diametro}";
                $pendiente = $elementosPendientes[$clave] ?? 0;
                $pedido = $pedidosPendientes[$clave] ?? 0;
                $disponible = $tipo === 'barra' ? $data['barras_total'] : $data['encarretado'];
                $diferencia = $disponible + $pedido - $pendiente;
                $comparativa[$clave] = compact('tipo', 'diametro', 'pendiente', 'pedido', 'disponible', 'diferencia');
            }
        }

        //------ CONSUMO A PARTIR DE MOVIMIENTOS A MÁQUINAS
        $hoy = now();
        $hace2Semanas = $hoy->copy()->subWeeks(2);
        $hace1Mes = $hoy->copy()->subMonth();
        $hace2Meses = $hoy->copy()->subMonths(2);

        // Función para calcular el consumo entre dos fechas
        $calcularConsumo = function ($desde, $hasta) {
            return Movimiento::whereNotNull('maquina_destino')
                ->whereBetween('fecha_ejecucion', [$desde, $hasta])
                ->join('productos', 'productos.id', '=', 'movimientos.producto_id')
                ->select('productos.producto_base_id', DB::raw('SUM(productos.peso_inicial) as total_consumido'))
                ->groupBy('productos.producto_base_id')
                ->pluck('total_consumido', 'productos.producto_base_id')
                ->map(fn($peso) => round($peso, 2));
        };

        $consumo2Semanas = $calcularConsumo($hace2Semanas, $hoy);
        $consumo1Mes     = $calcularConsumo($hace1Mes, $hoy);
        $consumo2Meses   = $calcularConsumo($hace2Meses, $hoy);

        $consumoManual = Producto::where('estado', 'consumido')
            ->whereNotNull('fecha_consumido')
            ->whereNotNull('producto_base_id')
            ->whereNotNull('peso_inicial')
            ->select('producto_base_id', DB::raw('SUM(peso_inicial) as total_manual'))
            ->groupBy('producto_base_id')
            ->pluck('total_manual', 'producto_base_id')
            ->map(fn($peso) => round($peso, 2));

        $kgPedidosPorProductoBase = DB::table('pedido_productos')
            ->join('productos_base', 'productos_base.id', '=', 'pedido_productos.producto_base_id')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_productos.pedido_id')
            ->where('pedidos.estado', 'pendiente')
            ->whereNotNull('pedido_productos.cantidad')
            ->groupBy('pedido_productos.producto_base_id')
            ->select('pedido_productos.producto_base_id', DB::raw('SUM(pedido_productos.cantidad) as total_pedido'))
            ->pluck('total_pedido', 'pedido_productos.producto_base_id')
            ->map(fn($valor) => round($valor, 2));
        $productosBase = ProductoBase::all(['id', 'tipo', 'diametro', 'longitud'])
            ->keyBy('id')
            ->map(fn($p) => [
                'tipo' => $p->tipo,
                'diametro' => intval($p->diametro),
                'longitud' => $p->tipo === 'barra' ? $p->longitud : null,
            ]);

        $resumenReposicion = collect($productosBase)->mapWithKeys(function ($info, $id) use (
            $consumo1Mes,
            $consumo2Semanas,
            $consumo2Meses,
            $stockPorProductoBase,
            $kgPedidosPorProductoBase
        ) {
            $consumo14d = $consumo2Semanas[$id] ?? 0;
            $consumo30d = $consumo1Mes[$id] ?? 0;
            $consumo60d = $consumo2Meses[$id] ?? 0;

            $stock = $stockPorProductoBase[$id] ?? 0;
            $pedido = $kgPedidosPorProductoBase[$id] ?? 0;

            $consumoReferencia = $consumo30d; // podrías usar un promedio ponderado si prefieres
            $reposicionNecesaria = max($consumoReferencia - $stock - $pedido, 0); // nunca negativa

            return [$id => [
                'tipo' => $info['tipo'],
                'diametro' => $info['diametro'],
                'longitud' => $info['longitud'],
                'consumo_14d' => $consumo14d,
                'consumo_30d' => $consumo30d,
                'consumo_60d' => $consumo60d,
                'stock' => $stock,
                'pedido' => $pedido,
                'reposicion' => round($reposicionNecesaria, 2),
            ]];
        });


        return [
            'stockData' => $stockData,
            'pedidosPorDiametro' => $pedidosPorDiametro,
            'necesarioPorDiametro' => $necesarioPorDiametro,
            'comparativa' => $comparativa,
            'totalGeneral' => $stockData->sum(fn($d) => $d['encarretado']) + $stockData->sum(fn($d) => $d['barras_total']),
            'consumoPorProductoBase' => [
                'ultimas_2_semanas' => $consumo2Semanas->mapWithKeys(fn($valor, $id) => [$id => $valor + ($consumoManual[$id] ?? 0)]),
                'ultimo_mes' => $consumo1Mes->mapWithKeys(fn($valor, $id) => [$id => $valor + ($consumoManual[$id] ?? 0)]),
                'ultimos_2_meses' => $consumo2Meses->mapWithKeys(fn($valor, $id) => [$id => $valor + ($consumoManual[$id] ?? 0)]),
            ],
            'productoBaseInfo' => $productosBase,
            'stockPorProductoBase' => $stockPorProductoBase,
            'kgPedidosPorProductoBase' => $kgPedidosPorProductoBase,
            'resumenReposicion' => $resumenReposicion,
        ];
    }
    // ---------------------------------------------------------------- Función para calcular el stock deseado


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

    // ---------------------------------------------------------------- Estadisticas de planilleros
    private function getPesoPorPlanillero()
    {
        return Planilla::where('estado', 'pendiente')
            ->with('user:id,name,primer_apellido,segundo_apellido')          // ⬅️  cargamos todo lo que necesita el accessor
            ->select('users_id', DB::raw('SUM(peso_total) AS peso_importado'))
            ->groupBy('users_id')
            ->get()
            ->map(function ($planilla) {
                return (object) [
                    'users_id'       => $planilla->users_id,
                    'nombre_completo' => optional($planilla->user)->nombre_completo, // accessor
                    'peso_importado' => $planilla->peso_importado,
                ];
            });
    }

    // private function getPesoPorPlanilleroPorDia()
    // {
    //     $primerDiaMes  = now()->startOfMonth();
    //     $ultimoDiaMes  = now()->endOfMonth();

    //     return Planilla::where('estado', 'pendiente')
    //         ->whereBetween('created_at', [$primerDiaMes, $ultimoDiaMes])
    //         ->with('user:id,name,primer_apellido,segundo_apellido')          // ⬅️  idem
    //         ->select(
    //             'users_id',
    //             DB::raw('DATE(created_at) AS fecha'),
    //             DB::raw('SUM(peso_total) AS peso_importado')
    //         )
    //         ->groupBy('users_id', 'fecha')
    //         ->orderBy('fecha', 'asc')
    //         ->get()
    //         ->map(function ($planilla) {
    //             return (object) [
    //                 'users_id'       => $planilla->users_id,
    //                 'nombre_completo' => optional($planilla->user)->nombre_completo,
    //                 'fecha'          => $planilla->fecha,
    //                 'peso_importado' => $planilla->peso_importado,
    //             ];
    //         });
    // }
    private function getPesoPorPlanilleroAgrupado($modo = 'mes')
    {
        $query = Planilla::where('estado', 'pendiente')
            ->with('user:id,name,primer_apellido,segundo_apellido');

        switch ($modo) {
            case 'mes':
                $campoFecha = DB::raw('DATE(created_at) AS periodo'); // ahora por día
                $filtroInicio = now()->startOfMonth();
                $filtroFin = now()->endOfMonth();
                $query->whereBetween('created_at', [$filtroInicio, $filtroFin]);

                $rangos = collect(CarbonPeriod::create($filtroInicio, '1 day', $filtroFin))
                    ->map(fn($fecha) => $fecha->format('Y-m-d'));

                $groupBy = ['users_id', 'periodo'];
                break;


            case 'anio':
                $campoFecha = DB::raw('DATE_FORMAT(created_at, "%Y-%m") AS periodo');
                $filtroInicio = now()->startOfYear();
                $filtroFin = now()->endOfYear();
                $query->whereBetween('created_at', [$filtroInicio, $filtroFin]);

                $rangos = collect(CarbonPeriod::create($filtroInicio, '1 month', $filtroFin))
                    ->map(fn($fecha) => $fecha->format('Y-m'));

                $groupBy = ['users_id', 'periodo'];
                break;

            case 'origen':
            default:
                $campoFecha = DB::raw('"origen" AS periodo');
                $rangos = collect(['origen']);
                $groupBy = ['users_id'];
                break;
        }

        $datos = $query->select('users_id', $campoFecha, DB::raw('SUM(peso_total) as peso_importado'))
            ->groupBy(...$groupBy)
            ->orderBy('periodo', 'asc')
            ->get();

        $planilleros = $datos->pluck('user')->unique('id')->filter()->values();

        $series = $planilleros->map(function ($usuario) use ($datos, $rangos) {
            $datosUsuario = $datos->where('users_id', $usuario->id)->keyBy('periodo');

            $acumulado = 0;
            $data = $rangos->map(function ($periodo) use (&$acumulado, $datosUsuario) {
                $peso = $datosUsuario[$periodo]->peso_importado ?? 0;
                $acumulado += $peso;
                return $acumulado;
            });

            return [
                'name' => $usuario->nombre_completo,
                'data' => $data->toArray(),
            ];
        });


        return [
            'labels' => $rangos->toArray(),
            'series' => $series->toArray(),
        ];
    }


    // ---------------------------------------------------------------- consumo de materia prima / maquina

    private function consumoKgPorMaquina(?string $desde, ?string $hasta, string $modo): array
    {
        $columnaFecha = match ($modo) {
            'mes'    => "DATE_FORMAT(movimientos.fecha_ejecucion, '%Y-%m')",
            'anio'   => "YEAR(movimientos.fecha_ejecucion)",
            'origen' => "'Total'",
            default  => "DATE(movimientos.fecha_ejecucion)",
        };

        $registros = Movimiento::query()
            ->where('tipo', 'movimiento libre')
            ->whereNotNull('maquina_destino')
            ->whereNotNull('fecha_ejecucion')
            ->when($desde, fn($q) => $q->whereDate('fecha_ejecucion', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha_ejecucion', '<=', $hasta))
            ->join('productos', 'productos.id', '=', 'movimientos.producto_id')
            ->selectRaw("
            movimientos.maquina_destino,
            {$columnaFecha} AS periodo,
            SUM(productos.peso_inicial) AS kg_totales
        ")
            ->groupBy('movimientos.maquina_destino', DB::raw($columnaFecha))
            ->orderBy('periodo')
            ->get();

        $fechas   = $registros->pluck('periodo')->unique()->sort()->values();
        $maquinas = $registros->pluck('maquina_destino')->unique();

        $datasets = $maquinas->map(function ($id) use ($registros, $fechas) {
            $serie = $fechas->map(function ($fecha) use ($registros, $id) {
                return optional(
                    $registros->first(fn($r) => $r->maquina_destino == $id && $r->periodo == $fecha)
                )->kg_totales ?? 0;
            });

            return [
                'label' => \App\Models\Maquina::find($id)?->nombre ?? "Máquina $id",
                'data'  => $serie,
                'fill'  => false,
            ];
        });

        $totales = $registros
            ->groupBy('maquina_destino')
            ->map(function ($grupo, $id) {
                return [
                    'maquina'    => \App\Models\Maquina::find($id)?->nombre ?? "Máquina $id",
                    'kg_totales' => $grupo->sum('kg_totales'),
                ];
            })->values()->all();

        return [
            'labels'   => $fechas->values()->all(),
            'datasets' => $datasets->values()->all(),
            'totales'  => $totales,
        ];
    }

    private function kilosPorMaquinaTipoDiametro(?string $desde, ?string $hasta, string $modo)
    {
        $desdeFecha = $desde ? Carbon::parse($desde) : Carbon::parse(Movimiento::min('fecha_ejecucion'));
        $hastaFecha = $hasta ? Carbon::parse($hasta) : now();

        // Calcular número de periodos según el modo
        $periodos = match ($modo) {
            'anio' => $this->aniosProporcionales($desdeFecha, $hastaFecha),

            'mes' => $this->mesesProporcionales($desdeFecha, $hastaFecha),

            'origen' => 1,
            default => collect(CarbonPeriod::create($desdeFecha, $hastaFecha))
                ->filter(fn($fecha) => $fecha->isWeekday())
                ->count(),
        };


        // Prevenir división por 0 si no hay días laborables
        $periodos = max($periodos, 1);

        return Movimiento::query()
            ->where('movimientos.tipo', 'movimiento libre')
            ->whereNotNull('maquina_destino')
            ->whereNotNull('fecha_ejecucion')
            ->when($desde, fn($q) => $q->whereDate('fecha_ejecucion', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha_ejecucion', '<=', $hasta))
            ->join('productos',       'productos.id',       '=', 'movimientos.producto_id')
            ->join('productos_base',  'productos_base.id',  '=', 'productos.producto_base_id')
            ->leftJoin('maquinas',    'maquinas.id',        '=', 'movimientos.maquina_destino')
            ->selectRaw('
            maquinas.nombre                 AS maquina,
            productos_base.tipo             AS tipo,
            productos_base.diametro         AS diametro,
            productos_base.longitud         AS longitud,
            ROUND(SUM(productos.peso_inicial) / ?, 2) AS kg
        ', [$periodos])
            ->groupBy(
                'maquinas.nombre',
                'productos_base.tipo',
                'productos_base.diametro',
                'productos_base.longitud'
            )
            ->orderBy('maquinas.nombre')
            ->orderBy('productos_base.tipo')
            ->orderBy('productos_base.diametro')
            ->orderBy('productos_base.longitud')
            ->get();
    }

    private function mesesProporcionales(Carbon $desde, Carbon $hasta): float
    {
        if ($desde->greaterThan($hasta)) return 1;

        // Primer mes (incompleto si no empieza el día 1)
        $primerMesDias = $desde->daysUntil($desde->copy()->endOfMonth())->count() + 1;
        $diasEnPrimerMes = $desde->daysInMonth;
        $proporcionPrimerMes = $primerMesDias / $diasEnPrimerMes;

        // Último mes (incompleto si no termina el último día)
        $ultimoMesDias = $hasta->day;
        $diasEnUltimoMes = $hasta->daysInMonth;
        $proporcionUltimoMes = $ultimoMesDias / $diasEnUltimoMes;

        // Si está en el mismo mes:
        if ($desde->format('Y-m') === $hasta->format('Y-m')) {
            return $hasta->diffInDays($desde) > 0
                ? ($hasta->diffInDays($desde) + 1) / $diasEnUltimoMes
                : 1 / $diasEnUltimoMes;
        }

        // Meses completos intermedios
        $mesesCompletos = $desde->copy()->addMonth()->startOfMonth()->diffInMonths($hasta->copy()->startOfMonth());

        return $proporcionPrimerMes + $mesesCompletos + $proporcionUltimoMes;
    }
    private function aniosProporcionales(Carbon $desde, Carbon $hasta): float
    {
        if ($desde->greaterThan($hasta)) return 1;

        $diasTotales = $desde->diffInDays($hasta) + 1;
        $anioReferencia = $desde->year;

        // Comprobar si el año es bisiesto
        $diasEnElAnio = Carbon::create($anioReferencia)->isLeapYear() ? 366 : 365;

        return round($diasTotales / $diasEnElAnio, 4); // mayor precisión
    }
}
