<x-app-layout>
    {{-- Título de la pestaña / header --}}
    <x-slot name="title">Mapa de Localizaciones - {{ config('app.name') }}</x-slot>

    {{-- Menú lateral/top si lo usas en todas --}}
    <x-menu.planillas />

    <div class="w-full p-4 sm:p-6">
        {{-- === Cabecera de la página === --}}
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Mapa de Localizaciones</h1>
                    <p class="text-gray-600 mt-1">
                        Obra: <span class="font-semibold">{{ $dimensiones['obra'] ?? 'Sin obra' }}</span>
                        | Dimensiones:
                        <span class="font-semibold">{{ $dimensiones['ancho'] }}m × {{ $dimensiones['largo'] }}m</span>
                        | Cliente: <span class="font-semibold">{{ $cliente->empresa ?? 'Sin cliente' }}</span>
                    </p>
                </div>

                {{-- Selector de obra --}}
                <div class="flex items-center gap-3">
                    <label for="obra-select" class="text-sm font-medium text-gray-700">Obra:</label>
                    <select
                        id="obra-select"
                        class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        onchange="window.location.href = '{{ route('mapa.paquetes') }}?obra=' + this.value">
                        @foreach($obras as $obra)
                        <option value="{{ $obra->id }}" {{ $obra->id == $obraActualId ? 'selected' : '' }}>
                            {{ $obra->obra }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- === Contenido principal: GRID (Mapa + Panel) === --}}
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-4 h-[calc(100vh-200px)]">
            {{-- MAPA --}}
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                {{-- Controles --}}
                <div class="bg-gray-50 border-b border-gray-200 p-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button id="btn-zoom-in" class="p-2 bg-white border border-gray-300 rounded hover:bg-gray-100 transition" title="Acercar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                            </svg>
                        </button>
                        <button id="btn-zoom-out" class="p-2 bg-white border border-gray-300 rounded hover:bg-gray-100 transition" title="Alejar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                            </svg>
                        </button>
                        <button id="btn-reset-zoom" class="p-2 bg-white border border-gray-300 rounded hover:bg-gray-100 transition" title="Restablecer zoom">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                    <div class="text-sm text-gray-600">
                        Zoom: <span id="zoom-level" class="font-semibold">100%</span>
                    </div>
                </div>

                {{-- Canvas con scroll --}}
                <div id="map-container" class="overflow-auto h-full"
                    style="padding:20px; background-color:#f9fafb;
            background-image:radial-gradient(#d1d5db 1px, transparent 1px);
            background-size:20px 20px;">
                    <canvas id="mapa-canvas" class="cursor-move" data-ctx='@json($ctx)'></canvas>
                </div>

            </div>

            {{-- PANEL LATERAL: Lista de paquetes (tu mismo contenido) --}}
            <div class="bg-white rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                    <h2 class="text-lg font-bold">Paquetes Ubicados</h2>
                    <p class="text-sm text-blue-100 mt-1">Total: {{ $paquetesConLocalizacion->count() }} paquetes</p>
                </div>

                <div class="p-3 border-b border-gray-200">
                    <input type="text" id="search-paquetes" placeholder="Buscar por código o ubicación..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                </div>

                <div class="flex-1 overflow-y-auto p-3" id="lista-paquetes">
                    @forelse($paquetesConLocalizacion as $paquete)
                    <div class="paquete-item bg-gray-50 rounded-lg p-3 mb-2 border border-gray-200 hover:border-blue-400 hover:shadow-md transition cursor-pointer"
                        data-paquete-id="{{ $paquete['id'] }}"
                        data-x1="{{ $paquete['x1'] }}" data-y1="{{ $paquete['y1'] }}"
                        data-x2="{{ $paquete['x2'] }}" data-y2="{{ $paquete['y2'] }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-gray-800 text-sm">📦 {{ $paquete['codigo'] }}</span>
                            <span class="text-xs text-gray-500">{{ $paquete['cantidad_elementos'] }} elementos</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                            <div><span class="text-gray-500">Peso:</span> <span class="font-semibold">{{ number_format($paquete['peso'], 2) }} kg</span></div>
                            <div><span class="text-gray-500">Tipo:</span> <span class="font-semibold capitalize">{{ $paquete['tipo_contenido'] }}</span></div>
                            <div class="col-span-2">
                                <span class="text-gray-500">Ubicación:</span>
                                <span class="font-semibold">({{ $paquete['x1'] }},{{ $paquete['y1'] }}) → ({{ $paquete['x2'] }},{{ $paquete['y2'] }})</span>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            @if($paquete['tipo_contenido'] === 'barras')
                            <span class="inline-block w-3 h-3 bg-blue-500 rounded-full"></span><span class="text-xs text-gray-600">Barras</span>
                            @elseif($paquete['tipo_contenido'] === 'estribos')
                            <span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span><span class="text-xs text-gray-600">Estribos</span>
                            @else
                            <span class="inline-block w-3 h-3 bg-orange-500 rounded-full"></span><span class="text-xs text-gray-600">Mixto</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="font-medium">No hay paquetes ubicados</p>
                        <p class="text-sm mt-1">Los paquetes con localización aparecerán aquí</p>
                    </div>
                    @endforelse
                </div>

                <div class="border-t border-gray-200 p-3 bg-gray-50">
                    <h3 class="text-xs font-bold text-gray-700 mb-2">LEYENDA</h3>
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-blue-500 rounded"></div><span>Barras</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-green-500 rounded"></div><span>Estribos</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-orange-500 rounded"></div><span>Mixto</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-gray-400 rounded"></div><span>Máquinas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Scripts ===== --}}
    @push('scripts')
    <script>
        // === (tu mismo JS tal cual) ===
        const CONFIG = {
            CELL_SIZE: 20,
            ZOOM_STEP: 0.1,
            ZOOM_MIN: 0.5,
            ZOOM_MAX: 3.0,
            COLORS: {
                GRID: '#e5e7eb',
                MAQUINA: '#9ca3af',
                ZONA_TRANSITABLE: '#d1fae5',
                ZONA_ALMACENAMIENTO: '#fef3c7',
                ZONA_CARGA: '#dbeafe',
                PAQUETE_BARRAS: '#3b82f6',
                PAQUETE_ESTRIBOS: '#10b981',
                PAQUETE_MIXTO: '#f97316',
                HIGHLIGHT: '#fbbf24',
                TEXT: '#1f2937'
            }
        };
        let canvas, ctx, mapContainer, currentZoom = 1.0,
            isDragging = false,
            dragStart = {
                x: 0,
                y: 0
            },
            scrollStart = {
                x: 0,
                y: 0
            },
            highlightedPaquete = null;
        let mapContext = {};

        document.addEventListener('DOMContentLoaded', function() {
            canvas = document.getElementById('mapa-canvas');
            mapContainer = document.getElementById('map-container');
            if (!canvas) {
                console.error('Canvas no encontrado');
                return;
            }
            ctx = canvas.getContext('2d');

            try {
                mapContext = JSON.parse(canvas.dataset.ctx);
            } catch (e) {
                console.error('Error parse ctx', e);
                return;
            }

            setupCanvas();
            drawMap();
            setupEventListeners();
        });

        function setupCanvas() {
            const width = mapContext.columnasVista * CONFIG.CELL_SIZE;
            const height = mapContext.filasVista * CONFIG.CELL_SIZE;
            canvas.width = width;
            canvas.height = height;
            canvas.style.width = '100%';
            canvas.style.maxWidth = width + 'px';
            canvas.style.height = 'auto';
        }

        function drawMap() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.save();
            ctx.scale(currentZoom, currentZoom);
            drawGrid();
            drawZonas();
            drawMaquinas();
            drawPaquetes();
            drawSectors();
            ctx.restore();
        }

        function drawGrid() {
            ctx.strokeStyle = CONFIG.COLORS.GRID;
            ctx.lineWidth = 0.5;
            const w = mapContext.columnasVista * CONFIG.CELL_SIZE,
                h = mapContext.filasVista * CONFIG.CELL_SIZE;
            for (let x = 0; x <= w; x += CONFIG.CELL_SIZE) {
                ctx.beginPath();
                ctx.moveTo(x, 0);
                ctx.lineTo(x, h);
                ctx.stroke();
            }
            for (let y = 0; y <= h; y += CONFIG.CELL_SIZE) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(w, y);
                ctx.stroke();
            }
        }

        function drawZonas() {
            const zonas = @json($localizacionesZonas);
            zonas.forEach(z => {
                const c = transformCoords(z.x1, z.y1, z.x2, z.y2);
                let color = CONFIG.COLORS.ZONA_TRANSITABLE;
                if (z.tipo === 'almacenamiento') color = CONFIG.COLORS.ZONA_ALMACENAMIENTO;
                if (z.tipo === 'carga_descarga') color = CONFIG.COLORS.ZONA_CARGA;
                ctx.fillStyle = color;
                ctx.fillRect(c.x, c.y, c.width, c.height);
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 1;
                ctx.strokeRect(c.x, c.y, c.width, c.height);
                drawLabel(c.x + c.width / 2, c.y + c.height / 2, z.nombre, 'bold');
            });
        }

        function drawMaquinas() {
            const maquinas = @json($localizacionesMaquinas);
            maquinas.forEach(m => {
                const c = transformCoords(m.x1, m.y1, m.x2, m.y2);
                ctx.fillStyle = CONFIG.COLORS.MAQUINA;
                ctx.fillRect(c.x, c.y, c.width, c.height);
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 2;
                ctx.strokeRect(c.x, c.y, c.width, c.height);
                drawLabel(c.x + c.width / 2, c.y + c.height / 2, m.nombre, 'bold');
            });
        }

        function drawPaquetes() {
            const paquetes = @json($paquetesConLocalizacion);
            paquetes.forEach(p => {
                const c = transformCoords(p.x1, p.y1, p.x2, p.y2);
                let color = p.tipo_contenido === 'barras' ? CONFIG.COLORS.PAQUETE_BARRAS :
                    p.tipo_contenido === 'estribos' ? CONFIG.COLORS.PAQUETE_ESTRIBOS : CONFIG.COLORS.PAQUETE_MIXTO;

                if (highlightedPaquete === p.id) {
                    ctx.fillStyle = CONFIG.COLORS.HIGHLIGHT;
                    ctx.fillRect(c.x - 3, c.y - 3, c.width + 6, c.height + 6);
                }

                ctx.fillStyle = color;
                ctx.fillRect(c.x, c.y, c.width, c.height);
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 1.5;
                ctx.strokeRect(c.x, c.y, c.width, c.height);
                drawLabel(c.x + c.width / 2, c.y + c.height / 2, p.codigo, 'normal', '#fff');
            });
        }

        function drawSectors() {
            const SECTOR_SIZE = 20,
                cellsPerSector = SECTOR_SIZE * 2;
            ctx.fillStyle = CONFIG.COLORS.TEXT;
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            for (let i = 0; i * cellsPerSector < mapContext.filasVista; i++) {
                const y = i * cellsPerSector * CONFIG.CELL_SIZE;
                const m = i * SECTOR_SIZE;
                ctx.fillText(`${m}m`, 5, y + 5);
            }
        }

        function transformCoords(x1, y1, x2, y2) {
            let px1, py1, px2, py2;
            if (mapContext.estaGirado) {
                px1 = (y1 - 1) * CONFIG.CELL_SIZE;
                py1 = (x1 - 1) * CONFIG.CELL_SIZE;
                px2 = y2 * CONFIG.CELL_SIZE;
                py2 = x2 * CONFIG.CELL_SIZE;
            } else {
                px1 = (x1 - 1) * CONFIG.CELL_SIZE;
                py1 = (y1 - 1) * CONFIG.CELL_SIZE;
                px2 = x2 * CONFIG.CELL_SIZE;
                py2 = y2 * CONFIG.CELL_SIZE;
            }
            return {
                x: px1,
                y: py1,
                width: px2 - px1,
                height: py2 - py1
            };
        }

        function drawLabel(x, y, text, weight = 'normal', color = CONFIG.COLORS.TEXT) {
            ctx.save();
            ctx.font = `${weight} 12px Arial`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.shadowColor = 'rgba(255,255,255,0.8)';
            ctx.shadowBlur = 3;
            ctx.fillStyle = color;
            ctx.fillText(text, x, y);
            ctx.restore();
        }

        function zoomIn() {
            if (currentZoom < CONFIG.ZOOM_MAX) {
                currentZoom = Math.min(currentZoom + CONFIG.ZOOM_STEP, CONFIG.ZOOM_MAX);
                updateZoom();
            }
        }

        function zoomOut() {
            if (currentZoom > CONFIG.ZOOM_MIN) {
                currentZoom = Math.max(currentZoom - CONFIG.ZOOM_STEP, CONFIG.ZOOM_MIN);
                updateZoom();
            }
        }

        function resetZoom() {
            currentZoom = 1.0;
            updateZoom();
        }

        function updateZoom() {
            drawMap();
            document.getElementById('zoom-level').textContent = Math.round(currentZoom * 100) + '%';
        }

        function highlightPaquete(id) {
            highlightedPaquete = id;
            drawMap();
        }

        function unhighlightPaquete() {
            highlightedPaquete = null;
            drawMap();
        }

        function setupEventListeners() {
            document.getElementById('btn-zoom-in')?.addEventListener('click', zoomIn);
            document.getElementById('btn-zoom-out')?.addEventListener('click', zoomOut);
            document.getElementById('btn-reset-zoom')?.addEventListener('click', resetZoom);

            mapContainer.addEventListener('wheel', e => {
                e.preventDefault();
                e.deltaY < 0 ? zoomIn() : zoomOut();
            });

            canvas.addEventListener('mousedown', e => {
                isDragging = true;
                dragStart = {
                    x: e.clientX,
                    y: e.clientY
                };
                scrollStart = {
                    x: mapContainer.scrollLeft,
                    y: mapContainer.scrollTop
                };
                canvas.style.cursor = 'grabbing';
            });
            document.addEventListener('mousemove', e => {
                if (!isDragging) return;
                mapContainer.scrollLeft = scrollStart.x - (e.clientX - dragStart.x);
                mapContainer.scrollTop = scrollStart.y - (e.clientY - dragStart.y);
            });
            document.addEventListener('mouseup', () => {
                isDragging = false;
                canvas.style.cursor = 'move';
            });

            document.querySelectorAll('.paquete-item').forEach(item => {
                item.addEventListener('click', function() {
                    const id = parseInt(this.dataset.paqueteId);
                    document.querySelectorAll('.paquete-item').forEach(i => i.classList.remove('border-blue-500', 'bg-blue-50'));
                    this.classList.add('border-blue-500', 'bg-blue-50');
                    highlightPaquete(id);
                    scrollToPaquete(this.dataset.x1, this.dataset.y1);
                });
                item.addEventListener('mouseenter', function() {
                    highlightPaquete(parseInt(this.dataset.paqueteId));
                });
                item.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('border-blue-500')) unhighlightPaquete();
                });
            });

            document.getElementById('search-paquetes')?.addEventListener('input', e => {
                const q = e.target.value.toLowerCase();
                document.querySelectorAll('.paquete-item').forEach(item => {
                    const txt = item.textContent.toLowerCase();
                    item.style.display = txt.includes(q) ? 'block' : 'none';
                });
            });
        }

        function scrollToPaquete(x1, y1) {
            const c = transformCoords(x1, y1, x1, y1);
            const left = (c.x * currentZoom) - (mapContainer.clientWidth / 2);
            const top = (c.y * currentZoom) - (mapContainer.clientHeight / 2);
            mapContainer.scrollTo({
                left: Math.max(0, left),
                top: Math.max(0, top),
                behavior: 'smooth'
            });
        }

        window.mapaDebug = {
            redraw: drawMap,
            zoom: {
                in: zoomIn,
                out: zoomOut,
                reset: resetZoom
            }
        };
    </script>
    @endpush
</x-app-layout>