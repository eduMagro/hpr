<x-app-layout>
    <x-slot name="title">Asignaciones de Turnos</x-slot>
    <x-menu.usuarios :totalSolicitudesPendientes="$totalSolicitudesPendientes ?? 0" />

    <div class="w-full px-6 py-4">

        {{-- @if (request('empleado') && $diasAsignados > 0)
            <div class="mb-4 text-sm text-gray-800 bg-gray-50 p-4 rounded shadow border leading-relaxed space-y-1">
                <p class="font-bold text-base text-blue-800">
                    <span>
                        {{ $asignaciones->first()->user->name ?? '—' }}
                        {{ $asignaciones->first()->user->primer_apellido ?? '—' }}
                        {{ $asignaciones->first()->user->segundo_apellido ?? '—' }}
                    </span>
                </p>

                <p><span class="text-blue-600">📋 Días asignados (con turno):</span> {{ $diasAsignados }}</p>
                <p><span class="text-indigo-600">🕐 Días fichados (con entrada real):</span> {{ $diasFichados }}
                </p>
                <p><span class="text-yellow-600">⚠️ No fichó:</span> {{ $diasSinFichaje }}</p>
                <p><span class="text-green-600">✅ Puntual (entra y sale bien):</span> {{ $diasPuntuales }}</p>
                <p><span class="text-red-600">❌ Llega tarde:</span> {{ $diasImpuntuales }}</p>
                <p><span class="text-purple-600">🔄 Se va antes:</span> {{ $diasSeVaAntes }}</p>
            </div>
        @endif

        <h3 class="text-lg font-bold mt-6 mb-4 text-blue-700">
            ⏱️ Ranking Puntualidad ({{ \Carbon\Carbon::now()->locale('es')->translatedFormat('F Y') }})
        </h3>

        @if ($estadisticasPuntualidad->isEmpty())
            <p class="text-sm text-gray-500 italic">No hay trabajadores con minutos de adelanto registrados este
                mes.
            </p>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($estadisticasPuntualidad as $index => $dato)
                @php
                    $medalla = match ($index) {
                        0 => '🥇',
                        1 => '🥈',
                        2 => '🥉',
                        default => null,
                    };

                    $bg = match ($index) {
                        0 => 'bg-yellow-300 border-yellow-600 text-gray-800', // 🥇 Oro
                        1 => 'bg-gray-300 border-gray-500 text-gray-800', // 🥈 Plata
                        2 => 'bg-amber-500 border-amber-700 text-gray-800', // 🥉 Bronce
                        default => 'bg-white border-gray-200 text-gray-800',
                    };

                @endphp

                <div
                    class="border {{ $bg }} rounded-xl shadow p-4 flex flex-col items-center text-sm text-gray-800">
                    <div class="text-3xl mb-2">{{ $medalla ?? '🎖️' }}</div>
                    <div class="font-bold text-base text-center">{{ $dato['usuario']->name }}
                        {{ $dato['usuario']->primer_apellido }} {{ $dato['usuario']->segundo_apellido }}</div>
                    <div class="mt-2 space-y-1 text-xs text-center">
                        <p><span class="text-blue-600 font-semibold">Minutos de adelanto:</span>
                            {{ $dato['minutos_adelanto'] }} min</p>
                    </div>
                </div>
            @endforeach
        </div>
 --}}

        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <!-- Tabla de asignaciones -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg">

            <table class="w-full border border-gray-300 rounded-lg text-xs uppercase text-center">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="p-2 border">{!! $ordenables['user_id'] !!}</th>
                        <th class="p-2 border">Empleado</th>
                        <th class="p-2 border">{!! $ordenables['fecha'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['obra_id'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['turno_id'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['maquina_id'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['entrada'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['salida'] !!}</th>

                        <th class="p-2 border">Resumen</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                    <tr class="text-center text-xs uppercase">
                        <form method="GET" action="{{ route('asignaciones-turnos.index') }}">
                            <th class="p-1 border">
                                <x-tabla.input name="id" type="text" :value="request('id')" class="w-full text-xs" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="empleado" type="text" :value="request('empleado')"
                                    class="w-full text-xs" />
                            </th>
                            <th class="p-1 border">
                                <div class="flex flex-row space-x-1">
                                    <x-tabla.input name="fecha_inicio" type="date" :value="request('fecha_inicio')"
                                        class="text-xs w-full" />
                                    <x-tabla.input name="fecha_fin" type="date" :value="request('fecha_fin')"
                                        class="text-xs w-full" />
                                </div>
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="obra" type="text" :value="request('obra')" class="w-full text-xs" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="turno" type="text" :value="request('turno')" class="w-full text-xs" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="maquina" type="text" :value="request('maquina')" class="w-full text-xs" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="entrada" type="text" :value="request('entrada')" class="w-full text-xs" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="salida" type="text" :value="request('salida')"
                                    class="w-full text-xs" />
                            </th>
                            <th class="p-1 border">

                            </th>
                            <x-tabla.botones-filtro ruta="asignaciones-turnos.index"
                                rutaExportar="asignaciones-turno.exportar" />
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
                            <td class="px-2 py-2 border">
                                {{ $asignacion->user->name ?? '—' }}
                                {{ $asignacion->user->primer_apellido ?? '—' }}
                                {{ $asignacion->user->segundo_apellido ?? '—' }}
                            </td>
                            <td class="px-2 py-2 border">
                                {{ \Carbon\Carbon::parse($asignacion->fecha)->format('d/m/Y') }}
                            </td>
                            <td class="px-2 py-2 border">{{ $asignacion->obra->obra ?? '—' }}</td>
                            <td class="px-2 py-2 border">
                                <template x-if="editando">
                                    <select x-model="asignacion.turno_id"
                                        class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">—</option>
                                        @foreach ($turnos as $turno)
                                            <option value="{{ $turno->id }}">{{ $turno->nombre }}</option>
                                        @endforeach
                                    </select>
                                </template>
                                <template x-if="!editando">
                                    <span>
                                        {{ $asignacion->turno->nombre ?? '—' }}
                                    </span>
                                </template>
                            </td>

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
                                    <div class="flex items-center gap-1 justify-center">
                                        <input type="time" x-model="asignacion.entrada"
                                            class="w-20 px-1 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        <button type="button" @click="asignacion.entrada = null"
                                            class="text-red-600 text-xs font-bold hover:text-red-800" title="Borrar">
                                            ❌
                                        </button>
                                    </div>


                                </template>
                                <template x-if="!editando">
                                    <span x-text="asignacion.entrada || '—'"></span>
                                </template>
                            </td>

                            <!-- Salida -->
                            <td class="px-2 py-2 border">
                                <template x-if="editando">
                                    <div class="flex items-center gap-1 justify-center">
                                        <input type="time" x-model="asignacion.salida"
                                            class="w-20 px-1 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        <button type="button" @click="asignacion.salida = null"
                                            class="text-red-600 text-xs font-bold hover:text-red-800" title="Borrar">
                                            ❌
                                        </button>
                                    </div>

                                </template>
                                <template x-if="!editando">
                                    <span x-text="asignacion.salida || '—'"></span>
                                </template>
                            </td>

                            <td class="px-2 py-2 border text-xs font-bold">
                                {!! $puntual !!}
                            </td>
                            <td class="px-2 py-2 border text-xs font-bold">
                                <div class="flex items-center space-x-2 justify-center">
                                    <!-- Mostrar solo en modo edición -->
                                    <x-tabla.boton-guardar x-show="editando"
                                        @click="guardarCambios(asignacion, original); editando = false" />
                                    <!-- Mostrar solo cuando NO está en modo edición -->
                                    <template x-if="!editando">
                                        <div class="flex items-center space-x-2">
                                            <x-tabla.boton-ver :href="route('users.show', $asignacion->user->id)" />
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-gray-500">No hay asignaciones
                                disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>


        </div>
        <x-tabla.paginacion :paginador="$asignaciones" />
    </div>
    <script>
        // Carga el mapa ID -> nombre del turno
        const turnosPorId = @json($turnos->pluck('nombre', 'id'));

        function guardarCambios(asignacionData, originalData) {
            if (asignacionData.entrada && asignacionData.entrada.length === 8) {
                asignacionData.entrada = asignacionData.entrada.slice(0, 5);
            }
            if (asignacionData.salida && asignacionData.salida.length === 8) {
                asignacionData.salida = asignacionData.salida.slice(0, 5);
            }

            const payload = {
                user_id: asignacionData.user_id,
                fecha_inicio: asignacionData.fecha,
                fecha_fin: asignacionData.fecha,
                turno_id: asignacionData.turno_id,
                tipo: turnosPorId[asignacionData.turno_id] ?? (asignacionData.estado || 'manual'),
                entrada: asignacionData.entrada,
                salida: asignacionData.salida,
                maquina_id: asignacionData.maquina_id,
                obra_id: asignacionData.obra_id,
            };

            fetch(`{{ route('asignaciones-turnos.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
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

                        Object.assign(asignacionData, JSON.parse(JSON.stringify(originalData)));
                    }
                })
                .catch(async error => {
                    console.error("❌ Error en Fetch:", error);

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
