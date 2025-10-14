document.addEventListener("DOMContentLoaded", () => {

    // variables
    const QRINPUT = document.getElementById("codigo_general");
    const LISTA = document.getElementById("qr_escaneados");
    const INPUT_OCULTO = document.getElementById("lista_qrs");
    let listaQrs = [];

    // se comprueba si el codigo es igual a 10 cuando se modifica el input ya que es la longitud estandar de los codigos 
    QRINPUT.addEventListener("input", () => {
        const valorqr = QRINPUT.value.trim();

        if (valorqr.length == 10) {
            listaQrs = agregarQrALista(listaQrs, valorqr);

            // se muestra en pantalla los codigos escaneados por el momento
            LISTA.textContent = listaQrs.join(", ");

            // se guardan los valores en formato json en un input oculto
            INPUT_OCULTO.value = JSON.stringify(listaQrs);

            // se borra el contenido del input para poder seguir escaneando codigos
            QRINPUT.value = "";

            // activar vibracion 100ms para indicar que el codigo se ha agregado
            navigator.vibrate(100);
        }
    });
});

// agregar el codigo a la lista si no se ha agregado aun
function agregarQrALista(anterioresQr, nuevoQr) {
    if (!anterioresQr.includes(nuevoQr)) {
        anterioresQr.push(nuevoQr);
    }
    return anterioresQr;
}
