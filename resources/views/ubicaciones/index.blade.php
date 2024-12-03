<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Movimientos de Ubicaciones') }}
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

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="container mx-auto px-4 py-6">
        <!-- Usamos una estructura de tarjetas para mostrar las ubicaciones -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @if (isset($registrosUbicaciones) &&
                    $registrosUbicaciones instanceof \Illuminate\Pagination\LengthAwarePaginator &&
                    $registrosUbicaciones->isNotEmpty())
                @forelse ($registrosUbicaciones as $ubicacion)
                    <div class="bg-white border p-4 shadow-md rounded-lg">
                        <h3 class="font-bold text-xl">{{ $ubicacion->codigo }}</h3>
                        <p><strong>Descripción:</strong> {{ $ubicacion->descripcion }}</p>
                        <p><strong>Código:</strong> {{ $ubicacion->codigo }}</p>

                        <!-- Mostrar los productos que contiene esta ubicación -->
                        <h4 class="mt-4 font-semibold">Productos en esta ubicación:</h4>
                        @if ($ubicacion->productos->isEmpty())
                            <p>No hay productos en esta ubicación.</p>
                        @else
                            <ul class="list-disc pl-6">
                                @foreach ($ubicacion->productos as $producto)
                                    <li>{{ $producto->nombre }} - {{ $producto->descripcion }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-2">
                            <a href="{{ route('ubicaciones.edit', $ubicacion->id) }}" class="text-blue-500">Editar</a> |
                            <form action="{{ route('ubicaciones.destroy', $ubicacion->id) }}" method="POST"
                                style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p>No hay ubicaciones disponibles.</p> <!-- Mensaje si no hay datos -->
                @endforelse
            @endif
            <!-- Paginación -->
            @if (isset($registrosUbicaciones) && $registrosUbicaciones instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $registrosUbicaciones->appends(request()->except('page'))->links() }}
            @endif
        </div>


    </div>
</x-app-layout>
