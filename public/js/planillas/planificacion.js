document.addEventListener("DOMContentLoaded", () => {
    const MAQUINAS = Array.from(document.getElementsByClassName("maquina"));
    const CONTENEDORES = MAQUINAS.map(m => m.querySelector('.planillas'));
    const BOTONES = [document.getElementById("nave1"), document.getElementById("nave2"), document.getElementById("ambas")];
    const PLANILLAS = Array.from(document.getElementsByClassName("planilla"));
    const QUIT_ELEMENTOS = document.getElementById("quit_elementos");
    const ELEMENTOS_EN_SELECCION = document.getElementById("elementos_en_seleccion")
    const MOVER_MODAL_ELEMENTOS = document.getElementById("mover_modal_elementos")

    BOTONES.forEach(boton => {
        boton.addEventListener("click", () => {
            BOTONES.forEach(b => b.style.color = "black");
            mostrarPorNave(MAQUINAS, boton);
        });
    });

    resaltarCompis(PLANILLAS);

    initDragAndDrop(CONTENEDORES, MAQUINAS);
    mostrarElementos(PLANILLAS, ELEMENTOS_EN_SELECCION, MOVER_MODAL_ELEMENTOS)
    funcionesQuitElementos(QUIT_ELEMENTOS, ELEMENTOS_EN_SELECCION)
    moverModalElementos(MOVER_MODAL_ELEMENTOS, ELEMENTOS_EN_SELECCION)
});

function initDragAndDrop(contenedores, maquinas) {
    let dragging = null;
    let origen = null;

    // 1) Eventos en cada tarjeta
    document.querySelectorAll('.planilla').forEach(card => {
        card.setAttribute('draggable', 'true');

        card.addEventListener('dragstart', (e) => {
            dragging = e.currentTarget;
            origen = dragging.parentElement; // .planillas de origen
            dragging.classList.add('dragging');
            // para algunos navegadores
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', dragging.dataset.planillaId || '');
        });

        card.addEventListener('dragend', () => {
            if (!dragging) return;
            dragging.classList.remove('dragging');

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

async function mostrarElementos(planillas, elementos_en_seleccion, btn) {
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

            const elementos = await obtenerElementos(planilla_id, maquina_id);

            let padre_elementos = document.getElementById("seleccion_elementos")
            padre_elementos.innerHTML = "";
            console.log(elementos)

            elementos_en_seleccion.classList.remove("-left-96")
            elementos_en_seleccion.classList.add("left-3")
            btn.textContent = ">";


            elementos.forEach(elemento => {
                anadirElemento(padre_elementos, elemento)
            });
        });
    });
}

async function obtenerElementos(planilla_id, maquina_id) {
    try {
        const res = await fetch(`/api/elementos?planilla_id=${planilla_id}&maquina_id=${maquina_id}`);
        const data = await res.json();
        return data;
    } catch (err) {
        console.error('Error al obtener elementos:', err);
        return [];
    }
}

function anadirElemento(padre, elemento) {
    let contenido_actual = padre.innerHTML
    nuevo_contenido = "<div class='p-2 w-full text-center bg-blue-300 rounded-xl'>" + elemento.codigo + "</div>"
    padre.innerHTML = contenido_actual + nuevo_contenido

}

function funcionesQuitElementos(quit, elementos_en_seleccion) {
    quit.addEventListener("click", () => {

        elementos_en_seleccion.classList.remove("left-3", "left-[calc(100%-15rem)]");
        elementos_en_seleccion.classList.add("-left-96")
    })
}

function moverModalElementos(btn, elementos_en_seleccion) {
    btn.addEventListener("click", (e) => {
        const flecha = e.target.textContent.trim();

        if (flecha === ">") {
            elementos_en_seleccion.classList.replace("left-3", "left-[calc(100%-15rem)]");
            e.target.textContent = "<";
        } else {
            elementos_en_seleccion.classList.replace("left-[calc(100%-15rem)]", "left-3");
            e.target.textContent = ">";
        }
    });
}
