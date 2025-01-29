<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('planillas.index') }}" class="text-gray-600">
                {{ __('Planillas') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Lista de Paquetes') }}
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

        <!-- Encabezado -->
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-700">Listado de Paquetes</h3>
        </div>

        <!-- Contenedor de la tabla -->
        <div class="w-full overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="w-full min-w-[800px] border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Planilla</th>
                        <th class="px-4 py-2">Ubicación</th>
                        <th class="px-4 py-2">Cantidad de Etiquetas</th>
                        <th class="px-4 py-2">Etiquetas</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($paquetes as $paquete)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-4 py-2">{{ $paquete->id }}</td>
                            <td class="px-4 py-2">{{ $paquete->planilla->codigo_limpio }}</td>
                            <td class="px-4 py-2">{{ $paquete->ubicacion->nombre ?? 'Sin ubicación' }}</td>
                            <td class="px-4 py-2">{{ $paquete->etiquetas->count() }}</td>
                            <td class="px-4 py-2">
                                @if ($paquete->etiquetas->isNotEmpty())
                                    <ul class="list-disc pl-4">
                                        @foreach ($paquete->etiquetas as $etiqueta)
                                            <li>{{ $etiqueta->nombre }} (ID: {{ $etiqueta->id }})</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-gray-500">Sin etiquetas</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 flex space-x-2">
                                <a href="{{ route('paquetes.show', $paquete->id) }}"
                                    class="text-blue-500 hover:underline">Ver</a>
                                <a href="{{ route('paquetes.edit', $paquete->id) }}"
                                    class="text-yellow-500 hover:underline">Editar</a>
                                <form action="{{ route('paquetes.destroy', $paquete->id) }}" method="POST"
                                    class="inline"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este paquete?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">No hay paquetes registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">
            {{ $paquetes->links() }}
        </div>
    </div>
</x-app-layout>
