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
                            <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código' !!}</th>
                            <th class="p-2 border">{!! $ordenables['pedido_global'] ?? 'Pedido Global' !!}</th>
                            <th class="p-2 border">{!! $ordenables['fabricante'] ?? 'Fabricante' !!}</th>
                            <th class="p-2 border">{!! $ordenables['distribuidor'] ?? 'Distribuidor' !!}</th>
                            <th class="px-2 py-2 border">Producto</th>
                            <th class="p-2 border">Cantidad Pedida</th>
                            <th class="p-2 border">Cantidad Recepcionada</th>
                            <th class="p-2 border">{!! $ordenables['fecha_pedido'] ?? 'F. Pedido' !!}</th>
                            <th class="p-2 border">{!! $ordenables['fecha_entrega'] ?? 'F. Entrega' !!}</th>
                            <th class="p-2 border">{!! $ordenables['estado'] ?? 'Estado' !!}</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>

                        <tr class="text-center text-xs uppercase">
                            <form method="GET" action="{{ route('pedidos.index') }}">
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
                                <x-tabla.botones-filtro ruta="pedidos.index" />
                            </form>
                        </tr>

                    </thead>

                    <tbody>
                        @forelse ($pedidos as $pedido)
                            {{-- Fila principal del pedido --}}
                            <tr class="bg-gray-100 text-xs font-bold uppercase">
                                <td colspan="10" class="text-left px-3 py-2">
                                    <span class="text-blue-600">Pedido:</span> {{ $pedido->codigo }} |
                                    <span class="text-blue-600">Peso Total: </span> {{ $pedido->peso_total_formateado }}
                                    |
                                    <span class="text-blue-600">Estado: </span>{{ $pedido->estado }} |
                                    <span class="text-blue-600">Fecha Pedido:
                                    </span>{{ $pedido->fecha_pedido_formateada }}
                                    <span class="float-right">
                                        <x-tabla.boton-eliminar :action="route('pedidos.destroy', $pedido->id)" />
                                    </span>
                                </td>
                            </tr>


                            {{-- Filas de las líneas del pedido --}}
                            @foreach ($pedido->lineas as $linea)
                                @php
                                    $estadoLinea = strtolower(trim($linea['estado']));
                                    $claseFondo = match ($estadoLinea) {
                                        'completado' => 'bg-green-100',
                                        'activo' => 'bg-yellow-100',
                                        'cancelado' => 'bg-gray-300 text-gray-500 opacity-70 cursor-not-allowed',
                                        default => 'even:bg-gray-50 odd:bg-white',
                                    };
                                    $esCancelado = $estadoLinea === 'cancelado';
                                    $esCompletado = $estadoLinea === 'completado';
                                @endphp


                                <tr class="text-xs {{ $claseFondo }}">
                                    <td class="border px-2 py-1">{{ $pedido->codigo }}</td>
                                    <td class="border px-2 py-1">{{ $pedido->pedidoGlobal?->codigo ?? '—' }}</td>
                                    <td class="border px-2 py-1">{{ $pedido->fabricante?->nombre ?? '—' }}</td>
                                    <td class="border px-2 py-1">{{ $pedido->distribuidor?->nombre ?? '—' }}</td>
                                    <td class="border px-2 py-1 text-center">
                                        {{ ucfirst($linea['tipo']) }}
                                        Ø{{ $linea['diametro'] }}
                                        @if ($linea['tipo'] === 'barra' && $linea['longitud'] !== '—')
                                            x {{ $linea['longitud'] }} m
                                        @endif
                                    </td>
                                    <td class="border px-2 py-1">
                                        {{ number_format($linea['cantidad'] ?? 0, 2, ',', '.') }} kg
                                    </td>
                                    <td class="border px-2 py-1">
                                        {{ number_format($linea['cantidad_recepcionada'] ?? 0, 2, ',', '.') }} kg
                                    </td>
                                    <td class="border px-2 py-1">{{ $pedido->fecha_pedido_formateada ?? '—' }}</td>
                                    <td class="border px-2 py-1">{{ $linea['fecha_estimada_entrega'] }}</td>
                                    <td class="border px-2 py-1 capitalize">{{ $linea['estado'] }}</td>
                                    <td class="border px-2 py-1 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            {{-- Botones en línea --}}
                                            <div class="flex items-center justify-center gap-1"
                                                @if ($esCancelado) style="pointer-events: none; opacity: 0.5;" @endif>

                                                @php $estado = strtolower(trim($linea['estado'])); @endphp

                                                @if ($esCompletado)
                                                    {{-- Solo botón Recepcionar --}}
                                                @elseif ($esCancelado)
                                                    <button disabled
                                                        class="bg-gray-400 text-white text-xs px-2 py-1 rounded shadow opacity-50 cursor-not-allowed">
                                                        Cancelado
                                                    </button>
                                                @elseif ($estado === 'activo')
                                                    {{-- Solo botón Desactivar --}}
                                                    <form method="POST"
                                                        action="{{ route('pedidos.lineas.editarDesactivar', [$pedido->id, $linea['id']]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" title="Desactivar línea"
                                                            class="bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded shadow transition">
                                                            Desactivar
                                                        </button>
                                                    </form>
                                                @elseif ($estado === 'pendiente')
                                                    {{-- Botón Activar --}}
                                                    <form method="POST"
                                                        action="{{ route('pedidos.lineas.editarActivar', [$pedido->id, $linea['id']]) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" title="Activar línea"
                                                            class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-2 py-1 rounded shadow transition">
                                                            Activar
                                                        </button>
                                                    </form>

                                                    {{-- Botón Cancelar (SweetAlert) --}}
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
                                <td colspan="12" class="py-4 text-gray-500">No hay pedidos registrados.</td>
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
                    :kg-pedidos-por-producto-base="$kgPedidosPorProductoBase" :resumen-reposicion="$resumenReposicion" :recomendacion-reposicion="$recomendacionReposicion" />


                <div class="mt-4 text-right">

                    <div id="modalConfirmacion"
                        class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                        <div class="bg-white p-6 rounded-lg w-full max-w-5xl shadow-xl">

                            {{-- Título alineado a la izquierda --}}
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 text-left">Confirmar pedido</h3>

                            <form id="formularioPedido" action="{{ route('pedidos.store') }}" method="POST"
                                class="space-y-4">
                                @csrf

                                {{-- Selector de fabricante alineado a la izquierda --}}
                                <div class="text-left">
                                    <label for="fabricante"
                                        class="block text-sm font-medium text-gray-700 mb-1">Seleccionar
                                        fabricante:</label>
                                    <select name="fabricante_id" id="fabricante"
                                        class="w-full border border-gray-300 rounded px-3 py-2">
                                        <option value="">-- Elige un fabricante --</option>
                                        @foreach ($fabricantes as $fabricante)
                                            <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- Selector de distribuidor --}}
                                <div class="text-left mt-4">
                                    <label for="distribuidor"
                                        class="block text-sm font-medium text-gray-700 mb-1">Seleccionar
                                        distribuidor:</label>
                                    <select name="distribuidor_id" id="distribuidor"
                                        class="w-full border border-gray-300 rounded px-3 py-2">
                                        <option value="">-- Elige un distribuidor --</option>
                                        @foreach ($distribuidores as $distribuidor)
                                            <option value="{{ $distribuidor->id }}">{{ $distribuidor->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Campo de lugar de entrega --}}
                                <div class="text-left">
                                    <label for="obra_id" class="block text-sm font-medium text-gray-700 mb-1">
                                        Lugar de Entrega:
                                    </label>
                                    <select name="obra_id" id="obra_id"
                                        class="w-full border border-gray-300 rounded px-3 py-2" required>
                                        <option value="">Seleccionar obra</option>
                                        @foreach ($obrasActivas as $obra)
                                            <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <table
                                    class="w-full border-collapse text-sm text-center shadow-xl overflow-hidden rounded-lg border border-gray-300">
                                    <thead class="bg-blue-800 text-white">
                                        <tr class="bg-gray-700 text-white">
                                            <th class="border px-2 py-1">Tipo</th>
                                            <th class="border px-2 py-1">Diámetro</th>
                                            <th class="border px-2 py-1">Peso a pedir (kg)</th>

                                        </tr>
                                    </thead>
                                    <tbody id="tablaConfirmacionBody">
                                        {{-- JavaScript agregará filas con inputs aquí --}}
                                    </tbody>
                                </table>



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
                           name="detalles[${clave}][cantidad]" value="${cantidad}" step="12500" min="12500"
                           onchange="generarFechasPorPeso(this, '${clave}')">
                    <div class="fechas-camion flex flex-col gap-1" id="${fechasId}" data-producto-id="${clave}">
                        <!-- Fechas se insertarán aquí -->
                    </div>
                </div>
            </td>
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

        function cerrarModalConfirmacion() {
            document.getElementById('modalConfirmacion').classList.remove('flex');
            document.getElementById('modalConfirmacion').classList.add('hidden');
        }

        //-----------------------------------------------------------------------------------------------------------
        function confirmarActivacion(pedidoId, productoId) {
            if (!confirm('¿Estás seguro de activar esta línea?')) return;

            enviarFormularioDinamico('pedidos.lineas.editarActivar', 'PUT', pedidoId,
                productoId);
        }

        function confirmarDesactivacion(pedidoId, productoId) {
            if (!confirm('¿Estás seguro de desactivar esta línea?')) return;

            enviarFormularioDinamico('pedidos.lineas.editarDesactivar', 'DELETE', pedidoId,
                productoId);
        }

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
