document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modal-dibujo");
    const cerrarModal = document.getElementById("cerrar-modal");
    const canvasModal = document.getElementById("canvas-dibujo");

    /* ******************************************************************
     * Constantes y configuración
     ****************************************************************** */
    const FIGURE_LINE_COLOR = "rgba(0, 0, 0, 0.8)";
    const VALOR_COTA_COLOR = "rgba(0, 0, 0, 1)";
    const BARS_TEXT_COLOR = "rgba(0, 0, 0, 1)";

    const marginX = 10;
    const marginY = 10;

    const OVERLAP_GROW_UNITS = 0.6;

    const SIZE_MAIN_TEXT = 18;
    const SIZE_DIM_TEXT = 14;
    const DIM_LINE_OFFSET = 16;
    const DIM_LABEL_LIFT = 10;
    const DIM_OFFSET = 10;
    const DIM_TANG_STEP = 6;
    const DIM_TANG_MAX_FRAC = 0.45;

    const LABEL_CLEARANCE = 6;
    const LABEL_STEP = 4;

    const TOP_BAND_HEIGHT = 26;
    const TOP_BAND_GAP = 14;
    const TOP_BAND_PAD_X = 6;

    const SIDE_BAND_GAP = 12;
    const SIDE_BAND_PAD = 6;

    const DIM_RING_MARGIN =
        DIM_LINE_OFFSET + SIZE_DIM_TEXT + DIM_LABEL_LIFT + 6;

    const SMALL_DIM_THRESHOLD = 50;
    const SMALL_DIM_SCALE = 2;

    const ANGLE_TOL_DEG = 0.75;
    const ANGLE_LABEL_OFFSET = 14;
    const ANGLE_LABEL_MAX_OFFSET = 60;
    const ANGLE_LABEL_SWEEP_DEG = [0, 10, -10, 20, -20, 30, -30];

    /* ******************************************************************
     * Funciones auxiliares - Geometría
     ****************************************************************** */

    function rad(deg) {
        return (deg * Math.PI) / 180;
    }

    function deg(rad) {
        return (rad * 180) / Math.PI;
    }

    function normalizeDeg180(a) {
        return ((a % 180) + 180) % 180;
    }

    // Extrae dimensiones del string y retorna array de objetos {type, length/radius/angle}
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

    // Calcula los puntos del path según las dimensiones
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

    // Escala las dimensiones si la pieza es pequeña
    function scaleDims(dims, factor) {
        if (!factor || factor === 1) return dims.map((d) => ({ ...d }));
        return dims.map((d) => {
            if (d.type === "line")
                return { ...d, length: (d.length || 0) * factor };
            if (d.type === "arc")
                return { ...d, radius: (d.radius || 0) * factor };
            return { ...d };
        });
    }

    // Combina rectas consecutivas
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

    // Ajusta longitudes para evitar solapes (como en canvasMaquina.js)
    function ajustarLongitudesParaEvitarSolapes(dims, grow) {
        const G = typeof grow === "number" ? grow : OVERLAP_GROW_UNITS;
        const out = dims.map((d) => Object.assign({}, d));
        let cx = 0, cy = 0, ang = 0;
        const prev = [];
        let lastDir = null, lastIdxPrev = -1, lastIdxDims = -1;
        const EPS = 1e-7;

        function isH(a) {
            return Math.abs(Math.sin(rad(a))) < 1e-12;
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
                const cx0 = cx + d.radius * Math.cos(rad(ang + 90));
                const cy0 = cy + d.radius * Math.sin(rad(ang + 90));
                const start = Math.atan2(cy - cy0, cx - cx0);
                const end = start + rad(d.arcAngle);
                cx = cx0 + d.radius * Math.cos(end);
                cy = cy0 + d.radius * Math.sin(end);
                ang += d.arcAngle;
                lastDir = null;
                continue;
            }

            function tryResolve() {
                const dir = { x: Math.cos(rad(ang)), y: Math.sin(rad(ang)) };
                const ex = cx + out[i].length * dir.x;
                const ey = cy + out[i].length * dir.y;
                const horiz = isH(ang);

                for (let k = 0; k < prev.length; k++) {
                    const s = prev[k];
                    if (horiz && s.horiz && Math.abs(cy - s.y) < EPS) {
                        if (overlap(Math.min(cx, ex), Math.max(cx, ex), Math.min(s.x1, s.x2), Math.max(s.x1, s.x2))) {
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
                        if (overlap(Math.min(cy, ey), Math.max(cy, ey), Math.min(s.y1, s.y2), Math.max(s.y1, s.y2))) {
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

            const dir = { x: Math.cos(rad(ang)), y: Math.sin(rad(ang)) };
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
    }

    // Rotar un punto
    function rotatePoint(p, cx, cy, degAng) {
        const r = rad(degAng),
            c = Math.cos(r),
            s = Math.sin(r);
        const dx = p.x - cx,
            dy = p.y - cy;
        return { x: cx + dx * c - dy * s, y: cy + dx * s + dy * c };
    }

    /* ******************************************************************
     * Funciones auxiliares - SVG
     ****************************************************************** */

    function crearSVG(width, height, bgColor) {
        const svg = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "svg"
        );
        svg.setAttribute("viewBox", "0 0 " + width + " " + height);
        svg.setAttribute("preserveAspectRatio", "xMidYMid meet");
        svg.style.width = "100%";
        svg.style.height = "100%";
        svg.style.display = "block";
        svg.style.background = bgColor || "transparent";
        svg.style.shapeRendering = "geometricPrecision";
        svg.style.textRendering = "optimizeLegibility";
        svg.style.boxSizing = "border-box";
        return svg;
    }

    function agregarTexto(svg, x, y, texto, color, size, anchor) {
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
    }

    function agregarPathD(svg, d, color, ancho) {
        const path = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "path"
        );
        path.setAttribute("d", d);
        path.setAttribute("stroke", color || FIGURE_LINE_COLOR);
        path.setAttribute("fill", "none");
        path.setAttribute("stroke-width", ancho || 2);
        path.setAttribute("vector-effect", "non-scaling-stroke");
        svg.appendChild(path);
        return path;
    }

    // Función para crear key de dirección canónica (como en canvasMaquina.js)
    function canonicalDir(dx, dy) {
        const L = Math.hypot(dx, dy) || 1;
        let ux = dx / L, uy = dy / L;
        if (uy < -1e-9 || (Math.abs(uy) <= 1e-9 && ux < 0)) {
            ux = -ux;
            uy = -uy;
        }
        return { ux, uy };
    }

    function dirKey(dx, dy, prec = 1e-2) {
        const d = canonicalDir(dx, dy);
        const qx = Math.round(d.ux / prec) * prec;
        const qy = Math.round(d.uy / prec) * prec;
        return qx + "|" + qy;
    }

    // Agrupa segmentos por dirección para que paralelos compartan una acotación
    function agruparSegmentosPorDireccion(segmentos) {
        const buckets = new Map();
        const seen = new Set();
        const result = [];

        for (let i = 0; i < segmentos.length; i++) {
            const s = segmentos[i];
            const dx = s.p2.x - s.p1.x;
            const dy = s.p2.y - s.p1.y;
            const key = dirKey(dx, dy);
            const label = Math.round(s.length).toString();
            const k2 = key + "|" + label;

            if (seen.has(k2)) continue; // Ya existe esta dirección + longitud
            seen.add(k2);

            result.push({
                p1: s.p1,
                p2: s.p2,
                label: label,
                length: s.length
            });
        }

        return result;
    }

    // Dibuja una acotación sin rotar el texto (como en canvasMaquina.js)
    function dibujarAcotacion(svg, p1, p2, texto, fontSize) {
        const dx = p2.x - p1.x;
        const dy = p2.y - p1.y;
        const L = Math.hypot(dx, dy) || 1;

        // Vector tangente y normal
        const tx = dx / L;
        const ty = dy / L;
        const nx = dy / L;
        const ny = -dx / L;

        // Punto medio
        const mx = (p1.x + p2.x) / 2;
        const my = (p1.y + p2.y) / 2;

        // Offset perpendicular
        const dimOffset = fontSize * 0.8;
        const lx = mx + nx * dimOffset;
        const ly = my + ny * dimOffset;

        // Crear texto SIN rotar (como en canvasMaquina.js)
        const txt = document.createElementNS("http://www.w3.org/2000/svg", "text");
        txt.setAttribute("x", lx);
        txt.setAttribute("y", ly);
        txt.setAttribute("fill", "black");
        txt.setAttribute("font-size", fontSize);
        txt.setAttribute("font-weight", "bold");
        txt.setAttribute("text-anchor", "middle");
        txt.setAttribute("alignment-baseline", "middle");
        txt.style.pointerEvents = "none";
        txt.textContent = texto;

        svg.appendChild(txt);
    }

    // Construye el path SVG desde las dimensiones
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
                } else {
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
                }
                x = ex;
                y = ey;
                ang += d.arcAngle;
            }
        }
        return dStr;
    }

    // Mide la figura en el modelo
    function medirFiguraEnModelo(dims) {
        const pts = computePathPoints(dims);
        if (pts.length === 0) return null;

        let minX = pts[0].x,
            maxX = pts[0].x;
        let minY = pts[0].y,
            maxY = pts[0].y;

        for (const p of pts) {
            minX = Math.min(minX, p.x);
            maxX = Math.max(maxX, p.x);
            minY = Math.min(minY, p.y);
            maxY = Math.max(maxY, p.y);
        }

        const w = maxX - minX;
        const h = maxY - minY;
        const midX = (minX + maxX) / 2;
        const midY = (minY + maxY) / 2;
        const cxModel = 0;
        const cyModel = 0;

        let rotDeg = 0;
        if (w < h) rotDeg = 90;

        const ptsRot = pts.map((p) => rotatePoint(p, cxModel, cyModel, rotDeg));
        let rMinX = ptsRot[0].x,
            rMaxX = ptsRot[0].x;
        let rMinY = ptsRot[0].y,
            rMaxY = ptsRot[0].y;

        for (const p of ptsRot) {
            rMinX = Math.min(rMinX, p.x);
            rMaxX = Math.max(rMaxX, p.x);
            rMinY = Math.min(rMinY, p.y);
            rMaxY = Math.max(rMaxY, p.y);
        }

        // Calcular el centro de la figura DESPUÉS de rotar
        const midXRot = (rMinX + rMaxX) / 2;
        const midYRot = (rMinY + rMaxY) / 2;

        return {
            w,
            h,
            minX,
            maxX,
            minY,
            maxY,
            midX,
            midY,
            midXRot,
            midYRot,
            cxModel,
            cyModel,
            rotDeg,
            wRot: rMaxX - rMinX,
            hRot: rMaxY - rMinY,
            ptsRot,
        };
    }

    /* ******************************************************************
     * Función principal para dibujar la figura usando SVG
     ****************************************************************** */
    function dibujarFigura(containerId, dimensionesStr, peso, diametro, barras) {
        console.log("🎨 dibujarFigura llamada:", {
            containerId,
            dimensionesStr,
            peso,
            diametro,
            barras,
        });

        let contenedor = document.getElementById(containerId);
        if (!contenedor) {
            console.error("❌ Contenedor no encontrado:", containerId);
            return;
        }

        // Variables para dimensiones
        let ancho, alto;

        // 🔄 Si el elemento es un canvas, reemplazarlo por un div
        if (contenedor.tagName.toLowerCase() === "canvas") {
            console.log("🔄 Detectado canvas, reemplazando por div contenedor");

            // Obtener dimensiones del canvas ANTES de reemplazarlo
            ancho = contenedor.width || parseInt(contenedor.style.width) || 240;
            alto = contenedor.height || parseInt(contenedor.style.height) || 120;

            // Obtener estilos computados del canvas
            const computedStyles = window.getComputedStyle(contenedor);

            const div = document.createElement("div");
            div.id = containerId;

            // Aplicar estilos copiados del CSS .elemento-drag canvas
            div.style.width = "100%"; // Igual que el canvas original
            div.style.height = computedStyles.height || alto + "px";
            div.style.display = "block";
            div.style.margin = "0";
            div.style.padding = "0";
            div.style.boxSizing = "border-box";
            div.style.overflow = "hidden";
            div.style.border = computedStyles.border || "1px solid #e5e7eb";
            div.style.borderRadius = computedStyles.borderRadius || "4px";
            div.style.background = "white";

            // Copiar clases del canvas
            div.className = contenedor.className;

            contenedor.parentNode.replaceChild(div, contenedor);
            contenedor = div;

            // Obtener dimensiones reales después del reemplazo
            const rect = div.getBoundingClientRect();
            ancho = rect.width || ancho;
            alto = rect.height || alto;
        } else {
            // Si no es canvas, obtener dimensiones del contenedor
            const rect = contenedor.getBoundingClientRect();
            console.log("🔍 getBoundingClientRect:", rect);
            console.log("🔍 contenedor.style:", { width: contenedor.style.width, height: contenedor.style.height });
            ancho = rect.width > 0 ? rect.width : parseInt(contenedor.style.width) || 600;
            alto = rect.height > 0 ? rect.height : parseInt(contenedor.style.height) || 400;
        }

        console.log("📐 Dimensiones finales del contenedor:", { ancho, alto });

        const svg = crearSVG(ancho, alto, "white");

        // Extraer y procesar dimensiones
        const dimsRaw = extraerDimensiones(dimensionesStr);
        console.log("📐 Dimensiones extraídas:", dimsRaw);

        if (dimsRaw.length === 0) {
            console.warn("⚠️ No hay dimensiones válidas para dibujar.");
            // Mostrar mensaje en el SVG
            agregarTexto(
                svg,
                ancho / 2,
                alto / 2,
                "Sin dimensiones válidas",
                "#FF0000",
                16,
                "middle"
            );
            contenedor.innerHTML = "";
            contenedor.appendChild(svg);
            return;
        }

        const dimsNoZero = combinarRectasConCeros(dimsRaw);
        console.log("🔧 Dimensiones combinadas:", dimsNoZero);

        // Verificar si es una pieza pequeña y escalar si es necesario
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

        console.log(
            "📏 Max linear:",
            maxLinear,
            "isSmall:",
            isSmall,
            "geomScale:",
            geomScale
        );

        // Medir la figura
        const medida = medirFiguraEnModelo(dimsScaled);
        if (!medida) {
            console.warn("⚠️ No se pudo medir la figura.");
            agregarTexto(
                svg,
                ancho / 2,
                alto / 2,
                "Error al medir figura",
                "#FF0000",
                16,
                "middle"
            );
            contenedor.innerHTML = "";
            contenedor.appendChild(svg);
            return;
        }

        console.log("📊 Medida inicial:", medida);

        // Ajustar dimensiones para evitar solapes (como en canvasMaquina.js)
        const dimsAdjusted = ajustarLongitudesParaEvitarSolapes(dimsScaled, OVERLAP_GROW_UNITS);

        // Recalcular medida DESPUÉS del ajuste para centrado correcto
        const medidaAjustada = medirFiguraEnModelo(dimsAdjusted);
        console.log("📊 Medida ajustada:", medidaAjustada);

        // Evitar división por cero
        const wRot = medidaAjustada.wRot || 1;
        const hRot = medidaAjustada.hRot || 1;

        // Usar márgenes reducidos para aprovechar mejor el espacio
        const margenSeguridad = 0.15; // 15% de margen de seguridad (reducido de 25%)
        const availableWidth = ancho * (1 - margenSeguridad);
        const availableHeight = alto * (1 - margenSeguridad);

        // Calcular escala para que quepa completamente
        let scale = Math.min(availableWidth / wRot, availableHeight / hRot);

        // Para contenedores pequeños, usar escala completa sin reducción adicional
        if (ancho < 300 || alto < 150) {
            scale *= 0.95; // Reducir solo al 95% en contenedores pequeños (antes era 80%)
        }

        console.log(
            "🔍 Escala calculada:",
            scale,
            "wRot:",
            wRot,
            "hRot:",
            hRot,
            "availableWidth:",
            availableWidth,
            "availableHeight:",
            availableHeight
        );

        // Centrar exactamente en el contenedor
        const cx = ancho / 2;
        const cy = alto / 2;

        // Construir y dibujar el path de la figura con dimensiones ajustadas
        // Usar la medida ajustada para el centrado correcto
        const dPath = buildSvgPathFromDims(
            dimsAdjusted,
            medidaAjustada.cxModel,
            medidaAjustada.cyModel,
            medidaAjustada.rotDeg,
            scale,
            medidaAjustada.midXRot,
            medidaAjustada.midYRot,
            cx,
            cy
        );

        console.log("🎨 Path generado:", dPath);

        if (dPath && dPath.length > 0) {
            // Ajustar grosor de línea según tamaño del contenedor
            const lineWidth = ancho < 300 ? 1.5 : 2;
            agregarPathD(svg, dPath, FIGURE_LINE_COLOR, lineWidth);
            console.log("✅ Path dibujado correctamente con grosor:", lineWidth);

            // Añadir acotaciones solo si el contenedor es suficientemente grande
            console.log("🔍 Verificando acotaciones:", { ancho, alto, cumpleCondicion: (ancho > 150 && alto > 80) });
            if (ancho > 150 && alto > 80) {
                console.log("✅ Dibujando acotaciones...");
                const fontSize = ancho < 300 ? 8 : 10;

                // Calcular puntos transformados para las acotaciones usando la medida ajustada
                function transformPoint(px, py) {
                    const p = rotatePoint({ x: px, y: py }, medidaAjustada.cxModel, medidaAjustada.cyModel, medidaAjustada.rotDeg);
                    return {
                        x: cx + (p.x - medidaAjustada.midXRot) * scale,
                        y: cy + (p.y - medidaAjustada.midYRot) * scale,
                    };
                }

                // Recolectar segmentos AJUSTADOS (para posiciones) y ORIGINALES (para longitudes)
                const segmentosAdjusted = [];
                const segmentosOriginales = [];

                // Segmentos ajustados (posiciones correctas para evitar solapes)
                let x = 0, y = 0, ang = 0;
                for (let i = 0; i < dimsAdjusted.length; i++) {
                    const d = dimsAdjusted[i];
                    if (d.type === "line") {
                        const p1 = { x, y };
                        x += d.length * Math.cos(rad(ang));
                        y += d.length * Math.sin(rad(ang));
                        const p2 = { x, y };

                        const p1svg = transformPoint(p1.x, p1.y);
                        const p2svg = transformPoint(p2.x, p2.y);

                        segmentosAdjusted.push({ p1: p1svg, p2: p2svg, length: d.length });
                    } else if (d.type === "turn") {
                        ang += d.angle;
                    } else if (d.type === "arc") {
                        const cx0 = x + d.radius * Math.cos(rad(ang + 90));
                        const cy0 = y + d.radius * Math.sin(rad(ang + 90));
                        const start = Math.atan2(y - cy0, x - cx0);
                        const end = start + rad(d.arcAngle);
                        x = cx0 + d.radius * Math.cos(end);
                        y = cy0 + d.radius * Math.sin(end);
                        ang += d.arcAngle;
                    }
                }

                // Segmentos originales (longitudes reales sin ajustar)
                x = 0; y = 0; ang = 0;
                for (let i = 0; i < dimsNoZero.length; i++) {
                    const d = dimsNoZero[i];
                    if (d.type === "line") {
                        segmentosOriginales.push({ length: d.length });
                        x += d.length * Math.cos(rad(ang));
                        y += d.length * Math.sin(rad(ang));
                    } else if (d.type === "turn") {
                        ang += d.angle;
                    } else if (d.type === "arc") {
                        const cx0 = x + d.radius * Math.cos(rad(ang + 90));
                        const cy0 = y + d.radius * Math.sin(rad(ang + 90));
                        const start = Math.atan2(y - cy0, x - cx0);
                        const end = start + rad(d.arcAngle);
                        x = cx0 + d.radius * Math.cos(end);
                        y = cy0 + d.radius * Math.sin(end);
                        ang += d.arcAngle;
                    }
                }

                // Combinar: posiciones ajustadas + longitudes originales
                const segmentosCombinados = segmentosAdjusted.map((s, i) => ({
                    p1: s.p1,
                    p2: s.p2,
                    length: segmentosOriginales[i]?.length || s.length
                }));

                // Agrupar por dirección (paralelos comparten acotación)
                const segmentosUnicos = agruparSegmentosPorDireccion(segmentosCombinados);
                console.log("📊 Segmentos únicos para acotar:", segmentosUnicos.length, segmentosUnicos);

                // Dibujar solo las acotaciones únicas
                segmentosUnicos.forEach((s, idx) => {
                    console.log(`📏 Dibujando acotación ${idx}:`, s.label);
                    dibujarAcotacion(svg, s.p1, s.p2, s.label, fontSize);
                });
                console.log("✅ Acotaciones completadas");
            } else {
                console.log("⚠️ Contenedor muy pequeño para acotaciones");
            }
        } else {
            console.error("❌ Path vacío");
            agregarTexto(
                svg,
                ancho / 2,
                alto / 2,
                "Path vacío",
                "#FF0000",
                16,
                "middle"
            );
        }

        // Mostrar información en la esquina superior izquierda
        console.log('📝 Información a mostrar:', { peso, diametro, barras, ancho, alto });

        // Siempre mostrar si hay información disponible
        const infoSize = ancho < 300 ? 10 : 12;
        const infoMarginX = 15;
        let infoMarginY = 25;
        const lineHeight = infoSize + 8;

        // Mostrar peso
        if (peso) {
            agregarTexto(
                svg,
                infoMarginX,
                infoMarginY,
                `Peso: ${peso} kg`,
                "#333333",
                infoSize,
                "start"
            );
            infoMarginY += lineHeight;
        }

        // Mostrar diámetro
        if (diametro) {
            agregarTexto(
                svg,
                infoMarginX,
                infoMarginY,
                `Ø: ${diametro} mm`,
                "#333333",
                infoSize,
                "start"
            );
            infoMarginY += lineHeight;
        }

        // Mostrar barras
        if (barras) {
            agregarTexto(
                svg,
                infoMarginX,
                infoMarginY,
                `Barras: ${barras}`,
                "#333333",
                infoSize,
                "start"
            );
        }

        // Limpiar el contenedor y agregar el SVG
        contenedor.innerHTML = "";
        contenedor.appendChild(svg);

        console.log("✅ SVG agregado al contenedor");
    }

    window.dibujarFiguraElemento = dibujarFigura;

    /* ******************************************************************
     * Eventos: abrir y cerrar modal
     ****************************************************************** */
    document.querySelectorAll(".abrir-modal-dibujo").forEach((link) => {
        link.addEventListener("click", function (event) {
            event.preventDefault();
            const dimensiones = this.getAttribute("data-dimensiones");
            const peso = this.getAttribute("data-peso") || "N/A";
            modal.classList.remove("hidden");

            // Crear un contenedor SVG si no existe o limpiar el existente
            let svgContainer = document.getElementById("svg-dibujo-container");
            if (!svgContainer) {
                // Si el modal tiene un canvas, podemos reutilizar su contenedor padre
                // o crear uno nuevo
                const canvasElement = document.getElementById("canvas-dibujo");
                if (canvasElement && canvasElement.parentNode) {
                    svgContainer = document.createElement("div");
                    svgContainer.id = "svg-dibujo-container";
                    svgContainer.style.width = "100%";
                    svgContainer.style.height = "100%";
                    // Ocultar el canvas si existe y mostrar nuestro contenedor
                    canvasElement.style.display = "none";
                    canvasElement.parentNode.insertBefore(
                        svgContainer,
                        canvasElement
                    );
                } else {
                    // Si no hay canvas, buscar un contenedor adecuado en el modal
                    svgContainer = document.createElement("div");
                    svgContainer.id = "svg-dibujo-container";
                    svgContainer.style.width = "100%";
                    svgContainer.style.minHeight = "200px";
                    if (modal.querySelector(".modal-content")) {
                        modal
                            .querySelector(".modal-content")
                            .appendChild(svgContainer);
                    } else {
                        modal.appendChild(svgContainer);
                    }
                }
            }

            dibujarFigura("svg-dibujo-container", dimensiones, peso);
        });
    });

    if (cerrarModal) {
        cerrarModal.addEventListener("click", function () {
            modal.classList.add("hidden");
        });
    }

    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                modal.classList.add("hidden");
            }
        });
    }
});
