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
        height: 100,
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