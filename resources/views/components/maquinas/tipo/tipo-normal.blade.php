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
            this.showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'false');
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
                class="col-span-12 lg:col-span-2 bg-white border border-gray-200 shadow-lg rounded-lg self-start lg:sticky lg:top-2 overflow-hidden">

                <div id="materia-prima-container" class="p-2 overflow-y-auto" style="max-height: calc(100vh - 60px);">
                    @foreach ($productosBaseCompatibles as $productoBase)
                        @php
                            $productoExistente = $maquina->productos->firstWhere('producto_base_id', $productoBase->id);
                            if ($productoExistente && $productoExistente->estado === 'consumido') continue;

                            $pesoStock = $productoExistente->peso_stock ?? 0;
                            $pesoInicial = $productoExistente->peso_inicial ?? 0;
                            $porcentaje = $pesoInicial > 0 ? ($pesoStock / $pesoInicial) * 100 : 0;

                            $codigoPB = $fabricantePB = $coladaPB = $paquetePB = null;
                            if ($productoExistente) {
                                $codigoPB = $productoExistente->codigo ?? ($productoExistente->codigo_producto ?? null);
                                $fabricantePB = $productoExistente->fabricante->nombre ?? ($productoExistente->fabricante ?? null);
                                $coladaPB = $productoExistente->n_colada ?? ($productoExistente->colada ?? null);
                                $paquetePB = $productoExistente->n_paquete ?? ($productoExistente->paquete ?? null);
                            }
                        @endphp

                        <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-green-400 transition">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm font-semibold text-gray-800">
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded">Ø {{ $productoBase->diametro }} mm</span>
                                    @if (strtoupper($productoBase->tipo) === 'BARRA')
                                        <span class="ml-2 text-gray-600">L: {{ $productoBase->longitud }} m</span>
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('movimientos.crear') }}">
                                    @csrf
                                    <input type="hidden" name="tipo" value="recarga_materia_prima">
                                    <input type="hidden" name="maquina_id" value="{{ $maquina->id }}">
                                    <input type="hidden" name="producto_base_id" value="{{ $productoBase->id }}">
                                    @if ($productoExistente)
                                        <input type="hidden" name="producto_id" value="{{ $productoExistente->id }}">
                                    @endif
                                    <input type="hidden" name="descripcion"
                                        value="Recarga solicitada para máquina {{ $maquina->nombre }} (Ø{{ $productoBase->diametro }} {{ strtolower($productoBase->tipo) }}, {{ $pesoStock }} kg)">

                                    @if (optional($productoBaseSolicitados)->contains($productoBase->id))
                                        <button disabled
                                            class="bg-gray-300 text-gray-600 text-xs font-medium px-2 py-1 rounded cursor-not-allowed">
                                            🕓 Solicitado
                                        </button>
                                    @else
                                        <button
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium px-2 py-1 rounded transition shadow-sm">
                                            Solicitar
                                        </button>
                                    @endif
                                </form>
                            </div>

                            @if ($productoExistente)
                                {{-- Barra de progreso --}}
                                <div id="progreso-container-{{ $productoExistente->id }}"
                                    class="relative mt-2 {{ strtoupper($productoBase->tipo) === 'ENCARRETADO' ? 'w-20 h-20 mx-auto' : 'w-full h-5' }} bg-gray-200 overflow-hidden rounded-full">
                                    <div id="progreso-barra-{{ $productoExistente->id }}"
                                        class="absolute {{ strtoupper($productoBase->tipo) === 'ENCARRETADO' ? 'bottom-0 w-full' : 'left-0 h-full' }} transition-all duration-300"
                                        style="{{ strtoupper($productoBase->tipo) === 'ENCARRETADO' ? 'height' : 'width' }}: {{ $porcentaje }}%; background: linear-gradient(90deg, #10b981, #059669);">
                                    </div>
                                    <span id="progreso-texto-{{ $productoExistente->id }}"
                                        class="absolute inset-0 flex items-center justify-center text-white text-xs font-bold drop-shadow">
                                        {{ number_format($pesoStock, 0, ',', '.') }} / {{ number_format($pesoInicial, 0, ',', '.') }} kg
                                    </span>
                                </div>

                                {{-- Información técnica --}}
                                <div class="mt-2 grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-gray-600">
                                    <div><span class="font-medium">Código:</span> {{ $codigoPB ?? '—' }}</div>
                                    <div><span class="font-medium">Fabricante:</span> {{ $fabricantePB ?? '—' }}</div>
                                    <div><span class="font-medium">Colada:</span> {{ $coladaPB ?? '—' }}</div>
                                    <div><span class="font-medium">Paquete:</span> {{ $paquetePB ?? '—' }}</div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Botones de acciones --}}
                <div class="p-3 border-t bg-gray-50 space-y-2">
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
            <div class="bg-white border border-gray-200 shadow-lg rounded-lg overflow-hidden"
                :class="{
                    'col-span-12 lg:col-span-8': showLeft && showRight,
                    'col-span-12 lg:col-span-10': (showLeft && !showRight) || (!showLeft && showRight),
                    'col-span-12': !showLeft && !showRight
                }">

                {{-- Panel flotante de sugerencia de corte --}}
                <div id="element-info-panel"
                    class="fixed top-20 left-1/2 z-50 w-full max-w-md hidden -translate-x-1/2
                    bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-400 shadow-2xl rounded-xl p-4">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h4 class="font-bold text-sm text-blue-900 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Sugerencia de corte
                        </h4>
                        <button type="button" class="text-blue-700 hover:text-blue-900 font-bold"
                            onclick="document.getElementById('element-info-panel').classList.add('hidden')">
                            ✕
                        </button>
                    </div>
                    <div id="element-info-body" class="text-xs text-blue-900 space-y-1"></div>
                </div>

                <div class="flex items-center justify-center" style="min-height: calc(100vh - 70px);">
                    <div class="grid grid-cols-1 gap-2 {{ count($planillasActivas) >= 2 ? 'md:grid-cols-2' : '' }} w-full">
                        @forelse($planillasActivas as $planilla)
                            @php
                                $grupoPlanilla = $elementosPorPlanilla->get($planilla->id, collect());
                                $elementosAgrupados = $grupoPlanilla->groupBy('etiqueta_sub_id')->sortBy(function ($grupo, $subId) {
                                    if (preg_match('/^(.*?)[\.\-](\d+)$/', $subId, $m)) {
                                        return sprintf('%s-%010d', $m[1], (int) $m[2]);
                                    }
                                    return $subId . '-0000000000';
                                });
                            @endphp

                            <section class="bg-gradient-to-br from-gray-50 to-white rounded-lg border-2 border-gray-200 shadow-md overflow-hidden">
                                <div class="space-y-2 overflow-y-auto flex flex-col items-center justify-start pt-4" style="max-height: calc(100vh - 70px);">
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

                                        <div class="hover:border-blue-400 hover:shadow-md transition-all duration-200">
                                            <x-etiqueta.etiqueta :etiqueta="$etiqueta" :planilla="$planilla" :maquina-tipo="$maquina->tipo" />

                                            @if ($tieneElementosEnOtrasMaquinas)
                                                <div class="mt-2 pt-2 border-t border-gray-200 text-center">
                                                    <span class="text-amber-600 font-semibold text-xs">⚠️ En otras máq.</span>
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-center p-8 text-sm text-gray-500 bg-gray-50 rounded-lg">
                                            Sin subetiquetas pendientes
                                        </div>
                                    @endforelse
                                </div>
                            </section>
                        @empty
                            <div class="col-span-2 text-center p-6 bg-gradient-to-r from-yellow-50 to-yellow-100 border-2 border-yellow-300 rounded-xl shadow-md">
                                <div class="text-6xl mb-3">📋</div>
                                <p class="text-lg font-bold text-gray-800 mb-1">No hay planillas en cola</p>
                                <p class="text-sm text-gray-600">Selecciona una posición en los controles superiores</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 COLUMNA DERECHA - GESTIÓN DE PAQUETES
                 ============================================================ -->
            <div x-show="showRight" x-cloak
                class="col-span-12 lg:col-span-2 bg-white border border-gray-200 shadow-lg rounded-lg self-start lg:sticky lg:top-2 overflow-hidden">

                <div x-data="{ tabActivo: 'crear' }" class="w-full">
                    {{-- Tabs Header --}}
                    <div class="flex bg-gray-100 border-b">
                        <button @click="tabActivo = 'crear'"
                            :class="tabActivo === 'crear' ? 'bg-white border-b-2 border-blue-600 text-blue-700 font-bold' : 'text-gray-600 hover:text-gray-900'"
                            class="flex-1 py-3 px-4 text-sm transition">
                            📦 Crear Paquete
                        </button>
                        <button @click="tabActivo = 'gestion'"
                            :class="tabActivo === 'gestion' ? 'bg-white border-b-2 border-blue-600 text-blue-700 font-bold' : 'text-gray-600 hover:text-gray-900'"
                            class="flex-1 py-3 px-4 text-sm transition">
                            🗂️ Gestión
                        </button>
                    </div>

                    <div id="maquina-info" data-maquina-id="{{ $maquina->id }}" class="hidden"></div>

                    {{-- Tab: Crear Paquete --}}
                    <div x-show="tabActivo === 'crear'" class="p-4">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-300 rounded-lg p-4 shadow-md">
                            <h3 class="font-bold text-lg mb-3 text-blue-900">Crear Paquete</h3>

                            <div class="mb-3">
                                <input type="text" id="qrItem"
                                    class="w-full border-2 border-blue-300 rounded-lg text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 py-3"
                                    placeholder="🔍 Escanear etiqueta" autocomplete="off">
                            </div>

                            <div class="mb-4 bg-white rounded-lg p-3 border border-blue-200">
                                <h4 class="font-semibold text-gray-700 mb-2 text-sm">Etiquetas en el carro:</h4>
                                <ul id="itemsList" class="list-disc pl-5 space-y-1.5 text-sm text-gray-700">
                                    <!-- Dinámico -->
                                </ul>
                            </div>

                            <button id="crearPaqueteBtn"
                                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition transform hover:scale-105">
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

<div id="modalPatron" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-auto max-w-full max-h-[85vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Elementos del patrón</h2>
            <button onclick="onclick="cerrarModalPatron()" wire:navigate" class="text-gray-500 hover:text-gray-800 text-2xl">✖</button>
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

    // Auto-refresh para el contenedor de materia prima cada 10 segundos
    let materiaPrimaRefreshInterval = null;

    function refreshMateriaPrima() {
        const container = document.getElementById('materia-prima-container');
        if (!container) {
            console.warn('Contenedor de materia prima no encontrado');
            return;
        }

        // Obtener parámetros de URL actuales
        const params = new URLSearchParams(window.location.search);
        const url = window.location.pathname + '?' + params.toString();

        // Hacer fetch para obtener el HTML actualizado
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Parsear el HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Dibujamos la etiqueta en la lista visual
            const li = document.createElement('li');
            li.textContent = `Etiqueta ${etiquetaId}`;
            li.dataset.etiquetaId = etiquetaId;
            itemsList.appendChild(li);

            inputQr.value = '';
        });

        /**
         * Enviar petición para crear el paquete desde la máquina actual.
         * Envía:
         *  - maquina_id
         *  - etiquetas_ids[]
         */
        btnCrear.addEventListener('click', async () => {
            if (!maquinaId) {
                alert(
                    'No se ha podido determinar la máquina actual.');
                return;
            }

            if (etiquetasSeleccionadas.size === 0) {
                alert(
                    'Añade al menos una etiqueta al carro antes de crear el paquete.');
                return;
            }

            const etiquetasIds = Array.from(
                etiquetasSeleccionadas);

            try {
                const resp = await fetch(
                    "{{ route('paquetes.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document
                                .querySelector(
                                    'meta[name=\"csrf-token\"]'
                                    ).getAttribute(
                                    'content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            maquina_id: maquinaId,
                            etiquetas_ids: etiquetasIds,
                        }),
                    });

                if (!resp.ok) {
                    throw new Error(
                        'Error al crear el paquete');
                }

                const data = await resp.json();

                // Aquí el paquete ya tiene localización en el mapa
                // (se ha creado el registro en localizaciones_paquetes)
                // Podrías mostrar un Swal bonito:
                // Swal.fire({ icon: 'success', title: 'Paquete creado', text: 'ID: ' + data.paquete.id });

                // Limpiamos el carro
                etiquetasSeleccionadas.clear();
                itemsList.innerHTML = '';

            } catch (e) {
                console.error(e);
                alert(
                    'Ha ocurrido un error al crear el paquete.');
            }
        });
    });
</script>

{{-- Panel de información del elemento --}}
<x-maquinas.paneles.info />
