function initMovimientosGrua() {
    // variables
    const QRINPUT = document.getElementById("codigo_general_general"); // input donde escribe el escáner
    const LISTAQRS = document.getElementById("mostrar_qrs"); // contenedor visual de códigos
    const INPUT_OCULTO = document.getElementById("lista_qrs"); // input hidden que viaja al backend
    const FORM = document.getElementById("form-movimiento-general"); // formulario
    const CANCELAR_BTN = document.getElementById("cancelar_btn"); // botón cancelar
    const API_INFO_URL = LISTAQRS.dataset.apiInfoUrl || "/api/codigos/info";

    // array con los codigos ya escaneados (estado)
    let yaEscaneados = [];

    // cache de respuestas para no repetir llamadas
    const cacheInfo = new Map(); // code -> {ok, ...}

    // si el hidden ya trae datos (editar), los cargamos
    try {
        const inicial = JSON.parse(INPUT_OCULTO.value || "[]");
        if (Array.isArray(inicial)) {
            yaEscaneados = Array.from(
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

    // utilidad: vibración para feedback
    const vibrar = (ms = 100) =>
        navigator &&
        typeof navigator.vibrate === "function" &&
        navigator.vibrate(ms);

    // utilidad: sincroniza el hidden con el array actual
    function syncHidden(filtrarErrores = false) {
        const lista = filtrarErrores
            ? yaEscaneados.filter((c) => !esError(c))
            : yaEscaneados;
        INPUT_OCULTO.value = JSON.stringify(lista);
    }

    // formatea el contenido final del chip con la info recibida del backend
    function formatearChip(info) {
        // info: { ok, clase, codigo, sigla, tipo, diametro, longitud }
        const partes = [];
        if (info.sigla) partes.push(info.sigla); // B / E / PAQ...
        partes.push(info.codigo); // MP********
        if (info.diametro !== null && info.diametro !== undefined) {
            partes.push(`Ø${info.diametro} mm`);
        }
        if (info.longitud) {
            const isEncarretado = String(info.tipo || "")
                .toLowerCase()
                .includes("encarretado");
            if (!isEncarretado) partes.push(`L:${info.longitud} mm`);
        }
        return partes.join(" · ");
    }

    // crea un chip básico (mientras carga) y lo deja listo para eliminarse con click
    function crearChipBase(codigo) {
        const pill = document.createElement("span");
        pill.className = "qr-chip loading"; // estado “cargando”
        pill.textContent = codigo; // contenido provisional: el código
        pill.dataset.code = codigo; // data attribute por si hace falta localizarlo
        pill.title = "Click para eliminar"; // hint

        // al clickar, eliminar el código de la lista y del hidden
        pill.addEventListener("click", () => {
            yaEscaneados = yaEscaneados.filter((c) => c !== codigo);
            pill.remove();
            syncHidden(); // sincronizamos el hidden
            vibrar(60);
        });

        return pill;
    }

    // actualiza un chip ya pintado con la info (si el usuario no lo borró antes)
    function actualizarChipConInfo(codigo, info) {
        const pill = LISTAQRS.querySelector(`[data-code="${codigo}"]`);
        if (!pill) return; // si ya se borró, no hacemos nada
        pill.classList.remove("loading", "error");

        if (!info || info.ok === false) {
            pill.classList.add("error");
            pill.textContent = `${codigo} · error`;
            return;
        }
        pill.textContent = formatearChip(info);
    }

    // hace la llamada asíncrona; no bloquea la UI
    async function cargarInfoAsync(codigo) {
        // si ya está en cache, úsalo
        if (cacheInfo.has(codigo)) {
            actualizarChipConInfo(codigo, cacheInfo.get(codigo));
            return;
        }
        try {
            const url = new URL(API_INFO_URL, window.location.origin);
            url.searchParams.set("code", codigo);

            const res = await fetch(url.toString(), {
                method: "GET",
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            });

            const data = await res.json().catch(() => ({}));
            cacheInfo.set(codigo, data);
            actualizarChipConInfo(codigo, data);
        } catch (e) {
            const data = { ok: false, error: "network" };
            cacheInfo.set(codigo, data);
            actualizarChipConInfo(codigo, data);
        }
    }

    // renderiza SOLO el nuevo chip y lanza su fetch en background
    function renderChipNuevo(codigoNuevo) {
        const pill = crearChipBase(codigoNuevo);
        LISTAQRS.appendChild(pill); // pintamos ya
        cargarInfoAsync(codigoNuevo); // enriquecemos en background
    }

    // repinta toda la lista (por ejemplo, tras cancelar)
    function renderLista() {
        LISTAQRS.innerHTML = "";
        yaEscaneados.forEach((code) => renderChipNuevo(code));
        syncHidden();
    }

    // agregar el código si es válido y no está repetido
    function agregarSiValido(valor) {
        const escaneado = String(valor).trim();
        if (!escaneado) return; // vacío, fuera

        // duplicado exacto (respetando may/min tal cual)
        if (!yaEscaneados.includes(escaneado)) {
            yaEscaneados.push(escaneado);
            syncHidden();
            renderChipNuevo(escaneado); // hace la llamada async y “devuelve el elemento”
            vibrar(100);
        } else {
            const pill = LISTAQRS.querySelector(`[data-code="${escaneado}"]`);
            if (pill) {
                pill.style.outline = "2px solid #991b1b";
                setTimeout(() => (pill.style.outline = ""), 500);
            }
            vibrar(50);
        }
    }

    // Si se cancela se borran los códigos escaneados (si el botón existe)
    if (CANCELAR_BTN) {
        CANCELAR_BTN.addEventListener("click", (e) => {
            cerrarModalMovimientoLibre();
            e.preventDefault();
            e.stopPropagation();
            resetEscaneos();
        });
    }

    // devuelve true si ese código está marcado como error en el caché
    function esError(codigo) {
        const info = cacheInfo.get(codigo);
        return info && info.ok === false;
    }

    // Cuando se modifique el input de qr:
    QRINPUT.addEventListener("input", () => {});

    // Aceptar también con Enter (algunos escáneres envían Enter al final)
    QRINPUT.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.keyCode === 13) {
            e.preventDefault();
            agregarSiValido(QRINPUT.value);
            QRINPUT.value = "";
        }
    });

    // Permitir pegar varios códigos separados por coma/espacio/salto de línea
    QRINPUT.addEventListener("paste", (e) => {
        e.preventDefault();
        const txt =
            (e.clipboardData || window.clipboardData).getData("text") || "";
        txt.split(/[,\s;]+/)
            .filter(Boolean)
            .forEach(agregarSiValido);
        QRINPUT.value = "";
    });

    // Pintamos lo que pudiera venir precargado (edición)
    renderLista();

    // Seguridad: antes de enviar garantizamos que el hidden esté actualizado
    FORM.addEventListener("submit", (e) => {
        // calcula válidos (excluye los que ya están marcados como error)
        const validos = yaEscaneados.filter((c) => !esError(c));
        const removidos = yaEscaneados.length - validos.length;

        // escribe SOLO los válidos al hidden
        INPUT_OCULTO.value = JSON.stringify(validos);

        // si quieres, deja un aviso en consola (no molesta al usuario)
        if (removidos > 0) {
            console.warn(
                `[QR] ${removidos} código(s) con error no se enviaron`
            );
        }
    });

    function resetEscaneos() {
        // Vacía el array sin perder la referencia (más seguro si alguien lo retiene)
        yaEscaneados.length = 0;

        // Sincroniza hidden y su defaultValue por si el DOM se rehidrata
        INPUT_OCULTO.value = "[]";
        if ("defaultValue" in INPUT_OCULTO) INPUT_OCULTO.defaultValue = "[]";
        INPUT_OCULTO.dispatchEvent(new Event("change", { bubbles: true }));

        // Limpia chips y caché
        LISTAQRS.innerHTML = "";
        cacheInfo.clear();

        // Limpia el input visible
        if (QRINPUT) QRINPUT.value = "";

        vibrar(60);
    }
}

// Inicialización compatible con Livewire Navigate
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initMovimientosGrua);
} else {
    initMovimientosGrua();
}
document.addEventListener("livewire:navigated", initMovimientosGrua);
