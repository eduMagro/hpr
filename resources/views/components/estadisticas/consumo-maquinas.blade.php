<div class="bg-white shadow rounded mb-6 p-4">
    <h3 class="font-semibold text-base mb-4">
        Consumo diario de materia prima por máquina (kg)
    </h3>
    <canvas id="consumoMaquinasChart"></canvas>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('consumoMaquinasChart');
        if (!ctx) return;
        /* 👉 Datos que llegan desde el controlador */
        const labels = @json($labels);
        const datasets = @json($datasets);

        /* ✅ Revisar en consola del navegador (F12) */
        console.log('[ConsumoMáquinas] Labels →', labels);
        console.log('[ConsumoMáquinas] Datasets →', datasets);
        new Chart(ctx, {
            type: 'bar', // cámbialo a 'line' si lo prefieres
            data: {
                labels: @json($labels),
                datasets: @json($datasets),
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kg consumidos'
                        },
                    },
                },
            },
        });
    });
</script>
