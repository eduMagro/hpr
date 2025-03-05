<!-- Reporte de Pesos por Usuario (Planillero) -->
<div class="bg-white shadow-lg rounded-lg mb-6">
    <div class="bg-blue-600 text-white text-center p-4 rounded-t-lg">
        <h3 class="text-lg font-semibold">Reporte de Pesos por Usuario (Planillero)</h3>
    </div>

    <div class="p-4">
        <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-2 rounded-md">
            Peso Total Importado por Usuario
        </h4>
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="px-4 py-3 border text-center">Usuario ID</th>
                        <th class="px-4 py-3 border text-center">Peso Total Importado (kg)</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse ($pesoPorPlanillero as $planillero)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                            <td class="px-4 py-3 text-center border">{{ $planillero->name }}</td>
                            <td class="px-4 py-3 text-center border">{{ number_format($planillero->peso_importado, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-red-600 px-4 py-3 text-center">
                                No hay datos disponibles
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center text-gray-600 bg-gray-100 py-2 rounded-b-lg">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>

    <div class="p-4">
        <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-2 rounded-md mt-6">
            Gráfica de Peso Total Importado por Usuario
        </h4>
        <div class="overflow-x-auto">
            <canvas id="pesoPorUsuarioChart" class="w-full h-64"></canvas>
        </div>
    </div>
</div>
<script>
    const ctx = document.getElementById('pesoPorUsuarioChart').getContext('2d');

    // Asegurarse de que los datos están correctamente pasados
    const pesoPorUsuarioData = @json($pesoPorPlanillero);

    // Verificar los datos en la consola
    console.log(pesoPorUsuarioData);

    if (!pesoPorUsuarioData || pesoPorUsuarioData.length === 0) {
        console.error('No hay datos para la gráfica');
    } else {
        // Asegurarnos de que las fechas están en el formato correcto
        // Convertir las fechas `created_at` a formato ISO 8601 (si es necesario)
        const fechas = [...new Set(pesoPorUsuarioData.map(planillero => new Date(planillero.created_at)
    .toISOString()))];
        const usuarios = [...new Set(pesoPorUsuarioData.map(planillero => planillero.name))];

        // Inicializar los datasets para cada usuario
        const datasets = usuarios.map(usuario => {
            return {
                label: usuario,
                data: fechas.map(fecha => {
                    const entry = pesoPorUsuarioData.find(planillero => planillero.name === usuario &&
                        new Date(planillero.created_at).toISOString() === fecha);
                    return entry ? entry.peso_importado : 0;
                }),
                borderColor: '#' + Math.floor(Math.random() * 16777215).toString(
                16), // Color aleatorio para cada línea
                backgroundColor: 'transparent',
                borderWidth: 2,
                fill: false
            };
        });

        // Configurar la gráfica
        new Chart(ctx, {
            type: 'line', // Usar tipo "line"
            data: {
                labels: fechas, // Etiquetas de las fechas
                datasets: datasets
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time', // Especificar que el eje X es de tipo tiempo
                        time: {
                            unit: 'day', // Ajusta el tipo de unidad de tiempo (puede ser 'day', 'month', etc.)
                            tooltipFormat: 'll', // Formato para la visualización en el tooltip
                            displayFormats: {
                                day: 'MMM D, YYYY', // Formato de las fechas en el gráfico
                            }
                        },
                        title: {
                            display: true,
                            text: 'Fecha'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Peso Total Importado (kg)'
                        }
                    }
                }
            }
        });
    }
</script>
