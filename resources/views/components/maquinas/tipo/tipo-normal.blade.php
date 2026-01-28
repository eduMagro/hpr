<style>
    [x-cloak] {
        display: none !important
    }
</style>

<div x-data="{
    showLeft: JSON.parse(localStorage.getItem('showLeft') ?? 'true'),
    showRight: JSON.parse(localStorage.getItem('showRight') ?? 'true'),

    toggleLeft() {
        this.showLeft = !this.showLeft;
        localStorage.setItem('showLeft', JSON.stringify(this.showLeft));
        window.dispatchEvent(new CustomEvent('toggleLeft'));
    },

    solo() {
        this.showLeft = false;
        this.showRight = false;
        localStorage.setItem('showLeft', 'false');
        localStorage.setItem('showRight', 'false');
        window.dispatchEvent(new CustomEvent('solo'));
    },

    toggleRight() {
        this.showRight = !this.showRight;
        localStorage.setItem('showRight', JSON.stringify(this.showRight));
        window.dispatchEvent(new CustomEvent('toggleRight'));
    },

    init() {
        window.addEventListener('toggleLeft', () => {
            this.showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'true');
        });
        window.addEventListener('solo', () => {
            this.showLeft = false;
            this.showRight = false;
        });
        window.addEventListener('toggleRight', () => {
            this.showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
        });

        // 🔥 Aplicar clases CSS inmediatamente después de que Alpine inicialice
        this.$nextTick(() => {
            if (window.updateGridClasses) {
                window.updateGridClasses(this.showLeft, this.showRight);
                console.log('✅ Clases aplicadas desde Alpine init:', this.showLeft, this.showRight);
            }
        });
    }
}" class="w-full">

    <!-- ============================================================
         GRID PRINCIPAL (3 COLUMNAS ADAPTATIVAS)
         ============================================================ -->
    <div class="max-w-screen-2xl mx-auto px-4">
        <div id="grid-maquina" class="grid grid-cols-12 gap-2 {{ count($planillasActivas) >= 2 ? 'dos-planillas' : 'una-planilla' }}"
             style="opacity: 0; visibility: hidden; transition: opacity 0.3s ease-in, visibility 0s 0.3s;">

            <!-- ============================================================
                 COLUMNA IZQUIERDA - MATERIA PRIMA
                 ============================================================ -->
            <div x-show="showLeft" x-cloak
                class="col-span-12 lg:col-span-2 bg-white dark:bg-gray-900/95 border border-gray-200 dark:border-blue-500/40 shadow-lg rounded-xl self-start lg:sticky lg:top-2 overflow-hidden dark:backdrop-blur-sm">

                <div id="materia-prima-container" class="p-1.5 overflow-y-auto" style="max-height: calc(100vh - 60px);">
                    @if($productosBaseCompatibles->isEmpty())
                        <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                            <p class="text-xs">No hay productos base compatibles</p>
                        </div>
                    @endif
                    @foreach ($productosBaseCompatibles as $productoBase)
                        @php
                            // Obtener TODOS los productos de este base que no estén consumidos
                            $productosDeEsteBase = $maquina->productos
                                ->where('producto_base_id', $productoBase->id)
                                ->reject(fn($p) => $p->estado === 'consumido')
                                ->sortByDesc('estado'); // fabricando primero

                            $tieneProductos = $productosDeEsteBase->isNotEmpty();
                        @endphp

                        <div class="mb-2 p-1.5 {{ $tieneProductos ? 'bg-gray-100 dark:bg-gray-800/50' : 'bg-red-100 dark:bg-red-900/30' }} rounded-lg border {{ $tieneProductos ? 'border-gray-300 dark:border-gray-700/50' : 'border-red-300 dark:border-red-500/40' }}">
                            {{-- Cabecera del producto base --}}
                            <div class="flex items-center justify-between mb-1">
                                <span class="{{ $tieneProductos ? 'bg-green-600' : 'bg-red-500' }} text-white px-1.5 py-0.5 rounded text-xs font-bold shadow-sm">
                                    Ø{{ $productoBase->diametro }}
                                    @if (strtoupper($productoBase->tipo) === 'BARRA')
                                        <span class="{{ $tieneProductos ? 'text-green-100' : 'text-red-100' }} font-normal">L:{{ $productoBase->longitud }}m</span>
                                    @endif
                                </span>
                                @if (optional($productoBaseSolicitados)->contains($productoBase->id))
                                    <button disabled class="bg-gray-300 text-gray-500 text-xs px-2 py-1 rounded cursor-not-allowed" title="Solicitud pendiente">
                                        🕓
                                    </button>
                                @else
                                    <button
                                        onclick="solicitarRecarga(this, {{ $maquina->id }}, {{ $productoBase->id }}, '{{ $productoBase->diametro }}')"
                                        class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-2 py-1 rounded font-medium"
                                        title="Solicitar recarga">
                                        +
                                    </button>
                                @endif
                            </div>

                            @if ($tieneProductos)
                            {{-- Lista de productos activos --}}
                            <div class="space-y-1">
                                @foreach ($productosDeEsteBase as $producto)
                                    @php
                                        $pesoStock = $producto->peso_stock ?? 0;
                                        $pesoInicial = $producto->peso_inicial ?? 0;
                                        $porcentaje = $pesoInicial > 0 ? ($pesoStock / $pesoInicial) * 100 : 0;
                                        $esFabricando = $producto->estado === 'fabricando';
                                    @endphp
                                    <div class="p-1 rounded {{ $esFabricando ? 'bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-500/40' : 'bg-gray-100 dark:bg-gray-800/60 border border-gray-300 dark:border-gray-600/40' }}">
                                        {{-- Barra de progreso + botón consumir --}}
                                        <div class="flex items-center gap-1">
                                            <div class="relative flex-1 h-4 bg-gray-300 dark:bg-gray-700 rounded-full overflow-hidden">
                                                <div class="absolute left-0 h-full transition-all duration-300"
                                                    style="width: {{ $porcentaje }}%; background: {{ $esFabricando ? 'linear-gradient(90deg, #10b981, #059669)' : 'linear-gradient(90deg, #6b7280, #4b5563)' }};">
                                                </div>
                                                <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold {{ $porcentaje > 50 ? 'text-white' : 'text-gray-600 dark:text-gray-300' }}">
                                                    {{ number_format($pesoStock, 0, ',', '.') }}/{{ number_format($pesoInicial, 0, ',', '.') }} kg
                                                </span>
                                            </div>
                                            <button type="button"
                                                onclick="consumirProducto({{ $producto->id }})"
                                                class="bg-red-500 hover:bg-red-600 text-white text-xs px-2 py-1 rounded font-medium flex-shrink-0"
                                                title="Consumir">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M13.5 3.5c-2 2-1.5 4-3 5.5s-4 1-4 5a6 6 0 0012 0c0-2-1-3.5-2-4.5s-1-3-3-6z" />
                                                </svg>
                                            </button>
                                        </div>
                                        {{-- Info compacta --}}
                                        <div class="flex items-center justify-between text-[9px] text-gray-500 dark:text-gray-400 mt-0.5 px-0.5">
                                            <span title="Código" class="truncate">{{ $producto->codigo ?? '—' }}</span>
                                            <span title="Colada">C: {{ $producto->n_colada ?? '—' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @else
                            {{-- Sin stock - mostrar mensaje --}}
                            <div class="text-center py-2">
                                <span class="text-xs text-red-400 font-medium">⚠️ Sin stock</span>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Botones de acciones --}}
                <div class="p-3 border-t border-gray-200 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-800/50 space-y-2">
                    @if ($elementosAgrupados->isNotEmpty())
                        <div id="datos-lote" data-lote='@json($elementosAgrupados->keys()->values())' class="hidden"></div>

                        <div x-data="{ cargando: false }">
                            <button type="button"
                                @click="
                                cargando = true;
                                let datos = document.getElementById('datos-lote').dataset.lote;
                                let lote = JSON.parse(datos);
                                Promise.resolve(imprimirEtiquetas(lote)).finally(() => cargando = false);
                                "
                                :disabled="cargando"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 font-semibold text-white shadow-md transition
                                bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="cargando" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3.536-3.536A9 9 0 103 12h4z"/>
                                </svg>
                                <span x-show="!cargando">🖨️ Imprimir Lote</span>
                                <span x-show="cargando">Imprimiendo...</span>
                            </button>
                        </div>
                    @endif

                    <button onclick="document.getElementById('modalIncidencia').classList.remove('hidden')"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow-md transition">
                        🚨 Reportar Incidencia
                    </button>

            <!-- Botón Realizar Chequeo de Máquina -->
            <button
                onclick="document.getElementById('modalCheckeo').classList.remove('hidden')"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-md w-full sm:w-auto transition">
                🛠️ Chequeo
            </button>
        </div>
    </div>

            <!-- ============================================================
                 COLUMNA CENTRAL - PLANILLAS DE TRABAJO
                 ============================================================ -->
            <div class="bg-white dark:bg-gray-900/95 border border-gray-200 dark:border-blue-500/40 shadow-lg rounded-xl overflow-hidden dark:backdrop-blur-sm"
                :class="{
                    'col-span-12 lg:col-span-8': showLeft && showRight,
                    'col-span-12 lg:col-span-10': (showLeft && !showRight) || (!showLeft && showRight),
                    'col-span-12': !showLeft && !showRight
                }">

                {{-- Panel flotante de sugerencia de corte --}}
                <div id="element-info-panel"
                    class="fixed top-20 left-1/2 z-50 w-full max-w-md hidden -translate-x-1/2
                    bg-white dark:bg-gray-900/95 border border-gray-200 dark:border-blue-500/50 shadow-2xl rounded-xl p-4 dark:backdrop-blur-sm">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h4 class="font-bold text-sm text-blue-600 dark:text-blue-400 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Sugerencia de corte
                        </h4>
                        <button type="button" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-bold"
                            onclick="document.getElementById('element-info-panel').classList.add('hidden')">
                            ✕
                        </button>
                    </div>
                    <div id="element-info-body" class="text-xs text-gray-600 dark:text-gray-300 space-y-1"></div>
                </div>

                <div class="flex items-center justify-center" style="min-height: calc(100vh - 70px);">
                    <div class="grid grid-cols-1 gap-2 {{ count($planillasActivas) >= 2 ? 'md:grid-cols-2' : '' }} w-full">
                        @forelse($planillasActivas as $planilla)
                            @php
                                $grupoPlanilla = $elementosPorPlanilla->get($planilla->id, collect());
                                $elementosAgrupadosLocal = $grupoPlanilla->groupBy('etiqueta_sub_id')->sortBy(function ($grupo, $subId) {
                                    if (preg_match('/^(.*?)[\.\-](\d+)$/', $subId, $m)) {
                                        return sprintf('%s-%010d', $m[1], (int) $m[2]);
                                    }
                                    return $subId . '-0000000000';
                                });

                                // Filtrar etiquetas que están en grupos resumidos
                                $etiquetasEnGruposArray = $etiquetasEnGrupos ?? [];
                                $elementosAgrupados = $elementosAgrupadosLocal->filter(
                                    fn($grupo, $subId) => !in_array($subId, $etiquetasEnGruposArray)
                                );
                            @endphp

                            <section class="bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700/50 shadow-md overflow-hidden">
                                <div class="space-y-2 overflow-y-auto flex flex-col items-center justify-start pt-4" style="max-height: calc(100vh - 70px);">

                                    {{-- GRUPOS DE RESUMEN de esta planilla --}}
                                    @php
                                        $gruposDePlanilla = collect($gruposResumen ?? [])->filter(function($grupo) use ($planilla, $planillasActivas) {
                                            // Grupos con planilla_id específico -> mostrar solo en esa planilla
                                            if (!is_null($grupo['planilla_id'])) {
                                                return $grupo['planilla_id'] == $planilla->id;
                                            }

                                            // Grupos multi-planilla -> mostrar solo en la primera planilla que tenga etiquetas
                                            $etiquetasPlanillas = collect($grupo['etiquetas'] ?? [])
                                                ->pluck('planilla_codigo')
                                                ->filter()
                                                ->unique();

                                            // Encontrar la primera planilla activa que tenga etiquetas de este grupo
                                            foreach ($planillasActivas as $pa) {
                                                $codigoPa = $pa->codigo_limpio ?? $pa->codigo;
                                                if ($etiquetasPlanillas->contains($codigoPa)) {
                                                    // Solo mostrar si esta es la primera planilla que coincide
                                                    $codigoPlanillaActual = $planilla->codigo_limpio ?? $planilla->codigo;
                                                    return $codigoPa === $codigoPlanillaActual;
                                                }
                                            }

                                            return false;
                                        });
                                    @endphp

                                    @foreach ($gruposDePlanilla as $grupo)
                                        <x-etiqueta.grupo-resumen :grupo="$grupo" :maquina="$maquina" />
                                    @endforeach

                                    {{-- Etiquetas originales de grupos (OCULTAS para impresión) --}}
                                    <div style="position:absolute;left:-9999px;top:0;opacity:0;pointer-events:none;" aria-hidden="true">
                                        @foreach ($gruposDePlanilla as $grupo)
                                            @foreach ($grupo['etiquetas'] ?? [] as $etData)
                                                @php
                                                    // Intentar usar pre-carga, fallback a consulta
                                                    $etOculta = isset($etiquetasPreCargadas[$etData['id']])
                                                        ? $etiquetasPreCargadas[$etData['id']]
                                                        : \App\Models\Etiqueta::with(['planilla'])->find($etData['id']);
                                                @endphp
                                                @if ($etOculta)
                                                    <x-etiqueta.etiqueta :etiqueta="$etOculta" :planilla="$etOculta->planilla" :maquina-tipo="$maquina->tipo" />
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </div>

                                    @forelse ($elementosAgrupados as $etiquetaSubId => $elementos)
                                        @php
                                            $firstElement = $elementos->first();
                                            $etiqueta = $firstElement->etiquetaRelacion ?? \App\Models\Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)->first();

                                            // Asegurar que el etiqueta_sub_id esté en el objeto etiqueta
                                            if ($etiqueta && !$etiqueta->etiqueta_sub_id) {
                                                $etiqueta->etiqueta_sub_id = $etiquetaSubId;
                                            }

                                            $tieneElementosEnOtrasMaquinas = isset($otrosElementos[$etiqueta?->id]) && $otrosElementos[$etiqueta?->id]->isNotEmpty();
                                        @endphp

                                        @if ($etiqueta)
                                            <div class="hover:border-blue-500/60 hover:shadow-lg hover:shadow-blue-500/10 transition-all duration-200 rounded-lg">
                                                <x-etiqueta.etiqueta :etiqueta="$etiqueta" :planilla="$planilla" :maquina-tipo="$maquina->tipo" />

                                                @if ($tieneElementosEnOtrasMaquinas)
                                                    <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700/50 text-center">
                                                        <span class="text-orange-500 dark:text-orange-400/80 font-semibold text-xs">⚠️ En otras máq.</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="p-4 bg-gray-100 dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600/50 rounded text-center text-gray-500 dark:text-gray-400 text-sm">
                                                ⚠️ Etiqueta no encontrada: {{ $etiquetaSubId }}
                                            </div>
                                        @endif
                                    @empty
                                        <div class="text-center p-8 text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800/30 rounded-lg border border-gray-200 dark:border-gray-700/30">
                                            Sin subetiquetas pendientes
                                        </div>
                                    @endforelse
                                </div>
                            </section>
                        @empty
                            <div class="col-span-2 text-center p-6 bg-gray-100 dark:bg-gray-800/50 border border-gray-200 dark:border-blue-500/30 rounded-xl shadow-md">
                                <div class="text-6xl mb-3">📋</div>
                                <p class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-1">No hay planillas en cola</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Selecciona una posición en los controles superiores</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 COLUMNA DERECHA - GESTIÓN DE PAQUETES
                 ============================================================ -->
            <div x-show="showRight" x-cloak
                class="col-span-12 lg:col-span-2 bg-white dark:bg-gray-900/95 border border-gray-200 dark:border-blue-500/40 shadow-lg rounded-xl self-start lg:sticky lg:top-2 overflow-hidden dark:backdrop-blur-sm">

                <div x-data="{ tabActivo: 'crear' }" class="w-full">
                    {{-- Tabs Header --}}
                    <div class="flex bg-gray-100 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700/50">
                        <button @click="tabActivo = 'crear'"
                            :class="tabActivo === 'crear' ? 'bg-white dark:bg-gray-900/80 border-b-2 border-blue-500 text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                            class="flex-1 py-3 px-4 text-sm transition">
                            📦 Crear Paquete
                        </button>
                        <button @click="tabActivo = 'gestion'"
                            :class="tabActivo === 'gestion' ? 'bg-white dark:bg-gray-900/80 border-b-2 border-blue-500 text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                            class="flex-1 py-3 px-4 text-sm transition">
                            🗂️ Gestión
                        </button>
                    </div>

                    <div id="maquina-info" data-maquina-id="{{ $maquina->id }}" class="hidden"></div>

                    {{-- Tab: Crear Paquete --}}
                    <div x-show="tabActivo === 'crear'" class="p-4">
                        <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-blue-500/30 rounded-lg p-4 shadow-md">
                            <h3 class="font-bold text-lg mb-3 text-blue-600 dark:text-blue-400">Crear Paquete</h3>

                            <div class="mb-3">
                                <input type="text" id="qrItem"
                                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 py-3 placeholder-gray-400 dark:placeholder-gray-500"
                                    placeholder="🔍 Escanear etiqueta" autocomplete="off">
                            </div>

                            <div class="mb-4 bg-gray-100 dark:bg-gray-900/50 rounded-lg p-3 border border-gray-200 dark:border-gray-700/50">
                                <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2 text-sm">Etiquetas en el carro:</h4>
                                <ul id="itemsList" class="list-disc pl-5 space-y-1.5 text-sm text-gray-500 dark:text-gray-400">
                                    <!-- Dinámico -->
                                </ul>
                            </div>

                            <button id="crearPaqueteBtn"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition transform hover:scale-105">
                                📦 Crear Paquete
                            </button>
                        </div>
                    </div>

                    {{-- Tab: Gestión de Paquetes --}}
                    <div x-show="tabActivo === 'gestion'" class="p-4">
                        @include('components.maquinas.partes.gestionPaquetes')
                    </div>
                </div>
            </div>

            {{-- Variables globales para JavaScript (dentro del grid para AJAX) --}}
            <script>
                window.elementosAgrupadosScript = @json($elementosAgrupadosScript ?? null);
            </script>

        </div>
    </div>
</div>

<!-- ============================================================
     MODALES
     ============================================================ -->
<x-maquinas.modales.cambio-maquina :maquina="$maquina" :maquinas="$maquinas" />
<x-maquinas.modales.dividir-elemento />

<div id="modalPatron" class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-900/95 border border-gray-200 dark:border-blue-500/40 rounded-xl shadow-xl p-6 w-auto max-w-full max-h-[85vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">Elementos del patrón</h2>
            <button onclick="cerrarModalPatron()" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 text-2xl">✖</button>
        </div>
        <div id="contenedorPatron" class="flex flex-col gap-4"></div>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
    function cerrarModalPatron() {
        document.getElementById('modalPatron')?.classList.add('hidden');
    }

    document.addEventListener('keydown', (e) => e.key === 'Escape' && cerrarModalPatron());
    document.getElementById('modalPatron')?.addEventListener('click', (e) => {
        if (!e.target.closest('.bg-white')) cerrarModalPatron();
    });
</script>

@once
    <script>
        window.MAQUINA_TIPO = @json(strtolower($maquina->tipo_material));
        window.MAQUINA_NOMBRE = "{{ $maquina->nombre }}";
        window.DIAMETRO_POR_ETIQUETA = @json(
            $diametroPorEtiqueta ?: $elementosAgrupados->map(function ($els) {
                $c = collect($els)->pluck('diametro')->filter()->map(fn($d) => (int) $d);
                return (int) $c->countBy()->sortDesc()->keys()->first();
            }));
        window.LONGITUD_POR_ETIQUETA = @json(
            $elementosAgrupados->map(function ($els) {
                // Obtener la longitud máxima de los elementos (en cm)
                return (float) collect($els)->pluck('longitud')->filter()->max();
            }));

        @if ($esBarra)
            window.LONGITUDES_POR_DIAMETRO = @json($longitudesPorDiametro);
        @endif
    </script>
@endonce

<script>
    // Validación de posiciones de planillas
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-posiciones-planillas');
        if (!form) return;

        const select1 = form.querySelector('select[name="posicion_1"]');
        const select2 = form.querySelector('select[name="posicion_2"]');
        if (!select1 || !select2) return;

        function validar() {
            const pos1 = select1.value;
            const pos2 = select2.value;

            if (pos1 && pos2 && pos1 === pos2) {
                select2.value = '';
                Swal.fire({
                    icon: 'warning',
                    title: 'Posiciones duplicadas',
                    text: 'No puedes seleccionar la misma posición dos veces',
                    confirmButtonColor: '#3085d6',
                });
                return false;
            }
            return true;
        }

        select1.addEventListener('change', validar);
        select2.addEventListener('change', validar);
        form.addEventListener('submit', (e) => !validar() && e.preventDefault());
    });

    // Función para consumir un producto (marcar como consumido)
    function consumirProducto(productoId) {
        Swal.fire({
            title: '¿Consumir producto?',
            text: 'El producto se marcará como consumido y se eliminará de la máquina',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, consumir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Consumiendo producto...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Hacer petición AJAX
                fetch(`/productos/${productoId}/consumir?modo=total`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Producto consumido',
                            text: data.message,
                            confirmButtonColor: '#10b981',
                        });

                        // Refrescar la sección de materia prima
                        refrescarMateriaPrima();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo consumir el producto',
                            confirmButtonColor: '#dc2626',
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor',
                        confirmButtonColor: '#dc2626',
                    });
                });
            }
        });
    }

    // Función para solicitar recarga de materia prima (AJAX)
    function solicitarRecarga(btn, maquinaId, productoBaseId, diametro) {
        // Deshabilitar botón inmediatamente
        btn.disabled = true;
        btn.innerHTML = '⏳';
        btn.className = 'bg-gray-400 text-white text-xs px-2 py-1 rounded cursor-wait';

        fetch('{{ route("movimientos.crear") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                tipo: 'recarga_materia_prima',
                maquina_id: maquinaId,
                producto_base_id: productoBaseId,
                descripcion: `Recarga Ø${diametro}`
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cambiar a estado "pendiente"
                btn.innerHTML = '🕓';
                btn.className = 'bg-gray-300 text-gray-500 text-xs px-2 py-1 rounded cursor-not-allowed';
                btn.title = 'Solicitud pendiente';

                // Toast de éxito
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Recarga Ø${diametro} solicitada`,
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                });
            } else {
                // Restaurar botón si falla
                btn.disabled = false;
                btn.innerHTML = '+';
                btn.className = 'bg-blue-500 hover:bg-blue-600 text-white text-xs px-2 py-1 rounded font-medium';

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: data.message || 'Error al solicitar recarga',
                    showConfirmButton: false,
                    timer: 3000,
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Restaurar botón si falla
            btn.disabled = false;
            btn.innerHTML = '+';
            btn.className = 'bg-blue-500 hover:bg-blue-600 text-white text-xs px-2 py-1 rounded font-medium';

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Error de conexión',
                showConfirmButton: false,
                timer: 3000,
            });
        });
    }

    // Función para refrescar la sección de materia prima sin recargar la página
    function refrescarMateriaPrima() {
        const container = document.getElementById('materia-prima-container');
        if (!container) return;

        // Obtener la URL actual
        const url = window.location.href;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const nuevoContainer = doc.getElementById('materia-prima-container');

            if (nuevoContainer) {
                container.innerHTML = nuevoContainer.innerHTML;
            }
        })
        .catch(error => {
            console.error('Error al refrescar materia prima:', error);
        });
    }

</script>

{{-- Panel de información del elemento --}}
<x-maquinas.paneles.info />
