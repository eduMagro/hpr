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
    'enableDragPaquetes' => false, // habilitar arrastre de paquetes

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
                        data-y2="{{ $paquete['y2'] }}"
                        data-identificador="{{ $paquete['codigo'] ?? $paquete['id'] }}"
                        {{-- orientación inicial: I = vertical, _ = horizontal --}}
                        data-orientacion="{{ $paquete['orientacion'] ?? 'I' }}">
                        {{-- Label quitado por petición del usuario --}}
                    </div>
                @endforeach

            </div>

            {{-- Botones de Zoom --}}
            <div id="zoom-controls" class="zoom-controls">
                <button id="zoom-in-btn" type="button" title="Acercar zoom"
                    class="zoom-btn zoom-btn-in">
                    <svg viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5">
                        <circle cx="11" cy="11" r="8" />
                        <path d="M11 8v6M8 11h6M21 21l-4.35-4.35" />
                    </svg>
                </button>
                <button id="zoom-out-btn" type="button" title="Alejar zoom"
                    class="zoom-btn zoom-btn-out">
                    <svg viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5">
                        <circle cx="11" cy="11" r="8" />
                        <path d="M8 11h6M21 21l-4.35-4.35" />
                    </svg>
                </button>
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

            if (!escenario || !grid) {
                console.error('No se encontró el escenario o la cuadrícula');
                return;
            };

            console.log("tamo bien")

            const W = ctx.columnasReales;
            const H = ctx.filasReales;
            const isVertical = !!ctx.estaGirado;

            const viewCols = isVertical ? W : H;
            const viewRows = isVertical ? H : W;

            // =========================
            //  Configuración de ZOOM
            // =========================
            // Límite mínimo de zoom (0.5 = 50% del tamaño base)
            const ZOOM_MIN = 0.5;
            // Límite máximo de zoom (3 = 300% del tamaño base)
            const ZOOM_MAX = 3;
            // Paso de zoom en cada "ruedazo" (10%)
            const ZOOM_STEP = 0.1;

            // Factor de zoom actual.
            // 1 = tamaño base calculado para encajar el mapa en la pantalla.
            let zoomFactor = 1;

            /**
             * Devuelve el tamaño actual de la celda en píxeles, leyendo la
             * variable CSS --tam-celda del grid. Este valor ya refleja el zoom.
             */
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
                    // No reposicionar paquetes que están en modo edición
                    if (el.classList.contains('loc-paquete--editing')) {
                        // Solo actualizar el tamaño basado en celdas, no la posición
                        // Mantener left y top intactos
                        const currentLeft = parseFloat(el.style.left) ||
                            0;
                        const currentTop = parseFloat(el.style.top) ||
                            0;

                        // Recalcular width y height si es necesario
                        const {
                            w,
                            h
                        } = realToViewRect(
                            +el.dataset.x1, +el.dataset.y1,
                            +el.dataset.x2, +el.dataset.y2
                        );

                        // Mantener la posición actual pero ajustar dimensiones proporcionalmente
                        const oldCeldaPx = parseFloat(el.dataset
                            .currentCeldaPx) || celdaPx;
                        const scaleFactor = celdaPx / oldCeldaPx;

                        el.style.left = (currentLeft * scaleFactor) +
                            'px';
                        el.style.top = (currentTop * scaleFactor) +
                            'px';
                        el.style.width = (parseFloat(el.style.width) *
                            scaleFactor) + 'px';
                        el.style.height = (parseFloat(el.style.height) *
                            scaleFactor) + 'px';

                        el.dataset.currentCeldaPx = celdaPx;
                        return;
                    }

                    const {
                        x,
                        y,
                        w,
                        h
                    } = realToViewRect(
                        +el.dataset.x1, +el.dataset.y1,
                        +el.dataset.x2, +el.dataset.y2
                    );
                    const width = w * celdaPx;
                    const height = h * celdaPx;

                    el.style.left = ((x - 1) * celdaPx) + 'px';
                    el.style.top = ((y - 1) * celdaPx) + 'px';
                    el.style.width = width + 'px';
                    el.style.height = height + 'px';
                    el.dataset.currentCeldaPx = celdaPx;
                });
            }

            /**
             * Calcula el tamaño base de la celda para que el mapa quepa
             * razonablemente en pantalla y le aplica el factor de zoom.
             */
            function ajustarTamCelda() {
                // Ancho disponible dentro del escenario (restamos un poco de margen)
                const anchoDisp = escenario.clientWidth - 12;

                // Alto disponible desde la parte superior del escenario hasta el
                // borde inferior de la ventana (restamos un pequeño margen)
                const altoDisp = window.innerHeight - escenario
                    .getBoundingClientRect().top - 24;

                // Tamaño de celda "natural" para que el mapa entre en el contenedor
                const tamPorAncho = Math.floor(anchoDisp / viewCols);
                const tamPorAlto = Math.floor(altoDisp / viewRows);

                // Tamaño base de la celda sin zoom (mínimo 4px)
                const baseTamCelda = Math.max(4, Math.min(tamPorAncho,
                    tamPorAlto));

                // Tamaño final aplicando el factor de zoom actual
                const tamCelda = Math.max(4, baseTamCelda * zoomFactor);

                // Guardamos el tamaño de celda en la variable CSS y ajustamos ancho/alto del grid
                grid.style.setProperty('--tam-celda', tamCelda + 'px');
                grid.style.width = (tamCelda * viewCols) + 'px';
                grid.style.height = (tamCelda * viewRows) + 'px';

                // Reposicionamos todas las localizaciones existentes usando el nuevo tamaño de celda
                renderExistentes();

                // Si tenemos un "ghost" de paquete en pantalla, también lo recolocamos
                if (typeof layoutGhost === 'function') {
                    try {
                        layoutGhost();
                    } catch (e) {
                        // Por si acaso layoutGhost aún no está definido cuando se llama
                        console.warn(
                            'layoutGhost no disponible aún al ajustar celda',
                            e);
                    }
                }
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

            // ==============================
            //  Zoom con la rueda del ratón
            // ==============================

            /**
             * Maneja el zoom del mapa usando la rueda del ratón.
             * - Ruede hacia arriba (deltaY < 0) => acercar (zoom in)
             * - Ruede hacia abajo (deltaY > 0) => alejar (zoom out)
             *
             * Nota:
             * Si prefieres que solo haga zoom al mantener CTRL, descomenta el if.
             */
            escenario.addEventListener('wheel', (event) => {
                // Si quieres obligar a usar CTRL + rueda para hacer zoom:
                // if (!event.ctrlKey) return;

                // Evitamos que la rueda haga scroll vertical en el contenedor
                event.preventDefault();

                // Calculamos el nuevo factor de zoom según la dirección de la rueda
                if (event.deltaY < 0) {
                    // Rueda hacia arriba -> zoom in
                    zoomFactor *= (1 + ZOOM_STEP);
                } else if (event.deltaY > 0) {
                    // Rueda hacia abajo -> zoom out
                    zoomFactor *= (1 - ZOOM_STEP);
                }

                // Limitamos el zoom a los rangos definidos
                if (zoomFactor < ZOOM_MIN) zoomFactor = ZOOM_MIN;
                if (zoomFactor > ZOOM_MAX) zoomFactor = ZOOM_MAX;

                // Recalculamos tamaños y reposicionamos todo con el nuevo zoom
                ajustarTamCelda();
            }, {
                passive: false // Necesario para poder usar event.preventDefault()
            });

            // ==============================
            //  Botones de Zoom (+/-)
            // ==============================
            const zoomInBtn = document.getElementById('zoom-in-btn');
            const zoomOutBtn = document.getElementById('zoom-out-btn');

            if (zoomInBtn) {
                zoomInBtn.addEventListener('click', () => {
                    // Acercar zoom
                    zoomFactor *= (1 + ZOOM_STEP);
                    if (zoomFactor > ZOOM_MAX) zoomFactor = ZOOM_MAX;
                    ajustarTamCelda();
                });
            }

            if (zoomOutBtn) {
                zoomOutBtn.addEventListener('click', () => {
                    // Alejar zoom
                    zoomFactor *= (1 - ZOOM_STEP);
                    if (zoomFactor < ZOOM_MIN) zoomFactor = ZOOM_MIN;
                    ajustarTamCelda();
                });
            }

            // Posicionar botones de zoom fixed respecto al mapa
            function posicionarBotonesZoom() {
                const zoomControls = document.getElementById('zoom-controls');
                if (zoomControls && escenario) {
                    const rect = escenario.getBoundingClientRect();
                    zoomControls.style.top = `${rect.top + 8}px`;
                    zoomControls.style.left = `${rect.left + 8}px`;
                }
            }

            posicionarBotonesZoom();
            window.addEventListener('resize', posicionarBotonesZoom);
            window.addEventListener('scroll', posicionarBotonesZoom);

            // ==============================
            //  Pan/Drag para mover el mapa
            // ==============================
            let isPanning = false;
            let panStartX = 0;
            let panStartY = 0;
            let panStartScrollLeft = 0;
            let panStartScrollTop = 0;

            escenario.addEventListener('mousedown', (e) => {
                // No activar pan si se hace clic en un elemento interactivo
                if (e.target.closest('.loc-existente') || e.target
                    .closest('button')) {
                    return;
                }

                isPanning = true;
                panStartX = e.clientX;
                panStartY = e.clientY;
                panStartScrollLeft = escenario.scrollLeft;
                panStartScrollTop = escenario.scrollTop;
                escenario.style.cursor = 'grabbing';
                e.preventDefault();
            });

            escenario.addEventListener('mousemove', (e) => {
                if (!isPanning) return;

                const deltaX = e.clientX - panStartX;
                const deltaY = e.clientY - panStartY;

                escenario.scrollLeft = panStartScrollLeft - deltaX;
                escenario.scrollTop = panStartScrollTop - deltaY;
            });

            escenario.addEventListener('mouseup', () => {
                if (isPanning) {
                    isPanning = false;
                    escenario.style.cursor = '';
                }
            });

            escenario.addEventListener('mouseleave', () => {
                if (isPanning) {
                    isPanning = false;
                    escenario.style.cursor = '';
                }
            });

            // Touch support para móviles
            let touchStartX = 0;
            let touchStartY = 0;
            let touchStartScrollLeft = 0;
            let touchStartScrollTop = 0;

            escenario.addEventListener('touchstart', (e) => {
                if (e.target.closest('.loc-existente') || e.target
                    .closest('button')) {
                    return;
                }

                if (e.touches.length === 1) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                    touchStartScrollLeft = escenario.scrollLeft;
                    touchStartScrollTop = escenario.scrollTop;
                }
            }, {
                passive: true
            });

            escenario.addEventListener('touchmove', (e) => {
                if (e.touches.length === 1) {
                    const deltaX = e.touches[0].clientX - touchStartX;
                    const deltaY = e.touches[0].clientY - touchStartY;

                    escenario.scrollLeft = touchStartScrollLeft -
                        deltaX;
                    escenario.scrollTop = touchStartScrollTop - deltaY;
                }
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
                // Quitamos el highlight de todos los paquetes dibujados en el mapa
                grid.querySelectorAll('.loc-paquete').forEach(el => {
                    el.classList.remove('loc-paquete--highlight');
                });

                // Buscamos el paquete concreto por su data-paquete-id
                const target = grid.querySelector(
                    '.loc-paquete[data-paquete-id="' + id + '"]'
                );

                // Si existe en el mapa, le aplicamos la clase de highlight
                if (target) {
                    target.classList.add('loc-paquete--highlight');
                }
            }

            function clearHighlight() {
                // Elimina el highlight de todos los paquetes actualmente dibujados
                grid.querySelectorAll('.loc-paquete').forEach(el => {
                    el.classList.remove('loc-paquete--highlight');
                });
            }

            /**
             * Muestra un paquete concreto (por id) en el mapa.
             * Se usará cuando el usuario “active” un paquete desde el listado lateral.
             */
            function showPaquete(id) {
                const el = grid.querySelector(
                    '.loc-paquete[data-paquete-id="' + id + '"]'
                );

                if (el) {
                    // Quitamos cualquier display:none que tenga el div del paquete
                    el.style.display = '';
                }
            }

            /**
             * Oculta un paquete concreto (por id) del mapa.
             * Se usará cuando el usuario “desactive” un paquete desde el listado lateral.
             */
            function hidePaquete(id) {
                const el = grid.querySelector(
                    '.loc-paquete[data-paquete-id="' + id + '"]'
                );

                if (el) {
                    // Lo escondemos visualmente
                    el.style.display = 'none';
                    // Y nos aseguramos de que no se queda como resaltado
                    el.classList.remove('loc-paquete--highlight');
                }
            }

            function focusPaquete(xReal, yReal) {
                // Calculamos la posición en píxeles de la celda real (xReal, yReal)
                const pos = getCeldaPosition(xReal, yReal);

                // Queremos centrar el scroll del escenario en esa posición
                const centerX = pos.left - escenario.clientWidth / 2;
                const centerY = pos.top - escenario.clientHeight / 2;

                // Movemos el scroll suavemente hacia el centro del paquete
                escenario.scrollTo({
                    left: Math.max(0, centerX),
                    top: Math.max(0, centerY),
                    behavior: 'smooth',
                });
            }

            // Exponer instancia en el canvas para que otras vistas puedan controlar el mapa
            escenario.mapaInstance = {
                setHighlight, // Resalta visualmente un paquete
                clearHighlight, // Limpia cualquier highlight activo
                focusPaquete, // Mueve la “cámara” a un paquete
                showPaquete, // Muestra un paquete concreto en el mapa
                hidePaquete, // Oculta un paquete concreto del mapa
            };



        })();
    </script>

    {{-- Script de edición de paquetes (separado para poder usarlo sin showControls) --}}
    @if ($enableDragPaquetes)
        <script>
            (() => {
                const escenario = document.getElementById('escenario-cuadricula');
                const grid = document.getElementById('cuadricula');
                console.log("cuadricula:", grid);

                if (!escenario || !grid) {
                    console.error('No se encontró el escenario o la cuadrícula');
                    return;
                };

                console.log("tamo bien")

                const ctx = @json($ctx);
                const isVertical = !!ctx.estaGirado;
                const W = ctx.columnasReales;
                const H = ctx.filasReales;

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

                // ================================
                //  MODO EDICIÓN DE PAQUETES
                // ================================
                let paqueteEnEdicion = null;
                let dragState = null;

                /**
                 * Inicializa la interacción de clic/edición en todos los paquetes del mapa.
                 */
                function initPaqueteInteracciones() {
                    console.log("Inicializando interacciones de paquetes...");
                    const paquetes = grid.querySelectorAll('.loc-paquete');

                    paquetes.forEach(paquete => {
                        if (getComputedStyle(paquete).position ===
                            'static') {
                            paquete.style.position = 'absolute';
                        }

                        paquete.addEventListener('click', function(ev) {
                            if (ev.target.closest(
                                    '.paquete-toolbar')) {
                                return;
                            }

                            if (this.classList.contains(
                                    'loc-paquete--editing')) {
                                return;
                            }

                            const yaTieneToolbarPreview = this
                                .querySelector(
                                    '.paquete-toolbar--preview');
                            if (!yaTieneToolbarPreview) {
                                crearToolbarPreview(this);
                            }
                        });
                    });
                }

                function crearToolbarPreview(paquete) {
                    if (paqueteEnEdicion && paqueteEnEdicion !== paquete) {
                        salirDeEdicion(paqueteEnEdicion, true);
                    }

                    const toolbar = document.createElement('div');
                    toolbar.className = 'paquete-toolbar paquete-toolbar--preview';

                    const btnEditar = document.createElement('button');
                    btnEditar.type = 'button';
                    btnEditar.title = 'Mover paquete';

                    btnEditar.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 20h4l10.5-10.5-4-4L4 16v4z" stroke="currentColor" stroke-width="2"/>
                            <path d="M14.5 5.5l4 4" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    `;

                    btnEditar.addEventListener('click', (ev) => {
                        ev.stopPropagation();
                        entrarEnEdicion(paquete);
                    });

                    toolbar.appendChild(btnEditar);
                    paquete.appendChild(toolbar);
                }

                function entrarEnEdicion(paquete) {
                    if (paqueteEnEdicion && paqueteEnEdicion !== paquete) {
                        salirDeEdicion(paqueteEnEdicion, true);
                    }

                    paqueteEnEdicion = paquete;

                    const previewToolbar = paquete.querySelector(
                        '.paquete-toolbar--preview');
                    if (previewToolbar) {
                        previewToolbar.remove();
                    }

                    paquete.classList.add('loc-paquete--editing');

                    if (!paquete.dataset.origLeft) {
                        paquete.dataset.origLeft = paquete.style.left || '0px';
                        paquete.dataset.origTop = paquete.style.top || '0px';
                        paquete.dataset.origWidth = paquete.style.width || '';
                        paquete.dataset.origHeight = paquete.style.height || '';
                    }

                    // Label deshabilitado por petición del usuario
                    // let label = paquete.querySelector('.paquete-label');
                    // if (!label) {
                    //     label = document.createElement('div');
                    //     label.className = 'paquete-label';
                    //     label.textContent = paquete.dataset.identificador || ('Paquete #' + (paquete.dataset.paqueteId ?? '?'));
                    //     paquete.appendChild(label);
                    // }

                    const toolbar = document.createElement('div');
                    toolbar.className = 'paquete-toolbar paquete-toolbar--edit';

                    // Botón confirmar
                    const btnConfirmar = document.createElement('button');
                    btnConfirmar.type = 'button';
                    btnConfirmar.title = 'Guardar nueva posición';
                    btnConfirmar.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    `;

                    btnConfirmar.addEventListener('click', async (ev) => {
                        ev.stopPropagation();

                        const paqueteId = paquete.dataset.paqueteId;
                        if (!paqueteId) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se encontró el ID del paquete',
                                confirmButtonColor: '#3b82f6'
                            });
                            return;
                        }

                        const celdaPx = getCeldaPx();
                        const left = parseFloat(paquete.style.left) ||
                            0;
                        const top = parseFloat(paquete.style.top) || 0;
                        const width = parseFloat(paquete.style.width) ||
                            celdaPx;
                        const height = parseFloat(paquete.style
                            .height) || celdaPx;

                        const x1v = Math.round(left / celdaPx) + 1;
                        const y1v = Math.round(top / celdaPx) + 1;
                        const x2v = Math.round((left + width) /
                            celdaPx);
                        const y2v = Math.round((top + height) /
                            celdaPx);

                        const p1 = mapViewToReal(x1v, y1v);
                        const p2 = mapViewToReal(x2v, y2v);

                        const x1r = Math.min(p1.x, p2.x);
                        const y1r = Math.min(p1.y, p2.y);
                        const x2r = Math.max(p1.x, p2.x);
                        const y2r = Math.max(p1.y, p2.y);

                        btnConfirmar.disabled = true;
                        btnConfirmar.innerHTML = `
                            <svg viewBox="0 0 24 24" fill="none" class="animate-spin">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="4" fill="none"/>
                            </svg>
                        `;

                        try {
                            const response = await fetch(
                                `/localizaciones/paquete/${paqueteId}`, {
                                    method: 'PUT',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document
                                            .querySelector(
                                                'meta[name="csrf-token"]'
                                            )?.content || '',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        x1: x1r,
                                        y1: y1r,
                                        x2: x2r,
                                        y2: y2r
                                    })
                                });

                            const data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.message ||
                                    'Error al guardar la posición');
                            }

                            paquete.dataset.x1 = x1r;
                            paquete.dataset.y1 = y1r;
                            paquete.dataset.x2 = x2r;
                            paquete.dataset.y2 = y2r;
                            paquete.dataset.origLeft = paquete.style
                                .left;
                            paquete.dataset.origTop = paquete.style.top;

                            Swal.fire({
                                icon: 'success',
                                title: '¡Guardado!',
                                text: 'La posición del paquete se guardó correctamente',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                            try {
                                let modalMoverPaquete = document
                                    .getElementById(
                                        "modal-mover-paquete")
                                modalMoverPaquete.classList.add(
                                    "hidden");
                            } catch (e) {}
                            salirDeEdicion(paquete, false);

                        } catch (error) {
                            console.error('Error al guardar posición:',
                                error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al guardar',
                                text: error.message ||
                                    'No se pudo guardar la posición del paquete',
                                confirmButtonColor: '#ef4444'
                            });

                            btnConfirmar.disabled = false;
                            btnConfirmar.innerHTML = `
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            `;
                        }
                    });

                    // Botón cancelar
                    const btnCancelar = document.createElement('button');
                    btnCancelar.type = 'button';
                    btnCancelar.title = 'Cancelar cambios';
                    btnCancelar.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M6 6l12 12" stroke="currentColor" stroke-width="2"/>
                            <path d="M18 6L6 18" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    `;

                    btnCancelar.addEventListener('click', (ev) => {
                        ev.stopPropagation();
                        paquete.style.left = paquete.dataset.origLeft ||
                            '0px';
                        paquete.style.top = paquete.dataset.origTop ||
                            '0px';
                        paquete.style.width = paquete.dataset.origWidth ||
                            '';
                        paquete.style.height = paquete.dataset.origHeight ||
                            '';
                        salirDeEdicion(paquete, true);
                    });

                    // Botón rotar
                    const btnRotar = document.createElement('button');
                    btnRotar.type = 'button';
                    btnRotar.title = 'Rotar paquete 90°';
                    btnRotar.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M21 12a9 9 0 0 1-9 9m9-9a9 9 0 0 0-9-9m9 9h-4m-5 9a9 9 0 0 1-9-9m9 9v-4m-9-5a9 9 0 0 1 9-9m-9 9h4m5-9v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    `;

                    btnRotar.addEventListener('click', (ev) => {
                        ev.stopPropagation();

                        const celdaPx = getCeldaPx();

                        // Obtener dimensiones actuales en píxeles
                        const widthPx = parseFloat(paquete.style.width) ||
                            0;
                        const heightPx = parseFloat(paquete.style.height) ||
                            0;

                        // Convertir a celdas (redondeando)
                        const widthCeldas = Math.round(widthPx / celdaPx);
                        const heightCeldas = Math.round(heightPx / celdaPx);

                        // Intercambiar celdas
                        const newWidthCeldas = heightCeldas;
                        const newHeightCeldas = widthCeldas;

                        // Convertir de vuelta a píxeles
                        const newWidthPx = newWidthCeldas * celdaPx;
                        const newHeightPx = newHeightCeldas * celdaPx;

                        // Aplicar nuevas dimensiones
                        paquete.style.width = `${newWidthPx}px`;
                        paquete.style.height = `${newHeightPx}px`;

                        console.log('Rotación:', {
                            antes: {
                                widthCeldas,
                                heightCeldas,
                                widthPx,
                                heightPx
                            },
                            despues: {
                                newWidthCeldas,
                                newHeightCeldas,
                                newWidthPx,
                                newHeightPx
                            }
                        });
                    });

                    toolbar.appendChild(btnConfirmar);
                    toolbar.appendChild(btnCancelar);
                    toolbar.appendChild(btnRotar);
                    paquete.appendChild(toolbar);

                    activarDragEnPaquete(paquete);
                }

                function salirDeEdicion(paquete, cancelar = false) {
                    const label = paquete.querySelector('.paquete-label');
                    if (label) label.remove();

                    const toolbarEdit = paquete.querySelector(
                        '.paquete-toolbar--edit');
                    if (toolbarEdit) toolbarEdit.remove();

                    paquete.classList.remove('loc-paquete--editing');
                    desactivarDragEnPaquete(paquete);

                    const preview = paquete.querySelector(
                        '.paquete-toolbar--preview');
                    if (preview) preview.remove();

                    if (paqueteEnEdicion === paquete) {
                        paqueteEnEdicion = null;
                    }
                }

                function activarDragEnPaquete(paquete) {
                    if (!paquete.classList.contains('loc-paquete--editing')) return;

                    const onMouseDown = (ev) => {
                        if (ev.target.closest('.paquete-toolbar')) return;
                        ev.preventDefault();

                        const gridRect = grid.getBoundingClientRect();
                        const pkgRect = paquete.getBoundingClientRect();
                        const offsetX = ev.clientX - pkgRect.left;
                        const offsetY = ev.clientY - pkgRect.top;

                        dragState = {
                            offsetX,
                            offsetY,
                            gridRect
                        };

                        document.addEventListener('mousemove', onMouseMove);
                        document.addEventListener('mouseup', onMouseUp);
                    };

                    const onMouseMove = (ev) => {
                        if (!dragState) return;

                        const xDentroGrid = ev.clientX - dragState.gridRect
                            .left;
                        const yDentroGrid = ev.clientY - dragState.gridRect.top;

                        let nuevoLeft = xDentroGrid - dragState.offsetX;
                        let nuevoTop = yDentroGrid - dragState.offsetY;

                        nuevoLeft = Math.max(0, Math.min(nuevoLeft, dragState
                            .gridRect.width - paquete.offsetWidth));
                        nuevoTop = Math.max(0, Math.min(nuevoTop, dragState
                            .gridRect.height - paquete.offsetHeight));

                        paquete.style.left = `${nuevoLeft}px`;
                        paquete.style.top = `${nuevoTop}px`;
                    };

                    const onMouseUp = () => {
                        dragState = null;
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                    };

                    paquete._onMouseDownEditar = onMouseDown;
                    paquete._onMouseMoveEditar = onMouseMove;
                    paquete._onMouseUpEditar = onMouseUp;

                    paquete.addEventListener('mousedown', onMouseDown);
                }

                function desactivarDragEnPaquete(paquete) {
                    if (paquete._onMouseDownEditar) {
                        paquete.removeEventListener('mousedown', paquete
                            ._onMouseDownEditar);
                        delete paquete._onMouseDownEditar;
                    }
                    if (paquete._onMouseMoveEditar) {
                        document.removeEventListener('mousemove', paquete
                            ._onMouseMoveEditar);
                        delete paquete._onMouseMoveEditar;
                    }
                    if (paquete._onMouseUpEditar) {
                        document.removeEventListener('mouseup', paquete
                            ._onMouseUpEditar);
                        delete paquete._onMouseUpEditar;
                    }
                    dragState = null;
                }

                // Inicializar la funcionalidad de drag
                initPaqueteInteracciones();
            })();
        </script>
    @endif
@endpush

<style>
    /* Toolbar flotante debajo del paquete */
    .paquete-toolbar {
        position: absolute;
        top: calc(100% + 30px);
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: row;
        gap: 1rem;
        align-items: center;
        justify-content: center;
        z-index: 25;
        background: rgba(15, 23, 42, 0.85);
        padding: 0.35rem 0.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
    }

    /* Botones de la toolbar */
    .paquete-toolbar button {
        width: 18px;
        height: 18px;
        border-radius: 9999px;
        border: 1.5px solid rgba(255, 255, 255, 0.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.95);
        cursor: pointer;
        padding: 0;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    /* Tablets y móviles */
    @media (max-width: 768px) {
        .paquete-toolbar button {
            width: 22px;
            height: 22px;
        }

        .paquete-toolbar {
            gap: 1.25rem;
            padding: 0.5rem 0.75rem;
        }
    }

    .paquete-toolbar button:hover {
        transform: scale(1.2);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .paquete-toolbar button:active {
        transform: scale(1.05);
    }

    .paquete-toolbar button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: scale(1);
    }

    .paquete-toolbar button svg {
        width: 10px;
        height: 10px;
        stroke: #fff;
        stroke-width: 2.5;
    }

    @media (max-width: 768px) {
        .paquete-toolbar button svg {
            width: 12px;
            height: 12px;
        }
    }

    .paquete-toolbar button span {
        font-size: 0.6rem;
        color: #fff;
        line-height: 1;
    }

    /* Toolbar preview (botón de lápiz) - debajo del paquete igual que los otros */
    .paquete-toolbar--preview {
        position: absolute;
        top: calc(100% + 30px);
        left: 50%;
        transform: translateX(-50%);
        background: rgba(15, 23, 42, 0.85);
        padding: 0.35rem 0.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
    }

    /* Color amarillo/naranja para el botón de lápiz */
    .paquete-toolbar--preview button {
        background: rgba(251, 146, 60, 0.95) !important;
        border-color: rgba(251, 146, 60, 0.3) !important;
    }

    .paquete-toolbar--preview button:hover {
        background: rgba(251, 146, 60, 1) !important;
        border-color: rgba(251, 146, 60, 0.5) !important;
    }

    /* Botones con colores específicos para modo edición */
    /* Botón 1: Confirmar (verde) */
    .paquete-toolbar--edit button:nth-child(1) {
        background: rgba(34, 197, 94, 0.95);
        border-color: rgba(34, 197, 94, 0.3);
    }

    .paquete-toolbar--edit button:nth-child(1):hover {
        background: rgba(34, 197, 94, 1);
        border-color: rgba(34, 197, 94, 0.5);
    }

    /* Botón 2: Cancelar (rojo) */
    .paquete-toolbar--edit button:nth-child(2) {
        background: rgba(239, 68, 68, 0.95);
        border-color: rgba(239, 68, 68, 0.3);
    }

    .paquete-toolbar--edit button:nth-child(2):hover {
        background: rgba(239, 68, 68, 1);
        border-color: rgba(239, 68, 68, 0.5);
    }

    /* Botón 3: Rotar (azul) */
    .paquete-toolbar--edit button:nth-child(3) {
        background: rgba(59, 130, 246, 0.95);
        border-color: rgba(59, 130, 246, 0.3);
    }

    .paquete-toolbar--edit button:nth-child(3):hover {
        background: rgba(59, 130, 246, 1);
        border-color: rgba(59, 130, 246, 0.5);
    }

    /* Modo edición: resaltamos el borde del paquete */
    .loc-paquete--editing {
        outline: 2px dashed #0ea5e9;
        outline-offset: 2px;
        cursor: move !important;
        z-index: 10;
    }

    /* Paquete al hacer hover (no en modo edición) */
    .loc-paquete:not(.loc-paquete--editing):hover {
        cursor: pointer;
        opacity: 0.9;
    }

    /* Orientación por clases (ajusta a tu gusto) */
    .loc-paquete--orient-I {
        /* por ejemplo, más alto que ancho */
    }

    .loc-paquete--orient-_ {
        /* por ejemplo, más ancho que alto */
    }

    /* Animación de spinner */
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    /* Cursor del mapa para indicar que se puede arrastrar */
    #escenario-cuadricula {
        cursor: grab;
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    #escenario-cuadricula:active {
        cursor: grabbing;
    }
</style>
