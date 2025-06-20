@props([
    'totales' => [],
    'series' => [],
])

<div class="bg-white shadow rounded mb-4 text-sm">
    {{-- Cabecera azul --}}
    <div class="bg-blue-600 text-white text-center py-2 rounded-t">
        <h3 class="font-semibold text-base">
            Consumo de materia prima por máquina
        </h3>
    </div>

    {{-- Tabla de totales por máquina --}}
    <div class="px-3 py-2">
        <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-1 rounded mb-2">
            Peso Total Consumido por Máquina
        </h4>

        <div class="overflow-x-auto">
            <table class="w-full border border-gray-300 text-xs">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="px-2 py-1 border text-center">Máquina</th>
                        <th class="px-2 py-1 border text-center">Kg Consumidos</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse ($totales as $row)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-100">
                            <td class="px-2 py-1 text-center border">{{ $row['maquina'] }}</td>
                            <td class="px-2 py-1 text-center border">
                                {{ number_format($row['kg_totales'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-red-600 px-2 py-1 text-center">
                                No hay datos disponibles
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pie con fecha de generación --}}
    <div class="text-center text-gray-500 bg-gray-100 py-1 rounded-b text-xs">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>

    {{-- Gráfica --}}
    <div class="px-3 py-2">
        <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-1 rounded mt-2 mb-1">
            Gráfica Diaria Kg/Máquina
        </h4>

        <div class="relative w-full overflow-x-auto">
            <canvas id="consumoMaquinasChart" class="w-full h-full" style="max-height: 300px;"></canvas>
        </div>
    </div>
</div>

{{-- --- JS para la gráfica --- --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('consumoMaquinasChart').getContext('2d');

        /* Datos importados desde Laravel */
        const etiquetas = @json($series['labels'] ?? []);
        const datasets = @json($series['datasets'] ?? []);

        console.log('[ConsumoMáquinas] Labels →', etiquetas);
        console.log('[ConsumoMáquinas] Datasets →', datasets);

        if (!etiquetas.length || !datasets.length) {
            console.warn('No hay data para la gráfica Consumo/Máquina');
            return;
        }

        new Chart(ctx, {
            type: 'bar', // cámbialo a 'line' si lo prefieres
            data: {
                labels: etiquetas,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    x: {
                        type: 'category',
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kg consumidos'
                        }
                    }
                }
            }
        });
    });
</script>
