<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('planillas.index') }}" class="text-blue-600">
                {{ __('Planillas') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Lista de Elementos') }}
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

        <form method="GET" action="{{ route('elementos.index') }}" class="mb-4 grid grid-cols-8 gap-4">
            <select name="estado" class="border p-2 rounded">
                <option value="">Todos</option>
                <option value="Completado" {{ request('estado') == 'Completado' ? 'selected' : '' }}>Completado
                </option>
                <option value="Fabricando" {{ request('estado') == 'Fabricando' ? 'selected' : '' }}>Fabricando
                </option>
                <option value="Montaje" {{ request('estado') == 'Montaje' ? 'selected' : '' }}>Montaje</option>
            </select>
            <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="border p-2 rounded">
            <input type="date" name="fecha_finalizacion" value="{{ request('fecha_finalizacion') }}"
                class="border p-2 rounded">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Filtrar</button>
        </form>
        <!-- Contenedor de la tabla con ancho completo y scroll horizontal -->
        <div class="w-full overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="w-full min-w-[1200px] border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-2">
                            ID
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
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
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="codigo_planilla"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">Usuario
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="usuario1"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">Usuario 2</th>
                        <th class="px-4 py-2">Etiqueta
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="etiqueta"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">Nombre</th>
                        <th class="px-4 py-2">Máquina 1
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="maquina"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">Máquina 2
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="maquina2"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">Máquina 3
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="maquina3"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">M. Prima 1
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="producto1"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">M. Prima 2
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="producto2"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">Paquete ID
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="paquete_id"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">Ubicación ID
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="ubicacion_id"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>


                        <th class="px-4 py-2">Figura
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="figura"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-2">Fila</th>
                        <th class="px-4 py-2">Descripción Fila</th>
                        <th class="px-4 py-2">Marca</th>
                        <th class="px-4 py-2">Etiqueta</th>
                        <th class="px-4 py-2">Barras</th>
                        <th class="px-4 py-2">Dobles Barra</th>
                        <th class="px-4 py-2">Peso (kg)</th>
                        <th class="px-4 py-2">Dimensiones</th>
                        <th class="px-4 py-2">Diámetro (mm)</th>
                        <th class="px-4 py-2">Longitud (cm)</th>
                        <th class="px-4 py-2">Longitud (m)</th>
                        <th class="px-4 py-2">Fecha Inicio</th>
                        <th class="px-4 py-2">Fecha Finalización</th>
                        <th class="px-4 py-2">Tiempo Fabricación</th>
                        <th class="px-4 py-2">Estado</th>
                        <th class="px-4 py-2">Suelta</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($elementos as $elemento)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-4 py-2">{{ $elemento->id }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('planillas.index', ['id' => $elemento->planilla->id]) }}"
                                    class="text-blue-500 hover:underline">
                                    {{ $elemento->planilla->codigo_limpio }}
                                </a>
                            </td>

                            <td class="px-4 py-2">{{ $elemento->user->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $elemento->user2->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">
                                @if ($elemento->etiquetaRelacion)
                                    <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->etiquetaRelacion->id }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>

                            <td class="px-4 py-2">{{ $elemento->nombre }}</td>
                            <td class="px-4 py-2">{{ $elemento->maquina->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $elemento->maquina_2->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $elemento->maquina_3->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-2">
                                @if ($elemento->producto)
                                    <a href="{{ route('productos.show', $elemento->producto_id) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->producto->id }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if ($elemento->producto2)
                                    <a href="{{ route('productos.show', $elemento->producto2->id) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->producto2->id }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $elemento->paquete_id ?? 'N/A' }}</td>
                            <td class="px-4 py-2">
                                @if ($elemento->ubicacion)
                                    <a href="{{ route('ubicaciones.show', ['ubicacione' => $elemento->ubicacion->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->ubicacion->nombre }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>


                            <td class="px-4 py-2">{{ $elemento->figura }}</td>
                            <td class="px-4 py-2">{{ $elemento->fila }}</td>
                            <td class="px-4 py-2">{{ $elemento->etiquetaRelacion->nombre }}</td>
                            <td class="px-4 py-2">{{ $elemento->marca }}</td>
                            <td class="px-4 py-2">{{ $elemento->etiqueta }}</td>
                            <td class="px-4 py-2">{{ $elemento->barras }}</td>
                            <td class="px-4 py-2">{{ $elemento->dobles_barra }}</td>
                            <td class="px-4 py-2">{{ $elemento->peso_kg }}</td>
                            <td class="px-4 py-2">{{ $elemento->dimensiones }}</td>
                            <td class="px-4 py-2">{{ $elemento->diametro_mm }}</td>
                            <td class="px-4 py-2">{{ $elemento->longitud_cm }}</td>
                            <td class="px-4 py-2">{{ $elemento->longitud_m }}</td>
                            <td class="px-4 py-2">{{ $elemento->fecha_inicio ?? 'No asignado' }}</td>
                            <td class="px-4 py-2">{{ $elemento->fecha_finalizacion ?? 'No asignado' }}</td>
                            <td class="px-4 py-2">{{ $elemento->tiempo_fabricacion_formato }}</td>
                            <td class="px-4 py-2">{{ $elemento->estado }}</td>
                            <td class="px-4 py-2">{{ $elemento->suelta }}</td>
                            <td class="px-4 py-2 flex space-x-2">
                                <a href="{{ route('elementos.show', $elemento->id) }}"
                                    class="text-blue-500 hover:underline">Ver</a>
                                <a href="{{ route('elementos.edit', $elemento->id) }}"
                                    class="text-yellow-500 hover:underline">Editar</a>
                                <form action="{{ route('elementos.destroy', $elemento->id) }}" method="POST"
                                    class="inline"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este elemento?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="25" class="text-center py-4 text-gray-500">No hay elementos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">
            {{ $elementos->links() }}
        </div>
    </div>
</x-app-layout>
