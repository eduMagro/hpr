// ==============================================
// GESTIÓN DE SALIDAS - CREAR Y ASIGNAR PAQUETES
// ==============================================

function initGestionSalidas() {
    console.log("🚀 Iniciando gestión de salidas...");
    // Generar formularios para crear salidas
    const btnGenerarFormularios = document.getElementById(
        "btn-generar-formularios"
    );
    if (btnGenerarFormularios) {
        btnGenerarFormularios.addEventListener(
            "click",
            generarFormulariosSalidas
        );
    }

    // Crear todas las salidas
    const btnCrearTodasSalidas = document.getElementById(
        "btn-crear-todas-salidas"
    );
    if (btnCrearTodasSalidas) {
        btnCrearTodasSalidas.addEventListener("click", crearTodasSalidas);
    }

    // Guardar asignaciones de paquetes
    const btnGuardarAsignaciones = document.getElementById(
        "btn-guardar-asignaciones"
    );
    if (btnGuardarAsignaciones) {
        btnGuardarAsignaciones.addEventListener("click", guardarAsignaciones);
    }

    // Inicializar drag and drop si hay salidas existentes
    if (document.querySelector(".paquete-item")) {
        inicializarDragAndDrop();
        actualizarTotalesSalidas();

        // Inicializar filtros si hay paquetes
        if (document.getElementById("filtro-obra")) {
            inicializarFiltros();
        }
    } else {
        // Si no hay paquetes en el DOM, renderizar los disponibles
        console.log("🔄 No hay paquetes en DOM inicial, renderizando...");

        if (
            window.AppGestionSalidas &&
            document.querySelector('.paquetes-zona[data-salida-id="null"]')
        ) {
            const paquetesIniciales = window.AppGestionSalidas
                .mostrarTodosPaquetes
                ? window.paquetesTodos
                : window.paquetesFiltrados;

            if (paquetesIniciales && paquetesIniciales.length > 0) {
                renderizarPaquetesDisponibles(paquetesIniciales);
                inicializarDragAndDrop();

                // Inicializar filtros DESPUÉS de renderizar
                if (document.getElementById("filtro-obra")) {
                    inicializarFiltros();
                }
            }
        }
    }
}

// Ejecutar en DOMContentLoaded y en navegación de Livewire
document.addEventListener("DOMContentLoaded", initGestionSalidas);
document.addEventListener("livewire:navigated", initGestionSalidas);

/* ===================== Generar formularios de salidas ===================== */
function generarFormulariosSalidas() {
    const numSalidas = parseInt(document.getElementById("num-salidas").value);

    if (numSalidas < 1 || numSalidas > 10) {
        Swal.fire(
            "⚠️",
            "El número de salidas debe estar entre 1 y 10",
            "warning"
        );
        return;
    }

    const container = document.getElementById("formularios-salidas");
    container.innerHTML = "";

    const empresas = window.AppGestionSalidas.empresas;
    const camiones = window.AppGestionSalidas.camiones;

    for (let i = 1; i <= numSalidas; i++) {
        const formulario = crearFormularioSalida(i, empresas, camiones);
        container.appendChild(formulario);
    }

    document.getElementById("btn-crear-container").classList.remove("hidden");
}

/* ===================== Crear formulario individual de salida ===================== */
function crearFormularioSalida(numero, empresas, camiones) {
    const div = document.createElement("div");
    div.className = "bg-gray-50 border border-gray-300 rounded-lg p-4";
    div.dataset.salidaIndex = numero;

    // Obtener fecha por defecto de la primera planilla si existe
    const fechaPorDefecto = new Date().toISOString().split("T")[0];

    div.innerHTML = `
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Salida #${numero}</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Salida</label>
                <input type="date"
                       class="salida-fecha w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       value="${fechaPorDefecto}"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Empresa de Transporte (opcional)</label>
                <select class="salida-empresa w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sin asignar (se asignará después)</option>
                    ${empresas
                        .map(
                            (empresa) => `
                        <option value="${empresa.id}">${empresa.nombre}</option>
                    `
                        )
                        .join("")}
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Camión (opcional)</label>
                <select class="salida-camion w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sin asignar (se asignará después)</option>
                    ${camiones
                        .map(
                            (camion) => `
                        <option value="${camion.id}" data-empresa="${
                                camion.empresa_id
                            }">
                            ${camion.modelo} - ${
                                camion.matricula || "Sin matrícula"
                            }
                        </option>
                    `
                        )
                        .join("")}
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código SAGE (opcional)</label>
                <input type="text"
                       class="salida-codigo-sage w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Código SAGE">
            </div>
        </div>
    `;

    // Filtrar camiones por empresa seleccionada
    const selectEmpresa = div.querySelector(".salida-empresa");
    const selectCamion = div.querySelector(".salida-camion");

    selectEmpresa.addEventListener("change", function () {
        const empresaId = this.value;
        const opciones = selectCamion.querySelectorAll("option");

        opciones.forEach((opcion) => {
            if (opcion.value === "") {
                opcion.style.display = "block";
                return;
            }

            if (opcion.dataset.empresa === empresaId) {
                opcion.style.display = "block";
            } else {
                opcion.style.display = "none";
                if (opcion.selected) {
                    selectCamion.value = "";
                }
            }
        });
    });

    return div;
}

/* ===================== Crear todas las salidas ===================== */
async function crearTodasSalidas() {
    const formularios = document.querySelectorAll("[data-salida-index]");
    const salidas = [];

    // Validar y recopilar datos
    for (const form of formularios) {
        const fecha = form.querySelector(".salida-fecha").value;
        const empresaId = form.querySelector(".salida-empresa").value;
        const camionId = form.querySelector(".salida-camion").value;
        const codigoSage = form.querySelector(".salida-codigo-sage").value;

        if (!fecha) {
            Swal.fire(
                "⚠️",
                "Por favor, completa la fecha de salida en todas las salidas",
                "warning"
            );
            return;
        }

        salidas.push({
            fecha_salida: fecha,
            empresa_transporte_id: empresaId || null,
            camion_id: camionId ? parseInt(camionId) : null,
            codigo_sage: codigoSage || null,
        });
    }

    try {
        Swal.fire({
            title: "Creando salidas...",
            text: "Por favor espera",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await fetch(
            window.AppGestionSalidas.routes.crearSalidasVacias,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": window.AppGestionSalidas.csrf,
                },
                body: JSON.stringify({
                    salidas: salidas,
                    planillas_ids: window.AppGestionSalidas.planillasIds,
                }),
            }
        );

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: "success",
                title: "✅ Salidas Creadas",
                text: `Se han creado ${data.salidas_creadas} salidas correctamente`,
                timer: 2000,
            });

            // Recargar la página para mostrar la gestión de paquetes
            window.location.href =
                window.AppGestionSalidas.routes.recargarVista;
        } else {
            Swal.fire(
                "❌",
                data.message || "Error al crear las salidas",
                "error"
            );
        }
    } catch (error) {
        console.error("Error:", error);
        Swal.fire("❌", "Error al crear las salidas", "error");
    }
}

/* ===================== Inicializar Drag and Drop ===================== */
function inicializarDragAndDrop() {
    let draggedElement = null;
    let touchStartElement = null;
    let ghostElement = null;
    let touchOffsetX = 0;
    let touchOffsetY = 0;

    // Eventos de drag para los paquetes (desktop)
    document.querySelectorAll(".paquete-item").forEach((item) => {
        // Desktop - Drag & Drop
        item.addEventListener("dragstart", (e) => {
            draggedElement = item;
            item.style.opacity = "0.5";
            e.dataTransfer.effectAllowed = "move";
        });

        item.addEventListener("dragend", (e) => {
            item.style.opacity = "1";
            draggedElement = null;
        });

        // Mobile - Touch events
        item.addEventListener("touchstart", (e) => {
            touchStartElement = item;
            item.style.opacity = "0.3";

            // Calcular offset desde el punto de toque hasta el elemento
            const rect = item.getBoundingClientRect();
            const touch = e.touches[0];
            touchOffsetX = touch.clientX - rect.left;
            touchOffsetY = touch.clientY - rect.top;

            // Crear elemento fantasma
            ghostElement = item.cloneNode(true);
            ghostElement.classList.add("ghost-dragging");
            ghostElement.style.position = "fixed";
            ghostElement.style.width = rect.width + "px";
            ghostElement.style.pointerEvents = "none";
            ghostElement.style.zIndex = "9999";
            ghostElement.style.opacity = "0.9";
            ghostElement.style.transform = "scale(1.05)";
            ghostElement.style.boxShadow = "0 10px 30px rgba(0, 0, 0, 0.3)";
            ghostElement.style.left = (touch.clientX - touchOffsetX) + "px";
            ghostElement.style.top = (touch.clientY - touchOffsetY) + "px";

            document.body.appendChild(ghostElement);
        });

        item.addEventListener("touchend", (e) => {
            if (!touchStartElement) return;

            const touch = e.changedTouches[0];
            const targetElement = document.elementFromPoint(
                touch.clientX,
                touch.clientY
            );

            // Buscar la zona de drop más cercana
            const dropZone = targetElement?.closest(".drop-zone");

            if (dropZone && touchStartElement) {
                // Remover placeholder si existe
                const placeholder = dropZone.querySelector(".text-gray-400");
                if (placeholder) placeholder.remove();

                // Mover el paquete
                dropZone.appendChild(touchStartElement);

                // Actualizar totales
                actualizarTotalesSalidas();
            }

            // Limpiar estado
            touchStartElement.style.opacity = "1";
            touchStartElement = null;

            // Eliminar elemento fantasma
            if (ghostElement) {
                ghostElement.remove();
                ghostElement = null;
            }

            // Limpiar resaltado de todas las zonas
            document.querySelectorAll(".drop-zone").forEach((z) => {
                z.style.backgroundColor = "";
            });
        });

        item.addEventListener("touchmove", (e) => {
            if (!touchStartElement) return;

            e.preventDefault(); // Prevenir scroll mientras arrastramos

            const touch = e.touches[0];

            // Mover el elemento fantasma
            if (ghostElement) {
                ghostElement.style.left = (touch.clientX - touchOffsetX) + "px";
                ghostElement.style.top = (touch.clientY - touchOffsetY) + "px";
            }

            const targetElement = document.elementFromPoint(
                touch.clientX,
                touch.clientY
            );

            // Limpiar resaltado de todas las zonas
            document.querySelectorAll(".drop-zone").forEach((z) => {
                z.style.backgroundColor = "";
            });

            // Resaltar la zona bajo el dedo
            const dropZone = targetElement?.closest(".drop-zone");
            if (dropZone) {
                dropZone.style.backgroundColor = "#e0f2fe";
            }
        });

        item.addEventListener("touchcancel", (e) => {
            if (touchStartElement) {
                touchStartElement.style.opacity = "1";
                touchStartElement = null;
            }

            // Eliminar elemento fantasma
            if (ghostElement) {
                ghostElement.remove();
                ghostElement = null;
            }

            // Limpiar resaltado de todas las zonas
            document.querySelectorAll(".drop-zone").forEach((z) => {
                z.style.backgroundColor = "";
            });
        });
    });

    // Eventos de drop para las zonas (desktop)
    document.querySelectorAll(".drop-zone").forEach((zone) => {
        zone.addEventListener("dragover", (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = "move";
            zone.style.backgroundColor = "#e0f2fe";
        });

        zone.addEventListener("dragleave", (e) => {
            zone.style.backgroundColor = "";
        });

        zone.addEventListener("drop", (e) => {
            e.preventDefault();
            zone.style.backgroundColor = "";

            if (draggedElement) {
                // Remover placeholder si existe
                const placeholder = zone.querySelector(".text-gray-400");
                if (placeholder) placeholder.remove();

                // Agregar elemento a la nueva zona
                zone.appendChild(draggedElement);

                // Actualizar totales
                actualizarTotalesSalidas();
            }
        });
    });
}

/* ===================== Actualizar totales de salidas ===================== */
function actualizarTotalesSalidas() {
    document.querySelectorAll(".drop-zone").forEach((zona) => {
        if (zona.dataset.salidaId === "null") return; // Ignorar zona de disponibles

        const paquetes = zona.querySelectorAll(".paquete-item");
        let totalKg = 0;

        paquetes.forEach((paquete) => {
            const peso = parseFloat(paquete.dataset.peso) || 0;
            totalKg += peso;
        });

        const badge = document.querySelector(
            `.peso-total-salida[data-salida-id="${zona.dataset.salidaId}"]`
        );
        if (badge) {
            badge.textContent = `${totalKg.toFixed(2)} kg`;
        }
    });
}

/* ===================== Guardar asignaciones de paquetes ===================== */
async function guardarAsignaciones() {
    const asignaciones = [];

    // Recopilar todas las asignaciones
    document.querySelectorAll(".drop-zone").forEach((zona) => {
        const salidaId = zona.dataset.salidaId;
        const paquetes = zona.querySelectorAll(".paquete-item");

        paquetes.forEach((paquete) => {
            const paqueteId = parseInt(paquete.dataset.paqueteId);
            asignaciones.push({
                paquete_id: paqueteId,
                salida_id: salidaId === "null" ? null : parseInt(salidaId),
            });
        });
    });

    try {
        Swal.fire({
            title: "Guardando asignaciones...",
            text: "Por favor espera",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await fetch(
            window.AppGestionSalidas.routes.guardarAsignacionesPaquetes,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": window.AppGestionSalidas.csrf,
                },
                body: JSON.stringify({ asignaciones }),
            }
        );

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: "success",
                title: "✅ Asignaciones Guardadas",
                text: "Los paquetes han sido asignados correctamente a las salidas",
                timer: 2000,
            });
        } else {
            Swal.fire(
                "⚠️",
                data.message || "No se pudieron guardar las asignaciones",
                "warning"
            );
        }
    } catch (error) {
        console.error("Error:", error);
        Swal.fire("❌", "Error al guardar las asignaciones", "error");
    }
}

/* ===================== Mostrar dibujo del paquete ===================== */
function mostrarDibujo(paqueteId) {
    const modal = document.getElementById("modal-dibujo");
    const canvasContainer = document.getElementById("canvas-dibujo");

    if (!modal || !canvasContainer) {
        console.error("Modal o canvas no encontrado");
        return;
    }

    const paquete = window.paquetes.find((p) => p.id == paqueteId);

    if (!paquete) {
        console.warn("No se encontró el paquete");
        return;
    }

    // Obtener elementos del paquete (igual que en PaquetesTable)
    const elementos = [];
    if (paquete.etiquetas && paquete.etiquetas.length > 0) {
        paquete.etiquetas.forEach((etiqueta) => {
            if (etiqueta.elementos && etiqueta.elementos.length > 0) {
                etiqueta.elementos.forEach((elemento) => {
                    elementos.push({
                        id: elemento.id,
                        dimensiones: elemento.dimensiones,
                    });
                });
            }
        });
    }

    if (elementos.length === 0) {
        Swal.fire(
            "⚠️",
            "Este paquete no tiene elementos para dibujar.",
            "warning"
        );
        return;
    }

    // Limpiar contenedor
    canvasContainer.innerHTML = "";

    // Crear contenedores para cada elemento
    elementos.forEach((elemento) => {
        const elementoDiv = document.createElement("div");
        elementoDiv.id = `elemento-${elemento.id}`;
        elementoDiv.style.width = "100%";
        elementoDiv.style.height = "200px";
        elementoDiv.style.border = "1px solid #e5e7eb";
        elementoDiv.style.borderRadius = "4px";
        elementoDiv.style.background = "white";
        elementoDiv.style.position = "relative";
        elementoDiv.style.marginBottom = "10px";
        canvasContainer.appendChild(elementoDiv);
    });

    // Mostrar modal
    modal.classList.remove("hidden");

    // Dibujar elementos
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            elementos.forEach((elemento) => {
                if (typeof window.dibujarFiguraElemento === "function") {
                    window.dibujarFiguraElemento(
                        `elemento-${elemento.id}`,
                        elemento.dimensiones,
                        null
                    );
                } else {
                    console.error(
                        "❌ dibujarFiguraElemento no está disponible"
                    );
                }
            });
        });
    });
}

// Exportar función globalmente
window.mostrarDibujo = mostrarDibujo;

// Event listener para cerrar modal
document.addEventListener("DOMContentLoaded", function () {
    const cerrarModal = document.getElementById("cerrar-modal");
    const modal = document.getElementById("modal-dibujo");

    if (cerrarModal && modal) {
        cerrarModal.addEventListener("click", function () {
            modal.classList.add("hidden");
        });

        // Cerrar al hacer clic fuera del modal
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                modal.classList.add("hidden");
            }
        });
    }
});

/* ===================== Eliminar salida ===================== */
async function eliminarSalida(salidaId) {
    const result = await Swal.fire({
        title: "¿Eliminar salida?",
        text: "Los paquetes asignados volverán a estar disponibles. Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc2626",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        Swal.fire({
            title: "Eliminando salida...",
            text: "Por favor espera",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await fetch(`/salidas-ferralla/${salidaId}`, {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": window.AppGestionSalidas.csrf,
            },
        });

        // Primero leer como texto para poder mostrarlo en caso de error
        const responseText = await response.text();

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error("Error al parsear JSON:", parseError);
            console.error("Respuesta del servidor:", responseText);
            Swal.fire(
                "Error",
                "Error al procesar la respuesta del servidor. Revisa la consola para más detalles.",
                "error"
            );
            return;
        }

        if (response.ok && data.success) {
            // Obtener todos los paquetes de esta salida
            const zonaSalida = document.querySelector(
                `.drop-zone[data-salida-id="${salidaId}"]`
            );
            const paquetes = zonaSalida
                ? zonaSalida.querySelectorAll(".paquete-item")
                : [];

            // Mover los paquetes a la zona de disponibles
            const zonaDisponibles = document.querySelector(
                '.drop-zone[data-salida-id="null"]'
            );
            if (zonaDisponibles && paquetes.length > 0) {
                paquetes.forEach((paquete) => {
                    zonaDisponibles.appendChild(paquete);
                });
            }

            // Eliminar el contenedor completo de la salida (el div padre con bg-blue-50)
            const contenedorSalida = zonaSalida
                ? zonaSalida.closest(".bg-blue-50")
                : null;
            if (contenedorSalida) {
                contenedorSalida.remove();
            }

            // Mostrar mensaje de éxito
            Swal.fire({
                icon: "success",
                title: "Salida eliminada",
                text: "La salida ha sido eliminada correctamente",
                timer: 2000,
                showConfirmButton: false,
            });
        } else {
            Swal.fire(
                "Error",
                data.message || "No se pudo eliminar la salida",
                "error"
            );
        }
    } catch (error) {
        console.error("Error completo:", error);
        Swal.fire(
            "Error",
            "Error al eliminar la salida: " + error.message,
            "error"
        );
    }
}

/* ===================== Toggle filtro de paquetes ===================== */
/**
 * Cambia entre mostrar todos los paquetes pendientes o solo los de la obra/cliente
 * SIN RECARGAR LA PÁGINA
 */
function toggleFiltroPaquetes() {
    try {
        console.log("🔄 Toggle iniciado...");

        // Obtener el estado actual del toggle desde window.AppGestionSalidas
        const mostrandoTodos = window.AppGestionSalidas.mostrarTodosPaquetes;

        // Cambiar el estado
        window.AppGestionSalidas.mostrarTodosPaquetes = !mostrandoTodos;

        // Seleccionar el conjunto de paquetes correcto
        const paquetesAMostrar = window.AppGestionSalidas.mostrarTodosPaquetes
            ? window.paquetesTodos
            : window.paquetesFiltrados;

        console.log(`🔄 Cambiando a ${window.AppGestionSalidas.mostrarTodosPaquetes ? 'TODOS' : 'FILTRADOS'} (${paquetesAMostrar ? paquetesAMostrar.length : 0} paquetes)`);

        if (!paquetesAMostrar || paquetesAMostrar.length === 0) {
            console.error("❌ No hay paquetes para mostrar");
            return;
        }

        // Renderizar los paquetes en el DOM
        renderizarPaquetesDisponibles(paquetesAMostrar);

        // DESPUÉS de renderizar, reinicializar filtros y limpiarlos
        resetearFiltros();

        // Actualizar el botón del toggle
        actualizarBotonToggle();

        // Actualizar el texto explicativo
        actualizarTextoExplicativo();

        // Re-inicializar el drag and drop
        inicializarDragAndDrop();

        console.log("✅ Toggle completado");
    } catch (error) {
        console.error("❌ Error en toggleFiltroPaquetes:", error);
    }
}

/**
 * Resetea los filtros después de cambiar el toggle
 */
function resetearFiltros() {
    try {
        console.log("🔄 Reseteando filtros...");

        const selectObra = document.getElementById("filtro-obra");
        const selectCliente = document.getElementById("filtro-cliente");
        const selectPlanilla = document.getElementById("filtro-planilla");

        if (selectObra) selectObra.value = "";
        if (selectCliente) selectCliente.value = "";
        if (selectPlanilla) selectPlanilla.value = "";

        // Esperar a que el DOM se actualice completamente
        setTimeout(() => {
            // Reinicializar los selectores con las nuevas opciones
            inicializarFiltros();
        }, 100);
    } catch (error) {
        console.error("❌ Error en resetearFiltros:", error);
    }
}

/**
 * Renderiza los paquetes disponibles en el contenedor
 */
function renderizarPaquetesDisponibles(paquetes) {
    const container = document.querySelector(
        '.paquetes-zona[data-salida-id="null"]'
    );

    if (!container) {
        console.error("No se encontró el contenedor de paquetes disponibles");
        return;
    }

    // Limpiar el contenedor
    container.innerHTML = "";

    // Crear elementos para cada paquete
    paquetes.forEach((paquete) => {
        const paqueteDiv = document.createElement("div");
        paqueteDiv.className =
            "paquete-item bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow";
        paqueteDiv.draggable = true;
        paqueteDiv.dataset.paqueteId = paquete.id;
        paqueteDiv.dataset.peso = paquete.peso;
        paqueteDiv.dataset.obra = paquete.obra || "";
        paqueteDiv.dataset.cliente = paquete.cliente || "";
        paqueteDiv.dataset.planilla = paquete.planilla_codigo || "";
        paqueteDiv.dataset.planillaId = paquete.planilla_id || "";

        paqueteDiv.innerHTML = `
            <div class="flex items-center justify-between text-xs">
                <span class="font-medium">📦 ${paquete.codigo}</span>
                <button onclick="mostrarDibujo(${
                    paquete.id
                }); event.stopPropagation();"
                    class="text-blue-500 hover:underline text-xs">
                    👁️ Ver
                </button>
            </div>
            <div class="flex items-center justify-between text-xs mt-1">
                <span class="text-gray-500">${
                    paquete.planilla_codigo || "N/A"
                }</span>
                <span class="text-gray-600">${parseFloat(paquete.peso).toFixed(
                    2
                )} kg</span>
            </div>
            <div class="text-xs text-gray-500 mt-1 border-t border-gray-200 pt-1">
                <div class="truncate" title="${paquete.obra}">🏗️ ${
            paquete.obra
        }</div>
                <div class="truncate" title="${paquete.cliente}">👤 ${
            paquete.cliente
        }</div>
            </div>
        `;

        container.appendChild(paqueteDiv);
    });

    console.log(`✅ Renderizados ${paquetes.length} paquetes`);
}

/**
 * Actualiza el botón de toggle con el color y texto correcto
 */
function actualizarBotonToggle() {
    const boton = document.getElementById("btn-toggle-paquetes");

    if (!boton) return;

    const mostrandoTodos = window.AppGestionSalidas.mostrarTodosPaquetes;

    if (mostrandoTodos) {
        // Mostrando todos - botón naranja para volver a filtrar solo estas planillas
        boton.className =
            "text-xs px-3 py-1.5 rounded-md transition-colors shadow-sm font-medium bg-orange-500 hover:bg-orange-600 text-white";
        boton.title =
            "Mostrar solo paquetes de las planillas que estás gestionando";
        boton.innerHTML = `
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Solo estas planillas
            </span>
        `;
    } else {
        // Mostrando filtrado - botón azul para incluir otros paquetes
        boton.className =
            "text-xs px-3 py-1.5 rounded-md transition-colors shadow-sm font-medium bg-blue-500 hover:bg-blue-600 text-white";
        boton.title =
            "Mostrar también paquetes pendientes de otras planillas (para mezclar salidas)";
        boton.innerHTML = `
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Incluir otros paquetes
            </span>
        `;
    }
}

/**
 * Actualiza el texto explicativo debajo del título
 */
function actualizarTextoExplicativo() {
    const contenedorInfo = document
        .querySelector('.paquetes-zona[data-salida-id="null"]')
        .closest(".bg-gray-50")
        .querySelector(".bg-blue-50");

    if (!contenedorInfo) return;

    const parrafo = contenedorInfo.querySelector("p");
    if (!parrafo) return;

    const mostrandoTodos = window.AppGestionSalidas.mostrarTodosPaquetes;

    // Contar paquetes actuales
    const paquetesActuales = mostrandoTodos
        ? window.paquetesTodos
        : window.paquetesFiltrados;
    const cantidad = paquetesActuales ? paquetesActuales.length : 0;

    if (mostrandoTodos) {
        parrafo.innerHTML = `<strong>🌐 Mostrando:</strong> Todos los paquetes pendientes sin asignar (${cantidad} total)`;
    } else {
        parrafo.innerHTML = `<strong>📋 Mostrando:</strong> Filtrando (${cantidad} paquetes...)`;
    }
}

/* ===================== Inicializar filtros ===================== */
/**
 * Inicializa los selectores de filtrado con las opciones únicas
 */
function inicializarFiltros() {
    console.log("🔧 Iniciando inicializarFiltros()...");

    const selectObra = document.getElementById("filtro-obra");
    const selectCliente = document.getElementById("filtro-cliente");
    const selectPlanilla = document.getElementById("filtro-planilla");

    if (!selectObra || !selectCliente || !selectPlanilla) {
        console.warn("❌ Selectores de filtro no encontrados");
        return;
    }

    // Obtener paquetes desde el DOM (los que están visibles)
    const paquetesEnDOM = document.querySelectorAll(
        '.paquetes-zona[data-salida-id="null"] .paquete-item'
    );

    console.log(`📦 Paquetes encontrados en DOM: ${paquetesEnDOM.length}`);

    if (paquetesEnDOM.length === 0) {
        console.warn("⚠️ No hay paquetes disponibles en el DOM para inicializar filtros");
        return;
    }

    // Debug: mostrar primer paquete
    if (paquetesEnDOM.length > 0) {
        const primer = paquetesEnDOM[0];
        console.log("🔍 Primer paquete data:", {
            obra: primer.dataset.obra,
            cliente: primer.dataset.cliente,
            planilla: primer.dataset.planilla
        });
    }

    // Extraer valores únicos desde los atributos data-*
    const obrasSet = new Set();
    const clientesSet = new Set();
    const planillasSet = new Set();

    paquetesEnDOM.forEach((paquete) => {
        const obra = paquete.dataset.obra;
        const cliente = paquete.dataset.cliente;
        const planilla = paquete.dataset.planilla;

        if (obra && obra !== "N/A") obrasSet.add(obra);
        if (cliente && cliente !== "N/A") clientesSet.add(cliente);
        if (planilla && planilla !== "N/A") planillasSet.add(planilla);
    });

    // Convertir a arrays y ordenar
    const obras = [...obrasSet].sort();
    const clientes = [...clientesSet].sort();
    const planillas = [...planillasSet].sort();

    // Poblar select de obras
    selectObra.innerHTML = '<option value="">-- Todas las obras --</option>';
    obras.forEach((obra) => {
        const option = document.createElement("option");
        option.value = obra;
        option.textContent = obra;
        selectObra.appendChild(option);
    });

    // Poblar select de clientes
    selectCliente.innerHTML =
        '<option value="">-- Todos los clientes --</option>';
    clientes.forEach((cliente) => {
        const option = document.createElement("option");
        option.value = cliente;
        option.textContent = cliente;
        selectCliente.appendChild(option);
    });

    // Poblar select de planillas
    selectPlanilla.innerHTML =
        '<option value="">-- Todas las planillas --</option>';
    planillas.forEach((planilla) => {
        const option = document.createElement("option");
        option.value = planilla;
        option.textContent = planilla;
        selectPlanilla.appendChild(option);
    });

    console.log("✅ Filtros inicializados desde DOM:", {
        paquetes_totales: paquetesEnDOM.length,
        obras: obras.length,
        clientes: clientes.length,
        planillas: planillas.length,
    });
}

/* ===================== Aplicar filtros ===================== */
/**
 * Aplica los filtros seleccionados a los paquetes disponibles
 */
function aplicarFiltros() {
    const selectObra = document.getElementById("filtro-obra");
    const selectCliente = document.getElementById("filtro-cliente");
    const selectPlanilla = document.getElementById("filtro-planilla");

    if (!selectObra || !selectCliente || !selectPlanilla) {
        return;
    }

    const filtroObra = selectObra.value;
    const filtroCliente = selectCliente.value;
    const filtroPlanilla = selectPlanilla.value;

    // Obtener todos los paquetes del DOM
    const todosPaquetes = document.querySelectorAll(
        '.paquetes-zona[data-salida-id="null"] .paquete-item'
    );

    let paquetesVisibles = 0;
    let paquetesOcultos = 0;

    // Mostrar/ocultar paquetes según filtros
    todosPaquetes.forEach((paquete) => {
        let mostrar = true;

        // Filtro por obra
        if (filtroObra && paquete.dataset.obra !== filtroObra) {
            mostrar = false;
        }

        // Filtro por cliente
        if (filtroCliente && paquete.dataset.cliente !== filtroCliente) {
            mostrar = false;
        }

        // Filtro por planilla
        if (filtroPlanilla && paquete.dataset.planilla !== filtroPlanilla) {
            mostrar = false;
        }

        // Mostrar u ocultar el paquete
        if (mostrar) {
            paquete.style.display = "";
            paquetesVisibles++;
        } else {
            paquete.style.display = "none";
            paquetesOcultos++;
        }
    });

    console.log(
        `🔍 Filtros aplicados: ${paquetesVisibles} visibles, ${paquetesOcultos} ocultos de ${todosPaquetes.length} totales`
    );
}

/* ===================== Limpiar filtros ===================== */
/**
 * Limpia todos los filtros y muestra todos los paquetes disponibles
 */
function limpiarFiltros() {
    const selectObra = document.getElementById("filtro-obra");
    const selectCliente = document.getElementById("filtro-cliente");
    const selectPlanilla = document.getElementById("filtro-planilla");

    if (selectObra) selectObra.value = "";
    if (selectCliente) selectCliente.value = "";
    if (selectPlanilla) selectPlanilla.value = "";

    // Mostrar todos los paquetes
    const todosPaquetes = document.querySelectorAll(
        '.paquetes-zona[data-salida-id="null"] .paquete-item'
    );
    todosPaquetes.forEach((paquete) => {
        paquete.style.display = "";
    });

    console.log(
        `🔄 Filtros limpiados: ${todosPaquetes.length} paquetes visibles`
    );
}

/* ===================== Actualizar fecha de salida ===================== */
/**
 * Actualiza la fecha de salida de una salida específica
 */
async function actualizarFechaSalida(salidaId, nuevaFecha) {
    try {
        console.log(`📅 Actualizando fecha de salida ${salidaId} a ${nuevaFecha}`);

        // Verificar que tenemos la ruta configurada
        if (!window.AppGestionSalidas || !window.AppGestionSalidas.routes || !window.AppGestionSalidas.routes.actualizarFechaSalida) {
            console.error('❌ Ruta actualizarFechaSalida no configurada');
            Swal.fire({
                icon: 'error',
                title: 'Error de configuración',
                text: 'No se pudo encontrar la ruta para actualizar la fecha',
            });
            return;
        }

        const response = await fetch(window.AppGestionSalidas.routes.actualizarFechaSalida, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.AppGestionSalidas.csrf,
            },
            body: JSON.stringify({
                id: salidaId,
                fecha_salida: nuevaFecha,
            }),
        });

        // Leer la respuesta como texto primero para debug
        const responseText = await response.text();
        console.log('📝 Respuesta del servidor:', responseText);

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Error al parsear JSON:', parseError);
            console.error('Respuesta del servidor:', responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error del servidor',
                text: 'La respuesta del servidor no es válida',
            });
            return;
        }

        if (response.ok && data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Fecha actualizada',
                text: 'La fecha de salida se ha actualizado correctamente',
                timer: 1500,
                showConfirmButton: false,
            });
        } else {
            console.error('❌ Error en la respuesta:', data);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo actualizar la fecha de salida',
            });
        }
    } catch (error) {
        console.error('❌ Error completo al actualizar fecha de salida:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al actualizar la fecha de salida: ' + error.message,
        });
    }
}

/* ===================== Toggle resumen de planillas ===================== */
/**
 * Muestra u oculta el resumen de planillas
 */
function toggleResumenPlanillas() {
    const contenido = document.getElementById('contenido-resumen-planillas');
    const icono = document.getElementById('icono-toggle-planillas');

    if (!contenido || !icono) return;

    if (contenido.classList.contains('hidden')) {
        // Mostrar
        contenido.classList.remove('hidden');
        icono.style.transform = 'rotate(90deg)';
    } else {
        // Ocultar
        contenido.classList.add('hidden');
        icono.style.transform = 'rotate(0deg)';
    }
}

// Exportar funciones globalmente
window.eliminarSalida = eliminarSalida;
window.toggleFiltroPaquetes = toggleFiltroPaquetes;
window.aplicarFiltros = aplicarFiltros;
window.limpiarFiltros = limpiarFiltros;
window.inicializarFiltros = inicializarFiltros;
window.actualizarFechaSalida = actualizarFechaSalida;
window.toggleResumenPlanillas = toggleResumenPlanillas;
