<x-app-layout>
    <x-slot name="title">Planificación por Obra</x-slot>

    @php
        $rutaActual = request()->route()->getName();
    @endphp

    @if (Auth::check() && Auth::user()->rol == 'oficina')
        <div class="w-full" x-data="{ open: false }">
            <!-- Menú móvil -->
            <div class="sm:hidden relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                    Opciones
                </button>

                <div x-show="open" x-transition @click.away="open = false"
                    class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                    x-cloak>

                    <a href="{{ route('produccion.maquinas') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('users.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📋 Planificación Planillas
                    </a>

                    <a href="{{ route('produccion.trabajadores') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('register') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📋 Planificación Trabajadores Máquina
                    </a>

                    <a href="{{ route('produccion.trabajadoresObra') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        ⏱️ Planificación Trabajadores Obra
                    </a>
                </div>
            </div>

            <!-- Menú escritorio -->
            <div class="hidden sm:flex sm:mt-0 w-full">
                <a href="{{ route('produccion.maquinas') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ request()->routeIs('users.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📋 Planificación Planillas
                </a>

                <a href="{{ route('produccion.trabajadores') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ request()->routeIs('register') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📋 Planificación Trabajadores Máquina
                </a>

                <a href="{{ route('produccion.trabajadoresObra') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    ⏱️ Planificación Trabajadores Obra
                </a>
            </div>
        </div>
    @endif

    <div id="lista-trabajadores" class="p-4 bg-white border rounded shadow w-full md:w-full mt-4">
        <h3 class="font-bold text-gray-800 mb-2">Trabajadores de HPR Servicios</h3>
        <div id="external-events" class="grid grid-cols-2 md:grid-cols-6 gap-2">
            @foreach ($trabajadores as $t)
                <div class="fc-event px-3 py-2 text-xs bg-blue-100 rounded cursor-pointer text-center shadow"
                    data-id="{{ $t->id }}" data-title="{{ $t->nombre_completo }}"
                    data-categoria="{{ $t->categoria?->nombre }}" data-especialidad="{{ $t->maquina?->nombre }}">
                    {{ $t->nombre_completo }}
                    <div class="text-[10px] text-gray-600">
                        {{ $t->categoria?->nombre }} @if ($t->maquina)
                            · {{ $t->maquina?->nombre }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="py-6">
        <!-- Calendario -->
        <div class="w-full bg-white">
            <div id="calendario-obras" class="h-[80vh] w-full"></div>
        </div>
    </div>

    <!-- FullCalendar + Tippy -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

    <script>
        new FullCalendar.Draggable(document.getElementById('external-events'), {
            itemSelector: '.fc-event',
            eventData: function(eventEl) {
                return {
                    title: eventEl.dataset.title,
                    extendedProps: {
                        user_id: eventEl.dataset.id,
                        categoria_nombre: eventEl.dataset.categoria,
                        especialidad_nombre: eventEl.dataset.especialidad
                    }
                };
            }
        });

        let calendarioObras;

        const resources = @json($resources);
        const eventos = @json($eventos);

        function inicializarCalendarioObras() {
            if (calendarioObras) {
                calendarioObras.destroy();
            }

            calendarioObras = new FullCalendar.Calendar(document.getElementById('calendario-obras'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                locale: 'es',
                initialView: 'resourceTimelineWeek',
                firstDay: 1,
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'resourceTimelineDay,resourceTimelineWeek'
                },
                buttonText: {
                    today: 'Hoy',
                    week: 'Semana',
                    day: 'Día'
                },
                editable: true,
                resourceAreaHeaderContent: 'Obras Activas',
                resources: resources,
                events: eventos,
                eventClick(info) {
                    const userId = info.event.extendedProps.user_id;
                    if (userId) {
                        window.location.href = "{{ route('users.show', ':id') }}".replace(':id', userId);
                    }
                },
                eventReceive(info) {
                    // Evitar que FullCalendar agregue el evento automáticamente
                    info.event.remove(); // elimina cualquier evento "fantasma"
                },
                droppable: true,
                drop: function(info) {
                    const userId = info.draggedEl.dataset.id;
                    const obraId = info.resource?.id;
                    const fecha = info.dateStr;

                    // Crea un evento visual provisional (con ID temporal)
                    const eventoTemporal = calendarioObras.addEvent({
                        id: 'temp-' + Date.now() + '-' + userId,
                        title: info.draggedEl.dataset.title,
                        start: fecha + 'T06:00:00',
                        end: fecha + 'T14:00:00',
                        resourceId: obraId,
                        extendedProps: {
                            user_id: userId,
                            provisional: true
                        }
                    });

                    fetch('{{ route('asignaciones-turno.asignarObra') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                user_id: userId,
                                obra_id: obraId,
                                fecha: fecha
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Eliminar cualquier evento existente del mismo user_id y fecha
                                calendarioObras.getEvents().forEach(ev => {
                                    if (ev.extendedProps.user_id == data.user.id && ev.startStr
                                        .startsWith(data.fecha)) {
                                        ev.remove();
                                    }
                                });

                                // Añadir el evento confirmado
                                calendarioObras.addEvent({
                                    id: 'turno-' + data.asignacion.id,
                                    title: data.user.nombre_completo,
                                    start: data.fecha + 'T06:00:00',
                                    end: data.fecha + 'T14:00:00',
                                    resourceId: data.obra_id,
                                    extendedProps: {
                                        user_id: data.user.id,
                                        categoria_nombre: data.user.categoria?.nombre,
                                        especialidad_nombre: data.user.maquina?.nombre
                                    }
                                });

                                eventoTemporal.remove();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message
                                });
                                eventoTemporal.remove();
                            }
                        })
                        .catch(error => {
                            console.error('❌ Error en la solicitud:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de red',
                                text: 'No se pudo completar la solicitud.'
                            });
                            eventoTemporal.remove();
                        });
                },
                eventDidMount(info) {
                    info.el.addEventListener('contextmenu', function(e) {
                        e.preventDefault(); // Evita el menú del navegador

                        Swal.fire({
                            title: '¿Eliminar asignación de obra?',
                            text: "Esto quitará la obra del trabajador en ese turno.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, eliminar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const asignacionId = info.event.id.replace('turno-', '');

                                fetch(`/asignaciones-turno/${asignacionId}/quitar-obra`, {
                                        method: 'PUT',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        }
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            info.event.remove();

                                        } else {
                                            Swal.fire('❌ Error', data.message, 'error');
                                        }
                                    })
                                    .catch(err => {
                                        console.error(err);
                                        Swal.fire('❌ Error', 'No se pudo quitar la obra.',
                                            'error');
                                    });
                            }
                        });
                    });
                },
                eventContent(arg) {
                    const props = arg.event.extendedProps;
                    return {
                        html: `
                            <div class="px-2 py-1 text-xs font-semibold">
                                <span>${arg.event.title}</span>
                                <span class="block text-[10px] opacity-80">${props.categoria_nombre ?? ''} ${props.especialidad_nombre ? '· ' + props.especialidad_nombre : ''}</span>
                            </div>
                        `
                    };
                },
                views: {
                    resourceTimelineDay: {
                        slotMinTime: '06:00:00',
                        slotMaxTime: '22:00:00',
                    },
                    resourceTimelineWeek: {
                        slotDuration: {
                            days: 1
                        },
                        slotLabelFormat: {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'short'
                        }
                    }
                },
                resourceAreaColumns: [{
                    field: 'title',
                    headerContent: 'Obra'
                }],
                eventDrop(info) {
                    const asignacionId = info.event.id.replace('turno-', '');
                    const nuevaObraId = info.event.getResources()[0].id;
                    const nuevaFecha = info.event.startStr.split('T')[0];

                    fetch(`/asignaciones-turnos/${asignacionId}/update-obra`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                obra_id: nuevaObraId,
                                fecha: nuevaFecha
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error al actualizar');
                            }
                            return response.json();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            info.revert();
                        });
                }
            });

            calendarioObras.render();
        }

        document.addEventListener('DOMContentLoaded', inicializarCalendarioObras);
    </script>
</x-app-layout>
