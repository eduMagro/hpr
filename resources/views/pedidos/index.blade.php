<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            @if (auth()->user()->rol === 'oficina')
                <a href="{{ route('entradas.index') }}" class="text-blue-600">
                    {{ __('Entradas de Material') }}
                </a>
                <span class="mx-2">/</span>
            @endif
            {{ __('Pedidos de Compra') }}
            @if (auth()->user()->rol === 'oficina')
                <span class="mx-2">/</span>
                <a href="{{ route('pedidos_globales.index') }}" class="text-blue-600">
                    {{ __('Pedidos Globales') }}
                </a>
                <span class="mx-2">/</span>
                <a href="{{ route('proveedores.index') }}" class="text-blue-600">
                    {{ __('Proveedores') }}
                </a>
            @endif
        </h2>
    </x-slot>

    <div class="px-4 py-6">

        @if (count($filtrosActivos))
            <div class="alert alert-info text-sm mt-2 mb-4 shadow-sm">
                <strong>Filtros aplicados:</strong> {!! implode(', ', $filtrosActivos) !!}
            </div>
        @endif
        @if (auth()->user()->rol === 'oficina')
            <div class="mb-6"> <!-- Tabla stock -->
                <div class="overflow-x-auto rounded-lg">
                    @php
                        function rojo($diametro, $tipo, $longitud = null)
                        {
                            // ✅ Desbloqueamos la celda específica: barra, diámetro 10, longitud 12
                            if ($tipo === 'barra' && $diametro == 10 && $longitud == 12) {
                                return '';
                            }
                            if ($tipo === 'encarretado' && in_array($diametro, [25, 32])) {
                                return 'bg-red-200';
                            }
                            if ($tipo === 'barra') {
                                if (in_array($diametro, [8, 10])) {
                                    return 'bg-red-200';
                                }
                                if ($diametro == 12 && in_array($longitud, [15, 16])) {
                                    return 'bg-red-200';
                                }
                            }
                            return '';
                        }
                    @endphp

                    <table
                        class="w-full text-sm border-collapse text-center mt-6 rounded-lg shadow border border-gray-300 overflow-hidden">
                        <thead>
                            <thead>
                                <tr class="bg-gray-800 text-white">
                                    <th rowspan="2" class="border px-2 py-1">Ø mm</th>
                                    <th colspan="3" class="border px-2 py-1">Encarretado</th>
                                    <th colspan="3" class="border px-2 py-1">Barras 12 m</th>
                                    <th colspan="3" class="border px-2 py-1">Barras 14 m</th>
                                    <th colspan="3" class="border px-2 py-1">Barras 15 m</th>
                                    <th colspan="3" class="border px-2 py-1">Barras 16 m</th>
                                    <th colspan="3" class="border px-2 py-1">Barras Total</th>
                                    <th colspan="3" class="border px-2 py-1">Total</th>
                                </tr>
                                <tr class="bg-gray-700 text-white">
                                    @for ($i = 0; $i < 7; $i++)
                                        <th class="border px-2 py-1">Stock</th>
                                        <th class="border px-2 py-1">Pedido</th>
                                        <th class="border px-2 py-1">Necesario</th>
                                    @endfor
                                </tr>
                            </thead>
                        </thead>
                        <tbody>
                            @foreach ($stockData as $diametro => $stock)
                                @php
                                    $pedido = $pedidosPorDiametro[$diametro] ?? [
                                        'encarretado' => 0,
                                        'barras' => collect([12 => 0, 14 => 0, 15 => 0, 16 => 0]),
                                        'barras_total' => 0,
                                        'total' => 0,
                                    ];
                                    $necesario = $necesarioPorDiametro[$diametro] ?? [
                                        'encarretado' => 0,
                                        'barras' => collect([12 => 0, 14 => 0, 15 => 0, 16 => 0]),
                                        'barras_total' => 0,
                                        'total' => 0,
                                    ];
                                @endphp
                                <tr class="bg-white">
                                    <td class="border px-2 py-1 font-bold">{{ $diametro }}</td>
                                    {{-- Encarretado --}}
                                    @foreach (['encarretado'] as $tipo)
                                        @php
                                            $claseRojo = rojo($diametro, 'encarretado');
                                            $stockVal = $stock['encarretado'];
                                            $pedidoVal = $pedido['encarretado'];
                                            $necesarioVal = $necesario['encarretado'];
                                            $colorTexto = $necesarioVal > $stockVal ? 'text-red-600' : 'text-green-600';
                                        @endphp

                                        {{-- Stock --}}
                                        <td class="border px-2 py-1 {{ $claseRojo }}">
                                            @if (!$claseRojo)
                                                {{ number_format($stockVal, 2, ',', '.') }}
                                            @endif
                                        </td>

                                        {{-- Pedido --}}
                                        <td class="border px-2 py-1 {{ $claseRojo }}">
                                            @if (!$claseRojo)
                                                {{ number_format($pedidoVal, 2, ',', '.') }}
                                            @endif
                                        </td>

                                        {{-- Necesario --}}
                                        <td class="border px-2 py-1 {{ $claseRojo }}">
                                            @if (!$claseRojo)
                                                <div class="flex items-center justify-start gap-1">
                                                    <input type="checkbox" name="seleccionados[]"
                                                        value="encarretado-{{ $diametro }}">
                                                    <input type="hidden"
                                                        name="detalles[encarretado-{{ $diametro }}][tipo]"
                                                        value="encarretado">
                                                    <input type="hidden"
                                                        name="detalles[encarretado-{{ $diametro }}][diametro]"
                                                        value="{{ $diametro }}">
                                                    @php
                                                        $cantidadAPedir = round(max(0, $necesarioVal - $stockVal), 2);
                                                    @endphp
                                                    <input type="hidden"
                                                        name="detalles[encarretado-{{ $diametro }}][cantidad]"
                                                        value="{{ $cantidadAPedir }}">
                                                    <span
                                                        class="ml-1 {{ $colorTexto }}">{{ number_format($necesarioVal, 2, ',', '.') }}</span>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach

                                    {{-- Barras por longitud --}}
                                    @foreach ([12, 14, 15, 16] as $longitud)
                                        @php
                                            $claseRojo = rojo($diametro, 'barra', $longitud);
                                            $stockVal = $stock['barras'][$longitud] ?? 0;
                                            $pedidoVal = $pedido['barras'][$longitud] ?? 0;
                                            $necesarioVal = $necesario['barras'][$longitud] ?? 0;
                                            $colorTexto = $necesarioVal > $stockVal ? 'text-red-600' : 'text-green-600';
                                        @endphp

                                        {{-- Stock --}}
                                        <td class="border px-2 py-1 {{ $claseRojo }}">
                                            @if (!$claseRojo)
                                                {{ number_format($stockVal, 2, ',', '.') }}
                                            @endif
                                        </td>

                                        {{-- Pedido --}}
                                        <td class="border px-2 py-1 {{ $claseRojo }}">
                                            @if (!$claseRojo)
                                                {{ number_format($pedidoVal, 2, ',', '.') }}
                                            @endif
                                        </td>

                                        {{-- Necesario --}}
                                        <td class="border px-2 py-1 {{ $claseRojo }}">
                                            @if (!$claseRojo)
                                                <div class="flex items-center justify-start gap-1">
                                                    <input type="checkbox" name="seleccionados[]"
                                                        value="barra-{{ $diametro }}-{{ $longitud }}">
                                                    <input type="hidden"
                                                        name="detalles[barra-{{ $diametro }}-{{ $longitud }}][tipo]"
                                                        value="barra">
                                                    <input type="hidden"
                                                        name="detalles[barra-{{ $diametro }}-{{ $longitud }}][diametro]"
                                                        value="{{ $diametro }}">
                                                    <input type="hidden"
                                                        name="detalles[barra-{{ $diametro }}-{{ $longitud }}][longitud]"
                                                        value="{{ $longitud }}">
                                                    <input type="hidden"
                                                        name="detalles[barra-{{ $diametro }}-{{ $longitud }}][cantidad]"
                                                        value="{{ $necesarioVal }}">
                                                    <span
                                                        class="ml-1 {{ $colorTexto }}">{{ number_format($necesarioVal, 2, ',', '.') }}</span>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach

                                    {{-- Total Barras --}}
                                    <td class="border px-2 py-1 font-semibold">
                                        {{ number_format($stock['barras_total'], 2, ',', '.') }}
                                    </td>
                                    <td class="border px-2 py-1 font-semibold">
                                        {{ number_format($pedido['barras_total'], 2, ',', '.') }}
                                    </td>
                                    <td class="border px-2 py-1 font-semibold">
                                        {{ number_format($necesario['barras_total'], 2, ',', '.') }}
                                    </td>

                                    {{-- Total general --}}
                                    <td class="border px-2 py-1 font-bold">
                                        {{ number_format($stock['total'], 2, ',', '.') }}
                                    </td>
                                    <td class="border px-2 py-1 font-bold">
                                        {{ number_format($pedido['total'], 2, ',', '.') }}
                                    </td>
                                    <td class="border px-2 py-1 font-bold">
                                        {{ number_format($necesario['total'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-right">
                    <button type="button" onclick="mostrarConfirmacion()"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Crear pedido con seleccionados
                    </button>
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
                                        <option value="">-- Elige un proveedor --</option>
                                        @foreach ($proveedores as $proveedor)
                                            <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Campo de fecha estimada de entrega --}}
                                <div class="text-left">
                                    <label for="fecha_estimada_entrega"
                                        class="block text-sm font-medium text-gray-700 mb-1">
                                        Fecha estimada de entrega:
                                    </label>
                                    <input type="date" name="fecha_estimada_entrega" id="fecha_estimada_entrega"
                                        class="w-full border border-gray-300 rounded px-3 py-2">
                                </div>

                                <table
                                    class="w-full border-collapse text-sm text-center shadow-xl  overflow-hidden rounded-lg shadow border border-gray-300">
                                    <thead class="bg-blue-800 text-white">
                                        <tr class="bg-gray-700 text-white rounded-lg">
                                            <th class="border px-2 py-1">Tipo</th>
                                            <th class="border px-2 py-1">Diámetro</th>
                                            <th class="border px-2 py-1">Peso a pedir (kg)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaConfirmacionBody">
                                        {{-- Se llenará con JS --}}
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
        <!-- Tabla pedidos  -->
        <div class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="w-full border-collapse text-sm text-center">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código' !!}</th>
                        <th class="p-2 border">{!! $ordenables['pedido_global'] ?? 'Pedido Global' !!}</th>
                        <th class="p-2 border">{!! $ordenables['proveedor'] ?? 'Proveedor' !!}</th>
                        <th class="p-2 border">Cantidad Suministrada</th>
                        <th class="p-2 border">{!! $ordenables['peso_total'] ?? 'peso_total' !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_pedido'] ?? 'F. Pedido' !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_entrega'] ?? 'F. Estimada Entrega' !!}</th>
                        <th class="p-2 border">{!! $ordenables['estado'] ?? 'Estado' !!}</th>
                        <th class="p-2 border">Lineas</th>
                        <th class="p-2 border">Creación Registro</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <form method="GET" action="{{ route('pedidos.index') }}">
                            <th class="p-1 border">
                                <input type="text" name="codigo" value="{{ request('codigo') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <select name="pedido_global_id" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    @foreach ($pedidosGlobales as $pg)
                                        <option value="{{ $pg->id }}"
                                            {{ request('pedido_global_id') == $pg->id ? 'selected' : '' }}>
                                            {{ $pg->codigo }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="p-1 border">
                                <select name="proveedor_id" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    @foreach ($proveedores as $proveedor)
                                        <option value="{{ $proveedor->id }}"
                                            {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                                            {{ $proveedor->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="p-1 border"></th>
                            <th class="p-1 border"></th>
                            <th class="p-1 border">
                                <input type="date" name="fecha_pedido" value="{{ request('fecha_pedido') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="date" name="fecha_entrega" value="{{ request('fecha_entrega') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <select name="estado" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    <option value="pendiente"
                                        {{ request('estado') == 'pendiente' ? 'selected' : '' }}>
                                        Pendiente</option>
                                    <option value="parcial" {{ request('estado') == 'parcial' ? 'selected' : '' }}>
                                        Parcial</option>
                                    <option value="completo" {{ request('estado') == 'completo' ? 'selected' : '' }}>
                                        Completo</option>
                                    <option value="cancelado"
                                        {{ request('estado') == 'cancelado' ? 'selected' : '' }}>
                                        Cancelado</option>
                                </select>
                            </th>
                            <th class="p-1 border text-center"></th>
                            <th class="p-1 border text-center"></th>
                            <th class="p-1 border text-center">
                                <button type="submit" class="btn btn-sm btn-info px-2"><i
                                        class="fas fa-search"></i></button>
                                <a href="{{ route('pedidos.index') }}" class="btn btn-sm btn-warning px-2"><i
                                        class="fas fa-undo"></i></a>
                            </th>
                        </form>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($pedidos as $pedido)
                        <tr tabindex="0" x-data="{
                            editando: false,
                            pedido: @js($pedido),
                            original: JSON.parse(JSON.stringify(@js($pedido)))
                        }"
                            @dblclick="if(!$event.target.closest('input')) {
                          if(!editando) {
                            editando = true;
                          } else {
                            pedido = JSON.parse(JSON.stringify(original));
                            editando = false;
                          }
                        }"
                            @keydown.enter.stop="guardarCambios(pedido); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                            <!-- Código -->
                            <td class="border px-3 py-2">
                                <span x-text="pedido.codigo"></span>
                            </td>

                            <!-- Pedido Global -->
                            <td class="border px-3 py-2">
                                <span x-text="pedido.pedido_global?.codigo ?? 'N/A'"></span>
                            </td>

                            <!-- Proveedor -->
                            <td class="border px-3 py-2">
                                <span x-text="pedido.proveedor?.nombre ?? 'N/A'"></span>
                            </td>

                            <!-- Cantidad recepcionada -->
                            <td class="border px-3 py-2">
                                {{ number_format($pedido->cantidad_suministrada, 2, ',', '.') }} kg

                            </td>
                            <!-- Peso total -->
                            <td class="border px-3 py-2">
                                <span x-text="pedido.peso_total_formateado"></span>
                            </td>

                            <!-- Fecha Pedido -->
                            <td class="border px-3 py-2">
                                <template x-if="!editando">
                                    <span x-text="pedido.fecha_pedido_formateada ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="pedido.fecha_pedido"
                                    class="form-input w-full">
                            </td>
                            <!-- Fecha Entrega -->
                            <td class="border px-3 py-2">
                                <template x-if="!editando">
                                    <span x-text="pedido.fecha_entrega_formateada ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="pedido.fecha_entrega"
                                    class="form-input w-full">
                            </td>
                            <td class="border px-3 py-2 capitalize">{{ $pedido->estado }}</td>
                            <td class="border px-3 py-2">{{ $pedido->productos->count() }}</td>
                            <td class="border px-3 py-2">{{ $pedido->fecha_creacion_formateada }}</td>
                            <td class="border px-3 py-2 space-x-2">
                                <template x-if="editando">
                                    <button @click="guardarCambios(pedido); editando = false"
                                        class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded shadow">
                                        Guardar
                                    </button>
                                </template>
                                <a href="javascript:void(0)" class="text-blue-600 hover:underline text-sm"
                                    onclick='mostrarProductosModal(@json($pedido->productos_formateados))'>
                                    Ver
                                </a>
                                <x-boton-eliminar :action="route('pedidos.destroy', $pedido->id)" />
                                @if ($pedido->estado !== 'completo')
                                    <a href="{{ route('pedidos.recepcion', $pedido->id) }}"
                                        class="text-green-600 hover:underline">Recepcionar</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-gray-500">No hay pedidos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <!-- Modal -->
            <div id="modalProductos"
                class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                <div class="bg-white p-6 rounded-lg w-full max-w-4xl shadow-xl relative">
                    <button type="button" onclick="cerrarModalProductos()"
                        class="absolute top-3 right-3 text-gray-500 hover:text-red-600 transition duration-200 p-2 rounded-full hover:bg-gray-100 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>


                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Productos del pedido</h3>

                    <table
                        class="w-full border-collapse text-sm text-center overflow-hidden rounded-lg shadow border border-gray-300">
                        <thead class="bg-blue-800 text-white">
                            <tr>
                                <th class="border px-3 py-2">Tipo</th>
                                <th class="border px-3 py-2">Diámetro</th>
                                <th class="border px-3 py-2">Longitud</th>
                                <th class="border px-3 py-2">Cantidad (kg)</th>
                            </tr>
                        </thead>
                        <tbody id="tablaProductosBody" class="bg-white">
                            {{-- Se rellena por JS --}}
                        </tbody>
                    </table>

                </div>
            </div>


        </div>

        <div class="mt-4">
            {{ $pedidos->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>

    <script>
        function mostrarConfirmacion() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
            const tbody = document.getElementById('tablaConfirmacionBody');
            const form = document.getElementById('formularioPedido');

            tbody.innerHTML = ''; // limpiar

            checkboxes.forEach(cb => {
                const clave = cb.value;
                const tipo = document.querySelector(`input[name="detalles[${clave}][tipo]"]`).value;
                const diametro = document.querySelector(`input[name="detalles[${clave}][diametro]"]`).value;
                const cantidad = document.querySelector(`input[name="detalles[${clave}][cantidad]"]`).value;
                const longitudInput = document.querySelector(`input[name="detalles[${clave}][longitud]"]`);
                const longitud = longitudInput ? longitudInput.value : null;

                const fila = document.createElement('tr');
                fila.className = "bg-gray-100";

                fila.innerHTML = `
                    <td class="border px-2 py-1">${tipo.charAt(0).toUpperCase() + tipo.slice(1)}</td>
                    <td class="border px-2 py-1">${diametro} mm${longitud ? ` / ${longitud} m` : ''}</td>
                    <td class="border px-2 py-1">
                        <input type="number" step="25000" min="0" name="detalles[${clave}][cantidad]"
                            value="${cantidad}" class="w-full text-center border border-gray-300 rounded px-2 py-1">
                    </td>
                    <input type="hidden" name="seleccionados[]" value="${clave}">
                    <input type="hidden" name="detalles[${clave}][tipo]" value="${tipo}">
                    <input type="hidden" name="detalles[${clave}][diametro]" value="${diametro}">
                    ${longitud ? `<input type="hidden" name="detalles[${clave}][longitud]" value="${longitud}">` : ''}
                `;

                tbody.appendChild(fila);
            });

            document.getElementById('modalConfirmacion').classList.remove('hidden');
            document.getElementById('modalConfirmacion').classList.add('flex');
        }

        function cerrarModalConfirmacion() {
            document.getElementById('modalConfirmacion').classList.remove('flex');
            document.getElementById('modalConfirmacion').classList.add('hidden');
        }

        //-----------------------------------------------------------------------------------------------------------
        function guardarCambios(pedido) {
            const datos = JSON.parse(JSON.stringify(pedido));

            // Normalizar fechas para campos tipo date
            if (datos.fecha_pedido) {
                datos.fecha_pedido = datos.fecha_pedido.split('T')[0];
            }
            if (datos.fecha_entrega) {
                datos.fecha_entrega = datos.fecha_entrega.split('T')[0];
            }

            console.log("✅ Enviando datos del pedido:", datos);

            fetch(`{{ route('pedidos.update', '') }}/${datos.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(datos)
                })
                .then(async response => {
                    const contentType = response.headers.get('content-type');
                    let data = {};

                    if (contentType && contentType.includes('application/json')) {
                        data = await response.json();
                    } else {
                        const text = await response.text();
                        throw new Error("El servidor devolvió una respuesta inesperada: " + text.slice(0, 100));
                    }

                    if (response.ok && data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Pedido actualizado",
                            text: data.message,
                            confirmButtonText: "OK"
                        }).then(() => {
                            window.location.reload(); // 🔁 recarga tras éxito
                        });
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
                            window.location.reload(); // 🔁 recarga también tras error
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        text: error.message || "No se pudo actualizar el pedido.",
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.reload(); // 🔁 recarga tras error de red
                    });
                });
        }
        //----------------------------------------------------------------------------------------
        function mostrarProductosModal(productos) {
            const tbody = document.getElementById('tablaProductosBody');
            tbody.innerHTML = '';

            productos.forEach(producto => {
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td class="border px-2 py-1">${capitalize(producto.tipo)}</td>
                    <td class="border px-2 py-1">${producto.diametro} mm</td>
                    <td class="border px-2 py-1">${producto.longitud ?? '-'}</td>
                    <td class="border px-2 py-1">${parseFloat(producto.cantidad).toLocaleString('es-ES', { minimumFractionDigits: 2 })} kg</td>
                `;
                tbody.appendChild(fila);
            });

            document.getElementById('modalProductos').classList.remove('hidden');
            document.getElementById('modalProductos').classList.add('flex');
            window.scrollTo({
                top: window.scrollY
            });
            document.body.style.scrollBehavior = 'auto'; // Desactiva scroll suave al abrir modal
        }

        function cerrarModalProductos() {
            document.getElementById('modalProductos').classList.remove('flex');
            document.getElementById('modalProductos').classList.add('hidden');
        }



        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    </script>

</x-app-layout>
