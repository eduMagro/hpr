<x-app-layout>
    <x-slot name="title">Detalles de {{ $user->name }}</x-slot>
    <x-menu.usuarios :totalSolicitudesPendientes="$totalSolicitudesPendientes ?? 0" />

    @php
        $config = [
            'userId' => $user->id,
            'locale' => 'es',
            'csrfToken' => csrf_token(),
            'routes' => [
                'eventosUrl' => route('users.verEventos-turnos', $user->id),
                'resumenUrl' => route('users.verResumen-asistencia', ['user' => $user->id]),
                'storeUrl' => route('asignaciones-turnos.store'),
                'destroyUrl' => route('asignaciones-turnos.destroy'),
                'updateHorasUrlBase' => url('/asignaciones-turno/{id}/actualizar-horas'),
            ],
            // pasamos un array llano, no colecciones
            'turnos' => $turnos->map(fn($t) => ['nombre' => $t->nombre])->values()->toArray(),
            'enableListMonth' => true,
            'mobileBreakpoint' => 768,
        ];
    @endphp

    <div class="container mx-auto md:px-4 py-6">
        <x-ficha-trabajador :user="$user" :resumen="$resumen" />

        <div class="bg-white py-2 rounded-lg shadow-lg">
            <div id="calendario" class="fc-calendario" data-config='@json($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
        </div>
    </div>

    {{-- FullCalendar --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>

    {{-- SweetAlert2 si no está ya en tu layout --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}

    <script src="{{ asset('js/calendario/calendario.js') }}"></script>

    <style>
        .fc .bg-selection-temp {
            background-color: rgba(30, 143, 255, 0.6);
        }

        .fc-daygrid-day-frame {
            display: flex;
            flex-direction: column;
        }

        .fc-daygrid-day-events {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            height: 100%;
            padding-bottom: 2px;
        }
    </style>
</x-app-layout>
