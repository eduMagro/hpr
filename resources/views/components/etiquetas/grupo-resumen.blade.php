{{--
    Componente: Tarjeta de Grupo de Resumen

    Props:
    - grupo: Modelo GrupoResumen con etiquetas cargadas
--}}

@props(['grupo'])

@php
    $esMultiplanilla = $grupo->es_multiplanilla;
    // Colores según tipo de grupo
    $bgGradient = $esMultiplanilla
        ? 'from-purple-50 to-purple-100'
        : 'from-teal-50 to-teal-100';
    $borderColor = $esMultiplanilla ? 'border-purple-300' : 'border-teal-300';
    $badgeBg = $esMultiplanilla ? 'bg-purple-600' : 'bg-teal-600';
    $textColor = $esMultiplanilla ? 'text-purple-600' : 'text-teal-600';
    $textDark = $esMultiplanilla ? 'text-purple-800' : 'text-teal-800';
    $textMedium = $esMultiplanilla ? 'text-purple-700' : 'text-teal-700';
    $borderLight = $esMultiplanilla ? 'border-purple-200' : 'border-teal-200';
    $hoverBorder = $esMultiplanilla ? 'hover:border-purple-400' : 'hover:border-teal-400';
    $hoverBg = $esMultiplanilla ? 'hover:bg-purple-50' : 'hover:bg-teal-50';
@endphp

<div class="grupo-resumen bg-gradient-to-br {{ $bgGradient }} border-2 {{ $borderColor }}
            rounded-lg shadow-lg p-4 relative transition-all duration-200 hover:shadow-xl"
     data-grupo-id="{{ $grupo->id }}"
     data-es-multiplanilla="{{ $esMultiplanilla ? '1' : '0' }}">

    {{-- Header del grupo --}}
    <div class="flex justify-between items-start mb-3">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="text-xs {{ $badgeBg }} text-white px-2 py-0.5 rounded-full font-medium whitespace-nowrap">
                    {{ $esMultiplanilla ? 'MULTI-PLANILLA' : 'GRUPO RESUMIDO' }}
                </span>
                <span class="text-xs {{ $textColor }} font-mono">{{ $grupo->codigo }}</span>
                @if($esMultiplanilla)
                    @php
                        $codigosPlanillas = $grupo->codigos_planillas;
                    @endphp
                    <span class="text-xs text-purple-500" title="Planillas: {{ implode(', ', $codigosPlanillas) }}">
                        ({{ count($codigosPlanillas) }} planillas)
                    </span>
                @endif
            </div>
            <h3 class="font-bold text-lg {{ $textDark }} truncate" title="Ø{{ $grupo->diametro }} | {{ $grupo->dimensiones ?: 'barra' }}">
                Ø{{ $grupo->diametro }} | {{ $grupo->dimensiones ?: 'barra' }}
            </h3>
            <p class="text-sm {{ $textColor }}">
                {{ $grupo->total_etiquetas }} etiquetas &middot;
                {{ $grupo->total_elementos }} elementos &middot;
                {{ number_format($grupo->peso_total, 2, ',', '.') }} kg
            </p>
            @if($esMultiplanilla)
                <div class="flex flex-wrap gap-1 mt-1">
                    @foreach($codigosPlanillas as $codigoPlanilla)
                        <span class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded">
                            {{ $codigoPlanilla }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Botones de acción --}}
        <div class="flex gap-1 ml-2 flex-shrink-0">
            <button onclick="toggleExpandirGrupo({{ $grupo->id }})"
                    class="p-2 bg-white rounded-lg {{ $hoverBg }} transition shadow-sm"
                    title="Ver etiquetas">
                <svg class="w-5 h-5 {{ $textColor }} grupo-chevron-{{ $grupo->id }} transition-transform duration-200"
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
    <div id="grupo-lista-{{ $grupo->id }}" class="hidden mt-3 border-t {{ $borderLight }} pt-3">
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium {{ $textMedium }}">Etiquetas para imprimir:</span>
            <button onclick="imprimirTodasEtiquetasGrupo({{ $grupo->id }})"
                    class="text-xs {{ $badgeBg }} {{ $esMultiplanilla ? 'hover:bg-purple-700' : 'hover:bg-teal-700' }} text-white px-3 py-1 rounded-full
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
                <div class="bg-white rounded border {{ $borderLight }} p-2 text-xs {{ $hoverBorder }} transition-colors">
                    <div class="font-medium text-gray-800 truncate" title="{{ $etiqueta->etiqueta_sub_id }}">
                        {{ $etiqueta->etiqueta_sub_id }}
                    </div>
                    <div class="text-gray-500 truncate" title="{{ $etiqueta->nombre }}">
                        {{ $etiqueta->nombre ?: '-' }}
                    </div>
                    @if($esMultiplanilla && $etiqueta->planilla)
                        <div class="text-purple-500 truncate text-[10px]" title="Planilla: {{ $etiqueta->planilla->codigo_limpio ?? $etiqueta->planilla->codigo }}">
                            {{ $etiqueta->planilla->codigo_limpio ?? $etiqueta->planilla->codigo }}
                        </div>
                    @endif
                    <div class="flex justify-between items-center mt-1">
                        <span class="{{ $textColor }}">
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
