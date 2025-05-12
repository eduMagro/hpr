<x-app-layout>
    <x-slot name="title">Entradas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Entradas de Material') }}
            <span class="mx-2">/</span>
            <a href="{{ route('pedidos.index') }}" class="text-blue-600">
                {{ __('Pedidos de Compra') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('pedidos_globales.index') }}" class="text-blue-600">
                {{ __('Pedidos Globales') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('proveedores.index') }}" class="text-blue-600">
                {{ __('Proveedores') }}
            </a>
        </h2>
    </x-slot>

    <div class="w-full p-4 sm:p-4">

        <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
        <div class="mb-4 flex space-x-2">
            <a href="{{ route('entradas.create') }}" class="btn btn-primary">
                Crear Nueva Entrada
            </a>
        </div>

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
                            <td class="px-3 py-2">{{ $entrada->user->name ?? 'N/A' }}</td>
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
