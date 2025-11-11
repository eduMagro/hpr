import { dataEvents } from "./eventos.js";
import { dataResources } from "./recursos.js";
import { configurarTooltipsYMenus } from "./tooltips.js";
import { attachEventoContextMenu } from "./calendario-menu.js";
import { actualizarTotales } from "./totales.js";
let currentViewType = "resourceTimelineWeek";
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
        "resourceTimeGridDay",
        "resourceTimelineWeek",
        "dayGridMonth",
    ];
    let vistaGuardada = localStorage.getItem("ultimaVistaCalendario");
    if (!vistasValidas.includes(vistaGuardada))
        vistaGuardada = "resourceTimeGridDay";
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
                resourceTimeGridDay: "Día",
                resourceTimelineWeek: "Semana",
                dayGridMonth: "Mes",
            },
            /* ▼ muy importante en timeline con muchos recursos/eventos */
            progressiveEventRendering: true,
            expandRows: true,
            height: "auto", // ok, pero recalcamos con updateSize()
            events: (info, success, failure) => {
                const vt =
                    (info.view && info.view.type) ||
                    calendar?.view?.type ||
                    "resourceTimeGridDay";
                dataEvents(vt, info).then(success).catch(failure);
            },
            resources: (info, success, failure) => {
                const vt =
                    (info.view && info.view.type) ||
                    calendar?.view?.type ||
                    "resourceTimeGridDay";
                dataResources(vt, info).then(success).catch(failure);
            },
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "resourceTimeGridDay,resourceTimelineWeek,dayGridMonth",
            },
            eventOrderStrict: true,
            eventOrder: (a, b) => {
                // Resúmenes siempre primero en vista mensual
                const aTipoResumen = a.extendedProps?.tipo === "resumen-dia";
                const bTipoResumen = b.extendedProps?.tipo === "resumen-dia";

                if (aTipoResumen && !bTipoResumen) return -1;
                if (!aTipoResumen && bTipoResumen) return 1;

                // Luego ordenar por código de obra
                const na =
                    parseInt(
                        String(a.extendedProps.cod_obra ?? "").replace(
                            /\D/g,
                            ""
                        ),
                        10
                    ) || 0;
                const nb =
                    parseInt(
                        String(b.extendedProps.cod_obra ?? "").replace(
                            /\D/g,
                            ""
                        ),
                        10
                    ) || 0;
                return na - nb;
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
            loading: (isLoading) => {
                // Cuando termina de cargar eventos, actualizar encabezados y resumen
                if (!isLoading && calendar) {
                    const viewType = calendar.view.type;
                    if (viewType === "resourceTimelineWeek") {
                        // Forzar actualización de encabezados
                        setTimeout(() => {
                            if (calendar) {
                                calendar.render();
                            }
                        }, 50);
                    }
                    if (viewType === "resourceTimeGridDay") {
                        // Actualizar resumen diario
                        setTimeout(() => mostrarResumenDiario(), 100);
                    }
                }
            },
            viewDidMount: (info) => {
                if (info.view.type === "resourceTimeGridDay") {
                    mostrarResumenDiario();
                }
            },
            eventContent: (arg) => {
                const bg = arg.event.backgroundColor || "#9CA3AF";
                const p = arg.event.extendedProps || {};

                // Vista mensual: mostrar resumen diario
                if (
                    p.tipo === "resumen-dia" &&
                    calendar?.view?.type === "dayGridMonth"
                ) {
                    const peso = Number(p.pesoTotal || 0).toLocaleString();
                    const longitud = Number(
                        p.longitudTotal || 0
                    ).toLocaleString();
                    const diametro = p.diametroMedio
                        ? Number(p.diametroMedio).toFixed(2)
                        : null;

                    return {
                        html: `
                        <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-xs w-full">
                            <div class="font-semibold text-yellow-900">📦 ${peso} kg</div>
                            <div class="text-yellow-800">📏 ${longitud} m</div>
                            ${
                                diametro
                                    ? `<div class="text-yellow-800">⌀ ${diametro} mm</div>`
                                    : ""
                            }
                        </div>
                    `,
                    };
                }

                let html = `
        <div style="background-color:${bg}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${arg.event.title}</div>
    `;

                if (p.tipo === "planilla") {
                    // Línea compacta: peso, longitud, diámetro
                    const peso =
                        p.pesoTotal != null
                            ? `📦 ${Number(p.pesoTotal).toLocaleString(
                                  undefined,
                                  {
                                      minimumFractionDigits: 2,
                                      maximumFractionDigits: 2,
                                  }
                              )} kg`
                            : null;

                    const longitud =
                        p.longitudTotal != null
                            ? `📏 ${Number(p.longitudTotal).toLocaleString()} m`
                            : null;

                    const diametro =
                        p.diametroMedio != null
                            ? `⌀ ${Number(p.diametroMedio).toFixed(2)} mm`
                            : null;

                    const partes = [peso, longitud, diametro].filter(Boolean);
                    if (partes.length > 0) {
                        html += `<div class="text-sm text-black font-semibold">${partes.join(
                            " | "
                        )}</div>`;
                    }

                    // Bloque de salidas (si existen)
                    if (
                        p.tieneSalidas &&
                        Array.isArray(p.salidas_codigos) &&
                        p.salidas_codigos.length > 0
                    ) {
                        html += `
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${p.salidas_codigos.join(", ")}
                </span>
            </div>`;
                    }
                }

                html += `</div>`;
                return { html };
            },
            eventDidMount: function (info) {
                // lee filtros actuales (código y nombre)
                const fCod = (
                    document.getElementById("filtro-obra")?.value || ""
                )
                    .trim()
                    .toLowerCase();
                const fNom = (
                    document.getElementById("filtro-nombre-obra")?.value || ""
                )
                    .trim()
                    .toLowerCase();

                // aplica solo si hay algún filtro
                if (fCod || fNom) {
                    const cod = (info.event.extendedProps?.cod_obra || "")
                        .toString()
                        .toLowerCase();
                    // usa extendedProps.nombre_obra; si no llega, cae a title
                    const nom = (
                        info.event.extendedProps?.nombre_obra ||
                        info.event.title ||
                        ""
                    )
                        .toString()
                        .toLowerCase();

                    const coincide =
                        (fCod && cod.includes(fCod)) ||
                        (fNom && nom.includes(fNom));

                    if (coincide) {
                        info.el.classList.add("evento-filtrado");
                    }
                }

                // tooltips + menú contextual
                if (typeof configurarTooltipsYMenus === "function") {
                    configurarTooltipsYMenus(info, calendar);
                }
                if (typeof attachEventoContextMenu === "function") {
                    attachEventoContextMenu(info, calendar);
                }
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

            eventAllow: (dropInfo, draggedEvent) => {
                // No permitir mover eventos de resumen
                if (draggedEvent.extendedProps?.tipo === "resumen-dia") {
                    return false;
                }
                return true;
            },
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
                        // ✅ actualiza totales sin recargar nada
                        const nuevaFecha = info.event.start;
                        const fechaISO = nuevaFecha.toISOString().split("T")[0];
                        actualizarTotales(fechaISO);

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

                if (
                    vt === "resourceTimelineWeek" ||
                    vt === "dayGridMonth"
                ) {
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
                                "resourceTimeGridDay",
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
                    slotLabelFormat: [
                        { weekday: "short", day: "numeric", month: "short" },
                    ],
                },
                resourceTimeGridDay: {
                    slotDuration: "01:00:00",
                },
            },
            slotLabelContent: (arg) => {
                // Solo para vista timeline semanal
                if (calendar?.view?.type !== "resourceTimelineWeek") {
                    return arg.text;
                }

                // Formatear fecha local
                const year = arg.date.getFullYear();
                const month = String(arg.date.getMonth() + 1).padStart(2, "0");
                const day = String(arg.date.getDate()).padStart(2, "0");
                const dateStr = `${year}-${month}-${day}`;

                const fecha = new Date(arg.date);
                const diaTexto = fecha.toLocaleDateString("es-ES", {
                    weekday: "short",
                    day: "numeric",
                    month: "short",
                });

                // Buscar el resumen del día
                let resumen = null;

                try {
                    if (calendar && calendar.getEvents) {
                        resumen = calendar.getEvents().find((ev) => {
                            const esFechaCorrecta =
                                ev.extendedProps?.fecha === dateStr;
                            const esTipoResumen =
                                ev.extendedProps?.tipo === "resumen-dia";
                            return esTipoResumen && esFechaCorrecta;
                        });
                    }
                } catch (e) {
                    // Si hay error, continuar sin resumen
                }

                if (resumen && resumen.extendedProps) {
                    const peso = Number(
                        resumen.extendedProps.pesoTotal || 0
                    ).toLocaleString();
                    const longitud = Number(
                        resumen.extendedProps.longitudTotal || 0
                    ).toLocaleString();
                    const diametro = resumen.extendedProps.diametroMedio
                        ? Number(resumen.extendedProps.diametroMedio).toFixed(2)
                        : null;

                    let resumenHtml = `
                        <div class="text-center py-2">
                            <div class="font-bold text-sm mb-2">${diaTexto}</div>
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-xs">
                                <div class="font-semibold text-yellow-900">📦 ${peso} kg</div>
                                <div class="text-yellow-800">📏 ${longitud} m</div>
                                ${
                                    diametro
                                        ? `<div class="text-yellow-800">⌀ ${diametro} mm</div>`
                                        : ""
                                }
                            </div>
                        </div>
                    `;
                    return { html: resumenHtml };
                }

                // Sin resumen, solo mostrar fecha
                return {
                    html: `<div class="text-center font-bold text-sm py-2">${diaTexto}</div>`,
                };
            },
            dayHeaderContent: (arg) => {
                // Formatear fecha local
                const year = arg.date.getFullYear();
                const month = String(arg.date.getMonth() + 1).padStart(2, "0");
                const day = String(arg.date.getDate()).padStart(2, "0");
                const dateStr = `${year}-${month}-${day}`;

                const fecha = new Date(arg.date);
                const diaTexto = fecha.toLocaleDateString("es-ES", {
                    weekday: "short",
                    day: "numeric",
                    month: "short",
                });

                // Buscar el resumen del día
                let resumen = null;

                try {
                    if (calendar && calendar.getEvents) {
                        resumen = calendar.getEvents().find((ev) => {
                            const esFechaCorrecta =
                                ev.extendedProps?.fecha === dateStr;
                            const esTipoResumen =
                                ev.extendedProps?.tipo === "resumen-dia";
                            return esTipoResumen && esFechaCorrecta;
                        });
                    }
                } catch (e) {
                    // Si hay error, continuar sin resumen
                }

                if (resumen && resumen.extendedProps) {
                    const peso = Number(
                        resumen.extendedProps.pesoTotal || 0
                    ).toLocaleString();
                    const longitud = Number(
                        resumen.extendedProps.longitudTotal || 0
                    ).toLocaleString();
                    const diametro = resumen.extendedProps.diametroMedio
                        ? Number(resumen.extendedProps.diametroMedio).toFixed(2)
                        : null;

                    let resumenHtml = `
                        <div class="text-center py-2">
                            <div class="font-bold text-base mb-2">${diaTexto}</div>
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-3 py-2 text-xs mx-1">
                                <div class="font-semibold text-yellow-900">📦 ${peso} kg</div>
                                <div class="text-yellow-800">📏 ${longitud} m</div>
                                ${
                                    diametro
                                        ? `<div class="text-yellow-800">⌀ ${diametro} mm</div>`
                                        : ""
                                }
                            </div>
                        </div>
                    `;
                    return { html: resumenHtml };
                }

                // Si no hay resumen, mostrar solo la fecha
                return {
                    html: `<div class="text-center font-bold text-base py-2">${diaTexto}</div>`,
                };
            },
            editable: true,
            resourceAreaColumns: [
                { field: "cod_obra", headerContent: "Código" },
                { field: "title", headerContent: "Obra" },
                { field: "cliente", headerContent: "Cliente" },
            ],
            resourceAreaHeaderContent: "Obras",
            resourceOrder: "orderIndex",
            resourceLabelContent: (arg) => {
                // Mostrar código y nombre de obra en el encabezado de la columna
                return {
                    html: `<div class="text-xs font-semibold">
                        <div class="text-blue-600">${arg.resource.extendedProps.cod_obra || ''}</div>
                        <div class="text-gray-700 truncate">${arg.resource.title || ''}</div>
                        <div class="text-gray-500 text-[10px] truncate">${arg.resource.extendedProps.cliente || ''}</div>
                    </div>`
                };
            },
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

    // Función para mostrar resumen diario arriba del calendario
    function mostrarResumenDiario() {
        if (!calendar || calendar.view.type !== "resourceTimeGridDay") return;

        // Buscar evento de resumen del día actual
        const currentDate = calendar.getDate();
        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, "0");
        const day = String(currentDate.getDate()).padStart(2, "0");
        const dateStr = `${year}-${month}-${day}`;

        const resumen = calendar.getEvents().find((ev) => {
            return (
                ev.extendedProps?.tipo === "resumen-dia" &&
                ev.extendedProps?.fecha === dateStr
            );
        });

        // Remover resumen anterior si existe
        const oldResumen = document.querySelector(".resumen-diario-custom");
        if (oldResumen) {
            oldResumen.remove();
        }

        if (resumen && resumen.extendedProps) {
            const peso = Number(
                resumen.extendedProps.pesoTotal || 0
            ).toLocaleString();
            const longitud = Number(
                resumen.extendedProps.longitudTotal || 0
            ).toLocaleString();
            const diametro = resumen.extendedProps.diametroMedio
                ? Number(resumen.extendedProps.diametroMedio).toFixed(2)
                : null;

            // Crear elemento HTML
            const resumenDiv = document.createElement("div");
            resumenDiv.className = "resumen-diario-custom";
            resumenDiv.innerHTML = `
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 ${peso} kg</div>
                        <div class="text-yellow-800">📏 ${longitud} m</div>
                        ${
                            diametro
                                ? `<div class="text-yellow-800">⌀ ${diametro} mm</div>`
                                : ""
                        }
                    </div>
                </div>
            `;

            // Insertar antes del calendario
            el.parentNode.insertBefore(resumenDiv, el);
        }
    }

    // Exponer función para uso global
    window.mostrarResumenDiario = mostrarResumenDiario;

    return calendar;
}

function calcularFechaCentral(info) {
    if (info.view.type === "dayGridMonth") {
        const mid = new Date(info.start);
        mid.setDate(mid.getDate() + 15);
        return mid.toISOString().split("T")[0];
    }
    if (info.view.type === "resourceTimeGridWeek" || info.view.type === "resourceTimelineWeek") {
        const mid = new Date(info.start);
        const daysToAdd = Math.floor(
            (info.end - info.start) / (1000 * 60 * 60 * 24) / 2
        );
        mid.setDate(mid.getDate() + daysToAdd);
        return mid.toISOString().split("T")[0];
    }
    return info.startStr.split("T")[0];
}
