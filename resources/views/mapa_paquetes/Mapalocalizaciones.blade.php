<x-app-layout>
    {{-- Título de la pestaña / header --}}
    <x-slot name="title">Mapa de Localizaciones -
        {{ config('app.name') }}</x-slot>

    <div class="w-full p-4 flex flex-col gap-4">
        {{-- === Cabecera de la página === --}}
        <div class="bg-white rounded-lg shadow-sm p-4 ">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Mapa de
                        Localizaciones</h1>
                    <p class="text-gray-600 mt-1">
                        Obra: <span class="font-semibold">{{ $dimensiones['obra'] ?? 'Sin obra' }}</span>
                        | Dimensiones:
                        <span class="font-semibold">{{ $dimensiones['ancho'] }}m
                            × {{ $dimensiones['largo'] }}m</span>
                        | Cliente: <span class="font-semibold">{{ $cliente->empresa ?? 'Sin cliente' }}</span>
                    </p>
                </div>

                {{-- Selector de obra --}}
                <div class="flex items-center gap-3">
                    <label for="obra-select" class="text-sm font-medium text-gray-700">Obra:</label>
                    <select id="obra-select"
                        class=" rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        onchange="window.location.href = '{{ route('mapa.paquetes') }}?obra=' + this.value">
                        @foreach ($obras as $obra)
                            <option value="{{ $obra->id }}" {{ $obra->id == $obraActualId ? 'selected' : '' }}>
                                {{ $obra->obra }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- === GRID principal: Mapa + Panel lateral === --}}
        <div class="flex gap-4 w-full" style="height: calc(100vh - 180px);">

            {{-- COMPONENTE DE MAPA --}}
            {{-- 
                aspect-ratio: define la forma basada en las dimensiones reales.
                max-width: 65%: limita el ancho máximo (aprox 5/8 de pantalla).
                min-width: 350px: asegura un tamaño mínimo legible.
                flex: 0 0 auto: respeta el ancho calculado (o sus límites) sin encogerse ni crecer arbitrariamente.
            --}}
            <div class="relative overflow-hidden rounded-md shadow-sm border border-gray-200"
                style="height: 100%; aspect-ratio: {{ $dimensiones['ancho'] }} / {{ $dimensiones['largo'] }}; max-width: 65%; min-width: 350px; flex: 0 0 auto;">
                <x-mapa-simple :nave-id="$obraActualId" :modo-edicion="true" class="w-full h-full absolute inset-0" />
            </div>

            {{-- PANEL LATERAL: Lista de paquetes --}}
            <div class="flex-1 bg-white rounded-lg shadow-sm overflow-hidden flex flex-col min-w-[350px]">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                    <h2 class="text-lg font-bold">Paquetes Ubicados</h2>
                    <p class="text-sm text-blue-100 mt-1">
                        Total: {{ $paquetesConLocalizacion->count() }} paquetes
                    </p>
                </div>

                <div class="p-3 border-b border-gray-200 space-y-2">
                    <input type="text" id="search-paquetes" placeholder="Buscar por código..."
                        class="w-full px-3 py-2  rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />

                    <select id="filter-obra-paquetes"
                        class="w-full px-3 py-2  rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                        <option value="">Todas las obras</option>
                    </select>

                </div>

                <div class="flex-1 overflow-y-auto p-3 grid gap-2 content-start" id="lista-paquetes"
                    style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                    @forelse($paquetesConLocalizacion as $paquete)
                        <div class="paquete-item bg-gray-50 rounded-lg p-3 border border-gray-200 hover:border-blue-400 hover:shadow-md transition cursor-pointer h-full"
                            data-paquete-id="{{ $paquete['id'] }}" data-codigo="{{ $paquete['codigo'] }}"
                            data-obra="{{ $paquete['obra'] }}" data-x1="{{ $paquete['x1'] }}"
                            data-y1="{{ $paquete['y1'] }}" data-x2="{{ $paquete['x2'] }}"
                            data-y2="{{ $paquete['y2'] }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-gray-800 text-sm">📦
                                    {{ $paquete['codigo'] }}</span>
                                <span class="text-xs text-gray-500">{{ $paquete['cantidad_etiquetas'] }}
                                    etiquetas</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                <div><span class="text-gray-500">Peso:</span>
                                    <span class="font-semibold">{{ number_format($paquete['peso'], 2) }}
                                        kg</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-500">Obra:</span>
                                    <span class="font-semibold">{{ $paquete['obra'] }}</span>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                @if ($paquete['tipo_contenido'] === 'barras')
                                    <span class="inline-block w-3 h-3 bg-blue-500 rounded-full"></span><span
                                        class="text-xs text-gray-600">Barras</span>
                                @elseif($paquete['tipo_contenido'] === 'estribos')
                                    <span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span><span
                                        class="text-xs text-gray-600">Estribos</span>
                                @else
                                    <span class="inline-block w-3 h-3 bg-orange-500 rounded-full"></span><span
                                        class="text-xs text-gray-600">Mixto</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-8 text-gray-500">
                            <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
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
                    <div class="grid grid-cols-2 gap-2 text-xs">
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

    {{-- Panel flotante para detalle de etiquetas/elementos del paquete (se muestra al hacer click derecho) --}}
    <div id="detalle-paquete-panel"
        class="hidden fixed z-50 bg-white shadow-2xl border border-gray-200 rounded-lg w-full max-w-lg max-h-[400px] overflow-hidden flex-col"
        style="top: 1.5rem; right: 1.5rem;">
        <div class="flex items-start justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Detalle del paquete</p>
                <p id="detalle-paquete-codigo" class="text-sm font-bold text-gray-800 mt-0.5">Selecciona un paquete</p>
            </div>
            <button id="detalle-paquete-cerrar" type="button"
                class="text-gray-500 hover:text-gray-700 rounded-full p-1 transition" aria-label="Cerrar detalle">
                ✕
            </button>
        </div>
        <div id="detalle-paquete-contenido" class="p-3 text-sm text-gray-700 space-y-3 overflow-y-auto flex-1">
            <p class="text-gray-500">Haz click derecho sobre un paquete para ver sus etiquetas y elementos.</p>
        </div>
    </div>



    <script>
        // Definir función global para evitar rediclaraciones anónimas y permitir limpieza
        window.initMapalocalizacionesPage = function() {
            // Protección contra doble inicialización
            if (document.body.dataset.mapalocalizacionesPageInit === 'true') return;

            // Variables de estado local a la ejecución de la página
            let paqueteSeleccionadoCodigo = null;
            let cacheDetalles = new Map();
            let ultimoPosDetalle = null;

            const listaPaquetes = document.getElementById('lista-paquetes');
            // Si no estamos en la página correcta, salir
            if (!listaPaquetes) return;

            const paquetesItems = listaPaquetes.querySelectorAll('.paquete-item');
            const searchInput = document.getElementById('search-paquetes');
            const filterObra = document.getElementById('filter-obra-paquetes');
            const detallePanel = document.getElementById('detalle-paquete-panel');
            const detalleContenido = document.getElementById('detalle-paquete-contenido');
            const detalleCodigo = document.getElementById('detalle-paquete-codigo');
            const cerrarDetalleBtn = document.getElementById('detalle-paquete-cerrar');

            // === Utilidades para renderizar dimensiones como SVG (longitud + ángulo) ===
            function parseDimensiones(dims) {
                if (!dims) return null;
                const tokens = dims.toString().trim().split(/\s+/).filter(Boolean);
                const longitudes = [];
                const angulos = [];
                tokens.forEach(tok => {
                    if (tok.toLowerCase().includes('d')) {
                        const ang = parseFloat(tok.replace(/[^\d.-]/g, ''));
                        if (!Number.isNaN(ang)) angulos.push(ang);
                    } else {
                        const lon = parseFloat(tok.replace(/[^\d.-]/g, ''));
                        if (!Number.isNaN(lon)) longitudes.push(lon);
                    }
                });
                if (!longitudes.length) return null;
                return {
                    longitudes,
                    angulos
                };
            }

            function buildPuntosFigura(longitudes, angulos) {
                const puntos = [{
                    x: 0,
                    y: 0
                }];
                let anguloActual = 0;
                let x = 0,
                    y = 0;
                longitudes.forEach((L, idx) => {
                    const rad = anguloActual * Math.PI / 180;
                    x += L * Math.cos(rad);
                    y += L * Math.sin(rad);
                    puntos.push({
                        x,
                        y
                    });
                    anguloActual += angulos[idx] ?? 0;
                });
                return puntos;
            }

            function renderDimensionesSvg(dimensiones) {
                const parsed = parseDimensiones(dimensiones);
                if (!parsed) return null;

                // Rotar -90° para que el primer tramo se dibuje hacia arriba
                const puntos = buildPuntosFigura(parsed.longitudes, parsed.angulos)
                    .map(p => ({
                        x: p.y,
                        y: -p.x
                    }));

                let minX = 0,
                    maxX = 0,
                    minY = 0,
                    maxY = 0;
                puntos.forEach(p => {
                    minX = Math.min(minX, p.x);
                    maxX = Math.max(maxX, p.x);
                    minY = Math.min(minY, p.y);
                    maxY = Math.max(maxY, p.y);
                });

                const ancho = maxX - minX || 1;
                const alto = maxY - minY || 1;
                const viewW = 140,
                    viewH = 80;
                const padding = 8;
                const scale = Math.min(
                    (viewW - padding * 2) / ancho,
                    (viewH - padding * 2) / alto
                );

                // Iniciar siempre hacia la derecha, sin rotaciones auto
                const offsetX = padding + (-minX) * scale;
                const offsetY = padding + (-minY) * scale;

                const polyPoints = puntos
                    .map(p => `${(p.x * scale + offsetX).toFixed(2)},${(p.y * scale + offsetY).toFixed(2)}`)
                    .join(' ');

                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('viewBox', `0 0 ${viewW} ${viewH}`);
                svg.setAttribute('class', 'w-full h-16');

                const polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
                polyline.setAttribute('points', polyPoints);
                polyline.setAttribute('fill', 'none');
                polyline.setAttribute('stroke', '#2563eb');
                polyline.setAttribute('stroke-width', '3');
                polyline.setAttribute('stroke-linecap', 'round');
                polyline.setAttribute('stroke-linejoin', 'round');

                svg.appendChild(polyline);
                return svg;
            }

            // === 1. Lógica de Filtrado ===

            // Poblar select
            const obrasUnicas = new Set();
            paquetesItems.forEach(item => {
                const obra = item.dataset.obra;
                if (obra) obrasUnicas.add(obra);
            });

            while (filterObra.options.length > 1) {
                filterObra.remove(1);
            }

            obrasUnicas.forEach(obra => {
                const option = document.createElement('option');
                option.value = obra;
                option.textContent = obra;
                filterObra.appendChild(option);
            });

            function filtrarPaquetes() {
                const textoBusqueda = searchInput.value.toLowerCase().trim();
                const obraSeleccionada = filterObra.value;

                paquetesItems.forEach(item => {
                    const codigoRaw = item.querySelector('span.font-bold').textContent;
                    const codigo = codigoRaw.replace('📦', '').trim().toLowerCase();
                    const obra = item.dataset.obra;

                    const coincideTexto = codigo.includes(textoBusqueda);
                    const coincideObra = obraSeleccionada === '' || obra === obraSeleccionada;

                    if (coincideTexto && coincideObra) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }

            const handleInput = () => filtrarPaquetes();
            const handleChange = () => filtrarPaquetes();

            if (searchInput) searchInput.addEventListener('input', handleInput);
            if (filterObra) filterObra.addEventListener('change', handleChange);


            // === 2. Lógica de Interacción con el Mapa ===

            function getMapaContainer() {
                return document.querySelector('[data-mapa-simple]');
            }

            // Handlers para listeners
            const handleClickItem = function() {
                const item = this;
                const mapaContainer = getMapaContainer();
                if (!mapaContainer || !mapaContainer.mostrarPaquete) {
                    console.warn('El componente de mapa no está listo o no expone las funciones necesarias.');
                    return;
                }

                const codigoSpan = item.querySelector('span.font-bold');
                const codigo = codigoSpan.textContent.replace('📦', '').trim();

                if (paqueteSeleccionadoCodigo === codigo) return;

                paquetesItems.forEach(p => {
                    p.classList.remove('bg-blue-100', 'border-blue-500', 'ring-2', 'ring-blue-200');
                    p.classList.add('bg-gray-50', 'border-gray-200');
                });

                if (paqueteSeleccionadoCodigo) {
                    mapaContainer.ocultarPaquete(paqueteSeleccionadoCodigo);
                }

                item.classList.remove('bg-gray-50', 'border-gray-200');
                item.classList.add('bg-blue-100', 'border-blue-500', 'ring-2', 'ring-blue-200');

                mapaContainer.mostrarPaquete(codigo);
                paqueteSeleccionadoCodigo = codigo;
            };

            const handleContextMenu = function(ev) {
                ev.preventDefault();
                const item = this;
                const paqueteId = item.dataset.paqueteId;
                const codigo = item.dataset.codigo || item.querySelector('span.font-bold')?.textContent?.replace(
                    '📦', '').trim();
                if (!paqueteId) return;
                mostrarDetallePaquete(paqueteId, codigo, {
                    x: ev.clientX,
                    y: ev.clientY
                });
            };

            const handleDocContextMenu = function(ev) {
                const paqueteMapa = ev.target.closest('.loc-paquete');
                if (!paqueteMapa) return;
                ev.preventDefault();
                const paqueteId = paqueteMapa.dataset.paqueteId;
                const codigo = paqueteMapa.dataset.codigo;
                if (!paqueteId) return;
                mostrarDetallePaquete(paqueteId, codigo, {
                    x: ev.clientX,
                    y: ev.clientY
                });
            };

            paquetesItems.forEach(item => {
                item.addEventListener('click', handleClickItem);
                item.addEventListener('contextmenu', handleContextMenu);
            });

            document.addEventListener('contextmenu', handleDocContextMenu);

            // Panel de detalle
            function cerrarDetalle() {
                detallePanel.classList.add('hidden');
                detallePanel.classList.remove('flex'); // Remove flex when hidden
                detalleContenido.innerHTML =
                    '<p class="text-gray-500 text-sm">Haz click derecho sobre un paquete para ver sus etiquetas y elementos.</p>';
            }

            function posicionarPanel(posicion) {
                if (!posicion) return;
                const padding = 12;
                const panelWidth = detallePanel.offsetWidth || 360;
                const panelHeight = detallePanel.offsetHeight || 200;
                let left = posicion.x + 10;
                let top = posicion.y + 10;

                if (left + panelWidth + padding > window.innerWidth) {
                    left = window.innerWidth - panelWidth - padding;
                }
                if (top + panelHeight + padding > window.innerHeight) {
                    top = window.innerHeight - panelHeight - padding;
                }

                detallePanel.style.left = `${Math.max(padding, left)}px`;
                detallePanel.style.top = `${Math.max(padding, top)}px`;
                detallePanel.style.right = 'auto';
            }

            const handleCerrarDetalle = () => cerrarDetalle();
            const handleDocClick = (ev) => {
                if (detallePanel.classList.contains('hidden')) return;
                if (!detallePanel.contains(ev.target)) {
                    cerrarDetalle();
                }
            };

            cerrarDetalleBtn?.addEventListener('click', handleCerrarDetalle);
            document.addEventListener('click', handleDocClick);

            async function mostrarDetallePaquete(paqueteId, codigo, posicion = null) {
                detallePanel.classList.remove('hidden');
                detallePanel.classList.add('flex'); // Add flex when shown
                detalleCodigo.textContent = codigo ? `Paquete ${codigo}` : `Paquete #${paqueteId}`;
                detalleContenido.innerHTML =
                    '<div class="flex items-center gap-2 text-blue-600 text-sm"><span class="animate-spin rounded-full h-4 w-4 border-2 border-current border-t-transparent"></span> Cargando etiquetas...</div>';
                ultimoPosDetalle = posicion || ultimoPosDetalle;
                posicionarPanel(ultimoPosDetalle);

                try {
                    let data = cacheDetalles.get(paqueteId);
                    if (!data) {
                        const resp = await fetch(`/paquetes/${paqueteId}/elementos`);
                        if (!resp.ok) throw new Error('No se pudieron cargar las etiquetas del paquete.');
                        data = await resp.json();
                        if (!data.success) throw new Error(data.message || 'Error al obtener datos.');
                        cacheDetalles.set(paqueteId, data);
                    }
                    renderDetalle(data);
                } catch (error) {
                    console.error('Error cargando detalle del paquete', error);
                    detalleContenido.innerHTML =
                        `<p class="text-red-600 text-sm">${error.message || 'Error inesperado al cargar el detalle.'}</p>`;
                }
            }

            function renderDetalle(data) {
                detalleContenido.innerHTML = '';
                const etiquetas = data.etiquetas || [];
                if (!etiquetas.length) {
                    detalleContenido.innerHTML =
                        '<p class="text-gray-600 text-sm">Este paquete no tiene etiquetas asociadas.</p>';
                    return;
                }

                etiquetas.forEach((etiqueta) => {
                    const etiquetaCard = document.createElement('div');
                    etiquetaCard.className = 'border border-gray-200 rounded-lg p-3 bg-gray-50 shadow-sm';

                    const header = document.createElement('div');
                    header.className = 'flex justify-between items-start mb-2';
                    const titulo = document.createElement('div');
                    const subCodigo = etiqueta.etiqueta_sub_id || etiqueta.codigo || etiqueta.id;
                    titulo.innerHTML =
                        `<p class="text-xs text-gray-500">Subetiqueta</p><p class="font-semibold text-gray-800">🏷️ ${subCodigo}</p>`;
                    const badge = document.createElement('span');
                    badge.className =
                        'text-[11px] px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-semibold';
                    badge.textContent = `${etiqueta.cantidad_elementos} elementos`;
                    header.appendChild(titulo);
                    header.appendChild(badge);
                    etiquetaCard.appendChild(header);

                    const elementosList = document.createElement('div');
                    elementosList.className = 'space-y-1';

                    if (!etiqueta.elementos || !etiqueta.elementos.length) {
                        const sinElementos = document.createElement('p');
                        sinElementos.className = 'text-xs text-gray-500';
                        sinElementos.textContent = 'Sin elementos en esta etiqueta.';
                        elementosList.appendChild(sinElementos);
                    } else {
                        etiqueta.elementos.forEach((elem) => {
                            const item = document.createElement('div');
                            item.className =
                                'text-xs text-gray-700 border border-gray-200 rounded px-2 py-2 bg-white space-y-1';

                            const header = document.createElement('div');
                            header.className = 'flex items-center justify-between';
                            const codigo = document.createElement('span');
                            codigo.className = 'font-semibold';
                            codigo.textContent = elem.codigo;
                            const peso = document.createElement('span');
                            peso.className = 'text-gray-500';
                            peso.textContent = `${elem.peso_kg ?? '-'}`;
                            header.appendChild(codigo);
                            header.appendChild(peso);
                            item.appendChild(header);

                            const svg = renderDimensionesSvg(elem.dimensiones);
                            if (svg) {
                                item.appendChild(svg);
                            } else {
                                const dimensionesText = document.createElement('span');
                                dimensionesText.className = 'text-gray-600 block';
                                dimensionesText.textContent = elem.dimensiones ||
                                    'Dimensiones no disponibles';
                                item.appendChild(dimensionesText);
                            }

                            elementosList.appendChild(item);
                        });
                    }

                    etiquetaCard.appendChild(elementosList);
                    detalleContenido.appendChild(etiquetaCard);
                });
            }

            // Cleanup function using the captured reference to handlers
            // Añadimos al sistema global de limpieza
            window.pageInitializers = window.pageInitializers || [];
            window.pageInitializers.push(() => {
                document.body.dataset.mapalocalizacionesPageInit = 'false';
                // Limpiar listeners globales
                document.removeEventListener('contextmenu', handleDocContextMenu);
                document.removeEventListener('click', handleDocClick);
            });

            // Marcar inicializado
            document.body.dataset.mapalocalizacionesPageInit = 'true';
        };

        // Eliminar listener anterior si existe para evitar duplicados al reevaluar el script
        if (window.initMapalocalizacionesPage) {
            document.removeEventListener('livewire:navigated', window.initMapalocalizacionesPage);
        }

        // Iniciar
        document.addEventListener('livewire:navigated', window.initMapalocalizacionesPage);
        window.initMapalocalizacionesPage();
    </script>
</x-app-layout>
