<?php

use Illuminate\Support\Facades\Route;
use App\Models\Producto;
use App\Services\StockService;

Route::get('/debug-stock/{obraId}/{diametro}', function ($obraId, $diametro) {
    $stockService = new StockService();

    // === QUERY DE STOCKSERVICE ===
    $productosStockService = Producto::with('productoBase')
        ->where('estado', 'almacenado')
        ->whereIn('obra_id', [$obraId])
        ->get()
        ->filter(function($producto) use ($diametro) {
            if (!$producto->productoBase) return false;
            if ($producto->productoBase->tipo !== 'encarretado') return false;
            if ((int)$producto->productoBase->diametro !== (int)$diametro) return false;
            return true;
        });

    $totalStockService = $productosStockService->sum('peso_inicial');

    // === QUERY DE PRODUCTOSTABLE SIN FILTRO ESTADO (simulado) ===
    $productosTableSinEstado = Producto::with('productoBase')
        ->where('obra_id', $obraId)
        ->whereHas('productoBase', function ($q) use ($diametro) {
            $q->where('tipo', 'encarretado')
              ->where('diametro', $diametro);
        })
        ->get();

    // === QUERY DE PRODUCTOSTABLE CON FILTRO ESTADO=ALMACENADO (usando whereHas como en ProductosTable) ===
    $productosTableConEstado = Producto::with('productoBase')
        ->where('obra_id', $obraId)
        ->where('estado', 'almacenado')
        ->whereHas('productoBase', function ($q) use ($diametro) {
            $q->where('tipo', 'encarretado')
              ->where('diametro', $diametro);
        })
        ->get();

    // === QUERY ALTERNATIVO: Usando filtros como texto (LIKE) similar a ProductosTable ===
    $productosTableTipoLike = Producto::with('productoBase')
        ->where('obra_id', $obraId)
        ->where('estado', 'almacenado')
        ->whereHas('productoBase', function ($q) use ($diametro) {
            $q->where('tipo', 'like', '%encarretado%')
              ->where('diametro', 'like', '%' . $diametro . '%');
        })
        ->get();

    $totalProductosTableSinEstado = $productosTableSinEstado->sum('peso_inicial');
    $totalProductosTableConEstado = $productosTableConEstado->sum('peso_inicial');
    $totalProductosTableTipoLike = $productosTableTipoLike->sum('peso_inicial');

    // Resumen de estados
    $estadosProductosTable = $productosTableSinEstado->groupBy('estado')->map(fn($g) => [
        'count' => $g->count(),
        'total_peso' => $g->sum('peso_inicial')
    ]);

    return response()->json([
        'obra_id' => $obraId,
        'diametro' => $diametro,
        'stockService' => [
            'query_filters' => 'estado=almacenado + obra_id + tipo=encarretado + diametro',
            'total' => $totalStockService,
            'count' => $productosStockService->count(),
            'productos' => $productosStockService->map(fn($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'peso_inicial' => $p->peso_inicial,
                'estado' => $p->estado,
                'producto_base_id' => $p->producto_base_id,
                'tipo' => $p->productoBase->tipo ?? null,
                'diametro' => $p->productoBase->diametro ?? null,
            ])->values()
        ],
        'productosTable_SIN_filtro_estado' => [
            'query_filters' => 'obra_id + tipo=encarretado + diametro',
            'total' => $totalProductosTableSinEstado,
            'count' => $productosTableSinEstado->count(),
            'estados_resumen' => $estadosProductosTable,
        ],
        'productosTable_CON_filtro_estado_almacenado' => [
            'query_filters' => 'estado=almacenado + obra_id + tipo=encarretado + diametro (exact match)',
            'total' => $totalProductosTableConEstado,
            'count' => $productosTableConEstado->count(),
            'productos' => $productosTableConEstado->map(fn($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'peso_inicial' => $p->peso_inicial,
                'estado' => $p->estado,
                'producto_base_id' => $p->producto_base_id,
                'tipo' => $p->productoBase->tipo ?? null,
                'diametro' => $p->productoBase->diametro ?? null,
            ])->values()
        ],
        'productosTable_CON_LIKE' => [
            'query_filters' => 'estado=almacenado + obra_id + tipo LIKE %encarretado% + diametro LIKE %12%',
            'total' => $totalProductosTableTipoLike,
            'count' => $productosTableTipoLike->count(),
            'productos' => $productosTableTipoLike->map(fn($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'peso_inicial' => $p->peso_inicial,
                'estado' => $p->estado,
                'producto_base_id' => $p->producto_base_id,
                'tipo' => $p->productoBase->tipo ?? null,
                'diametro' => $p->productoBase->diametro ?? null,
            ])->values()
        ],
        'comparacion' => [
            'diferencia_stockService_vs_productosTable_CON_estado' => $totalStockService - $totalProductosTableConEstado,
            'diferencia_stockService_vs_productosTable_SIN_estado' => $totalStockService - $totalProductosTableSinEstado,
            'diferencia_stockService_vs_productosTable_CON_LIKE' => $totalStockService - $totalProductosTableTipoLike,
        ],
        'conclusion' => $totalStockService === $totalProductosTableConEstado
            ? '✅ CORRECTO: Ambos dan lo mismo cuando ProductosTable filtra por estado=almacenado'
            : '❌ PROBLEMA: Hay diferencia incluso filtrando por estado=almacenado',
        'nota_importante' => 'Si ves 13 productos en la interfaz pero aquí vemos 18, puede ser que:
            1. Estés filtrando por una nave diferente
            2. Los filtros en ProductosTable usen LIKE en lugar de match exacto
            3. Haya paginación activa'
    ]);
});
