<x-app-layout>
    <x-slot name="title">Detalles de {{ $user->name }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        <a href="{{ route('users.index') }}" class="text-blue-600">
            {{ __('Usuarios') }}
        </a>
        <span class="mx-2">/</span>
        
            {{ $user->name }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="mb-4">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Volver a Usuarios</a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold mb-2">Información del Usuario</h3>
            <p><strong>Nombre:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Categoría:</strong> {{ $user->categoria }}</p>
        </div>

        <div class="mt-6 bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold mb-2">Registros de Fichajes</h3>
            <table class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="py-3 px-2 border text-center">ID</th>
                        <th class="py-3 px-2 border text-center">Entrada</th>
                        <th class="py-3 px-2 border text-center">Salida</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($user->registrosFichajes as $fichaje)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                            <td class="px-2 py-3 text-center border">{{ $fichaje->id }}</td>
                            <td class="px-2 py-3 text-center border">{{ $fichaje->entrada }}</td>
                            <td class="px-2 py-3 text-center border">{{ $fichaje->salida }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-gray-500">No hay registros de fichajes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
