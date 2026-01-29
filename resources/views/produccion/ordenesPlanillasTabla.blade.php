<x-app-layout>
    <x-slot name="title">Tabla de Ordenes</x-slot>

    <x-page-header
        title="Tabla de Ordenes"
        subtitle="Listado y filtrado de órdenes de producción"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>'
    />

    <div class="p-4 md:p-6">
        {{-- Encabezado con filtros --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">

            <div class="flex flex-wrap items-center gap-2">
                {{-- Filtro por código de obra --}}
                <select id="filtro-cod-obra" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Cód. Obra</option>
                    @foreach($filtros['codigosObra'] as $codigo)
                        <option value="{{ strtolower($codigo) }}">{{ $codigo }}</option>
                    @endforeach
                </select>

                {{-- Filtro por nombre de obra --}}
                <select id="filtro-obra" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Obra</option>
                    @foreach($filtros['obras'] as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>

                {{-- Filtro por código de empresa --}}
                <select id="filtro-cod-empresa" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Cód. Empresa</option>
                    @foreach($filtros['codigosEmpresa'] as $codigo)
                        <option value="{{ strtolower($codigo) }}">{{ $codigo }}</option>
                    @endforeach
                </select>

                {{-- Filtro por nombre de empresa --}}
                <select id="filtro-empresa" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Empresa</option>
                    @foreach($filtros['empresas'] as $nombre)
                        <option value="{{ strtolower($nombre) }}">{{ $nombre }}</option>
                    @endforeach
                </select>

                {{-- Input buscar planilla --}}
                <input type="text" id="filtro-planilla-texto" placeholder="Buscar planilla..."
                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 w-32">

                {{-- Filtro por obra/planilla/fecha --}}
                <select id="filtro-planilla" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 min-w-[280px]">
                    <option value="" data-tipo="todos">-- Planilla --</option>
                    @foreach($obrasConPlanillas as $obra)
                        <optgroup label="{{ $obra->obra }}">
                            @foreach($obra->planillasEnOrden as $planilla)
                                @php
                                    $fechaRaw = $planilla->getRawOriginal('fecha_estimada_entrega');
                                    $fechaDisplay = $fechaRaw ? \Carbon\Carbon::parse($fechaRaw)->format('d/m/Y') : '-';
                                @endphp
                                <option value="{{ $planilla->id }}">
                                    {{ $planilla->codigo }} - {{ $fechaDisplay }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>

                {{-- Botón limpiar --}}
                <button type="button" id="btn-limpiar"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-lg text-sm flex items-center justify-center transition"
                    title="Restablecer filtros">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Resumen de posiciones (se muestra al filtrar) --}}
        <div id="resumen-posiciones" class="hidden mb-4 bg-fuchsia-50 border border-fuchsia-200 rounded-lg p-4">
            {{-- Ficha de la planilla --}}
            <div id="resumen-ficha" class="mb-4 pb-4 border-b border-fuchsia-200 hidden"></div>

            <h3 class="font-semibold text-fuchsia-800 mb-2">Posiciones:</h3>
            <div id="resumen-contenido" class="flex flex-wrap gap-2"></div>
            <div id="resumen-sin-orden" class="mt-3 pt-3 border-t border-fuchsia-200 hidden">
                <h4 class="font-semibold text-red-700 mb-2">Sin posicionar:</h4>
                <div id="resumen-sin-orden-contenido" class="flex flex-wrap gap-2"></div>
            </div>
        </div>

        {{-- Datos de planillas para JS --}}
        <script>
            window.planillasSinOrden = @json($planillasSinOrdenJs);
            window.planillasConOrden = @json($planillasConOrdenJs);
        </script>

        {{-- Tabla --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-600 to-blue-700">
                            <th class="px-3 py-3 text-white font-semibold text-xs uppercase tracking-wider border-r border-blue-500 w-16">Pos.</th>
                            @foreach($maquinas as $maquina)
                                <th class="px-3 py-3 text-white font-semibold text-center border-r border-blue-500 last:border-r-0 min-w-[120px]">
                                    <div class="text-lg font-mono">{{ $maquina->codigo }}</div>
                                    <div class="text-xs font-normal opacity-80">{{ $maquina->nombre }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for($pos = 1; $pos <= $maxPosicion; $pos++)
                            <tr class="fila-posicion border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-3 py-2 text-center font-bold text-gray-500 bg-gray-50 border-r border-gray-200">
                                    {{ $pos }}
                                </td>
                                @foreach($maquinas as $maquina)
                                    @php
                                        $orden = $ordenesPorMaquina[$maquina->id][$pos] ?? null;
                                        $planilla = $orden?->planilla;
                                        $obraModel = $planilla?->obra;
                                        $clienteModel = $planilla?->cliente ?? $obraModel?->cliente;
                                        $obraId = $planilla?->obra_id;
                                        $fechaRaw = $planilla?->getRawOriginal('fecha_estimada_entrega');
                                        $fechaEntrega = $fechaRaw ? \Carbon\Carbon::parse($fechaRaw)->format('Y-m-d') : '';
                                        $fechaDisplay = $fechaRaw ? \Carbon\Carbon::parse($fechaRaw)->format('d/m') : '';
                                    @endphp
                                    <td class="px-2 py-1 border-r border-gray-200 last:border-r-0 celda-planilla"
                                        data-obra-id="{{ $obraId }}"
                                        data-planilla-id="{{ $planilla?->id }}"
                                        data-planilla-codigo="{{ strtolower($planilla?->codigo ?? '') }}"
                                        data-obra-codigo="{{ strtolower($obraModel?->cod_obra ?? '') }}"
                                        data-obra-nombre="{{ strtolower($obraModel?->obra ?? '') }}"
                                        data-empresa-codigo="{{ strtolower($clienteModel?->codigo ?? '') }}"
                                        data-empresa-nombre="{{ strtolower($clienteModel?->empresa ?? '') }}"
                                        data-maquina-codigo="{{ $maquina->codigo }}"
                                        data-posicion="{{ $pos }}"
                                        data-fecha="{{ $fechaEntrega }}">
                                        @if($planilla)
                                            @php
                                                $bgColor = $planilla->revisada ? 'bg-blue-200 hover:bg-blue-300' : 'bg-gray-100 hover:bg-blue-100';
                                                $textColor = $planilla->revisada ? 'text-blue-800' : 'text-blue-700';
                                            @endphp
                                            <a href="{{ route('planillas.show', $planilla->id) }}"
                                               class="block p-2 rounded-lg {{ $bgColor }} transition-colors text-center"
                                               title="{{ $planilla->revisada ? 'Revisada' : 'Pendiente de revisión' }}">
                                                <div class="font-mono font-semibold {{ $textColor }} text-xs">
                                                    {{ $planilla->codigo }}
                                                </div>
                                                <div class="text-[10px] text-gray-500 truncate">
                                                    {{ $planilla->obra?->obra ?? '-' }}
                                                </div>
                                                @if($fechaDisplay)
                                                    <div class="text-[9px] text-gray-400 mt-1">
                                                        {{ $fechaDisplay }}
                                                    </div>
                                                @endif
                                            </a>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Contador de resultados --}}
        <div class="mt-4 text-sm text-gray-600">
            <span id="contador-resultados"></span>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filtroCodObra = document.getElementById('filtro-cod-obra');
            const filtroObra = document.getElementById('filtro-obra');
            const filtroCodEmpresa = document.getElementById('filtro-cod-empresa');
            const filtroEmpresa = document.getElementById('filtro-empresa');
            const filtroPlanillaTexto = document.getElementById('filtro-planilla-texto');
            const filtroPlanilla = document.getElementById('filtro-planilla');
            const btnLimpiar = document.getElementById('btn-limpiar');
            const celdas = document.querySelectorAll('.celda-planilla');
            const contador = document.getElementById('contador-resultados');
            const resumenPanel = document.getElementById('resumen-posiciones');
            const resumenFicha = document.getElementById('resumen-ficha');
            const resumenContenido = document.getElementById('resumen-contenido');
            const resumenSinOrden = document.getElementById('resumen-sin-orden');
            const resumenSinOrdenContenido = document.getElementById('resumen-sin-orden-contenido');

            const filtrosSelect = [filtroCodObra, filtroObra, filtroCodEmpresa, filtroEmpresa, filtroPlanilla];

            function aplicarFiltros() {
                const codObra = filtroCodObra.value;
                const obraId = filtroObra.value;
                const codEmpresa = filtroCodEmpresa.value;
                const empresa = filtroEmpresa.value;
                const planillaTexto = filtroPlanillaTexto.value.toLowerCase().trim();
                const planillaId = filtroPlanilla.value;

                const hayFiltro = codObra || obraId || codEmpresa || empresa || planillaTexto || planillaId;

                let visibles = 0;
                let total = 0;
                let posiciones = [];

                celdas.forEach(celda => {
                    const link = celda.querySelector('a');
                    if (!link) return;

                    total++;

                    const celdaObraId = celda.dataset.obraId;
                    const celdaPlanillaId = celda.dataset.planillaId;
                    const celdaPlanillaCodigo = celda.dataset.planillaCodigo || '';
                    const celdaCodObra = celda.dataset.obraCodigo || '';
                    const celdaCodEmpresa = celda.dataset.empresaCodigo || '';
                    const celdaEmpresa = celda.dataset.empresaNombre || '';
                    const maquinaCodigo = celda.dataset.maquinaCodigo;
                    const posicion = celda.dataset.posicion;

                    let cumple = true;

                    if (codObra && celdaCodObra !== codObra) cumple = false;
                    if (obraId && celdaObraId !== obraId) cumple = false;
                    if (codEmpresa && celdaCodEmpresa !== codEmpresa) cumple = false;
                    if (empresa && celdaEmpresa !== empresa) cumple = false;
                    if (planillaTexto && !celdaPlanillaCodigo.includes(planillaTexto)) cumple = false;
                    if (planillaId && celdaPlanillaId !== planillaId) cumple = false;

                    if (cumple) {
                        link.classList.remove('opacity-20');
                        visibles++;

                        posiciones.push({
                            maquina: maquinaCodigo,
                            posicion: posicion,
                            codigo: link.querySelector('.font-mono')?.textContent?.trim() || ''
                        });

                        if (hayFiltro) {
                            celda.classList.add('!bg-fuchsia-50');
                            link.classList.add('!bg-fuchsia-200', 'ring-2', 'ring-fuchsia-400');
                        } else {
                            celda.classList.remove('!bg-fuchsia-50');
                            link.classList.remove('!bg-fuchsia-200', 'ring-2', 'ring-fuchsia-400');
                        }
                    } else {
                        link.classList.add('opacity-20');
                        celda.classList.remove('!bg-fuchsia-50');
                        link.classList.remove('!bg-fuchsia-200', 'ring-2', 'ring-fuchsia-400');
                    }
                });

                // Actualizar contador
                if (hayFiltro) {
                    contador.textContent = `Mostrando ${visibles} de ${total} planillas`;
                } else {
                    contador.textContent = `${total} planillas en total`;
                }

                // Filtrar planillas sin orden
                let sinOrdenFiltradas = [];
                if (hayFiltro && window.planillasSinOrden) {
                    sinOrdenFiltradas = window.planillasSinOrden.filter(p => {
                        let cumple = true;
                        if (codObra && p.obra_codigo !== codObra) cumple = false;
                        if (obraId && String(p.obra_id) !== obraId) cumple = false;
                        if (codEmpresa && p.empresa_codigo !== codEmpresa) cumple = false;
                        if (empresa && p.empresa_nombre !== empresa) cumple = false;
                        if (planillaTexto && !p.codigo.toLowerCase().includes(planillaTexto)) cumple = false;
                        if (planillaId && String(p.id) !== planillaId) cumple = false;
                        return cumple;
                    });
                }

                // Buscar la planilla filtrada para mostrar ficha
                let planillaFicha = null;
                const todasPlanillas = [...(window.planillasConOrden || []), ...(window.planillasSinOrden || [])];

                if (planillaId) {
                    planillaFicha = todasPlanillas.find(p => String(p.id) === planillaId);
                } else if (planillaTexto) {
                    // Buscar por texto - mostrar la primera que coincida exactamente o la única que contenga
                    const coincidencias = todasPlanillas.filter(p => p.codigo.toLowerCase().includes(planillaTexto));
                    if (coincidencias.length === 1) {
                        planillaFicha = coincidencias[0];
                    } else {
                        const exacta = coincidencias.find(p => p.codigo.toLowerCase() === planillaTexto);
                        if (exacta) planillaFicha = exacta;
                    }
                }

                // Mostrar resumen de posiciones
                if (hayFiltro) {
                    // Mostrar ficha de la planilla si la encontramos
                    if (planillaFicha) {
                        resumenFicha.innerHTML = `
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <a href="/planillas/${planillaFicha.id}" class="text-xl font-bold text-fuchsia-700 hover:underline">
                                            ${planillaFicha.codigo}
                                        </a>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-sm">
                                        <div>
                                            <span class="text-gray-500">Cliente:</span>
                                            <span class="font-medium text-gray-800">${planillaFicha.cliente_nombre || '-'}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Obra:</span>
                                            <span class="font-medium text-gray-800">${planillaFicha.obra_nombre || '-'}</span>
                                        </div>
                                        ${planillaFicha.descripcion ? `
                                        <div class="md:col-span-2">
                                            <span class="text-gray-500">Descripción:</span>
                                            <span class="text-gray-700">${planillaFicha.descripcion}</span>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                        resumenFicha.classList.remove('hidden');
                    } else {
                        resumenFicha.classList.add('hidden');
                    }

                    // Posiciones encontradas
                    if (posiciones.length > 0) {
                        resumenContenido.innerHTML = posiciones.map(p => `
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white border border-fuchsia-300 text-sm">
                                <span class="font-bold text-fuchsia-700">${p.maquina}</span>
                                <span class="text-gray-500">pos.</span>
                                <span class="font-bold text-fuchsia-700">${p.posicion}</span>
                                <span class="text-gray-400 text-xs">(${p.codigo})</span>
                            </span>
                        `).join('');
                    } else {
                        resumenContenido.innerHTML = '<span class="text-gray-500 text-sm">Ninguna posición en las máquinas</span>';
                    }

                    // Planillas sin orden
                    if (sinOrdenFiltradas.length > 0) {
                        resumenSinOrdenContenido.innerHTML = sinOrdenFiltradas.map(p => `
                            <a href="/planillas/${p.id}" class="block p-3 rounded-lg bg-red-50 border border-red-300 text-sm hover:bg-red-100 transition">
                                <div class="font-bold text-red-700">${p.codigo}</div>
                                <div class="text-xs text-gray-600 mt-1">
                                    <span class="font-medium">Cliente:</span> ${p.cliente_nombre || '-'}
                                </div>
                                <div class="text-xs text-gray-600">
                                    <span class="font-medium">Obra:</span> ${p.obra_nombre || '-'}
                                </div>
                                ${p.descripcion ? `<div class="text-xs text-gray-500 mt-1 italic">${p.descripcion}</div>` : ''}
                            </a>
                        `).join('');
                        resumenSinOrden.classList.remove('hidden');
                    } else {
                        resumenSinOrden.classList.add('hidden');
                    }

                    resumenPanel.classList.remove('hidden');
                } else {
                    resumenPanel.classList.add('hidden');
                    resumenFicha.classList.add('hidden');
                    resumenSinOrden.classList.add('hidden');
                }
            }

            filtrosSelect.forEach(f => f.addEventListener('change', aplicarFiltros));
            filtroPlanillaTexto.addEventListener('input', aplicarFiltros);

            btnLimpiar.addEventListener('click', function() {
                filtrosSelect.forEach(f => f.value = '');
                filtroPlanillaTexto.value = '';
                aplicarFiltros();
            });

            // Inicial
            aplicarFiltros();

            // ========== SCROLL CON FLECHAS ==========
            const contenedorScroll = document.querySelector('.overflow-x-auto');
            const scrollStep = 150; // píxeles por cada pulsación

            document.addEventListener('keydown', function(e) {
                // Ignorar si estamos en un input/select
                if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;

                // Buscar el contenedor principal con scroll (puede ser window o un contenedor específico)
                const main = document.querySelector('main') || document.documentElement;

                switch(e.key) {
                    case 'ArrowUp':
                        main.scrollBy(0, -scrollStep);
                        window.scrollBy(0, -scrollStep);
                        e.preventDefault();
                        break;
                    case 'ArrowDown':
                        main.scrollBy(0, scrollStep);
                        window.scrollBy(0, scrollStep);
                        e.preventDefault();
                        break;
                    case 'ArrowLeft':
                        contenedorScroll.scrollBy(-scrollStep, 0);
                        e.preventDefault();
                        break;
                    case 'ArrowRight':
                        contenedorScroll.scrollBy(scrollStep, 0);
                        e.preventDefault();
                        break;
                    case 'Home':
                        contenedorScroll.scrollTo(0, contenedorScroll.scrollTop);
                        e.preventDefault();
                        break;
                    case 'End':
                        contenedorScroll.scrollTo(contenedorScroll.scrollWidth, contenedorScroll.scrollTop);
                        e.preventDefault();
                        break;
                    case 'PageUp':
                        main.scrollBy(0, -window.innerHeight * 0.8);
                        window.scrollBy(0, -window.innerHeight * 0.8);
                        e.preventDefault();
                        break;
                    case 'PageDown':
                        main.scrollBy(0, window.innerHeight * 0.8);
                        window.scrollBy(0, window.innerHeight * 0.8);
                        e.preventDefault();
                        break;
                }
            });
        });
    </script>
</x-app-layout>
