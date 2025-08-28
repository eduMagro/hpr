<x-app-layout>
    <x-slot name="title">Planificación por Obra</x-slot>
    <x-menu.usuarios :totalSolicitudesPendientes="$totalSolicitudesPendientes ?? 0" />

    <div id="lista-trabajadores" class="p-4 bg-white border rounded shadow w-full mt-4">
        {{-- hpr servicios --}}
        <details class="mb-4" open>
            <summary class="cursor-pointer font-bold text-gray-800 mb-2">Trabajadores de HPR Servicios</summary>
            <div id="external-events-servicios" class="grid grid-cols-2 md:grid-cols-6 gap-2 mt-2">
                @foreach ($trabajadoresServicios as $t)
                    <div class="fc-event px-3 py-2 text-xs bg-blue-100 rounded cursor-pointer text-center shadow"
                        data-id="{{ $t->id }}" data-title="{{ $t->nombre_completo }}"
                        data-categoria="{{ $t->categoria?->nombre }}" data-especialidad="{{ $t->maquina?->nombre }}">
                        <img src="{{ $t->ruta_imagen }}"
                            class="w-10 h-10 rounded-full object-cover mx-auto mb-1 ring-2 ring-blue-300">
                        {{ $t->nombre_completo }}
                        <div class="text-[10px] text-gray-600">
                            {{ $t->categoria?->nombre }} @if ($t->maquina)
                                · {{ $t->maquina?->nombre }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </details>
        {{-- hpr --}}
        <details>
            <summary class="cursor-pointer font-bold text-gray-800 mb-2">Trabajadores de Hierros Paco Reyes
            </summary>
            <div id="external-events-hpr" class="grid grid-cols-2 md:grid-cols-6 gap-2 mt-2">
                @foreach ($trabajadoresHpr as $t)
                    <div class="fc-event px-3 py-2 text-xs bg-blue-100 rounded cursor-pointer text-center shadow"
                        data-id="{{ $t->id }}" data-title="{{ $t->nombre_completo }}"
                        data-categoria="{{ $t->categoria?->nombre }}" data-especialidad="{{ $t->maquina?->nombre }}">
                        <img src="{{ $t->ruta_imagen }}"
                            class="w-10 h-10 rounded-full object-cover mx-auto mb-1 ring-2 ring-blue-300">
                        {{ $t->nombre_completo }}
                        <div class="text-[10px] text-gray-600">
                            {{ $t->categoria?->nombre }} @if ($t->maquina)
                                · {{ $t->maquina?->nombre }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </details>
    </div>


    <div class="p-4">

        <!-- Calendario -->
        <div class="w-full bg-white">
            <div class="flex justify-end my-4">
                <button id="btnRepetirSemana"
                    class=" bg-yellow-500 hover:bg-yellow-600 text-white font-bold m-4 py-2 px-4 rounded">
                    🔁 Repetir semana anterior
                </button>
            </div>
            <div id="calendario-obras" class="h-[80vh] w-full"></div>
        </div>
    </div>
    <div class="max-w-xl mx-auto mt-10 bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">🔧 Cambiar tipo de una obra</h2>

        <form action="{{ route('obras.updateTipo') }}" method="POST">
            @csrf

            {{-- Selección de obra --}}
            <div class="mb-4">
                <label for="obra_id" class="block text-sm font-medium text-gray-700">Selecciona una obra</label>
                <select name="obra_id" id="obra_id"
                    class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 text-sm shadow-sm focus:ring focus:ring-blue-300">
                    <option value="">-- Elige una obra --</option>
                    @foreach ($obras as $obra)
                        <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                    @endforeach
                </select>
                @error('obra_id')
                    <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            {{-- Nuevo tipo --}}
            <div class="mb-4">
                <label for="tipo" class="block text-sm font-medium text-gray-700">Nuevo tipo</label>
                <select name="tipo" id="tipo"
                    class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 text-sm shadow-sm focus:ring focus:ring-blue-300">
                    <option value="">-- Selecciona un tipo --</option>
                    <option value="obra">Obra</option>
                    <option value="montaje">Montaje</option>
                    <option value="mantenimiento">Mantenimiento</option>
                </select>
                @error('tipo')
                    <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            {{-- Botón --}}
            <div class="flex justify-end">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded shadow">
                    💾 Actualizar tipo
                </button>
            </div>
        </form>
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
        document.querySelectorAll('.fc-event').forEach(eventEl => {
            eventEl.addEventListener('click', () => {
                eventEl.classList.toggle('bg-yellow-300'); // cambia visualmente
                eventEl.classList.toggle('seleccionado'); // añade clase de control
            });
        });
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-eliminar')) {
                e.stopPropagation(); // 🔴 Detiene el eventClick de FullCalendar
                e.preventDefault();

                const idEvento = e.target.dataset.id.replace('turno-', '');

                Swal.fire({
                    title: '¿Eliminar asignación de obra?',
                    text: "Esto quitará la obra del trabajador en ese turno.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/asignaciones-turno/${idEvento}/quitar-obra`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    const evento = calendarioObras.getEventById('turno-' + idEvento);
                                    if (evento) evento.remove();
                                } else {
                                    Swal.fire('❌ Error', data.message, 'error');
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                Swal.fire('❌ Error', 'No se pudo quitar la obra.', 'error');
                            });
                    }
                });
            }
        });

        let calendarioObras;

        const resources = @json($resources);

        function inicializarCalendarioObras() {
            if (calendarioObras) {
                calendarioObras.destroy();
            }

            calendarioObras = new FullCalendar.Calendar(document.getElementById('calendario-obras'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                locale: 'es',
                initialView: localStorage.getItem('vistaObras') || 'resourceTimelineWeek',
                initialDate: localStorage.getItem('fechaObras') || undefined,
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
                resourceAreaColumns: [{
                        field: 'codigo', // este campo debe existir en tu array `resources`
                        headerContent: 'Código',
                        width: 20
                    },
                    {
                        field: 'title',
                        headerContent: 'Obra',
                        width: 100
                    }
                ],
                datesSet: function(info) {
                    localStorage.setItem('vistaObras', info.view.type);
                    localStorage.setItem('fechaObras', info.startStr);

                    // Mostrar botón solo en vista semanal
                    const btn = document.getElementById('btnRepetirSemana');
                    if (info.view.type === 'resourceTimelineWeek') {
                        btn.classList.remove('hidden');
                        btn.dataset.fecha = info.startStr;
                    } else {
                        btn.classList.add('hidden');
                    }
                },
                selectable: true,
                selectMirror: true,
                select: function(info) {
                    const seleccionados = [...document.querySelectorAll('.fc-event.seleccionado')];
                    if (seleccionados.length === 0) {
                        Swal.fire('❌ Debes seleccionar primero uno o más trabajadores.');
                        return;
                    }

                    // Obtener rango de fechas (incluyendo el último día real)
                    const fechaInicio = info.startStr;
                    const fechaFinObj = new Date(info.end);
                    fechaFinObj.setDate(fechaFinObj.getDate()); // incluye el último día
                    const fechaFin = fechaFinObj.toISOString().split('T')[0];

                    // Obtener ID de la obra seleccionada (resource)
                    const obraId = info.resource?.id;
                    const obraNombre = info.resource?.title;

                    const mensajeFecha = fechaInicio === fechaFin ?
                        `<p>${fechaInicio}</p>` :
                        `<p>Desde: ${fechaInicio}</p><p>Hasta: ${fechaFin}</p>`;

                    Swal.fire({
                        title: "¿Asignar a obra seleccionada?",
                        html: `
            ${mensajeFecha}
            <p><strong>${seleccionados.length}</strong> trabajadores</p>
            <p>Obra: <strong>${obraNombre}</strong></p>
        `,
                        showCancelButton: true,
                        confirmButtonText: "Asignar",
                        cancelButtonText: "Cancelar",
                        preConfirm: () => {
                            const userIds = seleccionados.map(e => e.dataset.id);
                            return fetch('{{ route('asignaciones-turno.asignarObraMultiple') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    user_ids: userIds,
                                    obra_id: obraId,
                                    fecha_inicio: fechaInicio,
                                    fecha_fin: fechaFin
                                })
                            }).then(res => res.json());
                        }
                    }).then(res => {
                        if (res.isConfirmed && res.value?.success) {
                            // 🔹 Deseleccionar todos los trabajadores
                            document.querySelectorAll('.fc-event.seleccionado').forEach(el => {
                                el.classList.remove('seleccionado', 'bg-yellow-300');
                            });

                            calendarioObras.refetchEvents();
                        }
                    });
                },
                events: {
                    url: '{{ route('asignaciones-turnos.verEventosObra') }}',
                    method: 'GET',
                    failure: function() {
                        Swal.fire('Error al cargar eventos');
                    }
                },
                eventClick(info) {
                    // 🔴 Si se hizo clic en la X, no navegamos
                    if (info.jsEvent.target.closest('.btn-eliminar')) {
                        return;
                    }

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
                    const id = arg.event.id;

                    // 👇 Texto de estado si existe
                    const estadoTexto = props.estado ? `<div class="text-[10px] opacity-80">${props.estado}</div>` :
                        '';

                    return {
                        html: `
            <div class="relative px-2 py-1 text-xs font-semibold group">
                <span title="Eliminar" 
                      class="absolute top-0 right-0 text-red-600 hover:text-red-800 text-sm cursor-pointer btn-eliminar" 
                      data-id="${id}">X</span>
                <div>${arg.event.title}</div>
                ${estadoTexto}
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
        document.getElementById('btnRepetirSemana').addEventListener('click', function() {
            const fechaInicio = this.dataset.fecha;

            Swal.fire({
                title: '¿Repetir semana anterior?',
                text: 'Se copiarán todas las asignaciones de la semana pasada a la actual.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, repetir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('asignaciones-turno.repetirSemana') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                fecha_actual: fechaInicio
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('✅ Semana copiada correctamente');
                                calendarioObras.refetchEvents();
                            } else {
                                Swal.fire('❌ Error', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            Swal.fire('❌ Error', 'No se pudo completar la solicitud.', 'error');
                        });
                }
            });
        });
    </script>
    <style>
        .fc-event.seleccionado {
            outline: 3px solid #facc15;
            background-color: #fde68a !important;
            transform: scale(1.05);
        }

        .btn-eliminar {
            color: red;
            border-radius: 9999px;
            background-color: white;
            font-size: 14px;
            padding: 0 6px;
            line-height: 1;
        }

        .tippy-box[data-theme~='transparent-avatar'] {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
    </style>
</x-app-layout>
