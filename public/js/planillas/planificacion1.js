//variables
let planillas;
let ordenPlanillas;
let elementos;
let maquinas;
let maquinasDivs;
let btn_transferir
let datosOrdenPlanillaSeleccionado
let ultimoOrdenPlanillaId

//modales
let modalMostrarElementos;
let modalTransferirAMaquina
let modales = []


document.addEventListener("DOMContentLoaded", () => {
    planillas = Array.from(document.querySelectorAll('#todasPlanillas [data-planilla]')).map(div => JSON.parse(div.dataset.planilla));
    elementos = Array.from(document.querySelectorAll('#todosElementos [data-elementos]')).map(div => JSON.parse(div.dataset.elementos));
    ordenPlanillas = Array.from(document.querySelectorAll('#ordenPlanillas [data-orden]')).map(div => JSON.parse(div.dataset.orden));
    maquinas = Array.from(document.querySelectorAll('#maquinas [data-detalles]')).map(div => JSON.parse(div.dataset.detalles));
    maquinasDivs = Array.from(document.getElementsByClassName('maquina'))

    // obtener el id de ordenPlanilla mas alto, asi poder trabaja con id de manera local sin llamar a la bd
    ultimoOrdenPlanillaId = Math.max(...ordenPlanillas.map(o => Number(o.id) || 0)) || 0;

    //btn
    btn_transferir = document.getElementById("transferir_elementos")
    btn_transferir.addEventListener("click", transferirElementos)

    // modales
    modalMostrarElementos = document.getElementById("modal_elementos")
    modalTransferirAMaquina = document.getElementById("modal_transferir_a_maquina")

    modales = [modalMostrarElementos, modalTransferirAMaquina]

    // Agregar Listener de cierre a los modales cuando se clicka fuera de los hijos del mismo
    modales.forEach(modal => {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.add("hidden");
            }
        });
    });

    renderPlanillas()
})

// mostrar en funcion de ordenPlanillas local las planillas en orden asignadas a cada maquina
function renderPlanillas() {

    // limpiar planillas existentes, si hay
    maquinasDivs.forEach(maq => {
        const contenedor = maq.querySelector('.planillas');
        if (contenedor) contenedor.innerHTML = "";
    });


    ordenPlanillas.forEach(planilla => {

        // div que se renderizara
        let div = document.createElement('div');



        maquinasDivs.forEach(maq => {
            let div_maquina_id = JSON.parse(maq.dataset.detalles).id
            if (div_maquina_id == planilla.maquina_id) {
                // creo el div de la planilla
                div.className = "planilla group p-3 flex justify-around items-center border-2 border-emerald-400 hover:border-emerald-600 hover:-translate-y-1 transition-transform duration-75 ease-in-out rounded-xl cursor-grab bg-gradient-to-tr from-neutral-100 to-neutral-200 hover:from-emerald-300 hover:to-emerald-400 active:cursor-grabbing select-none text-center relative";
                div.dataset.ordenId = planilla.id

                // creo los divs del contenido (posicion y codigo planilla)
                let divPosicion = document.createElement('div');
                divPosicion.className = "posicion text-emerald-600 group-hover:text-black text-xs font-bold absolute top-1 left-1 pos-label";
                divPosicion.innerText = planilla.posicion


                // obtenerCodigoPlanilla
                let divCodigoPlanilla = document.createElement('div');
                let codigo_planilla;

                planillas.forEach(planilla_i => {
                    if (planilla_i.id == planilla.planilla_id) {
                        codigo_planilla = planilla_i.codigo
                    }
                });

                divCodigoPlanilla.innerText = codigo_planilla
                divCodigoPlanilla.className = "codigo text-emerald-800 font-semibold"

                div.appendChild(divPosicion)
                div.appendChild(divCodigoPlanilla)

            }
        })

        // pintar la maquina en el div .maquina con misma maquina_id que planilla
        maquinasDivs.forEach(maqDiv => {
            let maqId = maqDiv.getAttribute("data-maquina-id")
            if (maqId == planilla.maquina_id) {
                maqDiv.querySelector('.planillas').appendChild(div);
            }
        });

    });
    agregarClickAPlanillas()
}

// agregar listener click a las planillas, rehacerlo cada vez que se hagan cambios para evitar problemas con nuevos orden_planillas
function agregarClickAPlanillas() {
    let orden_planillas_div = Array.from(document.getElementsByClassName("planilla"))

    orden_planillas_div.forEach(orden_planilla_div => {
        orden_planilla_div.addEventListener("click", (e) => {
            target = e.currentTarget

            let maq_codigo = target.parentElement.parentElement.children[0].innerText
            let cod_planilla = target.children[1].innerText

            let ordenIdSel = Number(target.dataset.ordenId);
            let opSel = ordenPlanillas.find(o => Number(o.id) === ordenIdSel);

            // almacenar id de orden_planilla
            datosOrdenPlanillaSeleccionado = {
                id: target.dataset.ordenId,
                planilla_id: opSel ? opSel.planilla_id : null,
                posicion: target.children[0].innerText.trim(),
                codigo: target.children[1].innerText.trim(),
            };


            // render modal de elementos
            renderModalElementos(target.dataset.ordenId, cod_planilla, maq_codigo)
        })
    });

    resaltarCompis()
}

// procesado sobre datos que se muestran en el modal de elementos
// se le pasan codigoPlanilla y codigoMaquina para acelerar proceso con DOM
function renderModalElementos(ordenPlanillaId, codigoPlanilla, codigoMaquina) {
    // cabecera modal
    let rme_planilla = document.getElementById("seleccion_planilla_codigo");
    rme_planilla.innerText = codigoPlanilla;

    let rme_maquina = document.getElementById("seleccion_maquina_tag");
    rme_maquina.innerText = codigoMaquina;

    // contenedor lista
    let rme_elementos = document.getElementById("seleccion_elementos");
    rme_elementos.innerHTML = "";

    // filtrar elementos por orden_planilla_id
    const elementosDeOrden = elementos.filter(el => Number(el.orden_planilla_id) === Number(ordenPlanillaId));

    // pintar
    elementosDeOrden.forEach((elemento, i) => {
        anadirElemento(rme_elementos, elemento, i);
    });

    // dibujar mini-figuras en los canvas
    dibujarMiniFiguras(rme_elementos);

    // permitir seleccionar/deseleccionar para transferencias (mismo patrón)
    anadirPropiedadTransferible();

    // mostrar modal
    modalMostrarElementos.classList.remove("hidden");
}

function anadirElemento(padre, elemento, n) {
    const idCanvas = `cv-el-${elemento.id}`;

    const html = `
    <div class="p-2 w-full no_seleccionado text-center bg-gradient-to-tr from-indigo-200 to-indigo-300 hover:from-indigo-300 hover:to-indigo-400 hover:-translate-y-1 cursor-pointer rounded-xl flex flex-col items-center transition-all duration-75"
         data-peso="${elemento.peso ?? ''}"
         data-dimensiones="${elemento.dimensiones ?? ''}"
         data-id="${elemento.id}">
        <div class="flex justify-between items-center w-full">
            <div class="text-neutral-600 text-xs font-mono font-semibold">${n + 1}</div>
            <p>${elemento.codigo ?? ''}</p>
            <p><span class="text-red-500 font-semibold">Ø</span>${elemento.diametro ?? ''}</p>
        </div>
        <canvas id="${idCanvas}" class="w-full h-24 bg-white border border-gray-200 rounded-md"></canvas>
    </div>`;

    padre.insertAdjacentHTML('beforeend', html);

    // ajustar tamaño interno y dibujar
    const canvas = document.getElementById(idCanvas);
    if (canvas && typeof window.dibujarFiguraElemento === 'function') {
        const w = Math.max(160, canvas.clientWidth || 160);
        const h = Math.max(100, canvas.clientHeight || 100);
        canvas.width = w;
        canvas.height = h;

        window.dibujarFiguraElemento(canvas.id, elemento.dimensiones || '', elemento.peso ?? 'N/A');
    }
}

function dibujarMiniFiguras(padre) {
    if (typeof window.dibujarFiguraElemento !== 'function') return;

    padre.querySelectorAll('[data-dimensiones]').forEach(card => {
        const canvas = card.querySelector('canvas');
        if (!canvas) return;

        const w = Math.max(160, canvas.clientWidth || 160);
        const h = Math.max(100, canvas.clientHeight || 100);
        canvas.width = w;
        canvas.height = h;

        const dims = card.dataset.dimensiones || '';
        const peso = card.dataset.peso || 'N/A';
        window.dibujarFiguraElemento(canvas.id, dims, peso);
    });
}

function anadirPropiedadTransferible() {
    const elementosEnLista = Array.from(document.getElementsByClassName("no_seleccionado"));

    elementosEnLista.forEach(element => {
        element.addEventListener("click", (e) => {
            const target = e.currentTarget;

            if (target.classList.contains("no_seleccionado")) {
                target.classList.remove("no_seleccionado", "hover:from-indigo-300", "hover:to-indigo-400");
                target.classList.add("seleccionado", "from-indigo-400", "to-emerald-500");
            } else {
                target.classList.add("no_seleccionado", "hover:from-indigo-300", "hover:to-indigo-400");
                target.classList.remove("seleccionado", "from-indigo-400", "to-emerald-500");
            }
        });
    });
}

// resaltamos el color de la misma planilla repartida por otra maquina u otro turno
function resaltarCompis() {
    let orden_planillas_div = Array.from(document.getElementsByClassName("planilla"))
    orden_planillas_div.forEach(orden_planilla_div => {
        orden_planilla_div.addEventListener("mouseenter", (e) => {
            target = e.currentTarget;
            let valorTarget = target.children[1].innerText

            // comparamos todos los ordenPlanilla para que si tienen el mismo valorTarget (codigo planilla) se resalten
            orden_planillas_div.forEach(opd /* opd -> orden planilla div */ => {
                if (valorTarget == opd.children[1].innerText) {
                    opd.classList.remove("from-neutral-100", "from-neutral-200")
                    opd.classList.add("border-indigo-500", "from-indigo-200", "to-indigo-300", "-translate-y-[1px]")
                    

                    // letras
                    let rc_codigo = opd.querySelector(".codigo")
                    let rc_posicion = opd.querySelector(".posicion")
                    rc_codigo.classList.remove("")
                }
            });
        })

        // cuando el raton sale del div de una planilla resetCom
        orden_planilla_div.addEventListener("mouseleave", () => {
            resetCompis()
        })
    })
}

// deja de resaltar a las planillas con el mismo codigo
function resetCompis() {
    let orden_planillas_div = Array.from(document.getElementsByClassName("planilla"))
    orden_planillas_div.forEach(opd => {
        opd.classList.remove("border-indigo-500", "from-indigo-200", "to-indigo-300", "-translate-y-[1px]")
        opd.classList.add("from-neutral-100", "from-neutral-200")
    })
}

// elegir a que maquina movel los elementos
function seleccionarMaquinaParaMovimiento() {
    // cambiar de modal
    modal_elementos.classList.add("hidden")
    modalTransferirAMaquina.classList.remove("hidden")

    // obtener maquinas e inicializar maquina seleccionada a null
    let smpm_maquinas = Array.from(document.getElementsByClassName("maquina_transferir"));
    let maquinaSeleccionadaId = null;

    // estilos y obtencion de id
    smpm_maquinas.forEach(m => {
        m.addEventListener("click", () => {
            smpm_maquinas.forEach(x => {
                x.classList.add("hover:bg-neutral-400", "bg-neutral-300");
                x.classList.remove("bg-emerald-400", "maquina_seleccionada");
            });
            m.classList.remove("hover:bg-neutral-400", "bg-neutral-300");
            m.classList.add("bg-emerald-400", "maquina_seleccionada");

            // obtener id maquina seleccionada
            maquinaSeleccionadaId = m.getAttribute("data-id");
        });
    });
}

// proceso interno de treansferencia de un elemento a otra maquina, aqui se contempla si el elemento va a crear una nueva ordenPlanilla, unificarse con otra...
function transferirElementos() {

    let elementosSeleccionados = Array.from(document.getElementsByClassName("seleccionado"))

    let maquinaSeleccionada = document.querySelector(".maquina_seleccionada")
    let maquinaId = maquinaSeleccionada.dataset.id

    /*
    Casos:
        1. No hay una planilla con el mismo codigo en la maquina seleccionada => se crea un nuevo ordenPlanilla en la ultima posicion posible del
        orden de las planillas para esa maquina, se cambian elemento[ordenPlanillaId por la nueva planilla, maquinaId por la maquina seleccionada]
        2. Ya hay una planilla con el mismo codigo en la maquina seleccionada => 
            @Fusionar?
            Si: se cambian elemento[ordenPlanillaId por el id de la planilla existente, maquinaId por la maquina seleccionada]
            No: se crea un nuevo ordenPlanilla en la ultima posicion posible del
            orden de las planillas para esa maquina, se cambian elemento[ordenPlanillaId por la nueva planilla, maquinaId por la maquina seleccionada]
     */

    // revisamos si existe una planilla con el mismo codigo en la maquina seleccionada

    // obtenemos el div de la maquina a transferir en maquinaDiv 
    let maquinaDiv;
    maquinasDivs.forEach(div => {
        let detalles = JSON.parse(div.dataset.detalles)
        if (detalles.id == maquinaId) maquinaDiv = div;
    });

    // comprobamos si existe la planilla
    let existe = false
    let te_planillas = maquinaDiv.querySelectorAll(".planilla")
    te_planillas.forEach(planilla => {
        if (planilla.children[1].innerText.trim() == datosOrdenPlanillaSeleccionado.codigo) {
            existe = true;
        }
    });

    // existe la planilla?
    // si existe:
    if (existe) {
        console.log("NO IMPLEMENTADO: Ya hay una planilla con el mismo codigo en la maquina seleccionada")
    } else { //no existe
        // como en la bd la id de ordenPlanilla es "Auto Increment", tomaremos el ultimo valor existente y le agregaremos uno, usamos una variable
        // global por si el proceso se repite durante la sesion en la pagina
        let posicionNueva = (te_planillas.length + 1)

        // creamos localmente la nueva ordenPlanilla
        let nuevaOrdenPlanilla = {
            "id": Number(ultimoOrdenPlanillaId) + 1,
            "maquina_id": Number(maquinaId),
            "planilla_id": Number(datosOrdenPlanillaSeleccionado.planilla_id),
            "posicion": posicionNueva,
        }

        ultimoOrdenPlanillaId = nuevaOrdenPlanilla.id; // sincronizar el global
        let nuevoOrdenId = nuevaOrdenPlanilla.id;

        // la agregamos a la variable global
        ordenPlanillas.push(nuevaOrdenPlanilla)

        // asignar los elementos a la nueva ordenPlanilla y maquina, comprobamos por codigo
        elementosSeleccionados.forEach(elementoSeleccionado => {
            let codigoSeleccionado = elementoSeleccionado.querySelector("p").innerText.trim()

            elementos.forEach(elemento2 => {
                if (elemento2.codigo == codigoSeleccionado) {
                    elemento2.maquina_id = Number(maquinaId)
                    elemento2.orden_planilla_id = nuevoOrdenId
                }
            });
        });

        cerrarModales()
        renderPlanillas()

    }
}

function cerrarModales() {
    modales.forEach(modal => {
        modal.classList.add("hidden")
    });
}