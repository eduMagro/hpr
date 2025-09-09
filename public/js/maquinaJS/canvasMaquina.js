// =======================
// Colores y configuración
// =======================
const FIGURE_LINE_COLOR = "rgba(0, 0, 0, 0.8)";
const VALOR_COTA_COLOR = "rgba(0, 0, 0, 1)";
const BARS_TEXT_COLOR = "rgba(0, 0, 0, 1)";

const marginX = 5;
const marginY = 5;

// “recrecimiento” (en UNIDADES del modelo, no px)
const OVERLAP_GROW_UNITS = 0.6;

// tamaños de texto y separación de cotas
const SIZE_MAIN_TEXT = 18;
const SIZE_DIM_TEXT = 14;
const DIM_LINE_OFFSET = 16;
const DIM_LABEL_LIFT = 10; // px vertical (o horizontal) extra para separar la etiqueta de la línea
// separación de la cota respecto a la línea y cómo se desplaza si choca
const DIM_OFFSET = 10; // px perpendicular a la línea
const DIM_TANG_STEP = 6; // px por intento a lo largo de la línea
const DIM_TANG_MAX_FRAC = 0.45; // % de la longitud del tramo (desde el centro) como límite

// separación mínima del texto respecto a la figura y paso de alejamiento
const LABEL_CLEARANCE = 3; // px
const LABEL_STEP = 4; // px

// Reserva para layout (bandas)
const TOP_BAND_HEIGHT = 26; // alto de franja lógica arriba
const TOP_BAND_GAP = 14; // separación figura-banda arriba
const TOP_BAND_PAD_X = 6; // padding horizontal del texto

const SIDE_BAND_GAP = 12; // separación figura-banda lateral
const SIDE_BAND_PAD = 6; // padding interno lateral

// Reserva mínima para “anillo de cotas”
const DIM_RING_MARGIN = DIM_LINE_OFFSET + SIZE_DIM_TEXT + DIM_LABEL_LIFT + 6;

// =======================
// Helpers SVG
// =======================

function getEstadoColorFromCSSVar(contenedor) {
    const proceso = contenedor.closest(".proceso");
    if (!proceso) return "#e5e7eb";
    const color = getComputedStyle(proceso)
        .getPropertyValue("--bg-estado")
        .trim();
    return color || "#e5e7eb";
}

function crearSVG(width, height, bgColor) {
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("viewBox", "0 0 " + width + " " + height);
    svg.setAttribute("preserveAspectRatio", "xMidYMid meet");
    svg.style.width = "100%";
    svg.style.height = "70%";
    svg.style.display = "block";
    svg.style.background = bgColor || "#ffffff";
    svg.style.shapeRendering = "geometricPrecision";
    svg.style.textRendering = "optimizeLegibility";
    return svg;
}

function agregarTexto(svg, x, y, texto, color, size, anchor) {
    const txt = document.createElementNS("http://www.w3.org/2000/svg", "text");
    txt.setAttribute("x", x);
    txt.setAttribute("y", y);
    txt.setAttribute("fill", color || "black");
    txt.setAttribute("font-size", size || 16);
    txt.setAttribute("text-anchor", anchor || "middle");
    txt.setAttribute("alignment-baseline", "middle");
    txt.style.pointerEvents = "none";
    txt.textContent = texto;
    svg.appendChild(txt);
}
function agregarPathD(svg, d, color, ancho) {
    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", d);
    path.setAttribute("stroke", color || FIGURE_LINE_COLOR);
    path.setAttribute("fill", "none");
    path.setAttribute("stroke-width", ancho || 2);
    path.setAttribute("vector-effect", "non-scaling-stroke");
    svg.appendChild(path);
    return path;
}

// =======================
// Letras y leyenda
// =======================
function indexToLetters(n) {
    // 0 -> A, 25 -> Z, 26 -> AA, etc.
    let s = "";
    let i = Number(n) || 0;
    while (i >= 0) {
        const r = i % 26;
        s = String.fromCharCode(65 + r) + s;
        i = Math.floor(i / 26) - 1;
    }
    return s;
}
function drawLegendBottomLeft(svg, entries, width, height) {
    if (!entries || !entries.length) return;
    const pad = 8;
    const gap = 4;
    const size = 12;

    // Sin título: solo líneas A — Ø... | ... | xN
    const lines = entries.map(
        (e) => (e.letter ? e.letter + " — " : "") + (e.text || "")
    );

    // Medición para colocar bloque dentro del área inferior
    let maxW = 0;
    for (let i = 0; i < lines.length; i++) {
        const w = approxTextBox(lines[i], size).w;
        if (w > maxW) maxW = w;
    }
    const boxW = Math.min(
        Math.max(120, maxW + pad * 2),
        Math.max(140, width * 0.6)
    );
    const boxH = pad * 2 + lines.length * size + (lines.length - 1) * gap;

    const x = marginX;
    const y = Math.max(marginY, height - boxH - marginY);

    // Solo texto, sin fondo ni borde
    let cy = y + pad + size / 2;
    for (let i = 0; i < lines.length; i++) {
        const anchorX = x + pad;
        const t = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "text"
        );
        t.setAttribute("x", anchorX);
        t.setAttribute("y", cy);
        t.setAttribute("fill", BARS_TEXT_COLOR);
        t.setAttribute("font-size", size);
        t.setAttribute("text-anchor", "start");
        t.setAttribute("alignment-baseline", "middle");
        t.style.pointerEvents = "none";
        t.textContent = lines[i];
        svg.appendChild(t);
        cy += size + gap;
    }
}

// =======================
// Geometría base
// =======================
function extraerDimensiones(dimensiones) {
    const tokens = (dimensiones || "").split(/\s+/).filter(Boolean);
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
    for (let k = 0; k < dims.length; k++) {
        const d = dims[k];
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
    }
    return pts;
}
function computeLineSegments(dims) {
    let segs = [],
        x = 0,
        y = 0,
        a = 0;
    for (let k = 0; k < dims.length; k++) {
        const d = dims[k];
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
    }
    return segs;
}

// =======================
// Helpers (evitar solapes de textos)
// =======================
function approxTextBox(text, size) {
    const s = size || 12;
    return { w: (text ? text.length : 0) * s * 0.55, h: s };
}
function rectsOverlap(a, b, m) {
    const mm = m || 0;
    return !(
        a.right + mm < b.left ||
        a.left - mm > b.right ||
        a.bottom + mm < b.top ||
        a.top - mm > b.bottom
    );
}
function clampXInside(cx, w, left, right) {
    const half = w / 2;
    return Math.max(left + half, Math.min(right - half, cx));
}
function combinarRectasConCeros(dims, tol) {
    const TOL = typeof tol === "number" ? tol : 1e-9;
    const out = [];
    let acc = 0;
    function flush() {
        if (acc > TOL) {
            out.push({ type: "line", length: acc });
            acc = 0;
        }
    }
    for (let i = 0; i < dims.length; i++) {
        const d = dims[i];
        if (d.type === "line") {
            acc += d.length;
            continue;
        }
        if (d.type === "turn") {
            if (Math.abs(d.angle) < TOL) continue;
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
function formatDimLabel(value, opt) {
    const decimals = (opt && opt.decimals) || 0;
    const step = (opt && opt.step) || null;
    let v = Number(value || 0);
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
function dirKey(dx, dy, prec) {
    const p = prec || 1e-2;
    const d = canonicalDir(dx, dy);
    const qx = Math.round(d.ux / p) * p;
    const qy = Math.round(d.uy / p) * p;
    return qx + "|" + qy;
}
function agruparPorDireccionYEtiquetaRobusto(segsAdj, segsOrig, opt) {
    const dirPrecision = (opt && opt.dirPrecision) || 1e-2;
    const labelFormat = (opt && opt.labelFormat) || { decimals: 0, step: null };

    const buckets = new Map(); // dirección -> longitudes ORIGINALES
    for (let i = 0; i < segsOrig.length; i++) {
        const s = segsOrig[i];
        const dx = s.end.x - s.start.x,
            dy = s.end.y - s.start.y;
        const key = dirKey(dx, dy, dirPrecision);
        const arr = buckets.get(key) || [];
        arr.push(s.length || Math.hypot(dx, dy));
        buckets.set(key, arr);
    }

    const seen = new Set();
    const res = [];
    for (let i = 0; i < segsAdj.length; i++) {
        const s = segsAdj[i];
        const dx = s.end.x - s.start.x,
            dy = s.end.y - s.start.y;
        const key = dirKey(dx, dy, dirPrecision);
        const candidates = buckets.get(key) || [];
        const adjLen = Math.hypot(dx, dy);
        let chosen = adjLen;
        if (candidates.length) {
            let best = candidates[0],
                bestD = Math.abs(best - adjLen);
            for (let j = 1; j < candidates.length; j++) {
                const d = Math.abs(candidates[j] - adjLen);
                if (d < bestD) {
                    best = candidates[j];
                    bestD = d;
                }
            }
            chosen = best;
        }
        const label = formatDimLabel(chosen, labelFormat);
        const k2 = key + "|" + label;
        if (seen.has(k2)) continue;
        seen.add(k2);
        res.push({
            start: s.start,
            end: s.end,
            _dirKey: key,
            _label: label,
            _lenChosen: chosen,
        });
    }
    return res;
}

// =======================
// Texto principal – bandas (arriba o lateral)
// =======================
function placeMainLabelTopBand(params) {
    const svg = params.svg,
        text = params.text,
        figBox = params.figBox,
        centerX = params.centerX;
    const placedBoxes = params.placedBoxes;
    const safeLeft = params.safeLeft,
        safeRight = params.safeRight,
        safeTop = params.safeTop,
        safeBottom = params.safeBottom;
    const baseSize = params.baseSize || SIZE_MAIN_TEXT,
        minSize = params.minSize || 10;
    const bandHeight = params.bandHeight || TOP_BAND_HEIGHT,
        bandGap = params.bandGap || TOP_BAND_GAP,
        bandPadX = params.bandPadX || TOP_BAND_PAD_X;

    const tryTopY = figBox.top - bandGap - bandHeight;
    const tryBotY = figBox.bottom + bandGap;
    let bandTop,
        bandLeft = safeLeft,
        bandRight = safeRight;
    if (tryTopY >= safeTop) bandTop = Math.max(safeTop, tryTopY);
    else {
        bandTop = Math.min(tryBotY, safeBottom - bandHeight);
        if (bandTop < safeTop) bandTop = safeTop;
    }
    const bandBottom = Math.min(bandTop + bandHeight, safeBottom);
    const bandRect = {
        left: bandLeft,
        right: bandRight,
        top: bandTop,
        bottom: bandBottom,
    };
    placedBoxes.push(bandRect);

    const bandWidth = Math.max(0, bandRight - bandLeft - 2 * bandPadX);
    const estimateWidth = function (t, size) {
        return t.length * size * 0.55;
    };
    let size = baseSize;
    while (size >= minSize && estimateWidth(text, size) > bandWidth) size--;

    const cx = clampXInside(
        centerX,
        bandWidth,
        bandLeft + bandPadX,
        bandRight - bandPadX
    );
    const cy = bandTop + bandHeight / 2;

    if (size >= minSize) {
        agregarTexto(svg, cx, cy, text, BARS_TEXT_COLOR, size, "middle");
        return;
    }

    // Fallback: 2 líneas
    const parts = text.split("|");
    let line1, line2;
    if (parts.length >= 2) {
        line1 = parts[0].trim();
        line2 = parts.slice(1).join(" | ").trim();
    } else {
        const mid = Math.max(1, Math.floor(text.length / 2));
        line1 = text.slice(0, mid);
        line2 = text.slice(mid);
    }
    let size1 = baseSize,
        size2 = baseSize;
    while (size1 > minSize && estimateWidth(line1, size1) > bandWidth) size1--;
    while (size2 > minSize && estimateWidth(line2, size2) > bandWidth) size2--;
    size1 = Math.max(size1, minSize);
    size2 = Math.max(size2, minSize);

    agregarTexto(svg, cx, cy - 6, line1, BARS_TEXT_COLOR, size1, "middle");
    agregarTexto(
        svg,
        cx,
        cy - 6 + size2 + 4,
        line2,
        BARS_TEXT_COLOR,
        size2,
        "middle"
    );
}

function placeMainLabelSideBand(params) {
    const svg = params.svg,
        text = params.text,
        figBox = params.figBox,
        centerY = params.centerY;
    const placedBoxes = params.placedBoxes;
    const safeLeft = params.safeLeft,
        safeRight = params.safeRight,
        safeTop = params.safeTop,
        safeBottom = params.safeBottom;
    const side = params.side || "right";
    const baseSize = params.baseSize || SIZE_MAIN_TEXT,
        minSize = params.minSize || 10;
    const bandWidth = params.bandWidth,
        bandGap = params.bandGap || SIDE_BAND_GAP,
        bandPad = params.bandPad || SIDE_BAND_PAD;

    const bandLeft =
        side === "left"
            ? Math.max(safeLeft, figBox.left - bandGap - bandWidth)
            : Math.min(figBox.right + bandGap, safeRight - bandWidth);

    const bandTop = safeTop;
    const bandRight = bandLeft + bandWidth;
    const bandBottom = safeBottom;

    const bandRect = {
        left: bandLeft,
        right: bandRight,
        top: bandTop,
        bottom: bandBottom,
    };
    placedBoxes.push(bandRect);

    // Zona prohibida exterior: evita que cotas se vayan fuera de la banda
    const outerRect =
        side === "left"
            ? {
                  left: safeLeft,
                  right: bandLeft,
                  top: safeTop,
                  bottom: safeBottom,
              }
            : {
                  left: bandRight,
                  right: safeRight,
                  top: safeTop,
                  bottom: safeBottom,
              };
    placedBoxes.push(outerRect);

    const usableW = Math.max(0, bandWidth - 2 * bandPad);
    const estimateWidth = function (t, size) {
        return t.length * size * 0.55;
    };
    let size = baseSize;
    while (size >= minSize && estimateWidth(text, size) > usableW) size--;

    const cx = bandLeft + bandWidth / 2;
    const cy = Math.max(
        safeTop + SIZE_MAIN_TEXT,
        Math.min(centerY, safeBottom - SIZE_MAIN_TEXT)
    );

    if (size >= minSize) {
        agregarTexto(svg, cx, cy, text, BARS_TEXT_COLOR, size, "middle");
        return;
    }

    // Fallback 2 líneas vertical
    const parts = text.split("|");
    let line1, line2;
    if (parts.length >= 2) {
        line1 = parts[0].trim();
        line2 = parts.slice(1).join(" | ").trim();
    } else {
        const mid = Math.max(1, Math.floor(text.length / 2));
        line1 = text.slice(0, mid);
        line2 = text.slice(mid);
    }
    let size1 = baseSize,
        size2 = baseSize;
    while (size1 > minSize && estimateWidth(line1, size1) > usableW) size1--;
    while (size2 > minSize && estimateWidth(line2, size2) > usableW) size2--;
    size1 = Math.max(size1, minSize);
    size2 = Math.max(size2, minSize);

    agregarTexto(svg, cx, cy - 6, line1, BARS_TEXT_COLOR, size1, "middle");
    agregarTexto(
        svg,
        cx,
        cy - 6 + size2 + 4,
        line2,
        BARS_TEXT_COLOR,
        size2,
        "middle"
    );
}

// =======================
// Preproceso solapes (alarga tramo anterior)
// =======================
function ajustarLongitudesParaEvitarSolapes(dims, grow) {
    const G = typeof grow === "number" ? grow : OVERLAP_GROW_UNITS;
    const out = dims.map(function (d) {
        return Object.assign({}, d);
    });
    let cx = 0,
        cy = 0,
        ang = 0;
    const prev = [];
    let lastDir = null,
        lastIdxPrev = -1,
        lastIdxDims = -1;
    const EPS = 1e-7;
    function deg2rad(d) {
        return (d * Math.PI) / 180;
    }
    function isH(a) {
        return Math.abs(Math.sin(deg2rad(a))) < 1e-12;
    }
    function overlap(a1, b1, a2, b2) {
        return Math.min(b1, b2) - Math.max(a1, a2) > EPS;
    }

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
        function tryResolve() {
            const dir = {
                x: Math.cos(deg2rad(ang)),
                y: Math.sin(deg2rad(ang)),
            };
            const ex = cx + out[i].length * dir.x,
                ey = cy + out[i].length * dir.y;
            const horiz = isH(ang);
            for (let k = 0; k < prev.length; k++) {
                const s = prev[k];
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
                            out[lastIdxDims].length += G;
                            cx += lastDir.x * G;
                            cy += lastDir.y * G;
                            const ps = prev[lastIdxPrev];
                            ps.x2 += lastDir.x * G;
                            ps.y2 += lastDir.y * G;
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
                            out[lastIdxDims].length += G;
                            cx += lastDir.x * G;
                            cy += lastDir.y * G;
                            const ps = prev[lastIdxPrev];
                            ps.x2 += lastDir.x * G;
                            ps.y2 += lastDir.y * G;
                            return true;
                        }
                    }
                }
            }
            return false;
        }
        while (tryResolve()) {}

        const dir = { x: Math.cos(deg2rad(ang)), y: Math.sin(deg2rad(ang)) };
        const nx = cx + out[i].length * dir.x,
            ny = cy + out[i].length * dir.y;
        const horiz = isH(ang);
        prev.push({
            x1: cx,
            y1: cy,
            x2: nx,
            y2: ny,
            horiz: horiz,
            y: cy,
            x: cx,
        });
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
    function map(px, py) {
        const p = rotatePoint({ x: px, y: py }, cxModel, cyModel, rotDeg);
        return {
            x: centerX + (p.x - midX) * scale,
            y: centerY + (p.y - midY) * scale,
        };
    }
    function move() {
        if (!started) {
            const m = map(x, y);
            dStr += "M " + m.x + " " + m.y;
            started = true;
        }
    }
    for (let i = 0; i < dims.length; i++) {
        const d = dims[i];
        if (d.type === "turn") {
            ang += d.angle;
            continue;
        }
        if (d.type === "line") {
            const nx = x + d.length * Math.cos((ang * Math.PI) / 180);
            const ny = y + d.length * Math.sin((ang * Math.PI) / 180);
            move();
            const p = map(nx, ny);
            dStr += " L " + p.x + " " + p.y;
            x = nx;
            y = ny;
            continue;
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
                dStr +=
                    " A " +
                    R +
                    " " +
                    R +
                    " 0 1 " +
                    sweep +
                    " " +
                    pMid.x +
                    " " +
                    pMid.y;
                dStr +=
                    " A " +
                    R +
                    " " +
                    R +
                    " 0 1 " +
                    sweep +
                    " " +
                    pEnd.x +
                    " " +
                    pEnd.y;
                ang += d.arcAngle;
                continue;
            }
            const pEnd = map(ex, ey);
            const R = d.radius * scale,
                largeArc = absAng > 180 ? 1 : 0,
                sweep = d.arcAngle >= 0 ? 1 : 0;
            dStr +=
                " A " +
                R +
                " " +
                R +
                " 0 " +
                largeArc +
                " " +
                sweep +
                " " +
                pEnd.x +
                " " +
                pEnd.y;
            x = ex;
            y = ey;
            ang += d.arcAngle;
        }
    }
    return dStr || "M 0 0";
}

// =======================
// Script principal
// =======================
document.addEventListener("DOMContentLoaded", function () {
    // 👉 Conectar el panel informativo (global)
    if (window.setDataSources) {
        window.setDataSources({
            sugerencias: window.SUGERENCIAS || {},
            elementosAgrupados: window.elementosAgrupadosScript || [],
        });
    }
    var elementos = window.elementosAgrupadosScript;
    if (!elementos) return;

    elementos.forEach(function (grupo, gidx) {
        var groupId =
            grupo && grupo.etiqueta && grupo.etiqueta.id != null
                ? grupo.etiqueta.id
                : grupo && grupo.id != null
                ? grupo.id
                : gidx;

        var contenedor = document.getElementById("contenedor-svg-" + groupId);
        if (!contenedor) return;

        var ancho = 600,
            alto = 150;

        // ✅ lee el color UNA VEZ por contenedor y pásalo al crear el SVG
        const svgBg = getEstadoColorFromCSSVar(contenedor);
        var svg = crearSVG(ancho, alto, svgBg);

        var numElementos = grupo.elementos.length;
        var legendEntries = [];
        // Reset reservas por grupo
        window.__placedLetterBoxes = [];
        window.__figBoxesGroup = [];

        // Precalcular nº segmentos
        var segCounts = grupo.elementos.map(function (el) {
            var dimsRaw = extraerDimensiones(el.dimensiones || "");
            var dimsNoZero = combinarRectasConCeros(dimsRaw);
            var segsOrig = computeLineSegments(dimsNoZero);
            return segsOrig.length;
        });
        var allFewDims = segCounts.every(function (n) {
            return n <= 5;
        });

        // Distribución
        var columnas =
            numElementos > 1 && allFewDims
                ? 1
                : Math.ceil(Math.sqrt(numElementos));
        var filas =
            numElementos > 1 && allFewDims
                ? numElementos
                : Math.ceil(numElementos / columnas);

        var cellWidth = (ancho - marginX) / columnas;
        var cellHeight = (alto - marginY) / filas;

        grupo.elementos.forEach(function (elemento, idx) {
            var fila = Math.floor(idx / columnas);
            var col = idx % columnas;

            var centerX = marginX + col * cellWidth + cellWidth / 2;
            var centerY = marginY + fila * cellHeight + cellHeight / 2;

            var safeLeft = 0,
                safeRight = ancho,
                safeTop = 0,
                safeBottom = alto;

            // dims normalizadas + anti-solape
            var dimsRaw = extraerDimensiones(elemento.dimensiones || "");
            var dimsNoZero = combinarRectasConCeros(dimsRaw);
            var dims = ajustarLongitudesParaEvitarSolapes(
                dimsNoZero,
                OVERLAP_GROW_UNITS
            );

            var barras = elemento.barras != null ? elemento.barras : 0;
            // Formatear diámetro: quitar unidad y redondear (sin decimales)
            var diametro = "N/A";
            if (elemento.diametro != null && elemento.diametro !== "") {
                var dstr = String(elemento.diametro).replace(",", ".");
                var m = dstr.match(/-?\d+(?:\.\d+)?/);
                if (m) {
                    var dn = parseFloat(m[0]);
                    if (isFinite(dn)) diametro = String(Math.round(dn));
                }
            }
            var peso = elemento.peso != null ? elemento.peso : "N/A";
            var mainText = "Ø" + diametro + " | " + peso + " | x" + barras;

            // Letra de la figura y recoger en leyenda
            var letter = indexToLetters(idx);
            legendEntries.push({ letter: letter, text: mainText });

            // Figura (bbox modelo)
            var ptsModel = computePathPoints(dims);
            var minX = Math.min.apply(
                null,
                ptsModel.map((p) => p.x)
            );
            var maxX = Math.max.apply(
                null,
                ptsModel.map((p) => p.x)
            );
            var minY = Math.min.apply(
                null,
                ptsModel.map((p) => p.y)
            );
            var maxY = Math.max.apply(
                null,
                ptsModel.map((p) => p.y)
            );
            var cxModel = (minX + maxX) / 2,
                cyModel = (minY + maxY) / 2;

            // Rotación por forma
            var needsRotate = maxY - minY > maxX - minX;
            var rotDeg = needsRotate ? -90 : 0;

            var ptsRot = ptsModel.map((p) =>
                rotatePoint(p, cxModel, cyModel, rotDeg)
            );
            minX = Math.min.apply(
                null,
                ptsRot.map((p) => p.x)
            );
            maxX = Math.max.apply(
                null,
                ptsRot.map((p) => p.x)
            );
            minY = Math.min.apply(
                null,
                ptsRot.map((p) => p.y)
            );
            maxY = Math.max.apply(
                null,
                ptsRot.map((p) => p.y)
            );
            var figW = Math.max(1, maxX - minX),
                figH = Math.max(1, maxY - minY);
            var midX = (minX + maxX) / 2,
                midY = (minY + maxY) / 2;

            // Colocación del texto (según reglas)
            var segCount = segCounts[idx];
            var textPlacement =
                numElementos === 1 ? (segCount > 5 ? "side" : "top") : "top";

            // Banda lateral estimada
            var estSideW =
                mainText.length * SIZE_MAIN_TEXT * 0.55 + 2 * SIDE_BAND_PAD;
            var sideBandWidth = Math.max(80, Math.min(estSideW, 160));

            // Escala
            var scale;
            if (textPlacement === "top") {
                var usableHeight = Math.max(
                    10,
                    cellHeight * 0.95 -
                        TOP_BAND_HEIGHT -
                        TOP_BAND_GAP -
                        DIM_RING_MARGIN
                );
                scale = Math.min((cellWidth * 0.8) / figW, usableHeight / figH);
            } else {
                var usableWidth = Math.max(
                    10,
                    cellWidth * 0.95 -
                        sideBandWidth -
                        SIDE_BAND_GAP -
                        DIM_RING_MARGIN
                );
                scale = Math.min(usableWidth / figW, (cellHeight * 0.8) / figH);
            }

            // Bbox figura
            var ptsSvg = ptsRot.map((pt) => ({
                x: centerX + (pt.x - midX) * scale,
                y: centerY + (pt.y - midY) * scale,
            }));
            var figMinX = Math.min.apply(
                null,
                ptsSvg.map((p) => p.x)
            );
            var figMaxX = Math.max.apply(
                null,
                ptsSvg.map((p) => p.x)
            );
            var figMinY = Math.min.apply(
                null,
                ptsSvg.map((p) => p.y)
            );
            var figMaxY = Math.max.apply(
                null,
                ptsSvg.map((p) => p.y)
            );
            var figBox = {
                left: figMinX,
                right: figMaxX,
                top: figMinY,
                bottom: figMaxY,
            };
            // Guarda caja de figura para evitar invadir con letras
            window.__figBoxesGroup.push({
                left: figMinX,
                right: figMaxX,
                top: figMinY,
                bottom: figMaxY,
            });

            // Path principal
            var dPath = buildSvgPathFromDims(
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
            var pathEl = agregarPathD(svg, dPath, FIGURE_LINE_COLOR, 2);
            // Crear un path invisible más gordo como zona clicable
            var hitbox = pathEl.cloneNode(false);
            hitbox.setAttribute("stroke-width", "50"); // mucho más ancho
            hitbox.setAttribute("stroke", "transparent");
            hitbox.setAttribute("fill", "none");
            hitbox.style.cursor = "pointer";

            // Reenvía los eventos al path original
            [
                "click",
                "contextmenu",
                "mouseenter",
                "mouseleave",
                "keydown",
            ].forEach((evt) => {
                hitbox.addEventListener(evt, (e) =>
                    pathEl.dispatchEvent(new e.constructor(e.type, e))
                );
            });

            // Insertar hitbox ANTES del path visible, así no tapa la figura
            svg.insertBefore(hitbox, pathEl);

            // Interacción
            var etiquetaClick =
                (elemento.codigo != null ? elemento.codigo : elemento.id) + "";
            pathEl.style.cursor = "pointer";
            // 🆕 tooltip accesible para el operario
            pathEl.setAttribute(
                "title",
                "Click: dividir · Ctrl/Shift/⌘+Click o botón derecho: info"
            );

            // 🆕 Click normal -> dividir. Con modificadores -> info
            pathEl.addEventListener("click", function (e) {
                if (e.ctrlKey || e.metaKey || e.shiftKey) {
                    e.preventDefault();
                    if (window.mostrarPanelInfoElemento)
                        window.mostrarPanelInfoElemento(elemento.id);
                    return;
                }
                abrirModalDividirElemento(elemento.id, etiquetaClick);
            });

            // 🆕 Botón derecho -> info
            pathEl.addEventListener("contextmenu", function (e) {
                e.preventDefault();
                if (window.mostrarPanelInfoElemento)
                    window.mostrarPanelInfoElemento(elemento.id);
            });

            // 🆕 Teclado: Enter/Espacio = dividir. Ctrl+Enter = info
            pathEl.addEventListener("keydown", function (e) {
                if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    abrirModalDividirElemento(elemento.id, etiquetaClick);
                }
                if (
                    (e.ctrlKey || e.metaKey) &&
                    e.key.toLowerCase() === "enter"
                ) {
                    e.preventDefault();
                    if (window.mostrarPanelInfoElemento)
                        window.mostrarPanelInfoElemento(elemento.id);
                }
            });

            // Reservas + texto (solo letra por figura, leyenda abajo-izquierda)
            var placedBoxes = [];
            (function placeLetter() {
                var letterSize = 14;
                var tb = approxTextBox(letter, letterSize);

                // Colocar SIEMPRE a la derecha de la figura, evitando solapes
                function makeBoxAt(lx, ly) {
                    return {
                        left: lx,
                        right: lx + tb.w,
                        top: ly - tb.h / 2,
                        bottom: ly + tb.h / 2,
                    };
                }

                var chosen = null;
                var baseX = clampXInside(
                    figBox.right + 10,
                    tb.w,
                    safeLeft,
                    safeRight
                );
                var centerYFig = (figBox.top + figBox.bottom) / 2;
                var baseY = Math.max(
                    safeTop + tb.h / 2,
                    Math.min(centerYFig, safeBottom - tb.h / 2)
                );

                function tryColumn(xPos) {
                    var maxSpread = Math.max(40, (safeBottom - safeTop) * 0.5);
                    for (var off = 0; off <= maxSpread; off += LABEL_STEP) {
                        var dir = off % 2 === 0 ? 1 : -1;
                        var mult = Math.ceil(off / 2);
                        var dy = dir * mult * LABEL_STEP;
                        var ly = Math.max(
                            safeTop + tb.h / 2,
                            Math.min(safeBottom - tb.h / 2, baseY + dy)
                        );
                        var lx = xPos;
                        var box = makeBoxAt(lx, ly);

                        var collideFig = window.__figBoxesGroup.some(function (
                            b
                        ) {
                            return rectsOverlap(b, box, LABEL_CLEARANCE);
                        });
                        if (collideFig) continue;

                        var collidePrev = window.__placedLetterBoxes.some(
                            function (b) {
                                return rectsOverlap(b, box, LABEL_CLEARANCE);
                            }
                        );
                        if (collidePrev) continue;

                        var collideLocal = placedBoxes.some(function (b) {
                            return rectsOverlap(b, box, LABEL_CLEARANCE);
                        });
                        if (collideLocal) continue;

                        var out =
                            box.top < 0 ||
                            box.bottom > alto ||
                            box.left < 0 ||
                            box.right > ancho;
                        if (out) continue;

                        chosen = { x: lx, y: ly, box: box };
                        return true;
                    }
                    return false;
                }

                // Probar columna base y algunas columnas más a la derecha
                var columnsTried = 0;
                var xStep = 8;
                while (!chosen && columnsTried < 6) {
                    var xCol = clampXInside(
                        baseX + columnsTried * xStep,
                        tb.w,
                        safeLeft,
                        safeRight
                    );
                    if (tryColumn(xCol)) break;
                    columnsTried++;
                }

                // Fallback extremo: última columna disponible
                if (!chosen) {
                    var lxFallback = clampXInside(
                        safeRight - tb.w,
                        tb.w,
                        safeLeft,
                        safeRight
                    );
                    var lyFallback = baseY;
                    chosen = {
                        x: lxFallback,
                        y: lyFallback,
                        box: makeBoxAt(lxFallback, lyFallback),
                    };
                }

                window.__placedLetterBoxes.push(chosen.box);
                // También registrar en reservas locales para que otras etiquetas/cotas lo respeten
                placedBoxes.push(chosen.box);

                // Dibujar letra
                var t = document.createElementNS(
                    "http://www.w3.org/2000/svg",
                    "text"
                );
                t.setAttribute("x", chosen.x);
                t.setAttribute("y", chosen.y);
                t.setAttribute("fill", BARS_TEXT_COLOR);
                t.setAttribute("font-size", letterSize);
                t.setAttribute("text-anchor", "start");
                t.setAttribute("alignment-baseline", "middle");
                t.style.fontWeight = "600";
                t.style.pointerEvents = "none";
                t.textContent = letter;
                svg.appendChild(t);
            })();

            // Cotas
            var segsAdj = computeLineSegments(dims);
            var segsOrig = computeLineSegments(dimsNoZero);
            var segsUnicos = agruparPorDireccionYEtiquetaRobusto(
                segsAdj,
                segsOrig,
                {
                    dirPrecision: 1e-2,
                    labelFormat: { decimals: 0, step: null },
                }
            );

            var MAX_OFF = Math.max(ancho, alto) * 0.6;

            segsUnicos.forEach(function (s) {
                var s1 = rotatePoint(s.start, cxModel, cyModel, rotDeg);
                var s2 = rotatePoint(s.end, cxModel, cyModel, rotDeg);
                var p1 = {
                    x: centerX + (s1.x - midX) * scale,
                    y: centerY + (s1.y - midY) * scale,
                };
                var p2 = {
                    x: centerX + (s2.x - midX) * scale,
                    y: centerY + (s2.y - midY) * scale,
                };

                var dx = p2.x - p1.x,
                    dy = p2.y - p1.y;
                var L = Math.hypot(dx, dy) || 1;
                var tx = dx / L,
                    ty = dy / L;
                var nx = dy / L,
                    ny = -dx / L;
                var mx = (p1.x + p2.x) / 2,
                    my = (p1.y + p2.y) / 2;

                var baseLX = mx + nx * DIM_OFFSET;
                var baseLY = my + ny * DIM_OFFSET;

                var label = s._label;
                var tb = approxTextBox(label, SIZE_DIM_TEXT);
                var tw = tb.w,
                    th = tb.h;

                function makeBox(cx, cy) {
                    return {
                        left: cx - tw / 2,
                        right: cx + tw / 2,
                        top: cy - th / 2,
                        bottom: cy + th / 2,
                    };
                }

                var maxShift = L * DIM_TANG_MAX_FRAC;
                var bestLX = baseLX,
                    bestLY = baseLY;

                for (
                    var step = 0;
                    step <= Math.ceil(maxShift / DIM_TANG_STEP);
                    step++
                ) {
                    var dir = step % 2 === 0 ? 1 : -1;
                    var mult = Math.ceil(step / 2);
                    var shift = dir * mult * DIM_TANG_STEP;
                    if (Math.abs(shift) > maxShift) continue;

                    var lx = baseLX + tx * shift;
                    var ly = baseLY + ty * shift;

                    var tProj = (lx - p1.x) * tx + (ly - p1.y) * ty;
                    if (tProj < 0 + tw * 0.3 || tProj > L - tw * 0.3) continue;

                    var labelBox = makeBox(lx, ly);

                    var collideFig = rectsOverlap(
                        {
                            left: Math.min(p1.x, p2.x),
                            right: Math.max(p1.x, p2.x),
                            top: Math.min(p1.y, p2.y),
                            bottom: Math.max(p1.y, p2.y),
                        },
                        labelBox,
                        0
                    );
                    var collideOth = placedBoxes.some((b) =>
                        rectsOverlap(b, labelBox, LABEL_CLEARANCE)
                    );
                    var outOfBounds =
                        labelBox.top < 0 ||
                        labelBox.bottom > alto ||
                        labelBox.left < 0 ||
                        labelBox.right > ancho;

                    if (!collideFig && !collideOth && !outOfBounds) {
                        bestLX = lx;
                        bestLY = ly;
                        placedBoxes.push(labelBox);
                        break;
                    }
                }

                var hl = document.createElementNS(
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

                var txt = document.createElementNS(
                    "http://www.w3.org/2000/svg",
                    "text"
                );
                txt.setAttribute("x", bestLX);
                txt.setAttribute("y", bestLY);
                txt.setAttribute("fill", VALOR_COTA_COLOR);
                txt.setAttribute("font-size", SIZE_DIM_TEXT);
                txt.setAttribute("text-anchor", "middle");
                txt.setAttribute("alignment-baseline", "middle");
                txt.setAttribute("tabindex", "0");
                txt.style.cursor = "pointer";
                txt.textContent = label;
                svg.appendChild(txt);

                function onEnter() {
                    hl.style.opacity = 1;
                }
                function onLeave() {
                    hl.style.opacity = 0;
                }
                txt.addEventListener("mouseenter", onEnter);
                txt.addEventListener("mouseleave", onLeave);
                txt.addEventListener("focus", onEnter);
                txt.addEventListener("blur", onLeave);
            });
        });

        contenedor.innerHTML = "";
        // Dibuja la leyenda en la esquina inferior izquierda del SVG
        drawLegendBottomLeft(svg, legendEntries, ancho, alto);
        contenedor.appendChild(svg);
    });
});

// =======================
// Modal dividir elemento
// =======================
function abrirModalDividirElemento(elementoId) {
    var modal = document.getElementById("modalDividirElemento");
    var input = document.getElementById("dividir_elemento_id");
    var form = document.getElementById("formDividirElemento");
    if (!modal || !input || !form) return;
    input.value = elementoId;
    if (window.rutaDividirElemento)
        form.setAttribute("action", window.rutaDividirElemento);
    modal.classList.remove("hidden");
}
async function enviarDivision() {
    var form = document.getElementById("formDividirElemento");
    var url = form.getAttribute("action") || window.rutaDividirElemento;
    var fd = new FormData(form);
    try {
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var token =
            fd.get("_token") ||
            (tokenMeta ? tokenMeta.getAttribute("content") : null);
        var headers = token ? { "X-CSRF-TOKEN": token } : {};
        var res = await fetch(url, {
            method: "POST",
            headers: headers,
            body: fd,
        });
        var data = await res.json();
        if (!res.ok || !data.success)
            throw new Error(data.message || "Error al dividir");
        form.reset();
        var modalEl = document.getElementById("modalDividirElemento");
        if (modalEl) modalEl.classList.add("hidden");
        if (window.Swal) window.Swal.fire("Hecho", data.message, "success");
        else alert(data.message);
    } catch (e) {
        if (window.Swal)
            window.Swal.fire("Error", (e && e.message) || "Error", "error");
        else alert((e && e.message) || "Error");
    }
}
