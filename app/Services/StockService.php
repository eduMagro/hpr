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

        $configuracionVistaStock = $this->obtenerConfiguracionVistaStock($obraIds);
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
            'configuracion_vista_stock' => $configuracionVistaStock,
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

    /**
     * Determina si las obras filtradas corresponden a la nave "Almacén".
     */
    private function esNaveAlmacen(?array $idsObrasFiltradas): bool
    {
        if (empty($idsObrasFiltradas)) return false;

        // intenta con 'nombre' (con y sin tilde), y como respaldo otras columnas comunes
        return Obra::whereIn('id', $idsObrasFiltradas)
            ->where(function ($q) {
                $q->whereRaw('LOWER(obra) = ?', ['almacén'])
                    ->orWhereRaw('LOWER(obra) = ?', ['almacen']);
            })
            ->exists();
    }

    /**
     * Devuelve la configuración de la vista de stock según la nave.
     */
    private function obtenerConfiguracionVistaStock(?array $idsObrasFiltradas): array
    {
        $esAlmacen = $this->esNaveAlmacen($idsObrasFiltradas);

        if ($esAlmacen) {
            return [
                'es_nave_almacen'          => true,
                'diametros_considerados'   => [6, 8, 10, 12, 16, 20, 25, 32],
                'longitudes_barras'        => [12, 6],     // solo barras de 12 m y 6 m
                'incluir_encarretado'      => false,       // se ignora encarrete
            ];
        }

        return [
            'es_nave_almacen'          => false,
            'diametros_considerados'   => [8, 10, 12, 16, 20, 25, 32],
            'longitudes_barras'        => [12, 14, 15, 16],
            'incluir_encarretado'      => true,
        ];
    }

    // ⬇️ añade parámetro $obraIds a estos dos

    // ======================= Helpers existentes =======================
    private function getStockData(?array $idsObrasFiltradas = null)
    {
        $configuracionVistaStock = $this->obtenerConfiguracionVistaStock($idsObrasFiltradas);

        $productosAlmacenados = Producto::with('productoBase')
            ->where('estado', 'almacenado')
            ->when($idsObrasFiltradas, fn($q) => $q->whereIn('obra_id', $idsObrasFiltradas))
            ->get()
            ->filter(fn($producto) => $producto->productoBase);

        // Inicializamos la estructura de resultados
        $resultadoStock = [];
        foreach ($configuracionVistaStock['diametros_considerados'] as $diametro) {
            $resultadoStock[$diametro] = [
                'encarretado'   => 0.0,
                'barras'        => collect($configuracionVistaStock['longitudes_barras'])
                    ->mapWithKeys(fn($longitud) => [(int)$longitud => 0.0])
                    ->all(),
                'barras_total'  => 0.0,
                'total'         => 0.0,
            ];
        }

        // Recorremos productos
        foreach ($productosAlmacenados as $producto) {
            $productoBase = $producto->productoBase;
            if (!$productoBase) continue;

            $diametro = (int) $productoBase->diametro;
            if (!array_key_exists($diametro, $resultadoStock)) continue;

            if ($productoBase->tipo === 'encarretado') {
                if ($configuracionVistaStock['incluir_encarretado']) {
                    $resultadoStock[$diametro]['encarretado'] += (float) $producto->peso_inicial;
                }
            } elseif ($productoBase->tipo === 'barra') {
                $longitudBarra = (int) ($productoBase->longitud ?? 12);

                // En Almacén solo admitimos 12 m y 6 m
                if (!in_array($longitudBarra, $configuracionVistaStock['longitudes_barras'], true)) {
                    continue;
                }

                if (!isset($resultadoStock[$diametro]['barras'][$longitudBarra])) {
                    $resultadoStock[$diametro]['barras'][$longitudBarra] = 0.0;
                }
                $resultadoStock[$diametro]['barras'][$longitudBarra] += (float) $producto->peso_inicial;
            }
        }

        // Calcular totales
        foreach ($resultadoStock as $diametro => $datos) {
            $resultadoStock[$diametro]['barras_total'] = array_sum($datos['barras']);
            $resultadoStock[$diametro]['total'] = $datos['encarretado'] + $resultadoStock[$diametro]['barras_total'];
        }

        return collect($resultadoStock);
    }


    private function getNecesarioPorDiametro(?array $idsObrasFiltradas = null)
    {
        $configuracionVistaStock = $this->obtenerConfiguracionVistaStock($idsObrasFiltradas);

        $elementosPendientes = Elemento::with(['maquina', 'planilla'])
            ->where('estado', 'pendiente')
            ->when($idsObrasFiltradas, fn($q) => $q->whereHas('planilla', fn($p) => $p->whereIn('obra_id', $idsObrasFiltradas)))
            ->get()
            ->filter(fn($elemento) => $elemento->maquina && $elemento->maquina->tipo_material && $elemento->diametro);

        $resultadoNecesario = [];
        foreach ($configuracionVistaStock['diametros_considerados'] as $diametro) {
            $resultadoNecesario[$diametro] = [
                'encarretado'   => 0.0,
                'barras'        => collect($configuracionVistaStock['longitudes_barras'])
                    ->mapWithKeys(fn($longitud) => [(int)$longitud => 0.0])
                    ->all(),
                'barras_total'  => 0.0,
                'total'         => 0.0,
            ];
        }

        foreach ($elementosPendientes as $elemento) {
            $diametro = (int) $elemento->diametro;
            if (!isset($resultadoNecesario[$diametro])) continue;

            $pesoNecesario = (float) ($elemento->peso ?? 0);
            $tipoMaterial  = $elemento->maquina->tipo_material; // 'barra' | 'encarretado'

            if ($tipoMaterial === 'encarretado') {
                if ($configuracionVistaStock['incluir_encarretado']) {
                    $resultadoNecesario[$diametro]['encarretado'] += $pesoNecesario;
                }
            } else {
                // No conocemos longitud exacta → sumamos al total de barras
                $resultadoNecesario[$diametro]['barras_total'] += $pesoNecesario;
            }
        }

        foreach ($resultadoNecesario as $diametro => $datos) {
            $resultadoNecesario[$diametro]['total'] = $datos['encarretado'] + $resultadoNecesario[$diametro]['barras_total'];
        }

        return collect($resultadoNecesario);
    }


    private function getPedidosPorDiametro(?array $idsObrasFiltradas = null)
    {
        $configuracionVistaStock = $this->obtenerConfiguracionVistaStock($idsObrasFiltradas);

        $lineasPedidos = DB::table('pedido_productos as pp')
            ->join('pedidos as p', 'p.id', '=', 'pp.pedido_id')
            ->join('productos_base as pb', 'pb.id', '=', 'pp.producto_base_id')
            ->when($idsObrasFiltradas, fn($q) => $q->whereIn('p.obra_id', $idsObrasFiltradas))
            ->where('pp.estado', 'pendiente')
            ->groupBy('pb.tipo', 'pb.diametro', 'pb.longitud')
            ->select('pb.tipo', 'pb.diametro', 'pb.longitud', DB::raw('SUM(pp.cantidad) as total'))
            ->get();

        $resultadoPedidos = [];
        foreach ($configuracionVistaStock['diametros_considerados'] as $diametro) {
            $resultadoPedidos[$diametro] = [
                'encarretado'   => 0.0,
                'barras'        => collect($configuracionVistaStock['longitudes_barras'])
                    ->mapWithKeys(fn($longitud) => [(int)$longitud => 0.0])
                    ->all(),
                'barras_total'  => 0.0,
                'total'         => 0.0,
            ];
        }

        foreach ($lineasPedidos as $linea) {
            $diametro = (int) $linea->diametro;
            if (!isset($resultadoPedidos[$diametro])) continue;

            if ($linea->tipo === 'encarretado') {
                if ($configuracionVistaStock['incluir_encarretado']) {
                    $resultadoPedidos[$diametro]['encarretado'] += (float) $linea->total;
                }
            } elseif ($linea->tipo === 'barra') {
                $longitudBarra = (int) ($linea->longitud ?? 12);
                if (!in_array($longitudBarra, $configuracionVistaStock['longitudes_barras'], true)) {
                    continue;
                }

                if (!isset($resultadoPedidos[$diametro]['barras'][$longitudBarra])) {
                    $resultadoPedidos[$diametro]['barras'][$longitudBarra] = 0.0;
                }
                $resultadoPedidos[$diametro]['barras'][$longitudBarra] += (float) $linea->total;
            }
        }

        foreach ($resultadoPedidos as $diametro => $datos) {
            $resultadoPedidos[$diametro]['barras_total'] = array_sum($datos['barras']);
            $resultadoPedidos[$diametro]['total'] = $datos['encarretado'] + $resultadoPedidos[$diametro]['barras_total'];
        }

        return collect($resultadoPedidos);
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
