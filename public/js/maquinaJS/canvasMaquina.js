// Márgenes internos para delimitar el área de dibujo
const marginX = 50; // margen horizontal
const marginY = 25; // margen vertical

// Espacio extra entre cada slot (recuadro)
// Se redujo de 10 a 5 para disminuir el espacio entre figuras.
const gapSpacing = 10;

// Altura mínima para cada slot (puedes ajustar según tus necesidades)
const minSlotHeight = 100;

// Accede a la variable global que inyectamos en el HTML
const elementos = window.elementosAgrupadosScript;

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
    const clickableIDs = []; // Declarar variable
    const parent = canvas.parentElement;
    // Ajusta el ancho del canvas al ancho del div padre
    const canvasWidth = parent.clientWidth;

    // Calcula la altura del canvas dinámicamente según el número de elementos
    const numElementos = grupo.elementos.length;
    const canvasHeight =
        marginY * 2 +
        numElementos * minSlotHeight +
        (numElementos - 1) * gapSpacing;
    canvas.width = canvasWidth;
    canvas.height = canvasHeight;

    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Se reparte la altura disponible en "slots" para cada figura,
    // descontando los márgenes y el espacio entre recuadros.
    const availableSlotHeight =
        (canvasHeight - 2 * marginY - (numElementos - 1) * gapSpacing) /
        numElementos;
    const availableWidth = canvasWidth - 2 * marginX;

    grupo.elementos.forEach((elemento, index) => {
        // Extraer longitudes, ángulos y, en su caso, datos de circunferencia a partir del string.
        // Ejemplos:
        // - Línea simple: "400"
        // - Figura compuesta: "15 90d 85 ..."
        // - Circunferencia o arco: "500r 90d"
        const dimensionesStr = elemento.dimensiones || "";
        const { longitudes, angulos, radio, arcAngle } =
            extraerDimensiones(dimensionesStr);
        const barras = elemento.barras || 0; // Si no tiene valor, asumir 0

        // Centro del slot asignado:
        // Se calcula el centro vertical considerando el gapSpacing:
        const centerX = marginX + availableWidth / 2;
        const centerY =
            marginY +
            availableSlotHeight / 2 +
            index * (availableSlotHeight + gapSpacing);

        // Si se detecta un radio, dibujamos una circunferencia (o arco)
        if (radio !== null) {
            // Calcular la escala para que la circunferencia se ajuste en el espacio disponible
            // Se considera el diámetro = 2 * radio
            const scale = Math.min(
                availableWidth / (2 * radio),
                availableSlotHeight / (2 * radio)
            );
            const effectiveRadius = radio * scale;
            // Convertir el ángulo del arco a radianes; si no se proporciona, se dibuja la circunferencia completa
            const angleRad =
                arcAngle !== null ? (arcAngle * Math.PI) / 180 : 2 * Math.PI;
            ctx.beginPath();
            ctx.arc(centerX, centerY, effectiveRadius, 0, angleRad, false);
            ctx.strokeStyle = "#0000FF";
            ctx.lineWidth = 2;
            ctx.stroke();

            // Acotación: mostrar el valor del radio en la posición media del arco
            const midAngle = angleRad / 2;
            const offset = 10;
            const textX =
                centerX + (effectiveRadius + offset) * Math.cos(midAngle);
            const textY =
                centerY + (effectiveRadius + offset) * Math.sin(midAngle);
            ctx.font = "12px Arial";
            ctx.fillStyle = "red";
            ctx.fillText(radio.toString() + "r", textX, textY);

            // Mostrar el valor de barras
            ctx.fillText(
                `x${barras}`,
                centerX + availableWidth / 2 + 15,
                centerY + 5
            );

            // Colocar el label en la esquina inferior derecha del slot
            const slotBottom =
                marginY +
                availableSlotHeight +
                index * (availableSlotHeight + gapSpacing);
            const labelX = marginX + availableWidth - 10;
            const labelY = slotBottom - 5;
            ctx.font = "14px Arial";
            ctx.fillStyle = "#FF0000";
            ctx.fillText(`#${elemento.id}`, labelX, labelY);
            clickableIDs.push({
                id: elemento.id,
                x: labelX,
                y: labelY - 14,
                width: 40,
                height: 20,
            });
        } else if (longitudes.length === 1) {
            // CASO: ÚNICA DIMENSIÓN (línea horizontal)
            const length = longitudes[0];
            // Se usa el availableWidth para escalar la figura
            const scale = availableWidth / Math.abs(length);
            const lineLength = Math.abs(length) * scale;

            ctx.strokeStyle = "#0000FF";
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(centerX - lineLength / 2, centerY);
            ctx.lineTo(centerX + lineLength / 2, centerY);
            ctx.stroke();

            // Acotación (en rojo)
            const pt1 = { x: centerX - lineLength / 2, y: centerY };
            const pt2 = { x: centerX + lineLength / 2, y: centerY };
            const midX = (pt1.x + pt2.x) / 2;
            const midY = (pt1.y + pt2.y) / 2;
            // Para una línea horizontal el ángulo es 0
            const angle = Math.atan2(pt2.y - pt1.y, pt2.x - pt1.x);
            const offset = 10;
            const offsetX = offset * Math.cos(angle - Math.PI / 2);
            const offsetY = offset * Math.sin(angle - Math.PI / 2);

            ctx.strokeStyle = "#0000FF";
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(pt1.x, pt1.y);
            ctx.lineTo(pt2.x, pt2.y);
            ctx.stroke();

            ctx.font = "12px Arial";
            ctx.fillStyle = "red";
            ctx.fillText(length.toString(), midX + offsetX, midY + offsetY);

            // Colocar el label en la esquina inferior derecha del slot
            const slotBottom =
                marginY +
                availableSlotHeight +
                index * (availableSlotHeight + gapSpacing);
            const labelX = marginX + availableWidth - 10;
            const labelY = slotBottom - 5;
            ctx.font = "14px Arial";
            ctx.fillStyle = "#FF0000";
            ctx.fillText(`#${elemento.id}`, labelX, labelY);
            ctx.fillText(
                `x${barras}`,
                centerX + availableWidth / 2 + 15,
                centerY + 5
            );
            clickableIDs.push({
                id: elemento.id,
                x: labelX,
                y: labelY - 14,
                width: 40,
                height: 20,
            });
        } else {
            // CASO: FIGURA COMPUESTA (varias dimensiones)
            // 1. Calcular el bounding box de la figura en sus coordenadas locales
            const { minX, maxX, minY, maxY } = calcularBoundingBox(
                longitudes,
                angulos
            );
            const figWidth = maxX - minX;
            const figHeight = maxY - minY;

            // 2. Determinar si la figura debe rotarse para que su lado mayor quede horizontal.
            //    Si el ancho es menor que la altura, se rota 90° (por -90°).
            let rotate = false;
            if (figWidth < figHeight) {
                rotate = true;
            }
            // Las dimensiones "efectivas" son:
            const effectiveWidth = rotate ? figHeight : figWidth;
            const effectiveHeight = rotate ? figWidth : figHeight;
            const figCenterX = (minX + maxX) / 2;
            const figCenterY = (minY + maxY) / 2;

            // 3. Calcular la escala para ajustar la figura al espacio disponible
            const scale = Math.min(
                availableWidth / effectiveWidth,
                availableSlotHeight / effectiveHeight
            );

            // 4. Aplicar las transformaciones: trasladar, (rotar si es necesario), escalar y centrar.
            ctx.save();
            ctx.translate(centerX, centerY);
            if (rotate) {
                ctx.rotate(-Math.PI / 2);
            }
            ctx.scale(scale, scale);
            // Para centrar la figura en sus coordenadas locales se traslada su centro.
            ctx.translate(-figCenterX, -figCenterY);

            ctx.strokeStyle = "#0000FF"; // Celeste clásico
            ctx.lineWidth = 2 / scale; // Mantener grosor constante
            ctx.lineCap = "round";
            ctx.lineJoin = "round";
            dibujarFiguraPath(ctx, longitudes, angulos);
            ctx.restore();

            // 5. Calcular y dibujar las acotaciones de cada segmento
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
                // Si la figura se rota, el ángulo en el canvas se ajusta restando 90°.
                const angleCanvas = rotate
                    ? angleLocal - Math.PI / 2
                    : angleLocal;
                const offset = 10;
                const offsetX = offset * Math.cos(angleCanvas - Math.PI / 2);
                const offsetY = offset * Math.sin(angleCanvas - Math.PI / 2);

                ctx.strokeStyle = "#0000FF"; // Celeste clásico
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
            ctx.fillText(
                `x${barras}`,
                centerX + availableWidth / 2 + 15,
                centerY + 5
            );
            // Colocar el label en la esquina inferior derecha del slot
            const slotBottom =
                marginY +
                availableSlotHeight +
                index * (availableSlotHeight + gapSpacing);
            const labelX = marginX + availableWidth - 10;
            const labelY = slotBottom - 5;
            ctx.font = "14px Arial";
            ctx.fillStyle = "#FF0000";
            ctx.fillText(`#${elemento.id}`, labelX, labelY);
            clickableIDs.push({
                id: elemento.id,
                x: labelX,
                y: labelY - 14,
                width: 40,
                height: 20,
            });
        }
    });

    // Agregar evento de clic al canvas
    canvas.addEventListener("click", function (event) {
        console.log("Canvas clickeado");

        const rect = canvas.getBoundingClientRect();
        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;
        console.log(`Clic detectado en: X=${mouseX}, Y=${mouseY}`);

        clickableIDs.forEach((item) => {
            if (
                mouseX >= item.x &&
                mouseX <= item.x + item.width &&
                mouseY >= item.y &&
                mouseY <= item.y + item.height
            ) {
                console.log(`Elemento ${item.id} clickeado`);
                agregarItemDesdeCanvas(item.id);
            }
        });
    });
});
function agregarItemDesdeCanvas(itemCode) {
    const itemType = "elemento"; // Puedes cambiarlo si hay diferentes tipos

    console.log("Intentando agregar item desde canvas:", {
        itemCode,
        itemType,
    });

    if (items.some((i) => i.id === itemCode)) {
        console.log("Item duplicado:", itemCode);
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
    console.log("Item agregado. Lista actualizada:", items);

    actualizarLista(); // Actualizar la lista en el DOM
}

/* Función que calcula el bounding box de la figura en coordenadas locales.
   Se recorre cada segmento acumulando posiciones (origen en 0,0). */
function calcularBoundingBox(longitudes, angulos) {
    let currentX = 0,
        currentY = 0,
        currentAngle = 0;
    let minX = 0,
        maxX = 0,
        minY = 0,
        maxY = 0;
    longitudes.forEach((longitud, i) => {
        const angleIncrement = angulos[i] || 0;
        const newX =
            currentX + longitud * Math.cos((currentAngle * Math.PI) / 180);
        const newY =
            currentY + longitud * Math.sin((currentAngle * Math.PI) / 180);
        minX = Math.min(minX, newX);
        maxX = Math.max(maxX, newX);
        minY = Math.min(minY, newY);
        maxY = Math.max(maxY, newY);
        currentX = newX;
        currentY = newY;
        currentAngle += angleIncrement;
    });
    return { minX, maxX, minY, maxY };
}

/* Función que dibuja la figura (el path) en el canvas usando el sistema de coordenadas natural.
   Se asume que ya se aplicaron las transformaciones (traslación, rotación y escala). */
function dibujarFiguraPath(ctx, longitudes, angulos) {
    ctx.beginPath();
    let currentX = 0,
        currentY = 0,
        currentAngle = 0;
    ctx.moveTo(currentX, currentY);
    longitudes.forEach((longitud, i) => {
        const angleIncrement = angulos[i] || 0;
        const newX =
            currentX + longitud * Math.cos((currentAngle * Math.PI) / 180);
        const newY =
            currentY + longitud * Math.sin((currentAngle * Math.PI) / 180);
        ctx.lineTo(newX, newY);
        currentX = newX;
        currentY = newY;
        currentAngle += angleIncrement;
    });
    ctx.stroke();
}

/* Función que extrae longitudes, ángulos y datos de circunferencia a partir de un string de dimensiones.
Se asume que:
- Las longitudes vienen sin sufijo.
- Los ángulos llevan la "d" (ej.: "15 90d 85 ...").
- Las circunferencias se especifican con un radio seguido de "r" (ej.: "500r")
  y opcionalmente un ángulo de arco seguido de "d" (ej.: "90d").
*/
function extraerDimensiones(dimensiones) {
    const longitudes = [];
    const angulos = [];
    let radio = null;
    let arcAngle = null;
    const tokens = dimensiones.split(/\s+/).filter((token) => token.length > 0);
    tokens.forEach((token) => {
        if (token.includes("r")) {
            const num = parseFloat(token.replace("r", ""));
            radio = isNaN(num) ? 50 : num;
        } else if (token.includes("d")) {
            const num = parseFloat(token.replace("d", ""));
            // Si ya se detectó un radio y aún no se ha asignado arcAngle, asignarlo
            if (radio !== null && arcAngle === null) {
                arcAngle = isNaN(num) ? 0 : num;
            } else {
                angulos.push(isNaN(num) ? 0 : num);
            }
        } else {
            const num = parseFloat(token);
            longitudes.push(isNaN(num) ? 50 : num);
        }
    });
    return { longitudes, angulos, radio, arcAngle };
}

/* Función que computa la lista de puntos (en coordenadas locales) de la figura.
Comienza en (0,0) y acumula la posición de cada segmento. */
function computePoints(longitudes, angulos) {
    const points = [];
    let cx = 0,
        cy = 0,
        ca = 0;
    points.push({ x: cx, y: cy });
    for (let i = 0; i < longitudes.length; i++) {
        const newX = cx + longitudes[i] * Math.cos((ca * Math.PI) / 180);
        const newY = cy + longitudes[i] * Math.sin((ca * Math.PI) / 180);
        points.push({ x: newX, y: newY });
        cx = newX;
        cy = newY;
        ca += angulos[i] || 0;
    }
    return points;
}

/* Función que transforma un punto de coordenadas locales a coordenadas del canvas.
   Aplica la misma transformación que en el dibujo de la figura:
     - Si no se rota: global = center + scale * ((x,y) - (figCenterX, figCenterY))
     - Si se rota 90° (por -90°): global = center + scale * ((y - figCenterY), -(x - figCenterX))
*/
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
