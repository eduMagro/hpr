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

    {{-- Contexto listo del backend --}}
    <script>
        window.__LOC_CTX__ = @json($ctx);
    </script>

    {{-- JS interacción --}}
    <script>
        (() => {
            const ctx = window.__LOC_CTX__;
            const escenario = document.getElementById('escenario-cuadricula');
            const grid = document.getElementById('cuadricula');
            const ghost = document.getElementById('ghost');
            const bandeja = document.getElementById('bandeja-maquinas');
            const estadoSel = document.getElementById('estado-seleccion');

            let celdaPx = 8;
            let selected = null; // {id, nombre, w, h} en REAL
            let ghostPosVista = {
                x: 1,
                y: 1
            };
            let ghostSizeVista = {
                w: 1,
                h: 1
            }; // en VISTA (transpuesto)

            function getCeldaPx() {
                const v = getComputedStyle(grid).getPropertyValue('--tam-celda').trim();
                const n = parseInt(v, 10);
                return Number.isFinite(n) && n > 0 ? n : 8;
            }

            // Real (x:1..W, y:1..H) -> Vista horizontal (x':1..H, y':1..W)
            // x' = y ; y' = x ; w' = h ; h' = w
            function realToVistaRect(x1r, y1r, x2r, y2r) {
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
            }

            // Vista -> Real
            // x = y' ; y = x' ; w_real = h_vista ; h_real = w_vista
            function vistaToRealRect(x1v, y1v, wv, hv) {
                const x2v = x1v + wv - 1;
                const y2v = y1v + hv - 1;
                return {
                    x1: y1v,
                    y1: x1v,
                    x2: y2v,
                    y2: x2v
                };
            }

            function renderExistentes() {
                celdaPx = getCeldaPx();
                document.querySelectorAll('.loc-existente').forEach(el => {
                    const {
                        x,
                        y,
                        w,
                        h
                    } = realToVistaRect(+el.dataset.x1, +el.dataset.y1, +el.dataset.x2, +el.dataset.y2);
                    el.style.left = ((x - 1) * celdaPx) + 'px';
                    el.style.top = ((y - 1) * celdaPx) + 'px';
                    el.style.width = (w * celdaPx) + 'px';
                    el.style.height = (h * celdaPx) + 'px';
                });
            }

            function ajustarTamCelda() {
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
                renderGhost();
                updateGhostColor();
            }

            function puntoEnVista(ev) {
                const rect = grid.getBoundingClientRect();
                const x = Math.floor((ev.clientX - rect.left) / celdaPx) + 1;
                const y = Math.floor((ev.clientY - rect.top) / celdaPx) + 1;
                return {
                    x: Math.min(Math.max(1, x), ctx.columnasVista),
                    y: Math.min(Math.max(1, y), ctx.filasVista)
                };
            }

            function renderGhost() {
                if (!selected) return;
                const x = Math.min(ghostPosVista.x, ctx.columnasVista - ghostSizeVista.w + 1);
                const y = Math.min(ghostPosVista.y, ctx.filasVista - ghostSizeVista.h + 1);
                ghost.style.left = ((x - 1) * celdaPx) + 'px';
                ghost.style.top = ((y - 1) * celdaPx) + 'px';
                ghost.style.width = (ghostSizeVista.w * celdaPx) + 'px';
                ghost.style.height = (ghostSizeVista.h * celdaPx) + 'px';
            }

            function colisionaConOcupadas(rectReal) {
                return (ctx.ocupadas || []).some(o =>
                    !(rectReal.x2 < o.x1 || rectReal.x1 > o.x2 || rectReal.y2 < o.y1 || rectReal.y1 > o.y2)
                );
            }

            function updateGhostColor() {
                if (!selected) return;
                const x = Math.min(ghostPosVista.x, ctx.columnasVista - ghostSizeVista.w + 1);
                const y = Math.min(ghostPosVista.y, ctx.filasVista - ghostSizeVista.h + 1);
                const vr = vistaToRealRect(x, y, ghostSizeVista.w, ghostSizeVista.h);
                const fuera = vr.x1 < 1 || vr.y1 < 1 || vr.x2 > ctx.columnasReales || vr.y2 > ctx.filasReales;
                const choca = colisionaConOcupadas(vr);
                const ok = (!fuera && !choca);
                ghost.style.background = ok ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.15)';
                ghost.style.borderColor = ok ? '#22c55e' : '#ef4444';
            }

            function rotarSeleccion() {
                if (!selected) return;
                [ghostSizeVista.w, ghostSizeVista.h] = [ghostSizeVista.h, ghostSizeVista.w];
                renderGhost();
                updateGhostColor();
                const wReal = ghostSizeVista.h; // transpuesto
                const hReal = ghostSizeVista.w;
                estadoSel.textContent =
                    `Seleccionada: ${selected.nombre} (${wReal}×${hReal} reales). Pulsa "R" para rotar. Haz clic en la cuadrícula para colocar.`;
            }
            // 1) Añade esta función junto a rotarSeleccion()
            function cancelarColocacion() {
                if (!selected) return;
                selected = null;
                ghost.classList.add('hidden');
                estadoSel.textContent = 'Selección cancelada. Selecciona una máquina para continuar.';
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
                estadoSel.textContent =
                    `Seleccionada: ${selected.nombre} (${selected.w}×${selected.h} reales). Pulsa "R" para rotar. Haz clic en la cuadrícula para colocar.`;
                renderGhost();
                updateGhostColor();
            }

            // Delegación para TODAS las chips (presentes y futuras)
            bandeja.addEventListener('click', (ev) => {
                const btn = ev.target.closest('.chip-maquina');
                if (!btn) return;
                seleccionarChip(btn);
            });

            document.addEventListener('keydown', ev => {
                if (!selected) return;
                const tag = (ev.target.tagName || '').toLowerCase();
                if (['input', 'textarea', 'select'].includes(tag) || ev.isComposing) return;
                if (ev.key === 'r' || ev.key === 'R') {
                    ev.preventDefault();
                    rotarSeleccion();
                }
                // 👇 nuevo: cancelar con ESC
                if (ev.key === 'Escape') {
                    ev.preventDefault();
                    cancelarColocacion();
                    return;
                }
            });

            grid.addEventListener('mousemove', ev => {
                if (!selected) return;
                ghostPosVista = puntoEnVista(ev);
                renderGhost();
                updateGhostColor();
            });

            grid.addEventListener('click', async ev => {
                if (!selected) return;

                const x = Math.min(ghostPosVista.x, ctx.columnasVista - ghostSizeVista.w + 1);
                const y = Math.min(ghostPosVista.y, ctx.filasVista - ghostSizeVista.h + 1);
                const rectReal = vistaToRealRect(x, y, ghostSizeVista.w, ghostSizeVista.h);

                const fuera = rectReal.x1 < 1 || rectReal.y1 < 1 ||
                    rectReal.x2 > ctx.columnasReales || rectReal.y2 > ctx.filasReales;
                if (fuera) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Fuera de límites',
                        text: 'No puedes colocar la máquina fuera de la nave.'
                    });
                    return;
                }
                if (colisionaConOcupadas(rectReal)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Zona ocupada',
                        text: 'Esa zona ya tiene otra máquina.'
                    });
                    return;
                }

                const confirm = await Swal.fire({
                    icon: 'question',
                    title: 'Confirmar colocación',
                    html: `¿Colocar <b>${selected.nombre}</b> en <br> (${rectReal.x1},${rectReal.y1}) → (${rectReal.x2},${rectReal.y2})?`,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, colocar',
                    cancelButtonText: 'Cancelar'
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
                            x1: rectReal.x1,
                            y1: rectReal.y1,
                            x2: rectReal.x2,
                            y2: rectReal.y2,
                            seccion: 'A',
                            nombre: selected.nombre // unificado (no "localizacion")
                        })
                    });
                    if (!res.ok) throw new Error(await res.text());
                    const saved = await res.json().catch(() => null);

                    // Actualiza ocupadas y pinta overlay con datasets completos
                    (ctx.ocupadas ||= []).push(rectReal);

                    const div = document.createElement('div');
                    div.className = 'loc-existente loc-maquina';
                    div.dataset.x1 = rectReal.x1;
                    div.dataset.y1 = rectReal.y1;
                    div.dataset.x2 = rectReal.x2;
                    div.dataset.y2 = rectReal.y2;
                    div.dataset.nombre = selected.nombre;
                    div.dataset.maquinaId = String(selected.id);
                    div.dataset.w = String(selected.w);
                    div.dataset.h = String(selected.h);
                    if (saved?.id) div.dataset.id = String(saved.id);

                    const label = document.createElement('span');
                    label.className = 'loc-label';
                    label.textContent = selected.nombre;
                    div.appendChild(label);

                    const del = document.createElement('button');
                    del.type = 'button';
                    del.className = 'loc-delete';
                    del.title = `Eliminar ${selected.nombre}`;
                    del.setAttribute('aria-label', 'Eliminar localización');
                    del.textContent = '×';
                    div.appendChild(del);

                    grid.appendChild(div);
                    renderExistentes();

                    // Elimina chip de la bandeja
                    const chip = bandeja.querySelector(`.chip-maquina[data-id="${selected.id}"]`);
                    if (chip) chip.remove();

                    selected = null;
                    ghost.classList.add('hidden');
                    estadoSel.textContent = 'Máquina colocada. Selecciona otra para continuar.';

                    Swal.fire({
                        icon: 'success',
                        title: 'Máquina colocada',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } catch (e) {
                    console.error(e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo guardar la localización.'
                    });
                }
            });

            let pendiente = false;

            function onResize() {
                if (pendiente) return;
                pendiente = true;
                requestAnimationFrame(() => {
                    ajustarTamCelda();
                    pendiente = false;
                });
            }

            ajustarTamCelda();
            window.addEventListener('resize', onResize, {
                passive: true
            });
        })();
    </script>

    <script>
        (() => {
            // =========================================================
            // BLOQUE INDEPENDIENTE: eliminación de localizaciones (X)
            // =========================================================
            const grid = document.getElementById('cuadricula');
            const bandeja = document.getElementById('bandeja-maquinas');
            const ctx = window.__LOC_CTX__ || {};
            const deleteTemplate = ctx.deleteUrlTemplate || '/localizaciones/:id';

            function buildDeleteUrl(id) {
                return deleteTemplate.replace(':id', String(id));
            }

            // Quita el rect de ctx.ocupadas (coincidencia exacta por coords)
            function removeFromOcupadas(rect) {
                if (!Array.isArray(ctx.ocupadas)) return;
                const idx = ctx.ocupadas.findIndex(o =>
                    o.x1 === rect.x1 && o.y1 === rect.y1 && o.x2 === rect.x2 && o.y2 === rect.y2
                );
                if (idx !== -1) ctx.ocupadas.splice(idx, 1);
            }

            // Delegación SOLO para el botón .loc-delete
            grid.addEventListener('click', async (ev) => {
                const btn = ev.target.closest('.loc-delete');
                if (!btn) return;

                ev.stopPropagation();
                ev.preventDefault();

                const cont = btn.closest('.loc-existente');
                if (!cont) return;

                const id = Number(cont.dataset.id);
                const rect = {
                    x1: Number(cont.dataset.x1),
                    y1: Number(cont.dataset.y1),
                    x2: Number(cont.dataset.x2),
                    y2: Number(cont.dataset.y2),
                };
                const nombre = cont.dataset.nombre || 'Localización';

                const confirm = await Swal.fire({
                    icon: 'warning',
                    title: 'Eliminar localización',
                    html: `¿Seguro que quieres eliminar <b>${nombre}</b>? Esta acción no se puede deshacer.`,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#ef4444'
                });
                if (!confirm.isConfirmed) return;

                try {
                    const res = await fetch(buildDeleteUrl(id), {
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
                    if (!res.ok) {
                        const text = await res.text();
                        throw new Error(text || 'Error al eliminar');
                    }

                    // Actualiza estado
                    removeFromOcupadas(rect);
                    cont.remove();

                    // Si era una máquina, reconstruimos el chip (delegación ya activa)
                    const maqId = Number(cont.dataset.maquinaId);
                    const maqNombre = cont.dataset.nombre || 'Máquina';
                    const w = Number(cont.dataset.w) || 1;
                    const h = Number(cont.dataset.h) || 1;

                    if (maqId > 0 && !bandeja.querySelector(`.chip-maquina[data-id="${maqId}"]`)) {
                        const chip = document.createElement('button');
                        chip.className =
                            'chip-maquina px-3 py-2 border rounded-lg text-sm hover:bg-gray-50';
                        chip.dataset.id = String(maqId);
                        chip.dataset.nombre = maqNombre;
                        chip.dataset.w = String(w);
                        chip.dataset.h = String(h);
                        chip.title = `${maqNombre} — ${w}×${h} celdas`;
                        chip.innerHTML = `
          <span class="font-medium">${maqNombre}</span>
          <span class="ml-2 text-gray-500">${w}×${h}</span>
        `;
                        bandeja.appendChild(chip);
                        // No añadimos listeners: el bloque 1 usa DELEGACIÓN en #bandeja-maquinas
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: 'Localización eliminada',
                        timer: 1200,
                        showConfirmButton: false
                    });
                } catch (err) {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo eliminar',
                        text: 'Inténtalo de nuevo o contacta con soporte.'
                    });
                }
            }, {
                passive: false
            });
        })();
    </script>


    <script>
        (() => {
            // =========================================================
            // BLOQUE INDEPENDIENTE: Selección libre de zonas
            // (transitable / almacenamiento / carga_descarga)
            // =========================================================
            const grid = document.getElementById('cuadricula');
            const ctx = window.__LOC_CTX__;
            if (!grid || !ctx) return;

            function isMachinePlacementActive() {
                const ghost = document.getElementById('ghost');
                return ghost && !ghost.classList.contains('hidden');
            }

            function getCeldaPx() {
                const v = getComputedStyle(grid).getPropertyValue('--tam-celda').trim();
                const n = parseInt(v, 10);
                return Number.isFinite(n) && n > 0 ? n : 8;
            }
            let celdaPx = getCeldaPx();

            function puntoEnVista(ev) {
                const rect = grid.getBoundingClientRect();
                const x = Math.floor((ev.clientX - rect.left) / celdaPx) + 1;
                const y = Math.floor((ev.clientY - rect.top) / celdaPx) + 1;
                return {
                    x: Math.min(Math.max(1, x), ctx.columnasVista),
                    y: Math.min(Math.max(1, y), ctx.filasVista)
                };
            }

            function realToVistaRect(x1r, y1r, x2r, y2r) {
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
            }

            function vistaToRealRect(x1v, y1v, wv, hv) {
                const x2v = x1v + wv - 1;
                const y2v = y1v + hv - 1;
                return {
                    x1: y1v,
                    y1: x1v,
                    x2: y2v,
                    y2: x2v
                };
            }

            function normalizarVistaRect(a, b) {
                const x1 = Math.min(a.x, b.x),
                    y1 = Math.min(a.y, b.y);
                const x2 = Math.max(a.x, b.x),
                    y2 = Math.max(a.y, b.y);
                return {
                    x: x1,
                    y: y1,
                    w: (x2 - x1 + 1),
                    h: (y2 - y1 + 1)
                };
            }

            function colisionaConOcupadas(r) {
                const arr = Array.isArray(ctx.ocupadas) ? ctx.ocupadas : [];
                return arr.some(o => !(r.x2 < o.x1 || r.x1 > o.x2 || r.y2 < o.y1 || r.y1 > o.y2));
            }

            let dragging = false;
            let startCell = null;
            let selBox = null;
            let suppressNextClick = false;

            grid.addEventListener('click', (ev) => {
                if (suppressNextClick) {
                    ev.stopImmediatePropagation();
                    ev.preventDefault();
                    suppressNextClick = false;
                }
            }, true);

            grid.addEventListener('mousedown', (ev) => {
                if (ev.button !== 0) return;
                if (isMachinePlacementActive()) return;
                if (ev.target.closest('.loc-existente, .loc-delete')) return;

                dragging = true;
                startCell = puntoEnVista(ev);

                selBox = document.createElement('div');
                selBox.className = 'sel-box';
                selBox.style.position = 'absolute';
                selBox.style.pointerEvents = 'none';
                selBox.style.border = '2px dashed #111827';
                selBox.style.background = 'rgba(17,24,39,0.10)';
                selBox.style.borderRadius = '2px';
                grid.appendChild(selBox);

                updateSelBox(startCell, startCell);
                ev.preventDefault();
            });

            grid.addEventListener('mousemove', (ev) => {
                if (!dragging || !selBox) return;
                const cur = puntoEnVista(ev);
                updateSelBox(startCell, cur);
            });

            window.addEventListener('mouseup', async (ev) => {
                if (!dragging) return;
                dragging = false;

                const endCell = puntoEnVista(ev);
                const vr = normalizarVistaRect(startCell, endCell);

                if (!vr || vr.w < 1 || vr.h < 1) {
                    selBox?.remove();
                    selBox = null;
                    return;
                }

                suppressNextClick = true;
                setTimeout(() => {
                    suppressNextClick = false;
                }, 0);

                selBox?.remove();
                selBox = null;

                const rectReal = vistaToRealRect(vr.x, vr.y, vr.w, vr.h);
                const fuera = rectReal.x1 < 1 || rectReal.y1 < 1 ||
                    rectReal.x2 > ctx.columnasReales || rectReal.y2 > ctx.filasReales;
                if (fuera) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Fuera de límites',
                        text: 'La selección se sale de la nave.'
                    });
                    return;
                }
                if (colisionaConOcupadas(rectReal)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Zona ocupada',
                        text: 'Esa zona ya está ocupada.'
                    });
                    return;
                }

                // 1) Tipo (solo zonas, no "maquina")
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
                    showCancelButton: true,
                    confirmButtonText: 'Continuar'
                });
                if (!tipo) return;

                // 2) Nombre a guardar en BD (campo "nombre") y a mostrar en cuadrícula
                const nombreDefecto = (tipo.replace('_', '/').toUpperCase()) +
                    ` ${rectReal.x1}-${rectReal.y1}`;
                const {
                    value: nombreIngresado
                } = await Swal.fire({
                    title: 'Nombre/etiqueta',
                    input: 'text',
                    inputValue: nombreDefecto,
                    inputLabel: 'Texto que se mostrará en el plano (se guardará en el campo "nombre")',
                    showCancelButton: true,
                    confirmButtonText: 'Guardar'
                });
                if (nombreIngresado === undefined) return;

                const nombreFinal = (nombreIngresado ?? '').trim() || nombreDefecto;

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
                            x1: rectReal.x1,
                            y1: rectReal.y1,
                            x2: rectReal.x2,
                            y2: rectReal.y2,
                            nombre: nombreFinal // <<--- se guarda en BD en el campo "nombre"
                        })
                    });
                    if (!res.ok) throw new Error(await res.text());
                    const saved = await res.json().catch(() => null);

                    (ctx.ocupadas ||= []).push(rectReal);

                    const zona = document.createElement('div');
                    zona.className = `loc-existente loc-zona tipo-${tipo}`;
                    zona.dataset.x1 = rectReal.x1;
                    zona.dataset.y1 = rectReal.y1;
                    zona.dataset.x2 = rectReal.x2;
                    zona.dataset.y2 = rectReal.y2;
                    zona.dataset.nombre = (saved?.nombre ?? nombreFinal); // <<--- lo que queda visible
                    if (saved?.id) zona.dataset.id = saved.id;

                    const del = document.createElement('button');
                    del.type = 'button';
                    del.className = 'loc-delete';
                    del.title = `Eliminar ${zona.dataset.nombre}`;
                    del.setAttribute('aria-label', 'Eliminar localización');
                    del.textContent = '×';
                    zona.appendChild(del);

                    const label = document.createElement('span');
                    label.className = 'loc-label';
                    label.textContent = zona.dataset.nombre; // <<--- lo mostrado en cuadrícula
                    zona.appendChild(label);

                    posicionarZonaVista(zona);

                    if (tipo === 'transitable') {
                        zona.style.background = 'rgba(107,114,128,0.15)';
                        zona.style.border = '1px dashed #6b7280';
                    } else if (tipo === 'almacenamiento') {
                        zona.style.background = 'rgba(245,158,11,0.15)';
                        zona.style.border = '1px solid #f59e0b';
                    } else {
                        zona.style.background = 'rgba(59,130,246,0.15)';
                        zona.style.border = '1px solid #3b82f6';
                    }

                    grid.appendChild(zona);

                    await Swal.fire({
                        icon: 'success',
                        title: 'Zona creada',
                        timer: 1200,
                        showConfirmButton: false
                    });
                } catch (err) {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo guardar',
                        text: 'Revisa la conexión o vuelve a intentarlo.'
                    });
                }
            });

            function updateSelBox(a, b) {
                const r = normalizarVistaRect(a, b);
                selBox.style.left = ((r.x - 1) * celdaPx) + 'px';
                selBox.style.top = ((r.y - 1) * celdaPx) + 'px';
                selBox.style.width = (r.w * celdaPx) + 'px';
                selBox.style.height = (r.h * celdaPx) + 'px';
            }

            function posicionarZonaVista(el) {
                const x1 = Number(el.dataset.x1),
                    y1 = Number(el.dataset.y1);
                const x2 = Number(el.dataset.x2),
                    y2 = Number(el.dataset.y2);
                const v = realToVistaRect(x1, y1, x2, y2);
                el.style.position = 'absolute';
                el.style.left = ((v.x - 1) * celdaPx) + 'px';
                el.style.top = ((v.y - 1) * celdaPx) + 'px';
                el.style.width = (v.w * celdaPx) + 'px';
                el.style.height = (v.h * celdaPx) + 'px';
            }

            window.addEventListener('resize', () => {
                celdaPx = getCeldaPx();
                document.querySelectorAll('.loc-zona').forEach(posicionarZonaVista);
            }, {
                passive: true
            });
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('toggle-detalles');
            const grid = document.getElementById('cuadricula');

            if (!toggle || !grid) return;

            toggle.addEventListener('change', () => {
                if (toggle.checked) {
                    grid.classList.remove('ocultar-detalles');
                } else {
                    grid.classList.add('ocultar-detalles');
                }
            });
        });
    </script>

    <script>
        (() => {
            // ======= Config / refs =======
            const grid = document.getElementById('cuadricula');
            if (!grid) return;

            const CELL_METERS = 0.5; // cada celda = 0.5 m
            const ghost = document.getElementById('ghost');

            // UI flotante
            const meter = document.createElement('div');
            meter.id = 'medidor-area';
            meter.style.position = 'fixed';
            meter.style.zIndex = '9999';
            meter.style.pointerEvents = 'none';
            meter.style.padding = '6px 8px';
            meter.style.borderRadius = '6px';
            meter.style.boxShadow = '0 4px 10px rgba(0,0,0,.12)';
            meter.style.background = 'rgba(17,24,39,.92)'; // gris muy oscuro
            meter.style.color = '#fff';
            meter.style.fontSize = '12px';
            meter.style.lineHeight = '1.15';
            meter.style.whiteSpace = 'nowrap';
            meter.style.transform = 'translate(12px, 12px)'; // pequeño offset del cursor
            meter.style.display = 'none';
            meter.innerHTML = '—';
            document.body.appendChild(meter);

            // helpers
            const getCeldaPx = () => {
                const v = getComputedStyle(grid).getPropertyValue('--tam-celda').trim();
                const n = parseInt(v, 10);
                return Number.isFinite(n) && n > 0 ? n : 8;
            };

            const isGhostActive = () => ghost && !ghost.classList.contains('hidden');
            const getSelBox = () => document.querySelector('.sel-box');

            // vista -> real: w_real = h_vista ; h_real = w_vista
            const vistaToRealWH = (wv, hv) => ({
                w: hv,
                h: wv
            });

            let celdaPx = getCeldaPx();
            let raf = null;
            let mouse = {
                x: 0,
                y: 0
            };

            // formatea con 2 decimales pero sin ceros sobrantes
            const fmt = (n) => {
                const s = (Math.round(n * 100) / 100).toFixed(2);
                return s.replace(/\.?0+$/, '');
            };

            // pinta el tooltip según haya ghost o sel-box
            const tick = () => {
                raf = null;
                celdaPx = getCeldaPx();

                // 1) ¿selección libre activa?
                const selBox = getSelBox();
                if (selBox) {
                    const wCellsVista = Math.max(1, Math.round(selBox.offsetWidth / celdaPx));
                    const hCellsVista = Math.max(1, Math.round(selBox.offsetHeight / celdaPx));
                    const {
                        w: wRealCells,
                        h: hRealCells
                    } = vistaToRealWH(wCellsVista, hCellsVista);

                    const anchoM = wRealCells * CELL_METERS;
                    const largoM = hRealCells * CELL_METERS;
                    const areaM2 = anchoM * largoM;

                    meter.innerHTML =
                        `<strong>Selección</strong><br>` +
                        `${fmt(anchoM)} m × ${fmt(largoM)} m<br>` +
                        `${fmt(areaM2)} m²`;
                    meter.style.left = mouse.x + 'px';
                    meter.style.top = mouse.y + 'px';
                    meter.style.display = 'block';
                    return;
                }

                // 2) ¿ghost de máquina visible?
                if (isGhostActive()) {
                    const wVistaCells = Math.max(1, Math.round(ghost.offsetWidth / celdaPx));
                    const hVistaCells = Math.max(1, Math.round(ghost.offsetHeight / celdaPx));
                    const {
                        w: wRealCells,
                        h: hRealCells
                    } = vistaToRealWH(wVistaCells, hVistaCells);

                    const anchoM = wRealCells * CELL_METERS;
                    const largoM = hRealCells * CELL_METERS;
                    const areaM2 = anchoM * largoM;

                    meter.innerHTML =
                        `<strong>Máquina (ghost)</strong><br>` +
                        `${fmt(anchoM)} m × ${fmt(largoM)} m<br>` +
                        `${fmt(areaM2)} m²`;
                    meter.style.left = mouse.x + 'px';
                    meter.style.top = mouse.y + 'px';
                    meter.style.display = 'block';
                    return;
                }

                // nada activo -> ocultar
                meter.style.display = 'none';
            };

            // mover tooltip con el ratón y medir en rAF
            const onMove = (ev) => {
                mouse.x = ev.clientX;
                mouse.y = ev.clientY;
                if (!raf) raf = requestAnimationFrame(tick);
            };

            // también recalcula al redimensionar (cambia celdaPx)
            const onResize = () => {
                if (!raf) raf = requestAnimationFrame(tick);
            };

            // mostrar/ocultar por eventos propios de tu flujo
            grid.addEventListener('mousemove', onMove, {
                passive: true
            });
            window.addEventListener('resize', onResize, {
                passive: true
            });

            // cuando aparezca/desaparezca sel-box o cambie ghost, forzamos un tick
            const mo = new MutationObserver(() => {
                if (!raf) raf = requestAnimationFrame(tick);
            });
            mo.observe(grid, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style']
            });

            // pequeña mejora: ocultar si el cursor sale del grid y no hay ghost
            grid.addEventListener('mouseleave', () => {
                if (!isGhostActive()) meter.style.display = 'none';
            });

            // primer cálculo
            tick();
        })();
    </script>

    <script>
        (() => {
            // =========================================================
            // MOVER + ROTAR localizaciones existentes (maquina/zonas)
            // (controlando click-through de SweetAlert y congelando el ghost)
            // =========================================================
            const grid = document.getElementById('cuadricula');
            const ctx = window.__LOC_CTX__ || {};
            if (!grid || !ctx) return;

            const UPDATE_TPL = ctx.updateUrlTemplate || '/localizaciones/:id';
            const buildUpdateUrl = (id) => UPDATE_TPL.replace(':id', String(id));

            // ---- Estado para controlar el click-through del modal ----
            let isModalOpen = false; // true mientras el Swal está abierto
            let suprimeSiguienteDown = 0; // timestamp hasta el que ignoramos el próximo mousedown
            let congelado = false; // cuando true, el ghost NO se actualiza con el mousemove

            let isConfirming = false;

            // (Captura de clicks en fase de captura para tragárnoslos si hay modal)
            document.addEventListener('mousedown', (e) => {
                if (isModalOpen) e.stopPropagation();
            }, true);

            // --- Geometría y helpers ---
            const getCeldaPx = () => {
                const v = getComputedStyle(grid).getPropertyValue('--tam-celda').trim();
                const n = parseInt(v, 10);
                return Number.isFinite(n) && n > 0 ? n : 8;
            };
            let celdaPx = getCeldaPx();

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
                }; // transpuesto
            };
            const vistaToRealRect = (xv, yv, wv, hv) => {
                const x2v = xv + wv - 1,
                    y2v = yv + hv - 1;
                return {
                    x1: yv,
                    y1: xv,
                    x2: y2v,
                    y2: x2v
                }; // transpuesto
            };
            const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

            const puntoEnVista = (ev) => {
                const r = grid.getBoundingClientRect();
                const x = Math.floor((ev.clientX - r.left) / celdaPx) + 1;
                const y = Math.floor((ev.clientY - r.top) / celdaPx) + 1;
                return {
                    x: clamp(x, 1, ctx.columnasVista),
                    y: clamp(y, 1, ctx.filasVista)
                };
            };

            const rectsIguales = (a, b) => a.x1 === b.x1 && a.y1 === b.y1 && a.x2 === b.x2 && a.y2 === b.y2;

            const colisiona = (rectReal, selfRect = null) => {
                const occ = Array.isArray(ctx.ocupadas) ? ctx.ocupadas : [];
                return occ.some(o => {
                    if (selfRect && rectsIguales(o, selfRect)) return false;
                    // AABB overlap
                    return !(rectReal.x2 < o.x1 || rectReal.x1 > o.x2 || rectReal.y2 < o.y1 || rectReal.y1 >
                        o.y2);
                });
            };

            const inBounds = (r) => (
                r.x1 >= 1 && r.y1 >= 1 &&
                r.x2 <= ctx.columnasReales &&
                r.y2 <= ctx.filasReales
            );

            const actualizarCeldaPx = () => {
                celdaPx = getCeldaPx();
            };

            // --- Estado de interacción ---
            let active =
                null; // { el, id, nombre, startVista, offVista, wVista, hVista, selfRectReal, nextVista, nextReal, validNext }
            let dragGhost = null;

            // Ghost
            const ensureGhost = () => {
                if (!dragGhost) {
                    dragGhost = document.createElement('div');
                    dragGhost.id = 'drag-ghost';
                    dragGhost.style.position = 'absolute';
                    dragGhost.style.pointerEvents = 'none';
                    dragGhost.style.border = '2px dashed #22c55e';
                    dragGhost.style.background = 'rgba(34,197,94,0.15)';
                    dragGhost.style.borderRadius = '2px';
                    dragGhost.style.zIndex = '60';
                    grid.appendChild(dragGhost);
                }
            };
            const paintGhostVista = (xv, yv, wv, hv, ok = true) => {
                ensureGhost();
                dragGhost.style.left = ((xv - 1) * celdaPx) + 'px';
                dragGhost.style.top = ((yv - 1) * celdaPx) + 'px';
                dragGhost.style.width = (wv * celdaPx) + 'px';
                dragGhost.style.height = (hv * celdaPx) + 'px';
                dragGhost.style.borderColor = ok ? '#22c55e' : '#ef4444';
                dragGhost.style.background = ok ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.15)';
                dragGhost.style.display = 'block';
            };
            const hideGhost = () => {
                if (dragGhost) dragGhost.style.display = 'none';
            };

            // Posicionar un bloque según dataset real
            const aplicarPosicionVistaAlDOM = (el) => {
                const x1 = Number(el.dataset.x1),
                    y1 = Number(el.dataset.y1);
                const x2 = Number(el.dataset.x2),
                    y2 = Number(el.dataset.y2);
                const v = realToVistaRect(x1, y1, x2, y2);
                el.style.left = ((v.x - 1) * celdaPx) + 'px';
                el.style.top = ((v.y - 1) * celdaPx) + 'px';
                el.style.width = (v.w * celdaPx) + 'px';
                el.style.height = (v.h * celdaPx) + 'px';
            };

            // Ocupadas
            const removeFromOcupadas = (r) => {
                if (!Array.isArray(ctx.ocupadas)) return;
                const i = ctx.ocupadas.findIndex(o => rectsIguales(o, r));
                if (i !== -1) ctx.ocupadas.splice(i, 1);
            };
            const addToOcupadas = (r) => {
                (ctx.ocupadas ||= []).push(r);
            };

            // --- MOUSE DOWN sobre una .loc-existente (salvo botón X) ---
            grid.addEventListener('mousedown', (ev) => {
                // suprimir click-through posterior al cierre del modal
                if (performance.now() < suprimeSiguienteDown) return;
                if (ev.button !== 0) return;
                const el = ev.target.closest('.loc-existente');
                if (!el) return;
                if (ev.target.closest('.loc-delete')) return; // no interceptar la X

                actualizarCeldaPx();

                const id = Number(el.dataset.id);
                const nom = el.dataset.nombre || 'Localización';
                const x1 = Number(el.dataset.x1),
                    y1 = Number(el.dataset.y1);
                const x2 = Number(el.dataset.x2),
                    y2 = Number(el.dataset.y2);
                const maqId = Number(el.dataset.maquinaId || 0);
                const tipo = (el.dataset.tipo || (maqId > 0 ? 'maquina' : 'zona'));

                const v = realToVistaRect(x1, y1, x2, y2);
                const mouseVista = puntoEnVista(ev);
                const offVista = {
                    dx: mouseVista.x - v.x,
                    dy: mouseVista.y - v.y
                };

                active = {
                    el,
                    id,
                    nombre: nom,
                    tipo,
                    maqId,
                    startVista: {
                        x: v.x,
                        y: v.y
                    },
                    offVista,
                    wVista: v.w,
                    hVista: v.h,
                    selfRectReal: {
                        x1,
                        y1,
                        x2,
                        y2
                    }
                };

                paintGhostVista(v.x, v.y, v.w, v.h, true);
                ev.preventDefault();
            });

            // --- MOUSE MOVE: sigue al ratón (salvo si está congelado) ---
            window.addEventListener('mousemove', (ev) => {
                if (!active || congelado) return;
                actualizarCeldaPx();

                const m = puntoEnVista(ev);
                let nx = m.x - active.offVista.dx;
                let ny = m.y - active.offVista.dy;

                nx = clamp(nx, 1, ctx.columnasVista - active.wVista + 1);
                ny = clamp(ny, 1, ctx.filasVista - active.hVista + 1);

                const nr = vistaToRealRect(nx, ny, active.wVista, active.hVista);
                const okBounds = inBounds(nr);
                const okCol = !colisiona(nr, active.selfRectReal);
                paintGhostVista(nx, ny, active.wVista, active.hVista, okBounds && okCol);

                active.nextVista = {
                    x: nx,
                    y: ny
                };
                active.nextReal = nr;
                active.validNext = okBounds && okCol;
            }, {
                passive: true
            });

            // --- ROTAR con 'r' ---
            document.addEventListener('keydown', (ev) => {
                if (!active || congelado) return;
                if (ev.isComposing) return;
                if (['input', 'textarea', 'select'].includes((ev.target.tagName || '').toLowerCase())) return;

                if (ev.key === 'r' || ev.key === 'R') {
                    ev.preventDefault();

                    [active.wVista, active.hVista] = [active.hVista, active.wVista];

                    const baseVista = active.nextVista || active.startVista;
                    let nx = baseVista.x,
                        ny = baseVista.y;
                    nx = clamp(nx, 1, ctx.columnasVista - active.wVista + 1);
                    ny = clamp(ny, 1, ctx.filasVista - active.hVista + 1);

                    const nr = vistaToRealRect(nx, ny, active.wVista, active.hVista);
                    const okBounds = inBounds(nr);
                    const okCol = !colisiona(nr, active.selfRectReal);

                    paintGhostVista(nx, ny, active.wVista, active.hVista, (okBounds && okCol));
                    active.nextVista = {
                        x: nx,
                        y: ny
                    };
                    active.nextReal = nr;
                    active.validNext = okBounds && okCol;
                }

                if (ev.key === 'Escape') {
                    hideGhost();
                    active = null;
                }
            });

            // --- MOUSE UP: confirmar y guardar ---
            window.addEventListener('mouseup', async () => {
                if (!active || isConfirming) return;

                const v = active.nextVista || active.startVista;
                const r = active.nextReal || active.selfRectReal;

                const cambiado = !rectsIguales(r, active.selfRectReal);
                if (!cambiado) {
                    hideGhost();
                    active = null;
                    return;
                }

                if (!active.validNext) {
                    hideGhost();
                    active = null;
                    await Swal.fire({
                        icon: 'error',
                        title: 'Posición no válida',
                        text: 'Fuera de límites o colisiona con otra localización.'
                    });
                    return;
                }

                isConfirming = true;
                congelado = true;
                isModalOpen = true;

                const confirm = await Swal.fire({
                    icon: 'question',
                    title: 'Confirmar movimiento',
                    html: `¿Mover <b>${active.nombre}</b> a (${r.x1},${r.y1}) → (${r.x2},${r.y2})?` +
                        `<br><small>Pulsa "r" mientras arrastras para rotar.</small>`,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar'
                });

                isConfirming = false;
                isModalOpen = false;
                congelado = false;

                // Suprimir mousedown justo después de cerrar el modal (previene doble disparo)
                suprimeSiguienteDown = performance.now() + 200;

                if (!confirm.isConfirmed) {
                    hideGhost();
                    active = null;
                    return;
                }

                try {
                    const res = await fetch(buildUpdateUrl(active.id), {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            x1: r.x1,
                            y1: r.y1,
                            x2: r.x2,
                            y2: r.y2
                        })
                    });

                    if (!res.ok) throw new Error(await res.text());

                    // ✅ Actualizar
                    removeFromOcupadas(active.selfRectReal);
                    addToOcupadas(r);
                    active.el.dataset.x1 = String(r.x1);
                    active.el.dataset.y1 = String(r.y1);
                    active.el.dataset.x2 = String(r.x2);
                    active.el.dataset.y2 = String(r.y2);
                    aplicarPosicionVistaAlDOM(active.el);

                    hideGhost();
                    active = null;

                } catch (err) {
                    console.error('❌ Error en UPDATE:', err);
                    hideGhost();
                    active = null;
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo actualizar',
                        text: 'Revisa la conexión o inténtalo de nuevo.'
                    });
                }
            });


            // Recalcular tamaños al redimensionar
            window.addEventListener('resize', () => {
                actualizarCeldaPx();
                document.querySelectorAll('.loc-existente').forEach(aplicarPosicionVistaAlDOM);
                if (active && active.nextVista && !congelado) {
                    paintGhostVista(active.nextVista.x, active.nextVista.y, active.wVista, active.hVista, !!
                        active.validNext);
                }
            }, {
                passive: true
            });

            // Pintado inicial
            document.querySelectorAll('.loc-existente').forEach(aplicarPosicionVistaAlDOM);
        })();
    </script>


</x-app-layout>
