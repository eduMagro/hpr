import { R, DATA } from "../config.js";

/**
 * Detecta el turno según la hora usando la configuración del backend
 * @param {string} hora - Hora en formato HH:MM
 * @returns {string} - 'mañana', 'tarde' o 'noche'
 */
function detectarTurnoPorHora(hora) {
    if (!hora) return null;
    const [h] = hora.split(':').map(Number);

    // Obtener configuración de turnos del backend
    // turnosConfig tiene la estructura completa, turnos es el array simple
    const turnosConfig = window.AppPlanif?.turnosConfig?.turnos || window.AppPlanif?.turnos || [];

    for (const turno of turnosConfig) {
        if (!turno.hora_inicio || !turno.hora_fin) continue;

        const [hIni] = turno.hora_inicio.split(':').map(Number);
        const [hFin] = turno.hora_fin.split(':').map(Number);
        const esNocturno = hFin < hIni;

        let enTurno = false;
        if (esNocturno) {
            // Turno nocturno: 22:00 - 06:00
            enTurno = h >= hIni || h < hFin;
        } else {
            // Turnos normales
            enTurno = h >= hIni && h < hFin;
        }

        if (enTurno) {
            return turno.nombre;
        }
    }

    // Fallback: usar slots visuales del calendario
    // Noche: 00:00-08:00, Mañana: 08:00-16:00, Tarde: 16:00-24:00
    if (h >= 0 && h < 8) return 'noche';
    if (h >= 8 && h < 16) return 'mañana';
    return 'tarde';
}

/**
 * Abre el diálogo para generar turnos desde el calendario
 * @param {string} fechaISO - Fecha ISO seleccionada
 * @param {number} maquinaId - ID de la máquina/recurso desde el calendario
 * @param {string} maquinaNombre - Nombre de la máquina
 * @param {string} horaISO - Hora del clic (opcional) en formato HH:MM
 * @returns {Promise<object|null>} - Retorna el resultado o null si se canceló
 */
export async function generarTurnosDialog(fechaISO, maquinaId, maquinaNombre, horaISO = null) {
    // Detectar turno automáticamente según la hora del clic
    const turnoDetectado = detectarTurnoPorHora(horaISO);
    console.log("[generarTurnos] horaISO:", horaISO, "turnoDetectado:", turnoDetectado);
    // Obtener todos los trabajadores con rol operario y sus asignaciones
    let todosOperarios = [];
    let operariosSinTurno = [];
    let operariosMaquina = [];

    try {
        const response = await fetch(`/api/usuarios/operarios-agrupados?fecha=${fechaISO}&maquina_id=${maquinaId}`, {
            headers: {
                'X-CSRF-TOKEN': window.AppPlanif.csrf,
                'Accept': 'application/json',
            }
        });

        if (response.ok) {
            const data = await response.json();
            todosOperarios = data.todos || [];
            operariosSinTurno = data.sin_turno || [];
            operariosMaquina = data.de_maquina || [];
        } else {
            console.error('Error al obtener operarios:', response.status);
        }
    } catch (error) {
        console.error('Error al obtener operarios:', error);
    }

    // Función para generar opciones de select
    const generarOpciones = (lista, mensajeVacio = 'No hay trabajadores') => {
        if (lista.length === 0) {
            return `<option value="" disabled>${mensajeVacio}</option>`;
        }
        return lista.map(t =>
            `<option value="${t.id}">${t.name} ${t.primer_apellido || ''} ${t.segundo_apellido || ''}</option>`
        ).join('');
    };

    const opcionesSinTurno = generarOpciones(operariosSinTurno, 'No hay operarios sin turno este día');
    const opcionesTodosOperarios = generarOpciones(todosOperarios, 'No hay operarios disponibles');
    const opcionesMaquina = generarOpciones(operariosMaquina, 'No hay operarios asignados a esta máquina');

    // Texto del turno detectado para mostrar
    const turnoTexto = turnoDetectado
        ? `<span class="text-green-600 font-semibold">${turnoDetectado.charAt(0).toUpperCase() + turnoDetectado.slice(1)}</span>`
        : '<span class="text-gray-400">No detectado</span>';

    const { value: formValues } = await Swal.fire({
        title: "🔧 Generar Turnos",
        html: `
            <div class="text-left space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Fecha inicio:</strong> ${fechaISO}<br>
                        <strong>Máquina:</strong> ${maquinaNombre}<br>
                        <strong>Turno detectado:</strong> ${turnoTexto}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Sin turno asignado este día <span class="text-green-600">(${operariosSinTurno.length})</span>
                    </label>
                    <select id="swal-trabajador-sin-turno" class="swal2-input w-full">
                        <option value="" selected>Seleccionar...</option>
                        ${opcionesSinTurno}
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Asignados a ${maquinaNombre} <span class="text-blue-600">(${operariosMaquina.length})</span>
                    </label>
                    <select id="swal-trabajador-maquina" class="swal2-input w-full">
                        <option value="" selected>Seleccionar...</option>
                        ${opcionesMaquina}
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Todos los operarios <span class="text-gray-600">(${todosOperarios.length})</span>
                    </label>
                    <select id="swal-trabajador-todos" class="swal2-input w-full">
                        <option value="" selected>Seleccionar...</option>
                        ${opcionesTodosOperarios}
                    </select>
                </div>

                <input type="hidden" id="swal-trabajador" value="" />
                <input type="hidden" id="swal-turno-detectado" value="${turnoDetectado || ''}" />

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Alcance de generación <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-alcance" class="swal2-input w-full">
                        <option value="un_dia">Solo este día (${fechaISO})</option>
                        <option value="dos_semanas">Hasta el viernes de la semana siguiente</option>
                        <option value="resto_año">Desde ${fechaISO} hasta fin de año</option>
                    </select>
                </div>

                <div class="mb-4" id="turno-inicio-container" style="display: ${turnoDetectado ? 'none' : 'block'};">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Turno inicial (para diurnos) <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-turno-inicio" class="swal2-input w-full">
                        <option value="mañana" ${turnoDetectado === 'mañana' ? 'selected' : ''}>Mañana</option>
                        <option value="tarde" ${turnoDetectado === 'tarde' ? 'selected' : ''}>Tarde</option>
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
            const trabajadorInput = document.getElementById("swal-trabajador");
            const turnoContainer = document.getElementById("turno-inicio-container");
            const selectSinTurno = document.getElementById("swal-trabajador-sin-turno");
            const selectMaquina = document.getElementById("swal-trabajador-maquina");
            const selectTodos = document.getElementById("swal-trabajador-todos");

            // Combinar todos los operarios para búsqueda de turno
            const todosParaBusqueda = [...operariosSinTurno, ...operariosMaquina, ...todosOperarios];

            // Función para actualizar el trabajador seleccionado
            function actualizarTrabajador(trabajadorId, selectActivo) {
                trabajadorInput.value = trabajadorId;

                // Limpiar otros selects
                if (selectActivo !== selectSinTurno) selectSinTurno.value = "";
                if (selectActivo !== selectMaquina) selectMaquina.value = "";
                if (selectActivo !== selectTodos) selectTodos.value = "";

                // Buscar trabajador para verificar si es diurno
                const trabajador = todosParaBusqueda.find(t => t.id === parseInt(trabajadorId));
                if (trabajador && trabajador.turno === "diurno") {
                    turnoContainer.style.display = "block";
                } else {
                    turnoContainer.style.display = "none";
                }
            }

            // Event listeners para cada select
            selectSinTurno.addEventListener("change", (e) => {
                if (e.target.value) actualizarTrabajador(e.target.value, selectSinTurno);
            });

            selectMaquina.addEventListener("change", (e) => {
                if (e.target.value) actualizarTrabajador(e.target.value, selectMaquina);
            });

            selectTodos.addEventListener("change", (e) => {
                if (e.target.value) actualizarTrabajador(e.target.value, selectTodos);
            });
        },
        preConfirm: () => {
            const trabajadorId = document.getElementById("swal-trabajador").value;
            const alcance = document.getElementById("swal-alcance").value;
            const turnoInicio = document.getElementById("swal-turno-inicio").value;
            const turnoDetectadoValue = document.getElementById("swal-turno-detectado").value;

            if (!trabajadorId) {
                Swal.showValidationMessage("Debes seleccionar un trabajador");
                return false;
            }

            return {
                trabajador_id: trabajadorId,
                alcance: alcance,
                turno_inicio: turnoInicio,
                turno_detectado: turnoDetectadoValue || null,
            };
        },
    });

    if (!formValues) return null;

    // Llamar al backend para generar turnos
    try {
        // Buscar el trabajador en todas las listas
        const todosParaBusqueda = [...operariosSinTurno, ...operariosMaquina, ...todosOperarios];
        const trabajador = todosParaBusqueda.find(
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
                turno_detectado: formValues.turno_detectado,
            }),
        });

        const data = await response.json();
        console.log("[generarTurnos] Respuesta backend:", data);
        if (data.eventos && data.eventos.length > 0) {
            console.log("[generarTurnos] Primer evento:", data.eventos[0]);
        }

        if (!response.ok) {
            throw new Error(data.message || `Error HTTP ${response.status}`);
        }

        // Retornar los datos incluyendo los eventos para agregarlos al calendario
        return {
            ...data,
            eventos: data.eventos || []
        };
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
