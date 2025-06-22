<x-app-layout>
    <x-slot name="title">Estadísticas: Stock - {{ config('app.name') }}</x-slot>

    <x-menu.estadisticas />
    <div class="w-full px-6 py-4">
        <x-estadisticas.stock :datosPorPlanilla="$datosPorPlanilla" :pesoTotalPorDiametro="$pesoTotalPorDiametro" :stockEncarretado="$stockEncarretado" :stockBarras="$stockBarras"
            :stockOptimo="$stockOptimo" />
    </div>
</x-app-layout>
