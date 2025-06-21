<script>
    const RUTA_ALERTA = @json(route('alertas.store'));

    /* ────── Alpine factory per location ────── */
    window.inventarioUbicacion = function(productosEsperados, nombreUbicacion) {

        /* key used to persist scans for this location */
        const claveLS = `inv-${nombreUbicacion}`;

        return {
            /* props ---------------------------------------------------------------- */
            productosEsperados,
            nombreUbicacion,

            escaneados: [], // códigos OK
            sospechosos: [], // códigos inesperados
            ultimoCodigo: null, // para el flash visual

            /* audio */
            audioOk: null,
            audioError: null,

            /* lifecycle ----------------------------------------------------------- */
            init() {
                /* 1️⃣ recuperar progreso previo */
                this.escaneados = JSON.parse(localStorage.getItem(claveLS) || '[]');

                this.audioOk = document.getElementById('sonido-ok');
                this.audioError = document.getElementById('sonido-error');
            },

            /* helpers ------------------------------------------------------------- */
            reproducirOk() {
                if (!this.audioOk) return;
                this.audioOk.currentTime = 0;
                this.audioOk.play().catch(() => {});
            },
            reproducirError() {
                if (!this.audioError) return;
                this.audioError.currentTime = 0;
                this.audioError.play().catch(() => {});
            },

            progreso() {
                /* 0–1 decimal for width % bar */
                return (this.escaneados.length / this.productosEsperados.length) * 100;
            },

            procesarQR(codigo) {
                codigo = (codigo || '').trim();
                if (!codigo) return;

                if (this.productosEsperados.includes(codigo)) {
                    if (!this.escaneados.includes(codigo)) {
                        this.escaneados.push(codigo);
                        localStorage.setItem(claveLS, JSON.stringify(this.escaneados)); // 2️⃣ persist
                        this.reproducirOk();
                    }
                } else {
                    if (!this.sospechosos.includes(codigo)) {
                        this.sospechosos.push(codigo);
                        this.reproducirError();
                    }
                }

                /* 3️⃣ flash highlight */
                this.ultimoCodigo = codigo;
                setTimeout(() => (this.ultimoCodigo = null), 1200);
            },

            resetear() {
                /* 4️⃣ clear everything for this location */
                this.escaneados = [];
                this.sospechosos = [];
                this.ultimoCodigo = null;
                localStorage.removeItem(claveLS);
            },

            productoEscaneado(codigo) {
                return this.escaneados.includes(codigo);
            },

            reportarErrores() {
                const faltantes = this.productosEsperados.filter(c => !this.escaneados.includes(c));
                const inesperados = [...this.sospechosos];

                window.notificarProgramadorInventario({
                    ubicacion: this.nombreUbicacion,
                    faltantes,
                    inesperados
                });
            }
        };
    };
</script>


<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ubicaciones.index') }}" class="text-gray-600 hover:text-gray-800">
                {{ __('Ubicaciones') }}
            </a>
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6">
        @foreach ($ubicacionesPorSector as $sector => $ubicaciones)
            <h2 class="text-lg font-bold mt-6">Sector {{ $sector }}</h2>

            @foreach ($ubicaciones as $ubicacion)
                <!-- Componente Alpine independiente por ubicación -->
                <div x-data='inventarioUbicacion(@json($ubicacion->productos->pluck('codigo')), @json($ubicacion->ubicacion))'
                    class="bg-white shadow rounded-2xl overflow-hidden mt-4">
                    <!-- Cabecera -->
                    <div
                        class="flex flex-col sm:flex-row sm:justify-between sm:items-center bg-gray-800 text-white px-4 py-3 gap-3">
                        <div class="text-sm sm:text-base">
                            <span>Ubicación: <strong>{{ $ubicacion->ubicacion }}</strong></span>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-medium bg-gray-200 text-gray-900 ml-2">
                                {{ count($ubicacion->productos) }} prod. esperados
                            </span>
                        </div>
                        <!-- Input de escaneo para ESTA ubicación -->
                        <input type="text"
                            class="w-full sm:w-64 border border-gray-300 rounded-md px-3 py-2 text-xs text-gray-900 placeholder-gray-400 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow"
                            placeholder="Escanea aquí…"
                            x-on:keydown.enter.prevent="procesarQR($event.target.value); $event.target.value = ''"
                            x-ref="inputQR" @if ($loop->first && $loop->parent->first) autofocus @endif inputmode="none"
                            autocomplete="off">
                    </div>
                    <div class="h-2 bg-gray-200">
                        <div class="h-full bg-blue-500 transition-all duration-300" :style="`width: ${progreso()}%`">
                        </div>
                    </div>
                    <!-- Tabla de productos (visible >= sm) -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full text-xs md:text-sm divide-y divide-gray-200">
                            <thead class="bg-gray-100 text-gray-800">
                                <tr>
                                    <th class="px-2 py-1 text-center w-12">#</th>
                                    <th class="px-2 py-1 text-center">Código</th>
                                    <th class="px-2 py-1 text-center">Producto</th>
                                    <th class="px-2 py-1 text-center">Tipo</th>
                                    <th class="px-2 py-1 text-center">Ø / Long.</th>
                                    <th class="px-2 py-1 text-center">Peso</th>
                                    <th class="px-2 py-1 text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($ubicacion->productos as $idx => $producto)
                                    <tr
                                        :class="{
                                            'bg-green-50': productoEscaneado('{{ $producto->codigo }}'),
                                            'ring-2 ring-green-500 shadow-md scale-[1.01] transition-all duration-300 ease-out': ultimoCodigo === '{{ $producto->codigo }}'
                                        }">

                                        <td class="px-2 py-1 text-center">{{ $idx + 1 }}</td>
                                        <td class="px-2 py-1 text-xs text-center">{{ $producto->codigo }}</td>
                                        <td class="px-2 py-1 text-center">{{ $producto->nombre }}</td>
                                        <td class="px-2 py-1 capitalize text-center">{{ $producto->tipo }}</td>
                                        <td class="px-2 py-1 text-center">
                                            @if ($producto->tipo === 'encarretado')
                                                Ø {{ $producto->diametro }} mm
                                            @else
                                                {{ $producto->longitud }} m
                                            @endif
                                        </td>
                                        <td class="px-2 py-1 text-center">
                                            {{ number_format($producto->peso_inicial, 1, ',', '.') }}</td>
                                        <td class="px-2 py-1 text-center">
                                            <span x-show="productoEscaneado('{{ $producto->codigo }}')"
                                                class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-800">OK</span>
                                            <span x-show="!productoEscaneado('{{ $producto->codigo }}')"
                                                class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-100 text-yellow-800">Pend.</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Vista mobile (cards) -->
                    <div class="sm:hidden divide-y divide-gray-100 text-xs">
                        @foreach ($ubicacion->productos as $producto)
                            <div class="flex justify-between items-center py-2 px-3"
                                :class="productoEscaneado('{{ $producto->codigo }}') ? 'bg-green-50' : ''">
                                <div class="flex-1">
                                    <p class="font-semibold">{{ $producto->codigo }}</p>
                                    <p class="text-gray-600">{{ $producto->nombre }}</p>
                                </div>
                                <div class="text-right ml-2">
                                    <span x-cloak x-show="productoEscaneado('{{ $producto->codigo }}')"
                                        class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-[10px] font-semibold">OK</span>
                                    <span x-show="!productoEscaneado('{{ $producto->codigo }}')"
                                        class="inline-block px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 text-[10px] font-semibold">Pend.</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Productos inesperados -->
                    <div x-cloak class="px-4 py-3" x-show="sospechosos.length">
                        <h3 class="text-sm font-semibold text-red-600 mb-1">Productos inesperados:</h3>
                        <ul class="list-disc list-inside text-xs text-red-700 space-y-0.5">
                            <template x-for="codigo in sospechosos" :key="codigo">
                                <li x-text="codigo"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 px-4 py-4">
                        <button @click="resetear()"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-3 py-1.5 rounded-md text-xs shadow">
                            Limpiar escaneos
                        </button>

                        <button @click="reportarErrores()"
                            class="bg-red-600 hover:bg-red-700 text-white font-semibold px-3 py-1.5 rounded-md text-xs shadow">
                            Reportar errores
                        </button>
                    </div>

                </div>
            @endforeach
        @endforeach
    </div>

    <audio id="sonido-ok" src="{{ asset('sonidos/ok.mp3') }}" preload="auto"></audio>
    <audio id="sonido-error" src="{{ asset('sonidos/error.mp3') }}" preload="auto"></audio>
</x-app-layout>
