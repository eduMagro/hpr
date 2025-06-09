<x-app-layout>
    <x-slot name="title">Elementos - {{ config('app.name') }}</x-slot>
    @php
        $rutaActual = request()->route()->getName();
    @endphp

    @if (auth()->user()->rol !== 'operario')
        <div class="w-full" x-data="{ open: false }">
            <!-- Menú móvil -->
            <div class="sm:hidden relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                    Opciones
                </button>

                <div x-show="open" x-transition @click.away="open = false"
                    class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                    x-cloak>

                    <a href="{{ route('planillas.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'planillas.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📄 Planillas
                    </a>

                    <a href="{{ route('paquetes.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'paquetes.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📦 Paquetes
                    </a>

                    <a href="{{ route('etiquetas.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'etiquetas.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🏷️ Etiquetas
                    </a>

                    <a href="{{ route('elementos.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'elementos.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🔩 Elementos
                    </a>
                </div>
            </div>

            <!-- Menú escritorio -->
            <div class="hidden sm:flex sm:mt-0 w-full">
                <a href="{{ route('planillas.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ $rutaActual === 'planillas.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📄 Planillas
                </a>

                <a href="{{ route('paquetes.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ $rutaActual === 'paquetes.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📦 Paquetes
                </a>

                <a href="{{ route('etiquetas.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ $rutaActual === 'etiquetas.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🏷️ Etiquetas
                </a>

                <a href="{{ route('elementos.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ $rutaActual === 'elementos.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🔩 Elementos
                </a>
            </div>
        </div>
    @endif

    @php
        $filtrosActivos = [];

        if (request('buscar')) {
            $filtrosActivos[] = 'contiene <strong>“' . request('buscar') . '”</strong>';
        }

        if (request('id')) {
            $filtrosActivos[] = 'ID: <strong>' . request('id') . '</strong>';
        }

        if (request('codigo')) {
            $filtrosActivos[] = 'Código de elemento: <strong>' . request('codigo') . '</strong>';
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
                'fabricado' => 'Fabricado',
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
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

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
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border">{!! ordenarColumnaElemento('id', 'ID') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('codigo', 'Código Elemento') !!}</th>
                        <th class="p-2 border">{!! ordenarColumnaElemento('codigo_planilla', 'Planilla') !!}</th>
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

                    <tr class="text-center text-xs uppercase">
                        <form method="GET" action="{{ route('elementos.index') }}">
                            @foreach (['id', 'codigo', 'codigo_planilla', 'etiqueta', 'subetiqueta', 'paquete_id', 'maquina', 'maquina_2', 'maquina3', 'producto1', 'producto2', 'producto3', 'figura', 'peso', 'diametro', 'longitud'] as $campo)
                                <th class="p-1 border">
                                    <x-tabla.input name="{{ $campo }}" value="{{ request($campo) }}" />
                                </th>
                            @endforeach

                            <th class="p-1 border">
                                <x-tabla.select name="estado" :options="[
                                    'pendiente' => 'Pendiente',
                                    'fabricando' => 'Fabricando',
                                    'fabricado' => 'Fabricado',
                                    'montaje' => 'Montaje',
                                ]" :selected="request('estado')" empty="Todos" />
                            </th>

                            <x-tabla.botones-filtro ruta="elementos.index" />
                        </form>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($elementos as $elemento)

                        <tr tabindex="0" x-data="{
                            editando: false,
                            elemento: @js($elemento),
                            original: JSON.parse(JSON.stringify(@js($elemento)))
                        }"
                            @dblclick="if(!$event.target.closest('input')) {
                                  if(!editando) {
                                    editando = true;
                                  } else {
                                    elemento = JSON.parse(JSON.stringify(original));
                                    editando = false;
                                  }
                                }"
                            @keydown.enter.stop="guardarCambios(elemento); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">
                            <!-- ID -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.id"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.id"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- CODIGO -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <input type="text" x-model="elemento.codigo"
                                        class="form-control form-control-sm">
                                </template>
                            </td>
                            <!-- PLANILLA -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('planillas.index', ['planilla_id' => $elemento->planilla->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->planilla->codigo_limpio }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.planilla.codigo_limpio"
                                    class="form-control form-control-sm">
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
                                    class="form-control form-control-sm">
                            </td>

                            <!-- SUBETIQUETA -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion?->id ?? '#']) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->subetiqueta ?? 'N/A' }}
                                    </a>

                                </template>
                                <input x-show="editando" type="text" x-model="elemento.subetiquetas"
                                    class="form-control form-control-sm">
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
                                    class="form-control form-control-sm">
                            </td>
                            <!-- MAQUINA 1 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <!-- Se muestra el nombre de la máquina -->
                                    <span x-text="elemento.maquina.nombre || 'N/A'"></span>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <select x-show="editando" x-model="elemento.maquina_id"
                                    class="form-control form-control-sm">
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
                                    class="form-control form-control-sm">
                            </td>

                            <!-- MAQUINA 3 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <!-- Se muestra el nombre de la máquina -->
                                    <span x-text="elemento.maquina_3.nombre || 'N/A'"></span>
                                </template>
                                <!-- En modo edición, se edita el id de la máquina -->
                                <input x-show="editando" type="text" x-model="elemento.maquina_id_3"
                                    class="form-control form-control-sm">
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
                                    class="form-control form-control-sm">
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
                                    class="form-control form-control-sm">
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
                                    class="form-control form-control-sm">
                            </td>
                            <!-- FIGURA -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.figura"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.figura"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- PESO_KG -->
                            <td class="px-1 py-3 text-center border">
                                <!-- Muestra el peso formateado en modo vista -->
                                <template x-if="!editando">
                                    <span x-text="elemento.peso_kg"></span>
                                </template>
                                <!-- Edita el valor original -->
                                <input x-show="editando" type="number" x-model="elemento.peso"
                                    class="form-control form-control-sm">

                            </td>
                            <!-- DIAMETRO_MM -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.diametro_mm"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.diametro"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- LONGITUD_M -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.longitud_m"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.longitud"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- ESTADO -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.estado"></span>
                                </template>
                                <select x-show="editando" x-model="elemento.estado" class="form-select w-full">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="fabricando">Fabricando</option>
                                    <option value="fabricado">Fabricado</option>
                                </select>
                            </td>
                            <!-- Botones -->

                            <td class="px-1 py-2 border text-xs font-bold">
                                <div class="flex items-center space-x-2 justify-center">
                                    <!-- Mostrar solo en modo edición -->
                                    <x-tabla.boton-guardar x-show="editando"
                                        @click="guardarCambios(elemento); editando = false" />
                                    <x-tabla.boton-cancelar-edicion @click="editando = false" x-show="editando" />

                                    <!-- Mostrar solo cuando NO está en modo edición -->
                                    <template x-if="!editando">
                                        <div class="flex items-center space-x-2">
                                            <x-tabla.boton-editar @click="editando = true" x-show="!editando" />
                                            <a href="#"
                                                class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center abrir-modal-dibujo"
                                                data-id="{{ $elemento->id }}"
                                                data-dimensiones="{{ $elemento->dimensiones }}"
                                                data-peso="{{ $elemento->peso_kg }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>

                                            <x-tabla.boton-eliminar :action="route('elementos.destroy', $elemento->id)" />

                                        </div>
                                    </template>
                                </div>
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

        <x-tabla.paginacion :paginador="$elementos" />
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
