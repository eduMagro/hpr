<x-app-layout>
    <x-slot name="title">Pedidos - {{ config('app.name') }}</x-slot>

    <div class="px-4 py-6">
        @if (auth()->user()->rol === 'oficina')
            <!-- Tabla pedidos  -->
            @livewire('pedidos-table')

            <hr class="my-6">

            {{-- SECCIÓN DE STOCK --}}
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mt-4">📦 Estado actual de stock, pedidos y necesidades</h2>

                <div class="flex flex-wrap items-center gap-4 p-4">
                    <div>
                        <label for="obra_id_hpr" class="block text-sm font-medium text-gray-700 mb-1">
                            Seleccionar obra (Hierros Paco Reyes)
                        </label>
                        <select name="obra_id_hpr" id="obra_id_hpr_stock"
                            class="rounded border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <option value="">-- Todas las naves --</option>
                            @foreach ($obrasHpr as $obra)
                                <option value="{{ $obra->id }}"
                                    {{ request('obra_id_hpr') == $obra->id ? 'selected' : '' }}>
                                    {{ $obra->obra }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="loading-stock" class="hidden">
                        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                </div>

                <div id="contenedor-stock">
                    <x-estadisticas.stock :nombre-meses="$nombreMeses" :stock-data="$stockData" :pedidos-por-diametro="$pedidosPorDiametro" :necesario-por-diametro="$necesarioPorDiametro"
                        :total-general="$totalGeneral" :consumo-origen="$consumoOrigen" :consumos-por-mes="$consumosPorMes" :producto-base-info="$productoBaseInfo" :stock-por-producto-base="$stockPorProductoBase"
                        :kg-pedidos-por-producto-base="$kgPedidosPorProductoBase" :resumen-reposicion="$resumenReposicion" :recomendacion-reposicion="$recomendacionReposicion" :configuracion_vista_stock="$configuracion_vista_stock" />
                </div>
            </div>

            {{-- MODAL COLADAS / BULTOS PARA ACTIVACIÓN --}}
            <div id="modal-coladas-activacion"
                class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm hidden items-center justify-center z-50 transition-all duration-300">
                <div
                    class="bg-white rounded-2xl w-full max-w-3xl shadow-2xl transform transition-all duration-300 overflow-hidden border border-gray-200">
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-slate-700 to-slate-800 px-6 py-5 border-b border-slate-600">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Confirmar activación de línea
                        </h3>
                        <p class="text-sm text-slate-300 mt-2">
                            Registrar coladas y bultos asociados (opcional)
                        </p>
                    </div>

                    {{-- Body --}}
                    <div class="p-6">
                        <div class="bg-blue-50 border-l-4 border-blue-500 px-4 py-3 rounded-r mb-5">
                            <p class="text-sm text-blue-800 leading-relaxed">
                                <strong class="font-semibold">Información:</strong> Puedes añadir cero o más coladas y
                                bultos.
                                Si no necesitas registrar información, deja la tabla vacía y confirma la activación.
                            </p>
                        </div>

                        <div class="border border-gray-300 rounded-xl mb-5 shadow-sm bg-white overflow-hidden">
                            <table class="w-full text-sm table-fixed">
                                <colgroup>
                                    <col style="width:45%">
                                    <col style="width:40%">
                                    <col style="width:15%">
                                </colgroup>
                                <thead class="bg-gradient-to-r from-gray-700 to-gray-800 text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider text-xs">
                                            Colada</th>
                                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider text-xs">
                                            Bulto</th>
                                        <th
                                            class="px-4 py-3 text-center font-semibold uppercase tracking-wider text-xs whitespace-nowrap">
                                            Acciones</th>
                                    </tr>
                                </thead>
                            </table>
                            <div class="max-h-72 overflow-y-auto">
                                <table class="w-full text-sm table-fixed">
                                    <colgroup>
                                        <col style="width:45%">
                                        <col style="width:40%">
                                        <col style="width:15%">
                                    </colgroup>
                                    <tbody id="tabla-coladas-body" class="divide-y divide-gray-200">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mb-6 pt-2">
                            <button type="button" id="btn-agregar-colada"
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-medium px-4 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Añadir colada / bulto
                            </button>
                        </div>

                        {{-- Footer con botones --}}
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                            <button type="button" id="btn-cancelar-coladas"
                                class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-700 text-sm font-medium px-5 py-2.5 rounded-lg border border-gray-300 transition-all duration-200 shadow-sm hover:shadow">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Cancelar
                            </button>
                            <button type="button" id="btn-confirmar-activacion-coladas"
                                class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Confirmar activación
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MODAL CONFIRMACIÓN PEDIDO --}}
            <div id="modalConfirmacion"
                class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                <div class="bg-white p-6 rounded-lg w-full max-w-5xl shadow-xl">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 text-left">Confirmar pedido</h3>

                    <form id="formularioPedido" action="{{ route('pedidos.store') }}" method="POST" class="space-y-4">
                        @csrf

                        <div class="text-left">
                            <label for="fabricante" class="block text-sm font-medium text-gray-700 mb-1">
                                Seleccionar fabricante:
                            </label>
                            <select name="fabricante_id" id="fabricante"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                                <option value="">-- Elige un fabricante --</option>
                                @foreach ($fabricantes as $fabricante)
                                    <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="text-left mt-4">
                            <label for="distribuidor" class="block text-sm font-medium text-gray-700 mb-1">
                                Seleccionar distribuidor:
                            </label>
                            <select name="distribuidor_id" id="distribuidor"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                                <option value="">-- Elige un distribuidor --</option>
                                @foreach ($distribuidores as $distribuidor)
                                    <option value="{{ $distribuidor->id }}">{{ $distribuidor->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="text-left">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lugar de Entrega:</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Naves de Hierros Paco
                                        Reyes</label>
                                    <select name="obra_id_hpr" id="obra_id_hpr_modal"
                                        class="w-full border border-gray-300 rounded px-3 py-2"
                                        onchange="limpiarObraManual()">
                                        <option value="">Seleccionar nave</option>
                                        @foreach ($navesHpr as $nave)
                                            <option value="{{ $nave->id }}">{{ $nave->obra }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Obras Externas
                                        (activas)</label>
                                    <select name="obra_id_externa" id="obra_id_externa_modal"
                                        class="w-full border border-gray-300 rounded px-3 py-2"
                                        onchange="limpiarObraManual()">
                                        <option value="">Seleccionar obra externa</option>
                                        @foreach ($obrasExternas as $obra)
                                            <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Otra ubicación (texto
                                        libre)</label>
                                    <input type="text" name="obra_manual" id="obra_manual_modal"
                                        class="w-full border border-gray-300 rounded px-3 py-2"
                                        placeholder="Escribir dirección manualmente" oninput="limpiarSelectsObra()"
                                        value="{{ old('obra_manual') }}">
                                </div>
                            </div>
                        </div>

                        <div class="max-h-[60vh] overflow-auto rounded-lg shadow-xl border border-gray-300">
                            <table class="w-full border-collapse text-sm text-center">
                                <thead class="bg-blue-800 text-white sticky top-0 z-10">
                                    <tr class="bg-gray-700 text-white">
                                        <th class="border px-2 py-1">Tipo</th>
                                        <th class="border px-2 py-1">Diámetro</th>
                                        <th class="border px-2 py-1">Peso Total (kg)</th>
                                        <th class="border px-2 py-1">Desglose Camiones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaConfirmacionBody"></tbody>
                            </table>
                        </div>

                        <div id="mensajesGlobales" class="mt-2 text-sm space-y-1"></div>

                        <div class="text-right pt-4">
                            <button type="button" onclick="cerrarModalConfirmacion()"
                                class="mr-2 px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                Crear Pedido de Compra
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </div>
    @endif

    {{-- ROL OPERARIO --}}
    @if (Auth::user()->rol === 'operario')
        <div class="p-4 w-full max-w-4xl mx-auto">
            <div class="px-4 flex justify-center">
                <form method="GET" action="{{ route('pedidos.index') }}"
                    class="w-full sm:w-2/3 md:w-1/2 lg:w-1/3 flex flex-col sm:flex-row gap-2 mb-6">
                    <x-tabla.input name="codigo" value="{{ request('codigo') }}" class="flex-grow"
                        placeholder="Introduce el código del pedido (ej: PC25/0003)" />
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 rounded-xl shadow transition">
                        🔍 Buscar
                    </button>
                </form>
            </div>

            @php
                $codigo = request('codigo');
                $pedidosFiltrados = $codigo
                    ? \App\Models\Pedido::with('productos')
                        ->where('codigo', 'like', '%' . $codigo . '%')
                        ->orderBy('created_at', 'desc')
                        ->get()
                    : collect();
            @endphp

            @if ($codigo)
                @if ($pedidosFiltrados->isEmpty())
                    <div class="text-red-500 text-sm text-center">
                        No se encontraron pedidos con el código <strong>{{ $codigo }}</strong>.
                    </div>
                @else
                    {{-- Vista móvil --}}
                    <div class="grid gap-4 sm:hidden">
                        @foreach ($pedidosFiltrados as $pedido)
                            <div class="bg-white shadow rounded-lg p-4 text-sm border">
                                <div><span class="font-semibold">Código:</span> {{ $pedido->codigo }}</div>
                                <div><span class="font-semibold">Fabricante:</span>
                                    {{ $pedido->fabricante->nombre ?? '—' }}</div>
                                <div><span class="font-semibold">Estado:</span> {{ $pedido->estado ?? '—' }}</div>
                                <div class="mt-2">
                                    <a href="{{ route('pedidos.crearRecepcion', $pedido->id) }}" wire:navigate
                                        class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded text-xs">
                                        Recepcionar
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Vista escritorio --}}
                    <div class="hidden sm:block bg-white shadow rounded-lg overflow-x-auto mt-4">
                        <table class="w-full border text-sm text-center">
                            <thead class="bg-blue-600 text-white uppercase text-xs">
                                <tr>
                                    <th class="px-3 py-2 border">Código</th>
                                    <th class="px-3 py-2 border">Fabricante</th>
                                    <th class="px-3 py-2 border">Estado</th>
                                    <th class="px-3 py-2 border">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pedidosFiltrados as $pedido)
                                    <tr class="border-b hover:bg-blue-50">
                                        <td class="px-3 py-2">{{ $pedido->codigo }}</td>
                                        <td class="px-3 py-2">{{ $pedido->fabricante->nombre ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $pedido->estado ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route('pedidos.recepcion', $pedido->id) }}" wire:navigate
                                                class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded text-xs">
                                                Recepcionar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    @endif
    </div>

    {{-- ==================== SCRIPTS ==================== --}}

    {{-- Script: Confirmar completar línea --}}
    <script>
        function confirmarCompletarLinea(form) {
            Swal.fire({
                title: '¿Completar línea?',
                html: 'Se marcará como <b>completada</b> sin recepcionar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, completar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a',
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }
    </script>

    {{-- Script: Confirmar cancelación de línea --}}
    <script>
        function confirmarCancelacionLinea(pedidoId, lineaId) {
            Swal.fire({
                title: '¿Cancelar línea?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6b7280',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'Volver',
            }).then((result) => {
                if (result.isConfirmed) {
                    const formulario = document.querySelector(
                        `.form-cancelar-linea[data-pedido-id="${pedidoId}"][data-linea-id="${lineaId}"]`
                    );
                    if (formulario) {
                        formulario.submit();
                    }
                }
            });
        }
    </script>

    {{-- Script: Limpiar campos del modal --}}
    <script>
        function limpiarObraManual() {
            document.getElementById('obra_manual_modal').value = '';
        }

        function limpiarSelectsObra() {
            document.getElementById('obra_id_hpr_modal').selectedIndex = 0;
            document.getElementById('obra_id_externa_modal').selectedIndex = 0;
        }
    </script>

    {{-- Script: Edición unificada de línea --}}
    <script>
        // ========== EDICIÓN UNIFICADA DE LÍNEA (LUGAR + PRODUCTO) ==========

        function abrirEdicionLinea(lineaId) {
            const lugarView = document.querySelector(`.lugar-entrega-view-${lineaId}`);
            const productoView = document.querySelector(`.producto-view-${lineaId}`);
            const lugarEdit = document.querySelector(`.lugar-entrega-edit-${lineaId}`);
            const productoEdit = document.querySelector(`.producto-edit-${lineaId}`);

            const btnEditar = document.querySelector(`.btn-editar-linea-${lineaId}`);
            const btnGuardar = document.querySelector(`.btn-guardar-linea-${lineaId}`);
            const btnCancelar = document.querySelector(`.btn-cancelar-edicion-${lineaId}`);
            const botonesEstado = document.querySelector(`.botones-estado-${lineaId}`);

            if (lugarView) lugarView.classList.add('hidden');
            if (productoView) productoView.classList.add('hidden');
            if (lugarEdit) lugarEdit.classList.remove('hidden');
            if (productoEdit) productoEdit.classList.remove('hidden');

            if (btnEditar) btnEditar.classList.add('hidden');
            if (btnGuardar) btnGuardar.classList.remove('hidden');
            if (btnCancelar) btnCancelar.classList.remove('hidden');
            if (botonesEstado) botonesEstado.classList.add('hidden');

            if (lugarEdit) {
                configurarSelectsLugar(lugarEdit);
            }
        }

        function cancelarEdicionLinea(lineaId) {
            const lugarView = document.querySelector(`.lugar-entrega-view-${lineaId}`);
            const productoView = document.querySelector(`.producto-view-${lineaId}`);
            const lugarEdit = document.querySelector(`.lugar-entrega-edit-${lineaId}`);
            const productoEdit = document.querySelector(`.producto-edit-${lineaId}`);

            const btnEditar = document.querySelector(`.btn-editar-linea-${lineaId}`);
            const btnGuardar = document.querySelector(`.btn-guardar-linea-${lineaId}`);
            const btnCancelar = document.querySelector(`.btn-cancelar-edicion-${lineaId}`);
            const botonesEstado = document.querySelector(`.botones-estado-${lineaId}`);

            if (lugarView) lugarView.classList.remove('hidden');
            if (productoView) productoView.classList.remove('hidden');
            if (lugarEdit) lugarEdit.classList.add('hidden');
            if (productoEdit) productoEdit.classList.add('hidden');

            if (btnEditar) btnEditar.classList.remove('hidden');
            if (btnGuardar) btnGuardar.classList.add('hidden');
            if (btnCancelar) btnCancelar.classList.add('hidden');
            if (botonesEstado) botonesEstado.classList.remove('hidden');
        }

        function guardarLinea(lineaId, pedidoId) {
            const lugarEdit = document.querySelector(`.lugar-entrega-edit-${lineaId}`);
            const selectHpr = lugarEdit.querySelector('.obra-hpr-select');
            const selectExterna = lugarEdit.querySelector('.obra-externa-select');
            const inputManual = lugarEdit.querySelector('.obra-manual-input');

            const obraHpr = selectHpr.value;
            const obraExterna = selectExterna.value;
            const obraManual = inputManual.value.trim();
            const totalSeleccionado = [obraHpr, obraExterna, obraManual].filter(v => v).length;

            if (totalSeleccionado === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debes seleccionar un lugar de entrega'
                });
                return;
            }

            if (totalSeleccionado > 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Solo puedes seleccionar una opción de lugar de entrega'
                });
                return;
            }

            const productoEdit = document.querySelector(`.producto-edit-${lineaId}`);
            const selectProducto = productoEdit.querySelector('.producto-base-select');
            const productoBaseId = selectProducto.value;

            if (!productoBaseId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debes seleccionar un producto'
                });
                return;
            }

            const datos = {
                linea_id: lineaId,
                obra_id: obraHpr || obraExterna || null,
                obra_manual: obraManual || null,
                producto_base_id: productoBaseId
            };

            fetch(`/pedidos/${pedidoId}/actualizar-linea`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(datos)
                })
                .then(response => {
                    // Verificar si la respuesta es JSON válido
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Respuesta no es JSON:', text);
                            throw new Error('La respuesta del servidor no es JSON válido');
                        });
                    }

                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || `Error del servidor: ${response.status}`);
                        });
                    }

                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: 'Línea actualizada correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al actualizar la línea'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Error al actualizar la línea'
                    });
                });
        }

        function configurarSelectsLugar(editDiv) {
            const selectHpr = editDiv.querySelector('.obra-hpr-select');
            const selectExterna = editDiv.querySelector('.obra-externa-select');
            const inputManual = editDiv.querySelector('.obra-manual-input');

            if (!selectHpr || !selectExterna || !inputManual) return;

            const newSelectHpr = selectHpr.cloneNode(true);
            const newSelectExterna = selectExterna.cloneNode(true);
            const newInputManual = inputManual.cloneNode(true);

            selectHpr.parentNode.replaceChild(newSelectHpr, selectHpr);
            selectExterna.parentNode.replaceChild(newSelectExterna, selectExterna);
            inputManual.parentNode.replaceChild(newInputManual, inputManual);

            newSelectHpr.addEventListener('change', function() {
                if (this.value) {
                    newSelectExterna.value = '';
                    newInputManual.value = '';
                }
            });

            newSelectExterna.addEventListener('change', function() {
                if (this.value) {
                    newSelectHpr.value = '';
                    newInputManual.value = '';
                }
            });

            newInputManual.addEventListener('input', function() {
                if (this.value.trim()) {
                    newSelectHpr.value = '';
                    newSelectExterna.value = '';
                }
            });
        }
    </script>

    {{-- Script: Modal de creación de pedidos y sugerencia de pedido global --}}
    <script>
        function debounce(fn, delay) {
            let timer;
            return function() {
                clearTimeout(timer);
                const args = arguments;
                const context = this;
                timer = setTimeout(() => fn.apply(context, args), delay);
            }
        }

        // Recolectar todas las líneas del modal
        function recolectarLineas() {
            const lineas = [];
            let globalIndex = 0;

            document.querySelectorAll('#tablaConfirmacionBody tr').forEach((tr) => {
                const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                if (!contenedorFechas) return;

                const clave = contenedorFechas.id.replace('fechas-camion-', '');
                const inputsPeso = contenedorFechas.querySelectorAll('input[type="hidden"][name*="[peso]"]');

                inputsPeso.forEach((pesoInput, subIndex) => {
                    const peso = parseFloat(pesoInput.value || 0);
                    if (peso <= 0) return;

                    lineas.push({
                        index: globalIndex++,
                        clave: clave,
                        cantidad: peso,
                        sublinea: subIndex + 1
                    });
                });
            });

            return lineas;
        }

        // Sugerir pedidos globales disponibles
        function dispararSugerirMultiple() {
            const fabricante = document.getElementById('fabricante').value;
            const distribuidor = document.getElementById('distribuidor').value;
            if (!fabricante && !distribuidor) return;

            const lineas = recolectarLineas();
            if (lineas.length === 0) return;

            fetch('{{ route('pedidos.verSugerir-pedido-global') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        fabricante_id: fabricante,
                        distribuidor_id: distribuidor,
                        lineas: lineas
                    })
                })
                .then(r => r.json())
                .then(data => {
                    const mensajesGlobales = document.getElementById('mensajesGlobales');
                    mensajesGlobales.innerHTML = '';

                    // Limpiar asignaciones previas
                    document.querySelectorAll('[class*="pg-asignacion-"]').forEach(div => {
                        div.innerHTML = '<span class="text-gray-400">Sin asignar</span>';
                    });

                    if (data.mensaje) {
                        const div = document.createElement('div');
                        div.className = 'text-yellow-700 font-medium';
                        div.textContent = data.mensaje;
                        mensajesGlobales.appendChild(div);
                    }

                    // Procesar asignaciones
                    (data.asignaciones || []).forEach(asig => {
                        if (asig.linea_index !== null && asig.linea_index !== undefined) {
                            let encontrado = false;
                            let globalIdx = 0;

                            document.querySelectorAll('#tablaConfirmacionBody tr').forEach((tr) => {
                                if (encontrado) return;

                                const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                                if (!contenedorFechas) return;

                                const clave = contenedorFechas.id.replace('fechas-camion-', '');
                                const inputsPeso = contenedorFechas.querySelectorAll(
                                    'input[type="hidden"][name*="[peso]"]');

                                inputsPeso.forEach((pesoInput, subIdx) => {
                                    if (encontrado) return;

                                    if (globalIdx === asig.linea_index) {
                                        encontrado = true;

                                        const divAsignacion = document.querySelector(
                                            `.pg-asignacion-${clave}-${subIdx}`);

                                        if (divAsignacion) {
                                            if (asig.codigo) {
                                                divAsignacion.innerHTML = `
                                            <div class="text-left">
                                                <div class="font-bold text-green-700 text-sm">${asig.codigo}</div>
                                                <div class="text-xs text-gray-600 mt-1">${asig.mensaje}</div>
                                                <div class="text-xs text-blue-600 mt-1 font-medium">
                                                    📦 Quedan ${asig.cantidad_restante.toLocaleString('es-ES')} kg
                                                </div>
                                            </div>
                                        `;
                                                divAsignacion.className =
                                                    `pg-asignacion-${clave}-${subIdx} text-xs p-2 bg-green-50 rounded border border-green-200 min-h-[60px]`;

                                                // Agregar input hidden para pedido_global_id
                                                const lineaCamion = document.getElementById(
                                                    `linea-camion-${clave}-${subIdx}`);
                                                if (lineaCamion) {
                                                    let inputPG = lineaCamion.querySelector(
                                                        `input[name="productos[${clave}][${subIdx + 1}][pedido_global_id]"]`
                                                    );
                                                    if (!inputPG) {
                                                        inputPG = document.createElement(
                                                            'input');
                                                        inputPG.type = 'hidden';
                                                        inputPG.name =
                                                            `productos[${clave}][${subIdx + 1}][pedido_global_id]`;
                                                        lineaCamion.appendChild(inputPG);
                                                    }
                                                    inputPG.value = asig.pedido_global_id;
                                                }
                                            } else {
                                                divAsignacion.innerHTML =
                                                    `<div class="text-red-600 text-left">${asig.mensaje}</div>`;
                                                divAsignacion.className =
                                                    `pg-asignacion-${clave}-${subIdx} text-xs p-2 bg-red-50 rounded border border-red-200 min-h-[60px]`;
                                            }
                                        }
                                    }

                                    globalIdx++;
                                });
                            });
                        } else if (asig.mensaje) {
                            const div = document.createElement('div');
                            div.className = 'text-yellow-700 font-medium';
                            div.textContent = asig.mensaje;
                            mensajesGlobales.appendChild(div);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error al sugerir pedido global:', error);
                });
        }

        // Mostrar modal de confirmación
        function mostrarConfirmacion() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
            const tbody = document.getElementById('tablaConfirmacionBody');
            tbody.innerHTML = '';

            checkboxes.forEach((cb) => {
                const clave = cb.value;
                const tipo = document.querySelector(`input[name="detalles[${clave}][tipo]"]`).value;
                const diametro = document.querySelector(`input[name="detalles[${clave}][diametro]"]`).value;
                const cantidad = parseFloat(document.querySelector(`input[name="detalles[${clave}][cantidad]"]`)
                    .value);
                const longitudInput = document.querySelector(`input[name="detalles[${clave}][longitud]"]`);
                const longitud = longitudInput ? longitudInput.value : null;

                const fila = document.createElement('tr');
                fila.className = "bg-gray-100 border-b-2 border-gray-400";

                fila.innerHTML = `
                <td class="border px-2 py-2 align-top font-semibold">
                    ${tipo.charAt(0).toUpperCase() + tipo.slice(1)}
                </td>
                <td class="border px-2 py-2 align-top font-semibold">
                    ${diametro} mm${longitud ? ` / ${longitud} m` : ''}
                </td>
                <td class="border px-2 py-2 align-top">
                    <input type="number" class="peso-total w-full px-2 py-1 border rounded font-semibold"
                           name="detalles[${clave}][cantidad]" value="${cantidad}" step="2500" min="2500">
                </td>
                <td class="border px-2 py-2 align-top">
                    <div class="fechas-camion flex flex-col gap-2 w-full" id="fechas-camion-${clave}"></div>
                </td>
                <input type="hidden" name="seleccionados[]" value="${clave}">
                <input type="hidden" name="detalles[${clave}][tipo]" value="${tipo}">
                <input type="hidden" name="detalles[${clave}][diametro]" value="${diametro}">
                ${longitud ? `<input type="hidden" name="detalles[${clave}][longitud]" value="${longitud}">` : ''}
            `;
                tbody.appendChild(fila);

                const inputPeso = fila.querySelector('.peso-total');
                generarFechasPorPeso(inputPeso, clave);
            });

            dispararSugerirMultiple();
            document.getElementById('modalConfirmacion').classList.remove('hidden');
            document.getElementById('modalConfirmacion').classList.add('flex');
        }

        // Generar inputs de fecha según el peso
        function generarFechasPorPeso(input, clave) {
            const peso = parseFloat(input.value || 0);
            const contenedorFechas = document.getElementById(`fechas-camion-${clave}`);
            if (!contenedorFechas) return;

            contenedorFechas.innerHTML = '';

            const bloques = Math.ceil(peso / 25000);
            for (let i = 0; i < bloques; i++) {
                const pesoBloque = Math.min(25000, peso - i * 25000);

                const lineaCamion = document.createElement('div');
                lineaCamion.className = 'flex items-center gap-2 p-2 bg-white rounded border border-gray-200';
                lineaCamion.id = `linea-camion-${clave}-${i}`;

                lineaCamion.innerHTML = `
                <div class="flex flex-col gap-1 flex-1">
                    <label class="text-xs text-gray-600 font-medium">Camión ${i + 1} - ${pesoBloque.toLocaleString('es-ES')} kg</label>
                    <input type="date" 
                           name="productos[${clave}][${i + 1}][fecha]" 
                           required 
                           class="border px-2 py-1 rounded text-sm w-full">
                    <input type="hidden" 
                           name="productos[${clave}][${i + 1}][peso]" 
                           value="${pesoBloque}">
                </div>
                <div class="flex-1">
                    <div class="pg-asignacion-${clave}-${i} text-xs p-2 bg-gray-50 rounded border min-h-[60px] flex items-center justify-center">
                        <span class="text-gray-400">Selecciona fabricante/distribuidor</span>
                    </div>
                </div>
            `;

                contenedorFechas.appendChild(lineaCamion);
            }
        }

        // Cerrar modal
        function cerrarModalConfirmacion() {
            document.getElementById('modalConfirmacion').classList.add('hidden');
            document.getElementById('modalConfirmacion').classList.remove('flex');
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Listener para cambios en peso
            document.addEventListener('input', debounce((ev) => {
                const inputPeso = ev.target.closest('.peso-total');
                if (!inputPeso) return;

                const tr = inputPeso.closest('tr');
                const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                if (!contenedorFechas) return;

                const clave = contenedorFechas.id.replace('fechas-camion-', '');
                generarFechasPorPeso(inputPeso, clave);
                dispararSugerirMultiple();
            }, 300));

            // Listeners para fabricante/distribuidor
            const fabricanteSelect = document.getElementById('fabricante');
            const distribuidorSelect = document.getElementById('distribuidor');

            if (fabricanteSelect) {
                fabricanteSelect.addEventListener('change', dispararSugerirMultiple);
            }
            if (distribuidorSelect) {
                distribuidorSelect.addEventListener('change', dispararSugerirMultiple);
            }
        });
    </script>

    {{-- Script: Validación formulario pedido --}}
    <script>
        document.getElementById('formularioPedido').addEventListener('submit', function(ev) {
            ev.preventDefault();
            const errores = [];

            const fabricante = document.getElementById('fabricante').value;
            const distribuidor = document.getElementById('distribuidor').value;
            if (!fabricante && !distribuidor) {
                errores.push('Debes seleccionar un fabricante o un distribuidor.');
            }
            if (fabricante && distribuidor) {
                errores.push('Solo puedes seleccionar uno: fabricante o distribuidor.');
            }

            const obraHpr = document.getElementById('obra_id_hpr_modal').value;
            const obraExterna = document.getElementById('obra_id_externa_modal').value;
            const obraManual = document.getElementById('obra_manual_modal').value.trim();
            const totalObras = [obraHpr, obraExterna, obraManual].filter(v => v && v !== '').length;
            if (totalObras === 0) {
                errores.push('Debes seleccionar una nave, obra externa o escribir un lugar de entrega.');
            }
            if (totalObras > 1) {
                errores.push('Solo puedes seleccionar una opción: nave, obra externa o introducirla manualmente.');
            }

            const resumenLineas = [];
            document.querySelectorAll('#tablaConfirmacionBody tr').forEach(tr => {
                const tipo = tr.querySelector('td:nth-child(1)')?.textContent.trim();
                const diametro = tr.querySelector('td:nth-child(2)')?.textContent.trim().replace(' mm', '')
                    .split('/')[0].trim();
                const peso = parseFloat(tr.querySelector('.peso-total')?.value || 0);

                if (tipo && diametro) {
                    if (peso <= 0) {
                        errores.push(`El peso de la línea ${tipo} ${diametro} debe ser mayor a 0.`);
                    }

                    const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                    const fechas = [];

                    if (contenedorFechas) {
                        const inputsFecha = contenedorFechas.querySelectorAll('input[type="date"]');
                        inputsFecha.forEach((input, idx) => {
                            if (!input.value) {
                                errores.push(
                                    `Completa la fecha del camión ${idx + 1} para ${tipo} Ø${diametro}.`
                                );
                            }
                            fechas.push(input.value || '—');
                        });
                    }

                    resumenLineas.push({
                        tipo,
                        diametro,
                        peso,
                        fechas
                    });
                }
            });

            if (resumenLineas.length === 0) {
                errores.push('Debes seleccionar al menos un producto para generar el pedido.');
            }

            if (errores.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Revisa los datos',
                    html: '<ul style="text-align:left;">' + errores.map(e => `<li>• ${e}</li>`).join('') +
                        '</ul>'
                });
                return false;
            }

            let proveedorTexto = fabricante ?
                `Fabricante: ${document.querySelector('#fabricante option:checked').textContent}` :
                `Distribuidor: ${document.querySelector('#distribuidor option:checked').textContent}`;

            let obraTexto = obraHpr ?
                `Nave: ${document.querySelector('#obra_id_hpr_modal option:checked').textContent}` :
                obraExterna ?
                `Obra externa: ${document.querySelector('#obra_id_externa_modal option:checked').textContent}` :
                `Lugar manual: ${obraManual}`;

            let htmlResumen =
                `<p><b>${proveedorTexto}</b></p><p><b>${obraTexto}</b></p><hr><ul style="text-align:left;">`;
            resumenLineas.forEach(l => {
                htmlResumen += `<li>• ${l.tipo} Ø${l.diametro} → ${l.peso.toLocaleString('es-ES')} kg<br>` +
                    `📅 Fechas de entrega: ${l.fechas.join(', ')}</li>`;
            });
            htmlResumen += '</ul>';

            Swal.fire({
                title: '¿Crear pedido de compra?',
                html: htmlResumen,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, crear pedido',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a',
                focusCancel: true,
                width: 600,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    ev.target.submit();
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectObra = document.getElementById('obra_id_hpr_stock');
            const contenedorStock = document.getElementById('contenedor-stock');
            const loadingIndicator = document.getElementById('loading-stock');

            if (!selectObra || !contenedorStock) {
                return;
            }

            selectObra.addEventListener('change', function() {
                const obraId = this.value;

                // Mostrar loading
                if (loadingIndicator) loadingIndicator.classList.remove('hidden');
                contenedorStock.style.opacity = '0.5';
                contenedorStock.style.pointerEvents = 'none';

                // URL de la petición
                const url = '{{ route('pedidos.verStockHtml') }}' + (obraId ? '?obra_id_hpr=' + obraId :
                    '');

                fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Error en la petición');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.html) {
                            contenedorStock.innerHTML = data.html;
                            contenedorStock.style.opacity = '1';
                        } else {
                            throw new Error(data.message || 'Error desconocido');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo actualizar la tabla de stock',
                            confirmButtonColor: '#3b82f6'
                        });
                        contenedorStock.style.opacity = '1';
                    })
                    .finally(() => {
                        if (loadingIndicator) loadingIndicator.classList.add('hidden');
                        contenedorStock.style.pointerEvents = 'auto';
                    });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const CLIENTE_ID_REQUIERE_COLADAS = 1;
            const BASE_PEDIDOS_URL = `{{ url('/pedidos') }}`;
            const CLASES_ESTADO_A_REMOVER = [
                'bg-yellow-100',
                'bg-green-500',
                'bg-green-100',
                'bg-gray-300',
                'text-gray-500',
                'opacity-70',
                'cursor-not-allowed',
                'even:bg-gray-50',
                'odd:bg-white',
                'bg-white',
                'bg-gray-50'
            ];
            const CLASES_PENDIENTE = ['even:bg-gray-50', 'odd:bg-white'];

            const modal = document.getElementById('modal-coladas-activacion');
            const cuerpoTabla = document.getElementById('tabla-coladas-body');
            const btnAgregar = document.getElementById('btn-agregar-colada');
            const btnCancelar = document.getElementById('btn-cancelar-coladas');
            const btnConfirmar = document.getElementById('btn-confirmar-activacion-coladas');

            let pedidoIdActual = null;
            let lineaIdActual = null;
            let formularioActivacionActual = null;

            function obtenerFilaLinea(pedidoId, lineaId) {
                return document.querySelector(
                    `.fila-pedido-linea[data-pedido-id="${pedidoId}"][data-linea-id="${lineaId}"]`);
            }

            function actualizarEstadoVisualLinea(pedidoId, lineaId, nuevoEstado, clasesAgregar = [], filaElement =
                null) {
                const fila = filaElement || obtenerFilaLinea(pedidoId, lineaId);
                if (!fila) {
                    console.warn(`Fila no encontrada para pedido ${pedidoId} linea ${lineaId}`);
                    return;
                }

                // Remover clases una por una
                CLASES_ESTADO_A_REMOVER.forEach(clase => {
                    fila.classList.remove(clase);
                });

                // Agregar nuevas clases
                clasesAgregar.forEach(clase => {
                    if (clase) {
                        fila.classList.add(clase);
                    }
                });

                const estadoCelda = fila.querySelector('[data-columna-estado]');
                if (estadoCelda) {
                    estadoCelda.textContent = nuevoEstado;
                }
            }

            function toggleBotonesLinea(fila) {
                const formActivar = fila.querySelector('.form-activar-linea');
                const formDesactivar = fila.querySelector('.form-desactivar-linea');

                if (formActivar) formActivar.classList.toggle('hidden');
                if (formDesactivar) formDesactivar.classList.toggle('hidden');
            }

            function desactivarLinea(form) {
                const pedidoId = form.getAttribute('data-pedido-id');
                const lineaId = form.getAttribute('data-linea-id');
                if (!pedidoId || !lineaId) {
                    form.submit();
                    return;
                }

                const formData = new FormData(form);
                if (!formData.has('_token')) {
                    formData.append('_token', obtenerTokenCsrf());
                }
                if (!formData.has('_method')) {
                    formData.append('_method', 'DELETE');
                }

                fetch(form.getAttribute('action'), {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })
                    .then(response => response.json().then(data => ({
                        ok: response.ok,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        if (!ok || !data.success) {
                            const mensaje = data && data.message ? data.message :
                                'Error al desactivar la línea.';
                            throw new Error(mensaje);
                        }

                        const fila = form.closest('tr');
                        actualizarEstadoVisualLinea(pedidoId, lineaId, 'pendiente', CLASES_PENDIENTE, fila);
                        toggleBotonesLinea(fila);

                        Swal.fire({
                            icon: 'success',
                            title: data.message || 'Línea desactivada correctamente.',
                            showConfirmButton: false,
                            timer: 1800,
                        });
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Error al desactivar la línea.',
                        });
                    });
            }

            function abrirModalColadas(pedidoId, lineaId, form = null) {
                pedidoIdActual = pedidoId;
                lineaIdActual = lineaId;
                formularioActivacionActual = form;

                cuerpoTabla.innerHTML = '';
                agregarFilaColada();

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function cerrarModalColadas(limpiarFormulario = false) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                pedidoIdActual = null;
                lineaIdActual = null;
                if (limpiarFormulario) {
                    formularioActivacionActual = null;
                }
            }

            function agregarFilaColada() {
                const fila = document.createElement('tr');
                fila.className = 'fila-colada hover:bg-gray-50 transition-colors duration-150';
                fila.innerHTML = `
                    <td class="px-4 py-3">
                        <input type="text" class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg px-3 py-2 text-sm input-colada transition-all duration-200 outline-none" placeholder="Ej: 12/3456">
                    </td>
                    <td class="px-4 py-3">
                        <input type="number" step="1" min="0" class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg px-3 py-2 text-sm input-bulto transition-all duration-200 outline-none" placeholder="0">
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button type="button" class="btn-eliminar-colada inline-flex items-center justify-center bg-red-500 hover:bg-red-600 active:bg-red-700 text-white rounded-lg w-8 h-8 transition-all duration-200 shadow-sm hover:shadow transform hover:scale-105" title="Eliminar fila">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </td>
                `;
                cuerpoTabla.appendChild(fila);
            }

            if (btnAgregar) {
                btnAgregar.addEventListener('click', function() {
                    agregarFilaColada();
                });
            }

            if (cuerpoTabla) {
                cuerpoTabla.addEventListener('click', function(ev) {
                    const botonEliminar = ev.target.closest('.btn-eliminar-colada');
                    if (botonEliminar) {
                        const fila = botonEliminar.closest('tr');
                        if (fila) {
                            fila.remove();
                        }
                    }
                });
            }

            if (btnCancelar) {
                btnCancelar.addEventListener('click', function() {
                    cerrarModalColadas(true);
                });
            }

            function obtenerTokenCsrf() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) {
                    return meta.getAttribute('content');
                }
                const input = document.querySelector('input[name="_token"]');
                return input ? input.value : '';
            }

            function activarLineaConColadas() {
                if (!pedidoIdActual || !lineaIdActual) {
                    return;
                }

                const filas = cuerpoTabla.querySelectorAll('.fila-colada');
                const coladas = [];

                filas.forEach(fila => {
                    const coladaInput = fila.querySelector('.input-colada');
                    const bultoInput = fila.querySelector('.input-bulto');
                    const colada = coladaInput ? coladaInput.value.trim() : '';
                    const bultoValor = bultoInput ? bultoInput.value.trim() : '';

                    if (colada !== '' || bultoValor !== '') {
                        const bulto = bultoValor !== '' ? parseFloat(bultoValor.replace(',', '.')) : null;
                        coladas.push({
                            colada: colada !== '' ? colada : null,
                            bulto: bulto,
                        });
                    }
                });

                const url = `{{ url('/pedidos') }}/${pedidoIdActual}/lineas/${lineaIdActual}/activar-con-coladas`;

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': obtenerTokenCsrf(),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            coladas
                        }),
                    })
                    .then(response => response.json().then(data => ({
                        ok: response.ok,
                        status: response.status,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        if (!ok || !data.success) {
                            const mensaje = data && data.message ? data.message : 'Error al activar la línea.';
                            throw new Error(mensaje);
                        }

                        cerrarModalColadas();

                        let form = formularioActivacionActual;
                        if (!form) {
                            const formSelector =
                                `.form-activar-linea[data-pedido-id="${pedidoIdActual}"][data-linea-id="${lineaIdActual}"]`;
                            form = document.querySelector(formSelector);
                        }

                        if (form) {
                            const fila = form.closest('tr');
                            actualizarEstadoVisualLinea(pedidoIdActual, lineaIdActual, 'activo', [
                                'bg-yellow-100'
                            ], fila);
                            toggleBotonesLinea(fila);
                            formularioActivacionActual = null;
                        } else {
                            // Fallback si no encontramos el form, intentamos buscar la fila por ID
                            actualizarEstadoVisualLinea(pedidoIdActual, lineaIdActual, 'activo', [
                                'bg-yellow-100'
                            ]);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: data.message || 'Línea activada correctamente.',
                            showConfirmButton: false,
                            timer: 1800,
                        });
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Error al activar la línea.',
                        });
                    });
            }

            if (btnConfirmar) {
                btnConfirmar.addEventListener('click', function() {
                    activarLineaConColadas();
                });
            }

            document.addEventListener('submit', function(ev) {
                const form = ev.target.closest('form');
                if (!form) {
                    return;
                }

                if (form.classList.contains('form-desactivar-linea')) {
                    ev.preventDefault();
                    desactivarLinea(form);
                    return;
                }

                if (form.classList.contains('form-activar-linea')) {
                    const clienteId = parseInt(form.getAttribute('data-cliente-id') || '0', 10);

                    if (clienteId === CLIENTE_ID_REQUIERE_COLADAS) {
                        ev.preventDefault();
                        const pedidoId = form.getAttribute('data-pedido-id');
                        const lineaId = form.getAttribute('data-linea-id');
                        abrirModalColadas(pedidoId, lineaId, form);
                    }
                }
            });
        });
    </script>
</x-app-layout>
