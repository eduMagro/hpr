{{-- Mapa de nave para ejecutar salida, renderizado vía AJAX --}}
@php
    /** @var array $mapaData */
    $mapaData = $mapaData ?? [];

    $ctx = $mapaData['ctx'] ?? [];
    $localizacionesZonas = $mapaData['localizacionesZonas'] ?? [];
    $localizacionesMaquinas = $mapaData['localizacionesMaquinas'] ?? [];
    $paquetesConLocalizacion = $mapaData['paquetesConLocalizacion'] ?? [];
    $dimensiones = $mapaData['dimensiones'] ?? null;
    $obraActualId = $mapaData['obraActualId'] ?? null;
@endphp

@if (!empty($ctx))
    @include('partials.mapa-component-ajax', [
        'ctx' => $ctx,
        'localizacionesZonas' => $localizacionesZonas,
        'localizacionesMaquinas' => $localizacionesMaquinas,
        'paquetesConLocalizacion' => $paquetesConLocalizacion,
        'dimensiones' => $dimensiones,
        'obraActualId' => $obraActualId,
    ])
@else
    <div class="flex items-center justify-center h-full text-gray-400">
        <div class="text-center">
            <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <p class="text-gray-500">Mapa no disponible para esta nave</p>
        </div>
    </div>
@endif

