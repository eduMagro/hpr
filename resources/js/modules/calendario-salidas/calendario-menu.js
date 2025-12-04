// calendario-menu.js
import { openActionsMenu, closeMenu } from "./menuContextual.js";
/* ===================== Helpers de fecha (locales) ===================== */

/**
 * Convierte fecha ISO (YYYY-MM-DD) para input type="date"
 * @param {string} str - Fecha en formato ISO
 * @returns {string} - Fecha en formato YYYY-MM-DD para input date
 */
function toISO(str) {
    if (!str || typeof str !== "string") return "";

    // Si ya está en formato ISO (YYYY-MM-DD), devolverla tal como está
    const isoMatch = str.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);
    if (isoMatch) {
        const year = isoMatch[1];
        const month = isoMatch[2].padStart(2, "0");
        const day = isoMatch[3].padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    return str; // Si no coincide, devolver tal como está
}

/* ===================== Animaciones SweetAlert (una vez) ===================== */
/* FIX: la animación incluye translate(-50%,-50%) para no romper el centrado */
(function injectSwalAnimations() {
    if (document.getElementById("swal-anims")) return;
    const style = document.createElement("style");
    style.id = "swal-anims";
    style.textContent = `
  /* Animación solo con scale; el centrado lo hacemos con left/top */
  @keyframes swalFadeInZoom {
    0%   { opacity: 0; transform: scale(.95); }
    100% { opacity: 1; transform: scale(1); }
  }
  @keyframes swalFadeOut {
    0%   { opacity: 1; transform: scale(1); }
    100% { opacity: 0; transform: scale(.98); }
  }
  .swal-fade-in-zoom { animation: swalFadeInZoom .18s ease-out both; }
  .swal-fade-out     { animation: swalFadeOut   .12s ease-in  both; }

  /* IMPORTANTE: escalar desde el centro para que no “camine” */
  .swal2-popup { 
    will-change: transform, opacity; 
    backface-visibility: hidden; 
    transform-origin: center center;
  }

  @keyframes swalRowIn { to { opacity: 1; transform: none; } }
  
  /* Estilos para fines de semana en input type="date" */
  input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
  }
  
  /* Estilo personalizado para inputs de fecha en fines de semana */
  .weekend-date {
    background-color: rgba(239, 68, 68, 0.1) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
    color: #dc2626 !important;
  }
  
  .weekend-date:focus {
    background-color: rgba(239, 68, 68, 0.15) !important;
    border-color: rgba(239, 68, 68, 0.5) !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
  }
  
  /* Estilos para celdas de fin de semana en el calendario */
  .fc-day-sat,
  .fc-day-sun {
    background-color: rgba(239, 68, 68, 0.05) !important;
  }
  
  /* Estilos para el encabezado de días de fin de semana */
  .fc-col-header-cell.fc-day-sat,
  .fc-col-header-cell.fc-day-sun {
    background-color: rgba(239, 68, 68, 0.1) !important;
    color: #dc2626 !important;
  }
  
  /* Para vista de mes - celdas de fin de semana */
  .fc-daygrid-day.fc-day-sat,
  .fc-daygrid-day.fc-day-sun {
    background-color: rgba(239, 68, 68, 0.05) !important;
  }
  
  /* Para vista de semana - columnas de fin de semana */
  .fc-timegrid-col.fc-day-sat,
  .fc-timegrid-col.fc-day-sun {
    background-color: rgba(239, 68, 68, 0.05) !important;
  }
  
  /* Números de día en fin de semana */
  .fc-daygrid-day.fc-day-sat .fc-daygrid-day-number,
  .fc-daygrid-day.fc-day-sun .fc-daygrid-day-number {
    color: #dc2626 !important;
    font-weight: 600 !important;
  }
  `;
    document.head.appendChild(style);
})();

/* ===================== Util: extraer IDs planillas ===================== */
function extraerPlanillasIds(event) {
    const p = event.extendedProps || {};
    if (Array.isArray(p.planillas_ids) && p.planillas_ids.length)
        return p.planillas_ids;
    const m = (event.id || "").match(/planilla-(\d+)/);
    return m ? [Number(m[1])] : [];
}

/* ===================== Gestionar paquetes de una salida ===================== */
export async function gestionarPaquetesSalida(salidaId, calendar) {
    try {
        closeMenu();
    } catch (_) {}

    if (!salidaId) {
        return Swal.fire("⚠️", "ID de salida inválido.", "warning");
    }

    try {
        // Obtener información de la salida y sus paquetes
        const infoRes = await fetch(
            `${window.AppSalidas?.routes?.informacionPaquetesSalida}?salida_id=${salidaId}`,
            {
                headers: { Accept: "application/json" },
            }
        );

        if (!infoRes.ok) {
            throw new Error("Error al cargar información de la salida");
        }

        const { salida, paquetesAsignados, paquetesDisponibles, paquetesTodos, filtros } = await infoRes.json();

        // Construir y mostrar interfaz
        mostrarInterfazGestionPaquetesSalida(
            salida,
            paquetesAsignados,
            paquetesDisponibles,
            paquetesTodos || [],
            filtros || { obras: [], planillas: [], obrasRelacionadas: [] },
            calendar
        );
    } catch (err) {
        console.error(err);
        Swal.fire(
            "❌",
            "Error al cargar la información de la salida",
            "error"
        );
    }
}

/* ===================== Mostrar interfaz gestión paquetes salida ===================== */
function mostrarInterfazGestionPaquetesSalida(
    salida,
    paquetesAsignados,
    paquetesDisponibles,
    paquetesTodos,
    filtros,
    calendar
) {
    // Guardar datos globalmente para los filtros
    window._gestionPaquetesData = {
        salida,
        paquetesAsignados,
        paquetesDisponibles,
        paquetesTodos,
        filtros,
        mostrarTodos: false,
    };

    const html = construirInterfazGestionPaquetesSalida(
        salida,
        paquetesAsignados,
        paquetesDisponibles,
        filtros
    );

    Swal.fire({
        title: `📦 Gestionar Paquetes - Salida ${salida.codigo_salida || salida.id}`,
        html,
        width: Math.min(window.innerWidth * 0.95, 1200),
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: "💾 Guardar Cambios",
        cancelButtonText: "Cancelar",
        focusConfirm: false,
        customClass: {
            popup: "w-full max-w-screen-xl",
        },
        didOpen: () => {
            inicializarDragAndDropSalida();
            inicializarFiltrosModal(filtros);
            // Inicializar navegación por teclado
            inyectarEstilosModalKeyboard();
            setTimeout(() => {
                inicializarNavegacionTecladoModal();
            }, 100);
        },
        willClose: () => {
            // Limpiar navegación por teclado
            if (modalKeyboardNav.cleanup) {
                modalKeyboardNav.cleanup();
            }
            // Limpiar indicador
            const indicator = document.getElementById('modal-keyboard-indicator');
            if (indicator) indicator.remove();
        },
        preConfirm: () => {
            return recolectarPaquetesSalida();
        },
    }).then(async (result) => {
        if (result.isConfirmed && result.value) {
            await guardarPaquetesSalida(salida.id, result.value, calendar);
        }
    });
}

/* ===================== Construir HTML interfaz gestión paquetes salida ===================== */
function construirInterfazGestionPaquetesSalida(
    salida,
    paquetesAsignados,
    paquetesDisponibles,
    filtros
) {
    // Calcular totales de la salida
    const totalKgAsignados = paquetesAsignados.reduce(
        (sum, p) => sum + (parseFloat(p.peso) || 0),
        0
    );

    // Construir información de obras y clientes
    let obrasClientesInfo = "";
    if (salida.salida_clientes && salida.salida_clientes.length > 0) {
        obrasClientesInfo = `<div class="col-span-2"><strong>Obras/Clientes:</strong><br>`;
        salida.salida_clientes.forEach(sc => {
            const obraNombre = sc.obra?.obra || "Obra desconocida";
            const obraCodigo = sc.obra?.cod_obra ? `(${sc.obra.cod_obra})` : "";
            const clienteNombre = sc.cliente?.empresa || sc.obra?.cliente?.empresa || "";
            obrasClientesInfo += `<span class="text-xs">• ${obraNombre} ${obraCodigo}`;
            if (clienteNombre) obrasClientesInfo += ` - ${clienteNombre}`;
            obrasClientesInfo += `</span><br>`;
        });
        obrasClientesInfo += `</div>`;
    }

    // Información de la salida
    const infoSalida = `
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><strong>Código:</strong> ${salida.codigo_salida || "N/A"}</div>
                <div><strong>Código SAGE:</strong> ${salida.codigo_sage || "Sin asignar"}</div>
                <div><strong>Fecha salida:</strong> ${new Date(salida.fecha_salida).toLocaleString("es-ES")}</div>
                <div><strong>Estado:</strong> ${salida.estado || "pendiente"}</div>
                <div><strong>Empresa transporte:</strong> ${salida.empresa_transporte?.nombre || "Sin asignar"}</div>
                <div><strong>Camión:</strong> ${salida.camion?.modelo || "Sin asignar"}</div>
                ${obrasClientesInfo}
            </div>
        </div>
    `;

    // Construir opciones de obras
    const obrasOptions = (filtros?.obras || []).map(o =>
        `<option value="${o.id}">${o.cod_obra || ''} - ${o.obra || 'Sin nombre'}</option>`
    ).join('');

    // Construir opciones de planillas
    const planillasOptions = (filtros?.planillas || []).map(p =>
        `<option value="${p.id}" data-obra-id="${p.obra_id || ''}">${p.codigo || 'Sin código'}</option>`
    ).join('');

    return `
        <div class="text-left">
            ${infoSalida}

            <p class="text-sm text-gray-600 mb-4">
                Arrastra paquetes entre las zonas para asignarlos o quitarlos de esta salida.
            </p>

            <div class="grid grid-cols-2 gap-4">
                <!-- Paquetes asignados a esta salida -->
                <div class="bg-green-50 border-2 border-green-200 rounded-lg p-3">
                    <div class="font-semibold text-green-900 mb-2 flex items-center justify-between">
                        <span>📦 Paquetes en esta salida</span>
                        <span class="text-xs bg-green-200 px-2 py-1 rounded" id="peso-asignados">${totalKgAsignados.toFixed(2)} kg</span>
                    </div>
                    <div
                        class="paquetes-zona-salida drop-zone overflow-y-auto"
                        data-zona="asignados"
                        style="min-height: 350px; max-height: 450px; border: 2px dashed #10b981; border-radius: 8px; padding: 8px;"
                    >
                        ${construirPaquetesHTMLSalida(paquetesAsignados)}
                    </div>
                </div>

                <!-- Paquetes disponibles -->
                <div class="bg-gray-50 border-2 border-gray-300 rounded-lg p-3">
                    <div class="font-semibold text-gray-900 mb-2">
                        <span>📋 Paquetes Disponibles</span>
                    </div>

                    <!-- Filtros -->
                    <div class="space-y-2 mb-3">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">🏗️ Filtrar por Obra</label>
                                <select id="filtro-obra-modal" class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- Todas las obras --</option>
                                    ${obrasOptions}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">📄 Filtrar por Planilla</label>
                                <select id="filtro-planilla-modal" class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- Todas las planillas --</option>
                                    ${planillasOptions}
                                </select>
                            </div>
                        </div>
                        <button type="button" id="btn-limpiar-filtros-modal"
                                class="w-full text-xs px-2 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                            🔄 Limpiar Filtros
                        </button>
                    </div>

                    <div
                        class="paquetes-zona-salida drop-zone overflow-y-auto"
                        data-zona="disponibles"
                        style="min-height: 250px; max-height: 350px; border: 2px dashed #6b7280; border-radius: 8px; padding: 8px;"
                    >
                        ${construirPaquetesHTMLSalida(paquetesDisponibles)}
                    </div>
                </div>
            </div>
        </div>
    `;
}

/* ===================== Construir HTML paquetes salida ===================== */
function construirPaquetesHTMLSalida(paquetes) {
    if (!paquetes || paquetes.length === 0) {
        return '<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>';
    }

    return paquetes
        .map(
            (paquete) => `
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${paquete.id}"
            data-peso="${paquete.peso || 0}"
            data-obra-id="${paquete.planilla?.obra?.id || ''}"
            data-obra="${paquete.planilla?.obra?.obra || ''}"
            data-planilla-id="${paquete.planilla?.id || ''}"
            data-planilla="${paquete.planilla?.codigo || ''}"
            data-cliente="${paquete.planilla?.cliente?.empresa || ''}"
            data-paquete-json='${JSON.stringify(paquete).replace(/'/g, "&#39;")}'
        >
            <div class="flex items-center justify-between text-xs">
                <span class="font-medium">📦 ${paquete.codigo || 'Paquete #' + paquete.id}</span>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        onclick="event.stopPropagation(); window.verElementosPaqueteSalida(${paquete.id})"
                        class="text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded p-1 transition-colors"
                        title="Ver elementos del paquete"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                    <span class="text-gray-600">${parseFloat(paquete.peso || 0).toFixed(2)} kg</span>
                </div>
            </div>
            <div class="text-xs text-gray-500 mt-1">
                <div>📄 ${paquete.planilla?.codigo || paquete.planilla_id}</div>
                <div>🏗️ ${paquete.planilla?.obra?.cod_obra || ''} - ${paquete.planilla?.obra?.obra || "N/A"}</div>
                <div>👤 ${paquete.planilla?.cliente?.empresa || "Sin cliente"}</div>
                ${paquete.nave?.obra ? `<div class="text-blue-600 font-medium">📍 ${paquete.nave.obra}</div>` : ""}
            </div>
        </div>
    `
        )
        .join("");
}

/* ===================== Ver elementos de un paquete ===================== */
async function verElementosPaqueteSalida(paqueteId) {
    try {
        // Buscar el paquete en el DOM para obtener sus datos
        const paqueteElement = document.querySelector(`[data-paquete-id="${paqueteId}"]`);
        let paqueteData = null;

        if (paqueteElement && paqueteElement.dataset.paqueteJson) {
            try {
                paqueteData = JSON.parse(paqueteElement.dataset.paqueteJson.replace(/&#39;/g, "'"));
            } catch (e) {
                console.warn("No se pudo parsear JSON del paquete", e);
            }
        }

        // Si no tenemos datos del paquete, hacer fetch
        if (!paqueteData) {
            const response = await fetch(`/api/paquetes/${paqueteId}/elementos`);
            if (response.ok) {
                paqueteData = await response.json();
            }
        }

        if (!paqueteData) {
            alert("No se pudo obtener información del paquete");
            return;
        }

        // Extraer elementos de las etiquetas del paquete
        const elementos = [];
        if (paqueteData.etiquetas && paqueteData.etiquetas.length > 0) {
            paqueteData.etiquetas.forEach((etiqueta) => {
                if (etiqueta.elementos && etiqueta.elementos.length > 0) {
                    etiqueta.elementos.forEach((elemento) => {
                        elementos.push({
                            id: elemento.id,
                            dimensiones: elemento.dimensiones,
                            peso: elemento.peso,
                            longitud: elemento.longitud,
                            diametro: elemento.diametro,
                        });
                    });
                }
            });
        }

        if (elementos.length === 0) {
            alert("Este paquete no tiene elementos para mostrar");
            return;
        }

        // Construir HTML con los elementos
        const elementosHtml = elementos.map((el, idx) => `
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">Elemento #${el.id}</span>
                    <span class="text-xs text-gray-500">${idx + 1} de ${elementos.length}</span>
                </div>
                <div class="mt-2 text-sm text-gray-600 grid grid-cols-2 gap-2">
                    ${el.diametro ? `<div><strong>Ø:</strong> ${el.diametro} mm</div>` : ''}
                    ${el.longitud ? `<div><strong>Long:</strong> ${el.longitud} mm</div>` : ''}
                    ${el.peso ? `<div><strong>Peso:</strong> ${parseFloat(el.peso).toFixed(2)} kg</div>` : ''}
                </div>
                ${el.dimensiones ? `
                    <div class="mt-2 p-2 bg-white border rounded">
                        <div id="elemento-dibujo-${el.id}" class="w-full h-32"></div>
                    </div>
                ` : ''}
            </div>
        `).join('');

        // Eliminar modal anterior si existe
        const modalAnterior = document.getElementById('modal-elementos-paquete-overlay');
        if (modalAnterior) {
            modalAnterior.remove();
        }

        // Crear modal HTML superpuesto (sin usar SweetAlert para no cerrar el modal principal)
        const modalHtml = `
            <div id="modal-elementos-paquete-overlay"
                 class="fixed inset-0 flex items-center justify-center p-4"
                 style="z-index: 10000; background: rgba(0,0,0,0.5);"
                 onclick="if(event.target === this) this.remove()">
                <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-between p-4 border-b bg-blue-600 text-white rounded-t-lg">
                        <h3 class="text-lg font-semibold">👁️ Elementos del Paquete #${paqueteId}</h3>
                        <button onclick="document.getElementById('modal-elementos-paquete-overlay').remove()"
                                class="text-white hover:bg-blue-700 rounded p-1 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-4 overflow-y-auto flex-1">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <div class="text-sm">
                                <strong>Planilla:</strong> ${paqueteData.planilla?.codigo || 'N/A'}<br>
                                <strong>Peso total:</strong> ${parseFloat(paqueteData.peso || 0).toFixed(2)} kg<br>
                                <strong>Total elementos:</strong> ${elementos.length}
                            </div>
                        </div>
                        ${elementosHtml}
                    </div>
                    <div class="p-4 border-t bg-gray-50 rounded-b-lg">
                        <button onclick="document.getElementById('modal-elementos-paquete-overlay').remove()"
                                class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Insertar modal en el body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Dibujar figuras de elementos después de insertar el modal
        setTimeout(() => {
            if (typeof window.dibujarFiguraElemento === "function") {
                elementos.forEach((el) => {
                    if (el.dimensiones) {
                        window.dibujarFiguraElemento(
                            `elemento-dibujo-${el.id}`,
                            el.dimensiones,
                            null
                        );
                    }
                });
            }
        }, 100);

    } catch (error) {
        console.error("Error al ver elementos del paquete:", error);
        alert("Error al cargar los elementos del paquete");
    }
}

// Exponer función globalmente para que el onclick funcione
window.verElementosPaqueteSalida = verElementosPaqueteSalida;

/* ===================== Inicializar filtros del modal ===================== */
function inicializarFiltrosModal(filtros) {
    const filtroObra = document.getElementById('filtro-obra-modal');
    const filtroPlanilla = document.getElementById('filtro-planilla-modal');
    const btnLimpiar = document.getElementById('btn-limpiar-filtros-modal');

    // Filtro por obra
    if (filtroObra) {
        filtroObra.addEventListener('change', () => {
            // Actualizar planillas disponibles según la obra seleccionada
            actualizarPlanillasSegunObra();
            aplicarFiltrosModal();
        });
    }

    // Filtro por planilla
    if (filtroPlanilla) {
        filtroPlanilla.addEventListener('change', () => {
            aplicarFiltrosModal();
        });
    }

    // Limpiar filtros
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            if (filtroObra) filtroObra.value = '';
            if (filtroPlanilla) filtroPlanilla.value = '';
            actualizarPlanillasSegunObra();
            aplicarFiltrosModal();
        });
    }
}

/* ===================== Actualizar planillas según obra seleccionada ===================== */
function actualizarPlanillasSegunObra() {
    const filtroObra = document.getElementById('filtro-obra-modal');
    const filtroPlanilla = document.getElementById('filtro-planilla-modal');
    const data = window._gestionPaquetesData;

    if (!filtroPlanilla || !data) return;

    const obraId = filtroObra?.value || '';

    // Obtener paquetes fuente según si hay obra seleccionada
    const paquetesFuente = obraId ? data.paquetesTodos : data.paquetesDisponibles;

    // Extraer planillas únicas de los paquetes
    const planillasMap = new Map();
    paquetesFuente.forEach(p => {
        if (p.planilla?.id) {
            // Si hay obra seleccionada, solo incluir planillas de esa obra
            if (obraId && String(p.planilla.obra?.id) !== obraId) return;

            if (!planillasMap.has(p.planilla.id)) {
                planillasMap.set(p.planilla.id, {
                    id: p.planilla.id,
                    codigo: p.planilla.codigo || 'Sin código',
                    obra_id: p.planilla.obra?.id
                });
            }
        }
    });

    // Convertir a array y ordenar
    const planillas = Array.from(planillasMap.values()).sort((a, b) =>
        (a.codigo || '').localeCompare(b.codigo || '')
    );

    // Guardar valor actual
    const valorActual = filtroPlanilla.value;

    // Regenerar opciones
    filtroPlanilla.innerHTML = '<option value="">-- Todas las planillas --</option>';
    planillas.forEach(p => {
        const option = document.createElement('option');
        option.value = p.id;
        option.textContent = p.codigo;
        filtroPlanilla.appendChild(option);
    });

    // Restaurar valor si sigue existiendo
    if (valorActual && planillasMap.has(parseInt(valorActual))) {
        filtroPlanilla.value = valorActual;
    } else {
        filtroPlanilla.value = '';
    }
}

/* ===================== Actualizar paquetes disponibles en modal ===================== */
function actualizarPaquetesDisponiblesModal() {
    const data = window._gestionPaquetesData;
    if (!data) return;

    const zonaDisponibles = document.querySelector('[data-zona="disponibles"]');
    if (!zonaDisponibles) return;

    // Por defecto mostrar solo paquetes de las obras de esta salida
    const paquetes = data.paquetesDisponibles;

    // Filtrar los que ya están asignados (en la zona de asignados)
    const zonaAsignados = document.querySelector('[data-zona="asignados"]');
    const idsAsignados = new Set();
    if (zonaAsignados) {
        zonaAsignados.querySelectorAll('.paquete-item-salida').forEach(item => {
            idsAsignados.add(parseInt(item.dataset.paqueteId));
        });
    }

    const paquetesFiltrados = paquetes.filter(p => !idsAsignados.has(p.id));

    // Actualizar HTML
    zonaDisponibles.innerHTML = construirPaquetesHTMLSalida(paquetesFiltrados);

    // Re-inicializar drag and drop
    inicializarDragAndDropSalida();

    // Aplicar filtros actuales
    aplicarFiltrosModal();
}

/* ===================== Aplicar filtros en modal ===================== */
function aplicarFiltrosModal() {
    const filtroObra = document.getElementById('filtro-obra-modal');
    const filtroPlanilla = document.getElementById('filtro-planilla-modal');
    const data = window._gestionPaquetesData;

    const obraId = filtroObra?.value || '';
    const planillaId = filtroPlanilla?.value || '';

    const zonaDisponibles = document.querySelector('[data-zona="disponibles"]');
    if (!zonaDisponibles || !data) return;

    // Obtener IDs de paquetes ya asignados
    const zonaAsignados = document.querySelector('[data-zona="asignados"]');
    const idsAsignados = new Set();
    if (zonaAsignados) {
        zonaAsignados.querySelectorAll('.paquete-item-salida').forEach(item => {
            idsAsignados.add(parseInt(item.dataset.paqueteId));
        });
    }

    // Si hay filtro de obra, buscar en TODOS los paquetes; si no, solo en los de la salida
    let paquetesFuente = obraId ? data.paquetesTodos : data.paquetesDisponibles;

    // Filtrar por obra y planilla
    let paquetesFiltrados = paquetesFuente.filter(p => {
        // Excluir los ya asignados
        if (idsAsignados.has(p.id)) return false;

        // Filtrar por obra si está seleccionada
        if (obraId && String(p.planilla?.obra?.id) !== obraId) return false;

        // Filtrar por planilla si está seleccionada
        if (planillaId && String(p.planilla?.id) !== planillaId) return false;

        return true;
    });

    // Regenerar el HTML de paquetes disponibles
    zonaDisponibles.innerHTML = construirPaquetesHTMLSalida(paquetesFiltrados);

    // Reinicializar drag and drop para los nuevos elementos
    inicializarDragAndDropSalida();

    // Mostrar placeholder si no hay paquetes
    if (paquetesFiltrados.length === 0) {
        zonaDisponibles.innerHTML = '<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">No hay paquetes que coincidan con el filtro</div>';
    }
}

/* ===================== Navegación por teclado en modal de paquetes ===================== */
let modalKeyboardNav = {
    zonaActiva: 'asignados', // 'asignados' o 'disponibles'
    indiceFocused: -1,
    cleanup: null
};

function inicializarNavegacionTecladoModal() {
    // Limpiar listener anterior si existe
    if (modalKeyboardNav.cleanup) {
        modalKeyboardNav.cleanup();
    }

    modalKeyboardNav.zonaActiva = 'asignados';
    modalKeyboardNav.indiceFocused = 0;

    // Enfocar primer paquete si existe
    actualizarFocoPaqueteModal();

    function handleKeydown(e) {
        // Solo funcionar dentro del modal de SweetAlert
        if (!document.querySelector('.swal2-container')) return;

        const tag = e.target.tagName.toLowerCase();
        const enSelect = tag === 'select';
        const enInput = tag === 'input' || tag === 'textarea';

        // Si estamos en un input de texto, ignorar todo excepto Escape
        if (enInput && e.key !== 'Escape') return;

        const zonaAsignados = document.querySelector('[data-zona="asignados"]');
        const zonaDisponibles = document.querySelector('[data-zona="disponibles"]');
        if (!zonaAsignados || !zonaDisponibles) return;

        const zonaActual = modalKeyboardNav.zonaActiva === 'asignados' ? zonaAsignados : zonaDisponibles;
        const paquetesVisibles = Array.from(zonaActual.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));
        const totalPaquetes = paquetesVisibles.length;

        let handled = false;

        // Navegación de paquetes - solo si NO estamos en un select
        if (!enSelect) {
            switch (e.key) {
                case 'ArrowDown':
                    if (totalPaquetes > 0) {
                        modalKeyboardNav.indiceFocused = (modalKeyboardNav.indiceFocused + 1) % totalPaquetes;
                        actualizarFocoPaqueteModal();
                        handled = true;
                    }
                    break;

                case 'ArrowUp':
                    if (totalPaquetes > 0) {
                        modalKeyboardNav.indiceFocused = modalKeyboardNav.indiceFocused <= 0
                            ? totalPaquetes - 1
                            : modalKeyboardNav.indiceFocused - 1;
                        actualizarFocoPaqueteModal();
                        handled = true;
                    }
                    break;

                case 'ArrowLeft':
                case 'ArrowRight':
                    // Cambiar de zona
                    modalKeyboardNav.zonaActiva = modalKeyboardNav.zonaActiva === 'asignados' ? 'disponibles' : 'asignados';
                    modalKeyboardNav.indiceFocused = 0;
                    actualizarFocoPaqueteModal();
                    handled = true;
                    break;

                case 'Tab':
                    // Cambiar de zona con Tab
                    e.preventDefault();
                    modalKeyboardNav.zonaActiva = modalKeyboardNav.zonaActiva === 'asignados' ? 'disponibles' : 'asignados';
                    modalKeyboardNav.indiceFocused = 0;
                    actualizarFocoPaqueteModal();
                    handled = true;
                    break;

                case 'Enter': {
                    // Mover paquete al otro lado
                    if (totalPaquetes > 0 && modalKeyboardNav.indiceFocused >= 0) {
                        const paqueteFocused = paquetesVisibles[modalKeyboardNav.indiceFocused];
                        if (paqueteFocused) {
                            moverPaqueteAlOtroLado(paqueteFocused);
                            // Ajustar índice si es necesario
                            const nuevosVisibles = Array.from(zonaActual.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));
                            if (modalKeyboardNav.indiceFocused >= nuevosVisibles.length) {
                                modalKeyboardNav.indiceFocused = Math.max(0, nuevosVisibles.length - 1);
                            }
                            actualizarFocoPaqueteModal();
                            handled = true;
                        }
                    }
                    break;
                }

                case 'Home':
                    modalKeyboardNav.indiceFocused = 0;
                    actualizarFocoPaqueteModal();
                    handled = true;
                    break;

                case 'End':
                    modalKeyboardNav.indiceFocused = Math.max(0, totalPaquetes - 1);
                    actualizarFocoPaqueteModal();
                    handled = true;
                    break;
            }
        }

        // Si ya se manejó, salir
        if (handled) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }

        // Atajos para filtros - funcionan incluso desde un select
        switch (e.key) {
            case 'o':
            case 'O': {
                // Enfocar filtro de obra
                const filtroObra = document.getElementById('filtro-obra-modal');
                if (filtroObra) {
                    filtroObra.focus();
                    handled = true;
                }
                break;
            }

            case 'p':
            case 'P': {
                // Enfocar filtro de planilla
                const filtroPlanilla = document.getElementById('filtro-planilla-modal');
                if (filtroPlanilla) {
                    filtroPlanilla.focus();
                    handled = true;
                }
                break;
            }

            case 'l':
            case 'L': {
                // Limpiar filtros
                const btnLimpiar = document.getElementById('btn-limpiar-filtros-modal');
                if (btnLimpiar) {
                    btnLimpiar.click();
                    // Volver a navegación de paquetes
                    document.activeElement?.blur();
                    actualizarFocoPaqueteModal();
                    handled = true;
                }
                break;
            }

            case '/':
            case 'f':
            case 'F': {
                // Enfocar primer filtro (obra)
                const primerFiltro = document.getElementById('filtro-obra-modal');
                if (primerFiltro) {
                    primerFiltro.focus();
                    handled = true;
                }
                break;
            }

            case 'Escape':
                // Si estamos en un filtro, volver a navegación de paquetes
                if (enSelect) {
                    document.activeElement.blur();
                    actualizarFocoPaqueteModal();
                    handled = true;
                }
                break;

            case 's':
            case 'S': {
                // Guardar (clic en botón confirmar)
                if (e.ctrlKey || e.metaKey) {
                    const btnGuardar = document.querySelector('.swal2-confirm');
                    if (btnGuardar) {
                        btnGuardar.click();
                        handled = true;
                    }
                }
                break;
            }
        }

        if (handled) {
            e.preventDefault();
            e.stopPropagation();
        }
    }

    document.addEventListener('keydown', handleKeydown, true);

    modalKeyboardNav.cleanup = () => {
        document.removeEventListener('keydown', handleKeydown, true);
        limpiarFocoPaquetesModal();
    };
}

function actualizarFocoPaqueteModal() {
    // Limpiar foco anterior
    limpiarFocoPaquetesModal();

    const zonaAsignados = document.querySelector('[data-zona="asignados"]');
    const zonaDisponibles = document.querySelector('[data-zona="disponibles"]');
    if (!zonaAsignados || !zonaDisponibles) return;

    // Marcar zona activa
    if (modalKeyboardNav.zonaActiva === 'asignados') {
        zonaAsignados.classList.add('zona-activa-keyboard');
        zonaDisponibles.classList.remove('zona-activa-keyboard');
    } else {
        zonaDisponibles.classList.add('zona-activa-keyboard');
        zonaAsignados.classList.remove('zona-activa-keyboard');
    }

    const zonaActual = modalKeyboardNav.zonaActiva === 'asignados' ? zonaAsignados : zonaDisponibles;
    const paquetesVisibles = Array.from(zonaActual.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));

    if (paquetesVisibles.length > 0 && modalKeyboardNav.indiceFocused >= 0) {
        const idx = Math.min(modalKeyboardNav.indiceFocused, paquetesVisibles.length - 1);
        const paquete = paquetesVisibles[idx];
        if (paquete) {
            paquete.classList.add('paquete-focused-keyboard');
            paquete.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // Mostrar indicador de zona
    actualizarIndicadorZonaModal();
}

function limpiarFocoPaquetesModal() {
    document.querySelectorAll('.paquete-focused-keyboard').forEach(el => {
        el.classList.remove('paquete-focused-keyboard');
    });
    document.querySelectorAll('.zona-activa-keyboard').forEach(el => {
        el.classList.remove('zona-activa-keyboard');
    });
}

function moverPaqueteAlOtroLado(paqueteEl) {
    const zonaAsignados = document.querySelector('[data-zona="asignados"]');
    const zonaDisponibles = document.querySelector('[data-zona="disponibles"]');
    if (!zonaAsignados || !zonaDisponibles) return;

    const zonaActual = paqueteEl.closest('[data-zona]');
    const zonaDestino = zonaActual.dataset.zona === 'asignados' ? zonaDisponibles : zonaAsignados;

    // Remover placeholder si existe en destino
    const placeholder = zonaDestino.querySelector('.placeholder-sin-paquetes');
    if (placeholder) placeholder.remove();

    // Mover el paquete
    zonaDestino.appendChild(paqueteEl);

    // Agregar placeholder en origen si queda vacío
    const paquetesRestantes = zonaActual.querySelectorAll('.paquete-item-salida');
    if (paquetesRestantes.length === 0) {
        const newPlaceholder = document.createElement('div');
        newPlaceholder.className = 'text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes';
        newPlaceholder.textContent = 'Sin paquetes';
        zonaActual.appendChild(newPlaceholder);
    }

    // Re-inicializar drag and drop para el elemento movido
    inicializarDragEnPaquete(paqueteEl);

    // Actualizar totales
    actualizarTotalesSalida();
}

function actualizarIndicadorZonaModal() {
    // Crear o actualizar indicador
    let indicator = document.getElementById('modal-keyboard-indicator');

    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'modal-keyboard-indicator';
        indicator.className = 'fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs';
        document.body.appendChild(indicator);
    }

    const zonaAsignados = document.querySelector('[data-zona="asignados"]');
    const zonaDisponibles = document.querySelector('[data-zona="disponibles"]');

    const paquetesAsignados = zonaAsignados?.querySelectorAll('.paquete-item-salida').length || 0;
    const paquetesDisponibles = zonaDisponibles?.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length || 0;

    const zonaTexto = modalKeyboardNav.zonaActiva === 'asignados'
        ? `📦 Asignados (${paquetesAsignados})`
        : `📋 Disponibles (${paquetesDisponibles})`;

    indicator.innerHTML = `
        <div class="flex items-center gap-2 mb-2">
            <span class="${modalKeyboardNav.zonaActiva === 'asignados' ? 'bg-green-500' : 'bg-gray-500'} text-white text-xs px-2 py-0.5 rounded">${zonaTexto}</span>
        </div>
        <div class="text-gray-400 space-y-1">
            <div class="flex gap-3">
                <span>↑↓ Navegar</span>
                <span>←→ Zona</span>
                <span>Enter Mover</span>
            </div>
            <div class="flex gap-3 border-t border-gray-700 pt-1 mt-1">
                <span>O Obra</span>
                <span>P Planilla</span>
                <span>L Limpiar</span>
            </div>
            <div class="flex gap-3">
                <span>T Todos</span>
                <span>Esc Salir filtro</span>
                <span>Ctrl+S Guardar</span>
            </div>
        </div>
    `;

    // Ocultar cuando se cierre el modal
    clearTimeout(indicator._checkTimeout);
    indicator._checkTimeout = setTimeout(() => {
        if (!document.querySelector('.swal2-container')) {
            indicator.remove();
        }
    }, 500);
}

function inyectarEstilosModalKeyboard() {
    if (document.getElementById('modal-keyboard-styles')) return;

    const styles = document.createElement('style');
    styles.id = 'modal-keyboard-styles';
    styles.textContent = `
        .paquete-focused-keyboard {
            outline: 3px solid #3b82f6 !important;
            outline-offset: 2px;
            background-color: #eff6ff !important;
            transform: scale(1.02);
            z-index: 10;
            position: relative;
        }

        .paquete-focused-keyboard::before {
            content: '►';
            position: absolute;
            left: -16px;
            top: 50%;
            transform: translateY(-50%);
            color: #3b82f6;
            font-size: 12px;
        }

        .zona-activa-keyboard {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
        }

        [data-zona="asignados"].zona-activa-keyboard {
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.3) !important;
        }

        [data-zona="disponibles"].zona-activa-keyboard {
            box-shadow: 0 0 0 3px rgba(107, 114, 128, 0.3) !important;
        }
    `;
    document.head.appendChild(styles);
}

/* ===================== Inicializar drag and drop salida ===================== */
function inicializarDragEnPaquete(item) {
    item.addEventListener("dragstart", (e) => {
        item.style.opacity = "0.5";
        e.dataTransfer.setData('text/plain', item.dataset.paqueteId);
    });

    item.addEventListener("dragend", (e) => {
        item.style.opacity = "1";
    });
}

function inicializarDragAndDropSalida() {
    // Eventos de drag para los paquetes
    document.querySelectorAll(".paquete-item-salida").forEach((item) => {
        inicializarDragEnPaquete(item);
    });

    // Eventos de drop para las zonas
    document.querySelectorAll(".drop-zone").forEach((zone) => {
        zone.addEventListener("dragover", (e) => {
            e.preventDefault();
            const zonaType = zone.dataset.zona;
            zone.style.backgroundColor = zonaType === "asignados" ? "#d1fae5" : "#e0f2fe";
        });

        zone.addEventListener("dragleave", (e) => {
            zone.style.backgroundColor = "";
        });

        zone.addEventListener("drop", (e) => {
            e.preventDefault();
            zone.style.backgroundColor = "";

            const paqueteId = e.dataTransfer.getData('text/plain');
            const draggedElement = document.querySelector(`.paquete-item-salida[data-paquete-id="${paqueteId}"]`);

            if (draggedElement) {
                // Remover placeholder si existe
                const placeholder = zone.querySelector(".placeholder-sin-paquetes");
                if (placeholder) placeholder.remove();

                // Agregar elemento a la nueva zona
                zone.appendChild(draggedElement);

                // Actualizar totales
                actualizarTotalesSalida();
            }
        });
    });
}

/* ===================== Actualizar totales salida ===================== */
function actualizarTotalesSalida() {
    const zonaAsignados = document.querySelector('[data-zona="asignados"]');
    const paquetes = zonaAsignados?.querySelectorAll(".paquete-item-salida");

    let totalKg = 0;
    paquetes?.forEach((p) => {
        const peso = parseFloat(p.dataset.peso) || 0;
        totalKg += peso;
    });

    const badge = document.getElementById("peso-asignados");
    if (badge) {
        badge.textContent = `${totalKg.toFixed(2)} kg`;
    }

}

/* ===================== Recolectar paquetes salida ===================== */
function recolectarPaquetesSalida() {
    const zonaAsignados = document.querySelector('[data-zona="asignados"]');
    const paquetesAsignados = Array.from(
        zonaAsignados?.querySelectorAll(".paquete-item-salida") || []
    ).map((item) => parseInt(item.dataset.paqueteId));

    return {
        paquetes_ids: paquetesAsignados,
    };
}

/* ===================== Guardar paquetes salida ===================== */
async function guardarPaquetesSalida(salidaId, data, calendar) {
    try {
        const res = await fetch(
            window.AppSalidas?.routes?.guardarPaquetesSalida,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": window.AppSalidas?.csrf,
                },
                body: JSON.stringify({
                    salida_id: salidaId,
                    paquetes_ids: data.paquetes_ids,
                }),
            }
        );

        const responseData = await res.json();

        if (responseData.success) {
            await Swal.fire({
                icon: "success",
                title: "✅ Cambios Guardados",
                text: "Los paquetes de la salida se han actualizado correctamente.",
                timer: 2000,
            });

            if (calendar) {
                calendar.refetchEvents();
                calendar.refetchResources?.();
            }
        } else {
            await Swal.fire(
                "⚠️",
                responseData.message || "No se pudieron guardar los cambios",
                "warning"
            );
        }
    } catch (err) {
        console.error(err);
        Swal.fire("❌", "Error al guardar los paquetes", "error");
    }
}

/* ===================== Comentar salida ===================== */
async function comentarSalida(salidaId, comentarioActual, calendar) {
    // Cerrar el menú contextual si está abierto
    try {
        closeMenu();
    } catch (_) {}

    // Disparar evento de Livewire para abrir el modal
    window.Livewire.dispatch('abrirComentario', { salidaId });

    // Guardar referencia al calendario para actualización posterior
    window._calendarRef = calendar;
}

/* ===================== Asignar empresa de transporte ===================== */
async function asignarEmpresaTransporte(salidaId, empresaActualId = null, empresaActualNombre = "", calendar) {
    try {
        closeMenu();
    } catch (_) {}
    if (!salidaId) return Swal.fire("⚠️", "ID de salida inválido.", "warning");

    const empresas = window.AppSalidas?.empresasTransporte || [];
    if (!empresas.length) {
        return Swal.fire("⚠️", "No hay empresas de transporte disponibles.", "warning");
    }

    // Crear opciones del select
    const inputOptions = {};
    empresas.forEach(empresa => {
        inputOptions[empresa.id] = empresa.nombre;
    });

    const { value: empresaId, isConfirmed } = await Swal.fire({
        title: "🚚 Asignar Empresa de Transporte",
        input: "select",
        inputOptions: inputOptions,
        inputValue: empresaActualId || "",
        inputPlaceholder: "Selecciona una empresa",
        showCancelButton: true,
        confirmButtonText: "💾 Guardar",
        inputValidator: (value) => {
            if (!value) {
                return "Debes seleccionar una empresa";
            }
        }
    });

    if (!isConfirmed) return;

    const routeTmpl = window.AppSalidas?.routes?.empresaTransporte || "";
    if (!routeTmpl)
        return Swal.fire(
            "⚠️",
            "No está configurada la ruta de empresa de transporte.",
            "warning"
        );

    const url = routeTmpl.replace("__ID__", String(salidaId));
    const payload = { empresa_id: parseInt(empresaId) };

    try {
        const res = await fetch(url, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": window.AppSalidas?.csrf,
            },
            body: JSON.stringify(payload),
        });
        if (!res.ok) {
            const text = await res.text().catch(() => "");
            throw new Error(`HTTP ${res.status} ${res.statusText} ${text}`);
        }
        const data = await res.json().catch(() => ({}));
        await Swal.fire(
            data.success ? "✅" : "⚠️",
            data.message ||
                (data.success ? "Empresa de transporte asignada" : "No se pudo asignar"),
            data.success ? "success" : "warning"
        );
        if (data.success && calendar) {
            calendar.refetchEvents?.();
            calendar.refetchResources?.();
        }
    } catch (err) {
        console.error(err);
        Swal.fire("❌", "Ocurrió un error al guardar la empresa.", "error");
    }
}

/* ===================== Asignar código SAGE ===================== */
async function asignarCodigoSalida(salidaId, codigoActual = "", calendar) {
    try {
        closeMenu();
    } catch (_) {}
    if (!salidaId) return Swal.fire("⚠️", "ID de salida inválido.", "warning");

    const { value, isConfirmed } = await Swal.fire({
        title: "🏷️ Asignar código SAGE",
        input: "text",
        inputValue: (codigoActual || "").trim(),
        inputPlaceholder: "Ej.: F1/0004",
        inputValidator: (v) =>
            !v || v.trim().length === 0
                ? "El código no puede estar vacío"
                : undefined,
        showCancelButton: true,
        confirmButtonText: "💾 Guardar",
    });
    if (!isConfirmed) return;

    const routeTmpl = window.AppSalidas?.routes?.codigoSage || "";
    if (!routeTmpl)
        return Swal.fire(
            "⚠️",
            "No está configurada la ruta de código SAGE.",
            "warning"
        );

    const url = routeTmpl.replace("__ID__", String(salidaId));
    const payload = { codigo: value.trim() };

    try {
        const res = await fetch(url, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": window.AppSalidas?.csrf,
            },
            body: JSON.stringify(payload),
        });
        if (!res.ok) {
            const text = await res.text().catch(() => "");
            throw new Error(`HTTP ${res.status} ${res.statusText} ${text}`);
        }
        const data = await res.json().catch(() => ({}));
        await Swal.fire(
            data.success ? "" : "⚠️",
            data.message ||
                (data.success ? "Código SAGE asignado" : "No se pudo asignar"),
            data.success ? "success" : "warning"
        );
        if (data.success && calendar) {
            calendar.refetchEvents?.();
            calendar.refetchResources?.();
        }
    } catch (err) {
        console.error(err);
        Swal.fire("", "Ocurrió un error al guardar el código.", "error");
    }
}

/* ===================== Util: normalizar IDs ===================== */
function normalizarIds(input) {
    if (!input) return [];
    if (typeof input === "string") {
        return input
            .split(",")
            .map((s) => s.trim())
            .filter(Boolean);
    }
    const arr = Array.from(input);
    return arr
        .map((x) => (typeof x === "object" && x?.id != null ? x.id : x))
        .map(String)
        .map((s) => s.trim())
        .filter(Boolean);
}

/* ===================== Abrir listado de planillas ===================== */
export function salidasCreate(planillasIds /*, calendar */) {
    const base = window?.AppSalidas?.routes?.salidasCreate;
    if (!base) {
        alert("No se encontró la ruta de salidas.create");
        return;
    }
    const ids = Array.from(new Set(normalizarIds(planillasIds)));
    const qs = ids.length
        ? "?planillas=" + encodeURIComponent(ids.join(","))
        : "";
    window.location.href = base + qs;
}

/* ===================== Cambiar fechas (modal agrupación) ===================== */
async function obtenerInformacionPlanillas(ids) {
    const base = window.AppSalidas?.routes?.informacionPlanillas;
    if (!base) throw new Error("Ruta 'informacionPlanillas' no configurada");
    const url = `${base}?ids=${encodeURIComponent(ids.join(","))}`;
    const res = await fetch(url, { headers: { Accept: "application/json" } });
    if (!res.ok) {
        const t = await res.text().catch(() => "");
        throw new Error(`GET ${url} -> ${res.status} ${t}`);
    }
    const data = await res.json();
    return Array.isArray(data?.planillas) ? data.planillas : [];
}

/**
 * Detecta si una fecha es fin de semana (sábado o domingo)
 * @param {string} dateStr - Fecha en formato YYYY-MM-DD
 * @returns {boolean} - true si es fin de semana
 */
function esFinDeSemana(dateStr) {
    if (!dateStr) return false;
    const date = new Date(dateStr + "T00:00:00"); // Evitar problemas de zona horaria
    const dayOfWeek = date.getDay(); // 0 = domingo, 6 = sábado
    return dayOfWeek === 0 || dayOfWeek === 6;
}

/**
 * Muestra un tooltip con la figura del elemento al hacer hover
 * @param {string|number} elementoId - ID del elemento
 * @param {string} dimensiones - String de dimensiones del elemento
 * @param {HTMLElement} triggerBtn - Botón que disparó el evento
 */
function mostrarFiguraElementoModal(elementoId, codigo, dimensiones, triggerBtn) {
    // Eliminar modal anterior si existe
    const modalAnterior = document.getElementById('modal-figura-elemento-overlay');
    if (modalAnterior) {
        modalAnterior.remove();
    }

    // Calcular posición cerca del botón
    const rect = triggerBtn.getBoundingClientRect();
    const tooltipWidth = 320;
    const tooltipHeight = 240;

    // Posicionar a la derecha del botón, o a la izquierda si no hay espacio
    let left = rect.right + 10;
    if (left + tooltipWidth > window.innerWidth) {
        left = rect.left - tooltipWidth - 10;
    }

    // Centrar verticalmente respecto al botón
    let top = rect.top - (tooltipHeight / 2) + (rect.height / 2);
    if (top < 10) top = 10;
    if (top + tooltipHeight > window.innerHeight - 10) {
        top = window.innerHeight - tooltipHeight - 10;
    }

    // Crear tooltip HTML
    const modalHtml = `
        <div id="modal-figura-elemento-overlay"
             class="fixed bg-white rounded-lg shadow-2xl border border-gray-300"
             style="z-index: 10001; left: ${left}px; top: ${top}px; width: ${tooltipWidth}px;"
             onmouseleave="this.remove()">
            <div class="flex items-center justify-between px-3 py-2 border-b bg-gray-100 rounded-t-lg">
                <h3 class="text-xs font-semibold text-gray-700">${codigo || 'Elemento'}</h3>
            </div>
            <div class="p-2">
                <div id="figura-elemento-container-${elementoId}" class="w-full h-36 bg-gray-50 rounded"></div>
                <div class="mt-2 px-1 py-1 bg-gray-100 rounded text-xs text-gray-600 font-mono break-all">
                    ${dimensiones || 'Sin dimensiones'}
                </div>
            </div>
        </div>
    `;

    // Insertar tooltip en el body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Dibujar la figura después de insertar el modal
    setTimeout(() => {
        if (typeof window.dibujarFiguraElemento === 'function') {
            window.dibujarFiguraElemento(
                `figura-elemento-container-${elementoId}`,
                dimensiones,
                null
            );
        }
    }, 50);
}

function construirFormularioFechas(planillas) {
    const filas = planillas
        .map((p, i) => {
            const codObra = p.obra?.codigo || "";
            const nombreObra = p.obra?.nombre || "";
            const seccionObra = p.seccion || "";
            const descripcionObra = p.descripcion || "";
            const codigoPlanilla = p.codigo || `Planilla ${p.id}`;
            const pesoTotal = p.peso_total
                ? parseFloat(p.peso_total).toLocaleString("es-ES", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                  }) + " kg"
                : "";
            const fechaISO = toISO(p.fecha_estimada_entrega);
            const tieneElementos = p.elementos && p.elementos.length > 0;
            const numElementos = p.elementos?.length || 0;

            // Construir filas de elementos si existen
            let elementosHtml = '';
            if (tieneElementos) {
                elementosHtml = p.elementos.map((el, idx) => {
                    const fechaElISO = el.fecha_entrega || '';
                    const pesoEl = el.peso ? parseFloat(el.peso).toFixed(2) : '-';
                    const codigoEl = el.codigo || '-';
                    const tieneDimensiones = el.dimensiones && el.dimensiones.trim() !== '';
                    // Escapar dimensiones para JSON
                    const dimensionesEscaped = tieneDimensiones ? el.dimensiones.replace(/"/g, '&quot;').replace(/'/g, '&#39;') : '';

                    // Escapar código para atributo
                    const codigoEscaped = codigoEl.replace(/"/g, '&quot;').replace(/'/g, '&#39;');

                    return `
                    <tr class="elemento-row elemento-planilla-${p.id} bg-gray-50 hidden">
                        <td class="px-2 py-1 text-xs text-gray-400 pl-4">
                            <div class="flex items-center gap-1">
                                <input type="checkbox" class="elemento-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-3.5 w-3.5"
                                       data-elemento-id="${el.id}"
                                       data-planilla-id="${p.id}">
                                <span>↳</span>
                                <span class="font-medium text-gray-600">${codigoEl}</span>
                                ${tieneDimensiones ? `
                                <button type="button"
                                        class="ver-figura-elemento text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded p-0.5 transition-colors"
                                        data-elemento-id="${el.id}"
                                        data-elemento-codigo="${codigoEscaped}"
                                        data-dimensiones="${dimensionesEscaped}"
                                        title="Click para seleccionar, hover para ver figura">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                ` : ''}
                            </div>
                        </td>
                        <td class="px-2 py-1 text-xs text-gray-500">${el.marca || '-'}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">Ø${el.diametro || '-'}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${el.longitud || '-'} mm</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${el.barras || '-'} uds</td>
                        <td class="px-2 py-1 text-xs text-right text-gray-500">${pesoEl} kg</td>
                        <td class="px-2 py-1" colspan="2">
                            <input type="date" class="swal2-input !m-0 !w-auto !text-xs elemento-fecha"
                                   data-elemento-id="${el.id}"
                                   data-planilla-id="${p.id}"
                                   value="${fechaElISO}">
                        </td>
                    </tr>`;
                }).join('');
            }

            return `
<tr class="planilla-row hover:bg-blue-50 bg-blue-100 border-t border-blue-200" data-planilla-id="${p.id}" style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${i * 18}ms;">
  <td class="px-2 py-2 text-xs font-semibold text-blue-800" colspan="2">
    ${tieneElementos ? `<button type="button" class="toggle-elementos mr-1 text-blue-600 hover:text-blue-800" data-planilla-id="${p.id}">▶</button>` : ''}
    📄 ${codigoPlanilla}
    ${tieneElementos ? `<span class="ml-1 text-xs text-blue-500 font-normal">(${numElementos} elem.)</span>` : ''}
  </td>
  <td class="px-2 py-2 text-xs text-blue-700" colspan="2">
    <span class="font-medium">${codObra}</span> ${nombreObra}
  </td>
  <td class="px-2 py-2 text-xs text-blue-600">${seccionObra || '-'}</td>
  <td class="px-2 py-2 text-xs text-right font-semibold text-blue-800">${pesoTotal}</td>
  <td class="px-2 py-2" colspan="2">
    <div class="flex items-center gap-1">
      <input type="date" class="swal2-input !m-0 !w-auto planilla-fecha !bg-blue-50 !border-blue-300" data-planilla-id="${p.id}" value="${fechaISO}">
      ${tieneElementos ? `<button type="button" class="aplicar-fecha-elementos text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded" data-planilla-id="${p.id}" title="Aplicar fecha a todos los elementos">↓</button>` : ''}
    </div>
  </td>
</tr>
${elementosHtml}`;
        })
        .join("");

    return `
    <div class="text-left">
      <div class="text-sm text-gray-600 mb-2">
        Edita la <strong>fecha estimada de entrega</strong> de planillas y elementos.
        <span class="text-blue-600">▶</span> = expandir elementos, <span class="text-purple-600">☑</span> = seleccionar para asignar fecha masiva
      </div>

      <!-- Barra de acciones masivas para elementos -->
      <div id="barra-acciones-masivas" class="mb-3 p-3 bg-purple-50 border border-purple-200 rounded-lg hidden">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-purple-800">
              <span id="contador-seleccionados">0</span> elementos seleccionados
            </span>
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm text-purple-700">Asignar fecha:</label>
            <input type="date" id="fecha-masiva" class="swal2-input !m-0 !w-auto !text-sm !bg-white !border-purple-300">
            <button type="button" id="aplicar-fecha-masiva" class="text-sm bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded font-medium transition-colors">
              Aplicar a seleccionados
            </button>
          </div>
          <div class="flex items-center gap-2 ml-auto">
            <button type="button" id="limpiar-fecha-seleccionados" class="text-xs bg-gray-500 hover:bg-gray-600 text-white px-2 py-1 rounded" title="Quitar fecha de los seleccionados">
              Limpiar fecha
            </button>
            <button type="button" id="deseleccionar-todos" class="text-xs bg-gray-400 hover:bg-gray-500 text-white px-2 py-1 rounded">
              Deseleccionar
            </button>
          </div>
        </div>
      </div>

      <!-- Sumatorio dinámico por fechas -->
      <div id="sumatorio-fechas" class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="text-sm font-medium text-blue-800 mb-2">📊 Resumen por fecha:</div>
        <div id="resumen-contenido" class="text-xs text-blue-700">
          Cambia las fechas para ver el resumen...
        </div>
      </div>

      <div class="overflow-auto" style="max-height:50vh;border:1px solid #e5e7eb;border-radius:6px;">
        <table class="min-w-full text-sm">
        <thead class="sticky top-0 bg-white z-10">
          <tr>
            <th class="px-2 py-1 text-left">ID / Código</th>
            <th class="px-2 py-1 text-left">Marca</th>
            <th class="px-2 py-1 text-left">Ø</th>
            <th class="px-2 py-1 text-left">Longitud</th>
            <th class="px-2 py-1 text-left">Barras</th>
            <th class="px-2 py-1 text-left">Peso</th>
            <th class="px-2 py-1 text-left" colspan="2">Fecha Entrega</th>
          </tr>
        </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>

      <div class="mt-2 flex flex-wrap gap-2">
        <button type="button" id="expandir-todos" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded">
          📂 Expandir todos
        </button>
        <button type="button" id="colapsar-todos" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded">
          📁 Colapsar todos
        </button>
        <span class="border-l border-gray-300 mx-1"></span>
        <button type="button" id="seleccionar-todos-elementos" class="text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 px-3 py-1 rounded">
          ☑ Seleccionar todos los elementos
        </button>
        <button type="button" id="seleccionar-sin-fecha" class="text-xs bg-orange-100 hover:bg-orange-200 text-orange-700 px-3 py-1 rounded">
          ☑ Seleccionar sin fecha
        </button>
      </div>
    </div>`;
}

/**
 * Calcula el sumatorio de pesos por fecha
 * @param {Array} planillas - Array de planillas con peso_total
 * @returns {Object} - Objeto con fechas como keys y objetos {peso, planillas, esFinDeSemana} como values
 */
function calcularSumatorioFechas(planillas) {
    const sumatorio = {};

    // Obtener todas las fechas actuales de los inputs
    const dateInputs = document.querySelectorAll(
        'input[type="date"][data-planilla-id]'
    );

    dateInputs.forEach((input) => {
        const planillaId = parseInt(input.dataset.planillaId);
        const fecha = input.value;
        const planilla = planillas.find((p) => p.id === planillaId);

        if (fecha && planilla && planilla.peso_total) {
            if (!sumatorio[fecha]) {
                sumatorio[fecha] = {
                    peso: 0,
                    planillas: 0,
                    esFinDeSemana: esFinDeSemana(fecha),
                };
            }
            sumatorio[fecha].peso += parseFloat(planilla.peso_total);
            sumatorio[fecha].planillas += 1;
        }
    });

    return sumatorio;
}

/**
 * Actualiza el contenido del sumatorio dinámico
 * @param {Array} planillas - Array de planillas
 */
function actualizarSumatorio(planillas) {
    const sumatorio = calcularSumatorioFechas(planillas);
    const contenedor = document.getElementById("resumen-contenido");

    if (!contenedor) return;

    const fechas = Object.keys(sumatorio).sort();

    if (fechas.length === 0) {
        contenedor.innerHTML =
            '<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';
        return;
    }

    const resumenHTML = fechas
        .map((fecha) => {
            const datos = sumatorio[fecha];
            const fechaFormateada = new Date(
                fecha + "T00:00:00"
            ).toLocaleDateString("es-ES", {
                weekday: "short",
                day: "2-digit",
                month: "2-digit",
                year: "numeric",
            });

            const pesoFormateado = datos.peso.toLocaleString("es-ES", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const claseFinDeSemana = datos.esFinDeSemana
                ? "bg-orange-100 border-orange-300 text-orange-800"
                : "bg-green-100 border-green-300 text-green-800";
            const iconoFinDeSemana = datos.esFinDeSemana ? "🏖️" : "📦";

            return `
            <div class="inline-block m-1 px-2 py-1 rounded border ${claseFinDeSemana}">
                <span class="font-medium">${iconoFinDeSemana} ${fechaFormateada}</span>
                <br>
                <span class="text-xs">${pesoFormateado} kg (${
                datos.planillas
            } planilla${datos.planillas !== 1 ? "s" : ""})</span>
            </div>
        `;
        })
        .join("");

    const pesoTotal = fechas.reduce(
        (total, fecha) => total + sumatorio[fecha].peso,
        0
    );
    const totalPlanillas = fechas.reduce(
        (total, fecha) => total + sumatorio[fecha].planillas,
        0
    );

    contenedor.innerHTML = `
        <div class="mb-2">${resumenHTML}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${pesoTotal.toLocaleString("es-ES", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })} kg 
            (${totalPlanillas} planilla${totalPlanillas !== 1 ? "s" : ""})
        </div>
    `;
}

async function guardarFechasPlanillas(payload) {
    const base = window.AppSalidas?.routes?.actualizarFechasPlanillas;
    if (!base)
        throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");
    const res = await fetch(base, {
        method: "PUT",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": window.AppSalidas?.csrf,
            Accept: "application/json",
        },
        body: JSON.stringify({ planillas: payload }),
    });
    if (!res.ok) {
        const t = await res.text().catch(() => "");
        throw new Error(`PUT ${base} -> ${res.status} ${t}`);
    }
    return res.json().catch(() => ({}));
}

async function cambiarFechasEntrega(planillasIds, calendar) {
    try {
        const ids = Array.from(new Set(normalizarIds(planillasIds)))
            .map(Number)
            .filter(Boolean);
        if (!ids.length)
            return Swal.fire(
                "⚠️",
                "No hay planillas en la agrupación.",
                "warning"
            );

        const planillas = await obtenerInformacionPlanillas(ids);
        if (!planillas.length)
            return Swal.fire(
                "⚠️",
                "No se han encontrado planillas.",
                "warning"
            );

        // Cabecera draggable propia para evitar depender del h2 interno de SweetAlert
        const barraDrag = `
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `;
        const html = barraDrag + construirFormularioFechas(planillas);

        const { isConfirmed } = await Swal.fire({
            title: "",
            html,
            width: Math.min(window.innerWidth * 0.98, 1200), // ⬅️ Más ancho (hasta 1200px)
            customClass: {
                popup: "w-full max-w-screen-xl", // ⬅️ Fuerza a pantalla completa en pantallas grandes
            },
            showCancelButton: true,
            confirmButtonText: "💾 Guardar",
            cancelButtonText: "Cancelar",
            focusConfirm: false,
            showClass: { popup: "swal-fade-in-zoom" },
            hideClass: { popup: "swal-fade-out" },
            didOpen: (popup) => {
                // 1) Centrar SIEMPRE al abrir (coincide con la animación que usa translate)
                centerSwal(popup);
                // 2) Habilitar drag SOLO sobre #swal-drag (sin memoria para que no “herede” posiciones)
                hacerDraggableSwal("#swal-drag", false);
                // 3) Foco suave
                setTimeout(() => {
                    const first =
                        Swal.getHtmlContainer().querySelector(
                            'input[type="date"]'
                        );
                    first?.focus({ preventScroll: true });
                }, 120);

                // 4) Agregar event listeners para actualizar estilo de fin de semana y sumatorio
                const dateInputs =
                    Swal.getHtmlContainer().querySelectorAll(
                        'input[type="date"]'
                    );
                dateInputs.forEach((input) => {
                    input.addEventListener("change", function () {
                        const isWeekend = esFinDeSemana(this.value);
                        if (isWeekend) {
                            this.classList.add("weekend-date");
                        } else {
                            this.classList.remove("weekend-date");
                        }
                        // Actualizar sumatorio dinámico
                        actualizarSumatorio(planillas);
                    });
                });

                // 5) Event listeners para expandir/colapsar elementos
                const container = Swal.getHtmlContainer();

                // Toggle individual de elementos
                container.querySelectorAll('.toggle-elementos').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const planillaId = btn.dataset.planillaId;
                        const elementos = container.querySelectorAll(`.elemento-planilla-${planillaId}`);
                        const isExpanded = btn.textContent === '▼';

                        elementos.forEach(el => {
                            el.classList.toggle('hidden', isExpanded);
                        });
                        btn.textContent = isExpanded ? '▶' : '▼';
                    });
                });

                // Expandir todos
                container.querySelector('#expandir-todos')?.addEventListener('click', () => {
                    container.querySelectorAll('.elemento-row').forEach(el => el.classList.remove('hidden'));
                    container.querySelectorAll('.toggle-elementos').forEach(btn => btn.textContent = '▼');
                });

                // Colapsar todos
                container.querySelector('#colapsar-todos')?.addEventListener('click', () => {
                    container.querySelectorAll('.elemento-row').forEach(el => el.classList.add('hidden'));
                    container.querySelectorAll('.toggle-elementos').forEach(btn => btn.textContent = '▶');
                });

                // === FUNCIONALIDAD DE SELECCIÓN MASIVA ===

                // Función para actualizar el contador y mostrar/ocultar la barra
                function actualizarBarraSeleccion() {
                    const checkboxes = container.querySelectorAll('.elemento-checkbox:checked');
                    const cantidad = checkboxes.length;
                    const barra = container.querySelector('#barra-acciones-masivas');
                    const contador = container.querySelector('#contador-seleccionados');

                    if (cantidad > 0) {
                        barra?.classList.remove('hidden');
                        if (contador) contador.textContent = cantidad;
                    } else {
                        barra?.classList.add('hidden');
                    }
                }

                // Event listeners para checkboxes de elementos
                container.querySelectorAll('.elemento-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', actualizarBarraSeleccion);
                });

                // Seleccionar todos los elementos visibles
                container.querySelector('#seleccionar-todos-elementos')?.addEventListener('click', () => {
                    // Primero expandir todos
                    container.querySelectorAll('.elemento-row').forEach(el => el.classList.remove('hidden'));
                    container.querySelectorAll('.toggle-elementos').forEach(btn => btn.textContent = '▼');
                    // Luego seleccionar todos
                    container.querySelectorAll('.elemento-checkbox').forEach(cb => {
                        cb.checked = true;
                    });
                    actualizarBarraSeleccion();
                });

                // Seleccionar solo elementos sin fecha
                container.querySelector('#seleccionar-sin-fecha')?.addEventListener('click', () => {
                    // Primero expandir todos
                    container.querySelectorAll('.elemento-row').forEach(el => el.classList.remove('hidden'));
                    container.querySelectorAll('.toggle-elementos').forEach(btn => btn.textContent = '▼');
                    // Deseleccionar todos primero
                    container.querySelectorAll('.elemento-checkbox').forEach(cb => {
                        cb.checked = false;
                    });
                    // Seleccionar solo los que no tienen fecha
                    container.querySelectorAll('.elemento-checkbox').forEach(cb => {
                        const elementoId = cb.dataset.elementoId;
                        const fechaInput = container.querySelector(`.elemento-fecha[data-elemento-id="${elementoId}"]`);
                        if (fechaInput && !fechaInput.value) {
                            cb.checked = true;
                        }
                    });
                    actualizarBarraSeleccion();
                });

                // Deseleccionar todos
                container.querySelector('#deseleccionar-todos')?.addEventListener('click', () => {
                    container.querySelectorAll('.elemento-checkbox').forEach(cb => {
                        cb.checked = false;
                    });
                    actualizarBarraSeleccion();
                });

                // Aplicar fecha masiva a seleccionados
                container.querySelector('#aplicar-fecha-masiva')?.addEventListener('click', () => {
                    const fechaMasiva = container.querySelector('#fecha-masiva')?.value;
                    if (!fechaMasiva) {
                        alert('Por favor, selecciona una fecha para aplicar');
                        return;
                    }

                    const checkboxes = container.querySelectorAll('.elemento-checkbox:checked');
                    checkboxes.forEach(cb => {
                        const elementoId = cb.dataset.elementoId;
                        const fechaInput = container.querySelector(`.elemento-fecha[data-elemento-id="${elementoId}"]`);
                        if (fechaInput) {
                            fechaInput.value = fechaMasiva;
                            fechaInput.dispatchEvent(new Event('change'));
                        }
                    });

                    // Feedback visual
                    const btn = container.querySelector('#aplicar-fecha-masiva');
                    const textoOriginal = btn.textContent;
                    btn.textContent = '✓ Aplicado';
                    btn.classList.add('bg-green-600');
                    setTimeout(() => {
                        btn.textContent = textoOriginal;
                        btn.classList.remove('bg-green-600');
                    }, 1500);
                });

                // Limpiar fecha de seleccionados
                container.querySelector('#limpiar-fecha-seleccionados')?.addEventListener('click', () => {
                    const checkboxes = container.querySelectorAll('.elemento-checkbox:checked');
                    checkboxes.forEach(cb => {
                        const elementoId = cb.dataset.elementoId;
                        const fechaInput = container.querySelector(`.elemento-fecha[data-elemento-id="${elementoId}"]`);
                        if (fechaInput) {
                            fechaInput.value = '';
                            fechaInput.dispatchEvent(new Event('change'));
                        }
                    });
                });

                // === FIN FUNCIONALIDAD DE SELECCIÓN MASIVA ===

                // Aplicar fecha de planilla a todos sus elementos
                container.querySelectorAll('.aplicar-fecha-elementos').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const planillaId = btn.dataset.planillaId;
                        const fechaPlanilla = container.querySelector(`.planilla-fecha[data-planilla-id="${planillaId}"]`)?.value;

                        if (fechaPlanilla) {
                            container.querySelectorAll(`.elemento-fecha[data-planilla-id="${planillaId}"]`).forEach(input => {
                                input.value = fechaPlanilla;
                                // Trigger change event para actualizar estilos
                                input.dispatchEvent(new Event('change'));
                            });
                        }
                    });
                });

                // 6) Ver figura del elemento (hover + click para seleccionar)
                container.querySelectorAll('.ver-figura-elemento').forEach(btn => {
                    btn.addEventListener('mouseenter', (e) => {
                        const elementoId = btn.dataset.elementoId;
                        const codigo = btn.dataset.elementoCodigo?.replace(/&quot;/g, '"').replace(/&#39;/g, "'") || '';
                        const dimensiones = btn.dataset.dimensiones?.replace(/&quot;/g, '"').replace(/&#39;/g, "'") || '';

                        if (typeof window.dibujarFiguraElemento === 'function') {
                            mostrarFiguraElementoModal(elementoId, codigo, dimensiones, btn);
                        }
                    });

                    btn.addEventListener('mouseleave', (e) => {
                        // Pequeño delay para permitir mover el mouse al modal
                        setTimeout(() => {
                            const modal = document.getElementById('modal-figura-elemento-overlay');
                            if (modal && !modal.matches(':hover')) {
                                modal.remove();
                            }
                        }, 100);
                    });

                    // Click en el ojo marca/desmarca el checkbox del elemento
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const elementoId = btn.dataset.elementoId;
                        const checkbox = container.querySelector(`.elemento-checkbox[data-elemento-id="${elementoId}"]`);
                        if (checkbox) {
                            checkbox.checked = !checkbox.checked;
                            // Llamar directamente a la lógica de actualización
                            const checkboxes = container.querySelectorAll('.elemento-checkbox:checked');
                            const cantidad = checkboxes.length;
                            const barra = container.querySelector('#barra-acciones-masivas');
                            const contador = container.querySelector('#contador-seleccionados');

                            if (cantidad > 0) {
                                barra?.classList.remove('hidden');
                                if (contador) contador.textContent = cantidad;
                            } else {
                                barra?.classList.add('hidden');
                            }
                        }
                    });
                });

                // 7) Actualizar sumatorio inicial
                setTimeout(() => {
                    actualizarSumatorio(planillas);
                }, 100);
            },
        });
        if (!isConfirmed) return;

        const container = Swal.getHtmlContainer();

        // Recolectar fechas de planillas
        const planillaInputs = container.querySelectorAll('.planilla-fecha');

        const payload = Array.from(planillaInputs).map((inp) => {
            const planillaId = Number(inp.getAttribute("data-planilla-id"));

            // Recolectar elementos de esta planilla
            const elementoInputs = container.querySelectorAll(`.elemento-fecha[data-planilla-id="${planillaId}"]`);
            const elementos = Array.from(elementoInputs).map(elInp => ({
                id: Number(elInp.getAttribute("data-elemento-id")),
                fecha_entrega: elInp.value || null
            }));

            return {
                id: planillaId,
                fecha_estimada_entrega: inp.value,
                elementos: elementos.length > 0 ? elementos : undefined
            };
        });

        const resp = await guardarFechasPlanillas(payload);
        await Swal.fire(
            resp.success ? "✅" : "⚠️",
            resp.message ||
                (resp.success
                    ? "Fechas actualizadas"
                    : "No se pudieron actualizar"),
            resp.success ? "success" : "warning"
        );

        if (resp.success && calendar) {
            calendar.refetchEvents?.();
            calendar.refetchResources?.();
        }
    } catch (err) {
        console.error("[CambiarFechasEntrega] error:", err);
        Swal.fire(
            "❌",
            err?.message || "Ocurrió un error al actualizar las fechas.",
            "error"
        );
    }
}

/* ===================== Menú contextual calendario ===================== */
export function attachEventoContextMenu(info, calendar) {
    info.el.addEventListener("mousedown", closeMenu);

    info.el.addEventListener("contextmenu", (e) => {
        e.preventDefault();
        e.stopPropagation();

        const ev = info.event;
        const p = ev.extendedProps || {};
        const tipo = p.tipo || "planilla";

        // Construir header con información adicional para salidas
        let headerInfo = "";
        if (tipo === "salida") {
            // Mostrar clientes
            if (p.clientes && Array.isArray(p.clientes) && p.clientes.length > 0) {
                const clientesTexto = p.clientes.map(c => c.nombre).filter(Boolean).join(", ");
                if (clientesTexto) {
                    headerInfo += `<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${clientesTexto}</span>`;
                }
            }
            // Mostrar obras
            if (p.obras && Array.isArray(p.obras) && p.obras.length > 0) {
                headerInfo += `<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ `;
                headerInfo += p.obras.map(o => {
                    const codigo = o.codigo ? `(${o.codigo})` : '';
                    return `${o.nombre} ${codigo}`;
                }).join(', ');
                headerInfo += `</span>`;
            }
        }

        const headerHtml = `
      <div style="padding:10px 12px; font-weight:600;">
        ${ev.title ?? "Evento"}${headerInfo}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(ev.start).toLocaleString()} — ${new Date(
            ev.end
        ).toLocaleString()}
        </span>
      </div>
    `;

        let items = [];

        if (tipo === "planilla") {
            const planillasIds = extraerPlanillasIds(ev);
            items = [
                {
                    label: "Gestionar Salidas y Paquetes",
                    icon: "📦",
                    onClick: () => window.location.href = `/salidas-ferralla/gestionar-salidas?planillas=${planillasIds.join(",")}`,
                },
                {
                    label: "Cambiar fechas de entrega",
                    icon: "🗓️",
                    onClick: () => cambiarFechasEntrega(planillasIds, calendar),
                },
            ];
        } else if (tipo === "salida") {
            // El ID del evento ahora es directamente el salida_id
            const salidaId = p.salida_id || ev.id;
            const empresaId = p.empresa_id || null;
            const empresaNombre = p.empresa || "";

            items = [
                {
                    label: "Abrir salida",
                    icon: "🧾",
                    onClick: () => window.open(`/salidas-ferralla/${salidaId}`, "_blank"),
                },
                {
                    label: "Gestionar paquetes",
                    icon: "📦",
                    onClick: () => gestionarPaquetesSalida(salidaId, calendar),
                },
                {
                    label: "Agregar comentario",
                    icon: "✍️",
                    onClick: () =>
                        comentarSalida(salidaId, p.comentario || "", calendar),
                },
            ];
        } else {
            items = [
                {
                    label: "Abrir",
                    icon: "🧾",
                    onClick: () => window.open(p.url || "#", "_blank"),
                },
            ];
        }

        openActionsMenu(e.clientX, e.clientY, { headerHtml, items });
    });
}

/* ===================== Utils: centrar y drag ===================== */

function centerSwal(popup) {
    // Quita cualquier transform para medir bien
    popup.style.transform = "none";
    popup.style.position = "fixed";
    popup.style.margin = "0";

    // Asegura layout antes de medir
    const w = popup.offsetWidth;
    const h = popup.offsetHeight;

    const left = Math.max(0, Math.round((window.innerWidth - w) / 2));
    const top = Math.max(0, Math.round((window.innerHeight - h) / 2));

    popup.style.left = `${left}px`;
    popup.style.top = `${top}px`;
}

/* Nota: en pointerdown pasamos a coordenadas absolutas y quitamos translate.
   Como la animación ya incluye el translate, el centrado inicial no se pierde. */
function hacerDraggableSwal(handleSelector = ".swal2-title", remember = false) {
    const popup = Swal.getPopup();
    const container = Swal.getHtmlContainer();
    let handle =
        (handleSelector
            ? container?.querySelector(handleSelector) ||
              popup?.querySelector(handleSelector)
            : null) || popup;
    if (!popup || !handle) return;

    if (remember && hacerDraggableSwal.__lastPos) {
        popup.style.left = hacerDraggableSwal.__lastPos.left;
        popup.style.top = hacerDraggableSwal.__lastPos.top;
        popup.style.transform = "none";
    }

    handle.style.cursor = "move";
    handle.style.touchAction = "none";

    const isInteractive = (el) =>
        el.closest?.(
            "input, textarea, select, button, a, label, [contenteditable]"
        ) != null;

    let isDown = false;
    let startX = 0,
        startY = 0;
    let startLeft = 0,
        startTop = 0;

    const onPointerDown = (e) => {
        if (!handle.contains(e.target) || isInteractive(e.target)) return;

        isDown = true;
        document.body.style.userSelect = "none";

        const rect = popup.getBoundingClientRect();
        popup.style.left = `${rect.left}px`;
        popup.style.top = `${rect.top}px`;
        popup.style.transform = "none";

        startLeft = parseFloat(popup.style.left || rect.left);
        startTop = parseFloat(popup.style.top || rect.top);
        startX = e.clientX;
        startY = e.clientY;

        document.addEventListener("pointermove", onPointerMove);
        document.addEventListener("pointerup", onPointerUp, { once: true });
    };

    const onPointerMove = (e) => {
        if (!isDown) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        let nextLeft = startLeft + dx;
        let nextTop = startTop + dy;

        const w = popup.offsetWidth,
            h = popup.offsetHeight;
        const minLeft = -w + 40,
            maxLeft = window.innerWidth - 40;
        const minTop = -h + 40,
            maxTop = window.innerHeight - 40;
        nextLeft = Math.max(minLeft, Math.min(maxLeft, nextLeft));
        nextTop = Math.max(minTop, Math.min(maxTop, nextTop));

        popup.style.left = `${nextLeft}px`;
        popup.style.top = `${nextTop}px`;
    };

    const onPointerUp = () => {
        isDown = false;
        document.body.style.userSelect = "";
        if (remember) {
            hacerDraggableSwal.__lastPos = {
                left: popup.style.left,
                top: popup.style.top,
            };
        }
        document.removeEventListener("pointermove", onPointerMove);
    };

    handle.addEventListener("pointerdown", onPointerDown);
}

/* ===================== Livewire Event Listeners ===================== */
// Escuchar el evento comentarioGuardado de Livewire
document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('comentarioGuardado', (event) => {
        const { salidaId, comentario } = event.detail;

        // Obtener referencia al calendario
        const calendar = window._calendarRef;

        if (calendar) {
            // Actualizar solo el evento específico sin recargar todo el calendario
            const calendarEvent = calendar.getEventById(`salida-${salidaId}`);

            if (calendarEvent) {
                // Actualizar las propiedades extendidas del evento
                calendarEvent.setExtendedProp('comentario', comentario);

                // Si el evento tiene un tooltip, actualizarlo
                if (calendarEvent._def && calendarEvent._def.extendedProps) {
                    calendarEvent._def.extendedProps.comentario = comentario;
                }
            }

            // Mostrar notificación de éxito
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Comentario guardado',
                    text: 'El comentario se ha guardado correctamente',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        }
    });
});
