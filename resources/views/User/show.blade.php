<x-app-layout>
    <x-slot name="title">Detalles de {{ $user->name }}</x-slot>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @php
        $rutaActual = request()->route()->getName();
    @endphp
    @if (Auth::check() && Auth::user()->rol == 'oficina')
        <div class="w-full" x-data="{ open: false }">
            <!-- Menú móvil -->
            <div class="sm:hidden relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                    Opciones
                </button>

                <div x-show="open" x-transition @click.away="open = false"
                    class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                    x-cloak>

                    <a href="{{ route('users.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('users.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📋 Usuarios
                    </a>

                    <a href="{{ route('register') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('register') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📋 Registrar Usuario
                    </a>

                    <a href="{{ route('vacaciones.index') }}"
                        class="relative block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('vacaciones.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🌴 Vacaciones
                        @isset($totalSolicitudesPendientes)
                            @if ($totalSolicitudesPendientes > 0)
                                <span
                                    class="absolute top-2 right-4 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                    {{ $totalSolicitudesPendientes }}
                                </span>
                            @endif
                        @endisset
                    </a>

                    <a href="{{ route('asignaciones-turnos.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        ⏱️ Registros
                    </a>
                </div>
            </div>

            <!-- Menú escritorio -->
            <div class="hidden sm:flex sm:mt-0 w-full">
                <a href="{{ route('users.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ request()->routeIs('users.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📋 Usuarios
                </a>

                <a href="{{ route('register') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ request()->routeIs('register') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📋 Registrar Usuario
                </a>

                <a href="{{ route('vacaciones.index') }}"
                    class="relative flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ request()->routeIs('vacaciones.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🌴 Vacaciones
                    @isset($totalSolicitudesPendientes)
                        @if ($totalSolicitudesPendientes > 0)
                            <span
                                class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow">
                                {{ $totalSolicitudesPendientes }}
                            </span>
                        @endif
                    @endisset
                </a>

                <a href="{{ route('asignaciones-turnos.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    ⏱️ Registros Entrada y Salida
                </a>
            </div>
        </div>

    @endif

    <div class="container mx-auto px-4 py-6">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-3xl mx-auto mb-6 border border-gray-200">
            <!-- Encabezado con avatar -->
            <div class="flex items-center space-x-4 border-b pb-4 mb-4">
                <div
                    class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center text-2xl font-bold text-gray-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-xl font-semibold">{{ $user->nombre_completo }}</h3>
                    <p class="text-gray-500 text-sm">{{ $user->rol }}</p>
                </div>
            </div>

            <!-- Contenido en dos columnas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Información del usuario -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Información</h3>
                    <p><strong>Email:</strong> <span class="text-gray-600">{{ $user->email }}</span></p>
                    <p><strong>Categoría:</strong> <span
                            class="text-gray-600">{{ $user->categoria->nombre ?? 'N/A' }}</span></p>
                    <p><strong>Especialidad:</strong> <span
                            class="text-gray-600">{{ $user->maquina->nombre ?? 'N/A' }}</span></p>
                </div>

                <!-- Resumen de asistencias -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Asistencias</h3>
                    <div id="resumen-asistencia" class="bg-gray-100 p-3 rounded-lg">
                        <p><strong>Vacaciones asignadas: </strong> {{ $resumen['diasVacaciones'] }}</p>
                        <p><strong>Faltas injustificadas: </strong> {{ $resumen['faltasInjustificadas'] }}</p>
                        <p><strong>Faltas justificadas: </strong> {{ $resumen['faltasJustificadas'] }}</p>
                        <p><strong>Días de baja: </strong> {{ $resumen['diasBaja'] }}</p>
                    </div>
                </div>
            </div>
        </div>

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
            fetch("{{ route('users.resumen-asistencia', ['user' => $user->id]) }}")
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

            const vistaGuardada = localStorage.getItem('ultimaVistaCalendario') || 'dayGridMonth';
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
                    const evento = info.event;
                    const props = evento.extendedProps;

                    if (!props || !props.asignacion_id || !props.tipo) return;

                    Swal.fire({
                        title: `Editar ${props.tipo === 'salida' ? 'Salida' : 'Entrada'}`,
                        html: `
                        <p><strong>Fecha:</strong> ${props.fecha}</p>
                        <input type="time" id="nuevaHora" class="swal2-input" value="${props.tipo === 'salida' ? props.salida : props.entrada}">
                    `,
                        showDenyButton: true,
                        denyButtonText: 'Borrar',
                        showCancelButton: true,
                        confirmButtonText: 'Guardar',
                        cancelButtonText: 'Cancelar',
                        preConfirm: () => ({
                            hora: document.getElementById('nuevaHora').value,
                            campo: props.tipo,
                            fecha: props.fecha,
                            estado: props.estado_original ?? 'activo'
                        })
                    }).then(({
                        isConfirmed,
                        isDenied,
                        value
                    }) => {
                        if (!isConfirmed && !isDenied) return;

                        actualizarHora({
                            estado: value.estado,
                            fecha: value.fecha,
                            campo: value.campo,
                            hora: isDenied ? null : value.hora
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

                events: '{{ route('users.eventos-turnos', $user->id) }}'
            });

            // ✅ Función para actualizar solo fichajes
            function actualizarHora({
                estado,
                fecha,
                campo,
                hora
            }) {
                const body = {
                    user_id: "{{ $user->id }}",
                    fecha_inicio: fecha,
                    fecha_fin: fecha,
                    tipo: estado,
                    [campo]: hora
                };

                fetch("{{ route('asignaciones-turnos.store') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify(body)
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
                        Swal.fire("Error", "No se pudo guardar el cambio", "error");
                    });
            }

            calendar.render();
        });
    </script>

</x-app-layout>
