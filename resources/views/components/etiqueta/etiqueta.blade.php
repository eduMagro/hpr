@props([
    'etiqueta', // Objeto de la etiqueta
    'planilla', // Objeto de la planilla
    'maquinaTipo', // Tipo de la máquina ("ensambladora" u otro)
])

@php
    $safeSubId = str_replace('.', '-', $etiqueta->etiqueta_sub_id);
    $estadoPrincipal = strtolower($etiqueta->estado ?? 'pendiente');
    $estado2 = $etiqueta->estado2 ? strtolower($etiqueta->estado2) : null;

    // ✅ OPTIMIZADO: Usar la colección ya cargada en lugar de hacer consulta
    // Obtener maquina_id y maquina_id_2 del primer elemento (todos los elementos de una etiqueta comparten las mismas máquinas)
    $primerElemento = $etiqueta->relationLoaded('elementos')
        ? $etiqueta->elementos->first()
        : $etiqueta->elementos()->first();
    $maquinaId1 = $primerElemento?->maquina_id;
    $maquinaId2 = $primerElemento?->maquina_id_2;

    // Verificar si la etiqueta tiene elementos con maquina_id_2 (proceso secundario)
    $tieneMaquina2 = !is_null($maquinaId2);

    // El estado para el color depende de si tiene maquina_id_2
    // Si tiene maquina_id_2, el color se basa en estado2
    if ($tieneMaquina2 && $estado2) {
        $estado = $estado2;
    } else {
        $estado = $estadoPrincipal;
    }

    // Estado combinado para mostrar en badge
    $estadoCombinado = ucfirst($estadoPrincipal);
    if ($estado2) {
        $estadoCombinado .= '/' . ucfirst($estado2);
    }

    if (in_array($estado, ['fabricada', 'completada', 'ensamblada', 'soldada']) && $etiqueta->paquete_id) {
        $estado = 'en-paquete';
    }

@endphp

<style>
    /* === Contenedor general === */
    .etiqueta-wrapper {
        display: block;
        margin: 0.5rem 0;
    }

    /* === Prevenir FOUC (Flash of Unstyled Content) === */
    .proceso {
        opacity: 0;
        transition: opacity 0.15s ease-in;
    }

    /* === Etiqueta base (pantalla e impresión) === */
    /* Tamaño real para impresión */
    .etiqueta-card {
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
        justify-content: flex-start;
        transform-origin: top left;
    }

    /* Contenedor del SVG - expande y posiciona el SVG en la parte inferior */
    .etiqueta-card > div[id^="contenedor-svg-"] {
        flex: 1 1 0%;
        min-height: 0;
        overflow: hidden;
        display: flex;
        align-items: flex-end; /* SVG pegado abajo */
    }

    .etiqueta-card svg {
        width: 100%;
        height: auto; /* Altura automática, no 100% */
        max-height: 100%;
        display: block;
    }

    /* QR Box */
    .qr-box {
        position: absolute;
        top: 3mm;
        right: 3mm;
        border: 0.2mm solid #000;
        padding: 1mm;
        background: #fff;
        width: 18mm;
        /* 16mm del QR + 2mm de padding */
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .qr-box img {
        width: 16mm;
        height: 16mm;
        display: block;
    }

    .qr-label {
        width: 16mm;
        font-size: 8pt;
        color: #000;
        text-align: center;
        margin-top: 0.5mm;
        font-weight: bold;
        line-height: 1;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* Asegurar que el label del QR se imprima */
    @media print {
        .qr-label {
            display: block !important;
            font-size: 8pt !important;
            font-weight: bold !important;
            line-height: 1 !important;
        }
    }

    /* === Ajustes de pantalla === */
    /* Pantalla: escala mayor sin romper proporción */
    @media screen {
        .etiqueta-card {
            width: 630px;
            /* ancho grande en pantalla (~5 veces más que 126mm) */
            height: 355px;
            /* alto proporcional */
            margin: 0 1rem 1rem 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            /* Transición más rápida y eficiente - solo transform para mejor rendimiento */
            transform-origin: top left;
            will-change: transform;
        }

        .qr-label {
            font-size: 6px !important;
        }

        /* Los estilos responsivos están en resources/css/etiquetas-responsive.css */
    }

    /* Impresión: usa medidas exactas en mm */
    @media print {
        .etiqueta-card {
            width: 105mm !important;
            height: 59.4mm !important;
            margin: 0;
            box-shadow: none;
        }

        .no-print {
            display: none !important;
        }
    }

    /* Bloquea selección accidental en móviles */
    .proceso,
    .proceso * {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
</style>

<div class="etiqueta-wrapper" data-etiqueta-sub-id="{{ $etiqueta->etiqueta_sub_id }}" data-paquete-id="{{ $etiqueta->paquete_id ?? '' }}">
    <div class="etiqueta-card proceso estado-{{ $estado }}{{ $estado2 ? ' estado2-' . $estado2 : '' }}" id="etiqueta-{{ $safeSubId }}"
        data-estado="{{ $estadoPrincipal }}"
        data-estado2="{{ $estado2 ?? '' }}"
        data-tiene-maquina2="{{ $tieneMaquina2 ? 'true' : 'false' }}"
        data-maquina-id="{{ $maquinaId1 ?? '' }}"
        data-maquina-id-2="{{ $maquinaId2 ?? '' }}"
        data-en-paquete="{{ $etiqueta->paquete_id ? 'true' : 'false' }}"
        data-planilla-codigo="{{ $planilla->codigo_limpio ?? '' }}"
        data-planilla-id="{{ $planilla->id ?? '' }}">

        <!-- Botones -->
        <div class="absolute top-2 right-2 flex items-center gap-2 no-print z-10">
            <!-- Botón Deshacer (UNDO) - Manejado por historialEtiquetas.js -->
            <button type="button"
                class="btn-deshacer bg-amber-500 text-white px-3 py-1 rounded shadow-sm hover:bg-amber-600 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                data-etiqueta-id="{{ $etiqueta->etiqueta_sub_id }}"
                title="Deshacer último cambio (Ctrl+Z)">
                <span class="text-lg">↩️</span>
            </button>

            <!-- Selector de modo de impresión -->
            <select id="modo-impresion-{{ $etiqueta->id }}"
                class="border border-gray-300 rounded px-2 py-1 text-sm bg-white shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="a6">A6</option>
                <option value="a4">A4</option>
            </select>

            <!-- Botón Imprimir -->
            <button type="button"
                class="bg-blue-600 text-white px-3 py-1 rounded shadow-sm hover:bg-blue-700 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                onclick="const modo = document.getElementById('modo-impresion-{{ $etiqueta->id }}').value; imprimirEtiquetas(['{{ $etiqueta->etiqueta_sub_id }}'], modo)"
                title="Imprimir etiqueta">
                <span class="text-lg">🖨️</span>
            </button>

            <!-- Botón Añadir al carro -->
            <button type="button"
                class="btn-agregar-carro bg-green-600 text-white px-3 py-1 rounded shadow-sm hover:bg-green-700 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                data-etiqueta-id="{{ $etiqueta->etiqueta_sub_id }}" title="Añadir al carro">
                <span class="text-lg">🛒</span>
            </button>

            <!-- Botón Fabricar -->
            <button type="button"
                class="btn-fabricar bg-purple-600 text-white px-3 py-1 rounded shadow-sm hover:bg-purple-700 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                data-etiqueta-id="{{ $etiqueta->etiqueta_sub_id }}" title="Fabricar esta etiqueta">
                <span class="text-lg">⚙️</span>
            </button>
        </div>

        <!-- Contenido -->
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                {{ $planilla->obra->obra ?? 'Sin obra' }} - {{ $planilla->cliente->empresa ?? 'Sin cliente' }}<br>
                {{ $planilla->codigo_limpio }} - S:{{ $planilla->seccion }}
            </h2>
            <h3 class="text-lg font-semibold text-gray-900">
                <span class="etiqueta-codigo">{{ $etiqueta->etiqueta_sub_id }}</span>
                @if($etiqueta->paquete)
                    <span class="paquete-codigo text-purple-600 font-bold">({{ $etiqueta->paquete->codigo }})</span>
                @else
                    <span class="paquete-codigo text-purple-600 font-bold" style="display: none;"></span>
                @endif
                - {{ $etiqueta->nombre ?? 'Sin nombre' }} - Cal:B500SD -
                {{ $etiqueta->peso_kg ?? 'N/A' }}
                <span class="estado-badge ml-2 text-xs px-2 py-0.5 rounded-full
                    @if(in_array($estado, ['pendiente']))
                        bg-gray-200 text-gray-700
                    @elseif(in_array($estado, ['fabricando', 'ensamblando', 'soldando', 'doblando']))
                        bg-yellow-200 text-yellow-800
                    @elseif(in_array($estado, ['fabricada', 'completada', 'ensamblada', 'soldada']))
                        bg-green-200 text-green-800
                    @elseif($estado === 'en-paquete')
                        bg-purple-200 text-purple-800
                    @else
                        bg-gray-200 text-gray-700
                    @endif
                ">{{ $estadoCombinado }}</span>
            </h3>
        </div>

        <!-- SVG -->
        <div id="contenedor-svg-{{ $etiqueta->id }}" class="w-full h-full"></div>

        <!-- Canvas oculto para impresión -->
        <div style="width:100%;border-top:1px solid black;visibility:hidden;height:0;">
            <canvas id="canvas-imprimir-etiqueta-{{ $etiqueta->etiqueta_sub_id }}"></canvas>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="{{ asset('js/imprimirEtiqueta.js') }}?v={{ time() }}"></script>
