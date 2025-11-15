import { crearCalendario } from "./calendar.js";
import { onPageReady, onPageLeave } from "../../utils/livewire-helper.js";

import "./eventos.js";
import "./recursos.js";
import "./tooltips.js";
import "./calendario-menu.js";
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

    const url = `${window.AppSalidas.routes.totales}?fecha=${encodeURIComponent(
        fechaISO
    )}`;
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
    const btnReset = document.getElementById("btn-reset-filtros");
    const btnLimpiar = document.getElementById("btn-limpiar-filtros");

    btnReset?.addEventListener("click", () => {
        if (filtroCodigo) filtroCodigo.value = "";
        if (filtroNombre) filtroNombre.value = "";
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
        calendar.refetchEvents();
    });
}

// Usar helper para inicializar en carga y navegación
onPageReady(inicializarCalendario, {
    selector: '#calendario[data-calendar-type="salidas"]'
});

// Limpiar al salir de la página
onPageLeave(() => {
    if (window.calendar) {
        try {
            window.calendar.destroy();
            window.calendar = null;
        } catch (e) {
            console.warn('Error al limpiar calendario de salidas:', e);
        }
    }
});
