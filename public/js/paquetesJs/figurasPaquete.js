document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modal-dibujo");
    const cerrarModal = document.getElementById("cerrar-modal");
    const canvas = document.getElementById("canvas-dibujo");
    const ctx = canvas.getContext("2d");

    // Márgenes y espaciado
    const marginX = 50;
    const marginY = 25;
    const gapSpacing = 10;
    const minSlotHeight = 100;

    function extraerDimensiones(dimensiones) {
        const longitudes = [],
            angulos = [];
        dimensiones.split(/\s+/).forEach((token) => {
            if (token.includes("d")) {
                angulos.push(parseFloat(token.replace("d", "")) || 0);
            } else {
                longitudes.push(parseFloat(token) || 50);
            }
        });
        return { longitudes, angulos };
    }

    function calcularBoundingBox(longitudes, angulos) {
        let currentX = 0,
            currentY = 0,
            currentAngle = 0;
        let minX = 0,
            maxX = 0,
            minY = 0,
            maxY = 0;

        longitudes.forEach((longitud, i) => {
            currentX += longitud * Math.cos((currentAngle * Math.PI) / 180);
            currentY += longitud * Math.sin((currentAngle * Math.PI) / 180);
            minX = Math.min(minX, currentX);
            maxX = Math.max(maxX, currentX);
            minY = Math.min(minY, currentY);
            maxY = Math.max(maxY, currentY);
            currentAngle += angulos[i] || 0;
        });

        return { minX, maxX, minY, maxY };
    }

    function dibujarElementos(elementos) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const numElementos = elementos.length;
        const canvasWidth = canvas.width;
        const canvasHeight =
            marginY * 2 +
            numElementos * minSlotHeight +
            (numElementos - 1) * gapSpacing;

        // Ajustar la altura del canvas según la cantidad de elementos
        canvas.height = canvasHeight;

        const availableWidth = canvasWidth - 2 * marginX;
        const availableSlotHeight =
            (canvasHeight - 2 * marginY - (numElementos - 1) * gapSpacing) /
            numElementos;

        elementos.forEach((elemento, index) => {
            const { longitudes, angulos } = extraerDimensiones(
                elemento.dimensiones || ""
            );
            const centerX = marginX + availableWidth / 2;
            const centerY =
                marginY +
                availableSlotHeight / 2 +
                index * (availableSlotHeight + gapSpacing);

            if (longitudes.length === 1) {
                dibujarLinea(ctx, centerX, centerY, availableWidth, longitudes);
            } else {
                dibujarFigura(
                    ctx,
                    centerX,
                    centerY,
                    availableWidth,
                    availableSlotHeight,
                    longitudes,
                    angulos
                );
            }

            // Etiqueta del elemento (ID)
            ctx.font = "14px Arial";
            ctx.fillStyle = "#FF0000";
            ctx.fillText(
                `#${elemento.id}`,
                marginX + availableWidth - 10,
                centerY + availableSlotHeight / 2 - 5
            );
        });
    }

    function dibujarLinea(ctx, centerX, centerY, availableWidth, length) {
        const scale = availableWidth / Math.abs(length);
        const lineLength = Math.abs(length) * scale;

        ctx.strokeStyle = "#0000FF";
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(centerX - lineLength / 2, centerY);
        ctx.lineTo(centerX + lineLength / 2, centerY);
        ctx.stroke();

        // Etiqueta de dimensión
        ctx.font = "12px Arial";
        ctx.fillStyle = "red";
        ctx.fillText(length.toString(), centerX, centerY - 10);
    }

    function dibujarFigura(
        ctx,
        centerX,
        centerY,
        availableWidth,
        availableHeight,
        longitudes,
        angulos
    ) {
        const { minX, maxX, minY, maxY } = calcularBoundingBox(
            longitudes,
            angulos
        );
        const figWidth = maxX - minX;
        const figHeight = maxY - minY;

        const rotate = figWidth < figHeight;
        const scale = Math.min(
            availableWidth / (rotate ? figHeight : figWidth),
            availableHeight / (rotate ? figWidth : figHeight)
        );
        const figCenterX = (minX + maxX) / 2;
        const figCenterY = (minY + maxY) / 2;

        ctx.save();
        ctx.translate(centerX, centerY);
        if (rotate) ctx.rotate(-Math.PI / 2);
        ctx.scale(scale, scale);
        ctx.translate(-figCenterX, -figCenterY);

        ctx.strokeStyle = "#0000FF";
        ctx.lineWidth = 2 / scale;
        ctx.beginPath();

        let currentX = 0,
            currentY = 0,
            currentAngle = 0;
        ctx.moveTo(currentX, currentY);

        longitudes.forEach((longitud, i) => {
            const newX =
                currentX + longitud * Math.cos((currentAngle * Math.PI) / 180);
            const newY =
                currentY + longitud * Math.sin((currentAngle * Math.PI) / 180);
            ctx.lineTo(newX, newY);

            // Dibujar la acotación en cada segmento
            const midX = (currentX + newX) / 2;
            const midY = (currentY + newY) / 2;
            const angleRad = Math.atan2(newY - currentY, newX - currentX);

            ctx.save();
            ctx.translate(midX, midY);
            ctx.rotate(angleRad);
            ctx.font = `${12 / scale}px Arial`;
            ctx.fillStyle = "red";
            ctx.fillText(longitud.toFixed(1), 0, -5 / scale); // Mostrar la dimensión
            ctx.restore();

            currentX = newX;
            currentY = newY;
            currentAngle += angulos[i] || 0;
        });

        ctx.stroke();
        ctx.restore();
    }

    function mostrarDibujo(paqueteId) {
        const paquete = window.paquetes.find((p) => p.id == paqueteId);
        console.log(window.paquetes); // Esto te ayudará a ver la estructura de los datos

        if (!paquete) {
            console.warn("No se encontró el paquete.");
            return;
        }

        // Obtener los elementos directamente del paquete
        let elementos = paquete.elementos || [];
        let subpaquetes = paquete.subpaquetes || [];

        if (elementos.length > 0) {
            dibujarElementos(elementos);
            modal.classList.remove("hidden");
        } else if (subpaquetes.length > 0) {
            dibujarElementos(subpaquetes);
            modal.classList.remove("hidden");
        } else {
            alert("Este paquete no tiene elementos para dibujar.");
        }
    }

    cerrarModal.addEventListener("click", function () {
        modal.classList.add("hidden");
    });

    modal.addEventListener("click", function (e) {
        if (e.target === modal) {
            modal.classList.add("hidden");
        }
    });

    window.mostrarDibujo = mostrarDibujo;
});
