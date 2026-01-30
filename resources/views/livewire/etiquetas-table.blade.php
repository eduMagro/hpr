<div>
    <div class="w-full p-4 sm:p-2">
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white dark:bg-gray-900 shadow-lg rounded-lg">
            <table class="table-global w-full min-w-[1200px]">
                <thead>
                    <tr class="text-center">
                        <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
                        <x-tabla.encabezado-ordenable campo="codigo" :sortActual="$sort" :orderActual="$order" texto="Codigo" />
                        <x-tabla.encabezado-ordenable campo="etiqueta_sub_id" :sortActual="$sort" :orderActual="$order"
                            texto="Codigo SubEtiqueta" />
                        <x-tabla.encabezado-ordenable campo="codigo_planilla" :sortActual="$sort" :orderActual="$order"
                            texto="Planilla" />
                        <x-tabla.encabezado-ordenable campo="paquete" :sortActual="$sort" :orderActual="$order"
                            texto="Paquete" />
                        <th class="p-2 border">Op 1</th>
                        <th class="p-2 border">Op 2</th>
                        <th class="p-2 border">Ens 1</th>
                        <th class="p-2 border">Ens 2</th>
                        <th class="p-2 border">Sol 1</th>
                        <th class="p-2 border">Sol 2</th>
                        <x-tabla.encabezado-ordenable campo="numero_etiqueta" :sortActual="$sort" :orderActual="$order"
                            texto="Número de Etiqueta" />
                        <x-tabla.encabezado-ordenable campo="nombre" :sortActual="$sort" :orderActual="$order"
                            texto="Nombre" />
                        <th class="p-2 border">Marca</th>
                        <x-tabla.encabezado-ordenable campo="peso" :sortActual="$sort" :orderActual="$order"
                            texto="Peso (kg)" wire:navigate />
                        <x-tabla.encabezado-ordenable campo="inicio_fabricacion" :sortActual="$sort" :orderActual="$order"
                            texto="Inicio Fabricación" />
                        <x-tabla.encabezado-ordenable campo="final_fabricacion" :sortActual="$sort" :orderActual="$order"
                            texto="Final Fabricación" />
                        <x-tabla.encabezado-ordenable campo="inicio_ensamblado" :sortActual="$sort" :orderActual="$order"
                            texto="Inicio Ensamblado" />
                        <x-tabla.encabezado-ordenable campo="final_ensamblado" :sortActual="$sort" :orderActual="$order"
                            texto="Final Ensamblado" />
                        <x-tabla.encabezado-ordenable campo="inicio_soldadura" :sortActual="$sort" :orderActual="$order"
                            texto="Inicio Soldadura" />
                        <x-tabla.encabezado-ordenable campo="final_soldadura" :sortActual="$sort" :orderActual="$order"
                            texto="Final Soldadura" />
                        <x-tabla.encabezado-ordenable campo="estado" :sortActual="$sort" :orderActual="$order"
                            texto="Estado" />
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <x-tabla.filtro-row>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="etiqueta_id" placeholder="ID...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="codigo" placeholder="Código...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="etiqueta_sub_id" placeholder="SubEtiqueta...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="codigo_planilla" placeholder="Planilla...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="paquete" placeholder="Paquete...">
                        </th>
                        <th></th> {{-- Op 1 --}}
                        <th></th> {{-- Op 2 --}}
                        <th></th> {{-- Ens 1 --}}
                        <th></th> {{-- Ens 2 --}}
                        <th></th> {{-- Sol 1 --}}
                        <th></th> {{-- Sol 2 --}}
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="numero_etiqueta" placeholder="Número...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="nombre" placeholder="Nombre...">
                        </th>
                        <th></th> {{-- Marca --}}
                        <th></th> {{-- Peso --}}
                        <th>
                            <input type="date" wire:model.live.debounce.300ms="inicio_fabricacion">
                        </th>
                        <th>
                            <input type="date" wire:model.live.debounce.300ms="final_fabricacion">
                        </th>
                        <th>
                            <input type="date" wire:model.live.debounce.300ms="inicio_ensamblado">
                        </th>
                        <th>
                            <input type="date" wire:model.live.debounce.300ms="final_ensamblado">
                        </th>
                        <th>
                            <input type="date" wire:model.live.debounce.300ms="inicio_soldadura">
                        </th>
                        <th>
                            <input type="date" wire:model.live.debounce.300ms="final_soldadura">
                        </th>
                        <th>
                            <select wire:model.live="estado">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="fabricando">Fabricando</option>
                                <option value="ensamblando">Ensamblando</option>
                                <option value="soldando">Soldando</option>
                                <option value="completada">Completada</option>
                            </select>
                        </th>
                        <th class="text-center align-middle">
                            <div class="flex justify-center gap-2 items-center h-full">
                                <button type="button" wire:click="limpiarFiltros"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                    title="Restablecer filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                    </svg>
                                </button>
                            </div>
                        </th>
                    </x-tabla.filtro-row>
                </thead>

                <tbody class="text-gray-700 dark:text-gray-300">
                    @forelse ($etiquetas as $etiqueta)
                        <x-tabla.row wire:key="etiqueta-{{ $etiqueta->id }}" x-data="{
                            editando: false,
                            etiqueta: {{ Js::from($etiqueta) }},
                            original: JSON.parse(JSON.stringify({{ Js::from($etiqueta) }}))
                        }"
                            @dblclick="if(!$event.target.closest('input, select, button, a')) {
                                if(!editando) {
                                    editando = true;
                                } else {
                                    etiqueta = JSON.parse(JSON.stringify(original));
                                    editando = false;
                                }
                            }"
                            @keydown.enter.stop="if(editando) { guardarCambios(etiqueta, () => { original = JSON.parse(JSON.stringify(etiqueta)); }); editando = false; }"
                            @keydown.escape.stop="if(editando) { etiqueta = JSON.parse(JSON.stringify(original)); editando = false; }"
                            x-bind:class="{ 'editing': editando }"
                            class="uppercase">

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
                                        wire:navigate class="text-blue-500 dark:text-blue-400 hover:underline">
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
                                        wire:navigate class="text-blue-500 dark:text-blue-400 hover:underline">
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
                                        wire:navigate class="text-blue-500 dark:text-blue-400 hover:underline">
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
                                        wire:navigate class="text-blue-500 dark:text-blue-400 hover:underline">
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
                                        wire:navigate class="text-blue-500 dark:text-blue-400 hover:underline">
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
                                        wire:navigate class="text-blue-500 dark:text-blue-400 hover:underline">
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
                                    class="inline-edit-input">
                            </td>

                            <!-- Nombre (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.nombre"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="etiqueta.nombre"
                                    class="inline-edit-input">
                            </td>

                            <!-- Marca (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.marca"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="etiqueta.marca"
                                    class="inline-edit-input">
                            </td>

                            <!-- Peso (editable) -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.peso"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="etiqueta.peso"
                                    class="inline-edit-input">
                            </td>

                            <!-- Fecha Inicio Fabricación (editable) -->
                            <td class="p-2 text-center border whitespace-nowrap">
                                <template x-if="!editando">
                                    <span x-text="etiqueta.fecha_inicio ? etiqueta.fecha_inicio : ''"></span>
                                </template>
                                <input x-show="editando" type="datetime-local"
                                    :value="etiqueta.fecha_inicio ? etiqueta.fecha_inicio.replace(' ', 'T') : ''"
                                    @input="etiqueta.fecha_inicio = $event.target.value.replace('T', ' ')"
                                    class="inline-edit-input">
                            </td>

                            <!-- Fecha Finalización Fabricación (editable) -->
                            <td class="p-2 text-center border whitespace-nowrap">
                                <template x-if="!editando">
                                    <span
                                        x-text="etiqueta.fecha_finalizacion ? etiqueta.fecha_finalizacion : ''"></span>
                                </template>
                                <input x-show="editando" type="datetime-local"
                                    :value="etiqueta.fecha_finalizacion ? etiqueta.fecha_finalizacion.replace(' ', 'T') : ''"
                                    @input="etiqueta.fecha_finalizacion = $event.target.value.replace('T', ' ')"
                                    class="inline-edit-input">
                            </td>

                            <!-- Fecha Inicio Ensamblado (editable) -->
                            <td class="p-2 text-center border whitespace-nowrap">
                                <template x-if="!editando">
                                    <span
                                        x-text="etiqueta.fecha_inicio_ensamblado ? etiqueta.fecha_inicio_ensamblado : ''"></span>
                                </template>
                                <input x-show="editando" type="datetime-local"
                                    :value="etiqueta.fecha_inicio_ensamblado ? etiqueta.fecha_inicio_ensamblado.replace(' ',
                                        'T') : ''"
                                    @input="etiqueta.fecha_inicio_ensamblado = $event.target.value.replace('T', ' ')"
                                    class="inline-edit-input">
                            </td>

                            <!-- Fecha Finalización Ensamblado (editable) -->
                            <td class="p-2 text-center border whitespace-nowrap">
                                <template x-if="!editando">
                                    <span
                                        x-text="etiqueta.fecha_finalizacion_ensamblado ? etiqueta.fecha_finalizacion_ensamblado : ''"></span>
                                </template>
                                <input x-show="editando" type="datetime-local"
                                    :value="etiqueta.fecha_finalizacion_ensamblado ? etiqueta.fecha_finalizacion_ensamblado
                                        .replace(' ', 'T') : ''"
                                    @input="etiqueta.fecha_finalizacion_ensamblado = $event.target.value.replace('T', ' ')"
                                    class="inline-edit-input">
                            </td>

                            <!-- Fecha Inicio Soldadura (editable) -->
                            <td class="p-2 text-center border whitespace-nowrap">
                                <template x-if="!editando">
                                    <span
                                        x-text="etiqueta.fecha_inicio_soldadura ? etiqueta.fecha_inicio_soldadura : ''"></span>
                                </template>
                                <input x-show="editando" type="datetime-local"
                                    :value="etiqueta.fecha_inicio_soldadura ? etiqueta.fecha_inicio_soldadura.replace(' ',
                                        'T') : ''"
                                    @input="etiqueta.fecha_inicio_soldadura = $event.target.value.replace('T', ' ')"
                                    class="inline-edit-input">
                            </td>

                            <!-- Fecha Finalización Soldadura (editable) -->
                            <td class="p-2 text-center border whitespace-nowrap">
                                <template x-if="!editando">
                                    <span
                                        x-text="etiqueta.fecha_finalizacion_soldadura ? etiqueta.fecha_finalizacion_soldadura : ''"></span>
                                </template>
                                <input x-show="editando" type="datetime-local"
                                    :value="etiqueta.fecha_finalizacion_soldadura ? etiqueta.fecha_finalizacion_soldadura
                                        .replace(' ', 'T') : ''"
                                    @input="etiqueta.fecha_finalizacion_soldadura = $event.target.value.replace('T', ' ')"
                                    class="inline-edit-input">
                            </td>

                            <!-- Estado (editable mediante select) - Muestra estado/estado2 si tiene maquina_id_2 -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="(etiqueta.estado ? etiqueta.estado.charAt(0).toUpperCase() + etiqueta.estado.slice(1) : 'Pendiente') + (etiqueta.estado2 ? '/' + etiqueta.estado2.charAt(0).toUpperCase() + etiqueta.estado2.slice(1) : '')"></span>
                                </template>
                                <div x-show="editando" class="flex flex-col gap-1">
                                    <select x-model="etiqueta.estado"
                                        class="inline-edit-input">
                                        <option value="pendiente">Pendiente</option>
                                        <option value="fabricando">Fabricando</option>
                                        <option value="fabricada">Fabricada</option>
                                        <option value="ensamblando">Ensamblando</option>
                                        <option value="soldando">Soldando</option>
                                        <option value="completada">Completada</option>
                                    </select>
                                    <template x-if="etiqueta.estado2 !== null && etiqueta.estado2 !== undefined">
                                        <select x-model="etiqueta.estado2"
                                            class="inline-edit-input">
                                            <option value="pendiente">Pendiente</option>
                                            <option value="doblando">Doblando</option>
                                            <option value="completada">Completada</option>
                                        </select>
                                    </template>
                                </div>
                            </td>

                            <!-- Acciones (no editable) -->
                            <td class="px-2 py-2 border text-xs font-bold">
                                <div class="flex items-center space-x-2 justify-center">
                                    {{-- Botones visibles solo en edición --}}
                                    <button x-show="editando" @click="guardarCambios(etiqueta, () => { original = JSON.parse(JSON.stringify(etiqueta)); }); editando = false"
                                        class="w-6 h-6 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-800 flex items-center justify-center"
                                        title="Guardar cambios">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button x-show="editando"
                                        @click="etiqueta = JSON.parse(JSON.stringify(original)); editando = false"
                                        class="w-6 h-6 bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-800 flex items-center justify-center"
                                        title="Cancelar edición">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>

                                    {{-- Botones normales --}}
                                    <template x-if="!editando">
                                        <div class="flex items-center space-x-2">
                                            <button @click="editando = true"
                                                class="w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded hover:bg-blue-200 dark:hover:bg-blue-800 flex items-center justify-center"
                                                title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button @click="mostrar({{ $etiqueta->id }})" wire:navigate
                                                class="w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded hover:bg-blue-200 dark:hover:bg-blue-800 flex items-center justify-center"
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

                                            {{-- Ver elementos --}}
                                            <a href="{{ route('elementos.index', ['subetiqueta' => $etiqueta->etiqueta_sub_id]) }}"
                                                class="w-6 h-6 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-800 flex items-center justify-center"
                                                title="Ver elementos">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                                </svg>
                                            </a>

                                            {{-- Eliminar --}}
                                            <form action="{{ route('etiquetas.destroy', $etiqueta->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('¿Eliminar etiqueta {{ $etiqueta->codigo }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="w-6 h-6 bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-800 flex items-center justify-center"
                                                    title="Eliminar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </x-tabla.row>
                    @empty
                        <x-tabla.row :clickable="false">
                            <td colspan="23" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                No hay etiquetas registradas
                            </td>
                        </x-tabla.row>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación Livewire -->
        <x-tabla.paginacion-livewire :paginador="$etiquetas" />

        <!-- Modal para mostrar etiqueta -->
        <div id="modalEtiqueta" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50"
            wire:ignore>

            <div class="relative bg-white dark:bg-gray-800 p-4 rounded-lg max-w-4xl">
                <!-- Botón de cierre -->
                <button onclick="cerrarModal()" aria-label="Cerrar" id="modalClose"
                    class="absolute -top-3 -right-3 bg-white dark:bg-gray-700 border border-black dark:border-gray-500
                   rounded-full w-7 h-7 flex items-center justify-center
                   text-xl leading-none hover:bg-red-100 dark:hover:bg-red-900 dark:text-gray-200 z-10">
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
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                    stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
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
            // Función para renderizar todos los elementos de una etiqueta usando canvasMaquina.js (renderizarGrupoSVG)
            function renderizarSVGEtiqueta(etiquetaId, grupo) {
                const contenedor = document.getElementById(`contenedor-svg-${etiquetaId}`);
                if (!contenedor) return;

                const elementos = grupo.elementos || [];
                if (elementos.length === 0) {
                    contenedor.innerHTML = '<p class="text-center text-gray-500 py-4">Sin elementos</p>';
                    return;
                }

                // Usar renderizarGrupoSVG de canvasMaquina.js si está disponible
                if (typeof window.renderizarGrupoSVG === 'function') {
                    // Construir el objeto grupo en el formato esperado por renderizarGrupoSVG
                    const grupoData = {
                        id: etiquetaId,
                        etiqueta: {
                            id: etiquetaId,
                            etiqueta_sub_id: grupo.etiqueta?.etiqueta_sub_id || '',
                            nombre: grupo.etiqueta?.nombre || '',
                            peso_kg: grupo.etiqueta?.peso_kg || '',
                            estado: grupo.etiqueta?.estado || 'pendiente'
                        },
                        elementos: elementos.map(el => ({
                            id: el.id,
                            diametro: el.diametro,
                            dimensiones: el.dimensiones,
                            barras: el.barras,
                            peso: el.peso,
                            coladas: el.coladas || null
                        })),
                        colada_etiqueta: null,
                        colada_etiqueta_2: null
                    };

                    window.renderizarGrupoSVG(grupoData, etiquetaId);
                } else {
                    // Fallback: mostrar información básica
                    let html = '<div class="p-2 text-sm">';
                    elementos.forEach((el, idx) => {
                        html += `<div class="mb-1">Elemento ${idx + 1}: Ø${el.diametro || '?'} - ${el.barras || 0} barras - ${el.dimensiones || 'N/A'}</div>`;
                    });
                    html += '</div>';
                    contenedor.innerHTML = html;
                }
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
                                    onclick="onclick="const modo = document.getElementById('modo-impresion-${etiquetaId}').value; imprimirEtiquetas(['${subId}'], modo)" wire:navigate">
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



            function cerrarModal() {
                const modal = document.getElementById('modalEtiqueta');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        </script>
        <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" onerror="console.warn('figuraElemento.js no encontrado')"></script>
        <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}" onerror="console.warn('canvasMaquina.js no encontrado')"
            wire:navigate></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
            window.domSafe = window.domSafe || ((v) => String(v).replace(/[^A-Za-z0-9_-]/g, '-'));

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
            function guardarCambios(etiqueta, onSuccess) {
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
                            // Mostrar toast de éxito sin recargar
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Etiqueta actualizada',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });
                            // Ejecutar callback de éxito si existe
                            if (onSuccess) onSuccess();
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

            function initEtiquetasTablePage() {
                // Update data
                window.etiquetasConElementos = @json($etiquetasJson);

                if (document.body.dataset.etiquetasTablePageInit === 'true') return;

                const modalClose = document.getElementById('modalClose');
                const modalEtiqueta = document.getElementById('modalEtiqueta');

                if (modalClose) {
                    const newClose = modalClose.cloneNode(true);
                    modalClose.parentNode.replaceChild(newClose, modalClose);
                    newClose.addEventListener('click', cerrarModal);
                }

                if (modalEtiqueta) {
                    const newModal = modalEtiqueta.cloneNode(true);
                    modalEtiqueta.parentNode.replaceChild(newModal, modalEtiqueta);
                    newModal.addEventListener('click', e => {
                        if (e.target === e.currentTarget) cerrarModal();
                    });
                }

                document.body.dataset.etiquetasTablePageInit = 'true';
            }

            window.pageInitializers = window.pageInitializers || [];
            window.pageInitializers.push(initEtiquetasTablePage);

            document.addEventListener('livewire:navigated', initEtiquetasTablePage);
            document.addEventListener('DOMContentLoaded', initEtiquetasTablePage);
            document.addEventListener('livewire:navigating', () => {
                document.body.dataset.etiquetasTablePageInit = 'false';
            });
        </script>
    @endpush
</div>
