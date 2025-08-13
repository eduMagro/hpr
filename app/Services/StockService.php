<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Elemento;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\ProductoBase;
use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Obra;
use Illuminate\Support\Facades\Schema;

class StockService
{
    // 👉 hazlo público para llamarlo desde los controladores
    public function obtenerDatosStock(?array $obraIds = null, ?string $clienteLike = null): array
    {

        // si no vienen obras pero sí patrón de cliente, resolvemos obras por cliente
        if (empty($obraIds) && $clienteLike) {
            $obraIds = $this->getObraIdsByClienteLike($clienteLike);
        }
        // normalizar: si llega string (p.ej. "123") lo convertimos a array [123]
        if (is_string($obraIds) && is_numeric($obraIds)) {
            $obraIds = [(int) $obraIds];
        } elseif ($obraIds !== null && !is_array($obraIds)) {
            // cualquier otra cosa inesperada => ignoramos
            $obraIds = null;
        }
        $stockData            = $this->getStockData($obraIds); // estocaje = global (no depende de obra)
        $pedidosPorDiametro   = $this->getPedidosPorDiametro($obraIds);     // 🔎 filtrado por obra si aplica
        $necesarioPorDiametro = $this->getNecesarioPorDiametro($obraIds);   // 🔎 filtrado por obra si aplica
        $comparativa          = $this->getComparativa($stockData, $pedidosPorDiametro, $necesarioPorDiametro);

        $consumosMensuales = $this->obtenerConsumosMensuales();
        $consumoOrigen     = $this->getConsumoTotalOrigen();
        $resumenReposicion = $this->getResumenReposicion($consumosMensuales['consumos']);
        $ids               = $this->getIds($consumosMensuales['consumos']);
        $recomendacionReposicion = $this->getRecomendacionReposicion($resumenReposicion, $consumosMensuales['consumos']);

        return [
            'stockData'                => $stockData,
            'pedidosPorDiametro'       => $pedidosPorDiametro,
            'necesarioPorDiametro'     => $necesarioPorDiametro,
            'comparativa'              => $comparativa,
            'totalGeneral'             => $stockData->sum(fn($d) => $d['encarretado']) + $stockData->sum(fn($d) => $d['barras_total']),
            'consumoOrigen'            => $consumoOrigen,
            'consumosPorMes'           => $consumosMensuales['consumos'],
            'nombreMeses'              => $consumosMensuales['nombreMeses'],
            'productoBaseInfo'         => $this->getProductoBaseInfo(),
            'stockPorProductoBase'     => $this->getStockPorProductoBase(),
            'kgPedidosPorProductoBase' => $this->getKgPedidosPorProductoBase(),
            'resumenReposicion'        => $resumenReposicion,
            'recomendacionReposicion'  => $recomendacionReposicion,
            'ids'                      => $ids,
            'obraIds_filtradas'        => $obraIds,
            'clienteLike'              => $clienteLike,
        ];
    }

    // 🔧 robusto: detecta columna de nombre en 'clientes' y obtiene obras por LIKE
    private function getObraIdsByClienteLike(string $like): array
    {
        $posiblesCols = ['nombre', 'razon_social', 'cliente', 'name'];
        $colsValidas  = array_values(array_filter($posiblesCols, fn($c) => Schema::hasColumn('clientes', $c)));
        if (empty($colsValidas)) return [];

        $clienteIds = Cliente::query()
            ->where(function ($q) use ($colsValidas, $like) {
                foreach ($colsValidas as $col) {
                    $q->orWhere($col, 'like', $like);
                }
            })
            ->pluck('id');

        if ($clienteIds->isEmpty()) return [];

        return Obra::whereIn('cliente_id', $clienteIds)->pluck('id')->all();
    }

    // ⬇️ añade parámetro $obraIds a estos dos

    // ======================= Helpers existentes =======================
    private function getStockData(?array $obraIds = null)
    {
        $productos = Producto::with('productoBase')
            ->where('estado', 'almacenado')
            ->when($obraIds, fn($q) => $q->whereIn('obra_id', $obraIds))
            ->get()
            ->filter(fn($p) => $p->productoBase);

        $diametrosFijos = [8, 10, 12, 16, 20, 25, 32];

        $res = [];
        foreach ($diametrosFijos as $d) {
            $res[$d] = [
                'encarretado'  => 0.0,
                'barras'       => [12 => 0.0, 14 => 0.0, 15 => 0.0, 16 => 0.0],
                'barras_total' => 0.0,
                'total'        => 0.0,
            ];
        }

        foreach ($productos as $p) {
            $pb = $p->productoBase;
            $d  = (int)$pb->diametro;
            if (!isset($res[$d])) continue;

            if ($pb->tipo === 'encarretado') {
                $res[$d]['encarretado'] += (float)$p->peso_inicial;
            } elseif ($pb->tipo === 'barra') {
                $L = (int)($pb->longitud ?? 12);
                if (!isset($res[$d]['barras'][$L])) $res[$d]['barras'][$L] = 0.0;
                $res[$d]['barras'][$L] += (float)$p->peso_inicial;
            }
        }

        foreach ($res as $d => $x) {
            $res[$d]['barras_total'] = array_sum($x['barras']);
            $res[$d]['total']        = $x['encarretado'] + $res[$d]['barras_total'];
        }

        return collect($res);
    }


    private function getNecesarioPorDiametro(?array $obraIds = null)
    {
        $diametrosFijos = [8, 10, 12, 16, 20, 25, 32];

        $elementos = Elemento::with(['maquina', 'planilla'])
            ->where('estado', 'pendiente')
            ->when($obraIds, fn($q) => $q->whereHas('planilla', fn($p) => $p->whereIn('obra_id', $obraIds)))
            ->get()
            ->filter(fn($e) => $e->maquina && $e->maquina->tipo_material && $e->diametro);

        // Estructura base como arrays
        $res = [];
        foreach ($diametrosFijos as $d) {
            $res[$d] = [
                'encarretado'  => 0.0,
                'barras'       => [12 => 0.0, 14 => 0.0, 15 => 0.0, 16 => 0.0], // sin info de L, quedarán en 0
                'barras_total' => 0.0,
                'total'        => 0.0,
            ];
        }

        foreach ($elementos as $e) {
            $d = (int) $e->diametro;
            if (!isset($res[$d])) continue;

            $peso = (float) ($e->peso ?? 0);
            $tipo = $e->maquina->tipo_material; // 'barra' | 'encarretado'

            if ($tipo === 'encarretado') {
                $res[$d]['encarretado'] += $peso;
            } else {
                // No conocemos longitud base -> sumamos al TOTAL de barras
                $res[$d]['barras_total'] += $peso;
            }
        }

        // Recalcular totales (las claves por L quedan para coherencia visual)
        foreach ($res as $d => $x) {
            $res[$d]['total'] = $res[$d]['encarretado'] + $res[$d]['barras_total'];
        }

        return collect($res);
    }


    private function getPedidosPorDiametro(?array $obraIds = null)
    {
        $diametrosFijos = [8, 10, 12, 16, 20, 25, 32];

        $rows = DB::table('pedido_productos as pp')
            ->join('pedidos as p', 'p.id', '=', 'pp.pedido_id')
            ->join('productos_base as pb', 'pb.id', '=', 'pp.producto_base_id')
            ->when($obraIds, fn($q) => $q->whereIn('p.obra_id', $obraIds))
            ->where('p.estado', 'pendiente')
            ->groupBy('pb.tipo', 'pb.diametro', 'pb.longitud')
            ->select('pb.tipo', 'pb.diametro', 'pb.longitud', DB::raw('SUM(pp.cantidad) as total'))
            ->get();

        // Inicializa como ARRAYS (no Collection)
        $res = [];
        foreach ($diametrosFijos as $d) {
            $res[$d] = [
                'encarretado'  => 0.0,
                'barras'       => [12 => 0.0, 14 => 0.0, 15 => 0.0, 16 => 0.0],
                'barras_total' => 0.0,
                'total'        => 0.0,
            ];
        }

        foreach ($rows as $r) {
            $d = (int)$r->diametro;
            if (!isset($res[$d])) continue;

            if ($r->tipo === 'encarretado') {
                $res[$d]['encarretado'] += (float)$r->total;
            } elseif ($r->tipo === 'barra') {
                $L = (int)($r->longitud ?? 12);
                if (!isset($res[$d]['barras'][$L])) $res[$d]['barras'][$L] = 0.0;
                $res[$d]['barras'][$L] += (float)$r->total;
            }
        }

        // Totales
        foreach ($res as $d => $x) {
            $res[$d]['barras_total'] = array_sum($x['barras']);
            $res[$d]['total']        = $x['encarretado'] + $res[$d]['barras_total'];
        }

        // Devuelve como Collection si lo prefieres:
        return collect($res);
    }

    private function getComparativa($stockData, $pedidosPendientes, $necesarioPorDiametro)
    {
        $comparativa = [];

        foreach ($stockData as $diametro => $data) {

            // ---- global por tipo (como ya tienes) ----
            foreach (['barra', 'encarretado'] as $tipo) {
                $pendiente = $tipo === 'barra'
                    ? $necesarioPorDiametro[$diametro]['barras_total']
                    : $necesarioPorDiametro[$diametro]['encarretado'];

                $pedido = $tipo === 'barra'
                    ? $pedidosPendientes[$diametro]['barras_total']
                    : $pedidosPendientes[$diametro]['encarretado'];

                $disponible = $tipo === 'barra'
                    ? $data['barras_total']
                    : $data['encarretado'];

                $diferencia = $disponible + $pedido - $pendiente;

                $comparativa["{$tipo}-{$diametro}"] = compact('tipo', 'diametro', 'pendiente', 'pedido', 'disponible', 'diferencia');
            }

            // ---- opcional: detalle por longitud en barras ----
            foreach ($data['barras'] as $L => $dispL) {
                $pendL  = $necesarioPorDiametro[$diametro]['barras'][$L] ?? 0;
                $pedL   = $pedidosPendientes[$diametro]['barras'][$L] ?? 0;
                $difL   = $dispL + $pedL - $pendL;

                $comparativa["barra-{$diametro}-{$L}"] = [
                    'tipo'       => 'barra',
                    'diametro'   => $diametro,
                    'longitud'   => (int)$L,
                    'pendiente'  => $pendL,
                    'pedido'     => $pedL,
                    'disponible' => $dispL,
                    'diferencia' => $difL,
                ];
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
