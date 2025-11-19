{{-- Vista parcial para cargar el componente de mapa vía AJAX --}}
@php
    $mapId = 'mapa-ajax-' . uniqid();
    $isVertical = !empty($ctx['estaGirado']);
    $colsReales = $ctx['columnasReales'] ?? 0;
    $filasReales = $ctx['filasReales'] ?? 0;
    $columnasVista = $isVertical ? $colsReales : $filasReales;
    $filasVista = $isVertical ? $filasReales : $colsReales;
@endphp

<div class="mapa-ajax-wrapper">
    {{-- Información de la nave --}}
    <div class="bg-gray-50 p-3 rounded-lg mb-3 text-sm">
        <p class="font-semibold text-gray-800">{{ $dimensiones['obra'] ?? 'Nave' }}</p>
        <p class="text-gray-600">
            {{ $dimensiones['ancho'] }}m x {{ $dimensiones['largo'] }}m
            ({{ $colsReales }} x {{ $filasReales }} celdas)
        </p>
    </div>

    {{-- Contenedor del mapa --}}
    <div id="{{ $mapId }}-escenario"
        data-mapa-canvas
        data-mapa-id="{{ $mapId }}"
        data-nave-id="{{ $obraActualId }}"
        class="orient-vertical overflow-auto relative bg-white border rounded-lg"
        style="--cols: {{ $columnasVista }}; --rows: {{ $filasVista }}; height: 400px;">

        <div id="{{ $mapId }}-cuadricula" class="relative cuadricula-mapa">
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
                    $tipo = str_replace('-', '_', $loc['tipo'] ?? 'transitable');
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
                    data-orientacion="{{ $paquete['orientacion'] ?? 'I' }}">
                </div>
            @endforeach

            {{-- Ghost del paquete a ubicar (se añadirá dinámicamente) --}}
            <div id="{{ $mapId }}-ghost-paquete"
                class="ghost-paquete-mover hidden"
                style="position: absolute; background: rgba(34, 197, 94, 0.8); border: 3px solid #15803d; border-radius: 4px; cursor: move; z-index: 100;">
                <div class="text-center text-white text-xs font-bold p-1 truncate"></div>
            </div>
        </div>

        {{-- Botones de Zoom --}}
        <div class="zoom-controls">
            <button id="{{ $mapId }}-zoom-in" type="button" title="Acercar" class="zoom-btn zoom-btn-in">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M11 8v6M8 11h6M21 21l-4.35-4.35"/>
                </svg>
            </button>
            <button id="{{ $mapId }}-zoom-out" type="button" title="Alejar" class="zoom-btn zoom-btn-out">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M8 11h6M21 21l-4.35-4.35"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Leyenda --}}
    <div class="mt-3 text-xs text-gray-500 flex flex-wrap gap-3">
        <span><span class="inline-block w-3 h-3 bg-gray-400 rounded mr-1"></span> Máquinas</span>
        <span><span class="inline-block w-3 h-3 bg-blue-400 rounded mr-1"></span> Paquetes existentes</span>
        <span><span class="inline-block w-3 h-3 bg-green-500 rounded mr-1"></span> Nuevo paquete</span>
    </div>
</div>

{{-- Script de inicialización del mapa --}}
<script>
(function() {
    const mapId = '{{ $mapId }}';
    const ctx = @json($ctx);
    const escenario = document.getElementById(`${mapId}-escenario`);
    const grid = document.getElementById(`${mapId}-cuadricula`);
    const ghost = document.getElementById(`${mapId}-ghost-paquete`);

    if (!escenario || !grid) {
        console.error('No se encontró el escenario o la cuadrícula');
        return;
    }

    const W = ctx.columnasReales;
    const H = ctx.filasReales;
    const isVertical = !!ctx.estaGirado;
    const viewCols = isVertical ? W : H;
    const viewRows = isVertical ? H : W;

    let cellSize = 8;
    let zoomLevel = 1;

    // Helper functions for coordinate mapping
    function mapPointToView(x, y) {
        if (isVertical) {
            return { x: x, y: y };
        } else {
            return { x: y, y: x }; // Swap for horizontal
        }
    }

    function viewPointToMap(vx, vy) {
        if (isVertical) {
            return { x: vx, y: vy };
        } else {
            return { x: vy, y: vx };
        }
    }

    function updateMap() {
        const containerWidth = escenario.clientWidth - 40;
        const containerHeight = 380;

        // Calcular tamaño base para que quepa
        const maxCellW = Math.floor(containerWidth / viewCols);
        const maxCellH = Math.floor(containerHeight / viewRows);
        const baseCellSize = Math.max(4, Math.min(maxCellW, maxCellH, 12));

        // Aplicar zoom
        cellSize = Math.max(4, baseCellSize * zoomLevel);

        // Calcular el tamaño total del grid
        const gridWidth = viewCols * cellSize;
        const gridHeight = viewRows * cellSize;

        // Aplicar tamaño al grid
        grid.style.width = `${gridWidth}px`;
        grid.style.height = `${gridHeight}px`;
        grid.style.minWidth = `${gridWidth}px`;
        grid.style.minHeight = `${gridHeight}px`;

        // Aplicar el background con el patrón de grid
        grid.style.backgroundSize = `${cellSize}px ${cellSize}px`;
        grid.style.backgroundImage = `
            linear-gradient(to right, #e5e7eb 1px, transparent 1px),
            linear-gradient(to bottom, #e5e7eb 1px, transparent 1px)
        `;
        grid.style.backgroundPosition = '0 0, 0 0';

        // Eliminar transformaciones anteriores si las hubiera
        grid.style.transform = '';

        // Reposicionar elementos existentes
        grid.querySelectorAll('.loc-existente').forEach(el => {
            const x1 = parseInt(el.dataset.x1) || 1;
            const y1 = parseInt(el.dataset.y1) || 1;
            const x2 = parseInt(el.dataset.x2) || x1;
            const y2 = parseInt(el.dataset.y2) || y1;

            const p1 = mapPointToView(x1, y1);
            const p2 = mapPointToView(x2, y2);

            const minVX = Math.min(p1.x, p2.x);
            const maxVX = Math.max(p1.x, p2.x);
            const minVY = Math.min(p1.y, p2.y);
            const maxVY = Math.max(p1.y, p2.y);

            const left = (minVX - 1) * cellSize;
            const top = (minVY - 1) * cellSize;
            const width = (maxVX - minVX + 1) * cellSize;
            const height = (maxVY - minVY + 1) * cellSize;

            el.style.position = 'absolute';
            el.style.left = `${left}px`;
            el.style.top = `${top}px`;
            el.style.width = `${width}px`;
            el.style.height = `${height}px`;
        });

        // Actualizar instancia global
        if (window.mapaAjaxInstance && window.mapaAjaxInstance[mapId]) {
            window.mapaAjaxInstance[mapId].cellSize = cellSize;
            
            // Si el ghost está visible, actualizarlo también
            const instance = window.mapaAjaxInstance[mapId];
            if (!ghost.classList.contains('hidden') && instance.anchoCeldas) {
                instance.mostrarGhost(
                    ghost.querySelector('div').textContent, 
                    instance.anchoCeldas, 
                    instance.largoCeldas
                );
            }
        }
    }

    // Inicializar mapa
    updateMap();

    // Zoom
    const zoomInBtn = document.getElementById(`${mapId}-zoom-in`);
    const zoomOutBtn = document.getElementById(`${mapId}-zoom-out`);

    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', () => {
            zoomLevel = Math.min(3, zoomLevel + 0.2);
            updateMap();
        });
    }

    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', () => {
            zoomLevel = Math.max(0.5, zoomLevel - 0.2);
            updateMap();
        });
    }

    // Pan/Drag para mover el mapa
    let isPanning = false;
    let panStartX = 0;
    let panStartY = 0;
    let panStartScrollLeft = 0;
    let panStartScrollTop = 0;

    escenario.addEventListener('mousedown', (e) => {
        if (e.target.closest('.loc-existente') || 
            e.target.closest('button') || 
            e.target.closest('.ghost-paquete-mover')) {
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
        isPanning = false;
        escenario.style.cursor = '';
    });

    escenario.addEventListener('mouseleave', () => {
        isPanning = false;
        escenario.style.cursor = '';
    });
    
    // Touch support
    let touchStartX = 0;
    let touchStartY = 0;
    let touchStartScrollLeft = 0;
    let touchStartScrollTop = 0;

    escenario.addEventListener('touchstart', (e) => {
        if (e.target.closest('.loc-existente') || 
            e.target.closest('button') || 
            e.target.closest('.ghost-paquete-mover')) {
            return;
        }
        if (e.touches.length === 1) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            touchStartScrollLeft = escenario.scrollLeft;
            touchStartScrollTop = escenario.scrollTop;
        }
    }, { passive: true });

    escenario.addEventListener('touchmove', (e) => {
        if (e.touches.length === 1) {
            const deltaX = e.touches[0].clientX - touchStartX;
            const deltaY = e.touches[0].clientY - touchStartY;
            escenario.scrollLeft = touchStartScrollLeft - deltaX;
            escenario.scrollTop = touchStartScrollTop - deltaY;
        }
    }, { passive: true });

    // Exponer API para el ghost del paquete
    window.mapaAjaxInstance = window.mapaAjaxInstance || {};
    window.mapaAjaxInstance[mapId] = {
        mapId: mapId,
        cellSize: cellSize,
        maxW: viewCols,
        maxH: viewRows,
        ghost: ghost,
        grid: grid,
        coordenadas: { x1: 1, y1: 1, x2: 1, y2: 1 },
        anchoCeldas: 0,
        largoCeldas: 0,

        mostrarGhost: function(codigo, anchoCeldas, largoCeldas) {
            ghost.classList.remove('hidden');
            ghost.querySelector('div').textContent = codigo;
            
            let w, h;
            if (isVertical) {
                w = anchoCeldas;
                h = largoCeldas;
            } else {
                w = largoCeldas;
                h = anchoCeldas;
            }

            ghost.style.width = `${w * this.cellSize}px`;
            ghost.style.height = `${h * this.cellSize}px`;
            
            // Si es la primera vez que se muestra, posicionar en 0,0
            if (ghost.style.left === '' || ghost.style.left === '0px') {
                ghost.style.left = '0px';
                ghost.style.top = '0px';
                this.actualizarCoordenadas(1, 1);
            } else {
                // Recalcular posición visual basada en coordenadas guardadas
                const p = mapPointToView(this.coordenadas.x1, this.coordenadas.y1);
                ghost.style.left = `${(p.x - 1) * this.cellSize}px`;
                ghost.style.top = `${(p.y - 1) * this.cellSize}px`;
            }

            this.anchoCeldas = anchoCeldas;
            this.largoCeldas = largoCeldas;
            this.activarDrag();
        },

        actualizarCoordenadas: function(x1, y1) {
            const x2 = x1 + this.largoCeldas - 1;
            const y2 = y1 + this.anchoCeldas - 1;
            this.coordenadas = { x1, y1, x2, y2 };

            // Actualizar inputs si existen
            const inputX1 = document.getElementById('coord-x1');
            const inputY1 = document.getElementById('coord-y1');
            const inputX2 = document.getElementById('coord-x2');
            const inputY2 = document.getElementById('coord-y2');

            if (inputX1) inputX1.value = x1;
            if (inputY1) inputY1.value = y1;
            if (inputX2) inputX2.value = x2;
            if (inputY2) inputY2.value = y2;

            if (typeof coordenadasPaquete !== 'undefined') {
                coordenadasPaquete.x1 = x1;
                coordenadasPaquete.y1 = y1;
                coordenadasPaquete.x2 = x2;
                coordenadasPaquete.y2 = y2;
            }
        },

        activarDrag: function() {
            const self = this;
            let isDragging = false;
            let startX = 0, startY = 0;
            let ghostStartLeft = 0, ghostStartTop = 0;

            ghost.onmousedown = function(e) {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                ghostStartLeft = parseFloat(ghost.style.left) || 0;
                ghostStartTop = parseFloat(ghost.style.top) || 0;
                e.preventDefault();
            };

            document.onmousemove = function(e) {
                if (!isDragging) return;

                const deltaX = e.clientX - startX;
                const deltaY = e.clientY - startY;

                // Ajustar delta por zoomLevel? No, porque ahora cambiamos el tamaño real
                // El movimiento del mouse es 1:1 con pixeles de pantalla
                let newLeft = ghostStartLeft + deltaX;
                let newTop = ghostStartTop + deltaY;

                const ghostWidth = parseFloat(ghost.style.width) || 0;
                const ghostHeight = parseFloat(ghost.style.height) || 0;

                newLeft = Math.max(0, Math.min(newLeft, (self.maxW * self.cellSize) - ghostWidth));
                newTop = Math.max(0, Math.min(newTop, (self.maxH * self.cellSize) - ghostHeight));

                // Snap to grid
                newLeft = Math.round(newLeft / self.cellSize) * self.cellSize;
                newTop = Math.round(newTop / self.cellSize) * self.cellSize;

                ghost.style.left = `${newLeft}px`;
                ghost.style.top = `${newTop}px`;

                const vx = Math.round(newLeft / self.cellSize) + 1;
                const vy = Math.round(newTop / self.cellSize) + 1;
                
                const p = viewPointToMap(vx, vy);
                self.actualizarCoordenadas(p.x, p.y);
            };

            document.onmouseup = function() {
                isDragging = false;
            };
        },

        getCoordenadas: function() {
            return this.coordenadas;
        }
    };

    // Notificar que el mapa está listo
    escenario.dispatchEvent(new CustomEvent('mapaListo', {
        detail: { mapId: mapId, instance: window.mapaAjaxInstance[mapId] }
    }));
})();
</script>
