<x-app-layout>
    <x-slot name="title">Planificación por Máquina</x-slot>
    <x-menu.planificacion />

    @if ($tablaPlanillas->isNotEmpty())
        <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
            <h3 class="px-6 py-3 font-semibold text-gray-800 bg-gray-50">
                Control de plazos de planillas
            </h3>

            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="px-4 py-2">Código</th>
                        <th class="px-4 py-2">Fin&nbsp;programado</th>
                        <th class="px-4 py-2">Entrega&nbsp;estimada</th>
                        <th class="px-4 py-2">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($tablaPlanillas as $fila)
                        <tr
                            class="{{ $fila['estado'] === '🟢 En tiempo' ? 'bg-green-50' : ($fila['estado'] === '🔴 Retraso' ? 'bg-red-50' : '') }}">
                            <td class="px-4 py-2 font-medium">
                                <a href="{{ route('planillas.show', $fila['planilla_id']) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $fila['codigo'] }}
                                </a>
                            </td>
                            <td class="px-4 py-2">{{ $fila['fin_programado'] }}</td>
                            <td class="px-4 py-2">{{ $fila['entrega_estimada'] }}</td>
                            <td class="px-4 py-2">
                                {{ $fila['estado'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

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

        <div class="bg-white shadow rounded-lg p-4">
            <div id="calendario" class="h-[80vh] w-full"></div>
        </div>
    </div>

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
        <button id="filtrarFechas"
            class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition text-sm">
            Aplicar Filtro
        </button>
        <p id="rango-aplicado" class="text-sm text-gray-600 mt-2 px-4"></p>
    </div>

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

                <canvas id="grafico-maquina-{{ $maquina->id }}" width="280" height="200"
                    class="mx-auto"></canvas>
            </div>
        @endforeach

    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const maquinas = @json($resources);

            const planillas = @json($planillasEventos);

            const calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                initialView: 'resourceTimelineFiveDay',
                /* 👇 Aquí definimos la vista de 5 días */
                views: {
                    // Vista de 1 día (la de siempre)
                    resourceTimelineDay: {
                        type: 'resourceTimeline',
                        duration: {
                            days: 1
                        },
                        slotMinTime: '00:00:00',
                        slotMaxTime: '24:00:00',
                        slotDuration: {
                            hours: 1
                        },
                        slotLabelFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        },
                        buttonText: '1 día'
                    },

                    // 👉 NUEVA vista de 5 días con horas
                    resourceTimelineFiveDay: {
                        type: 'resourceTimeline',
                        duration: {
                            days: 15
                        }, // muestra 5 días
                        slotDuration: {
                            hours: 1
                        }, // cada columna = 1 hora
                        slotMinTime: '00:00:00',
                        slotMaxTime: '24:00:00',

                        /* Etiquetamos la cabecera en dos filas:
                           - fila 1: “lun 24 jun”
                           - fila 2: “08:00”, “09:00”…                */
                        slotLabelFormat: [{
                                weekday: 'short',
                                day: 'numeric',
                                month: 'short'
                            },
                            {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            }
                        ],

                        buttonText: '5 días'
                    }
                },
                locale: 'es',
                timeZone: 'Europe/Madrid',
                initialDate: new Date(),
                resourceAreaHeaderContent: 'Máquinas',
                resources: maquinas,
                events: planillas,
                resourceAreaWidth: '220px',
                height: 'auto',
                editable: true,
                eventResizableFromStart: false,
                eventDurationEditable: false,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'resourceTimelineDay,resourceTimelineFiveDay'
                },
                eventContent: function(arg) {
                    const progreso = arg.event.extendedProps.progreso;
                    const color = '#3b82f6'; // siempre azul

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
                    } else {
                        return {
                            html: `<div class="truncate w-full text-xs font-semibold text-white px-2 py-1 rounded"
                     style="background-color: ${arg.event.backgroundColor};"
                     title="${arg.event.title}">
                     ${arg.event.title}
                </div>`
                        };
                    }
                },


                eventDrop: async function(info) {
                    const planillaId = info.event.id.replace('planilla-', '');
                    const codigoPlanilla = info.event.extendedProps.codigo ?? info.event.title;

                    const maquinaOrigenId = info.oldResource?.id ?? info.event.getResources()[0]?.id;
                    const maquinaDestinoId = info.newResource?.id ?? info.event.getResources()[0]?.id;
                    const maquinaId = info.newResource?.id ?? info.event.getResources()[0]
                        ?.id; // ⚠️ Aquí definimos máquina
                    /* 1️⃣  Solo permitimos mover dentro de la misma máquina */
                    if (maquinaOrigenId !== maquinaDestinoId) {
                        alert('Solo puedes reordenar dentro de la misma máquina.');
                        info.revert();
                        return;
                    }

                    /* 2️⃣  Preguntar confirmación */

                    if (!confirm(`¿Quieres reordenar la planilla ${codigoPlanilla}?`)) {

                        info.revert();
                        return;
                    }

                    /* 3️⃣  Calcular la nueva posición (1, 2, 3, …) dentro de la máquina */
                    const eventosOrdenados = calendar.getEvents()
                        .filter(ev => ev.getResources().some(r => r.id == maquinaDestinoId))
                        .sort((a, b) => a.start - b.start);

                    const nuevaPosicion = eventosOrdenados.findIndex(ev => ev.id === info.event.id) +
                        1; // +1 porque el índice arranca en 0

                    /* 4️⃣  Llamar al backend */
                    try {
                        const res = await fetch('/planillas/reordenar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                id: planillaId,
                                maquina_id: maquinaId, // 👈 nuevo
                                nueva_posicion: nuevaPosicion
                            })
                        });

                        if (!res.ok) {
                            // leer texto devuelto por la excepción (422, 500, …)
                            const errorMsg = await res.text();
                            throw new Error(errorMsg || 'Error desconocido');
                        }

                        const data = await res.json();
                        if (!data.success) throw new Error('El servidor informó de fallo.');

                    } catch (e) {
                        // Si algo falla, revertimos el drag & drop y mostramos mensaje
                        console.error(e);
                        alert(e.message || 'No se pudo reordenar la planilla.');
                        info.revert();
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
                            Estado: ${props.estado}<br>
                            Duración: ${props.duracion_horas} horas
                        </div>`;
                    tooltip.style.position = 'absolute';
                    tooltip.style.zIndex = 9999;
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

            calendar.render();
            window.calendar = calendar;

            const cargaMaquinaTurno = @json($cargaPorMaquinaTurno);
            const datosOriginales = @json($cargaPorMaquinaTurnoConFechas);
            console.log(datosOriginales);

            const charts = {};

            Object.entries(cargaMaquinaTurno).forEach(([maquinaId, turnos]) => {
                const ctx = document.getElementById(`grafico-maquina-${maquinaId}`).getContext('2d');
                const labels = ["Mañana", "Tarde", "Noche"];
                const esperado = labels.map(t => turnos[t.toLowerCase()].esperado);
                const fabricado = labels.map(t => turnos[t.toLowerCase()].fabricado);

                charts[maquinaId] = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                                label: 'Esperado (kg)',
                                data: esperado,
                                backgroundColor: 'rgba(255, 159, 64, 0.6)',
                                borderColor: 'rgba(255, 159, 64, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Fabricado (kg)',
                                data: fabricado,
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
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

            document.getElementById('rango-aplicado').textContent =
                `Mostrando datos desde ${document.getElementById('fechaInicio').value} hasta ${document.getElementById('fechaFin').value}`;

            document.getElementById('filtrarFechas').addEventListener('click', () => {
                const desde = new Date(document.getElementById('fechaInicio').value);
                const hasta = new Date(document.getElementById('fechaFin').value);

                document.getElementById('rango-aplicado').textContent =
                    `Mostrando datos desde ${document.getElementById('fechaInicio').value} hasta ${document.getElementById('fechaFin').value}`;

                Object.entries(datosOriginales).forEach(([maquinaId, turnos]) => {
                    const labels = ["Mañana", "Tarde", "Noche"];

                    const esperado = labels.map(t =>
                        (turnos[t.toLowerCase()] || []).filter(e => {
                            const fecha = new Date(e.fecha);
                            return fecha >= desde && fecha <= hasta;
                        }).reduce((suma, e) => suma + e.peso, 0)
                    );

                    const fabricado = labels.map(t =>
                        (turnos[t.toLowerCase()] || []).filter(e => {
                            const fecha = new Date(e.fecha);
                            return fecha >= desde && fecha <= hasta && (e.estado
                                ?.toLowerCase() === 'fabricado');
                        }).reduce((suma, e) => suma + e.peso, 0)
                    );

                    if (charts[maquinaId]) {
                        charts[maquinaId].data.datasets[0].data = esperado;
                        charts[maquinaId].data.datasets[1].data = fabricado;
                        charts[maquinaId].update();
                    }
                });
            });
        });
    </script>
    <script>
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
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .content
                        },
                        body: JSON.stringify({
                            estado: nuevoEstado
                        })
                    });

                    const text = await res.text();
                    if (!res.ok) throw new Error(text);

                    const data = JSON.parse(text);

                    if (data.success) {
                        console.log(`✅ Máquina ${maquinaId} actualizada a estado "${nuevoEstado}"`);

                        // 🔁 Actualizar el título del resource en FullCalendar
                        const calendar = window.calendar; // asegúrate que tu instancia esté global

                        const estado = data.estado;
                        const colores = {
                            activa: '🟢',
                            averiada: '🔴',
                            mantenimiento: '🛠️',
                            pausa: '⏸️'
                        };
                        const icono = colores[estado] ?? ' ';
                        const nombreMaquina = select.dataset.nombre ?? `Máquina ${maquinaId}`;
                        const nuevoTitulo = `${icono} ${nombreMaquina}`;

                        calendar.getResourceById(maquinaId)?.setProp('title', nuevoTitulo);
                    }

                } catch (e) {
                    console.error('Respuesta no válida:', e);
                    alert(`❌ Error: ${e.message}`);
                }
            });
        });
    </script>

    <style>
        .fc-tooltip {
            pointer-events: none;
            transition: opacity 0.1s ease-in-out;
        }
    </style>
</x-app-layout>
