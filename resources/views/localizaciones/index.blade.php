<x-app-layout>
    <x-slot name="title">Mapa de Ubicaciones — Localizar</x-slot>
    <x-menu.localizaciones.menu-localizaciones-index :obras="$cliente->obras" :obra-actual-id="$obra->id ?? null" />

    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        /* Layout principal */
        #toolbar {
            gap: .5rem;
        }

        #zoom-wrapper {
            transform-origin: top left;
        }

        /* Scroll interno para no pintar toda la página infinita */
        #grid-scroll {
            height: calc(100vh - 140px);
            /* ajusta según tu header */
            overflow: auto;
            position: relative;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            background: #fff;
        }

        #grid {
            position: relative;
            width: 75vw;
            max-width: 1400px;
            background: #fff;
            border: 1px solid #e5e7eb;
        }

        .grid-lines {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(to right, rgba(0, 0, 0, .08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(0, 0, 0, .08) 1px, transparent 1px);
            background-repeat: repeat;
            z-index: 1;
        }

        #overlays {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 5;
        }


        .overlay-ping {
            position: absolute;
            background: rgba(255, 165, 0, .28);
            border: 2px solid rgba(255, 165, 0, .9);
            border-radius: 2px;
            box-sizing: border-box;
            animation: pulse 1.1s ease-out 2;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 165, 0, .6);
            }

            70% {
                box-shadow: 0 0 0 12px rgba(255, 165, 0, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(255, 165, 0, 0);
            }
        }

        /* Columna de sectores: navegación rápida */
        #sectores {
            display: grid;
            grid-template-rows: repeat(7, 1fr);
            width: 90px;
            border-left: 1px solid #e5e7eb;
            border-radius: .5rem;
            overflow: hidden;
            user-select: none;
        }

        #sectores button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            font-size: .8rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: filter .15s ease, transform .05s ease;
        }

        #sectores button:active {
            transform: scale(.98);
        }

        #sectores button:hover {
            filter: brightness(.95);
        }

        #sectores button[data-sector="7"] {
            background: #15803d;
            color: #fff;
            border-top: 1px solid #e5e7eb;
        }

        #sectores button[data-sector="6"] {
            background: #16a34a;
            color: #fff;
            border-top: 1px solid #e5e7eb;
        }

        #sectores button[data-sector="5"] {
            background: #22c55e;
            color: #fff;
            border-top: 1px solid #e5e7eb;
        }

        #sectores button[data-sector="4"] {
            background: #4ade80;
            color: #0b3;
            border-top: 1px solid #e5e7eb;
        }

        #sectores button[data-sector="3"] {
            background: #86efac;
            color: #064;
            border-top: 1px solid #e5e7eb;
        }

        #sectores button[data-sector="2"] {
            background: #bbf7d0;
            color: #064;
            border-top: 1px solid #e5e7eb;
        }

        #sectores button[data-sector="1"] {
            background: #dcfce7;
            color: #064;
            border-top: 1px solid #e5e7eb;
        }

        /* Mini HUD */
        .hud {
            position: fixed;
            top: .75rem;
            left: .75rem;
            z-index: 50;
            background: rgba(255, 255, 255, .9);
            padding: .35rem .6rem;
            border-radius: .45rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
            font-weight: 600;
            color: #374151;
        }

        /* Botones / inputs */
        .btn {
            padding: .45rem .7rem;
            border-radius: .4rem;
            border: 1px solid #e5e7eb;
            background: #f3f4f6;
        }

        .btn:hover {
            background: #e5e7eb;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
            border-color: #1d4ed8;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .input {
            border: 1px solid #e5e7eb;
            border-radius: .4rem;
            padding: .45rem .6rem;
            min-width: 260px;
        }

        .pill {
            font-size: .8rem;
            padding: .1rem .5rem;
            border-radius: 1rem;
            background: #eef2ff;
            color: #3730a3;
        }

        /* Capa de máquinas: interactiva (sí recibe eventos) */
        #machines {
            position: absolute;
            inset: 0;
            pointer-events: auto;
        }

        /* Caja visual de la máquina pintada en el grid */
        .machine {
            position: absolute;
            box-sizing: border-box;
            border: 2px solid #0ea5e9;
            /* borde cyan */
            background: rgba(14, 165, 233, .12);
            border-radius: .35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font: 600 12px/1.1 ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
            color: #0c4a6e;
            text-shadow: 0 1px 0 rgba(255, 255, 255, .4);
            padding: 2px;
            cursor: pointer;
            user-select: none;
            transition: transform .06s ease, box-shadow .12s ease;
        }

        .machine:hover {
            box-shadow: 0 2px 10px rgba(2, 132, 199, .25);
        }

        .machine .badge {
            position: absolute;
            top: -10px;
            left: -10px;
            background: #0369a1;
            color: #fff;
            border-radius: .5rem;
            font-size: 10px;
            padding: 2px 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .12);
        }
    </style>

    <div class="w-screen flex flex-col gap-3 p-2">
        <!-- Barra superior -->
        <div id="toolbar" class="flex flex-wrap items-center">
            <input id="codigoPaquete" type="text" class="input" placeholder="Código de paquete (ej. P25090001)">
            <button id="btnLocalizar" class="btn btn-primary">📍 Localizar</button>
            <button id="btnLimpiar" class="btn">🧹 Limpiar</button>
            <div class="flex items-center gap-2 ml-auto">
                <button class="btn" id="zoomOut">➖ Alejar</button>
                <button class="btn" id="zoomIn">➕ Acercar</button>
                <button class="btn" id="zoomReset">🔄 Reset</button>
                <span id="zoomInfo" class="pill">zoom: 1.0×</span>
            </div>
        </div>

        <div class="hud">Posición actual: <span id="posicionActual">—</span></div>

        <!-- Lienzo lógico: grid + overlays + columna sectores -->
        <div id="layout" class="flex">
            <div id="zoom-wrapper">
                <div id="grid-scroll">
                    <div id="grid" data-ancho-m="{{ $dimensiones['ancho'] }}" {{-- metros --}}
                        data-alto-m="{{ $dimensiones['alto'] }}" {{-- metros --}} style="--cols: 1; --rows: 1;">
                        {{-- se sobreescribe en JS --}}
                        <div id="overlays"></div>
                        <div id="machines"></div>

                        <!-- Marcadores (opcionales) -->
                        <div id="sector-markers">
                            <div id="sector7-marker" style="position:absolute;top:0;height:1px;width:1px;"></div>
                            <div id="sector6-marker" style="position:absolute;top:calc(100%/7*1);height:1px;width:1px;">
                            </div>
                            <div id="sector5-marker" style="position:absolute;top:calc(100%/7*2);height:1px;width:1px;">
                            </div>
                            <div id="sector4-marker" style="position:absolute;top:calc(100%/7*3);height:1px;width:1px;">
                            </div>
                            <div id="sector3-marker" style="position:absolute;top:calc(100%/7*4);height:1px;width:1px;">
                            </div>
                            <div id="sector2-marker" style="position:absolute;top:calc(100%/7*5);height:1px;width:1px;">
                            </div>
                            <div id="sector1-marker" style="position:absolute;top:calc(100%/7*6);height:1px;width:1px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navegación rápida por sectores -->
            <div id="sectores" class="hidden md:grid ml-2">
                <button type="button" data-sector="7" title="Ir al Sector 7">Sector 7</button>
                <button type="button" data-sector="6" title="Ir al Sector 6">Sector 6</button>
                <button type="button" data-sector="5" title="Ir al Sector 5">Sector 5</button>
                <button type="button" data-sector="4" title="Ir al Sector 4">Sector 4</button>
                <button type="button" data-sector="3" title="Ir al Sector 3">Sector 3</button>
                <button type="button" data-sector="2" title="Ir al Sector 2">Sector 2</button>
                <button type="button" data-sector="1" title="Ir al Sector 1">Sector 1</button>
            </div>
        </div>
    </div>
    <script>
        // Datos de máquinas por localización (METROS)
        const MACHINES = @json($machines);
        console.log('MACHINES =', MACHINES);
    </script>
    <script>
        /** ====== GRID CORE (común a Index y Create) ====== **/

        const CELL_M = 0.5; // 1 celda = 0.5 m

        // Sistema canónico: (1,1) abajo-izquierda; x→, y↑
        function metersToCells(m) {
            return Math.max(1, Math.round(m / CELL_M));
        }

        function rectMetersToCells({
            mx1,
            my1,
            mx2,
            my2
        }) {
            // cubrir completamente metros → celdas (bordes inclusivos)
            const x1 = Math.max(1, Math.floor((mx1 - 1e-9) / CELL_M) + 1);
            const y1 = Math.max(1, Math.floor((my1 - 1e-9) / CELL_M) + 1);
            const x2 = Math.max(x1, Math.ceil(mx2 / CELL_M));
            const y2 = Math.max(y1, Math.ceil(my2 / CELL_M));
            return {
                x1,
                y1,
                x2,
                y2
            };
        }

        // “Tumbadas”: intercambia ejes X<->Y manteniendo mismo origen (abajo-izq)
        function tumbleRect({
            x1,
            y1,
            x2,
            y2
        }) {
            return {
                x1: y1,
                y1: x1,
                x2: y2,
                y2: x2
            };
        }

        // px helpers (origen arriba-izq en pantalla; convertimos desde nuestro canónico)
        function cellsRectToPx({
            x1,
            y1,
            x2,
            y2
        }, {
            cols,
            rows
        }, gridRect) {
            const cw = gridRect.width / cols;
            const ch = gridRect.height / rows;
            const left = (x1 - 1) * cw;
            const top = (rows - y2) * ch; // y crece hacia arriba
            const width = (x2 - x1 + 1) * cw;
            const height = (y2 - y1 + 1) * ch;
            return {
                left,
                top,
                width,
                height
            };
        }

        // Ocupación rápida
        function occupyRect(set, {
            x1,
            y1,
            x2,
            y2
        }) {
            for (let y = y1; y <= y2; y++)
                for (let x = x1; x <= x2; x++) set.add(`${x},${y}`);
        }

        function isFree(set, {
            x1,
            y1,
            x2,
            y2
        }) {
            for (let y = y1; y <= y2; y++)
                for (let x = x1; x <= x2; x++)
                    if (set.has(`${x},${y}`)) return false;
            return true;
        }

        // Dibujo de líneas (sin celdas)
        function ensureSquareGridSize(gridEl, cols, rows) {
            const w = gridEl.clientWidth;
            const cell = w / cols;
            gridEl.style.height = Math.round(cell * rows) + 'px';
            let lines = gridEl.querySelector('.grid-lines');
            if (!lines) {
                lines = document.createElement('div');
                lines.className = 'grid-lines';
                gridEl.appendChild(lines);
            }
            lines.style.backgroundSize = `${cell}px ${cell}px`;
            return gridEl.getBoundingClientRect();
        }

        // Pinta/repinta un overlay rectangular
        function drawOverlay(overlaysEl, key, pxBox, className = 'area') {
            let el = overlaysEl.querySelector(`[data-key="${key}"]`);
            if (!el) {
                el = document.createElement('div');
                el.dataset.key = key;
                el.className = className;
                overlaysEl.appendChild(el);
            }
            el.style.left = pxBox.left + 'px';
            el.style.top = pxBox.top + 'px';
            el.style.width = pxBox.width + 'px';
            el.style.height = pxBox.height + 'px';
            return el;
        }
        // Etiqueta centrada arriba
        function drawLabel(overlaysEl, key, pxBox, text) {
            let lab = overlaysEl.querySelector(`[data-key="${key}-label"]`);
            if (!lab) {
                lab = document.createElement('div');
                lab.dataset.key = `${key}-label`;
                lab.className = 'maquina-label';
                overlaysEl.appendChild(lab);
            }
            lab.textContent = text;
            lab.style.left = (pxBox.left + pxBox.width / 2) + 'px';
            lab.style.top = (pxBox.top) + 'px';
            return lab;
        }
    </script>

    <script>
        (function IndexGrid() {
            const TUMBLE_IN = false; // <-- pon true si lo que recibes está tumbado
            const grid = document.getElementById('grid');
            const overlays = document.getElementById('overlays');

            const naveAnchoM = parseFloat(grid.dataset.anchoM) || 10;
            const naveAltoM = parseFloat(grid.dataset.altoM) || 10;
            const cols = metersToCells(naveAnchoM);
            const rows = metersToCells(naveAltoM);

            // Tamaño cuadrado + líneas
            let gridRect = ensureSquareGridSize(grid, cols, rows);
            window.addEventListener('resize', () => gridRect = ensureSquareGridSize(grid, cols, rows));

            // Ocupación
            const busy = new Set();

            // PINTAR Localizaciones existentes (del backend)
            const localizaciones = @json($localizacionesMaquinas); // {x1,y1,x2,y2,tipo,nombre} en CELDAS
            localizaciones.forEach((l, i) => {
                const base = TUMBLE_IN ? tumbleRect(l) : l;
                occupyRect(busy, base);
                const box = cellsRectToPx(base, {
                    cols,
                    rows
                }, gridRect);
                const key = `loc-${i}`;
                drawOverlay(overlays, key, box, `area tipo-${l.tipo}`);
                if (l.tipo === 'maquina' && l.nombre) drawLabel(overlays, key, box, l.nombre);
            });

            // HUD (opcional)
            const posHud = document.getElementById('posicionActual');
            grid.addEventListener('mousemove', (e) => {
                const r = grid.getBoundingClientRect();
                const cw = r.width / cols,
                    ch = r.height / rows;
                const cx = Math.min(cols, Math.max(1, Math.floor((e.clientX - r.left) / cw) + 1));
                const cyFromTop = Math.min(rows, Math.max(1, Math.floor((e.clientY - r.top) / ch) + 1));
                const cy = rows - cyFromTop + 1;
                posHud && (posHud.textContent = `(${cx}, ${cy})`);
            });
            grid.addEventListener('mouseleave', () => posHud && (posHud.textContent = '—'));
        })();
    </script>

    <script>
        /* =========================
         * 1) Configuración y helpers
         * ========================= */

        // 2 celdas = 1 metro  →  10 m = 20 celdas (lado celda ~0.5 m)
        // Si quisieras 1 celda = 1 metro, pondrías 1.
        const CELLS_PER_METER = 2;

        // Conversión celdas ↔ metros (entran/salen índices 1..N)
        function cellsToMeters(cellIndex) {
            // celdas 1–2 => metro 1; 3–4 => metro 2; ...
            return Math.ceil(cellIndex / CELLS_PER_METER);
        }

        function metersToCells(meterIndex) {
            // metro 1 ocupa celdas 1–2 → devolvemos la PRIMERA celda de ese bloque
            return (meterIndex - 1) * CELLS_PER_METER + 1;
        }

        /* =========================
         * 2) Refs y dimensiones base
         * ========================= */
        const grid = document.getElementById('grid');
        const overlays = document.getElementById('overlays');
        const gridScroll = document.getElementById('grid-scroll');
        const zoomWrapper = document.getElementById('zoom-wrapper');
        const posHud = document.getElementById('posicionActual');

        // Metros de la nave (vienen del dataset de Blade)
        const metrosAncho = parseFloat(grid.dataset.anchoM) || 10;
        const metrosAlto = parseFloat(grid.dataset.altoM) || 10;

        // Columnas/filas calculadas con el factor  (siempre ≥ 1)
        let columnas = Math.max(1, Math.round(metrosAncho * CELLS_PER_METER));
        let filas = Math.max(1, Math.round(metrosAlto * CELLS_PER_METER));

        // Aplicar a las variables CSS que dibujan las líneas de la cuadrícula
        grid.style.setProperty('--cols', columnas);
        grid.style.setProperty('--rows', filas);

        /* ==========================================
         * 3) Celdas cuadradas: ajustar altura en px
         * ========================================== */
        function ensureSquareCells() {
            // Usamos el ancho real para calcular altura proporcional
            const widthPx = grid.clientWidth;
            if (!columnas || !filas || widthPx <= 0) return;

            const cellW = widthPx / columnas; // ancho de UNA celda
            const heightPx = Math.round(cellW * filas); // alto total = alto celda * filas

            grid.style.height = heightPx + 'px';

            if (MACHINES && MACHINES.length) drawMachines();
        }
        window.addEventListener('load', ensureSquareCells);
        window.addEventListener('resize', ensureSquareCells);

        // Si cambias de obra/máquina en caliente y conoces sus METROS:
        function refreshFromMeters(anchoM, altoM) {
            columnas = Math.max(1, Math.round(anchoM * CELLS_PER_METER));
            filas = Math.max(1, Math.round(altoM * CELLS_PER_METER));
            grid.style.setProperty('--cols', columnas);
            grid.style.setProperty('--rows', filas);
            ensureSquareCells();

            resetMachines();
        }

        /* ==============
         * 4) Control zoom
         * ============== */
        let zoom = 1;

        function applyZoom() {
            zoomWrapper.style.transform = `scale(${zoom})`;
            document.getElementById('zoomInfo').textContent = `zoom: ${zoom.toFixed(1)}×`;
        }
        document.getElementById('zoomIn').addEventListener('click', () => {
            zoom = Math.min(3, zoom + 0.1);
            applyZoom();
        });
        document.getElementById('zoomOut').addEventListener('click', () => {
            zoom = Math.max(0.3, zoom - 0.1);
            applyZoom();
        });
        document.getElementById('zoomReset').addEventListener('click', () => {
            zoom = 1;
            applyZoom();
        });

        /* =========================================
         * 5) Coordenadas ratón -> HUD (en METROS)
         * ========================================= */
        function getMouseCell(e) {
            const rect = grid.getBoundingClientRect();
            const relX = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
            const relY = Math.max(0, Math.min(e.clientY - rect.top, rect.height));

            const cellW = rect.width / columnas;
            const cellH = rect.height / filas;

            // Índices de CELDA (1..N)
            const xCell = Math.floor(relX / cellW) + 1;
            const yCellFromTop = Math.floor(relY / cellH) + 1;
            const yCell = filas - yCellFromTop + 1; // tu eje Y crece hacia ARRIBA

            // Índices de POSICIÓN en METROS (1 cada 2 celdas)
            const xPos = cellsToMeters(xCell);
            const yPos = cellsToMeters(yCell);

            return {
                xCell,
                yCell,
                xPos,
                yPos
            };
        }

        grid.addEventListener('mousemove', (e) => {
            const {
                xPos,
                yPos
            } = getMouseCell(e);
            posHud.textContent = (xPos >= 1 && yPos >= 1) ? `(${xPos}, ${yPos})` : '—';
        });
        grid.addEventListener('mouseleave', () => posHud.textContent = '—');

        /* =========================================
         * 6) Overlay de localización (rect en celdas)
         * ========================================= */
        let pingEl = null;

        function clearOverlay() {
            if (pingEl) {
                pingEl.remove();
                pingEl = null;
            }
        }

        function rectToOverlay({
            x1,
            y1,
            x2,
            y2
        }) {
            clearOverlay();

            const rect = grid.getBoundingClientRect();
            const cellW = rect.width / columnas;
            const cellH = rect.height / filas;

            const left = (x1 - 1) * cellW;
            const top = (filas - y2) * cellH; // y2 medido desde abajo
            const width = (x2 - x1 + 1) * cellW;
            const height = (y2 - y1 + 1) * cellH;

            pingEl = document.createElement('div');
            pingEl.className = 'overlay-ping';
            Object.assign(pingEl.style, {
                left: left + 'px',
                top: top + 'px',
                width: width + 'px',
                height: height + 'px'
            });
            overlays.appendChild(pingEl);

            centerScroll(left + width / 2, top + height / 2);
        }

        // Rectángulo en METROS → convertir a celdas y pintar
        function metersRectToOverlay({
            mx1,
            my1,
            mx2,
            my2
        }) {
            rectToOverlay({
                // inicio del metro → primera celda del bloque
                x1: metersToCells(mx1),
                y1: metersToCells(my1),
                // final del metro → última celda del bloque
                x2: metersToCells(mx2 + 1) - 1,
                y2: metersToCells(my2 + 1) - 1
            });
        }

        function centerScroll(targetX, targetY) {
            const scrollLeft = targetX - gridScroll.clientWidth / 2;
            const scrollTop = targetY - gridScroll.clientHeight / 2;
            gridScroll.scrollTo({
                left: Math.max(0, scrollLeft),
                top: Math.max(0, scrollTop),
                behavior: 'smooth'
            });
        }

        /* ===========================
         * 7) Localizar por código (METROS)
         * =========================== */
        document.getElementById('btnLocalizar').addEventListener('click', localizar);
        document.getElementById('codigoPaquete').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') localizar();
        });
        document.getElementById('btnLimpiar').addEventListener('click', clearOverlay);

        async function localizar() {
            const codigo = document.getElementById('codigoPaquete').value.trim();
            if (!codigo) return alert('Introduce un código de paquete');

            try {
                const res = await fetch(`/localizaciones-paquetes/${encodeURIComponent(codigo)}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error(await res.text() || 'No se pudo localizar el paquete.');
                const data = await res.json();

                // Preferimos METROS: { mx1, my1, mx2, my2 }
                if (data && data.mx1 != null) {
                    return metersRectToOverlay({
                        mx1: +data.mx1,
                        my1: +data.my1,
                        mx2: +data.mx2,
                        my2: +data.my2
                    });
                }

                // Back-compat si el backend todavía devuelve celdas: { x1, y1, x2, y2 }
                if (data && data.x1 != null) {
                    return rectToOverlay({
                        x1: +data.x1,
                        y1: +data.y1,
                        x2: +data.x2,
                        y2: +data.y2
                    });
                }

                throw new Error('Código sin coordenadas válidas.');
            } catch (err) {
                clearOverlay();
                alert(err.message || 'Error inesperado al localizar.');
            }
        }

        /* =========================
         * 8) Navegación por sectores
         * ========================= */
        document.getElementById('sectores')?.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-sector]');
            if (!btn) return;
            const n = Number(btn.dataset.sector); // 1..7
            scrollToSector(n);
        });

        function scrollToSector(sector) {
            // sector 7 arriba; sector 1 abajo (7 bandas visuales)
            const rect = grid.getBoundingClientRect();
            const sectorHeight = rect.height / 7;
            const indexFromTop = 7 - sector; // 0..6
            const targetY = indexFromTop * sectorHeight;
            centerScroll(rect.width / 2, targetY);
        }

        /* ===== Init ===== */
        applyZoom();
        ensureSquareCells();

        // ========= PINTAR MÁQUINAS EN EL GRID =========
        const machinesLayer = document.getElementById('machines');
        let machineNodes = []; // referencias DOM para redibujar en resize

        // Convierte un rectángulo EN METROS a coordenadas en píxeles sobre el grid
        function metersRectToPixelBox({
            mx1,
            my1,
            mx2,
            my2
        }) {
            // 1) metros -> celdas (bloque completo)
            const x1 = metersToCells(mx1);
            const y1 = metersToCells(my1);
            const x2 = metersToCells(mx2 + 1) - 1;
            const y2 = metersToCells(my2 + 1) - 1;

            // 2) celdas -> px con el tamaño actual del grid
            const rect = grid.getBoundingClientRect();
            const cellW = rect.width / columnas;
            const cellH = rect.height / filas;

            const left = (x1 - 1) * cellW;
            const top = (filas - y2) * cellH; // y desde abajo
            const width = (x2 - x1 + 1) * cellW;
            const height = (y2 - y1 + 1) * cellH;

            return {
                left,
                top,
                width,
                height
            };
        }

        // Crea el nodo DOM de una máquina y lo posiciona
        function createMachineNode(machine) {
            const el = document.createElement('div');
            el.className = 'machine';
            el.title = machine.label;

            // Badge con el código
            const badge = document.createElement('div');
            badge.className = 'badge';
            badge.textContent = machine.code;
            el.appendChild(badge);

            // Texto centrado (nombre corto o código)
            const span = document.createElement('div');
            span.textContent = machine.label;
            el.appendChild(span);

            machinesLayer.appendChild(el);
            return el;
        }

        // Posiciona (o reposiciona) un nodo existente según metros
        function positionMachineNode(el, machine) {
            const box = metersRectToPixelBox(machine);
            el.style.left = box.left + 'px';
            el.style.top = box.top + 'px';
            el.style.width = box.width + 'px';
            el.style.height = box.height + 'px';
        }

        // Dibuja todas las máquinas (crear si no existen, o reposicionar si ya hay)
        function drawMachines() {
            // Si no hay nodos aún, crearlos
            if (machineNodes.length === 0) {
                MACHINES.forEach(m => {
                    const node = createMachineNode(m);
                    machineNodes.push({
                        node,
                        data: m
                    });
                });
            }
            // Posicionar según tamaño actual del grid
            machineNodes.forEach(({
                node,
                data
            }) => positionMachineNode(node, data));
        }

        // Si cambian dimensiones en caliente, limpiar y repintar
        function resetMachines() {
            machineNodes.forEach(({
                node
            }) => node.remove());
            machineNodes = [];
            drawMachines();
        }
    </script>
</x-app-layout>
