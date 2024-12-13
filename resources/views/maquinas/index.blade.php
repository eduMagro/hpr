<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Máquinas') }}
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
            <!-- Botón para crear una nueva maquina con estilo Bootstrap -->
            <div class="mb-4">
                <a href="{{ route('maquinas.create') }}" class="btn btn-primary">
                    Crear Nueva Máquina
                </a>
            </div>
        <!-- FORMULARIO DE BUSQUEDA -->
        <form method="GET" action="{{ route('maquinas.index') }}" class="form-inline mt-3 mb-3">
            <input type="text" name="nombre" class="form-control mb-3" placeholder="Buscar por código QR"
                value="{{ request('nombre') }}">
            <button type="submit" class="btn btn-info ml-2">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>

        <!-- Usamos una estructura de tarjetas para mostrar las ubicaciones -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @if (isset($registrosMaquina) &&
                    $registrosMaquina instanceof \Illuminate\Pagination\LengthAwarePaginator &&
                    $registrosMaquina->isNotEmpty())
                @forelse ($registrosMaquina as $maquina)
                    <div class="bg-white border p-4 shadow-md rounded-lg">
                        <h3 class="font-bold text-xl break-words">{{ $maquina->codigo }}</h3>
                        <p><strong>Nombre Máquina:</strong> {{ $maquina->nombre }}</p>
                        <p>

                            <button id="generateQR" onclick="generateAndPrintQR('{{ $maquina->codigo }}')"
                                class="btn btn-primary">QR</button>
                        </p>
                        <div id="qrCanvas" style="display:none;"></div>

                        <p><strong>Diámetros aceptados: </strong>{{ $maquina->diametro_min . " - " . $maquina->diametro_max }}</p>
                        <p><strong>Pesos bobinas: </strong>{{ ($maquina->peso_min && $maquina->peso_max) ? ($maquina->peso_min . ' - ' . $maquina->peso_max) : 'Barras' }}</p>
                        <!-- Mostrar los productos que contiene esta ubicación -->
                        <h4 class="mt-4 font-semibold">Productos en máquina:</h4>
                        @if ($maquina->productos->isEmpty())
                            <p>No hay productos en esta máquina.</p>
                        @else
                            <ul class="list-disc pl-6 break-words">
                                @foreach ($maquina->productos as $producto)
                                    <li class="mb-2 flex items-center justify-between">
                                        <span>
                                            ID: {{ $producto->id }} - Tipo: {{ $producto->tipo }} - D{{ $producto->diametro }} - L{{ $producto->longitud ?? '??' }}
                                        </span>
                                        <a href="{{ route('productos.show', $producto->id) }}" class="btn btn-sm btn-primary">Ver</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        
                        <hr style="border: 1px solid #ccc; margin: 10px 0;">
                        <div class="mt-4 flex justify-between">
                            <!-- Enlace para editar -->
                            <a href="{{ route('maquinas.edit', $maquina->id) }}"
                                class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                            <!-- Formulario para eliminar -->
                            <form action="{{ route('maquinas.destroy', $maquina->id) }}" method="POST"
                                style="display:inline;"
                                onsubmit="return confirm('¿Estás seguro de querer eliminar esta máquina?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p>No hay máquinas disponibles.</p> <!-- Mensaje si no hay datos -->
                @endforelse
            @endif
        </div>
        <!-- Paginación -->
        @if (isset($registrosMaquina) && $registrosMaquina instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $registrosMaquina->appends(request()->except('page'))->links() }}
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
