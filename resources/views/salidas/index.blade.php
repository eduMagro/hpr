<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Gestión de Salidas de Camiones') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-6">
        <div class="grid grid-cols-1 gap-6">

            <!-- SECCIÓN: PLANILLAS COMPLETADAS -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-4 text-black">Planillas Completadas</h1>

                <div class="space-y-4">
                    @foreach ($planillasCompletadas as $planilla)
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <div class="flex justify-between items-center">
                                <h2 class="font-semibold text-lg text-gray-800">{{ $planilla->codigo_limpio }}</h2>
                                <span class="text-sm text-gray-600">{{ $planilla->peso_total_kg }} kg</span>
                            </div>
                            <p class="text-gray-700">Cliente: {{ $planilla->cliente }}</p>
                            <p class="text-gray-700">Obra: {{ $planilla->obra }}</p>
                            <p class="text-gray-700">Sección: {{ $planilla->seccion }}</p>
                            <p class="text-gray-700">Descripción: {{ $planilla->descripcion }}</p>

                            <div class="mt-4">
                                <h3 class="font-semibold text-md text-gray-800">Paquetes Asociados:</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                    @foreach ($planilla->paquetes as $paquete)
                                        <div class="p-4 bg-white shadow rounded-lg flex justify-between items-center">
                                            <span class="text-gray-800">Paquete #{{ $paquete->id }}</span>
                                            <span class="text-gray-600">Peso: {{ $paquete->peso }} kg - </span>
                                            <button onclick="mostrarDibujo({{ $paquete->id }})"
                                                class="text-blue-500 hover:underline">
                                                Ver
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
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

            <!-- Scrollable container for package content -->
            <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                <canvas id="canvas-dibujo" width="800" height="600" class="border max-w-full h-auto"></canvas>
            </div>
        </div>
    </div>
    <script>
        window.paquetes = @json($paquetes);
    </script>
    <script src="{{ asset('js/paquetesJs/figurasPaquete.js') }}" defer></script>
</x-app-layout>
