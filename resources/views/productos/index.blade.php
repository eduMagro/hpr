<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">

            {{ __('Materia Prima Almacenada') }}

        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <!-- FORMULARIO DE BÚSQUEDA AVANZADA -->
        <button class="btn btn-secondary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosBusqueda">
            🔍 Filtros Avanzados
        </button>

        <div id="filtrosBusqueda" class="collapse">
            <form method="GET" action="{{ route('productos.index') }}" class="card card-body shadow-sm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="id" class="form-control" placeholder="ID"
                            value="{{ request('id') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="fabricante" class="form-control">
                            <option value="">Seleccione Fabricante</option>
                            <option value="MEGASA" {{ request('fabricante') == 'MEGASA' ? 'selected' : '' }}>MEGASA
                            </option>
                            <option value="GETAFE" {{ request('fabricante') == 'GETAFE' ? 'selected' : '' }}>GETAFE
                            </option>
                            <option value="NERVADUCTIL" {{ request('fabricante') == 'NERVADUCTIL' ? 'selected' : '' }}>
                                NERVADUCTIL</option>
                            <option value="Siderurgica Sevillana"
                                {{ request('fabricante') == 'Siderurgica Sevillana' ? 'selected' : '' }}>Siderúrgica
                                Sevillana</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="tipo" class="form-control">
                            <option value="">Seleccione Tipo</option>
                            <option value="barras" {{ request('tipo') == 'barras' ? 'selected' : '' }}>Barras</option>
                            <option value="encarretado" {{ request('tipo') == 'encarretado' ? 'selected' : '' }}>
                                Encarretado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="diametro" class="form-control">
                            <option value="">Seleccione Diámetro</option>
                            <option value="5" {{ request('diametro') == '5' ? 'selected' : '' }}>5 mm</option>
                            <option value="8" {{ request('diametro') == '8' ? 'selected' : '' }}>8 mm</option>
                            <option value="10" {{ request('diametro') == '10' ? 'selected' : '' }}>10 mm</option>
                            <option value="12" {{ request('diametro') == '12' ? 'selected' : '' }}>12 mm</option>
                            <option value="16" {{ request('diametro') == '16' ? 'selected' : '' }}>16 mm</option>
                            <option value="20" {{ request('diametro') == '20' ? 'selected' : '' }}>20 mm</option>
                            <option value="25" {{ request('diametro') == '25' ? 'selected' : '' }}>25 mm</option>
                            <option value="32" {{ request('diametro') == '32' ? 'selected' : '' }}>32 mm</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="longitud" class="form-control" placeholder="Longitud"
                            value="{{ request('longitud') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="n_colada" class="form-control" placeholder="Número de Colada"
                            value="{{ request('n_colada') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="n_paquete" class="form-control" placeholder="Número de Paquete"
                            value="{{ request('n_paquete') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="estado" class="form-control" placeholder="Estado"
                            value="{{ request('estado') }}">
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="{{ route('productos.index') }}" class="btn btn-warning">
                            <i class="fas fa-undo"></i> Resetear Filtros
                        </a>
                    </div>
                </div>
            </form>
        </div>

        @if (Auth::user()->rol === 'oficina')
            <!-- Modo Tabla -->
            <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                    <thead class="bg-blue-500 text-white">
                        <tr class="text-left text-sm uppercase">
                            <th class="px-4 py-3 border">ID Materia Prima</th>
                            <th class="px-4 py-3 border">Fabricante</th>
                            <th class="px-4 py-3 border">Nombre</th>
                            <th class="px-4 py-3 border">Tipo</th>
                            <th class="px-4 py-3 border">Diámetro</th>
                            <th class="px-4 py-3 border">Longitud</th>
                            <th class="px-4 py-3 border">Nº Colada</th>
                            <th class="px-4 py-3 border">Nº Paquete</th>
                            <th class="px-4 py-3 border">Peso Inicial</th>
                            <th class="px-4 py-3 border">Peso Stock</th>
                            <th class="px-4 py-3 border">Estado</th>
                            <th class="px-4 py-3 border">Ubicación</th>
                            <th class="px-4 py-3 border text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse($registrosProductos as $producto)
                            <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer">
                                <td class="px-4 py-3 text-center border">{{ $producto->id }}</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->fabricante }}</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->nombre }}</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->tipo }}</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->diametro }}</td>
                                <td class="px-4 py-3 text-center border">{{ $producto->longitud ?? 'N/A' }}</td>
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
                                    <a href="{{ route('productos.show', $producto->id) }}"
                                        class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>
                                    <a href="{{ route('productos.edit', $producto->id) }}"
                                        class="text-blue-500 hover:text-blue-700 text-sm ml-2">Editar</a>
                                    <a href="{{ route('movimientos.create', ['producto_id' => $producto->id]) }}"
                                        class="text-green-500 hover:text-green-700 text-sm ml-2">Mover</a>
                                    <x-boton-eliminar :action="route('productos.destroy', $producto->id)" class="ml-2" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4 text-gray-500">No hay productos
                                    disponibles.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <!-- Modo Tarjetas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($registrosProductos as $producto)
                    <div class="bg-white shadow-md rounded-lg p-4">
                        <h3 class="font-bold text-lg text-gray-700">ID Materia Prima: {{ $producto->id }}</h3>
                        <p><strong>Fabricante:</strong> {{ $producto->fabricante }}</p>
                        <p><strong>Nombre:</strong> {{ $producto->nombre }}</p>
                        <p><strong>Tipo:</strong> {{ $producto->tipo }}</p>
                        <p><strong>Diámetro:</strong> {{ $producto->diametro }}</p>
                        <p><strong>Longitud:</strong> {{ $producto->longitud ?? 'N/A' }}</p>
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
                        <div class="mt-2 flex flex-col space-y-2 items-start">
                            <x-boton-eliminar :action="route('productos.destroy', $producto->id)" />
                            <a href="{{ route('productos.edit', $producto->id) }}"
                                class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                            <a href="{{ route('movimientos.create', ['producto_id' => $producto->id]) }}"
                                class="text-green-500 hover:text-green-700 text-sm">Mover</a>
                            <a href="{{ route('productos.show', $producto->id) }}"
                                class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>
                        </div>
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
</x-app-layout>
