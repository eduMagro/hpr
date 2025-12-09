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
            ? 340
            : 120;

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
                            font-size: 16px;
                            text-transform: uppercase;
							border-radius: 8px 8px 0 0;

                        }
                        .id {
                            font-size: 14px;
                            color: #555;
                        }
                        .nombre {
                            font-size: 14px;
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

/**
 * Genera e imprime QR para paquetes con información detallada
 * Formato A6 (105mm x 148mm) - Diseño optimizado para ahorro de tinta
 */
function generateAndPrintQRPaquete(data) {
    const { codigo, planilla, cliente, obra, descripcion, seccion, ensamblado, peso, etiquetas } = data;

    const qrContainerId = `qrContainer-paquete-${codigo}`;
    let qrContainer = document.getElementById(qrContainerId);

    if (!qrContainer) {
        qrContainer = document.createElement("div");
        qrContainer.id = qrContainerId;
        qrContainer.style.display = "none";
        document.body.appendChild(qrContainer);
    }

    qrContainer.innerHTML = "";

    // QR tamaño ajustado para A6
    const qrSize = 120;

    const qrCode = new QRCode(qrContainer, {
        text: codigo.toString(),
        width: qrSize,
        height: qrSize,
    });

    setTimeout(() => {
        const qrImg = qrContainer.querySelector("img");
        if (!qrImg) return;

        const printWindow = window.open("", "_blank");
        printWindow.document.write(`
            <html>
                <head>
                    <title>QR Paquete - ${codigo}</title>
                    <style>
                        @page {
                            size: 105mm 148mm;
                            margin: 4mm;
                        }
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        body {
                            font-family: Arial, sans-serif;
                            background: #fff;
                            width: 97mm;
                            height: 140mm;
                            padding: 2mm;
                        }
                        .card {
                            width: 100%;
                            height: 100%;
                            border: 1.5px solid #000;
                            display: flex;
                            flex-direction: column;
                        }
                        .header {
                            border-bottom: 1.5px solid #000;
                            padding: 4px 8px;
                            text-align: center;
                        }
                        .header h1 {
                            font-size: 16px;
                            font-weight: bold;
                            margin: 0;
                            letter-spacing: 1px;
                        }
                        .header .planilla {
                            font-size: 11px;
                            color: #333;
                        }
                        .qr-codigo {
                            display: flex;
                            justify-content: center;
                            padding: 8px;
                        }
                        .qr-codigo img {
                            width: ${qrSize}px;
                            height: ${qrSize}px;
                        }
                        .codigo-section {
                            text-align: center;
                            padding: 4px;
                            border-bottom: 1px solid #000;
                        }
                        .codigo-section .codigo {
                            font-size: 16px;
                            font-weight: bold;
                            font-family: 'Courier New', monospace;
                        }
                        .info-section {
                            flex: 1;
                            padding: 4px 8px;
                            font-size: 10px;
                        }
                        .info-row {
                            display: flex;
                            padding: 3px 0;
                            border-bottom: 1px dotted #ccc;
                        }
                        .info-row:last-child {
                            border-bottom: none;
                        }
                        .info-label {
                            font-weight: bold;
                            width: 65px;
                            flex-shrink: 0;
                            text-transform: uppercase;
                            font-size: 9px;
                        }
                        .info-value {
                            flex: 1;
                            word-break: break-word;
                        }
                        .peso-section {
                            border-top: 1.5px solid #000;
                            padding: 6px;
                            text-align: center;
                        }
                        .peso-section .label {
                            font-size: 9px;
                            text-transform: uppercase;
                            color: #555;
                        }
                        .peso-section .value {
                            font-size: 20px;
                            font-weight: bold;
                        }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <div class="header">
                            <h1>${planilla || ''}</h1>
                        </div>

                        <div class="qr-codigo">
                            <img src="${qrImg.src}" alt="QR">
                        </div>

                        <div class="codigo-section">
                            <div class="codigo">${codigo}</div>
                        </div>

                        <div class="info-section">
                            <div class="info-row">
                                <span class="info-label">Cliente</span>
                                <span class="info-value">${cliente || '-'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Obra</span>
                                <span class="info-value">${obra || '-'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Descrip.</span>
                                <span class="info-value">${descripcion || '-'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Seccion</span>
                                <span class="info-value">${seccion || '-'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Ensambl.</span>
                                <span class="info-value">${ensamblado || '-'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Etiquetas</span>
                                <span class="info-value">${etiquetas || '-'}</span>
                            </div>
                        </div>

                        <div class="peso-section">
                            <div class="label">Peso Total</div>
                            <div class="value">${peso} kg</div>
                        </div>
                    </div>
                    <script>
                        window.print();
                        setTimeout(() => window.close(), 500);
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }, 500);
}
