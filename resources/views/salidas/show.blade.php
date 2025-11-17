<x-app-layout>
    <x-slot name="title">{{ $salida->codigo_salida }} - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('planificacion.index') }}" wire:navigate class="text-blue-600">
                {{ __('Planificación') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Detalles de la Salida') }} {{ $salida->codigo_salida }}
    </x-slot>

    <div class="container mx-auto p-6">
        <!-- Detalles de la salida -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <span class="text-sm text-gray-600">Fecha Estimada Entrega:
                    {{ $salida->created_at->format('d/m/Y H:i') }}</span>
                <span class="text-sm text-gray-600">Fecha Creación Salida:
                    {{ $salida->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <p class="text-gray-700 mt-2">Empresa Transporte: <span
                    class="font-semibold text-gray-800">{{ $salida->empresaTransporte->nombre ?? 'N/A' }}</p>

            <p class="text-gray-700 mt-2"> </span>Camión: <span
                    class="font-semibold text-gray-800">{{ $salida->camion->modelo ?? 'N/A' }}
                </span></p>

            <h3 class="font-semibold text-md text-gray-800 mt-4">Paquetes Asociados:</h3>
            <div class="space-y-4 mt-2">
                @foreach ($groupedPackagesFormatted as $grupo)
                    <div class="bg-gray-100 p-2 rounded">
                        <h4 class="font-semibold text-lg">
                            Cliente: {{ $grupo['cliente'] }} / Obra: {{ $grupo['obra'] }}
                        </h4>
                    </div>

                    <div class="space-y-2">
                        @foreach ($grupo['paquetes'] as $paquete)
                            <div class="flex items-center border-b pb-2">
                                <span class="text-gray-900 font-medium">
                                    {{ $paquete->planilla->codigo_limpio }} -
                                </span>
                                <span class="text-gray-700">
                                    Paquete #{{ $paquete->id }} - Peso: {{ $paquete->peso }} kg
                                </span>
                                <button onclick="mostrarDibujo({{ $paquete->id }})"
                                    class="text-blue-500 hover:underline ml-2">
                                    Ver
                                </button>
                                <form action="{{ route('salidas.editarQuitarPaquete', [$salida->id, $paquete->id]) }}"
                                    method="POST" class="ml-4">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline"
                                        onclick="return confirm('¿Estás seguro de que quieres quitar este paquete de la salida?')">
                                        Quitar
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div id="modal-dibujo" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center p-4">
        <div
            class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-auto max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative">
            <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">
                ✖
            </button>

            <h2 class="text-xl font-semibold mb-4 text-center">Elementos del paquete</h2>

            <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                <canvas id="canvas-dibujo" width="800" height="600" class="border max-w-full h-auto"></canvas>
            </div>
        </div>
    </div>
    <script>
        window.paquetes = @json($paquetes);
    </script>

    <!-- Vite: paquetes-bundle -->
    @vite(['resources/js/paquetesJs/paquetes-bundle.js'])
    <!-- <script src="{{ asset('js/paquetesJs/figurasPaquete.js') }}" defer></script> -->
</x-app-layout>
