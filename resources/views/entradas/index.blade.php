<x-app-layout>
    <x-slot name="title">Entradas - {{ config('app.name') }}</x-slot>
    <x-menu.materiales />

    <div class="w-full p-4 sm:p-4">
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="w-full border text-sm text-center">
                <thead class="bg-blue-600 text-white uppercase text-xs">
                    <tr>
                        <th class="px-3 py-2 border">{!! $ordenables['pedido_producto_id'] ?? 'Línea de pedido' !!}</th>
                        <th class="px-3 py-2 border">{!! $ordenables['albaran'] ?? 'Albarán' !!}</th>
                        <th class="px-3 py-2 border">{!! $ordenables['codigo_sage'] ?? 'Código SAGE' !!}</th>
                        <th class="px-3 py-2 border">{!! $ordenables['nave_id'] ?? 'Nave' !!}</th>
                        {{-- ✅ NUEVA COLUMNA --}}
                        <th class="px-3 py-2 border">Producto Base</th>
                        <th class="px-3 py-2 border">{!! $ordenables['pedido_codigo'] ?? 'Pedido Compra' !!}</th>
                        <th class="px-3 py-2 border">{!! $ordenables['created_at'] ?? 'Fecha' !!}</th>
                        <th class="px-3 py-2 border">Nº Productos</th>
                        <th class="px-3 py-2 border">Peso Total</th>
                        <th class="px-3 py-2 border">Estado</th>
                        <th class="px-3 py-2 border">{!! $ordenables['usuario'] ?? 'Usuario' !!}</th>
                        <th class="px-3 py-2 border">PDF Adjunto</th>
                        <th class="px-3 py-2 border">Acciones</th>
                    </tr>

                    <tr>
                        <form method="GET" action="{{ route('entradas.index') }}">
                            {{-- ID Línea Pedido --}}
                            <th class="border p-1">
                                <x-tabla.input name="pedido_producto_id" :value="request('pedido_producto_id')" class="text-xs w-full" />
                            </th>

                            <th class="border p-1">
                                <x-tabla.input name="albaran" :value="request('albaran')" class="text-xs w-full" />
                            </th>

                            <th class="border p-1">
                                <x-tabla.input name="codigo_sage" :value="request('codigo_sage')" class="text-xs w-full" />
                            </th>

                            <th class="border p-1">
                                <x-tabla.input name="obra" :value="request('obra')" class="text-xs w-full"
                                    placeholder="Nave" />
                            </th>

                            {{-- ✅ FILTRO PARA PRODUCTO BASE CON 3 MINI INPUTS --}}
                            <th class="py-1 px-0 border">
                                <div class="flex gap-2 justify-center">
                                    <input type="text" name="producto_tipo" value="{{ request('producto_tipo') }}"
                                        placeholder="T"
                                        class="bg-white text-gray-800 border border-gray-300 rounded text-[10px] text-center w-14 h-6" />
                                    <input type="text" name="producto_diametro"
                                        value="{{ request('producto_diametro') }}" placeholder="Ø"
                                        class="bg-white text-gray-800 border border-gray-300 rounded text-[10px] text-center w-14 h-6" />
                                    <input type="text" name="producto_longitud"
                                        value="{{ request('producto_longitud') }}" placeholder="L"
                                        class="bg-white text-gray-800 border border-gray-300 rounded text-[10px] text-center w-14 h-6" />
                                </div>
                            </th>

                            <th class="border p-1">
                                <x-tabla.input name="pedido_codigo" :value="request('pedido_codigo')" class="text-xs w-full" />
                            </th>

                            <th class="border p-1"></th>
                            <th class="border p-1"></th>
                            <th class="border p-1"></th>
                            <th class="border p-1"></th>

                            <th class="border p-1">
                                <x-tabla.input name="usuario" :value="request('usuario')" class="text-xs w-full" />
                            </th>

                            <th class="border p-1"></th>

                            <x-tabla.botones-filtro ruta="entradas.index" />

                        </form>
                    </tr>

                </thead>
                <tbody>
                    @forelse ($entradas as $entrada)
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
                            @keydown.enter.stop="guardarEntrada(fila); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b hover:bg-blue-50 text-sm text-center">

                            <!-- ID -->
                            <td class="px-3 py-2 text-center">
                                <a href="{{ route('pedidos.index', ['pedido_producto_id' => $entrada->pedido_producto_id]) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $entrada->pedido_producto_id }}
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

                            {{-- ✅ NUEVA COLUMNA: PRODUCTO BASE --}}
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

                            <!-- Pedido Compra (no editable) -->
                            @php $pedido = $entrada->pedido; @endphp
                            <td class="px-3 py-2">
                                @if ($pedido)
                                    <a href="{{ route('pedidos.index', ['pedido_id' => $pedido->id]) }}"
                                        class="text-blue-600 hover:underline">
                                        {{ $pedido->codigo }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>

                            <!-- Fecha -->
                            <td class="px-3 py-2">{{ $entrada->created_at->format('d/m/Y H:i') }}</td>

                            <!-- Nº Productos -->
                            <td class="px-3 py-2">
                                @if ($entrada->productos_count > 0)
                                    <a href="{{ route('productos.index', ['entrada_id' => $entrada->id, 'mostrar_todos' => 1]) }}"
                                        class="text-blue-600 hover:underline">
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
                                    <a href="{{ route('entradas.crearDescargarPdf', $entrada->id) }}" target="_blank"
                                        class="text-green-600 font-semibold hover:underline">
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
                                    <x-tabla.boton-guardar x-show="editando"
                                        @click="guardarEntrada(fila); editando = false" />
                                    <x-tabla.boton-cancelar-edicion x-show="editando"
                                        @click="fila = JSON.parse(JSON.stringify(original)); editando=false" />

                                    <!-- Botones normales cuando NO editando -->
                                    <template x-if="!editando">
                                        <div class="flex items-center space-x-2">
                                            <!-- Botón de adjuntar albarán -->
                                            <button @click="$dispatch('abrir-modal-adjuntar', { entradaId: fila.id })"
                                                class="w-6 h-6 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 flex items-center justify-center"
                                                title="Adjuntar albarán PDF">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l7.07-7.07a4 4 0 00-5.657-5.657L6.343 11.343a6 6 0 008.485 8.485l.707-.707" />
                                                </svg>
                                            </button>

                                            <button @click="editando = true" type="button"
                                                class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center"
                                                title="Editar">
                                                <!-- ícono lápiz -->
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
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
                        <tr>
                            <td colspan="13" class="py-4 text-gray-500">No hay entradas de material registradas.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        <x-tabla.paginacion :paginador="$entradas" />

    </div>
    <div id="modalDibujo" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded shadow-lg max-w-2xl w-full">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitulo" class="text-lg font-semibold">Productos de la entrada</h2>
                <button onclick="cerrarModal()" class="text-red-600 hover:text-red-800">✖</button>
            </div>
            <div id="contenidoProductos">
                <p class="text-gray-500">Cargando productos...</p>
            </div>
        </div>
    </div>
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

    <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
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

    <script>
        document.querySelectorAll('.abrir-modal-dibujo').forEach(boton => {
            boton.addEventListener('click', function(e) {
                e.preventDefault();

                const productos = JSON.parse(this.dataset.productos);
                const albaran = this.dataset.albaran;
                let html = '<ol class="list-decimal pl-5 space-y-1">';

                if (productos.length > 0) {
                    productos.forEach(p => {
                        const base = p?.producto_base ?? {};
                        const nombre = base.tipo || '—';
                        const diametro = base.diametro ?? '–';
                        const longitud = base.longitud ?? '–';
                        const fabricante = p?.fabricante?.nombre || 'Sin fabricante';

                        html +=
                            `<li>${nombre} – Ø${diametro} – ${longitud} m – <span class="text-gray-600">${fabricante}</span></li>`;
                    });
                } else {
                    html += '<li class="text-gray-500">No hay productos asociados.</li>';
                }

                html += '</ol>';

                document.getElementById('contenidoProductos').innerHTML = html;
                document.getElementById('modalDibujo').classList.remove('hidden');
                document.getElementById('modalTitulo').innerText = `Productos del albarán ${albaran}`;

            });
        });

        function cerrarModal() {
            document.getElementById('modalDibujo').classList.add('hidden');
            document.getElementById('contenidoProductos').innerHTML = '';
        }
    </script>
</x-app-layout>
