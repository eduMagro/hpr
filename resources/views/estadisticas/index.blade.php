<x-app-layout>
    <x-slot name="title">Estadísticas - {{ config('app.name') }}</x-slot>

    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Estadísticas') }} wire:navigate
        </h2>
    </x-slot>
    @php
        $menu = \App\Services\MenuService::getContextMenu('estadisticas');
    @endphp
    <x-navigation.context-menu
        :items="$menu['items']"
        :colorBase="$menu['config']['colorBase']"
        :style="$menu['config']['style']"
        :mobileLabel="$menu['config']['mobileLabel']"
    />
</x-app-layout>
