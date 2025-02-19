function generateAndPrintQR(id, nombre, tipo) {
    const qrContainerId = `qrContainer-${id}`;
    let qrContainer = document.getElementById(qrContainerId);

    if (!qrContainer) {
        qrContainer = document.createElement("div");
        qrContainer.id = qrContainerId;
        qrContainer.style.display = "none";
        document.body.appendChild(qrContainer);
    }

    qrContainer.innerHTML = ""; // Limpia cualquier QR anterior

    // Definir tamaño del QR en función del tipo
    let qrSize =
        tipo.toLowerCase() === "ubicacion" || tipo.toLowerCase() === "maquina"
            ? 640
            : 420;

    // Generamos el código QR
    const qrCode = new QRCode(qrContainer, {
        text: id.toString(),
        width: qrSize,
        height: qrSize,
    });

    // Esperar a que la imagen del QR se genere
    setTimeout(() => {
        const qrImg = qrContainer.querySelector("img");
        if (!qrImg) return;

        // Crear una nueva ventana con contenido para imprimir
        const printWindow = window.open("", "_blank");
        printWindow.document.write(`
            <html>
                <head>
                    <title>Imprimir QR</title>
                    <style>
                        body {
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            height: 100vh;
                            margin: 0;
                            font-family: 'Arial', sans-serif;
                            background-color: #f4f4f9;
                        }
                        .qr-card {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            padding: 15px;
                            border: 1px solid #000; /* Borde negro de 1px */
                            border-radius: 8px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                            background-color: #ffffff;
                            text-align: center;
                            width: ${
                                qrSize + 60
                            }px; /* Ajusta el ancho en función del QR */
                        }
                        .tipo, .id, .nombre {
                            width: 100%;
                            padding: 5px;
                            border: 1px solid #000; /* Borde para cada sección */
							 
                            text-align: center;
                        }
                        .tipo {
                            font-weight: bold;
                            font-size: 18px;
                            text-transform: uppercase;
							border-radius: 8px 8px 0 0;

                        }
                        .id {
                            font-size: 32px;
                            color: #555;
                        }
                        .nombre {
                            font-size: 60px;
                            font-weight: 500;
							border-radius: 0 0 8px 8px;
                        }
                        img {
                            width: ${qrSize}px;
                            height: ${qrSize}px;
                            padding: 5px;
                            margin-bottom: 10px;
                        }
                    </style>
                </head>
                <body>
                    <div class="qr-card">
                        <img src="${qrImg.src}" alt="Código QR">
                        <div class="tipo">${tipo}</div>
                        <div class="id">ID: ${id}</div>
                        <div class="nombre">${nombre}</div>
                    </div>
                    <script>
                        window.print();
                        setTimeout(() => window.close(), 500);
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }, 500); // Pequeño retraso para asegurar la carga del QR
}
