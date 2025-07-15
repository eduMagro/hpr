<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Elemento;
use App\Models\Pedido;
use App\Models\ProductoBase;
use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;

class StockService
{
    // 👉 hazlo público para llamarlo desde los controladores
    public function obtenerDatosStock(): array
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

    private function getConsumosUnificadosPorRango(): array
    {
        $hoy = now();
        return [
            'ultimas_2_semanas' => $this->getConsumosUnificados($hoy->copy()->subWeeks(2), $hoy),
            'ultimo_mes'        => $this->getConsumosUnificados($hoy->copy()->subMonth(), $hoy),
            'ultimos_2_meses'   => $this->getConsumosUnificados($hoy->copy()->subMonths(2), $hoy),
        ];
    }

    private function getConsumosUnificados($desde, $hasta)
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
            $kgPedidosPorProductoBase
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

    private function getIds($consumos)
    {
        return collect($consumos['ultimas_2_semanas'])
            ->keys()
            ->merge($consumos['ultimo_mes']->keys())
            ->merge($consumos['ultimos_2_meses']->keys())
            ->unique()
            ->sort();
    }
}
