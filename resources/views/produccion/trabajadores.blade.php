<x-app-layout>
    <x-slot name="title">Planificación Producción</x-slot>
    <x-menu.usuarios :totalSolicitudesPendientes="$totalSolicitudesPendientes ?? 0" />

    <div class="py-6">
        <!-- Contenedor del Calendario -->
        <div class="w-full bg-white">
            <div id="calendario" class="h-[80vh] w-full"></div>
        </div>
    </div>

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
    @php
        $cfg = [
            'csrf' => csrf_token(),
            'routes' => [
                'festivo' => [
                    'store' => route('festivos.store'),
                    'update' => route('festivos.actualizarFecha', ['festivo' => '__ID__']),
                    'delete' => route('festivos.eliminar', ['festivo' => '__ID__']),
                ],
                'asignacion' => [
                    'delete' => route('asignaciones-turnos.destroy', ['asignacion' => '__ID__']),
                    'updateHoras' => url('/asignaciones-turno/__ID__/actualizar-horas'),
                    'updatePuesto' => url('/asignaciones-turno/__ID__/actualizar-puesto'),
                ],
                'userShow' => route('users.show', ':id'),
            ],
            'maquinas' => $maquinas,
            'eventos' => $trabajadoresEventos,
        ];
    @endphp

    <script>
        window.AppPlanif = @json($cfg);
    </script>
    <style>
        .fc-contextmenu button:hover {
            background: #f3f4f6;
        }

        .tippy-box[data-theme~="transparent-avatar"] {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        /* Contenedor general */
        .ctx-menu-container {
            display: flex;
            flex-direction: column;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            min-width: 240px;
            font-family: system-ui, sans-serif;
        }

        /* Cabecera */
        .ctx-menu-header {
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Botones */
        .ctx-menu-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            font-size: 14px;
            background: white;
            border: none;
            text-align: left;
            cursor: pointer;
            transition: background 0.15s ease-in-out;
        }

        .ctx-menu-item:hover {
            background: #f3f4f6;
        }

        .ctx-menu-item:active {
            background: #e5e7eb;
        }

        /* Icono */
        .ctx-menu-icon {
            font-size: 16px;
        }

        /* Texto */
        .ctx-menu-label {
            flex: 1;
        }

        /* Opción peligrosa */
        .ctx-menu-danger {
            color: #b91c1c;
        }

        .ctx-menu-danger:hover {
            background: #fee2e2;
        }
    </style>
    @vite(['resources/js/app.js'])
</x-app-layout>
