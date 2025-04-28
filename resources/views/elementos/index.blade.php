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
        </h2>
    </x-slot>
    @php
        $filtrosActivos = [];

        if (request('buscar')) {
            $filtrosActivos[] = 'contiene <strong>“' . request('buscar') . '”</strong>';
        }

        if (request('id')) {
            $filtrosActivos[] = 'ID: <strong>' . request('id') . '</strong>';
        }

        if (request('codigo_planilla')) {
            $filtrosActivos[] = 'Código de planilla: <strong>' . request('codigo_planilla') . '</strong>';
        }

        if (request('usuario1')) {
            $filtrosActivos[] = 'Operario 1: <strong>' . request('usuario1') . '</strong>';
        }

        if (request('usuario2')) {
            $filtrosActivos[] = 'Operario 2: <strong>' . request('usuario2') . '</strong>';
        }

        if (request('etiqueta')) {
            $filtrosActivos[] = 'Etiqueta ID: <strong>' . request('etiqueta') . '</strong>';
        }

        if (request('subetiqueta')) {
            $filtrosActivos[] = 'Subetiqueta: <strong>' . request('subetiqueta') . '</strong>';
        }

        if (request('paquete_id')) {
            $filtrosActivos[] = 'Paquete ID: <strong>' . request('paquete_id') . '</strong>';
        }

        if (request('maquina')) {
            $filtrosActivos[] = 'Máquina 1: <strong>' . request('maquina') . '</strong>';
        }

        if (request('maquina_2')) {
            $filtrosActivos[] = 'Máquina 2: <strong>' . request('maquina_2') . '</strong>';
        }

        if (request('maquina3')) {
            $filtrosActivos[] = 'Máquina 3: <strong>' . request('maquina3') . '</strong>';
        }

        if (request('producto1')) {
            $filtrosActivos[] = 'Materia Prima 1: <strong>' . request('producto1') . '</strong>';
        }

        if (request('producto2')) {
            $filtrosActivos[] = 'Materia Prima 2: <strong>' . request('producto2') . '</strong>';
        }

        if (request('producto3')) {
            $filtrosActivos[] = 'Materia Prima 3: <strong>' . request('producto3') . '</strong>';
        }

        if (request('figura')) {
            $filtrosActivos[] = 'Figura: <strong>' . request('figura') . '</strong>';
        }

        if (request('estado')) {
            $estados = [
                'pendiente' => 'Pendiente',
                'fabricando' => 'Fabricando',
                'completado' => 'Completado',
                'montaje' => 'En Montaje',
            ];
            $filtrosActivos[] = 'Estado: <strong>' . ($estados[request('estado')] ?? request('estado')) . '</strong>';
        }

        if (request('fecha_inicio')) {
            $filtrosActivos[] = 'Desde: <strong>' . request('fecha_inicio') . '</strong>';
        }

        if (request('fecha_finalizacion')) {
            $filtrosActivos[] = 'Hasta: <strong>' . request('fecha_finalizacion') . '</strong>';
        }

        if (request('sort_by')) {
            $sorts = [
                'created_at' => 'Fecha de creación',
                'id' => 'ID',
                'figura' => 'Figura',
                'subetiqueta' => 'Subetiqueta',
                'paquete_id' => 'Paquete ID',
            ];
            $orden = request('order') == 'desc' ? 'descendente' : 'ascendente';
            $filtrosActivos[] =
                'Ordenado por <strong>' .
                ($sorts[request('sort_by')] ?? request('sort_by')) .
                "</strong> en orden <strong>$orden</strong>";
        }
    @endphp


    <div class="w-full p-4 sm:p-2">
        @if (count($filtrosActivos))
            <div class="alert alert-info text-sm mt-2 mb-4 shadow-sm">
                <strong>Filtros aplicados:</strong> {!! implode(', ', $filtrosActivos) !!}
            </div>
        @endif
        @php
            function ordenarColumnaElemento($columna, $titulo)
            {
                $currentSort = request('sort_by');
                $currentOrder = request('order');
                $isSorted = $currentSort === $columna;
                $nextOrder = $isSorted && $currentOrder === 'asc' ? 'desc' : 'asc';
                $icon = $isSorted ? ($currentOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down') : 'fas fa-sort';
                $url = request()->fullUrlWithQuery(['sort_by' => $columna, 'order' => $nextOrder]);

                return '<a href="' .
                    $url .
                    '" class="text-white text-decoration-none">' .
                    $titulo .
                    ' <i class="' .
                    $icon .
                    '"></i></a>';
            }
        @endphp

        <!-- Tabla de elementos con scroll horizontal -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-center text-xs uppercase">
                        <!-- Encabezados con orden dinámico -->
                        <th class="p-2 border">{!! ordenarColumnaElemento('id', 'ID') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('codigo_planilla', 'Planilla') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('usuario1', 'Op. 1') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('usuario2', 'Op. 2') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('etiqueta', 'Etiqueta') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('subetiqueta', 'SubEtiqueta') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('paquete_id', 'Paquete') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('maquina', 'Maq. 1') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('maquina_2', 'Maq. 2') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('maquina3', 'Maq. 3') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('producto1', 'M. Prima 1') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('producto2', 'M. Prima 2') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('producto3', 'M. Prima 3') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('figura', 'Figura') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('peso', 'Peso (kg)') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('diametro', 'Diámetro (mm)') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('longitud', 'Longitud (m)') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('estado', 'Estado') !!}</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <form method="GET" action="{{ route('elementos.index') }}">
                        <tr class="text-center text-xs uppercase">
                            @foreach (['id', 'codigo_planilla', 'usuario1', 'usuario2', 'etiqueta', 'subetiqueta', 'paquete_id', 'maquina', 'maquina_2', 'maquina3', 'producto1', 'producto2', 'producto3', 'figura'] as $campo)
                                <th class="py-3 border">
                                    <input type="text" name="{{ $campo }}" value="{{ request($campo) }}"
                                        class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </th>
                            @endforeach

                            <!-- Peso, diámetro, longitud: sin filtro -->
                            <th class="py-3 border">
                                <input type="text" name="peso" value="{{ request('peso') }}"
                                    class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                            </th>
                            <th class="py-3 border">
                                <input type="text" name="diametro" value="{{ request('diametro') }}"
                                    class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                            </th>
                            <th class="py-3 border">
                                <input type="text" name="longitud" value="{{ request('longitud') }}"
                                    class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                            </th>


                            <!-- Estado con select -->
                            <th class="py-3 border">
                                <select name="estado"
                                    class="w-full px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500">
                                    <option value="">Todos</option>
                                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>
                                        Pendiente</option>
                                    <option value="fabricando"
                                        {{ request('estado') == 'fabricando' ? 'selected' : '' }}>Fabricando</option>
                                    <option value="completado"
                                        {{ request('estado') == 'completado' ? 'selected' : '' }}>Completado</option>
                                    <option value="montaje" {{ request('estado') == 'montaje' ? 'selected' : '' }}>
                                        Montaje</option>
                                </select>
                            </th>

                            <!-- Botón buscar y limpiar -->
                            <th class="py-3 border flex gap-1 justify-center">
                                <button type="submit" class="btn btn-sm btn-info px-2"><i
                                        class="fas fa-search"></i></button>
                                <a href="{{ route('elementos.index') }}" class="btn btn-sm btn-warning px-2"><i
                                        class="fas fa-undo"></i></a>
                            </th>
                        </tr>
                    </form>
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
                                <input x-show="editando" type="text" x-model="elemento.id" class="form-input w-full">
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

                            <!-- SUBETIQUETA -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion?->id ?? '#']) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->subetiqueta ?? 'N/A' }}
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
