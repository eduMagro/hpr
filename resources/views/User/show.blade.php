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

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                selectable: true,
                events: eventosDesdeLaravel, // Usar eventos enviados desde el backend
                select: function(info) {
                    var fechaSeleccionada = info.startStr;

                    Swal.fire({
                        title: "¿Registrar vacaciones?",
                        text: `¿Quieres registrar el ${fechaSeleccionada} como vacaciones?`,
                        icon: "question",
                        showCancelButton: true,
                        confirmButtonText: "Sí, registrar",
                        cancelButtonText: "Cancelar"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ route('vacaciones.store') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },
                                    body: JSON.stringify({
                                        user_id: "{{ $user->id }}",
                                        fecha: fechaSeleccionada
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

                                        let vacacionesRestantesElement = document
                                            .getElementById("vacaciones-restantes");
                                        if (vacacionesRestantesElement) {
                                            let diasRestantes = parseInt(
                                                vacacionesRestantesElement.innerText);
                                            if (!isNaN(diasRestantes)) {
                                                vacacionesRestantesElement.innerText =
                                                    diasRestantes - 1;
                                            }
                                        }

                                        // Agregar el nuevo evento de vacaciones al calendario sin recargar
                                        calendar.addEvent({
                                            title: 'Vacaciones',
                                            start: fechaSeleccionada,
                                            backgroundColor: '#f87171',
                                            borderColor: '#dc2626',
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
                                        text: "Ocurrió un problema al registrar las vacaciones.",
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
