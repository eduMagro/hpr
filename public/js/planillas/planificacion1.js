//variables
let planillas;
let ordenPlanillas;
let elementos;
let maquinas;
let maquinasDivs;
let btn_transferir;
let datosOrdenPlanillaSeleccionado;
let ultimoOrdenPlanillaId;

//modales
let modalMostrarElementos;
let modalTransferirAMaquina;
let modalGuardar;
let modalElegirOrden;
let modales = [];

// constante elementos original, servira para referenciar los cambios realizados
let ELEMENTOS_ORIGINAL;

document.addEventListener("DOMContentLoaded", () => {
    planillas = Array.from(
        document.querySelectorAll("#todasPlanillas [data-planilla]")
    ).map((div) => JSON.parse(div.dataset.planilla));

    elementos = Array.from(
        document.querySelectorAll("#todosElementos [data-elementos]")
    ).map((div) => JSON.parse(div.dataset.elementos));

    ELEMENTOS_ORIGINAL = JSON.parse(JSON.stringify(elementos));

    ordenPlanillas = Array.from(
        document.querySelectorAll("#ordenPlanillas [data-orden]")
    ).map((div) => JSON.parse(div.dataset.orden));

    maquinas = Array.from(
        document.querySelectorAll("#maquinas [data-detalles]")
    ).map((div) => JSON.parse(div.dataset.detalles));

    maquinasDivs = Array.from(document.getElementsByClassName("maquina"));

    // obtener el id de ordenPlanilla mas alto, asi poder trabaja con id de manera local sin llamar a la bd
    ultimoOrdenPlanillaId =
        Math.max(...ordenPlanillas.map((o) => Number(o.id) || 0)) || 0;

    //btn
    btn_transferir = document.getElementById("transferir_elementos");
    btn_transferir.addEventListener("click", transferirElementos);

    // modales
    modalMostrarElementos = document.getElementById("modal_elementos");
    modalTransferirAMaquina = document.getElementById(
        "modal_transferir_a_maquina"
    );
    modalGuardar = document.getElementById("modal_guardar");
    modalElegirOrden = document.getElementById("modal_elegir_orden");
    modales = [
        modalMostrarElementos,
        modalTransferirAMaquina,
        modalElegirOrden,
    ];

    // Agregar Listener de cierre a los modales cuando se clicka fuera de los hijos del mismo
    modales.forEach((modal) => {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.add("hidden");
            }
        });
    });

    renderPlanillas();
    resaltarCompis();
});

// mostrar en funcion de ordenPlanillas local las planillas en orden asignadas a cada maquina
function renderPlanillas() {
    // limpiar planillas existentes, si hay
    maquinasDivs.forEach((maq) => {
        const contenedor = maq.querySelector(".planillas");
        if (contenedor) contenedor.innerHTML = "";
    });

    ordenPlanillas.forEach((planilla) => {
        // div que se renderizara
        let div = document.createElement("div");

        maquinasDivs.forEach((maq) => {
            let div_maquina_id = JSON.parse(maq.dataset.detalles).id;
            if (div_maquina_id == planilla.maquina_id) {
                // creo el div de la planilla
                div.className =
                    "planilla group p-3 flex justify-around items-center border-2 border-emerald-400 hover:border-emerald-600 hover:-translate-y-1 transition-transform duration-75 ease-in-out rounded-xl cursor-grab bg-gradient-to-tr from-neutral-100 to-neutral-200 hover:from-emerald-300 hover:to-emerald-400 active:cursor-grabbing select-none text-center relative";
                div.dataset.ordenId = planilla.id;

                // creo los divs del contenido (posicion y codigo planilla)
                let divPosicion = document.createElement("div");
                divPosicion.className =
                    "posicion text-emerald-600 group-hover:text-black text-xs font-bold absolute top-1 left-1 pos-label";
                divPosicion.innerText = planilla.posicion;

                // obtenerCodigoPlanilla
                let divCodigoPlanilla = document.createElement("div");
                let codigo_planilla;

                planillas.forEach((planilla_i) => {
                    if (planilla_i.id == planilla.planilla_id) {
                        codigo_planilla = planilla_i.codigo;
                    }
                });

                divCodigoPlanilla.innerText = codigo_planilla;
                divCodigoPlanilla.className =
                    "codigo text-emerald-800 font-semibold";

                div.dataset.codigo = codigo_planilla;

                div.appendChild(divPosicion);
                div.appendChild(divCodigoPlanilla);
            }
        });

        // pintar la maquina en el div .maquina con misma maquina_id que planilla
        maquinasDivs.forEach((maqDiv) => {
            let maqId = maqDiv.getAttribute("data-maquina-id");
            if (maqId == planilla.maquina_id) {
                maqDiv.querySelector(".planillas").appendChild(div);
            }
        });
    });
    agregarClickAPlanillas();
}

// agregar listener click a las planillas, rehacerlo cada vez que se hagan cambios para evitar problemas con nuevos orden_planillas
function agregarClickAPlanillas() {
    let orden_planillas_div = Array.from(
        document.getElementsByClassName("planilla")
    );

    orden_planillas_div.forEach((orden_planilla_div) => {
        orden_planilla_div.addEventListener("click", (e) => {
            target = e.currentTarget;

            let maq_codigo =
                target.parentElement.parentElement.children[0].innerText;
            let cod_planilla = target.children[1].innerText;

            let ordenIdSel = Number(target.dataset.ordenId);
            let opSel = ordenPlanillas.find((o) => Number(o.id) === ordenIdSel);

            // almacenar id de orden_planilla
            datosOrdenPlanillaSeleccionado = {
                id: target.dataset.ordenId,
                planilla_id: opSel ? opSel.planilla_id : null,
                posicion: target.children[0].innerText.trim(),
                codigo: target.children[1].innerText.trim(),
            };

            // render modal de elementos
            renderModalElementos(
                target.dataset.ordenId,
                cod_planilla,
                maq_codigo
            );
        });
    });
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
    const elementosDeOrden = elementos.filter(
        (el) => Number(el.orden_planilla_id) === Number(ordenPlanillaId)
    );

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
         data-peso="${elemento.peso ?? ""}"
         data-dimensiones="${elemento.dimensiones ?? ""}"
         data-id="${elemento.id}">
        <div class="flex justify-between items-center w-full">
            <div class="text-neutral-600 text-xs font-mono font-semibold">${
                n + 1
            }</div>
            <p>${elemento.codigo ?? ""}</p>
            <p><span class="text-red-500 font-semibold">Ø</span>${
                elemento.diametro ?? ""
            }</p>
        </div>
        <canvas id="${idCanvas}" class="w-full h-24 bg-white border border-gray-200 rounded-md"></canvas>
    </div>`;

    padre.insertAdjacentHTML("beforeend", html);

    // ajustar tamaño interno y dibujar
    const canvas = document.getElementById(idCanvas);
    if (canvas && typeof window.dibujarFiguraElemento === "function") {
        const w = Math.max(160, canvas.clientWidth || 160);
        const h = Math.max(100, canvas.clientHeight || 100);
        canvas.width = w;
        canvas.height = h;

        window.dibujarFiguraElemento(
            canvas.id,
            elemento.dimensiones || "",
            elemento.peso ?? "N/A"
        );
    }
}

function dibujarMiniFiguras(padre) {
    if (typeof window.dibujarFiguraElemento !== "function") return;

    padre.querySelectorAll("[data-dimensiones]").forEach((card) => {
        const canvas = card.querySelector("canvas");
        if (!canvas) return;

        const w = Math.max(160, canvas.clientWidth || 160);
        const h = Math.max(100, canvas.clientHeight || 100);
        canvas.width = w;
        canvas.height = h;

        const dims = card.dataset.dimensiones || "";
        const peso = card.dataset.peso || "N/A";
        window.dibujarFiguraElemento(canvas.id, dims, peso);
    });
}

function anadirPropiedadTransferible() {
    const elementosEnLista = Array.from(
        document.getElementsByClassName("no_seleccionado")
    );

    elementosEnLista.forEach((element) => {
        element.addEventListener("click", (e) => {
            const target = e.currentTarget;

            if (target.classList.contains("no_seleccionado")) {
                target.classList.remove(
                    "no_seleccionado",
                    "hover:from-indigo-300",
                    "hover:to-indigo-400"
                );
                target.classList.add(
                    "seleccionado",
                    "to-indigo-400",
                    "from-emerald-500"
                );
            } else {
                target.classList.add(
                    "no_seleccionado",
                    "hover:from-indigo-300",
                    "hover:to-indigo-400"
                );
                target.classList.remove(
                    "seleccionado",
                    "to-indigo-400",
                    "from-emerald-500"
                );
            }
        });
    });
}

// resaltamos el color de la misma planilla repartida por otra maquina u otro turno
let _hlCode = null;
let _hlNodes = [];

function _clearHighlight() {
    if (!_hlNodes.length) return;
    _hlNodes.forEach((opd) => {
        opd.classList.remove(
            "border-indigo-500",
            "from-indigo-200",
            "to-indigo-300",
            "-translate-y-[1px]"
        );
        opd.classList.add("from-neutral-100", "to-neutral-200");
        opd.querySelector(".codigo")?.classList.remove("text-indigo-900");
        opd.querySelector(".codigo")?.classList.add("text-emerald-800");
        opd.querySelector(".posicion")?.classList.remove("text-indigo-700");
        opd.querySelector(".posicion")?.classList.add("text-emerald-600");
    });
    _hlNodes = [];
    _hlCode = null;
}

function resaltarCompis() {
    const root = document; // o el contenedor que envuelve todas las .planilla

    root.addEventListener(
        "mouseover",
        (e) => {
            const card = e.target.closest(".planilla");
            if (!card) return;

            const code = (
                card.dataset.codigo ||
                card.querySelector(".codigo")?.textContent ||
                ""
            ).trim();
            if (!code || code === _hlCode) return;

            _clearHighlight();

            const matches = document.querySelectorAll(
                `.planilla[data-codigo="${CSS.escape(code)}"]`
            );
            matches.forEach((opd) => {
                if (opd === card) return;
                opd.classList.remove("from-neutral-100", "to-neutral-200");
                opd.classList.add(
                    "border-indigo-500",
                    "from-indigo-200",
                    "to-indigo-300",
                    "-translate-y-[1px]"
                );
                opd.querySelector(".codigo")?.classList.add("text-indigo-900");
                opd.querySelector(".codigo")?.classList.remove(
                    "text-emerald-800"
                );
                opd.querySelector(".posicion")?.classList.add(
                    "text-indigo-700"
                );
                opd.querySelector(".posicion")?.classList.remove(
                    "text-emerald-600"
                );
            });

            _hlNodes = Array.from(matches);
            _hlCode = code;
        },
        { passive: true }
    );

    root.addEventListener(
        "mouseout",
        (e) => {
            // si sigues dentro de otra .planilla con el mismo código, no limpies
            const to = e.relatedTarget?.closest?.(".planilla");
            if (to) {
                const toCode = (
                    to.dataset.codigo ||
                    to.querySelector(".codigo")?.textContent ||
                    ""
                ).trim();
                if (toCode && toCode === _hlCode) return;
            }
            _clearHighlight();
        },
        { passive: true }
    );
}

// elegir a que maquina movel los elementos
function seleccionarMaquinaParaMovimiento() {
    // cambiar de modal
    modalMostrarElementos.classList.add("hidden");
    modalTransferirAMaquina.classList.remove("hidden");

    // obtener maquinas e inicializar maquina seleccionada a null
    let smpm_maquinas = Array.from(
        document.getElementsByClassName("maquina_transferir")
    );
    let maquinaSeleccionadaId = null;

    // estilos y obtencion de id
    smpm_maquinas.forEach((maquina) => {
        maquina.addEventListener("click", () => {
            smpm_maquinas.forEach((maquina_2) => {
                if (maquina_2 === maquina) {
                    maquina_2.classList.remove("maquina_no_seleccionada");
                    maquina_2.classList.add("maquina_si_seleccionada");
                } else {
                    maquina_2.classList.remove("maquina_si_seleccionada");
                    maquina_2.classList.add("maquina_no_seleccionada");
                }
            });

            // obtener id maquina seleccionada
            maquinaSeleccionadaId = maquina.dataset.id;
            btn_transferir.innerHTML = `TRANSFERIR A <span class="chiptransferirA transition-all duration-150">${maquina.children[0].innerText}</span>`;
        });
    });
}

// proceso interno de treansferencia de un elemento a otra maquina, aqui se contempla si el elemento va a crear una nueva ordenPlanilla, unificarse con otra...
function transferirElementos() {
    let elementosSeleccionados = Array.from(
        document.getElementsByClassName("seleccionado")
    );

    let maquinaSeleccionada = document.querySelector(
        ".maquina_si_seleccionada"
    );
    let maquinaId = maquinaSeleccionada.dataset.id;

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
    maquinasDivs.forEach((div) => {
        let detalles = JSON.parse(div.dataset.detalles);
        if (detalles.id == maquinaId) maquinaDiv = div;
    });

    // comprobamos si existe la planilla
    let existe = false;
    let te_planillas = maquinaDiv.querySelectorAll(".planilla");
    te_planillas.forEach((planilla) => {
        if (
            planilla.children[1].innerText.trim() ==
            datosOrdenPlanillaSeleccionado.codigo
        ) {
            existe = true;
        }
    });

    // existe la planilla?
    // si existe:
    if (existe) {
        // 1) Construir lista de coincidencias con mismo código en máquina seleccionada
        const codigoCoincide = datosOrdenPlanillaSeleccionado.codigo;
        const coincidencias = findOrdenesCoincidentes(
            maquinaId,
            codigoCoincide
        );

        // 2) Pintar modal y preparar handlers
        renderModalElegirOrden({
            maquinaId,
            codigo: codigoCoincide,
            coincidencias,
        });

        // Handlers del modal
        const meo = document.getElementById("modal_elegir_orden");
        const btnConfirmar = document.getElementById("meo_confirmar");
        const btnCancelar = document.getElementById("meo_cancelar");

        // para evitar múltiples binds si se abre varias veces
        btnConfirmar.replaceWith(btnConfirmar.cloneNode(true));
        btnCancelar.replaceWith(btnCancelar.cloneNode(true));

        const _btnConfirmar = document.getElementById("meo_confirmar");
        const _btnCancelar = document.getElementById("meo_cancelar");

        _btnCancelar.addEventListener("click", () => {
            meo.classList.add("hidden");
        });

        _btnConfirmar.addEventListener("click", () => {
            // radio seleccionado
            const sel = document.querySelector(
                'input[name="meo_opcion"]:checked'
            );
            if (!sel) return; // no se seleccionó nada

            const val = sel.value;
            let destinoOrdenId = null;

            if (val === "__crear_nueva__") {
                // Crear nueva al final
                const te_planillas2 = maquinaDiv.querySelectorAll(".planilla");
                const posicionNueva = te_planillas2.length + 1;

                const nuevaOrdenPlanilla = {
                    id: Number(ultimoOrdenPlanillaId) + 1,
                    maquina_id: Number(maquinaId),
                    planilla_id: Number(
                        datosOrdenPlanillaSeleccionado.planilla_id
                    ),
                    posicion: posicionNueva,
                };
                ultimoOrdenPlanillaId = nuevaOrdenPlanilla.id;
                destinoOrdenId = nuevaOrdenPlanilla.id;
                ordenPlanillas.push(nuevaOrdenPlanilla);
            } else {
                destinoOrdenId = Number(val);
            }

            // Actualizar elementos seleccionados -> a maquinaId y orden_planilla_id destinoOrdenId
            elementosSeleccionados.forEach((card) => {
                const elId = Number(card.dataset.id);
                const idx = elementos.findIndex((e) => Number(e.id) === elId);
                if (idx !== -1) {
                    elementos[idx].maquina_id = Number(maquinaId);
                    elementos[idx].orden_planilla_id = Number(destinoOrdenId);
                }
            });

            cerrarModales(); // cierra todos, incluido el de elegir orden
            renderPlanillas();
            sePuedeGuardar();
        });
    } else {
        // no existe: crear nueva al final
        let posicionNueva = te_planillas.length + 1;

        let nuevaOrdenPlanilla = {
            id: Number(ultimoOrdenPlanillaId) + 1,
            maquina_id: Number(maquinaId),
            planilla_id: Number(datosOrdenPlanillaSeleccionado.planilla_id),
            posicion: posicionNueva,
        };

        ultimoOrdenPlanillaId = nuevaOrdenPlanilla.id; // sincronizar el global
        let nuevoOrdenId = nuevaOrdenPlanilla.id;

        // la agregamos a la variable global
        ordenPlanillas.push(nuevaOrdenPlanilla);

        // asignar los elementos (usando id del dataset del card para máxima fiabilidad)
        elementosSeleccionados.forEach((elementoSeleccionado) => {
            const elId = Number(elementoSeleccionado.dataset.id);
            const idx = elementos.findIndex((e) => Number(e.id) === elId);
            if (idx !== -1) {
                elementos[idx].maquina_id = Number(maquinaId);
                elementos[idx].orden_planilla_id = Number(nuevoOrdenId);
            }
        });

        cerrarModales();
        renderPlanillas();
        sePuedeGuardar();
    }
}

function cerrarModales() {
    modales.forEach((modal) => {
        modal.classList.add("hidden");
    });
}

function getMaquinaById(id) {
    const mid = Number(id);
    return maquinas.find((m) => Number(m.id) === mid) || null;
}

function getPlanillaById(id) {
    const pid = Number(id);
    return planillas.find((p) => Number(p.id) === pid) || null;
}

function getOrdenesPorMaquina(maquinaId) {
    const mid = Number(maquinaId);
    return ordenPlanillas.filter((o) => Number(o.maquina_id) === mid);
}

function hasCambiosElementos(actual, original) {
    if (!Array.isArray(actual) || !Array.isArray(original)) return true;
    if (actual.length !== original.length) return true;

    // Mapeo por id para comparar rápido
    const mapOrig = new Map(original.map((e) => [Number(e.id), e]));
    for (const e of actual) {
        const o = mapOrig.get(Number(e.id));
        if (!o) return true;

        // Compara SOLO lo que te importa para “guardar”
        if (Number(e.maquina_id) !== Number(o.maquina_id)) return true;
        if (Number(e.orden_planilla_id) !== Number(o.orden_planilla_id))
            return true;
    }
    return false;
}

function sePuedeGuardar() {
    if (hasCambiosElementos(elementos, ELEMENTOS_ORIGINAL)) {
        modalGuardar.classList.remove("-bottom-14");
        modalGuardar.classList.add("bottom-14");
    } else {
        modalGuardar.classList.add("-bottom-14");
        modalGuardar.classList.remove("bottom-14");
    }
}

/**
 * Devuelve las ordenes de la maquina cuyo planilla.codigo === code
 * con datos enriquecidos para mostrar.
 */
function findOrdenesCoincidentes(maquinaId, code) {
    const ops = getOrdenesPorMaquina(maquinaId);
    const res = [];
    ops.forEach((op) => {
        const p = getPlanillaById(op.planilla_id);
        if (p && String(p.codigo).trim() === String(code).trim()) {
            res.push({
                orden_id: Number(op.id),
                posicion: Number(op.posicion),
                planilla_id: Number(op.planilla_id),
                codigo: p.codigo,
            });
        }
    });
    // ordenar por posicion asc
    res.sort((a, b) => a.posicion - b.posicion);
    return res;
}

// Pinta las coincidencias en el modal_elegir_orden
function renderModalElegirOrden({ maquinaId, codigo, coincidencias }) {
    const meo = document.getElementById("modal_elegir_orden");
    const cont = document.getElementById("meo_lista");
    const nom = document.getElementById("meo_maquina_nombre");
    const cod = document.getElementById("meo_codigo");

    const maq = getMaquinaById(maquinaId);
    nom.textContent = maq
        ? maq.nombre || maq.codigo || `#${maquinaId}`
        : `#${maquinaId}`;
    cod.textContent = codigo;
    cont.innerHTML = "";

    if (!Array.isArray(coincidencias) || !coincidencias.length) {
        cont.innerHTML = `<div class="text-sm text-gray-600 p-2">No se encontraron coincidencias (esto no debería mostrarse si vienes por rama "existe").</div>`;
    } else {
        coincidencias.forEach((c) => {
            const div = document.createElement("label");
            div.className =
                "meo_item flex items-center gap-3 p-2 rounded-lg border cursor-pointer transition-all duration-100";
            div.innerHTML = `
        <input type="radio" name="meo_opcion" value="${c.orden_id}">
        <div class="flex flex-col">
          <div class="text-sm"><span class="font-semibold">Orden #${c.orden_id}</span> — <span class="font-mono">${c.codigo}</span></div>
          <div class="text-xs text-gray-600">Posición actual: ${c.posicion}</div>
        </div>
      `;
            cont.appendChild(div);
        });
    }

    meo.classList.remove("hidden");
}
