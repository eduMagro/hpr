{{-- resources/views/components/mapa-component.blade.php --}}
@props([
    'ctx',
    'localizacionesZonas' => [],
    'localizacionesMaquinas' => [],
    'paquetesConLocalizacion' => [],
    'dimensiones' => null,
    'obraActualId' => null,

    // Opciones visuales / comportamiento
    'showControls' => false, // botones Horizontal/Vertical + input QR
    'showScanResult' => false, // panel de resultado del escaneo
    'mostrarObra' => false,
    'height' => '',

    // Rutas para AJAX
    'rutaPaquete' => null, // route('paquetes.tamaño')
    'rutaGuardar' => null, // route('localizaciones.storePaquete')
])

@php
    $mapId = 'mapa-' . uniqid();

    $isVertical = !empty($ctx['estaGirado']);
    $colsReales = $ctx['columnasReales'] ?? 0;
    $filasReales = $ctx['filasReales'] ?? 0;

    $columnasVista = $isVertical ? $colsReales : $filasReales;
    $filasVista = $isVertical ? $filasReales : $colsReales;
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    @if ($mostrarObra)
        {{-- Cabecera opcional con info de dimensiones --}}
        @if ($dimensiones)
            <div class="bg-white rounded-lg shadow-sm p-3 mb-3">
                <h2
                    class="font-semibold text-base sm:text-lg md:text-xl text-gray-800 leading-tight">
                    Mapa de {{ $dimensiones['obra'] ?? 'localizaciones' }}
                </h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">
                    Celda = 0,5 m.
                    Ancho: {{ $dimensiones['ancho'] }} m
                    ({{ $colsReales }} cols),
                    Largo: {{ $dimensiones['largo'] }} m
                    ({{ $filasReales }} filas).
                    Vista: {{ $columnasVista }}×{{ $filasVista }}
                    ({{ $isVertical ? 'vertical' : 'horizontal' }}).
                </p>
            </div>
        @endif
    @endif

    {{-- Controles de orientación + input QR (opcionales) --}}
    @if ($showControls)
        @php
            $qsBase = request()->except('orientacion');
            $urlH =
                request()->url() .
                '?' .
                http_build_query(
                    array_merge($qsBase, ['orientacion' => 'horizontal']),
                );
            $urlV =
                request()->url() .
                '?' .
                http_build_query(
                    array_merge($qsBase, ['orientacion' => 'vertical']),
                );
        @endphp

        <div class="mb-3">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $urlH }}"
                    class="px-3 py-1.5 rounded border text-sm {{ !$isVertical ? 'bg-gray-800 text-white' : 'hover:bg-gray-50' }}">
                    Horizontal
                </a>
                <a href="{{ $urlV }}"
                    class="px-3 py-1.5 rounded border text-sm {{ $isVertical ? 'bg-gray-800 text-white' : 'hover:bg-gray-50' }}">
                    Vertical
                </a>

                <input id="{{ $mapId }}-input-etiqueta-sub"
                    type="text" inputmode="text" autocomplete="off"
                    placeholder="Escanea/pega etiqueta_sub_id (ETQ123456.01)"
                    class="w-full sm:w-auto sm:min-w-[16rem] md:min-w-[18rem] flex-1 px-3 py-1.5 rounded border text-sm focus:ring focus:outline-none"
                    aria-label="Escanear código QR de subetiqueta" />
            </div>
        </div>
    @endif

    {{-- Escenario + Cuadrícula --}}
    <div>
        <div id="escenario-cuadricula" data-mapa-canvas
            data-mapa-id="{{ $mapId }}" {{-- opcional, por si quieres identificar la instancia --}}
            class="{{ $isVertical ? 'orient-vertical' : 'orient-horizontal' }} overflow-auto relative"
            data-nave-id="{{ $obraActualId ?? ($ctx['naveId'] ?? '') }}"
            data-ruta-paquete="{{ $rutaPaquete }}"
            data-ruta-guardar="{{ $rutaGuardar }}"
            style="
                --cols: {{ $isVertical ? $colsReales : $filasReales }};
                --rows: {{ $isVertical ? $filasReales : $colsReales }};
                height: {{ $height }};
            ">
            <div id="cuadricula" class="relative"
                aria-label="Cuadrícula de la nave">
                {{-- Máquinas --}}
                @foreach ($localizacionesMaquinas as $loc)
                    <div class="loc-existente loc-maquina"
                        data-id="{{ $loc['id'] }}"
                        data-x1="{{ $loc['x1'] }}"
                        data-y1="{{ $loc['y1'] }}"
                        data-x2="{{ $loc['x2'] }}"
                        data-y2="{{ $loc['y2'] }}"
                        data-maquina-id="{{ $loc['maquina_id'] }}"
                        data-nombre="{{ $loc['nombre'] }}">
                        <span class="loc-label">{{ $loc['nombre'] }}</span>
                    </div>
                @endforeach

                {{-- Zonas --}}
                @foreach ($localizacionesZonas as $loc)
                    @php
                        $tipo = str_replace(
                            '-',
                            '_',
                            $loc['tipo'] ?? 'transitable',
                        );
                    @endphp
                    <div class="loc-existente loc-zona tipo-{{ $tipo }}"
                        data-id="{{ $loc['id'] }}"
                        data-x1="{{ $loc['x1'] }}"
                        data-y1="{{ $loc['y1'] }}"
                        data-x2="{{ $loc['x2'] }}"
                        data-y2="{{ $loc['y2'] }}"
                        data-nombre="{{ $loc['nombre'] }}"
                        data-tipo="{{ $tipo }}">
                        <span class="loc-label">{{ $loc['nombre'] }}</span>
                    </div>
                @endforeach

                {{-- Paquetes existentes --}}
                @foreach ($paquetesConLocalizacion as $paquete)
                    <div class="loc-existente loc-paquete tipo-{{ $paquete['tipo_contenido'] ?? 'mixto' }}"
                        data-id="{{ $paquete['id'] }}"
                        data-paquete-id="{{ $paquete['id'] }}"
                        data-codigo="{{ $paquete['codigo'] }}"
                        data-x1="{{ $paquete['x1'] }}"
                        data-y1="{{ $paquete['y1'] }}"
                        data-x2="{{ $paquete['x2'] }}"
                        data-y2="{{ $paquete['y2'] }}">
                        <span class="loc-label">{{ $paquete['codigo'] }}</span>
                    </div>
                @endforeach
            </div>

        </div>
    </div>

    {{-- Panel de resultados del escaneo (opcional) --}}
    @if ($showScanResult)
        <div id="{{ $mapId }}-scan-result"
            class="mt-3 hidden border rounded-lg p-3 bg-white text-sm">
            <div class="font-semibold mb-1">Resultado del paquete</div>
            <div id="{{ $mapId }}-scan-result-body"
                class="text-gray-700"></div>
        </div>
    @endif
</div>

{{-- CSS del mapa, solo una vez --}}
@once
    <link rel="stylesheet"
        href="{{ asset('css/localizaciones/styleLocIndex.css') }}">
@endonce

{{-- Scripts específicos de ESTA instancia de mapa --}}
@push('scripts')
    <script>
        (() => {
            const ctx = @json($ctx);
            const escenario = document.getElementById('escenario-cuadricula');
            const grid = document.getElementById('cuadricula');

            if (!escenario || !grid) return;

            const W = ctx.columnasReales;
            const H = ctx.filasReales;
            const isVertical = !!ctx.estaGirado;

            const viewCols = isVertical ? W : H;
            const viewRows = isVertical ? H : W;

            function getCeldaPx() {
                const v = getComputedStyle(grid).getPropertyValue('--tam-celda')
                    .trim();
                const n = parseInt(v, 10);
                return Number.isFinite(n) && n > 0 ? n : 8;
            }

            function mapPointToView(x, y) {
                if (isVertical) {
                    return {
                        x,
                        y: (H - y + 1)
                    };
                }
                return {
                    x: y,
                    y: x
                };
            }

            function realToViewRect(x1r, y1r, x2r, y2r) {
                const x1 = Math.min(x1r, x2r),
                    x2 = Math.max(x1r, x2r);
                const y1 = Math.min(y1r, y2r),
                    y2 = Math.max(y1r, y2r);

                const p1 = mapPointToView(x1, y1);
                const p2 = mapPointToView(x2, y1);
                const p3 = mapPointToView(x1, y2);
                const p4 = mapPointToView(x2, y2);

                const xs = [p1.x, p2.x, p3.x, p4.x];
                const ys = [p1.y, p2.y, p3.y, p4.y];

                const minX = Math.min(...xs),
                    maxX = Math.max(...xs);
                const minY = Math.min(...ys),
                    maxY = Math.max(...ys);

                return {
                    x: minX,
                    y: minY,
                    w: (maxX - minX + 1),
                    h: (maxY - minY + 1),
                };
            }

            function renderExistentes() {
                const celdaPx = getCeldaPx();
                grid.querySelectorAll('.loc-existente').forEach(el => {
                    const {
                        x,
                        y,
                        w,
                        h
                    } = realToViewRect(
                        +el.dataset.x1, +el.dataset.y1,
                        +el.dataset.x2, +el.dataset.y2
                    );
                    el.style.left = ((x - 1) * celdaPx) + 'px';
                    el.style.top = ((y - 1) * celdaPx) + 'px';
                    el.style.width = (w * celdaPx) + 'px';
                    el.style.height = (h * celdaPx) + 'px';
                });
            }

            function ajustarTamCelda() {
                const anchoDisp = escenario.clientWidth - 12;
                const altoDisp = window.innerHeight - escenario
                    .getBoundingClientRect().top - 24;

                const tamPorAncho = Math.floor(anchoDisp / viewCols);
                const tamPorAlto = Math.floor(altoDisp / viewRows);
                const tamCelda = Math.max(4, Math.min(tamPorAncho, tamPorAlto));

                grid.style.setProperty('--tam-celda', tamCelda + 'px');
                grid.style.width = (tamCelda * viewCols) + 'px';
                grid.style.height = (tamCelda * viewRows) + 'px';

                renderExistentes();
            }

            ajustarTamCelda();
            let pendiente = false;
            window.addEventListener('resize', () => {
                if (pendiente) return;
                pendiente = true;
                requestAnimationFrame(() => {
                    ajustarTamCelda();
                    pendiente = false;
                });
            }, {
                passive: true
            });

            // ----------------- API para el panel lateral -----------------
            function mapViewToReal(xv, yv) {
                if (isVertical) {
                    return {
                        x: xv,
                        y: (H - yv + 1)
                    };
                }
                return {
                    x: yv,
                    y: xv
                };
            }

            function viewFromReal(x, y) {
                // inversa de mapViewToReal, para scroll a coordenadas reales
                if (isVertical) {
                    // real -> vista vertical: (x, H - y + 1)
                    return {
                        xv: x,
                        yv: (H - y + 1)
                    };
                }
                // real -> vista horizontal: (y, x)
                return {
                    xv: y,
                    yv: x
                };
            }

            function getCeldaPosition(xReal, yReal) {
                const {
                    xv,
                    yv
                } = viewFromReal(xReal, yReal);
                const celdaPx = getCeldaPx();
                return {
                    left: (xv - 1) * celdaPx,
                    top: (yv - 1) * celdaPx,
                };
            }

            function setHighlight(id) {
                grid.querySelectorAll('.loc-paquete').forEach(el => {
                    el.classList.remove('loc-paquete--highlight');
                });
                const target = grid.querySelector(
                    '.loc-paquete[data-paquete-id="' + id + '"]');
                if (target) {
                    target.classList.add('loc-paquete--highlight');
                }
            }

            function clearHighlight() {
                grid.querySelectorAll('.loc-paquete').forEach(el => {
                    el.classList.remove('loc-paquete--highlight');
                });
            }

            function focusPaquete(xReal, yReal) {
                const pos = getCeldaPosition(xReal, yReal);
                const centerX = pos.left - escenario.clientWidth / 2;
                const centerY = pos.top - escenario.clientHeight / 2;
                escenario.scrollTo({
                    left: Math.max(0, centerX),
                    top: Math.max(0, centerY),
                    behavior: 'smooth',
                });
            }

            // Exponer instancia en el canvas
            escenario.mapaInstance = {
                setHighlight,
                clearHighlight,
                focusPaquete,
            };

        })();
    </script>

    {{-- Script de ghost + escaneo sólo si hay controles y rutas --}}
    @if ($showControls && $rutaPaquete && $rutaGuardar)
        <script>
            (() => {
                const escenario = document.getElementById('escenario-cuadricula');
                const grid = document.getElementById('cuadricula');

                if (!escenario || !grid) return;

                const ctx = @json($ctx);
                const isVertical = !!ctx.estaGirado;
                const W = ctx.columnasReales;
                const H = ctx.filasReales;

                const viewCols = isVertical ? W : H;
                const viewRows = isVertical ? H : W;

                function getCeldaPx() {
                    const v = getComputedStyle(grid).getPropertyValue('--tam-celda')
                        .trim();
                    const n = parseInt(v, 10);
                    return Number.isFinite(n) && n > 0 ? n : 8;
                }

                function mapViewToReal(xv, yv) {
                    if (isVertical) {
                        return {
                            x: xv,
                            y: (H - yv + 1)
                        };
                    }
                    return {
                        x: yv,
                        y: xv
                    };
                }

                let ghost = null;
                let ghostActions = null;
                let celdaPx = getCeldaPx();
                let gWidthCells = 1;
                let gHeightCells = 2;
                let gX = 1;
                let gY = 1;
                let paqueteMeta = null;

                function ensureGhost() {
                    if (ghost) return;

                    ghost = document.createElement('div');
                    ghost.id = '{{ $mapId }}-paquete-ghost';
                    ghost.innerHTML = `<div class="ghost-label"></div>`;
                    grid.appendChild(ghost);

                    ghostActions = document.createElement('div');
                    ghostActions.id = '{{ $mapId }}-ghost-actions';
                    ghostActions.innerHTML = `
          <button class="ghost-btn cancel" id="{{ $mapId }}-btn-cancel-ghost" title="Cancelar (Esc)" aria-label="Cancelar">
            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>

          <button class="ghost-btn rotate" id="{{ $mapId }}-btn-rotate-ghost" title="Voltear (R)" aria-label="Voltear">
            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 4v4l3-2-3-2zM4 12a8 8 0 1 1 8 8" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>

          <button class="ghost-btn confirm" id="{{ $mapId }}-btn-place-ghost" title="Asignar aquí (Enter)" aria-label="Asignar">
            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="9" stroke="#22c55e" stroke-width="2"/>
              <path d="M8 12l3 3 5-6" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        `;
                    grid.appendChild(ghostActions);

                    document.getElementById('{{ $mapId }}-btn-cancel-ghost')
                        .addEventListener('click', () => {
                            ghost.remove();
                            ghost = null;
                            ghostActions.remove();
                            ghostActions = null;
                            paqueteMeta = null;
                        });
                    document.getElementById('{{ $mapId }}-btn-place-ghost')
                        .addEventListener('click', onPlaceGhost);
                    document.getElementById('{{ $mapId }}-btn-rotate-ghost')
                        .addEventListener('click', rotateGhostKeepCenter);

                    enableDrag();
                }

                function layoutGhost() {
                    if (!ghost) return;
                    celdaPx = getCeldaPx();

                    gX = Math.max(1, Math.min(viewCols - gWidthCells + 1, gX));
                    gY = Math.max(1, Math.min(viewRows - gHeightCells + 1, gY));

                    ghost.style.left = ((gX - 1) * celdaPx) + 'px';
                    ghost.style.top = ((gY - 1) * celdaPx) + 'px';
                    ghost.style.width = (gWidthCells * celdaPx) + 'px';
                    ghost.style.height = (gHeightCells * celdaPx) + 'px';

                    const label = ghost.querySelector('.ghost-label');
                    if (label && paqueteMeta) {
                        label.textContent =
                            `${paqueteMeta.codigo} · ${paqueteMeta.longitud.toFixed(2)} m · ${gWidthCells}×${gHeightCells} celdas`;
                    }

                    if (ghostActions) {
                        ghostActions.style.left = ghost.style.left;
                        ghostActions.style.top = ghost.style.top;
                        ghostActions.style.display = 'flex';
                    }
                }

                function centerGhost() {
                    gX = Math.floor((viewCols - gWidthCells) / 2) + 1;
                    gY = Math.floor((viewRows - gHeightCells) / 2) + 1;
                    layoutGhost();
                }

                function setGhostSizeFromPaquete(tamano) {
                    const CELDA_M = 0.5;
                    const anchoCells = Math.max(1, Math.round((tamano.ancho ?? 1) /
                        CELDA_M));
                    const largoCells = Math.max(1, Math.ceil((tamano.longitud ??
                        0) / CELDA_M));
                    gWidthCells = largoCells;
                    gHeightCells = anchoCells;
                }

                function enableDrag() {
                    if (!ghost) return;
                    let dragging = false;
                    let startMouseX = 0,
                        startMouseY = 0;
                    let startGX = 0,
                        startGY = 0;

                    function onDown(e) {
                        dragging = true;
                        ghost.classList.add('dragging');
                        startMouseX = (e.touches ? e.touches[0].clientX : e
                            .clientX);
                        startMouseY = (e.touches ? e.touches[0].clientY : e
                            .clientY);
                        startGX = gX;
                        startGY = gY;
                        e.preventDefault();
                    }

                    function onMove(e) {
                        if (!dragging) return;
                        const mx = (e.touches ? e.touches[0].clientX : e.clientX);
                        const my = (e.touches ? e.touches[0].clientY : e.clientY);
                        const dx = mx - startMouseX;
                        const dy = my - startMouseY;
                        const dCol = Math.round(dx / celdaPx);
                        const dRow = Math.round(dy / celdaPx);
                        gX = startGX + dCol;
                        gY = startGY + dRow;
                        layoutGhost();
                        e.preventDefault();
                    }

                    function onUp() {
                        dragging = false;
                        ghost.classList.remove('dragging');
                    }

                    ghost.addEventListener('mousedown', onDown);
                    ghost.addEventListener('touchstart', onDown, {
                        passive: false
                    });
                    window.addEventListener('mousemove', onMove, {
                        passive: false
                    });
                    window.addEventListener('touchmove', onMove, {
                        passive: false
                    });
                    window.addEventListener('mouseup', onUp, {
                        passive: true
                    });
                    window.addEventListener('touchend', onUp, {
                        passive: true
                    });
                }

                function rotateGhostKeepCenter() {
                    if (!ghost) return;
                    const cx = gX + (gWidthCells - 1) / 2;
                    const cy = gY + (gHeightCells - 1) / 2;

                    const newW = gHeightCells;
                    const newH = gWidthCells;

                    let newGX = Math.round(cx - (newW - 1) / 2);
                    let newGY = Math.round(cy - (newH - 1) / 2);

                    newGX = Math.max(1, Math.min(viewCols - newW + 1, newGX));
                    newGY = Math.max(1, Math.min(viewRows - newH + 1, newGY));

                    gWidthCells = newW;
                    gHeightCells = newH;
                    gX = newGX;
                    gY = newGY;

                    layoutGhost();
                }

                async function onPlaceGhost() {
                    if (!paqueteMeta) return;

                    const x1v = gX,
                        y1v = gY;
                    const x2v = gX + gWidthCells - 1;
                    const y2v = gY + gHeightCells - 1;

                    const p1 = mapViewToReal(x1v, y1v);
                    const p2 = mapViewToReal(x2v, y2v);

                    const x1r = Math.min(p1.x, p2.x);
                    const y1r = Math.min(p1.y, p2.y);
                    const x2r = Math.max(p1.x, p2.x);
                    const y2r = Math.max(p1.y, p2.y);

                    if (x1r < 1 || y1r < 1 || x2r > W || y2r > H) {
                        alert('Fuera de los límites de la nave.');
                        return;
                    }

                    if (!confirm(
                            `Asignar paquete ${paqueteMeta.codigo} en (${x1r},${y1r})–(${x2r},${y2r})?`
                        ))
                        return;

                    const naveId = escenario.dataset.naveId || null;
                    const urlGuardar = escenario.dataset.rutaGuardar;
                    try {
                        const resp = await fetch(urlGuardar, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]')
                                    ?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                nave_id: naveId,
                                tipo: 'paquete',
                                nombre: paqueteMeta.codigo,
                                paquete_id: paqueteMeta
                                    .paquete_id,
                                x1: x1r,
                                y1: y1r,
                                x2: x2r,
                                y2: y2r,
                            })
                        });
                        if (!resp.ok) {
                            const t = await resp.text();
                            throw new Error(t || `HTTP ${resp.status}`);
                        }
                        ghost.remove();
                        ghost = null;
                        ghostActions.remove();
                        ghostActions = null;
                        paqueteMeta = null;
                        location.reload();
                    } catch (err) {
                        console.error(err);
                        alert(
                            'No se pudo guardar la localización del paquete.'
                        );
                    }
                }

                async function fetchPaqueteBySubId(subId) {
                    const url = escenario.dataset.rutaPaquete;
                    const resp = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]')
                                ?.getAttribute('content') || '',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            codigo: subId
                        }),
                    });
                    if (!resp.ok) {
                        const t = await resp.text();
                        throw new Error(t || `HTTP ${resp.status}`);
                    }
                    return resp.json();
                }

                const input = document.getElementById(
                    '{{ $mapId }}-input-etiqueta-sub');
                input?.addEventListener('keydown', async (e) => {
                    if (e.key !== 'Enter') return;
                    const raw = (input.value || '').trim();
                    if (!raw) return;

                    try {
                        const data = await fetchPaqueteBySubId(raw);
                        paqueteMeta = {
                            codigo: data.codigo,
                            paquete_id: data.paquete_id,
                            longitud: Number(data.longitud || 0),
                            ancho: Number(data.ancho || 1),
                        };
                        ensureGhost();
                        setGhostSizeFromPaquete({
                            ancho: paqueteMeta.ancho,
                            longitud: paqueteMeta.longitud,
                        });
                        centerGhost();
                        input.select();
                    } catch (err) {
                        console.error(err);
                        alert(
                            'No se encontró el paquete para ese código.'
                        );
                    }
                });

                window.addEventListener('keydown', (e) => {
                    if (!ghost) return;
                    if (e.key === 'Escape') {
                        document.getElementById(
                                '{{ $mapId }}-btn-cancel-ghost')
                            ?.click();
                    } else if (e.key.toLowerCase() === 'r') {
                        document.getElementById(
                                '{{ $mapId }}-btn-rotate-ghost')
                            ?.click();
                    } else if (e.key === 'Enter') {
                        document.getElementById(
                                '{{ $mapId }}-btn-place-ghost')
                            ?.click();
                    }
                });

                window.addEventListener('resize', () => {
                    if (!ghost) return;
                    requestAnimationFrame(layoutGhost);
                }, {
                    passive: true
                });

            })();
        </script>
    @endif
@endpush
