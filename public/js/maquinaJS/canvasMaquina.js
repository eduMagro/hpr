// =======================
// Colores y configuración
// =======================
const FIGURE_LINE_COLOR = "rgba(0, 0, 0, 0.8)";
const LINEA_COTA_COLOR = "rgba(255, 0, 0, 0.8)";
const VALOR_COTA_COLOR = "rgba(0, 0, 0, 1)";
const BARS_TEXT_COLOR = "rgba(0, 0, 0, 1)";
const ELEMENT_TEXT_COLOR = "blue";

const marginX = 10;
const marginY = 10;
const gapSpacing = 25;
const minSlotHeight = 50;

// =======================
// Funciones SVG Helpers
// =======================
function crearSVG(width, height) {
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    // ❌ fuera tamaño fijo en px
    // svg.setAttribute("width", width);
    // svg.setAttribute("height", height);

    // ✅ tamaño lógico + escalado al contenedor
    svg.setAttribute("viewBox", `0 0 ${width} ${height}`);
    svg.setAttribute("preserveAspectRatio", "xMidYMid meet");
    svg.style.width = "100%";
    svg.style.height = "70%";
    svg.style.display = "block";
    svg.style.background = "#fe7f09";
    svg.style.shapeRendering = "geometricPrecision";
    svg.style.textRendering = "optimizeLegibility";
    return svg;
}

function agregarLinea(svg, x1, y1, x2, y2, color = "black", ancho = 2) {
    const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
    line.setAttribute("x1", x1);
    line.setAttribute("y1", y1);
    line.setAttribute("x2", x2);
    line.setAttribute("y2", y2);
    line.setAttribute("stroke", color);
    line.setAttribute("stroke-width", ancho);
    svg.appendChild(line);
}

function agregarTexto(
    svg,
    x,
    y,
    texto,
    color = "black",
    size = 12,
    anchor = "middle"
) {
    const txt = document.createElementNS("http://www.w3.org/2000/svg", "text");
    txt.setAttribute("x", x);
    txt.setAttribute("y", y);
    txt.setAttribute("fill", color);
    txt.setAttribute("font-size", size);
    txt.setAttribute("text-anchor", anchor);
    txt.setAttribute("alignment-baseline", "middle");
    txt.textContent = texto;
    svg.appendChild(txt);
}

function agregarPath(svg, puntos, color = FIGURE_LINE_COLOR, ancho = 2) {
    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    let d = `M ${puntos[0].x} ${puntos[0].y}`;
    for (let i = 1; i < puntos.length; i++) {
        d += ` L ${puntos[i].x} ${puntos[i].y}`;
    }
    path.setAttribute("d", d);
    path.setAttribute("stroke", color);
    path.setAttribute("fill", "none");
    path.setAttribute("stroke-width", ancho);
    svg.appendChild(path);
}

// =======================
// Funciones existentes de cálculo (no cambian)
// =======================
function extraerDimensiones(dimensiones) {
    const tokens = dimensiones.split(/\s+/).filter((t) => t.length > 0);
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
// Script principal
// =======================
document.addEventListener("DOMContentLoaded", () => {
    const elementos = window.elementosAgrupadosScript;
    if (!elementos) return;

    elementos.forEach((grupo) => {
        const contenedor = document.getElementById(
            `contenedor-svg-${grupo.etiqueta?.id}`
        );
        if (!contenedor) return;

        // tamaño del área de dibujo
        const ancho = 600;
        const alto = 150;
        const svg = crearSVG(ancho, alto);

        const numElementos = grupo.elementos.length;

        // calcula columnas y filas dinámicas
        const columnas = Math.ceil(Math.sqrt(numElementos));
        const filas = Math.ceil(numElementos / columnas);

        const cellWidth = (ancho - marginX) / columnas;
        const cellHeight = (alto - marginY) / filas;

        grupo.elementos.forEach((elemento, index) => {
            const fila = Math.floor(index / columnas);
            const col = index % columnas;

            const centerX = marginX + col * cellWidth + cellWidth / 2;
            const centerY = marginY + fila * cellHeight + cellHeight / 2;

            const dims = extraerDimensiones(elemento.dimensiones || "");
            const barras = elemento.barras ?? 0;
            const diametro = elemento.diametro ?? "N/A";
            const peso = elemento.peso ?? "N/A";

            // textos
            agregarTexto(
                svg,
                centerX,
                centerY - cellHeight / 3 + 12,
                `Ø${diametro} | ${peso} | x${barras}`,
                BARS_TEXT_COLOR,
                10,
                "middle"
            );
            agregarTexto(
                svg,
                centerX,
                centerY + cellHeight / 4 - 8,
                `#${elemento.id}`,
                ELEMENT_TEXT_COLOR,
                10,
                "middle"
            );

            // dibuja figura
            const puntos = computePathPoints(dims);
            let minX = Math.min(...puntos.map((p) => p.x));
            let maxX = Math.max(...puntos.map((p) => p.x));
            let minY = Math.min(...puntos.map((p) => p.y));
            let maxY = Math.max(...puntos.map((p) => p.y));

            const figW = Math.max(1, maxX - minX);
            const figH = Math.max(1, maxY - minY);

            // usa casi todo el espacio de la celda
            const scale = Math.min(
                (cellWidth * 0.8) / figW,
                (cellHeight * 0.6) / figH
            );

            const pts = puntos.map((pt) => ({
                x: centerX + (pt.x - (minX + maxX) / 2) * scale,
                y: centerY + (pt.y - (minY + maxY) / 2) * scale,
            }));

            agregarPath(svg, pts, FIGURE_LINE_COLOR, 2);

            // cotas por segmento
            const segs = computeLineSegments(dims);
            segs.forEach((s) => {
                const p1 = {
                    x: centerX + (s.start.x - (minX + maxX) / 2) * scale,
                    y: centerY + (s.start.y - (minY + maxY) / 2) * scale,
                };
                const p2 = {
                    x: centerX + (s.end.x - (minX + maxX) / 2) * scale,
                    y: centerY + (s.end.y - (minY + maxY) / 2) * scale,
                };
                const ux = (p2.y - p1.y) / Math.hypot(p2.x - p1.x, p2.y - p1.y);
                const uy =
                    -(p2.x - p1.x) / Math.hypot(p2.x - p1.x, p2.y - p1.y);
                const o = 8;
                const q1 = { x: p1.x + ux * o, y: p1.y + uy * o };
                const q2 = { x: p2.x + ux * o, y: p2.y + uy * o };

                agregarLinea(svg, q1.x, q1.y, q2.x, q2.y, LINEA_COTA_COLOR, 1);

                agregarTexto(
                    svg,
                    (q1.x + q2.x) / 2,
                    (q1.y + q2.y) / 2 - 4,
                    s.length.toString(),
                    VALOR_COTA_COLOR,
                    9
                );
            });
        });

        contenedor.innerHTML = "";
        contenedor.appendChild(svg);
    });
});
