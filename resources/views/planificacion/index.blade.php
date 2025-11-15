<x-app-layout>
    <x-slot name="title">Planificación Salidas</x-slot>

    <div class="px-4" id="planificacion-container">
        <div class="max-w-[1800px] mx-auto">
            <!-- Sección de Filtros y Resúmenes -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <!-- Filtros -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Filtro por código de obra -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Filtrar por obra</h4>
                        <div class="flex flex-wrap items-center gap-2">
                            <input id="filtro-obra" type="text" placeholder="Código de obra"
                                class="border-gray-300 rounded-md px-3 py-2 text-sm flex-1 min-w-[120px] focus:ring-purple-500 focus:border-purple-500"
                                autocomplete="off" />
                            <input id="filtro-nombre-obra" type="text" placeholder="Nombre de obra"
                                class="border-gray-300 rounded-md px-3 py-2 text-sm flex-1 min-w-[200px] focus:ring-purple-500 focus:border-purple-500"
                                autocomplete="off" />
                            <button type="button"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded-md text-sm flex items-center justify-center transition"
                                id="btn-reset-filtros" title="Restablecer filtros">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Filtros de tipo de evento -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Filtrar por tipo</h4>
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center">
                                <input type="checkbox" id="solo-salidas"
                                    class="rounded border-gray-300 text-green-600 focus:ring-green-500 h-4 w-4" />
                                <label for="solo-salidas" class="ml-2 text-sm text-gray-700 cursor-pointer">Solo
                                    salidas</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="solo-planillas"
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-4 w-4" />
                                <label for="solo-planillas" class="ml-2 text-sm text-gray-700 cursor-pointer">Solo
                                    planillas y resúmenes</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resúmenes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Resumen Semanal -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 shadow-sm">
                        <h3 class="text-base font-semibold text-blue-700 mb-3 text-center">
                            Resumen semanal <span id="resumen-semanal-fecha" class="text-gray-600 text-sm"></span>
                        </h3>
                        <div class="flex items-center justify-center gap-4 text-sm">
                            <p id="resumen-semanal-peso" class="font-medium">📦 0 kg</p>
                            <p id="resumen-semanal-longitud" class="font-medium">📏 0 m</p>
                            <p id="resumen-semanal-diametro" class="font-medium"></p>
                        </div>
                    </div>

                    <!-- Resumen Mensual -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 shadow-sm">
                        <h3 class="text-base font-semibold text-blue-700 mb-3 text-center">
                            Resumen mensual <span id="resumen-mensual-fecha" class="text-gray-600 text-sm"></span>
                        </h3>
                        <div class="flex items-center justify-center gap-4 text-sm">
                            <p id="resumen-mensual-peso" class="font-medium">📦 0 kg</p>
                            <p id="resumen-mensual-longitud" class="font-medium">📏 0 m</p>
                            <p id="resumen-mensual-diametro" class="font-medium"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendario -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 relative">
                <!-- Botón de pantalla completa en esquina superior derecha -->
                <button onclick="toggleFullScreen()" id="fullscreen-btn"
                    title="Pantalla completa"
                    class="absolute top-4 right-4 z-10 p-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg transition-colors shadow-lg">
                    <svg id="fullscreen-icon-expand" class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4">
                        </path>
                    </svg>
                    <svg id="fullscreen-icon-collapse" class="w-5 h-5 hidden" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25">
                        </path>
                    </svg>
                </button>

                <div id="calendario" data-calendar-type="salidas" class="h-[80vh] w-full overflow-hidden"></div>
            </div>
        </div>
    </div>

    @php
        $cfg = [
            'csrf' => csrf_token(),
            'routes' => [
                'planificacion' => url('/planificacion'),
                'crearSalidaDesdeCalendario' => route('planificacion.crearSalidaDesdeCalendario'),
                'informacionPaquetesSalida' => route('planificacion.informacionPaquetesSalida'),
                'guardarPaquetesSalida' => route('planificacion.guardarPaquetesSalida'),
                'comentario' => url('/planificacion/comentario/__ID__'),
                // para update por drag&drop: PUT /planificacion/{id}
                'updateItem' => url('/planificacion/__ID__'),
                'totales' => url('/planificacion/totales'), // GET ?fecha=YYYY-MM-DD
                'salidasCreate' => route('salidas-ferralla.create'),
                // 📅 nuevas rutas para cambiar fechas de entrega
                'informacionPlanillas' => route('planillas.editarInformacionMasiva'), // GET ?ids=1,2,3
                'actualizarFechasPlanillas' => route('planillas.editarActualizarFechasMasiva'), // PUT JSON
            ],
            'camiones' => $camiones ?? [],
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

    {{-- Tu config global ANTES de @vite --}}
    <script data-navigate-once>
        // 1) Asegura el objeto global sin pisarlo
        window.AppSalidas = window.AppSalidas || {};

        // 2) Mezcla (merge) la config del backend SIN reemplazar el objeto entero
        Object.assign(window.AppSalidas, @json($cfg));

        // 3) Asegura que routes existe y añade solo la nueva clave
        window.AppSalidas.routes = window.AppSalidas.routes || {};
        window.AppSalidas.routes.codigoSage = @json(route('salidas.editarCodigoSage', ['salida' => '__ID__']));

        // 4) Sistema de pantalla completa
        window.isFullScreen = window.isFullScreen || false;

        function toggleFullScreen() {
            const container = document.getElementById('planificacion-container');
            const sidebar = document.querySelector('[class*="sidebar"]') || document.querySelector('aside');
            const header = document.querySelector('nav');
            const breadcrumbs = document.querySelector('[class*="breadcrumb"]');
            const expandIcon = document.getElementById('fullscreen-icon-expand');
            const collapseIcon = document.getElementById('fullscreen-icon-collapse');
            const fullscreenBtn = document.getElementById('fullscreen-btn');

            if (!isFullScreen) {
                // Entrar en pantalla completa
                if (sidebar) sidebar.style.display = 'none';
                if (header) header.style.display = 'none';
                if (breadcrumbs) breadcrumbs.style.display = 'none';

                container.classList.add('fixed', 'inset-0', 'z-50', 'bg-gray-50', 'overflow-auto');
                container.classList.remove('px-4');
                container.style.padding = '1rem';

                expandIcon.classList.add('hidden');
                collapseIcon.classList.remove('hidden');
                fullscreenBtn.title = 'Salir de pantalla completa';

                window.isFullScreen = true;

                // Atajo de teclado ESC para salir
                document.addEventListener('keydown', handleEscKey);
            } else {
                // Salir de pantalla completa
                if (sidebar) sidebar.style.display = '';
                if (header) header.style.display = '';
                if (breadcrumbs) breadcrumbs.style.display = '';

                container.classList.remove('fixed', 'inset-0', 'z-50', 'bg-gray-50', 'overflow-auto');
                container.classList.add('px-4');
                container.style.padding = '';

                expandIcon.classList.remove('hidden');
                collapseIcon.classList.add('hidden');
                fullscreenBtn.title = 'Pantalla completa';

                window.isFullScreen = false;

                document.removeEventListener('keydown', handleEscKey);
            }

            // Re-renderizar el calendario para ajustar su tamaño
            if (window.calendar) {
                setTimeout(() => {
                    window.calendar.updateSize();
                }, 100);
            }
        }

        function handleEscKey(e) {
            if (e.key === 'Escape' && window.isFullScreen) {
                toggleFullScreen();
            }
        }

        // También permitir F11 como alternativa (opcional)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F11') {
                e.preventDefault();
                toggleFullScreen();
            }
        });

        // (opcional) deja activado el nuevo menú sin romper nada
        window.AppSalidas.useNewMenu = true;
    </script>

    <!-- Componente Livewire para comentarios -->
    @livewire('planificacion.comentario-salida')

</x-app-layout>
