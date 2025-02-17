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

    const qrSize = 50; // Tamaño exacto en píxeles

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

        // Crear un lienzo con el tamaño exacto
        let canvas = document.createElement("canvas");
        let ctx = canvas.getContext("2d");
        let img = new Image();

        img.onload = function () {
            canvas.width = qrSize;
            canvas.height = qrSize + 40; // Espacio para el nombre
            ctx.fillStyle = "white";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, qrSize, qrSize);

            // Añadir el texto con el nombre
            ctx.fillStyle = "black";
            ctx.font = "16px Arial";
            ctx.textAlign = "center";
            ctx.fillText(nombre, qrSize / 2, qrSize + 30);

            let qrBase64 = canvas.toDataURL("image/png", 1.0);

            // Crear enlace para descargar la imagen con el tamaño exacto
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
                text: "El QR se ha descargado correctamente. Ábrelo desde iPrint&Label",
            });
        };

        img.src = qrImg.src; // Cargar la imagen en base64
    }, 500);
}
