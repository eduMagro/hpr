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
            ? 200
            : 200;

    // Generamos el código QR
    const qrCode = new QRCode(qrContainer, {
        text: id.toString(),
        width: qrSize,
        height: qrSize,
    });
    const partesNombre = nombre.split(",");
    const sector = partesNombre[1]?.trim() || "";
    const ubicacion = partesNombre[2]?.trim() || "";

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
                                border: 1px solid #000;
                                border-radius: 8px;
                                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                                background-color: #ffffff;
                                text-align: center;
                                width: ${qrSize + 200}px;
                            }
                                .tipo{
                                  font-size: 200px;
                                }
                            .tipo, .id, .nombre {
                                width: 100%;
                                padding: 5px;
                                border: 1px solid #000;
                               /* ✅ tamaño actualizado */
                                text-align: center;
                            }
                            .tipo {
                                font-weight: bold;
                                text-transform: uppercase;
                                border-radius: 8px 8px 0 0;
                            }
                            .id {
                            
                                color: #555;
                            }
                            .nombre {
                              font-size: 64px;
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
                        <div class="tipo">${id}</div>
                      <div class="nombre">${sector}</div>
<div class="nombre">${ubicacion}</div>

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
