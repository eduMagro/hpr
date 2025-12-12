<x-app-layout>
    {{-- Cabecera --}}
    <x-slot name="title">Mapa de Ubicaciones — Ver máquinas</x-slot>

    {{-- Menús --}}
    <x-menu.localizaciones.menu-localizaciones-vistas :obra-actual-id="$obraActualId ?? null"
        route-index="localizaciones.index" route-create="localizaciones.create" />
    <x-menu.localizaciones.menu-localizaciones-naves :obras="$obras"
        :obra-actual-id="$obraActualId ?? null" />

    <div
        class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <!-- Columna: Título + detalle -->
        <div class="px-4 sm:px-6 lg:px-8 mt-2 md:mt-4 w-full md:w-auto">
            <div class="bg-white border rounded-lg p-3">
                <h2
                    class="font-semibold text-base sm:text-lg md:text-xl text-gray-800 leading-tight">
                    Mapa de {{ $dimensiones['obra'] ?? 'localizaciones' }}
                </h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">
                    Celda = 0,5 m.
                    Ancho: {{ $dimensiones['ancho'] }} m
                    ({{ $ctx['columnasReales'] }} cols),
                    Largo: {{ $dimensiones['largo'] }} m
                    ({{ $ctx['filasReales'] }} filas).
                    Vista: {{ $columnasVista }}×{{ $filasVista }}
                    ({{ $ctx['estaGirado'] ? 'vertical' : 'horizontal' }}).
                </p>
            </div>
        </div>

        {{-- Controles: orientación + input QR --}}
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

        <div class="px-4 sm:px-6 lg:px-8 w-full md:w-auto">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $urlH }}"
                    class="px-3 py-1.5 rounded border text-sm {{ !$ctx['estaGirado'] ? 'bg-gray-800 text-white' : 'hover:bg-gray-50' }}">
                    Horizontal
                </a>
                <a href="{{ $urlV }}"
                    class="px-3 py-1.5 rounded border text-sm {{ $ctx['estaGirado'] ? 'bg-gray-800 text-white' : 'hover:bg-gray-50' }}">
                    Vertical
                </a>

                <!-- Input QR: full-width en móvil, tamaño cómodo en desktop -->
                <input id="input-etiqueta-sub" type="text" inputmode="text"
                    autocomplete="off"
                    placeholder="Escanea/pega etiqueta_sub_id (ETQ123456.01)"
                    class="w-full sm:w-auto sm:min-w-[16rem] md:min-w-[18rem] flex-1 px-3 py-1.5 rounded border text-sm focus:ring focus:outline-none"
                    aria-label="Escanear código QR de subetiqueta" />
            </div>
        </div>
    </div>

    {{-- Escenario + Cuadrícula (solo lectura) --}}
    <div class="px-4 sm:px-6 lg:px-8 mt-4">
        <div id="escenario-cuadricula"
            class="{{ $ctx['estaGirado'] ? 'orient-vertical' : 'orient-horizontal' }}"
            data-nave-id="{{ $obraActualId ?? ($ctx['naveId'] ?? '') }}"
            data-ruta-paquete="{{ route('paquetes.tamaño') }}"
            data-ruta-guardar="{{ route('localizaciones.storePaquete') }}"
            style="--cols: {{ $ctx['estaGirado'] ? $ctx['columnasReales'] : $ctx['filasReales'] }};
            --rows: {{ $ctx['estaGirado'] ? $ctx['filasReales'] : $ctx['columnasReales'] }};">

            <div id="cuadricula" aria-label="Cuadrícula de la nave">
                {{-- Overlays: MÁQUINAS --}}
                @foreach ($localizacionesConMaquina as $loc)
                    <div class="loc-existente loc-maquina"
                        data-id="{{ $loc['id'] }}"
                        data-x1="{{ $loc['x1'] }}"
                        data-y1="{{ $loc['y1'] }}"
                        data-x2="{{ $loc['x2'] }}"
                        data-y2="{{ $loc['y2'] }}"
                        data-maquina-id="{{ $loc['maquina_id'] }}"
                        data-nombre="{{ $loc['nombre'] }}"
                        @if (isset($loc['wCeldas'])) data-w="{{ $loc['wCeldas'] }}" @endif
                        @if (isset($loc['hCeldas'])) data-h="{{ $loc['hCeldas'] }}" @endif>
                        <span class="loc-label">{{ $loc['nombre'] }}</span>
                    </div>
                @endforeach

                {{-- Overlays: ZONAS (transitable / almacenamiento / carga_descarga) --}}
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
            </div>


            <div id="info-cuadricula" class="info-cuadricula">
                {{ $columnasVista }} columnas × {{ $filasVista }} filas
            </div>
        </div>

    </div>
    {{-- Panel de resultados del escaneo --}}
    <div id="scan-result"
        class="mx-4 sm:mx-6 lg:mx-8 mt-3 hidden border rounded-lg p-3 bg-white text-sm">
        <div class="font-semibold mb-1">Resultado del paquete</div>
        <div id="scan-result-body" class="text-gray-700"></div>
    </div>

    {{-- CSS --}}
    <link rel="stylesheet"
        href="{{ asset('css/localizaciones/styleLocIndex.css') }}">

    {{-- Contexto backend --}}
    <script>
        window.__LOC_CTX__ = @json($ctx);
    </script>

    {{-- JS: solo render/resize; sin colocación ni borrado --}}
    <script>
        (() => {
            const ctx = window.__LOC_CTX__;

            // Reales en celdas (0,5 m): W = ancho, H = largo
            const W = ctx.columnasReales; // ej. 44
            const H = ctx.filasReales; // ej. 330

            // Vertical = true  => (1,1) abajo-izquierda (invertimos Y)
            // Horizontal = false => (1,1) arriba-izquierda y la nave se "acuesta" (transpose)
            const isVertical = !!ctx.estaGirado;

            // Tamaño de la VISTA (cómo pintamos el grid en pantalla)
            // Horizontal: ancho = H, alto = W  (más ancho que alto)
            // Vertical:   ancho = W, alto = H
            const viewCols = isVertical ? W : H;
            const viewRows = isVertical ? H : W;

            const escenario = document.getElementById('escenario-cuadricula');
            const grid = document.getElementById('cuadricula');
            let celdaPx = 8;

            function getCeldaPx() {
                const v = getComputedStyle(grid).getPropertyValue('--tam-celda')
                    .trim();
                const n = parseInt(v, 10);
                return Number.isFinite(n) && n > 0 ? n : 8;
            }

            // ---- MAPEOS ----
            // Punto real -> punto vista
            // Vertical: (x, H - y + 1)  [invierte Y, NO intercambia ejes]
            // Horizontal: (y, x)        [transpose: intercambia ejes sin inversión]
            function mapPointToView(x, y) {
                if (isVertical) return {
                    x,
                    y: (H - y + 1)
                };
                return {
                    x: y,
                    y: x
                };
            }

            // Rect real -> rect vista (usamos envolvente de las 4 esquinas)
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
                    h: (maxY - minY + 1)
                };
            }

            // ---- RENDER ----
            function renderExistentes() {
                celdaPx = getCeldaPx();
                document.querySelectorAll('.loc-existente').forEach(el => {
                    const {
                        x,
                        y,
                        w,
                        h
                    } = realToViewRect(
                        +el.dataset.x1, +el.dataset.y1, +el.dataset
                        .x2, +el.dataset.y2
                    );
                    el.style.left = ((x - 1) * celdaPx) + 'px';
                    el.style.top = ((y - 1) * celdaPx) + 'px';
                    el.style.width = (w * celdaPx) + 'px';
                    el.style.height = (h * celdaPx) + 'px';
                });
            }

            function ajustarTamCelda() {
                const anchoDisp = escenario.clientWidth - 12;
                const altoDisp = (window.innerHeight - escenario
                    .getBoundingClientRect().top - 24);

                const tamPorAncho = Math.floor(anchoDisp / viewCols);
                const tamPorAlto = Math.floor(altoDisp / viewRows);
                const tamCelda = Math.max(4, Math.min(tamPorAncho, tamPorAlto));

                grid.style.setProperty('--tam-celda', tamCelda + 'px');
                grid.style.width = (tamCelda * viewCols) + 'px';
                grid.style.height = (tamCelda * viewRows) + 'px';

                renderExistentes();
            }

            // Init + resize
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
        })();
    </script>


    {{-- JS Ghost compartido (mismo código que en mapa-simple) --}}
    <x-localizaciones.ghost-paquete-js />

    <script>
        (() => {
            const escenario = document.getElementById('escenario-cuadricula');
            const grid = document.getElementById('cuadricula');
            if (!escenario || !grid) return;

            const ctx = window.__LOC_CTX__;
            const isVertical = !!ctx.estaGirado;
            const W = ctx.columnasReales;
            const H = ctx.filasReales;
            const viewCols = isVertical ? W : H;
            const viewRows = isVertical ? H : W;

            function getCeldaPx() {
                const v = getComputedStyle(grid).getPropertyValue('--tam-celda').trim();
                const n = parseInt(v, 10);
                return Number.isFinite(n) && n > 0 ? n : 8;
            }

            // Crear instancia del Ghost usando la clase compartida
            const ghostPaquete = new window.GhostPaquete({
                grid: grid,
                viewCols: viewCols,
                viewRows: viewRows,
                W: W,
                H: H,
                isVertical: isVertical,
                naveId: escenario.dataset.naveId || null,
                rutaGuardar: escenario.dataset.rutaGuardar,
                getCeldaPx: getCeldaPx,
                confirmBeforeSave: true,
                onSuccess: function() {
                    location.reload();
                }
            });

            // Buscar paquete por etiqueta_sub_id
            async function fetchPaqueteBySubId(subId) {
                const url = escenario.dataset.rutaPaquete;
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ codigo: subId })
                });
                if (!resp.ok) {
                    const t = await resp.text();
                    throw new Error(t || `HTTP ${resp.status}`);
                }
                return resp.json();
            }

            // Hook al input de escaneo
            const input = document.getElementById('input-etiqueta-sub');
            input?.addEventListener('keydown', async (e) => {
                if (e.key !== 'Enter') return;
                const raw = (input.value || '').trim();
                if (!raw) return;

                try {
                    const data = await fetchPaqueteBySubId(raw);
                    ghostPaquete.crear({
                        codigo: data.codigo,
                        paquete_id: data.paquete_id,
                        longitud: Number(data.longitud || 0),
                        ancho: Number(data.ancho || 1)
                    });
                    input.select();
                } catch (err) {
                    console.error(err);
                    alert('No se encontró el paquete para ese código.');
                }
            });
        })();
    </script>


</x-app-layout>
