{{--
    Componente: Tarjeta de Grupo de Resumen

    Props:
    - grupo: Modelo GrupoResumen con etiquetas cargadas
--}}

@props(['grupo'])

<div class="grupo-resumen bg-gradient-to-br from-teal-50 to-teal-100 border-2 border-teal-300
            rounded-lg shadow-lg p-4 relative transition-all duration-200 hover:shadow-xl"
     data-grupo-id="{{ $grupo->id }}">

    {{-- Header del grupo --}}
    <div class="flex justify-between items-start mb-3">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs bg-teal-600 text-white px-2 py-0.5 rounded-full font-medium whitespace-nowrap">
                    GRUPO RESUMIDO
                </span>
                <span class="text-xs text-teal-600 font-mono">{{ $grupo->codigo }}</span>
            </div>
            <h3 class="font-bold text-lg text-teal-800 truncate" title="Ø{{ $grupo->diametro }} | {{ $grupo->dimensiones ?: 'barra' }}">
                Ø{{ $grupo->diametro }} | {{ $grupo->dimensiones ?: 'barra' }}
            </h3>
            <p class="text-sm text-teal-600">
                {{ $grupo->total_etiquetas }} etiquetas &middot;
                {{ $grupo->total_elementos }} elementos &middot;
                {{ number_format($grupo->peso_total, 2, ',', '.') }} kg
            </p>
        </div>

        {{-- Botones de acción --}}
        <div class="flex gap-1 ml-2 flex-shrink-0">
            <button onclick="toggleExpandirGrupo({{ $grupo->id }})"
                    class="p-2 bg-white rounded-lg hover:bg-teal-50 transition shadow-sm"
                    title="Ver etiquetas">
                <svg class="w-5 h-5 text-teal-600 grupo-chevron-{{ $grupo->id }} transition-transform duration-200"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <button onclick="desagruparGrupo({{ $grupo->id }})"
                    class="p-2 bg-white rounded-lg hover:bg-red-50 transition shadow-sm"
                    title="Desagrupar">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Lista de etiquetas (colapsable) --}}
    <div id="grupo-lista-{{ $grupo->id }}" class="hidden mt-3 border-t border-teal-200 pt-3">
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-teal-700">Etiquetas para imprimir:</span>
            <button onclick="imprimirTodasEtiquetasGrupo({{ $grupo->id }})"
                    class="text-xs bg-teal-600 hover:bg-teal-700 text-white px-3 py-1 rounded-full
                           flex items-center gap-1 transition-colors shadow-sm">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir todas
            </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto pr-1">
            @foreach($grupo->etiquetas as $etiqueta)
                <div class="bg-white rounded border border-teal-200 p-2 text-xs hover:border-teal-400 transition-colors">
                    <div class="font-medium text-gray-800 truncate" title="{{ $etiqueta->etiqueta_sub_id }}">
                        {{ $etiqueta->etiqueta_sub_id }}
                    </div>
                    <div class="text-gray-500 truncate" title="{{ $etiqueta->nombre }}">
                        {{ $etiqueta->nombre ?: '-' }}
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-teal-600">
                            {{ $etiqueta->elementos->count() }} elem
                        </span>
                        <button onclick="imprimirEtiquetaIndividual('{{ $etiqueta->etiqueta_sub_id }}')"
                                class="text-blue-500 hover:text-blue-700 p-1 hover:bg-blue-50 rounded transition"
                                title="Imprimir esta etiqueta">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
