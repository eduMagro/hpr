// ==============================================
// GESTIÓN DE SALIDAS - CREAR Y ASIGNAR PAQUETES
// ==============================================

document.addEventListener('DOMContentLoaded', function() {
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
});

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
