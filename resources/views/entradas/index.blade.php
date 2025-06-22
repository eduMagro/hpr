<x-app-layout>
    <x-slot name="title">Entradas - {{ config('app.name') }}</x-slot>
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

                    <a href="{{ route('productos.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'productos.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📦 Materiales
                    </a>
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

                    <a href="{{ route('fabricantes.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'fabricantes.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🏭 Proveedores
                    </a>
                </div>
            </div>

            <!-- Menú escritorio -->
            <div class="hidden sm:flex sm:mt-0 w-full">
                <a href="{{ route('productos.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ $rutaActual === 'productos.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📦 Materiales
                </a>
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

                <a href="{{ route('fabricantes.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ $rutaActual === 'fabricantes.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🏭 Proveedores
                </a>
            </div>
        </div>
    @endif

    <div class="w-full p-4 sm:p-4">

        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="w-full border text-sm text-center">
                <thead class="bg-blue-600 text-white uppercase text-xs">
                    <tr>
                        <th class="px-3 py-2 border">ID</th>
                        <th class="px-3 py-2 border">Albarán</th>
                        <th class="px-3 py-2 border">Pedido Compra</th>
                        <th class="px-3 py-2 border">Fecha</th>
                        <th class="px-3 py-2 border">Nº Productos</th>
                        <th class="px-3 py-2 border">Peso Total</th>
                        <th class="px-3 py-2 border">Estado</th>
                        <th class="px-3 py-2 border">Usuario</th>
                        <th class="px-3 py-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entradas as $entrada)
                        <tr class="border-b hover:bg-blue-50">
                            <td class="px-3 py-2">{{ $entrada->id }}</td>
                            <td class="px-3 py-2">{{ $entrada->albaran }}</td>
                            @php
                                $pedido = $entrada->pedido; // Relación podría ser null
                            @endphp

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
                            <td class="px-3 py-2">{{ $entrada->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2 text-center">
                                @if ($entrada->productos_count > 0)
                                    <a href="{{ route('productos.index', ['entrada_id' => $entrada->id]) }}"
                                        class="text-blue-600 hover:underline">
                                        {{ $entrada->productos_count }}
                                    </a>
                                @else
                                    0
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ number_format($entrada->peso_total ?? 0, 2, ',', '.') }} kg
                            </td>
                            <td class="px-3 py-2">{{ $entrada->estado ?? 'N/A' }}</td>
                            <td class="px-3 py-2">{{ $entrada->user->nombre_completo ?? 'N/A' }}</td>
                            {{-- <td class="px-3 py-2">
                                <a href="{{ route('entradas.show', $entrada->id) }}"
                                    class="text-blue-600 hover:underline text-sm">Ver</a> |
                                <a href="{{ route('entradas.edit', $entrada->id) }}"
                                    class="text-yellow-600 hover:underline text-sm">Editar</a> |
                                <x-tabla.boton-eliminar :action="route('entradas.destroy', $entrada->id)" />
                            </td> --}}
                            <td class="px-2 py-2 border text-xs font-bold">
                                <div class="flex items-center space-x-2 justify-center">
                                    {{-- Editar: va a la ruta entradas.edit --}}
                                    <a href="{{ route('entradas.edit', $entrada->id) }}"
                                        class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center"
                                        title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </a>

                                    {{-- Ver --}}
                                    <a href="#"
                                        class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center abrir-modal-dibujo"
                                        data-productos='@json($entrada->productos)'>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>

                                    {{-- Eliminar --}}
                                    <x-tabla.boton-eliminar :action="route('productos.destroy', $entrada->id)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-gray-500">No hay entradas de material registradas.
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
                <h2 class="text-lg font-semibold">Productos de la entrada</h2>
                <button onclick="cerrarModal()" class="text-red-600 hover:text-red-800">✖</button>
            </div>
            <div id="contenidoProductos">
                <p class="text-gray-500">Cargando productos...</p>
            </div>
        </div>
    </div>


    <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
    <script>
        document.querySelectorAll('.abrir-modal-dibujo').forEach(boton => {
            boton.addEventListener('click', function(e) {
                e.preventDefault();

                const productos = JSON.parse(this.dataset.productos);
                let html = '<ul class="list-disc pl-5 space-y-1">';

                if (productos.length > 0) {
                    productos.forEach(p => {
                        html += `<li>${p.nombre} – ${p.cantidad ?? ''}</li>`;
                    });
                } else {
                    html += '<li class="text-gray-500">No hay productos asociados.</li>';
                }

                html += '</ul>';

                document.getElementById('contenidoProductos').innerHTML = html;
                document.getElementById('modalDibujo').classList.remove('hidden');
            });
        });

        function cerrarModal() {
            document.getElementById('modalDibujo').classList.add('hidden');
            document.getElementById('contenidoProductos').innerHTML = '';
        }
    </script>


</x-app-layout>
