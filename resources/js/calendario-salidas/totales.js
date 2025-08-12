export function actualizarTotales(fecha) {
    if (!fecha) return;

    // Mes “bonito”
    const dateObj = new Date(fecha);
    const opciones = { year: "numeric", month: "long" };
    let mesTexto = dateObj.toLocaleDateString("es-ES", opciones);
    mesTexto = mesTexto.charAt(0).toUpperCase() + mesTexto.slice(1);
    const mesEl = document.querySelector("#resumen-mensual-fecha");
    if (mesEl) mesEl.textContent = `(${mesTexto})`;

    const base = window.AppSalidas?.routes?.totales;
    if (!base) return;

    fetch(`${base}?fecha=${encodeURIComponent(fecha)}`)
        .then((res) => res.json())
        .then((data) => {
            // Semanal
            const s = data.semana || {};
            setText("#resumen-semanal-peso", `📦 ${num(s.peso)} kg`);
            setText("#resumen-semanal-longitud", `📏 ${num(s.longitud)} m`);
            setText(
                "#resumen-semanal-diametro",
                s.diametro != null && !isNaN(s.diametro)
                    ? `⌀ ${Number(s.diametro).toFixed(2)} mm`
                    : ""
            );

            // Mensual
            const m = data.mes || {};
            setText("#resumen-mensual-peso", `📦 ${num(m.peso)} kg`);
            setText("#resumen-mensual-longitud", `📏 ${num(m.longitud)} m`);
            setText(
                "#resumen-mensual-diametro",
                m.diametro != null && !isNaN(m.diametro)
                    ? `⌀ ${Number(m.diametro).toFixed(2)} mm`
                    : ""
            );
        })
        .catch((err) =>
            console.error("❌ Error al actualizar los totales:", err)
        );
}

function num(v) {
    return v != null ? Number(v).toLocaleString() : "0";
}
function setText(sel, txt) {
    const el = document.querySelector(sel);
    if (el) el.textContent = txt;
}
