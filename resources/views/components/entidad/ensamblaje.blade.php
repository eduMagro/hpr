{{--
    Componente: Visualizacion grafica de entidad/ensamblaje
    Uso: <x-entidad.ensamblaje :entidad="$entidad" :planilla="$planilla" />
--}}

@props([
    'entidad',
    'planilla',
    'expandido' => false,
])

@php
    $distribucion = $entidad->distribucion ?? [];
    $armaduraLong = $distribucion['armadura_longitudinal'] ?? [];
    $armaduraTrans = $distribucion['armadura_transversal'] ?? [];
    $safeId = 'entidad-' . $entidad->id;

    // Calcular totales por posicion para la seccion transversal
    $barrasInferiores = collect($armaduraLong)->filter(fn($b) => ($b['posicion'] ?? '') === 'inferior')->sum('cantidad');
    $barrasSuperiores = collect($armaduraLong)->filter(fn($b) => ($b['posicion'] ?? '') === 'superior')->sum('cantidad');

    // Si no hay posicion definida, distribuir equitativamente
    if ($barrasInferiores == 0 && $barrasSuperiores == 0) {
        $totalBarras = collect($armaduraLong)->sum('cantidad');
        $barrasInferiores = ceil($totalBarras / 2);
        $barrasSuperiores = floor($totalBarras / 2);
    }

    // Datos del primer estribo para separacion
    $primerEstribo = $armaduraTrans[0] ?? null;
    $separacionEstribos = $primerEstribo['separacion_aprox_cm'] ?? 15;
    $cantidadEstribos = $primerEstribo['cantidad'] ?? $entidad->total_estribos;
    $diametroEstribo = $primerEstribo['diametro'] ?? 8;

    // Longitud en cm para calculos
    $longitudCm = ($entidad->longitud_ensamblaje ?? 0) * 100;
@endphp

<div class="entidad-ensamblaje-card bg-white border rounded-lg shadow-sm overflow-hidden"
     id="{{ $safeId }}"
     x-data="{ expandido: {{ $expandido ? 'true' : 'false' }} }">

    {{-- Header clickeable --}}
    <div class="p-3 bg-gradient-to-r from-amber-50 to-orange-50 border-b cursor-pointer hover:bg-amber-100 transition-colors"
         @click="expandido = !expandido">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                {{-- Icono expandir/colapsar --}}
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-5 h-5 text-amber-600 transition-transform duration-200"
                     :class="{ 'rotate-90': expandido }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>

                <div>
                    <h4 class="font-bold text-gray-800">
                        {{ $entidad->marca }}
                        <span class="font-normal text-gray-600">- {{ $entidad->situacion ?: 'Sin situacion' }}</span>
                    </h4>
                    <p class="text-xs text-gray-500">
                        {{ $entidad->cantidad }} unidad(es) |
                        L: {{ number_format($entidad->longitud_ensamblaje ?? 0, 2) }}m |
                        Peso: {{ number_format($entidad->peso_total ?? 0, 2) }}kg
                    </p>
                </div>
            </div>

            {{-- Badges resumen --}}
            <div class="flex gap-2">
                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">
                    {{ $entidad->total_barras }} barras
                </span>
                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">
                    {{ $entidad->total_estribos }} estribos
                </span>
            </div>
        </div>
    </div>

    {{-- Contenido expandible con grafico --}}
    <div x-show="expandido"
         x-collapse
         class="p-4 bg-gray-50">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Vista Seccion Transversal --}}
            <div class="text-center">
                <h5 class="font-semibold text-sm text-gray-700 mb-3">Seccion Transversal</h5>
                <div class="inline-block bg-white p-4 rounded border">
                    <svg width="180" height="180" viewBox="0 0 180 180" class="mx-auto">
                        {{-- Estribo exterior (rectangulo) --}}
                        <rect x="20" y="20" width="140" height="140"
                              fill="none"
                              stroke="#dc2626"
                              stroke-width="3"
                              rx="4"/>

                        {{-- Barras superiores --}}
                        @if($barrasSuperiores > 0)
                            @php
                                $espaciadoSup = $barrasSuperiores > 1 ? 120 / ($barrasSuperiores - 1) : 0;
                                $startXSup = $barrasSuperiores > 1 ? 30 : 90;
                            @endphp
                            @for($i = 0; $i < $barrasSuperiores; $i++)
                                <circle cx="{{ $startXSup + ($i * $espaciadoSup) }}"
                                        cy="40"
                                        r="8"
                                        fill="#2563eb"
                                        stroke="#1d4ed8"
                                        stroke-width="2"/>
                            @endfor
                        @endif

                        {{-- Barras inferiores --}}
                        @if($barrasInferiores > 0)
                            @php
                                $espaciadoInf = $barrasInferiores > 1 ? 120 / ($barrasInferiores - 1) : 0;
                                $startXInf = $barrasInferiores > 1 ? 30 : 90;
                            @endphp
                            @for($i = 0; $i < $barrasInferiores; $i++)
                                <circle cx="{{ $startXInf + ($i * $espaciadoInf) }}"
                                        cy="140"
                                        r="8"
                                        fill="#2563eb"
                                        stroke="#1d4ed8"
                                        stroke-width="2"/>
                            @endfor
                        @endif

                        {{-- Barras laterales (si hay mas de 4 barras totales) --}}
                        @if(($barrasSuperiores + $barrasInferiores) > 4)
                            <circle cx="40" cy="90" r="6" fill="#2563eb" stroke="#1d4ed8" stroke-width="2"/>
                            <circle cx="140" cy="90" r="6" fill="#2563eb" stroke="#1d4ed8" stroke-width="2"/>
                        @endif
                    </svg>
                </div>
            </div>

            {{-- Vista Lateral/Longitudinal --}}
            <div class="text-center">
                <h5 class="font-semibold text-sm text-gray-700 mb-3">Vista Lateral</h5>
                <div class="inline-block bg-white p-4 rounded border">
                    <svg width="300" height="120" viewBox="0 0 300 120" class="mx-auto">
                        {{-- Barras longitudinales superiores --}}
                        <line x1="15" y1="25" x2="285" y2="25"
                              stroke="#2563eb" stroke-width="4" stroke-linecap="round"/>

                        {{-- Barras longitudinales inferiores --}}
                        <line x1="15" y1="95" x2="285" y2="95"
                              stroke="#2563eb" stroke-width="4" stroke-linecap="round"/>

                        {{-- Estribos verticales --}}
                        @php
                            $numEstribosVista = min($cantidadEstribos, 20);
                            $espaciadoEstribos = 260 / max(1, $numEstribosVista - 1);
                        @endphp
                        @for($i = 0; $i < $numEstribosVista; $i++)
                            <line x1="{{ 20 + ($i * $espaciadoEstribos) }}" y1="20"
                                  x2="{{ 20 + ($i * $espaciadoEstribos) }}" y2="100"
                                  stroke="#dc2626" stroke-width="2"/>
                        @endfor

                        {{-- Cotas --}}
                        <text x="150" y="115" text-anchor="middle" font-size="10" fill="#666">
                            {{ number_format($longitudCm, 0) }} cm
                        </text>

                        {{-- Indicador separacion --}}
                        @if($numEstribosVista > 1)
                            <text x="150" y="12" text-anchor="middle" font-size="9" fill="#999">
                                c/{{ $separacionEstribos }}cm
                            </text>
                        @endif
                    </svg>
                </div>
            </div>
        </div>

        {{-- Leyenda detallada --}}
        <div class="mt-4 p-3 bg-white rounded border">
            <h5 class="font-semibold text-sm text-gray-700 mb-2">Composicion</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                {{-- Armadura Longitudinal --}}
                <div>
                    <p class="font-medium text-blue-700 flex items-center gap-2 mb-1">
                        <span class="w-3 h-3 bg-blue-600 rounded-full"></span>
                        Armadura Longitudinal
                    </p>
                    @if(count($armaduraLong) > 0)
                        <ul class="ml-5 text-gray-600 space-y-1">
                            @foreach($armaduraLong as $barra)
                                <li>
                                    {{ $barra['cantidad'] ?? '?' }}x
                                    <span class="font-mono">O{{ $barra['diametro'] ?? '?' }}</span>
                                    @if(!empty($barra['posicion']))
                                        <span class="text-gray-400">({{ $barra['posicion'] }})</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="ml-5 text-gray-400 italic">Sin datos</p>
                    @endif
                </div>

                {{-- Armadura Transversal --}}
                <div>
                    <p class="font-medium text-red-700 flex items-center gap-2 mb-1">
                        <span class="w-3 h-3 border-2 border-red-600 rounded"></span>
                        Armadura Transversal (Estribos)
                    </p>
                    @if(count($armaduraTrans) > 0)
                        <ul class="ml-5 text-gray-600 space-y-1">
                            @foreach($armaduraTrans as $estribo)
                                <li>
                                    {{ $estribo['cantidad'] ?? '?' }}x
                                    <span class="font-mono">O{{ $estribo['diametro'] ?? '?' }}</span>
                                    @if(!empty($estribo['separacion_aprox_cm']))
                                        <span class="text-gray-500">c/{{ $estribo['separacion_aprox_cm'] }}cm</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="ml-5 text-gray-400 italic">Sin datos</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Boton imprimir (opcional) --}}
        <div class="mt-3 flex justify-end no-print">
            <button type="button"
                    class="px-3 py-1.5 text-sm bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors"
                    onclick="imprimirEntidad('{{ $safeId }}')">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Imprimir
            </button>
        </div>
    </div>
</div>
