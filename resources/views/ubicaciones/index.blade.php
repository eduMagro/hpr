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
                                <small>No hay materia Prima en esta ubicación.</small>
                            @else
                                <ul class="list-disc pl-6 break-words">
                                    @foreach ($ubicacion->productos as $producto)
                                        <li>{{ 'ID' . $producto->id . ' - ' . $producto->tipo . ' - D' . $producto->diametro . ' - L' . $producto->longitud . ' - PI' . $producto->peso_inicial . ' - PS' . $producto->peso_stock }}
                                        </li>
                                        <a href="{{ route('productos.show', $producto->id) }}"
                                            class="btn btn-sm btn-primary">Ver</a>
                                    @endforeach
                                </ul>
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
