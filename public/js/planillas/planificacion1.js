//variables
let planillas;
let ordenPlanillas;
let elementos;
let maquinas;
let maquinasDivs = [];
let btnGuardar;
let datosOrdenPlanillaSeleccionado;
let ultimoOrdenPlanillaId;
let naves = [];

// btn
let btn_transferir;
let btn_guardar;
let btn_cancelar;

//modales
let modalMostrarElementos;
let modalTransferirAMaquina;
let modalGuardar;
let modalElegirOrden;
let modalResaltarObra;
let modales = [];

// variables datos originales, servira para referenciar los cambios realizados
let ELEMENTOS_ORIGINAL;
let ORDEN_PLANILLAS_ORIGINAL;

// DRAG & DROP CROSS-COLUMN
let DND = {
    draggingEl: null,
    sourceContainer: null,
    placeholder: null,
};

document.addEventListener("DOMContentLoaded", () => {
    planillas = Array.from(
        document.querySelectorAll("#todasPlanillas [data-planilla]")
    ).map((div) => JSON.parse(div.dataset.planilla));

    elementos = Array.from(
        document.querySelectorAll("#todosElementos [data-elementos]")
    ).map((div) => JSON.parse(div.dataset.elementos));

    ordenPlanillas = Array.from(
        document.querySelectorAll("#ordenPlanillas [data-orden]")
    ).map((div) => JSON.parse(div.dataset.orden));

    maquinas = Array.from(
        document.querySelectorAll("#maquinas [data-detalles]")
    ).map((div) => JSON.parse(div.dataset.detalles));

    naves = Array.from(document.querySelectorAll(".filtro_nave"));

    naves.forEach((btn) => {
        btn.addEventListener("click", () => filtrarPorNave(btn.dataset.nave));
    });

    maquinasDivs = Array.from(document.getElementsByClassName("maquina"));

    ELEMENTOS_ORIGINAL = JSON.parse(JSON.stringify(elementos));
    ORDEN_PLANILLAS_ORIGINAL = JSON.parse(JSON.stringify(ordenPlanillas));

    // obtener el id de ordenPlanilla mas alto, asi poder trabaja con id de manera local sin llamar a la bd
    ultimoOrdenPlanillaId =
        Math.max(...ordenPlanillas.map((o) => Number(o.id) || 0)) || 0;

    //btn
    btn_transferir = document.getElementById("transferir_elementos");
    btn_transferir.addEventListener("click", transferirElementos);
    btn_guardar = document.getElementById("btn_guardar");
    btn_cancelar = document.getElementById("btn_cancelar_guardar");

    btn_guardar.addEventListener("click", guardarCambios);
    btn_cancelar.addEventListener("click", () => {
        elementos = JSON.parse(JSON.stringify(ELEMENTOS_ORIGINAL));
        ordenPlanillas = JSON.parse(JSON.stringify(ORDEN_PLANILLAS_ORIGINAL));
        renderPlanillas();
        sePuedeGuardar();
    });

    // modales
    modalMostrarElementos = document.getElementById("modal_elementos");
    modalTransferirAMaquina = document.getElementById(
        "modal_transferir_a_maquina"
    );
    modalGuardar = document.getElementById("modal_guardar");
    modalElegirOrden = document.getElementById("modal_elegir_orden");
    modalResaltarObra = document.getElementById("modal_resaltar_obra");
    modales = [
        modalMostrarElementos,
        modalTransferirAMaquina,
        modalElegirOrden,
        modalResaltarObra,
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
    filtrarPorNave();

    filtrarPorNave("all");
});

// mostrar en funcion de ordenPlanillas local las planillas en orden asignadas a cada maquina
function renderPlanillas() {
    // limpiar planillas existentes, si hay
    maquinasDivs.forEach((maq) => {
        const contenedor = maq.querySelector(".planillas");
        if (contenedor) contenedor.innerHTML = "";
    });

    // ⚠️ ORDENAR por maquina_id y posicion antes de pintar
    const ordenadas = [...ordenPlanillas].sort((a, b) => {
        if (Number(a.maquina_id) !== Number(b.maquina_id)) {
            return Number(a.maquina_id) - Number(b.maquina_id);
        }
        return Number(a.posicion) - Number(b.posicion);
    });

    ordenadas.forEach((planilla) => {
        // div que se renderizara
        let div = document.createElement("div");

        maquinasDivs.forEach((maq) => {
            let div_maquina_id = JSON.parse(maq.dataset.detalles).id;
            if (div_maquina_id == planilla.maquina_id) {
                div.className =
                    "planilla group p-3 flex justify-around items-center border-2 border-emerald-400 hover:border-emerald-600 hover:-translate-y-1 transition-transform duration-75 ease-in-out rounded-xl cursor-grab bg-gradient-to-tr from-neutral-100 to-neutral-200 hover:from-emerald-300 hover:to-emerald-400 active:cursor-grabbing select-none text-center relative";
                div.dataset.ordenId = planilla.id;
                div.dataset.planillaId = planilla.planilla_id;
                div.setAttribute("draggable", "true");

                let divPosicion = document.createElement("div");
                divPosicion.className =
                    "posicion text-emerald-600 group-hover:text-black text-xs font-bold absolute top-1 left-1 pos-label";
                divPosicion.innerText = planilla.posicion;

                let divCodigoPlanilla = document.createElement("div");
                let codigo_planilla;
                for (const planilla_i of planillas) {
                    if (planilla_i.id == planilla.planilla_id) {
                        codigo_planilla = planilla_i.codigo;
                        break;
                    }
                }
                divCodigoPlanilla.innerText = codigo_planilla;
                divCodigoPlanilla.className =
                    "codigo text-emerald-800 font-semibold";
                div.dataset.codigo = codigo_planilla;

                div.appendChild(divPosicion);
                div.appendChild(divCodigoPlanilla);
            }
        });

        // pintar en su columna
        maquinasDivs.forEach((maqDiv) => {
            let maqId = maqDiv.getAttribute("data-maquina-id");
            if (maqId == planilla.maquina_id) {
                maqDiv.querySelector(".planillas").appendChild(div);
            }
        });
    });

    agregarClickAPlanillas();
    initDragAndDrop();
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
        // si el nodo ya no está en el DOM, sáltalo
        if (!opd || !opd.isConnected) return;

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

        // header de la máquina (ajusta el selector si tu header tiene otra clase)
        const maquinaHeader = opd
            .closest(".maquina")
            ?.querySelector(":scope > *:first-child");
        if (maquinaHeader) {
            maquinaHeader.classList.add("from-emerald-600", "to-emerald-700");
            maquinaHeader.classList.remove("from-indigo-400", "to-indigo-500");
        }
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

                // resaltar maquina
                const maquinaHeader = opd
                    .closest(".maquina")
                    ?.querySelector(":scope > *:first-child");
                if (maquinaHeader) {
                    maquinaHeader.classList.remove(
                        "from-emerald-600",
                        "to-emerald-700"
                    );
                    maquinaHeader.classList.add(
                        "from-indigo-400",
                        "to-indigo-500"
                    );
                }

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

            // id de la orden de origen (la que abriste en el modal)
            const origenOrdenId = Number(datosOrdenPlanillaSeleccionado.id);

            // quedan elementos en la orden de origen?
            if (countElementosByOrdenId(origenOrdenId) === 0) {
                removeOrdenPlanilla(origenOrdenId);
            }

            // refrescos
            cerrarModales();
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

        // id de la orden de origen (la que abriste en el modal)
        const origenOrdenId = Number(datosOrdenPlanillaSeleccionado.id);

        // quedan elementos en la orden de origen?
        if (countElementosByOrdenId(origenOrdenId) === 0) {
            removeOrdenPlanilla(origenOrdenId);
        }

        cerrarModales();
        renderPlanillas();
        sePuedeGuardar();
    }
}

function cerrarModales(noCerrar = null) {
    modales.forEach((modal) => {
        if (modal != noCerrar) {
            modal.classList.add("hidden");
        }
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
    cerrarModales(meo);
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
                "flex items-center gap-3 p-2 rounded-lg border cursor-pointer transition-all duration-100";
            div.innerHTML = `
        <input type="radio" name="meo_opcion" value="${c.orden_id}">
        <div class="flex flex-col">
          <div class="text-sm">Planilla en posición: <span class="font-semibold">${c.posicion}</span></div>
        </div>
      `;
            cont.appendChild(div);
        });
    }

    meo.classList.remove("hidden");
}

/*
INICIO INICIO INICIO INICIO INICIO INICIO INICIO
FUNCIONES PARA DRAG DROP
INICIO INICIO INICIO INICIO INICIO INICIO INICIO
*/

function getContainerMachineId(containerEl) {
    // containerEl es .planillas; su padre .maquina lleva data-detalles
    const maqDiv = containerEl.closest(".maquina");
    if (!maqDiv) return null;
    try {
        return Number(JSON.parse(maqDiv.dataset.detalles).id);
    } catch {
        return null;
    }
}

function makePlaceholder(heightPx) {
    const ph = document.createElement("div");
    ph.className =
        "planilla placeholder p-3 flex justify-around items-center rounded-xl box-border shrink-0";
    ph.style.height = `${heightPx || 56}px`;
    ph.style.minHeight = `${heightPx || 56}px`; // cinturón y tirantes
    return ph;
}

function getChildCards(container) {
    const all = Array.from(
        container.querySelectorAll(".planilla:not(.placeholder)")
    );
    // Excluir SOLO si está realmente en arrastre (tiene la clase .dragging)
    if (
        DND.draggingEl &&
        DND.draggingEl.classList.contains("dragging") &&
        container.contains(DND.draggingEl)
    ) {
        return all.filter((n) => n !== DND.draggingEl);
    }
    return all;
}

function calcDropIndex(container, clientY) {
    const cards = getChildCards(container);
    for (let i = 0; i < cards.length; i++) {
        const rect = cards[i].getBoundingClientRect();
        const midY = rect.top + rect.height / 2;
        if (clientY < midY) return i;
    }
    return cards.length;
}

function reindexDOMColumn(container) {
    const cards = getChildCards(container);
    cards.forEach((card, i) => {
        const pos = card.querySelector(".posicion");
        if (pos) pos.textContent = String(i + 1);
    });
}

function syncOrdenPlanillasFromDOM(container) {
    // Sincroniza el array ordenPlanillas con el orden actual del DOM en esta columna
    const maquinaId = getContainerMachineId(container);
    const cards = getChildCards(container);
    cards.forEach((card, i) => {
        const oid = Number(card.dataset.ordenId);
        const op = ordenPlanillas.find((o) => Number(o.id) === oid);
        if (op) {
            op.maquina_id = Number(maquinaId);
            op.posicion = i + 1;
        }
    });
}

function moveElementsWithOrdenToMachine(ordenId, nuevaMaquinaId) {
    // Todos los elementos con ese orden_planilla_id cambian su maquina_id
    elementos.forEach((e) => {
        if (Number(e.orden_planilla_id) === Number(ordenId)) {
            e.maquina_id = Number(nuevaMaquinaId);
            // e.orden_planilla_id NO cambia porque seguimos moviendo la misma orden
        }
    });
}

function initDragAndDrop() {
    // limpiar listeners previos (recreamos de forma segura)
    document.querySelectorAll(".planilla").forEach((card) => {
        card.removeEventListener("_dnd_bound", () => {});
    });

    const cards = document.querySelectorAll(".planilla");
    const containers = document.querySelectorAll(".maquina .planillas");

    containers.forEach((c) => {
        // asegurar que soporta drop
        c.addEventListener(
            "dragover",
            (e) => {
                e.preventDefault();
                if (!DND.draggingEl) return;
                c.classList.add("drop-target");

                // altura visual exacta del ítem arrastrado
                const h = Math.max(
                    DND.draggingEl.getBoundingClientRect().height || 56,
                    56
                );

                // placeholder
                if (!DND.placeholder) {
                    const h = Math.max(DND.draggingEl?.offsetHeight || 56, 56);
                    DND.placeholder = makePlaceholder(h);
                } else {
                    // si cambió la altura de la card (responsive), sincroniza
                    const h = Math.max(DND.draggingEl?.offsetHeight || 56, 56);
                    DND.placeholder.style.height = `${h}px`;
                }

                const idx = calcDropIndex(c, e.clientY);
                const currentChildren = getChildCards(c);
                const refNode = currentChildren[idx] || null;
                if (DND.placeholder.parentElement !== c) {
                    c.insertBefore(DND.placeholder, refNode);
                } else {
                    c.insertBefore(DND.placeholder, refNode);
                }

                reindexPreview(c, DND.placeholder);
            },
            { passive: false }
        );

        c.addEventListener("dragleave", (e) => {
            // si te vas fuera del contenedor, quitamos highlight
            const to = e.relatedTarget;
            if (!c.contains(to)) {
                c.classList.remove("drop-target");
            }
        });
    });

    cards.forEach((card) => {
        card.addEventListener("dragstart", (e) => {
            DND.draggingEl = e.currentTarget;
            DND.sourceContainer = DND.draggingEl.closest(".planillas");
            DND.draggingEl.classList.add("dragging");
            e.dataTransfer.effectAllowed = "move";
            try {
                e.dataTransfer.setData(
                    "text/plain",
                    DND.draggingEl.dataset.ordenId || ""
                );
            } catch {}
        });

        card.addEventListener("dragend", () => {
            const finalContainer =
                DND.placeholder?.parentElement || DND.sourceContainer;

            const targetMachineId = getContainerMachineId(finalContainer);
            const ordenId = Number(DND.draggingEl.dataset.ordenId);

            // Inserta el card real donde está el placeholder
            if (DND.placeholder && DND.placeholder.parentElement) {
                finalContainer.insertBefore(DND.draggingEl, DND.placeholder);
            }

            // Limpieza visual
            document
                .querySelectorAll(".planillas.drop-target")
                .forEach((el) => el.classList.remove("drop-target"));
            DND.draggingEl.classList.remove("dragging");
            if (DND.placeholder && DND.placeholder.parentElement) {
                DND.placeholder.parentElement.removeChild(DND.placeholder);
            }
            DND.placeholder = null;

            // Guarda refs y ANULA dragging antes de reindexar/sincronizar
            const src = DND.sourceContainer;
            const dst = finalContainer;
            const draggingRef = DND.draggingEl;
            DND.draggingEl = null; // <- clave para que getChildCards ya NO la excluya
            DND.sourceContainer = null;

            // Reindex DOM
            if (src && dst !== src) reindexDOMColumn(src);
            reindexDOMColumn(dst);

            // Actualiza elementos -> nueva máquina (si ha cambiado de columna)
            if (targetMachineId != null) {
                moveElementsWithOrdenToMachine(ordenId, targetMachineId);
            }

            // Sincroniza arrays con el DOM definitivo
            if (src) syncOrdenPlanillasFromDOM(src);
            if (dst) syncOrdenPlanillasFromDOM(dst);

            normalizeOrdenPlanillas();
            _clearHighlight();
            // Repinta y muestra guardar si procede
            renderPlanillas();
            sePuedeGuardar();
        });
    });
}

/**
 * Mientras arrastras, muestra reindex "what-you-see-is-what-you-get"
 */
function reindexPreview(container, placeholder) {
    const children = Array.from(container.children);
    let pos = 1;
    for (const node of children) {
        if (!node.classList.contains("planilla")) continue;
        const isPh = node.classList.contains("placeholder");
        // Ignorar la tarjeta que se está arrastrando si aún aparece en el DOM aquí
        if (DND.draggingEl && node === DND.draggingEl) continue;

        if (!isPh) {
            const posEl = node.querySelector(".posicion");
            if (posEl) posEl.textContent = String(pos);
        }
        pos++;
    }
}

function normalizeOrdenPlanillas() {
    ordenPlanillas.sort((a, b) => {
        if (Number(a.maquina_id) !== Number(b.maquina_id)) {
            return Number(a.maquina_id) - Number(b.maquina_id);
        }
        return Number(a.posicion) - Number(b.posicion);
    });
}

/*
FIN FIN FIN FIN FIN FIN FIN FIN FIN FIN FIN FIN
FUNCIONES PARA DRAG DROP
FIN FIN FIN FIN FIN FIN FIN FIN FIN FIN FIN FIN
*/

// comprobar que no quedan elementos en la planilla donde se ha hecho un movimiento de elementos
function countElementosByOrdenId(ordenId) {
    const oid = Number(ordenId);
    return elementos.reduce(
        (acc, e) => acc + (Number(e.orden_planilla_id) === oid ? 1 : 0),
        0
    );
}

function reindexMachine(maquinaId) {
    const mid = Number(maquinaId);
    const ops = ordenPlanillas
        .filter((o) => Number(o.maquina_id) === mid)
        .sort((a, b) => Number(a.posicion) - Number(b.posicion));
    ops.forEach((o, i) => {
        o.posicion = i + 1;
    });
}

// eliminar elOrdenPlanilla que se ha quedado sin elementos
function removeOrdenPlanilla(ordenId) {
    const oid = Number(ordenId);
    const idx = ordenPlanillas.findIndex((o) => Number(o.id) === oid);
    if (idx === -1) return;
    const maquinaId = ordenPlanillas[idx].maquina_id;
    ordenPlanillas.splice(idx, 1);
    reindexMachine(maquinaId);
}

function buildDiff() {
    // 1) ELEMENTOS: actualizaciones (solo si cambió maquina_id u orden_planilla_id)
    const mapElOrig = new Map(ELEMENTOS_ORIGINAL.map((e) => [Number(e.id), e]));
    const elementos_updates = [];
    for (const e of elementos) {
        const o = mapElOrig.get(Number(e.id));
        if (!o) continue; // si no estaba en el original, no lo tocamos aquí (no creas elementos nuevos con este flujo)
        if (
            Number(e.maquina_id) !== Number(o.maquina_id) ||
            Number(e.orden_planilla_id) !== Number(o.orden_planilla_id)
        ) {
            elementos_updates.push({
                id: Number(e.id),
                maquina_id: Number(e.maquina_id),
                orden_planilla_id: Number(e.orden_planilla_id),
            });
        }
    }

    // 2) ORDEN_PLANILLAS: crear/actualizar/eliminar
    const mapOPOrig = new Map(
        ORDEN_PLANILLAS_ORIGINAL.map((o) => [Number(o.id), o])
    );
    const mapOPNow = new Map(ordenPlanillas.map((o) => [Number(o.id), o]));

    // a) eliminados: estaban antes y ya no están
    const orden_planillas_delete = [];
    for (const [id, op] of mapOPOrig.entries()) {
        if (!mapOPNow.has(id)) {
            orden_planillas_delete.push(id);
        }
    }

    // b) creados: están ahora y no estaban antes
    const orden_planillas_create = [];
    for (const [id, op] of mapOPNow.entries()) {
        if (!mapOPOrig.has(id)) {
            orden_planillas_create.push({
                id: Number(op.id), // si quieres que el server respete este id
                maquina_id: Number(op.maquina_id),
                planilla_id: Number(op.planilla_id),
                posicion: Number(op.posicion),
            });
        }
    }

    // c) actualizados: están en ambos pero cambió maquina_id o posicion
    const orden_planillas_update = [];
    for (const [id, now] of mapOPNow.entries()) {
        const old = mapOPOrig.get(id);
        if (!old) continue;
        if (
            Number(now.maquina_id) !== Number(old.maquina_id) ||
            Number(now.posicion) !== Number(old.posicion)
        ) {
            orden_planillas_update.push({
                id: Number(now.id),
                maquina_id: Number(now.maquina_id),
                posicion: Number(now.posicion),
            });
        }
    }

    return {
        elementos_updates,
        orden_planillas: {
            create: orden_planillas_create,
            update: orden_planillas_update,
            delete: orden_planillas_delete,
        },
    };
}

async function guardarCambios() {
    const url = document.getElementById("modal_guardar").dataset.saveUrl;
    const payload = buildDiff();

    // si no hay nada que guardar, salimos
    if (
        (!payload.elementos_updates ||
            payload.elementos_updates.length === 0) &&
        (!payload.orden_planillas.create ||
            payload.orden_planillas.create.length === 0) &&
        (!payload.orden_planillas.update ||
            payload.orden_planillas.update.length === 0) &&
        (!payload.orden_planillas.delete ||
            payload.orden_planillas.delete.length === 0)
    ) {
        return;
    }

    // CSRF
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    try {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token ?? "",
                Accept: "application/json",
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) {
            const errTxt = await res.text();
            console.error("Error guardando:", errTxt);
            alert("Error al guardar. Revisa la consola.");
            return;
        }

        const data = await res.json();

        // Si el backend decide reasignar IDs (ej. no respetas el id del cliente), podrías recibir un id_map con {tempId:nuevoId}
        if (data?.orden_planillas_id_map) {
            const idMap = data.orden_planillas_id_map;
            // Actualiza referencias en memoria
            // 1) ordenPlanillas
            ordenPlanillas.forEach((op) => {
                const n = idMap[String(op.id)];
                if (n) op.id = Number(n);
            });
            // 2) elementos (orden_planilla_id)
            elementos.forEach((el) => {
                const n = idMap[String(el.orden_planilla_id)];
                if (n) el.orden_planilla_id = Number(n);
            });
        }

        // Snapshot nuevos originales
        ELEMENTOS_ORIGINAL = JSON.parse(JSON.stringify(elementos));
        ORDEN_PLANILLAS_ORIGINAL = JSON.parse(JSON.stringify(ordenPlanillas));

        // UI
        sePuedeGuardar();
        renderPlanillas();

        // feedback sencillo
        // (si quieres toasts chulos, tírale a un componente o a Alpine)
        alert("Cambios guardados ✅");
    } catch (err) {
        console.error(err);
        alert("Error de red guardando cambios.");
    }
}

function filtrarPorNave(valor) {
    // Normaliza
    const target = (valor ?? "all").toString();

    // 1) Botones activos (estilos)
    naves.forEach((btn) => {
        const activo = btn.dataset.nave === target;
        btn.classList.toggle("bg-gradient-to-r", activo);
        btn.classList.toggle("text-white", activo);
        btn.classList.toggle("text-emerald-700", !activo);
    });

    // 2) Mostrar/Ocultar máquinas por nave
    maquinasDivs.forEach((maqDiv) => {
        const detalles = JSON.parse(maqDiv.dataset.detalles || "{}");
        const naveId = String(detalles.nave_id ?? "");
        const show = target === "all" || naveId === target;
        maqDiv.style.display = show ? "" : "none";
    });
}

// mostrar modal de resaltar planillas por obra
function mostrarModalResaltarObra() {
    modalResaltarObra.classList.remove("hidden")
}
