<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">

            {{ __('Materia Prima Almacenada') }}

        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">

        @if (Auth::user()->rol === 'oficina')

            <button onclick="abrirModal()"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow">
                Generar códigos
            </button>
            <div id="modalGenerarCodigos"
                class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                    <h2 class="text-xl font-semibold mb-4">Generar y exportar códigos</h2>

                    <form action="{{ route('productos.generar.exportar') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label for="cantidad" class="block text-sm font-medium text-gray-700">Cantidad a
                                generar</label>
                            <input type="number" id="cantidad" name="cantidad" value="10" min="1"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="flex justify-end pt-2 space-x-2">
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
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-lg font-semibold text-gray-800">Catálogo de Productos Base</h2>
                    <button @click="open = !open"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                        <span x-show="!open">Mostrar</span><span x-show="open">Ocultar</span>
                    </button>
                </div>
                <div x-show="open" x-transition class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="w-full min-w-[600px] border border-gray-300 text-sm">
                        <thead class="bg-blue-200 text-gray-700">
                            <tr>
                                <th class="px-3 py-2 border">ID</th>
                                <th class="px-3 py-2 border">Tipo</th>
                                <th class="px-3 py-2 border">Diámetro</th>
                                <th class="px-3 py-2 border">Longitud</th>

                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($productosBase as $base)
                                <tr class="border-b odd:bg-gray-100 even:bg-gray-50">
                                    <td class="px-3 py-2 border text-center">{{ $base->id }}</td>
                                    <td class="px-3 py-2 border text-center">{{ ucfirst($base->tipo) }}</td>
                                    <td class="px-3 py-2 border text-center">{{ $base->diametro }} mm</td>
                                    <td class="px-3 py-2 border text-center">{{ $base->longitud ?? '—' }}</td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-gray-500">No hay productos base
                                        registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @php
                $filtrosActivos = [];

                if (request('buscar')) {
                    $filtrosActivos[] = 'contiene <strong>“' . request('buscar') . '”</strong>';
                }
                if (request('codigo')) {
                    $filtrosActivos[] = 'Código: <strong>' . request('codigo') . '</strong>';
                }
                if (request('proveedor')) {
                    $filtrosActivos[] = 'Proveedor: <strong>' . request('proveedor') . '</strong>';
                }
                if (request('tipo')) {
                    $filtrosActivos[] = 'Tipo: <strong>' . request('tipo') . '</strong>';
                }
                if (request('diametro')) {
                    $filtrosActivos[] = 'Diámetro: <strong>' . request('diametro') . '</strong>';
                }
                if (request('longitud')) {
                    $filtrosActivos[] = 'Longitud: <strong>' . request('longitud') . '</strong>';
                }
                if (request('n_colada')) {
                    $filtrosActivos[] = 'Nº Colada: <strong>' . request('n_colada') . '</strong>';
                }
                if (request('n_paquete')) {
                    $filtrosActivos[] = 'Nº Paquete: <strong>' . request('n_paquete') . '</strong>';
                }
                if (request('estado')) {
                    $estados = [
                        'disponible' => 'Disponible',
                        'reservado' => 'Reservado',
                        'consumido' => 'Consumido',
                    ];
                    $filtrosActivos[] =
                        'Estado: <strong>' . ($estados[request('estado')] ?? request('estado')) . '</strong>';
                }
                if (request('ubicacion')) {
                    $filtrosActivos[] = 'Ubicación: <strong>' . request('ubicacion') . '</strong>';
                }

                if (request('sort')) {
                    $sorts = [
                        'id' => 'ID',
                        'proveedor' => 'Proveedor',
                        'tipo' => 'Tipo',
                        'diametro' => 'Diámetro',
                        'longitud' => 'Longitud',
                        'n_colada' => 'Nº Colada',
                        'n_paquete' => 'Nº Paquete',
                        'peso_inicial' => 'Peso Inicial',
                        'peso_stock' => 'Peso Stock',
                        'estado' => 'Estado',
                        'ubicacion' => 'Ubicación',
                    ];
                    $orden = request('order') == 'desc' ? 'descendente' : 'ascendente';
                    $filtrosActivos[] =
                        'Ordenado por <strong>' .
                        ($sorts[request('sort')] ?? request('sort')) .
                        "</strong> en orden <strong>$orden</strong>";
                }

                if (request('per_page')) {
                    $filtrosActivos[] = 'Mostrando <strong>' . request('per_page') . '</strong> registros por página';
                }
            @endphp

            @if (count($filtrosActivos))
                <div class="alert alert-info text-sm mt-2 mb-4 shadow-sm">
                    <strong>Filtros aplicados:</strong> {!! implode(', ', $filtrosActivos) !!}
                </div>
            @endif

            @php
                function ordenarColumna($columna, $titulo)
                {
                    $currentSort = request('sort_by');
                    $currentOrder = request('order');
                    $isSorted = $currentSort === $columna;
                    $nextOrder = $isSorted && $currentOrder === 'asc' ? 'desc' : 'asc';
                    $icon = $isSorted
                        ? ($currentOrder === 'asc'
                            ? 'fas fa-sort-up'
                            : 'fas fa-sort-down')
                        : 'fas fa-sort';

                    $url = request()->fullUrlWithQuery(['sort_by' => $columna, 'order' => $nextOrder]);

                    return '<a href="' .
                        $url .
                        '" class="text-white text-decoration-none">' .
                        $titulo .
                        ' <i class="' .
                        $icon .
                        '"></i></a>';
                }
            @endphp

            <!-- Modo Tabla -->
            <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                    <thead class="bg-blue-500 text-white">
                        <tr class="text-center text-xs uppercase">
                            <th class="p-2 border">{!! ordenarColumna('id', 'ID Materia Prima') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('codigo', 'Código') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('proveedor', 'Proveedor') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('tipo', 'Tipo') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('diametro', 'Diámetro') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('longitud', 'Longitud') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('n_colada', 'Nº Colada') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('n_paquete', 'Nº Paquete') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('peso_inicial', 'Peso Inicial') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('peso_stock', 'Peso Stock') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('estado', 'Estado') !!}</th>
                            <th class="p-2 border">{!! ordenarColumna('ubicacion', 'Ubicación') !!}</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>
                        <tr class="text-center text-xs uppercase">
                            <form method="GET" action="{{ route('productos.index') }}">
                                <th class="p-1 border">
                                    <input type="text" name="id" value="{{ request('id') }}"
                                        class="form-control form-control-sm" />
                                </th>
                                <th class="p-1 border">
                                    <input type="text" name="codigo" value="{{ request('codigo') }}"
                                        class="form-control form-control-sm" />
                                </th>
                                <th class="p-1 border">
                                    <input type="text" name="proveedor" value="{{ request('proveedor') }}"
                                        class="form-control form-control-sm" />
                                </th>
                                <th class="p-1 border">
                                    <input type="text" name="tipo" value="{{ request('tipo') }}"
                                        class="form-control form-control-sm" />
                                </th>
                                <th class="p-1 border">
                                    <input type="text" name="diametro" value="{{ request('diametro') }}"
                                        class="form-control form-control-sm" />
                                </th>
                                <th class="p-1 border">
                                    <input type="text" name="longitud" value="{{ request('longitud') }}"
                                        class="form-control form-control-sm" />
                                </th>
                                <th class="p-1 border">
                                    <input type="text" name="num_colada" value="{{ request('num_colada') }}"
                                        class="form-control form-control-sm" />
                                </th>
                                <th class="p-1 border">
                                    <input type="text" name="num_paquete" value="{{ request('num_paquete') }}"
                                        class="form-control form-control-sm" />
                                </th>
                                <th class="p-1 border"></th> <!-- Peso Inicial: sin filtro por ahora -->
                                <th class="p-1 border"></th> <!-- Peso Stock: sin filtro por ahora -->
                                <th class="p-1 border">
                                    <select name="estado" class="form-control form-control-sm">
                                        <option value="">Todos</option>
                                        <option value="almacenado"
                                            {{ request('estado') == 'almacenado' ? 'selected' : '' }}>Almacenado
                                        </option>
                                        <option value="fabricando"
                                            {{ request('estado') == 'fabricando' ? 'selected' : '' }}>Fabricando
                                        </option>
                                        <option value="consumido"
                                            {{ request('estado') == 'consumido' ? 'selected' : '' }}>Consumido</option>
                                    </select>
                                </th>
                                <th class="p-1 border">
                                    <input type="text" name="ubicacion" value="{{ request('ubicacion') }}"
                                        class="form-control form-control-sm" />
                                </th>
                                <th class="p-1 border text-center">
                                    <button type="submit" class="btn btn-sm btn-info px-2"><i
                                            class="fas fa-search"></i></button>
                                    <a href="{{ route('productos.index') }}" class="btn btn-sm btn-warning px-2"><i
                                            class="fas fa-undo"></i></a>
                                </th>
                            </form>
                        </tr>

                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse($registrosProductos as $producto)
                            <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer">
                                <td class="px-4 py-3 text-center border">{{ $producto->id }}</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->codigo ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->proveedor->nombre ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center border">
                                    {{ ucfirst($producto->productoBase->tipo ?? '—') }}</td>
                                <td class="px-4 py-3 text-center border">
                                    {{ $producto->productoBase->diametro ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center border">
                                    {{ $producto->productoBase->longitud ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center border">{{ $producto->n_colada }}</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->n_paquete }}</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->peso_inicial }} kg</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->peso_stock }} kg</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->estado }}</td>
                                <td class="px-4 py-3 text-center border">
                                    @if (isset($producto->ubicacion->nombre))
                                        {{ $producto->ubicacion->nombre }}
                                    @elseif (isset($producto->maquina->nombre))
                                        {{ $producto->maquina->nombre }}
                                    @else
                                        No está ubicada
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center border">
                                    <div class="flex flex-col space-y-2 items-center">
                                        <a href="{{ route('productos.show', $producto->id) }}"
                                            class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>
                                        <a href="{{ route('productos.edit', $producto->id) }}"
                                            class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                                        <a href="{{ route('movimientos.create', ['producto_id' => $producto->id]) }}"
                                            class="text-green-500 hover:text-green-700 text-sm">Mover</a>
                                        <x-boton-eliminar :action="route('productos.destroy', $producto->id)" />
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4 text-gray-500">No hay productos
                                    con esa descripción.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <!-- Buscador por código -->
            <div class="mb-4">
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
            </div>
            <!-- Modo Tarjetas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($registrosProductos as $producto)
                    <div class="bg-white shadow-md rounded-lg p-4">
                        <h3 class="font-bold text-lg text-gray-700">ID: {{ $producto->id }}</h3>
                        <h3 class="font-bold text-lg text-gray-700">Código: {{ $producto->codigo }}</h3>
                        <p><strong>Proveedor:</strong> {{ $producto->proveedor->nombre ?? '—' }}</p>
                        <p><strong>Tipo:</strong> {{ ucfirst($producto->productoBase->tipo ?? '—') }}</p>
                        <p><strong>Diámetro:</strong> {{ $producto->productoBase->diametro ?? '—' }}</p>
                        <p><strong>Longitud:</strong> {{ $producto->productoBase->longitud ?? '—' }}</p>
                        <p><strong>Nº Colada:</strong> {{ $producto->n_colada }}</p>
                        <p><strong>Nº Paquete:</strong> {{ $producto->n_paquete }}</p>
                        <p><strong>Peso Inicial:</strong> {{ $producto->peso_inicial }} kg</p>
                        <p><strong>Peso Stock:</strong> {{ $producto->peso_stock }} kg</p>
                        <p><strong>Estado:</strong> {{ $producto->estado }}</p>
                        <p><strong>Otros:</strong> {{ $producto->otros ?? 'N/A' }}</p>

                        <button
                            onclick="generateAndPrintQR('{{ $producto->id }}', '{{ $producto->n_paquete }}', 'MATERIA PRIMA')"
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">QR</button>

                        <div id="qrCanvas" style="display:none;"></div>
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
                        <td class="px-4 py-3 text-center border">
                            @php
                                $usuario = auth()->user();
                                $esOficina = $usuario->rol === 'oficina';
                                $esGruista = $usuario->rol !== 'oficina' && $usuario->maquina?->tipo === 'grua';
                            @endphp

                            @if ($esOficina || $esGruista)
                                <div class="flex flex-col space-y-2 items-center">
                                    <a href="{{ route('productos.show', $producto->id) }}"
                                        class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>
                                    <a href="{{ route('productos.edit', $producto->id) }}"
                                        class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                                    <a href="{{ route('movimientos.create', ['producto_id' => $producto->id]) }}"
                                        class="text-green-500 hover:text-green-700 text-sm">Mover</a>
                                    <x-boton-eliminar :action="route('productos.destroy', $producto->id)" />
                                </div>
                            @endif
                        </td>

                    </div>
                @empty
                    <div class="col-span-3 text-center py-4">No hay productos disponibles.</div>
                @endforelse
            </div>
        @endif

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">
            {{ $registrosProductos->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
        </div>
        <!-- SCRIPT PARA IMPRIMIR QR -->

        <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
</x-app-layout>
