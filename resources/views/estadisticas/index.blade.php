<x-app-layout>
    <x-slot name="title">Estadísticas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Estadísticas') }}
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4" x-data="{ mostrarStock: false, mostrarPesoEntregadoObras: false }">
        <!-- Botones para mostrar/ocultar las secciones -->
        <div class="mb-4">
            <button @click="mostrarStock = !mostrarStock" class="px-4 py-2 bg-blue-500 text-white rounded-md ml-2">
                <span x-text="mostrarStock ? '❌ Cerrar Stock' : 'Ver Stock'"></span>
            </button>

            <button @click="mostrarPesoEntregadoObras = !mostrarPesoEntregadoObras"
                class="px-4 py-2 bg-blue-500 text-white rounded-md ml-2">
                <span x-text="mostrarPesoEntregadoObras ? '❌ Cerrar Peso Obras' : 'Ver Peso Obras'"></span>
            </button>
        </div>

        <!-- Sección Stock -->
        <div x-show="mostrarStock">
            <!-- Componente de Estadísticas Completo -->
            <x-estadisticas.stock :datosPorPlanilla="$datosPorPlanilla" :pesoTotalPorDiametro="$pesoTotalPorDiametro" :stockEncarretado="$stockEncarretado" :stockBarras="$stockBarras" />
        </div>
        <!-- Sección Peso Obras -->
        <div x-show="mostrarPesoEntregadoObras">
            <!-- Componente de Estadísticas Completo -->
            <x-estadisticas.obras :pesoPorObra="$pesoPorObra" />
        </div>
    </div>
</x-app-layout>
