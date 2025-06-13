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

// ==========================================================
// Función PRINCIPAL – dibuja UN grupo en UN canvas
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
    const availableWidth = canvasWidth - 2 * marginX;

    grupo.elementos.forEach((el, i) => {
        /* -------- Datos del elemento -------- */
        const dims = extraerDimensiones(el.dimensiones || "");
        const barras = el.barras ?? 0;
        const diam = el.diametro ?? "N/A";
        const peso = el.peso ?? "N/A";

        const cX = marginX + availableWidth / 2;
        const cY =
            textHeight +
            i * (availableSlotHeight + gapSpacing) +
            availableSlotHeight / 2;

        ctx.font = "26px Arial";
        ctx.fillStyle = "#000";
        ctx.textAlign = "right";
        ctx.fillText(
            `Ø${diam} | ${peso} | x${barras}`,
            canvasWidth - 10,
            cY - 40
        );

        ctx.fillStyle = ELEMENT_TEXT_COLOR;
        ctx.textAlign = "left";
        ctx.fillText(
            `#${el.id}`,
            marginX + availableWidth + 25,
            cY + availableSlotHeight / 2 - 50
        );

        /* -------- Dibujo de la figura -------- */
        if (dims.length === 1 && dims[0].type === "arc") {
            const arc = dims[0];
            const scale = Math.min(
                availableWidth / (2 * arc.radius),
                availableSlotHeight / (2 * arc.radius)
            );
            const R = arc.radius * scale;
            const ang = ((arc.arcAngle || 360) * Math.PI) / 180;
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
                10
            );
        } else {
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
            const segs = computeLineSegments(dims);
            const rot = maxX - minX < maxY - minY && segs.length <= 7;
            const effW = rot ? maxY - minY : maxX - minX;
            const effH = rot ? maxX - minX : maxY - minY;
            const figCX = (minX + maxX) / 2;
            const figCY = (minY + maxY) / 2;
            const scale = Math.min(
                availableWidth / effW,
                availableSlotHeight / effH
            );

            ctx.save();
            ctx.translate(cX, cY);
            if (rot) ctx.rotate(-Math.PI / 2);
            ctx.scale(scale, scale);
            ctx.translate(-figCX, -figCY);
            ctx.lineWidth = 2 / scale;
            dibujarFiguraPath(ctx, dims);
            ctx.restore();

            segs.forEach((seg) => {
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
                drawDimensionLine(ctx, p1, p2, seg.length.toString(), 10);
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
