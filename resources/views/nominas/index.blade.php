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
    <div class="flex items-center space-x-4 mt-10 ml-10">
        <form id="form-generar-nominas" method="POST" action="{{ route('generar.nominas') }}">
            @csrf

            <div class="bg-white shadow-lg rounded-lg p-6 max-w-4xl mx-auto mt-10 space-y-6 border border-gray-200">

                {{-- Título --}}
                <h2 class="text-xl font-semibold text-gray-800">
                    Generación de Nóminas
                </h2>

                {{-- Selección de mes --}}
                <div>
                    <label for="fecha_nominas" class="block text-sm font-medium text-gray-700">
                        Mes y Año
                    </label>
                    <input type="month" name="fecha" id="fecha_nominas"
                        class="mt-1 border border-gray-300 rounded px-3 py-2 w-48 focus:ring focus:ring-blue-200 focus:outline-none" />
                </div>

                {{-- Sección desplegable de incentivos --}}
                <div x-data="{ abierto: false }" class="border border-gray-300 rounded-md p-4 bg-gray-50">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">Incentivos por productividad</h3>
                        <button type="button" @click="abierto = !abierto"
                            class="text-sm text-blue-600 hover:underline focus:outline-none">
                            <span x-show="!abierto">Mostrar lista</span>
                            <span x-show="abierto">Ocultar lista</span>
                        </button>
                    </div>

                    <div x-show="abierto" x-transition>
                        <label class="inline-flex items-center font-medium mb-2">
                            <input type="checkbox" class="mr-2" id="check-all">
                            Seleccionar todos
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mt-2">
                            @foreach ($trabajadores as $trabajador)
                                <label class="inline-flex items-center text-sm text-gray-700">
                                    <input type="checkbox" name="incentivados[]" value="{{ $trabajador->id }}"
                                        class="mr-2 checkbox-trabajador">
                                    {{ $trabajador->name }} ({{ $trabajador->categoria->nombre ?? 'Sin categoría' }})
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Botón de acción --}}
                <div>
                    <button type="submit"
                        class="bg-blue-600 text-white font-semibold px-4 py-2 rounded hover:bg-blue-700 transition">
                        Generar nóminas del mes
                    </button>
                </div>
            </div>
        </form>

        {{-- Input de fecha --}}
        {{-- <input type="month" id="fecha_nominas" class="border border-gray-300 rounded px-3 py-2" />

        {{-- Botón con confirmación 
        <button x-data
            @click="
                const fecha = document.getElementById('fecha_nominas').value;
                if (!fecha) {
                    Swal.fire('Falta la fecha', 'Selecciona el mes y el año para generar las nóminas.', 'warning');
                    return;
                }
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: `Esto generará las nóminas para ${fecha}.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, generar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '{{ route('generar.nominas') }}' + `?fecha=${fecha}`;
                    }
                })
            "
            class="btn btn-primary">
            Generar nóminas del mes
        </button> --}}
    </div>

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
    <div class="overflow-x-auto w-full">
        <table class="min-w-full text-sm text-left text-gray-700 border border-gray-300 rounded-lg overflow-hidden">
            <thead class="bg-gray-800 text-white text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-2">Empleado</th>
                    <th class="px-4 py-2">Categoría</th>
                    <th class="px-4 py-2">Salario Base</th>
                    {{-- <th class="px-4 py-2">Plus Actividad</th>
                    <th class="px-4 py-2">Plus Asistencia</th>
                    <th class="px-4 py-2">Plus Transporte</th>
                    <th class="px-4 py-2">Plus Dieta</th>
                    <th class="px-4 py-2">Plus Turnicidad</th> --}}
                    <th class="px-4 py-2">Plus Productividad</th>
                    <th class="px-4 py-2">Prorrateo</th>
                    <th class="px-4 py-2">Horas Extra</th>
                    <th class="px-4 py-2">Valor Hora Extra</th>
                    <th class="px-4 py-2">Devengado</th>
                    <th class="px-4 py-2">Deducciones S.S.</th>
                    <th class="px-4 py-2">IRPF %</th>
                    <th class="px-4 py-2">IRPF</th>
                    <th class="px-4 py-2">Líquido</th>
                    <th class="px-4 py-2">Coste Empresa</th>
                    <th class="px-4 py-2">Fecha</th>
                    <th class="px-4 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($nominas as $nomina)
                    <tr class="border-t border-gray-200 hover:bg-gray-100">
                        <td class="px-4 py-2">{{ $nomina->empleado->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $nomina->categoria->nombre ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ number_format($nomina->salario_base, 2, ',', '.') }} €</td>
                        {{-- <td class="px-4 py-2">{{ number_format($nomina->plus_actividad, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2">{{ number_format($nomina->plus_asistencia, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2">{{ number_format($nomina->plus_transporte, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2">{{ number_format($nomina->plus_dieta, 2, ',', '.') }} €</td> 
                        <td class="px-4 py-2">{{ number_format($nomina->plus_turnicidad, 2, ',', '.') }} €</td> --}}
                        <td class="px-4 py-2">{{ number_format($nomina->plus_productividad, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2">{{ number_format($nomina->prorrateo, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2">{{ $nomina->horas_extra ?? '-' }}</td>
                        <td class="px-4 py-2">{{ number_format($nomina->valor_hora_extra, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2">{{ number_format($nomina->total_devengado, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2">{{ number_format($nomina->total_deducciones_ss, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2">{{ number_format($nomina->irpf_porcentaje ?? 0, 2, ',', '.') }} %</td>
                        <td class="px-4 py-2">{{ number_format($nomina->irpf_mensual, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2 bg-green-100 text-green-800 font-semibold">
                            {{ number_format($nomina->liquido, 2, ',', '.') }} €
                        </td>
                        <td class="px-4 py-2">{{ number_format($nomina->coste_empresa, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($nomina->fecha)->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('nominas.show', $nomina->id) }}" target="_blank"
                                rel="noopener noreferrer"
                                class="inline-block px-3 py-1 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded">
                                Ver Nómina
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="20" class="px-4 py-4 text-center text-gray-500">No hay nóminas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        document.getElementById('check-all')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.checkbox-trabajador');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>


</x-app-layout>
