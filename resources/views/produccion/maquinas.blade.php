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
            <a href="{{ route('produccion.cargasMaquinas') }}"
               class="px-6 py-3 font-semibold text-gray-600 hover:text-blue-600 hover:bg-gray-50 border-b-2 border-transparent transition-colors">
                Cargas Máquinas
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
        <!-- Panel de filtros colapsable -->
        <div class="mt-4 bg-white shadow rounded-lg overflow-hidden">
            <!-- Header del panel (siempre visible) -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-3 py-2 flex items-center justify-between cursor-pointer hover:from-blue-700 hover:to-blue-800 transition-all"
                onclick="toggleFiltros()">
                <div class="flex items-center gap-2 text-white">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span class="font-semibold text-sm">Filtros de planillas</span>
                    <!-- Indicador de filtros activos -->
                    <span id="filtrosActivosBadge" class="hidden bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-0.5 rounded-full"></span>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Botón resetear -->
                    <button type="button" id="limpiarResaltado"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                        title="Restablecer filtros" onclick="event.stopPropagation()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                        </svg>
                    </button>
                    <!-- Flecha de expandir/colapsar -->
                    <svg id="filtrosChevron" class="w-5 h-5 text-white transform transition-transform duration-200"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- Contenido del panel (colapsable) -->
            <div id="panelFiltros" class="overflow-hidden transition-all duration-300" style="max-height: 0;">
                <div class="p-3 bg-gray-50">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7 gap-2">
                        <!-- Filtro por Cliente -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1 text-xs">Cliente</label>
                            <input type="text" id="filtroCliente" placeholder="Buscar..."
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Filtro por Código Cliente -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1 text-xs">Cód. Cliente</label>
                            <input type="text" id="filtroCodCliente" placeholder="Buscar..."
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Filtro por Obra -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1 text-xs">Obra</label>
                            <input type="text" id="filtroObra" placeholder="Buscar..."
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Filtro por Código Obra -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1 text-xs">Cód. Obra</label>
                            <input type="text" id="filtroCodObra" placeholder="Buscar..."
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Filtro por Código Planilla -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1 text-xs">Cód. Planilla</label>
                            <input type="text" id="filtroCodigoPlanilla" placeholder="Buscar..."
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Filtro por fecha de entrega -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1 text-xs">F. Entrega</label>
                            <input type="date" id="filtroFechaEntrega"
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Filtro por estado -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1 text-xs">Estado</label>
                            <select id="filtroEstado"
                                class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="fabricando">Fabricando</option>
                                <option value="completada">Completada</option>
                            </select>
                        </div>
                    </div>

                    <!-- Indicador de resultados -->
                    <div id="filtrosActivos" class="mt-2 text-xs text-blue-700 hidden">
                        <span class="font-semibold">📊</span>
                        <span id="textoFiltrosActivos"></span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Por esta versión con transición -->
        <div id="contenedor-calendario" class="bg-white shadow rounded-lg p-4 transition-all duration-300">
            <div id="calendario" class="w-full"></div>
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

    <div id="panel_overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40" style="pointer-events: none;"></div>

    <!-- Indicador de posición al arrastrar -->
    <div id="indicador_posicion"
        class="fixed bg-blue-600 text-white rounded-full shadow-lg font-bold hidden z-[99999] pointer-events-none"
        style="display: none; width: 48px; height: 48px; line-height: 48px; text-align: center; font-size: 20px;">
        <span id="numero_posicion">1</span>
    </div>


    <!-- Scripts externos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}"></script>
    <script src="{{ asset('js/multiselect-elementos.js') }}"></script>

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
            position: relative;
        }

        .elemento-drag:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .elemento-drag.fc-dragging {
            opacity: 0.5;
        }

        /* Elemento seleccionado */
        .elemento-drag.seleccionado {
            border-color: #2563eb;
            background-color: #eff6ff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .elemento-drag.seleccionado::before {
            content: '✓';
            position: absolute;
            top: 8px;
            right: 8px;
            background-color: #2563eb;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            z-index: 10;
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

        /* Badge con contador de selección */
        .selection-badge {
            position: fixed;
            bottom: 20px;
            right: 340px;
            background: #2563eb;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            font-weight: bold;
            z-index: 100;
            display: none;
            transition: all 0.3s;
        }

        .selection-badge.show {
            display: block;
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

        /* ===== EVENTOS SIN REVISAR ===== */
        .evento-sin-revisar {
            opacity: 0.7 !important;
            background-color: #9e9e9e !important;
            border: 2px dashed #757575 !important;
            cursor: not-allowed !important;
        }

        .evento-sin-revisar .fc-event-title {
            font-style: italic !important;
        }

        /* Tooltip/Hover para eventos sin revisar */
        .evento-sin-revisar:hover {
            opacity: 0.85 !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3) !important;
        }

        /* Eventos revisados (normales) */
        .evento-revisado {
            opacity: 1 !important;
            cursor: pointer !important;
        }

        /* ===== TURNOS - FONDOS DE COLOR ===== */
        .turno-manana {
            background-color: rgba(254, 240, 138, 0.5) !important; /* Amarillo brillante */
        }

        .turno-tarde {
            background-color: rgba(252, 211, 77, 0.5) !important; /* Naranja/Amarillo intenso */
        }

        .turno-noche {
            background-color: rgba(147, 197, 253, 0.5) !important; /* Azul claro */
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

        /* Indicador de posición durante arrastre */
        #indicador_posicion {
            transition: left 0.05s ease-out, top 0.05s ease-out;
        }

        #indicador_posicion span {
            display: block;
            width: 100%;
            height: 100%;
            line-height: 48px;
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

            // Referencias al indicador de posición
            const indicadorPosicion = document.getElementById('indicador_posicion');
            const numeroPosicion = document.getElementById('numero_posicion');

            // Variable para trackear elemento que se arrastra desde el panel
            let elementoArrastrandose = null;
            let eventoArrastrandose = null;
            let mostrarIndicador = false;
            let tooltipsDeshabilitados = false;

            // 🎯 Listener GLOBAL de mousemove para el indicador
            document.addEventListener('mousemove', function(e) {
                if (mostrarIndicador) {
                    indicadorPosicion.style.left = (e.clientX + 20) + 'px';
                    indicadorPosicion.style.top = (e.clientY - 20) + 'px';
                    indicadorPosicion.style.display = 'block';
                    indicadorPosicion.classList.remove('hidden');
                }
            });


            // Inicializar FullCalendar
            calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                initialView: 'resourceTimeGrid7Days',
                nextDayThreshold: '06:00:00',
                resourceLabelContent: function(arg) {
                    return {
                        html: `<a href="/maquinas/${arg.resource.id}" class="text-blue-600 hover:text-blue-800 hover:underline font-semibold">${arg.resource.title}</a>`
                    };
                },
                views: {
                    resourceTimeGrid7Days: {
                        type: 'resourceTimeGrid',
                        duration: {
                            days: 1
                        },
                        slotMinTime: '00:00:00',
                        slotMaxTime: '168:00:00',
                        slotDuration: '01:00:00',
                        dayHeaderContent: function(arg) {
                            return '';
                        },
                        buttonText: '7 días'
                    }
                },
                locale: 'es',
                timeZone: 'Europe/Madrid',
                initialDate: "{{ $initialDate }}",
                resources: maquinas,
                resourceOrder: false, // ✅ Mantener el orden del array sin reordenar por ID
                events: planillas,
                height: 'auto',
                scrollTime: '06:00:00',
                editable: true,
                eventResizableFromStart: false,
                eventDurationEditable: false,
                droppable: true, // ✅ Habilitar drop de elementos externos

                headerToolbar: {
                    left: '',
                    center: 'title',
                    right: ''
                },

                // 🎯 CLAVE: Configurar recepción de elementos externos
                eventReceive: async function(info) {
                    try {
                    // Ocultar indicador al soltar
                    mostrarIndicador = false;
                    indicadorPosicion.classList.add('hidden');
                    indicadorPosicion.style.display = 'none';

                    const elementoDiv = document.querySelector(
                        `.elemento-drag[data-elemento-id="${info.event.extendedProps.elementoId}"]`
                    );

                    if (!elementoDiv) {
                        info.revert();
                        return;
                    }

                    // Obtener datos de los elementos a mover (uno o varios)
                    const dataMovimiento = window.MultiSelectElementos.getDataElementosParaMover(elementoDiv);
                    console.log('📊 dataMovimiento:', dataMovimiento);


                    // Validar que tengamos la máquina original
                    if (!dataMovimiento.maquinaOriginal || isNaN(dataMovimiento.maquinaOriginal)) {
                        console.log('❌ No se pudo obtener maquina original');
                        console.error('Error: No se pudo obtener la máquina original del elemento', elementoDiv);
                        info.revert();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo determinar la máquina original del elemento'
                        });
                        return;
                    }

                    const maquinaDestinoId = parseInt(info.event.getResources()[0].id);
                    const maquinaDestinoNombre = info.event.getResources()[0].title;
                    console.log('🎯 Máquina destino:', maquinaDestinoId, maquinaDestinoNombre);

                    // Calcular la posición correcta donde se soltó el elemento
                    const eventosOrdenados = calendar.getEvents()
                        .filter(ev => ev.getResources().some(r => r.id == maquinaDestinoId))
                        .sort((a, b) => a.start - b.start);

                    // Encontrar posición basada en el tiempo donde se soltó
                    let nuevaPosicion = 1;
                    for (let i = 0; i < eventosOrdenados.length; i++) {
                        if (info.event.start < eventosOrdenados[i].start) {
                            nuevaPosicion = i + 1;
                            break;
                        }
                        nuevaPosicion = i + 2;
                    }

                    // Confirmar movimiento
                    const mensaje = dataMovimiento.cantidad > 1
                        ? `¿Mover ${dataMovimiento.cantidad} elementos a <strong>${maquinaDestinoNombre}</strong>?`
                        : `¿Mover elemento a <strong>${maquinaDestinoNombre}</strong>?`;

                    console.log('❓ Mostrando primer Swal de confirmación');
                    const resultado = await Swal.fire({
                        title: dataMovimiento.cantidad > 1 ? '¿Mover elementos?' : '¿Mover elemento?',
                        html: mensaje,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, mover',
                        cancelButtonText: 'Cancelar'
                    });

                    console.log('✅ Resultado primer Swal:', resultado);

                    if (!resultado.isConfirmed) {
                        console.log('❌ Usuario canceló el primer Swal');
                        info.revert();
                        return;
                    }

                    console.log('✅ Usuario confirmó movimiento, iniciando try-catch');

                    try {
                        console.log('🚀 Enviando petición a /planillas/reordenar');
                        const res = await fetch('/planillas/reordenar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                id: dataMovimiento.planillaId,
                                maquina_id: maquinaDestinoId,
                                maquina_origen_id: dataMovimiento.maquinaOriginal,
                                nueva_posicion: nuevaPosicion,
                                elementos_id: dataMovimiento.elementosIds
                            })
                        });

                        // Parsear respuesta JSON independientemente del código HTTP
                        let data;
                        try {
                            data = await res.json();
                        } catch (jsonError) {
                            console.error('❌ Error parseando JSON:', jsonError);
                            throw new Error('Error al procesar la respuesta del servidor');
                        }

                        // 🔍 IMPORTANTE: Verificar requiresNuevaPosicionConfirmation ANTES de verificar success
                        // Esto es necesario porque el backend devuelve 422 con requiresNuevaPosicionConfirmation
                        if (data.requiresNuevaPosicionConfirmation) {
                            console.log('✅ Mostrando diálogo de confirmación con 3 botones');
                            const resultadoConfirmacion = await Swal.fire({
                                title: 'Posición ya existe',
                                html: data.message + '<br><br><strong>¿Qué deseas hacer?</strong>',
                                icon: 'question',
                                showCancelButton: true,
                                showDenyButton: true,
                                confirmButtonText: 'Crear nueva posición',
                                denyButtonText: 'Usar posición existente',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#10b981',
                                denyButtonColor: '#3b82f6',
                                cancelButtonColor: '#6b7280',
                                reverseButtons: false,
                                allowOutsideClick: false,
                                buttonsStyling: true
                            });

                            if (resultadoConfirmacion.isConfirmed) {
                                // Usuario quiere crear una nueva posición
                                const res2 = await fetch('/planillas/reordenar', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    body: JSON.stringify({
                                        id: dataMovimiento.planillaId,
                                        maquina_id: maquinaDestinoId,
                                        maquina_origen_id: dataMovimiento.maquinaOriginal,
                                        nueva_posicion: nuevaPosicion,
                                        elementos_id: dataMovimiento.elementosIds,
                                        crear_nueva_posicion: true
                                    })
                                });

                                const data2 = await res2.json();

                                if (!res2.ok || !data2.success) {
                                    throw new Error(data2.message || 'Error al mover elementos');
                                }

                                // Actualizar calendario
                                actualizarEventosSinRecargar(data2.eventos, [dataMovimiento.maquinaOriginal, maquinaDestinoId]);

                                // Remover elementos del panel
                                window.MultiSelectElementos.removerElementosDelPanel(dataMovimiento.elementosIds);

                                // Remover el evento temporal que se creó
                                info.event.remove();

                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 1500,
                                    timerProgressBar: true,
                                });
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Nueva posición creada'
                                });

                            } else if (resultadoConfirmacion.isDenied) {
                                // Usuario quiere mover a la posición existente
                                const res2 = await fetch('/planillas/reordenar', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    body: JSON.stringify({
                                        id: dataMovimiento.planillaId,
                                        maquina_id: maquinaDestinoId,
                                        maquina_origen_id: dataMovimiento.maquinaOriginal,
                                        nueva_posicion: nuevaPosicion,
                                        elementos_id: dataMovimiento.elementosIds,
                                        crear_nueva_posicion: false
                                    })
                                });

                                const data2 = await res2.json();

                                if (!res2.ok || !data2.success) {
                                    throw new Error(data2.message || 'Error al mover elementos');
                                }

                                // Actualizar calendario
                                actualizarEventosSinRecargar(data2.eventos, [dataMovimiento.maquinaOriginal, maquinaDestinoId]);

                                // Remover elementos del panel
                                window.MultiSelectElementos.removerElementosDelPanel(dataMovimiento.elementosIds);

                                // Remover el evento temporal que se creó
                                info.event.remove();

                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 1500,
                                    timerProgressBar: true,
                                });
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Elementos movidos a posición existente'
                                });

                            } else {
                                // Usuario canceló
                                info.revert();
                            }

                            return;
                        }

                        // Solo verificar errores si NO es el caso de requiresNuevaPosicionConfirmation
                        if ((!res.ok || !data.success) && !data.requiresNuevaPosicionConfirmation) {
                            throw new Error(data.message || 'Error al mover elementos');
                        }

                        // Actualizar calendario
                        actualizarEventosSinRecargar(data.eventos, [dataMovimiento.maquinaOriginal, maquinaDestinoId]);

                        // Remover elementos del panel
                        window.MultiSelectElementos.removerElementosDelPanel(dataMovimiento.elementosIds);

                        // Remover el evento temporal que se creó
                        info.event.remove();

                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                        });
                        Toast.fire({
                            icon: 'success',
                            title: dataMovimiento.cantidad > 1
                                ? `${dataMovimiento.cantidad} elementos movidos`
                                : 'Elemento movido'
                        });

                    } catch (error) {
                        console.error('❌ Error en eventReceive (try interno):', error);
                        console.error('Stack trace:', error.stack);
                        info.revert();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'No se pudo mover el elemento'
                        });
                    }
                    } catch (globalError) {
                        console.error('💥💥💥 ERROR GLOBAL EN eventReceive:', globalError);
                        console.error('💥 Stack:', globalError.stack);
                        console.error('💥 Message:', globalError.message);
                        info.revert();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error crítico',
                            html: `<strong>Error:</strong> ${globalError.message}<br><br><pre>${globalError.stack}</pre>`
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
                    // Inicializar contador de slots si no existe
                    if (!calendar._slotCounter) {
                        calendar._slotCounter = 0;
                        calendar._lastViewStart = null;
                    }

                    // Reiniciar contador si cambia la vista
                    const currentViewStart = calendar.view.currentStart.getTime();
                    if (calendar._lastViewStart !== currentViewStart) {
                        calendar._slotCounter = 0;
                        calendar._lastViewStart = currentViewStart;
                    }

                    // Obtener el inicio de la vista
                    const viewStart = new Date(calendar.view.currentStart);

                    // Calcular la hora absoluta desde el inicio basándose en el contador
                    const horaAbsoluta = calendar._slotCounter;
                    const diasCompletos = Math.floor(horaAbsoluta / 24);
                    const horaDelDia = horaAbsoluta % 24;
                    const minutos = 0; // Los slots son de hora en hora

                    // Calcular la fecha real
                    const fechaReal = new Date(viewStart);
                    fechaReal.setDate(fechaReal.getDate() + diasCompletos);
                    fechaReal.setHours(horaDelDia, minutos, 0, 0);

                    // Incrementar contador para el siguiente slot
                    calendar._slotCounter++;

                    // Formatear la hora para mostrar
                    const timeText = `${horaDelDia.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;

                    // Determinar si este slot corresponde a un inicio de turno
                    let esTurno = false;
                    let nombreTurno = '';
                    let fechaMostrar = new Date(fechaReal);

                    if (horaDelDia === 7 && minutos === 0) {
                        // Turno Mañana (muestra la fecha del mismo día)
                        esTurno = true;
                        nombreTurno = '☀️ Mañana';
                    } else if (horaDelDia === 15 && minutos === 0) {
                        // Turno Tarde (muestra la fecha del mismo día)
                        esTurno = true;
                        nombreTurno = '🌤️ Tarde';
                    } else if (horaDelDia === 23 && minutos === 0) {
                        // Turno Noche (muestra la fecha del día siguiente porque trabaja de noche)
                        esTurno = true;
                        nombreTurno = '🌙 Noche';
                        fechaMostrar = new Date(fechaReal.getTime());
                        fechaMostrar.setDate(fechaMostrar.getDate() + 1);
                    }

                    let contenido = '';

                    if (esTurno) {
                        // Formatear fecha para mostrar
                        const dia = fechaMostrar.getDate().toString().padStart(2, '0');
                        const mes = (fechaMostrar.getMonth() + 1).toString().padStart(2, '0');
                        const año = fechaMostrar.getFullYear();
                        const nombreDia = fechaMostrar.toLocaleDateString('es-ES', {
                            weekday: 'short'
                        }).toUpperCase();
                        const fechaFormateada = `${dia}/${mes}/${año}`;

                        contenido = `
            <div class="turno-con-fecha">
                <div class="fecha-turno">${nombreDia}<br>${fechaFormateada}</div>
                <div class="hora-text">${timeText}</div>
                <span class="turno-label">${nombreTurno}</span>
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
                    const eventId = arg.event.id || arg.event._def.publicId;

                    if (typeof progreso === 'number') {
                        return {
                            html: `
                                <div class="w-full px-1 py-0.5 text-xs font-semibold text-white" data-event-id="${eventId}">
                                    <div class="mb-0.5 truncate" title="${arg.event.title}">${arg.event.title}</div>
                                    <div class="w-full h-2 bg-gray-300 rounded overflow-hidden">
                                        <div class="h-2 bg-blue-500 rounded transition-all duration-500" style="width: ${progreso}%; min-width: 1px;"></div>
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

                // 🎯 Eventos para mostrar indicador de posición al arrastrar
                eventDragStart: function(info) {
                    eventoArrastrandose = info.event;
                    mostrarIndicador = true;
                    tooltipsDeshabilitados = true;

                    // Ocultar todos los tooltips existentes
                    document.querySelectorAll('.fc-tooltip').forEach(t => t.style.display = 'none');

                    // Calcular posición inicial
                    const recursoId = info.event.getResources()[0]?.id;
                    if (recursoId) {
                        const eventosOrdenados = calendar.getEvents()
                            .filter(ev => ev.getResources().some(r => r.id == recursoId) && ev.id !== info.event.id)
                            .sort((a, b) => a.start - b.start);

                        let posicion = 1;
                        for (let i = 0; i < eventosOrdenados.length; i++) {
                            if (info.event.start < eventosOrdenados[i].start) {
                                posicion = i + 1;
                                break;
                            }
                            posicion = i + 2;
                        }
                        numeroPosicion.textContent = posicion;
                    }
                },

                eventAllow: function(dropInfo, draggedEvent) {
                    // Este se ejecuta constantemente mientras arrastras
                    if (mostrarIndicador && draggedEvent) {
                        const recursoId = dropInfo.resource?.id;

                        if (recursoId) {
                            const eventosOrdenados = calendar.getEvents()
                                .filter(ev => ev.getResources().some(r => r.id == recursoId) && ev.id !== draggedEvent.id)
                                .sort((a, b) => a.start - b.start);

                            // Usar el tiempo de dropInfo para calcular posición
                            const tiempoDestino = dropInfo.start;
                            let posicionDestino = 1;

                            for (let i = 0; i < eventosOrdenados.length; i++) {
                                if (tiempoDestino < eventosOrdenados[i].start) {
                                    posicionDestino = i + 1;
                                    break;
                                }
                                posicionDestino = i + 2;
                            }

                            numeroPosicion.textContent = posicionDestino;
                        }
                    }
                    return true; // Permitir el drop
                },

                eventDragStop: function(info) {
                    eventoArrastrandose = null;
                    mostrarIndicador = false;
                    tooltipsDeshabilitados = false;
                    indicadorPosicion.classList.add('hidden');
                    indicadorPosicion.style.display = 'none';

                    // Limpiar tooltips duplicados que puedan haberse creado
                    document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());
                },

                eventDrop: async function(info) {
                    // Limpiar tooltips residuales
                    document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());

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

                        // 🔍 Verificar si requiere confirmación de nueva posición
                        if (data.requiresNuevaPosicionConfirmation) {

                            const confirmacion = await Swal.fire({
                                title: 'Posición ya existe',
                                html: data.message + '<br><br><strong>¿Qué deseas hacer?</strong>',
                                icon: 'question',
                                showCancelButton: true,
                                showDenyButton: true,
                                confirmButtonText: 'Crear nueva posición',
                                denyButtonText: 'Usar posición existente',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#10b981',
                                denyButtonColor: '#3b82f6',
                                cancelButtonColor: '#6b7280',
                                reverseButtons: false,
                                allowOutsideClick: false,
                                buttonsStyling: true
                            });

                            if (confirmacion.isConfirmed) {
                                // Crear nueva posición
                                const res2 = await fetch('/planillas/reordenar', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    body: JSON.stringify({
                                        id: planillaId,
                                        maquina_id: maquinaDestinoId,
                                        maquina_origen_id: maquinaOrigenId,
                                        nueva_posicion: nuevaPosicion,
                                        elementos_id: elementosId,
                                        crear_nueva_posicion: true
                                    })
                                });

                                const data2 = await res2.json();
                                if (!res2.ok || !data2.success) {
                                    throw new Error(data2.message || 'Error al crear nueva posición');
                                }

                                actualizarEventosSinRecargar(data2.eventos, [maquinaOrigenId, maquinaDestinoId]);

                                Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 1500,
                                    timerProgressBar: true
                                }).fire({
                                    icon: 'success',
                                    title: 'Nueva posición creada'
                                });

                            } else if (confirmacion.isDenied) {
                                // Usar posición existente
                                const res2 = await fetch('/planillas/reordenar', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    body: JSON.stringify({
                                        id: planillaId,
                                        maquina_id: maquinaDestinoId,
                                        maquina_origen_id: maquinaOrigenId,
                                        nueva_posicion: nuevaPosicion,
                                        elementos_id: elementosId,
                                        usar_posicion_existente: true
                                    })
                                });

                                const data2 = await res2.json();
                                if (!res2.ok || !data2.success) {
                                    throw new Error(data2.message || 'Error al mover a posición existente');
                                }

                                actualizarEventosSinRecargar(data2.eventos, [maquinaOrigenId, maquinaDestinoId]);

                                Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 1500,
                                    timerProgressBar: true
                                }).fire({
                                    icon: 'success',
                                    title: 'Planilla movida a posición existente'
                                });

                            } else {
                                // Cancelar
                                info.revert();
                            }

                            return;
                        }

                        if (!res.ok || !data.success) {
                            throw new Error(data.message || 'Error al reordenar');
                        }

                        actualizarEventosSinRecargar(data.eventos, [maquinaOrigenId, maquinaDestinoId]);

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

                    // ⚠️ Información de revisión
                    let estadoRevision = '';
                    if (props.revisada === false || props.revisada === 0) {
                        estadoRevision = '<br><span class="text-red-400 font-bold">⚠️ SIN REVISAR - No iniciar producción</span>';
                    } else if (props.revisada === true || props.revisada === 1) {
                        estadoRevision = `<br><span class="text-green-400">✅ Revisada por ${props.revisada_por || 'N/A'}</span>`;
                    }

                    tooltip.innerHTML = `
                        <div class="bg-gray-900 text-white text-xs rounded px-2 py-1 shadow-md max-w-xs">
                            <strong>${info.event.title}</strong><br>
                            Obra: ${props.obra}<br>
                            Estado producción: ${props.estado}<br>
                            Fin programado: <span class="text-yellow-300">${props.fin_programado}</span><br>
                            Fecha estimada entrega: <span class="text-green-300">${props.fecha_entrega}</span>${estadoRevision}
                        </div>`;
                    tooltip.style.display = 'none';
                    document.body.appendChild(tooltip);

                    info.el.addEventListener('mouseenter', function(e) {
                        if (!tooltipsDeshabilitados) {
                            tooltip.style.left = e.pageX + 10 + 'px';
                            tooltip.style.top = e.pageY + 10 + 'px';
                            tooltip.style.display = 'block';
                        }
                    });
                    info.el.addEventListener('mousemove', function(e) {
                        if (!tooltipsDeshabilitados) {
                            tooltip.style.left = e.pageX + 10 + 'px';
                            tooltip.style.top = e.pageY + 10 + 'px';
                        }
                    });
                    info.el.addEventListener('mouseleave', function() {
                        tooltip.style.display = 'none';
                    });
                }
            });
            calendar.render();
            window.calendar = calendar;

            // 🎯 Listener para calcular posición al hacer drop de elementos externos
            const calendarioEl = document.getElementById('calendario');
            let ultimoRecursoDetectado = null;
            let ultimaTiempoDetectado = null;

            calendarioEl.addEventListener('dragover', function(e) {
                if (!elementoArrastrandose) return;

                e.preventDefault();

                const elementosBajoMouse = document.elementsFromPoint(e.clientX, e.clientY);

                // Buscar cualquier elemento que tenga data-resource-id
                let resourceId = null;
                for (const el of elementosBajoMouse) {
                    if (el.dataset.resourceId) {
                        resourceId = el.dataset.resourceId;
                        break;
                    }
                    let parent = el.parentElement;
                    while (parent && !resourceId) {
                        if (parent.dataset.resourceId) {
                            resourceId = parent.dataset.resourceId;
                            break;
                        }
                        parent = parent.parentElement;
                    }
                    if (resourceId) break;
                }

                if (!resourceId) {
                    numeroPosicion.textContent = '?';
                    return;
                }

                // Obtener todos los eventos de esa máquina ordenados
                const eventosOrdenados = calendar.getEvents()
                    .filter(ev => ev.getResources().some(r => r.id == resourceId))
                    .sort((a, b) => a.start - b.start);

                // Buscar evento más cercano bajo el cursor para estimar posición
                let eventoMasCercano = null;
                let distanciaMinima = Infinity;

                eventosOrdenados.forEach(evento => {
                    const eventoEls = document.querySelectorAll(`.fc-event[data-event-id="${evento.id}"]`);
                    eventoEls.forEach(eventoEl => {
                        const rect = eventoEl.getBoundingClientRect();
                        const distancia = Math.abs(e.clientY - (rect.top + rect.height / 2));
                        if (distancia < distanciaMinima) {
                            distanciaMinima = distancia;
                            eventoMasCercano = evento;
                        }
                    });
                });

                let posicionCalculada = 1;

                if (eventoMasCercano) {
                    const indexCercano = eventosOrdenados.findIndex(ev => ev.id === eventoMasCercano.id);
                    const eventoEl = document.querySelector(`.fc-event[data-event-id="${eventoMasCercano.id}"]`);
                    if (eventoEl) {
                        const rect = eventoEl.getBoundingClientRect();
                        const mitadAltura = rect.top + (rect.height / 2);

                        if (e.clientY < mitadAltura) {
                            posicionCalculada = indexCercano + 1;
                        } else {
                            posicionCalculada = indexCercano + 2;
                        }
                    } else {
                        posicionCalculada = indexCercano + 1;
                    }
                } else {
                    posicionCalculada = 1;
                }

                numeroPosicion.textContent = posicionCalculada;
            });

            // Función para actualizar eventos sin recargar
            function actualizarEventosSinRecargar(eventosNuevos, maquinasAfectadas = null) {
                document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());

                if (!eventosNuevos || !Array.isArray(eventosNuevos)) {
                    console.warn('No se recibieron eventos para actualizar');
                    return;
                }

                // Si no se pasan máquinas afectadas, extraerlas de los eventos
                if (!maquinasAfectadas) {
                    maquinasAfectadas = [...new Set(eventosNuevos.map(e => String(e.resourceId)))];
                } else {
                    // Asegurar que sean strings
                    maquinasAfectadas = maquinasAfectadas.map(id => String(id));
                }

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
                    div.title = ''; // Evitar tooltip nativo del navegador

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
                        <canvas id="${canvasId}" width="240" height="120" draggable="false"></canvas>
                        <div class="elemento-info-mini" draggable="false">
                            <span draggable="false"><strong>⌀${elemento.diametro}mm</strong></span>
                            <span draggable="false"><strong>${elemento.peso}kg</strong></span>
                        </div>
                    `;

                    lista.appendChild(div);

                    // ✅ Evento de clic para selección múltiple
                    div.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        window.MultiSelectElementos.toggleSeleccion(div);
                    });

                    setTimeout(() => {
                        window.dibujarFiguraElemento(canvasId, elemento.dimensiones, elemento.peso);
                    }, 10);
                });

                // Configurar FullCalendar.Draggable con timeout para asegurar que se ejecuta
                setTimeout(() => {
                    const draggable = new FullCalendar.Draggable(lista, {
                        itemSelector: '.elemento-drag',
                        eventData: function(eventEl) {
                            return JSON.parse(eventEl.dataset.event);
                        }
                    });

                    // Usar eventos nativos del DOM
                    lista.addEventListener('mousedown', function(e) {
                        const target = e.target.closest('.elemento-drag');
                        if (target) {
                            setTimeout(() => {
                                elementoArrastrandose = target;
                                mostrarIndicador = true;
                                tooltipsDeshabilitados = true;
                                numeroPosicion.textContent = '?';
                                document.querySelectorAll('.fc-tooltip').forEach(t => t.style.display = 'none');
                            }, 50);
                        }
                    });

                    document.addEventListener('mouseup', function(e) {
                        if (elementoArrastrandose) {
                            setTimeout(() => {
                                elementoArrastrandose = null;
                                mostrarIndicador = false;
                                tooltipsDeshabilitados = false;
                                indicadorPosicion.classList.add('hidden');
                                indicadorPosicion.style.display = 'none';
                                document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());
                            }, 100);
                        }
                    });
                }, 100);

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
                // Limpiar selección múltiple
                window.MultiSelectElementos.limpiarSelecciones();
                document.body.classList.remove('panel-abierto');
                document.getElementById('panel_elementos').classList.remove('abierto');
                document.getElementById('panel_overlay').classList.add('hidden');

                setTimeout(() => {
                    calendar.updateSize();
                }, 300);
            }

            document.getElementById('cerrar_panel').addEventListener('click', cerrarPanel);
            // Overlay ya no captura clics (pointer-events: none) para permitir interacción con calendario

            // Cerrar panel al hacer clic fuera del panel (en el área del calendario)
            document.addEventListener('click', function(e) {
                const panel = document.getElementById('panel_elementos');
                const panelAbierto = panel.classList.contains('abierto');

                if (panelAbierto && !panel.contains(e.target) && !e.target.closest('.fc-event')) {
                    // Solo cerrar si se hace clic fuera del panel y no en un elemento arrastrable
                    const clickEnCalendario = e.target.closest('#contenedor-calendario');
                    if (clickEnCalendario && !e.target.closest('.elemento-drag')) {
                        cerrarPanel();
                    }
                }
            });

            // ================================
            // SISTEMA DE FILTROS DE RESALTADO
            // ================================

            let filtrosActivos = {
                cliente: null,
                codCliente: null,
                obra: null,
                codObra: null,
                codigoPlanilla: null,
                fechaEntrega: null,
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

                // Filtro por cliente (búsqueda parcial, case-insensitive)
                if (filtrosActivos.cliente && filtrosActivos.cliente !== '') {
                    const cliente = (props.cliente || '').toLowerCase();
                    const filtro = filtrosActivos.cliente.toLowerCase();
                    const cumple = cliente.includes(filtro);
                    console.log('Cliente filtro:', filtrosActivos.cliente);
                    console.log('Cliente evento:', props.cliente);
                    console.log('Cumple cliente:', cumple ? '✅' : '❌');
                    if (!cumple) return false;
                }

                // Filtro por código cliente (búsqueda parcial, case-insensitive)
                if (filtrosActivos.codCliente && filtrosActivos.codCliente !== '') {
                    const codCliente = (props.cod_cliente || '').toLowerCase();
                    const filtro = filtrosActivos.codCliente.toLowerCase();
                    const cumple = codCliente.includes(filtro);
                    console.log('Código Cliente filtro:', filtrosActivos.codCliente);
                    console.log('Código Cliente evento:', props.cod_cliente);
                    console.log('Cumple código cliente:', cumple ? '✅' : '❌');
                    if (!cumple) return false;
                }

                // Filtro por obra (búsqueda parcial, case-insensitive)
                if (filtrosActivos.obra && filtrosActivos.obra !== '') {
                    const obra = (props.obra || '').toLowerCase();
                    const filtro = filtrosActivos.obra.toLowerCase();
                    const cumple = obra.includes(filtro);
                    console.log('Obra filtro:', filtrosActivos.obra);
                    console.log('Obra evento:', props.obra);
                    console.log('Cumple obra:', cumple ? '✅' : '❌');
                    if (!cumple) return false;
                }

                // Filtro por código obra (búsqueda parcial, case-insensitive)
                if (filtrosActivos.codObra && filtrosActivos.codObra !== '') {
                    const codObra = (props.cod_obra || '').toLowerCase();
                    const filtro = filtrosActivos.codObra.toLowerCase();
                    const cumple = codObra.includes(filtro);
                    console.log('Código Obra filtro:', filtrosActivos.codObra);
                    console.log('Código Obra evento:', props.cod_obra);
                    console.log('Cumple código obra:', cumple ? '✅' : '❌');
                    if (!cumple) return false;
                }

                // Filtro por código planilla (búsqueda parcial, case-insensitive)
                if (filtrosActivos.codigoPlanilla && filtrosActivos.codigoPlanilla !== '') {
                    const codigoPlanilla = (props.codigo_planilla || '').toLowerCase();
                    const filtro = filtrosActivos.codigoPlanilla.toLowerCase();
                    const cumple = codigoPlanilla.includes(filtro);
                    console.log('Código Planilla filtro:', filtrosActivos.codigoPlanilla);
                    console.log('Código Planilla evento:', props.codigo_planilla);
                    console.log('Cumple código planilla:', cumple ? '✅' : '❌');
                    if (!cumple) return false;
                }

                // Filtro por fecha de entrega
                if (filtrosActivos.fechaEntrega) {
                    const fechaEvento = parsearFechaEvento(props.fecha_entrega);
                    const cumple = fechasIguales(fechaEvento, filtrosActivos.fechaEntrega);
                    console.log('Fecha filtro:', filtrosActivos.fechaEntrega.toLocaleDateString('es-ES'));
                    console.log('Fecha evento:', props.fecha_entrega);
                    console.log('Cumple fecha:', cumple ? '✅' : '❌');
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
                            // Buscar TODAS las representaciones DOM de este evento
                            // Un evento puede tener múltiples elementos DOM si está en varias posiciones
                            const elementosDOM = [];

                            // Primero intentar con evento.el
                            if (evento.el) {
                                elementosDOM.push(evento.el);
                            }

                            // Buscar todas las instancias DOM que coincidan con este evento ID
                            const todosElementos = document.querySelectorAll('.fc-event');
                            todosElementos.forEach(el => {
                                // Verificar por fcSeg
                                if (el.fcSeg && el.fcSeg.eventRange.def.publicId === evento.id) {
                                    // Evitar duplicados
                                    if (!elementosDOM.includes(el)) {
                                        elementosDOM.push(el);
                                    }
                                }
                                // También verificar por atributos data
                                const dataEventId = el.getAttribute('data-event-id') ||
                                                  el.querySelector('[data-event-id]')?.getAttribute('data-event-id');
                                if (dataEventId === evento.id && !elementosDOM.includes(el)) {
                                    elementosDOM.push(el);
                                }
                            });

                            if (elementosDOM.length === 0) {
                                console.warn('⚠️ No se encontró ningún elemento DOM para evento:', evento.id);
                                return;
                            }

                            // Aplicar clases a TODOS los elementos DOM encontrados
                            elementosDOM.forEach(elementoDOM => {
                                // Remover clases previas
                                elementoDOM.classList.remove('evento-resaltado', 'evento-opaco', 'pulsando');

                                if (cumple) {
                                    elementoDOM.classList.add('evento-resaltado', 'pulsando');
                                    console.log('✅ Elemento resaltado:', evento.id);
                                } else {
                                    elementoDOM.classList.add('evento-opaco');
                                    console.log('⚪ Elemento opacado:', evento.id);
                                }
                            });

                            // Contar segmentos resaltados (no elementos DOM)
                            if (cumple) {
                                segmentosResaltados++;
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
                    cliente: null,
                    codCliente: null,
                    obra: null,
                    codObra: null,
                    codigoPlanilla: null,
                    fechaEntrega: null,
                    estado: null
                };

                console.log('✅ Todos los resaltados eliminados');
                console.log('✅ Filtros reseteados');

                // Limpiar inputs
                document.getElementById('filtroFechaEntrega').value = '';
                document.getElementById('filtroObra').value = '';
                document.getElementById('filtroEstado').value = '';

                // Ocultar indicador y badge
                document.getElementById('filtrosActivos').classList.add('hidden');
                document.getElementById('filtrosActivosBadge').classList.add('hidden');
            }
            /**
             * Actualiza el indicador visual de filtros activos
             */
            function actualizarIndicadorFiltros(cantidad) {
                const indicador = document.getElementById('filtrosActivos');
                const texto = document.getElementById('textoFiltrosActivos');
                const badge = document.getElementById('filtrosActivosBadge');

                let descripcion = [];

                if (filtrosActivos.cliente) {
                    descripcion.push(`Cliente: ${filtrosActivos.cliente}`);
                }
                if (filtrosActivos.codCliente) {
                    descripcion.push(`Cód.Cliente: ${filtrosActivos.codCliente}`);
                }
                if (filtrosActivos.obra) {
                    descripcion.push(`Obra: ${filtrosActivos.obra}`);
                }
                if (filtrosActivos.codObra) {
                    descripcion.push(`Cód.Obra: ${filtrosActivos.codObra}`);
                }
                if (filtrosActivos.codigoPlanilla) {
                    descripcion.push(`Cód.Planilla: ${filtrosActivos.codigoPlanilla}`);
                }
                if (filtrosActivos.fechaEntrega) {
                    descripcion.push(`Entrega: ${filtrosActivos.fechaEntrega.toLocaleDateString('es-ES')}`);
                }
                if (filtrosActivos.estado) {
                    descripcion.push(`Estado: ${filtrosActivos.estado}`);
                }

                // Actualizar texto del indicador dentro del panel
                texto.textContent =
                    `${descripcion.join(' | ')} → ${cantidad} resultado${cantidad !== 1 ? 's' : ''}`;
                indicador.classList.remove('hidden');

                // Actualizar badge en el header
                badge.textContent = cantidad;
                badge.classList.remove('hidden');
            }


            // ================================
            // EVENT LISTENERS PARA FILTROS
            // ================================

            // Función para capturar y aplicar filtros
            function capturarYAplicarFiltros() {
                // Capturar valores de todos los campos
                const clienteInput = document.getElementById('filtroCliente').value.trim();
                const codClienteInput = document.getElementById('filtroCodCliente').value.trim();
                const obraInput = document.getElementById('filtroObra').value.trim();
                const codObraInput = document.getElementById('filtroCodObra').value.trim();
                const codigoPlanillaInput = document.getElementById('filtroCodigoPlanilla').value.trim();
                const fechaInput = document.getElementById('filtroFechaEntrega').value;
                const estadoInput = document.getElementById('filtroEstado').value;

                // Actualizar filtros activos
                filtrosActivos.cliente = clienteInput || null;
                filtrosActivos.codCliente = codClienteInput || null;
                filtrosActivos.obra = obraInput || null;
                filtrosActivos.codObra = codObraInput || null;
                filtrosActivos.codigoPlanilla = codigoPlanillaInput || null;
                filtrosActivos.fechaEntrega = fechaInput ? new Date(fechaInput) : null;
                filtrosActivos.estado = estadoInput || null;

                // Aplicar
                aplicarResaltadoEventos();
            }

            // Debounce para evitar ejecutar la función demasiadas veces
            let filtroTimeout;
            function aplicarFiltrosConDebounce() {
                clearTimeout(filtroTimeout);
                filtroTimeout = setTimeout(() => {
                    capturarYAplicarFiltros();
                }, 300); // Esperar 300ms después de dejar de escribir
            }

            // Función para abrir/cerrar panel de filtros
            window.toggleFiltros = function() {
                const panel = document.getElementById('panelFiltros');
                const chevron = document.getElementById('filtrosChevron');

                if (panel.style.maxHeight === '0px' || panel.style.maxHeight === '') {
                    // Abrir
                    panel.style.maxHeight = panel.scrollHeight + 'px';
                    chevron.style.transform = 'rotate(180deg)';
                } else {
                    // Cerrar
                    panel.style.maxHeight = '0px';
                    chevron.style.transform = 'rotate(0deg)';
                }
            };

            // Listeners en tiempo real para campos de texto
            ['filtroCliente', 'filtroCodCliente', 'filtroObra', 'filtroCodObra', 'filtroCodigoPlanilla'].forEach(id => {
                document.getElementById(id).addEventListener('input', aplicarFiltrosConDebounce);
            });

            // Listeners para campos que cambian de valor inmediatamente
            document.getElementById('filtroFechaEntrega').addEventListener('change', capturarYAplicarFiltros);
            document.getElementById('filtroEstado').addEventListener('change', capturarYAplicarFiltros);

            document.getElementById('limpiarResaltado').addEventListener('click', function() {
                // Limpiar los valores de los inputs
                document.getElementById('filtroCliente').value = '';
                document.getElementById('filtroCodCliente').value = '';
                document.getElementById('filtroObra').value = '';
                document.getElementById('filtroCodObra').value = '';
                document.getElementById('filtroCodigoPlanilla').value = '';
                document.getElementById('filtroFechaEntrega').value = '';
                document.getElementById('filtroEstado').value = '';

                // Limpiar y aplicar (esto limpiará los resaltados automáticamente)
                limpiarResaltado();
            });

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

            // ========================================
            // 🔄 SISTEMA DE ACTUALIZACIÓN EN TIEMPO REAL
            // ========================================

            let ultimoTimestamp = new Date().toISOString();
            let intervaloPolling = null;
            let calendarioVisible = true;
            let actualizacionesRecibidas = 0;

            // Detectar visibilidad de la pestaña para pausar polling
            document.addEventListener('visibilitychange', () => {
                calendarioVisible = !document.hidden;

                if (calendarioVisible) {
                    console.log('🟢 Pestaña visible - Iniciando polling');
                    iniciarPolling();
                } else {
                    console.log('🔴 Pestaña oculta - Pausando polling');
                    detenerPolling();
                }
            });

            function iniciarPolling() {
                if (intervaloPolling) return; // Ya está activo

                console.log('🚀 Sistema de polling iniciado (cada 5 segundos)');

                intervaloPolling = setInterval(async () => {
                    try {
                        const url = `/produccion/maquinas/actualizaciones?timestamp=${encodeURIComponent(ultimoTimestamp)}`;
                        console.log('📡 Solicitando actualizaciones:', url);

                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        });

                        console.log('📥 Respuesta recibida:', {
                            status: response.status,
                            statusText: response.statusText,
                            ok: response.ok
                        });

                        if (!response.ok) {
                            console.error(`❌ Error HTTP: ${response.status} ${response.statusText}`);
                            const text = await response.text();
                            console.error('Respuesta:', text.substring(0, 200));
                            return;
                        }

                        const data = await response.json();
                        console.log('📦 Datos recibidos:', data);

                        if (data.success && data.actualizaciones && data.actualizaciones.length > 0) {
                            console.log(`🔄 ${data.total} actualización(es) recibida(s)`);
                            aplicarActualizaciones(data.actualizaciones);
                            ultimoTimestamp = data.timestamp;
                            actualizacionesRecibidas += data.total;

                            // Notificación visual
                            mostrarNotificacion(`${data.total} planilla(s) actualizada(s)`, 'info');
                        } else {
                            console.log('✅ No hay actualizaciones nuevas');
                        }
                    } catch (error) {
                        console.error('❌ Error al obtener actualizaciones:', error);
                        console.error('Stack:', error.stack);
                    }
                }, 5000); // Cada 5 segundos
            }

            function detenerPolling() {
                if (intervaloPolling) {
                    clearInterval(intervaloPolling);
                    intervaloPolling = null;
                    console.log('⏸️ Polling detenido');
                }
            }

            function aplicarActualizaciones(actualizaciones) {
                actualizaciones.forEach(upd => {
                    // Buscar todos los eventos de esta planilla y máquina
                    const eventos = calendar.getEvents().filter(e => {
                        const eventoId = e.id || '';
                        return eventoId.includes(`planilla-${upd.planilla_id}`) &&
                               e.getResources()[0]?.id == upd.maquina_id;
                    });

                    if (eventos.length === 0) {
                        console.log(`⚠️ No se encontraron eventos para planilla ${upd.planilla_id} en máquina ${upd.maquina_id}`);
                        return;
                    }

                    eventos.forEach(evento => {
                        let cambios = [];

                        // 1. Actualizar progreso
                        const progresoAnterior = evento.extendedProps.progreso;
                        if (progresoAnterior !== upd.progreso) {
                            evento.setExtendedProp('progreso', upd.progreso);
                            cambios.push(`progreso: ${progresoAnterior}% → ${upd.progreso}%`);

                            // Actualizar barra de progreso visual
                            actualizarBarraProgreso(evento._def.publicId, upd.progreso);
                        }

                        // 2. Actualizar estado
                        if (evento.extendedProps.estado !== upd.estado) {
                            evento.setExtendedProp('estado', upd.estado);
                            cambios.push(`estado: ${evento.extendedProps.estado} → ${upd.estado}`);
                        }

                        // 3. Actualizar revisión
                        const revisadaAnterior = evento.extendedProps.revisada;
                        if (revisadaAnterior !== upd.revisada) {
                            evento.setExtendedProp('revisada', upd.revisada);
                            cambios.push(`revisada: ${revisadaAnterior} → ${upd.revisada}`);

                            // Cambiar color y título si cambió revisión
                            if (upd.revisada) {
                                // Cambió a revisada → Color verde
                                evento.setProp('backgroundColor', '#22c55e');
                                evento.setProp('borderColor', null);
                                evento.setProp('classNames', ['evento-revisado']);
                                evento.setProp('title', upd.codigo);

                                mostrarNotificacion(`✅ Planilla ${upd.codigo} marcada como revisada`, 'success');
                            } else {
                                // Cambió a sin revisar → Color gris
                                evento.setProp('backgroundColor', '#9e9e9e');
                                evento.setProp('borderColor', '#757575');
                                evento.setProp('classNames', ['evento-sin-revisar']);
                                evento.setProp('title', `⚠️ ${upd.codigo} (SIN REVISAR)`);
                            }
                        }

                        // 4. Si se completó la planilla
                        if (upd.completado && upd.estado === 'completada') {
                            cambios.push('PLANILLA COMPLETADA');

                            // Notificación especial
                            mostrarNotificacion(`🎉 Planilla ${upd.codigo} completada!`, 'success');

                            // Opcional: Cambiar color a completada
                            evento.setProp('backgroundColor', '#10b981');

                            // Opcional: Remover después de 3 segundos
                            setTimeout(() => {
                                evento.remove();
                                console.log(`🗑️ Evento de planilla ${upd.codigo} eliminado (completada)`);
                            }, 3000);
                        }

                        if (cambios.length > 0) {
                            console.log(`📝 Planilla ${upd.codigo}: ${cambios.join(', ')}`);
                        }
                    });
                });

                // Forzar re-render de los tooltips
                actualizarTooltips();
            }

            function actualizarBarraProgreso(eventoId, progreso) {
                // Buscar el elemento del DOM del evento por su data-event-id
                const eventoEl = document.querySelector(`[data-event-id="${eventoId}"]`);
                if (!eventoEl) {
                    console.log(`⚠️ No se encontró elemento con data-event-id="${eventoId}"`);
                    return;
                }

                // Buscar la barra de progreso interna (el div con clase bg-blue-500)
                const barra = eventoEl.querySelector('.bg-blue-500');
                if (barra) {
                    barra.style.width = progreso + '%';
                    console.log(`✅ Barra de progreso actualizada a ${progreso}%`);
                } else {
                    console.log(`⚠️ No se encontró barra de progreso en evento ${eventoId}`);
                }
            }

            function actualizarTooltips() {
                // Los tooltips se regeneran automáticamente en el próximo hover
                // No necesitamos hacer nada especial aquí
            }

            function mostrarNotificacion(mensaje, tipo = 'info') {
                // Colores según tipo
                const colores = {
                    'info': 'bg-blue-600',
                    'success': 'bg-green-600',
                    'warning': 'bg-yellow-600',
                    'error': 'bg-red-600'
                };

                const iconos = {
                    'info': '🔄',
                    'success': '✅',
                    'warning': '⚠️',
                    'error': '❌'
                };

                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 ${colores[tipo]} text-white px-4 py-3 rounded-lg shadow-lg z-[9999] transition-opacity duration-300`;
                toast.style.opacity = '0';
                toast.innerHTML = `
                    <div class="flex items-center gap-2">
                        <span class="text-xl">${iconos[tipo]}</span>
                        <span class="font-medium">${mensaje}</span>
                    </div>
                `;

                document.body.appendChild(toast);

                // Fade in
                setTimeout(() => toast.style.opacity = '1', 10);

                // Fade out y remover
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // Iniciar polling al cargar
            console.log('📅 Calendario de producción inicializado');
            iniciarPolling();

            // Debug: Mostrar estadísticas cada minuto
            setInterval(() => {
                console.log(`📊 Estadísticas de polling: ${actualizacionesRecibidas} actualizaciones recibidas`);
            }, 60000);

        });
    </script>
</x-app-layout>
