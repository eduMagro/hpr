<x-app-layout>
    <x-slot name="title">Mapa de Ubicaciones</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Localizaciones') }}
        </h2>
    </x-slot>

    <div class="h-screen w-screen flex flex-col">
        <div class="p-2 bg-white z-10">
            <div class="text-gray-800 font-semibold">
                Posición actual: <span id="posicionActual">—</span>
            </div>
        </div>

        <div id="grid-container">
            <div id="grid"></div>
        </div>
    </div>

    <style>
        #grid-container {
            padding: 1rem;
            height: 100%;
            width: 100%;
            display: flex;
            align-items: center;
        }

        #grid {
            display: grid;
            border: 1px solid #ccc;
            width: 95%;
            height: auto;
            aspect-ratio: calc(115 / 22);
        }

        .cell {
            aspect-ratio: 1;
            width: 100%;
            background-color: white;
            border: 1px solid #ddd;
            cursor: pointer;
            user-select: none;
        }

        .preview {
            background-color: rgba(0, 0, 0, 0.2) !important;
            outline: 2px dashed black;
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

        .borde-top {
            border-top: 2px solid black !important;
        }

        .borde-right {
            border-right: 2px solid black !important;
        }

        .borde-bottom {
            border-bottom: 2px solid black !important;
        }

        .borde-left {
            border-left: 2px solid black !important;
        }

        @media (min-width: 768px) {
            #grid {
                grid-template-columns: repeat(115, 1fr);
                grid-template-rows: repeat(22, 1fr);
            }
        }

        @media (max-width: 767px) {
            #grid {
                grid-template-columns: repeat(22, 1fr);
                grid-template-rows: repeat(115, 1fr);
            }
        }
    </style>

    <script>
        const grid = document.getElementById('grid');
        const posicionActual = document.getElementById('posicionActual');
        const celdas = [];
        const filas = 22;
        const columnas = 115;
        const localizaciones = @json($localizaciones);

        let localizacionSeleccionada = null;
        let sombraActual = [];

        // Crear celdas
        for (let y = 1; y <= filas; y++) {
            for (let x = 1; x <= columnas; x++) {
                const cell = document.createElement('div');
                cell.className = 'cell';
                cell.dataset.coord = `${x},${y}`;
                cell.dataset.x = x;
                cell.dataset.y = y;

                cell.addEventListener('mousemove', () => {
                    posicionActual.textContent = `(${x}, ${y})`;
                });

                grid.appendChild(cell);
                celdas.push(cell);
            }
        }

        // Pintar localizaciones
        for (const loc of localizaciones) {
            for (let x = loc.x1; x <= loc.x2; x++) {
                for (let y = loc.y1; y <= loc.y2; y++) {
                    const cell = celdas.find(c => c.dataset.coord === `${x},${y}`);
                    if (!cell) continue;

                    cell.classList.add('selected', `tipo-${loc.tipo}`);
                    cell.dataset.localizacionId = loc.id;
                    cell.dataset.localizacionNombre = loc.localizacion;
                    cell.dataset.localizacionTipo = loc.tipo;

                    if (loc.tipo === 'material') {
                        if (y === loc.y1) cell.classList.add('borde-top');
                        if (y === loc.y2) cell.classList.add('borde-bottom');
                        if (x === loc.x1) cell.classList.add('borde-left');
                        if (x === loc.x2) cell.classList.add('borde-right');
                    }

                    // Solo en la esquina superior izquierda activamos el movimiento
                    if (x === loc.x1 && y === loc.y1) {
                        cell.addEventListener('mousedown', (e) => {
                            e.preventDefault();
                            localizacionSeleccionada = {
                                ...loc
                            };
                            console.log(`🟢 Iniciando movimiento de "${loc.localizacion}"`);
                            grid.addEventListener('mousemove', mostrarSombra);
                        });
                    }
                }
            }
        }
        console.log('✅ Celdas creadas:', celdas.length); // Debería ser 22 x 115 = 2530
        console.log('✅ Localizaciones recibidas:', localizaciones);

        // Mostrar sombra en movimiento
        function mostrarSombra(e) {
            limpiarSombra();

            const target = e.target.closest('.cell');
            if (!target || !localizacionSeleccionada) return;

            const x = parseInt(target.dataset.x);
            const y = parseInt(target.dataset.y);

            const ancho = localizacionSeleccionada.x2 - localizacionSeleccionada.x1;
            const alto = localizacionSeleccionada.y2 - localizacionSeleccionada.y1;

            for (let dx = 0; dx <= ancho; dx++) {
                for (let dy = 0; dy <= alto; dy++) {
                    const sombra = celdas.find(c => c.dataset.coord === `${x + dx},${y + dy}`);
                    if (sombra) {
                        sombra.classList.add('preview');
                        sombraActual.push(sombra);
                    }
                }
            }
        }

        function limpiarSombra() {
            sombraActual.forEach(cell => cell.classList.remove('preview'));
            sombraActual = [];
        }

        grid.addEventListener('mouseup', (e) => {
            grid.removeEventListener('mousemove', mostrarSombra);

            const cell = e.target.closest('.cell');
            if (!cell || !localizacionSeleccionada) return;

            const x = parseInt(cell.dataset.x);
            const y = parseInt(cell.dataset.y);

            const ancho = localizacionSeleccionada.x2 - localizacionSeleccionada.x1;
            const alto = localizacionSeleccionada.y2 - localizacionSeleccionada.y1;

            const newX1 = x;
            const newY1 = y;
            const newX2 = x + ancho;
            const newY2 = y + alto;

            console.log(`📦 Nueva posición: (${newX1}, ${newY1}) → (${newX2}, ${newY2})`);
            Swal.fire({
                title: `Mover "${localizacionSeleccionada.localizacion}"`,
                text: `¿Confirmas moverla a (${newX1}, ${newY1})?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, mover',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    if (!localizacionSeleccionada) {
                        console.warn('❌ localizacionSeleccionada estaba vacía dentro del then');
                        return;
                    }

                    const id = localizacionSeleccionada.id;
                    console.log('📤 Enviando PUT con ID:', id);

                    fetch(`/localizaciones/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                _method: 'PUT',
                                x1: newX1,
                                y1: newY1,
                                x2: newX2,
                                y2: newY2
                            })
                        })
                        .then(async res => {
                            if (!res.ok) {
                                const error = await res.text();
                                throw new Error(error);
                            }
                            return res.json();
                        })
                        .then(data => {
                            console.log('✅ Movimiento confirmado:', data);
                            Swal.fire('Actualizada', data.message, 'success').then(() => location
                                .reload());
                        })
                        .catch(err => {
                            console.error('❌ Error al actualizar:', err);
                            Swal.fire('Error', err.message, 'error');
                        })
                        .finally(() => {
                            localizacionSeleccionada = null; // ✅ Ahora sí, se resetea después
                        });

                } else {
                    console.log('❌ Movimiento cancelado por el usuario');
                    localizacionSeleccionada = null;
                }
            });

        });

        grid.addEventListener('mouseleave', () => {
            posicionActual.textContent = '—';
            limpiarSombra();

        });
    </script>

</x-app-layout>
