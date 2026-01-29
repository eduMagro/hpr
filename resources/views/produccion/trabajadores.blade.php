<x-app-layout>
    <x-slot name="title">Planificación Producción</x-slot>

    <x-page-header
        title="Planificación de Trabajadores"
        subtitle="Calendario de turnos y asignaciones del personal"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'
    />

    <div class="py-2" id="calendario-trabajadores-container">
        <!-- Filtro de búsqueda de trabajadores (eventos) -->
        <div class="px-4 py-2 bg-white border-b">
            <input type="text" id="filtro-eventos"
                   placeholder="Buscar trabajador..."
                   class="w-full md:w-64 border border-gray-300 rounded px-3 py-2 text-sm focus:ring focus:ring-blue-300 focus:border-blue-500">
        </div>
        <!-- Contenedor del Calendario -->
        <div class="w-full bg-white">
            <div id="calendario" data-calendar-type="trabajadores" class="w-full" style="height: calc(100vh - 100px);"></div>
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
                    'delete' => route('festivos.destroy', ['festivo' => '__ID__']),
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
            'cargaTrabajo' => $cargaTrabajo ?? [],
            'turnos' => $turnos ?? [],
            'turnosConfig' => $turnosConfig ?? null,
        ];
    @endphp

    <script>
        window.AppPlanif = @json($cfg);
    </script>
    <style>
        /* ============================================
           ESTILOS DEL CALENDARIO (mismo estilo que User/show)
           ============================================ */

        .fc {
            width: 100% !important;
            max-width: 100% !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        /* Header del calendario - mismo color que sidebar */
        .fc .fc-toolbar {
            padding: 1rem;
            background: #111827; /* gray-900 */
            border-radius: 12px 12px 0 0;
            margin-bottom: 0 !important;
        }

        .fc .fc-toolbar-title {
            color: white !important;
            font-weight: 700;
            font-size: 1.25rem;
            text-transform: capitalize;
        }

        .fc .fc-button {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px !important;
            transition: all 0.2s ease;
            text-transform: capitalize;
        }

        .fc .fc-button:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            transform: translateY(-1px);
        }

        .fc .fc-button-active {
            background: #3b82f6 !important;
            color: white !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
        }

        .fc .fc-button:disabled {
            opacity: 0.5;
        }

        /* Bordes redondeados del contenedor */
        .fc .fc-view-harness {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-top: none;
        }

        /* Encabezados de recursos (máquinas) */
        .fc .fc-resource-area {
            background: #f8fafc;
        }

        .fc .fc-datagrid-cell-frame {
            padding: 0.5rem;
        }

        /* Eventos de carga de trabajo (indicadores pequeños) */
        .fc-event.evento-carga {
            font-size: 0.6rem !important;
            padding: 0 !important;
            border-radius: 3px !important;
            cursor: default !important;
            pointer-events: none !important;
            opacity: 0.9;
            min-height: 16px !important;
            max-height: 18px !important;
            line-height: 16px !important;
            overflow: hidden;
        }

        .fc-event.evento-carga .fc-event-main {
            padding: 0 !important;
        }

        .carga-content {
            font-size: 0.6rem;
            font-weight: 700;
            text-align: center;
            padding: 0 3px;
            line-height: 16px;
        }

        /* Ocultar eventos de carga en vista semanal */
        .vista-semana .evento-carga {
            display: none !important;
        }

        /* Celdas del timeline - vista día con turnos limpios */
        .vista-dia .fc-timeline-slot {
            border-color: #d1d5db !important;
        }

        .vista-dia .fc-timeline-slot-frame {
            border-right: 2px solid #9ca3af !important;
        }

        /* Ocultar líneas de cuadrícula menores en vista día */
        .vista-dia .fc-timeline-slot-minor {
            border: none !important;
        }

        /* Celdas del timeline - vista semana */
        .vista-semana .fc-timeline-slot {
            border-color: #e2e8f0 !important;
        }

        /* Hover solo en la celda individual */
        .fc .fc-timeline-lane:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* Slot del día actual */
        .fc .fc-day-today {
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%) !important;
        }

        /* Eventos */
        .fc .fc-event {
            border-radius: 6px;
            border: none !important;
            padding: 2px 6px;
            font-size: 0.75rem;
            font-weight: 500;
            margin: 1px 2px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .fc .fc-event:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            z-index: 10;
        }

        /* Labels de slots (días/horas) */
        .fc .fc-timeline-slot-label {
            font-weight: 600;
            color: #475569;
            text-transform: capitalize;
            font-size: 0.8rem;
        }

        /* Scrollbar del calendario */
        .fc .fc-scroller::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .fc .fc-scroller::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.75rem;
            }

            .fc .fc-toolbar-title {
                font-size: 1rem;
            }

            .fc .fc-button {
                padding: 0.4rem 0.75rem;
                font-size: 0.8rem;
            }
        }

        /* ============================================
           ESTILOS DEL MENÚ CONTEXTUAL
           ============================================ */

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

    {{-- Usar Vite en lugar de asset() para cache busting --}}
    @vite(['resources/js/modules/calendario-trabajadores/index.js'])
</x-app-layout>
