import { R } from "../config.js";

/**
 * Abre el diálogo para generar turnos desde el calendario
 * @param {string} fechaISO - Fecha ISO seleccionada
 * @param {number} maquinaId - ID de la máquina/recurso desde el calendario
 * @param {Array} trabajadores - Lista de trabajadores disponibles
 * @returns {Promise<object|null>} - Retorna el resultado o null si se canceló
 */
export async function generarTurnosDialog(fechaISO, maquinaId, trabajadores) {
    // Filtrar trabajadores que coincidan con la máquina seleccionada
    const trabajadoresFiltrados = trabajadores.filter(
        (t) => t.maquina_id === parseInt(maquinaId)
    );

    // Crear opciones para el select de trabajadores
    const trabajadoresOptions = trabajadoresFiltrados.length > 0
        ? trabajadoresFiltrados
              .map(
                  (t) =>
                      `<option value="${t.id}">${t.nombre_completo} - ${t.categoria?.nombre || "Sin categoría"}</option>`
              )
              .join("")
        : '<option value="" disabled>No hay trabajadores para esta máquina</option>';

    const { value: formValues } = await Swal.fire({
        title: "🔧 Generar Turnos",
        html: `
            <div class="text-left space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Fecha inicio:</strong> ${fechaISO}<br>
                        <strong>Máquina:</strong> ID ${maquinaId}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Trabajador <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-trabajador" class="swal2-input w-full" required>
                        <option value="" disabled selected>Selecciona un trabajador</option>
                        ${trabajadoresOptions}
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Alcance de generación <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-alcance" class="swal2-input w-full">
                        <option value="un_dia">Solo este día (${fechaISO})</option>
                        <option value="resto_año">Desde ${fechaISO} hasta fin de año</option>
                    </select>
                </div>

                <div class="mb-4" id="turno-inicio-container" style="display: none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Turno inicial (para diurnos) <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-turno-inicio" class="swal2-input w-full">
                        <option value="mañana">Mañana</option>
                        <option value="tarde">Tarde</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Los turnos alternarán cada viernes</p>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-xs text-yellow-800">
                        <strong>Nota:</strong> Se saltarán automáticamente sábados, domingos, festivos y días con vacaciones.
                    </p>
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: "Generar Turnos",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#3b82f6",
        cancelButtonColor: "#6b7280",
        width: "600px",
        didOpen: () => {
            const trabajadorSelect = document.getElementById("swal-trabajador");
            const turnoContainer = document.getElementById("turno-inicio-container");

            // Mostrar selector de turno inicial si el trabajador es diurno
            trabajadorSelect.addEventListener("change", () => {
                const trabajadorId = parseInt(trabajadorSelect.value);
                const trabajador = trabajadoresFiltrados.find((t) => t.id === trabajadorId);

                if (trabajador && trabajador.turno === "diurno") {
                    turnoContainer.style.display = "block";
                } else {
                    turnoContainer.style.display = "none";
                }
            });
        },
        preConfirm: () => {
            const trabajadorId = document.getElementById("swal-trabajador").value;
            const alcance = document.getElementById("swal-alcance").value;
            const turnoInicio = document.getElementById("swal-turno-inicio").value;

            if (!trabajadorId) {
                Swal.showValidationMessage("Debes seleccionar un trabajador");
                return false;
            }

            return {
                trabajador_id: trabajadorId,
                alcance: alcance,
                turno_inicio: turnoInicio,
            };
        },
    });

    if (!formValues) return null;

    // Llamar al backend para generar turnos
    try {
        const trabajador = trabajadoresFiltrados.find(
            (t) => t.id === parseInt(formValues.trabajador_id)
        );

        const url = `/profile/generar-turnos-calendario`;
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": window.AppPlanif.csrf,
                Accept: "application/json",
            },
            body: JSON.stringify({
                user_id: formValues.trabajador_id,
                maquina_id: maquinaId,
                fecha_inicio: fechaISO,
                alcance: formValues.alcance,
                turno_inicio: formValues.turno_inicio,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `Error HTTP ${response.status}`);
        }

        await Swal.fire({
            icon: "success",
            title: "¡Turnos generados!",
            html: `
                <p>Se han generado los turnos para:</p>
                <p class="font-semibold mt-2">${trabajador.nombre_completo}</p>
                <p class="text-sm text-gray-600 mt-1">
                    ${data.turnos_creados || 0} turnos creados
                </p>
            `,
            timer: 3000,
            showConfirmButton: false,
        });

        return data;
    } catch (error) {
        console.error("Error al generar turnos:", error);
        await Swal.fire({
            icon: "error",
            title: "Error",
            text: error.message || "No se pudieron generar los turnos",
            confirmButtonText: "Aceptar",
        });
        return null;
    }
}
