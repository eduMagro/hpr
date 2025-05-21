<x-app-layout>
    <x-slot name="title">Planificación por Máquina</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Planificación de Máquinas y Planillas') }}
        </h2>
    </x-slot>

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
                <h3 class="text-sm font-semibold mb-1 text-center">{{ $maquina->nombre }}</h3>
                <canvas id="grafico-maquina-{{ $maquina->id }}" width="280" height="200" class="mx-auto"></canvas>
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
            const maquinas = @json($maquinas->map(fn($m) => ['id' => $m->id, 'title' => $m->nombre]));
            const planillas = @json($planillasEventos);

            const calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                initialView: 'resourceTimelineDay',
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
                    right: 'resourceTimelineDay,resourceTimelineWeek'
                },
                views: {
                    resourceTimelineDay: {
                        slotMinTime: '00:00:00',
                        slotMaxTime: '23:59:00',
                    },
                    resourceTimelineWeek: {
                        slotDuration: {
                            days: 1
                        }
                    }
                },
                eventContent: function(arg) {
                    return {
                        html: `<div class="truncate w-full text-xs font-semibold text-white px-2 py-1 rounded"
                             style="background-color: ${arg.event.backgroundColor};"
                             title="${arg.event.title}">
                             ${arg.event.title}
                        </div>`
                    };
                },
                eventDrop: function(info) {
                    const planillaId = info.event.id.replace('planilla-', '');
                    const nuevaMaquinaId = info.newResource?.id ?? info.event.getResources()[0]?.id;
                    const nuevaFechaInicio = info.event.start.toISOString();

                    if (!confirm(
                            `¿Quieres mover la planilla ${planillaId} a la máquina ${nuevaMaquinaId}?`
                        )) {
                        info.revert();
                        return;
                    }

                    fetch('/planillas/reordenar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                id: planillaId,
                                nueva_maquina_id: nuevaMaquinaId,
                                nueva_fecha_inicio: nuevaFechaInicio
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                alert('Error al guardar los cambios');
                                info.revert();
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            alert('Error al comunicarse con el servidor');
                            info.revert();
                        });
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
                            Duración: ${props.duracion_min} min
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

    <style>
        .fc-tooltip {
            pointer-events: none;
            transition: opacity 0.1s ease-in-out;
        }
    </style>
</x-app-layout>
