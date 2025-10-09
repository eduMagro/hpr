document.addEventListener('DOMContentLoaded', () => {

    // DIVS SECTORES
    const SECTORES = Array.from(document.getElementsByClassName("escondible"));

    // DIV CON TODO EL CONTENIDO
    document.getElementById("contenido").classList.remove("opacity-0")

    mostrarOcultarSectores(SECTORES);
})


// FUNCION PARA OCULTAR O MOSTRAR SECTORES CUANDO SE ACCEDE O SALE DE ELLOS PARA NO SATURAR LA PANTALLA DE INFORMACIÓN
function mostrarOcultarSectores(sectores) {

    // POR CADA ELEMENTO SE APLICA UN EVENTO "CLICK"
    Array.from(sectores).forEach(e => {
        e.addEventListener("click", (esteElemento) => {

            // SI ACTUALMENTE ESTAN MOSTRANDOSE LOS DATOS DEL SECTOR CLICKADO, SE LE QUITA LA CLASE DE "mostrandoDetalles" Y SE VUELVEN A MOSTRAR LOS DEMAS SECTORES
            if (e.classList.contains("mostrandoDetalles")) {
                e.classList.remove("mostrandoDetalles")

                // SE MUESTRAN LOS SECTORES OCULTOS
                sectores.forEach(f => {
                    f.classList.remove("hidden")
                    f.classList.remove("no-click")

                    // REAPLICAR TAMANO QUE OCUPA TODA LA PAGINA
                    const BOTON = f.firstElementChild
                    console.log(BOTON)
                    BOTON.classList.remove("h-[5vh]")
                    BOTON.classList.add("h-full")
                })

                // SI NO ESTABA MOSTRANDOSE EL SECTOR ...
            } else {
                // SE LE AGREGA LA CLASE "mostrandoDetalles"
                e.classList.add("mostrandoDetalles")

                // CAMBIAR EL TAMANO DEL BOTON PARA QUE NO SIGA OCUPANDO UN GRAN PORCENTAJE DE PANTALLA SI HAY POCOS SECTORES
                const BOTON = e.firstElementChild
                console.log(BOTON)
                BOTON.classList.add("h-[5vh]")
                BOTON.classList.remove("h-full")

                // ... SE OCULTAN TODOS LOS SECTORES QUE NO SON EL SECTOR CLICKADO
                sectores.forEach(f => {
                    if (esteElemento != f) {
                        if (f != e) {
                            f.classList.add("hidden")
                            f.classList.add("no-click")
                        }
                    }
                });
            }
        })
    });
}

