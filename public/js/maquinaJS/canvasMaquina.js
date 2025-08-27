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

// tamaños de texto y separación de cotas
const SIZE_MAIN_TEXT = 14;
const SIZE_ID_TEXT = 12;
const SIZE_DIM_TEXT = 12;
const DIM_LINE_OFFSET = 12;
const DIM_LABEL_LIFT = 6;

// separación mínima del texto respecto a la figura y paso de alejamiento
const LABEL_CLEARANCE = 3; // px
const LABEL_STEP = 4; // px
const MAIN_ABOVE_GAP = 8;
const ID_BELOW_GAP = 12;

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
    txt.style.pointerEvents = "none"; // no intercepta clics sobre la figura
    txt.textContent = texto;
    svg.appendChild(txt);
}
function agregarTextoClickable(
    svg,
    x,
    y,
    texto,
    color = "blue",
    size = 12,
    anchor = "middle",
    onClick = null
) {
    const txt = document.createElementNS("http://www.w3.org/2000/svg", "text");
    txt.setAttribute("x", x);
    txt.setAttribute("y", y);
    txt.setAttribute("fill", color);
    txt.setAttribute("font-size", size);
    txt.setAttribute("text-anchor", anchor);
    txt.setAttribute("alignment-baseline", "middle");
    txt.style.cursor = "pointer";
    txt.textContent = texto;
    if (onClick) txt.addEventListener("click", onClick);
    svg.appendChild(txt);
    return txt;
}
function agregarCirculo(svg, cx, cy, r, color = FIGURE_LINE_COLOR, ancho = 2) {
    const c = document.createElementNS("http://www.w3.org/2000/svg", "circle");
    c.setAttribute("cx", cx);
    c.setAttribute("cy", cy);
    c.setAttribute("r", r);
    c.setAttribute("stroke", color);
    c.setAttribute("stroke-width", ancho);
    c.setAttribute("fill", "none");
    svg.appendChild(c);
}
function agregarPathD(svg, d, color = FIGURE_LINE_COLOR, ancho = 2) {
    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", d);
    path.setAttribute("stroke", color);
    path.setAttribute("fill", "none");
    path.setAttribute("stroke-width", ancho);
    svg.appendChild(path);
    return path;
}

// =======================
// Geometría base
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
            const start = { x: currentX, y: currentY };
            const end = {
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
// Helpers (evitar solapes de textos)
// =======================
function approxTextBox(text, size) {
    const w = text.length * size * 0.55;
    const h = size;
    return { w, h };
}
function rectsOverlap(a, b, m = 0) {
    return !(
        a.right + m < b.left ||
        a.left - m > b.right ||
        a.bottom + m < b.top ||
        a.top - m > b.bottom
    );
}
function clampXInside(cx, w, left, right) {
    const half = w / 2;
    return Math.max(left + half, Math.min(right - half, cx));
}

// =======================
// Colocación de texto principal (Ø, kg, xN)
// =======================
function placeMainLabel({
    svg,
    text,
    figBox,
    centerX,
    centerY,
    placedBoxes,
    safeLeft,
    safeRight,
    safeTop,
    safeBottom,
    baseSize = SIZE_MAIN_TEXT,
    minSize = 10,
    gapTop = MAIN_ABOVE_GAP,
    gapBottom = MAIN_ABOVE_GAP,
    gapSide = 6,
}) {
    const clearance = LABEL_CLEARANCE;
    const step = LABEL_STEP;
    const mkBox = (lx, ly, w, h) => ({
        left: lx - w / 2,
        right: lx + w / 2,
        top: ly - h / 2,
        bottom: ly + h / 2,
    });
    const ok = (box) =>
        box.left >= safeLeft &&
        box.right <= safeRight &&
        box.top >= safeTop &&
        box.bottom <= safeBottom &&
        !rectsOverlap(figBox, box, clearance) &&
        !placedBoxes.some((b) => rectsOverlap(b, box, clearance));

    // A) Arriba
    {
        let size = baseSize;
        let { w, h } = approxTextBox(text, size);
        let ly = figBox.top - gapTop;
        let tries = 0;
        while (ly - h / 2 >= safeTop) {
            let lx = clampXInside(centerX, w, safeLeft, safeRight);
            const box = mkBox(lx, ly, w, h);
            if (ok(box)) {
                agregarTexto(
                    svg,
                    lx,
                    ly,
                    text,
                    BARS_TEXT_COLOR,
                    size,
                    "middle"
                );
                placedBoxes.push(box);
                return;
            }
            ly -= step;
            tries++;
            if (tries > 200) break;
        }
    }
    // B) Abajo
    {
        let size = baseSize;
        let { w, h } = approxTextBox(text, size);
        let ly = figBox.bottom + gapBottom;
        let tries = 0;
        while (ly + h / 2 <= safeBottom) {
            let lx = clampXInside(centerX, w, safeLeft, safeRight);
            const box = mkBox(lx, ly, w, h);
            if (ok(box)) {
                agregarTexto(
                    svg,
                    lx,
                    ly,
                    text,
                    BARS_TEXT_COLOR,
                    size,
                    "middle"
                );
                placedBoxes.push(box);
                return;
            }
            ly += step;
            tries++;
            if (tries > 200) break;
        }
    }
    // C) Lados (izq/der) reduciendo tamaño si hace falta
    const trySide = (side) => {
        let size = baseSize;
        while (size >= minSize) {
            const { w, h } = approxTextBox(text, size);
            const ly = Math.max(
                safeTop + h / 2,
                Math.min(centerY, safeBottom - h / 2)
            );
            let lx;
            if (side === "left") {
                lx = Math.min(figBox.left - gapSide - w / 2, safeRight - w / 2);
                lx = Math.max(lx, safeLeft + w / 2);
                const box = mkBox(lx, ly, w, h);
                if (box.right <= figBox.left - LABEL_CLEARANCE && ok(box)) {
                    agregarTexto(
                        svg,
                        lx,
                        ly,
                        text,
                        BARS_TEXT_COLOR,
                        size,
                        "middle"
                    );
                    placedBoxes.push(box);
                    return true;
                }
            } else {
                lx = Math.max(figBox.right + gapSide + w / 2, safeLeft + w / 2);
                lx = Math.min(lx, safeRight - w / 2);
                const box = mkBox(lx, ly, w, h);
                if (box.left >= figBox.right + LABEL_CLEARANCE && ok(box)) {
                    agregarTexto(
                        svg,
                        lx,
                        ly,
                        text,
                        BARS_TEXT_COLOR,
                        size,
                        "middle"
                    );
                    placedBoxes.push(box);
                    return true;
                }
            }
            size -= 1;
        }
        return false;
    };
    if (trySide("left")) return;
    if (trySide("right")) return;

    // D) Fallback: abajo centrado reduciendo tamaño
    {
        let size = baseSize;
        let ly = Math.min(safeBottom - size / 2, figBox.bottom + gapBottom);
        while (size >= minSize) {
            const { w, h } = approxTextBox(text, size);
            let lx = clampXInside(centerX, w, safeLeft, safeRight);
            const box = mkBox(lx, ly, w, h);
            if (ok(box)) {
                agregarTexto(
                    svg,
                    lx,
                    ly,
                    text,
                    BARS_TEXT_COLOR,
                    size,
                    "middle"
                );
                placedBoxes.push(box);
                return;
            }
            size -= 1;
            ly = Math.min(safeBottom - size / 2, figBox.bottom + gapBottom);
        }
    }
}

// =======================
// Preproceso solapes (alarga tramo anterior)
// =======================
function ajustarLongitudesParaEvitarSolapes(dims, grow = OVERLAP_GROW_UNITS) {
    const out = dims.map((d) => ({ ...d }));
    let cx = 0,
        cy = 0,
        ang = 0;
    const prevSegs = [];
    let lastLineDir = null,
        lastLineIdxInPrevSegs = -1,
        lastLineIdxInDims = -1;

    const EPS = 1e-7;
    const deg2rad = (d) => (d * Math.PI) / 180;
    const isHorizontal = (a) => Math.abs(Math.sin(deg2rad(a))) < 1e-12;
    const overlap1D = (a1, b1, a2, b2) =>
        Math.min(b1, b2) - Math.max(a1, a2) > EPS;

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
            return false;
        };

        while (tryResolve()) {}

        const dir = { x: Math.cos(deg2rad(ang)), y: Math.sin(deg2rad(ang)) };
        const nx = cx + out[i].length * dir.x;
        const ny = cy + out[i].length * dir.y;
        const horiz = isHorizontal(ang);
        prevSegs.push({ x1: cx, y1: cy, x2: nx, y2: ny, horiz, y: cy, x: cx });

        lastLineDir = dir;
        lastLineIdxInPrevSegs = prevSegs.length - 1;
        lastLineIdxInDims = i;

        cx = nx;
        cy = ny;
    }

    return out;
}

// =======================
// Rotación si H>W
// =======================
function rotatePoint(p, cx, cy, deg) {
    const rad = (deg * Math.PI) / 180;
    const c = Math.cos(rad),
        s = Math.sin(rad);
    const dx = p.x - cx,
        dy = p.y - cy;
    return { x: cx + dx * c - dy * s, y: cy + dx * s + dy * c };
}

// =======================
// Path SVG con rectas + arcos
// =======================
function buildSvgPathFromDims(
    dims,
    cxModel,
    cyModel,
    rotDeg,
    scale,
    midX,
    midY,
    centerX,
    centerY
) {
    let dStr = "";
    let x = 0,
        y = 0,
        ang = 0;
    let started = false;

    const map = (px, py) => {
        const p = rotatePoint({ x: px, y: py }, cxModel, cyModel, rotDeg);
        return {
            x: centerX + (p.x - midX) * scale,
            y: centerY + (p.y - midY) * scale,
        };
    };

    const moveIfNeeded = () => {
        if (!started) {
            const m = map(x, y);
            dStr += `M ${m.x} ${m.y}`;
            started = true;
        }
    };

    dims.forEach((d) => {
        if (d.type === "turn") {
            ang += d.angle;
            return;
        }

        if (d.type === "line") {
            const nx = x + d.length * Math.cos((ang * Math.PI) / 180);
            const ny = y + d.length * Math.sin((ang * Math.PI) / 180);
            moveIfNeeded();
            const p = map(nx, ny);
            dStr += ` L ${p.x} ${p.y}`;
            x = nx;
            y = ny;
            return;
        }

        if (d.type === "arc") {
            const cx = x + d.radius * Math.cos(((ang + 90) * Math.PI) / 180);
            const cy = y + d.radius * Math.sin(((ang + 90) * Math.PI) / 180);

            const start = Math.atan2(y - cy, x - cx);
            const end = start + (d.arcAngle * Math.PI) / 180;

            const ex = cx + d.radius * Math.cos(end);
            const ey = cy + d.radius * Math.sin(end);

            const absAng = Math.abs(d.arcAngle) % 360;
            if (absAng < 1e-6 || Math.abs(d.arcAngle) >= 359.9) {
                // círculo completo → 2 semicircunferencias
                moveIfNeeded();
                const midAng = start + Math.sign(d.arcAngle) * Math.PI;
                const mx = cx + d.radius * Math.cos(midAng);
                const my = cy + d.radius * Math.sin(midAng);

                const pMid = map(mx, my);
                const pEnd = map(x, y); // vuelve al inicio
                const R = d.radius * scale;
                const sweep = d.arcAngle >= 0 ? 1 : 0;

                dStr += ` A ${R} ${R} 0 1 ${sweep} ${pMid.x} ${pMid.y}`;
                dStr += ` A ${R} ${R} 0 1 ${sweep} ${pEnd.x} ${pEnd.y}`;

                ang += d.arcAngle; // coherencia si hay más tramos
                return;
            }

            moveIfNeeded();
            const pEnd = map(ex, ey);
            const R = d.radius * scale;
            const largeArc = absAng > 180 ? 1 : 0;
            const sweep = d.arcAngle >= 0 ? 1 : 0;
            dStr += ` A ${R} ${R} 0 ${largeArc} ${sweep} ${pEnd.x} ${pEnd.y}`;

            x = ex;
            y = ey;
            ang += d.arcAngle;
        }
    });

    return dStr || "M 0 0";
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

            // Límites seguros para texto
            const safeLeft = 0,
                safeRight = ancho,
                safeTop = 0,
                safeBottom = alto;

            // 1) dims ajustadas
            const dimsRaw = extraerDimensiones(elemento.dimensiones || "");
            const dims = ajustarLongitudesParaEvitarSolapes(
                dimsRaw,
                OVERLAP_GROW_UNITS
            );

            const barras = elemento.barras ?? 0;
            const diametro = elemento.diametro ?? "N/A";
            const peso = elemento.peso ?? "N/A";

            // ---------- FIGURA con rotación automática ----------
            const ptsModel = computePathPoints(dims);

            // bbox original
            let minX = Math.min(...ptsModel.map((p) => p.x));
            let maxX = Math.max(...ptsModel.map((p) => p.x));
            let minY = Math.min(...ptsModel.map((p) => p.y));
            let maxY = Math.max(...ptsModel.map((p) => p.y));
            const cxModel = (minX + maxX) / 2;
            const cyModel = (minY + maxY) / 2;

            const needsRotate = maxY - minY > maxX - minX;
            const rotDeg = needsRotate ? -90 : 0;

            // bbox tras rotación (para escalar y centrar)
            const ptsRot = ptsModel.map((p) =>
                rotatePoint(p, cxModel, cyModel, rotDeg)
            );
            minX = Math.min(...ptsRot.map((p) => p.x));
            maxX = Math.max(...ptsRot.map((p) => p.x));
            minY = Math.min(...ptsRot.map((p) => p.y));
            maxY = Math.max(...ptsRot.map((p) => p.y));
            const figW = Math.max(1, maxX - minX);
            const figH = Math.max(1, maxY - minY);

            const scale = Math.min(
                (cellWidth * 0.8) / figW,
                (cellHeight * 0.6) / figH
            );
            const midX = (minX + maxX) / 2;
            const midY = (minY + maxY) / 2;

            // Path combinado (rectas + arcos)
            const dPath = buildSvgPathFromDims(
                dims,
                cxModel,
                cyModel,
                rotDeg,
                scale,
                midX,
                midY,
                centerX,
                centerY
            );
            const pathEl = agregarPathD(svg, dPath, FIGURE_LINE_COLOR, 2);

            // 👉 La figura es clicable para dividir
            const etiquetaClick = `${elemento.codigo ?? elemento.id}`;
            pathEl.style.cursor = "pointer";
            pathEl.setAttribute("tabindex", "0");
            pathEl.setAttribute("role", "button");
            pathEl.setAttribute(
                "aria-label",
                `Dividir elemento ${etiquetaClick}`
            );
            pathEl.addEventListener("click", () =>
                abrirModalDividirElemento(elemento.id, etiquetaClick)
            );
            pathEl.addEventListener("keydown", (e) => {
                if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    abrirModalDividirElemento(elemento.id, etiquetaClick);
                }
            });
            pathEl.addEventListener("mouseenter", () =>
                pathEl.setAttribute("stroke-width", 3)
            );
            pathEl.addEventListener("mouseleave", () =>
                pathEl.setAttribute("stroke-width", 2)
            );

            // bbox figura en SVG (para evitar solapes de textos)
            const ptsSvg = ptsRot.map((pt) => ({
                x: centerX + (pt.x - midX) * scale,
                y: centerY + (pt.y - midY) * scale,
            }));
            const figMinX = Math.min(...ptsSvg.map((p) => p.x));
            const figMaxX = Math.max(...ptsSvg.map((p) => p.x));
            const figMinY = Math.min(...ptsSvg.map((p) => p.y));
            const figMaxY = Math.max(...ptsSvg.map((p) => p.y));
            const figBox = {
                left: figMinX,
                right: figMaxX,
                top: figMinY,
                bottom: figMaxY,
            };

            // ---------- COTAS (solo texto, valor ORIGINAL, evitando pisar figura) ----------
            const segsModelAdj = computeLineSegments(dims);
            const segsModelOrig = computeLineSegments(dimsRaw);

            const placedBoxes = [];

            segsModelAdj.forEach((s, idx) => {
                const s1 = rotatePoint(s.start, cxModel, cyModel, rotDeg);
                const s2 = rotatePoint(s.end, cxModel, cyModel, rotDeg);

                const p1 = {
                    x: centerX + (s1.x - midX) * scale,
                    y: centerY + (s1.y - midY) * scale,
                };
                const p2 = {
                    x: centerX + (s2.x - midX) * scale,
                    y: centerY + (s2.y - midY) * scale,
                };

                const L = Math.hypot(p2.x - p1.x, p2.y - p1.y) || 1;
                let nx = (p2.y - p1.y) / L;
                let ny = -(p2.x - p1.x) / L;

                const mx = (p1.x + p2.x) / 2,
                    my = (p1.y + p2.y) / 2;
                if ((mx - centerX) * nx + (my - centerY) * ny < 0) {
                    nx = -nx;
                    ny = -ny;
                }

                let off = DIM_LINE_OFFSET;
                const label = (
                    segsModelOrig[idx]?.length ?? s.length
                ).toString();
                const { w: tw, h: th } = approxTextBox(label, SIZE_DIM_TEXT);

                while (true) {
                    const lx = mx + nx * off;
                    const ly = my + ny * off - DIM_LABEL_LIFT;
                    const labelBox = {
                        left: lx - tw / 2,
                        right: lx + tw / 2,
                        top: ly - th / 2,
                        bottom: ly + th / 2,
                    };
                    const collideFigure = rectsOverlap(
                        figBox,
                        labelBox,
                        LABEL_CLEARANCE
                    );
                    const collideOthers = placedBoxes.some((b) =>
                        rectsOverlap(b, labelBox, LABEL_CLEARANCE)
                    );
                    if (!collideFigure && !collideOthers) {
                        agregarTexto(
                            svg,
                            lx,
                            ly,
                            label,
                            VALOR_COTA_COLOR,
                            SIZE_DIM_TEXT
                        );
                        placedBoxes.push(labelBox);
                        break;
                    }
                    off += LABEL_STEP;
                }
            });

            // === TEXTO PRINCIPAL (Ø, peso, xN) ===
            const mainText = `Ø${diametro} | ${peso} | x${barras}`;
            placeMainLabel({
                svg,
                text: mainText,
                figBox,
                centerX,
                centerY,
                placedBoxes,
                safeLeft,
                safeRight,
                safeTop,
                safeBottom,
                baseSize: SIZE_MAIN_TEXT,
                minSize: 10,
                gapTop: MAIN_ABOVE_GAP,
                gapBottom: MAIN_ABOVE_GAP,
                gapSide: 8,
            });

            // // === Código/ID informativo (no clicable) debajo si cabe ===
            // {
            //     const idText = `${elemento.codigo ?? elemento.id}`;
            //     const { w, h } = approxTextBox(idText, SIZE_ID_TEXT);
            //     let lx = centerX;
            //     let ly = figBox.bottom + ID_BELOW_GAP;
            //     let tries = 0;
            //     while (true) {
            //         const box = {
            //             left: lx - w / 2,
            //             right: lx + w / 2,
            //             top: ly - h / 2,
            //             bottom: ly + h / 2,
            //         };
            //         const collideFig = rectsOverlap(
            //             figBox,
            //             box,
            //             LABEL_CLEARANCE
            //         );
            //         const collideCotas = placedBoxes.some((b) =>
            //             rectsOverlap(b, box, LABEL_CLEARANCE)
            //         );
            //         const inside =
            //             box.left >= 0 &&
            //             box.right <= ancho &&
            //             box.top >= 0 &&
            //             box.bottom <= alto;
            //         if (!collideFig && !collideCotas && inside) {
            //             agregarTexto(
            //                 svg,
            //                 lx,
            //                 ly,
            //                 idText,
            //                 ELEMENT_TEXT_COLOR,
            //                 SIZE_ID_TEXT,
            //                 "middle"
            //             );
            //             placedBoxes.push(box);
            //             break;
            //         }
            //         ly += LABEL_STEP;
            //         tries++;
            //         if (tries > 100) break;
            //     }
            // }
        });

        contenedor.innerHTML = "";
        contenedor.appendChild(svg);
    });
});

function abrirModalDividirElemento(elementoId, etiqueta = "") {
    const modal = document.getElementById("modalDividirElemento");
    const input = document.getElementById("dividir_elemento_id");
    const form = document.getElementById("formDividirElemento");
    if (!modal || !input || !form) return;
    input.value = elementoId;
    if (window.rutaDividirElemento)
        form.setAttribute("action", window.rutaDividirElemento);
    modal.classList.remove("hidden");
}

async function enviarDivision() {
    const form = document.getElementById("formDividirElemento");
    const url = form.getAttribute("action") || window.rutaDividirElemento;
    const fd = new FormData(form);
    try {
        const token =
            fd.get("_token") ||
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
        const res = await fetch(url, {
            method: "POST",
            headers: token ? { "X-CSRF-TOKEN": token } : {},
            body: fd,
        });
        const data = await res.json();
        if (!res.ok || !data.success)
            throw new Error(data.message || "Error al dividir");
        form.reset();
        document
            .getElementById("modalDividirElemento")
            ?.classList.add("hidden");
        if (window.Swal) Swal.fire("Hecho", data.message, "success");
        else alert(data.message);
    } catch (e) {
        if (window.Swal) Swal.fire("Error", e.message || "Error", "error");
        else alert(e.message || "Error");
    }
}
