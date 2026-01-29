// eventos.js - Optimizado con cache multi-vista

// 🚀 Cache multi-entrada para evitar refetch al cambiar de vista
const CACHE_MAX_ENTRIES = 8;
const CACHE_TTL_MS = 60000; // 60 segundos

// Cache en memoria con múltiples entradas
let _multiCache = new Map();
let _fetchPromises = new Map();

function getCacheKey(viewType, info) {
    return `${viewType}|${info.startStr}|${info.endStr}`;
}

function isExpired(entry) {
    return Date.now() - entry.timestamp > CACHE_TTL_MS;
}

function pruneCache() {
    // Si excede el límite, eliminar entradas más antiguas
    if (_multiCache.size > CACHE_MAX_ENTRIES) {
        const entries = Array.from(_multiCache.entries())
            .sort((a, b) => a[1].timestamp - b[1].timestamp);

        // Eliminar las más antiguas hasta llegar al límite
        const toDelete = entries.slice(0, entries.length - CACHE_MAX_ENTRIES);
        toDelete.forEach(([key]) => _multiCache.delete(key));
    }
}

// Función unificada que trae eventos, recursos y totales en una sola petición
async function fetchAllData(viewType, info) {
    const base = window.AppSalidas?.routes?.planificacion;
    if (!base) return { events: [], resources: [], totales: null };

    const key = getCacheKey(viewType, info);

    // Si ya tenemos datos válidos en cache, devolverlos
    const cached = _multiCache.get(key);
    if (cached && !isExpired(cached)) {
        return cached.data;
    }

    // Si ya hay una petición en curso para esta clave, esperarla
    if (_fetchPromises.has(key)) {
        return _fetchPromises.get(key);
    }

    // Crear nueva petición
    const fetchPromise = (async () => {
        try {
            const params = new URLSearchParams({
                tipo: "all",
                viewType: viewType || "",
                start: info.startStr || "",
                end: info.endStr || "",
            });
            const res = await fetch(`${base}?${params.toString()}`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();
            const result = {
                events: data.events || [],
                resources: data.resources || [],
                totales: data.totales || null,
            };

            // Guardar en cache
            _multiCache.set(key, {
                data: result,
                timestamp: Date.now()
            });
            pruneCache();

            return result;
        } catch (err) {
            console.error("fetch all data falló:", err);
            return { events: [], resources: [], totales: null };
        } finally {
            _fetchPromises.delete(key);
        }
    })();

    _fetchPromises.set(key, fetchPromise);
    return fetchPromise;
}

// Invalida todo el cache (llamar cuando se hacen cambios que afectan los datos)
export function invalidateCache() {
    _multiCache.clear();
    _fetchPromises.clear();
}

// Invalida solo una clave específica
export function invalidateCacheKey(viewType, info) {
    const key = getCacheKey(viewType, info);
    _multiCache.delete(key);
    _fetchPromises.delete(key);
}

// Aplica filtros locales a los eventos (sin nueva petición)
function applyEventFilters(eventos) {
    const soloSalidas = document.getElementById("solo-salidas")?.checked || false;
    const soloPlanillas = document.getElementById("solo-planillas")?.checked || false;

    const eventosResumen = eventos.filter(e => e.extendedProps?.tipo === "resumen-dia");
    const eventosNormales = eventos.filter(e => e.extendedProps?.tipo !== "resumen-dia");

    let eventosFiltrados = eventosNormales;

    if (soloSalidas && !soloPlanillas) {
        eventosFiltrados = eventosNormales.filter(e => e.extendedProps?.tipo === "salida");
    } else if (soloPlanillas && !soloSalidas) {
        eventosFiltrados = eventosNormales.filter(e => {
            const tipo = e.extendedProps?.tipo;
            return tipo === "planilla" || tipo === "festivo";
        });
    }

    return [...eventosFiltrados, ...eventosResumen];
}

export async function dataEvents(viewType, info) {
    const data = await fetchAllData(viewType, info);
    return applyEventFilters(data.events);
}

export async function dataResources(viewType, info) {
    const data = await fetchAllData(viewType, info);
    return data.resources;
}

// Actualiza los totales en el DOM usando datos del cache
export async function updateTotalesFromCache(viewType, info) {
    const data = await fetchAllData(viewType, info);
    if (!data.totales) return;

    const { semana, mes } = data.totales;
    const num = v => v != null ? Number(v).toLocaleString() : "0";

    // Semanal
    const pesoSem = document.querySelector("#resumen-semanal-peso");
    const longSem = document.querySelector("#resumen-semanal-longitud");
    const diamSem = document.querySelector("#resumen-semanal-diametro");
    const semEl = document.querySelector("#resumen-semanal-fecha");
    if (pesoSem) pesoSem.textContent = `${num(semana?.peso)} kg`;
    if (longSem) longSem.textContent = `${num(semana?.longitud)} m`;
    if (diamSem) diamSem.textContent = semana?.diametro ? `⌀ ${Number(semana.diametro).toFixed(2)} mm` : "";
    if (semEl && semana?.nombre) semEl.textContent = `(${semana.nombre})`;

    // Mensual
    const pesoMes = document.querySelector("#resumen-mensual-peso");
    const longMes = document.querySelector("#resumen-mensual-longitud");
    const diamMes = document.querySelector("#resumen-mensual-diametro");
    const mesEl = document.querySelector("#resumen-mensual-fecha");
    if (pesoMes) pesoMes.textContent = `${num(mes?.peso)} kg`;
    if (longMes) longMes.textContent = `${num(mes?.longitud)} m`;
    if (diamMes) diamMes.textContent = mes?.diametro ? `⌀ ${Number(mes.diametro).toFixed(2)} mm` : "";
    // Usar el nombre del mes calculado en el backend (punto medio de la vista)
    if (mesEl && mes?.nombre) {
        mesEl.textContent = `(${mes.nombre})`;
    }
}
