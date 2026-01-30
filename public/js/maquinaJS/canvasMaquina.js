// =======================
// Colores y configuración
// =======================
const FIGURE_LINE_COLOR = "rgba(0, 0, 0, 0.8)";
const VALOR_COTA_COLOR = "rgba(0, 0, 0, 1)";
const BARS_TEXT_COLOR = "rgba(0, 0, 0, 1)";

const marginX = 50;
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

// Reserva mínima para "anillo de cotas"
const DIM_RING_MARGIN = DIM_LINE_OFFSET + SIZE_DIM_TEXT + DIM_LABEL_LIFT + 4;

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
    svg.style.height = "100%"; // antes era 70%
    svg.style.display = "block";
    svg.style.background = bgColor || "#ffffff";
    svg.style.shapeRendering = "geometricPrecision";
    svg.style.textRendering = "optimizeLegibility";
    svg.style.boxSizing = "border-box"; // 🔥 igual que la etiqueta
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

// ——— Padding exclusivo para la leyenda (0 = pegado al borde) ———
const LEGEND_PAD_X = 0;
const LEGEND_PAD_Y = -25; // Negativo para bajar la leyenda (más cerca del borde inferior)

/** Dibuja la leyenda SIEMPRE abajo-izquierda del SVG */
function drawLegendBottomLeft(svg, entries, width, height) {
    if (!entries || !entries.length) return;

    const gap = 2; // separación entre líneas
    const size = 12; // tamaño de texto
    const lineH = size + gap;

    const lines = entries.map(
        (e) => (e.letter ? e.letter + " " : "") + (e.text || "")
    );

    // Altura total de la leyenda
    const totalH = size * lines.length + gap * (lines.length - 1);

    // Esquina inferior izquierda, sin usar marginX/marginY
    const x = LEGEND_PAD_X;
    // Como usamos alignment-baseline="middle", arrancamos a mitad de la primera línea
    let y = height - LEGEND_PAD_Y - totalH + size / 2;

    // Guarda cajas para evitar solapes con cotas/ángulos/letras
    window.__legendBoxesGroup = window.__legendBoxesGroup || [];

    for (let i = 0; i < lines.length; i++) {
        const text = lines[i];

        const t = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "text"
        );
        t.setAttribute("x", x);
        t.setAttribute("y", y);
        t.setAttribute("fill", BARS_TEXT_COLOR);
        t.setAttribute("font-size", size);
        t.setAttribute("text-anchor", "start");
        t.setAttribute("alignment-baseline", "middle");
        t.style.pointerEvents = "none";
        t.textContent = text;
        svg.appendChild(t);

        // Caja de colisión por línea (ancho aproximado)
        const w = approxTextBox(text, size).w;
        window.__legendBoxesGroup.push({
            left: x,
            right: x + w,
            top: y - size / 2,
            bottom: y + size / 2,
        });

        y += lineH;
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

    // Detectar factor de escala comparando longitudes totales
    const totalAdj = segsAdj.reduce((sum, s) => sum + Math.hypot(s.end.x - s.start.x, s.end.y - s.start.y), 0);
    const totalOrig = segsOrig.reduce((sum, s) => sum + (s.length || Math.hypot(s.end.x - s.start.x, s.end.y - s.start.y)), 0);
    const scaleFactor = totalOrig > 0 ? totalAdj / totalOrig : 1;

    // Crear buckets de longitudes originales por dirección
    const buckets = new Map();
    for (let i = 0; i < segsOrig.length; i++) {
        const s = segsOrig[i];
        const dx = s.end.x - s.start.x,
            dy = s.end.y - s.start.y;
        const key = dirKey(dx, dy, dirPrecision);
        const arr = buckets.get(key) || [];
        arr.push({ length: s.length || Math.hypot(dx, dy), index: i });
        buckets.set(key, arr);
    }

    // También crear un mapa indexado para fallback directo
    const origByIndex = segsOrig.map(s => s.length || Math.hypot(s.end.x - s.start.x, s.end.y - s.start.y));

    const seen = new Set();
    const usedIndices = new Set();
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
            // Calcular la longitud original esperada (desescalando)
            const expectedOrig = adjLen / scaleFactor;

            // Buscar la longitud original más cercana a la esperada (no a la escalada)
            let best = null, bestD = Infinity;
            for (const c of candidates) {
                // Preferir índices no usados para mejor correspondencia
                const d = Math.abs(c.length - expectedOrig);
                const penalty = usedIndices.has(c.index) ? 0.1 : 0; // Pequeña penalización por reusar
                if (d + penalty < bestD) {
                    best = c;
                    bestD = d + penalty;
                }
            }
            if (best) {
                chosen = best.length;
                usedIndices.add(best.index);
            }
        } else if (i < origByIndex.length) {
            // Fallback: usar longitud original por índice si no hay match por dirección
            chosen = origByIndex[i];
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
    const padding = opts.padding ?? 12,
        gapCol = opts.gapCol ?? 12,
        gapRow = opts.gapRow ?? 10,
        kMax = Math.max(1, Math.min(medidas.length, opts.kMax ?? 4));

    // === CONSTANTES Y CONFIGURACIÓN ===
    const anchoUsable = Math.max(10, svgW - 2 * padding);
    const altoUsable = Math.max(10, svgH - 2 * padding);

    // === ANÁLISIS PROFUNDO DE ELEMENTOS ===
    const analysis = analyzeElements(medidas);

    function analyzeElements(items) {
        const aspectRatios = items.map(m => m.w / Math.max(m.h, 1));
        const heights = items.map(m => m.h);
        const widths = items.map(m => m.w);
        const areas = items.map(m => m.w * m.h);

        const avg = arr => arr.reduce((a, b) => a + b, 0) / arr.length;
        const variance = (arr, mean) => arr.reduce((sum, val) => sum + Math.pow(val - mean, 2), 0) / arr.length;
        const stdDev = (arr, mean) => Math.sqrt(variance(arr, mean));

        const avgAspectRatio = avg(aspectRatios);
        const avgHeight = avg(heights);
        const avgWidth = avg(widths);
        const totalArea = areas.reduce((a, b) => a + b, 0);

        const heightStdDev = stdDev(heights, avgHeight);
        const widthStdDev = stdDev(widths, avgWidth);

        // Coeficiente de variación (CV) - medida de dispersión relativa
        const heightCV = avgHeight > 0 ? heightStdDev / avgHeight : 0;
        const widthCV = avgWidth > 0 ? widthStdDev / avgWidth : 0;

        return {
            avgAspectRatio,
            avgHeight,
            avgWidth,
            totalArea,
            heightCV,
            widthCV,
            isLinear: avgAspectRatio > 6 || avgHeight < avgWidth * 0.12,
            isUniformHeight: heightCV < 0.15,
            isUniformWidth: widthCV < 0.15,
            isHighlyVariable: heightCV > 0.5 || widthCV > 0.5,
            count: items.length
        };
    }

    // === BÚSQUEDA INTELIGENTE DE CONFIGURACIÓN ÓPTIMA ===
    let best = { S: 0, k: 1, cols: null, score: 0 };

    for (let k = 1; k <= kMax; k++) {
        const cols = assignColumnsFFD(medidas, k, gapRow);
        const sumWCols = cols.reduce((a, c) => a + c.maxW, 0) + gapCol * (k - 1);
        const maxHCols = Math.max(...cols.map((c) => c.sumH));
        if (sumWCols <= 0 || maxHCols <= 0) continue;

        // Calcular escala con un factor de reducción para dar más espacio
        const scaleW = anchoUsable / sumWCols;
        const scaleH = altoUsable / maxHCols;
        const rawScale = Math.min(scaleW, scaleH);

        // Aplicar factor de reducción del 92% para dar más margen
        const S = Math.max(0.01, rawScale * 0.92);

        // Métricas de calidad
        const scaledArea = analysis.totalArea * S * S;
        const containerArea = anchoUsable * altoUsable;
        const efficiency = scaledArea / containerArea;

        // Balance de columnas (penalizar distribuciones muy desbalanceadas)
        const colHeights = cols.map(c => c.sumH * S);
        const avgColHeight = colHeights.reduce((a, b) => a + b, 0) / k;
        const heightDiff = Math.max(...colHeights) - Math.min(...colHeights);
        const colBalance = avgColHeight > 0 ? 1 - (heightDiff / avgColHeight) : 1;

        // Penalizar fuertemente configuraciones con muchas columnas
        const columnPenalty = k === 1 ? 1.1 : k === 2 ? 0.9 : 0.7;

        // Penalizar configuraciones con muchas columnas para pocos elementos
        const densityPenalty = k > analysis.count * 0.5 ? 0.7 : 1;

        // Bonus para configuraciones bien balanceadas
        const balanceBonus = colBalance > 0.85 ? 1.05 : 1.0;

        // Score compuesto: favorece escala grande, penaliza muchas columnas
        const score = S * (1 + efficiency * 0.25) * (1 + colBalance * 0.1) * columnPenalty * densityPenalty * balanceBonus;

        if (score > best.score) {
            best = { S, k, cols, score, efficiency, colBalance };
        }
    }

    // === DISTRIBUCIÓN HORIZONTAL MEJORADA ===
    const widthsEsc = best.cols.map((c) => c.maxW * best.S);
    const totalContentW = widthsEsc.reduce((a, w) => a + w, 0);
    const horizontalFillRatio = totalContentW / anchoUsable;

    let centersX = [];

    if (best.k === 1) {
        // Una sola columna: centrar
        centersX[0] = svgW / 2;
    } else {
        // Para múltiples columnas: usar gap mínimo de gapCol y distribuir equitativamente
        const totalMinGapSpace = (best.k - 1) * gapCol;
        const totalUsedWidth = totalContentW + totalMinGapSpace;

        if (totalUsedWidth < anchoUsable) {
            // Si cabe con el gap mínimo, distribuir el espacio extra equitativamente
            const extraSpace = anchoUsable - totalUsedWidth;
            const gapColAdjusted = gapCol + (extraSpace / (best.k - 1));

            let xStart = padding;
            for (let c = 0; c < best.k; c++) {
                centersX[c] = xStart + widthsEsc[c] / 2;
                xStart += widthsEsc[c] + gapColAdjusted;
            }
        } else {
            // Si no cabe, usar gap mínimo y centrar
            let xStart = padding + Math.max(0, (anchoUsable - totalUsedWidth) / 2);
            for (let c = 0; c < best.k; c++) {
                centersX[c] = xStart + widthsEsc[c] / 2;
                xStart += widthsEsc[c] + gapCol;
            }
        }
    }

    // === DISTRIBUCIÓN VERTICAL: USAR TODO EL ESPACIO DISPONIBLE ===
    const centersYByCol = [];

    // Calcular el ancho que ocupa la leyenda (se calcula después de dibujarla)
    // Las columnas a la derecha de la leyenda pueden usar toda la altura del SVG
    const getLegendWidth = () => {
        if (!window.__legendBoxesGroup || window.__legendBoxesGroup.length === 0) {
            return 0;
        }
        return Math.max(...window.__legendBoxesGroup.map(box => box.right));
    };

    for (let c = 0; c < best.k; c++) {
        const col = best.cols[c];
        centersYByCol[c] = [];

        // Calcular alturas escaladas
        const heights = col.items.map(idx => medidas[idx].h * best.S);
        const totalItemsHeight = heights.reduce((a, b) => a + b, 0);

        // Si no hay elementos en la columna, continuar
        if (col.items.length === 0) continue;

        // Determinar si esta columna solapa con la leyenda
        const colCenterX = centersX[c];
        const colWidth = best.cols[c].maxW * best.S;
        const colLeftX = colCenterX - colWidth / 2;
        const colRightX = colCenterX + colWidth / 2;

        const legendWidth = getLegendWidth();

        // Verificar si hay solape significativo con la leyenda
        // Calculamos cuánto de la columna solapa con la leyenda
        const overlapWidth = Math.max(0, Math.min(colRightX, legendWidth) - colLeftX);
        const overlapPercentage = overlapWidth / colWidth;

        // Si hay cualquier solape con la leyenda (> 5%), reducir altura
        const hasSignificantOverlap = overlapPercentage > 0.05;

        // Calcular altura disponible para esta columna
        let availableHeight = altoUsable;

        if (hasSignificantOverlap && window.__legendBoxesGroup && window.__legendBoxesGroup.length > 0) {
            // Si más del 5% de la columna solapa con la leyenda, restar la altura de la leyenda
            const legendTop = Math.min(...window.__legendBoxesGroup.map(box => box.top));
            const maxYForThisCol = legendTop - 15; // 15px de margen de seguridad para evitar que elementos toquen la leyenda
            availableHeight = Math.max(10, maxYForThisCol - padding);
        }

        // Si solo hay un elemento, centrarlo verticalmente
        if (col.items.length === 1) {
            const h = heights[0];
            const centerY = padding + availableHeight / 2;
            const validCenterY = Math.max(padding + h / 2, Math.min(centerY, svgH - padding - h / 2));
            centersYByCol[c].push(validCenterY);
            continue;
        }

        // Para múltiples elementos: DISTRIBUIR POR TODO EL ESPACIO VERTICAL DISPONIBLE

        // Verificar si los elementos caben en el espacio disponible
        if (totalItemsHeight > availableHeight) {
            // No caben: necesitamos reducir la escala o apilar con gap mínimo
            console.warn(`Columna ${c}: elementos no caben (${totalItemsHeight}px > ${availableHeight}px)`);

            // Determinar gap mínimo según tipo de elementos
            const avgHeight = totalItemsHeight / col.items.length;
            const isVeryThin = avgHeight < 4;
            const minGap = isVeryThin ? 20 : 5; // Barras rectas: mínimo 20px, otras: 5px

            let y = padding;

            for (let i = 0; i < col.items.length; i++) {
                const h = Math.max(heights[i], 2); // Altura mínima de 2px para mejor visualización
                const centerY = y + h / 2;
                centersYByCol[c].push(centerY);
                y += h + minGap;
            }
        } else {
            // Sí caben: distribuir usando todo el espacio disponible

            // Calcular espacio disponible para gaps
            const spaceForGaps = availableHeight - totalItemsHeight;
            const numberOfGaps = col.items.length - 1;

            // Distribuir el espacio equitativamente entre todos los gaps
            let gap = spaceForGaps / numberOfGaps;

            // Para barras muy delgadas, necesitan MÁS espacio para ser visibles
            const avgHeight = totalItemsHeight / col.items.length;
            const isVeryThin = avgHeight < 4;

            if (isVeryThin) {
                // Para barras rectas: gap mínimo de 18px, máximo de 50px
                gap = Math.max(18, Math.min(gap, 50));
            } else {
                // Para figuras normales: gap mínimo de 5px
                gap = Math.max(5, gap);
            }

            // Recalcular la altura total con el gap ajustado
            const actualTotalHeight = totalItemsHeight + (numberOfGaps * gap);

            // Si hay espacio extra después del ajuste, centrarlo verticalmente
            const extraSpace = availableHeight - actualTotalHeight;
            let y = padding + Math.max(0, extraSpace / 2);

            // Posicionar elementos
            for (let i = 0; i < col.items.length; i++) {
                const h = Math.max(heights[i], 2); // Altura mínima de 2px para mejor visualización
                const centerY = y + h / 2;

                // Validar que no exceda los límites
                const maxAllowedY = padding + availableHeight - h / 2;
                const validCenterY = Math.max(padding + h / 2, Math.min(centerY, maxAllowedY));

                centersYByCol[c].push(validCenterY);

                y += h + gap;
            }
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
// Función para renderizar un grupo SVG
// =======================
window.renderizarGrupoSVG = function renderizarGrupoSVG(grupo, gidx) {
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

        // ===== AGRUPAR elementos con mismo diámetro+dimensiones, sumando barras =====
        const elementosAgrupados = [];
        const gruposMap = new Map(); // key: "diametro|dimensiones" -> índice en elementosAgrupados

        (grupo.elementos || []).forEach((elemento) => {
            // Normalizar diámetro
            let diametroNorm = "0";
            if (elemento.diametro != null && elemento.diametro !== "") {
                const dstr = String(elemento.diametro).replace(",", ".");
                const mtch = dstr.match(/-?\d+(?:\.\d+)?/);
                if (mtch) {
                    const dn = parseFloat(mtch[0]);
                    if (isFinite(dn)) diametroNorm = String(Math.round(dn));
                }
            }

            // Normalizar dimensiones (trim, lowercase)
            const dimensionesNorm = (elemento.dimensiones || "barra").trim().toLowerCase();
            const key = `${diametroNorm}|${dimensionesNorm}`;

            if (gruposMap.has(key)) {
                // Ya existe, sumar barras
                const idx = gruposMap.get(key);
                elementosAgrupados[idx].barrasTotal += (elemento.barras || 0);
                // Acumular coladas únicas
                if (elemento.coladas?.colada1 && !elementosAgrupados[idx].coladasSet.has(elemento.coladas.colada1)) {
                    elementosAgrupados[idx].coladasSet.add(elemento.coladas.colada1);
                }
                if (elemento.coladas?.colada2 && !elementosAgrupados[idx].coladasSet.has(elemento.coladas.colada2)) {
                    elementosAgrupados[idx].coladasSet.add(elemento.coladas.colada2);
                }
                if (elemento.coladas?.colada3 && !elementosAgrupados[idx].coladasSet.has(elemento.coladas.colada3)) {
                    elementosAgrupados[idx].coladasSet.add(elemento.coladas.colada3);
                }
            } else {
                // Nuevo grupo
                const coladasSet = new Set();
                if (elemento.coladas?.colada1) coladasSet.add(elemento.coladas.colada1);
                if (elemento.coladas?.colada2) coladasSet.add(elemento.coladas.colada2);
                if (elemento.coladas?.colada3) coladasSet.add(elemento.coladas.colada3);

                elementosAgrupados.push({
                    elemento: elemento, // Elemento representativo para dibujar
                    diametro: diametroNorm,
                    dimensiones: elemento.dimensiones,
                    barrasTotal: elemento.barras || 0,
                    coladasSet: coladasSet,
                });
                gruposMap.set(key, elementosAgrupados.length - 1);
            }
        });

        // ===== leyenda: usar elementos agrupados (únicos) con suma de barras =====
        const legendEntries = elementosAgrupados.map((grp, idx) => {
            // Construir texto de coladas: primero de la etiqueta, luego del grupo
            const coladas = [];

            // Colada de la etiqueta (asignada en primer clic)
            if (grupo.colada_etiqueta) {
                coladas.push(grupo.colada_etiqueta);
            }
            // Colada 2 de la etiqueta (asignada en segundo clic si cambió)
            if (grupo.colada_etiqueta_2 && grupo.colada_etiqueta_2 !== grupo.colada_etiqueta) {
                coladas.push(grupo.colada_etiqueta_2);
            }

            // Si no hay coladas de etiqueta, usar las del grupo de elementos
            if (coladas.length === 0 && grp.coladasSet.size > 0) {
                coladas.push(...grp.coladasSet);
            }

            const textColadas = coladas.length > 0 ? ` (${coladas.join(", ")})` : "";

            return {
                letter: indexToLetters(idx),
                text: `Ø${grp.diametro} x${grp.barrasTotal}${textColadas}`,
            };
        });
        drawLegendBottomLeft(svg, legendEntries, ancho, alto); // ← primero, para evitar solapes con todo lo demás

        // ====== medir piezas usando elementos únicos (agrupados) ======
        const preproc = elementosAgrupados.map((grp) => {
            const el = grp.elemento;
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
            padding: 20,
            gapCol: 50,
            gapRow: 22,
            kMax: 3,
        });

        const indexInCol = new Map();
        for (let c = 0; c < plan.k; c++) {
            plan.cols[c].items.forEach((idx, j) =>
                indexInCol.set(idx, { c, j })
            );
        }

        // ====== bucle de pintado (solo elementos únicos) ======
        elementosAgrupados.forEach(function (grp, idx) {
            const elemento = grp.elemento;
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

                            // Margen completo con figuras (6px)
                            const collideFig = window.__figBoxesGroup.some(
                                (b) => rectsOverlap(b, box, LABEL_CLEARANCE)
                            );
                            if (collideFig) continue;

                            // Margen reducido con dimensiones (2px) - los ángulos pueden estar muy cerca de las cotas
                            const collideDims = (
                                window.__dimBoxesGroup || []
                            ).some((b) => rectsOverlap(b, box, 2));
                            if (collideDims) continue;

                            // Margen reducido entre ángulos (2px)
                            const collideAngles = (
                                window.__angleBoxesGroup || []
                            ).some((b) => rectsOverlap(b, box, 2));
                            if (collideAngles) continue;

                            // Margen reducido con letras (2px)
                            const collideLetters = (
                                window.__placedLetterBoxes || []
                            ).some((b) => rectsOverlap(b, box, 2));
                            if (collideLetters) continue;

                            // Margen completo con leyenda (6px)
                            const collideLegend = (
                                window.__legendBoxesGroup || []
                            ).some((b) => rectsOverlap(b, box, LABEL_CLEARANCE));
                            if (collideLegend) continue;

                            placed = { x: lx, y: ly, box };
                        }
                    }

                    // Si no encontramos posición válida, usar la posición base como último recurso
                    if (!placed) {
                        const fallbackX = P.x + bx * (R + ANGLE_LABEL_OFFSET);
                        const fallbackY = P.y + by * (R + ANGLE_LABEL_OFFSET);
                        const fallbackBox = makeBox(fallbackX, fallbackY);
                        placed = { x: fallbackX, y: fallbackY, box: fallbackBox };
                    }

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
                abrirModalDividirElemento(elemento.id, elemento.barras || 0);
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

                    // Margen reducido con ángulos (2px)
                    const collideAngles = (window.__angleBoxesGroup || []).some(
                        (b) => rectsOverlap(b, labelBox, 2)
                    );
                    if (collideAngles) continue;

                    // Margen completo con leyenda (6px)
                    const collideLegend = (
                        window.__legendBoxesGroup || []
                    ).some((b) => rectsOverlap(b, labelBox, LABEL_CLEARANCE));
                    if (collideLegend) continue;

                    // Margen reducido entre cotas (2px)
                    const collideOth = placedBoxes.some((b) =>
                        rectsOverlap(b, labelBox, 2)
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

            // ========== COTAS DE RADIOS (ARCOS) ==========
            (function drawArcDimensions() {
                const arcs = [];
                let x = 0, y = 0, a = 0;

                for (let k = 0; k < dimsScaled.length; k++) {
                    const d = dimsScaled[k];
                    if (d.type === "line") {
                        x += d.length * Math.cos(rad(a));
                        y += d.length * Math.sin(rad(a));
                    } else if (d.type === "turn") {
                        a += d.angle;
                    } else if (d.type === "arc") {
                        const cx_arc = x + d.radius * Math.cos(rad(a + 90));
                        const cy_arc = y + d.radius * Math.sin(rad(a + 90));
                        const start = Math.atan2(y - cy_arc, x - cx_arc);
                        const end = start + rad(d.arcAngle);

                        let originalRadius = d.radius;
                        for (let j = 0; j < dimsNoZero.length; j++) {
                            const orig = dimsNoZero[j];
                            if (orig.type === "arc" && Math.abs(orig.arcAngle - d.arcAngle) < 0.01) {
                                originalRadius = orig.radius;
                                break;
                            }
                        }

                        arcs.push({
                            center: { x: cx_arc, y: cy_arc },
                            radius: d.radius,
                            originalRadius: originalRadius,
                            arcAngle: d.arcAngle,
                            startAngle: start,
                            endAngle: end
                        });

                        x = cx_arc + d.radius * Math.cos(end);
                        y = cy_arc + d.radius * Math.sin(end);
                        a += d.arcAngle;
                    }
                }

                arcs.forEach(function(arc) {
                    // Transform arc center to SVG coordinates
                    const centerRot = rotatePoint(arc.center, m.cxModel, m.cyModel, m.rotDeg);
                    const centerSvg = {
                        x: cx + (centerRot.x - m.midX) * scale,
                        y: cy + (centerRot.y - m.midY) * scale
                    };

                    const midAngle = (arc.startAngle + arc.endAngle) / 2;
                    const radiusSvg = arc.radius * scale;
                    const label = "R" + formatDimLabel(arc.originalRadius, { decimals: 0, step: null });
                    const tb = approxTextBox(label, SIZE_DIM_TEXT);

                    // Try multiple angles around the arc
                    const angleOffsets = [0, 15, -15, 30, -30, 45, -45, 60, -60, 90, -90];
                    let placed = false;
                    let labelX, labelY, labelBox;

                    for (let angleIdx = 0; angleIdx < angleOffsets.length && !placed; angleIdx++) {
                        const angleOffset = angleOffsets[angleIdx];
                        const adjustedAngle = midAngle + rad(angleOffset);

                        // Calculate point on arc at adjusted angle
                        const arcPtAdj = {
                            x: arc.center.x + arc.radius * Math.cos(adjustedAngle),
                            y: arc.center.y + arc.radius * Math.sin(adjustedAngle)
                        };
                        const arcPtRot = rotatePoint(arcPtAdj, m.cxModel, m.cyModel, m.rotDeg);
                        const arcPtSvg = {
                            x: cx + (arcPtRot.x - m.midX) * scale,
                            y: cy + (arcPtRot.y - m.midY) * scale
                        };

                        // Calculate direction from center to arc point
                        const dxAdj = arcPtSvg.x - centerSvg.x;
                        const dyAdj = arcPtSvg.y - centerSvg.y;
                        const distAdj = Math.hypot(dxAdj, dyAdj) || 1;
                        const nxAdj = dxAdj / distAdj;
                        const nyAdj = dyAdj / distAdj;

                        // Try multiple distances from the arc
                        for (let offset = 8; offset <= 60 && !placed; offset += 6) {
                            const lineEnd = radiusSvg * 0.7;
                            labelX = centerSvg.x + nxAdj * lineEnd + nxAdj * offset;
                            labelY = centerSvg.y + nyAdj * lineEnd + nyAdj * offset;
                            labelBox = {
                                left: labelX - tb.w / 2,
                                right: labelX + tb.w / 2,
                                top: labelY - tb.h / 2,
                                bottom: labelY + tb.h / 2
                            };

                            // Check bounds and collisions
                            const outOfBounds = labelBox.top < 0 || labelBox.bottom > alto ||
                                              labelBox.left < 0 || labelBox.right > ancho;
                            if (outOfBounds) continue;

                            const collideFig = window.__figBoxesGroup.some(b =>
                                rectsOverlap(b, labelBox, 2)
                            );
                            if (collideFig) continue;

                            const collideLegend = (window.__legendBoxesGroup || []).some(b =>
                                rectsOverlap(b, labelBox, 2)
                            );
                            if (collideLegend) continue;

                            placed = true;
                            break;
                        }
                    }

                    if (!placed) return;

                    placedBoxes.push(labelBox);

                    // Draw radial line
                    const dirToLabel = {
                        x: labelX - centerSvg.x,
                        y: labelY - centerSvg.y
                    };
                    const distToLabel = Math.hypot(dirToLabel.x, dirToLabel.y) || 1;
                    const nToLabel = {
                        x: dirToLabel.x / distToLabel,
                        y: dirToLabel.y / distToLabel
                    };
                    const finalLineEndX = centerSvg.x + nToLabel.x * radiusSvg * 0.6;
                    const finalLineEndY = centerSvg.y + nToLabel.y * radiusSvg * 0.6;

                    const rl = document.createElementNS("http://www.w3.org/2000/svg", "line");
                    rl.setAttribute("x1", centerSvg.x);
                    rl.setAttribute("y1", centerSvg.y);
                    rl.setAttribute("x2", finalLineEndX);
                    rl.setAttribute("y2", finalLineEndY);
                    rl.setAttribute("stroke", "rgba(0,128,0,0.6)");
                    rl.setAttribute("stroke-width", "4");
                    rl.setAttribute("stroke-linecap", "round");
                    rl.setAttribute("vector-effect", "non-scaling-stroke");
                    rl.style.opacity = 0;
                    rl.style.pointerEvents = "none";
                    rl.style.transition = "opacity 120ms ease";
                    svg.appendChild(rl);

                    const txt = document.createElementNS("http://www.w3.org/2000/svg", "text");
                    txt.setAttribute("x", labelX);
                    txt.setAttribute("y", labelY);
                    txt.setAttribute("fill", VALOR_COTA_COLOR);
                    txt.setAttribute("font-size", SIZE_DIM_TEXT);
                    txt.setAttribute("text-anchor", "middle");
                    txt.setAttribute("alignment-baseline", "middle");
                    txt.setAttribute("tabindex", "0");
                    txt.style.cursor = "pointer";
                    txt.textContent = label;
                    svg.appendChild(txt);

                    function onEnter() {
                        rl.style.opacity = 1;
                    }
                    function onLeave() {
                        rl.style.opacity = 0;
                    }
                    txt.addEventListener("mouseenter", onEnter);
                    txt.addEventListener("mouseleave", onLeave);
                    txt.addEventListener("focus", onEnter);
                    txt.addEventListener("blur", onLeave);
                });
            })();

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
                    const maxSpread = 30; // Reducido para mantener la letra cerca de su figura
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

                        // Margen completo con figuras (6px) - no queremos superposición
                        const collideFig = window.__figBoxesGroup.some((b) =>
                            rectsOverlap(b, box, LABEL_CLEARANCE)
                        );
                        if (collideFig) continue;

                        // Margen reducido con dimensiones y ángulos (3px) - permitimos estar más cerca
                        const collideDims = (window.__dimBoxesGroup || []).some(
                            (b) => rectsOverlap(b, box, 3)
                        );
                        if (collideDims) continue;
                        const collideAngles = (
                            window.__angleBoxesGroup || []
                        ).some((b) => rectsOverlap(b, box, 3));
                        if (collideAngles) continue;

                        // Margen completo con leyenda (6px)
                        const collideLegend = (
                            window.__legendBoxesGroup || []
                        ).some((b) => rectsOverlap(b, box, LABEL_CLEARANCE));
                        if (collideLegend) continue;

                        // Margen reducido entre letras (2px) - permitimos que estén más juntas
                        const collidePrev = (
                            window.__placedLetterBoxes || []
                        ).some((b) => rectsOverlap(b, box, 2));
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
                // Estrategia: buscar en círculos concéntricos, probando todas las posiciones cercanas primero
                const positions = [
                    // Prioridad 1: Inmediatamente a la derecha o izquierda de la figura
                    { x: figBox.right + 10, priority: 1 },
                    { x: figBox.left - 10 - tb.w, priority: 1 },
                    // Prioridad 2: Ligeramente más alejado (5-15px)
                    { x: figBox.right + 15, priority: 2 },
                    { x: figBox.left - 15 - tb.w, priority: 2 },
                    { x: figBox.right + 5, priority: 2 },
                    { x: figBox.left - 5 - tb.w, priority: 2 },
                    // Prioridad 3: Un poco más lejos (20-30px)
                    { x: figBox.right + 20, priority: 3 },
                    { x: figBox.left - 20 - tb.w, priority: 3 },
                    { x: figBox.right + 25, priority: 3 },
                    { x: figBox.left - 25 - tb.w, priority: 3 },
                    { x: figBox.right + 30, priority: 3 },
                    { x: figBox.left - 30 - tb.w, priority: 3 },
                ];

                // Ordenar por prioridad y probar cada posición
                positions.sort((a, b) => a.priority - b.priority);

                for (const pos of positions) {
                    if (chosen) break;
                    const xCol = clampXInside(pos.x, tb.w, 0, ancho);
                    if (tryColumn(xCol)) break;
                }

                // Si no encontramos posición, intentar arriba/abajo de la figura centrada
                if (!chosen) {
                    const centerXFig = (figBox.left + figBox.right) / 2;
                    const tryAboveBelow = [
                        { x: centerXFig - tb.w / 2, y: figBox.top - tb.h - 5 },  // Arriba
                        { x: centerXFig - tb.w / 2, y: figBox.bottom + tb.h + 5 }, // Abajo
                    ];

                    for (const pos of tryAboveBelow) {
                        if (chosen) break;
                        const lx = clampXInside(pos.x, tb.w, 0, ancho);
                        const ly = Math.max(tb.h / 2, Math.min(alto - tb.h / 2, pos.y));
                        const box = makeBoxAt(lx, ly);

                        // Verificar colisiones con márgenes reducidos
                        const noCollide =
                            !window.__figBoxesGroup.some((b) => rectsOverlap(b, box, LABEL_CLEARANCE)) &&
                            !(window.__dimBoxesGroup || []).some((b) => rectsOverlap(b, box, 3)) &&
                            !(window.__angleBoxesGroup || []).some((b) => rectsOverlap(b, box, 3)) &&
                            !(window.__legendBoxesGroup || []).some((b) => rectsOverlap(b, box, LABEL_CLEARANCE)) &&
                            !(window.__placedLetterBoxes || []).some((b) => rectsOverlap(b, box, 2)) &&
                            box.top >= 0 && box.bottom <= alto && box.left >= 0 && box.right <= ancho;

                        if (noCollide) {
                            chosen = { x: lx, y: ly, box };
                        }
                    }
                }

                // Último recurso: colocar a la derecha del canvas
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
}

// =======================
// Script principal
// =======================
function initCanvasMaquina() {
    if (window.setDataSources) {
        window.setDataSources({
            sugerencias: window.SUGERENCIAS || {},
            elementosAgrupados: window.elementosAgrupadosScript || [],
        });
    }
    const grupos = window.elementosAgrupadosScript;
    if (!grupos) return;

    // 🔥 PASO 1: Aplicar clases CSS ANTES de renderizar SVG
    const gridMaquina = document.getElementById('grid-maquina');
    if (gridMaquina && window.updateGridClasses) {
        const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'true');
        const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
        window.updateGridClasses(showLeft, showRight);
        console.log('🎨 Clases aplicadas ANTES de renderizar SVG');
    }

    // 🔥 PASO 2: Renderizar todos los SVG
    grupos.forEach(function (grupo, gidx) {
        renderizarGrupoSVG(grupo, gidx);
    });

    // 🔥 PASO 3: Mostrar el grid con las clases ya aplicadas
    requestAnimationFrame(() => {
        if (gridMaquina) {
            gridMaquina.style.opacity = '1';
            gridMaquina.style.visibility = 'visible';
            gridMaquina.style.transition = 'opacity 0.2s ease-in, visibility 0s 0s';
            console.log('✅ Grid visible con clases:', gridMaquina.className);
        }

        // Mostrar las etiquetas
        document.querySelectorAll('.proceso').forEach(el => {
            el.style.opacity = '1';
        });
    });

    // =======================
    // Función global para actualizar SVG con coladas
    // =======================
    window.actualizarSVGConColadas = function(etiquetaSubId, coladasPorElemento) {
        if (!window.elementosAgrupadosScript) return;

        // Buscar el grupo de la etiqueta
        const grupos = window.elementosAgrupadosScript;
        const grupoIndex = grupos.findIndex(g =>
            g.etiqueta && String(g.etiqueta.etiqueta_sub_id) === String(etiquetaSubId)
        );

        if (grupoIndex === -1) {
            console.warn(`No se encontró grupo para etiqueta ${etiquetaSubId}`);
            return;
        }

        const grupo = grupos[grupoIndex];

        // Actualizar coladas en cada elemento según su ID
        if (grupo.elementos && coladasPorElemento) {
            grupo.elementos.forEach(elemento => {
                const elementoId = String(elemento.id);
                const coladas = coladasPorElemento[elementoId];

                // Inicializar objeto coladas si no existe
                if (!elemento.coladas) {
                    elemento.coladas = { colada1: null, colada2: null, colada3: null };
                }

                // Asignar coladas específicas de este elemento
                if (coladas && Array.isArray(coladas)) {
                    elemento.coladas.colada1 = coladas[0] || null;
                    elemento.coladas.colada2 = coladas[1] || null;
                    elemento.coladas.colada3 = coladas[2] || null;
                }
            });
        }

        // Regenerar el SVG completo para este grupo
        renderizarGrupoSVG(grupo, grupoIndex);

        console.log(`✅ SVG actualizado con coladas para etiqueta ${etiquetaSubId}`, coladasPorElemento);
    };

    // =======================
    // Función global para LIMPIAR coladas del SVG (usado al deshacer)
    // =======================
    window.limpiarColadasSVG = function(etiquetaSubId) {
        if (!window.elementosAgrupadosScript) return;

        // Buscar el grupo de la etiqueta
        const grupos = window.elementosAgrupadosScript;
        const grupoIndex = grupos.findIndex(g =>
            g.etiqueta && String(g.etiqueta.etiqueta_sub_id) === String(etiquetaSubId)
        );

        if (grupoIndex === -1) {
            console.warn(`No se encontró grupo para etiqueta ${etiquetaSubId}`);
            return;
        }

        const grupo = grupos[grupoIndex];

        // Limpiar coladas de todos los elementos
        if (grupo.elementos) {
            grupo.elementos.forEach(elemento => {
                elemento.coladas = { colada1: null, colada2: null, colada3: null };
            });
        }

        // Regenerar el SVG completo para este grupo
        renderizarGrupoSVG(grupo, grupoIndex);

        console.log(`🧹 Coladas limpiadas del SVG para etiqueta ${etiquetaSubId}`);
    };

    // =======================
    // Listener para regenerar SVG cuando se deshace una etiqueta
    // =======================
    window.addEventListener('regenerar-svg-etiqueta', function(e) {
        const etiquetaSubId = e.detail?.etiquetaSubId;
        if (!etiquetaSubId || !window.elementosAgrupadosScript) return;

        const grupos = window.elementosAgrupadosScript;
        const grupoIndex = grupos.findIndex(g =>
            g.etiqueta && String(g.etiqueta.etiqueta_sub_id) === String(etiquetaSubId)
        );

        if (grupoIndex === -1) {
            console.warn(`No se encontró grupo para regenerar SVG: ${etiquetaSubId}`);
            return;
        }

        const grupo = grupos[grupoIndex];
        renderizarGrupoSVG(grupo, grupoIndex);
        console.log(`🔄 SVG regenerado para etiqueta ${etiquetaSubId} (evento deshacer)`);
    });
}

// Inicialización compatible con Livewire Navigate
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCanvasMaquina);
} else {
    initCanvasMaquina();
}
document.addEventListener("livewire:navigated", initCanvasMaquina);

// =======================
// Modal dividir elemento
// =======================
window.abrirModalDividirElemento = function abrirModalDividirElemento(elementoId, barras) {
    const modal = document.getElementById("modalDividirElemento");
    const input = document.getElementById("dividir_elemento_id");
    const inputBarrasTotales = document.getElementById("dividir_barras_totales");
    const inputPesoTotal = document.getElementById("dividir_peso_total");
    const labelBarras = document.getElementById("labelBarrasActuales");
    const inputBarrasAMover = document.getElementById("barras_a_mover");
    const preview = document.getElementById("previewDivision");
    const form = document.getElementById("formDividirElemento");
    const badgeSugerencia = document.getElementById("badgeSugerenciaPeso");
    const barrasSugeridas = document.getElementById("barrasSugeridas");
    const detalleSugerencia = document.getElementById("detalleSugerencia");

    if (!modal || !input || !form) return;

    input.value = elementoId;

    // Buscar elemento en elementosAgrupadosScript o gruposResumenData
    let elementoData = null;
    let barrasTotales = parseInt(barras) || 0;

    // Buscar en elementosAgrupadosScript
    if (window.elementosAgrupadosScript) {
        for (const grupo of window.elementosAgrupadosScript) {
            if (grupo.elementos) {
                const elem = grupo.elementos.find(e => String(e.id) === String(elementoId));
                if (elem) {
                    elementoData = elem;
                    if (elem.barras) {
                        barrasTotales = parseInt(elem.barras) || 0;
                    }
                    break;
                }
            }
        }
    }

    // Si no se encontró, buscar en gruposResumenData
    if (!elementoData && window.gruposResumenData) {
        for (const grupo of window.gruposResumenData) {
            if (grupo.elementos) {
                const elem = grupo.elementos.find(e => String(e.id) === String(elementoId));
                if (elem) {
                    elementoData = elem;
                    if (elem.barras) {
                        barrasTotales = parseInt(elem.barras) || 0;
                    }
                    break;
                }
            }
        }
    }

    // Verificar si la etiqueta está en proceso/completada o el elemento pertenece a un paquete
    const estadoEtiqueta = elementoData?.estado;
    const etiquetaEnProcesoOCompletada = estadoEtiqueta === 'fabricando' || estadoEtiqueta === 'completada';
    const tienePaquete = elementoData && elementoData.paquete_id;
    const deshabilitarCambioMaquina = etiquetaEnProcesoOCompletada || tienePaquete;

    // Actualizar estado del radio button "cambiar_maquina"
    const radioCambiarMaquina = document.querySelector('input[name="accion_etiqueta"][value="cambiar_maquina"]');
    const labelCambiarMaquina = radioCambiarMaquina?.closest('label');

    if (radioCambiarMaquina) {
        radioCambiarMaquina.disabled = deshabilitarCambioMaquina;

        // Actualizar estilo visual del label
        if (labelCambiarMaquina) {
            if (deshabilitarCambioMaquina) {
                labelCambiarMaquina.classList.add('opacity-50', 'cursor-not-allowed');
                // Añadir tooltip explicativo
                let motivo = etiquetaEnProcesoOCompletada ? 'La etiqueta está en proceso o completada' : 'El elemento pertenece a un paquete';
                labelCambiarMaquina.setAttribute('title', motivo);
            } else {
                labelCambiarMaquina.classList.remove('opacity-50', 'cursor-not-allowed');
                labelCambiarMaquina.removeAttribute('title');
            }
        }

        // Si estaba seleccionado y ahora está deshabilitado, cambiar a dividir
        if (deshabilitarCambioMaquina && radioCambiarMaquina.checked) {
            const radioDividir = document.querySelector('input[name="accion_etiqueta"][value="dividir"]');
            if (radioDividir) {
                radioDividir.checked = true;
                if (typeof toggleCamposDivision === 'function') {
                    toggleCamposDivision();
                }
            }
        }
    }

    if (inputBarrasTotales) inputBarrasTotales.value = barrasTotales;
    if (labelBarras) labelBarras.textContent = barrasTotales > 0 ? barrasTotales : '-';

    // Obtener peso del elemento y calcular sugerencia para paquetes de máx 1200 kg
    // Usar peso_numerico (valor numérico) en lugar de peso (cadena formateada)
    const pesoTotal = elementoData ? parseFloat(elementoData.peso_numerico) || 0 : 0;
    if (inputPesoTotal) inputPesoTotal.value = pesoTotal;

    console.log('🔍 Debug badge sugerencia:', {
        peso_numerico: elementoData?.peso_numerico,
        pesoTotal,
        barrasTotales
    });

    // Calcular barras sugeridas para mantener paquetes bajo 1200 kg
    const PESO_MAXIMO_PAQUETE = 1200;
    const divisionAutoData = document.getElementById('divisionAutoData');

    if (badgeSugerencia && barrasSugeridas && barrasTotales > 0 && pesoTotal > 0) {
        const pesoPorBarra = pesoTotal / barrasTotales;
        const barrasMaxPorPaquete = Math.floor(PESO_MAXIMO_PAQUETE / pesoPorBarra);

        if (pesoTotal > PESO_MAXIMO_PAQUETE && barrasMaxPorPaquete < barrasTotales) {
            // Calcular cuántas etiquetas se necesitan
            const numEtiquetas = Math.ceil(barrasTotales / barrasMaxPorPaquete);

            // Calcular distribución equitativa de barras
            const barrasPorEtiqueta = Math.floor(barrasTotales / numEtiquetas);
            const etiquetasConBarraExtra = barrasTotales % numEtiquetas;

            // Guardar datos para división automática
            if (divisionAutoData) {
                divisionAutoData.value = JSON.stringify({
                    elemento_id: elementoId,
                    etiqueta_sub_id: elementoData?.etiqueta_sub_id || null,
                    num_etiquetas: numEtiquetas,
                    barras_por_etiqueta: barrasPorEtiqueta,
                    etiquetas_con_barra_extra: etiquetasConBarraExtra,
                    barras_totales: barrasTotales,
                    peso_por_barra: pesoPorBarra
                });
            }

            // Construir mensaje descriptivo
            let detalle = '';
            if (etiquetasConBarraExtra === 0) {
                // Todas las etiquetas tienen el mismo número de barras
                detalle = `${numEtiquetas} etiquetas de ${barrasPorEtiqueta} barras cada una (~${(barrasPorEtiqueta * pesoPorBarra).toFixed(0)} kg)`;
            } else {
                // Algunas etiquetas tienen una barra más
                const barrasGrande = barrasPorEtiqueta + 1;
                const barrasPeque = barrasPorEtiqueta;
                detalle = `${etiquetasConBarraExtra} etiqueta(s) de ${barrasGrande} barras + ${numEtiquetas - etiquetasConBarraExtra} etiqueta(s) de ${barrasPeque} barras`;
            }

            barrasSugeridas.textContent = `${numEtiquetas} etiquetas`;
            detalleSugerencia.innerHTML = detalle + `<br><span class="text-xs text-amber-600">Barras máx por etiqueta: ${barrasMaxPorPaquete} (para no superar ${PESO_MAXIMO_PAQUETE} kg)</span>`;
            badgeSugerencia.classList.remove('hidden');
        } else {
            // No necesita dividir, el peso ya es menor a 1200 kg
            badgeSugerencia.classList.add('hidden');
            if (divisionAutoData) divisionAutoData.value = '';
        }
    } else {
        if (badgeSugerencia) badgeSugerencia.classList.add('hidden');
        if (divisionAutoData) divisionAutoData.value = '';
    }

    // Limpiar campo de barras a mover y preview
    if (inputBarrasAMover) inputBarrasAMover.value = '';
    if (preview) preview.classList.add('hidden');

    if (window.rutaDividirElemento)
        form.setAttribute("action", window.rutaDividirElemento);

    modal.classList.remove("hidden");
}
window.enviarDivision = async function enviarDivision() {
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
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            const preview = text.substring(0, 200) + (text.length > 200 ? '...' : '');
            if (typeof window.mostrarErrorConReporte === 'function') {
                window.mostrarErrorConReporte('El servidor devolvió una respuesta inválida', 'Error de respuesta', `${parseError.message}\n\nRespuesta: ${preview}`);
            } else if (window.Swal) {
                window.Swal.fire("Error", parseError.message, "error");
            }
            return;
        }
        if (!res.ok || !data.success)
            throw new Error(data.message || "Error al dividir");
        form.reset();
        const modalEl = document.getElementById("modalDividirElemento");
        if (modalEl) modalEl.classList.add("hidden");
        if (window.Swal) window.Swal.fire("Hecho", data.message, "success");
        else alert(data.message);
    } catch (e) {
        if (typeof window.mostrarErrorConReporte === 'function') {
            window.mostrarErrorConReporte((e && e.message) || "Error", "Error");
        } else if (window.Swal) {
            window.Swal.fire("Error", (e && e.message) || "Error", "error");
        } else {
            alert((e && e.message) || "Error");
        }
    }
}

// Función para manejar la acción de ver dimensiones desde el modal
window.enviarAccionEtiqueta = async function() {
    const elementoId = document.getElementById('dividir_elemento_id')?.value;
    const accionRadio = document.querySelector('input[name="accion_etiqueta"]:checked');
    const accion = accionRadio?.value;

    if (!elementoId) {
        alert('Falta el ID del elemento.');
        return;
    }

    if (accion === 'ver_dimensiones') {
        // Cerrar el modal actual
        document.getElementById('modalDividirElemento')?.classList.add('hidden');

        // Abrir el modal de ver dimensiones
        if (typeof window.abrirModalVerDimensiones === 'function') {
            window.abrirModalVerDimensiones(elementoId);
        } else {
            alert('La función de ver dimensiones no está disponible');
        }
        return;
    }

    // Para otras acciones, usar el formulario original si existe
    const form = document.getElementById('formDividirElemento');
    if (form && typeof form.requestSubmit === 'function') {
        // Trigger the original form logic
    }
}
