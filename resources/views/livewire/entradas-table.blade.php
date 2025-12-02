<div class="w-full">
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    <x-tabla.wrapper minWidth="1200px">
        <x-tabla.header>
            <x-tabla.header-row>
                <x-tabla.encabezado-ordenable campo="pedido_producto_id" :sortActual="$sort" :orderActual="$order"
                    texto="Código Línea" />
                <x-tabla.encabezado-ordenable campo="albaran" :sortActual="$sort" :orderActual="$order" texto="Albarán" />
                <x-tabla.encabezado-ordenable campo="codigo_sage" :sortActual="$sort" :orderActual="$order"
                    texto="Código SAGE" />
                <x-tabla.encabezado-ordenable campo="nave_id" :sortActual="$sort" :orderActual="$order" texto="Nave" />
                <th class="p-2">Producto Base</th>
                <x-tabla.encabezado-ordenable campo="created_at" :sortActual="$sort" :orderActual="$order" texto="Fecha" />
                <th class="p-2">Nº Productos</th>
                <th class="p-2">Peso Total</th>
                <th class="p-2">Estado</th>
                <th class="p-2">Usuario</th>
                <th class="p-2">PDF Adjunto</th>
                <th class="p-2">Acciones</th>
            </x-tabla.header-row>

            <x-tabla.filtro-row>
                <x-tabla.filtro-input model="pedido_codigo" placeholder="PC25/0001" />
                <x-tabla.filtro-vacio />
                <x-tabla.filtro-vacio />
                <x-tabla.filtro-input model="nave_id" placeholder="Nave" />
                <x-tabla.filtro-producto-base />
                <x-tabla.filtro-vacio />
                <x-tabla.filtro-vacio />
                <x-tabla.filtro-vacio />
                <x-tabla.filtro-vacio />
                <x-tabla.filtro-input model="usuario" placeholder="Usuario" />
                <x-tabla.filtro-vacio />
                <x-tabla.filtro-acciones />
            </x-tabla.filtro-row>
        </x-tabla.header>

        <x-tabla.body>
            @forelse ($registrosEntradas as $entrada)
                <tr tabindex="0" x-data="{
                    editando: false,
                    fila: {
                        id: {{ $entrada->id }},
                        albaran: @js($entrada->albaran),
                        codigo_sage: @js($entrada->codigo_sage),
                        peso_total: @js($entrada->peso_total),
                        estado: @js($entrada->estado),
                    },
                    original: {}
                }" x-init="original = JSON.parse(JSON.stringify(fila))"
                    @dblclick="if(!$event.target.closest('input,select,textarea,button,a')){ editando = !editando; if(!editando){ fila = JSON.parse(JSON.stringify(original)); }}"
                    @keydown.enter.stop="guardarEntrada(fila); editando = false" :class="{ 'bg-yellow-100': editando }"
                    class="border-b hover:bg-blue-50 text-sm text-center">

                    <!-- Código Línea -->
                    <td class="px-3 py-2 text-center">
                        <a href="{{ route('pedidos.index', ['pedido_producto_id' => $entrada->pedido_producto_id]) }}"
                            wire:navigate class="text-blue-600 hover:underline font-medium">
                            {{ $entrada->pedidoProducto->codigo ?? 'N/A' }}
                        </a>
                    </td>

                    <!-- Albarán -->
                    <td class="px-3 py-2">
                        <span x-text="fila.albaran"></span>
                    </td>

                    <!-- Código SAGE -->
                    <td class="px-3 py-2">
                        <template x-if="!editando">
                            <span x-text="fila.codigo_sage ?? 'N/A'"></span>
                        </template>
                        <input x-show="editando" type="text" x-model="fila.codigo_sage"
                            class="w-full border rounded px-2 py-1 text-xs" maxlength="50">
                    </td>

                    <!-- Nave -->
                    <td class="px-3 py-2">
                        {{ $entrada->nave->obra ?? 'N/A' }}
                    </td>

                    {{-- PRODUCTO BASE --}}
                    <td class="px-3 py-2">
                        @php
                            $productoBase = $entrada->pedidoProducto?->productoBase;
                        @endphp
                        @if ($productoBase)
                            <span class="font-semibold">{{ $productoBase->tipo }}</span>
                            <span class="text-gray-600">Ø{{ $productoBase->diametro }}</span>
                            @if ($productoBase->tipo === 'barra' && $productoBase->longitud)
                                <span class="text-gray-500">{{ $productoBase->longitud }}m</span>
                            @endif
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </td>

                    <!-- Fecha -->
                    <td class="px-3 py-2">{{ $entrada->created_at->format('d/m/Y H:i') }}</td>

                    <!-- Nº Productos -->
                    <td class="px-3 py-2">
                        @if ($entrada->productos_count > 0)
                            <a href="{{ route('productos.index', ['entrada_id' => $entrada->id, 'mostrar_todos' => 1]) }}"
                                wire:navigate class="text-blue-600 hover:underline">
                                {{ $entrada->productos_count }}
                            </a>
                        @else
                            0
                        @endif
                    </td>

                    <!-- Peso Total -->
                    <td class="px-3 py-2">
                        <span
                            x-text="new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(fila.peso_total) || 0) + ' kg'"></span>
                    </td>

                    <!-- Estado -->
                    <td class="px-3 py-2">
                        <span class="uppercase" x-text="fila.estado ?? 'N/A'"></span>
                    </td>

                    <!-- Usuario -->
                    <td class="px-3 py-2">{{ $entrada->user->nombre_completo ?? 'N/A' }}</td>

                    {{-- PDF adjunto --}}
                    <td class="px-3 py-2">
                        @if ($entrada->pdf_albaran)
                            <a href="{{ route('entradas.crearDescargarPdf', $entrada->id) }}" wire:navigate
                                target="_blank" class="text-green-600 font-semibold hover:underline">
                                {{ $entrada->pdf_albaran }}
                            </a>
                        @else
                            <span class="text-red-500">No</span>
                        @endif
                    </td>

                    <!-- Acciones -->
                    <td class="px-2 py-2 border text-xs font-bold">
                        <div class="flex items-center space-x-2 justify-center">
                            <!-- Guardar / Cancelar cuando editando -->
                            <x-tabla.boton-guardar x-show="editando" @click="guardarEntrada(fila); editando = false" />
                            <x-tabla.boton-cancelar-edicion x-show="editando"
                                @click="fila = JSON.parse(JSON.stringify(original)); editando=false" />

                            <!-- Botones normales cuando NO editando -->
                            <template x-if="!editando">
                                <div class="flex items-center space-x-2">
                                    <!-- Botón de adjuntar albarán -->
                                    <button @click="$dispatch('abrir-modal-adjuntar', { entradaId: fila.id })"
                                        class="w-6 h-6 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 flex items-center justify-center"
                                        title="Adjuntar albarán PDF">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l7.07-7.07a4 4 0 00-5.657-5.657L6.343 11.343a6 6 0 008.485 8.485l.707-.707" />
                                        </svg>
                                    </button>

                                    <button @click="editando = true" type="button"
                                        class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center"
                                        title="Editar">
                                        <!-- ícono lápiz -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </button>
                                    <x-tabla.boton-eliminar :action="route('entradas.destroy', $entrada->id)" />
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
            @empty
                <x-tabla.empty-state colspan="12" mensaje="No hay entradas de material registradas." />
            @endforelse
        </x-tabla.body>
    </x-tabla.wrapper>

    <x-tabla.paginacion-livewire :paginador="$registrosEntradas" />

    {{-- Modal para adjuntar albarán --}}
    <div x-data="{ mostrar: false, entradaId: null }" @abrir-modal-adjuntar.window="mostrar = true; entradaId = $event.detail.entradaId"
        x-show="mostrar" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        style="display: none;" x-cloak>
        <div class="bg-white p-6 rounded shadow-lg w-full max-w-lg">
            <h2 class="text-lg font-semibold mb-4">Adjuntar albarán en PDF</h2>
            <form method="POST" action="{{ route('entradas.crearImportarAlbaranPdf') }}"
                enctype="multipart/form-data">

                @csrf
                <input type="hidden" name="entrada_id" :value="entradaId">
                <div class="mb-4">
                    <label for="albaran_pdf" class="block text-sm font-medium text-gray-700 mb-1">Archivo PDF:</label>
                    <input type="file" name="albaran_pdf" accept="application/pdf" required
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" @click="mostrar = false"
                        class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 text-sm bg-blue-600 text-white hover:bg-blue-700 rounded">Adjuntar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function guardarEntrada(fila) {
            fetch(`{{ route('entradas.update', '') }}/${fila.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        albaran: fila.albaran,
                        codigo_sage: fila.codigo_sage,
                        peso_total: fila.peso_total,
                        estado: fila.estado
                    })
                })
                .then(async (response) => {
                    const contentType = response.headers.get('content-type');
                    let data = {};
                    if (contentType && contentType.includes('application/json')) {
                        data = await response.json();
                    } else {
                        const text = await response.text();
                        throw new Error("Respuesta inesperada del servidor: " + text.slice(0, 200));
                    }

                    if (response.ok && data.success) {
                        // refresca para ver ordenables / totales recalculados si aplica
                        window.location.reload();
                    } else {
                        let errorMsg = data.message || "Error al actualizar la entrada.";
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join("<br>");
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Error al actualizar",
                            html: errorMsg
                        });
                    }
                })
                .catch((err) => {
                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        text: err.message || "No se pudo actualizar la entrada."
                    });
                });
        }
    </script>
</div>
