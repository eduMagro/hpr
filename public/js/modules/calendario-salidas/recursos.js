// Export CON NOMBRE
export async function dataResources(viewType, info) {
    const base = window.AppSalidas?.routes?.planificacion;
    if (!base) return [];

    const params = new URLSearchParams({
        tipo: "resources",
        viewType: viewType || "",
        start: info.startStr || "",
        end: info.endStr || "",
    });

    const res = await fetch(`${base}?${params.toString()}`, { method: "GET" });
    if (!res.ok) throw new Error("Error cargando recursos");
    const data = await res.json();

    if (Array.isArray(data)) return data;
    if (Array.isArray(data?.resources)) return data.resources;
    return [];
}
