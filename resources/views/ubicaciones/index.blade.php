<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ubicaciones') }}
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

    <div class="container mx-auto px-3 py-6">
            <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
            <div class="mb-4">
                <a href="{{ route('ubicaciones.create') }}" class="btn btn-primary">
                    Crear Nueva Ubicación
                </a>
            </div>
        <!-- FORMULARIO DE BUSQUEDA -->
        <form method="GET" action="{{ route('ubicaciones.index') }}" class="form-inline mt-3 mb-3">
            <input type="text" name="id" class="form-control mb-3" placeholder="Buscar ubicación por QR"
                value="{{ request('id') }}">
            <button type="submit" class="btn btn-info ml-2">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>

        <!-- Usamos una estructura de tarjetas para mostrar las ubicaciones -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @if (isset($registrosUbicaciones) &&
                    $registrosUbicaciones instanceof \Illuminate\Pagination\LengthAwarePaginator &&
                    $registrosUbicaciones->isNotEmpty())
                @forelse ($registrosUbicaciones as $ubicacion)
                    <div class="bg-white border p-4 shadow-md rounded-lg">
                        <h3 class="font-bold text-xl break-words">{{ $ubicacion->codigo }}</h3>
                        <p><strong>Descripción:</strong> {{ $ubicacion->descripcion }}</p>
                        <p>

                            <button id="generateQR" onclick="generateAndPrintQR('{{ $ubicacion->id }}', '{{ $ubicacion->codigo }}')"
                                class="btn btn-primary">QR</button>
                        </p>
                        <div id="qrCanvas" style="display:none;"></div>


                        <!-- Mostrar los productos que contiene esta ubicación -->
                        <h4 class="mt-4 font-semibold">Materia prima en esta ubicación:</h4>
                        @if ($ubicacion->productos->isEmpty())
                            <p>No hay materia Prima en esta ubicación.</p>
                        @else
                            <ul class="list-disc pl-6 break-words">
                                @foreach ($ubicacion->productos as $producto)
                                    <li>{{ 'ID' . $producto->id . ' - ' . $producto->tipo . ' - D' . $producto->diametro . ' - L' . $producto->longitud . ' - PI' . $producto->peso_inicial . ' - PS' . $producto->peso_stock}}</li>
                                    <a href="{{ route('productos.show', $producto->id) }}" class="btn btn-sm btn-primary">Ver</a>
                                @endforeach
                            </ul>
                        @endif
                        <hr style="border: 1px solid #ccc; margin: 10px 0;">
                        <div class="mt-4 flex justify-between">
                            <!-- Enlace para editar -->
                            <a href="{{ route('ubicaciones.edit', $ubicacion->id) }}"
                                class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                            <!-- Formulario para eliminar -->
                            <form action="{{ route('ubicaciones.destroy', $ubicacion->id) }}" method="POST"
                                style="display:inline;"
                                onsubmit="return confirm('¿Estás seguro de querer eliminar esta ubicación?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p>No hay ubicaciones disponibles.</p> <!-- Mensaje si no hay datos -->
                @endforelse
            @endif
        </div>
        <!-- Paginación -->
        @if (isset($registrosUbicaciones) && $registrosUbicaciones instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $registrosUbicaciones->appends(request()->except('page'))->links() }}
        @endif
    </div>
    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        function generateAndPrintQR(id, codigo) {
            // Validamos que el ID sea válido
            if (!id || isNaN(id)) {
                alert("El ID proporcionado no es válido. Por favor, verifica.");
                return;
            }
    
            // Limpiamos el contenedor del QR
            const qrContainer = document.getElementById('qrCanvas');
            qrContainer.innerHTML = ""; // Elimina cualquier QR previo
    
            // Generamos el QR con el ID
            const qrCode = new QRCode(qrContainer, {
                text: id.toString(), // Usamos el ID convertido a texto
                width: 100,
                height: 200,
            });
    
            // Esperamos a que el QR esté listo antes de imprimirlo
            setTimeout(() => {
                const qrImg = qrContainer.querySelector('img'); // Obtenemos la imagen del QR
                if (!qrImg) {
                    alert("Error al generar el QR. Intenta nuevamente.");
                    return;
                }
    
                // Creamos una ventana para la impresión
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <body>
                            <img src="${qrImg.src}" alt="Código QR" style="width:200px; height:200px;">
                            <p>${codigo}</p>
                            <script>
                                window.print();
                                setTimeout(() => window.close(), 1000); // Cierra la ventana después de imprimir
                            <\/script>
                        </body>
                    </html>
                `);
                printWindow.document.close();
            }, 500); // Tiempo de espera para que el QR se genere completamente
        }
    </script>
    
</x-app-layout>
