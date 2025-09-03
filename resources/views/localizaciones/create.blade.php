<x-app-layout>
    {{-- Cabecera --}}
    <x-slot name="title">Mapa de Ubicaciones — Colocar máquinas</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar mapa de {{ $obraActiva->obra ?? 'localizaciones' }}
        </h2>
        <p class="text-sm text-gray-500">
            Celda = 0,5 m.
            Ancho: {{ $dimensiones['ancho'] }} m ({{ $columnasReales }} columnas),
            Largo: {{ $dimensiones['largo'] }} m ({{ $filasReales }} filas).
            Vista: {{ $columnasVista }}×{{ $filasVista }} (lado largo en horizontal).
        </p>
    </x-slot>

    {{-- Menús --}}
    <x-menu.localizaciones.menu-localizaciones-vistas :obra-actual-id="$obraActualId ?? null" route-index="localizaciones.index"
        route-create="localizaciones.create" />
    <x-menu.localizaciones.menu-localizaciones-naves :obras="$obras" :obra-actual-id="$obraActualId ?? null" />

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
                    <div class="loc-existente" data-x1="{{ $loc['x1'] }}" data-y1="{{ $loc['y1'] }}"
                        data-x2="{{ $loc['x2'] }}" data-y2="{{ $loc['y2'] }}"
                        data-nombre="{{ $loc['nombre'] }}">
                        {{ $loc['nombre'] }}
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
    <link rel="stylesheet" href="{{ asset('css/localizaciones/styleLoc.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Contexto listo del backend --}}
    <script>
        window.__LOC_CTX__ = @json($ctx);
    </script>

    {{-- JS interacción (igual que ya tenías, sin cálculos Blade) --}}
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

            function realToVistaRect(x1r, y1r, x2r, y2r) {
                if (ctx.estaGirado) {
                    return {
                        x: y1r,
                        y: x1r,
                        w: y2r - y1r + 1,
                        h: x2r - x1r + 1
                    };
                }
                return {
                    x: x1r,
                    y: y1r,
                    w: x2r - x1r + 1,
                    h: y2r - y1r + 1
                };
            }

            function vistaToRealRect(x1v, y1v, wv, hv) {
                const x2v = x1v + wv - 1,
                    y2v = y1v + hv - 1;
                if (ctx.estaGirado) {
                    return {
                        x1: y1v,
                        y1: x1v,
                        x2: y2v,
                        y2: x2v
                    };
                }
                return {
                    x1: x1v,
                    y1: y1v,
                    x2: x2v,
                    y2: y2v
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
                        w: +btn.dataset.w,
                        h: +btn.dataset.h
                    };
                    ghostSizeVista = {
                        w: selected.w,
                        h: selected.h
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


</x-app-layout>
