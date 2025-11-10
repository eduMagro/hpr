// ================================================================================
// SVGETIQUETA.JS - Sistema de renderizado de etiquetas de armado
// ================================================================================

// ================================================================================
// 1. CONFIGURACIÃ“N GLOBAL
// ================================================================================

const CONFIG = {
    // Colores
    colors: {
        figureLine: "rgba(0, 0, 0, 0.8)",
        valorCota: "rgba(0, 0, 0, 1)",
        barsText: "rgba(0, 0, 0, 1)",
        angleArc: "rgba(255,99,71,0.7)",
        angleArcHover: "rgba(255,69,0,1)",
        dimHighlight: "rgba(0,0,255,0.6)",
    },

    // MÃ¡rgenes
    margins: {
        x: 50,
        y: 1,
    },

    // Recrecimiento para evitar solapamientos (en UNIDADES del modelo, no px)
    overlapGrowUnits: 0.6,

    // TamaÃ±os de texto
    text: {
        sizeMain: 18,
        sizeDim: 16, // AUMENTADO para mejor legibilidad
        sizeLetter: 16, // AUMENTADO
        sizeLegend: 13, // AUMENTADO
    },

    // Cotas (dimensiones)
    dimensions: {
        lineOffset: 30, // AUMENTADO de 16 a 30 - cotas más alejadas
        labelLift: 12, // AUMENTADO de 10 a 12
        offset: 20, // AUMENTADO de 10 a 20 - más perpendicular
        tangStep: 8, // AUMENTADO de 6 a 8
        tangMaxFrac: 0.35, // REDUCIDO de 0.45 a 0.35 - más centrado
    },

    // Clearance y steps
    spacing: {
        labelClearance: 12, // AUMENTADO de 6 a 12 - más espacio
        labelStep: 6, // AUMENTADO de 4 a 6
    },

    // Bandas reservadas
    bands: {
        topHeight: 26,
        topGap: 14,
        topPadX: 6,
        sideGap: 12,
        sidePad: 6,
    },

    // Margen para anillo de cotas
    get dimRingMargin() {
        return (
            this.dimensions.lineOffset +
            this.text.sizeDim +
            this.dimensions.labelLift +
            6
        );
    },

    // Auto-escala para piezas pequeÃ±as
    autoScale: {
        smallThreshold: 50, // umbral "pieza pequeÃ±a"
        smallScale: 2, // factor de escala
    },

    // Ãngulos
    angles: {
        toleranceDeg: 0.75,
        labelOffset: 25,
        labelMaxOffset: 100,
        labelSweepDeg: [0, 15, -15, 30, -30, 45, -45, 20, -20],
    },

    // Leyenda
    legend: {
        padX: 0,
        padY: 0,
        gap: 2,
    },
};

// ================================================================================
// 1.5 FUNCION DE FILTRADO DE ANGULOS REPETIDOS (NUEVO)
// ================================================================================

/**
 * Filtra angulos repetidos para evitar saturacion visual
 * Mantiene solo los mas importantes (primeros y ultimos)
 */
function filtrarAngulosRepetidos(turns, maxSameAngle = 2) {
    if (!turns || turns.length === 0) return turns;

    // Normalizar angulos a valores absolutos redondeados
    const normalized = turns.map((t, idx) => ({
        ...t,
        originalIndex: idx,
        normalizedAngle: Math.round(Math.abs(t.angleDeg)),
    }));

    // Contar frecuencia de cada angulo
    const angleCounts = new Map();
    normalized.forEach((t) => {
        const ang = t.normalizedAngle;
        angleCounts.set(ang, (angleCounts.get(ang) || 0) + 1);
    });

    // Filtrar manteniendo solo maxSameAngle de cada tipo
    const kept = [];
    const usedAngles = new Map();

    for (const t of normalized) {
        const ang = t.normalizedAngle;
        const count = usedAngles.get(ang) || 0;
        const totalOfThisAngle = angleCounts.get(ang);

        // Si ya tenemos suficientes, saltar
        if (count >= maxSameAngle) continue;

        // Si hay muchos repetidos, solo mantener primero y ultimo
        if (totalOfThisAngle > maxSameAngle) {
            const allOfThisAngle = normalized.filter(
                (x) => x.normalizedAngle === ang
            );
            const isFirst = t.originalIndex === allOfThisAngle[0].originalIndex;
            const isLast =
                t.originalIndex ===
                allOfThisAngle[allOfThisAngle.length - 1].originalIndex;

            if (!isFirst && !isLast && count > 0) {
                continue;
            }
        }

        kept.push(turns[t.originalIndex]);
        usedAngles.set(ang, count + 1);
    }

    return kept;
}

// ================================================================================
// 2. UTILIDADES MATEMÃTICAS
// ================================================================================

const MathUtils = {
    /**
     * Convierte grados a radianes
     */
    rad(deg) {
        return (deg * Math.PI) / 180;
    },

    /**
     * Convierte radianes a grados
     */
    deg(rad) {
        return (rad * 180) / Math.PI;
    },

    /**
     * Normaliza un Ã¡ngulo al rango [0, 180)
     */
    normalizeDeg180(a) {
        return ((a % 180) + 180) % 180;
    },

    /**
     * Calcula el Ã¡ngulo de un segmento entre dos puntos
     */
    computeSegmentAngleDeg(p1, p2) {
        const dx = p2.x - p1.x;
        const dy = p2.y - p1.y;
        const deg = (Math.atan2(dy, dx) * 180) / Math.PI;
        return this.normalizeDeg180(deg);
    },

    /**
     * Rota un punto alrededor de un centro
     */
    rotatePoint(p, cx, cy, degAng) {
        const r = this.rad(degAng);
        const c = Math.cos(r);
        const s = Math.sin(r);
        const dx = p.x - cx;
        const dy = p.y - cy;
        return {
            x: cx + dx * c - dy * s,
            y: cy + dx * s + dy * c,
        };
    },

    /**
     * Limita un valor x dentro de un rango considerando el ancho
     */
    clampXInside(cx, w, left, right) {
        const half = w / 2;
        return Math.max(left + half, Math.min(right - half, cx));
    },
};

// Exponer funciones como antes para compatibilidad
const rad = MathUtils.rad.bind(MathUtils);
const deg = MathUtils.deg.bind(MathUtils);
const normalizeDeg180 = MathUtils.normalizeDeg180.bind(MathUtils);
const computeSegmentAngleDeg = MathUtils.computeSegmentAngleDeg.bind(MathUtils);
const rotatePoint = MathUtils.rotatePoint.bind(MathUtils);
const clampXInside = MathUtils.clampXInside.bind(MathUtils);

// ================================================================================
// 3. UTILIDADES SVG
// ================================================================================

const SVGUtils = {
    /**
     * Obtiene color de estado desde CSS variables
     */
    getEstadoColorFromCSSVar(contenedor) {
        const proceso = contenedor.closest(".proceso");
        if (!proceso) return "#e5e7eb";
        const color = getComputedStyle(proceso)
            .getPropertyValue("--bg-estado")
            .trim();
        return color || "#e5e7eb";
    },

    /**
     * Crea un elemento SVG con configuraciÃ³n base
     */
    crearSVG(width, height, bgColor) {
        const svg = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "svg"
        );
        svg.setAttribute("viewBox", "0 0 " + width + " " + height);
        svg.setAttribute("preserveAspectRatio", "xMidYMid meet");
        svg.style.width = "100%";
        svg.style.height = "100%";
        svg.style.display = "block";
        svg.style.background = bgColor || "#ffffff";
        svg.style.shapeRendering = "geometricPrecision";
        svg.style.textRendering = "optimizeLegibility";
        svg.style.boxSizing = "border-box";
        return svg;
    },

    /**
     * AÃ±ade texto al SVG
     */
    agregarTexto(svg, x, y, texto, color, size, anchor) {
        const txt = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "text"
        );
        txt.setAttribute("x", x);
        txt.setAttribute("y", y);
        txt.setAttribute("fill", color || "black");
        txt.setAttribute("font-size", size || 16);
        txt.setAttribute("text-anchor", anchor || "middle");
        txt.setAttribute("alignment-baseline", "middle");
        txt.style.pointerEvents = "none";
        txt.textContent = texto;
        svg.appendChild(txt);
        return txt;
    },

    /**
     * AÃ±ade un path al SVG
     */
    agregarPathD(svg, d, color, ancho) {
        const path = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "path"
        );
        path.setAttribute("d", d);
        path.setAttribute("stroke", color || CONFIG.colors.figureLine);
        path.setAttribute("fill", "none");
        path.setAttribute("stroke-width", ancho || 2);
        path.setAttribute("vector-effect", "non-scaling-stroke");
        svg.appendChild(path);
        return path;
    },
};

// Exponer funciones para compatibilidad
const getEstadoColorFromCSSVar =
    SVGUtils.getEstadoColorFromCSSVar.bind(SVGUtils);
const crearSVG = SVGUtils.crearSVG.bind(SVGUtils);
const agregarTexto = SVGUtils.agregarTexto.bind(SVGUtils);
const agregarPathD = SVGUtils.agregarPathD.bind(SVGUtils);

// ================================================================================
// 4. GEOMETRÃA Y PATHS
// ================================================================================

const GeometryUtils = {
    /**
     * Escala dimensiones por un factor
     */
    scaleDims(dims, factor) {
        if (!factor || factor === 1) return dims.map((d) => ({ ...d }));
        return dims.map((d) => {
            if (d.type === "line")
                return { ...d, length: (d.length || 0) * factor };
            if (d.type === "arc")
                return { ...d, radius: (d.radius || 0) * factor };
            return { ...d };
        });
    },

    /**
     * Extrae dimensiones desde string
     * Formato: "100 90d 50 45r 90d"
     */
    extraerDimensiones(dimensiones) {
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
    },

    /**
     * Calcula puntos del path
     */
    computePathPoints(dims) {
        let pts = [];
        let x = 0,
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
                const start = Math.atan2(y - cy, x - cx);
                const end = start + rad(d.arcAngle);
                x = cx + d.radius * Math.cos(end);
                y = cy + d.radius * Math.sin(end);
                a += d.arcAngle;
                pts.push({ x, y });
            }
        }

        return pts;
    },

    /**
     * Calcula segmentos de lÃ­nea (sin arcos)
     */
    computeLineSegments(dims) {
        let segs = [];
        let x = 0,
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
                const start = Math.atan2(y - cy, x - cx);
                const end = start + rad(d.arcAngle);
                x = cx + d.radius * Math.cos(end);
                y = cy + d.radius * Math.sin(end);
                a += d.arcAngle;
            }
        }

        return segs;
    },

    /**
     * Combina lÃ­neas rectas consecutivas y elimina giros de 0Â°
     */
    combinarRectasConCeros(dims, tol) {
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
    },

    /**
     * Obtiene vÃ©rtices donde hay giros
     */
    getTurnVertices(dims) {
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
                const cx = x + d.radius * Math.cos(rad(a + 90));
                const cy = y + d.radius * Math.sin(rad(a + 90));
                const start = Math.atan2(y - cy, x - cx);
                const end = start + rad(d.arcAngle);
                x = cx + d.radius * Math.cos(end);
                y = cy + d.radius * Math.sin(end);
                a += d.arcAngle;
            }
        }

        return out;
    },

    /**
     * FUNCIÃ“N COMPLETA: Ajusta longitudes para evitar solapamientos
     * Esta es la versiÃ³n completa del original
     */
    ajustarLongitudesParaEvitarSolapes(dims, grow) {
        const G = typeof grow === "number" ? grow : CONFIG.overlapGrowUnits;
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
                const ex = cx + out[i].length * dir.x;
                const ey = cy + out[i].length * dir.y;
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
                            if (
                                lastDir &&
                                lastIdxPrev >= 0 &&
                                lastIdxDims >= 0
                            ) {
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
                            if (
                                lastDir &&
                                lastIdxPrev >= 0 &&
                                lastIdxDims >= 0
                            ) {
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

            const dir = {
                x: Math.cos(deg2rad(ang)),
                y: Math.sin(deg2rad(ang)),
            };
            const nx = cx + out[i].length * dir.x;
            const ny = cy + out[i].length * dir.y;
            const horiz = isH(ang);
            prev.push({ x1: cx, y1: cy, x2: nx, y2: ny, horiz, y: cy, x: cx });
            lastDir = dir;
            lastIdxPrev = prev.length - 1;
            lastIdxDims = i;
            cx = nx;
            cy = ny;
        }

        return out;
    },

    /**
     * Genera path SVG desde dimensiones
     */
    buildSvgPathFromDims(
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
                const nx = x + d.length * Math.cos(rad(ang));
                const ny = y + d.length * Math.sin(rad(ang));
                move();
                const p = map(nx, ny);
                dStr += " L " + p.x + " " + p.y;
                x = nx;
                y = ny;
                continue;
            }

            if (d.type === "arc") {
                const cx = x + d.radius * Math.cos(rad(ang + 90));
                const cy = y + d.radius * Math.sin(rad(ang + 90));
                const start = Math.atan2(y - cy, x - cx);
                const end = start + rad(d.arcAngle);
                const ex = cx + d.radius * Math.cos(end);
                const ey = cy + d.radius * Math.sin(end);
                const absAng = Math.abs(d.arcAngle) % 360;

                move();

                if (absAng < 1e-6 || Math.abs(d.arcAngle) >= 359.9) {
                    const midAng = start + Math.sign(d.arcAngle) * Math.PI;
                    const mx = cx + d.radius * Math.cos(midAng);
                    const my = cy + d.radius * Math.sin(midAng);
                    const pMid = map(mx, my);
                    const pEnd = map(x, y);
                    const R = d.radius * scale;
                    const sweep = d.arcAngle >= 0 ? 1 : 0;

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
                const R = d.radius * scale;
                const largeArc = absAng > 180 ? 1 : 0;
                const sweep = d.arcAngle >= 0 ? 1 : 0;

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
    },
};

// Exponer funciones para compatibilidad
const scaleDims = GeometryUtils.scaleDims.bind(GeometryUtils);
const extraerDimensiones = GeometryUtils.extraerDimensiones.bind(GeometryUtils);
const computePathPoints = GeometryUtils.computePathPoints.bind(GeometryUtils);
const computeLineSegments =
    GeometryUtils.computeLineSegments.bind(GeometryUtils);
const combinarRectasConCeros =
    GeometryUtils.combinarRectasConCeros.bind(GeometryUtils);
const getTurnVertices = GeometryUtils.getTurnVertices.bind(GeometryUtils);
const ajustarLongitudesParaEvitarSolapes =
    GeometryUtils.ajustarLongitudesParaEvitarSolapes.bind(GeometryUtils);
const buildSvgPathFromDims =
    GeometryUtils.buildSvgPathFromDims.bind(GeometryUtils);

// ================================================================================
// 5. SISTEMA DE COLISIONES
// ================================================================================

const CollisionUtils = {
    /**
     * Inicializa arrays de colisiones
     */
    init() {
        window.__placedLetterBoxes = [];
        window.__figBoxesGroup = [];
        window.__dimBoxesGroup = [];
        window.__angleBoxesGroup = [];
        window.__legendBoxesGroup = [];
    },

    /**
     * Aproxima tamaÃ±o de caja de texto
     */
    approxTextBox(text, size) {
        const s = size || 12;
        return { w: (text ? text.length : 0) * s * 0.55, h: s };
    },

    /**
     * Verifica si dos rectÃ¡ngulos se solapan
     */
    rectsOverlap(a, b, m) {
        const mm = m || 0;
        return !(
            a.right + mm < b.left ||
            a.left - mm > b.right ||
            a.bottom + mm < b.top ||
            a.top - mm > b.bottom
        );
    },
};

// Exponer para compatibilidad
const approxTextBox = CollisionUtils.approxTextBox.bind(CollisionUtils);
const rectsOverlap = CollisionUtils.rectsOverlap.bind(CollisionUtils);

// ================================================================================
// 6. FORMATEO Y AGRUPACIÃ“N DE COTAS
// ================================================================================

const DimensionUtils = {
    /**
     * Formatea etiqueta de dimensiÃ³n
     */
    formatDimLabel(value, opt) {
        const decimals = (opt && opt.decimals) || 0;
        const step = (opt && opt.step) || null;
        let v = Number(value || 0);
        if (step && step > 0) v = Math.round(v / step) * step;
        return v.toFixed(decimals).replace(/\.0+$/, "");
    },

    /**
     * DirecciÃ³n canÃ³nica normalizada
     */
    canonicalDir(dx, dy) {
        const L = Math.hypot(dx, dy) || 1;
        let ux = dx / L;
        let uy = dy / L;
        if (uy < -1e-9 || (Math.abs(uy) <= 1e-9 && ux < 0)) {
            ux = -ux;
            uy = -uy;
        }
        return { ux, uy };
    },

    /**
     * Genera clave de direcciÃ³n
     */
    dirKey(dx, dy, prec) {
        const p = prec || 1e-2;
        const d = this.canonicalDir(dx, dy);
        const qx = Math.round(d.ux / p) * p;
        const qy = Math.round(d.uy / p) * p;
        return qx + "|" + qy;
    },

    /**
     * FUNCIÃ“N COMPLETA: Agrupa segmentos por direcciÃ³n y etiqueta
     */
    agruparPorDireccionYEtiquetaRobusto(segsAdj, segsOrig, opt) {
        const dirPrecision = (opt && opt.dirPrecision) || 1e-2;
        const labelFormat = (opt && opt.labelFormat) || {
            decimals: 0,
            step: null,
        };
        const buckets = new Map();

        for (let i = 0; i < segsOrig.length; i++) {
            const s = segsOrig[i];
            const dx = s.end.x - s.start.x;
            const dy = s.end.y - s.start.y;
            const key = this.dirKey(dx, dy, dirPrecision);
            const arr = buckets.get(key) || [];
            arr.push(s.length || Math.hypot(dx, dy));
            buckets.set(key, arr);
        }

        const seen = new Set();
        const res = [];

        for (let i = 0; i < segsAdj.length; i++) {
            const s = segsAdj[i];
            const dx = s.end.x - s.start.x;
            const dy = s.end.y - s.start.y;
            const key = this.dirKey(dx, dy, dirPrecision);
            const candidates = buckets.get(key) || [];
            const adjLen = Math.hypot(dx, dy);
            let chosen = adjLen;

            if (candidates.length) {
                let best = candidates[0];
                let bestD = Math.abs(best - adjLen);
                for (let j = 1; j < candidates.length; j++) {
                    const d = Math.abs(candidates[j] - adjLen);
                    if (d < bestD) {
                        best = candidates[j];
                        bestD = d;
                    }
                }
                chosen = best;
            }

            const label = this.formatDimLabel(chosen, labelFormat);
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
    },
};

// Exponer para compatibilidad
const formatDimLabel = DimensionUtils.formatDimLabel.bind(DimensionUtils);
const canonicalDir = DimensionUtils.canonicalDir.bind(DimensionUtils);
const dirKey = DimensionUtils.dirKey.bind(DimensionUtils);
const agruparPorDireccionYEtiquetaRobusto =
    DimensionUtils.agruparPorDireccionYEtiquetaRobusto.bind(DimensionUtils);

// ================================================================================
// 7. SISTEMA DE LAYOUT (MASONRY)
// ================================================================================

const LayoutSystem = {
    /**
     * Mide figura en modelo y determina rotaciÃ³n
     */
    medirFiguraEnModelo(dims) {
        const pts = computePathPoints(dims);

        let minX = Math.min(...pts.map((p) => p.x));
        let maxX = Math.max(...pts.map((p) => p.x));
        let minY = Math.min(...pts.map((p) => p.y));
        let maxY = Math.max(...pts.map((p) => p.y));

        const cx = (minX + maxX) / 2;
        const cy = (minY + maxY) / 2;

        const needsRotate = maxY - minY > maxX - minX;
        const rotDeg = needsRotate ? -90 : 0;

        const rot = pts.map((p) => rotatePoint(p, cx, cy, rotDeg));

        minX = Math.min(...rot.map((p) => p.x));
        maxX = Math.max(...rot.map((p) => p.x));
        minY = Math.min(...rot.map((p) => p.y));
        maxY = Math.max(...rot.map((p) => p.y));

        return {
            rotDeg,
            w: Math.max(1, maxX - minX),
            h: Math.max(1, maxY - minY),
            cxModel: cx,
            cyModel: cy,
            midX: (minX + maxX) / 2,
            midY: (minY + maxY) / 2,
            ptsRot: rot,
        };
    },

    /**
     * Asigna elementos a columnas (First Fit Decreasing)
     */
    assignColumnsFFD(items, k, gapRow) {
        const cols = Array.from({ length: k }, () => ({
            sumH: 0,
            maxW: 0,
            items: [],
        }));

        const order = [...items.keys()].sort((a, b) => items[b].h - items[a].h);

        for (const idx of order) {
            let best = 0;
            let bestVal = Infinity;

            for (let c = 0; c < k; c++) {
                const col = cols[c];
                const val =
                    col.sumH +
                    (col.items.length > 0 ? gapRow : 0) +
                    items[idx].h;

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
    },

    /**
     * Planifica layout masonry Ã³ptimo
     */
    planMasonryOptimal(medidas, svgW, svgH, opts = {}) {
        const padding = opts.padding ?? 10;
        const gapCol = opts.gapCol ?? 10;
        const gapRow = opts.gapRow ?? 8;
        const kMax = Math.max(1, Math.min(medidas.length, opts.kMax ?? 4));

        const anchoUsable = Math.max(10, svgW - 2 * padding);
        const altoUsable = Math.max(
            10,
            svgH - 2 * padding - CONFIG.dimRingMargin
        );

        let best = { S: 0, k: 1, cols: null };

        for (let k = 1; k <= kMax; k++) {
            const cols = this.assignColumnsFFD(medidas, k, gapRow);

            const sumWCols =
                cols.reduce((a, c) => a + c.maxW, 0) + gapCol * (k - 1);
            const maxHCols = Math.max(...cols.map((c) => c.sumH));

            if (sumWCols <= 0 || maxHCols <= 0) continue;

            const S = Math.max(
                0.01,
                Math.min(anchoUsable / sumWCols, altoUsable / maxHCols)
            );

            if (S > best.S) {
                best = { S, k, cols };
            }
        }

        const widthsEsc = best.cols.map((c) => c.maxW * best.S);
        const totalW =
            widthsEsc.reduce((a, w) => a + w, 0) + (best.k - 1) * gapCol;

        let xStart = (svgW - totalW) / 2;
        const centersX = [];

        for (let c = 0; c < best.k; c++) {
            const w = widthsEsc[c];
            centersX[c] = xStart + w / 2;
            xStart += w + gapCol;
        }

        const centersYByCol = [];
        for (let c = 0; c < best.k; c++) {
            const col = best.cols[c];
            const hEscTotal =
                col.items.reduce((a, idx) => a + medidas[idx].h * best.S, 0) +
                (col.items.length - 1) * gapRow;

            let y = (svgH - hEscTotal) / 2;
            centersYByCol[c] = [];

            for (let i = 0; i < col.items.length; i++) {
                const idx = col.items[i];
                const hEsc = medidas[idx].h * best.S;
                centersYByCol[c].push(y + hEsc / 2);
                y += hEsc + gapRow;
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
    },
};

// Exponer para compatibilidad
const medirFiguraEnModelo = LayoutSystem.medirFiguraEnModelo.bind(LayoutSystem);
const assignColumnsFFD = LayoutSystem.assignColumnsFFD.bind(LayoutSystem);
const planMasonryOptimal = LayoutSystem.planMasonryOptimal.bind(LayoutSystem);

// ================================================================================
// 8. RENDERIZADO DE ELEMENTOS
// ================================================================================

const RenderUtils = {
    /**
     * Convierte Ã­ndice a letras (A, B, C, ..., AA, AB, ...)
     */
    indexToLetters(n) {
        let s = "";
        let i = Number(n) || 0;

        while (i >= 0) {
            const r = i % 26;
            s = String.fromCharCode(65 + r) + s;
            i = Math.floor(i / 26) - 1;
        }

        return s;
    },

    /**
     * Dibuja leyenda en esquina inferior izquierda
     */
    drawLegendBottomLeft(svg, entries, width, height) {
        if (!entries || !entries.length) return;

        const gap = CONFIG.legend.gap;
        const size = CONFIG.text.sizeLegend;
        const lineH = size + gap;

        const lines = entries.map(
            (e) => (e.letter ? e.letter + " " : "") + (e.text || "")
        );

        const totalH = size * lines.length + gap * (lines.length - 1);
        const x = CONFIG.legend.padX;
        let y = height - CONFIG.legend.padY - totalH + size / 2;

        for (let i = 0; i < lines.length; i++) {
            const text = lines[i];

            const t = document.createElementNS(
                "http://www.w3.org/2000/svg",
                "text"
            );
            t.setAttribute("x", x);
            t.setAttribute("y", y);
            t.setAttribute("fill", CONFIG.colors.barsText);
            t.setAttribute("font-size", size);
            t.setAttribute("text-anchor", "start");
            t.setAttribute("alignment-baseline", "middle");
            t.style.pointerEvents = "none";
            t.textContent = text;
            svg.appendChild(t);

            const w = approxTextBox(text, size).w;
            window.__legendBoxesGroup.push({
                left: x,
                right: x + w,
                top: y - size / 2,
                bottom: y + size / 2,
            });

            y += lineH;
        }
    },
};

// Exponer para compatibilidad
const indexToLetters = RenderUtils.indexToLetters.bind(RenderUtils);
const drawLegendBottomLeft = RenderUtils.drawLegendBottomLeft.bind(RenderUtils);

// ================================================================================
// 9. SCRIPT PRINCIPAL - MANTIENE TODA LA FUNCIONALIDAD ORIGINAL
// ================================================================================

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

        const ancho = 600;
        const alto = 150;
        const svgBg = getEstadoColorFromCSSVar(contenedor);
        const svg = crearSVG(ancho, alto, svgBg);

        // Reset reservas por grupo
        CollisionUtils.init();

        // ===== LEYENDA: preparar entradas primero, dibujarla YA para reservar espacio =====
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

            // Construir texto de coladas
            const coladas = [];
            if (elemento.coladas?.colada1)
                coladas.push(elemento.coladas.colada1);
            if (elemento.coladas?.colada2)
                coladas.push(elemento.coladas.colada2);
            if (elemento.coladas?.colada3)
                coladas.push(elemento.coladas.colada3);
            const textColadas =
                coladas.length > 0 ? ` (${coladas.join(", ")})` : "";

            return {
                letter: indexToLetters(idx),
                text: `Ã˜${diametro} x${barras}${textColadas}`,
            };
        });
        drawLegendBottomLeft(svg, legendEntries, ancho, alto);

        // ====== MEDIR PIEZAS Y DECIDIR ESCALA POR ELEMENTO ======
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
            const isSmall = maxLinear <= CONFIG.autoScale.smallThreshold;
            const geomScale = isSmall ? CONFIG.autoScale.smallScale : 1;
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

        // ====== BUCLE DE PINTADO ======
        grupo.elementos.forEach(function (elemento, idx) {
            const { dimsNoZero, dimsScaled, medida: m } = preproc[idx];

            const loc = indexInCol.get(idx);
            const cx = plan.centersX[loc.c];
            const cy = plan.centersYByCol[loc.c][loc.j];
            const scale = plan.S;

            // BBox figura
            const ptsSvg = m.ptsRot.map((pt) => ({
                x: cx + (pt.x - m.midX) * scale,
                y: cy + (pt.y - m.midY) * scale,
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
            window.__figBoxesGroup.push({ ...figBox });

            // Path visible (con recrecimiento)
            const dimsAdjForDraw = ajustarLongitudesParaEvitarSolapes(
                dimsScaled,
                CONFIG.overlapGrowUnits
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
            const pathEl = agregarPathD(
                svg,
                dPath,
                CONFIG.colors.figureLine,
                2
            );

            // ======== ÃNGULOS (FUNCIÃ“N COMPLETA) ========
            (function drawTurnAngles() {
                const turns = getTurnVertices(dimsScaled);

                function shouldShow(deg) {
                    return (
                        Math.abs(Math.abs(deg) - 90) >=
                        CONFIG.angles.toleranceDeg
                    );
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
                    const vPrev = rotVec(t.prevDir, m.rotDeg);
                    const vNext = rotVec(t.nextDir, m.rotDeg);
                    const aStart = Math.atan2(vPrev.y, vPrev.x);
                    const aEnd = aStart + rad(t.angleDeg);
                    const figSpan = Math.min(
                        figBox.right - figBox.left,
                        figBox.bottom - figBox.top
                    );
                    let R = clampR(0.12 * figSpan);
                    const x1 = P.x + R * Math.cos(aStart);
                    const y1 = P.y + R * Math.sin(aStart);
                    const x2 = P.x + R * Math.cos(aEnd);
                    const y2 = P.y + R * Math.sin(aEnd);
                    const absAng = Math.abs(t.angleDeg);
                    const largeArc = absAng > 180 ? 1 : 0;
                    const sweep = t.angleDeg >= 0 ? 1 : 0;

                    const arc = document.createElementNS(
                        "http://www.w3.org/2000/svg",
                        "path"
                    );
                    arc.setAttribute(
                        "d",
                        `M ${x1} ${y1} A ${R} ${R} 0 ${largeArc} ${sweep} ${x2} ${y2}`
                    );
                    arc.setAttribute("stroke", CONFIG.colors.angleArc);
                    arc.setAttribute("stroke-width", "2");
                    arc.setAttribute("fill", "none");
                    arc.style.pointerEvents = "none";
                    svg.appendChild(arc);

                    // Bisectriz interior
                    let bx = vPrev.x + vNext.x;
                    let by = vPrev.y + vNext.y;
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
                            .replace(/\.0+$/, "") + "Â°";
                    const tb = approxTextBox(label, CONFIG.text.sizeDim);

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
                        let off = CONFIG.angles.labelOffset;
                        off <= CONFIG.angles.labelMaxOffset && !placed;
                        off += CONFIG.spacing.labelStep
                    ) {
                        for (
                            let k = 0;
                            k < CONFIG.angles.labelSweepDeg.length && !placed;
                            k++
                        ) {
                            const dAng = CONFIG.angles.labelSweepDeg[k];
                            const dir = rotVec({ x: bx, y: by }, dAng);
                            const lx = P.x + dir.x * (R + off);
                            const ly = P.y + dir.y * (R + off);
                            const box = makeBox(lx, ly);

                            const out =
                                box.top < 0 ||
                                box.bottom > alto ||
                                box.left < 0 ||
                                box.right > ancho;
                            if (out) continue;

                            const collideFig = window.__figBoxesGroup.some(
                                (b) =>
                                    rectsOverlap(
                                        b,
                                        box,
                                        CONFIG.spacing.labelClearance
                                    )
                            );
                            if (collideFig) continue;

                            const collideDims = (
                                window.__dimBoxesGroup || []
                            ).some((b) =>
                                rectsOverlap(
                                    b,
                                    box,
                                    CONFIG.spacing.labelClearance
                                )
                            );
                            if (collideDims) continue;

                            const collideAngles = (
                                window.__angleBoxesGroup || []
                            ).some((b) =>
                                rectsOverlap(
                                    b,
                                    box,
                                    CONFIG.spacing.labelClearance
                                )
                            );
                            if (collideAngles) continue;

                            const collideLetters = (
                                window.__placedLetterBoxes || []
                            ).some((b) =>
                                rectsOverlap(
                                    b,
                                    box,
                                    CONFIG.spacing.labelClearance
                                )
                            );
                            if (collideLetters) continue;

                            const collideLegend = (
                                window.__legendBoxesGroup || []
                            ).some((b) =>
                                rectsOverlap(
                                    b,
                                    box,
                                    CONFIG.spacing.labelClearance
                                )
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
                    txt.setAttribute("fill", CONFIG.colors.valorCota);
                    txt.setAttribute("font-size", CONFIG.text.sizeDim);
                    txt.setAttribute("text-anchor", "middle");
                    txt.setAttribute("alignment-baseline", "middle");
                    txt.style.cursor = "pointer";
                    txt.textContent = label;
                    svg.appendChild(txt);

                    txt.addEventListener("mouseenter", () => {
                        arc.setAttribute("stroke", CONFIG.colors.angleArcHover);
                        arc.setAttribute("stroke-width", "3");
                        arc.style.filter =
                            "drop-shadow(0 0 2px rgba(255,69,0,0.7))";
                    });
                    txt.addEventListener("mouseleave", () => {
                        arc.setAttribute("stroke", CONFIG.colors.angleArc);
                        arc.setAttribute("stroke-width", "2");
                        arc.style.filter = "none";
                    });
                });
            })();

            // Hitbox de interacciÃ³n
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
                "Click: dividir Â· Ctrl/Shift/âŒ˜+Click o botÃ³n derecho: info"
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
            // COTAS (LONGITUDES) - FUNCIÃ“N COMPLETA
            // ===================
            const placedBoxes = [];
            const segsAdj = computeLineSegments(dimsAdjForDraw);
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

                const dx = p2.x - p1.x;
                const dy = p2.y - p1.y;
                const L = Math.hypot(dx, dy) || 1;
                const tx = dx / L;
                const ty = dy / L;
                const nx = dy / L;
                const ny = -dx / L;
                const mx = (p1.x + p2.x) / 2;
                const my = (p1.y + p2.y) / 2;
                const baseLX = mx + nx * CONFIG.dimensions.offset;
                const baseLY = my + ny * CONFIG.dimensions.offset;

                const tb = approxTextBox(label, CONFIG.text.sizeDim);
                const tw = tb.w;
                const th = tb.h;

                function makeBox(cx0, cy0) {
                    return {
                        left: cx0 - tw / 2,
                        right: cx0 + tw / 2,
                        top: cy0 - th / 2,
                        bottom: cy0 + th / 2,
                    };
                }

                const maxShift = L * CONFIG.dimensions.tangMaxFrac;
                let bestLX = baseLX;
                let bestLY = baseLY;

                for (
                    let step = 0;
                    step <= Math.ceil(maxShift / CONFIG.dimensions.tangStep);
                    step++
                ) {
                    const dir = step % 2 === 0 ? 1 : -1;
                    const mult = Math.ceil(step / 2);
                    const shift = dir * mult * CONFIG.dimensions.tangStep;
                    if (Math.abs(shift) > maxShift) continue;

                    const lx = baseLX + tx * shift;
                    const ly = baseLY + ty * shift;
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
                        (b) =>
                            rectsOverlap(
                                b,
                                labelBox,
                                CONFIG.spacing.labelClearance
                            )
                    );
                    if (collideAngles) continue;

                    const collideLegend = (
                        window.__legendBoxesGroup || []
                    ).some((b) =>
                        rectsOverlap(b, labelBox, CONFIG.spacing.labelClearance)
                    );
                    if (collideLegend) continue;

                    const collideOth = placedBoxes.some((b) =>
                        rectsOverlap(b, labelBox, CONFIG.spacing.labelClearance)
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
                hl.setAttribute("stroke", CONFIG.colors.dimHighlight);
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
                txt.setAttribute("fill", CONFIG.colors.valorCota);
                txt.setAttribute("font-size", CONFIG.text.sizeDim);
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
            // LETRA (FUNCIÃ“N COMPLETA)
            // =========================
            (function placeLetter() {
                const letter = indexToLetters(idx);
                const letterSize = CONFIG.text.sizeLetter;
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
                    for (
                        let off = 0;
                        off <= maxSpread;
                        off += CONFIG.spacing.labelStep
                    ) {
                        const dir = off % 2 === 0 ? 1 : -1;
                        const mult = Math.ceil(off / 2);
                        const dy = dir * mult * CONFIG.spacing.labelStep;
                        const ly = Math.max(
                            tb.h / 2,
                            Math.min(alto - tb.h / 2, baseY + dy)
                        );
                        const lx = xPos;
                        const box = makeBoxAt(lx, ly);

                        const collideFig = window.__figBoxesGroup.some((b) =>
                            rectsOverlap(b, box, CONFIG.spacing.labelClearance)
                        );
                        if (collideFig) continue;

                        const collideDims = (window.__dimBoxesGroup || []).some(
                            (b) =>
                                rectsOverlap(
                                    b,
                                    box,
                                    CONFIG.spacing.labelClearance
                                )
                        );
                        if (collideDims) continue;

                        const collideAngles = (
                            window.__angleBoxesGroup || []
                        ).some((b) =>
                            rectsOverlap(b, box, CONFIG.spacing.labelClearance)
                        );
                        if (collideAngles) continue;

                        const collideLegend = (
                            window.__legendBoxesGroup || []
                        ).some((b) =>
                            rectsOverlap(b, box, CONFIG.spacing.labelClearance)
                        );
                        if (collideLegend) continue;

                        const collidePrev = (
                            window.__placedLetterBoxes || []
                        ).some((b) =>
                            rectsOverlap(b, box, CONFIG.spacing.labelClearance)
                        );
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

                let columnsTried = 0;
                let xStep = 8;
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
                t.setAttribute("fill", CONFIG.colors.barsText);
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
