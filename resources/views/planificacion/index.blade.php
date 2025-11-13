<x-app-layout>
    <x-slot name="title">Planificación Salidas</x-slot>

    <div class="py-6 px-4">
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
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
                                <label for="solo-salidas" class="ml-2 text-sm text-gray-700 cursor-pointer">Solo salidas</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" id="solo-planillas"
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-4 w-4" />
                                <label for="solo-planillas" class="ml-2 text-sm text-gray-700 cursor-pointer">Solo planillas y resúmenes</label>
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
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div id="calendario" class="h-[80vh] w-full"></div>
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
