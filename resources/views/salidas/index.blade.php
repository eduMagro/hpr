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
            <div class="bg-white shadow-lg rounded-lg p-6 mt-6">
                <h3 class="font-semibold text-xl text-gray-800 mb-4">Salidas Pendientes</h3>

                @foreach ($salidas as $salida)
                    <div class="mb-6" x-data="{
                        open: false,
                        todosVerificados: false,
                        verificados: [],
                        verificarPaquete(event) {
                            const { verificado, paqueteId } = event.detail;
                            if (verificado) {
                                if (!this.verificados.includes(paqueteId)) {
                                    this.verificados.push(paqueteId);
                                }
                            } else {
                                this.verificados = this.verificados.filter(id => id !== paqueteId);
                            }
                            // Comprobamos si todos los paquetes han sido verificados
                            this.todosVerificados = this.verificados.length === {{ count($salida->paquetes) }};
                        },
                        async actualizarEstado(salidaId) {
                                const response = await fetch(`/salidas/${salidaId}/actualizar`, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), }, body:
                        JSON.stringify({ estado: 'completada' }) }); if (response.ok) { Swal.fire({ title: 'Éxito' ,
                        text: 'El estado de la salida ha sido actualizado a "completada".' , icon: 'success' ,
                        confirmButtonText: 'OK' , }).then(()=> {
                        location.reload();
                        });
                        } else {
                        const data = await response.json();
                        Swal.fire({
                        title: 'Error',
                        text: data.message || 'Hubo un error al actualizar el estado.',
                        icon: 'error',
                        confirmButtonText: 'Reintentar'
                        });
                        }
                        }
                        }">
                        <div class="bg-gray-100 p-4 rounded-lg shadow-md">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-lg font-semibold">{{ $salida->codigo_salida }}</p>
                                    <p class="text-sm text-gray-500">{{ $salida->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm">{{ ucfirst($salida->estado) }}</p>
                                </div>
                                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg mt-2 hover:bg-blue-700"
                                    @click="open = !open">
                                    Ver Detalles
                                </button>
                            </div>

                            <!-- Detalles de la salida (paquetes asociados) -->
                            <div x-show="open" x-transition class="mt-4">
                                <h4 class="text-md font-semibold text-gray-700">Paquetes asociados:</h4>
                                <ul class="list-disc pl-5">
                                    @foreach ($salida->paquetes as $paquete)
                                        <li class="text-sm flex items-center space-x-2" x-data="{ idIngresado: '', paqueteVerificado: false, paqueteId: '{{ $paquete->id }}' }">
                                            <span>{{ $paquete->nombre }} (ID: {{ $paquete->id }})</span>
                                            <input type="text" placeholder="Ingrese ID del paquete"
                                                class="border px-2 py-1 rounded-md" x-model="idIngresado"
                                                @input="paqueteVerificado = (idIngresado == paqueteId); $dispatch('verificar-paquete', { verificado: paqueteVerificado, paqueteId: paqueteId })">

                                            <span x-show="paqueteVerificado" class="text-green-500">&#10004;</span>
                                            <span x-show="!paqueteVerificado && idIngresado"
                                                class="text-red-500">&#10008;</span>
                                        </li>
                                    @endforeach
                                </ul>

                                <!-- Botón para actualizar estado a "completada" -->
                                <div class="mt-4">
                                    <button x-show="todosVerificados"
                                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300"
                                        @click="actualizarEstado({{ $salida->id }})">
                                        Marcar como Completada
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
    <script src="{{ asset('js/salidasJs/salida.js') }}" defer></script>
</x-app-layout>
