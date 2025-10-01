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

                            <th class="p-2 border">ID LINEA</th>
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
                                    <x-tabla.input name="pedido_producto_id" type="text" :value="request('pedido_producto_id')"
                                        class="w-full text-xs" />
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
                                <th class="p-1 border">

                                </th>
                                <th class="p-1 border">

                                </th>
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
                                        'pendiente' => 'Pendiente',
                                        'parcial' => 'Parcial',
                                        'completado' => 'Completado',
                                        'cancelado' => 'Cancelado',
                                    ]" :selected="request('estado')" empty="Todos"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">

                                </th>
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
                                    $esCancelado = $estadoLinea === 'cancelado';
                                    $esCompletado = $estadoLinea === 'completado';
                                    $esFacturado = $estadoLinea === 'facturado';
                                @endphp

                                <tr class="text-xs {{ $claseFondo }}">
                                    <td class="border px-2 py-1 text-center">
                                        <span class="font-semibold">{{ $linea->id }}</span>
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

                                    {{-- pedido global de la línea --}}
                                    <td class="border px-2 py-1">{{ $linea->pedidoGlobal?->codigo ?? '—' }}</td>

                                    <td class="border px-2 py-1">{{ $pedido->fabricante?->nombre ?? '—' }}</td>
                                    <td class="border px-2 py-1">{{ $pedido->distribuidor?->nombre ?? '—' }}</td>
                                    <td class="border px-2 py-1">
                                        {{ $pedido->obra->obra ?? ($pedido->obra_manual ?? '—') }}
                                    </td>

                                    <td class="border px-2 py-1 text-center">
                                        {{ ucfirst($linea->tipo) }}
                                        Ø{{ $linea->diametro }}
                                        @if ($linea->tipo === 'barra' && $linea->longitud !== '—')
                                            x {{ $linea->longitud }} m
                                        @endif
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

                                    <td class="border px-2 py-1 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            @php
                                                $estado = strtolower(trim($linea['estado']));
                                                $esCancelado = $estado === 'cancelado';
                                                $esCompletado = $estado === 'completado';
                                                $esFacturado = $estado === 'facturado';
                                                $esEntregaDirecta = !$pedido->obra?->es_nave_paco_reyes;
                                                $pedidoCompletado = strtolower($pedido->estado) === 'completado';
                                            @endphp

                                            <div class="flex items-center justify-center gap-1"
                                                @if ($esCancelado) style="pointer-events:none;opacity:.5" @endif>

                                                @if ($esCompletado || $esFacturado)
                                                    {{-- Cerrada: sin acciones --}}
                                                @elseif ($esCancelado)
                                                    <button disabled
                                                        class="bg-gray-400 text-white text-xs px-2 py-1 rounded shadow opacity-50 cursor-not-allowed">
                                                        Cancelado
                                                    </button>
                                                @else
                                                    {{-- === LÍNEA ABIERTA: mostramos acciones según caso === --}}

                                                    {{-- Entrega directa: botón Completar línea --}}
                                                    @if ($esEntregaDirecta && !$pedidoCompletado)
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

                                                    {{-- Flujo normal HPR --}}
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
                                                    @elseif ($estado === 'pendiente' || ($estado === 'parcial' && $pedido->obra?->es_nave_paco_reyes))
                                                        {{-- activar SOLO si es nave Paco Reyes --}}
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

                                                    {{-- Botón CANCELAR (siempre que esté abierto) --}}
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
            <hr>
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mt-4">📦 Estado actual de stock, pedidos y necesidades</h2>
                <!-- Tabla stock -->
                <form method="GET" action="{{ route('pedidos.index') }}"
                    class="flex flex-wrap items-center gap-4 p-4">
                    {{-- Mantener otros filtros activos --}}
                    @foreach (request()->except('page', 'obra_id_hpr') as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <div>
                        <label for="obra_id_hpr" class="block text-sm font-medium text-gray-700 mb-1">
                            Seleccionar obra (Hierros Paco Reyes)
                        </label>
                        <select name="obra_id_hpr" id="obra_id_hpr"
                            class="rounded border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            onchange="this.form.submit()">
                            <option value="">-- Todas las naves --</option>
                            @foreach ($obrasHpr as $obra)
                                <option value="{{ $obra->id }}"
                                    {{ request('obra_id_hpr') == $obra->id ? 'selected' : '' }}>
                                    {{ $obra->obra }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>


                <x-estadisticas.stock :nombre-meses="$nombreMeses" :stock-data="$stockData" :pedidos-por-diametro="$pedidosPorDiametro" :necesario-por-diametro="$necesarioPorDiametro"
                    :total-general="$totalGeneral" :consumo-origen="$consumoOrigen" :consumos-por-mes="$consumosPorMes" :producto-base-info="$productoBaseInfo" :stock-por-producto-base="$stockPorProductoBase"
                    :kg-pedidos-por-producto-base="$kgPedidosPorProductoBase" :resumen-reposicion="$resumenReposicion" :recomendacion-reposicion="$recomendacionReposicion" :configuracion_vista_stock="$configuracion_vista_stock" />
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

                            {{-- Lugar de entrega --}}
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
                                            placeholder="Escribir dirección manualmente"
                                            oninput="limpiarSelectsObra()" value="{{ old('obra_manual') }}">
                                    </div>
                                </div>
                            </div>

                            <table
                                class="w-full border-collapse text-sm text-center shadow-xl overflow-hidden rounded-lg border border-gray-300">
                                <thead class="bg-blue-800 text-white">
                                    <tr class="bg-gray-700 text-white">
                                        <th class="border px-2 py-1">Tipo</th>
                                        <th class="border px-2 py-1">Diámetro</th>
                                        <th class="border px-2 py-1">Peso a pedir (kg)</th>
                                        <th class="border px-2 py-1">Pedido Global sugerido</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaConfirmacionBody">
                                    {{-- JavaScript agregará filas con inputs aquí --}}
                                </tbody>
                            </table>
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
    </div>

    @endif
    {{-- ---------------------------------------------------- ROL OPERARIO ---------------------------------------------------- --}}
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
                    {{-- Vista tipo tarjeta en móvil --}}
                    <div class="grid gap-4 sm:hidden">
                        @foreach ($pedidosFiltrados as $pedido)
                            <div class="bg-white shadow rounded-lg p-4 text-sm border">
                                <div><span class="font-semibold">Código:</span> {{ $pedido->codigo }}</div>
                                <div><span class="font-semibold">Fabricante:</span>
                                    {{ $pedido->fabricante->nombre ?? '—' }}</div>
                                <div><span class="font-semibold">Distribuidor:</span>
                                    {{ $pedido->fabricante->distribuidor ?? '—' }}</div>
                                <div><span class="font-semibold">Fecha Pedido:</span>
                                    {{ optional($pedido->fecha_pedido)->format('d/m/Y') }}
                                </div>
                                <div><span class="font-semibold">Fecha Entrega:</span>
                                    {{ optional($pedido->fecha_entrega)->format('d/m/Y') }}
                                </div>
                                <div>
                                    <span class="font-semibold">Peso Total:</span>
                                    {{ $pedido->peso_total !== null ? round($pedido->peso_total, 0) . ' kg' : '—' }}
                                </div>
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

                    {{-- Vista tabla en escritorio --}}
                    <div class="hidden sm:block bg-white shadow rounded-lg overflow-x-auto mt-4">
                        <table class="w-full border text-sm text-center">
                            <thead class="bg-blue-600 text-white uppercase text-xs">
                                <tr>
                                    <th class="px-3 py-2 border">Código</th>
                                    <th class="px-3 py-2 border">Fabricante</th>
                                    <th class="px-3 py-2 border">Distribuidor</th>
                                    <th class="px-3 py-2 border">Fecha Pedido</th>
                                    <th class="px-3 py-2 border">Fecha Entrega</th>
                                    <th class="px-3 py-2 border">Peso Total</th>
                                    <th class="px-3 py-2 border">Estado</th>
                                    <th class="px-3 py-2 border">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pedidosFiltrados as $pedido)
                                    <tr class="border-b hover:bg-blue-50">
                                        <td class="px-3 py-2">{{ $pedido->codigo }}</td>
                                        <td class="px-3 py-2">{{ $pedido->fabricante->nombre ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $pedido->fabricante->distribuidor ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            {{ optional($pedido->fecha_pedido)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ optional($pedido->fecha_entrega)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ $pedido->peso_total !== null ? round($pedido->peso_total, 0) . ' kg' : '—' }}
                                        </td>
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

    <script>
        function confirmarCompletarLinea(form) {
            Swal.fire({
                title: '¿Completar línea?',
                html: 'Se marcará como <b>completada</b> sin recepcionar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, completar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a', // verde (tailwind: green-600)
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false; // bloquear envío hasta confirmar
        }
    </script>

    <script>
        function limpiarObraManual() {
            document.getElementById('obra_manual_modal').value = '';
        }

        function limpiarSelectsObra() {
            document.getElementById('obra_id_hpr_modal').selectedIndex = 0;
            document.getElementById('obra_id_externa_modal').selectedIndex = 0;
        }
    </script>
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

        // 🔹 Devuelve [{index, cantidad}, ...] con todas las líneas del modal
        function recolectarLineas() {
            const filas = document.querySelectorAll('#tablaConfirmacionBody tr');
            const lineas = [];
            filas.forEach((tr, index) => {
                const peso = parseFloat(tr.querySelector('.peso-total').value || 0);
                if (peso > 0) {
                    lineas.push({
                        index: index,
                        cantidad: peso
                    });
                }
            });
            return lineas;
        }

        // 🔹 Llama al backend y pinta las sugerencias en cada fila
        function dispararSugerirMultiple() {
            const fabricante = document.getElementById('fabricante').value;
            const distribuidor = document.getElementById('distribuidor').value;
            if (!fabricante && !distribuidor) return;

            const lineas = recolectarLineas();
            if (lineas.length === 0) return;

            fetch('{{ route('pedidos.sugerir-pedido-global') }}', {
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

                    if (data.mensaje) {
                        const div = document.createElement('div');
                        div.className = 'text-yellow-700 font-medium';
                        div.textContent = data.mensaje;
                        mensajesGlobales.appendChild(div);
                    }
                    data.asignaciones.forEach(asig => {
                        if (asig.linea_index !== null) {
                            // Mensajes por línea
                            const tr = document.querySelector(
                                `#tablaConfirmacionBody tr:nth-child(${asig.linea_index + 1})`
                            );
                            if (tr) {
                                const td = tr.querySelector('.pg-sugerido');
                                const clave = td?.dataset?.clave; // 👈 clave real que espera el backend

                                if (!clave) return; // por seguridad

                                if (asig.codigo) {
                                    td.innerHTML = `
                                        <div class="font-bold text-green-700">${asig.codigo}</div>
                                        <div class="text-xs text-gray-600">${asig.mensaje}</div>
                                        <div class="text-xs text-blue-600">Quedan ${asig.cantidad_restante} kg</div>
                                        <input type="hidden" 
                                                name="detalles[${clave}][pedido_global_id]" 
                                                value="${asig.pedido_global_id}">
                                        `;
                                } else {
                                    td.innerHTML = `
                                        <div class="text-red-600">${asig.mensaje}</div>
                                        <input type="hidden" 
                                                name="detalles[${clave}][pedido_global_id]" 
                                                value="">
                                        `;
                                }
                            }

                        } else {
                            // Mensajes globales (sin línea asociada)
                            const div = document.createElement('div');
                            div.className = 'text-yellow-700 font-medium';
                            div.textContent = asig.mensaje;
                            mensajesGlobales.appendChild(div);
                        }
                    });

                });


        }

        // recalcular cuando el usuario cambie peso en cualquier fila
        document.addEventListener('input', debounce((ev) => {
            if (!ev.target.closest('.peso-total')) return;
            dispararSugerirMultiple();
        }, 300));

        // recalcular cuando cambie fabricante/distribuidor
        document.getElementById('fabricante').addEventListener('change', dispararSugerirMultiple);
        document.getElementById('distribuidor').addEventListener('change', dispararSugerirMultiple);

        // dentro de mostrarConfirmacion(), tras crear todas las filas:
        dispararSugerirMultiple();
    </script>
    <script>
        document.getElementById('formularioPedido').addEventListener('submit', function(ev) {
            ev.preventDefault(); // siempre bloquear de primeras
            const errores = [];

            // 1) Hay productos seleccionados
            const seleccionados = document.querySelectorAll('#tablaConfirmacionBody input[name="seleccionados[]"]');
            if (seleccionados.length === 0) {
                errores.push('Selecciona al menos un producto para generar el pedido.');
            }

            // 2) Validar proveedor (uno y solo uno)
            const fabricante = document.getElementById('fabricante').value;
            const distribuidor = document.getElementById('distribuidor').value;
            if (!fabricante && !distribuidor) {
                errores.push('Debes seleccionar un fabricante o un distribuidor.');
            }
            if (fabricante && distribuidor) {
                errores.push('Solo puedes seleccionar uno: fabricante o distribuidor.');
            }

            // 3) Validar lugar de entrega (exactamente uno)
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

            // 4) Validar pesos y fechas de cada línea
            const resumenLineas = [];
            document.querySelectorAll('#tablaConfirmacionBody tr').forEach(tr => {
                const tipo = tr.querySelector('td:nth-child(1)').textContent.trim();
                const diametro = tr.querySelector('td:nth-child(2)').textContent.trim();
                const peso = parseFloat(tr.querySelector('.peso-total').value || 0);
                if (peso <= 0) {
                    errores.push(`El peso de la línea ${tipo} ${diametro} debe ser mayor a 0.`);
                }
                // comprobar fechas requeridas
                const fechas = [];
                tr.querySelectorAll('.fechas-camion input[type="date"]').forEach(input => {
                    if (!input.value) {
                        errores.push(
                            `Completa todas las fechas de entrega para ${tipo} ${diametro}.`);
                    }
                    fechas.push(input.value || '—');
                });

                resumenLineas.push({
                    tipo: tipo,
                    diametro: diametro,
                    peso: peso,
                    fechas: fechas
                });
            });

            if (errores.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Revisa los datos',
                    html: '<ul style="text-align:left;">' + errores.map(e => `<li>• ${e}</li>`).join('') +
                        '</ul>'
                });
                return false;
            }

            // ✅ Si no hay errores → mostrar confirmación con resumen
            let proveedorTexto = fabricante ?
                `Fabricante: ${document.querySelector('#fabricante option:checked').textContent}` :
                `Distribuidor: ${document.querySelector('#distribuidor option:checked').textContent}`;

            let obraTexto = obraHpr ?
                `Nave: ${document.querySelector('#obra_id_hpr_modal option:checked').textContent}` :
                obraExterna ?
                `Obra externa: ${document.querySelector('#obra_id_externa_modal option:checked').textContent}` :
                `Lugar manual: ${obraManual}`;

            let htmlResumen = `
        <p><b>${proveedorTexto}</b></p>
        <p><b>${obraTexto}</b></p>
        <hr>
        <ul style="text-align:left;">
    `;
            resumenLineas.forEach(l => {
                htmlResumen += `<li>• ${l.tipo} ${l.diametro} → ${l.peso} kg<br>
        Fechas: ${l.fechas.join(', ')}</li>`;
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
                    const fd = new FormData(document.getElementById('formularioPedido'));
                    console.log('PG de cada clave:', [...fd.entries()].filter(([k]) => k.includes(
                        'pedido_global_id')));

                    ev.target.submit();
                }
            });
        });
    </script>


    <script>
        function mostrarConfirmacion() {
            const checkboxes = document.querySelectorAll(
                'input[type="checkbox"]:checked');
            const tbody = document.getElementById('tablaConfirmacionBody');
            tbody.innerHTML = ''; // limpiar

            checkboxes.forEach(cb => {
                const clave = cb.value;
                const tipo = document.querySelector(
                    `input[name="detalles[${clave}][tipo]"]`).value;
                const diametro = document.querySelector(
                    `input[name="detalles[${clave}][diametro]"]`).value;
                const cantidad = parseFloat(document.querySelector(
                        `input[name="detalles[${clave}][cantidad]"]`)
                    .value);
                const longitudInput = document.querySelector(
                    `input[name="detalles[${clave}][longitud]"]`);
                const longitud = longitudInput ? longitudInput.value : null;

                const fila = document.createElement('tr');
                fila.className = "bg-gray-100";

                const fechasId = `fechas-camion-${clave}`;

                fila.innerHTML = `
  <td class="border px-2 py-1">${tipo.charAt(0).toUpperCase() + tipo.slice(1)}</td>
  <td class="border px-2 py-1">${diametro} mm${longitud ? ` / ${longitud} m` : ''}</td>
  <td class="border px-2 py-1">
    <div class="flex flex-col gap-2">
      <input type="number" class="peso-total w-full px-2 py-1 border rounded"
             name="detalles[${clave}][cantidad]" value="${cantidad}" step="2500" min="2500">
      <div class="fechas-camion flex flex-col gap-1" id="fechas-camion-${clave}"></div>
    </div>
  </td>
  <td class="border px-2 py-1 font-semibold text-green-700 pg-sugerido" data-clave="${clave}">—</td>
  <input type="hidden" name="seleccionados[]" value="${clave}">
  <input type="hidden" name="detalles[${clave}][tipo]" value="${tipo}">
  <input type="hidden" name="detalles[${clave}][diametro]" value="${diametro}">
  ${longitud ? `<input type="hidden" name="detalles[${clave}][longitud]" value="${longitud}">` : ''}
`;


                tbody.appendChild(fila);

                // Generar fechas al cargar
                const inputPeso = fila.querySelector('.peso-total');
                generarFechasPorPeso(inputPeso, clave);

            });

            // al final de mostrarConfirmacion(), justo antes de abrir el modal:
            dispararSugerirMultiple();

            document.getElementById('modalConfirmacion').classList.remove('hidden');
            document.getElementById('modalConfirmacion').classList.add('flex');

        }

        function generarFechasPorPeso(input, clave) {
            const peso = parseFloat(input.value || 0);
            const contenedorFechas = document.getElementById(`fechas-camion-${clave}`);
            if (!contenedorFechas) return;

            contenedorFechas.innerHTML = '';

            const bloques = Math.ceil(peso / 25000);
            for (let i = 0; i < bloques; i++) {
                const fecha = document.createElement('input');
                fecha.type = 'date';
                fecha.name = `productos[${clave}][${i + 1}][fecha]`;
                fecha.required = true;
                fecha.className = 'border px-2 py-1 rounded';
                contenedorFechas.appendChild(fecha);

                const pesoInput = document.createElement('input');
                pesoInput.type = 'hidden';
                pesoInput.name = `productos[${clave}][${i + 1}][peso]`;
                pesoInput.value = Math.min(25000, peso - i * 25000);
                contenedorFechas.appendChild(pesoInput);
            }
        }

        // cada vez que el usuario cambia el peso en una fila:
        document.addEventListener('input', debounce((ev) => {
            const inputPeso = ev.target.closest('.peso-total');
            if (!inputPeso) return;

            // 🔹 regenerar fechas para esa fila
            const clave = inputPeso
                .closest('tr')
                .querySelector('.pg-sugerido')
                .dataset.clave;
            generarFechasPorPeso(inputPeso, clave);

            // 🔹 recalcular pedido global sugerido
            dispararSugerirMultiple();
        }, 300));


        function cerrarModalConfirmacion() {
            document.getElementById('modalConfirmacion').classList.remove('flex');
            document.getElementById('modalConfirmacion').classList.add('hidden');
        }

        //-----------------------------------------------------------------------------------------------------------
        // function confirmarActivacion(pedidoId, productoId) {
        //     if (!confirm('¿Estás seguro de activar esta línea?')) return;

        //     enviarFormularioDinamico('pedidos.lineas.editarActivar', 'PUT', pedidoId,
        //         productoId);
        // }

        // function confirmarDesactivacion(pedidoId, productoId) {
        //     if (!confirm('¿Estás seguro de desactivar esta línea?')) return;

        //     enviarFormularioDinamico('pedidos.lineas.editarDesactivar', 'DELETE', pedidoId,
        //         productoId);
        // }

        function enviarFormularioDinamico(nombreRuta, metodo, pedidoId, lineaId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = route(nombreRuta, [pedidoId, lineaId]);

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrf;
            form.appendChild(csrfInput);

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = metodo;
            form.appendChild(methodInput);

            document.body.appendChild(form);
            form.submit();
        }

        //-----------------------------------------------------------------------------------------------------------


        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function confirmarActivacion(pedidoId, lineaId) {
            Swal.fire({
                title: '¿Activar producto?',
                html: 'Este producto del pedido se activará y estará disponible para su recepción.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, activar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d97706',
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/pedidos/${pedidoId}/lineas/${lineaId}/activar`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            _method: 'PUT'
                        })
                    }).then(() => location.reload());
                }
            });
        }


        function confirmarDesactivacion(pedidoId, lineaId) {
            Swal.fire({
                title: '¿Desactivar producto?',
                html: 'Se eliminará el movimiento pendiente si lo hay y se marcará como <b>pendiente</b> en el pedido.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#b91c1c',
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/pedidos/${pedidoId}/lineas/${lineaId}/desactivar`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            _method: 'DELETE'
                        })
                    }).then(() => location.reload());
                }
            });
        }
    </script>
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
                    } else {
                        console.error("No se encontró el formulario para cancelar la línea.");
                    }
                }
            });
        }
    </script>
</x-app-layout>
