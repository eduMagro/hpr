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
let inputFiltrarObra;
let obras = [];
let obrasInput;
let obrasModal;
let clickObras; // .obra
let obraSeleccionada;
let OBRA_HL = { active: false, id: null, codes: new Set() };
let MODAL_MAQ_CHIPS = new Map();

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
let modalDetalles;
let modalMapa;
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

    // Mapear chips del modal mini-mapa: maquina_id -> nodo chip
    document
        .querySelectorAll("#modal_maquinas_con_elementos .chip-maq")
        .forEach((chip) => {
            const mid = Number(chip.dataset.maquinaId);
            if (!Number.isNaN(mid)) MODAL_MAQ_CHIPS.set(mid, chip);
        });

    obras = Array.from(document.querySelectorAll("#obras [data-obras]")).map(
        (div) => JSON.parse(div.dataset.obras)
    );
    obrasInput = document.getElementById("input_filtrar_obra");
    obrasModal = document.getElementById("obras_modal");
    clickObras = Array.from(document.getElementsByClassName("obra"));

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
    modalDetalles = document.getElementById("modal_detalles"); // este no hay que agregarlo al array
    modalMapa = document.getElementById("modal_maquinas_con_elementos"); // este no hay que agregarlo al array
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

    // mostrar obras que coincidan con la busqueda del input
    inputFiltrarObra = document.getElementById("input_filtrar_obra");
    inputFiltrarObra.addEventListener("input", () => {
        let obras;
    });

    renderPlanillas();
    resaltarCompis();
    filtrarPorNave();

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            cerrarModales();
        }
    });

    filtrarPorNave("all");
    renderObras();
    obrasInput.addEventListener("input", renderObras);

    // btn resaltar por obra
    resaltarObra(1);

    document.addEventListener("mousemove", (e) => {
        const ancho = window.innerWidth;
        const tercio = ancho / 3;
        const modalDetalles = document.getElementById("modal_detalles");
        const modalMapa = document.getElementById(
            "modal_maquinas_con_elementos"
        );

        if (!modalDetalles || !modalMapa) return;

        // Primer tercio (zona izquierda)
        if (e.clientX < tercio) {
            // Mover ambos a la derecha
            modalDetalles.classList.remove("left-4");
            modalDetalles.classList.add("right-4");

            modalMapa.classList.remove("left-3");
            modalMapa.classList.add("right-3");
        }
        // Último tercio (zona derecha)
        else if (e.clientX > tercio * 2) {
            // Mover ambos a la izquierda
            modalDetalles.classList.remove("right-4");
            modalDetalles.classList.add("left-4");

            modalMapa.classList.remove("right-3");
            modalMapa.classList.add("left-3");
        }
    });
});

// renderizar obras en funcion del valor del input
function renderObras() {
    let valorContains = obrasInput.value.trim().toLowerCase();
    clickObras.forEach((obra) => {
        let p = obra.querySelector("p");
        if (p) {
            let nombre = obra.querySelector("p").innerText.toLowerCase();
            if (nombre.includes(valorContains) || valorContains === "") {
                obra.classList.remove("hidden");
            } else {
                obra.classList.add("hidden");
            }
        }
    });
    resaltarObra();
}

// si se le pasa un valor quita el resalte de las obras
function resaltarObra(quitar = null) {
    let btn1 = document.getElementById("btn_mostrar_modal_obras");
    let btn2 = document.getElementById("btn_quitar_resaltado");
    if (!quitar) {
        btn1.classList.add("hidden");
        btn2.classList.remove("hidden");
        clickObras.forEach((clickObra) => {
            clickObra.addEventListener("click", () => {
                cerrarModales();
                obraSeleccionada = clickObra.dataset.id;
                let planillasEnLaMismaObra = [];

                // recoger codigos de planillas que se encuentran en la obra seleccionada
                planillas.forEach((planilla) => {
                    if (planilla.obra_id == obraSeleccionada) {
                        planillasEnLaMismaObra.push(planilla.codigo);
                    }
                });

                let ordenPlanillasRenderizado = Array.from(
                    document.getElementsByClassName("planilla")
                );
                ordenPlanillasRenderizado.forEach((planilla) => {
                    if (
                        planillasEnLaMismaObra.includes(
                            planilla.children[1]?.innerText.trim()
                        )
                    ) {
                        // Quitar clases de hover que pintan en verde + desactivar group-hover
                        planilla.classList.remove(
                            "hover:from-blue-300",
                            "hover:to-blue-400",
                            "hover:border-blue-600",
                            /* quita group para que no actúe .group-hover:* en los hijos */
                            "group"
                            // si NO quieres el leve levantado al hover, descomenta:
                            // , "hover:-translate-y-1"
                        );

                        const posEl = planilla.querySelector(".posicion");
                        const codEl = planilla.querySelector(".codigo");
                        posEl?.classList.remove(
                            "text-blue-600",
                            "group-hover:text-black"
                        );
                        codEl?.classList.remove("text-blue-800");

                        // Aplica el gradiente y el borde fucsia
                        planilla.classList.remove(
                            "from-neutral-100",
                            "to-neutral-200"
                        );
                        planilla.classList.add(
                            "bg-gradient-to-tr",
                            "from-fuchsia-200",
                            "to-fuchsia-300",
                            "border-2",
                            "border-fuchsia-400",
                            "obra_resaltada"
                        );

                        // Colores de texto en fucsia (no depender de herencia)
                        posEl?.classList.add("text-fuchsia-700");
                        codEl?.classList.add("text-fuchsia-700");
                    }
                });
            });
        });
    } else {
        btn2.classList.add("hidden");
        btn1.classList.remove("hidden");

        const cards = Array.from(document.getElementsByClassName("planilla"));
        cards.forEach((card) => {
            // restaurar clases base y hover verdes
            card.classList.remove(
                "obra_resaltada",
                "from-fuchsia-200",
                "to-fuchsia-300",
                "border-fuchsia-400"
            );
            card.classList.add(
                "bg-gradient-to-tr",
                "from-neutral-100",
                "to-neutral-200",
                "border-2",
                "border-blue-400",
                "hover:from-blue-300",
                "hover:to-blue-400",
                "hover:border-blue-600",
                "group"
                // si quitaste el lift en highlight, reañádelo aquí:
                // , "hover:-translate-y-1"
            );

            const posEl = card.querySelector(".posicion");
            const codEl = card.querySelector(".codigo");
            posEl?.classList.remove("text-fuchsia-700");
            posEl?.classList.add("text-blue-600", "group-hover:text-black");
            codEl?.classList.remove("text-fuchsia-700");
            codEl?.classList.add("text-blue-800");
        });
    }
}

// mostrar en funcion de ordenPlanillas local las planillas en orden asignadas a cada maquina
function renderPlanillas() {
    // limpiar planillas existentes, si hay
    maquinasDivs.forEach((maq) => {
        const contenedor = maq.querySelector(".planillas");
        if (contenedor) contenedor.innerHTML = "";
    });

    // ORDENAR por maquina_id y posicion antes de pintar
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
                    "planilla group p-3 flex justify-around items-center border-2 border-blue-400 hover:border-blue-600 hover:-translate-y-1 transition-transform duration-75 ease-in-out rounded-xl cursor-grab bg-gradient-to-tr from-neutral-100 to-neutral-200 hover:from-blue-300 hover:to-blue-400 active:cursor-grabbing select-none text-center relative";
                div.dataset.ordenId = planilla.id;
                div.dataset.planillaId = planilla.planilla_id;
                div.setAttribute("draggable", "true");

                let divPosicion = document.createElement("div");
                divPosicion.className =
                    "posicion text-blue-600 group-hover:text-black text-xs font-bold absolute top-1 left-1 pos-label";
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
                    "codigo text-blue-800 font-semibold";
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
    if (OBRA_HL.active) applyObraHighlight();
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

    // toolbar Seleccionar todos
    ensureSelectAllToolbar();

    // mostrar modal
    modalDetalles.classList.add("hidden");
    modalMapa.classList.add("hidden");
    modalMostrarElementos.classList.remove("hidden");
}

function anadirElemento(padre, elemento, n) {
    const idCanvas = `cv-el-${elemento.id}`;

    const html = `
    <div class="p-2 w-full no_seleccionado text-center bg-gradient-to-tr from-orange-200 to-orange-300 hover:from-orange-300 hover:to-orange-400 hover:-translate-y-1 cursor-pointer rounded-xl flex flex-col items-center transition-all duration-75"
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
                    "hover:from-orange-300",
                    "hover:to-orange-400"
                );
                target.classList.add(
                    "seleccionado",
                    "to-orange-400",
                    "from-blue-500"
                );
            } else {
                target.classList.add(
                    "no_seleccionado",
                    "hover:from-orange-300",
                    "hover:to-orange-400"
                );
                target.classList.remove(
                    "seleccionado",
                    "to-orange-400",
                    "from-blue-500"
                );
            }
        });
    });
}

// resaltamos el color de la misma planilla repartida por otra maquina u otro turno
let _hlCode = null;
let _hlNodes = [];

function _clearHighlight() {
    if (_hlNodes.length) {
        _hlNodes.forEach((opd) => {
            if (!opd || !opd.isConnected) return;
            opd.classList.remove("__hl-compi");
            const maquinaHeader = opd
                .closest(".maquina")
                ?.querySelector(":scope > *:first-child");
            if (maquinaHeader) {
                // vuelve al azul... y luego si hay obra activa, la reponemos abajo
                maquinaHeader.classList.remove(
                    "from-orange-400",
                    "to-orange-500"
                );
                maquinaHeader.classList.add("from-blue-600", "to-blue-700");
            }
        });
    }

    // mini-mapa
    MODAL_MAQ_CHIPS.forEach((chip) => {
        chip.classList.remove("from-orange-400", "to-orange-500");
        chip.classList.add("from-blue-600", "to-blue-700");
    });

    _hlNodes = [];
    _hlCode = null;

    if (OBRA_HL.active) applyObraMachineHighlights();
}

function resaltarCompis() {
    const root = document; // o el contenedor que envuelve todas las .planilla

    let ocultarModalesTimeout = null;

    root.addEventListener(
        "mouseover",
        (e) => {
            const card = e.target.closest(".planilla");

            // Si NO estás sobre una card → programa ocultado con delay
            if (!card) {
                // opcional: si pasas por encima de los propios modales, no programes ocultado
                if (
                    e.target.closest("#modal_detalles") ||
                    e.target.closest("#modal_maquinas_con_elementos")
                ) {
                    return;
                }

                clearTimeout(ocultarModalesTimeout);
                ocultarModalesTimeout = setTimeout(() => {
                    _clearHighlight(); // limpia resaltados
                    modalDetalles.classList.add("hidden");
                    modalMapa.classList.add("hidden");
                }, 1000); // 1 s
                return;
            }

            // Si SÍ estás sobre una card → cancela el ocultado y muestra + resalta
            clearTimeout(ocultarModalesTimeout);
            modalDetalles.classList.remove("hidden");
            modalMapa.classList.remove("hidden");

            const code = (
                card.dataset.codigo ||
                card.querySelector(".codigo")?.textContent ||
                ""
            ).trim();

            actualizarModalDetalles(code);
            if (!code || code === _hlCode) return;

            _clearHighlight();

            const matches = document.querySelectorAll(
                `.planilla[data-codigo="${CSS.escape(code)}"]`
            );
            matches.forEach((opd) => {
                if (opd === card) return;
                opd.classList.add("__hl-compi");
                const header = opd
                    .closest(".maquina")
                    ?.querySelector(":scope > *:first-child");
                if (header) {
                    header.classList.remove("from-blue-600", "to-blue-700");
                    header.classList.add("from-orange-400", "to-orange-500");
                }
            });

            const machineIds = getMachineIdsForCode(code);
            highlightModalMachines(machineIds);

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

    // elementos seleccionados
    const seleccionados = Array.from(
        document.getElementsByClassName("seleccionado")
    )
        .map((card) => Number(card.dataset.id))
        .map((id) => elementos.find((e) => Number(e.id) === id))
        .filter(Boolean);

    // título con cantidad
    const smpmTitulo = document.getElementById("smpm_titulo");
    if (smpmTitulo) {
        smpmTitulo.textContent = `Seleccione nueva ubicación para los ${seleccionados.length} elementos`;
    }

    const smpm_maquinas = Array.from(
        document.getElementsByClassName("maquina_transferir")
    );
    let maquinaSeleccionadaId = null;

    const esCompatibleConMaq = (el, maq) => {
        const min = maq.diametro_min != null ? Number(maq.diametro_min) : null;
        const max = maq.diametro_max != null ? Number(maq.diametro_max) : null;
        const d =
            el.diametro != null && el.diametro !== ""
                ? Number(el.diametro)
                : null;
        const dentroMin = min == null || d == null || d >= min;
        const dentroMax = max == null || d == null || d <= max;
        return dentroMin && dentroMax;
    };

    // reset visual
    smpm_maquinas.forEach((maqDiv) => {
        maqDiv.classList.remove(
            "grayscale",
            "opacity-60",
            "cursor-not-allowed"
        );
        maqDiv.classList.add("hover:-translate-y-[1px]");
        maqDiv.dataset.allIncompatibles = "0";

        const badge = maqDiv.querySelector(".badge-incompatibles");
        if (badge) {
            badge.className = "badge-incompatibles hidden";
            badge.textContent = "";
        }
    });

    // calcular compatibilidad y pintar
    smpm_maquinas.forEach((maqDiv) => {
        const maqId = Number(maqDiv.dataset.id);
        const maq = getMaquinaById(maqId);
        if (!maq) return;

        let incompatibles = 0;
        for (const el of seleccionados) {
            if (!esCompatibleConMaq(el, maq)) incompatibles++;
        }

        const meta = maqDiv.querySelector(".maq-meta");
        const badge = maqDiv.querySelector(".badge-incompatibles");
        const codeChip = maqDiv.querySelector(".maq-codigo-chip");

        // 100% incompatibles → gris y sin hover
        if (
            incompatibles === seleccionados.length &&
            seleccionados.length > 0
        ) {
            maqDiv.classList.add(
                "grayscale",
                "opacity-60",
                "cursor-not-allowed"
            );
            maqDiv.classList.remove("hover:-translate-y-[1px]");
            maqDiv.dataset.allIncompatibles = "1";
            // oculta badge por si acaso
            if (badge) {
                badge.classList.add("hidden");
                badge.textContent = "";
            }
        }
        // mezcla → badge a la IZQUIERDA del código
        else if (incompatibles > 0 && meta && badge && codeChip) {
            // estilos del badge: ámbar, mono, extrabold, tamaño mayor
            badge.className =
                "badge-incompatibles inline-flex items-center justify-center rounded-md px-2 py-0.5 " +
                "bg-amber-300 text-amber-900 text-sm font-mono font-extrabold";
            badge.textContent = String(incompatibles);
            // asegúrate de que el badge esté antes del código
            if (codeChip.previousElementSibling !== badge) {
                meta.insertBefore(badge, codeChip);
            }
        } else {
            // todo compatible → sin badge
            if (badge) {
                badge.classList.add("hidden");
                badge.textContent = "";
            }
        }
    });

    // selección de máquina (bloquea 100% incompatibles)
    smpm_maquinas.forEach((maquina) => {
        maquina.onclick = () => {
            if (maquina.dataset.allIncompatibles === "1") {
                maquina.classList.add("animate-pulse");
                setTimeout(
                    () => maquina.classList.remove("animate-pulse"),
                    300
                );
                return;
            }
            smpm_maquinas.forEach((m2) => {
                if (m2 === maquina) {
                    m2.classList.remove("maquina_no_seleccionada");
                    m2.classList.add("maquina_si_seleccionada");
                } else {
                    m2.classList.remove("maquina_si_seleccionada");
                    m2.classList.add("maquina_no_seleccionada");
                }
            });

            maquinaSeleccionadaId = maquina.dataset.id;
            btn_transferir.innerHTML = `TRANSFERIR A <span class="chiptransferirA transition-all duration-150">${
                maquina.querySelector("p").innerText
            }</span>`;
            btn_transferir.classList.add("text-white");
        };
    });
}

// proceso interno de treansferencia de un elemento a otra maquina, aqui se contempla si el elemento va a crear una nueva ordenPlanilla, unificarse con otra...
function transferirElementos() {
    const seleccionados = Array.from(
        document.getElementsByClassName("seleccionado")
    );

    const maqSelDiv = document.querySelector(".maquina_si_seleccionada");
    if (!maqSelDiv) return;

    const maquinaId = Number(maqSelDiv.dataset.id);
    const maquina = getMaquinaById(maquinaId);
    if (!maquina) return;

    // Límites (null = sin límite)
    const min =
        maquina.diametro_min != null ? Number(maquina.diametro_min) : null;
    const max =
        maquina.diametro_max != null ? Number(maquina.diametro_max) : null;

    const compatibles = [];
    const noCompatibles = [];
    seleccionados.forEach((card) => {
        const elId = Number(card.dataset.id);
        const el = elementos.find((e) => Number(e.id) === elId);
        if (!el) return;

        const d = el.diametro != null ? Number(el.diametro) : null; // si no hay diámetro, trátalo como sin restricción
        const dentroMin = min == null || d == null || d >= min;
        const dentroMax = max == null || d == null || d <= max;

        if (dentroMin && dentroMax) {
            compatibles.push({ card, el });
        } else {
            //  Guardamos info completa para pintar tarjetas como anadirElemento
            noCompatibles.push({
                id: el.id ?? null,
                codigo: el.codigo ?? "",
                diametro:
                    el.diametro != null && el.diametro !== ""
                        ? Number(el.diametro).toFixed(2)
                        : "—",
                dimensiones: el.dimensiones ?? "",
                peso: el.peso ?? "N/A",
            });
        }
    });

    // Si hay no compatibles, avisa (pero seguimos con los compatibles)
    if (noCompatibles.length) {
        const rango = `<b>Ø${min == null ? "sin límite" : min}</b> - <b>Ø${
            max == null ? "sin límite" : max
        }</b>`;

        // Tarjetas tipo "anadirElemento" con canvas (idéntico al drag & drop)
        const cardsHtml = `
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 mt-2 max-h-[50vh] overflow-y-scroll">
        ${noCompatibles
            .map((nc, i) => {
                const idCanvas = `cv-tf-nc-${nc.id ?? `maq${maquinaId}-i${i}`}`;
                return `
              <div class="p-2 w-full no_seleccionado text-center bg-gradient-to-tr from-orange-200 to-orange-300 hover:from-orange-300 hover:to-orange-400 hover:-translate-y-1 cursor-pointer rounded-xl flex flex-col items-center transition-all duration-75"
                   data-peso="${nc.peso ?? ""}"
                   data-dimensiones="${nc.dimensiones ?? ""}"
                   data-id="${nc.id ?? ""}">
                <div class="flex justify-between items-center w-full">
                  <p>${nc.codigo}</p>
                  <p><span class="text-red-500 font-semibold">Ø</span>${
                      nc.diametro
                  }</p>
                </div>
                <canvas id="${idCanvas}" class="w-full h-24 bg-white border border-gray-200 rounded-md"></canvas>
              </div>`;
            })
            .join("")}
      </div>
    `;

        Swal.fire({
            icon: "warning",
            title: "Elementos no compatibles",
            width: "auto",
            html: `
          <div class="text-left">
            <p>La máquina <b>${
                maquina.nombre || maquina.codigo || "#" + maquinaId
            }</b>
            admite diámetros: ${rango}.</p>
            <p class="mt-2">Se han descartado del movimiento:</p>
            ${cardsHtml}
          </div>
        `,
            didOpen: () => {
                // Dibujar cada canvas como en anadirElemento
                noCompatibles.forEach((nc, i) => {
                    const idCanvas = `cv-tf-nc-${
                        nc.id ?? `maq${maquinaId}-i${i}`
                    }`;
                    const canvas = document.getElementById(idCanvas);
                    if (
                        !canvas ||
                        typeof window.dibujarFiguraElemento !== "function"
                    )
                        return;

                    const w = Math.max(160, canvas.clientWidth || 160);
                    const h = Math.max(100, canvas.clientHeight || 100);
                    canvas.width = w;
                    canvas.height = h;

                    window.dibujarFiguraElemento(
                        idCanvas,
                        nc.dimensiones || "",
                        nc.peso ?? "N/A"
                    );
                });
            },
        });
    }

    // Si no queda nada compatible, salir
    if (!compatibles.length) {
        cerrarModales();
        return;
    }

    // --- A partir de aquí es tu flujo original, pero usando SOLO los compatibles ---

    // obtener el div de la máquina destino
    let maquinaDiv;
    maquinasDivs.forEach((div) => {
        const det = JSON.parse(div.dataset.detalles);
        if (Number(det.id) === maquinaId) maquinaDiv = div;
    });

    const te_planillas = maquinaDiv.querySelectorAll(".planilla");

    // ¿existe ya planilla con el mismo código en la máquina destino?
    let existe = false;
    te_planillas.forEach((planilla) => {
        if (
            planilla.children[1].innerText.trim() ===
            datosOrdenPlanillaSeleccionado.codigo
        ) {
            existe = true;
        }
    });

    if (existe) {
        const codigoCoincide = datosOrdenPlanillaSeleccionado.codigo;
        const coincidencias = findOrdenesCoincidentes(
            maquinaId,
            codigoCoincide
        );

        renderModalElegirOrden({
            maquinaId,
            codigo: codigoCoincide,
            coincidencias,
        });

        const meo = document.getElementById("modal_elegir_orden");
        const btnConfirmar = document.getElementById("meo_confirmar");
        const btnCancelar = document.getElementById("meo_cancelar");

        btnConfirmar.replaceWith(btnConfirmar.cloneNode(true));
        btnCancelar.replaceWith(btnCancelar.cloneNode(true));

        const _btnConfirmar = document.getElementById("meo_confirmar");
        const _btnCancelar = document.getElementById("meo_cancelar");

        _btnCancelar.addEventListener("click", () => {
            meo.classList.add("hidden");
        });

        _btnConfirmar.addEventListener("click", () => {
            const sel = document.querySelector(
                'input[name="meo_opcion"]:checked'
            );
            if (!sel) return;

            const val = sel.value;
            let destinoOrdenId = null;

            if (val === "__crear_nueva__") {
                const posicionNueva = te_planillas.length + 1;
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

            // aplicar SOLO compatibles
            compatibles.forEach(({ el }) => {
                el.maquina_id = Number(maquinaId);
                el.orden_planilla_id = Number(destinoOrdenId);
            });

            const origenOrdenId = Number(datosOrdenPlanillaSeleccionado.id);
            if (countElementosByOrdenId(origenOrdenId) === 0) {
                removeOrdenPlanilla(origenOrdenId);
            }

            cerrarModales();
            renderPlanillas();
            sePuedeGuardar();
        });
    } else {
        // crear nueva al final
        const posicionNueva = te_planillas.length + 1;
        const nuevaOrdenPlanilla = {
            id: Number(ultimoOrdenPlanillaId) + 1,
            maquina_id: Number(maquinaId),
            planilla_id: Number(datosOrdenPlanillaSeleccionado.planilla_id),
            posicion: posicionNueva,
        };

        ultimoOrdenPlanillaId = nuevaOrdenPlanilla.id;
        const nuevoOrdenId = nuevaOrdenPlanilla.id;
        ordenPlanillas.push(nuevaOrdenPlanilla);

        // aplicar SOLO compatibles
        compatibles.forEach(({ el }) => {
            el.maquina_id = Number(maquinaId);
            el.orden_planilla_id = Number(nuevoOrdenId);
        });

        const origenOrdenId = Number(datosOrdenPlanillaSeleccionado.id);
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

function sePuedeGuardar() {
    const diff = buildDiff();

    const hayCambios =
        (diff.elementos_updates && diff.elementos_updates.length > 0) ||
        (diff.orden_planillas?.create &&
            diff.orden_planillas.create.length > 0) ||
        (diff.orden_planillas?.update &&
            diff.orden_planillas.update.length > 0) ||
        (diff.orden_planillas?.delete &&
            diff.orden_planillas.delete.length > 0);

    if (hayCambios) {
        // Mostrar con transición
        modalGuardar.classList.remove("hidden");
        requestAnimationFrame(() => {
            modalGuardar.classList.remove("-bottom-14", "opacity-0");
            modalGuardar.classList.add("bottom-14", "opacity-100");
        });
    } else {
        // Ocultar con transición
        modalGuardar.classList.remove("bottom-14", "opacity-100");
        modalGuardar.classList.add("-bottom-14", "opacity-0");
        setTimeout(() => {
            modalGuardar.classList.add("hidden");
        }, 150);
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
                const maquina = getMaquinaById(targetMachineId);
                const min =
                    maquina?.diametro_min != null
                        ? Number(maquina.diametro_min)
                        : null;
                const max =
                    maquina?.diametro_max != null
                        ? Number(maquina.diametro_max)
                        : null;

                // elementos de la orden que se pretende mover
                const elemsOrden = elementos.filter(
                    (e) => Number(e.orden_planilla_id) === Number(ordenId)
                );

                const noCompat = [];
                elemsOrden.forEach((el) => {
                    const d = el.diametro != null ? Number(el.diametro) : null;
                    const dentroMin = min == null || d == null || d >= min;
                    const dentroMax = max == null || d == null || d <= max;
                    if (!(dentroMin && dentroMax)) {
                        noCompat.push({
                            id: el.id ?? null,
                            codigo: el.codigo ?? "",
                            diametro:
                                el.diametro != null && el.diametro !== ""
                                    ? Number(el.diametro).toFixed(2)
                                    : "—",
                            dimensiones: el.dimensiones ?? "",
                            peso: el.peso ?? "N/A",
                        });
                    }
                });

                if (noCompat.length) {
                    // revertir visualmente el arrastre (volver a la columna origen)
                    if (src && src !== dst) {
                        src.insertBefore(
                            draggingRef,
                            src.children[
                                calcDropIndex(
                                    src,
                                    draggingRef.getBoundingClientRect().top
                                )
                            ]
                        );
                        reindexDOMColumn(src);
                    }
                    reindexDOMColumn(dst);

                    const rango = `<b>Ø${
                        min == null ? "sin límite" : min
                    }</b> - <b>Ø${max == null ? "sin límite" : max}</b>`;

                    // tarjetas tipo "anadirElemento" con canvas
                    const cardsHtml = `
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 mt-2 max-h-[50vh] overflow-y-scroll">
      ${noCompat
          .map((nc, i) => {
              const idCanvas = `cv-nc-${nc.id ?? `ord${ordenId}-i${i}`}`;
              return `
        <div class="p-2 w-full no_seleccionado text-center bg-gradient-to-tr from-orange-200 to-orange-300 hover:from-orange-300 hover:to-orange-400 hover:-translate-y-1 cursor-pointer rounded-xl flex flex-col items-center transition-all duration-75"
             data-peso="${nc.peso ?? ""}"
             data-dimensiones="${nc.dimensiones ?? ""}"
             data-id="${nc.id ?? ""}">
          <div class="flex justify-between items-center w-full">
            <p>${nc.codigo}</p>
            <p><span class="text-red-500 font-semibold">Ø</span>${
                nc.diametro
            }</p>
          </div>
          <canvas id="${idCanvas}" class="w-full h-24 bg-white border border-gray-200 rounded-md"></canvas>
        </div>`;
          })
          .join("")}
    </div>
  `;

                    Swal.fire({
                        icon: "warning",
                        title: "Movimiento cancelado",
                        width: "auto",
                        height: "auto",
                        html: `
      <div class="text-left">
        <p>Hay elementos no compatibles con la máquina
           <b>${maquina?.nombre || maquina?.codigo || "#" + targetMachineId}</b>
           (diámetros aceptados: ${rango}).</p>
        <p class="mt-2">Revisa los siguientes elementos de la orden:</p>
        ${cardsHtml}
      </div>
    `,
                        didOpen: () => {
                            // dibujar cada canvas como en anadirElemento
                            noCompat.forEach((nc, i) => {
                                const idCanvas = `cv-nc-${
                                    nc.id ?? `ord${ordenId}-i${i}`
                                }`;
                                const canvas =
                                    document.getElementById(idCanvas);
                                if (
                                    !canvas ||
                                    typeof window.dibujarFiguraElemento !==
                                        "function"
                                )
                                    return;

                                const w = Math.max(
                                    160,
                                    canvas.clientWidth || 160
                                );
                                const h = Math.max(
                                    100,
                                    canvas.clientHeight || 100
                                );
                                canvas.width = w;
                                canvas.height = h;

                                window.dibujarFiguraElemento(
                                    idCanvas,
                                    nc.dimensiones || "",
                                    nc.peso ?? "N/A"
                                );
                            });
                        },
                    });
                } else {
                    // todo compatible → mover
                    moveElementsWithOrdenToMachine(ordenId, targetMachineId);
                }
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
                Accept: "application/json",
                "X-CSRF-TOKEN": token ?? "",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data?.success) {
            throw new Error(
                data?.message || "Error al guardar. Revisa la consola."
            );
        }

        // Si el backend reasigna IDs, sincronízalos
        if (data?.orden_planillas_id_map) {
            const idMap = data.orden_planillas_id_map;
            ordenPlanillas.forEach((op) => {
                const n = idMap[String(op.id)];
                if (n) op.id = Number(n);
            });
            elementos.forEach((el) => {
                const n = idMap[String(el.orden_planilla_id)];
                if (n) el.orden_planilla_id = Number(n);
            });
        }

        // Congelar como estado “persistido”
        ELEMENTOS_ORIGINAL = JSON.parse(JSON.stringify(elementos));
        ORDEN_PLANILLAS_ORIGINAL = JSON.parse(JSON.stringify(ordenPlanillas));

        // Refresca UI y oculta la barra de Guardar
        renderPlanillas();
        sePuedeGuardar();

        // ✅ Modal de éxito (mensaje del backend)
        await Swal.fire({
            icon: "success",
            title:
                data?.message || "Movimiento(s) registrado(s) correctamente.",
            timer: 1600,
            showConfirmButton: false,
        });
    } catch (err) {
        console.error("Error guardando:", err);
        await Swal.fire({
            icon: "error",
            title: "Error al guardar",
            text:
                err?.message ||
                "Se produjo un error de red o el servidor devolvió una respuesta no válida.",
        });
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
        btn.classList.toggle("text-blue-700", !activo);
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
    modalResaltarObra.classList.remove("hidden");
    modalDetalles.classList.add("hidden");
    modalMapa.classList.add("hidden");
    document.getElementById("input_filtrar_obra").focus();
}

function actualizarModalDetalles(idPlanilla) {
    let amd_planilla;
    for (let i = 0; i < planillas.length; i++) {
        if (planillas[i].codigo == idPlanilla) {
            amd_planilla = planillas[i];
            break;
        }
    }

    let amd_response;
    amd_response = {
        0: amd_obtenederObra(amd_planilla.obra_id),
        1: amd_planilla.estado.toUpperCase(),
        2: "?",
        3: amd_planilla.estimacion_entrega,
    };

    for (let i = 0; i < 4; i++) {
        modalDetalles.children[i].querySelector("span").innerText =
            amd_response[i];
    }
}

function amd_obtenederObra(idObra) {
    for (let i = 0; i < obras.length; i++) {
        if (obras[i].obra_id == idObra) {
            return obras[i].nombre;
        }
    }
}

// helpers para resaltar ordenPlanillas de obra filtrada
function buildCodesForObra(obraId) {
    const set = new Set();
    planillas.forEach((p) => {
        if (Number(p.obra_id) === Number(obraId)) {
            set.add(String(p.codigo).trim());
        }
    });
    return set;
}

function applyObraStylesToCard(card) {
    // Quitar hover azul y group
    card.classList.remove(
        "hover:from-blue-300",
        "hover:to-blue-400",
        "hover:border-blue-600",
        "group",
        "from-neutral-100",
        "to-neutral-200"
    );
    // Poner gradiente fucsia y borde
    card.classList.add(
        "bg-gradient-to-tr",
        "from-fuchsia-200",
        "to-fuchsia-300",
        "border-2",
        "border-fuchsia-400",
        "obra_resaltada"
    );
    // Textos
    const posEl = card.querySelector(".posicion");
    const codEl = card.querySelector(".codigo");
    posEl?.classList.remove("text-blue-600", "group-hover:text-black");
    codEl?.classList.remove("text-blue-800");
    posEl?.classList.add("text-fuchsia-700");
    codEl?.classList.add("text-fuchsia-700");
}

function resetObraStylesOnCard(card) {
    card.classList.remove(
        "obra_resaltada",
        "from-fuchsia-200",
        "to-fuchsia-300",
        "border-fuchsia-400"
    );
    card.classList.add(
        "bg-gradient-to-tr",
        "from-neutral-100",
        "to-neutral-200",
        "border-2",
        "border-blue-400",
        "hover:from-blue-300",
        "hover:to-blue-400",
        "hover:border-blue-600",
        "group"
    );
    const posEl = card.querySelector(".posicion");
    const codEl = card.querySelector(".codigo");
    posEl?.classList.remove("text-fuchsia-700");
    posEl?.classList.add("text-blue-600", "group-hover:text-black");
    codEl?.classList.remove("text-fuchsia-700");
    codEl?.classList.add("text-blue-800");
}

function applyObraHighlight() {
    if (!OBRA_HL.active || !OBRA_HL.id) return;
    const cards = document.querySelectorAll(".planilla");
    cards.forEach((card) => {
        const code = (
            card.dataset.codigo ||
            card.querySelector(".codigo")?.textContent ||
            ""
        ).trim();
        if (OBRA_HL.codes.has(code)) applyObraStylesToCard(card);
    });

    applyObraMachineHighlights();
}

function clearObraHighlightUI() {
    document.querySelectorAll(".planilla").forEach(resetObraStylesOnCard);
    clearObraColumnHeaderHighlights();
    clearObraModalMachineHighlights();
}

function setObraHighlight(obraId) {
    OBRA_HL.active = true;
    OBRA_HL.id = Number(obraId);
    OBRA_HL.codes = buildCodesForObra(obraId);
    applyObraHighlight();
}

function clearObraHighlight() {
    OBRA_HL = { active: false, id: null, codes: new Set() };
    clearObraHighlightUI();
}

function resaltarObra(quitar = null) {
    let btn1 = document.getElementById("btn_mostrar_modal_obras");
    let btn2 = document.getElementById("btn_quitar_resaltado");

    if (!quitar) {
        btn1.classList.add("hidden");
        btn2.classList.remove("hidden");

        // Solo añadimos un listener simple que fija el estado y aplica
        clickObras.forEach((clickObra) => {
            clickObra.onclick = () => {
                cerrarModales();
                obraSeleccionada = clickObra.dataset.id;
                setObraHighlight(obraSeleccionada); // ← guarda estado y aplica a las cards actuales
            };
        });
    } else {
        btn2.classList.add("hidden");
        btn1.classList.remove("hidden");

        clearObraHighlight(); // ← restaura estilos base en todas las cards y limpia estado
    }
}

// Helpers para saber qué máquinas contienen ese código
function getMachineIdsForCode(code) {
    const mids = new Set();
    ordenPlanillas.forEach((op) => {
        const p = getPlanillaById(op.planilla_id);
        if (!p) return;
        if (String(p.codigo).trim() === String(code).trim()) {
            mids.add(Number(op.maquina_id));
        }
    });
    return Array.from(mids);
}

function highlightModalMachines(machineIds) {
    // reset compi (a azul) para todos antes de pintar naranja
    MODAL_MAQ_CHIPS.forEach((chip) => {
        chip.classList.remove("from-orange-400", "to-orange-500");
    });

    machineIds.forEach((id) => {
        const chip = MODAL_MAQ_CHIPS.get(Number(id));
        if (!chip) return;
        chip.classList.remove(
            "from-blue-600",
            "to-blue-700",
            "from-fuchsia-400",
            "to-fuchsia-600"
        );
        chip.classList.add("from-orange-400", "to-orange-500");
    });
}

function clearModalMachineHighlights() {
    MODAL_MAQ_CHIPS.forEach((chip) => {
        chip.classList.remove("from-orange-400", "to-orange-500");
        chip.classList.add("from-blue-600", "to-blue-700");
    });
}

// Helpers: conseguir máquinas de la obra + resaltar/limpiar
function getMachineIdsForObraCodes(codesSet) {
    const mids = new Set();
    ordenPlanillas.forEach((op) => {
        const p = getPlanillaById(op.planilla_id);
        if (!p) return;
        const code = String(p.codigo).trim();
        if (codesSet.has(code)) mids.add(Number(op.maquina_id));
    });
    return Array.from(mids);
}

/* ----- COLUMNAS (headers arriba de .maquina) ----- */
function clearObraColumnHeaderHighlights() {
    document.querySelectorAll(".maquina > :first-child").forEach((h) => {
        h.classList.remove("from-fuchsia-400", "to-fuchsia-600");
        // vuelve al azul base si no está forzado en naranja por “compis”
        if (!h.classList.contains("from-orange-400")) {
            h.classList.add("from-blue-600", "to-blue-700");
        }
    });
}

function highlightObraColumnHeaders(machineIds) {
    clearObraColumnHeaderHighlights();
    machineIds.forEach((id) => {
        const col = document.querySelector(
            `.maquina[data-maquina-id="${id}"] > :first-child`
        );
        if (!col) return;
        col.classList.remove(
            "from-blue-600",
            "to-blue-700",
            "from-orange-400",
            "to-orange-500"
        );
        col.classList.add("from-fuchsia-400", "to-fuchsia-600");
    });
}

/* ----- MINI-MAPA (chips de #modal_maquinas_con_elementos) ----- */
function clearObraModalMachineHighlights() {
    MODAL_MAQ_CHIPS?.forEach((chip) => {
        chip.classList.remove("from-fuchsia-400", "to-fuchsia-600");
        // si no está en naranja por compis, vuelve al azul
        if (!chip.classList.contains("from-orange-400")) {
            chip.classList.add("from-blue-600", "to-blue-700");
        }
    });
}

function highlightObraModalMachines(machineIds) {
    clearObraModalMachineHighlights();
    machineIds.forEach((id) => {
        const chip = MODAL_MAQ_CHIPS?.get(Number(id));
        if (!chip) return;
        chip.classList.remove(
            "from-blue-600",
            "to-blue-700",
            "from-orange-400",
            "to-orange-500"
        );
        chip.classList.add("from-fuchsia-400", "to-fuchsia-600");
    });
}

/* Util centralizado para (re)aplicar highlight de obra a máquinas */
function applyObraMachineHighlights() {
    if (!OBRA_HL.active || !OBRA_HL.codes?.size) {
        clearObraColumnHeaderHighlights();
        clearObraModalMachineHighlights();
        return;
    }
    const mids = getMachineIdsForObraCodes(OBRA_HL.codes);
    highlightObraColumnHeaders(mids);
    highlightObraModalMachines(mids);
}

// seleccionar todos los elementos
function ensureSelectAllToolbar() {
    const cont = document.getElementById("seleccion_elementos");
    const modal = document.getElementById("modal_elementos");
    if (!cont || !modal) return;

    // Botón
    let btnSel = document.getElementById("btn_sel_todos");
    if (!btnSel) {
        btnSel = document.createElement("button");
        btnSel.id = "btn_sel_todos";
        btnSel.type = "button";
        btnSel.className =
            "px-3 py-1 rounded-lg text-xs font-mono font-bold " +
            "bg-gradient-to-tr from-blue-600 to-blue-700 text-white hover:opacity-90 transition";
        document.getElementById("header_seleecionar_elementos").appendChild(btnSel);
    }

    // Helpers
    const counts = () => {
        const total = cont.querySelectorAll(
            ".no_seleccionado, .seleccionado"
        ).length;
        const sel = cont.querySelectorAll(".seleccionado").length;
        return { total, sel };
    };

    const updateLabel = () => {
        const { total, sel } = counts();
        // ✅ sin cantidades en el texto
        btnSel.textContent =
            sel === total && total > 0
                ? "Quitar selección"
                : "Seleccionar todos";
    };

    const setAllSeleccionados = (on) => {
        cont.querySelectorAll(".no_seleccionado, .seleccionado").forEach(
            (card) => {
                if (on) {
                    card.classList.remove(
                        "no_seleccionado",
                        "hover:from-orange-300",
                        "hover:to-orange-400"
                    );
                    card.classList.add(
                        "seleccionado",
                        "to-orange-400",
                        "from-blue-500"
                    );
                } else {
                    card.classList.add(
                        "no_seleccionado",
                        "hover:from-orange-300",
                        "hover:to-orange-400"
                    );
                    card.classList.remove(
                        "seleccionado",
                        "to-orange-400",
                        "from-blue-500"
                    );
                }
            }
        );
    };

    // Click toggle (recalcula siempre total/sel)
    btnSel.onclick = () => {
        const { total, sel } = counts();
        const seleccionar = sel < total; // si no están todos, selecciona; si ya están todos, des-selecciona
        setAllSeleccionados(seleccionar);
        updateLabel();
    };

    // Actualiza la etiqueta cuando el usuario selecciona/deselecciona individualmente
    if (!cont.dataset.selAllBound) {
        cont.addEventListener("click", (ev) => {
            if (ev.target.closest(".no_seleccionado, .seleccionado")) {
                // Deja que el toggle de clases de tu handler se aplique primero
                setTimeout(updateLabel, 0);
            }
        });
        cont.dataset.selAllBound = "1";
    }

    // Atajo Ctrl+A (solo dentro del modal)
    if (!modal.dataset.ctrlABound) {
        modal.addEventListener("keydown", (ev) => {
            if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === "a") {
                ev.preventDefault();
                setAllSeleccionados(true);
                updateLabel();
            }
        });
        modal.dataset.ctrlABound = "1";
    }

    // Estado inicial correcto cada vez que se abre el modal
    updateLabel();
}
