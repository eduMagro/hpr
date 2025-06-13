// =======================
// Configuración Global
// =======================
const FIGURE_LINE_COLOR = "rgba(0, 0, 0, 0.8)"; // Color de la línea de la figura
const LINEA_COTA_COLOR = "rgba(255, 0, 0, 0.5)"; // Color de la línea y cabeza de flechas de las cotas
const VALOR_COTA_COLOR = "rgba(0, 0, 0, 1)"; // Color del valor de las cotas
const BARS_TEXT_COLOR = "rgba(0, 0, 0, 1)"; // Color del texto del número de piezas
const ELEMENT_TEXT_COLOR = "blue"; // Color del texto del elemento (ID)

const marginX = 50; // Margen horizontal interno
const marginY = 50; // Margen vertical interno
const gapSpacing = 25; // Espacio extra entre slots
const minSlotHeight = 80; // Altura mínima para cada slot

// =======================
// Función drawDimensionLine (Mayoría de Figuras)
// =======================
function drawDimensionLine(ctx, pt1, pt2, text, offset = 10) {
    const dx = pt2.x - pt1.x;
    const dy = pt2.y - pt1.y;
    const len = Math.sqrt(dx * dx + dy * dy);
    if (len === 0) return;
    const ux = -dy / len;
    const uy = dx / len;
    const p1 = { x: pt1.x + ux * offset, y: pt1.y + uy * offset };
    const p2 = { x: pt2.x + ux * offset, y: pt2.y + uy * offset };

    ctx.beginPath();
    ctx.moveTo(p1.x, p1.y);
    ctx.lineTo(p2.x, p2.y);
    ctx.strokeStyle = LINEA_COTA_COLOR;
    ctx.lineWidth = 1;
    ctx.stroke();

    const arrowSize = 5;
    drawArrowhead(ctx, p1, p2, arrowSize);
    drawArrowhead(ctx, p2, p1, arrowSize);

    const midX = (p1.x + p2.x) / 2 + ux * 10;
    const midY = (p1.y + p2.y) / 2 + uy * 10;
    ctx.font = "12px Arial";
    ctx.fillStyle = VALOR_COTA_COLOR;
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(text, midX, midY);
}

// =======================
// Función que dibuja cabeza de flechas para todas las figuras
// =======================
function drawArrowhead(ctx, from, to, size) {
    ctx.save();
    const angle = Math.atan2(to.y - from.y, to.x - from.x);
    ctx.beginPath();
    ctx.moveTo(from.x, from.y);
    ctx.lineTo(
        from.x + size * Math.cos(angle + Math.PI / 6),
        from.y + size * Math.sin(angle + Math.PI / 6)
    );
    ctx.moveTo(from.x, from.y);
    ctx.lineTo(
        from.x + size * Math.cos(angle - Math.PI / 6),
        from.y + size * Math.sin(angle - Math.PI / 6)
    );
    // Establecemos estilos para las cabezas de flechas
    ctx.strokeStyle = LINEA_COTA_COLOR;
    ctx.lineWidth = 1;
    ctx.stroke();
    ctx.restore();
}

// =======================
// Función que dibuja una cota (línea de dimensión con flechas y texto)
// para figuras con más de 7 segmentos
// =======================
function drawSimpleDimension(
    ctx,
    pt1,
    pt2,
    text,
    horizontal = true,
    offset = 10
) {
    ctx.save();
    ctx.strokeStyle = LINEA_COTA_COLOR;
    ctx.lineWidth = 1;

    // Clonar los puntos para no modificarlos
    let dpt1 = { x: pt1.x, y: pt1.y };
    let dpt2 = { x: pt2.x, y: pt2.y };

    // Desplazar la línea de cota en una dirección fija (vertical u horizontal)
    if (horizontal) {
        dpt1.y -= offset;
        dpt2.y -= offset;
    } else {
        dpt1.x += offset;
        dpt2.x += offset;
    }

    // Dibujar la línea de cota
    ctx.beginPath();
    ctx.moveTo(dpt1.x, dpt1.y);
    ctx.lineTo(dpt2.x, dpt2.y);
    ctx.stroke();

    // Calcular la distancia y el vector director de la línea
    const dx = dpt2.x - dpt1.x;
    const dy = dpt2.y - dpt1.y;
    const distance = Math.sqrt(dx * dx + dy * dy);
    const arrowSize = Math.max(3, Math.min(10, distance * 0.05));

    // Dibujar las flechas en ambos extremos
    drawArrowhead(ctx, dpt1, dpt2, arrowSize);
    drawArrowhead(ctx, dpt2, dpt1, arrowSize);

    // Calcular el vector unitario perpendicular a la línea
    const ux = -dy / distance;
    const uy = dx / distance;
    const textOffset = 10; // Desplazamiento adicional para el texto

    // Calcular el punto medio desplazado perpendicularmente
    const midX = (dpt1.x + dpt2.x) / 2 + ux * textOffset;
    const midY = (dpt1.y + dpt2.y) / 2 + uy * textOffset;

    // Dibujar el texto de la cota en la posición calculada
    ctx.font = "12px Arial";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillStyle = VALOR_COTA_COLOR;
    ctx.fillText(text, midX, midY);

    ctx.restore();
}

// =======================
// Función que computa segmentos para tokens "line" a partir de dims
// =======================
function computeLineSegments(dims) {
    let segments = [];
    let currentX = 0,
        currentY = 0,
        currentAngle = 0;
    dims.forEach((d) => {
        if (d.type === "line") {
            let start = { x: currentX, y: currentY };
            let end = {
                x:
                    currentX +
                    d.length * Math.cos((currentAngle * Math.PI) / 180),
                y:
                    currentY +
                    d.length * Math.sin((currentAngle * Math.PI) / 180),
            };
            segments.push({ start, end, length: d.length });
            currentX = end.x;
            currentY = end.y;
        } else if (d.type === "turn") {
            currentAngle += d.angle;
        } else if (d.type === "arc") {
            let centerX =
                currentX +
                d.radius * Math.cos(((currentAngle + 90) * Math.PI) / 180);
            let centerY =
                currentY +
                d.radius * Math.sin(((currentAngle + 90) * Math.PI) / 180);
            let startAngle = Math.atan2(currentY - centerY, currentX - centerX);
            let sweep = (d.arcAngle * Math.PI) / 180;
            let endAngle = startAngle + sweep;
            currentX = centerX + d.radius * Math.cos(endAngle);
            currentY = centerY + d.radius * Math.sin(endAngle);
            currentAngle += d.arcAngle;
        }
    });
    return segments;
}

// =======================
// Función que transforma un punto de coordenadas locales a globales
// =======================
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

// =======================
// Función que dibuja la figura a partir de dims (path)
// =======================
function dibujarFiguraPath(ctx, dims) {
    ctx.beginPath();
    let currentX = 0,
        currentY = 0,
        currentAngle = 0;
    ctx.moveTo(currentX, currentY);
    dims.forEach((d) => {
        if (d.type === "line") {
            currentX += d.length * Math.cos((currentAngle * Math.PI) / 180);
            currentY += d.length * Math.sin((currentAngle * Math.PI) / 180);
            ctx.lineTo(currentX, currentY);
        } else if (d.type === "turn") {
            currentAngle += d.angle;
        } else if (d.type === "arc") {
            let centerX =
                currentX +
                d.radius * Math.cos(((currentAngle + 90) * Math.PI) / 180);
            let centerY =
                currentY +
                d.radius * Math.sin(((currentAngle + 90) * Math.PI) / 180);
            let startAngle = Math.atan2(currentY - centerY, currentX - centerX);
            let sweep = (d.arcAngle * Math.PI) / 180;
            ctx.arc(
                centerX,
                centerY,
                d.radius,
                startAngle,
                startAngle + sweep,
                false
            );
            let endAngle = startAngle + sweep;
            currentX = centerX + d.radius * Math.cos(endAngle);
            currentY = centerY + d.radius * Math.sin(endAngle);
            currentAngle += d.arcAngle;
            ctx.lineTo(currentX, currentY);
        }
    });
    ctx.strokeStyle = FIGURE_LINE_COLOR;
    ctx.stroke();
}

// =======================
// Función que extrae dimensiones (tokens) desde el string
// =======================
function extraerDimensiones(dimensiones) {
    const tokens = dimensiones.split(/\s+/).filter((token) => token.length > 0);
    const dims = [];
    let i = 0;
    while (i < tokens.length) {
        const token = tokens[i];
        if (token.endsWith("r")) {
            const radius = parseFloat(token.slice(0, -1));
            let arcAngle = 360;
            if (i + 1 < tokens.length && tokens[i + 1].endsWith("d")) {
                arcAngle = parseFloat(tokens[i + 1].slice(0, -1));
                i++;
            }
            dims.push({ type: "arc", radius, arcAngle });
        } else if (token.endsWith("d")) {
            const angle = parseFloat(token.slice(0, -1));
            dims.push({ type: "turn", angle });
        } else {
            const length = parseFloat(token);
            dims.push({ type: "line", length });
        }
        i++;
    }
    return dims;
}

// =======================
// Función que computa la lista de puntos (para bounding box)
// =======================
function computePathPoints(dims) {
    let points = [];
    let currentX = 0,
        currentY = 0,
        currentAngle = 0;
    points.push({ x: currentX, y: currentY });
    dims.forEach((d) => {
        if (d.type === "line") {
            currentX += d.length * Math.cos((currentAngle * Math.PI) / 180);
            currentY += d.length * Math.sin((currentAngle * Math.PI) / 180);
            points.push({ x: currentX, y: currentY });
        } else if (d.type === "turn") {
            currentAngle += d.angle;
        } else if (d.type === "arc") {
            let centerX =
                currentX +
                d.radius * Math.cos(((currentAngle + 90) * Math.PI) / 180);
            let centerY =
                currentY +
                d.radius * Math.sin(((currentAngle + 90) * Math.PI) / 180);
            let startAngle = Math.atan2(currentY - centerY, currentX - centerX);
            let sweep = (d.arcAngle * Math.PI) / 180;
            let endAngle = startAngle + sweep;
            currentX = centerX + d.radius * Math.cos(endAngle);
            currentY = centerY + d.radius * Math.sin(endAngle);
            currentAngle += d.arcAngle;
            points.push({ x: currentX, y: currentY });
        }
    });
    return points;
}

// =======================
// Script principal
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
            `canvas-etiqueta-${grupo.etiqueta?.id}`
        );
        if (!canvas) {
            console.warn(
                `Canvas no encontrado para etiqueta ID: ${grupo.etiqueta?.id}`
            );
            return;
        }
        const clickableIDs = [];
        const parent = canvas.parentElement;
        const canvasWidth = parent.clientWidth;
        const textHeight = 60; // Espacio para los textos arriba
        const buttonHeight = 50; // Espacio para los botones abajo
        const numElementos = grupo.elementos.length;

        // Ajustar altura del canvas dinámicamente
        const canvasHeight =
            textHeight +
            numElementos * minSlotHeight +
            (numElementos - 1) * gapSpacing +
            buttonHeight;
        canvas.width = canvasWidth;
        canvas.height = canvasHeight;

        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = "#fe7f09";
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.clearRect(0, 0, canvas.width, canvas.height);

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

            // Mostrar datos del elemento a la derecha
            const dataX = canvasWidth - 10; // Posición alineada a la derecha
            const dataY = centerY - 40; // Ajuste vertical
            // 4. Dibujar texto de diametro peso y barras en global
            ctx.font = "14px Arial";
            ctx.fillStyle = BARS_TEXT_COLOR;
            ctx.fillStyle = "#000";
            ctx.font = "14px Arial";
            ctx.textAlign = "right";
            ctx.fillText(`Ø${diametro} | ${peso} | x${barras}`, dataX, dataY);

            const slotBottom =
                marginY +
                availableSlotHeight +
                index * (availableSlotHeight + gapSpacing);
            const labelX = marginX + availableWidth + 25;
            const labelY = slotBottom - 50;
            ctx.font = "14px Arial";
            ctx.fillStyle = ELEMENT_TEXT_COLOR;
            ctx.fillText(`#${elemento.id}`, labelX, labelY);

            const buttonX = canvas.width - 100;
            const buttonY = centerY + availableSlotHeight / 2 - 50;

            ctx.fillStyle = "#007bff";
            ctx.fillRect(buttonX, buttonY, 80, 30);
            ctx.fillStyle = "#fe7f09";
            ctx.font = "14px Arial";
            ctx.fillText("✂️ Dividir", buttonX + 70, buttonY + 20);

            // Area de click
            canvas.addEventListener("click", function (event) {
                let rect = canvas.getBoundingClientRect();
                let mouseX = event.clientX - rect.left;
                let mouseY = event.clientY - rect.top;

                if (
                    mouseX >= buttonX &&
                    mouseX <= buttonX + 80 &&
                    mouseY >= buttonY &&
                    mouseY <= buttonY + 30
                ) {
                    abrirModalEtiquetarElemento(elemento.id);
                }
            });

            clickableIDs.push({
                id: elemento.id,
                x: labelX - 45,
                y: labelY - 20,
                width: 50,
                height: 30,
            });
            // Botón "Dividir" (ya existente)

            const cambioX = canvas.width - 160; // puedes ajustarlo
            const cambioY = buttonY + 40;
            const cambioAncho = 150;
            const cambioAlto = 30;

            ctx.fillStyle = "#28a745";
            ctx.fillRect(cambioX, cambioY, cambioAncho, cambioAlto);
            ctx.fillStyle = "#fe7f09";
            ctx.font = "13px Arial";
            ctx.textAlign = "center";
            ctx.fillText(
                "🔁 Cambiar máquina",
                cambioX + cambioAncho / 2,
                cambioY + 20
            );

            // Área de clic
            canvas.addEventListener("click", function (event) {
                let rect = canvas.getBoundingClientRect();
                let mouseX = event.clientX - rect.left;
                let mouseY = event.clientY - rect.top;

                if (
                    mouseX >= cambioX &&
                    mouseX <= cambioX + cambioAncho &&
                    mouseY >= cambioY &&
                    mouseY <= cambioY + cambioAlto
                ) {
                    abrirModalCambioElemento(elemento.id);
                }
            });

            // CASO 1: ARC único
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

                // Acotación simple del radio (flecha y valor)
                const midAngle = angleRad / 2;
                const offset = 10;
                const textX =
                    centerX + (effectiveRadius + offset) * Math.cos(midAngle);
                const textY =
                    centerY + (effectiveRadius + offset) * Math.sin(midAngle);
                ctx.font = "12px Arial";
                ctx.fillStyle = VALOR_COTA_COLOR;
                ctx.fillText(arc.radius.toString() + "r", textX, textY);
            }
            // CASO 2: Línea única
            else if (dims.length === 1 && dims[0].type === "line") {
                const line = dims[0];
                const scale = availableWidth / Math.abs(line.length);
                const lineLength = Math.abs(line.length) * scale;
                ctx.strokeStyle = FIGURE_LINE_COLOR;
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(centerX - lineLength / 2, centerY);
                ctx.lineTo(centerX + lineLength / 2, centerY);
                ctx.stroke();

                // Acotación de la línea (flechas y valor)
                const pt1 = { x: centerX - lineLength / 2, y: centerY };
                const pt2 = { x: centerX + lineLength / 2, y: centerY };
                drawDimensionLine(ctx, pt1, pt2, line.length.toString(), 10);
            }
            // CASO 3: Figura compuesta
            else {
                // 1. Calcular bounding box en coords locales
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

                // Calculamos segmentos
                const segments = computeLineSegments(dims);

                // Definimos rotación sólo si la figura es más alta que ancha y tiene ≤ 7 segmentos
                let rotate = false;
                if (figWidth < figHeight && segments.length <= 7) {
                    rotate = true;
                }

                // Ajuste de dimensiones efectivas considerando la rotación
                const effectiveWidth = rotate ? figHeight : figWidth;
                const effectiveHeight = rotate ? figWidth : figHeight;

                // Centro de la figura en coords locales
                const figCenterX = (minX + maxX) / 2;
                const figCenterY = (minY + maxY) / 2;

                // Escala para encajar en el slot
                const scale = Math.min(
                    availableWidth / effectiveWidth,
                    availableSlotHeight / effectiveHeight
                );

                // 2. Dibujar la figura con transformaciones
                ctx.save();
                ctx.translate(centerX, centerY);
                if (rotate) {
                    ctx.rotate(-Math.PI / 2);
                }
                ctx.scale(scale, scale);
                ctx.translate(-figCenterX, -figCenterY);
                ctx.lineWidth = 2 / scale;
                dibujarFiguraPath(ctx, dims);
                ctx.restore();

                // 3. Acotaciones
                if (segments.length > 7) {
                    // Si la figura tiene más de 7 longitudes, sólo acotamos el bounding box
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

                    // Cota horizontal (arriba de la figura)
                    const yOffset = -15;
                    drawSimpleDimension(
                        ctx,
                        { x: globalMinX, y: globalMinY + yOffset },
                        { x: globalMaxX, y: globalMinY + yOffset },
                        totalWidth,
                        true,
                        0
                    );
                    // Cota vertical (a la izquierda de la figura)
                    const xOffset = -20;
                    drawSimpleDimension(
                        ctx,
                        { x: globalMinX + xOffset, y: globalMinY },
                        { x: globalMinX + xOffset, y: globalMaxY },
                        totalHeight,
                        false,
                        0
                    );
                } else {
                    // Acotar cada segmento
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

        canvas.addEventListener("click", function (event) {
            const rect = canvas.getBoundingClientRect();
            const mouseX = event.clientX - rect.left;
            const mouseY = event.clientY - rect.top;
            clickableIDs.forEach((item) => {
                if (
                    mouseX >= item.x &&
                    mouseX <= item.x + item.width &&
                    mouseY >= item.y &&
                    mouseY <= item.y + item.height
                ) {
                    agregarItemDesdeCanvas(item.id);
                }
            });
        });
    });
});

// =======================
// Función para agregar ítems desde el canvas
// =======================
function agregarItemDesdeCanvas(itemCode) {
    const itemType = "elemento";
    if (items.some((i) => i.id === itemCode)) {
        Swal.fire({
            icon: "error",
            title: "Item Duplicado",
            text: "Este item ya ha sido agregado.",
            confirmButtonColor: "#d33",
        });
        return;
    }
    const newItem = { id: itemCode, type: itemType };
    items.push(newItem);
    actualizarLista();
}

// =======================
// Función para etiquetar elementos
// =======================
function abrirModalEtiquetarElemento(idElemento) {
    const grupo = window.elementosAgrupadosScript.find((g) =>
        g.elementos.some((e) => e.id === idElemento)
    );

    if (!grupo || !grupo.etiqueta) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "No se encontró la etiqueta asociada al elemento.",
        });
        return;
    }

    const elemento = grupo.elementos.find((e) => e.id === idElemento);
    const barrasOriginales = elemento.barras ?? 0;

    if (barrasOriginales < 2) {
        Swal.fire({
            icon: "warning",
            title: "No se puede dividir",
            text: "Este elemento no tiene suficientes barras para dividirse.",
        });
        return;
    }

    const etiquetaSubId = grupo.etiqueta?.etiqueta_sub_id || "";
    const base = etiquetaSubId.split(".")[0]; // ETQ-25-0001.02 → ETQ-25-0001

    const subetiquetasExistentes = window.elementosAgrupadosScript
        .filter((g) => {
            const sub = g.etiqueta?.etiqueta_sub_id;
            return sub && sub.startsWith(base + ".");
        })
        .map((g) => g.etiqueta.etiqueta_sub_id);

    let subIdDisponible = null;
    for (let i = 1; i <= 100; i++) {
        const candidato = `${base}.${String(i).padStart(2, "0")}`;
        if (!subetiquetasExistentes.includes(candidato)) {
            subIdDisponible = candidato;
            break;
        }
    }

    if (!subIdDisponible) {
        Swal.fire({
            icon: "error",
            title: "Límite alcanzado",
            text: "No se pudo generar una nueva subetiqueta, ya existen demasiadas.",
        });
        return;
    }

    // Preguntar cuántas barras quiere mover
    Swal.fire({
        title: `¿Cuántas barras quieres mover?`,
        input: "number",
        inputAttributes: {
            min: 1,
            max: barrasOriginales,
            step: 1,
        },
        inputValue: 1,
        showCancelButton: true,
        confirmButtonText: "Mover",
        cancelButtonText: "Cancelar",
        preConfirm: (valor) => {
            const cantidad = parseInt(valor);
            if (
                isNaN(cantidad) ||
                cantidad < 1 ||
                cantidad > barrasOriginales
            ) {
                return Swal.showValidationMessage(
                    `Debes ingresar un número entre 1 y ${barrasOriginales - 1}`
                );
            }
            return cantidad;
        },
    }).then((result) => {
        if (!result.isConfirmed) return;

        const cantidadMover = result.value;

        fetch(`/subetiquetas/crear`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            body: JSON.stringify({
                elemento_id: idElemento,
                etiqueta_sub_id: subIdDisponible,
                cantidad: cantidadMover,
            }),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Elemento dividido",
                        text: data.message,
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error al crear",
                        text: data.message || "Algo salió mal",
                    });
                }
            })
            .catch((err) => {
                console.error(err);
                Swal.fire({
                    icon: "error",
                    title: "Error de red",
                    text: "No se pudo dividir el elemento.",
                });
            });
    });
}
function dibujarCanvasEtiqueta(canvasId, elementos) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const parent = canvas.parentElement;
    const canvasWidth = parent.clientWidth;
    const textHeight = 60;
    const buttonHeight = 0;
    const numElementos = elementos.length;

    const canvasHeight =
        textHeight +
        numElementos * minSlotHeight +
        (numElementos - 1) * gapSpacing +
        buttonHeight;

    canvas.width = canvasWidth;
    canvas.height = canvasHeight;

    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = "#fe7f09";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    const availableSlotHeight =
        (canvasHeight -
            textHeight -
            buttonHeight -
            (numElementos - 1) * gapSpacing) /
        numElementos;
    const availableWidth = canvasWidth - 2 * marginX;

    elementos.forEach((elemento, index) => {
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

        // Dibujar info de barras
        ctx.font = "14px Arial";
        ctx.fillStyle = BARS_TEXT_COLOR;
        ctx.textAlign = "right";
        ctx.fillText(
            `Ø${diametro} | ${peso} | x${barras}`,
            canvasWidth - 10,
            centerY - 40
        );

        // ID del elemento
        ctx.textAlign = "left";
        ctx.fillStyle = ELEMENT_TEXT_COLOR;
        ctx.fillText(`#${elemento.id}`, 10, centerY + 5);

        // Figura
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
            ctx.fillText(
                `${arc.radius}r`,
                centerX + effectiveRadius * Math.cos(midAngle),
                centerY + effectiveRadius * Math.sin(midAngle)
            );
        } else if (dims.length === 1 && dims[0].type === "line") {
            const line = dims[0];
            const scale = availableWidth / Math.abs(line.length);
            const length = line.length * scale;
            ctx.beginPath();
            ctx.moveTo(centerX - length / 2, centerY);
            ctx.lineTo(centerX + length / 2, centerY);
            ctx.strokeStyle = FIGURE_LINE_COLOR;
            ctx.lineWidth = 2;
            ctx.stroke();

            drawDimensionLine(
                ctx,
                { x: centerX - length / 2, y: centerY },
                { x: centerX + length / 2, y: centerY },
                `${line.length}`,
                10
            );
        } else {
            const points = computePathPoints(dims);
            let minX = Infinity,
                maxX = -Infinity,
                minY = Infinity,
                maxY = -Infinity;
            points.forEach((pt) => {
                minX = Math.min(minX, pt.x);
                maxX = Math.max(maxX, pt.x);
                minY = Math.min(minY, pt.y);
                maxY = Math.max(maxY, pt.y);
            });

            const segments = computeLineSegments(dims);
            const figWidth = maxX - minX;
            const figHeight = maxY - minY;
            const figCenterX = (minX + maxX) / 2;
            const figCenterY = (minY + maxY) / 2;
            let rotate = figWidth < figHeight && segments.length <= 7;

            const effectiveWidth = rotate ? figHeight : figWidth;
            const effectiveHeight = rotate ? figWidth : figHeight;
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
                drawDimensionLine(ctx, p1, p2, seg.length.toString(), 10);
            });
        }
    });
}
