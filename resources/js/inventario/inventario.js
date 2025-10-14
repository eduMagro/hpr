document.addEventListener("DOMContentLoaded", () => {
    // DIVS SECTORES
    const SECTORES = Array.from(document.getElementsByClassName("escondible"));

    // DIV CON TODO EL CONTENIDO
    const CONTENIDO = document.getElementById("contenido");

    aparecer(CONTENIDO);

    mostrarOcultarSectores(SECTORES, CONTENIDO);
});

// FUNCION PARA OCULTAR O MOSTRAR SECTORES CUANDO SE ACCEDE O SALE DE ELLOS PARA NO SATURAR LA PANTALLA DE INFORMACIÓN
function mostrarOcultarSectores(sectores, contenido) {
    // POR CADA ELEMENTO SE APLICA UN EVENTO "CLICK"
    sectores.forEach((e) => {
        e.addEventListener("click", (esteElemento) => {
            // SI ACTUALMENTE ESTAN MOSTRANDOSE LOS DATOS DEL SECTOR CLICKADO, SE LE QUITA LA CLASE DE "mostrandoDetalles" Y SE VUELVEN A MOSTRAR LOS DEMAS SECTORES
            if (e.classList.contains("mostrandoDetalles")) {
                reaparecer(contenido)
                e.classList.remove("mostrandoDetalles");

                // SE MUESTRAN LOS SECTORES OCULTOS
                sectores.forEach((f) => {
                    f.classList.remove("hidden");
                    f.style.pointerEvents = "auto";

                    // REAPLICAR TAMANO QUE OCUPA TODA LA PAGINA
                    const BOTON = f.firstElementChild;
                    console.log(BOTON);
                    e.classList.remove("h-[5vh]");
                    e.classList.add("h-full");
                });

                // SI NO ESTABA MOSTRANDOSE EL SECTOR ...
            } else {
                // SE LE AGREGA LA CLASE "mostrandoDetalles"
                e.classList.add("mostrandoDetalles");

                // CAMBIAR EL TAMANO DEL BOTON PARA QUE NO SIGA OCUPANDO UN GRAN PORCENTAJE DE PANTALLA SI HAY POCOS SECTORES
                e.classList.add("h-[5vh]");
                e.classList.remove("h-full");

                // ... SE OCULTAN TODOS LOS SECTORES QUE NO SON EL SECTOR CLICKADO
                sectores.forEach((f) => {
                    if (esteElemento != f) {
                        if (f != e) {
                            f.classList.add("hidden");
                            f.style.pointerEvents = "none";
                        }
                    }
                });
            }
        });
    });
}

function aparecer(contenido) {
    contenido.classList.remove("opacity-0");
}

function reaparecer(contenido) {
    contenido.classList.remove("transform", "transition-all", "duration-200");
    contenido.classList.add("opacity-0");
    aparecer(contenido)
}
