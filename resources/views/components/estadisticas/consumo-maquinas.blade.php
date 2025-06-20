@props([
    'totales' => [],
    'series' => [],
    'desde' => null,
    'hasta' => null,
    'detalle' => [],
])
{{-- Filtro por fechas dentro del componente --}}
<form method="GET" action="{{ route('estadisticas.index') }}" class="mb-4 flex flex-wrap items-end gap-4 text-sm">

    <div>
        <label for="desde" class="block text-xs font-medium text-gray-700">Desde</label>
        <input type="date" name="desde" id="desde" value="{{ $desde }}" class="border rounded px-2 py-1">
    </div>

    <div>
        <label for="hasta" class="block text-xs font-medium text-gray-700">Hasta</label>
        <input type="date" name="hasta" id="hasta" value="{{ $hasta }}"
            class="border rounded px-2 py-1">
    </div>

    <input type="hidden" name="panel" value="consumo-maquinas">
    <label class="text-gray-700">Agrupar por:</label>
    <select name="modo" class="border border-gray-300 rounded px-2 py-1 text-xs">
        <option value="dia" {{ request('modo', 'dia') === 'dia' ? 'selected' : '' }}>Día</option>
        <option value="mes" {{ request('modo') === 'mes' ? 'selected' : '' }}>Mes</option>
        <option value="anio" {{ request('modo') === 'anio' ? 'selected' : '' }}>Año</option>
        <option value="origen"{{ request('modo') === 'origen' ? 'selected' : '' }}>Todo (origen)</option>
    </select>
    <div class="flex justify-center gap-2 items-center h-full">
        <!-- Botón buscar -->
        <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
            title="Buscar">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-4.35-4.35m1.3-5.4a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>

        <!-- Botón reset -->
        <a href="{{ route('estadisticas.index', ['panel' => 'consumo-maquinas']) }}"
            class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
            title="Restablecer filtros">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
            </svg>
        </a>
    </div>
</form>


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
    @if (collect($detalle)->isNotEmpty())
        <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-1 rounded mt-4 mb-1">
            Kg consumidos por máquina, tipo, Ø y longitud
        </h4>

        <div class="overflow-x-auto mb-4">
            <table class="w-full border border-gray-300 text-xs">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="px-1 py-1 border">Máquina</th>
                        <th class="px-1 py-1 border">Tipo</th>
                        <th class="px-1 py-1 border">Ø mm</th>
                        <th class="px-1 py-1 border">Longitud mm</th>
                        <th class="px-1 py-1 border">Kg</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @foreach ($detalle as $row)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50">
                            <td class="px-1 py-1 border text-center">{{ $row->maquina ?? '—' }}</td>
                            <td class="px-1 py-1 border text-center capitalize">{{ $row->tipo }}</td>
                            <td class="px-1 py-1 border text-center">{{ $row->diametro }}</td>
                            <td class="px-1 py-1 border text-center">{{ $row->longitud ?? '—' }}</td>
                            <td class="px-1 py-1 border text-center">{{ number_format($row->kg, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

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
