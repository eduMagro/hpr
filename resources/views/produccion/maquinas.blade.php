<x-app-layout>
    <x-slot name="title">Planificación por Máquina</x-slot>
    <x-menu.planillas />

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
        <!-- Filtros de resaltado -->
        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-blue-900 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filtros de resaltado
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Filtro por fecha de entrega -->
                <label class="text-sm">
                    <span class="block text-gray-700 font-medium mb-1">Fecha de entrega:</span>
                    <input type="date" id="filtroFechaEntrega"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </label>

                <!-- Filtro por obra -->
                <label class="text-sm">
                    <span class="block text-gray-700 font-medium mb-1">Obra:</span>
                    <select id="filtroObra"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas</option>
                        <!-- Las opciones se llenarán dinámicamente -->
                    </select>
                </label>

                <!-- Filtro por estado -->
                <label class="text-sm">
                    <span class="block text-gray-700 font-medium mb-1">Estado:</span>
                    <select id="filtroEstado"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="fabricando">Fabricando</option>
                        <option value="completada">Completada</option>
                    </select>
                </label>

                <!-- Botones de acción -->
                <div class="flex flex-col gap-2">
                    <button id="aplicarResaltado"
                        class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition text-sm font-medium">
                        🔍 Aplicar filtro
                    </button>
                    <button id="limpiarResaltado"
                        class="bg-gray-400 text-white px-4 py-2 rounded shadow hover:bg-gray-500 transition text-sm font-medium">
                        ✖ Limpiar filtro
                    </button>
                </div>
            </div>

            <!-- Indicador de filtros activos -->
            <div id="filtrosActivos" class="mt-3 text-xs text-blue-700 hidden">
                <span class="font-semibold">Filtros activos:</span>
                <span id="textoFiltrosActivos"></span>
            </div>
        </div>
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

        /* Filtros para resaltado de eventos */
        /* Estilos para resaltado de eventos */
        .fc-event.evento-resaltado {
            box-shadow: 0 0 0 3px #3b82f6, 0 0 12px rgba(59, 130, 246, 0.5) !important;
            z-index: 100 !important;
            transform: scale(1.02);
            transition: all 0.2s ease;
        }

        .fc-event.evento-opaco {
            opacity: 0.25 !important;
            filter: grayscale(50%);
            transition: all 0.2s ease;
        }

        .fc-event.evento-resaltado:hover {
            transform: scale(1.05);
            box-shadow: 0 0 0 4px #2563eb, 0 0 16px rgba(37, 99, 235, 0.6) !important;
        }

        /* Animación de pulso para eventos resaltados */
        @keyframes pulso-resaltado {

            0%,
            100% {
                box-shadow: 0 0 0 3px #3b82f6, 0 0 12px rgba(59, 130, 246, 0.5);
            }

            50% {
                box-shadow: 0 0 0 5px #3b82f6, 0 0 20px rgba(59, 130, 246, 0.7);
            }
        }

        .fc-event.evento-resaltado.pulsando {
            animation: pulso-resaltado 1.5s ease-in-out infinite;
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

            // ================================
            // SISTEMA DE FILTROS DE RESALTADO
            // ================================

            let filtrosActivos = {
                fechaEntrega: null,
                obra: null,
                estado: null
            };
            /**
             * Parsea una fecha desde string DD/MM/YYYY HH:mm a objeto Date
             */
            function parsearFechaEvento(fechaStr) {
                if (!fechaStr || fechaStr === '—') {
                    console.log('📅 parsearFechaEvento: fecha vacía o inválida', fechaStr);
                    return null;
                }

                // Formato: "DD/MM/YYYY HH:mm"
                const partes = fechaStr.match(/(\d{2})\/(\d{2})\/(\d{4})/);
                if (!partes) {
                    console.warn('⚠️ parsearFechaEvento: no se pudo parsear', fechaStr);
                    return null;
                }

                const [_, dia, mes, anio] = partes;
                const fecha = new Date(anio, mes - 1, dia);

                console.log(`📅 parsearFechaEvento: "${fechaStr}" → ${fecha.toLocaleDateString('es-ES')}`);
                return fecha;
            }

            /**
             * Compara dos fechas sin considerar la hora
             */
            function fechasIguales(fecha1, fecha2) {
                if (!fecha1 || !fecha2) {
                    console.log('⚖️ fechasIguales: alguna fecha es null', {
                        fecha1,
                        fecha2
                    });
                    return false;
                }

                const iguales = fecha1.getDate() === fecha2.getDate() &&
                    fecha1.getMonth() === fecha2.getMonth() &&
                    fecha1.getFullYear() === fecha2.getFullYear();

                console.log('⚖️ fechasIguales:', {
                    fecha1: fecha1.toLocaleDateString('es-ES'),
                    fecha2: fecha2.toLocaleDateString('es-ES'),
                    resultado: iguales ? '✅ IGUALES' : '❌ DIFERENTES'
                });

                return iguales;
            }

            /**
             * Determina si un evento cumple con los filtros activos
             */
            function cumpleFiltros(evento) {
                const props = evento.extendedProps;

                // Filtro por fecha de entrega
                if (filtrosActivos.fechaEntrega) {
                    const fechaEvento = parsearFechaEvento(props.fecha_entrega);
                    const cumple = fechasIguales(fechaEvento, filtrosActivos.fechaEntrega);

                    console.log('Fecha filtro:', filtrosActivos.fechaEntrega.toLocaleDateString('es-ES'));
                    console.log('Fecha evento:', props.fecha_entrega);
                    console.log('Cumple fecha:', cumple ? '✅' : '❌');

                    if (!cumple) return false;
                }

                // Filtro por obra
                if (filtrosActivos.obra && filtrosActivos.obra !== '') {
                    const cumple = props.obra === filtrosActivos.obra;
                    console.log('Obra filtro:', filtrosActivos.obra);
                    console.log('Obra evento:', props.obra);
                    console.log('Cumple obra:', cumple ? '✅' : '❌');

                    if (!cumple) return false;
                }

                // Filtro por estado
                if (filtrosActivos.estado && filtrosActivos.estado !== '') {
                    const cumple = props.estado === filtrosActivos.estado;
                    console.log('Estado filtro:', filtrosActivos.estado);
                    console.log('Estado evento:', props.estado);
                    console.log('Cumple estado:', cumple ? '✅' : '❌');

                    if (!cumple) return false;
                }

                return true;
            }

            /**
             * Aplica el resaltado a los eventos del calendario
             */
            function aplicarResaltadoEventos() {
                console.clear();
                console.log('🎨 APLICANDO FILTROS');

                const hayFiltros = Object.values(filtrosActivos).some(v => v !== null && v !== '');

                console.log('Filtros activos:', filtrosActivos);

                if (!hayFiltros) {
                    limpiarResaltado();
                    return;
                }

                setTimeout(() => {
                    // Agrupar eventos por planilla
                    const eventosPorPlanilla = {};

                    calendar.getEvents().forEach(evento => {
                        // Extraer ID de planilla del ID del evento (formato: "planilla-123-seg1")
                        const match = evento.id.match(/^planilla-(\d+)-seg\d+$/);
                        if (!match) return;

                        const planillaId = match[1];

                        if (!eventosPorPlanilla[planillaId]) {
                            eventosPorPlanilla[planillaId] = {
                                eventos: [],
                                props: evento.extendedProps,
                                title: evento.extendedProps.codigo || evento.title
                            };
                        }

                        eventosPorPlanilla[planillaId].eventos.push(evento);
                    });

                    console.log('Total planillas encontradas:', Object.keys(eventosPorPlanilla).length);

                    let planillasResaltadas = 0;
                    let segmentosResaltados = 0;

                    // Evaluar cada planilla
                    Object.entries(eventosPorPlanilla).forEach(([planillaId, data]) => {
                        console.group(`📋 Planilla ${data.title}`);
                        console.log('Segmentos:', data.eventos.length);
                        console.log('Props:', data.props);

                        const cumple = cumpleFiltros(data.eventos[
                            0]); // Evaluar con el primer segmento

                        // Aplicar a TODOS los segmentos de esta planilla
                        data.eventos.forEach(evento => {
                            const elementos = document.querySelectorAll('.fc-event');
                            let elementoDOM = null;

                            elementos.forEach(el => {
                                if (el.fcSeg && el.fcSeg.eventRange.def.publicId ===
                                    evento.id) {
                                    elementoDOM = el;
                                }
                            });

                            if (!elementoDOM) return;

                            // Remover clases previas
                            elementoDOM.classList.remove('evento-resaltado', 'evento-opaco',
                                'pulsando');

                            if (cumple) {
                                elementoDOM.classList.add('evento-resaltado', 'pulsando');
                                segmentosResaltados++;
                            } else {
                                elementoDOM.classList.add('evento-opaco');
                            }
                        });

                        if (cumple) {
                            planillasResaltadas++;
                            console.log('✅ RESALTADA (todos los segmentos)');
                        } else {
                            console.log('⚪ OPACADA (todos los segmentos)');
                        }

                        console.groupEnd();
                    });

                    console.log(`━━━━━━━━━━━━━━━━`);
                    console.log(`✅ Planillas resaltadas: ${planillasResaltadas}`);
                    console.log(`📊 Segmentos resaltados: ${segmentosResaltados}`);

                    actualizarIndicadorFiltros(planillasResaltadas);

                    if (planillasResaltadas === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Sin resultados',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: `${planillasResaltadas} planilla(s) resaltada(s)`,
                            text: `${segmentosResaltados} segmento(s) en total`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }, 100);
            }
            /**
             * Limpia todos los resaltados
             */
            function limpiarResaltado() {
                console.log('%c🧹 LIMPIANDO FILTROS', 'font-size: 14px; font-weight: bold; color: #dc2626;');

                calendar.getEvents().forEach(evento => {
                    const elemento = evento.el;
                    if (elemento) {
                        elemento.classList.remove('evento-resaltado', 'evento-opaco', 'pulsando');
                    }
                });

                // Limpiar filtros
                filtrosActivos = {
                    fechaEntrega: null,
                    obra: null,
                    estado: null
                };

                console.log('✅ Todos los resaltados eliminados');
                console.log('✅ Filtros reseteados');

                // Limpiar inputs
                document.getElementById('filtroFechaEntrega').value = '';
                document.getElementById('filtroObra').value = '';
                document.getElementById('filtroEstado').value = '';

                // Ocultar indicador
                document.getElementById('filtrosActivos').classList.add('hidden');
            }
            /**
             * Actualiza el indicador visual de filtros activos
             */
            function actualizarIndicadorFiltros(cantidad) {
                const indicador = document.getElementById('filtrosActivos');
                const texto = document.getElementById('textoFiltrosActivos');

                let descripcion = [];

                if (filtrosActivos.fechaEntrega) {
                    descripcion.push(`Entrega: ${filtrosActivos.fechaEntrega.toLocaleDateString('es-ES')}`);
                }
                if (filtrosActivos.obra) {
                    descripcion.push(`Obra: ${filtrosActivos.obra}`);
                }
                if (filtrosActivos.estado) {
                    descripcion.push(`Estado: ${filtrosActivos.estado}`);
                }

                texto.textContent =
                    `${descripcion.join(' | ')} → ${cantidad} resultado${cantidad !== 1 ? 's' : ''}`;
                indicador.classList.remove('hidden');
            }

            /**
             * Puebla el select de obras con las obras únicas de los eventos
             */
            function inicializarSelectObras() {
                const obras = new Set();
                calendar.getEvents().forEach(evento => {
                    const obra = evento.extendedProps.obra;
                    if (obra && obra !== '—') {
                        obras.add(obra);
                    }
                });

                const select = document.getElementById('filtroObra');
                Array.from(obras).sort().forEach(obra => {
                    const option = document.createElement('option');
                    option.value = obra;
                    option.textContent = obra;
                    select.appendChild(option);
                });
            }

            // ================================
            // EVENT LISTENERS PARA FILTROS
            // ================================

            document.getElementById('aplicarResaltado').addEventListener('click', function() {
                // Capturar valores
                const fechaInput = document.getElementById('filtroFechaEntrega').value;
                const obraInput = document.getElementById('filtroObra').value;
                const estadoInput = document.getElementById('filtroEstado').value;

                // Actualizar filtros activos
                filtrosActivos.fechaEntrega = fechaInput ? new Date(fechaInput) : null;
                filtrosActivos.obra = obraInput || null;
                filtrosActivos.estado = estadoInput || null;

                // Aplicar
                aplicarResaltadoEventos();
            });

            document.getElementById('limpiarResaltado').addEventListener('click', function() {
                limpiarResaltado();

                Swal.fire({
                    icon: 'success',
                    title: 'Filtros limpiados',
                    timer: 1500,
                    showConfirmButton: false
                });
            });

            // Aplicar filtro al presionar Enter en el input de fecha
            document.getElementById('filtroFechaEntrega').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('aplicarResaltado').click();
                }
            });

            // Inicializar después de que el calendario esté listo
            setTimeout(() => {
                inicializarSelectObras();
            }, 500);

            /**
             * 🔧 FUNCIÓN DE DEBUG - Inspeccionar un evento específico
             * Úsala en la consola: debugEvento('nombre-del-evento')
             */
            window.debugEvento = function(nombreEvento) {
                console.clear();
                console.log('%c🔍 DEBUG DE EVENTO ESPECÍFICO',
                    'font-size: 16px; font-weight: bold; color: #8b5cf6;');
                console.log('━'.repeat(80));

                const eventos = calendar.getEvents();
                const evento = eventos.find(e => e.title.toLowerCase().includes(nombreEvento.toLowerCase()));

                if (!evento) {
                    console.error(`❌ No se encontró evento con nombre: "${nombreEvento}"`);
                    console.log('📋 Eventos disponibles:');
                    eventos.forEach((e, i) => console.log(`  ${i + 1}. ${e.title}`));
                    return;
                }

                console.log('✅ Evento encontrado:', evento.title);
                console.log('━'.repeat(80));

                console.group('📋 Información completa del evento');
                console.log('ID:', evento.id);
                console.log('Title:', evento.title);
                console.log('Start:', evento.start);
                console.log('End:', evento.end);
                console.log('Resource ID:', evento.getResources()[0]?.id);
                console.groupEnd();

                console.group('🔧 Extended Props');
                Object.entries(evento.extendedProps).forEach(([key, value]) => {
                    console.log(`${key}:`, value);
                });
                console.groupEnd();

                console.log('━'.repeat(80));
                console.log('🎯 Probando contra filtros activos:');
                cumpleFiltros(evento);
            };

            // Añade también esta función para listar todos los eventos
            window.listarEventos = function() {
                console.clear();
                console.log('%c📋 LISTA DE TODOS LOS EVENTOS',
                    'font-size: 16px; font-weight: bold; color: #059669;');
                console.log('━'.repeat(80));

                const eventos = calendar.getEvents();
                console.log(`Total: ${eventos.length} eventos\n`);

                eventos.forEach((e, i) => {
                    console.group(`${i + 1}. ${e.title}`);
                    console.log('Fecha entrega:', e.extendedProps.fecha_entrega);
                    console.log('Obra:', e.extendedProps.obra);
                    console.log('Estado:', e.extendedProps.estado);
                    console.groupEnd();
                });
            };

        });
    </script>
</x-app-layout>
