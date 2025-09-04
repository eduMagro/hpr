// eventos.js
export async function dataEvents(viewType, info) {
    try {
        const base = window.AppSalidas?.routes?.planificacion;
        if (!base) return [];
        const params = new URLSearchParams({
            tipo: "events",
            viewType: viewType || "",
            start: info.startStr || "",
            end: info.endStr || "",
            t: Date.now(), // evita cache agresivo
        });
        const res = await fetch(`${base}?${params.toString()}`);
        if (!res.ok) {
            console.error("Error eventos", res.status);
            return [];
        }
        const data = await res.json();
        let eventos = Array.isArray(data)
            ? data
            : Array.isArray(data?.events)
            ? data.events
            : [];

        // Filtrar eventos según los checkboxes de tipo
        const soloSalidas =
            document.getElementById("solo-salidas")?.checked || false;
        const soloPlanillas =
            document.getElementById("solo-planillas")?.checked || false;

        if (soloSalidas && !soloPlanillas) {
            // Mostrar solo eventos de salidas
            eventos = eventos.filter((evento) => {
                const tipo = evento.extendedProps?.tipo;
                return tipo === "salida";
            });
        } else if (soloPlanillas && !soloSalidas) {
            // Mostrar solo planillas, festivos y resúmenes
            eventos = eventos.filter((evento) => {
                const tipo = evento.extendedProps?.tipo;
                return tipo === "planilla" || tipo === "festivo" || !tipo; // !tipo para resúmenes sin tipo específico
            });
        }
        // Si ambos están marcados o ninguno está marcado, mostrar todos los eventos

        return eventos;
    } catch (err) {
        console.error("fetch eventos falló:", err);
        return [];
    }
}

// recursos.js igual idea
export async function dataResources(viewType, info) {
    try {
        const base = window.AppSalidas?.routes?.planificacion;
        if (!base) return [];
        const params = new URLSearchParams({
            tipo: "resources",
            viewType: viewType || "",
            start: info.startStr || "",
            end: info.endStr || "",
            t: Date.now(),
        });
        const res = await fetch(`${base}?${params.toString()}`);
        if (!res.ok) {
            console.error("Error resources", res.status);
            return [];
        }
        const data = await res.json();
        return Array.isArray(data)
            ? data
            : Array.isArray(data?.resources)
            ? data.resources
            : [];
    } catch (err) {
        console.error("fetch recursos falló:", err);
        return [];
    }
}
