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

// “recrecimiento” (en UNIDADES de las dimensiones, no en píxeles)
const OVERLAP_GROW_UNITS = 5;
// Tamaños de texto
const SIZE_MAIN_TEXT = 14; // "Ø... | ... | x..."
const SIZE_ID_TEXT = 12; // "#id"
const SIZE_DIM_TEXT = 12; // números de las cotas rojas

// Separaciones para cotas (por si subes el tamaño)
const DIM_LINE_OFFSET = 12; // antes 8: distancia de la cota a la figura
const DIM_LABEL_LIFT = 6; // antes 4: cuánto sube el número respecto a la línea de cota

// =======================
// Helpers SVG
// =======================
function crearSVG(width, height) {
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
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
    for (let i = 1; i < puntos.length; i++)
        d += ` L ${puntos[i].x} ${puntos[i].y}`;
    path.setAttribute("d", d);
    path.setAttribute("stroke", color);
    path.setAttribute("fill", "none");
    path.setAttribute("stroke-width", ancho);
    svg.appendChild(path);
}

// =======================
// Modelo geométrico existente
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
            dims.push({ type: "turn", angle: parseFloat(token.slice(0, -1)) });
        } else {
            dims.push({ type: "line", length: parseFloat(token) });
        }
        i++;
    }
    return dims;
}

function computePathPoints(dims) {
    let points = [],
        currentX = 0,
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
            const cx =
                currentX +
                d.radius * Math.cos(((currentAngle + 90) * Math.PI) / 180);
            const cy =
                currentY +
                d.radius * Math.sin(((currentAngle + 90) * Math.PI) / 180);
            const start = Math.atan2(currentY - cy, currentX - cx);
            const end = start + (d.arcAngle * Math.PI) / 180;
            currentX = cx + d.radius * Math.cos(end);
            currentY = cy + d.radius * Math.sin(end);
            currentAngle += d.arcAngle;
            points.push({ x: currentX, y: currentY });
        }
    });
    return points;
}

function computeLineSegments(dims) {
    let segments = [],
        currentX = 0,
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
            const cx =
                currentX +
                d.radius * Math.cos(((currentAngle + 90) * Math.PI) / 180);
            const cy =
                currentY +
                d.radius * Math.sin(((currentAngle + 90) * Math.PI) / 180);
            const start = Math.atan2(currentY - cy, currentX - cx);
            const end = start + (d.arcAngle * Math.PI) / 180;
            currentX = cx + d.radius * Math.cos(end);
            currentY = cy + d.radius * Math.sin(end);
            currentAngle += d.arcAngle;
        }
    });
    return segments;
}

// =======================
// NUEVO: Preproceso para alargar el tramo anterior
// cuando la nueva línea iría por encima de otra previa
// =======================
function ajustarLongitudesParaEvitarSolapes(dims, grow = OVERLAP_GROW_UNITS) {
    const out = dims.map((d) => ({ ...d }));

    let cx = 0,
        cy = 0,
        ang = 0; // cursor de dibujo
    const prevSegs = []; // segmentos ya consolidados (tras ajustes)
    let lastLineDir = null; // dirección (unitaria) de la última línea
    let lastLineIdxInPrevSegs = -1; // índice en prevSegs de la última línea
    let lastLineIdxInDims = -1; // índice en 'out' de la última línea

    const EPS = 1e-7;

    // Utilidades
    const deg2rad = (d) => (d * Math.PI) / 180;
    const isHorizontal = (a) => Math.abs(Math.sin(deg2rad(a))) < 1e-12;
    const overlap1D = (a1, b1, a2, b2) =>
        Math.min(b1, b2) - Math.max(a1, a2) > EPS;

    // Recorremos y vamos ajustando en caliente
    for (let i = 0; i < out.length; i++) {
        const d = out[i];

        if (d.type === "turn") {
            ang += d.angle;
            continue;
        }

        if (d.type === "arc") {
            const cx0 = cx + d.radius * Math.cos(deg2rad(ang + 90));
            const cy0 = cy + d.radius * Math.sin(deg2rad(ang + 90));
            const start = Math.atan2(cy - cy0, cx - cx0);
            const end = start + deg2rad(d.arcAngle);
            cx = cx0 + d.radius * Math.cos(end);
            cy = cy0 + d.radius * Math.sin(end);
            ang += d.arcAngle;
            lastLineDir = null;
            continue;
        }

        // d.type === "line"
        // Mientras el nuevo tramo solape a algún tramo previo paralelo en la MISMA línea,
        // alargamos la línea anterior (la ortogonal) en +grow y actualizamos el cursor.
        const tryResolve = () => {
            const dir = {
                x: Math.cos(deg2rad(ang)),
                y: Math.sin(deg2rad(ang)),
            };
            const endX = cx + out[i].length * dir.x;
            const endY = cy + out[i].length * dir.y;
            const horiz = isHorizontal(ang);

            for (const s of prevSegs) {
                if (horiz && s.horiz && Math.abs(cy - s.y) < EPS) {
                    if (
                        overlap1D(
                            Math.min(cx, endX),
                            Math.max(cx, endX),
                            Math.min(s.x1, s.x2),
                            Math.max(s.x1, s.x2)
                        )
                    ) {
                        // solapa: alargamos la línea anterior
                        if (
                            lastLineDir &&
                            lastLineIdxInPrevSegs >= 0 &&
                            lastLineIdxInDims >= 0
                        ) {
                            out[lastLineIdxInDims].length += grow;
                            cx += lastLineDir.x * grow;
                            cy += lastLineDir.y * grow;

                            // actualizamos el segmento previo
                            const ps = prevSegs[lastLineIdxInPrevSegs];
                            ps.x2 += lastLineDir.x * grow;
                            ps.y2 += lastLineDir.y * grow;
                            return true; // hemos resuelto una vez, volver a comprobar
                        }
                    }
                } else if (!horiz && !s.horiz && Math.abs(cx - s.x) < EPS) {
                    if (
                        overlap1D(
                            Math.min(cy, endY),
                            Math.max(cy, endY),
                            Math.min(s.y1, s.y2),
                            Math.max(s.y1, s.y2)
                        )
                    ) {
                        if (
                            lastLineDir &&
                            lastLineIdxInPrevSegs >= 0 &&
                            lastLineIdxInDims >= 0
                        ) {
                            out[lastLineIdxInDims].length += grow;
                            cx += lastLineDir.x * grow;
                            cy += lastLineDir.y * grow;

                            const ps = prevSegs[lastLineIdxInPrevSegs];
                            ps.x2 += lastLineDir.x * grow;
                            ps.y2 += lastLineDir.y * grow;
                            return true;
                        }
                    }
                }
            }
            return false; // no había solape
        };

        // Repite hasta que el tramo actual deje de solapar
        while (tryResolve()) {
            /* vacío */
        }

        // Consolidamos el tramo actual con la geometría ya corregida
        const dir = { x: Math.cos(deg2rad(ang)), y: Math.sin(deg2rad(ang)) };
        const nx = cx + out[i].length * dir.x;
        const ny = cy + out[i].length * dir.y;

        const horiz = isHorizontal(ang);
        prevSegs.push({
            x1: cx,
            y1: cy,
            x2: nx,
            y2: ny,
            horiz,
            y: cy,
            x: cx,
        });

        lastLineDir = dir;
        lastLineIdxInPrevSegs = prevSegs.length - 1;

        // Localiza en 'out' el índice de la última línea (yo mismo)
        lastLineIdxInDims = i;

        // avanza el cursor
        cx = nx;
        cy = ny;
    }

    return out;
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

        const ancho = 600,
            alto = 150;
        const svg = crearSVG(ancho, alto);

        const numElementos = grupo.elementos.length;
        const columnas = Math.ceil(Math.sqrt(numElementos));
        const filas = Math.ceil(numElementos / columnas);

        const cellWidth = (ancho - marginX) / columnas;
        const cellHeight = (alto - marginY) / filas;

        grupo.elementos.forEach((elemento, index) => {
            const fila = Math.floor(index / columnas);
            const col = index % columnas;

            const centerX = marginX + col * cellWidth + cellWidth / 2;
            const centerY = marginY + fila * cellHeight + cellHeight / 2;

            // 1) dims originales
            const dimsRaw = extraerDimensiones(elemento.dimensiones || "");
            // 2) dims ajustadas (aquí es donde alargamos 25→30, 106→111, …)
            const dims = ajustarLongitudesParaEvitarSolapes(
                dimsRaw,
                OVERLAP_GROW_UNITS
            );

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
                SIZE_MAIN_TEXT,
                "middle"
            );
            agregarTexto(
                svg,
                centerX,
                centerY + cellHeight / 4 - 8,
                `#${elemento.id}`,
                ELEMENT_TEXT_COLOR,
                SIZE_ID_TEXT,
                "middle"
            );

            // FIGURA
            const puntos = computePathPoints(dims);
            let minX = Math.min(...puntos.map((p) => p.x));
            let maxX = Math.max(...puntos.map((p) => p.x));
            let minY = Math.min(...puntos.map((p) => p.y));
            let maxY = Math.max(...puntos.map((p) => p.y));
            const figW = Math.max(1, maxX - minX);
            const figH = Math.max(1, maxY - minY);
            const scale = Math.min(
                (cellWidth * 0.8) / figW,
                (cellHeight * 0.6) / figH
            );

            const pts = puntos.map((pt) => ({
                x: centerX + (pt.x - (minX + maxX) / 2) * scale,
                y: centerY + (pt.y - (minY + maxY) / 2) * scale,
            }));
            agregarPath(svg, pts, FIGURE_LINE_COLOR, 2);

            // COTAS (se recalculan con las longitudes ya ajustadas → verás 30, 111, …)
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
                const o = DIM_LINE_OFFSET;
                const q1 = { x: p1.x + ux * o, y: p1.y + uy * o };
                const q2 = { x: p2.x + ux * o, y: p2.y + uy * o };

                // agregarLinea(svg, q1.x, q1.y, q2.x, q2.y, LINEA_COTA_COLOR, 1);
                agregarTexto(
                    svg,
                    (q1.x + q2.x) / 2,
                    (q1.y + q2.y) / 2 - DIM_LABEL_LIFT,
                    s.length.toString(),
                    VALOR_COTA_COLOR,
                    SIZE_DIM_TEXT
                );
            });
        });

        contenedor.innerHTML = "";
        contenedor.appendChild(svg);
    });
});
