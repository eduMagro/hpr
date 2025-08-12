import { dataEvents } from "./eventos.js";
import { dataResources } from "./recursos.js";
import { configurarTooltipsYMenus } from "./tooltips.js";
import { actualizarTotales } from "./totales.js";
import { attachEventoContextMenu } from "./calendario-menu.js";
let currentViewType = "resourceTimelineDay";
export let calendar = null;

/* ▼ util: render cuando el elemento sea visible */
function renderWhenVisible(el, renderFn) {
    // ya visible?
    const isVisible = () =>
        el &&
        el.offsetParent !== null &&
        el.clientWidth > 0 &&
        el.clientHeight >= 0;
    if (isVisible()) return renderFn();

    // IntersectionObserver es lo más fino
    if ("IntersectionObserver" in window) {
        const io = new IntersectionObserver(
            (entries) => {
                const vis = entries.some((e) => e.isIntersecting);
                if (vis) {
                    io.disconnect();
                    renderFn();
                }
            },
            { root: null, threshold: 0.01 }
        );
        io.observe(el);
        return;
    }

    // Fallback: ResizeObserver (si existe)
    if ("ResizeObserver" in window) {
        const ro = new ResizeObserver(() => {
            if (isVisible()) {
                ro.disconnect();
                renderFn();
            }
        });
        ro.observe(el);
        return;
    }

    // Fallback final: polling cortito
    const iv = setInterval(() => {
        if (isVisible()) {
            clearInterval(iv);
            renderFn();
        }
    }, 100);
}

/* ▼ util: tras cambios de datos, asegurar recalculo */
function safeUpdateSize() {
    if (!calendar) return;
    // cola al siguiente frame para que el DOM esté listo
    requestAnimationFrame(() => {
        try {
            calendar.updateSize();
        } catch {}
    });
    // plan B por si el layout tarda en asentarse (tabs, fuentes, etc.)
    setTimeout(() => {
        try {
            calendar.updateSize();
        } catch {}
    }, 150);
}

export function crearCalendario() {
    if (!window.FullCalendar) {
        console.error(
            "FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."
        );
        return null;
    }

    if (calendar) calendar.destroy();

    const vistasValidas = [
        "resourceTimelineDay",
        "resourceTimelineWeek",
        "dayGridMonth",
    ];
    let vistaGuardada = localStorage.getItem("ultimaVistaCalendario");
    if (!vistasValidas.includes(vistaGuardada))
        vistaGuardada = "resourceTimelineDay";
    const fechaGuardada = localStorage.getItem("fechaCalendario");
    let refetchTimer = null;
    const el = document.getElementById("calendario");
    if (!el) {
        console.error("#calendario no encontrado");
        return null;
    }
    // === helpers ===
    function hayFestivoEnFecha(dateStr) {
        if (!calendar) return false;
        return calendar.getEvents().some((ev) => {
            const startISO = (
                ev.startStr ||
                ev.start?.toISOString() ||
                ""
            ).split("T")[0];
            const esFestivo =
                ev.extendedProps?.tipo === "festivo" ||
                (typeof ev.id === "string" && ev.id.startsWith("festivo-"));
            return esFestivo && startISO === dateStr;
        });
    }
    const init = () => {
        calendar = new FullCalendar.Calendar(el, {
            schedulerLicenseKey: "CC-Attribution-NonCommercial-NoDerivatives",
            locale: "es",
            navLinks: true,
            initialView: vistaGuardada,
            initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,

            /* timeline */
            slotMinTime: "06:00:00",
            slotMaxTime: "18:00:00",
            buttonText: {
                today: "Hoy",
                day: "Día",
                week: "Semana",
                month: "Mes",
            },

            /* ▼ muy importante en timeline con muchos recursos/eventos */
            progressiveEventRendering: true,
            expandRows: true,
            height: "auto", // ok, pero recalcamos con updateSize()

            resources: (info, success, failure) => {
                const vt =
                    (info.view && info.view.type) ||
                    calendar?.view?.type ||
                    "resourceTimelineDay";
                dataResources(vt, info)
                    .then((res) => {
                        success(res);
                        safeUpdateSize();
                    })
                    .catch(failure);
            },
            events: (info, success, failure) => {
                const vt =
                    (info.view && info.view.type) ||
                    calendar?.view?.type ||
                    "resourceTimelineDay";
                dataEvents(vt, info)
                    .then((evs) => {
                        success(evs);
                        safeUpdateSize();
                    })
                    .catch(failure);
            },

            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "resourceTimelineDay,resourceTimelineWeek,dayGridMonth",
            },

            datesSet: (info) => {
                try {
                    const fecha = calcularFechaCentral(info);
                    localStorage.setItem("fechaCalendario", fecha);
                    localStorage.setItem(
                        "ultimaVistaCalendario",
                        info.view.type
                    );

                    // actualizar totales
                    setTimeout(() => actualizarTotales(fecha), 0);

                    // ► refetch tras cambio de vista/mes
                    clearTimeout(refetchTimer);
                    refetchTimer = setTimeout(() => {
                        calendar.refetchResources();
                        calendar.refetchEvents();
                        safeUpdateSize();
                    }, 0);
                } catch (e) {
                    console.error("Error en datesSet:", e);
                }
            },

            eventContent: (arg) => {
                const bg = arg.event.backgroundColor || "#9CA3AF";
                const p = arg.event.extendedProps || {};
                let html = `
          <div style="background-color:${bg};color:#fff;" class="rounded px-2 py-1 text-xs font-semibold">
            ${arg.event.title}
        `;
                if (p.tipo === "planilla") {
                    if (p.pesoTotal != null) {
                        html += `<br><span class="text-[10px] font-normal">🧱 ${Number(
                            p.pesoTotal
                        ).toLocaleString()} kg</span>`;
                    }
                    if (p.longitudTotal != null) {
                        html += `<br><span class="text-[10px] font-normal">📏 ${Number(
                            p.longitudTotal
                        ).toLocaleString()} m</span>`;
                    }
                    if (p.diametroMedio != null) {
                        html += `<br><span class="text-[10px] font-normal">⌀ ${p.diametroMedio} mm</span>`;
                    }
                    if (
                        p.tieneSalidas &&
                        Array.isArray(p.salidas_codigos) &&
                        p.salidas_codigos.length > 0
                    ) {
                        html += `<div class="mt-1 text-[10px] font-normal bg-yellow-400 text-black rounded px-1 py-0.5 inline-block">
              🔗 Salidas: ${p.salidas_codigos.join(", ")}
            </div>`;
                    }
                }
                html += `</div>`;
                return { html };
            },

            eventDidMount: (info) => {
                configurarTooltipsYMenus(info, calendar);
                attachEventoContextMenu(info, calendar); // ← menú SOLO aquí
                safeUpdateSize?.();
            },

            // eventClick: (info) => {
            //     const p = info.event.extendedProps || {};
            //     if (p.tipo === "planilla") {
            //         const ids = p.planillas_ids || [];
            //         window.location.href = `{{ url('/salidas/create') }}?planillas=${ids.join(
            //             ","
            //         )}`;
            //     }
            //     if (p.tipo === "salida") {
            //         window.open(
            //             `{{ url('/salidas') }}/${info.event.id}`,
            //             "_blank"
            //         );
            //     }
            // },

            eventDrop: (info) => {
                const p = info.event.extendedProps || {};
                const id = info.event.id;
                const nuevaFechaISO = info.event.start?.toISOString();
                const body = {
                    fecha: nuevaFechaISO,
                    tipo: p.tipo,
                    planillas_ids: p.planillas_ids || [],
                };
                const url = (
                    window.AppSalidas?.routes?.updateItem || ""
                ).replace("__ID__", id);

                fetch(url, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": window.AppSalidas?.csrf,
                    },
                    body: JSON.stringify(body),
                })
                    .then((r) => {
                        if (!r.ok)
                            throw new Error("No se pudo actualizar la fecha.");
                        return r.json();
                    })
                    .then(() => {
                        calendar.refetchEvents();
                        calendar.refetchResources();
                        /* ▼ asegurar que se vea al terminar */
                        safeUpdateSize();
                    })
                    .catch((err) => {
                        console.error("Error:", err);
                        info.revert();
                    });
            },
            dateClick: (info) => {
                const vt = calendar.view.type;

                if (hayFestivoEnFecha(info.dateStr)) {
                    Swal.fire({
                        icon: "info",
                        title: "📅 Día festivo",
                        text: "Los festivos se editan en la planificación de Trabajadores.",
                        confirmButtonText: "Entendido",
                    });
                    return;
                }

                if (vt === "resourceTimelineWeek" || vt === "dayGridMonth") {
                    Swal.fire({
                        title: "📅 Cambiar a vista diaria",
                        text: `¿Quieres ver el día ${info.dateStr}?`,
                        icon: "question",
                        showCancelButton: true,
                        confirmButtonText: "Sí, ver día",
                        cancelButtonText: "No",
                    }).then((res) => {
                        if (res.isConfirmed) {
                            calendar.changeView(
                                "resourceTimelineDay",
                                info.dateStr
                            );
                            safeUpdateSize();
                        }
                    });
                }
            },
            eventMinHeight: 30,
            firstDay: 1,
            views: {
                resourceTimelineWeek: {
                    slotDuration: { days: 1 },
                    slotLabelFormat: {
                        weekday: "long",
                        day: "numeric",
                        month: "short",
                    },
                },
            },
            editable: true,
            resourceAreaColumns: [
                { field: "cod_obra", headerContent: "Código" },
                { field: "title", headerContent: "Obra" },
                { field: "cliente", headerContent: "Cliente" },
            ],
            resourceOrder: "orderIndex",
            /* ▼ por si usas CSS que cambie fuentes/anchos justo al montar */
            windowResize: () => safeUpdateSize(),
        });

        calendar.render();
        safeUpdateSize();
    };

    // ⚠️ Render sólo cuando #calendario sea visible
    renderWhenVisible(el, init);

    // Por si lo muestras en tabs/modales de Bootstrap u otros:
    window.addEventListener("shown.bs.tab", safeUpdateSize);
    window.addEventListener("shown.bs.collapse", safeUpdateSize);
    window.addEventListener("shown.bs.modal", safeUpdateSize);

    return calendar;
}

function calcularFechaCentral(info) {
    if (info.view.type === "dayGridMonth") {
        const mid = new Date(info.start);
        mid.setDate(mid.getDate() + 15);
        return mid.toISOString().split("T")[0];
    }
    if (info.view.type === "resourceTimelineWeek") {
        const mid = new Date(info.start);
        const daysToAdd = Math.floor(
            (info.end - info.start) / (1000 * 60 * 60 * 24) / 2
        );
        mid.setDate(mid.getDate() + daysToAdd);
        return mid.toISOString().split("T")[0];
    }
    return info.startStr.split("T")[0];
}
