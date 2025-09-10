// =======================
// Configuración Global
// =======================
const FIGURE_LINE_COLOR = "rgba(0, 0, 0, 0.8)";
const LINEA_COTA_COLOR = "rgba(255, 0, 0, 0.5)";
const VALOR_COTA_COLOR = "rgba(0, 0, 0, 1)";
const BARS_TEXT_COLOR = "rgba(0, 0, 0, 1)";
const ELEMENT_TEXT_COLOR = "blue";

const marginX = 50;
const marginY = 50;
const gapSpacing = 30;
const minSlotHeight = 100;
const LEGEND_ZONE = 36; // espacio reservado inferior en cada slot para la leyenda

// === Constantes nuevas (cotas duplicadas) ===
const DUP_OFFSET_STEP_PX = 12; // escalón entre cotas duplicadas
const BASE_OFFSET_PX = 10; // offset base
const EPS = 1e-3; // tolerancia geométrica

// ----- util para claves de segmentos (independiente del sentido) -----
function _n(v) {
    return Math.round(v / EPS) * EPS;
}
function _keyForSegment(a, b) {
    const p = [_n(a.x), _n(a.y)];
    const q = [_n(b.x), _n(b.y)];
    const [s1, s2] =
        p[0] < q[0] || (p[0] === q[0] && p[1] <= q[1]) ? [p, q] : [q, p];
    return `${s1[0]},${s1[1]}|${s2[0]},${s2[1]}`;
}

// (opcional) anotar duplicados geométricos – no la usamos en la nueva lógica
function annotateDuplicateOffsets(segments) {
    const groups = new Map();
    segments.forEach((s, idx) => {
        const k = _keyForSegment(s.start, s.end);
        if (!groups.has(k)) groups.set(k, []);
        groups.get(k).push(idx);
    });
    const out = segments.map((s) => ({
        ...s,
        dupIndex: 0,
        dupCount: 1,
        extraOffsetPx: 0,
    }));
    for (const idxs of groups.values()) {
        idxs.forEach((segIdx, k) => {
            const dupIndex = k;
            out[segIdx].dupIndex = dupIndex;
            out[segIdx].dupCount = idxs.length;
            out[segIdx].extraOffsetPx = dupIndex * DUP_OFFSET_STEP_PX;
        });
    }
    return out;
}

// ===== línea de cota con desplazamiento en px =====
function drawDimensionLine(ctx, p1, p2, label, offsetPx = BASE_OFFSET_PX) {
    const dx = p2.x - p1.x,
        dy = p2.y - p1.y;
    const L = Math.hypot(dx, dy);
    if (L < 1e-6) return;

    const nx = -dy / L,
        ny = dx / L; // normal perpendicular

    const a = { x: p1.x + nx * offsetPx, y: p1.y + ny * offsetPx };
    const b = { x: p2.x + nx * offsetPx, y: p2.y + ny * offsetPx };

    ctx.save();
    ctx.strokeStyle = LINEA_COTA_COLOR;
    ctx.lineWidth = 1;

    // Línea de cota
    ctx.beginPath();
    ctx.moveTo(a.x, a.y);
    ctx.lineTo(b.x, b.y);
    ctx.stroke();

    // Patillas
    ctx.beginPath();
    ctx.moveTo(p1.x, p1.y);
    ctx.lineTo(a.x, a.y);
    ctx.moveTo(p2.x, p2.y);
    ctx.lineTo(b.x, b.y);
    ctx.stroke();

    // Texto
    ctx.font = "12px Arial";
    ctx.fillStyle = VALOR_COTA_COLOR;
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(label, (a.x + b.x) / 2, (a.y + b.y) / 2 - 12);
    ctx.restore();
}

// Agrupar por orientación de la cota
function angleBucket(p1, p2) {
    const ang = Math.atan2(p2.y - p1.y, p2.x - p1.x);
    const deg = Math.round((ang * 180) / Math.PI);
    return deg; // -180..180
}

// Distancia perpendicular firmada de un punto a la recta (p1->p2)
function signedDistanceToLine(p1, p2, pt) {
    const dx = p2.x - p1.x,
        dy = p2.y - p1.y;
    const L = Math.hypot(dx, dy) || 1;
    const nx = -dy / L,
        ny = dx / L;
    return (pt.x - p1.x) * nx + (pt.y - p1.y) * ny;
}

// ==========================================================
// Función PRINCIPAL – dibuja UN grupo en UN canvas
// ==========================================================
function dibujarGrupoEnCanvas(grupo, canvas) {
    if (!grupo || !grupo.elementos || !canvas) return;

    const parent = canvas.parentElement;
    const canvasWidth = parent.clientWidth;
    const textHeight = 60;
    const n = grupo.elementos.length;
    const canvasHeight = textHeight + n * minSlotHeight + (n - 1) * gapSpacing;

    canvas.width = canvasWidth;
    canvas.height = canvasHeight;

    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = "#fff";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    const availableSlotHeight =
        (canvasHeight - textHeight - (n - 1) * gapSpacing) / n;
    const drawableSlotHeight = Math.max(10, availableSlotHeight - LEGEND_ZONE); // alto útil para figura y cotas
    const availableWidth = canvasWidth - 2 * marginX;

    grupo.elementos.forEach((el, i) => {
        // -------- Datos del elemento --------
        const dims = extraerDimensiones(el.dimensiones || "");
        const barras = el.barras ?? 0;
        const diam = el.diametro ?? "N/A";
        const peso = el.peso ?? "N/A";

        const cX = marginX + availableWidth / 2;
        const slotTop = textHeight + i * (availableSlotHeight + gapSpacing);
        const cY = slotTop + drawableSlotHeight / 2;

        // Leyenda inferior izquierda (reservada por LEGEND_ZONE)
        ctx.font = "26px Arial";
        ctx.fillStyle = "#000";
        ctx.textAlign = "left";
        const legendText = `Ø${diam} | ${peso} | x${barras}`;
        ctx.fillText(
            legendText,
            marginX + 6,
            slotTop + availableSlotHeight - LEGEND_ZONE / 2
        );

        // Id del elemento (mantener fuera del área de dibujo)
        ctx.fillStyle = ELEMENT_TEXT_COLOR;
        ctx.textAlign = "left";
        ctx.fillText(
            `#${el.id}`,
            marginX + availableWidth + 25,
            slotTop + drawableSlotHeight - 50
        );

        // -------- Dibujo de la figura --------
        if (dims.length === 1 && dims[0].type === "arc") {
            const arc = dims[0];
            const scale = Math.min(
                availableWidth / (2 * arc.radius),
                drawableSlotHeight / (2 * arc.radius)
            );
            const R = arc.radius * scale;
            const ang = ((arc.arcAngle || 360) * Math.PI) / 180;
            ctx.save();
            ctx.beginPath();
            ctx.rect(marginX, slotTop, availableWidth, drawableSlotHeight);
            ctx.clip();
            ctx.beginPath();
            ctx.arc(cX, cY, R, 0, ang);
            ctx.strokeStyle = FIGURE_LINE_COLOR;
            ctx.lineWidth = 2;
            ctx.stroke();
            ctx.font = "12px Arial";
            ctx.fillStyle = VALOR_COTA_COLOR;
            const mid = ang / 2;
            ctx.fillText(
                `${arc.radius}r`,
                cX + (R + 10) * Math.cos(mid),
                cY + (R + 10) * Math.sin(mid)
            );
            ctx.restore();
        } else if (dims.length === 1 && dims[0].type === "line") {
            const line = dims[0];
            const scale = availableWidth / Math.abs(line.length);
            const L = Math.abs(line.length) * scale;
            ctx.strokeStyle = FIGURE_LINE_COLOR;
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(cX - L / 2, cY);
            ctx.lineTo(cX + L / 2, cY);
            ctx.stroke();
            drawDimensionLine(
                ctx,
                { x: cX - L / 2, y: cY },
                { x: cX + L / 2, y: cY },
                line.length.toString(),
                BASE_OFFSET_PX
            );
        } else {
            // --- bounding box ---
            const pts = computePathPoints(dims);
            let minX = 1e9,
                maxX = -1e9,
                minY = 1e9,
                maxY = -1e9;
            pts.forEach((p) => {
                minX = Math.min(minX, p.x);
                maxX = Math.max(maxX, p.x);
                minY = Math.min(minY, p.y);
                maxY = Math.max(maxY, p.y);
            });

            // --- segmentos + transformaciones ---
            const segsRaw = computeLineSegments(dims);
            const rot = maxX - minX < maxY - minY && segsRaw.length <= 7;
            const effW = rot ? maxY - minY : maxX - minX;
            const effH = rot ? maxX - minX : maxY - minY;
            const figCX = (minX + maxX) / 2;
            const figCY = (minY + maxY) / 2;
            const scale = Math.min(
                availableWidth / effW,
                drawableSlotHeight / effH
            );

            // figura
            ctx.save();
            ctx.translate(cX, cY);
            if (rot) ctx.rotate(-Math.PI / 2);
            ctx.scale(scale, scale);
            ctx.translate(-figCX, -figCY);
            ctx.lineWidth = 2 / scale;
            dibujarFiguraPath(ctx, dims);
            ctx.restore();

            // --- 1) segmentos transformados a canvas ---
            const segsCanvas = segsRaw.map((seg) => {
                const p1 = transformPoint(
                    seg.start.x,
                    seg.start.y,
                    cX,
                    cY,
                    scale,
                    rot,
                    figCX,
                    figCY
                );
                const p2 = transformPoint(
                    seg.end.x,
                    seg.end.y,
                    cX,
                    cY,
                    scale,
                    rot,
                    figCX,
                    figCY
                );
                return { ...seg, p1, p2 };
            });

            // --- 2) agrupar por línea de cota base (orientación + distancia) ---
            const groups = new Map();
            segsCanvas.forEach((s, idx) => {
                const angKey = angleBucket(s.p1, s.p2);
                const baseD =
                    signedDistanceToLine(s.p1, s.p2, s.p1) + BASE_OFFSET_PX;
                const dKey = Math.round(baseD * 10) / 10; // cuantiza a 0.1 px
                const key = `${angKey}|${dKey}`;
                if (!groups.has(key)) groups.set(key, []);
                groups.get(key).push(idx);
            });

            // --- 3) pintar escalonando offsets dentro de cada grupo ---
            segsCanvas.forEach((s, idx) => {
                const angKey = angleBucket(s.p1, s.p2);
                const baseD =
                    signedDistanceToLine(s.p1, s.p2, s.p1) + BASE_OFFSET_PX;
                const dKey = Math.round(baseD * 10) / 10;
                const key = `${angKey}|${dKey}`;

                const arr = groups.get(key) || [];
                const pos = arr.indexOf(idx); // 0,1,2,...

                // apila al mismo lado; si quieres alternar, usa side/rank como te dije
                const offsetPx = BASE_OFFSET_PX + pos * DUP_OFFSET_STEP_PX;

                drawDimensionLine(
                    ctx,
                    s.p1,
                    s.p2,
                    s.length.toString(),
                    offsetPx
                );
            });
        }
    });
}

// ==========================================================
// Modal y función global `mostrar(id)`
// ==========================================================
let _modal, _canvas, _ctx;
_modal = document.getElementById("modal-dibujo");
_canvas = document.getElementById("canvas-dibujo");
_ctx = _canvas.getContext("2d");

document.getElementById("cerrar-modal").addEventListener("click", () => {
    _modal.classList.add("hidden");
    _ctx.clearRect(0, 0, _canvas.width, _canvas.height);
});

window.mostrar = function (id) {
    if (!window.etiquetasConElementos) {
        console.error("window.etiquetasConElementos no está definido");
        return;
    }
    const grupo = window.etiquetasConElementos[id];
    if (!grupo || !grupo.elementos || !grupo.elementos.length) {
        Swal &&
            Swal.fire(
                "Sin elementos",
                "Esta etiqueta no tiene elementos.",
                "warning"
            );
        return;
    }
    _modal.classList.remove("hidden");
    dibujarGrupoEnCanvas(grupo, _canvas);
};
