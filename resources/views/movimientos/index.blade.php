<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Movimientos') }}
        </h2>
    </x-slot>

    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Mostrar mensajes de éxito o error -->
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="container mx-auto px-4 py-6">
        <!-- Botón para crear un nuevo movimiento con estilo Bootstrap -->
        <div class="mb-4">
            <a href="{{ route('movimientos.create') }}" class="btn btn-primary">
                Crear Movimiento
            </a>
        </div>
        <!-- FORMULARIO DE BUSQUEDA -->
        <form method="GET" action="{{ route('movimientos.index') }}" class="form-inline mt-3 mb-3">

            <input type="text" name="producto_id" class="form-control mb-3" placeholder="Buscar por QR de producto"
                value="{{ request('producto_id') }}">
            <input type="text" name="nombre_usuario" class="form-control mb-3"
                placeholder="Buscar por nombre de usuario" value="{{ request('nombre_usuario') }}">

            <button type="submit" class="btn btn-info ml-2">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @if ($registrosMovimientos instanceof \Illuminate\Pagination\LengthAwarePaginator && $registrosMovimientos->isNotEmpty())
                @foreach ($registrosMovimientos as $movimiento)
                    <div class="bg-white border p-6 shadow-md rounded-lg">
                        <!-- Encabezado -->
                        <h3 class="font-bold text-lg text-gray-700">Movimiento #{{ $movimiento->id }}</h3>
                        <p class="text-sm text-gray-500">Fecha: {{ $movimiento->created_at->format('d/m/Y H:i') }}</p>

                        <!-- Información del Movimiento -->
                        <div class="mt-3">
                            <p><strong>Usuario:</strong> {{ $movimiento->usuario->name ?? 'Usuario desconocido' }}</p>
                            <div class="border-t my-3"></div>

                            <!-- Origen -->
                            <p><strong>Origen:</strong>
                                @if ($movimiento->ubicacionOrigen && $movimiento->ubicacionOrigen->descripcion)
                                    {{ $movimiento->ubicacionOrigen->descripcion }}
                                @elseif ($movimiento->maquinaOrigen && $movimiento->maquinaOrigen->nombre)
                                    {{ $movimiento->maquinaOrigen->nombre }}
                                @else
                                    Sin origen
                                @endif
                            </p>

                            <!-- Destino -->
                            <p><strong>Destino:</strong>
                                @if ($movimiento->ubicacionDestino && $movimiento->ubicacionDestino->descripcion)
                                    {{ $movimiento->ubicacionDestino->descripcion }}
                                @elseif ($movimiento->maquina && $movimiento->maquina->nombre)
                                    {{ $movimiento->maquina->nombre }}
                                @else
                                    Sin destino
                                @endif
                            </p>
                        </div>

                        <div class="border-t my-3"></div>

                        <!-- Producto Asociado -->
                        <h4 class="font-semibold text-gray-600">Producto asociado:</h4>
                        @if ($movimiento->producto)
                            <p>{{ 'ID: ' . $movimiento->producto->id }}</p>
                            <p>{{ 'Tipo de producto: ' . $movimiento->producto->tipo }}</p>
                            <p>{{ 'Diámetro: ' . $movimiento->producto->diametro }}</p>
                            <p>{{ 'Longitud: ' . $movimiento->producto->longitud }}</p>
                            <a href="{{ route('productos.show', $movimiento->producto->id) }}"
                                class="btn btn-sm btn-primary">Ver</a>
                        @else
                            <p class="text-sm text-gray-500">No hay producto asociado a este movimiento.</p>
                        @endif

                        <div class="border-t my-3"></div>

                        <!-- Botones de acción -->
                        <div class="flex justify-between mt-4">
                            <form action="{{ route('movimientos.destroy', $movimiento->id) }}" method="POST"
                                onsubmit="return confirm('¿Estás seguro de querer eliminar este movimiento?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-gray-500">No hay movimientos registrados ahora mismo.</p>
            @endif
        </div>


        <!-- Paginación -->
        @if (isset($registrosMovimientos) && $registrosMovimientos instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $registrosMovimientos->appends(request()->except('page'))->links() }}
        @endif
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
