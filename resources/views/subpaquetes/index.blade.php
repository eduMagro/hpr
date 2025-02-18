<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('paquetes.index') }}" class="text-blue-600">
                {{ __('Planillas') }}
            </a>
        <span class="mx-2">/</span>
        <a href="{{ route('paquetes.index') }}" class="text-blue-600">
            {{ __('Paquetes') }}
        </a>
        <span class="mx-2">/</span>
        <a href="{{ route('etiquetas.index') }}" class="text-blue-600">
            {{ __('Etiquetas') }}
        </a>
        <span class="mx-2">/</span>
        <a href="{{ route('elementos.index') }}" class="text-blue-600">
            {{ __('Elementos') }}
        </a>
        <span class="mx-2">/</span>
      
            {{ __('Lista de Subpaquetes') }}
     
    </h2>
    </x-slot>

    <div class="w-full px-6 py-4">

        <!-- Encabezado -->
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-700">Listado de Subpaquetes</h3>
        </div>

        <!-- FORMULARIO DE BÚSQUEDA -->
        <form method="GET" action="{{ route('subpaquetes.index') }}" class="mb-4 grid grid-cols-4 gap-4">
            <input type="text" name="nombre" class="border p-2 rounded-md" placeholder="Nombre"
                value="{{ request('nombre') }}">
            <input type="text" name="planilla_id" class="border p-2 rounded-md" placeholder="Planilla ID"
                value="{{ request('planilla_id') }}">
            <input type="text" name="paquete_id" class="border p-2 rounded-md" placeholder="Paquete ID"
                value="{{ request('paquete_id') }}">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                Buscar
            </button>
        </form>

        <!-- TABLA DE SUBPAQUETES -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1200px] border border-gray-300 rounded-lg">
                <thead class="bg-gray-800 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-3 border">ID</th>
                        <th class="px-4 py-3 border">Nombre</th>
                        <th class="px-4 py-3 border">Peso (kg)</th>
                        <th class="px-4 py-3 border">Dimensiones</th>
                        <th class="px-4 py-3 border">Cantidad</th>
                        <th class="px-4 py-3 border">Descripción</th>
                        <th class="px-4 py-3 border">Planilla</th>
                        <th class="px-4 py-3 border">Paquete</th>
                        <th class="px-4 py-3 border">Elemento</th>
                        <th class="px-4 py-3 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($subpaquetes as $subpaquete)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                            <td class="px-4 py-3 text-center border">{{ $subpaquete->id }}</td>
                            <td class="px-4 py-3 text-center border">{{ $subpaquete->nombre }}</td>
                            <td class="px-4 py-3 text-center border">{{ number_format($subpaquete->peso, 2) }}</td>
                            <td class="px-4 py-3 text-center border">{{ $subpaquete->dimensiones ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $subpaquete->cantidad ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $subpaquete->descripcion ?? 'Sin descripción' }}</td>
                            <td class="px-4 py-3 text-center border">
                                @if ($subpaquete->planilla)
                                    <a href="{{ route('planillas.index', ['id' => $subpaquete->planilla->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $subpaquete->planilla->codigo_limpio }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center border">
                                @if ($subpaquete->paquete)
                                    <a href="{{ route('paquetes.index', ['id' => $subpaquete->paquete->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $subpaquete->paquete->id }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center border">
                                @if ($subpaquete->elemento)
                                    <a href="{{ route('elementos.index', ['id' => $subpaquete->elemento->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        ID {{ $subpaquete->elemento->id }} - FIGURA {{ $subpaquete->elemento->figura }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center border flex space-x-2">
                                <a href="{{ route('subpaquetes.show', $subpaquete->id) }}"
                                    class="text-blue-500 hover:underline">Ver</a>
                                <a href="{{ route('subpaquetes.edit', $subpaquete->id) }}"
                                    class="text-yellow-500 hover:underline">Editar</a>
                                <form action="{{ route('subpaquetes.destroy', $subpaquete->id) }}" method="POST"
                                    class="inline"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este subpaquete?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-gray-500">No hay subpaquetes registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">
            {{ $subpaquetes->links() }}
        </div>
    </div>
</x-app-layout>
