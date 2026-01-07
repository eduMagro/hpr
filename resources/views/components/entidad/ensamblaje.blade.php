{{--
    Componente: Etiqueta de ensamblaje individual
    Uso: <x-entidad.ensamblaje :etiqueta="$etiquetaEnsamblaje" :planilla="$planilla" />

    Recibe una EtiquetaEnsamblaje que contiene referencia a la entidad.
    Muestra número de unidad (ej: 1/3, 2/3) y permite seguimiento individual.
--}}

@props([
    'etiqueta',  // EtiquetaEnsamblaje
    'planilla',
    'maquinaId' => null,  // ID de la máquina para el botón ensamblar
])

@php
    // Obtener la entidad desde la etiqueta
    $entidad = $etiqueta->entidad;

    $distribucion = $entidad->distribucion ?? [];
    $composicion = $entidad->composicion ?? [];
    $armaduraLong = $distribucion['armadura_longitudinal'] ?? [];
    $armaduraTrans = $distribucion['armadura_transversal'] ?? [];
    $safeId = 'etiqueta-ens-' . $etiqueta->id;

    // Agrupar barras por posicion
    $barrasPorPosicion = collect($armaduraLong)->groupBy('posicion');
    $barrasInferiores = $barrasPorPosicion->get('inferior', collect());
    $barrasSuperiores = $barrasPorPosicion->get('superior', collect());

    $totalInferiores = $barrasInferiores->sum('cantidad');
    $totalSuperiores = $barrasSuperiores->sum('cantidad');

    if ($totalInferiores == 0 && $totalSuperiores == 0) {
        $totalBarras = collect($armaduraLong)->sum('cantidad');
        $totalInferiores = ceil($totalBarras / 2);
        $totalSuperiores = floor($totalBarras / 2);
        $barrasInferiores = collect($armaduraLong)->take(1);
        $barrasSuperiores = collect($armaduraLong)->skip(1)->take(1);
    }

    $primerEstribo = $armaduraTrans[0] ?? null;
    $separacionEstribos = $primerEstribo['separacion_aprox_cm'] ?? 15;
    $cantidadEstribos = $primerEstribo['cantidad'] ?? $entidad->total_estribos;
    $diametroEstribo = $primerEstribo['diametro'] ?? 8;
    // longitud_ensamblaje está en mm (los datos reales), convertir a cm
    $longitudCm = ($etiqueta->longitud ?? $entidad->longitud_ensamblaje ?? 0) / 10;

    // Dimensiones del estribo (alto x ancho)
    $estribosComp = $composicion["estribos"] ?? [];
    $primerEstriboComp = $estribosComp[0] ?? null;
    $estriboAlto = $primerEstriboComp["alto_cm"] ?? ($primerEstriboComp["alto"] ?? null);
    $estriboAncho = $primerEstriboComp["ancho_cm"] ?? ($primerEstriboComp["ancho"] ?? null);
    if (!$estriboAlto || !$estriboAncho) {
        $maxBarrasLado = max($totalSuperiores, $totalInferiores);
        $estriboAncho = max(25, $maxBarrasLado * 8);
        $estriboAlto = 30;
    }

    // Info de solape (zonas sin estribos)
    $tieneSolape = $distribucion["solape"] ?? false;
    $longitudSolapeCm = ($distribucion["longitud_solape_cm"] ?? 0);
    $posicionSolape = $distribucion["posicion_solape"] ?? "inferior";

    // Asignar letras a cada tipo de armadura (A, B, C...)
    $letras = range('A', 'Z');
    $letraIndex = 0;
    $armaduraConLetras = [];

    // Primero las barras longitudinales
    foreach ($armaduraLong as $barra) {
        $armaduraConLetras[] = [
            'letra' => $letras[$letraIndex] ?? '?',
            'tipo' => 'longitudinal',
            'diametro' => $barra['diametro'] ?? '?',
            'cantidad' => $barra['cantidad'] ?? 0,
            'posicion' => $barra['posicion'] ?? 'long',
        ];
        $letraIndex++;
    }

    // Luego los estribos
    $letraEstribo = $letras[$letraIndex] ?? 'E';
    foreach ($armaduraTrans as $est) {
        $armaduraConLetras[] = [
            'letra' => $letraEstribo,
            'tipo' => 'transversal',
            'diametro' => $est['diametro'] ?? 8,
            'cantidad' => $est['cantidad'] ?? 0,
            'separacion' => $est['separacion_aprox_cm'] ?? 15,
        ];
    }

    // Obtener letras de superiores e inferiores
    $letraSup = null;
    $letraInf = null;
    $diametroSup = null;
    $diametroInf = null;
    foreach ($armaduraConLetras as $arm) {
        if ($arm['tipo'] === 'longitudinal') {
            if ($arm['posicion'] === 'superior' && !$letraSup) {
                $letraSup = $arm['letra'];
                $diametroSup = $arm['diametro'];
            }
            if ($arm['posicion'] === 'inferior' && !$letraInf) {
                $letraInf = $arm['letra'];
                $diametroInf = $arm['diametro'];
            }
        }
    }
    // Si no hay posiciones definidas, asignar A=superior, B=inferior
    if (!$letraSup && !$letraInf && count($armaduraLong) >= 2) {
        $letraSup = 'A';
        $letraInf = 'B';
        $diametroSup = $armaduraLong[0]['diametro'] ?? '?';
        $diametroInf = $armaduraLong[1]['diametro'] ?? '?';
    } elseif (!$letraSup && !$letraInf && count($armaduraLong) == 1) {
        $letraSup = 'A';
        $letraInf = 'A';
        $diametroSup = $armaduraLong[0]['diametro'] ?? '?';
        $diametroInf = $diametroSup;
    }

    // Codigo de etiqueta (viene de la BD)
    $codigoEtiqueta = $etiqueta->codigo;
    $unidadTexto = $etiqueta->numero_unidad . '/' . $etiqueta->total_unidades;
    $pesoUnidad = $etiqueta->peso ?? ($entidad->peso_total / max(1, $entidad->cantidad));

    // Mapear estados del modelo a estados CSS (igual que etiquetas normales)
    // en_proceso -> ensamblando, completada -> ensamblada
    $estadoOriginal = strtolower($etiqueta->estado ?? 'pendiente');
    $estadoCSS = match($estadoOriginal) {
        'en_proceso' => 'ensamblando',
        'completada' => 'ensamblada',
        default => $estadoOriginal,
    };

    // Obtener elementos asociados a esta etiqueta de ensamblaje
    // Cargar relaciones para obtener ubicación: elemento → etiqueta → paquete → localizacionPaquete
    $elementosEtiqueta = $etiqueta->elementos()
        ->with(['etiquetaRelacion.paquete.localizacionPaquete.localizacion'])
        ->get();

    // Si no tiene elementos directos, buscar por la entidad
    if ($elementosEtiqueta->isEmpty() && $entidad) {
        $elementosEtiqueta = $entidad->elementos()
            ->with(['etiquetaRelacion.paquete.localizacionPaquete.localizacion'])
            ->get();
    }

    // === VINCULAR ELEMENTOS CON LETRAS DE LA LEYENDA ===
    // Función para determinar si un elemento es estribo basándose en dimensiones
    $esEstribo = function($dimensiones) {
        if (!$dimensiones) return false;
        // Contar ángulos de 90 grados - los estribos tienen 4+ ángulos
        $angulos = preg_match_all('/90d/', $dimensiones);
        return $angulos >= 4;
    };

    // Crear mapa de diámetro -> letra para longitudinales
    $mapaLongitudinales = [];
    foreach ($armaduraConLetras as $arm) {
        if ($arm['tipo'] === 'longitudinal') {
            $diametro = floatval($arm['diametro']);
            $mapaLongitudinales[$diametro] = $arm['letra'];
        }
    }

    // Asignar letra y ubicación a cada elemento
    $elementosConLetra = $elementosEtiqueta->map(function($elem) use ($esEstribo, $mapaLongitudinales, $letraEstribo) {
        $diametro = floatval($elem->diametro ?? 0);
        $dimensiones = $elem->dimensiones ?? '';

        // Determinar tipo y letra
        if ($esEstribo($dimensiones)) {
            $elem->tipo_armadura = 'estribo';
            $elem->letra_leyenda = $letraEstribo ?? 'E';
        } else {
            $elem->tipo_armadura = 'longitudinal';
            // Buscar letra por diámetro exacto o más cercano
            if (isset($mapaLongitudinales[$diametro])) {
                $elem->letra_leyenda = $mapaLongitudinales[$diametro];
            } else {
                // Buscar el diámetro más cercano
                $closest = null;
                $minDiff = PHP_FLOAT_MAX;
                foreach ($mapaLongitudinales as $d => $l) {
                    $diff = abs($d - $diametro);
                    if ($diff < $minDiff) {
                        $minDiff = $diff;
                        $closest = $l;
                    }
                }
                $elem->letra_leyenda = $closest ?? 'A';
            }
        }

        // Obtener ubicación del paquete: elemento → etiqueta → paquete → localizacionPaquete
        $elem->ubicacion_texto = null;
        $elem->ubicacion_coords = null;

        $paquete = $elem->etiquetaRelacion->paquete ?? null;
        if ($paquete && $paquete->localizacionPaquete) {
            $locPaq = $paquete->localizacionPaquete;

            // Guardar coordenadas para el mini-mapa
            $elem->ubicacion_coords = [
                'x1' => $locPaq->x1,
                'y1' => $locPaq->y1,
                'x2' => $locPaq->x2,
                'y2' => $locPaq->y2,
            ];

            // Si tiene localización con nombre, usarla
            if ($locPaq->localizacion && $locPaq->localizacion->nombre) {
                $elem->ubicacion_texto = $locPaq->localizacion->nombre;
            } else {
                // Mostrar fila/columna de forma legible
                $elem->ubicacion_texto = "Fila {$locPaq->y1} Col {$locPaq->x1}";
            }
        }

        return $elem;
    });

    // Agrupar elementos por letra para mostrar organizados
    $elementosPorLetra = $elementosConLetra->groupBy('letra_leyenda')->sortKeys();

    // Preparar datos de ubicación para JavaScript
    $ubicacionesParaJS = $elementosPorLetra->mapWithKeys(function($elementos, $letra) {
        $elem = $elementos->first();
        $paquete = $elem->etiquetaRelacion->paquete ?? null;
        return [$letra => [
            'codigo' => $elem->etiqueta_sub_id ?? $elem->codigo ?? '',
            'paquete_codigo' => $paquete->codigo ?? null,
            'ubicacion_texto' => $elem->ubicacion_texto ?? null,
            'tiene_ubicacion' => !empty($elem->ubicacion_coords),
        ]];
    })->toArray();
@endphp

<style>
    .entidad-wrapper { display: block; margin: 0.5rem 0; }

    /* === Etiqueta base - IGUAL que etiquetas normales === */
    .entidad-card {
        position: relative;
        width: 126mm;
        height: 71mm;
        box-sizing: border-box;
        border: 0.2mm solid #000;
        overflow: hidden;
        background: var(--bg-estado, #fff);
        padding: 3mm;
        display: flex;
        flex-direction: column;
        transform-origin: top left;
    }

    .entidad-card .qr-box {
        position: absolute;
        top: 3mm;
        right: 3mm;
        border: 0.2mm solid #000;
        padding: 1mm;
        background: #fff;
        width: 18mm;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .entidad-card .qr-box img {
        width: 16mm;
        height: 16mm;
        display: block;
    }

    .entidad-card .qr-label {
        width: 16mm;
        font-size: 6pt;
        color: #000;
        text-align: center;
        margin-top: 0.5mm;
        font-weight: bold;
        line-height: 1;
    }

    @media screen {
        .entidad-card {
            width: 630px;
            height: 355px;
            margin: 0 1rem 1rem 1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .entidad-card .qr-box {
            width: 70px;
            top: 12px;
            right: 12px;
            padding: 4px;
        }
        .entidad-card .qr-box img {
            width: 60px;
            height: 60px;
        }
        .entidad-card .qr-label {
            width: 60px;
            font-size: 8px;
        }
    }

    @media print {
        .entidad-card {
            width: 148mm !important;
            height: 105mm !important;
            margin: 0;
            box-shadow: none;
            page-break-after: always;
        }
        .entidad-card .qr-box img {
            width: 20mm;
            height: 20mm;
        }
        .no-print { display: none !important; }
    }
</style>

<div class="entidad-wrapper" id="{{ $safeId }}" data-etiqueta-id="{{ $etiqueta->id }}">
    <div class="entidad-card proceso estado-{{ $estadoCSS }}" data-estado="{{ $estadoCSS }}" data-estado-original="{{ $estadoOriginal }}">
        {{-- Botones de accion --}}
        <div class="absolute top-2 right-2 flex items-center gap-2 no-print z-10" style="right: 90px;">
            @if($maquinaId)
                @php
                    $btnClases = match($etiqueta->estado) {
                        'pendiente' => 'bg-green-600 hover:bg-green-700',
                        'en_proceso' => 'bg-yellow-500 hover:bg-yellow-600',
                        'completada' => 'bg-gray-400 cursor-not-allowed',
                        default => 'bg-green-600 hover:bg-green-700',
                    };
                    $btnTexto = match($etiqueta->estado) {
                        'pendiente' => 'ENSAMBLAR',
                        'en_proceso' => 'COMPLETAR',
                        'completada' => 'COMPLETADA',
                        default => 'ENSAMBLAR',
                    };
                    $btnDisabled = $etiqueta->estado === 'completada';
                @endphp
                <button type="button"
                    id="btn-ensamblar-{{ $etiqueta->id }}"
                    data-etiqueta-id="{{ $etiqueta->id }}"
                    data-maquina-id="{{ $maquinaId }}"
                    data-estado="{{ $etiqueta->estado }}"
                    class="btn-ensamblar text-white px-4 py-2 rounded shadow-sm flex items-center gap-2 font-bold text-sm {{ $btnClases }}"
                    onclick="ensamblarEtiqueta({{ $etiqueta->id }}, {{ $maquinaId }})"
                    {{ $btnDisabled ? 'disabled' : '' }}
                    title="{{ $btnTexto }}">
                    <span>{{ $btnTexto }}</span>
                </button>
            @endif
            <button type="button"
                class="bg-blue-600 text-white px-3 py-1 rounded shadow-sm hover:bg-blue-700 flex items-center gap-1"
                onclick="imprimirEntidad('{{ $safeId }}')"
                title="Imprimir etiqueta A6">
                <span class="text-lg">🖨️</span>
            </button>
        </div>

        {{-- QR Box --}}
        <div class="qr-box">
            <div id="qr-{{ $safeId }}"></div>
            <div class="qr-label">{{ $codigoEtiqueta }}</div>
        </div>

        {{-- Header --}}
        <div style="padding-right: 75px;">
            <h2 class="text-base font-semibold text-gray-900 leading-tight">
                {{ $planilla->obra->obra ?? 'Obra' }} - {{ $planilla->cliente->empresa ?? 'Cliente' }}<br>
                {{ $planilla->codigo_limpio }} - S:{{ $planilla->seccion ?? '-' }}
            </h2>
            <h3 class="text-sm font-semibold text-gray-800">
                <span class="text-amber-700 text-lg">{{ $codigoEtiqueta }}</span>
                <span class="bg-gray-800 text-white px-2 py-0.5 rounded text-xs ml-1">{{ $unidadTexto }}</span>
                @if($etiqueta->estado !== 'pendiente')
                    <span class="px-2 py-0.5 rounded text-xs ml-1 {{ $etiqueta->estado === 'completada' ? 'bg-green-600 text-white' : 'bg-yellow-500 text-black' }}">
                        {{ strtoupper($etiqueta->estado) }}
                    </span>
                @endif
                <br>
                {{ $etiqueta->marca }} {{ $etiqueta->situacion }}
                - L:{{ number_format(($etiqueta->longitud ?? $entidad->longitud_ensamblaje ?? 0) / 1000, 2, '.', '') }}m
                - {{ number_format($pesoUnidad, 2, '.', '') }}kg
            </h3>
        </div>

        {{-- SVG del ensamblaje - PLANO PROFESIONAL --}}
        <div id="contenedor-svg-{{ $safeId }}" class="flex-1 flex items-center justify-center" style="min-height: 0;">
            <svg viewBox="0 0 560 260" preserveAspectRatio="xMidYMid meet" style="width: 100%; height: 100%; max-height: 100%; background: var(--bg-estado, #fff);">
                {{-- Fondo --}}
                <rect class="svg-fondo" x="0" y="0" width="560" height="260" fill="transparent"/>

                {{-- === PLANO DE ENSAMBLADO COMPLETO === --}}
                {!! \App\Helpers\SvgBarraHelper::generarPlanoEnsamblado([
                    'longitud' => $longitudCm / 100, // Convertir cm a metros para el helper
                    'estriboAncho' => $estriboAncho,
                    'estriboAlto' => $estriboAlto,
                    'totalSuperiores' => $totalSuperiores,
                    'totalInferiores' => $totalInferiores,
                    'cantidadEstribos' => $cantidadEstribos,
                    'separacionEstribos' => $separacionEstribos,
                    'armaduraConLetras' => $armaduraConLetras,
                    'elementosPorLetra' => $elementosPorLetra,
                    'letraSup' => $letraSup ?? 'A',
                    'letraInf' => $letraInf ?? 'B',
                    'letraEstribo' => $letraEstribo ?? 'C',
                    'composicion' => $composicion,
                ]) !!}

                {{-- Info resumen en la esquina inferior --}}
                <g transform="translate(145, 240)">
                    <text x="0" y="12" font-size="9" fill="#333">
                        <tspan font-weight="bold">{{ $codigoEtiqueta }}</tspan> |
                        <tspan>{{ $unidadTexto }}</tspan> |
                        <tspan font-weight="bold">{{ number_format($pesoUnidad, 2, '.', '') }}kg</tspan>
                    </text>
                </g>
            </svg>
        </div>
    </div>

    {{-- Sección de elementos asociados - Organizado por LETRA --}}
    @if($elementosConLetra->isNotEmpty())
        <div class="mt-3 bg-white rounded-lg border-2 border-blue-200 shadow-sm" x-data="{ showElementos: true }">
            {{-- Header con título y resumen --}}
            <button @click="showElementos = !showElementos"
                    class="w-full flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-blue-100 rounded-t-lg hover:from-blue-100 hover:to-blue-150 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🔧</span>
                    <div class="text-left">
                        <div class="font-bold text-blue-900">ELEMENTOS PARA ENSAMBLAJE</div>
                        <div class="text-xs text-blue-600">{{ $elementosConLetra->count() }} piezas organizadas por tipo</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    {{-- Resumen rápido de letras --}}
                    @foreach($elementosPorLetra as $letra => $elementos)
                        <span class="w-7 h-7 flex items-center justify-center rounded-full bg-blue-600 text-white text-sm font-bold shadow">
                            {{ $letra }}
                        </span>
                    @endforeach
                    <svg class="w-5 h-5 ml-2 text-blue-600 transform transition-transform" :class="{ 'rotate-180': showElementos }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </button>

            {{-- Contenido expandible --}}
            <div x-show="showElementos" x-collapse class="p-3">
                @foreach($elementosPorLetra as $letra => $elementos)
                    @php
                        $primerElem = $elementos->first();
                        $tipoArmadura = $primerElem->tipo_armadura ?? 'longitudinal';
                        $colorLetra = $tipoArmadura === 'estribo'
                            ? 'bg-amber-500 border-amber-600'
                            : 'bg-blue-600 border-blue-700';
                        $colorFondo = $tipoArmadura === 'estribo'
                            ? 'bg-amber-50 border-amber-200'
                            : 'bg-blue-50 border-blue-200';
                    @endphp

                    {{-- Grupo por letra --}}
                    <div class="mb-4 last:mb-0">
                        {{-- Header del grupo --}}
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 flex items-center justify-center rounded-lg {{ $colorLetra }} text-white text-xl font-black shadow-md border-2">
                                {{ $letra }}
                            </div>
                            <div>
                                <div class="font-bold text-gray-800">
                                    {{ $tipoArmadura === 'estribo' ? 'ESTRIBOS' : 'BARRAS LONGITUDINALES' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    Ø{{ $primerElem->diametro ?? '-' }}mm · {{ $elementos->count() }} unidad(es)
                                </div>
                            </div>
                        </div>

                        {{-- Elementos de este grupo --}}
                        <div class="grid grid-cols-1 gap-2 ml-12">
                            @foreach($elementos as $elemento)
                                @php
                                    $estadoElem = strtolower($elemento->estado ?? 'pendiente');
                                    $colorEstado = match($estadoElem) {
                                        'fabricando' => 'border-l-yellow-500 bg-yellow-50',
                                        'fabricada', 'completada' => 'border-l-green-500 bg-green-50',
                                        default => 'border-l-gray-400 bg-gray-50',
                                    };
                                    $iconoEstado = match($estadoElem) {
                                        'fabricando' => '🔄',
                                        'fabricada', 'completada' => '✅',
                                        default => '⏳',
                                    };
                                    $figuraId = 'figura-elem-' . $etiqueta->id . '-' . $elemento->id;
                                    $dimensionesLimpias = $elemento->dimensiones ? preg_replace('/\s+/', ' ', trim($elemento->dimensiones)) : null;
                                @endphp

                                <div class="flex gap-3 p-2 rounded-lg border-l-4 {{ $colorEstado }} border border-gray-200">
                                    {{-- Info del elemento --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 flex items-center justify-center rounded {{ $colorLetra }} text-white text-xs font-bold">
                                                    {{ $letra }}
                                                </span>
                                                <span class="font-bold text-gray-800 text-sm">{{ $elemento->etiqueta_sub_id ?? 'Elem-'.$elemento->id }}</span>
                                            </div>
                                            <div class="flex items-center gap-1 text-xs">
                                                <span>{{ $iconoEstado }}</span>
                                                <span class="font-medium {{ $estadoElem === 'fabricada' || $estadoElem === 'completada' ? 'text-green-700' : ($estadoElem === 'fabricando' ? 'text-yellow-700' : 'text-gray-600') }}">
                                                    {{ strtoupper($estadoElem) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-600 flex flex-wrap gap-x-3">
                                            <span><strong>Ø</strong> {{ $elemento->diametro ?? '-' }}mm</span>
                                            <span><strong>L:</strong> {{ number_format(($elemento->longitud ?? 0) / 1000, 2) }}m</span>
                                            <span><strong>Peso:</strong> {{ number_format($elemento->peso ?? 0, 2) }}kg</span>
                                        </div>
                                        @if($elemento->ubicacion_texto)
                                            <div class="mt-1 text-xs text-blue-700 font-medium flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                                </svg>
                                                <span>📦 {{ $elemento->ubicacion_texto }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Figura del elemento --}}
                                    @if($dimensionesLimpias)
                                        <div id="{{ $figuraId }}"
                                             class="w-32 h-20 bg-white rounded border border-gray-300 flex items-center justify-center overflow-hidden flex-shrink-0"
                                             data-dimensiones="{{ $dimensionesLimpias }}"
                                             data-peso="{{ $elemento->peso ?? 0 }}"
                                             data-diametro="{{ $elemento->diametro ?? 0 }}"
                                             data-barras="{{ $elemento->barras ?? 1 }}">
                                            <span class="text-gray-400 text-[10px]">...</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Resumen final --}}
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>Total: {{ $elementosConLetra->count() }} elementos</span>
                        <span>Peso total: {{ number_format($elementosConLetra->sum('peso'), 2) }} kg</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Modal para mapa de ubicación --}}
<div id="modal-ubicacion-{{ $safeId }}"
     class="fixed inset-0 z-50 hidden"
     x-data="{ open: false }"
     x-show="open"
     x-cloak
     @keydown.escape.window="open = false; document.getElementById('modal-ubicacion-{{ $safeId }}').classList.add('hidden')">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
         @click="open = false; document.getElementById('modal-ubicacion-{{ $safeId }}').classList.add('hidden')"></div>

    {{-- Modal content --}}
    <div class="absolute inset-4 md:inset-10 bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <div>
                    <h3 class="font-bold text-lg">Ubicación del Elemento</h3>
                    <p class="text-blue-100 text-sm" id="modal-ubicacion-{{ $safeId }}-subtitulo">
                        Elemento <span class="font-semibold" id="modal-letra-{{ $safeId }}">-</span>
                        <span id="modal-codigo-{{ $safeId }}"></span>
                    </p>
                </div>
            </div>
            <button type="button"
                    class="p-2 hover:bg-white/20 rounded-full transition-colors"
                    @click="open = false; document.getElementById('modal-ubicacion-{{ $safeId }}').classList.add('hidden')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body con mapa --}}
        <div class="flex-1 p-4 overflow-hidden" id="modal-mapa-container-{{ $safeId }}">
            {{-- El mapa se carga dinámicamente aquí --}}
            <div class="h-full flex items-center justify-center text-gray-400">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-2 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p>Cargando mapa...</p>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-4 py-3 bg-gray-50 border-t flex items-center justify-between">
            <div class="text-sm text-gray-600" id="modal-info-paquete-{{ $safeId }}">
                {{-- Info del paquete --}}
            </div>
            <button type="button"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-700 font-medium transition-colors"
                    @click="open = false; document.getElementById('modal-ubicacion-{{ $safeId }}').classList.add('hidden')">
                Cerrar
            </button>
        </div>
    </div>
</div>

{{-- Script para QR --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // QR Code
        const qrContainer = document.getElementById('qr-{{ $safeId }}');
        if (qrContainer && typeof QRCode !== 'undefined' && !qrContainer.hasChildNodes()) {
            new QRCode(qrContainer, {
                text: '{{ $codigoEtiqueta }}',
                width: 60,
                height: 60,
                colorDark: '#000000',
                colorLight: '#ffffff',
            });
        }

        // Inicializar color del SVG según el estado (igual que etiquetas normales)
        const card = document.querySelector('#{{ $safeId }} .entidad-card');
        const contenedorSvg = document.getElementById('contenedor-svg-{{ $safeId }}');
        if (card && contenedorSvg) {
            const svg = contenedorSvg.querySelector('svg');
            if (svg) {
                const bgColor = getComputedStyle(card).getPropertyValue('--bg-estado').trim();
                svg.style.background = bgColor || '#fff';
            }
        }

        // === INTERACTIVIDAD DE LA LEYENDA ===
        const naveId = {{ $planilla->obra->id ?? 'null' }};
        const safeId = '{{ $safeId }}';

        // Datos de ubicación de elementos por letra
        const ubicacionesPorLetra = @json($ubicacionesParaJS);

        // Agregar listeners a elementos de la leyenda
        if (contenedorSvg) {
            const svg = contenedorSvg.querySelector('svg');
            if (svg) {
                const elementos = svg.querySelectorAll('.leyenda-elemento[data-tiene-ubicacion="1"]');
                elementos.forEach(el => {
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const letra = this.dataset.letra;
                        const codigo = this.dataset.codigo;
                        const paqueteCodigo = this.dataset.paquete;

                        if (!paqueteCodigo || !naveId) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Sin ubicación',
                                    text: 'Este elemento no tiene ubicación asignada en el mapa.',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            }
                            return;
                        }

                        abrirModalUbicacion(letra, codigo, paqueteCodigo);
                    });

                    // Hover effect
                    el.addEventListener('mouseenter', function() {
                        this.style.opacity = '0.8';
                    });
                    el.addEventListener('mouseleave', function() {
                        this.style.opacity = '1';
                    });
                });
            }
        }

        // Función para abrir modal de ubicación
        function abrirModalUbicacion(letra, codigo, paqueteCodigo) {
            const modal = document.getElementById('modal-ubicacion-' + safeId);
            const container = document.getElementById('modal-mapa-container-' + safeId);
            const letraSpan = document.getElementById('modal-letra-' + safeId);
            const codigoSpan = document.getElementById('modal-codigo-' + safeId);
            const infoPaquete = document.getElementById('modal-info-paquete-' + safeId);

            if (!modal || !container) return;

            // Actualizar info del modal
            if (letraSpan) letraSpan.textContent = letra;
            if (codigoSpan) codigoSpan.textContent = codigo ? ' - ' + codigo : '';
            if (infoPaquete) infoPaquete.innerHTML = paqueteCodigo
                ? '<span class="font-semibold">📦 Paquete:</span> ' + paqueteCodigo
                : '';

            // Mostrar modal
            modal.classList.remove('hidden');
            // Activar Alpine si está disponible
            if (modal.__x) {
                modal.__x.$data.open = true;
            }

            // Cargar mapa via AJAX
            container.innerHTML = `
                <div class="h-full flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p>Cargando mapa...</p>
                    </div>
                </div>
            `;

            // Cargar el componente de mapa via fetch
            fetch('/api/mapa-nave/' + naveId)
                .then(response => response.json())
                .then(result => {
                    if (!result.success) {
                        throw new Error(result.message || 'Error cargando mapa');
                    }

                    // Renderizar mapa inline
                    renderizarMapaInline(container, result.data, paqueteCodigo);
                })
                .catch(error => {
                    console.error('Error cargando mapa:', error);
                    container.innerHTML = `
                        <div class="h-full flex items-center justify-center">
                            <div class="text-center text-red-600">
                                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <p>Error al cargar el mapa</p>
                                <p class="text-sm text-gray-500 mt-1">${error.message}</p>
                            </div>
                        </div>
                    `;
                });
        }

        // Renderizar mapa inline con highlight del paquete
        function renderizarMapaInline(container, data, paqueteCodigo) {
            const { ctx, localizacionesZonas, localizacionesMaquinas, paquetesConLocalizacion } = data;
            const mapId = 'mapa-modal-' + safeId + '-' + Date.now();

            const W = ctx.columnasReales;
            const H = ctx.filasReales;
            const isVertical = !ctx.estaGirado;

            // Calcular tamaño óptimo
            const containerRect = container.getBoundingClientRect();
            const maxWidth = containerRect.width - 40;
            const maxHeight = containerRect.height - 40;
            const cellW = Math.floor(maxWidth / (isVertical ? W : H));
            const cellH = Math.floor(maxHeight / (isVertical ? H : W));
            const cellSize = Math.max(4, Math.min(cellW, cellH, 15));

            const gridWidth = (isVertical ? W : H) * cellSize;
            const gridHeight = (isVertical ? H : W) * cellSize;

            container.innerHTML = `
                <div class="h-full flex flex-col">
                    <div class="flex-1 overflow-auto flex items-center justify-center p-2">
                        <div id="${mapId}-grid" class="relative"
                             style="width: ${gridWidth}px; height: ${gridHeight}px;
                                    background-image: linear-gradient(to right, #e5e7eb 1px, transparent 1px),
                                                      linear-gradient(to bottom, #e5e7eb 1px, transparent 1px);
                                    background-size: ${cellSize}px ${cellSize}px;
                                    border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                    </div>
                    <div class="text-center text-xs text-gray-500 mt-2">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-3 h-3 bg-blue-500 rounded animate-pulse"></span>
                            Paquete destacado: <strong>${paqueteCodigo}</strong>
                        </span>
                    </div>
                </div>
            `;

            const grid = document.getElementById(mapId + '-grid');
            if (!grid) return;

            // Función para transformar coordenadas
            function realToViewRect(x1r, y1r, x2r, y2r) {
                const x1 = Math.min(x1r, x2r);
                const x2 = Math.max(x1r, x2r);
                const y1 = Math.min(y1r, y2r);
                const y2 = Math.max(y1r, y2r);

                function mapPointToView(x, y) {
                    if (isVertical) return { x, y: (H - y + 1) };
                    return { x: y, y: x };
                }

                const p1 = mapPointToView(x1, y1);
                const p2 = mapPointToView(x2, y1);
                const p3 = mapPointToView(x1, y2);
                const p4 = mapPointToView(x2, y2);

                const xs = [p1.x, p2.x, p3.x, p4.x];
                const ys = [p1.y, p2.y, p3.y, p4.y];

                return {
                    x: Math.min(...xs),
                    y: Math.min(...ys),
                    w: Math.max(...xs) - Math.min(...xs) + 1,
                    h: Math.max(...ys) - Math.min(...ys) + 1,
                };
            }

            // Renderizar zonas
            localizacionesZonas.forEach(loc => {
                const rect = realToViewRect(loc.x1, loc.y1, loc.x2, loc.y2);
                const div = document.createElement('div');
                div.className = 'absolute bg-gray-200/50 border border-gray-300 flex items-center justify-center text-[8px] text-gray-600 overflow-hidden';
                div.style.left = (rect.x - 1) * cellSize + 'px';
                div.style.top = (rect.y - 1) * cellSize + 'px';
                div.style.width = rect.w * cellSize + 'px';
                div.style.height = rect.h * cellSize + 'px';
                if (loc.nombre && rect.w * cellSize > 20) {
                    div.innerHTML = `<span class="truncate px-0.5">${loc.nombre}</span>`;
                }
                grid.appendChild(div);
            });

            // Renderizar máquinas
            localizacionesMaquinas.forEach(loc => {
                const rect = realToViewRect(loc.x1, loc.y1, loc.x2, loc.y2);
                const div = document.createElement('div');
                div.className = 'absolute bg-purple-200/70 border-2 border-purple-400 flex items-center justify-center text-[8px] text-purple-700 font-bold overflow-hidden';
                div.style.left = (rect.x - 1) * cellSize + 'px';
                div.style.top = (rect.y - 1) * cellSize + 'px';
                div.style.width = rect.w * cellSize + 'px';
                div.style.height = rect.h * cellSize + 'px';
                if (loc.nombre && rect.w * cellSize > 20) {
                    div.innerHTML = `<span class="truncate px-0.5">${loc.nombre}</span>`;
                }
                grid.appendChild(div);
            });

            // Renderizar paquetes
            paquetesConLocalizacion.forEach(paq => {
                const rect = realToViewRect(paq.x1, paq.y1, paq.x2, paq.y2);
                const esDestacado = paq.codigo === paqueteCodigo;

                const div = document.createElement('div');
                div.className = 'absolute flex items-center justify-center transition-all';
                div.style.left = (rect.x - 1) * cellSize + 'px';
                div.style.top = (rect.y - 1) * cellSize + 'px';
                div.style.width = rect.w * cellSize + 'px';
                div.style.height = rect.h * cellSize + 'px';

                if (esDestacado) {
                    div.className += ' bg-blue-500 border-2 border-blue-700 shadow-lg z-10';
                    div.style.animation = 'pulse 2s infinite';
                    // Scroll al paquete destacado
                    setTimeout(() => {
                        div.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                    }, 100);
                } else {
                    div.className += ' bg-amber-200/60 border border-amber-400';
                }

                grid.appendChild(div);
            });

            // Añadir animación CSS
            if (!document.getElementById('pulse-animation-style')) {
                const style = document.createElement('style');
                style.id = 'pulse-animation-style';
                style.textContent = `
                    @keyframes pulse {
                        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
                        50% { opacity: 0.8; box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
                    }
                `;
                document.head.appendChild(style);
            }
        }
    });
</script>
