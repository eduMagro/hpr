import { crearCalendario } from "./calendar.js";
import { onPageReady, onPageLeave } from "../../utils/livewire-helper.js";
import { gestionarPaquetesSalida } from "./calendario-menu.js";
import { invalidateCache } from "./eventos.js";
import "./recursos.js";
import "./tooltips.js";
import "./totales.js";

// ---- helpers para etiquetas semana/mes
function etiquetaMes(fechaISO) {
    const d = new Date(fechaISO);
    let txt = d.toLocaleDateString("es-ES", { month: "long", year: "numeric" });
    return `(${txt.charAt(0).toUpperCase() + txt.slice(1)})`;
}

function etiquetaSemana(fechaISO) {
    const d = new Date(fechaISO);
    const dow = d.getDay();
    const diffToMon = dow === 0 ? -6 : 1 - dow;
    const monday = new Date(d);
    monday.setDate(d.getDate() + diffToMon);
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    const fmtDM = new Intl.DateTimeFormat("es-ES", {
        day: "2-digit",
        month: "short",
    });
    const fmtY = new Intl.DateTimeFormat("es-ES", { year: "numeric" });

    return `(${fmtDM.format(monday)} – ${fmtDM.format(sunday)} ${fmtY.format(
        sunday
    )})`;
}

// ---- pintar resúmenes
function actualizarTotales(fechaISO) {
    const semanalSpan = document.querySelector("#resumen-semanal-fecha");
    const mensualSpan = document.querySelector("#resumen-mensual-fecha");
    if (semanalSpan) semanalSpan.textContent = etiquetaSemana(fechaISO);
    if (mensualSpan) mensualSpan.textContent = etiquetaMes(fechaISO);

    // Verificar que AppSalidas esté inicializado
    const baseUrl = window.AppSalidas?.routes?.totales;
    if (!baseUrl) return;

    const url = `${baseUrl}?fecha=${encodeURIComponent(fechaISO)}`;
    fetch(url)
        .then((r) => r.json())
        .then((data) => {
            const s = data.semana || {};
            const m = data.mes || {};

            document.querySelector(
                "#resumen-semanal-peso"
            ).textContent = `📦 ${Number(s.peso || 0).toLocaleString()} kg`;
            document.querySelector(
                "#resumen-semanal-longitud"
            ).textContent = `📏 ${Number(s.longitud || 0).toLocaleString()} m`;
            document.querySelector("#resumen-semanal-diametro").textContent =
                s.diametro != null
                    ? `⌀ ${Number(s.diametro).toFixed(2)} mm`
                    : "";

            document.querySelector(
                "#resumen-mensual-peso"
            ).textContent = `📦 ${Number(m.peso || 0).toLocaleString()} kg`;
            document.querySelector(
                "#resumen-mensual-longitud"
            ).textContent = `📏 ${Number(m.longitud || 0).toLocaleString()} m`;
            document.querySelector("#resumen-mensual-diametro").textContent =
                m.diametro != null
                    ? `⌀ ${Number(m.diametro).toFixed(2)} mm`
                    : "";
        })
        .catch((err) => console.error("❌ Totales:", err));
}

// ---- lógica principal
let calendar;

// Función para inicializar el calendario
function inicializarCalendario() {
    // Si ya existe un calendario, destruirlo primero
    if (window.calendar) {
        try {
            window.calendar.destroy();
        } catch (e) {
            console.warn('Error al destruir calendario anterior:', e);
        }
    }

    const cal = crearCalendario();
    calendar = cal;
    window.calendar = cal;

    // recarga inicial
    cal.refetchResources();
    cal.refetchEvents();

    // Botones opcionales
    document
        .getElementById("ver-con-salidas")
        ?.addEventListener("click", () => {
            cal.refetchResources();
            cal.refetchEvents();
        });
    document.getElementById("ver-todas")?.addEventListener("click", () => {
        cal.refetchResources();
        cal.refetchEvents();
    });

    // Totales iniciales
    const saved = localStorage.getItem("fechaCalendario");
    const hoyISO = (saved || new Date().toISOString()).split("T")[0];
    actualizarTotales(hoyISO);

    // Restaurar estado de los checkboxes desde localStorage
    const soloSalidasGuardado = localStorage.getItem("soloSalidas") === "true";
    const soloPlanillasGuardado =
        localStorage.getItem("soloPlanillas") === "true";

    const checkboxSoloSalidas = document.getElementById("solo-salidas");
    const checkboxSoloPlanillas = document.getElementById("solo-planillas");

    if (checkboxSoloSalidas) {
        checkboxSoloSalidas.checked = soloSalidasGuardado;
    }
    if (checkboxSoloPlanillas) {
        checkboxSoloPlanillas.checked = soloPlanillasGuardado;
    }

    // Filtros
    const filtroCodigo = document.getElementById("filtro-obra");
    const filtroNombre = document.getElementById("filtro-nombre-obra");
    const filtroCodCliente = document.getElementById("filtro-cod-cliente");
    const filtroCliente = document.getElementById("filtro-cliente");
    const filtroCodPlanilla = document.getElementById("filtro-cod-planilla");
    const btnReset = document.getElementById("btn-reset-filtros");
    const btnLimpiar = document.getElementById("btn-limpiar-filtros");

    btnReset?.addEventListener("click", () => {
        if (filtroCodigo) filtroCodigo.value = "";
        if (filtroNombre) filtroNombre.value = "";
        if (filtroCodCliente) filtroCodCliente.value = "";
        if (filtroCliente) filtroCliente.value = "";
        if (filtroCodPlanilla) filtroCodPlanilla.value = "";
        if (checkboxSoloSalidas) {
            checkboxSoloSalidas.checked = false;
            localStorage.setItem("soloSalidas", "false");
        }
        if (checkboxSoloPlanillas) {
            checkboxSoloPlanillas.checked = false;
            localStorage.setItem("soloPlanillas", "false");
        }
        // Actualizar estilos
        actualizarEstilosContenedores();
        // Limpiar clases de filtrado directamente del DOM
        document.querySelectorAll(".fc-event.evento-filtrado, .fc-event.evento-atenuado").forEach(el => {
            el.classList.remove("evento-filtrado", "evento-atenuado");
        });
        // Invalidar cache y refetch
        invalidateCache();
        calendar.refetchEvents();
    });

    const debounce = (fn, ms = 150) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    };

    const refiltrar = debounce(() => {
        calendar.refetchEvents(); // eventDidMount hará el resaltado
    }, 120);

    filtroCodigo?.addEventListener("input", refiltrar);
    filtroNombre?.addEventListener("input", refiltrar);
    filtroCodCliente?.addEventListener("input", refiltrar);
    filtroCliente?.addEventListener("input", refiltrar);
    filtroCodPlanilla?.addEventListener("input", refiltrar);

    // Función para actualizar estilos de contenedores
    function actualizarEstilosContenedores() {
        const contenedorSalidas = checkboxSoloSalidas?.closest(
            ".checkbox-container"
        );
        const contenedorPlanillas = checkboxSoloPlanillas?.closest(
            ".checkbox-container"
        );

        // Remover todas las clases activas
        contenedorSalidas?.classList.remove("active-salidas");
        contenedorPlanillas?.classList.remove("active-planillas");

        // Agregar clase activa según el estado
        if (checkboxSoloSalidas?.checked) {
            contenedorSalidas?.classList.add("active-salidas");
        }
        if (checkboxSoloPlanillas?.checked) {
            contenedorPlanillas?.classList.add("active-planillas");
        }
    }

    // Event listeners para los checkboxes de tipo de evento (mutuamente excluyentes)
    checkboxSoloSalidas?.addEventListener("change", (e) => {
        if (e.target.checked && checkboxSoloPlanillas) {
            // Si se marca "Solo salidas", desmarcar "Solo planillas"
            checkboxSoloPlanillas.checked = false;
            localStorage.setItem("soloPlanillas", "false");
        }
        // Guardar estado en localStorage
        localStorage.setItem("soloSalidas", e.target.checked.toString());
        // Actualizar estilos
        actualizarEstilosContenedores();
        // Refrescar eventos del calendario
        calendar.refetchEvents();
    });

    checkboxSoloPlanillas?.addEventListener("change", (e) => {
        if (e.target.checked && checkboxSoloSalidas) {
            // Si se marca "Solo planillas", desmarcar "Solo salidas"
            checkboxSoloSalidas.checked = false;
            localStorage.setItem("soloSalidas", "false");
        }
        // Guardar estado en localStorage
        localStorage.setItem("soloPlanillas", e.target.checked.toString());
        // Actualizar estilos
        actualizarEstilosContenedores();
        // Refrescar eventos del calendario
        calendar.refetchEvents();
    });

    // Aplicar estilos iniciales
    actualizarEstilosContenedores();

    btnLimpiar?.addEventListener("click", () => {
        if (filtroCodigo) filtroCodigo.value = "";
        if (filtroNombre) filtroNombre.value = "";
        if (filtroCodCliente) filtroCodCliente.value = "";
        if (filtroCliente) filtroCliente.value = "";
        if (filtroCodPlanilla) filtroCodPlanilla.value = "";
        // Limpiar clases de filtrado de todos los eventos en el DOM
        document.querySelectorAll(".fc-event.evento-filtrado, .fc-event.evento-atenuado").forEach(el => {
            el.classList.remove("evento-filtrado", "evento-atenuado");
        });
        // Invalidar cache y forzar re-render completo
        invalidateCache();
        calendar.refetchEvents();
    });
}

// ---- Navegación por teclado del calendario ----
let focusedDate = null;
let keyboardNavEnabled = false;
let keyboardNavCleanup = null;

// Navegación por eventos
let navMode = 'days'; // 'days' o 'events'
let focusedEventIndex = -1;
let eventsInView = [];

function inicializarNavegacionTeclado() {
    // Limpiar listeners anteriores si existen
    if (keyboardNavCleanup) {
        keyboardNavCleanup();
    }

    const calendar = window.calendar;
    if (!calendar) return;

    // Inicializar fecha enfocada con la fecha actual del calendario
    focusedDate = calendar.getDate();
    keyboardNavEnabled = true;
    navMode = 'days';
    focusedEventIndex = -1;

    // Marcar el día inicial como enfocado
    actualizarFocoVisual();

    function handleKeydown(e) {
        // Ignorar si estamos en un input
        const tag = e.target.tagName.toLowerCase();
        if (tag === 'input' || tag === 'textarea' || e.target.isContentEditable) {
            return;
        }

        // Ignorar si hay un modal abierto (SweetAlert, etc.)
        if (document.querySelector('.swal2-container')) {
            return;
        }

        const calendar = window.calendar;
        if (!calendar) return;

        let handled = false;

        switch (e.key) {
            // ← → Cambiar mes/semana
            case 'ArrowLeft':
                calendar.prev();
                handled = true;
                break;
            case 'ArrowRight':
                calendar.next();
                handled = true;
                break;

            // T: Ir a hoy
            case 't':
            case 'T':
                calendar.today();
                handled = true;
                break;

            // Escape: cerrar menús, salir de fullscreen
            case 'Escape':
                if (window.isFullScreen) {
                    window.toggleFullScreen?.();
                    handled = true;
                }
                break;
        }

        if (handled) {
            e.preventDefault();
            e.stopPropagation();
        }
    }

    // Usar capture phase para interceptar antes que el sidebar
    document.addEventListener('keydown', handleKeydown, true);

    // Actualizar lista de eventos cuando cambia la vista
    calendar.on('eventsSet', () => {
        if (navMode === 'events') {
            actualizarListaEventos();
            actualizarFocoEventos();
        }
    });

    // Guardar función de limpieza
    keyboardNavCleanup = () => {
        document.removeEventListener('keydown', handleKeydown, true);
        limpiarFocoVisual();
        limpiarFocoEventos();
        keyboardNavEnabled = false;
    };
}

function toggleNavMode() {
    if (navMode === 'days') {
        navMode = 'events';
        actualizarListaEventos();
        if (eventsInView.length > 0) {
            focusedEventIndex = 0;
            actualizarFocoEventos();
        } else {
            // No hay eventos, volver a modo días
            navMode = 'days';
            mostrarMensajeNoEventos();
        }
    } else {
        navMode = 'days';
        focusedEventIndex = -1;
        limpiarFocoEventos();
        actualizarFocoVisual();
    }
    actualizarIndicadorFecha();
}

function actualizarListaEventos() {
    const calendar = window.calendar;
    if (!calendar) {
        eventsInView = [];
        return;
    }

    // Obtener todos los eventos visibles (excluyendo resúmenes y festivos)
    eventsInView = calendar.getEvents()
        .filter(ev => {
            const tipo = ev.extendedProps?.tipo;
            return tipo !== 'resumen-dia' && tipo !== 'festivo';
        })
        .sort((a, b) => {
            // Ordenar por fecha, luego por título
            const dateA = a.start || new Date(0);
            const dateB = b.start || new Date(0);
            if (dateA < dateB) return -1;
            if (dateA > dateB) return 1;
            return (a.title || '').localeCompare(b.title || '');
        });
}

function navegarEventos(e) {
    if (eventsInView.length === 0) return false;

    let handled = false;

    switch (e.key) {
        case 'ArrowDown':
        case 'ArrowRight':
            focusedEventIndex = (focusedEventIndex + 1) % eventsInView.length;
            actualizarFocoEventos();
            handled = true;
            break;

        case 'ArrowUp':
        case 'ArrowLeft':
            focusedEventIndex = focusedEventIndex <= 0 ? eventsInView.length - 1 : focusedEventIndex - 1;
            actualizarFocoEventos();
            handled = true;
            break;

        case 'Home':
            focusedEventIndex = 0;
            actualizarFocoEventos();
            handled = true;
            break;

        case 'End':
            focusedEventIndex = eventsInView.length - 1;
            actualizarFocoEventos();
            handled = true;
            break;

        case 'Enter':
            // Abrir el evento (simular clic)
            abrirEventoEnfocado();
            handled = true;
            break;

        case 'e':
        case 'E':
            // Editar evento (abrir menú contextual)
            abrirMenuEventoEnfocado();
            handled = true;
            break;

        case 'i':
        case 'I':
            // Mostrar info del evento
            mostrarInfoEventoEnfocado();
            handled = true;
            break;
    }

    return handled;
}

function navegarDias(e) {
    const calendar = window.calendar;
    const newDate = new Date(focusedDate);
    let handled = false;

    switch (e.key) {
        case 'ArrowLeft':
            newDate.setDate(newDate.getDate() - 1);
            handled = true;
            break;
        case 'ArrowRight':
            newDate.setDate(newDate.getDate() + 1);
            handled = true;
            break;
        case 'ArrowUp':
            newDate.setDate(newDate.getDate() - 7);
            handled = true;
            break;
        case 'ArrowDown':
            newDate.setDate(newDate.getDate() + 7);
            handled = true;
            break;
        case 'Home':
            newDate.setDate(1);
            handled = true;
            break;
        case 'End':
            newDate.setMonth(newDate.getMonth() + 1);
            newDate.setDate(0);
            handled = true;
            break;
        case 'PageUp':
            newDate.setMonth(newDate.getMonth() - 1);
            handled = true;
            break;
        case 'PageDown':
            newDate.setMonth(newDate.getMonth() + 1);
            handled = true;
            break;
        case 'Enter':
            const dateStr = formatDateISO(focusedDate);
            const viewType = calendar.view.type;
            if (viewType === 'dayGridMonth' || viewType === 'resourceTimelineWeek') {
                calendar.changeView('resourceTimeGridDay', dateStr);
            } else {
                calendar.gotoDate(focusedDate);
            }
            handled = true;
            break;
        case 't':
        case 'T':
            if (!e.ctrlKey && !e.metaKey) {
                focusedDate = new Date();
                calendar.today();
                actualizarFocoVisual();
                handled = true;
            }
            break;
    }

    if (handled && e.key !== 'Enter' && e.key !== 't' && e.key !== 'T') {
        focusedDate = newDate;
        const view = calendar.view;
        if (newDate < view.currentStart || newDate >= view.currentEnd) {
            calendar.gotoDate(newDate);
        }
        actualizarFocoVisual();
    }

    return handled;
}

function actualizarFocoEventos() {
    limpiarFocoEventos();

    if (focusedEventIndex < 0 || focusedEventIndex >= eventsInView.length) return;

    const event = eventsInView[focusedEventIndex];
    if (!event) return;

    // Buscar el elemento DOM del evento
    const eventEl = document.querySelector(`[data-event-id="${event.id}"]`) ||
                    document.querySelector(`.fc-event[data-event="${event.id}"]`);

    // Alternativa: buscar por contenido del título
    if (!eventEl) {
        const allEvents = document.querySelectorAll('.fc-event');
        for (const el of allEvents) {
            // Verificar si es el evento correcto comparando el título
            if (el.textContent.includes(event.title?.substring(0, 20))) {
                el.classList.add('keyboard-focused-event');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                break;
            }
        }
    } else {
        eventEl.classList.add('keyboard-focused-event');
        eventEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Actualizar fecha enfocada al día del evento
    if (event.start) {
        focusedDate = new Date(event.start);
    }

    actualizarIndicadorFecha();
}

function limpiarFocoEventos() {
    document.querySelectorAll('.keyboard-focused-event').forEach(el => {
        el.classList.remove('keyboard-focused-event');
    });
}

function abrirEventoEnfocado() {
    if (focusedEventIndex < 0 || focusedEventIndex >= eventsInView.length) return;

    const event = eventsInView[focusedEventIndex];
    if (!event) return;

    const props = event.extendedProps || {};
    const calendar = window.calendar;

    // Abrir según el tipo de evento
    if (props.tipo === 'salida') {
        // Abrir modal de gestionar paquetes
        const salidaId = props.salida_id || event.id;
        gestionarPaquetesSalida(salidaId, calendar);
    } else if (props.tipo === 'planilla') {
        // Abrir página de gestionar salidas y paquetes
        const ids = props.planillas_ids || [];
        if (ids.length > 0) {
            window.location.href = `/salidas-ferralla/gestionar-salidas?planillas=${ids.join(',')}`;
        }
    }
}

function abrirMenuEventoEnfocado() {
    if (focusedEventIndex < 0 || focusedEventIndex >= eventsInView.length) return;

    const event = eventsInView[focusedEventIndex];
    if (!event) return;

    // Buscar el elemento y simular clic derecho
    const allEvents = document.querySelectorAll('.fc-event');
    for (const el of allEvents) {
        if (el.classList.contains('keyboard-focused-event') ||
            el.textContent.includes(event.title?.substring(0, 20))) {
            // Simular clic derecho para abrir menú contextual
            const rect = el.getBoundingClientRect();
            const contextEvent = new MouseEvent('contextmenu', {
                bubbles: true,
                cancelable: true,
                clientX: rect.left + rect.width / 2,
                clientY: rect.top + rect.height / 2
            });
            el.dispatchEvent(contextEvent);
            break;
        }
    }
}

function mostrarInfoEventoEnfocado() {
    if (focusedEventIndex < 0 || focusedEventIndex >= eventsInView.length) return;

    const event = eventsInView[focusedEventIndex];
    if (!event) return;

    const props = event.extendedProps || {};

    let info = `<strong>${event.title}</strong><br><br>`;

    if (props.tipo === 'salida') {
        info += `<b>Tipo:</b> Salida<br>`;
        if (props.obras && props.obras.length > 0) {
            info += `<b>Obras:</b> ${props.obras.map(o => o.nombre).join(', ')}<br>`;
        }
    } else if (props.tipo === 'planilla') {
        info += `<b>Tipo:</b> Planilla<br>`;
        if (props.cod_obra) info += `<b>Código:</b> ${props.cod_obra}<br>`;
        if (props.pesoTotal) info += `<b>Peso:</b> ${Number(props.pesoTotal).toLocaleString()} kg<br>`;
        if (props.longitudTotal) info += `<b>Longitud:</b> ${Number(props.longitudTotal).toLocaleString()} m<br>`;
    }

    if (event.start) {
        info += `<b>Fecha:</b> ${event.start.toLocaleDateString('es-ES', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        })}<br>`;
    }

    Swal.fire({
        title: 'Información del evento',
        html: info,
        icon: 'info',
        confirmButtonText: 'Cerrar'
    });
}

function mostrarMensajeNoEventos() {
    const indicator = document.getElementById('keyboard-nav-indicator');
    if (indicator) {
        const dateSpan = document.getElementById('keyboard-nav-date');
        if (dateSpan) {
            dateSpan.innerHTML = '<span class="text-yellow-400">No hay eventos visibles</span>';
        }
        clearTimeout(indicator._hideTimeout);
        indicator.style.display = 'flex';
        indicator._hideTimeout = setTimeout(() => {
            actualizarIndicadorFecha();
        }, 2000);
    }
}

function formatDateISO(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function actualizarFocoVisual() {
    // Limpiar foco anterior
    limpiarFocoVisual();

    if (!focusedDate) return;

    const dateStr = formatDateISO(focusedDate);
    const calendar = window.calendar;
    if (!calendar) return;

    const viewType = calendar.view.type;

    // Buscar la celda del día en el calendario según la vista
    let dayCell = null;

    if (viewType === 'dayGridMonth') {
        // Vista mensual: buscar por data-date
        dayCell = document.querySelector(`.fc-daygrid-day[data-date="${dateStr}"]`);
    } else if (viewType === 'resourceTimelineWeek') {
        // Vista semanal timeline: buscar en los slots de tiempo
        const slots = document.querySelectorAll('.fc-timeline-slot[data-date]');
        slots.forEach(slot => {
            if (slot.dataset.date && slot.dataset.date.startsWith(dateStr)) {
                dayCell = slot;
            }
        });
        // También buscar en los headers
        if (!dayCell) {
            dayCell = document.querySelector(`.fc-timeline-slot-lane[data-date^="${dateStr}"]`);
        }
    } else if (viewType === 'resourceTimeGridDay') {
        // Vista diaria: el header del día
        dayCell = document.querySelector('.fc-col-header-cell');
    }

    if (dayCell) {
        dayCell.classList.add('keyboard-focused-day');
        dayCell.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    }

    // Actualizar indicador visual en la UI
    actualizarIndicadorFecha();
}

function limpiarFocoVisual() {
    document.querySelectorAll('.keyboard-focused-day').forEach(el => {
        el.classList.remove('keyboard-focused-day');
    });
}

function actualizarIndicadorFecha() {
    // Actualizar o crear indicador de fecha enfocada
    let indicator = document.getElementById('keyboard-nav-indicator');

    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'keyboard-nav-indicator';
        indicator.className = 'fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm';
        document.body.appendChild(indicator);
    }

    // Contenido diferente según el modo
    if (navMode === 'events') {
        const event = eventsInView[focusedEventIndex];
        const eventTitle = event?.title || 'Sin evento';
        const eventNum = `${focusedEventIndex + 1}/${eventsInView.length}`;

        indicator.innerHTML = `
            <div class="flex items-center gap-2">
                <span class="bg-green-500 text-white text-xs px-2 py-0.5 rounded">EVENTOS</span>
                <span class="font-medium truncate max-w-[200px]">${eventTitle}</span>
                <span class="text-gray-400">${eventNum}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1 flex gap-3">
                <span>↑↓ Navegar</span>
                <span>Enter Abrir</span>
                <span>E Menú</span>
                <span>I Info</span>
                <span>Tab/Esc Días</span>
            </div>
        `;
    } else {
        const dateStr = focusedDate ? focusedDate.toLocaleDateString('es-ES', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }) : '';

        indicator.innerHTML = `
            <div class="flex items-center gap-2">
                <span class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded">DÍAS</span>
                <span class="opacity-75">📅</span>
                <span id="keyboard-nav-date">${dateStr}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1 flex gap-3">
                <span>← → ↑ ↓</span>
                <span>Enter Vista día</span>
                <span>T Hoy</span>
                <span>Tab Eventos</span>
            </div>
        `;
    }

    // Ocultar indicador después de 4 segundos de inactividad
    clearTimeout(indicator._hideTimeout);
    indicator.style.display = 'block';
    indicator._hideTimeout = setTimeout(() => {
        indicator.style.display = 'none';
    }, 4000);
}

// Inyectar estilos para el foco visual
function inyectarEstilosFoco() {
    if (document.getElementById('keyboard-nav-styles')) return;

    const styles = document.createElement('style');
    styles.id = 'keyboard-nav-styles';
    styles.textContent = `
        /* Foco en días */
        .keyboard-focused-day {
            outline: 3px solid #3b82f6 !important;
            outline-offset: -3px;
            background-color: rgba(59, 130, 246, 0.15) !important;
            position: relative;
            z-index: 5;
        }

        .keyboard-focused-day::after {
            content: '';
            position: absolute;
            inset: 0;
            border: 2px solid #3b82f6;
            pointer-events: none;
            animation: pulse-focus 1.5s ease-in-out infinite;
        }

        @keyframes pulse-focus {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Para vista timeline */
        .fc-timeline-slot.keyboard-focused-day,
        .fc-timeline-slot-lane.keyboard-focused-day {
            background-color: rgba(59, 130, 246, 0.2) !important;
        }

        /* Foco en eventos */
        .keyboard-focused-event {
            outline: 3px solid #22c55e !important;
            outline-offset: 2px;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.3), 0 4px 12px rgba(0, 0, 0, 0.3) !important;
            transform: scale(1.02);
            z-index: 100 !important;
            position: relative;
            transition: all 0.15s ease;
        }

        .keyboard-focused-event::before {
            content: '►';
            position: absolute;
            left: -20px;
            top: 50%;
            transform: translateY(-50%);
            color: #22c55e;
            font-size: 14px;
            animation: bounce-arrow 0.6s ease-in-out infinite;
        }

        @keyframes bounce-arrow {
            0%, 100% { transform: translateY(-50%) translateX(0); }
            50% { transform: translateY(-50%) translateX(3px); }
        }

        #keyboard-nav-indicator {
            transition: opacity 0.3s ease;
        }
    `;
    document.head.appendChild(styles);
}

// Usar helper para inicializar en carga y navegación
onPageReady(() => {
    inicializarCalendario();
    inyectarEstilosFoco();
    // Pequeño delay para asegurar que el calendario está renderizado
    setTimeout(() => {
        inicializarNavegacionTeclado();
    }, 500);
}, {
    selector: '#calendario[data-calendar-type="salidas"]'
});

// Limpiar al salir de la página
onPageLeave(() => {
    if (keyboardNavCleanup) {
        keyboardNavCleanup();
        keyboardNavCleanup = null;
    }
    if (window.calendar) {
        try {
            window.calendar.destroy();
            window.calendar = null;
        } catch (e) {
            console.warn('Error al limpiar calendario de salidas:', e);
        }
    }
    // Limpiar indicador
    const indicator = document.getElementById('keyboard-nav-indicator');
    if (indicator) indicator.remove();
});
