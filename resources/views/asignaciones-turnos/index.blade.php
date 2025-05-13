<x-app-layout>
    <x-slot name="title">Asignaciones de Turnos</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Asignaciones de Turnos') }}
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <form method="GET" class="mb-4">
            <div class="flex items-center gap-4">
                <input type="text" name="trabajador" value="{{ request('trabajador') }}"
                    class="form-control form-control-sm w-64" placeholder="Buscar por nombre de trabajador">

                <button type="submit" class="btn btn-sm btn-info px-2">
                    <i class="fas fa-search"></i>
                </button>

                <a href="{{ route('asignaciones-turnos.index') }}" class="btn btn-sm btn-warning px-2">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>

        @if (request('trabajador') && $diasTrabajados)
            <div class="mb-4 text-sm text-gray-800 bg-gray-50 p-4 rounded shadow border">
                <strong>Total de días trabajados:</strong> {{ $diasTrabajados }}<br>
                <span class="text-green-600">✅ Puntual:</span> {{ $diasPuntuales }}<br>
                <span class="text-red-600">❌ Impuntual:</span> {{ $diasImpuntuales }}<br>
                <span class="text-yellow-600">⚠️ No Ficha:</span> {{ $diasSinFichaje }}

            </div>
        @endif

        <!-- Tabla de asignaciones -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg mt-4">

            <table class="w-full border border-gray-300 rounded-lg text-xs uppercase text-center">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="p-2 border">ID Empleado</th>
                        <th class="p-2 border">Empleado</th>
                        <th class="p-2 border">Fecha</th>
                        <th class="p-2 border">Turno</th>
                        <th class="p-2 border">Máquina</th>
                        <th class="p-2 border">Entrada</th>
                        <th class="p-2 border">Salida</th>
                        <th class="p-2 border">Resumen</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse ($asignaciones as $asignacion)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                            <td class="px-2 py-2 border">{{ $asignacion->user->id }}</td>
                            <td class="px-2 py-2 border">{{ $asignacion->user->name ?? '—' }}</td>
                            <td class="px-2 py-2 border">
                                {{ \Carbon\Carbon::parse($asignacion->fecha)->format('d/m/Y') }}
                            </td>
                            <td class="px-2 py-2 border">{{ $asignacion->turno->nombre ?? '—' }}</td>
                            <td class="px-2 py-2 border">{{ $asignacion->maquina->nombre ?? '—' }}</td>
                            {{-- <td class="px-2 py-2 border">{{ $asignacion->entrada ?? '—' }}</td>
                            <td class="px-2 py-2 border">{{ $asignacion->salida ?? '—' }}</td> --}}
                            @php
                                $esperadaEntrada = $asignacion->turno->hora_entrada ?? null;
                                $esperadaSalida = $asignacion->turno->hora_salida ?? null;
                                $realEntrada = $asignacion->entrada;
                                $realSalida = $asignacion->salida;

                                $puntual = '—';

                                if ($esperadaEntrada && $realEntrada) {
                                    $puntual = \Carbon\Carbon::parse($realEntrada)->lte(
                                        \Carbon\Carbon::parse($esperadaEntrada),
                                    )
                                        ? '✅ Puntual'
                                        : '❌ Tarde';
                                } elseif ($esperadaEntrada && !$realEntrada) {
                                    $puntual = '⚠️ No fichó';
                                }
                            @endphp

                            <td class="px-2 py-2 border">
                                {{ $realEntrada ?? '—' }}
                                <div class="text-xs text-gray-500">({{ $esperadaEntrada ?? '—' }})</div>
                            </td>
                            <td class="px-2 py-2 border">
                                {{ $realSalida ?? '—' }}
                                <div class="text-xs text-gray-500">({{ $esperadaSalida ?? '—' }})</div>
                            </td>
                            <td class="px-2 py-2 border text-xs font-bold">
                                {!! $puntual !!}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-gray-500">No hay asignaciones disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>


        </div>
        <div class="mt-4 flex justify-center">
            {{ $asignaciones->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>
</x-app-layout>
