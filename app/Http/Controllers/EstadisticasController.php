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
    // ---------------------------------------------------------------- Función para calcular datos de stock, pedidos, necesario y consumos
    // 👉 Este será tu método principal que llama a los otros
    private function obtenerDatosStock()
    {
        $stockData            = $this->getStockData();
        $pedidosPorDiametro   = $this->getPedidosPorDiametro();
        $necesarioPorDiametro = $this->getNecesarioPorDiametro();
        $comparativa          = $this->getComparativa($stockData, $pedidosPorDiametro, $necesarioPorDiametro);

        // 👉 Usa consumos unificados
        $consumos             = $this->getConsumosUnificadosPorRango();

        $resumenReposicion    = $this->getResumenReposicion($consumos);
        $ids                  = $this->getIds($consumos);

        return [
            'stockData'              => $stockData,
            'pedidosPorDiametro'     => $pedidosPorDiametro,
            'necesarioPorDiametro'   => $necesarioPorDiametro,
            'comparativa'            => $comparativa,
            'totalGeneral'           => $stockData->sum(fn($d) => $d['encarretado']) + $stockData->sum(fn($d) => $d['barras_total']),
            'consumoPorProductoBase' => $consumos,
            'productoBaseInfo'       => $this->getProductoBaseInfo(),
            'stockPorProductoBase'   => $this->getStockPorProductoBase(),
            'kgPedidosPorProductoBase' => $this->getKgPedidosPorProductoBase(),
            'resumenReposicion'      => $resumenReposicion,
            'ids'                    => $ids,
        ];
    }
    private function getStockData()
    {
        $productos = Producto::with('productoBase:id,tipo,diametro,longitud')
            ->where('estado', 'almacenado')
            ->get();

        // Agrupamos por producto_base_id
        return $productos
            ->groupBy('producto_base_id')
            ->map(function ($grupo) {
                $base = $grupo->first()->productoBase;
                $encarretado = $base->tipo === 'encarretado'
                    ? $grupo->sum('peso_inicial')
                    : 0;

                // Si es barra, agrupar por longitud
                $barrasPorLongitud = collect();
                if ($base->tipo === 'barra') {
                    $barrasPorLongitud = $grupo->groupBy(fn($p) => $p->productoBase->longitud)
                        ->map(fn($g) => $g->sum('peso_inicial'));
                }

                return [
                    'producto_base_id' => $base->id,
                    'tipo' => $base->tipo,
                    'diametro' => $base->diametro,
                    'longitudes' => $barrasPorLongitud,
                    'barras_total' => $barrasPorLongitud->sum(),
                    'encarretado' => $encarretado,
                    'total' => $barrasPorLongitud->sum() + $encarretado,
                ];
            });
    }
    private function getNecesarioPorDiametro()
    {
        $diametrosFijos = [8, 10, 12, 16, 20, 25, 32];

        $elementosPendientes = Elemento::with('maquina')
            ->where('estado', 'pendiente')
            ->get()
            ->filter(fn($e) => $e->maquina && $e->maquina->tipo && $e->diametro)
            ->groupBy(fn($e) => $e->maquina->tipo_material . '-' . intval($e->diametro))
            ->map(fn($group) => $group->sum('peso'));

        return collect($diametrosFijos)->mapWithKeys(function ($diametro) use ($elementosPendientes) {
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
    }
    private function getPedidosPorDiametro()
    {
        $diametrosFijos = [8, 10, 12, 16, 20, 25, 32];

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

        return collect($diametrosFijos)->mapWithKeys(function ($diametro) use ($pedidosPendientes) {
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
    }
    private function getComparativa($stockData, $pedidosPendientes, $necesarioPorDiametro)
    {
        $comparativa = [];
        foreach ($stockData as $diametro => $data) {
            foreach (['barra', 'encarretado'] as $tipo) {
                $clave = "{$tipo}-{$diametro}";
                $pendiente = ($tipo === 'barra') ? $necesarioPorDiametro[$diametro]['barras_total'] : $necesarioPorDiametro[$diametro]['encarretado'];
                $pedido = ($tipo === 'barra') ? $pedidosPendientes[$diametro]['barras_total'] : $pedidosPendientes[$diametro]['encarretado'];
                $disponible = $tipo === 'barra' ? $data['barras_total'] : $data['encarretado'];
                $diferencia = $disponible + $pedido - $pendiente;
                $comparativa[$clave] = compact('tipo', 'diametro', 'pendiente', 'pedido', 'disponible', 'diferencia');
            }
        }
        return $comparativa;
    }
    private function getConsumosUnificados(Carbon $desde, Carbon $hasta)
    {
        // Movimientos
        $movimientos = Movimiento::whereNotNull('maquina_destino')
            ->whereBetween('fecha_ejecucion', [$desde, $hasta])
            ->join('productos', 'productos.id', '=', 'movimientos.producto_id')
            ->select('productos.producto_base_id', DB::raw('SUM(productos.peso_inicial) as total'))
            ->groupBy('productos.producto_base_id')
            ->pluck('total', 'productos.producto_base_id');

        // Manuales
        $manuales = Producto::where('estado', 'consumido')
            ->whereBetween('fecha_consumido', [$desde, $hasta])
            ->whereNotNull('producto_base_id')
            ->select('producto_base_id', DB::raw('SUM(peso_inicial) as total'))
            ->groupBy('producto_base_id')
            ->pluck('total', 'producto_base_id');

        // Combinar
        $ids = $movimientos->keys()->merge($manuales->keys())->unique();
        return $ids->mapWithKeys(function ($id) use ($movimientos, $manuales) {
            $mov = $movimientos[$id] ?? 0;
            $man = $manuales[$id] ?? 0;
            return [$id => round($mov + $man, 2)];
        });
    }
    private function getConsumosUnificadosPorRango()
    {
        $hoy = now();
        return [
            'ultimas_2_semanas' => $this->getConsumosUnificados($hoy->copy()->subWeeks(2), $hoy),
            'ultimo_mes'        => $this->getConsumosUnificados($hoy->copy()->subMonth(), $hoy),
            'ultimos_2_meses'   => $this->getConsumosUnificados($hoy->copy()->subMonths(2), $hoy),
        ];
    }
    private function getResumenReposicion($consumos)
    {
        $stockPorProductoBase   = $this->getStockPorProductoBase();
        $kgPedidosPorProductoBase = $this->getKgPedidosPorProductoBase();
        $productosBase          = $this->getProductoBaseInfo();

        $idsParaResumen = collect($consumos['ultimo_mes'])->keys()
            ->merge($consumos['ultimas_2_semanas']->keys())
            ->merge($consumos['ultimos_2_meses']->keys())
            ->unique();

        return $idsParaResumen->mapWithKeys(function ($id) use (
            $productosBase,
            $consumos,
            $stockPorProductoBase,
            $kgPedidosPorProductoBase,
        ) {
            $info = $productosBase[$id] ?? ['tipo' => 'desconocido', 'diametro' => 0, 'longitud' => null];
            $consumo14d = $consumos['ultimas_2_semanas'][$id] ?? 0;
            $consumo30d = $consumos['ultimo_mes'][$id] ?? 0;
            $consumo60d = $consumos['ultimos_2_meses'][$id] ?? 0;
            $stock = $stockPorProductoBase[$id] ?? 0;
            $pedido = $kgPedidosPorProductoBase[$id] ?? 0;
            $reposicionNecesaria = max($consumo30d - $stock - $pedido, 0);

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
    }
    private function getProductoBaseInfo()
    {
        return ProductoBase::all(['id', 'tipo', 'diametro', 'longitud'])
            ->keyBy('id')
            ->map(fn($p) => [
                'tipo' => $p->tipo,
                'diametro' => intval($p->diametro),
                'longitud' => $p->tipo === 'barra' ? $p->longitud : null,
            ]);
    }
    private function getStockPorProductoBase()
    {
        return Producto::where('estado', 'almacenado')
            ->whereNotNull('producto_base_id')
            ->groupBy('producto_base_id')
            ->select('producto_base_id', DB::raw('SUM(peso_inicial) as total'))
            ->pluck('total', 'producto_base_id')
            ->map(fn($p) => round($p, 2));
    }
    private function getKgPedidosPorProductoBase()
    {
        return DB::table('pedido_productos')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_productos.pedido_id')
            ->where('pedidos.estado', 'pendiente')
            ->groupBy('pedido_productos.producto_base_id')
            ->select('pedido_productos.producto_base_id', DB::raw('SUM(pedido_productos.cantidad) as total_pedido'))
            ->pluck('total_pedido', 'pedido_productos.producto_base_id')
            ->map(fn($p) => round($p, 2));
    }
    private function getIds($consumos)
    {
        return collect($consumos['ultimas_2_semanas'])
            ->keys()
            ->merge($consumos['ultimo_mes']->keys())
            ->merge($consumos['ultimos_2_meses']->keys())
            ->unique()
            ->sort();
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
