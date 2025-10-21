let datos_elementos;
let datos_elementos_original;
let div_elementos;
let modal_elementos
let modal_transferir


document.addEventListener("DOMContentLoaded", () => {
    const MAQUINAS = Array.from(document.getElementsByClassName("maquina"));
    const CONTENEDORES = MAQUINAS.map(m => m.querySelector('.planillas'));
    const BOTONES = [document.getElementById("nave1"), document.getElementById("nave2"), document.getElementById("ambas")];
    const PLANILLAS = Array.from(document.getElementsByClassName("planilla"));
    const MOVER_MODAL_ELEMENTOS = document.getElementById("mover_modal_elementos")
    div_elementos = document.getElementById("div_elementos")
    modal_elementos = document.getElementById("modal_elementos")
    modal_transferir = document.getElementById("modal_transferir_a_maquina");
    modales = [modal_elementos, modal_transferir]

    const TODOS = document.getElementById("todosElementos");
    let elementos = TODOS.querySelectorAll("[data-elementos]");
    datos_elementos = Array.from(elementos).map(div => JSON.parse(div.dataset.elementos));
    datos_elementos_original = JSON.parse(JSON.stringify(datos_elementos));

    BOTONES.forEach(boton => {
        boton.addEventListener("click", () => {
            BOTONES.forEach(b => b.style.color = "black");
            mostrarPorNave(MAQUINAS, boton);
        });
    });

    // llamada de funciones
    resaltarCompis(PLANILLAS);
    initDragAndDrop(CONTENEDORES, MAQUINAS);
    mostrarElementos(PLANILLAS, div_elementos, MOVER_MODAL_ELEMENTOS, datos_elementos)


    // Agregar Listener de cierre a los modales cuando se clicka fuera de los hijos del mismo
    modales.forEach(modal => {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.add("hidden");
            }
        });

    });

    // cerrar modal elementos por boton cancelar
    document.getElementById("cancelar_modal_elementos").addEventListener("click", () => modal_elementos.classList.add("hidden"))
});

function initDragAndDrop(contenedores, maquinas) {
    let dragging = null;
    let origen = null;

    // 1) Eventos en cada tarjeta
    document.querySelectorAll('.planilla').forEach(card => {
        card.setAttribute('draggable', 'true');

        card.addEventListener('dragstart', (e) => {
            dragging = e.currentTarget;
            origen = dragging.parentElement;
            dragging.classList.add('dragging', 'cursor-grabbing');
            dragging.classList.remove('cursor-grab');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', dragging.dataset.planillaId || '');
        });



        card.addEventListener('dragend', () => {
            if (!dragging) return;

            dragging.classList.remove('dragging', 'cursor-grabbing');
            dragging.classList.add('cursor-grab');

            // reindexar origen y (si cambió) destino
            reindexColumn(origen);
            const destino = dragging.parentElement;
            if (destino !== origen) reindexColumn(destino);

            // ajustar alturas después de mover
            const MAQUINAS = Array.from(document.getElementsByClassName('maquina'));

            dragging = null;
            origen = null;
        });
    });

    // 2) Eventos en cada contenedor de columna (.planillas)
    contenedores.forEach(col => {
        col.addEventListener('dragover', (e) => {
            e.preventDefault(); // necesario para permitir drop
            e.dataTransfer.dropEffect = 'move';
            col.classList.add('drop-target');

            const after = getDragAfterElement(col, e.clientY);
            if (!after) {
                col.appendChild(document.querySelector('.planilla.dragging'));
            } else {
                col.insertBefore(document.querySelector('.planilla.dragging'), after);
            }
        });

        col.addEventListener('dragleave', () => {
            col.classList.remove('drop-target');
        });

        col.addEventListener('drop', () => {
            col.classList.remove('drop-target');
            // el reindex lo hace dragend
        });
    });
}

// Devuelve el elemento .planilla inmediatamente posterior a la posición del cursor,
// para saber dónde insertar la tarjeta arrastrada.
function getDragAfterElement(container, y) {
    const els = [...container.querySelectorAll('.planilla:not(.dragging)')];

    let closest = null;
    let closestOffset = Number.NEGATIVE_INFINITY;

    els.forEach(el => {
        const box = el.getBoundingClientRect();
        const offset = y - (box.top + box.height / 2);
        if (offset < 0 && offset > closestOffset) {
            closestOffset = offset;
            closest = el;
        }
    });

    return closest;
}

function reindexColumn(columnEl) {
    const items = [...columnEl.querySelectorAll('.planilla')];
    items.forEach((item, idx) => {
        item.dataset.posicion = String(idx + 1);
        const label = item.querySelector('.pos-label');
        if (label) label.textContent = String(idx + 1);
    });
}

function mostrarPorNave(maquinas, boton) {
    const texto = boton.textContent;

    maquinas.forEach(maq => { maq.style.display = "flex"; });

    if (texto == "Nave 1") {
        maquinas.forEach(maq => { if (getDetalles(maq).nave_id != 1) maq.style.display = "none"; });
    } else if (texto == "Nave 2") {
        maquinas.forEach(maq => { if (getDetalles(maq).nave_id != 2) maq.style.display = "none"; });
    }

    boton.style.color = "#2563eb";
}

function getDetalles(maquina) { return JSON.parse(maquina.dataset.detalles); }



function resaltarCompis(planillas) {
    planillas.forEach(planilla => {
        planilla.addEventListener('mouseenter', () => {
            const codigo = planilla.children[1].textContent.trim();
            planillas.forEach(compi => {
                if (compi !== planilla && compi.children[1].textContent.trim() === codigo) {
                    compi.classList.add('compi-resaltado');
                }
            });
        });
        planilla.addEventListener('mouseleave', () => {
            planillas.forEach(compi => compi.classList.remove('compi-resaltado'));
        });
    });
}

async function mostrarElementos(planillas, div_elementos, btn, datos_elementos) {
    planillas.forEach(planilla => {
        planilla.addEventListener("click", async (e) => {
            const target = e.currentTarget;
            const codigo = target.children[1]?.textContent?.trim() || "";
            const maquinaTitulo = target.closest('.maquina')
                ?.querySelector('.bg-neutral-400 p, .bg-slate-300 p')
                ?.textContent?.trim() || "";

            const planilla_id = target.dataset.planillaId || target.getAttribute("data-planilla-id");
            const padre_maquina = target.closest('.maquina');
            if (!padre_maquina) return;

            const raw = padre_maquina.dataset.detalles || padre_maquina.getAttribute('data-detalles');
            let maquina_id = null;
            try {
                maquina_id = JSON.parse(raw).id;
            } catch (err) {
                console.error("JSON inválido en data-detalles:", raw, err);
                return;
            }

            // pintar UI
            const seleccion_planilla_codigo = document.getElementById("seleccion_planilla_codigo");
            const seleccion_maquina_tag = document.getElementById("seleccion_maquina_tag");
            if (seleccion_planilla_codigo) seleccion_planilla_codigo.textContent = codigo;
            if (seleccion_maquina_tag) seleccion_maquina_tag.textContent = maquinaTitulo;

            const elementos = agregarElementosModal(planilla_id, maquina_id, datos_elementos)

            let padre_elementos = document.getElementById("seleccion_elementos")
            padre_elementos.innerHTML = "";

            modal_elementos.classList.remove("hidden")

            elementos.forEach((elemento, i) => {
                anadirElemento(padre_elementos, elemento, i)
            });
            dibujarMiniFiguras(padre_elementos);
            anadirPropiedadTransferible()
        });
    });
}

function agregarElementosModal(planilla_id, maquina_id, datos_elementos) {
    return datos_elementos.filter(e =>
        e.planilla_id == planilla_id && e.maquina_id == maquina_id
    );
}


function anadirElemento(padre, elemento, n) {
    const idCanvas = `cv-el-${elemento.id}`;

    const html = `
    <div class="p-2 w-full no_seleccionado text-center bg-blue-300 hover:bg-blue-400 cursor-pointer rounded-xl flex flex-col items-center transition-all duration-150"
         data-peso="${elemento.peso ?? ''}"
         data-dimensiones="${elemento.dimensiones ?? ''}"
         data-id="${elemento.id}">
        <div class="flex justify-center gap-4 items-center w-full">
            <div class="text-neutral-600 text-xs font-mono font-semibold">${n + 1}</div>
            <p>${elemento.codigo}</p>
        </div>
        <canvas id="${idCanvas}" class="w-full h-24 bg-white border border-gray-200 rounded-md"></canvas>
    </div>`;

    padre.insertAdjacentHTML('beforeend', html);

    // 🔧 Ajusta el tamaño real del canvas a su tamaño mostrado, y dibuja
    const canvas = document.getElementById(idCanvas);
    if (canvas && typeof window.dibujarFiguraElemento === 'function') {
        // igualamos dimensiones internas a las CSS para que no se vea borroso
        const w = Math.max(160, canvas.clientWidth || 160);
        const h = Math.max(100, canvas.clientHeight || 100);
        canvas.width = w;
        canvas.height = h;

        window.dibujarFiguraElemento(idCanvas, elemento.dimensiones || '', elemento.peso ?? 'N/A');
    }
}

function dibujarMiniFiguras(padre) {
    if (typeof window.dibujarFiguraElemento !== 'function') return;

    padre.querySelectorAll('[data-dimensiones]').forEach(card => {
        const canvas = card.querySelector('canvas');
        if (!canvas) return;
        // ajustar tamaño interno a tamaño CSS
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
                target.classList.remove("no_seleccionado", "bg-blue-300", "hover:bg-blue-400");
                target.classList.add("seleccionado", "bg-blue-500");
                target.children[0].classList.remove("text-neutral-500");
                target.children[0].classList.add("text-white");
            } else {
                target.classList.add("no_seleccionado", "bg-blue-300", "hover:bg-blue-400");
                target.classList.remove("seleccionado", "bg-blue-500");
                target.children[0].classList.add("text-neutral-500");
                target.children[0].classList.remove("text-white");
            }
        });
    });
}


function actualizarMaquinaDeElementos(ids, nuevo_maquina_id) {
    // Convertir IDs a números por si vienen como strings
    const idsNumericos = ids.map(id => Number(id));
    let haCambiado = JSON.stringify(datos_elementos) !== JSON.stringify(datos_elementos_original);
    console.log("¿Ha cambiado?", haCambiado);


    // Recorremos el array principal y modificamos los que coincidan
    datos_elementos.forEach(e => {
        if (idsNumericos.includes(Number(e.id))) {
            e.maquina_id = Number(nuevo_maquina_id);
        }
    });

    console.log(`Actualizados ${idsNumericos.length} elementos a máquina ${nuevo_maquina_id}`);
    haCambiado = JSON.stringify(datos_elementos) !== JSON.stringify(datos_elementos_original);
    console.log("¿Ha cambiado?", haCambiado);

    recalcularPlanillas()
}

function recalcularPlanillas() {
    console.log(datos_elementos)
}


// transcurso entre que se seleccionan los elementos hasta que se le da al boton de transferir (a otra maquina)
function seleccionarMaquinaParaMovimiento() {
    const modal = document.getElementById("modal_transferir_a_maquina");
    const btnTransferir = document.getElementById("transferir_elementos");
    const maquinas = Array.from(document.getElementsByClassName("maquina_transferir"));

    modal_elementos.classList.add("hidden")
    modal.classList.remove("hidden");

    // estado: máquina actualmente seleccioná
    let maquinaSeleccionadaId = null;

    // click en cada maquina -> solo actualiza seleccion y estilos
    maquinas.forEach(m => {
        m.addEventListener("click", () => {
            // estilos
            maquinas.forEach(x => {
                x.classList.add("hover:bg-neutral-400", "bg-neutral-300");
                x.classList.remove("bg-neutral-400");
            });
            m.classList.remove("hover:bg-neutral-400", "bg-neutral-300");
            m.classList.add("bg-neutral-400");

            // guardar seleccion
            maquinaSeleccionadaId = m.getAttribute("data-id");
        });
    });

    // Registrar el click del btn
    btnTransferir.onclick = () => {
        if (!maquinaSeleccionadaId) {
            console.log("sin maquina seleccionada");
            return;
        }

        // recoger ids seleccionados actuales
        const elementos_seleccionados = Array.from(document.getElementsByClassName("seleccionado"))
            .map(el => el.getAttribute("data-id"));

        // reset estilos + cerrar modal
        maquinas.forEach(x => {
            x.classList.add("hover:bg-neutral-400", "bg-neutral-300");
            x.classList.remove("bg-neutral-400");
        });
        modal.classList.add("hidden");

        // ejecutar transferencia
        actualizarMaquinaDeElementos(elementos_seleccionados, maquinaSeleccionadaId);
    };
}
