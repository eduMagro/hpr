<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">

            {{ __('Materia Prima Almacenada') }}

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
        <!-- FORMULARIO DE BUSQUEDA -->
        <form method="GET" action="{{ route('productos.index') }}" class="form-inline mt-3 mb-3">
            <input type="text" name="id" class="form-control mb-3" placeholder="Buscar por QR"
                value="{{ request('id') }}">
            <button type="submit" class="btn btn-info ml-2">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>

        <!-- Tarjetas de productos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($registrosProductos as $producto)
                <div class="bg-white shadow-md rounded-lg p-4">

                    <h3 class="font-bold text-lg text-gray-700">ID Materia Prima: {{ $producto->id }}</h3>

                    <p><strong>Fabricante:</strong> {{ $producto->fabricante }}</p>
                    <p><strong>Nombre:</strong> {{ $producto->nombre }}</p>
                    <p><strong>Tipo:</strong> {{ $producto->tipo }}</p>
                    <p><strong>Diámetro:</strong> {{ $producto->diametro }}</p>
                    <p><strong>Longitud:</strong> {{ $producto->longitud ?? 'N/A' }}</p>
                    <p><strong>Nº Colada:</strong> {{ $producto->n_colada }}</p>
                    <p><strong>Nº Paquete:</strong> {{ $producto->n_paquete }}</p>
                    <p><strong>Peso Inicial:</strong> {{ $producto->peso_inicial }} kg</p>
                    <p><strong>Peso Stock:</strong> {{ $producto->peso_stock }} kg</p>
                    <p><strong>Estado:</strong> {{ $producto->estado }}</p>
                    <p><strong>Otros:</strong> {{ $producto->otros ?? 'N/A' }}</p>
                    <p>
                        <button onclick="generateAndPrintQR('{{ 'MP' . $producto->id }}')" class="btn btn-primary">QR</button>
                    </p>
                    <div id="qrCanvas{{ $producto->id }}" style="display:none;"></div>

                    <hr class="m-2 border-gray-300">

                    <!-- Detalles de Ubicación o Máquina -->
                    @if (isset($producto->ubicacion->descripcion))
                        <p class="font-bold text-lg text-gray-800 break-words">
                            {{ $producto->ubicacion->descripcion }}</p>
                    @elseif (isset($producto->maquina->nombre))
                        <p class="font-bold text-lg text-gray-800 break-words">{{ $producto->maquina->nombre }}
                        </p>
                    @else
                        <p class="font-bold text-lg text-gray-800 break-words">No está ubicada</p>
                    @endif
                    <p class="text-gray-600 mt-2">{{ $producto->created_at->format('d/m/Y H:i') }}</p>

                    <hr class="my-2 border-gray-300">
                    <div class="mt-2 flex justify-between">
                           {{-- sweet alert para eliminar --}}
                           <x-boton-eliminar :action="route('productos.destroy', $producto->id)" />
                        <!-- Enlace para editar -->
                        <a href="{{ route('productos.edit', $producto->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                       <a href="{{ route('productos.show', $producto->id)}}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-4">No hay productos disponibles.</div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if (isset($registrosProductos) && $registrosProductos instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $registrosProductos->appends(request()->except('page'))->links() }}
        @endif
    </div>
     <!-- SCRIPT PARA IMPRIMIR QR -->
     <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
     <script>
         function generateAndPrintQR(id) {

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
