<x-app-layout>
    <x-menu.ubicaciones :obras="$obras" :obra-actual-id="$obraActualId" color-base="emerald" />

    <div class="container mx-auto px-4 py-6">
        <div x-data="{ openModal: false }">
            <a @click="openModal = true"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow transition cursor-pointer">
                ➕ Nueva Ubicación ({{ $nombreAlmacen }})
            </a>
            <a href="{{ route('ubicaciones.verInventario', ['almacen' => $obraActualId]) }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow transition cursor-pointer">
                📦 Inventario
            </a>


            <div x-show="openModal" x-transition
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div @click.away="openModal = false" class="bg-white w-full max-w-lg p-6 rounded-xl shadow-xl mx-4">
                    <h2 class="text-center text-lg font-bold mb-4 text-gray-800">
                        Crear Nueva Ubicación ({{ $nombreAlmacen }})
                    </h2>

                    <form method="POST" action="{{ route('ubicaciones.store') }}" class="space-y-4">
                        @csrf

                        {{-- Campo oculto con el valor de la nave --}}
                        <input type="hidden" name="almacen" value="0B">

                        {{-- Sector --}}
                        <x-tabla.select name="sector" label="📍 Sector" :options="collect(range(1, 20))
                            ->mapWithKeys(
                                fn($i) => [str_pad($i, 2, '0', STR_PAD_LEFT) => str_pad($i, 2, '0', STR_PAD_LEFT)],
                            )
                            ->toArray()"
                            placeholder="Ej. 01, 02, 03..." />

                        {{-- Ubicación --}}
                        <x-tabla.select name="ubicacion" label="📦 Ubicación" :options="collect(range(1, 100))
                            ->mapWithKeys(
                                fn($i) => [str_pad($i, 2, '0', STR_PAD_LEFT) => str_pad($i, 2, '0', STR_PAD_LEFT)],
                            )
                            ->toArray()"
                            placeholder="Ej. 01 a 100" />

                        {{-- Descripción --}}
                        <x-tabla.input name="descripcion" label="📝 Descripción"
                            placeholder="Ej. Entrada de barras largas" />

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="openModal = false"
                                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Cancelar</button>
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                                ➕ Crear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Aquí mantienes el mismo bloque de visualización de ubicaciones --}}

        @foreach ($ubicacionesPorSector as $sector => $ubicaciones)
            <div class="mb-4 sector-card">
                <h3 class="sector-header">Sector {{ $sector }}</h3>
                <div class="mapa-sector">
                    @foreach ($ubicaciones as $ubicacion)
                        <div class="ubicacion">
                            <span>
                                <a href="{{ route('ubicaciones.show', $ubicacion->id) }}">
                                    {{ $ubicacion->id }}
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
