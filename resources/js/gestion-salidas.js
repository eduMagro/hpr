// ==============================================
// GESTIÓN DE SALIDAS - CREAR Y ASIGNAR PAQUETES
// ==============================================

function initGestionSalidas() {
    // Generar formularios para crear salidas
    const btnGenerarFormularios = document.getElementById('btn-generar-formularios');
    if (btnGenerarFormularios) {
        btnGenerarFormularios.addEventListener('click', generarFormulariosSalidas);
    }

    // Crear todas las salidas
    const btnCrearTodasSalidas = document.getElementById('btn-crear-todas-salidas');
    if (btnCrearTodasSalidas) {
        btnCrearTodasSalidas.addEventListener('click', crearTodasSalidas);
    }

    // Guardar asignaciones de paquetes
    const btnGuardarAsignaciones = document.getElementById('btn-guardar-asignaciones');
    if (btnGuardarAsignaciones) {
        btnGuardarAsignaciones.addEventListener('click', guardarAsignaciones);
    }

    // Inicializar drag and drop si hay salidas existentes
    if (document.querySelector('.paquete-item')) {
        inicializarDragAndDrop();
        actualizarTotalesSalidas();
    }
}

/* ===================== Generar formularios de salidas ===================== */
function generarFormulariosSalidas() {
    const numSalidas = parseInt(document.getElementById('num-salidas').value);

    if (numSalidas < 1 || numSalidas > 10) {
        Swal.fire('⚠️', 'El número de salidas debe estar entre 1 y 10', 'warning');
        return;
    }

    const container = document.getElementById('formularios-salidas');
    container.innerHTML = '';

    const empresas = window.AppGestionSalidas.empresas;
    const camiones = window.AppGestionSalidas.camiones;

    for (let i = 1; i <= numSalidas; i++) {
        const formulario = crearFormularioSalida(i, empresas, camiones);
        container.appendChild(formulario);
    }

    document.getElementById('btn-crear-container').classList.remove('hidden');
}

/* ===================== Crear formulario individual de salida ===================== */
function crearFormularioSalida(numero, empresas, camiones) {
    const div = document.createElement('div');
    div.className = 'bg-gray-50 border border-gray-300 rounded-lg p-4';
    div.dataset.salidaIndex = numero;

    // Obtener fecha por defecto de la primera planilla si existe
    const fechaPorDefecto = new Date().toISOString().split('T')[0];

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
                    ${empresas.map(empresa => `
                        <option value="${empresa.id}">${empresa.nombre}</option>
                    `).join('')}
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Camión (opcional)</label>
                <select class="salida-camion w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sin asignar (se asignará después)</option>
                    ${camiones.map(camion => `
                        <option value="${camion.id}" data-empresa="${camion.empresa_id}">
                            ${camion.modelo} - ${camion.matricula || 'Sin matrícula'}
                        </option>
                    `).join('')}
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
    const selectEmpresa = div.querySelector('.salida-empresa');
    const selectCamion = div.querySelector('.salida-camion');

    selectEmpresa.addEventListener('change', function() {
        const empresaId = this.value;
        const opciones = selectCamion.querySelectorAll('option');

        opciones.forEach(opcion => {
            if (opcion.value === '') {
                opcion.style.display = 'block';
                return;
            }

            if (opcion.dataset.empresa === empresaId) {
                opcion.style.display = 'block';
            } else {
                opcion.style.display = 'none';
                if (opcion.selected) {
                    selectCamion.value = '';
                }
            }
        });
    });

    return div;
}

/* ===================== Crear todas las salidas ===================== */
async function crearTodasSalidas() {
    const formularios = document.querySelectorAll('[data-salida-index]');
    const salidas = [];

    // Validar y recopilar datos
    for (const form of formularios) {
        const fecha = form.querySelector('.salida-fecha').value;
        const empresaId = form.querySelector('.salida-empresa').value;
        const camionId = form.querySelector('.salida-camion').value;
        const codigoSage = form.querySelector('.salida-codigo-sage').value;

        if (!fecha) {
            Swal.fire('⚠️', 'Por favor, completa la fecha de salida en todas las salidas', 'warning');
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
            title: 'Creando salidas...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch(window.AppGestionSalidas.routes.crearSalidasVacias, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.AppGestionSalidas.csrf,
            },
            body: JSON.stringify({
                salidas: salidas,
                planillas_ids: window.AppGestionSalidas.planillasIds,
            }),
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: '✅ Salidas Creadas',
                text: `Se han creado ${data.salidas_creadas} salidas correctamente`,
                timer: 2000,
            });

            // Recargar la página para mostrar la gestión de paquetes
            window.location.href = window.AppGestionSalidas.routes.recargarVista;
        } else {
            Swal.fire('❌', data.message || 'Error al crear las salidas', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('❌', 'Error al crear las salidas', 'error');
    }
}

/* ===================== Inicializar Drag and Drop ===================== */
function inicializarDragAndDrop() {
    let draggedElement = null;

    // Eventos de drag para los paquetes
    document.querySelectorAll('.paquete-item').forEach((item) => {
        item.addEventListener('dragstart', (e) => {
            draggedElement = item;
            item.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', (e) => {
            item.style.opacity = '1';
            draggedElement = null;
        });
    });

    // Eventos de drop para las zonas
    document.querySelectorAll('.drop-zone').forEach((zone) => {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            zone.style.backgroundColor = '#e0f2fe';
        });

        zone.addEventListener('dragleave', (e) => {
            zone.style.backgroundColor = '';
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.style.backgroundColor = '';

            if (draggedElement) {
                // Remover placeholder si existe
                const placeholder = zone.querySelector('.text-gray-400');
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
    document.querySelectorAll('.drop-zone').forEach((zona) => {
        if (zona.dataset.salidaId === 'null') return; // Ignorar zona de disponibles

        const paquetes = zona.querySelectorAll('.paquete-item');
        let totalKg = 0;

        paquetes.forEach((paquete) => {
            const peso = parseFloat(paquete.dataset.peso) || 0;
            totalKg += peso;
        });

        const badge = document.querySelector(`.peso-total-salida[data-salida-id="${zona.dataset.salidaId}"]`);
        if (badge) {
            badge.textContent = `${totalKg.toFixed(2)} kg`;
        }
    });
}

/* ===================== Guardar asignaciones de paquetes ===================== */
async function guardarAsignaciones() {
    const asignaciones = [];

    // Recopilar todas las asignaciones
    document.querySelectorAll('.drop-zone').forEach((zona) => {
        const salidaId = zona.dataset.salidaId;
        const paquetes = zona.querySelectorAll('.paquete-item');

        paquetes.forEach((paquete) => {
            const paqueteId = parseInt(paquete.dataset.paqueteId);
            asignaciones.push({
                paquete_id: paqueteId,
                salida_id: salidaId === 'null' ? null : parseInt(salidaId),
            });
        });
    });

    try {
        Swal.fire({
            title: 'Guardando asignaciones...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch(window.AppGestionSalidas.routes.guardarAsignacionesPaquetes, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.AppGestionSalidas.csrf,
            },
            body: JSON.stringify({ asignaciones }),
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: '✅ Asignaciones Guardadas',
                text: 'Los paquetes han sido asignados correctamente a las salidas',
                timer: 2000,
            });
        } else {
            Swal.fire('⚠️', data.message || 'No se pudieron guardar las asignaciones', 'warning');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('❌', 'Error al guardar las asignaciones', 'error');
    }
}

/* ===================== Mostrar dibujo del paquete ===================== */
function mostrarDibujo(paqueteId) {
    const modal = document.getElementById('modal-dibujo');
    const canvasContainer = document.getElementById('canvas-dibujo');

    if (!modal || !canvasContainer) {
        console.error('Modal o canvas no encontrado');
        return;
    }

    const paquete = window.paquetes.find(p => p.id == paqueteId);

    if (!paquete) {
        console.warn('No se encontró el paquete');
        return;
    }

    // Obtener elementos del paquete (igual que en PaquetesTable)
    const elementos = [];
    if (paquete.etiquetas && paquete.etiquetas.length > 0) {
        paquete.etiquetas.forEach(etiqueta => {
            if (etiqueta.elementos && etiqueta.elementos.length > 0) {
                etiqueta.elementos.forEach(elemento => {
                    elementos.push({
                        id: elemento.id,
                        dimensiones: elemento.dimensiones
                    });
                });
            }
        });
    }

    if (elementos.length === 0) {
        Swal.fire('⚠️', 'Este paquete no tiene elementos para dibujar.', 'warning');
        return;
    }

    // Limpiar contenedor
    canvasContainer.innerHTML = '';

    // Crear contenedores para cada elemento
    elementos.forEach((elemento) => {
        const elementoDiv = document.createElement('div');
        elementoDiv.id = `elemento-${elemento.id}`;
        elementoDiv.style.width = '100%';
        elementoDiv.style.height = '200px';
        elementoDiv.style.border = '1px solid #e5e7eb';
        elementoDiv.style.borderRadius = '4px';
        elementoDiv.style.background = 'white';
        elementoDiv.style.position = 'relative';
        elementoDiv.style.marginBottom = '10px';
        canvasContainer.appendChild(elementoDiv);
    });

    // Mostrar modal
    modal.classList.remove('hidden');

    // Dibujar elementos
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            elementos.forEach((elemento) => {
                if (typeof window.dibujarFiguraElemento === 'function') {
                    window.dibujarFiguraElemento(`elemento-${elemento.id}`, elemento.dimensiones, null);
                } else {
                    console.error('❌ dibujarFiguraElemento no está disponible');
                }
            });
        });
    });
}

// Exportar función globalmente
window.mostrarDibujo = mostrarDibujo;

// Event listener para cerrar modal
function initGestionSalidasModal() {
    const cerrarModal = document.getElementById('cerrar-modal');
    const modal = document.getElementById('modal-dibujo');

    if (cerrarModal && modal) {
        cerrarModal.addEventListener('click', function() {
            modal.classList.add('hidden');
        });

        // Cerrar al hacer clic fuera del modal
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }
}

function initGestionSalidasPageJS() {
    initGestionSalidas();
    initGestionSalidasModal();
}

// Inicialización compatible con Livewire Navigate
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initGestionSalidasPageJS);
} else {
    initGestionSalidasPageJS();
}
document.addEventListener("livewire:navigated", initGestionSalidasPageJS);

/* ===================== Eliminar salida ===================== */
async function eliminarSalida(salidaId) {
    const result = await Swal.fire({
        title: '¿Eliminar salida?',
        text: 'Los paquetes asignados volverán a estar disponibles. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        Swal.fire({
            title: 'Eliminando salida...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch(`/salidas-ferralla/${salidaId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.AppGestionSalidas.csrf,
            }
        });

        // Primero leer como texto para poder mostrarlo en caso de error
        const responseText = await response.text();

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Error al parsear JSON:', parseError);
            console.error('Respuesta del servidor:', responseText);
            Swal.fire('Error', 'Error al procesar la respuesta del servidor. Revisa la consola para más detalles.', 'error');
            return;
        }

        if (response.ok && data.success) {
            // Obtener todos los paquetes de esta salida
            const zonaSalida = document.querySelector(`.drop-zone[data-salida-id="${salidaId}"]`);
            const paquetes = zonaSalida ? zonaSalida.querySelectorAll('.paquete-item') : [];

            // Mover los paquetes a la zona de disponibles
            const zonaDisponibles = document.querySelector('.drop-zone[data-salida-id="null"]');
            if (zonaDisponibles && paquetes.length > 0) {
                paquetes.forEach(paquete => {
                    zonaDisponibles.appendChild(paquete);
                });
            }

            // Eliminar el contenedor completo de la salida (el div padre con bg-blue-50)
            const contenedorSalida = zonaSalida ? zonaSalida.closest('.bg-blue-50') : null;
            if (contenedorSalida) {
                contenedorSalida.remove();
            }

            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: 'Salida eliminada',
                text: 'La salida ha sido eliminada correctamente',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Error', data.message || 'No se pudo eliminar la salida', 'error');
        }
    } catch (error) {
        console.error('Error completo:', error);
        Swal.fire('Error', 'Error al eliminar la salida: ' + error.message, 'error');
    }
}

/* ===================== Toggle filtro de paquetes ===================== */
/**
 * Cambia entre mostrar todos los paquetes pendientes o solo los de la obra/cliente
 * SIN RECARGAR LA PÁGINA
 */
function toggleFiltroPaquetes() {
    // Obtener el estado actual del toggle desde window.AppGestionSalidas
    const mostrandoTodos = window.AppGestionSalidas.mostrarTodosPaquetes;

    // Cambiar el estado
    window.AppGestionSalidas.mostrarTodosPaquetes = !mostrandoTodos;

    // Seleccionar el conjunto de paquetes correcto
    const paquetesAMostrar = window.AppGestionSalidas.mostrarTodosPaquetes
        ? window.paquetesTodos
        : window.paquetesFiltrados;

    // Renderizar los paquetes en el DOM
    renderizarPaquetesDisponibles(paquetesAMostrar);

    // Actualizar el botón del toggle
    actualizarBotonToggle();

    // Actualizar el texto explicativo
    actualizarTextoExplicativo();

    // Re-inicializar el drag and drop
    inicializarDragAndDrop();
}

/**
 * Renderiza los paquetes disponibles en el contenedor
 */
function renderizarPaquetesDisponibles(paquetes) {
    const container = document.querySelector('.paquetes-zona[data-salida-id="null"]');

    if (!container) {
        console.error('No se encontró el contenedor de paquetes disponibles');
        return;
    }

    // Limpiar el contenedor
    container.innerHTML = '';

    // Crear elementos para cada paquete
    paquetes.forEach(paquete => {
        const paqueteDiv = document.createElement('div');
        paqueteDiv.className = 'paquete-item bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow';
        paqueteDiv.draggable = true;
        paqueteDiv.dataset.paqueteId = paquete.id;
        paqueteDiv.dataset.peso = paquete.peso;

        paqueteDiv.innerHTML = `
            <div class="flex items-center justify-between text-xs">
                <span class="font-medium">📦 ${paquete.codigo}</span>
                <button onclick="mostrarDibujo(${paquete.id}); event.stopPropagation();"
                    class="text-blue-500 hover:underline text-xs">
                    👁️ Ver
                </button>
            </div>
            <div class="flex items-center justify-between text-xs mt-1">
                <span class="text-gray-500">${paquete.planilla_codigo || 'N/A'}</span>
                <span class="text-gray-600">${parseFloat(paquete.peso).toFixed(2)} kg</span>
            </div>
            <div class="text-xs text-gray-500 mt-1 border-t border-gray-200 pt-1">
                <div class="truncate" title="${paquete.obra}">🏗️ ${paquete.obra}</div>
                <div class="truncate" title="${paquete.cliente}">👤 ${paquete.cliente}</div>
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
    const boton = document.getElementById('btn-toggle-paquetes');

    if (!boton) return;

    const mostrandoTodos = window.AppGestionSalidas.mostrarTodosPaquetes;

    if (mostrandoTodos) {
        // Mostrando todos - botón naranja, texto para cambiar a filtrado
        boton.className = 'text-xs px-3 py-1 rounded-md transition-colors bg-orange-500 hover:bg-orange-600 text-white';
        boton.innerHTML = '📍 Ver solo obra/cliente';
    } else {
        // Mostrando filtrado - botón azul, texto para cambiar a todos
        boton.className = 'text-xs px-3 py-1 rounded-md transition-colors bg-blue-500 hover:bg-blue-600 text-white';
        boton.innerHTML = '🌐 Ver todos pendientes';
    }
}

/**
 * Actualiza el texto explicativo debajo del título
 */
function actualizarTextoExplicativo() {
    const parrafo = document.querySelector('.paquetes-zona[data-salida-id="null"]').closest('.bg-gray-50').querySelector('p.text-xs');

    if (!parrafo) return;

    const mostrandoTodos = window.AppGestionSalidas.mostrarTodosPaquetes;

    if (mostrandoTodos) {
        parrafo.innerHTML = 'Mostrando <strong>TODOS</strong> los paquetes pendientes sin filtro';
    } else {
        parrafo.innerHTML = 'Mostrando solo paquetes de <strong>esta obra/cliente</strong>';
    }
}

// Exportar funciones globalmente
window.eliminarSalida = eliminarSalida;
window.toggleFiltroPaquetes = toggleFiltroPaquetes;
