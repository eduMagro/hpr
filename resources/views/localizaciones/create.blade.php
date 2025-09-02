<x-app-layout>
    <x-slot name="title">Mapa de Ubicaciones</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Asignar ubicación a máquinas
        </h2>
    </x-slot>

    {{-- Menú de obras --}}
    <x-menu.localizaciones.menu-localizaciones-index :obras="$obras" :obra-actual-id="$obraActualId ?? null" />

    <div class="h-screen w-screen flex flex-col">
        <!-- HUD -->
        <div class="p-2 bg-white z-10 flex items-center gap-6">
            <div class="text-gray-800 font-semibold">
                Posición actual: <span id="posicionActual">—</span>
            </div>
            <div class="text-sm text-gray-500">Escala: 1 celda = 0,5 m</div>
        </div>

        <!-- BANDEJA DE MÁQUINAS -->
        <div id="bandeja" class="w-full flex flex-wrap gap-2 p-2 bg-white/80 sticky top-0 z-30"></div>

        <!-- GRID -->
        <div class="relative flex-1 p-3">
            <div id="grid" data-ancho-m="{{ $dimensiones['ancho'] }}" data-alto-m="{{ $dimensiones['alto'] }}">
                <div id="overlays"></div>
                <div id="previewRect" class="hidden absolute border-2"></div>
            </div>
        </div>
    </div>

    <!-- MODAL COLOCADOR -->
    <div id="placerModal" class="hidden fixed inset-0 z-40 bg-black/50 items-center justify-center">
        <div class="bg-white rounded-xl shadow-2xl p-4 w-[min(92vw,720px)]">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Colocar máquina</h3>
                <button id="cancelPlaceBtn" class="text-sm text-gray-500 hover:text-gray-800">Cerrar ✕</button>
            </div>

            <div id="placerCanvas" class="relative mt-3 w-full border rounded-md bg-gray-50 overflow-hidden">
                <div id="centerPreview" class="absolute bg-orange-300/60 border-2 border-orange-500"></div>
            </div>

            <div class="mt-3 flex items-center justify-between gap-3">
                <div class="text-sm text-gray-700">
                    Máquina: <b id="mLabel">—</b> · Huella: <b id="huellaLabel">—</b> celdas
                </div>
                <div class="flex gap-2">
                    <button id="rotateBtn" type="button" class="px-3 py-1 border rounded bg-white hover:bg-gray-50">⤾
                        Girar 90º (R)</button>
                    <button id="startPlaceBtn" type="button"
                        class="px-3 py-1 border rounded bg-blue-600 text-white hover:bg-blue-700">
                        Arrastrar al mapa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
        }

        /* Grid horizontal: ocupa 95vw y se ajusta en altura para verse completo sin scroll */
        #grid {
            position: relative;
            width: 95vw;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d1d5db;
        }

        /* Líneas de la rejilla (sin miles de celdas) */
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
            z-index: 5;
        }

        .area {
            position: absolute;
            background: #e6f4ea;
            border: 1px solid #93c5fd;
            box-sizing: border-box;
        }

        .area.tipo-material {
            background: #15803d !important;
        }

        .area.tipo-maquina {
            background: #1d4ed8 !important;
        }

        .area.tipo-transitable {
            background: #6b7280 !important;
        }

        .maquina-label {
            position: absolute;
            transform: translate(-50%, -100%);
            font-size: 10px;
            font-weight: 700;
            color: #111;
            background: rgba(255, 255, 255, .9);
            border-radius: 4px;
            padding: 1px 4px;
            pointer-events: none;
            white-space: nowrap;
            z-index: 10;
        }

        /* Preview */
        #previewRect {
            z-index: 15;
            background: rgba(255, 165, 0, .28);
            border-color: #f97316;
            pointer-events: none;
        }

        #previewRect.ok {
            background: rgba(34, 197, 94, .25) !important;
            border-color: #22c55e !important;
        }

        #previewRect.bad {
            background: rgba(239, 68, 68, .25) !important;
            border-color: #ef4444 !important;
        }

        /* Tokens de la bandeja */
        .machine-token {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .35rem .6rem;
            border: 1px solid #d1d5db;
            border-radius: .5rem;
            background: #f8fafc;
            cursor: pointer;
            user-select: none;
            font-size: .9rem;
        }

        .machine-chip {
            font-size: .7rem;
            background: #e5e7eb;
            border-radius: .4rem;
            padding: .1rem .35rem;
        }
    </style>

    <script>
        // Rutas backend
        window.routes = {
            verificar: "{{ route('localizaciones.verificar') }}",
            store: "{{ route('localizaciones.store') }}"
        };
    </script>
    <script>
        window.context = {
            nave_id: {{ $obraActualId ?? 'null' }} // o $naveActualId
        };
    </script>

    {{-- CORE compartido (helpers) --}}
    <script>
        const CELL_M = 0.5; // 1 celda = 0.5 m

        // (1,1) abajo-izq; x→, y↑
        function metersToCells(m) {
            return Math.max(1, Math.round(m / CELL_M));
        }

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

        function cellsRectToPx({
            x1,
            y1,
            x2,
            y2
        }, {
            cols,
            rows
        }, gridRect) {
            const cw = gridRect.width / cols,
                ch = gridRect.height / rows;
            return {
                left: (x1 - 1) * cw,
                top: (rows - y2) * ch,
                width: (x2 - x1 + 1) * cw,
                height: (y2 - y1 + 1) * ch
            };
        }

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

        function fitWholeGrid(gridEl, cols, rows) {
            const vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
            const vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
            const reservedY = 160; // header + HUD + bandeja (ajústalo si cambias layout)
            const maxW = vw * 0.95;
            const maxH = Math.max(200, vh - reservedY);

            const cell = Math.min(maxW / cols, maxH / rows);
            gridEl.style.width = Math.floor(cell * cols) + 'px';
            gridEl.style.height = Math.floor(cell * rows) + 'px';

            let lines = gridEl.querySelector('.grid-lines');
            if (!lines) {
                lines = document.createElement('div');
                lines.className = 'grid-lines';
                gridEl.appendChild(lines);
            }
            lines.style.backgroundSize = `${cell}px ${cell}px`;

            return gridEl.getBoundingClientRect();
        }

        function drawOverlay(overlaysEl, key, pxBox, cls = 'area') {
            let el = overlaysEl.querySelector(`[data-key="${key}"]`);
            if (!el) {
                el = document.createElement('div');
                el.dataset.key = key;
                el.className = cls;
                overlaysEl.appendChild(el);
            }
            el.style.left = pxBox.left + 'px';
            el.style.top = pxBox.top + 'px';
            el.style.width = pxBox.width + 'px';
            el.style.height = pxBox.height + 'px';
            return el;
        }

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
            lab.style.top = pxBox.top + 'px';
            return lab;
        }
    </script>

    {{-- Vista Create (horizontal, sin scroll; coords tumbadas al colocar, canónicas al guardar) --}}
    <script>
        /* ====== CREATE (grid horizontal / render tumbado, DB canónica) ====== */
        (function CreateGrid() {
            const RENDER_TUMBLED = true; // 👈 pintamos el grid tumbado en esta vista
            const grid = document.getElementById('grid');
            const overlays = document.getElementById('overlays');
            const preview = document.getElementById('previewRect');
            const bandeja = document.getElementById('bandeja');
            const posHUD = document.getElementById('posicionActual');

            const naveAnchoM = parseFloat(grid.dataset.anchoM) || 10; // canónico
            const naveAltoM = parseFloat(grid.dataset.altoM) || 10; // canónico
            const colsCanon = metersToCells(naveAnchoM); // canónico (X)
            const rowsCanon = metersToCells(naveAltoM); // canónico (Y)

            // 👇 En render “horizontal” intercambiamos ejes para que se vea apaisado.
            const cols = RENDER_TUMBLED ? rowsCanon : colsCanon; // render
            const rows = RENDER_TUMBLED ? colsCanon : rowsCanon; // render

            // Helpers de mapeo rect Render <-> Canon
            const toCanonRect = (rRender) => RENDER_TUMBLED ? tumbleRect(rRender) : rRender;
            const toRenderRect = (rCanon) => RENDER_TUMBLED ? tumbleRect(rCanon) : rCanon;

            // Ajustar tamaño para ver TODO sin scroll (líneas incluidas)
            let gridRect = fitWholeGrid(grid, cols, rows);
            window.addEventListener('resize', () => gridRect = fitWholeGrid(grid, cols, rows));

            // ==== Ocupación + pintar existentes (BD ya viene en canónico) ====
            const busy = new Set();
            const localizaciones = @json($localizaciones); // {x1,y1,x2,y2,tipo,maquina_id,nombre} en canónico
            localizaciones.forEach((l, i) => {
                occupyRect(busy, l); // ocupa en canónico
                const rRender = toRenderRect(l); // lo que dibujamos
                const box = cellsRectToPx(rRender, {
                    cols,
                    rows
                }, gridRect);
                const key = `loc-${i}`;
                drawOverlay(overlays, key, box, `area tipo-${l.tipo}`);
                if (l.tipo === 'maquina' && l.nombre) drawLabel(overlays, key, box, l.nombre);
            });

            // ==== HUD (coordenadas mostradas en sistema render) ====
            grid.addEventListener('mousemove', (e) => {
                const r = grid.getBoundingClientRect();
                const cw = r.width / cols,
                    ch = r.height / rows;
                const rx = Math.min(cols, Math.max(1, Math.floor((e.clientX - r.left) / cw) + 1)); // render X
                const ryT = Math.min(rows, Math.max(1, Math.floor((e.clientY - r.top) / ch) +
                    1)); // desde arriba
                const ry = rows - ryT + 1; // eje Y hacia arriba (render)
                posHUD.textContent = `(${rx}, ${ry})`;
            });
            grid.addEventListener('mouseleave', () => posHUD.textContent = '—');

            // ==== Bandeja de máquinas ====
            const maquinas = @json($maquinas); // {id,nombre,ancho_m,largo_m}
            maquinas.forEach(m => {
                const token = document.createElement('div');
                token.className = 'machine-token';
                token.dataset.id = m.id;
                token.dataset.nombre = m.nombre;
                token.dataset.ancho_m = m.ancho_m ?? 1;
                token.dataset.largo_m = m.largo_m ?? 1;
                token.innerHTML =
                    `<strong>${m.nombre}</strong><span class="machine-chip">${m.ancho_m ?? 1}m × ${m.largo_m ?? 1}m</span>`;
                token.addEventListener('click', () => openPlacer(token));
                bandeja.appendChild(token);
            });

            // ==== Modal (rotación/huella en celdas render) ====
            const placerModal = document.getElementById('placerModal');
            const placerCanvas = document.getElementById('placerCanvas');
            const centerPreview = document.getElementById('centerPreview');
            const rotateBtn = document.getElementById('rotateBtn');
            const startPlaceBtn = document.getElementById('startPlaceBtn');
            const cancelPlaceBtn = document.getElementById('cancelPlaceBtn');
            const huellaLabel = document.getElementById('huellaLabel');
            const mLabel = document.getElementById('mLabel');

            let selectedToken = null,
                foot = {
                    w: 1,
                    h: 1
                };

            function openPlacer(token) {
                selectedToken = token;
                const aw = parseFloat(token.dataset.ancho_m) || 1;
                const ah = parseFloat(token.dataset.largo_m) || 1;
                // metros -> celdas (ceil). Ojo: la huella es en el SISTEMA QUE VES (render).
                foot.w = Math.max(1, Math.ceil(aw / CELL_M));
                foot.h = Math.max(1, Math.ceil(ah / CELL_M));
                // Si el render está tumbado, el usuario los percibe intercambiados → opcional:
                if (RENDER_TUMBLED)[foot.w, foot.h] = [foot.h, foot.w];

                mLabel.textContent = token.dataset.nombre;
                updateCenterPreview();
                placerModal.classList.remove('hidden');
                placerModal.classList.add('flex');
                document.addEventListener('keydown', onR, {
                    capture: true
                });
            }

            function closePlacer() {
                placerModal.classList.add('hidden');
                placerModal.classList.remove('flex');
                document.removeEventListener('keydown', onR, {
                    capture: true
                });
            }

            function onR(e) {
                if (e.key.toLowerCase() === 'r') rotateFoot();
            }

            function rotateFoot() {
                [foot.w, foot.h] = [foot.h, foot.w];
                updateCenterPreview();
            }

            function updateCenterPreview() {
                huellaLabel.textContent = `${foot.w} × ${foot.h}`;
                const r = placerCanvas.getBoundingClientRect();
                const cw = r.width / cols,
                    ch = r.height / rows;
                Object.assign(centerPreview.style, {
                    width: (foot.w * cw) + 'px',
                    height: (foot.h * ch) + 'px',
                    left: (r.width / 2 - (foot.w * cw) / 2) + 'px',
                    top: (r.height / 2 - (foot.h * ch) / 2) + 'px'
                });
            }
            rotateBtn.addEventListener('click', rotateFoot);
            cancelPlaceBtn.addEventListener('click', closePlacer);
            startPlaceBtn.addEventListener('click', () => {
                if (!selectedToken) return;
                closePlacer();
                startDrag(selectedToken, foot.w, foot.h);
            });

            // ==== Drag + preview (en render). Guardamos en canónico. ====
            let dragging = null,
                drag = {
                    w: 1,
                    h: 1
                };

            function startDrag(token, w, h) {
                dragging = {
                    token
                };
                drag = {
                    w,
                    h
                };
                preview.classList.remove('hidden');

                const cw = gridRect.width / cols,
                    ch = gridRect.height / rows;
                let rx = Math.floor(cols / 2 - w / 2) + 1,
                    ry = Math.floor(rows / 2 - h / 2) + 1; // render
                rx = Math.min(Math.max(1, rx), cols - w + 1);
                ry = Math.min(Math.max(1, ry), rows - h + 1);
                setPreviewAt(rx, ry);

                document.addEventListener('mousemove', onMove);
                grid.addEventListener('click', onClickPlace);
            }

            function stopDrag() {
                preview.classList.add('hidden');
                preview.classList.remove('ok', 'bad');
                document.removeEventListener('mousemove', onMove);
                grid.removeEventListener('click', onClickPlace);
                dragging = null;
            }

            function onMove(e) {
                if (!dragging) return;
                const r = grid.getBoundingClientRect();
                const cw = r.width / cols,
                    ch = r.height / rows;
                const rx = Math.min(cols, Math.max(1, Math.floor((e.clientX - r.left) / cw) + 1));
                const ryT = Math.min(rows, Math.max(1, Math.floor((e.clientY - r.top) / ch) + 1));
                const ry = rows - ryT + 1;
                setPreviewAt(Math.min(Math.max(1, rx), cols - drag.w + 1),
                    Math.min(Math.max(1, ry), rows - drag.h + 1));
            }

            function setPreviewAt(rx, ry) {
                // rect en RENDER (lo que ve el usuario)
                const rectRender = {
                    x1: rx,
                    y1: ry,
                    x2: rx + drag.w - 1,
                    y2: ry + drag.h - 1
                };
                // rect en CANÓNICO (para validar/guardar)
                const rectCanon = toCanonRect(rectRender);
                const ok = isFree(busy, rectCanon);
                const box = cellsRectToPx(rectRender, {
                    cols,
                    rows
                }, gridRect);
                Object.assign(preview.style, {
                    left: box.left + 'px',
                    top: box.top + 'px',
                    width: box.width + 'px',
                    height: box.height + 'px'
                });
                preview.dataset.rx = rx;
                preview.dataset.ry = ry;
                preview.classList.toggle('ok', ok);
                preview.classList.toggle('bad', !ok);
            }

            async function onClickPlace() {
                if (!dragging) return;

                // Rectángulo como lo ve el usuario (coordenadas de render)
                const rx = +preview.dataset.rx,
                    ry = +preview.dataset.ry;
                const rectRender = {
                    x1: rx,
                    y1: ry,
                    x2: rx + drag.w - 1,
                    y2: ry + drag.h - 1
                };

                // Rectángulo canónico para DB (eje Y ↑, sin tumbadas)
                const rectCanon = toCanonRect(rectRender);

                // Chequeo rápido en cliente
                if (!isFree(busy, rectCanon)) {
                    // opcional: feedback
                    // Swal.fire('Posición ocupada', 'Ya hay una localización en esa zona.', 'warning');
                    return;
                }

                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                    const naveId = (window.context && window.context.nave_id) ?? {{ $obraActualId ?? 'null' }};

                    // 1) Verificación servidor (misma nave)
                    const verRes = await fetch(window.routes.verificar, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({
                            ...rectCanon,
                            nave_id: naveId // <-- clave para aislar por nave
                        })
                    });

                    const ver = await verRes.json().catch(() => ({}));
                    if (!verRes.ok) {
                        throw new Error(ver?.message || 'Error en verificación.');
                    }
                    if (ver.existe) {
                        const msg = ver.tipo === 'exacta' ?
                            'Área ya registrada en esta nave.' :
                            'La zona solapa con otra localización en esta nave.';
                        await Swal.fire('No permitido', msg, 'warning');
                        return;
                    }

                    // 2) Guardar en servidor (canónico)
                    const payload = {
                        ...rectCanon,
                        tipo: 'maquina',
                        maquina_id: dragging.token.dataset.id,
                        nave_id: naveId // <-- guardar ligada a la nave actual
                    };

                    const saveRes = await fetch(window.routes.store, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify(payload)
                    });

                    const saveData = await saveRes.json().catch(() => ({}));
                    if (!saveRes.ok || !saveData?.success) {
                        throw new Error(saveData?.message || 'Error al guardar la localización.');
                    }

                    // 3) Pintar como lo vio el usuario (render) y ocupar en canónico
                    const box = cellsRectToPx(rectRender, {
                        cols,
                        rows
                    }, gridRect);
                    const key = `live-${Date.now()}`;
                    drawOverlay(overlays, key, box, 'area tipo-maquina');
                    drawLabel(overlays, key, box, dragging.token.dataset.nombre);

                    occupyRect(busy, rectCanon); // ocupa en canónico
                    dragging.token.remove();
                    stopDrag();

                    await Swal.fire('Guardado', `Ubicación asignada a "${dragging.token.dataset.nombre}"`,
                        'success');
                } catch (err) {
                    console.error(err);
                    await Swal.fire('Error', err.message ?? 'Error desconocido', 'error');
                }
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') stopDrag();
            });
        })();
    </script>


</x-app-layout>
