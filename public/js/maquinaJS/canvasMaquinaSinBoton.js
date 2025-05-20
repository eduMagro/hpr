// =======================
// Configuración Global
// =======================
const FIGURE_LINE_COLOR_X = "rgba(0, 0, 0, 0.8)";
const LINEA_COTA_COLOR_X = "rgba(255, 0, 0, 0.5)";
const VALOR_COTA_COLOR_X = "rgba(0, 0, 0, 1)";
const BARS_TEXT_COLOR_X = "rgba(0, 0, 0, 1)";
const ELEMENT_TEXT_COLOR_X = "blue";

const marginX_X = 50;
const marginY_X = 50;
const gapSpacing_X = 30;
const minSlotHeight_X = 100;

// // =======================
// Funciones auxiliares de dibujo y computación
// =======================
// (drawDimensionLine, drawArrowhead, drawSimpleDimension, computeLineSegments, transformPoint, dibujarFiguraPath, extraerDimensiones, computePathPoints)
// Estas funciones se mantienen igual

// =======================
// Script principal sin botones
// =======================
document.addEventListener("DOMContentLoaded", () => {
    const elementos = window.elementosAgrupadosScript;
    if (!elementos) {
        console.error(
            "La variable 'elementosAgrupadosScript' no está definida."
        );
        return;
    }

    elementos.forEach((grupo) => {
        const canvas = document.getElementById(
            `canvas-imprimir-etiqueta-${grupo.etiqueta?.etiqueta_sub_id}`
        );

        if (!canvas) {
            console.warn(
                `Canvas no encontrado para etiqueta ID: ${grupo.etiqueta?.id}`
            );
            return;
        }

        const parent = canvas.parentElement;
        const canvasWidth = parent.clientWidth;
        const textHeight = 60;
        const buttonHeight = 0; // eliminamos altura para botones
        const numElementos = grupo.elementos.length;
        const canvasHeight =
            textHeight +
            numElementos * minSlotHeight +
            (numElementos - 1) * gapSpacing +
            buttonHeight;
        canvas.width = canvasWidth;
        canvas.height = canvasHeight;

        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = "#fff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const availableSlotHeight =
            (canvasHeight -
                textHeight -
                buttonHeight -
                (numElementos - 1) * gapSpacing) /
            numElementos;
        const availableWidth = canvasWidth - 2 * marginX;

        grupo.elementos.forEach((elemento, index) => {
            const dimensionesStr = elemento.dimensiones || "";
            const dims = extraerDimensiones(dimensionesStr);
            const barras = elemento.barras ?? 0;
            const diametro = elemento.diametro ?? "N/A";
            const peso = elemento.peso ?? "N/A";

            const centerX = marginX + availableWidth / 2;
            const centerY =
                textHeight +
                index * (availableSlotHeight + gapSpacing) +
                availableSlotHeight / 2;

            // Mostrar datos del elemento
            const dataX = canvasWidth - 10;
            const dataY = centerY - 40;
            ctx.font = "26px Arial";
            ctx.fillStyle = "#000";
            ctx.textAlign = "right";
            ctx.fillText(`Ø${diametro} | ${peso} | x${barras}`, dataX, dataY);

            const slotBottom =
                marginY +
                availableSlotHeight +
                index * (availableSlotHeight + gapSpacing);
            const labelX = marginX + availableWidth + 25;
            const labelY = slotBottom - 50;
            ctx.font = "26px Arial";
            ctx.fillStyle = ELEMENT_TEXT_COLOR;
            ctx.fillText(`#${elemento.id}`, labelX, labelY);

            // Dibujar figura
            if (dims.length === 1 && dims[0].type === "arc") {
                const arc = dims[0];
                const scale = Math.min(
                    availableWidth / (2 * arc.radius),
                    availableSlotHeight / (2 * arc.radius)
                );
                const effectiveRadius = arc.radius * scale;
                const angleRad = ((arc.arcAngle || 360) * Math.PI) / 180;
                ctx.beginPath();
                ctx.arc(centerX, centerY, effectiveRadius, 0, angleRad, false);
                ctx.strokeStyle = FIGURE_LINE_COLOR;
                ctx.lineWidth = 2;
                ctx.stroke();

                const midAngle = angleRad / 2;
                const offset = 10;
                const textX =
                    centerX + (effectiveRadius + offset) * Math.cos(midAngle);
                const textY =
                    centerY + (effectiveRadius + offset) * Math.sin(midAngle);
                ctx.font = "12px Arial";
                ctx.fillStyle = VALOR_COTA_COLOR;
                ctx.fillText(arc.radius.toString() + "r", textX, textY);
            } else if (dims.length === 1 && dims[0].type === "line") {
                const line = dims[0];
                const scale = availableWidth / Math.abs(line.length);
                const lineLength = Math.abs(line.length) * scale;
                ctx.strokeStyle = FIGURE_LINE_COLOR;
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(centerX - lineLength / 2, centerY);
                ctx.lineTo(centerX + lineLength / 2, centerY);
                ctx.stroke();

                const pt1 = { x: centerX - lineLength / 2, y: centerY };
                const pt2 = { x: centerX + lineLength / 2, y: centerY };
                drawDimensionLine(ctx, pt1, pt2, line.length.toString(), 10);
            } else {
                const points = computePathPoints(dims);
                let minX = Infinity,
                    maxX = -Infinity,
                    minY = Infinity,
                    maxY = -Infinity;
                points.forEach((pt) => {
                    if (pt.x < minX) minX = pt.x;
                    if (pt.x > maxX) maxX = pt.x;
                    if (pt.y < minY) minY = pt.y;
                    if (pt.y > maxY) maxY = pt.y;
                });

                const figWidth = maxX - minX;
                const figHeight = maxY - minY;
                const segments = computeLineSegments(dims);
                let rotate = figWidth < figHeight && segments.length <= 7;

                const effectiveWidth = rotate ? figHeight : figWidth;
                const effectiveHeight = rotate ? figWidth : figHeight;
                const figCenterX = (minX + maxX) / 2;
                const figCenterY = (minY + maxY) / 2;

                const scale = Math.min(
                    availableWidth / effectiveWidth,
                    availableSlotHeight / effectiveHeight
                );

                ctx.save();
                ctx.translate(centerX, centerY);
                if (rotate) ctx.rotate(-Math.PI / 2);
                ctx.scale(scale, scale);
                ctx.translate(-figCenterX, -figCenterY);
                ctx.lineWidth = 2 / scale;
                dibujarFiguraPath(ctx, dims);
                ctx.restore();

                if (segments.length > 7) {
                    function transformLocalToGlobal(x, y) {
                        if (!rotate) {
                            return {
                                x: centerX + (x - figCenterX) * scale,
                                y: centerY + (y - figCenterY) * scale,
                            };
                        } else {
                            let dx = y - figCenterY;
                            let dy = -(x - figCenterX);
                            return {
                                x: centerX + dx * scale,
                                y: centerY + dy * scale,
                            };
                        }
                    }
                    const c1 = transformLocalToGlobal(minX, minY);
                    const c2 = transformLocalToGlobal(minX, maxY);
                    const c3 = transformLocalToGlobal(maxX, minY);
                    const c4 = transformLocalToGlobal(maxX, maxY);
                    const globalMinX = Math.min(c1.x, c2.x, c3.x, c4.x);
                    const globalMaxX = Math.max(c1.x, c2.x, c3.x, c4.x);
                    const globalMinY = Math.min(c1.y, c2.y, c3.y, c4.y);
                    const globalMaxY = Math.max(c1.y, c2.y, c3.y, c4.y);
                    const totalWidth = (maxX - minX).toFixed(0);
                    const totalHeight = (maxY - minY).toFixed(0);
                    drawSimpleDimension(
                        ctx,
                        { x: globalMinX, y: globalMinY - 15 },
                        { x: globalMaxX, y: globalMinY - 15 },
                        totalWidth,
                        true,
                        0
                    );
                    drawSimpleDimension(
                        ctx,
                        { x: globalMinX - 20, y: globalMinY },
                        { x: globalMinX - 20, y: globalMaxY },
                        totalHeight,
                        false,
                        0
                    );
                } else {
                    segments.forEach((seg) => {
                        const p1 = transformPoint(
                            seg.start.x,
                            seg.start.y,
                            centerX,
                            centerY,
                            scale,
                            rotate,
                            figCenterX,
                            figCenterY
                        );
                        const p2 = transformPoint(
                            seg.end.x,
                            seg.end.y,
                            centerX,
                            centerY,
                            scale,
                            rotate,
                            figCenterX,
                            figCenterY
                        );
                        drawDimensionLine(
                            ctx,
                            p1,
                            p2,
                            seg.length.toString(),
                            10
                        );
                    });
                }
            }
        });
    });
});
