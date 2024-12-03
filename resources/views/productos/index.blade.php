<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Productos Almacenados') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">

        <!-- Tabla de productos -->
        <div class="overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="min-w-full table-auto">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border-b">ID</th>
                        <th class="px-4 py-2 border-b">Nombre</th>
                        <th class="px-4 py-2 border-b">Descripción</th>
                        <th class="px-4 py-2 border-b">Precio</th>
                        <th class="px-4 py-2 border-b">Stock</th>
                        <th class="px-4 py-2 border-b">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                        <tr>
                            <td class="px-4 py-2 border-b">{{ $producto->id }}</td>
                            <td class="px-4 py-2 border-b">{{ $producto->nombre }}</td>
                            <td class="px-4 py-2 border-b">{{ $producto->descripcion }}</td>
                            <td class="px-4 py-2 border-b">{{ $producto->precio }}</td>
                            <td class="px-4 py-2 border-b">{{ $producto->stock }}</td>
                            <td class="px-4 py-2 border-b">
                                <a href="{{ route('productos.edit', $producto->id) }}" class="text-blue-500">Editar</a>
                                |
                                <form action="{{ route('productos.destroy', $producto->id) }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">No hay productos disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
