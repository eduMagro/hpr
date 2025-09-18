// ===================== PANEL DE CORTE · ACCIÓN MULTI-ETIQUETA =====================
// Objetivo: desde el panel de optimización de corte, poder lanzar de una vez la
// fabricación de TODAS las subetiquetas implicadas en el patrón sugerido (3+ etiquetas).
// El botón "Fabricar optimización" invoca tu backend con un payload compacto.
// ==================================================================================

// ---- Estado global ----
window.ZONAS_CLICABLES = new Map();
window.DATOS_SUGERENCIAS = {};
window.DATOS_ELEMENTOS = [];
window.ID_PANEL = "element-info-panel";
window.ID_PANEL_CUERPO = "element-info-body";

// --------- Carga de datos ---------
window.establecerFuentesDeDatos = ({ sugerencias, elementosAgrupados }) => {
    window.DATOS_SUGERENCIAS = sugerencias || {};
    window.DATOS_ELEMENTOS = elementosAgrupados || [];
};

window.establecerIdsPanel = ({ panelId, panelBodyId } = {}) => {
    if (panelId) window.ID_PANEL = panelId;
    if (panelBodyId) window.ID_PANEL_CUERPO = panelBodyId;
};

// --------- Zonas clicables en el canvas ---------
window.registrarZonaClicable = (elementoId, rutaOCaja) =>
    window.ZONAS_CLICABLES.set(elementoId, rutaOCaja);
window.limpiarZonasClicables = () => window.ZONAS_CLICABLES.clear();

window.vincularClicsEnCanvas = (canvas, contexto) => {
    canvas.addEventListener("click", (evento) => {
        const rect = canvas.getBoundingClientRect();
        const x = evento.clientX - rect.left;
        const y = evento.clientY - rect.top;

        for (const [idElemento, zona] of window.ZONAS_CLICABLES.entries()) {
            let acierta = false;

            if (typeof Path2D !== "undefined" && zona instanceof Path2D) {
                acierta =
                    contexto.isPointInPath(zona, x, y) ||
                    contexto.isPointInStroke(zona, x, y);
            } else if (zona && zona.x != null) {
                acierta =
                    x >= zona.x &&
                    x <= zona.x + zona.w &&
                    y >= zona.y &&
                    y <= zona.y + zona.h;
            }

            if (acierta) {
                window.mostrarPanelInformacionElemento(idElemento);
                break;
            }
        }
    });
};

// --------- Utilidades de texto y formato ---------
const seguro = (v, guion = "—") => (v == null || v === "" ? guion : String(v));
const pct = (x) =>
    x == null ? "—" : (x * 100).toFixed(1).replace(".", ",") + "%";
const cmTxt = (cm) => (cm == null ? "—" : `${cm} cm`);
const mTxt = (m) =>
    m == null ? "—" : `${Number(m).toFixed(2).toString().replace(".", ",")} m`;
const aMetros = (cm) => (cm == null ? null : cm / 100);

// --------- Buscar datos del elemento por id (etiqueta y planilla) ---------
window.buscarElementoPorId = function (idElemento) {
    for (const grupo of window.DATOS_ELEMENTOS) {
        const elemento = (grupo.elementos || []).find(
            (x) => x.id === idElemento
        );
        if (elemento) {
            const etiquetaSubId =
                elemento.etiqueta_sub_id ??
                grupo?.etiqueta?.etiqueta_sub_id ??
                grupo?.etiqueta_sub_id ??
                null;
            return {
                ...elemento,
                etiqueta: grupo.etiqueta,
                planilla: grupo.planilla,
                etiqueta_sub_id: etiquetaSubId,
            };
        }
    }
    return null;
};

// --------- Texto visible de planilla ---------
const textoPlanilla = (infoElemento) => {
    const p = infoElemento?.planilla ?? null;
    if (!p) return "—";
    return p.nombre ?? p.codigo ?? `Planilla #${p.id ?? "?"}`;
};

// --------- Sumar piezas por planilla (para “Planillas implicadas”) ---------
const agruparPiezasPorPlanilla = (piezasPorBarra = {}) => {
    const acc = new Map();
    for (const [idStr, uds] of Object.entries(piezasPorBarra)) {
        const info = window.buscarElementoPorId(Number(idStr));
        const clave = textoPlanilla(info);
        acc.set(clave, (acc.get(clave) ?? 0) + Number(uds));
    }
    return Array.from(acc.entries()).map(([planilla, uds]) => ({
        planilla,
        uds,
    }));
};

// --------- Construcción del payload para "fabricación optimizada" ---------
function construirPayloadFabricacion(sugerencia) {
    // De patron: elemento_id, len_cm, count_por_patron
    const porEtiqueta = new Map(); // etiqueta_sub_id -> { elementos:Set, unidades_por_patron: {id:count} }
    const resumenPatron = [];

    if (Array.isArray(sugerencia?.patron)) {
        for (const p of sugerencia.patron) {
            const info = window.buscarElementoPorId(Number(p.elemento_id));
            const sub = info?.etiqueta_sub_id ?? "SIN_SUB";
            if (!porEtiqueta.has(sub)) {
                porEtiqueta.set(sub, {
                    elementos: new Set(),
                    unidades_por_patron: {},
                });
            }
            const obj = porEtiqueta.get(sub);
            obj.elementos.add(Number(p.elemento_id));
            obj.unidades_por_patron[p.elemento_id] = Number(
                p.count_por_patron ?? 1
            );

            resumenPatron.push({
                elemento_id: Number(p.elemento_id),
                len_cm: Number(p.len_cm ?? 0),
                unidades_por_patron: Number(p.count_por_patron ?? 1),
            });
        }
    }

    const etiquetas = Array.from(porEtiqueta.entries()).map(
        ([etiqueta_sub_id, data]) => ({
            etiqueta_sub_id,
            elementos: Array.from(data.elementos.values()),
            unidades_por_patron: data.unidades_por_patron,
        })
    );

    return {
        producto_base: {
            tipo: sugerencia?.tipo ?? null,
            diametro_mm: Number(sugerencia?.diametro_mm ?? 0),
            longitud_barra_cm: Number(sugerencia?.longitud_barra_cm ?? 0),
            uso_cm: Number(sugerencia?.uso_cm ?? 0),
            sobrante_cm: Number(sugerencia?.sobrante_cm ?? 0),
            eficiencia: Number(sugerencia?.eficiencia ?? 0),
        },
        repeticiones: Number(sugerencia?.repeticiones ?? 1),
        barras_totales_estimadas: Number(sugerencia?.barras_totales ?? 0),
        resumen_patron: resumenPatron, // por si tu servicio/factoría necesita reconstruir
        etiquetas, // <- lo importante para lanzar varias subetiquetas a fabricación
    };
}

// --------- Envío al backend ---------
async function postFabricacionOptimizada(url, payload) {
    const token =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ||
        window.Laravel?.csrfToken ||
        "";

    const resp = await fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": token,
            Accept: "application/json",
        },
        body: JSON.stringify(payload),
    });

    if (!resp.ok) {
        const msg = await resp.text();
        throw new Error(msg || `Error HTTP ${resp.status}`);
    }
    return resp.json().catch(() => ({}));
}

// --------- Botón: Fabricar optimización ---------
async function onFabricarClick({
    sugerencia,
    url = "/fabricacion/optimizada",
}) {
    const lanzar = async () => {
        const payload = construirPayloadFabricacion(sugerencia);
        // Validación mínima
        if (!payload.etiquetas.length)
            throw new Error("No hay subetiquetas en el patrón.");
        if (!payload.producto_base.longitud_barra_cm) {
            throw new Error("Falta la longitud de barra del producto base.");
        }
        const res = await postFabricacionOptimizada(url, payload);
        return res;
    };

    // SweetAlert2 si existe; si no, confirm/alert
    if (window.Swal?.fire) {
        const ok = await window.Swal.fire({
            title: "¿Fabricar optimización?",
            html:
                "<div class='text-left text-sm'>" +
                "<p>Se pondrán en fabricación todas las subetiquetas implicadas en el patrón.</p>" +
                "<p class='mt-2'>¿Confirmas?</p></div>",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Sí, fabricar",
            cancelButtonText: "Cancelar",
            focusConfirm: true,
        });

        if (!ok.isConfirmed) return;
        try {
            const res = await lanzar();
            await window.Swal.fire({
                title: "Fabricación lanzada",
                text:
                    res?.message ||
                    "Se han puesto en fabricación las subetiquetas.",
                icon: "success",
            });
            // Opcional: refrescar listados/estado
            window.dispatchEvent(
                new CustomEvent("fabricacion:optimizada:ok", { detail: res })
            );
        } catch (e) {
            window.Swal.fire({
                title: "No se pudo fabricar",
                html: `<pre class="text-left whitespace-pre-wrap">${seguro(
                    e.message
                )}</pre>`,
                icon: "error",
            });
        }
    } else {
        const ok = window.confirm(
            "Se fabricarán todas las subetiquetas implicadas. ¿Continuar?"
        );
        if (!ok) return;
        try {
            const res = await lanzar();
            window.alert(res?.message || "Fabricación lanzada correctamente.");
            window.dispatchEvent(
                new CustomEvent("fabricacion:optimizada:ok", { detail: res })
            );
        } catch (e) {
            window.alert("Error al fabricar: " + seguro(e.message));
        }
    }
}

// ===================== PANEL: MOSTRAR INFORMACIÓN =====================
window.mostrarPanelInformacionElemento = function (elementoId) {
    const panel = document.getElementById(window.ID_PANEL);
    const cuerpo = document.getElementById(window.ID_PANEL_CUERPO);
    if (!panel || !cuerpo) return;

    const elemento = window.buscarElementoPorId(elementoId);
    const envoltura = window.DATOS_SUGERENCIAS[elementoId] || null;
    const sugerencia =
        envoltura && envoltura.ok && envoltura.sugerencia
            ? envoltura.sugerencia
            : null;

    cuerpo.innerHTML = window.renderizarInformacionElemento(
        elemento,
        sugerencia
    );

    // Enlazar botón "Fabricar optimización", si existe
    if (sugerencia) {
        const btn = cuerpo.querySelector("#btn-fabricar-optim");
        if (btn) {
            btn.addEventListener("click", () =>
                onFabricarClick({
                    sugerencia,
                    url: btn.dataset.url || "/fabricacion/optimizada",
                })
            );
        }
    }

    panel.classList.remove("hidden");
};

// ===================== RENDER HTML DEL PANEL =====================
window.renderizarInformacionElemento = function (elemento, sugerencia) {
    const etiquetaSub = seguro(elemento?.etiqueta_sub_id);
    const longElem = seguro(elemento?.longitud ?? elemento?.longitud_cm);
    const diamElem = seguro(elemento?.diametro ?? elemento?.diametro_mm);
    const planillaTxt = textoPlanilla(elemento);

    // Si no hay sugerencia, mostramos básico sin botón
    if (!sugerencia) {
        return `
      <div class="space-y-2">
        <div class="text-xs text-gray-500">Etiqueta</div>
        <div class="text-sm font-semibold">${etiquetaSub}</div>

        <div class="grid grid-cols-2 gap-x-4 gap-y-1">
          <div>Longitud A:</div><div class="font-medium">${longElem}</div>
          <div>Diámetro A:</div><div class="font-medium">${diamElem}</div>
          <div>Planilla A:</div>
          <div class="font-medium"><span class="inline-block px-2 py-0.5 rounded bg-gray-100">${planillaTxt}</span></div>
        </div>

        <hr class="my-2">
        <div class="text-amber-700">Sin sugerencia disponible.</div>
      </div>`;
    }

    const barraCm = sugerencia.longitud_barra_cm ?? null;
    const barraM = aMetros(barraCm);
    const diamMM = sugerencia.diametro_mm ?? null;

    // Resumen producto base
    let html = `
    <div class="space-y-2">
      <div class="text-xs text-gray-500">Etiqueta</div>
      <div class="text-sm font-semibold">${etiquetaSub}</div>

      <div class="grid grid-cols-2 gap-x-4 gap-y-1">
        <div>Longitud A:</div><div class="font-medium">${longElem}</div>
        <div>Diámetro A:</div><div class="font-medium">${diamElem}</div>
        <div>Planilla A:</div>
        <div class="font-medium"><span class="inline-block px-2 py-0.5 rounded bg-gray-100">${planillaTxt}</span></div>
      </div>

      <hr class="my-2">
      <div class="grid grid-cols-2 gap-x-4 gap-y-1">
        <div>Producto base:</div><div class="font-medium">${seguro(
            sugerencia.tipo
        )} — ${seguro(diamMM)} mm — ${mTxt(barraM)}</div>
        <div>Uso por barra:</div><div class="font-medium">${cmTxt(
            sugerencia.uso_cm
        )}</div>
        <div>Sobrante por barra:</div><div class="font-medium">${cmTxt(
            sugerencia.sobrante_cm
        )}</div>
        <div>Eficiencia:</div><div class="font-medium">${pct(
            sugerencia.eficiencia
        )}</div>
        <div>Barras totales (est.):</div><div class="font-medium">${seguro(
            sugerencia.barras_totales
        )}</div>
        <div>Repeticiones patrón:</div><div class="font-medium">${seguro(
            sugerencia.repeticiones
        )}</div>
      </div>
  `;

    // Tabla patrón
    if (Array.isArray(sugerencia.patron) && sugerencia.patron.length) {
        const filasPatron = sugerencia.patron
            .map((p) => {
                const info = window.buscarElementoPorId(p.elemento_id);
                return `
          <tr class="border-b">
            <td class="px-2 py-1 text-xs">${seguro(p.elemento_id)}</td>
            <td class="px-2 py-1 text-xs">${seguro(info?.etiqueta_sub_id)}</td>
            <td class="px-2 py-1 text-xs">${cmTxt(p.len_cm)}</td>
            <td class="px-2 py-1 text-xs text-center">${seguro(
                p.count_por_patron
            )}</td>
            <td class="px-2 py-1 text-xs"><span class="inline-block px-2 py-0.5 rounded bg-gray-100">${textoPlanilla(
                info
            )}</span></td>
          </tr>`;
            })
            .join("");

        html += `
      <div class="mt-3">
        <div class="font-semibold mb-1">Patrón por barra</div>
        <table class="w-full text-left text-sm border rounded overflow-hidden">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-2 py-1 text-xs">Elemento</th>
              <th class="px-2 py-1 text-xs">Subetiqueta</th>
              <th class="px-2 py-1 text-xs">Longitud</th>
              <th class="px-2 py-1 text-xs text-center">u/patrón</th>
              <th class="px-2 py-1 text-xs">Planilla</th>
            </tr>
          </thead>
          <tbody>${filasPatron}</tbody>
        </table>
      </div>`;
    }

    // Piezas por barra + planillas implicadas
    if (sugerencia.piezas_por_barra) {
        const filasPiezas = Object.entries(sugerencia.piezas_por_barra)
            .map(([id, uds]) => {
                const info = window.buscarElementoPorId(Number(id));
                return `
          <tr class="border-b">
            <td class="px-2 py-1 text-xs">${seguro(id)}</td>
            <td class="px-2 py-1 text-xs">${seguro(info?.etiqueta_sub_id)}</td>
            <td class="px-2 py-1 text-xs text-center">${seguro(uds)}</td>
            <td class="px-2 py-1 text-xs"><span class="inline-block px-2 py-0.5 rounded bg-gray-100">${textoPlanilla(
                info
            )}</span></td>
          </tr>`;
            })
            .join("");

        const planillasImplicadas = agruparPiezasPorPlanilla(
            sugerencia.piezas_por_barra
        )
            .map(
                (x) =>
                    `<span class="inline-block px-2 py-0.5 mr-1 mb-1 rounded bg-blue-50 border text-xs">${x.planilla}: <b>${x.uds}</b> uds/barra</span>`
            )
            .join("");

        html += `
      <div class="mt-3">
        <div class="font-semibold mb-1">Piezas por barra</div>
        <table class="w-full text-left text-sm border rounded overflow-hidden">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-2 py-1 text-xs">Elemento</th>
              <th class="px-2 py-1 text-xs">Subetiqueta</th>
              <th class="px-2 py-1 text-xs text-center">uds</th>
              <th class="px-2 py-1 text-xs">Planilla</th>
            </tr>
          </thead>
          <tbody>${filasPiezas}</tbody>
        </table>

        <div class="mt-2">
          <div class="text-xs text-gray-600 mb-1">Planillas implicadas (por barra):</div>
          <div>${planillasImplicadas || '<span class="text-xs">—</span>'}</div>
        </div>
      </div>`;
    }

    // Botón de acción (multi-etiqueta)
    html += `
      <div class="mt-4 flex justify-end">
      <button id="btn-fabricar-optim"
        data-url="{{ route('etiquetas.fabricacion-optimizada') }}"
        class="px-3 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">
  Fabricar optimización
</button>

      </div>
    </div>`;

    return html;
};

// ===================== INICIALIZACIÓN DESDE LA VISTA =====================
// Llama a esto desde tu HTML después de tener el canvas:
// window.iniciarCanvasMaquinas?.({ canvas, contexto, sugerencias, elementosAgrupados, idsPanel })
window.iniciarCanvasMaquinas = function ({
    canvas,
    contexto,
    sugerencias,
    elementosAgrupados,
    idsPanel,
}) {
    window.establecerFuentesDeDatos({ sugerencias, elementosAgrupados });
    if (idsPanel) window.establecerIdsPanel(idsPanel);
    window.vincularClicsEnCanvas(canvas, contexto);
};

// ===================== ALIAS DE COMPATIBILIDAD (opcional) =====================
window.setDataSources = window.establecerFuentesDeDatos;
window.setPanelIds = window.establecerIdsPanel;
window.registrarHitbox = window.registrarZonaClicable;
window.clearHitboxes = window.limpiarZonasClicables;
window.bindCanvasClicks = window.vincularClicsEnCanvas;
window.mostrarPanelInfoElemento = window.mostrarPanelInformacionElemento;
window.renderizarInfoElemento = window.renderizarInformacionElemento;
window.initCanvasMaquinas = window.iniciarCanvasMaquinas;
