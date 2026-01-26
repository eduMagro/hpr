<div>
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    <div class="w-full overflow-x-auto bg-white dark:bg-gray-900 shadow-md rounded-lg">
        <table class="table-global min-w-full">
            <thead>
                <tr class="text-center">
                    <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
                    <x-tabla.encabezado-ordenable campo="tipo" :sortActual="$sort" :orderActual="$order" texto="Tipo" />
                    <x-tabla.encabezado-ordenable campo="pedido_producto_id" :sortActual="$sort" :orderActual="$order" texto="Línea Pedido" />
                    <x-tabla.encabezado-ordenable campo="producto_base" :sortActual="$sort" :orderActual="$order" texto="Producto" />
                    <x-tabla.encabezado-ordenable campo="descripcion" :sortActual="$sort" :orderActual="$order" texto="Descripción" />
                    <x-tabla.encabezado-ordenable campo="nave_id" :sortActual="$sort" :orderActual="$order" texto="Nave" />
                    <x-tabla.encabezado-ordenable campo="prioridad" :sortActual="$sort" :orderActual="$order" texto="Prioridad" />
                    <x-tabla.encabezado-ordenable campo="solicitado_por" :sortActual="$sort" :orderActual="$order" texto="Solicitado" />
                    <x-tabla.encabezado-ordenable campo="ejecutado_por" :sortActual="$sort" :orderActual="$order" texto="Ejecutado" />
                    <x-tabla.encabezado-ordenable campo="estado" :sortActual="$sort" :orderActual="$order" texto="Estado" />
                    <x-tabla.encabezado-ordenable campo="fecha_solicitud" :sortActual="$sort" :orderActual="$order" texto="F. Solicitud" />
                    <x-tabla.encabezado-ordenable campo="fecha_ejecucion" :sortActual="$sort" :orderActual="$order" texto="F. Ejecución" />
                    <x-tabla.encabezado-ordenable campo="origen" :sortActual="$sort" :orderActual="$order" texto="Origen" />
                    <x-tabla.encabezado-ordenable campo="destino" :sortActual="$sort" :orderActual="$order" texto="Destino" />
                    <x-tabla.encabezado-ordenable campo="producto_paquete" :sortActual="$sort" :orderActual="$order" texto="Prod/Paq" />
                    <th>Acciones</th>
                </tr>

                <x-tabla.filtro-row>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="id" placeholder="ID" class="inline-edit-input">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="tipo" placeholder="Tipo" class="inline-edit-input">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="pedido_producto_id" placeholder="Línea" class="inline-edit-input">
                    </th>
                    <th class="px-0">
                        <div class="flex gap-1 justify-center">
                            <input type="text" wire:model.live.debounce.300ms="producto_tipo" placeholder="T" class="inline-edit-input !w-12 text-center" />
                            <input type="text" wire:model.live.debounce.300ms="producto_diametro" placeholder="Ø" class="inline-edit-input !w-12 text-center" />
                            <input type="text" wire:model.live.debounce.300ms="producto_longitud" placeholder="L" class="inline-edit-input !w-12 text-center" />
                        </div>
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="descripcion" placeholder="Descripción" class="inline-edit-input">
                    </th>
                    <th>
                        <select wire:model.live="nave_id" class="inline-edit-select">
                            <option value="">Todas</option>
                            @foreach($naves as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th>
                        <select wire:model.live="prioridad" class="inline-edit-select">
                            <option value="">Todas</option>
                            <option value="1">Baja</option>
                            <option value="2">Media</option>
                            <option value="3">Alta</option>
                        </select>
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="solicitado_por" placeholder="Solicitado" class="inline-edit-input">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="ejecutado_por" placeholder="Ejecutado" class="inline-edit-input">
                    </th>
                    <th>
                        <select wire:model.live="estado" class="inline-edit-select">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="completado">Completado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </th>
                    <th>
                        <input type="date" wire:model.live.debounce.300ms="fecha_solicitud" class="inline-edit-input">
                    </th>
                    <th>
                        <input type="date" wire:model.live.debounce.300ms="fecha_ejecucion" class="inline-edit-input">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="origen" placeholder="Origen" class="inline-edit-input">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="destino" placeholder="Destino" class="inline-edit-input">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="producto_paquete" placeholder="Prod/Paq" class="inline-edit-input">
                    </th>
                    <th class="text-center align-middle">
                        <button wire:click="limpiarFiltros"
                            class="table-btn bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700 text-white"
                            title="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                            </svg>
                        </button>
                    </th>
                </x-tabla.filtro-row>
            </thead>

            <tbody>
                @forelse($movimientos as $movimiento)
                    <x-tabla.row wire:key="movimiento-{{ $movimiento->id }}">
                        <td class="px-2 py-4 text-center border">{{ $movimiento->id }}</td>

                        <td class="px-6 py-4 text-center border">{{ ucfirst($movimiento->tipo ?? 'N/A') }}</td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->pedidoProducto)
                                <a href="{{ route('pedidos.index', ['codigo_linea' => '=' . $movimiento->pedidoProducto->codigo]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $movimiento->pedidoProducto->codigo }}
                                </a>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->productoBase)
                                {{ ucfirst(strtolower($movimiento->productoBase->tipo)) }}
                                (Ø{{ $movimiento->productoBase->diametro }}{{ strtolower($movimiento->productoBase->tipo) === 'barra' ? ', ' . $movimiento->productoBase->longitud . ' m' : '' }})
                            @else
                                <span class="text-gray-400 dark:text-gray-500 italic">Sin datos</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border" title="{{ $movimiento->descripcion }}">
                            {{ Str::limit($movimiento->descripcion, 50) ?? '—' }}
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->nave)
                                {{ $movimiento->nave->obra }}
                            @else
                                <span class="text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->prioridad == 1)
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-300">Baja</span>
                            @elseif($movimiento->prioridad == 2)
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-yellow-200 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300">Media</span>
                            @elseif($movimiento->prioridad == 3)
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-red-200 dark:bg-red-900/50 text-red-800 dark:text-red-300">Alta</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->solicitadoPor)
                                <a href="{{ route('users.show', $movimiento->solicitadoPor->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $movimiento->solicitadoPor->nombre_completo }}
                                </a>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->ejecutadoPor)
                                <a href="{{ route('users.show', $movimiento->ejecutadoPor->id) }}" class="text-green-600 dark:text-green-400 hover:underline">
                                    {{ $movimiento->ejecutadoPor->nombre_completo }}
                                </a>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @php
                                $estadoClasses = match($movimiento->estado) {
                                    'pendiente' => 'bg-yellow-200 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
                                    'completado' => 'bg-green-200 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                                    default => 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-300',
                                };
                            @endphp
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $estadoClasses }}">
                                {{ ucfirst($movimiento->estado) }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center border">{{ $movimiento->fecha_solicitud ?? '—' }}</td>

                        <td class="px-6 py-4 text-center border">{{ $movimiento->fecha_ejecucion ?? '—' }}</td>

                        <td class="px-6 py-4 text-center border">
                            {{ $movimiento->ubicacionOrigen->nombre ?? ($movimiento->maquinaOrigen->nombre ?? '—') }}
                        </td>

                        <td class="px-6 py-4 text-center border">
                            {{ $movimiento->ubicacionDestino->nombre ?? ($movimiento->maquinaDestino->nombre ?? '—') }}
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->producto)
                                <a href="{{ route('productos.index', ['id' => $movimiento->producto->id]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $movimiento->producto->codigo }}
                                </a>
                            @elseif($movimiento->paquete)
                                <a href="{{ route('paquetes.index', ['id' => $movimiento->paquete->id]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $movimiento->paquete->codigo }}
                                </a>
                            @else
                                —
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            <x-tabla.boton-eliminar :action="route('movimientos.destroy', $movimiento->id)" />
                        </td>
                    </x-tabla.row>
                @empty
                    <tr>
                        <td colspan="16" class="text-center py-4 text-gray-500 dark:text-gray-400">No hay movimientos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <x-tabla.paginacion-livewire :paginador="$movimientos" />
</div>
