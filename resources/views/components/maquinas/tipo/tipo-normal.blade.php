<style>
    [x-cloak] {
        display: none !important
    }
</style>

<div x-data="{
    showLeft: JSON.parse(localStorage.getItem('showLeft') ?? 'false'),
    showRight: JSON.parse(localStorage.getItem('showRight') ?? 'false'),

    get soloCentral() { return !this.showLeft && !this.showRight },
    toggleLeft() {
        this.showLeft = !this.showLeft;
        localStorage.setItem('showLeft', JSON.stringify(this.showLeft));
    },
    solo() {
        this.showLeft = false;
        this.showRight = false;
        localStorage.setItem('showLeft', 'false');
        localStorage.setItem('showRight', 'false');
    },
    toggleRight() {
        this.showRight = !this.showRight;
        localStorage.setItem('showRight', JSON.stringify(this.showRight));
    },

    restablecer() {
        this.showLeft = false;
        this.showRight = false;
        localStorage.setItem('showLeft', 'false');
        localStorage.setItem('showRight', 'false');
    }
}"
    class="w-full mx-auto px-4 grid grid-cols-1 sm:grid-cols-12 gap-4">

    <!-- ============================================================
         BARRA DE CONTROLES SUPERIORES
         ============================================================ -->
    <div
        class="col-span-full bg-gray-50 p-3 rounded-lg border flex flex-wrap items-center gap-3">

        {{-- Botones de control de columnas --}}
        <div class="flex flex-wrap gap-2">
            <button @click="toggleLeft()"
                class="px-3 py-1 rounded text-sm font-semibold border hover:bg-gray-100 transition"
                :class="showLeft ? 'border-gray-300' : 'border-yellow-500 bg-yellow-50'"
                title="Mostrar/Ocultar columna izquierda">
                <span x-text="showLeft ? 'Ocultar' : 'Mostrar'"></span> izquierda
            </button>

            <button @click="solo()"
                class="px-3 py-1 rounded text-sm font-semibold border border-blue-500 text-blue-700 hover:bg-blue-50 transition"
                title="Ver solo columna central">
                Solo central
            </button>

            <button @click="toggleRight()"
                class="px-3 py-1 rounded text-sm font-semibold border hover:bg-gray-100 transition"
                :class="showRight ? 'border-gray-300' : 'border-yellow-500 bg-yellow-50'"
                title="Mostrar/Ocultar columna derecha">
                <span x-text="showRight ? 'Ocultar' : 'Mostrar'"></span> derecha
            </button>

            <button @click="restablecer()"
                class="px-3 py-1 rounded text-sm font-semibold border border-gray-300 hover:bg-gray-100 transition"
                title="Restablecer columnas">
                Restablecer
            </button>
        </div>

        {{-- 🔥 NUEVO: Selectores de posiciones de planillas --}}
        <div class="ml-auto flex items-center gap-2 border-l pl-4">
            <form method="GET" id="form-posiciones-planillas"
                class="flex flex-wrap items-center gap-2">
                {{-- Mantener otros parámetros de la URL --}}
                @foreach (request()->except(['posicion_1', 'posicion_2']) as $k => $v)
                    <input type="hidden" name="{{ $k }}"
                        value="{{ $v }}">
                @endforeach

                <label
                    class="text-sm font-medium text-gray-700 whitespace-nowrap">
                    Ver planillas:
                </label>

                {{-- Select para primera posición --}}
                <select name="posicion_1"
                    class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                    onchange="this.form.submit()"
                    aria-label="Seleccionar primera posición de planilla">
                    <option value="">-- Ninguna --</option>
                    @foreach ($posicionesDisponibles as $pos)
                        <option value="{{ $pos }}"
                            {{ $posicion1 == $pos ? 'selected' : '' }}>
                            Pos. {{ $pos }}
                        </option>
                    @endforeach
                </select>

                <span class="text-sm text-gray-500 font-bold">+</span>

                {{-- Select para segunda posición --}}
                <select name="posicion_2"
                    class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                    onchange="this.form.submit()"
                    aria-label="Seleccionar segunda posición de planilla">
                    <option value="">-- Ninguna --</option>
                    @foreach ($posicionesDisponibles as $pos)
                        <option value="{{ $pos }}"
                            {{ $posicion2 == $pos ? 'selected' : '' }}>
                            Pos. {{ $pos }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- ============================================================
         COLUMNA IZQUIERDA - MATERIA PRIMA Y CONTROLES
         ============================================================ -->
    <div x-show="showLeft" x-cloak
        class="w-full bg-white border shadow-md rounded-lg self-start sm:col-span-3 md:sticky md:top-4">

        <!-- MATERIA PRIMA EN LA MÁQUINA -->
        <ul class="list-none p-1 break-words">
            @foreach ($productosBaseCompatibles as $productoBase)
                @php
                    $productoExistente = $maquina->productos->firstWhere(
                        'producto_base_id',
                        $productoBase->id,
                    );

                    // si no hay producto, saltamos los datos
                    if (
                        $productoExistente &&
                        $productoExistente->estado === 'consumido'
                    ) {
                        continue;
                    }

                    $pesoStock = $productoExistente->peso_stock ?? 0;
                    $pesoInicial = $productoExistente->peso_inicial ?? 0;
                    $porcentaje =
                        $pesoInicial > 0
                            ? ($pesoStock / $pesoInicial) * 100
                            : 0;

                    // inicializamos vacíos por defecto
                    $codigoPB = $fabricantePB = $coladaPB = $paquetePB = null;

                    if ($productoExistente) {
                        $codigoPB =
                            $productoExistente->codigo ??
                            ($productoExistente->codigo_producto ?? null);
                        $fabricantePB =
                            $productoExistente->fabricante->nombre ??
                            ($productoExistente->fabricante ?? null);
                        $coladaPB =
                            $productoExistente->n_colada ??
                            ($productoExistente->colada ?? null);
                        $paquetePB =
                            $productoExistente->n_paquete ??
                            ($productoExistente->paquete ?? null);
                    }
                @endphp

                <li class="mb-1">
                    <div
                        class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="text-sm">
                            <span><strong>Ø</strong>
                                {{ $productoBase->diametro }} mm</span>
                            @if (strtoupper($productoBase->tipo) === 'BARRA')
                                <span class="ml-2"><strong>L:</strong>
                                    {{ $productoBase->longitud }}
                                    m</span>
                            @endif
                        </div>

                        <form method="POST"
                            action="{{ route('movimientos.crear') }}">
                            @csrf
                            <input type="hidden" name="tipo"
                                value="recarga_materia_prima">
                            <input type="hidden" name="maquina_id"
                                value="{{ $maquina->id }}">
                            <input type="hidden" name="producto_base_id"
                                value="{{ $productoBase->id }}">
                            @if ($productoExistente)
                                <input type="hidden" name="producto_id"
                                    value="{{ $productoExistente->id }}">
                            @endif
                            <input type="hidden" name="descripcion"
                                value="Recarga solicitada para máquina {{ $maquina->nombre }} (Ø{{ $productoBase->diametro }} {{ strtolower($productoBase->tipo) }}, {{ $pesoStock }} kg)">
                            @if (optional($productoBaseSolicitados)->contains($productoBase->id))
                                <button disabled
                                    class="bg-gray-400 text-white text-sm font-medium px-3 py-1 rounded opacity-60 cursor-not-allowed">
                                    🕓 Solicitado
                                </button>
                            @else
                                <button
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium px-3 py-1 rounded transition">
                                    Solicitar
                                </button>
                            @endif
                        </form>
                    </div>

                    @if ($productoExistente)
                        {{-- Progreso --}}
                        <div id="progreso-container-{{ $productoExistente->id }}"
                            class="relative mt-2 {{ strtoupper($productoBase->tipo) === 'ENCARRETADO' ? 'w-20 h-20' : 'w-full max-w-sm h-4' }} bg-gray-300 overflow-hidden rounded-lg">
                            <div id="progreso-barra-{{ $productoExistente->id }}"
                                class="absolute bottom-0 w-full"
                                style="{{ strtoupper($productoBase->tipo) === 'ENCARRETADO' ? 'height' : 'width' }}: {{ $porcentaje }}%; background-color: green;">
                            </div>
                            <span
                                id="progreso-texto-{{ $productoExistente->id }}"
                                class="absolute inset-0 flex items-center justify-center text-white text-xs font-semibold">
                                {{ number_format($pesoStock, 0, ',', '.') }} /
                                {{ number_format($pesoInicial, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- Info técnica --}}
                        <div class="w-full text-xs text-gray-700 mt-1">
                            <div class="flex flex-wrap gap-x-4 gap-y-1">
                                <span>
                                    <span class="font-semibold">Código:</span>
                                    {{ $codigoPB ?? '—' }}
                                </span>
                                <span>
                                    <span
                                        class="font-semibold">Fabricante:</span>
                                    {{ $fabricantePB ?? '—' }}
                                </span>
                                <span>
                                    <span class="font-semibold">Colada:</span>
                                    {{ $coladaPB ?? '—' }}
                                </span>
                                <span>
                                    <span class="font-semibold">Paquete:</span>
                                    {{ $paquetePB ?? '—' }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <hr class="my-1">
                </li>
            @endforeach
        </ul>

        <!-- BOTONES DEBAJO DE LA MATERIA PRIMA -->
        <div class="flex flex-col gap-2 p-4">
            @if ($elementosAgrupados->isNotEmpty())
                <div id="datos-lote" data-lote='@json($elementosAgrupados->keys()->values())'>
                </div>

                <div x-data="{ cargando: false }">
                    <button type="button"
                        @click="
                        cargando = true;
                        let datos = document.getElementById('datos-lote').dataset.lote;
                        let lote = JSON.parse(datos);
                        Promise.resolve(imprimirEtiquetas(lote))
                            .finally(() => cargando = false);
                        "
                        :disabled="cargando"
                        class="inline-flex items-center gap-2 rounded-md px-4 py-2 font-semibold text-white shadow
       bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">

                        <svg x-show="cargando"
                            class="h-4 w-4 animate-spin text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" role="status"
                            aria-hidden="true">
                            <circle class="opacity-25" cx="12"
                                cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8v4l3.536-3.536A9 9 0 103 12h4z" />
                        </svg>

                        <span x-show="!cargando">🖨️ Imprimir Lote</span>
                        <span x-show="cargando">Cargando…</span>
                    </button>
                </div>
            @endif

            <!-- Botón Reportar Incidencia -->
            <button
                onclick="document.getElementById('modalIncidencia').classList.remove('hidden')"
                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow-md w-full sm:w-auto transition">
                🚨 Incidencia
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
    <div class="bg-white border shadow-md w-full rounded-lg flex flex-col items-center gap-4"
        :class="{
            'sm:col-span-6 sm:col-start-4': showLeft && showRight, // 3 | 6 | 3
            'sm:col-span-9 sm:col-start-4': showLeft && !showRight, // 3 | 9
            'sm:col-span-9 sm:col-start-1': !showLeft && showRight, // 9 | 3
            'sm:col-span-12 sm:col-start-1': !showLeft && !
                showRight // 12 columnas completas
        }">

        {{-- Panel de información de sugerencia de corte --}}
        <div id="element-info-panel"
            class="fixed top-1 left-1/2 z-50 w-full max-w-md hidden
        -translate-x-1/2 -translate-y-1/2
        bg-blue-100 border border-blue-300 shadow-xl rounded-xl p-4">
            <div class="flex items-start justify-between gap-2 mb-2">
                <h4 class="font-semibold text-sm text-blue-900">Sugerencia de
                    corte</h4>
                <button type="button"
                    class="text-blue-700 hover:text-blue-900"
                    onclick="document.getElementById('element-info-panel').classList.add('hidden')">
                    ✕
                </button>
            </div>
            <div id="element-info-body"
                class="text-xs text-blue-900 space-y-1"></div>
        </div>

        {{-- Contenido: 1 o 2 columnas según cantidad de planillas activas --}}
        <div
            class="grid grid-cols-1 gap-4 md:grid-cols-{{ count($planillasActivas) >= 2 ? '2' : '1' }} p-3 w-full">
            @forelse($planillasActivas as $planilla)
                @php
                    $grupoPlanilla = $elementosPorPlanilla->get(
                        $planilla->id,
                        collect(),
                    );
                    $elementosAgrupados = $grupoPlanilla
                        ->groupBy('etiqueta_sub_id')
                        ->sortBy(function ($grupo, $subId) {
                            if (
                                preg_match('/^(.*?)[\.\-](\d+)$/', $subId, $m)
                            ) {
                                return sprintf('%s-%010d', $m[1], (int) $m[2]);
                            }
                            return $subId . '-0000000000';
                        });
                @endphp

                <section
                    class="bg-white rounded-lg border shadow-sm flex flex-col">
                    {{-- Cabecera fija con información de la planilla --}}
                    <header class="p-2 border-b flex-shrink-0 bg-gray-50">
                        <div class="text-sm font-semibold">
                            Planilla
                            <span class="text-gray-500">—
                                {{ $planilla->codigo_limpio ?? 'sin código' }}</span>
                        </div>
                    </header>

                    {{-- Contenido con scroll independiente --}}
                    <div class="p-2 space-y-2 flex-1 overflow-y-auto"
                        style="max-height: 80vh;">
                        @forelse ($elementosAgrupados as $etiquetaSubId => $elementos)
                            @php
                                $firstElement = $elementos->first();
                                $etiqueta =
                                    $firstElement->etiquetaRelacion ??
                                    \App\Models\Etiqueta::where(
                                        'etiqueta_sub_id',
                                        $etiquetaSubId,
                                    )->first();
                                $tieneElementosEnOtrasMaquinas =
                                    isset($otrosElementos[$etiqueta?->id]) &&
                                    $otrosElementos[
                                        $etiqueta?->id
                                    ]->isNotEmpty();
                            @endphp

                            <div
                                class="border rounded-md p-2 hover:bg-gray-50 transition">
                                <x-etiqueta.etiqueta :etiqueta="$etiqueta"
                                    :planilla="$planilla" :maquina-tipo="$maquina->tipo" />
                                <div
                                    class="text-xs text-gray-500 mt-1 flex gap-4 flex-wrap">
                                    <span>Elementos:
                                        {{ $elementos->count() }}</span>
                                    <span>Peso:
                                        {{ number_format($elementos->sum('peso'), 2, ',', '.') }}
                                        kg</span>
                                    @if ($tieneElementosEnOtrasMaquinas)
                                        <span
                                            class="text-amber-600 font-semibold">⚠️
                                            Tiene piezas en otras
                                            máquinas</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center p-4 text-sm text-gray-600">
                                Esta planilla no tiene subetiquetas pendientes.
                            </div>
                        @endforelse
                    </div>
                </section>
            @empty
                <div
                    class="col-span-2 text-center mt-6 p-6 text-gray-800 text-lg font-semibold bg-yellow-100 border border-yellow-300 rounded-xl shadow-sm">
                    📋 No hay planillas en la cola de trabajo.
                    <p class="text-sm font-normal text-gray-600 mt-2">
                        Selecciona una posición en los controles
                        superiores.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- ============================================================
         COLUMNA DERECHA - GESTIÓN DE PAQUETES
         ============================================================ -->
    <div x-show="showRight" x-cloak
        class="w-full bg-white border shadow-md rounded-lg self-start sm:col-span-3 md:sticky md:top-4">

        {{-- TABS: Crear Paquete | Gestión de Paquetes --}}
        <div x-data="{ tabActivo: 'crear' }" class="w-full">

            {{-- Navegación de Tabs --}}
            <div class="flex border-b border-gray-200">
                <button @click="tabActivo = 'crear'"
                    :class="tabActivo === 'crear' ?
                        'border-blue-600 text-blue-600 bg-blue-50' :
                        'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                    class="flex-1 py-3 px-4 text-center border-b-2 font-semibold transition">
                    📦 Crear Paquete
                </button>
                <button @click="tabActivo = 'gestion'"
                    :class="tabActivo === 'gestion' ?
                        'border-blue-600 text-blue-600 bg-blue-50' :
                        'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                    class="flex-1 py-3 px-4 text-center border-b-2 font-semibold transition">
                    🗂️ Gestión
                </button>
            </div>

            <div id="maquina-info" data-maquina-id="{{ $maquina->id }}">
            </div>

            {{-- Contenido del Tab "Crear Paquete" --}}
            <div x-show="tabActivo === 'crear'" class="p-4">
                {{-- Sistema de inputs para crear paquetes --}}
                <div class="bg-gray-100 border p-2 mb-2 shadow-md rounded-lg">
                    <h3 class="font-bold text-xl mb-3">Crear Paquete</h3>

                    <div class="mb-2">
                        <input type="text" id="qrItem"
                            class="w-full border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            style="height:1cm; padding:0.75rem 1rem; font-size:1rem;"
                            placeholder="AÑADIR ETIQUETA AL CARRO"
                            autocomplete="off">
                    </div>

                    {{-- Listado dinámico de etiquetas --}}
                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-700 mb-2">Etiquetas
                            en el carro:</h4>
                        <ul id="itemsList"
                            class="list-disc pl-6 space-y-2 text-sm">
                            <!-- Se rellenan dinámicamente -->
                        </ul>
                    </div>

                    {{-- Botón para crear el paquete --}}
                    <button id="crearPaqueteBtn"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-md w-full transition">
                        📦 Crear Paquete
                    </button>
                </div>

                {{-- Formulario para eliminar paquete --}}
                <form id="deleteForm" method="POST" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <label for="paquete_id"
                        class="block text-gray-700 font-semibold mb-2">
                        ID del Paquete a Eliminar:
                    </label>
                    <input type="number" name="paquete_id" id="paquete_id"
                        required
                        class="w-full border border-gray-300 p-2 rounded mb-2 focus:ring-2 focus:ring-red-500 focus:outline-none"
                        placeholder="Ingrese ID del paquete">
                    <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow-md w-full transition">
                        🗑️ Eliminar Paquete
                    </button>
                </form>
            </div>

            {{-- Contenido del Tab "Gestión de Paquetes" --}}
            <div x-show="tabActivo === 'gestion'" class="p-4">
                @include('components.maquinas.partes.gestionPaquetes')
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODALES
     ============================================================ -->
<x-maquinas.modales.cambio-maquina :maquina="$maquina" :maquinas="$maquinas" />
<x-maquinas.modales.dividir-elemento />

<!-- Modal Patrón -->
<div id="modalPatron"
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div
        class="bg-white rounded-lg shadow-lg p-4 w-auto max-w-full h-[85vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold">Elementos del patrón</h2>
            <button onclick="cerrarModalPatron()"
                class="text-gray-600 hover:text-black">✖</button>
        </div>
        <div id="contenedorPatron" class="flex flex-col gap-4"></div>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->

{{-- Script para cerrar modal de patrón --}}
<script>
    function cerrarModalPatron() {
        const modal = document.getElementById('modalPatron');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // Cerrar con ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            cerrarModalPatron();
        }
    });

    // Cerrar haciendo clic en el fondo del modal
    document.getElementById('modalPatron')?.addEventListener('click', function(
        event) {
        const fondoModal = event.currentTarget;
        const contenido = fondoModal.querySelector('div.bg-white');
        if (!contenido.contains(event.target)) {
            cerrarModalPatron();
        }
    });
</script>

{{-- Variables globales para scripts de máquina --}}
@once
    <script>
        // Tipo de material para la lógica de fabricación
        window.MAQUINA_TIPO = @json(strtolower($maquina->tipo_material));
        window.MAQUINA_NOMBRE = "{{ $maquina->nombre }}";

        // Mapa etiqueta->diámetro
        window.DIAMETRO_POR_ETIQUETA = @json(
            $diametroPorEtiqueta ?:
            $elementosAgrupados->map(function ($els) {
                $c = collect($els)->pluck('diametro')->filter()->map(fn($d) => (int) $d);
                return (int) $c->countBy()->sortDesc()->keys()->first();
            }));

        // Solo si es barra, pasamos longitudes por diámetro
        @if ($esBarra)
            window.LONGITUDES_POR_DIAMETRO = @json($longitudesPorDiametro);
        @endif
    </script>
@endonce

{{-- Script para validación de eliminación de paquetes --}}
<script>
    document.getElementById('deleteForm')?.addEventListener('submit', function(
        event) {
        event.preventDefault();
        const paqueteId = document.getElementById('paquete_id').value;

        if (!paqueteId) {
            Swal.fire({
                icon: "warning",
                title: "Campo vacío",
                text: "Por favor, ingrese un ID válido.",
                confirmButtonColor: "#3085d6",
            });
            return;
        }

        Swal.fire({
            title: "¿Estás seguro?",
            text: "Esta acción no se puede deshacer.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                this.action = "/paquetes/" + paqueteId;
                this.submit();
            }
        });
    });
</script>

{{-- 🔥 Script de validación para selectores de posiciones --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formPosiciones = document.getElementById(
            'form-posiciones-planillas');
        if (!formPosiciones) return;

        const select1 = formPosiciones.querySelector(
            'select[name="posicion_1"]');
        const select2 = formPosiciones.querySelector(
            'select[name="posicion_2"]');

        if (!select1 || !select2) return;

        /**
         * Validar que no se repitan posiciones
         */
        function validarPosiciones() {
            const pos1 = select1.value;
            const pos2 = select2.value;

            // Si ambas están seleccionadas y son iguales, limpiar la segunda
            if (pos1 && pos2 && pos1 === pos2) {
                select2.value = '';

                // Usar SweetAlert2 si está disponible, sino usar alert nativo
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Posiciones duplicadas',
                        text: 'No puedes seleccionar la misma posición dos veces',
                        confirmButtonColor: '#3085d6',
                    });
                } else {
                    alert(
                        'No puedes seleccionar la misma posición dos veces');
                }

                return false;
            }
            return true;
        }

        // Validar al cambiar cualquier select
        select1.addEventListener('change', validarPosiciones);
        select2.addEventListener('change', validarPosiciones);

        // Validar antes de enviar el formulario (por si acaso)
        formPosiciones.addEventListener('submit', function(e) {
            if (!validarPosiciones()) {
                e.preventDefault();
            }
        });

        // Log para debug (opcional, puedes quitarlo en producción)
        console.log(
        '✅ Sistema de selección de planillas inicializado', {
            posicion1: select1.value,
            posicion2: select2.value,
            posicionesDisponibles: Array.from(select1.options)
                .map(o => o.value).filter(v => v)
        });
    });
</script>

{{-- Estilos adicionales para los selectores --}}
<style>
    /* Estilos para los selectores de posiciones */
    #form-posiciones-planillas select {
        min-width: 100px;
        cursor: pointer;
    }

    #form-posiciones-planillas select:hover {
        border-color: #3b82f6;
    }

    #form-posiciones-planillas select:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Indicador visual cuando hay dos planillas seleccionadas */
    #form-posiciones-planillas:has(select[name="posicion_1"]:not([value=""])) :has(select[name="posicion_2"]:not([value=""])) {
        background-color: #eff6ff;
        padding: 0.5rem;
        border-radius: 0.375rem;
    }

    /* Mejorar apariencia de opciones en hover (para navegadores compatibles) */
    #form-posiciones-planillas select option:hover {
        background-color: #dbeafe;
    }

    /* Responsive: ajustar en móviles */
    @media (max-width: 640px) {
        #form-posiciones-planillas {
            flex-direction: column;
            align-items: stretch !important;
        }

        #form-posiciones-planillas select {
            width: 100%;
        }

        #form-posiciones-planillas label {
            margin-bottom: 0.5rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const inputQr = document.getElementById('qrItem');
        const itemsList = document.getElementById('itemsList');
        const btnCrear = document.getElementById('crearPaqueteBtn');
        const maquinaInfo = document.getElementById('maquina-info');

        // Carro local de etiquetas escaneadas (solo IDs)
        const etiquetasSeleccionadas = new Set();

        if (!inputQr || !itemsList || !btnCrear || !maquinaInfo) {
            // Si falta algo, no hacemos nada
            return;
        }

        const maquinaId = maquinaInfo.dataset.maquinaId;

        /**
         * Añade una etiqueta al carro cuando se escanea su código/ID.
         * Aquí asumo que el valor del input es directamente el ID de la etiqueta.
         * Si usas otro formato, adapta esta parte.
         */
        inputQr.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter') return;

            e.preventDefault();

            const valor = inputQr.value.trim();
            if (!valor) return;

            const etiquetaId = parseInt(valor, 10);
            if (isNaN(etiquetaId)) {
                // Si no es un número, ignoramos (puedes meter aquí un Swal)
                inputQr.value = '';
                return;
            }

            if (etiquetasSeleccionadas.has(etiquetaId)) {
                // Ya está en el carro
                inputQr.value = '';
                return;
            }

            etiquetasSeleccionadas.add(etiquetaId);

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
                    "{{ route('paquetes.storeDesdeMaquina') }}", {
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
