<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('planillas.index') }}" class="text-blue-600">
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
            {{ __('Lista de Elementos') }}
            <span class="mx-2">/</span>
            <a href="{{ route('subpaquetes.index') }}" class="text-blue-600">
                {{ __('Subpaquetes') }}
            </a>
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <!-- Formulario de filtrado -->
   <form method="GET" action="{{ route('elementos.index') }}" class="mb-4 grid grid-cols-8 gap-4">
    <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
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

        <!-- Tabla de elementos con scroll horizontal -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
               <table class="w-full border border-gray-300 rounded-lg">
                        <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-3 border">ID
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
                        <th class="px-4 py-3 border">Planilla
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
                        <th class="px-4 py-3 border">Usuario
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
                        <th class="px-4 py-3 border">Usuario 2</th>
                        <th class="px-4 py-3 border">Etiqueta
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
                        <th class="px-4 py-3 border">Nombre</th>
                        <th class="px-4 py-3 border">Máquina 1
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
                        <th class="px-4 py-3 border">Máquina 2
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
                        <th class="px-4 py-3 border">Máquina 3
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
                        <th class="px-4 py-3 border">M. Prima 1
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
                        <th class="px-4 py-3 border">M. Prima 2
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
                        <th class="px-4 py-3 border">Paquete ID
						
						<form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="paquete_id"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form></th>
                        <th class="px-4 py-3 border">Ubicación
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
                        <th class="px-4 py-3 border">Figura
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
                        <th class="px-4 py-3 border">Peso (kg)</th>
                        <th class="px-4 py-3 border">Diámetro (mm)</th>
                        <th class="px-4 py-3 border">Longitud (m)</th>
                        <th class="px-4 py-3 border">Fecha Inicio</th>
                        <th class="px-4 py-3 border">Fecha Finalización</th>
                        <th class="px-4 py-3 border">Estado</th>
                        <th class="px-4 py-3 border">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($elementos as $elemento)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                            <td class="px-4 py-3 text-center border">{{ $elemento->id }}</td>
                            <td class="px-4 py-3 text-center border">
                                <a href="{{ route('planillas.index', ['id' => $elemento->planilla->id]) }}" class="text-blue-500 hover:underline">
                                    {{ $elemento->planilla->codigo_limpio }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->user->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->user2->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->etiquetaRelacion->id ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->nombre }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->maquina->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->maquina_2->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->maquina_3->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->producto->id ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->producto2->id ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->paquete_id ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->ubicacion->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->figura }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->peso_kg }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->diametro_mm }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->longitud_m }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->fecha_inicio ?? 'No asignado' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->fecha_finalizacion ?? 'No asignado' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->estado }}</td>
                            <td class="px-4 py-3 text-center border">
                                <a href="{{ route('elementos.show', $elemento->id) }}" class="text-blue-500 hover:underline">Ver</a>
                                <a href="{{ route('elementos.edit', $elemento->id) }}" class="text-yellow-500 hover:underline">Editar</a>
                              <x-boton-eliminar :action="route('elementos.destroy', $elemento->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="25" class="text-center py-4 text-gray-500">No hay elementos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-center">
            {{ $elementos->links() }}
        </div>
    </div>
</x-app-layout>
