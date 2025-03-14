<x-app-layout>
    <x-slot name="title">Salidas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Listado de Salidas') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-4 sm:p-6">
        @if (auth()->user()->rol == 'oficina')
            <!-- Botón para crear nueva salida -->
            <div class="mb-4 flex justify-end">
                <a href="{{ route('salidas.create') }}"
                    class="bg-green-600 text-white py-2 px-4 rounded-lg shadow-lg hover:bg-green-700 transition duration-300">
                    Crear Nueva Salida
                </a>
            </div>

            @if (!empty($salidasPorMes) && count($salidasPorMes) > 0)
                @foreach ($salidasPorMes as $mes => $salidas)
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900 mt-6">{{ ucfirst($mes) }}</h2>
                        <a href="{{ route('salidas.export', ['mes' => $mes]) }}"
                            class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 transition duration-300">
                            Exportar Excel
                        </a>
                    </div>

                    <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6 mt-4 overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-left text-sm font-medium text-gray-700">
                                    <th class="py-2 px-4 border-b">Salida</th>
                                    <th class="py-2 px-4 border-b">Cliente</th>
                                    <th class="py-2 px-4 border-b">Obra</th>
                                    <th class="py-2 px-4 border-b">Empresa</th>
                                    <th class="py-2 px-4 border-b">Camión</th>
                                    <th class="py-2 px-4 border-b">Importe</th>
                                    <th class="py-2 px-4 border-b">Paralización</th>
                                    <th class="py-2 px-4 border-b">Horas</th>
                                    <th class="py-2 px-4 border-b">Horas/Almacén</th>
                                    <th class="py-2 px-4 border-b">Fecha</th>
                                    <th class="py-2 px-4 border-b">Estado</th>
                                    <th class="py-2 px-4 border-b">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($salidas as $salida)
                                    <tr class="hover:bg-gray-50 text-sm">
                                        <td class="py-2 px-4 border-b">{{ $salida->codigo_salida }}</td>
                                        <td class="py-2 px-4 border-b">
                                            @foreach ($salida->clientesUnicos ?? [] as $cliente)
                                                <span class="block mb-2">{{ $cliente ?? 'N/A' }}</span>
                                            @endforeach
                                        </td>
                                        <td class="py-2 px-4 border-b">
                                            @foreach ($salida->obrasUnicas as $obra)
                                                <span class="block mb-2">{{ $obra ?? 'N/A' }}</span>
                                            @endforeach
                                        </td>
                                        <td class="py-2 px-4 border-b">{{ $salida->empresaTransporte->nombre }}</td>
                                        <td class="py-2 px-4 border-b">
                                            {{ $salida->camion->modelo }} - {{ $salida->camion->matricula }}
                                        </td>
                                        <!-- Campos editables -->
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="importe">
                                            {{ $salida->importe ?? '' }}
                                        </td>
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="paralizacion">
                                            {{ $salida->paralizacion ?? '' }}
                                        </td>
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="horas">
                                            {{ $salida->horas ?? '' }}
                                        </td>
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="horas_almacen">
                                            {{ $salida->horas_almacen ?? '' }}
                                        </td>
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="fecha_salida">
                                            {{ $salida->fecha_salida ?? 'Sin fecha' }}
                                        </td>
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="estado">
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
                    {{-- Resumen por Cliente --}}
                    @php
                        $clientSummary = [];
                        // Recorremos todas las salidas del mes para acumular el importe por cliente.
                        foreach ($salidas as $salida) {
                            // Suponemos que $salida->importe es numérico y que clientesUnicos es un arreglo con el nombre del cliente
                            $importe = $salida->importe ?? 0;
                            foreach ($salida->clientesUnicos as $cliente) {
                                if ($cliente) {
                                    if (!isset($clientSummary[$cliente])) {
                                        $clientSummary[$cliente] = 0;
                                    }
                                    $clientSummary[$cliente] += $importe;
                                }
                            }
                        }
                    @endphp

                    @if (!empty($clientSummary))
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-800">Resumen por Cliente</h3>
                            <table class="w-full border-collapse mt-2">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="py-2 px-4 border-b">Cliente</th>
                                        <th class="py-2 px-4 border-b">Total Importe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($clientSummary as $cliente => $total)
                                        <tr>
                                            <td class="py-2 px-4 border-b">{{ $cliente }}</td>
                                            <td class="py-2 px-4 border-b">{{ number_format($total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endforeach
            @else
                <p class="text-gray-600 text-center">No hay salidas registradas.</p>
            @endif
        @elseif (auth()->user()->categoria == 'gruista')
            <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                @foreach ($salidas as $salida)
                    <div x-data="{ paquetesVerificados: 0, totalPaquetes: {{ count($salida->paquetes) }} }">
                        <div class="mb-6" x-data="{ open: false }">
                            <div class="bg-gray-100 py-4 px-4 sm:px-6 rounded-lg shadow-md">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-lg font-semibold">{{ $salida->codigo_salida }}</p>
                                        <p class="text-sm text-gray-500">
                                            {{ $salida->created_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                    <div class="mt-2 sm:mt-0">
                                        <p class="py-2">{{ $salida->empresaTransporte->nombre }}</p>
                                        <p class="py-2">
                                            {{ $salida->camion->modelo }} - {{ $salida->camion->matricula }}
                                        </p>
                                    </div>
                                    <button
                                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mt-2 sm:mt-0"
                                        @click="open = !open">
                                        <span x-text="open ? '❌' : 'Ver'"></span>
                                    </button>
                                </div>

                                <!-- Detalles de la salida (paquetes asociados) -->
                                <div x-show="open" x-transition class="mt-4 p-2 sm:p-4">
                                    <h4 class="text-md font-semibold text-gray-700">Paquetes asociados:</h4>
                                    <ul class="list-disc pl-5">
                                        @foreach ($salida->paquetes as $paquete)
                                            <li class="text-sm flex items-center space-x-2" x-data="{ idIngresado: '', paqueteVerificado: false, paqueteId: '{{ $paquete->id }}' }"
                                                x-init="$watch('paqueteVerificado', value => {
                                                    if (value) paquetesVerificados++
                                                    else paquetesVerificados--;
                                                })">
                                                <span>
                                                    {{ $paquete->ubicacion->nombre }} (ID: {{ $paquete->id }})
                                                </span>
                                                <input type="text" placeholder="QR Paquete"
                                                    class="border mb-2 px-2 py-1 rounded-md w-20 sm:w-auto max-w-full"
                                                    x-model="idIngresado"
                                                    @input="paqueteVerificado = (idIngresado == paqueteId);">
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

                                    <!-- Botón para actualizar estado -->
                                    <div class="mt-4 text-center">
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
                <div class="mt-4 flex justify-center">
                    {{ $salidas->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
                </div>
        @endif

        <!-- Modal para ver dibujos -->
        <div id="modal-dibujo"
            class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center p-4">
            <div
                class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-auto max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative">
                <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">
                    ✖
                </button>
                <h2 class="text-xl font-semibold mb-4 text-center">Elementos del paquete</h2>
                <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                    <canvas id="canvas-dibujo" width="800" height="600"
                        class="border max-w-full h-auto"></canvas>
                </div>
            </div>
        </div>

        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
        <script src="{{ asset('js/salidasJs/salida.js') }}" defer></script>
        <script>
            // Extraer solo los paquetes de todas las salidas para su uso posterior
            window.paquetes = @json($salidas->pluck('paquetes')->flatten());
            console.log(window.paquetes);
        </script>
        <script src="{{ asset('js/paquetesJs/figurasPaquete.js') }}" defer></script>

        <!-- Script para Inline Editing -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const editableCells = document.querySelectorAll('.editable');
                editableCells.forEach(cell => {
                    // Evitar salto de línea al presionar Enter
                    cell.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            cell.blur();
                        }
                    });
                    cell.addEventListener('blur', function() {
                        const id = this.dataset.id;
                        const field = this.dataset.field;
                        const value = this.innerText.trim();
                        // Realiza la petición AJAX usando el ID correcto
                        fetch(`/salidas/${id}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    id: id,
                                    field: field,
                                    value: value
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log('Actualizado correctamente');
                                } else {
                                    let errorMsg =
                                        data.message || "Ha ocurrido un error inesperado.";
                                    // Si existen errores de validación, concatenarlos
                                    if (data.errors) {
                                        errorMsg = Object.values(data.errors).flat().join(
                                            "<br>"); // O puedes usar '\n' para saltos de línea
                                    }
                                    console.error('Error al actualizar', errorMsg);
                                    Swal.fire({
                                        icon: "error",
                                        title: "Error al actualizar",
                                        html: errorMsg,
                                        confirmButtonText: "OK",
                                        showCancelButton: true,
                                        cancelButtonText: "Reportar Error"
                                    }).then((result) => {
                                        if (result.dismiss === Swal.DismissReason.cancel) {
                                            notificarProgramador(errorMsg);
                                        }
                                    }).then(() => {
                                        window.location
                                            .reload(); // Recarga la página tras el mensaje
                                    });
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    });
                });
            });
        </script>
    </div>
</x-app-layout>
