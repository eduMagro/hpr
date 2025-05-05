<x-app-layout>
    <x-slot name="title">Calendario de Vacaciones</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('users.index') }}" class="text-blue-600">
                {{ __('Usuarios') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Calendario de Vacaciones Globales') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6" id="contenedorCalendario">
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

            if (!calendarEl) {
                console.error("Elemento 'calendario' no encontrado.");
                return;
            }

            // Convertir los datos de Laravel en JSON válido
            var eventosDesdeLaravel = @json($eventosVacaciones) || [];

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                editable: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                eventDrop: function(info) {
                    const evento = info.event;
                    const data = {
                        id: evento.id,
                        nueva_fecha: evento.startStr
                    };

                    console.log("📦 Evento movido con fetch:", data);

                    fetch('/festivos/editar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify(data)
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error("Respuesta no válida del servidor");
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log("✅ Festivo actualizado:", data);
                            Swal.fire('¡Actualizado!', 'La fecha del festivo se ha cambiado.',
                                'success');
                        })
                        .catch(error => {
                            console.error("❌ Error al guardar:", error);
                            Swal.fire('Error', 'No se pudo guardar el cambio.', 'error');
                            info.revert();
                        });
                },
                events: eventosDesdeLaravel
            });

            calendar.render();
        });
    </script>

</x-app-layout>
