@php
    $esOficina = auth()->user()->rol === 'oficina';
    $config = [
        'locale' => 'es',
        'csrfToken' => csrf_token(),
        'routes' => [
            'eventosUrl' => route('users.verEventos-turnos', $user->id),
            'resumenUrl' => route('users.verResumen-asistencia', ['user' => $user->id]),
            // Para que el operario pueda pedir vacaciones (el JS lo usa si canRequestVacations = true)
            'vacacionesStoreUrl' => route('vacaciones.solicitar'),
        ],
        'enableListMonth' => true,
        'mobileBreakpoint' => 768,
        'permissions' => [
            'canRequestVacations' => !$esOficina, // oficina solo ve; operario puede pedir
            'canEditHours' => false,
            'canAssignShifts' => false,
            'canAssignStates' => false,
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="title">Detalles de {{ $user->name }}</x-slot>
    <x-menu.usuarios :totalSolicitudesPendientes="$totalSolicitudesPendientes ?? 0" />

    <div class="container mx-auto md:px-4 py-6">
        <x-ficha-trabajador :user="$user" :resumen="$resumen" />

        <div class="bg-white py-2 rounded-lg shadow-lg">
            <div id="calendario" class="fc-calendario" data-config='@json($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
        </div>
    </div>

    {{-- FullCalendar (build global) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- CSS para el sombreado del rango entre primer y último clic --}}
    <style>
        .fc .bg-selection-temp {
            background-color: rgba(30, 143, 255, .28);
        }
    </style>

    {{-- Tu JS de calendario (el que te puse de “clic-clic”) --}}
    <script src="{{ asset('js/calendario/calendario.js') }}"></script>
</x-app-layout>
