document.addEventListener("DOMContentLoaded", () => {
    // variables
    const QRINPUT = document.getElementById("codigo_general_general"); // input donde escribe el escáner
    const LISTAQRS = document.getElementById("mostrar_qrs"); // contenedor visual de códigos
    const INPUT_OCULTO = document.getElementById("lista_qrs"); // input hidden que viaja al backend
    const FORM = document.getElementById("form-movimiento-general"); // formulario
    const CANCELAR_BTN = document.getElementById("cancelar_btn"); // (opcional) botón cancelar si existe

    // array con los codigos ya escaneados (estado)
    let yaEscaneados = [];

    // si el hidden ya trae datos (editar), los cargamos
    try {
        const inicial = JSON.parse(INPUT_OCULTO.value || "[]");
        if (Array.isArray(inicial)) {
            yaEscaneados = inicial
                .map((v) => String(v).trim().toUpperCase())
                .filter(Boolean);
            yaEscaneados = Array.from(new Set(yaEscaneados)); // dedup
        }
    } catch (e) {
        /* no-op */
    }

    // utilidad: vibración para feedback
    const vibrar = (ms = 100) =>
        navigator &&
        typeof navigator.vibrate === "function" &&
        navigator.vibrate(ms);

    // renderiza los codigos como pequeñas cajas
    function renderLista() {
        // limpiamos el contenedor
        LISTAQRS.innerHTML = "";

        // por cada código del array creamos su “píldora”
        yaEscaneados.forEach((codigo) => {
            const pill = document.createElement("span");
            pill.className = "qr-chip";
            pill.textContent = codigo; // sin "x", solo el código
            pill.dataset.code = codigo; // data attribute por si hace falta localizarlo
            pill.title = "Click para eliminar"; // hint
            // al clickar, eliminar el código de la lista y del hidden
            pill.addEventListener("click", () => {
                yaEscaneados = yaEscaneados.filter((c) => c !== codigo);
                INPUT_OCULTO.value = JSON.stringify(yaEscaneados); // sincronizamos el hidden
                renderLista(); // re-pintamos
                vibrar(60);
            });
            LISTAQRS.appendChild(pill);
        });

        // Pasamos en formato JSON todos los codigos de nuestro array
        INPUT_OCULTO.value = JSON.stringify(yaEscaneados);
    }

    // agregar el código si es válido y no está repetido
    function agregarSiValido(valor) {
        const escaneado = String(valor).trim().toUpperCase();

        // Reglas actuales: debe empezar por "MP" y tener longitud 10
        if (!(escaneado.startsWith("MP") && escaneado.length === 10)) return;

        // comprobar duplicado
        if (!yaEscaneados.includes(escaneado)) {
            // Agregamos el nuevo codigo al array de codigos
            yaEscaneados.push(escaneado);

            // Pasamos en formato JSON todos los codigos de nuestro array
            INPUT_OCULTO.value = JSON.stringify(yaEscaneados);

            // Repintamos como píldoras
            renderLista();

            // Hacemos vibrar el dispositivo para dar una señal de OK
            vibrar(100);
        } else {
            // Si el codigo ha sido escaneado, resaltamos brevemente la píldora existente
            const pill = LISTAQRS.querySelector(`[data-code="${escaneado}"]`);
            if (pill) {
                pill.style.outline = "2px solid #991b1b";
                setTimeout(() => (pill.style.outline = ""), 500);
            }
            vibrar(50);
        }
    }

    // Si se cancela se borran los codigos escaneados (si el botón existe)
    if (CANCELAR_BTN) {
        CANCELAR_BTN.addEventListener("click", () => {
            yaEscaneados = [];
            INPUT_OCULTO.value = JSON.stringify(yaEscaneados);
            renderLista();
        });
    }

    // Cuando se modifique el input de qr:
    QRINPUT.addEventListener("input", () => {
        // Comprueba el valor actual sin espacios alrededor
        const escaneado = QRINPUT.value.trim();

        // Si la longitud típica es 10 y cumple regla, lo agregamos y limpiamos
        if (escaneado.length === 10 && escaneado.startsWith("MP")) {
            agregarSiValido(escaneado);
            // Limpiamos el input para dejarlo listo a un nuevo escaneo
            QRINPUT.value = "";
        } else if (!escaneado.startsWith("MP") && escaneado.length > 10) {
            // Si el formato introducido no cuadra se borra el input
            QRINPUT.value = "";
        }
    });

    // Aceptar también con Enter (algunos escáneres envían Enter al final)
    QRINPUT.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
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
    FORM.addEventListener("submit", () => {
        INPUT_OCULTO.value = JSON.stringify(yaEscaneados);
    });
});
