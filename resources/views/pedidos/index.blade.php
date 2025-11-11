<x-app-layout>
    <x-slot name="title">Pedidos - {{ config('app.name') }}</x-slot>
    <x-menu.materiales />

    <div class="px-4 py-6">
        @if (auth()->user()->rol === 'oficina')
            <!-- Tabla pedidos  -->
            <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
            <div class="overflow-x-auto bg-white shadow rounded-lg">
                <table class="w-full border-collapse text-sm text-center">
                    <thead class="bg-blue-500 text-white text-10">
                        <tr class="text-center text-xs uppercase">
                            <th class="p-2 border">Cód. Linea</th>
                            <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código' !!}</th>
                            <th class="p-2 border">{!! $ordenables['pedido_global'] ?? 'Pedido Global Linea' !!}</th>
                            <th class="p-2 border">{!! $ordenables['fabricante'] ?? 'Fabricante' !!}</th>
                            <th class="p-2 border">{!! $ordenables['distribuidor'] ?? 'Distribuidor' !!}</th>
                            <th class="p-2 border">{!! $ordenables['obra_id'] ?? 'Lugar de entrega' !!}</th>
                            <th class="px-2 py-2 border">Producto</th>
                            <th class="p-2 border">Cantidad Pedida</th>
                            <th class="p-2 border">Cantidad Recepcionada</th>
                            <th class="p-2 border">{!! $ordenables['fecha_pedido'] ?? 'F. Pedido' !!}</th>
                            <th class="p-2 border">{!! $ordenables['fecha_entrega'] ?? 'F. Entrega' !!}</th>
                            <th class="p-2 border">{!! $ordenables['estado'] ?? 'Estado' !!}</th>
                            <th class="p-2 border">{!! $ordenables['created_by'] ?? 'Creado por' !!}</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>

                        <tr class="text-center text-xs uppercase">
                            <form method="GET" action="{{ route('pedidos.index') }}">
                                <th class="p-1 border">
                                    <div class="relative">
                                        <x-tabla.input name="codigo_linea" type="text" :value="request('codigo_linea')"
                                            placeholder="PC25/0001–001" class="w-full text-xs pr-6" />
                                        <i class="fas fa-question-circle absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-help"
                                            title="Búsqueda flexible: 'PC25' / '0001–' / '=PC25/0001–001' (exacto)"></i>
                                    </div>
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="codigo" type="text" :value="request('codigo')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.select name="pedido_global_id" :options="$pedidosGlobales->pluck('codigo', 'id')" :selected="request('pedido_global_id')"
                                        empty="Todos" class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.select name="fabricante_id" :options="$fabricantes->pluck('nombre', 'id')" :selected="request('fabricante_id')"
                                        empty="Todos" class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.select name="distribuidor_id" :options="$distribuidores->pluck('nombre', 'id')" :selected="request('distribuidor_id')"
                                        empty="Todos" class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.select name="obra_id" :options="$obras" :selected="request('obra_id')" empty="Todas"
                                        class="w-full text-xs" />
                                </th>
                                <th class="py-1 px-0 border">
                                    <div class="flex gap-2 justify-center">
                                        <input type="text" name="producto_tipo"
                                            value="{{ request('producto_tipo') }}" placeholder="T"
                                            class="bg-white text-gray-800 border border-gray-300 rounded text-[10px] text-center w-14 h-6" />
                                        <input type="text" name="producto_diametro"
                                            value="{{ request('producto_diametro') }}" placeholder="Ø"
                                            class="bg-white text-gray-800 border border-gray-300 rounded text-[10px] text-center w-14 h-6" />
                                        <input type="text" name="producto_longitud"
                                            value="{{ request('producto_longitud') }}" placeholder="L"
                                            class="bg-white text-gray-800 border border-gray-300 rounded text-[10px] text-center w-14 h-6" />
                                    </div>
                                </th>
                                <th class="p-1 border"></th>
                                <th class="p-1 border"></th>
                                <th class="p-1 border">
                                    <x-tabla.input name="fecha_pedido" type="date" :value="request('fecha_pedido')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="fecha_entrega" type="date" :value="request('fecha_entrega')"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.select name="estado" :options="[
                                        'activo' => 'Activo',
                                        'pendiente' => 'Pendiente',
                                        'parcial' => 'Parcial',
                                        'completado' => 'Completado',
                                        'cancelado' => 'Cancelado',
                                    ]" :selected="request('estado')" empty="Todos"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border"></th>
                                <x-tabla.botones-filtro ruta="pedidos.index" />
                            </form>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($pedidos as $pedido)
                            {{-- Fila principal del pedido --}}
                            <tr class="bg-gray-100 text-xs font-bold uppercase">
                                <td colspan="14" class="text-left px-3 py-2">
                                    <span class="text-blue-600">Pedido:</span> {{ $pedido->codigo }}
                                    |
                                    <span class="text-blue-600">Peso Total: </span> {{ $pedido->peso_total_formateado }}
                                    |
                                    <span class="text-blue-600">Precio Ref: </span>
                                    {{ $pedido->pedidoGlobal?->precio_referencia_euro ?? 'N/A' }}
                                    |
                                    <span class="text-blue-600">Estado: </span>{{ $pedido->estado }}

                                    <span class="float-right">
                                        <x-tabla.boton-eliminar :action="route('pedidos.destroy', $pedido->id)" />
                                    </span>
                                </td>
                            </tr>

                            {{-- Filas de las líneas del pedido --}}
                            @foreach ($pedido->lineas as $linea)
                                @php
                                    $estadoLinea = strtolower(trim($linea->estado));
                                    $claseFondo = match ($estadoLinea) {
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
                                            @if ($linea->codigo)
                                                @php
                                                    $partes = explode('–', $linea->codigo);
                                                @endphp
                                                <div class="flex items-baseline justify-center gap-0.5">
                                                    <span class="text-sm text-gray-600">{{ $partes[0] ?? '' }}</span>
                                                    <span
                                                        class="text-lg font-bold text-blue-600">–{{ $partes[1] ?? '' }}</span>
                                                </div>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif

                                        </div>
                                    </td>
                                    <td class="border px-2 py-1 text-center align-middle">
                                        <div class="inline-flex flex-col items-center gap-1">
                                            <span class="font-semibold">{{ $pedido->codigo }}</span>
                                            @if (!empty($linea->id))
                                                <a href="{{ route('entradas.index', ['pedido_producto_id' => $linea->id]) }}"
                                                    class="text-blue-600 hover:underline text-[11px]">
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
                                            @if ($linea->obra_id)
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
                                                <select class="obra-hpr-select text-xs border rounded px-1 py-1"
                                                    data-linea-id="{{ $linea->id }}">
                                                    <option value="">Nave HPR</option>
                                                    @foreach ($navesHpr as $nave)
                                                        <option value="{{ $nave->id }}"
                                                            {{ $linea->obra_id == $nave->id ? 'selected' : '' }}>
                                                            {{ $nave->obra }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <select class="obra-externa-select text-xs border rounded px-1 py-1"
                                                    data-linea-id="{{ $linea->id }}">
                                                    <option value="">Obra Externa</option>
                                                    @foreach ($obrasExternas as $obra)
                                                        <option value="{{ $obra->id }}"
                                                            {{ $linea->obra_id == $obra->id ? 'selected' : '' }}>
                                                            {{ $obra->obra }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <input type="text"
                                                    class="obra-manual-input text-xs border rounded px-1 py-1"
                                                    placeholder="Otra ubicación"
                                                    value="{{ $linea->obra_manual ?? '' }}"
                                                    data-linea-id="{{ $linea->id }}">
                                            </div>
                                        </div>
                                    </td>

                                    {{-- CELDA DE PRODUCTO --}}
                                    <td class="border px-2 py-1 text-center">
                                        {{-- Vista normal --}}
                                        <div class="producto-view-{{ $linea->id }}">
                                            {{ ucfirst($linea->tipo) }}
                                            Ø{{ $linea->diametro }}
                                            @if ($linea->tipo === 'barra' && $linea->longitud && $linea->longitud !== '—')
                                                x {{ $linea->longitud }} m
                                            @endif
                                        </div>

                                        {{-- Vista edición (oculta por defecto) --}}
                                        <div class="producto-edit-{{ $linea->id }} hidden">
                                            <select
                                                class="producto-base-select text-xs border rounded px-1 py-1 w-full"
                                                data-linea-id="{{ $linea->id }}">
                                                <option value="">Seleccionar producto</option>
                                                @php
                                                    $productosAgrupados = $productosBase->groupBy('tipo')->sortKeys();
                                                @endphp
                                                @foreach ($productosAgrupados as $tipo => $productos)
                                                    <optgroup label="{{ strtoupper($tipo) }}">
                                                        @foreach ($productos->sortBy('diametro') as $producto)
                                                            <option value="{{ $producto->id }}"
                                                                data-tipo="{{ $producto->tipo }}"
                                                                data-diametro="{{ $producto->diametro }}"
                                                                data-longitud="{{ $producto->longitud ?? '' }}"
                                                                {{ $linea->producto_base_id == $producto->id ? 'selected' : '' }}>
                                                                Ø{{ $producto->diametro }}
                                                                @if ($producto->longitud)
                                                                    x {{ $producto->longitud }}m
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>

                                    <td class="border px-2 py-1">
                                        {{ number_format($linea->cantidad ?? 0, 2, ',', '.') }} kg
                                    </td>
                                    <td class="border px-2 py-1">
                                        {{ number_format($linea->cantidad_recepcionada ?? 0, 2, ',', '.') }} kg
                                    </td>
                                    <td class="border px-2 py-1">{{ $pedido->fecha_pedido_formateada ?? '—' }}</td>
                                    <td class="border px-2 py-1">
                                        {{ $linea->fecha_estimada_entrega_formateada ?? '—' }}</td>
                                    <td class="border px-2 py-1 capitalize">{{ $linea->estado }}</td>
                                    <td class="border px-2 py-1 capitalize">{{ $pedido->creador->name ?? '—' }}</td>

                                    {{-- COLUMNA DE ACCIONES --}}
                                    <td class="border px-2 py-1 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            @php
                                                $estado = strtolower(trim($linea->estado));
                                                $esCancelado = $estado === 'cancelado';
                                                $esCompletado = $estado === 'completado';
                                                $esFacturado = $estado === 'facturado';

                                                $obraLinea = $linea->obra;
                                                $esEntregaDirecta = $obraLinea
                                                    ? !$obraLinea->es_nave_paco_reyes
                                                    : false;
                                                $esAlmacen = $obraLinea
                                                    ? stripos($obraLinea->obra, 'Almacén') !== false
                                                    : false;
                                                $esNaveA = $obraLinea
                                                    ? stripos($obraLinea->obra, 'Nave A') !== false
                                                    : false;
                                                $esNaveB = $obraLinea
                                                    ? stripos($obraLinea->obra, 'Nave B') !== false
                                                    : false;
                                                $esNaveValida = $esNaveA || $esNaveB;
                                                $pedidoCompletado = strtolower($pedido->estado) === 'completado';
                                            @endphp

                                            <div class="flex items-center justify-center gap-1 flex-wrap"
                                                @if ($esCancelado) style="pointer-events:none;opacity:.5" @endif>

                                                @if ($esCompletado || $esFacturado)
                                                    {{-- Sin acciones para líneas cerradas --}}
                                                @elseif ($esCancelado)
                                                    <button disabled
                                                        class="bg-gray-400 text-white text-xs px-2 py-1 rounded shadow opacity-50 cursor-not-allowed">
                                                        Cancelado
                                                    </button>
                                                @else
                                                    {{-- ========== BOTONES DE ESTADO DE LÍNEA (se ocultan en modo edición) ========== --}}
                                                    <div
                                                        class="botones-estado-{{ $linea->id }} flex items-center gap-1 flex-wrap">
                                                        {{-- BOTÓN COMPLETAR (Entrega directa) --}}
                                                        @if (($esEntregaDirecta || $esAlmacen) && !$pedidoCompletado)
                                                            <form method="POST"
                                                                action="{{ route('pedidos.editarCompletarLineaManual', ['pedido' => $pedido->id, 'linea' => $linea['id']]) }}"
                                                                onsubmit="return confirmarCompletarLinea(this);">
                                                                @csrf
                                                                <button type="submit"
                                                                    class="bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded shadow transition">
                                                                    Completar
                                                                </button>
                                                            </form>
                                                        @endif

                                                        {{-- BOTÓN DESACTIVAR --}}
                                                        @if ($estado === 'activo')
                                                            <form method="POST"
                                                                action="{{ route('pedidos.lineas.editarDesactivar', [$pedido->id, $linea['id']]) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" title="Desactivar línea"
                                                                    class="bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded shadow transition">
                                                                    Desactivar
                                                                </button>
                                                            </form>
                                                        @endif

                                                        {{-- BOTÓN ACTIVAR --}}
                                                        @if (($estado === 'pendiente' || $estado === 'parcial') && $esNaveValida)
                                                            <form method="POST"
                                                                action="{{ route('pedidos.lineas.editarActivar', [$pedido->id, $linea['id']]) }}">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" title="Activar línea"
                                                                    class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-2 py-1 rounded shadow transition">
                                                                    Activar
                                                                </button>
                                                            </form>
                                                        @endif

                                                        {{-- BOTÓN CANCELAR LÍNEA (oculto, se activa con confirmación) --}}
                                                        <form method="POST"
                                                            action="{{ route('pedidos.lineas.editarCancelar', [$pedido->id, $linea['id']]) }}"
                                                            class="form-cancelar-linea hidden"
                                                            data-pedido-id="{{ $pedido->id }}"
                                                            data-linea-id="{{ $linea['id'] }}">
                                                            @csrf
                                                            @method('PUT')
                                                        </form>

                                                        <button type="button"
                                                            onclick="confirmarCancelacionLinea({{ $pedido->id }}, {{ $linea['id'] }})"
                                                            class="bg-gray-500 hover:bg-gray-600 text-white text-xs px-2 py-1 rounded shadow transition">
                                                            Cancelar
                                                        </button>
                                                    </div>

                                                    {{-- ========== BOTONES DE EDICIÓN UNIFICADA ========== --}}

                                                    {{-- Botón EDITAR (abre ambos campos) --}}
                                                    <button type="button"
                                                        onclick="abrirEdicionLinea({{ $linea->id }})"
                                                        class="btn-editar-linea-{{ $linea->id }} bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded shadow transition"
                                                        title="Editar línea">
                                                        ✏️
                                                    </button>

                                                    {{-- Botón GUARDAR (guarda ambos campos) - OCULTO --}}
                                                    <button type="button"
                                                        onclick="guardarLinea({{ $linea->id }}, {{ $pedido->id }})"
                                                        class="btn-guardar-linea-{{ $linea->id }} hidden bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded shadow transition"
                                                        title="Guardar cambios">
                                                        💾
                                                    </button>

                                                    {{-- Botón CANCELAR EDICIÓN - OCULTO --}}
                                                    <button type="button"
                                                        onclick="cancelarEdicionLinea({{ $linea->id }})"
                                                        class="btn-cancelar-edicion-{{ $linea->id }} hidden bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded shadow transition"
                                                        title="Cancelar edición">
                                                        ✖️
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                        @empty
                            <tr>
                                <td colspan="14" class="py-4 text-gray-500">No hay pedidos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-tabla.paginacion :paginador="$pedidos" />

            <hr class="my-6">

            {{-- SECCIÓN DE STOCK --}}
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mt-4">📦 Estado actual de stock, pedidos y necesidades</h2>

                <div class="flex flex-wrap items-center gap-4 p-4">
                    <div>
                        <label for="obra_id_hpr" class="block text-sm font-medium text-gray-700 mb-1">
                            Seleccionar obra (Hierros Paco Reyes)
                        </label>
                        <select name="obra_id_hpr" id="obra_id_hpr_stock"
                            class="rounded border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <option value="">-- Todas las naves --</option>
                            @foreach ($obrasHpr as $obra)
                                <option value="{{ $obra->id }}"
                                    {{ request('obra_id_hpr') == $obra->id ? 'selected' : '' }}>
                                    {{ $obra->obra }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="loading-stock" class="hidden">
                        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                </div>

                <div id="contenedor-stock">
                    <x-estadisticas.stock :nombre-meses="$nombreMeses" :stock-data="$stockData" :pedidos-por-diametro="$pedidosPorDiametro" :necesario-por-diametro="$necesarioPorDiametro"
                        :total-general="$totalGeneral" :consumo-origen="$consumoOrigen" :consumos-por-mes="$consumosPorMes" :producto-base-info="$productoBaseInfo" :stock-por-producto-base="$stockPorProductoBase"
                        :kg-pedidos-por-producto-base="$kgPedidosPorProductoBase" :resumen-reposicion="$resumenReposicion" :recomendacion-reposicion="$recomendacionReposicion" :configuracion_vista_stock="$configuracion_vista_stock" />
                </div>
            </div>

            {{-- MODAL CONFIRMACIÓN PEDIDO --}}
            <div id="modalConfirmacion"
                class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                <div class="bg-white p-6 rounded-lg w-full max-w-5xl shadow-xl">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 text-left">Confirmar pedido</h3>

                    <form id="formularioPedido" action="{{ route('pedidos.store') }}" method="POST"
                        class="space-y-4">
                        @csrf

                        <div class="text-left">
                            <label for="fabricante" class="block text-sm font-medium text-gray-700 mb-1">
                                Seleccionar fabricante:
                            </label>
                            <select name="fabricante_id" id="fabricante"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                                <option value="">-- Elige un fabricante --</option>
                                @foreach ($fabricantes as $fabricante)
                                    <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="text-left mt-4">
                            <label for="distribuidor" class="block text-sm font-medium text-gray-700 mb-1">
                                Seleccionar distribuidor:
                            </label>
                            <select name="distribuidor_id" id="distribuidor"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                                <option value="">-- Elige un distribuidor --</option>
                                @foreach ($distribuidores as $distribuidor)
                                    <option value="{{ $distribuidor->id }}">{{ $distribuidor->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="text-left">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lugar de Entrega:</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Naves de Hierros Paco
                                        Reyes</label>
                                    <select name="obra_id_hpr" id="obra_id_hpr_modal"
                                        class="w-full border border-gray-300 rounded px-3 py-2"
                                        onchange="limpiarObraManual()">
                                        <option value="">Seleccionar nave</option>
                                        @foreach ($navesHpr as $nave)
                                            <option value="{{ $nave->id }}">{{ $nave->obra }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Obras Externas
                                        (activas)</label>
                                    <select name="obra_id_externa" id="obra_id_externa_modal"
                                        class="w-full border border-gray-300 rounded px-3 py-2"
                                        onchange="limpiarObraManual()">
                                        <option value="">Seleccionar obra externa</option>
                                        @foreach ($obrasExternas as $obra)
                                            <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Otra ubicación (texto
                                        libre)</label>
                                    <input type="text" name="obra_manual" id="obra_manual_modal"
                                        class="w-full border border-gray-300 rounded px-3 py-2"
                                        placeholder="Escribir dirección manualmente" oninput="limpiarSelectsObra()"
                                        value="{{ old('obra_manual') }}">
                                </div>
                            </div>
                        </div>

                        <div class="max-h-[60vh] overflow-auto rounded-lg shadow-xl border border-gray-300">
                            <table class="w-full border-collapse text-sm text-center">
                                <thead class="bg-blue-800 text-white sticky top-0 z-10">
                                    <tr class="bg-gray-700 text-white">
                                        <th class="border px-2 py-1">Tipo</th>
                                        <th class="border px-2 py-1">Diámetro</th>
                                        <th class="border px-2 py-1">Peso Total (kg)</th>
                                        <th class="border px-2 py-1">Desglose Camiones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaConfirmacionBody"></tbody>
                            </table>
                        </div>

                        <div id="mensajesGlobales" class="mt-2 text-sm space-y-1"></div>

                        <div class="text-right pt-4">
                            <button type="button" onclick="cerrarModalConfirmacion()"
                                class="mr-2 px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                Crear Pedido de Compra
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </div>
    @endif

    {{-- ROL OPERARIO --}}
    @if (Auth::user()->rol === 'operario')
        <div class="p-4 w-full max-w-4xl mx-auto">
            <div class="px-4 flex justify-center">
                <form method="GET" action="{{ route('pedidos.index') }}"
                    class="w-full sm:w-2/3 md:w-1/2 lg:w-1/3 flex flex-col sm:flex-row gap-2 mb-6">
                    <x-tabla.input name="codigo" value="{{ request('codigo') }}" class="flex-grow"
                        placeholder="Introduce el código del pedido (ej: PC25/0003)" />
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 rounded-xl shadow transition">
                        🔍 Buscar
                    </button>
                </form>
            </div>

            @php
                $codigo = request('codigo');
                $pedidosFiltrados = $codigo
                    ? \App\Models\Pedido::with('productos')
                        ->where('codigo', 'like', '%' . $codigo . '%')
                        ->orderBy('created_at', 'desc')
                        ->get()
                    : collect();
            @endphp

            @if ($codigo)
                @if ($pedidosFiltrados->isEmpty())
                    <div class="text-red-500 text-sm text-center">
                        No se encontraron pedidos con el código <strong>{{ $codigo }}</strong>.
                    </div>
                @else
                    {{-- Vista móvil --}}
                    <div class="grid gap-4 sm:hidden">
                        @foreach ($pedidosFiltrados as $pedido)
                            <div class="bg-white shadow rounded-lg p-4 text-sm border">
                                <div><span class="font-semibold">Código:</span> {{ $pedido->codigo }}</div>
                                <div><span class="font-semibold">Fabricante:</span>
                                    {{ $pedido->fabricante->nombre ?? '—' }}</div>
                                <div><span class="font-semibold">Estado:</span> {{ $pedido->estado ?? '—' }}</div>
                                <div class="mt-2">
                                    <a href="{{ route('pedidos.crearRecepcion', $pedido->id) }}"
                                        class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded text-xs">
                                        Recepcionar
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Vista escritorio --}}
                    <div class="hidden sm:block bg-white shadow rounded-lg overflow-x-auto mt-4">
                        <table class="w-full border text-sm text-center">
                            <thead class="bg-blue-600 text-white uppercase text-xs">
                                <tr>
                                    <th class="px-3 py-2 border">Código</th>
                                    <th class="px-3 py-2 border">Fabricante</th>
                                    <th class="px-3 py-2 border">Estado</th>
                                    <th class="px-3 py-2 border">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pedidosFiltrados as $pedido)
                                    <tr class="border-b hover:bg-blue-50">
                                        <td class="px-3 py-2">{{ $pedido->codigo }}</td>
                                        <td class="px-3 py-2">{{ $pedido->fabricante->nombre ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $pedido->estado ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route('pedidos.recepcion', $pedido->id) }}"
                                                class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded text-xs">
                                                Recepcionar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    @endif
    </div>

    {{-- ==================== SCRIPTS ==================== --}}

    {{-- Script: Confirmar completar línea --}}
    <script>
        function confirmarCompletarLinea(form) {
            Swal.fire({
                title: '¿Completar línea?',
                html: 'Se marcará como <b>completada</b> sin recepcionar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, completar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a',
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }
    </script>

    {{-- Script: Confirmar cancelación de línea --}}
    <script>
        function confirmarCancelacionLinea(pedidoId, lineaId) {
            Swal.fire({
                title: '¿Cancelar línea?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6b7280',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'Volver',
            }).then((result) => {
                if (result.isConfirmed) {
                    const formulario = document.querySelector(
                        `.form-cancelar-linea[data-pedido-id="${pedidoId}"][data-linea-id="${lineaId}"]`
                    );
                    if (formulario) {
                        formulario.submit();
                    }
                }
            });
        }
    </script>

    {{-- Script: Limpiar campos del modal --}}
    <script>
        function limpiarObraManual() {
            document.getElementById('obra_manual_modal').value = '';
        }

        function limpiarSelectsObra() {
            document.getElementById('obra_id_hpr_modal').selectedIndex = 0;
            document.getElementById('obra_id_externa_modal').selectedIndex = 0;
        }
    </script>

    {{-- Script: Edición unificada de línea --}}
    <script>
        // ========== EDICIÓN UNIFICADA DE LÍNEA (LUGAR + PRODUCTO) ==========

        function abrirEdicionLinea(lineaId) {
            const lugarView = document.querySelector(`.lugar-entrega-view-${lineaId}`);
            const productoView = document.querySelector(`.producto-view-${lineaId}`);
            const lugarEdit = document.querySelector(`.lugar-entrega-edit-${lineaId}`);
            const productoEdit = document.querySelector(`.producto-edit-${lineaId}`);

            const btnEditar = document.querySelector(`.btn-editar-linea-${lineaId}`);
            const btnGuardar = document.querySelector(`.btn-guardar-linea-${lineaId}`);
            const btnCancelar = document.querySelector(`.btn-cancelar-edicion-${lineaId}`);
            const botonesEstado = document.querySelector(`.botones-estado-${lineaId}`);

            if (lugarView) lugarView.classList.add('hidden');
            if (productoView) productoView.classList.add('hidden');
            if (lugarEdit) lugarEdit.classList.remove('hidden');
            if (productoEdit) productoEdit.classList.remove('hidden');

            if (btnEditar) btnEditar.classList.add('hidden');
            if (btnGuardar) btnGuardar.classList.remove('hidden');
            if (btnCancelar) btnCancelar.classList.remove('hidden');
            if (botonesEstado) botonesEstado.classList.add('hidden');

            if (lugarEdit) {
                configurarSelectsLugar(lugarEdit);
            }
        }

        function cancelarEdicionLinea(lineaId) {
            const lugarView = document.querySelector(`.lugar-entrega-view-${lineaId}`);
            const productoView = document.querySelector(`.producto-view-${lineaId}`);
            const lugarEdit = document.querySelector(`.lugar-entrega-edit-${lineaId}`);
            const productoEdit = document.querySelector(`.producto-edit-${lineaId}`);

            const btnEditar = document.querySelector(`.btn-editar-linea-${lineaId}`);
            const btnGuardar = document.querySelector(`.btn-guardar-linea-${lineaId}`);
            const btnCancelar = document.querySelector(`.btn-cancelar-edicion-${lineaId}`);
            const botonesEstado = document.querySelector(`.botones-estado-${lineaId}`);

            if (lugarView) lugarView.classList.remove('hidden');
            if (productoView) productoView.classList.remove('hidden');
            if (lugarEdit) lugarEdit.classList.add('hidden');
            if (productoEdit) productoEdit.classList.add('hidden');

            if (btnEditar) btnEditar.classList.remove('hidden');
            if (btnGuardar) btnGuardar.classList.add('hidden');
            if (btnCancelar) btnCancelar.classList.add('hidden');
            if (botonesEstado) botonesEstado.classList.remove('hidden');
        }

        function guardarLinea(lineaId, pedidoId) {
            const lugarEdit = document.querySelector(`.lugar-entrega-edit-${lineaId}`);
            const selectHpr = lugarEdit.querySelector('.obra-hpr-select');
            const selectExterna = lugarEdit.querySelector('.obra-externa-select');
            const inputManual = lugarEdit.querySelector('.obra-manual-input');

            const obraHpr = selectHpr.value;
            const obraExterna = selectExterna.value;
            const obraManual = inputManual.value.trim();
            const totalSeleccionado = [obraHpr, obraExterna, obraManual].filter(v => v).length;

            if (totalSeleccionado === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debes seleccionar un lugar de entrega'
                });
                return;
            }

            if (totalSeleccionado > 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Solo puedes seleccionar una opción de lugar de entrega'
                });
                return;
            }

            const productoEdit = document.querySelector(`.producto-edit-${lineaId}`);
            const selectProducto = productoEdit.querySelector('.producto-base-select');
            const productoBaseId = selectProducto.value;

            if (!productoBaseId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debes seleccionar un producto'
                });
                return;
            }

            const datos = {
                _method: 'PUT',
                linea_id: lineaId,
                obra_id: obraHpr || obraExterna || null,
                obra_manual: obraManual || null,
                producto_base_id: productoBaseId
            };

            fetch(`/pedidos/${pedidoId}/actualizar-linea`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(datos)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: 'Línea actualizada correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al actualizar la línea'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al actualizar la línea'
                    });
                });
        }

        function configurarSelectsLugar(editDiv) {
            const selectHpr = editDiv.querySelector('.obra-hpr-select');
            const selectExterna = editDiv.querySelector('.obra-externa-select');
            const inputManual = editDiv.querySelector('.obra-manual-input');

            if (!selectHpr || !selectExterna || !inputManual) return;

            const newSelectHpr = selectHpr.cloneNode(true);
            const newSelectExterna = selectExterna.cloneNode(true);
            const newInputManual = inputManual.cloneNode(true);

            selectHpr.parentNode.replaceChild(newSelectHpr, selectHpr);
            selectExterna.parentNode.replaceChild(newSelectExterna, selectExterna);
            inputManual.parentNode.replaceChild(newInputManual, inputManual);

            newSelectHpr.addEventListener('change', function() {
                if (this.value) {
                    newSelectExterna.value = '';
                    newInputManual.value = '';
                }
            });

            newSelectExterna.addEventListener('change', function() {
                if (this.value) {
                    newSelectHpr.value = '';
                    newInputManual.value = '';
                }
            });

            newInputManual.addEventListener('input', function() {
                if (this.value.trim()) {
                    newSelectHpr.value = '';
                    newSelectExterna.value = '';
                }
            });
        }
    </script>

    {{-- Script: Modal de creación de pedidos y sugerencia de pedido global --}}
    <script>
        function debounce(fn, delay) {
            let timer;
            return function() {
                clearTimeout(timer);
                const args = arguments;
                const context = this;
                timer = setTimeout(() => fn.apply(context, args), delay);
            }
        }

        // Recolectar todas las líneas del modal
        function recolectarLineas() {
            const lineas = [];
            let globalIndex = 0;

            document.querySelectorAll('#tablaConfirmacionBody tr').forEach((tr) => {
                const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                if (!contenedorFechas) return;

                const clave = contenedorFechas.id.replace('fechas-camion-', '');
                const inputsPeso = contenedorFechas.querySelectorAll('input[type="hidden"][name*="[peso]"]');

                inputsPeso.forEach((pesoInput, subIndex) => {
                    const peso = parseFloat(pesoInput.value || 0);
                    if (peso <= 0) return;

                    lineas.push({
                        index: globalIndex++,
                        clave: clave,
                        cantidad: peso,
                        sublinea: subIndex + 1
                    });
                });
            });

            return lineas;
        }

        // Sugerir pedidos globales disponibles
        function dispararSugerirMultiple() {
            const fabricante = document.getElementById('fabricante').value;
            const distribuidor = document.getElementById('distribuidor').value;
            if (!fabricante && !distribuidor) return;

            const lineas = recolectarLineas();
            if (lineas.length === 0) return;

            fetch('{{ route('pedidos.verSugerir-pedido-global') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        fabricante_id: fabricante,
                        distribuidor_id: distribuidor,
                        lineas: lineas
                    })
                })
                .then(r => r.json())
                .then(data => {
                    const mensajesGlobales = document.getElementById('mensajesGlobales');
                    mensajesGlobales.innerHTML = '';

                    // Limpiar asignaciones previas
                    document.querySelectorAll('[class*="pg-asignacion-"]').forEach(div => {
                        div.innerHTML = '<span class="text-gray-400">Sin asignar</span>';
                    });

                    if (data.mensaje) {
                        const div = document.createElement('div');
                        div.className = 'text-yellow-700 font-medium';
                        div.textContent = data.mensaje;
                        mensajesGlobales.appendChild(div);
                    }

                    // Procesar asignaciones
                    (data.asignaciones || []).forEach(asig => {
                        if (asig.linea_index !== null && asig.linea_index !== undefined) {
                            let encontrado = false;
                            let globalIdx = 0;

                            document.querySelectorAll('#tablaConfirmacionBody tr').forEach((tr) => {
                                if (encontrado) return;

                                const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                                if (!contenedorFechas) return;

                                const clave = contenedorFechas.id.replace('fechas-camion-', '');
                                const inputsPeso = contenedorFechas.querySelectorAll(
                                    'input[type="hidden"][name*="[peso]"]');

                                inputsPeso.forEach((pesoInput, subIdx) => {
                                    if (encontrado) return;

                                    if (globalIdx === asig.linea_index) {
                                        encontrado = true;

                                        const divAsignacion = document.querySelector(
                                            `.pg-asignacion-${clave}-${subIdx}`);

                                        if (divAsignacion) {
                                            if (asig.codigo) {
                                                divAsignacion.innerHTML = `
                                            <div class="text-left">
                                                <div class="font-bold text-green-700 text-sm">${asig.codigo}</div>
                                                <div class="text-xs text-gray-600 mt-1">${asig.mensaje}</div>
                                                <div class="text-xs text-blue-600 mt-1 font-medium">
                                                    📦 Quedan ${asig.cantidad_restante.toLocaleString('es-ES')} kg
                                                </div>
                                            </div>
                                        `;
                                                divAsignacion.className =
                                                    `pg-asignacion-${clave}-${subIdx} text-xs p-2 bg-green-50 rounded border border-green-200 min-h-[60px]`;

                                                // Agregar input hidden para pedido_global_id
                                                const lineaCamion = document.getElementById(
                                                    `linea-camion-${clave}-${subIdx}`);
                                                if (lineaCamion) {
                                                    let inputPG = lineaCamion.querySelector(
                                                        `input[name="productos[${clave}][${subIdx + 1}][pedido_global_id]"]`
                                                    );
                                                    if (!inputPG) {
                                                        inputPG = document.createElement(
                                                            'input');
                                                        inputPG.type = 'hidden';
                                                        inputPG.name =
                                                            `productos[${clave}][${subIdx + 1}][pedido_global_id]`;
                                                        lineaCamion.appendChild(inputPG);
                                                    }
                                                    inputPG.value = asig.pedido_global_id;
                                                }
                                            } else {
                                                divAsignacion.innerHTML =
                                                    `<div class="text-red-600 text-left">${asig.mensaje}</div>`;
                                                divAsignacion.className =
                                                    `pg-asignacion-${clave}-${subIdx} text-xs p-2 bg-red-50 rounded border border-red-200 min-h-[60px]`;
                                            }
                                        }
                                    }

                                    globalIdx++;
                                });
                            });
                        } else if (asig.mensaje) {
                            const div = document.createElement('div');
                            div.className = 'text-yellow-700 font-medium';
                            div.textContent = asig.mensaje;
                            mensajesGlobales.appendChild(div);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error al sugerir pedido global:', error);
                });
        }

        // Mostrar modal de confirmación
        function mostrarConfirmacion() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
            const tbody = document.getElementById('tablaConfirmacionBody');
            tbody.innerHTML = '';

            checkboxes.forEach((cb) => {
                const clave = cb.value;
                const tipo = document.querySelector(`input[name="detalles[${clave}][tipo]"]`).value;
                const diametro = document.querySelector(`input[name="detalles[${clave}][diametro]"]`).value;
                const cantidad = parseFloat(document.querySelector(`input[name="detalles[${clave}][cantidad]"]`)
                    .value);
                const longitudInput = document.querySelector(`input[name="detalles[${clave}][longitud]"]`);
                const longitud = longitudInput ? longitudInput.value : null;

                const fila = document.createElement('tr');
                fila.className = "bg-gray-100 border-b-2 border-gray-400";

                fila.innerHTML = `
                <td class="border px-2 py-2 align-top font-semibold">
                    ${tipo.charAt(0).toUpperCase() + tipo.slice(1)}
                </td>
                <td class="border px-2 py-2 align-top font-semibold">
                    ${diametro} mm${longitud ? ` / ${longitud} m` : ''}
                </td>
                <td class="border px-2 py-2 align-top">
                    <input type="number" class="peso-total w-full px-2 py-1 border rounded font-semibold"
                           name="detalles[${clave}][cantidad]" value="${cantidad}" step="2500" min="2500">
                </td>
                <td class="border px-2 py-2 align-top">
                    <div class="fechas-camion flex flex-col gap-2 w-full" id="fechas-camion-${clave}"></div>
                </td>
                <input type="hidden" name="seleccionados[]" value="${clave}">
                <input type="hidden" name="detalles[${clave}][tipo]" value="${tipo}">
                <input type="hidden" name="detalles[${clave}][diametro]" value="${diametro}">
                ${longitud ? `<input type="hidden" name="detalles[${clave}][longitud]" value="${longitud}">` : ''}
            `;
                tbody.appendChild(fila);

                const inputPeso = fila.querySelector('.peso-total');
                generarFechasPorPeso(inputPeso, clave);
            });

            dispararSugerirMultiple();
            document.getElementById('modalConfirmacion').classList.remove('hidden');
            document.getElementById('modalConfirmacion').classList.add('flex');
        }

        // Generar inputs de fecha según el peso
        function generarFechasPorPeso(input, clave) {
            const peso = parseFloat(input.value || 0);
            const contenedorFechas = document.getElementById(`fechas-camion-${clave}`);
            if (!contenedorFechas) return;

            contenedorFechas.innerHTML = '';

            const bloques = Math.ceil(peso / 25000);
            for (let i = 0; i < bloques; i++) {
                const pesoBloque = Math.min(25000, peso - i * 25000);

                const lineaCamion = document.createElement('div');
                lineaCamion.className = 'flex items-center gap-2 p-2 bg-white rounded border border-gray-200';
                lineaCamion.id = `linea-camion-${clave}-${i}`;

                lineaCamion.innerHTML = `
                <div class="flex flex-col gap-1 flex-1">
                    <label class="text-xs text-gray-600 font-medium">Camión ${i + 1} - ${pesoBloque.toLocaleString('es-ES')} kg</label>
                    <input type="date" 
                           name="productos[${clave}][${i + 1}][fecha]" 
                           required 
                           class="border px-2 py-1 rounded text-sm w-full">
                    <input type="hidden" 
                           name="productos[${clave}][${i + 1}][peso]" 
                           value="${pesoBloque}">
                </div>
                <div class="flex-1">
                    <div class="pg-asignacion-${clave}-${i} text-xs p-2 bg-gray-50 rounded border min-h-[60px] flex items-center justify-center">
                        <span class="text-gray-400">Selecciona fabricante/distribuidor</span>
                    </div>
                </div>
            `;

                contenedorFechas.appendChild(lineaCamion);
            }
        }

        // Cerrar modal
        function cerrarModalConfirmacion() {
            document.getElementById('modalConfirmacion').classList.add('hidden');
            document.getElementById('modalConfirmacion').classList.remove('flex');
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Listener para cambios en peso
            document.addEventListener('input', debounce((ev) => {
                const inputPeso = ev.target.closest('.peso-total');
                if (!inputPeso) return;

                const tr = inputPeso.closest('tr');
                const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                if (!contenedorFechas) return;

                const clave = contenedorFechas.id.replace('fechas-camion-', '');
                generarFechasPorPeso(inputPeso, clave);
                dispararSugerirMultiple();
            }, 300));

            // Listeners para fabricante/distribuidor
            const fabricanteSelect = document.getElementById('fabricante');
            const distribuidorSelect = document.getElementById('distribuidor');

            if (fabricanteSelect) {
                fabricanteSelect.addEventListener('change', dispararSugerirMultiple);
            }
            if (distribuidorSelect) {
                distribuidorSelect.addEventListener('change', dispararSugerirMultiple);
            }
        });
    </script>

    {{-- Script: Validación formulario pedido --}}
    <script>
        document.getElementById('formularioPedido').addEventListener('submit', function(ev) {
            ev.preventDefault();
            const errores = [];

            const fabricante = document.getElementById('fabricante').value;
            const distribuidor = document.getElementById('distribuidor').value;
            if (!fabricante && !distribuidor) {
                errores.push('Debes seleccionar un fabricante o un distribuidor.');
            }
            if (fabricante && distribuidor) {
                errores.push('Solo puedes seleccionar uno: fabricante o distribuidor.');
            }

            const obraHpr = document.getElementById('obra_id_hpr_modal').value;
            const obraExterna = document.getElementById('obra_id_externa_modal').value;
            const obraManual = document.getElementById('obra_manual_modal').value.trim();
            const totalObras = [obraHpr, obraExterna, obraManual].filter(v => v && v !== '').length;
            if (totalObras === 0) {
                errores.push('Debes seleccionar una nave, obra externa o escribir un lugar de entrega.');
            }
            if (totalObras > 1) {
                errores.push('Solo puedes seleccionar una opción: nave, obra externa o introducirla manualmente.');
            }

            const resumenLineas = [];
            document.querySelectorAll('#tablaConfirmacionBody tr').forEach(tr => {
                const tipo = tr.querySelector('td:nth-child(1)')?.textContent.trim();
                const diametro = tr.querySelector('td:nth-child(2)')?.textContent.trim().replace(' mm', '')
                    .split('/')[0].trim();
                const peso = parseFloat(tr.querySelector('.peso-total')?.value || 0);

                if (tipo && diametro) {
                    if (peso <= 0) {
                        errores.push(`El peso de la línea ${tipo} ${diametro} debe ser mayor a 0.`);
                    }

                    const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                    const fechas = [];

                    if (contenedorFechas) {
                        const inputsFecha = contenedorFechas.querySelectorAll('input[type="date"]');
                        inputsFecha.forEach((input, idx) => {
                            if (!input.value) {
                                errores.push(
                                    `Completa la fecha del camión ${idx + 1} para ${tipo} Ø${diametro}.`
                                );
                            }
                            fechas.push(input.value || '—');
                        });
                    }

                    resumenLineas.push({
                        tipo,
                        diametro,
                        peso,
                        fechas
                    });
                }
            });

            if (resumenLineas.length === 0) {
                errores.push('Debes seleccionar al menos un producto para generar el pedido.');
            }

            if (errores.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Revisa los datos',
                    html: '<ul style="text-align:left;">' + errores.map(e => `<li>• ${e}</li>`).join('') +
                        '</ul>'
                });
                return false;
            }

            let proveedorTexto = fabricante ?
                `Fabricante: ${document.querySelector('#fabricante option:checked').textContent}` :
                `Distribuidor: ${document.querySelector('#distribuidor option:checked').textContent}`;

            let obraTexto = obraHpr ?
                `Nave: ${document.querySelector('#obra_id_hpr_modal option:checked').textContent}` :
                obraExterna ?
                `Obra externa: ${document.querySelector('#obra_id_externa_modal option:checked').textContent}` :
                `Lugar manual: ${obraManual}`;

            let htmlResumen =
                `<p><b>${proveedorTexto}</b></p><p><b>${obraTexto}</b></p><hr><ul style="text-align:left;">`;
            resumenLineas.forEach(l => {
                htmlResumen += `<li>• ${l.tipo} Ø${l.diametro} → ${l.peso.toLocaleString('es-ES')} kg<br>` +
                    `📅 Fechas de entrega: ${l.fechas.join(', ')}</li>`;
            });
            htmlResumen += '</ul>';

            Swal.fire({
                title: '¿Crear pedido de compra?',
                html: htmlResumen,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, crear pedido',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a',
                focusCancel: true,
                width: 600,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    ev.target.submit();
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectObra = document.getElementById('obra_id_hpr_stock');
            const contenedorStock = document.getElementById('contenedor-stock');
            const loadingIndicator = document.getElementById('loading-stock');

            if (!selectObra || !contenedorStock) {
                return;
            }

            selectObra.addEventListener('change', function() {
                const obraId = this.value;

                // Mostrar loading
                if (loadingIndicator) loadingIndicator.classList.remove('hidden');
                contenedorStock.style.opacity = '0.5';
                contenedorStock.style.pointerEvents = 'none';

                // URL de la petición
                const url = '{{ route('pedidos.verStockHtml') }}' + (obraId ? '?obra_id_hpr=' + obraId :
                    '');

                fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Error en la petición');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.html) {
                            contenedorStock.innerHTML = data.html;
                            contenedorStock.style.opacity = '1';
                        } else {
                            throw new Error(data.message || 'Error desconocido');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo actualizar la tabla de stock',
                            confirmButtonColor: '#3b82f6'
                        });
                        contenedorStock.style.opacity = '1';
                    })
                    .finally(() => {
                        if (loadingIndicator) loadingIndicator.classList.add('hidden');
                        contenedorStock.style.pointerEvents = 'auto';
                    });
            });
        });
    </script>
</x-app-layout>
