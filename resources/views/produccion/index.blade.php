<x-app-layout>
    <x-slot name="title">Planificación Producción</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Planificación de Máquinas y Trabajadores') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <!-- Contenedor del Calendario -->
        <div class="w-full bg-white">
            <div id="calendario" class="w-full h-screen"></div>
        </div>
        <!-- Lista de Operarios -->
        <div class="mt-6">
            <h3 class="text-2xl font-semibold text-gray-900 mb-4">Operarios Trabajando</h3>
            <ul class="space-y-2">
                @foreach ($operariosTrabajando as $operario)
                    <li class="px-4 py-2 rounded {{ $operario->estado == 'trabajando' ? 'bg-green-500 text-white' : 'bg-gray-100' }}"
                        data-operario-id="{{ $operario->id }}">
                        <span>{{ $operario->name }}</span>
                        <span>Categoría: {{ $operario->categoria_id }}</span>
                        <span class="text-sm text-gray-600" id="especialidad-{{ $operario->id }}"
                            onclick="activarEdicion({{ $operario->id }}, '{{ $operario->especialidad }}')">
                            {{ $operario->especialidad }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>

    <script>
        let calendar;

        const maquinas = @json($maquinas); // Datos de las máquinas
        const trabajadores = @json($trabajadoresEventos); // Datos de los trabajadores

        console.log(maquinas); // Verificar que las máquinas y soldadoras están correctamente ordenadas
        console.log(trabajadores); // Verificar que las máquinas y soldadoras están correctamente ordenadas

        function crearCalendario(resources, eventosFiltrados) {
            if (calendar) {
                calendar.destroy();
            }

            const vistaGuardada = localStorage.getItem('ultimaVistaCalendario') || 'resourceTimelineDay';
            const fechaGuardada = localStorage.getItem('fechaCalendario');

            calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                locale: 'es',
                initialView: vistaGuardada,
                initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,
                datesSet: function(info) {
                    let fechaActual = info.startStr;

                    if (calendar.view.type === 'dayGridMonth') {
                        const middleDate = new Date(info.start);
                        middleDate.setDate(middleDate.getDate() + 15); // Aproximadamente la mitad del mes
                        fechaActual = middleDate.toISOString().split('T')[0];
                    }

                    localStorage.setItem('fechaCalendario', fechaActual);
                    localStorage.setItem('ultimaVistaCalendario', calendar.view.type);
                },
                displayEventEnd: true,
                eventMinHeight: 30,
                slotMinTime: "00:00:00",
                slotMaxTime: "22:00:00",
                firstDay: 1,
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'resourceTimelineDay,resourceTimelineWeek'
                },
                buttonText: {
                    today: 'Hoy',
                    day: 'Día',
                    week: 'Semana'
                },
                views: {
                    resourceTimelineWeek: {
                        slotDuration: {
                            days: 1
                        },
                        slotLabelFormat: {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'short'
                        }
                    },
                },
                editable: true,
                resources: resources, // Usamos los recursos ordenados
                events: trabajadores,
                resourceAreaColumns: [{
                    field: 'title',
                    headerContent: 'Máquinas'
                }],
                eventDrop: function(info) {
                    const asignacionId = info.event.id; // ID del registro asignaciones_turno
                    const nuevoPuesto = info.newResource.title;

                    fetch(`/asignaciones-turno/${asignacionId}/actualizar-puesto`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                puesto: nuevoPuesto
                            })
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Error al actualizar el puesto');
                            return response.json();
                        })
                        .then(data => {
                            console.log('Puesto actualizado con éxito:', data);
                        })
                        .catch(error => {
                            alert('No se pudo actualizar el puesto');
                            console.error(error);
                            info.revert(); // Deshacer el movimiento si falla
                        });
                },
                eventContent: function(arg) {
                    const props = arg.event.extendedProps;
                    let html = `<div class="px-2 py-1 text-xs font-semibold bg-blue-600 text-white rounded">
                            ${arg.event.title}
                        </div>`;
                    return {
                        html
                    };
                }
            });
            // Forzar el orden de los recursos explícitamente usando setResources
            calendar.render();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar todas las máquinas al cargar
            crearCalendario(maquinas, trabajadores);
        });
    </script>
</x-app-layout>
