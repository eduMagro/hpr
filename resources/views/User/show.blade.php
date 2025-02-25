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
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold mb-2">Información del Usuario</h3>
            <p><strong>Nombre:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Rol:</strong> {{ $user->rol }}</p>
            <p><strong>Categoría:</strong> {{ $user->categoria }}</p>
            <p><strong>Especialidad:</strong> {{ $user->especialidad }}</p>
            <p><strong>Días de vacaciones restantes:</strong> <span
                    id="vacaciones-restantes">{{ $user->dias_vacaciones }}</span></p>
        </div>

        <div class="mt-6 bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold mb-2">Calendario de Fichajes</h3>
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

            // Cargar eventos directamente desde Laravel
            var eventosDesdeLaravel = {!! json_encode($eventos) !!};
            var coloresTurnos = {!! json_encode($coloresTurnos) !!}; // Colores desde el backend

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                selectable: true,
                events: eventosDesdeLaravel, // Usar eventos enviados desde el backend
                select: function(info) {
                    var fechaSeleccionada = info.startStr;

                    Swal.fire({
                        title: "Elige turno para ese día",
                        html: `
                            <select id="tipo-dia" class="swal2-select">
                                <option value="vacaciones">Vacaciones</option>
                                <option value="baja">Baja</option>
                                <option value="mañana">Mañana</option>
                                <option value="tarde">Tarde</option>
                                <option value="noche">Noche</option>
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

                            fetch("{{ route('asignaciones-turnos.store') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },
                                    body: JSON.stringify({
                                        user_id: "{{ $user->id }}",
                                        fecha: fechaSeleccionada,
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

                                        // Obtener colores desde el backend
                                        let color = coloresTurnos[tipoSeleccionado] || {
                                            bg: '#808080',
                                            border: '#606060'
                                        };

                                        // Agregar el nuevo evento sin recargar la página
                                        calendar.addEvent({
                                            title: tipoSeleccionado.charAt(0)
                                                .toUpperCase() + tipoSeleccionado
                                                .slice(1),
                                            start: fechaSeleccionada,
                                            backgroundColor: color.bg,
                                            borderColor: color.border,
                                            textColor: 'white',
                                            allDay: true
                                        });

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
                                        text: "Ocurrió un problema al registrar el día.",
                                        icon: "error"
                                    });
                                });
                        }
                    });
                }
            });

            calendar.render();
        });
    </script>



</x-app-layout>
