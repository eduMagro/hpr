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
    </style>

    <div class="w-screen flex flex-col">
        <div class="p-2 bg-white z-10">
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
        <div class="flex">
            <div id="grid-container" class="border-r w-full md:w-1/2">
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
            <a href="#sector1-marker"
                class="block bg-green-100 hover:bg-green-200 px-3 py-1 rounded text-xs font-bold w-full">Sector 1</a>
            <a href="#sector2-marker"
                class="block bg-green-200 hover:bg-green-300 px-3 py-1 rounded text-xs font-bold w-full">Sector 2</a>
            <a href="#sector3-marker"
                class="block bg-green-300 hover:bg-green-400 px-3 py-1 rounded text-xs font-bold w-full">Sector 3</a>
            <a href="#sector4-marker"
                class="block bg-green-400 hover:bg-green-500 px-3 py-1 rounded text-xs font-bold w-full">Sector 4</a>
            <a href="#sector5-marker"
                class="block bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs font-bold w-full">Sector
                5</a>
            <a href="#sector6-marker"
                class="block bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-bold w-full">Sector
                6</a>
            <a href="#sector7-marker"
                class="block bg-green-700 hover:bg-green-800 text-white px-3 py-1 rounded text-xs font-bold w-full">Sector
                7</a>
        </div>

    </div>

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
            }
        }

        const localizaciones = @json($localizaciones);
        for (const loc of localizaciones) {
            for (let x = loc.x1; x <= loc.x2; x++) {
                for (let y = loc.y1; y <= loc.y2; y++) {
                    const key = `${x},${y}`;
                    const cell = celdas.find(c => c.dataset.coord === key);
                    if (cell) {
                        cell.classList.add('selected');
                        cell.classList.add(`tipo-${loc.tipo}`);
                    }
                }
            }
        }

        grid.addEventListener('mouseleave', () => {
            posicionActual.textContent = '—';
            isDragging = false;
        });

        document.addEventListener('mouseup', () => {
            if (!isDragging || areaTemporal.size === 0) return;
            isDragging = false;

            const minX = Math.min(startX, endX);
            const maxX = Math.max(startX, endX);
            const minY = Math.min(startY, endY);
            const maxY = Math.max(startY, endY);

            fetch("{{ route('localizaciones.verificar') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        x1: minX,
                        y1: minY,
                        x2: maxX,
                        y2: maxY
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.existe) {
                        Swal.fire({
                            title: 'Ya existe',
                            text: `Esta área ya pertenece a "${data.localizacion.localizacion}" (tipo ${data.localizacion.tipo})`,
                            icon: 'info'
                        });
                    } else {
                        Swal.fire({
                            title: 'Datos de la ubicación',
                            html: `
                            <label class="block text-left mb-1">Tipo:</label>
                            <select id="tipo" class="swal2-input">
                                <option value="material">Tipo Material</option>
                                <option value="maquina">Tipo Máquina</option>
                                <option value="transitable">Tipo Transitable</option>
                            </select>
                            <label class="block text-left mt-3 mb-1">Sección:</label>
                            <input id="seccion" class="swal2-input" placeholder="Ej. A1, B2...">
                            <label class="block text-left mt-3 mb-1">Nombre de la localización:</label>
                            <input id="nombre" class="swal2-input" placeholder="Ej. Máquina 5, Pasillo 3...">
                        `,
                            focusConfirm: false,
                            showCancelButton: true,
                            confirmButtonText: 'Crear',
                            cancelButtonText: 'Cancelar',
                            preConfirm: () => {
                                const tipo = document.getElementById('tipo').value;
                                const seccion = document.getElementById('seccion').value.trim();
                                const nombre = document.getElementById('nombre').value.trim();
                                if (!seccion || !nombre) {
                                    Swal.showValidationMessage('Sección y nombre son obligatorios');
                                    return false;
                                }
                                return {
                                    tipo,
                                    seccion,
                                    nombre
                                };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const {
                                    tipo,
                                    seccion,
                                    nombre
                                } = result.value;
                                guardarConTipo(tipo, seccion, nombre);
                            }
                        });
                    }
                })
                .catch(err => Swal.fire('Error', err.message, 'error'));

            areaTemporal.clear();
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
                    const cell = celdas.find(c => c.dataset.x == cx && c.dataset.y == cy);
                    if (cell && seleccionadas.has(key)) {
                        seleccionadas.delete(key);
                        cell.classList.remove('selected');
                    }
                }
            }
            for (const key of nuevaArea) {
                if (!areaTemporal.has(key)) {
                    const [cx, cy] = key.split(',').map(Number);
                    const cell = celdas.find(c => c.dataset.x == cx && c.dataset.y == cy);
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
</x-app-layout>
