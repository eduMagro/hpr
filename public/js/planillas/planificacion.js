let datos_elementos;
let datos_elementos_original;
let div_elementos;
let modal_elementos
let modal_transferir
let pendingFusion = null; // { planillaId, codigo, origenCol, destinoCol, draggingEl, origenMachineId, destinoMachineId, originIndex }
let obras
let select_obra


document.addEventListener("DOMContentLoaded", () => {
    const MAQUINAS = Array.from(document.getElementsByClassName("maquina"));
    const CONTENEDORES = MAQUINAS.map(m => m.querySelector('.planillas'));
    const BOTONES = [document.getElementById("nave_1"), document.getElementById("nave_2"), document.getElementById("nave_3")];
    const PLANILLAS = Array.from(document.getElementsByClassName("planilla"));

    const MOVER_MODAL_ELEMENTOS = document.getElementById("mover_modal_elementos")
    // abrir el modal de selección de máquina desde el modal de elementos
    MOVER_MODAL_ELEMENTOS?.addEventListener('click', seleccionarMaquinaParaMovimiento);

    div_elementos = document.getElementById("div_elementos")
    modal_elementos = document.getElementById("modal_elementos")
    modal_transferir = document.getElementById("modal_transferir_a_maquina");
    modales = [modal_elementos, modal_transferir]
    select_obra = document.getElementById("select_obra")

    // datos de todas las obras (id, nombre)
    obras = Array.from(document.querySelectorAll("#obras [data-obras]")).map(div => JSON.parse(div.dataset.obras));

    console.log(obras);


    const TODOS = document.getElementById("todosElementos");
    let elementos = TODOS.querySelectorAll("[data-elementos]");
    datos_elementos = Array.from(elementos).map(div => JSON.parse(div.dataset.elementos));
    datos_elementos_original = JSON.parse(JSON.stringify(datos_elementos));

    renderPlanillasFromDatos(datos_elementos);
    anadirNombreObraADataPlanilla()


    BOTONES.forEach(boton => {
        boton.addEventListener("click", () => {
            BOTONES.forEach(b => {
                b.classList.remove("bg-gradient-to-r", "text-white")
                b.classList.add("text-emerald-700")
            });
            mostrarPorNave(MAQUINAS, boton);
        });
    });

    // llamada de funciones
    resaltarCompis(PLANILLAS);
    initDragAndDrop(CONTENEDORES, MAQUINAS);
    mostrarElementos(PLANILLAS, div_elementos, MOVER_MODAL_ELEMENTOS, datos_elementos)
    resaltarPorObra()


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

    const modalFusion = document.getElementById("modal_fusionar_planilla");
    const btnFusionCancelar = document.getElementById("fusionar_cancelar");
    const btnFusionAceptar = document.getElementById("fusionar_aceptar");

    if (btnFusionCancelar) {
        btnFusionCancelar.onclick = () => {
            if (!pendingFusion) return;
            // Revertir la tarjeta a su sitio original
            const { origenCol, draggingEl, originIndex } = pendingFusion;
            if (origenCol && draggingEl) {
                const children = Array.from(origenCol.children);
                const ref = children[originIndex] || null;
                origenCol.insertBefore(draggingEl, ref); // vuelve a su índice o al final
                reindexColumn(origenCol);
            }
            modalFusion.classList.add("hidden");
            pendingFusion = null;
            resetPlanillaTransforms();
        };
    }

    // cancelar cambios
    document.getElementById("btn_cancelar_guardar").addEventListener("click", () => {
        datos_elementos = JSON.parse(JSON.stringify(datos_elementos_original));
        renderPlanillasFromDatos(datos_elementos);
        document.getElementById("modal_guardar")?.classList.replace("bottom-14", "-bottom-14");
    });


    if (btnFusionAceptar) {
        btnFusionAceptar.onclick = () => {
            if (!pendingFusion) return;
            const { planillaId, destinoCol, destinoMachineId } = pendingFusion;

            // localizar la tarjeta destino (la que queda)
            const targetCard = Array.from(destinoCol.querySelectorAll('.planilla'))
                .find(pl => Number(pl.dataset.planillaId) === Number(planillaId));
            const targetOrdenId = Number(targetCard?.dataset.ordenId);

            // mover TODOS los elementos de esa planilla a ese orden + máquina
            datos_elementos.forEach(e => {
                if (Number(e.planilla_id) === Number(planillaId)) {
                    e.orden_planilla_id = targetOrdenId;
                    e.maquina_id = destinoMachineId; // 👈 también actualizamos máquina
                }
            });

            renderPlanillasFromDatos(datos_elementos);
            document.getElementById('modal_guardar')?.classList.replace('-bottom-14', 'bottom-14');
            document.getElementById("modal_fusionar_planilla").classList.add("hidden");
            pendingFusion = null;
        };
    }


    const MODAL_GUARDAR = document.getElementById('modal_guardar');
    const BTN_GUARDAR = document.getElementById('btn_guardar');

    if (BTN_GUARDAR && MODAL_GUARDAR) {
        BTN_GUARDAR.addEventListener('click', async () => {
            const GUARDAR_URL = MODAL_GUARDAR.dataset.saveUrl;
            const ordenes = collectOrdenPayload();
            const cambios_elementos = collectCambiosElementosPayload();

            if (ordenes.length === 0 && cambios_elementos.length === 0) {
                MODAL_GUARDAR.classList.replace("bottom-14", "-bottom-14");
                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const resp = await fetch(GUARDAR_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(token ? { 'X-CSRF-TOKEN': token } : {})
                    },
                    body: JSON.stringify({ ordenes, cambios_elementos })
                });

                if (!resp.ok) {
                    const err = await resp.json().catch(() => ({}));
                    console.error('❌ Error al guardar:', err);
                    alert('No se han podido guardar los cambios.');
                    return;
                }

                datos_elementos_original = JSON.parse(JSON.stringify(datos_elementos));
                MODAL_GUARDAR.classList.replace("bottom-14", "-bottom-14");
                // console.log('✅ Cambios guardados');
            } catch (e) {
                console.error('❌ Error de red al guardar:', e);
                alert('Error de red al guardar.');
            }
        });
    }
});

function indexOfChild(parent, child) {
    return Array.prototype.indexOf.call(parent.children, child);
}


function initDragAndDrop(contenedores, maquinas) {
    let dragging = null;
    let origenCol = null;
    let origenMachineId = null;
    let originIndex = -1;

    document.querySelectorAll('.planilla').forEach(card => {
        card.setAttribute('draggable', 'true');

        card.addEventListener('dragstart', (e) => {
            dragging = e.currentTarget;
            origenCol = dragging.parentElement;               // .planillas (origen)
            origenMachineId = getMachineIdFromColumn(origenCol);
            originIndex = indexOfChild(origenCol, dragging);  // posición original
            dragging.classList.add('dragging', 'cursor-grabbing');
            dragging.classList.remove('cursor-grab');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', dragging.dataset.planillaId || '');
        });

        card.addEventListener('dragend', () => {
            if (!dragging) return;

            const destinoCol = dragging.parentElement;        // .planillas (destino)
            const destinoMachineId = getMachineIdFromColumn(destinoCol);
            const planillaId = Number(dragging.dataset.planillaId);
            const planillaCodigo = dragging.querySelector('p:nth-child(2)')?.textContent?.trim() || `PL-${planillaId}`;

            dragging.classList.remove('dragging', 'cursor-grabbing');
            dragging.classList.add('cursor-grab');

            // Reindex visual
            reindexColumn(origenCol);
            if (destinoCol !== origenCol) reindexColumn(destinoCol);

            // --- ¿Hay duplicado en destino? (misma planilla_id) ---
            const yaExiste = Array.from(destinoCol.querySelectorAll('.planilla'))
                .some(pl => pl !== dragging && Number(pl.dataset.planillaId) === planillaId);

            if (yaExiste) {
                // Preparar modal de fusión
                pendingFusion = {
                    planillaId,
                    codigo: planillaCodigo,
                    origenCol,
                    destinoCol,
                    draggingEl: dragging,
                    origenMachineId,
                    destinoMachineId,
                    originIndex
                };

                const spanCod = document.getElementById('fusionar_planilla_codigo');
                if (spanCod) spanCod.textContent = planillaCodigo;

                const modalFusion = document.getElementById("modal_fusionar_planilla");
                modalFusion.classList.remove("hidden");

                resetPlanillaTransforms();

                // OJO: no actualizamos datos todavía, se hace si aceptan
                dragging = null;
                origenCol = null;
                origenMachineId = null;
                originIndex = -1;
                return; // salimos aquí
            }

            // --- Flujo normal sin duplicado ---
            const before = JSON.parse(JSON.stringify(datos_elementos));

            const nuevosOrdenes = syncOrdenPlanillasDesdeDOM();
            // console.log('📦 Orden actualizado desde DOM:', nuevosOrdenes);

            logDiffDatosElementos(before, datos_elementos, planillaId);

            const modalGuardar = document.getElementById('modal_guardar');
            if (modalGuardar) modalGuardar.classList.replace("-bottom-14", "bottom-14");

            dragging = null;
            origenCol = null;
            origenMachineId = null;
            originIndex = -1;
        });
    });

    contenedores.forEach(col => {
        col.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            col.classList.add('drop-target');

            const draggingEl = document.querySelector('.planilla.dragging');
            if (!draggingEl) return;

            // 1) Medimos posiciones ANTES de mover
            const hermanosAntes = [...col.querySelectorAll('.planilla:not(.dragging)')];
            const prev = new Map(hermanosAntes.map(el => [el, el.getBoundingClientRect()]));

            // 2) Insertamos en la nueva posición
            const after = getDragAfterElement(col, e.clientY);
            if (!after) col.appendChild(draggingEl);
            else col.insertBefore(draggingEl, after);

            // 3) Medimos DESPUÉS y animamos (FLIP)
            const hermanosDespues = [...col.querySelectorAll('.planilla:not(.dragging)')];

            hermanosDespues.forEach(el => {
                const beforeRect = prev.get(el);
                if (!beforeRect) return; // elementos nuevos no se animan

                const afterRect = el.getBoundingClientRect();
                const dx = beforeRect.left - afterRect.left;
                const dy = beforeRect.top - afterRect.top;

                if (dx !== 0 || dy !== 0) {
                    // Invertimos
                    el.style.transition = 'none';
                    el.style.transform = `translate(${dx}px, ${dy}px)`;

                    // Y reproducimos la animación a su sitio
                    requestAnimationFrame(() => {
                        el.style.transition = 'transform 140ms ease';
                        el.style.transform = '';
                    });
                }
            });
        });

        col.addEventListener('dragleave', () => {
            col.classList.remove('drop-target');
        });

        col.addEventListener('drop', () => {
            col.classList.remove('drop-target');
            // el resto se maneja en dragend
        });
    });
}


function collectOrdenPayload() {
    return syncOrdenPlanillasDesdeDOM(); // ya la tienes; devuelve [{maquina_id, planilla_id, posicion}, ...]
}

// 2) Cambios de elementos (solo difs maquina_id)
function collectCambiosElementosPayload() {
    const cambios = [];
    const originalMap = new Map(datos_elementos_original.map(e => [Number(e.id), e]));

    datos_elementos.forEach(now => {
        const before = originalMap.get(Number(now.id));
        if (!before) return;

        const diffOrden = Number(before.orden_planilla_id) !== Number(now.orden_planilla_id);
        const diffMaq = Number(before.maquina_id) !== Number(now.maquina_id);

        if (diffOrden || diffMaq) {
            cambios.push({
                id: Number(now.id),
                ...(diffOrden ? { orden_planilla_id: Number(now.orden_planilla_id) } : {}),
                ...(diffMaq ? { maquina_id: Number(now.maquina_id) } : {}),
            });
        }
    });

    return cambios;
}


function resetPlanillaTransforms(root = document) {
    root.querySelectorAll('.planilla').forEach(el => {
        el.style.transition = '';
        el.style.transform = '';
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

    boton.classList.add("bg-gradient-to-r", "text-white")
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
                ?.querySelector('.bg-gradient-to-r p')  // o '.rounded-t-xl p'
                ?.textContent?.trim() || "";

            const orden_id = Number(target.dataset.ordenId || 0);  // 👈 importante
            const planilla_id = Number(target.dataset.planillaId || target.getAttribute("data-planilla-id"));

            window.__ctx_planilla_id__ = planilla_id;
            window.__ctx_orden_id__ = orden_id;

            // info de máquina para pintar cabecera
            const padre_maquina = target.closest('.maquina');
            if (!padre_maquina) return;
            const raw = padre_maquina.dataset.detalles || padre_maquina.getAttribute('data-detalles');
            let maquina_id = null;
            try { maquina_id = JSON.parse(raw).id; } catch { return; }

            // pintar cabecera
            const seleccion_planilla_codigo = document.getElementById("seleccion_planilla_codigo");
            const seleccion_maquina_tag = document.getElementById("seleccion_maquina_tag");
            if (seleccion_planilla_codigo) seleccion_planilla_codigo.textContent = codigo;
            if (seleccion_maquina_tag) seleccion_maquina_tag.textContent = maquinaTitulo;

            // 👇 recoger elementos por orden_planilla_id
            const elementos = datos_elementos.filter(e => Number(e.orden_planilla_id) === orden_id);

            const padre_elementos = document.getElementById("seleccion_elementos");
            padre_elementos.innerHTML = "";
            modal_elementos.classList.remove("hidden");

            elementos.forEach((elemento, i) => anadirElemento(padre_elementos, elemento, i));
            dibujarMiniFiguras(padre_elementos);
            anadirPropiedadTransferible();
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
        <div class="flex justify-between items-center w-full">
            <div class="text-neutral-600 text-xs font-mono font-semibold">${n + 1}</div>
            <p>${elemento.codigo}</p>
            <p><span class="text-red-500 font-semibold">Ø</span>${elemento.diametro}</p>
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


function actualizarMaquinaDeElementos(ids, nuevo_maquina_id, ordenDestinoId) {
    const idsNumericos = ids.map(Number);

    datos_elementos.forEach(e => {
        if (idsNumericos.includes(Number(e.id))) {
            e.maquina_id = Number(nuevo_maquina_id);
            if (ordenDestinoId != null) {
                e.orden_planilla_id = Number(ordenDestinoId);
            }
        }
    });

    renderPlanillasFromDatos(datos_elementos);
    document.getElementById('modal_guardar')?.classList.replace('-bottom-14', 'bottom-14');
}

// transcurso entre que se seleccionan los elementos hasta que se le da al boton de transferir (a otra maquina)
function seleccionarMaquinaParaMovimiento() {
    const modal = document.getElementById("modal_transferir_a_maquina");
    const btnTransferir = document.getElementById("transferir_elementos");
    const maquinas = Array.from(document.getElementsByClassName("maquina_transferir"));

    modal_elementos.classList.add("hidden")
    modal.classList.remove("hidden");

    let maquinaSeleccionadaId = null;

    maquinas.forEach(m => {
        m.addEventListener("click", () => {
            maquinas.forEach(x => {
                x.classList.add("hover:bg-neutral-400", "bg-neutral-300");
                x.classList.remove("bg-neutral-400");
            });
            m.classList.remove("hover:bg-neutral-400", "bg-neutral-300");
            m.classList.add("bg-neutral-400");

            maquinaSeleccionadaId = m.getAttribute("data-id");
        });
    });

    btnTransferir.onclick = () => {
        if (!maquinaSeleccionadaId) {
            return;
        }

        const elementos_seleccionados = Array.from(document.getElementsByClassName("seleccionado"))
            .map(el => el.getAttribute("data-id"));

        if (elementos_seleccionados.length === 0) {
            alert("No hay elementos seleccionados");
            return;
        }

        // Validar compatibilidad
        const { validos, invalidos } = validarCompatibilidadElementos(elementos_seleccionados, maquinaSeleccionadaId);

        // Si todos son válidos, proceder normalmente
        if (invalidos.length === 0) {
            maquinas.forEach(x => {
                x.classList.add("hover:bg-neutral-400", "bg-neutral-300");
                x.classList.remove("bg-neutral-400");
            });
            modal.classList.add("hidden");
            const ordenDestinoId = obtenerOrdenDestinoId(maquinaSeleccionadaId);
            actualizarMaquinaDeElementos(elementos_seleccionados, maquinaSeleccionadaId, ordenDestinoId);

            return;
        }

        // Si hay elementos inválidos, mostrar modal de advertencia
        mostrarModalAdvertencia(validos, invalidos, maquinaSeleccionadaId);

        // Cerrar modal de transferir
        maquinas.forEach(x => {
            x.classList.add("hover:bg-neutral-400", "bg-neutral-300");
            x.classList.remove("bg-neutral-400");
        });
        modal.classList.add("hidden");
    };
}

function obtenerOrdenDestinoId(maquinaSeleccionadaId) {
    const mid = Number(maquinaSeleccionadaId);
    const pid = Number(window.__ctx_planilla_id__);
    if (!mid || !pid) return null;

    const byMaquina = buildOrdenesMap(); // maquina_id -> Array<{id, planilla_id, posicion}>
    const ordenesDeMaquina = byMaquina.get(mid) || [];

    // 1) Busca misma planilla en esa máquina
    const match = ordenesDeMaquina.find(o => Number(o.planilla_id) === pid);
    if (match) return Number(match.id);

    // 2) (Opcional) fallback: primer orden de esa máquina
    // return ordenesDeMaquina.length ? Number(ordenesDeMaquina[0].id) : null;

    // Si no quieres fallback, deja:
    return null;
}


// logica renderizado planillas
// --- helpers para leer datasets ocultos ---
function buildPlanillasMap() {
    const cont = document.getElementById('todasPlanillas');
    const map = new Map(); // planilla_id -> { codigo, obra_id }
    if (!cont) return map;

    cont.querySelectorAll('[data-planilla]').forEach(node => {
        try {
            const { id, codigo, obra_id } = JSON.parse(node.dataset.planilla);
            map.set(Number(id), {
                codigo: String(codigo ?? `PL-${id}`),
                obra_id: obra_id != null ? Number(obra_id) : null
            });
        } catch (_) { /* silencio */ }
    });

    return map;
}


function buildOrdenesMap() {
    const cont = document.getElementById('ordenPlanillas');
    const byMaquina = new Map(); // maquina_id -> Array<{id, planilla_id, posicion}>
    if (!cont) return byMaquina;

    cont.querySelectorAll('[data-orden]').forEach(node => {
        try {
            const { id, maquina_id, planilla_id, posicion } = JSON.parse(node.dataset.orden);
            const mid = Number(maquina_id);
            if (!byMaquina.has(mid)) byMaquina.set(mid, []);
            byMaquina.get(mid).push({
                id: Number(id),
                planilla_id: Number(planilla_id),
                posicion: Number(posicion)
            });
        } catch (_) { }
    });

    // ordenar por posicion
    for (const arr of byMaquina.values()) arr.sort((a, b) => a.posicion - b.posicion);
    return byMaquina;
}


// Crea el nodo .planilla
function createPlanillaCard({ orden_id, planilla_id, codigo, posicion, obra_id = null }) {
    const div = document.createElement('div');
    div.className = "planilla p-3 flex justify-around items-center border border-emerald-400 hover:-translate-y-1 transition-all duration-75 ease-in-out rounded-xl bg-white hover:bg-emerald-400 cursor-grab active:cursor-grabbing select-none text-center relative";
    div.setAttribute('draggable', 'true');

    div.dataset.ordenId = String(orden_id);
    div.dataset.planillaId = String(planilla_id);
    if (posicion != null) div.dataset.posicion = String(posicion);
    if (obra_id != null) div.dataset.obraId = String(obra_id);

    const posP = document.createElement('p');
    posP.className = "text-neutral-500 text-xs font-bold absolute top-1 left-1 pos-label";
    posP.textContent = posicion != null ? String(posicion) : "";

    const codeP = document.createElement('p');
    codeP.textContent = codigo ?? `PL-${planilla_id}`;

    div.appendChild(posP);
    div.appendChild(codeP);
    return div;
}



/**
 * Rellena cada columna .planillas a partir de datos_elementos
 * - Agrupa por (maquina_id, planilla_id)
 * - Usa ordenPlanillas si existe; si no, ordena por codigo asc
 */
function renderPlanillasFromDatos(datos_elementos) {
    const planillasMap = buildPlanillasMap();   // planilla_id -> {codigo, obra_id}
    const ordenesByMaquina = buildOrdenesMap();     // maquina_id -> [{id, planilla_id, posicion}, ...]

    document.querySelectorAll('.maquina').forEach(maq => {
        const mid = Number(maq.dataset.maquinaId || JSON.parse(maq.dataset.detalles || '{}').id);
        const cont = maq.querySelector('.planillas');
        if (!cont) return;
        cont.innerHTML = '';

        const ordenes = ordenesByMaquina.get(mid) || [];
        ordenes.forEach(({ id: orden_id, planilla_id, posicion }) => {
            const info = planillasMap.get(planilla_id) || {};
            const codigo = info.codigo ?? `PL-${planilla_id}`;
            const obraId = info.obra_id ?? null;

            const card = createPlanillaCard({ orden_id, planilla_id, codigo, posicion, obra_id: obraId });
            cont.appendChild(card);
        });

        reindexColumn(cont);
    });

    initDragAndDrop(
        Array.from(document.querySelectorAll('.planillas')),
        Array.from(document.querySelectorAll('.maquina'))
    );

    const PLANILLAS = Array.from(document.getElementsByClassName("planilla"));
    resaltarCompis(PLANILLAS);
    mostrarElementos(
        PLANILLAS,
        document.getElementById("div_elementos"),
        document.getElementById("mover_modal_elementos"),
        datos_elementos
    );

    resetPlanillaTransforms();
}




function getMachineIdFromColumn(columnEl) {
    const maquina = columnEl.closest('.maquina');
    if (!maquina) return null;
    const midAttr = maquina.dataset.maquinaId;
    if (midAttr) return Number(midAttr);
    try {
        return Number(JSON.parse(maquina.dataset.detalles || '{}').id);
    } catch {
        return null;
    }
}

function syncOrdenPlanillasDesdeDOM() {
    const nuevos = [];
    document.querySelectorAll('.maquina').forEach(maq => {
        const mid = Number(maq.dataset.maquinaId);
        const cont = maq.querySelector('.planillas');
        if (!cont) return;
        [...cont.querySelectorAll('.planilla')].forEach((pl, idx) => {
            nuevos.push({
                orden_id: pl.dataset.ordenId ? Number(pl.dataset.ordenId) : null,
                maquina_id: mid,
                planilla_id: Number(pl.dataset.planillaId),
                posicion: idx + 1
            });
        });
    });
    return nuevos;
}

function logDiffDatosElementos(beforeArr, afterArr, onlyPlanillaId = null) {
    // console.group('🔎 Diff elementos');
    afterArr.forEach(nuevo => {
        if (onlyPlanillaId && Number(nuevo.planilla_id) !== Number(onlyPlanillaId)) return;
        const anterior = beforeArr.find(e => e.id === nuevo.id);
        if (!anterior) {
            // console.log(`🆕 Nuevo elemento ${nuevo.id}`, nuevo);
            return;
        }
        const diffs = {};
        for (const k in nuevo) {
            if (JSON.stringify(nuevo[k]) !== JSON.stringify(anterior[k])) {
                diffs[k] = { antes: anterior[k], ahora: nuevo[k] };
            }
        }
        // if (Object.keys(diffs).length) {
        //     console.group(`Elemento ${nuevo.id}`);
        //     console.table(diffs);
        //     console.groupEnd();
        // }
    });
    console.groupEnd();
}

// función para obtener los detalles de una máquina por ID
function getMaquinaDetallesById(maquina_id) {
    const maquina = document.querySelector(`.maquina[data-maquina-id="${maquina_id}"]`);
    if (!maquina) return null;
    try {
        return JSON.parse(maquina.dataset.detalles);
    } catch {
        return null;
    }
}

// función para validar compatibilidad entre elementos y maquina
function validarCompatibilidadElementos(elementos_ids, maquina_id) {
    const maquinaDetalles = getMaquinaDetallesById(maquina_id);
    if (!maquinaDetalles) return { validos: elementos_ids, invalidos: [] };

    const diametro_min = Number(maquinaDetalles.diametro_min);
    const diametro_max = Number(maquinaDetalles.diametro_max);

    const validos = [];
    const invalidos = [];

    elementos_ids.forEach(id => {
        const elemento = datos_elementos.find(e => Number(e.id) === Number(id));
        if (!elemento) return;

        const diametro = Number(elemento.diametro);

        // Validar si el diámetro está dentro del rango
        if (diametro >= diametro_min && diametro <= diametro_max) {
            validos.push({ id, codigo: elemento.codigo, diametro });
        } else {
            invalidos.push({ id, codigo: elemento.codigo, diametro });
        }
    });

    return { validos, invalidos };
}

// función para mostrar el modal de advertencia
function mostrarModalAdvertencia(validos, invalidos, maquina_id) {
    const modalAdvertencia = document.getElementById("modal_advertencia_compatibilidad");
    const listaIncompatibles = document.getElementById("lista_elementos_incompatibles");
    const countValidos = document.getElementById("count_validos");
    const countInvalidos = document.getElementById("count_invalidos");
    const btnCancelar = document.getElementById("advertencia_cancelar");
    const btnProseguir = document.getElementById("advertencia_proseguir");

    const maquinaDetalles = getMaquinaDetallesById(maquina_id);

    // Si la máquina no tiene restricciones de diámetro, todos son válidos
    if (!maquinaDetalles || maquinaDetalles.diametro_min == null || maquinaDetalles.diametro_max == null) {
        const idsTodos = validos.concat(invalidos).map(v => v.id ?? v);
        const ordenDestinoId = obtenerOrdenDestinoId(maquina_id);
        actualizarMaquinaDeElementos(idsTodos, maquina_id, ordenDestinoId);
        return;
    }


    const rangoText = `Rango permitido: ${maquinaDetalles.diametro_min}mm - ${maquinaDetalles.diametro_max}mm`;

    // Actualizar contadores
    countValidos.textContent = validos.length;
    countInvalidos.textContent = invalidos.length;

    // Llenar lista de incompatibles
    listaIncompatibles.innerHTML = `
        <div class="text-xs text-gray-600 mb-2">${rangoText}</div>
        ${invalidos.map(el => `
            <div class="bg-white p-2 rounded mb-2 flex justify-between items-center border border-red-200">
                <span class="font-mono font-semibold">${el.codigo}</span>
                <span class="text-sm text-red-600">Ø ${el.diametro}mm</span>
            </div>
        `).join('')}
    `;

    // Mostrar modal
    modalAdvertencia.classList.remove("hidden");

    // Manejar botón cancelar
    btnCancelar.onclick = () => {
        modalAdvertencia.classList.add("hidden");
    };

    // Manejar botón proseguir
    btnProseguir.onclick = () => {
        modalAdvertencia.classList.add("hidden");

        if (validos.length === 0) {
            alert("No hay elementos compatibles para transferir");
            return;
        }

        const idsValidos = validos.map(v => v.id);
        const ordenDestinoId = obtenerOrdenDestinoId(maquina_id);
        actualizarMaquinaDeElementos(idsValidos, maquina_id, ordenDestinoId);
    };
}


function resaltarPorObra() {
    select_obra.addEventListener("change", () => {
        seleccion = select_obra.value
        let planillas = Array.from(document.getElementsByClassName("planilla"))

        planillas.forEach(planilla => {

            planilla.classList.remove("border-purple-500", "bg-purple-200")
            planilla.classList.add("hover:bg-emerald-400", "border-emerald-500")

            if (planilla.dataset.obraId == seleccion) {
                planilla.classList.remove("hover:bg-emerald-400", "border-emerald-500")
                planilla.classList.add("border-purple-500", "bg-purple-200")
            }
        });
    })
}

function modalDetallesPlanilla() {
    let planillas = Array.from(document.getElementsByClassName("planilla"))

}

function anadirNombreObraADataPlanilla() {

}