{{--
    Componente: Etiqueta de ensamblaje individual
    Uso: <x-entidad.ensamblaje :etiqueta="$etiquetaEnsamblaje" :planilla="$planilla" />

    Recibe una EtiquetaEnsamblaje que contiene referencia a la entidad.
    Muestra número de unidad (ej: 1/3, 2/3) y permite seguimiento individual.
--}}

@props([
    'etiqueta',  // EtiquetaEnsamblaje
    'planilla',
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
    $longitudCm = ($etiqueta->longitud ?? $entidad->longitud_ensamblaje ?? 0) * 100;

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

    // Estado de la etiqueta para estilos
    $estadoClases = [
        'pendiente' => 'border-gray-400',
        'en_proceso' => 'border-yellow-500 bg-yellow-50',
        'completada' => 'border-green-500 bg-green-50',
    ];
    $estadoClase = $estadoClases[$etiqueta->estado] ?? 'border-gray-400';
@endphp

<style>
    .entidad-wrapper { display: block; margin: 0.5rem 0; }

    .entidad-card {
        position: relative;
        width: 126mm;
        height: 71mm;
        box-sizing: border-box;
        border: 0.2mm solid #000;
        overflow: hidden;
        background: #fff;
        padding: 3mm;
        display: flex;
        flex-direction: column;
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

<div class="entidad-wrapper" id="{{ $safeId }}">
    <div class="entidad-card">
        {{-- Botones de accion --}}
        <div class="absolute top-2 right-2 flex items-center gap-2 no-print z-10" style="right: 90px;">
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
                - L:{{ number_format($etiqueta->longitud ?? $entidad->longitud_ensamblaje ?? 0, 2, '.', '') }}m
                - {{ number_format($pesoUnidad, 2, '.', '') }}kg
            </h3>
        </div>

        {{-- SVG del ensamblaje - Blanco y Negro con Leyenda de Letras --}}
        <div class="flex-1 flex items-center justify-center" style="min-height: 0;">
            <svg viewBox="0 0 560 280" preserveAspectRatio="xMidYMid meet" style="width: 100%; height: 100%; max-height: 100%;">
                {{-- Fondo --}}
                <rect x="0" y="0" width="560" height="280" fill="#fff"/>

                {{-- === SECCION TRANSVERSAL (izquierda) === --}}
                <g transform="translate(10, 5)">
                    <text x="75" y="14" text-anchor="middle" font-size="11" font-weight="bold" fill="#000">SECCIÓN</text>
                    <text x="75" y="26" text-anchor="middle" font-size="9" fill="#000">({{ $estriboAncho }}x{{ $estriboAlto }} cm)</text>

                    {{-- Estribo exterior --}}
                    <rect x="15" y="32" width="120" height="120" fill="#fff" stroke="#000" stroke-width="3" rx="4"/>

                    {{-- Barras superiores con LETRA --}}
                    @if($totalSuperiores > 0)
                        @php
                            $numSup = $totalSuperiores;
                            $espaciadoSup = $numSup > 1 ? 90 / ($numSup - 1) : 0;
                            $startXSup = $numSup > 1 ? 35 : 80;
                        @endphp
                        @for($i = 0; $i < min($numSup, 6); $i++)
                            <circle cx="{{ $startXSup + ($i * $espaciadoSup) }}" cy="55" r="10" fill="#fff" stroke="#000" stroke-width="2"/>
                            <text x="{{ $startXSup + ($i * $espaciadoSup) }}" y="59" text-anchor="middle" font-size="11" fill="#000" font-weight="bold">{{ $letraSup ?? 'A' }}</text>
                        @endfor
                    @endif

                    {{-- Barras inferiores con LETRA --}}
                    @if($totalInferiores > 0)
                        @php
                            $numInf = $totalInferiores;
                            $espaciadoInf = $numInf > 1 ? 90 / ($numInf - 1) : 0;
                            $startXInf = $numInf > 1 ? 35 : 80;
                        @endphp
                        @for($i = 0; $i < min($numInf, 6); $i++)
                            <circle cx="{{ $startXInf + ($i * $espaciadoInf) }}" cy="130" r="10" fill="#fff" stroke="#000" stroke-width="2"/>
                            <text x="{{ $startXInf + ($i * $espaciadoInf) }}" y="134" text-anchor="middle" font-size="11" fill="#000" font-weight="bold">{{ $letraInf ?? 'B' }}</text>
                        @endfor
                    @endif

                    
                </g>

                {{-- === VISTA LATERAL (derecha) === --}}
                <g transform="translate(170, 5)">
                    <text x="190" y="14" text-anchor="middle" font-size="11" font-weight="bold" fill="#000">VISTA LATERAL</text>
                    <text x="190" y="26" text-anchor="middle" font-size="9" fill="#000">(L = {{ number_format($longitudCm/100, 2, '.', '') }}m)</text>

                    {{-- Fondo elemento --}}
                    <rect x="5" y="32" width="370" height="120" fill="#fff" stroke="#000" stroke-width="1" rx="3"/>

                    {{-- Barras superiores --}}
                    @if($totalSuperiores > 0)
                        <line x1="12" y1="52" x2="368" y2="52" stroke="#000" stroke-width="5" stroke-linecap="round"/>
                        <text x="30" y="45" font-size="12" fill="#000" font-weight="bold">{{ $letraSup ?? 'A' }}</text>
                        <text x="340" y="45" font-size="12" fill="#000" font-weight="bold">{{ $letraSup ?? 'A' }}</text>
                    @endif

                    {{-- Barras inferiores --}}
                    @if($totalInferiores > 0)
                        <line x1="12" y1="132" x2="368" y2="132" stroke="#000" stroke-width="5" stroke-linecap="round"/>
                        <text x="30" y="160" font-size="12" fill="#000" font-weight="bold">{{ $letraInf ?? 'B' }}</text>
                        <text x="340" y="160" font-size="12" fill="#000" font-weight="bold">{{ $letraInf ?? 'B' }}</text>
                    @endif

                    {{-- Estribos (con soporte para zonas de solape) --}}
                    @if($tieneSolape && $longitudSolapeCm > 0)
                        {{-- CON SOLAPE: zona sombreada sin estribos --}}
                        @php
                            $proporcionSolape = $longitudSolapeCm / max(1, $longitudCm);
                            $anchoSolape = min(370 * $proporcionSolape, 100);
                            $inicioEstribos = ($posicionSolape === "inferior" || $posicionSolape === "ambos") ? $anchoSolape : 0;
                            $finEstribos = ($posicionSolape === "superior" || $posicionSolape === "ambos") ? (370 - $anchoSolape) : 370;
                            $anchoZonaEstribos = max(1, $finEstribos - $inicioEstribos);
                            $numEst = min($cantidadEstribos, 20);
                            $espacEst = $anchoZonaEstribos / max(1, $numEst - 1);
                        @endphp
                        {{-- Zona de solape sombreada --}}
                        @if($posicionSolape === "inferior" || $posicionSolape === "ambos")
                            <rect x="5" y="32" width="{{ $anchoSolape }}" height="120" fill="#f0f0f0" stroke="#999" stroke-width="1" stroke-dasharray="4,2"/>
                            <text x="{{ 5 + $anchoSolape/2 }}" y="95" text-anchor="middle" font-size="7" fill="#666">SOLAPE</text>
                        @endif
                        @if($posicionSolape === "superior" || $posicionSolape === "ambos")
                            <rect x="{{ 375 - $anchoSolape }}" y="32" width="{{ $anchoSolape }}" height="120" fill="#f0f0f0" stroke="#999" stroke-width="1" stroke-dasharray="4,2"/>
                            <text x="{{ 375 - $anchoSolape/2 }}" y="95" text-anchor="middle" font-size="7" fill="#666">SOLAPE</text>
                        @endif
                        {{-- Estribos solo en zona sin solape --}}
                        @for($i = 0; $i < $numEst; $i++)
                            <line x1="{{ 5 + $inicioEstribos + ($i * $espacEst) }}" y1="40" x2="{{ 5 + $inicioEstribos + ($i * $espacEst) }}" y2="144" stroke="#000" stroke-width="1.5"/>
                        @endfor
                    @else
                        {{-- SIN SOLAPE: estribos en toda la longitud --}}
                        @php
                            $numEst = min($cantidadEstribos, 25);
                            $espacEst = 350 / max(1, $numEst - 1);
                        @endphp
                        @for($i = 0; $i < $numEst; $i++)
                            <line x1="{{ 15 + ($i * $espacEst) }}" y1="40" x2="{{ 15 + ($i * $espacEst) }}" y2="144" stroke="#000" stroke-width="1.5"/>
                        @endfor
                    @endif

                    {{-- Cota --}}
                    <line x1="5" y1="162" x2="375" y2="162" stroke="#000" stroke-width="1"/>
                    <line x1="5" y1="157" x2="5" y2="167" stroke="#000" stroke-width="1"/>
                    <line x1="375" y1="157" x2="375" y2="167" stroke="#000" stroke-width="1"/>
                    <text x="190" y="177" text-anchor="middle" font-size="10" fill="#000" font-weight="bold">{{ number_format($longitudCm, 0, '.', '') }} cm</text>

                    
                </g>

                {{-- === LEYENDA COMPLETA (abajo) === --}}
                <g transform="translate(10, 205)">
                    {{-- Armadura longitudinal --}}
                    @php $xLeg = 0; @endphp
                    @foreach($armaduraConLetras as $arm)
                        @if($arm['tipo'] === 'longitudinal')
                            <circle cx="{{ $xLeg }}" cy="12" r="12" fill="#fff" stroke="#000" stroke-width="2"/>
                            <text x="{{ $xLeg }}" y="17" text-anchor="middle" font-size="12" fill="#000" font-weight="bold">{{ $arm['letra'] }}</text>
                            <text x="{{ $xLeg + 20 }}" y="18" font-size="12" fill="#000">: {{ $arm['cantidad'] }}&#8960;{{ $arm['diametro'] }}mm ({{ $arm['posicion'] }})</text>
                            @php $xLeg += 150; @endphp
                        @endif
                    @endforeach

                    {{-- Armadura transversal (estribos) --}}
                    @foreach($armaduraConLetras as $arm)
                        @if($arm['tipo'] === 'transversal')
                            <rect x="{{ $xLeg - 8 }}" y="0" width="16" height="22" fill="#fff" stroke="#000" stroke-width="2" rx="2"/>
                            <text x="{{ $xLeg }}" y="17" text-anchor="middle" font-size="12" fill="#000" font-weight="bold">{{ $arm['letra'] }}</text>
                            <text x="{{ $xLeg + 18 }}" y="18" font-size="12" fill="#000">: {{ $arm['cantidad'] }}&#8960;{{ $arm['diametro'] }}mm c/{{ number_format($arm['separacion'], 0, '.', '') }}cm</text>
                            @php $xLeg += 170; @endphp
                        @endif
                    @endforeach

                    {{-- Segunda linea con info adicional --}}
                    <text x="0" y="55" font-size="12" fill="#000">
                        Unidad: {{ $unidadTexto }} | Peso: {{ number_format($pesoUnidad, 2, '.', '') }}kg | Barras: {{ $entidad->total_barras ?? '-' }} | Estribos: {{ $entidad->total_estribos ?? '-' }}
                    </text>
                </g>
            </svg>
        </div>
    </div>
</div>

{{-- Script para QR --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
    });
</script>
