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


    <div class="container mt-5">
        <h2 class="text-center mb-4">Mapa de Ubicaciones</h2>
        @foreach ($ubicacionesPorSector as $sector => $ubicaciones)
            <div class="mb-4 sector-card">
                <h3 class="sector-header">Sector {{ $sector }}</h3>
                <div class="mapa-sector">
                    @foreach ($ubicaciones as $ubicacion)
                        <div class="ubicacion">
                            <span>{{ $ubicacion->ubicacion }}</span>
                            <small>{{ $ubicacion->descripcion }}</small>
                            <!-- Mostrar los productos que contiene esta ubicación -->
                            @if ($ubicacion->productos->isEmpty())
                                <p class="text-gray-500 italic text-xs">No hay material en esta ubicación.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($ubicacion->productos as $producto)
                                        <div class="bg-gray-100 rounded-lg p-1 shadow-md text-center">
                                            <p class="text-xs text-gray-700 font-semibold">
                                                ID: {{ $producto->id }} | Ø {{ $producto->diametro }} mm
                                            </p>
                                            <a href="{{ route('productos.show', $producto->id) }}"
                                                class="mt-2 inline-block bg-blue-500 text-white text-xs px-3 py-1 rounded-md hover:bg-blue-600 transition">
                                                Ver
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif



                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <style>
        /* Contenedor de cada sector */
        .sector-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }

        /* Título del sector */
        .sector-header {
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 1.2rem;
            margin: 0;
        }

        /* Contenedor en grid para las ubicaciones */
        .mapa-sector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            /* Columnas automáticas */
            gap: 10px;
            padding: 15px;
            justify-items: center;
        }

        /* Estilos de cada ubicación */
        .ubicacion {
            background-color: #e3f2fd;
            border: 1px solid #007bff;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            border-radius: 5px;
            min-width: 100px;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Efecto hover */
        .ubicacion:hover {
            background-color: #90caf9;
            cursor: pointer;
        }

        /* Ajuste de tamaño para pantallas pequeñas */
        @media (max-width: 768px) {
            .mapa-sector {
                grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            }
        }
    </style>
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
