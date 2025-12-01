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
            eventDragStart: (info) => {
                const p = info.event.extendedProps || {};
                const bg = info.event.backgroundColor || '#6b7280';

                // Inyectar CSS para ocultar el mirror de FC y mostrar el nuestro
                const style = document.createElement('style');
                style.id = 'custom-drag-style';
                style.textContent = `
                    .fc-event-mirror { display: none !important; }
                    .fc-event-dragging { opacity: 0.3 !important; }
                `;
                document.head.appendChild(style);

                // Crear nuestro propio elemento visual de arrastre
                const customMirror = document.createElement('div');
                customMirror.id = 'custom-drag-mirror';
                customMirror.style.cssText = `
                    position: fixed;
                    width: 150px;
                    padding: 8px 12px;
                    background: ${bg};
                    border: 2px dashed white;
                    border-radius: 6px;
                    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
                    z-index: 99999;
                    pointer-events: none;
                    font-size: 12px;
                    color: #000;
                    font-weight: 500;
                    overflow: hidden;
                    max-height: 80px;
                `;

                const tipoTexto = p.tipo === 'salida' ? '🚚 Salida' : '📦 Planillas';
                const titulo = p.tipo === 'salida'
                    ? (p.codigo_salida || 'Salida')
                    : (p.cod_obra || 'Planillas');
                customMirror.innerHTML = `
                    <div style="font-weight:bold; margin-bottom:4px;">${tipoTexto}</div>
                    <div style="font-size:11px;">${titulo}</div>
                `;
                document.body.appendChild(customMirror);

                // Crear indicador de fecha en la parte superior
                const dragIndicator = document.createElement('div');
                dragIndicator.id = 'drag-indicator';
                dragIndicator.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 z-[99999] bg-gray-900 text-white px-4 py-2 rounded-lg shadow-xl text-sm font-medium';
                const fechaOriginal = info.event.start.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
                dragIndicator.innerHTML = `
                    <div class="flex items-center gap-3">
                        <span class="text-yellow-400">${fechaOriginal}</span>
                        <span class="text-gray-400">→</span>
                        <span id="drag-dest-date" class="text-green-400">...</span>
                    </div>
                `;
                document.body.appendChild(dragIndicator);

                // Mover el mirror custom con el mouse
                const moveCustomMirror = (e) => {
                    customMirror.style.left = (e.clientX + 10) + 'px';
                    customMirror.style.top = (e.clientY + 10) + 'px';

                    // Actualizar fecha destino
                    const destDateEl = document.getElementById('drag-dest-date');
                    if (destDateEl) {
                        const elemUnder = document.elementFromPoint(e.clientX, e.clientY);
                        if (elemUnder) {
                            const dayCell = elemUnder.closest('[data-date]');
                            if (dayCell) {
                                const dateStr = dayCell.getAttribute('data-date');
                                if (dateStr) {
                                    const fecha = new Date(dateStr);
                                    destDateEl.textContent = fecha.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
                                }
                            }
                        }
                    }
                };
                document.addEventListener('mousemove', moveCustomMirror);
                window._dragMoveHandler = moveCustomMirror;

                // Posicionar inicialmente
                moveCustomMirror({ clientX: info.jsEvent.clientX, clientY: info.jsEvent.clientY });
            },
            eventDragStop: () => {
                // Eliminar elementos custom
                const customMirror = document.getElementById('custom-drag-mirror');
                if (customMirror) customMirror.remove();

                const indicator = document.getElementById('drag-indicator');
                if (indicator) indicator.remove();

                const style = document.getElementById('custom-drag-style');
                if (style) style.remove();

                // Limpiar listener
                if (window._dragMoveHandler) {
                    document.removeEventListener('mousemove', window._dragMoveHandler);
                    delete window._dragMoveHandler;
                }
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
                        const nuevaFecha = info.event.start;
                        const fechaISO = nuevaFecha.toISOString().split("T")[0];
                        actualizarTotales(fechaISO);
                        safeUpdateSize();
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
            views: {
                resourceTimelineWeek: {
                    slotDuration: { days: 1 },
                    slotLabelFormat: [
                        { weekday: "short", day: "numeric", month: "short" },
                    ],
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
            // slotLabelContent para formatear etiquetas de tiempo según vista
            slotLabelContent: (arg) => {
                const viewType = calendar?.view?.type;

                // Vista diaria: mostrar horas
                if (viewType === "resourceTimeGridDay") {
                    const hora = arg.date.toLocaleTimeString("es-ES", {
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: false,
                    });
                    return {
                        html: `<div class="text-sm font-medium text-gray-700 py-1">${hora}</div>`,
                    };
                }

                // Vista timeline semanal: mostrar fecha
                if (viewType === "resourceTimelineWeek") {
                    const fecha = new Date(arg.date);
                    const diaTexto = fecha.toLocaleDateString("es-ES", {
                        weekday: "short",
                        day: "numeric",
                        month: "short",
                    });
                    return {
                        html: `<div class="text-center font-bold text-sm py-2">${diaTexto}</div>`,
                    };
                }

                // Otras vistas: usar formato por defecto
                return null;
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
