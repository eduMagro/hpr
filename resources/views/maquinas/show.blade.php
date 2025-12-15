<x-app-layout>
    <x-slot name="title">{{ $maquina->nombre }} - {{ config('app.name') }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <strong>{{ $maquina->nombre }}</strong>,
                {{ $usuario1->name }}
                @if ($usuario2)
                    y {{ $usuario2->name }}
                @endif
            </h2>

            <div class="flex flex-wrap items-center gap-4">
                @if ($maquina->tipo !== 'grua')
                    {{-- Selectores de posiciones de planillas --}}
                    <div class="contenedor-selectores-planilla">
                        <select id="posicion_1" name="posicion_1" onchange="cambiarPosicionesPlanillas()">
                            <option value="0" {{ empty($posicion1) ? 'selected' : '' }}>0</option>
                            @foreach ($posicionesDisponibles as $pos)
                                <option value="{{ $pos }}"
                                    {{ $posicion1 == $pos ? 'selected' : '' }}>
                                    {{ $pos }} - {{ $codigosPorPosicion[$pos] ?? '' }}
                                </option>
                            @endforeach
                        </select>

                        <span class="separador">+</span>

                        <select id="posicion_2" name="posicion_2" onchange="cambiarPosicionesPlanillas()">
                            <option value="0" {{ empty($posicion2) ? 'selected' : '' }}>0</option>
                            @foreach ($posicionesDisponibles as $pos)
                                <option value="{{ $pos }}"
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

                    <style>
                        /* Contenedor con layout fijo */
                        .contenedor-selectores-planilla {
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            background: white;
                            border-radius: 6px;
                            padding: 6px 12px;
                            border: 1px solid #d1d5db;
                            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                            /* CRÍTICO: Dimensiones fijas para evitar recalculos */
                            min-width: 280px;
                            height: 40px;
                            box-sizing: border-box;
                            /* Alineación vertical con otros controles */
                            vertical-align: middle;
                            /* NUEVO: Evitar que se mueva por cambios de layout */
                            will-change: auto;
                            contain: layout style;
                        }

                        .contenedor-selectores-planilla select {
                            width: 120px;
                            height: 30px;
                            padding: 4px 8px;
                            border: 1px solid #d1d5db;
                            border-radius: 4px;
                            font-size: 0.8rem;
                            background: white;
                            flex-shrink: 0;
                            /* CRÍTICO: Sin transiciones ni transformaciones */
                            transition: none !important;
                            transform: none !important;
                            box-sizing: border-box;
                            /* NUEVO: Aislar del layout */
                            isolation: isolate;
                            -webkit-appearance: none;
                            -moz-appearance: none;
                            appearance: none;
                            /* Agregar flecha personalizada */
                            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23374151' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
                            background-repeat: no-repeat;
                            background-position: right 8px center;
                            padding-right: 28px;
                        }

                        .contenedor-selectores-planilla select:focus {
                            outline: none !important;
                            border-color: #3b82f6 !important;
                            box-shadow: none !important;
                            /* Mantener dimensiones exactas */
                            width: 120px !important;
                            height: 30px !important;
                        }

                        .contenedor-selectores-planilla select:disabled {
                            opacity: 0.5;
                            cursor: not-allowed;
                        }

                        .contenedor-selectores-planilla .separador {
                            color: #9ca3af;
                            flex-shrink: 0;
                        }

                        .contenedor-selectores-planilla .spinner-loading {
                            flex-shrink: 0;
                        }
                    </style>

                    <script>
                        // Variable global para evitar múltiples ejecuciones simultáneas
                        let cambiarPosicionesTimeout = null;
                        let cambiarPosicionesEnProceso = false;

                        function cambiarPosicionesPlanillas() {
                            // Evitar ejecuciones múltiples si ya está en proceso
                            if (cambiarPosicionesEnProceso) {
                                console.log('⏸️ Cambio de planillas ya en proceso, ignorando...');
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

                        function ejecutarCambioPlanillas() {
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
                                // Resetear el segundo selector
                                document.getElementById('posicion_2').value = '0';
                                return;
                            }

                            // Marcar como en proceso
                            cambiarPosicionesEnProceso = true;

                            // Deshabilitar los selectores mientras se carga
                            const select1 = document.getElementById('posicion_1');
                            const select2 = document.getElementById('posicion_2');
                            const loadingIndicator = document.getElementById('loading-planillas');

                            select1.disabled = true;
                            select2.disabled = true;

                            // Mostrar indicador de carga
                            if (loadingIndicator) {
                                loadingIndicator.style.display = 'inline-block';
                            }

                            console.log('🔄 Cambiando planillas a posiciones:', pos1, pos2);

                            // Construir URL con parámetros (0 = ninguna selección)
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

                            // Actualizar URL sin recargar
                            const newUrl = window.location.pathname + '?' + params.toString();
                            window.history.pushState({}, '', newUrl);

                            // Recargar solo el contenido de planillas
                            fetch(newUrl, {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(response => response.text())
                                .then(html => {
                                    // Crear un documento temporal para parsear el HTML
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(html, 'text/html');

                                    // Obtener el nuevo grid de máquina
                                    const nuevoGrid = doc.getElementById('grid-maquina');
                                    if (nuevoGrid) {
                                        const gridActual = document.getElementById('grid-maquina');
                                        if (gridActual) {
                                            // Ocultar el grid actual
                                            gridActual.style.opacity = '0';
                                            gridActual.style.visibility = 'hidden';

                                            // Después de la animación, reemplazar el contenido
                                            setTimeout(() => {
                                                gridActual.innerHTML = nuevoGrid.innerHTML;

                                                // Actualizar variables globales
                                                const scripts = doc.querySelectorAll('script');
                                                scripts.forEach(script => {
                                                    const content = script.textContent || script.innerText;
                                                    if (content.includes('window.elementosAgrupadosScript') ||
                                                        content.includes('window.etiquetasData') ||
                                                        content.includes('window.pesosElementos') ||
                                                        content.includes('window.SUGERENCIAS')) {
                                                        eval(content);
                                                    }
                                                });

                                                // Actualizar data sources si existe la función
                                                if (window.setDataSources && window.elementosAgrupadosScript) {
                                                    window.setDataSources({
                                                        sugerencias: window.SUGERENCIAS || {},
                                                        elementosAgrupados: window.elementosAgrupadosScript || []
                                                    });
                                                }

                                                // Re-renderizar SVGs
                                                if (window.elementosAgrupadosScript && window.renderizarGrupoSVG) {
                                                    window.elementosAgrupadosScript.forEach((grupo, gidx) => {
                                                        window.renderizarGrupoSVG(grupo, gidx);
                                                    });
                                                }

                                                // Mostrar el grid con animación optimizada
                                                requestAnimationFrame(() => {
                                                    gridActual.style.opacity = '1';
                                                    gridActual.style.visibility = 'visible';

                                                    // Mostrar etiquetas
                                                    document.querySelectorAll('.proceso').forEach(el => {
                                                        el.style.opacity = '1';
                                                    });

                                                    // Re-aplicar clases de columnas después de AJAX
                                                    if (window.updateGridClasses) {
                                                        // Detectar cuántas planillas hay activas contando secciones
                                                        const numPlanillas = gridActual.querySelectorAll(
                                                            '.planilla-section, section.bg-gradient-to-br').length;

                                                        // Actualizar clase dos-planillas / una-planilla
                                                        if (numPlanillas >= 2) {
                                                            gridActual.classList.remove('una-planilla');
                                                            gridActual.classList.add('dos-planillas');
                                                        } else {
                                                            gridActual.classList.remove('dos-planillas');
                                                            gridActual.classList.add('una-planilla');
                                                        }

                                                        const showLeft = JSON.parse(localStorage.getItem('showLeft') ??
                                                            'true');
                                                        const showRight = JSON.parse(localStorage.getItem(
                                                            'showRight') ?? 'true');
                                                        window.updateGridClasses(showLeft, showRight);
                                                    }

                                                    // Re-inicializar event listeners del botón crear paquete
                                                    const btnCrear = document.getElementById("crearPaqueteBtn");
                                                    if (btnCrear && window.TrabajoPaquete && window.TrabajoPaquete
                                                        .crearPaquete) {
                                                        btnCrear.removeEventListener("click", window.TrabajoPaquete
                                                            .crearPaquete);
                                                        btnCrear.addEventListener("click", window.TrabajoPaquete
                                                            .crearPaquete);
                                                        console.log(
                                                            '✅ Event listener del botón crear paquete re-inicializado después de cambio de planillas'
                                                        );
                                                    }
                                                });

                                                // Re-habilitar selectores y ocultar loading
                                                select1.disabled = false;
                                                select2.disabled = false;
                                                if (loadingIndicator) {
                                                    loadingIndicator.style.display = 'none';
                                                }
                                                cambiarPosicionesEnProceso = false;

                                                console.log('✅ Planillas cambiadas correctamente');
                                            }, 100);
                                        }
                                    }
                                })
                                .catch(error => {
                                    console.error('❌ Error al cambiar planillas:', error);

                                    // Re-habilitar selectores y ocultar loading en caso de error
                                    select1.disabled = false;
                                    select2.disabled = false;
                                    if (loadingIndicator) {
                                        loadingIndicator.style.display = 'none';
                                    }
                                    cambiarPosicionesEnProceso = false;

                                    // Si falla, hacer refresh normal
                                    window.location.href = newUrl;
                                });
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
                    }">
                        <button @click="toggleLeft()"
                            class="px-3 py-1.5 rounded-md text-sm font-medium border transition-all duration-200"
                            :class="showLeft ? 'bg-white border-gray-300 text-gray-700 shadow-sm' :
                                'bg-blue-500 border-blue-600 text-white hover:bg-blue-600'"
                            title="Mostrar/Ocultar materia prima">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                </svg>
                                <span x-text="showLeft ? 'Ocultar' : 'Materia'"></span>
                            </span>
                        </button>

                        <button @click="solo()"
                            class="px-3 py-1.5 rounded-md text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-all duration-200 shadow-sm"
                            title="Ver solo planillas">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                Solo Planillas
                            </span>
                        </button>

                        <button @click="toggleRight()"
                            class="px-3 py-1.5 rounded-md text-sm font-medium border transition-all duration-200"
                            :class="showRight ? 'bg-white border-gray-300 text-gray-700 shadow-sm' :
                                'bg-blue-500 border-blue-600 text-white hover:bg-blue-600'"
                            title="Mostrar/Ocultar paquetes">
                            <span class="flex items-center gap-1">
                                <span x-text="showRight ? 'Ocultar' : 'Paquetes'"></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                </svg>
                            </span>
                        </button>

                        {{-- Separador visual --}}
                        <div class="h-6 w-px bg-gray-300 mx-1"></div>

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
                                <span x-text="{
                                    'todos': 'Todas',
                                    'sin-paquete': 'Sin paquete',
                                    'en-paquete': 'En paquete',
                                    'pendiente': 'Pendientes',
                                    'fabricando': 'Fabricando',
                                    'completada': 'Completadas'
                                }[filtroEstado]"></span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
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
                        <button type="button" onclick="resumirEtiquetasMaquina()"
                            class="px-3 py-2 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-1"
                            title="Resumir: Agrupa etiquetas con mismo diámetro y dimensiones (mantiene originales para imprimir)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Resumir
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

                <form method="POST" action="{{ route('turno.cambiarMaquina') }}">
                    @csrf
                    <input type="hidden" name="asignacion_id" value="{{ $turnoHoy->id ?? '' }}">

                    <div class="relative">
                        <select name="nueva_maquina_id" onchange="this.form.submit()"
                            class="appearance-none bg-white border-2 border-gray-300 hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg px-4 py-2 pr-10 text-sm font-medium text-gray-700 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer">
                            @foreach ($maquinas as $m)
                                <option value="{{ $m->id }}"
                                    {{ $m->id == ($turnoHoy->maquina_id ?? $maquina->id) ? 'selected' : '' }}>
                                    {{ $m->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </form>
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
                    {{-- @elseif ($maquina->tipo === 'dobladora_manual')
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                        <x-maquinas.tipo.tipo-dobladora-manual :maquina="$maquina" :maquinas="$maquinas" :elementosAgrupados="$elementosAgrupados"
                            :productosBaseCompatibles="$productosBaseCompatibles" />
                    </div>
                @elseif ($maquina->tipo === 'cortadora_manual')
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                        <x-maquinas.tipo.tipo-cortadora-manual :maquina="$maquina" :maquinas="$maquinas" :elementosAgrupados="$elementosAgrupados"
                            :productosBaseCompatibles="$productosBaseCompatibles" />
                    </div> --}}
                @else
                    <x-maquinas.tipo.tipo-normal :maquina="$maquina" :maquinas="$maquinas" :elementos-agrupados="$elementosAgrupados" :productos-base-compatibles="$productosBaseCompatibles"
                        :producto-base-solicitados="$productoBaseSolicitados" :planillas-activas="$planillasActivas" :elementos-por-planilla="$elementosPorPlanilla" :es-barra="$esBarra" :longitudes-por-diametro="$longitudesPorDiametro"
                        :diametro-por-etiqueta="$diametroPorEtiqueta" :elementos-agrupados-script="$elementosAgrupadosScript" :posiciones-disponibles="$posicionesDisponibles" :posicion1="$posicion1"
                        :posicion2="$posicion2" :grupos-resumen="$gruposResumen ?? collect()" :etiquetas-en-grupos="$etiquetasEnGrupos ?? []" />

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

        <script>
            window.SUGERENCIAS = @json($sugerenciasPorElemento ?? []);
            window.elementosAgrupadosScript = @json($elementosAgrupadosScript ?? null);
            window.gruposResumenData = @json($gruposResumen ?? []);
            window.etiquetasEnGrupos = @json($etiquetasEnGrupos ?? []);
            window.rutaDividirElemento = "{{ route('elementos.dividir') }}";

            window.etiquetasData = @json($etiquetasData);
            window.pesosElementos = @json($pesosElementos);
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
                    console.log('🔄 Refrescando etiquetas...');

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
                                eval(content);
                            } catch (e) {
                                console.warn('Error al evaluar script:', e);
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

                    // Re-inicializar event listeners del botón crear paquete
                    const btnCrear = document.getElementById("crearPaqueteBtn");
                    if (btnCrear && window.TrabajoPaquete && window.TrabajoPaquete.crearPaquete) {
                        // Remover listener anterior si existe
                        btnCrear.replaceWith(btnCrear.cloneNode(true));
                        const btnCrearNuevo = document.getElementById("crearPaqueteBtn");
                        btnCrearNuevo.addEventListener("click", window.TrabajoPaquete.crearPaquete);
                        console.log('✅ Event listener del botón crear paquete re-inicializado');
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
                                console.log('🎨 Re-renderizando', window.elementosAgrupadosScript.length, 'etiquetas individuales...');
                                window.elementosAgrupadosScript.forEach((grupo, gidx) => {
                                    window.renderizarGrupoSVG(grupo, gidx);
                                });
                            }

                            // Re-renderizar SVGs de grupos de resumen (usando data attributes del DOM)
                            const gruposResumenCards = document.querySelectorAll('.grupo-resumen-card');
                            if (gruposResumenCards.length > 0 && window.renderizarGrupoSVG) {
                                console.log('🎨 Re-renderizando', gruposResumenCards.length, 'grupos de resumen desde DOM...');
                                gruposResumenCards.forEach((card) => {
                                    const contenedorSvgId = card.dataset.contenedorSvgId;
                                    const grupoId = card.dataset.grupoId;
                                    let elementos = [];
                                    try {
                                        elementos = JSON.parse(card.dataset.elementos || '[]');
                                    } catch (e) {
                                        console.warn('Error parsing elementos:', e);
                                    }

                                    if (contenedorSvgId && elementos.length > 0) {
                                        const grupoData = {
                                            id: parseInt(contenedorSvgId),
                                            etiqueta: { id: parseInt(contenedorSvgId) },
                                            elementos: elementos
                                        };
                                        window.renderizarGrupoSVG(grupoData, parseInt(grupoId));
                                    }
                                });
                            }
                        }, 50); // Pequeño delay para asegurar que el DOM esté completamente actualizado
                    });

                    console.log('✅ Etiquetas refrescadas correctamente');

                    // Re-aplicar filtro de estado después de refrescar
                    const filtroActual = localStorage.getItem('filtroEstadoEtiqueta') ?? 'todos';
                    window.aplicarFiltroEstadoEtiquetas(filtroActual);

                } catch (error) {
                    console.error('❌ Error al refrescar etiquetas:', error);
                    // Si falla, recargar la página como fallback
                    console.warn('Recargando página como fallback...');
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

                console.log(`🔍 Filtro aplicado: ${estado}`);
            };

            // Escuchar cambios en el filtro de estado
            window.addEventListener('filtroEstadoChanged', function(e) {
                window.aplicarFiltroEstadoEtiquetas(e.detail);
            });

            // Aplicar filtro inicial al cargar la página
            document.addEventListener('DOMContentLoaded', function() {
                const filtroInicial = localStorage.getItem('filtroEstadoEtiqueta') ?? 'todos';
                setTimeout(() => {
                    window.aplicarFiltroEstadoEtiquetas(filtroInicial);
                }, 500); // Esperar a que se rendericen las etiquetas
            });
        </script>

        <!-- ✅ Vite: Bundle de máquinas -->
        @vite(['resources/js/maquinaJS/maquina-bundle.js'])
        <script src="{{ asset('js/maquinaJS/sl28/cortes.js') }}?v={{ time() }}"></script>
        {{-- <script src="{{ asset('js/maquinaJS/crearPaquetes.js') }}" defer></script> --}}
        {{-- Al final del archivo Blade --}}

        <script>
            // Bloquea el menú contextual solo dentro de .proceso (tu tarjeta de etiqueta)
            document.addEventListener('contextmenu', function(e) {
                if (e.target.closest('.proceso')) {
                    e.preventDefault();
                }
            }, {
                capture: true
            });

            // Validación de posiciones de planillas en el header (compatible con Livewire Navigate)
            function initValidacionPosicionesPlanillasHeader() {
                const form = document.getElementById('form-posiciones-planillas-header');
                if (!form || form.dataset.validacionInit === '1') return;
                form.dataset.validacionInit = '1';

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
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initValidacionPosicionesPlanillasHeader);
            } else {
                initValidacionPosicionesPlanillasHeader();
            }
            document.addEventListener('livewire:navigated', initValidacionPosicionesPlanillasHeader);

            // Función para completar planilla actual
            function completarPlanillaActual() {
                const pos1 = document.getElementById('posicion_1')?.value;
                const pos2 = document.getElementById('posicion_2')?.value;

                if (!pos1 && !pos2) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin planilla seleccionada',
                        text: 'Debes seleccionar al menos una posición de planilla para completarla',
                        confirmButtonColor: '#3085d6',
                    });
                    return;
                }

                // Confirmar acción
                Swal.fire({
                    icon: 'question',
                    title: '¿Completar planilla?',
                    html: `
                        <p class="mb-3">Se verificará que todas las etiquetas estén en paquetes y se eliminará la planilla de la cola.</p>
                        <p class="text-sm text-gray-600">Posición${pos2 ? 'es' : ''}: ${pos1}${pos2 ? ` y ${pos2}` : ''}</p>
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
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Enviar solicitud
                        fetch('{{ route('maquinas.completar-planilla', $maquina->id) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    posicion_1: pos1 || null,
                                    posicion_2: pos2 || null
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '¡Planilla completada!',
                                        text: data.message,
                                        confirmButtonColor: '#9333ea',
                                    }).then(() => {
                                        // Recargar la página para actualizar las posiciones
                                        window.location.reload();
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
                                body: JSON.stringify({ posiciones: posiciones }),
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
                                body: JSON.stringify({ posiciones: posiciones }),
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

            // Función para resumir etiquetas de la máquina actual
            function resumirEtiquetasMaquina() {
                // Obtener las planillas activas (por posición seleccionada)
                const pos1 = document.getElementById('posicion_1')?.value;
                const pos2 = document.getElementById('posicion_2')?.value;

                // Si hay planillas activas definidas en el scope
                const planillasActivas = @json($planillasActivas ?? []);

                if (planillasActivas.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin planillas',
                        text: 'No hay planillas activas en esta máquina para resumir',
                    });
                    return;
                }

                // Si hay una sola planilla, usar esa directamente
                if (planillasActivas.length === 1) {
                    resumirEtiquetas(planillasActivas[0].id, {{ $maquina->id }});
                    return;
                }

                // Si hay múltiples planillas, preguntar cuál resumir
                const opcionesHtml = planillasActivas.map(p =>
                    `<option value="${p.id}">${p.codigo} (${p.peso_total || 0} kg)</option>`
                ).join('');

                Swal.fire({
                    icon: 'question',
                    title: 'Seleccionar planilla',
                    html: `
                        <p class="mb-3">Selecciona la planilla a resumir:</p>
                        <select id="swal-planilla-select" class="w-full border rounded px-3 py-2">
                            <option value="todas">Todas las planillas</option>
                            ${opcionesHtml}
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#14b8a6',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        return document.getElementById('swal-planilla-select').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value === 'todas') {
                            // Resumir todas las planillas una por una
                            planillasActivas.forEach(p => {
                                resumirEtiquetas(p.id, {{ $maquina->id }});
                            });
                        } else {
                            resumirEtiquetas(parseInt(result.value), {{ $maquina->id }});
                        }
                    }
                });
            }
        </script>

        {{-- Script para dibujar figuras de elementos --}}
        <script src="{{ asset('js/elementosJs/figuraElemento.js') }}"></script>

        {{-- Script del sistema de resumen de etiquetas --}}
        <script src="{{ asset('js/resumir-etiquetas.js') }}"></script>

</x-app-layout>
