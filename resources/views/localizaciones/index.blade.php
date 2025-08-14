<x-app-layout>
    <x-slot name="title">Mapa de Ubicaciones</x-slot>

    <style>
        html {
            scroll-behavior: smooth;
            /* desplazamiento suave */
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        #grid-container {
            transform: scale(1);
            /* 1 = 100%, 1.2 = 120% zoom in, 0.8 = alejar */
            transform-origin: top left;
            /* para que no se desplace raro */
        }

        #grid {
            display: grid;
            grid-template-columns: repeat(22, 1fr);
            /* no forces row height, just repeat */
            grid-auto-rows: 1fr;
            /* deja que cada celda defina su altura */
            width: 100%;
            /* en lugar de height fija, deja que crezca */
            max-width: 90vw;
            border: 1px solid #ccc;
        }

        .cell {
            aspect-ratio: 1 / 1;
            /* mantiene 1x1 */
            width: 100%;
            background-color: white;
            border: 1px solid #ddd;
        }

        #sectores {
            display: grid;
            grid-template-rows: repeat(7, 1fr);
            width: 80px;
            border-left: 1px solid #ccc;
        }

        #sectores div {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            border-top: 1px solid #ccc;
        }

        #sectores div:nth-child(1) {
            background: #15803d;
            color: white;
        }

        #sectores div:nth-child(2) {
            background: #16a34a;
            color: white;
        }

        #sectores div:nth-child(3) {
            background: #22c55e;
            color: white;
        }

        #sectores div:nth-child(4) {
            background: #4ade80;
        }

        #sectores div:nth-child(5) {
            background: #86efac;
        }

        #sectores div:nth-child(6) {
            background: #bbf7d0;
        }

        #sectores div:nth-child(7) {
            background: #dcfce7;
        }

        .selected {
            background-color: green;
        }

        .tipo-material {
            background-color: #15803d !important;
        }

        .tipo-maquina {
            background-color: #1d4ed8 !important;
        }

        .tipo-transitable {
            background-color: #6b7280 !important;
        }

        .paquete-preview {
            background-color: rgba(255, 165, 0, 0.6) !important;
            /* naranja translúcido */
            border: 2px solid orange;
        }

        .paquete-guardado {
            background-color: rgba(57, 78, 183, 0.8) !important;
            border: 2px solid darkblue;
        }
    </style>

    <div class="w-screen flex flex-col">
        <div class="hidden md:block p-2 bg-white z-10">
            <a href="{{ route('localizaciones.create') }}"
                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition inline-block">Crear Nueva
                Localización</a>
            <a href="{{ route('localizaciones.editarMapa') }}"
                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition inline-block">Editar
                Localización</a>
            <div class="fixed top-2 left-2 z-50 bg-white/90 px-3 py-1 rounded shadow font-semibold text-gray-800">
                Posición actual: <span id="posicionActual">—</span>
            </div>
        </div>

        <!-- GRID Y SECTORES -->
        <div class="p-2 flex gap-2">
            <input id="codigoPaquete" type="text" class="border px-2 py-1 rounded" placeholder="Código de paquete">
            <button onclick="dibujarPaquete()" class="bg-blue-600 text-white px-3 py-1 rounded">📦 Dibujar
                paquete</button>
        </div>

        <div id="zoom-wrapper" class="flex origin-top-left">

            <div id="grid-container" class="relative" style="width:75%;">
                <div id="grid"></div>
                <!-- Marcadores invisibles de cada sector -->
                <div id="sector-markers">
                    <div id="sector7-marker" style="position:absolute;top:0;height:1px;width:1px;"></div>
                    <div id="sector6-marker" style="position:absolute;top:calc(100%/7*1);height:1px;width:1px;"></div>
                    <div id="sector5-marker" style="position:absolute;top:calc(100%/7*2);height:1px;width:1px;"></div>
                    <div id="sector4-marker" style="position:absolute;top:calc(100%/7*3);height:1px;width:1px;"></div>
                    <div id="sector3-marker" style="position:absolute;top:calc(100%/7*4);height:1px;width:1px;"></div>
                    <div id="sector2-marker" style="position:absolute;top:calc(100%/7*5);height:1px;width:1px;"></div>
                    <div id="sector1-marker" style="position:absolute;top:calc(100%/7*6);height:1px;width:1px;"></div>
                </div>
            </div>
            <div id="sectores"></div>
        </div>

        <!-- LEYENDA NAVEGACIÓN -->
        <div id="leyendaSectores"
            class="hidden md:block fixed top-1/2 right-4 -translate-y-1/2 bg-white/90 shadow-lg rounded p-2 space-y-2 z-50">
            <a href="#sector7-marker"
                class="block bg-green-700 hover:bg-green-800 px-3 py-1 rounded text-xs font-bold w-full">Sector 7</a>
            <a href="#sector6-marker"
                class="block bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-xs font-bold w-full">Sector 6</a>
            <a href="#sector5-marker"
                class="block bg-green-500 hover:bg-green-600 px-3 py-1 rounded text-xs font-bold w-full">Sector 5</a>
            <a href="#sector4-marker"
                class="block bg-green-400 hover:bg-green-500 px-3 py-1 rounded text-xs font-bold w-full">Sector 4</a>
            <a href="#sector3-marker"
                class="block bg-green-300 hover:bg-green-400 text-white px-3 py-1 rounded text-xs font-bold w-full">Sector
                3</a>
            <a href="#sector2-marker"
                class="block bg-green-200 hover:bg-green-300 text-white px-3 py-1 rounded text-xs font-bold w-full">Sector
                2</a>
            <a href="#sector1-marker"
                class="block bg-green-100 hover:bg-green-200 text-white px-3 py-1 rounded text-xs font-bold w-full">Sector
                1</a>
            <div class="flex gap-2 p-2">
                <button onclick="zoomOut()" class="bg-gray-300 px-3 py-1 rounded">➖ Alejar</button>
                <button onclick="zoomIn()" class="bg-gray-300 px-3 py-1 rounded">➕ Acercar</button>
                <button onclick="resetZoom()" class="bg-gray-300 px-3 py-1 rounded">🔄 Reset</button>
            </div>

        </div>

    </div>
    <script>
        const paquetesEnMapa = @json($paquetesEnMapa);
    </script>
    <script>
        const grid = document.getElementById('grid');
        const seleccionadas = new Set();
        const posicionActual = document.getElementById('posicionActual');
        let startX = null;
        let startY = null;
        let isDragging = false;
        let endX = null;
        let endY = null;
        let areaTemporal = new Set();
        const celdas = [];
        const cellsMap = {};
        const columnas = 22;
        const filas = 115;

        for (let y = filas; y >= 1; y--) {
            for (let x = 1; x <= columnas; x++) {
                const cell = document.createElement('div');
                cell.className = 'cell';
                cell.dataset.coord = `${x},${y}`;
                cell.dataset.x = x;
                cell.dataset.y = y;

                cell.addEventListener('mousemove', () => {
                    posicionActual.textContent = `(${x}, ${y})`;
                });

                cell.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    isDragging = true;
                    startX = parseInt(cell.dataset.x);
                    startY = parseInt(cell.dataset.y);
                    endX = startX;
                    endY = startY;
                    areaTemporal = new Set();
                    seleccionarRectangulo(startX, startY, startX, startY);
                });

                cell.addEventListener('mouseenter', () => {
                    if (isDragging) {
                        endX = parseInt(cell.dataset.x);
                        endY = parseInt(cell.dataset.y);
                        seleccionarRectangulo(startX, startY, endX, endY);
                    }
                });

                grid.appendChild(cell);
                celdas.push(cell);

                cellsMap[`${x},${y}`] = cell;
            }
        }

        const localizaciones = @json($localizaciones);
        for (const loc of localizaciones) {
            for (let x = loc.x1; x <= loc.x2; x++) {
                for (let y = loc.y1; y <= loc.y2; y++) {
                    const key = `${x},${y}`;
                    const cell = cellsMap[key];
                    if (cell) {
                        cell.classList.add('selected');
                        cell.classList.add(`tipo-${loc.tipo}`);
                    }
                }
            }
        }
        // después de pintar localizaciones
        for (const paquete of paquetesEnMapa) {
            for (let x = paquete.x1; x <= paquete.x2; x++) {
                for (let y = paquete.y1; y <= paquete.y2; y++) {
                    const key = `${x},${y}`;
                    const cell = cellsMap[key];
                    if (cell) {
                        cell.classList.add('paquete-guardado'); // usa el mismo estilo o crea uno específico
                    }
                }
            }
        }

        grid.addEventListener('mouseleave', () => {
            posicionActual.textContent = '—';
            isDragging = false;
        });

        function seleccionarRectangulo(x1, y1, x2, y2) {
            const nuevaArea = new Set();
            const minX = Math.min(x1, x2);
            const maxX = Math.max(x1, x2);
            const minY = Math.min(y1, y2);
            const maxY = Math.max(y1, y2);

            for (let cx = minX; cx <= maxX; cx++) {
                for (let cy = minY; cy <= maxY; cy++) {
                    const key = `${cx},${cy}`;
                    nuevaArea.add(key);
                }
            }
            for (const key of areaTemporal) {
                if (!nuevaArea.has(key)) {
                    const [cx, cy] = key.split(',').map(Number);
                    const cell = cellsMap[key];
                    if (cell && seleccionadas.has(key)) {
                        seleccionadas.delete(key);
                        cell.classList.remove('selected');
                    }
                }
            }
            for (const key of nuevaArea) {
                if (!areaTemporal.has(key)) {
                    const [cx, cy] = key.split(',').map(Number);
                    const cell = cellsMap[key];
                    if (cell && !seleccionadas.has(key)) {
                        seleccionadas.add(key);
                        cell.classList.add('selected');
                    }
                }
            }
            areaTemporal = nuevaArea;
        }

        function guardarConTipo(tipo, seccion, nombre) {
            const minX = Math.min(startX, endX);
            const maxX = Math.max(startX, endX);
            const minY = Math.min(startY, endY);
            const maxY = Math.max(startY, endY);

            const localizacion = {
                x1: minX,
                y1: minY,
                x2: maxX,
                y2: maxY,
                tipo,
                seccion,
                localizacion: nombre
            };

            fetch("{{ route('localizaciones.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(localizacion)
                })
                .then(async res => {
                    if (!res.ok) {
                        const error = await res.json();
                        throw new Error(error.message || 'Error desconocido');
                    }
                    return res.json();
                })
                .then(data => {
                    Swal.fire('Guardado', data.message, 'success');
                })
                .catch(err => {
                    Swal.fire('Error', err.message, 'error');
                });
        }

        // Crear los sectores visuales a la derecha
        const sectoresContainer = document.getElementById('sectores');
        const nombresSectores = ['Sector 7', 'Sector 6', 'Sector 5', 'Sector 4', 'Sector 3', 'Sector 2', 'Sector 1'];
        nombresSectores.forEach(n => {
            const div = document.createElement('div');
            div.textContent = n;
            sectoresContainer.appendChild(div);
        });
    </script>
    {{--  ESTO ES PARA HACER ZOOM --}}
    <script>
        let zoom = 1;

        function applyZoom() {
            const wrapper = document.getElementById('zoom-wrapper');
            wrapper.style.transform = `scale(${zoom})`;
            wrapper.style.transformOrigin = 'top left'; // importante para que no se desplace raro
        }

        function zoomIn() {
            zoom += 0.1;
            applyZoom();
        }

        function zoomOut() {
            zoom = Math.max(0.1, zoom - 0.1);
            applyZoom();
        }

        function resetZoom() {
            zoom = 1;
            applyZoom();
        }
    </script>
    {{-- CARGAR TAMAÑO PAQUETE --}}
    <script>
        function cargarPaquete() {
            const codigo = document.getElementById('codigoPaquete').value.trim();
            if (!codigo) {
                Swal.fire('Error', 'Introduce un código', 'error');
                return;
            }

            fetch("{{ route('paquetes.tamaño') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        codigo
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        Swal.fire('Error', data.error, 'error');
                        return;
                    }

                    // 🔥 data.ancho y data.longitud ya son en metros (celdas)
                    crearPaqueteVisual(data.codigo, data.ancho, data.longitud);
                })
                .catch(err => Swal.fire('Error', err.message, 'error'));
        }
    </script>
    <script>
        async function dibujarPaquete() {
            const codigo = document.getElementById('codigoPaquete').value.trim();
            if (!codigo) {
                Swal.fire('Error', 'Introduce un código', 'error');
                return;
            }

            // limpia cualquier paquete anterior
            limpiarPreview();

            try {
                const res = await fetch("{{ route('paquetes.tamaño') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        codigo
                    })
                });

                if (!res.ok) throw new Error('No se pudo obtener el tamaño');
                const data = await res.json();
                if (data.error) {
                    Swal.fire('Error', data.error, 'error');
                    return;
                }

                // dimensiones en metros = celdas
                const ancho = Math.max(1, Math.round(data.ancho));
                const largo = Math.max(1, Math.round(data.longitud));

                Swal.fire({
                    title: 'Selecciona posición',
                    text: `Haz click en la celda donde quieres colocar la esquina superior izquierda (${ancho}×${largo})`,
                    icon: 'info'
                });

                // espera a que el usuario haga click en la cuadrícula
                prepararClickParaColocar(codigo, ancho, largo);

            } catch (e) {
                Swal.fire('Error', e.message, 'error');
            }
        }

        let codigoActual = null; // arriba del todo

        function prepararClickParaColocar(codigo, ancho, largo) {
            codigoActual = codigo; // guardamos el código para luego
            // quitar cualquier click anterior
            for (const cell of celdas) {
                cell.removeEventListener('click', cell._clickHandlerPaquete);
            }
            for (const cell of celdas) {
                cell._clickHandlerPaquete = () => {
                    const startX = parseInt(cell.dataset.x);
                    const startY = parseInt(cell.dataset.y);
                    pintarPaqueteEnCeldas(startX, startY, ancho, largo);
                };
                cell.addEventListener('click', cell._clickHandlerPaquete);
            }
        }


        function pintarPaqueteEnCeldas(startX, startY, ancho, largo) {
            limpiarPreview();

            for (let x = startX; x < startX + ancho; x++) {
                for (let y = startY; y < startY + largo; y++) {
                    const key = `${x},${y}`;
                    const celda = cellsMap[key];
                    if (celda) {
                        celda.classList.add('paquete-preview');
                    }
                }
            }

            const x1 = startX;
            const y1 = startY;
            const x2 = startX + ancho - 1;
            const y2 = startY + largo - 1;

            // ⚡️ Pedimos confirmación
            Swal.fire({
                title: '¿Colocar paquete aquí?',
                html: `Coordenadas: <b>${x1},${y1}</b> hasta <b>${x2},${y2}</b>`,
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    guardarLocalizacionPaquete(codigoActual, x1, y1, x2, y2);
                } else {
                    limpiarPreview();
                }
            });
        }


        function limpiarPreview() {
            for (const cell of celdas) {
                cell.classList.remove('paquete-preview');
            }
        }

        function guardarLocalizacionPaquete(codigo, x1, y1, x2, y2) {
            fetch(`/localizaciones-paquetes/${codigo}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        x1,
                        y1,
                        x2,
                        y2
                    })
                })
                .then(async res => {
                    const text = await res.text(); // lee como texto
                    console.log('Respuesta cruda:', text);
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    return JSON.parse(text);
                })
                .then(data => {
                    Swal.fire('Guardado', data.message, 'success');
                })
                .catch(err => {
                    console.error('Error al guardar:', err);
                    Swal.fire('Error', err.message, 'error');
                });

        }
    </script>

</x-app-layout>
