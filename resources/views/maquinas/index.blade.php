<x-app-layout>
    <x-slot name="title">Máquinas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Máquinas') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <!-- Botón para crear una nueva máquina -->
        <div class="mb-4">
            <x-tabla.boton-azul :href="route('maquinas.create')">
                ➕ Crear Nueva Máquina
            </x-tabla.boton-azul>
        </div>

        <!-- Lista de máquinas en grid responsive -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            @forelse($registrosMaquina as $maquina)
                <!-- Componente Alpine para control de desplegable por máquina -->
                <div x-data="{ openMachine: false }" class="bg-white border p-4 shadow-md rounded-lg">
                    <!-- Cabecera de la tarjeta de máquina -->
                    <button @click="openMachine = !openMachine" class="w-full flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-xl break-words">{{ $maquina->codigo }} — {{ $maquina->nombre }}
                            </h3>
                            <p class="text-sm text-gray-600">
                                <strong>Estado:</strong>
                                @php
                                    $inProduction =
                                        $maquina->tipo == 'ensambladora'
                                            ? $maquina->elementos_ensambladora > 0
                                            : $maquina->elementos_count > 0;
                                @endphp
                                <span class="{{ $inProduction ? 'text-success' : 'text-danger' }}">
                                    {{ $inProduction ? 'En producción' : 'Sin trabajo' }}
                                </span>
                            </p>
                        </div>
                        <!-- Icono de flecha que rota al expandir -->
                        <svg :class="openMachine ? 'transform rotate-180' : ''" class="h-6 w-6 transition-transform"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Contenido desplegable de la máquina -->
                    <div x-show="openMachine" x-collapse class="mt-4 space-y-4">
                        <!-- Botones QR y parámetros -->
                        <div class="flex items-center space-x-2">
                            <button
                                onclick="generateAndPrintQR('{{ $maquina->id }}','{{ $maquina->nombre }}','MÁQUINA')"
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                QR
                            </button>
                            <div id="qrCanvas_{{ $maquina->id }}" style="display:none;"></div>
                            <p><strong>Diámetros:</strong> {{ $maquina->diametro_min }} - {{ $maquina->diametro_max }}
                            </p>
                        </div>

                        {{-- Cola de planillas agrupadas por planilla_id --}}
                        @php
                            // recogemos, para esta máquina ($maquina->id),
                            // el conjunto de elementos preparado en el controlador
                            $elements = $colaPorMaquina->get($maquina->id, collect());

                            // dentro de esos elementos, agrupamos por planilla
                            $groupedByPlanilla = $elements->groupBy(fn($e) => $e->planilla->id);
                        @endphp

                        <div>
                            <h4 class="font-semibold mb-2">Cola de Planillas ({{ $elements->count() }})</h4>
                            @if ($groupedByPlanilla->isEmpty())
                                <p class="text-gray-500">No hay planillas en cola.</p>
                            @else
                                @foreach ($groupedByPlanilla as $planillaId => $items)
                                    <div x-data="{ openPlanilla: false }" class="border rounded-lg mb-3">
                                        <button @click="openPlanilla = !openPlanilla"
                                            class="w-full px-3 py-2 flex justify-between items-center bg-gray-100">
                                            <div>
                                                <span class="font-medium">
                                                    {{ $items->first()->planilla->codigo_limpio }}
                                                </span>
                                                <span class="ml-2 text-sm text-gray-600">
                                                    Entrega:
                                                    {{ $items->first()->planilla->fecha_estimada_entrega }}
                                                </span>
                                            </div>
                                            <svg :class="openPlanilla ? 'transform rotate-180' : ''"
                                                class="h-5 w-5 transition-transform" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <ul x-show="openPlanilla" x-collapse class="px-6 py-3 space-y-2 bg-white">
                                            @foreach ($items as $elemento)
                                                <li class="flex justify-between items-center">
                                                    <div>
                                                        <span class="font-semibold">Elemento
                                                            #{{ $elemento->id }}</span>
                                                        <span class="text-sm text-gray-600">
                                                            Tipo: {{ $elemento->figura }}, Estado:
                                                            {{ ucfirst($elemento->estado) }}
                                                        </span>
                                                    </div>
                                                    <a href="{{ route('elementos.show', $elemento->id) }}"
                                                        class="text-blue-500 hover:underline text-sm">
                                                        Ver
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <!-- Productos en máquina -->
                        <div>
                            <h4 class="font-semibold mb-2">Productos en máquina:</h4>
                            @if ($maquina->productos->isEmpty())
                                <p>No hay productos en esta máquina.</p>
                            @else
                                <ul class="list-disc pl-6 space-y-2">
                                    @foreach ($maquina->productos->sortBy([['diametro', 'asc'], ['peso_stock', 'asc']]) as $producto)
                                        <li class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                @if ($producto->tipo === 'ENCARRETADO')
                                                    <div
                                                        class="w-24 h-24 bg-gray-200 relative rounded-lg overflow-hidden">
                                                        <div class="absolute bottom-0 w-full"
                                                            style="height: {{ ($producto->peso_stock / $producto->peso_inicial) * 100 }}%; background-color: green;">
                                                        </div>
                                                        <span
                                                            class="absolute top-2 left-2 text-white">{{ $producto->peso_stock }}
                                                            / {{ $producto->peso_inicial }} kg</span>
                                                    </div>
                                                @elseif($producto->tipo === 'BARRA')
                                                    <div
                                                        class="w-48 h-8 bg-gray-200 relative rounded-lg overflow-hidden">
                                                        <div class="absolute right-0 h-full"
                                                            style="width: {{ ($producto->peso_stock / $producto->peso_inicial) * 100 }}%; background-color: green;">
                                                        </div>
                                                        <span
                                                            class="absolute left-2 top-1/2 transform -translate-y-1/2 text-white">{{ $producto->peso_stock }}
                                                            / {{ $producto->peso_inicial }} kg</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <p><strong>ID:</strong> {{ $producto->id }}</p>
                                                    <p><strong>Diámetro:</strong> {{ $producto->diametro_mm }}</p>
                                                    @if ($producto->tipo === 'BARRA')
                                                        <p><strong>Longitud:</strong> {{ $producto->longitud_metros }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                            <a href="{{ route('productos.show', $producto->id) }}"
                                                class="btn btn-sm btn-primary">Ver</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <!-- Acciones finales: eliminar, editar, iniciar sesión -->
                        <div class="mt-4 flex justify-between items-center">
                            <x-boton-eliminar :action="route('maquinas.destroy', $maquina->id)" />
                            <a href="javascript:void(0);"
                                class="text-blue-500 hover:text-blue-700 text-sm open-edit-modal"
                                data-id="{{ $maquina->id }}">
                                Editar
                            </a>
                            <a href="javascript:void(0);" onclick="seleccionarCompañero({{ $maquina->id }})"
                                class="text-blue-500 hover:text-blue-700 text-sm">Iniciar Sesión</a>
                        </div>
                    </div>
                </div>
            @empty
                <p>No hay máquinas disponibles.</p>
            @endforelse

        </div>

        <!-- Paginación -->
        <div class="mt-6 flex justify-center">
            {{ $registrosMaquina->links('vendor.pagination.bootstrap-5') }}
        </div>

    </div>
    <div id="editModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">

        <div class="bg-white p-6 rounded shadow-lg w-96">
            <h2 class="text-lg font-semibold mb-4">Editar Máquina</h2>
            <form id="editMaquinaForm">
                @csrf
                <input type="hidden" id="edit-id" name="id">

                @foreach ([
        'codigo' => 'Código de la Máquina',
        'nombre' => 'Nombre de la Máquina',
        'diametro_min' => 'Diámetro Mínimo',
        'diametro_max' => 'Diámetro Máximo',
        'peso_min' => 'Peso Mínimo',
        'peso_max' => 'Peso Máximo',
    ] as $field => $label)
                    <div class="form-group mb-4">
                        <label for="edit-{{ $field }}"
                            class="form-label fw-bold text-uppercase">{{ $label }}</label>
                        <input
                            type="{{ str_starts_with($field, 'peso') || str_starts_with($field, 'diametro') ? 'number' : 'text' }}"
                            id="edit-{{ $field }}" name="{{ $field }}"
                            class="form-control form-control-lg" placeholder="Introduce {{ strtolower($label) }}">
                    </div>
                @endforeach

                <div class="form-group mb-4">
                    <label for="edit-estado" class="form-label fw-bold text-uppercase">Estado</label>
                    <select id="edit-estado" name="estado" class="form-control form-control-lg" required>
                        <option value="">-- Selecciona un estado --</option>
                        <option value="activa">Activa</option>
                        <option value="en mantenimiento">En mantenimiento</option>
                        <option value="inactiva">Inactiva</option>
                    </select>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Actualizar Máquina</button>
                    <button type="button" id="closeModal" class="btn btn-secondary mt-2">Cancelar</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Scripts QR y Alpine -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/imprimirQr.js') }}"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        const usuarios = @json($usuarios);
        const csrfToken = '{{ csrf_token() }}';
        window.rutas = {
            guardarSesion: '{{ route('maquinas.sesion.guardar') }}',
            base: '{{ url('/') }}'
        };
    </script>
    <script src="{{ asset('js/maquinaJS/seleccionarCompa.js') }}" defer></script>
    <script>
        // Asignar evento a todos los botones de edición
        document.querySelectorAll('.open-edit-modal').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                console.log('Clic en editar máquina ID:', id); // Para depurar

                // Obtener los datos de la máquina por AJAX
                fetch(`/maquinas/${id}/json`)

                    .then(res => res.json())
                    .then(data => {
                        // Rellenar los campos del modal
                        document.getElementById('edit-id').value = data.id;
                        ['codigo', 'nombre', 'diametro_min', 'diametro_max', 'peso_min', 'peso_max',
                            'estado'
                        ].forEach(field => {
                            const el = document.getElementById(`edit-${field}`);
                            if (el) el.value = data[field] ?? '';
                        });

                        // Mostrar el modal (asegura visibilidad con estilo)
                        const modal = document.getElementById('editModal');
                        modal.classList.remove('hidden');
                        modal.style.display = 'flex';
                    })
                    .catch(err => {
                        console.error('Error al cargar datos de la máquina:', err);
                        alert('No se pudieron cargar los datos de la máquina.');
                    });
            });
        });

        // Cerrar modal al hacer clic en cancelar
        document.getElementById('closeModal').addEventListener('click', () => {
            const modal = document.getElementById('editModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        });

        // Enviar formulario de edición con AJAX
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
                        document.getElementById('editModal').classList.add('hidden');
                        document.getElementById('editModal').style.display = 'none';
                        location.reload(); // Recargar para mostrar cambios
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
    </script>

</x-app-layout>
