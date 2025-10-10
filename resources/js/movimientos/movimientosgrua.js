document.addEventListener("DOMContentLoaded", () => {
    const QRINPUT = document.getElementById("codigo_general_general");
    const LISTAQRS = document.getElementById("mostrar_qrs");
    const CANCELAR_BTN = document.getElementById("cancelar_btn");
    const INPUT_OCULTO = document.getElementById("lista_qrs");
    let yaEscaneados = [];

    // Si se cancela se borran los codigos escaneados
    CANCELAR_BTN.addEventListener("click", () => {
        yaEscaneados = [];
    });

    // Cuando se modifique el input de qr:
    QRINPUT.addEventListener("input", () => {
        // Comprueba el valor actual sin espacios alrededor
        let escaneado = QRINPUT.value.trim();

        // Si empieza por "MP" y la longitud del codigo es exactamente 10 caracteres:
        if (escaneado.startsWith("MP") && escaneado.length === 10) {
            // Recojo el valor de los qr escaneados por el momento
            const ACTUAL = LISTAQRS.textContent.trim();

            // Compruebo que el ultimo qr escaneado no haya sido escaneado
            if (!yaEscaneados.includes(escaneado)) {
                // Si ya hay algun elemento escaneado se agrega el nuevo seguido de una ","
                if (ACTUAL) {
                    LISTAQRS.textContent = ACTUAL + ", " + escaneado;
                } else {
                    LISTAQRS.textContent = escaneado;
                }

                // Agregamos el nuevo codigo al array de codigos
                yaEscaneados.push(escaneado);

                // Pasamos en formato JSON todos los codigos de nuestro array
                INPUT_OCULTO.value = JSON.stringify(yaEscaneados);

                // Hacemos vibrar el dispositivo para dar una senal al trabajador de que ha escaneado un codigo valido
                navigator.vibrate(100);
            } else {
                // Si el codigo ha sido escaneado alerta provisional
                alert("Ya escaneado");
            }

            // Limpiamos el input para dejarlo listo a un nuevo escaneo
            QRINPUT.value = "";

            // Si el formato introducido no cuadra se borra el input
        } else if (!escaneado.startsWith("MP") && escaneado.length > 10) {
            QRINPUT.value = "";
        }
    });
});