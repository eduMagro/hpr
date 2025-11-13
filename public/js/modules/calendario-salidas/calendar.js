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

            /* Evitar que los eventos se agrupen en columnas en vista mensual */
            dayMaxEventRows: false,
            dayMaxEvents: false,

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

                    // Limpiar resúmenes personalizados previos al cambiar de vista
                    limpiarResumenesCustom();

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
                // Cuando termina de cargar eventos, actualizar resumen diario si estamos en vista diaria
                if (!isLoading && calendar) {
                    const viewType = calendar.view.type;
                    if (viewType === "resourceTimeGridDay") {
                        // Pequeño delay para asegurar que el DOM está listo
                        setTimeout(() => mostrarResumenDiario(), 150);
                    }
                }
            },
            viewDidMount: (info) => {
                // Limpiar resúmenes al montar nueva vista
                limpiarResumenesCustom();

                // Solo mostrar resumen custom en vista diaria
                if (info.view.type === "resourceTimeGridDay") {
                    setTimeout(() => mostrarResumenDiario(), 100);
                }

                // Forzar ancho completo en vista mensual (excluyendo resumen)
                if (info.view.type === "dayGridMonth") {
                    setTimeout(() => {
                        document.querySelectorAll('.fc-daygrid-event-harness').forEach(harness => {
                            // No aplicar a eventos de resumen
                            const event = harness.querySelector('.evento-resumen-diario');
                            if (!event) {
                                harness.style.setProperty('width', '100%', 'important');
                                harness.style.setProperty('max-width', '100%', 'important');
                                harness.style.setProperty('position', 'static', 'important');
                                harness.style.setProperty('left', 'unset', 'important');
                                harness.style.setProperty('right', 'unset', 'important');
                                harness.style.setProperty('top', 'unset', 'important');
                                harness.style.setProperty('inset', 'unset', 'important');
                                harness.style.setProperty('margin', '0 0 2px 0', 'important');
                            }
                        });
                        document.querySelectorAll('.fc-daygrid-event:not(.evento-resumen-diario)').forEach(event => {
                            event.style.setProperty('width', '100%', 'important');
                            event.style.setProperty('max-width', '100%', 'important');
                            event.style.setProperty('margin', '0', 'important');
                            event.style.setProperty('position', 'static', 'important');
                            event.style.setProperty('left', 'unset', 'important');
                            event.style.setProperty('right', 'unset', 'important');
                            event.style.setProperty('inset', 'unset', 'important');
                        });
                    }, 50);
                }
            },
            eventContent: (arg) => {
                const bg = arg.event.backgroundColor || "#9CA3AF";
                const p = arg.event.extendedProps || {};
                const viewType = calendar?.view?.type;

                // Renderizado especial para resumen diario
                if (p.tipo === "resumen-dia") {
                    const peso = Number(p.pesoTotal || 0).toLocaleString(undefined, {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0,
                    });
                    const longitud = Number(p.longitudTotal || 0).toLocaleString(undefined, {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0,
                    });
                    const diametro = p.diametroMedio
                        ? Number(p.diametroMedio).toFixed(1)
                        : null;

                    // En vista timeline semanal, formato más compacto
                    if (viewType === "resourceTimelineWeek") {
                        let resumenHtml = `
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${peso} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${longitud} m</div>
                                ${
                                    diametro
                                        ? `<div class="text-yellow-800">⌀ ${diametro} mm</div>`
                                        : ""
                                }
                            </div>
                        `;
                        return { html: resumenHtml };
                    }

                    // En vista mensual, mismo formato compacto
                    if (viewType === "dayGridMonth") {
                        let resumenHtml = `
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${peso} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${longitud} m</div>
                                ${
                                    diametro
                                        ? `<div class="text-yellow-800">⌀ ${diametro} mm</div>`
                                        : ""
                                }
                            </div>
                        `;
                        return { html: resumenHtml };
                    }
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
                const p = info.event.extendedProps || {};

                // Si es un evento de resumen, no aplicar tooltips ni menú contextual
                if (p.tipo === "resumen-dia") {
                    // Asegurar que tenga la clase correcta
                    info.el.classList.add("evento-resumen-diario");
                    // No permitir interacción
                    info.el.style.cursor = "default";
                    return; // Salir temprano
                }

                // Forzar ancho completo en vista mensual (solo para eventos normales, no resumen)
                if (info.view.type === "dayGridMonth") {
                    // Forzar en el contenedor padre (harness)
                    const harness = info.el.closest('.fc-daygrid-event-harness');
                    if (harness) {
                        harness.style.setProperty('width', '100%', 'important');
                        harness.style.setProperty('max-width', '100%', 'important');
                        harness.style.setProperty('position', 'static', 'important');
                        harness.style.setProperty('left', 'unset', 'important');
                        harness.style.setProperty('right', 'unset', 'important');
                        harness.style.setProperty('top', 'unset', 'important');
                        harness.style.setProperty('inset', 'unset', 'important');
                        harness.style.setProperty('margin', '0 0 2px 0', 'important');
                    }

                    // Forzar en el elemento del evento
                    info.el.style.setProperty('width', '100%', 'important');
                    info.el.style.setProperty('max-width', '100%', 'important');
                    info.el.style.setProperty('margin', '0', 'important');
                    info.el.style.setProperty('position', 'static', 'important');
                    info.el.style.setProperty('left', 'unset', 'important');
                    info.el.style.setProperty('right', 'unset', 'important');
                    info.el.style.setProperty('inset', 'unset', 'important');
                }

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

                // tooltips + menú contextual (solo para eventos que no son resumen)
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
                const tipo = draggedEvent.extendedProps?.tipo;
                // No permitir mover eventos de resumen ni festivos
                if (tipo === "resumen-dia" || tipo === "festivo") {
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
            // slotLabelContent para vista semanal timeline - solo muestra fecha
            slotLabelContent: (arg) => {
                const viewType = calendar?.view?.type;

                // Solo aplicar en vista timeline semanal
                if (viewType !== "resourceTimelineWeek") {
                    return null; // Usar formato por defecto
                }

                const fecha = new Date(arg.date);
                const diaTexto = fecha.toLocaleDateString("es-ES", {
                    weekday: "short",
                    day: "numeric",
                    month: "short",
                });

                // Mostrar solo la fecha (el resumen aparece como evento en fila especial)
                return {
                    html: `<div class="text-center font-bold text-sm py-2">${diaTexto}</div>`,
                };
            },
            dayHeaderContent: (arg) => {
                // dayHeaderContent solo se usa en vista diaria para columnas de recursos
                // Mostrar solo la fecha simple
                const fecha = new Date(arg.date);
                const diaTexto = fecha.toLocaleDateString("es-ES", {
                    weekday: "short",
                    day: "numeric",
                    month: "short",
                });

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

    // Función para limpiar todos los resúmenes personalizados
    function limpiarResumenesCustom() {
        // Limpiar todos los resúmenes diarios custom que puedan existir
        const resumenesCustom = document.querySelectorAll(".resumen-diario-custom");
        resumenesCustom.forEach((elem) => elem.remove());
    }

    // Función para mostrar resumen diario arriba del calendario (solo vista diaria)
    function mostrarResumenDiario() {
        // Solo ejecutar en vista diaria
        if (!calendar || calendar.view.type !== "resourceTimeGridDay") {
            limpiarResumenesCustom();
            return;
        }

        // Limpiar resúmenes previos primero
        limpiarResumenesCustom();

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
                        <div class="text-yellow-900">📦 Peso: ${peso} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${longitud} m</div>
                        ${
                            diametro
                                ? `<div class="text-yellow-800">⌀ Diámetro: ${diametro} mm</div>`
                                : ""
                        }
                    </div>
                </div>
            `;

            // Verificar que el elemento calendario y su padre existen
            if (el && el.parentNode) {
                // Insertar antes del calendario
                el.parentNode.insertBefore(resumenDiv, el);
            }
        }
    }

    // Exponer funciones para uso global
    window.mostrarResumenDiario = mostrarResumenDiario;
    window.limpiarResumenesCustom = limpiarResumenesCustom;

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
