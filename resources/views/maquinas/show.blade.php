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
                        <label>📋 Planillas:</label>

                        <select id="posicion_1" name="posicion_1" onchange="cambiarPosicionesPlanillas()">
                            <option value="">-- Pos. 1 --</option>
                            @foreach ($posicionesDisponibles as $pos)
                                <option value="{{ $pos }}"
                                    {{ request('posicion_1') == $pos ? 'selected' : '' }}>
                                    Pos. {{ $pos }}
                                </option>
                            @endforeach
                        </select>

                        <span class="separador">+</span>

                        <select id="posicion_2" name="posicion_2" onchange="cambiarPosicionesPlanillas()">
                            <option value="">-- Pos. 2 --</option>
                            @foreach ($posicionesDisponibles as $pos)
                                <option value="{{ $pos }}"
                                    {{ request('posicion_2') == $pos ? 'selected' : '' }}>
                                    Pos. {{ $pos }}
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
                            min-width: 320px;
                            height: 40px;
                            box-sizing: border-box;
                            /* Alineación vertical con otros controles */
                            vertical-align: middle;
                            /* NUEVO: Evitar que se mueva por cambios de layout */
                            will-change: auto;
                            contain: layout style;
                        }

                        .contenedor-selectores-planilla label {
                            font-size: 0.875rem;
                            font-weight: 500;
                            color: #374151;
                            white-space: nowrap;
                            flex-shrink: 0;
                            margin: 0;
                        }

                        .contenedor-selectores-planilla select {
                            width: 90px;
                            height: 30px;
                            padding: 4px 8px;
                            border: 1px solid #d1d5db;
                            border-radius: 4px;
                            font-size: 0.875rem;
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
                            width: 90px !important;
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

                            // Validar que no sean la misma posición
                            if (pos1 && pos2 && pos1 === pos2) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Posiciones duplicadas',
                                    text: 'No puedes seleccionar la misma posición dos veces',
                                    confirmButtonColor: '#3085d6',
                                });
                                // Resetear el segundo selector
                                document.getElementById('posicion_2').value = '';
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

                            // Construir URL con parámetros
                            const params = new URLSearchParams(window.location.search);
                            if (pos1) {
                                params.set('posicion_1', pos1);
                            } else {
                                params.delete('posicion_1');
                            }
                            if (pos2) {
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
                                                            'false');
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
                        showLeft: JSON.parse(localStorage.getItem('showLeft') ?? 'false'),
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
                    </div>

                    {{-- Botón Exportar BVBs para MSR20 --}}
                    @if (strtoupper($maquina->nombre) === 'MSR20')
                        <a href="{{ route('maquinas.exportar-bvbs', $maquina->id) }}" wire:navigate
                            class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2"
                            title="Exportar BVBs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Exportar BVBs
                        </a>
                    @endif

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

                <form method="POST" action="{{ route('turno.cambiarMaquina') }}" class="flex items-center gap-3">
                    @csrf
                    <input type="hidden" name="asignacion_id" value="{{ $turnoHoy->id ?? '' }}">

                    <div class="relative">
                        <select name="nueva_maquina_id"
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

                    <button type="submit"
                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        Cambiar máquina
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="w-full sm:px-4">
        <!-- Grid principal -->
        <div class="w-full">
            @if ($maquina->tipo === 'grua')
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
                        :posicion2="$posicion2" />

                    @include('components.maquinas.modales.normal.modales-normal')
            @endif

        </div>

        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

        <script>
            window.SUGERENCIAS = @json($sugerenciasPorElemento ?? []);
            window.elementosAgrupadosScript = @json($elementosAgrupadosScript ?? null);
            window.rutaDividirElemento = "{{ route('elementos.dividir') }}";
            window.etiquetasData = @json($etiquetasData);
            window.pesosElementos = @json($pesosElementos);
            window.maquinaId = @json($maquina->id);
            window.tipoMaquina = @json($maquina->tipo_material); // 👈 Añadido
            window.ubicacionId = @json(optional($ubicacion)->id);
            console.log('etiquetasData', window.etiquetasData);

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

                    // Re-renderizar SVGs
                    if (window.elementosAgrupadosScript && window.renderizarGrupoSVG) {
                        window.elementosAgrupadosScript.forEach((grupo, gidx) => {
                            window.renderizarGrupoSVG(grupo, gidx);
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

                    // Animación de entrada
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

                            const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'false');
                            const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
                            window.updateGridClasses(showLeft, showRight);
                        }
                    });

                    console.log('✅ Etiquetas refrescadas correctamente');

                } catch (error) {
                    console.error('❌ Error al refrescar etiquetas:', error);
                    // Si falla, recargar la página como fallback
                    console.warn('Recargando página como fallback...');
                    window.location.reload();
                }
            };
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

            // Validación de posiciones de planillas en el header
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('form-posiciones-planillas-header');
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
        </script>

</x-app-layout>
