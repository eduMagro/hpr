import { openActionsMenu } from "../menu/baseMenu.js";
import { crearFestivo } from "../dialogs/festivo.js";
import { generarTurnosDialog } from "../dialogs/generarTurnos.js";
import { DATA } from "../config.js";

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
                icon: "🔧",
                label: resourceId
                    ? `Generar turnos para ${maquinaNombre}`
                    : "Generar turnos (seleccione una máquina)",
                disabled: !resourceId,
                onClick: async () => {
                    if (!resourceId) {
                        Swal.fire({
                            icon: "warning",
                            title: "Máquina no seleccionada",
                            text: "Haz clic derecho sobre una máquina específica para generar turnos.",
                        });
                        return;
                    }

                    // Obtener trabajadores desde la configuración
                    const { eventos } = DATA();

                    // Extraer lista única de trabajadores desde los eventos
                    const trabajadoresMap = new Map();
                    eventos.forEach((ev) => {
                        if (ev.extendedProps?.user_id && !ev.extendedProps?.es_festivo) {
                            const userId = ev.extendedProps.user_id;
                            if (!trabajadoresMap.has(userId)) {
                                trabajadoresMap.set(userId, {
                                    id: userId,
                                    nombre_completo: ev.title,
                                    maquina_id: ev.extendedProps.maquina_id || null,
                                    turno: ev.extendedProps.turno || null,
                                    categoria: {
                                        nombre: ev.extendedProps.categoria_nombre || null,
                                    },
                                });
                            }
                        }
                    });

                    const trabajadores = Array.from(trabajadoresMap.values());

                    const resultado = await generarTurnosDialog(
                        fechaISO,
                        resourceId,
                        trabajadores
                    );

                    if (resultado) {
                        // Refrescar el calendario para mostrar los nuevos turnos
                        calendar.refetchEvents();
                    }
                },
            },
        ];

    openActionsMenu(x, y, {
        headerHtml: `<div>Acciones para <b>${fechaISO}</b></div>`,
        items,
    });
}
