<x-app-layout>
    <x-slot name="title">Planificación por Obra</x-slot>

    <div id="lista-trabajadores" class="p-4 bg-white border rounded shadow w-full mt-4">
        {{-- Filtro de búsqueda --}}
        <div class="mb-4">
            <input type="text" id="filtro-trabajadores" placeholder="Buscar trabajador..."
                class="w-full md:w-64 border border-gray-300 rounded px-3 py-2 text-sm focus:ring focus:ring-blue-300 focus:border-blue-500">
        </div>
        {{-- hpr servicios --}}
        <details class="mb-4" open>
            <summary class="cursor-pointer font-bold text-gray-800 mb-2">Trabajadores de HPR Servicios</summary>
            <div id="external-events-servicios" class="grid grid-cols-2 md:grid-cols-6 gap-2 mt-2">
                @foreach ($trabajadoresServicios as $t)
                    <div class="fc-event px-3 py-2 text-xs bg-blue-100 rounded cursor-pointer text-center shadow"
                        data-id="{{ $t->id }}" data-title="{{ $t->nombre_completo }}"
                        data-categoria="{{ $t->categoria?->nombre }}" data-especialidad="{{ $t->maquina?->nombre }}"
                        data-dias-asignados="0">
                        @if ($t->ruta_imagen)
                            <img src="{{ $t->ruta_imagen }}"
                                class="w-10 h-10 rounded-full object-cover mx-auto mb-1 ring-2 ring-blue-300">
                        @else
                            <div
                                class="w-10 h-10 rounded-full bg-gray-300 mx-auto mb-1 ring-2 ring-blue-300 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                </svg>
                            </div>
                        @endif
                        {{ $t->nombre_completo }}
                        <div class="text-[10px] text-gray-600">
                            {{ $t->categoria?->nombre }} @if ($t->maquina)
                                Â· {{ $t->maquina?->nombre }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </details>
        {{-- hpr --}}
        <details>
            <summary class="cursor-pointer font-bold text-gray-800 mb-2">Trabajadores de Hierros Paco Reyes
            </summary>
            <div id="external-events-hpr" class="grid grid-cols-2 md:grid-cols-6 gap-2 mt-2">
                @foreach ($trabajadoresHpr as $t)
                    <div class="fc-event px-3 py-2 text-xs bg-blue-100 rounded cursor-pointer text-center shadow"
                        data-id="{{ $t->id }}" data-title="{{ $t->nombre_completo }}"
                        data-categoria="{{ $t->categoria?->nombre }}" data-especialidad="{{ $t->maquina?->nombre }}"
                        data-dias-asignados="0">
                        @if ($t->ruta_imagen)
                            <img src="{{ $t->ruta_imagen }}"
                                class="w-10 h-10 rounded-full object-cover mx-auto mb-1 ring-2 ring-blue-300">
                        @else
                            <div
                                class="w-10 h-10 rounded-full bg-gray-300 mx-auto mb-1 ring-2 ring-blue-300 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                </svg>
                            </div>
                        @endif
                        {{ $t->nombre_completo }}
                        <div class="text-[10px] text-gray-600">
                            {{ $t->categoria?->nombre }} @if ($t->maquina)
                                Â· {{ $t->maquina?->nombre }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </details>
        {{-- Trabajadores ficticios --}}
        <details class="mt-4">
            <summary class="cursor-pointer font-bold text-gray-800 mb-2">Trabajadores Ficticios</summary>
            <div class="mt-2">
                {{-- Formulario para crear --}}
                <div class="flex items-center gap-2 mb-3 p-2 bg-gray-50 rounded">
                    <input type="text" id="ficticio_nombre" placeholder="Nombre del trabajador ficticio..."
                        class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:ring focus:ring-green-300">
                    <button type="button" id="btnCrearTrabajadorFicticio"
                        class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded shadow text-sm">
                        + Añadir
                    </button>
                </div>
                {{-- Lista de ficticios --}}
                <div id="external-events-ficticios" class="grid grid-cols-2 md:grid-cols-6 gap-2">
                    @foreach ($trabajadoresFicticios as $t)
                        <div class="fc-event fc-event-ficticio px-3 py-2 text-xs bg-gray-200 rounded cursor-pointer text-center shadow relative group"
                            data-id="ficticio-{{ $t->id }}" data-title="{{ $t->nombre }}"
                            data-ficticio="true">
                            <button type="button"
                                class="btn-eliminar-trabajador-ficticio absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center shadow-md opacity-0 group-hover:opacity-100 transition-opacity"
                                data-id="{{ $t->id }}">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                            <div
                                class="w-10 h-10 rounded-full bg-gray-400 mx-auto mb-1 ring-2 ring-gray-500 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                </svg>
                            </div>
                            {{ $t->nombre }}
                            <div class="text-[10px] text-gray-500">Ficticio</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </details>
    </div>


    <div class="p-4">

        <!-- Calendario -->
        <div class="w-full bg-white">
            <div class="flex flex-wrap items-center justify-between gap-4 my-4 px-4">
                {{-- Panel de eventos seleccionados --}}
                <div id="panelEventosSeleccionados"
                    class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded px-3 py-2"
                    style="display: none;">
                    <span class="text-sm text-blue-800">
                        <strong id="contadorEventos">0</strong> eventos seleccionados
                    </span>
                    <select id="selectObraMover"
                        class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring focus:ring-blue-300">
                        <option value="">-- Mover a obra --</option>
                    </select>
                    <button id="btnMoverEventos"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                        Mover
                    </button>
                    <button id="btnDeseleccionarTodos"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-1 px-3 rounded text-sm">
                        Limpiar
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    {{-- Repetir obra específica --}}
                    <div class="flex items-center gap-2">
                        <select id="selectObraRepetir"
                            class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring focus:ring-yellow-300">
                            <option value="">-- Seleccionar obra --</option>
                        </select>
                        <button id="btnRepetirObraEspecifica"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                            🔁 Repetir obra
                        </button>
                    </div>

                    {{-- Separador --}}
                    <div class="h-8 w-px bg-gray-300"></div>

                    {{-- Repetir todas --}}
                    <button id="btnRepetirSemana"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                        🔁 Repetir todas las obras
                    </button>

                    {{-- Separador --}}
                    <div class="h-8 w-px bg-gray-300"></div>

                    {{-- Limpiar semana --}}
                    <button id="btnLimpiarSemana"
                        class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
                        🗑️ Limpiar semana
                    </button>
                </div>
            </div>
            {{-- Filtro de búsqueda de eventos --}}
            <div class="mb-2 mt-2">
                <input type="text" id="filtro-eventos" placeholder="Buscar trabajador en calendario..."
                    class="w-full md:w-64 border border-gray-300 rounded px-3 py-2 text-sm focus:ring focus:ring-blue-300 focus:border-blue-500">
            </div>
            <div id="calendario-obras" class="w-full"></div>
        </div>
    </div>
    <div class="max-w-xl mx-auto mt-4 bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">🤔 Cambiar tipo de una obra</h2>

        <form action="{{ route('obras.updateTipo') }}" method="POST">
            @csrf

            {{-- Selección de obra --}}
            <div class="mb-4">
                <label for="obra_id" class="block text-sm font-medium text-gray-700">Selecciona una obra</label>
                <select name="obra_id" id="obra_id"
                    class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 text-sm shadow-sm focus:ring focus:ring-blue-300">
                    <option value="">-- Elige una obra --</option>
                    @foreach ($obras as $obra)
                        <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                    @endforeach
                </select>
                @error('obra_id')
                    <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            {{-- Nuevo tipo --}}
            <div class="mb-4">
                <label for="tipo" class="block text-sm font-medium text-gray-700">Nuevo tipo</label>
                <select name="tipo" id="tipo"
                    class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 text-sm shadow-sm focus:ring focus:ring-blue-300">
                    <option value="">-- Selecciona un tipo --</option>
                    <option value="obra">Obra</option>
                    <option value="montaje">Montaje</option>
                    <option value="mantenimiento">Mantenimiento</option>
                </select>
                @error('tipo')
                    <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            {{-- Botón --}}
            <div class="flex justify-end">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded shadow">
                    Actualizar tipo
                </button>
            </div>
        </form>
    </div>

    <!-- FullCalendar + Tippy -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

    <script>
        // Guardar resources en variable global ANTES de cualquier inicialización
        // Esto se ejecuta siempre para tener datos frescos del servidor
        window.obrasResources = @json($resources);

        // Inicializar event listeners para selección de fichas de trabajadores
        function inicializarFichasTrabajadores() {
            document.querySelectorAll(
                '#external-events-servicios .fc-event, #external-events-hpr .fc-event, #external-events-ficticios .fc-event'
            ).forEach(eventEl => {
                // Remover listeners anteriores si existen
                eventEl.replaceWith(eventEl.cloneNode(true));
            });

            // Agregar listeners a las fichas (normales y ficticias)
            document.querySelectorAll(
                '#external-events-servicios .fc-event, #external-events-hpr .fc-event, #external-events-ficticios .fc-event'
            ).forEach(eventEl => {
                eventEl.addEventListener('click', (e) => {
                    // Ignorar si se hace click en botón eliminar
                    if (e.target.closest('.btn-eliminar-trabajador-ficticio')) return;
                    e.stopPropagation();
                    eventEl.classList.toggle('bg-yellow-300');
                    eventEl.classList.toggle('seleccionado');
                });
            });
        }

        // Handler unificado para clicks globales en esta página
        window.handleObrasClicks = function(e) {
            // 1. Eliminar trabajador ficticio de la LISTA LATERAL (lógica traída del listener inferior)
            const btnEliminarTrabajador = e.target.closest('.btn-eliminar-trabajador-ficticio');
            if (btnEliminarTrabajador) {
                e.stopPropagation();
                e.preventDefault();

                const trabajadorId = btnEliminarTrabajador.dataset.id;

                Swal.fire({
                    title: '¿Eliminar trabajador ficticio?',
                    text: "Se eliminarán también todos sus eventos en el calendario.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/trabajadores-ficticios/${trabajadorId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    // Eliminar ficha del DOM
                                    const ficha = btnEliminarTrabajador.closest('.fc-event-ficticio');
                                    if (ficha) ficha.remove();

                                    // Recargar eventos del calendario
                                    if (window.calendarioObras) window.calendarioObras.refetchEvents();

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Eliminado',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire('âŒ Error', data.message, 'error');
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                Swal.fire('âŒ Error', 'No se pudo eliminar.', 'error');
                            });
                    }
                });
                return;
            }

            // 2. Eliminar evento ficticio DEL CALENDARIO
            const btnEliminarFicticio = e.target.closest('.btn-eliminar-ficticio');
            if (btnEliminarFicticio) {
                e.stopPropagation();
                e.preventDefault();

                const ficticioId = btnEliminarFicticio.dataset.id;

                Swal.fire({
                    title: '¿Eliminar trabajador ficticio?',
                    text: "Se eliminará este evento del calendario.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/eventos-ficticios-obra/${ficticioId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    const evento = window.calendarioObras.getEventById('ficticio-' +
                                        ficticioId);
                                    if (evento) evento.remove();
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Eliminado',
                                        text: 'Trabajador ficticio eliminado',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire('âŒ Error', data.message, 'error');
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                Swal.fire('âŒ Error', 'No se pudo eliminar.', 'error');
                            });
                    }
                });
                return;
            }

            // 3. Eliminar asignación de obra (Evento Normal)
            const btnEliminar = e.target.closest('.btn-eliminar');
            if (btnEliminar) {
                e.stopPropagation();
                e.preventDefault();

                const idCompleto = btnEliminar.dataset.id;
                const idEvento = idCompleto.replace('turno-', '');

                Swal.fire({
                    title: '¿Eliminar asignación de obra?',
                    text: "Esto quitará la obra del trabajador en ese turno.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/asignaciones-turno/${idEvento}/quitar-obra`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    const evento = window.calendarioObras.getEventById('turno-' + idEvento);
                                    if (evento) evento.remove();

                                    // Actualizar estado de fichas
                                    setTimeout(() => actualizarEstadoFichasTrabajadores(), 100);
                                } else {
                                    Swal.fire('âŒ Error', data.message, 'error');
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                Swal.fire('âŒ Error', 'No se pudo quitar la obra.', 'error');
                            });
                    }
                });
            }
        };

        window.calendarioObras = window.calendarioObras || null;

        // Map para almacenar eventos seleccionados
        window.eventosSeleccionados = window.eventosSeleccionados || new Map();

        /**
         * Actualiza el panel de eventos seleccionados
         */
        function actualizarPanelEventosSeleccionados() {
            const panel = document.getElementById('panelEventosSeleccionados');
            const contador = document.getElementById('contadorEventos');
            const btnMover = document.getElementById('btnMoverEventos');
            const selectObra = document.getElementById('selectObraMover');

            const cantidad = window.eventosSeleccionados.size;
            contador.textContent = cantidad;

            if (cantidad > 0) {
                panel.style.display = 'flex';
                btnMover.disabled = !selectObra.value;
            } else {
                panel.style.display = 'none';
            }
        }

        /**
         * Deselecciona todos los eventos
         */
        function deseleccionarTodosEventos() {
            window.eventosSeleccionados.forEach((data, eventId) => {
                const evento = window.calendarioObras.getEventById(eventId);
                if (evento) {
                    evento.setExtendedProp('seleccionado', false);
                }
            });
            window.eventosSeleccionados.clear();

            // Quitar clase visual de todos los eventos
            document.querySelectorAll('.evento-seleccionado').forEach(el => {
                el.classList.remove('evento-seleccionado');
            });

            actualizarPanelEventosSeleccionados();
        }

        /**
         * Poblar el select de obras para mover
         */
        function poblarSelectObrasMover() {
            const select = document.getElementById('selectObraMover');
            if (!select || !window.obrasResources) return;

            select.innerHTML = '<option value="">-- Mover a obra --</option>';

            window.obrasResources.forEach(resource => {
                if (resource.id !== 'sin-obra') {
                    const option = document.createElement('option');
                    option.value = resource.id;
                    option.textContent = resource.codigo ? `${resource.codigo} - ${resource.title}` : resource
                        .title;
                    select.appendChild(option);
                }
            });
        }

        /**
         * Mover eventos seleccionados a otra obra
         */
        function moverEventosAObra() {
            const selectObra = document.getElementById('selectObraMover');
            const obraId = selectObra.value;

            if (!obraId) {
                Swal.fire('⚠️', 'Selecciona una obra destino', 'warning');
                return;
            }

            if (window.eventosSeleccionados.size === 0) {
                Swal.fire('⚠️', 'No hay eventos seleccionados', 'warning');
                return;
            }

            const ids = Array.from(window.eventosSeleccionados.keys());
            const obraTexto = selectObra.options[selectObra.selectedIndex].text;

            // Separar IDs de eventos normales y ficticios
            const idsNormales = ids.filter(id => id.startsWith('turno-'));
            const idsFicticios = ids.filter(id => id.startsWith('ficticio-')).map(id => parseInt(id.replace('ficticio-',
                '')));

            Swal.fire({
                title: '¿Mover eventos?',
                text: `Se moverán ${ids.length} evento(s) a "${obraTexto}"`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, mover',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const promesas = [];

                    // Mover eventos normales
                    if (idsNormales.length > 0) {
                        promesas.push(
                            fetch('{{ route('asignaciones-turnos.moverEventos') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    asignacion_ids: idsNormales,
                                    obra_id: obraId
                                })
                            }).then(res => res.json())
                        );
                    }

                    // Mover eventos ficticios
                    if (idsFicticios.length > 0) {
                        promesas.push(
                            fetch('{{ route('eventos-ficticios-obra.mover') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    evento_ids: idsFicticios,
                                    obra_id: obraId
                                })
                            }).then(res => res.json())
                        );
                    }

                    Promise.all(promesas)
                        .then(results => {
                            const todoOk = results.every(r => r.success);
                            if (todoOk) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '✅ Eventos movidos',
                                    timer: 2000,
                                    showConfirmButton: false
                                });

                                // Limpiar selección y recargar eventos
                                deseleccionarTodosEventos();
                                window.calendarioObras.refetchEvents();
                                setTimeout(() => actualizarEstadoFichasTrabajadores(), 200);
                            } else {
                                Swal.fire('âŒ Error', 'Algunos eventos no se pudieron mover.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            Swal.fire('âŒ Error', 'No se pudieron mover los eventos.', 'error');
                        });
                }
            });
        }

        /**
         * Actualiza el estado de las fichas de trabajadores según sus asignaciones en la semana actual
         */
        function actualizarEstadoFichasTrabajadores() {
            const cal = window.calendarioObras;
            if (!cal) {
                return;
            }

            // Obtener rango de la semana visible
            const currentView = cal.view;
            const currentStart = new Date(currentView.currentStart);
            const currentEnd = new Date(currentView.currentEnd);

            // Calcular todos los días de la semana visible (lunes a domingo = 7 días)
            const diasSemana = [];

            if (currentView.type === 'resourceTimelineDay') {
                // Vista día: solo ese día
                const d = new Date(currentStart);
                diasSemana.push(d.toISOString().split('T')[0]);
            } else {
                // Vista semana: queremos lunes a domingo (7 días completos)
                // Usar una función que no tenga problemas de zona horaria

                const formatLocalDate = (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                };

                const start = new Date(currentStart);
                const dayOfWeek = start.getDay(); // 0=Dom, 1=Lun, 2=Mar, etc.

                // Calcular offset para llegar al lunes de esta semana
                let offsetToMonday = 0;
                if (dayOfWeek === 0) {
                    // Domingo: avanzar 1 día al lunes siguiente
                    offsetToMonday = 1;
                } else if (dayOfWeek > 1) {
                    // Martes-Sábado: retroceder al lunes de esta semana
                    offsetToMonday = -(dayOfWeek - 1);
                }
                // Si es lunes (1), offset = 0

                // Crear fecha del lunes
                const lunes = new Date(start);
                lunes.setDate(lunes.getDate() + offsetToMonday);

                // Generar los 7 días (Lun-Dom) usando hora local
                for (let i = 0; i < 7; i++) {
                    const dia = new Date(lunes);
                    dia.setDate(dia.getDate() + i);
                    diasSemana.push(formatLocalDate(dia));
                }
            }

            const totalDiasSemana = diasSemana.length;

            // Obtener todos los eventos del calendario
            const eventos = cal.getEvents();

            // Contar días asignados por trabajador
            const diasPorTrabajador = {};

            eventos.forEach(evento => {
                const userId = String(evento.extendedProps?.user_id);
                const fechaEvento = evento.startStr.split('T')[0];

                // Ignorar eventos provisionales, temporales o festivos
                const esProvisional = evento.extendedProps?.provisional;
                const esFestivo = evento.extendedProps?.es_festivo;
                const esTemporal = evento.id?.toString().startsWith('temp-');
                const turnoNombre = evento.extendedProps?.turno?.toLowerCase();

                // Solo contabilizar eventos cuyo turno sea "montaje"
                const esTurnoMontaje = turnoNombre && turnoNombre === 'montaje';

                if (esProvisional || esFestivo || esTemporal || !esTurnoMontaje) {
                    return;
                }

                if (userId && userId !== 'undefined' && diasSemana.includes(fechaEvento)) {
                    if (!diasPorTrabajador[userId]) {
                        diasPorTrabajador[userId] = new Set();
                    }
                    diasPorTrabajador[userId].add(fechaEvento);
                }
            });

            // Actualizar fichas de trabajadores
            const fichas = document.querySelectorAll('.fc-event[data-id]');

            fichas.forEach(ficha => {
                const userId = String(ficha.dataset.id); // Asegurar que sea string para comparación
                const diasAsignados = diasPorTrabajador[userId]?.size || 0;

                // Actualizar atributo data para el contador
                ficha.setAttribute('data-dias-asignados', diasAsignados);

                // Si tiene 7 días completos, mostrar en gris
                if (diasAsignados >= 7) {
                    ficha.classList.remove('bg-blue-100');
                    ficha.classList.add('ficha-completa', 'bg-gray-300');
                } else {
                    ficha.classList.remove('ficha-completa', 'bg-gray-300');
                    ficha.classList.add('bg-blue-100');
                }

                // Actualizar tooltip
                ficha.title = `${diasAsignados}/7 días asignados esta semana`;
            });
        }

        /**
         * Verifica qué trabajadores están ocupados en el calendario de producción (naves Paco Reyes)
         * y marca sus fichas como no disponibles
         */
        async function verificarOcupacionCruzada() {
            const cal = window.calendarioObras;
            if (!cal) return;

            const currentView = cal.view;
            const start = currentView.currentStart.toISOString().split('T')[0];
            const end = currentView.currentEnd.toISOString().split('T')[0];

            try {
                const response = await fetch(
                    `{{ route('asignaciones-turnos.ocupacionCruzada') }}?start=${start}&end=${end}&calendario=obras`
                );
                const data = await response.json();

                if (!data.success) return;

                const ocupados = data.ocupados;

                // Actualizar todas las fichas
                document.querySelectorAll('#external-events-servicios .fc-event, #external-events-hpr .fc-event')
                    .forEach(ficha => {
                        const userId = ficha.dataset.id;

                        // Remover clases anteriores
                        ficha.classList.remove('ocupado-taller');
                        const badgeAnterior = ficha.querySelector('.badge-taller');
                        if (badgeAnterior) badgeAnterior.remove();

                        if (ocupados[userId]) {
                            const diasOcupado = ocupados[userId].total_dias;
                            const diasRaw = ocupados[userId].dias || [];
                            // Convertir a array si es objeto
                            const diasLista = Array.isArray(diasRaw) ? diasRaw : Object.values(diasRaw);

                            // Formatear los días para mostrar
                            const diasFormateados = diasLista.map(fecha => {
                                // Asegurar formato correcto YYYY-MM-DD
                                const partes = fecha.split('-');
                                if (partes.length === 3) {
                                    const d = new Date(partes[0], partes[1] - 1, partes[2]);
                                    return d.toLocaleDateString('es-ES', {
                                        weekday: 'short',
                                        day: 'numeric'
                                    });
                                }
                                return fecha;
                            }).join(', ');

                            // Marcar como ocupado en taller
                            ficha.classList.add('ocupado-taller');

                            // Añadir badge indicador en esquina inferior izquierda
                            const badge = document.createElement('span');
                            badge.className = 'badge-taller';
                            badge.title = `En taller: ${diasFormateados}`;
                            badge.textContent = `🏭 ${diasOcupado}`;
                            badge.style.cssText =
                                'position:absolute; bottom:-4px; left:-4px; background:#f97316; color:white; font-size:9px; font-weight:bold; padding:2px 6px; border-radius:9999px; box-shadow:0 1px 3px rgba(0,0,0,0.3); z-index:10;';
                            ficha.style.position = 'relative';
                            ficha.appendChild(badge);

                            // Actualizar tooltip con días específicos
                            const tooltipActual = ficha.title || '';
                            ficha.title = `${tooltipActual} | Taller: ${diasFormateados}`;
                        }
                    });
            } catch (error) {
                console.error('Error verificando ocupación cruzada:', error);
            }
        }

        // Función auxiliar para crear asignación en obra
        async function crearAsignacionObra(userId, obraId, fecha, nombreTrabajador) {
            // Evento provisional
            const eventoTemporal = window.calendarioObras.addEvent({
                id: 'temp-' + Date.now() + '-' + userId,
                title: nombreTrabajador,
                start: fecha + 'T06:00:00',
                end: fecha + 'T14:00:00',
                resourceId: obraId ?? 'sin-obra',
                extendedProps: {
                    user_id: userId,
                    provisional: true
                }
            });

            try {
                const response = await fetch('{{ route('asignaciones-turnos.asignarObra') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        obra_id: obraId,
                        fecha: fecha
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.calendarioObras.getEvents().forEach(ev => {
                        if (ev.extendedProps.user_id == data.user.id && ev.startStr.startsWith(data.fecha)) {
                            ev.remove();
                        }
                    });

                    window.calendarioObras.addEvent({
                        id: 'turno-' + data.asignacion.id,
                        title: data.user.nombre_completo,
                        start: data.fecha + 'T06:00:00',
                        end: data.fecha + 'T14:00:00',
                        resourceId: data.obra_id ?? 'sin-obra',
                        extendedProps: {
                            user_id: data.user.id,
                            categoria_nombre: data.user.categoria?.nombre,
                            especialidad_nombre: data.user.maquina?.nombre,
                            estado: data.asignacion.estado ?? null
                        }
                    });

                    eventoTemporal.remove();
                    setTimeout(() => actualizarEstadoFichasTrabajadores(), 100);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                    eventoTemporal.remove();
                }
            } catch (error) {
                console.error('âŒ Error en la solicitud:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de red',
                    text: 'No se pudo completar la solicitud.'
                });
                eventoTemporal.remove();
            }
        }

        function inicializarCalendarioObras() {
            if (window.calendarioObras) {
                window.calendarioObras.destroy();
            }

            window.calendarioObras = new FullCalendar.Calendar(document.getElementById('calendario-obras'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                locale: 'es',
                initialView: localStorage.getItem('vistaObras') || 'resourceTimelineWeek',
                initialDate: localStorage.getItem('fechaObras') || undefined,
                firstDay: 1,
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'resourceTimelineDay,resourceTimelineWeek'
                },
                buttonText: {
                    today: 'Hoy',
                    week: 'Semana',
                    day: 'Día'
                },
                editable: true,
                resourceAreaHeaderContent: 'Obras Activas',
                resources: window.obrasResources,
                resourceAreaColumns: [{
                        field: 'codigo', // este campo debe existir en tu array `resources`
                        headerContent: 'Código',
                        width: 20
                    },
                    {
                        field: 'title',
                        headerContent: 'Obra',
                        width: 100
                    }
                ],
                datesSet: function(info) {
                    localStorage.setItem('vistaObras', info.view.type);
                    localStorage.setItem('fechaObras', info.startStr);

                    // Mostrar botón solo en vista semanal
                    const btn = document.getElementById('btnRepetirSemana');
                    if (info.view.type === 'resourceTimelineWeek') {
                        btn.classList.remove('hidden');
                        btn.dataset.fecha = info.startStr;
                    } else {
                        btn.classList.add('hidden');
                    }

                    // Actualizar estado de fichas cuando cambie la fecha/vista
                    setTimeout(() => {
                        actualizarEstadoFichasTrabajadores();
                        verificarOcupacionCruzada();
                    }, 100);
                },
                selectable: true,
                selectMirror: true,
                select: function(info) {
                    const seleccionados = [...document.querySelectorAll('.fc-event.seleccionado')];

                    if (seleccionados.length === 0) {
                        return;
                    }

                    // Rango de fechas
                    const fechaInicio = info.startStr;
                    const fechaFinObj = new Date(info.end);
                    fechaFinObj.setDate(fechaFinObj.getDate());
                    const fechaFin = fechaFinObj.toISOString().split('T')[0];

                    // Obra seleccionada
                    const obraId = info.resource?.id;

                    // Separar trabajadores normales de ficticios
                    const normales = seleccionados.filter(e => e.dataset.ficticio !== 'true');
                    const ficticios = seleccionados.filter(e => e.dataset.ficticio === 'true');

                    const promesas = [];

                    // Asignar trabajadores normales
                    if (normales.length > 0) {
                        const userIds = normales.map(e => e.dataset.id);
                        promesas.push(
                            fetch('{{ route('asignaciones-turnos.asignarObraMultiple') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    user_ids: userIds,
                                    obra_id: obraId,
                                    fecha_inicio: fechaInicio,
                                    fecha_fin: fechaFin
                                })
                            }).then(r => r.json())
                        );
                    }

                    // Asignar trabajadores ficticios
                    if (ficticios.length > 0) {
                        const ficticioIds = ficticios.map(e => e.dataset.id.replace('ficticio-', ''));
                        promesas.push(
                            fetch('{{ route('eventos-ficticios-obra.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    trabajador_ficticio_ids: ficticioIds,
                                    obra_id: obraId,
                                    fecha_inicio: fechaInicio,
                                    fecha_fin: fechaFin
                                })
                            }).then(r => r.json())
                        );
                    }

                    if (promesas.length === 0) {
                        return;
                    }

                    Promise.all(promesas).then(results => {
                        const todoOk = results.every(r => r.success);
                        if (todoOk) {
                            // Deseleccionar todos los trabajadores
                            document.querySelectorAll('.fc-event.seleccionado').forEach(el => {
                                el.classList.remove('seleccionado', 'bg-yellow-300');
                            });

                            window.calendarioObras.refetchEvents();

                            // Actualizar estado de fichas
                            setTimeout(() => actualizarEstadoFichasTrabajadores(), 200);
                        } else {
                            Swal.fire('âŒ Error al asignar');
                        }
                    }).catch(err => {
                        console.error('Error en asignación:', err);
                        Swal.fire('âŒ Error al asignar');
                    });
                },
                events: {
                    url: '{{ route('asignaciones-turnos.verEventosObra') }}',
                    method: 'GET',
                    failure: function(error) {
                        console.error('Error al cargar eventos:', error);
                        Swal.fire('Error al cargar eventos');
                    },
                    success: function(events, xhr) {
                        const trabajadores = events.filter(e => e.id?.startsWith('turno-')).length;
                        const ficticios = events.filter(e => e.id?.startsWith('ficticio-')).length;
                        const festivos = events.filter(e => e.id?.startsWith('festivo-')).length;
                        console.log(
                            `Eventos cargados: ${trabajadores} trabajadores, ${ficticios} ficticios, ${festivos} festivos`
                        );
                        return events;
                    }
                },
                eventSourceSuccess: function(content, xhr) {
                    // Se ejecuta después de cargar TODOS los eventos
                    setTimeout(() => actualizarEstadoFichasTrabajadores(), 300);

                    // Limpiar eventos seleccionados al recargar
                    window.eventosSeleccionados.clear();
                    actualizarPanelEventosSeleccionados();
                },
                eventClick(info) {
                    // Si es click en botón eliminar (normal o ficticio), no hacer nada aquí
                    if (info.jsEvent && (info.jsEvent.target.closest('.btn-eliminar') || info.jsEvent.target
                            .closest('.btn-eliminar-ficticio'))) {
                        return;
                    }

                    // Detener propagación para evitar eventos globales
                    if (info.jsEvent) {
                        info.jsEvent.stopPropagation();
                        info.jsEvent.preventDefault();
                    }

                    // Toggle selección del evento
                    const eventId = info.event.id;
                    const eventEl = info.el;

                    if (window.eventosSeleccionados.has(eventId)) {
                        // Deseleccionar
                        window.eventosSeleccionados.delete(eventId);
                        eventEl.classList.remove('evento-seleccionado');
                        info.event.setExtendedProp('seleccionado', false);
                    } else {
                        // Seleccionar
                        window.eventosSeleccionados.set(eventId, {
                            id: eventId,
                            title: info.event.title,
                            fecha: info.event.startStr,
                            user_id: info.event.extendedProps?.user_id
                        });
                        eventEl.classList.add('evento-seleccionado');
                        info.event.setExtendedProp('seleccionado', true);
                    }

                    actualizarPanelEventosSeleccionados();
                },
                eventReceive(info) {
                    // Evitar que FullCalendar agregue el evento automáticamente
                    info.event.remove(); // elimina cualquier evento "fantasma"
                },
                droppable: true,
                drop: function(info) {
                    const dataId = info.draggedEl.dataset.id;
                    const obraId = info.resource?.id === 'sin-obra' ? null : parseInt(info.resource?.id);
                    const fecha = info.dateStr;
                    const esFicticio = info.draggedEl.dataset.ficticio === 'true';

                    // Si es trabajador ficticio
                    if (esFicticio) {
                        const trabajadorFicticioId = dataId.replace('ficticio-', '');

                        // Evento provisional
                        const eventoTemporal = window.calendarioObras.addEvent({
                            id: 'temp-ficticio-' + Date.now(),
                            title: info.draggedEl.dataset.title,
                            start: fecha + 'T06:00:00',
                            end: fecha + 'T14:00:00',
                            resourceId: obraId ?? 'sin-obra',
                            backgroundColor: '#9ca3af',
                            borderColor: '#6b7280',
                            textColor: '#ffffff',
                            extendedProps: {
                                es_ficticio: true,
                                provisional: true
                            }
                        });

                        fetch('{{ route('eventos-ficticios-obra.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    trabajador_ficticio_id: trabajadorFicticioId,
                                    obra_id: obraId,
                                    fecha: fecha
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                eventoTemporal.remove();
                                if (data.success) {
                                    window.calendarioObras.addEvent(data.evento);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('âŒ Error:', error);
                                eventoTemporal.remove();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error de red',
                                    text: 'No se pudo crear el evento.'
                                });
                            });

                        return;
                    }

                    // Trabajador normal
                    const userId = dataId;
                    const nombreTrabajador = info.draggedEl.dataset.title;

                    // Verificar conflictos con taller antes de asignar
                    (async () => {
                        try {
                            const conflictosResp = await fetch(
                                '/asignaciones-turno/verificar-conflictos', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        user_id: userId,
                                        fecha_inicio: fecha,
                                        fecha_fin: fecha,
                                        destino: 'obra' // Va hacia obra externa
                                    })
                                });

                            const conflictos = await conflictosResp.json();

                            // Si hay conflictos, preguntar al usuario
                            if (conflictos.tiene_conflictos && conflictos.dias_en_taller.length > 0) {
                                const diasTexto = conflictos.dias_en_taller.join(', ');
                                const result = await Swal.fire({
                                    icon: 'warning',
                                    title: '⚠️ Este trabajador tiene días en taller',
                                    html: `
                                        <div class="text-left">
                                            <p class="mb-3"><strong>${nombreTrabajador}</strong> ya tiene asignaciones en <strong>taller/producción</strong> esta semana:</p>
                                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-3">
                                                <p class="font-semibold text-amber-800">🏭 ${diasTexto}</p>
                                            </div>
                                            <p class="text-sm text-gray-600">¿Deseas continuar con la asignación de todos modos?</p>
                                        </div>
                                    `,
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, continuar',
                                    cancelButtonText: 'Cancelar',
                                    confirmButtonColor: '#f59e0b',
                                    cancelButtonColor: '#6b7280',
                                });

                                if (!result.isConfirmed) {
                                    return; // Usuario canceló
                                }
                            }

                            // Continuar con la asignación usando la función auxiliar
                            crearAsignacionObra(userId, obraId, fecha, nombreTrabajador);

                        } catch (error) {
                            console.error('Error verificando conflictos:', error);
                            // En caso de error, continuar con la asignación
                            crearAsignacionObra(userId, obraId, fecha, nombreTrabajador);
                        }
                    })();
                },

                eventDidMount(info) {
                    const foto = info.event.extendedProps.foto;

                    // Solo mostrar tooltip con foto si existe
                    if (foto) {
                        const content = `
    <img src="${foto}" class="w-18 h-18 rounded-full object-cover ring-2 ring-blue-400 shadow-lg">
`;

                        tippy(info.el, {
                            content: content,
                            allowHTML: true,
                            placement: 'top',
                            theme: 'transparent-avatar',
                            interactive: false,
                            arrow: false,
                            delay: [100, 0],
                            offset: [0, 10],
                        });
                    }
                    info.el.addEventListener('contextmenu', function(e) {
                        e.preventDefault(); // Evita el menú del navegador

                        const props = info.event.extendedProps;
                        const userId = props.user_id;
                        const asignacionId = info.event.id.replace('turno-', '');
                        const estadoActual = props.estado || 'activo';

                        // Detectar si hay eventos seleccionados
                        const numSeleccionados = window.eventosSeleccionados?.size || 0;
                        const haySeleccionados = numSeleccionados > 0;

                        // Obtener IDs de los seleccionados (solo turnos normales, no ficticios)
                        const idsSeleccionados = haySeleccionados ?
                            Array.from(window.eventosSeleccionados.keys())
                            .filter(id => id.startsWith('turno-'))
                            .map(id => id.replace('turno-', '')) : [asignacionId];

                        // Cerrar menú contextual anterior si existe
                        const menuAnterior = document.getElementById('menuContextualObra');
                        if (menuAnterior) menuAnterior.remove();

                        // Crear menú contextual
                        const menu = document.createElement('div');
                        menu.id = 'menuContextualObra';
                        menu.className =
                            'fixed bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-[9999] min-w-[220px]';
                        menu.style.left = e.pageX + 'px';
                        menu.style.top = e.pageY + 'px';

                        // Cabecera diferente si hay selección múltiple
                        const cabecera = haySeleccionados ?
                            `<div class="px-3 py-2 border-b border-gray-100 text-xs font-medium bg-blue-50 text-blue-700">
                                   <span class="inline-flex items-center gap-1">
                                       <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                       ${numSeleccionados} eventos seleccionados
                                   </span>
                               </div>` :
                            `<div class="px-3 py-2 border-b border-gray-100 text-xs text-gray-500 font-medium">${info.event.title}</div>`;

                        menu.innerHTML = `
                            ${cabecera}
                            ${!haySeleccionados ? `
                                                                                    <button class="w-full text-left px-4 py-2 hover:bg-blue-50 flex items-center gap-2 text-sm text-gray-700" data-action="perfil">
                                                                                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                                                        </svg>
                                                                                        Ir a su perfil
                                                                                    </button>` : ''}
                            <button class="w-full text-left px-4 py-2 hover:bg-yellow-50 flex items-center gap-2 text-sm text-gray-700" data-action="estado">
                                <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                Cambiar estado ${haySeleccionados ? `(${idsSeleccionados.length})` : ''}
                            </button>
                            <div class="border-t border-gray-100 mt-1 pt-1">
                                <button class="w-full text-left px-4 py-2 hover:bg-red-50 flex items-center gap-2 text-sm text-red-600" data-action="eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Eliminar ${haySeleccionados ? `(${idsSeleccionados.length})` : 'asignación'}
                                </button>
                            </div>
                        `;

                        document.body.appendChild(menu);

                        // Ajustar posición si se sale de la pantalla
                        const rect = menu.getBoundingClientRect();
                        if (rect.right > window.innerWidth) {
                            menu.style.left = (window.innerWidth - rect.width - 10) + 'px';
                        }
                        if (rect.bottom > window.innerHeight) {
                            menu.style.top = (e.pageY - rect.height) + 'px';
                        }

                        // Manejar clics en las opciones
                        menu.addEventListener('click', function(ev) {
                            const action = ev.target.closest('button')?.dataset.action;
                            if (!action) return;

                            menu.remove();

                            if (action === 'perfil') {
                                // Ir al perfil del usuario
                                if (userId) {
                                    window.open(`/users/${userId}`, '_blank');
                                } else {
                                    Swal.fire('Info', 'No se encontró el perfil del trabajador',
                                        'info');
                                }
                            } else if (action === 'estado') {
                                // Mostrar modal para cambiar estado
                                const textoInfo = haySeleccionados ?
                                    `Se cambiará el estado de <strong>${idsSeleccionados.length} asignaciones</strong>` :
                                    `Trabajador: <strong>${info.event.title}</strong>`;

                                Swal.fire({
                                    title: 'Cambiar estado de asignación',
                                    html: `
                                        <p class="text-sm text-gray-600 mb-4">${textoInfo}</p>
                                        <select id="swal-estado" class="w-full border border-gray-300 rounded px-3 py-2">
                                            <option value="activo" ${estadoActual === 'activo' ? 'selected' : ''}>⏱️ Activo</option>
                                            <option value="curso" ${estadoActual === 'curso' ? 'selected' : ''}>🎓 Cursos</option>
                                            <option value="vacaciones" ${estadoActual === 'vacaciones' ? 'selected' : ''}>🏖️ Vacaciones</option>
                                            <option value="baja" ${estadoActual === 'baja' ? 'selected' : ''}>🤕 Baja</option>
                                            <option value="justificada" ${estadoActual === 'justificada' ? 'selected' : ''}>✅ Justificada</option>
                                            <option value="injustificada" ${estadoActual === 'injustificada' ? 'selected' : ''}>❌ Injustificada</option>
                                        </select>
                                    `,
                                    showCancelButton: true,
                                    confirmButtonText: 'Guardar',
                                    cancelButtonText: 'Cancelar',
                                    preConfirm: () => {
                                        return document.getElementById('swal-estado')
                                            .value;
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        const nuevoEstado = result.value;

                                        // Actualizar todas las asignaciones seleccionadas
                                        const promesas = idsSeleccionados.map(id =>
                                            fetch(`/asignaciones-turnos/${id}`, {
                                                method: 'PUT',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                },
                                                body: JSON.stringify({
                                                    estado: nuevoEstado
                                                })
                                            }).then(res => res.json())
                                        );

                                        Promise.all(promesas)
                                            .then(results => {
                                                const exitosos = results.filter(r => r
                                                    .success).length;
                                                if (exitosos > 0) {
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Estado actualizado',
                                                        text: `Se actualizaron ${exitosos} asignaciones`,
                                                        timer: 1500,
                                                        showConfirmButton: false
                                                    });
                                                    // Limpiar selección y recargar
                                                    deseleccionarTodosEventos();
                                                    window.calendarioObras
                                                        .refetchEvents();
                                                } else {
                                                    Swal.fire('Error',
                                                        'No se pudo actualizar ninguna asignación',
                                                        'error');
                                                }
                                            })
                                            .catch(err => {
                                                console.error(err);
                                                Swal.fire('Error',
                                                    'No se pudo actualizar el estado',
                                                    'error');
                                            });
                                    }
                                });
                            } else if (action === 'eliminar') {
                                const textoEliminar = haySeleccionados ?
                                    `Se eliminarán ${idsSeleccionados.length} asignaciones` :
                                    'Esto quitará la obra del trabajador en ese turno.';

                                Swal.fire({
                                    title: haySeleccionados ? '¿Eliminar asignaciones?' :
                                        '¿Eliminar asignación?',
                                    text: textoEliminar,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Sí, eliminar',
                                    cancelButtonText: 'Cancelar'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        const promesas = idsSeleccionados.map(id =>
                                            fetch(
                                                `/asignaciones-turno/${id}/quitar-obra`, {
                                                    method: 'PUT',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                    }
                                                }).then(res => res.json())
                                        );

                                        Promise.all(promesas)
                                            .then(results => {
                                                const exitosos = results.filter(r => r
                                                    .success).length;
                                                if (exitosos > 0) {
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Eliminadas',
                                                        text: `Se eliminaron ${exitosos} asignaciones`,
                                                        timer: 1500,
                                                        showConfirmButton: false
                                                    });
                                                    deseleccionarTodosEventos();
                                                    window.calendarioObras
                                                        .refetchEvents();
                                                    setTimeout(() =>
                                                        actualizarEstadoFichasTrabajadores(),
                                                        100);
                                                } else {
                                                    Swal.fire('Error',
                                                        'No se pudo eliminar ninguna asignación',
                                                        'error');
                                                }
                                            })
                                            .catch(err => {
                                                console.error(err);
                                                Swal.fire('Error',
                                                    'No se pudo eliminar', 'error');
                                            });
                                    }
                                });
                            }
                        });

                        // Cerrar menú al hacer clic fuera
                        const cerrarMenu = (ev) => {
                            if (!menu.contains(ev.target)) {
                                menu.remove();
                                document.removeEventListener('click', cerrarMenu);
                            }
                        };
                        setTimeout(() => document.addEventListener('click', cerrarMenu), 10);
                    });
                },
                eventContent(arg) {
                    const props = arg.event.extendedProps;
                    const id = arg.event.id;

                    // Si es festivo, mostrar solo el título sin botón de eliminar
                    if (props.es_festivo) {
                        return {
                            html: `<div class="px-2 py-1 text-xs font-semibold" style="color:#fff">${arg.event.title}</div>`
                        };
                    }

                    // Si es ficticio, mostrar con estilo diferente y botón eliminar
                    if (props.es_ficticio) {
                        const notasTexto = props.notas ?
                            `<div class="text-[10px] opacity-80 italic">${props.notas}</div>` : '';
                        return {
                            html: `
                <div class="relative px-2 py-1 text-xs font-semibold group">
                    <button title="Eliminar ficticio"
                          class="absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center shadow-md transition-all duration-200 opacity-70 group-hover:opacity-100 btn-eliminar-ficticio transform hover:scale-110"
                          data-id="${props.ficticio_id}">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div>${arg.event.title}</div>
                    ${notasTexto}
                </div>
            `
                        };
                    }

                    // Texto de estado si existe
                    const estadoTexto = props.estado ? `<div class="text-[10px] opacity-80">${props.estado}</div>` :
                        '';

                    return {
                        html: `
            <div class="relative px-2 py-1 text-xs font-semibold group">
                <button title="Eliminar"
                      class="absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center shadow-md transition-all duration-200 opacity-70 group-hover:opacity-100 btn-eliminar transform hover:scale-110"
                      data-id="${id}">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div>${arg.event.title}</div>
                ${estadoTexto}
            </div>
        `
                    };
                },
                views: {
                    resourceTimelineDay: {
                        slotMinTime: '06:00:00',
                        slotMaxTime: '22:00:00',
                    },
                    resourceTimelineWeek: {
                        slotDuration: {
                            days: 1
                        },
                        slotLabelFormat: {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'short'
                        }
                    }
                },
                eventDrop(info) {
                    const eventoArrastrado = info.event;
                    const nuevaObraId = eventoArrastrado.getResources()[0]?.id ?? null;
                    const nuevaFecha = eventoArrastrado.startStr.split('T')[0];
                    const eventoId = eventoArrastrado.id;
                    const esFicticio = eventoId.startsWith('ficticio-');

                    // Verificar si el evento arrastrado está seleccionado
                    const estaSeleccionado = window.eventosSeleccionados.has(eventoId);
                    const hayMultiplesSeleccionados = window.eventosSeleccionados.size > 1;

                    // Si hay múltiples eventos seleccionados y el arrastrado es uno de ellos
                    if (estaSeleccionado && hayMultiplesSeleccionados) {
                        const ids = Array.from(window.eventosSeleccionados.keys());

                        // Separar IDs de eventos normales y ficticios
                        const idsNormales = ids.filter(id => id.startsWith('turno-'));
                        const idsFicticios = ids.filter(id => id.startsWith('ficticio-')).map(id => parseInt(id
                            .replace('ficticio-', '')));

                        const promesas = [];

                        // Mover eventos normales
                        if (idsNormales.length > 0) {
                            promesas.push(
                                fetch('{{ route('asignaciones-turnos.moverEventos') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        asignacion_ids: idsNormales,
                                        obra_id: nuevaObraId
                                    })
                                }).then(res => res.json())
                            );
                        }

                        // Mover eventos ficticios
                        if (idsFicticios.length > 0) {
                            promesas.push(
                                fetch('{{ route('eventos-ficticios-obra.mover') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        evento_ids: idsFicticios,
                                        obra_id: nuevaObraId
                                    })
                                }).then(res => res.json())
                            );
                        }

                        Promise.all(promesas)
                            .then(results => {
                                const todoOk = results.every(r => r.success);
                                if (todoOk) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '✅ Eventos movidos',
                                        toast: true,
                                        position: 'top-end',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });

                                    // Limpiar selección y recargar eventos
                                    deseleccionarTodosEventos();
                                    window.calendarioObras.refetchEvents();
                                    setTimeout(() => actualizarEstadoFichasTrabajadores(), 200);
                                } else {
                                    info.revert();
                                    Swal.fire('âŒ Error', 'Algunos eventos no se pudieron mover.', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('âŒ Error:', error);
                                info.revert();
                                Swal.fire('âŒ Error', 'No se pudieron mover los eventos.', 'error');
                            });

                        return; // Salir, ya procesamos el drop múltiple
                    }

                    // Drop individual
                    if (esFicticio) {
                        // Evento ficticio individual
                        const ficticioId = eventoId.replace('ficticio-', '');

                        fetch(`/eventos-ficticios-obra/${ficticioId}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    obra_id: nuevaObraId === '' ? null : nuevaObraId,
                                    fecha: nuevaFecha
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (!data.success) {
                                    throw new Error(data.message ?? 'Error inesperado');
                                }
                            })
                            .catch(error => {
                                console.error('Error en actualización de evento ficticio:', error);
                                info.revert();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al actualizar',
                                    text: error.message
                                });
                            });
                    } else {
                        // Evento normal individual
                        const asignacionId = eventoId.replace('turno-', '');

                        fetch(`/asignaciones-turnos/${asignacionId}/update-obra`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    obra_id: nuevaObraId === '' ? null : nuevaObraId,
                                    fecha: nuevaFecha
                                })
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Error al actualizar');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (!data.success) {
                                    throw new Error(data.message ?? 'Error inesperado');
                                }
                            })
                            .catch(error => {
                                console.error('Error en actualización de obra:', error);
                                info.revert();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al actualizar',
                                    text: error.message
                                });
                            });
                    }
                }
            });

            window.calendarioObras.render();

            // Inicializar todas las fichas con contador 0/5
            document.querySelectorAll('.fc-event[data-id]').forEach(ficha => {
                if (!ficha.hasAttribute('data-dias-asignados')) {
                    ficha.setAttribute('data-dias-asignados', '0');
                }
            });

            // Inicializar listeners de fichas
            inicializarFichasTrabajadores();

            // Actualizar fichas después de renderizar el calendario
            setTimeout(() => {
                actualizarEstadoFichasTrabajadores();
                verificarOcupacionCruzada();
            }, 500);
        }

        // Función para inicializar el calendario con limpieza previa
        function initCalendario() {
            const calendarioEl = document.getElementById('calendario-obras');
            if (!calendarioEl) return;

            // Destruir calendario anterior si existe
            if (window.calendarioObras) {
                try {
                    window.calendarioObras.destroy();
                    window.calendarioObras = null;
                } catch (e) {
                    console.warn('Error al destruir calendario anterior:', e);
                }
            }

            inicializarCalendarioObras();
        }

        // Función para inicializar todo
        function inicializarTodo() {
            // Verificar que FullCalendar esté disponible
            if (typeof FullCalendar === 'undefined') {
                console.warn('FullCalendar no disponible, reintentando...');
                setTimeout(inicializarTodo, 100);
                return;
            }

            const calendarioEl = document.getElementById('calendario-obras');
            if (!calendarioEl) {
                console.warn('Elemento calendario-obras no encontrado');
                return;
            }

            initCalendario();

            // Inicializar filtro de búsqueda de trabajadores
            const filtroInput = document.getElementById('filtro-trabajadores');
            if (filtroInput) {
                filtroInput.addEventListener('input', function() {
                    const texto = this.value.toLowerCase().trim();
                    const contenedores = [
                        document.getElementById('external-events-servicios'),
                        document.getElementById('external-events-hpr'),
                        document.getElementById('external-events-ficticios')
                    ];

                    contenedores.forEach(contenedor => {
                        if (!contenedor) return;
                        const trabajadores = contenedor.querySelectorAll('.fc-event');
                        trabajadores.forEach(t => {
                            const nombre = (t.dataset.title || t.textContent || '').toLowerCase();
                            const categoria = (t.dataset.categoria || '').toLowerCase();
                            const especialidad = (t.dataset.especialidad || '').toLowerCase();
                            const coincide = nombre.includes(texto) || categoria.includes(texto) ||
                                especialidad.includes(texto);
                            t.style.display = coincide ? '' : 'none';
                        });
                    });
                });
            }

            // Inicializar filtro de búsqueda de eventos del calendario
            const filtroEventosInput = document.getElementById('filtro-eventos');
            if (filtroEventosInput && window.calendarioObras) {
                filtroEventosInput.addEventListener('input', function() {
                    const texto = this.value.toLowerCase().trim();
                    const todosEventos = window.calendarioObras.getEvents();

                    todosEventos.forEach(evento => {
                        const props = evento.extendedProps || {};
                        // Ignorar festivos
                        if (props.es_festivo) return;

                        const titulo = (evento.title || '').toLowerCase();
                        const categoria = (props.categoria_nombre || '').toLowerCase();
                        const especialidad = (props.especialidad_nombre || '').toLowerCase();

                        const coincide = !texto ||
                            titulo.includes(texto) ||
                            categoria.includes(texto) ||
                            especialidad.includes(texto);

                        evento.setProp('display', coincide ? 'auto' : 'none');
                    });
                });
            }
        }

        // Configuración de eventos UI
        function setupObrasUIEvents() {
            // Poblar selects iniciales
            setTimeout(poblarSelectObras, 100);
            if (typeof poblarSelectObrasMover === 'function') setTimeout(poblarSelectObrasMover, 100);

            // Helper para limpiar listeners previos mediante clonación
            const cleanListener = (id, event, handler) => {
                const el = document.getElementById(id);
                if (el) {
                    const newEl = el.cloneNode(true);
                    el.parentNode.replaceChild(newEl, el);
                    newEl.addEventListener(event, handler);
                }
            };

            // 1. Panel Mover
            cleanListener('selectObraMover', 'change', function() {
                const btn = document.getElementById('btnMoverEventos');
                if (btn) btn.disabled = !this.value || window.eventosSeleccionados.size === 0;
            });
            cleanListener('btnMoverEventos', 'click', moverEventosAObra);
            cleanListener('btnDeseleccionarTodos', 'click', deseleccionarTodosEventos);

            // 2. Repetir Obra Específica
            cleanListener('selectObraRepetir', 'change', function() {
                const btn = document.getElementById('btnRepetirObraEspecifica');
                if (btn) btn.disabled = !this.value;
            });

            cleanListener('btnRepetirObraEspecifica', 'click', function() {
                const select = document.getElementById('selectObraRepetir');
                const obraId = select.value;
                const obraTexto = select.options[select.selectedIndex].text;
                const fechaInicio = document.getElementById('btnRepetirSemana').dataset.fecha;

                if (!obraId) {
                    Swal.fire('âŒ Error', 'Debes seleccionar una obra', 'error');
                    return;
                }

                Swal.fire({
                    title: '¿Repetir semana anterior?',
                    text: `Se copiarán las asignaciones de "${obraTexto}" de la semana pasada a la actual.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, repetir',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('{{ route('asignaciones-turnos.repetirSemanaObra') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    fecha_actual: fechaInicio,
                                    obra_id: obraId
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('✅ Obra copiada correctamente', data.message || '',
                                        'success');
                                    window.calendarioObras.refetchEvents();
                                    setTimeout(() => actualizarEstadoFichasTrabajadores(), 200);
                                } else {
                                    Swal.fire('âŒ Error', data.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error(error);
                                Swal.fire('âŒ Error', 'No se pudo completar la solicitud.', 'error');
                            });
                    }
                });
            });

            // 3. Repetir Semana Completa
            cleanListener('btnRepetirSemana', 'click', function() {
                const fechaInicio = this.dataset.fecha;
                Swal.fire({
                    title: '¿Repetir semana anterior?',
                    text: 'Se copiarán TODAS las asignaciones de la semana pasada a la actual.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, repetir todas',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('{{ route('asignaciones-turnos.repetirSemana') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    fecha_actual: fechaInicio
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('✅ Semana copiada correctamente');
                                    window.calendarioObras.refetchEvents();
                                    setTimeout(() => actualizarEstadoFichasTrabajadores(), 200);
                                } else {
                                    Swal.fire('âŒ Error', data.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error(error);
                                Swal.fire('âŒ Error', 'No se pudo completar la solicitud.', 'error');
                            });
                    }
                });
            });

            // 4. Limpiar Semana
            cleanListener('btnLimpiarSemana', 'click', function() {
                const fechaInicio = document.getElementById('btnRepetirSemana').dataset.fecha;
                Swal.fire({
                    title: '¿Limpiar semana actual?',
                    html: `
                        <p class="text-sm text-gray-600 mb-4">Se eliminarán todas las asignaciones de obra de esta semana.</p>
                        <div class="text-left">
                            <label class="flex items-center gap-2 mb-2">
                                <input type="checkbox" id="limpiarTodas" checked class="rounded">
                                <span class="text-sm">Todas las obras</span>
                            </label>
                            <div id="selectObraLimpiarContainer" class="hidden">
                                <select id="selectObraLimpiar" class="w-full border border-gray-300 rounded px-3 py-2 text-sm mt-2">
                                    <option value="">-- Seleccionar obra --</option>
                                </select>
                            </div>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, limpiar',
                    cancelButtonText: 'Cancelar',
                    didOpen: () => {
                        const selectObra = document.getElementById('selectObraLimpiar');
                        window.obrasResources.forEach(resource => {
                            if (resource.id !== 'sin-obra') {
                                const option = document.createElement('option');
                                option.value = resource.id;
                                option.textContent = resource.codigo ?
                                    `${resource.codigo} - ${resource.title}` : resource.title;
                                selectObra.appendChild(option);
                            }
                        });
                        document.getElementById('limpiarTodas').addEventListener('change', function() {
                            const container = document.getElementById(
                                'selectObraLimpiarContainer');
                            container.classList.toggle('hidden', this.checked);
                        });
                    },
                    preConfirm: () => {
                        const limpiarTodas = document.getElementById('limpiarTodas').checked;
                        const obraId = document.getElementById('selectObraLimpiar').value;
                        return {
                            limpiarTodas,
                            obraId
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const {
                            limpiarTodas,
                            obraId
                        } = result.value;
                        fetch('{{ route('asignaciones-turnos.limpiarSemana') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    fecha_actual: fechaInicio,
                                    obra_id: limpiarTodas ? null : obraId
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Semana limpiada',
                                        text: data.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    window.calendarioObras.refetchEvents();
                                    setTimeout(() => actualizarEstadoFichasTrabajadores(), 200);
                                } else {
                                    Swal.fire('âŒ Error', data.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error(error);
                                Swal.fire('âŒ Error', 'No se pudo completar la solicitud.', 'error');
                            });
                    }
                });
            });

            // 5. Crear Trabajador Ficticio
            cleanListener('btnCrearTrabajadorFicticio', 'click', function() {
                const nombre = document.getElementById('ficticio_nombre').value.trim();
                if (!nombre) {
                    Swal.fire('⚠️ Error', 'Debes introducir un nombre', 'warning');
                    return;
                }
                fetch('{{ route('trabajadores-ficticios.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            nombre: nombre
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const container = document.getElementById('external-events-ficticios');
                            const ficha = document.createElement('div');
                            ficha.className =
                                'fc-event fc-event-ficticio px-3 py-2 text-xs bg-gray-200 rounded cursor-pointer text-center shadow relative group';
                            ficha.dataset.id = 'ficticio-' + data.trabajador.id;
                            ficha.dataset.title = data.trabajador.nombre;
                            ficha.dataset.ficticio = 'true';
                            ficha.innerHTML = `
                            <button type="button" class="btn-eliminar-trabajador-ficticio absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center shadow-md opacity-0 group-hover:opacity-100 transition-opacity" data-id="${data.trabajador.id}">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                            <div class="w-10 h-10 rounded-full bg-gray-400 mx-auto mb-1 ring-2 ring-gray-500 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            </div>
                            ${data.trabajador.nombre}
                            <div class="text-[10px] text-gray-500">Ficticio</div>
                        `;
                            ficha.addEventListener('click', (e) => {
                                if (e.target.closest('.btn-eliminar-trabajador-ficticio')) return;
                                e.stopPropagation();
                                ficha.classList.toggle('bg-yellow-300');
                                ficha.classList.toggle('seleccionado');
                            });
                            container.appendChild(ficha);
                            document.getElementById('ficticio_nombre').value = '';
                            Swal.fire({
                                icon: 'success',
                                title: '✅ Trabajador ficticio creado',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('âŒ Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire('âŒ Error', 'No se pudo crear el trabajador.', 'error');
                    });
            });
        }

        // --- INIT PRINCIPAL ROBUSTO ---
        function initProduccionTrabajadoresObraPage() {
            if (document.body.dataset.produccionTrabajadoresObraPageInit === 'true') return;

            inicializarTodo();
            setupObrasUIEvents();

            document.removeEventListener('click', window.handleObrasClicks);
            document.addEventListener('click', window.handleObrasClicks);

            document.body.dataset.produccionTrabajadoresObraPageInit = 'true';
        }

        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(initProduccionTrabajadoresObraPage);

        document.addEventListener('livewire:navigated', initProduccionTrabajadoresObraPage);
        document.addEventListener('DOMContentLoaded', initProduccionTrabajadoresObraPage);

        document.addEventListener('livewire:navigating', () => {
            document.body.dataset.produccionTrabajadoresObraPageInit = 'false';
            document.removeEventListener('click', window.handleObrasClicks);
            if (window.calendarioObras) {
                try {
                    window.calendarioObras.destroy();
                    window.calendarioObras = null;
                } catch (e) {
                    console.warn(e);
                }
            }
        });

        // Poblar el select de obras con los resources (excluyendo 'sin-obra')
        function poblarSelectObras() {
            const select = document.getElementById('selectObraRepetir');
            if (!select || !window.obrasResources) return;

            // Limpiar opciones existentes
            select.innerHTML = '<option value="">-- Seleccionar obra --</option>';

            // Añadir obras desde los resources
            window.obrasResources.forEach(resource => {
                if (resource.id !== 'sin-obra') {
                    const option = document.createElement('option');
                    option.value = resource.id;
                    option.textContent = resource.codigo ? `${resource.codigo} - ${resource.title}` : resource
                        .title;
                    select.appendChild(option);
                }
            });
        }
    </script>
    <style>
        /* ============================================
           ESTILOS DEL CALENDARIO (mismo estilo que trabajadores)
           ============================================ */

        .fc {
            width: 100% !important;
            max-width: 100% !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        /* Header del calendario - mismo color que sidebar */
        .fc .fc-toolbar {
            padding: 1rem;
            background: #111827;
            /* gray-900 */
            border-radius: 12px 12px 0 0;
            margin-bottom: 0 !important;
        }

        .fc .fc-toolbar-title {
            color: white !important;
            font-weight: 700;
            font-size: 1.25rem;
            text-transform: capitalize;
        }

        .fc .fc-button {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px !important;
            transition: all 0.2s ease;
            text-transform: capitalize;
        }

        .fc .fc-button:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            transform: translateY(-1px);
        }

        .fc .fc-button-active {
            background: #3b82f6 !important;
            color: white !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
        }

        .fc .fc-button:disabled {
            opacity: 0.5;
        }

        /* Bordes redondeados del contenedor */
        .fc .fc-view-harness {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-top: none;
        }

        /* Encabezados de recursos (obras) */
        .fc .fc-resource-area {
            background: #f8fafc;
        }

        .fc .fc-datagrid-cell-frame {
            padding: 0.5rem;
        }

        /* Celdas del timeline - vista semana */
        .fc .fc-timeline-slot {
            border-color: #e2e8f0 !important;
        }

        /* Hover solo en la celda individual */
        .fc .fc-timeline-lane:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* Slot del día actual */
        .fc .fc-day-today {
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%) !important;
        }

        /* Eventos */
        .fc .fc-event {
            border-radius: 6px;
            border: none !important;
            padding: 2px 6px;
            font-size: 0.75rem;
            font-weight: 500;
            margin: 1px 2px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .fc .fc-event:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            z-index: 10;
        }

        /* Labels de slots (días/horas) */
        .fc .fc-timeline-slot-label {
            font-weight: 600;
            color: #475569;
            text-transform: capitalize;
            font-size: 0.8rem;
        }

        /* Scrollbar del calendario */
        .fc .fc-scroller::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .fc .fc-scroller::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.75rem;
            }

            .fc .fc-toolbar-title {
                font-size: 1rem;
            }

            .fc .fc-button {
                padding: 0.4rem 0.75rem;
                font-size: 0.8rem;
            }
        }

        /* ============================================
           ESTILOS ESPECÍFICOS DE TRABAJADORES OBRA
           ============================================ */

        .fc-event.seleccionado {
            outline: 3px solid #facc15;
            background-color: #fde68a !important;
            transform: scale(1.05);
        }

        /* Eventos del calendario seleccionados */
        .fc-timeline-event.evento-seleccionado,
        .fc-event.evento-seleccionado {
            outline: 3px solid #3b82f6 !important;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.5) !important;
            transform: scale(1.02);
            z-index: 100 !important;
        }

        .fc-timeline-event.evento-seleccionado .fc-event-main,
        .fc-event.evento-seleccionado .fc-event-main {
            background-color: rgba(59, 130, 246, 0.2) !important;
        }

        /* Asegurar posición relativa en las fichas */
        #external-events-servicios .fc-event,
        #external-events-hpr .fc-event {
            position: relative !important;
        }

        /* Mostrar contador de días en todas las fichas */
        #external-events-servicios .fc-event[data-dias-asignados]::after,
        #external-events-hpr .fc-event[data-dias-asignados]::after {
            content: attr(data-dias-asignados) '/7' !important;
            position: absolute !important;
            top: 2px !important;
            right: 2px !important;
            background: #3b82f6 !important;
            color: white !important;
            font-size: 10px !important;
            padding: 2px 6px !important;
            border-radius: 4px !important;
            font-weight: bold !important;
            z-index: 100 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
            line-height: 1.2 !important;
            display: block !important;
            pointer-events: none !important;
        }

        /* Fichas con semana completa (5/5) en verde */
        #external-events-servicios .fc-event.ficha-completa::after,
        #external-events-hpr .fc-event.ficha-completa::after {
            background: #10b981 !important;
        }

        /* Fichas con semana completa en gris */
        #external-events-servicios .fc-event.ficha-completa,
        #external-events-hpr .fc-event.ficha-completa {
            opacity: 0.7 !important;
        }

        /* Fichas ocupadas en taller - borde naranja */
        #external-events-servicios .fc-event.ocupado-taller,
        #external-events-hpr .fc-event.ocupado-taller {
            border: 2px solid #f97316 !important;
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.3) !important;
        }

        /* Badge de taller */
        .badge-taller {
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .btn-eliminar {
            z-index: 10;
            pointer-events: auto;
            cursor: pointer;
        }

        .btn-eliminar:hover {
            box-shadow: 0 4px 6px rgba(239, 68, 68, 0.4);
        }

        .btn-eliminar * {
            pointer-events: none;
        }

        .tippy-box[data-theme~='transparent-avatar'] {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        /* Estilos para eventos de festivos */
        .evento-festivo {
            opacity: 0.9;
            font-weight: bold;
        }

        .evento-festivo .fc-event-main {
            padding: 2px 6px;
        }
    </style>
</x-app-layout>
