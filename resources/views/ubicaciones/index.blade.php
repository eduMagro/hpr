<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ubicaciones.index') }}" class="text-gray-600">
                {{ __('Ubicaciones') }}
            </a>
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">

        @foreach ($ubicacionesPorSector as $sector => $ubicaciones)
            <div class="mb-4 sector-card">
                <h3 class="sector-header">Sector {{ $sector }}</h3>
                <div class="mapa-sector">
                    @foreach ($ubicaciones as $ubicacion)
                        <div class="ubicacion">
                            <span>
                                <a href="{{ route('ubicaciones.show', $ubicacion->id) }}">
                                    {{ $ubicacion->ubicacion }}
                                </a>
                            </span>
                            <small>{{ $ubicacion->descripcion }}</small>

                            <!-- Productos -->
                            <div>
                                <h4 class="text-xs font-semibold text-gray-600 mt-2">Productos</h4>
                                @if ($ubicacion->productos->isEmpty())
                                    <p class="text-gray-500 italic text-xs">No hay productos.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($ubicacion->productos as $producto)
                                            <div class="bg-gray-100 rounded-lg p-1 shadow-md text-center">
                                                <p class="text-xs text-gray-700 font-semibold">
                                                    MP#{{ $producto->id }} | Ø {{ $producto->diametro }} mm
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Paquetes -->
                            <div>
                                <h4 class="text-xs font-semibold text-gray-600 mt-2">Paquetes</h4>
                                @if ($ubicacion->paquetes->isEmpty())
                                    <p class="text-gray-500 italic text-xs">No hay paquetes.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($ubicacion->paquetes as $paquete)
                                            <div class="bg-green-100 rounded-lg p-1 shadow-md text-center">
                                                <p class="text-xs text-gray-700 font-semibold">
                                                    Paquete#{{ $paquete->id }} |
                                                    {{ $paquete->planilla->codigo_limpio }} |
                                                    Peso: {{ number_format($paquete->peso, 2) }} kg
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/qr/ubicacionesQr.js') }}"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/ubicaciones/mapaUbis.css') }}">
</x-app-layout>
