@props([
    'grupo', // Array con datos del grupo de resumen
    'maquina', // Objeto de la máquina
])

@php
    // Obtener la primera etiqueta del grupo como representativa
    $primeraEtiqueta = \App\Models\Etiqueta::with(['planilla.obra', 'planilla.cliente', 'elementos'])
        ->find($grupo['etiquetas'][0]['id'] ?? null);

    if (!$primeraEtiqueta) {
        return;
    }

    $planilla = $primeraEtiqueta->planilla;
    $estado = strtolower($grupo['estado'] ?? 'pendiente');
    $safeSubId = 'grupo-' . $grupo['id'];

    $totalEtiquetas = $grupo['total_etiquetas'] ?? 0;
    $totalElementos = $grupo['total_elementos'] ?? 0;
    $pesoTotal = $grupo['peso_total'] ?? 0;
    $diametro = $grupo['diametro'] ?? 0;

    // Calcular suma total de barras de todos los elementos del grupo
    $totalBarras = collect($grupo['elementos'] ?? [])->sum('barras');

    // Usar el ID de la primera etiqueta para el contenedor SVG
    $contenedorSvgId = $primeraEtiqueta->id;
@endphp

<div class="etiqueta-wrapper" data-grupo-id="{{ $grupo['id'] }}" data-es-grupo="true">
    <div class="etiqueta-card proceso estado-{{ $estado }} grupo-resumen-card" id="etiqueta-{{ $safeSubId }}"
        data-estado="{{ $estado }}"
        data-grupo-id="{{ $grupo['id'] }}"
        data-maquina-id="{{ $maquina->id }}"
        data-diametro="{{ $diametro }}"
        data-contenedor-svg-id="{{ $contenedorSvgId }}"
        data-elementos='@json($grupo['elementos'] ?? [])'
        data-etiquetas-sub-ids='@json(collect($grupo["etiquetas"])->pluck("etiqueta_sub_id")->values())'
        data-primera-etiqueta-id="{{ $primeraEtiqueta->etiqueta_sub_id }}"
        data-planilla-id="{{ $planilla->id }}">

        <!-- Botones (igual que etiqueta normal) -->
        <div class="absolute top-2 right-2 flex items-center gap-2 no-print z-10">
            <!-- Badge de grupo (junto a los botones) -->
            <span class="bg-teal-600 text-white px-3 py-1 rounded shadow-sm flex items-center gap-1" title="Grupo de {{ $totalEtiquetas }} etiquetas resumidas">
                <svg style="width:16px;height:16px;flex:none;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span class="font-bold text-sm">x{{ $totalEtiquetas }}</span>
            </span>

            <!-- Botón Desagrupar -->
            <button type="button"
                class="bg-amber-500 text-white px-3 py-1 rounded shadow-sm hover:bg-amber-600 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                onclick="desagruparGrupo({{ $grupo['id'] }})"
                title="Desagrupar (volver a ver etiquetas individuales)">
                <span class="text-lg">↩️</span>
            </button>

            <!-- Selector de modo de impresión -->
            <select id="modo-impresion-grupo-{{ $grupo['id'] }}"
                class="border border-gray-300 rounded px-2 py-1 text-sm bg-white shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="a6">A6</option>
                <option value="a4">A4</option>
            </select>

            <!-- Botón Imprimir (todas las etiquetas) -->
            <button type="button"
                class="bg-blue-600 text-white px-3 py-1 rounded shadow-sm hover:bg-blue-700 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                onclick="imprimirTodasEtiquetasGrupo({{ $grupo['id'] }})"
                title="Imprimir las {{ $totalEtiquetas }} etiquetas">
                <span class="text-lg">🖨️</span>
            </button>

            <!-- Botón Añadir al carro (agrega todas las etiquetas del grupo) -->
            <button type="button"
                class="btn-agregar-carro-grupo bg-green-600 text-white px-3 py-1 rounded shadow-sm hover:bg-green-700 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                data-grupo-id="{{ $grupo['id'] }}"
                data-etiquetas='@json(collect($grupo["etiquetas"])->map(fn($et) => ["id" => $et["etiqueta_sub_id"], "peso" => 0])->values())'
                title="Añadir {{ $totalEtiquetas }} etiquetas al carro">
                <span class="text-lg">🛒</span>
            </button>

            <!-- Botón Fabricar (usa mismo JS que etiquetas normales) -->
            <button type="button"
                class="btn-fabricar bg-purple-600 text-white px-3 py-1 rounded shadow-sm hover:bg-purple-700 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                data-grupo-id="{{ $grupo['id'] }}"
                data-diametro="{{ $diametro }}"
                title="Fabricar todas las etiquetas del grupo">
                <span class="text-lg">⚙️</span>
            </button>
        </div>

        <!-- Contenido (igual que etiqueta normal) -->
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                {{ $planilla->obra->obra ?? 'N/A' }} - {{ $planilla->cliente->empresa ?? 'N/A' }}<br>
                {{ $planilla->codigo_limpio ?? $planilla->codigo }} - S:{{ $planilla->seccion }}
            </h2>
            <h3 class="text-lg font-semibold text-gray-900">
                {{ $primeraEtiqueta->etiqueta_sub_id }} - {{ $primeraEtiqueta->nombre ?? 'Sin nombre' }} - Cal:B500SD -
                {{ number_format($pesoTotal, 1) }} kg
            </h3>
        </div>

        <!-- SVG (igual que etiqueta normal) -->
        <div id="contenedor-svg-{{ $contenedorSvgId }}" class="w-full h-full"></div>

        <!-- Barra inferior con info del grupo -->
        <div class="absolute bottom-1 left-1 right-1 no-print">
            <div class="flex items-center justify-between text-xs bg-teal-50 border border-teal-200 rounded px-2 py-1">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-teal-700">Etiquetas:</span>
                    <div class="flex flex-wrap gap-1">
                        @foreach (array_slice($grupo['etiquetas'] ?? [], 0, 4) as $et)
                            <span class="bg-white border border-teal-300 text-teal-700 px-1.5 py-0.5 rounded text-xs">
                                {{ $et['etiqueta_sub_id'] }}
                            </span>
                        @endforeach
                        @if (count($grupo['etiquetas'] ?? []) > 4)
                            <span class="text-teal-600 font-medium">+{{ count($grupo['etiquetas']) - 4 }} más</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-teal-600 font-semibold" title="Total de barras sumadas">{{ $totalBarras }} barras</span>
                    <span class="text-teal-600">{{ $totalElementos }} elem.</span>
                </div>
            </div>
        </div>

        <!-- Canvas oculto -->
        <div style="visibility:hidden;height:0;">
            <canvas id="canvas-imprimir-grupo-{{ $grupo['id'] }}"></canvas>
        </div>
    </div>
</div>

<script>
    // Renderizar SVG del grupo cuando el DOM esté listo
    (function() {
        const grupoData = {
            id: {{ $contenedorSvgId }},
            etiqueta: { id: {{ $contenedorSvgId }} },
            elementos: @json($grupo['elementos'] ?? [])
        };

        console.log('🔍 DEBUG Grupo SVG:', {
            grupoId: {{ $grupo['id'] }},
            contenedorSvgId: {{ $contenedorSvgId }},
            elementosCount: grupoData.elementos.length,
            elementos: grupoData.elementos
        });

        function renderizarGrupo() {
            const contenedor = document.getElementById("contenedor-svg-{{ $contenedorSvgId }}");
            console.log('🎨 Renderizando grupo SVG:', {
                contenedorExists: !!contenedor,
                funcionExists: typeof window.renderizarGrupoSVG === 'function'
            });

            if (typeof window.renderizarGrupoSVG === 'function') {
                window.renderizarGrupoSVG(grupoData, {{ $grupo['id'] }});
            }
        }

        if (document.readyState === 'complete') {
            setTimeout(renderizarGrupo, 100);
        } else {
            window.addEventListener('load', () => setTimeout(renderizarGrupo, 100));
        }
    })();

</script>
