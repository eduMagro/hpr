<x-app-layout>
    <x-slot name="title">Movimientos - {{ config('app.name') }}</x-slot>

    <div class="w-full px-6 py-4">
        <!-- Botón para crear un nuevo movimiento con estilo Bootstrap -->
        <div class="mb-4">
            <div class="container mx-auto">
                <!-- Botón que abre el modal -->
                <div class="mb-4 flex justify-center">
                    <button onclick="abrirModalMovimientoLibre()"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow">
                        ➕ Crear Movimiento Libre
                    </button>
                </div>
                @include('components.maquinas.modales.grua.modales-grua', ['maquinasDisponibles' => $maquinasDisponibles])


                <!-- Resto de tu contenido -->
            </div>
        </div>
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <div class="overflow-x-auto bg-white shadow-md rounded-lg">

            <table class="min-w-full table-auto">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">

                        <th class="p-2 border">{!! $ordenables['id'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['tipo'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['pedido_producto_id'] ?? 'Línea Pedido' !!}</th>

                        <th class="p-2 border">{!! $ordenables['producto_id'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['descripcion'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['nave'] ?? 'Nave' !!}</th>
                        <th class="p-2 border">{!! $ordenables['prioridad'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['solicitado_por'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['ejecutado_por'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['estado'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_solicitud'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_ejecucion'] !!}</th>
                        <th class="p-2 border">Origen</th>
                        <th class="p-2 border">Destino</th>
                        <th class="p-2 border">Producto/Paquete</th>
                        <th class="p-2 border">Acciones</th>

                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <form method="GET" action="{{ route('movimientos.index') }}">
                            <th class="p-1 border">
                                <x-tabla.input name="id" value="{{ request('id') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="tipo" value="{{ request('tipo') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="pedido_producto_id" value="{{ request('pedido_producto_id') }}"
                                    placeholder="Línea" />
                            </th>

                            <th class="py-1 px-0 border">
                                <div class="flex gap-2 justify-center">
                                    <input type="text" name="producto_tipo" value="{{ request('producto_tipo') }}"
                                        placeholder="T"
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
                                <x-tabla.input name="descripcion" value="{{ request('descripcion') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.select name="nave_id" :options="$navesSelect" :selected="request('nave_id')" empty="Todas"
                                    class="form-select" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.select name="prioridad" :options="[
                                    1 => 'Baja',
                                    2 => 'Media',
                                    3 => 'Alta',
                                ]" :selected="request('prioridad')" empty="Todas" />

                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="solicitado_por" value="{{ request('solicitado_por') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="ejecutado_por" value="{{ request('ejecutado_por') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.select name="estado" :options="[
                                    'pendiente' => 'Pendiente',
                                    'completado' => 'Completado',
                                    'cancelado' => 'Cancelado',
                                ]" :selected="request('estado')" empty="Todos" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input type="date" name="fecha_solicitud"
                                    value="{{ request('fecha_solicitud') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input type="date" name="fecha_ejecucion"
                                    value="{{ request('fecha_ejecucion') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="origen" value="{{ request('origen') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="destino" value="{{ request('destino') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="producto_paquete" value="{{ request('producto_paquete') }}" />
                            </th>
                            <x-tabla.botones-filtro ruta="movimientos.index" />
                        </form>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($registrosMovimientos as $movimiento)
                    <tr class="border-b">
                        <td class="px-2 py-4 text-xs leading-none text-gray-900 text-center">
                            {{ $movimiento->id }}
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            {{ ucfirst($movimiento->tipo ?? 'N/A') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            @php $linea = $movimiento->pedido_producto_id; @endphp

                            @if ($linea)
                            <a href="{{ route('pedidos.index', ['pedido_producto_id' => $linea]) }}"
                                class="text-indigo-600 hover:underline">
                                #{{ $linea }}
                            </a>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            @if ($movimiento->productoBase)
                            {{ ucfirst(strtolower($movimiento->productoBase->tipo)) }}
                            (Ø{{ $movimiento->productoBase->diametro }}{{ strtolower($movimiento->productoBase->tipo) === 'barra' ? ', ' . $movimiento->productoBase->longitud . ' m' : '' }})
                            @else
                            <span class="text-gray-400 italic">Sin datos</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center"
                            title="{{ $movimiento->descripcion }}">
                            {{ Str::limit($movimiento->descripcion, 50) ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            @if ($movimiento->nave)
                            {{ $movimiento->nave->obra }}
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            @if ($movimiento->prioridad == 1)
                            <span class="badge bg-secondary">Baja</span>
                            @elseif ($movimiento->prioridad == 2)
                            <span class="badge bg-warning">Media</span>
                            @elseif ($movimiento->prioridad == 3)
                            <span class="badge bg-danger">Alta</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            @if ($movimiento->solicitadoPor)
                            <a href="{{ route('users.index', ['id' => $movimiento->solicitadoPor->id]) }}"
                                class="text-blue-500 hover:underline">
                                {{ $movimiento->solicitadoPor->nombre_completo }}
                            </a>
                            @else
                            <span class="text-gray-400 text-center align-middle">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            @if ($movimiento->ejecutadoPor)
                            <a href="{{ route('users.index', ['id' => $movimiento->ejecutadoPor->id]) }}"
                                class="text-green-600 hover:underline">
                                {{ $movimiento->ejecutadoPor->nombre_completo }}
                            </a>
                            @else
                            <span class="text-gray-400 text-center align-middle">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            <span
                                class="badge {{ $movimiento->estado === 'pendiente' ? 'bg-warning' : 'bg-success' }}">
                                {{ ucfirst($movimiento->estado) }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            {{ $movimiento->fecha_solicitud ?? '—' }}
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            {{ $movimiento->fecha_ejecucion ?? '—' }}
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            {{ $movimiento->ubicacionOrigen->nombre ?? ($movimiento->maquinaOrigen->nombre ?? '—') }}
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            {{ $movimiento->ubicacionDestino->nombre ?? ($movimiento->maquinaDestino->nombre ?? '—') }}
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            @if ($movimiento->producto)
                            <a href="{{ route('productos.index', ['id' => $movimiento->producto->id]) }}"
                                class="text-blue-500 hover:underline">
                                {{ $movimiento->producto->codigo }}
                            </a>
                            @elseif ($movimiento->paquete)
                            <a href="{{ route('paquetes.index', ['id' => $movimiento->paquete->id]) }}"
                                class="text-blue-500 hover:underline">
                                {{ $movimiento->paquete->codigo }}
                            </a>
                            @else
                            —
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            <x-tabla.boton-eliminar :action="route('movimientos.destroy', $movimiento->id)" />
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
        <x-tabla.paginacion :paginador="$registrosMovimientos" />
        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
            function generateAndPrintQR(data) {
                // Reemplazamos los caracteres problemáticos antes de generar el QR
                const safeData = data.replace(/_/g, '%5F'); // Reemplazamos _ por %5F

                // Elimina cualquier contenido previo del canvas
                const qrContainer = document.getElementById('qrCanvas');
                qrContainer.innerHTML = ""; // Limpia el canvas si ya existe un QR previo

                // Generar el código QR con el texto seguro
                const qrCode = new QRCode(qrContainer, {
                    text: safeData, // Usamos el texto transformado
                    width: 200,
                    height: 200,
                });

                // Esperar a que el QR esté listo para imprimir
                setTimeout(() => {
                    const qrImg = qrContainer.querySelector('img'); // Obtiene la imagen del QR
                    if (!qrImg) {
                        alert("Error al generar el QR. Intenta nuevamente.");
                        return;
                    }

                    // Abrir ventana de impresión
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(`
                  <html>
                      <head><title>Imprimir QR</title></head>
                      <body>
                          <img src="${qrImg.src}" alt="Código QR" style="width:100px">
                   
                          <script>window.print();<\/script>
                      </body>
                  </html>
              `);
                    printWindow.document.close();
                }, 500); // Tiempo suficiente para generar el QR
            }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                let form = document.getElementById('miFormulario'); // Asegúrate de poner el ID correcto del formulario

                form.addEventListener("submit", function(event) {
                    event.preventDefault(); // Evita el envío inmediato

                    let formData = new FormData(form);

                    fetch(form.action, {
                            method: form.method,
                            body: formData,
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.confirm) {
                                Swal.fire({
                                    title: 'Material en fabricación',
                                    text: data.message,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, continuar',
                                    cancelButtonText: 'Cancelar'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        form
                                            .submit(); // Si el usuario confirma, enviar el formulario
                                    }
                                });
                            } else {
                                form.submit();
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            });
        </script>

</x-app-layout>