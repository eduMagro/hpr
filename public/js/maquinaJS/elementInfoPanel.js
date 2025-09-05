// public/js/elementInfoPanel.js

// Estado interno
window.HITBOXES = new Map();
window.DATA_SUGERENCIAS = {};
window.DATA_ELEMENTOS = [];
window.PANEL_ID = "element-info-panel";
window.PANEL_BODY_ID = "element-info-body";

// Configurar fuentes de datos
window.setDataSources = function ({ sugerencias, elementosAgrupados }) {
    window.DATA_SUGERENCIAS = sugerencias || {};
    window.DATA_ELEMENTOS = elementosAgrupados || [];
};

window.setPanelIds = function ({ panelId, panelBodyId } = {}) {
    if (panelId) window.PANEL_ID = panelId;
    if (panelBodyId) window.PANEL_BODY_ID = panelBodyId;
};

window.registrarHitbox = function (elementId, pathOrBox) {
    window.HITBOXES.set(elementId, pathOrBox);
};

window.clearHitboxes = function () {
    window.HITBOXES.clear();
};

window.bindCanvasClicks = function (canvas, ctx) {
    canvas.addEventListener("click", (ev) => {
        const rect = canvas.getBoundingClientRect();
        const x = ev.clientX - rect.left;
        const y = ev.clientY - rect.top;

        for (const [id, hb] of window.HITBOXES.entries()) {
            let hit = false;
            if (typeof Path2D !== "undefined" && hb instanceof Path2D) {
                hit =
                    ctx.isPointInPath(hb, x, y) ||
                    ctx.isPointInStroke(hb, x, y);
            } else if (hb && hb.x != null) {
                hit =
                    x >= hb.x &&
                    x <= hb.x + hb.w &&
                    y >= hb.y &&
                    y <= hb.y + hb.h;
            }
            if (hit) {
                window.mostrarPanelInfoElemento(id);
                break;
            }
        }
    });
};

window.mostrarPanelInfoElemento = function (elementId) {
    const panel = document.getElementById(window.PANEL_ID);
    const body = document.getElementById(window.PANEL_BODY_ID);
    if (!panel || !body) return;

    const sugWrap = window.DATA_SUGERENCIAS[elementId] || null;
    const elemBase = window.buscarElementoPorId(elementId);

    body.innerHTML = window.renderizarInfoElemento(elemBase, sugWrap);
    panel.classList.remove("hidden");
};

window.buscarElementoPorId = function (id) {
    for (const g of window.DATA_ELEMENTOS) {
        const found = (g.elementos || []).find((e) => e.id === id);
        if (found)
            return { ...found, etiqueta: g.etiqueta, planilla: g.planilla };
    }
    return null;
};

window.renderizarInfoElemento = function (elem, sugWrap) {
    const safe = (v) => (v == null ? "—" : String(v));
    const codigo = safe(elem?.codigo);
    const longCm = elem?.longitud ?? elem?.longitud_cm ?? null;
    const diam = elem?.diametro ?? elem?.diametro_mm ?? null;

    let sugHtml =
        '<div class="text-amber-700">Sin sugerencia disponible.</div>';
    if (sugWrap?.ok && sugWrap.sugerencia) {
        const s = sugWrap.sugerencia;
        const eff = (s.eficiencia * 100).toFixed(1).replace(".", ",");
        const longBarraM = (s.longitud_barra_mm / 1000)
            .toFixed(2)
            .replace(".", ",");

        sugHtml = `
      <div class="grid grid-cols-2 gap-x-4 gap-y-1">
        <div>Producto base:</div><div class="font-medium">#${s.producto_base_id}</div>
        <div>Longitud barra:</div><div class="font-medium">${longBarraM} m</div>
        <div>Piezas por barra:</div><div class="font-medium">${s.n_por_barra}</div>
        <div>Sobrante por barra:</div><div class="font-medium">${s.sobrante_mm} mm</div>
        <div>Eficiencia:</div><div class="font-medium">${eff}%</div>
        <div>Barras totales (est.):</div><div class="font-medium">${s.barras_totales}</div>
      </div>
    `;

        if (s.pareja) {
            const eff2 = (s.pareja.eficiencia * 100)
                .toFixed(1)
                .replace(".", ",");
            sugHtml += `
        <div class="mt-2 p-2 rounded border bg-white">
          <div class="font-semibold mb-1">Emparejar con</div>
          <div class="grid grid-cols-2 gap-x-4 gap-y-1">
            <div>Elemento pareja:</div><div class="font-medium">#${s.pareja.elemento_id}</div>
            <div>Sobrante con pareja:</div><div class="font-medium">${s.pareja.sobrante_mm} mm</div>
            <div>Eficiencia con pareja:</div><div class="font-medium">${eff2}%</div>
          </div>
        </div>
      `;
        }
    }

    return `
    <div class="space-y-2">
      <div class="text-xs text-gray-500">Elemento</div>
      <div class="text-sm font-semibold">${codigo}</div>
      <div class="grid grid-cols-2 gap-x-4 gap-y-1">
        <div>Longitud:</div><div class="font-medium">${longCm ?? "—"} ${
        longCm ? "cm" : ""
    }</div>
        <div>Diámetro:</div><div class="font-medium">${diam ?? "—"} ${
        diam ? "mm" : ""
    }</div>
      </div>
      <hr class="my-2">
      ${sugHtml}
    </div>
  `;
};
