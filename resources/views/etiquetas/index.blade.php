<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('planillas.index') }}" class="text-gray-600">
                {{ __('Planillas') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Lista de Etiquetas') }}
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
        <!-- Formulario de Filtros -->
        <form method="GET" action="{{ route('etiquetas.index') }}" class="mb-4 flex space-x-4">
            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
                <select name="estado" id="estado"
                    class="w-40 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="Completado" {{ request('estado') == 'Completado' ? 'selected' : '' }}>Completado
                    </option>
                    <option value="Fabricando" {{ request('estado') == 'Fabricando' ? 'selected' : '' }}>Fabricando
                    </option>
                    <option value="Montaje" {{ request('estado') == 'Montaje' ? 'selected' : '' }}>Montaje</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                    Filtrar
                </button>
            </div>
        </form>
        <!-- Contenedor de la tabla con ancho completo y scroll horizontal -->
        <div class="w-full overflow-x-auto bg-white shadow-md rounded-lg">

            <table class="w-full min-w-[1200px] border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-2">
                            ID
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('etiquetas.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="id"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">Planilla
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('etiquetas.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="codigo_planilla"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">Usuario 1</th>
                        <th class="px-4 py-2">Usuario 2</th>
                        <th class="px-4 py-2">Paquete</th>
                        <th class="px-4 py-2">Número de Etiqueta</th>
                        <th class="px-4 py-2">Nombre</th>
                        <th class="px-4 py-2">Ubicación</th>
                        <th class="px-4 py-2">M.Prima 1</th>
                        <th class="px-4 py-2">M.Prima 2</th>
                        <th class="px-4 py-2">Peso (kg)</th>
                        <th class="px-4 py-2">Fecha Inicio</th>
                        <th class="px-4 py-2">Fecha Finalización</th>
                        <th class="px-4 py-2">Estado</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($etiquetas as $etiqueta)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-4 py-2">{{ $etiqueta->id }}</td>
                            <td class="px-4 py-2">
                                @if ($etiqueta->planilla_id)
                                    <a href="{{ route('planillas.index', ['planilla_id' => $etiqueta->planilla_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->planilla->codigo_limpio }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $etiqueta->user_name }}</td>
                            <td class="px-4 py-2">{{ $etiqueta->user2_name }}</td>
                            <td class="px-4 py-2">
                                @if ($etiqueta->paquete)
                                    <a href="{{ route('paquetes.index', ['paquete_id' => $etiqueta->paquete->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->paquete->id }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $etiqueta->numero_etiqueta }}</td>
                            <td class="px-4 py-2">{{ $etiqueta->nombre }}</td>
                            <td class="px-4 py-2">
                                @if ($etiqueta->ubicacion)
                                    <a href="{{ route('productos.show', $etiqueta->ubicacion_id) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->ubicacion->nombre }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if ($etiqueta->producto)
                                    <a href="{{ route('productos.show', $etiqueta->producto->id) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->producto->id }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if ($etiqueta->producto2)
                                    <a href="{{ route('productos.show', $etiqueta->producto2->id) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->producto2->id }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $etiqueta->peso_kg }}</td>
                            <td class="px-4 py-2">{{ $etiqueta->fecha_inicio ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $etiqueta->fecha_finalizacion ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $etiqueta->estado }}</td>
                            <td class="px-4 py-2 flex space-x-2">
                                <a href="{{ route('etiquetas.show', $etiqueta->id) }}"
                                    class="text-blue-500 hover:underline">Ver</a>
                                <a href="{{ route('etiquetas.edit', $etiqueta->id) }}"
                                    class="text-yellow-500 hover:underline">Editar</a>
                                <form action="{{ route('etiquetas.destroy', $etiqueta->id) }}" method="POST"
                                    class="inline"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar esta etiqueta?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center py-4 text-gray-500">No hay etiquetas registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">
            {{ $etiquetas->links() }}
        </div>
    </div>
</x-app-layout>
