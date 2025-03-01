<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ubicaciones.index') }}" class="text-gray-600">
                {{ __('Ubicaciones') }}
            </a>
        </h2>
    </x-slot>



    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Mostrar mensajes de éxito o error -->
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


    <div class="container mt-5">
        <!-- Resumen de pesos por tipo y clasificación -->
        <div class="grid grid-cols-2 gap-4 p-4 mb-4 bg-white shadow rounded-lg">
            <div>
                <h3 class="font-bold text-lg">Total Encarretado por Diámetro</h3>
                @foreach ($pesoEncarretadoPorDiametro as $diametro => $peso)
                    <p class="text-sm text-gray-700">Ø {{ $diametro }} mm: <span
                            class="font-semibold">{{ number_format($peso, 2) }} kg</span></p>
                @endforeach
            </div>
            <div>
                <h3 class="font-bold text-lg">Total Barras por Longitud</h3>
                @foreach ($pesoBarrasPorLongitud as $longitud => $peso)
                    <p class="text-sm text-gray-700">{{ $longitud }} m: <span
                            class="font-semibold">{{ number_format($peso, 2) }} kg</span></p>
                @endforeach
            </div>
        </div>

        @foreach ($ubicacionesPorSector as $sector => $ubicaciones)
            <div class="mb-4 sector-card">
                <h3 class="sector-header">Sector {{ $sector }}</h3>
                <div class="mapa-sector">
                    @foreach ($ubicaciones as $ubicacion)
                        <div class="ubicacion">
                            <span><a href="{{ route('ubicaciones.show', $ubicacion->id) }}">{{ $ubicacion->ubicacion }}
                                </a></span>
                            <small>{{ $ubicacion->descripcion }}</small>
                            <!-- Mostrar los productos que contiene esta ubicación -->
                            @if ($ubicacion->productos->isEmpty())
                                <p class="text-gray-500 italic text-xs">No hay material en esta ubicación.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($ubicacion->productos as $producto)
                                        <div class="bg-gray-100 rounded-lg p-1 shadow-md text-center">
                                            <p class="text-xs text-gray-700 font-semibold">
                                                ID: {{ $producto->id }} | Ø {{ $producto->diametro }} mm
                                            </p>

                                        </div>
                                    @endforeach
                                </div>
                            @endif

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
