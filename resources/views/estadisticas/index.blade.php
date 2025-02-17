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

    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h3>Reporte de Pesos por Planilla (Pendientes)</h3>
            </div>
            <div class="card-body">

                <!-- Tabla 1: Peso Total por Diámetro -->
                <div class="mb-4">
                    <h4 class="text-center bg-secondary text-white p-2 rounded">Peso Total por Diámetro</h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th>Diámetro (mm)</th>
                                    <th>Peso Total (kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pesoTotalPorDiametro as $fila)
                                    <tr class="text-center">
                                        <td>{{ number_format($fila->diametro, 2) }}</td>
                                        <td>{{ number_format($fila->peso_total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-danger">No hay datos disponibles</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabla 2: Peso por Planilla y Diámetro -->
                <div class="mb-4">
                    <h4 class="text-center bg-secondary text-white p-2 rounded">Peso por Planilla y Diámetro</h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th>Diámetro (mm)</th>
                                    <th>Planilla</th>
                                    <th>Peso por Planilla (kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($datosPorPlanilla as $fila)
                                    <tr class="text-center">
                                        <td>{{ number_format($fila->diametro, 2) }}</td>
                                        <td>{{ $fila->planilla_id }}</td>
                                        <td>{{ number_format($fila->peso_por_planilla, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-danger">No hay datos disponibles</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <div class="card-footer text-center text-muted">
                Generado el {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

</x-app-layout>
