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

    <div class="w-full p-4 sm:p-4">

        {{-- <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
        <div class="mb-4 flex space-x-2">
            <a href="{{ route('entradas.create') }}" class="btn btn-primary">
                Crear Nueva Entrada
            </a>
        </div> --}}

        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="w-full border text-sm text-center">
                <thead class="bg-blue-600 text-white uppercase text-xs">
                    <tr>
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
                            <td class="px-3 py-2">{{ $entrada->albaran }}</td>
                            <td class="px-3 py-2">{{ $entrada->pedido->codigo ?? 'N/A' }}</td>
                            <td class="px-3 py-2">{{ $entrada->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $entrada->productos->count() }}</td>
                            <td class="px-3 py-2">{{ number_format($entrada->peso_total ?? 0, 2, ',', '.') }} kg
                            </td>
                            <td class="px-3 py-2">{{ $entrada->estado ?? 'N/A' }}</td>
                            <td class="px-3 py-2">{{ $entrada->user->nombre_completo ?? 'N/A' }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('entradas.show', $entrada->id) }}"
                                    class="text-blue-600 hover:underline text-sm">Ver</a> |
                                <a href="{{ route('entradas.edit', $entrada->id) }}"
                                    class="text-yellow-600 hover:underline text-sm">Editar</a> |
                                <x-boton-eliminar :action="route('entradas.destroy', $entrada->id)" />
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

        <div class="mt-4 flex justify-center">
            {{ $entradas->onEachSide(1)->links('vendor.pagination.bootstrap-5') }}
        </div>

    </div>
    <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
</x-app-layout>
