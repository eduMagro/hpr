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

                {{-- overlays de localizaciones existentes --}}
                @foreach ($localizacionesConMaquina as $loc)
                    <div class="loc-existente" data-id="{{ $loc['id'] }}" data-x1="{{ $loc['x1'] }}"
                        data-y1="{{ $loc['y1'] }}" data-x2="{{ $loc['x2'] }}" data-y2="{{ $loc['y2'] }}"
                        data-nombre="{{ $loc['nombre'] }}">
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
            const chips = Array.from(document.querySelectorAll('.chip-maquina'));
            const estadoSel = document.getElementById('estado-seleccion');

            let celdaPx = 8;
            let selected = null;
            let ghostPosVista = {
                x: 1,
                y: 1
            };
            let ghostSizeVista = {
                w: 1,
                h: 1
            };

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

            chips.forEach(btn => {
                btn.addEventListener('click', () => {
                    chips.forEach(c => c.classList.remove('ring', 'ring-blue-500'));
                    btn.classList.add('ring', 'ring-blue-500');
                    selected = {
                        id: +btn.dataset.id,
                        nombre: btn.dataset.nombre,
                        w: +btn.dataset.w, // real
                        h: +btn.dataset.h // real
                    };
                    // 👇 en vista horizontal, el ghost se dibuja transpuesto
                    ghostSizeVista = {
                        w: selected.h,
                        h: selected.w
                    };
                    ghost.classList.remove('hidden');
                    estadoSel.textContent =
                        `Seleccionada: ${selected.nombre} (${selected.w}×${selected.h}). Pulsa "R" para rotar. Haz clic en la cuadrícula para colocar.`;
                    renderGhost();
                    updateGhostColor();
                });
            });


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
                return ctx.ocupadas.some(o => !(rectReal.x2 < o.x1 || rectReal.x1 > o.x2 || rectReal.y2 < o.y1 ||
                    rectReal.y1 > o.y2));
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
                estadoSel.textContent =
                    `Seleccionada: ${selected.nombre} (${ghostSizeVista.w}×${ghostSizeVista.h}). Pulsa "R" para rotar. Haz clic en la cuadrícula para colocar.`;
            }

            document.addEventListener('keydown', ev => {
                if (!selected) return;
                const tag = (ev.target.tagName || '').toLowerCase();
                if (['input', 'textarea', 'select'].includes(tag) || ev.isComposing) return;
                if (ev.key === 'r' || ev.key === 'R') {
                    ev.preventDefault();
                    rotarSeleccion();
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

                const fuera = rectReal.x1 < 1 || rectReal.y1 < 1 || rectReal.x2 > ctx.columnasReales ||
                    rectReal.y2 > ctx.filasReales;
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
                            localizacion: selected.nombre
                        })
                    });
                    if (!res.ok) throw new Error(await res.text());

                    ctx.ocupadas.push(rectReal);
                    const div = document.createElement('div');
                    div.className = 'loc-existente';
                    div.dataset.x1 = rectReal.x1;
                    div.dataset.y1 = rectReal.y1;
                    div.dataset.x2 = rectReal.x2;
                    div.dataset.y2 = rectReal.y2;
                    div.textContent = selected.nombre;
                    grid.appendChild(div);
                    renderExistentes();

                    const btn = chips.find(c => +c.dataset.id === selected.id);
                    if (btn) btn.remove();
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
            const ctx = window.__LOC_CTX__ || {};
            const deleteTemplate = ctx.deleteUrlTemplate || '/localizaciones/:id'; // <-- define esto en tu controlador

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

            // Delegación de eventos SOLO para el botón .loc-delete
            grid.addEventListener('click', async (ev) => {
                const btn = ev.target.closest('.loc-delete');
                if (!btn) return;

                ev.stopPropagation(); // no dispares el click de colocar máquina
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

                    // Actualiza modelo de ocupadas y DOM
                    removeFromOcupadas(rect);
                    cont.remove();
                    // tras cont.remove();
                    const bandeja = document.getElementById('bandeja-maquinas');
                    const maqId = Number(cont.dataset.maquinaId);
                    const maqNombre = cont.dataset.nombre || 'Máquina';
                    const w = Number(cont.dataset.w) || 1;
                    const h = Number(cont.dataset.h) || 1;

                    if (!bandeja.querySelector(`.chip-maquina[data-id="${maqId}"]`)) {
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

                        // vuelve a enganchar tu handler de selección
                        chip.addEventListener('click', () => {
                            document.querySelectorAll('.chip-maquina').forEach(c => c.classList
                                .remove('ring', 'ring-blue-500'));
                            chip.classList.add('ring', 'ring-blue-500');
                            selected = {
                                id: maqId,
                                nombre: maqNombre,
                                w,
                                h
                            }; // reales
                            ghostSizeVista = {
                                w: h,
                                h: w
                            }; // 👈 transpuesto para la vista
                            ghost.classList.remove('hidden');
                            estadoSel.textContent =
                                `Seleccionada: ${maqNombre} (${w}×${h}). Pulsa "R" para rotar. Haz clic en la cuadrícula para colocar.`;
                            renderGhost();
                            updateGhostColor();
                        });

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

</x-app-layout>
