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
        return Array.isArray(data)
            ? data
            : Array.isArray(data?.events)
            ? data.events
            : [];
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
