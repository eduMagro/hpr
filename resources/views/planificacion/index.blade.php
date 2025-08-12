<x-app-layout>
    <x-slot name="title">Planificación Salidas</x-slot>

    <x-menu.salidas />

    <div class="py-6">
        <!-- Contenedor del Calendario -->
        <div class="w-full bg-white">
            <div class="mb-6 flex flex-col md:flex-row gap-4 justify-center">
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
                'comentario' => url('/planificacion/comentario/__ID__'),
                // para update por drag&drop: PUT /planificacion/{id}
                'updateItem' => url('/planificacion/__ID__'),
                'totales' => url('/planificacion/totales'), // GET ?fecha=YYYY-MM-DD
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

    {{-- Tu config global ANTES de @vite --}}
    <script>
        // 1) Asegura el objeto global sin pisarlo
        window.AppSalidas = window.AppSalidas || {};

        // 2) Mezcla (merge) la config del backend SIN reemplazar el objeto entero
        Object.assign(window.AppSalidas, @json($cfg));

        // 3) Asegura que routes existe y añade solo la nueva clave
        window.AppSalidas.routes = window.AppSalidas.routes || {};
        window.AppSalidas.routes.codigoSage = @json(route('salidas.codigoSage', ['salida' => '__ID__']));

        // (opcional) deja activado el nuevo menú sin romper nada
        window.AppSalidas.useNewMenu = true;
    </script>

    @vite(['resources/js/calendario-salidas/index.js', 'resources/js/calendario-salidas/estilos.css'])


</x-app-layout>
