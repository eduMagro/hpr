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
            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
                <select name="estado" class="border p-2 rounded">
                    <option value="">Todos</option>
                    <option value="Completado" {{ request('estado') == 'Completado' ? 'selected' : '' }}>Completado
                    </option>
                    <option value="Fabricando" {{ request('estado') == 'Fabricando' ? 'selected' : '' }}>Fabricando
                    </option>
                    <option value="Montaje" {{ request('estado') == 'Montaje' ? 'selected' : '' }}>Montaje</option>
                </select>
            </div>
            <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="border p-2 rounded">
            <input type="date" name="fecha_finalizacion" value="{{ request('fecha_finalizacion') }}"
                class="border p-2 rounded">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Filtrar</button>
        </form>

        <!-- Tabla de elementos con scroll horizontal -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full table-fixed border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="py-3 border text-center">ID
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="id"
                                    class="w-20 px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Planilla
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
                        <th class="py-3 border text-center">Trabajador 1
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
                        <th class="py-3 border text-center">Trabajador 2</th>
                        <th class="py-3 border text-center">Etiqueta
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
                        <th class="py-3 border text-center">Paquete ID

                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="paquete_id"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>

                        <th class="py-3 border text-center">Máquina 1
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
                        <th class="py-3 border text-center">Máquina 2
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="maquina2"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Máquina 3
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="maquina3"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">M. Prima 1
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
                        <th class="py-3 border text-center">M. Prima 2
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
                        <th class="py-3 border text-center">Ubicación
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
                        <th class="py-3 border text-center">Figura
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
                        <th class="py-3 border text-center">Peso (kg)</th>
                        <th class="py-3 border text-center">Diámetro (mm)</th>
                        <th class="py-3 border text-center">Longitud (m)</th>
                        <th class="py-3 border text-center">Fecha Inicio</th>
                        <th class="py-3 border text-center">Fecha Finalización</th>
                        <th class="py-3 border text-center">Estado</th>
                        <th class="py-3 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($elementos as $elemento)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200"
                            x-data="{ editando: false, elemento: @js($elemento) }">
                            <!-- ID -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.id"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.id"
                                    class="form-input w-full">
                            </td>
                            <!-- PLANILLA -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('planillas.index', ['id' => $elemento->planilla->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->planilla->codigo_limpio }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.planilla.codigo_limpio"
                                    class="form-input w-full">
                            </td>
                            <!-- USUARIO 1 -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <!-- Se muestra el nombre de la máquina -->
                                    <span x-text="elemento.user?.name || 'N/A'"></span>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <input x-show="editando" type="text" x-model="elemento.users_id"
                                    class="form-input w-full">
                            </td>

                            <!-- USUARIO 2 -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <!-- Se muestra el nombre de la máquina -->
                                    <span x-text="elemento.user2?.name || 'N/A'"></span>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <input x-show="editando" type="text" x-model="elemento.users_id_2"
                                    class="form-input w-full">
                            </td>

                            <!-- ETIQUETA -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion?->id ?? '#']) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->etiquetaRelacion?->id ?? 'N/A' }}
                                    </a>

                                </template>
                                <input x-show="editando" type="text" x-model="elemento.etiquetaRelacion.id"
                                    class="form-input w-full">
                            </td>
                            <!-- PAQUETE -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('paquetes.index', ['id' => $elemento->paquete_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->paquete_id ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.paquete_id"
                                    class="form-input w-full">
                            </td>
                            <!-- MAQUINA 1 -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <!-- Se muestra el nombre de la máquina -->
                                    <span x-text="elemento.maquina?.nombre || 'N/A'"></span>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <input x-show="editando" type="text" x-model="elemento.maquina_id"
                                    class="form-input w-full">
                            </td>
                            <!-- MAQUINA 2 -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <!-- Se muestra el nombre de la máquina -->
                                    <span x-text="elemento.maquina2?.nombre || 'N/A'"></span>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <input x-show="editando" type="text" x-model="elemento.maquina_id_2"
                                    class="form-input w-full">
                            </td>

                            <!-- MAQUINA 3 -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <!-- Se muestra el nombre de la máquina -->
                                    <span x-text="elemento.maquina3?.nombre || 'N/A'"></span>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <input x-show="editando" type="text" x-model="elemento.maquina_id_3"
                                    class="form-input w-full">
                            </td>

                            <!-- PRODUCTO 1 -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.producto?.id ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.producto.id"
                                    class="form-input w-full">
                            </td>
                            <!-- PRODUCTO 2 -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.producto2?.id ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.producto2.id"
                                    class="form-input w-full">
                            </td>
                            <!-- UBICACION -->
                            <td class="px-4 py-3 text-center border" x-data="{ ubicacionNombre: elemento.ubicacion?.nombre ?? '' }">
                                <template x-if="!editando">
                                    <span x-text="ubicacionNombre || 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="ubicacionNombre"
                                    class="form-input w-full">
                            </td>

                            <!-- FIGURA -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.figura"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.figura"
                                    class="form-input w-full">
                            </td>
                            <!-- PESO_KG -->
                            <td class="px-4 py-3 text-center border">
                                <!-- Muestra el peso formateado en modo vista -->
                                <template x-if="!editando">
                                    <span x-text="elemento.peso_kg"></span>
                                </template>
                                <!-- Edita el valor original -->
                                <input x-show="editando" type="number" x-model="elemento.peso"
                                    class="form-input w-full">

                            </td>
                            <!-- DIAMETRO_MM -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.diametro_mm"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.diametro"
                                    class="form-input w-full">
                            </td>
                            <!-- LONGITUD_M -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.longitud_m"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.longitud"
                                    class="form-input w-full">
                            </td>
                            <!-- FECHA_INICIO -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.fecha_inicio ?? 'No asignado'"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="elemento.fecha_inicio"
                                    class="form-input w-full">
                            </td>
                            <!-- FECHA_FINALIZACION -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.fecha_finalizacion ?? 'No asignado'"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="elemento.fecha_finalizacion"
                                    class="form-input w-full">
                            </td>
                            <!-- ESTADO -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.estado"></span>
                                </template>
                                <select x-show="editando" x-model="elemento.estado" class="form-select w-full">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="fabricando">Fabricando</option>
                                    <option value="completado">Completado</option>
                                </select>
                            </td>
                            <!-- Botones -->
                            <td class="px-4 py-3 text-center border">

                                <a href="#" class="text-blue-500 hover:underline abrir-modal-dibujo"
                                    data-id="{{ $elemento->id }}" data-dimensiones="{{ $elemento->dimensiones }}"
                                    data-peso="{{ $elemento->peso_kg }}">
                                    Ver
                                </a><br>
                                <button @click.stop="editando = !editando">
                                    <span x-show="!editando">✏️</span>
                                    <span x-show="editando">✖</span>
                                    <span x-show="editando" @click.stop="guardarCambios(elemento)">✅</span>
                                </button><br>
                                <x-boton-eliminar :action="route('elementos.destroy', $elemento->id)" />
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

        <div class="mt-4 flex justify-center">
            {{ $elementos->links() }}
        </div>
        <!-- Modal -->
        <div id="modal-dibujo"
            class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center">
            <div class="bg-white rounded-lg p-5 w-3/4 max-w-lg relative">
                <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:text-red-900">
                    ✖
                </button>
                @if (isset($elemento) && $elemento->id)
                    <h2 class="text-lg font-semibold mb-3">Elemento #{{ $elemento->id }}</h2>
                @endif
                <canvas id="canvas-dibujo" class="border border-gray-300 w-full h-[300px]"></canvas>
            </div>
        </div>
    </div>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="{{ asset('js/elementosJs/guardarCambios.js') }}" defer></script>
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
    <script>
        @if (isset($elemento))
            window.elementoData = @json($elemento);
        @else
            window.elementoData = null;
        @endif
    </script>
</x-app-layout>
