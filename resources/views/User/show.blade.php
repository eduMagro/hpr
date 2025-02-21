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
            <p><strong>Días de vacaciones restantes:</strong> {{ $user->dias_vacaciones }}</p>
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

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                selectable: true, // Permitir seleccionar días
                select: function(info) {
                    var fechaSeleccionada = info.startStr;

                    if (confirm("¿Quieres registrar este día como vacaciones?")) {
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
                                    alert(data.success);
                                    document.getElementById("vacaciones-restantes").innerText -= 1;
                                    calendar.refetchEvents(); // Refrescar eventos en el calendario
                                } else {
                                    alert(data.error);
                                }
                            })
                            .catch(error => console.error("Error:", error));
                    }
                }
            });

            calendar.render();
        });
    </script>



</x-app-layout>
