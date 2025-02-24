<x-app-layout>
    <x-slot name="title">Usuarios - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __(auth()->user()->name) }}
        </h2>
        @if (Auth::check() && Auth::user()->categoria == 'administrador')
            <p class="text-green-600">Usuarios conectados:
                <strong>{{ $usuariosConectados }}</strong>
            </p>
        @endif
    </x-slot>
    @if (Auth::check() && Auth::user()->categoria == 'administrador')
        <div class="container mx-auto px-4 py-6">

            <div class="flex justify-between items-center w-full gap-4 p-4">
                <button onclick="registrarFichaje('entrada')" class="w-full py-2 px-4 bg-green-600 text-white rounded-md">
                    Entrada
                </button>
                <button onclick="registrarFichaje('salida')" class="w-full py-2 px-4 bg-red-600 text-white rounded-md">
                    Salida
                </button>
            </div>

            <div class="mb-4 flex items-center space-x-4">
                <a href="{{ route('register') }}" class="btn btn-primary">
                    Registrar Usuario
                </a>
                <a href="{{ route('vacaciones.index') }}" class="btn btn-primary">
                    Mostrar Vacaciones Globales
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
                            <th class="py-3 px-2 border text-center">Rol</th>
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
                                <td class="px-2 py-3 text-center border">{{ $user->rol }}</td>
                                <td class="px-2 py-3 text-center border">{{ $user->categoria }}</td>
                                <td class="px-2 py-3 text-center border"
                                    style="background-color: {{ $user->turno == 'mañana' ? '#FFD700' : ($user->turno == 'tarde' ? '#FF8C00' : ($user->turno == 'noche' ? '#1E90FF' : ($user->turno == 'flexible' ? '#32CD32' : ''))) }}">
                                    {{ $user->turno ? ucfirst($user->turno) : 'N/A' }}
                                </td>

                                <td class="px-2 py-3 text-center border">
                                    @if ($user->isOnline())
                                        <span class="text-green-600">En línea</span>
                                    @else
                                        <span class="text-gray-500">Desconectado</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-center border">
                                    <a href="{{ route('users.edit', $user->id) }}"
                                        class="text-blue-500 hover:underline">Editar</a>
                                    |
                                    <a href="{{ route('users.show', $user->id) }}"
                                        class="text-blue-500 hover:underline">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-gray-500">No hay usuarios disponibles.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-center">
                {{ $registrosUsuarios->links() }}
            </div>
        @else
            <div class="flex justify-between items-center w-full gap-4 p-4">
                <button onclick="registrarFichaje('entrada')"
                    class="w-full py-2 px-4 bg-green-600 text-white rounded-md">
                    Entrada
                </button>
                <button onclick="registrarFichaje('salida')" class="w-full py-2 px-4 bg-red-600 text-white rounded-md">
                    Salida
                </button>
            </div>

            <div class="container mx-auto px-4 py-6">
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-lg font-semibold mb-2">Información del Usuario</h3>
                    <p><strong>Nombre:</strong> {{ auth()->user()->name }}</p>
                    <p><strong>Correo:</strong> {{ auth()->user()->email }}</p>
                    <p><strong>Puesto:</strong> {{ auth()->user()->rol }}</p>
                    <p><strong>Categoría:</strong> {{ auth()->user()->categoria }}</p>
                    <p><strong>Especialidad:</strong> {{ auth()->user()->especialidad }}</p>
                    <p><strong>Días de vacaciones restantes:</strong> {{ auth()->user()->dias_vacaciones }}</p>
                </div>


            </div>
    @endif
    </div>
    <!-- Variables globales para JavaScript -->
    <script>
        const userId = "{{ auth()->id() }}";
        const fichajeRoute = "{{ route('registros-fichaje.store') }}";
        const csrfToken = "{{ csrf_token() }}";
    </script>

    <!-- Cargar el script externo -->
    <script src="{{ asset('js/usuarios/registrarFichaje.js') }}"></script>
    
</x-app-layout>
