<x-app-layout>
    <x-slot name="title">Elementos - {{ config('app.name') }}</x-slot>
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

    <div class="w-full p-4 sm:p-2">
        <!-- Formulario de filtrado -->
        <form method="GET" action="{{ route('elementos.index') }}"
            class="mb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2 md:gap-4 p-2">

            <!-- Estado -->
            <div class="flex flex-col">
                <label for="estado" class="text-sm font-medium text-gray-700">Estado</label>
                <select name="estado" class="border p-2 rounded w-full">
                    <option value="">Todos</option>
                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente
                    </option>
                    <option value="fabricando" {{ request('estado') == 'fabricando' ? 'selected' : '' }}>Fabricando
                    </option>
                    <option value="completado" {{ request('estado') == 'completado' ? 'selected' : '' }}>Completado
                    </option>
                    <option value="montaje" {{ request('estado') == 'montaje' ? 'selected' : '' }}>Montaje</option>
                </select>
            </div>

            <!-- Fecha Inicio -->
            <div class="flex flex-col">
                <label for="fecha_inicio" class="text-sm font-medium text-gray-700">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}"
                    class="border p-2 rounded w-full">
            </div>

            <!-- Fecha Finalización -->
            <div class="flex flex-col">
                <label for="fecha_finalizacion" class="text-sm font-medium text-gray-700">Fecha Fin</label>
                <input type="date" name="fecha_finalizacion" value="{{ request('fecha_finalizacion') }}"
                    class="border p-2 rounded w-full">
            </div>

            <!-- Botón Filtrar -->
            <div class="flex flex-col justify-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded w-full md:w-auto">
                    Filtrar
                </button>
            </div>
        </form>

        <!-- Tabla de elementos con scroll horizontal -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-top h-full">
                                <span class="self-center">ID</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="id"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">Planilla</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="codigo_planilla"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">Op. 1</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="usuario1"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">Op. 2</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="usuario2"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">Etiqueta</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="etiqueta"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">Paquete</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="paquete_id"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">Maq. 1</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="maquina"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">Maq. 2</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="maquina2"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">Maq. 3</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="maquina3"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">M. Prima 1</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="producto1"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">M. Prima 2</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="producto2"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">M. Prima 3</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="producto3"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>
                        <th class="py-3 border text-center">
                            <div class="flex flex-col items-end h-full">
                                <span class="self-center">Figura</span>
                                <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 w-full">
                                    <input type="text" name="figura"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </div>
                        </th>

                        <th class="py-3 border text-center">Peso (kg)</th>
                        <th class="py-3 border text-center">Diámetro (mm)</th>
                        <th class="py-3 border text-center">Longitud (m)</th>
                        <th class="py-3 border text-center">Estado</th>
                        <th class="py-3 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($elementos as $elemento)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer"
                            x-data="{
                                editando: false,
                                elemento: @js($elemento),
                                inicializar() {
                            
                                    this.elemento.producto = this.elemento.producto || { id: '' };
                                    this.elemento.producto2 = this.elemento.producto2 || { id: '' };
                                    this.elemento.producto3 = this.elemento.producto3 || { id: '' };
                                    this.elemento.figura = this.elemento.figura || '';
                                    this.elemento.peso = this.elemento.peso || 0;
                                    this.elemento.diametro = this.elemento.diametro || 0;
                                    this.elemento.longitud = this.elemento.longitud || 0;
                                    this.elemento.maquina = this.elemento.maquina || { nombre: 'N/A' };
                                    this.elemento.maquina_2 = this.elemento.maquina_2 || { nombre: 'N/A' };
                                    this.elemento.maquina_3 = this.elemento.maquina_3 || { nombre: 'N/A' };
                                }
                            }" x-init="inicializar()">
                            <!-- ID -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.id"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.id"
                                    class="form-input w-full">
                            </td>
                            <!-- PLANILLA -->
                            <td class="px-1 py-3 text-center border">
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
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('users.index', ['id' => $elemento->user->id ?? '#']) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->user->name ?? 'N/A' }}
                                    </a>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <input x-show="editando" type="text" x-model="elemento.users_id"
                                    class="form-input w-full">
                            </td>
                            <!-- USUARIO 2 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('users.index', ['id' => $elemento->user2->id ?? '#']) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->user2->name ?? 'N/A' }}
                                    </a>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <input x-show="editando" type="text" x-model="elemento.users_id_2"
                                    class="form-input w-full">
                            </td>

                            <!-- ETIQUETA -->
                            <td class="px-1 py-3 text-center border">
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
                            <td class="px-1 py-3 text-center border">
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
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <!-- Se muestra el nombre de la máquina -->
                                    <span x-text="elemento.maquina.nombre || 'N/A'"></span>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <select x-show="editando" x-model="elemento.maquina_id" class="form-input w-full">
                                    <option value="">Seleccionar máquina</option>
                                    @foreach ($maquinas as $maquina)
                                        <option value="{{ $maquina->id }}">{{ $maquina->codigo }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <!-- MAQUINA 2 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <!-- Se muestra el nombre de la máquina -->
                                    <span x-text="elemento.maquina_2.nombre || 'N/A'"></span>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <input x-show="editando" type="text" x-model="elemento.maquina_id_2"
                                    class="form-input w-full">
                            </td>

                            <!-- MAQUINA 3 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <!-- Se muestra el nombre de la máquina -->
                                    <span x-text="elemento.maquina_3.nombre || 'N/A'"></span>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <input x-show="editando" type="text" x-model="elemento.maquina_id_3"
                                    class="form-input w-full">
                            </td>

                            <!-- PRODUCTO 1 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('productos.index', ['id' => $elemento->producto_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->producto_id ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.producto_id"
                                    class="form-input w-full">
                            </td>
                            <!-- PRODUCTO 2 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('productos.index', ['id' => $elemento->producto_id_2]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->producto_id_2 ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.producto_id_2"
                                    class="form-input w-full">
                            </td>
                            <!-- PRODUCTO 3 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('productos.index', ['id' => $elemento->producto_id_3]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->producto_id_3 ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.producto_id_3"
                                    class="form-input w-full">
                            </td>
                            <!-- FIGURA -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.figura"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.figura"
                                    class="form-input w-full">
                            </td>
                            <!-- PESO_KG -->
                            <td class="px-1 py-3 text-center border">
                                <!-- Muestra el peso formateado en modo vista -->
                                <template x-if="!editando">
                                    <span x-text="elemento.peso_kg"></span>
                                </template>
                                <!-- Edita el valor original -->
                                <input x-show="editando" type="number" x-model="elemento.peso"
                                    class="form-input w-full">

                            </td>
                            <!-- DIAMETRO_MM -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.diametro_mm"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.diametro"
                                    class="form-input w-full">
                            </td>
                            <!-- LONGITUD_M -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.longitud_m"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.longitud"
                                    class="form-input w-full">
                            </td>
                            <!-- ESTADO -->
                            <td class="px-1 py-3 text-center border">
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
                            <td class="px-1 py-3 text-center border flex flex-col gap-2">
                                <a href="#" class="text-blue-500 hover:text-blue-700 abrir-modal-dibujo"
                                    data-id="{{ $elemento->id }}" data-dimensiones="{{ $elemento->dimensiones }}"
                                    data-peso="{{ $elemento->peso_kg }}">
                                    Ver
                                </a>
                                <button @click.stop="editando = !editando">
                                    <span x-show="!editando">✏️</span>
                                    <span x-show="editando" class="mr-2">✖</span>
                                    <span x-show="editando" @click.stop="guardarCambios(elemento)">✅</span>
                                </button>
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
            {{ $elementos->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}

        </div>
        <!-- Modal -->
        <div id="modal-dibujo"
            class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center">
            <div class="bg-white rounded-lg p-5 w-3/4 max-w-lg relative">
                <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">
                    ✖
                </button>
                @if (isset($elemento) && $elemento->id)
                    <h2 class="text-lg font-semibold mb-3">Elemento #{{ $elemento->id }}</h2>
                @endif
                <canvas id="canvas-dibujo" class="border border-gray-300 w-full h-[300px]"></canvas>
            </div>
        </div>
    </div>

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
