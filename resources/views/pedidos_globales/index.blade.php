<x-app-layout>
    <x-slot name="title">Pedidos Globales - {{ config('app.name') }}</x-slot>
    <x-menu.materiales />

    <div class="px-4 py-6">

        <button onclick="abrirModalPedidoGlobal()"
            class="px-4 py-2 mb-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
            ➕ Crear Pedido Global
        </button>

        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        {{-- =========================
             TABLA PRINCIPAL (SIN MAQUILA)
           ========================= --}}
        <div class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="w-full border-collapse text-sm text-center">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-xs uppercase">
                        <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código' !!}</th>
                        <th class="p-2 border">{!! $ordenables['fabricante'] ?? 'Fabricante' !!}</th>
                        <th class="p-2 border">{!! $ordenables['distribuidor'] ?? 'Distribuidor' !!}</th>
                        <th class="p-2 border">{!! $ordenables['precio_referencia'] ?? 'Precio Ref.' !!}</th>
                        <th class="p-2 border">{!! $ordenables['cantidad_total'] ?? 'Cantidad Total' !!}</th>
                        <th class="p-2 border">Cantidad Restante</th>
                        <th class="p-2 border">Progreso</th>
                        <th class="p-2 border">{!! $ordenables['estado'] ?? 'Estado' !!}</th>
                        <th class="p-2 border">Creación Registro</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    {{-- Fila de filtros (afectan a ambas tablas porque se aplican en el controlador antes de separar) --}}
                    <tr class="text-xs uppercase bg-blue-50 text-black">
                        <form method="GET" action="{{ route('pedidos_globales.index') }}">
                            <th class="p-1 border">
                                <input name="codigo" type="text" value="{{ request('codigo') }}"
                                    class="w-full text-xs border rounded px-1 py-1" placeholder="Código">
                            </th>
                            <th class="p-1 border">
                                <input name="fabricante" type="text" value="{{ request('fabricante') }}"
                                    class="w-full text-xs border rounded px-1 py-1" placeholder="Fabricante">
                            </th>
                            <th class="p-1 border">
                                <input name="distribuidor" type="text" value="{{ request('distribuidor') }}"
                                    class="w-full text-xs border rounded px-1 py-1" placeholder="Distribuidor">
                            </th>

                            <th class="p-1 border"></th>
                            <th class="p-1 border"></th>
                            <th class="p-1 border"></th>
                            <th class="p-1 border"></th>

                            <th class="p-1 border">
                                <select name="estado" class="w-full text-xs border rounded px-1 py-1">
                                    <option value="">Todos</option>
                                    @foreach (['pendiente' => 'Pendiente', 'en curso' => 'En curso', 'completado' => 'Completado', 'cancelado' => 'Cancelado'] as $val => $label)
                                        <option value="{{ $val }}"
                                            {{ request('estado') === $val ? 'selected' : '' }}>{{ $label }}
                                        </option>
                                    @endforeach
                                </select>
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
                            @dblclick="if(!$event.target.closest('input,select,button,form')) {
                                editando = !editando;
                                if (!editando) pedido = JSON.parse(JSON.stringify(original));
                            }"
                            @keydown.enter.stop="guardarCambiosPedidoGlobal(pedido); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                            {{-- Código (no editable) --}}
                            <td class="p-2 border" x-text="pedido.codigo"></td>

                            {{-- Fabricante --}}
                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.fabricante?.nombre ?? 'N/A' "></span>
                                </template>
                                <select x-show="editando" x-model="pedido.fabricante_id"
                                    class="w-full text-xs border rounded px-1 py-1">
                                    <option value="">Selecciona</option>
                                    @foreach ($fabricantes as $fab)
                                        <option value="{{ $fab->id }}">{{ $fab->nombre }}</option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Distribuidor --}}
                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.distribuidor?.nombre ?? 'N/A'"></span>
                                </template>
                                <select x-show="editando" x-model="pedido.distribuidor_id"
                                    class="w-full text-xs border rounded px-1 py-1">
                                    <option value="">Selecciona</option>
                                    @foreach ($distribuidores as $dist)
                                        <option value="{{ $dist->id }}">{{ $dist->nombre }}</option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Precio referencia --}}
                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.precio_referencia_euro ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" x-model="pedido.precio_referencia" type="number"
                                    step="0.01" min="0"
                                    class="w-full text-right text-xs border rounded px-1 py-1" placeholder="Ej: 6,40">
                            </td>

                            {{-- Cantidad total --}}
                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span
                                        x-text="Number(pedido.cantidad_total).toLocaleString('es-ES',{minimumFractionDigits:2}) + ' kg'"></span>
                                </template>
                                <input x-show="editando" type="number" step="0.01" x-model="pedido.cantidad_total"
                                    class="w-full text-xs border rounded px-1 py-1">
                            </td>

                            {{-- Cantidad restante (solo lectura) --}}
                            <td class="p-2 border">
                                {{ number_format($pedido->cantidad_restante, 2, ',', '.') }} kg
                            </td>

                            {{-- Progreso --}}
                            <td class="p-2 border">
                                <div class="w-full bg-gray-200 rounded-full h-4">
                                    <div class="bg-green-400 h-4 text-white rounded-full text-[10px] text-center"
                                        style="width: {{ $pedido->progreso }}%">
                                        {{ $pedido->progreso }}%
                                    </div>
                                </div>
                            </td>

                            {{-- Estado --}}
                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.estado"></span>
                                </template>
                                <select x-show="editando" x-model="pedido.estado"
                                    class="w-full text-xs border rounded px-1 py-1">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="en curso">En curso</option>
                                    <option value="completado">Completado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </td>

                            {{-- Fecha formateada --}}
                            <td class="border px-3 py-2">{{ $pedido->fecha_creacion_formateada }}</td>

                            {{-- Acciones --}}
                            <td class="p-2 border text-center">
                                <template x-if="editando">
                                    <button @click="guardarCambiosPedidoGlobal(pedido); editando=false"
                                        class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded shadow">
                                        Guardar
                                    </button>
                                </template>

                                <x-tabla.boton-eliminar :action="route('pedidos_globales.destroy', $pedido->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-4 text-gray-500 text-center">No hay pedidos globales
                                registrados.</td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot class="bg-gray-100 text-xs uppercase">
                    {{-- Totales del filtrado (excluye maquila) --}}
                    <tr>
                        <td class="p-2 border text-right font-semibold" colspan="4">Totales
                        </td>
                        <td class="p-2 border font-semibold">
                            {{ number_format($totalesPrincipal['cantidad_total'] ?? 0, 2, ',', '.') }} kg
                        </td>
                        <td class="p-2 border font-semibold">
                            {{ number_format($totalesPrincipal['cantidad_restante'] ?? 0, 2, ',', '.') }} kg
                        </td>
                        <td class="p-2 border" colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <x-tabla.paginacion :paginador="$pedidosGlobales" />
        {{-- =========================
             TABLA MAQUILA
           ========================= --}}
        <div class="mt-8 overflow-x-auto bg-white shadow rounded-lg">
            <div class="px-3 pt-3 text-left text-sm">
                <strong>Pedido Global</strong>
            </div>
            <table class="w-full border-collapse text-sm text-center">
                <thead class="bg-purple-600 text-white">
                    <tr class="text-xs uppercase">
                        <th class="p-2 border">Código</th>
                        <th class="p-2 border">Fabricante</th>
                        <th class="p-2 border">Distribuidor</th>
                        <th class="p-2 border">Precio Ref.</th>
                        <th class="p-2 border">Cantidad Total</th>
                        <th class="p-2 border">Cantidad Restante</th>
                        <th class="p-2 border">Progreso</th>
                        <th class="p-2 border">Estado</th>
                        <th class="p-2 border">Creación Registro</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($pedidosMaquila as $pedido)
                        <tr tabindex="0" x-data="{
                            editando: false,
                            pedido: @js($pedido),
                            original: JSON.parse(JSON.stringify(@js($pedido)))
                        }"
                            @dblclick="if(!$event.target.closest('input,select,button,form')) {
                                editando = !editando;
                                if (!editando) pedido = JSON.parse(JSON.stringify(original));
                            }"
                            @keydown.enter.stop="guardarCambiosPedidoGlobal(pedido); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-purple-200 cursor-pointer text-xs uppercase">

                            <td class="p-2 border" x-text="pedido.codigo"></td>

                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.fabricante?.nombre ?? 'N/A' "></span>
                                </template>
                                <select x-show="editando" x-model="pedido.fabricante_id"
                                    class="w-full text-xs border rounded px-1 py-1">
                                    <option value="">Selecciona</option>
                                    @foreach ($fabricantes as $fab)
                                        <option value="{{ $fab->id }}">{{ $fab->nombre }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.distribuidor?.nombre ?? 'N/A'"></span>
                                </template>
                                <select x-show="editando" x-model="pedido.distribuidor_id"
                                    class="w-full text-xs border rounded px-1 py-1">
                                    <option value="">Selecciona</option>
                                    @foreach ($distribuidores as $dist)
                                        <option value="{{ $dist->id }}">{{ $dist->nombre }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.precio_referencia_euro ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" x-model="pedido.precio_referencia" type="number"
                                    step="0.01" min="0"
                                    class="w-full text-right text-xs border rounded px-1 py-1" placeholder="Ej: 6,40">
                            </td>

                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span
                                        x-text="Number(pedido.cantidad_total).toLocaleString('es-ES',{minimumFractionDigits:2}) + ' kg'"></span>
                                </template>
                                <input x-show="editando" type="number" step="0.01"
                                    x-model="pedido.cantidad_total" class="w-full text-xs border rounded px-1 py-1">
                            </td>

                            <td class="p-2 border">
                                {{ number_format($pedido->cantidad_restante, 2, ',', '.') }} kg
                            </td>

                            <td class="p-2 border">
                                <div class="w-full bg-gray-200 rounded-full h-4">
                                    <div class="bg-green-500 h-4 text-white rounded-full text-[10px] text-center"
                                        style="width: {{ $pedido->progreso }}%">
                                        {{ $pedido->progreso }}%
                                    </div>
                                </div>
                            </td>

                            <td class="p-2 border">
                                <template x-if="!editando">
                                    <span x-text="pedido.estado"></span>
                                </template>
                                <select x-show="editando" x-model="pedido.estado"
                                    class="w-full text-xs border rounded px-1 py-1">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="en curso">En curso</option>
                                    <option value="completado">Completado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </td>

                            <td class="border px-3 py-2">{{ $pedido->fecha_creacion_formateada }}</td>

                            <td class="p-2 border text-center">
                                <template x-if="editando">
                                    <button @click="guardarCambiosPedidoGlobal(pedido); editando=false"
                                        class="bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded shadow">
                                        Guardar
                                    </button>
                                </template>


                                <x-tabla.boton-eliminar :action="route('pedidos_globales.destroy', $pedido->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-4 text-gray-500 text-center">No hay pedidos Globales para
                                mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot class="bg-gray-100 text-xs uppercase">
                    {{-- Totales del pedido de maquila --}}
                    <tr>
                        <td class="p-2 border text-right font-semibold" colspan="4">
                            Totales
                        </td>
                        <td class="p-2 border font-semibold">
                            {{ number_format($totalesMaquila['cantidad_total'] ?? 0, 2, ',', '.') }} kg
                        </td>
                        <td class="p-2 border font-semibold">
                            {{ number_format($totalesMaquila['cantidad_restante'] ?? 0, 2, ',', '.') }} kg
                        </td>
                        <td class="p-2 border" colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <x-tabla.paginacion :paginador="$pedidosMaquila" />
    </div>

    {{-- =========================
         MODAL CREAR PEDIDO GLOBAL
       ========================= --}}
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
                    <select name="fabricante_id" class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($fabricantes as $fabricante)
                            <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="distribuidor_id" class="block text-sm font-medium text-gray-700">Distribuidor</label>
                    <select name="distribuidor_id" class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($distribuidores as $distribuidor)
                            <option value="{{ $distribuidor->id }}">{{ $distribuidor->nombre }}</option>
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

    {{-- =========================
         SCRIPTS
       ========================= --}}
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

            fetch("{{ route('pedidos_globales.store') }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: new FormData(form)
                })
                .then(async res => {
                    const contentType = res.headers.get("content-type") || '';
                    if (res.ok && contentType.includes("application/json")) {
                        await res.json();
                        alert('Pedido global creado correctamente.');
                        window.location.reload();
                    } else {
                        const text = await res.text();
                        throw new Error("Error inesperado:\n" + text.slice(0, 600));
                    }
                })
                .catch(err => {
                    alert(err.message || 'Error creando pedido global.');
                });
        });

        function guardarCambiosPedidoGlobal(pedido) {
            fetch(`{{ route('pedidos_globales.update', '') }}/${pedido.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(pedido)
                })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        let mensaje = (data && data.message) ? data.message : 'Error desconocido';
                        if (data && data.errors) {
                            mensaje = Object.values(data.errors).flat().join('\n');
                        }
                        throw new Error(mensaje);
                    }
                    alert('Pedido global actualizado.');
                    window.location.reload();
                })
                .catch(error => {
                    alert(error.message || 'No se pudo actualizar el pedido global.');
                });
        }
    </script>
</x-app-layout>
