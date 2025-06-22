<x-app-layout>
    <x-slot name="title">Estadísticas: Obras - {{ config('app.name') }}</x-slot>
    <x-menu.estadisticas />

    <div class="w-full px-6 py-4">

        <x-estadisticas.obras :pesoPorObra="$pesoPorObra" />
    </div>
</x-app-layout>
