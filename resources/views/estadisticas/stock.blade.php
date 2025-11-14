<x-app-layout>
    <x-slot name="title">Estadísticas: Stock - {{ config('app.name') }}</x-slot>

    @php
        $menu = \App\Services\MenuService::getContextMenu('estadisticas');
    @endphp
    <x-navigation.context-menu
        :items="$menu['items']"
        :colorBase="$menu['config']['colorBase']"
        :style="$menu['config']['style']"
        :mobileLabel="$menu['config']['mobileLabel']"
    />
    <div class="w-full px-6 py-4">
        <x-estadisticas.stock :nombre-meses="$nombreMeses" :stock-data="$stockData" :pedidos-por-diametro="$pedidosPorDiametro" :necesario-por-diametro="$necesarioPorDiametro" :total-general="$totalGeneral"
            :consumo-origen="$consumoOrigen" :consumos-por-mes="$consumosPorMes" :producto-base-info="$productoBaseInfo" :stock-por-producto-base="$stockPorProductoBase" :kg-pedidos-por-producto-base="$kgPedidosPorProductoBase" :resumen-reposicion="$resumenReposicion"
            :recomendacion-reposicion="$recomendacionReposicion" />


    </div>
</x-app-layout>
