<x-app-layout>
    <x-slot name="title">Mapa de Ubicaciones (22 x 115 m)</x-slot>

    <div class="h-screen w-screen flex flex-col">
        <div class="p-2 bg-white z-10">
            <a href="{{ route('localizaciones.create') }}"
                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition inline-block">
                Crear Nueva Localización
            </a>

            <div class="text-gray-800 font-semibold">
                Posición actual: <span id="posicionActual">—</span>
            </div>
        </div>

        <div id="grid-container">
            <div id="grid"></div>
        </div>

    </div>

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
        }

        #grid-container {
            padding: 1rem;
            box-sizing: border-box;
            height: 100%;
            width: 100%;
            display: flex;
            align-items: center;
        }

        #grid {
            display: grid;
            border: 1px solid #ccc;
            width: 95%;
            /* << reducimos el ancho al 50% */
            height: auto;
            /* se ajusta según el contenido */
            aspect-ratio: calc(115 / 22);
            /* mantiene proporción de la nave completa */
        }


        .cell {
            aspect-ratio: 1;
            width: 100%;
            background-color: white;
            border: 1px solid #ddd;
            cursor: pointer;
            user-select: none;
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


        /* Vista escritorio: horizontal */
        @media (min-width: 768px) {
            #grid {
                grid-template-columns: repeat(115, 1fr);
                grid-template-rows: repeat(22, 1fr);
            }
        }

        /* Vista móvil: vertical */
        @media (max-width: 767px) {
            #grid {
                grid-template-columns: repeat(22, 1fr);
                grid-template-rows: repeat(115, 1fr);
            }
        }
    </style>

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

        const filas = 22;
        const columnas = 115;


        for (let y = 1; y <= filas; y++) {
            for (let x = 1; x <= columnas; x++) {
                const cell = document.createElement('div');
                cell.className = 'cell';
                cell.dataset.coord = `${x},${y}`;
                cell.dataset.x = x;
                cell.dataset.y = y;

                // Mostrar la posición en tiempo real
                cell.addEventListener('mousemove', () => {
                    posicionActual.textContent = `(${x}, ${y})`;
                });

                // Inicia arrastre
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


                // Selección dinámica durante arrastre
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
            // Y AQUÍ, UNA VEZ TODO ESTÁ PINTADO, PINTAS LAS LOCALIZACIONES
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
        }

        // Al salir del grid
        grid.addEventListener('mouseleave', () => {
            posicionActual.textContent = '—';
            isDragging = false;
        });

        // Al soltar el clic
        document.addEventListener('mouseup', () => {
            if (!isDragging || areaTemporal.size === 0) return;
            isDragging = false;
            Swal.fire({
                title: 'Datos de la ubicación',
                html: `
        <label for="tipo" class="block text-left mb-1">Tipo:</label>
        <select id="tipo" class="swal2-input">
            <option value="material">Tipo Material</option>
            <option value="maquina">Tipo Máquina</option>
            <option value="transitable">Tipo Transitable</option>
        </select>
        <label for="seccion" class="block text-left mt-3 mb-1">Sección:</label>
        <input id="seccion" class="swal2-input" placeholder="Ej. A1, B2...">
        <label for="nombre" class="block text-left mt-3 mb-1">Nombre de la localización:</label>
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

                areaTemporal = new Set(); // limpiar la selección temporal
            });


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

            // Eliminar celdas que salieron del rectángulo
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

            // Añadir nuevas celdas seleccionadas
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
                tipo: tipo,
                seccion: seccion,
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
    </script>

</x-app-layout>
