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

            if (!calendarEl) {
                console.error("Elemento 'calendario' no encontrado.");
                return;
            }

            // Cargar eventos desde Laravel (convertidos en JSON correctamente)
            var eventosDesdeLaravel = {
                !!json_encode($eventosVacaciones, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!
            };

            // Verifica en consola si los eventos están bien formateados
            console.log("Eventos cargados:", eventosDesdeLaravel);

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