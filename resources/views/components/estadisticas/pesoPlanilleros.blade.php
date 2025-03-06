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

        pesoPorUsuarioData.forEach(planillero => {
            if (!planillero.fecha) {
                console.warn("Registro con fecha inválida:", planillero);
                return;
            }

            const fecha = planillero.fecha;
            const usuario = planillero.name;

            if (!datosUsuarios[usuario]) {
                datosUsuarios[usuario] = {};
            }
            if (!datosUsuarios[usuario][fecha]) {
                datosUsuarios[usuario][fecha] = 0;
            }
            datosUsuarios[usuario][fecha] += planillero.peso_importado;
        });

        console.log("Datos agrupados:", datosUsuarios);

        if (Object.keys(datosUsuarios).length === 0) {
            console.warn("No hay datos procesables para la gráfica.");
            return;
        }

        // Función para generar colores con más contraste
        function getRandomColor() {
            const letters = '0123456789ABCDEF';
            let color = '#';
            for (let i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 12)]; // Evita colores demasiado claros
            }
            return color;
        }

        // Preparar los datasets para Chart.js
        const datasets = Object.keys(datosUsuarios).map(usuario => {
            let dataValues = fechas.map(fecha => datosUsuarios[usuario][fecha] || 0);

            console.log(`Usuario: ${usuario}, Datos:`, dataValues);

            return {
                label: usuario,
                data: dataValues,
                borderColor: getRandomColor(),
                backgroundColor: 'transparent',
                borderWidth: 2,
                fill: false,
                tension: 0.3 // Hace la línea más suave
            };
        });

        console.log("Datasets:", datasets);

        // Configurar la gráfica
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: datasets
            },
            options: {
                responsive: true,
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
                            text: 'Peso Total Importado (kg)'
                        }
                    }
                }
            }
        });
    });
</script>
