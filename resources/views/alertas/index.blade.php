<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
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
        <!-- Alertas no leídas -->
        @if ($alertasNoLeidas->isNotEmpty())
            <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-800 p-4 rounded-lg shadow">
                <h3 class="text-md font-semibold">📢 Alertas No Leídas</h3>
                <ul class="mt-2">
                    @foreach ($alertasNoLeidas as $alerta)
                        <li class="py-1 border-b last:border-b-0">
                            <span class="font-bold">{{ $alerta->created_at->format('d/m/Y H:i') }}:</span>
                            {{ $alerta->mensaje }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        <!-- Contenedor de la tabla -->
        <div class="w-full overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="w-full min-w-[800px] border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Usuario 1</th>
                        <th class="px-4 py-2">Usuario 2</th>
                        <th class="px-4 py-2">Destinatario</th>
                        <th class="px-4 py-2">Mensaje</th>
                        <th class="px-4 py-2">Fecha</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($alertas as $alerta)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-4 py-2">{{ $alerta->id }}</td>
                            <td class="px-4 py-2">{{ $alerta->usuario1->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $alerta->usuario2->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ ucfirst($alerta->destinatario) }}</td>
                            <td class="px-4 py-2">{{ $alerta->mensaje }}</td>
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
