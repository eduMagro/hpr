<div>
    <x-menu.planillas />

    <div class="w-full p-4 sm:p-2">
        <!-- Banner de revisión de planilla -->
        @if($planilla && !$planilla->revisada)
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 p-4 rounded-r-lg shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">⚠️</span>
                        <div>
                            <h3 class="text-lg font-bold text-red-800">
                                Planilla {{ $planilla->codigo }} SIN REVISAR
                            </h3>
                            <p class="text-sm text-red-700">
                                Esta planilla aparece en <strong>GRIS</strong> en el calendario. Revisa las máquinas asignadas y marca como revisada cuando esté correcta.
                            </p>
                        </div>
                    </div>
                    <form action="{{ route('planillas.marcarRevisada', $planilla->id) }}" method="POST"
                          onsubmit="return confirm('¿Marcar esta planilla como revisada?\n\nAparecerá en color normal en el calendario de producción.');">
                        @csrf
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded transition-colors whitespace-nowrap">
                            ✅ Marcar como revisada
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if($planilla && $planilla->revisada)
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 p-4 rounded-r-lg shadow">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">✅</span>
                    <div>
                        <h3 class="text-lg font-bold text-green-800">
                            Planilla {{ $planilla->codigo }} REVISADA
                        </h3>
                        <p class="text-sm text-green-700">
                            Revisada por <strong>{{ $planilla->revisor->name ?? 'N/A' }}</strong>
                            el {{ $planilla->fecha_revision ? (is_string($planilla->fecha_revision) && str_contains($planilla->fecha_revision, '/') ? \Carbon\Carbon::createFromFormat('d/m/Y H:i', $planilla->fecha_revision)->format('d/m/Y H:i') : \Carbon\Carbon::parse($planilla->fecha_revision)->format('d/m/Y H:i')) : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tabla de elementos con scroll horizontal -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg" wire:ignore.self>
            <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('id')">
                            ID @if($sort === 'id') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('codigo')">
                            Código @if($sort === 'codigo') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border">Planilla</th>
                        <th class="p-2 border">Etiqueta</th>
                        <th class="p-2 border">Subetiqueta</th>
                        <th class="p-2 border">Dimensiones</th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('diametro')">
                            Diámetro @if($sort === 'diametro') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border">Barras</th>
                        <th class="p-2 border">Maq. 1</th>
                        <th class="p-2 border">Maq. 2</th>
                        <th class="p-2 border">Maq. 3</th>
                        <th class="p-2 border">M. Prima 1</th>
                        <th class="p-2 border">M. Prima 2</th>
                        <th class="p-2 border">M. Prima 3</th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('figura')">
                            Figura @if($sort === 'figura') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('peso')">
                            Peso (kg) @if($sort === 'peso') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('longitud')">
                            Longitud (m) @if($sort === 'longitud') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('estado')">
                            Estado @if($sort === 'estado') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="elemento_id" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="ID...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Código...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo_planilla" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Planilla...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="etiqueta" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Etiqueta...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="subetiqueta" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Subetiqueta...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="dimensiones" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Dimensiones...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="diametro" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Diámetro...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="barras" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Barras...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="maquina" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Máquina...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="maquina_2" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Máquina 2...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="maquina3" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Máquina 3...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="producto1" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Producto 1...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="producto2" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Producto 2...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="producto3" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Producto 3...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="figura" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Figura...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="peso" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Peso...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="longitud" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Longitud...">
                        </th>
                        <th class="p-1 border">
                            <select wire:model.live="estado" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="fabricando">Fabricando</option>
                                <option value="fabricado">Fabricado</option>
                                <option value="montaje">Montaje</option>
                            </select>
                        </th>
                        <th class="p-1 border text-center align-middle">
                            <div class="flex justify-center gap-2 items-center h-full">
                                {{-- ♻️ Botón reset --}}
                                <button wire:click="limpiarFiltros"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                    title="Restablecer filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                    </svg>
                                </button>
                            </div>
                        </th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($elementos as $elemento)
                        <tr tabindex="0" x-data="{
                            editando: false,
                            seleccionada: false,
                            elemento: @js($elemento),
                            original: JSON.parse(JSON.stringify(@js($elemento)))
                        }"
                            @dblclick="if(!$event.target.closest('input, select, button, a')) {
                                if(!editando) {
                                    editando = true;
                                } else {
                                    elemento = JSON.parse(JSON.stringify(original));
                                    editando = false;
                                }
                            }"
                            @keydown.enter.stop="if(editando) { guardarCambios(elemento); editando = false; }"
                            :class="{
                                'bg-yellow-100': editando,
                                'bg-blue-100': seleccionada,
                                'hover:bg-blue-50': !seleccionada && !editando
                            }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 cursor-pointer text-xs uppercase transition-colors">

                            <!-- ID -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.id"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.id"
                                    class="form-control form-control-sm w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- CODIGO -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.codigo"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.codigo"
                                    class="form-control form-control-sm w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- PLANILLA -->
                            <td class="px-2 py-2 text-center border">
                                <a href="{{ route('elementos.index', ['planilla_id' => $elemento->planilla_id]) }}"
                                   class="text-blue-500 hover:underline">
                                    {{ $elemento->planilla->codigo_limpio ?? 'N/A' }}
                                </a>
                            </td>

                            <!-- ETIQUETA -->
                            <td class="px-2 py-2 text-center border">
                                <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion?->id ?? '#']) }}"
                                   class="text-blue-500 hover:underline">
                                    {{ $elemento->etiquetaRelacion?->id ?? 'N/A' }}
                                </a>
                            </td>

                            <!-- SUBETIQUETA -->
                            <td class="px-2 py-2 text-center border">
                                <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion?->id ?? '#']) }}"
                                   class="text-blue-500 hover:underline">
                                    {{ $elemento->subetiqueta ?? 'N/A' }}
                                </a>
                            </td>

                            <!-- DIMENSIONES -->
                            <td class="px-1 py-3 text-center border">
                                <span>{{ $elemento->dimensiones ?? 'N/A' }}</span>
                            </td>

                            <!-- DIAMETRO_MM -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.diametro_mm"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.diametro"
                                    class="form-control form-control-sm w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- BARRAS -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.barras"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.barras"
                                    class="form-control form-control-sm w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- MAQUINA 1 -->
                            <td class="px-1 py-3 text-center border">
                                <div class="flex items-center justify-center gap-1">
                                    <select class="text-xs border rounded px-1 py-0.5 flex-1 text-gray-800 bg-white"
                                            data-id="{{ $elemento->id }}"
                                            data-field="maquina_id"
                                            onchange="actualizarCampoElemento(this)">
                                        <option value="">N/A</option>
                                        @foreach ($maquinas->whereIn('tipo', ['cortadora_dobladora', 'estribadora', 'cortadora manual']) as $maquina)
                                            <option value="{{ $maquina->id }}" @selected($elemento->maquina_id === $maquina->id)>
                                                {{ $maquina->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <a href="#"
                                        class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center abrir-modal-dibujo flex-shrink-0"
                                        data-id="{{ $elemento->id }}"
                                        data-codigo="{{ $elemento->codigo }}"
                                        data-dimensiones="{{ $elemento->dimensiones }}"
                                        data-peso="{{ $elemento->peso_kg }}"
                                        title="Ver figura del elemento">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>

                            <!-- MAQUINA 2 -->
                            <td class="px-1 py-3 text-center border">
                                <select class="text-xs border rounded px-1 py-0.5 text-gray-800 bg-white"
                                        data-id="{{ $elemento->id }}"
                                        data-field="maquina_id_2"
                                        onchange="actualizarCampoElemento(this)">
                                    <option value="">N/A</option>
                                    @foreach ($maquinas->whereIn('tipo', ['cortadora_dobladora', 'estribadora', 'cortadora_manual', 'dobladora_manual', 'soldadora']) as $maquina)
                                        <option value="{{ $maquina->id }}" @selected($elemento->maquina_id_2 === $maquina->id)>
                                            {{ $maquina->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- MAQUINA 3 -->
                            <td class="px-1 py-3 text-center border">
                                <select class="text-xs border rounded px-1 py-0.5 text-gray-800 bg-white"
                                        data-id="{{ $elemento->id }}"
                                        data-field="maquina_id_3"
                                        onchange="actualizarCampoElemento(this)">
                                    <option value="">N/A</option>
                                    @foreach ($maquinas->whereIn('tipo', ['soldadora', 'ensambladora']) as $maquina)
                                        <option value="{{ $maquina->id }}" @selected($elemento->maquina_id_3 === $maquina->id)>
                                            {{ $maquina->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- PRODUCTO 1 -->
                            <td class="px-2 py-2 text-center border">
                                <a href="{{ route('productos.index', ['id' => $elemento->producto_id]) }}"
                                   class="text-blue-500 hover:underline">
                                    {{ $elemento->producto_id ?? 'N/A' }}
                                </a>
                            </td>

                            <!-- PRODUCTO 2 -->
                            <td class="px-2 py-2 text-center border">
                                <a href="{{ route('productos.index', ['id' => $elemento->producto_id_2]) }}"
                                   class="text-blue-500 hover:underline">
                                    {{ $elemento->producto_id_2 ?? 'N/A' }}
                                </a>
                            </td>

                            <!-- PRODUCTO 3 -->
                            <td class="px-2 py-2 text-center border">
                                <a href="{{ route('productos.index', ['id' => $elemento->producto_id_3]) }}"
                                   class="text-blue-500 hover:underline">
                                    {{ $elemento->producto_id_3 ?? 'N/A' }}
                                </a>
                            </td>

                            <!-- FIGURA -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.figura"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.figura"
                                    class="form-control form-control-sm w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- PESO_KG -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.peso_kg"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.peso"
                                    class="form-control form-control-sm w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- LONGITUD_M -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.longitud_m"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.longitud"
                                    class="form-control form-control-sm w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- ESTADO -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.estado"></span>
                                </template>
                                <select x-show="editando" x-model="elemento.estado" class="form-select w-full text-xs border rounded px-1 py-0.5">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="fabricando">Fabricando</option>
                                    <option value="fabricado">Fabricado</option>
                                </select>
                            </td>

                            <!-- BOTONES -->
                            <td class="px-1 py-2 border text-xs font-bold">
                                <div class="flex items-center space-x-2 justify-center">
                                    <!-- Mostrar solo en modo edición -->
                                    <button x-show="editando"
                                            @click="guardarCambios(elemento); editando = false"
                                            class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                            title="Guardar cambios">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button x-show="editando"
                                            @click="elemento = JSON.parse(JSON.stringify(original)); editando = false"
                                            class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center"
                                            title="Cancelar edición">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>

                                    <!-- Mostrar solo cuando NO está en modo edición -->
                                    <template x-if="!editando">
                                        <div class="flex items-center space-x-2">
                                            <button @click="editando = true"
                                                    class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center"
                                                    title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <form action="{{ route('elementos.destroy', $elemento->id) }}" method="POST"
                                                  onsubmit="return confirm('¿Eliminar elemento {{ $elemento->codigo }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center"
                                                        title="Eliminar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="19" class="text-center py-4 text-gray-500">
                                No hay elementos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="bg-gradient-to-r from-blue-50 to-blue-100 border-t border-blue-300">
                        <td colspan="19" class="px-6 py-3">
                            <div class="flex justify-end items-center gap-4 text-sm text-gray-700">
                                <span class="font-semibold">Total peso filtrado:</span>
                                <span class="text-base font-bold text-blue-800">
                                    {{ number_format($totalPesoFiltrado, 2, ',', '.') }} kg
                                </span>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Paginación Livewire -->
        <x-tabla.paginacion-livewire :paginador="$elementos" />

        <!-- Modal de dibujo -->
        <div id="modal-dibujo" class="hidden fixed inset-0 flex justify-end items-center pr-96 pointer-events-none" wire:ignore>
            <div class="bg-white rounded-lg p-5 w-3/4 max-w-lg relative pointer-events-auto shadow-lg border border-gray-300">
                <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">✖</button>
                <h2 class="text-lg font-semibold mb-3" id="modal-titulo">Elemento</h2>
                <canvas id="canvas-dibujo" class="border border-gray-300 w-full h-[300px]"></canvas>
            </div>
        </div>
    </div>

    <!-- Loading indicator flotante -->
    <div wire:loading class="fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg z-50">
        <div class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Filtrando...</span>
        </div>
    </div>

    {{-- Scripts JavaScript --}}
    @push('scripts')
    <script src="{{ asset('js/elementosJs/guardarCambios.js') }}" defer></script>
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
    <script>
        function actualizarCampoElemento(input) {
            const id = input.dataset.id;
            const campo = input.dataset.field;
            const valor = input.value;

            const valorOriginal = input.dataset.originalValue || input.defaultValue || '';
            if (!input.dataset.originalValue) {
                input.dataset.originalValue = valorOriginal;
            }

            console.log(`Actualizando elemento ${id}, campo ${campo}, valor: "${valor}"`);

            fetch(`/elementos/${id}/actualizar-campo`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    campo: campo,
                    valor: valor
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Campo actualizado correctamente');
                    input.dataset.originalValue = valor;
                    // Recargar la página para ver los cambios
                    window.location.reload();
                } else {
                    console.error('❌ Error al actualizar:', data.message);
                    alert('Error: ' + (data.message || 'No se pudo actualizar'));
                    input.value = valorOriginal;
                }
            })
            .catch(error => {
                console.error('❌ Error de red:', error);
                alert('Error de conexión. Por favor, inténtalo de nuevo.');
                input.value = valorOriginal;
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById("modal-dibujo");
            const titulo = document.getElementById("modal-titulo");
            const canvas = document.getElementById("canvas-dibujo");
            const cerrar = document.getElementById("cerrar-modal");

            let timeoutCerrar = null;

            function abrirModal(ojo) {
                if (timeoutCerrar) {
                    clearTimeout(timeoutCerrar);
                    timeoutCerrar = null;
                }

                const id = ojo.dataset.id;
                const codigo = ojo.dataset.codigo;
                const dimensiones = ojo.dataset.dimensiones;
                const peso = ojo.dataset.peso;

                if (titulo) titulo.textContent = `${codigo}`;

                window.elementoData = {
                    id,
                    dimensiones,
                    peso
                };

                modal.classList.remove("hidden");

                if (typeof window.dibujarFiguraElemento === 'function') {
                    window.dibujarFiguraElemento("canvas-dibujo", dimensiones, peso);
                }
            }

            function cerrarModal() {
                timeoutCerrar = setTimeout(() => {
                    modal.classList.add("hidden");
                }, 100);
            }

            function mantenerModalAbierto() {
                if (timeoutCerrar) {
                    clearTimeout(timeoutCerrar);
                    timeoutCerrar = null;
                }
            }

            document.querySelectorAll(".abrir-modal-dibujo").forEach(ojo => {
                ojo.addEventListener("mouseenter", () => abrirModal(ojo));
                ojo.addEventListener("mouseleave", cerrarModal);
                ojo.addEventListener("click", e => e.preventDefault());
            });

            if (modal) {
                modal.addEventListener("mouseenter", mantenerModalAbierto);
                modal.addEventListener("mouseleave", cerrarModal);
            }

            if (cerrar) {
                cerrar.addEventListener("click", () => {
                    if (timeoutCerrar) {
                        clearTimeout(timeoutCerrar);
                        timeoutCerrar = null;
                    }
                    modal.classList.add("hidden");
                });
            }

            // Re-inicializar event listeners después de que Livewire actualice
            document.addEventListener('livewire:navigated', () => {
                document.querySelectorAll(".abrir-modal-dibujo").forEach(ojo => {
                    ojo.addEventListener("mouseenter", () => abrirModal(ojo));
                    ojo.addEventListener("mouseleave", cerrarModal);
                    ojo.addEventListener("click", e => e.preventDefault());
                });
            });
        });
    </script>
    @endpush
</div>
