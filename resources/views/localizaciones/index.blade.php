<x-app-layout>
    <x-slot name="title">Mapa de Ubicaciones</x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mapa de {{ $dimensiones['obra'] ?? 'localizaciones' }}
        </h2>
    </x-slot>

    {{-- Menús (ajusta a tus componentes) --}}
    <x-menu.localizaciones.menu-localizaciones-vistas :obra-actual-id="$obraActualId" route-index="localizaciones.index"
        route-create="localizaciones.create" />
    <x-menu.localizaciones.menu-localizaciones-naves :obras="$obras" :obra-actual-id="$obraActualId" />

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

        /* Scroll interno */
        #grid-scroll {
            height: calc(100vh - 140px);
            overflow: auto;
            position: relative;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            background: #fff;
        }

        /* Lienzo principal */
        #grid {
            position: relative;
            width: 75vw;
            max-width: 1400px;
            background: #fff;
            border: 1px solid #e5e7eb;
        }

        /* Líneas de rejilla */
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

        /* Capas */
        #overlays {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 3;
        }

        #machines {
            position: absolute;
            inset: 0;
            pointer-events: auto;
            z-index: 4;
        }

        /* Overlay de localización */
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

        /* Navegación por sectores (derecha) */
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

        /* Caja visual de máquina */
        .machine {
            position: absolute;
            box-sizing: border-box;
            border: 2px solid #0ea5e9;
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
                    <div id="grid" data-ancho-m="{{ $dimensiones['ancho'] }}"
                        data-alto-m="{{ $dimensiones['alto'] }}" style="--cols:1; --rows:1;">
                        <div class="grid-lines"></div>
                        <div id="overlays"></div>
                        <div id="machines"></div>
                    </div>
                </div>
            </div>

            <!-- Navegación rápida por sectores -->
            <div id="sectores" class="hidden md:grid ml-2">
                <button type="button" data-sector="7">Sector 7</button>
                <button type="button" data-sector="6">Sector 6</button>
                <button type="button" data-sector="5">Sector 5</button>
                <button type="button" data-sector="4">Sector 4</button>
                <button type="button" data-sector="3">Sector 3</button>
                <button type="button" data-sector="2">Sector 2</button>
                <button type="button" data-sector="1">Sector 1</button>
            </div>
        </div>
    </div>

    <script>
        // Datos de máquinas por localización (METROS)
        const MACHINES = @json($machines);
        console.log('MACHINES =', MACHINES);
    </script>

    <script>
        /* =========================
         * 1) Configuración y helpers
         * ========================= */

        // 2 celdas = 1 metro → 10 m = 20 celdas
        const CELLS_PER_METER = 2;

        function cellsToMeters(cellIndex) {
            return Math.ceil(cellIndex / CELLS_PER_METER);
        }

        function metersToCells(meterIndex) {
            // metro 1 → celdas 1–2 (devuelve la primera celda del bloque)
            return (meterIndex - 1) * CELLS_PER_METER + 1;
        }

        /* =========================
         * 2) Refs y dimensiones base
         * ========================= */
        const grid = document.getElementById('grid');
        const overlays = document.getElementById('overlays');
        const machinesEl = document.getElementById('machines');
        const gridScroll = document.getElementById('grid-scroll');
        const zoomWrapper = document.getElementById('zoom-wrapper');
        const posHud = document.getElementById('posicionActual');

        const metrosAncho = parseFloat(grid.dataset.anchoM) || 10;
        const metrosAlto = parseFloat(grid.dataset.altoM) || 10;

        let columnas = Math.max(1, Math.round(metrosAncho * CELLS_PER_METER));
        let filas = Math.max(1, Math.round(metrosAlto * CELLS_PER_METER));

        grid.style.setProperty('--cols', columnas);
        grid.style.setProperty('--rows', filas);

        /* ==========================================
         * 3) Celdas cuadradas: ajustar altura en px
         * ========================================== */
        function ensureSquareCells() {
            const widthPx = grid.clientWidth;
            if (!columnas || !filas || widthPx <= 0) return;

            const cellW = widthPx / columnas;
            const heightPx = Math.round(cellW * filas);
            grid.style.height = heightPx + 'px';

            drawMachines(); // <- repinta máquinas con el tamaño real
        }
        window.addEventListener('load', ensureSquareCells);
        window.addEventListener('resize', ensureSquareCells);

        // Cambiar dimensiones en caliente (si hiciera falta)
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
        applyZoom();

        /* =========================================
         * 5) Coordenadas ratón -> HUD (en METROS)
         * ========================================= */
        function getMouseCell(e) {
            const rect = grid.getBoundingClientRect();
            const relX = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
            const relY = Math.max(0, Math.min(e.clientY - rect.top, rect.height));

            const cellW = rect.width / columnas;
            const cellH = rect.height / filas;

            const xCell = Math.floor(relX / cellW) + 1;
            const yCellFromTop = Math.floor(relY / cellH) + 1;
            const yCell = filas - yCellFromTop + 1; // eje y hacia arriba

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

        /* =========================
         * 6) Overlay de localización
         * ========================= */
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
            const top = (filas - y2) * cellH;
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

        function metersRectToOverlay({
            mx1,
            my1,
            mx2,
            my2
        }) {
            rectToOverlay({
                x1: metersToCells(mx1),
                y1: metersToCells(my1),
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

                if (data && data.mx1 != null) return metersRectToOverlay({
                    mx1: +data.mx1,
                    my1: +data.my1,
                    mx2: +data.mx2,
                    my2: +data.my2
                });
                if (data && data.x1 != null) return rectToOverlay({
                    x1: +data.x1,
                    y1: +data.y1,
                    x2: +data.x2,
                    y2: +data.y2
                });

                throw new Error('Código sin coordenadas válidas.');
            } catch (err) {
                clearOverlay();
                alert(err.message || 'Error inesperado al localizar.');
            }
        }

        /* =========================
         * 7) Navegación por sectores
         * ========================= */
        document.getElementById('sectores')?.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-sector]');
            if (!btn) return;
            const n = Number(btn.dataset.sector); // 1..7
            scrollToSector(n);
        });

        function scrollToSector(sector) {
            // sector 7 arriba; sector 1 abajo (7 bandas)
            const rect = grid.getBoundingClientRect();
            const sectorHeight = rect.height / 7;
            const indexFromTop = 7 - sector; // 0..6
            const targetY = indexFromTop * sectorHeight;
            centerScroll(rect.width / 2, targetY);
        }

        /* ==============================
         * 8) Pintar máquinas en el grid
         * ============================== */
        let machineNodes = [];

        function metersRectToPixelBox({
            mx1,
            my1,
            mx2,
            my2
        }) {
            const x1 = metersToCells(mx1);
            const y1 = metersToCells(my1);
            const x2 = metersToCells(mx2 + 1) - 1;
            const y2 = metersToCells(my2 + 1) - 1;

            const rect = grid.getBoundingClientRect();
            const cellW = rect.width / columnas;
            const cellH = rect.height / filas;

            const left = (x1 - 1) * cellW;
            const top = (filas - y2) * cellH;
            const width = (x2 - x1 + 1) * cellW;
            const height = (y2 - y1 + 1) * cellH;
            return {
                left,
                top,
                width,
                height
            };
        }

        function createMachineNode(machine) {
            const el = document.createElement('div');
            el.className = 'machine';
            el.title = machine.label;

            const badge = document.createElement('div');
            badge.className = 'badge';
            badge.textContent = machine.code ?? '—';
            el.appendChild(badge);

            const span = document.createElement('div');
            span.textContent = machine.label ?? machine.code ?? 'Máquina';
            el.appendChild(span);

            machinesEl.appendChild(el);
            return el;
        }

        function positionMachineNode(el, machine) {
            const box = metersRectToPixelBox(machine);
            el.style.left = box.left + 'px';
            el.style.top = box.top + 'px';
            el.style.width = box.width + 'px';
            el.style.height = box.height + 'px';
        }

        function drawMachines() {
            if (!Array.isArray(MACHINES) || MACHINES.length === 0) return;

            if (machineNodes.length === 0) {
                MACHINES.forEach(m => {
                    const node = createMachineNode(m);
                    machineNodes.push({
                        node,
                        data: m
                    });
                });
            }
            machineNodes.forEach(({
                node,
                data
            }) => positionMachineNode(node, data));
        }

        function resetMachines() {
            machineNodes.forEach(({
                node
            }) => node.remove());
            machineNodes = [];
            drawMachines();
        }
    </script>
</x-app-layout>
