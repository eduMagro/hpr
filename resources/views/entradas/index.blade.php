<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Entradas de Material') }}
        </h2>
    </x-slot>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
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
        <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
        <div class="mb-4">
            <a href="{{ route('entradas.create') }}" class="btn btn-primary">
                Crear Nueva Entrada
            </a>
        </div>
        <!-- Usamos una estructura de tarjetas para dispositivos móviles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($entradas as $entrada)
                <div class="bg-white border p-4 shadow-md rounded-lg">
                    <h3 class="font-bold text-xl">{{ $entrada->albaran }}</h3>

                    <p><strong>Usuario:</strong> {{ $entrada->user->name ?? 'Sin usuario' }}</p>
                    <p><strong>Fecha:</strong> {{ $entrada->created_at }}</p>

                    <h4 class="mt-4 font-semibold">Productos Asociados: {{ $entrada->productos->count() }}</h4>
                    <hr style="border: 1px solid #ccc; margin: 10px 0;">
                    <ul>
                        @foreach ($entrada->productos as $producto)
                            <li class="mt-2">
                                <p><strong>ID:</strong> {{ $producto->id }}</p>
                                <a href="{{ route('productos.show', $producto->id) }}" class="btn btn-sm btn-primary">Ver</a>
                                <p><strong>Producto:</strong> {{ $producto->nombre }} / {{ $producto->tipo }}</p>
                                <p><strong>Diámetro:</strong> {{ $producto->diametro }}</p>
                                <p><strong>Longitud:</strong> {{ $producto->longitud }}</p>
                                <!-- Lista desordenada con los detalles del producto -->
                                @if (isset($producto->ubicacion->descripcion))
                                    <p><strong>Ubicación:</strong>
                                        {{ $producto->ubicacion->descripcion }}</p>
                                @elseif (isset($producto->maquina->nombre))
                                    <p><strong>Máquina:</strong>
                                        {{ $producto->maquina->nombre }}
                                    </p>
                                @else
                                    <p class="font-bold text-lg text-gray-800 break-words">No está ubicada</p>
                                @endif
                                <p><strong>Otros:</strong> {{ $producto->otros ?? 'N/A' }}</p>
                                <p>

                                    <button id="generateQR" onclick="generateAndPrintQR('{{ $producto->id }}')"
                                        class="btn btn-primary">Imprimir QR</button>
                                </p>
                                <div id="qrCanvas" style="display:none;"></div>

                            </li>
                            <hr style="border: 1px solid #ccc; margin: 10px 0;">
                        @endforeach

                    </ul>

                    <div class="mt-4 flex justify-between">
                        <!-- Enlace para editar -->
                        <a href="{{ route('entradas.edit', $entrada->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>

                        <!-- Formulario para eliminar -->
                        <form action="{{ route('entradas.destroy', $entrada->id) }}" method="POST"
                            style="display:inline;"
                            onsubmit="return confirm('¿Estás seguro de querer eliminar esta entrada de material?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p>No hay entradas de material disponibles.</p> <!-- Mensaje si no hay datos -->
            @endforelse
        </div>

        <!-- Paginación -->
        @if (isset($entradas) && $entradas instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $entradas->appends(request()->except('page'))->links() }}
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

</x-app-layout>
