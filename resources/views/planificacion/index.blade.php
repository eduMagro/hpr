<x-app-layout>
    <x-slot name="title">Planificación - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Planificación de Salidas') }}
        </h2>
    </x-slot>


    <!-- 📌 Tabla de Obras (a la Izquierda) -->
    <div class="w-1/4 bg-white p-4 shadow-md border">
        <h3 class="text-lg font-semibold mb-3">Obras con Salidas</h3>
        <table class="w-full border">
            <thead>
                <tr class="bg-gray-200 border-b">
                    <th class="px-4 py-2 text-left">Obra</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($obrasConSalidas as $obra)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $obra->planillas->user->name }}</td>
                        <td class="px-4 py-2">{{ $obra->cod_obra }}</td>
                        <td class="px-4 py-2">{{ $obra->obra }}</td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 📅 Calendario (a la Derecha) -->

    <div class="container mx-auto px-4 py-6" id="contenedorCalendario">
        <div class="bg-white rounded-lg shadow-lg">
            <div id="calendario"></div>
        </div>

    </div>



    <!-- FullCalendar -->
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

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: @json($eventos)
            });

            calendar.render();
        });
    </script>

</x-app-layout>
