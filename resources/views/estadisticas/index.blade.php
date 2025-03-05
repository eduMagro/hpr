<x-app-layout>
    <x-slot name="title">Estadísticas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Estadísticas') }}
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4" x-data="{ mostrarPesos: true, mostrarStock: false }">
        <!-- Botones para mostrar/ocultar las secciones -->
        <div class="mb-4">
            <button @click="mostrarStock = !mostrarStock" class="px-4 py-2 bg-blue-500 text-white rounded-md ml-2">Toggle
                Stock</button>
        </div>

        <!-- Sección Stock -->
        <div x-show="mostrarStock">
            <x-estadisticas.stock :titulo="'Stock Actual'" :stockBarras="$stockBarras" :stockEncarretado="$stockEncarretado" />
        </div>
    </div>
</x-app-layout>
