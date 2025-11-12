@extends('layouts.app')

@section('title', 'Mapa de Localizaciones - Paquetes')

@section('content')
<div class="container-fluid p-4">
    {{-- Cabecera con información de la obra --}}
    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Mapa de Localizaciones</h1>
                <p class="text-gray-600 mt-1">
                    Obra: <span class="font-semibold">{{ $dimensiones['obra'] ?? 'Sin obra' }}</span> 
                    | Dimensiones: <span class="font-semibold">{{ $dimensiones['ancho'] }}m × {{ $dimensiones['largo'] }}m</span>
                    | Cliente: <span class="font-semibold">{{ $cliente->empresa ?? 'Sin cliente' }}</span>
                </p>
            </div>

            {{-- Selector de obra --}}
            <div class="flex items-center gap-3">
                <label for="obra-select" class="text-sm font-medium text-gray-700">Obra:</label>
                <select 
                    id="obra-select" 
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    onchange="window.location.href = '{{ route('localizaciones.mapa') }}?obra=' + this.value"
                >
                    @foreach($obras as $obra)
                        <option value="{{ $obra->id }}" {{ $obra->id == $obraActualId ? 'selected' : '' }}>
                            {{ $obra->obra }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Contenedor principal: Mapa + Panel lateral --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-4 h-[calc(100vh-200px)]">
        
        {{-- MAPA DE LOCALIZACIONES --}}
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            {{-- Controles del mapa --}}
            <div class="bg-gray-50 border-b border-gray-200 p-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button 
                        id="btn-zoom-in" 
                        class="p-2 bg-white border border-gray-300 rounded hover:bg-gray-100 transition"
                        title="Acercar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                        </svg>
                    </button>
                    <button 
                        id="btn-zoom-out" 
                        class="p-2 bg-white border border-gray-300 rounded hover:bg-gray-100 transition"
                        title="Alejar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/>
                        </svg>
                    </button>
                    <button 
                        id="btn-reset-zoom" 
                        class="p-2 bg-white border border-gray-300 rounded hover:bg-gray-100 transition"
                        title="Restablecer zoom"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>

                <div class="text-sm text-gray-600">
                    Zoom: <span id="zoom-level" class="font-semibold">100%</span>
                </div>
            </div>

            {{-- Contenedor del canvas con scroll --}}
            <div 
                id="map-container" 
                class="overflow-auto h-full"
                style="background-color: #f9fafb; background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 20px 20px;"
            >
                <canvas 
                    id="mapa-canvas" 
                    class="cursor-move"
                    data-ctx="{{ json_encode($ctx) }}"
                ></canvas>
            </div>
        </div>

        {{-- PANEL LATERAL: LISTA DE PAQUETES --}}
        <div class="bg-white rounded-lg shadow-sm overflow-hidden flex flex-col">
            {{-- Cabecera del panel --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                <h2 class="text-lg font-bold">Paquetes Ubicados</h2>
                <p class="text-sm text-blue-100 mt-1">
                    Total: {{ $paquetesConLocalizacion->count() }} paquetes
                </p>
            </div>

            {{-- Buscador de paquetes --}}
            <div class="p-3 border-b border-gray-200">
                <input 
                    type="text" 
                    id="search-paquetes" 
                    placeholder="Buscar por código o ubicación..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>

            {{-- Lista de paquetes con scroll --}}
            <div class="flex-1 overflow-y-auto p-3" id="lista-paquetes">
                @forelse($paquetesConLocalizacion as $paquete)
                    <div 
                        class="paquete-item bg-gray-50 rounded-lg p-3 mb-2 border border-gray-200 hover:border-blue-400 hover:shadow-md transition cursor-pointer"
                        data-paquete-id="{{ $paquete['id'] }}"
                        data-x1="{{ $paquete['x1'] }}"
                        data-y1="{{ $paquete['y1'] }}"
                        data-x2="{{ $paquete['x2'] }}"
                        data-y2="{{ $paquete['y2'] }}"
                    >
                        {{-- Código del paquete --}}
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-gray-800 text-sm">
                                📦 {{ $paquete['codigo'] }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $paquete['cantidad_elementos'] }} elementos
                            </span>
                        </div>

                        {{-- Información del paquete --}}
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                            <div>
                                <span class="text-gray-500">Peso:</span>
                                <span class="font-semibold">{{ number_format($paquete['peso'], 2) }} kg</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Tipo:</span>
                                <span class="font-semibold capitalize">{{ $paquete['tipo_contenido'] }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-gray-500">Ubicación:</span>
                                <span class="font-semibold">
                                    ({{ $paquete['x1'] }},{{ $paquete['y1'] }}) → ({{ $paquete['x2'] }},{{ $paquete['y2'] }})
                                </span>
                            </div>
                        </div>

                        {{-- Indicador visual del tipo --}}
                        <div class="mt-2 flex items-center gap-2">
                            @if($paquete['tipo_contenido'] === 'barras')
                                <span class="inline-block w-3 h-3 bg-blue-500 rounded-full"></span>
                                <span class="text-xs text-gray-600">Barras</span>
                            @elseif($paquete['tipo_contenido'] === 'estribos')
                                <span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span>
                                <span class="text-xs text-gray-600">Estribos</span>
                            @else
                                <span class="inline-block w-3 h-3 bg-orange-500 rounded-full"></span>
                                <span class="text-xs text-gray-600">Mixto</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="font-medium">No hay paquetes ubicados</p>
                        <p class="text-sm mt-1">Los paquetes con localización aparecerán aquí</p>
                    </div>
                @endforelse
            </div>

            {{-- Leyenda --}}
            <div class="border-t border-gray-200 p-3 bg-gray-50">
                <h3 class="text-xs font-bold text-gray-700 mb-2">LEYENDA</h3>
                <div class="space-y-1 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-blue-500 rounded"></div>
                        <span>Barras</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-green-500 rounded"></div>
                        <span>Estribos</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-orange-500 rounded"></div>
                        <span>Mixto</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-gray-400 rounded"></div>
                        <span>Máquinas</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts JavaScript --}}
@push('scripts')
<script>
/**
 * MAPA DE LOCALIZACIONES DE PAQUETES
 * Sistema interactivo para visualizar ubicaciones de paquetes en el almacén
 */

// ==================== CONFIGURACIÓN GLOBAL ====================
const CONFIG = {
    CELL_SIZE: 20,           // Tamaño de cada celda en píxeles (0.5m real = 20px)
    ZOOM_STEP: 0.1,          // Incremento de zoom por click
    ZOOM_MIN: 0.5,           // Zoom mínimo (50%)
    ZOOM_MAX: 3.0,           // Zoom máximo (300%)
    COLORS: {
        GRID: '#e5e7eb',     // Color de la cuadrícula
        MAQUINA: '#9ca3af',  // Color de máquinas
        ZONA_TRANSITABLE: '#d1fae5', // Verde claro
        ZONA_ALMACENAMIENTO: '#fef3c7', // Amarillo claro
        ZONA_CARGA: '#dbeafe', // Azul claro
        PAQUETE_BARRAS: '#3b82f6', // Azul
        PAQUETE_ESTRIBOS: '#10b981', // Verde
        PAQUETE_MIXTO: '#f97316', // Naranja
        HIGHLIGHT: '#fbbf24', // Amarillo para resaltado
        TEXT: '#1f2937',     // Color del texto
    }
};

// ==================== VARIABLES GLOBALES ====================
let canvas, ctx, mapContainer;
let currentZoom = 1.0;
let isDragging = false;
let dragStart = { x: 0, y: 0 };
let scrollStart = { x: 0, y: 0 };
let highlightedPaquete = null;

// Contexto del mapa (dimensiones, orientación, etc.)
let mapContext = {};

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🗺️ Inicializando Mapa de Localizaciones');
    
    // Obtener elementos del DOM
    canvas = document.getElementById('mapa-canvas');
    mapContainer = document.getElementById('map-container');
    
    if (!canvas) {
        console.error('❌ No se encontró el canvas del mapa');
        return;
    }
    
    ctx = canvas.getContext('2d');
    
    // Cargar contexto desde el atributo data
    try {
        mapContext = JSON.parse(canvas.dataset.ctx);
        console.log('📊 Contexto del mapa:', mapContext);
    } catch (e) {
        console.error('❌ Error al parsear contexto del mapa:', e);
        return;
    }
    
    // Configurar canvas
    setupCanvas();
    
    // Dibujar mapa inicial
    drawMap();
    
    // Configurar event listeners
    setupEventListeners();
    
    console.log('✅ Mapa inicializado correctamente');
});

// ==================== CONFIGURACIÓN DEL CANVAS ====================
/**
 * Configura las dimensiones del canvas basándose en el contexto del mapa
 * El canvas se dibuja con un tamaño que depende de las dimensiones de la nave
 */
function setupCanvas() {
    // Calcular dimensiones del canvas basadas en el grid de la vista
    const width = mapContext.columnasVista * CONFIG.CELL_SIZE;
    const height = mapContext.filasVista * CONFIG.CELL_SIZE;
    
    // Configurar tamaño del canvas
    canvas.width = width;
    canvas.height = height;
    
    // Aplicar estilos para que sea responsive
    canvas.style.width = '100%';
    canvas.style.maxWidth = width + 'px';
    canvas.style.height = 'auto';
    
    console.log(`📐 Canvas configurado: ${width}x${height}px`);
}

// ==================== DIBUJADO DEL MAPA ====================
/**
 * Función principal que dibuja todos los elementos del mapa
 */
function drawMap() {
    // Limpiar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Aplicar transformación de zoom
    ctx.save();
    ctx.scale(currentZoom, currentZoom);
    
    // 1. Dibujar grid de fondo
    drawGrid();
    
    // 2. Dibujar zonas (transitable, almacenamiento, carga/descarga)
    drawZonas();
    
    // 3. Dibujar máquinas
    drawMaquinas();
    
    // 4. Dibujar paquetes
    drawPaquetes();
    
    // 5. Dibujar sectores/etiquetas (cada 20m)
    drawSectors();
    
    ctx.restore();
}

/**
 * Dibuja la cuadrícula de fondo del mapa
 * Cada celda representa 0.5 metros en el espacio real
 */
function drawGrid() {
    ctx.strokeStyle = CONFIG.COLORS.GRID;
    ctx.lineWidth = 0.5;
    
    const width = mapContext.columnasVista * CONFIG.CELL_SIZE;
    const height = mapContext.filasVista * CONFIG.CELL_SIZE;
    
    // Líneas verticales
    for (let x = 0; x <= width; x += CONFIG.CELL_SIZE) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, height);
        ctx.stroke();
    }
    
    // Líneas horizontales
    for (let y = 0; y <= height; y += CONFIG.CELL_SIZE) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(width, y);
        ctx.stroke();
    }
}

/**
 * Dibuja las zonas definidas en el mapa (transitable, almacenamiento, etc.)
 */
function drawZonas() {
    const zonas = @json($localizacionesZonas);
    
    zonas.forEach(zona => {
        let coords = transformCoords(zona.x1, zona.y1, zona.x2, zona.y2);
        
        // Color según tipo de zona
        let color = CONFIG.COLORS.ZONA_TRANSITABLE;
        if (zona.tipo === 'almacenamiento') color = CONFIG.COLORS.ZONA_ALMACENAMIENTO;
        if (zona.tipo === 'carga_descarga') color = CONFIG.COLORS.ZONA_CARGA;
        
        // Dibujar rectángulo de la zona
        ctx.fillStyle = color;
        ctx.fillRect(coords.x, coords.y, coords.width, coords.height);
        
        // Borde
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 1;
        ctx.strokeRect(coords.x, coords.y, coords.width, coords.height);
        
        // Etiqueta del nombre
        drawLabel(coords.x + coords.width / 2, coords.y + coords.height / 2, zona.nombre);
    });
}

/**
 * Dibuja las máquinas ubicadas en el mapa
 */
function drawMaquinas() {
    const maquinas = @json($localizacionesMaquinas);
    
    maquinas.forEach(maquina => {
        let coords = transformCoords(maquina.x1, maquina.y1, maquina.x2, maquina.y2);
        
        // Dibujar rectángulo de la máquina
        ctx.fillStyle = CONFIG.COLORS.MAQUINA;
        ctx.fillRect(coords.x, coords.y, coords.width, coords.height);
        
        // Borde más grueso
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.strokeRect(coords.x, coords.y, coords.width, coords.height);
        
        // Etiqueta con nombre de la máquina
        drawLabel(coords.x + coords.width / 2, coords.y + coords.height / 2, maquina.nombre, 'bold');
    });
}

/**
 * Dibuja los paquetes ubicados en el mapa
 */
function drawPaquetes() {
    const paquetes = @json($paquetesConLocalizacion);
    
    paquetes.forEach(paquete => {
        let coords = transformCoords(paquete.x1, paquete.y1, paquete.x2, paquete.y2);
        
        // Color según tipo de contenido
        let color = CONFIG.COLORS.PAQUETE_MIXTO;
        if (paquete.tipo_contenido === 'barras') color = CONFIG.COLORS.PAQUETE_BARRAS;
        if (paquete.tipo_contenido === 'estribos') color = CONFIG.COLORS.PAQUETE_ESTRIBOS;
        
        // Resaltado si está seleccionado
        if (highlightedPaquete === paquete.id) {
            ctx.fillStyle = CONFIG.COLORS.HIGHLIGHT;
            ctx.fillRect(coords.x - 3, coords.y - 3, coords.width + 6, coords.height + 6);
        }
        
        // Dibujar rectángulo del paquete
        ctx.fillStyle = color;
        ctx.fillRect(coords.x, coords.y, coords.width, coords.height);
        
        // Borde
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 1.5;
        ctx.strokeRect(coords.x, coords.y, coords.width, coords.height);
        
        // Etiqueta con código del paquete
        drawLabel(
            coords.x + coords.width / 2, 
            coords.y + coords.height / 2, 
            paquete.codigo,
            'normal',
            '#fff'
        );
    });
}

/**
 * Dibuja sectores/etiquetas cada 20 metros
 */
function drawSectors() {
    const SECTOR_SIZE = 20; // 20 metros
    const cellsPerSector = SECTOR_SIZE * 2; // 40 celdas (cada celda = 0.5m)
    
    ctx.fillStyle = CONFIG.COLORS.TEXT;
    ctx.font = 'bold 14px Arial';
    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';
    
    // Etiquetas en el eje vertical (cada 20m)
    for (let i = 0; i * cellsPerSector < mapContext.filasVista; i++) {
        const y = i * cellsPerSector * CONFIG.CELL_SIZE;
        const metros = i * SECTOR_SIZE;
        ctx.fillText(`${metros}m`, 5, y + 5);
    }
}

/**
 * Transforma coordenadas del grid (x1,y1,x2,y2) a píxeles del canvas
 * Toma en cuenta la orientación del mapa (girado o no)
 */
function transformCoords(x1, y1, x2, y2) {
    let px1, py1, px2, py2;
    
    if (mapContext.estaGirado) {
        // Si está girado (vertical), intercambiar coordenadas
        px1 = (y1 - 1) * CONFIG.CELL_SIZE;
        py1 = (x1 - 1) * CONFIG.CELL_SIZE;
        px2 = y2 * CONFIG.CELL_SIZE;
        py2 = x2 * CONFIG.CELL_SIZE;
    } else {
        // Orientación normal (horizontal)
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

/**
 * Dibuja una etiqueta de texto centrada en las coordenadas dadas
 */
function drawLabel(x, y, text, fontWeight = 'normal', textColor = CONFIG.COLORS.TEXT) {
    ctx.save();
    
    ctx.font = `${fontWeight} 12px Arial`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Sombra para mejorar legibilidad
    ctx.shadowColor = 'rgba(255, 255, 255, 0.8)';
    ctx.shadowBlur = 3;
    ctx.fillStyle = textColor;
    
    ctx.fillText(text, x, y);
    
    ctx.restore();
}

// ==================== CONTROLES DE ZOOM ====================
/**
 * Incrementa el nivel de zoom
 */
function zoomIn() {
    if (currentZoom < CONFIG.ZOOM_MAX) {
        currentZoom = Math.min(currentZoom + CONFIG.ZOOM_STEP, CONFIG.ZOOM_MAX);
        updateZoom();
    }
}

/**
 * Disminuye el nivel de zoom
 */
function zoomOut() {
    if (currentZoom > CONFIG.ZOOM_MIN) {
        currentZoom = Math.max(currentZoom - CONFIG.ZOOM_STEP, CONFIG.ZOOM_MIN);
        updateZoom();
    }
}

/**
 * Restablece el zoom al 100%
 */
function resetZoom() {
    currentZoom = 1.0;
    updateZoom();
}

/**
 * Actualiza la visualización del zoom
 */
function updateZoom() {
    drawMap();
    document.getElementById('zoom-level').textContent = Math.round(currentZoom * 100) + '%';
}

// ==================== INTERACCIÓN CON PAQUETES ====================
/**
 * Resalta un paquete específico en el mapa
 */
function highlightPaquete(paqueteId) {
    highlightedPaquete = paqueteId;
    drawMap();
}

/**
 * Desactiva el resaltado de paquetes
 */
function unhighlightPaquete() {
    highlightedPaquete = null;
    drawMap();
}

// ==================== EVENT LISTENERS ====================
/**
 * Configura todos los event listeners del mapa
 */
function setupEventListeners() {
    // Botones de zoom
    document.getElementById('btn-zoom-in')?.addEventListener('click', zoomIn);
    document.getElementById('btn-zoom-out')?.addEventListener('click', zoomOut);
    document.getElementById('btn-reset-zoom')?.addEventListener('click', resetZoom);
    
    // Zoom con rueda del mouse
    mapContainer.addEventListener('wheel', function(e) {
        e.preventDefault();
        if (e.deltaY < 0) {
            zoomIn();
        } else {
            zoomOut();
        }
    });
    
    // Arrastrar el mapa (pan)
    canvas.addEventListener('mousedown', function(e) {
        isDragging = true;
        dragStart = { x: e.clientX, y: e.clientY };
        scrollStart = { 
            x: mapContainer.scrollLeft, 
            y: mapContainer.scrollTop 
        };
        canvas.style.cursor = 'grabbing';
    });
    
    document.addEventListener('mousemove', function(e) {
        if (isDragging) {
            const dx = e.clientX - dragStart.x;
            const dy = e.clientY - dragStart.y;
            mapContainer.scrollLeft = scrollStart.x - dx;
            mapContainer.scrollTop = scrollStart.y - dy;
        }
    });
    
    document.addEventListener('mouseup', function() {
        isDragging = false;
        canvas.style.cursor = 'move';
    });
    
    // Click en items de la lista de paquetes
    document.querySelectorAll('.paquete-item').forEach(item => {
        item.addEventListener('click', function() {
            const paqueteId = parseInt(this.dataset.paqueteId);
            
            // Remover selección anterior
            document.querySelectorAll('.paquete-item').forEach(i => {
                i.classList.remove('border-blue-500', 'bg-blue-50');
            });
            
            // Seleccionar item actual
            this.classList.add('border-blue-500', 'bg-blue-50');
            
            // Resaltar en el mapa
            highlightPaquete(paqueteId);
            
            // Scroll al paquete en el mapa (opcional)
            scrollToPaquete(this.dataset.x1, this.dataset.y1);
        });
        
        // Hover effect
        item.addEventListener('mouseenter', function() {
            const paqueteId = parseInt(this.dataset.paqueteId);
            highlightPaquete(paqueteId);
        });
        
        item.addEventListener('mouseleave', function() {
            // Solo unhighlight si no está seleccionado
            if (!this.classList.contains('border-blue-500')) {
                unhighlightPaquete();
            }
        });
    });
    
    // Buscador de paquetes
    const searchInput = document.getElementById('search-paquetes');
    searchInput?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        document.querySelectorAll('.paquete-item').forEach(item => {
            const codigo = item.textContent.toLowerCase();
            if (codigo.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

/**
 * Hace scroll al contenedor para mostrar un paquete específico
 */
function scrollToPaquete(x1, y1) {
    const coords = transformCoords(x1, y1, x1, y1);
    
    const scrollX = (coords.x * currentZoom) - (mapContainer.clientWidth / 2);
    const scrollY = (coords.y * currentZoom) - (mapContainer.clientHeight / 2);
    
    mapContainer.scrollTo({
        left: Math.max(0, scrollX),
        top: Math.max(0, scrollY),
        behavior: 'smooth'
    });
}

// ==================== UTILIDADES ====================
/**
 * Imprime información de debugging en consola
 */
function logDebugInfo() {
    console.log('🐛 DEBUG INFO');
    console.log('Zoom actual:', currentZoom);
    console.log('Dimensiones canvas:', canvas.width, 'x', canvas.height);
    console.log('Paquetes resaltados:', highlightedPaquete);
    console.log('Contexto del mapa:', mapContext);
}

// Exponer funciones globales para debugging
window.mapaDebug = {
    logInfo: logDebugInfo,
    redraw: drawMap,
    zoom: { in: zoomIn, out: zoomOut, reset: resetZoom }
};

console.log('💡 Usa window.mapaDebug para acceder a funciones de debugging');
</script>
@endpush
@endsection