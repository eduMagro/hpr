<x-app-layout>
    <x-slot name="title">Pedidos - {{ config('app.name') }}</x-slot>
    <x-menu.materiales />

    <div class="px-4 py-6">

        @if (auth()->user()->rol === 'oficina')

            <div class="mb-6"> <!-- Tabla stock -->
                <x-estadisticas.stock :stock-data="$stockData" :pedidos-por-diametro="$pedidosPorDiametro" :necesario-por-diametro="$necesarioPorDiametro" :total-general="$totalGeneral" />

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
            <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
            <!-- Tabla pedidos  -->
            <div class="overflow-x-auto bg-white shadow rounded-lg">
                <table class="w-full border-collapse text-sm text-center">
                    <thead class="bg-blue-500 text-white text-10">
                        <tr class="text-center text-xs uppercase">
                            <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código' !!}</th>
                            <th class="p-2 border">{!! $ordenables['pedido_global'] ?? 'Pedido Global' !!}</th>
                            <th class="p-2 border">{!! $ordenables['fabricante'] ?? 'Fabricante' !!}</th>
                            <th class="p-2 border">{!! $ordenables['distribuidor'] ?? 'Distribuidor' !!}</th>
                            <th class="p-2 border">Cantidad Restante</th>
                            <th class="p-2 border">{!! $ordenables['peso_total'] ?? 'peso_total' !!}</th>
                            <th class="p-2 border">{!! $ordenables['fecha_pedido'] ?? 'F. Pedido' !!}</th>
                            <th class="p-2 border">{!! $ordenables['fecha_entrega'] ?? 'F. Entrega' !!}</th>
                            <th class="p-2 border">{!! $ordenables['estado'] ?? 'Estado' !!}</th>
                            <th class="p-2 border">Lineas</th>
                            <th class="p-2 border">Creación Registro</th>
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
                                        'pendiente' => 'Pendiente',
                                        'parcial' => 'Parcial',
                                        'completo' => 'Completo',
                                        'cancelado' => 'Cancelado',
                                    ]" :selected="request('estado')" empty="Todos"
                                        class="w-full text-xs" />
                                </th>

                                <th class="p-1 border text-center"></th>
                                <th class="p-1 border text-center"></th>

                                <x-tabla.botones-filtro ruta="pedidos.index" />
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

                                <!-- Fabricante -->
                                <td class="border px-3 py-2">
                                    <span x-text="pedido.fabricante?.nombre ?? 'N/A'"></span>
                                </td>

                                <!-- Distribuidor -->
                                <td class="border px-3 py-2">
                                    <span x-text="pedido.distribuidor?.nombre ?? 'N/A'"></span>
                                </td>


                                <!-- Cantidad recepcionada -->
                                <td class="border px-3 py-2">
                                    {{ number_format($pedido->cantidad_restante, 2, ',', '.') }} kg

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

                                <td class="px-1 py-2 border text-xs font-bold">
                                    <div class="flex items-center space-x-2 justify-center">
                                        <!-- Mostrar solo en modo edición -->
                                        <x-tabla.boton-guardar x-show="editando"
                                            @click="guardarCambios(elemento); editando = false" />
                                        <x-tabla.boton-cancelar-edicion @click="editando = false" x-show="editando" />

                                        <!-- Mostrar solo cuando NO está en modo edición -->
                                        <template x-if="!editando">
                                            <div class="flex items-center space-x-2">

                                                <x-tabla.boton-editar @click="editando = true" x-show="!editando" />
                                                <a href="javascript:void(0)"
                                                    @click="mostrarProductosModal(@js($pedido->productos_formateados), {{ $pedido->id }})"
                                                    class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>

                                                <x-tabla.boton-eliminar :action="route('pedidos.destroy', $pedido->id)" />

                                            </div>
                                        </template>
                                    </div>
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
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
                                    <th class="border px-3 py-2">Cantidad recepcionada</th>
                                    <th class="border px-3 py-2">Cantidad total</th>
                                    <th class="border px-3 py-2">Fecha estimada</th>
                                    <th class="border px-3 py-2">Estado recepción</th>
                                    <th class="border px-3 py-2">Acciones</th>
                                </tr>
                            </thead>

                            <tbody id="tablaProductosBody" class="bg-white">
                                {{-- Se rellena con JS --}}
                            </tbody>

                        </table>

                    </div>
                </div>

            </div>
            <x-tabla.paginacion :paginador="$pedidos" />

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
                                        <a href="{{ route('pedidos.recepcion', $pedido->id) }}"
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
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
            const tbody = document.getElementById('tablaConfirmacionBody');
            tbody.innerHTML = ''; // limpiar

            checkboxes.forEach(cb => {
                const clave = cb.value;
                const tipo = document.querySelector(`input[name="detalles[${clave}][tipo]"]`).value;
                const diametro = document.querySelector(`input[name="detalles[${clave}][diametro]"]`).value;
                const cantidad = parseFloat(document.querySelector(`input[name="detalles[${clave}][cantidad]"]`)
                    .value);
                const longitudInput = document.querySelector(`input[name="detalles[${clave}][longitud]"]`);
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
                           name="detalles[${clave}][cantidad]" value="${cantidad}" step="25000" min="25000"
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
        function mostrarProductosModal(productos, pedidoId) {
            const tbody = document.getElementById('tablaProductosBody');
            tbody.innerHTML = '';

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            productos.forEach(producto => {
                const fila = document.createElement('tr');
                const estaActivo = producto.estado_recepcion === 'activo';

                // Estado de recepción visual
                let estadoRecepcion = '—';
                switch (producto.estado_recepcion) {
                    case 'completado':
                        estadoRecepcion =
                            `<span class="text-green-700 bg-green-100 px-2 py-1 rounded text-xs font-semibold">Completado</span>`;
                        break;
                    case 'parcial':
                        estadoRecepcion =
                            `<span class="text-yellow-800 bg-yellow-100 px-2 py-1 rounded text-xs font-semibold">Parcial</span>`;
                        break;
                    case 'pendiente':
                        estadoRecepcion =
                            `<span class="text-red-700 bg-red-100 px-2 py-1 rounded text-xs font-semibold">Pendiente</span>`;
                        break;
                }

                fila.innerHTML = `
<td class="border px-2 py-1">${capitalize(producto.tipo)}</td>
<td class="border px-2 py-1">${producto.diametro} mm</td>
<td class="border px-2 py-1">${producto.longitud ?? '—'}</td>
<td class="border px-2 py-1">${parseFloat(producto.cantidad_recepcionada ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2 })} kg</td>
<td class="border px-2 py-1">${parseFloat(producto.cantidad).toLocaleString('es-ES', { minimumFractionDigits: 2 })} kg</td>
<td class="border px-2 py-1">${producto.fecha_estimada_entrega ?? '—'}</td>
<td class="border px-2 py-1">${estadoRecepcion}</td>
<td class="border px-2 py-1">
   ${
    estaActivo
        ? `<button
                                                                                                            onclick="confirmarDesactivacion(${pedidoId}, ${producto.producto_base_id})"
                                                                                                            class="bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded shadow transition">
                                                                                                            Desactivar
                                                                                                       </button>`
        : `<button
                                                                                                            onclick="confirmarActivacion(${pedidoId}, ${producto.producto_base_id})"
                                                                                                            class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-2 py-1 rounded shadow transition">
                                                                                                            Activar
                                                                                                       </button>`
}

</td>
`;
                tbody.appendChild(fila);
            });

            document.getElementById('modalProductos').classList.remove('hidden');
            document.getElementById('modalProductos').classList.add('flex');
            window.scrollTo({
                top: window.scrollY
            });
            document.body.style.scrollBehavior = 'auto';
        }

        function cerrarModalProductos() {
            document.getElementById('modalProductos').classList.remove('flex');
            document.getElementById('modalProductos').classList.add('hidden');
        }



        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function confirmarActivacion(pedidoId, productoBaseId) {
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
                    fetch(`/pedidos/${pedidoId}/activar-producto/${productoBaseId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            _method: 'PUT'
                        })
                    }).then(() => location.reload());
                }
            });
        }

        function confirmarDesactivacion(pedidoId, productoBaseId) {
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
                    fetch(`/pedidos/${pedidoId}/desactivar-producto/${productoBaseId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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

</x-app-layout>
