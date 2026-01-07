<x-app-layout>
    <x-slot name="title">Gestión de Incidencias - {{ config('app.name') }}</x-slot>

    <div class="px-4 py-6 max-w-7xl mx-auto">

        {{-- Header & Controls --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Panel de Incidencias</h1>
                <p class="text-gray-500 text-sm mt-1">Gestión de averías y mantenimiento correctivo</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('incidencias.create') }}"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl shadow-lg shadow-red-500/20 transition-all font-bold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Nueva Incidencia
                </a>
            </div>
        </div>

        {{-- Filters Bar --}}
        <div
            class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <form method="GET" action="{{ route('incidencias.index') }}" id="filterForm"
                    class="flex items-center gap-2">
                    <label class="flex items-center cursor-pointer group select-none">
                        <div class="relative">
                            <input type="checkbox" name="ver_inactivas" value="1"
                                {{ request('ver_inactivas') ? 'checked' : '' }} class="sr-only peer"
                                onchange="document.getElementById('filterForm').submit()">
                            <div
                                class="w-5 h-5 bg-gray-100 border-2 border-gray-300 rounded peer-checked:bg-blue-500 peer-checked:border-blue-500 transition-all flex items-center justify-center">
                                <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <span
                            class="ml-2 text-sm font-medium text-gray-600 group-hover:text-blue-600 transition-colors">Ver
                            historial (Resueltas)</span>
                    </label>
                </form>
            </div>

            <div class="text-sm text-gray-500">
                <span class="font-bold text-gray-900">{{ $activasCount }}</span> incidencias activas
            </div>
        </div>

        {{-- Incidents List --}}
        <div class="space-y-4">
            @forelse($incidencias as $incidencia)
                <div
                    class="bg-white rounded-xl border-l-4 {{ $incidencia->estado == 'resuelta' ? 'border-green-500 opacity-75 hover:opacity-100' : 'border-red-500 shadow-sm hover:shadow-md' }} border-y border-r border-gray-200 overflow-hidden transition-all group relative">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                            <div class="flex gap-4">
                                {{-- Icon --}}
                                <div
                                    class="w-16 h-16 rounded-xl {{ $incidencia->estado == 'resuelta' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' }} flex items-center justify-center shrink-0 border border-gray-100">
                                    @if ($incidencia->estado == 'resuelta')
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @else
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    @endif
                                </div>

                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            {{ $incidencia->maquina->nombre ?? 'Máquina Desconocida' }}</h3>
                                        <span
                                            class="text-xs font-mono text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">{{ $incidencia->maquina->codigo ?? '???' }}</span>
                                    </div>

                                    <p
                                        class="text-sm {{ $incidencia->estado == 'resuelta' ? 'text-green-600' : 'text-red-500' }} font-bold uppercase tracking-wider mb-1">
                                        {{ ucfirst($incidencia->estado) }}
                                    </p>

                                    <h4 class="font-semibold text-gray-800 mb-1">{{ $incidencia->titulo }}</h4>
                                    <p class="text-sm text-gray-500 line-clamp-2 max-w-2xl">
                                        {{ $incidencia->descripcion }}
                                    </p>
                                </div>
                            </div>

                            <div class="text-left md:text-right shrink-0">
                                <div class="text-xs text-gray-400 font-mono mb-1">
                                    #INC-{{ str_pad($incidencia->id, 4, '0', STR_PAD_LEFT) }}</div>
                                <div
                                    class="inline-flex items-center gap-1.5 bg-gray-50 px-2.5 py-1 rounded-lg border border-gray-100">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-600">
                                        {{ $incidencia->fecha_reporte->diffForHumans() }}
                                    </span>
                                </div>
                                @if ($incidencia->fecha_resolucion)
                                    <div class="mt-1 text-xs text-green-600 font-medium">
                                        Resuelta en
                                        {{ $incidencia->fecha_reporte->diffInHours($incidencia->fecha_resolucion) }}h
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold border border-blue-200">
                                        {{ substr($incidencia->user->name ?? '?', 0, 1) }}
                                    </div>
                                    <span class="text-xs text-gray-500">Reportado por <strong
                                            class="text-gray-700">{{ $incidencia->user->name ?? 'Usuario' }}</strong></span>
                                </div>
                            </div>

                            <a href="{{ route('incidencias.show', $incidencia->id) }}"
                                class="bg-white border border-gray-200 hover:border-blue-300 hover:text-blue-600 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg transition-all flex items-center gap-2 shadow-sm">
                                Ver Detalles
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                    <div
                        class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Todo en orden</h3>
                    <p class="text-gray-500 mt-1">No hay incidencias que coincidan con los filtros.</p>
                </div>
            @endforelse

            <div class="mt-4">
                {{ $incidencias->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
