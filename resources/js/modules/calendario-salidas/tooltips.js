// tooltips.js
export function configurarTooltipsYMenus(info, calendar) {
    const p = info.event.extendedProps || {};

    // nada para festivos
    if (p.tipo === "festivo") return;

    if (p.tipo === "planilla") {
        const contenido = `
      ✅ Fabricados: ${n(p.fabricadosKg)} kg<br>
      🔄 Fabricando: ${n(p.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${n(p.pendientesKg)} kg
    `;
        tippy(info.el, {
            content: contenido,
            allowHTML: true,
            theme: "light-border",
            placement: "top",
            animation: "shift-away",
            arrow: true,
        });
        // 👇 quitar este bloque (antes llamaba a mostrarMenuPlanilla)
        // info.el.addEventListener("contextmenu", ...)
    }

    if (p.tipo === "salida") {
        // Solo mostrar comentario si existe
        if (p.comentario && p.comentario.trim()) {
            tippy(info.el, {
                content: p.comentario,
                allowHTML: true,
                theme: "light-border",
                placement: "top",
                animation: "shift-away",
                arrow: true,
            });
        }
        // 👇 quitar este bloque (antes llamaba a mostrarMenuSalida)
        // info.el.addEventListener("contextmenu", ...)
    }
}

function n(v) {
    return v != null ? Number(v).toLocaleString() : 0;
}
