<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('entradas.index') }}" class="text-blue-600">
                {{ __('Entradas') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Lista de Pedidos') }}
        </h2>
    </x-slot>

    <div class="px-4 py-6">
        <div class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="w-full border-collapse text-sm text-center">
                <thead class="bg-blue-600 text-white">
                    <tr>
                        <th class="border px-3 py-2">Código</th>
                        <th class="border px-3 py-2">Proveedor</th>
                        <th class="border px-3 py-2">F. Pedido</th>
                        <th class="border px-3 py-2">F. Estimada Entrega</th>
                        <th class="border px-3 py-2">Estado</th>
                        <th class="border px-3 py-2">Líneas</th>
                        <th class="border px-3 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedidos as $pedido)
                        <tr class="odd:bg-gray-50 even:bg-gray-100 hover:bg-blue-100">
                            <td class="border px-3 py-2">{{ $pedido->codigo }}</td>
                            <td class="border px-3 py-2">{{ $pedido->proveedor->nombre }}</td>
                            <td class="border px-3 py-2">{{ $pedido->fecha_pedido->format('d/m/Y') }}</td>
                            <td class="border px-3 py-2">{{ $pedido->fecha_estimada->format('d/m/Y') }}</td>
                            <td class="border px-3 py-2 capitalize">{{ $pedido->estado }}</td>
                            <td class="border px-3 py-2">{{ $pedido->productos->count() }}</td>
                            <td class="border px-3 py-2 space-x-2">
                                <a href="{{ route('pedidos.show', $pedido->id) }}"
                                    class="text-blue-600 hover:underline">Ver</a>
                                <x-boton-eliminar :action="route('pedidos.destroy', $pedido->id)" />
                                @if ($pedido->estado !== 'completo')
                                    <a href="{{ route('pedidos.recepcion', $pedido->id) }}"
                                        class="text-green-600 hover:underline">Recepcionar</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-gray-500">No hay pedidos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $pedidos->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>
</x-app-layout>
