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
            navLinkDayClick: (date, jsEvent) => {
                const day = date.getDay();
                const isWeekend = day === 0 || day === 6;
                const viewType = calendar?.view?.type;

                // Si es fin de semana en vista semanal o mensual, expandir/colapsar
                if (isWeekend && (viewType === "resourceTimelineWeek" || viewType === "dayGridMonth")) {
                    jsEvent.preventDefault();

                    let key;
                    if (viewType === "dayGridMonth") {
                        // En vista mensual, usamos 'saturday' o 'sunday' como clave
                        key = day === 6 ? 'saturday' : 'sunday';
                    } else {
                        // En vista semanal, usamos la fecha específica
                        key = date.toISOString().split("T")[0];
                    }

                    // Toggle expandido/colapsado
                    if (!window.expandedWeekendDays) window.expandedWeekendDays = new Set();

                    if (window.expandedWeekendDays.has(key)) {
                        window.expandedWeekendDays.delete(key);
                    } else {
                        window.expandedWeekendDays.add(key);
                    }
                    localStorage.setItem("expandedWeekendDays", JSON.stringify([...window.expandedWeekendDays]));
                    calendar.render();
                    setTimeout(() => window.applyWeekendCollapse?.(), 50);
                    return; // No navegar
                }

                // Para días laborables, navegar a la vista día
                calendar.changeView("resourceTimeGridDay", date);
            },
            initialView: vistaGuardada,
            initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,

            /* Evitar que los eventos se agrupen en columnas en vista mensual */
            dayMaxEventRows: false,
            dayMaxEvents: false,

            /* timeline */
            slotMinTime: "05:00:00",
            slotMaxTime: "20:00:00",
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
                        // Aplicar colapso de fines de semana después de refetch
                        if ((info.view.type === "resourceTimelineWeek" || info.view.type === "dayGridMonth") && window.applyWeekendCollapse) {
                            setTimeout(() => window.applyWeekendCollapse(), 150);
                        }
                    }, 0);
                } catch (e) {
                    console.error("Error en datesSet:", e);
                }
            },
            loading: (isLoading) => {
                // Cuando termina de cargar eventos
                if (!isLoading && calendar) {
                    const viewType = calendar.view.type;
                    if (viewType === "resourceTimeGridDay") {
                        // Pequeño delay para asegurar que el DOM está listo
                        setTimeout(() => mostrarResumenDiario(), 150);
                    }
                    // Aplicar colapso de fines de semana en vista semanal o mensual
                    if ((viewType === "resourceTimelineWeek" || viewType === "dayGridMonth") && window.applyWeekendCollapse) {
                        setTimeout(() => window.applyWeekendCollapse(), 150);
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
                        harness.style.setProperty('min-width', '100%', 'important');
                        harness.style.setProperty('position', 'static', 'important');
                        harness.style.setProperty('left', 'unset', 'important');
                        harness.style.setProperty('right', 'unset', 'important');
                        harness.style.setProperty('top', 'unset', 'important');
                        harness.style.setProperty('inset', 'unset', 'important');
                        harness.style.setProperty('margin', '0 0 2px 0', 'important');
                        harness.style.setProperty('display', 'block', 'important');
                    }

                    // Forzar en el elemento del evento
                    info.el.style.setProperty('width', '100%', 'important');
                    info.el.style.setProperty('max-width', '100%', 'important');
                    info.el.style.setProperty('min-width', '100%', 'important');
                    info.el.style.setProperty('margin', '0', 'important');
                    info.el.style.setProperty('position', 'static', 'important');
                    info.el.style.setProperty('left', 'unset', 'important');
                    info.el.style.setProperty('right', 'unset', 'important');
                    info.el.style.setProperty('inset', 'unset', 'important');
                    info.el.style.setProperty('display', 'block', 'important');

                    // Forzar en todos los hijos
                    info.el.querySelectorAll('*').forEach(child => {
                        child.style.setProperty('width', '100%', 'important');
                        child.style.setProperty('max-width', '100%', 'important');
                    });
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
                    let coincide = false;

                    // Para eventos de salida con múltiples obras
                    if (p.tipo === "salida" && p.obras && Array.isArray(p.obras)) {
                        coincide = p.obras.some(obra => {
                            const cod = (obra.codigo || "").toString().toLowerCase();
                            const nom = (obra.nombre || "").toString().toLowerCase();
                            return (fCod && cod.includes(fCod)) || (fNom && nom.includes(fNom));
                        });
                    } else {
                        // Para eventos de planilla u otros con un solo cod_obra/nombre_obra
                        const cod = (info.event.extendedProps?.cod_obra || "")
                            .toString()
                            .toLowerCase();
                        const nom = (
                            info.event.extendedProps?.nombre_obra ||
                            info.event.title ||
                            ""
                        )
                            .toString()
                            .toLowerCase();

                        coincide =
                            (fCod && cod.includes(fCod)) ||
                            (fNom && nom.includes(fNom));
                    }

                    if (coincide) {
                        info.el.classList.add("evento-filtrado");
                        // Forzar fondo negro en el evento y TODOS sus hijos
                        const colorFondo = '#1f2937';
                        const colorBorde = '#111827';
                        info.el.style.setProperty('background-color', colorFondo, 'important');
                        info.el.style.setProperty('background', colorFondo, 'important');
                        info.el.style.setProperty('border-color', colorBorde, 'important');
                        info.el.style.setProperty('color', 'white', 'important');
                        // Aplicar a TODOS los elementos hijos
                        info.el.querySelectorAll('*').forEach(child => {
                            child.style.setProperty('background-color', colorFondo, 'important');
                            child.style.setProperty('background', colorFondo, 'important');
                            child.style.setProperty('color', 'white', 'important');
                        });
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
            eventDragStart: () => {
                // Forzar ancho en elementos que se arrastran (FC aplica estilos inline)
                const fixDragWidth = () => {
                    document.querySelectorAll('.fc-event-dragging').forEach(el => {
                        el.style.width = '150px';
                        el.style.maxWidth = '150px';
                        el.style.minWidth = '150px';
                        el.style.height = '80px';
                        el.style.maxHeight = '80px';
                        el.style.overflow = 'hidden';
                    });
                    if (window._isDragging) {
                        requestAnimationFrame(fixDragWidth);
                    }
                };
                window._isDragging = true;
                requestAnimationFrame(fixDragWidth);
            },
            eventDragStop: () => {
                window._isDragging = false;
            },
            eventDrop: (info) => {
                const p = info.event.extendedProps || {};
                const id = info.event.id;
                const nuevaFechaISO = info.event.start?.toISOString();
                const body = {
                    fecha: nuevaFechaISO,
                    tipo: p.tipo,
                    planillas_ids: p.planillas_ids || [],
                    elementos_ids: p.elementos_ids || [],
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
                    .then((data) => {
                        calendar.refetchEvents();
                        calendar.refetchResources();
                        const nuevaFecha = info.event.start;
                        const fechaISO = nuevaFecha.toISOString().split("T")[0];
                        actualizarTotales(fechaISO);
                        safeUpdateSize();

                        // Mostrar alerta si hay retraso en fabricación
                        if (data.alerta_retraso) {
                            Swal.fire({
                                icon: "warning",
                                title: "⚠️ Fecha de entrega adelantada",
                                html: `
                                    <div class="text-left">
                                        <p class="mb-2">${data.alerta_retraso.mensaje}</p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fin fabricación:</strong> ${data.alerta_retraso.fin_programado}</p>
                                            <p class="text-sm"><strong>Fecha entrega:</strong> ${data.alerta_retraso.fecha_entrega}</p>
                                        </div>
                                        <p class="mt-3 text-sm text-gray-600">Los elementos no estarán listos para la fecha indicada según la programación actual de máquinas.</p>
                                    </div>
                                `,
                                showCancelButton: true,
                                confirmButtonText: "🚀 Adelantar fabricación",
                                cancelButtonText: "Entendido",
                                confirmButtonColor: "#10b981",
                                cancelButtonColor: "#f59e0b",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Simular adelanto
                                    simularAdelantoFabricacion(p.elementos_ids, nuevaFechaISO);
                                }
                            });
                        }
                    })
                    .catch((err) => {
                        console.error("Error:", err);
                        info.revert();
                    });
            },
            dateClick: (info) => {
                // Solo mostrar info de festivo con clic normal
                if (hayFestivoEnFecha(info.dateStr)) {
                    Swal.fire({
                        icon: "info",
                        title: "📅 Día festivo",
                        text: "Los festivos se editan en la planificación de Trabajadores.",
                        confirmButtonText: "Entendido",
                    });
                }
                // La navegación a día específico se hace con clic derecho
            },
            eventMinHeight: 30,
            firstDay: 1,
            // Estado de días colapsados (sábado=6, domingo=0) - Por defecto colapsados
            slotLabelContent: (arg) => {
                const viewType = calendar?.view?.type;
                if (viewType !== "resourceTimelineWeek") return null;

                const date = arg.date;
                if (!date) return null;

                const dayOfWeek = date.getDay(); // 0=domingo, 6=sábado
                const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
                const dateStr = date.toISOString().split("T")[0];

                // Formatear la fecha
                const options = { weekday: "short", day: "numeric", month: "short" };
                const formattedDate = date.toLocaleDateString("es-ES", options);

                if (isWeekend) {
                    // Por defecto colapsados, expandidos solo si están en expandedWeekendDays
                    const isExpanded = window.expandedWeekendDays?.has(dateStr);
                    const isCollapsed = !isExpanded;
                    const icon = isCollapsed ? "▶" : "▼";

                    // Si está colapsado, mostrar solo el día de la semana abreviado
                    const shortLabel = isCollapsed
                        ? date.toLocaleDateString("es-ES", { weekday: "short" }).substring(0, 3)
                        : formattedDate;

                    return {
                        html: `<div class="weekend-header cursor-pointer select-none hover:bg-gray-200 px-1 rounded"
                                    data-date="${dateStr}"
                                    data-collapsed="${isCollapsed}"
                                    title="${isCollapsed ? 'Clic para expandir' : 'Clic para colapsar'}">
                                <span class="collapse-icon text-xs mr-1">${icon}</span>
                                <span class="weekend-label">${shortLabel}</span>
                               </div>`
                    };
                }

                return { html: `<span>${formattedDate}</span>` };
            },
            views: {
                resourceTimelineWeek: {
                    slotDuration: { days: 1 },
                },
                resourceTimeGridDay: {
                    slotDuration: "01:00:00",
                    slotLabelFormat: {
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: false,
                    },
                    slotLabelInterval: "01:00:00",
                    allDaySlot: false,
                },
            },
            editable: true,
            eventDurationEditable: false, // Solo drag, no resize
            eventStartEditable: true,     // Permitir mover eventos
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

        // Inicializar estado de días colapsados desde localStorage
        // Por defecto, los fines de semana están colapsados (true = colapsado por defecto)
        const savedExpanded = localStorage.getItem("expandedWeekendDays");
        window.expandedWeekendDays = new Set(savedExpanded ? JSON.parse(savedExpanded) : []);
        // Para compatibilidad, mantenemos collapsedWeekendDays pero ahora invierte la lógica
        window.weekendDefaultCollapsed = true; // Por defecto colapsados

        // Función para verificar si una fecha es fin de semana
        function isWeekendDate(dateStr) {
            const date = new Date(dateStr + 'T00:00:00');
            const day = date.getDay();
            return day === 0 || day === 6; // domingo o sábado
        }

        // Función para aplicar estilos de colapso a las columnas de fin de semana
        function applyWeekendCollapse() {
            const viewType = calendar?.view?.type;

            // Vista semanal (timeline)
            if (viewType === "resourceTimelineWeek") {
                const slots = document.querySelectorAll('.fc-timeline-slot[data-date]');
                slots.forEach(slot => {
                    const dateStr = slot.getAttribute('data-date');
                    if (isWeekendDate(dateStr)) {
                        const isExpanded = window.expandedWeekendDays?.has(dateStr);
                        if (isExpanded) {
                            slot.classList.remove('weekend-collapsed');
                        } else {
                            slot.classList.add('weekend-collapsed');
                        }
                    }
                });

                // También colapsar las celdas de eventos en esas fechas
                const laneCells = document.querySelectorAll('.fc-timeline-lane td[data-date]');
                laneCells.forEach(cell => {
                    const dateStr = cell.getAttribute('data-date');
                    if (isWeekendDate(dateStr)) {
                        const isExpanded = window.expandedWeekendDays?.has(dateStr);
                        if (isExpanded) {
                            cell.classList.remove('weekend-collapsed');
                        } else {
                            cell.classList.add('weekend-collapsed');
                        }
                    }
                });
            }

            // Vista mensual (dayGrid)
            if (viewType === "dayGridMonth") {
                // Verificar si sábados/domingos están expandidos
                const satExpanded = window.expandedWeekendDays?.has('saturday');
                const sunExpanded = window.expandedWeekendDays?.has('sunday');
                console.log('applyWeekendCollapse - satExpanded:', satExpanded, 'sunExpanded:', sunExpanded);

                // Aplicar a headers (th) - importante para que el ancho sea consistente
                const satHeaders = document.querySelectorAll('.fc-col-header-cell.fc-day-sat');
                const sunHeaders = document.querySelectorAll('.fc-col-header-cell.fc-day-sun');
                console.log('Headers encontrados - sat:', satHeaders.length, 'sun:', sunHeaders.length);

                satHeaders.forEach(header => {
                    if (satExpanded) {
                        header.classList.remove('weekend-day-collapsed');
                    } else {
                        header.classList.add('weekend-day-collapsed');
                    }
                    console.log('Header sat después:', header.classList.contains('weekend-day-collapsed'));
                });
                sunHeaders.forEach(header => {
                    if (sunExpanded) {
                        header.classList.remove('weekend-day-collapsed');
                    } else {
                        header.classList.add('weekend-day-collapsed');
                    }
                });

                // Aplicar a celdas de días (td)
                document.querySelectorAll('.fc-daygrid-day.fc-day-sat').forEach(cell => {
                    if (satExpanded) {
                        cell.classList.remove('weekend-day-collapsed');
                    } else {
                        cell.classList.add('weekend-day-collapsed');
                    }
                });
                document.querySelectorAll('.fc-daygrid-day.fc-day-sun').forEach(cell => {
                    if (sunExpanded) {
                        cell.classList.remove('weekend-day-collapsed');
                    } else {
                        cell.classList.add('weekend-day-collapsed');
                    }
                });

                // Aplicar colgroup para anchos de columna
                const table = document.querySelector('.fc-dayGridMonth-view table');
                if (table) {
                    let colgroup = table.querySelector('colgroup');
                    if (!colgroup) {
                        colgroup = document.createElement('colgroup');
                        for (let i = 0; i < 7; i++) {
                            colgroup.appendChild(document.createElement('col'));
                        }
                        table.insertBefore(colgroup, table.firstChild);
                    }
                    const cols = colgroup.querySelectorAll('col');
                    if (cols.length >= 7) {
                        // Lunes a viernes (índices 0-4), sábado (5), domingo (6)
                        // firstDay = 1 (lunes), así que sábado es índice 5, domingo es 6
                        cols[5].style.width = satExpanded ? '' : '40px';
                        cols[6].style.width = sunExpanded ? '' : '40px';
                    }
                }
            }
        }

        // Función para alternar expansión de un día de fin de semana
        function toggleWeekendCollapse(key) {
            console.log('toggleWeekendCollapse llamado con key:', key);
            console.log('expandedWeekendDays antes:', [...(window.expandedWeekendDays || [])]);

            if (!window.expandedWeekendDays) {
                window.expandedWeekendDays = new Set();
            }

            // Lógica invertida: por defecto colapsados, toggle para expandir
            if (window.expandedWeekendDays.has(key)) {
                // Si estaba expandido, quitarlo (volver a colapsar)
                window.expandedWeekendDays.delete(key);
                console.log('Colapsando:', key);
            } else {
                // Si estaba colapsado, expandirlo
                window.expandedWeekendDays.add(key);
                console.log('Expandiendo:', key);
            }

            console.log('expandedWeekendDays después:', [...window.expandedWeekendDays]);

            // Guardar en localStorage
            localStorage.setItem("expandedWeekendDays", JSON.stringify([...window.expandedWeekendDays]));

            // Aplicar colapso sin re-renderizar todo el calendario
            applyWeekendCollapse();
        }

        // Event listener para clics en encabezados de fin de semana
        el.addEventListener('click', (e) => {
            console.log('Click detectado en:', e.target);

            const weekendHeader = e.target.closest('.weekend-header');
            if (weekendHeader) {
                const dateStr = weekendHeader.getAttribute('data-date');
                console.log('Click en weekend-header, dateStr:', dateStr);
                if (dateStr) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleWeekendCollapse(dateStr);
                    return;
                }
            }

            // Vista mensual: clic en header de sábado/domingo
            const viewType = calendar?.view?.type;
            console.log('Vista actual:', viewType);

            if (viewType === "dayGridMonth") {
                const headerCell = e.target.closest('.fc-col-header-cell.fc-day-sat, .fc-col-header-cell.fc-day-sun');
                console.log('Header cell encontrado:', headerCell);
                if (headerCell) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isSaturday = headerCell.classList.contains('fc-day-sat');
                    const key = isSaturday ? 'saturday' : 'sunday';
                    console.log('Toggling:', key);
                    toggleWeekendCollapse(key);
                    return;
                }

                // También permitir clic en las celdas de días de fin de semana
                const dayCell = e.target.closest('.fc-daygrid-day.fc-day-sat, .fc-daygrid-day.fc-day-sun');
                console.log('Day cell encontrado:', dayCell);
                if (dayCell && !e.target.closest('.fc-event')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isSaturday = dayCell.classList.contains('fc-day-sat');
                    const key = isSaturday ? 'saturday' : 'sunday';
                    console.log('Toggling day:', key);
                    toggleWeekendCollapse(key);
                    return;
                }
            }
        }, true); // Usar capturing para interceptar antes que FullCalendar

        // Aplicar colapso inicial después de que el calendario se renderice
        setTimeout(() => applyWeekendCollapse(), 100);

        // Exponer función para uso global
        window.applyWeekendCollapse = applyWeekendCollapse;

        // Añadir menú contextual para celdas del calendario (clic derecho en día)
        el.addEventListener('contextmenu', (e) => {
            // Buscar si el clic fue en una celda de día
            const dayCell = e.target.closest('.fc-daygrid-day, .fc-timeline-slot, .fc-timegrid-slot, .fc-col-header-cell');
            if (dayCell) {
                // Obtener la fecha de la celda
                let dateStr = dayCell.getAttribute('data-date');
                if (!dateStr) {
                    // Intentar obtener de fc-timeline-slot
                    const slot = e.target.closest('[data-date]');
                    if (slot) dateStr = slot.getAttribute('data-date');
                }

                if (dateStr && calendar) {
                    const vt = calendar.view.type;
                    // Solo mostrar en vistas semanal o mensual
                    if (vt === "resourceTimelineWeek" || vt === "dayGridMonth") {
                        e.preventDefault();
                        e.stopPropagation();

                        Swal.fire({
                            title: "📅 Ir a día",
                            text: `¿Quieres ver el día ${dateStr}?`,
                            icon: "question",
                            showCancelButton: true,
                            confirmButtonText: "Sí, ir al día",
                            cancelButtonText: "Cancelar",
                        }).then((res) => {
                            if (res.isConfirmed) {
                                calendar.changeView("resourceTimeGridDay", dateStr);
                                safeUpdateSize();
                            }
                        });
                    }
                }
            }
        });
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

/**
 * Simula el adelanto de fabricación y muestra opciones al usuario
 */
function simularAdelantoFabricacion(elementosIds, fechaEntrega) {
    if (!elementosIds || elementosIds.length === 0) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "No hay elementos para adelantar",
        });
        return;
    }

    // Mostrar loading
    Swal.fire({
        title: "Analizando opciones...",
        html: "Calculando la mejor posición para adelantar la fabricación",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    fetch("/planificacion/simular-adelanto", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": window.AppSalidas?.csrf,
        },
        body: JSON.stringify({
            elementos_ids: elementosIds,
            fecha_entrega: fechaEntrega,
        }),
    })
        .then((r) => {
            if (!r.ok) throw new Error("Error en la simulación");
            return r.json();
        })
        .then((data) => {
            if (!data.necesita_adelanto) {
                Swal.fire({
                    icon: "info",
                    title: "No es necesario adelantar",
                    text: data.mensaje || "Los elementos llegarán a tiempo.",
                });
                return;
            }

            // Construir HTML con detalles de órdenes a adelantar
            let htmlOrdenes = "";
            if (data.ordenes_a_adelantar && data.ordenes_a_adelantar.length > 0) {
                htmlOrdenes = `
                    <div class="mb-4">
                        <h4 class="font-semibold text-green-700 mb-2">📋 Planillas a adelantar:</h4>
                        <div class="max-h-40 overflow-y-auto">
                            <table class="w-full text-sm border">
                                <thead class="bg-green-100">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Planilla</th>
                                        <th class="px-2 py-1 text-left">Máquina</th>
                                        <th class="px-2 py-1 text-center">Pos. Actual</th>
                                        <th class="px-2 py-1 text-center">Nueva Pos.</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                data.ordenes_a_adelantar.forEach((o) => {
                    htmlOrdenes += `
                        <tr class="border-t">
                            <td class="px-2 py-1">${o.planilla_codigo}</td>
                            <td class="px-2 py-1">${o.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${o.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${o.posicion_nueva}</td>
                        </tr>
                    `;
                });
                htmlOrdenes += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            // Construir HTML con planillas colaterales afectadas
            let htmlColaterales = "";
            if (data.colaterales && data.colaterales.length > 0) {
                htmlColaterales = `
                    <div class="mb-4">
                        <h4 class="font-semibold text-orange-700 mb-2">⚠️ Planillas que se retrasarán:</h4>
                        <div class="max-h-32 overflow-y-auto bg-orange-50 border border-orange-200 rounded p-2">
                            <table class="w-full text-sm">
                                <thead class="bg-orange-100">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Planilla</th>
                                        <th class="px-2 py-1 text-left">Obra</th>
                                        <th class="px-2 py-1 text-left">F. Entrega</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                data.colaterales.forEach((c) => {
                    htmlColaterales += `
                        <tr class="border-t">
                            <td class="px-2 py-1">${c.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${c.obra}</td>
                            <td class="px-2 py-1">${c.fecha_entrega}</td>
                        </tr>
                    `;
                });
                htmlColaterales += `
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-orange-600 mt-1">Estas planillas bajarán una posición en la cola de fabricación.</p>
                    </div>
                `;
            }

            const fechaEntregaStr = data.fecha_entrega || "---";

            Swal.fire({
                icon: "question",
                title: "🚀 Adelantar fabricación",
                html: `
                    <div class="text-left">
                        <p class="mb-3">Para cumplir con la fecha de entrega <strong>${fechaEntregaStr}</strong>, se propone el siguiente cambio:</p>
                        ${htmlOrdenes}
                        ${htmlColaterales}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el adelanto?</p>
                    </div>
                `,
                width: 600,
                showCancelButton: true,
                confirmButtonText: "✅ Ejecutar adelanto",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#10b981",
                cancelButtonColor: "#6b7280",
            }).then((result) => {
                if (result.isConfirmed) {
                    ejecutarAdelantoFabricacion(data.ordenes_a_adelantar);
                }
            });
        })
        .catch((err) => {
            console.error("Error en simulación:", err);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo simular el adelanto. " + err.message,
            });
        });
}

/**
 * Ejecuta el adelanto de fabricación
 */
function ejecutarAdelantoFabricacion(ordenesAAdelantar) {
    if (!ordenesAAdelantar || ordenesAAdelantar.length === 0) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "No hay órdenes para adelantar",
        });
        return;
    }

    // Mostrar loading
    Swal.fire({
        title: "Ejecutando adelanto...",
        html: "Actualizando posiciones en la cola de fabricación",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    // Preparar datos para enviar
    const ordenes = ordenesAAdelantar.map((o) => ({
        planilla_id: o.planilla_id,
        maquina_id: o.maquina_id,
        posicion_nueva: o.posicion_nueva,
    }));

    fetch("/planificacion/ejecutar-adelanto", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": window.AppSalidas?.csrf,
        },
        body: JSON.stringify({ ordenes }),
    })
        .then((r) => {
            if (!r.ok) throw new Error("Error al ejecutar el adelanto");
            return r.json();
        })
        .then((data) => {
            if (data.success) {
                Swal.fire({
                    icon: "success",
                    title: "¡Adelanto ejecutado!",
                    text: data.mensaje || "Las posiciones han sido actualizadas correctamente.",
                    confirmButtonColor: "#10b981",
                }).then(() => {
                    // Refrescar el calendario
                    if (calendar) {
                        calendar.refetchEvents();
                        calendar.refetchResources();
                    }
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.mensaje || "No se pudo ejecutar el adelanto.",
                });
            }
        })
        .catch((err) => {
            console.error("Error al ejecutar adelanto:", err);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo ejecutar el adelanto. " + err.message,
            });
        });
}
