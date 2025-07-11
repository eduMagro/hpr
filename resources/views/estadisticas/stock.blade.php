<x-app-layout>
    <x-slot name="title">Estadísticas: Stock - {{ config('app.name') }}</x-slot>

    <x-menu.estadisticas />
    <div class="w-full px-6 py-4">
        <x-estadisticas.stock :stock-data="$stockData" :pedidos-por-diametro="$pedidosPorDiametro" :necesario-por-diametro="$necesarioPorDiametro" :total-general="$totalGeneral" :consumo-por-producto-base="$consumoPorProductoBase"
            :producto-base-info="$productoBaseInfo" :stock-por-producto-base="$stockPorProductoBase" :kg-pedidos-por-producto-base="$kgPedidosPorProductoBase" :resumen-reposicion="$resumenReposicion" />

    </div>
</x-app-layout>
