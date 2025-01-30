<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">

            {{ __('Materia Prima Almacenada') }}

        </h2>
    </x-slot>
    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Mostrar mensajes de éxito o error -->
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    {{-- mostrar mensaje de exito --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    {{-- mision abortada --}}
    @if (session('abort'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Acceso denegado',
                text: "{{ session('abort') }}",
            });
        </script>
    @endif
    <div class="container mx-auto px-4 py-6">
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

        <!-- Tarjetas de productos -->
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
                    <p>

                        <button
                            onclick="generateAndPrintQR('{{ $producto->id }}', '{{ $producto->fabricante }}', 'MATERIA PRIMA')"
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            QR
                        </button>

                    </p>
                    <div id="qrCanvas" style="display:none;"></div>

                    <hr class="m-2 border-gray-300">

                    <!-- Detalles de Ubicación o Máquina -->
                    @if (isset($producto->ubicacion->nombre))
                        <p class="font-bold text-lg text-gray-800 break-words">
                            {{ $producto->ubicacion->nombre }}</p>
                    @elseif (isset($producto->maquina->nombre))
                        <p class="font-bold text-lg text-gray-800 break-words">{{ $producto->maquina->nombre }}
                        </p>
                    @else
                        <p class="font-bold text-lg text-gray-800 break-words">No está ubicada</p>
                    @endif
                    <p class="text-gray-600 mt-2">{{ $producto->created_at->format('d/m/Y H:i') }}</p>

                    <hr class="my-2 border-gray-300">
                    <div class="mt-2 flex justify-between">
                        {{-- sweet alert para eliminar --}}
                        <x-boton-eliminar :action="route('productos.destroy', $producto->id)" />
                        <!-- Enlace para editar -->
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

        <!-- Paginación -->
        @if (isset($registrosProductos) && $registrosProductos instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $registrosProductos->appends(request()->except('page'))->links() }}
        @endif
    </div>
    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/imprimirQr.js') }}"></script>
</x-app-layout>
