<x-app-layout>
    <x-slot name="title">Crear Pedido de Almacén - {{ config('app.name') }}</x-slot>

    <x-menu.salidas.salidas />
    <x-menu.salidas.salidas2 />
    <x-menu.salidas.salidasAlmacen />

    <div class="w-full px-6 py-4">
        {{-- ========== 1) Buscador de disponibilidad (opcional) ========== --}}
        {{-- 1) Buscador de disponibilidad por Producto Base --}}

        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-lg font-semibold mb-3">Disponibilidad por Producto Base</h2>

            <div class="flex flex-wrap items-end gap-3">
                <!-- Diámetro -->
                <div class="inline-flex items-center gap-2">
                    <label class="text-xs text-gray-600 whitespace-nowrap">Diámetro</label>
                    <select x-model.number="filtro.diametro" class="border rounded px-2 py-1 text-sm w-auto min-w-24">
                        <option value="">—</option>
                        @foreach ($diametros as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Longitud -->
                <div class="inline-flex items-center gap-2">
                    <label class="text-xs text-gray-600 whitespace-nowrap">Longitud</label>
                    <select x-model.number="filtro.longitud" class="border rounded px-2 py-1 text-sm w-auto min-w-24">
                        <option value="">—</option>
                        @foreach ($longitudes as $l)
                            <option value="{{ $l }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Botones: consultar + restablecer -->
                <div class="inline-flex items-center gap-2">
                    <button @click="consultar()"
                        class="inline-flex items-center gap-2 bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 active:scale-[.99] transition w-auto">
                        Consultar
                    </button>

                    {{-- ♻️ Botón reset --}}
                    <button type="button" @click="resetFiltros()"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                        title="Restablecer filtros">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <template x-if="consultado">
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="bg-gray-50 p-3 rounded border">
                    <p class="text-xs text-gray-600">Peso disponible</p>
                    <p class="text-xl font-semibold" x-text="total_peso_kg.toFixed(2) + ' kg'"></p>
                </div>
                <div class="bg-gray-50 p-3 rounded border">
                    <p class="text-xs text-gray-600">Nº Paquetes</p>
                    <p class="text-xl font-semibold" x-text="total_productos"></p>
                </div>

            </div>
        </template>

        <template x-if="bases.length">
            <div class="mt-4 overflow-x-auto rounded-lg border bg-white shadow-sm">
                <table class="w-full text-[13px]">
                    <thead class="bg-gray-100/80 sticky top-0 z-10">
                        <tr class="text-xs uppercase tracking-wide text-gray-600">

                            <th class="p-2 border">Diám.</th>
                            <th class="p-2 border">Long.</th>
                            <th class="p-2 border">Nº Paquetes</th>
                            <th class="p-2 border">Peso total (kg)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="b in bases" :key="b.id">
                            <tr>

                                <td class="p-2 border" x-text="b.diametro"></td>
                                <td class="p-2 border" x-text="b.longitud ?? '—'"></td>
                                <td class="p-2 border" x-text="b.resumen.productos"></td>
                                <td class="p-2 border" x-text="Number(b.resumen.peso_total).toFixed(2)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        <template x-if="preview.length">
            <div class="mt-4">
                <h3 class="text-sm font-semibold mb-2">Primeros productos (FIFO)</h3>
                <ul class="text-xs grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2">
                    <template x-for="p in preview" :key="p.id">
                        <li class="border rounded p-2 bg-white">
                            <div><span x-text="p.codigo"></span></div>
                            <div>Peso: <span x-text="p.peso_kg"></span> kg</div>

                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </div>

    {{-- ========== 2) Formulario de creación de pedido de almacén ========== --}}
    <div class="bg-white rounded-lg shadow max-w-4xl mx-auto px-6 py-4 space-y-6">
        <form method="POST" action="{{ route('pedidos-almacen-venta.store') }}" x-data="{ lineas: [{}] }"
            class="space-y-4">
            @csrf

            <h2 class="text-lg font-semibold">Nuevo Pedido de Almacén</h2>

            {{-- CLIENTE --}}
            <div>
                <label class="text-sm text-gray-600">Cliente</label>
                <select name="cliente_id" required class="w-full border rounded px-3 py-2 text-sm bg-white">
                    <option value="">— Selecciona cliente —</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- LÍNEAS DINÁMICAS --}}
            <template x-for="(linea, index) in lineas" :key="index">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t pt-4 mt-4">
                    <!-- Producto base -->
                    <div class="sm:col-span-2">
                        <label class="text-xs text-gray-600">Producto base</label>
                        <select :name="`lineas[${index}][producto_base_id]`"
                            class="w-full border rounded px-2 py-1 text-sm bg-white" required>
                            <option value="">— Selecciona —</option>
                            @foreach ($productosBase as $pb)
                                <option value="{{ $pb->id }}">Ø{{ $pb->diametro }} @if ($pb->longitud)
                                        · {{ $pb->longitud }}m
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Datos de línea -->
                    <div class="bg-gray-50 p-3 rounded">
                        <label class="text-xs text-gray-600">Unidad y cantidad</label>
                        <div class="flex items-center gap-2 mt-1">
                            <select :name="`lineas[${index}][unidad_medida]`" class="border rounded px-2 py-1 text-sm"
                                required>
                                <option value="kg">kg</option>
                                <option value="bultos">bultos</option>
                            </select>

                            <input :name="`lineas[${index}][cantidad_solicitada]`" type="number" step="0.01"
                                min="0.01" class="w-full border rounded px-2 py-1 text-sm" placeholder="Cantidad"
                                required>
                        </div>

                        <!-- Obra -->
                        <div class="mt-2">
                            <label class="text-xs text-gray-600">Obra (opcional)</label>
                            <select :name="`lineas[${index}][obra_id]`"
                                class="w-full border rounded px-2 py-1 text-sm bg-white">
                                <option value="">—</option>
                                @foreach ($obras as $obra)
                                    <option value="{{ $obra->id }}">
                                        {{ $obra->nombre }} ({{ $obra->cliente->nombre }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Kg/bulto override -->
                        <div class="mt-2">
                            <label class="text-xs text-gray-600">Kg por bulto (opcional)</label>
                            <input type="number" min="0.01" step="0.01"
                                :name="`lineas[${index}][kg_por_bulto_override]`"
                                class="w-full border rounded px-2 py-1 text-sm" placeholder="Ej: 150">
                        </div>

                        <!-- Precio y notas -->
                        <div class="mt-2">
                            <input type="number" step="0.0001" min="0"
                                :name="`lineas[${index}][precio_unitario]`"
                                class="w-full border rounded px-2 py-1 text-sm" placeholder="Precio unitario (€)">
                        </div>

                        <div class="mt-2">
                            <textarea :name="`lineas[${index}][notas]`" rows="1" class="w-full border rounded px-2 py-1 text-sm"
                                placeholder="Notas..."></textarea>
                        </div>

                        <!-- Eliminar línea -->
                        <div class="mt-2 text-right">
                            <button type="button" @click="lineas.splice(index, 1)"
                                class="text-red-500 text-xs hover:underline">Eliminar</button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Botón añadir línea --}}
            <div class="text-left">
                <button type="button" @click="lineas.push({})" wire:navigate class="text-sm text-blue-600 hover:underline">
                    ➕ Añadir otra línea
                </button>
            </div>

            {{-- Observaciones generales --}}
            <div>
                <label class="text-sm text-gray-600">Observaciones</label>
                <textarea name="observaciones" rows="3" class="w-full border rounded px-3 py-2 text-sm"
                    placeholder="Observaciones del pedido..."></textarea>
            </div>

            {{-- Botón crear pedido --}}
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Crear pedido
                </button>
            </div>
        </form>
    </div>
    </div>
</x-app-layout>
