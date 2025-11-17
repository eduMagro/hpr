<x-app-layout>
    <x-slot name="title">Pedidos de Almacén - {{ config('app.name') }}</x-slot>

    <x-menu.salidas.salidas />
    <x-menu.salidas.salidas2 />
    <x-menu.salidas.salidasAlmacen />

    <div class="w-full px-6 py-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-semibold text-gray-800">📄 Pedidos de Almacén</h1>
            <a href="{{ route('pedidos-almacen-venta.create') }}" wire:navigate
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
                ➕ Nuevo pedido
            </a>
        </div>

        <!-- Filtros aplicados -->
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <form id="form-salida" method="POST" action="{{ route('salidas-almacen.crear-desde-lineas') }}">
            @csrf

            <input type="hidden" name="lineas" id="lineas-input">
            <div class="overflow-x-auto bg-white shadow rounded-lg">
                <table class="w-full border-collapse text-sm text-center">
                    <thead class="bg-blue-500 text-white text-10">
                        <!-- Fila de títulos -->
                        <tr class="text-xs uppercase">
                            <th class="p-2 border">ID Línea</th>
                            <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código Pedido' !!}</th>
                            <th class="p-2 border">{!! $ordenables['cliente'] ?? 'Cliente' !!}</th>
                            <th class="p-2 border">Producto Base</th>
                            <th class="p-2 border">Unidad Medida</th>
                            <th class="p-2 border">Cant. Solicitada</th>
                            <th class="p-2 border">Cant. Servida</th>
                            <th class="p-2 border">Precio Unitario</th>
                            <th class="p-2 border">Estado</th>
                            <th class="p-2 border">
                                <input type="checkbox" id="select-all">
                            </th>

                            <th class="p-2 border">Acciones</th>
                        </tr>

                        <!-- Fila de filtros -->
                        <tr class="text-xs uppercase bg-blue-100 text-gray-800">
                            <form method="GET" action="{{ route('pedidos-almacen-venta.index') }}">
                                <th class="p-1 border">
                                    <x-tabla.input name="linea_id" type="text" :value="request('linea_id')"
                                        class="w-full text-xs" placeholder="ID" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="codigo" type="text" :value="request('codigo')"
                                        class="w-full text-xs" placeholder="Código" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.select name="cliente_id" :options="$clientes->pluck('nombre', 'id')" :selected="request('cliente_id')" empty="Todos"
                                        class="w-full text-xs" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="producto_base" type="text" :value="request('producto_base')"
                                        class="w-full text-xs" placeholder="Producto" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="unidad_medida" type="text" :value="request('unidad_medida')"
                                        class="w-full text-xs" placeholder="Unidad" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="cantidad_solicitada" type="number" step="0.01"
                                        :value="request('cantidad_solicitada')" wire:navigate class="w-full text-xs" placeholder="≥" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="cantidad_servida" type="number" step="0.01"
                                        :value="request('cantidad_servida')" wire:navigate class="w-full text-xs" placeholder="≥" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="precio_unitario" type="number" step="0.01"
                                        :value="request('precio_unitario')" wire:navigate class="w-full text-xs" placeholder="€" />
                                </th>
                                <th class="p-1 border">

                                </th>
                                <th class="p-1 border">
                                    <x-tabla.botones-filtro ruta="pedidos-almacen-venta.index" />
                                </th>
                            </form>
                        </tr>
                    </thead>


                    <tbody>
                        @forelse ($pedidos as $pedido)
                            {{-- 🔹 Cabecera del pedido --}}
                            <tr class="bg-gray-100 text-xs font-bold uppercase">
                                <td colspan="11" class="text-left px-3 py-2">
                                    <span class="text-blue-600">Pedido:</span> {{ $pedido->codigo }} |
                                    <span class="text-blue-600">Fecha:</span> {{ $pedido->fecha->format('d/m/Y') }} |
                                    <span class="text-blue-600">Cliente:</span> {{ $pedido->cliente->nombre ?? '—' }} |
                                    <span class="text-blue-600">Estado:</span>
                                    {{ ucfirst(str_replace('_', ' ', $pedido->estado)) }}

                                    <span class="float-right flex items-center gap-2">
                                        <a href="{{ route('pedidos-almacen-venta.show', $pedido) }}" wire:navigate
                                            class="text-blue-600 hover:underline text-xs">Ver</a>

                                        @if ($pedido->estado === 'borrador')
                                            <form action="{{ route('pedidos-almacen-venta.confirmar', $pedido) }}"
                                                method="POST" class="inline-block"
                                                onsubmit="return confirm('¿Confirmar este pedido?')">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:underline text-xs">
                                                    Confirmar
                                                </button>
                                            </form>
                                        @endif

                                        <x-tabla.boton-eliminar :action="route('pedidos-almacen-venta.destroy', $pedido->id)" />
                                    </span>
                                </td>
                            </tr>

                            {{-- 🔹 Filas de líneas del pedido --}}
                            @foreach ($pedido->lineas as $linea)
                                @php
                                    $albaranes = $linea->albaranesLineas; // colección de albaranes asociados
                                    $yaAsignada = $albaranes->isNotEmpty();

                                    $estado = $linea->estado_dinamico;

                                    $clasesEstado = match ($estado) {
                                        'completada' => 'bg-green-100 hover:bg-green-200',
                                        'parcial' => 'bg-yellow-100 hover:bg-yellow-200',
                                        default => 'bg-white hover:bg-gray-100',
                                    };
                                @endphp

                                <tr class="text-xs {{ $clasesEstado }}">


                                    <td class="border px-2 py-1">{{ $linea->id }}</td>
                                    <td class="border px-2 py-1 font-mono">{{ $pedido->codigo }}</td>
                                    <td class="border px-2 py-1">{{ $pedido->cliente->nombre ?? '—' }}</td>
                                    <td class="border px-2 py-1">
                                        {{ $linea->productoBase->tipo ?? '—' }} |
                                        {{ $linea->productoBase->diametro ?? '—' }} |
                                        {{ $linea->productoBase->longitud ?? '—' }}m
                                    </td>
                                    <td class="border px-2 py-1">{{ $linea->unidad_medida ?? '—' }}</td>
                                    <td class="border px-2 py-1 text-right">
                                        {{ number_format($linea->cantidad_solicitada, 2, ',', '.') }}
                                    </td>
                                    <td class="border px-2 py-1 text-right">
                                        {{ number_format($linea->cantidad_servida_calculada, 2, ',', '.') }}
                                    </td>
                                    <td class="border px-2 py-1 text-right">
                                        {{ $linea->precio_unitario !== null ? number_format($linea->precio_unitario, 2, ',', '.') . ' €' : '—' }}
                                    </td>
                                    <td class="border px-2 py-1 text-right">
                                        {{ $linea->estado ?? '—' }}
                                    </td>

                                    {{-- Columna de checkbox o info --}}
                                    <td class="border px-2 py-1 text-center">
                                        @if (in_array($linea->estado_dinamico, ['pendiente', 'parcial']))
                                            <input type="checkbox" name="lineas[]" value="{{ $linea->id }}"
                                                class="chk-linea">
                                        @else
                                            {{ ucfirst($linea->estado_dinamico) }}
                                        @endif
                                    </td>

                                    {{-- Columna de acciones o info extra --}}
                                    <td class="border px-2 py-1 text-center">
                                        @if ($yaAsignada)
                                            <div class="text-xs text-blue-600">
                                                En albaranes:<br>
                                                @foreach ($albaranes as $albLinea)
                                                    <span class="inline-block bg-blue-100 px-2 py-0.5 rounded">
                                                        {{ $albLinea->albaran->codigo ?? '—' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @elseif ($pedido->estado === 'borrador')
                                            <form method="POST"
                                                action="{{ route('pedidos-almacen-venta.lineas.cancelar', [$pedido->id, $linea->id]) }}"
                                                onsubmit="return confirm('¿Cancelar esta línea?')">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit"
                                                    class="bg-gray-500 hover:bg-gray-600 text-white text-xs px-2 py-1 rounded shadow">
                                                    Cancelar
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach


                        @empty
                            <tr>
                                <td colspan="11" class="py-4 text-gray-500">No hay pedidos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <button type="button" id="btn-crear-salida"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm">
                    🚚 Crear salida con seleccionadas
                </button>

            </div>
        </form>
        <script>
            document.getElementById("btn-crear-salida").addEventListener("click", async function() {
                const seleccionadas = window.lineasSeleccionadas;

                if (!seleccionadas.length) {
                    Swal.fire("Atención", "No has seleccionado ninguna línea.", "warning");
                    return;
                }

                // Pedimos al servidor los detalles de esas líneas seleccionadas
                const resp = await fetch("{{ route('pedidos-almacen-venta.lineas.detalles') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        lineas: seleccionadas
                    })
                });

                const data = await resp.json();

                if (!resp.ok) {
                    Swal.fire("Error", data.message || "No se pudieron cargar las líneas.", "error");
                    return;
                }

                // Generamos inputs dinámicos para cada línea
                // Generamos inputs dinámicos para cada línea
                let html = "<div class='text-left space-y-2'>";
                data.lineas.forEach(l => {
                    html += `
        <div class="p-2 border rounded bg-gray-50">
            <div><strong>Pedido:</strong> ${l.pedido_codigo} | <strong>Cliente:</strong> ${l.cliente_nombre}</div>
            <div><strong>Línea ${l.id}</strong> - ${l.producto}</div>
            <div>
                Solicitada: ${l.cantidad_solicitada} |
                Ya servida: ${l.cantidad_servida_calculada} |
                Pendiente: ${l.cantidad_pendiente}
            </div>
            ${
                l.cantidad_pendiente > 0
                    ? `<label>
                                                                                                                                                        Cant. a asignar:
                                                                                                                                                        <input type="number" step="0.01" min="0" max="${l.cantidad_pendiente}"
                                                                                                                                                            name="cantidad[${l.id}]" value="${l.cantidad_pendiente}"
                                                                                                                                                            class="swal2-input w-32">
                                                                                                                                                       </label>`
                    : `<span class="inline-block bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
                                                                                                                                                        ✅ Completada
                                                                                                                                                       </span>`
            }
        </div>
    `;
                });
                html += "</div>";



                const {
                    value: formValues
                } = await Swal.fire({
                    title: "Asignar a salida",
                    html: html,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: "Crear salida",
                    preConfirm: () => {
                        const cantidades = {};
                        data.lineas.forEach(l => {
                            const val = document.querySelector(
                                `input[name="cantidad[${l.id}]"]`).value;
                            cantidades[l.id] = parseFloat(val) || 0;
                        });
                        return cantidades;
                    }
                });

                if (formValues) {
                    // Enviar al backend con cantidades seleccionadas
                    const resp2 = await fetch("{{ route('salidas-almacen.crear-desde-lineas') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            lineas: formValues
                        })
                    });

                    const result = await resp2.json();
                    if (resp2.ok) {
                        Swal.fire("Salida creada", "Se ha generado la salida correctamente.", "success")
                            .then(() => location.reload());
                    } else {
                        Swal.fire("Error", result.message || "No se pudo crear la salida.", "error");
                    }
                }
            });
        </script>
        <script>
            // Guardar las selecciones en memoria
            window.lineasSeleccionadas = JSON.parse(localStorage.getItem("lineasSeleccionadas") || "[]");

            function syncCheckboxes() {
                document.querySelectorAll(".chk-linea").forEach(chk => {
                    chk.checked = window.lineasSeleccionadas.includes(parseInt(chk.value));
                    chk.addEventListener("change", () => {
                        const val = parseInt(chk.value);
                        if (chk.checked) {
                            if (!window.lineasSeleccionadas.includes(val)) {
                                window.lineasSeleccionadas.push(val);
                            }
                        } else {
                            window.lineasSeleccionadas = window.lineasSeleccionadas.filter(id => id !== val);
                        }
                        localStorage.setItem("lineasSeleccionadas", JSON.stringify(window.lineasSeleccionadas));
                    });
                });

                // Control para "Seleccionar todo" de la página actual
                const selectAll = document.getElementById("select-all");
                if (selectAll) {
                    selectAll.checked = [...document.querySelectorAll(".chk-linea")].every(c => c.checked);
                    selectAll.addEventListener("change", e => {
                        document.querySelectorAll(".chk-linea").forEach(chk => {
                            chk.checked = e.target.checked;
                            chk.dispatchEvent(new Event("change")); // dispara la lógica normal
                        });
                    });
                }
            }

            // Llamar al cargar
            syncCheckboxes();

            // Antes de enviar el formulario, guardamos todo en un hidden input
            document.getElementById("form-salida").addEventListener("submit", function() {
                document.getElementById("lineas-input").value = JSON.stringify(window.lineasSeleccionadas);
                localStorage.removeItem("lineasSeleccionadas"); // opcional, limpiar después
            });
        </script>
        <x-tabla.paginacion :paginador="$pedidos" />
    </div>
</x-app-layout>
