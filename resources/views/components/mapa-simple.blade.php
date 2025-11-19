{{-- Componente de mapa simplificado - Solo necesita el ID de la nave --}}
@props(['naveId' => null, 'salidaId' => null, 'modoEdicion' => false])

@php
    $mapId = 'mapa-simple-' . uniqid();
@endphp

{{-- CSS del mapa (solo una vez) --}}
@once
    <link rel="stylesheet" href="{{ asset('css/localizaciones/styleLocIndex.css') }}">
@endonce

<div {{ $attributes->merge(['class' => 'mapa-simple-wrapper h-full overflow-hidden']) }} data-mapa-simple="{{ $mapId }}">
    {{-- Mensaje de carga --}}
    <div id="{{ $mapId }}-loading" class="flex items-center justify-center p-8 bg-gray-50 rounded-lg h-full">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-3"></div>
            <p class="text-gray-600">Cargando mapa...</p>
        </div>
    </div>

    {{-- Contenedor del mapa (oculto inicialmente) --}}
    <div id="{{ $mapId }}-container" class="hidden overflow-auto flex flex-col h-full">
        {{-- Información de la nave --}}
        <div id="{{ $mapId }}-info" class="bg-white p-3 rounded-lg mb-3 text-sm border flex-shrink-0">
            <p class="font-semibold text-gray-800" id="{{ $mapId }}-nombre"></p>
            <p class="text-gray-600" id="{{ $mapId }}-dimensiones"></p>
        </div>

        {{-- Escenario del mapa --}}
        <div id="{{ $mapId }}-escenario"
            data-mapa-canvas
            data-mapa-id="{{ $mapId }}"
            class="orient-vertical overflow-auto relative bg-white border rounded-lg flex-1"
            style="min-height: 0; height: 100%;">

            <div id="{{ $mapId }}-cuadricula" class="relative cuadricula-mapa">
                {{-- Los elementos se insertarán dinámicamente vía JavaScript --}}
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

        {{-- Mensaje de error --}}
        <div id="{{ $mapId }}-error" class="hidden mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm"></div>
    </div>
</div>

<script>
(function() {
    const mapId = '{{ $mapId }}';
    const naveId = {{ $naveId ?? 'null' }};
    const salidaId = {{ $salidaId ?? 'null' }};
    const modoEdicion = {{ $modoEdicion ? 'true' : 'false' }};

    if (!naveId) {
        mostrarError('No se especificó el ID de la nave');
        return;
    }

    // Construir URL con parámetros opcionales
    let url = `/api/mapa-nave/${naveId}`;
    if (salidaId) {
        url += `?salida_id=${salidaId}`;
    }

    // Cargar datos del mapa
    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error('Error al cargar el mapa');
            return response.json();
        })
        .then(result => {
            if (!result.success) throw new Error(result.message || 'Error desconocido');
            inicializarMapa(result.data);
        })
        .catch(error => {
            console.error('Error cargando mapa:', error);
            mostrarError(error.message);
        });

    function mostrarError(mensaje) {
        document.getElementById(`${mapId}-loading`).classList.add('hidden');
        const errorDiv = document.getElementById(`${mapId}-error`);
        errorDiv.textContent = mensaje;
        errorDiv.classList.remove('hidden');
        document.getElementById(`${mapId}-container`).classList.remove('hidden');
    }

    function inicializarMapa(data) {
        const { ctx, localizacionesZonas, localizacionesMaquinas, paquetesConLocalizacion, dimensiones } = data;

        // Ocultar loading, mostrar container
        document.getElementById(`${mapId}-loading`).classList.add('hidden');
        document.getElementById(`${mapId}-container`).classList.remove('hidden');

        // Actualizar info
        document.getElementById(`${mapId}-nombre`).textContent = dimensiones.obra;
        document.getElementById(`${mapId}-dimensiones`).textContent =
            `${dimensiones.ancho}m x ${dimensiones.largo}m (${ctx.columnasReales} x ${ctx.filasReales} celdas)`;

        // Obtener elementos
        const escenario = document.getElementById(`${mapId}-escenario`);
        const grid = document.getElementById(`${mapId}-cuadricula`);

        const W = ctx.columnasReales;
        const H = ctx.filasReales;
        const viewCols = W;
        const viewRows = H;
        const isVertical = !ctx.estaGirado;

        let cellSize = 15;
        let zoomLevel = 1;

        // Transformar coordenadas reales a coordenadas de vista
        function realToViewRect(x1r, y1r, x2r, y2r) {
            const x1 = Math.min(x1r, x2r);
            const x2 = Math.max(x1r, x2r);
            const y1 = Math.min(y1r, y2r);
            const y2 = Math.max(y1r, y2r);

            function mapPointToView(x, y) {
                if (isVertical) {
                    // Modo vertical: invertir Y
                    return { x, y: (H - y + 1) };
                }
                // Modo horizontal: intercambiar X e Y
                return { x: y, y: x };
            }

            const p1 = mapPointToView(x1, y1);
            const p2 = mapPointToView(x2, y1);
            const p3 = mapPointToView(x1, y2);
            const p4 = mapPointToView(x2, y2);

            const xs = [p1.x, p2.x, p3.x, p4.x];
            const ys = [p1.y, p2.y, p3.y, p4.y];

            const minX = Math.min(...xs);
            const maxX = Math.max(...xs);
            const minY = Math.min(...ys);
            const maxY = Math.max(...ys);

            return {
                x: minX,
                y: minY,
                w: (maxX - minX + 1),
                h: (maxY - minY + 1),
            };
        }

        function renderExistentes() {
            grid.querySelectorAll('.loc-existente').forEach(el => {
                // No reposicionar paquetes que están en modo edición
                if (el.classList.contains('loc-paquete--editing')) {
                    // Solo actualizar el tamaño basado en celdas, no la posición
                    // Mantener left y top intactos
                    const currentLeft = parseFloat(el.style.left) || 0;
                    const currentTop = parseFloat(el.style.top) || 0;

                    // Recalcular width y height si es necesario
                    const rect = realToViewRect(
                        +el.dataset.x1, +el.dataset.y1,
                        +el.dataset.x2, +el.dataset.y2
                    );

                    // Mantener la posición actual pero ajustar dimensiones proporcionalmente
                    const oldCeldaPx = parseFloat(el.dataset.currentCeldaPx) || cellSize;
                    const scaleFactor = cellSize / oldCeldaPx;

                    el.style.left = (currentLeft * scaleFactor) + 'px';
                    el.style.top = (currentTop * scaleFactor) + 'px';
                    el.style.width = (parseFloat(el.style.width) * scaleFactor) + 'px';
                    el.style.height = (parseFloat(el.style.height) * scaleFactor) + 'px';

                    el.dataset.currentCeldaPx = cellSize;
                    return;
                }

                const rect = realToViewRect(
                    +el.dataset.x1, +el.dataset.y1,
                    +el.dataset.x2, +el.dataset.y2
                );
                
                const width = rect.w * cellSize;
                const height = rect.h * cellSize;

                el.style.left = ((rect.x - 1) * cellSize) + 'px';
                el.style.top = ((rect.y - 1) * cellSize) + 'px';
                el.style.width = width + 'px';
                el.style.height = height + 'px';
                el.dataset.currentCeldaPx = cellSize;
            });
        }

        function updateMap() {
            const containerWidth = escenario.clientWidth - 40;
            const containerHeight = 450;

            const maxCellW = Math.floor(containerWidth / viewCols);
            const maxCellH = Math.floor(containerHeight / viewRows);
            const baseCellSize = Math.max(4, Math.min(maxCellW, maxCellH, 12));

            cellSize = Math.max(4, baseCellSize * zoomLevel);

            const gridWidth = viewCols * cellSize;
            const gridHeight = viewRows * cellSize;

            grid.style.width = `${gridWidth}px`;
            grid.style.height = `${gridHeight}px`;
            grid.style.minWidth = `${gridWidth}px`;
            grid.style.minHeight = `${gridHeight}px`;
            grid.style.backgroundSize = `${cellSize}px ${cellSize}px`;
            grid.style.backgroundImage = `
                linear-gradient(to right, #e5e7eb 1px, transparent 1px),
                linear-gradient(to bottom, #e5e7eb 1px, transparent 1px)
            `;
            grid.style.backgroundPosition = '0 0, 0 0';

            renderExistentes();
        }

        function renderizarElementos() {
            // Limpiar grid
            grid.innerHTML = '';

            // Renderizar máquinas
            localizacionesMaquinas.forEach(loc => {
                const div = crearElemento(loc, 'loc-maquina', loc.nombre);
                grid.appendChild(div);
            });

            // Renderizar zonas
            localizacionesZonas.forEach(loc => {
                const tipo = loc.tipo.replace(/-/g, '_');
                const div = crearElemento(loc, `loc-zona tipo-${tipo}`, loc.nombre);
                grid.appendChild(div);
            });

            // Renderizar paquetes
            paquetesConLocalizacion.forEach(paq => {
                const tipo = paq.tipo_contenido || 'mixto';
                const div = crearElemento(paq, `loc-paquete tipo-${tipo}`, '');
                div.dataset.paqueteId = paq.id;
                div.dataset.codigo = paq.codigo;
                div.dataset.orientacion = paq.orientacion || 'I';
                grid.appendChild(div);
            });
        }

        function crearElemento(item, claseExtra, label) {
            const div = document.createElement('div');
            div.className = `loc-existente ${claseExtra}`;
            div.dataset.id = item.id;
            div.dataset.x1 = item.x1;
            div.dataset.y1 = item.y1;
            div.dataset.x2 = item.x2;
            div.dataset.y2 = item.y2;

            // Usar transformación de coordenadas
            const rect = realToViewRect(item.x1, item.y1, item.x2, item.y2);
            const left = (rect.x - 1) * cellSize;
            const top = (rect.y - 1) * cellSize;
            const width = rect.w * cellSize;
            const height = rect.h * cellSize;

            div.style.position = 'absolute';
            div.style.left = `${left}px`;
            div.style.top = `${top}px`;
            div.style.width = `${width}px`;
            div.style.height = `${height}px`;

            if (label) {
                const span = document.createElement('span');
                span.className = 'loc-label';
                span.textContent = label;
                div.appendChild(span);
            }

            return div;
        }

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

        // Pan/Drag
        let isPanning = false;
        let panStartX = 0, panStartY = 0;
        let panStartScrollLeft = 0, panStartScrollTop = 0;

        escenario.addEventListener('mousedown', (e) => {
            if (e.target.closest('.loc-existente') || e.target.closest('button')) return;
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
        let touchStartX = 0, touchStartY = 0;
        let touchStartScrollLeft = 0, touchStartScrollTop = 0;

        escenario.addEventListener('touchstart', (e) => {
            if (e.target.closest('.loc-existente') || e.target.closest('button')) return;
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

        // Inicializar
        renderizarElementos();
        updateMap();
        initPaqueteInteracciones();

        // Resize
        let resizePending = false;
        window.addEventListener('resize', () => {
            if (resizePending) return;
            resizePending = true;
            requestAnimationFrame(() => {
                updateMap();
                resizePending = false;
            });
        }, { passive: true });

        // ================================
        //  MODO EDICIÓN DE PAQUETES (Funciones y Estado)
        // ================================
        let paqueteEnEdicion = null;
        let dragState = null;

        function getCeldaPx() {
            const computed = getComputedStyle(grid);
            const width = parseFloat(computed.width);
            return width / viewCols;
        }

        function initPaqueteInteracciones() {
            if (!modoEdicion) return;
            
            // Usar delegación de eventos en el grid para manejar clicks en paquetes
            grid.addEventListener('click', function(ev) {
                const paquete = ev.target.closest('.loc-paquete');
                if (!paquete) return;

                // Si el click fue en la toolbar, no hacer nada (ya tiene sus propios listeners)
                if (ev.target.closest('.paquete-toolbar')) {
                    return;
                }

                // Si ya está en edición, no hacer nada (el usuario interactúa con la toolbar de edición)
                if (paquete.classList.contains('loc-paquete--editing')) {
                    return;
                }

                // Si ya tiene la toolbar de preview, no hacer nada
                const yaTieneToolbarPreview = paquete.querySelector('.paquete-toolbar--preview');
                if (yaTieneToolbarPreview) {
                    return;
                }

                console.log('Click en paquete:', paquete.dataset.paqueteId);
                crearToolbarPreview(paquete);
            });
        }

        function crearToolbarPreview(paquete) {
            // Si hay otro paquete en edición o con preview, cerrarlo
            if (paqueteEnEdicion && paqueteEnEdicion !== paquete) {
                salirDeEdicion(paqueteEnEdicion, true);
            }
            
            // Cerrar otros previews abiertos
            grid.querySelectorAll('.paquete-toolbar--preview').forEach(tb => {
                if (tb.parentElement !== paquete) {
                    tb.parentElement.classList.remove('has-toolbar');
                    tb.remove();
                }
            });

            const toolbar = document.createElement('div');
            toolbar.className = 'paquete-toolbar paquete-toolbar--preview';

            const btnEditar = document.createElement('button');
            btnEditar.type = 'button';
            btnEditar.title = 'Mover paquete';
            btnEditar.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M16.474 5.408l2.118 2.117m-.756-3.982L12.109 9.27a2.118 2.118 0 00-.58 1.082L11 13l2.648-.53c.41-.082.786-.283 1.082-.579l5.727-5.727a1.853 1.853 0 10-2.621-2.621z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            `;

            btnEditar.addEventListener('click', (ev) => {
                ev.stopPropagation();
                console.log('Entrando en edición paquete:', paquete.dataset.paqueteId);
                entrarEnEdicion(paquete);
            });

            toolbar.appendChild(btnEditar);
            paquete.appendChild(toolbar);
            paquete.classList.add('has-toolbar');
        }

        function entrarEnEdicion(paquete) {
            if (paqueteEnEdicion && paqueteEnEdicion !== paquete) {
                salirDeEdicion(paqueteEnEdicion, true);
            }

            paqueteEnEdicion = paquete;

            const previewToolbar = paquete.querySelector('.paquete-toolbar--preview');
            if (previewToolbar) {
                previewToolbar.remove();
            }

            paquete.classList.add('loc-paquete--editing');
            paquete.classList.add('has-toolbar'); // Asegurar que tenga la clase

            if (!paquete.dataset.origLeft) {
                paquete.dataset.origLeft = paquete.style.left || '0px';
                paquete.dataset.origTop = paquete.style.top || '0px';
                paquete.dataset.origWidth = paquete.style.width || '';
                paquete.dataset.origHeight = paquete.style.height || '';
            }

            const toolbar = document.createElement('div');
            toolbar.className = 'paquete-toolbar paquete-toolbar--edit';

            // Botón confirmar
            const btnConfirmar = document.createElement('button');
            btnConfirmar.type = 'button';
            btnConfirmar.title = 'Guardar nueva posición';
            btnConfirmar.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            `;

            btnConfirmar.addEventListener('click', async (ev) => {
                ev.stopPropagation();

                const paqueteId = paquete.dataset.paqueteId;
                if (!paqueteId) {
                    alert('No se encontró el ID del paquete');
                    return;
                }

                const celdaPx = getCeldaPx();
                const left = parseFloat(paquete.style.left) || 0;
                const top = parseFloat(paquete.style.top) || 0;
                const width = parseFloat(paquete.style.width) || celdaPx;
                const height = parseFloat(paquete.style.height) || celdaPx;

                const x1v = Math.round(left / celdaPx) + 1;
                const y1v = Math.round(top / celdaPx) + 1;
                const x2v = Math.round((left + width) / celdaPx);
                const y2v = Math.round((top + height) / celdaPx);

                // Convertir de vista a real (inverso de realToViewRect)
                function viewToReal(xv, yv) {
                    if (isVertical) {
                        return { x: xv, y: (H - yv + 1) };
                    }
                    return { x: yv, y: xv };
                }

                const p1 = viewToReal(x1v, y1v);
                const p2 = viewToReal(x2v, y2v);

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
                    const response = await fetch(`/localizaciones/paquete/${paqueteId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            x1: x1r,
                            y1: y1r,
                            x2: x2r,
                            y2: y2r
                        })
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'Error al guardar la posición');
                    }

                    paquete.dataset.x1 = x1r;
                    paquete.dataset.y1 = y1r;
                    paquete.dataset.x2 = x2r;
                    paquete.dataset.y2 = y2r;
                    paquete.dataset.origLeft = paquete.style.left;
                    paquete.dataset.origTop = paquete.style.top;

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Guardado!',
                            text: 'La posición del paquete se ha actualizado correctamente',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    } else {
                        alert('¡Guardado! La posición del paquete se guardó correctamente');
                    }
                    
                    salirDeEdicion(paquete, false);

                } catch (error) {
                    console.error('Error al guardar posición:', error);
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo guardar la posición: ' + error.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4000
                        });
                    } else {
                        alert('Error al guardar: ' + error.message);
                    }

                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
                    <path d="M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            `;

            btnCancelar.addEventListener('click', (ev) => {
                ev.stopPropagation();
                paquete.style.left = paquete.dataset.origLeft || '0px';
                paquete.style.top = paquete.dataset.origTop || '0px';
                paquete.style.width = paquete.dataset.origWidth || '';
                paquete.style.height = paquete.dataset.origHeight || '';
                salirDeEdicion(paquete, true);
            });

            // Botón rotar
            const btnRotar = document.createElement('button');
            btnRotar.type = 'button';
            btnRotar.title = 'Rotar paquete 90°';
            btnRotar.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            `;

            btnRotar.addEventListener('click', (ev) => {
                ev.stopPropagation();

                const currentWidth = paquete.style.width;
                const currentHeight = paquete.style.height;

                paquete.style.width = currentHeight;
                paquete.style.height = currentWidth;
            });

            toolbar.appendChild(btnConfirmar);
            toolbar.appendChild(btnCancelar);
            toolbar.appendChild(btnRotar);
            paquete.appendChild(toolbar);

            activarDragEnPaquete(paquete);
        }

        function salirDeEdicion(paquete, cancelar = false) {
            const toolbarEdit = paquete.querySelector('.paquete-toolbar--edit');
            if (toolbarEdit) toolbarEdit.remove();

            paquete.classList.remove('loc-paquete--editing');
            paquete.classList.remove('has-toolbar'); // Remover clase
            desactivarDragEnPaquete(paquete);

            const preview = paquete.querySelector('.paquete-toolbar--preview');
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

                dragState = { offsetX, offsetY, gridRect };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            };

            const onMouseMove = (ev) => {
                if (!dragState) return;

                // gridRect.left ya es relativo al viewport, así que ev.clientX - gridRect.left
                // nos da la posición X relativa al borde izquierdo del grid.
                // No hay que restar scrollLeft porque getBoundingClientRect ya tiene en cuenta el scroll.
                const xDentroGrid = ev.clientX - dragState.gridRect.left;
                const yDentroGrid = ev.clientY - dragState.gridRect.top;

                let nuevoLeft = xDentroGrid - dragState.offsetX;
                let nuevoTop = yDentroGrid - dragState.offsetY;

                nuevoLeft = Math.max(0, Math.min(nuevoLeft, grid.offsetWidth - paquete.offsetWidth));
                nuevoTop = Math.max(0, Math.min(nuevoTop, grid.offsetHeight - paquete.offsetHeight));

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
                paquete.removeEventListener('mousedown', paquete._onMouseDownEditar);
                delete paquete._onMouseDownEditar;
            }
            if (paquete._onMouseMoveEditar) {
                document.removeEventListener('mousemove', paquete._onMouseMoveEditar);
                delete paquete._onMouseMoveEditar;
            }
            if (paquete._onMouseUpEditar) {
                document.removeEventListener('mouseup', paquete._onMouseUpEditar);
                delete paquete._onMouseUpEditar;
            }
            dragState = null;
        }
    }

    // Exponer función para recargar el mapa con diferentes parámetros
    const mapaContainer = document.querySelector(`[data-mapa-simple="${mapId}"]`);
    if (mapaContainer) {
        mapaContainer.recargarMapa = function(nuevoSalidaId = null) {
            let url = `/api/mapa-nave/${naveId}`;
            if (nuevoSalidaId) {
                url += `?salida_id=${nuevoSalidaId}`;
            }

            document.getElementById(`${mapId}-container`).classList.add('hidden');
            document.getElementById(`${mapId}-loading`).classList.remove('hidden');

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Error al cargar el mapa');
                    return response.json();
                })
                .then(result => {
                    if (!result.success) throw new Error(result.message || 'Error desconocido');
                    inicializarMapa(result.data);
                })
                .catch(error => {
                    console.error('Error recargando mapa:', error);
                    mostrarError(error.message);
                });
        };
    }
})();
</script>

{{-- Estilos para el modo edición --}}
@if($modoEdicion)
<style>
    /* Permitir que la toolbar se vea fuera del paquete */
    .loc-paquete {
        overflow: visible !important;
    }
    
    .loc-paquete.has-toolbar {
        z-index: 90;
    }

    /* Toolbar flotante debajo del paquete */
    .paquete-toolbar {
        position: absolute;
        top: calc(100% + 5px);
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
        /* Asegurar dimensiones mínimas */
        min-width: max-content;
        min-height: 24px;
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
        flex-shrink: 0; /* Evitar que se aplasten */
    }

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
        display: block; /* Asegurar renderizado */
    }

    @media (max-width: 768px) {
        .paquete-toolbar button svg {
            width: 12px;
            height: 12px;
        }
    }

    /* Toolbar preview (botón de lápiz) */
    .paquete-toolbar--preview {
        position: absolute;
        top: calc(100% + 5px);
        left: 50%;
        transform: translateX(-50%);
        background: rgba(15, 23, 42, 0.85);
        padding: 0.35rem 0.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
    }

    .paquete-toolbar--preview button {
        background: rgba(251, 146, 60, 0.95) !important;
        border-color: rgba(251, 146, 60, 0.3) !important;
    }

    .paquete-toolbar--preview button:hover {
        background: rgba(251, 146, 60, 1) !important;
        border-color: rgba(251, 146, 60, 0.5) !important;
    }

    /* Botones con colores específicos para modo edición */
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
        z-index: 100; /* Elevado para que esté por encima de otros */
    }

    /* Paquete al hacer hover (no en modo edición) */
    .loc-paquete:not(.loc-paquete--editing):hover {
        cursor: pointer;
        opacity: 0.9;
    }

    /* Animación de spinner */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }
</style>
@endif
