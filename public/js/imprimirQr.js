function generateAndPrintQR(id, ubicacionDescripcion) {
    const qrContainerId = `qrContainer-${id}`;
    let qrContainer = document.getElementById(qrContainerId);

    if (!qrContainer) {
        qrContainer = document.createElement("div");
        qrContainer.id = qrContainerId;
        qrContainer.style.display = "none";
        document.body.appendChild(qrContainer);
    }

    qrContainer.innerHTML = ""; // Limpia cualquier QR anterior
    const qrSize = 200; // Tamaño del código

    // 1. Generar el código QR con la librería QRCode.js
    new QRCode(qrContainer, {
        text: id.toString(),
        width: qrSize,
        height: qrSize,
    });

    // 2. Extraer “ubicación” y “descripción” (esperamos el formato "ubicación->descripción")
    const [ubicacionRaw = "", descripcionRaw = ""] = ubicacionDescripcion
        .split("->")
        .map((s) => s.trim());
    const lineaInfo = `${ubicacionRaw}`;

    // 3. Cuando la imagen del QR ya exista, abrir la ventana de impresión
    setTimeout(() => {
        const qrImg = qrContainer.querySelector("img");
        if (!qrImg) return;

        const printWindow = window.open("", "_blank");
        printWindow.document.write(`
            <html>
            <head>
                <title>Imprimir QR</title>
                <style>
                    body{
                        display:flex;
                        justify-content:center;
                        align-items:center;
                        height:100vh;
                        margin:0;
                        gap:30px;
                        font-family:'Arial',sans-serif;
                        background:#f4f4f9;
                    }
                    .qr-card{
                        display:flex;
                        flex-direction:column;
                        align-items:center;
                        padding:15px;
                        border:1px solid #000;
                        border-radius:8px;
                        box-shadow:0 4px 8px rgba(0,0,0,.1);
                        background:#fff;
                        text-align:center;
                        width:${qrSize + 260}px;
                    }
                    .id{
                        width:100%;
                        padding:5px;
                        border:1px solid #000;
                        font-weight:bold;
                        font-size:100px;
                        text-transform:uppercase;
                        border-radius:8px 8px 0 0;
                    }
                    .info{
                        width:100%;
                        padding:5px;
                        border:1px solid #000;
                        font-size:64px;
                        font-weight:500;
                        border-radius:0 0 8px 8px;
                    }
                    img{
                        width:${qrSize}px;
                        height:${qrSize}px;
                        margin-bottom:10px;
                        padding:5px;
                    }
                </style>
            </head>
            <body>
                <!-- Copia 1 -->
                <div class="qr-card">
                    <img src="${qrImg.src}" alt="Código QR">
                    <div class="id">${id}</div>
                    <div class="info">${lineaInfo}</div>
                </div>

                <!-- Copia 2 -->
                <div class="qr-card">
                    <img src="${qrImg.src}" alt="Código QR">
                    <div class="id">${id}</div>
                    <div class="info">${lineaInfo}</div>
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
