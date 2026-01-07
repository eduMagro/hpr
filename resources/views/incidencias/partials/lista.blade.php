@if (isset($incidencias))
    @forelse($incidencias as $incidencia)
        <div
            class="bg-white rounded-xl {{ $incidencia->estado == 'resuelta' ? 'border-green-500 opacity-75 hover:opacity-100' : 'border-red-500 shadow-sm hover:shadow-md' }} border-x-4 border-gray-200 overflow-hidden transition-all group relative">
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
                                {{ round($incidencia->fecha_reporte->diffInMinutes($incidencia->fecha_resolucion) / 60, 2) }}h
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
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
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
        {{ $incidencias->appends(['tab' => 'incidencias'])->onEachSide(1)->links() }}
    </div>
@endif
