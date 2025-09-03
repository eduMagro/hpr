<x-app-layout>
    <x-slot name="title">Mapa de Ubicaciones</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar mapa de {{ $obraActiva->obra ?? 'localizaciones' }}
        </h2>
    </x-slot>

    {{-- Menús de navegación --}}
    <x-menu.localizaciones.menu-localizaciones-vistas :obra-actual-id="$obraActualId ?? null" route-index="localizaciones.index"
        route-create="localizaciones.create" />

    <x-menu.localizaciones.menu-localizaciones-naves :obras="$obras" :obra-actual-id="$obraActualId ?? null" />

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
            background-color: rgba(255, 165, 0, 0.28) !important;
            outline: 2px dashed #f97316;
        }

        .preview.ok {
            background-color: rgba(34, 197, 94, 0.25) !important;
            outline: 2px dashed #22c55e;
        }

        .preview.bad {
            background-color: rgba(239, 68, 68, 0.25) !important;
            outline: 2px dashed #ef4444;
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
        // Rutas para verificación
        window.routes = {
            verificar: "{{ route('localizaciones.verificar') }}"
        };

        // Contexto de la nave actual
        window.context = {
            nave_id: {{ $obraActualId ?? 'null' }}
        };

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

        // Mostrar sombra en movimiento con verificación en tiempo real
        async function mostrarSombra(e) {
            limpiarSombra();

            const target = e.target.closest('.cell');
            if (!target || !localizacionSeleccionada) return;

            const x = parseInt(target.dataset.x);
            const y = parseInt(target.dataset.y);

            const ancho = localizacionSeleccionada.x2 - localizacionSeleccionada.x1;
            const alto = localizacionSeleccionada.y2 - localizacionSeleccionada.y1;

            // Dibujar la sombra
            for (let dx = 0; dx <= ancho; dx++) {
                for (let dy = 0; dy <= alto; dy++) {
                    const sombra = celdas.find(c => c.dataset.coord === `${x + dx},${y + dy}`);
                    if (sombra) {
                        sombra.classList.add('preview');
                        sombraActual.push(sombra);
                    }
                }
            }

            // Verificar si la posición es válida
            const esValida = await verificarPosicion(x, y, x + ancho, y + alto, localizacionSeleccionada.id);

            // Aplicar clase según validación
            sombraActual.forEach(cell => {
                if (esValida) {
                    cell.classList.add('ok');
                    cell.classList.remove('bad');
                } else {
                    cell.classList.add('bad');
                    cell.classList.remove('ok');
                }
            });
        }

        // Función para verificar posición con el servidor
        async function verificarPosicion(x1, y1, x2, y2, excluirId) {
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const naveId = window.context?.nave_id;

                if (!csrf) {
                    console.warn('No se encontró el token CSRF');
                    return false;
                }

                if (!naveId) {
                    console.warn('No hay nave_id disponible');
                    return false;
                }

                const response = await fetch(window.routes.verificar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        x1: x1,
                        y1: y1,
                        x2: x2,
                        y2: y2,
                        nave_id: naveId,
                        excluir_id: excluirId
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error en verificación:', response.status, errorText);
                    return false;
                }

                const data = await response.json();
                console.log('Respuesta de verificación:', data);
                return !data.existe; // Si no existe conflicto, es válida
            } catch (error) {
                console.error('Error al verificar posición:', error);
                return false;
            }
        }

        function limpiarSombra() {
            sombraActual.forEach(cell => {
                cell.classList.remove('preview', 'ok', 'bad');
            });
            sombraActual = [];
        }

        grid.addEventListener('mouseup', async (e) => {
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

            // Verificar si la posición es válida antes de mostrar el diálogo
            const esValida = await verificarPosicion(newX1, newY1, newX2, newY2, localizacionSeleccionada.id);

            if (!esValida) {
                Swal.fire({
                    title: 'Posición no válida',
                    text: 'No se puede mover la máquina a esta posición porque hay un conflicto con otra localización.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                limpiarSombra();
                localizacionSeleccionada = null;
                return;
            }

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
