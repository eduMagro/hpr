<x-app-layout>
    <x-slot name="title">Planificación por Máquina</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Planificación de Máquinas y Planillas') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="bg-white shadow rounded-lg p-4">
            <div id="calendario" class="h-[80vh] w-full"></div>
        </div>
    </div>
    <div class="mt-10 bg-white shadow rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-4">Carga de Trabajo por Máquina (kg)</h3>
        <canvas id="graficoCargaKg"></canvas>
    </div>
    {{-- FullCalendar --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const maquinas = @json(
                $maquinas->map(fn($m) => [
                        'id' => $m->id,
                        'title' => $m->nombre,
                    ]));
            const planillas = @json($planillasEventos);
            console.log(planillas);
            console.log(maquinas);
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
                    const props = arg.event.extendedProps;
                    let html = `
                        <div class="px-2 py-1 text-xs font-semibold text-white rounded ${arg.event.backgroundColor ? '' : 'bg-blue-600'}" 
                             style="background-color: ${arg.event.backgroundColor};">
                            <span>${arg.event.title}</span>
                            <br><span class="text-[10px] opacity-80">Obra: ${props.obra}</span>
                        </div>`;
                    return {
                        html
                    };
                }
            });

            calendar.render();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const datosKg = @json($cargaPorMaquinaKg);
        const nombresMaquinas = @json($maquinas->pluck('nombre', 'id'));

        const etiquetas = Object.keys(datosKg).map(id => nombresMaquinas[id] ?? `Máquina ${id}`);
        const valores = Object.values(datosKg);

        const ctxKg = document.getElementById('graficoCargaKg').getContext('2d');
        new Chart(ctxKg, {
            type: 'bar',
            data: {
                labels: etiquetas,
                datasets: [{
                    label: 'Kg pendientes',
                    data: valores,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
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
    </script>
</x-app-layout>
