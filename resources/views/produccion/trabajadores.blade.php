<x-app-layout>
    <x-slot name="title">Planificación Producción</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Planificación de Trabajadores') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <!-- Contenedor del Calendario -->
        <div class="w-full bg-white">
            <div id="calendario" class="h-[80vh] w-full"></div>
        </div>
        <!-- Tabla de Operarios -->
        <div class="mt-8">
            <h3 class="text-2xl font-semibold text-gray-900 mb-4">Operarios que trabajan hoy</h3>
            <div class="overflow-x-auto rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200 bg-white">
                    <thead class="bg-gray-50">

                        <tr>
                            <th class="px-4 py-2 text-left text-sm text-gray-600">Nombre</th>
                            <th class="px-4 py-2 text-left text-sm text-gray-600">Categoría</th>
                            <th class="px-4 py-2 text-left text-sm text-gray-600">Especialidad</th>
                            <th class="px-4 py-2 text-left text-sm text-gray-600">Puesto asignado</th>
                            <th class="px-4 py-2 text-left text-sm text-gray-600">Turno</th>
                            <th class="px-4 py-2 text-left text-sm text-gray-600">Entrada</th>
                            <th class="px-4 py-2 text-left text-sm text-gray-600">Salida</th>
                            <th class="px-4 py-2 text-left text-sm text-gray-600">Estado</th>
                            <th class="px-4 py-2 text-left text-sm text-gray-600">Evento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($operariosTrabajando as $operario)
                            @php
                                $turno = $operario->asignacionesTurnos()->whereDate('fecha', today())->first();
                                $puestoAsignado = $turno->maquina?->nombre ?? '—';

                                $turnoNombre = $turno->turno->nombre ?? '—';
                                $tieneEvento = collect($trabajadoresEventos)->contains(function ($evento) use (
                                    $operario,
                                ) {
                                    return isset($evento['resourceId']) && $evento['title'] === $operario->name;
                                });
                            @endphp
                            <tr class="{{ $operario->estado == 'trabajando' ? 'bg-green-100' : '' }}">
                                <td class="px-4 py-2 text-sm text-gray-900 font-medium">{{ $operario->name }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    {{ $operario->categoria->nombre ?? $operario->categoria_id }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    <span id="maquina-{{ $operario->id }}"
                                        onclick="activarEdicion({{ $operario->id }}, '{{ $operario->maquina_id }}')">
                                        {{ $operario->maquina->nombre ?? 'Sin asignar' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $puestoAsignado }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $turnoNombre }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    {{ $registroFichajes[$operario->id]['entrada'] ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    {{ $registroFichajes[$operario->id]['salida'] ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700 capitalize">{{ $operario->estado }}</td>
                                <td class="px-4 py-2 text-sm">
                                    @if ($tieneEvento)
                                        <span class="text-green-600 font-bold">✅</span>
                                    @else
                                        <span class="text-red-500 font-bold">❌</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- FullCalendar Scheduler con marca de agua -->
    <!-- ✅ FullCalendar Scheduler completo con vista resourceTimelineWeek -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    {{-- TOOLTIP --}}
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

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
                firstDay: 1,
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title'
                },
                buttonText: {
                    today: 'Hoy'
                },
                slotLabelDidMount: function(info) {
                    const viewType = info.view.type;

                    if (viewType === 'resourceTimelineDay') {
                        const hour = parseInt(info.date.getHours());

                        if (hour >= 6 && hour < 14) {
                            info.el.style.backgroundColor = '#a7f3d0'; // verde claro
                        } else if (hour >= 14 && hour < 22) {
                            info.el.style.backgroundColor = '#bfdbfe'; // azul claro
                        } else {
                            info.el.style.backgroundColor = '#fde68a'; // amarillo claro
                        }

                        info.el.style.borderRight = '1px solid #e5e7eb';
                    }
                }, // eventOrder: 'orden',
                views: {
                    resourceTimelineDay: {
                        slotMinTime: '00:00:00',
                        slotMaxTime: '23:59:00',
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
                editable: true,
                resources: resources, // Usamos los recursos ordenados
                resourceAreaWidth: '200px',
                events: trabajadores,
                resourceAreaColumns: [{
                    field: 'title',
                    headerContent: 'Máquinas'
                }],
                eventDrop: function(info) {
                    const asignacionId = info.event.id.replace(/^turno-/, ''); // Elimina el prefijo "turno-"
                    const nuevoMaquinaId = parseInt(info.event.getResources()[0]?.id, 10); // Convierte a número

                    fetch(`/asignaciones-turno/${asignacionId}/actualizar-puesto`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                maquina_id: nuevoMaquinaId
                            })
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Error al actualizar la máquina asignada');
                            return response.json();
                        })
                        .then(data => {
                            console.log('Máquina actualizada con éxito:', data);
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo actualizar la máquina asignada.'
                            });
                            console.error(error);
                            info.revert();
                        });
                },
                eventClick: function(info) {
                    const userId = info.event.extendedProps.user_id;
                    if (userId) {
                        const url = "{{ route('users.show', ':id') }}".replace(':id', userId);
                        window.location.href = url;
                    }
                },
                eventContent: function(arg) {
                    const props = arg.event.extendedProps;
                    let html = `
                        <div class="px-2 py-1 text-xs font-semibold bg-blue-600 text-white rounded flex items-center gap-1">
                            <span>${arg.event.title}</span>
                            <span class="text-[10px] font-normal opacity-80">(${props.categoria_nombre ?? ''})</span>
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
