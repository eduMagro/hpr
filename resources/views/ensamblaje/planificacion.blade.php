<x-app-layout>
    <x-slot name="title">Planificación de Ensamblaje - {{ config('app.name') }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="text-2xl">🏗️</span>
                Planificación de Ensamblaje
            </h2>

            @if($maquinas->isNotEmpty())
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700">Máquina:</label>
                    <select id="selector-maquina" onchange="cambiarMaquina(this.value)"
                        class="border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @foreach($maquinas as $m)
                            <option value="{{ $m->id }}" {{ $maquinaSeleccionada?->id == $m->id ? 'selected' : '' }}>
                                {{ $m->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($maquinas->isEmpty())
                <div class="bg-yellow-50 border-2 border-yellow-300 rounded-xl p-8 text-center">
                    <div class="text-5xl mb-4">⚠️</div>
                    <h3 class="text-xl font-bold text-yellow-800 mb-2">No hay máquinas ensambladoras</h3>
                    <p class="text-yellow-600">Crea una máquina con tipo "ensambladora" para usar esta funcionalidad.</p>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- COLUMNA IZQUIERDA: Entidades listas para ensamblar -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="p-4 bg-gradient-to-r from-green-500 to-green-600 text-white">
                            <h3 class="font-bold text-lg flex items-center gap-2">
                                <span>✅</span> Entidades Listas para Ensamblar
                            </h3>
                            <p class="text-green-100 text-sm mt-1">
                                Todos sus elementos están fabricados
                            </p>
                        </div>

                        <div class="p-4 max-h-[600px] overflow-y-auto" id="lista-entidades-listas">
                            @forelse($entidadesListas as $entidad)
                                <div class="mb-3 p-3 bg-green-50 border-2 border-green-200 rounded-lg hover:border-green-400 transition cursor-pointer"
                                     data-entidad-id="{{ $entidad->id }}"
                                     onclick="toggleSeleccion(this)">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" class="checkbox-entidad rounded border-gray-300"
                                                       data-id="{{ $entidad->id }}" onclick="event.stopPropagation()">
                                                <h4 class="font-bold text-gray-800">{{ $entidad->marca }}</h4>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">{{ $entidad->situacion }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                📋 {{ $entidad->planilla->codigo ?? 'Sin planilla' }}
                                                @if($entidad->planilla?->obra)
                                                    | 🏗️ {{ $entidad->planilla->obra->obra }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <span class="bg-green-600 text-white px-2 py-1 rounded text-sm font-bold">
                                                {{ $entidad->cantidad }} uds
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $entidad->estado_elementos['fabricados'] ?? 0 }}/{{ $entidad->estado_elementos['total'] ?? 0 }} elem.
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Composición resumida --}}
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($entidad->barras ?? [] as $barra)
                                            <span class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs">
                                                {{ $barra['cantidad'] ?? 0 }}x Ø{{ $barra['diametro'] ?? '?' }}
                                            </span>
                                        @endforeach
                                        @foreach($entidad->estribos ?? [] as $estribo)
                                            <span class="bg-amber-200 text-amber-700 px-2 py-0.5 rounded text-xs">
                                                {{ $estribo['cantidad'] ?? 0 }}x Ø{{ $estribo['diametro'] ?? '?' }} est.
                                            </span>
                                        @endforeach
                                    </div>

                                    {{-- Botón añadir individual --}}
                                    <div class="mt-2 flex justify-end">
                                        <button type="button"
                                            onclick="event.stopPropagation(); asignarEntidad({{ $entidad->id }})"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-medium">
                                            ➕ Añadir a cola
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8 text-gray-500">
                                    <div class="text-4xl mb-2">📭</div>
                                    <p>No hay entidades listas para ensamblar</p>
                                    <p class="text-sm mt-1">Las entidades aparecerán aquí cuando todos sus elementos estén fabricados</p>
                                </div>
                            @endforelse
                        </div>

                        @if($entidadesListas->isNotEmpty())
                            <div class="p-4 bg-gray-50 border-t flex justify-between items-center">
                                <span class="text-sm text-gray-600">
                                    <span id="contador-seleccion">0</span> seleccionadas
                                </span>
                                <button type="button" onclick="asignarSeleccionadas()"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                                    ➕ Añadir Seleccionadas
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- COLUMNA DERECHA: Cola de ensamblaje -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="p-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                            <h3 class="font-bold text-lg flex items-center gap-2">
                                <span>📋</span> Cola de Ensamblaje
                                <span class="bg-blue-400 px-2 py-0.5 rounded text-sm ml-2">
                                    {{ $maquinaSeleccionada?->nombre }}
                                </span>
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">
                                Arrastra para reordenar
                            </p>
                        </div>

                        {{-- En proceso --}}
                        @if($entidadesEnProgreso->isNotEmpty())
                            <div class="p-4 bg-yellow-50 border-b-2 border-yellow-200">
                                <h4 class="font-bold text-yellow-800 mb-2 flex items-center gap-1">
                                    <span>🔨</span> En Proceso
                                </h4>
                                @foreach($entidadesEnProgreso as $orden)
                                    <div class="p-2 bg-yellow-100 border border-yellow-300 rounded mb-2">
                                        <div class="font-medium text-yellow-800">{{ $orden->entidad->marca }}</div>
                                        <div class="text-sm text-yellow-600">{{ $orden->entidad->situacion }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="p-4 max-h-[500px] overflow-y-auto" id="lista-cola">
                            @forelse($colaEnsamblaje as $orden)
                                <div class="mb-2 p-3 bg-blue-50 border-2 border-blue-200 rounded-lg cursor-move hover:border-blue-400 transition"
                                     data-orden-id="{{ $orden->id }}"
                                     draggable="true">
                                    <div class="flex items-center gap-3">
                                        <div class="text-gray-400 cursor-move">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                            </svg>
                                        </div>
                                        <div class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">
                                            {{ $orden->posicion }}
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-800">{{ $orden->entidad->marca }}</h4>
                                            <p class="text-sm text-gray-600">{{ $orden->entidad->situacion }}</p>
                                            <p class="text-xs text-gray-500">
                                                📋 {{ $orden->entidad->planilla->codigo ?? 'N/A' }}
                                                | {{ $orden->entidad->cantidad }} uds
                                            </p>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            @if($orden->estado === 'pausada')
                                                <span class="bg-orange-500 text-white px-2 py-0.5 rounded text-xs">PAUSADA</span>
                                            @endif
                                            <button type="button" onclick="quitarDeCola({{ $orden->id }})"
                                                class="text-red-500 hover:text-red-700 text-sm">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8 text-gray-500">
                                    <div class="text-4xl mb-2">📭</div>
                                    <p>Cola vacía</p>
                                    <p class="text-sm mt-1">Añade entidades desde la columna izquierda</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="p-4 bg-gray-50 border-t">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">
                                    {{ $colaEnsamblaje->count() }} entidades en cola
                                </span>
                                <a href="{{ route('maquinas.show', $maquinaSeleccionada?->id ?? 0) }}"
                                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                                    🏗️ Ir a Producción
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            @endif
        </div>
    </div>

    <script>
        const maquinaId = {{ $maquinaSeleccionada?->id ?? 0 }};

        function cambiarMaquina(id) {
            window.location.href = '{{ route("ensamblaje.planificacion") }}?maquina_id=' + id;
        }

        function toggleSeleccion(element) {
            const checkbox = element.querySelector('.checkbox-entidad');
            checkbox.checked = !checkbox.checked;
            actualizarContador();
        }

        function actualizarContador() {
            const seleccionadas = document.querySelectorAll('.checkbox-entidad:checked').length;
            document.getElementById('contador-seleccion').textContent = seleccionadas;
        }

        async function asignarEntidad(entidadId) {
            try {
                const response = await fetch('{{ route("ensamblaje.asignar") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        maquina_id: maquinaId,
                        planilla_entidad_id: entidadId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Añadida',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        }

        async function asignarSeleccionadas() {
            const checkboxes = document.querySelectorAll('.checkbox-entidad:checked');
            const entidades = Array.from(checkboxes).map(cb => cb.dataset.id);

            if (entidades.length === 0) {
                Swal.fire('Aviso', 'Selecciona al menos una entidad', 'warning');
                return;
            }

            try {
                const response = await fetch('{{ route("ensamblaje.asignar-multiple") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        maquina_id: maquinaId,
                        entidades: entidades
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Añadidas',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        }

        async function quitarDeCola(ordenId) {
            const result = await Swal.fire({
                title: '¿Quitar de la cola?',
                text: 'La entidad se quitará de la cola de ensamblaje',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch('{{ route("ensamblaje.quitar") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ orden_id: ordenId })
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        }

        // Drag & Drop para reordenar
        document.addEventListener('DOMContentLoaded', function() {
            const lista = document.getElementById('lista-cola');
            if (!lista) return;

            let draggedItem = null;

            lista.querySelectorAll('[draggable="true"]').forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    draggedItem = this;
                    this.classList.add('opacity-50');
                });

                item.addEventListener('dragend', function() {
                    this.classList.remove('opacity-50');
                    guardarOrden();
                });

                item.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    const afterElement = getDragAfterElement(lista, e.clientY);
                    if (afterElement == null) {
                        lista.appendChild(draggedItem);
                    } else {
                        lista.insertBefore(draggedItem, afterElement);
                    }
                });
            });

            function getDragAfterElement(container, y) {
                const draggableElements = [...container.querySelectorAll('[draggable="true"]:not(.opacity-50)')];
                return draggableElements.reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;
                    if (offset < 0 && offset > closest.offset) {
                        return { offset: offset, element: child };
                    } else {
                        return closest;
                    }
                }, { offset: Number.NEGATIVE_INFINITY }).element;
            }

            async function guardarOrden() {
                const items = lista.querySelectorAll('[data-orden-id]');
                const orden = Array.from(items).map(item => item.dataset.ordenId);

                try {
                    await fetch('{{ route("ensamblaje.reordenar") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            maquina_id: maquinaId,
                            orden: orden
                        })
                    });
                } catch (error) {
                    console.error('Error al guardar orden:', error);
                }
            }
        });
    </script>
</x-app-layout>
