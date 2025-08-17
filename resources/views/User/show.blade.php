<x-app-layout>
    <x-slot name="title">Detalles de {{ $user->name }}</x-slot>
    <x-menu.usuarios :totalSolicitudesPendientes="$totalSolicitudesPendientes ?? 0" />

    <div class="container mx-auto px-4 py-6">

        <x-ficha-trabajador :user="$user" :resumen="$resumen" />

        <div class="bg-white rounded-lg shadow-lg">
            <div id="calendario"></div>
        </div>
    </div>

    <!-- Cargar FullCalendar con prioridad -->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        function actualizarResumenAsistencia() {
            fetch("{{ route('users.verResumen-asistencia', ['user' => $user->id]) }}")
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    const div = document.getElementById('resumen-asistencia');
                    if (div) {
                        div.style.opacity = 0.5;
                        div.innerHTML = `
                            <p><strong>Vacaciones asignadas: </strong> ${data.diasVacaciones}</p>
                            <p><strong>Faltas injustificadas: </strong> ${data.faltasInjustificadas}</p>
                            <p><strong>Faltas justificadas: </strong> ${data.faltasJustificadas}</p>
                            <p><strong>Días de baja: </strong> ${data.diasBaja}</p>
                        `;
                        setTimeout(() => div.style.opacity = 1, 200);
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar asistencias:', error);
                });
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

</x-app-layout>
