document.addEventListener("DOMContentLoaded", () => {
    const MAQUINAS = Array.from(document.getElementsByClassName("maquina"))
    const BOTONES = [document.getElementById("nave1"), document.getElementById("nave2"), document.getElementById("ambas")]

    BOTONES.forEach(boton => {
        boton.addEventListener("click", () => {
            mostrarPorNave(MAQUINAS, boton)
        })
    });




    MAQUINAS.forEach(maquina => {
        const DETALLES = JSON.parse(maquina.dataset.detalles)
    });
})


function mostrarPorNave(maquinas, boton) {
    texto = boton.textContent;
    
    if (texto == "Nave 1") {
        
    }
}