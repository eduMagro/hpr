// eventos.js - Optimizado con cache unificado

// Cache global para evitar múltiples peticiones
let _dataCache = null;
let _cacheKey = null;
let _fetchPromise = null;

// Función unificada que trae eventos, recursos y totales en una sola petición
async function fetchAllData(viewType, info) {
    const base = window.AppSalidas?.routes?.planificacion;
    if (!base) return { events: [], resources: [], totales: null };

    const key = `${viewType}|${info.startStr}|${info.endStr}`;

    // Si ya tenemos datos en cache para este rango, devolverlos
    if (_cacheKey === key && _dataCache) {
        return _dataCache;
    }

    // Si ya hay una petición en curso para este rango, esperarla
    if (_fetchPromise && _cacheKey === key) {
        return _fetchPromise;
    }

    _cacheKey = key;
    _fetchPromise = (async () => {
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
            _dataCache = {
                events: data.events || [],
                resources: data.resources || [],
                totales: data.totales || null,
            };
            return _dataCache;
        } catch (err) {
            console.error("fetch all data falló:", err);
            _dataCache = null;
            return { events: [], resources: [], totales: null };
        } finally {
            _fetchPromise = null;
        }
    })();

    return _fetchPromise;
}

// Invalida el cache (llamar cuando se hacen cambios)
export function invalidateCache() {
    _dataCache = null;
    _cacheKey = null;
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
    if (pesoSem) pesoSem.textContent = `📦 ${num(semana?.peso)} kg`;
    if (longSem) longSem.textContent = `📏 ${num(semana?.longitud)} m`;
    if (diamSem) diamSem.textContent = semana?.diametro ? `⌀ ${Number(semana.diametro).toFixed(2)} mm` : "";

    // Mensual
    const pesoMes = document.querySelector("#resumen-mensual-peso");
    const longMes = document.querySelector("#resumen-mensual-longitud");
    const diamMes = document.querySelector("#resumen-mensual-diametro");
    if (pesoMes) pesoMes.textContent = `📦 ${num(mes?.peso)} kg`;
    if (longMes) longMes.textContent = `📏 ${num(mes?.longitud)} m`;
    if (diamMes) diamMes.textContent = mes?.diametro ? `⌀ ${Number(mes.diametro).toFixed(2)} mm` : "";

    // Actualizar fecha del mes
    if (info.startStr) {
        const dateObj = new Date(info.startStr);
        const opciones = { year: "numeric", month: "long" };
        let mesTexto = dateObj.toLocaleDateString("es-ES", opciones);
        mesTexto = mesTexto.charAt(0).toUpperCase() + mesTexto.slice(1);
        const mesEl = document.querySelector("#resumen-mensual-fecha");
        if (mesEl) mesEl.textContent = `(${mesTexto})`;
    }
}
