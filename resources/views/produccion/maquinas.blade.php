<x-app-layout>
    <x-slot name="title">Planificación por Máquina</x-slot>
    <x-menu.planillas />

    <!-- Botones de navegación -->
    <div class="mb-6 border-b border-gray-200">
        <div class="flex space-x-2">
            <a href="{{ route('produccion.verMaquinas') }}"
               class="px-6 py-3 font-semibold text-blue-600 border-b-2 border-blue-600 bg-blue-50 transition-colors">
                Producción/Máquinas
            </a>
            <a href="{{ route('planificacion.index') }}"
               class="px-6 py-3 font-semibold text-gray-600 hover:text-blue-600 hover:bg-gray-50 border-b-2 border-transparent transition-colors">
                Planificación
            </a>
        </div>
    </div>

    <div class="py-6">
        @if (!empty($erroresPlanillas))
            <div class="mb-4 bg-yellow-100 text-yellow-800 p-4 rounded shadow">
                <h3 class="font-semibold">Advertencias de planificación:</h3>
                <ul class="list-disc pl-5 text-sm mt-2">
                    @foreach ($erroresPlanillas as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Por esta versión con transición -->
        <div id="contenedor-calendario" class="bg-white shadow rounded-lg p-4 transition-all duration-300">
            <div id="calendario" class="h-[80vh] w-full"></div>
        </div>
    </div>

    <!-- Panel lateral para elementos -->
    <div id="panel_elementos"
        class="fixed top-0 right-0 h-full w-80 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50 flex flex-col">

        <div class="bg-blue-600 text-white p-4 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-lg">Elementos</h3>
                <p class="text-sm opacity-90" id="panel_codigo"></p>
            </div>
            <button id="cerrar_panel" class="hover:bg-blue-700 rounded p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div id="panel_lista"
            class="flex-1 overflow-y-auto p-4 space-y-3
                   [&::-webkit-scrollbar]:w-2
                   [&::-webkit-scrollbar-track]:bg-gray-200
                   [&::-webkit-scrollbar-thumb]:bg-blue-600
                   [&::-webkit-scrollbar-thumb]:rounded-full">
        </div>
    </div>

    <div id="panel_overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40"></div>

    <!-- Filtros -->
    <div class="mt-6 mb-4 flex flex-col sm:flex-row items-center gap-4 px-4">
        <label class="text-sm">
            Desde:
            <input type="date" id="fechaInicio" class="border rounded px-2 py-1 ml-1"
                value="{{ now()->subDays(7)->toDateString() }}">
        </label>
        <label class="text-sm">
            Hasta:
            <input type="date" id="fechaFin" class="border rounded px-2 py-1 ml-1"
                value="{{ now()->toDateString() }}">
        </label>
        <label class="text-sm">
            Turno:
            <select id="turnoFiltro" class="border rounded text-sm px-2 py-1 ml-1">
                <option value="">Todos</option>
                <option value="mañana">Mañana</option>
                <option value="tarde">Tarde</option>
                <option value="noche">Noche</option>
            </select>
        </label>
        <button id="filtrarFechas"
            class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition text-sm">
            Aplicar Filtro
        </button>
        <p id="rango-aplicado" class="text-sm text-gray-600 mt-2 px-4"></p>
    </div>

    <!-- Gráficos por máquina -->
    <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($maquinas as $maquina)
            <div class="bg-white shadow rounded-lg p-3">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-sm font-semibold">{{ $maquina->nombre }}</h3>
                    <select data-id="{{ $maquina->id }}" data-nombre="{{ $maquina->nombre }}"
                        class="estado-maquina border rounded text-sm px-2 py-1">
                        <option value="activa" {{ $maquina->estado === 'activa' ? 'selected' : '' }}>🟢 Activa</option>
                        <option value="averiada" {{ $maquina->estado === 'averiada' ? 'selected' : '' }}>🔴 Averiada
                        </option>
                        <option value="mantenimiento" {{ $maquina->estado === 'mantenimiento' ? 'selected' : '' }}>🛠️
                            Mantenimiento</option>
                        <option value="pausa" {{ $maquina->estado === 'pausa' ? 'selected' : '' }}>⏸️ Pausa</option>
                    </select>
                </div>
                <canvas id="grafico-maquina-{{ $maquina->id }}" class="mx-auto h-[180px]"></canvas>
            </div>
        @endforeach
    </div>

    <!-- Scripts externos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}"></script>

    <style>
        /* Contenedor calendario */
        #contenedor-calendario {
            transition: all 0.3s ease;
        }

        #contenedor-calendario.con-panel-abierto {
            width: calc(100% - 320px);
            margin-right: 320px;
        }

        body.panel-abierto #contenedor-calendario {
            margin-right: 320px;
        }

        canvas {
            height: 180px !important;
            max-height: 180px !important;
        }

        /* Panel lateral */
        #panel_elementos.abierto {
            transform: translateX(0);
        }

        /* Elementos arrastrables */
        .elemento-drag {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            cursor: move;
            transition: all 0.2s;
        }

        .elemento-drag:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .elemento-drag.fc-dragging {
            opacity: 0.5;
        }

        .elemento-drag canvas {
            width: 100%;
            height: 120px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .elemento-info-mini {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
        }

        /* Highlight en recursos cuando se arrastra */
        .fc-timeline-lane.fc-resource-dragging {
            background-color: rgba(59, 130, 246, 0.2) !important;
        }

        .fc-tooltip {
            pointer-events: none;
            transition: opacity 0.1s ease-in-out;
            position: absolute;
            z-index: 9999;
        }

        .fc-event {
            min-width: 50px !important;
        }

        /* ===== TURNOS - FONDOS DE COLOR ===== */
        .turno-manana {
            background-color: rgba(255, 243, 205, 0.4) !important;
        }

        .turno-tarde {
            background-color: rgba(255, 224, 178, 0.4) !important;
        }

        .turno-noche {
            background-color: rgba(197, 202, 233, 0.4) !important;
        }

        /* ===== ETIQUETAS DE TIEMPO - HORAS NORMALES ===== */
        .slot-label-wrapper {
            position: relative;
            padding: 4px 6px;
            min-height: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hora-text {
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
            text-align: center;
        }

        /* ===== ETIQUETAS DE TURNOS CON FECHA ===== */
        .turno-con-fecha {
            position: relative;
            padding: 8px 6px;
            min-height: 65px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            margin: -4px -6px;
            border-radius: 6px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
        }

        /* Fecha dentro del turno */
        .fecha-turno {
            color: white;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        /* Hora dentro del turno */
        .turno-con-fecha .hora-text {
            color: white;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        /* Etiqueta del tipo de turno */
        .turno-label {
            display: inline-block;
            font-size: 10px;
            font-weight: bold;
            color: white;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 4px;
            text-align: center;
            white-space: nowrap;
        }

        /* ===== LÍNEAS SEPARADORAS ===== */
        /* Línea fuerte para inicio de turnos */
        .fc-timegrid-slot[data-time="06:00:00"],
        .fc-timegrid-slot[data-time="14:00:00"],
        .fc-timegrid-slot[data-time="22:00:00"] {
            border-top: 4px solid #3b82f6 !important;
        }

        /* ===== AJUSTES ADICIONALES ===== */
        /* Mejorar visibilidad del axis */
        .fc-timegrid-axis {
            background-color: #f9fafb !important;
        }

        /* Asegurar que las etiquetas no se corten */
        .fc-timegrid-slot-label-frame {
            overflow: visible !important;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const maquinas = @json($resources);
            const planillas = @json($planillasEventos);
            const cargaTurnoResumen = @json($cargaTurnoResumen);
            const planDetallado = @json($planDetallado);
            const realDetallado = @json($realDetallado);

            // Variable global para el calendario
            let calendar;

            // Inicializar FullCalendar
            calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                initialView: 'resourceTimeGridFiveDays',
                nextDayThreshold: '06:00:00',
                views: {
                    resourceTimeGridDay: {
                        type: 'resourceTimeGrid',
                        duration: {
                            days: 1
                        },
                        slotMinTime: '00:00:00',
                        slotMaxTime: '24:00:00',
                        slotDuration: '01:00:00',
                        slotLabelFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        },
                        buttonText: '1 día'
                    },
                    resourceTimeGridFiveDays: {
                        type: 'resourceTimeGrid',
                        duration: {
                            days: 1
                        },
                        slotMinTime: '00:00:00',
                        slotMaxTime: '120:00:00',
                        slotDuration: '01:00:00',
                        slotLabelFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        },
                        dayHeaderFormat: {
                            weekday: 'short',
                            day: 'numeric',
                            month: 'short'
                        },
                        buttonText: '5 días'
                    }
                },
                locale: 'es',
                timeZone: 'Europe/Madrid',
                initialDate: "{{ $initialDate }}",
                resources: maquinas,
                events: planillas,
                height: 'auto',
                scrollTime: '06:00:00',
                editable: true,
                eventResizableFromStart: false,
                eventDurationEditable: false,
                droppable: true, // ✅ Habilitar drop de elementos externos

                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'resourceTimeGridDay,resourceTimeGridFiveDays'
                },

                // 🎯 CLAVE: Configurar recepción de elementos externos
                eventReceive: async function(info) {
                    const elementoId = parseInt(info.event.extendedProps.elementoId);
                    const planillaId = parseInt(info.event.extendedProps.planillaId);
                    const maquinaOrigenId = parseInt(info.event.extendedProps.maquinaOriginal);
                    const maquinaDestinoId = parseInt(info.event.getResources()[0].id);

                    // Confirmar movimiento
                    const resultado = await Swal.fire({
                        title: '¿Mover elemento?',
                        html: `¿Mover a <strong>${info.event.getResources()[0].title}</strong>?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, mover',
                        cancelButtonText: 'Cancelar'
                    });

                    if (!resultado.isConfirmed) {
                        info.revert();
                        return;
                    }

                    try {
                        const res = await fetch('/planillas/reordenar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                id: planillaId,
                                maquina_id: maquinaDestinoId,
                                maquina_origen_id: maquinaOrigenId,
                                nueva_posicion: 1,
                                elementos_id: [elementoId],
                                forzar_movimiento: true
                            })
                        });

                        const data = await res.json();

                        if (data.success) {
                            // Actualizar calendario
                            actualizarEventosSinRecargar(data.eventos);

                            // Remover elemento del panel
                            const elementoDiv = document.querySelector(
                                `.elemento-drag[data-elemento-id="${elementoId}"]`);
                            if (elementoDiv) {
                                elementoDiv.remove();
                            }

                            // Remover el evento temporal que se creó
                            info.event.remove();

                            Swal.fire({
                                icon: 'success',
                                title: 'Elemento movido',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    } catch (error) {
                        info.revert();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo mover el elemento'
                        });
                    }
                },

                slotLaneClassNames: function(arg) {
                    const hour = arg.date.getHours();
                    if (hour > 6 && hour <= 14) return ['turno-manana'];
                    if (hour >= 14 && hour <= 22) return ['turno-tarde'];
                    if (hour >= 22 || hour <= 6) return ['turno-noche'];
                    return [];
                },

                slotLabelContent: function(arg) {
                    // ✅ Usar arg.date en lugar de parsear arg.text
                    const currentDate = new Date(arg.date);
                    const horaReal = currentDate.getHours();
                    const minutos = currentDate.getMinutes();

                    // Formatear la hora para mostrar
                    const timeText =
                        `${horaReal.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;

                    // ✅ Para turno de noche (22:00), usar fecha del día siguiente
                    let fechaMostrar = new Date(currentDate);
                    if (horaReal === 22) {
                        fechaMostrar.setDate(fechaMostrar.getDate() + 1);
                    }

                    // Formatear fecha
                    const dia = fechaMostrar.getDate().toString().padStart(2, '0');
                    const mes = (fechaMostrar.getMonth() + 1).toString().padStart(2, '0');
                    const año = fechaMostrar.getFullYear();
                    const nombreDia = fechaMostrar.toLocaleDateString('es-ES', {
                        weekday: 'short'
                    }).toUpperCase();
                    const fechaFormateada = `${dia}/${mes}/${año}`;

                    let contenido = '';


                    if (horaReal === 7) {
                        // 🌅 Turno Mañana (06:00)
                        contenido = `
            <div class="turno-con-fecha">
                <div class="fecha-turno">${nombreDia}<br>${fechaFormateada}</div>
                <div class="hora-text">${timeText}</div>
                <span class="turno-label">☀️ Mañana</span>
            </div>`;
                    } else if (horaReal === 15) {
                        // 🌤️ Turno Tarde (14:00)
                        contenido = `
            <div class="turno-con-fecha">
                <div class="fecha-turno">${nombreDia}<br>${fechaFormateada}</div>
                <div class="hora-text">${timeText}</div>
                <span class="turno-label">🌤️ Tarde</span>
            </div>`;
                    } else if (horaReal === 23) {
                        // 🌙 Turno Noche (22:00) - MUESTRA FECHA DEL DÍA SIGUIENTE
                        contenido = `
            <div class="turno-con-fecha">
                <div class="fecha-turno">${nombreDia}<br>${fechaFormateada}</div>
                <div class="hora-text">${timeText}</div>
                <span class="turno-label">🌙 Noche</span>
            </div>`;
                    } else {
                        // Horas normales sin fecha
                        contenido = `
            <div class="slot-label-wrapper">
                <div class="hora-text">${timeText}</div>
            </div>`;
                    }

                    return {
                        html: contenido
                    };
                },
                eventClick: async function(info) {
                    const planillaId = info.event.id.split('-')[1];
                    const elementosId = info.event.extendedProps.elementos_id;
                    const codigoPlanilla = info.event.extendedProps.codigo ?? info.event.title;

                    if (!Array.isArray(elementosId) || elementosId.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sin elementos',
                            text: 'Este evento no tiene elementos asociados.',
                        });
                        return;
                    }

                    try {
                        const response = await fetch(`/elementos/por-ids?ids=${elementosId.join(',')}`);
                        const elementos = await response.json();
                        mostrarPanelElementos(elementos, planillaId, codigoPlanilla);
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudieron cargar los elementos'
                        });
                    }
                },

                eventContent: function(arg) {
                    const progreso = arg.event.extendedProps.progreso;

                    if (typeof progreso === 'number') {
                        return {
                            html: `
                                <div class="w-full px-1 py-0.5 text-xs font-semibold text-white">
                                    <div class="mb-0.5 truncate" title="${arg.event.title}">${arg.event.title}</div>
                                    <div class="w-full h-2 bg-gray-300 rounded overflow-hidden">
                                        <div class="h-2 bg-blue-500 rounded" style="width: ${progreso}%; min-width: 1px;"></div>
                                    </div>
                                </div>`
                        };
                    }

                    return {
                        html: `
                            <div class="truncate w-full text-xs font-semibold text-white px-2 py-1 rounded"
                                 style="background-color: ${arg.event.backgroundColor};"
                                 title="${arg.event.title}">
                                ${arg.event.title}
                            </div>`
                    };
                },

                eventDrop: async function(info) {
                    const planillaId = info.event.id.split('-')[1];
                    const codigoPlanilla = info.event.extendedProps.codigo ?? info.event.title;
                    const maquinaOrigenId = info.oldResource?.id ?? info.event.getResources()[0]?.id;
                    const maquinaDestinoId = info.newResource?.id ?? info.event.getResources()[0]?.id;
                    const elementosId = info.event.extendedProps.elementos_id || [];

                    const resultado = await Swal.fire({
                        title: '¿Reordenar planilla?',
                        html: `¿Quieres mover la planilla <strong>${codigoPlanilla}</strong> ${maquinaOrigenId !== maquinaDestinoId ? 'a otra máquina' : 'en la misma máquina'}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, reordenar',
                        cancelButtonText: 'Cancelar'
                    });

                    if (!resultado.isConfirmed) {
                        info.revert();
                        return;
                    }

                    const eventosOrdenados = calendar.getEvents()
                        .filter(ev => ev.getResources().some(r => r.id == maquinaDestinoId))
                        .sort((a, b) => a.start - b.start);
                    const nuevaPosicion = eventosOrdenados.findIndex(ev => ev.id === info.event.id) + 1;

                    try {
                        const res = await fetch('/planillas/reordenar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                id: planillaId,
                                maquina_id: maquinaDestinoId,
                                maquina_origen_id: maquinaOrigenId,
                                nueva_posicion: nuevaPosicion,
                                elementos_id: elementosId,
                            })
                        });

                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            throw new Error(data.message || 'Error al reordenar');
                        }

                        actualizarEventosSinRecargar(data.eventos);

                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Planilla reordenada'
                        });

                    } catch (error) {
                        info.revert();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'No se pudo reordenar'
                        });
                    }
                },

                eventDidMount: function(info) {
                    const props = info.event.extendedProps;
                    const tooltip = document.createElement('div');
                    tooltip.className = 'fc-tooltip';
                    tooltip.innerHTML = `
                        <div class="bg-gray-900 text-white text-xs rounded px-2 py-1 shadow-md max-w-xs">
                            <strong>${info.event.title}</strong><br>
                            Obra: ${props.obra}<br>
                            Estado producción: ${props.estado}<br>
                            Fin programado: <span class="text-yellow-300">${props.fin_programado}</span><br>
                            Fecha estimada entrega: <span class="text-green-300">${props.fecha_entrega}</span>
                        </div>`;
                    tooltip.style.display = 'none';
                    document.body.appendChild(tooltip);

                    info.el.addEventListener('mouseenter', function(e) {
                        tooltip.style.left = e.pageX + 10 + 'px';
                        tooltip.style.top = e.pageY + 10 + 'px';
                        tooltip.style.display = 'block';
                    });
                    info.el.addEventListener('mousemove', function(e) {
                        tooltip.style.left = e.pageX + 10 + 'px';
                        tooltip.style.top = e.pageY + 10 + 'px';
                    });
                    info.el.addEventListener('mouseleave', function() {
                        tooltip.style.display = 'none';
                    });
                }
            });
            console.log('Fecha inicial del calendario:', "{{ $initialDate }}");
            console.log('Fecha parseada:', new Date("{{ $initialDate }}"));
            calendar.render();
            window.calendar = calendar;

            // Función para actualizar eventos sin recargar
            function actualizarEventosSinRecargar(eventosNuevos) {
                document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());

                if (!eventosNuevos || !Array.isArray(eventosNuevos)) {
                    console.warn('No se recibieron eventos para actualizar');
                    return;
                }

                const maquinasAfectadas = [...new Set(eventosNuevos.map(e => String(e.resourceId)))];

                calendar.getEvents().forEach(evento => {
                    const recursos = evento.getResources();
                    const perteneceAMaquinaAfectada = recursos.some(recurso =>
                        maquinasAfectadas.includes(String(recurso.id))
                    );

                    if (perteneceAMaquinaAfectada) {
                        evento.remove();
                    }
                });

                eventosNuevos.forEach(eventoData => {
                    calendar.addEvent(eventoData);
                });
            }

            // Función para mostrar panel de elementos
            function mostrarPanelElementos(elementos, planillaId, codigo) {
                const panel = document.getElementById('panel_elementos');
                const overlay = document.getElementById('panel_overlay');
                const lista = document.getElementById('panel_lista');
                const contenedorCalendario = document.getElementById('contenedor-calendario');

                document.getElementById('panel_codigo').textContent = codigo;
                lista.innerHTML = '';

                elementos.forEach(elemento => {
                    const div = document.createElement('div');
                    div.className = 'elemento-drag fc-event';
                    div.draggable = true;

                    div.dataset.elementoId = elemento.id;
                    div.dataset.planillaId = planillaId;
                    div.dataset.maquinaOriginal = elemento.maquina_id;

                    div.dataset.event = JSON.stringify({
                        title: elemento.codigo,
                        extendedProps: {
                            elementoId: elemento.id,
                            planillaId: planillaId,
                            maquinaOriginal: elemento.maquina_id
                        },
                        duration: '01:00'
                    });

                    const canvasId = `canvas-panel-${elemento.id}`;

                    div.innerHTML = `
            <canvas id="${canvasId}" width="240" height="120"></canvas>
            <div class="elemento-info-mini">
                <span><strong>⌀${elemento.diametro}mm</strong></span>
                <span><strong>${elemento.peso}kg</strong></span>
            </div>
        `;

                    lista.appendChild(div);

                    setTimeout(() => {
                        window.dibujarFiguraElemento(canvasId, elemento.dimensiones, elemento.peso);
                    }, 10);
                });

                new FullCalendar.Draggable(lista, {
                    itemSelector: '.elemento-drag',
                    eventData: function(eventEl) {
                        return JSON.parse(eventEl.dataset.event);
                    }
                });

                // ✅ Ajustar calendario
                panel.classList.add('abierto');
                overlay.classList.remove('hidden');
                contenedorCalendario.classList.add('con-panel-abierto');
                document.body.classList.add('panel-abierto');

                setTimeout(() => {
                    calendar.updateSize();
                }, 300);
                // ✅ Redimensionar calendario después de la transición
                setTimeout(() => {
                    calendar.updateSize();
                }, 300); // Espera a que termine la transición CSS
            }

            function cerrarPanel() {
                document.body.classList.remove('panel-abierto');
                document.getElementById('panel_elementos').classList.remove('abierto');
                document.getElementById('panel_overlay').classList.add('hidden');

                setTimeout(() => {
                    calendar.updateSize();
                }, 300);
            }

            document.getElementById('cerrar_panel').addEventListener('click', cerrarPanel);
            document.getElementById('panel_overlay').addEventListener('click', cerrarPanel);

            // --- Charts (Planificado vs Real) ---
            const charts = {};
            const $desde = document.getElementById('fechaInicio');
            const $hasta = document.getElementById('fechaFin');
            const $turno = document.getElementById('turnoFiltro');
            const $rango = document.getElementById('rango-aplicado');

            const parseDate = (val) => {
                if (!val) return null;
                const d = new Date(val);
                return isNaN(d.getTime()) ? null : d;
            };

            Object.entries(cargaTurnoResumen).forEach(([maquinaId, turnos]) => {
                const canvas = document.getElementById(`grafico-maquina-${maquinaId}`);
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                const labels = ["Mañana", "Tarde", "Noche"];
                const planificado = labels.map(t => (turnos[t.toLowerCase()]?.planificado ?? 0));
                const real = labels.map(t => (turnos[t.toLowerCase()]?.real ?? 0));

                charts[maquinaId] = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                                label: 'Planificado (kg)',
                                data: planificado,
                                backgroundColor: 'rgba(255, 159, 64, 0.6)',
                                borderColor: 'rgba(255, 159, 64, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Real (kg)',
                                data: real,
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            title: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Kg'
                                }
                            }
                        }
                    }
                });
            });

            const pintarTextoRango = () => {
                const turnoTxt = $turno?.value ? $turno.options[$turno.selectedIndex].text : 'Todos';
                $rango.textContent =
                    `Mostrando datos desde ${$desde.value} hasta ${$hasta.value} · Turno: ${turnoTxt}`;
            };
            pintarTextoRango();

            const aplicarFiltro = (ev) => {
                ev?.preventDefault?.();

                const desde = parseDate($desde.value);
                const hasta = parseDate($hasta.value);
                if (!desde || !hasta) {
                    $rango.textContent = 'Selecciona un rango de fechas válido.';
                    return;
                }
                hasta.setHours(23, 59, 59, 999);

                const turnoSel = ($turno?.value || '').toLowerCase();
                pintarTextoRango();

                Object.entries(planDetallado).forEach(([maquinaId, turnosPlan]) => {
                    const etiquetas = turnoSel ? [turnoSel] : ['mañana', 'tarde', 'noche'];

                    const planificado = etiquetas.map(t =>
                        (turnosPlan[t] || [])
                        .filter(e => {
                            const f = parseDate(e.fecha);
                            return f && f >= desde && f <= hasta;
                        })
                        .reduce((suma, e) => suma + (Number(e.peso) || 0), 0)
                    );

                    const turnosReal = realDetallado[maquinaId] || {};
                    const real = etiquetas.map(t =>
                        (turnosReal[t] || [])
                        .filter(e => {
                            const f = parseDate(e.fecha);
                            return f && f >= desde && f <= hasta;
                        })
                        .reduce((suma, e) => suma + (Number(e.peso) || 0), 0)
                    );

                    if (charts[maquinaId]) {
                        charts[maquinaId].data.labels = etiquetas.map(s => s.charAt(0).toUpperCase() + s
                            .slice(1));
                        charts[maquinaId].data.datasets[0].data = planificado;
                        charts[maquinaId].data.datasets[1].data = real;
                        charts[maquinaId].update();
                    }
                });
            };

            document.getElementById('filtrarFechas').addEventListener('click', aplicarFiltro);
            ['change', 'input'].forEach(evt => {
                $desde.addEventListener(evt, aplicarFiltro);
                $hasta.addEventListener(evt, aplicarFiltro);
                $turno?.addEventListener(evt, aplicarFiltro);
            });

            // Cambiar estados de máquinas
            document.querySelectorAll('.estado-maquina').forEach(select => {
                select.addEventListener('change', async function() {
                    const maquinaId = this.dataset.id;
                    const nuevoEstado = this.value;

                    try {
                        const res = await fetch(`/maquinas/${maquinaId}/cambiar-estado`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                estado: nuevoEstado
                            })
                        });

                        const data = await res.json();

                        if (data.success) {
                            const colores = {
                                activa: '🟢',
                                averiada: '🔴',
                                mantenimiento: '🛠️',
                                pausa: '⏸️'
                            };
                            const icono = colores[data.estado] ?? ' ';
                            const nombreMaquina = select.dataset.nombre ??
                                `Máquina ${maquinaId}`;
                            const nuevoTitulo = `${icono} ${nombreMaquina}`;
                            calendar.getResourceById(maquinaId)?.setProp('title', nuevoTitulo);
                        }
                    } catch (e) {
                        Swal.fire({
                            title: 'Error',
                            text: e.message,
                            icon: 'error'
                        });
                    }
                });
            });
        });
    </script>
</x-app-layout>
