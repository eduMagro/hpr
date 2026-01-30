<x-app-layout>
    <x-slot name="title">{{ $maquina->nombre }} - {{ config('app.name') }}</x-slot>

    {{-- CSS externalizado para mejor cacheo --}}
    <link rel="stylesheet" href="{{ asset('css/maquinas-show.css') }}">

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-1 lg:gap-4">
            <h2 class="font-semibold text-base lg:text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <strong>{{ $maquina->nombre }}</strong>
                <span class="text-sm lg:text-base font-normal text-gray-600 dark:text-gray-400">- {{ $usuario1->name }}@if ($usuario2), {{ $usuario2->name }}@endif</span>
            </h2>

            <div class="flex flex-wrap items-center gap-2 lg:gap-4">
                @if ($maquina->tipo !== 'grua' && $maquina->tipo !== 'ensambladora')
                    {{-- Selectores de posiciones de planillas --}}
                    <div class="contenedor-selectores-planilla">
                        <select id="posicion_1" name="posicion_1" onchange="cambiarPosicionesPlanillas()">
                            <option value="0" data-planilla-id="" data-planilla-codigo="">0</option>
                            @foreach ($posicionesDisponibles as $pos)
                                <option value="{{ $pos }}"
                                    data-planilla-id="{{ $planillaIdsPorPosicion[$pos] ?? '' }}"
                                    data-planilla-codigo="{{ $codigosPorPosicion[$pos] ?? '' }}"
                                    {{ $posicion1 == $pos ? 'selected' : '' }}>
                                    {{ $pos }} - {{ $codigosPorPosicion[$pos] ?? '' }}
                                </option>
                            @endforeach
                        </select>

                        <span class="separador">+</span>

                        <select id="posicion_2" name="posicion_2" onchange="cambiarPosicionesPlanillas()">
                            <option value="0" data-planilla-id="" data-planilla-codigo="">0</option>
                            @foreach ($posicionesDisponibles as $pos)
                                <option value="{{ $pos }}"
                                    data-planilla-id="{{ $planillaIdsPorPosicion[$pos] ?? '' }}"
                                    data-planilla-codigo="{{ $codigosPorPosicion[$pos] ?? '' }}"
                                    {{ $posicion2 == $pos ? 'selected' : '' }}>
                                    {{ $pos }} - {{ $codigosPorPosicion[$pos] ?? '' }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Indicador de carga --}}
                        <span id="loading-planillas" class="spinner-loading" style="display: none;">
                            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </span>
                    </div>

                    {{-- Estilos movidos a public/css/maquinas-show.css --}}

                    <script>
                        // Variable global para evitar múltiples ejecuciones simultáneas
                        let cambiarPosicionesTimeout = null;
                        let cambiarPosicionesEnProceso = false;

                        function cambiarPosicionesPlanillas() {
                            // Evitar ejecuciones múltiples si ya está en proceso
                            if (cambiarPosicionesEnProceso) {
                                return;
                            }

                            // Limpiar timeout anterior si existe
                            if (cambiarPosicionesTimeout) {
                                clearTimeout(cambiarPosicionesTimeout);
                            }

                            // Debounce de 300ms para evitar llamadas múltiples
                            cambiarPosicionesTimeout = setTimeout(() => {
                                ejecutarCambioPlanillas();
                            }, 300);
                        }

                        async function ejecutarCambioPlanillas() {
                            const pos1 = document.getElementById('posicion_1').value;
                            const pos2 = document.getElementById('posicion_2').value;

                            // Validar que no sean la misma posición (ignorar si ambas son "0")
                            if (pos1 && pos2 && pos1 !== '0' && pos2 !== '0' && pos1 === pos2) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Posiciones duplicadas',
                                    text: 'No puedes seleccionar la misma posición dos veces',
                                    confirmButtonColor: '#3085d6',
                                });
                                document.getElementById('posicion_2').value = '0';
                                return;
                            }

                            // Marcar como en proceso
                            cambiarPosicionesEnProceso = true;

                            const select1 = document.getElementById('posicion_1');
                            const select2 = document.getElementById('posicion_2');
                            const loadingIndicator = document.getElementById('loading-planillas');
                            const gridActual = document.getElementById('grid-maquina');

                            select1.disabled = true;
                            select2.disabled = true;

                            if (loadingIndicator) {
                                loadingIndicator.style.display = 'inline-block';
                            }

                            // Ocultar grid actual con transición
                            if (gridActual) {
                                gridActual.style.opacity = '0.3';
                            }

                            // Construir URL
                            const params = new URLSearchParams(window.location.search);
                            if (pos1 && pos1 !== '0') {
                                params.set('posicion_1', pos1);
                            } else {
                                params.delete('posicion_1');
                            }
                            if (pos2 && pos2 !== '0') {
                                params.set('posicion_2', pos2);
                            } else {
                                params.delete('posicion_2');
                            }

                            const newUrl = window.location.pathname + '?' + params.toString();

                            // Actualizar URL sin recargar
                            window.history.pushState({}, '', newUrl);

                            try {
                                // Fetch AJAX
                                const response = await fetch(newUrl, {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                });
                                const html = await response.text();

                                // Parsear HTML
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const nuevoGrid = doc.getElementById('grid-maquina');

                                if (nuevoGrid && gridActual) {
                                    // Extraer y ejecutar scripts con variables globales
                                    const scripts = doc.querySelectorAll('script');
                                    scripts.forEach(script => {
                                        const content = script.textContent || script.innerText;
                                        // Buscar scripts con definiciones de variables globales
                                        if (content.includes('window.elementosAgrupadosScript') ||
                                            content.includes('window.SUGERENCIAS') ||
                                            content.includes('window.gruposResumenData') ||
                                            content.includes('window.etiquetasData') ||
                                            content.includes('window.pesosElementos')) {
                                            try {
                                                // Crear función para ejecutar en contexto global
                                                const fn = new Function(content);
                                                fn();
                                            } catch (e) {
                                                // Error ejecutando script
                                            }
                                        }
                                    });

                                    // Reemplazar contenido del grid
                                    gridActual.innerHTML = nuevoGrid.innerHTML;

                                    // Actualizar data sources si existe
                                    if (window.setDataSources && window.elementosAgrupadosScript) {
                                        window.setDataSources({
                                            sugerencias: window.SUGERENCIAS || {},
                                            elementosAgrupados: window.elementosAgrupadosScript || []
                                        });
                                    }

                                    // Esperar a que el DOM se actualice
                                    await new Promise(resolve => setTimeout(resolve, 100));

                                    // Renderizar SVGs
                                    if (window.elementosAgrupadosScript && typeof window.renderizarGrupoSVG === 'function') {
                                        window.elementosAgrupadosScript.forEach((grupo, gidx) => {
                                            try {
                                                window.renderizarGrupoSVG(grupo, gidx);
                                            } catch (e) {
                                                // Error renderizando grupo
                                            }
                                        });
                                    }

                                    // Renderizar SVGs de grupos de resumen (leyendo datos del DOM)
                                    const gruposResumenCards = gridActual.querySelectorAll('.grupo-resumen-card');
                                    if (gruposResumenCards.length > 0 && typeof window.renderizarGrupoSVG === 'function') {
                                        gruposResumenCards.forEach((card) => {
                                            const contenedorSvgId = card.dataset.contenedorSvgId;
                                            const grupoId = card.dataset.grupoId;
                                            let elementos = [];
                                            try {
                                                elementos = JSON.parse(card.dataset.elementos || '[]');
                                            } catch (e) {
                                                // Error parsing elementos del grupo
                                            }

                                            if (contenedorSvgId && elementos.length > 0) {
                                                try {
                                                    const grupoData = {
                                                        id: parseInt(contenedorSvgId),
                                                        etiqueta: {
                                                            id: parseInt(contenedorSvgId)
                                                        },
                                                        elementos: elementos
                                                    };
                                                    window.renderizarGrupoSVG(grupoData, parseInt(grupoId));
                                                } catch (e) {
                                                    // Error renderizando grupo resumen
                                                }
                                            }
                                        });
                                    }

                                    // Mostrar grid con transición
                                    gridActual.style.opacity = '1';

                                    // Mostrar etiquetas
                                    document.querySelectorAll('.proceso').forEach(el => {
                                        el.style.opacity = '1';
                                    });

                                    // Re-aplicar clases de columnas
                                    if (window.updateGridClasses) {
                                        const numPlanillas = gridActual.querySelectorAll('.planilla-section, section.bg-gradient-to-br')
                                            .length;
                                        if (numPlanillas >= 2) {
                                            gridActual.classList.remove('una-planilla');
                                            gridActual.classList.add('dos-planillas');
                                        } else {
                                            gridActual.classList.remove('dos-planillas');
                                            gridActual.classList.add('una-planilla');
                                        }
                                        const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'true');
                                        const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
                                        window.updateGridClasses(showLeft, showRight);
                                    }

                                    // Re-inicializar input QR si existe (el botón crear usa event delegation)
                                    if (window.TrabajoPaquete?.inicializar) {
                                        window.TrabajoPaquete.inicializar();
                                    }
                                }
                            } catch (error) {
                                // En caso de error, hacer reload como fallback
                                window.location.href = newUrl;
                                return;
                            }

                            // Re-habilitar selectores
                            select1.disabled = false;
                            select2.disabled = false;
                            if (loadingIndicator) {
                                loadingIndicator.style.display = 'none';
                            }
                            cambiarPosicionesEnProceso = false;
                        }
                    </script>

                    {{-- Controles de vista para máquinas tipo normal --}}
                    <div class="flex items-center gap-2" x-data="{
                        showLeft: JSON.parse(localStorage.getItem('showLeft') ?? 'true'),
                        showRight: JSON.parse(localStorage.getItem('showRight') ?? 'true'),
                        filtroEstado: localStorage.getItem('filtroEstadoEtiqueta') ?? 'todos',
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
                        setFiltroEstado(estado) {
                            this.filtroEstado = estado;
                            localStorage.setItem('filtroEstadoEtiqueta', estado);
                            window.dispatchEvent(new CustomEvent('filtroEstadoChanged', { detail: estado }));
                        }
                    }"
                        @toggle-left.window="showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'true')"
                        @toggle-right.window="showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true')"
                        @solo.window="showLeft = false; showRight = false">
                        {{-- Filtros de estado de etiquetas - Select personalizado --}}
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false" type="button"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium border shadow-sm transition-all duration-200 flex items-center gap-2 min-w-[130px] justify-between"
                                :class="{
                                    'bg-white border-gray-300 text-gray-800': filtroEstado === 'todos',
                                    'bg-purple-500 border-purple-600 text-white': filtroEstado === 'sin-paquete',
                                    'bg-blue-500 border-blue-600 text-white': filtroEstado === 'en-paquete',
                                    'bg-gray-500 border-gray-600 text-white': filtroEstado === 'pendiente',
                                    'bg-yellow-500 border-yellow-600 text-white': filtroEstado === 'fabricando',
                                    'bg-green-500 border-green-600 text-white': filtroEstado === 'completada'
                                }">
                                <span
                                    x-text="{
                                    'todos': 'Todas',
                                    'sin-paquete': 'Sin paquete',
                                    'en-paquete': 'En paquete',
                                    'pendiente': 'Pendientes',
                                    'fabricando': 'Fabricando',
                                    'completada': 'Completadas'
                                }[filtroEstado]"></span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                <button @click="setFiltroEstado('todos'); open = false" type="button"
                                    class="w-full px-3 py-2 text-left text-sm font-medium bg-white hover:bg-gray-100 text-gray-800 border-b border-gray-100">
                                    Todas
                                </button>
                                <button @click="setFiltroEstado('sin-paquete'); open = false" type="button"
                                    class="w-full px-3 py-2 text-left text-sm font-medium bg-purple-500 hover:bg-purple-600 text-white border-b border-purple-400">
                                    Sin paquete
                                </button>
                                <button @click="setFiltroEstado('en-paquete'); open = false" type="button"
                                    class="w-full px-3 py-2 text-left text-sm font-medium bg-blue-500 hover:bg-blue-600 text-white border-b border-blue-400">
                                    En paquete
                                </button>
                                <button @click="setFiltroEstado('pendiente'); open = false" type="button"
                                    class="w-full px-3 py-2 text-left text-sm font-medium bg-gray-500 hover:bg-gray-600 text-white border-b border-gray-400">
                                    Pendientes
                                </button>
                                <button @click="setFiltroEstado('fabricando'); open = false" type="button"
                                    class="w-full px-3 py-2 text-left text-sm font-medium bg-yellow-500 hover:bg-yellow-600 text-white border-b border-yellow-400">
                                    Fabricando
                                </button>
                                <button @click="setFiltroEstado('completada'); open = false" type="button"
                                    class="w-full px-3 py-2 text-left text-sm font-medium bg-green-500 hover:bg-green-600 text-white">
                                    Completadas
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Botón Exportar BVBs para MSR20 --}}
                    @if (strtoupper($maquina->nombre) === 'MSR20')
                        <a href="#" onclick="exportarBVBS(event)"
                            class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2"
                            title="Exportar BVBs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Exportar BVBs
                        </a>
                        <script>
                            function exportarBVBS(event) {
                                event.preventDefault();
                                const posicion = document.getElementById('posicion_1').value;
                                if (!posicion || posicion === '0') {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Selecciona una posición',
                                        text: 'Debes seleccionar una posición en el primer selector para exportar.',
                                    });
                                    return;
                                }
                                window.location.href = "{{ route('maquinas.exportar-bvbs', $maquina->id) }}?posicion=" + posicion;
                            }
                        </script>
                    @endif

                    {{-- Botones Comprimir/Descomprimir/Resumir Etiquetas --}}
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="comprimirEtiquetas()"
                            class="px-3 py-2 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-1"
                            title="Comprimir: Agrupa elementos hermanos en mismas etiquetas (máx 5 por etiqueta)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                            Comprimir
                        </button>
                        <button type="button" onclick="descomprimirEtiquetas()"
                            class="px-3 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-1"
                            title="Descomprimir: Separa elementos en etiquetas individuales (1 elemento = 1 etiqueta)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                            Descomprimir
                        </button>
                    </div>

                    {{-- Botón Planilla Completada --}}
                    @if ($maquina->tipo !== 'grua')
                        <button type="button" onclick="completarPlanillaActual()"
                            class="px-4 py-2 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2"
                            title="Marcar planilla como completada y pasar a la siguiente">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Planilla Completada
                        </button>
                    @endif
                @endif

                <form method="POST" action="{{ route('turno.cambiarMaquina') }}" id="form-cambiar-maquina">
                    @csrf
                    <input type="hidden" name="asignacion_id" value="{{ $turnoHoy->id ?? '' }}">
                    <input type="hidden" name="nueva_maquina_id" id="hidden-nueva-maquina-id" value="">

                    <div class="relative">
                        <select id="select-cambiar-maquina"
                            onchange="cambiarMaquinaSelect(this, {{ $maquina->id }})"
                            class="appearance-none bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:border-blue-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 rounded px-2 py-1 lg:px-4 lg:py-2 pr-8 lg:pr-10 text-xs lg:text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm transition-all duration-200 cursor-pointer">
                            @foreach ($maquinas as $m)
                                <option value="{{ $m->id }}" {{ $m->id == $maquina->id ? 'selected' : '' }}>
                                    {{ $m->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 lg:px-3 text-gray-500 dark:text-gray-400">
                            <svg class="w-3 h-3 lg:w-4 lg:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </form>

                <script>
                    function cambiarMaquinaSelect(select, maquinaActualId) {
                        const nuevaMaquinaId = select.value;

                        // Evitar navegar a la misma máquina
                        if (parseInt(nuevaMaquinaId) === parseInt(maquinaActualId)) {
                            return;
                        }

                        const overlay = document.getElementById('overlay-cambiar-maquina');
                        const nombreMaquina = document.getElementById('loader-maquina-nombre');
                        const hiddenInput = document.getElementById('hidden-nueva-maquina-id');

                        if (hiddenInput) hiddenInput.value = nuevaMaquinaId;

                        const selectedOption = select.options[select.selectedIndex];
                        if (nombreMaquina) nombreMaquina.textContent = selectedOption.text;

                        if (overlay) overlay.classList.add('active');

                        select.disabled = true;
                        select.classList.add('opacity-50');

                        setTimeout(() => {
                            const form = document.getElementById('form-cambiar-maquina');
                            if (form) form.submit();
                        }, 300);
                    }
                </script>

                {{-- Overlay de carga al cambiar máquina (estilos en maquinas-show.css) --}}
                <div id="overlay-cambiar-maquina" class="fixed inset-0 z-[9999]">
                    {{-- Fondo con blur --}}
                    <div class="overlay-bg absolute inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
                    {{-- Contenedor central --}}
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="loader-card bg-white rounded-2xl shadow-2xl p-8 flex flex-col items-center gap-5">
                            {{-- Spinner elegante --}}
                            <div class="relative w-16 h-16">
                                <div class="absolute inset-0 border-4 border-blue-100 rounded-full"></div>
                                <div
                                    class="absolute inset-0 border-4 border-transparent border-t-blue-600 rounded-full spinner-ring">
                                </div>
                            </div>
                            {{-- Texto --}}
                            <div class="text-center">
                                <p class="text-gray-800 font-semibold text-lg">Cambiando de máquina</p>
                                <p class="text-gray-500 text-sm mt-1" id="loader-maquina-nombre"></p>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </x-slot>

    <div class="w-full sm:px-4">
        <!-- Grid principal -->
        <div class="w-full">
            @if ($maquina->tipo === 'grua' && !($modoFabricacionGrua ?? false))
                <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                    {{-- <x-maquinas.tipo.tipo-grua :movimientosPendientes="$movimientosPendientes" :ubicaciones="$ubicaciones" :paquetes="$paquetes" /> --}}
                    <x-maquinas.tipo.tipo-grua :maquina="$maquina" :movimientos-pendientes="$movimientosPendientes" :movimientos-completados="$movimientosCompletados"
                        :ubicaciones-disponibles-por-producto-base="$ubicacionesDisponiblesPorProductoBase" />
                    @include('components.maquinas.modales.grua.modales-grua')

            @elseif ($maquina->tipo === 'ensambladora')
                <x-maquinas.tipo.tipo-ensamblado
                    :maquina="$maquina"
                    :planillasActivas="$planillasActivas ?? collect()"
                    :ordenesEnsamblaje="$ordenesEnsamblaje ?? collect()"
                    :entidadesActivas="$entidadesActivas ?? collect()"
                    :elementosPorDiametro="$elementosPorDiametro ?? collect()"
                    :etiquetasPorEntidad="$etiquetasPorEntidad ?? collect()"
                />

                @else
                    <x-maquinas.tipo.tipo-normal :maquina="$maquina" :maquinas="$maquinas" :elementos-agrupados="$elementosAgrupados" :productos-base-compatibles="$productosBaseCompatibles"
                        :producto-base-solicitados="$productoBaseSolicitados" :planillas-activas="$planillasActivas" :elementos-por-planilla="$elementosPorPlanilla" :es-barra="$esBarra" :longitudes-por-diametro="$longitudesPorDiametro"
                        :diametro-por-etiqueta="$diametroPorEtiqueta" :elementos-agrupados-script="$elementosAgrupadosScript" :posiciones-disponibles="$posicionesDisponibles" :posicion1="$posicion1" :posicion2="$posicion2"
                        :grupos-resumen="$gruposResumen ?? collect()" :etiquetas-en-grupos="$etiquetasEnGrupos ?? []" :etiquetas-pre-cargadas="$etiquetasPreCargadas ?? collect()" />

                    @include('components.maquinas.modales.normal.modales-normal')

                    {{-- Incluir modal de mover paquete para grúa en modo fabricación --}}
                    @if ($modoFabricacionGrua ?? false)
                        @include('components.maquinas.modales.grua.modales-grua')
                    @endif
            @endif

        </div>

        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script src="{{ asset('js/imprimirQrS.js') }}"></script>

        {{-- Script del sistema de resumen de etiquetas (NO necesario para grúa ni ensambladora) --}}
        @if ($maquina->tipo !== 'grua' && $maquina->tipo !== 'ensambladora')
            <script src="{{ asset('js/resumir-etiquetas.js') }}"></script>
        @endif

        <script>
            @if ($maquina->tipo !== 'grua' && $maquina->tipo !== 'ensambladora')
                window.SUGERENCIAS = @json($sugerenciasPorElemento ?? []);
                window.elementosAgrupadosScript = @json($elementosAgrupadosScript ?? null);
                window.gruposResumenData = @json($gruposResumen ?? []);
                window.etiquetasEnGrupos = @json($etiquetasEnGrupos ?? []);
                window.rutaDividirElemento = "{{ route('elementos.dividir') }}";
                window.etiquetasData = @json($etiquetasData ?? []);
                window.pesosElementos = @json($pesosElementos ?? []);
            @endif

            window.maquinaId = @json($maquina->id);
            window.MAQUINA_TIPO = @json($maquina->tipo_material);
            window.MAQUINA_CODIGO = @json($maquina->codigo);
            window.MAQUINA_TIPO_NOMBRE = @json($maquina->tipo);
            window.ubicacionId = @json(optional($ubicacion)->id);

            /**
             * Función para refrescar las etiquetas sin recargar la página completa
             * Se llama después de dividir elementos o mover a nuevas subetiquetas
             */
            window.refrescarEtiquetasMaquina = async function() {
                try {

                    // Hacer fetch a la URL actual con los mismos parámetros
                    const currentUrl = window.location.href;
                    const response = await fetch(currentUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Error al obtener datos actualizados');
                    }

                    const html = await response.text();

                    // Parsear el HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Obtener el nuevo grid
                    const nuevoGrid = doc.getElementById('grid-maquina');
                    if (!nuevoGrid) {
                        throw new Error('No se encontró el grid en la respuesta');
                    }

                    const gridActual = document.getElementById('grid-maquina');
                    if (!gridActual) {
                        throw new Error('No se encontró el grid actual');
                    }

                    // Animación de salida
                    gridActual.style.opacity = '0';
                    gridActual.style.transition = 'opacity 0.2s ease';

                    await new Promise(resolve => setTimeout(resolve, 200));

                    // Reemplazar contenido
                    gridActual.innerHTML = nuevoGrid.innerHTML;

                    // Actualizar variables globales
                    const scripts = doc.querySelectorAll('script');
                    let variablesActualizadas = 0;
                    scripts.forEach(script => {
                        const content = script.textContent || script.innerText;
                        if (content.includes('window.elementosAgrupadosScript') ||
                            content.includes('window.etiquetasData') ||
                            content.includes('window.pesosElementos') ||
                            content.includes('window.gruposResumenData') ||
                            content.includes('window.etiquetasEnGrupos') ||
                            content.includes('window.DIAMETRO_POR_ETIQUETA') ||
                            content.includes('window.SUGERENCIAS')) {
                            try {
                                // Usar new Function para ejecutar en contexto global
                                const fn = new Function(content);
                                fn();
                                variablesActualizadas++;
                            } catch (e) {
                                // Error al ejecutar script
                            }
                        }
                    });

                    // Actualizar data sources
                    if (window.setDataSources && window.elementosAgrupadosScript) {
                        window.setDataSources({
                            sugerencias: window.SUGERENCIAS || {},
                            elementosAgrupados: window.elementosAgrupadosScript || []
                        });
                    }

                    // Re-inicializar input QR si existe (el botón crear usa event delegation global)
                    if (window.TrabajoPaquete && window.TrabajoPaquete.inicializar) {
                        window.TrabajoPaquete.inicializar();
                    }

                    // Animación de entrada y re-renderizado de SVGs
                    requestAnimationFrame(() => {
                        gridActual.style.opacity = '1';

                        // Mostrar etiquetas con animación
                        document.querySelectorAll('.proceso').forEach(el => {
                            el.style.opacity = '1';
                        });

                        // Re-aplicar clases de columnas
                        if (window.updateGridClasses) {
                            const numPlanillas = gridActual.querySelectorAll(
                                '.planilla-section, section.bg-gradient-to-br').length;

                            if (numPlanillas >= 2) {
                                gridActual.classList.remove('una-planilla');
                                gridActual.classList.add('dos-planillas');
                            } else {
                                gridActual.classList.remove('dos-planillas');
                                gridActual.classList.add('una-planilla');
                            }

                            const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'true');
                            const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
                            window.updateGridClasses(showLeft, showRight);
                        }

                        // Re-renderizar SVGs después de que el DOM esté listo
                        setTimeout(() => {
                            // Re-renderizar SVGs de etiquetas individuales
                            if (window.elementosAgrupadosScript && window.renderizarGrupoSVG) {
                                window.elementosAgrupadosScript.forEach((grupo, gidx) => {
                                    window.renderizarGrupoSVG(grupo, gidx);
                                });
                            }

                            // Re-renderizar SVGs de grupos de resumen (usando data attributes del DOM)
                            const gruposResumenCards = document.querySelectorAll('.grupo-resumen-card');
                            if (gruposResumenCards.length > 0 && window.renderizarGrupoSVG) {
                                gruposResumenCards.forEach((card) => {
                                    const contenedorSvgId = card.dataset.contenedorSvgId;
                                    const grupoId = card.dataset.grupoId;
                                    let elementos = [];
                                    try {
                                        elementos = JSON.parse(card.dataset.elementos || '[]');
                                    } catch (e) {
                                        // Error parsing elementos
                                    }

                                    if (contenedorSvgId && elementos.length > 0) {
                                        const grupoData = {
                                            id: parseInt(contenedorSvgId),
                                            etiqueta: {
                                                id: parseInt(contenedorSvgId)
                                            },
                                            elementos: elementos
                                        };
                                        window.renderizarGrupoSVG(grupoData, parseInt(grupoId));
                                    }
                                });
                            }

                            // Disparar evento para indicar que los SVGs están listos
                            window.dispatchEvent(new CustomEvent('svg-ready'));
                        }, 150); // Delay aumentado para asegurar que el DOM esté completamente actualizado
                    });

                    // Re-aplicar filtro de estado después de refrescar
                    const filtroActual = localStorage.getItem('filtroEstadoEtiqueta') ?? 'todos';
                    window.aplicarFiltroEstadoEtiquetas(filtroActual);

                } catch (error) {
                    // Si falla, recargar la página como fallback
                    window.location.reload();
                }
            };

            /**
             * Aplica el filtro de estado a las etiquetas
             * @param {string} estado - 'todos', 'sin-paquete', 'en-paquete', 'pendiente', 'fabricando', 'completada'
             */
            window.aplicarFiltroEstadoEtiquetas = function(estado) {
                const etiquetas = document.querySelectorAll('.etiqueta-card');

                etiquetas.forEach(etiqueta => {
                    const estadoEtiqueta = etiqueta.dataset.estado || 'pendiente';
                    const enPaquete = etiqueta.dataset.enPaquete === 'true';
                    const wrapper = etiqueta.closest('.etiqueta-wrapper') || etiqueta.parentElement;

                    let mostrar = false;

                    if (estado === 'todos') {
                        mostrar = true;
                    } else if (estado === 'sin-paquete') {
                        mostrar = !enPaquete;
                    } else if (estado === 'en-paquete') {
                        mostrar = enPaquete;
                    } else if (estadoEtiqueta === estado) {
                        mostrar = true;
                    }

                    wrapper.style.display = mostrar ? '' : 'none';
                });
            };

            // Escuchar cambios en el filtro de estado (con limpieza previa idealmente, o función nombrada)
            window.handleFiltroEstadoChanged = function(e) {
                if (window.aplicarFiltroEstadoEtiquetas) window.aplicarFiltroEstadoEtiquetas(e.detail);
            };

            // Remover para evitar duplicados si el script se vuelve a ejecutar
            window.removeEventListener('filtroEstadoChanged', window.handleFiltroEstadoChanged);
            window.addEventListener('filtroEstadoChanged', window.handleFiltroEstadoChanged);

            // Nota: El listener DOMContentLoaded previo se ha movido a initMaquinasShowPage
        </script>

        <!-- ✅ Vite: Bundle de máquinas (NO necesario para grúa) -->
        @if ($maquina->tipo !== 'grua')
            @vite(['resources/js/maquinaJS/maquina-bundle.js'])
            <script src="{{ asset('js/maquinaJS/sl28/cortes.js') }}?v={{ time() }}"></script>
        @endif

        <script>
            // Variable global para controladores
            window.maquinasShowHandlers = window.maquinasShowHandlers || {};

            // Validación de posiciones de planillas en el header
            function initValidacionPosicionesPlanillasHeader() {
                const form = document.getElementById('form-posiciones-planillas-header');
                if (!form) return;

                // Limpiar listeners anteriores clonando
                const newForm = form.cloneNode(true);
                form.replaceWith(newForm);

                // Re-seleccionar elementos en el nuevo formulario
                const select1 = newForm.querySelector('select[name="posicion_1"]');
                const select2 = newForm.querySelector('select[name="posicion_2"]');
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
                newForm.addEventListener('submit', (e) => !validar() && e.preventDefault());
            }

            // Función principal de inicialización
            function initMaquinasShowPage() {
                if (document.body.dataset.maquinasShowPageInit === 'true') return;

                // 1. Context Menu
                const ctxHandler = function(e) {
                    if (e.target.closest('.proceso')) {
                        e.preventDefault();
                    }
                };
                // Limpiar previo
                if (window.maquinasShowHandlers.ctx) {
                    document.removeEventListener('contextmenu', window.maquinasShowHandlers.ctx, {
                        capture: true
                    });
                }
                window.maquinasShowHandlers.ctx = ctxHandler;
                document.addEventListener('contextmenu', ctxHandler, {
                    capture: true
                });

                // 2. Validación Header
                initValidacionPosicionesPlanillasHeader();

                // 3. Shortcuts (si están cargados)
                if (typeof window.initMaquinasShortcuts === 'function') {
                    window.initMaquinasShortcuts();
                }

                // 4. Filtros
                const filtroInicial = localStorage.getItem('filtroEstadoEtiqueta') ?? 'todos';
                setTimeout(() => {
                    if (window.aplicarFiltroEstadoEtiquetas) window.aplicarFiltroEstadoEtiquetas(filtroInicial);
                }, 500);

                document.body.dataset.maquinasShowPageInit = 'true';
            }

            // Registrar en el sistema global
            window.pageInitializers = window.pageInitializers || [];
            window.pageInitializers.push(initMaquinasShowPage);

            // Listeners
            document.addEventListener('livewire:navigated', initMaquinasShowPage);
            document.addEventListener('DOMContentLoaded', initMaquinasShowPage);

            // Cleanup
            document.addEventListener('livewire:navigating', () => {
                document.body.dataset.maquinasShowPageInit = 'false';
                if (window.maquinasShowHandlers.ctx) {
                    document.removeEventListener('contextmenu', window.maquinasShowHandlers.ctx, {
                        capture: true
                    });
                }
                // Shortcuts se limpian en su propia lógica o aquí si tenemos acceso al handler
                if (window.maquinasShowHandlers.keydown) {
                    document.removeEventListener('keydown', window.maquinasShowHandlers.keydown);
                }
            });

            // Función para completar planilla actual
            function completarPlanillaActual() {
                const select1 = document.getElementById('posicion_1');
                const select2 = document.getElementById('posicion_2');

                const pos1 = select1?.value;
                const pos2 = select2?.value;

                // Obtener datos de la opción seleccionada
                const option1 = select1?.selectedOptions[0];
                const option2 = select2?.selectedOptions[0];

                const planillaId1 = option1?.dataset.planillaId || '';
                const planillaCodigo1 = option1?.dataset.planillaCodigo || '';
                const planillaId2 = option2?.dataset.planillaId || '';
                const planillaCodigo2 = option2?.dataset.planillaCodigo || '';

                // Verificar cuántas posiciones válidas hay seleccionadas
                const pos1Valida = pos1 && pos1 !== '0';
                const pos2Valida = pos2 && pos2 !== '0';

                if (!pos1Valida && !pos2Valida) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin planilla seleccionada',
                        text: 'Debes seleccionar una planilla (posición diferente de 0) para completarla',
                        confirmButtonColor: '#3085d6',
                    });
                    return;
                }

                // NUEVA VALIDACIÓN: Solo se puede completar UNA planilla a la vez
                if (pos1Valida && pos2Valida) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selecciona solo una planilla',
                        html: `
                            <p class="mb-2">Para completar una planilla, debes tener <strong>solo una</strong> posición seleccionada.</p>
                            <p class="text-sm text-gray-600">Actualmente tienes seleccionadas:</p>
                            <ul class="text-sm text-left mt-2 ml-4">
                                <li>Posición ${pos1}: <strong>${planillaCodigo1}</strong></li>
                                <li>Posición ${pos2}: <strong>${planillaCodigo2}</strong></li>
                            </ul>
                            <p class="text-sm text-gray-600 mt-3">Cambia una de las posiciones a <strong>0</strong> y vuelve a intentar.</p>
                        `,
                        confirmButtonColor: '#3085d6',
                    });
                    return;
                }

                // Determinar cuál es la planilla a completar
                const posicion = pos1Valida ? pos1 : pos2;
                const planillaId = pos1Valida ? planillaId1 : planillaId2;
                const planillaCodigo = pos1Valida ? planillaCodigo1 : planillaCodigo2;

                // Confirmar acción mostrando el código de la planilla
                Swal.fire({
                    icon: 'question',
                    title: '¿Completar planilla?',
                    html: `
                        <div class="text-center">
                            <p class="text-lg font-semibold text-purple-700 mb-3">${planillaCodigo}</p>
                            <p class="text-sm text-gray-600 mb-2">Posición: ${posicion}</p>
                            <hr class="my-3">
                            <p class="text-sm">Se verificará que todas las etiquetas estén en paquetes y se eliminará la planilla de la cola.</p>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#9333ea',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, completar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mostrar loading
                        Swal.fire({
                            title: 'Completando planilla...',
                            html: `<p class="text-sm text-gray-600">${planillaCodigo}</p>`,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Enviar solicitud con planilla_id para verificación
                        fetch('{{ route('maquinas.completar-planilla', $maquina->id) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    posicion: posicion,
                                    planilla_id: planillaId,
                                    planilla_codigo: planillaCodigo
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Actualizar selects: eliminar planilla completada y reordenar posiciones
                                    actualizarSelectsDespuesDeCompletar(posicion);

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Planilla completada',
                                        text: data.message,
                                        confirmButtonColor: '#9333ea',
                                    }).then(() => {
                                        // Refrescar contenido de etiquetas sin recargar página
                                        if (typeof window.refrescarEtiquetasMaquina === 'function') {
                                            window.refrescarEtiquetasMaquina();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message || 'No se pudo completar la planilla',
                                        confirmButtonColor: '#3085d6',
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error de conexión',
                                    text: 'No se pudo conectar con el servidor',
                                    confirmButtonColor: '#3085d6',
                                });
                            });
                    }
                });
            }

            /**
             * Actualiza los selects después de completar una planilla
             * - Elimina la opción de la posición completada
             * - Reordena las posiciones restantes (las mayores bajan 1)
             * - Pasa a la siguiente planilla disponible (no a 0)
             */
            function actualizarSelectsDespuesDeCompletar(posicionCompletada) {
                const select1 = document.getElementById('posicion_1');
                const select2 = document.getElementById('posicion_2');
                const posNum = parseInt(posicionCompletada);

                [select1, select2].forEach(select => {
                    if (!select) return;

                    const valorActual = parseInt(select.value);
                    const teniaLaPosicionCompletada = valorActual === posNum;

                    // Recorrer opciones y actualizar
                    Array.from(select.options).forEach(option => {
                        const optPos = parseInt(option.value);

                        if (isNaN(optPos) || optPos === 0) return;

                        if (optPos === posNum) {
                            // Eliminar la opción de la posición completada
                            option.remove();
                        } else if (optPos > posNum) {
                            // Bajar posición en 1 para las posteriores
                            const nuevaPos = optPos - 1;
                            const codigo = option.dataset.planillaCodigo || '';
                            option.value = nuevaPos;
                            option.textContent = `${nuevaPos} - ${codigo}`;
                        }
                    });

                    // Si este select tenía la posición completada, pasar a la siguiente disponible
                    if (teniaLaPosicionCompletada) {
                        // Buscar la siguiente opción disponible (la que ahora tiene la misma posición)
                        // Porque las posiciones posteriores bajaron 1
                        const siguienteOpcion = Array.from(select.options).find(opt => {
                            const optPos = parseInt(opt.value);
                            return !isNaN(optPos) && optPos > 0;
                        });

                        if (siguienteOpcion) {
                            select.value = siguienteOpcion.value;
                        } else {
                            // No hay más planillas, ir a 0
                            select.value = '0';
                        }
                    }
                });

                // Disparar cambio para actualizar la vista si es necesario
                if (typeof cambiarPosicionesPlanillas === 'function') {
                    cambiarPosicionesPlanillas();
                }
            }

            // Función para comprimir etiquetas (agrupar hermanos, máx 5 por etiqueta)
            function comprimirEtiquetas() {
                Swal.fire({
                    icon: 'question',
                    title: 'Comprimir etiquetas',
                    html: `
                        <p class="mb-3">Esta acción agrupará los elementos hermanos en las mismas etiquetas.</p>
                        <p class="text-sm text-gray-600">Máximo 5 elementos por etiqueta. Los elementos se agruparán por código padre.</p>
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, comprimir',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Comprimiendo etiquetas...',
                            text: 'Esto puede tardar unos segundos',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 120000); // 2 minutos timeout

                        // Obtener posiciones seleccionadas
                        const pos1 = document.getElementById('posicion_1')?.value;
                        const pos2 = document.getElementById('posicion_2')?.value;
                        const posiciones = [pos1, pos2].filter(p => p && p !== '0').map(p => parseInt(p));

                        fetch('{{ route('maquinas.comprimir-etiquetas', $maquina->id) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    posiciones: posiciones
                                }),
                                signal: controller.signal
                            })
                            .then(response => {
                                clearTimeout(timeoutId);
                                if (!response.ok) {
                                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Compresión completada',
                                        html: `
                                            <p>${data.message}</p>
                                            <div class="mt-3 text-sm text-gray-600">
                                                <p>Elementos procesados: ${data.stats.elementos_procesados}</p>
                                                <p>Movimientos realizados: ${data.stats.movimientos}</p>
                                            </div>
                                        `,
                                        confirmButtonColor: '#6366f1',
                                    });
                                    // Refrescar solo las etiquetas sin recargar la página
                                    if (typeof refrescarEtiquetasMaquina === 'function') {
                                        refrescarEtiquetasMaquina();
                                    }
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message || 'No se pudo comprimir',
                                        confirmButtonColor: '#3085d6',
                                    });
                                }
                            })
                            .catch(error => {
                                clearTimeout(timeoutId);
                                console.error('Error:', error);
                                let mensaje = 'No se pudo conectar con el servidor';
                                if (error.name === 'AbortError') {
                                    mensaje = 'La operación tardó demasiado. Intenta de nuevo o recarga la página.';
                                } else if (error.message) {
                                    mensaje = error.message;
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: mensaje,
                                    confirmButtonColor: '#3085d6',
                                });
                            });
                    }
                });
            }

            // Función para descomprimir etiquetas (1 elemento = 1 etiqueta)
            function descomprimirEtiquetas() {
                Swal.fire({
                    icon: 'question',
                    title: 'Descomprimir etiquetas',
                    html: `
                        <p class="mb-3">Esta acción separará los elementos en etiquetas individuales.</p>
                        <p class="text-sm text-gray-600">Cada elemento tendrá su propia subetiqueta (ETQ001.01, ETQ001.02, etc.)</p>
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, descomprimir',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Descomprimiendo etiquetas...',
                            text: 'Esto puede tardar unos segundos',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 120000); // 2 minutos timeout

                        // Obtener posiciones seleccionadas
                        const pos1 = document.getElementById('posicion_1')?.value;
                        const pos2 = document.getElementById('posicion_2')?.value;
                        const posiciones = [pos1, pos2].filter(p => p && p !== '0').map(p => parseInt(p));

                        fetch('{{ route('maquinas.descomprimir-etiquetas', $maquina->id) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    posiciones: posiciones
                                }),
                                signal: controller.signal
                            })
                            .then(response => {
                                clearTimeout(timeoutId);
                                if (!response.ok) {
                                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Descompresión completada',
                                        html: `
                                            <p>${data.message}</p>
                                            <div class="mt-3 text-sm text-gray-600">
                                                <p>Elementos procesados: ${data.stats.elementos_procesados}</p>
                                                <p>Movimientos realizados: ${data.stats.movimientos}</p>
                                            </div>
                                        `,
                                        confirmButtonColor: '#f59e0b',
                                    });
                                    // Refrescar solo las etiquetas sin recargar la página
                                    if (typeof refrescarEtiquetasMaquina === 'function') {
                                        refrescarEtiquetasMaquina();
                                    }
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message || 'No se pudo descomprimir',
                                        confirmButtonColor: '#3085d6',
                                    });
                                }
                            })
                            .catch(error => {
                                clearTimeout(timeoutId);
                                console.error('Error:', error);
                                let mensaje = 'No se pudo conectar con el servidor';
                                if (error.name === 'AbortError') {
                                    mensaje = 'La operación tardó demasiado. Intenta de nuevo o recarga la página.';
                                } else if (error.message) {
                                    mensaje = error.message;
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: mensaje,
                                    confirmButtonColor: '#3085d6',
                                });
                            });
                    }
                });
            }
        </script>

        {{-- Script para dibujar figuras de elementos (NO necesario para grúa) --}}
        @if ($maquina->tipo !== 'grua')
            <script src="{{ asset('js/elementosJs/figuraElemento.js') }}"></script>
        @endif

        {{-- Script de atajos de teclado para control de columnas --}}
        <script>
            // ============================================================
            // ATAJOS DE FLECHAS - PRIORIDAD ABSOLUTA (fuera de init)
            // ============================================================
            (function() {
                // Evitar registrar múltiples veces
                if (window.__maquinasArrowKeysRegistered) return;
                window.__maquinasArrowKeysRegistered = true;

                // Estados: 'normal' -> 'solo' -> 'fullscreen'
                window.__vistaMode = 'normal';
                window.__fullscreenEtiquetaIndex = 0;
                window.__etiquetasVisibles = [];

                // Crear overlay de pantalla completa (estilos en maquinas-show.css)
                const crearOverlayFullscreen = () => {
                    if (document.getElementById('fullscreen-etiqueta-overlay')) return;

                    const overlay = document.createElement('div');
                    overlay.id = 'fullscreen-etiqueta-overlay';
                    overlay.innerHTML = `
                        <div id="fullscreen-etiqueta-container"></div>
                        <div id="fullscreen-contador">
                            <span class="actual">1</span>
                            <span>/</span>
                            <span class="total">1</span>
                        </div>
                        <div id="fullscreen-instrucciones">
                            <kbd>↑</kbd> Salir &nbsp;|&nbsp; <kbd>Rueda</kbd> Navegar
                        </div>
                    `;
                    document.body.appendChild(overlay);
                };

                // Obtener etiquetas visibles
                const obtenerEtiquetas = () => {
                    const etiquetas = document.querySelectorAll('.etiqueta-card');
                    return Array.from(etiquetas).filter(el => {
                        const rect = el.getBoundingClientRect();
                        return rect.width > 0 && rect.height > 0;
                    });
                };

                // Mostrar etiqueta en fullscreen
                const mostrarEtiquetaFullscreen = (index, direction = 0) => {
                    const container = document.getElementById('fullscreen-etiqueta-container');
                    const contador = document.getElementById('fullscreen-contador');
                    if (!container || !window.__etiquetasVisibles.length) return;

                    // Asegurar índice válido
                    if (index < 0) index = window.__etiquetasVisibles.length - 1;
                    if (index >= window.__etiquetasVisibles.length) index = 0;
                    window.__fullscreenEtiquetaIndex = index;

                    // Animación de cambio
                    container.classList.add('changing');

                    setTimeout(() => {
                        const etiqueta = window.__etiquetasVisibles[index];
                        const clone = etiqueta.cloneNode(true);

                        // Limpiar botones del clone
                        clone.querySelectorAll('.no-print, button, select').forEach(el => el.remove());
                        clone.style.margin = '0';

                        // Escalar el SVG para que ocupe todo el espacio
                        const svg = clone.querySelector('svg');
                        if (svg) {
                            svg.style.width = '100%';
                            svg.style.height = '100%';
                        }

                        container.innerHTML = '';
                        container.appendChild(clone);

                        // Actualizar contador
                        contador.querySelector('.actual').textContent = index + 1;
                        contador.querySelector('.total').textContent = window.__etiquetasVisibles.length;

                        container.classList.remove('changing');
                    }, 150);
                };

                // Activar modo fullscreen
                const activarFullscreen = () => {
                    crearOverlayFullscreen();
                    window.__etiquetasVisibles = obtenerEtiquetas();

                    if (!window.__etiquetasVisibles.length) {
                        return false;
                    }

                    window.__vistaMode = 'fullscreen';
                    window.__fullscreenEtiquetaIndex = 0;

                    const overlay = document.getElementById('fullscreen-etiqueta-overlay');
                    overlay.style.display = 'flex';
                    requestAnimationFrame(() => overlay.classList.add('visible'));

                    mostrarEtiquetaFullscreen(0);
                    document.body.style.overflow = 'hidden';
                    return true;
                };

                // Desactivar modo fullscreen
                const desactivarFullscreen = () => {
                    const overlay = document.getElementById('fullscreen-etiqueta-overlay');
                    if (overlay) {
                        overlay.classList.remove('visible');
                        setTimeout(() => overlay.style.display = 'none', 300);
                    }
                    document.body.style.overflow = '';
                    window.__vistaMode = 'solo';
                };

                // Handler del scroll en fullscreen
                const wheelHandler = (e) => {
                    if (window.__vistaMode !== 'fullscreen') return;

                    e.preventDefault();
                    e.stopPropagation();

                    const direction = e.deltaY > 0 ? 1 : -1;
                    mostrarEtiquetaFullscreen(window.__fullscreenEtiquetaIndex + direction, direction);
                };

                document.addEventListener('wheel', wheelHandler, { passive: false, capture: true });

                // Handler de flechas
                const arrowHandler = function(e) {
                    if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) return;

                    // Permitir en inputs de texto para mover cursor
                    const el = document.activeElement;
                    const isTextInput = el?.tagName === 'INPUT' &&
                        ['text', 'search', 'email', 'password', 'tel', 'url', 'number'].includes(el.type);
                    const isTextarea = el?.tagName === 'TEXTAREA';
                    const isEditable = el?.isContentEditable;

                    if ((isTextInput || isTextarea || isEditable) &&
                        (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    // En modo fullscreen
                    if (window.__vistaMode === 'fullscreen') {
                        if (e.key === 'ArrowUp') {
                            desactivarFullscreen();
                        } else if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                            mostrarEtiquetaFullscreen(window.__fullscreenEtiquetaIndex + 1, 1);
                        } else if (e.key === 'ArrowLeft') {
                            mostrarEtiquetaFullscreen(window.__fullscreenEtiquetaIndex - 1, -1);
                        }
                        return;
                    }

                    // Modos normales
                    switch (e.key) {
                        case 'ArrowLeft':
                            const showL = JSON.parse(localStorage.getItem('showLeft') ?? 'true');
                            localStorage.setItem('showLeft', JSON.stringify(!showL));
                            window.dispatchEvent(new CustomEvent('toggleLeft'));
                            window.__vistaMode = 'normal';
                            break;

                        case 'ArrowRight':
                            const showR = JSON.parse(localStorage.getItem('showRight') ?? 'true');
                            localStorage.setItem('showRight', JSON.stringify(!showR));
                            window.dispatchEvent(new CustomEvent('toggleRight'));
                            window.__vistaMode = 'normal';
                            break;

                        case 'ArrowUp':
                            if (window.__vistaMode === 'solo') {
                                // Volver a normal
                                localStorage.setItem('showLeft', 'true');
                                localStorage.setItem('showRight', 'true');
                                window.dispatchEvent(new CustomEvent('toggleLeft'));
                                window.dispatchEvent(new CustomEvent('toggleRight'));
                                window.showHeader = true;
                                localStorage.setItem('showHeader', 'true');
                                if (window.aplicarEstadoHeader) window.aplicarEstadoHeader();
                                window.__vistaMode = 'normal';
                            } else {
                                if (window.toggleMaquinaHeader) window.toggleMaquinaHeader();
                            }
                            break;

                        case 'ArrowDown':
                            if (window.__vistaMode === 'solo') {
                                // Ya estamos en solo -> ir a fullscreen
                                activarFullscreen();
                            } else {
                                // Normal -> ir a solo
                                window.dispatchEvent(new CustomEvent('solo'));
                                localStorage.setItem('showLeft', 'false');
                                localStorage.setItem('showRight', 'false');
                                window.showHeader = false;
                                localStorage.setItem('showHeader', 'false');
                                if (window.aplicarEstadoHeader) window.aplicarEstadoHeader();
                                window.__vistaMode = 'solo';
                            }
                            break;
                    }
                };

                document.addEventListener('keydown', arrowHandler, { capture: true });
            })();

            // Definir funciones globalmente para acceso
            window.showHeader = JSON.parse(localStorage.getItem('showHeader') ?? 'true');

            window.aplicarEstadoHeader = function() {
                const header = document.querySelector('main header.mb-6');
                if (header) {
                    header.style.display = window.showHeader ? '' : 'none';
                    header.style.transition = 'all 0.2s ease-in-out';
                }
            };

            window.toggleMaquinaHeader = function() {
                window.showHeader = !window.showHeader;
                localStorage.setItem('showHeader', JSON.stringify(window.showHeader));
                window.aplicarEstadoHeader();
            };

            // Migración a patrón de inicialización SPA Livewire
            window.initMaquinasShowPage = function() {
                if (document.body.dataset.maquinasShowPageInit === 'true') return;

                // 1. Inicializar Header
                window.aplicarEstadoHeader();

                // --- Cleanup ---
                document.body.dataset.maquinasShowPageInit = 'true';

                const cleanup = () => {
                    document.body.dataset.maquinasShowPageInit = 'false';
                };

                document.addEventListener('livewire:navigating', cleanup, {
                    once: true
                });
            };

            // Registrar en sistema global
            window.pageInitializers = window.pageInitializers || [];
            window.pageInitializers.push(window.initMaquinasShowPage);

            // Listeners iniciales
            if (typeof Livewire !== 'undefined') {
                document.addEventListener('livewire:navigated', window.initMaquinasShowPage);
            }
            document.addEventListener('DOMContentLoaded', window.initMaquinasShowPage);

            // Ejecutar si ya cargó
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                window.initMaquinasShowPage();
            }
        </script>

</x-app-layout>
