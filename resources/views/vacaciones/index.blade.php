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
        {{-- Maquinistas --}}
        <div>
            <h3 class="text-xl font-semibold text-blue-700 mb-4">📥 Solicitudes pendientes · Maquinistas</h3>
            @if ($solicitudesMaquinistas->isEmpty())
                <p class="text-gray-600">No hay solicitudes pendientes.</p>
            @else
                <x-tabla-solicitudes :solicitudes="$solicitudesMaquinistas" />
            @endif
        </div>
        <!-- Calendario Maquinistas -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-blue-700 mb-4">Vacaciones · Maquinistas</h3>
            <div id="calendario-maquinistas"></div>
        </div>

        {{-- Ferrallas --}}
        <div>
            <h3 class="text-xl font-semibold text-blue-700 mb-4">📥 Solicitudes pendientes · Ferrallas</h3>
            @if ($solicitudesFerrallas->isEmpty())
                <p class="text-gray-600">No hay solicitudes pendientes.</p>
            @else
                <x-tabla-solicitudes :solicitudes="$solicitudesFerrallas" />
            @endif
        </div>
        <!-- Calendario Ferrallas -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-blue-700 mb-4">Vacaciones · Ferrallas</h3>
            <div id="calendario-ferrallas"></div>
        </div>

        {{-- Oficina --}}
        <div>
            <h3 class="text-xl font-semibold text-yellow-700 mb-4">📥 Solicitudes pendientes · Oficina</h3>
            @if ($solicitudesOficina->isEmpty())
                <p class="text-gray-600">No hay solicitudes pendientes.</p>
            @else
                <x-tabla-solicitudes :solicitudes="$solicitudesOficina" />
            @endif
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
                    right: ''
                }
            };

            // Calendario Maquinistas
            const calendarioMaquinistas = new FullCalendar.Calendar(document.getElementById(
                'calendario-maquinistas'), {
                ...configComun,
                events: @json($eventosMaquinistas)
            });
            // Calendario Ferrallas
            const calendarioFerrallas = new FullCalendar.Calendar(document.getElementById('calendario-ferrallas'), {
                ...configComun,
                events: @json($eventosFerrallas)
            });

            // Calendario Oficina
            const calendarioOficina = new FullCalendar.Calendar(document.getElementById('calendario-oficina'), {
                ...configComun,
                events: @json($eventosOficina)
            });

            calendarioMaquinistas.render();
            calendarioFerrallas.render();
            calendarioOficina.render();

        });
    </script>
</x-app-layout>
