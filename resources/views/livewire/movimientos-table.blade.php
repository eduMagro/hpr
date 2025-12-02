<div>
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    <x-tabla.wrapper minWidth="1600px">
        <x-tabla.header>
            {{-- Fila de encabezados --}}
            <x-tabla.header-row>
                <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
                <x-tabla.encabezado-ordenable campo="tipo" :sortActual="$sort" :orderActual="$order" texto="Tipo" />
                <x-tabla.encabezado-ordenable campo="pedido_producto_id" :sortActual="$sort" :orderActual="$order"
                    texto="Línea Pedido" />
                <th class="p-2">Producto</th>
                <x-tabla.encabezado-ordenable campo="descripcion" :sortActual="$sort" :orderActual="$order"
                    texto="Descripción" />
                <th class="p-2">Nave</th>
                <x-tabla.encabezado-ordenable campo="prioridad" :sortActual="$sort" :orderActual="$order"
                    texto="Prioridad" />
                <th class="p-2">Solicitado por</th>
                <th class="p-2">Ejecutado por</th>
                <x-tabla.encabezado-ordenable campo="estado" :sortActual="$sort" :orderActual="$order" texto="Estado" />
                <x-tabla.encabezado-ordenable campo="fecha_solicitud" :sortActual="$sort" :orderActual="$order"
                    texto="Fecha Solicitud" />
                <x-tabla.encabezado-ordenable campo="fecha_ejecucion" :sortActual="$sort" :orderActual="$order"
                    texto="Fecha Ejecución" />
                <th class="p-2">Origen</th>
                <th class="p-2">Destino</th>
                <th class="p-2">Producto/Paquete</th>
                <th class="p-2">Acciones</th>
            </x-tabla.header-row>

            {{-- Fila de filtros --}}
            <x-tabla.filtro-row>
                <x-tabla.filtro-input model="id" placeholder="ID" />
                <x-tabla.filtro-input model="tipo" placeholder="Tipo" />
                <x-tabla.filtro-input model="pedido_producto_id" placeholder="Línea" />
                <x-tabla.filtro-producto-base />
                <x-tabla.filtro-input model="descripcion" placeholder="Descripción" />

                <x-tabla.filtro-select model="nave_id" placeholder="Todas">
                    @foreach ($naves as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </x-tabla.filtro-select>

                <x-tabla.filtro-select model="prioridad" placeholder="Todas">
                    <option value="1">Baja</option>
                    <option value="2">Media</option>
                    <option value="3">Alta</option>
                </x-tabla.filtro-select>

                <x-tabla.filtro-input model="solicitado_por" placeholder="Solicitado" />
                <x-tabla.filtro-input model="ejecutado_por" placeholder="Ejecutado" />

                <x-tabla.filtro-select model="estado" placeholder="Todos">
                    <option value="pendiente">Pendiente</option>
                    <option value="completado">Completado</option>
                    <option value="cancelado">Cancelado</option>
                </x-tabla.filtro-select>

                <x-tabla.filtro-fecha model="fecha_solicitud" />
                <x-tabla.filtro-fecha model="fecha_ejecucion" />
                <x-tabla.filtro-input model="origen" placeholder="Origen" />
                <x-tabla.filtro-input model="destino" placeholder="Destino" />
                <x-tabla.filtro-input model="producto_paquete" placeholder="Prod/Paq" />
                <x-tabla.filtro-acciones />
            </x-tabla.filtro-row>
        </x-tabla.header>

        <x-tabla.body>
            @forelse($movimientos as $movimiento)
                <x-tabla.row>
                    <x-tabla.cell>{{ $movimiento->id }}</x-tabla.cell>

                    <x-tabla.cell>{{ ucfirst($movimiento->tipo ?? 'N/A') }}</x-tabla.cell>

                    <x-tabla.cell>
                        @php $linea = $movimiento->pedido_producto_id; @endphp
                        @if ($linea)
                            <a href="{{ route('pedidos.index', ['pedido_producto_id' => $linea]) }}"
                                class="text-indigo-600 hover:underline">
                                #{{ $linea }}
                            </a>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </x-tabla.cell>

                    <x-tabla.cell>
                        @if ($movimiento->productoBase)
                            {{ ucfirst(strtolower($movimiento->productoBase->tipo)) }}
                            (Ø{{ $movimiento->productoBase->diametro }}{{ strtolower($movimiento->productoBase->tipo) === 'barra' ? ', ' . $movimiento->productoBase->longitud . ' m' : '' }})
                        @else
                            <span class="text-gray-400 italic">Sin datos</span>
                        @endif
                    </x-tabla.cell>

                    <x-tabla.cell>
                        <span title="{{ $movimiento->descripcion }}">
                            {{ Str::limit($movimiento->descripcion, 50) ?? '—' }}
                        </span>
                    </x-tabla.cell>

                    <x-tabla.cell>
                        @if ($movimiento->nave)
                            {{ $movimiento->nave->obra }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </x-tabla.cell>

                    <x-tabla.cell>
                        <x-tabla.badge-prioridad :prioridad="$movimiento->prioridad" />
                    </x-tabla.cell>

                    <x-tabla.cell>
                        @if ($movimiento->solicitadoPor)
                            <a href="{{ route('users.show', $movimiento->solicitadoPor->id) }}"
                                class="text-blue-500 hover:underline">
                                {{ $movimiento->solicitadoPor->nombre_completo }}
                            </a>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </x-tabla.cell>

                    <x-tabla.cell>
                        @if ($movimiento->ejecutadoPor)
                            <a href="{{ route('users.show', $movimiento->ejecutadoPor->id) }}"
                                class="text-green-600 hover:underline">
                                {{ $movimiento->ejecutadoPor->nombre_completo }}
                            </a>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </x-tabla.cell>

                    <x-tabla.cell>
                        <x-tabla.badge-estado :estado="$movimiento->estado" />
                    </x-tabla.cell>

                    <x-tabla.cell>{{ $movimiento->fecha_solicitud ?? '—' }}</x-tabla.cell>

                    <x-tabla.cell>{{ $movimiento->fecha_ejecucion ?? '—' }}</x-tabla.cell>

                    <x-tabla.cell>
                        {{ $movimiento->ubicacionOrigen->nombre ?? ($movimiento->maquinaOrigen->nombre ?? '—') }}
                    </x-tabla.cell>

                    <x-tabla.cell>
                        {{ $movimiento->ubicacionDestino->nombre ?? ($movimiento->maquinaDestino->nombre ?? '—') }}
                    </x-tabla.cell>

                    <x-tabla.cell>
                        @if ($movimiento->producto)
                            <a href="{{ route('productos.index', ['id' => $movimiento->producto->id]) }}"
                                class="text-blue-500 hover:underline">
                                {{ $movimiento->producto->codigo }}
                            </a>
                        @elseif($movimiento->paquete)
                            <a href="{{ route('paquetes.index', ['id' => $movimiento->paquete->id]) }}"
                                class="text-blue-500 hover:underline">
                                {{ $movimiento->paquete->codigo }}
                            </a>
                        @else
                            —
                        @endif
                    </x-tabla.cell>

                    <x-tabla.cell>
                        <x-tabla.boton-eliminar :action="route('movimientos.destroy', $movimiento->id)" />
                    </x-tabla.cell>
                </x-tabla.row>
            @empty
                <x-tabla.empty-state colspan="16" mensaje="No hay movimientos registrados" />
            @endforelse
        </x-tabla.body>
    </x-tabla.wrapper>

    <x-tabla.paginacion-livewire :paginador="$movimientos" />
</div>
