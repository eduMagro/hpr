import { openActionsMenu } from "../menu/baseMenu.js";
import { crearFestivo } from "../dialogs/festivo.js";
import { generarTurnosDialog } from "../dialogs/generarTurnos.js";
import { DATA, CSRF } from "../config.js";

/** Copia eventos (no festivos) de un día a otro, manteniendo horas y recurso */
async function copiarRegistrosDia({ fromISO, toISO, calendar }) {
    // confirma
    const ok = await Swal.fire({
        icon: "question",
        title: "Copiar registros",
        html: `¿Copiar registros de <b>${fromISO}</b> a <b>${toISO}</b>?`,
        showCancelButton: true,
        confirmButtonText: "Copiar",
        cancelButtonText: "Cancelar",
    }).then((r) => r.isConfirmed);
    if (!ok) return;

    const evs = calendar
        .getEvents()
        .filter((ev) => !ev.extendedProps?.es_festivo); // solo trabajadores

    // Para evitar duplicados básicos: índice por (title, resourceId, fecha)
    const yaExiste = (title, resourceId, fechaISO) => {
        return evs.some((ev) => {
            const rId =
                ev.getResources?.()[0]?.id ??
                ev.extendedProps?.resourceId ??
                null;
            return (
                ev.title === title &&
                String(rId) === String(resourceId) &&
                (ev.startStr || ev.start?.toISOString()).slice(0, 10) ===
                    fechaISO
            );
        });
    };

    // Filtra los del día origen
    const delOrigen = evs.filter(
        (ev) =>
            (ev.startStr || ev.start?.toISOString()).slice(0, 10) === fromISO
    );

    let creados = 0;
    for (const ev of delOrigen) {
        const res = ev.getResources ? ev.getResources() : [];
        const resourceId = res?.[0]?.id ?? ev.extendedProps?.resourceId ?? null;

        // Construye nuevas fechas conservando HH:mm
        const start = ev.start ? new Date(ev.start) : null;
        const end = ev.end ? new Date(ev.end) : null;

        const hhmm = start
            ? `${String(start.getHours()).padStart(2, "0")}:${String(
                  start.getMinutes()
              ).padStart(2, "0")}`
            : "08:00";
        const hhmmEnd = end
            ? `${String(end.getHours()).padStart(2, "0")}:${String(
                  end.getMinutes()
              ).padStart(2, "0")}`
            : null;

        const startNew = new Date(`${toISO}T${hhmm}:00`);
        const endNew = hhmmEnd ? new Date(`${toISO}T${hhmmEnd}:00`) : null;

        // evita duplicado
        if (yaExiste(ev.title, resourceId, toISO)) continue;

        calendar.addEvent({
            id: `tmp-copy-${Date.now()}-${Math.random().toString(36).slice(2)}`, // id temporal
            title: ev.title,
            start: startNew.toISOString(),
            end: endNew ? endNew.toISOString() : null,
            resourceId: resourceId ?? undefined, // si hay uno
            allDay: ev.allDay,
            backgroundColor: ev.backgroundColor,
            borderColor: ev.borderColor,
            textColor: ev.textColor,
            extendedProps: { ...ev.extendedProps },
        });

        creados++;
    }

    Swal.fire({
        icon: "success",
        title: "Copiado completado",
        html: `Se han creado <b>${creados}</b> registros en ${toISO}.`,
        timer: 1400,
        showConfirmButton: false,
    });

    // TODO (persistencia): aquí puedes llamar a tu backend para guardar cada asignación creada
    // o bien exponer un endpoint tipo POST /asignaciones-turno/copiar-dia { from, to }
}

/**
 * Copia los turnos de la semana anterior para una máquina específica
 * @param {string} maquinaId - ID de la máquina
 * @param {string} maquinaNombre - Nombre de la máquina
 * @param {string} fechaISO - Fecha de referencia (se usará para calcular la semana)
 * @param {number} duracionSemanas - 1 o 2 semanas
 * @param {object} calendar - Instancia del calendario
 */
async function copiarSemanaAnterior({ maquinaId, maquinaNombre, fechaISO, duracionSemanas, calendar }) {
    const duracionTexto = duracionSemanas === 1 ? "la semana actual" : "las próximas 2 semanas";

    const ok = await Swal.fire({
        icon: "question",
        title: "Copiar semana anterior",
        html: `¿Copiar los turnos de la semana anterior de <b>${maquinaNombre}</b> para ${duracionTexto}?`,
        showCancelButton: true,
        confirmButtonText: "Copiar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#3b82f6",
    }).then((r) => r.isConfirmed);

    if (!ok) return;

    try {
        // Calcular el inicio de la semana actual basándose en la fecha
        const fecha = new Date(fechaISO);
        const diaSemana = fecha.getDay();
        const diffLunes = diaSemana === 0 ? -6 : 1 - diaSemana;
        const inicioSemana = new Date(fecha);
        inicioSemana.setDate(fecha.getDate() + diffLunes);
        const semanaInicio = inicioSemana.toISOString().slice(0, 10);

        const response = await fetch("/asignaciones-turno/repetir-semana-maquina", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF(),
                Accept: "application/json",
            },
            body: JSON.stringify({
                maquina_id: maquinaId,
                semana_inicio: semanaInicio,
                duracion_semanas: duracionSemanas,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `Error HTTP ${response.status}`);
        }

        // Agregar los nuevos eventos al calendario
        if (data.eventos && data.eventos.length > 0) {
            data.eventos.forEach((evento) => {
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

export function openCellMenu(x, y, { fechaISO, resourceId }, calendar, maquinas) {
    // helpers para fechas vecinas
    const prevISO = new Date(fechaISO);
    prevISO.setDate(prevISO.getDate() - 1);
    const nextISO = new Date(fechaISO);
    nextISO.setDate(nextISO.getDate() + 1);
    const prevStr = prevISO.toISOString().slice(0, 10);
    const nextStr = nextISO.toISOString().slice(0, 10);

    // Obtener el nombre de la máquina si tenemos resourceId
    const maquinaNombre = resourceId
        ? maquinas.find((m) => String(m.id) === String(resourceId))?.title || `Máquina ${resourceId}`
        : "Seleccione una máquina";

    const items = [
            {
                icon: "📅",
                label: "Crear festivo este día",
                onClick: async () => {
                    const festivo = await crearFestivo(fechaISO);
                    if (!festivo) return;

                    const start = new Date(festivo.fecha + "T00:00:00");
                    const end = new Date(start);
                    end.setDate(end.getDate() + 1);
                    const resourceIds = (maquinas || []).map((m) => m.id);

                    calendar.addEvent({
                        id: "festivo-" + festivo.id,
                        title: festivo.titulo,
                        start: start.toISOString(),
                        end: end.toISOString(),
                        allDay: true,
                        resourceIds,
                        backgroundColor: "#ff0000",
                        borderColor: "#b91c1c",
                        textColor: "#ffffff",
                        editable: true,
                        classNames: ["evento-festivo"],
                        extendedProps: {
                            es_festivo: true,
                            festivo_id: festivo.id,
                            entrada: null,
                            salida: null,
                        },
                    });

                    Swal.fire({
                        icon: "success",
                        title: "Festivo creado",
                        timer: 1200,
                        showConfirmButton: false,
                    });
                },
            },
            {
                // NUEVO: copiar del día anterior a la fecha seleccionada
                icon: "⬅️",
                label: `Copiar registros del día anterior (${prevStr} → ${fechaISO})`,
                onClick: () =>
                    copiarRegistrosDia({
                        fromISO: prevStr,
                        toISO: fechaISO,
                        calendar,
                    }),
            },
            {
                // NUEVO: copiar del día siguiente a la fecha seleccionada
                icon: "➡️",
                label: `Copiar registros del día siguiente (${nextStr} → ${fechaISO})`,
                onClick: () =>
                    copiarRegistrosDia({
                        fromISO: nextStr,
                        toISO: fechaISO,
                        calendar,
                    }),
            },
            {
                icon: "📅",
                label: resourceId
                    ? `Copiar semana anterior → semana actual (${maquinaNombre})`
                    : "Copiar semana anterior (seleccione una máquina)",
                disabled: !resourceId,
                onClick: () =>
                    copiarSemanaAnterior({
                        maquinaId: resourceId,
                        maquinaNombre,
                        fechaISO,
                        duracionSemanas: 1,
                        calendar,
                    }),
            },
            {
                icon: "📅📅",
                label: resourceId
                    ? `Copiar semana anterior → 2 semanas (${maquinaNombre})`
                    : "Copiar semana anterior (seleccione una máquina)",
                disabled: !resourceId,
                onClick: () =>
                    copiarSemanaAnterior({
                        maquinaId: resourceId,
                        maquinaNombre,
                        fechaISO,
                        duracionSemanas: 2,
                        calendar,
                    }),
            },
            {
                icon: "🔧",
                label: resourceId
                    ? `Generar turnos para ${maquinaNombre}`
                    : "Generar turnos (seleccione una máquina)",
                disabled: !resourceId,
                onClick: async () => {
                    console.log("[menu] Click en generar turnos, resourceId:", resourceId);

                    if (!resourceId) {
                        console.log("[menu] No hay resourceId, mostrando advertencia");
                        Swal.fire({
                            icon: "warning",
                            title: "Máquina no seleccionada",
                            text: "Haz clic derecho sobre una máquina específica para generar turnos.",
                        });
                        return;
                    }

                    console.log("[menu] Llamando a generarTurnosDialog...");

                    const resultado = await generarTurnosDialog(
                        fechaISO,
                        resourceId,
                        maquinaNombre
                    );

                    console.log("[menu] Resultado del diálogo:", resultado);

                    if (resultado && resultado.eventos) {
                        console.log("[menu] Procesando eventos:", resultado.eventos.length);

                        // Primero, eliminar eventos antiguos del trabajador en las fechas afectadas
                        const userId = resultado.eventos[0]?.user_id;
                        if (userId) {
                            const eventosExistentes = calendar.getEvents();
                            const fechasNuevas = resultado.eventos.map(e => e.start);

                            eventosExistentes.forEach(evento => {
                                const eventoUserId = evento.extendedProps?.user_id;
                                const eventoFecha = evento.startStr?.slice(0, 10) || evento.start?.toISOString().slice(0, 10);

                                // Eliminar si es del mismo usuario y está en las fechas que estamos actualizando
                                if (eventoUserId === userId && fechasNuevas.includes(eventoFecha) && !evento.extendedProps?.es_festivo) {
                                    console.log("[menu] Eliminando evento antiguo:", evento.id);
                                    evento.remove();
                                }
                            });
                        }

                        // Agregar los nuevos eventos
                        resultado.eventos.forEach(evento => {
                            calendar.addEvent({
                                id: evento.id,
                                title: evento.title,
                                start: evento.start,
                                resourceId: evento.resourceId,
                                allDay: true,
                                backgroundColor: evento.backgroundColor,
                                borderColor: evento.borderColor,
                                textColor: evento.textColor || '#000000',
                                extendedProps: {
                                    user_id: evento.user_id,
                                    categoria_nombre: evento.categoria_nombre,
                                    turno: evento.turno,
                                    entrada: evento.entrada,
                                    salida: evento.salida,
                                    foto: evento.foto,
                                    es_festivo: false,
                                }
                            });
                        });

                        console.log("[menu] Eventos actualizados correctamente");
                    }
                },
            },
        ];

    openActionsMenu(x, y, {
        headerHtml: `<div>Acciones para <b>${fechaISO}</b></div>`,
        items,
    });
}
