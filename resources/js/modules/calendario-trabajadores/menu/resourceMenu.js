import { openActionsMenu } from "./baseMenu.js";
import { CSRF, R } from "../config.js";

/**
 * Abre el menú contextual para un recurso (máquina) en vista semanal
 * @param {number} x - Posición X del click
 * @param {number} y - Posición Y del click
 * @param {object} params - Parámetros del recurso
 * @param {object} calendar - Instancia del calendario
 */
export function openResourceMenu(x, y, { resourceId, resourceTitle, weekStart }, calendar) {
    const items = [
        {
            icon: "📋",
            label: "Repetir semana anterior",
            onClick: async () => {
                await repetirSemanaAnteriorDialog(resourceId, resourceTitle, weekStart, calendar);
            },
        },
    ];

    openActionsMenu(x, y, {
        headerHtml: `<div>Acciones para <b>${resourceTitle}</b></div>`,
        items,
    });
}

/**
 * Diálogo para repetir la semana anterior
 */
async function repetirSemanaAnteriorDialog(maquinaId, maquinaNombre, weekStart, calendar) {
    const { value: formValues } = await Swal.fire({
        title: "📋 Repetir Semana Anterior",
        html: `
            <div class="text-left space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Máquina:</strong> ${maquinaNombre}<br>
                        <strong>Semana actual:</strong> ${weekStart}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Duración de la copia <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-duracion" class="swal2-input w-full">
                        <option value="1">Una semana (semana actual)</option>
                        <option value="2">Dos semanas (actual y siguiente)</option>
                    </select>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-xs text-yellow-800">
                        <strong>Nota:</strong> Se copiarán los turnos de la semana anterior
                        para esta máquina. Los turnos existentes NO serán sobrescritos.
                    </p>
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: "Copiar Turnos",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#3b82f6",
        cancelButtonColor: "#6b7280",
        width: "500px",
        preConfirm: () => {
            return {
                duracion: document.getElementById("swal-duracion").value,
            };
        },
    });

    if (!formValues) return;

    // Llamar al backend
    try {
        const response = await fetch("/asignaciones-turno/repetir-semana-maquina", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF(),
                Accept: "application/json",
            },
            body: JSON.stringify({
                maquina_id: maquinaId,
                semana_inicio: weekStart,
                duracion_semanas: parseInt(formValues.duracion),
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `Error HTTP ${response.status}`);
        }

        // Agregar los nuevos eventos al calendario
        if (data.eventos && data.eventos.length > 0) {
            data.eventos.forEach((evento) => {
                // Verificar si el evento ya existe
                const existente = calendar.getEventById(evento.id);
                if (!existente) {
                    calendar.addEvent({
                        id: evento.id,
                        title: evento.title,
                        start: evento.start,
                        end: evento.end,
                        resourceId: evento.resourceId,
                        backgroundColor: evento.backgroundColor,
                        borderColor: evento.borderColor,
                        textColor: evento.textColor || "#000000",
                        extendedProps: evento.extendedProps || {},
                    });
                }
            });
        }

        await Swal.fire({
            icon: "success",
            title: "Turnos copiados",
            html: `Se han copiado <b>${data.turnos_creados || 0}</b> turnos correctamente.`,
            timer: 2000,
            showConfirmButton: false,
        });
    } catch (error) {
        console.error("Error al copiar turnos:", error);
        await Swal.fire({
            icon: "error",
            title: "Error",
            text: error.message || "No se pudieron copiar los turnos",
            confirmButtonText: "Aceptar",
        });
    }
}
