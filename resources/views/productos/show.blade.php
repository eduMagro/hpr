<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">

            {{ __('Detalles Materia Prima') }}

        </h2>
    </x-slot>
    <div class="container mx-auto px-4 py-6">
        <!-- Tarjetas de productos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            @if (isset($detalles_producto))
                <div class="bg-white shadow-md rounded-lg p-4">

                    <h3 class="font-bold text-lg text-gray-700">ID Producto: {{ $detalles_producto->id }}</h3>
                    <p><strong>Fabricante:</strong> {{ $detalles_producto->fabricante }}</p>
                    <p><strong>Nombre:</strong> {{ $detalles_producto->nombre }}</p>
                    <p><strong>Tipo:</strong> {{ $detalles_producto->tipo }}</p>
                    <p><strong>Diámetro:</strong> {{ $detalles_producto->diametro }}</p>
                    <p><strong>Longitud:</strong> {{ $detalles_producto->longitud ?? 'N/A' }}</p>
                    <p><strong>Nº Colada:</strong> {{ $detalles_producto->n_colada }}</p>
                    <p><strong>Nº Paquete:</strong> {{ $detalles_producto->n_paquete }}</p>
                    <p><strong>Peso Inicial:</strong> {{ $detalles_producto->peso_inicial }} kg</p>
                    <p><strong>Peso Stock:</strong> {{ $detalles_producto->peso_stock }} kg</p>
                    <p><strong>Estado:</strong> {{ $detalles_producto->estado }}</p>
                    <p><strong>Otros:</strong> {{ $detalles_producto->otros ?? 'N/A' }}</p>
                    <p>
                        <button onclick="generateAndPrintQR('{{ $detalles_producto->id }}')"
                            class="btn btn-primary">Imprimir
                            QR</button>
                    </p>
                    <div id="qrCanvas{{ $detalles_producto->id }}" style="display:none;"></div>

                    <hr class="m-2 border-gray-300">

                    <!-- Detalles de Ubicación o Máquina -->
                    @if (isset($detalles_producto->ubicacion->nombre))
                        <p class="font-bold text-lg text-gray-800 break-words">
                            {{ $detalles_producto->ubicacion->nombre }}</p>
                    @elseif (isset($detalles_producto->maquina->nombre))
                        <p class="font-bold text-lg text-gray-800 break-words">
                            {{ $detalles_producto->maquina->nombre }}
                        </p>
                    @else
                        <p class="font-bold text-lg text-gray-800 break-words">No está ubicada</p>
                    @endif
                    <p class="text-gray-600 mt-2">{{ $detalles_producto->created_at->format('d/m/Y H:i') }}</p>

                    <hr class="my-2 border-gray-300">

                    <div class="mt-2 flex justify-between">
                        {{-- sweet alert para eliminar --}}
                        <x-boton-eliminar :action="route('productos.destroy', $producto->id)" />
                        <!-- Enlace para editar -->
                        <a href="{{ route('productos.edit', $producto->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                        <a href="{{ route('movimientos.create', ['producto_id' => $producto->id]) }}"
                            class="text-green-500 hover:text-green-700 text-sm">Mover</a>

                        <a href="{{ route('productos.show', $producto->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>
                    </div>
                </div>
            @else
                <div class="col-span-3 text-center py-4">No hay productos disponibles.</div>


            @endif
        </div>

    </div>
</x-app-layout>
