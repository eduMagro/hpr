<x-app-layout>
    <x-menu.ubicaciones :obras="$obras" :obra-actual-id="$obraActualId" color-base="emerald" />

    <div x-data="{
        openModal: false,
        openSectors: {},
        toggleAll() {
            const values = Object.values(this.openSectors);
            const allOpen = values.length && values.every(Boolean);
            Object.keys(this.openSectors).forEach(k => this.openSectors[k] = !allOpen);
        }
    }" class="max-w-7xl mx-auto py-6 space-y-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4 lg:p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-wide text-gray-500 dark:text-gray-400">Ubicaciones | {{ $nombreAlmacen }}</p>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestión de Ubicaciones</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Crea, navega y revisa las ubicaciones del almacén con acceso rápido al inventario.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button @click="toggleAll()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-tr from-gray-900 to-gray-700 hover:from-gray-800 hover:to-gray-900 text-white rounded-lg shadow text-sm font-semibold">
                        <span x-text="Object.values(openSectors).length && Object.values(openSectors).every(Boolean) ? 'Cerrar todo' : 'Abrir todo'"></span>
                    </button>
                    <a href="{{ route('ubicaciones.verInventario', ['almacen' => $obraActualId]) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg  text-white font-semibold shadow bg-gradient-to-tr from-gray-900 to-gray-700 hover:from-gray-800 hover:to-gray-900">
                        <span class="text-lg">📦</span> Hacer inventario
                    </a>
                </div>
            </div>
        </div>

        {{-- Sectores --}}
        <div class="space-y-4">
            @foreach ($ubicacionesPorSector as $sector => $ubicaciones)
            <div x-init="if (openSectors['{{ $sector }}'] === undefined) openSectors['{{ $sector }}'] = false"
                class="border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm bg-white dark:bg-gray-900 overflow-hidden">
                <button @click="openSectors['{{ $sector }}'] = !openSectors['{{ $sector }}']"
                    class="w-full flex items-center justify-between px-5 py-4 bg-gradient-to-tr from-gray-900 to-gray-700 hover:from-gray-800 hover:to-gray-900 text-white">
                    @php
                    $totalMP = $ubicaciones->reduce(fn($carry, $ubicacion) => $carry + $ubicacion->productos->count(), 0);
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-gray-600 text-white font-bold">S{{ $sector }}</span>
                        <div class="flex flex-col gap-1 items-start">
                            <p class="text-lg font-semibold">Sector {{ $sector }}</p>
                            <p class="text-xs text-white/80">Material en sector: {{ $totalMP }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-white/70">{{ count($ubicaciones) }} ubicaciones</span>
                        <svg :class="openSectors['{{ $sector }}'] ? 'rotate-180' : ''" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </button>

                <div x-show="openSectors['{{ $sector }}']" x-collapse
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-4">
                    @foreach ($ubicaciones as $ubicacion)
                    <div class="bg-slate-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex flex-col gap-2 shadow-sm hover:border-blue-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between w-full">
                            <a href="{{ route('ubicaciones.show', $ubicacion->id) }}" wire:navigate
                                class="text-sm font-semibold text-gray-900 dark:text-white hover:text-blue-700 dark:hover:text-blue-300 transition">
                                {{ $ubicacion->codigo ?? $ubicacion->id }}
                            </a>
                            <span class="text-xs px-2 py-1 rounded-full bg-gradient-to-tr from-gray-900 to-gray-700 text-white font-semibold">Material: {{ $ubicacion->productos->count() }}</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ $ubicacion->descripcion }}</p>

                        @php
                        $tieneProductos = $ubicacion->productos->isNotEmpty();
                        $tienePaquetes = $ubicacion->paquetes->isNotEmpty();
                        @endphp

                        @if (! $tieneProductos && ! $tienePaquetes)
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Ubicación sin material.</p>
                        @else
                        @if($tieneProductos)
                        <div class="w-full mt-1 space-y-1">
                            @foreach ($ubicacion->productos as $producto)
                            <div class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-400 rounded-md px-2 py-1 text-center">
                                <p class="text-[11px] text-gray-800 dark:text-gray-100 font-semibold">
                                    MP#{{ $producto->id }} | Ø {{ $producto->productoBase->diametro ?? $producto->diametro ?? 'N/D' }} mm
                                </p>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="border-2 border-dashed border-blue-200 dark:border-blue-700/70 rounded-2xl shadow-sm bg-white/70 dark:bg-gray-900/70 hover:border-blue-500 hover:shadow-md transition">
            <button @click="openModal = true"
                class="w-full flex items-center justify-between px-5 py-3 text-left">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-blue-600 text-white font-bold text-xl shadow-sm">+</span>
                    <div class="text-left">
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">Nueva ubicación</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Añade rápidamente otra ubicación dentro del almacén.</p>
                    </div>
                </div>
            </button>
        </div>

        <!-- Modal crear ubicación -->
        <div x-show="openModal" x-transition
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur">
            <div @click.away="openModal = false" class="bg-white dark:bg-gray-900 w-full max-w-lg p-6 rounded-xl shadow-2xl mx-4 border border-gray-200 dark:border-gray-800">
                <h2 class="text-center text-lg font-bold mb-4 text-gray-800 dark:text-white">
                    Crear Nueva Ubicación ({{ $nombreAlmacen }})
                </h2>

                <form method="POST" action="{{ route('ubicaciones.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="almacen" value="{{ $obraActualId }}">

                    <x-tabla.select name="sector" label="📍 Sector" :options="collect(range(1, 20))
                        ->mapWithKeys(
                            fn($i) => [str_pad($i, 2, '0', STR_PAD_LEFT) => str_pad($i, 2, '0', STR_PAD_LEFT)],
                        )
                        ->toArray()"
                        placeholder="Ej. 01, 02, 03..." />

                    <x-tabla.select name="ubicacion" label="📦 Ubicación" :options="collect(range(1, 100))
                        ->mapWithKeys(
                            fn($i) => [str_pad($i, 2, '0', STR_PAD_LEFT) => str_pad($i, 2, '0', STR_PAD_LEFT)],
                        )
                        ->toArray()"
                        placeholder="Ej. 01 a 100" />

                    <x-tabla.input name="descripcion" label="📝 Descripción"
                        placeholder="Ej. Entrada de barras largas" />

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="openModal = false"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-800">Cancelar</button>
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                            Crear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <!-- Vite: qr-bundle -->
    @vite(['resources/js/qr/qr-bundle.js'])
</x-app-layout>