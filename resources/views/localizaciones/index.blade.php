<x-app-layout>
    <x-slot name="title">Mapa de Ubicaciones</x-slot>

    <x-page-header
        title="Mapa de Localizaciones"
        subtitle="Visualización de la distribución de la planta"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>'
    />

    <style>
        /* Variables de tema */
        :root {
            --loc-header-bg: #ffffff;
            --loc-header-text: #111827;
            --loc-header-muted: #6b7280;
            --loc-btn-bg: #f3f4f6;
            --loc-accent: #3b82f6;
        }

        :root.dark {
            --loc-header-bg: #1f2937;
            --loc-header-text: #f9fafb;
            --loc-header-muted: #9ca3af;
            --loc-btn-bg: #374151;
            --loc-accent: #60a5fa;
        }

        .loc-container {
            min-height: 100%;
        }

        /* Transiciones suaves para cambio de nave */
        .map-transition-container {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .map-transition-container.loading {
            opacity: 0.5;
            pointer-events: none;
        }

        .map-transition-container.fade-out {
            opacity: 0;
            transform: scale(0.98);
        }

        .map-transition-container.fade-in {
            opacity: 1;
            transform: scale(1);
        }

        /* Indicador de carga */
        .map-loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        :root.dark .map-loading-overlay {
            background: rgba(31, 41, 55, 0.8);
        }

        .map-loading-overlay.visible {
            opacity: 1;
            pointer-events: auto;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: var(--loc-accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Transicion de elementos individuales */
        .loc-existente {
            transition: left 0.3s ease, top 0.3s ease, width 0.3s ease, height 0.3s ease, opacity 0.3s ease;
        }

        .loc-existente.entering {
            opacity: 0;
            transform: scale(0.8);
        }

        .loc-existente.visible {
            opacity: 1;
            transform: scale(1);
        }

        /* Header */
        .loc-header {
            background: var(--loc-header-bg);
            padding: 10px 16px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .loc-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--loc-header-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .loc-title-icon {
            width: 26px;
            height: 26px;
            background: var(--loc-accent);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        /* Filtros */
        .loc-filters {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-label {
            font-size: 13px;
            color: var(--loc-header-muted);
        }

        .filter-select {
            background: var(--loc-btn-bg);
            border: none;
            border-radius: 5px;
            padding: 6px 10px;
            font-size: 13px;
            color: var(--loc-header-text);
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--loc-accent);
        }

        /* Botones de orientacion */
        .orient-group {
            display: flex;
            background: var(--loc-btn-bg);
            border-radius: 5px;
            padding: 2px;
        }

        .orient-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            color: var(--loc-header-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .orient-btn.active {
            background: var(--loc-header-bg);
            color: var(--loc-header-text);
        }

        .orient-btn svg {
            width: 14px;
            height: 14px;
        }

        /* Boton Editor */
        .btn-editor {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background: var(--loc-accent);
            color: white;
            border-radius: 5px;
            font-size: 13px;
        }

        .btn-editor:hover {
            opacity: 0.9;
        }

        .btn-editor svg {
            width: 14px;
            height: 14px;
        }

        /* Info derecha */
        .header-right {
            margin-left: auto;
        }

        .info-badge {
            font-size: 11px;
            color: var(--loc-header-muted);
            font-family: monospace;
        }

        /* Leyenda del mapa */
        .map-info {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 16px;
            background: rgba(0,0,0,0.6);
            padding: 6px 12px;
            border-radius: 6px;
            z-index: 5;
        }

        .map-info-item {
            font-size: 11px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .loc-header {
                padding: 12px;
                gap: 12px;
            }

            .loc-title {
                font-size: 16px;
                width: 100%;
            }

            .header-right {
                width: 100%;
                justify-content: space-between;
            }

            .filter-select {
                min-width: 120px;
            }
        }
    </style>

    <div class="loc-container" id="loc-container">
        {{-- Header --}}
        <header class="loc-header">
            <div class="loc-title">
                <div class="loc-title-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                </div>
                <span>Mapa de Localizaciones</span>
            </div>

            <div class="loc-filters">
                {{-- Filtro de Naves --}}
                <div class="filter-group">
                    <label class="filter-label">Nave:</label>
                    <select id="nave-selector" class="filter-select">
                        @foreach($obras as $obra)
                            <option value="{{ $obra->id }}"
                                    data-ancho="{{ $obra->ancho_m ?? 22 }}"
                                    data-largo="{{ $obra->largo_m ?? 115 }}"
                                    {{ ($obraActualId == $obra->id) ? 'selected' : '' }}>
                                {{ $obra->obra }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Orientacion --}}
                <div class="orient-group">
                    <button type="button" id="orient-horizontal" class="orient-btn {{ !$ctx['estaGirado'] ? 'active' : '' }}" data-orientacion="horizontal">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        Horizontal
                    </button>
                    <button type="button" id="orient-vertical" class="orient-btn {{ $ctx['estaGirado'] ? 'active' : '' }}" data-orientacion="vertical">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8"/>
                        </svg>
                        Vertical
                    </button>
                </div>

                {{-- Editor Visual --}}
                <a href="{{ route('localizaciones.editarMapa', ['obra' => $obraActualId]) }}" id="btn-editar-mapa" class="btn-editor">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar Mapa
                </a>
            </div>

            <div class="header-right">
                <div class="info-badge">
                    {{ $dimensiones['ancho'] }}×{{ $dimensiones['largo'] }}m
                    &bull;
                    {{ $columnasVista }}×{{ $filasVista }} celdas
                </div>
            </div>
        </header>

        {{-- Mapa --}}
        <div class="map-transition-container" id="map-transition-container" style="position: relative;">
            <div class="map-loading-overlay" id="map-loading-overlay">
                <div class="loading-spinner"></div>
            </div>

            <div id="escenario-cuadricula" class="{{ $ctx['estaGirado'] ? 'orient-vertical' : 'orient-horizontal' }}"
                data-nave-id="{{ $obraActualId ?? ($ctx['naveId'] ?? '') }}"
                style="--cols: {{ $ctx['estaGirado'] ? $ctx['columnasReales'] : $ctx['filasReales'] }};
                       --rows: {{ $ctx['estaGirado'] ? $ctx['filasReales'] : $ctx['columnasReales'] }};">

                <div id="cuadricula" aria-label="Cuadricula de la nave">
                    {{-- Maquinas --}}
                    @foreach ($localizacionesConMaquina as $loc)
                        <div class="loc-existente loc-maquina"
                             data-id="{{ $loc['id'] }}"
                             data-x1="{{ $loc['x1'] }}" data-y1="{{ $loc['y1'] }}"
                             data-x2="{{ $loc['x2'] }}" data-y2="{{ $loc['y2'] }}"
                             data-nombre="{{ $loc['nombre'] }}"
                             title="{{ $loc['nombre'] }}">
                            <span class="loc-label">{{ $loc['nombre'] }}</span>
                        </div>
                    @endforeach

                    {{-- Zonas --}}
                    @foreach ($localizacionesZonas as $loc)
                        @php $tipo = str_replace('-', '_', $loc['tipo'] ?? 'transitable'); @endphp
                        <div class="loc-existente loc-zona tipo-{{ $tipo }}"
                             data-id="{{ $loc['id'] }}"
                             data-x1="{{ $loc['x1'] }}" data-y1="{{ $loc['y1'] }}"
                             data-x2="{{ $loc['x2'] }}" data-y2="{{ $loc['y2'] }}"
                             data-nombre="{{ $loc['nombre'] }}"
                             title="{{ $loc['nombre'] }}">
                            <span class="loc-label">{{ $loc['nombre'] }}</span>
                        </div>
                    @endforeach

                    {{-- Leyenda --}}
                    <div class="map-info">
                        <div class="map-info-item">
                            <span style="width:10px;height:10px;background:#3b82f6;border-radius:2px;"></span>
                            <span>Maquinas</span>
                        </div>
                        <div class="map-info-item">
                            <span style="width:10px;height:10px;background:#10b981;border-radius:2px;"></span>
                            <span>Almacenamiento</span>
                        </div>
                        <div class="map-info-item">
                            <span style="width:10px;height:10px;background:#6b7280;border-radius:2px;"></span>
                            <span>Pasillos</span>
                        </div>
                        <div class="map-info-item">
                            <span style="width:10px;height:10px;background:#f59e0b;border-radius:2px;"></span>
                            <span>Carga/Descarga</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CSS externo --}}
    <link rel="stylesheet" href="{{ asset('css/localizaciones/styleLocIndex.css') }}">

    {{-- Contexto --}}
    <script>
        window.__LOC_CTX__ = @json($ctx);
    </script>

    {{-- JS: Render con soporte AJAX --}}
    <script>
        (() => {
            // Estado global del mapa
            let ctx = window.__LOC_CTX__;
            let W = ctx.columnasReales;
            let H = ctx.filasReales;
            let isVertical = !!ctx.estaGirado;
            let viewCols = isVertical ? W : H;
            let viewRows = isVertical ? H : W;
            let currentNaveId = ctx.naveId;
            let currentOrientacion = ctx.orientacion || 'horizontal';

            const escenario = document.getElementById('escenario-cuadricula');
            const grid = document.getElementById('cuadricula');
            const transitionContainer = document.getElementById('map-transition-container');
            const loadingOverlay = document.getElementById('map-loading-overlay');
            const naveSelector = document.getElementById('nave-selector');
            const btnHorizontal = document.getElementById('orient-horizontal');
            const btnVertical = document.getElementById('orient-vertical');
            const btnEditarMapa = document.getElementById('btn-editar-mapa');
            const infoBadge = document.querySelector('.info-badge');

            // URL base para la API
            const apiBaseUrl = '{{ url("/api/localizaciones-index") }}';
            const editorBaseUrl = '{{ route("localizaciones.editarMapa") }}';

            // Funciones de mapa
            function getCeldaPx() {
                const v = getComputedStyle(grid).getPropertyValue('--tam-celda').trim();
                const n = parseInt(v, 10);
                return Number.isFinite(n) && n > 0 ? n : 8;
            }

            function mapPointToView(x, y) {
                if (isVertical) return { x, y: (H - y + 1) };
                return { x: y, y: x };
            }

            function realToViewRect(x1r, y1r, x2r, y2r) {
                const x1 = Math.min(x1r, x2r), x2 = Math.max(x1r, x2r);
                const y1 = Math.min(y1r, y2r), y2 = Math.max(y1r, y2r);

                const corners = [
                    mapPointToView(x1, y1),
                    mapPointToView(x2, y1),
                    mapPointToView(x1, y2),
                    mapPointToView(x2, y2)
                ];

                const xs = corners.map(p => p.x);
                const ys = corners.map(p => p.y);

                return {
                    x: Math.min(...xs),
                    y: Math.min(...ys),
                    w: Math.max(...xs) - Math.min(...xs) + 1,
                    h: Math.max(...ys) - Math.min(...ys) + 1
                };
            }

            function renderExistentes() {
                const celdaPx = getCeldaPx();
                document.querySelectorAll('.loc-existente').forEach(el => {
                    const { x, y, w, h } = realToViewRect(
                        +el.dataset.x1, +el.dataset.y1, +el.dataset.x2, +el.dataset.y2
                    );
                    el.style.left = ((x - 1) * celdaPx) + 'px';
                    el.style.top = ((y - 1) * celdaPx) + 'px';
                    el.style.width = (w * celdaPx) + 'px';
                    el.style.height = (h * celdaPx) + 'px';
                });
            }

            function ajustarTamCelda() {
                const anchoDisp = escenario.clientWidth - 40;
                const altoDisp = window.innerHeight - escenario.getBoundingClientRect().top - 60;

                const tamPorAncho = Math.floor(anchoDisp / viewCols);
                const tamPorAlto = Math.floor(altoDisp / viewRows);
                const tamCelda = Math.max(4, Math.min(tamPorAncho, tamPorAlto));

                grid.style.setProperty('--tam-celda', tamCelda + 'px');
                grid.style.width = (tamCelda * viewCols) + 'px';
                grid.style.height = (tamCelda * viewRows) + 'px';

                renderExistentes();
            }

            // Mostrar/ocultar carga
            function showLoading() {
                loadingOverlay.classList.add('visible');
                transitionContainer.classList.add('loading');
            }

            function hideLoading() {
                loadingOverlay.classList.remove('visible');
                transitionContainer.classList.remove('loading');
            }

            // Actualizar botones de orientación
            function updateOrientationButtons(orientacion) {
                btnHorizontal.classList.toggle('active', orientacion === 'horizontal');
                btnVertical.classList.toggle('active', orientacion === 'vertical');
            }

            // Actualizar link del editor
            function updateEditorLink(naveId) {
                btnEditarMapa.href = `${editorBaseUrl}?obra=${naveId}`;
            }

            // Crear elemento de localización
            function createLocElement(loc, tipo) {
                const div = document.createElement('div');
                const tipoClass = tipo === 'maquina' ? 'loc-maquina' : `loc-zona tipo-${loc.tipo || 'transitable'}`;
                div.className = `loc-existente ${tipoClass} entering`;
                div.dataset.id = loc.id;
                div.dataset.x1 = loc.x1;
                div.dataset.y1 = loc.y1;
                div.dataset.x2 = loc.x2;
                div.dataset.y2 = loc.y2;
                div.dataset.nombre = loc.nombre;
                div.title = loc.nombre;
                div.innerHTML = `<span class="loc-label">${loc.nombre}</span>`;

                // Animación de entrada
                requestAnimationFrame(() => {
                    div.classList.remove('entering');
                    div.classList.add('visible');
                });

                return div;
            }

            // Renderizar nuevos datos del mapa
            function renderMapData(data) {
                // Actualizar contexto
                ctx = data.ctx;
                W = ctx.columnasReales;
                H = ctx.filasReales;
                isVertical = !!ctx.estaGirado;
                viewCols = isVertical ? W : H;
                viewRows = isVertical ? H : W;
                currentNaveId = ctx.naveId;
                currentOrientacion = ctx.orientacion || 'horizontal';

                // Actualizar CSS variables del escenario
                escenario.style.setProperty('--cols', ctx.estaGirado ? ctx.columnasReales : ctx.filasReales);
                escenario.style.setProperty('--rows', ctx.estaGirado ? ctx.filasReales : ctx.columnasReales);
                escenario.dataset.naveId = ctx.naveId;
                escenario.className = ctx.estaGirado ? 'orient-vertical' : 'orient-horizontal';

                // Eliminar localizaciones existentes con animación
                const existingLocs = grid.querySelectorAll('.loc-existente');
                existingLocs.forEach(el => {
                    el.style.opacity = '0';
                    el.style.transform = 'scale(0.8)';
                });

                // Esperar la animación de salida y luego limpiar
                setTimeout(() => {
                    existingLocs.forEach(el => el.remove());

                    // Crear nuevas máquinas
                    data.localizacionesConMaquina.forEach(loc => {
                        const el = createLocElement(loc, 'maquina');
                        grid.insertBefore(el, grid.querySelector('.map-info'));
                    });

                    // Crear nuevas zonas
                    data.localizacionesZonas.forEach(loc => {
                        const el = createLocElement(loc, 'zona');
                        grid.insertBefore(el, grid.querySelector('.map-info'));
                    });

                    // Actualizar dimensiones y renderizar
                    ajustarTamCelda();

                    // Actualizar info badge
                    if (infoBadge) {
                        infoBadge.textContent = `${data.dimensiones.ancho}×${data.dimensiones.largo}m • ${data.columnasVista}×${data.filasVista} celdas`;
                    }

                    // Actualizar contexto global
                    window.__LOC_CTX__ = ctx;

                    hideLoading();
                }, 200);
            }

            // Cargar datos de la nave via AJAX
            async function loadNaveData(naveId, orientacion) {
                showLoading();

                try {
                    const url = `${apiBaseUrl}/${naveId}?orientacion=${orientacion}`;
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Error al cargar los datos');
                    }

                    const result = await response.json();

                    if (result.success) {
                        renderMapData(result.data);
                        updateOrientationButtons(orientacion);
                        updateEditorLink(naveId);

                        // Actualizar URL sin recargar
                        const newUrl = new URL(window.location);
                        newUrl.searchParams.set('obra', naveId);
                        newUrl.searchParams.set('orientacion', orientacion);
                        window.history.pushState({}, '', newUrl);
                    } else {
                        throw new Error(result.message || 'Error desconocido');
                    }
                } catch (error) {
                    console.error('Error cargando nave:', error);
                    hideLoading();
                    // Mostrar mensaje de error
                    alert('Error al cargar los datos de la nave. Por favor, recarga la página.');
                }
            }

            // Event Listeners
            naveSelector.addEventListener('change', (e) => {
                const naveId = e.target.value;
                loadNaveData(naveId, currentOrientacion);
            });

            btnHorizontal.addEventListener('click', () => {
                if (currentOrientacion !== 'horizontal') {
                    loadNaveData(currentNaveId, 'horizontal');
                }
            });

            btnVertical.addEventListener('click', () => {
                if (currentOrientacion !== 'vertical') {
                    loadNaveData(currentNaveId, 'vertical');
                }
            });

            // Manejar navegación del historial
            window.addEventListener('popstate', () => {
                const params = new URLSearchParams(window.location.search);
                const obra = params.get('obra') || currentNaveId;
                const orientacion = params.get('orientacion') || 'horizontal';

                naveSelector.value = obra;
                loadNaveData(obra, orientacion);
            });

            // Init
            ajustarTamCelda();

            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(ajustarTamCelda, 100);
            }, { passive: true });
        })();
    </script>

</x-app-layout>
