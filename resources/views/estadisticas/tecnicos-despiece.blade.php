<x-app-layout>
    <x-slot name="title">Estadísticas: Planilleros - {{ config('app.name') }}</x-slot>
    @php
        $menu = \App\Services\MenuService::getContextMenu('estadisticas');
    @endphp
    <x-navigation.context-menu :items="$menu['items']" :colorBase="$menu['config']['colorBase']" :style="$menu['config']['style']" :mobileLabel="$menu['config']['mobileLabel']" />
    <div class="w-full">
        <x-estadisticas.tecnicos-despiece :pesoPorPlanillero="$pesoPorUsuario" :pesoAgrupado="$pesoAgrupado" :modo="$modo" />

    </div>
</x-app-layout>
