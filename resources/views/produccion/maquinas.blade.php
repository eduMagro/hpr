<x-app-layout>
    <x-slot name="title">Planificación por Máquina</x-slot>


    <div id="produccion-maquinas-container">
        @if (!empty($erroresPlanillas))
            <div class="mb-4 bg-yellow-100 text-yellow-800 p-4 rounded shadow">
                <h3 class="font-semibold">Advertencias de planificación:</h3>
                <ul class="list-disc pl-5 text-sm mt-2">
                    @foreach ($erroresPlanillas as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="py-2">
            @if (!empty($erroresPlanillas))
                <div class="mb-4 bg-yellow-100 text-yellow-800 p-4 rounded shadow">
                    <h3 class="font-semibold">Advertencias de planificación:</h3>
                    <ul class="list-disc pl-5 text-sm mt-2">
                        @foreach ($erroresPlanillas as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <!-- Panel de filtros colapsable -->
            <div class="mt-2 bg-white shadow rounded-lg overflow-hidden">
                <!-- Header del panel (siempre visible) -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-3 py-2 flex items-center justify-between cursor-pointer hover:from-blue-700 hover:to-blue-800 transition-all"
                    onclick="toggleFiltros()">
                    <div class="flex items-center gap-2 text-white">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span class="font-semibold text-sm">Filtros de planillas</span>
                        <!-- Indicador de filtros activos -->
                        <span id="filtrosActivosBadge"
                            class="hidden bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-0.5 rounded-full"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Botón resetear -->
                        <button type="button" id="limpiarResaltado"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                            title="Restablecer filtros" onclick="event.stopPropagation()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                            </svg>
                        </button>
                        <!-- Flecha de expandir/colapsar -->
                        <svg id="filtrosChevron" class="w-5 h-5 text-white transform transition-transform duration-200"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <!-- Contenido del panel (colapsable) -->
                <div id="panelFiltros" class="overflow-hidden transition-all duration-300" style="max-height: 0;">
                    <div class="p-3 bg-gray-50">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7 gap-2">
                            <!-- Filtro por Cliente -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-1 text-xs">Cliente</label>
                                <input type="text" id="filtroCliente" placeholder="Buscar..."
                                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Filtro por Código Cliente -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-1 text-xs">Cód. Cliente</label>
                                <input type="text" id="filtroCodCliente" placeholder="Buscar..."
                                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Filtro por Obra -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-1 text-xs">Obra</label>
                                <input type="text" id="filtroObra" placeholder="Buscar..."
                                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Filtro por Código Obra -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-1 text-xs">Cód. Obra</label>
                                <input type="text" id="filtroCodObra" placeholder="Buscar..."
                                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Filtro por Código Planilla -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-1 text-xs">Cód.
                                    Planilla</label>
                                <input type="text" id="filtroCodigoPlanilla" placeholder="Buscar..."
                                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Filtro por fecha de entrega -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-1 text-xs">F. Entrega</label>
                                <input type="date" id="filtroFechaEntrega"
                                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Filtro por estado -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-1 text-xs">Estado</label>
                                <select id="filtroEstado"
                                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todos</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="fabricando">Fabricando</option>
                                    <option value="completada">Completada</option>
                                </select>
                            </div>
                        </div>

                        <!-- Control de Turnos -->
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-gray-700 font-semibold text-xs">⏰ Turnos Activos</label>
                                <span class="text-xs text-gray-500">Haz clic para activar/desactivar</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                @foreach($turnosLista as $turno)
                                    <button
                                        type="button"
                                        data-turno-id="{{ $turno->id }}"
                                        data-turno-nombre="{{ $turno->nombre }}"
                                        class="turno-toggle-btn px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200 border-2 {{ $turno->activo ? 'bg-green-500 text-white border-green-600 hover:bg-green-600' : 'bg-gray-200 text-gray-600 border-gray-300 hover:bg-gray-300' }}"
                                        onclick="toggleTurno({{ $turno->id }}, '{{ $turno->nombre }}')"
                                        title="{{ $turno->activo ? 'Desactivar' : 'Activar' }} turno {{ $turno->nombre }}">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="turno-icon">{{ $turno->activo ? '✓' : '✕' }}</span>
                                            <span class="turno-nombre">{{ ucfirst($turno->nombre) }}</span>
                                        </div>
                                        @if($turno->hora_inicio && $turno->hora_fin)
                                            <div class="text-[10px] opacity-75 mt-0.5">
                                                {{ substr($turno->hora_inicio, 0, 5) }}-{{ substr($turno->hora_fin, 0, 5) }}
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Indicador de resultados -->
                        <div id="filtrosActivos" class="mt-2 text-xs text-blue-700 hidden">
                            <span class="font-semibold">📊</span>
                            <span id="textoFiltrosActivos"></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Por esta versión con transición -->
            <div id="contenedor-calendario" class="bg-white shadow rounded-lg p-2 transition-all duration-300 relative">
                <!-- Botones en esquina superior izquierda -->
                <div class="absolute top-4 left-4 z-10 flex gap-2">
                    <!-- Botón de optimizar planillas -->
                    <button onclick="abrirModalOptimizar()" id="optimizar-btn"
                        title="Optimizar planillas con retraso"
                        class="px-3 py-2 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2 group">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span class="text-sm font-medium hidden md:inline">Optimizar Planillas</span>
                    </button>

                    <!-- Botón de balancear carga -->
                    <button onclick="abrirModalBalanceo()" id="balancear-btn"
                        title="Balancear carga entre máquinas"
                        class="px-3 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2 group">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                        </svg>
                        <span class="text-sm font-medium hidden md:inline">Balancear Carga</span>
                    </button>
                </div>

                <!-- Botón de pantalla completa en esquina superior derecha -->
                <button onclick="toggleFullScreen()" id="fullscreen-btn"
                    title="Pantalla completa (F11)"
                    class="absolute top-4 right-4 z-10 px-3 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2 group">
                    <svg id="fullscreen-icon-expand" class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4">
                        </path>
                    </svg>
                    <svg id="fullscreen-icon-collapse" class="w-5 h-5 hidden transition-transform group-hover:scale-110" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25">
                        </path>
                    </svg>
                    <span id="fullscreen-text" class="text-sm font-medium hidden md:inline">Expandir</span>
                </button>

                <div id="calendario" data-calendar-type="maquinas" class="w-full"></div>
            </div>
        </div>

        <!-- Panel lateral para elementos -->
        <div id="panel_elementos"
            class="fixed top-0 right-0 h-full w-80 bg-white shadow-2xl transform translate-x-full transition-all duration-300 ease-in-out z-50 flex flex-col">

            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 shadow-md">
                <div class="flex justify-between items-center mb-3">
                    <div>
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Elementos
                        </h3>
                        <p class="text-sm opacity-90" id="panel_codigo"></p>
                    </div>
                    <button id="cerrar_panel" class="hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-all duration-200 transform hover:scale-110">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <button id="btn_marcar_revisada" class="w-full bg-white hover:bg-gray-100 text-blue-700 font-semibold py-2 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Marcar como revisada
                </button>
            </div>

            <div id="panel_lista"
                class="flex-1 overflow-y-auto p-4 space-y-3
                   [&::-webkit-scrollbar]:w-2
                   [&::-webkit-scrollbar-track]:bg-gray-200
                   [&::-webkit-scrollbar-thumb]:bg-blue-600
                   [&::-webkit-scrollbar-thumb]:rounded-full">
            </div>
        </div>

        <div id="panel_overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden transition-opacity duration-300 z-40"
            style="pointer-events: none;"></div>

        <!-- Indicador de posición al arrastrar -->
        <div id="indicador_posicion"
            class="fixed bg-blue-600 text-white rounded-full shadow-lg font-bold hidden z-[99999] pointer-events-none"
            style="display: none; width: 48px; height: 48px; line-height: 48px; text-align: center; font-size: 20px;">
            <span id="numero_posicion">1</span>
        </div>


        <!-- Modal para ver figura dibujada -->
        <div id="modal-dibujo" class="hidden fixed inset-0 flex justify-center items-center z-[9999] bg-black bg-opacity-50">
            <div class="bg-white rounded-lg p-5 w-3/4 max-w-4xl max-h-[90vh] overflow-auto relative shadow-lg border border-gray-300 m-4">
                <button id="cerrar-modal" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <h3 class="text-lg font-semibold mb-4">Figura del Elemento</h3>
                <div class="flex justify-center items-center">
                    <canvas id="canvas-dibujo" width="800" height="600" class="border border-gray-300 max-w-full"></canvas>
                </div>
            </div>
        </div>

        <!-- Scripts externos -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
        <script src="{{ asset('js/elementosJs/figuraElemento.js') }}"></script>
        <script src="{{ asset('js/multiselect-elementos.js') }}"></script>

        <!-- Modal Cambiar Estado -->
        <div id="modalEstado" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
                <div class="bg-blue-600 text-white px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold">Cambiar Estado de Máquina</h3>
                    <p id="nombreMaquinaEstado" class="text-sm opacity-90"></p>
                </div>
                <div class="p-6">
                    <label class="block text-gray-700 font-medium mb-3">Selecciona el nuevo estado:</label>
                    <div class="space-y-2">
                        <button onclick="cambiarEstado('activa')"
                            class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-lg flex items-center justify-center gap-2 transition-colors">
                            <span class="text-xl">🟢</span>
                            <span class="font-medium">Activa</span>
                        </button>
                        <button onclick="cambiarEstado('averiada')"
                            class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-lg flex items-center justify-center gap-2 transition-colors">
                            <span class="text-xl">🔴</span>
                            <span class="font-medium">Averiada</span>
                        </button>
                        <button onclick="cambiarEstado('mantenimiento')"
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-3 rounded-lg flex items-center justify-center gap-2 transition-colors">
                            <span class="text-xl">🛠️</span>
                            <span class="font-medium">Mantenimiento</span>
                        </button>
                        <button onclick="cambiarEstado('pausa')"
                            class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-3 rounded-lg flex items-center justify-center gap-2 transition-colors">
                            <span class="text-xl">⏸️</span>
                            <span class="font-medium">Pausa</span>
                        </button>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end">
                    <button onclick="cerrarModalEstado()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Redistribuir Cola -->
        <div id="modalRedistribuir"
            class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
                <div class="bg-orange-600 text-white px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold">Redistribuir Cola de Trabajo</h3>
                    <p id="nombreMaquinaRedistribuir" class="text-sm opacity-90"></p>
                </div>
                <div class="p-6">
                    <div class="mb-4 bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <p class="text-sm text-orange-800">
                            <strong>⚠️ Atención:</strong> Esta acción redistribuirá los elementos pendientes de
                            esta
                            máquina en las otras máquinas disponibles, siguiendo las reglas de asignación
                            automática.
                        </p>
                    </div>
                    <label class="block text-gray-700 font-medium mb-3">Selecciona qué redistribuir:</label>
                    <div class="space-y-2">
                        <button onclick="redistribuir('primeros')"
                            class="w-full bg-orange-400 hover:bg-orange-500 text-white px-4 py-3 rounded-lg flex items-center justify-start gap-3 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                            <div class="text-left">
                                <div class="font-medium">Los primeros elementos</div>
                                <div class="text-xs opacity-90">Redistribuir solo los próximos en la cola</div>
                            </div>
                        </button>
                        <button onclick="redistribuir('todos')"
                            class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-3 rounded-lg flex items-center justify-start gap-3 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <div class="text-left">
                                <div class="font-medium">Todos los elementos pendientes</div>
                                <div class="text-xs opacity-90">Redistribuir toda la cola de trabajo</div>
                            </div>
                        </button>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end">
                    <button onclick="cerrarModalRedistribuir()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Confirmación Previa de Redistribución -->
        <div id="modalConfirmacionRedistribucion"
            class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 my-8 max-h-[90vh] flex flex-col">
                <div class="bg-orange-600 text-white px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold">Confirmar Redistribución</h3>
                    <p id="mensajeConfirmacionRedistribucion" class="text-sm opacity-90"></p>
                </div>

                <!-- Lista de elementos a redistribuir -->
                <div class="flex-1 overflow-y-auto p-6">
                    <h4 class="font-semibold text-gray-800 mb-3">Elementos que serán redistribuidos:</h4>
                    <div id="listaElementosRedistribuir" class="space-y-2"></div>
                </div>

                <!-- Botones de acción -->
                <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end gap-3 border-t border-gray-200">
                    <button onclick="cerrarModalConfirmacionRedistribucion()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button onclick="confirmarRedistribucion()"
                        class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition-colors">
                        Confirmar Redistribución
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Resultados de Redistribución -->
        <div id="modalResultados"
            class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 my-8">
                <div class="bg-green-600 text-white px-6 py-4 rounded-t-lg sticky top-0">
                    <h3 class="text-lg font-semibold">✅ Redistribución Completada</h3>
                    <p id="mensajeResultados" class="text-sm opacity-90"></p>
                </div>

                <!-- Resumen por máquina -->
                <div class="p-6 border-b border-gray-200">
                    <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Resumen por Máquina
                    </h4>
                    <div id="resumenMaquinas" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>

                <!-- Detalle de elementos -->
                <div class="p-6">
                    <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Detalle de Elementos Redistribuidos
                    </h4>
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        ID</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Marca
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Ø</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Peso
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Planilla</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Máquina
                                        Anterior</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Nueva
                                        Máquina</th>
                                </tr>
                            </thead>
                            <tbody id="detalleElementos" class="bg-white divide-y divide-gray-200">
                                <!-- Se llenará dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-between items-center sticky bottom-0">
                    <button onclick="descargarReporte()"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Descargar Reporte
                    </button>
                    <button onclick="cerrarModalResultados()"
                        class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Selector de Máquina -->
        <div id="modalSelectorMaquina"
            class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div
                class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[80vh] overflow-hidden flex flex-col">
                <div id="selectorHeader" class="bg-blue-600 text-white px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold" id="selectorTitulo">Seleccionar Máquina</h3>
                    <p class="text-sm opacity-90">Elige la máquina sobre la que quieres realizar la acción</p>
                </div>
                <div class="p-6 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3" id="listaMaquinas">
                        <!-- Se llenará dinámicamente con las máquinas -->
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end border-t">
                    <button onclick="cerrarModalSelectorMaquina()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Optimizar Planillas -->
        <div id="modalOptimizar"
            class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl mx-4 my-8 max-h-[90vh] flex flex-col">
                <div class="bg-purple-600 text-white px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Optimizar Planillas con Retraso
                    </h3>
                    <p class="text-sm opacity-90 mt-1">Redistribuir elementos para cumplir fechas de entrega</p>
                </div>

                <!-- Loading state -->
                <div id="optimizarLoading" class="p-12 text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                    <p class="mt-4 text-gray-600">Analizando planillas y calculando optimización...</p>
                </div>

                <!-- Content state -->
                <div id="optimizarContent" class="hidden flex-1 overflow-y-auto">
                    <!-- Estadísticas superiores -->
                    <div class="p-6 bg-gray-50 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="text-sm text-red-600 font-medium">Planillas con Retraso</div>
                                <div id="estadPlanillasRetraso" class="text-3xl font-bold text-red-700">0</div>
                            </div>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="text-sm text-blue-600 font-medium">Elementos a Mover</div>
                                <div id="estadElementosMover" class="text-3xl font-bold text-blue-700">0</div>
                            </div>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="text-sm text-green-600 font-medium">Máquinas Disponibles</div>
                                <div id="estadMaquinasDisponibles" class="text-3xl font-bold text-green-700">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de elementos -->
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Previsualización de Redistribución
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Elemento</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Planilla</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ø mm</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Material</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Peso kg</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Máquina Actual</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha Entrega</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fin Programado</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nueva Máquina</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaOptimizacion" class="bg-white divide-y divide-gray-200">
                                    <!-- Se llenará dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div id="optimizarEmpty" class="hidden p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">¡Todo está optimizado!</h3>
                    <p class="text-gray-600">No hay planillas con retraso que requieran redistribución.</p>
                </div>

                <!-- Botones de acción -->
                <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end gap-3 border-t border-gray-200">
                    <button onclick="cerrarModalOptimizar()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button id="btnAplicarOptimizacion" onclick="aplicarOptimizacion()"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2 hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Aplicar Optimización
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Balancear Carga -->
        <div id="modalBalanceo"
            class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl mx-4 my-8 max-h-[90vh] flex flex-col">
                <div class="bg-green-600 text-white px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                        </svg>
                        Balancear Carga entre Máquinas
                    </h3>
                    <p class="text-sm opacity-90 mt-1">Distribuir equitativamente el trabajo entre todas las máquinas</p>
                </div>

                <!-- Loading state -->
                <div id="balanceoLoading" class="p-12 text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
                    <p class="mt-4 text-gray-600">Analizando carga de máquinas y calculando distribución...</p>
                </div>

                <!-- Content state -->
                <div id="balanceoContent" class="hidden flex-1 overflow-y-auto">
                    <!-- Estadísticas superiores -->
                    <div class="p-6 bg-gray-50 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="text-sm text-blue-600 font-medium">Elementos a Redistribuir</div>
                                <div id="estadElementosBalanceo" class="text-3xl font-bold text-blue-700">0</div>
                            </div>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="text-sm text-green-600 font-medium">Tiempo Promedio por Máquina</div>
                                <div id="estadTiempoPromedio" class="text-3xl font-bold text-green-700">0h</div>
                            </div>
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                <div class="text-sm text-purple-600 font-medium">Máquinas Balanceadas</div>
                                <div id="estadMaquinasBalanceadas" class="text-3xl font-bold text-purple-700">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de distribución -->
                    <div class="p-6 border-b border-gray-200">
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Distribución de Carga Original
                        </h4>
                        <div id="graficoCargaOriginal" class="space-y-2">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>

                    <!-- Tabla de elementos -->
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            Movimientos Propuestos
                        </h4>
                        <div class="mb-4 flex gap-2">
                            <button onclick="seleccionarTodosBalanceo()" class="text-sm text-green-600 hover:text-green-700 font-medium">
                                Seleccionar todos
                            </button>
                            <span class="text-gray-300">|</span>
                            <button onclick="deseleccionarTodosBalanceo()" class="text-sm text-gray-600 hover:text-gray-700 font-medium">
                                Deseleccionar todos
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left">
                                            <input type="checkbox" id="checkAllBalanceo" onchange="toggleAllBalanceo(this)"
                                                class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Elemento</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Planilla</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ø mm</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tiempo</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Máquina Actual</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nueva Máquina</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Razón</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaBalanceo" class="bg-white divide-y divide-gray-200">
                                    <!-- Se llenará dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div id="balanceoEmpty" class="hidden p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">¡Carga ya balanceada!</h3>
                    <p class="text-gray-600">La distribución de trabajo entre máquinas ya es óptima.</p>
                </div>

                <!-- Botones de acción -->
                <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end gap-3 border-t border-gray-200">
                    <button onclick="cerrarModalBalanceo()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button id="btnAplicarBalanceo" onclick="aplicarBalanceo()"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2 hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Aplicar Balanceo
                    </button>
                </div>
            </div>
        </div>

        <style>
            /* Contenedor calendario */
            #contenedor-calendario {
                transition: all 0.3s ease;
                min-height: 1200px;
            }

            #contenedor-calendario.con-panel-abierto {
                width: calc(100% - 320px);
                margin-right: 320px;
            }

            body.panel-abierto #contenedor-calendario {
                margin-right: 320px;
            }

            canvas {
                height: 180px !important;
                max-height: 180px !important;
            }

            /* Panel lateral */
            #panel_elementos.abierto {
                transform: translateX(0);
            }

            /* Elementos arrastrables */
            .elemento-drag {
                background: white;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                padding: 12px;
                cursor: move;
                transition: all 0.2s;
                position: relative;
            }

            .elemento-drag:hover {
                border-color: #3b82f6;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                transform: translateY(-2px);
            }

            .elemento-drag.fc-dragging {
                opacity: 0.5;
            }

            /* Elemento seleccionado */
            .elemento-drag.seleccionado {
                border-color: #2563eb;
                background-color: #eff6ff;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
            }

            .elemento-drag.seleccionado::before {
                content: '✓';
                position: absolute;
                top: 8px;
                right: 8px;
                background-color: #2563eb;
                color: white;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 14px;
                z-index: 10;
            }

            .elemento-drag canvas {
                width: 100%;
                height: 120px;
                border: 1px solid #e5e7eb;
                border-radius: 4px;
                margin-bottom: 8px;
            }

            .elemento-info-mini {
                display: flex;
                justify-content: space-between;
                font-size: 0.875rem;
            }

            /* Secciones de máquina en el panel */
            .seccion-maquina-header {
                margin: 0 -16px 12px -16px;
            }

            .seccion-maquina-header:first-child {
                margin-top: -16px;
            }

            .seccion-maquina-header > div {
                position: sticky;
                top: 0;
                z-index: 5;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .seccion-maquina-elementos {
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-bottom: 20px;
            }

            /* Badge con contador de selección */
            .selection-badge {
                position: fixed;
                bottom: 20px;
                right: 340px;
                background: #2563eb;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                font-weight: bold;
                z-index: 100;
                display: none;
                transition: all 0.3s;
            }

            .selection-badge.show {
                display: block;
            }

            /* Highlight en recursos cuando se arrastra */
            .fc-timeline-lane.fc-resource-dragging {
                background-color: rgba(59, 130, 246, 0.2) !important;
            }

            .fc-tooltip {
                pointer-events: none;
                transition: opacity 0.1s ease-in-out;
                position: absolute;
                z-index: 9999;
            }

            .fc-event {
                width: 100% !important;
                max-width: 100% !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            /* Asegurar que los eventos se ajusten al contenedor de la celda */
            .fc-timegrid-event-harness {
                left: 0 !important;
                right: 0 !important;
            }

            .fc-timegrid-event {
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
            }

            /* ===== EVENTOS SIN REVISAR ===== */
            .evento-sin-revisar {
                opacity: 0.7 !important;
                background-color: #9e9e9e !important;
                border: 2px dashed #757575 !important;
                cursor: not-allowed !important;
            }

            .evento-sin-revisar .fc-event-title {
                font-style: italic !important;
            }

            /* Tooltip/Hover para eventos sin revisar */
            .evento-sin-revisar:hover {
                opacity: 0.85 !important;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3) !important;
            }

            /* Eventos revisados (normales) */
            .evento-revisado {
                opacity: 1 !important;
                cursor: pointer !important;
            }

            /* ===== TURNOS - FONDOS DE COLOR ===== */
            .turno-manana {
                background-color: rgba(254, 240, 138, 0.5) !important;
                /* Amarillo brillante */
            }

            .turno-tarde {
                background-color: rgba(252, 211, 77, 0.5) !important;
                /* Naranja/Amarillo intenso */
            }

            .turno-noche {
                background-color: rgba(147, 197, 253, 0.5) !important;
                /* Azul claro */
            }

            /* ===== ETIQUETAS DE TIEMPO - HORAS NORMALES ===== */
            .slot-label-wrapper {
                position: relative;
                padding: 1px 3px;
                min-height: 22px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .hora-text {
                font-size: 9px;
                font-weight: 600;
                color: #1f2937;
                text-align: center;
            }

            /* ===== ETIQUETAS DE TURNOS CON FECHA ===== */
            .turno-con-fecha {
                position: relative;
                padding: 3px 2px;
                min-height: 40px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                margin: -1px -3px;
                border-radius: 3px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            }

            /* Fecha dentro del turno */
            .fecha-turno {
                color: white;
                font-size: 7px;
                font-weight: 700;
                text-align: center;
                margin-bottom: 1px;
                letter-spacing: 0.2px;
                line-height: 1;
            }

            /* Hora dentro del turno */
            .turno-con-fecha .hora-text {
                color: white;
                font-size: 10px;
                font-weight: 700;
                margin-bottom: 1px;
            }

            /* Etiqueta del tipo de turno */
            .turno-label {
                display: inline-block;
                font-size: 7px;
                font-weight: bold;
                color: white;
                padding: 1px 3px;
                background: rgba(255, 255, 255, 0.25);
                border-radius: 2px;
                text-align: center;
                white-space: nowrap;
            }

            /* ===== LÍNEAS SEPARADORAS ===== */
            /* Línea fuerte para inicio de turnos (aplicada dinámicamente) */
            .fc-timegrid-slot.turno-inicio {
                border-top: 4px solid #3b82f6 !important;
            }

            /* ===== AJUSTES ADICIONALES ===== */
            /* Mejorar visibilidad del axis */
            .fc-timegrid-axis {
                background-color: #f9fafb !important;
            }

            /* Asegurar que las etiquetas no se corten */
            .fc-timegrid-slot-label-frame {
                overflow: visible !important;
            }

            /* Filtros para resaltado de eventos */
            /* Estilos para resaltado de eventos - CON COLOR DE FONDO */
            .fc-event.evento-resaltado {
                background-color: #3b82f6 !important;
                z-index: 100 !important;
                transform: scale(1.05);
                transition: all 0.2s ease;
            }

            .fc-event.evento-opaco {
                opacity: 0.25 !important;
                filter: grayscale(50%);
                transition: all 0.2s ease;
            }

            .fc-event.evento-resaltado:hover {
                transform: scale(1.08);
                background-color: #2563eb !important;
            }

            /* Animación de pulso para eventos resaltados */
            @keyframes pulso-resaltado {

                0%,
                100% {
                    background-color: #3b82f6;
                }

                50% {
                    background-color: #2563eb;
                }
            }

            .fc-event.evento-resaltado.pulsando {
                animation: pulso-resaltado 1.5s ease-in-out infinite;
            }

            /* ===== HEADER FIJO DEL CALENDARIO ===== */

            /* Hacer sticky el header de recursos (columna izquierda) */
            .fc-datagrid-header {
                position: sticky !important;
                top: 0 !important;
                z-index: 3 !important;
                background-color: white !important;
            }

            /* ===== HEADER FIJO DEL CALENDARIO ===== */

            /* Hacer sticky el header de recursos (columna izquierda) */
            .fc-datagrid-header {
                position: sticky !important;
                top: 0 !important;
                z-index: 3 !important;
                background-color: white !important;
            }

            /* Hacer sticky el header de las columnas de tiempo */
            .fc-col-header {
                position: sticky !important;
                top: 0 !important;
                z-index: 3 !important;
                background-color: white !important;
            }

            /* Para resourceTimeGrid: hacer sticky toda la sección del header */
            .fc-resource-timeline .fc-scrollgrid-section-header > tr > * {
                position: sticky !important;
                top: 0 !important;
                z-index: 3 !important;
                background-color: white !important;
            }

            /* Sombra para mejorar visibilidad del header sticky */
            .fc-datagrid-header::after,
            .fc-col-header::after {
                content: '';
                position: absolute;
                bottom: -2px;
                left: 0;
                right: 0;
                height: 2px;
                background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), transparent);
                pointer-events: none;
            }

            /* Indicador de posición durante arrastre */
            #indicador_posicion {
                transition: left 0.05s ease-out, top 0.05s ease-out;
            }

            #indicador_posicion span {
                display: block;
                width: 100%;
                height: 100%;
                line-height: 48px;
            }
        </style>
        <script data-navigate-once>
            function inicializarCalendarioMaquinas() {
                const maquinas = @json($resources);
                const planillas = @json($planillasEventos);
                const cargaTurnoResumen = @json($cargaTurnoResumen);
                const planDetallado = @json($planDetallado);
                const realDetallado = @json($realDetallado);
                const turnosActivos = @json($turnosLista);

                // Variable global para el calendario
                let calendar;

                // Referencias al indicador de posición
                const indicadorPosicion = document.getElementById('indicador_posicion');
                const numeroPosicion = document.getElementById('numero_posicion');

                // Variable para trackear elemento que se arrastra desde el panel
                let elementoArrastrandose = null;
                let eventoArrastrandose = null;
                let mostrarIndicador = false;
                let tooltipsDeshabilitados = false;

                // 🎯 Listener GLOBAL de mousemove para el indicador
                document.addEventListener('mousemove', function(e) {
                    if (mostrarIndicador && indicadorPosicion) {
                        indicadorPosicion.style.left = (e.clientX + 20) + 'px';
                        indicadorPosicion.style.top = (e.clientY - 20) + 'px';
                        indicadorPosicion.style.display = 'block';
                        indicadorPosicion.classList.remove('hidden');
                    }
                });


                // Inicializar FullCalendar
                calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
                    schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                    initialView: 'resourceTimeGrid7Days',
                    nextDayThreshold: '00:00:00',
                    allDaySlot: false,
                    resourceLabelContent: function(arg) {
                        return {
                            html: `
                            <div class="flex flex-col gap-2 w-full py-1">
                                <a href="/maquinas/${arg.resource.id}"
                                   wire:navigate
                                   class="text-blue-600 hover:text-blue-800 hover:underline font-semibold maquina-nombre"
                                   data-maquina-id="${arg.resource.id}"
                                   data-maquina-titulo="${arg.resource.title}">${arg.resource.title}</a>
                                <div class="flex gap-1 justify-center">
                                    <button
                                        onclick="event.stopPropagation(); abrirModalEstado(${arg.resource.id})"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs"
                                        title="Cambiar estado"
                                        data-maquina-id="${arg.resource.id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        onclick="event.stopPropagation(); abrirModalRedistribuir(${arg.resource.id})"
                                        class="bg-orange-500 hover:bg-orange-600 text-white px-2 py-1 rounded text-xs"
                                        title="Redistribuir cola"
                                        data-maquina-id="${arg.resource.id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `
                        };
                    },
                    views: {
                        resourceTimeGrid7Days: {
                            type: 'resourceTimeGrid',
                            duration: {
                                days: 1
                            },
                            slotMinTime: '00:00:00',
                            slotMaxTime: '360:00:00',
                            slotDuration: '01:00:00',
                            dayHeaderContent: function(arg) {
                                return '';
                            },
                            buttonText: '15 días'
                        }
                    },
                    locale: 'es',
                    timeZone: 'Europe/Madrid',
                    initialDate: "{{ $initialDate }}",
                    // ✅ CAMBIO: Usar endpoints dinámicos en lugar de datos estáticos
                    resources: {
                        url: '{{ route('api.produccion.recursos') }}',
                        failure: function(error) {
                            console.error('❌ Error al cargar recursos:', error);
                            alert('Error al cargar las máquinas. Revisa la consola.');
                        }
                    },
                    resourceOrder: false,
                    events: {
                        url: '{{ route('api.produccion.eventos') }}',
                        failure: function(error) {
                            console.error('❌ Error al cargar eventos:', error);
                            alert('Error al cargar los eventos. Revisa la consola.');
                        }
                    },
                    height: 900, // Altura fija para permitir scroll en la página
                    scrollTime: '06:00:00',
                    editable: true,
                    eventResizableFromStart: false,
                    eventDurationEditable: false,
                    droppable: true, // ✅ Habilitar drop de elementos externos

                    headerToolbar: {
                        left: '',
                        center: 'title',
                        right: ''
                    },

                    // 🎯 CLAVE: Configurar recepción de elementos externos
                    eventReceive: async function(info) {
                        try {
                            // Ocultar indicador al soltar
                            mostrarIndicador = false;
                            if (indicadorPosicion) {
                                indicadorPosicion.classList.add('hidden');
                                indicadorPosicion.style.display = 'none';
                            }

                            const elementoDiv = document.querySelector(
                                `.elemento-drag[data-elemento-id="${info.event.extendedProps.elementoId}"]`
                            );

                            if (!elementoDiv) {
                                info.revert();
                                return;
                            }

                            // Obtener datos de los elementos a mover (uno o varios)
                            const dataMovimiento = window.MultiSelectElementos.getDataElementosParaMover(
                                elementoDiv);
                            console.log('📊 dataMovimiento:', dataMovimiento);


                            // Validar que tengamos la máquina original
                            if (!dataMovimiento.maquinaOriginal || isNaN(dataMovimiento.maquinaOriginal)) {
                                console.log('❌ No se pudo obtener maquina original');
                                console.error('Error: No se pudo obtener la máquina original del elemento',
                                    elementoDiv);
                                info.revert();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'No se pudo determinar la máquina original del elemento'
                                });
                                return;
                            }

                            const maquinaDestinoId = parseInt(info.event.getResources()[0].id);
                            const maquinaDestinoNombre = info.event.getResources()[0].title;
                            console.log('🎯 Máquina destino:', maquinaDestinoId, maquinaDestinoNombre);

                            // Calcular la posición correcta donde se soltó el elemento
                            const eventosOrdenados = calendar.getEvents()
                                .filter(ev => ev.getResources().some(r => r.id == maquinaDestinoId))
                                .sort((a, b) => a.start - b.start);

                            // Encontrar posición basada en el tiempo donde se soltó
                            let nuevaPosicion = 1;
                            for (let i = 0; i < eventosOrdenados.length; i++) {
                                if (info.event.start < eventosOrdenados[i].start) {
                                    nuevaPosicion = i + 1;
                                    break;
                                }
                                nuevaPosicion = i + 2;
                            }

                            // Confirmar movimiento
                            const mensaje = dataMovimiento.cantidad > 1 ?
                                `¿Mover ${dataMovimiento.cantidad} elementos a <strong>${maquinaDestinoNombre}</strong>?` :
                                `¿Mover elemento a <strong>${maquinaDestinoNombre}</strong>?`;

                            console.log('❓ Mostrando primer Swal de confirmación');
                            const resultado = await Swal.fire({
                                title: dataMovimiento.cantidad > 1 ? '¿Mover elementos?' :
                                    '¿Mover elemento?',
                                html: mensaje,
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, mover',
                                cancelButtonText: 'Cancelar'
                            });

                            console.log('✅ Resultado primer Swal:', resultado);

                            if (!resultado.isConfirmed) {
                                console.log('❌ Usuario canceló el primer Swal');
                                info.revert();
                                return;
                            }

                            console.log('✅ Usuario confirmó movimiento, iniciando try-catch');

                            try {
                                console.log('🚀 Enviando petición a /planillas/reordenar');
                                const res = await fetch('/planillas/reordenar', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').content
                                    },
                                    body: JSON.stringify({
                                        id: dataMovimiento.planillaId,
                                        maquina_id: maquinaDestinoId,
                                        maquina_origen_id: dataMovimiento.maquinaOriginal,
                                        nueva_posicion: nuevaPosicion,
                                        elementos_id: dataMovimiento.elementosIds
                                    })
                                });

                                // Parsear respuesta JSON independientemente del código HTTP
                                let data;
                                try {
                                    data = await res.json();
                                } catch (jsonError) {
                                    console.error('❌ Error parseando JSON:', jsonError);
                                    throw new Error('Error al procesar la respuesta del servidor');
                                }

                                // 🔍 IMPORTANTE: Verificar requiresNuevaPosicionConfirmation ANTES de verificar success
                                // Esto es necesario porque el backend devuelve 422 con requiresNuevaPosicionConfirmation
                                if (data.requiresNuevaPosicionConfirmation) {
                                    console.log('✅ Mostrando diálogo de confirmación con 3 botones');
                                    const resultadoConfirmacion = await Swal.fire({
                                        title: 'Posición ya existe',
                                        html: data.message +
                                            '<br><br><strong>¿Qué deseas hacer?</strong>',
                                        icon: 'question',
                                        showCancelButton: true,
                                        showDenyButton: true,
                                        confirmButtonText: 'Crear nueva posición',
                                        denyButtonText: 'Usar posición existente',
                                        cancelButtonText: 'Cancelar',
                                        confirmButtonColor: '#10b981',
                                        denyButtonColor: '#3b82f6',
                                        cancelButtonColor: '#6b7280',
                                        reverseButtons: false,
                                        allowOutsideClick: false,
                                        buttonsStyling: true
                                    });

                                    if (resultadoConfirmacion.isConfirmed) {
                                        // Usuario quiere crear una nueva posición
                                        const res2 = await fetch('/planillas/reordenar', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector(
                                                    'meta[name="csrf-token"]').content
                                            },
                                            body: JSON.stringify({
                                                id: dataMovimiento.planillaId,
                                                maquina_id: maquinaDestinoId,
                                                maquina_origen_id: dataMovimiento
                                                    .maquinaOriginal,
                                                nueva_posicion: nuevaPosicion,
                                                elementos_id: dataMovimiento.elementosIds,
                                                crear_nueva_posicion: true
                                            })
                                        });

                                        const data2 = await res2.json();

                                        if (!res2.ok || !data2.success) {
                                            throw new Error(data2.message || 'Error al mover elementos');
                                        }

                                        // Remover elementos del panel
                                        window.MultiSelectElementos.removerElementosDelPanel(dataMovimiento
                                            .elementosIds);

                                        // Remover el evento temporal que se creó
                                        info.event.remove();

                                        // Recargar eventos desde el servidor
                                        calendar.refetchEvents();

                                        const Toast = Swal.mixin({
                                            toast: true,
                                            position: 'top-end',
                                            showConfirmButton: false,
                                            timer: 1500,
                                            timerProgressBar: true,
                                        });
                                        Toast.fire({
                                            icon: 'success',
                                            title: 'Nueva posición creada'
                                        });

                                    } else if (resultadoConfirmacion.isDenied) {
                                        // Usuario quiere mover a la posición existente
                                        const res2 = await fetch('/planillas/reordenar', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector(
                                                    'meta[name="csrf-token"]').content
                                            },
                                            body: JSON.stringify({
                                                id: dataMovimiento.planillaId,
                                                maquina_id: maquinaDestinoId,
                                                maquina_origen_id: dataMovimiento
                                                    .maquinaOriginal,
                                                nueva_posicion: nuevaPosicion,
                                                elementos_id: dataMovimiento.elementosIds,
                                                crear_nueva_posicion: false
                                            })
                                        });

                                        const data2 = await res2.json();

                                        if (!res2.ok || !data2.success) {
                                            throw new Error(data2.message || 'Error al mover elementos');
                                        }

                                        // Remover elementos del panel
                                        window.MultiSelectElementos.removerElementosDelPanel(dataMovimiento
                                            .elementosIds);

                                        // Remover el evento temporal que se creó
                                        info.event.remove();

                                        // Recargar eventos desde el servidor
                                        calendar.refetchEvents();

                                        const Toast = Swal.mixin({
                                            toast: true,
                                            position: 'top-end',
                                            showConfirmButton: false,
                                            timer: 1500,
                                            timerProgressBar: true,
                                        });
                                        Toast.fire({
                                            icon: 'success',
                                            title: 'Elementos movidos a posición existente'
                                        });

                                    } else {
                                        // Usuario canceló
                                        info.revert();
                                    }

                                    return;
                                }

                                // Solo verificar errores si NO es el caso de requiresNuevaPosicionConfirmation
                                if ((!res.ok || !data.success) && !data.requiresNuevaPosicionConfirmation) {
                                    throw new Error(data.message || 'Error al mover elementos');
                                }

                                // Remover elementos del panel
                                window.MultiSelectElementos.removerElementosDelPanel(dataMovimiento
                                    .elementosIds);

                                // Remover el evento temporal que se creó
                                info.event.remove();

                                // Recargar eventos desde el servidor
                                calendar.refetchEvents();

                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 1500,
                                    timerProgressBar: true,
                                });
                                Toast.fire({
                                    icon: 'success',
                                    title: dataMovimiento.cantidad > 1 ?
                                        `${dataMovimiento.cantidad} elementos movidos` :
                                        'Elemento movido'
                                });

                            } catch (error) {
                                console.error('❌ Error en eventReceive (try interno):', error);
                                console.error('Stack trace:', error.stack);
                                info.revert();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: error.message || 'No se pudo mover el elemento'
                                });
                            }
                        } catch (globalError) {
                            console.error('💥💥💥 ERROR GLOBAL EN eventReceive:', globalError);
                            console.error('💥 Stack:', globalError.stack);
                            console.error('💥 Message:', globalError.message);
                            info.revert();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error crítico',
                                html: `<strong>Error:</strong> ${globalError.message}<br><br><pre>${globalError.stack}</pre>`
                            });
                        }
                    },

                    slotLaneClassNames: function(arg) {
                        const hour = arg.date.getHours();
                        if (hour > 6 && hour <= 14) return ['turno-manana'];
                        if (hour >= 14 && hour <= 22) return ['turno-tarde'];
                        if (hour >= 22 || hour <= 6) return ['turno-noche'];
                        return [];
                    },

                    slotLabelContent: function(arg) {
                        // Inicializar contador de slots si no existe
                        if (!calendar._slotCounter) {
                            calendar._slotCounter = 0;
                            calendar._lastViewStart = null;
                        }

                        // Reiniciar contador si cambia la vista
                        const currentViewStart = calendar.view.currentStart.getTime();
                        if (calendar._lastViewStart !== currentViewStart) {
                            calendar._slotCounter = 0;
                            calendar._lastViewStart = currentViewStart;
                        }

                        // Obtener el inicio de la vista
                        const viewStart = new Date(calendar.view.currentStart);

                        // Calcular la hora absoluta desde el inicio basándose en el contador
                        const horaAbsoluta = calendar._slotCounter;
                        const diasCompletos = Math.floor(horaAbsoluta / 24);
                        const horaDelDia = horaAbsoluta % 24;
                        const minutos = 0; // Los slots son de hora en hora

                        // Calcular la fecha real
                        const fechaReal = new Date(viewStart);
                        fechaReal.setDate(fechaReal.getDate() + diasCompletos);
                        fechaReal.setHours(horaDelDia, minutos, 0, 0);

                        // Incrementar contador para el siguiente slot
                        calendar._slotCounter++;

                        // Formatear la hora para mostrar
                        const timeText =
                            `${horaDelDia.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;

                        // Determinar si este slot corresponde a un inicio de turno basado en turnos activos
                        let esTurno = false;
                        let nombreTurno = '';
                        let fechaMostrar = new Date(fechaReal);
                        let turnoEncontrado = null;

                        // Buscar si esta hora corresponde al inicio de algún turno activo
                        for (const turno of turnosActivos) {
                            if (!turno.activo || !turno.hora_inicio) continue;

                            const [horaInicio, minInicio] = turno.hora_inicio.split(':').map(Number);

                            if (horaDelDia === horaInicio && minutos === minInicio) {
                                esTurno = true;
                                turnoEncontrado = turno;

                                // Determinar emoji según el nombre del turno
                                let emoji = '⏰';
                                const nombreLower = turno.nombre.toLowerCase();
                                if (nombreLower.includes('mañana') || nombreLower.includes('manana')) {
                                    emoji = '☀️';
                                } else if (nombreLower.includes('tarde')) {
                                    emoji = '🌤️';
                                } else if (nombreLower.includes('noche')) {
                                    emoji = '🌙';
                                }

                                nombreTurno = `${emoji} ${turno.nombre}`;

                                // Si el turno tiene offset negativo, mostrar fecha del día siguiente
                                if (turno.offset_dias_inicio < 0) {
                                    fechaMostrar = new Date(fechaReal.getTime());
                                    fechaMostrar.setDate(fechaMostrar.getDate() + 1);
                                }

                                break;
                            }
                        }

                        let contenido = '';

                        if (esTurno) {
                            // Formatear fecha para mostrar
                            const dia = fechaMostrar.getDate().toString().padStart(2, '0');
                            const mes = (fechaMostrar.getMonth() + 1).toString().padStart(2, '0');
                            const año = fechaMostrar.getFullYear();
                            const nombreDia = fechaMostrar.toLocaleDateString('es-ES', {
                                weekday: 'short'
                            }).toUpperCase();
                            const fechaFormateada = `${dia}/${mes}/${año}`;

                            contenido = `
            <div class="turno-con-fecha">
                <div class="fecha-turno">${nombreDia}<br>${fechaFormateada}</div>
                <div class="hora-text">${timeText}</div>
                <span class="turno-label">${nombreTurno}</span>
            </div>`;
                        } else {
                            // Horas normales sin fecha
                            contenido = `
            <div class="slot-label-wrapper">
                <div class="hora-text">${timeText}</div>
            </div>`;
                        }

                        return {
                            html: contenido
                        };
                    },
                    eventClick: async function(info) {
                        console.log('🔍 Event clicked:', info.event);
                        console.log('🔍 Event ID:', info.event.id);
                        console.log('🔍 Event extendedProps:', info.event.extendedProps);

                        // Intentar obtener planillaId de múltiples fuentes
                        let planillaId = null;

                        // Opción 1: Del ID del evento (formato: "planilla-123")
                        if (info.event.id && typeof info.event.id === 'string' && info.event.id.includes('-')) {
                            const partes = info.event.id.split('-');
                            if (partes.length > 1 && partes[1]) {
                                planillaId = partes[1];
                            }
                        }

                        // Opción 2: De extendedProps.planilla_id
                        if (!planillaId && info.event.extendedProps?.planilla_id) {
                            planillaId = info.event.extendedProps.planilla_id;
                        }

                        // Opción 3: De extendedProps.id
                        if (!planillaId && info.event.extendedProps?.id) {
                            planillaId = info.event.extendedProps.id;
                        }

                        const codigoPlanilla = info.event.extendedProps.codigo ?? info.event.title;

                        console.log('🔍 planillaId extraído:', planillaId);
                        console.log('🔍 codigoPlanilla:', codigoPlanilla);

                        if (!planillaId) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Sin planilla',
                                text: 'No se pudo identificar la planilla.',
                            });
                            return;
                        }

                        try {
                            const response = await fetch(`/elementos/por-ids?planilla_id=${planillaId}`);
                            const elementos = await response.json();
                            console.log('✅ Elementos cargados:', elementos.length);
                            mostrarPanelElementos(elementos, planillaId, codigoPlanilla);
                        } catch (error) {
                            console.error('❌ Error al cargar elementos:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudieron cargar los elementos'
                            });
                        }
                    },

                    eventContent: function(arg) {
                        const progreso = arg.event.extendedProps.progreso;
                        const eventId = arg.event.id || arg.event._def.publicId;

                        if (typeof progreso === 'number') {
                            return {
                                html: `
                                <div class="w-full px-1 py-0.5 text-xs font-semibold text-white" data-event-id="${eventId}">
                                    <div class="mb-0.5 truncate" title="${arg.event.title}">${arg.event.title}</div>
                                    <div class="w-full h-2 bg-gray-300 rounded overflow-hidden">
                                        <div class="h-2 bg-blue-500 rounded transition-all duration-500" style="width: ${progreso}%; min-width: 1px;"></div>
                                    </div>
                                </div>`
                            };
                        }

                        return {
                            html: `
                            <div class="truncate w-full text-xs font-semibold text-white px-2 py-1 rounded"
                                 style="background-color: ${arg.event.backgroundColor};"
                                 title="${arg.event.title}">
                                ${arg.event.title}
                            </div>`
                        };
                    },

                    // 🎯 Eventos para mostrar indicador de posición al arrastrar
                    eventDragStart: function(info) {
                        eventoArrastrandose = info.event;
                        mostrarIndicador = true;
                        tooltipsDeshabilitados = true;

                        // Ocultar todos los tooltips existentes
                        document.querySelectorAll('.fc-tooltip').forEach(t => t.style.display = 'none');

                        // Calcular posición inicial
                        const recursoId = info.event.getResources()[0]?.id;
                        if (recursoId) {
                            const eventosOrdenados = calendar.getEvents()
                                .filter(ev => ev.getResources().some(r => r.id == recursoId) && ev.id !==
                                    info.event.id)
                                .sort((a, b) => a.start - b.start);

                            let posicion = 1;
                            for (let i = 0; i < eventosOrdenados.length; i++) {
                                if (info.event.start < eventosOrdenados[i].start) {
                                    posicion = i + 1;
                                    break;
                                }
                                posicion = i + 2;
                            }
                            if (numeroPosicion) {
                                numeroPosicion.textContent = posicion;
                            }
                        }
                    },

                    eventAllow: function(dropInfo, draggedEvent) {
                        // Este se ejecuta constantemente mientras arrastras
                        if (mostrarIndicador && draggedEvent) {
                            const recursoId = dropInfo.resource?.id;

                            if (recursoId) {
                                const eventosOrdenados = calendar.getEvents()
                                    .filter(ev => ev.getResources().some(r => r.id == recursoId) && ev
                                        .id !== draggedEvent.id)
                                    .sort((a, b) => a.start - b.start);

                                // Usar el tiempo de dropInfo para calcular posición
                                const tiempoDestino = dropInfo.start;
                                let posicionDestino = 1;

                                for (let i = 0; i < eventosOrdenados.length; i++) {
                                    if (tiempoDestino < eventosOrdenados[i].start) {
                                        posicionDestino = i + 1;
                                        break;
                                    }
                                    posicionDestino = i + 2;
                                }

                                if (numeroPosicion) {
                                    numeroPosicion.textContent = posicionDestino;
                                }
                            }
                        }
                        return true; // Permitir el drop
                    },

                    eventDragStop: function(info) {
                        eventoArrastrandose = null;
                        mostrarIndicador = false;
                        tooltipsDeshabilitados = false;
                        if (indicadorPosicion) {
                            indicadorPosicion.classList.add('hidden');
                            indicadorPosicion.style.display = 'none';
                        }

                        // Limpiar tooltips duplicados que puedan haberse creado
                        document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());
                    },

                    eventDrop: async function(info) {
                        // Limpiar tooltips residuales
                        document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());

                        const planillaId = info.event.id.split('-')[1];
                        const codigoPlanilla = info.event.extendedProps.codigo ?? info.event.title;
                        const maquinaOrigenId = info.oldResource?.id ?? info.event.getResources()[0]?.id;
                        const maquinaDestinoId = info.newResource?.id ?? info.event.getResources()[0]?.id;
                        const elementosId = info.event.extendedProps.elementos_id || [];

                        const resultado = await Swal.fire({
                            title: '¿Reordenar planilla?',
                            html: `¿Quieres mover la planilla <strong>${codigoPlanilla}</strong> ${maquinaOrigenId !== maquinaDestinoId ? 'a otra máquina' : 'en la misma máquina'}?`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, reordenar',
                            cancelButtonText: 'Cancelar'
                        });

                        if (!resultado.isConfirmed) {
                            info.revert();
                            return;
                        }

                        const eventosOrdenados = calendar.getEvents()
                            .filter(ev => ev.getResources().some(r => r.id == maquinaDestinoId))
                            .sort((a, b) => a.start - b.start);
                        const nuevaPosicion = eventosOrdenados.findIndex(ev => ev.id === info.event.id) + 1;

                        try {
                            const res = await fetch('/planillas/reordenar', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    id: planillaId,
                                    maquina_id: maquinaDestinoId,
                                    maquina_origen_id: maquinaOrigenId,
                                    nueva_posicion: nuevaPosicion,
                                    elementos_id: elementosId,
                                })
                            });

                            const data = await res.json();

                            // 🔍 Verificar si requiere confirmación de nueva posición
                            if (data.requiresNuevaPosicionConfirmation) {

                                const confirmacion = await Swal.fire({
                                    title: 'Posición ya existe',
                                    html: data.message +
                                        '<br><br><strong>¿Qué deseas hacer?</strong>',
                                    icon: 'question',
                                    showCancelButton: true,
                                    showDenyButton: true,
                                    confirmButtonText: 'Crear nueva posición',
                                    denyButtonText: 'Usar posición existente',
                                    cancelButtonText: 'Cancelar',
                                    confirmButtonColor: '#10b981',
                                    denyButtonColor: '#3b82f6',
                                    cancelButtonColor: '#6b7280',
                                    reverseButtons: false,
                                    allowOutsideClick: false,
                                    buttonsStyling: true
                                });

                                if (confirmacion.isConfirmed) {
                                    // Crear nueva posición
                                    const res2 = await fetch('/planillas/reordenar', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]').content
                                        },
                                        body: JSON.stringify({
                                            id: planillaId,
                                            maquina_id: maquinaDestinoId,
                                            maquina_origen_id: maquinaOrigenId,
                                            nueva_posicion: nuevaPosicion,
                                            elementos_id: elementosId,
                                            crear_nueva_posicion: true
                                        })
                                    });

                                    const data2 = await res2.json();
                                    if (!res2.ok || !data2.success) {
                                        throw new Error(data2.message || 'Error al crear nueva posición');
                                    }

                                    // Recargar eventos desde el servidor
                                    calendar.refetchEvents();

                                    Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true
                                    }).fire({
                                        icon: 'success',
                                        title: 'Nueva posición creada'
                                    });

                                } else if (confirmacion.isDenied) {
                                    // Usar posición existente
                                    const res2 = await fetch('/planillas/reordenar', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]').content
                                        },
                                        body: JSON.stringify({
                                            id: planillaId,
                                            maquina_id: maquinaDestinoId,
                                            maquina_origen_id: maquinaOrigenId,
                                            nueva_posicion: nuevaPosicion,
                                            elementos_id: elementosId,
                                            usar_posicion_existente: true
                                        })
                                    });

                                    const data2 = await res2.json();
                                    if (!res2.ok || !data2.success) {
                                        throw new Error(data2.message ||
                                            'Error al mover a posición existente');
                                    }

                                    // Recargar eventos desde el servidor
                                    calendar.refetchEvents();

                                    Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true
                                    }).fire({
                                        icon: 'success',
                                        title: 'Planilla movida a posición existente'
                                    });

                                } else {
                                    // Cancelar
                                    info.revert();
                                }

                                return;
                            }

                            if (!res.ok || !data.success) {
                                throw new Error(data.message || 'Error al reordenar');
                            }

                            // Recargar eventos desde el servidor
                            calendar.refetchEvents();

                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                            });
                            Toast.fire({
                                icon: 'success',
                                title: 'Planilla reordenada'
                            });

                        } catch (error) {
                            info.revert();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.message || 'No se pudo reordenar'
                            });
                        }
                    },

                    eventDidMount: function(info) {
                        const props = info.event.extendedProps;
                        const tooltip = document.createElement('div');
                        tooltip.className = 'fc-tooltip';

                        // ⚠️ Información de revisión
                        let estadoRevision = '';
                        if (props.revisada === false || props.revisada === 0) {
                            estadoRevision =
                                '<br><span class="text-red-400 font-bold">⚠️ SIN REVISAR - No iniciar producción</span>';
                        } else if (props.revisada === true || props.revisada === 1) {
                            estadoRevision =
                                `<br><span class="text-green-400">✅ Revisada por ${props.revisada_por || 'N/A'}</span>`;
                        }

                        // Debug: mostrar elementos de este evento
                        const elementosDebug = props.codigos_elementos ? props.codigos_elementos.join(', ') : 'N/A';
                        const maquinaId = info.event.getResources()[0]?.id || 'N/A';

                        tooltip.innerHTML = `
                        <div class="bg-gray-900 text-white text-xs rounded px-2 py-1 shadow-md max-w-xs">
                            <strong>${info.event.title}</strong><br>
                            Obra: ${props.obra}<br>
                            Estado producción: ${props.estado}<br>
                            Máquina: <span class="text-blue-300">${maquinaId}</span><br>
                            Elementos: <span class="text-purple-300">${elementosDebug}</span><br>
                            Duración: <span class="text-cyan-300">${props.duracion_horas || 0} hrs</span><br>
                            Fin programado: <span class="text-yellow-300">${props.fin_programado}</span><br>
                            Fecha estimada entrega: <span class="text-green-300">${props.fecha_entrega}</span>${estadoRevision}
                        </div>`;
                        tooltip.style.display = 'none';
                        document.body.appendChild(tooltip);

                        info.el.addEventListener('mouseenter', function(e) {
                            if (!tooltipsDeshabilitados) {
                                tooltip.style.left = e.pageX + 10 + 'px';
                                tooltip.style.top = e.pageY + 10 + 'px';
                                tooltip.style.display = 'block';
                            }
                        });
                        info.el.addEventListener('mousemove', function(e) {
                            if (!tooltipsDeshabilitados) {
                                tooltip.style.left = e.pageX + 10 + 'px';
                                tooltip.style.top = e.pageY + 10 + 'px';
                            }
                        });
                        info.el.addEventListener('mouseleave', function() {
                            tooltip.style.display = 'none';
                        });
                    }
                });
                calendar.render();
                window.calendar = calendar;

                // 🎯 Aplicar líneas separadoras de turnos dinámicamente
                window.aplicarLineasTurnos = function() {
                    // Limpiar líneas anteriores
                    document.querySelectorAll('.fc-timegrid-slot.turno-inicio').forEach(el => {
                        el.classList.remove('turno-inicio');
                    });

                    // Aplicar líneas para cada turno activo
                    turnosActivos.forEach(turno => {
                        if (!turno.activo || !turno.hora_inicio) return;

                        const horaInicio = turno.hora_inicio.substring(0, 5); // "06:00" de "06:00:00"
                        const selector = `.fc-timegrid-slot[data-time="${horaInicio}:00"]`;
                        const slots = document.querySelectorAll(selector);

                        slots.forEach(slot => {
                            slot.classList.add('turno-inicio');
                        });
                    });
                }

                // Aplicar inicialmente
                setTimeout(() => {
                    window.aplicarLineasTurnos();
                }, 100);

                // Re-aplicar cuando cambie la vista
                calendar.on('datesSet', function() {
                    setTimeout(() => {
                        window.aplicarLineasTurnos();
                    }, 100);
                });

                // 🎯 STICKY HEADER: Hacer que el header se quede fijo al hacer scroll en la PÁGINA
                setTimeout(() => {
                    const headerResources = document.querySelector('.fc-datagrid-header');
                    const headerTime = document.querySelector('.fc-col-header');
                    const headerSection = document.querySelector('.fc-scrollgrid-section-header');

                    if (!headerSection) {
                        return;
                    }

                    // Obtener la posición inicial del header
                    const headerInitialTop = headerSection.getBoundingClientRect().top + window.pageYOffset;

                    // Escuchar scroll en la página (window)
                    window.addEventListener('scroll', function() {
                        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                        // Si el scroll pasa la posición del header, hacerlo sticky
                        if (scrollTop > headerInitialTop - 10) {
                            if (headerResources) {
                                headerResources.style.position = 'fixed';
                                headerResources.style.top = '0px';
                                headerResources.style.zIndex = '1000';
                                headerResources.style.backgroundColor = 'white';
                                headerResources.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                            }
                            if (headerTime) {
                                headerTime.style.position = 'fixed';
                                headerTime.style.top = '0px';
                                headerTime.style.zIndex = '1000';
                                headerTime.style.backgroundColor = 'white';
                                headerTime.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                            }
                            if (headerSection) {
                                headerSection.style.position = 'fixed';
                                headerSection.style.top = '0px';
                                headerSection.style.zIndex = '999';
                                headerSection.style.backgroundColor = 'white';
                                headerSection.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                                headerSection.style.width = '100%';
                            }
                        } else {
                            // Restaurar posición normal
                            if (headerResources) {
                                headerResources.style.position = '';
                                headerResources.style.top = '';
                                headerResources.style.zIndex = '';
                                headerResources.style.boxShadow = '';
                            }
                            if (headerTime) {
                                headerTime.style.position = '';
                                headerTime.style.top = '';
                                headerTime.style.zIndex = '';
                                headerTime.style.boxShadow = '';
                            }
                            if (headerSection) {
                                headerSection.style.position = '';
                                headerSection.style.top = '';
                                headerSection.style.zIndex = '';
                                headerSection.style.boxShadow = '';
                                headerSection.style.width = '';
                            }
                        }
                    }, { passive: true });
                }, 500);

                // 🎯 PANTALLA COMPLETA
                window.isFullScreen = window.isFullScreen || false;

                window.toggleFullScreen = function() {
                    const container = document.getElementById('produccion-maquinas-container');
                    const sidebar = document.querySelector('[class*="sidebar"]') || document.querySelector('aside');
                    const header = document.querySelector('nav');
                    const breadcrumbs = document.querySelector('[class*="breadcrumb"]');
                    const expandIcon = document.getElementById('fullscreen-icon-expand');
                    const collapseIcon = document.getElementById('fullscreen-icon-collapse');
                    const fullscreenBtn = document.getElementById('fullscreen-btn');
                    const fullscreenText = document.getElementById('fullscreen-text');

                    // Verificar que existen los elementos necesarios
                    if (!container || !expandIcon || !collapseIcon || !fullscreenBtn) {
                        console.warn('Elementos de fullscreen no encontrados');
                        return;
                    }

                    if (!isFullScreen) {
                        // Entrar en pantalla completa
                        if (sidebar) sidebar.style.display = 'none';
                        if (header) header.style.display = 'none';
                        if (breadcrumbs) breadcrumbs.style.display = 'none';

                        container.classList.add('fixed', 'inset-0', 'z-50', 'bg-gray-50', 'overflow-auto');
                        container.style.padding = '1rem';

                        expandIcon.classList.add('hidden');
                        collapseIcon.classList.remove('hidden');
                        fullscreenBtn.title = 'Salir de pantalla completa (ESC)';
                        if (fullscreenText) fullscreenText.textContent = 'Contraer';

                        window.isFullScreen = true;

                        // Atajo de teclado ESC para salir
                        document.addEventListener('keydown', handleEscKey);
                    } else {
                        // Salir de pantalla completa
                        if (sidebar) sidebar.style.display = '';
                        if (header) header.style.display = '';
                        if (breadcrumbs) breadcrumbs.style.display = '';

                        container.classList.remove('fixed', 'inset-0', 'z-50', 'bg-gray-50', 'overflow-auto');
                        container.style.padding = '';

                        expandIcon.classList.remove('hidden');
                        collapseIcon.classList.add('hidden');
                        fullscreenBtn.title = 'Pantalla completa (F11)';
                        if (fullscreenText) fullscreenText.textContent = 'Expandir';

                        window.isFullScreen = false;

                        document.removeEventListener('keydown', handleEscKey);
                    }

                    // Re-renderizar el calendario para ajustar su tamaño
                    if (window.calendar) {
                        setTimeout(() => {
                            window.calendar.updateSize();
                        }, 100);
                    }
                }

                function handleEscKey(e) {
                    if (e.key === 'Escape' && isFullScreen) {
                        toggleFullScreen();
                    }
                }

                // También permitir F11 como alternativa
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'F11') {
                        e.preventDefault();
                        toggleFullScreen();
                    }
                });

                // 🎯 Listener para calcular posición al hacer drop de elementos externos
                const calendarioEl = document.getElementById('calendario');
                let ultimoRecursoDetectado = null;
                let ultimaTiempoDetectado = null;

                calendarioEl.addEventListener('dragover', function(e) {
                    if (!elementoArrastrandose) return;

                    e.preventDefault();

                    const elementosBajoMouse = document.elementsFromPoint(e.clientX, e.clientY);

                    // Buscar cualquier elemento que tenga data-resource-id
                    let resourceId = null;
                    for (const el of elementosBajoMouse) {
                        if (el.dataset.resourceId) {
                            resourceId = el.dataset.resourceId;
                            break;
                        }
                        let parent = el.parentElement;
                        while (parent && !resourceId) {
                            if (parent.dataset.resourceId) {
                                resourceId = parent.dataset.resourceId;
                                break;
                            }
                            parent = parent.parentElement;
                        }
                        if (resourceId) break;
                    }

                    if (!resourceId) {
                        if (numeroPosicion) {
                            numeroPosicion.textContent = '?';
                        }
                        return;
                    }

                    // Obtener todos los eventos de esa máquina ordenados
                    const eventosOrdenados = calendar.getEvents()
                        .filter(ev => ev.getResources().some(r => r.id == resourceId))
                        .sort((a, b) => a.start - b.start);

                    // Buscar evento más cercano bajo el cursor para estimar posición
                    let eventoMasCercano = null;
                    let distanciaMinima = Infinity;

                    eventosOrdenados.forEach(evento => {
                        const eventoEls = document.querySelectorAll(
                            `.fc-event[data-event-id="${evento.id}"]`);
                        eventoEls.forEach(eventoEl => {
                            const rect = eventoEl.getBoundingClientRect();
                            const distancia = Math.abs(e.clientY - (rect.top + rect.height /
                                2));
                            if (distancia < distanciaMinima) {
                                distanciaMinima = distancia;
                                eventoMasCercano = evento;
                            }
                        });
                    });

                    let posicionCalculada = 1;

                    if (eventoMasCercano) {
                        const indexCercano = eventosOrdenados.findIndex(ev => ev.id === eventoMasCercano.id);
                        const eventoEl = document.querySelector(
                            `.fc-event[data-event-id="${eventoMasCercano.id}"]`);
                        if (eventoEl) {
                            const rect = eventoEl.getBoundingClientRect();
                            const mitadAltura = rect.top + (rect.height / 2);

                            if (e.clientY < mitadAltura) {
                                posicionCalculada = indexCercano + 1;
                            } else {
                                posicionCalculada = indexCercano + 2;
                            }
                        } else {
                            posicionCalculada = indexCercano + 1;
                        }
                    } else {
                        posicionCalculada = 1;
                    }

                    if (numeroPosicion) {
                        numeroPosicion.textContent = posicionCalculada;
                    }
                });

                // Función para actualizar eventos sin recargar
                function actualizarEventosSinRecargar(eventosNuevos, maquinasAfectadas = null) {
                    document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());

                    if (!eventosNuevos || !Array.isArray(eventosNuevos)) {
                        console.warn('No se recibieron eventos para actualizar');
                        return;
                    }

                    // Si no se pasan máquinas afectadas, extraerlas de los eventos
                    if (!maquinasAfectadas) {
                        maquinasAfectadas = [...new Set(eventosNuevos.map(e => String(e.resourceId)))];
                    } else {
                        // Asegurar que sean strings
                        maquinasAfectadas = maquinasAfectadas.map(id => String(id));
                    }

                    calendar.getEvents().forEach(evento => {
                        const recursos = evento.getResources();
                        const perteneceAMaquinaAfectada = recursos.some(recurso =>
                            maquinasAfectadas.includes(String(recurso.id))
                        );

                        if (perteneceAMaquinaAfectada) {
                            evento.remove();
                        }
                    });

                    eventosNuevos.forEach(eventoData => {
                        calendar.addEvent(eventoData);
                    });
                }

                // Variable global para guardar el planillaId actual del panel
                let planillaIdActualPanel = null;

                // Función para mostrar panel de elementos
                function mostrarPanelElementos(elementos, planillaId, codigo) {
                    console.log('📋 mostrarPanelElementos llamado con:', {
                        elementos: elementos?.length || 0,
                        planillaId,
                        codigo
                    });

                    // Validar planillaId
                    if (!planillaId) {
                        console.error('❌ planillaId es undefined o null');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo identificar la planilla',
                        });
                        return;
                    }

                    const panel = document.getElementById('panel_elementos');
                    const overlay = document.getElementById('panel_overlay');
                    const lista = document.getElementById('panel_lista');
                    const contenedorCalendario = document.getElementById('contenedor-calendario');
                    const panelCodigo = document.getElementById('panel_codigo');

                    // Verificar que existen los elementos necesarios
                    if (!panel || !overlay || !lista || !contenedorCalendario) {
                        console.warn('Elementos del panel no encontrados');
                        return;
                    }

                    // Guardar el planillaId actual
                    planillaIdActualPanel = planillaId;
                    console.log('✅ planillaIdActualPanel establecido:', planillaIdActualPanel);

                    if (panelCodigo) panelCodigo.textContent = codigo;
                    lista.innerHTML = '';

                    // Agrupar elementos por maquina_id
                    const elementosPorMaquina = elementos.reduce((acc, elemento) => {
                        const maquinaId = elemento.maquina_id || 'sin_maquina';
                        if (!acc[maquinaId]) {
                            acc[maquinaId] = {
                                nombre: elemento.maquina ? elemento.maquina.nombre : 'Sin asignar',
                                elementos: []
                            };
                        }
                        acc[maquinaId].elementos.push(elemento);
                        return acc;
                    }, {});

                    // Crear secciones por máquina
                    Object.entries(elementosPorMaquina).forEach(([maquinaId, grupo]) => {
                        // Crear header de la sección
                        const seccionHeader = document.createElement('div');
                        seccionHeader.className = 'seccion-maquina-header';
                        seccionHeader.innerHTML = `
                            <div class="bg-blue-500 text-white px-4 py-2 rounded-md font-semibold text-sm mb-3">
                                ${grupo.nombre} (${grupo.elementos.length})
                            </div>
                        `;
                        lista.appendChild(seccionHeader);

                        // Crear contenedor de elementos de esta máquina
                        const seccionElementos = document.createElement('div');
                        seccionElementos.className = 'seccion-maquina-elementos mb-4';

                        grupo.elementos.forEach(elemento => {
                            const div = document.createElement('div');
                            div.className = 'elemento-drag fc-event';
                            div.draggable = true;
                            div.title = ''; // Evitar tooltip nativo del navegador

                            div.dataset.elementoId = elemento.id;
                            div.dataset.planillaId = planillaId;
                            div.dataset.maquinaOriginal = elemento.maquina_id;

                            div.dataset.event = JSON.stringify({
                                title: elemento.codigo,
                                extendedProps: {
                                    elementoId: elemento.id,
                                    planillaId: planillaId,
                                    maquinaOriginal: elemento.maquina_id
                                },
                                duration: '01:00'
                            });

                            const canvasId = `canvas-panel-${elemento.id}`;

                            div.innerHTML = `
                            <canvas id="${canvasId}" width="240" height="120" draggable="false"></canvas>
                        `;

                            seccionElementos.appendChild(div);

                            // ✅ Evento de clic para selección múltiple
                            div.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                window.MultiSelectElementos.toggleSeleccion(div);
                            });

                            setTimeout(() => {
                                window.dibujarFiguraElemento(
                                    canvasId,
                                    elemento.dimensiones,
                                    elemento.peso,
                                    elemento.diametro,
                                    elemento.barras
                                );
                            }, 10);
                        });

                        lista.appendChild(seccionElementos);
                    });

                    // Configurar FullCalendar.Draggable con timeout para asegurar que se ejecuta
                    setTimeout(() => {
                        const draggable = new FullCalendar.Draggable(lista, {
                            itemSelector: '.elemento-drag',
                            eventData: function(eventEl) {
                                return JSON.parse(eventEl.dataset.event);
                            }
                        });

                        // Usar eventos nativos del DOM
                        lista.addEventListener('mousedown', function(e) {
                            const target = e.target.closest('.elemento-drag');
                            if (target) {
                                setTimeout(() => {
                                    elementoArrastrandose = target;
                                    mostrarIndicador = true;
                                    tooltipsDeshabilitados = true;
                                    if (numeroPosicion) {
                                        numeroPosicion.textContent = '?';
                                    }
                                    document.querySelectorAll('.fc-tooltip').forEach(t => t
                                        .style.display = 'none');
                                }, 50);
                            }
                        });

                        document.addEventListener('mouseup', function(e) {
                            if (elementoArrastrandose) {
                                setTimeout(() => {
                                    elementoArrastrandose = null;
                                    mostrarIndicador = false;
                                    tooltipsDeshabilitados = false;
                                    if (indicadorPosicion) {
                                        indicadorPosicion.classList.add('hidden');
                                        indicadorPosicion.style.display = 'none';
                                    }
                                    document.querySelectorAll('.fc-tooltip').forEach(t => t
                                        .remove());
                                }, 100);
                            }
                        });
                    }, 100);

                    // ✅ Ajustar calendario
                    panel.classList.add('abierto');
                    overlay.classList.remove('hidden');
                    contenedorCalendario.classList.add('con-panel-abierto');
                    document.body.classList.add('panel-abierto');

                    setTimeout(() => {
                        calendar.updateSize();
                    }, 300);
                }

                function cerrarPanel() {
                    console.log('🚪 Cerrando panel...');

                    // Limpiar selección múltiple
                    window.MultiSelectElementos.limpiarSelecciones();
                    document.body.classList.remove('panel-abierto');

                    // Limpiar planillaId actual
                    planillaIdActualPanel = null;
                    console.log('🧹 planillaIdActualPanel limpiado');

                    const panelElementos = document.getElementById('panel_elementos');
                    const panelOverlay = document.getElementById('panel_overlay');
                    const contenedorCalendario = document.getElementById('contenedor-calendario');

                    if (panelElementos) panelElementos.classList.remove('abierto');
                    if (panelOverlay) panelOverlay.classList.add('hidden');
                    if (contenedorCalendario) contenedorCalendario.classList.remove('con-panel-abierto');

                    setTimeout(() => {
                        if (calendar) calendar.updateSize();
                    }, 300);
                }

                const cerrarPanelBtn = document.getElementById('cerrar_panel');
                if (cerrarPanelBtn) {
                    cerrarPanelBtn.addEventListener('click', cerrarPanel);
                }

                // Evento para marcar planilla como revisada
                const btnMarcarRevisada = document.getElementById('btn_marcar_revisada');
                if (btnMarcarRevisada) {
                    btnMarcarRevisada.addEventListener('click', async function() {
                        console.log('🔍 planillaIdActualPanel:', planillaIdActualPanel);

                        if (!planillaIdActualPanel) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Sin planilla',
                                text: 'No hay planilla seleccionada',
                                confirmButtonColor: '#3085d6',
                            });
                            return;
                        }

                        const resultado = await Swal.fire({
                            title: '¿Marcar como revisada?',
                            text: '¿Estás seguro de que quieres marcar esta planilla como revisada?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, marcar como revisada',
                            cancelButtonText: 'Cancelar'
                        });

                        if (!resultado.isConfirmed) {
                            return;
                        }

                        try {
                            const response = await fetch(`/planillas/${planillaIdActualPanel}/marcar-revisada`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            });

                            const data = await response.json();

                            if (data.success) {
                                await Swal.fire({
                                    icon: 'success',
                                    title: 'Éxito',
                                    text: 'Planilla marcada como revisada correctamente',
                                    timer: 2000,
                                    showConfirmButton: false
                                });

                                cerrarPanel();

                                // Recargar eventos del calendario
                                if (window.calendar) {
                                    calendar.refetchEvents();
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.mensaje || 'No se pudo marcar como revisada',
                                    confirmButtonColor: '#d33',
                                });
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al marcar como revisada: ' + error.message,
                                confirmButtonColor: '#d33',
                            });
                        }
                    });
                }

                // Overlay ya no captura clics (pointer-events: none) para permitir interacción con calendario

                // Cerrar panel al hacer clic fuera del panel (en el área del calendario)
                document.addEventListener('click', function(e) {
                    const panel = document.getElementById('panel_elementos');
                    const panelAbierto = panel.classList.contains('abierto');

                    if (panelAbierto && !panel.contains(e.target) && !e.target.closest('.fc-event')) {
                        // Solo cerrar si se hace clic fuera del panel y no en un elemento arrastrable
                        const clickEnCalendario = e.target.closest('#contenedor-calendario');
                        if (clickEnCalendario && !e.target.closest('.elemento-drag')) {
                            cerrarPanel();
                        }
                    }
                });

                // ================================
                // SISTEMA DE FILTROS DE RESALTADO
                // ================================

                let filtrosActivos = {
                    cliente: null,
                    codCliente: null,
                    obra: null,
                    codObra: null,
                    codigoPlanilla: null,
                    fechaEntrega: null,
                    estado: null
                };
                /**
                 * Parsea una fecha desde string DD/MM/YYYY HH:mm a objeto Date
                 */
                function parsearFechaEvento(fechaStr) {
                    if (!fechaStr || fechaStr === '—') {
                        console.log('📅 parsearFechaEvento: fecha vacía o inválida', fechaStr);
                        return null;
                    }

                    // Formato: "DD/MM/YYYY HH:mm"
                    const partes = fechaStr.match(/(\d{2})\/(\d{2})\/(\d{4})/);
                    if (!partes) {
                        console.warn('⚠️ parsearFechaEvento: no se pudo parsear', fechaStr);
                        return null;
                    }

                    const [_, dia, mes, anio] = partes;
                    const fecha = new Date(anio, mes - 1, dia);

                    console.log(`📅 parsearFechaEvento: "${fechaStr}" → ${fecha.toLocaleDateString('es-ES')}`);
                    return fecha;
                }

                /**
                 * Compara dos fechas sin considerar la hora
                 */
                function fechasIguales(fecha1, fecha2) {
                    if (!fecha1 || !fecha2) {
                        console.log('⚖️ fechasIguales: alguna fecha es null', {
                            fecha1,
                            fecha2
                        });
                        return false;
                    }

                    const iguales = fecha1.getDate() === fecha2.getDate() &&
                        fecha1.getMonth() === fecha2.getMonth() &&
                        fecha1.getFullYear() === fecha2.getFullYear();

                    console.log('⚖️ fechasIguales:', {
                        fecha1: fecha1.toLocaleDateString('es-ES'),
                        fecha2: fecha2.toLocaleDateString('es-ES'),
                        resultado: iguales ? '✅ IGUALES' : '❌ DIFERENTES'
                    });

                    return iguales;
                }

                /**
                 * Determina si un evento cumple con los filtros activos
                 */
                function cumpleFiltros(evento) {
                    const props = evento.extendedProps;

                    // Filtro por cliente (búsqueda parcial, case-insensitive)
                    if (filtrosActivos.cliente && filtrosActivos.cliente !== '') {
                        const cliente = (props.cliente || '').toLowerCase();
                        const filtro = filtrosActivos.cliente.toLowerCase();
                        const cumple = cliente.includes(filtro);
                        console.log('Cliente filtro:', filtrosActivos.cliente);
                        console.log('Cliente evento:', props.cliente);
                        console.log('Cumple cliente:', cumple ? '✅' : '❌');
                        if (!cumple) return false;
                    }

                    // Filtro por código cliente (búsqueda parcial, case-insensitive)
                    if (filtrosActivos.codCliente && filtrosActivos.codCliente !== '') {
                        const codCliente = (props.cod_cliente || '').toLowerCase();
                        const filtro = filtrosActivos.codCliente.toLowerCase();
                        const cumple = codCliente.includes(filtro);
                        console.log('Código Cliente filtro:', filtrosActivos.codCliente);
                        console.log('Código Cliente evento:', props.cod_cliente);
                        console.log('Cumple código cliente:', cumple ? '✅' : '❌');
                        if (!cumple) return false;
                    }

                    // Filtro por obra (búsqueda parcial, case-insensitive)
                    if (filtrosActivos.obra && filtrosActivos.obra !== '') {
                        const obra = (props.obra || '').toLowerCase();
                        const filtro = filtrosActivos.obra.toLowerCase();
                        const cumple = obra.includes(filtro);
                        console.log('Obra filtro:', filtrosActivos.obra);
                        console.log('Obra evento:', props.obra);
                        console.log('Cumple obra:', cumple ? '✅' : '❌');
                        if (!cumple) return false;
                    }

                    // Filtro por código obra (búsqueda parcial, case-insensitive)
                    if (filtrosActivos.codObra && filtrosActivos.codObra !== '') {
                        const codObra = (props.cod_obra || '').toLowerCase();
                        const filtro = filtrosActivos.codObra.toLowerCase();
                        const cumple = codObra.includes(filtro);
                        console.log('Código Obra filtro:', filtrosActivos.codObra);
                        console.log('Código Obra evento:', props.cod_obra);
                        console.log('Cumple código obra:', cumple ? '✅' : '❌');
                        if (!cumple) return false;
                    }

                    // Filtro por código planilla (búsqueda parcial, case-insensitive)
                    if (filtrosActivos.codigoPlanilla && filtrosActivos.codigoPlanilla !== '') {
                        const codigoPlanilla = (props.codigo_planilla || evento.extendedProps.codigo || evento.title || '').toLowerCase();
                        const filtro = filtrosActivos.codigoPlanilla.toLowerCase();
                        const cumple = codigoPlanilla.includes(filtro);
                        console.log('🔍 Código Planilla filtro:', filtrosActivos.codigoPlanilla);
                        console.log('🔍 Código Planilla evento (codigo_planilla):', props.codigo_planilla);
                        console.log('🔍 Código Planilla evento (codigo):', evento.extendedProps.codigo);
                        console.log('🔍 Código Planilla evento (title):', evento.title);
                        console.log('🔍 Valor final usado:', codigoPlanilla);
                        console.log('🔍 Cumple código planilla:', cumple ? '✅' : '❌');
                        if (!cumple) return false;
                    }

                    // Filtro por fecha de entrega
                    if (filtrosActivos.fechaEntrega) {
                        const fechaEvento = parsearFechaEvento(props.fecha_entrega);
                        const cumple = fechasIguales(fechaEvento, filtrosActivos.fechaEntrega);
                        console.log('Fecha filtro:', filtrosActivos.fechaEntrega.toLocaleDateString('es-ES'));
                        console.log('Fecha evento:', props.fecha_entrega);
                        console.log('Cumple fecha:', cumple ? '✅' : '❌');
                        if (!cumple) return false;
                    }

                    // Filtro por estado
                    if (filtrosActivos.estado && filtrosActivos.estado !== '') {
                        const cumple = props.estado === filtrosActivos.estado;
                        console.log('Estado filtro:', filtrosActivos.estado);
                        console.log('Estado evento:', props.estado);
                        console.log('Cumple estado:', cumple ? '✅' : '❌');

                        if (!cumple) return false;
                    }

                    return true;
                }

                /**
                 * Aplica el resaltado a los eventos del calendario
                 */
                function aplicarResaltadoEventos() {
                    console.clear();
                    console.log('🎨 APLICANDO FILTROS');

                    const hayFiltros = Object.values(filtrosActivos).some(v => v !== null && v !== '');

                    console.log('Filtros activos:', filtrosActivos);

                    if (!hayFiltros) {
                        limpiarResaltado();
                        return;
                    }

                    setTimeout(() => {
                        // Agrupar eventos por planilla
                        const eventosPorPlanilla = {};

                        calendar.getEvents().forEach(evento => {
                            // Extraer ID de planilla del ID del evento (formato: "planilla-123-maq2-orden456")
                            const match = evento.id.match(/^planilla-(\d+)-/);
                            if (!match) {
                                console.warn('⚠️ Evento sin formato correcto:', evento.id);
                                return;
                            }

                            const planillaId = match[1];

                            if (!eventosPorPlanilla[planillaId]) {
                                eventosPorPlanilla[planillaId] = {
                                    eventos: [],
                                    props: evento.extendedProps,
                                    title: evento.extendedProps.codigo || evento.title
                                };
                            }

                            eventosPorPlanilla[planillaId].eventos.push(evento);
                        });

                        console.log('Total planillas encontradas:', Object.keys(eventosPorPlanilla).length);

                        let planillasResaltadas = 0;
                        let segmentosResaltados = 0;

                        // Evaluar cada planilla
                        Object.entries(eventosPorPlanilla).forEach(([planillaId, data]) => {
                            console.group(`📋 Planilla ${data.title}`);
                            console.log('Segmentos:', data.eventos.length);
                            console.log('Props:', data.props);

                            const cumple = cumpleFiltros(data.eventos[
                                0]); // Evaluar con el primer segmento

                            // Aplicar a TODOS los segmentos de esta planilla
                            data.eventos.forEach(evento => {
                                // Buscar TODAS las representaciones DOM de este evento
                                // Un evento puede tener múltiples elementos DOM si está en varias posiciones
                                const elementosDOM = [];

                                // Primero intentar con evento.el
                                if (evento.el) {
                                    elementosDOM.push(evento.el);
                                }

                                // Buscar todas las instancias DOM que coincidan con este evento ID
                                const todosElementos = document.querySelectorAll('.fc-event');
                                todosElementos.forEach(el => {
                                    // Verificar por fcSeg
                                    if (el.fcSeg && el.fcSeg.eventRange.def.publicId ===
                                        evento.id) {
                                        // Evitar duplicados
                                        if (!elementosDOM.includes(el)) {
                                            elementosDOM.push(el);
                                        }
                                    }
                                    // También verificar por atributos data
                                    const dataEventId = el.getAttribute(
                                            'data-event-id') ||
                                        el.querySelector('[data-event-id]')
                                        ?.getAttribute('data-event-id');
                                    if (dataEventId === evento.id && !elementosDOM
                                        .includes(el)) {
                                        elementosDOM.push(el);
                                    }
                                });

                                if (elementosDOM.length === 0) {
                                    console.warn(
                                        '⚠️ No se encontró ningún elemento DOM para evento:',
                                        evento.id);
                                    return;
                                }

                                // Aplicar clases a TODOS los elementos DOM encontrados
                                elementosDOM.forEach(elementoDOM => {
                                    // Remover clases previas
                                    elementoDOM.classList.remove('evento-resaltado',
                                        'evento-opaco', 'pulsando');

                                    if (cumple) {
                                        elementoDOM.classList.add('evento-resaltado',
                                            'pulsando');
                                        console.log('✅ Elemento resaltado:', evento.id);
                                    } else {
                                        elementoDOM.classList.add('evento-opaco');
                                        console.log('⚪ Elemento opacado:', evento.id);
                                    }
                                });

                                // Contar segmentos resaltados (no elementos DOM)
                                if (cumple) {
                                    segmentosResaltados++;
                                }
                            });

                            if (cumple) {
                                planillasResaltadas++;
                                console.log('✅ RESALTADA (todos los segmentos)');
                            } else {
                                console.log('⚪ OPACADA (todos los segmentos)');
                            }

                            console.groupEnd();
                        });

                        console.log(`━━━━━━━━━━━━━━━━`);
                        console.log(`✅ Planillas resaltadas: ${planillasResaltadas}`);
                        console.log(`📊 Segmentos resaltados: ${segmentosResaltados}`);

                        actualizarIndicadorFiltros(planillasResaltadas);

                        if (planillasResaltadas === 0) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Sin resultados',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            //  console.log('🎉 Resaltado aplicado con éxito');
                        }
                    }, 100);
                }
                /**
                 * Limpia todos los resaltados
                 */
                function limpiarResaltado() {
                    console.log('%c🧹 LIMPIANDO FILTROS', 'font-size: 14px; font-weight: bold; color: #dc2626;');

                    calendar.getEvents().forEach(evento => {
                        const elemento = evento.el;
                        if (elemento) {
                            elemento.classList.remove('evento-resaltado', 'evento-opaco', 'pulsando');
                        }
                    });

                    // Limpiar filtros
                    filtrosActivos = {
                        cliente: null,
                        codCliente: null,
                        obra: null,
                        codObra: null,
                        codigoPlanilla: null,
                        fechaEntrega: null,
                        estado: null
                    };

                    console.log('✅ Todos los resaltados eliminados');
                    console.log('✅ Filtros reseteados');

                    // Limpiar inputs
                    document.getElementById('filtroFechaEntrega').value = '';
                    document.getElementById('filtroObra').value = '';
                    document.getElementById('filtroEstado').value = '';

                    // Ocultar indicador y badge
                    document.getElementById('filtrosActivos').classList.add('hidden');
                    document.getElementById('filtrosActivosBadge').classList.add('hidden');
                }
                /**
                 * Actualiza el indicador visual de filtros activos
                 */
                function actualizarIndicadorFiltros(cantidad) {
                    const indicador = document.getElementById('filtrosActivos');
                    const texto = document.getElementById('textoFiltrosActivos');
                    const badge = document.getElementById('filtrosActivosBadge');

                    let descripcion = [];

                    if (filtrosActivos.cliente) {
                        descripcion.push(`Cliente: ${filtrosActivos.cliente}`);
                    }
                    if (filtrosActivos.codCliente) {
                        descripcion.push(`Cód.Cliente: ${filtrosActivos.codCliente}`);
                    }
                    if (filtrosActivos.obra) {
                        descripcion.push(`Obra: ${filtrosActivos.obra}`);
                    }
                    if (filtrosActivos.codObra) {
                        descripcion.push(`Cód.Obra: ${filtrosActivos.codObra}`);
                    }
                    if (filtrosActivos.codigoPlanilla) {
                        descripcion.push(`Cód.Planilla: ${filtrosActivos.codigoPlanilla}`);
                    }
                    if (filtrosActivos.fechaEntrega) {
                        descripcion.push(`Entrega: ${filtrosActivos.fechaEntrega.toLocaleDateString('es-ES')}`);
                    }
                    if (filtrosActivos.estado) {
                        descripcion.push(`Estado: ${filtrosActivos.estado}`);
                    }

                    // Actualizar texto del indicador dentro del panel
                    texto.textContent =
                        `${descripcion.join(' | ')} → ${cantidad} resultado${cantidad !== 1 ? 's' : ''}`;
                    indicador.classList.remove('hidden');

                    // Actualizar badge en el header
                    badge.textContent = cantidad;
                    badge.classList.remove('hidden');
                }


                // ================================
                // EVENT LISTENERS PARA FILTROS
                // ================================

                // Función para capturar y aplicar filtros
                function capturarYAplicarFiltros() {
                    // Capturar valores de todos los campos
                    const clienteInput = document.getElementById('filtroCliente').value.trim();
                    const codClienteInput = document.getElementById('filtroCodCliente').value.trim();
                    const obraInput = document.getElementById('filtroObra').value.trim();
                    const codObraInput = document.getElementById('filtroCodObra').value.trim();
                    const codigoPlanillaInput = document.getElementById('filtroCodigoPlanilla').value.trim();
                    const fechaInput = document.getElementById('filtroFechaEntrega').value;
                    const estadoInput = document.getElementById('filtroEstado').value;

                    // Actualizar filtros activos
                    filtrosActivos.cliente = clienteInput || null;
                    filtrosActivos.codCliente = codClienteInput || null;
                    filtrosActivos.obra = obraInput || null;
                    filtrosActivos.codObra = codObraInput || null;
                    filtrosActivos.codigoPlanilla = codigoPlanillaInput || null;
                    filtrosActivos.fechaEntrega = fechaInput ? new Date(fechaInput) : null;
                    filtrosActivos.estado = estadoInput || null;

                    // Aplicar
                    aplicarResaltadoEventos();
                }

                // Debounce para evitar ejecutar la función demasiadas veces
                let filtroTimeout;

                function aplicarFiltrosConDebounce() {
                    clearTimeout(filtroTimeout);
                    filtroTimeout = setTimeout(() => {
                        capturarYAplicarFiltros();
                    }, 300); // Esperar 300ms después de dejar de escribir
                }

                // Función para abrir/cerrar panel de filtros
                window.toggleFiltros = function() {
                    const panel = document.getElementById('panelFiltros');
                    const chevron = document.getElementById('filtrosChevron');

                    if (panel.style.maxHeight === '0px' || panel.style.maxHeight === '') {
                        // Abrir
                        panel.style.maxHeight = panel.scrollHeight + 'px';
                        chevron.style.transform = 'rotate(180deg)';
                    } else {
                        // Cerrar
                        panel.style.maxHeight = '0px';
                        chevron.style.transform = 'rotate(0deg)';
                    }
                };

                // Listeners en tiempo real para campos de texto
                ['filtroCliente', 'filtroCodCliente', 'filtroObra', 'filtroCodObra', 'filtroCodigoPlanilla'].forEach(
                    id => {
                        document.getElementById(id).addEventListener('input', aplicarFiltrosConDebounce);
                    });

                // Listeners para campos que cambian de valor inmediatamente
                document.getElementById('filtroFechaEntrega').addEventListener('change', capturarYAplicarFiltros);
                document.getElementById('filtroEstado').addEventListener('change', capturarYAplicarFiltros);

                document.getElementById('limpiarResaltado').addEventListener('click', function() {
                    // Limpiar los valores de los inputs
                    document.getElementById('filtroCliente').value = '';
                    document.getElementById('filtroCodCliente').value = '';
                    document.getElementById('filtroObra').value = '';
                    document.getElementById('filtroCodObra').value = '';
                    document.getElementById('filtroCodigoPlanilla').value = '';
                    document.getElementById('filtroFechaEntrega').value = '';
                    document.getElementById('filtroEstado').value = '';

                    // Limpiar y aplicar (esto limpiará los resaltados automáticamente)
                    limpiarResaltado();
                });

                /**
                 * 🔧 FUNCIÓN DE DEBUG - Inspeccionar un evento específico
                 * Úsala en la consola: debugEvento('nombre-del-evento')
                 */
                window.debugEvento = function(nombreEvento) {
                    console.clear();
                    console.log('%c🔍 DEBUG DE EVENTO ESPECÍFICO',
                        'font-size: 16px; font-weight: bold; color: #8b5cf6;');
                    console.log('━'.repeat(80));

                    const eventos = calendar.getEvents();
                    const evento = eventos.find(e => e.title.toLowerCase().includes(nombreEvento.toLowerCase()));

                    if (!evento) {
                        console.error(`❌ No se encontró evento con nombre: "${nombreEvento}"`);
                        console.log('📋 Eventos disponibles:');
                        eventos.forEach((e, i) => console.log(`  ${i + 1}. ${e.title}`));
                        return;
                    }

                    console.log('✅ Evento encontrado:', evento.title);
                    console.log('━'.repeat(80));

                    console.group('📋 Información completa del evento');
                    console.log('ID:', evento.id);
                    console.log('Title:', evento.title);
                    console.log('Start:', evento.start);
                    console.log('End:', evento.end);
                    console.log('Resource ID:', evento.getResources()[0]?.id);
                    console.groupEnd();

                    console.group('🔧 Extended Props');
                    Object.entries(evento.extendedProps).forEach(([key, value]) => {
                        console.log(`${key}:`, value);
                    });
                    console.groupEnd();

                    console.log('━'.repeat(80));
                    console.log('🎯 Probando contra filtros activos:');
                    cumpleFiltros(evento);
                };

                // Añade también esta función para listar todos los eventos
                window.listarEventos = function() {
                    console.clear();
                    console.log('%c📋 LISTA DE TODOS LOS EVENTOS',
                        'font-size: 16px; font-weight: bold; color: #059669;');
                    console.log('━'.repeat(80));

                    const eventos = calendar.getEvents();
                    console.log(`Total: ${eventos.length} eventos\n`);

                    eventos.forEach((e, i) => {
                        console.group(`${i + 1}. ${e.title}`);
                        console.log('Fecha entrega:', e.extendedProps.fecha_entrega);
                        console.log('Obra:', e.extendedProps.obra);
                        console.log('Estado:', e.extendedProps.estado);
                        console.groupEnd();
                    });
                };

                // ========================================
                // 🔄 SISTEMA DE ACTUALIZACIÓN EN TIEMPO REAL
                // ========================================

                let ultimoTimestamp = new Date().toISOString();
                let intervaloPolling = null;
                let calendarioVisible = true;
                let actualizacionesRecibidas = 0;

                // Detectar visibilidad de la pestaña para pausar polling
                document.addEventListener('visibilitychange', () => {
                    calendarioVisible = !document.hidden;

                    if (calendarioVisible) {
                        iniciarPolling();
                    } else {
                        detenerPolling();
                    }
                });

                function iniciarPolling() {
                    if (intervaloPolling) return; // Ya está activo

                    intervaloPolling = setInterval(async () => {
                        try {
                            const url =
                                `/produccion/maquinas/actualizaciones?timestamp=${encodeURIComponent(ultimoTimestamp)}`;

                            const response = await fetch(url, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                credentials: 'same-origin'
                            });

                            if (!response.ok) {
                                console.error(`❌ Error HTTP: ${response.status} ${response.statusText}`);
                                const text = await response.text();
                                console.error('Respuesta:', text.substring(0, 200));
                                return;
                            }

                            const data = await response.json();

                            if (data.success && data.actualizaciones && data.actualizaciones.length > 0) {
                                aplicarActualizaciones(data.actualizaciones);
                                ultimoTimestamp = data.timestamp;
                                actualizacionesRecibidas += data.total;

                                // Notificación visual
                                mostrarNotificacion(`${data.total} planilla(s) actualizada(s)`, 'info');
                            }
                        } catch (error) {
                            console.error('❌ Error al obtener actualizaciones:', error);
                            console.error('Stack:', error.stack);
                        }
                    }, 5000); // Cada 5 segundos
                }

                function detenerPolling() {
                    if (intervaloPolling) {
                        clearInterval(intervaloPolling);
                        intervaloPolling = null;
                        console.log('⏸️ Polling detenido');
                    }
                }

                function aplicarActualizaciones(actualizaciones) {
                    actualizaciones.forEach(upd => {
                        console.log('📊 POLLING: Actualización recibida', {
                            planilla_id: upd.planilla_id,
                            maquina_id: upd.maquina_id,
                            revisada: upd.revisada,
                            fecha_entrega: upd.fecha_entrega,
                            fin_programado: upd.fin_programado,
                            tiene_retraso: upd.tiene_retraso
                        });

                        // Buscar todos los eventos de esta planilla y máquina
                        const eventos = calendar.getEvents().filter(e => {
                            const eventoId = e.id || '';
                            return eventoId.includes(`planilla-${upd.planilla_id}`) &&
                                e.getResources()[0]?.id == upd.maquina_id;
                        });

                        if (eventos.length === 0) {
                            console.log(
                                `⚠️ No se encontraron eventos para planilla ${upd.planilla_id} en máquina ${upd.maquina_id}`
                            );
                            return;
                        }

                        eventos.forEach(evento => {
                            let cambios = [];

                            // 1. Actualizar progreso
                            const progresoAnterior = evento.extendedProps.progreso;
                            if (progresoAnterior !== upd.progreso) {
                                evento.setExtendedProp('progreso', upd.progreso);
                                cambios.push(`progreso: ${progresoAnterior}% → ${upd.progreso}%`);

                                // Actualizar barra de progreso visual
                                actualizarBarraProgreso(evento._def.publicId, upd.progreso);
                            }

                            // 2. Actualizar estado
                            if (evento.extendedProps.estado !== upd.estado) {
                                evento.setExtendedProp('estado', upd.estado);
                                cambios.push(`estado: ${evento.extendedProps.estado} → ${upd.estado}`);
                            }

                            // 3. Actualizar revisión y fechas
                            const revisadaAnterior = evento.extendedProps.revisada;
                            const fechaEntregaAnterior = evento.extendedProps.fecha_entrega;
                            const finProgramadoAnterior = evento.extendedProps.fin_programado;

                            let cambioRelevante = false;

                            if (revisadaAnterior !== upd.revisada) {
                                evento.setExtendedProp('revisada', upd.revisada);
                                cambios.push(`revisada: ${revisadaAnterior} → ${upd.revisada}`);
                                cambioRelevante = true;
                            }

                            if (upd.fecha_entrega && fechaEntregaAnterior !== upd.fecha_entrega) {
                                evento.setExtendedProp('fecha_entrega', upd.fecha_entrega);
                                cambios.push(`fecha_entrega: ${fechaEntregaAnterior} → ${upd.fecha_entrega}`);
                                cambioRelevante = true;
                            }

                            if (upd.fin_programado && finProgramadoAnterior !== upd.fin_programado) {
                                evento.setExtendedProp('fin_programado', upd.fin_programado);
                                cambios.push(`fin_programado: ${finProgramadoAnterior} → ${upd.fin_programado}`);
                                cambioRelevante = true;
                            }

                            // 🎨 Actualizar color según estado de revisión y retraso
                            if (cambioRelevante) {
                                console.log('🎨 Aplicando cambio de color', {
                                    evento_id: evento.id,
                                    revisada: upd.revisada,
                                    tiene_retraso: upd.tiene_retraso
                                });

                                if (!upd.revisada) {
                                    // Sin revisar → Color gris
                                    console.log('➡️ Aplicando color GRIS (sin revisar)');
                                    evento.setProp('backgroundColor', '#9e9e9e');
                                    evento.setProp('borderColor', '#757575');
                                    evento.setProp('classNames', ['evento-sin-revisar']);
                                    evento.setProp('title', `⚠️ ${upd.codigo} (SIN REVISAR)`);
                                } else if (upd.tiene_retraso) {
                                    // Revisada con retraso → Color rojo
                                    console.log('➡️ Aplicando color ROJO (con retraso)');
                                    evento.setProp('backgroundColor', '#ef4444');
                                    evento.setProp('borderColor', null);
                                    evento.setProp('classNames', ['evento-revisado', 'evento-retraso']);
                                    evento.setProp('title', upd.codigo);

                                    if (revisadaAnterior !== upd.revisada) {
                                        mostrarNotificacion(
                                            `⚠️ Planilla ${upd.codigo} marcada como revisada (CON RETRASO)`,
                                            'warning'
                                        );
                                    }
                                } else {
                                    // Revisada a tiempo → Color verde
                                    console.log('➡️ Aplicando color VERDE (a tiempo)');
                                    evento.setProp('backgroundColor', '#22c55e');
                                    evento.setProp('borderColor', null);
                                    evento.setProp('classNames', ['evento-revisado']);
                                    evento.setProp('title', upd.codigo);

                                    if (revisadaAnterior !== upd.revisada) {
                                        mostrarNotificacion(
                                            `✅ Planilla ${upd.codigo} marcada como revisada`,
                                            'success'
                                        );
                                    }
                                }
                            } else {
                                console.log('⚠️ No hay cambio relevante, no actualizando color');
                            }

                            // 4. Si se completó la planilla
                            if (upd.completado && upd.estado === 'completada') {
                                cambios.push('PLANILLA COMPLETADA');

                                // Notificación especial
                                mostrarNotificacion(`🎉 Planilla ${upd.codigo} completada!`, 'success');

                                // Opcional: Cambiar color a completada
                                evento.setProp('backgroundColor', '#10b981');

                                // Opcional: Remover después de 3 segundos
                                setTimeout(() => {
                                    evento.remove();
                                    console.log(
                                        `🗑️ Evento de planilla ${upd.codigo} eliminado (completada)`
                                    );
                                }, 3000);
                            }

                            if (cambios.length > 0) {
                                console.log(`📝 Planilla ${upd.codigo}: ${cambios.join(', ')}`);
                            }
                        });
                    });

                    // Forzar re-render de los tooltips
                    actualizarTooltips();
                }

                function actualizarBarraProgreso(eventoId, progreso) {
                    // Buscar el elemento del DOM del evento por su data-event-id
                    const eventoEl = document.querySelector(`[data-event-id="${eventoId}"]`);
                    if (!eventoEl) {
                        console.log(`⚠️ No se encontró elemento con data-event-id="${eventoId}"`);
                        return;
                    }

                    // Buscar la barra de progreso interna (el div con clase bg-blue-500)
                    const barra = eventoEl.querySelector('.bg-blue-500');
                    if (barra) {
                        barra.style.width = progreso + '%';
                        console.log(`✅ Barra de progreso actualizada a ${progreso}%`);
                    } else {
                        console.log(`⚠️ No se encontró barra de progreso en evento ${eventoId}`);
                    }
                }

                function actualizarTooltips() {
                    // Los tooltips se regeneran automáticamente en el próximo hover
                    // No necesitamos hacer nada especial aquí
                }

                // Iniciar polling al cargar el calendario
                console.log('📅 Calendario de producción inicializado');
                iniciarPolling();

                // Debug: Mostrar estadísticas cada minuto
                setInterval(() => {
                    console.log(`📊 Estadísticas de polling: ${actualizacionesRecibidas} actualizaciones recibidas`);
                }, 60000);
            }

            // Inicializar en carga inicial
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', inicializarCalendarioMaquinas);
            } else {
                inicializarCalendarioMaquinas();
            }

            // Reinicializar en navegación Livewire
            document.addEventListener('livewire:navigated', function() {
                // Solo inicializar si estamos en la página de máquinas
                const calendarioEl = document.getElementById('calendario');
                if (calendarioEl && calendarioEl.dataset.calendarType === 'maquinas') {
                    console.log('🔄 Reinicializando calendario de máquinas después de navegación');

                    // Destruir calendario anterior si existe
                    if (window.calendar) {
                        try {
                            window.calendar.destroy();
                            window.calendar = null;
                        } catch (e) {
                            console.warn('Error al destruir calendario anterior:', e);
                        }
                    }

                    inicializarCalendarioMaquinas();
                }
            });

            // Limpiar al salir de la página
            document.addEventListener('livewire:navigating', function() {
                if (window.calendar) {
                    try {
                        console.log('🧹 Limpiando calendario de máquinas');
                        window.calendar.destroy();
                        window.calendar = null;
                    } catch (e) {
                        console.warn('Error al limpiar calendario:', e);
                    }
                }
            });

            // ============================================================
            // FUNCIONES GLOBALES (fuera de inicializarCalendarioMaquinas)
            // ============================================================

            // Función para mostrar notificaciones toast
            function mostrarNotificacion(mensaje, tipo = 'info') {
                const colores = {
                    'info': 'bg-blue-600',
                    'success': 'bg-green-600',
                    'warning': 'bg-yellow-600',
                    'error': 'bg-red-600'
                };

                const iconos = {
                    'info': '🔄',
                    'success': '✅',
                    'warning': '⚠️',
                    'error': '❌'
                };

                const toast = document.createElement('div');
                toast.className =
                    `fixed top-4 right-4 ${colores[tipo]} text-white px-4 py-3 rounded-lg shadow-lg z-[9999] transition-opacity duration-300`;
                toast.style.opacity = '0';
                toast.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="text-xl">${iconos[tipo]}</span>
                    <span class="font-medium">${mensaje}</span>
                </div>
            `;

                document.body.appendChild(toast);

                // Fade in
                setTimeout(() => toast.style.opacity = '1', 10);

                // Fade out y remover
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            window.window.maquinaActualId = window.maquinaActualId || null;

            // Modal Estado
            function abrirModalEstado(maquinaId) {
                console.log('🔵 abrirModalEstado llamado con ID:', maquinaId, 'tipo:', typeof maquinaId);
                window.maquinaActualId = maquinaId;
                console.log('🔵 maquinaActualId establecido en:', maquinaActualId);

                // Obtener el título del enlace usando el data-maquina-id
                const link = document.querySelector(`a.maquina-nombre[data-maquina-id="${maquinaId}"]`);
                const nombreMaquina = link ? link.textContent : 'Máquina';
                console.log('🔵 Nombre de máquina obtenido del DOM:', nombreMaquina);

                document.getElementById('nombreMaquinaEstado').textContent = nombreMaquina;
                const modal = document.getElementById('modalEstado');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function cerrarModalEstado() {
                const modal = document.getElementById('modalEstado');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                window.maquinaActualId = null;
            }

            async function cambiarEstado(nuevoEstado) {
                if (!maquinaActualId) return;

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        console.error('No se encontró el token CSRF');
                        alert('Error: No se encontró el token de seguridad. Recarga la página.');
                        return;
                    }

                    const response = await fetch(`/maquinas/${maquinaActualId}/cambiar-estado`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.content
                        },
                        body: JSON.stringify({
                            estado: nuevoEstado
                        })
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Error del servidor:', errorText);
                        throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        cerrarModalEstado();
                        console.log('✅ Estado actualizado en el servidor');

                        // Refrescar recursos para obtener el emoji actualizado del endpoint
                        calendar.refetchResources();
                        console.log('✅ Recursos refrescados desde el endpoint');
                    } else {
                        alert('Error al cambiar el estado: ' + (data.mensaje || 'Error desconocido'));
                    }
                } catch (error) {
                    console.error('Error completo:', error);
                    alert('Error al comunicarse con el servidor: ' + error.message);
                }
            }

            // Modal Redistribuir
            function abrirModalRedistribuir(maquinaId) {
                console.log('🟠 abrirModalRedistribuir llamado con ID:', maquinaId);
                window.maquinaActualId = maquinaId;

                // Obtener el título del enlace usando el data-maquina-id
                const link = document.querySelector(`a.maquina-nombre[data-maquina-id="${maquinaId}"]`);
                const nombreMaquina = link ? link.textContent : 'Máquina';

                document.getElementById('nombreMaquinaRedistribuir').textContent = nombreMaquina;
                const modal = document.getElementById('modalRedistribuir');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function cerrarModalRedistribuir() {
                const modal = document.getElementById('modalRedistribuir');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                window.maquinaActualId = null;
            }

            // Funciones para selector de máquina desde botones superiores
            window.accionSeleccionada = window.accionSeleccionada || null; // 'estado' o 'redistribuir'

            function mostrarSelectorMaquinaEstado() {
                window.accionSeleccionada = 'estado';
                const header = document.getElementById('selectorHeader');
                const titulo = document.getElementById('selectorTitulo');
                header.className = 'bg-blue-600 text-white px-6 py-4 rounded-t-lg';
                titulo.textContent = 'Cambiar Estado de Máquina';
                mostrarSelectorMaquina();
            }

            function mostrarSelectorMaquinaRedistribuir() {
                window.accionSeleccionada = 'redistribuir';
                const header = document.getElementById('selectorHeader');
                const titulo = document.getElementById('selectorTitulo');
                header.className = 'bg-orange-600 text-white px-6 py-4 rounded-t-lg';
                titulo.textContent = 'Redistribuir Elementos de Máquina';
                mostrarSelectorMaquina();
            }

            function mostrarSelectorMaquina() {
                const modal = document.getElementById('modalSelectorMaquina');
                const listaMaquinas = document.getElementById('listaMaquinas');

                // Limpiar lista
                listaMaquinas.innerHTML = '';

                // Obtener todas las máquinas del calendario
                const resources = calendar.getResources();

                resources.forEach(resource => {
                    const maquinaId = resource.id;
                    const maquinaNombre = resource.title;

                    const boton = document.createElement('button');
                    boton.className =
                        'p-4 border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all text-left flex items-center gap-3 group';
                    boton.onclick = () => seleccionarMaquina(maquinaId);

                    const colorIcon = window.accionSeleccionada === 'estado' ? 'text-blue-600' : 'text-orange-600';

                    boton.innerHTML = `
                    <div class="${colorIcon} group-hover:scale-110 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-gray-800">${maquinaNombre}</div>
                        <div class="text-xs text-gray-500">ID: ${maquinaId}</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-blue-600 group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                `;

                    listaMaquinas.appendChild(boton);
                });

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function cerrarModalSelectorMaquina() {
                const modal = document.getElementById('modalSelectorMaquina');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                window.accionSeleccionada = null;
            }

            function seleccionarMaquina(maquinaId) {
                cerrarModalSelectorMaquina();

                if (window.accionSeleccionada === 'estado') {
                    abrirModalEstado(maquinaId);
                } else if (window.accionSeleccionada === 'redistribuir') {
                    abrirModalRedistribuir(maquinaId);
                }
            }

            window.datosRedistribucion = window.datosRedistribucion || null; // Para almacenar los datos para el reporte

            window.tipoRedistribucionSeleccionado = window.tipoRedistribucionSeleccionado || null;
            window.maquinaRedistribucionId = window.maquinaRedistribucionId || null; // ID de la máquina desde donde se redistribuye

            async function redistribuir(tipo) {
                if (!maquinaActualId) return;

                window.tipoRedistribucionSeleccionado = tipo;
                window.maquinaRedistribucionId = maquinaActualId; // Guardar el ID

                try {
                    // Obtener los elementos que serán redistribuidos (sin ejecutar la redistribución)
                    const response = await fetch(`/maquinas/${maquinaActualId}/elementos-pendientes?tipo=${tipo}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`Error HTTP ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success && data.elementos) {
                        // Mostrar modal de confirmación con los elementos
                        mostrarModalConfirmacionRedistribucion(data.elementos, tipo, data.maquina_origen, data.maquinas_disponibles);
                        cerrarModalRedistribuir();
                    } else {
                        alert('No hay elementos para redistribuir');
                    }
                } catch (error) {
                    console.error('Error completo:', error);
                    alert('Error al obtener elementos: ' + error.message);
                }
            }

            window.maquinasDisponiblesGlobal = window.maquinasDisponiblesGlobal || [];

            function mostrarModalConfirmacionRedistribucion(elementos, tipo, maquinaOrigen, maquinasDisponibles) {
                const modal = document.getElementById('modalConfirmacionRedistribucion');
                const mensaje = document.getElementById('mensajeConfirmacionRedistribucion');
                const lista = document.getElementById('listaElementosRedistribuir');

                window.maquinasDisponiblesGlobal = maquinasDisponibles;

                mensaje.textContent = `Se redistribuirán ${elementos.length} elemento(s) desde "${maquinaOrigen.nombre}" - ${tipo === 'todos' ? 'TODOS los pendientes' : 'Los primeros elementos'}`;

                lista.innerHTML = '';
                elementos.forEach((elemento, index) => {
                    const div = document.createElement('div');
                    div.className = 'bg-gray-50 border border-gray-200 rounded-lg p-3 hover:bg-gray-100 transition';

                    // Escapar comillas simples en dimensiones para evitar errores JavaScript
                    const dimensionesEscapadas = (elemento.dimensiones || '').replace(/'/g, "\\'");

                    const canvasId = `canvas-elemento-${elemento.id}`;

                    div.innerHTML = `
                        <div class="flex gap-4">
                            <div class="flex-shrink-0" style="width: 200px;">
                                <div id="${canvasId}" style="width: 200px; height: 120px; border: 1px solid #e5e7eb; border-radius: 4px; background: white;"></div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="font-mono text-sm font-semibold text-gray-700">${elemento.codigo}</span>
                                    <span class="text-xs text-gray-500">${elemento.dimensiones || 'Sin dimensiones'}</span>
                                </div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs text-gray-600 font-medium">Desde:</span>
                                    <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded">${maquinaOrigen.nombre}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                    <span class="text-xs text-gray-600 font-medium">Hacia:</span>
                                    <select
                                        class="text-xs border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        data-elemento-id="${elemento.id}"
                                        onchange="actualizarMaquinaDestino(${elemento.id}, this.value)">
                                        <option value="">Automático (${elemento.maquina_destino_nombre})</option>
                                        ${maquinasDisponibles.map(m => `<option value="${m.id}" ${m.id === elemento.maquina_destino_id ? 'selected' : ''}>${m.nombre}</option>`).join('')}
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;

                    lista.appendChild(div);

                    // Dibujar la figura en el canvas después de añadir al DOM
                    setTimeout(() => {
                        if (typeof window.dibujarFiguraElemento === 'function') {
                            window.dibujarFiguraElemento(
                                canvasId,
                                elemento.dimensiones || '',
                                elemento.peso,
                                elemento.diametro,
                                elemento.barras
                            );
                        }
                    }, 50);
                });

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Almacenar las máquinas destino seleccionadas
            window.maquinasDestinoSeleccionadas = window.maquinasDestinoSeleccionadas || {};

            function actualizarMaquinaDestino(elementoId, maquinaId) {
                if (maquinaId) {
                    window.maquinasDestinoSeleccionadas[elementoId] = parseInt(maquinaId);
                } else {
                    delete window.maquinasDestinoSeleccionadas[elementoId];
                }
            }

            function verFiguraElementoRedistribucion(id, codigo, dimensiones, peso, diametro, barras) {
                console.log('Abriendo figura para elemento:', { id, codigo, dimensiones, peso, diametro, barras });

                // Asegurar que el modal de dibujo esté visible
                const modalDibujo = document.getElementById('modal-dibujo');
                if (modalDibujo) {
                    modalDibujo.classList.remove('hidden');
                    modalDibujo.classList.add('flex');
                }

                // Actualizar el título del modal
                const titulo = modalDibujo.querySelector('h3');
                if (titulo) {
                    titulo.textContent = `Figura del Elemento - ${codigo}`;
                }

                // Actualizar datos globales del elemento
                window.elementoData = {
                    id: id,
                    dimensiones: dimensiones || '',
                    peso: peso || '',
                    diametro: diametro || '',
                    barras: barras || ''
                };

                // Dibujar la figura directamente
                if (typeof window.dibujarFiguraElemento === 'function') {
                    window.dibujarFiguraElemento('canvas-dibujo', dimensiones || '', peso || '', diametro || '', barras || '');
                } else {
                    console.error('La función dibujarFiguraElemento no está disponible');
                }
            }

            // Mantener la función de limpieza del botón temporal si se usaba
            function limpiarBotonTemporal(btnTemp) {
                setTimeout(() => {
                    if (btnTemp && btnTemp.parentNode) {
                        btnTemp.remove();
                    }
                }, 100);
            }

            function cerrarModalConfirmacionRedistribucion() {
                const modal = document.getElementById('modalConfirmacionRedistribucion');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                window.tipoRedistribucionSeleccionado = null;
                window.maquinaRedistribucionId = null;
                window.maquinasDestinoSeleccionadas = {};
            }

            async function confirmarRedistribucion() {
                console.log('🔄 confirmarRedistribucion llamada', { maquinaRedistribucionId: window.maquinaRedistribucionId, tipoRedistribucionSeleccionado: window.tipoRedistribucionSeleccionado, maquinasDestinoSeleccionadas: window.maquinasDestinoSeleccionadas });

                if (!window.maquinaRedistribucionId || !window.tipoRedistribucionSeleccionado) {
                    console.error('Faltan datos:', { maquinaRedistribucionId: window.maquinaRedistribucionId, tipoRedistribucionSeleccionado: window.tipoRedistribucionSeleccionado });
                    alert('Error: Faltan datos necesarios para la redistribución');
                    return;
                }

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        console.error('No se encontró el token CSRF');
                        alert('Error: No se encontró el token de seguridad. Recarga la página.');
                        return;
                    }

                    console.log('Enviando petición de redistribución...');

                    const response = await fetch(`/maquinas/${window.maquinaRedistribucionId}/redistribuir`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.content
                        },
                        body: JSON.stringify({
                            tipo: window.tipoRedistribucionSeleccionado,
                            maquinas_destino: window.maquinasDestinoSeleccionadas
                        })
                    });

                    console.log('Respuesta recibida:', response.status);

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Error del servidor:', errorText);
                        throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();
                    console.log('Datos de respuesta:', data);

                    if (data.success) {
                        cerrarModalConfirmacionRedistribucion();

                        // Recargar eventos del calendario
                        if (window.calendar) {
                            calendar.refetchEvents();
                        }

                        // Guardar datos para el reporte
                        window.datosRedistribucion = data;
                        // Mostrar modal de resultados
                        mostrarResultados(data);
                    } else {
                        alert('Error al redistribuir: ' + (data.mensaje || 'Error desconocido'));
                    }
                } catch (error) {
                    console.error('Error completo:', error);
                    alert('Error al comunicarse con el servidor: ' + error.message);
                }
            }

            function verFiguraElemento(id, codigo, dimensiones, peso) {
                // Usar el sistema existente de modal de dibujo
                const evento = new CustomEvent('abrirModalDibujo', {
                    detail: {
                        id: id,
                        codigo: codigo,
                        dimensiones: dimensiones,
                        peso: peso
                    }
                });
                document.dispatchEvent(evento);
            }

            function mostrarResultados(data) {
                // Actualizar mensaje principal
                document.getElementById('mensajeResultados').textContent = data.mensaje;

                // Llenar resumen por máquina
                const resumenMaquinas = document.getElementById('resumenMaquinas');
                resumenMaquinas.innerHTML = '';

                data.resumen.forEach(maquina => {
                    const card = document.createElement('div');
                    card.className = 'bg-blue-50 border border-blue-200 rounded-lg p-4';
                    card.innerHTML = `
                    <div class="font-semibold text-blue-900 mb-2">${maquina.nombre}</div>
                    <div class="text-sm text-blue-700">
                        <div>📦 ${maquina.cantidad} elemento${maquina.cantidad !== 1 ? 's' : ''}</div>
                        <div>⚖️ ${maquina.peso_total.toFixed(2)} kg</div>
                    </div>
                `;
                    resumenMaquinas.appendChild(card);
                });

                // Llenar tabla de detalles
                const detalleElementos = document.getElementById('detalleElementos');
                detalleElementos.innerHTML = '';

                data.detalles.forEach(elemento => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50';
                    row.innerHTML = `
                    <td class="px-3 py-2 whitespace-nowrap text-gray-900">${elemento.elemento_id}</td>
                    <td class="px-3 py-2 whitespace-nowrap text-gray-600">${elemento.marca}</td>
                    <td class="px-3 py-2 whitespace-nowrap text-gray-600">${elemento.diametro}</td>
                    <td class="px-3 py-2 whitespace-nowrap text-gray-600">${elemento.peso} kg</td>
                    <td class="px-3 py-2 whitespace-nowrap text-gray-600">${elemento.planilla}</td>
                    <td class="px-3 py-2 whitespace-nowrap text-gray-500">${elemento.maquina_anterior}</td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            ${elemento.maquina_nueva}
                        </span>
                    </td>
                `;
                    detalleElementos.appendChild(row);
                });

                // Mostrar el modal
                const modal = document.getElementById('modalResultados');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function cerrarModalResultados() {
                const modal = document.getElementById('modalResultados');
                modal.classList.add('hidden');
                modal.classList.remove('flex');

                // Refrescar tanto eventos como recursos desde los endpoints
                if (calendar) {
                    calendar.refetchResources(); // Por si cambiaron estados de máquinas
                    calendar.refetchEvents(); // Para mostrar elementos redistribuidos
                    console.log('✅ Recursos y eventos refrescados después de redistribución');
                }
            }

            function descargarReporte() {
                if (!window.datosRedistribucion) return;

                // Crear contenido CSV
                let csv = 'ID,Marca,Diámetro,Peso (kg),Planilla,Máquina Anterior,Nueva Máquina\n';

                window.datosRedistribucion.detalles.forEach(elemento => {
                    csv +=
                        `${elemento.elemento_id},"${elemento.marca}",${elemento.diametro},${elemento.peso},"${elemento.planilla}","${elemento.maquina_anterior}","${elemento.maquina_nueva}"\n`;
                });

                // Agregar resumen al final
                csv += '\n\nRESUMEN POR MÁQUINA\n';
                csv += 'Máquina,Cantidad,Peso Total (kg)\n';
                window.datosRedistribucion.resumen.forEach(maquina => {
                    csv += `"${maquina.nombre}",${maquina.cantidad},${maquina.peso_total.toFixed(2)}\n`;
                });

                // Crear y descargar archivo
                const blob = new Blob([csv], {
                    type: 'text/csv;charset=utf-8;'
                });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);

                const fecha = new Date().toISOString().split('T')[0];
                link.setAttribute('href', url);
                link.setAttribute('download', `redistribucion_${fecha}.csv`);
                link.style.visibility = 'hidden';

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            // ============================================================
            // OPTIMIZACIÓN DE PLANILLAS
            // ============================================================

            window.datosOptimizacion = window.datosOptimizacion || null;

            async function abrirModalOptimizar() {
                const modal = document.getElementById('modalOptimizar');
                const loading = document.getElementById('optimizarLoading');
                const content = document.getElementById('optimizarContent');
                const empty = document.getElementById('optimizarEmpty');
                const btnAplicar = document.getElementById('btnAplicarOptimizacion');

                // Mostrar modal en estado de carga
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                loading.classList.remove('hidden');
                content.classList.add('hidden');
                empty.classList.add('hidden');
                btnAplicar.classList.add('hidden');

                try {
                    // Llamar al endpoint para obtener análisis de optimización
                    const response = await fetch('/api/produccion/optimizar-analisis', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Error al analizar planillas');
                    }

                    const data = await response.json();
                    window.datosOptimizacion = data;

                    loading.classList.add('hidden');

                    if (data.elementos && data.elementos.length > 0) {
                        mostrarPreviewOptimizacion(data);
                        content.classList.remove('hidden');
                        btnAplicar.classList.remove('hidden');
                    } else {
                        empty.classList.remove('hidden');
                    }

                } catch (error) {
                    console.error('Error al cargar optimización:', error);
                    loading.classList.add('hidden');
                    empty.classList.remove('hidden');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar el análisis de optimización'
                    });
                }
            }

            function mostrarPreviewOptimizacion(data) {
                // Actualizar estadísticas
                document.getElementById('estadPlanillasRetraso').textContent = data.planillas_retraso || 0;
                document.getElementById('estadElementosMover').textContent = data.elementos.length || 0;
                document.getElementById('estadMaquinasDisponibles').textContent = data.maquinas_disponibles || 0;

                // Llenar tabla de elementos
                const tabla = document.getElementById('tablaOptimizacion');
                tabla.innerHTML = '';

                data.elementos.forEach((elem, index) => {
                    const row = document.createElement('tr');
                    row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';

                    // Crear select de máquinas compatibles
                    const selectMaquinas = crearSelectMaquinas(elem.id, elem.maquina_destino_sugerida, elem.maquinas_compatibles);

                    // Calcular retraso
                    const fechaEntrega = new Date(elem.fecha_entrega);
                    const finProgramado = new Date(elem.fin_programado);
                    const retraso = finProgramado > fechaEntrega;

                    row.innerHTML = `
                        <td class="px-3 py-2 text-sm text-gray-900">${elem.codigo}</td>
                        <td class="px-3 py-2 text-sm text-gray-600">${elem.planilla_codigo}</td>
                        <td class="px-3 py-2 text-sm text-gray-900">${elem.diametro}</td>
                        <td class="px-3 py-2 text-sm text-gray-600">${elem.tipo_material || '-'}</td>
                        <td class="px-3 py-2 text-sm text-gray-900">${elem.peso}</td>
                        <td class="px-3 py-2 text-sm text-gray-600">${elem.maquina_actual_nombre}</td>
                        <td class="px-3 py-2 text-sm ${retraso ? 'text-green-600 font-medium' : 'text-gray-600'}">
                            ${formatearFecha(elem.fecha_entrega)}
                        </td>
                        <td class="px-3 py-2 text-sm ${retraso ? 'text-red-600 font-bold' : 'text-gray-600'}">
                            ${formatearFecha(elem.fin_programado)}
                        </td>
                        <td class="px-3 py-2">${selectMaquinas}</td>
                    `;

                    tabla.appendChild(row);
                });
            }

            function crearSelectMaquinas(elementoId, maquinaSugerida, maquinasCompatibles) {
                let html = `<select class="maquina-selector border border-gray-300 rounded px-2 py-1 text-sm w-full focus:ring-2 focus:ring-purple-500" data-elemento-id="${elementoId}">`;

                maquinasCompatibles.forEach(maq => {
                    const selected = maq.id === maquinaSugerida ? 'selected' : '';
                    const badge = maq.id === maquinaSugerida ? ' ⭐' : '';
                    html += `<option value="${maq.id}" ${selected}>${maq.nombre}${badge}</option>`;
                });

                html += '</select>';
                return html;
            }

            function formatearFecha(fechaStr) {
                if (!fechaStr) return '-';
                const fecha = new Date(fechaStr);
                return fecha.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            }

            function cerrarModalOptimizar() {
                const modal = document.getElementById('modalOptimizar');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                window.datosOptimizacion = null;
            }

            async function aplicarOptimizacion() {
                // Recopilar las selecciones del usuario
                const selectores = document.querySelectorAll('.maquina-selector');
                const redistribuciones = [];

                selectores.forEach(select => {
                    redistribuciones.push({
                        elemento_id: parseInt(select.dataset.elementoId),
                        nueva_maquina_id: parseInt(select.value)
                    });
                });

                if (redistribuciones.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin cambios',
                        text: 'No hay elementos para redistribuir'
                    });
                    return;
                }

                // Confirmar con el usuario
                const result = await Swal.fire({
                    icon: 'question',
                    title: '¿Aplicar optimización?',
                    html: `Se van a mover <strong>${redistribuciones.length}</strong> elemento(s) a nuevas máquinas.<br><br>¿Deseas continuar?`,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, aplicar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#9333ea',
                    cancelButtonColor: '#6b7280'
                });

                if (!result.isConfirmed) return;

                // Mostrar loading
                Swal.fire({
                    title: 'Aplicando optimización...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    const response = await fetch('/api/produccion/optimizar-aplicar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ redistribuciones })
                    });

                    if (!response.ok) {
                        throw new Error('Error al aplicar optimización');
                    }

                    const data = await response.json();

                    Swal.fire({
                        icon: 'success',
                        title: '¡Optimización aplicada!',
                        html: `Se han redistribuido <strong>${data.elementos_movidos}</strong> elemento(s) exitosamente.`,
                        confirmButtonColor: '#9333ea'
                    });

                    cerrarModalOptimizar();

                    // Refrescar calendario
                    if (typeof calendar !== 'undefined') {
                        calendar.refetchResources();
                        calendar.refetchEvents();
                    }

                } catch (error) {
                    console.error('Error al aplicar optimización:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo aplicar la optimización. Por favor intenta de nuevo.'
                    });
                }
            }

            // ============================================================
            // BALANCEO DE CARGA
            // ============================================================

            window.datosBalanceo = window.datosBalanceo || null;

            async function abrirModalBalanceo() {
                const modal = document.getElementById('modalBalanceo');
                const loading = document.getElementById('balanceoLoading');
                const content = document.getElementById('balanceoContent');
                const empty = document.getElementById('balanceoEmpty');
                const btnAplicar = document.getElementById('btnAplicarBalanceo');

                // Mostrar modal en estado de carga
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                loading.classList.remove('hidden');
                content.classList.add('hidden');
                empty.classList.add('hidden');
                btnAplicar.classList.add('hidden');

                try {
                    // Llamar al endpoint para obtener análisis de balanceo
                    const response = await fetch('/api/produccion/balancear-carga-analisis', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Error al analizar balanceo');
                    }

                    const data = await response.json();
                    window.datosBalanceo = data;

                    loading.classList.add('hidden');

                    if (data.elementos && data.elementos.length > 0) {
                        mostrarPreviewBalanceo(data);
                        content.classList.remove('hidden');
                        btnAplicar.classList.remove('hidden');
                    } else {
                        empty.classList.remove('hidden');
                    }

                } catch (error) {
                    console.error('Error al cargar balanceo:', error);
                    loading.classList.add('hidden');
                    empty.classList.remove('hidden');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar el análisis de balanceo'
                    });
                }
            }

            function mostrarPreviewBalanceo(data) {
                // Actualizar estadísticas
                document.getElementById('estadElementosBalanceo').textContent = data.total_elementos || 0;
                document.getElementById('estadTiempoPromedio').textContent = (data.tiempo_promedio_horas || 0) + 'h';
                document.getElementById('estadMaquinasBalanceadas').textContent = (data.resumen_original || []).length;

                // Mostrar gráfico de carga original
                const grafico = document.getElementById('graficoCargaOriginal');
                grafico.innerHTML = '';

                if (data.resumen_original && data.resumen_original.length > 0) {
                    const maxHoras = Math.max(...data.resumen_original.map(m => m.tiempo_horas));

                    data.resumen_original.forEach(maquina => {
                        const porcentaje = (maquina.tiempo_horas / maxHoras) * 100;
                        const esSobrecargada = maquina.tiempo_horas > (data.tiempo_promedio_horas * 1.15);
                        const esSubcargada = maquina.tiempo_horas < (data.tiempo_promedio_horas * 0.85);

                        const barColor = esSobrecargada ? 'bg-red-500' : (esSubcargada ? 'bg-yellow-500' : 'bg-green-500');

                        grafico.innerHTML += `
                            <div class="flex items-center gap-2">
                                <div class="w-32 text-sm font-medium text-gray-700">${maquina.nombre}</div>
                                <div class="flex-1 bg-gray-200 rounded-full h-6 relative">
                                    <div class="${barColor} h-6 rounded-full flex items-center justify-end pr-2 text-white text-xs font-medium"
                                         style="width: ${porcentaje}%">
                                        ${maquina.tiempo_horas}h
                                    </div>
                                </div>
                                <div class="w-24 text-sm text-gray-600">${maquina.cantidad_elementos} elem.</div>
                            </div>
                        `;
                    });
                }

                // Llenar tabla de elementos
                const tabla = document.getElementById('tablaBalanceo');
                tabla.innerHTML = '';

                data.elementos.forEach((elemento, index) => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50';
                    row.innerHTML = `
                        <td class="px-3 py-2">
                            <input type="checkbox"
                                   class="balanceo-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500"
                                   data-elemento-id="${elemento.elemento_id}"
                                   data-maquina-actual="${elemento.maquina_actual_id}"
                                   data-maquina-nueva="${elemento.maquina_nueva_id}"
                                   checked>
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-900">${elemento.codigo}</td>
                        <td class="px-3 py-2 text-gray-600">${elemento.planilla_codigo || '-'}</td>
                        <td class="px-3 py-2 text-gray-600">${elemento.diametro}</td>
                        <td class="px-3 py-2 text-gray-600">${elemento.tiempo_horas}h</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                ${elemento.maquina_actual_nombre}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                ${elemento.maquina_nueva_nombre}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-600">${elemento.razon || '-'}</td>
                    `;
                    tabla.appendChild(row);
                });
            }

            function cerrarModalBalanceo() {
                const modal = document.getElementById('modalBalanceo');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                window.datosBalanceo = null;
            }

            function toggleAllBalanceo(checkbox) {
                const checkboxes = document.querySelectorAll('.balanceo-checkbox');
                checkboxes.forEach(cb => cb.checked = checkbox.checked);
            }

            function seleccionarTodosBalanceo() {
                const checkboxes = document.querySelectorAll('.balanceo-checkbox');
                checkboxes.forEach(cb => cb.checked = true);
                document.getElementById('checkAllBalanceo').checked = true;
            }

            function deseleccionarTodosBalanceo() {
                const checkboxes = document.querySelectorAll('.balanceo-checkbox');
                checkboxes.forEach(cb => cb.checked = false);
                document.getElementById('checkAllBalanceo').checked = false;
            }

            async function aplicarBalanceo() {
                // Recopilar elementos seleccionados
                const checkboxes = document.querySelectorAll('.balanceo-checkbox:checked');
                const movimientos = [];

                checkboxes.forEach(cb => {
                    movimientos.push({
                        elemento_id: parseInt(cb.dataset.elementoId),
                        maquina_actual_id: parseInt(cb.dataset.maquinaActual),
                        maquina_nueva_id: parseInt(cb.dataset.maquinaNueva)
                    });
                });

                if (movimientos.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin selección',
                        text: 'Por favor selecciona al menos un elemento para balancear'
                    });
                    return;
                }

                // Confirmar con el usuario
                const result = await Swal.fire({
                    icon: 'question',
                    title: '¿Aplicar balanceo de carga?',
                    html: `Se van a mover <strong>${movimientos.length}</strong> elemento(s) para balancear la carga entre máquinas.<br><br>¿Deseas continuar?`,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, aplicar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor: '#6b7280'
                });

                if (!result.isConfirmed) return;

                // Mostrar loading
                Swal.fire({
                    title: 'Aplicando balanceo...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    const response = await fetch('/api/produccion/balancear-carga-aplicar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ movimientos })
                    });

                    if (!response.ok) {
                        throw new Error('Error al aplicar balanceo');
                    }

                    const data = await response.json();

                    Swal.fire({
                        icon: 'success',
                        title: '¡Balanceo aplicado!',
                        html: `Se han redistribuido <strong>${data.procesados}</strong> elemento(s) exitosamente.`,
                        confirmButtonColor: '#16a34a'
                    });

                    cerrarModalBalanceo();

                    // Refrescar calendario
                    if (typeof calendar !== 'undefined') {
                        calendar.refetchResources();
                        calendar.refetchEvents();
                    }

                } catch (error) {
                    console.error('Error al aplicar balanceo:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo aplicar el balanceo. Por favor intenta de nuevo.'
                    });
                }
            }

            // ============================================================
            // GESTIÓN DE TURNOS
            // ============================================================

            async function toggleTurno(turnoId, turnoNombre) {
                console.log('🔄 toggleTurno llamado:', turnoId, turnoNombre);

                const btn = document.querySelector(`button[data-turno-id="${turnoId}"]`);
                if (!btn) {
                    console.error('❌ No se encontró el botón con turno-id:', turnoId);
                    return;
                }

                const icon = btn.querySelector('.turno-icon');
                const currentActivo = btn.classList.contains('bg-green-500');

                console.log('✅ Botón encontrado, estado actual:', currentActivo ? 'activo' : 'inactivo');

                try {
                    // Mostrar estado de carga
                    btn.disabled = true;
                    btn.style.opacity = '0.6';
                    icon.textContent = '⏳';

                    // Hacer el toggle
                    const response = await fetch(`/turnos/${turnoId}/toggle`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Error al cambiar estado del turno');
                    }

                    const data = await response.json();
                    const nuevoActivo = data.turno.activo;

                    console.log('✅ Respuesta del servidor:', nuevoActivo ? 'Turno activado' : 'Turno desactivado');

                    // Actualizar UI del botón
                    if (nuevoActivo) {
                        btn.classList.remove('bg-gray-200', 'text-gray-600', 'border-gray-300', 'hover:bg-gray-300');
                        btn.classList.add('bg-green-500', 'text-white', 'border-green-600', 'hover:bg-green-600');
                        icon.textContent = '✓';
                        btn.title = `Desactivar turno ${turnoNombre}`;
                    } else {
                        btn.classList.remove('bg-green-500', 'text-white', 'border-green-600', 'hover:bg-green-600');
                        btn.classList.add('bg-gray-200', 'text-gray-600', 'border-gray-300', 'hover:bg-gray-300');
                        icon.textContent = '✕';
                        btn.title = `Activar turno ${turnoNombre}`;
                    }

                    // Mostrar mensaje
                    const mensaje = nuevoActivo
                        ? `✅ Turno "${turnoNombre}" activado`
                        : `⏸️ Turno "${turnoNombre}" desactivado`;

                    // Crear toast notification
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-20 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 ' +
                        (nuevoActivo ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-gray-100 text-gray-800 border border-gray-300');
                    toast.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span class="text-xl">${nuevoActivo ? '✅' : '⏸️'}</span>
                            <div>
                                <div class="font-semibold text-sm">${mensaje}</div>
                                <div class="text-xs opacity-75">Recargando eventos...</div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);

                    // Re-renderizar eventos sin refrescar la página
                    if (calendar) {
                        console.log('🔄 Recargando eventos tras cambio de turno...');
                        await calendar.refetchEvents();
                        console.log('✅ Eventos recargados');

                        // Re-aplicar líneas de turnos
                        if (window.aplicarLineasTurnos) {
                            setTimeout(() => window.aplicarLineasTurnos(), 100);
                        }

                        // Actualizar mensaje del toast
                        toast.querySelector('.text-xs').textContent = '¡Listo!';
                    }

                    // Eliminar toast después de 3 segundos
                    setTimeout(() => {
                        toast.style.opacity = '0';
                        setTimeout(() => toast.remove(), 300);
                    }, 3000);

                } catch (error) {
                    console.error('Error al cambiar turno:', error);

                    // Mostrar error
                    const errorToast = document.createElement('div');
                    errorToast.className = 'fixed top-20 right-4 z-50 px-4 py-3 rounded-lg shadow-lg bg-red-100 text-red-800 border border-red-300';
                    errorToast.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span class="text-xl">❌</span>
                            <div class="font-semibold text-sm">Error al cambiar turno</div>
                        </div>
                    `;
                    document.body.appendChild(errorToast);
                    setTimeout(() => {
                        errorToast.style.opacity = '0';
                        setTimeout(() => errorToast.remove(), 300);
                    }, 3000);

                    // Revertir icono
                    icon.textContent = currentActivo ? '✓' : '✕';
                } finally {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            }

            // ============================================================

        </script>
</x-app-layout>
