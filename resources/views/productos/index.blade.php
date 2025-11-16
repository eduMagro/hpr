<x-app-layout>
    <x-slot name="title">Materiales - {{ config('app.name') }}</x-slot>

    @include('components.maquinas.modales.grua.modales-grua', [
        'maquinasDisponibles' => $maquinasDisponibles,
    ])

    <div class="w-full px-6 py-4">
        <!-- Botones superiores -->
        <div class="mb-6 flex flex-wrap justify-center gap-3">
            @if (auth()->check() && auth()->id() === 1)
                <x-tabla.boton-azul :href="route('entradas.create')">
                    ➕ Crear Nueva Entrada
                </x-tabla.boton-azul>
            @endif

            <button onclick="abrirModal()"
                class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-2.5 px-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 hidden md:inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" />
                </svg>
                Generar Códigos QR
            </button>
        </div>

        <!-- 🖥️ Tabla solo en pantallas medianas o grandes -->
        <div class="hidden md:block">
            <div id="modalGenerarCodigos"
                class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                    <h2 class="text-xl font-semibold mb-4">Generar y exportar códigos</h2>

                    <form action="{{ route('productos.generar.crearExportar') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label for="cantidad" class="block text-sm font-medium text-gray-700">Cantidad a
                                generar</label>
                            <input type="number" id="cantidad" name="cantidad" value="10" min="1"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="flex justify-end pt-2 space-x-2">
                            <p class="text-xs text-gray-500 mt-2">
                                ⚠️ Esta exportación es importante. Exporta solo si vas a imprimir etiquetas QR para
                                evitar duplicados.
                            </p>

                            <button type="button" onclick="cerrarModal()"
                                class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Generar
                            </button>
                        </div>
                    </form>

                </div>
            </div>
            <script>
                function abrirModal() {
                    const modal = document.getElementById('modalGenerarCodigos');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function cerrarModal() {
                    const modal = document.getElementById('modalGenerarCodigos');
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                }

                // Opcional: cerrar con tecla ESC
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        cerrarModal();
                    }
                });

                // Opcional: cerrar si se hace clic fuera del contenido
                window.addEventListener('click', function(event) {
                    const modal = document.getElementById('modalGenerarCodigos');
                    if (event.target === modal) {
                        cerrarModal();
                    }
                });
            </script>

            <!-- Catálogo de Productos Base -->
            <div x-data="{ open: false }" class="mb-6">
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg shadow-md border border-gray-200 p-4">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-600 p-2 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">Catálogo de Productos Base</h2>
                                <p class="text-xs text-gray-600">Listado de productos disponibles</p>
                            </div>
                        </div>
                        <button @click="open = !open"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 shadow-md hover:shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="transition: transform 0.2s;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            <span x-show="!open">Mostrar</span>
                            <span x-show="open">Ocultar</span>
                        </button>
                    </div>
                </div>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="mt-3 overflow-x-auto bg-white shadow-lg rounded-lg border border-gray-200">
                    <table class="w-full min-w-[600px] text-sm">
                        <thead class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                            <tr>
                                <th class="px-4 py-3 border-b border-blue-400 font-semibold">ID</th>
                                <th class="px-4 py-3 border-b border-blue-400 font-semibold">Tipo</th>
                                <th class="px-4 py-3 border-b border-blue-400 font-semibold">Diámetro</th>
                                <th class="px-4 py-3 border-b border-blue-400 font-semibold">Longitud</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($productosBase as $base)
                                <tr class="border-b hover:bg-blue-50 transition-colors">
                                    <td class="px-4 py-3 text-center font-medium text-gray-700">{{ $base->id }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold">
                                            {{ ucfirst($base->tipo) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-medium text-gray-700">{{ $base->diametro }} mm</td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ $base->longitud ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-8">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <span class="text-gray-500 font-medium">No hay productos base registrados</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @livewire('productos-table')
        </div>

        <!-- 📱 Tarjetas solo en pantallas pequeñas -->
        <div class="block md:hidden">
            <!-- Buscador por código -->
            {{-- <div class="mb-4">
                <form method="GET" action="{{ route('productos.index') }}"
            class="flex flex-col sm:flex-row gap-2 items-center">
            <input type="text" name="codigo" placeholder="Buscar por código..."
                value="{{ request('codigo') }}"
                class="w-full sm:w-64 px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-semibold">
                Buscar
            </button>
            @if (request('codigo'))
            <a href="{{ route('productos.index') }}"
                class="text-sm text-gray-600 underline hover:text-gray-800">Limpiar</a> 
            @endif
            </form>
        </div> --}}
            <!-- Buscador con filtros personalizados -->
            <div class="mb-4">
                <form method="GET" action="{{ route('productos.index') }}"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 items-end">

                    <x-tabla.input name="codigo" label="Código" placeholder="Buscar por QR..." autofocus
                        autocomplete="off" />

                    <select id="producto_base_id" name="producto_base_id"
                        class="w-full px-2 py-1 border border-gray-300 rounded text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="" disabled selected>Seleccione un producto base</option>
                        <option value="">NINGUNO</option>
                        @foreach ($productosBase as $producto)
                            <option value="{{ $producto->id }}"
                                {{ old('producto_base_id') == $producto->id ? 'selected' : '' }}>
                                {{ strtoupper($producto->tipo) }} |
                                Ø{{ $producto->diametro }}{{ $producto->longitud ? ' | ' . $producto->longitud . ' m' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <x-tabla.botones-filtro ruta="productos.index" />
                </form>
            </div>

            <!-- Modo Tarjetas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($registrosProductos as $producto)
                    <div class="bg-white shadow-md rounded-lg p-4">
                        <h3 class="font-bold text-lg text-gray-700">ID: {{ $producto->id }}</h3>
                        <h3 class="font-bold text-lg text-gray-700">Código: {{ $producto->codigo }}</h3>
                        <p><strong>Fabricante:</strong> {{ $producto->fabricante->nombre ?? '—' }}</p>
                        <p>
                            <strong>Características:</strong>
                            {{ strtoupper($producto->productoBase->tipo ?? '—') }}
                            |
                            Ø{{ $producto->productoBase->diametro ?? '—' }}
                            {{ $producto->productoBase->longitud ? '| ' . $producto->productoBase->longitud . ' m' : '' }}
                        </p>

                        <p><strong>Nº Colada:</strong> {{ $producto->n_colada }}</p>
                        <p><strong>Nº Paquete:</strong> {{ $producto->n_paquete }}</p>
                        <p><strong>Peso Inicial:</strong> {{ $producto->peso_inicial }} kg</p>
                        <p><strong>Peso Stock:</strong> {{ $producto->peso_stock }} kg</p>
                        <p><strong>Estado:</strong> {{ $producto->estado }}</p>
                        <hr class="m-2 border-gray-300">

                        @if (isset($producto->ubicacion->nombre))
                            <p class="font-bold text-lg text-gray-800 break-words">{{ $producto->ubicacion->nombre }}
                            </p>
                        @elseif (isset($producto->maquina->nombre))
                            <p class="font-bold text-lg text-gray-800 break-words">{{ $producto->maquina->nombre }}
                            </p>
                        @else
                            <p class="font-bold text-lg text-gray-800 break-words">No está ubicada</p>
                        @endif

                        <p class="text-gray-600 mt-2">{{ $producto->created_at->format('d/m/Y H:i') }}</p>

                        <hr class="my-2 border-gray-300">
                        <td class="px-2 py-3 text-center border">
                            @php
                                $usuario = auth()->user();
                                $esOficina = $usuario->rol === 'oficina';
                                $esGruista = $usuario->rol !== 'oficina' && $usuario->maquina?->tipo === 'grua';
                            @endphp

                            @if ($esOficina || $esGruista)
                                <div class="flex flex-wrap gap-2 mt-4 w-full">
                                    <a href="{{ route('productos.show', $producto->id) }}"
                                        class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                        Ver
                                    </a>
                                    <a href="{{ route('productos.edit', $producto->id) }}"
                                        class="flex-1 bg-blue-400 hover:bg-blue-500 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                        Editar
                                    </a>
                                    <button onclick="abrirModalMovimientoLibre('{{ $producto->codigo }}')"
                                        class="flex-1 bg-green-500 hover:bg-green-600 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                        Mover
                                    </button>

                                    <a href="{{ route('productos.editarConsumir', $producto->id) }}"
                                        data-consumir="{{ route('productos.editarConsumir', $producto->id) }}"
                                        class="btn-consumir flex-1 bg-red-500 hover:bg-red-600 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                        Consumir
                                    </a>

                                    <form action="{{ route('productos.destroy', $producto->id) }}" method="POST"
                                        class="form-eliminar flex-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn-eliminar w-full bg-gray-500 hover:bg-gray-600 text-white text-sm font-semibold py-2 px-2 rounded shadow">
                                            Eliminar
                                        </button>
                                    </form>

                                </div>
                            @endif
                        </td>

                    </div>
                @empty
                    <div class="col-span-3 text-center py-4">No hay productos disponibles.</div>
                @endforelse
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Delegación de eventos para botones "Consumir"
                document.body.addEventListener('click', async (e) => {
                    const btn = e.target.closest('.btn-consumir');
                    if (!btn) return;

                    e.preventDefault();

                    const url = btn.dataset.consumir || btn.getAttribute('href');

                    const {
                        value: opcion
                    } = await Swal.fire({
                        title: '¿Cómo deseas consumir el material?',
                        text: 'Selecciona si quieres consumirlo completo o solo unos kilos.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Consumir completo',
                        cancelButtonText: 'Cancelar',
                        showDenyButton: true,
                        denyButtonText: 'Consumir por kilos'
                    });

                    if (opcion) {
                        // ✅ Consumir completo
                        if (opcion === true) {
                            window.location.href = url + '?modo=total';
                        }
                    } else if (opcion === false) {
                        // ✅ Consumir por kilos
                        const {
                            value: kilos
                        } = await Swal.fire({
                            title: 'Introduce los kilos a consumir',
                            input: 'number',
                            inputAttributes: {
                                min: 1,
                                step: 0.01
                            },
                            inputPlaceholder: 'Ejemplo: 250',
                            showCancelButton: true,
                            confirmButtonText: 'Consumir',
                            cancelButtonText: 'Cancelar',
                            preConfirm: (value) => {
                                if (!value || value <= 0) {
                                    Swal.showValidationMessage(
                                        'Debes indicar un número válido mayor que 0');
                                    return false;
                                }
                                return value;
                            }
                        });

                        if (kilos) {
                            // Redirigimos con cantidad en la URL (ejemplo GET)
                            window.location.href = url + '?modo=parcial&kgs=' + kilos;
                        }
                    }
                });

                // Confirmar eliminación
                document.querySelectorAll('.form-eliminar').forEach(form => {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: "Esta acción eliminará la materia prima de forma permanente.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#6c757d',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                });
            });
        </script>

</x-app-layout>
