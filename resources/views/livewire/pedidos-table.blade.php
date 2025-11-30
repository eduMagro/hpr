<div>
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="w-full border-collapse text-sm text-center">
            <thead class="bg-blue-500 text-white text-10">
                <tr class="text-center text-xs uppercase">
                    <th class="p-2 border">Cód. Linea</th>
                    <th class="p-2 border cursor-pointer" wire:click="sortBy('codigo')">
                        Código @if($sort === 'codigo'){{ $order === 'asc' ? '↑' : '↓' }}@endif
                    </th>
                    <th class="p-2 border">Pedido Global Linea</th>
                    <th class="p-2 border">Fabricante</th>
                    <th class="p-2 border">Distribuidor</th>
                    <th class="p-2 border">Lugar de entrega</th>
                    <th class="px-2 py-2 border">Producto</th>
                    <th class="p-2 border">Cantidad Pedida</th>
                    <th class="p-2 border">Cantidad Recepcionada</th>
                    <th class="p-2 border cursor-pointer" wire:click="sortBy('fecha_pedido')">
                        F. Pedido @if($sort === 'fecha_pedido'){{ $order === 'asc' ? '↑' : '↓' }}@endif
                    </th>
                    <th class="p-2 border cursor-pointer" wire:click="sortBy('fecha_entrega')">
                        F. Entrega @if($sort === 'fecha_entrega'){{ $order === 'asc' ? '↑' : '↓' }}@endif
                    </th>
                    <th class="p-2 border cursor-pointer" wire:click="sortBy('estado')">
                        Estado @if($sort === 'estado'){{ $order === 'asc' ? '↑' : '↓' }}@endif
                    </th>
                    <th class="p-2 border">Creado por</th>
                    <th class="p-2 border">Acciones</th>
                </tr>

                <tr class="text-center text-xs uppercase">
                    <th class="p-1 border">
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="codigo_linea" placeholder="PC25/0001–001" class="w-full text-xs px-2 py-1 border rounded pr-6" />
                            <i class="fas fa-question-circle absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-help" title="Búsqueda flexible: 'PC25' / '0001–' / '=PC25/0001–001' (exacto)"></i>
                        </div>
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="codigo" class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <select wire:model.live="pedido_global_id" class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todos</option>
                            @foreach($pedidosGlobales as $pg)
                                <option value="{{ $pg->id }}">{{ $pg->codigo }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="p-1 border">
                        <select wire:model.live="fabricante_id" class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todos</option>
                            @foreach($fabricantes as $fab)
                                <option value="{{ $fab->id }}">{{ $fab->nombre }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="p-1 border">
                        <select wire:model.live="distribuidor_id" class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todos</option>
                            @foreach($distribuidores as $dist)
                                <option value="{{ $dist->id }}">{{ $dist->nombre }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="p-1 border">
                        <select wire:model.live="obra_id" class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todas</option>
                            @foreach($obras as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="py-1 px-0 border">
                        <div class="flex gap-2 justify-center">
                            <input type="text" wire:model.live.debounce.300ms="producto_tipo" placeholder="T" class="bg-white text-blue-900 border border-gray-300 rounded text-[10px] text-center w-14 h-6 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                            <input type="text" wire:model.live.debounce.300ms="producto_diametro" placeholder="Ø" class="bg-white text-blue-900 border border-gray-300 rounded text-[10px] text-center w-14 h-6 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                            <input type="text" wire:model.live.debounce.300ms="producto_longitud" placeholder="L" class="bg-white text-blue-900 border border-gray-300 rounded text-[10px] text-center w-14 h-6 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                        </div>
                    </th>
                    <th class="p-1 border"></th>
                    <th class="p-1 border"></th>
                    <th class="p-1 border">
                        <input type="date" wire:model.live.debounce.300ms="fecha_pedido" class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="date" wire:model.live.debounce.300ms="fecha_entrega" class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <select wire:model.live="estado" class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todos</option>
                            <option value="activo">Activo</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="parcial">Parcial</option>
                            <option value="completado">Completado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </th>
                    <th class="p-1 border"></th>
                    <th class="p-1 border"></th>
                </tr>
            </thead>

            <tbody>
                @forelse($pedidos as $pedido)
                    {{-- Fila principal del pedido --}}
                    <tr class="bg-gray-100 text-xs font-bold uppercase">
                        <td colspan="14" class="text-left px-3 py-2">
                            <span class="text-blue-600">Pedido:</span> {{ $pedido->codigo }}
                            |
                            <span class="text-blue-600">Peso Total: </span> {{ $pedido->peso_total_formateado }}
                            |
                            <span class="text-blue-600">Precio Ref: </span> {{ $pedido->pedidoGlobal?->precio_referencia_euro ?? 'N/A' }}
                            |
                            <span class="text-blue-600">Estado: </span>{{ $pedido->estado }}

                            <span class="float-right">
                                <x-tabla.boton-eliminar :action="route('pedidos.destroy', $pedido->id)" />
                            </span>
                        </td>
                    </tr>

                    {{-- Filas de las líneas del pedido --}}
                    @foreach($pedido->lineas as $linea)
                        @php
                            $estadoLinea = strtolower(trim($linea->estado));
                            $claseFondo = match($estadoLinea) {
                                'facturado' => 'bg-green-500',
                                'completado' => 'bg-green-100',
                                'activo' => 'bg-yellow-100',
                                'cancelado' => 'bg-gray-300 text-gray-500 opacity-70 cursor-not-allowed',
                                default => 'even:bg-gray-50 odd:bg-white',
                            };
                        @endphp

                        <tr class="text-xs {{ $claseFondo }}">
                            <td class="border px-2 py-1 text-center">
                                <div class="flex flex-col">
                                    @if($linea->codigo)
                                        @php
                                            $partes = explode('–', $linea->codigo);
                                        @endphp
                                        <div class="flex items-baseline justify-center gap-0.5">
                                            <span class="text-sm text-gray-600">{{ $partes[0] ?? '' }}</span>
                                            <span class="text-lg font-bold text-blue-600">–{{ $partes[1] ?? '' }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="border px-2 py-1 text-center align-middle">
                                <div class="inline-flex flex-col items-center gap-1">
                                    <span class="font-semibold">{{ $pedido->codigo }}</span>
                                    @if(!empty($linea->id))
                                        <a href="{{ route('entradas.index', ['pedido_producto_id' => $linea->id]) }}" class="text-blue-600 hover:underline text-[11px]">
                                            Ver entradas
                                        </a>
                                    @endif
                                </div>
                            </td>

                            <td class="border px-2 py-1">{{ $linea->pedidoGlobal?->codigo ?? '—' }}</td>
                            <td class="border px-2 py-1">{{ $pedido->fabricante?->nombre ?? '—' }}</td>
                            <td class="border px-2 py-1">{{ $pedido->distribuidor?->nombre ?? '—' }}</td>

                            {{-- CELDA DE LUGAR DE ENTREGA --}}
                            <td class="border px-2 py-1">
                                {{-- Vista normal --}}
                                <div class="lugar-entrega-view-{{ $linea->id }}">
                                    @if($linea->obra_id)
                                        {{ $linea->obra?->obra ?? '—' }}
                                    @elseif($linea->obra_manual)
                                        {{ $linea->obra_manual }}
                                    @else
                                        —
                                    @endif
                                </div>

                                {{-- Vista edición (oculta por defecto) --}}
                                <div class="lugar-entrega-edit-{{ $linea->id }} hidden">
                                    <div class="flex flex-col gap-1">
                                        <select class="obra-hpr-select text-xs border rounded px-1 py-1" data-linea-id="{{ $linea->id }}">
                                            <option value="">Nave HPR</option>
                                            <option value="sin_obra" {{ !$linea->obra_id && !$linea->obra_manual ? 'selected' : '' }}>Sin obra</option>
                                            @foreach($navesHpr as $nave)
                                                <option value="{{ $nave->id }}" {{ $linea->obra_id == $nave->id ? 'selected' : '' }}>
                                                    {{ $nave->obra }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <select class="obra-externa-select text-xs border rounded px-1 py-1" data-linea-id="{{ $linea->id }}">
                                            <option value="">Obra Externa</option>
                                            <option value="sin_obra" {{ !$linea->obra_id && !$linea->obra_manual ? 'selected' : '' }}>Sin obra</option>
                                            @foreach($obrasExternas as $obra)
                                                <option value="{{ $obra->id }}" {{ $linea->obra_id == $obra->id ? 'selected' : '' }}>
                                                    {{ $obra->obra }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <input type="text" class="obra-manual-input text-xs border rounded px-1 py-1" placeholder="Otra ubicación" value="{{ $linea->obra_manual ?? '' }}" data-linea-id="{{ $linea->id }}">
                                    </div>
                                </div>
                            </td>

                            {{-- CELDA DE PRODUCTO --}}
                            <td class="border px-2 py-1 text-center">
                                {{-- Vista normal --}}
                                <div class="producto-view-{{ $linea->id }}">
                                    {{ ucfirst($linea->tipo) }}
                                    Ø{{ $linea->diametro }}
                                    @if($linea->tipo === 'barra' && $linea->longitud && $linea->longitud !== '—')
                                        x {{ $linea->longitud }} m
                                    @endif
                                </div>

                                {{-- Vista edición (oculta por defecto) --}}
                                <div class="producto-edit-{{ $linea->id }} hidden">
                                    <select class="producto-base-select text-xs border rounded px-1 py-1 w-full" data-linea-id="{{ $linea->id }}">
                                        <option value="">Seleccionar producto</option>
                                        @php
                                            $productosAgrupados = $productosBase->groupBy('tipo')->sortKeys();
                                        @endphp
                                        @foreach($productosAgrupados as $tipo => $productos)
                                            <optgroup label="{{ strtoupper($tipo) }}">
                                                @foreach($productos->sortBy('diametro') as $producto)
                                                    <option value="{{ $producto->id }}" data-tipo="{{ $producto->tipo }}" data-diametro="{{ $producto->diametro }}" data-longitud="{{ $producto->longitud ?? '' }}" {{ $linea->producto_base_id == $producto->id ? 'selected' : '' }}>
                                                        Ø{{ $producto->diametro }}
                                                        @if($producto->longitud)
                                                            x {{ $producto->longitud }}m
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </td>

                            {{-- CELDA DE CANTIDAD PEDIDA --}}
                            <td class="border px-2 py-1 text-center">
                                {{ number_format($linea->cantidad ?? 0, 2, ',', '.') }} kg
                            </td>

                            {{-- CELDA DE CANTIDAD RECEPCIONADA --}}
                            <td class="border px-2 py-1 text-center">
                                <span class="font-semibold {{ ($linea->cantidad_recepcionada ?? 0) >= ($linea->cantidad ?? 0) ? 'text-green-600' : 'text-gray-700' }}">
                                    {{ number_format($linea->cantidad_recepcionada ?? 0, 2, ',', '.') }} kg
                                </span>
                            </td>

                            {{-- FECHA PEDIDO --}}
                            <td class="border px-2 py-1 text-center">
                                {{ $pedido->fecha_pedido_formateada ?? '—' }}
                            </td>

                            {{-- FECHA ENTREGA --}}
                            <td class="border px-2 py-1 text-center">
                                {{ $linea->fecha_estimada_entrega_formateada ?? '—' }}
                            </td>

                            {{-- ESTADO LINEA --}}
                            <td class="border px-2 py-1 text-center capitalize">{{ $linea->estado }}</td>

                            {{-- CREADO POR --}}
                            <td class="border px-2 py-1 text-center capitalize">
                                {{ $pedido->creador->name ?? '—' }}
                            </td>

                            {{-- COLUMNA DE ACCIONES --}}
                            <td class="border px-2 py-1 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    @php
                                        $estado = strtolower(trim($linea->estado));
                                        $esCancelado = $estado === 'cancelado';
                                        $esCompletado = $estado === 'completado';
                                        $esFacturado = $estado === 'facturado';

                                        $obraLinea = $linea->obra;
                                        $tieneObraManual = !empty($linea->obra_manual);
                                        $esEntregaDirecta = $tieneObraManual || ($obraLinea ? !$obraLinea->es_nave_paco_reyes : false);
                                        $esAlmacen = $obraLinea ? stripos($obraLinea->obra, 'Almacén') !== false : false;
                                        $esNaveA = $obraLinea ? stripos($obraLinea->obra, 'Nave A') !== false : false;
                                        $esNaveB = $obraLinea ? stripos($obraLinea->obra, 'Nave B') !== false : false;
                                        $esNaveValida = $esNaveA || $esNaveB;
                                        $pedidoCompletado = strtolower($pedido->estado) === 'completado';
                                    @endphp

                                    <div class="flex items-center justify-center gap-1 flex-wrap" @if($esCancelado) style="pointer-events:none;opacity:.5" @endif>
                                        @if($esCompletado || $esFacturado)
                                            {{-- Sin acciones para líneas cerradas --}}
                                        @elseif($esCancelado)
                                            <button disabled class="bg-gray-400 text-white text-xs px-2 py-1 rounded shadow opacity-50 cursor-not-allowed">
                                                Cancelado
                                            </button>
                                        @else
                                            {{-- BOTONES DE ESTADO DE LÍNEA --}}
                                            <div class="botones-estado-{{ $linea->id }} flex items-center gap-1 flex-nowrap">
                                                {{-- BOTÓN COMPLETAR (Entrega directa) --}}
                                                @if(($esEntregaDirecta || $esAlmacen) && !$pedidoCompletado)
                                                    <form method="POST" action="{{ route('pedidos.editarCompletarLineaManual', ['pedido' => $pedido->id, 'linea' => $linea['id']]) }}" onsubmit="return confirmarCompletarLinea(this);">
                                                        @csrf
                                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded shadow transition">
                                                            Completar
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- BOTÓN DESACTIVAR --}}
                                                @if($estado === 'activo')
                                                    <form method="POST" action="{{ route('pedidos.lineas.editarDesactivar', [$pedido->id, $linea['id']]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" title="Desactivar línea" class="bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded shadow transition">
                                                            Desactivar
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- BOTÓN ACTIVAR --}}
                                                @if(($estado === 'pendiente' || $estado === 'parcial') && $esNaveValida)
                                                    @php
                                                        $clienteId = $linea->obra && $linea->obra->cliente ? $linea->obra->cliente->id : 0;
                                                    @endphp
                                                    <form method="POST"
                                                        action="{{ route('pedidos.lineas.editarActivar', [$pedido->id, $linea['id']]) }}"
                                                        class="form-activar-linea"
                                                        data-cliente-id="{{ $clienteId }}"
                                                        data-pedido-id="{{ $pedido->id }}"
                                                        data-linea-id="{{ $linea['id'] }}"
                                                        wire:ignore>
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" title="Activar línea" class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-2 py-1 rounded shadow transition btn-activar-linea">
                                                            Activar
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- BOTÓN CANCELAR LÍNEA (oculto) --}}
                                                <form method="POST" action="{{ route('pedidos.lineas.editarCancelar', [$pedido->id, $linea['id']]) }}" class="form-cancelar-linea hidden" data-pedido-id="{{ $pedido->id }}" data-linea-id="{{ $linea['id'] }}">
                                                    @csrf
                                                    @method('PUT')
                                                </form>

                                                <button type="button" onclick="confirmarCancelacionLinea({{ $pedido->id }}, {{ $linea['id'] }})" class="bg-gray-500 hover:bg-gray-600 text-white text-xs px-2 py-1 rounded shadow transition">
                                                    Cancelar
                                                </button>

                                                {{-- BOTONES DE EDICIÓN --}}
                                                <button type="button" onclick="abrirEdicionLinea({{ $linea->id }})" class="btn-editar-linea-{{ $linea->id }} bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded shadow transition" title="Editar línea">
                                                    ✏️
                                                </button>

                                                <button type="button" onclick="guardarLinea({{ $linea->id }}, {{ $pedido->id }})" class="btn-guardar-linea-{{ $linea->id }} hidden bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded shadow transition" title="Guardar cambios">
                                                    💾
                                                </button>

                                                <button type="button" onclick="cancelarEdicionLinea({{ $linea->id }})" class="btn-cancelar-edicion-{{ $linea->id }} hidden bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded shadow transition" title="Cancelar edición">
                                                    ✖️
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="14" class="text-center py-4 text-gray-500">No hay pedidos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    {{ $pedidos->links() }}
</div>

{{-- SCRIPTS JAVASCRIPT --}}
@push('scripts')
<script>
function abrirEdicionLinea(lineaId) {
    // Ocultar vista normal y mostrar edición
    document.querySelector(`.lugar-entrega-view-${lineaId}`).classList.add('hidden');
    document.querySelector(`.lugar-entrega-edit-${lineaId}`).classList.remove('hidden');
    document.querySelector(`.producto-view-${lineaId}`).classList.add('hidden');
    document.querySelector(`.producto-edit-${lineaId}`).classList.remove('hidden');

    // Ocultar botones de estado y mostrar botones de edición
    document.querySelector(`.botones-estado-${lineaId}`).classList.add('hidden');
    document.querySelector(`.btn-editar-linea-${lineaId}`).classList.add('hidden');
    document.querySelector(`.btn-guardar-linea-${lineaId}`).classList.remove('hidden');
    document.querySelector(`.btn-cancelar-edicion-${lineaId}`).classList.remove('hidden');

    // Configurar listeners para limpiar otras opciones cuando se selecciona una
    const obraHprSelect = document.querySelector(`.obra-hpr-select[data-linea-id="${lineaId}"]`);
    const obraExternaSelect = document.querySelector(`.obra-externa-select[data-linea-id="${lineaId}"]`);
    const obraManualInput = document.querySelector(`.obra-manual-input[data-linea-id="${lineaId}"]`);

    // Limpiar listeners previos clonando y reemplazando
    const newObraHpr = obraHprSelect.cloneNode(true);
    const newObraExterna = obraExternaSelect.cloneNode(true);
    const newObraManual = obraManualInput.cloneNode(true);

    obraHprSelect.parentNode.replaceChild(newObraHpr, obraHprSelect);
    obraExternaSelect.parentNode.replaceChild(newObraExterna, obraExternaSelect);
    obraManualInput.parentNode.replaceChild(newObraManual, obraManualInput);

    // Agregar nuevos listeners
    newObraHpr.addEventListener('change', function() {
        if (this.value) {
            newObraExterna.value = '';
            newObraManual.value = '';
        }
    });

    newObraExterna.addEventListener('change', function() {
        if (this.value) {
            newObraHpr.value = '';
            newObraManual.value = '';
        }
    });

    newObraManual.addEventListener('input', function() {
        if (this.value.trim()) {
            newObraHpr.value = '';
            newObraExterna.value = '';
        }
    });
}

function cancelarEdicionLinea(lineaId) {
    // Mostrar vista normal y ocultar edición
    document.querySelector(`.lugar-entrega-view-${lineaId}`).classList.remove('hidden');
    document.querySelector(`.lugar-entrega-edit-${lineaId}`).classList.add('hidden');
    document.querySelector(`.producto-view-${lineaId}`).classList.remove('hidden');
    document.querySelector(`.producto-edit-${lineaId}`).classList.add('hidden');

    // Mostrar botones de estado y ocultar botones de edición
    document.querySelector(`.botones-estado-${lineaId}`).classList.remove('hidden');
    document.querySelector(`.btn-editar-linea-${lineaId}`).classList.remove('hidden');
    document.querySelector(`.btn-guardar-linea-${lineaId}`).classList.add('hidden');
    document.querySelector(`.btn-cancelar-edicion-${lineaId}`).classList.add('hidden');
}

async function guardarLinea(lineaId, pedidoId) {
    // Obtener valores de obra
    const obraHpr = document.querySelector(`.obra-hpr-select[data-linea-id="${lineaId}"]`).value;
    const obraExterna = document.querySelector(`.obra-externa-select[data-linea-id="${lineaId}"]`).value;
    const obraManual = document.querySelector(`.obra-manual-input[data-linea-id="${lineaId}"]`).value.trim();

    // Obtener valor de producto
    const productoBase = document.querySelector(`.producto-base-select[data-linea-id="${lineaId}"]`);
    const productoBaseId = productoBase ? productoBase.value : '';

    // Validar que se haya seleccionado un producto
    if (!productoBaseId) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debes seleccionar un producto'
            });
        } else {
            alert('Debes seleccionar un producto');
        }
        return;
    }

    // Determinar qué obra usar
    let obraId = null;
    let obraManualFinal = null;

    if (obraHpr && obraHpr !== 'sin_obra') {
        obraId = obraHpr;
    } else if (obraExterna && obraExterna !== 'sin_obra') {
        obraId = obraExterna;
    } else if (obraManual) {
        obraManualFinal = obraManual;
    }
    // Si ambos selects tienen "sin_obra" o están vacíos, obraId y obraManualFinal quedan null

    // Preparar datos para enviar como JSON
    const datos = {
        linea_id: lineaId,
        obra_id: obraId,
        obra_manual: obraManualFinal,
        producto_base_id: productoBaseId
    };

    try {
        const response = await fetch(`/pedidos/${pedidoId}/actualizar-linea`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(datos)
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Cancelar el modo edición
            cancelarEdicionLinea(lineaId);

            // Refrescar el componente Livewire
            Livewire.dispatch('$refresh');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Guardado!',
                    text: 'Línea actualizada correctamente',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al guardar la línea'
                });
            } else {
                alert(data.message || 'Error al guardar la línea');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión al guardar'
            });
        } else {
            alert('Error de conexión al guardar');
        }
    }
}

function confirmarCancelacionLinea(pedidoId, lineaId) {
    if (confirm('¿Estás seguro de que deseas cancelar esta línea?')) {
        document.querySelector(`.form-cancelar-linea[data-pedido-id="${pedidoId}"][data-linea-id="${lineaId}"]`).submit();
    }
}

function confirmarCompletarLinea(form) {
    return confirm('¿Estás seguro de que deseas completar esta línea?');
}
</script>
@endpush
