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

                <a href="{{ route('produccion.maquinas') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ request()->routeIs('register') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📋 Planificación Trabajadores Almacén
                </a>

                <a href="{{ route('produccion.trabajadoresObra') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    ⏱️ Planificación Trabajadores Obra
                </a>
            </div>
        </div>
    @endif
    <div id="lista-trabajadores" class="p-4 bg-white border rounded shadow w-full md:w-full">
        <h3 class="font-bold text-gray-800 mb-2">Trabajadores sin asignar</h3>

        <div id="external-events" class="grid grid-cols-2 md:grid-cols-6 gap-2">
            @foreach ($trabajadoresSinObra as $t)
                <div class="fc-event px-3 py-2 text-xs bg-blue-100 rounded cursor-pointer text-center shadow"
                    data-id="{{ $t->id }}" data-title="{{ $t->nombre_completo }}">
                    {{ $t->nombre_completo }}
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
                        user_id: eventEl.dataset.id
                    }
                };
            }
        });

        let calendarioObras;

        const resources = @json($resources);
        const trabajadores = @json($eventos);
        console.log(trabajadores);

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
                    right: ''
                },
                editable: true,
                resourceAreaHeaderContent: 'Obras Activas',
                resources: resources,
                events: trabajadores,
                eventClick(info) {
                    const userId = info.event.extendedProps.user_id;
                    if (userId) {
                        window.location.href = "{{ route('users.show', ':id') }}".replace(':id', userId);
                    }
                },
                droppable: true, // Permite soltar eventos desde fuera
                drop: function(info) {
                    const userId = info.draggedEl.dataset.id;
                    const obraId = info.resource?.id;
                    const fecha = info.dateStr;
                    const hora = info.date.getHours();

                    let turnoId = null;
                    if (hora >= 6 && hora < 14) turnoId = 1;
                    else if (hora >= 14 && hora < 22) turnoId = 2;
                    else turnoId = 3;

                    fetch('/asignaciones-turno/asignar-obra', {

                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                user_id: userId,
                                obra_id: obraId,
                                fecha: fecha,
                                turno_id: turnoId
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (!data || !data.id || !data.hora_entrada || !data.hora_salida) {
                                console.error('❌ Datos incompletos recibidos:', data);
                                return;
                            }

                            calendarioObras.addEvent({
                                id: 'turno-' + data.id,
                                title: data.nombre,
                                start: fecha + 'T' + data.hora_entrada,
                                end: fecha + 'T' + data.hora_salida,
                                resourceId: obraId,
                                extendedProps: {
                                    user_id: userId,
                                    categoria_nombre: data.categoria,
                                    especialidad_nombre: data.especialidad
                                }
                            });

                            // Opcional: eliminar de la lista externa si quieres
                            document.querySelector(`[data-id="${userId}"]`)?.remove();
                        });
                },
                eventContent(arg) {
                    const props = arg.event.extendedProps;
                    return {
                        html: `
                            <div class="px-2 py-1 text-xs font-semibold">
                                <span>${arg.event.title}</span>
                                <span class="block text-[10px] opacity-80">${props.categoria_nombre ?? ''} · ${props.especialidad_nombre ?? ''}</span>
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
                    // Aquí puedes hacer una actualización similar al cambio de máquina
                    const nuevaObraId = info.event.getResources()?.[0]?.id;
                    const asignacionId = info.event.id.replace(/^turno-/, '');
                    const nuevaFecha = info.event.startStr;

                    fetch('/asignaciones-turno/asignar-obra', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                user_id: userId,
                                obra_id: obraId,
                                fecha: fecha
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('✅ Asignación de obra actualizada:', data);
                                // Puedes opcionalmente añadir el evento al calendario si era nuevo
                            } else {
                                throw new Error(data.error || 'Error desconocido');
                            }
                        })
                        .catch(error => {
                            console.error('❌ Error al asignar obra:', error);
                            info.revert();
                        });

                }
            });

            calendarioObras.render();
        }

        document.addEventListener('DOMContentLoaded', () => {
            inicializarCalendarioObras();
        });
    </script>
</x-app-layout>
