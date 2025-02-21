<x-app-layout>
    <x-slot name="title">Calendario de Vacaciones</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Calendario de Vacaciones
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold mb-2">Vacaciones de los Trabajadores</h3>
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

            // ✅ PASANDO JSON DESDE LARAVEL A JAVASCRIPT CORRECTAMENTE
            var eventosDesdeLaravel = {
                !!json_encode($eventosVacaciones) !!
            };

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                events: eventosDesdeLaravel // Mostrar todas las vacaciones
            });

            calendar.render();
        });
    </script>

</x-app-layout>