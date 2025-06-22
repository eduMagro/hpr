<x-app-layout>
    <x-slot name="title">Estadísticas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Estadísticas') }}
        </h2>
    </x-slot>
    <x-menu.estadisticas />

</x-app-layout>
