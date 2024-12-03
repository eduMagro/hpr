<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Movimientos de Entrada') }}
        </h2>
    </x-slot>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
        <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
        <div class="mb-4">
            <a href="{{ route('entradas.create') }}" class="btn btn-primary">
                Crear Nueva Entrada
            </a>
        </div>

        <!-- Usamos una estructura de tarjetas para dispositivos móviles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($entradas as $entrada)
                <div class="bg-white border p-4 shadow-md rounded-lg">
                    <h3 class="font-bold text-xl">{{ $entrada->albaran }}</h3>
                    <p><strong>Productos:</strong> {{ $entrada->descripcion_material }}</p>
                    <p><strong>Ubicación:</strong> {{ $entrada->ubicacion->codigo ?? 'Sin ubicación' }}</p>
                    <p><strong>Usuario:</strong> {{ $entrada->user->name ?? 'Sin usuario' }}</p>
                    <p><strong>Fecha:</strong> {{ $entrada->fecha }}</p>

                    <div class="mt-2">
                        <a href="{{ route('entradas.edit', $entrada->id) }}" class="text-blue-500">Editar</a> |
                        <form action="{{ route('entradas.destroy', $entrada->id) }}" method="POST"
                            style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p>No hay entradas de material disponibles.</p> <!-- Mensaje si no hay datos -->
            @endforelse
        </div>

        <!-- Paginación -->
        <div class="mt-6">
            {{ $entradas->links() }} <!-- Esto agrega los enlaces de paginación -->
        </div>
    </div>
</x-app-layout>
