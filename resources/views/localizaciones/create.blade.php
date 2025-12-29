<x-app-layout>
    {{-- Cabecera --}}
    <x-slot name="title">Mapa de Ubicaciones — Colocar máquinas</x-slot>
    {{-- Menús --}}
    <x-menu.localizaciones.menu-localizaciones-vistas :obra-actual-id="$obraActualId ?? null" route-index="localizaciones.index"
        route-create="localizaciones.create" />
    <x-menu.localizaciones.menu-localizaciones-naves :obras="$obras" :obra-actual-id="$obraActualId ?? null" />
    <div class="px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-white border rounded-lg p-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar mapa de {{ $obraActiva->obra ?? 'localizaciones' }}
            </h2>
            <p class="text-sm text-gray-500">
                Celda = 0,5 m.
                Ancho: {{ $dimensiones['ancho'] }} m ({{ $columnasReales }} columnas),
                Largo: {{ $dimensiones['largo'] }} m ({{ $filasReales }} filas).
                Vista: {{ $columnasVista }}×{{ $filasVista }} (lado largo en horizontal).
            </p>
        </div>
        <div class="px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-white border rounded-lg p-3 flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" id="toggle-detalles" checked>
                    Mostrar nombres y botones eliminar
                </label>
            </div>
        </div>

    </div>
    {{-- Bandeja de máquinas --}}
    <div class="px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-white border rounded-lg p-3">
            <h3 class="font-semibold text-gray-700 mb-2">
                Máquinas disponibles ({{ $maquinasDisponibles->count() }})
            </h3>
            <div id="bandeja-maquinas" class="flex flex-wrap gap-2">
                @forelse($maquinasDisponibles as $maq)
                    <button class="chip-maquina px-3 py-2 border rounded-lg text-sm hover:bg-gray-50"
                        data-id="{{ $maq['id'] }}" data-nombre="{{ $maq['nombre'] }}" data-w="{{ $maq['wCeldas'] }}"
                        data-h="{{ $maq['hCeldas'] }}"
                        title="{{ $maq['nombre'] }} — {{ $maq['wCeldas'] }}×{{ $maq['hCeldas'] }} celdas">
                        <span class="font-medium">{{ $maq['nombre'] }}</span>
                        <span class="ml-2 text-gray-500">{{ $maq['wCeldas'] }}×{{ $maq['hCeldas'] }}</span>
                    </button>
                @empty
                    <div class="text-sm text-gray-500">No hay máquinas sin colocar en esta nave.</div>
                @endforelse
            </div>
            <p id="estado-seleccion" class="text-sm text-gray-600 mt-2">
                Selecciona una máquina y colócala en la cuadrícula.
            </p>
        </div>
    </div>

    {{-- Escenario + Cuadrícula --}}
    <div class="px-4 sm:px-6 lg:px-8 mt-4">
        <div id="escenario-cuadricula">
            <div id="cuadricula" aria-label="Cuadrícula de la nave" class="relative">

                {{-- overlays de localizaciones con máquina --}}
                @foreach ($localizacionesConMaquina as $loc)
                    <div class="loc-existente loc-maquina" data-id="{{ $loc['id'] }}" data-x1="{{ $loc['x1'] }}"
                        data-y1="{{ $loc['y1'] }}" data-x2="{{ $loc['x2'] }}" data-y2="{{ $loc['y2'] }}"
                        data-nombre="{{ $loc['nombre'] }}" data-maquina-id="{{ $loc['maquina_id'] ?? '' }}"
                        data-w="{{ $loc['wCeldas'] ?? 1 }}" data-h="{{ $loc['hCeldas'] ?? 1 }}">
                        <span class="loc-label">{{ $loc['nombre'] }}</span>
                        <button type="button" class="loc-delete" aria-label="Eliminar localización"
                            title="Eliminar {{ $loc['nombre'] }}">×</button>
                    </div>
                @endforeach

                {{-- overlays de ZONAS (no-maquina) --}}
                @foreach ($localizacionesZonas as $loc)
                    @php
                        $tipo = str_replace('-', '_', $loc['tipo'] ?? 'transitable');
                    @endphp
                    <div class="loc-existente loc-zona tipo-{{ $tipo }}" data-id="{{ $loc['id'] }}"
                        data-x1="{{ $loc['x1'] }}" data-y1="{{ $loc['y1'] }}" data-x2="{{ $loc['x2'] }}"
                        data-y2="{{ $loc['y2'] }}" data-nombre="{{ $loc['nombre'] }}"
                        data-tipo="{{ $tipo }}">
                        <span class="loc-label">{{ $loc['nombre'] }}</span>
                        <button type="button" class="loc-delete" aria-label="Eliminar localización"
                            title="Eliminar {{ $loc['nombre'] }}">×</button>
                    </div>
                @endforeach

                <div id="ghost" class="absolute pointer-events-none hidden border-2 border-dashed rounded-sm"></div>
            </div>

            <div id="info-cuadricula" class="info-cuadricula">
                {{ $columnasVista }} columnas × {{ $filasVista }} filas
            </div>

        </div>
    </div>

    {{-- CSS --}}
    <link rel="stylesheet" href="{{ asset('css/localizaciones/styleLocCreate.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Script Único Consolidado SPA --}}
    <script>
        window.locCreateCtx = @json($ctx);

        window.initLocalizacionesCreatePage = function() {
            // 1. Evitar doble inicialización
            if (document.body.dataset.localizacionesCreatePageInit === "true") return;
            document.body.dataset.localizacionesCreatePageInit = "true";

            // 2. Referencias y Contexto
            const ctx = window.locCreateCtx || {};
            const grid = document.getElementById('cuadricula');

            // Si no hay grid, salimos (puede pasar si cambiamos de página rápido)
            if (!grid) return;

            const escenario = document.getElementById('escenario-cuadricula');
            const ghost = document.getElementById('ghost');
            const bandeja = document.getElementById('bandeja-maquinas');
            const estadoSel = document.getElementById('estado-seleccion');
            const toggle = document.getElementById('toggle-detalles');

            // UI Flotante Medidor
            let meterEl = document.getElementById('medidor-area');
            if (!meterEl) {
                meterEl = document.createElement('div');
                meterEl.id = 'medidor-area';
                Object.assign(meterEl.style, {
                    position: 'fixed',
                    zIndex: '9999',
                    pointerEvents: 'none',
                    padding: '6px 8px',
                    borderRadius: '6px',
                    boxShadow: '0 4px 10px rgba(0,0,0,.12)',
                    background: 'rgba(17,24,39,.92)',
                    color: '#fff',
                    fontSize: '12px',
                    lineHeight: '1.15',
                    whiteSpace: 'nowrap',
                    transform: 'translate(12px, 12px)',
                    display: 'none'
                });
                meterEl.innerHTML = '—';
                document.body.appendChild(meterEl);
            }

            // --- ESTADO GLOBAL PAGINA ---
            let celdaPx = 8;
            const CELL_METERS = 0.5;

            // Estado: Colocación de Nueva Máquina
            let selected = null; // {id, nombre, w, h} (REAL)
            let ghostPosVista = {
                x: 1,
                y: 1
            };
            let ghostSizeVista = {
                w: 1,
                h: 1
            }; // VISTA (transpuesto)

            // Estado: Selección de Zona
            let draggingZone = false;
            let startCellZone = null;
            let selBox = null;
            let suppressNextClick = false;

            // Estado: Mover/Editar Existente
            let active = null; // objeto siendo arrastrado
            let dragGhost = null;
            let isModalOpen = false;
            let suprimeSiguienteDown = 0;
            let congelado = false;
            let isConfirming = false;

            // Estado: Meter
            let rafMeter = null;
            let mouseMeter = {
                x: 0,
                y: 0
            };


            // --- HELPERS BÁSICOS ---

            const getCeldaPx = () => {
                const v = getComputedStyle(grid).getPropertyValue('--tam-celda').trim();
                const n = parseInt(v, 10);
                return Number.isFinite(n) && n > 0 ? n : 8;
            };

            const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

            // Real (x:1..W, y:1..H) -> Vista horizontal (x':1..H, y':1..W)
            const realToVistaRect = (x1r, y1r, x2r, y2r) => {
                const x1 = Math.min(x1r, x2r),
                    x2 = Math.max(x1r, x2r);
                const y1 = Math.min(y1r, y2r),
                    y2 = Math.max(y1r, y2r);
                return {
                    x: y1,
                    y: x1,
                    w: (y2 - y1 + 1),
                    h: (x2 - x1 + 1)
                };
            };

            // Vista -> Real
            const vistaToRealRect = (x1v, y1v, wv, hv) => {
                const x2v = x1v + wv - 1;
                const y2v = y1v + hv - 1;
                return {
                    x1: y1v,
                    y1: x1v,
                    x2: y2v,
                    y2: x2v
                };
            };

            const vistaToRealWH = (wv, hv) => ({
                w: hv,
                h: wv
            });

            const puntoEnVista = (ev) => {
                const rect = grid.getBoundingClientRect();
                const x = Math.floor((ev.clientX - rect.left) / celdaPx) + 1;
                const y = Math.floor((ev.clientY - rect.top) / celdaPx) + 1;
                return {
                    x: clamp(x, 1, ctx.columnasVista),
                    y: clamp(y, 1, ctx.filasVista)
                };
            };

            const rectsIguales = (a, b) => a.x1 === b.x1 && a.y1 === b.y1 && a.x2 === b.x2 && a.y2 === b.y2;

            const colisionaConOcupadas = (rectReal, selfRect = null) => {
                const arr = Array.isArray(ctx.ocupadas) ? ctx.ocupadas : [];
                return arr.some(o => {
                    if (selfRect && rectsIguales(o, selfRect)) return false;
                    return !(rectReal.x2 < o.x1 || rectReal.x1 > o.x2 || rectReal.y2 < o.y1 || rectReal.y1 >
                        o.y2);
                });
            };

            const inBounds = (r) => (r.x1 >= 1 && r.y1 >= 1 && r.x2 <= ctx.columnasReales && r.y2 <= ctx.filasReales);

            const fmt = (n) => (Math.round(n * 100) / 100).toFixed(2).replace(/\.?0+$/, '');

            // --- GESTIÓN DE OCUPADAS ---
            const removeFromOcupadas = (r) => {
                if (!Array.isArray(ctx.ocupadas)) return;
                const i = ctx.ocupadas.findIndex(o => rectsIguales(o, r));
                if (i !== -1) ctx.ocupadas.splice(i, 1);
            };
            const addToOcupadas = (r) => {
                (ctx.ocupadas ||= []).push(r);
            };

            // --- RENDERIZADO ---

            function renderExistentes() {
                celdaPx = getCeldaPx();
                document.querySelectorAll('.loc-existente').forEach(el => {
                    const x1 = Number(el.dataset.x1),
                        y1 = Number(el.dataset.y1);
                    const x2 = Number(el.dataset.x2),
                        y2 = Number(el.dataset.y2);
                    const {
                        x,
                        y,
                        w,
                        h
                    } = realToVistaRect(x1, y1, x2, y2);
                    el.style.left = ((x - 1) * celdaPx) + 'px';
                    el.style.top = ((y - 1) * celdaPx) + 'px';
                    el.style.width = (w * celdaPx) + 'px';
                    el.style.height = (h * celdaPx) + 'px';
                });
                // También actualizar zonas que usan la misma clase
            }

            function ajustarTamCelda() {
                if (!escenario) return;
                const anchoDisp = escenario.clientWidth - 12;
                const altoDisp = escenario.clientHeight - 12;
                const tamPorAncho = Math.floor(anchoDisp / ctx.columnasVista);
                const tamPorAlto = Math.floor(altoDisp / ctx.filasVista);
                const tamCelda = Math.max(4, Math.min(tamPorAncho, tamPorAlto));

                grid.style.setProperty('--tam-celda', tamCelda + 'px');
                grid.style.width = (tamCelda * ctx.columnasVista) + 'px';
                grid.style.height = (tamCelda * ctx.filasVista) + 'px';
                celdaPx = tamCelda;

                renderExistentes();
                renderGhostPlacement();
                updateGhostPlacementColor();

                // Si movemos, actualizar dragGhost
                if (active && active.nextVista && !congelado) {
                    paintDragGhost(active.nextVista.x, active.nextVista.y, active.wVista, active.hVista, !!active
                        .validNext);
                }
            }


            // --- LOGICA PLACEMENT (NUEVA MAQUINA) ---

            function renderGhostPlacement() {
                if (!selected || !ghost) return;
                const x = Math.min(ghostPosVista.x, ctx.columnasVista - ghostSizeVista.w + 1);
                const y = Math.min(ghostPosVista.y, ctx.filasVista - ghostSizeVista.h + 1);
                ghost.style.left = ((x - 1) * celdaPx) + 'px';
                ghost.style.top = ((y - 1) * celdaPx) + 'px';
                ghost.style.width = (ghostSizeVista.w * celdaPx) + 'px';
                ghost.style.height = (ghostSizeVista.h * celdaPx) + 'px';
            }

            function updateGhostPlacementColor() {
                if (!selected || !ghost) return;
                const x = Math.min(ghostPosVista.x, ctx.columnasVista - ghostSizeVista.w + 1);
                const y = Math.min(ghostPosVista.y, ctx.filasVista - ghostSizeVista.h + 1);
                const vr = vistaToRealRect(x, y, ghostSizeVista.w, ghostSizeVista.h);
                const fuera = !inBounds(vr);
                const choca = colisionaConOcupadas(vr);
                const ok = (!fuera && !choca);
                ghost.style.background = ok ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.15)';
                ghost.style.borderColor = ok ? '#22c55e' : '#ef4444';
            }

            function seleccionarChip(btn) {
                document.querySelectorAll('.chip-maquina').forEach(c => c.classList.remove('ring', 'ring-blue-500'));
                btn.classList.add('ring', 'ring-blue-500');

                selected = {
                    id: +btn.dataset.id,
                    nombre: btn.dataset.nombre,
                    w: +btn.dataset.w, // REAL
                    h: +btn.dataset.h // REAL
                };
                // en vista horizontal, el ghost se dibuja transpuesto
                ghostSizeVista = {
                    w: selected.h,
                    h: selected.w
                };

                ghost.classList.remove('hidden');
                estadoSel.textContent = `Seleccionada: ${selected.nombre}. Pulsa "R" para rotar. Clic para colocar.`;
                renderGhostPlacement();
                updateGhostPlacementColor();
            }

            function rotarSeleccionPlacement() {
                if (!selected) return;
                [ghostSizeVista.w, ghostSizeVista.h] = [ghostSizeVista.h, ghostSizeVista.w];
                renderGhostPlacement();
                updateGhostPlacementColor();
            }

            function cancelarColocacion() {
                if (!selected) return;
                selected = null;
                ghost.classList.add('hidden');
                estadoSel.textContent = 'Selección cancelada. Selecciona una máquina para continuar.';
                document.querySelectorAll('.chip-maquina').forEach(c => c.classList.remove('ring', 'ring-blue-500'));
            }

            // --- LOGICA ZONE SELECTION (NUEVA ZONA) ---

            function updateSelBox(a, b) {
                if (!selBox) return;
                const x1 = Math.min(a.x, b.x),
                    y1 = Math.min(a.y, b.y);
                const x2 = Math.max(a.x, b.x),
                    y2 = Math.max(a.y, b.y);
                const w = x2 - x1 + 1;
                const h = y2 - y1 + 1;

                selBox.style.left = ((x1 - 1) * celdaPx) + 'px';
                selBox.style.top = ((y1 - 1) * celdaPx) + 'px';
                selBox.style.width = (w * celdaPx) + 'px';
                selBox.style.height = (h * celdaPx) + 'px';
            }


            // --- LOGICA MOVE/EDIT (EXISTENTE) ---

            function ensureDragGhost() {
                if (!dragGhost) {
                    dragGhost = document.createElement('div');
                    dragGhost.id = 'drag-ghost';
                    Object.assign(dragGhost.style, {
                        position: 'absolute',
                        pointerEvents: 'none',
                        border: '2px dashed #22c55e',
                        background: 'rgba(34,197,94,0.15)',
                        borderRadius: '2px',
                        zIndex: '60',
                        display: 'none'
                    });
                    grid.appendChild(dragGhost);
                }
            }

            function paintDragGhost(xv, yv, wv, hv, ok = true) {
                ensureDragGhost();
                dragGhost.style.left = ((xv - 1) * celdaPx) + 'px';
                dragGhost.style.top = ((yv - 1) * celdaPx) + 'px';
                dragGhost.style.width = (wv * celdaPx) + 'px';
                dragGhost.style.height = (hv * celdaPx) + 'px';
                dragGhost.style.borderColor = ok ? '#22c55e' : '#ef4444';
                dragGhost.style.background = ok ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.15)';
                dragGhost.style.display = 'block';
            }

            function hideDragGhost() {
                if (dragGhost) dragGhost.style.display = 'none';
            }

            // --- EVENT HANDLERS CONSOLIDADOS ---

            function handleResize() {
                ajustarTamCelda();
                // forzamos tick del meter
                if (!rafMeter) rafMeter = requestAnimationFrame(tickMeter);
            }

            function handleKeyDown(ev) {
                // Ignore inputs
                const tag = (ev.target.tagName || '').toLowerCase();
                if (['input', 'textarea', 'select'].includes(tag) || ev.isComposing) return;

                if (ev.key === 'r' || ev.key === 'R') {
                    // ROTAR
                    if (active && !congelado) {
                        // Rotar Drag
                        ev.preventDefault();
                        [active.wVista, active.hVista] = [active.hVista, active.wVista];

                        const baseVista = active.nextVista || active.startVista;
                        let nx = clamp(baseVista.x, 1, ctx.columnasVista - active.wVista + 1);
                        let ny = clamp(baseVista.y, 1, ctx.filasVista - active.hVista + 1);

                        const nr = vistaToRealRect(nx, ny, active.wVista, active.hVista);
                        const okBounds = inBounds(nr);
                        const okCol = !colisionaConOcupadas(nr, active.selfRectReal);

                        paintDragGhost(nx, ny, active.wVista, active.hVista, (okBounds && okCol));
                        active.nextVista = {
                            x: nx,
                            y: ny
                        };
                        active.nextReal = nr;
                        active.validNext = okBounds && okCol;
                    } else if (selected) {
                        // Rotar Placement
                        ev.preventDefault();
                        rotarSeleccionPlacement();
                    }
                }

                if (ev.key === 'Escape') {
                    if (active) {
                        hideDragGhost();
                        active = null;
                    } else if (selected) {
                        cancelarColocacion();
                    }
                }
            }

            function handleGridMouseDown(ev) {
                // 1. Check click-through suppression
                if (performance.now() < suprimeSiguienteDown) return;
                if (ev.button !== 0) return;

                // 2. Check if clicking on existing (.loc-existente)
                const elExistente = ev.target.closest('.loc-existente');
                // Si es un boton delete, no hacemos drag
                if (ev.target.closest('.loc-delete')) return;

                if (elExistente) {
                    // --- MODO MOVE START ---
                    // Evitar propago si es necesario
                    actualizarCeldaPx();

                    const x1 = Number(elExistente.dataset.x1),
                        y1 = Number(elExistente.dataset.y1);
                    const x2 = Number(elExistente.dataset.x2),
                        y2 = Number(elExistente.dataset.y2);
                    const v = realToVistaRect(x1, y1, x2, y2);
                    const m = puntoEnVista(ev);

                    active = {
                        el: elExistente,
                        id: Number(elExistente.dataset.id),
                        nombre: elExistente.dataset.nombre,
                        selfRectReal: {
                            x1,
                            y1,
                            x2,
                            y2
                        },
                        startVista: {
                            x: v.x,
                            y: v.y
                        },
                        offVista: {
                            dx: m.x - v.x,
                            dy: m.y - v.y
                        },
                        wVista: v.w,
                        hVista: v.h,
                        nextVista: null,
                        nextReal: null,
                        validNext: true
                    };
                    paintDragGhost(v.x, v.y, v.w, v.h, true);
                    ev.preventDefault();
                    return;
                }

                // 3. No hay existente, ver si estamos colocando maquina
                if (selected) {
                    // Placement no usa mousedown drag, usa click. Nothing to do on mousedown here, 
                    // except maybe preventing default selection
                    // ev.preventDefault();
                    return;
                }

                // 4. MODO ZONE SELECTION START
                draggingZone = true;
                startCellZone = puntoEnVista(ev);

                selBox = document.createElement('div');
                selBox.className = 'sel-box';
                Object.assign(selBox.style, {
                    position: 'absolute',
                    pointerEvents: 'none',
                    border: '2px dashed #111827',
                    background: 'rgba(17,24,39,0.10)',
                    borderRadius: '2px'
                });
                grid.appendChild(selBox);
                updateSelBox(startCellZone, startCellZone);
                ev.preventDefault();
            }

            function handleWindowMouseMove(ev) {
                // Actualizar meter siempre
                mouseMeter.x = ev.clientX;
                mouseMeter.y = ev.clientY;
                if (!rafMeter) rafMeter = requestAnimationFrame(tickMeter);

                // Grid bounding check for interaction
                // (Opcional: si queremos que el drag continue fuera del grid, usamos window)

                if (active && !congelado) {
                    // --- MOVE DRAG ---
                    celdaPx = getCeldaPx(); // ensure fresh
                    const m = puntoEnVista(ev);
                    let nx = m.x - active.offVista.dx;
                    let ny = m.y - active.offVista.dy;
                    nx = clamp(nx, 1, ctx.columnasVista - active.wVista + 1);
                    ny = clamp(ny, 1, ctx.filasVista - active.hVista + 1);

                    const nr = vistaToRealRect(nx, ny, active.wVista, active.hVista);
                    const okBounds = inBounds(nr);
                    const okCol = !colisionaConOcupadas(nr, active.selfRectReal);

                    paintDragGhost(nx, ny, active.wVista, active.hVista, okBounds && okCol);
                    active.nextVista = {
                        x: nx,
                        y: ny
                    };
                    active.nextReal = nr;
                    active.validNext = okBounds && okCol;
                    return;
                }

                if (draggingZone && selBox) {
                    // --- ZONE DRAG ---
                    const cur = puntoEnVista(ev);
                    updateSelBox(startCellZone, cur);
                    return;
                }

                if (selected && !active && !draggingZone) {
                    // --- PLACEMENT GHOST ---
                    // Solo si el ratón está sobre el grid? 
                    // El evento es window, pero calculamos puntoEnVista que hace clamp.
                    // Verificamos si realmente está sobre el grid para ocultar si sale?
                    // El original usaba grid.addEventListener('mousemove') para placement.
                    // Aquí podemos checar elementFromPoint o bounding client.
                    const rect = grid.getBoundingClientRect();
                    const isOver = ev.clientX >= rect.left && ev.clientX <= rect.right && ev.clientY >= rect.top && ev
                        .clientY <= rect.bottom;

                    if (isOver) {
                        ghostPosVista = puntoEnVista(ev);
                        renderGhostPlacement();
                        updateGhostPlacementColor();
                    }
                }
            }

            async function handleWindowMouseUp(ev) {
                if (active && !isConfirming) {
                    // --- MOVE END ---
                    const v = active.nextVista || active.startVista;
                    const r = active.nextReal || active.selfRectReal;

                    const cambiado = !rectsIguales(r, active.selfRectReal);
                    if (!cambiado) {
                        hideDragGhost();
                        active = null;
                        return;
                    }

                    if (!active.validNext) {
                        hideDragGhost();
                        active = null;
                        await Swal.fire({
                            icon: 'error',
                            title: 'Posición no válida',
                            text: 'Fuera de límites o ocupada.'
                        });
                        return;
                    }

                    isConfirming = true;
                    congelado = true;
                    isModalOpen = true; // flag para suprimir clicks

                    const confirm = await Swal.fire({
                        icon: 'question',
                        title: 'Confirmar movimiento',
                        html: `¿Mover <b>${active.nombre}</b> a (${r.x1},${r.y1}) → (${r.x2},${r.y2})?`,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, guardar',
                        cancelButtonText: 'Cancelar'
                    });

                    isConfirming = false;
                    isModalOpen = false;
                    congelado = false;
                    suprimeSiguienteDown = performance.now() + 200;

                    if (!confirm.isConfirmed) {
                        hideDragGhost();
                        active = null;
                        return;
                    }

                    // Save
                    try {
                        const url = (ctx.updateUrlTemplate || '/localizaciones/:id').replace(':id', String(active
                            .id));
                        const res = await fetch(url, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                x1: r.x1,
                                y1: r.y1,
                                x2: r.x2,
                                y2: r.y2
                            })
                        });
                        if (!res.ok) throw new Error(await res.text());

                        removeFromOcupadas(active.selfRectReal);
                        addToOcupadas(r);
                        active.el.dataset.x1 = String(r.x1);
                        active.el.dataset.y1 = String(r.y1);
                        active.el.dataset.x2 = String(r.x2);
                        active.el.dataset.y2 = String(r.y2);
                        const newVista = realToVistaRect(r.x1, r.y1, r.x2, r.y2);
                        active.el.style.left = ((newVista.x - 1) * celdaPx) + 'px';
                        active.el.style.top = ((newVista.y - 1) * celdaPx) + 'px';
                        // Width/Height update too just in case rotation changed them visually
                        active.el.style.width = (newVista.w * celdaPx) + 'px';
                        active.el.style.height = (newVista.h * celdaPx) + 'px';

                        hideDragGhost();
                        active = null;
                    } catch (err) {
                        console.error(err);
                        hideDragGhost();
                        active = null;
                        Swal.fire('Error', 'No se pudo actualizar', 'error');
                    }
                    return;
                }

                if (draggingZone) {
                    draggingZone = false;
                    const endCell = puntoEnVista(ev);

                    // Normalize
                    const x1v = Math.min(startCellZone.x, endCell.x),
                        y1v = Math.min(startCellZone.y, endCell.y);
                    const x2v = Math.max(startCellZone.x, endCell.x),
                        y2v = Math.max(startCellZone.y, endCell.y);
                    const w = x2v - x1v + 1;
                    const h = y2v - y1v + 1;

                    if (w < 1 || h < 1 || !selBox) {
                        selBox?.remove();
                        selBox = null;
                        return;
                    }

                    suppressNextClick = true;
                    setTimeout(() => suppressNextClick = false, 0);

                    selBox.remove();
                    selBox = null;

                    const r = vistaToRealRect(x1v, y1v, w, h);
                    const fuera = !inBounds(r);
                    if (fuera) {
                        Swal.fire('Error', 'Fuera de límites', 'error');
                        return;
                    }
                    if (colisionaConOcupadas(r)) {
                        Swal.fire('Error', 'Zona ocupada', 'error');
                        return;
                    }

                    // Create Wizard
                    const {
                        value: tipo
                    } = await Swal.fire({
                        title: 'Tipo de localización',
                        input: 'select',
                        inputOptions: {
                            transitable: 'Transitable',
                            almacenamiento: 'Almacenamiento',
                            carga_descarga: 'Carga/descarga'
                        },
                        inputPlaceholder: 'Elige un tipo',
                        showCancelButton: true
                    });
                    if (!tipo) return;

                    const nombreDefecto = (tipo.replace('_', '/').toUpperCase()) + ` ${r.x1}-${r.y1}`;
                    const {
                        value: nombreIngresado
                    } = await Swal.fire({
                        title: 'Nombre/etiqueta',
                        input: 'text',
                        inputValue: nombreDefecto,
                        showCancelButton: true
                    });
                    if (nombreIngresado === undefined) return;

                    const nombreFinal = (nombreIngresado || '').trim() || nombreDefecto;

                    try {
                        const res = await fetch(ctx.storeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                nave_id: ctx.naveId,
                                tipo,
                                x1: r.x1,
                                y1: r.y1,
                                x2: r.x2,
                                y2: r.y2,
                                nombre: nombreFinal
                            })
                        });
                        if (!res.ok) throw new Error(await res.text());
                        const saved = await res.json();

                        addToOcupadas(r);

                        const div = document.createElement('div');
                        div.className = `loc-existente loc-zona tipo-${tipo}`;
                        div.dataset.x1 = r.x1;
                        div.dataset.y1 = r.y1;
                        div.dataset.x2 = r.x2;
                        div.dataset.y2 = r.y2;
                        div.dataset.nombre = saved.nombre || nombreFinal;
                        div.dataset.id = saved.id;
                        div.dataset.tipo = tipo;

                        // Clean old content inner
                        div.innerHTML =
                            `<span class="loc-label">${div.dataset.nombre}</span><button type="button" class="loc-delete" aria-label="Eliminar" title="Eliminar">×</button>`;

                        const v = realToVistaRect(r.x1, r.y1, r.x2, r.y2);
                        div.style.left = ((v.x - 1) * celdaPx) + 'px';
                        div.style.top = ((v.y - 1) * celdaPx) + 'px';
                        div.style.width = (v.w * celdaPx) + 'px';
                        div.style.height = (v.h * celdaPx) + 'px';

                        if (tipo === 'transitable') {
                            div.style.background = 'rgba(107,114,128,0.15)';
                            div.style.border = '1px dashed #6b7280';
                        } else if (tipo === 'almacenamiento') {
                            div.style.background = 'rgba(245,158,11,0.15)';
                            div.style.border = '1px solid #f59e0b';
                        } else {
                            div.style.background = 'rgba(59,130,246,0.15)';
                            div.style.border = '1px solid #3b82f6';
                        }

                        grid.appendChild(div);

                        Swal.fire({
                            icon: 'success',
                            title: 'Zona creada',
                            timer: 1200,
                            showConfirmButton: false
                        });
                    } catch (err) {
                        console.error(err);
                        Swal.fire('Error', 'No se pudo guardar', 'error');
                    }
                }
            }

            async function handleGridClick(ev) {
                if (suppressNextClick) {
                    ev.stopImmediatePropagation();
                    ev.preventDefault();
                    suppressNextClick = false;
                    return;
                }

                // Delete Button
                const btnDel = ev.target.closest('.loc-delete');
                if (btnDel) {
                    ev.stopPropagation();
                    ev.preventDefault();
                    const cont = btnDel.closest('.loc-existente');
                    if (!cont) return;

                    const id = Number(cont.dataset.id);
                    const r = {
                        x1: Number(cont.dataset.x1),
                        y1: Number(cont.dataset.y1),
                        x2: Number(cont.dataset.x2),
                        y2: Number(cont.dataset.y2)
                    };
                    const confirm = await Swal.fire({
                        icon: 'warning',
                        title: 'Eliminar localización',
                        text: 'Esta acción no se puede deshacer.',
                        showCancelButton: true,
                        confirmButtonText: 'Eliminar',
                        confirmButtonColor: '#ef4444'
                    });
                    if (!confirm.isConfirmed) return;

                    try {
                        const url = (ctx.deleteUrlTemplate || '/localizaciones/:id').replace(':id', String(id));
                        const res = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                id
                            })
                        });
                        if (!res.ok) throw new Error();

                        removeFromOcupadas(r);

                        // Si es maquina, devolver a bandeja
                        const maqId = Number(cont.dataset.maquinaId || 0);
                        if (maqId > 0 && bandeja && !bandeja.querySelector(`.chip-maquina[data-id="${maqId}"]`)) {
                            const btn = document.createElement('button');
                            btn.className = 'chip-maquina px-3 py-2 border rounded-lg text-sm hover:bg-gray-50';
                            btn.dataset.id = maqId;
                            btn.dataset.nombre = cont.dataset.nombre;
                            const w = cont.dataset.w || 1;
                            const h = cont.dataset.h || 1;
                            btn.dataset.w = w;
                            btn.dataset.h = h;
                            btn.innerHTML =
                                `<span class="font-medium">${cont.dataset.nombre}</span><span class="ml-2 text-gray-500">${w}×${h}</span>`;
                            bandeja.appendChild(btn);
                        }

                        cont.remove();
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminada',
                            timer: 1000,
                            showConfirmButton: false
                        });

                    } catch (e) {
                        Swal.fire('Error', 'No se pudo eliminar', 'error');
                    }
                    return;
                }

                // Placement Click
                if (selected && !active) {
                    const x = Math.min(ghostPosVista.x, ctx.columnasVista - ghostSizeVista.w + 1);
                    const y = Math.min(ghostPosVista.y, ctx.filasVista - ghostSizeVista.h + 1);
                    const r = vistaToRealRect(x, y, ghostSizeVista.w, ghostSizeVista.h);

                    if (!inBounds(r)) {
                        Swal.fire('Error', 'Fuera de límites', 'error');
                        return;
                    }
                    if (colisionaConOcupadas(r)) {
                        Swal.fire('Error', 'Zona ocupada', 'error');
                        return;
                    }

                    const confirm = await Swal.fire({
                        title: 'Confirmar',
                        html: `Colocar <b>${selected.nombre}</b> en (${r.x1},${r.y1})?`,
                        showCancelButton: true
                    });
                    if (!confirm.isConfirmed) return;

                    try {
                        const res = await fetch(ctx.storeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                nave_id: ctx.naveId,
                                tipo: 'maquina',
                                maquina_id: selected.id,
                                x1: r.x1,
                                y1: r.y1,
                                x2: r.x2,
                                y2: r.y2,
                                seccion: 'A',
                                nombre: selected.nombre
                            })
                        });
                        if (!res.ok) throw new Error();
                        const saved = await res.json();

                        addToOcupadas(r);

                        const div = document.createElement('div');
                        div.className = 'loc-existente loc-maquina';
                        div.dataset.x1 = r.x1;
                        div.dataset.y1 = r.y1;
                        div.dataset.x2 = r.x2;
                        div.dataset.y2 = r.y2;
                        div.dataset.nombre = selected.nombre;
                        div.dataset.id = saved.id;
                        div.dataset.maquinaId = selected.id;
                        div.dataset.w = selected.w;
                        div.dataset.h = selected.h;
                        div.innerHTML =
                            `<span class="loc-label">${selected.nombre}</span><button type="button" class="loc-delete" aria-label="Eliminar" title="Eliminar">×</button>`;

                        const v = realToVistaRect(r.x1, r.y1, r.x2, r.y2);
                        div.style.left = ((v.x - 1) * celdaPx) + 'px';
                        div.style.top = ((v.y - 1) * celdaPx) + 'px';
                        div.style.width = (v.w * celdaPx) + 'px';
                        div.style.height = (v.h * celdaPx) + 'px';

                        grid.appendChild(div);

                        // Remove from bandeja
                        const chip = bandeja.querySelector(`.chip-maquina[data-id="${selected.id}"]`);
                        if (chip) chip.remove();

                        selected = null;
                        ghost.classList.add('hidden');
                        estadoSel.textContent = 'Máquina colocada.';
                        Swal.fire({
                            icon: 'success',
                            title: 'Máquina colocada',
                            timer: 1000,
                            showConfirmButton: false
                        });

                    } catch (e) {
                        Swal.fire('Error', 'No se pudo guardar', 'error');
                    }
                }
            }

            // --- METER LOOP ---
            function tickMeter() {
                rafMeter = null;
                if (!meterEl) return;
                celdaPx = getCeldaPx();

                const isGhost = (ghost && !ghost.classList.contains('hidden'));
                const hasSel = (selBox != null);

                if (hasSel && selBox) {
                    const wCellsVista = Math.max(1, Math.round(selBox.offsetWidth / celdaPx));
                    const hCellsVista = Math.max(1, Math.round(selBox.offsetHeight / celdaPx));
                    const {
                        w,
                        h
                    } = vistaToRealWH(wCellsVista, hCellsVista);
                    meterEl.innerHTML =
                        `<strong>Selección</strong><br>${fmt(w*CELL_METERS)} m × ${fmt(h*CELL_METERS)} m<br>${fmt(w*h*CELL_METERS*CELL_METERS)} m²`;
                    meterEl.style.left = mouseMeter.x + 'px';
                    meterEl.style.top = mouseMeter.y + 'px';
                    meterEl.style.display = 'block';
                    return;
                }

                if (isGhost && ghost) {
                    const wCellsVista = Math.max(1, Math.round(ghost.offsetWidth / celdaPx));
                    const hCellsVista = Math.max(1, Math.round(ghost.offsetHeight / celdaPx));
                    const {
                        w,
                        h
                    } = vistaToRealWH(wCellsVista, hCellsVista);
                    meterEl.innerHTML =
                        `<strong>Máquina (ghost)</strong><br>${fmt(w*CELL_METERS)} m × ${fmt(h*CELL_METERS)} m<br>${fmt(w*h*CELL_METERS*CELL_METERS)} m²`;
                    meterEl.style.left = mouseMeter.x + 'px';
                    meterEl.style.top = mouseMeter.y + 'px';
                    meterEl.style.display = 'block';
                    return;
                }

                meterEl.style.display = 'none';
            }


            // --- LISTENERS ---

            // Bandeja Click Delegation
            function handleBandejaClick(ev) {
                const btn = ev.target.closest('.chip-maquina');
                if (btn) seleccionarChip(btn);
            }

            // Toggle change
            function handleToggleChange() {
                if (toggle.checked) grid.classList.remove('ocultar-detalles');
                else grid.classList.add('ocultar-detalles');
            }


            // ATTACH
            window.addEventListener('resize', handleResize, {
                passive: true
            });
            document.addEventListener('keydown', handleKeyDown);
            window.addEventListener('mousemove', handleWindowMouseMove); // Maneja drag y meter
            window.addEventListener('mouseup', handleWindowMouseUp);
            // Si usamos click para placement/delete/zoneend, mejor grid click
            grid.addEventListener('click', handleGridClick, {
                capture: true
            }); // capture to handle stopImmediatePropagation properly? No, default is bubbling but we used capture in original for modal.
            grid.addEventListener('mousedown', handleGridMouseDown);
            bandeja.addEventListener('click', handleBandejaClick);
            if (toggle) toggle.addEventListener('change', handleToggleChange);

            // Init call
            ajustarTamCelda();


            // CLEANUP
            window.pageInitializers.push(() => {
                document.body.dataset.localizacionesCreatePageInit = "false";
                window.removeEventListener('resize', handleResize);
                document.removeEventListener('keydown', handleKeyDown);
                window.removeEventListener('mousemove', handleWindowMouseMove);
                window.removeEventListener('mouseup', handleWindowMouseUp);

                if (meterEl) meterEl.remove();
                if (dragGhost) dragGhost.remove();

                // Estos selectores podrían ya no existir si cambiamos de página, pero remover listener no da error
                if (grid) {
                    grid.removeEventListener('click', handleGridClick);
                    grid.removeEventListener('mousedown', handleGridMouseDown);
                }
                if (bandeja) bandeja.removeEventListener('click', handleBandejaClick);
                if (toggle) toggle.removeEventListener('change', handleToggleChange);
            });
        };

        // Bootstrap
        document.removeEventListener('livewire:navigated', window.initLocalizacionesCreatePage);
        document.addEventListener('livewire:navigated', window.initLocalizacionesCreatePage);
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', window.initLocalizacionesCreatePage);
        } else {
            window.initLocalizacionesCreatePage();
        }
    </script>
</x-app-layout>
