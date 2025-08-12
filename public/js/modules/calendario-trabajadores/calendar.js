import { DATA, R } from "./config.js";
import { openCellMenu } from "./menu/cellMenu.js";
import { openFestivoMenu } from "./menu/festivoMenu.js";
import { openWorkerMenu } from "./menu/workerMenu.js";

export function initCalendar(domEl) {
    const { maquinas, eventos } = DATA();

    const calendar = new FullCalendar.Calendar(domEl, {
        schedulerLicenseKey: "CC-Attribution-NonCommercial-NoDerivatives",
        locale: "es",
        initialView:
            localStorage.getItem("ultimaVistaCalendario") ||
            "resourceTimelineDay",
        initialDate: localStorage.getItem("fechaCalendario") || undefined,
        selectable: true,
        unselectAuto: true,
        datesSet(info) {
            let fechaActual = info.startStr;
            if (calendar.view.type === "dayGridMonth") {
                const middleDate = new Date(info.start);
                middleDate.setDate(middleDate.getDate() + 15);
                fechaActual = middleDate.toISOString().split("T")[0];
            }
            localStorage.setItem("fechaCalendario", fechaActual);
            localStorage.setItem("ultimaVistaCalendario", calendar.view.type);
        },
        displayEventEnd: true,
        eventMinHeight: 30,
        firstDay: 1,
        height: "auto",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "resourceTimelineDay,resourceTimelineWeek,dayGridMonth",
        },
        buttonText: { today: "Hoy", month: "Mes" },
        slotLabelDidMount(info) {
            const viewType = info.view.type;
            if (viewType === "resourceTimelineDay") {
                const hour = parseInt(info.date.getHours());
                if (hour >= 6 && hour < 14)
                    info.el.style.backgroundColor = "#a7f3d0";
                else if (hour >= 14 && hour < 22)
                    info.el.style.backgroundColor = "#bfdbfe";
                else info.el.style.backgroundColor = "#fde68a";
                info.el.style.borderRight = "1px solid #e5e7eb";
            }
        },
        views: {
            resourceTimelineDay: {
                slotMinTime: "00:00:00",
                slotMaxTime: "21:59:00",
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
        resourceAreaWidth: "100px",
        resourceLabelDidMount(info) {
            const color = info.resource.extendedProps.backgroundColor;
            if (color) {
                info.el.style.backgroundColor = color;
                info.el.style.color = "#fff";
            }
        },
        filterResourcesWithEvents: false,
        events: eventos,
        resourceAreaColumns: [{ field: "title", headerContent: "Máquinas" }],

        // mover evento: festivo → mueve fecha; trabajador → actualizar puesto/turno
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
                const turnoId =
                    hora >= 6 && hora < 14
                        ? 1
                        : hora >= 14 && hora < 22
                        ? 2
                        : 3;

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

        // manejar context menu por tipo
        eventDidMount(info) {
            const e = info.event;
            const props = e.extendedProps || {};

            // tooltip solo trabajadores
            if (props.foto && !props.es_festivo) {
                const content = `<img src="${props.foto}" class="w-18 h-18 rounded-full object-cover ring-2 ring-blue-400 shadow-lg">`;
                tippy(info.el, {
                    content,
                    allowHTML: true,
                    placement: "top",
                    theme: "transparent-avatar",
                    interactive: false,
                    arrow: false,
                    delay: [100, 0],
                    offset: [0, 10],
                });
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

        // click izquierdo → users.show (trabajador)
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

        // render contenido
        eventContent(arg) {
            const p = arg.event.extendedProps;
            if (p?.es_festivo) {
                return {
                    html: `<div class="px-2 py-1 text-xs font-semibold" style="color:#fff">${arg.event.title}</div>`,
                };
            }
            const horas =
                p.entrada && p.salida
                    ? `${p.entrada} / ${p.salida}`
                    : p.entrada || p.salida || "-- / --";
            return {
                html: `
          <div class="px-2 py-1 text-xs font-semibold flex items-center">
            <div class="flex flex-col">
              <span>${arg.event.title}</span>
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

    // menú contextual en celdas (no eventos)
    const root = calendar.el;
    if (!root._ctxMenuBound) {
        root._ctxMenuBound = true;
        root.addEventListener("contextmenu", (ev) => {
            if (ev.target.closest(".fc-event")) return; // ignorar si es evento
            ev.preventDefault();
            const dateEl = ev.target.closest("[data-date]");
            if (!dateEl) return;
            let fechaISO = dateEl.getAttribute("data-date") || "";
            if (fechaISO.length >= 10) fechaISO = fechaISO.slice(0, 10);
            openCellMenu(
                ev.clientX,
                ev.clientY,
                { fechaISO },
                calendar,
                maquinas
            );
        });
    }

    return calendar;
}
