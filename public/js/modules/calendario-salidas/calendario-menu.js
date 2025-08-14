// calendario-menu.js
import { openActionsMenu, closeMenu } from "./menuContextual.js";

/* ===================== Animaciones SweetAlert (una vez) ===================== */
/* FIX: la animación incluye translate(-50%,-50%) para no romper el centrado */
(function injectSwalAnimations() {
    if (document.getElementById("swal-anims")) return;
    const style = document.createElement("style");
    style.id = "swal-anims";
    style.textContent = `
  /* Animación solo con scale; el centrado lo hacemos con left/top */
  @keyframes swalFadeInZoom {
    0%   { opacity: 0; transform: scale(.95); }
    100% { opacity: 1; transform: scale(1); }
  }
  @keyframes swalFadeOut {
    0%   { opacity: 1; transform: scale(1); }
    100% { opacity: 0; transform: scale(.98); }
  }
  .swal-fade-in-zoom { animation: swalFadeInZoom .18s ease-out both; }
  .swal-fade-out     { animation: swalFadeOut   .12s ease-in  both; }

  /* IMPORTANTE: escalar desde el centro para que no “camine” */
  .swal2-popup { 
    will-change: transform, opacity; 
    backface-visibility: hidden; 
    transform-origin: center center;
  }

  @keyframes swalRowIn { to { opacity: 1; transform: none; } }
  `;
    document.head.appendChild(style);
})();

/* ===================== Util: extraer IDs planillas ===================== */
function extraerPlanillasIds(event) {
    const p = event.extendedProps || {};
    if (Array.isArray(p.planillas_ids) && p.planillas_ids.length)
        return p.planillas_ids;
    const m = (event.id || "").match(/planilla-(\d+)/);
    return m ? [Number(m[1])] : [];
}

/* ===================== Crear salida ===================== */
async function crearSalida(planillasIds, calendar) {
    const camiones = window.AppSalidas?.camiones || [];
    if (!camiones.length) {
        return Swal.fire(
            "Sin camiones",
            "No hay camiones disponibles.",
            "warning"
        );
    }
    const { value: camionId, isConfirmed } = await Swal.fire({
        title: "Selecciona un camión",
        input: "select",
        inputOptions: Object.fromEntries(
            camiones.map((c) => [
                c.id,
                `${c.modelo} (${
                    c?.empresa_transporte?.nombre ?? "Sin empresa"
                })`,
            ])
        ),
        inputPlaceholder: "Elige una opción",
        showCancelButton: true,
        confirmButtonText: "Confirmar",
    });
    if (!isConfirmed || !camionId) return;

    try {
        const res = await fetch(
            window.AppSalidas?.routes?.crearSalidaDesdeCalendario,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": window.AppSalidas?.csrf,
                },
                body: JSON.stringify({
                    planillas_ids: planillasIds,
                    camion_id: camionId,
                }),
            }
        );
        const data = await res.json();
        await Swal.fire(
            data.success ? "✅" : "⚠️",
            data.message ||
                (data.success ? "Salida creada" : "No se pudo crear"),
            data.success ? "success" : "warning"
        );
        if (data.success && calendar) {
            calendar.refetchEvents();
            calendar.refetchResources?.();
        }
    } catch (err) {
        console.error(err);
        Swal.fire("❌", "Hubo un problema al crear la salida.", "error");
    }
}

/* ===================== Comentar salida ===================== */
async function comentarSalida(salidaId, comentarioActual, calendar) {
    const { value, isConfirmed } = await Swal.fire({
        title: "✏️ Agregar comentario",
        input: "textarea",
        inputValue: comentarioActual || "",
        inputPlaceholder: "Escribe aquí…",
        showCancelButton: true,
        confirmButtonText: "💾 Guardar",
    });
    if (!isConfirmed) return;

    const url = (window.AppSalidas?.routes?.comentario || "").replace(
        "__ID__",
        salidaId
    );
    try {
        const res = await fetch(url, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": window.AppSalidas?.csrf,
            },
            body: JSON.stringify({ comentario: value }),
        });
        const data = await res.json();
        await Swal.fire(
            data.success ? "✅" : "⚠️",
            data.message ||
                (data.success ? "Comentario guardado" : "No se pudo guardar"),
            data.success ? "success" : "warning"
        );
        if (data.success && calendar) calendar.refetchEvents();
    } catch (err) {
        console.error(err);
        Swal.fire("❌", "Ocurrió un error al guardar", "error");
    }
}

/* ===================== Asignar código SAGE ===================== */
async function asignarCodigoSalida(salidaId, codigoActual = "", calendar) {
    try {
        closeMenu();
    } catch (_) {}
    if (!salidaId) return Swal.fire("⚠️", "ID de salida inválido.", "warning");

    const { value, isConfirmed } = await Swal.fire({
        title: "🏷️ Asignar código SAGE",
        input: "text",
        inputValue: (codigoActual || "").trim(),
        inputPlaceholder: "Ej.: F1/0004",
        inputValidator: (v) =>
            !v || v.trim().length === 0
                ? "El código no puede estar vacío"
                : undefined,
        showCancelButton: true,
        confirmButtonText: "💾 Guardar",
    });
    if (!isConfirmed) return;

    const routeTmpl = window.AppSalidas?.routes?.codigoSage || "";
    if (!routeTmpl)
        return Swal.fire(
            "⚠️",
            "No está configurada la ruta de código SAGE.",
            "warning"
        );

    const url = routeTmpl.replace("__ID__", String(salidaId));
    const payload = { codigo: value.trim() };

    try {
        const res = await fetch(url, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": window.AppSalidas?.csrf,
            },
            body: JSON.stringify(payload),
        });
        if (!res.ok) {
            const text = await res.text().catch(() => "");
            throw new Error(`HTTP ${res.status} ${res.statusText} ${text}`);
        }
        const data = await res.json().catch(() => ({}));
        await Swal.fire(
            data.success ? "✅" : "⚠️",
            data.message ||
                (data.success ? "Código SAGE asignado" : "No se pudo asignar"),
            data.success ? "success" : "warning"
        );
        if (data.success && calendar) {
            calendar.refetchEvents?.();
            calendar.refetchResources?.();
        }
    } catch (err) {
        console.error(err);
        Swal.fire("❌", "Ocurrió un error al guardar el código.", "error");
    }
}

/* ===================== Util: normalizar IDs ===================== */
function normalizarIds(input) {
    if (!input) return [];
    if (typeof input === "string") {
        return input
            .split(",")
            .map((s) => s.trim())
            .filter(Boolean);
    }
    const arr = Array.from(input);
    return arr
        .map((x) => (typeof x === "object" && x?.id != null ? x.id : x))
        .map(String)
        .map((s) => s.trim())
        .filter(Boolean);
}

/* ===================== Abrir listado de planillas ===================== */
export function salidasCreate(planillasIds /*, calendar */) {
    const base = window?.AppSalidas?.routes?.salidasCreate;
    if (!base) {
        alert("No se encontró la ruta de salidas.create");
        return;
    }
    const ids = Array.from(new Set(normalizarIds(planillasIds)));
    const qs = ids.length
        ? "?planillas=" + encodeURIComponent(ids.join(","))
        : "";
    window.location.href = base + qs;
}

/* ===================== Cambiar fechas (modal agrupación) ===================== */
async function obtenerInformacionPlanillas(ids) {
    const base = window.AppSalidas?.routes?.informacionPlanillas;
    if (!base) throw new Error("Ruta 'informacionPlanillas' no configurada");
    const url = `${base}?ids=${encodeURIComponent(ids.join(","))}`;
    const res = await fetch(url, { headers: { Accept: "application/json" } });
    if (!res.ok) {
        const t = await res.text().catch(() => "");
        throw new Error(`GET ${url} -> ${res.status} ${t}`);
    }
    const data = await res.json();
    return Array.isArray(data?.planillas) ? data.planillas : [];
}

function construirFormularioFechas(planillas) {
    const filas = planillas
        .map((p, i) => {
            const f = (p.fecha_estimada_entrega || "").slice(0, 10);
            const codObra = p.obra?.codigo || p.obra?.cod_obra || "";
            const nombreObra = p.obra?.nombre || p.obra?.obra || "";
            const codigoPlanilla = p.codigo || `Planilla ${p.id}`;
            return `
      <tr style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${
          i * 18
      }ms;">
        <td class="px-2 py-1 text-xs">${p.id}</td>
        <td class="px-2 py-1 text-xs">${codObra}</td>
        <td class="px-2 py-1 text-xs">${nombreObra}</td>
        <td class="px-2 py-1 text-xs">${codigoPlanilla}</td>
        <td class="px-2 py-1">
          <input type="date" class="swal2-input !m-0 !w-auto" data-planilla-id="${
              p.id
          }" value="${f}">
        </td>
      </tr>`;
        })
        .join("");

    return `
    <div class="text-left">
      <div class="text-sm text-gray-600 mb-2">
        Edita la <strong>fecha estimada de entrega</strong> y guarda.
      </div>
      <div class="overflow-auto" style="max-height:50vh;border:1px solid #e5e7eb;border-radius:6px;">
        <table class="min-w-full text-sm">
          <thead class="sticky top-0 bg-white">
            <tr>
              <th class="px-2 py-1 text-left">ID</th>
              <th class="px-2 py-1 text-left">Cod. Obra</th>
              <th class="px-2 py-1 text-left">Obra</th>
              <th class="px-2 py-1 text-left">Planilla</th>
              <th class="px-2 py-1 text-left">Fecha Estimada Entrega</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
    </div>`;
}

async function guardarFechasPlanillas(payload) {
    const base = window.AppSalidas?.routes?.actualizarFechasPlanillas;
    if (!base)
        throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");
    const res = await fetch(base, {
        method: "PUT",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": window.AppSalidas?.csrf,
            Accept: "application/json",
        },
        body: JSON.stringify({ planillas: payload }),
    });
    if (!res.ok) {
        const t = await res.text().catch(() => "");
        throw new Error(`PUT ${base} -> ${res.status} ${t}`);
    }
    return res.json().catch(() => ({}));
}

async function cambiarFechasEntrega(planillasIds, calendar) {
    try {
        const ids = Array.from(new Set(normalizarIds(planillasIds)))
            .map(Number)
            .filter(Boolean);
        if (!ids.length)
            return Swal.fire(
                "⚠️",
                "No hay planillas en la agrupación.",
                "warning"
            );

        const planillas = await obtenerInformacionPlanillas(ids);
        if (!planillas.length)
            return Swal.fire(
                "⚠️",
                "No se han encontrado planillas.",
                "warning"
            );

        // Cabecera draggable propia para evitar depender del h2 interno de SweetAlert
        const barraDrag = `
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `;
        const html = barraDrag + construirFormularioFechas(planillas);

        const { isConfirmed } = await Swal.fire({
            title: "", // usamos barraDrag en el HTML
            html,
            width: Math.min(window.innerWidth * 0.9, 800),
            showCancelButton: true,
            confirmButtonText: "💾 Guardar",
            cancelButtonText: "Cancelar",
            focusConfirm: false,
            showClass: { popup: "swal-fade-in-zoom" },
            hideClass: { popup: "swal-fade-out" },
            didOpen: (popup) => {
                // 1) Centrar SIEMPRE al abrir (coincide con la animación que usa translate)
                centerSwal(popup);
                // 2) Habilitar drag SOLO sobre #swal-drag (sin memoria para que no “herede” posiciones)
                hacerDraggableSwal("#swal-drag", false);
                // 3) Foco suave
                setTimeout(() => {
                    const first =
                        Swal.getHtmlContainer().querySelector(
                            'input[type="date"]'
                        );
                    first?.focus({ preventScroll: true });
                }, 120);
            },
        });
        if (!isConfirmed) return;

        const inputs = Swal.getHtmlContainer().querySelectorAll(
            'input[type="date"][data-planilla-id]'
        );
        const payload = Array.from(inputs).map((inp) => ({
            id: Number(inp.getAttribute("data-planilla-id")),
            fecha_estimada_entrega: (inp.value || "").trim() || null,
        }));

        const resp = await guardarFechasPlanillas(payload);
        await Swal.fire(
            resp.success ? "✅" : "⚠️",
            resp.message ||
                (resp.success
                    ? "Fechas actualizadas"
                    : "No se pudieron actualizar"),
            resp.success ? "success" : "warning"
        );

        if (resp.success && calendar) {
            calendar.refetchEvents?.();
            calendar.refetchResources?.();
        }
    } catch (err) {
        console.error("[CambiarFechasEntrega] error:", err);
        Swal.fire(
            "❌",
            err?.message || "Ocurrió un error al actualizar las fechas.",
            "error"
        );
    }
}

/* ===================== Menú contextual calendario ===================== */
export function attachEventoContextMenu(info, calendar) {
    info.el.addEventListener("mousedown", closeMenu);

    info.el.addEventListener("contextmenu", (e) => {
        e.preventDefault();
        e.stopPropagation();

        const ev = info.event;
        const p = ev.extendedProps || {};
        const tipo = p.tipo || "planilla";

        const headerHtml = `
      <div style="padding:10px 12px; font-weight:600;">
        ${ev.title ?? "Evento"}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(ev.start).toLocaleString()} — ${new Date(
            ev.end
        ).toLocaleString()}
        </span>
      </div>
    `;

        let items = [];

        if (tipo === "planilla") {
            const planillasIds = extraerPlanillasIds(ev);
            items = [
                {
                    label: "Crear salida",
                    icon: "🚚",
                    onClick: () => crearSalida(planillasIds, calendar),
                },
                {
                    label: "Ver Planillas de Agrupación",
                    icon: "🧾",
                    onClick: () => salidasCreate(planillasIds, calendar),
                },
                {
                    label: "Cambiar fechas de entrega",
                    icon: "🗓️",
                    onClick: () => cambiarFechasEntrega(planillasIds, calendar),
                },
            ];
        } else if (tipo === "salida") {
            items = [
                {
                    label: "Abrir salida",
                    icon: "🧾",
                    onClick: () => window.open(`/salidas/${ev.id}`, "_blank"),
                },
                {
                    label: "Asignar código SAGE",
                    icon: "🏷️",
                    onClick: () =>
                        asignarCodigoSalida(ev.id, p.codigo || "", calendar),
                },
                {
                    label: "Agregar comentario",
                    icon: "✍️",
                    onClick: () =>
                        comentarSalida(ev.id, p.comentario || "", calendar),
                },
            ];
        } else {
            items = [
                {
                    label: "Abrir",
                    icon: "🧾",
                    onClick: () => window.open(p.url || "#", "_blank"),
                },
            ];
        }

        openActionsMenu(e.clientX, e.clientY, { headerHtml, items });
    });
}

/* ===================== Utils: centrar y drag ===================== */

function centerSwal(popup) {
    // Quita cualquier transform para medir bien
    popup.style.transform = "none";
    popup.style.position = "fixed";
    popup.style.margin = "0";

    // Asegura layout antes de medir
    const w = popup.offsetWidth;
    const h = popup.offsetHeight;

    const left = Math.max(0, Math.round((window.innerWidth - w) / 2));
    const top = Math.max(0, Math.round((window.innerHeight - h) / 2));

    popup.style.left = `${left}px`;
    popup.style.top = `${top}px`;
}

/* Nota: en pointerdown pasamos a coordenadas absolutas y quitamos translate.
   Como la animación ya incluye el translate, el centrado inicial no se pierde. */
function hacerDraggableSwal(handleSelector = ".swal2-title", remember = false) {
    const popup = Swal.getPopup();
    const container = Swal.getHtmlContainer();
    let handle =
        (handleSelector
            ? container?.querySelector(handleSelector) ||
              popup?.querySelector(handleSelector)
            : null) || popup;
    if (!popup || !handle) return;

    if (remember && hacerDraggableSwal.__lastPos) {
        popup.style.left = hacerDraggableSwal.__lastPos.left;
        popup.style.top = hacerDraggableSwal.__lastPos.top;
        popup.style.transform = "none";
    }

    handle.style.cursor = "move";
    handle.style.touchAction = "none";

    const isInteractive = (el) =>
        el.closest?.(
            "input, textarea, select, button, a, label, [contenteditable]"
        ) != null;

    let isDown = false;
    let startX = 0,
        startY = 0;
    let startLeft = 0,
        startTop = 0;

    const onPointerDown = (e) => {
        if (!handle.contains(e.target) || isInteractive(e.target)) return;

        isDown = true;
        document.body.style.userSelect = "none";

        const rect = popup.getBoundingClientRect();
        popup.style.left = `${rect.left}px`;
        popup.style.top = `${rect.top}px`;
        popup.style.transform = "none";

        startLeft = parseFloat(popup.style.left || rect.left);
        startTop = parseFloat(popup.style.top || rect.top);
        startX = e.clientX;
        startY = e.clientY;

        document.addEventListener("pointermove", onPointerMove);
        document.addEventListener("pointerup", onPointerUp, { once: true });
    };

    const onPointerMove = (e) => {
        if (!isDown) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        let nextLeft = startLeft + dx;
        let nextTop = startTop + dy;

        const w = popup.offsetWidth,
            h = popup.offsetHeight;
        const minLeft = -w + 40,
            maxLeft = window.innerWidth - 40;
        const minTop = -h + 40,
            maxTop = window.innerHeight - 40;
        nextLeft = Math.max(minLeft, Math.min(maxLeft, nextLeft));
        nextTop = Math.max(minTop, Math.min(maxTop, nextTop));

        popup.style.left = `${nextLeft}px`;
        popup.style.top = `${nextTop}px`;
    };

    const onPointerUp = () => {
        isDown = false;
        document.body.style.userSelect = "";
        if (remember) {
            hacerDraggableSwal.__lastPos = {
                left: popup.style.left,
                top: popup.style.top,
            };
        }
        document.removeEventListener("pointermove", onPointerMove);
    };

    handle.addEventListener("pointerdown", onPointerDown);
}
