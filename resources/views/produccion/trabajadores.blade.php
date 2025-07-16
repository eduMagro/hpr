<x-app-layout>
    <x-slot name="title">Planificación Producción</x-slot>
    <x-menu.planificacion />

    <div class="py-6">
        <!-- Contenedor del Calendario -->
        <div class="w-full bg-white">
            <div id="calendario" class="h-[80vh] w-full"></div>
        </div>

        {{-- <!-- Tabla de Operarios -->
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
                                <td class="px-4 py-2 text-sm text-gray-900 font-medium">
                                    {{ trim("{$operario->name} {$operario->primer_apellido} {$operario->segundo_apellido}") }}
                                </td>

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
        </div> --}}

    </div>

    <!-- ✅ FullCalendar Scheduler completo con vista resourceTimelineWeek -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    {{-- TOOLTIP --}}
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light.css" />


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

            const vistasValidas = ['resourceTimelineDay', 'resourceTimelineWeek'];
            let vistaGuardada = localStorage.getItem('ultimaVistaCalendario');
            if (!vistasValidas.includes(vistaGuardada)) {
                vistaGuardada = 'resourceTimelineDay';
            }

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
                    center: 'title',
                    right: ''
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
                        slotMaxTime: '21:59:00',
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
                resourceAreaWidth: '100px',
                resourceLabelDidMount: function(info) {
                    const color = info.resource.extendedProps.backgroundColor;
                    if (color) {
                        info.el.style.backgroundColor = color;
                        info.el.style.color = '#fff';
                    }
                },
                events: trabajadores,
                resourceAreaColumns: [{
                    field: 'title',
                    headerContent: 'Máquinas'
                }],
                eventDrop: function(info) {
                    const asignacionId = info.event.id.replace(/^turno-/, '');
                    const recurso = info.event.getResources()?.[0];
                    const nuevoMaquinaId = recurso ? parseInt(recurso.id, 10) : null;
                    const nuevaHoraInicio = info.event.start?.toISOString();
                    let turnoId = null;
                    const hora = new Date(nuevaHoraInicio).getHours();

                    if (hora >= 6 && hora < 14) {
                        turnoId = 1; // mañana
                    } else if (hora >= 14 && hora < 22) {
                        turnoId = 2; // tarde
                    } else {
                        turnoId = 3; // noche
                    }

                    if (!nuevoMaquinaId || !nuevaHoraInicio) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Datos incompletos',
                            text: 'No se pudo determinar la máquina o la hora de inicio.'
                        });
                        info.revert();
                        return;
                    }

                    fetch(`/asignaciones-turno/${asignacionId}/actualizar-puesto`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                maquina_id: nuevoMaquinaId,
                                start: nuevaHoraInicio,
                                turno_id: turnoId
                            })
                        })
                        .then(response => response.json().then(data => ({
                            ok: response.ok,
                            status: response.status,
                            data
                        })))
                        .then(({
                            ok,
                            status,
                            data
                        }) => {
                            if (!ok) {
                                throw new Error(data?.message || `Error ${status}`);
                            }
                            console.log('✅ Asignación actualizada:', data);

                            // 🔥 ACTUALIZAR COLOR DEL EVENTO SIN RECARGAR
                            if (data.color) {
                                info.event.setProp('backgroundColor', data.color);
                                info.event.setProp('borderColor', data.color);
                            }
                            if (typeof data.nuevo_obra_id !== 'undefined') {
                                info.event.setExtendedProp('obra_id', data.nuevo_obra_id);
                            }

                        })
                        .catch(error => {
                            console.error('❌ Error al actualizar:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al guardar',
                                text: error.message || 'Ocurrió un error inesperado.'
                            });
                            info.revert();
                        });
                },
                eventDidMount: function(info) {
                    const foto = info.event.extendedProps.foto;

                    const content = `
    <img src="${foto}" class="w-18 h-18 rounded-full object-cover ring-2 ring-blue-400 shadow-lg">
`;

                    tippy(info.el, {
                        content: content,
                        allowHTML: true,
                        placement: 'top',
                        theme: 'transparent-avatar',
                        interactive: false,
                        arrow: false,
                        delay: [100, 0],
                        offset: [0, 10],
                    });
                    // 👉 Listener para clic derecho
                    info.el.addEventListener('contextmenu', function(e) {
                        e.preventDefault(); // evitar el menú contextual por defecto

                        const evento = info.event;
                        const props = evento.extendedProps;
                        const entradaActual = props.entrada || '';
                        const salidaActual = props.salida || '';

                        Swal.fire({
                            title: 'Editar fichaje',
                            html: `
                <div class="flex flex-col gap-3">
                    <label class="text-left text-sm">Entrada</label>
                    <input id="entradaHora" type="time" class="swal2-input" value="${entradaActual}">
                    <label class="text-left text-sm">Salida</label>
                    <input id="salidaHora" type="time" class="swal2-input" value="${salidaActual}">
                </div>
            `,
                            showCancelButton: true,
                            confirmButtonText: 'Guardar',
                            cancelButtonText: 'Cancelar',
                            preConfirm: () => {
                                const entradaNueva = document.getElementById('entradaHora')
                                    .value;
                                const salidaNueva = document.getElementById('salidaHora')
                                    .value;

                                if (!entradaNueva && !salidaNueva) {
                                    Swal.showValidationMessage(
                                        'Debes indicar al menos una hora');
                                    return false;
                                }

                                return {
                                    entrada: entradaNueva,
                                    salida: salidaNueva
                                };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const {
                                    entrada,
                                    salida
                                } = result.value;
                                const idLimpio = info.event.id.toString().replace(/^turno-/,
                                    '');
                                fetch(`/asignaciones-turno/${idLimpio}/actualizar-horas`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({
                                            entrada,
                                            salida
                                        })
                                    })
                                    .then(res => res.json().then(data => ({
                                        ok: res.ok,
                                        data
                                    })))
                                    .then(({
                                        ok,
                                        data
                                    }) => {
                                        if (!ok) throw new Error(data.message ||
                                            'Error al actualizar');

                                        // Actualizar propiedades sin recargar
                                        evento.setExtendedProp('entrada', entrada);
                                        evento.setExtendedProp('salida', salida);

                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Horas actualizadas',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                    })
                                    .catch(err => {
                                        console.error(err);
                                        Swal.fire('Error', err.message ||
                                            'Error al actualizar', 'error');
                                    });
                            }
                        });
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
                    let horasTexto = '-- / --';

                    if (props.entrada && props.salida) {
                        horasTexto = `${props.entrada} / ${props.salida}`;
                    } else if (props.entrada && !props.salida) {
                        horasTexto = props.entrada;
                    } else if (props.entrada && !props.salida) {
                        horasTexto = props.salida;
                    }


                    let html = `
                        <div class="px-2 py-1 text-xs font-semibold flex items-center gap-1">
                            <span>${arg.event.title}</span>
                            <span class="text-[10px] font-normal opacity-80">(${props.categoria_nombre ?? ''}  </span>
                            <span class="text-[10px] font-normal opacity-80">🛠 ${props.especialidad_nombre ?? 'Sin especialidad'})</span>
                            <span class="text-[10px] font-normal opacity-80">${horasTexto}</span>
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
    <style>
        .tippy-box[data-theme~='transparent-avatar'] {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
    </style>
</x-app-layout>
