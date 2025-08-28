// =======================
// Colores y configuración
// =======================
const FIGURE_LINE_COLOR = "rgba(0, 0, 0, 0.8)";
const LINEA_COTA_COLOR = "rgba(255, 0, 0, 0.8)"; // solo para el texto de cota si lo necesitas
const VALOR_COTA_COLOR = "rgba(0, 0, 0, 1)";
const BARS_TEXT_COLOR = "rgba(0, 0, 0, 1)";
const ELEMENT_TEXT_COLOR = "blue";

const marginX = 10;
const marginY = 10;
const gapSpacing = 25;
const minSlotHeight = 50;

// “recrecimiento” (en UNIDADES del modelo, no px)
const OVERLAP_GROW_UNITS = 1;

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
    txt.style.pointerEvents = "none";
    txt.textContent = texto;
    svg.appendChild(txt);
}
function agregarPathD(svg, d, color = FIGURE_LINE_COLOR, ancho = 2) {
    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", d);
    path.setAttribute("stroke", color);
    path.setAttribute("fill", "none");
    path.setAttribute("stroke-width", ancho);
    path.setAttribute("vector-effect", "non-scaling-stroke");
    svg.appendChild(path);
    return path;
}

// =======================
// Geometría base
// =======================
function extraerDimensiones(dimensiones) {
    const tokens = dimensiones.split(/\s+/).filter(Boolean);
    const dims = [];
    for (let i = 0; i < tokens.length; i++) {
        const t = tokens[i];
        if (t.endsWith("r")) {
            const radius = parseFloat(t.slice(0, -1));
            let arcAngle = 360;
            if (i + 1 < tokens.length && tokens[i + 1].endsWith("d")) {
                arcAngle = parseFloat(tokens[++i].slice(0, -1));
            }
            dims.push({ type: "arc", radius, arcAngle });
        } else if (t.endsWith("d")) {
            dims.push({ type: "turn", angle: parseFloat(t.slice(0, -1)) });
        } else {
            dims.push({ type: "line", length: parseFloat(t) });
        }
    }
    return dims;
}
function computePathPoints(dims) {
    let pts = [],
        x = 0,
        y = 0,
        a = 0;
    pts.push({ x, y });
    dims.forEach((d) => {
        if (d.type === "line") {
            x += d.length * Math.cos((a * Math.PI) / 180);
            y += d.length * Math.sin((a * Math.PI) / 180);
            pts.push({ x, y });
        } else if (d.type === "turn") {
            a += d.angle;
        } else if (d.type === "arc") {
            const cx = x + d.radius * Math.cos(((a + 90) * Math.PI) / 180);
            const cy = y + d.radius * Math.sin(((a + 90) * Math.PI) / 180);
            const start = Math.atan2(y - cy, x - cx);
            const end = start + (d.arcAngle * Math.PI) / 180;
            x = cx + d.radius * Math.cos(end);
            y = cy + d.radius * Math.sin(end);
            a += d.arcAngle;
            pts.push({ x, y });
        }
    });
    return pts;
}
function computeLineSegments(dims) {
    let segs = [],
        x = 0,
        y = 0,
        a = 0;
    dims.forEach((d) => {
        if (d.type === "line") {
            const start = { x, y };
            const end = {
                x: x + d.length * Math.cos((a * Math.PI) / 180),
                y: y + d.length * Math.sin((a * Math.PI) / 180),
            };
            segs.push({ start, end, length: d.length });
            x = end.x;
            y = end.y;
        } else if (d.type === "turn") {
            a += d.angle;
        } else if (d.type === "arc") {
            const cx = x + d.radius * Math.cos(((a + 90) * Math.PI) / 180);
            const cy = y + d.radius * Math.sin(((a + 90) * Math.PI) / 180);
            const start = Math.atan2(y - cy, x - cx);
            const end = start + (d.arcAngle * Math.PI) / 180;
            x = cx + d.radius * Math.cos(end);
            y = cy + d.radius * Math.sin(end);
            a += d.arcAngle;
        }
    });
    return segs;
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
function combinarRectasConCeros(dims, tol = 1e-9) {
    const out = [];
    let acc = 0;
    const flush = () => {
        if (acc > tol) {
            out.push({ type: "line", length: acc });
            acc = 0;
        }
    };
    for (const d of dims) {
        if (d.type === "line") {
            acc += d.length;
            continue;
        }
        if (d.type === "turn") {
            if (Math.abs(d.angle) < tol) continue;
            flush();
            out.push(d);
            continue;
        }
        if (d.type === "arc") {
            flush();
            out.push(d);
            continue;
        }
        flush();
        out.push(d);
    }
    flush();
    return out;
}

// =======================
// Formateo + agrupado robusto por dirección
// =======================
function formatDimLabel(value, { decimals = 0, step = null } = {}) {
    let v = Number(value ?? 0);
    if (step && step > 0) v = Math.round(v / step) * step;
    return v.toFixed(decimals).replace(/\.0+$/, "");
}
function canonicalDir(dx, dy) {
    const L = Math.hypot(dx, dy) || 1;
    let ux = dx / L,
        uy = dy / L;
    if (uy < -1e-9 || (Math.abs(uy) <= 1e-9 && ux < 0)) {
        ux = -ux;
        uy = -uy;
    }
    return { ux, uy };
}
function dirKey(dx, dy, prec = 1e-2) {
    const { ux, uy } = canonicalDir(dx, dy);
    const qx = Math.round(ux / prec) * prec;
    const qy = Math.round(uy / prec) * prec;
    return `${qx}|${qy}`;
}
function agruparPorDireccionYEtiquetaRobusto(
    segsAdj,
    segsOrig,
    { dirPrecision = 1e-2, labelFormat = { decimals: 0, step: null } } = {}
) {
    const buckets = new Map(); // dirección -> longitudes ORIGINALES
    for (const s of segsOrig) {
        const dx = s.end.x - s.start.x,
            dy = s.end.y - s.start.y;
        const key = dirKey(dx, dy, dirPrecision);
        const arr = buckets.get(key) || [];
        arr.push(s.length ?? Math.hypot(dx, dy));
        buckets.set(key, arr);
    }

    const seen = new Set();
    const res = [];
    for (const s of segsAdj) {
        const dx = s.end.x - s.start.x,
            dy = s.end.y - s.start.y;
        const key = dirKey(dx, dy, dirPrecision);
        const candidates = buckets.get(key) || [];
        const adjLen = Math.hypot(dx, dy);
        let chosen = adjLen;
        if (candidates.length) {
            let best = candidates[0],
                bestD = Math.abs(best - adjLen);
            for (let i = 1; i < candidates.length; i++) {
                const d = Math.abs(candidates[i] - adjLen);
                if (d < bestD) {
                    best = candidates[i];
                    bestD = d;
                }
            }
            chosen = best;
        }
        const label = formatDimLabel(chosen, labelFormat);
        const k2 = `${key}|${label}`;
        if (seen.has(k2)) continue;
        seen.add(k2);
        res.push({ ...s, _dirKey: key, _label: label, _lenChosen: chosen });
    }
    return res;
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
    const clearance = LABEL_CLEARANCE,
        step = LABEL_STEP;
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

    // arriba
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
            if (++tries > 200) break;
        }
    }
    // abajo
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
            if (++tries > 200) break;
        }
    }
    // laterales
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

    // fallback
    {
        let size = baseSize;
        let ly = Math.min(safeBottom - size / 2, figBox.bottom + gapBottom);
        while (size >= minSize) {
            const { w, h } = approxTextBox(text, size);
            let lx = clampXInside(centerX, w, safeLeft, safeRight);
            const box = {
                left: lx - w / 2,
                right: lx + w / 2,
                top: ly - h / 2,
                bottom: ly + h / 2,
            };
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
    const prev = [];
    let lastDir = null,
        lastIdxPrev = -1,
        lastIdxDims = -1;
    const EPS = 1e-7,
        deg2rad = (d) => (d * Math.PI) / 180,
        isH = (a) => Math.abs(Math.sin(deg2rad(a))) < 1e-12;
    const overlap = (a1, b1, a2, b2) =>
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
            lastDir = null;
            continue;
        }
        const tryResolve = () => {
            const dir = {
                x: Math.cos(deg2rad(ang)),
                y: Math.sin(deg2rad(ang)),
            };
            const ex = cx + out[i].length * dir.x,
                ey = cy + out[i].length * dir.y;
            const horiz = isH(ang);
            for (const s of prev) {
                if (horiz && s.horiz && Math.abs(cy - s.y) < EPS) {
                    if (
                        overlap(
                            Math.min(cx, ex),
                            Math.max(cx, ex),
                            Math.min(s.x1, s.x2),
                            Math.max(s.x1, s.x2)
                        )
                    ) {
                        if (lastDir && lastIdxPrev >= 0 && lastIdxDims >= 0) {
                            out[lastIdxDims].length += grow;
                            cx += lastDir.x * grow;
                            cy += lastDir.y * grow;
                            const ps = prev[lastIdxPrev];
                            ps.x2 += lastDir.x * grow;
                            ps.y2 += lastDir.y * grow;
                            return true;
                        }
                    }
                } else if (!horiz && !s.horiz && Math.abs(cx - s.x) < EPS) {
                    if (
                        overlap(
                            Math.min(cy, ey),
                            Math.max(cy, ey),
                            Math.min(s.y1, s.y2),
                            Math.max(s.y1, s.y2)
                        )
                    ) {
                        if (lastDir && lastIdxPrev >= 0 && lastIdxDims >= 0) {
                            out[lastIdxDims].length += grow;
                            cx += lastDir.x * grow;
                            cy += lastDir.y * grow;
                            const ps = prev[lastIdxPrev];
                            ps.x2 += lastDir.x * grow;
                            ps.y2 += lastDir.y * grow;
                            return true;
                        }
                    }
                }
            }
            return false;
        };
        while (tryResolve()) {}

        const dir = { x: Math.cos(deg2rad(ang)), y: Math.sin(deg2rad(ang)) };
        const nx = cx + out[i].length * dir.x,
            ny = cy + out[i].length * dir.y;
        const horiz = isH(ang);
        prev.push({ x1: cx, y1: cy, x2: nx, y2: ny, horiz, y: cy, x: cx });
        lastDir = dir;
        lastIdxPrev = prev.length - 1;
        lastIdxDims = i;
        cx = nx;
        cy = ny;
    }
    return out;
}

// =======================
// Rotación y path
// =======================
function rotatePoint(p, cx, cy, deg) {
    const r = (deg * Math.PI) / 180,
        c = Math.cos(r),
        s = Math.sin(r);
    const dx = p.x - cx,
        dy = p.y - cy;
    return { x: cx + dx * c - dy * s, y: cy + dx * s + dy * c };
}
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
    let dStr = "",
        x = 0,
        y = 0,
        ang = 0,
        started = false;
    const map = (px, py) => {
        const p = rotatePoint({ x: px, y: py }, cxModel, cyModel, rotDeg);
        return {
            x: centerX + (p.x - midX) * scale,
            y: centerY + (p.y - midY) * scale,
        };
    };
    const move = () => {
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
            move();
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
            move();
            if (absAng < 1e-6 || Math.abs(d.arcAngle) >= 359.9) {
                const midAng = start + Math.sign(d.arcAngle) * Math.PI;
                const mx = cx + d.radius * Math.cos(midAng);
                const my = cy + d.radius * Math.sin(midAng);
                const pMid = map(mx, my),
                    pEnd = map(x, y);
                const R = d.radius * scale,
                    sweep = d.arcAngle >= 0 ? 1 : 0;
                dStr += ` A ${R} ${R} 0 1 ${sweep} ${pMid.x} ${pMid.y}`;
                dStr += ` A ${R} ${R} 0 1 ${sweep} ${pEnd.x} ${pEnd.y}`;
                ang += d.arcAngle;
                return;
            }
            const pEnd = map(ex, ey);
            const R = d.radius * scale,
                largeArc = absAng > 180 ? 1 : 0,
                sweep = d.arcAngle >= 0 ? 1 : 0;
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

        grupo.elementos.forEach((elemento, idx) => {
            const fila = Math.floor(idx / columnas);
            const col = idx % columnas;

            const centerX = marginX + col * cellWidth + cellWidth / 2;
            const centerY = marginY + fila * cellHeight + cellHeight / 2;

            const safeLeft = 0,
                safeRight = ancho,
                safeTop = 0,
                safeBottom = alto;

            // dims normalizadas + anti-solape
            const dimsRaw = extraerDimensiones(elemento.dimensiones || "");
            const dimsNoZero = combinarRectasConCeros(dimsRaw);
            const dims = ajustarLongitudesParaEvitarSolapes(
                dimsNoZero,
                OVERLAP_GROW_UNITS
            );

            const barras = elemento.barras ?? 0;
            const diametro = elemento.diametro ?? "N/A";
            const peso = elemento.peso ?? "N/A";

            // Figura
            const ptsModel = computePathPoints(dims);
            let minX = Math.min(...ptsModel.map((p) => p.x));
            let maxX = Math.max(...ptsModel.map((p) => p.x));
            let minY = Math.min(...ptsModel.map((p) => p.y));
            let maxY = Math.max(...ptsModel.map((p) => p.y));
            const cxModel = (minX + maxX) / 2,
                cyModel = (minY + maxY) / 2;

            const needsRotate = maxY - minY > maxX - minX;
            const rotDeg = needsRotate ? -90 : 0;

            const ptsRot = ptsModel.map((p) =>
                rotatePoint(p, cxModel, cyModel, rotDeg)
            );
            minX = Math.min(...ptsRot.map((p) => p.x));
            maxX = Math.max(...ptsRot.map((p) => p.x));
            minY = Math.min(...ptsRot.map((p) => p.y));
            maxY = Math.max(...ptsRot.map((p) => p.y));
            const figW = Math.max(1, maxX - minX),
                figH = Math.max(1, maxY - minY);

            const scale = Math.min(
                (cellWidth * 0.8) / figW,
                (cellHeight * 0.6) / figH
            );
            const midX = (minX + maxX) / 2,
                midY = (minY + maxY) / 2;

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

            // clic para dividir
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

            // bbox figura
            const ptsSvg = ptsRot.map((pt) => ({
                x: centerX + (pt.x - midX) * scale,
                y: centerY + (pt.y - midY) * scale,
            }));
            const figMinX = Math.min(...ptsSvg.map((p) => p.x)),
                figMaxX = Math.max(...ptsSvg.map((p) => p.x));
            const figMinY = Math.min(...ptsSvg.map((p) => p.y)),
                figMaxY = Math.max(...ptsSvg.map((p) => p.y));
            const figBox = {
                left: figMinX,
                right: figMaxX,
                top: figMinY,
                bottom: figMaxY,
            };

            // === COTAS (SOLO TEXTO + HIGHLIGHT EN EL TRAMO AL PASAR POR EL NÚMERO) ===
            const segsAdj = computeLineSegments(dims); // recrecidos
            const segsOrig = computeLineSegments(dimsNoZero); // originales

            const segsUnicos = agruparPorDireccionYEtiquetaRobusto(
                segsAdj,
                segsOrig,
                {
                    dirPrecision: 1e-2, // sube a 2e-2 si aún te duplica
                    labelFormat: { decimals: 0, step: null }, // p.ej. {decimals:0, step:5}
                }
            );

            const placedBoxes = [];

            segsUnicos.forEach((s) => {
                // rotar segmento ajustado al SVG
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
                let nx = (p2.y - p1.y) / L,
                    ny = -(p2.x - p1.x) / L;

                const mx = (p1.x + p2.x) / 2,
                    my = (p1.y + p2.y) / 2;
                if ((mx - centerX) * nx + (my - centerY) * ny < 0) {
                    nx = -nx;
                    ny = -ny;
                }

                // etiqueta visible
                const label = s._label;
                const { w: tw, h: th } = approxTextBox(label, SIZE_DIM_TEXT);

                let off = DIM_LINE_OFFSET,
                    lx,
                    ly,
                    labelBox;
                while (true) {
                    lx = mx + nx * off;
                    ly = my + ny * off - DIM_LABEL_LIFT;
                    // ⚠️ aquí estaba el bug: usar 'th' (no 'h')
                    labelBox = {
                        left: lx - tw / 2,
                        right: lx + tw / 2,
                        top: ly - th / 2,
                        bottom: ly + th / 2,
                    };
                    const collideFig = rectsOverlap(
                        figBox,
                        labelBox,
                        LABEL_CLEARANCE
                    );
                    const collideOth = placedBoxes.some((b) =>
                        rectsOverlap(b, labelBox, LABEL_CLEARANCE)
                    );
                    if (!collideFig && !collideOth) break;
                    off += LABEL_STEP;
                }

                // highlight del tramo real (oculto) — se muestra SOLO con el número
                const hl = document.createElementNS(
                    "http://www.w3.org/2000/svg",
                    "line"
                );
                hl.setAttribute("x1", p1.x);
                hl.setAttribute("y1", p1.y);
                hl.setAttribute("x2", p2.x);
                hl.setAttribute("y2", p2.y);
                hl.setAttribute("stroke", "rgba(0,0,255,0.6)");
                hl.setAttribute("stroke-width", "4");
                hl.setAttribute("stroke-linecap", "round");
                hl.setAttribute("vector-effect", "non-scaling-stroke");
                hl.style.opacity = 0;
                hl.style.pointerEvents = "none";
                hl.style.transition = "opacity 120ms ease";
                svg.appendChild(hl);

                // texto de cota (interactivo)
                const txt = document.createElementNS(
                    "http://www.w3.org/2000/svg",
                    "text"
                );
                txt.setAttribute("x", lx);
                txt.setAttribute("y", ly);
                txt.setAttribute("fill", VALOR_COTA_COLOR);
                txt.setAttribute("font-size", SIZE_DIM_TEXT);
                txt.setAttribute("text-anchor", "middle");
                txt.setAttribute("alignment-baseline", "middle");
                txt.setAttribute("tabindex", "0");
                txt.style.cursor = "pointer";
                txt.textContent = label;
                svg.appendChild(txt); // encima del highlight

                const onEnter = () => {
                    hl.style.opacity = 1;
                };
                const onLeave = () => {
                    hl.style.opacity = 0;
                };
                txt.addEventListener("mouseenter", onEnter);
                txt.addEventListener("mouseleave", onLeave);
                txt.addEventListener("focus", onEnter);
                txt.addEventListener("blur", onLeave);

                placedBoxes.push(labelBox);
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
        });

        contenedor.innerHTML = "";
        contenedor.appendChild(svg);
    });
});

// =======================
// Modal dividir elemento
// =======================
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
