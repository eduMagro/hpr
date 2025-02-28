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
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-3xl mx-auto border border-gray-200">
            <!-- Encabezado con avatar -->
            <div class="flex items-center space-x-4 border-b pb-4 mb-4">
                <div class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center text-2xl font-bold text-gray-700">
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
                        <p><strong>Faltas injustificadas:</strong> <span class="text-red-600">{{ $faltasInjustificadas }}</span></p>
                        <p><strong>Faltas justificadas:</strong> <span class="text-green-600">{{ $faltasJustificadas }}</span></p>
                        <p><strong>Medias faltas justificadas:</strong> <span class="text-yellow-600">{{ $mediaFaltasJustificadas }}</span></p>
                        <p><strong>Medias faltas injustificadas:</strong> <span class="text-orange-600">{{ $mediaFaltasInjustificadas }}</span></p>
                        <p><strong>Días de baja:</strong> <span class="text-purple-600">{{ $diasBaja }}</span></p>
                    </div>
                </div>
            </div>
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
