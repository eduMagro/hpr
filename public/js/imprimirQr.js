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

    // Tamaño del QR (puedes ajustarlo si lo necesitas por tipo)
    const qrSize = 200;

    // Generamos el código QR
    new QRCode(qrContainer, {
        text: id.toString(),
        width: qrSize,
        height: qrSize,
    });

    const partesNombre = nombre.split(",");
    const sector = (partesNombre[1] || "").trim();
    const ubicacion = (partesNombre[2] || "").trim();

    // Esperar a que la imagen del QR se genere
    setTimeout(() => {
        const qrImg = qrContainer.querySelector("img");
        if (!qrImg) return;

        // Abrimos la ventana de impresión con dos copias
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
                        gap:30px;                 /* separación entre copias */
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
                        width:${qrSize + 270}px;
                    }
                    .label{
                        width:100%;
                        padding:5px;
                        border:1px solid #000;
                        text-align:center;
                    }
                    .tipo{
                        font-weight:bold;
                        font-size:100px;
                        text-transform:uppercase;
                        border-radius:8px 8px 0 0;
                    }
                    .arrow{
             font-weight:bold;
                        font-size:100px;
                        line-height:1;
                        margin:0;
                    }
                    .nombre{
                    font-size:64px;
                    font-weight:500;
                    border-radius:0 0 8px 8px;}
                    img{width:${qrSize}px;height:${qrSize}px;margin-bottom:10px;padding:5px;}
                </style>
            </head>
            <body>
                <!-- Copia con flecha a la izquierda -->
                <div class="qr-card">
                    <img src="${qrImg.src}" alt="Código QR">
                    <div class="label tipo">${id}</div>
                    <div class="label arrow">&#x2190;</div> <!-- ← -->
                    <div class="label nombre">${sector}</div>
                    <div class="label nombre">${ubicacion}</div>
                </div>

                <!-- Copia con flecha a la derecha -->
                <div class="qr-card">
                    <img src="${qrImg.src}" alt="Código QR">
                    <div class="label tipo">${id}</div>
                    <div class="label arrow">&#x2192;</div> <!-- → -->
                    <div class="label nombre">${sector}</div>
                    <div class="label nombre">${ubicacion}</div>
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
