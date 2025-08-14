// calendario-menu.js
import { openActionsMenu, closeMenu } from "./menuContextual.js";

function extraerPlanillasIds(event) {
    const p = event.extendedProps || {};
    if (Array.isArray(p.planillas_ids) && p.planillas_ids.length)
        return p.planillas_ids;
    const m = (event.id || "").match(/planilla-(\d+)/);
    return m ? [Number(m[1])] : [];
}

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
async function asignarCodigoSalida(salidaId, codigoActual = "", calendar) {
    try {
        closeMenu();
    } catch (_) {}

    // Validación temprana
    if (!salidaId) {
        return Swal.fire("⚠️", "ID de salida inválido.", "warning");
    }

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
    if (!routeTmpl) {
        return Swal.fire(
            "⚠️",
            "No está configurada la ruta de código SAGE.",
            "warning"
        );
    }

    const url = routeTmpl.replace("__ID__", String(salidaId));
    const payload = { codigo: value.trim() }; // si tu backend espera `codigo_sage`, cambia a { codigo_sage: ... }

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
// util pequeña para normalizar IDs
function normalizarIds(input) {
    if (!input) return [];
    // admite Set, Array, string "1,2,3"
    if (typeof input === "string") {
        return input
            .split(",")
            .map((s) => s.trim())
            .filter(Boolean);
    }
    const arr = Array.from(input);
    return arr
        .map((x) => (typeof x === "object" && x?.id != null ? x.id : x)) // por si vienen objetos
        .map(String)
        .map((s) => s.trim())
        .filter(Boolean);
}

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

export function attachEventoContextMenu(info, calendar) {
    // Limpia cualquier menú al montar/redibujar
    info.el.addEventListener("mousedown", closeMenu);

    info.el.addEventListener("contextmenu", (e) => {
        e.preventDefault();
        e.stopPropagation();

        const ev = info.event;
        const p = ev.extendedProps || {};
        const tipo = p.tipo || "planilla";

        // Cabecera del menú (bonito)
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
            // fallback genérico
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
