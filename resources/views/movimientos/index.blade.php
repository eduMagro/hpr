<x-app-layout>
    <x-slot name="title">Movimientos - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Movimientos') }}
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <!-- Botón para crear un nuevo movimiento con estilo Bootstrap -->
        <div class="mb-4">
            <a href="{{ route('movimientos.create') }}" class="btn btn-primary">
                Crear Movimiento
            </a>
            <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosBusqueda">
                🔍 Filtros Avanzados
            </button>
        </div>
        <div id="filtrosBusqueda" class="collapse">
            <form method="GET" action="{{ route('movimientos.index') }}" class="card card-body shadow-sm">
                <div class="row g-3">

                    <!-- Filtros de texto -->
                    <div class="col-md-4">
                        <input type="text" name="movimiento_id" class="form-control" placeholder="ID de Movimiento"
                            value="{{ request('movimiento_id') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="usuario" class="form-control" placeholder="Nombre de Usuario"
                            value="{{ request('usuario') }}">
                    </div>

                    <!-- Filtros de búsqueda por Producto o Paquete -->
                    <div class="col-md-4">
                        <input type="text" name="producto_id" class="form-control" placeholder="ID Producto"
                            value="{{ request('producto_id') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="paquete_id" class="form-control" placeholder="ID Paquete"
                            value="{{ request('paquete_id') }}">
                    </div>

                    <!-- Filtros de fechas -->
                    <div class="col-md-4">
                        <label for="fecha_inicio">Desde:</label>
                        <input type="date" name="fecha_inicio" class="form-control"
                            value="{{ request('fecha_inicio') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="fecha_finalizacion">Hasta:</label>
                        <input type="date" name="fecha_finalizacion" class="form-control"
                            value="{{ request('fecha_finalizacion') }}">
                    </div>

                    <!-- Registros por página -->
                    <div class="col-md-4">
                        <label for="per_page">Mostrar:</label>
                        <select name="per_page" class="form-control">
                            <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="col-12 d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="{{ route('movimientos.index') }}" class="btn btn-warning">
                            <i class="fas fa-undo"></i> Resetear Filtros
                        </a>
                    </div>

                </div>
            </form>
        </div>


        <div class="overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Movimiento ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Destino</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto Asociado
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registrosMovimientos as $movimiento)
                        <tr class="border-b">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $movimiento->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <a href="{{ route('users.index', ['id' => $movimiento->usuario->id]) }}"
                                    class="text-blue-500 hover:underline">
                                    {{ $movimiento->usuario->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if ($movimiento->ubicacionOrigen && $movimiento->ubicacionOrigen->nombre)
                                    {{ $movimiento->ubicacionOrigen->nombre }}
                                @elseif ($movimiento->maquinaOrigen && $movimiento->maquinaOrigen->nombre)
                                    {{ $movimiento->maquinaOrigen->nombre }}
                                @else
                                    Sin origen
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if ($movimiento->ubicacionDestino && $movimiento->ubicacionDestino->nombre)
                                    {{ $movimiento->ubicacionDestino->nombre }}
                                @elseif ($movimiento->maquina && $movimiento->maquina->nombre)
                                    {{ $movimiento->maquina->nombre }}
                                @else
                                    Sin destino
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if ($movimiento->producto)
                                    <a href="{{ route('productos.index', ['id' => $movimiento->producto->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        Materia Prima #{{ $movimiento->producto->id }}
                                    </a>
                                @elseif ($movimiento->paquete)
                                    <a href="{{ route('paquetes.index', ['id' => $movimiento->paquete->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        Paquete #{{ $movimiento->paquete->id }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <x-boton-eliminar :action="route('movimientos.destroy', $movimiento->id)" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-center">
            {{ $registrosMovimientos->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
        </div>
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
