<x-app-layout>
    {{-- Cabecera --}}
    <x-slot name="title">Mapa de Ubicaciones — Ver máquinas</x-slot>

    {{-- Menús --}}
    <x-menu.localizaciones.menu-localizaciones-vistas :obra-actual-id="$obraActualId ?? null" route-index="localizaciones.index"
        route-create="localizaciones.create" />
    <x-menu.localizaciones.menu-localizaciones-naves :obras="$obras" :obra-actual-id="$obraActualId ?? null" />

    <div class="flex items-center justify-between">
        <div class="px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-white border rounded-lg p-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Mapa de {{ $dimensiones['obra'] ?? 'localizaciones' }}
                </h2>
                <p class="text-sm text-gray-500">
                    Celda = 0,5 m.
                    Ancho: {{ $dimensiones['ancho'] }} m ({{ $ctx['columnasReales'] }} columnas),
                    Largo: {{ $dimensiones['largo'] }} m ({{ $ctx['filasReales'] }} filas).
                    Vista: {{ $columnasVista }}×{{ $filasVista }}
                    ({{ $ctx['estaGirado'] ? 'vertical' : 'horizontal' }}).
                </p>
            </div>
        </div>

        {{-- Conmutador de orientación --}}
        <div class="flex items-center gap-2">
            @php
                $qsBase = request()->except('orientacion');
                $urlH =
                    request()->url() . '?' . http_build_query(array_merge($qsBase, ['orientacion' => 'horizontal']));
                $urlV = request()->url() . '?' . http_build_query(array_merge($qsBase, ['orientacion' => 'vertical']));
            @endphp
            <a href="{{ $urlH }}"
                class="px-3 py-1.5 rounded border text-sm {{ !$ctx['estaGirado'] ? 'bg-gray-800 text-white' : 'hover:bg-gray-50' }}">
                Horizontal
            </a>
            <a href="{{ $urlV }}"
                class="px-3 py-1.5 rounded border text-sm {{ $ctx['estaGirado'] ? 'bg-gray-800 text-white' : 'hover:bg-gray-50' }}">
                Vertical
            </a>
        </div>
    </div>
    {{-- Escenario + Cuadrícula (solo lectura) --}}
    <div class="px-4 sm:px-6 lg:px-8 mt-4">
        <div id="escenario-cuadricula" class="{{ $ctx['estaGirado'] ? 'orient-vertical' : 'orient-horizontal' }}"
            style="
                --cols: {{ $ctx['estaGirado'] ? $ctx['columnasReales'] : $ctx['filasReales'] }};
                --rows: {{ $ctx['estaGirado'] ? $ctx['filasReales'] : $ctx['columnasReales'] }};
                ">
            <div id="cuadricula" aria-label="Cuadrícula de la nave">
                {{-- Overlays existentes (máquinas colocadas) --}}
                @foreach ($localizacionesConMaquina as $loc)
                    <div class="loc-existente" data-id="{{ $loc['id'] }}" data-x1="{{ $loc['x1'] }}"
                        data-y1="{{ $loc['y1'] }}" data-x2="{{ $loc['x2'] }}" data-y2="{{ $loc['y2'] }}"
                        data-maquina-id="{{ $loc['maquina_id'] }}" data-nombre="{{ $loc['maquina_nombre'] }}">
                        <span class="loc-label">{{ $loc['maquina_nombre'] }}</span>
                    </div>
                @endforeach
            </div>

            <div id="info-cuadricula" class="info-cuadricula">
                {{ $columnasVista }} columnas × {{ $filasVista }} filas
            </div>
        </div>
    </div>

    {{-- CSS --}}
    <link rel="stylesheet" href="{{ asset('css/localizaciones/styleLocIndex.css') }}">

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
                const v = getComputedStyle(grid).getPropertyValue('--tam-celda').trim();
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
                        +el.dataset.x1, +el.dataset.y1, +el.dataset.x2, +el.dataset.y2
                    );
                    el.style.left = ((x - 1) * celdaPx) + 'px';
                    el.style.top = ((y - 1) * celdaPx) + 'px';
                    el.style.width = (w * celdaPx) + 'px';
                    el.style.height = (h * celdaPx) + 'px';
                });
            }

            function ajustarTamCelda() {
                const anchoDisp = escenario.clientWidth - 12;
                const altoDisp = (window.innerHeight - escenario.getBoundingClientRect().top - 24);

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

</x-app-layout>
