import { crearCalendario } from "./calendar.js";
// ---- helpers para etiquetas semana/mes
function etiquetaMes(fechaISO) {
    const d = new Date(fechaISO);
    let txt = d.toLocaleDateString("es-ES", { month: "long", year: "numeric" });
    return `(${txt.charAt(0).toUpperCase() + txt.slice(1)})`;
}

function etiquetaSemana(fechaISO) {
    const d = new Date(fechaISO);
    const dow = d.getDay(); // 0 dom .. 1 lun
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
    // etiquetas
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
            // semanal
            document.querySelector(
                "#resumen-semanal-peso"
            ).textContent = `📦 ${Number(
                data.semana.peso || 0
            ).toLocaleString()} kg`;
            document.querySelector(
                "#resumen-semanal-longitud"
            ).textContent = `📏 ${Number(
                data.semana.longitud || 0
            ).toLocaleString()} m`;
            const semDiam = document.querySelector("#resumen-semanal-diametro");
            semDiam.textContent =
                data.semana?.diametro != null && !isNaN(data.semana.diametro)
                    ? `⌀ ${Number(data.semana.diametro).toFixed(2)} mm`
                    : "";

            // mensual
            document.querySelector(
                "#resumen-mensual-peso"
            ).textContent = `📦 ${Number(
                data.mes.peso || 0
            ).toLocaleString()} kg`;
            document.querySelector(
                "#resumen-mensual-longitud"
            ).textContent = `📏 ${Number(
                data.mes.longitud || 0
            ).toLocaleString()} m`;
            const mesDiam = document.querySelector("#resumen-mensual-diametro");
            mesDiam.textContent =
                data.mes?.diametro != null && !isNaN(data.mes.diametro)
                    ? `⌀ ${Number(data.mes.diametro).toFixed(2)} mm`
                    : "";
        })
        .catch((err) => console.error("❌ Totales:", err));
}

document.addEventListener("DOMContentLoaded", () => {
    // Inicializa el calendario
    const cal = crearCalendario();

    // Carga inicial “como si hubieras pulsado ver-con-salidas”
    // (equivale a refetch de resources/events)
    cal.refetchResources();
    cal.refetchEvents();

    // Hooks opcionales si tienes botones en la vista
    const btnCon = document.getElementById("ver-con-salidas");
    if (btnCon)
        btnCon.addEventListener("click", () => {
            cal.refetchResources();
            cal.refetchEvents();
        });

    const btnTodas = document.getElementById("ver-todas");
    if (btnTodas)
        btnTodas.addEventListener("click", () => {
            cal.refetchResources();
            cal.refetchEvents();
        });

    const saved = localStorage.getItem("fechaCalendario");
    const hoyISO = (saved || new Date().toISOString()).split("T")[0];
    actualizarTotales(hoyISO);
});
