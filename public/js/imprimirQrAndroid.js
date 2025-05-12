function generateAndPrintQR(id, nombre, tipo) {
    const qrContainerId = `qrContainer-${id}`;
    let qrContainer = document.getElementById(qrContainerId);

    if (!qrContainer) {
        qrContainer = document.createElement("div");
        qrContainer.id = qrContainerId;
        qrContainer.style.display = "none";
        document.body.appendChild(qrContainer);
    }

    qrContainer.innerHTML = "";
    const qrSize = 200; // Puedes ajustar el tamaño del QR

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

        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        const img = new Image();

        img.onload = function () {
            canvas.width = qrSize;
            canvas.height = qrSize;
            ctx.fillStyle = "white";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, qrSize, qrSize);

            canvas.toBlob((blob) => {
                const blobUrl = URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.href = blobUrl;
                link.download = `QR-${nombre}-${id}.png`;

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(blobUrl);

                Swal.fire({
                    icon: "success",
                    title: "QR descargado",
                    text: "El QR se ha guardado correctamente.",
                });
            }, "image/png");
        };

        img.src = qrImg.src;
    }, 600);
}
