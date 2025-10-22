let datos_elementos;
let datos_elementos_original;
let div_elementos;
let modal_elementos
let modal_transferir
let pendingFusion = null; // { planillaId, codigo, origenCol, destinoCol, draggingEl, origenMachineId, destinoMachineId, originIndex }



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
    renderPlanillasFromDatos(datos_elementos);


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
        };
    }

    // cancelar cambios
    document.getElementById("btn_cancelar_guardar").addEventListener("click", () => {
        datos_elementos = JSON.parse(JSON.stringify(datos_elementos_original));
        renderPlanillasFromDatos(datos_elementos);
        document.getElementById("modal_guardar")?.classList.add("hidden");
    });


    if (btnFusionAceptar) {
        btnFusionAceptar.onclick = () => {
            if (!pendingFusion) return;
            const { planillaId, origenMachineId, destinoMachineId, destinoCol } = pendingFusion;

            const before = JSON.parse(JSON.stringify(datos_elementos));

            // 1) Actualizar datos: mover TODOS los elementos de esa planilla a la máquina destino
            const movidos = applyPlanillaMoveToDatos(planillaId, origenMachineId, destinoMachineId);
            // console.log(`✅ Fusionar: movidos ${movidos} elementos de planilla ${planillaId} a máquina ${destinoMachineId}`);

            // 2) En el DOM, asegura que solo quede una tarjeta para esa planilla en destino
            const dups = Array.from(destinoCol.querySelectorAll('.planilla'))
                .filter(pl => Number(pl.dataset.planillaId) === Number(planillaId));
            // Dejar la primera, eliminar las demás
            dups.slice(1).forEach(n => n.remove());
            reindexColumn(destinoCol);

            // 3) Sincronizar orden desde DOM
            const nuevosOrdenes = syncOrdenPlanillasDesdeDOM();
            // console.log('📦 Orden tras fusión:', nuevosOrdenes);

            // 4) Re-render lógico por si hay efectos colaterales
            renderPlanillasFromDatos(datos_elementos);

            // 5) Diff opcional
            logDiffDatosElementos(before, datos_elementos, planillaId);

            // 6) Mostrar modal Guardar (si lo usas)
            const modalGuardar = document.getElementById('modal_guardar');
            if (modalGuardar) modalGuardar.classList.remove('hidden');

            // Cerrar modal fusión
            const modalFusion = document.getElementById("modal_fusionar_planilla");
            modalFusion.classList.add("hidden");
            pendingFusion = null;
        };
    }

    const MODAL_GUARDAR = document.getElementById('modal_guardar');
    const BTN_GUARDAR = document.getElementById('btn_guardar');

    if (BTN_GUARDAR && MODAL_GUARDAR) {
        BTN_GUARDAR.addEventListener('click', async () => {
            const GUARDAR_URL = MODAL_GUARDAR.dataset.saveUrl; // ← aquí está la ruta buena
            const ordenes = collectOrdenPayload();
            const cambios_elementos = collectCambiosElementosPayload();

            if (ordenes.length === 0 && cambios_elementos.length === 0) {
                MODAL_GUARDAR.classList.add('hidden');
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
                MODAL_GUARDAR.classList.add('hidden');
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

                // OJO: no actualizamos datos todavía, se hace si aceptan
                dragging = null;
                origenCol = null;
                origenMachineId = null;
                originIndex = -1;
                return; // salimos aquí
            }

            // --- Flujo normal sin duplicado ---
            const before = JSON.parse(JSON.stringify(datos_elementos));

            if (destinoMachineId != null && origenMachineId != null && destinoMachineId !== origenMachineId) {
                const n = applyPlanillaMoveToDatos(planillaId, origenMachineId, destinoMachineId);
                // console.log(`🔁 Movida planilla ${planillaId} de máquina ${origenMachineId} a ${destinoMachineId}. Elementos afectados: ${n}`);
            }

            const nuevosOrdenes = syncOrdenPlanillasDesdeDOM();
            // console.log('📦 Orden actualizado desde DOM:', nuevosOrdenes);

            logDiffDatosElementos(before, datos_elementos, planillaId);

            const modalGuardar = document.getElementById('modal_guardar');
            if (modalGuardar) modalGuardar.classList.remove('hidden');

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

            const after = getDragAfterElement(col, e.clientY);
            const draggingEl = document.querySelector('.planilla.dragging');
            if (!draggingEl) return;

            if (!after) col.appendChild(draggingEl);
            else col.insertBefore(draggingEl, after);
        });

        col.addEventListener('dragleave', () => {
            col.classList.remove('drop-target');
        });

        col.addEventListener('drop', () => {
            col.classList.remove('drop-target');
            // resto se maneja en dragend
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
        if (Number(before.maquina_id) !== Number(now.maquina_id)) {
            cambios.push({
                id: Number(now.id),
                maquina_id: Number(now.maquina_id)
            });
        }
    });
    return cambios;
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

    // Recorremos el array principal y modificamos los que coincidan
    datos_elementos.forEach(e => {
        if (idsNumericos.includes(Number(e.id))) {
            e.maquina_id = Number(nuevo_maquina_id);
        }
    });

    // console.log(`Actualizados ${idsNumericos.length} elementos a máquina ${nuevo_maquina_id}`);
    haCambiado = JSON.stringify(datos_elementos) !== JSON.stringify(datos_elementos_original);
    renderPlanillasFromDatos(datos_elementos);

    if (haCambiado) {
        // console.log("Cambios detectados:");

        // Recorremos todos los elementos nuevos
        datos_elementos.forEach((nuevo) => {
            const anterior = datos_elementos_original.find(e => e.id === nuevo.id);
            if (!anterior) {
                // console.log(`Nuevo elemento con ID ${nuevo.id}`, nuevo);
                return;
            }

            // Comparamos campo a campo
            const cambios = {};
            for (const key in nuevo) {
                if (JSON.stringify(nuevo[key]) !== JSON.stringify(anterior[key])) {
                    cambios[key] = { antes: anterior[key], ahora: nuevo[key] };
                }
            }

            // Si hubo cambios, los mostramos
            if (Object.keys(cambios).length > 0) {
                console.group(`Elemento ${nuevo.id}`);
                console.table(cambios);
                console.groupEnd();
            }
        });
    }


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
            // console.log("sin maquina seleccionada");
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


// logica renderizado planillas
// --- helpers para leer datasets ocultos ---
function buildPlanillasMap() {
    const cont = document.getElementById('todasPlanillas');
    const map = new Map(); // planilla_id -> codigo
    if (!cont) return map;
    cont.querySelectorAll('[data-planilla]').forEach(node => {
        try {
            const { id, codigo } = JSON.parse(node.dataset.planilla);
            map.set(Number(id), String(codigo));
        } catch (_) { }
    });
    return map;
}

function buildOrdenesMap() {
    const cont = document.getElementById('ordenPlanillas');
    const byMaquina = new Map(); // maquina_id -> Map(planilla_id -> posicion)
    if (!cont) return byMaquina;
    cont.querySelectorAll('[data-orden]').forEach(node => {
        try {
            const { maquina_id, planilla_id, posicion } = JSON.parse(node.dataset.orden);
            const mid = Number(maquina_id);
            const pid = Number(planilla_id);
            const pos = Number(posicion);
            if (!byMaquina.has(mid)) byMaquina.set(mid, new Map());
            byMaquina.get(mid).set(pid, pos);
        } catch (_) { }
    });
    return byMaquina;
}

// Crea el nodo .planilla
function createPlanillaCard({ planilla_id, codigo, posicion }) {
    const div = document.createElement('div');
    div.className = "planilla p-3 flex justify-around items-center bg-orange-300 hover:bg-orange-400 cursor-grab active:cursor-grabbing select-none text-center relative";
    div.setAttribute('draggable', 'true');
    div.dataset.planillaId = String(planilla_id);
    if (posicion != null) div.dataset.posicion = String(posicion);

    const posP = document.createElement('p');
    posP.className = "text-neutral-500 text-xs font-bold absolute top-1 left-1 pos-label";
    posP.textContent = posicion != null ? String(posicion) : ""; // se rellenará con reindex si no hay orden

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
    const planillasMap = buildPlanillasMap();
    const ordenesByMaquina = buildOrdenesMap();

    // 1) Agrupar por máquina -> set de planilla_id
    const porMaquina = new Map(); // maquina_id -> Set(planilla_id)
    datos_elementos.forEach(e => {
        const mid = Number(e.maquina_id);
        const pid = Number(e.planilla_id);
        if (!porMaquina.has(mid)) porMaquina.set(mid, new Set());
        porMaquina.get(mid).add(pid);
    });

    // 2) Para cada máquina del DOM, pintar sus planillas
    document.querySelectorAll('.maquina').forEach(maq => {
        const mid = Number(maq.dataset.maquinaId || JSON.parse(maq.dataset.detalles || '{}').id);
        const cont = maq.querySelector('.planillas');
        if (!cont) return;
        cont.innerHTML = '';

        const planillasSet = porMaquina.get(mid) || new Set();
        let tarjetas = [];

        // construimos array con {planilla_id, codigo, posicion}
        planillasSet.forEach(pid => {
            const codigo = planillasMap.get(pid) ?? `PL-${pid}`;
            const posicion = ordenesByMaquina.get(mid)?.get(pid) ?? null;
            tarjetas.push({ planilla_id: pid, codigo, posicion });
        });

        // 3) ordenar: primero por 'posicion' si existe, si no por 'codigo'
        tarjetas.sort((a, b) => {
            const ap = a.posicion ?? Infinity;
            const bp = b.posicion ?? Infinity;
            if (ap !== bp) return ap - bp;
            return String(a.codigo).localeCompare(String(b.codigo));
        });

        // 4) pintar
        tarjetas.forEach(t => {
            const card = createPlanillaCard(t);
            cont.appendChild(card);
        });

        // 5) si no traíamos posiciones, reindexa para que se vean números
        reindexColumn(cont);
    });

    // 6) reengancha drag & drop y eventos dependientes de .planilla
    initDragAndDrop(
        Array.from(document.querySelectorAll('.planillas')),
        Array.from(document.querySelectorAll('.maquina'))
    );

    // OJO: si tienes otros listeners ligados a .planilla (ej. mostrarElementos),
    // vuelve a montarlos:
    const PLANILLAS = Array.from(document.getElementsByClassName("planilla"));
    resaltarCompis(PLANILLAS);
    mostrarElementos(
        PLANILLAS,
        document.getElementById("div_elementos"),
        document.getElementById("mover_modal_elementos"),
        datos_elementos
    );
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
    const nuevosOrdenes = [];
    document.querySelectorAll('.maquina').forEach(maq => {
        const mid = Number(maq.dataset.maquinaId);
        const cont = maq.querySelector('.planillas');
        if (!cont) return;
        [...cont.querySelectorAll('.planilla')].forEach((pl, idx) => {
            nuevosOrdenes.push({
                maquina_id: mid,
                planilla_id: Number(pl.dataset.planillaId),
                posicion: idx + 1
            });
        });
    });
    return nuevosOrdenes;
}

function applyPlanillaMoveToDatos(planillaId, oldMachineId, newMachineId) {
    // Cambia maquina_id de todos los elementos de esa planilla
    let cambios = 0;
    datos_elementos.forEach(e => {
        if (Number(e.planilla_id) === Number(planillaId) &&
            Number(e.maquina_id) === Number(oldMachineId)) {
            e.maquina_id = Number(newMachineId);
            cambios++;
        }
    });
    return cambios;
}

function logDiffDatosElementos(beforeArr, afterArr, onlyPlanillaId = null) {
    console.group('🔎 Diff elementos');
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
        if (Object.keys(diffs).length) {
            console.group(`Elemento ${nuevo.id}`);
            console.table(diffs);
            console.groupEnd();
        }
    });
    console.groupEnd();
}
