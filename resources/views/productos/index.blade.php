<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Productos Almacenados') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <!-- FORMULARIO DE BUSQUEDA -->
        <form method="GET" action="{{ route('productos.index') }}" class="form-inline mt-3 mb-3">
            <input type="text" name="qr" class="form-control mb-3" placeholder="Buscar por código QR"
                value="{{ request('qr') }}">
            <button type="submit" class="btn btn-info ml-2">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>

        <!-- Tarjetas de productos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($registrosProductos as $producto)
                <div class="bg-white shadow-md rounded-lg p-4">
                    <!-- Lista desordenada con los detalles del producto -->
                    <ul class="list-disc pl-5">
                        <li class="font-bold text-lg text-gray-800 break-words">{{ $producto->qr ?? NULL}}</li>
                        @if (isset($producto->ubicacion->descripcion))
                        <li class="font-bold text-lg text-gray-800 break-words">
                            {{ $producto->ubicacion->descripcion }}</li>
                    @elseif (isset($producto->maquina->nombre))
                        <li class="font-bold text-lg text-gray-800 break-words">{{ $producto->maquina->nombre }}
                        </li>
                    @else
                        <li class="font-bold text-lg text-gray-800 break-words">No está ubicada</li>
                    @endif
                        <li class="text-gray-600 mt-2">{{ $producto->created_at }}</li>
                    </ul>
                    <hr style="border: 1px solid #ccc; margin: 10px 0;">
                    <div class="mt-4 flex justify-between">
             
                        <form action="{{ route('productos.destroy', $producto->id) }}" method="POST"
                            style="display:inline;"
                            onsubmit="return confirm('¿Estás seguro de querer eliminar este producto?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500">Eliminar</button>
                        </form>
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
