<x-app-layout>
    <x-slot name="title">Detalles de {{ $user->name }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('users.index') }}" class="text-blue-600">
                {{ __('Usuarios') }}
            </a>
            <span class="mx-2">/</span>
            {{ $user->name }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-3xl mx-auto mb-6 border border-gray-200">
            <!-- Encabezado con avatar -->
            <div class="flex items-center space-x-4 border-b pb-4 mb-4">
                <div
                    class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center text-2xl font-bold text-gray-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-xl font-semibold">{{ $user->name }}</h3>
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
                    <p id="vacaciones-restantes" class="mt-3 p-2 bg-blue-100 text-blue-700 rounded-md text-center">
                        <strong>Vacaciones restantes:</strong> {{ $diasVacaciones }}
                    </p>

                </div>

                <!-- Resumen de asistencias -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Asistencias</h3>
                    <div class="bg-gray-100 p-3 rounded-lg">
                        <p><strong>Faltas injustificadas:</strong> <span
                                class="text-red-600">{{ $faltasInjustificadas }}</span></p>
                        <p><strong>Faltas justificadas:</strong> <span
                                class="text-green-600">{{ $faltasJustificadas }}</span></p>
                        <p><strong>Días de baja:</strong> <span class="text-purple-600">{{ $diasBaja }}</span></p>
                    </div>
                </div>
            </div>
        </div>


        <div class="bg-white rounded-lg shadow-lg">
            <div id="calendario"></div>
        </div>
    </div>

    <!-- Cargar FullCalendar con prioridad -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>

    <script>
        function actualizarVacacionesRestantes() {
            fetch('{{ route('users.vacaciones-restantes', $user->id) }}')
                .then(response => response.json())
                .then(data => {
                    const div = document.getElementById('vacaciones-restantes');
                    if (div && data.dias !== undefined) {
                        div.innerHTML = `<strong>Vacaciones restantes:</strong> ${data.dias}`;
                    }
                })
                .catch(error => console.error('Error al actualizar vacaciones:', error));
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendario');

            // ✅ Recuperar vista y fecha guardadas
            const vistaGuardada = localStorage.getItem('ultimaVistaCalendario') || 'dayGridMonth';
            const fechaGuardada = localStorage.getItem('fechaCalendario');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,
                locale: 'es',
                height: 'auto',
                selectable: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                // ✅ Guardar la vista y fecha actual cada vez que cambia
                datesSet: function(info) {
                    let fechaActual = info.startStr;

                    if (calendar.view.type === 'dayGridMonth') {
                        const middleDate = new Date(info.start);
                        middleDate.setDate(middleDate.getDate() + 15); // Aproximadamente mitad del mes
                        fechaActual = middleDate.toISOString().split('T')[0];
                    }

                    localStorage.setItem('fechaCalendario', fechaActual);
                    localStorage.setItem('ultimaVistaCalendario', calendar.view.type);
                },
                events: '{{ route('users.eventos-turnos', $user->id) }}',
                select: function(info) {
                    // Obtener fechas inicial y final
                    let fechaInicio = info.startStr;
                    let fechaFinObj = new Date(info.end);
                    fechaFinObj.setDate(fechaFinObj.getDate()); // Ajuste para que sea inclusiva
                    let fechaFin = fechaFinObj.toISOString().split('T')[0];

                    // Generar HTML condicionalmente según si se selecciona un solo día o un rango
                    let mensajeFecha = fechaInicio === fechaFin ?
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
                        preConfirm: () => {
                            return document.getElementById("tipo-dia").value;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let tipoSeleccionado = result.value;

                            if (tipoSeleccionado === "eliminarTurnoEstado" ||
                                tipoSeleccionado === "eliminarEstado") {
                                let eventosEnRango = calendar.getEvents().filter(event => {
                                    let eventDate = event.startStr;
                                    return eventDate >= fechaInicio && eventDate <=
                                        fechaFin;
                                });

                                // Detectar si es tipo "festivo" (todos los eventos en rango con título "Festivo")
                                let todosSonFestivo = eventosEnRango.length > 0 &&
                                    eventosEnRango.every(e => e.title?.toLowerCase() ===
                                        "festivo");

                                let body = {
                                    fecha_inicio: fechaInicio,
                                    fecha_fin: fechaFin
                                };

                                if (todosSonFestivo) {
                                    body.tipo_turno =
                                        "festivo"; // eliminar de todos los usuarios
                                } else {
                                    body.user_id =
                                        "{{ $user->id }}"; // solo del usuario actual

                                    // ✅ Para diferenciar si es eliminar turno o eliminar estado
                                    body.tipo =
                                        tipoSeleccionado; // directamente lo que viene del select

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

                                            eventosEnRango.forEach(event => event.remove());
                                            calendar.refetchEvents();
                                            actualizarVacacionesRestantes();

                                        } else {
                                            Swal.fire({
                                                title: "Error",
                                                text: data.error,
                                                icon: "error"
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error:", error);
                                        Swal.fire({
                                            title: "Error",
                                            text: "Ocurrió un problema al eliminar los turnos.",
                                            icon: "error"
                                        });
                                    });

                            } else {
                                // Petición para asignar turno o estado
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
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            // ✅ Recarga completa de la página
                                            calendar.refetchEvents();
                                            actualizarVacacionesRestantes();

                                        } else {
                                            Swal.fire({
                                                title: "Error",
                                                text: data.error,
                                                icon: "error"
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error:", error);
                                        Swal.fire({
                                            title: "Error",
                                            text: "Ocurrió un problema al registrar los turnos.",
                                            icon: "error"
                                        });
                                    });
                            }

                        }
                    });
                }
            });

            calendar.render();
        });
    </script>
</x-app-layout>
