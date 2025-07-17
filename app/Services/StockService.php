<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Elemento;
use App\Models\Pedido;
use App\Models\ProductoBase;
use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StockService
{
    // 👉 hazlo público para llamarlo desde los controladores
    public function obtenerDatosStock(): array
    {
        $stockData            = $this->getStockData();
        $pedidosPorDiametro   = $this->getPedidosPorDiametro();
        $necesarioPorDiametro = $this->getNecesarioPorDiametro();
        $comparativa          = $this->getComparativa($stockData, $pedidosPorDiametro, $necesarioPorDiametro);

        // 👉 Obtenemos consumos mensuales unificados
        $consumosMensuales = $this->obtenerConsumosMensuales();
        $consumoOrigen = $this->getConsumoTotalOrigen();

        // ✅ Usamos el array correcto de consumos
        $resumenReposicion = $this->getResumenReposicion($consumosMensuales['consumos']);
        $ids               = $this->getIds($consumosMensuales['consumos']);

        $resumenReposicion = $this->getResumenReposicion($consumosMensuales['consumos']);
        $recomendacionReposicion = $this->getRecomendacionReposicion($resumenReposicion, $consumosMensuales['consumos']);

        return [
            'stockData'              => $stockData,
            'pedidosPorDiametro'     => $pedidosPorDiametro,
            'necesarioPorDiametro'   => $necesarioPorDiametro,
            'comparativa'            => $comparativa,
            'totalGeneral'           => $stockData->sum(fn($d) => $d['encarretado']) + $stockData->sum(fn($d) => $d['barras_total']),
            'consumoOrigen' => $consumoOrigen,
            'consumosPorMes'         => $consumosMensuales['consumos'],
            'nombreMeses'            => $consumosMensuales['nombreMeses'],
            'productoBaseInfo'       => $this->getProductoBaseInfo(),
            'stockPorProductoBase'   => $this->getStockPorProductoBase(),
            'kgPedidosPorProductoBase' => $this->getKgPedidosPorProductoBase(),
            'resumenReposicion'      => $resumenReposicion,
            'recomendacionReposicion' => $recomendacionReposicion,
            'ids'                    => $ids,
        ];
    }


    // ======================= Helpers existentes =======================
    private function getStockData()
    {
        $productos = Producto::with('productoBase')
            ->where('estado', 'almacenado')
            ->get();

        $diametrosFijos = [8, 10, 12, 16, 20, 25, 32];

        return collect($diametrosFijos)->mapWithKeys(function ($diametro) use ($productos) {
            $grupo = $productos->filter(fn($p) => intval($p->productoBase->diametro) === $diametro);
            $encarretado = $grupo->where('productoBase.tipo', 'encarretado')->sum('peso_inicial');
            $barras = $grupo->where('productoBase.tipo', 'barra');
            $barrasPorLongitud = $barras->groupBy(fn($p) => $p->productoBase->longitud)
                ->map(fn($g) => $g->sum('peso_inicial'));
            $barrasTotal = $barrasPorLongitud->sum();
            return [$diametro => [
                'encarretado' => $encarretado,
                'barras' => $barrasPorLongitud,
                'barras_total' => $barrasTotal,
                'total' => $barrasTotal + $encarretado,
            ]];
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
                $pendiente = ($tipo === 'barra') ? $necesarioPorDiametro[$diametro]['barras_total'] : $necesarioPorDiametro[$diametro]['encarretado'];
                $pedido = ($tipo === 'barra') ? $pedidosPendientes[$diametro]['barras_total'] : $pedidosPendientes[$diametro]['encarretado'];
                $disponible = $tipo === 'barra' ? $data['barras_total'] : $data['encarretado'];
                $diferencia = $disponible + $pedido - $pendiente;
                $comparativa["{$tipo}-{$diametro}"] = compact('tipo', 'diametro', 'pendiente', 'pedido', 'disponible', 'diferencia');
            }
        }
        return $comparativa;
    }

    // ======================= Nuevos helpers =======================



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
    //Este no lo usamos ya, pendiente de quitar
    private function getResumenReposicion($consumosPorMes)
    {
        $stockPorProductoBase     = $this->getStockPorProductoBase();
        $kgPedidosPorProductoBase = $this->getKgPedidosPorProductoBase();
        $productosBase            = $this->getProductoBaseInfo();

        // Unimos todas las claves de los tres meses
        $idsParaResumen = collect($consumosPorMes['mes_hace_dos'])->keys()
            ->merge(collect($consumosPorMes['mes_anterior'])->keys())
            ->merge(collect($consumosPorMes['mes_actual'])->keys())
            ->unique()
            ->values(); // opcional: reindexa


        // Construimos la colección
        $coleccion = $idsParaResumen->map(function ($id) use (
            $productosBase,
            $consumosPorMes,
            $stockPorProductoBase,
            $kgPedidosPorProductoBase,
        ) {
            $info = $productosBase[$id] ?? ['tipo' => 'desconocido', 'diametro' => 0, 'longitud' => null];

            $consumoMesH2 = $consumosPorMes['mes_hace_dos'][$id] ?? 0;
            $consumoMesAnt = $consumosPorMes['mes_anterior'][$id] ?? 0;
            $consumoMesAct = $consumosPorMes['mes_actual'][$id] ?? 0;

            $stock  = $stockPorProductoBase[$id] ?? 0;
            $pedido = $kgPedidosPorProductoBase[$id] ?? 0;

            // referencia: consumo mes anterior
            $reposicionNecesaria = max($consumoMesAnt - $stock - $pedido, 0);

            return [
                'id'             => $id,
                'tipo'           => $info['tipo'],
                'diametro'       => $info['diametro'],
                'longitud'       => $info['longitud'],
                'consumo_hace2'  => $consumoMesH2,
                'consumo_ant'    => $consumoMesAnt,
                'consumo_actual' => $consumoMesAct,
                'stock'          => $stock,
                'pedido'         => $pedido,
                'reposicion'     => round($reposicionNecesaria, 2),
            ];
        });

        // Ordenamos por tipo, luego diámetro, luego longitud
        $ordenada = $coleccion
            ->sortBy(fn($item) => $item['longitud'] ?? 0)
            ->sortBy(fn($item) => $item['diametro'])
            ->sortBy(fn($item) => $item['tipo']);

        // Devolvemos como array asociativo (conservar id como clave si quieres)
        return $ordenada->mapWithKeys(fn($item) => [$item['id'] => $item]);
    }
    private function getRecomendacionReposicion($resumenReposicion, $consumosPorMes): array
    {
        return collect($resumenReposicion)->map(function ($item, $id) use ($consumosPorMes) {
            $consumoMayo  = $consumosPorMes['mes_hace_dos'][$id] ?? 0;
            $consumoJunio = $consumosPorMes['mes_anterior'][$id] ?? 0;
            $consumoJulio = $consumosPorMes['mes_actual'][$id] ?? 0;

            // 📊 Tendencia ponderada: mayo 20%, junio 30%, julio 50%
            $tendencia = ($consumoMayo * 0.2) + ($consumoJunio * 0.3) + ($consumoJulio * 0.5);

            // 🎯 Stock objetivo: cubrir 2 meses de consumo
            $stockObjetivo = $tendencia * 2;

            $stockActual = $item['stock'];
            $pedido = $item['pedido'];
            $reponer = $stockObjetivo - $stockActual - $pedido;

            return [
                'id'            => $id,
                'tipo'          => $item['tipo'],
                'diametro'      => $item['diametro'],
                'longitud'      => $item['longitud'],
                'tendencia'     => round($tendencia, 2),
                'stock_objetivo' => round($stockObjetivo, 2),
                'stock_actual'  => $stockActual,
                'pedido'        => $pedido,
                'reponer'       => round($reponer, 2),
            ];
        })
            // ❌ quitamos el filter para no excluir nada
            ->values()
            ->toArray();
    }


    public function obtenerConsumosMensuales(): array
    {
        $hoy = now();

        // Fechas de corte
        $inicioHaceDosMeses = $hoy->copy()->subMonthsNoOverflow(2)->startOfMonth();
        $finHaceDosMeses    = $inicioHaceDosMeses->copy()->endOfMonth();

        $inicioMesAnterior  = $hoy->copy()->subMonthNoOverflow()->startOfMonth();
        $finMesAnterior     = $inicioMesAnterior->copy()->endOfMonth();

        $inicioMesActual    = $hoy->copy()->startOfMonth();
        $finHoy             = $hoy;

        // Calcular consumos unificados
        $consumoHaceDosMeses = $this->getConsumosUnificados($inicioHaceDosMeses, $finHaceDosMeses);
        $consumoMesAnterior  = $this->getConsumosUnificados($inicioMesAnterior, $finMesAnterior);
        $consumoMesActual    = $this->getConsumosUnificados($inicioMesActual, $finHoy);

        return [
            'nombreMeses' => [
                'haceDosMeses' => ucfirst($inicioHaceDosMeses->locale('es')->monthName),
                'mesAnterior'  => ucfirst($inicioMesAnterior->locale('es')->monthName),
                'mesActual'    => ucfirst($hoy->locale('es')->monthName),
            ],
            'consumos' => [
                'mes_hace_dos' => $consumoHaceDosMeses,
                'mes_anterior' => $consumoMesAnterior,
                'mes_actual'   => $consumoMesActual,
            ],
        ];
    }

    /**
     * 👉 Método unificado: consumos por movimientos y consumos manuales.
     */
    private function getConsumosUnificados(Carbon $desde, Carbon $hasta): array
    {
        // ✅ Movimientos (libres con máquina destino)
        $movimientos = Movimiento::where('tipo', 'movimiento libre')
            ->whereNotNull('maquina_destino')
            ->whereBetween('fecha_ejecucion', [$desde, $hasta])
            ->join('productos', 'productos.id', '=', 'movimientos.producto_id')
            ->select('productos.producto_base_id', DB::raw('SUM(productos.peso_inicial) as total'))
            ->groupBy('productos.producto_base_id')
            ->pluck('total', 'productos.producto_base_id');

        // ✅ Manuales (productos consumidos directamente)
        $manuales = Producto::where('estado', 'consumido')
            ->whereNotNull('producto_base_id')
            ->whereBetween('fecha_consumido', [$desde, $hasta])
            ->select('producto_base_id', DB::raw('SUM(peso_inicial) as total'))
            ->groupBy('producto_base_id')
            ->pluck('total', 'producto_base_id');

        // ✅ Combinar resultados
        $ids = $movimientos->keys()->merge($manuales->keys())->unique();

        return $ids->mapWithKeys(function ($id) use ($movimientos, $manuales) {
            $mov = $movimientos[$id] ?? 0;
            $man = $manuales[$id] ?? 0;
            return [$id => round($mov + $man, 2)];
        })->toArray();
    }
    private function getConsumoTotalOrigen(): array
    {
        // obtenemos la fecha más antigua de movimientos o manuales
        $fechaInicioMov = Movimiento::min('fecha_ejecucion');
        $fechaInicioMan = Producto::where('estado', 'consumido')->min('fecha_consumido');

        $fechaDesde = collect([$fechaInicioMov, $fechaInicioMan])->filter()->min();
        if (!$fechaDesde) {
            return [];
        }

        $fechaDesde = Carbon::parse($fechaDesde);
        $fechaHasta = now();

        // cantidad de meses completos transcurridos
        $meses = $fechaDesde->diffInMonths($fechaHasta) + 1;

        // obtenemos totales de movimientos
        $movimientos = Movimiento::where('tipo', 'movimiento libre')
            ->whereNotNull('maquina_destino')
            ->whereBetween('fecha_ejecucion', [$fechaDesde, $fechaHasta])
            ->join('productos', 'productos.id', '=', 'movimientos.producto_id')
            ->select('productos.producto_base_id', DB::raw('SUM(productos.peso_inicial) as total'))
            ->groupBy('productos.producto_base_id')
            ->pluck('total', 'productos.producto_base_id');

        // obtenemos totales de consumidos manuales
        $manuales = Producto::where('estado', 'consumido')
            ->whereNotNull('producto_base_id')
            ->whereBetween('fecha_consumido', [$fechaDesde, $fechaHasta])
            ->select('producto_base_id', DB::raw('SUM(peso_inicial) as total'))
            ->groupBy('producto_base_id')
            ->pluck('total', 'producto_base_id');

        $ids = $movimientos->keys()->merge($manuales->keys())->unique();

        return $ids->mapWithKeys(function ($id) use ($movimientos, $manuales, $meses) {
            $total = ($movimientos[$id] ?? 0) + ($manuales[$id] ?? 0);
            $mediaMensual = $meses > 0 ? round($total / $meses, 2) : 0;
            return [
                $id => [
                    'total_origen' => round($total, 2),
                    'media_mensual' => $mediaMensual,
                ],
            ];
        })->toArray();
    }


    private function getIds($consumosPorMes)
    {
        return collect($consumosPorMes['mes_hace_dos'])->keys()
            ->merge(collect($consumosPorMes['mes_anterior'])->keys())
            ->merge(collect($consumosPorMes['mes_actual'])->keys())
            ->unique()
            ->sort()
            ->values(); // opcional: para reindexar
    }
}
