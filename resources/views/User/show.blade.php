@php
    $esOficina = Auth::check() && Auth::user()->rol === 'oficina';

    $config = [
        'locale' => 'es',
        'csrfToken' => csrf_token(),
        'routes' => [
            'eventosUrl' => route('users.verEventos-turnos', $user->id),
            'resumenUrl' => route('users.verResumen-asistencia', ['user' => $user->id]),
            'vacacionesStoreUrl' => route('vacaciones.solicitar'),
            'storeUrl' => route('asignaciones-turnos.store'),
            'destroyUrl' => route('asignaciones-turnos.destroy'),
        ],
        'enableListMonth' => true,
        'mobileBreakpoint' => 768,
        'permissions' => [
            'canRequestVacations' => !$esOficina,
            'canEditHours' => false,
            'canAssignShifts' => $esOficina, // si quieres permitir asignar turnos
            'canAssignStates' => $esOficina, // estados: vacaciones/baja/etc
        ],
        // Opcional: si quieres permitir asignar turnos por nombre
        'turnos' => $turnos->map(fn($t) => ['nombre' => $t->nombre])->values()->toArray(),
        'userId' => $user->id,
    ];
@endphp



<x-app-layout>
    <x-slot name="title">Detalles de {{ $user->name }}</x-slot>
    <x-menu.usuarios :totalSolicitudesPendientes="$totalSolicitudesPendientes ?? 0" />

    {{-- Botones de fichaje: solo operarios --}}
    @if (!$esOficina)
        <div class="flex justify-between items-center w-full gap-4 p-4">
            <button onclick="registrarFichaje('entrada')"
                class="w-full py-2 px-4 bg-green-600 text-white rounded-md btn-cargando">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Entrada</span>
            </button>

            <button onclick="registrarFichaje('salida')"
                class="w-full py-2 px-4 bg-red-600 text-white rounded-md btn-cargando">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Salida</span>
            </button>
        </div>
    @endif

    <div class="container mx-auto md:px-4 py-6">
        <x-ficha-trabajador :user="$user" :resumen="$resumen" />

        <div class="bg-white py-2 rounded-lg shadow-lg">
            <div id="calendario" class="fc-calendario" data-config='@json($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
        </div>
    </div>

    <!-- Cargar FullCalendar con prioridad -->

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
