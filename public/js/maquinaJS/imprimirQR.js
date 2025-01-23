function generateAndPrintQR(id, descripcion_fila) {
    const qrContainer = document.getElementById("qrContainer");
    qrContainer.innerHTML = ""; // Limpia cualquier QR anterior

    // Generamos el código QR solo con el ID
    const qrCode = new QRCode(qrContainer, {
        text: id.toString(), // Solo contiene el ID
        width: 100, // Tamaño ajustable
        height: 100,
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
                        body { display: flex; justify-content: center; align-items: center; flex-direction: column; font-family: Arial, sans-serif; }
                        img { margin-bottom: 20px; width: 100px; height: 100px; }
                    </style>
                </head>
                <body>
                    <img src="${qrImg.src}" alt="Código QR">
                    <p>${descripcion_fila}</p>
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
