<x-app-layout>
    <x-slot name="title">Asignaciones de Turnos</x-slot>

    <div class="w-full p-4 sm:p-2">
        <!-- Desktop -->
        <div class="hidden md:block">
            @livewire('asignaciones-turnos-table')
        </div>

        <!-- Móvil -->
        <div class="block md:hidden space-y-4">
            <div class="bg-gradient-to-r from-gray-900 to-gray-700 text-white rounded-2xl p-4 shadow-lg">
                <p class="text-sm font-semibold uppercase tracking-wide text-gray-300">Asignaciones de turnos</p>
                <p class="text-xs text-gray-200 mt-1">Consulta y filtra las asignaciones del personal.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-3">
                <form method="GET" action="{{ route('asignaciones-turnos.index') }}" class="space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Empleado</label>
                            <input type="text" name="empleado" value="{{ request('empleado') }}" placeholder="Nombre..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Obra</label>
                            <input type="text" name="obra" value="{{ request('obra') }}" placeholder="Obra..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Turno</label>
                            <input type="text" name="turno" value="{{ request('turno') }}" placeholder="Turno..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Máquina</label>
                            <input type="text" name="maquina" value="{{ request('maquina') }}" placeholder="Máquina..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Desde</label>
                            <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Hasta</label>
                            <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('asignaciones-turnos.index') }}"
                            class="text-xs text-gray-600 hover:text-gray-900">Limpiar</a>
                        <button type="submit"
                            class="bg-gray-900 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow hover:bg-gray-800">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            @php
                $mobilePage = max(1, (int) request('mpage', 1));
                $perPage = 10;

                $query = \App\Models\AsignacionTurno::with(['user', 'obra', 'turno', 'maquina']);

                if (request('empleado')) {
                    $query->whereHas('user', function ($q) {
                        $term = '%' . request('empleado') . '%';
                        $q->where('name', 'like', $term)
                            ->orWhere('primer_apellido', 'like', $term)
                            ->orWhere('segundo_apellido', 'like', $term);
                    });
                }
                if (request('obra')) {
                    $query->whereHas('obra', function ($q) {
                        $q->where('obra', 'like', '%' . request('obra') . '%');
                    });
                }
                if (request('turno')) {
                    $query->whereHas('turno', function ($q) {
                        $q->where('nombre', 'like', '%' . request('turno') . '%');
                    });
                }
                if (request('maquina')) {
                    $query->whereHas('maquina', function ($q) {
                        $q->where('nombre', 'like', '%' . request('maquina') . '%');
                    });
                }
                if (request('fecha_inicio')) {
                    $query->whereDate('fecha', '>=', request('fecha_inicio'));
                }
                if (request('fecha_fin')) {
                    $query->whereDate('fecha', '<=', request('fecha_fin'));
                }

                $asignacionesMobile = $query
                    ->latest('fecha')
                    ->skip(($mobilePage - 1) * $perPage)
                    ->take($perPage + 1)
                    ->get();

                $hayMasAsignaciones = $asignacionesMobile->count() > $perPage;
                if ($hayMasAsignaciones) {
                    $asignacionesMobile = $asignacionesMobile->take($perPage);
                }
            @endphp

            <div class="space-y-2">
                @forelse ($asignacionesMobile as $asignacion)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div
                            class="bg-gradient-to-tr from-gray-900 to-gray-700 text-white px-3 py-2 flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-[9px] text-gray-300">Empleado</p>
                                <h3 class="text-sm font-semibold tracking-tight truncate">
                                    {{ trim(($asignacion->user->name ?? '') . ' ' . ($asignacion->user->primer_apellido ?? '')) ?: '—' }}
                                </h3>
                                <p class="text-[9px] text-gray-300 mt-0.5 truncate">
                                    {{ optional($asignacion->fecha)->format('d/m/Y') ?? \Carbon\Carbon::parse($asignacion->fecha)->format('d/m/Y') }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-semibold bg-white/10 border border-white/20">
                                    {{ $asignacion->turno->nombre ?? 'Sin turno' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 space-y-2 text-xs text-gray-700">
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Obra</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ $asignacion->obra->obra ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Máquina</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ $asignacion->maquina->nombre ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Entrada</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ $asignacion->entrada ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Salida</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ $asignacion->salida ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-xs text-gray-600">
                        No hay asignaciones disponibles.
                    </div>
                @endforelse

                @if ($asignacionesMobile->count() > 0)
                    <div class="flex justify-between items-center gap-2 pt-2">
                        @if ($mobilePage > 1)
                            <a href="{{ route('asignaciones-turnos.index', array_merge(request()->except('mpage'), ['mpage' => $mobilePage - 1])) }}"
                                class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-800 text-xs font-semibold border border-gray-200 hover:bg-gray-200">
                                ← Anterior
                            </a>
                        @else
                            <span
                                class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-400 text-xs font-semibold border border-gray-100">
                                ← Anterior
                            </span>
                        @endif

                        <span class="text-xs text-gray-600">Página {{ $mobilePage }}</span>

                        @if ($hayMasAsignaciones)
                            <a href="{{ route('asignaciones-turnos.index', array_merge(request()->except('mpage'), ['mpage' => $mobilePage + 1])) }}"
                                class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-800 text-xs font-semibold border border-gray-200 hover:bg-gray-200">
                                Siguiente →
                            </a>
                        @else
                            <span
                                class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-400 text-xs font-semibold border border-gray-100">
                                Siguiente →
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
