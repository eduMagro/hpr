<x-app-layout>
    <x-slot name="title">Asignaciones de Turnos</x-slot>
    @php
        $rutaActual = request()->route()->getName();
    @endphp
    @if (Auth::check() && Auth::user()->rol == 'oficina')
        <div class="w-full" x-data="{ open: false }">
            <!-- Menú móvil -->
            <div class="sm:hidden relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                    Opciones
                </button>

                <div x-show="open" x-transition @click.away="open = false"
                    class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                    x-cloak>

                    <a href="{{ route('users.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('users.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📋 Usuarios
                    </a>

                    <a href="{{ route('register') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('register') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📋 Registrar Usuario
                    </a>

                    <a href="{{ route('vacaciones.index') }}"
                        class="relative block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('vacaciones.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🌴 Vacaciones
                        @isset($totalSolicitudesPendientes)
                            @if ($totalSolicitudesPendientes > 0)
                                <span
                                    class="absolute top-2 right-4 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                    {{ $totalSolicitudesPendientes }}
                                </span>
                            @endif
                        @endisset
                    </a>

                    <a href="{{ route('asignaciones-turnos.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        ⏱️ Registros
                    </a>
                </div>
            </div>

            <!-- Menú escritorio -->
            <div class="hidden sm:flex sm:mt-0 w-full">
                <a href="{{ route('users.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ request()->routeIs('users.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📋 Usuarios
                </a>

                <a href="{{ route('register') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ request()->routeIs('register') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📋 Registrar Usuario
                </a>

                <a href="{{ route('vacaciones.index') }}"
                    class="relative flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ request()->routeIs('vacaciones.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🌴 Vacaciones
                    @isset($totalSolicitudesPendientes)
                        @if ($totalSolicitudesPendientes > 0)
                            <span
                                class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow">
                                {{ $totalSolicitudesPendientes }}
                            </span>
                        @endif
                    @endisset
                </a>

                <a href="{{ route('asignaciones-turnos.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    ⏱️ Registros Entrada y Salida
                </a>
            </div>
        </div>

    @endif

    <div class="w-full px-6 py-4">

        @if (request('empleado') && $diasAsignados > 0)
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
                            <x-tabla.botones-filtro ruta="asignaciones-turnos.index" />
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
                            <td class="px-2 py-2 border">{{ $asignacion->obra_id->obra ?? '—' }}</td>
                            <td class="px-2 py-2 border">
                                {{ $asignacion->turno->nombre ?? '—' }}</td>
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
                                        class="w-50 px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />

                                </template>
                                <template x-if="!editando">
                                    <span x-text="asignacion.entrada || '—'"></span>
                                </template>
                            </td>

                            <!-- Salida -->
                            <td class="px-2 py-2 border">
                                <template x-if="editando">
                                    <input type="time" x-model="asignacion.salida"
                                        class="w-50 px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />

                                </template>
                                <template x-if="!editando">
                                    <span x-text="asignacion.salida || '—'"></span>
                                </template>
                            </td>

                            <td class="px-2 py-2 border text-xs font-bold">
                                {!! $puntual !!}
                            </td>
                            <td class="px-2 py-2 border text-xs font-bold">
                                <template x-if="editando">
                                    <button @click="guardarCambios(usuario); editando = false"
                                        class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded shadow">
                                        Guardar
                                    </button>
                                </template>
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
