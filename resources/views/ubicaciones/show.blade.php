<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ubicaciones.index') }}" class="text-gray-600">
                {{ __('Ubicaciones') }}
            </a>
        </h2>
    </x-slot>

    <div class="container mx-auto mt-5">
        <h2 class="text-2xl font-bold text-center mb-4">{{ $ubicacion->nombre }}</h2>
        <h4 class="text-xl text-gray-700 text-center mb-6">{{ $ubicacion->codigo }}</h4>

        <div class="bg-white shadow-md rounded-lg p-6">
            <p class="mb-4">

                <button onclick="generateAndPrintQR('{{ $ubicacion->id }}', '{{ $ubicacion->nombre }}', 'UBICACIÓN')"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    QR
                </button>

            <div id="qrCanvas" style="display:none;"></div>
            <h3 class="text-xl font-semibold mb-3">Productos en esta Ubicación</h3>

            @if ($ubicacion->productos->isEmpty())
                <p class="text-gray-500 italic">No hay productos en esta ubicación.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($ubicacion->productos as $producto)
                        <div class="bg-gray-100 p-4 rounded-lg shadow">
                            <h3 class="font-bold text-lg text-gray-700">ID Producto: {{ $producto->id }}</h3>
                            <p><strong>Fabricante:</strong> {{ $producto->fabricante }}</p>
                            <p><strong>Nombre:</strong> {{ $producto->nombre }}</p>
                            <p><strong>Tipo:</strong> {{ $producto->tipo }}</p>
                            <p><strong>Diámetro:</strong> {{ $producto->diametro }}</p>
                            <p><strong>Longitud:</strong> {{ $producto->longitud ?? 'N/A' }}</p>
                            <p><strong>Nº Colada:</strong> {{ $producto->n_colada }}</p>
                            <p><strong>Nº Paquete:</strong> {{ $producto->n_paquete }}</p>
                            <p><strong>Peso Inicial:</strong> {{ $producto->peso_inicial }} kg</p>
                            <p><strong>Peso Stock:</strong> {{ $producto->peso_stock }} kg</p>
                            <p><strong>Estado:</strong> {{ $producto->estado }}</p>
                            <p><strong>Otros:</strong> {{ $producto->otros ?? 'N/A' }}</p>
                            <a href="{{ route('productos.show', $producto->id) }}"
                                class="mt-2 inline-block bg-blue-500 text-white text-xs px-3 py-1 rounded-md hover:bg-blue-600 transition">
                                Ver
                            </a>
                        </div>
                    @endforeach

                </div>
            @endif
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('ubicaciones.index') }}"
                class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition">Volver</a>
        </div>
    </div>
    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/imprimirQr.js') }}"></script>
</x-app-layout>
