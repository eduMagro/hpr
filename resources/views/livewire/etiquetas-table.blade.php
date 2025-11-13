<div>
    <x-menu.planillas />

    <div class="w-full p-4 sm:p-2">
        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg" wire:ignore.self>
            <table class="w-full min-w-[1200px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('id')">
                            ID @if($sort === 'id') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('codigo')">
                            Codigo @if($sort === 'codigo') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border">Codigo SubEtiqueta</th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('codigo_planilla')">
                            Planilla @if($sort === 'codigo_planilla') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('paquete')">
                            Paquete @if($sort === 'paquete') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border">Op 1</th>
                        <th class="p-2 border">Op 2</th>
                        <th class="p-2 border">Ens 1</th>
                        <th class="p-2 border">Ens 2</th>
                        <th class="p-2 border">Sol 1</th>
                        <th class="p-2 border">Sol 2</th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('numero_etiqueta')">
                            Número de Etiqueta @if($sort === 'numero_etiqueta') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('nombre')">
                            Nombre @if($sort === 'nombre') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border">Marca</th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('peso')">
                            Peso (kg) @if($sort === 'peso') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border">Inicio Fabricación</th>
                        <th class="p-2 border">Final Fabricación</th>
                        <th class="p-2 border">Inicio Ensamblado</th>
                        <th class="p-2 border">Final Ensamblado</th>
                        <th class="p-2 border">Inicio Soldadura</th>
                        <th class="p-2 border">Final Soldadura</th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('estado')">
                            Estado @if($sort === 'estado') {{ $order === 'asc' ? '▲' : '▼' }} @endif
                        </th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="etiqueta_id" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="ID...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Código...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="etiqueta_sub_id" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="SubEtiqueta...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo_planilla" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Planilla...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="paquete" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Paquete...">
                        </th>
                        <th class="p-1 border"></th> {{-- Op 1 --}}
                        <th class="p-1 border"></th> {{-- Op 2 --}}
                        <th class="p-1 border"></th> {{-- Ens 1 --}}
                        <th class="p-1 border"></th> {{-- Ens 2 --}}
                        <th class="p-1 border"></th> {{-- Sol 1 --}}
                        <th class="p-1 border"></th> {{-- Sol 2 --}}
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="numero_etiqueta" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Número...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="nombre" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white" placeholder="Nombre...">
                        </th>
                        <th class="p-1 border"></th> {{-- Marca --}}
                        <th class="p-1 border"></th> {{-- Peso --}}
                        <th class="p-1 border">
                            <input type="date" wire:model.live.debounce.300ms="inicio_fabricacion" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white">
                        </th>
                        <th class="p-1 border">
                            <input type="date" wire:model.live.debounce.300ms="final_fabricacion" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white">
                        </th>
                        <th class="p-1 border">
                            <input type="date" wire:model.live.debounce.300ms="inicio_ensamblado" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white">
                        </th>
                        <th class="p-1 border">
                            <input type="date" wire:model.live.debounce.300ms="final_ensamblado" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white">
                        </th>
                        <th class="p-1 border">
                            <input type="date" wire:model.live.debounce.300ms="inicio_soldadura" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white">
                        </th>
                        <th class="p-1 border">
                            <input type="date" wire:model.live.debounce.300ms="final_soldadura" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white">
                        </th>
                        <th class="p-1 border">
                            <select wire:model.live="estado" class="w-full text-xs border rounded px-1 py-0.5 text-gray-800 bg-white">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="fabricando">Fabricando</option>
                                <option value="ensamblando">Ensamblando</option>
                                <option value="soldando">Soldando</option>
                                <option value="completada">Completada</option>
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
                    @forelse ($etiquetas as $etiqueta)
                        <tr tabindex="0" x-data="{
                            editando: false,
                            etiqueta: @js($etiqueta),
                            original: JSON.parse(JSON.stringify(@js($etiqueta)))
                        }"
                            @dblclick="if(!$event.target.closest('input, select, button, a')) {
                                if(!editando) {
                                    editando = true;
                                } else {
                                    etiqueta = JSON.parse(JSON.stringify(original));
                                    editando = false;
                                }
                            }"
                            @keydown.enter.stop="if(editando) { guardarCambios(etiqueta); editando = false; }"
                            :class="{
                                'bg-yellow-100': editando,
                                'hover:bg-blue-50': !editando
                            }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 cursor-pointer text-xs uppercase transition-colors">

                            <!-- ID (no editable) -->
                            <td class="p-2 text-center border">{{ $etiqueta->id }}</td>

                            <!-- CODIGO (no editable) -->
                            <td class="p-2 text-center border">{{ $etiqueta->codigo }}</td>

                            <!-- SUBETIQUETA (no editable) -->
                            <td class="p-2 text-center border">{{ $etiqueta->etiqueta_sub_id }}</td>

                            <!-- PLANILLA (no editable) -->
                            <td class="p-2 text-center border">
                                @if ($etiqueta->planilla_id)
                                    <a href="{{ route('planillas.index', ['planilla_id' => $etiqueta->planilla_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->planilla->codigo_limpio ?? 'N/A' }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>

                            <!-- PAQUETE (no editable) -->
                            <td class="p-2 text-center border">
                                @if (isset($etiqueta->paquete->codigo))
                                    <a href="{{ route('paquetes.index', [$etiqueta->paquete_id => $etiqueta->paquete->codigo]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->paquete->codigo }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>

                            <!-- Opeario 1 (no editable) -->
                            <td class="p-2 text-center border">
                                @if ($etiqueta->operario1)
                                    <a href="{{ route('users.index', ['users_id' => $etiqueta->operario1]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->operario1->name }}
                                        {{ $etiqueta->operario1->primer_apellido }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>

                            <!-- Operario 2 (no editable) -->
                            <td class="p-2 text-center border">
                                @if ($etiqueta->operario2)
                                    <a href="{{ route('users.index', ['users_id' => $etiqueta->operario2]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->operario2->name }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>

                            <!-- Ensamblador 1 (no editable) -->
                            <td class="p-2 text-center border">
                                @if ($etiqueta->ensamblador1)
                                    <a href="{{ route('users.index', ['users_id' => $etiqueta->ensamblador1]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->ensamblador1->name }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>

                            <!-- Ensamblador 2 (no editable) -->
                            <td class="p-2 text-center border">
                                @if ($etiqueta->ensamblador2)
                                    <a href="{{ route('users.index', ['users_id' => $etiqueta->ensamblador2]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $etiqueta->ensamblador2->name }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>

                            <!-- Soldador1 (no editable) -->
                            <td class="p-2 text-center border">{{ $etiqueta->soldador1->name ?? 'N/A' }}</td>

                            <!-- Soldador2 (no editable) -->
                            <td class="p-2 text-center border">{{ $etiqueta->soldador2->name ?? 'N/A' }}</td>

                            <!-- Número de Etiqueta (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.numero_etiqueta"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="etiqueta.numero_etiqueta"
                                    class="w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- Nombre (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.nombre"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="etiqueta.nombre"
                                    class="w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- Marca (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.marca"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="etiqueta.marca"
                                    class="w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- Peso (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.peso"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="etiqueta.peso"
                                    class="w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- Fecha Inicio Fabricación (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.fecha_inicio"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="etiqueta.fecha_inicio"
                                    class="w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- Fecha Finalización Fabricación (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.fecha_finalizacion"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="etiqueta.fecha_finalizacion"
                                    class="w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- Fecha Inicio Ensamblado (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.fecha_inicio_ensamblado"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="etiqueta.fecha_inicio_ensamblado"
                                    class="w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- Fecha Finalización Ensamblado (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.fecha_finalizacion_ensamblado"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="etiqueta.fecha_finalizacion_ensamblado"
                                    class="w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- Fecha Inicio Soldadura (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.fecha_inicio_soldadura"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="etiqueta.fecha_inicio_soldadura"
                                    class="w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- Fecha Finalización Soldadura (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.fecha_finalizacion_soldadura"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="etiqueta.fecha_finalizacion_soldadura"
                                    class="w-full text-xs border rounded px-1 py-0.5">
                            </td>

                            <!-- Estado (editable mediante select) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.estado ? etiqueta.estado.charAt(0).toUpperCase() + etiqueta.estado.slice(1) : ''"></span>
                                </template>
                                <select x-show="editando" x-model="etiqueta.estado" class="w-full text-xs border rounded px-1 py-0.5">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="fabricando">Fabricando</option>
                                    <option value="ensamblando">Ensamblando</option>
                                    <option value="soldando">Soldando</option>
                                    <option value="completada">Completada</option>
                                </select>
                            </td>

                            <!-- Acciones (no editable) -->
                            <td class="px-2 py-2 border text-xs font-bold">
                                <div class="flex items-center space-x-2 justify-center">
                                    {{-- Botones visibles solo en edición --}}
                                    <button x-show="editando"
                                            @click="guardarCambios(etiqueta); editando = false"
                                            class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                            title="Guardar cambios">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button x-show="editando"
                                            @click="etiqueta = JSON.parse(JSON.stringify(original)); editando = false"
                                            class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center"
                                            title="Cancelar edición">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>

                                    {{-- Botones normales --}}
                                    <template x-if="!editando">
                                        <div class="flex items-center space-x-2">
                                            <button @click="editando = true"
                                                    class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center"
                                                    title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button @click="mostrar({{ $etiqueta->id }})"
                                                class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center"
                                                title="Ver">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>

                                            {{-- Eliminar --}}
                                            <form action="{{ route('etiquetas.destroy', $etiqueta->id) }}" method="POST"
                                                  onsubmit="return confirm('¿Eliminar etiqueta {{ $etiqueta->codigo }}?');">
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
                            <td colspan="23" class="text-center py-4 text-gray-500">No hay etiquetas registradas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación con selector de cantidad por página -->
        <div class="m-4 text-center">
            <div class="inline-flex items-center justify-center gap-2 text-sm">
                <label class="text-gray-600">Mostrar</label>
                <select wire:model.live="perPage" class="border border-gray-300 rounded px-2 py-1 text-sm text-gray-800 bg-white">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-gray-600">por página</span>
            </div>
        </div>

        @if ($etiquetas->hasPages())
            <div class="mt-6 space-y-3 text-center">
                {{-- Texto resumen --}}
                <div class="text-sm text-gray-600">
                    Mostrando
                    <span class="font-semibold">{{ $etiquetas->firstItem() }}</span>
                    a
                    <span class="font-semibold">{{ $etiquetas->lastItem() }}</span>
                    de
                    <span class="font-semibold">{{ $etiquetas->total() }}</span>
                    resultados
                </div>

                {{-- Paginación --}}
                <div class="flex justify-center">
                    <div class="inline-flex flex-wrap gap-1 bg-white px-2 py-1 mb-6 rounded-md shadow-sm">
                        {{-- Botón anterior --}}
                        @if ($etiquetas->onFirstPage())
                            <span class="px-3 py-1 text-xs text-gray-400 cursor-not-allowed"><<</span>
                        @else
                            <button wire:click="previousPage" class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition"><<</button>
                        @endif

                        {{-- Lógica de paginación con recorte --}}
                        @php
                            $current = $etiquetas->currentPage();
                            $last = $etiquetas->lastPage();
                            $range = 2;
                            $pages = [];

                            $pages[] = 1;

                            for ($i = $current - $range; $i <= $current + $range; $i++) {
                                if ($i > 1 && $i < $last) {
                                    $pages[] = $i;
                                }
                            }

                            if ($last > 1) {
                                $pages[] = $last;
                            }

                            $pages = array_unique($pages);
                            sort($pages);
                        @endphp

                        @php $prevPage = 0; @endphp
                        @foreach ($pages as $page)
                            @if ($prevPage && $page > $prevPage + 1)
                                <span class="px-2 text-xs text-gray-400">…</span>
                            @endif

                            @if ($page == $current)
                                <span class="px-3 py-1 text-xs font-bold bg-blue-600 text-white rounded shadow border border-blue-700">
                                    {{ $page }}
                                </span>
                            @else
                                <button wire:click="gotoPage({{ $page }})" class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition">
                                    {{ $page }}
                                </button>
                            @endif

                            @php $prevPage = $page; @endphp
                        @endforeach

                        {{-- Botón siguiente --}}
                        @if ($etiquetas->hasMorePages())
                            <button wire:click="nextPage" class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition">>></button>
                        @else
                            <span class="px-3 py-1 text-xs text-gray-400 cursor-not-allowed">>></span>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal para mostrar etiqueta -->
        <div id="modalEtiqueta"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50" wire:ignore>

            <div class="relative bg-white p-4 rounded-lg max-w-4xl">
                <!-- Botón de cierre -->
                <button onclick="cerrarModal()" aria-label="Cerrar" id="modalClose"
                    class="absolute -top-3 -right-3 bg-white border border-black
                   rounded-full w-7 h-7 flex items-center justify-center
                   text-xl leading-none hover:bg-red-100 z-10">
                    &times;
                </button>

                <!-- Contenido dinámico de la etiqueta -->
                <div id="modalContent"></div>
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
    <style>
        /* === Contenedor general === */
        .etiqueta-wrapper {
            display: block;
            margin: 0.5rem 0;
        }

        .etiqueta-id-web-only {
            text-align: left;
            margin-bottom: 2px;
            font-size: 0.75rem;
            color: #4b5563;
        }

        /* === Etiqueta base (pantalla e impresión) === */
        .etiqueta-card {
            position: relative;
            width: 525px;
            height: 297px;
            box-sizing: border-box;
            border: 0.2mm solid #000;
            overflow: hidden;
            background: var(--bg-estado, #fff);
            padding: 3mm;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            transform-origin: top left;
            margin: 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .etiqueta-card svg {
            flex: 1 1 auto;
            width: 100%;
            height: 100%;
        }

        /* QR */
        .qr-box {
            position: absolute;
            top: 3mm;
            right: 3mm;
            border: 0.2mm solid #000;
            padding: 1mm;
            background: #fff;
        }

        .qr-box img {
            width: 16mm;
            height: 16mm;
        }

        /* Bloquea selección accidental */
        .proceso,
        .proceso * {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        /* Impresión */
        @media print {
            .etiqueta-card {
                width: 105mm !important;
                height: 59.4mm !important;
                margin: 0;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }

            .etiqueta-id-web-only {
                display: none !important;
            }
        }
    </style>
    <script>
        window.etiquetasConElementos = @json($etiquetasJson);
    </script>
    <script>
        // Función para renderizar SVG de etiqueta usando el código ya cargado de canvasMaquina.js
        function renderizarSVGEtiqueta(etiquetaId, grupo) {
            const contenedor = document.getElementById(`contenedor-svg-${etiquetaId}`);
            if (!contenedor || !grupo.elementos || grupo.elementos.length === 0) {
                console.log('No hay elementos para renderizar');
                return;
            }

            // Actualizar window.elementosAgrupadosScript temporalmente
            const elementosAgrupadosOriginal = window.elementosAgrupadosScript;
            window.elementosAgrupadosScript = [grupo];

            // Disparar manualmente un evento DOMContentLoaded falso
            // para que canvasMaquina.js procese el nuevo contenedor
            const event = new Event('DOMContentLoaded');
            document.dispatchEvent(event);

            // Restaurar después de un momento
            setTimeout(() => {
                window.elementosAgrupadosScript = elementosAgrupadosOriginal;
            }, 200);
        }

        function mostrar(etiquetaId) {
            const datos = window.etiquetasConElementos[etiquetaId];
            if (!datos) return;

            const subId = datos.etiqueta_sub_id ?? 'N/A';
            const safeSubId = subId.replace(/\./g, '-');
            const nombre = datos.nombre ?? 'Sin nombre';
            const peso = datos.peso_kg ?? 'N/A';
            const cliente = datos.planilla?.cliente?.empresa ?? 'Sin cliente';
            const obra = datos.planilla?.obra?.obra ?? 'Sin obra';
            const planillaCod = datos.planilla?.codigo_limpio ?? 'N/A';
            const seccion = datos.planilla?.seccion ?? '';
            const estado = (datos.estado ?? 'pendiente').toLowerCase();

            const html = `
                <div class="etiqueta-wrapper">
                    <div class="etiqueta-id-web-only">${subId}</div>

                    <div class="etiqueta-card proceso estado-${estado}" id="etiqueta-${safeSubId}" data-estado="${estado}">
                        <!-- Botón de imprimir -->
                        <div class="relative">
                            <div class="absolute top-2 right-20 flex items-center gap-2 no-print">
                                <select id="modo-impresion-${etiquetaId}" class="border border-gray-300 rounded px-2 py-1 text-sm">
                                    <option value="a6">A6</option>
                                    <option value="a4">A4</option>
                                </select>

                                <button type="button" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"
                                    onclick="const modo = document.getElementById('modo-impresion-${etiquetaId}').value; imprimirEtiquetas(['${subId}'], modo)">
                                    🖨️
                                </button>
                            </div>
                        </div>

                        <!-- Contenido -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                ${obra} - ${cliente}<br>
                                ${planillaCod} - S:${seccion}
                            </h2>
                            <h3 class="text-lg font-semibold text-gray-900">
                                ${nombre} - Cal:B500SD - ${peso} kg
                            </h3>
                        </div>

                        <!-- SVG Container -->
                        <div id="contenedor-svg-${etiquetaId}" class="w-full flex-1"></div>

                        <!-- Canvas oculto para impresión -->
                        <div style="width:100%;border-top:1px solid black;visibility:hidden;height:0;">
                            <canvas id="canvas-imprimir-etiqueta-${subId}"></canvas>
                        </div>
                    </div>
                </div>
            `;

            const content = document.getElementById('modalContent');
            content.innerHTML = html;

            const modal = document.getElementById('modalEtiqueta');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Preparar datos para renderizar con el sistema existente
            const grupoEtiqueta = {
                id: etiquetaId,
                etiqueta: {
                    id: etiquetaId,
                    etiqueta_sub_id: subId,
                    nombre: nombre,
                    peso_kg: peso,
                    estado: estado
                },
                elementos: datos.elementos || []
            };

            // Esperar a que el DOM se actualice y renderizar el SVG
            setTimeout(() => {
                renderizarSVGEtiqueta(etiquetaId, grupoEtiqueta);
            }, 50);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const modalClose = document.getElementById('modalClose');
            const modalEtiqueta = document.getElementById('modalEtiqueta');

            if (modalClose) {
                modalClose.addEventListener('click', cerrarModal);
            }

            if (modalEtiqueta) {
                modalEtiqueta.addEventListener('click', e => {
                    if (e.target === e.currentTarget) cerrarModal();
                });
            }
        });

        function cerrarModal() {
            const modal = document.getElementById('modalEtiqueta');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
    <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}"></script>
    <script src="{{ asset('js/maquinaJS/canvasMaquinaSinBoton.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        const domSafe = (v) => String(v).replace(/[^A-Za-z0-9_-]/g, '-');

        async function imprimirEtiquetas(ids, modo = 'a6') {
            if (!Array.isArray(ids)) ids = [ids];
            const etiquetasHtml = [];

            for (const rawId of ids) {
                const safeId = domSafe(rawId);
                let contenedor = document.getElementById(`etiqueta-${safeId}`) ||
                    document.getElementById(`etiqueta-${rawId}`);
                if (!contenedor) continue;

                // Buscar canvas
                let canvas = document.getElementById(`canvas-imprimir-etiqueta-${safeId}`) ||
                    document.getElementById(`canvas-imprimir-etiqueta-${rawId}`) ||
                    contenedor.querySelector('canvas');

                // Renderizar a imagen
                let canvasImg = null;
                if (canvas && (canvas.width || canvas.height)) {
                    const scale = 2;
                    const tmp = document.createElement('canvas');
                    const w = canvas.width || canvas.getBoundingClientRect().width || 600;
                    const h = canvas.height || canvas.getBoundingClientRect().height || 200;
                    tmp.width = Math.max(1, Math.round(w * scale));
                    tmp.height = Math.max(1, Math.round(h * scale));
                    const ctx = tmp.getContext('2d');
                    ctx.scale(scale, scale);
                    ctx.drawImage(canvas, 0, 0);
                    canvasImg = tmp.toDataURL('image/png');
                }

                // Clonar y limpiar
                const clone = contenedor.cloneNode(true);
                clone.classList.add('etiqueta-print');
                clone.querySelectorAll('.no-print').forEach(el => el.remove());

                // Reemplazar canvas
                if (canvasImg) {
                    const targetCanvas = clone.querySelector('canvas');
                    const host = targetCanvas ? targetCanvas.parentNode : clone;
                    if (host) {
                        if (targetCanvas) targetCanvas.remove();
                        const img = new Image();
                        img.src = canvasImg;
                        img.style.width = '100%';
                        img.style.height = 'auto';
                        host.appendChild(img);
                    }
                }

                // Generar QR
                const tempQR = document.createElement('div');
                document.body.appendChild(tempQR);
                await new Promise(res => {
                    new QRCode(tempQR, {
                        text: String(rawId),
                        width: 50,
                        height: 50
                    });
                    setTimeout(() => {
                        const qrImg = tempQR.querySelector('img');
                        const qrCanvas = tempQR.querySelector('canvas');
                        const qrNode = qrImg || (qrCanvas ? (() => {
                            const img = new Image();
                            img.src = qrCanvas.toDataURL();
                            return img;
                        })() : null);

                        if (qrNode) {
                            qrNode.classList.add('qr-print');
                            const qrBox = document.createElement('div');
                            qrBox.className = 'qr-box';
                            qrBox.appendChild(qrNode);
                            clone.insertBefore(qrBox, clone.firstChild);
                        }
                        tempQR.remove();
                        res();
                    }, 150);
                });

                etiquetasHtml.push(clone.outerHTML);
            }

            // CSS e impresión
            let css = '';
            if (modo === 'a4') {
                css = `<style>
        @page{size:A4 portrait;margin:10;}
        body{margin:0;padding:0;background:#fff;}
        .sheet-grid{
            display:grid;
            grid-template-columns:105mm 105mm;
            grid-template-rows:repeat(5,59.4mm);
            width:210mm;height:297mm;
        }
        .etiqueta-print{
            position:relative;width:105mm;height:59.4mm;
            box-sizing:border-box;border:0.2mm solid #000;
            overflow:hidden;padding:3mm;background:#fff;
            page-break-inside:avoid;
        }
        .etiqueta-print h2{font-size:10pt;margin:0;}
        .etiqueta-print h3{font-size:9pt;margin:0;}
        .etiqueta-print img:not(.qr-print){width:100%;height:auto;margin-top:2mm;}
        .qr-box{position:absolute;top:3mm;right:3mm;border:0.2mm solid #000;padding:1mm;background:#fff;}
        .qr-box img{width:16mm;height:16mm;}
        .no-print{display:none!important;}
    </style>`;
            } else if (modo === 'a6') {
                css = `<style>
  @page { size: A6 landscape; margin: 0; }

  html, body {
    margin: 0;
    padding: 0;
    background: #fff;
    width: 148mm;
    height: 105mm;
  }

  body {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .etiqueta-print {
    width: 140mm;
    height: 100mm;
    padding: 4mm;
    box-sizing: border-box;
    border: 0.2mm solid #000;
    background: #fff;
    overflow: hidden;
    position: relative;
    page-break-after: always;
  }

  .etiqueta-print h2 {
    font-size: 11pt;
    margin: 0 0 2mm 0;
    line-height: 1.3;
  }

  .etiqueta-print h3 {
    font-size: 10pt;
    margin: 0 0 2mm 0;
  }

  .etiqueta-print img:not(.qr-print) {
    width: 100%;
    height: auto;
    margin-top: 3mm;
  }

  .qr-box {
    position: absolute;
    top: 4mm;
    right: 4mm;
    border: 0.2mm solid #000;
    padding: 1mm;
    background: #fff;
  }

  .qr-box img {
    width: 20mm;
    height: 20mm;
  }

  .no-print {
    display: none !important;
  }
</style>`;
            }

            const w = window.open('', '_blank');
            w.document.open();
            w.document.write(`
          <html>
            <head><title>Impresión</title>${css}</head>
            <body>
              <div class="sheet-grid">${etiquetasHtml.join('')}</div>
              <script>
                window.onload = () => {
                  const imgs = document.images;
                  let loaded = 0, total = imgs.length;
                  if(total===0){window.print();setTimeout(()=>window.close(),500);return;}
                  for(const img of imgs){
                    if(img.complete){
                      loaded++; if(loaded===total){window.print();setTimeout(()=>window.close(),500);}
                    }else{
                      img.onload = img.onerror = () => { loaded++; if(loaded===total){window.print();setTimeout(()=>window.close(),500);} };
                    }
                  }
                };
              <\/script>
            </body>
          </html>`);
            w.document.close();
        }
    </script>
    <script>
        function guardarCambios(etiqueta) {
            fetch(`/etiquetas/${etiqueta.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(etiqueta)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        let errorMsg = data.message || "Ha ocurrido un error inesperado.";
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join("<br>");
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Error al actualizar",
                            html: errorMsg,
                            confirmButtonText: "OK",
                            showCancelButton: true,
                            cancelButtonText: "Reportar Error"
                        }).then((result) => {
                            if (result.dismiss === Swal.DismissReason.cancel) {
                                notificarProgramador(errorMsg);
                            }
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        text: "No se pudo actualizar la etiqueta. Inténtalo nuevamente.",
                        confirmButtonText: "OK"
                    });
                });
        }

        // Re-inicializar después de que Livewire actualice
        document.addEventListener('livewire:navigated', () => {
            window.etiquetasConElementos = @json($etiquetasJson);
        });
    </script>
    @endpush
</div>
