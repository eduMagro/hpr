import { DATA, R } from "./config.js";
import { openCellMenu } from "./menu/cellMenu.js";
import { openFestivoMenu } from "./menu/festivoMenu.js";
import { openWorkerMenu } from "./menu/workerMenu.js";

/**
 * Genera eventos de carga de trabajo por turno para mostrar en vista de día
 * Son indicadores pequeños que muestran las horas de trabajo por turno
 * @param {Object} cargaTrabajo - Datos de carga por máquina/día/turno
 * @param {Array} turnosConfig - Configuración de turnos desde BD
 */
function generarEventosCarga(cargaTrabajo, turnosConfig) {
    const eventos = [];

    if (!cargaTrabajo || typeof cargaTrabajo !== 'object') {
        return eventos;
    }

    // Filtrar turnos válidos (con hora_inicio y hora_fin)
    const turnosValidos = (turnosConfig || []).filter(t => t.hora_inicio && t.hora_fin);

    if (turnosValidos.length === 0) {
        return eventos;
    }

    // Mapear turnos con horas alineadas a los slots del calendario (06-14, 14-22, 22-06)
    const turnosMap = {};
    turnosValidos.forEach(turno => {
        const nombre = turno.nombre.toLowerCase();
        const [hIni] = turno.hora_inicio.split(':').map(Number);
        const [hFin] = turno.hora_fin.split(':').map(Number);
        const esNocturno = hFin < hIni;

        // Alinear con los slots del calendario
        let slotInicio, slotFin;
        if (esNocturno) {
            slotInicio = '22:00:00';
            slotFin = '22:30:00'; // Pequeño para que sea un indicador
        } else if (hIni < 14) {
            slotInicio = '06:00:00';
            slotFin = '06:30:00';
        } else {
            slotInicio = '14:00:00';
            slotFin = '14:30:00';
        }

        turnosMap[nombre] = {
            inicio: slotInicio,
            fin: slotFin,
            color: turno.color,
            esNocturno: esNocturno,
        };
    });

    for (const [maquinaId, dias] of Object.entries(cargaTrabajo)) {
        if (!dias || typeof dias !== 'object') continue;

        for (const [fecha, data] of Object.entries(dias)) {
            if (!data) continue;

            // Crear indicador por cada turno que tenga horas
            for (const [turnoNombre, turnoConfig] of Object.entries(turnosMap)) {
                const horas = data[turnoNombre] || 0;
                if (horas <= 0) continue;

                eventos.push({
                    id: `carga-${maquinaId}-${fecha}-${turnoNombre}`,
                    title: `${horas}h`,
                    start: `${fecha}T${turnoConfig.inicio}`,
                    end: `${fecha}T${turnoConfig.fin}`,
                    resourceId: maquinaId,
                    backgroundColor: getColorCarga(horas),
                    borderColor: getColorCarga(horas),
                    textColor: '#000',
                    editable: false,
                    classNames: ['evento-carga'],
                    extendedProps: {
                        es_carga: true,
                        turno: turnoNombre,
                        horas: horas,
                    }
                });
            }
        }
    }

    return eventos;
}

// Sumar minutos a una hora HH:MM
function sumarMinutos(hora, minutos) {
    const [h, m] = hora.split(':').map(Number);
    const totalMin = h * 60 + m + minutos;
    const newH = Math.floor(totalMin / 60) % 24;
    const newM = totalMin % 60;
    return `${String(newH).padStart(2, '0')}:${String(newM).padStart(2, '0')}`;
}

// Color según horas de carga
function getColorCarga(horas) {
    if (horas > 6) return '#fca5a5';   // rojo claro
    if (horas > 3) return '#fcd34d';   // amarillo
    return '#86efac';                   // verde claro
}

// Calcular configuración de slots basada en turnos
// Mostrar desde el primer turno del día hasta el fin del turno nocturno (día siguiente)
function calcularConfigTurnos(turnos) {
    const defaultConfig = {
        slotMinTime: '06:00:00',
        slotMaxTime: '30:00:00',
        slotDuration: '08:00:00',
        turnos: [],
    };

    if (!turnos || turnos.length === 0) {
        return defaultConfig;
    }

    // Filtrar turnos válidos (con hora_inicio y hora_fin)
    const turnosValidos = turnos.filter(t => t.hora_inicio && t.hora_fin);

    if (turnosValidos.length === 0) {
        return defaultConfig;
    }

    // Ordenar turnos por hora de inicio (cronológico)
    const turnosOrdenados = [...turnosValidos].sort((a, b) => {
        return (a.hora_inicio || '').localeCompare(b.hora_inicio || '');
    });

    // Encontrar el turno más temprano y el nocturno
    const primerTurno = turnosOrdenados[0]; // mañana (05:00)
    const turnoNocturno = turnosValidos.find(t => {
        const [hIni] = t.hora_inicio.split(':').map(Number);
        const [hFin] = t.hora_fin.split(':').map(Number);
        return hFin < hIni;
    });

    // slotMinTime: usar 06:00 para que 24h se divida exactamente en 3 slots de 8h
    // (aunque mañana empiece a las 05:00, los eventos seguirán visibles)
    const hMin = 6;

    // slotMaxTime: fin del turno nocturno + 24 si cruza medianoche
    let hMax = 30; // Por defecto 06:00 del día siguiente
    if (turnoNocturno) {
        const [hFin] = turnoNocturno.hora_fin.split(':').map(Number);
        hMax = hFin + 24; // Ej: 06:00 -> 30:00
    }

    // Calcular duración: total de horas / número de turnos
    const totalHoras = hMax - hMin;
    const numTurnos = turnosValidos.length;
    const duracionSlot = Math.floor(totalHoras / numTurnos);

    return {
        slotMinTime: `${String(hMin).padStart(2, '0')}:00:00`,
        slotMaxTime: `${String(hMax).padStart(2, '0')}:00:00`,
        slotDuration: `${String(duracionSlot).padStart(2, '0')}:00:00`,
        turnos: turnosOrdenados,
    };
}

function getVistaValida(claveLocalStorage, vistasPermitidas, vistaDefault) {
    const vista = localStorage.getItem(claveLocalStorage);
    return vistasPermitidas.includes(vista) ? vista : vistaDefault;
}

export function initCalendar(domEl) {
    const { maquinas, eventos, cargaTrabajo, turnos } = DATA();

    // Calcular configuración de slots basada en turnos de la BD
    const configTurnos = calcularConfigTurnos(turnos);

    // Generar eventos de carga de trabajo (indicadores por día/máquina)
    const eventosCarga = generarEventosCarga(cargaTrabajo, turnos);

    // Combinar eventos de trabajadores con eventos de carga
    const todosEventos = [...eventos, ...eventosCarga];

    const VISTA_KEY = "vistaObras";
    const FECHA_KEY = "fechaObras";

    // Variable global para controlar si estamos arrastrando
    let isDragging = false;

    const calendar = new FullCalendar.Calendar(domEl, {
        schedulerLicenseKey: "CC-Attribution-NonCommercial-NoDerivatives",
        locale: "es",
        initialView: getVistaValida(
            VISTA_KEY,
            ["resourceTimelineDay", "resourceTimelineWeek"],
            "resourceTimelineWeek"
        ),
        initialDate: localStorage.getItem(FECHA_KEY) || undefined,
        selectable: true,
        unselectAuto: true,
        datesSet(info) {
            // Guardar vista y fecha válidas en localStorage
            localStorage.setItem("vistaObras", info.view.type);
            localStorage.setItem("fechaObras", info.startStr);

            // Mostrar/ocultar eventos de carga según la vista
            const esVistaDia = info.view.type === "resourceTimelineDay";
            domEl.classList.toggle('vista-dia', esVistaDia);
            domEl.classList.toggle('vista-semana', !esVistaDia);

            // Mostrar botón solo en vista semanal (si el botón existe)
            const btn = document.getElementById("btnRepetirSemana");
            if (btn) {
                if (info.view.type === "resourceTimelineWeek") {
                    btn.classList.remove("hidden");
                    btn.dataset.fecha = info.startStr;
                } else {
                    btn.classList.add("hidden");
                }
            }
        },
        displayEventEnd: true,
        eventMinHeight: 30,
        firstDay: 1,
        height: "auto",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "resourceTimelineDay,resourceTimelineWeek",
        },
        buttonText: {
            today: "Hoy",
            week: "Semana",
            day: "Día",
        },
        slotLabelDidMount(info) {
            const viewType = info.view.type;
            if (viewType === "resourceTimelineDay" && configTurnos.turnos) {
                const hour = info.date.getHours();
                // Colorear según turno desde la BD
                for (const turno of configTurnos.turnos) {
                    const [hIni] = turno.hora_inicio.split(':').map(Number);
                    const [hFin] = turno.hora_fin.split(':').map(Number);

                    let enTurno = false;
                    if (hFin > hIni) {
                        enTurno = hour >= hIni && hour < hFin;
                    } else {
                        // Turno nocturno (cruza medianoche)
                        enTurno = hour >= hIni || hour < hFin;
                    }

                    if (enTurno) {
                        info.el.style.backgroundColor = turno.color || '#e5e7eb';
                        break;
                    }
                }
            }
        },
        views: {
            resourceTimelineDay: {
                slotMinTime: configTurnos.slotMinTime,
                slotMaxTime: configTurnos.slotMaxTime,
                slotDuration: configTurnos.slotDuration,
                slotLabelFormat: {
                    hour: "2-digit",
                    minute: "2-digit",
                    hour12: false,
                },
                // Mostrar solo un día en el título (no "30 nov - 1 dic")
                titleFormat: {
                    weekday: "long",
                    day: "numeric",
                    month: "long",
                    year: "numeric",
                },
            },
            resourceTimelineWeek: {
                slotDuration: { days: 1 },
                slotLabelFormat: {
                    weekday: "long",
                    day: "numeric",
                    month: "short",
                },
            },
        },
        editable: true,
        resources: maquinas,
        resourceOrder: "orden",
        resourceAreaWidth: "100px",
        resourceLabelDidMount(info) {
            const color = info.resource.extendedProps.backgroundColor;
            if (color) {
                info.el.style.backgroundColor = color;
                info.el.style.color = "#fff";
            }
        },
        filterResourcesWithEvents: false,
        events: todosEventos,
        resourceAreaColumns: [{ field: "title", headerContent: "Máquinas" }],

        eventDragStart: (info) => {
            // Marcar que estamos arrastrando
            isDragging = true;

            // Destruir tooltip del elemento que se está arrastrando
            const el = info.el;
            if (el._tippy) {
                el._tippy.destroy();
            }

            // Deshabilitar todos los tooltips existentes
            document.querySelectorAll('.fc-event').forEach(eventEl => {
                if (eventEl._tippy) {
                    eventEl._tippy.disable();
                }
            });
        },

        eventDragStop: (info) => {
            // Marcar que terminamos de arrastrar
            isDragging = false;

            // Habilitar todos los tooltips después de un pequeño delay
            setTimeout(() => {
                document.querySelectorAll('.fc-event').forEach(eventEl => {
                    if (eventEl._tippy) {
                        eventEl._tippy.enable();
                    }
                });
            }, 100);
        },

        eventDrop: async (info) => {
            const e = info.event;
            const props = e.extendedProps || {};
            try {
                if (props.es_festivo) {
                    const nuevaFecha = e.startStr.slice(0, 10);
                    await fetch(
                        R().festivo.update.replace("__ID__", props.festivo_id),
                        {
                            method: "PUT",
                            headers: {
                                "X-CSRF-TOKEN": window.AppPlanif.csrf,
                                Accept: "application/json",
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({ fecha: nuevaFecha }),
                        }
                    ).then((r) => {
                        if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    });
                    return;
                }
                const asignacionId = e.id.replace(/^turno-/, "");
                const recurso = e.getResources()?.[0];
                const nuevoMaquinaId = recurso
                    ? parseInt(recurso.id, 10)
                    : null;
                const nuevaHoraInicio = e.start?.toISOString();
                const hora = new Date(nuevaHoraInicio).getHours();

                // Determinar turno_id basado en la hora y los turnos de la BD
                let turnoId = null;
                for (const turno of configTurnos.turnos) {
                    const [hIni] = turno.hora_inicio.split(':').map(Number);
                    const [hFin] = turno.hora_fin.split(':').map(Number);
                    const esNocturno = hFin < hIni;

                    let enTurno = false;
                    if (esNocturno) {
                        // Turno nocturno: 22:00 - 06:00
                        enTurno = hora >= hIni || hora < hFin;
                    } else {
                        // Turnos normales
                        enTurno = hora >= hIni && hora < hFin;
                    }

                    if (enTurno) {
                        turnoId = turno.id;
                        break;
                    }
                }

                // Fallback si no se encontró turno
                if (!turnoId) {
                    turnoId = hora >= 6 && hora < 14 ? 1 : hora >= 14 && hora < 22 ? 2 : 3;
                }

                if (!nuevoMaquinaId || !nuevaHoraInicio)
                    throw new Error("Datos incompletos");

                const url = R().asignacion.updatePuesto.replace(
                    "__ID__",
                    asignacionId
                );
                const resp = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": window.AppPlanif.csrf,
                    },
                    body: JSON.stringify({
                        maquina_id: nuevoMaquinaId,
                        start: nuevaHoraInicio,
                        turno_id: turnoId,
                    }),
                });
                const data = await resp.json();
                if (!resp.ok)
                    throw new Error(data?.message || `HTTP ${resp.status}`);
                if (data.color) {
                    e.setProp("backgroundColor", data.color);
                    e.setProp("borderColor", data.color);
                }
                if (typeof data.nuevo_obra_id !== "undefined")
                    e.setExtendedProp("obra_id", data.nuevo_obra_id);

                // Actualizar turno_id y turno_nombre en el evento
                e.setExtendedProp("turno_id", turnoId);
                const turnoEncontrado = configTurnos.turnos.find(t => t.id === turnoId);
                if (turnoEncontrado) {
                    e.setExtendedProp("turno_nombre", turnoEncontrado.nombre);
                }

                // Forzar re-renderizado del evento para recrear el tooltip
                calendar.refetchEvents();
            } catch (err) {
                console.error(err);
                Swal.fire(
                    "Error",
                    err.message || "Ocurrió un error inesperado.",
                    "error"
                );
                info.revert();
            }
        },
        eventDidMount(info) {
            const e = info.event;
            const props = e.extendedProps || {};

            // Ignorar eventos de carga (sin tooltip, sin menú)
            if (props.es_carga) {
                // Solo tooltip simple con info
                info.el.title = `${props.horas}h de trabajo - Turno ${props.turno}`;
                return;
            }

            // Destruir tooltip anterior si existe
            if (info.el._tippy) {
                info.el._tippy.destroy();
            }

            if (props.foto && !props.es_festivo) {
                const content = `<img src="${props.foto}" class="w-18 h-18 rounded-full object-cover ring-2 ring-blue-400 shadow-lg">`;
                const tippyInstance = tippy(info.el, {
                    content,
                    allowHTML: true,
                    placement: "top",
                    theme: "transparent-avatar",
                    interactive: false,
                    arrow: false,
                    delay: [100, 0],
                    offset: [0, 10],
                    // No mostrar tooltip si estamos arrastrando
                    onShow() {
                        if (isDragging) {
                            return false;
                        }
                    },
                });

                // Si estamos arrastrando, deshabilitar inmediatamente
                if (isDragging && tippyInstance) {
                    tippyInstance.disable();
                }
            }

            info.el.addEventListener("contextmenu", (ev) => {
                ev.preventDefault();
                ev.stopPropagation();
                if (props.es_festivo) {
                    openFestivoMenu(ev.clientX, ev.clientY, {
                        event: e,
                        titulo: e.title,
                    });
                } else {
                    openWorkerMenu(ev.clientX, ev.clientY, e);
                }
            });
        },

        eventClick(info) {
            const e = info.event;
            const props = e.extendedProps || {};
            if (props.es_festivo) return;
            const userId = props.user_id;
            if (userId) {
                const url = R().userShow.replace(":id", userId);
                window.location.href = url;
            }
        },

        eventContent(arg) {
            const p = arg.event.extendedProps;

            // Eventos de carga: formato simple
            if (p?.es_carga) {
                return {
                    html: `<div class="carga-content">${p.horas}h</div>`,
                };
            }

            // Festivos
            if (p?.es_festivo) {
                return {
                    html: `<div class="px-2 py-1 text-xs font-semibold" style="color:#fff">${arg.event.title}</div>`,
                };
            }

            // Eventos de asignación de turno (trabajadores)
            const horas =
                p.entrada && p.salida
                    ? `${p.entrada} / ${p.salida}`
                    : p.entrada || p.salida || "-- / --";
            const turnoNombre = p.turno_nombre ? p.turno_nombre.charAt(0).toUpperCase() + p.turno_nombre.slice(1) : '';
            return {
                html: `
          <div class="px-2 py-1 text-xs font-semibold flex items-center">
            <div class="flex flex-col">
              <span>${arg.event.title} <span class="text-[10px] font-medium opacity-70">[${turnoNombre}]</span></span>
              <span class="text-[10px] font-normal opacity-80">(${
                  p.categoria_nombre ?? ""
              } 🛠 ${p.especialidad_nombre ?? "Sin especialidad"})</span>
            </div>
            <div class="ml-auto text-right">
              <span class="text-[10px] font-normal opacity-80">${horas}</span>
            </div>
          </div>`,
            };
        },
    });

    calendar.render();

    console.log("[cal] Configurando event listener para contextmenu...");

    const root = calendar.el;
    console.log("[cal] Elemento raíz del calendario:", root);

    // Siempre agregar el event listener (el destroy debería limpiar el elemento)
    console.log("[cal] Agregando event listener de contextmenu al calendario");

    root.addEventListener("contextmenu", (ev) => {
            console.log("[cal] ¡Contextmenu disparado!", ev.target);

            if (ev.target.closest(".fc-event")) {
                console.log("[cal] Es un evento, ignorando");
                return;
            }

            ev.preventDefault();

            // Método alternativo: encontrar el recurso por posición vertical
            let resourceId = null;
            let fechaISO = null;

            // Primero intentar obtener la fecha del elemento clickeado
            const dateEl = ev.target.closest("[data-date]");
            if (dateEl) {
                fechaISO = dateEl.getAttribute("data-date") || "";
                if (fechaISO.length >= 10) fechaISO = fechaISO.slice(0, 10);
            }

            if (!fechaISO) {
                console.log("[cal] No se pudo determinar la fecha");
                return;
            }

            console.log("[cal] Fecha encontrada:", fechaISO);
            console.log("[cal] Elemento clickeado:", ev.target);
            console.log("[cal] Elemento con data-date:", dateEl);

            // Intentar obtener el recurso desde la posición Y del click
            // Buscar todas las filas de recursos en el área izquierda
            const resourceLanes = root.querySelectorAll('.fc-timeline-lane[data-resource-id]');
            console.log("[cal] Filas de recursos encontradas:", resourceLanes.length);

            if (resourceLanes.length > 0) {
                const clickY = ev.clientY;
                console.log("[cal] Posición Y del click:", clickY);

                for (const lane of resourceLanes) {
                    const rect = lane.getBoundingClientRect();
                    console.log("[cal] Examinando lane con resource-id:", lane.dataset.resourceId, "top:", rect.top, "bottom:", rect.bottom);

                    if (clickY >= rect.top && clickY <= rect.bottom) {
                        resourceId = lane.dataset.resourceId;
                        console.log("[cal] ¡ResourceId encontrado por posición Y!:", resourceId);
                        break;
                    }
                }
            }

            console.log("[cal] ResourceId final detectado:", resourceId, "Fecha:", fechaISO);

            openCellMenu(
                ev.clientX,
                ev.clientY,
                { fechaISO, resourceId },
                calendar,
                maquinas
            );
        });

    console.log("[cal] Event listener de contextmenu agregado correctamente");

    return calendar;
}
