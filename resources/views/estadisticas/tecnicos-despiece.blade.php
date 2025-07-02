<x-app-layout>
    <x-slot name="title">Estadísticas: Planilleros - {{ config('app.name') }}</x-slot>
    <x-menu.estadisticas />
    <div class="w-full px-6 py-4">
        <x-estadisticas.tecnicos-despiece :pesoPorPlanillero="$pesoPorUsuario" :pesoAgrupado="$pesoAgrupado" :modo="$modo" />

    </div>
</x-app-layout>
