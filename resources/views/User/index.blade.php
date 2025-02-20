<x-app-layout>
    <x-slot name="title">Usuarios - {{ config('app.name') }}</x-slot>
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
         <!-- Tabla de usuarios -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="py-3 px-2 border text-center">ID</th>
                        <th class="py-3 px-2 border text-center">Nombre</th>
                        <th class="py-3 px-2 border text-center">Email</th>
                        <th class="py-3 px-2 border text-center">Categoría</th>
						 <th class="py-3 px-2 border text-center">Turno</th>
                        <th class="py-3 px-2 border text-center">Estado</th>
                        <th class="py-3 px-2 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($registrosUsuarios as $user)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                            <td class="px-2 py-3 text-center border">{{ $user->id }}</td>
                            <td class="px-2 py-3 text-center border">{{ $user->name }}</td>
                            <td class="px-2 py-3 text-center border">{{ $user->email }}</td>
                            <td class="px-2 py-3 text-center border">{{ $user->categoria }}</td>
                            <td class="px-2 py-3 text-center border">
                                @if ($user->turno)
                                    <span class="px-2 py-1 rounded text-white"
                                          style="background-color: {{ $user->turno == 'mañana' ? '#FFD700' : ($user->turno == 'tarde' ? '#FF8C00' : ($user->turno == 'noche' ? '#1E90FF' : '#32CD32')) }}">
                                        {{ ucfirst($user->turno) }}
                                    </span>
                                @else
                                    <span class="text-gray-500">Sin turno</span>
                                @endif
                            </td>
                            
                            <td class="px-2 py-3 text-center border">
                                @if ($user->isOnline())
                                    <span class="text-green-600">En línea</span>
                                @else
                                    <span class="text-gray-500">Desconectado</span>
                                @endif
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <a href="{{ route('users.edit', $user->id) }}" class="text-blue-500 hover:underline">Editar</a>
                                |
                                <a href="{{ route('users.show', $user->id) }}" class="text-blue-500 hover:underline">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-gray-500">No hay usuarios disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-center">
            {{ $registrosUsuarios->links() }}
        </div>
    </div>

</x-app-layout>
