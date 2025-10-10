document.addEventListener("DOMContentLoaded", () => {
    const QRINPUT = document.getElementById("codigo_general_general");
    const LISTAQRS = document.getElementById("mostrar_qrs")
    const CANCELAR_BTN = document.getElementById("cancelar_btn");
    const INPUT_OCULTO = document.getElementById("lista_qrs");
    let yaEscaneados = [];

    CANCELAR_BTN.addEventListener("click", () => {
        yaEscaneados = [];
    })




    QRINPUT.addEventListener("input", () => {


        let escaneado = QRINPUT.value.trim()


        if (escaneado.startsWith("MP") && escaneado.length === 10) {

            const ACTUAL = LISTAQRS.textContent.trim()

            if (!yaEscaneados.includes(escaneado)) {
                if (ACTUAL) {
                    LISTAQRS.textContent = ACTUAL + ", " + escaneado;
                } else {
                    LISTAQRS.textContent = escaneado;
                }
                yaEscaneados.push(escaneado)
                INPUT_OCULTO.value = JSON.stringify(yaEscaneados);
                console.log(INPUT_OCULTO.value)
                navigator.vibrate(100);
            } else {
                alert("yaescaneado")
            }


            QRINPUT.value = "";

        } else if (!escaneado.startsWith("MP") && escaneado.length > 10) {
            QRINPUT.value = ""
        }
    }
    )
});

// agregar el codigo a la lista si no se ha agregado aun
function agregarQrALista(anterioresQr, nuevoQr) {
    if (!anterioresQr.includes(nuevoQr)) {
        anterioresQr.push(nuevoQr);
    }
    return anterioresQr;
}
