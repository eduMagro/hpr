<x-app-layout>
    <x-slot name="title">Estadísticas: Stock - {{ config('app.name') }}</x-slot>

    <x-menu.estadisticas />
    <div class="w-full px-6 py-4">
        <x-estadisticas.stock :stock-data="$stockData" :pedidos-por-diametro="$pedidosPorDiametro" :necesario-por-diametro="$necesarioPorDiametro" :total-general="$totalGeneral"
            :consumo-mensual-promedio="$consumoMensualPromedio" />

    </div>
</x-app-layout>
