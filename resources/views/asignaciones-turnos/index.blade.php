<x-app-layout>
    <x-slot name="title">Asignaciones de Turnos</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Asignaciones de Turnos') }}
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">

        @if (request('empleado') && $diasTrabajados + $diasSinFichaje > 0)
            <div class="mb-4 text-sm text-gray-800 bg-gray-50 p-4 rounded shadow border leading-relaxed space-y-1">
                <p class="font-bold text-base text-blue-800">
                    <span>
                        {{ $asignaciones->first()->user->name ?? '—' }}
                    </span>
                </p>
                <p><strong>🧾 Total de días con asignación:</strong> {{ $diasTrabajados + $diasSinFichaje }}</p>
                <p><span class="text-blue-600">📅 Días trabajados:</span> {{ $diasTrabajados }}</p>
                <p><span class="text-green-600">✅ Puntual (entra y sale bien):</span> {{ $diasPuntuales }}</p>
                <p><span class="text-red-600">❌ Llega tarde:</span> {{ $diasImpuntuales }}</p>
                <p><span class="text-purple-600">🔄 Se va antes:</span> {{ $diasSeVaAntes }}</p>
                <p><span class="text-yellow-600">⚠️ No fichó:</span> {{ $diasSinFichaje }}</p>
            </div>
        @endif


        <!-- Tabla de asignaciones -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg mt-4">

            <table class="w-full border border-gray-300 rounded-lg text-xs uppercase text-center">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="p-2 border">ID Empleado</th>
                        <th class="p-2 border">
                            <a
                                href="{{ route('asignaciones-turnos.index', array_merge(request()->all(), ['sort' => 'user_id', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                Empleado
                            </a>
                        </th>
                        <th class="p-2 border">
                            <a
                                href="{{ route('asignaciones-turnos.index', array_merge(request()->all(), ['sort' => 'fecha', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                Fecha
                            </a>
                        </th>
                        <th class="p-2 border">
                            <a
                                href="{{ route('asignaciones-turnos.index', array_merge(request()->all(), ['sort' => 'fecha', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                Lugar
                            </a>
                        </th>
                        <th class="p-2 border">
                            <a
                                href="{{ route('asignaciones-turnos.index', array_merge(request()->all(), ['sort' => 'turno_id', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                Turno
                            </a>
                        </th>
                        <th class="p-2 border">
                            <a
                                href="{{ route('asignaciones-turnos.index', array_merge(request()->all(), ['sort' => 'maquina_id', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                Máquina
                            </a>
                        </th>

                        <th class="p-2 border">Entrada</th>
                        <th class="p-2 border">Salida</th>
                        <th class="p-2 border">Resumen</th>
                    </tr>
                    <tr class="text-center text-xs uppercase">
                        <form method="GET" action="{{ route('asignaciones-turnos.index') }}">
                            <th class="p-1 border">
                                <input type="text" name="id" value="{{ request('id') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="empleado" value="{{ request('empleado') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <div class="flex flex-row space-x-1">
                                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}"
                                        class="form-control form-control-sm text-xs" />
                                    <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}"
                                        class="form-control form-control-sm text-xs" />
                                </div>
                            </th>
                            <th class="p-2 border">
                                <input type="text" name="obra" value="{{ request('obra') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="turno" value="{{ request('turno') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="maquina" value="{{ request('maquina') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="entrada" value="{{ request('entrada') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="salida" value="{{ request('salida') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <button type="submit" class="btn btn-sm btn-info px-2">
                                    <i class="fas fa-search"></i>
                                </button>

                                <a href="{{ route('asignaciones-turnos.index') }}" class="btn btn-sm btn-warning px-2">
                                    <i class="fas fa-undo"></i>
                                </a>
                            </th>
                        </form>
                    </tr>

                </thead>
                <tbody class="text-gray-700">
                    @forelse ($asignaciones as $asignacion)
                        <tr tabindex="0" x-data="{
                            editando: false,
                            asignacion: @js($asignacion),
                            original: JSON.parse(JSON.stringify(@js($asignacion)))
                        }"
                            @dblclick="if(!$event.target.closest('input')) {
                              if(!editando) {
                                editando = true;
                              } else {
                                asignacion = JSON.parse(JSON.stringify(original));
                                editando = false;
                              }
                            }"
                            @keydown.enter.stop="guardarCambios(asignacion, original); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                            <td class="px-2 py-2 border">{{ $asignacion->user->id }}</td>
                            <td class="px-2 py-2 border">{{ $asignacion->user->name ?? '—' }}</td>
                            <td class="px-2 py-2 border">
                                {{ \Carbon\Carbon::parse($asignacion->fecha)->format('d/m/Y') }}
                            </td>
                            <td class="px-2 py-2 border">{{ $asignacion->obra_id->obra ?? '—' }}</td>
                            <td class="px-2 py-2 border">{{ $asignacion->turno->nombre ?? '—' }}</td>
                            <td class="px-2 py-2 border">{{ $asignacion->maquina->nombre ?? '—' }}</td>

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


                            <!-- Entrada -->
                            <td class="px-2 py-2 border">
                                <template x-if="editando">
                                    <input type="time" x-model="asignacion.entrada"
                                        class="form-control form-control-sm" />
                                </template>
                                <template x-if="!editando">
                                    <span x-text="asignacion.entrada || '—'"></span>
                                </template>
                            </td>

                            <!-- Salida -->
                            <td class="px-2 py-2 border">
                                <template x-if="editando">
                                    <input type="time" x-model="asignacion.salida"
                                        class="form-control form-control-sm" />
                                </template>
                                <template x-if="!editando">
                                    <span x-text="asignacion.salida || '—'"></span>
                                </template>
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
    <script>
        function guardarCambios(asignacionData, originalData) {
            // Recortar segundos si vienen en formato HH:MM:SS
            if (asignacionData.entrada && asignacionData.entrada.length === 8) {
                asignacionData.entrada = asignacionData.entrada.slice(0, 5); // "22:05:00" → "22:05"
            }
            if (asignacionData.salida && asignacionData.salida.length === 8) {
                asignacionData.salida = asignacionData.salida.slice(0, 5);
            }

            fetch(`{{ route('asignaciones-turnos.update', '') }}/${asignacionData.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(asignacionData)
                })
                .then(async response => {
                    const contentType = response.headers.get('content-type');
                    let data = {};

                    if (contentType && contentType.includes('application/json')) {
                        data = await response.json();
                    } else {
                        const text = await response.text();
                        throw new Error("El servidor devolvió una respuesta inesperada: " + text.slice(0, 100));
                    }

                    if (response.ok && data.success) {
                        window.location.reload();
                    } else {
                        let errorMsg = data.message || "Ha ocurrido un error inesperado.";
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join("<br>");
                        }

                        Swal.fire({
                            icon: "error",
                            title: "Error al actualizar",
                            html: errorMsg,
                            confirmButtonText: "OK",
                            showCancelButton: true,
                            cancelButtonText: "Reportar Error"
                        }).then((result) => {
                            if (result.dismiss === Swal.DismissReason.cancel) {
                                notificarProgramador(errorMsg);
                            }
                        });

                        // Revertir
                        Object.assign(asignacionData, JSON.parse(JSON.stringify(originalData)));
                    }
                })
                .catch(async error => {
                    console.error("❌ Error en Fetch:", error);

                    // Revertir si falla la conexión
                    Object.assign(asignacionData, JSON.parse(JSON.stringify(originalData)));

                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        html: "No se pudo actualizar la asignación. Inténtalo nuevamente.",
                        confirmButtonText: "OK"
                    });
                });
        }
    </script>
</x-app-layout>
