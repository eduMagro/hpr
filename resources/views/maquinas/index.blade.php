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
    {{-- mostrar mensaje de exito --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    {{-- mision abortada --}}
    @if (session('abort'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Acceso denegado',
                text: "{{ session('abort') }}",
            });
        </script>
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

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

                        <p><strong>Diámetros aceptados:
                            </strong>{{ $maquina->diametro_min . ' - ' . $maquina->diametro_max }}</p>
                        <p><strong>Pesos bobinas:
                            </strong>{{ $maquina->peso_min && $maquina->peso_max ? $maquina->peso_min . ' - ' . $maquina->peso_max : 'Barras' }}
                        </p>
                        <!-- Mostrar los productos que contiene esta ubicación -->
                        <h4 class="mt-4 font-semibold">Productos en máquina:</h4>
                        @if ($maquina->productos->isEmpty())
                            <p>No hay productos en esta máquina.</p>
                        @else
                            <ul class="list-disc pl-6 break-words">
                                @foreach ($maquina->productos as $producto)
                                    <li class="mb-2 flex items-center justify-between">
                                        <span>

                                            ID{{ $producto->id }} - Tipo: {{ $producto->tipo }} -
                                            D{{ $producto->diametro }} - L{{ $producto->longitud ?? '??' }}
                                        </span>
                                        <a href="{{ route('productos.show', $producto->id) }}"
                                            class="btn btn-sm btn-primary">Ver</a>
                                        @if ($producto->tipo == 'encarretado')
                                            <div
                                                style="width: 100px; height: 100px; background-color: #ddd; position: relative; overflow: hidden;">
                                                <div class="cuadro verde"
                                                    style="width: 100%; 
														height: {{ ($producto->peso_stock / $producto->peso_inicial) * 100 }}%; 
														background-color: green; 
														position: absolute; 
														bottom: 0;">
                                                </div>
                                                <span style="position: absolute; top: 10px; left: 10px; color: white;">
                                                    {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                                </span>
                                            </div>
                                        @endif

                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <hr style="border: 1px solid #ccc; margin: 10px 0;">

                        <div class="mt-4 flex justify-between items-center">
                            {{-- sweet alert para eliminar --}}
                            <x-boton-eliminar :action="route('maquinas.destroy', $maquina->id)" />

                            <!-- Enlace para editar -->
                            <a href="{{ route('maquinas.edit', $maquina->id) }}"
                                class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                            {{-- Enlace para ver --}}
                            <a href="javascript:void(0);" onclick="seleccionarCompañero({{ $maquina->id }})"
                                class="text-blue-500 hover:text-blue-700 text-sm">Iniciar Sesión</a>

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
                          <p>${safeData}</p>
                          <script>window.print();<\/script>
                      </body>
                  </html>
              `);
                printWindow.document.close();
            }, 500); // Tiempo suficiente para generar el QR
        }
    </script>
    <script>
        function seleccionarCompañero(maquinaId) {
            let opciones = usuarios.map(usuario => `<option value="${usuario.id}">${usuario.nombre}</option>`).join('');

            Swal.fire({
                title: 'Seleccionar Compañero',
                html: `
                <select id="users_id_2" class="swal2-input">
                    ${opciones}
                </select>
            `,
                showCancelButton: true,
                confirmButtonText: 'Iniciar Sesión',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const users_id_2 = document.getElementById('users_id_2').value;
                    if (!users_id_2) {
                        Swal.showValidationMessage('Debes seleccionar un compañero');
                        return false;
                    }
                    return fetch('{{ route('maquinas.sesion.guardar') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            maquina_id: maquinaId,
                            users_id_2: users_id_2
                        })
                    }).then(response => {
                        if (!response.ok) {
                            throw new Error(response.statusText);
                        }
                        return response.json();
                    }).catch(error => {
                        Swal.showValidationMessage(`Error: ${error}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `/maquinas/${maquinaId}`; // Redirige a la vista de la máquina
                }
            });
        }
    </script>
</x-app-layout>
