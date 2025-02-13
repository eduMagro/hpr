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

    let qrSize =
        tipo.toLowerCase() === "ubicacion" || tipo.toLowerCase() === "maquina"
            ? 240
            : 120;

    // Generamos el código QR
    new QRCode(qrContainer, {
        text: id.toString(),
        width: qrSize,
        height: qrSize,
    });

    setTimeout(() => {
        const qrImg = qrContainer.querySelector("img");
        if (!qrImg) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Error al generar el código QR.",
            });
            return;
        }

        // Convertir la imagen en base64 para descarga
        let canvas = document.createElement("canvas");
        let ctx = canvas.getContext("2d");
        let img = new Image();

        img.onload = function () {
            canvas.width = qrSize;
            canvas.height = qrSize;
            ctx.drawImage(img, 0, 0, qrSize, qrSize);
            let qrBase64 = canvas.toDataURL("image/png");

            // Descargar la imagen QR
            let link = document.createElement("a");
            link.href = qrBase64;
            link.download = `QR-${nombre}-${id}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Mensaje para guiar al usuario a imprimir manualmente
            Swal.fire({
                icon: "success",
                title: "QR Descargado",
                text: "El QR se ha descargado correctamente. Ábrelo desde tu galería y presiona imprimir.",
            });
        };

        img.src = qrImg.src; // Cargar la imagen en base64
    }, 500);
}
