document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modal-dibujo");
    const cerrarModal = document.getElementById("cerrar-modal");
    const canvas = document.getElementById("canvas-dibujo");
    const ctx = canvas.getContext("2d");

    // Márgenes internos para delimitar el área de dibujo
    const marginX = 50; // margen horizontal
    const marginY = 25; // margen vertical

    /* ******************************************************************
     * Funciones auxiliares
     ****************************************************************** */

    // Extrae longitudes y ángulos de un string (ej.: "15 90d 85 ...")
    function extraerDimensiones(dimensiones) {
        const longitudes = [];
        const angulos = [];
        if (!dimensiones)
            return {
                longitudes,
                angulos,
            };

        const tokens = dimensiones
            .split(/\s+/)
            .filter((token) => token.length > 0);
        tokens.forEach((token) => {
            if (token.includes("d")) {
                const num = parseFloat(token.replace("d", ""));
                angulos.push(isNaN(num) ? 0 : num);
            } else {
                const num = parseFloat(token);
                longitudes.push(isNaN(num) ? 50 : num);
            }
        });
        return {
            longitudes,
            angulos,
        };
    }

    // Calcula el bounding box de la figura en coordenadas locales (origen en 0,0)
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
        return {
            minX,
            maxX,
            minY,
            maxY,
        };
    }

    // Dibuja el path de la figura en el canvas usando el contexto
    function dibujarFiguraPath(ctx, longitudes, angulos) {
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
            currentX = newX;
            currentY = newY;
            currentAngle += angulos[i] || 0;
        });
        ctx.stroke();
    }

    // Calcula la lista de puntos (en coordenadas locales) de la figura
    function computePoints(longitudes, angulos) {
        const points = [];
        let cx = 0,
            cy = 0,
            ca = 0;
        points.push({
            x: cx,
            y: cy,
        });
        for (let i = 0; i < longitudes.length; i++) {
            const newX = cx + longitudes[i] * Math.cos((ca * Math.PI) / 180);
            const newY = cy + longitudes[i] * Math.sin((ca * Math.PI) / 180);
            points.push({
                x: newX,
                y: newY,
            });
            cx = newX;
            cy = newY;
            ca += angulos[i] || 0;
        }
        return points;
    }

    // Transforma un punto de coordenadas locales a coordenadas del canvas
    function transformPoint(
        x,
        y,
        centerX,
        centerY,
        scale,
        rotate,
        figCenterX,
        figCenterY
    ) {
        if (rotate) {
            return {
                x: centerX + scale * (y - figCenterY),
                y: centerY - scale * (x - figCenterX),
            };
        } else {
            return {
                x: centerX + scale * (x - figCenterX),
                y: centerY + scale * (y - figCenterY),
            };
        }
    }

    /* ******************************************************************
     * Función principal para dibujar la figura en el canvas
     ****************************************************************** */
    function dibujarFigura(canvasId, dimensionesStr, peso) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.warn(`Canvas no encontrado: ${canvasId}`);
            return;
        }
        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Extraer dimensiones
        const { longitudes, angulos } = extraerDimensiones(dimensionesStr);
        if (longitudes.length === 0) {
            console.warn("No hay dimensiones válidas para dibujar.");
            return;
        }

        // Espacio disponible en el canvas
        const availableWidth = canvas.width - 2 * marginX;
        const availableHeight = canvas.height - 2 * marginY;
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;

        if (longitudes.length === 1) {
            // Caso: Única dimensión (dibuja una línea horizontal)
            const length = longitudes[0];
            const scale = availableWidth / Math.abs(length);
            const lineLength = Math.abs(length) * scale;

            ctx.strokeStyle = "#0000FF";
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(centerX - lineLength / 2, centerY);
            ctx.lineTo(centerX + lineLength / 2, centerY);
            ctx.stroke();

            // Acotación en rojo
            const pt1 = {
                x: centerX - lineLength / 2,
                y: centerY,
            };
            const pt2 = {
                x: centerX + lineLength / 2,
                y: centerY,
            };
            const midX = (pt1.x + pt2.x) / 2;
            const midY = (pt1.y + pt2.y) / 2;
            // Para línea horizontal, el ángulo es 0
            const offset = 10;
            const offsetX = offset * Math.cos(0 - Math.PI / 2);
            const offsetY = offset * Math.sin(0 - Math.PI / 2);

            ctx.font = "12px Arial";
            ctx.fillStyle = "red";
            ctx.fillText(length.toString(), midX + offsetX, midY + offsetY);
        } else {
            // Caso: Figura compuesta (varias dimensiones)
            const { minX, maxX, minY, maxY } = calcularBoundingBox(
                longitudes,
                angulos
            );
            const figWidth = maxX - minX;
            const figHeight = maxY - minY;

            // Si el ancho es menor que la altura, rotar para que el lado mayor quede horizontal
            let rotate = false;
            if (figWidth < figHeight) {
                rotate = true;
            }
            const effectiveWidth = rotate ? figHeight : figWidth;
            const effectiveHeight = rotate ? figWidth : figHeight;
            const figCenterX = (minX + maxX) / 2;
            const figCenterY = (minY + maxY) / 2;

            // Calcular escala para ajustar la figura al área disponible
            const scale = Math.min(
                availableWidth / effectiveWidth,
                availableHeight / effectiveHeight
            );

            // Aplicar transformaciones: trasladar, rotar (si es necesario), escalar y centrar
            ctx.save();
            ctx.translate(centerX, centerY);
            if (rotate) {
                ctx.rotate(-Math.PI / 2);
            }
            ctx.scale(scale, scale);
            ctx.translate(-figCenterX, -figCenterY);

            ctx.strokeStyle = "#0000FF";
            ctx.lineWidth = 2 / scale;
            ctx.lineCap = "round";
            ctx.lineJoin = "round";
            dibujarFiguraPath(ctx, longitudes, angulos);
            ctx.restore();

            // Dibujar acotaciones para cada segmento
            const pointsLocal = computePoints(longitudes, angulos);
            const pointsCanvas = pointsLocal.map((pt) =>
                transformPoint(
                    pt.x,
                    pt.y,
                    centerX,
                    centerY,
                    scale,
                    rotate,
                    figCenterX,
                    figCenterY
                )
            );
            for (let i = 0; i < pointsCanvas.length - 1; i++) {
                const pt1 = pointsCanvas[i];
                const pt2 = pointsCanvas[i + 1];
                const midX = (pt1.x + pt2.x) / 2;
                const midY = (pt1.y + pt2.y) / 2;
                const dxLocal = pointsLocal[i + 1].x - pointsLocal[i].x;
                const dyLocal = pointsLocal[i + 1].y - pointsLocal[i].y;
                const angleLocal = Math.atan2(dyLocal, dxLocal);
                const angleCanvas = rotate
                    ? angleLocal - Math.PI / 2
                    : angleLocal;
                const offset = 10;
                const offsetX = offset * Math.cos(angleCanvas - Math.PI / 2);
                const offsetY = offset * Math.sin(angleCanvas - Math.PI / 2);

                ctx.strokeStyle = "#0000FF";
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(pt1.x, pt1.y);
                ctx.lineTo(pt2.x, pt2.y);
                ctx.stroke();

                ctx.font = "12px Arial";
                ctx.fillStyle = "#FF0000";
                const text = longitudes[i].toString();
                ctx.fillText(text, midX + offsetX, midY + offsetY);
            }
        }

        // Mostrar la etiqueta (peso) en la esquina inferior derecha
        ctx.font = "14px Arial";
        ctx.fillStyle = "#FF0000";
        ctx.fillText(peso, canvas.width - 50, canvas.height - 10);
    }

    /* ******************************************************************
     * Eventos: abrir y cerrar modal
     ****************************************************************** */
    document.querySelectorAll(".abrir-modal-dibujo").forEach((link) => {
        link.addEventListener("click", function (event) {
            event.preventDefault();
            const dimensiones = this.getAttribute("data-dimensiones");
            const peso = this.getAttribute("data-peso") || "N/A";
            modal.classList.remove("hidden");
            dibujarFigura("canvas-dibujo", dimensiones, peso);
        });
    });

    cerrarModal.addEventListener("click", function () {
        modal.classList.add("hidden");
    });

    modal.addEventListener("click", function (e) {
        if (e.target === modal) {
            modal.classList.add("hidden");
        }
    });
});
