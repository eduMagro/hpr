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
        let contenido = "";

        // Información de transporte
        const camion = p.camion ? ` (${p.camion})` : "";
        if (p.empresa) contenido += `🚛 ${p.empresa}${camion}<br>`;

        // Información de clientes
        if (p.clientes && Array.isArray(p.clientes) && p.clientes.length > 0) {
            const clientesTexto = p.clientes.map(c => c.nombre).filter(Boolean).join(", ");
            if (clientesTexto) {
                contenido += `👤 ${clientesTexto}<br>`;
            }
        }

        // Información de obras
        if (p.obras && Array.isArray(p.obras) && p.obras.length > 0) {
            contenido += `🏗️ Obras:<br>`;
            p.obras.forEach(obra => {
                const codigo = obra.codigo ? `(${obra.codigo})` : '';
                contenido += `&nbsp;&nbsp;• ${obra.nombre} ${codigo}<br>`;
            });
        }

        // Peso total
        if (p.peso_total) {
            contenido += `📦 ${n(p.peso_total)} kg<br>`;
        }

        // Comentario
        if (p.comentario && p.comentario.trim()) {
            contenido += `💬 <strong>Comentario:</strong> ${p.comentario}`;
        }

        if (contenido) {
            tippy(info.el, {
                content: contenido,
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
