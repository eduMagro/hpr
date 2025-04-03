<x-app-layout>
    <x-slot name="title">Cálculo Nómina</x-slot>

    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('empresas.index') }}" class="text-blue-600">
                {{ __('Empresa Información') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Nómina') }}
        </h2>
    </x-slot>
    <button x-data
        @click="Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esto generará las nóminas del mes para todos los trabajadores.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, generar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '{{ route('generar.nominas') }}';
        }
    })"
        class="btn btn-primary mt-10 ml-10">
        Generar nóminas del mes
    </button>
    <form method="POST" action="{{ route('nominas.borrarTodas') }}"
        onsubmit="return confirm('¿Estás seguro de que quieres borrar todas las nóminas?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger m-10">Borrar todas las nóminas</button>
    </form>


    <style>
        table thead th {
            position: sticky;
            top: 0;
            background-color: #343a40;
            /* color del encabezado (equivalente a .table-dark) */
            color: white;
            z-index: 10;
        }
    </style>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Empleado</th>
                <th>Categoría</th>
                <th>Salario Base</th>
                <th>Plus Actividad</th>
                <th>Prorrateo</th>
                <th>Plus Varios</th>
                <th>Horas Extra</th>
                <th>Valor Hora Extra</th>
                <th>Devengado</th>
                <th>Deducciones S.S.</th>
                <th>IRPF</th>
                <th>Líquido</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($nominas as $nomina)
                <tr>
                    <td>{{ $nomina->empleado->name ?? 'N/A' }}</td>
                    <td>{{ $nomina->categoria->nombre ?? 'N/A' }}</td>
                    <td>{{ number_format($nomina->salario_base, 2) }} €</td>
                    <td>{{ number_format($nomina->plus_actividad, 2) }} €</td>
                    <td>{{ number_format($nomina->prorrateo, 2) }} €</td>
                    <td>{{ number_format($nomina->plus_varios, 2) }} €</td>
                    <td>{{ $nomina->horas_extra }}</td>
                    <td>{{ number_format($nomina->valor_hora_extra, 2) }} €</td>
                    <td>{{ number_format($nomina->total_devengado, 2) }} €</td>
                    <td>{{ number_format($nomina->total_deducciones_ss, 2) }} €</td>
                    <td>{{ number_format($nomina->irpf_mensual, 2) }} €</td>
                    <td>{{ number_format($nomina->liquido, 2) }} €</td>
                    <td>{{ \Carbon\Carbon::parse($nomina->fecha)->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('nominas.show', $nomina->id) }}" class="btn btn-sm btn-primary">
                            Ver Nómina
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center">No hay nóminas registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</x-app-layout>
