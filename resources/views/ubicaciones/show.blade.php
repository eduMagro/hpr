<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ubicaciones.index') }}" class="text-gray-600">
                {{ __('Ubicaciones') }}
            </a>
        </h2>
    </x-slot>

    <div class="container mx-auto mt-5">
        <h2 class="text-2xl font-bold text-center mb-4">Ubicación: {{ $ubicacion->ubicacion }}</h2>
        <p class="text-gray-700 text-center mb-6">{{ $ubicacion->descripcion }}</p>
        
        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-3">Productos en esta Ubicación</h3>
            
            @if ($ubicacion->productos->isEmpty())
                <p class="text-gray-500 italic">No hay productos en esta ubicación.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($ubicacion->productos as $producto)
                        <div class="bg-gray-100 p-4 rounded-lg shadow">
                            <p class="text-gray-700 font-semibold">ID: {{ $producto->id }}</p>
                            <p class="text-gray-700">Ø {{ $producto->diametro }} mm</p>
                            <p class="text-gray-600">Peso: {{ $producto->peso }} kg</p>
                            <a href="{{ route('productos.show', $producto->id) }}" class="mt-2 block bg-blue-500 text-white px-3 py-1 rounded-md text-center hover:bg-blue-600 transition">Ver Producto</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    
        <div class="mt-6 text-center">
            <a href="{{ route('ubicaciones.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition">Volver</a>
        </div>
    </div>
</x-app-layout>
