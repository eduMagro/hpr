import { dataEvents, dataResources, updateTotalesFromCache, invalidateCache } from "./eventos.js";
import { configurarTooltipsYMenus } from "./tooltips.js";
import { attachEventoContextMenu } from "./calendario-menu.js";
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

/* ▼ Helpers para mejorar el drag en vista diaria */

// Crear imagen de drag transparente para ocultar el ghost nativo del navegador
function createTransparentDragImage() {
    let img = document.getElementById('transparent-drag-image');
    if (!img) {
        img = document.createElement('img');
        img.id = 'transparent-drag-image';
        // 1x1 pixel transparente
        img.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        img.style.cssText = 'position: fixed; left: -9999px; top: -9999px; width: 1px; height: 1px; opacity: 0;';
        document.body.appendChild(img);
    }
    return img;
}

// Crear ghost personalizado que sigue el cursor (solución robusta)
function createCustomDragGhost(event, sourceEl) {
    removeCustomDragGhost();

    const ghost = document.createElement('div');
    ghost.id = 'custom-drag-ghost';
    ghost.className = 'custom-drag-ghost';

    const p = event.extendedProps || {};
    const esSalida = p.tipo === 'salida';
    const tipoIcon = esSalida ? '🚚' : '📋';
    const tipoLabel = esSalida ? 'Salida' : 'Planilla';
    const codObra = p.cod_obra || '';
    const nombreObra = p.nombre_obra || event.title?.split('\n')[0] || '';
    const cliente = p.cliente || '';
    const peso = p.pesoTotal ? Number(p.pesoTotal).toLocaleString('es-ES', { maximumFractionDigits: 0 }) : '';
    const longitud = p.longitudTotal ? Number(p.longitudTotal).toLocaleString('es-ES', { maximumFractionDigits: 0 }) : '';
    const diametro = p.diametroMedio ? Number(p.diametroMedio).toFixed(1) : '';
    const bgColor = sourceEl?.style?.backgroundColor || event.backgroundColor || '#6366f1';

    ghost.innerHTML = `
        <div class="ghost-card" style="--ghost-color: ${bgColor};">
            <!-- Tipo -->
            <div class="ghost-type-badge ${esSalida ? 'badge-salida' : 'badge-planilla'}">
                <span>${tipoIcon}</span>
                <span>${tipoLabel}</span>
            </div>

            <!-- Info principal -->
            <div class="ghost-main">
                ${codObra ? `<div class="ghost-code">${codObra}</div>` : ''}
                ${nombreObra ? `<div class="ghost-name">${nombreObra}</div>` : ''}
                ${cliente ? `<div class="ghost-client">👤 ${cliente}</div>` : ''}
            </div>

            <!-- Métricas -->
            ${(peso || longitud || diametro) ? `
            <div class="ghost-metrics">
                ${peso ? `<span class="ghost-metric">📦 ${peso} kg</span>` : ''}
                ${longitud ? `<span class="ghost-metric">📏 ${longitud} m</span>` : ''}
                ${diametro ? `<span class="ghost-metric">⌀ ${diametro} mm</span>` : ''}
            </div>
            ` : ''}

            <!-- Destino del drop -->
            <div class="ghost-destination">
                <span class="ghost-dest-date">--</span>
            </div>
        </div>
    `;

    document.body.appendChild(ghost);
    return ghost;
}

// Actualizar posición del ghost personalizado
function updateCustomDragGhost(x, y, timeStr, dateStr) {
    const ghost = document.getElementById('custom-drag-ghost');
    if (!ghost) return;

    // Posicionar junto al cursor con offset
    ghost.style.left = `${x + 20}px`;
    ghost.style.top = `${y - 20}px`;

    // Actualizar hora
    if (timeStr) {
        const timeValue = ghost.querySelector('.ghost-dest-time');
        if (timeValue) {
            timeValue.textContent = timeStr;
        }
    }

    // Actualizar fecha
    if (dateStr) {
        const dateValue = ghost.querySelector('.ghost-dest-date');
        if (dateValue) {
            // Formatear fecha a formato legible
            const date = new Date(dateStr + 'T00:00:00');
            const options = { weekday: 'short', day: 'numeric', month: 'short' };
            dateValue.textContent = date.toLocaleDateString('es-ES', options);
        }
    }
}

// Remover ghost personalizado
function removeCustomDragGhost() {
    const ghost = document.getElementById('custom-drag-ghost');
    if (ghost) ghost.remove();
}

// Calcular hora basada en posición Y
function calculateTimeFromY(clientY, calendarEl) {
    const timeGrid = calendarEl?.querySelector('.fc-timegrid-slots');
    if (!timeGrid) return null;

    const gridRect = timeGrid.getBoundingClientRect();
    const relativeY = clientY - gridRect.top + timeGrid.scrollTop;
    const gridHeight = timeGrid.scrollHeight || gridRect.height;

    const startHour = 5;
    const endHour = 20;
    const totalHours = endHour - startHour;

    const hoursFromTop = (relativeY / gridHeight) * totalHours;
    const totalMinutes = (startHour * 60) + (hoursFromTop * 60);

    // Snap a 30 minutos
    const snappedMinutes = Math.round(totalMinutes / 30) * 30;
    const hours = Math.max(startHour, Math.min(endHour - 1, Math.floor(snappedMinutes / 60)));
    const minutes = snappedMinutes % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

// Highlight de zonas de drop válidas
function highlightDropZones(enable) {
    const slots = document.querySelectorAll('.fc-timegrid-slot, .fc-timegrid-col');

    if (enable) {
        slots.forEach(slot => {
            slot.classList.add('fc-drop-zone-highlight');
        });
    } else {
        slots.forEach(slot => {
            slot.classList.remove('fc-drop-zone-highlight');
        });
    }
}

export function crearCalendario() {
    if (!window.FullCalendar) {
        console.error(
            "FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."
        );
        return null;
    }

    // ✅ INYECTAR ESTILOS GLOBALES para ocultar el ghost de FullCalendar SIEMPRE
    if (!document.getElementById('fc-mirror-hide-style-global')) {
        const globalStyle = document.createElement('style');
        globalStyle.id = 'fc-mirror-hide-style-global';
        globalStyle.textContent = `
            /* Ocultar el elemento que FullCalendar mueve con position:fixed durante el drag */
            .fc-event-dragging[style*="position: fixed"],
            .fc-event-dragging[style*="position:fixed"],
            .fc-event.fc-event-dragging[style*="fixed"],
            a.fc-event.fc-event-dragging {
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }

            /* Ocultar completamente el mirror de FullCalendar */
            .fc-event-mirror,
            .fc .fc-event-mirror,
            .fc-timegrid-event.fc-event-mirror,
            .fc-daygrid-event.fc-event-mirror,
            .fc-timeline-event.fc-event-mirror,
            .fc-timegrid-event-harness.fc-event-mirror,
            .fc-daygrid-event-harness .fc-event-mirror,
            [class*="fc-event-mirror"],
            .fc-event.fc-event-mirror {
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
        `;
        document.head.appendChild(globalStyle);
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

                    // ► refetch tras cambio de vista/mes (una sola petición trae eventos + recursos + totales)
                    clearTimeout(refetchTimer);
                    refetchTimer = setTimeout(async () => {
                        // Invalidar cache para forzar nueva petición
                        invalidateCache();
                        calendar.refetchResources();
                        calendar.refetchEvents();

                        // Actualizar totales desde el cache (ya cargados con eventos/recursos)
                        await updateTotalesFromCache(info.view.type, {
                            startStr: info.startStr,
                            endStr: info.endStr
                        });

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
                // Mostrar/ocultar spinner de carga
                const loadingEl = document.getElementById('calendario-loading');
                const loadingText = document.getElementById('loading-text');

                if (loadingEl) {
                    if (isLoading) {
                        loadingEl.classList.remove('hidden');
                        if (loadingText) loadingText.textContent = 'Cargando eventos...';
                    } else {
                        loadingEl.classList.add('hidden');
                    }
                }

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

                // ✅ Desactivar completamente el HTML5 drag nativo para evitar el ghost del navegador
                // FullCalendar usa pointer events internamente, no necesita HTML5 drag
                info.el.setAttribute('draggable', 'false');
                info.el.ondragstart = (e) => {
                    e.preventDefault();
                    return false;
                };

                // Si es un evento de resumen, no aplicar tooltips ni menú contextual
                if (p.tipo === "resumen-dia") {
                    // Asegurar que tenga la clase correcta
                    info.el.classList.add("evento-resumen-diario");
                    // No permitir interacción
                    info.el.style.cursor = "default";
                    return; // Salir temprano
                }

                // Forzar ancho completo en vista mensual (usando clases CSS)
                if (info.view.type === "dayGridMonth") {
                    const harness = info.el.closest('.fc-daygrid-event-harness');
                    if (harness) harness.classList.add('evento-fullwidth');
                    info.el.classList.add('evento-fullwidth-event');
                }

                // lee filtros actuales
                const fCodObra = (document.getElementById("filtro-obra")?.value || "").trim().toLowerCase();
                const fNomObra = (document.getElementById("filtro-nombre-obra")?.value || "").trim().toLowerCase();
                const fCodCliente = (document.getElementById("filtro-cod-cliente")?.value || "").trim().toLowerCase();
                const fCliente = (document.getElementById("filtro-cliente")?.value || "").trim().toLowerCase();
                const fCodPlanilla = (document.getElementById("filtro-cod-planilla")?.value || "").trim().toLowerCase();

                // aplica solo si hay algún filtro
                if (fCodObra || fNomObra || fCodCliente || fCliente || fCodPlanilla) {
                    let coincide = false;

                    // Para eventos de salida con múltiples obras
                    if (p.tipo === "salida" && p.obras && Array.isArray(p.obras)) {
                        coincide = p.obras.some(obra => {
                            const codObra = (obra.codigo || "").toString().toLowerCase();
                            const nomObra = (obra.nombre || "").toString().toLowerCase();
                            const codCli = (obra.cod_cliente || "").toString().toLowerCase();
                            const nomCli = (obra.cliente || "").toString().toLowerCase();

                            // Verificar coincidencias (contain)
                            const matchCodObra = !fCodObra || codObra.includes(fCodObra);
                            const matchNomObra = !fNomObra || nomObra.includes(fNomObra);
                            const matchCodCliente = !fCodCliente || codCli.includes(fCodCliente);
                            const matchCliente = !fCliente || nomCli.includes(fCliente);

                            return matchCodObra && matchNomObra && matchCodCliente && matchCliente;
                        });

                        // Para salidas, también verificar código de planilla si aplica
                        if (fCodPlanilla && p.planillas_codigos && Array.isArray(p.planillas_codigos)) {
                            const matchPlanilla = p.planillas_codigos.some(cod =>
                                (cod || "").toString().toLowerCase().includes(fCodPlanilla)
                            );
                            coincide = coincide && matchPlanilla;
                        }
                    } else {
                        // Para eventos de planilla u otros
                        const codObra = (p.cod_obra || "").toString().toLowerCase();
                        const nomObra = (p.nombre_obra || info.event.title || "").toString().toLowerCase();
                        const codCli = (p.cod_cliente || "").toString().toLowerCase();
                        const nomCli = (p.cliente || "").toString().toLowerCase();

                        // Verificar coincidencias (contain) - AND lógico: todos los filtros deben coincidir
                        const matchCodObra = !fCodObra || codObra.includes(fCodObra);
                        const matchNomObra = !fNomObra || nomObra.includes(fNomObra);
                        const matchCodCliente = !fCodCliente || codCli.includes(fCodCliente);
                        const matchCliente = !fCliente || nomCli.includes(fCliente);

                        // Para código de planilla, buscar en planillas_codigos (array) o en el título
                        let matchCodPlanilla = true;
                        if (fCodPlanilla) {
                            if (p.planillas_codigos && Array.isArray(p.planillas_codigos)) {
                                matchCodPlanilla = p.planillas_codigos.some(cod =>
                                    (cod || "").toString().toLowerCase().includes(fCodPlanilla)
                                );
                            } else {
                                // Fallback: buscar en el título del evento
                                matchCodPlanilla = (info.event.title || "").toLowerCase().includes(fCodPlanilla);
                            }
                        }

                        coincide = matchCodObra && matchNomObra && matchCodCliente && matchCliente && matchCodPlanilla;
                    }

                    if (coincide) {
                        // Solo añadir clase, el CSS se encarga del resto
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
            // Snap a intervalos de 30 minutos en vista diaria
            snapDuration: '00:30:00',
            eventDragStart: (info) => {
                window._isDragging = true;
                window._draggedEvent = info.event;

                // Crear ghost personalizado (solución robusta para el problema del mirror)
                createCustomDragGhost(info.event, info.el);

                // Añadir clase al body para estilos globales durante drag
                // El CSS en estilosCalendarioSalidas.css se encarga de ocultar mirrors
                document.body.classList.add('fc-dragging-active');

                // ✅ Interceptar HTML5 drag para usar imagen transparente
                const transparentImg = createTransparentDragImage();
                const handleNativeDragStart = (e) => {
                    if (e.dataTransfer && window._isDragging) {
                        e.dataTransfer.setDragImage(transparentImg, 0, 0);
                    }
                };
                document.addEventListener('dragstart', handleNativeDragStart, true);
                window._nativeDragStartHandler = handleNativeDragStart;

                // Highlight zona de drop en vista diaria
                const calendarEl = document.getElementById('calendario');
                if (calendar?.view?.type === 'resourceTimeGridDay') {
                    highlightDropZones(true);
                }

                // Función para obtener fecha del elemento bajo el cursor
                const getDateFromPosition = (x, y) => {
                    const elements = document.elementsFromPoint(x, y);
                    for (const el of elements) {
                        // Buscar celda de día en vista mensual
                        const dayCell = el.closest('.fc-daygrid-day');
                        if (dayCell) {
                            return dayCell.getAttribute('data-date');
                        }
                        // Buscar slot en vista timeline
                        const timelineSlot = el.closest('[data-date]');
                        if (timelineSlot) {
                            return timelineSlot.getAttribute('data-date');
                        }
                    }
                    return null;
                };

                // Listener de mouse para actualizar ghost (throttled con RAF)
                let rafPending = false;
                const handleMouseMove = (e) => {
                    if (!window._isDragging || rafPending) return;

                    rafPending = true;
                    requestAnimationFrame(() => {
                        rafPending = false;
                        if (!window._isDragging) return;

                        const timeStr = calculateTimeFromY(e.clientY, calendarEl);
                        const dateStr = getDateFromPosition(e.clientX, e.clientY);
                        updateCustomDragGhost(e.clientX, e.clientY, timeStr, dateStr);
                    });
                };

                document.addEventListener('mousemove', handleMouseMove, { passive: true });
                window._dragMouseMoveHandler = handleMouseMove;

                // Posición inicial
                if (info.jsEvent) {
                    const timeStr = calculateTimeFromY(info.jsEvent.clientY, calendarEl);
                    const dateStr = getDateFromPosition(info.jsEvent.clientX, info.jsEvent.clientY);
                    updateCustomDragGhost(info.jsEvent.clientX, info.jsEvent.clientY, timeStr, dateStr);
                }

                // Guardar posición original para poder revertir
                window._dragOriginalStart = info.event.start;
                window._dragOriginalEnd = info.event.end;
                window._dragEventId = info.event.id;

                // Clic derecho cancela el drag
                const handleContextMenu = (e) => {
                    if (window._isDragging) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();

                        window._cancelDrag = true;
                        removeCustomDragGhost();

                        // Simular mouseup para terminar el drag de FullCalendar
                        const mouseUpEvent = new PointerEvent('pointerup', {
                            bubbles: true,
                            cancelable: true,
                            clientX: e.clientX,
                            clientY: e.clientY
                        });
                        document.dispatchEvent(mouseUpEvent);
                    }
                };
                document.addEventListener('contextmenu', handleContextMenu, { capture: true });
                window._dragContextMenuHandler = handleContextMenu;
            },
            eventDragStop: (info) => {
                window._isDragging = false;
                window._draggedEvent = null;

                // Remover listener de HTML5 drag
                if (window._nativeDragStartHandler) {
                    document.removeEventListener('dragstart', window._nativeDragStartHandler, true);
                    window._nativeDragStartHandler = null;
                }

                // Remover listener de mouse
                if (window._dragMouseMoveHandler) {
                    document.removeEventListener('mousemove', window._dragMouseMoveHandler);
                    window._dragMouseMoveHandler = null;
                }

                // Remover listener de contextmenu
                if (window._dragContextMenuHandler) {
                    document.removeEventListener('contextmenu', window._dragContextMenuHandler, { capture: true });
                    window._dragContextMenuHandler = null;
                }

                // Limpiar datos de posición original
                window._dragOriginalStart = null;
                window._dragOriginalEnd = null;
                window._dragEventId = null;

                // Remover ghost personalizado
                removeCustomDragGhost();

                // Quitar clase del body
                document.body.classList.remove('fc-dragging-active');

                // Quitar highlight de zonas
                highlightDropZones(false);
            },
            eventDrop: (info) => {
                // Si se canceló con clic derecho, revertir a posición original
                if (window._cancelDrag) {
                    window._cancelDrag = false;
                    info.revert();
                    // Por si revert no funciona, restaurar manualmente
                    if (window._dragOriginalStart) {
                        info.event.setStart(window._dragOriginalStart);
                        if (window._dragOriginalEnd) {
                            info.event.setEnd(window._dragOriginalEnd);
                        }
                    }
                    return;
                }

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

                // Mostrar spinner de carga
                Swal.fire({
                    title: 'Actualizando fecha...',
                    html: 'Verificando programación de fabricación',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

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
                    .then(async (data) => {
                        // Cerrar spinner
                        Swal.close();
                        // Invalidar cache y refetch
                        invalidateCache();
                        calendar.refetchEvents();
                        calendar.refetchResources();
                        safeUpdateSize();

                        // Mostrar alerta si hay retraso en fabricación (fecha adelantada)
                        if (data.alerta_retraso) {
                            const esElementosConFechaPropia = data.alerta_retraso.es_elementos_con_fecha_propia || false;
                            const tipoTitulo = esElementosConFechaPropia ? "elementos" : "planilla";

                            Swal.fire({
                                icon: "warning",
                                title: `⚠️ Fecha de entrega adelantada`,
                                html: `
                                    <div class="text-left">
                                        <p class="mb-2">${data.alerta_retraso.mensaje}</p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fin fabricación:</strong> ${data.alerta_retraso.fin_programado}</p>
                                            <p class="text-sm"><strong>Fecha entrega:</strong> ${data.alerta_retraso.fecha_entrega}</p>
                                        </div>
                                        <p class="mt-3 text-sm text-gray-600">Los ${tipoTitulo === 'elementos' ? 'elementos' : 'elementos de la planilla'} no estarán listos para la fecha indicada.</p>
                                    </div>
                                `,
                                showCancelButton: true,
                                confirmButtonText: "🚀 Adelantar fabricación",
                                cancelButtonText: "Entendido",
                                confirmButtonColor: "#10b981",
                                cancelButtonColor: "#f59e0b",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Simular adelanto - pasar flag si son elementos con fecha propia
                                    simularAdelantoFabricacion(
                                        data.alerta_retraso.elementos_ids || p.elementos_ids,
                                        nuevaFechaISO,
                                        esElementosConFechaPropia
                                    );
                                }
                            });
                        }

                        // Mostrar opción de retrasar fabricación (fecha pospuesta)
                        if (data.opcion_posponer) {
                            const esElementosConFechaPropia = data.opcion_posponer.es_elementos_con_fecha_propia || false;
                            const ordenesInfo = data.opcion_posponer.ordenes_afectadas || [];
                            const tipoTitulo = esElementosConFechaPropia ? "Elementos con fecha propia" : "Planilla";

                            let tablaOrdenes = '';
                            if (ordenesInfo.length > 0) {
                                tablaOrdenes = `
                                    <div class="max-h-40 overflow-y-auto mt-3">
                                        <table class="w-full text-sm border">
                                            <thead class="bg-blue-100">
                                                <tr>
                                                    <th class="px-2 py-1 text-left">Planilla</th>
                                                    <th class="px-2 py-1 text-left">Máquina</th>
                                                    <th class="px-2 py-1 text-center">Posición</th>
                                                    ${esElementosConFechaPropia ? '<th class="px-2 py-1 text-center">Elementos</th>' : ''}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${ordenesInfo.map(ord => `
                                                    <tr class="border-t">
                                                        <td class="px-2 py-1">${ord.planilla_codigo}</td>
                                                        <td class="px-2 py-1">${ord.maquina_nombre}</td>
                                                        <td class="px-2 py-1 text-center">${ord.posicion_actual} / ${ord.total_posiciones}</td>
                                                        ${esElementosConFechaPropia ? `<td class="px-2 py-1 text-center">${ord.elementos_count || '-'}</td>` : ''}
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                `;
                            }

                            Swal.fire({
                                icon: "question",
                                title: `📅 ${tipoTitulo} - Fecha pospuesta`,
                                html: `
                                    <div class="text-left">
                                        <p class="mb-2">${data.opcion_posponer.mensaje}</p>
                                        <div class="bg-blue-50 border border-blue-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fecha anterior:</strong> ${data.opcion_posponer.fecha_anterior}</p>
                                            <p class="text-sm"><strong>Nueva fecha:</strong> ${data.opcion_posponer.fecha_nueva}</p>
                                        </div>
                                        ${tablaOrdenes}
                                        <p class="mt-3 text-sm text-gray-600">Al retrasar la fabricación, otras planillas más urgentes podrán avanzar en la cola.</p>
                                    </div>
                                `,
                                showCancelButton: true,
                                confirmButtonText: "⏬ Retrasar fabricación",
                                cancelButtonText: "No, mantener posición",
                                confirmButtonColor: "#3b82f6",
                                cancelButtonColor: "#6b7280",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Simular y ejecutar retraso - pasar nueva fecha y flag
                                    simularRetrasoFabricacion(
                                        data.opcion_posponer.elementos_ids,
                                        esElementosConFechaPropia,
                                        nuevaFechaISO
                                    );
                                }
                            });
                        }
                    })
                    .catch((err) => {
                        Swal.close();
                        console.error("Error:", err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo actualizar la fecha.',
                            timer: 3000,
                        });
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

        // Ocultar spinner inicial después de un breve delay (por si no hay eventos que cargar)
        setTimeout(() => {
            const loadingEl = document.getElementById('calendario-loading');
            if (loadingEl && !loadingEl.classList.contains('opacity-0')) {
                loadingEl.classList.add('opacity-0', 'pointer-events-none');
                loadingEl.classList.remove('opacity-100');
            }
        }, 500);

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

                // Aplicar a headers (th) - importante para que el ancho sea consistente
                const satHeaders = document.querySelectorAll('.fc-col-header-cell.fc-day-sat');
                const sunHeaders = document.querySelectorAll('.fc-col-header-cell.fc-day-sun');

                satHeaders.forEach(header => {
                    if (satExpanded) {
                        header.classList.remove('weekend-day-collapsed');
                    } else {
                        header.classList.add('weekend-day-collapsed');
                    }
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
            if (!window.expandedWeekendDays) {
                window.expandedWeekendDays = new Set();
            }

            // Lógica invertida: por defecto colapsados, toggle para expandir
            if (window.expandedWeekendDays.has(key)) {
                window.expandedWeekendDays.delete(key);
            } else {
                window.expandedWeekendDays.add(key);
            }

            // Guardar en localStorage
            localStorage.setItem("expandedWeekendDays", JSON.stringify([...window.expandedWeekendDays]));

            // Aplicar colapso sin re-renderizar todo el calendario
            applyWeekendCollapse();
        }

        // Event listener para clics en encabezados de fin de semana
        el.addEventListener('click', (e) => {
            const weekendHeader = e.target.closest('.weekend-header');
            if (weekendHeader) {
                const dateStr = weekendHeader.getAttribute('data-date');
                if (dateStr) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleWeekendCollapse(dateStr);
                    return;
                }
            }

            // Vista mensual: clic en header de sábado/domingo
            const viewType = calendar?.view?.type;

            if (viewType === "dayGridMonth") {
                const headerCell = e.target.closest('.fc-col-header-cell.fc-day-sat, .fc-col-header-cell.fc-day-sun');
                if (headerCell) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isSaturday = headerCell.classList.contains('fc-day-sat');
                    const key = isSaturday ? 'saturday' : 'sunday';
                    toggleWeekendCollapse(key);
                    return;
                }

                // También permitir clic en las celdas de días de fin de semana
                const dayCell = e.target.closest('.fc-daygrid-day.fc-day-sat, .fc-daygrid-day.fc-day-sun');
                if (dayCell && !e.target.closest('.fc-event')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isSaturday = dayCell.classList.contains('fc-day-sat');
                    const key = isSaturday ? 'saturday' : 'sunday';
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
            // No mostrar menú si estamos arrastrando un evento
            if (window._isDragging || window._cancelDrag) {
                return;
            }

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
 * @param {Array} elementosIds - IDs de elementos
 * @param {string} fechaEntrega - Fecha de entrega (ISO string)
 * @param {boolean} esElementosConFechaPropia - Si son elementos con fecha_entrega propia
 */
function simularAdelantoFabricacion(elementosIds, fechaEntrega, esElementosConFechaPropia = false) {
    if (!elementosIds || elementosIds.length === 0) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "No hay elementos para adelantar",
        });
        return;
    }

    // Si son elementos con fecha propia, usar endpoint directo de adelanto
    if (esElementosConFechaPropia) {
        ejecutarAdelantoElementos(elementosIds, fechaEntrega);
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

    // Timeout de 60 segundos para cálculos con muchas planillas
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 60000);

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
        signal: controller.signal,
    })
        .then((r) => {
            clearTimeout(timeoutId);
            if (!r.ok) throw new Error("Error en la simulación");
            return r.json();
        })
        .then((data) => {
            if (!data.necesita_adelanto) {
                // Convertir saltos de línea a HTML y mostrar mensaje detallado
                const mensajeHtml = (data.mensaje || "Los elementos llegarán a tiempo.")
                    .replace(/\n/g, '<br>')
                    .replace(/•/g, '<span class="text-amber-600">•</span>');

                // Si hay razones con fin_minimo, ofrecer mover a primera posición de todas formas
                const tieneRazones = data.razones && data.razones.length > 0;
                const puedeAdelantar = tieneRazones && data.razones.some(r => r.fin_minimo);

                if (puedeAdelantar) {
                    Swal.fire({
                        icon: "warning",
                        title: "No se puede entregar a tiempo",
                        html: `
                            <div class="text-left text-sm mb-4">${mensajeHtml}</div>
                            <div class="text-left text-sm font-semibold text-amber-700 border-t pt-3">
                                ¿Deseas adelantar a primera posición de todas formas?
                            </div>
                        `,
                        width: 650,
                        showCancelButton: true,
                        confirmButtonText: "Sí, adelantar a 1ª posición",
                        cancelButtonText: "Cancelar",
                        confirmButtonColor: "#f59e0b",
                        cancelButtonColor: "#6b7280",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Construir órdenes para mover a posición 1
                            // Ahora razones está agrupado por máquina con planillas_ids
                            const ordenesParaAdelantar = [];
                            data.razones
                                .filter(r => r.fin_minimo)
                                .forEach(r => {
                                    // Si tiene planillas_ids (nuevo formato agrupado)
                                    if (r.planillas_ids && r.planillas_ids.length > 0) {
                                        r.planillas_ids.forEach(planillaId => {
                                            ordenesParaAdelantar.push({
                                                planilla_id: planillaId,
                                                maquina_id: r.maquina_id,
                                                posicion_nueva: 1,
                                            });
                                        });
                                    } else if (r.planilla_id) {
                                        // Formato antiguo por si acaso
                                        ordenesParaAdelantar.push({
                                            planilla_id: r.planilla_id,
                                            maquina_id: r.maquina_id,
                                            posicion_nueva: 1,
                                        });
                                    }
                                });

                            if (ordenesParaAdelantar.length > 0) {
                                console.log("Órdenes a adelantar:", ordenesParaAdelantar);
                                ejecutarAdelantoFabricacion(ordenesParaAdelantar);
                            } else {
                                console.warn("No se encontraron órdenes para adelantar", data.razones);
                                Swal.fire({
                                    icon: "warning",
                                    title: "Sin órdenes",
                                    text: "No se encontraron órdenes para adelantar.",
                                });
                            }
                        }
                    });
                } else {
                    Swal.fire({
                        icon: "info",
                        title: "No es necesario adelantar",
                        html: `<div class="text-left text-sm">${mensajeHtml}</div>`,
                        width: 600,
                    });
                }
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
            clearTimeout(timeoutId);
            console.error("Error en simulación:", err);

            // Mensaje específico para timeout
            const isTimeout = err.name === 'AbortError';
            Swal.fire({
                icon: "error",
                title: isTimeout ? "Tiempo agotado" : "Error",
                text: isTimeout
                    ? "El cálculo está tardando demasiado. La operación fue cancelada."
                    : "No se pudo simular el adelanto. " + err.message,
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

    console.log("Enviando órdenes al servidor:", JSON.stringify({ ordenes }, null, 2));

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
            console.log("Respuesta del servidor:", data);

            if (data.success) {
                // Contar éxitos y fallos
                const resultados = data.resultados || [];
                const exitos = resultados.filter(r => r.success);
                const fallos = resultados.filter(r => !r.success);

                let html = data.mensaje || "Las posiciones han sido actualizadas.";
                if (exitos.length > 0) {
                    html += `<br><br><strong>${exitos.length} orden(es) movidas correctamente.</strong>`;
                }
                if (fallos.length > 0) {
                    html += `<br><span class="text-amber-600">${fallos.length} orden(es) no pudieron moverse:</span>`;
                    html += "<ul class='text-left text-sm mt-2'>";
                    fallos.forEach(f => {
                        html += `<li>• Planilla ${f.planilla_id}: ${f.mensaje}</li>`;
                    });
                    html += "</ul>";
                }

                Swal.fire({
                    icon: exitos.length > 0 ? "success" : "warning",
                    title: exitos.length > 0 ? "¡Adelanto ejecutado!" : "Problemas al adelantar",
                    html: html,
                    confirmButtonColor: "#10b981",
                }).then(() => {
                    // Refrescar el calendario
                    if (calendar) {
                        invalidateCache();
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

/**
 * Ejecuta el adelanto de fabricación para elementos con fecha_entrega propia
 * Este endpoint separa los elementos en su propia posición de cola y los adelanta
 * @param {Array} elementosIds - IDs de elementos
 * @param {string} nuevaFechaEntrega - Nueva fecha de entrega (ISO string)
 */
function ejecutarAdelantoElementos(elementosIds, nuevaFechaEntrega) {
    if (!elementosIds || elementosIds.length === 0) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "No hay elementos para adelantar",
        });
        return;
    }

    Swal.fire({
        title: "Ejecutando adelanto...",
        html: "Separando elementos y actualizando posiciones en la cola",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    fetch("/planificacion/ejecutar-adelanto-elementos", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": window.AppSalidas?.csrf,
        },
        body: JSON.stringify({
            elementos_ids: elementosIds,
            nueva_fecha_entrega: nuevaFechaEntrega,
        }),
    })
        .then((r) => {
            if (!r.ok) throw new Error("Error al ejecutar el adelanto");
            return r.json();
        })
        .then((data) => {
            if (data.success) {
                const resultados = data.resultados || [];
                const exitos = resultados.filter(r => r.success);
                const fallos = resultados.filter(r => !r.success);

                let html = data.mensaje || "Las posiciones han sido actualizadas.";
                if (exitos.length > 0) {
                    html += `<br><br><strong>${exitos.length} orden(es) de elementos adelantadas.</strong>`;
                }
                if (fallos.length > 0) {
                    html += `<br><span class="text-amber-600">${fallos.length} orden(es) no pudieron moverse.</span>`;
                }

                Swal.fire({
                    icon: exitos.length > 0 ? "success" : "warning",
                    title: exitos.length > 0 ? "¡Adelanto ejecutado!" : "Problemas al adelantar",
                    html: html,
                    confirmButtonColor: "#10b981",
                }).then(() => {
                    if (calendar) {
                        invalidateCache();
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
            console.error("Error al ejecutar adelanto de elementos:", err);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo ejecutar el adelanto. " + err.message,
            });
        });
}

/**
 * Simula el retraso de fabricación y muestra opciones al usuario
 * @param {Array} elementosIds - IDs de elementos
 * @param {boolean} esElementosConFechaPropia - Si son elementos con fecha_entrega propia
 * @param {string} nuevaFechaEntrega - Nueva fecha de entrega (ISO string)
 */
function simularRetrasoFabricacion(elementosIds, esElementosConFechaPropia = false, nuevaFechaEntrega = null) {
    if (!elementosIds || elementosIds.length === 0) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "No hay elementos para retrasar",
        });
        return;
    }

    // Mostrar loading
    Swal.fire({
        title: "Analizando...",
        html: "Calculando el impacto del retraso en la cola de fabricación",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    fetch("/planificacion/simular-retraso", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": window.AppSalidas?.csrf,
        },
        body: JSON.stringify({
            elementos_ids: elementosIds,
            es_elementos_con_fecha_propia: esElementosConFechaPropia,
        }),
    })
        .then((r) => {
            if (!r.ok) throw new Error("Error en la simulación");
            return r.json();
        })
        .then((data) => {
            if (!data.puede_retrasar) {
                Swal.fire({
                    icon: "info",
                    title: "No se puede retrasar",
                    text: data.mensaje || "Las planillas ya están al final de la cola.",
                });
                return;
            }

            // Construir HTML con detalles de órdenes a retrasar
            let htmlOrdenes = "";
            if (data.ordenes_a_retrasar && data.ordenes_a_retrasar.length > 0) {
                htmlOrdenes = `
                    <div class="mb-4">
                        <h4 class="font-semibold text-blue-700 mb-2">📋 Planillas a retrasar:</h4>
                        <div class="max-h-40 overflow-y-auto">
                            <table class="w-full text-sm border">
                                <thead class="bg-blue-100">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Planilla</th>
                                        <th class="px-2 py-1 text-left">Máquina</th>
                                        <th class="px-2 py-1 text-center">Pos. Actual</th>
                                        <th class="px-2 py-1 text-center">Nueva Pos.</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                data.ordenes_a_retrasar.forEach((o) => {
                    htmlOrdenes += `
                        <tr class="border-t">
                            <td class="px-2 py-1">${o.planilla_codigo}</td>
                            <td class="px-2 py-1">${o.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${o.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-blue-600">${o.posicion_nueva}</td>
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

            // Construir HTML con planillas que se beneficiarán
            let htmlBeneficiados = "";
            if (data.beneficiados && data.beneficiados.length > 0) {
                htmlBeneficiados = `
                    <div class="mb-4">
                        <h4 class="font-semibold text-green-700 mb-2">✅ Planillas que avanzarán:</h4>
                        <div class="max-h-32 overflow-y-auto bg-green-50 border border-green-200 rounded p-2">
                            <table class="w-full text-sm">
                                <thead class="bg-green-100">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Planilla</th>
                                        <th class="px-2 py-1 text-left">Obra</th>
                                        <th class="px-2 py-1 text-center">Nueva Pos.</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                data.beneficiados.slice(0, 10).forEach((b) => {
                    htmlBeneficiados += `
                        <tr class="border-t">
                            <td class="px-2 py-1">${b.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${b.obra}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${b.posicion_nueva}</td>
                        </tr>
                    `;
                });
                htmlBeneficiados += `
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-green-600 mt-1">Estas planillas subirán una posición en la cola.</p>
                    </div>
                `;
            }

            const tituloModal = data.es_elementos_con_fecha_propia
                ? "⏬ Retrasar fabricación (Elementos)"
                : "⏬ Retrasar fabricación";

            Swal.fire({
                icon: "question",
                title: tituloModal,
                html: `
                    <div class="text-left">
                        <p class="mb-3">${data.mensaje}</p>
                        ${htmlOrdenes}
                        ${htmlBeneficiados}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el retraso?</p>
                    </div>
                `,
                width: 600,
                showCancelButton: true,
                confirmButtonText: "✅ Ejecutar retraso",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#3b82f6",
                cancelButtonColor: "#6b7280",
            }).then((result) => {
                if (result.isConfirmed) {
                    ejecutarRetrasoFabricacion(
                        elementosIds,
                        esElementosConFechaPropia,
                        nuevaFechaEntrega
                    );
                }
            });
        })
        .catch((err) => {
            console.error("Error en simulación de retraso:", err);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo simular el retraso. " + err.message,
            });
        });
}

/**
 * Ejecuta el retraso de fabricación
 */
/**
 * Ejecuta el retraso de fabricación
 * @param {Array} elementosIds - IDs de elementos
 * @param {boolean} esElementosConFechaPropia - Si son elementos con fecha_entrega propia
 * @param {string} nuevaFechaEntrega - Nueva fecha de entrega (ISO string)
 */
function ejecutarRetrasoFabricacion(elementosIds, esElementosConFechaPropia = false, nuevaFechaEntrega = null) {
    if (!elementosIds || elementosIds.length === 0) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "No hay elementos para retrasar",
        });
        return;
    }

    // Mostrar loading
    Swal.fire({
        title: "Ejecutando retraso...",
        html: "Actualizando posiciones en la cola de fabricación",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    const bodyData = {
        elementos_ids: elementosIds,
        es_elementos_con_fecha_propia: esElementosConFechaPropia,
    };

    // Si son elementos con fecha propia, enviar también la nueva fecha
    if (esElementosConFechaPropia && nuevaFechaEntrega) {
        bodyData.nueva_fecha_entrega = nuevaFechaEntrega;
    }

    fetch("/planificacion/ejecutar-retraso", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": window.AppSalidas?.csrf,
        },
        body: JSON.stringify(bodyData),
    })
        .then((r) => {
            if (!r.ok) throw new Error("Error al ejecutar el retraso");
            return r.json();
        })
        .then((data) => {
            if (data.success) {
                const resultados = data.resultados || [];
                const exitos = resultados.filter(r => r.success);
                const fallos = resultados.filter(r => !r.success);

                let html = data.mensaje || "Las posiciones han sido actualizadas.";
                if (exitos.length > 0) {
                    html += `<br><br><strong>${exitos.length} planilla(s) movidas al final de la cola.</strong>`;
                }
                if (fallos.length > 0) {
                    html += `<br><span class="text-amber-600">${fallos.length} orden(es) no pudieron moverse:</span>`;
                    html += "<ul class='text-left text-sm mt-2'>";
                    fallos.forEach(f => {
                        html += `<li>• Planilla ${f.planilla_id}: ${f.mensaje}</li>`;
                    });
                    html += "</ul>";
                }

                Swal.fire({
                    icon: exitos.length > 0 ? "success" : "warning",
                    title: exitos.length > 0 ? "¡Retraso ejecutado!" : "Problemas al retrasar",
                    html: html,
                    confirmButtonColor: "#3b82f6",
                }).then(() => {
                    // Refrescar el calendario
                    if (calendar) {
                        invalidateCache();
                        calendar.refetchEvents();
                        calendar.refetchResources();
                    }
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.mensaje || "No se pudo ejecutar el retraso.",
                });
            }
        })
        .catch((err) => {
            console.error("Error al ejecutar retraso:", err);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo ejecutar el retraso. " + err.message,
            });
        });
}
