// =======================
// Colores y configuración
// =======================
const FIGURE_LINE_COLOR = "rgba(0, 0, 0, 0.8)";
const VALOR_COTA_COLOR = "rgba(0, 0, 0, 1)";
const BARS_TEXT_COLOR = "rgba(0, 0, 0, 1)";

const marginX = 1;
const marginY = 1;

// “recrecimiento” (en UNIDADES del modelo, no px)
const OVERLAP_GROW_UNITS = 0.6;

// tamaños de texto y separación de cotas
const SIZE_MAIN_TEXT = 18;
const SIZE_DIM_TEXT = 14;
const DIM_LINE_OFFSET = 16;
const DIM_LABEL_LIFT = 10; // px extra para separar etiqueta de la línea
const DIM_OFFSET = 10; // px perpendicular a la línea
const DIM_TANG_STEP = 6; // px por intento a lo largo de la línea
const DIM_TANG_MAX_FRAC = 0.45; // % longitud del tramo desde el centro

// separación mínima del texto respecto a la figura y paso de alejamiento
const LABEL_CLEARANCE = 6; // px
const LABEL_STEP = 4; // px

// Reserva para layout / leyenda
const TOP_BAND_HEIGHT = 26;
const TOP_BAND_GAP = 14;
const TOP_BAND_PAD_X = 6;

const SIDE_BAND_GAP = 12;
const SIDE_BAND_PAD = 6;

// Reserva mínima para “anillo de cotas”
const DIM_RING_MARGIN = DIM_LINE_OFFSET + SIZE_DIM_TEXT + DIM_LABEL_LIFT + 6;

// === NUEVO: auto-escala para piezas pequeñas ===
const SMALL_DIM_THRESHOLD = 50; // umbral "pieza pequeña"
const SMALL_DIM_SCALE = 2; // factor de escala para mejorar visibilidad
function scaleDims(dims, factor) {
    if (!factor || factor === 1) return dims.map((d) => ({ ...d }));
    return dims.map((d) => {
        if (d.type === "line")
            return { ...d, length: (d.length || 0) * factor };
        if (d.type === "arc") return { ...d, radius: (d.radius || 0) * factor };
        return { ...d }; // giros sin cambios
    });
}

// =======================
// Helpers SVG / Ángulos
// =======================
const ANGLE_TOL_DEG = 0.75;
const ANGLE_LABEL_OFFSET = 14;
const ANGLE_LABEL_MAX_OFFSET = 60;
const ANGLE_LABEL_SWEEP_DEG = [0, 10, -10, 20, -20, 30, -30];

function normalizeDeg180(a) {
    return ((a % 180) + 180) % 180;
}
function computeSegmentAngleDeg(p1, p2) {
    const dx = p2.x - p1.x,
        dy = p2.y - p1.y;
    const deg = (Math.atan2(dy, dx) * 180) / Math.PI;
    return normalizeDeg180(deg);
}
function rad(deg) {
    return (deg * Math.PI) / 180;
}
function deg(rad) {
    return (rad * 180) / Math.PI;
}

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
    let s = "",
        i = Number(n) || 0;
    while (i >= 0) {
        const r = i % 26;
        s = String.fromCharCode(65 + r) + s;
        i = Math.floor(i / 26) - 1;
    }
    return s;
}

/** Dibuja la leyenda compacta y registra sus cajas para evitar solapes */
function drawLegendBottomLeft(svg, entries, width, height) {
    if (!entries || !entries.length) return;
    const pad = 8,
        gap = 4,
        size = 12;
    const lines = entries.map(
        (e) => (e.letter ? e.letter + " " : "") + (e.text || "")
    );
    const boxH = pad * 2 + lines.length * size + (lines.length - 1) * gap;
    const x = marginX,
        y = Math.max(marginY, height - boxH - marginY);
    window.__legendBoxesGroup = window.__legendBoxesGroup || [];
    let cy = y + pad + size / 2;
    for (let i = 0; i < lines.length; i++) {
        const text = lines[i];
        const w = approxTextBox(text, size).w;
        const left = x + pad,
            right = left + w;
        const t = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "text"
        );
        t.setAttribute("x", left);
        t.setAttribute("y", cy);
        t.setAttribute("fill", BARS_TEXT_COLOR);
        t.setAttribute("font-size", size);
        t.setAttribute("text-anchor", "start");
        t.setAttribute("alignment-baseline", "middle");
        t.style.pointerEvents = "none";
        t.textContent = text;
        svg.appendChild(t);
        window.__legendBoxesGroup.push({
            left,
            right,
            top: cy - size / 2,
            bottom: cy + size / 2,
        });
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
            if (i + 1 < tokens.length && tokens[i + 1].endsWith("d"))
                arcAngle = parseFloat(tokens[++i].slice(0, -1));
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
            x += d.length * Math.cos(rad(a));
            y += d.length * Math.sin(rad(a));
            pts.push({ x, y });
        } else if (d.type === "turn") {
            a += d.angle;
        } else if (d.type === "arc") {
            const cx = x + d.radius * Math.cos(rad(a + 90));
            const cy = y + d.radius * Math.sin(rad(a + 90));
            const start = Math.atan2(y - cy, x - cx),
                end = start + rad(d.arcAngle);
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
                x: x + d.length * Math.cos(rad(a)),
                y: y + d.length * Math.sin(rad(a)),
            };
            segs.push({ start, end, length: d.length });
            x = end.x;
            y = end.y;
        } else if (d.type === "turn") {
            a += d.angle;
        } else if (d.type === "arc") {
            const cx = x + d.radius * Math.cos(rad(a + 90));
            const cy = y + d.radius * Math.sin(rad(a + 90));
            const start = Math.atan2(y - cy, x - cx),
                end = start + rad(d.arcAngle);
            x = cx + d.radius * Math.cos(end);
            y = cy + d.radius * Math.sin(end);
            a += d.arcAngle;
        }
    }
    return segs;
}

// =======================
// Helpers (evitar solapes)
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
// Formateo + agrupado
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
    const buckets = new Map();
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
// Preproceso solapes (recrecer tramo anterior)
// =======================
function ajustarLongitudesParaEvitarSolapes(dims, grow) {
    const G = typeof grow === "number" ? grow : OVERLAP_GROW_UNITS;
    const out = dims.map((d) => Object.assign({}, d));
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
            const start = Math.atan2(cy - cy0, cx - cx0),
                end = start + deg2rad(d.arcAngle);
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
                            prev[lastIdxPrev].x2 += lastDir.x * G;
                            prev[lastIdxPrev].y2 += lastDir.y * G;
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
                            prev[lastIdxPrev].x2 += lastDir.x * G;
                            prev[lastIdxPrev].y2 += lastDir.y * G;
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
function rotatePoint(p, cx, cy, degAng) {
    const r = rad(degAng),
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
            const nx = x + d.length * Math.cos(rad(ang)),
                ny = y + d.length * Math.sin(rad(ang));
            move();
            const p = map(nx, ny);
            dStr += " L " + p.x + " " + p.y;
            x = nx;
            y = ny;
            continue;
        }
        if (d.type === "arc") {
            const cx = x + d.radius * Math.cos(rad(ang + 90)),
                cy = y + d.radius * Math.sin(rad(ang + 90));
            const start = Math.atan2(y - cy, x - cx),
                end = start + rad(d.arcAngle);
            const ex = cx + d.radius * Math.cos(end),
                ey = cy + d.radius * Math.sin(end);
            const absAng = Math.abs(d.arcAngle) % 360;
            move();
            if (absAng < 1e-6 || Math.abs(d.arcAngle) >= 359.9) {
                const midAng = start + Math.sign(d.arcAngle) * Math.PI;
                const mx = cx + d.radius * Math.cos(midAng),
                    my = cy + d.radius * Math.sin(midAng);
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
// Layout "masonry"
// =======================
function medirFiguraEnModelo(dims) {
    const pts = computePathPoints(dims);
    let minX = Math.min(...pts.map((p) => p.x)),
        maxX = Math.max(...pts.map((p) => p.x));
    let minY = Math.min(...pts.map((p) => p.y)),
        maxY = Math.max(...pts.map((p) => p.y));
    const cx = (minX + maxX) / 2,
        cy = (minY + maxY) / 2;
    const needsRotate = maxY - minY > maxX - minX;
    const rotDeg = needsRotate ? -90 : 0;
    const rot = pts.map((p) => rotatePoint(p, cx, cy, rotDeg));
    minX = Math.min(...rot.map((p) => p.x));
    maxX = Math.max(...rot.map((p) => p.x));
    minY = Math.min(...rot.map((p) => p.y));
    maxY = Math.max(...rot.map((p) => p.y));
    const w = Math.max(1, maxX - minX),
        h = Math.max(1, maxY - minY);
    const midX = (minX + maxX) / 2,
        midY = (minY + maxY) / 2;
    return { rotDeg, w, h, cxModel: cx, cyModel: cy, midX, midY, ptsRot: rot };
}
function getTurnVertices(dims) {
    const out = [];
    let x = 0,
        y = 0,
        a = 0;
    function dirFromAng(deg) {
        const r = rad(deg);
        return { x: Math.cos(r), y: Math.sin(r) };
    }
    for (let i = 0; i < dims.length; i++) {
        const d = dims[i];
        if (d.type === "line") {
            x += d.length * Math.cos(rad(a));
            y += d.length * Math.sin(rad(a));
        } else if (d.type === "turn") {
            const prevDir = dirFromAng(a);
            const turnDeg = d.angle;
            a += turnDeg;
            const nextDir = dirFromAng(a);
            out.push({ x, y, angleDeg: turnDeg, prevDir, nextDir });
        } else if (d.type === "arc") {
            const cx0 = x + d.radius * Math.cos(rad(a + 90)),
                cy0 = y + d.radius * Math.sin(rad(a + 90));
            const start = Math.atan2(y - cy0, x - cx0),
                end = start + rad(d.arcAngle);
            x = cx0 + d.radius * Math.cos(end);
            y = cy0 + d.radius * Math.sin(end);
            a += d.arcAngle;
        }
    }
    return out;
}
function assignColumnsFFD(items, k, gapRow) {
    const cols = Array.from({ length: k }, () => ({
        sumH: 0,
        maxW: 0,
        items: [],
    }));
    const order = [...items.keys()].sort((a, b) => items[b].h - items[a].h);
    for (const idx of order) {
        let best = 0,
            bestVal = Infinity;
        for (let c = 0; c < k; c++) {
            const col = cols[c];
            const val =
                col.sumH + (col.items.length > 0 ? gapRow : 0) + items[idx].h;
            if (val < bestVal) {
                bestVal = val;
                best = c;
            }
        }
        const col = cols[best];
        col.sumH =
            col.items.length > 0
                ? col.sumH + gapRow + items[idx].h
                : col.sumH + items[idx].h;
        col.maxW = Math.max(col.maxW, items[idx].w);
        col.items.push(idx);
    }
    return cols;
}
function planMasonryOptimal(medidas, svgW, svgH, opts = {}) {
    const padding = opts.padding ?? 10,
        gapCol = opts.gapCol ?? 10,
        gapRow = opts.gapRow ?? 8,
        kMax = Math.max(1, Math.min(medidas.length, opts.kMax ?? 4));
    const anchoUsable = Math.max(10, svgW - 2 * padding);
    const altoUsable = Math.max(10, svgH - 2 * padding - DIM_RING_MARGIN);
    let best = { S: 0, k: 1, cols: null };
    for (let k = 1; k <= kMax; k++) {
        const cols = assignColumnsFFD(medidas, k, gapRow);
        const sumWCols =
            cols.reduce((a, c) => a + c.maxW, 0) + gapCol * (k - 1);
        const maxHCols = Math.max(...cols.map((c) => c.sumH));
        if (sumWCols <= 0 || maxHCols <= 0) continue;
        const S = Math.max(
            0.01,
            Math.min(anchoUsable / sumWCols, altoUsable / maxHCols)
        );
        if (S > best.S) best = { S, k, cols };
    }
    const widthsEsc = best.cols.map((c) => c.maxW * best.S);
    const totalW =
        widthsEsc.reduce((a, w) => a + w, 0) +
        (best.k - 1) * (opts.gapCol ?? 10);
    let xStart = (svgW - totalW) / 2;
    const centersX = [];
    for (let c = 0; c < best.k; c++) {
        const w = widthsEsc[c];
        centersX[c] = xStart + w / 2;
        xStart += w + (opts.gapCol ?? 10);
    }
    const centersYByCol = [];
    for (let c = 0; c < best.k; c++) {
        const col = best.cols[c];
        const hEscTotal =
            col.items.reduce((a, idx) => a + medidas[idx].h * best.S, 0) +
            (col.items.length - 1) * (opts.gapRow ?? 8);
        let y = (svgH - hEscTotal) / 2;
        centersYByCol[c] = [];
        for (let i = 0; i < col.items.length; i++) {
            const idx = col.items[i];
            const hEsc = medidas[idx].h * best.S;
            centersYByCol[c].push(y + hEsc / 2);
            y += hEsc + (opts.gapRow ?? 8);
        }
    }
    return {
        S: best.S,
        k: best.k,
        cols: best.cols,
        centersX,
        centersYByCol,
        padding,
        gapRow,
        gapCol,
    };
}

// =======================
// Script principal
// =======================
document.addEventListener("DOMContentLoaded", function () {
    if (window.setDataSources) {
        window.setDataSources({
            sugerencias: window.SUGERENCIAS || {},
            elementosAgrupados: window.elementosAgrupadosScript || [],
        });
    }
    const grupos = window.elementosAgrupadosScript;
    if (!grupos) return;

    grupos.forEach(function (grupo, gidx) {
        const groupId =
            grupo && grupo.etiqueta && grupo.etiqueta.id != null
                ? grupo.etiqueta.id
                : grupo && grupo.id != null
                ? grupo.id
                : gidx;
        const contenedor = document.getElementById("contenedor-svg-" + groupId);
        if (!contenedor) return;

        const ancho = 600,
            alto = 150;
        const svgBg = getEstadoColorFromCSSVar(contenedor);
        const svg = crearSVG(ancho, alto, svgBg);

        // Reset reservas por grupo
        window.__placedLetterBoxes = [];
        window.__figBoxesGroup = [];
        window.__dimBoxesGroup = [];
        window.__angleBoxesGroup = [];
        window.__legendBoxesGroup = [];

        // ===== leyenda: preparar entradas primero, dibujarla YA para reservar espacio =====
        const legendEntries = (grupo.elementos || []).map((elemento, idx) => {
            const barras = elemento.barras != null ? elemento.barras : 0;
            let diametro = "N/A";
            if (elemento.diametro != null && elemento.diametro !== "") {
                const dstr = String(elemento.diametro).replace(",", ".");
                const mtch = dstr.match(/-?\d+(?:\.\d+)?/);
                if (mtch) {
                    const dn = parseFloat(mtch[0]);
                    if (isFinite(dn)) diametro = String(Math.round(dn));
                }
            }
            return {
                letter: indexToLetters(idx),
                text: `Ø${diametro} x${barras}`,
            };
        });
        drawLegendBottomLeft(svg, legendEntries, ancho, alto); // ← primero, para evitar solapes con todo lo demás

        // ====== medir piezas y decidir escala por elemento ======
        const preproc = grupo.elementos.map((el) => {
            const dimsRaw = extraerDimensiones(el.dimensiones || "");
            const dimsNoZero = combinarRectasConCeros(dimsRaw);
            let maxLinear = 0;
            for (const d of dimsNoZero) {
                if (d.type === "line")
                    maxLinear = Math.max(maxLinear, Math.abs(d.length || 0));
                if (d.type === "arc")
                    maxLinear = Math.max(maxLinear, Math.abs(d.radius || 0));
            }
            const isSmall = maxLinear <= SMALL_DIM_THRESHOLD;
            const geomScale = isSmall ? SMALL_DIM_SCALE : 1;
            const dimsScaled = scaleDims(dimsNoZero, geomScale);
            const medida = medirFiguraEnModelo(dimsScaled);
            return { dimsRaw, dimsNoZero, dimsScaled, geomScale, medida };
        });

        const medidas = preproc.map((p) => p.medida);
        const plan = planMasonryOptimal(medidas, ancho, alto, {
            padding: 15,
            gapCol: 10,
            gapRow: 20,
            kMax: 4,
        });

        const indexInCol = new Map();
        for (let c = 0; c < plan.k; c++) {
            plan.cols[c].items.forEach((idx, j) =>
                indexInCol.set(idx, { c, j })
            );
        }

        // ====== bucle de pintado ======
        grupo.elementos.forEach(function (elemento, idx) {
            const { dimsNoZero, dimsScaled, medida: m } = preproc[idx];

            const loc = indexInCol.get(idx);
            const cx = plan.centersX[loc.c],
                cy = plan.centersYByCol[loc.c][loc.j],
                scale = plan.S;

            // BBox figura
            const ptsSvg = m.ptsRot.map((pt) => ({
                x: cx + (pt.x - m.midX) * scale,
                y: cy + (pt.y - m.midY) * scale,
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
            window.__figBoxesGroup.push({ ...figBox });

            // Path visible (con recrecimiento) usando dimsScaled
            const dimsAdjForDraw = ajustarLongitudesParaEvitarSolapes(
                dimsScaled,
                OVERLAP_GROW_UNITS
            );
            const dPath = buildSvgPathFromDims(
                dimsAdjForDraw,
                m.cxModel,
                m.cyModel,
                m.rotDeg,
                scale,
                m.midX,
                m.midY,
                cx,
                cy
            );
            const pathEl = agregarPathD(svg, dPath, FIGURE_LINE_COLOR, 2);

            // ======== ÁNGULOS (usa dimsScaled para posiciones) ========
            (function drawTurnAngles() {
                const turns = getTurnVertices(dimsScaled); // ← corregido (coincide con lo dibujado)
                function shouldShow(deg) {
                    return Math.abs(Math.abs(deg) - 90) >= ANGLE_TOL_DEG;
                }
                const rotVec = (v, degAng) =>
                    rotatePoint({ x: v.x, y: v.y }, 0, 0, degAng);
                const mapToSvg = (px, py) => {
                    const pr = rotatePoint(
                        { x: px, y: py },
                        m.cxModel,
                        m.cyModel,
                        m.rotDeg
                    );
                    return {
                        x: cx + (pr.x - m.midX) * scale,
                        y: cy + (pr.y - m.midY) * scale,
                    };
                };
                const clampR = (R) => Math.max(10, Math.min(28, R));

                turns.forEach((t) => {
                    if (!shouldShow(t.angleDeg)) return;
                    const P = mapToSvg(t.x, t.y);
                    const vPrev = rotVec(t.prevDir, m.rotDeg),
                        vNext = rotVec(t.nextDir, m.rotDeg);
                    const aStart = Math.atan2(vPrev.y, vPrev.x),
                        aEnd = aStart + rad(t.angleDeg);
                    const figSpan = Math.min(
                        figBox.right - figBox.left,
                        figBox.bottom - figBox.top
                    );
                    let R = clampR(0.12 * figSpan);
                    const x1 = P.x + R * Math.cos(aStart),
                        y1 = P.y + R * Math.sin(aStart);
                    const x2 = P.x + R * Math.cos(aEnd),
                        y2 = P.y + R * Math.sin(aEnd);
                    const absAng = Math.abs(t.angleDeg),
                        largeArc = absAng > 180 ? 1 : 0,
                        sweep = t.angleDeg >= 0 ? 1 : 0;

                    const arc = document.createElementNS(
                        "http://www.w3.org/2000/svg",
                        "path"
                    );
                    arc.setAttribute(
                        "d",
                        `M ${x1} ${y1} A ${R} ${R} 0 ${largeArc} ${sweep} ${x2} ${y2}`
                    );
                    arc.setAttribute("stroke", "rgba(255,99,71,0.7)");
                    arc.setAttribute("stroke-width", "2");
                    arc.setAttribute("fill", "none");
                    arc.style.pointerEvents = "none";
                    svg.appendChild(arc);

                    // bisectriz interior
                    let bx = vPrev.x + vNext.x,
                        by = vPrev.y + vNext.y;
                    if (absAng > 180) {
                        bx = -bx;
                        by = -by;
                    }
                    let bl = Math.hypot(bx, by);
                    if (bl < 1e-6) {
                        const sgn = t.angleDeg >= 0 ? 1 : -1;
                        bx = -vPrev.y * sgn;
                        by = vPrev.x * sgn;
                        bl = Math.hypot(bx, by) || 1;
                    }
                    bx /= bl;
                    by /= bl;

                    const label =
                        (Math.round(t.angleDeg * 100) / 100)
                            .toString()
                            .replace(/\.0+$/, "") + "°";
                    const tb = approxTextBox(label, SIZE_DIM_TEXT);
                    function makeBox(cx0, cy0) {
                        return {
                            left: cx0 - tb.w / 2,
                            right: cx0 + tb.w / 2,
                            top: cy0 - tb.h / 2,
                            bottom: cy0 + tb.h / 2,
                        };
                    }

                    let placed = null;
                    for (
                        let off = ANGLE_LABEL_OFFSET;
                        off <= ANGLE_LABEL_MAX_OFFSET && !placed;
                        off += LABEL_STEP
                    ) {
                        for (
                            let k = 0;
                            k < ANGLE_LABEL_SWEEP_DEG.length && !placed;
                            k++
                        ) {
                            const dAng = ANGLE_LABEL_SWEEP_DEG[k];
                            const dir = rotVec({ x: bx, y: by }, dAng);
                            const lx = P.x + dir.x * (R + off),
                                ly = P.y + dir.y * (R + off);
                            const box = makeBox(lx, ly);
                            const out =
                                box.top < 0 ||
                                box.bottom > alto ||
                                box.left < 0 ||
                                box.right > ancho;
                            if (out) continue;
                            const collideFig = window.__figBoxesGroup.some(
                                (b) => rectsOverlap(b, box, LABEL_CLEARANCE)
                            );
                            if (collideFig) continue;
                            const collideDims = (
                                window.__dimBoxesGroup || []
                            ).some((b) =>
                                rectsOverlap(b, box, LABEL_CLEARANCE)
                            );
                            if (collideDims) continue;
                            const collideAngles = (
                                window.__angleBoxesGroup || []
                            ).some((b) =>
                                rectsOverlap(b, box, LABEL_CLEARANCE)
                            );
                            if (collideAngles) continue;
                            const collideLetters = (
                                window.__placedLetterBoxes || []
                            ).some((b) =>
                                rectsOverlap(b, box, LABEL_CLEARANCE)
                            );
                            if (collideLetters) continue;
                            const collideLegend = (
                                window.__legendBoxesGroup || []
                            ).some((b) =>
                                rectsOverlap(b, box, LABEL_CLEARANCE)
                            );
                            if (collideLegend) continue;
                            placed = { x: lx, y: ly, box };
                        }
                    }
                    if (!placed) return;
                    window.__angleBoxesGroup.push(placed.box);
                    const txt = document.createElementNS(
                        "http://www.w3.org/2000/svg",
                        "text"
                    );
                    txt.setAttribute("x", placed.x);
                    txt.setAttribute("y", placed.y);
                    txt.setAttribute("fill", VALOR_COTA_COLOR);
                    txt.setAttribute("font-size", SIZE_DIM_TEXT);
                    txt.setAttribute("text-anchor", "middle");
                    txt.setAttribute("alignment-baseline", "middle");
                    txt.style.cursor = "pointer";
                    txt.textContent = label;
                    svg.appendChild(txt);
                    txt.addEventListener("mouseenter", () => {
                        arc.setAttribute("stroke", "rgba(255,69,0,1)");
                        arc.setAttribute("stroke-width", "3");
                        arc.style.filter =
                            "drop-shadow(0 0 2px rgba(255,69,0,0.7))";
                    });
                    txt.addEventListener("mouseleave", () => {
                        arc.setAttribute("stroke", "rgba(255,99,71,0.7)");
                        arc.setAttribute("stroke-width", "2");
                        arc.style.filter = "none";
                    });
                });
            })();

            // Hitbox de interacción
            const hitbox = pathEl.cloneNode(false);
            hitbox.setAttribute("stroke-width", "50");
            hitbox.setAttribute("stroke", "transparent");
            hitbox.setAttribute("fill", "none");
            hitbox.style.cursor = "pointer";
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
            svg.insertBefore(hitbox, pathEl);

            const etiquetaClick =
                (elemento.codigo != null ? elemento.codigo : elemento.id) + "";
            pathEl.style.cursor = "pointer";
            pathEl.setAttribute(
                "title",
                "Click: dividir · Ctrl/Shift/⌘+Click o botón derecho: info"
            );
            pathEl.addEventListener("click", function (e) {
                if (e.ctrlKey || e.metaKey || e.shiftKey) {
                    e.preventDefault();
                    if (window.mostrarPanelInfoElemento)
                        window.mostrarPanelInfoElemento(elemento.id);
                    return;
                }
                abrirModalDividirElemento(elemento.id, etiquetaClick);
            });
            pathEl.addEventListener("contextmenu", function (e) {
                e.preventDefault();
                if (window.mostrarPanelInfoElemento)
                    window.mostrarPanelInfoElemento(elemento.id);
            });

            // ===================
            // Cotas (longitudes)
            // ===================
            const placedBoxes = [];
            const segsAdj = computeLineSegments(dimsAdjForDraw); // ← CORREGIDO (antes usaba 'dims' inexistente)
            const segsOrig = computeLineSegments(dimsNoZero);
            const segsUnicos = agruparPorDireccionYEtiquetaRobusto(
                segsAdj,
                segsOrig,
                { dirPrecision: 1e-2, labelFormat: { decimals: 0, step: null } }
            );

            segsUnicos.forEach(function (s) {
                const s1 = rotatePoint(s.start, m.cxModel, m.cyModel, m.rotDeg);
                const s2 = rotatePoint(s.end, m.cxModel, m.cyModel, m.rotDeg);
                const p1 = {
                    x: cx + (s1.x - m.midX) * scale,
                    y: cy + (s1.y - m.midY) * scale,
                };
                const p2 = {
                    x: cx + (s2.x - m.midX) * scale,
                    y: cy + (s2.y - m.midY) * scale,
                };
                const label = s._label;

                const dx = p2.x - p1.x,
                    dy = p2.y - p1.y;
                const L = Math.hypot(dx, dy) || 1;
                const tx = dx / L,
                    ty = dy / L;
                const nx = dy / L,
                    ny = -dx / L;
                const mx = (p1.x + p2.x) / 2,
                    my = (p1.y + p2.y) / 2;
                const baseLX = mx + nx * DIM_OFFSET,
                    baseLY = my + ny * DIM_OFFSET;

                const tb = approxTextBox(label, SIZE_DIM_TEXT);
                const tw = tb.w,
                    th = tb.h;
                function makeBox(cx0, cy0) {
                    return {
                        left: cx0 - tw / 2,
                        right: cx0 + tw / 2,
                        top: cy0 - th / 2,
                        bottom: cy0 + th / 2,
                    };
                }

                const maxShift = L * DIM_TANG_MAX_FRAC;
                let bestLX = baseLX,
                    bestLY = baseLY;
                for (
                    let step = 0;
                    step <= Math.ceil(maxShift / DIM_TANG_STEP);
                    step++
                ) {
                    const dir = step % 2 === 0 ? 1 : -1;
                    const mult = Math.ceil(step / 2);
                    const shift = dir * mult * DIM_TANG_STEP;
                    if (Math.abs(shift) > maxShift) continue;
                    const lx = baseLX + tx * shift,
                        ly = baseLY + ty * shift;
                    const tProj = (lx - p1.x) * tx + (ly - p1.y) * ty;
                    if (tProj < 0 + tw * 0.3 || tProj > L - tw * 0.3) continue;
                    const labelBox = makeBox(lx, ly);
                    const collideFig = rectsOverlap(
                        {
                            left: Math.min(p1.x, p2.x),
                            right: Math.max(p1.x, p2.x),
                            top: Math.min(p1.y, p2.y),
                            bottom: Math.max(p1.y, p2.y),
                        },
                        labelBox,
                        0
                    );
                    if (collideFig) continue;
                    const collideAngles = (window.__angleBoxesGroup || []).some(
                        (b) => rectsOverlap(b, labelBox, LABEL_CLEARANCE)
                    );
                    if (collideAngles) continue;
                    const collideLegend = (
                        window.__legendBoxesGroup || []
                    ).some((b) => rectsOverlap(b, labelBox, LABEL_CLEARANCE));
                    if (collideLegend) continue;
                    const collideOth = placedBoxes.some((b) =>
                        rectsOverlap(b, labelBox, LABEL_CLEARANCE)
                    );
                    if (collideOth) continue;
                    const outOfBounds =
                        labelBox.top < 0 ||
                        labelBox.bottom > alto ||
                        labelBox.left < 0 ||
                        labelBox.right > ancho;
                    if (outOfBounds) continue;
                    bestLX = lx;
                    bestLY = ly;
                    placedBoxes.push(labelBox);
                    window.__dimBoxesGroup.push(labelBox);
                    break;
                }

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

                const txt = document.createElementNS(
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

            // =========================
            // Letra (después de cotas)
            // =========================
            (function placeLetter() {
                const letter = indexToLetters(idx);
                const letterSize = 14;
                const tb = approxTextBox(letter, letterSize);
                function makeBoxAt(lx, ly) {
                    return {
                        left: lx,
                        right: lx + tb.w,
                        top: ly - tb.h / 2,
                        bottom: ly + tb.h / 2,
                    };
                }
                let chosen = null;
                const centerYFig = (figBox.top + figBox.bottom) / 2;
                const baseY = Math.max(
                    tb.h / 2,
                    Math.min(centerYFig, alto - tb.h / 2)
                );
                function tryColumn(xPos) {
                    const maxSpread = Math.max(60, alto * 0.6);
                    for (let off = 0; off <= maxSpread; off += LABEL_STEP) {
                        const dir = off % 2 === 0 ? 1 : -1,
                            mult = Math.ceil(off / 2),
                            dy = dir * mult * LABEL_STEP;
                        const ly = Math.max(
                            tb.h / 2,
                            Math.min(alto - tb.h / 2, baseY + dy)
                        );
                        const lx = xPos;
                        const box = makeBoxAt(lx, ly);
                        const collideFig = window.__figBoxesGroup.some((b) =>
                            rectsOverlap(b, box, LABEL_CLEARANCE)
                        );
                        if (collideFig) continue;
                        const collideDims = (window.__dimBoxesGroup || []).some(
                            (b) => rectsOverlap(b, box, LABEL_CLEARANCE)
                        );
                        if (collideDims) continue;
                        const collideAngles = (
                            window.__angleBoxesGroup || []
                        ).some((b) => rectsOverlap(b, box, LABEL_CLEARANCE));
                        if (collideAngles) continue;
                        const collideLegend = (
                            window.__legendBoxesGroup || []
                        ).some((b) => rectsOverlap(b, box, LABEL_CLEARANCE));
                        if (collideLegend) continue;
                        const collidePrev = (
                            window.__placedLetterBoxes || []
                        ).some((b) => rectsOverlap(b, box, LABEL_CLEARANCE));
                        if (collidePrev) continue;
                        const out =
                            box.top < 0 ||
                            box.bottom > alto ||
                            box.left < 0 ||
                            box.right > ancho;
                        if (out) continue;
                        chosen = { x: lx, y: ly, box };
                        return true;
                    }
                    return false;
                }
                const baseRight = clampXInside(
                    figBox.right + 10,
                    tb.w,
                    0,
                    ancho
                );
                const baseLeft = clampXInside(
                    figBox.left - 10 - tb.w,
                    tb.w,
                    0,
                    ancho
                );
                let columnsTried = 0,
                    xStep = 8;
                while (!chosen && columnsTried < 8) {
                    const xCol = clampXInside(
                        baseRight + columnsTried * xStep,
                        tb.w,
                        0,
                        ancho
                    );
                    if (tryColumn(xCol)) break;
                    columnsTried++;
                }
                columnsTried = 0;
                while (!chosen && columnsTried < 8) {
                    const xColL = clampXInside(
                        baseLeft - columnsTried * xStep,
                        tb.w,
                        0,
                        ancho
                    );
                    if (tryColumn(xColL)) break;
                    columnsTried++;
                }
                if (!chosen) {
                    const lx = clampXInside(ancho - tb.w, tb.w, 0, ancho);
                    const ly = baseY;
                    chosen = { x: lx, y: ly, box: makeBoxAt(lx, ly) };
                }
                window.__placedLetterBoxes.push(chosen.box);
                const t = document.createElementNS(
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
        });

        contenedor.innerHTML = "";
        contenedor.appendChild(svg);
    });
});

// =======================
// Modal dividir elemento
// =======================
function abrirModalDividirElemento(elementoId) {
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
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const token =
            fd.get("_token") ||
            (tokenMeta ? tokenMeta.getAttribute("content") : null);
        const headers = token ? { "X-CSRF-TOKEN": token } : {};
        const res = await fetch(url, { method: "POST", headers, body: fd });
        const data = await res.json();
        if (!res.ok || !data.success)
            throw new Error(data.message || "Error al dividir");
        form.reset();
        const modalEl = document.getElementById("modalDividirElemento");
        if (modalEl) modalEl.classList.add("hidden");
        if (window.Swal) window.Swal.fire("Hecho", data.message, "success");
        else alert(data.message);
    } catch (e) {
        if (window.Swal)
            window.Swal.fire("Error", (e && e.message) || "Error", "error");
        else alert((e && e.message) || "Error");
    }
}
