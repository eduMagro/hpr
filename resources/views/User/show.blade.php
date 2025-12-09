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
    <x-slot name="title">{{ $user->nombre_completo }}</x-slot>

    {{-- Botones de fichaje: disponibles para todos los roles --}}
    <div class="container mx-auto px-4 pt-6 pb-4">
        <div class="flex justify-center items-center gap-4">
            <button onclick="registrarFichaje('entrada')"
                class="py-3 px-8 bg-green-600 hover:bg-green-700 text-white text-lg font-semibold rounded-lg shadow-lg transition duration-200 btn-cargando">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Entrada</span>
            </button>

            <button onclick="registrarFichaje('salida')"
                class="py-3 px-8 bg-red-600 hover:bg-red-700 text-white text-lg font-semibold rounded-lg shadow-lg transition duration-200 btn-cargando">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Salida</span>
            </button>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <x-ficha-trabajador :user="$user" :resumen="$resumen" />
    </div>

    {{-- Calendario a ancho completo --}}
    <div class="calendario-full-width">
        <div class="bg-white py-4">
            <div id="calendario" class="fc-calendario" data-config='@json($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
        </div>
    </div>

    {{-- FullCalendar --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Calendario a ancho completo - salir del contenedor padre */
        .calendario-full-width {
            width: 100%;
        }

        .fc {
            width: 100% !important;
            max-width: 100% !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .fc-daygrid-day {
            min-width: 0 !important;
        }

        /* Header del calendario - mismo color que sidebar */
        .fc .fc-toolbar {
            padding: 1rem;
            background: #111827;
            /* gray-900 */
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

        /* Encabezados de días */
        .fc .fc-col-header {
            background: #f8fafc;
        }

        .fc .fc-col-header-cell {
            padding: 0.75rem 0;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-color: #e2e8f0 !important;
        }

        /* Celdas de días */
        .fc .fc-daygrid-day {
            transition: background-color 0.15s ease;
            border-color: #e2e8f0 !important;
        }

        .fc .fc-daygrid-day:hover {
            background-color: #f1f5f9;
        }

        .fc .fc-daygrid-day-number {
            font-weight: 600;
            color: #334155;
            padding: 0.5rem;
            font-size: 0.875rem;
        }

        .fc .fc-day-today {
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%) !important;
        }

        .fc .fc-day-today .fc-daygrid-day-number {
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.25rem;
        }

        /* Días de otros meses */
        .fc .fc-day-other .fc-daygrid-day-number {
            color: #94a3b8;
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
        }

        .fc .fc-daygrid-event-dot {
            display: none;
        }

        /* Más eventos link */
        .fc .fc-daygrid-more-link {
            color: #6366f1;
            font-weight: 600;
            font-size: 0.75rem;
        }

        /* Scrollbar del calendario */
        .fc .fc-scroller::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .fc .fc-scroller::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Selección de rango */
        .fc .bg-select-range {
            background: rgba(99, 102, 241, 0.25) !important;
            border-radius: 4px;
        }

        .fc .bg-select-endpoint {
            background: rgba(99, 102, 241, 0.45) !important;
        }

        .fc .bg-select-endpoint-left {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .fc .bg-select-endpoint-right {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .fc .fc-daygrid-day-bg {
            overflow: visible;
        }

        /* que los bg events no intercepten el mouse */
        .fc .bg-select-range,
        .fc .bg-select-endpoint {
            pointer-events: none !important;
        }

        /* Vista lista */
        .fc .fc-list {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }

        .fc .fc-list-day-cushion {
            background: #f8fafc !important;
            padding: 0.75rem 1rem;
        }

        .fc .fc-list-event:hover td {
            background: #f1f5f9 !important;
        }

        /* Bordes redondeados del contenedor */
        .fc .fc-view-harness {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-top: none;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.75rem;
            }

            .fc .fc-toolbar-title {
                font-size: 1.1rem;
            }

            .fc .fc-button {
                padding: 0.4rem 0.75rem;
                font-size: 0.8rem;
            }

            .fc .fc-col-header-cell {
                font-size: 0.65rem;
                padding: 0.5rem 0;
            }

            .fc .fc-daygrid-day-number {
                font-size: 0.75rem;
                padding: 0.25rem;
            }

            .fc .fc-event {
                font-size: 0.65rem;
                padding: 1px 4px;
            }
        }

        /* SweetAlert personalizado para gestión de turnos */
        .swal-calendario-popup {
            border-radius: 12px !important;
            overflow: hidden;
        }

        .swal-calendario-popup .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
        }

        .swal-calendario-popup .swal2-actions {
            margin-top: 20px;
            gap: 12px;
        }

        .swal-calendario-popup .swal2-confirm,
        .swal-calendario-popup .swal2-cancel {
            padding: 10px 24px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            transition: transform 0.15s, box-shadow 0.15s !important;
        }

        .swal-calendario-popup .swal2-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
        }

        .swal-calendario-popup .swal2-cancel:hover {
            transform: translateY(-1px);
        }

        .swal-calendario-popup select optgroup {
            font-weight: 600;
            color: #6b7280;
            font-size: 12px;
        }

        .swal-calendario-popup select option {
            padding: 8px;
            font-size: 14px;
        }
    </style>

    {{-- Usar script desde public (no migrado a Vite aún) --}}
    <script src="{{ asset('js/calendario/calendario.js') }}?v={{ time() }}"></script>

    <script>
        function registrarFichaje(tipo) {
            const boton = event.currentTarget;
            const textoOriginal = boton.querySelector('.texto').textContent;

            boton.disabled = true;
            boton.querySelector('.texto').textContent = 'Procesando...';
            boton.classList.add('opacity-50', 'cursor-not-allowed');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const latitud = position.coords.latitude;
                    const longitud = position.coords.longitude;
                    procesarFichaje(tipo, latitud, longitud, boton, textoOriginal);
                },
                function(error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de ubicacion',
                        text: `${error.message}`
                    });
                    boton.disabled = false;
                    boton.querySelector('.texto').textContent = textoOriginal;
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                }, {
                    enableHighAccuracy: false,
                    timeout: 8000,
                    maximumAge: 60000
                }
            );
        }

        function procesarFichaje(tipo, latitud, longitud, boton, textoOriginal) {
            Swal.fire({
                title: 'Confirmar Fichaje',
                text: `Quieres registrar una ${tipo}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Si, fichar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const payload = {
                        user_id: "{{ auth()->id() }}",
                        tipo: tipo,
                        latitud: latitud,
                        longitud: longitud,
                    };

                    fetch("{{ url('/fichar') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: data.success,
                                    text: `📍 Lugar: ${data.obra_nombre}`,
                                    showConfirmButton: false,
                                    timer: 3000
                                });

                                if (window.calendar) {
                                    window.calendar.refetchEvents();
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.error
                                });
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo comunicar con el servidor'
                            });
                        });
                }

                boton.disabled = false;
                boton.querySelector('.texto').textContent = textoOriginal;
                boton.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }
    </script>
</x-app-layout>
