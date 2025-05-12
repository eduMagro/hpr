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

    <div class="container mx-auto px-4 py-6 space-y-12" id="contenedorCalendarios">

        <!-- Calendario Operarios -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-blue-700 mb-4">Vacaciones · Operarios</h3>
            <div id="calendario-operarios"></div>
        </div>

        <!-- Calendario Oficina -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-yellow-700 mb-4">Vacaciones · Oficina</h3>
            <div id="calendario-oficina"></div>
        </div>
    </div>

    <!-- FullCalendar y dependencias -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const configComun = {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                }
            };

            // Calendario Operarios
            const calendarioOperarios = new FullCalendar.Calendar(document.getElementById('calendario-operarios'), {
                ...configComun,
                events: @json($eventosOperarios)
            });

            // Calendario Oficina
            const calendarioOficina = new FullCalendar.Calendar(document.getElementById('calendario-oficina'), {
                ...configComun,
                events: @json($eventosOficina)
            });

            calendarioOperarios.render();
            calendarioOficina.render();
        });
    </script>
</x-app-layout>
