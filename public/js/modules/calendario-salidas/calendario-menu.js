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
async function gestionarPaquetesSalida(salidaId, calendar) {
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

        const { salida, paquetesAsignados, paquetesDisponibles } = await infoRes.json();

        // Construir y mostrar interfaz
        mostrarInterfazGestionPaquetesSalida(
            salida,
            paquetesAsignados,
            paquetesDisponibles,
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
    calendar
) {
    const html = construirInterfazGestionPaquetesSalida(
        salida,
        paquetesAsignados,
        paquetesDisponibles
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
    paquetesDisponibles
) {
    // Calcular totales de la salida
    const totalKgAsignados = paquetesAsignados.reduce(
        (sum, p) => sum + (parseFloat(p.peso) || 0),
        0
    );

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
            </div>
        </div>
    `;

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
                        class="paquetes-zona-salida drop-zone"
                        data-zona="asignados"
                        style="min-height: 400px; border: 2px dashed #10b981; border-radius: 8px; padding: 8px;"
                    >
                        ${construirPaquetesHTMLSalida(paquetesAsignados)}
                    </div>
                </div>

                <!-- Paquetes disponibles -->
                <div class="bg-gray-50 border-2 border-gray-300 rounded-lg p-3">
                    <div class="font-semibold text-gray-900 mb-2">📋 Paquetes Disponibles</div>
                    <div
                        class="paquetes-zona-salida drop-zone"
                        data-zona="disponibles"
                        style="min-height: 400px; border: 2px dashed #6b7280; border-radius: 8px; padding: 8px;"
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
        return '<div class="text-gray-400 text-sm text-center py-4">Sin paquetes</div>';
    }

    return paquetes
        .map(
            (paquete) => `
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${paquete.id}"
        >
            <div class="flex items-center justify-between text-xs">
                <span class="font-medium">📦 Paquete #${paquete.id}</span>
                <span class="text-gray-600">${parseFloat(paquete.peso || 0).toFixed(2)} kg</span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
                <div>Planilla: ${paquete.planilla?.codigo || paquete.planilla_id}</div>
                <div>Obra: ${paquete.planilla?.obra?.obra || "N/A"}</div>
                ${paquete.planilla?.obra?.cod_obra ? `<div>Código: ${paquete.planilla.obra.cod_obra}</div>` : ""}
            </div>
        </div>
    `
        )
        .join("");
}

/* ===================== Inicializar drag and drop salida ===================== */
function inicializarDragAndDropSalida() {
    let draggedElement = null;

    // Eventos de drag para los paquetes
    document.querySelectorAll(".paquete-item-salida").forEach((item) => {
        item.addEventListener("dragstart", (e) => {
            draggedElement = item;
            item.style.opacity = "0.5";
        });

        item.addEventListener("dragend", (e) => {
            item.style.opacity = "1";
            draggedElement = null;
        });
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

            if (draggedElement) {
                // Remover placeholder si existe
                const placeholder = zone.querySelector(".text-gray-400");
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
        const pesoText = p.querySelector(".text-gray-600")?.textContent;
        const peso = parseFloat(pesoText) || 0;
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

            return `
<tr style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${
                i * 18
            }ms;">
  <td class="px-2 py-1 text-xs">${p.id}</td>
  <td class="px-2 py-1 text-xs">${codObra}</td>
  <td class="px-2 py-1 text-xs">${nombreObra}</td>
  <td class="px-2 py-1 text-xs">${seccionObra}</td>
  <td class="px-2 py-1 text-xs">${descripcionObra}</td>
  <td class="px-2 py-1 text-xs">${codigoPlanilla}</td>
  <td class="px-2 py-1 text-xs text-right font-medium">${pesoTotal}</td>
  <td class="px-2 py-1">
    <input type="date" class="swal2-input !m-0 !w-auto" data-planilla-id="${
        p.id
    }" value="${fechaISO}">
  </td>
</tr>`;
        })

        .join("");

    return `
    <div class="text-left">
      <div class="text-sm text-gray-600 mb-2">
        Edita la <strong>fecha estimada de entrega</strong> y guarda.
      </div>
      
      <!-- Sumatorio dinámico por fechas -->
      <div id="sumatorio-fechas" class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="text-sm font-medium text-blue-800 mb-2">📊 Resumen por fecha:</div>
        <div id="resumen-contenido" class="text-xs text-blue-700">
          Cambia las fechas para ver el resumen...
        </div>
      </div>
      
      <div class="overflow-auto" style="max-height:45vh;border:1px solid #e5e7eb;border-radius:6px;">
        <table class="min-w-full text-sm">
        <thead class="sticky top-0 bg-white">
  <tr>
    <th class="px-2 py-1 text-left">ID</th>
    <th class="px-2 py-1 text-left">Cod. Obra</th>
    <th class="px-2 py-1 text-left">Obra</th>
    <th class="px-2 py-1 text-left">Sección</th>
    <th class="px-2 py-1 text-left">Descripción</th>
    <th class="px-2 py-1 text-left">Planilla</th>
    <th class="px-2 py-1 text-left">Peso Total</th>
    <th class="px-2 py-1 text-left">Fecha Entrega</th>
  </tr>
</thead>

          <tbody>${filas}</tbody>
        </table>
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

                // 5) Actualizar sumatorio inicial
                setTimeout(() => {
                    actualizarSumatorio(planillas);
                }, 100);
            },
        });
        if (!isConfirmed) return;

        const inputs = Swal.getHtmlContainer().querySelectorAll(
            "input[data-planilla-id]"
        );

        const payload = Array.from(inputs).map((inp) => ({
            id: Number(inp.getAttribute("data-planilla-id")),
            fecha_estimada_entrega: inp.value, // input type="date" ya devuelve formato YYYY-MM-DD
        }));

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

        const headerHtml = `
      <div style="padding:10px 12px; font-weight:600;">
        ${ev.title ?? "Evento"}<br>
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
            // El ID del evento viene en formato "salida_id-obra_id", extraer solo el salida_id
            const salidaId = typeof ev.id === 'string' ? ev.id.split('-')[0] : ev.id;

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
                    label: "Asignar código SAGE",
                    icon: "🏷️",
                    onClick: () =>
                        asignarCodigoSalida(salidaId, p.codigo || "", calendar),
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
