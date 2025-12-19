@php
    $detalles = \App\Models\Producto::with('productoBase')
        ->get()
        ->mapWithKeys(function ($p) {
            return [
                $p->codigo => [
                    'codigo' => $p->codigo,
                    'nombre' => $p->nombre,
                    'tipo' => optional($p->productoBase)->tipo,
                    'diametro' => optional($p->productoBase)->diametro,
                    'longitud' => optional($p->productoBase)->longitud,
                    'peso_inicial' => $p->peso_inicial,
                ],
            ];
        });
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ubicaciones.index') }}" wire:navigate class="text-gray-600 hover:text-gray-800">
                {{ __('Ubicaciones') }}
            </a>
        </h2>
    </x-slot>

    <div id="contenido"
        class="max-w-7xl gap-2 flex flex-col altura-c h-[calc(100vh-90px)] w-screen mx-auto transition-all duration-200">
        @foreach ($ubicacionesPorSector as $sector => $ubicaciones)
            <div x-data="{ abierto: false }" class="h-full w-full">

                <!-- Encabezado del sector con botón para expandir -->
                <button @click="abierto = !abierto"
                    class="escondible w-full h-full flex items-center justify-between px-4 py-3 bg-gray-800  text-white font-semibold text-left text-xl hover:bg-gray-700 min-h-20">
                    <span>Sector {{ $sector }}</span>
                    <svg :class="abierto ? 'rotate-90' : ''" class="w-4 h-4 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Contenido del sector -->
                <div x-show="abierto" x-transition>
                    @foreach ($ubicaciones as $ubicacion)
                        <!-- Componente Alpine independiente por ubicación -->
                        <div x-data='inventarioUbicacion(@json($ubicacion->productos->pluck('codigo')), "{{ $ubicacion->id }}")'
                            x-on:producto-reasignado.window="handleProductoReasignado($event)"
                            :key="{{ json_encode($ubicacion->id) }}"
                            class="bg-white shadow rounded-2xl overflow-hidden mt-4">

                            <!-- Cabecera -->
                            <div
                                class="desplegar-subcontenido flex flex-row justify-between items-center bg-gray-800 text-white px-4 py-3 gap-3 hover:bg-gray-700 cursor-pointer">
                                <div class="text-sm sm:text-base">
                                    <span><strong>{{ $ubicacion->id }} -- {{ $ubicacion->codigo }} --
                                            {{ $ubicacion->descripcion }}</strong></span>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-medium bg-gray-200 text-gray-900 ml-2">
                                        {{ count($ubicacion->productos) }} prod. esperados
                                    </span>
                                </div>
                                <!-- Input de escaneo para ESTA ubicación -->
                                <input type="text"
                                    class="hidden qr-input w-64 border border-gray-300 rounded-md px-3 py-2 text-xs text-gray-900 placeholder-gray-400 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow"
                                    placeholder="Escanea aquí…"
                                    x-on:keydown.enter.prevent="procesarQR($event.target.value); $event.target.value = ''"
                                    x-ref="inputQR" inputmode="none" autocomplete="off">

                                <div class="qr-desplegable-info">></div>
                            </div>

                            <!-- ///////////////////////////////// -->
                            <div class="subcontenido overflow-hidden h-0 opacity-0">

                                <div class="h-2 bg-gray-200">
                                    <div class="h-full bg-blue-500 transition-all duration-300"
                                        :style="`width: ${progreso()}%`"></div>
                                    <div class="text-xs text-right px-4 py-1 text-gray-500">
                                        <span
                                            x-text="`${escaneados.length} / ${productosEsperados.length} escaneados`"></span>
                                    </div>
                                </div>

                                <!-- Tabla de productos (visible >= sm) -->
                                <div class="hidden sm:block overflow-x-auto">
                                    <table class="min-w-full text-xs md:text-sm divide-y divide-gray-200">
                                        <thead class="bg-gray-100 text-gray-800">
                                            <tr>
                                                <th class="px-2 py-1 text-center w-12">#</th>
                                                <th class="px-2 py-1 text-center">Código</th>
                                                <th class="px-2 py-1 text-center">Tipo</th>
                                                <th class="px-2 py-1 text-center">Ø / Long.</th>
                                                <th class="px-2 py-1 text-center">Peso</th>
                                                <th class="px-2 py-1 text-center">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($ubicacion->productos as $idx => $producto)
                                                <tr x-show="(asignados['{{ $producto->codigo }}']?.toString() || '{{ $ubicacion->id }}') === nombreUbicacion.toString()"
                                                    :class="{
                                                        'bg-green-50': productoEscaneado('{{ $producto->codigo }}'),
                                                        'ring-2 ring-green-500 shadow-md scale-[1.01] transition-all duration-300 ease-out': ultimoCodigo === '{{ $producto->codigo }}'
                                                    }">
                                                    <td class="px-2 py-1 text-center">{{ $idx + 1 }}</td>
                                                    <td class="px-2 py-1 text-xs text-center">{{ $producto->codigo }}
                                                    </td>
                                                    <td class="px-2 py-1 capitalize text-center">
                                                        {{ $producto->productoBase->tipo }}
                                                    </td>
                                                    <td class="px-2 py-1 text-center">
                                                        @if ($producto->productoBase->tipo === 'encarretado')
                                                            Ø {{ $producto->productoBase->diametro }} mm
                                                        @else
                                                            Ø {{ $producto->productoBase->diametro }} mm /
                                                            {{ $producto->productoBase->longitud }} m
                                                        @endif
                                                    </td>
                                                    <td class="px-2 py-1 text-center">
                                                        {{ number_format($producto->peso_inicial, 1, ',', '.') }}
                                                    </td>
                                                    <td class="px-2 py-1 text-center">
                                                        <span x-show="productoEscaneado('{{ $producto->codigo }}')"
                                                            class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-800">OK</span>
                                                        <span x-show="!productoEscaneado('{{ $producto->codigo }}')"
                                                            class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-100 text-yellow-800">Pend.</span>
                                                    </td>
                                                </tr>
                                            @endforeach

                                            <!-- Filas dinámicas añadidas tras reasignar -->
                                            <template x-for="codigo in productosAnadidos()" :key="codigo">
                                                <tr class="bg-white">
                                                    <td class="px-2 py-1 text-center">+</td>
                                                    <td class="px-2 py-1 text-xs text-center" x-text="codigo"></td>
                                                    <td class="px-2 py-1 capitalize text-center"
                                                        x-text="window.detallesProductos[codigo]?.tipo || '—'"></td>
                                                    <td class="px-2 py-1 text-center">
                                                        <template
                                                            x-if="(window.detallesProductos[codigo]?.tipo || '') === 'encarretado'">
                                                            <span>
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro ?? '—'"></span>
                                                                mm
                                                            </span>
                                                        </template>
                                                        <template
                                                            x-if="(window.detallesProductos[codigo]?.tipo || '') !== 'encarretado'">
                                                            <span>
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro ?? '—'"></span>
                                                                mm /
                                                                <span
                                                                    x-text="window.detallesProductos[codigo]?.longitud ?? '—'"></span>
                                                                m
                                                            </span>
                                                        </template>
                                                    </td>
                                                    <td class="px-2 py-1 text-center">
                                                        <span
                                                            x-text="(window.detallesProductos[codigo]?.peso_inicial ?? 0).toLocaleString('es-ES', {minimumFractionDigits:1, maximumFractionDigits:1})"></span>
                                                    </td>
                                                    <td class="px-2 py-1 text-center">
                                                        <span
                                                            class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-800">OK</span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Vista mobile (cards) -->
                                <div class="sm:hidden divide-y divide-gray-100 text-xs">
                                    @foreach ($ubicacion->productos as $producto)
                                        <div x-show="(asignados['{{ $producto->codigo }}']?.toString() || '{{ $ubicacion->id }}') === nombreUbicacion.toString()"
                                            class="flex justify-between items-center py-2 px-3"
                                            :class="productoEscaneado('{{ $producto->codigo }}') ? 'bg-green-50' : ''">
                                            <div class="flex-1">
                                                <p class="font-semibold">{{ $producto->codigo }}</p>
                                                <p class="text-gray-600">{{ $producto->nombre }}</p>
                                                <p class="text-gray-500">
                                                    {{ ucfirst($producto->productoBase->tipo) }} —
                                                    Ø {{ $producto->productoBase->diametro }} mm
                                                    @if ($producto->productoBase->tipo !== 'encarretado')
                                                        / {{ $producto->productoBase->longitud }} m
                                                    @endif
                                                </p>
                                                <p class="text-gray-500">
                                                    {{ number_format($producto->peso_inicial, 1, ',', '.') }} kg
                                                </p>
                                            </div>
                                            <div class="text-right ml-2">
                                                <span x-cloak x-show="productoEscaneado('{{ $producto->codigo }}')"
                                                    class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-[10px] font-semibold">OK</span>
                                                <span x-show="!productoEscaneado('{{ $producto->codigo }}')"
                                                    class="inline-block px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 text-[10px] font-semibold">Pend.</span>
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- Cards dinámicas añadidas tras reasignar (móvil) -->
                                    <template x-for="codigo in productosAnadidos()" :key="codigo">
                                        <div class="flex justify-between items-center py-2 px-3 bg-green-50">
                                            <div class="flex-1">
                                                <p class="font-semibold" x-text="codigo"></p>
                                                <p class="text-gray-600"
                                                    x-text="window.detallesProductos[codigo]?.nombre || ''"></p>
                                                <p class="text-gray-500">
                                                    <span
                                                        x-text="(window.detallesProductos[codigo]?.tipo || '—').charAt(0).toUpperCase() + (window.detallesProductos[codigo]?.tipo || '—').slice(1)"></span>
                                                    —
                                                    Ø <span
                                                        x-text="window.detallesProductos[codigo]?.diametro ?? '—'"></span>
                                                    mm
                                                    <template
                                                        x-if="(window.detallesProductos[codigo]?.tipo || '') !== 'encarretado'">
                                                        <span>/ <span
                                                                x-text="window.detallesProductos[codigo]?.longitud ?? '—'"></span>
                                                            m</span>
                                                    </template>
                                                </p>
                                                <p class="text-gray-500">
                                                    <span
                                                        x-text="(window.detallesProductos[codigo]?.peso_inicial ?? 0).toLocaleString('es-ES', {minimumFractionDigits:1, maximumFractionDigits:1})"></span>
                                                    kg
                                                </p>
                                            </div>
                                            <div class="text-right ml-2">
                                                <span
                                                    x-text="(window.detallesProductos[codigo]?.peso_inicial ?? 0).toLocaleString('es-ES', {minimumFractionDigits:1, maximumFractionDigits:1})"></span>
                                                kg
                                                </p>
                                            </div>
                                            <div class="text-right ml-2">
                                                <span
                                                    class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-[10px] font-semibold">OK</span>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Productos inesperados -->
                                <div x-cloak class="px-4 py-3" x-show="sospechosos.length">
                                    <h3 class="text-sm font-semibold text-red-600 mb-1">Inesperado:</h3>

                                    <ul class="space-y-2">
                                        <template x-for="(codigo, idx) in sospechosos" :key="codigo">
                                            <li class="grid grid-cols-[1fr_auto] items-center gap-3 rounded-lg border px-3 py-2 shadow-sm"
                                                :class="idx % 2 === 0 ? 'bg-white border-gray-200' :
                                                    'bg-gray-50 border-gray-200'"
                                                x-data="{ ubic: null, hasId: false, misma: false, estado: null, esConsumido: false }" x-init="ubic = (asignados && Object.prototype.hasOwnProperty.call(asignados, codigo)) ? asignados[codigo] : null;
                                                hasId = (ubic !== null && ubic !== '' && ubic !== undefined);
                                                misma = (hasId && ubic.toString() === nombreUbicacion.toString());
                                                estado = (estados && Object.prototype.hasOwnProperty.call(estados, codigo)) ? (estados[codigo] ?? null) : null;
                                                esConsumido = (estado === 'consumido');">
                                                <!-- IZQUIERDA: código + estado -->
                                                <div class="min-w-0 flex gap-1 items-center">
                                                    <div class="flex items-center gap-2">
                                                        <!-- Punto de estado -->
                                                        <span class="inline-block h-2.5 w-2.5 rounded-full"
                                                            :class="esConsumido ? 'bg-blue-500/80' : (hasId ?
                                                                'bg-amber-500/80' : 'bg-red-500/80')"></span>

                                                        <!-- Código -->
                                                        <span class="text-xs sm:text-base break-all font-sans"
                                                            :class="esConsumido ? 'text-blue-600' : (hasId ? 'text-amber-500' :
                                                                'text-red-800')"
                                                            x-text="codigo"></span>

                                                        <!-- Subtexto/Chip -->
                                                        <div
                                                            class="inline-flex items-center px-1.5 py-0.5 text-sm sm:text-base rounded bg-gray-200 italic">
                                                            <span class="text-gray-900"
                                                                x-show="esConsumido">Consumido</span>
                                                            <span class="text-gray-900"
                                                                x-show="!esConsumido && hasId && !misma">
                                                                Ubicación: <span x-text="ubic"></span>
                                                            </span>
                                                            <span class="text-gray-900"
                                                                x-show="!esConsumido && !hasId">Sin registrar</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- DERECHA: acción -->
                                                <button
                                                    class="bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs sm:text-base w-24 sm:w-auto"
                                                    x-show="!esConsumido && hasId && !misma"
                                                    @click="reasignarProducto(codigo)">
                                                    Asignar aquí
                                                </button>

                                                <!-- Sólo si no es consumido -->
                                                <span
                                                    class="text-gray-800 px-3 py-1.5 rounded-md text-xs sm:text-base w-24 sm:w-auto"
                                                    x-show="!esConsumido && !hasId">
                                                    No asignable
                                                </span>
                                            </li>

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

                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach


    </div>

    <!-- Botón global para limpiar todos -->
    <div class="flex" id="btn_limpiar_todos_escaneos">
        <button onclick="window.limpiarTodosEscaneos()"
            class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-1 rounded shadow text-center">
            Limpiar TODOS los escaneos
        </button>
    </div>

    <!-- Audios -->
    <audio id="sonido-ok" src="{{ asset('sonidos/ok.mp3') }}" preload="auto"></audio>
    <audio id="sonido-error" src="{{ asset('sonidos/error.mp3') }}" preload="auto"></audio>
    <audio id="sonido-pedo" src="{{ asset('sonidos/pedo.mp3') }}" preload="auto"></audio>
    <audio id="sonido-estaEnOtraUbi" src="{{ asset('sonidos/estaEnOtraUbi.mp3') }}" preload="auto"></audio>
    <audio id="sonido-noTieneUbicacion" src="{{ asset('sonidos/noTieneUbicacion.mp3') }}" preload="auto"></audio>

    <style>
        .altura-c {
            height: calc(100vh - 90px);
        }
    </style>

    <script src="{{ asset('js/inventario/inventario.js') }}"></script>

    @push('scripts')
        <script>
            window.inventarioCtx = {
                asignados: @json(\App\Models\Producto::whereNotNull('ubicacion_id')->pluck('ubicacion_id', 'codigo')),
                detalles: @json($detalles),
                estados: @json(\App\Models\Producto::pluck('estado', 'codigo')),
                rutaAlerta: @json(route('alertas.store')),
                rutaReasignar: "{{ route('productos.editarUbicacionInventario', ['codigo' => '___CODIGO___']) }}",
                token: document.querySelector('meta[name="csrf-token"]')?.content
            };

            window.initInventarioPage = function() {
                if (document.body.dataset.inventarioPageInit === 'true') return;
                document.body.dataset.inventarioPageInit = 'true';

                // Helpers Globales
                window.productosAsignados = window.inventarioCtx.asignados;
                window.detallesProductos = window.inventarioCtx.detalles;
                window.productosEstados = window.inventarioCtx.estados;

                // Definir Factory solo si no existe
                if (!window.inventarioUbicacion) {
                    window.inventarioUbicacion = function(productosEsperados, nombreUbicacion) {
                        const claveLS = `inv-${nombreUbicacion}`;

                        return {
                            productosEsperados,
                            nombreUbicacion,
                            originalEsperados: [],
                            escaneados: [],
                            sospechosos: [],
                            ultimoCodigo: null,
                            asignados: {},
                            audioOk: null,
                            audioError: null,
                            audioPedo: null,
                            audioEstaEnOtraUbi: null,
                            audioNoTieneUbicacion: null,
                            estados: {},

                            init() {
                                this.originalEsperados = [...this.productosEsperados];
                                this.escaneados = JSON.parse(localStorage.getItem(claveLS) || '[]');
                                this.sospechosos = JSON.parse(localStorage.getItem(`sospechosos-${nombreUbicacion}`) ||
                                    '[]');
                                this.$nextTick(() => this.$refs.inputQR?.focus());

                                this.audioOk = document.getElementById('sonido-ok');
                                this.audioError = document.getElementById('sonido-error');
                                this.audioPedo = document.getElementById('sonido-pedo');
                                this.audioEstaEnOtraUbi = document.getElementById('sonido-estaEnOtraUbi');
                                this.audioNoTieneUbicacion = document.getElementById('sonido-noTieneUbicacion');

                                this.asignados = {
                                    ...(window.productosAsignados || {})
                                };
                                this.estados = {
                                    ...(window.productosEstados || {})
                                };
                            },

                            handleProductoReasignado(e) {
                                const {
                                    codigo,
                                    nuevaUbicacion
                                } = e.detail;

                                this.sospechosos = this.sospechosos.filter(c => c !== codigo);
                                this.escaneados = this.escaneados.filter(c => c !== codigo);
                                this.productosEsperados = this.productosEsperados.filter(c => c !== codigo);

                                this.asignados[codigo] = nuevaUbicacion;

                                if (this.nombreUbicacion.toString() === nuevaUbicacion.toString()) {
                                    if (!this.productosEsperados.includes(codigo)) this.productosEsperados.push(codigo);
                                    if (!this.escaneados.includes(codigo)) this.escaneados.push(codigo);
                                }

                                localStorage.setItem(`inv-${this.nombreUbicacion}`, JSON.stringify(this.escaneados));
                                localStorage.setItem(`sospechosos-${this.nombreUbicacion}`, JSON.stringify(this
                                    .sospechosos));
                            },

                            reproducirOk() {
                                this.audioOk && (this.audioOk.currentTime = 0, this.audioOk.play().catch(() => {}));
                            },
                            reproducirError() {
                                this.audioError && (this.audioError.currentTime = 0, this.audioError.play().catch(
                            () => {}));
                            },
                            reproducirPedo() {
                                this.audioPedo && (this.audioPedo.currentTime = 0, this.audioPedo.play().catch(
                                () => {}));
                            },
                            reproducirEstaEnOtraUbi() {
                                this.audioEstaEnOtraUbi && (this.audioEstaEnOtraUbi.currentTime = 0, this
                                    .audioEstaEnOtraUbi.play().catch(() => {}));
                            },
                            reproducirNoTieneUbicacion() {
                                this.audioNoTieneUbicacion && (this.audioNoTieneUbicacion.currentTime = 0, this
                                    .audioNoTieneUbicacion.play().catch(() => {}));
                            },

                            progreso() {
                                if (!this.productosEsperados.length) return 0;
                                return (this.escaneados.length / this.productosEsperados.length) * 100;
                            },

                            procesarQR(codigo) {
                                codigo = (codigo || '').trim();
                                if (!codigo.toUpperCase().startsWith('MP')) {
                                    this.reproducirPedo();
                                    this.ultimoCodigo = codigo;
                                    setTimeout(() => (this.ultimoCodigo = null), 1200);
                                    return;
                                }
                                if (!codigo) return;

                                const ubicacionAsignada = (window.productosAsignados || {})[codigo];
                                if (this.productosEsperados.includes(codigo)) {
                                    if (!this.escaneados.includes(codigo)) {
                                        this.escaneados.push(codigo);
                                        localStorage.setItem(claveLS, JSON.stringify(this.escaneados));
                                        this.reproducirOk();
                                    }
                                    const indexSospechoso = this.sospechosos.indexOf(codigo);
                                    if (indexSospechoso !== -1) {
                                        this.sospechosos.splice(indexSospechoso, 1);
                                        localStorage.setItem(`sospechosos-${nombreUbicacion}`, JSON.stringify(this
                                            .sospechosos));
                                    }
                                } else {
                                    if (!this.sospechosos.includes(codigo)) {
                                        this.sospechosos.push(codigo);
                                        localStorage.setItem(`sospechosos-${nombreUbicacion}`, JSON.stringify(this
                                            .sospechosos));
                                    }
                                    if (ubicacionAsignada !== undefined && ubicacionAsignada.toString() !== this
                                        .nombreUbicacion.toString()) {
                                        this.reproducirEstaEnOtraUbi();
                                    } else if (ubicacionAsignada === undefined) {
                                        this.reproducirNoTieneUbicacion();
                                    } else {
                                        this.reproducirError();
                                    }
                                }
                                this.ultimoCodigo = codigo;
                                setTimeout(() => (this.ultimoCodigo = null), 1200);
                            },

                            resetear() {
                                Swal.fire({
                                    icon: 'warning',
                                    title: '¿Limpiar esta ubicación?',
                                    text: 'Se perderán los escaneos guardados.',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, borrar',
                                    cancelButtonText: 'Cancelar',
                                    confirmButtonColor: '#dc2626'
                                }).then(result => {
                                    if (result.isConfirmed) {
                                        this.escaneados = [];
                                        this.sospechosos = [];
                                        this.ultimoCodigo = null;
                                        localStorage.removeItem(claveLS);
                                        localStorage.removeItem(`sospechosos-${this.nombreUbicacion}`);
                                    }
                                });
                            },
                            productoEscaneado(codigo) {
                                return this.escaneados.includes(codigo);
                            },
                            productosAnadidos() {
                                return this.productosEsperados.filter(c => !this.originalEsperados.includes(c));
                            },
                            reportarErrores() {
                                window.notificarProgramadorInventario({
                                    ubicacion: this.nombreUbicacion,
                                    faltantes: this.productosEsperados.filter(c => !this.escaneados.includes(
                                        c)),
                                    inesperados: [...this.sospechosos]
                                });
                            },
                            reasignarProducto(codigo) {
                                fetch(window.inventarioCtx.rutaReasignar.replace('___CODIGO___', codigo), {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': window.inventarioCtx.token
                                    },
                                    body: JSON.stringify({
                                        ubicacion_id: this.nombreUbicacion
                                    })
                                }).then(res => res.json()).then(data => {
                                    if (data.success) {
                                        window.productosAsignados[codigo] = this.nombreUbicacion;
                                        this.asignados[codigo] = this.nombreUbicacion; // update local reactive
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Reasignado',
                                            text: `El producto ${codigo} fue reasignado a esta ubicación.`,
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                        window.dispatchEvent(new CustomEvent('producto-reasignado', {
                                            detail: {
                                                codigo,
                                                nuevaUbicacion: this.nombreUbicacion
                                            }
                                        }));
                                    } else throw new Error(data.message || 'Error desconocido');
                                }).catch(err => Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: err.message
                                }));
                            }
                        };
                    };
                }

                // Global Functions (if not exists)
                window.notificarProgramadorInventario = window.notificarProgramadorInventario || function({
                    ubicacion,
                    faltantes,
                    inesperados
                }) {
                    Swal.fire({
                        icon: 'warning',
                        title: '¿Quieres reportar los errores al programador?',
                        html: `<p><strong>Ubicación:</strong> ${ubicacion}</p><p><strong>Faltantes:</strong> ${faltantes.length ? faltantes.join(', ') : '—'}</p><p><strong>Inesperados:</strong> ${inesperados.length ? inesperados.join(', ') : '—'}</p>`,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, enviar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc2626'
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch(window.inventarioCtx.rutaAlerta, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': window.inventarioCtx.token
                                },
                                body: JSON.stringify({
                                    tipo: 'inventario',
                                    mensaje: `Ubicación: ${ubicacion}\nFaltantes: ${faltantes.join(', ') || '—'}\nInesperados: ${inesperados.join(', ') || '—'}`
                                        .trim(),
                                    enviar_a_departamentos: ['Programador']
                                })
                            }).then(async res => {
                                const data = await res.json();
                                if (!res.ok || data.success === false) throw new Error(data
                                .message);
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Reporte enviado',
                                    text: 'Gracias por notificar.',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            }).catch(error => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al enviar',
                                    text: error.message
                                });
                            });
                        }
                    });
                };

                window.limpiarTodosEscaneos = window.limpiarTodosEscaneos || function() {
                    Swal.fire({
                        icon: 'warning',
                        title: '¿Eliminar todos los escaneos?',
                        text: 'Se borrarán todos los registros escaneados.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, borrar todo',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc2626'
                    }).then(result => {
                        if (result.isConfirmed) {
                            const clavesABorrar = [];
                            for (let i = 0; i < localStorage.length; i++) {
                                const key = localStorage.key(i);
                                if (key?.startsWith('inv-') || key?.startsWith('sospechosos-')) clavesABorrar
                                    .push(key);
                            }
                            clavesABorrar.forEach(key => localStorage.removeItem(key));
                            Swal.fire({
                                icon: 'success',
                                title: 'Escaneos eliminados',
                                text: `${clavesABorrar.length} registros fueron eliminados.`,
                                timer: 3000,
                                showConfirmButton: false
                            });
                            setTimeout(() => {
                                window.location.reload();
                            }, 800);
                        }
                    });
                };

                // Move button if needed
                const btn = document.getElementById("btn_limpiar_todos_escaneos");
                if (btn && btn.dataset.invPlaced !== "1") {
                    const header = document.querySelector(".max-w-7xl.mx-auto.py-4.px-4.sm\\:px-6.lg\\:px-8");
                    if (header) {
                        btn.dataset.invPlaced = "1";
                        btn.remove();
                        header.classList.add("flex", "justify-between");
                        header.append(btn);
                    }
                }

                // Init cleanup
                window.pageInitializers = window.pageInitializers || [];
                window.pageInitializers.push(() => {
                    document.body.dataset.inventarioPageInit = 'false';
                    // Note: We don't remove global functions or factories usually unless strict requirement, 
                    // but we do reset the flag.
                });
            };

            // Bootstrap
            document.removeEventListener('livewire:navigated', window.initInventarioPage);
            document.addEventListener('livewire:navigated', window.initInventarioPage);
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', window.initInventarioPage);
            } else {
                window.initInventarioPage();
            }
        </script>
    @endpush
</x-app-layout>
