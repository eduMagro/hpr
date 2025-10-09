document.addEventListener("DOMContentLoaded", () => {
    const QRINPUT = document.getElementById("codigo_general_general");
    const LISTAQRS = document.getElementById("lista_qrs")
    const CANCELAR_BTN = document.getElementById("cancelar_btn");
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
            } else {
                alert("yaescaneado")
            }


            qrinput.value = "";

        } else if (!escaneado.startsWith("MP") && escaneado.length > 10) {
            QRINPUT.value = ""
        }
    }
    )
});
