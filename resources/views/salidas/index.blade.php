<x-app-layout>
    <x-slot name="title">Salidas - {{ config('app.name') }}</x-slot>
    <x-menu.salidas />

    <div class="w-full p-4 sm:p-4">

        {{-- Botón para crear nueva salida (solo rol oficina) --}}
        @if (auth()->user()->rol == 'oficina')
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
                                <tr class="bg-gray-100 text-left text-xs font-medium text-center text-gray-700">
                                    <th class="py-2 px-4 border-b">Salida</th>
                                    <th class="py-2 px-4 border-b">Codigo Sage</th>
                                    <th class="py-2 px-4 border-b">Cliente</th>
                                    <th class="py-2 px-4 border-b">Obra</th>
                                    <th class="py-2 px-4 border-b">E. Transporte</th>
                                    <th class="py-2 px-4 border-b">Camión</th>
                                    <th class="p-2 border-b">Horas paralización</th>
                                    <th class="p-2 border-b">Importe paralización</th>
                                    <th class="p-2 border-b">Horas Grua</th>
                                    <th class="p-2 border-b">Importe Grua</th>
                                    <th class="p-2 border-b">Horas Almacén</th>
                                    <th class="p-2 border-b">Importe</th>
                                    <th class="py-2 px-4 border-b">Fecha Estimada Entrega</th>
                                    <th class="py-2 px-4 border-b">Estado</th>
                                    <th class="py-2 px-4 border-b">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($salidasGrupo as $salida)
                                    {{-- Iteramos sobre los registros del pivot (salidaClientes) para cada salida --}}
                                    @foreach ($salida->salidaClientes as $registro)
                                        <tr class="hover:bg-gray-50 text-xs text-center">
                                            <td class="py-2 px-4 border-b">{{ $salida->codigo_salida }}</td>
                                            <td class="p-2 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}"
                                                data-cliente="{{ $registro->cliente->id }}"
                                                data-obra="{{ $registro->obra->id }}" data-field="codigo_sage">
                                                {{ $salida->codigo_sage ?? '' }}
                                            </td>
                                            <td class="py-2 px-4 border-b">{{ $registro->cliente->empresa }}</td>
                                            <td class="py-2 px-4 border-b">{{ $registro->obra->obra ?? 'N/A' }}</td>
                                            <td class="py-2 px-4 border-b">{{ $salida->empresaTransporte->nombre }}
                                            </td>
                                            <td class="py-2 px-4 border-b">
                                                {{ $salida->camion->modelo }}
                                            </td>
                                            <td class="p-2 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}"
                                                data-cliente="{{ $registro->cliente->id }}"
                                                data-obra="{{ $registro->obra->id }}" data-field="horas_paralizacion">
                                                {{ $registro->horas_paralizacion }}
                                            </td>
                                            <td class="p-2 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}"
                                                data-cliente="{{ $registro->cliente->id }}"
                                                data-obra="{{ $registro->obra->id }}"
                                                data-field="importe_paralizacion">
                                                {{ number_format($registro->importe_paralizacion, 2) }}
                                            </td>
                                            <td class="p-2 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}"
                                                data-cliente="{{ $registro->cliente->id }}"
                                                data-obra="{{ $registro->obra->id }}" data-field="horas_grua">
                                                {{ $registro->horas_grua }}
                                            </td>
                                            <td class="p-2 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}"
                                                data-cliente="{{ $registro->cliente->id }}"
                                                data-obra="{{ $registro->obra->id }}" data-field="importe_grua">
                                                {{ number_format($registro->importe_grua, 2) }}
                                            </td>
                                            <td class="p-2 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}"
                                                data-cliente="{{ $registro->cliente->id }}"
                                                data-obra="{{ $registro->obra->id }}" data-field="horas_almacen">
                                                {{ $registro->horas_almacen }}
                                            </td>
                                            <td class="p-2 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}"
                                                data-cliente="{{ $registro->cliente->id }}"
                                                data-obra="{{ $registro->obra->id }}" data-field="importe">
                                                {{ number_format($registro->importe, 2) }}
                                            </td>
                                            <td class="py-2 px-4 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}"
                                                data-cliente="{{ $registro->cliente->id }}"
                                                data-obra="{{ $registro->obra->id }}" data-field="fecha_salida">
                                                {{ $salida->fecha_salida ?? 'Sin fecha' }}
                                            </td>
                                            <td class="py-2 px-4 border-b editable" contenteditable="true"
                                                data-id="{{ $salida->id }}"
                                                data-cliente="{{ $registro->cliente->id }}"
                                                data-obra="{{ $registro->obra->id }}" data-field="estado">
                                                {{ ucfirst($salida->estado) }}
                                            </td>

                                            <td class="py-2 px-4 border-b">
                                                <a href="{{ route('salidas.show', $salida->id) }}"
                                                    class="text-blue-600 hover:text-blue-800">Ver</a>
                                                @if (auth()->user()->rol === 'oficina' || strtolower(auth()->user()->name) === 'alberto mayo martin')
                                                    <x-tabla.boton-eliminar :action="route('salidas.destroy', $salida->id)" />
                                                @endif

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
                        <div class="mt-6 px-20 mb-20">
                            <h3 class="text-lg font-semibold text-gray-800">Resumen por Empresa Transporte -
                                {{ ucfirst($mes) }}
                            </h3>
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-200 text-left text-xs font-medium text-gray-700">
                                        <th class="py-2 px-4 border-b">E.Transporte</th>
                                        <th class="py-2 px-4 border-b">Horas Paralización</th>
                                        <th class="py-2 px-4 border-b">Importe Paralización</th>
                                        <th class="py-2 px-4 border-b">Horas Grúa</th>
                                        <th class="py-2 px-4 border-b">Importe Grúa</th>
                                        <th class="py-2 px-4 border-b">Horas Almacén</th>
                                        <th class="py-2 px-4 border-b">Importe</th>
                                        <th class="py-2 px-4 border-b">Total Cliente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($clientSummary as $cliente => $data)
                                        <tr class="text-xs hover:bg-gray-50"
                                            data-resumen-cliente="{{ $data['cliente_id'] ?? 'N/A' }}">
                                            <td class="py-2 px-4 border-b font-semibold">{{ $cliente }}</td>
                                            <td class="py-2 px-4 border-b text-center"
                                                data-resumen-field="horas_paralizacion">
                                                {{ $data['horas_paralizacion'] ?? 0 }}
                                            </td>
                                            <td class="py-2 px-4 border-b text-right"
                                                data-resumen-field="importe_paralizacion">
                                                {{ number_format($data['importe_paralizacion'] ?? 0, 2) }} €
                                            </td>
                                            <td class="py-2 px-4 border-b text-center"
                                                data-resumen-field="horas_grua">
                                                {{ $data['horas_grua'] ?? 0 }}
                                            </td>
                                            <td class="py-2 px-4 border-b text-right"
                                                data-resumen-field="importe_grua">
                                                {{ number_format($data['importe_grua'] ?? 0, 2) }} €
                                            </td>
                                            <td class="py-2 px-4 border-b text-center"
                                                data-resumen-field="horas_almacen">
                                                {{ $data['horas_almacen'] ?? 0 }}
                                            </td>
                                            <td class="py-2 px-4 border-b text-right" data-resumen-field="importe">
                                                {{ number_format($data['importe'] ?? 0, 2) }} €
                                            </td>
                                            <td class="py-2 px-4 border-b text-right font-bold" data-resumen-total>
                                                {{ number_format($data['total'] ?? 0, 2) }} €
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>
                        <hr class="m-10 border-gray-300">
                    @endif
                @endforeach
            @endif
        @elseif (auth()->user()->rol == 'operario')
            <div class="bg-white shadow-lg rounded-lg p-2 sm:p-4">
                @foreach ($salidas as $salida)
                    <div x-data="{ paquetesVerificados: 0, totalPaquetes: {{ count($salida->paquetes) }} }">
                        <div class="mb-6" x-data="{ open: false }">
                            <div class="bg-gray-100 py-4 px- sm:p-4 rounded-lg shadow-md">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold">{{ $salida->codigo_salida }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $salida->created_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                    <div class="mt-2 sm:mt-0">
                                        <p class="py-2">{{ $salida->empresaTransporte->nombre }}</p>
                                        <p class="py-2">
                                            {{ $salida->camion->modelo }}
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
            </div>
            <!-- Modal con Canvas para Dibujar las Dimensiones -->
            <div id="modal-dibujo"
                class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center p-4">
                <div
                    class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-auto max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative">
                    <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">
                        ✖
                    </button>

                    <h2 class="text-xl font-semibold mb-4 text-center">Elementos del paquete</h2>
                    <!-- Contenedor desplazable -->
                    <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                        <canvas id="canvas-dibujo" width="800" height="600"
                            class="border max-w-full h-auto"></canvas>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <!-- Scripts -->
    <script src="{{ asset('js/salidasJs/salida.js') }}" defer></script>
    <script src="{{ asset('js/paquetesJs/figurasPaquete.js') }}" defer></script>
    <script>
        window.paquetes = @json($paquetes);

        window.canEdit = @json(auth()->user()->rol === 'oficina' || strtolower(auth()->user()->name) === 'Alberto Mayo Martin');
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Formatea las celdas editables en cuanto la página carga
            const editableCells = document.querySelectorAll('.editable');

            // Si no se tiene permiso, remover contenteditable de todas las celdas
            if (!window.canEdit) {
                editableCells.forEach(cell => {
                    cell.removeAttribute('contenteditable');
                });
                return; // No se agrega ningún listener
            }

            editableCells.forEach(cell => {
                const field = cell.dataset.field;
                // Si el campo es numérico (monetario o de horas), se formatea
                if (esCampoMonetario(field) || esCampoHoras(field)) {
                    // Se extrae el valor sin sufijos para asegurarse
                    let rawValue = cell.innerText.trim().replace(/[€h]/g, '').trim();
                    let numericValue = parseFloat(rawValue) || 0;
                    cell.innerText = formatearValor(field, numericValue);
                }
            });
            editableCells.forEach(cell => {
                // Interceptar "Enter" para evitar salto de línea y forzar blur
                cell.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.blur();
                    }
                });
                cell.addEventListener('blur', function() {
                    const id = this.dataset.id;
                    const clienteId = this.dataset.cliente;
                    const obraId = this.dataset.obra;
                    const field = this.dataset.field;
                    // Remover símbolos de € y h para obtener el valor limpio
                    let rawValue = this.innerText.trim().replace(/[€h]/g, '').trim();

                    // Formateo específico para fecha y estado
                    let value;
                    if (field === 'fecha_salida') {
                        value = convertirFechaHora(rawValue);
                        if (!value) {
                            Swal.fire({
                                icon: "error",
                                title: "Error al actualizar",
                                html: "Formato de fecha inválido. Usa DD/MM/YYYY HH:MM o YYYY-MM-DD HH:MM:SS.",
                                confirmButtonText: "OK"
                            });
                            return;
                        }
                    } else if (field === 'estado') {
                        value = rawValue.charAt(0).toUpperCase() + rawValue.slice(1).toLowerCase();
                        if (!value) {
                            Swal.fire({
                                icon: "error",
                                title: "Error al actualizar",
                                html: "El estado no puede estar vacío",
                                confirmButtonText: "OK"
                            });
                            return;
                        }
                    } else if (esCampoMonetario(field) || esCampoHoras(field)) {
                        // Para los campos numéricos, obtener valor numérico
                        value = parseFloat(rawValue) || 0;
                    } else {
                        // Para campos de texto como codigo_sage
                        value = rawValue;
                    }

                    // Enviar actualización

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
                                obra_id: obraId,
                                field,
                                value
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Actualizado correctamente');
                                // Reaplicar formato solo para campos numéricos
                                if (field !== 'fecha_salida' && field !== 'estado') {
                                    // Actualizamos la celda con el valor formateado
                                    this.innerText = formatearValor(field, value);
                                    actualizarResumen(clienteId, field, value);
                                } else {
                                    this.innerText = value;
                                }
                            } else {
                                console.error('Error al actualizar', data.message);
                                Swal.fire({
                                    icon: "error",
                                    title: "Error al actualizar",
                                    html: data.message,
                                    confirmButtonText: "OK"
                                });
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            });

            /**
             * 🔹 Actualiza el resumen de clientes cuando cambia un valor en la tabla de salidas.
             */
            function actualizarResumen(clienteId, field, newValue) {
                const resumenRow = document.querySelector(`tr[data-resumen-cliente="${clienteId}"]`);
                if (!resumenRow) return;

                // Seleccionar la celda del resumen que coincide con el campo actualizado
                const resumenField = resumenRow.querySelector(`[data-resumen-field="${field}"]`);
                if (resumenField) {
                    resumenField.innerText = formatearValor(field, newValue);
                }

                // 🔹 Si es un campo monetario o de horas, actualizar el total correspondiente
                if (esCampoMonetario(field) || esCampoHoras(field)) {
                    actualizarTotalResumen(resumenRow);
                }
            }

            /**
             * 🔹 Recalcula el total de cada cliente en el resumen.
             */
            function actualizarTotalResumen(resumenRow) {
                let totalEuros = 0;
                let totalHoras = 0;

                // Sumar valores de euros y horas por separado
                ['importe_paralizacion', 'importe_grua', 'importe'].forEach(field => {
                    let cell = resumenRow.querySelector(`[data-resumen-field="${field}"]`);
                    if (cell) {
                        totalEuros += parseFloat(cell.innerText.replace('€', '').trim()) || 0;
                    }
                });

                ['horas_paralizacion', 'horas_grua', 'horas_almacen'].forEach(field => {
                    let cell = resumenRow.querySelector(`[data-resumen-field="${field}"]`);
                    if (cell) {
                        totalHoras += parseFloat(cell.innerText.trim()) || 0;
                    }
                });

                // Actualizar el campo total en euros del cliente en el resumen
                let totalEurosCell = resumenRow.querySelector(`[data-resumen-total]`);
                if (totalEurosCell) {
                    totalEurosCell.innerText = `${totalEuros.toFixed(2)} €`;
                }

                // Actualizar el campo total en horas si hay un campo específico para eso
                let totalHorasCell = resumenRow.querySelector(`[data-resumen-horas-total]`);
                if (totalHorasCell) {
                    totalHorasCell.innerText = `${totalHoras.toFixed(2)} h`;
                }
            }

            function formatearValor(field, value) {
                if (esCampoMonetario(field)) {
                    return `${parseFloat(value).toFixed(2)} €`;
                } else if (esCampoHoras(field)) {
                    return `${parseFloat(value).toFixed(2)} h`;
                }
                return value;
            }

            function esCampoMonetario(field) {
                return ['importe_paralizacion', 'importe_grua', 'importe'].includes(field);
            }

            function esCampoHoras(field) {
                return ['horas_paralizacion', 'horas_grua', 'horas_almacen'].includes(field);
            }

            /**
             * 🔹 Convierte una fecha con hora de DD/MM/YYYY HH:MM a YYYY-MM-DD HH:MM:SS.
             */
            function convertirFechaHora(fecha) {
                let regexDMY = /^(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{2}):(\d{2})$/; // Formato: 17/03/2025 14:30
                let regexYMD =
                    /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):?(\d{2})?$/; // Formato: 2025-03-17 14:30:00

                if (regexDMY.test(fecha)) {
                    let [, day, month, year, hours, minutes] = fecha.match(regexDMY);
                    return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')} ${hours}:${minutes}:00`;
                } else if (regexYMD.test(fecha)) {
                    return fecha; // Ya está en el formato correcto
                }

                return null; // No es un formato válido
            }
        });
    </script>

</x-app-layout>
