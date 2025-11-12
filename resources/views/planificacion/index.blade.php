<x-app-layout>
    <x-slot name="title">Planificación Salidas</x-slot>

    <x-menu.salidas.salidas />

    <!-- Botones de navegación -->
    <div class="mb-6 border-b border-gray-200">
        <div class="flex space-x-2">
            <a href="{{ route('produccion.verMaquinas') }}"
               class="px-6 py-3 font-semibold text-gray-600 hover:text-blue-600 hover:bg-gray-50 border-b-2 border-transparent transition-colors">
                Producción/Máquinas
            </a>
            <a href="{{ route('planificacion.index') }}"
               class="px-6 py-3 font-semibold text-blue-600 border-b-2 border-blue-600 bg-blue-50 transition-colors">
                Planificación
            </a>
        </div>
    </div>

    <div class="py-6">
        <!-- Contenedor del Calendario -->
        <div class="w-full bg-white">
            <div class="mb-6 flex flex-col md:flex-row gap-4 justify-center">
                <!-- Filtro por código de obra -->
                <div class="flex items-center gap-2">
                    <label for="filtro-obra" class="text-sm text-gray-700">Filtrar por:</label>
                    <input id="filtro-obra" type="text" placeholder="Código de obra"
                        class="border rounded px-2 py-1 text-sm w-32" autocomplete="off" />
                    <input id="filtro-nombre-obra" type="text" placeholder="Nombre de obra"
                        class="border rounded px-2 py-1 text-sm w-64" autocomplete="off" />

                    {{-- ♻️ Botón reset --}}
                    <button type="button"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                        id="btn-reset-filtros" title="Restablecer filtros">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                        </svg>
                    </button>

                </div>

                <!-- Filtros de tipo de evento -->
                <div class="flex items-center gap-3">
                    <div class="checkbox-container">
                        <input type="checkbox" id="solo-salidas"
                            class="rounded border-gray-300 text-green-600 focus:ring-green-500" />
                        <label for="solo-salidas" class="text-sm text-gray-700 cursor-pointer">Solo salidas</label>
                    </div>
                    <div class="checkbox-container">
                        <input type="checkbox" id="solo-planillas"
                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500" />
                        <label for="solo-planillas" class="text-sm text-gray-700 cursor-pointer">Solo planillas y
                            resúmenes</label>
                    </div>
                </div>

                <!-- Resumen Semanal -->
                <div class="max-w-sm bg-blue-50 border border-blue-200 rounded-md p-3 shadow-sm text-sm">

                    <h3 class="text-base font-semibold text-blue-700 mb-1 text-center">
                        Resumen semanal <span id="resumen-semanal-fecha" class="text-gray-600 text-sm"></span>
                    </h3>
                    <div class="flex items-center justify-center gap-3">
                        <p id="resumen-semanal-peso">📦 0 kg</p>
                        <p id="resumen-semanal-longitud">📏 0 m</p>
                        <p id="resumen-semanal-diametro"></p>
                    </div>
                </div>

                <!-- Resumen Mensual -->
                <div class="max-w-sm bg-blue-50 border border-blue-200 rounded-md p-3 shadow-sm text-sm">
                    <h3 class="text-base font-semibold text-blue-700 mb-1 text-center">
                        Resumen mensual <span id="resumen-mensual-fecha" class="text-gray-600 text-sm"></span>
                    </h3>
                    <div class="flex items-center justify-center gap-3">
                        <p id="resumen-mensual-peso">📦 0 kg</p>
                        <p id="resumen-mensual-longitud">📏 0 m</p>
                        <p id="resumen-mensual-diametro"></p>
                    </div>
                </div>
            </div>

            <div id="calendario" class="h-[80vh] w-full"></div>
        </div>
    </div>

    @php
        $cfg = [
            'csrf' => csrf_token(),
            'routes' => [
                'planificacion' => url('/planificacion'),
                'crearSalidaDesdeCalendario' => route('planificacion.crearSalidaDesdeCalendario'),
                'crearSalidasVaciasDesdeCalendario' => route('planificacion.crearSalidasVaciasDesdeCalendario'),
                'informacionGestionPaquetes' => route('planificacion.informacionGestionPaquetes'),
                'obtenerSalidasPorPlanillas' => route('planificacion.obtenerSalidasPorPlanillas'),
                'guardarAsignacionesPaquetes' => route('planificacion.guardarAsignacionesPaquetes'),
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
    <!-- Primero los helpers -->
    <script src="{{ asset('js/utils/global-fechas.js') }}"></script>

    <!-- Después el calendario que los usa -->
    <script src="{{ asset('js/modules/calendario-salidas/calendario-menu.js') }}"></script>

    {{-- Tu config global ANTES de @vite --}}
    <script>
        // 1) Asegura el objeto global sin pisarlo
        window.AppSalidas = window.AppSalidas || {};

        // 2) Mezcla (merge) la config del backend SIN reemplazar el objeto entero
        Object.assign(window.AppSalidas, @json($cfg));

        // 3) Asegura que routes existe y añade solo la nueva clave
        window.AppSalidas.routes = window.AppSalidas.routes || {};
        window.AppSalidas.routes.codigoSage = @json(route('salidas.editarCodigoSage', ['salida' => '__ID__']));

        // (opcional) deja activado el nuevo menú sin romper nada
        window.AppSalidas.useNewMenu = true;
    </script>
    <link rel="stylesheet" href="{{ asset('css/estilosCalendarioSalidas.css') }}">
    <script type="module" src="{{ asset('js/modules/calendario-salidas/index.js') }}"></script>

    <!-- Componente Livewire para comentarios -->
    @livewire('planificacion.comentario-salida')

</x-app-layout>
