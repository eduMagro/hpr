<div>
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    <div class="w-full overflow-x-auto bg-white shadow-md rounded-lg">
        <table class="min-w-full table-auto">
            <thead class="bg-blue-500 text-white text-10">
                <tr class="text-center text-xs uppercase">
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
                    <th class="p-2 border">Acciones</th>
                </tr>

                <tr class="text-center text-xs uppercase">
                    <th class="p-2 border">
                        <input type="text" wire:model.live.debounce.300ms="id" placeholder="ID" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="p-2 border">
                        <input type="text" wire:model.live.debounce.300ms="tipo" placeholder="Tipo" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="p-2 border">
                        <input type="text" wire:model.live.debounce.300ms="pedido_producto_id" placeholder="Línea" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="py-2 px-0 border">
                        <div class="flex gap-2 justify-center">
                            <input type="text" wire:model.live.debounce.300ms="producto_tipo" placeholder="T" class="bg-white text-blue-900 border border-gray-300 rounded text-[10px] text-center w-14 h-7 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                            <input type="text" wire:model.live.debounce.300ms="producto_diametro" placeholder="Ø" class="bg-white text-blue-900 border border-gray-300 rounded text-[10px] text-center w-14 h-7 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                            <input type="text" wire:model.live.debounce.300ms="producto_longitud" placeholder="L" class="bg-white text-blue-900 border border-gray-300 rounded text-[10px] text-center w-14 h-7 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                        </div>
                    </th>
                    <th class="p-2 border">
                        <input type="text" wire:model.live.debounce.300ms="descripcion" placeholder="Descripción" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="p-2 border">
                        <select wire:model.live="nave_id" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todas</option>
                            @foreach($naves as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="p-2 border">
                        <select wire:model.live="prioridad" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todas</option>
                            <option value="1">Baja</option>
                            <option value="2">Media</option>
                            <option value="3">Alta</option>
                        </select>
                    </th>
                    <th class="p-2 border">
                        <input type="text" wire:model.live.debounce.300ms="solicitado_por" placeholder="Solicitado" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="p-2 border">
                        <input type="text" wire:model.live.debounce.300ms="ejecutado_por" placeholder="Ejecutado" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="p-2 border">
                        <select wire:model.live="estado" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="completado">Completado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </th>
                    <th class="p-2 border">
                        <input type="date" wire:model.live.debounce.300ms="fecha_solicitud" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="p-2 border">
                        <input type="date" wire:model.live.debounce.300ms="fecha_ejecucion" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="p-2 border">
                        <input type="text" wire:model.live.debounce.300ms="origen" placeholder="Origen" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="p-2 border">
                        <input type="text" wire:model.live.debounce.300ms="destino" placeholder="Destino" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="p-2 border">
                        <input type="text" wire:model.live.debounce.300ms="producto_paquete" placeholder="Prod/Paq" class="w-full text-xs px-2 py-1.5 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                    </th>
                    <th class="p-2 border text-center align-middle">
                        <button wire:click="limpiarFiltros"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                            title="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                            </svg>
                        </button>
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse($movimientos as $movimiento)
                    <tr wire:key="movimiento-{{ $movimiento->id }}"
                        class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 text-xs leading-none">
                        <td class="px-2 py-4 text-center border">{{ $movimiento->id }}</td>

                        <td class="px-6 py-4 text-center border">{{ ucfirst($movimiento->tipo ?? 'N/A') }}</td>

                        <td class="px-6 py-4 text-center border">
                            @php $linea = $movimiento->pedido_producto_id; @endphp
                            @if($linea)
                                <a href="{{ route('pedidos.index', ['pedido_producto_id' => $linea]) }}" class="text-indigo-600 hover:underline">
                                    #{{ $linea }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->productoBase)
                                {{ ucfirst(strtolower($movimiento->productoBase->tipo)) }}
                                (Ø{{ $movimiento->productoBase->diametro }}{{ strtolower($movimiento->productoBase->tipo) === 'barra' ? ', ' . $movimiento->productoBase->longitud . ' m' : '' }})
                            @else
                                <span class="text-gray-400 italic">Sin datos</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border" title="{{ $movimiento->descripcion }}">
                            {{ Str::limit($movimiento->descripcion, 50) ?? '—' }}
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->nave)
                                {{ $movimiento->nave->obra }}
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->prioridad == 1)
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-200 text-gray-800">Baja</span>
                            @elseif($movimiento->prioridad == 2)
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-yellow-200 text-yellow-800">Media</span>
                            @elseif($movimiento->prioridad == 3)
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-red-200 text-red-800">Alta</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->solicitadoPor)
                                <a href="{{ route('users.show', $movimiento->solicitadoPor->id) }}" class="text-blue-500 hover:underline">
                                    {{ $movimiento->solicitadoPor->nombre_completo }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            @if($movimiento->ejecutadoPor)
                                <a href="{{ route('users.show', $movimiento->ejecutadoPor->id) }}" class="text-green-600 hover:underline">
                                    {{ $movimiento->ejecutadoPor->nombre_completo }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $movimiento->estado === 'pendiente' ? 'bg-yellow-200 text-yellow-800' : ($movimiento->estado === 'completado' ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-800') }}">
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
                                <a href="{{ route('productos.index', ['id' => $movimiento->producto->id]) }}" class="text-blue-500 hover:underline">
                                    {{ $movimiento->producto->codigo }}
                                </a>
                            @elseif($movimiento->paquete)
                                <a href="{{ route('paquetes.index', ['id' => $movimiento->paquete->id]) }}" class="text-blue-500 hover:underline">
                                    {{ $movimiento->paquete->codigo }}
                                </a>
                            @else
                                —
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center border">
                            <x-tabla.boton-eliminar :action="route('movimientos.destroy', $movimiento->id)" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center py-4 text-gray-500">No hay movimientos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <x-tabla.paginacion-livewire :paginador="$movimientos" />
</div>
