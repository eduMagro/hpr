<x-app-layout>
    <x-slot name="title">Materiales - {{ config('app.name') }}</x-slot>

    <x-menu.materiales />
    <div class="w-full px-6 py-4">
        <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
        <div class="mb-4 flex justify-center space-x-2">
            <x-tabla.boton-azul :href="route('entradas.create')">
                ➕ Crear Nueva Entrada
            </x-tabla.boton-azul>
        </div>
        <!-- 🖥️ Tabla solo en pantallas medianas o grandes -->
        <div class="hidden md:block">
            <button onclick="abrirModal()"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow">
                Generar códigos
            </button>
            <div id="modalGenerarCodigos"
                class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                    <h2 class="text-xl font-semibold mb-4">Generar y exportar códigos</h2>

                    <form action="{{ route('productos.generar.exportar') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label for="cantidad" class="block text-sm font-medium text-gray-700">Cantidad a
                                generar</label>
                            <input type="number" id="cantidad" name="cantidad" value="10" min="1"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="flex justify-end pt-2 space-x-2">
                            <p class="text-xs text-gray-500 mt-2">
                                ⚠️ Esta exportación es importante. Exporta solo si vas a imprimir etiquetas QR para
                                evitar duplicados.
                            </p>

                            <button type="button" onclick="cerrarModal()"
                                class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Generar
                            </button>
                        </div>
                    </form>

                </div>
            </div>
            <script>
                function abrirModal() {
                    const modal = document.getElementById('modalGenerarCodigos');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function cerrarModal() {
                    const modal = document.getElementById('modalGenerarCodigos');
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                }

                // Opcional: cerrar con tecla ESC
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        cerrarModal();
                    }
                });

                // Opcional: cerrar si se hace clic fuera del contenido
                window.addEventListener('click', function(event) {
                    const modal = document.getElementById('modalGenerarCodigos');
                    if (event.target === modal) {
                        cerrarModal();
                    }
                });
            </script>

            <!-- Catálogo de Productos Base -->
            <div x-data="{ open: false }" class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-lg font-semibold text-gray-800">Catálogo de Productos Base</h2>
                    <button @click="open = !open"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                        <span x-show="!open">Mostrar</span><span x-show="open">Ocultar</span>
                    </button>
                </div>
                <div x-show="open" x-transition class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="w-full min-w-[600px] border border-gray-300 text-sm">
                        <thead class="bg-blue-200 text-gray-700">
                            <tr>
                                <th class="px-3 py-2 border">ID</th>
                                <th class="px-3 py-2 border">Tipo</th>
                                <th class="px-3 py-2 border">Diámetro</th>
                                <th class="px-3 py-2 border">Longitud</th>

                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($productosBase as $base)
                                <tr class="border-b odd:bg-gray-100 even:bg-gray-50">
                                    <td class="px-3 py-2 border text-center">{{ $base->id }}</td>
                                    <td class="px-3 py-2 border text-center">{{ ucfirst($base->tipo) }}</td>
                                    <td class="px-3 py-2 border text-center">{{ $base->diametro }} mm</td>
                                    <td class="px-3 py-2 border text-center">{{ $base->longitud ?? '—' }}</td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-gray-500">No hay productos base
                                        registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
            <!-- Modo Tabla -->
            <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                    <thead class="bg-blue-500 text-white">
                        <tr class="text-center text-xs uppercase">
                            <th class="p-2 border">{!! $ordenables['id'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['entrada_id'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['codigo'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['fabricante'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['tipo'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['diametro'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['longitud'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['n_colada'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['n_paquete'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['peso_inicial'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['peso_stock'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['estado'] !!}</th>
                            <th class="p-2 border">{!! $ordenables['ubicacion'] !!}</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>
                        <tr class="text-center text-xs uppercase">
                            <form method="GET" action="{{ route('productos.index') }}">
                                <th class="p-1 border">
                                    <x-tabla.input name="id" type="text" :value="request('id')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="albaran" type="text" :value="request('albaran')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="codigo" type="text" :value="request('codigo')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="fabricante" type="text" :value="request('fabricante')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="tipo" type="text" :value="request('tipo')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="diametro" type="text" :value="request('diametro')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="longitud" type="text" :value="request('longitud')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="n_colada" type="text" :value="request('n_colada')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="n_paquete" type="text" :value="request('n_paquete')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border"></th> <!-- Peso Inicial -->
                                <th class="p-1 border"></th> <!-- Peso Stock -->
                                <th class="p-1 border">
                                    <x-tabla.select name="estado" :options="[
                                        'almacenado' => 'Almacenado',
                                        'fabricando' => 'Fabricando',
                                        'consumido' => 'Consumido',
                                    ]" :selected="request('estado')"
                                        empty="Todos" class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="ubicacion" type="text" :value="request('ubicacion')"
                                        class="w-full text-xs" />
                                </th>

                                <x-tabla.botones-filtro ruta="productos.index" />
                            </form>
                        </tr>

                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse($registrosProductos as $producto)
                            <tr
                                class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 text-xs leading-none cursor-pointer">
                                <td class="px-2 py-3 text-center border">{{ $producto->id }}</td>
                                @php
                                    // Id de la entrada relacionada (puede ser null)
                                    $entradaId = $producto->entrada?->id;
                                @endphp

                                <td class="px-2 py-3 text-center border">
                                    @if ($entradaId)
                                        <a href="{{ route('entradas.index', ['albaran' => $producto->entrada->albaran]) }}"
                                            class="text-blue-600 hover:underline">
                                            {{ $producto->entrada->albaran }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="px-2 py-3 text-center border">{{ $producto->codigo ?? 'N/A' }}</td>
                                <td class="px-2 py-3 text-center border">{{ $producto->fabricante->nombre ?? '—' }}
                                </td>
                                <td class="px-2 py-3 text-center border">
                                    {{ ucfirst($producto->productoBase->tipo ?? '—') }}</td>
                                <td class="px-2 py-3 text-center border">
                                    {{ $producto->productoBase->diametro ?? '—' }}
                                </td>
                                <td class="px-2 py-3 text-center border">
                                    {{ $producto->productoBase->longitud ?? '—' }}
                                </td>
                                <td class="px-2 py-3 text-center border">{{ $producto->n_colada }}</td>
                                <td class="px-2 py-3 text-center border">{{ $producto->n_paquete }}</td>
                                <td class="px-2 py-3 text-center border">{{ $producto->peso_inicial }} kg</td>
                                <td class="px-2 py-3 text-center border">{{ $producto->peso_stock }} kg</td>
                                <td class="px-2 py-3 text-center border">{{ $producto->estado }}</td>
                                <td class="px-2 py-3 text-center border">
                                    @if (isset($producto->ubicacion->nombre))
                                        {{ $producto->ubicacion->nombre }}
                                    @elseif (isset($producto->maquina->nombre))
                                        {{ $producto->maquina->nombre }}
                                    @else
                                        No está ubicada
                                    @endif
                                </td>
                                <td class="px-2 py-2 border text-xs font-bold">
                                    <div class="flex items-center space-x-2 justify-center">
                                        {{-- Editar: va a la ruta productos.edit --}}
                                        <a href="{{ route('productos.edit', $producto->id) }}"
                                            class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center"
                                            title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </a>

                                        {{-- Ver --}}
                                        <x-tabla.boton-ver :href="route('productos.show', $producto->id)" />

                                        <a href="{{ route('movimientos.create', ['codigo' => $producto->codigo]) }}"
                                            class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                            title="Mover producto">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M8.5 2A1.5 1.5 0 0 1 10 3.5V10h.5V4A1.5 1.5 0 0 1 13 4v6h.5V5.5a1.5 1.5 0 0 1 3 0V10h.5V7a1.5 1.5 0 0 1 3 0v9.5a3.5 3.5 0 0 1-7 0V18h-2a3 3 0 0 1-3-3v-4H8V3.5A1.5 1.5 0 0 1 8.5 2z" />
                                            </svg>
                                        </a>

                                        {{-- ② Icono compacto --}}
                                        <a href="{{ route('productos.consumir', $producto->id) }}"
                                            data-consumir="{{ route('productos.consumir', $producto->id) }}"
                                            class="btn-consumir w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center"
                                            title="Consumir">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                fill="currentColor" viewBox="0 0 24 24">
                                                <!-- 🔥 Llama para “consumir” -->
                                                <path
                                                    d="M13.5 3.5c-2 2-1.5 4-3 5.5s-4 1-4 5a6 6 0 0012 0c0-2-1-3.5-2-4.5s-1-3-3-6z" />
                                            </svg>
                                        </a>


                                        {{-- Eliminar --}}
                                        <x-tabla.boton-eliminar :action="route('productos.destroy', $producto->id)" />
                                    </div>
                                </td>


                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4 text-gray-500">No hay productos
                                    con esa descripción.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gradient-to-r from-blue-50 to-blue-100 border-t border-blue-300">
                            <td colspan="100%" class="px-6 py-3" style="padding: 0" colspan="999">
                                <div class="flex justify-end items-center gap-4 px-6 py-3 text-sm text-gray-700">
                                    <span class="font-semibold">Total peso filtrado:</span>
                                    <span class="text-base font-bold text-blue-800">
                                        {{ number_format($totalPesoInicial, 2, ',', '.') }} kg
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 📱 Tarjetas solo en pantallas pequeñas -->
        <div class="block md:hidden">
            <!-- Buscador por código -->
            {{-- <div class="mb-4">
                <form method="GET" action="{{ route('productos.index') }}"
                    class="flex flex-col sm:flex-row gap-2 items-center">
                    <input type="text" name="codigo" placeholder="Buscar por código..."
                        value="{{ request('codigo') }}"
                        class="w-full sm:w-64 px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-semibold">
                        Buscar
                    </button>
                    @if (request('codigo'))
                        <a href="{{ route('productos.index') }}"
                            class="text-sm text-gray-600 underline hover:text-gray-800">Limpiar</a>
                    @endif
                </form>
            </div> --}}
            <!-- Buscador con filtros personalizados -->
            <div class="mb-4">
                <form method="GET" action="{{ route('productos.index') }}"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 items-end">

                    <x-tabla.input name="codigo" label="Código" value="{{ request('codigo') }}"
                        placeholder="Buscar por QR..." autofocus />

                    <select id="producto_base_id" name="producto_base_id"
                        class="w-full px-2 py-1 border border-gray-300 rounded text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="" disabled selected>Seleccione un producto base</option>
                        <option value="">NINGUNO</option>
                        @foreach ($productosBase as $producto)
                            <option value="{{ $producto->id }}"
                                {{ old('producto_base_id') == $producto->id ? 'selected' : '' }}>
                                {{ strtoupper($producto->tipo) }} |
                                Ø{{ $producto->diametro }}{{ $producto->longitud ? ' | ' . $producto->longitud . ' m' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <x-tabla.botones-filtro ruta="productos.index" />
                </form>
            </div>

            <!-- Modo Tarjetas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($registrosProductos as $producto)
                    <div class="bg-white shadow-md rounded-lg p-4">
                        <h3 class="font-bold text-lg text-gray-700">ID: {{ $producto->id }}</h3>
                        <h3 class="font-bold text-lg text-gray-700">Código: {{ $producto->codigo }}</h3>
                        <p><strong>Fabricante:</strong> {{ $producto->fabricante->nombre ?? '—' }}</p>
                        <p>
                            <strong>Características:</strong>
                            {{ strtoupper($producto->productoBase->tipo ?? '—') }}
                            |
                            Ø{{ $producto->productoBase->diametro ?? '—' }}
                            {{ $producto->productoBase->longitud ? '| ' . $producto->productoBase->longitud . ' m' : '' }}
                        </p>

                        <p><strong>Nº Colada:</strong> {{ $producto->n_colada }}</p>
                        <p><strong>Nº Paquete:</strong> {{ $producto->n_paquete }}</p>
                        <p><strong>Peso Inicial:</strong> {{ $producto->peso_inicial }} kg</p>
                        <p><strong>Peso Stock:</strong> {{ $producto->peso_stock }} kg</p>
                        <p><strong>Estado:</strong> {{ $producto->estado }}</p>
                        <hr class="m-2 border-gray-300">

                        @if (isset($producto->ubicacion->nombre))
                            <p class="font-bold text-lg text-gray-800 break-words">{{ $producto->ubicacion->nombre }}
                            </p>
                        @elseif (isset($producto->maquina->nombre))
                            <p class="font-bold text-lg text-gray-800 break-words">{{ $producto->maquina->nombre }}
                            </p>
                        @else
                            <p class="font-bold text-lg text-gray-800 break-words">No está ubicada</p>
                        @endif

                        <p class="text-gray-600 mt-2">{{ $producto->created_at->format('d/m/Y H:i') }}</p>

                        <hr class="my-2 border-gray-300">
                        <td class="px-2 py-3 text-center border">
                            @php
                                $usuario = auth()->user();
                                $esOficina = $usuario->rol === 'oficina';
                                $esGruista = $usuario->rol !== 'oficina' && $usuario->maquina?->tipo === 'grua';
                            @endphp

                            @if ($esOficina || $esGruista)
                                <div class="flex flex-wrap gap-2 mt-4 w-full">
                                    <a href="{{ route('productos.show', $producto->id) }}"
                                        class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                        Ver
                                    </a>
                                    <a href="{{ route('productos.edit', $producto->id) }}"
                                        class="flex-1 bg-blue-400 hover:bg-blue-500 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                        Editar
                                    </a>
                                    <a href="{{ route('movimientos.create', ['codigo' => $producto->codigo]) }}"
                                        class="flex-1 bg-green-500 hover:bg-green-600 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                        Mover
                                    </a>

                                    <a href="{{ route('productos.consumir', $producto->id) }}"
                                        data-consumir="{{ route('productos.consumir', $producto->id) }}"
                                        class="btn-consumir flex-1 bg-red-500 hover:bg-red-600 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                        Consumir
                                    </a>

                                    <form action="{{ route('productos.destroy', $producto->id) }}" method="POST"
                                        class="form-eliminar flex-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn-eliminar w-full bg-gray-500 hover:bg-gray-600 text-white text-sm font-semibold py-2 px-2 rounded shadow">
                                            Eliminar
                                        </button>
                                    </form>

                                </div>
                            @endif

                        </td>

                    </div>
                @empty
                    <div class="col-span-3 text-center py-4">No hay productos disponibles.</div>
                @endforelse
            </div>
        </div>

        <!-- Paginación -->
        <x-tabla.paginacion :paginador="$registrosProductos" />
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Confirmar consumir
                // Use a single delegated listener on <body>
                document.body.addEventListener('click', (e) => {
                    const btn = e.target.closest('.btn-consumir');
                    if (!btn) return; // Click wasn’t on a consumir button

                    e.preventDefault();

                    // Prefer data-consumir; fall back to href
                    const url = btn.dataset.consumir || btn.getAttribute('href');

                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: 'Esta materia prima se marcará como consumida.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, consumir',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = url;
                        }
                    });
                });
                // Confirmar eliminación
                document.querySelectorAll('.form-eliminar').forEach(form => {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: "Esta acción eliminará la materia prima de forma permanente.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#6c757d',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                });
            });
        </script>

</x-app-layout>
