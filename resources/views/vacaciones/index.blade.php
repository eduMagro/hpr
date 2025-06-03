<x-app-layout>
    <x-slot name="title">Calendario de Vacaciones</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('users.index') }}" class="text-blue-600">
                {{ __('Usuarios') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Vacaciones') }}
        </h2>
    </x-slot>

    <div class="w-full max-w-7xl mx-auto py-6 space-y-12" id="contenedorCalendarios">

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
        <div class="w-full bg-white rounded-lg shadow-lg p-4 sm:p-6">

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
        <div class="w-full bg-white rounded-lg shadow-lg p-4 sm:p-6">

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
        <div class="w-full bg-white rounded-lg shadow-lg p-4 sm:p-6">

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
                editable: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                }
            };

            function crearCalendario(idElemento, eventos) {
                return new FullCalendar.Calendar(document.getElementById(idElemento), {
                    ...configComun,
                    events: eventos,

                    eventDrop: function(info) {
                        const fecha = info.event.startStr;
                        const idUsuario = info.event.extendedProps.user_id;

                        fetch(`/vacaciones/reprogramar`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': token
                                },
                                body: JSON.stringify({
                                    user_id: idUsuario,
                                    fecha_original: info.oldEvent.startStr,
                                    nueva_fecha: fecha
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (!data.success) {
                                    Swal.fire("❌ Error", data.error || "No se pudo reprogramar",
                                        "error");
                                    info.revert();
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                Swal.fire("❌ Error", "Error de conexión", "error");
                                info.revert();
                            });
                    },

                    eventClick: function(info) {
                        // Clic izquierdo: ir a ficha de usuario
                        const userId = info.event.extendedProps.user_id;
                        window.location.href = `/users/${userId}`;
                    },

                    eventDidMount: function(info) {
                        // Clic derecho: confirmar eliminar vacaciones
                        info.el.addEventListener('contextmenu', function(ev) {
                            ev.preventDefault();
                            Swal.fire({
                                title: `¿Eliminar vacaciones de ${info.event.title}?`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, eliminar',
                                cancelButtonText: 'Cancelar'
                            }).then(result => {
                                if (result.isConfirmed) {
                                    fetch(`/vacaciones/eliminar-evento`, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': token
                                            },
                                            body: JSON.stringify({
                                                user_id: info.event
                                                    .extendedProps.user_id,
                                                fecha: info.event.startStr
                                            })
                                        })
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data.success) {
                                                info.event.remove();
                                                Swal.fire('Eliminado',
                                                    'Vacaciones eliminadas correctamente.',
                                                    'success');
                                            } else {
                                                Swal.fire("❌ Error", data.error ||
                                                    "No se pudo eliminar",
                                                    "error");
                                            }
                                        })
                                        .catch(() => {
                                            Swal.fire("❌ Error",
                                                "Error de conexión", "error");
                                        });
                                }
                            });
                        });
                    }
                });
            }

            const calendarioMaquinistas = crearCalendario('calendario-maquinistas', @json($eventosMaquinistas));
            const calendarioFerrallas = crearCalendario('calendario-ferrallas', @json($eventosFerrallas));
            const calendarioOficina = crearCalendario('calendario-oficina', @json($eventosOficina));

            calendarioMaquinistas.render();
            calendarioFerrallas.render();
            calendarioOficina.render();
        });
    </script>


</x-app-layout>
