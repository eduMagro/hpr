<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Productos Almacenados') }}
        </h2>
    </x-slot>
    <div class="container mx-auto px-4 py-6">
        <!-- FORMULARIO DE BUSQUEDA -->
        <form method="GET" action="{{ route('productos.index') }}" class="form-inline mt-3 mb-3">
            <input type="text" name="id" class="form-control mb-3" placeholder="Buscar por QR"
                value="{{ request('id') }}">
            <button type="submit" class="btn btn-info ml-2">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>

        <!-- Tarjetas de productos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($registrosProductos as $producto)
                <div class="bg-white shadow-md rounded-lg p-4">
                    <h3 class="font-bold text-lg text-gray-700">ID Producto: {{ $producto->id }}</h3>
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
                        <button onclick="generateAndPrintQR('{{ $producto->id }}')" class="btn btn-primary">Imprimir
                            QR</button>
                    </p>
                    <div id="qrCanvas{{ $producto->id }}" style="display:none;"></div>

                    <hr class="m-2 border-gray-300">

                    <!-- Detalles de Ubicación o Máquina -->
                    @if (isset($producto->ubicacion->descripcion))
                        <p class="font-bold text-lg text-gray-800 break-words">
                            {{ $producto->ubicacion->descripcion }}</p>
                    @elseif (isset($producto->maquina->nombre))
                        <p class="font-bold text-lg text-gray-800 break-words">{{ $producto->maquina->nombre }}
                        </p>
                    @else
                        <p class="font-bold text-lg text-gray-800 break-words">No está ubicada</p>
                    @endif
                    <p class="text-gray-600 mt-2">{{ $producto->created_at->format('d/m/Y H:i') }}</p>

                    <hr class="my-2 border-gray-300">
                    <div class="mt-2 flex justify-between">
                        <!-- Enlace para editar -->
                        <a href="{{ route('productos.edit', $producto->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                        <div class="mt-2 flex justify-between">
                            <form action="{{ route('productos.destroy', $producto->id) }}" method="POST"
                                onsubmit="return confirm('¿Estás seguro de querer eliminar este producto?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500">Eliminar</button>
                            </form>
                        </div>
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
</x-app-layout>
