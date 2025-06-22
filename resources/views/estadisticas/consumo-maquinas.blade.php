<x-app-layout>
    <x-slot name="title">Estadísticas: Consumo Máquinas - {{ config('app.name') }}</x-slot>
    <x-menu.estadisticas />

    <div class="w-full px-6 py-4">

        <x-estadisticas.consumo-maquinas :totales="$tablaConsumoTotales" {{-- 👈 mismo nombre --}} :series="['labels' => $labels, 'datasets' => $datasets]" :desde="$desde"
            :hasta="$hasta" :modo="$modo" :detalle="$kilosPorTipoDiametro" {{-- 👈 mismo nombre --}} />
    </div>
</x-app-layout>
