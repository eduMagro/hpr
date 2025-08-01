<x-app-layout>
    <x-slot name="title">Pedidos Globales - {{ config('app.name') }}</x-slot>
    <x-menu.materiales />

    <div class="px-4 py-6">

        <button onclick="abrirModalPedidoGlobal()"
            class="px-4 py-2 mb-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            ➕ Crear Pedido Global
        </button>
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <div class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="w-full border-collapse text-sm text-center">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-xs uppercase">
                        <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código' !!}</th>
                        <th class="p-2 border">{!! $ordenables['fabricante'] ?? 'Fabricante' !!}</th>
                        <th class="p-2 border">{!! $ordenables['cantidad_total'] ?? 'Cantidad Total' !!}</th>
                        <th class="p-2 border">Cantidad Restante</th>
                        <th class="p-2 border">Progreso</th>
                        <th class="p-2 border">{!! $ordenables['estado'] ?? 'Estado' !!}</th>
                        <th class="p-2 border">Creación Registro</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                    <tr class="text-xs uppercase">
                        <form method="GET" action="{{ route('pedidos_globales.index') }}">
                            <th class="p-1 border">
                                <x-tabla.input name="codigo" type="text" :value="request('codigo')" class="w-full text-xs" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.input name="fabricante" type="text" :value="request('fabricante')"
                                    class="w-full text-xs" />
                            </th>

                            <th class="p-1 border"></th>
                            <th class="p-1 border"></th>
                            <th class="p-1 border"></th>

                            <th class="p-1 border">
                                <x-tabla.select name="estado" :options="[
                                    'pendiente' => 'Pendiente',
                                    'en curso' => 'En curso',
                                    'completado' => 'Completado',
                                    'cancelado' => 'Cancelado',
                                ]" :selected="request('estado')" empty="Todos"
                                    class="w-full text-xs" />
                            </th>

                            <th class="p-1 border"></th>
                            <x-tabla.botones-filtro ruta="pedidos_globales.index" />
                        </form>
                    </tr>

                </thead>

                <tbody>
                    @forelse ($pedidosGlobales as $pedido)
                        <tr tabindex="0" x-data="{
                            editando: false,
                            pedido: @js($pedido),
                            original: JSON.parse(JSON.stringify(@js($pedido)))
                        }"
                            @dblclick="if(!$event.target.closest('input')) {
                              if(!editando) {
                                editando = true;
                              } else {
                                planilla = JSON.parse(JSON.stringify(original));
                                editando = false;
                              }
                            }"
                            @keydown.enter.stop="guardarCambios(pedido); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                            <!-- Código (no editable) -->
                            <td class="p-2 border" x-text="pedido.codigo"></td>

                            <!-- Fabricante -->
                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.fabricante?.nombre ?? 'N/A' "></span>
                                </template>
                                <select x-show="editando" x-model="pedido.fabricante_id" class="form-input w-full">
                                    <option value="">Selecciona</option>
                                    @foreach ($fabricantes as $fab)
                                        <option value="{{ $fab->id }}">
                                            {{ $fab->nombre }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- Cantidad total -->
                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span
                                        x-text="Number(pedido.cantidad_total).toLocaleString('es-ES',
                                        {minimumFractionDigits:2}) + ' kg' "></span>
                                </template>
                                <input x-show="editando" type="number" step="0.01" x-model="pedido.cantidad_total"
                                    class="form-input w-full">
                            </td>

                            <!-- Cantidad acumulada (solo lectura) -->
                            <td class="p-2 border">
                                {{ number_format($pedido->cantidad_restante, 2, ',', '.') }} kg
                            </td>

                            <!-- Progreso (solo lectura) -->
                            <td class="p-2 border">
                                <div class="w-full bg-gray-200 rounded-full h-4">
                                    <div class="bg-blue-600 h-4 rounded-full text-white text-[10px] text-center"
                                        style="width: {{ $pedido->progreso }}%">
                                        {{ $pedido->progreso }}%
                                    </div>
                                </div>
                            </td>

                            <!-- Estado -->
                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.estado"></span>
                                </template>
                                <select x-show="editando" x-model="pedido.estado" class="form-input w-full">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="en curso">En curso</option>
                                    <option value="completado">Completado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </td>

                            <!-- Fecha Formateada -->
                            <td class="border px-3 py-2">{{ $pedido->fecha_creacion_formateada }}</td>

                            <!-- Acciones -->
                            <td class="p-2 border text-center">
                                <template x-if="editando">
                                    <button @click="guardarCambiosPedidoGlobal(pedido); editando=false"
                                        class="bg-green-500
                                hover:bg-green-600 text-white text-xs px-2 py-1 rounded shadow">
                                        Guardar
                                    </button>
                                </template>
                                <x-tabla.boton-eliminar :action="route('pedidos_globales.destroy', $pedido->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-gray-500 text-center">No hay pedidos globales
                                registrados.</td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $pedidosGlobales->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>
    <div id="modalPedidoGlobal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg relative">

            <h3 class="text-lg font-semibold text-gray-800 mb-4">Nuevo Pedido Global</h3>

            <form id="formPedidoGlobal">
                @csrf

                <div class="mb-3">
                    <label for="cantidad_total" class="block text-sm font-medium text-gray-700">Cantidad Total
                        (kg)</label>

                    <input type="number" name="cantidad_total" step="10000"
                        class="w-full border border-gray-300 rounded px-3 py-2" required>
                </div>

                <div class="mb-3">
                    <label for="fabricante_id" class="block text-sm font-medium text-gray-700">Fabricante</label>
                    <select name="fabricante_id" class="form-select w-full">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($fabricantes as $fabricante)
                            <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="text-right pt-4">
                    <button type="button" onclick="cerrarModalPedidoGlobal()"
                        class="mr-2 px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Crear Pedido Global
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function abrirModalPedidoGlobal() {
            document.getElementById('modalPedidoGlobal').classList.remove('hidden');
            document.getElementById('modalPedidoGlobal').classList.add('flex');
        }

        function cerrarModalPedidoGlobal() {
            document.getElementById('modalPedidoGlobal').classList.remove('flex');
            document.getElementById('modalPedidoGlobal').classList.add('hidden');
        }

        document.getElementById('formPedidoGlobal').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const data = new FormData(form);

            fetch("{{ route('pedidos_globales.store') }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json' // 🔑 esto fuerza a Laravel a responder JSON
                    },
                    body: new FormData(form)
                })
                .then(async res => {
                    const contentType = res.headers.get("content-type");

                    if (res.ok && contentType.includes("application/json")) {
                        const data = await res.json();
                        Swal.fire({
                            icon: 'success',
                            title: 'Pedido global creado',
                            text: 'Se ha guardado correctamente.',
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        const text = await res.text(); // 👀 respuesta no JSON
                        throw new Error("Error inesperado:\n" + text.slice(0, 300));
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: `<pre>${err.message}</pre>`,
                    });
                });

        });

        function guardarCambiosPedidoGlobal(pedido) {
            fetch(`{{ route('pedidos_globales.update', '') }}/${pedido.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(pedido)
                })
                .then(async response => {
                    const data = await response.json();

                    if (!response.ok) {
                        let mensaje = data.message || 'Error desconocido';
                        if (data.errors) {
                            mensaje = Object.values(data.errors).flat().join('\n');
                        }
                        throw new Error(mensaje);
                    }

                    Swal.fire({
                        icon: "success",
                        title: "Pedido global actualizado",
                        text: "Los cambios se han guardado correctamente.",
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(error => {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: error.message || 'No se pudo actualizar el pedido global. Inténtalo nuevamente.',
                    });
                });
        }
    </script>

</x-app-layout>
