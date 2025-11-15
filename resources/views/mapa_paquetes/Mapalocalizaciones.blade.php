<x-app-layout>
    {{-- Título de la pestaña / header --}}
    <x-slot name="title">Mapa de Localizaciones -
        {{ config('app.name') }}</x-slot>

    {{-- Menú lateral/top --}}
    <x-menu.planillas />

    <div class="w-full p-4 sm:p-6">
        {{-- === Cabecera de la página === --}}
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Mapa de
                        Localizaciones</h1>
                    <p class="text-gray-600 mt-1">
                        Obra: <span
                            class="font-semibold">{{ $dimensiones['obra'] ?? 'Sin obra' }}</span>
                        | Dimensiones:
                        <span class="font-semibold">{{ $dimensiones['ancho'] }}m
                            × {{ $dimensiones['largo'] }}m</span>
                        | Cliente: <span
                            class="font-semibold">{{ $cliente->empresa ?? 'Sin cliente' }}</span>
                    </p>
                </div>

                {{-- Selector de obra --}}
                <div class="flex items-center gap-3">
                    <label for="obra-select"
                        class="text-sm font-medium text-gray-700">Obra:</label>
                    <select id="obra-select"
                        class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        onchange="window.location.href = '{{ route('mapa.paquetes') }}?obra=' + this.value">
                        @foreach ($obras as $obra)
                            <option value="{{ $obra->id }}"
                                {{ $obra->id == $obraActualId ? 'selected' : '' }}>
                                {{ $obra->obra }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- === GRID principal: Mapa + Panel lateral === --}}
        <div class="flex gap-4 w-full" style="height: calc(100vh - 200px); max-height: calc(100vh - 200px);">

            {{-- COMPONENTE DE MAPA (nuevo) --}}
            <div class="flex-1 overflow-hidden">
                <x-mapa-component :ctx="$ctx" :localizaciones-zonas="$localizacionesZonas"
                    :localizaciones-maquinas="$localizacionesMaquinas" :paquetes-con-localizacion="$paquetesConLocalizacion" :dimensiones="$dimensiones"
                    :obra-actual-id="$obraActualId" :show-controls="false" :mostrarObra="false"
                    :show-scan-result="false" :ruta-paquete="route('paquetes.tamaño')" :ruta-guardar="route('localizaciones.storePaquete')"
                    :enable-drag-paquetes="true"
                    height="100%" class='w-full h-full border-2 rounded-lg' />
            </div>

            {{-- PANEL LATERAL: Lista de paquetes (igual que lo tenías) --}}
            <div
                class="bg-white rounded-lg shadow-sm overflow-hidden flex flex-col w-full max-w-xl flex-shrink-0">
                <div
                    class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                    <h2 class="text-lg font-bold">Paquetes Ubicados</h2>
                    <p class="text-sm text-blue-100 mt-1">
                        Total: {{ $paquetesConLocalizacion->count() }} paquetes
                    </p>
                </div>

                <div class="p-3 border-b border-gray-200 space-y-2">
                    <input type="text" id="search-paquetes"
                        placeholder="Buscar por código..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />

                    <select id="filter-obra-paquetes"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                        <option value="">Todas las obras</option>
                    </select>

                </div>

                <div class="flex-1 overflow-y-auto p-3" id="lista-paquetes">
                    @forelse($paquetesConLocalizacion as $paquete)
                        <div class="paquete-item bg-gray-50 rounded-lg p-3 mb-2 border border-gray-200 hover:border-blue-400 hover:shadow-md transition cursor-pointer"
                            data-paquete-id="{{ $paquete['id'] }}"
                            data-obra="{{ $paquete['obra'] }}"
                            data-x1="{{ $paquete['x1'] }}"
                            data-y1="{{ $paquete['y1'] }}"
                            data-x2="{{ $paquete['x2'] }}"
                            data-y2="{{ $paquete['y2'] }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-gray-800 text-sm">📦
                                    {{ $paquete['codigo'] }}</span>
                                <span
                                    class="text-xs text-gray-500">{{ $paquete['cantidad_elementos'] }}
                                    elementos</span>
                            </div>
                            <div
                                class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                <div><span class="text-gray-500">Peso:</span>
                                    <span
                                        class="font-semibold">{{ number_format($paquete['peso'], 2) }}
                                        kg</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-500">Obra:</span>
                                    <span class="font-semibold">{{ $paquete['obra'] }}</span>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                @if ($paquete['tipo_contenido'] === 'barras')
                                    <span
                                        class="inline-block w-3 h-3 bg-blue-500 rounded-full"></span><span
                                        class="text-xs text-gray-600">Barras</span>
                                @elseif($paquete['tipo_contenido'] === 'estribos')
                                    <span
                                        class="inline-block w-3 h-3 bg-green-500 rounded-full"></span><span
                                        class="text-xs text-gray-600">Estribos</span>
                                @else
                                    <span
                                        class="inline-block w-3 h-3 bg-orange-500 rounded-full"></span><span
                                        class="text-xs text-gray-600">Mixto</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-16 h-16 mx-auto mb-3 text-gray-300"
                                fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round" stroke-width="2"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="font-medium">No hay paquetes ubicados</p>
                            <p class="text-sm mt-1">Los paquetes con
                                localización aparecerán aquí</p>
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-gray-200 p-3 bg-gray-50">
                    <h3 class="text-xs font-bold text-gray-700 mb-2">LEYENDA
                    </h3>
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

    {{-- Context Menu para paquetes --}}
    <div id="context-menu-paquete" class="hidden fixed bg-white border border-gray-300 rounded-lg shadow-lg py-1" style="min-width: 150px; z-index: 9999;">
        <button id="context-menu-ver-elementos" class="w-full px-4 py-2 text-left hover:bg-blue-50 text-sm text-gray-700 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            Ver elementos
        </button>
    </div>

    {{-- Modal para mostrar elementos del paquete --}}
    <div id="modal-elementos-paquete" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4" style="z-index: 99999;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header del modal -->
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800">Elementos del Paquete <span id="modal-paquete-codigo"></span></h2>
                <button id="cerrar-modal-elementos" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Contenido del modal -->
            <div id="modal-elementos-contenido" class="flex-1 overflow-y-auto p-6">
                <div class="text-center text-gray-500">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                    <p class="mt-2">Cargando elementos...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Script para dibujar figuras SVG de elementos --}}
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>

    {{-- Script para integrar el panel lateral con el componente del mapa --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Obtenemos el canvas del mapa (el div con data-mapa-canvas)
                const canvas = document.querySelector('[data-mapa-canvas]');
                // La instancia JS del mapa la expone el componente en canvas.mapaInstance
                const mapaInstance = canvas?.mapaInstance;

                if (!mapaInstance) {
                    console.warn('No se encontró la instancia del mapa');
                    return;
                }

                // ==========================
                // ESTADO INICIAL DEL MAPA
                // ==========================
                // Al principio queremos que no aparezca ningún paquete dibujado en el mapa.
                // Sólo se verán los que el usuario vaya "activando" desde la lista lateral.
                document.querySelectorAll('.paquete-item').forEach(item => {
                    const id = parseInt(item.dataset.paqueteId);

                    if (!Number.isNaN(id) && typeof mapaInstance
                        .hidePaquete === 'function') {
                        mapaInstance.hidePaquete(id);
                    }
                });

                // =========================================
                // GESTIÓN DE CLIC/HOVER EN LOS PAQUETES
                // =========================================
                document.querySelectorAll('.paquete-item').forEach(item => {

                    // CLIC: alterna el paquete como activo/inactivo
                    item.addEventListener('click', function() {
                        const id = parseInt(this.dataset
                            .paqueteId);
                        if (Number.isNaN(id)) return;

                        // ¿Este paquete ya estaba seleccionado en el menú?
                        const isSelected = this.classList
                            .contains('border-blue-500');

                        if (isSelected) {
                            // Caso 1: Ya estaba seleccionado → ahora se desactiva

                            // 1) Quitar estilos de selección en el menú
                            this.classList.remove(
                                'border-blue-500',
                                'bg-blue-50');

                            // 2) Ocultar el paquete del mapa
                            if (typeof mapaInstance
                                .hidePaquete === 'function') {
                                mapaInstance.hidePaquete(id);
                            }

                            // 3) Si estaba resaltado, limpiamos el highlight
                            if (typeof mapaInstance
                                .clearHighlight === 'function'
                            ) {
                                mapaInstance.clearHighlight();
                            }
                        } else {
                            // Caso 2: No estaba seleccionado → ahora se activa

                            // 1) Marcar visualmente el paquete como seleccionado en la lista
                            this.classList.add(
                                'border-blue-500',
                                'bg-blue-50');

                            // 2) Mostrar el paquete en el mapa
                            if (typeof mapaInstance
                                .showPaquete === 'function') {
                                mapaInstance.showPaquete(id);
                            }

                            // 3) Resaltar y centrar la "cámara" en su posición real
                            const x1 = parseInt(this.dataset
                                .x1);
                            const y1 = parseInt(this.dataset
                                .y1);

                            if (typeof mapaInstance
                                .setHighlight === 'function') {
                                mapaInstance.setHighlight(id);
                            }

                            if (!Number.isNaN(x1) && !Number
                                .isNaN(y1) &&
                                typeof mapaInstance
                                .focusPaquete === 'function') {
                                mapaInstance.focusPaquete(x1,
                                    y1);
                            }
                        }
                    });

                    // HOVER: sólo cambiamos el highlight si el paquete está activo en el mapa
                    item.addEventListener('mouseenter', function() {
                        const id = parseInt(this.dataset
                            .paqueteId);
                        if (Number.isNaN(id)) return;

                        // Si el paquete está seleccionado en el menú, reforzamos su highlight al pasar el ratón
                        if (this.classList.contains(
                                'border-blue-500') &&
                            typeof mapaInstance.setHighlight ===
                            'function') {

                            mapaInstance.setHighlight(id);
                        }
                    });

                    item.addEventListener('mouseleave', function() {
                        // Si NO está seleccionado de forma permanente, limpiamos el highlight
                        if (!this.classList.contains(
                                'border-blue-500') &&
                            typeof mapaInstance
                            .clearHighlight === 'function') {

                            mapaInstance.clearHighlight();
                        }
                    });
                });

                // =====================
                // FILTRO DE OBRAS
                // =====================
                const filterObra = document.getElementById('filter-obra');
                if (filterObra) {
                    filterObra.addEventListener('change', (e) => {
                        const obraId = e.target.value;
                        const url = new URL(window.location.href);
                        if (obraId) {
                            url.searchParams.set('obra', obraId);
                        } else {
                            url.searchParams.delete('obra');
                        }
                        window.location.href = url.toString();
                    });
                }

                // =====================
                // BUSCADOR DE PAQUETES
                // =====================
                const searchInput = document.getElementById('search-paquetes');

                if (searchInput) {
                    searchInput.addEventListener('input', (e) => {
                        const query = e.target.value.toLowerCase();

                        document.querySelectorAll('.paquete-item')
                            .forEach(item => {
                                const text = item.textContent
                                    .toLowerCase();
                                // Mostramos/ocultamos el item de la lista según coincida con la búsqueda
                                item.style.display = text.includes(
                                    query) ? 'block' : 'none';
                            });
                    });
                }

                // =====================
                // FILTRO DE OBRAS PAQUETES (JS - sin recargar página)
                // =====================
                const filterObraPaquetes = document.getElementById('filter-obra-paquetes');

                if (filterObraPaquetes) {
                    // Poblar el select con obras únicas de los paquetes
                    const paqueteItems = document.querySelectorAll('.paquete-item');
                    const obrasUnicas = new Set();

                    paqueteItems.forEach(item => {
                        const obra = item.dataset.obra;
                        if (obra && obra !== '-') {
                            obrasUnicas.add(obra);
                        }
                    });

                    // Ordenar obras alfabéticamente y agregar opciones al select
                    Array.from(obrasUnicas).sort().forEach(obra => {
                        const option = document.createElement('option');
                        option.value = obra;
                        option.textContent = obra;
                        filterObraPaquetes.appendChild(option);
                    });

                    // Listener para filtrar paquetes por obra
                    filterObraPaquetes.addEventListener('change', (e) => {
                        const obraSeleccionada = e.target.value;

                        paqueteItems.forEach(item => {
                            const obraItem = item.dataset.obra;

                            if (obraSeleccionada === '') {
                                // Mostrar todos
                                item.style.display = 'block';
                            } else {
                                // Mostrar solo los que coinciden
                                item.style.display = (obraItem === obraSeleccionada) ? 'block' : 'none';
                            }
                        });
                    });
                }

                // =====================
                // CONTEXT MENU Y MODAL DE ELEMENTOS
                // =====================
                const contextMenu = document.getElementById('context-menu-paquete');
                const modal = document.getElementById('modal-elementos-paquete');
                const modalContenido = document.getElementById('modal-elementos-contenido');
                const modalCodigo = document.getElementById('modal-paquete-codigo');
                const btnCerrarModal = document.getElementById('cerrar-modal-elementos');
                const btnVerElementos = document.getElementById('context-menu-ver-elementos');
                let paqueteIdActual = null;

                console.log('Context menu element:', contextMenu);
                console.log('Paquetes encontrados:', document.querySelectorAll('.paquete-item').length);

                // Agregar context menu a los paquetes de la lista
                document.querySelectorAll('.paquete-item').forEach(item => {
                    item.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        e.stopPropagation();

                        paqueteIdActual = item.dataset.paqueteId;
                        console.log('Context menu activado para paquete:', paqueteIdActual);

                        // Posicionar context menu (usar clientX/clientY en lugar de pageX/pageY)
                        contextMenu.style.left = e.clientX + 'px';
                        contextMenu.style.top = e.clientY + 'px';
                        contextMenu.classList.remove('hidden');
                    });
                });

                // Ocultar context menu al hacer click fuera
                document.addEventListener('click', (e) => {
                    if (!contextMenu.contains(e.target)) {
                        contextMenu.classList.add('hidden');
                    }
                });

                // Botón "Ver elementos" del context menu
                btnVerElementos.addEventListener('click', () => {
                    contextMenu.classList.add('hidden');
                    if (paqueteIdActual) {
                        abrirModalElementos(paqueteIdActual);
                    }
                });

                // Abrir modal y cargar elementos
                async function abrirModalElementos(paqueteId) {
                    modal.classList.remove('hidden');
                    modalContenido.innerHTML = '<div class="text-center text-gray-500"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div><p class="mt-2">Cargando elementos...</p></div>';

                    try {
                        const response = await fetch(`/paquetes/${paqueteId}/elementos`);
                        const data = await response.json();

                        if (data.success) {
                            modalCodigo.textContent = data.paquete.codigo;
                            renderizarElementos(data.elementos);
                        } else {
                            modalContenido.innerHTML = '<div class="text-center text-red-500"><p>Error al cargar elementos: ' + (data.message || 'Error desconocido') + '</p></div>';
                        }
                    } catch (error) {
                        console.error('Error al cargar elementos:', error);
                        modalContenido.innerHTML = '<div class="text-center text-red-500"><p>Error al cargar elementos del paquete</p></div>';
                    }
                }

                // Renderizar elementos en grid (4 por fila)
                function renderizarElementos(elementos) {
                    if (!elementos || elementos.length === 0) {
                        modalContenido.innerHTML = '<div class="text-center text-gray-500"><p>Este paquete no tiene elementos</p></div>';
                        return;
                    }

                    modalContenido.innerHTML = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" id="grid-elementos"></div>';
                    const grid = document.getElementById('grid-elementos');

                    elementos.forEach((elemento, index) => {
                        const elementoDiv = document.createElement('div');
                        elementoDiv.className = 'border border-gray-300 rounded-lg p-3 bg-white shadow-sm';
                        elementoDiv.innerHTML = `
                            <h3 class="text-sm font-semibold text-gray-700 mb-2 truncate" title="${elemento.codigo}">${elemento.codigo}</h3>
                            <div id="svg-elemento-${elemento.id}" class="w-full" style="height: 150px; border: 1px solid #e5e7eb; border-radius: 4px; background: white;"></div>
                        `;
                        grid.appendChild(elementoDiv);

                        // Dibujar el elemento usando la función del script figuraElemento.js
                        // Esperar a que el elemento esté en el DOM
                        setTimeout(() => {
                            if (typeof window.dibujarFiguraElemento === 'function') {
                                window.dibujarFiguraElemento(
                                    'svg-elemento-' + elemento.id,
                                    elemento.dimensiones,
                                    elemento.peso_kg,
                                    elemento.diametro,
                                    elemento.barras
                                );
                            }
                        }, 50 * index); // Pequeño delay escalonado para mejor rendimiento
                    });
                }

                // Cerrar modal
                btnCerrarModal.addEventListener('click', () => {
                    modal.classList.add('hidden');
                });

                // Cerrar modal al hacer click fuera
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.classList.add('hidden');
                    }
                });
            });
        </script>
    @endpush

    <style>
        /* Label del paquete - YA NO SE USA (quitado por petición del usuario) */

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

        /* Botones de Zoom - Fixed al contenedor del mapa */
        #escenario-cuadricula {
            position: relative;
        }

        .zoom-controls {
            position: fixed;
            display: flex;
            flex-direction: row;
            gap: 0;
            z-index: 50;
        }

        .zoom-btn {
            width: 18px;
            height: 18px;
            border: 1px solid rgba(156, 163, 175, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(75, 85, 99, 0.9);
            cursor: pointer;
            padding: 0;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .zoom-btn:hover {
            background: rgba(107, 114, 128, 0.95);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        .zoom-btn:active {
            transform: scale(0.95);
        }

        .zoom-btn svg {
            width: 11px;
            height: 11px;
            stroke: #fff;
        }

        /* Botón izquierdo (zoom in) - Redondeado a la izquierda */
        .zoom-btn-in {
            border-radius: 4px 0 0 4px;
            border-right: none;
        }

        /* Botón derecho (zoom out) - Redondeado a la derecha */
        .zoom-btn-out {
            border-radius: 0 4px 4px 0;
        }

        /* Responsive para móvil */
        @media (max-width: 768px) {
            .zoom-controls {
                top: 0.5rem;
                left: 0.5rem;
            }

            .zoom-btn {
                width: 22px;
                height: 22px;
            }

            .zoom-btn svg {
                width: 13px;
                height: 13px;
            }
        }
    </style>


</x-app-layout>
