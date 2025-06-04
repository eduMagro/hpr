<x-app-layout>
    <x-slot name="title">Pedidos Globales - {{ config('app.name') }}</x-slot>
    @php
        $rutaActual = request()->route()->getName();
    @endphp

    @if (auth()->user()->rol !== 'operario')
        <div class="w-full" x-data="{ open: false }">
            <!-- Menú móvil -->
            <div class="sm:hidden relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                    Opciones
                </button>

                <div x-show="open" x-transition @click.away="open = false"
                    class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                    x-cloak>

                    <a href="{{ route('entradas.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'entradas.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📦 Entradas de Material
                    </a>

                    <a href="{{ route('pedidos.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'pedidos.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🛒 Pedidos de Compra
                    </a>

                    <a href="{{ route('pedidos_globales.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'pedidos_globales.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🌐 Pedidos Globales
                    </a>

                    <a href="{{ route('proveedores.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'proveedores.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🏭 Proveedores
                    </a>
                </div>
            </div>

            <!-- Menú escritorio -->
            <div class="hidden sm:flex sm:mt-0 w-full">
                <a href="{{ route('entradas.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ $rutaActual === 'entradas.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📦 Entradas de Material
                </a>

                <a href="{{ route('pedidos.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ $rutaActual === 'pedidos.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🛒 Pedidos de Compra
                </a>

                <a href="{{ route('pedidos_globales.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ $rutaActual === 'pedidos_globales.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🌐 Pedidos Globales
                </a>

                <a href="{{ route('proveedores.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ $rutaActual === 'proveedores.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🏭 Proveedores
                </a>
            </div>
        </div>
    @endif


    <div class="px-4 py-6">
        @if (count($filtrosActivos))
            <div class="alert alert-info text-sm mt-2 mb-4 shadow-sm">
                <strong>Filtros aplicados:</strong> {!! implode(', ', $filtrosActivos) !!}
            </div>
        @endif
        <button onclick="abrirModalPedidoGlobal()"
            class="px-4 py-2 mb-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            ➕ Crear Pedido Global
        </button>
        <div class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="w-full border-collapse text-sm text-center">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-xs uppercase">
                        <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código' !!}</th>
                        <th class="p-2 border">{!! $ordenables['proveedor'] ?? 'Proveedor' !!}</th>
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
                                <input type="text" name="codigo" value="{{ request('codigo') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="proveedor" value="{{ request('proveedor') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border"></th>
                            <th class="p-1 border"></th>
                            <th class="p-1 border"></th>
                            <th class="p-1 border">
                                <select name="estado" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>
                                        Pendiente</option>
                                    <option value="en curso" {{ request('estado') == 'en curso' ? 'selected' : '' }}>En
                                        curso</option>
                                    <option value="completado"
                                        {{ request('estado') == 'completado' ? 'selected' : '' }}>Completado</option>
                                    <option value="cancelado" {{ request('estado') == 'cancelado' ? 'selected' : '' }}>
                                        Cancelado</option>
                                </select>
                            </th>
                            <th class="p-1 border"></th>
                            <th class="p-1 border">
                                <button type="submit" class="btn btn-sm btn-info px-2"><i
                                        class="fas fa-search"></i></button>
                                <a href="{{ route('pedidos_globales.index') }}" class="btn btn-sm btn-warning px-2"><i
                                        class="fas fa-undo"></i></a>
                            </th>
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

                            <!-- Proveedor -->
                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.proveedor?.nombre ?? 'N/A' "></span>
                                </template>
                                <select x-show="editando" x-model="pedido.proveedor_id" class="form-input w-full">
                                    <option value="">Selecciona</option>
                                    @foreach ($proveedores as $prov)
                                        <option value="{{ $prov->id }}">
                                            {{ $prov->nombre }}</option>
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
                                <x-boton-eliminar :action="route('pedidos_globales.destroy', $pedido->id)" />
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
                    <label for="proveedor_id" class="block text-sm font-medium text-gray-700">Proveedor</label>
                    <select name="proveedor_id" class="form-select w-full">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
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
