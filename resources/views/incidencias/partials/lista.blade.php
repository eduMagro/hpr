@if (isset($grupos) && count($grupos) > 0)
    @foreach ($grupos as $maquina)
        <div x-data="{ expanded: false }"
            class="rounded-xl border border-gray-200 dark:border-blue-600 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            {{-- Machine Header --}}
            <div @click="expanded = !expanded"
                class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-blue-800/10 dark:to-blue-700/10 p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors flex items-center justify-between">
                <div class="flex items-center gap-4">
                    {{-- Machine Image/Icon --}}
                    <div
                        class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 border border-gray-200 overflow-hidden">
                        @if ($maquina->imagen)
                            <img src="{{ asset($maquina->imagen) }}" class="w-full h-full object-cover"
                                alt="{{ $maquina->nombre }}">
                        @else
                            <svg class="h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="m21.12 6.4-6.05-4.06a2 2 0 0 0-2.17-.05L2.95 8.41a2 2 0 0 0-.95 1.7v5.82a2 2 0 0 0 .88 1.66l6.05 4.07a2 2 0 0 0 2.17.05l9.95-6.12a2 2 0 0 0 .95-1.7V8.06a2 2 0 0 0-.88-1.66Z">
                                </path>
                                <path d="M10 22v-8L2.25 9.15"></path>
                                <path d="m10 14 11.77-6.87"></path>
                            </svg>
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-gray-900 dark:text-white text-lg">{{ $maquina->nombre }}</h3>
                            <span
                                class="text-xs font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-900 dark:border dark:border-gray-700 px-1.5 py-0.5 rounded">{{ $maquina->codigo }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-0.5">
                            <span
                                class="inline-flex items-center gap-1 font-medium {{ $maquina->incidencias->first()->estado == 'resuelta' ? 'text-green-600' : 'text-red-500' }}">
                                {{ $maquina->incidencias->count() }}
                                incidencia{{ $maquina->incidencias->count() !== 1 ? 's' : '' }}
                            </span>
                            <span>•</span>
                            <span class="text-gray-400">Última:
                                {{ $maquina->incidencias->first()->fecha_reporte->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Chevron --}}
                <div class="text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': expanded }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            {{-- Incidents List (Expanded) --}}
            <div x-show="expanded" x-collapse style="display: none;"
                class="border-t border-gray-100 dark:border-blue-600 bg-gray-50/50 dark:bg-transparent">
                @foreach ($maquina->incidencias as $incidencia)
                    <div class="p-4 last:border-0 hover:bg-white dark:hover:bg-gray-800/50 transition-colors group relative pl-20">
                        {{-- Connecting Line --}}
                        <div class="absolute left-10 top-0 bottom-0 w-px bg-gray-300 dark:bg-blue-600 group-last:bottom-1/2"></div>
                        <div class="absolute left-10 top-1/2 w-6 h-px bg-gray-300 dark:bg-blue-600"></div>

                        <a href="{{ route('incidencias.show', $incidencia->id) }}"
                            class="flex flex-col md:flex-row md:items-start justify-between gap-4">

                            <div class="flex justify-between items-center w-full">
                                <div class="flex gap-4 items-center">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[10px] {{ $incidencia->estado == 'resuelta' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} font-bold px-2 py-0.5 rounded-full uppercase tracking-wider border {{ $incidencia->estado == 'resuelta' ? 'border-green-200' : 'border-red-200' }}">
                                            {{ ucfirst($incidencia->estado) }}
                                        </span>
                                        <span
                                            class="text-xs text-gray-400 font-mono">#INC-{{ str_pad($incidencia->id, 4, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 text-sm uppercase">
                                        {{ $incidencia->titulo }}
                                    </h4>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                        <span
                                            class="text-xs text-gray-400">{{ $incidencia->fecha_reporte->format('d M Y, H:i') }}</span>
                                        <span class="text-gray-300">|</span>
                                        <div
                                            class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold">
                                            {{ substr($incidencia->user->name ?? '?', 0, 1) }}
                                        </div>
                                        <span>{{ $incidencia->user->name ?? 'Usuario' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="self-center shrink-0 p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@else
    <div
        class="text-center py-12 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 ">
        <div
            class="w-16 h-16 bg-gray-50 dark:bg-gray-900 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400 dark:text-gray-200">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200">Todo en orden</h3>
        <p class="text-gray-500 dark:text-gray-400 mt-1">No hay incidencias que coincidan con los filtros.</p>
    </div>
@endif