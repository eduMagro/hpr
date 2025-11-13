<x-app-layout>
    <x-slot name="title">Máquinas - {{ config('app.name') }}</x-slot>

    <style>
        html {
            scroll-behavior: smooth;
        }

        /* Animaciones suaves para el sidebar */
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }

        /* Overlay para móviles */
        .sidebar-overlay {
            transition: opacity 0.3s ease-in-out;
        }

        /* Mejoras visuales */
        .machine-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .machine-card {
            transition: all 0.3s ease;
        }

        /* Enlace activo del sidebar */
        .sidebar-link.bg-blue-100 {
            background-color: rgb(219 234 254);
            border-color: rgb(59 130 246);
            color: rgb(29 78 216);
        }

        .sidebar-link.bg-blue-100 .text-gray-600 {
            color: rgb(30 58 138);
        }
    </style>

    <div class="relative">
        {{-- Botón hamburguesa para móviles --}}
        <button id="sidebarToggle"
            class="fixed top-4 left-4 z-50 lg:hidden bg-blue-600 text-white p-3 rounded-lg shadow-lg hover:bg-blue-700 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                </path>
            </svg>
        </button>

        {{-- Overlay para cerrar sidebar en móvil --}}
        <div id="sidebarOverlay"
            class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden sidebar-overlay"></div>

        {{-- MENÚ LATERAL RESPONSIVE --}}
        <aside id="sidebar" role="navigation"
            class="fixed left-0 top-0 h-screen w-64 md:w-72 bg-white shadow-xl z-40 overflow-y-auto sidebar-transition transform -translate-x-full lg:translate-x-0">

            {{-- Botón cerrar en móvil --}}
            <div class="lg:hidden flex justify-end p-4">
                <button id="closeSidebar"
                    class="text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="px-4 pb-4">
                <x-tabla.boton-azul :href="route('maquinas.create')" class="w-full text-center">
                    ➕ Crear Nueva Máquina
                </x-tabla.boton-azul>
            </div>

            <div class="px-4 pb-4 border-t border-gray-200 pt-4">
                <h3 class="text-lg font-bold text-gray-800 mb-3">Navegación por Máquina</h3>

                <ul class="space-y-1">
                    @foreach ($registrosMaquina as $maquina)
                        <li>
                            <a href="#maquina-{{ $maquina->id }}"
                                class="sidebar-link block px-3 py-2 text-sm rounded-lg hover:bg-blue-50 hover:text-blue-700 font-medium transition-all duration-200 truncate border border-transparent hover:border-blue-200 cursor-pointer">
                                <span class="font-semibold">{{ $maquina->codigo }}</span>
                                <span class="text-gray-600">— {{ $maquina->nombre }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>

        {{-- Contenido principal --}}
        <div class="lg:ml-64 xl:ml-72 min-h-screen bg-gray-50">
            <div class="p-4 sm:p-6 lg:p-10 pt-20 lg:pt-10">

            {{-- Botón para mostrar todas las máquinas --}}
            <div id="showAllContainer" class="hidden mb-6">
                <button id="showAllBtn"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    Mostrar todas las máquinas
                    <span id="machineCount" class="ml-2 bg-blue-800 px-2 py-0.5 rounded-full text-xs"></span>
                </button>
            </div>

            {{-- Grid responsive para las tarjetas --}}
            <div id="machinesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
            @forelse($registrosMaquina as $maquina)
                <div id="maquina-{{ $maquina->id }}"
                    class="machine-card scroll-mt-28 bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden flex flex-col h-full">

                    {{-- Imagen responsive --}}
                    <div class="w-full h-48 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center flex-shrink-0">
                        @if ($maquina->imagen)
                            <img src="{{ asset($maquina->imagen) }}" alt="Imagen de {{ $maquina->nombre }}"
                                class="object-contain h-full w-full p-4">
                        @else
                            <div class="text-center">
                                <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <span class="text-gray-400 text-sm mt-2 block">Sin imagen</span>
                            </div>
                        @endif
                    </div>

                    {{-- Datos principales --}}
                    <div class="p-4 space-y-3 flex-1 flex flex-col">
                        {{-- Título --}}
                        <div class="border-b border-gray-200 pb-2">
                            <h3 class="text-base font-bold text-gray-900 line-clamp-2">
                                <span class="text-blue-600">{{ $maquina->codigo }}</span>
                                <span class="text-gray-400 text-sm">—</span>
                                <span class="text-sm">{{ $maquina->nombre }}</span>
                            </h3>
                        </div>

                        {{-- Grid de información --}}
                        <div class="grid grid-cols-1 gap-2 text-sm flex-1">
                            {{-- Estado --}}
                            <div class="flex items-center">
                                @php
                                    $inProduction =
                                        $maquina->tipo == 'ensambladora'
                                            ? $maquina->elementos_ensambladora > 0
                                            : $maquina->elementos_count > 0;
                                @endphp
                                <span class="font-semibold text-gray-700 mr-2">Estado:</span>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $inProduction ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $inProduction ? '✓ En producción' : '○ Sin trabajo' }}
                                </span>
                            </div>

                            {{-- Nave --}}
                            <div class="flex items-start flex-col">
                                <span class="font-semibold text-gray-700 text-xs">Nave:</span>
                                <span class="text-gray-600 text-xs truncate w-full">
                                    {{ $maquina->obra?->obra ?? 'Sin Nave asignada' }}
                                </span>
                            </div>

                            {{-- Diámetros --}}
                            <div class="flex items-start flex-col">
                                <span class="font-semibold text-gray-700 text-xs">Diámetros:</span>
                                <span class="text-gray-600 text-xs">
                                    {{ $maquina->diametro_min }} - {{ $maquina->diametro_max }} mm
                                </span>
                            </div>
                        </div>

                        {{-- Operarios --}}
                        @php
                            $asignacionesHoy = $usuariosPorMaquina->get($maquina->id, collect());
                            $ordenTurno = ['noche' => 0, 'mañana' => 1, 'tarde' => 2];
                            $asignacionesOrdenadas = $asignacionesHoy->sortBy(function ($asig) use ($ordenTurno) {
                                $nombreTurno = strtolower($asig->turno->nombre ?? '');
                                return $ordenTurno[$nombreTurno] ?? 99;
                            });
                        @endphp

                        <div class="bg-gray-50 rounded-lg p-2.5">
                            <strong class="text-xs text-gray-700 block mb-1.5">Operarios:</strong>
                            @if ($asignacionesOrdenadas->isEmpty())
                                <span class="text-xs text-gray-500 italic">Ninguno</span>
                            @else
                                <ul class="space-y-1 max-h-20 overflow-y-auto">
                                    @foreach ($asignacionesOrdenadas as $asig)
                                        <li class="text-xs text-gray-700 flex items-center">
                                            <svg class="w-3 h-3 mr-1.5 text-gray-400 flex-shrink-0" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="truncate flex-1">{{ $asig->user->name }}</span>
                                            <span class="ml-1 text-[10px] text-gray-500 bg-white px-1.5 py-0.5 rounded flex-shrink-0">
                                                {{ substr(ucfirst(data_get($asig, 'turno.nombre', 'Sin')), 0, 1) }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        {{-- Subir imagen --}}
                        <form action="{{ route('maquinas.imagen', $maquina->id) }}" method="POST"
                            enctype="multipart/form-data" class="border-t border-gray-200 pt-3 mt-auto">
                            @csrf
                            @method('PUT')

                            <details class="group">
                                <summary class="text-xs font-semibold text-blue-600 cursor-pointer hover:text-blue-700 list-none flex items-center justify-between">
                                    <span>Actualizar imagen</span>
                                    <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </summary>
                                <div class="flex flex-col gap-2 mt-2">
                                    <input type="file" name="imagen" accept="image/*"
                                        class="text-xs text-gray-600 border border-gray-300 rounded-lg p-1.5 file:mr-2 file:py-1 file:px-2 file:border-0 file:bg-blue-50 file:text-blue-700 file:rounded-md file:text-xs file:font-medium hover:file:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                    <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded-lg text-xs font-medium transition-colors shadow-sm">
                                        Subir
                                    </button>
                                </div>
                            </details>
                        </form>
                    </div>

                    {{-- Acciones --}}
                    <div class="bg-gray-50 px-3 py-3 border-t border-gray-200 flex flex-col gap-2 mt-auto">
                        <a href="{{ route('maquinas.show', $maquina->id) }}"
                            class="w-full inline-flex items-center justify-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Iniciar Sesión
                        </a>

                        <div class="flex gap-2">
                            <a href="javascript:void(0);"
                                class="open-edit-modal flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors"
                                data-id="{{ $maquina->id }}">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                Editar
                            </a>

                            <x-tabla.boton-eliminar :action="route('maquinas.destroy', $maquina->id)" />
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-xl border-2 border-dashed border-gray-300 p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No hay máquinas disponibles</h3>
                    <p class="mt-2 text-sm text-gray-500">Comienza creando una nueva máquina.</p>
                </div>
            @endforelse
            </div>

            {{-- Paginación dentro del contenedor --}}
            @if ($registrosMaquina->hasPages())
                <div class="mt-8 flex justify-center">
                    {{ $registrosMaquina->links('vendor.pagination.bootstrap-5') }}
                </div>
            @endif
            </div>
        </div>
    </div>

    {{-- Modal de edición mejorado --}}
    <div id="editModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 justify-center items-center p-4 overflow-y-auto">
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-3xl my-8 mx-auto transform transition-all">
            {{-- Header del modal --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-xl">
                <h2 class="text-xl font-bold text-white">Editar Máquina</h2>
            </div>

            <form id="editMaquinaForm" class="p-6">
                @csrf
                <input type="hidden" id="edit-id" name="id">

                {{-- Grid de 2 columnas responsive --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto pr-2">
                    {{-- Código --}}
                    <div>
                        <label for="edit-codigo" class="block text-sm font-semibold text-gray-700 mb-2">
                            Código
                        </label>
                        <input id="edit-codigo" name="codigo" type="text"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    {{-- Nombre --}}
                    <div>
                        <label for="edit-nombre" class="block text-sm font-semibold text-gray-700 mb-2">
                            Nombre
                        </label>
                        <input id="edit-nombre" name="nombre" type="text"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    {{-- Obra asignada --}}
                    <div>
                        <label for="edit-obra_id" class="block text-sm font-semibold text-gray-700 mb-2">
                            Obra asignada
                        </label>
                        <select id="edit-obra_id" name="obra_id"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                            <option value="">Sin asignar</option>
                            @foreach ($obras as $obra)
                                <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tipo --}}
                    <div>
                        <label for="edit-tipo" class="block text-sm font-semibold text-gray-700 mb-2">
                            Tipo
                        </label>
                        <select id="edit-tipo" name="tipo"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                            <option value="">— Selecciona tipo —</option>
                            <option value="cortadora_dobladora">Cortadora-Dobladora</option>
                            <option value="ensambladora">Ensambladora</option>
                            <option value="soldadora">Soldadora</option>
                            <option value="cortadora_manual">Cortadora manual</option>
                            <option value="dobladora_manual">Dobladora manual</option>
                            <option value="grua">Grúa</option>
                        </select>
                    </div>

                    {{-- Diámetro mínimo --}}
                    <div>
                        <label for="edit-diametro_min" class="block text-sm font-semibold text-gray-700 mb-2">
                            Diámetro mínimo (mm)
                        </label>
                        <input id="edit-diametro_min" name="diametro_min" type="number"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    {{-- Diámetro máximo --}}
                    <div>
                        <label for="edit-diametro_max" class="block text-sm font-semibold text-gray-700 mb-2">
                            Diámetro máximo (mm)
                        </label>
                        <input id="edit-diametro_max" name="diametro_max" type="number"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    {{-- Peso mínimo --}}
                    <div>
                        <label for="edit-peso_min" class="block text-sm font-semibold text-gray-700 mb-2">
                            Peso mínimo
                        </label>
                        <input id="edit-peso_min" name="peso_min" type="number"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    {{-- Peso máximo --}}
                    <div>
                        <label for="edit-peso_max" class="block text-sm font-semibold text-gray-700 mb-2">
                            Peso máximo
                        </label>
                        <input id="edit-peso_max" name="peso_max" type="number"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    {{-- Ancho en metros --}}
                    <div>
                        <label for="edit-ancho_m" class="block text-sm font-semibold text-gray-700 mb-2">
                            Ancho (m)
                        </label>
                        <input id="edit-ancho_m" name="ancho_m" type="number" step="0.01"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    {{-- Largo en metros --}}
                    <div>
                        <label for="edit-largo_m" class="block text-sm font-semibold text-gray-700 mb-2">
                            Largo (m)
                        </label>
                        <input id="edit-largo_m" name="largo_m" type="number" step="0.01"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                </div>

                {{-- Botones del modal --}}
                <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                    <button type="button" id="closeModal"
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-colors border border-gray-300">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors shadow-sm">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        (function() {
            // ========== SIDEBAR TOGGLE ==========
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const closeSidebar = document.getElementById('closeSidebar');
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            const showAllContainer = document.getElementById('showAllContainer');
            const showAllBtn = document.getElementById('showAllBtn');
            const machineCount = document.getElementById('machineCount');
            const allMachineCards = document.querySelectorAll('.machine-card');

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevenir scroll del body
            }

            function closeSidebarFn() {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
                document.body.style.overflow = ''; // Restaurar scroll
            }

            // Función para determinar si estamos en modo multi-columna
            function isMultiColumnMode() {
                return window.innerWidth >= 768; // md breakpoint
            }

            // Función para mostrar solo una máquina
            function showOnlyMachine(machineId) {
                let visibleCount = 0;
                allMachineCards.forEach(card => {
                    if (card.id === `maquina-${machineId}`) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Mostrar botón "Mostrar todas"
                showAllContainer.classList.remove('hidden');
                machineCount.textContent = allMachineCards.length;

                // Scroll suave al top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // Función para mostrar todas las máquinas
            function showAllMachines() {
                allMachineCards.forEach(card => {
                    card.style.display = '';
                });
                showAllContainer.classList.add('hidden');

                // Remover clase activa de todos los enlaces
                sidebarLinks.forEach(link => {
                    link.classList.remove('bg-blue-100', 'border-blue-500');
                });
            }

            // Abrir sidebar con botón hamburguesa
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', openSidebar);
            }

            // Cerrar sidebar con botón X
            if (closeSidebar) {
                closeSidebar.addEventListener('click', closeSidebarFn);
            }

            // Cerrar sidebar con overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebarFn);
            }

            // Botón "Mostrar todas"
            if (showAllBtn) {
                showAllBtn.addEventListener('click', showAllMachines);
            }

            // Manejar clic en enlaces del sidebar
            sidebarLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    // Extraer ID de la máquina del href (#maquina-123)
                    const href = link.getAttribute('href');
                    const machineId = href.replace('#maquina-', '');

                    if (isMultiColumnMode()) {
                        // En modo multi-columna: filtrar máquinas
                        e.preventDefault();
                        showOnlyMachine(machineId);

                        // Marcar enlace como activo
                        sidebarLinks.forEach(l => l.classList.remove('bg-blue-100', 'border-blue-500'));
                        link.classList.add('bg-blue-100', 'border-blue-500');
                    } else {
                        // En móvil: mantener comportamiento de scroll
                        // No prevenir default, dejar que funcione el scroll normal
                    }

                    // Cerrar sidebar en móvil
                    if (window.innerWidth < 1024) {
                        closeSidebarFn();
                    }
                });
            });

            // Manejar redimensionamiento de ventana
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    // Si cambiamos de multi-columna a móvil, restaurar todas las máquinas
                    if (!isMultiColumnMode()) {
                        showAllMachines();
                    }
                }, 250);
            });

            // ========== MODAL DE EDICIÓN ==========
            const modal = document.getElementById('editModal');
            const closeBtn = document.getElementById('closeModal');

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }

            // Abrir modal al hacer clic en "Editar"
            document.querySelectorAll('.open-edit-modal').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;

                    fetch(`/maquinas/${id}/json`)
                        .then(res => res.json())
                        .then(data => {
                            // Rellenar campos
                            document.getElementById('edit-id').value = data.id ?? '';
                            ['codigo', 'nombre', 'diametro_min', 'diametro_max', 'peso_min',
                                'peso_max', 'ancho_m', 'largo_m', 'tipo'
                            ].forEach(f => {
                                const el = document.getElementById(`edit-${f}`);
                                if (el) el.value = (data[f] ?? '');
                            });

                            // Select obra con fallback
                            const obraSelect = document.getElementById('edit-obra_id');
                            if (obraSelect) {
                                const obraId = data.obra_id ?? '';
                                let opt = obraSelect.querySelector(`option[value="${obraId}"]`);
                                if (!opt && obraId) {
                                    opt = document.createElement('option');
                                    opt.value = obraId;
                                    opt.textContent = (data.obra && data.obra.obra) ? data.obra
                                        .obra : `Obra #${obraId}`;
                                    obraSelect.appendChild(opt);
                                }
                                obraSelect.value = opt ? obraId : '';
                            }

                            openModal();
                        })
                        .catch(err => {
                            console.error('Error al cargar datos de la máquina:', err);
                            alert('No se pudieron cargar los datos de la máquina.');
                        });
                });
            });

            // Cerrar modal con botón "Cancelar"
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            // Cerrar modal con clic en el fondo
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });

            // Cerrar modal con tecla ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (!modal.classList.contains('hidden')) {
                        closeModal();
                    }
                    if (!sidebar.classList.contains('-translate-x-full') && window.innerWidth < 1024) {
                        closeSidebarFn();
                    }
                }
            });

            // Envío del formulario de edición
            document.getElementById('editMaquinaForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const id = document.getElementById('edit-id').value;
                const formData = new FormData(this);
                formData.append('_method', 'PUT');

                fetch(`/maquinas/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': formData.get('_token'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            closeModal();
                            location.reload();
                        } else {
                            return response.json().then(data => {
                                alert(data.message || 'Error al actualizar la máquina.');
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error en la actualización:', error);
                        alert('Error inesperado. Revisa la consola.');
                    });
            });
        })();
    </script>


</x-app-layout>
