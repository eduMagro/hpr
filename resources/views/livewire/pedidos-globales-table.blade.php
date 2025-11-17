<div class="w-full">
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    {{-- =========================
         TABLA PRINCIPAL (SIN MAQUILA)
       ========================= --}}
    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="w-full border-collapse text-sm text-center">
            <thead class="bg-blue-500 text-white">
                <tr class="text-xs uppercase">
                    <th class="p-2 border">
                        <button wire:click="sortBy('codigo')" wire:navigate class="w-full text-center hover:text-yellow-200">
                            Código
                            @if($sort === 'codigo')
                                <span>{!! $order === 'asc' ? '▲' : '▼' !!}</span>
                            @endif
                        </button>
                    </th>
                    <th class="p-2 border">Fabricante</th>
                    <th class="p-2 border">Distribuidor</th>
                    <th class="p-2 border">
                        <button wire:click="sortBy('precio_referencia')" wire:navigate class="w-full text-center hover:text-yellow-200">
                            Precio Ref.
                            @if($sort === 'precio_referencia')
                                <span>{!! $order === 'asc' ? '▲' : '▼' !!}</span>
                            @endif
                        </button>
                    </th>
                    <th class="p-2 border">
                        <button wire:click="sortBy('cantidad_total')" wire:navigate class="w-full text-center hover:text-yellow-200">
                            Cantidad Total
                            @if($sort === 'cantidad_total')
                                <span>{!! $order === 'asc' ? '▲' : '▼' !!}</span>
                            @endif
                        </button>
                    </th>
                    <th class="p-2 border">Cantidad Restante</th>
                    <th class="p-2 border">Progreso</th>
                    <th class="p-2 border">
                        <button wire:click="sortBy('estado')" wire:navigate class="w-full text-center hover:text-yellow-200">
                            Estado
                            @if($sort === 'estado')
                                <span>{!! $order === 'asc' ? '▲' : '▼' !!}</span>
                            @endif
                        </button>
                    </th>
                    <th class="p-2 border">Creación Registro</th>
                    <th class="p-2 border">Acciones</th>
                </tr>

                {{-- Fila de filtros --}}
                <tr class="text-xs uppercase bg-blue-50 text-black">
                    <th class="p-1 border">
                        <input wire:model.live.debounce.300ms="codigo" type="text"
                            class="w-full text-xs border rounded px-1 py-1" placeholder="Código">
                    </th>
                    <th class="p-1 border">
                        <input wire:model.live.debounce.300ms="fabricante" type="text"
                            class="w-full text-xs border rounded px-1 py-1" placeholder="Fabricante">
                    </th>
                    <th class="p-1 border">
                        <input wire:model.live.debounce.300ms="distribuidor" type="text"
                            class="w-full text-xs border rounded px-1 py-1" placeholder="Distribuidor">
                    </th>

                    <th class="p-1 border"></th>
                    <th class="p-1 border"></th>
                    <th class="p-1 border"></th>
                    <th class="p-1 border"></th>

                    <th class="p-1 border">
                        <select wire:model.live="estado" class="w-full text-xs border rounded px-1 py-1">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="en curso">En curso</option>
                            <option value="completado">Completado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </th>

                    <th class="p-1 border"></th>
                    <th class="p-1 border text-center align-middle">
                        <div class="flex justify-center gap-2 items-center h-full">
                            <button wire:click="limpiarFiltros" type="button"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                title="Restablecer filtros">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                </svg>
                            </button>
                        </div>
                    </th>
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
                            <x-tabla.boton-guardar x-show="editando"
                                @click="guardarCambiosPedidoGlobal(pedido); editando = false" />
                            <x-tabla.boton-cancelar-edicion x-show="editando"
                                @click="pedido = JSON.parse(JSON.stringify(original)); editando=false" />

                            <template x-if="!editando">
                                <x-tabla.boton-eliminar :action="route('pedidos_globales.destroy', $pedido->id)" />
                            </template>
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

    {{ $pedidosGlobales->links() }}

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
                            <x-tabla.boton-guardar x-show="editando"
                                @click="guardarCambiosPedidoGlobal(pedido); editando = false" />
                            <x-tabla.boton-cancelar-edicion x-show="editando"
                                @click="pedido = JSON.parse(JSON.stringify(original)); editando=false" />

                            <template x-if="!editando">
                                <x-tabla.boton-eliminar :action="route('pedidos_globales.destroy', $pedido->id)" />
                            </template>
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

    <script>
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
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Pedido global actualizado correctamente.',
                        confirmButtonColor: '#16a34a'
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo actualizar el pedido global.'
                    });
                });
        }
    </script>
</div>
