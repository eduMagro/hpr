<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Usuarios') }}
        </h2>
        @if (Auth::check() && Auth::user()->role == 'administrador')
            <p class="text-green-600">Usuarios conectados:
                <strong>{{ $usuariosConectados }}</strong>
            </p>
        @endif
    </x-slot>
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
        <!-- Botón para crear un nuevo usuario con estilo Bootstrap -->
        <div class="mb-4">
            <a href="{{ route('register') }}" class="btn btn-primary">
                Registrar Usuario
            </a>
        </div>
        <!-- FORMULARIO DE BUSQUEDA -->
        <form method="GET" action="{{ route('users.index') }}" class="form-inline mt-3 mb-3">
            <input type="text" name="name" class="form-control mb-3" placeholder="Buscar por nombre"
                value="{{ request('name') }}">
            <button type="submit" class="btn btn-info ml-2">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>
        <!-- Grid para tarjetas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse ($registrosUsuarios as $user)
                <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <!-- Indicador de estado en línea -->
                    @if ($user->isOnline())
                        <span class="relative inline-block w-4 h-4 bg-green-500 rounded-full border-2 border-white">
                        </span>
                    @endif

                    <p class="text-gray-500 text-sm">ID: {{ $user->id }}</p>
                    <h2 class="font-semibold text-lg mb-2">{{ $user->name }}</h2>
                    <p class="text-gray-600 mb-2">Email: {{ $user->email }}</p>
                    <p class="text-gray-600 mb-2">Categoría: {{ $user->categoria }}</p>
                    <hr style="border: 1px solid #ccc; margin: 10px 0;">
                    <div class="mt-4 flex justify-between">
                        <!-- Enlace para editar -->
                        <a href="{{ route('users.edit', $user->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                        <!-- Enlace para ver -->
                        <a href="{{ route('users.show', $user->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>

                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-4">No hay usuarios disponibles.</div>
            @endforelse
        </div>
        <!-- Paginación -->
        @if (isset($registrosUsuarios) && $registrosUsuarios instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $registrosUsuarios->appends(request()->except('page'))->links() }}
        @endif

    </div>

</x-app-layout>
