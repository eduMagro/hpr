<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('alertas.index') }}" class="text-gray-600">
                {{ __('Alertas') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Lista de Alertas') }}
        </h2>
    </x-slot>

    <!-- Mensajes de Error y Éxito -->
    <div class="w-full px-6 py-4">
        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li class="text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="bg-green-500 text-white p-4 rounded-lg mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Contenedor de la tabla -->
        <div class="w-full overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="w-full min-w-[800px] border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Usuario Generador</th>
                        <th class="px-4 py-2">Usuario Receptor</th>
                        <th class="px-4 py-2">Destinatario</th>
                        <th class="px-4 py-2">Mensaje</th>
                        <th class="px-4 py-2">Estado</th>
                        <th class="px-4 py-2">Fecha</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($alertas as $alerta)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-4 py-2">{{ $alerta->id }}</td>
                            <td class="px-4 py-2">{{ $alerta->usuarioGenerador->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $alerta->usuarioReceptor->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ ucfirst($alerta->destinatario) }}</td>
                            <td class="px-4 py-2">{{ $alerta->mensaje }}</td>
                            <td class="px-4 py-2">
                                @if ($alerta->leida)
                                    <span class="text-green-500 font-semibold">Leída</span>
                                @else
                                    <span class="text-red-500 font-semibold">No Leída</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $alerta->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-gray-500">No hay alertas registradas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">
            {{ $alertas->links() }}
        </div>
    </div>
</x-app-layout>
