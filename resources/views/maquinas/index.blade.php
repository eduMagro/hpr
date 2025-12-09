<x-app-layout>
    <x-slot name="title">Máquinas - {{ config('app.name') }}</x-slot>

    <style>
        html {
            scroll-behavior: smooth;
        }

        .machine-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .machine-card {
            transition: all 0.3s ease;
        }
    </style>

    <div class="p-4 sm:p-6 lg:p-10 bg-gray-50 min-h-screen">

        {{-- Header con filtro --}}
        <div class="mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Máquinas</h1>

            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                {{-- Filtro de nave --}}
                <select id="naveFilter" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                    <option value="">Todas las naves</option>
                    @foreach ($obras as $obra)
                        <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                    @endforeach
                </select>

                {{-- Filtro de máquina --}}
                <select id="machineFilter" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
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

        {{-- Grid responsive para las tarjetas --}}
        <div id="machinesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
            @forelse($registrosMaquina as $maquina)
                <div id="maquina-{{ $maquina->id }}" data-machine-id="{{ $maquina->id }}" data-obra-id="{{ $maquina->obra_id }}"
                    class="machine-card bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden flex flex-col h-full">

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

        {{-- Paginación --}}
        @if ($registrosMaquina->hasPages())
            <div class="mt-8 flex justify-center">
                {{ $registrosMaquina->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- Modal de edición --}}
    <div id="editModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 justify-center items-center p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl my-8 mx-auto transform transition-all">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-xl">
                <h2 class="text-xl font-bold text-white">Editar Máquina</h2>
            </div>

            <form id="editMaquinaForm" class="p-6">
                @csrf
                <input type="hidden" id="edit-id" name="id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto pr-2">
                    <div>
                        <label for="edit-codigo" class="block text-sm font-semibold text-gray-700 mb-2">Código</label>
                        <input id="edit-codigo" name="codigo" type="text"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label for="edit-nombre" class="block text-sm font-semibold text-gray-700 mb-2">Nombre</label>
                        <input id="edit-nombre" name="nombre" type="text"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label for="edit-obra_id" class="block text-sm font-semibold text-gray-700 mb-2">Obra asignada</label>
                        <select id="edit-obra_id" name="obra_id"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                            <option value="">Sin asignar</option>
                            @foreach ($obras as $obra)
                                <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="edit-tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo</label>
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

                    <div>
                        <label for="edit-diametro_min" class="block text-sm font-semibold text-gray-700 mb-2">Diámetro mínimo (mm)</label>
                        <input id="edit-diametro_min" name="diametro_min" type="number"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label for="edit-diametro_max" class="block text-sm font-semibold text-gray-700 mb-2">Diámetro máximo (mm)</label>
                        <input id="edit-diametro_max" name="diametro_max" type="number"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label for="edit-peso_min" class="block text-sm font-semibold text-gray-700 mb-2">Peso mínimo</label>
                        <input id="edit-peso_min" name="peso_min" type="number"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label for="edit-peso_max" class="block text-sm font-semibold text-gray-700 mb-2">Peso máximo</label>
                        <input id="edit-peso_max" name="peso_max" type="number"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label for="edit-ancho_m" class="block text-sm font-semibold text-gray-700 mb-2">Ancho (m)</label>
                        <input id="edit-ancho_m" name="ancho_m" type="number" step="0.01"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label for="edit-largo_m" class="block text-sm font-semibold text-gray-700 mb-2">Largo (m)</label>
                        <input id="edit-largo_m" name="largo_m" type="number" step="0.01"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                </div>

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
            const machineFilter = document.getElementById('machineFilter');
            const naveFilter = document.getElementById('naveFilter');
            const allMachineCards = document.querySelectorAll('.machine-card');

            // Función para aplicar ambos filtros
            function applyFilters() {
                const selectedMachineId = machineFilter.value;
                const selectedNaveId = naveFilter.value;

                allMachineCards.forEach(card => {
                    const matchesMachine = selectedMachineId === '' || card.dataset.machineId === selectedMachineId;
                    const matchesNave = selectedNaveId === '' || card.dataset.obraId === selectedNaveId;

                    if (matchesMachine && matchesNave) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Scroll al top si se filtró
                if (selectedMachineId || selectedNaveId) {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }

            // Filtrar máquinas por select
            machineFilter.addEventListener('change', applyFilters);

            // Filtrar por nave
            naveFilter.addEventListener('change', applyFilters);

            // Modal de edición
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

            document.querySelectorAll('.open-edit-modal').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;

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
                                    opt.textContent = (data.obra && data.obra.obra) ? data.obra.obra : `Obra #${obraId}`;
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

            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

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
