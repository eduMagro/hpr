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
            {{-- OJO: JSON en atributo, siempre entre comillas --}}
            <div id="calendario" class="fc-calendario" data-config='@json($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
        </div>
    </div>

    {{-- FullCalendar --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .fc .bg-select-range {
            background: rgba(59, 131, 246, 0.534) !important;
        }

        .fc .bg-select-endpoint {
            background: rgba(37, 100, 235, 0.829) !important;
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
    </style>

    {{-- Asegúrate de que la ruta coincide con el fichero real --}}
    <script src="{{ asset('js/calendario/calendario.js') }}"></script>

    <script>
        function registrarFichaje(tipo) {
            const boton = event.currentTarget;
            const textoOriginal = boton.querySelector('.texto').textContent;

            boton.disabled = true;
            boton.querySelector('.texto').textContent = 'Obteniendo ubicación…';
            boton.classList.add('opacity-50', 'cursor-not-allowed');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const latitud = position.coords.latitude;
                    const longitud = position.coords.longitude;

                    // Ya tenemos coordenadas rápidas
                    Swal.fire({
                        title: 'Confirmar Fichaje',
                        text: `¿Quieres registrar una ${tipo}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, fichar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ url('/fichar') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },
                                    body: JSON.stringify({
                                        user_id: "{{ auth()->id() }}",
                                        tipo: tipo,
                                        latitud: latitud,
                                        longitud: longitud,
                                    })
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
                    });

                    boton.disabled = false;
                    boton.querySelector('.texto').textContent = textoOriginal;
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                },
                function(error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de ubicación',
                        text: `${error.message}`
                    });
                    boton.disabled = false;
                    boton.querySelector('.texto').textContent = textoOriginal;
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                }, {
                    enableHighAccuracy: false, // 💡 más rápido
                    timeout: 8000, // 💡 máximo 8 segundos
                    maximumAge: 60000 // 💡 usar cache si tiene <1 min
                }
            );
        }
    </script>

    {{-- asignar turno como operario --}}
    <script></script>
</x-app-layout>
