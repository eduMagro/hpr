<x-app-layout>
    <x-slot name="title">Máquinas - {{ config('app.name') }}</x-slot>

    <style>
        html {
            scroll-behavior: smooth;
        }

        .machine-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.6);
        }

        .machine-card {
            transition: all 0.25s ease;
        }
    </style>

    <div class="p-4 sm:p-6 lg:p-10 min-h-screen">

        {{-- Header con filtro --}}
        <div class="mb-6 flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between">

            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Máquinas</h1>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                {{-- Filtro de nave --}}
                <select id="naveFilter"
                    class="border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 pr-8 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                    <option value="">Todas las naves</option>
                    @foreach ($obras as $obra)
                        <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                    @endforeach
                </select>

                {{-- Filtro de máquina --}}
                <select id="machineFilter"
                    class="border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 pr-8 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                    <option value="">Todas las máquinas ({{ $registrosMaquina->count() }})</option>
                    @foreach ($registrosMaquina as $maquina)
                        <option value="{{ $maquina->id }}">{{ $maquina->codigo }} — {{ $maquina->nombre }}</option>
                    @endforeach
                </select>

                {{-- Botón crear nueva máquina --}}
                <x-tabla.boton-azul :href="route('maquinas.create')" class="whitespace-nowrap">
                    ➕ Nueva Máquina
                </x-tabla.boton-azul>
            </div>
        </div>

        {{-- Listado Section --}}
        <div class="space-y-6">
            {{-- Grid responsive para las tarjetas --}}
            <div id="machinesGrid"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                @forelse($registrosMaquina as $maquina)
                    <div id="maquina-{{ $maquina->id }}" data-machine-id="{{ $maquina->id }}"
                        data-obra-id="{{ $maquina->obra_id }}"
                        class="machine-card bg-white dark:bg-gray-900/95 border border-gray-300 dark:border-blue-500/40 rounded-xl shadow-lg overflow-hidden flex flex-col h-full dark:backdrop-blur-sm">

                        {{-- Imagen responsive --}}
                        <div
                            class="w-full h-48 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 flex items-center justify-center flex-shrink-0">
                            @if ($maquina->imagen)
                                <div class="w-full h-full bg-center bg-cover bg-no-repeat transition-transform hover:scale-105 duration-500"
                                    style="background-image: url('{{ asset($maquina->imagen) }}');">
                                </div>
                            @else
                                <div class="text-center">
                                    <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    <span class="text-gray-500 text-sm mt-2 block">Sin imagen</span>
                                </div>
                            @endif
                        </div>

                        {{-- Datos principales --}}
                        <div class="p-4 space-y-3 flex-1 flex flex-col">
                            {{-- Título --}}
                            <div class="border-b border-gray-200 dark:border-gray-700/50 pb-2">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 line-clamp-2">
                                    <span class="text-blue-600 dark:text-blue-400">{{ $maquina->codigo }}</span>
                                    <span class="text-gray-400 dark:text-gray-600 text-sm">—</span>
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-300 font-normal">{{ $maquina->nombre }}</span>
                                </h3>
                            </div>

                            {{-- Grid de información --}}
                            <div class="flex gap-6 text-sm">
                                {{-- Nave --}}
                                <div class="flex items-start flex-col">
                                    <span class="font-semibold text-blue-600 dark:text-blue-400 text-xs">Nave:</span>
                                    <span class="text-gray-600 dark:text-gray-400 text-xs truncate w-full">
                                        {{ $maquina->obra?->obra ?? 'Sin Nave asignada' }}
                                    </span>
                                </div>

                                {{-- Diámetros --}}
                                <div class="flex items-start flex-col">
                                    <span class="font-semibold text-blue-600 dark:text-blue-400 text-xs">Diámetros:</span>
                                    <span class="text-gray-600 dark:text-gray-400 text-xs">
                                        {{ $maquina->diametro_min }} - {{ $maquina->diametro_max }} mm
                                    </span>
                                </div>
                            </div>

                            {{-- Subir imagen --}}
                            <form action="{{ route('maquinas.imagen', $maquina->id) }}" method="POST"
                                enctype="multipart/form-data"
                                class="border-t border-gray-200 dark:border-gray-700/50 pt-3 mt-auto">
                                @csrf
                                @method('PUT')

                                <details class="group">
                                    <summary
                                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 cursor-pointer hover:text-blue-500 dark:hover:text-blue-300 list-none flex items-center justify-between">
                                        <span>Actualizar imagen</span>
                                        <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </summary>
                                    <div class="flex flex-col gap-2 mt-2">
                                        <input type="file" name="imagen" accept="image/*"
                                            class="text-xs text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-lg p-1.5 file:mr-2 file:py-1 file:px-2 file:border-0 file:bg-blue-600 file:text-white file:rounded-md file:text-xs file:font-medium hover:file:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required>
                                        <button type="submit"
                                            class="bg-green-600 hover:bg-green-500 text-white px-4 py-1.5 rounded-lg text-xs font-medium transition-colors shadow-sm">
                                            Subir
                                        </button>
                                    </div>
                                </details>
                            </form>
                        </div>

                        {{-- Acciones --}}
                        <div
                            class="bg-gray-50 dark:bg-gray-800/50 px-3 py-3 border-t border-gray-200 dark:border-gray-700/50 flex flex-col gap-2 mt-auto">
                            <a href="{{ route('maquinas.show', $maquina->id) }}" wire:navigate
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
                    <div
                        class="col-span-full bg-white dark:bg-gray-900/95 rounded-xl border border-dashed border-gray-300 dark:border-blue-500/40 p-12 text-center">
                        <svg class="mx-auto h-16 w-16 text-blue-500/60" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-800 dark:text-gray-200">No hay máquinas disponibles
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">Comienza creando una nueva máquina.</p>
                    </div>
                @endforelse
            </div>

            {{-- Paginación --}}
            @if ($registrosMaquina->hasPages())
                <div class="mt-8 flex justify-center">
                    {{ $registrosMaquina->links('vendor.pagination.bootstrap-5') }}
                </div>
            @endif
        </div>



        {{-- Modal de edición --}}
        <div id="editModal"
            class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] justify-center items-center p-2 overflow-y-auto">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl my-8 mx-auto transform transition-all border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-xl">
                    <h2 class="text-xl font-bold text-white">Editar Máquina</h2>
                </div>

                <form id="editMaquinaForm" class="p-6">
                    @csrf
                    <input type="hidden" id="edit-id" name="id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto pr-2">
                        <div>
                            <label for="edit-codigo"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Código</label>
                            <input id="edit-codigo" name="codigo" type="text"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>

                        <div>
                            <label for="edit-nombre"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Nombre</label>
                            <input id="edit-nombre" name="nombre" type="text"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>

                        <div>
                            <label for="edit-obra_id"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Obra
                                asignada</label>
                            <select id="edit-obra_id" name="obra_id"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="">Sin asignar</option>
                                @foreach ($obras as $obra)
                                    <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="edit-tipo"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Tipo</label>
                            <select id="edit-tipo" name="tipo"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="">— Selecciona tipo —</option>
                                <option value="cortadora_dobladora">Cortadora-Dobladora</option>
                                <option value="ensambladora">Ensambladora</option>
                                <option value="soldadora">Soldadora</option>
                                <option value="cortadora_manual">Cortadora manual</option>
                                <option value="dobladora_manual">Dobladora manual</option>
                                <option value="grua">Grúa</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit-diametro_min"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Diámetro
                                mínimo (mm)</label>
                            <input id="edit-diametro_min" name="diametro_min" type="number"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>

                        <div>
                            <label for="edit-diametro_max"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Diámetro
                                máximo (mm)</label>
                            <input id="edit-diametro_max" name="diametro_max" type="number"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>

                        <div>
                            <label for="edit-peso_min"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Peso
                                mínimo</label>
                            <input id="edit-peso_min" name="peso_min" type="number"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>

                        <div>
                            <label for="edit-peso_max"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Peso
                                máximo</label>
                            <input id="edit-peso_max" name="peso_max" type="number"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>

                        <div>
                            <label for="edit-ancho_m"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Ancho
                                (m)</label>
                            <input id="edit-ancho_m" name="ancho_m" type="number" step="0.01"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>

                        <div>
                            <label for="edit-largo_m"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Largo
                                (m)</label>
                            <input id="edit-largo_m" name="largo_m" type="number" step="0.01"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                    </div>

                    <div
                        class="flex flex-col sm:flex-row justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" id="closeModal"
                            class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-colors border border-gray-300 dark:border-gray-600">
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
            // Migración a patrón de inicialización SPA Livewire
            window.initMaquinasIndexPage = function () {
                if (document.body.dataset.maquinasIndexPageInit === 'true') return;
                console.log('Inicializando Maquinas Index Page');

                const machineFilter = document.getElementById('machineFilter');
                const naveFilter = document.getElementById('naveFilter');
                const allMachineCards = document.querySelectorAll('.machine-card');
                const modal = document.getElementById('editModal');
                const closeBtn = document.getElementById('closeModal');
                const form = document.getElementById('editMaquinaForm');

                // --- Handlers ---

                // Función para aplicar ambos filtros
                function applyFilters() {
                    const selectedMachineId = machineFilter.value;
                    const selectedNaveId = naveFilter.value;

                    allMachineCards.forEach(card => {
                        const matchesMachine = selectedMachineId === '' || card.dataset.machineId ===
                            selectedMachineId;
                        const matchesNave = selectedNaveId === '' || card.dataset.obraId === selectedNaveId;

                        if (matchesMachine && matchesNave) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    // Scroll al top si se filtró
                    if (selectedMachineId || selectedNaveId) {
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                }

                function openModal() {
                    if (!modal) return;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                }

                function closeModal() {
                    if (!modal) return;
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                }

                function handleModalClick(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                }

                function handleEscKey(e) {
                    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                }

                function handleEditClick(e) {
                    const btn = e.target.closest('.open-edit-modal');
                    if (!btn) return;

                    const id = btn.dataset.id;
                    fetch(`/maquinas/${id}/json`)
                        .then(res => res.json())
                        .then(data => {
                            document.getElementById('edit-id').value = data.id ?? '';
                            ['codigo', 'nombre', 'diametro_min', 'diametro_max', 'peso_min',
                                'peso_max', 'ancho_m', 'largo_m', 'tipo'
                            ].forEach(f => {
                                const el = document.getElementById(`edit-${f}`);
                                if (el) el.value = (data[f] ?? '');
                            });

                            const obraSelect = document.getElementById('edit-obra_id');
                            if (obraSelect) {
                                const obraId = data.obra_id ?? '';
                                let opt = obraSelect.querySelector(`option[value="${obraId}"]`);
                                if (!opt && obraId) {
                                    opt = document.createElement('option');
                                    opt.value = obraId;
                                    opt.textContent = (data.obra && data.obra.obra) ? data.obra.obra :
                                        `Obra #${obraId}`;
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
                }

                // --- Event Listeners ---

                if (machineFilter) machineFilter.addEventListener('change', applyFilters);
                if (naveFilter) naveFilter.addEventListener('change', applyFilters);

                // Usamos delegación de eventos para los botones de editar (mejor performance y menos listeners)
                const machinesGrid = document.getElementById('machinesGrid');
                if (machinesGrid) {
                    machinesGrid.addEventListener('click', handleEditClick);
                }

                if (closeBtn) closeBtn.addEventListener('click', closeModal);
                if (modal) modal.addEventListener('click', handleModalClick);
                document.addEventListener('keydown', handleEscKey);

                if (form) {
                    // Removemos el listener previo si existiera (aunque livewire reemplaza el DOM, es buena práctica)
                    // form.removeEventListener('submit', ...); 
                    // Pero aquí definimos el handler inline wrapper
                    form.addEventListener('submit', function (e) {
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
                }

                // --- Cleanup Function ---
                // Exportamos una función de limpieza específica si fuera necesario
                // Pero usamos el sistema global:

                // Registrar limpio
                document.body.dataset.maquinasIndexPageInit = 'true';

                // Definir función de limpieza para este init
                const cleanup = () => {
                    if (machineFilter) machineFilter.removeEventListener('change', applyFilters);
                    if (naveFilter) naveFilter.removeEventListener('change', applyFilters);
                    if (machinesGrid) machinesGrid.removeEventListener('click', handleEditClick);
                    if (closeBtn) closeBtn.removeEventListener('click', closeModal);
                    if (modal) modal.removeEventListener('click', handleModalClick);
                    document.removeEventListener('keydown', handleEscKey);
                    document.body.dataset.maquinasIndexPageInit = 'false';

                    // Removerse a sí mismo de los initializers (opcional pero limpio)
                };

                // Hookear limpieza al evento de navegación global
                document.addEventListener('livewire:navigating', cleanup, {
                    once: true
                });
            };

            // Registrar en sistema global
            window.pageInitializers = window.pageInitializers || [];
            window.pageInitializers.push(window.initMaquinasIndexPage);

            // Listeners iniciales
            if (typeof Livewire !== 'undefined') {
                document.addEventListener('livewire:navigated', window.initMaquinasIndexPage);
            }
            document.addEventListener('DOMContentLoaded', window.initMaquinasIndexPage);

            // Ejecutar inmediatamente si ya cargó (caso edge)
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                window.initMaquinasIndexPage();
            }
        </script>
</x-app-layout>