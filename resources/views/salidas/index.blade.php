<x-app-layout>
    <x-slot name="title">Salidas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Listado de Salidas') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-6">
        @if (auth()->user()->categoria == 'administrador')
            <!-- Botón para crear nueva salida -->
            <div class="mb-4">
                <a href="{{ route('salidas.create') }}"
                    class="bg-green-600 text-white py-2 px-4 rounded-lg shadow-lg hover:bg-green-700 transition duration-300 ease-in-out">
                    Crear Nueva Salida
                </a>
            </div>

            <div class="bg-white shadow-lg rounded-lg p-6 mt-6">
                <h3 class="font-semibold text-xl text-gray-800 mb-4">Todas las Salidas</h3>
                <table class="min-w-full table-auto border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-left text-sm font-medium text-gray-700">
                            <th class="py-2 px-4 border-b">Salida</th>
                            <th class="py-2 px-4 border-b">Fecha</th>
                            <th class="py-2 px-4 border-b">Empresa</th>
                            <th class="py-2 px-4 border-b">Camión</th>
                            <th class="py-2 px-4 border-b">Estado</th>
                            <th class="py-2 px-4 border-b">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($salidas as $salida)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 px-4 border-b">{{ $salida->codigo_salida }}</td>
                                <td class="py-2 px-4 border-b">{{ $salida->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2 px-4 border-b">{{ $salida->empresaTransporte->nombre }}</td>
                                <td class="py-2 px-4 border-b">{{ $salida->camion->modelo }} -
                                    {{ $salida->camion->matricula }}</td>
                                <td class="py-2 px-4 border-b">
                                    {{ ucfirst($salida->estado) }}
                                </td>
                                <td class="py-2 px-4 border-b">
                                    <a href="{{ route('salidas.show', $salida->id) }}"
                                        class="text-blue-600 hover:text-blue-800">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif (auth()->user()->categoria == 'gruista')
            <div class="bg-white shadow-lg rounded-lg p-6">
                @foreach ($salidas as $salida)
                    <div x-data="{ paquetesVerificados: 0, totalPaquetes: {{ count($salida->paquetes) }} }">
                        <div class="mb-6" x-data="{ open: false }">
                            <div class="bg-gray-100 py-4 rounded-lg shadow-md">
                                <div class="flex flex-col md:flex-row md:p-6 justify-between items-center">
                                    <div>
                                        <p class="text-lg font-semibold">{{ $salida->codigo_salida }}</p>
                                        <p class="text-sm text-gray-500">{{ $salida->created_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="py-2 px-4 border-b">{{ $salida->empresaTransporte->nombre }}
                                        </p>
                                        <p class="py-2 px-4 border-b">{{ $salida->camion->modelo }} -
                                            {{ $salida->camion->matricula }}</p>

                                    </div>
                                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg mt-2 hover:bg-blue-700"
                                        @click="open = !open">
                                        <span x-text="open ? '❌' : 'Ver'"></span>
                                    </button>

                                </div>

                                <!-- Detalles de la salida (paquetes asociados) -->
                                <div x-show="open" x-transition
                                    class="mt-4 flex flex-col justify-between items-center">
                                    <h4 class="text-md font-semibold text-gray-700">Paquetes asociados:</h4>
                                    <ul class="list-disc pl-5">
                                        @foreach ($salida->paquetes as $paquete)
                                            <li class="text-sm flex items-center space-x-2" x-data="{ idIngresado: '', paqueteVerificado: false, paqueteId: '{{ $paquete->id }}' }"
                                                x-init="$watch('paqueteVerificado', value => {
                                                    if (value) paquetesVerificados++
                                                    else paquetesVerificados--;
                                                })">
                                                <span>{{ $paquete->nombre }} (ID: {{ $paquete->id }})</span>
                                                <input type="text" placeholder="QR Paquete"
                                                    class="border mb-2 px-2 py-1 rounded-md w-20 sm:w-auto max-w-full"
                                                    x-model="idIngresado"
                                                    @input="paqueteVerificado = (idIngresado == paqueteId); $dispatch('verificar-paquete', { verificado: paqueteVerificado, paqueteId: paqueteId })"
                                                    readonly>


                                                <span x-show="paqueteVerificado" class="text-green-500">&#10004;</span>
                                                <span x-show="!paqueteVerificado && idIngresado"
                                                    class="text-red-500">&#10008;</span>
                                                <button onclick="mostrarDibujo({{ $paquete->id }})"
                                                    class="text-blue-500 hover:underline ml-2">
                                                    Ver
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <!-- Botón para actualizar estado a "completada" -->
                                    <div class="mt-4">
                                        <button
                                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300"
                                            :disabled="paquetesVerificados !== totalPaquetes"
                                            @click="actualizarEstado({{ $salida->id }})">
                                            Marcar como Completada
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
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

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
    <script src="{{ asset('js/salidasJs/salida.js') }}" defer></script>
    <script>
        // Extraer solo los paquetes de todas las salidas
        window.paquetes = @json($salidas->pluck('paquetes')->flatten());
        console.log(window.paquetes); // Puedes ver los paquetes en la consola
    </script>

    <script src="{{ asset('js/paquetesJs/figurasPaquete.js') }}" defer></script>
</x-app-layout>
