const qrConfigs = {
    id: (id, codigo, nombre, descripcion) => `
        <div class="texto-id">${id}</div>
    `,
    id_codigo: (id, codigo, nombre, descripcion) => `
        <div class="texto-id">${id}</div>
        <div class="texto-dato">${codigo}</div>
    `,
    id_descripcion: (id, codigo, nombre, descripcion) => `
        <div class="texto-id">${id}</div>
        <div class="texto-dato">${descripcion}</div>
    `,
    id_nombre: (id, codigo, nombre, descripcion) => {
        // dividir el nombre por comas y eliminar espacios
        const partes = (nombre || "")
            .split(",")
            .map((p) => p.trim())
            .filter((p) => p.length > 0);

        return `
            <div class="texto-id">${id}</div>
            ${partes.map((p) => `<div class="texto-dato">${p}</div>`).join("")}
        `;
    },
};

function imprimirQR(id, nombre, descripcion, codigo) {
    const qrContainerId = `qrContainer-${id}`;
    let qrContainer = document.getElementById(qrContainerId);

    if (!qrContainer) {
        qrContainer = document.createElement("div");
        qrContainer.id = qrContainerId;
        qrContainer.style.display = "none";
        document.body.appendChild(qrContainer);
    }
    qrContainer.innerHTML = "";

    const qrSize = 200;
    new QRCode(qrContainer, {
        text: id.toString(),
        width: qrSize,
        height: qrSize,
    });

    const withArrows = document.getElementById("qrConFlechas").checked;
    const datos = document.getElementById("qrDatos").value;

    setTimeout(() => {
        const qrImg = qrContainer.querySelector("img");
        if (!qrImg) return;

        let contenido = `
            <div class="qr-card">
                <img src="${qrImg.src}" alt="QR">
                <div class="datos-container">
                    ${qrConfigs[datos](id, codigo, nombre, descripcion)}
                </div>
            </div>
        `;

        if (withArrows) {
            contenido = `
                <div class="qr-card">
                    <img src="${qrImg.src}" alt="QR">
                    <div class="datos-container">
                        ${qrConfigs[datos](id, codigo, nombre, descripcion)}
                        <div class="arrow">&#x2190;</div>
                    </div>
                </div>
                <div class="qr-card">
                    <img src="${qrImg.src}" alt="QR">
                    <div class="datos-container">
                        ${qrConfigs[datos](id, codigo, nombre, descripcion)}
                        <div class="arrow">&#x2192;</div>
                    </div>
                </div>
            `;
        }

        const printWindow = window.open("", "_blank");
        if (!printWindow) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo abrir la ventana de impresión',
                text: 'Por favor, desactiva el bloqueador de ventanas emergentes para este sitio e inténtalo de nuevo.',
            });
            return;
        }
        printWindow.document.write(`
            <html>
            <head>
                <title>Imprimir QR</title>
                <style>
                  body{
    display:flex;
    justify-content:center;
    align-items:flex-start;
    gap:20px;
    margin:0;
    padding:20px;
    font-family:'Arial',sans-serif;
    background:#fff;
    flex-wrap:nowrap;
}
.qr-card{
    width:300px;   /* fijo */
    height:440px;  /* fijo */
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:flex-start;
    padding:10px;
    border:1px solid #000;
    border-radius:8px;
    background:#fff;
    box-sizing:border-box;
    overflow:hidden;
    text-align:center;
}
img{
    width:160px;   /* QR fijo */
    height:160px;
    margin-bottom:10px;
}
.datos-container{
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:flex-start;
    align-items:center;
    gap:5px;
    width:100%;
    overflow:hidden;
}
.texto-id{
    font-size:64px; /* grande */
    font-weight:bold;
    line-height:1.1;
    max-height:30%;
    word-break:break-word;
}
.texto-dato{
    font-size:32px;
    font-weight:bold;
    line-height:1.1;
    word-break:break-word;
}
.arrow{
    font-size:80px;
    font-weight:bold;
    margin-top:auto;
}

@page {
    size: A4 portrait;
    margin: 10mm;
}

                </style>
            </head>
            <body>
                ${contenido}
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
