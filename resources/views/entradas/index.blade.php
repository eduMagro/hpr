<x-app-layout>
    <x-slot name="title">Entradas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Entradas de Material') }}
        </h2>
    </x-slot>

    <div class="w-full p-4 sm:p-4">

        <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
        <div class="mb-4 flex space-x-2">
            <a href="{{ route('entradas.create') }}" class="btn btn-primary">
                Crear Nueva Entrada
            </a>
            <a href="{{ route('pedidos.index') }}" class="btn btn-secondary bg-yellow-500 hover:bg-yellow-600 text-white">
                Ir a Pedidos de Compra
            </a>
            <a href="{{ route('proveedores.index') }}"
                class="btn btn-secondary bg-yellow-500 hover:bg-yellow-600 text-white">
                Ver Proveedores
            </a>
        </div>

        <div class="my-6">
            <h3 class="text-lg font-semibold mb-3 text-gray-800">Stock disponible en almacén</h3>
            <div class="overflow-x-auto rounded-lg">
                <form action="{{ route('pedidos.confirmar') }}" method="POST">
                    @csrf

                    <table class="w-full text-sm border-collapse border text-center mt-6">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="border px-2 py-1">✔</th>
                                <th class="border px-2 py-1">Tipo</th>
                                <th class="border px-2 py-1">Diámetro</th>
                                <th class="border px-2 py-1">Peso Pendiente</th>
                                <th class="border px-2 py-1">Stock Disponible</th>
                                <th class="border px-2 py-1">Pedido</th>

                                <th class="border px-2 py-1">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comparativa as $clave => $c)
                                <tr class="{{ $c['diferencia'] < 0 ? 'bg-red-100' : 'bg-green-100' }}">
                                    <td class="border px-2 py-1">
                                        <input type="checkbox" name="seleccionados[]" value="{{ $clave }}">
                                        <input type="hidden" name="detalles[{{ $clave }}][tipo]"
                                            value="{{ $c['tipo'] }}">
                                        <input type="hidden" name="detalles[{{ $clave }}][diametro]"
                                            value="{{ $c['diametro'] }}">
                                        <input type="hidden" name="detalles[{{ $clave }}][cantidad]"
                                            value="{{ abs($c['diferencia']) }}">
                                    </td>
                                    <td class="border px-2 py-1">{{ ucfirst($c['tipo']) }}</td>
                                    <td class="border px-2 py-1">{{ $c['diametro'] }} mm</td>
                                    <td class="border px-2 py-1">{{ number_format($c['pendiente'], 2, ',', '.') }} kg
                                    </td>
                                    <td class="border px-2 py-1">{{ number_format($c['disponible'], 2, ',', '.') }} kg
                                    </td>
                                    <td class="border px-2 py-1">{{ number_format($c['pedido'], 2, ',', '.') }} kg</td>

                                    <td class="border px-2 py-1 font-bold">
                                        {{ number_format($c['diferencia'], 2, ',', '.') }} kg</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4 text-right">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Crear pedido con seleccionados
                        </button>
                    </div>
                </form>


            </div>
        </div>

        <!-- Usamos una estructura de tarjetas para dispositivos móviles -->

        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="w-full border text-sm text-center">
                <thead class="bg-blue-600 text-white uppercase text-xs">
                    <tr>
                        <th class="px-3 py-2 border">Albarán</th>
                        <th class="px-3 py-2 border">Fecha</th>
                        <th class="px-3 py-2 border">Nº Productos</th>
                        <th class="px-3 py-2 border">Peso Total</th>
                        <th class="px-3 py-2 border">Usuario</th>
                        <th class="px-3 py-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entradas as $entrada)
                        <tr class="border-b hover:bg-blue-50">
                            <td class="px-3 py-2">{{ $entrada->albaran }}</td>
                            <td class="px-3 py-2">{{ $entrada->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $entrada->productos->count() }}</td>
                            <td class="px-3 py-2">{{ number_format($entrada->peso_total ?? 0, 2, ',', '.') }} kg
                            </td>
                            <td class="px-3 py-2">{{ $entrada->usuario->name ?? 'N/A' }}</td>
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


    <div class="mt-4 flex justify-center">
        {{ $entradas->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
</x-app-layout>
