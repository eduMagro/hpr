<x-app-layout>
    <x-slot name="title">Planificación Salidas</x-slot>

    <x-page-header
        title="Planificación de Salidas"
        subtitle="Programación y seguimiento de expediciones"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'
    />

    <div class="px-4 transition-colors" id="planificacion-container">
        <div class="max-w-[1800px] mx-auto">
            <!-- Sección de Filtros y Resúmenes - Compacta -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-3 mb-4 transition-colors">
                <div class="flex flex-wrap items-start gap-3">
                    <!-- Filtros -->
                    <div class="flex flex-wrap items-center gap-2 flex-1">
                        <!-- Cliente -->
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Cliente:</span>
                            <input id="filtro-cod-cliente" type="text" placeholder="Cód."
                                class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400 rounded px-2 py-1 text-xs w-16 focus:ring-purple-500 focus:border-purple-500"
                                autocomplete="off" />
                            <input id="filtro-cliente" type="text" placeholder="Nombre"
                                class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400 rounded px-2 py-1 text-xs w-24 focus:ring-purple-500 focus:border-purple-500"
                                autocomplete="off" />
                        </div>

                        <div class="h-5 w-px bg-gray-300 dark:bg-gray-600 hidden sm:block"></div>

                        <!-- Obra -->
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Obra:</span>
                            <input id="filtro-obra" type="text" placeholder="Cód."
                                class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400 rounded px-2 py-1 text-xs w-16 focus:ring-purple-500 focus:border-purple-500"
                                autocomplete="off" />
                            <input id="filtro-nombre-obra" type="text" placeholder="Nombre"
                                class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400 rounded px-2 py-1 text-xs w-24 focus:ring-purple-500 focus:border-purple-500"
                                autocomplete="off" />
                        </div>

                        <div class="h-5 w-px bg-gray-300 dark:bg-gray-600 hidden sm:block"></div>

                        <!-- Planilla -->
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Planilla:</span>
                            <input id="filtro-cod-planilla" type="text" placeholder="Cód."
                                class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400 rounded px-2 py-1 text-xs w-20 focus:ring-purple-500 focus:border-purple-500"
                                autocomplete="off" />
                        </div>

                        <div class="h-5 w-px bg-gray-300 dark:bg-gray-600 hidden sm:block"></div>

                        <!-- Checkboxes -->
                        <div class="flex items-center gap-2">
                            <label class="flex items-center text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" id="solo-salidas"
                                    class="rounded border-gray-300 dark:border-gray-600 text-green-600 focus:ring-green-500 h-3.5 w-3.5 mr-1" />
                                Salidas
                            </label>
                            <label class="flex items-center text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" id="solo-planillas"
                                    class="rounded border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500 h-3.5 w-3.5 mr-1" />
                                Planillas
                            </label>
                        </div>

                        <!-- Reset -->
                        <button type="button"
                            class="bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-500 text-white p-1.5 rounded transition"
                            id="btn-reset-filtros" title="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                            </svg>
                        </button>
                    </div>

                    <!-- Separador vertical -->
                    <div class="h-8 w-px bg-gray-200 dark:bg-gray-600 hidden lg:block"></div>

                    <!-- Resúmenes inline -->
                    <div class="flex items-center gap-4 text-xs">
                        <!-- Semanal -->
                        <div class="flex items-center gap-2 px-2.5 py-1.5 bg-slate-100 dark:bg-slate-700/50 rounded border border-slate-200 dark:border-slate-600">
                            <span class="font-semibold text-slate-600 dark:text-slate-300">Semanal</span>
                            <span id="resumen-semanal-fecha" class="text-slate-400 dark:text-slate-500"></span>
                            <span id="resumen-semanal-peso" class="text-slate-700 dark:text-slate-200 font-medium">0 kg</span>
                            <span id="resumen-semanal-longitud" class="text-slate-700 dark:text-slate-200 font-medium">0 m</span>
                            <span id="resumen-semanal-diametro" class="text-slate-700 dark:text-slate-200 font-medium"></span>
                        </div>
                        <!-- Mensual -->
                        <div class="flex items-center gap-2 px-2.5 py-1.5 bg-slate-100 dark:bg-slate-700/50 rounded border border-slate-200 dark:border-slate-600">
                            <span class="font-semibold text-slate-600 dark:text-slate-300">Mensual</span>
                            <span id="resumen-mensual-fecha" class="text-slate-400 dark:text-slate-500"></span>
                            <span id="resumen-mensual-peso" class="text-slate-700 dark:text-slate-200 font-medium">0 kg</span>
                            <span id="resumen-mensual-longitud" class="text-slate-700 dark:text-slate-200 font-medium">0 m</span>
                            <span id="resumen-mensual-diametro" class="text-slate-700 dark:text-slate-200 font-medium"></span>
                        </div>
                    </div>
                </div>

                <!-- Resultados de búsqueda (se muestran debajo cuando hay coincidencias) -->
                <div class="flex flex-wrap gap-2 mt-2 empty:mt-0">
                    <div id="clientes-encontrados" class="hidden p-2 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded text-xs flex-1 min-w-[200px]">
                        <p class="font-semibold text-blue-700 dark:text-blue-300 mb-1">Clientes:</p>
                        <div id="clientes-encontrados-lista" class="flex flex-col gap-1"></div>
                    </div>
                    <div id="obras-encontradas" class="hidden p-2 bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-700 rounded text-xs flex-1 min-w-[200px]">
                        <p class="font-semibold text-purple-700 dark:text-purple-300 mb-1">Obras:</p>
                        <div id="obras-encontradas-lista" class="flex flex-wrap gap-1"></div>
                    </div>
                    <div id="planillas-encontradas" class="hidden p-2 bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-700 rounded text-xs flex-1 min-w-[200px]">
                        <p class="font-semibold text-purple-700 dark:text-purple-300 mb-1">Planillas:</p>
                        <div id="planillas-encontradas-lista" class="flex flex-wrap gap-1"></div>
                    </div>
                </div>
            </div>

            <!-- Calendario -->
            <div class="relative" id="calendario-wrapper">
                <!-- Spinner de carga -->
                <div id="calendario-loading" class="absolute inset-0 bg-slate-100/80 dark:bg-gray-900/80 backdrop-blur-sm z-20 flex items-center justify-center transition-opacity duration-300 rounded-lg">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-10 h-10 border-3 border-purple-200 dark:border-purple-800 rounded-full animate-spin border-t-purple-600 dark:border-t-purple-400"></div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400" id="loading-text">Cargando...</span>
                    </div>
                </div>

                <!-- Botones flotantes -->
                <div class="absolute top-2 right-2 z-10 flex gap-1.5">
                    <button onclick="abrirVentanaLogsS()" id="logs-btn-salidas" title="Ver historial de cambios"
                        class="p-1.5 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400 text-white rounded transition-colors shadow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </button>
                    <button onclick="toggleFullScreen()" id="fullscreen-btn" title="Pantalla completa"
                        class="p-1.5 bg-gray-700 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded transition-colors shadow">
                        <svg id="fullscreen-icon-expand" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                        </svg>
                        <svg id="fullscreen-icon-collapse" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"></path>
                        </svg>
                    </button>
                </div>

                <div id="calendario" data-calendar-type="salidas" class="min-h-[82vh] w-full"></div>
            </div>
        </div>
    </div>

    @php
        $cfg = [
            'csrf' => csrf_token(),
            'routes' => [
                'planificacion' => url('/planificacion'),
                'informacionPaquetesSalida' => route('planificacion.informacionPaquetesSalida'),
                'guardarPaquetesSalida' => route('planificacion.guardarPaquetesSalida'),
                'comentario' => url('/planificacion/comentario/__ID__'),
                'empresaTransporte' => url('/planificacion/empresa-transporte/__ID__'),
                // para update por drag&drop: PUT /planificacion/{id}
                'updateItem' => url('/planificacion/__ID__'),
                'totales' => url('/planificacion/totales'), // GET ?fecha=YYYY-MM-DD
                'salidasCreate' => route('salidas-ferralla.create'),
                // 📅 nuevas rutas para cambiar fechas de entrega
                'informacionPlanillas' => route('planillas.editarInformacionMasiva'), // GET ?ids=1,2,3
                'actualizarFechasPlanillas' => route('planillas.editarActualizarFechasMasiva'), // PUT JSON
                // Ruta automatizarSalidas eliminada - ahora es automático via PaqueteObserver
            ],
            'camiones' => $camiones ?? [],
            'empresasTransporte' => $empresasTransporte ?? [],
            // si quieres precargar eventos/resources por servidor, podrías añadirlos aquí:
            // 'maquinas' => $maquinas ?? [],
            // 'eventos'  => $eventos ?? [],
        ];
    @endphp

    <!-- ✅ FullCalendar Scheduler completo con vista resourceTimelineWeek -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    {{-- TOOLTIP --}}
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light.css" />

    <!-- ✅ Vite: Estilos y módulo principal del calendario (incluye calendario-menu.js) -->
    @vite(['resources/css/estilosCalendarioSalidas.css', 'resources/js/modules/calendario-salidas/index.js'])

    <!-- Script para dibujar figuras de elementos -->
    <script src="{{ asset('js/elementosJs/figuraElemento.js') . '?v=' . time() }}"></script>

    {{-- Tu config global ANTES de @vite --}}
    <script>
        window.initPlanificacionPage = function() {
            // Protección contra doble inicialización
            if (document.body.dataset.planificacionPageInit === 'true') return;
            console.log('Inicializando Planificación Page');

            // 1) Asegura el objeto global sin pisarlo
            window.AppSalidas = window.AppSalidas || {};

            // 2) Mezcla (merge) la config del backend SIN reemplazar el objeto entero
            Object.assign(window.AppSalidas, @json($cfg));

            // 3) Asegura que routes existe y añade solo la nueva clave
            window.AppSalidas.routes = window.AppSalidas.routes || {};
            window.AppSalidas.routes.codigoSage = @json(route('salidas.editarCodigoSage', ['salida' => '__ID__']));

            // 4) Sistema de pantalla completa
            window.isFullScreen = window.isFullScreen || false;

            // Definimos funciones en window para que el onclick del HTML funcione
            window.handleEscKey = function(e) {
                if (e.key === 'Escape' && window.isFullScreen) {
                    window.toggleFullScreen();
                }
            };

            window.toggleFullScreen = function() {
                const container = document.getElementById('planificacion-container');
                const sidebar = document.querySelector('[class*="sidebar"]') || document.querySelector('aside');
                const topNavigation = document.getElementById('top-navigation');
                const mainLayout = document.getElementById('mainlayout');
                const breadcrumbs = document.querySelector('[class*="breadcrumb"]');
                const expandIcon = document.getElementById('fullscreen-icon-expand');
                const collapseIcon = document.getElementById('fullscreen-icon-collapse');
                const fullscreenBtn = document.getElementById('fullscreen-btn');
                const calendarioWrapper = document.getElementById('calendario-wrapper');
                const calendarioEl = document.getElementById('calendario');
                // Sección de filtros y resúmenes (primer hijo directo del max-w container)
                const filtrosSection = container?.querySelector('.max-w-\\[1800px\\] > .bg-white.p-6.mb-6');
                const maxWContainer = container?.querySelector('.max-w-\\[1800px\\]');

                // Si contenedor no existe (navegamos fuera), no hacemos nada
                if (!container) return;

                if (!window.isFullScreen) {
                    // Entrar en pantalla completa - SOLO calendario
                    if (sidebar) sidebar.style.display = 'none';
                    if (topNavigation) topNavigation.style.display = 'none';
                    if (breadcrumbs) breadcrumbs.style.display = 'none';
                    if (filtrosSection) filtrosSection.style.display = 'none';

                    // Quitar padding del main layout
                    if (mainLayout) {
                        mainLayout.style.padding = '0';
                        mainLayout.style.margin = '0';
                    }

                    // z-[70] para estar por encima del top-navigation (z-[60])
                    // Detectar si está en modo dark para usar el color de fondo correcto
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    container.classList.add('fixed', 'inset-0');
                    container.classList.add(isDarkMode ? 'bg-gray-900' : 'bg-gray-50');
                    container.style.zIndex = '70';
                    container.classList.remove('px-4');
                    container.style.padding = '0';
                    container.style.overflow = 'hidden';

                    // Quitar límite de ancho del contenedor
                    if (maxWContainer) {
                        maxWContainer.style.maxWidth = '100%';
                        maxWContainer.style.height = '100%';
                        maxWContainer.style.display = 'flex';
                        maxWContainer.style.flexDirection = 'column';
                    }

                    // Hacer que el calendario ocupe todo el espacio
                    if (calendarioWrapper) {
                        calendarioWrapper.style.flex = '1';
                        calendarioWrapper.style.height = '100vh';
                        calendarioWrapper.style.borderRadius = '0';
                        calendarioWrapper.style.border = 'none';
                        calendarioWrapper.style.margin = '0';
                    }

                    if (calendarioEl) {
                        calendarioEl.style.minHeight = 'calc(100vh - 60px)';
                        calendarioEl.style.height = 'calc(100vh - 60px)';
                    }

                    if (expandIcon) expandIcon.classList.add('hidden');
                    if (collapseIcon) collapseIcon.classList.remove('hidden');
                    if (fullscreenBtn) fullscreenBtn.title = 'Salir de pantalla completa';

                    window.isFullScreen = true;

                    // Atajo de teclado ESC para salir
                    document.addEventListener('keydown', window.handleEscKey);
                } else {
                    // Salir de pantalla completa
                    if (sidebar) sidebar.style.display = '';
                    if (topNavigation) topNavigation.style.display = '';
                    if (breadcrumbs) breadcrumbs.style.display = '';
                    if (filtrosSection) filtrosSection.style.display = '';

                    // Restaurar padding del main layout
                    if (mainLayout) {
                        mainLayout.style.padding = '';
                        mainLayout.style.margin = '';
                    }

                    container.classList.remove('fixed', 'inset-0', 'bg-gray-50', 'bg-gray-900');
                    container.style.zIndex = '';
                    container.classList.add('px-4');
                    container.style.padding = '';
                    container.style.overflow = '';

                    // Restaurar límite de ancho
                    if (maxWContainer) {
                        maxWContainer.style.maxWidth = '';
                        maxWContainer.style.height = '';
                        maxWContainer.style.display = '';
                        maxWContainer.style.flexDirection = '';
                    }

                    // Restaurar estilos del calendario
                    if (calendarioWrapper) {
                        calendarioWrapper.style.flex = '';
                        calendarioWrapper.style.height = '';
                        calendarioWrapper.style.borderRadius = '';
                        calendarioWrapper.style.border = '';
                        calendarioWrapper.style.margin = '';
                    }

                    if (calendarioEl) {
                        calendarioEl.style.minHeight = '';
                        calendarioEl.style.height = '';
                    }

                    if (expandIcon) expandIcon.classList.remove('hidden');
                    if (collapseIcon) collapseIcon.classList.add('hidden');
                    if (fullscreenBtn) fullscreenBtn.title = 'Pantalla completa';

                    window.isFullScreen = false;

                    document.removeEventListener('keydown', window.handleEscKey);
                }

                // Re-renderizar el calendario para ajustar su tamaño
                if (window.calendar) {
                    setTimeout(() => {
                        window.calendar.updateSize();
                    }, 100);
                }
            };

            // También permitir F11 como alternativa (opcional)
            const handleF11 = function(e) {
                if (e.key === 'F11') {
                    e.preventDefault();
                    window.toggleFullScreen();
                }
            };
            document.addEventListener('keydown', handleF11);

            // Listener para redimensionar calendario cuando se toggle el sidebar
            const handleSidebarToggle = function() {
                if (window.calendar) {
                    // Pequeño delay para esperar la animación del sidebar
                    setTimeout(() => {
                        window.calendar.updateSize();
                    }, 300);
                }
            };
            window.addEventListener('sidebar-toggled', handleSidebarToggle);

            // (opcional) deja activado el nuevo menú sin romper nada
            window.AppSalidas.useNewMenu = true;

            // --- Cleanup ---
            document.body.dataset.planificacionPageInit = 'true';

            const cleanup = () => {
                document.removeEventListener('keydown', handleF11);
                window.removeEventListener('sidebar-toggled', handleSidebarToggle);
                // Asegurarse de quitar ESC si nos vamos en modo fullscreen
                document.removeEventListener('keydown', window.handleEscKey);

                // Si nos vamos en modo fullscreen, intentar restaurar estilos podría ser difícil ya que los elementos se van a destruir,
                // pero la variable global window.isFullScreen debería resetearse para la próxima vez?
                // O se mantendrá en true? Mejor falsearla.
                window.isFullScreen = false;

                document.body.dataset.planificacionPageInit = 'false';
            };

            document.addEventListener('livewire:navigating', cleanup, {
                once: true
            });
        };

        // Registrar en sistema global
        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(window.initPlanificacionPage);

        // Listeners iniciales
        if (typeof Livewire !== 'undefined') {
            document.addEventListener('livewire:navigated', window.initPlanificacionPage);
        }
        document.addEventListener('DOMContentLoaded', window.initPlanificacionPage);

        // Ejecución inmediata
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            window.initPlanificacionPage();
        }
    </script>

    {{-- Búsqueda de planillas para mostrar badges informativos --}}
    <script>
        // Función global para navegar a una fecha en el calendario
        window.irAFechaCalendario = function(fechaISO) {
            if (!fechaISO) return;

            if (window.calendar && typeof window.calendar.gotoDate === 'function') {
                window.calendar.gotoDate(fechaISO);

                setTimeout(() => {
                    // Buscar la celda del día específico en el calendario
                    const dayCell = document.querySelector(`[data-date="${fechaISO}"]`) ||
                                   document.querySelector(`.fc-day[data-date="${fechaISO}"]`) ||
                                   document.querySelector(`td[data-date="${fechaISO}"]`);

                    if (dayCell) {
                        dayCell.scrollIntoView({behavior: 'smooth', block: 'center'});
                        // Resaltar brevemente la celda
                        dayCell.style.transition = 'background-color 0.3s';
                        dayCell.style.backgroundColor = '#c4b5fd';
                        setTimeout(() => {
                            dayCell.style.backgroundColor = '';
                        }, 1500);
                    } else {
                        // Si no encuentra la celda, al menos scroll al calendario
                        document.getElementById('calendario')?.scrollIntoView({behavior: 'smooth', block: 'start'});
                    }
                }, 200);
            } else {
                console.error('Calendario no disponible');
            }
        };

        (function() {
            function initBuscadorPlanillas() {
                const inputPlanilla = document.getElementById('filtro-cod-planilla');
                const containerResultados = document.getElementById('planillas-encontradas');
                const listaResultados = document.getElementById('planillas-encontradas-lista');

                if (!inputPlanilla || !containerResultados || !listaResultados) return;

                // Evitar doble inicialización
                if (inputPlanilla.dataset.buscadorInit === 'true') return;
                inputPlanilla.dataset.buscadorInit = 'true';

                let debounceTimer = null;

                inputPlanilla.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    const codigo = this.value.trim();

                    if (codigo.length < 2) {
                        containerResultados.classList.add('hidden');
                        listaResultados.innerHTML = '';
                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        fetch(`{{ route('planificacion.buscarPlanillas') }}?codigo=${encodeURIComponent(codigo)}`)
                            .then(res => res.json())
                            .then(planillas => {
                                if (planillas.length === 0) {
                                    containerResultados.classList.add('hidden');
                                    listaResultados.innerHTML = '';
                                    return;
                                }

                                listaResultados.innerHTML = planillas.map(p =>
                                    `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-purple-100 text-purple-800 border border-purple-300">
                                        <strong>${p.codigo}</strong>
                                        <span class="text-purple-600">(${p.fecha})</span>
                                        ${p.fechaISO ? `<button type="button" onclick="window.irAFechaCalendario('${p.fechaISO}')" class="ml-1 px-1.5 py-0.5 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded transition" title="Ir a esta fecha">Ir</button>` : ''}
                                    </span>`
                                ).join('');
                                containerResultados.classList.remove('hidden');
                            })
                            .catch(err => {
                                console.error('Error buscando planillas:', err);
                                containerResultados.classList.add('hidden');
                            });
                    }, 300);
                });

                // Limpiar al resetear filtros
                document.getElementById('btn-reset-filtros')?.addEventListener('click', () => {
                    containerResultados.classList.add('hidden');
                    listaResultados.innerHTML = '';
                });
            }

            function initBuscadorObras() {
                const inputCodObra = document.getElementById('filtro-obra');
                const inputNombreObra = document.getElementById('filtro-nombre-obra');
                const containerResultados = document.getElementById('obras-encontradas');
                const listaResultados = document.getElementById('obras-encontradas-lista');

                if (!inputCodObra || !inputNombreObra || !containerResultados || !listaResultados) return;

                // Evitar doble inicialización
                if (inputCodObra.dataset.buscadorInit === 'true') return;
                inputCodObra.dataset.buscadorInit = 'true';

                let debounceTimer = null;

                function buscarObras() {
                    clearTimeout(debounceTimer);
                    const codigo = inputCodObra.value.trim();
                    const nombre = inputNombreObra.value.trim();

                    if (codigo.length < 2 && nombre.length < 2) {
                        containerResultados.classList.add('hidden');
                        listaResultados.innerHTML = '';
                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        const params = new URLSearchParams();
                        if (codigo) params.append('codigo', codigo);
                        if (nombre) params.append('nombre', nombre);

                        fetch(`{{ route('planificacion.buscarObras') }}?${params.toString()}`)
                            .then(res => res.json())
                            .then(obras => {
                                if (obras.length === 0) {
                                    containerResultados.classList.add('hidden');
                                    listaResultados.innerHTML = '';
                                    return;
                                }

                                listaResultados.innerHTML = obras.map(obra => `
                                    <div class="mb-2 p-2 bg-white rounded border border-purple-200">
                                        <div class="flex items-center gap-2 mb-1">
                                            <strong class="text-purple-800">${obra.codigo}</strong>
                                            <span class="text-purple-600 text-xs">${obra.nombre || ''}</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1">
                                            ${obra.planillas.map(p => `
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 text-xs">
                                                    <span>${p.codigo}</span>
                                                    <span class="text-purple-500">(${p.fecha})</span>
                                                    ${p.fechaISO ? `<button type="button" onclick="window.irAFechaCalendario('${p.fechaISO}')" class="px-1 py-0.5 bg-purple-600 hover:bg-purple-700 text-white rounded transition" title="Ir a ${p.fecha}">Ir</button>` : ''}
                                                </span>
                                            `).join('')}
                                        </div>
                                    </div>
                                `).join('');
                                containerResultados.classList.remove('hidden');
                            })
                            .catch(err => {
                                console.error('Error buscando obras:', err);
                                containerResultados.classList.add('hidden');
                            });
                    }, 300);
                }

                inputCodObra.addEventListener('input', buscarObras);
                inputNombreObra.addEventListener('input', buscarObras);

                // Limpiar al resetear filtros
                document.getElementById('btn-reset-filtros')?.addEventListener('click', () => {
                    containerResultados.classList.add('hidden');
                    listaResultados.innerHTML = '';
                });
            }

            function initBuscadorClientes() {
                const inputCodCliente = document.getElementById('filtro-cod-cliente');
                const inputNombreCliente = document.getElementById('filtro-cliente');
                const containerResultados = document.getElementById('clientes-encontrados');
                const listaResultados = document.getElementById('clientes-encontrados-lista');

                if (!inputCodCliente || !inputNombreCliente || !containerResultados || !listaResultados) return;

                // Evitar doble inicialización
                if (inputCodCliente.dataset.buscadorInit === 'true') return;
                inputCodCliente.dataset.buscadorInit = 'true';

                let debounceTimer = null;

                function buscarClientes() {
                    clearTimeout(debounceTimer);
                    const codigo = inputCodCliente.value.trim();
                    const nombre = inputNombreCliente.value.trim();

                    if (codigo.length < 2 && nombre.length < 2) {
                        containerResultados.classList.add('hidden');
                        listaResultados.innerHTML = '';
                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        const params = new URLSearchParams();
                        if (codigo) params.append('codigo', codigo);
                        if (nombre) params.append('nombre', nombre);

                        fetch(`{{ route('planificacion.buscarClientes') }}?${params.toString()}`)
                            .then(res => res.json())
                            .then(clientes => {
                                if (clientes.length === 0) {
                                    containerResultados.classList.add('hidden');
                                    listaResultados.innerHTML = '';
                                    return;
                                }

                                listaResultados.innerHTML = clientes.map(cliente => `
                                    <div class="mb-2 p-2 bg-white rounded border border-blue-200">
                                        <div class="flex items-center gap-2 mb-1">
                                            <strong class="text-blue-800">${cliente.codigo}</strong>
                                            <span class="text-blue-600 text-xs">${cliente.nombre || ''}</span>
                                            <span class="text-gray-500 text-xs">(${cliente.obrasCount} obras)</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1">
                                            ${cliente.planillas.map(p => `
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-xs">
                                                    <span class="font-medium">${p.planillaCodigo}</span>
                                                    <span>${p.fecha}</span>
                                                    <span class="text-blue-500" title="${p.obraNombre}">(${p.obraCodigo})</span>
                                                    ${p.fechaISO ? `<button type="button" onclick="window.irAFechaCalendario('${p.fechaISO}')" class="px-1 py-0.5 bg-blue-600 hover:bg-blue-700 text-white rounded transition" title="Ir a ${p.fecha}">Ir</button>` : ''}
                                                </span>
                                            `).join('')}
                                        </div>
                                    </div>
                                `).join('');
                                containerResultados.classList.remove('hidden');
                            })
                            .catch(err => {
                                console.error('Error buscando clientes:', err);
                                containerResultados.classList.add('hidden');
                            });
                    }, 300);
                }

                inputCodCliente.addEventListener('input', buscarClientes);
                inputNombreCliente.addEventListener('input', buscarClientes);

                // Limpiar al resetear filtros
                document.getElementById('btn-reset-filtros')?.addEventListener('click', () => {
                    containerResultados.classList.add('hidden');
                    listaResultados.innerHTML = '';
                });
            }

            function initAll() {
                initBuscadorPlanillas();
                initBuscadorObras();
                initBuscadorClientes();
            }

            // Inicializar en DOMContentLoaded
            document.addEventListener('DOMContentLoaded', initAll);

            // Inicializar en navegación Livewire
            if (typeof Livewire !== 'undefined') {
                document.addEventListener('livewire:navigated', initAll);
            }

            // Ejecutar inmediatamente si el DOM ya está listo
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                initAll();
            }
        })();
    </script>

    <!-- Componente Livewire para comentarios -->
    @livewire('planificacion.comentario-salida')

    <!-- Botón de ayuda de atajos flotante -->
    <div id="shortcuts-help-btn" class="fixed bottom-4 left-4 z-50 group">
        <button
            class="w-10 h-10 bg-slate-600 hover:bg-slate-500 dark:bg-slate-500 dark:hover:bg-slate-400 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-200 hover:scale-110">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                </path>
            </svg>
        </button>

        <!-- Tooltip con atajos -->
        <div
            class="absolute bottom-12 left-0 w-64 bg-gray-800 dark:bg-gray-900 text-white rounded-lg shadow-2xl p-4 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform translate-y-2 group-hover:translate-y-0">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-600 dark:border-gray-700">
                <h3 class="font-semibold text-sm">Atajos de teclado</h3>
            </div>

            <!-- Navegación -->
            <div class="mb-3">
                <div class="text-xs text-gray-400 dark:text-gray-500 mb-2">Navegación</div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-300 dark:text-gray-400">Mes/Semana anterior</span>
                        <kbd class="px-2 py-0.5 bg-gray-700 dark:bg-gray-800 rounded text-gray-200 dark:text-gray-300">←</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-300 dark:text-gray-400">Mes/Semana siguiente</span>
                        <kbd class="px-2 py-0.5 bg-gray-700 dark:bg-gray-800 rounded text-gray-200 dark:text-gray-300">→</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-300 dark:text-gray-400">Ir a hoy</span>
                        <kbd class="px-2 py-0.5 bg-gray-700 dark:bg-gray-800 rounded text-gray-200 dark:text-gray-300">T</kbd>
                    </div>
                </div>
            </div>

            <!-- Generales -->
            <div class="pt-2 border-t border-gray-600 dark:border-gray-700">
                <div class="text-xs text-gray-400 dark:text-gray-500 mb-2">General</div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-300 dark:text-gray-400">Pantalla completa</span>
                        <kbd class="px-2 py-0.5 bg-gray-700 dark:bg-gray-800 rounded text-gray-200 dark:text-gray-300">F11</kbd>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-300 dark:text-gray-400">Salir fullscreen</span>
                        <kbd class="px-2 py-0.5 bg-gray-700 dark:bg-gray-800 rounded text-gray-200 dark:text-gray-300">Esc</kbd>
                    </div>
                </div>
            </div>

            <!-- Flecha del tooltip -->
            <div class="absolute -bottom-2 left-4 w-4 h-4 bg-gray-800 dark:bg-gray-900 transform rotate-45"></div>
        </div>
    </div>

    <!-- Modal Logs de Planificación Salidas -->
    <div id="modalLogsS" onclick="if(event.target === this) cerrarVentanaLogsS()"
        class="fixed inset-0 bg-black/50 dark:bg-black/70 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-3xl mx-4 max-h-[90vh] flex flex-col border border-gray-200 dark:border-gray-700">
            <div class="bg-indigo-600 dark:bg-indigo-700 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    Historial de Cambios
                </h3>
                <button onclick="cerrarVentanaLogsS()" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4 flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-800/50" id="logs-container-s">
                <div id="logs-loading-s" class="flex justify-center py-8">
                    <svg class="animate-spin h-8 w-8 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div id="logs-list-s" class="space-y-3 hidden"></div>
                <div id="logs-empty-s" class="hidden text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p>No hay registros de cambios</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center bg-white dark:bg-gray-800">
                <span id="logs-total-s" class="text-sm text-gray-500 dark:text-gray-400"></span>
                <div class="flex gap-3">
                    <button onclick="cargarMasLogsS()" id="logs-cargar-mas-s" class="hidden px-4 py-2 text-sm bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/70 transition-colors">
                        Cargar más
                    </button>
                    <button onclick="cerrarVentanaLogsS()"
                        class="px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ============================================================
        // LOGS DE PLANIFICACIÓN SALIDAS
        // ============================================================

        let logsOffsetS = 0;
        const logsLimitS = 20;
        let logsTotalCountS = 0;

        function abrirVentanaLogsS() {
            const modal = document.getElementById('modalLogsS');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            logsOffsetS = 0;
            cargarLogsS(true);
        }

        function cerrarVentanaLogsS() {
            const modal = document.getElementById('modalLogsS');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function cargarLogsS(reset = false) {
            if (reset) {
                logsOffsetS = 0;
                document.getElementById('logs-list-s').innerHTML = '';
            }

            document.getElementById('logs-loading-s').classList.remove('hidden');
            document.getElementById('logs-list-s').classList.add('hidden');
            document.getElementById('logs-empty-s').classList.add('hidden');

            try {
                const response = await fetch(`{{ route('planificacion.logs') }}?limit=${logsLimitS}&offset=${logsOffsetS}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                document.getElementById('logs-loading-s').classList.add('hidden');

                if (data.success) {
                    logsTotalCountS = data.total;
                    document.getElementById('logs-total-s').textContent = `${data.total} registro(s) en total`;

                    if (data.logs.length === 0 && logsOffsetS === 0) {
                        document.getElementById('logs-empty-s').classList.remove('hidden');
                    } else {
                        document.getElementById('logs-list-s').classList.remove('hidden');
                        renderizarLogsS(data.logs, reset);

                        const cargarMasBtn = document.getElementById('logs-cargar-mas-s');
                        if (data.has_more) {
                            cargarMasBtn.classList.remove('hidden');
                        } else {
                            cargarMasBtn.classList.add('hidden');
                        }
                    }
                }
            } catch (error) {
                console.error('Error cargando logs:', error);
                document.getElementById('logs-loading-s').classList.add('hidden');
            }
        }

        function cargarMasLogsS() {
            logsOffsetS += logsLimitS;
            cargarLogsS(false);
        }

        function renderizarLogsS(logs, reset) {
            const container = document.getElementById('logs-list-s');

            if (reset) {
                container.innerHTML = '';
            }

            logs.forEach(log => {
                const iconos = {
                    'mover_planilla': { icon: '📅', color: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' },
                    'mover_salida': { icon: '🚚', color: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' },
                    'crear_salida': { icon: '➕', color: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' },
                    'eliminar_salida': { icon: '🗑️', color: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' },
                    'actualizar_salida': { icon: '✏️', color: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300' },
                    'revertir_accion': { icon: '↩️', color: 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300' }
                };

                const config = iconos[log.accion] || { icon: '📝', color: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' };

                const detallesHtml = log.detalles ? `
                    <button onclick="toggleDetallesLogS(${log.id})" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mt-1">
                        Ver detalles
                    </button>
                    <pre id="detalles-log-s-${log.id}" class="hidden mt-2 text-xs bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 p-2 rounded overflow-x-auto">${JSON.stringify(log.detalles, null, 2)}</pre>
                ` : '';

                let revertirHtml = '';
                if (log.puede_revertirse) {
                    revertirHtml = `
                        <button onclick="revertirLogS(${log.id})" class="ml-2 px-2 py-1 text-xs bg-orange-500 hover:bg-orange-600 dark:bg-orange-600 dark:hover:bg-orange-500 text-white rounded transition-colors" title="Deshacer esta acción">
                            ↩️ Deshacer
                        </button>
                    `;
                } else if (log.revertido) {
                    revertirHtml = `<span class="ml-2 text-xs text-gray-400 dark:text-gray-500 italic">Revertido</span>`;
                }

                const html = `
                    <div class="flex gap-3 p-3 bg-white dark:bg-gray-700/50 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-100 dark:border-gray-600 transition-colors ${log.revertido ? 'opacity-60' : ''}">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full ${config.color} text-lg">
                                ${config.icon}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    <span class="font-semibold text-indigo-700 dark:text-indigo-400">${log.usuario}</span> ${log.descripcion}
                                </p>
                                ${revertirHtml}
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400" title="${log.fecha}">${log.fecha_relativa}</span>
                            ${detallesHtml}
                        </div>
                    </div>
                `;

                container.insertAdjacentHTML('beforeend', html);
            });
        }

        async function revertirLogS(logId) {
            if (!confirm('¿Estás seguro de que deseas deshacer esta acción?')) {
                return;
            }

            try {
                const response = await fetch('{{ route('planificacion.revertirLog') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ log_id: logId })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Acción revertida correctamente');
                    cargarLogsS(true);
                    if (window.calendar) {
                        window.calendar.refetchEvents();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error al revertir:', error);
                alert('Error al revertir la acción');
            }
        }

        function toggleDetallesLogS(logId) {
            const detalles = document.getElementById(`detalles-log-s-${logId}`);
            detalles.classList.toggle('hidden');
        }
    </script>

</x-app-layout>
