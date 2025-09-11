<x-app-layout>
    <x-slot name="title">Máquinas - {{ config('app.name') }}</x-slot>

    <style>
        html {
            scroll-behavior: smooth;
        }
    </style>

    <div class="flex w-full">

        {{-- 🚀 MENÚ LATERAL FIJO --}}
        <aside role="navigation" class="fixed left-0 h-screen w-64 bg-white shadow-md z-20 overflow-y-auto">
            <x-tabla.boton-azul :href="route('maquinas.create')" class="m-4 w-[calc(100%-2rem)] text-center">
                ➕ Crear Nueva Máquina
            </x-tabla.boton-azul>

            <div class="p-4 border-t border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-3 px-2">Navegación por Máquina</h3>

                <ul class="space-y-1">
                    @foreach ($registrosMaquina as $maquina)
                        <li>
                            <a href="#maquina-{{ $maquina->id }}"
                                class="block px-3 py-2 text-sm rounded hover:bg-gray-100 hover:text-blue-800 font-medium transition-colors duration-200 truncate">
                                {{ $maquina->codigo }} — {{ $maquina->nombre }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>

        <!-- Contenido principal -->
        <div class="flex-1 ml-64 p-10">

            @forelse($registrosMaquina as $maquina)
                <div id="maquina-{{ $maquina->id }}"
                    class="scroll-mt-28 bg-white border rounded-lg shadow-md mb-8 overflow-hidden">

                    <!-- Imagen -->
                    <div class="w-full h-64 bg-gray-100 flex items-center justify-center">
                        @if ($maquina->imagen)
                            <img src="{{ asset($maquina->imagen) }}" alt="Imagen de {{ $maquina->nombre }}"
                                class="object-contain h-full">
                        @else
                            <span class="text-gray-500">Sin imagen</span>
                        @endif
                    </div>

                    <!-- Datos principales -->
                    <div class="p-4 space-y-2">
                        <h3 class="text-xl font-bold text-gray-800">
                            {{ $maquina->codigo }} — {{ $maquina->nombre }}
                        </h3>

                        <p class="text-sm text-gray-700">
                            Estado:
                            @php
                                $inProduction =
                                    $maquina->tipo == 'ensambladora'
                                        ? $maquina->elementos_ensambladora > 0
                                        : $maquina->elementos_count > 0;
                            @endphp
                            <span class="{{ $inProduction ? 'text-green-600' : 'text-red-500' }}">
                                {{ $inProduction ? 'En producción' : 'Sin trabajo' }}
                            </span>
                        </p>
                        <p class="text-sm text-gray-700">
                            Nave:

                            <span>
                                {{ $maquina->obra?->obra ?? 'Sin Nave asignada' }}

                            </span>
                        </p>

                        @php
                            $asignacionesHoy = $usuariosPorMaquina->get($maquina->id, collect());
                            $ordenTurno = ['noche' => 0, 'mañana' => 1, 'tarde' => 2];
                            $asignacionesOrdenadas = $asignacionesHoy->sortBy(function ($asig) use ($ordenTurno) {
                                $nombreTurno = strtolower($asig->turno->nombre ?? '');
                                return $ordenTurno[$nombreTurno] ?? 99;
                            });
                        @endphp

                        <div class="text-sm text-gray-700 mb-2">
                            <strong>Operarios en turno:</strong>
                            @if ($asignacionesOrdenadas->isEmpty())
                                <span class="text-gray-500">Ninguno</span>
                            @else
                                <ul class="list-disc pl-5">
                                    @foreach ($asignacionesOrdenadas as $asig)
                                        <li>
                                            {{ $asig->user->name }}
                                            <span class="text-gray-500 text-xs">
                                                ({{ ucfirst(data_get($asig, 'turno.nombre', 'Sin turno')) }})
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <p class="text-sm text-gray-700">
                            Rango de diámetros: <strong>{{ $maquina->diametro_min }} mm</strong> -
                            <strong>{{ $maquina->diametro_max }} mm</strong>
                        </p>

                        <!-- Subir imagen -->
                        <form action="{{ route('maquinas.imagen', $maquina->id) }}" method="POST"
                            enctype="multipart/form-data" class="mt-2">
                            @csrf
                            @method('PUT')

                            <div class="flex items-center gap-2">
                                <input type="file" name="imagen" accept="image/*"
                                    class="block w-full text-sm text-gray-600 border border-gray-300 rounded p-1 file:mr-2 file:py-1 file:px-3 file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    required>
                                <button type="submit"
                                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                                    Subir
                                </button>
                            </div>
                        </form>
                    </div>


                    <!-- Acciones finales: eliminar, editar, iniciar sesión -->
                    <div class="mt-4 flex justify-between items-center">
                        <x-tabla.boton-eliminar :action="route('maquinas.destroy', $maquina->id)" />
                        <a href="javascript:void(0);" class="text-blue-500 hover:text-blue-700 text-sm open-edit-modal"
                            data-id="{{ $maquina->id }}">
                            Editar
                        </a>
                        <a href="{{ route('maquinas.show', $maquina->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">
                            Iniciar Sesión
                        </a>

                    </div>
                </div>
            @empty
                <p class="text-gray-600">No hay máquinas disponibles.</p>
            @endforelse
        </div>
    </div>

    <!-- Paginación -->
    <div class="mt-6 flex justify-center">
        {{ $registrosMaquina->links('vendor.pagination.bootstrap-5') }}
    </div>

    <!-- Modal de edición -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 justify-center items-center">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl p-6 relative">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Editar Máquina</h2>

            <form id="editMaquinaForm">
                @csrf
                <input type="hidden" id="edit-id" name="id">

                <!-- Grid de 2 columnas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Código -->
                    <div>
                        <label for="edit-codigo" class="block text-sm font-semibold text-gray-700 mb-1">Código</label>
                        <input id="edit-codigo" name="codigo" type="text"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label for="edit-nombre" class="block text-sm font-semibold text-gray-700 mb-1">Nombre</label>
                        <input id="edit-nombre" name="nombre" type="text"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>

                    <!-- Obra asignada -->
                    <div>
                        <label for="edit-obra_id" class="block text-sm font-semibold text-gray-700 mb-1">Obra
                            asignada</label>
                        <select id="edit-obra_id" name="obra_id" class="w-full border rounded px-3 py-2 text-sm">
                            <option value="">Sin asignar</option>
                            @foreach ($obras as $obra)
                                <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Diámetro mínimo -->
                    <div>
                        <label for="edit-diametro_min" class="block text-sm font-semibold text-gray-700 mb-1">Diámetro
                            mínimo</label>
                        <input id="edit-diametro_min" name="diametro_min" type="number"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>

                    <!-- Diámetro máximo -->
                    <div>
                        <label for="edit-diametro_max" class="block text-sm font-semibold text-gray-700 mb-1">Diámetro
                            máximo</label>
                        <input id="edit-diametro_max" name="diametro_max" type="number"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>

                    <!-- Peso mínimo -->
                    <div>
                        <label for="edit-peso_min" class="block text-sm font-semibold text-gray-700 mb-1">Peso
                            mínimo</label>
                        <input id="edit-peso_min" name="peso_min" type="number"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>

                    <!-- Peso máximo -->
                    <div>
                        <label for="edit-peso_max" class="block text-sm font-semibold text-gray-700 mb-1">Peso
                            máximo</label>
                        <input id="edit-peso_max" name="peso_max" type="number"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>

                    <!-- Ancho en metros -->
                    <div>
                        <label for="edit-ancho_m" class="block text-sm font-semibold text-gray-700 mb-1">Ancho
                            (m)</label>
                        <input id="edit-ancho_m" name="ancho_m" type="number" step="0.01"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>

                    <!-- Largo en metros -->
                    <div>
                        <label for="edit-largo_m" class="block text-sm font-semibold text-gray-700 mb-1">Largo
                            (m)</label>
                        <input id="edit-largo_m" name="largo_m" type="number" step="0.01"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" id="closeModal"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        (function() {
            const modal = document.getElementById('editModal');
            const modalContent = document.getElementById('editModalContent');
            const closeBtn = document.getElementById('closeModal');

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Abrir: cuando haces clic en “Editar”
            document.querySelectorAll('.open-edit-modal').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;

                    fetch(`/maquinas/${id}/json`)
                        .then(res => res.json())
                        .then(data => {
                            // Rellenar campos
                            document.getElementById('edit-id').value = data.id ?? '';
                            ['codigo', 'nombre', 'diametro_min', 'diametro_max', 'peso_min',
                                'peso_max', 'ancho_m', 'largo_m'
                            ]
                            .forEach(f => {
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

            // Cerrar: botón “Cancelar”
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            // Cerrar: clic en el fondo (pero no dentro del panel)
            modal.addEventListener('click', (e) => {
                if (!modalContent.contains(e.target)) closeModal();
            });

            // Cerrar: tecla ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            // Envío del formulario (sin cambios, pero no toques display inline)
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
