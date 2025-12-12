// Estado global para el módulo de movimientos grúa
window._movimientosGruaState = window._movimientosGruaState || {
    yaEscaneados: [],
    cacheInfo: new Map(),
    initialized: false
};

// Función auxiliar para crear un chip y cargar su info
function _crearChipQR(escaneado, LISTAQRS, state) {
    const API_INFO_URL = LISTAQRS.dataset.apiInfoUrl || "/api/codigos/info";
    const pill = document.createElement("span");
    pill.className = "qr-chip loading";
    pill.textContent = escaneado;
    pill.dataset.code = escaneado;
    pill.title = "Click para eliminar";

    pill.addEventListener("click", () => {
        state.yaEscaneados = state.yaEscaneados.filter((c) => c !== escaneado);
        pill.remove();
        const INPUT_OCULTO = document.getElementById("lista_qrs");
        if (INPUT_OCULTO) INPUT_OCULTO.value = JSON.stringify(state.yaEscaneados);
        if (navigator && typeof navigator.vibrate === "function") navigator.vibrate(60);
    });

    LISTAQRS.appendChild(pill);

    // Cargar info async
    (async () => {
        try {
            const url = new URL(API_INFO_URL, window.location.origin);
            url.searchParams.set("code", escaneado);
            const res = await fetch(url.toString(), {
                method: "GET",
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            });
            const data = await res.json().catch(() => ({}));
            state.cacheInfo.set(escaneado, data);

            const currentPill = LISTAQRS.querySelector(`[data-code="${escaneado}"]`);
            if (currentPill) {
                currentPill.classList.remove("loading", "error");
                if (!data || data.ok === false) {
                    currentPill.classList.add("error");
                    currentPill.textContent = `${escaneado} · error`;
                } else {
                    const partes = [];
                    if (data.sigla) partes.push(data.sigla);
                    partes.push(data.codigo);
                    if (data.diametro !== null && data.diametro !== undefined) {
                        partes.push(`Ø${data.diametro} mm`);
                    }
                    if (data.longitud) {
                        const isEncarretado = String(data.tipo || "").toLowerCase().includes("encarretado");
                        if (!isEncarretado) partes.push(`L:${data.longitud} mm`);
                    }
                    currentPill.textContent = partes.join(" · ");
                }
            }
        } catch (e) {
            const currentPill = LISTAQRS.querySelector(`[data-code="${escaneado}"]`);
            if (currentPill) {
                currentPill.classList.remove("loading");
                currentPill.classList.add("error");
                currentPill.textContent = `${escaneado} · error`;
            }
        }
    })();
}

// Función global para agregar QR - siempre disponible
window.agregarQRMovimientoLibre = function(valor) {
    const state = window._movimientosGruaState;
    const LISTAQRS = document.getElementById("mostrar_qrs");
    const INPUT_OCULTO = document.getElementById("lista_qrs");

    if (!LISTAQRS || !INPUT_OCULTO) {
        console.warn('[agregarQRMovimientoLibre] Elementos del modal no encontrados');
        return false;
    }

    const escaneado = String(valor).trim();
    if (!escaneado) return false;

    // duplicado exacto
    if (!state.yaEscaneados.includes(escaneado)) {
        state.yaEscaneados.push(escaneado);
        INPUT_OCULTO.value = JSON.stringify(state.yaEscaneados);

        _crearChipQR(escaneado, LISTAQRS, state);

        if (navigator && typeof navigator.vibrate === "function") navigator.vibrate(100);
        return true;
    } else {
        // Duplicado - resaltar
        const pill = LISTAQRS.querySelector(`[data-code="${escaneado}"]`);
        if (pill) {
            pill.style.outline = "2px solid #991b1b";
            setTimeout(() => (pill.style.outline = ""), 500);
        }
        if (navigator && typeof navigator.vibrate === "function") navigator.vibrate(50);
        return false;
    }
};

function initMovimientosGrua() {
    const QRINPUT = document.getElementById("codigo_general_general");
    const LISTAQRS = document.getElementById("mostrar_qrs");
    const INPUT_OCULTO = document.getElementById("lista_qrs");
    const FORM = document.getElementById("form-movimiento-general");
    const CANCELAR_BTN = document.getElementById("cancelar_btn");

    if (!QRINPUT || !LISTAQRS || !INPUT_OCULTO || !FORM) {
        console.warn('[movimientosgrua] Elementos del modal no encontrados, saltando inicialización de listeners');
        return;
    }

    // Evitar inicialización múltiple
    if (QRINPUT.dataset.initialized === 'true') {
        return;
    }
    QRINPUT.dataset.initialized = 'true';

    const state = window._movimientosGruaState;
    state.initialized = true;

    // Si el hidden ya trae datos (editar), los cargamos
    let codigosIniciales = [];
    try {
        const inicial = JSON.parse(INPUT_OCULTO.value || "[]");
        if (Array.isArray(inicial)) {
            codigosIniciales = Array.from(
                new Set(
                    inicial
                        .map((v) => String(v).trim().toUpperCase())
                        .filter(Boolean)
                )
            );
        }
    } catch (e) {
        /* no-op */
    }

    // Limpiar estado y renderizar lista inicial
    state.yaEscaneados = [];
    LISTAQRS.innerHTML = "";

    // Agregar cada código inicial (esto los añade al array Y crea los chips)
    codigosIniciales.forEach((code) => {
        window.agregarQRMovimientoLibre(code);
    });

    // Si se cancela se borran los códigos escaneados
    if (CANCELAR_BTN) {
        CANCELAR_BTN.addEventListener("click", (e) => {
            if (typeof cerrarModalMovimientoLibre === 'function') {
                cerrarModalMovimientoLibre();
            }
            e.preventDefault();
            e.stopPropagation();

            state.yaEscaneados.length = 0;
            INPUT_OCULTO.value = "[]";
            if ("defaultValue" in INPUT_OCULTO) INPUT_OCULTO.defaultValue = "[]";
            INPUT_OCULTO.dispatchEvent(new Event("change", { bubbles: true }));
            LISTAQRS.innerHTML = "";
            state.cacheInfo.clear();
            if (QRINPUT) QRINPUT.value = "";
            if (navigator && typeof navigator.vibrate === "function") navigator.vibrate(60);
        });
    }

    function esError(codigo) {
        const info = state.cacheInfo.get(codigo);
        return info && info.ok === false;
    }

    // Aceptar con Enter
    QRINPUT.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.keyCode === 13) {
            e.preventDefault();
            window.agregarQRMovimientoLibre(QRINPUT.value);
            QRINPUT.value = "";
        }
    });

    // Permitir pegar varios códigos
    QRINPUT.addEventListener("paste", (e) => {
        e.preventDefault();
        const txt = (e.clipboardData || window.clipboardData).getData("text") || "";
        txt.split(/[,\s;]+/)
            .filter(Boolean)
            .forEach((code) => window.agregarQRMovimientoLibre(code));
        QRINPUT.value = "";
    });

    // Antes de enviar, filtrar errores
    FORM.addEventListener("submit", (e) => {
        const validos = state.yaEscaneados.filter((c) => !esError(c));
        const removidos = state.yaEscaneados.length - validos.length;
        INPUT_OCULTO.value = JSON.stringify(validos);
        if (removidos > 0) {
            console.warn(`[QR] ${removidos} código(s) con error no se enviaron`);
        }
    });
}

// Inicialización compatible con Livewire Navigate
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initMovimientosGrua);
} else {
    initMovimientosGrua();
}
document.addEventListener("livewire:navigated", initMovimientosGrua);
