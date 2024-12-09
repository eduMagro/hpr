<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Estadisticas') }}
        </h2>
    </x-slot>

    <!-- Mostrar los errores y mensajes de éxito -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Contenedor de la gráfica -->
    <div class="container my-5">
        <h3>Gráfica de Productos por Estado</h3>

        <!-- Canvas para Chart.js -->
        <canvas id="productosEstadoChart" width="400" height="200"></canvas>
    </div>

    <!-- Incluir el JS de Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Obtener los datos pasados desde el controlador
        var productosPorEstado = @json($productosPorEstado);

        // Extraer etiquetas y valores para la gráfica
        var estados = productosPorEstado.map(function(item) {
            return item.estado;
        });

        var totales = productosPorEstado.map(function(item) {
            return item.total;
        });

        // Crear la gráfica
        var ctx = document.getElementById('productosEstadoChart').getContext('2d');
        var productosEstadoChart = new Chart(ctx, {
            type: 'bar',  // Tipo de gráfica: barra
            data: {
                labels: estados,  // Etiquetas (los estados de los productos)
                datasets: [{
                    label: 'Cantidad de Productos',
                    data: totales,  // Los valores (la cantidad de productos por estado)
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true  // Asegura que la escala Y comience desde 0
                    }
                }
            }
        });
    </script>
</x-app-layout>
