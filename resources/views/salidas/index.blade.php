<x-app-layout>
    <x-slot name="title">Salidas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Listado de Salidas') }}
        </h2>
    </x-slot>

    <div class="w-full p-4 sm:p-6">
        {{-- Botón para crear nueva salida (solo rol oficina) --}}
        @if (auth()->user()->rol == 'oficina')
            <div class="mb-4 flex justify-end">
                <a href="{{ route('salidas.create') }}"
                    class="bg-green-600 text-white py-2 px-4 rounded-lg shadow-lg hover:bg-green-700 transition duration-300">
                    Crear Nueva Salida
                </a>
            </div>


            {{-- Verificamos que existan salidas --}}
            @if ($salidas->count())
                {{-- Iteramos por cada grupo de salidas por mes --}}
                @foreach ($salidasPorMes as $mes => $salidasGrupo)
                    <div class="mb-4 flex items-center gap-20">
                        <h2 class="text-xl font-semibold text-gray-900">{{ ucfirst($mes) }}</h2>
                        <a href="{{ route('salidas.export', ['mes' => $mes]) }}"
                            class="inline-flex items-center bg-gray-400 text-white py-1 px-3 rounded-lg shadow-lg hover:bg-gray-700 transition duration-300">
                            <img width="28" height="28"
                                src="https://img.icons8.com/fluency/48/microsoft-excel-2019.png"
                                alt="microsoft-excel-2019" />
                            Excel
                        </a>
                    </div>

                    <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                        <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                            <thead>
                                <tr class="bg-gray-100 text-left text-sm font-medium text-gray-700">
                                    <th class="py-2 px-4 border-b">Salida</th>
                                    <th class="py-2 px-4 border-b">Cliente</th>
                                    <th class="py-2 px-4 border-b">Obra</th>
                                    <th class="py-2 px-4 border-b">Empresa</th>
                                    <th class="py-2 px-4 border-b">Camión</th>
                                    <th class="py-2 px-4 border-b">Horas paralización</th>
                                    <th class="py-2 px-4 border-b">Importe paralización</th>
                                    <th class="py-2 px-4 border-b">Horas Grua</th>
                                    <th class="py-2 px-4 border-b">Importe Grua</th>
                                    <th class="py-2 px-4 border-b">Horas Almacén</th>
                                    <th class="py-2 px-4 border-b">Importe</th>
                                    <th class="py-2 px-4 border-b">Fecha</th>
                                    <th class="py-2 px-4 border-b">Estado</th>
                                    <th class="py-2 px-4 border-b">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($salidasGrupo as $salida)
                                    {{-- Por cada salida, iteramos sobre la relación "clientes" para mostrar la información del pivot --}}
                                    @foreach ($salida->clientes as $cliente)
                                        <tr class="hover:bg-gray-50 text-sm">
                                            <td class="py-2 px-4 border-b">{{ $salida->codigo_salida }}</td>
                                            <td class="py-2 px-4 border-b">{{ $cliente->empresa }}</td>
                                            <td class="py-2 px-4 border-b">
                                                {{ $cliente->obrasUnicas->isNotEmpty() ? $cliente->obrasUnicas->implode(', ') : 'N/A' }}
                                            </td>
                                            <td class="py-2 px-4 border-b">{{ $salida->empresaTransporte->nombre }}</td>
                                            <td class="py-2 px-4 border-b">
                                                {{ $salida->camion->modelo }} - {{ $salida->camion->matricula }}
                                            </td>
                                            <td class="py-2 px-4 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}" data-cliente="{{ $cliente->id }}"
                                                data-field="horas_paralizacion">
                                                {{ $cliente->pivot->horas_paralizacion }}
                                            </td>
                                            <td class="py-2 px-4 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}" data-cliente="{{ $cliente->id }}"
                                                data-field="importe_paralizacion">
                                                {{ number_format($cliente->pivot->importe_paralizacion, 2) }}
                                            </td>
                                            <td class="py-2 px-4 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}" data-cliente="{{ $cliente->id }}"
                                                data-field="horas_grua">
                                                {{ $cliente->pivot->horas_grua }}
                                            </td>
                                            <td class="py-2 px-4 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}" data-cliente="{{ $cliente->id }}"
                                                data-field="importe_grua">
                                                {{ number_format($cliente->pivot->importe_grua, 2) }}
                                            </td>
                                            <td class="py-2 px-4 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}" data-cliente="{{ $cliente->id }}"
                                                data-field="horas_almacen">
                                                {{ $cliente->pivot->horas_almacen }}
                                            </td>
                                            <td class="py-2 px-4 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}" data-cliente="{{ $cliente->id }}"
                                                data-field="importe">
                                                {{ number_format($cliente->pivot->importe, 2) }}
                                            </td>
                                            <td class="py-2 px-4 border-b">
                                                {{ $salida->fecha_salida ?? 'Sin fecha' }}
                                            </td>
                                            <td class="py-2 px-4 border-b">
                                                {{ ucfirst($salida->estado) }}
                                            </td>
                                            <td class="py-2 px-4 border-b">
                                                <a href="{{ route('salidas.show', $salida->id) }}"
                                                    class="text-blue-600 hover:text-blue-800">Ver</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{-- Resumen de Clientes para este mes --}}
                    @php
                        $clientSummary = $resumenMensual[$mes] ?? [];
                    @endphp

                    @if (!empty($clientSummary))
                        <div class="mt-6 px-20">
                            <h3 class="text-lg font-semibold text-gray-800">Resumen por Cliente - {{ ucfirst($mes) }}
                            </h3>
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-200 text-left text-sm font-medium text-gray-700">
                                        <th class="py-2 px-4 border-b">Cliente</th>
                                        <th class="py-2 px-4 border-b">Horas Paralización</th>
                                        <th class="py-2 px-4 border-b">Importe Paralización</th>
                                        <th class="py-2 px-4 border-b">Horas Grúa</th>
                                        <th class="py-2 px-4 border-b">Importe Grúa</th>
                                        <th class="py-2 px-4 border-b">Horas Almacén</th>
                                        <th class="py-2 px-4 border-b">Importe Almacén</th>
                                        <th class="py-2 px-4 border-b">Total Cliente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($clientSummary as $cliente => $data)
                                        <tr class="text-sm hover:bg-gray-50">
                                            <td class="py-2 px-4 border-b font-semibold">{{ $cliente }}</td>
                                            <td class="py-2 px-4 border-b text-center">
                                                {{ $data['horas_paralizacion'] }}
                                            </td>
                                            <td class="py-2 px-4 border-b text-right">
                                                {{ number_format($data['importe_paralizacion'], 2) }} €</td>
                                            <td class="py-2 px-4 border-b text-center">{{ $data['horas_grua'] }}</td>
                                            <td class="py-2 px-4 border-b text-right">
                                                {{ number_format($data['importe_grua'], 2) }} €</td>
                                            <td class="py-2 px-4 border-b text-center">{{ $data['horas_almacen'] }}
                                            </td>
                                            <td class="py-2 px-4 border-b text-right">
                                                {{ number_format($data['importe'], 2) }} €</td>
                                            <td class="py-2 px-4 border-b text-right font-bold">
                                                {{ number_format($data['total'], 2) }} €</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endforeach
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
                                                <span x-show="paqueteVerificado"
                                                    class="text-green-500">&#10004;</span>
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

        @endif

        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
        <script src="{{ asset('js/salidasJs/salida.js') }}" defer></script>
        <script src="{{ asset('js/paquetesJs/figurasPaquete.js') }}" defer></script>
        <script>
            window.paquetes = @json($salidas->pluck('paquetes')->flatten());
            console.log(window.paquetes);
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const editableCells = document.querySelectorAll('.editable');

                editableCells.forEach(cell => {
                    // Detectar "Enter" para guardar y salir
                    cell.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            cell.blur();
                        }
                    });

                    // Detectar cambio de valor
                    cell.addEventListener('blur', function() {
                        const id = this.dataset.id;
                        const clienteId = this.dataset.cliente;
                        const field = this.dataset.field;
                        const value = this.innerText.trim();

                        // Evitar peticiones innecesarias si el valor no ha cambiado
                        if (!value) return;

                        fetch(`/salidas/${id}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    id,
                                    cliente_id: clienteId,
                                    field,
                                    value
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log('Actualizado correctamente');
                                } else {
                                    let errorMsg = data.message ||
                                        "Ha ocurrido un error inesperado.";
                                    if (data.errors) {
                                        errorMsg = Object.values(data.errors).flat().join("<br>");
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
                                    });
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    });
                });
            });
        </script>

</x-app-layout>
