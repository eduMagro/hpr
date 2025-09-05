@props([
    'etiqueta', // Objeto de la etiqueta
    'planilla', // Objeto de la planilla
    'maquinaTipo', // Tipo de la máquina ("ensambladora" u otro)
])

@php
    $safeSubId = str_replace('.', '-', $etiqueta->etiqueta_sub_id);
    $estado = strtolower($etiqueta->estado ?? 'pendiente');

@endphp
<style>
    /* Mapa de colores centralizado */
    .proceso {
        --bg-estado: #e5e7eb;
    }

    /* default */

    .proceso.estado-pendiente {
        --bg-estado: #ffffff;
    }

    /* blanco */
    .proceso.estado-fabricando,
    .proceso.estado-ensamblando,
    .proceso.estado-soldando {
        --bg-estado: #facc15;
    }

    /* amarillo */

    .proceso.estado-fabricada,
    .proceso.estado-completada,
    .proceso.estado-ensamblada,
    .proceso.estado-soldada {
        --bg-estado: #22c55e;
    }

    /* verde */
</style>



<div class="proceso border shadow-xl mt-4 estado-{{ $estado }}" id="etiqueta-{{ $safeSubId }}"
    data-estado="{{ $estado }}" style="background-color: var(--bg-estado); border:1px solid black; width:100%;">

    <div class="relative"><!-- Botón de impresión -->
        <!-- Botón para fabricar la etiqueta actual -->

        <button onclick="imprimirEtiquetas(['{{ $etiqueta->etiqueta_sub_id }}'])"
            class="absolute top-2 right-2 text-blue-800 hover:text-blue-900 no-print" title="Imprimir esta etiqueta">
            🖨️
        </button>
        <button type="button"
            class="absolute top-2 right-12 bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 no-print btn-fabricar"
            data-etiqueta-id="{{ $etiqueta->etiqueta_sub_id }}" title="Fabricar esta etiqueta">
            ⚙️
        </button>
    </div>

    <!-- Contenido principal -->
    <div class="p-2">
        <h2 class="text-lg font-semibold text-gray-900">
            <span>{{ $planilla->obra->obra }}</span> -
            <span>{{ $planilla->cliente->empresa }}</span><br>
            <span>{{ $planilla->codigo_limpio }}</span> - S:{{ $planilla->seccion }}
        </h2>

        <h3 class="text-lg font-semibold text-gray-900">
            <span class="text-blue-700">{{ $etiqueta->etiqueta_sub_id }}</span>
            {{ $etiqueta->nombre ?? 'Sin nombre' }} -
            <span>Cal:B500SD</span> -
            {{ $etiqueta->peso_kg ?? 'N/A' }}
        </h3>

        <!-- QR oculto -->
        <div id="qrContainer-{{ $etiqueta->id }}" style="display: none;"></div>

        <!-- Datos de estado y fechas -->
        <div class="p-2 no-print">
            <p>
                <strong>Estado:</strong>
                <span id="estado-{{ str_replace('.', '-', $etiqueta->etiqueta_sub_id) }}">
                    {{ $etiqueta->estado ?? 'N/A' }}
                </span>
                <strong>Fecha Inicio:</strong>
                <span id="inicio-{{ str_replace('.', '-', $etiqueta->etiqueta_sub_id) }}">
                    {{ $maquinaTipo === 'ensambladora'
                        ? $etiqueta->fecha_inicio_ensamblado ?? 'No asignada'
                        : $etiqueta->fecha_inicio ?? 'No asignada' }}
                </span>
                <strong>Fecha Finalización:</strong>
                <span id="final-{{ str_replace('.', '-', $etiqueta->etiqueta_sub_id) }}">
                    {{ $maquinaTipo === 'ensambladora'
                        ? $etiqueta->fecha_finalizacion_ensamblado ?? 'No asignada'
                        : $etiqueta->fecha_finalizacion ?? 'No asignada' }}
                </span>
            </p>
        </div>
    </div>

    <!-- Canvas -->
    <div>
        <div id="contenedor-svg-{{ $etiqueta->id }}" class="w-full h-full"></div>
        <div style="width:100%;border-top:1px solid black;visibility:hidden;height:0;">
            <canvas id="canvas-imprimir-etiqueta-{{ $etiqueta->etiqueta_sub_id }}"></canvas>
        </div>
    </div>
</div>
