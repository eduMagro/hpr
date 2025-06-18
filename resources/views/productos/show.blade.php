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
                    <p><strong>Fabricante:</strong> {{ $detalles_producto->fabricante->nombre ?? 'N/A' }}</p>
                    <p><strong>Tipo:</strong> {{ $detalles_producto->productoBase->tipo ?? 'N/A' }}</p>
                    <p><strong>Diámetro:</strong> {{ $detalles_producto->productoBase->diametro ?? 'N/A' }} mm</p>
                    <p><strong>Longitud:</strong> {{ $detalles_producto->productoBase->longitud ?? 'N/A' }} mm</p>
                    <p><strong>Nº Colada:</strong> {{ $detalles_producto->n_colada }}</p>
                    <p><strong>Nº Paquete:</strong> {{ $detalles_producto->n_paquete }}</p>
                    <p><strong>Peso Inicial:</strong> {{ $detalles_producto->peso_inicial }} kg</p>
                    <p><strong>Peso Stock:</strong> {{ $detalles_producto->peso_stock }} kg</p>
                    <p><strong>Estado:</strong> {{ $detalles_producto->estado }}</p>
                    <p><strong>Otros:</strong> {{ $detalles_producto->otros ?? 'N/A' }}</p>
                    <p>
                        <button
                            onclick="generateAndPrintQR('{{ $detalles_producto->id }}', '{{ $detalles_producto->n_colada }}', 'MATERIA PRIMA')"
                            class="btn btn-primary btn-sm">QR</button>
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

                    <div class="mt-2 flex space-x-2 justify-between">

                        {{-- Botón editar (como enlace con estilo de componente) --}}
                        <a href="{{ route('productos.edit', $detalles_producto->id) }}"
                            class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center"
                            title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                        </a>

                        {{-- Botón ver (componente ya existente) --}}
                        <x-tabla.boton-ver :href="route('productos.show', $detalles_producto->id)" />
                        {{-- Botón mover (personalizado con icono) --}}
                        <a href="{{ route('movimientos.create', ['codigo' => $detalles_producto->codigo]) }}"
                            class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                            title="Mover">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 11.5V6.75a1.25 1.25 0 112.5 0v5.25M9.5 11.5V4.75a1.25 1.25 0 112.5 0v6.75M12 11.5V5.75a1.25 1.25 0 112.5 0v5.75M14.5 11.5V8.25a1.25 1.25 0 112.5 0v5.75m0 0v.75a4.25 4.25 0 01-8.5 0v-.75" />
                            </svg>
                        </a>


                        {{-- Botón eliminar (componente ya existente) --}}
                        <x-tabla.boton-eliminar :action="route('productos.destroy', $detalles_producto->id)" />
                    </div>

                </div>
            @else
                <div class="col-span-3 text-center py-4">No hay productos disponibles.</div>


            @endif
        </div>

    </div>
    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
</x-app-layout>
