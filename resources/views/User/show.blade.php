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
                    <p><strong>Categoría:</strong> <span class="text-gray-600">{{ $user->categoria }}</span></p>
                    <p><strong>Especialidad:</strong> <span class="text-gray-600">{{ $user->especialidad }}</span></p>
                    <p class="mt-3 p-2 bg-blue-100 text-blue-700 rounded-md text-center">
                        <strong>Vacaciones restantes:</strong> {{ $user->dias_vacaciones }}
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
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendario');

            // Cargar eventos y colores desde el backend
            var eventosDesdeLaravel = {!! json_encode($eventos) !!};
            var coloresTurnos = {!! json_encode($coloresTurnos) !!};

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                selectable: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: eventosDesdeLaravel,
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
                        <option value="ninguno">❌ No asignar</option>
                        @foreach ($turnos as $turno)
                            <option value="{{ $turno->nombre }}">{{ ucfirst($turno->nombre) }}</option>
                        @endforeach
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

                            if (tipoSeleccionado === "ninguno") {
                                // Petición para eliminar turnos
                                fetch("{{ route('asignaciones-turnos.destroy') }}", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                        },
                                        body: JSON.stringify({
                                            user_id: "{{ $user->id }}",
                                            fecha_inicio: fechaInicio,
                                            fecha_fin: fechaFin
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            Swal.fire({
                                                title: "Turno eliminado",
                                                text: data.success,
                                                icon: "success",
                                                timer: 2000,
                                                showConfirmButton: false
                                            });

                                            // Eliminar eventos en ese rango de fechas
                                            let eventsToRemove = calendar.getEvents()
                                                .filter(event => {
                                                    let eventDate = event.startStr;
                                                    return eventDate >= fechaInicio &&
                                                        eventDate <= fechaFin;
                                                });

                                            eventsToRemove.forEach(event => event.remove());
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
                                // Petición para asignar turno
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
                                            Swal.fire({
                                                title: "Registrado",
                                                text: data.success,
                                                icon: "success",
                                                timer: 2000,
                                                showConfirmButton: false
                                            });

                                            // Agregar eventos al calendario
                                            let currentDate = new Date(fechaInicio);
                                            let endDate = new Date(fechaFin);
                                            while (currentDate <= endDate) {
                                                // Opcional: omitir sábados (6) y domingos (0)
                                                if (currentDate.getDay() !== 0 &&
                                                    currentDate.getDay() !== 6) {
                                                    let color = coloresTurnos[
                                                        tipoSeleccionado] || {
                                                        bg: '#808080',
                                                        border: '#606060'
                                                    };
                                                    calendar.addEvent({
                                                        title: tipoSeleccionado
                                                            .charAt(0)
                                                        .toUpperCase() +
                                                            tipoSeleccionado.slice(
                                                                1),
                                                        start: currentDate
                                                            .toISOString().split(
                                                                'T')[0],
                                                        backgroundColor: color.bg,
                                                        borderColor: color.border,
                                                        textColor: 'white',
                                                        allDay: true
                                                    });
                                                }
                                                currentDate.setDate(currentDate.getDate() +
                                                    1);
                                            }
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
