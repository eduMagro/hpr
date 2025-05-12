<!-- Reporte de Pesos por Usuario (Planillero) -->
<div class="bg-white shadow rounded mb-4 text-sm">
    <div class="bg-blue-600 text-white text-center py-2 rounded-t">
        <h3 class="font-semibold text-base">Reporte de Pesos por Planillero</h3>
    </div>

    <div class="px-3 py-2">
        <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-1 rounded mb-2">
            Peso Total Importado por Planillero
        </h4>
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-300 text-xs">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="px-2 py-1 border text-center">Trabajador</th>
                        <th class="px-2 py-1 border text-center">Peso Total Importado (kg)</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse ($pesoPorPlanillero as $planillero)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-100">
                            <td class="px-2 py-1 text-center border">{{ $planillero->name }}</td>
                            <td class="px-2 py-1 text-center border">
                                {{ number_format($planillero->peso_importado, 2) }}
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

    <div class="text-center text-gray-500 bg-gray-100 py-1 rounded-b text-xs">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>

    <div class="px-3 py-2">
        <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-1 rounded mt-2 mb-1">
            Gráfica Mensual Peso/Planillero
        </h4>
        <div class="relative w-full overflow-x-auto">
            <canvas id="pesoPorUsuarioChart" class="w-full h-full" style="max-height: 300px;"></canvas>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('pesoPorUsuarioChart').getContext('2d');

        // Datos importados desde Laravel
        const pesoPorUsuarioData = @json($pesoPorPlanilleroPorDia);

        console.log("Datos recibidos:", pesoPorUsuarioData);

        if (!pesoPorUsuarioData || pesoPorUsuarioData.length === 0) {
            console.error('No hay datos para la gráfica');
            return;
        }

        // Obtener la fecha del primer y último día del mes actual
        const hoy = new Date();
        const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);

        // Crear una lista con todas las fechas del mes
        let fechas = [];
        for (let d = new Date(primerDiaMes); d <= ultimoDiaMes; d.setDate(d.getDate() + 1)) {
            fechas.push(d.toISOString().split('T')[0]); // YYYY-MM-DD
        }

        // Agrupar los datos por usuario y fecha
        let datosUsuarios = {};
        let usuariosUnicos = new Set();

        pesoPorUsuarioData.forEach(planillero => {
            if (!planillero.fecha) {
                console.warn("Registro con fecha inválida:", planillero);
                return;
            }

            const fecha = planillero.fecha;
            const usuario = planillero.name;
            usuariosUnicos.add(usuario); // Guardar usuarios únicos

            if (!datosUsuarios[usuario]) {
                datosUsuarios[usuario] = {};
            }
            if (!datosUsuarios[usuario][fecha]) {
                datosUsuarios[usuario][fecha] = 0;
            }
            datosUsuarios[usuario][fecha] += parseFloat(planillero.peso_importado);
        });

        console.log("Datos agrupados por usuario:", datosUsuarios);

        if (Object.keys(datosUsuarios).length === 0) {
            console.warn("No hay datos procesables para la gráfica.");
            return;
        }

        // Función para generar colores únicos basados en un usuario
        function getColorForUser(username) {
            const colors = [
                '#FF5733', '#33FF57', '#3357FF', '#F39C12', '#9B59B6',
                '#1ABC9C', '#E74C3C', '#D35400', '#C0392B', '#7F8C8D'
            ];
            let hash = 0;
            for (let i = 0; i < username.length; i++) {
                hash = username.charCodeAt(i) + ((hash << 5) - hash);
            }
            const index = Math.abs(hash % colors.length);
            return colors[index];
        }

        // Preparar los datasets para Chart.js con acumulación de peso
        const datasets = Array.from(usuariosUnicos).map(usuario => {
            let acumulado = 0;
            let dataValues = fechas.map(fecha => {
                acumulado += (datosUsuarios[usuario][fecha] || 0);
                return acumulado; // Se acumula el peso día a día
            });

            console.log(`Usuario: ${usuario}, Datos acumulados:`, dataValues);

            return {
                label: usuario,
                data: dataValues,
                borderColor: getColorForUser(usuario),
                backgroundColor: 'transparent',
                borderWidth: 2,
                fill: false,
                tension: 0.3 // Hace la línea más suave
            };
        });

        console.log("Datasets para Chart.js:", datasets);

        // Configurar la gráfica
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Permite que la gráfica se adapte a diferentes pantallas
                scales: {
                    x: {
                        type: 'category',
                        title: {
                            display: true,
                            text: 'Fecha'
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 50000,
                        title: {
                            display: true,
                            text: 'Peso Acumulado Importado (kg)'
                        }
                    }
                }
            }
        });
    });
</script>
