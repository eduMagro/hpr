<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Movimientos') }}
        </h2>
    </x-slot>

    <div class="container mt-5">

        <!-- Formulario de movimiento -->
        <div class="card shadow-lg border-0">
            <div class="card-header bg-primary text-white text-center">
                <h2>Crear Movimiento de Material</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('movimientos.store') }}" method="POST" id="form-movimiento">
                    @csrf

                    <!-- Input QR o manual -->
                    <div class="form-group mb-4">
                        <label for="producto_id" class="form-label fw-bold">Producto (QR)</label>
                        <input type="text" name="producto_id" id="producto_id" class="form-control mb-3"
                            placeholder="Escanea o introduce el código del producto" value="{{ old('producto_id') }}"
                            onfocus="this.select()">
                    </div>

                    <!-- Campo oculto con ID de localización -->
                    <input type="hidden" name="localizacion_id" id="localizacion_id">

                    <!-- Visualización del nombre o ID de ubicación -->
                    <div class="form-group mb-4">
                        <label class="form-label fw-bold">Ubicación Seleccionada:</label>
                        <div id="ubicacionElegida" class="text-success fw-bold">Ninguna seleccionada</div>
                    </div>

                    <!-- Botón de envío -->
                    <div class="d-grid">
                        <button type="submit" id="submit-btn" class="btn btn-success btn-lg">
                            Registrar Movimiento
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-footer text-center text-muted">
                <small>El producto puede moverse a otra ubicación o a una máquina, pero no ambos.</small>
            </div>
        </div>

        <!-- Contenedor visual del grid -->
        <div id="grid-container" class="mb-4">
            <div id="grid" class="border w-100 aspect-[115/22] grid"></div>
        </div>
    </div>

    <!-- ESTILOS -->
    <style>
        .cell {
            aspect-ratio: 1;
            width: 100%;
            background-color: white;
            border: 1px solid #ccc;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .cell:hover {
            background-color: #f0fdf4;
        }

        .tipo-material {
            background-color: #15803d;
        }

        .tipo-maquina {
            background-color: #1d4ed8;
        }

        .tipo-transitable {
            background-color: #6b7280;
        }

        .seleccionada {
            outline: 3px solid red;
        }

        #grid {
            display: grid;
            grid-template-columns: repeat(115, 1fr);
            grid-template-rows: repeat(22, 1fr);
        }
    </style>

    <!-- SCRIPTS -->
    <script>
        const grid = document.getElementById('grid');
        const localizaciones = @json($localizaciones);
        const celdas = [];
        let celdaSeleccionada = null;
        const celdaMap = {};

        // Crear y mapear celdas
        const fragment = document.createDocumentFragment();
        for (let y = 1; y <= 22; y++) {
            for (let x = 1; x <= 115; x++) {
                const div = document.createElement('div');
                const coord = `${x},${y}`;
                div.classList.add('cell');
                div.dataset.coord = coord;
                celdaMap[coord] = div;
                fragment.appendChild(div);
                celdas.push(div);
            }
        }
        grid.appendChild(fragment);

        // Pintar localizaciones existentes
        for (const loc of localizaciones) {
            for (let x = loc.x1; x <= loc.x2; x++) {
                for (let y = loc.y1; y <= loc.y2; y++) {
                    const key = `${x},${y}`;
                    const cell = celdaMap[key];
                    if (cell) {
                        cell.classList.add(`tipo-${loc.tipo}`);
                        cell.dataset.localizacionId = loc.id;
                        cell.dataset.localizacionNombre = loc.localizacion;
                    }
                }
            }
        }

        // Al hacer clic en una celda
        grid.addEventListener('click', (e) => {
            const cell = e.target;
            // Validaciones
            if (!cell.classList.contains('cell') ||
                !cell.dataset.localizacionId ||
                cell.classList.contains('tipo-transitable')) return;

            // Desmarcar anterior
            if (celdaSeleccionada)
                celdaSeleccionada.classList.remove('seleccionada');

            // Marcar nueva
            cell.classList.add('seleccionada');
            celdaSeleccionada = cell;

            const id = cell.dataset.localizacionId;
            const nombre = cell.dataset.localizacionNombre || `ID ${id}`;

            document.getElementById('ubicacionElegida').textContent = nombre;
            document.getElementById('localizacion_id').value = id;
        });

        // Variables para arrastre
        let isDragging = false;
        let startX = null,
            startY = null,
            endX = null,
            endY = null;
        let areaTemporal = new Set();

        // Inicia selección
        document.addEventListener('mousedown', (e) => {
            if (!e.target.classList.contains('cell')) return;
            const [x, y] = e.target.dataset.coord.split(',').map(Number);
            startX = endX = x;
            startY = endY = y;
            isDragging = true;
            areaTemporal = new Set([`${x},${y}`]);
        });

        // Actualiza selección mientras se arrastra
        document.addEventListener('mouseover', (e) => {
            if (!isDragging || !e.target.classList.contains('cell')) return;
            const [x, y] = e.target.dataset.coord.split(',').map(Number);
            endX = x;
            endY = y;

            areaTemporal.clear();
            const minX = Math.min(startX, endX),
                maxX = Math.max(startX, endX);
            const minY = Math.min(startY, endY),
                maxY = Math.max(startY, endY);

            for (let cx = minX; cx <= maxX; cx++) {
                for (let cy = minY; cy <= maxY; cy++) {
                    areaTemporal.add(`${cx},${cy}`);
                }
            }
        });

        // Al soltar el botón: verificar si la zona ya existe
        document.addEventListener('mouseup', () => {
            console.log('[DEBUG] mouseup lanzado');
            if (!isDragging || areaTemporal.size === 0) {
                console.log('No se está arrastrando o no hay área temporal seleccionada');
                return;
            }

            isDragging = false;

            const minX = Math.min(startX, endX);
            const maxX = Math.max(startX, endX);
            const minY = Math.min(startY, endY);
            const maxY = Math.max(startY, endY);

            console.log('Área seleccionada:', {
                x1: minX,
                y1: minY,
                x2: maxX,
                y2: maxY
            });

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
                .then(res => {
                    console.log('Respuesta fetch recibida:', res);
                    return res.json();
                })
                .then(data => {
                    console.log('Respuesta parseada:', data);

                    if (data.existe) {
                        console.log('¡Localización existente encontrada!', data.localizacion);

                        Swal.fire({
                            title: 'Esta ubicación ya existe',
                            text: `¿Quieres editar la localización "${data.localizacion.localizacion}" de tipo "${data.localizacion.tipo}"?`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Editar',
                            cancelButtonText: 'Cancelar'
                        }).then(result => {
                            if (result.isConfirmed) {
                                console.log('El usuario eligió editar.');
                                abrirModalEdicion(data.localizacion);
                            } else {
                                console.log('El usuario canceló la edición.');
                            }
                        });
                    } else {
                        console.log('No existe ninguna localización exacta para esta área.');
                        abrirModalCreacion(minX, minY, maxX, maxY);
                    }
                })
                .catch(error => {
                    console.error('Error al verificar localización:', error);
                });

            areaTemporal.clear();
        });

        // Modal para nueva localización
        function abrirModalCreacion(x1, y1, x2, y2) {
            Swal.fire({
                title: 'Nueva localización',
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
                    guardarConTipo(tipo, seccion, nombre, x1, y1, x2, y2);
                }
            });
        }

        // Guardar localización nueva
        function guardarConTipo(tipo, seccion, nombre, x1, y1, x2, y2) {
            const localizacion = {
                x1,
                y1,
                x2,
                y2,
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
                    Swal.fire('Guardado', data.message, 'success').then(() => {
                        location.reload(); // Refrescar para ver la nueva zona
                    });
                })
                .catch(err => {
                    Swal.fire('Error', err.message, 'error');
                });
        }
    </script>
</x-app-layout>
