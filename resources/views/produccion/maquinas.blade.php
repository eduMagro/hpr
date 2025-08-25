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
            </div> {{-- <- CIERRA aquí la tarjeta --}}
        @endforeach
    </div>


    <style>
        canvas {
            height: 180px !important;
            max-height: 180px !important;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const maquinas = @json($resources);
            const planillas = @json($planillasEventos);
            const cargaTurnoResumen = @json($cargaTurnoResumen);
            const planDetallado = @json($planDetallado);
            const realDetallado = @json($realDetallado);

            // ------- FullCalendar -------
            const calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                initialView: 'resourceTimelineFiveDay',
                views: {
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
                    resourceTimelineFiveDay: {
                        type: 'resourceTimeline',
                        duration: {
                            days: 15
                        }, // si quieres realmente 5 días, cambia a { days: 5 }
                        slotDuration: {
                            hours: 1
                        },
                        slotMinTime: '00:00:00',
                        slotMaxTime: '24:00:00',
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

                    // Calcular nueva posición según orden cronológico en la nueva máquina
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
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                id: planillaId,
                                maquina_id: maquinaDestinoId,
                                maquina_origen_id: maquinaOrigenId,
                                nueva_posicion: nuevaPosicion
                            })
                        });

                        const text = await res.text();
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (jsonError) {
                            throw new Error('❌ El servidor no devolvió JSON válido:\n\n' + text);
                        }

                        if (!res.ok || !data.success) throw new Error(data.message ||
                            '❌ Error al reordenar');

                        // Refrescar eventos
                        document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());
                        calendar.removeAllEvents();
                        const resEventos = await fetch('/planillas/eventos');
                        const nuevosEventos = await resEventos.json();
                        calendar.addEventSource(nuevosEventos);

                    } catch (e) {
                        console.error('Error en respuesta:', e);
                        Swal.fire({
                            title: 'Error',
                            html: `<pre style="white-space:pre-wrap;text-align:left;">${e.message}</pre>`,
                            icon: 'error'
                        });
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
                    Estado producción: ${props.estado}<br>
                    Fin programado: <span class="text-yellow-300">${props.fin_programado}</span><br>
                    Fecha estimada entrega: <span class="text-green-300">${props.fecha_entrega}</span>
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

            // ------- Charts (Planificado vs Real) -------
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

            // Crear charts iniciales
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

            // Texto inicial del rango
            const pintarTextoRango = () => {
                const turnoTxt = $turno?.value ?
                    $turno.options[$turno.selectedIndex].text :
                    'Todos';
                $rango.textContent =
                    `Mostrando datos desde ${$desde.value} hasta ${$hasta.value} · Turno: ${turnoTxt}`;
            };
            pintarTextoRango();

            // Función única para aplicar filtro (por botón o por cambios)
            const aplicarFiltro = (ev) => {
                ev?.preventDefault?.();

                const desde = parseDate($desde.value);
                const hasta = parseDate($hasta.value);
                if (!desde || !hasta) {
                    $rango.textContent = 'Selecciona un rango de fechas válido.';
                    return;
                }
                // incluir día completo
                hasta.setHours(23, 59, 59, 999);

                const turnoSel = ($turno?.value || '').toLowerCase(); // '', 'mañana','tarde','noche'
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

            // Click en botón
            document.getElementById('filtrarFechas').addEventListener('click', aplicarFiltro);

            // Reactividad instantánea (sin pulsar botón)
            ['change', 'input'].forEach(evt => {
                $desde.addEventListener(evt, aplicarFiltro);
                $hasta.addEventListener(evt, aplicarFiltro);
                $turno?.addEventListener(evt, aplicarFiltro);
            });
        }); // <-- cierre correcto de DOMContentLoaded
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
                    console.error('Error en respuesta:', e);
                    Swal.fire({
                        title: 'Error',
                        html: `<pre style="white-space:pre-wrap;text-align:left;">${e.message}</pre>`,
                        icon: 'error'
                    });
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
