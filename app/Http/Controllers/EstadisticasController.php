<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Elemento;
use App\Models\Movimiento;
use App\Models\Maquina;
use App\Models\Producto;
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

        //------ STOCK DESEADO
        $fechaInicioConsumo = Producto::whereNotNull('fecha_consumido')->min('fecha_consumido');
        $fechaFin = now();
        $mesesTranscurridos = max(Carbon::parse($fechaInicioConsumo)->diffInMonths($fechaFin), 1);

        $consumoHistorico = Producto::with('productoBase')
            ->whereNotNull('fecha_consumido')
            ->whereNotNull('peso_inicial') // ✅ Asegura que el peso no es null
            ->where('peso_inicial', '>', 0) // ✅ Asegura que el peso es positivo
            ->get()
            ->filter(
                fn($p) =>
                $p->productoBase
                    && $p->productoBase->tipo
                    && $p->productoBase->diametro
            )
            ->groupBy(function ($p) {
                $tipo = $p->productoBase->tipo;
                $diametro = intval($p->productoBase->diametro);
                $longitud = $p->productoBase->longitud ?? 12;
                return "$tipo-$diametro-$longitud";
            })
            ->map(fn($grupo) => round($grupo->sum('peso_inicial') / $mesesTranscurridos, 2));
        // dd($consumoHistorico->toArray());


        return [
            'stockData' => $stockData,
            'pedidosPorDiametro' => $pedidosPorDiametro,
            'necesarioPorDiametro' => $necesarioPorDiametro,
            'comparativa' => $comparativa,
            'totalGeneral' => $stockData->sum(fn($d) => $d['encarretado']) + $stockData->sum(fn($d) => $d['barras_total']),
            'consumoMensualPromedio' => $consumoHistorico,
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
    private function getPesoPorPlanilleroAgrupado($modo = 'dia')
    {
        $query = Planilla::where('estado', 'pendiente')
            ->with('user:id,name,primer_apellido,segundo_apellido');

        // Definimos el campo de agrupación y el filtro de fechas si aplica
        switch ($modo) {
            case 'dia':
                $campoFecha = DB::raw('DATE(created_at) AS periodo');
                $filtroInicio = now()->startOfMonth();
                $filtroFin = now()->endOfMonth();
                $query->whereBetween('created_at', [$filtroInicio, $filtroFin]);
                $groupBy = ['users_id', 'periodo'];
                break;

            case 'mes':
                $campoFecha = DB::raw('DATE_FORMAT(created_at, "%Y-%m") AS periodo');
                $filtroInicio = now()->startOfYear();
                $filtroFin = now()->endOfYear();
                $query->whereBetween('created_at', [$filtroInicio, $filtroFin]);
                $groupBy = ['users_id', 'periodo'];
                break;

            case 'anio':
                $campoFecha = DB::raw('YEAR(created_at) AS periodo');
                $query->whereYear('created_at', '>=', now()->year - 2); // opcional: últimos 3 años
                $groupBy = ['users_id', 'periodo'];
                break;

            case 'origen':
            default:
                $campoFecha = DB::raw('"origen" AS periodo');
                $groupBy = ['users_id'];
                break;
        }

        // Seleccionamos los datos agrupados
        $resultados = $query
            ->select(
                'users_id',
                $campoFecha,
                DB::raw('SUM(peso_total) AS peso_importado')
            )
            ->groupBy(...$groupBy)
            ->orderBy('periodo', 'asc')
            ->get()
            ->map(function ($planilla) {
                return (object) [
                    'users_id'        => $planilla->users_id,
                    'nombre_completo' => optional($planilla->user)->nombre_completo,
                    'periodo'         => $planilla->periodo,
                    'peso_importado'  => $planilla->peso_importado,
                ];
            });

        return $resultados;
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
