<x-app-layout>
    {{-- Slots de Layout (Cabecera) --}}
    <x-slot name="title">Mapa de Ubicaciones</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar mapa de {{ $obraActiva->obra ?? 'localizaciones' }}
        </h2>
    </x-slot>

    {{-- Menús (Blade) --}}
    <x-menu.localizaciones.menu-localizaciones-vistas :obra-actual-id="$obraActualId ?? null" route-index="localizaciones.index"
        route-create="localizaciones.create" />

    <x-menu.localizaciones.menu-localizaciones-naves :obras="$obras" :obra-actual-id="$obraActualId ?? null" />

    {{-- Vista principal (HUD + Grid) --}}
    <div class="h-screen w-screen flex flex-col">
        <!-- HUD con posición del cursor y escala -->
        <div class="p-2 bg-white z-10 flex items-center gap-6">
            <div class="text-gray-800 font-semibold">
                Posición actual: <span id="posicionActual">—</span>
            </div>
            <div class="text-sm text-gray-500">Escala: 1 celda = 0,5 m</div>
            <div class="text-sm text-gray-500" id="orientacionInfo">Orientación: Normal</div>
            <div id="modoEdicion" class="hidden text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
                🔧 Editando posición
            </div>
        </div>

        <!-- Bandeja: fichas de máquinas disponibles para colocar -->
        <div id="bandeja" class="w-full flex flex-wrap gap-2 p-2 bg-white/80 sticky top-0 z-30"></div>

        <!-- Grid: contenedor de la rejilla, overlays y sombra de preview -->
        <div class="relative flex-1 p-3">
            <div id="grid" data-ancho-m="{{ $dimensiones['ancho'] }}" data-alto-m="{{ $dimensiones['alto'] }}">
                <div id="overlays"></div> {{-- capa donde se dibujan rectángulos/etiquetas/herramientas --}}
                <div id="previewRect" class="hidden absolute border-2"></div> {{-- sombra de previsualización --}}
            </div>
        </div>
    </div>

    {{-- Modal para preparar colocación --}}
    <div id="placerModal" class="hidden fixed inset-0 z-40 bg-black/50 items-center justify-center">
        <div class="bg-white rounded-xl shadow-2xl p-4 w-[min(92vw,720px)]">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Colocar máquina</h3>
                <button id="cancelPlaceBtn" class="text-sm text-gray-500 hover:text-gray-800">Cerrar ✕</button>
            </div>

            <!-- Canvas previo: solo para mostrar la huella centrada y poder girarla -->
            <div id="placerCanvas" class="relative mt-3 w-full h-48 border rounded-md bg-gray-50 overflow-hidden">
                <div id="centerPreview" class="absolute bg-blue-400/40 border-2 border-blue-600 rounded-sm"></div>
                <div id="orientacionIndicator"
                    class="absolute top-2 left-2 bg-white px-2 py-1 rounded text-xs font-bold shadow-sm">Normal</div>
                <!-- Grid de fondo para el canvas -->
                <div id="canvasGrid" class="absolute inset-0 opacity-20"
                    style="background-image: linear-gradient(to right, #9ca3af 1px, transparent 1px), linear-gradient(to bottom, #9ca3af 1px, transparent 1px); background-size: 8px 8px;">
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between gap-3">
                <div class="text-sm text-gray-700">
                    Máquina: <b id="mLabel">—</b> · Huella: <b id="huellaLabel">—</b> celdas
                </div>
                <div class="flex gap-2">
                    <!-- Rotar huella 90º (atajo R) -->
                    <button id="rotateBtn" type="button" class="px-3 py-1 border rounded bg-white hover:bg-gray-50">⤾
                        Girar 90º (R)</button>
                    <!-- Salir a arrastrar al mapa -->
                    <button id="startPlaceBtn" type="button"
                        class="px-3 py-1 border rounded bg-blue-600 text-white hover:bg-blue-700">
                        Arrastrar al mapa
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Estilos mejorados --}}
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
        }

        /* Grid horizontal: ocupa 95vw y se ajusta en altura para verse completo sin scroll */
        #grid {
            position: relative;
            width: 95vw;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d1d5db;
        }

        /* Líneas de la rejilla: patrón dibujado en background (no creamos miles de divs) */
        .grid-lines {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(to right, rgba(0, 0, 0, .08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(0, 0, 0, .08) 1px, transparent 1px);
            background-repeat: repeat;
            z-index: 1;
        }

        #overlays {
            position: absolute;
            inset: 0;
            z-index: 5;
        }

        .area {
            position: absolute;
            background-color: rgba(0, 150, 136, 0.3);
            border: 1px solid #009688;
            cursor: default;
        }

        .rotate-btn {
            position: absolute;
            top: 2px;
            right: 2px;
            background-color: white;
            border: none;
            border-radius: 50%;
            font-size: 14px;
            cursor: pointer;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .drag-handle {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 20px;
            height: 20px;
            background-color: #ffeb3b;
            border-radius: 3px;
            cursor: grab;
        }

        /* Manita: interacción de arrastre sobre máquinas */
        .area.tipo-maquina {
            background: #1d4ed8 !important;
            cursor: grab;
        }

        /* Etiqueta con nombre de máquina (no intercepta clicks) */
        .maquina-label {
            position: absolute;
            transform: translate(-50%, -100%);
            font-size: 10px;
            font-weight: 700;
            color: #111;
            background: rgba(255, 255, 255, .9);
            border-radius: 4px;
            padding: 1px 4px;
            pointer-events: none;
            white-space: nowrap;
            z-index: 10;
        }

        /* Sombra de previsualización (verde=ok / rojo=colisión) */
        #previewRect {
            z-index: 15;
            background: rgba(255, 165, 0, .28);
            border-color: #f97316;
            pointer-events: none;
        }

        #previewRect.ok {
            background: rgba(34, 197, 94, .25) !important;
            border-color: #22c55e !important;
        }

        #previewRect.bad {
            background: rgba(239, 68, 68, .25) !important;
            border-color: #ef4444 !important;
        }

        /* Cuadrícula de ocupación para edición de máquinas */
        .grid-ocupacion {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 2;
            opacity: 0.7;
        }

        .celda-ocupacion {
            position: absolute;
            border: 0.5px solid rgba(0, 0, 0, 0.05);
            transition: background-color 0.2s ease;
        }

        .celda-libre {
            background-color: rgba(34, 197, 94, 0.15);
        }

        .celda-ocupada {
            background-color: rgba(239, 68, 68, 0.25);
        }

        /* Animación sutil para la aparición de la cuadrícula */
        .grid-ocupacion {
            animation: fadeInGrid 0.3s ease-in-out;
        }

        @keyframes fadeInGrid {
            from {
                opacity: 0;
            }

            to {
                opacity: 0.7;
            }
        }

        /* Herramientas del overlay (rotar) */
        .overlay-tools {
            position: absolute;
            top: -10px;
            right: -10px;
            display: flex;
            gap: 6px;
            z-index: 20;
            pointer-events: auto;
        }

        .overlay-btn {
            width: 24px;
            height: 24px;
            border-radius: 9999px;
            background: #ffffffee;
            border: 1px solid #d1d5db;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            line-height: 1;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .08);
        }

        .overlay-btn:hover {
            background: #f3f4f6;
        }

        .btn-eliminar {
            background: #fee2e2 !important;
            color: #dc2626 !important;
            border-color: #fca5a5 !important;
        }

        .btn-eliminar:hover {
            background: #fecaca !important;
            color: #b91c1c !important;
        }

        .overlay-draggable {
            position: absolute;
            border: 2px solid #4a90e2;
            background-color: rgba(74, 144, 226, 0.2);
            /* transparente */
            box-sizing: border-box;
        }

        /* Fichas de la bandeja */
        .token-maquina {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .35rem .6rem;
            border: 1px solid #d1d5db;
            border-radius: .5rem;
            background: #f8fafc;
            cursor: pointer;
            user-select: none;
            font-size: .9rem;
            transition: all 0.2s ease;
        }

        .token-maquina:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .token-maquina.colocada {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .maquina-ficha {
            font-size: .7rem;
            background: #e5e7eb;
            border-radius: .4rem;
            padding: .1rem .35rem;
        }

        .area.tipo-maquina.arrastrando {
            cursor: grabbing;
            opacity: 0.7;
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 25 !important;
        }

        /* Nuevos estilos para mejorar la UX */
        .orientacion-normal {
            background: rgba(34, 197, 94, 0.3) !important;
            border-color: #16a34a !important;
        }

        .orientacion-girada {
            background: rgba(59, 130, 246, 0.3) !important;
            border-color: #2563eb !important;
        }

        .orientacion-indicator {
            position: absolute;
            top: 2px;
            left: 2px;
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            z-index: 10;
        }

        .orientacion-normal .orientacion-indicator {
            background: #dcfce7;
            color: #166534;
        }

        .orientacion-girada .orientacion-indicator {
            background: #dbeafe;
            color: #1e40af;
        }
    </style>

    {{-- Rutas JS y contexto global --}}
    <script>
        window.routes = {
            verificar: "{{ route('localizaciones.verificar') }}", // POST: valida solapes/duplicados
            store: "{{ route('localizaciones.store') }}", // POST: crear localización
            updateBase: "{{ url('localizaciones') }}" // PUT: /localizaciones/{id}
        };
        window.context = {
            nave_id: {{ $obraActualId ?? 'null' }} // Nave/obra actual
        };
    </script>

    {{-- JavaScript mejorado y reorganizado --}}
    <script>
        // Constantes y configuración
        const CELDA_METROS = 0.5;
        const RENDER_TUMBADA = true;

        // Estado de la aplicación
        const appState = {
            orientacion: 'normal', // 'normal' o 'girada'
            ocupadas: new Set(),
            arrastre: null,
            dragEstado: null,
            tokenSel: null,
            huella: {
                w: 1,
                h: 1
            },
            rectGrid: null
        };

        // Elementos DOM
        const elementos = {
            grid: document.getElementById('grid'),
            overlays: document.getElementById('overlays'),
            sombra: document.getElementById('previewRect'),
            bandeja: document.getElementById('bandeja'),
            hudPos: document.getElementById('posicionActual'),
            orientacionInfo: document.getElementById('orientacionInfo'),
            modoEdicion: document.getElementById('modoEdicion'),
            modal: document.getElementById('placerModal'),
            canvas: document.getElementById('placerCanvas'),
            prevCtr: document.getElementById('centerPreview'),
            orientacionIndicator: document.getElementById('orientacionIndicator'),
            btnR: document.getElementById('rotateBtn'),
            btnGo: document.getElementById('startPlaceBtn'),
            btnX: document.getElementById('cancelPlaceBtn'),
            lblHuf: document.getElementById('huellaLabel'),
            lblNom: document.getElementById('mLabel')
        };

        // Dimensiones del grid
        const anchoNaveM = parseFloat(elementos.grid.dataset.anchoM) || 10;
        const altoNaveM = parseFloat(elementos.grid.dataset.altoM) || 10;
        const colsCanon = metrosACeldas(anchoNaveM);
        const filasCanon = metrosACeldas(altoNaveM);
        const columnas = RENDER_TUMBADA ? filasCanon : colsCanon;
        const filas = RENDER_TUMBADA ? colsCanon : filasCanon;

        // Datos desde Blade
        const localizacionesConMaquina = @json($localizacionesConMaquina ?? []);
        const localizacionesTodas = @json($localizacionesTodas ?? []);
        const maquinas = @json($maquinas ?? []);

        // ===== HELPERS: geometría, conversión celdas↔px y utilidades DOM =====
        function metrosACeldas(m) {
            return Math.max(1, Math.round(m / CELDA_METROS));
        }

        // Intercambia ejes (vista "tumbada")
        function intercambiarRect({
            x1,
            y1,
            x2,
            y2
        }) {
            return {
                x1: y1,
                y1: x1,
                x2: y2,
                y2: x2
            };
        }

        /* ===== Conversión celdas -> px (para CSS absolutos) ===== */
        function rectCeldasAPx({
            x1,
            y1,
            x2,
            y2
        }, {
            columnas,
            filas
        }, rectGrid) {
            const cw = rectGrid.width / columnas,
                ch = rectGrid.height / filas;
            return {
                left: (x1 - 1) * cw,
                top: (filas - y2) * ch, // invertimos Y para pantalla
                width: (x2 - x1 + 1) * cw,
                height: (y2 - y1 + 1) * ch
            };
        }

        /* ===== Ocupación de celdas (colisiones en cliente) ===== */
        function ocuparRect(set, {
            x1,
            y1,
            x2,
            y2
        }) {
            for (let y = y1; y <= y2; y++)
                for (let x = x1; x <= x2; x++) set.add(`${x},${y}`);
        }

        function liberarRect(set, {
            x1,
            y1,
            x2,
            y2
        }) {
            for (let y = y1; y <= y2; y++)
                for (let x = x1; x <= x2; x++) set.delete(`${x},${y}`);
        }

        function libre(set, {
            x1,
            y1,
            x2,
            y2
        }) {
            for (let y = y1; y <= y2; y++)
                for (let x = x1; x <= x2; x++)
                    if (set.has(`${x},${y}`)) return false;
            return true;
        }

        /* ===== Ajuste del grid a viewport + líneas ===== */
        function ajustarGridSinScroll(elGrid, columnas, filas) {
            const vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
            const vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
            const reservadoY = 160; // Header + HUD + Bandeja
            const maxW = vw * 0.95;
            const maxH = Math.max(200, vh - reservadoY);
            const lado = Math.min(maxW / columnas, maxH / filas);

            elGrid.style.width = Math.floor(lado * columnas) + 'px';
            elGrid.style.height = Math.floor(lado * filas) + 'px';

            let lineas = elGrid.querySelector('.grid-lines');
            if (!lineas) {
                lineas = document.createElement('div');
                lineas.className = 'grid-lines';
                elGrid.appendChild(lineas);
            }
            lineas.style.backgroundSize = `${lado}px ${lado}px`;
            return elGrid.getBoundingClientRect();
        }

        // ===== HELPERS: cuadrícula de ocupación =====
        function mostrarCuadriculaOcupacion() {
            let gridOcupacion = elementos.grid.querySelector('.grid-ocupacion');
            if (!gridOcupacion) {
                gridOcupacion = document.createElement('div');
                gridOcupacion.className = 'grid-ocupacion';
                elementos.grid.appendChild(gridOcupacion);

                const cw = appState.rectGrid.width / columnas;
                const ch = appState.rectGrid.height / filas;

                // Crear celdas para toda la cuadrícula solo una vez
                for (let y = 1; y <= filas; y++) {
                    for (let x = 1; x <= columnas; x++) {
                        const celda = document.createElement('div');
                        celda.className = 'celda-ocupacion';
                        celda.dataset.x = x;
                        celda.dataset.y = y;

                        // Posicionar la celda
                        celda.style.left = `${(x - 1) * cw}px`;
                        celda.style.top = `${(filas - y) * ch}px`;
                        celda.style.width = `${cw}px`;
                        celda.style.height = `${ch}px`;

                        gridOcupacion.appendChild(celda);
                    }
                }
            }

            // Solo actualizar colores la primera vez que se crea
            actualizarColoresCuadricula();
        }

        function actualizarColoresCuadricula() {
            const gridOcupacion = elementos.grid.querySelector('.grid-ocupacion');
            if (!gridOcupacion) return;

            const celdas = gridOcupacion.querySelectorAll('.celda-ocupacion');
            celdas.forEach(celda => {
                const x = parseInt(celda.dataset.x);
                const y = parseInt(celda.dataset.y);

                // Convertir coordenadas de render a canónicas para verificar ocupación
                const rectRender = {
                    x1: x,
                    y1: y,
                    x2: x,
                    y2: y
                };
                const rectCanon = aCanon(rectRender);
                const clave = `${rectCanon.x1},${rectCanon.y1}`;

                // Remover clases anteriores y agregar la correcta
                celda.classList.remove('celda-libre', 'celda-ocupada');
                if (appState.ocupadas.has(clave)) {
                    celda.classList.add('celda-ocupada');
                } else {
                    celda.classList.add('celda-libre');
                }
            });
        }

        function ocultarCuadriculaOcupacion() {
            const gridOcupacion = elementos.grid.querySelector('.grid-ocupacion');
            if (gridOcupacion) {
                gridOcupacion.remove();
            }
        }

        // ===== HELPERS: dibujarOverlay y dibujarEtiqueta =====
        function dibujarOverlay(capa, clave, cajaPx, clase = 'area tipo-maquina', meta = null) {
            // crea o reutiliza
            let el = capa.querySelector(`[data-clave="${clave}"]`);
            if (!el) {
                el = document.createElement('div');
                el.dataset.clave = clave;
                el.className = clase; // ej. 'area tipo-maquina'
                el.style.position = 'absolute';
                el.style.boxSizing = 'border-box';
                el.style.pointerEvents = 'auto';
                el.style.zIndex = '6'; // por encima de .grid-lines (z:1)
                capa.appendChild(el);
            }

            // ⚠️ usar left/top/width/height (NO x/y)
            el.style.left = `${cajaPx.left}px`;
            el.style.top = `${cajaPx.top}px`;
            el.style.width = `${cajaPx.width}px`;
            el.style.height = `${cajaPx.height}px`;

            // data-* para edición/drag
            if (meta) {
                if (meta.id != null) el.dataset.id = meta.id;
                if (meta.nombre != null) el.dataset.nombre = meta.nombre;
                if (meta.maquinaId != null) el.dataset.maquinaId = meta.maquinaId;
                if (meta.x1 != null) el.dataset.x1 = meta.x1;
                if (meta.y1 != null) el.dataset.y1 = meta.y1;
                if (meta.x2 != null) el.dataset.x2 = meta.x2;
                if (meta.y2 != null) el.dataset.y2 = meta.y2;
            }

            // Añadir herramientas de overlay (rotar y eliminar) solo si tiene ID (máquinas guardadas)
            if (meta && meta.id && clase.includes('tipo-maquina')) {
                crearHerramientasOverlay(el);
            }

            return el;
        }

        function crearHerramientasOverlay(elemento) {
            // Verificar si ya tiene herramientas
            let herramientasExistentes = elemento.querySelector('.overlay-tools');
            if (herramientasExistentes) {
                // Si ya existen, asegurarse de que tienen ambos botones
                if (!herramientasExistentes.querySelector('.btn-rotar') || !herramientasExistentes.querySelector(
                        '.btn-eliminar')) {
                    herramientasExistentes.remove();
                    herramientasExistentes = null;
                } else {
                    return; // Ya tiene las herramientas completas
                }
            }

            const herramientas = document.createElement('div');
            herramientas.className = 'overlay-tools';
            herramientas.innerHTML = `
                <button class="overlay-btn btn-rotar" title="Rotar 90°">⤾</button>
                <button class="overlay-btn btn-eliminar" title="Eliminar máquina">✕</button>
            `;
            elemento.appendChild(herramientas);
        }

        // Etiqueta con el nombre de la máquina
        function dibujarEtiqueta(capa, clave, cajaPx, texto) {
            let lab = capa.querySelector(`[data-clave="${clave}-label"]`);
            if (!lab) {
                lab = document.createElement('div');
                lab.dataset.clave = `${clave}-label`;
                lab.className = 'maquina-label';
                capa.appendChild(lab);
            }
            lab.textContent = texto;
            lab.style.left = (cajaPx.left + cajaPx.width / 2) + 'px';
            lab.style.top = cajaPx.top + 'px';
            return lab;
        }

        // ===== Funciones de gestión de estado =====
        function actualizarOrientacion(nuevaOrientacion) {
            appState.orientacion = nuevaOrientacion;
            elementos.orientacionInfo.textContent = `Orientación: ${nuevaOrientacion === 'normal' ? 'Normal' : 'Girada'}`;

            if (elementos.orientacionIndicator) {
                elementos.orientacionIndicator.textContent = nuevaOrientacion === 'normal' ? 'Normal' : 'Girada';
                elementos.orientacionIndicator.className = nuevaOrientacion === 'normal' ?
                    'orientacion-indicator orientacion-normal' : 'orientacion-indicator orientacion-girada';
            }
        }

        // ===== Funciones de inicialización =====
        function inicializarVista() {
            // Ajustar grid y pintar existentes
            appState.rectGrid = ajustarGridSinScroll(elementos.grid, columnas, filas);
            pintarLocalizacionesExistentes();
            renderizarBandejaMaquinas();

            // Ajustar grid al redimensionar
            window.addEventListener('resize', () => {
                appState.rectGrid = ajustarGridSinScroll(elementos.grid, columnas, filas);
            });
        }

        function pintarLocalizacionesExistentes() {
            localizacionesConMaquina.forEach((loc, i) => {
                ocuparRect(appState.ocupadas, loc);
                const rRender = aRender(loc);
                const cajaPx = rectCeldasAPx(rRender, {
                    columnas,
                    filas
                }, appState.rectGrid);
                const clave = `loc-${loc.id ?? i}`;
                const meta = {
                    id: loc.id ?? null,
                    nombre: loc.nombre ?? '',
                    maquinaId: loc.maquina_id ?? null,
                    x1: loc.x1,
                    y1: loc.y1,
                    x2: loc.x2,
                    y2: loc.y2
                };
                const el = dibujarOverlay(elementos.overlays, clave, cajaPx, 'area tipo-maquina', meta);
                if (loc.nombre) dibujarEtiqueta(elementos.overlays, clave, cajaPx, loc.nombre);

                // Añadir evento para arrastrar
                el.addEventListener('mousedown', onOverlayMouseDown);

                // Forzar creación de herramientas si tiene ID (máquina guardada)
                if (loc.id) {
                    crearHerramientasOverlay(el);
                }
            });
        }

        function renderizarBandejaMaquinas() {
            // Limpiar bandeja
            elementos.bandeja.innerHTML = '';

            // Identificar máquinas ya colocadas
            const idsColocados = new Set(localizacionesConMaquina.filter(l => l.tipo === 'maquina' && l.maquina_id).map(l =>
                l
                .maquina_id));
            // Renderizar solo las que no están colocadas
            maquinas.forEach(maquina => {
                if (idsColocados.has(maquina.id)) return;
                const token = document.createElement('div');
                token.className = 'token-maquina';
                token.dataset.id = maquina.id;
                token.dataset.nombre = maquina.nombre;
                token.dataset.ancho = maquina.ancho_m;
                token.dataset.largo = maquina.largo_m;
                token.innerHTML =
                    `<strong>${maquina.nombre}</strong><span class="maquina-ficha">${maquina.ancho_m ?? 1}m × ${maquina.largo_m ?? 1}m</span>`;

                token.addEventListener('click', () => abrirColocador(token));
                elementos.bandeja.appendChild(token);
            });
        }

        // ===== Funciones de interacción =====
        function abrirColocador(token) {
            appState.tokenSel = token;
            const aw = parseFloat(token.dataset.ancho) || 1;
            const ah = parseFloat(token.dataset.largo) || 1;

            // Reiniciar a orientación normal
            actualizarOrientacion('normal');
            appState.huella.w = Math.max(1, Math.ceil(aw / CELDA_METROS));
            appState.huella.h = Math.max(1, Math.ceil(ah / CELDA_METROS));

            if (RENDER_TUMBADA)[appState.huella.w, appState.huella.h] = [appState.huella.h, appState.huella.w];

            elementos.lblNom.textContent = token.dataset.nombre;
            elementos.modal.classList.remove('hidden');
            elementos.modal.classList.add('flex');

            // Actualizar previsualización después de que el modal sea visible
            setTimeout(() => {
                actualizarPreviewCentro();
            }, 10);

            // Añadir listener para tecla R
            document.addEventListener('keydown', atajoGirar, {
                capture: true
            });
        }

        function cerrarColocador() {
            elementos.modal.classList.add('hidden');
            elementos.modal.classList.remove('flex');

            // Ocultar la previsualización
            elementos.prevCtr.style.display = 'none';

            document.removeEventListener('keydown', atajoGirar, {
                capture: true
            });
        }

        function atajoGirar(e) {
            if (e.key.toLowerCase() === 'r') girarHuella();
        }

        function girarHuella() {
            [appState.huella.w, appState.huella.h] = [appState.huella.h, appState.huella.w];
            actualizarOrientacion(appState.orientacion === 'normal' ? 'girada' : 'normal');
            actualizarPreviewCentro();
        }

        function actualizarPreviewCentro() {
            elementos.lblHuf.textContent = `${appState.huella.w} × ${appState.huella.h}`;

            // Obtener dimensiones del canvas
            const r = elementos.canvas.getBoundingClientRect();
            const canvasWidth = r.width;
            const canvasHeight = r.height;

            // Calcular el tamaño de celda para que la máquina se vea bien proporcionada
            const maxCeldas = Math.max(appState.huella.w, appState.huella.h);
            const margen = 60; // margen en píxeles
            const espacioDisponible = Math.min(canvasWidth - margen, canvasHeight - margen);
            const tamañoCelda = Math.max(6, Math.min(12, espacioDisponible / (maxCeldas +
                4))); // entre 6px y 12px por celda (reducido a la mitad)

            // Calcular dimensiones de la previsualización
            const previewWidth = appState.huella.w * tamañoCelda;
            const previewHeight = appState.huella.h * tamañoCelda;

            // Centrar la previsualización
            const left = (canvasWidth - previewWidth) / 2;
            const top = (canvasHeight - previewHeight) / 2;

            // Aplicar estilos
            Object.assign(elementos.prevCtr.style, {
                width: previewWidth + 'px',
                height: previewHeight + 'px',
                left: left + 'px',
                top: top + 'px',
                display: 'block'
            });

            // Actualizar el grid de fondo para que coincida con el tamaño de celda
            const canvasGrid = document.getElementById('canvasGrid');
            if (canvasGrid) {
                canvasGrid.style.backgroundSize = `${tamañoCelda}px ${tamañoCelda}px`;
            }

            // Actualizar el indicador de orientación
            elementos.orientacionIndicator.textContent = appState.orientacion === 'normal' ? 'Normal' : 'Girada';
            elementos.orientacionIndicator.className = appState.orientacion === 'normal' ?
                'absolute top-2 left-2 bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold shadow-sm' :
                'absolute top-2 left-2 bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold shadow-sm';
        }

        function iniciarArrastreDesdeModal() {
            if (!appState.tokenSel) return;
            cerrarColocador();
            iniciarArrastreNueva(appState.tokenSel, appState.huella.w, appState.huella.h);
        }

        // ===== Funciones de arrastre y colocación =====
        function aCanon(r) {
            return RENDER_TUMBADA ? intercambiarRect(r) : r;
        }

        function aRender(r) {
            return RENDER_TUMBADA ? r : intercambiarRect(r);
        }

        function mostrarSombraCentrada(w, h) {
            elementos.sombra.classList.remove('hidden');
            const cw = appState.rectGrid.width / columnas,
                ch = appState.rectGrid.height / filas;
            let rx = Math.floor(columnas / 2 - w / 2) + 1;
            let ry = Math.floor(filas / 2 - h / 2) + 1;
            rx = Math.min(Math.max(1, rx), columnas - w + 1);
            ry = Math.min(Math.max(1, ry), filas - h + 1);
            ponerSombraEn(rx, ry, w, h);
        }

        function ponerSombraEn(rx, ry, w = appState.arrastre.anchoCeldas, h = appState.arrastre.altoCeldas) {
            const rectRender = {
                x1: rx,
                y1: ry,
                x2: rx + w - 1,
                y2: ry + h - 1
            };
            const rectCanon = aCanon(rectRender);
            const ok = libre(appState.ocupadas, rectCanon);
            const cajaPx = rectCeldasAPx(rectRender, {
                columnas,
                filas
            }, appState.rectGrid);
            Object.assign(elementos.sombra.style, {
                left: `${cajaPx.left}px`,
                top: `${cajaPx.top}px`,
                width: `${cajaPx.width}px`,
                height: `${cajaPx.height}px`
            });
            elementos.sombra.dataset.rx = rx;
            elementos.sombra.dataset.ry = ry;
            elementos.sombra.classList.toggle('ok', ok);
            elementos.sombra.classList.toggle('bad', !ok);
        }

        function iniciarArrastreNueva(token, w, h) {
            appState.arrastre = {
                modo: 'nuevo',
                token,
                nombre: token.dataset.nombre,
                maquinaId: +token.dataset.id,
                anchoCeldas: w,
                altoCeldas: h
            };
            mostrarSombraCentrada(w, h);
            document.addEventListener('mousemove', onMouseMoveSombra);
            elementos.grid.addEventListener('click', onClickConfirmarNuevo);
        }

        function detenerArrastre(confirmado = false) {
            // Limpiar sombra
            elementos.sombra.classList.add('hidden');
            elementos.sombra.classList.remove('ok', 'bad');

            // Limpiar event listeners
            document.removeEventListener('mousemove', onMouseMoveSombra);
            document.removeEventListener('mousemove', onDragMove);
            document.removeEventListener('mouseup', onDragEnd);
            elementos.grid.removeEventListener('click', onClickConfirmarNuevo);

            // Ocultar cuadrícula de ocupación si está visible
            ocultarCuadriculaOcupacion();

            // Si hay un arrastre de máquina existente en curso, restaurar su ocupación
            if (appState.dragEstado && !confirmado) {
                ocuparRect(appState.ocupadas, appState.dragEstado.rectCanonIni);
                const cont = appState.dragEstado.elemento || elementos.overlays.querySelector(
                    `.area[data-id="${appState.dragEstado.id}"]`);
                if (cont) {
                    cont.classList.remove('arrastrando');
                    // Limpiar flag de procesamiento si existe
                    cont.dataset.procesando = 'false';
                }
                elementos.modoEdicion.classList.add('hidden');
                appState.dragEstado = null;
            }

            // Limpiar estado de arrastre
            appState.arrastre = null;
        }

        function onMouseMoveSombra(e) {
            if (!appState.arrastre) return;
            const r = elementos.grid.getBoundingClientRect();
            const cw = r.width / columnas,
                ch = r.height / filas;
            const rx = Math.min(columnas, Math.max(1, Math.floor((e.clientX - r.left) / cw) + 1));
            const ryT = Math.min(filas, Math.max(1, Math.floor((e.clientY - r.top) / ch) + 1));
            const ry = filas - ryT + 1;
            const rxC = Math.min(Math.max(1, rx), columnas - appState.arrastre.anchoCeldas + 1);
            const ryC = Math.min(Math.max(1, ry), filas - appState.arrastre.altoCeldas + 1);
            ponerSombraEn(rxC, ryC);
        }

        // Confirmar NUEVO (click)
        async function onClickConfirmarNuevo() {
            if (!appState.arrastre) return;
            const rx = +elementos.sombra.dataset.rx,
                ry = +elementos.sombra.dataset.ry;
            const rectRender = {
                x1: rx,
                y1: ry,
                x2: rx + appState.arrastre.anchoCeldas - 1,
                y2: ry + appState.arrastre.altoCeldas - 1
            };
            const rectCanon = aCanon(rectRender);

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const naveId = (window.context && window.context.nave_id) ?? {{ $obraActualId ?? 'null' }};

            // Verificar servidor con timeout
            const verRes = await conTimeout(fetch(window.routes.verificar, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    ...rectCanon,
                    nave_id: naveId,
                    maquina_id: appState.arrastre.maquinaId
                })
            }), 8000);
            const ver = await verRes.json().catch(() => ({}));
            if (!verRes.ok) {
                await mostrarAlerta('error', 'Error', ver?.message || 'Error en verificación');
                return;
            }
            if (ver.existe) {
                const msg = ver.tipo === 'exacta' ? 'Área ya registrada en esta nave.' :
                    ver.tipo === 'parcial' ? 'La zona solapa con otra localización.' :
                    ver.message || 'Ubicación no permitida.';
                await mostrarAlerta('warning', 'No permitido', msg);
                return;
            }

            // Guardar (crear) con timeout
            const resSave = await conTimeout(fetch(window.routes.store, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    ...rectCanon,
                    tipo: 'maquina',
                    maquina_id: appState.arrastre.maquinaId,
                    nave_id: naveId
                })
            }), 8000);
            const dataSave = await resSave.json().catch(() => ({}));
            if (!resSave.ok || !dataSave?.success) {
                const msg = dataSave?.message || Object.values(dataSave?.errors || {}).flat().join(', ') ||
                    'Error al guardar.';
                await mostrarAlerta('error', 'Error', msg);
                return;
            }

            // Dibujar en cuadrícula
            const idNuevo = dataSave?.localizacion?.id ?? `nuevo-${Date.now()}`;
            const clave = `loc-${idNuevo}`;
            const cajaPx = rectCeldasAPx(rectRender, {
                columnas,
                filas
            }, appState.rectGrid);
            const el = dibujarOverlay(elementos.overlays, clave, cajaPx, 'area tipo-maquina', {
                id: idNuevo,
                nombre: appState.arrastre.nombre,
                maquinaId: appState.arrastre.maquinaId,
                x1: rectCanon.x1,
                y1: rectCanon.y1,
                x2: rectCanon.x2,
                y2: rectCanon.y2
            });
            if (appState.arrastre.nombre) dibujarEtiqueta(elementos.overlays, clave, cajaPx, appState.arrastre.nombre);

            // Añadir evento para arrastrar
            el.addEventListener('mousedown', onOverlayMouseDown);

            // Añadir la nueva localización al array
            const nuevaLocalizacion = {
                id: idNuevo,
                nombre: appState.arrastre.nombre,
                maquina_id: appState.arrastre.maquinaId,
                tipo: 'maquina',
                x1: rectCanon.x1,
                y1: rectCanon.y1,
                x2: rectCanon.x2,
                y2: rectCanon.y2
            };
            localizacionesConMaquina.push(nuevaLocalizacion);

            ocuparRect(appState.ocupadas, rectCanon);
            appState.arrastre.token?.remove(); // si no debe volver a colocarse
            const nombreOk = appState.arrastre.nombre;
            detenerArrastre(true);
            await mostrarAlerta('success', 'Guardado', `Ubicación asignada a "${nombreOk}"`);
        }

        // ===== Funciones para manejar overlays existentes =====
        async function manejarClicOverlay(e) {
            const btnRotar = e.target.closest('.btn-rotar');
            const btnEliminar = e.target.closest('.btn-eliminar');

            if (!btnRotar && !btnEliminar) return;

            const el = (btnRotar || btnEliminar).closest('.area[data-id]');
            if (!el) return;

            // Prevenir múltiples clics
            if (el.dataset.procesando === 'true') return;
            el.dataset.procesando = 'true';

            try {
                const id = +el.dataset.id;
                const nombre = el.dataset.nombre || '';
                const naveId = (window.context && window.context.nave_id) ?? {{ $obraActualId ?? 'null' }};
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                if (!csrf) {
                    await mostrarAlerta('error', 'Error', 'Token CSRF no encontrado. Recarga la página.');
                    return;
                }

                if (btnEliminar) {
                    await eliminarMaquina(id, nombre, el, csrf);
                    return;
                }

                if (btnRotar) {
                    await rotarMaquina(id, nombre, el, naveId, csrf);
                    return;
                }
            } catch (error) {
                console.error('Error en manejarClicOverlay:', error);
                await mostrarAlerta('error', 'Error', 'Ocurrió un error inesperado. Inténtalo de nuevo.');
            } finally {
                // Siempre limpiar el flag de procesamiento
                el.dataset.procesando = 'false';
            }
        }

        async function eliminarMaquina(id, nombre, elemento, csrf) {
            // Confirmar eliminación
            const result = await (async () => {
                try {
                    if (typeof Swal !== 'undefined') {
                        return await Swal.fire({
                            title: '¿Eliminar máquina?',
                            text: `¿Estás seguro de que quieres eliminar "${nombre}" del mapa?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#dc2626',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        });
                    } else {
                        return {
                            isConfirmed: confirm(`¿Eliminar máquina "${nombre}" del mapa?`)
                        };
                    }
                } catch (error) {
                    console.error('Error al mostrar confirmación:', error);
                    return {
                        isConfirmed: confirm(`¿Eliminar máquina "${nombre}" del mapa?`)
                    };
                }
            })();

            if (!result.isConfirmed) return;

            try {
                // Realizar petición DELETE con timeout
                const deleteRes = await conTimeout(fetch(`${window.routes.updateBase}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                }), 8000);

                const deleteData = await deleteRes.json().catch(() => ({}));

                if (!deleteRes.ok || !deleteData?.success) {
                    throw new Error(deleteData?.message || 'No se pudo eliminar la máquina.');
                }

                // Remover del DOM
                elemento.remove();

                // Remover etiqueta si existe
                const etiqueta = elementos.overlays.querySelector(
                    `[data-clave="${elemento.dataset.clave}-label"].maquina-label`);
                if (etiqueta) etiqueta.remove();

                // Liberar celdas ocupadas
                const rectCanon = {
                    x1: +elemento.dataset.x1,
                    y1: +elemento.dataset.y1,
                    x2: +elemento.dataset.x2,
                    y2: +elemento.dataset.y2
                };
                liberarRect(appState.ocupadas, rectCanon);

                // Remover de la lista de localizaciones
                const indiceLocalización = localizacionesConMaquina.findIndex(loc => loc.id === id);
                if (indiceLocalización !== -1) {
                    localizacionesConMaquina.splice(indiceLocalización, 1);
                }

                // Actualizar bandeja de máquinas
                renderizarBandejaMaquinas();

                await mostrarAlerta('success', 'Eliminado', `"${nombre}" ha sido eliminada del mapa.`);
            } catch (err) {
                console.error(err);
                await mostrarAlerta('error', 'Error', err.message || 'Error desconocido al eliminar.');
            }
        }

        async function rotarMaquina(id, nombre, el, naveId, csrf) {
            try {
                const rectCanon = {
                    x1: +el.dataset.x1,
                    y1: +el.dataset.y1,
                    x2: +el.dataset.x2,
                    y2: +el.dataset.y2
                };
                const ancho = rectCanon.x2 - rectCanon.x1 + 1;
                const alto = rectCanon.y2 - rectCanon.y1 + 1;
                const nuevoCanon = {
                    x1: rectCanon.x1,
                    y1: rectCanon.y1,
                    x2: rectCanon.x1 + alto - 1,
                    y2: rectCanon.y1 + ancho - 1
                };

                // Verificar que la nueva posición esté dentro de los límites
                if (nuevoCanon.x2 > colsCanon || nuevoCanon.y2 > filasCanon) {
                    await mostrarAlerta('warning', 'No permitido', 'La rotación excede los límites del mapa.');
                    return;
                }

                // Liberar temporalmente la ocupación actual para la verificación
                liberarRect(appState.ocupadas, rectCanon);

                // Verificar excluyendo mi id
                const verRes = await conTimeout(fetch(window.routes.verificar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        ...nuevoCanon,
                        nave_id: naveId,
                        excluir_id: id
                    })
                }), 8000);
                const ver = await verRes.json().catch(() => ({}));

                if (!verRes.ok) {
                    // Restaurar ocupación original en caso de error
                    ocuparRect(appState.ocupadas, rectCanon);
                    await mostrarAlerta('error', 'Error', ver?.message || 'Error en verificación.');
                    return;
                }

                if (ver.existe) {
                    // Restaurar ocupación original
                    ocuparRect(appState.ocupadas, rectCanon);
                    await mostrarAlerta('warning', 'No permitido', 'La rotación solapa con otra localización.');
                    return;
                }

                // PUT con timeout
                const updRes = await conTimeout(fetch(`${window.routes.updateBase}/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(nuevoCanon)
                }), 8000);
                const upd = await updRes.json().catch(() => ({}));

                if (!updRes.ok || !upd?.success) {
                    // Restaurar ocupación original en caso de error
                    ocuparRect(appState.ocupadas, rectCanon);
                    await mostrarAlerta('error', 'Error', upd?.message || 'No se pudo rotar.');
                    return;
                }

                // Actualizar ocupación con la nueva posición
                ocuparRect(appState.ocupadas, nuevoCanon);

                // Actualizar DOM
                el.dataset.x1 = nuevoCanon.x1;
                el.dataset.y1 = nuevoCanon.y1;
                el.dataset.x2 = nuevoCanon.x2;
                el
                    .dataset.y2 = nuevoCanon.y2;

                const rectRender = aRender(nuevoCanon);
                const cajaPx = rectCeldasAPx(rectRender, {
                    columnas,
                    filas
                }, appState.rectGrid);
                el.style.left = `${cajaPx.left}px`;
                el.style.top = `${cajaPx.top}px`;
                el
                    .style.width = `${cajaPx.width}px`;
                el.style.height = `${cajaPx.height}px`;

                // Actualizar etiqueta si existe
                const etiqueta = elementos.overlays.querySelector(`[data-clave="${el.dataset.clave}-label"]`);
                if (etiqueta) {
                    etiqueta.style.left = (cajaPx.left + cajaPx.width / 2) + 'px';
                    etiqueta.style.top = cajaPx.top + 'px';
                }

                // Actualizar en el array de localizaciones
                const localizacion = localizacionesConMaquina.find(loc => loc.id === id);
                if (localizacion) {
                    localizacion.x1 = nuevoCanon.x1;
                    localizacion.y1 = nuevoCanon.y1;
                    localizacion.x2 = nuevoCanon.x2;
                    localizacion.y2 = nuevoCanon.y2;
                }

                await mostrarAlerta('success', 'Ok', `"${nombre}" girada 90°`);
            } catch (error) {
                console.error('Error en rotarMaquina:', error);
                await mostrarAlerta('error', 'Error', 'Ocurrió un error al rotar la máquina.');
            }
        }

        function onOverlayMouseDown(e) {
            if (e.target.closest('.overlay-btn')) return; // no arrancar drag si pulsas los botones

            // Prevenir arrastre si ya hay uno en curso
            if (appState.dragEstado) return;

            const cont = e.currentTarget;

            // Verificar que el elemento tenga los datos necesarios
            if (!cont.dataset.id || !cont.dataset.x1 || !cont.dataset.y1 || !cont.dataset.x2 || !cont.dataset.y2) {
                console.error('Elemento sin datos necesarios para arrastre:', cont);
                return;
            }

            const rectCanon = {
                x1: +cont.dataset.x1,
                y1: +cont.dataset.y1,
                x2: +cont.dataset.x2,
                y2: +cont.dataset.y2
            };

            appState.dragEstado = {
                id: +cont.dataset.id,
                nombre: cont.dataset.nombre || '',
                ancho: rectCanon.x2 - rectCanon.x1 + 1,
                alto: rectCanon.y2 - rectCanon.y1 + 1,
                rectCanonIni: rectCanon,
                elemento: cont
            };

            cont.classList.add('arrastrando');

            const rRender = aRender(rectCanon);
            ponerSombraEn(rRender.x1, rRender.y1, appState.dragEstado.ancho, appState.dragEstado.alto);

            // libera su ocupación para validar en vivo
            liberarRect(appState.ocupadas, rectCanon);

            // Mostrar cuadrícula de ocupación durante el arrastre
            mostrarCuadriculaOcupacion();

            // Mostrar indicador de modo edición
            elementos.modoEdicion.classList.remove('hidden');

            document.addEventListener('mousemove', onDragMove);
            document.addEventListener('mouseup', onDragEnd);

            // Prevenir selección de texto durante el arrastre
            e.preventDefault();
        }

        function onDragMove(e) {
            if (!appState.dragEstado) return;
            const r = elementos.grid.getBoundingClientRect();
            const cw = r.width / columnas,
                ch = r.height / filas;
            const rx = Math.min(columnas, Math.max(1, Math.floor((e.clientX - r.left) / cw) + 1));
            const ryT = Math.min(filas, Math.max(1, Math.floor((e.clientY - r.top) / ch) + 1));
            const ry = filas - ryT + 1;
            const rxC = Math.min(Math.max(1, rx), columnas - appState.dragEstado.ancho + 1);
            const ryC = Math.min(Math.max(1, ry), filas - appState.dragEstado.alto + 1);
            ponerSombraEn(rxC, ryC, appState.dragEstado.ancho, appState.dragEstado.alto);
        }

        async function onDragEnd() {
            if (!appState.dragEstado) return;

            // Limpiar event listeners inmediatamente
            document.removeEventListener('mousemove', onDragMove);
            document.removeEventListener('mouseup', onDragEnd);

            const cont = appState.dragEstado.elemento || elementos.overlays.querySelector(
                `.area[data-id="${appState.dragEstado.id}"]`);
            cont?.classList.remove('arrastrando');

            // Verificar que tenemos una posición válida de la sombra
            if (!elementos.sombra.dataset.rx || !elementos.sombra.dataset.ry) {
                console.error('Posición de sombra no válida');
                detenerArrastre(false);
                return;
            }

            const rx = +elementos.sombra.dataset.rx,
                ry = +elementos.sombra.dataset.ry;
            const rectRender = {
                x1: rx,
                y1: ry,
                x2: rx + appState.dragEstado.ancho - 1,
                y2: ry + appState.dragEstado.alto - 1
            };
            const nuevoCanon = aCanon(rectRender);

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const naveId = (window.context && window.context.nave_id) ?? {{ $obraActualId ?? 'null' }};

            if (!csrf) {
                await mostrarAlerta('error', 'Error', 'Token CSRF no encontrado. Recarga la página.');
                detenerArrastre(false);
                return;
            }

            try {
                // verificar con timeout
                const verRes = await conTimeout(fetch(window.routes.verificar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        ...nuevoCanon,
                        nave_id: naveId,
                        excluir_id: appState.dragEstado.id
                    })
                }), 8000);
                const ver = await verRes.json().catch(() => ({}));
                if (!verRes.ok) throw new Error(ver?.message || 'Error en verificación.');
                if (ver.existe) {
                    ocuparRect(appState.ocupadas, appState.dragEstado.rectCanonIni);
                    elementos.sombra.classList.add('hidden');
                    ocultarCuadriculaOcupacion();
                    elementos.modoEdicion.classList.add('hidden');
                    await mostrarAlerta('warning', 'No permitido',
                        'La nueva posición solapa con otra localización.');
                    appState.dragEstado = null;
                    return;
                }

                // PUT con timeout
                const updRes = await conTimeout(fetch(`${window.routes.updateBase}/${appState.dragEstado.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(nuevoCanon)
                }), 8000);
                const upd = await updRes.json().catch(() => ({}));
                if (!updRes.ok || !upd?.success) throw new Error(upd?.message || 'No se pudo mover.');

                // DOM
                if (cont) {
                    cont.dataset.x1 = nuevoCanon.x1;
                    cont.dataset.y1 = nuevoCanon.y1;
                    cont.dataset.x2 = nuevoCanon.x2;
                    cont.dataset.y2 = nuevoCanon.y2;
                    const cajaPx = rectCeldasAPx(rectRender, {
                        columnas,
                        filas
                    }, appState.rectGrid);
                    cont.style.left = `${cajaPx.left}px`;
                    cont.style.top = `${cajaPx.top}px`;
                    cont.style.width = `${cajaPx.width}px`;
                    cont.style.height = `${cajaPx.height}px`;

                    // Actualizar etiqueta si existe
                    const etiqueta = elementos.overlays.querySelector(
                        `[data-clave="${cont.dataset.clave}-label"]`);
                    if (etiqueta) {
                        etiqueta.style.left = (cajaPx.left + cajaPx.width / 2) + 'px';
                        etiqueta.style.top = cajaPx.top + 'px';
                    }
                }
                ocuparRect(appState.ocupadas, nuevoCanon);
                elementos.sombra.classList.add('hidden');

                // Actualizar en el array de localizaciones
                const localizacion = localizacionesConMaquina.find(loc => loc.id === appState.dragEstado.id);
                if (localizacion) {
                    localizacion.x1 = nuevoCanon.x1;
                    localizacion.y1 = nuevoCanon.y1;
                    localizacion.x2 = nuevoCanon.x2;
                    localizacion.y2 = nuevoCanon.y2;
                }

                await mostrarAlerta('success', 'Actualizado',
                    `"${appState.dragEstado.nombre}" movida correctamente.`);
            } catch (err) {
                console.error('Error en onDragEnd:', err);
                ocuparRect(appState.ocupadas, appState.dragEstado.rectCanonIni);
                elementos.sombra.classList.add('hidden');
                await mostrarAlerta('error', 'Error', err.message ?? 'Error desconocido');
            } finally {
                // Ocultar cuadrícula de ocupación al finalizar el arrastre
                ocultarCuadriculaOcupacion();
                // Ocultar indicador de modo edición
                elementos.modoEdicion.classList.add('hidden');
                appState.dragEstado = null;
            }
        }

        // ===== Funciones de utilidad general =====

        // Función auxiliar para crear un timeout en promesas
        function conTimeout(promesa, tiempoMs = 10000) {
            return Promise.race([
                promesa,
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Operación timeout')), tiempoMs)
                )
            ]);
        }

        // Función auxiliar para mostrar alertas de manera segura
        async function mostrarAlerta(tipo, titulo, mensaje) {
            try {
                if (typeof Swal !== 'undefined' && Swal.fire) {
                    return await Swal.fire({
                        title: titulo,
                        text: mensaje,
                        icon: tipo,
                        confirmButtonText: 'OK',
                        allowOutsideClick: true,
                        allowEscapeKey: true
                    });
                } else {
                    console.warn(`SweetAlert2 no disponible. ${tipo.toUpperCase()}: ${titulo} - ${mensaje}`);
                    alert(`${titulo}: ${mensaje}`);
                    return {
                        isConfirmed: true
                    };
                }
            } catch (error) {
                console.error('Error al mostrar alerta:', error);
                alert(`${titulo}: ${mensaje}`);
                return {
                    isConfirmed: true
                };
            }
        }

        function actualizarHUD(e) {
            const r = elementos.grid.getBoundingClientRect();
            const cw = r.width / columnas,
                ch = r.height / filas;
            const rx = Math.min(columnas, Math.max(1, Math.floor((e.clientX - r.left) / cw) + 1));
            const ryT = Math.min(filas, Math.max(1, Math.floor((e.clientY - r.top) / ch) + 1));
            const ry = filas - ryT + 1;
            elementos.hudPos.textContent = `(${rx}, ${ry})`;
        }

        function manejarTeclas(e) {
            if (e.key === 'Escape') detenerArrastre(false);
        }

        // Función para forzar la creación de herramientas en todas las máquinas
        function forzarCreacionHerramientas() {
            const maquinasEnMapa = elementos.overlays.querySelectorAll('.area.tipo-maquina[data-id]');

            maquinasEnMapa.forEach(maquina => {
                if (maquina.dataset.id) {
                    crearHerramientasOverlay(maquina);
                }
            });
        }

        // ===== Inicialización =====
        (function VistaCreate() {
            // Verificar que SweetAlert2 esté disponible
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 no está disponible. Asegúrate de que esté incluido en la página.');
                alert('Error: SweetAlert2 no está disponible. Recarga la página.');
                return;
            }

            inicializarVista();

            // Configurar event listeners para los botones del modal
            elementos.btnX.addEventListener('click', cerrarColocador);
            elementos.btnR.addEventListener('click', girarHuella);
            elementos.btnGo.addEventListener('click', iniciarArrastreDesdeModal);

            // Event listeners globales
            elementos.grid.addEventListener('mousemove', actualizarHUD);
            document.addEventListener('keydown', manejarTeclas);
            elementos.overlays.addEventListener('click', manejarClicOverlay);

            // Forzar creación de herramientas después de un pequeño delay
            setTimeout(forzarCreacionHerramientas, 100);
        })();
    </script>
</x-app-layout>
