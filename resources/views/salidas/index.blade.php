<x-app-layout>
    <x-slot name="title">Salidas - {{ config('app.name') }}</x-slot>

    <x-page-header
        title="Salidas de Material"
        subtitle="Gestión de expediciones y envíos"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l-3-3m3 3l3-3m-3 3V3"/></svg>'
    />

    <div class="w-full p-4 sm:p-4" data-salidas-index>

        {{-- Si el usuario es de oficina, mostramos la tabla con filtros Livewire --}}
        {{-- Si el usuario es operario, mostramos un listado simplificado con opción de completar --}}
        {{-- y escanear paquetes --}}
        @if (auth()->user()->rol == 'oficina')
            @livewire('salidas-ferralla-table')
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
                                        <p class="py-2">{{ $salida->empresaTransporte->nombre ?? 'N/A' }}</p>
                                        <p class="py-2">
                                            {{ $salida->camion->modelo ?? 'N/A' }}
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
    <!-- Vite: salidas-bundle y paquetes-bundle -->
    @vite(['resources/js/salidasJs/salidas-bundle.js', 'resources/js/paquetesJs/paquetes-bundle.js'])
    <!-- <script src="{{ asset('js/salidasJs/salida.js') }}" defer></script>
    <script src="{{ asset('js/paquetesJs/figurasPaquete.js') }}" defer></script> -->
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

                    fetch(`/salidas-ferralla/${id}`, {
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
    <script>
        window.TODOS_CAMIONES = @json($camionesJson);
        console.log('TODOS_CAMIONES cargados:', window.TODOS_CAMIONES);
    </script>

    <script>
        function initSalidasIndexCamiones() {
            const container = document.querySelector('[data-salidas-index]');
            if (!container || container.dataset.camionesInit === '1') {
                return;
            }
            container.dataset.camionesInit = '1';

            const canEdit = !!window.canEdit;

            // Deshabilitar selects si no puede editar
            if (!canEdit) {
                container.querySelectorAll('select.editable-select').forEach(s => s.disabled = true);
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function opcionesCamion(empresaId, camionActual) {
                const frag = document.createDocumentFragment();
                const opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = 'Sin camión';
                frag.appendChild(opt0);

                // Si no hay empresa seleccionada, no mostrar camiones
                if (!empresaId) {
                    return frag;
                }

                const lista = window.TODOS_CAMIONES.filter(c => String(c.empresa_id) === String(empresaId));
                console.log('Filtrando camiones para empresa:', empresaId, 'Encontrados:', lista.length);

                lista.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.modelo;
                    if (camionActual && String(camionActual) === String(c.id)) opt.selected = true;
                    frag.appendChild(opt);
                });
                return frag;
            }

            function findCamionSelectByKey(key) {
                return container.querySelector(`select.camion-select[data-key="${CSS.escape(key)}"]`);
            }

            // Inicializar "camión" para cada fila en base a la empresa seleccionada
            container.querySelectorAll('select.empresa-select').forEach(selEmpresa => {
                const key = selEmpresa.dataset.key;
                const empresaId = selEmpresa.value || selEmpresa.dataset.empresa || '';
                const selCamion = findCamionSelectByKey(key);
                if (!selCamion) return;

                const camionActual = selCamion.getAttribute('data-value') || '';
                selCamion.innerHTML = '';
                selCamion.appendChild(opcionesCamion(empresaId, camionActual));
            });

            // Handler de cambio de empresa: repoblar camiones y guardar empresa
            container.querySelectorAll('select.empresa-select').forEach(selEmpresa => {
                selEmpresa.addEventListener('change', async (e) => {
                    const empresaId = e.target.value;
                    const key = e.target.dataset.key;
                    const selCamion = findCamionSelectByKey(key);
                    if (selCamion) {
                        selCamion.innerHTML = '';
                        selCamion.appendChild(opcionesCamion(empresaId,
                        null)); // limpia selección de camión
                    }
                    if (!canEdit) return;

                    const payload = {
                        id: e.target.dataset.id,
                        cliente_id: e.target.dataset.cliente,
                        obra_id: e.target.dataset.obra,
                        field: e.target.dataset.field, // "empresa_transporte_id"
                        value: empresaId || null,
                    };

                    try {
                        const res = await fetch(`/salidas-ferralla/${payload.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(payload),
                        });
                        const data = await res.json();
                        if (!data.success) {
                            console.error('Error actualizando empresa transporte:', data.message);
                            Swal?.fire?.({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo guardar.'
                            });
                        } else {
                            // Si hemos cambiado de empresa, lo normal es resetear también el camión en BD
                            if (selCamion) {
                                const clearPayload = {
                                    id: payload.id,
                                    cliente_id: payload.cliente_id,
                                    obra_id: payload.obra_id,
                                    field: 'camion_id',
                                    value: null,
                                };
                                await fetch(`/salidas-ferralla/${payload.id}`, {
                                    method: 'PUT',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrf
                                    },
                                    body: JSON.stringify(clearPayload),
                                });
                            }
                        }
                    } catch (err) {
                        console.error(err);
                    }
                });
            });

            // Handler de cambio de camión: guardar camión
            container.querySelectorAll('select.camion-select').forEach(selCamion => {
                selCamion.addEventListener('change', async (e) => {
                    if (!canEdit) return;

                    const payload = {
                        id: e.target.dataset.id,
                        cliente_id: e.target.dataset.cliente,
                        obra_id: e.target.dataset.obra,
                        field: e.target.dataset.field, // "camion_id"
                        value: e.target.value || null,
                    };

                    try {
                        const res = await fetch(`/salidas-ferralla/${payload.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(payload),
                        });
                        const data = await res.json();
                        if (!data.success) {
                            console.error('Error actualizando camión:', data.message);
                            Swal?.fire?.({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo guardar.'
                            });
                        }
                    } catch (err) {
                        console.error(err);
                    }
                });
            });
        }

        // Ejecutar tanto en carga inicial como en navegaciones de Livewire
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSalidasIndexCamiones);
        } else {
            initSalidasIndexCamiones();
        }
        document.addEventListener('livewire:navigated', initSalidasIndexCamiones);
    </script>

    {{-- Inicialización maestra con patrón robusto --}}
    <script>
        function initSalidasIndexPage() {
            // Prevenir doble inicialización
            if (document.body.dataset.salidasIndexPageInit === 'true') return;

            console.log('🔍 Inicializando Salidas Index...');

            // Llamar a las funciones de inicialización
            if (typeof initSalidasIndexCamiones === 'function') initSalidasIndexCamiones();

            // Marcar como inicializado
            document.body.dataset.salidasIndexPageInit = 'true';
        }

        // Registrar en el sistema global
        window.pageInitializers.push(initSalidasIndexPage);

        // Configurar listeners
        document.addEventListener('livewire:navigated', initSalidasIndexPage);
        document.addEventListener('DOMContentLoaded', initSalidasIndexPage);

        // Limpiar flag antes de navegar
        document.addEventListener('livewire:navigating', () => {
            document.body.dataset.salidasIndexPageInit = 'false';
        });
    </script>

</x-app-layout>
