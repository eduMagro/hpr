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
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendario');
            actualizarResumenAsistencia();

            const vistasDisponibles = ['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listWeek'];
            let vistaGuardada = localStorage.getItem('ultimaVistaCalendario');

            if (!vistasDisponibles.includes(vistaGuardada)) {
                vistaGuardada = 'dayGridMonth';
            }

            const fechaGuardada = localStorage.getItem('fechaCalendario');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: vistaGuardada,
                initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,
                locale: 'es',
                firstDay: 1,
                height: 'auto',
                selectable: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                // ✅ EVENTO CLICK: SOLO PARA FICHAJES (entrada/salida)
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    console.log('🔍 Click en evento', props);
                    // ahora cualquier evento que tenga asignacion_id sirve
                    if (!props || !props.asignacion_id) return;

                    Swal.fire({
                        title: `📋 Fichaje del ${props.fecha}`,
                        html: `
                        <div class="text-center">
                            <div class="mb-4">
                            <label class="block mb-2 font-semibold">Hora de entrada:</label>
                            <input type="time"
                                    id="horaEntrada"
                                    class="swal2-input"
                                    style="display:block; margin:0 auto; width:auto;"
                                    value="${props.entrada ?? ''}">
                            </div>
                            <div>
                            <label class="block mb-2 font-semibold">Hora de salida:</label>
                            <input type="time"
                                    id="horaSalida"
                                    class="swal2-input"
                                    style="display:block; margin:0 auto; width:auto;"
                                    value="${props.salida ?? ''}">
                            </div>
                        </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: "Guardar",
                        cancelButtonText: "Cancelar"
                    }).then(res => {
                        if (!res.isConfirmed) return;

                        let entrada = document.getElementById('horaEntrada').value;
                        let salida = document.getElementById('horaSalida').value;

                        // recortamos segundos si vienen en formato HH:mm:ss
                        if (entrada && entrada.length === 8) entrada = entrada.slice(0, 5);
                        if (salida && salida.length === 8) salida = salida.slice(0, 5);

                        fetch(`/asignaciones-turno/${props.asignacion_id}/actualizar-horas`, {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                },
                                body: JSON.stringify({
                                    entrada,
                                    salida
                                })
                            })
                            .then(async response => {
                                const contentType = response.headers.get(
                                    'content-type');
                                let data = {};
                                if (contentType && contentType.includes(
                                        'application/json')) {
                                    data = await response.json();
                                } else {
                                    const text = await response.text();
                                    throw new Error("Respuesta inesperada: " + text
                                        .slice(0, 100));
                                }

                                if (response.ok && data.ok) {
                                    calendar.refetchEvents();
                                    setTimeout(actualizarResumenAsistencia, 200);
                                } else {
                                    let errorMsg = data.message ||
                                        "Ha ocurrido un error inesperado.";
                                    if (data.errors) {
                                        errorMsg = Object.values(data.errors).flat()
                                            .join('<br>');
                                    }
                                    Swal.fire({
                                        icon: "error",
                                        title: "Error al guardar",
                                        html: errorMsg
                                    });
                                }
                            })
                            .catch(err => {
                                console.error("Error en Fetch:", err);
                                Swal.fire({
                                    icon: "error",
                                    title: "Error de conexión",
                                    text: "No se pudo actualizar la asignación. Inténtalo nuevamente."
                                });
                            });
                    });
                },
                // ✅ SELECT DE DÍAS: ASIGNACIÓN DE TURNOS, VACACIONES, ETC.
                select: function(info) {
                    const fechaInicio = info.startStr;
                    const fechaFinObj = new Date(info.end);
                    fechaFinObj.setDate(fechaFinObj.getDate()); // para que sea inclusiva
                    const fechaFin = fechaFinObj.toISOString().split('T')[0];

                    const mensajeFecha = fechaInicio === fechaFin ?
                        `<p>${fechaInicio}</p>` :
                        `<p>Desde: ${fechaInicio}</p><p>Hasta: ${fechaFin}</p>`;

                    Swal.fire({
                        title: "Selecciona un turno",
                        html: `
                        ${mensajeFecha}
                        <select id="tipo-dia" class="swal2-select">
                            <option value="eliminarTurnoEstado">🗑 Eliminar Turno</option>
                            @foreach ($turnos as $turno)
                                <option value="{{ $turno->nombre }}">{{ ucfirst($turno->nombre) }}</option>
                            @endforeach
                            <option value="eliminarEstado">🗑 Eliminar Estado</option>
                            <option value="curso">Realizando Cursos</option>
                            <option value="vacaciones">🏖 Vacaciones</option>
                            <option value="baja">🤒 Baja</option>
                            <option value="justificada">✅ Falta Justificada</option>
                            <option value="injustificada">❌ Falta Injustificada</option>
                        </select>
                    `,
                        showCancelButton: true,
                        confirmButtonText: "Registrar",
                        cancelButtonText: "Cancelar",
                        preConfirm: () => document.getElementById("tipo-dia").value
                    }).then((result) => {
                        if (!result.isConfirmed) return;

                        const tipoSeleccionado = result.value;
                        if (tipoSeleccionado === "eliminarTurnoEstado" || tipoSeleccionado ===
                            "eliminarEstado") {
                            const mensajeConfirmacion = tipoSeleccionado ===
                                "eliminarTurnoEstado" ?
                                "¿Estás seguro de que quieres eliminar el turno? Esto también eliminará cualquier estado asignado (vacaciones, baja...) y las horas de entrada y salida." :
                                "¿Seguro que quieres eliminar solo el estado? Las horas de entrada y salida y el turno se mantendrán.";

                            Swal.fire({
                                title: "Confirmar eliminación",
                                text: mensajeConfirmacion,
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonText: "Sí, eliminar",
                                cancelButtonText: "Cancelar"
                            }).then(confirmacion => {
                                if (!confirmacion.isConfirmed) return;

                                const eventosEnRango = calendar.getEvents().filter(
                                    event => {
                                        const eventDate = event.startStr;
                                        return eventDate >= fechaInicio &&
                                            eventDate <= fechaFin;
                                    });

                                const todosSonFestivo = eventosEnRango.length > 0 &&
                                    eventosEnRango.every(e => e.title?.toLowerCase() ===
                                        "festivo");

                                const body = {
                                    fecha_inicio: fechaInicio,
                                    fecha_fin: fechaFin
                                };

                                if (todosSonFestivo) {
                                    body.tipo_turno = "festivo";
                                } else {
                                    body.user_id = "{{ $user->id }}";
                                    body.tipo = tipoSeleccionado;
                                }

                                fetch("{{ route('asignaciones-turnos.destroy') }}", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                        },
                                        body: JSON.stringify(body)
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            eventosEnRango.forEach(event => event
                                                .remove());
                                            calendar.refetchEvents();
                                            setTimeout(actualizarResumenAsistencia,
                                                200);
                                            Swal.fire("Eliminado",
                                                "Los turnos han sido eliminados correctamente.",
                                                "success");
                                        } else {
                                            Swal.fire("Error", data.error, "error");
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error:", error);
                                        Swal.fire("Error",
                                            "Ocurrió un problema al eliminar los turnos.",
                                            "error");
                                    });
                            });
                        } else {
                            // ✅ Asignación de nuevo turno o estado
                            fetch("{{ route('asignaciones-turnos.store') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },
                                    body: JSON.stringify({
                                        user_id: "{{ $user->id }}",
                                        fecha_inicio: fechaInicio,
                                        fecha_fin: fechaFin,
                                        tipo: tipoSeleccionado
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        calendar.refetchEvents();
                                        setTimeout(actualizarResumenAsistencia, 200);
                                    } else {
                                        Swal.fire("Error", data.error, "error");
                                    }
                                })
                                .catch(err => {
                                    console.error("Error:", err);
                                    Swal.fire("Error",
                                        "Ocurrió un problema al registrar los turnos.",
                                        "error");
                                });
                        }
                    });
                },

                // ✅ Guardar vista actual y fecha para recordar posición
                datesSet: function(info) {
                    let fechaActual = info.startStr;
                    if (calendar.view.type === 'dayGridMonth') {
                        const middleDate = new Date(info.start);
                        middleDate.setDate(middleDate.getDate() + 15);
                        fechaActual = middleDate.toISOString().split('T')[0];
                    }
                    localStorage.setItem('fechaCalendario', fechaActual);
                    localStorage.setItem('ultimaVistaCalendario', calendar.view.type);
                },

                events: '{{ route('users.verEventos-turnos', $user->id) }}'
            });

            // // ✅ Función para actualizar solo fichajes
            // function actualizarHora({
            //     estado,
            //     fecha,
            //     campo,
            //     hora
            // }) {
            //     const body = {
            //         user_id: "{{ $user->id }}",
            //         fecha_inicio: fecha,
            //         fecha_fin: fecha,
            //         tipo: estado,
            //         [campo]: hora
            //     };

            //     fetch("{{ route('asignaciones-turnos.store') }}", {
            //             method: "POST",
            //             headers: {
            //                 "Content-Type": "application/json",
            //                 "X-CSRF-TOKEN": "{{ csrf_token() }}"
            //             },
            //             body: JSON.stringify(body)
            //         })
            //         .then(res => res.json())
            //         .then(data => {
            //             if (data.success) {
            //                 calendar.refetchEvents();
            //                 setTimeout(actualizarResumenAsistencia, 200);
            //             } else {
            //                 Swal.fire("Error", data.error, "error");
            //             }
            //         })
            //         .catch(err => {
            //             console.error("Error:", err);
            //             Swal.fire("Error", "No se pudo guardar el cambio", "error");
            //         });
            // }

            calendar.render();
        });
    </script>

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
