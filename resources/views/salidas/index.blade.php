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
        @endif

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

                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6 mt-4 overflow-x-auto">
                    <table class="w-full border-collapse">
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
                                        <!-- Datos de la salida -->
                                        <td class="py-2 px-4 border-b">{{ $salida->codigo_salida }}</td>
                                        <!-- Cliente: obtenido de la relación -->
                                        <td class="py-2 px-4 border-b">{{ $cliente->nombre }}</td>
                                        <!-- Obra: se muestra el resultado de obrasUnicas si existe -->
                                        <td class="py-2 px-4 border-b">
                                            @if (isset($salida->obrasUnicas) && $salida->obrasUnicas->count())
                                                {{ implode(', ', $salida->obrasUnicas->toArray()) }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="py-2 px-4 border-b">{{ $salida->empresaTransporte->nombre }}</td>
                                        <td class="py-2 px-4 border-b">
                                            {{ $salida->camion->modelo }} - {{ $salida->camion->matricula }}
                                        </td>
                                        <!-- Datos específicos del pivot -->
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="horas_paralizacion">
                                            {{ $cliente->pivot->horas_paralizacion }}
                                        </td>
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="importe_paralizacion">
                                            {{ number_format($cliente->pivot->importe_paralizacion, 2) }} €
                                        </td>
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="horas_grua">
                                            {{ $cliente->pivot->horas_grua }}
                                        </td>
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="importe_grua">
                                            {{ number_format($cliente->pivot->importe_grua, 2) }} €
                                        </td>
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="horas_almacen">
                                            {{ $cliente->pivot->horas_almacen }}
                                        </td>
                                        <td class="py-2 px-4 border-b editable" contenteditable="true"
                                            data-id="{{ $salida->id }}" data-field="importe">
                                            {{ number_format($cliente->pivot->importe, 2) }} €
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
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Resumen por Cliente: suma de importes de la tabla pivote --}}
                @php
                    $clientSummary = [];
                    foreach ($salidasGrupo as $salida) {
                        foreach ($salida->clientes as $cliente) {
                            $nombre = $cliente->nombre;
                            $importe = $cliente->pivot->importe;
                            if ($nombre) {
                                if (!isset($clientSummary[$nombre])) {
                                    $clientSummary[$nombre] = 0;
                                }
                                $clientSummary[$nombre] += $importe;
                            }
                        }
                    }
                @endphp

                @if (!empty($clientSummary))
                    <div class="mt-6 px-20">
                        <h3 class="text-lg font-semibold text-gray-800">Resumen por Cliente</h3>
                        <table class="border-collapse mt-2 bg-white shadow-lg rounded-lg">
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
                                        <td class="py-2 px-4 border-b">{{ number_format($total, 2) }} €</td>
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

        {{-- La sección para usuarios de categoría gruista se omite en este ejemplo --}}
    </div>

    {{-- Modal para ver dibujos --}}
    <div id="modal-dibujo" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center p-4">
        <div
            class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-auto max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative">
            <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">✖</button>
            <h2 class="text-xl font-semibold mb-4 text-center">Elementos del paquete</h2>
            <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                <canvas id="canvas-dibujo" width="800" height="600" class="border max-w-full h-auto"></canvas>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
    <script src="{{ asset('js/salidasJs/salida.js') }}" defer></script>
    <script>
        window.paquetes = @json($salidas->pluck('paquetes')->flatten());
        console.log(window.paquetes);
    </script>
    <script src="{{ asset('js/paquetesJs/figurasPaquete.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editableCells = document.querySelectorAll('.editable');
            editableCells.forEach(cell => {
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
                    fetch(`/salidas/${id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                id,
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
                                }).then(() => {
                                    window.location.reload();
                                });
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            });
        });
    </script>
</x-app-layout>
