// public/js/usersJs/calendario.js
(function () {
    if (typeof FullCalendar === "undefined") {
        console.error("FullCalendar no está cargado.");
        return;
    }

    // Utils
    const qsAll = (sel, ctx = document) =>
        Array.from(ctx.querySelectorAll(sel));

    function addDaysStr(yyyy_mm_dd, days) {
        const d = new Date(yyyy_mm_dd);
        d.setDate(d.getDate() + days);
        return d.toISOString().split("T")[0];
    }
    function addOneDayStr(yyyy_mm_dd) {
        return addDaysStr(yyyy_mm_dd, 1);
    }

    // Merge de eventos de día consecutivos "iguales"
    function mergeDailyEvents(events) {
        const norm = events.map((ev) => {
            const startISO =
                ev.startStr ||
                ev.start ||
                ev.startTime ||
                ev.startDate ||
                ev.start;
            const start = new Date(startISO);
            let endISO = ev.endStr || ev.end;
            if (!endISO)
                endISO = addOneDayStr(start.toISOString().split("T")[0]);
            return {
                ...ev,
                start: start.toISOString(),
                end: new Date(endISO).toISOString(),
                allDay: ev.allDay !== false,
            };
        });

        norm.sort((a, b) => new Date(a.start) - new Date(b.start));

        const keyOf = (ev) => {
            const p = ev.extendedProps || {};
            return [
                ev.title || "",
                p.tipo || p.estado || p.turno || "",
                (ev.classNames && ev.classNames.join(",")) || "",
            ].join("|");
        };

        const merged = [];
        for (const ev of norm) {
            const startDay = ev.start.split("T")[0];
            if (!merged.length) {
                merged.push({ ...ev, __key: keyOf(ev) });
                continue;
            }
            const last = merged[merged.length - 1];
            const lastEndDay = last.end.split("T")[0];
            if (last.__key === keyOf(ev) && startDay === lastEndDay) {
                last.end = ev.end;
                if (last.extendedProps) {
                    // Si no quieres anular clic en rangos fusionados, comenta:
                    last.extendedProps.asignacion_id = null;
                    last.extendedProps.merged = true;
                }
            } else {
                merged.push({ ...ev, __key: keyOf(ev) });
            }
        }
        return merged.map(({ __key, ...rest }) => rest);
    }

    // Actualiza el resumen (se invoca desde cada instancia con su config)
    function actualizarResumenAsistencia(resumenUrl) {
        if (!resumenUrl) return;
        fetch(resumenUrl)
            .then((r) => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then((data) => {
                const div = document.getElementById("resumen-asistencia");
                if (!div) return;
                div.style.opacity = 0.5;
                div.innerHTML = `
          <p><strong>Vacaciones asignadas: </strong> ${data.diasVacaciones}</p>
          <p><strong>Faltas injustificadas: </strong> ${data.faltasInjustificadas}</p>
          <p><strong>Faltas justificadas: </strong> ${data.faltasJustificadas}</p>
          <p><strong>Días de baja: </strong> ${data.diasBaja}</p>
        `;
                setTimeout(() => (div.style.opacity = 1), 200);
            })
            .catch((e) => console.error("Error resumen asistencia:", e));
    }

    // Inicializa una instancia de calendario sobre un elemento
    function initCalendarOn(el) {
        let cfg = {};
        try {
            cfg = JSON.parse(el.getAttribute("data-config") || "{}");
        } catch (e) {
            console.error("data-config inválido", e);
            return;
        }

        const {
            locale = "es",
            csrfToken = "",
            routes = {},
            turnos = [],
            enableListMonth = true,
            mobileBreakpoint = 768,
        } = cfg;

        const isMobile = window.matchMedia(
            `(max-width: ${mobileBreakpoint}px)`
        ).matches;

        // estados de selección móvil
        let tapStart = null;
        let tapBgEvent = null;

        // preferencias guardadas por instancia (usamos el id o un uid)
        const storageKeyPrefix = el.id
            ? `fc:${el.id}:`
            : `fc:${Math.random().toString(36).slice(2)}:`;
        const vistasDisponibles = [
            "dayGridMonth",
            "timeGridWeek",
            "timeGridDay",
            "listWeek",
            "listMonth",
        ];
        let vistaGuardada = localStorage.getItem(storageKeyPrefix + "vista");
        if (!vistasDisponibles.includes(vistaGuardada))
            vistaGuardada = "dayGridMonth";
        const fechaGuardada = localStorage.getItem(storageKeyPrefix + "fecha");

        // Lógica compartida para registrar/editar/eliminar rango
        async function handleRangeSelection(fechaInicio, fechaFin) {
            const mensajeFecha =
                fechaInicio === fechaFin
                    ? `<p>${fechaInicio}</p>`
                    : `<p>Desde: ${fechaInicio}</p><p>Hasta: ${fechaFin}</p>`;

            // Render del select con turnos dinámicos
            const opcionesTurnos = turnos
                .map(
                    (t) =>
                        `<option value="${t.nombre}">${
                            (t.nombre || "").charAt(0).toUpperCase() +
                            (t.nombre || "").slice(1)
                        }</option>`
                )
                .join("");

            const { isConfirmed, value: tipoSeleccionado } = await Swal.fire({
                title: "Selecciona un turno",
                html: `
          ${mensajeFecha}
          <select id="tipo-dia" class="swal2-select">
            <option value="eliminarTurnoEstado">🗑 Eliminar Turno</option>
            ${opcionesTurnos}
            <option value="eliminarEstado">🗑 Eliminar Estado</option>
            <option value="curso">Realizando Cursos</option>
            <option value="vacaciones">🏖 Vacaciones</option>
            <option value="baja">🤒 Baja</option>
            <option value="justificada">✅ Falta Justificada</option>
            <option value="injustificada">❌ Falta Injustificada</option>
          </select>
        `,
                showCancelButton: true,
                confirmButtonText: "Registrar",
                cancelButtonText: "Cancelar",
                preConfirm: () => document.getElementById("tipo-dia").value,
            });
            if (!isConfirmed) return;

            if (
                tipoSeleccionado === "eliminarTurnoEstado" ||
                tipoSeleccionado === "eliminarEstado"
            ) {
                const mensajeConfirmacion =
                    tipoSeleccionado === "eliminarTurnoEstado"
                        ? "¿Estás seguro de que quieres eliminar el turno? Esto también eliminará cualquier estado asignado (vacaciones, baja...) y las horas de entrada y salida."
                        : "¿Seguro que quieres eliminar solo el estado? Las horas de entrada y salida y el turno se mantendrán.";

                const confirmacion = await Swal.fire({
                    title: "Confirmar eliminación",
                    text: mensajeConfirmacion,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar",
                });
                if (!confirmacion.isConfirmed) return;

                const eventosEnRango = calendar.getEvents().filter((event) => {
                    const eventDate = event.startStr;
                    return eventDate >= fechaInicio && eventDate <= fechaFin;
                });

                const todosSonFestivo =
                    eventosEnRango.length > 0 &&
                    eventosEnRango.every(
                        (e) => (e.title || "").toLowerCase() === "festivo"
                    );

                const body = { fecha_inicio: fechaInicio, fecha_fin: fechaFin };
                if (todosSonFestivo) body.tipo_turno = "festivo";
                else {
                    body.user_id = cfg.userId;
                    body.tipo = tipoSeleccionado;
                }

                fetch(routes.destroyUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify(body),
                })
                    .then((r) => r.json())
                    .then((data) => {
                        if (data.success) {
                            eventosEnRango.forEach((e) => e.remove());
                            calendar.refetchEvents();
                            setTimeout(
                                () =>
                                    actualizarResumenAsistencia(
                                        routes.resumenUrl
                                    ),
                                200
                            );
                            Swal.fire(
                                "Eliminado",
                                "Los turnos han sido eliminados correctamente.",
                                "success"
                            );
                        } else {
                            Swal.fire(
                                "Error",
                                data.error || "Error eliminando turnos",
                                "error"
                            );
                        }
                    })
                    .catch(() =>
                        Swal.fire(
                            "Error",
                            "Ocurrió un problema al eliminar los turnos.",
                            "error"
                        )
                    );
            } else {
                fetch(routes.storeUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify({
                        user_id: cfg.userId,
                        fecha_inicio: fechaInicio,
                        fecha_fin: fechaFin,
                        tipo: tipoSeleccionado,
                    }),
                })
                    .then((r) => r.json())
                    .then((data) => {
                        if (data.success) {
                            calendar.refetchEvents();
                            setTimeout(
                                () =>
                                    actualizarResumenAsistencia(
                                        routes.resumenUrl
                                    ),
                                200
                            );
                        } else {
                            Swal.fire(
                                "Error",
                                data.error || "Error registrando turnos",
                                "error"
                            );
                        }
                    })
                    .catch(() =>
                        Swal.fire(
                            "Error",
                            "Ocurrió un problema al registrar los turnos.",
                            "error"
                        )
                    );
            }
        }

        // Botonera (Mes / Lista si procede)
        const rightButtons = enableListMonth
            ? "dayGridMonth,listMonth"
            : "dayGridMonth";

        // Crear calendario
        const calendar = new FullCalendar.Calendar(el, {
            locale,
            initialView: vistaGuardada,
            initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,
            firstDay: 1,
            height: "auto",
            selectable: !isMobile, // en móvil usamos tap-tap
            selectMirror: true,
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: rightButtons,
            },
            buttonText: {
                today: "Hoy",
                dayGridMonth: "Mes",
                listMonth: "Lista",
            },

            events: function (fetchInfo, success, failure) {
                if (!routes.eventosUrl) {
                    console.warn("Sin 'eventosUrl' en data-config");
                    success([]);
                    return;
                }
                fetch(routes.eventosUrl)
                    .then((r) => r.json())
                    .then((events) => success(mergeDailyEvents(events)))
                    .catch(failure);
            },

            // Desktop: arrastre
            select: function (info) {
                if (isMobile) return;
                const startStr = info.startStr;
                const finIncl = new Date(info.end); // end exclusivo → ya venía +1 día
                finIncl.setDate(finIncl.getDate());
                const endStr = finIncl.toISOString().split("T")[0];
                handleRangeSelection(startStr, endStr);
            },

            // Móvil: tap-tap
            dateClick: function (info) {
                if (!isMobile) return;
                const clicked = info.dateStr;

                if (!tapStart) {
                    tapStart = clicked;
                    if (tapBgEvent) {
                        tapBgEvent.remove();
                        tapBgEvent = null;
                    }
                    tapBgEvent = calendar.addEvent({
                        start: clicked,
                        end: addOneDayStr(clicked),
                        display: "background",
                        overlap: false,
                        classNames: ["bg-selection-temp"],
                    });
                    return;
                }

                const startStr = clicked < tapStart ? clicked : tapStart;
                const lastStr = clicked < tapStart ? tapStart : clicked;

                if (tapBgEvent) {
                    tapBgEvent.remove();
                    tapBgEvent = null;
                }
                tapStart = null;

                handleRangeSelection(startStr, lastStr);
            },

            // Click en evento → edición horas (fichajes)
            eventClick: function (info) {
                const props = info.event.extendedProps || {};
                if (!props.asignacion_id) return;

                Swal.fire({
                    title: `📋 Fichaje del ${props.fecha}`,
                    html: `
            <div class="text-center">
              <div class="mb-4">
                <label class="block mb-2 font-semibold">Hora de entrada:</label>
                <input type="time" id="horaEntrada" class="swal2-input" style="display:block; margin:0 auto; width:auto;" value="${
                    props.entrada ?? ""
                }">
              </div>
              <div>
                <label class="block mb-2 font-semibold">Hora de salida:</label>
                <input type="time" id="horaSalida" class="swal2-input" style="display:block; margin:0 auto; width:auto;" value="${
                    props.salida ?? ""
                }">
              </div>
            </div>
          `,
                    showCancelButton: true,
                    confirmButtonText: "Guardar",
                    cancelButtonText: "Cancelar",
                }).then((res) => {
                    if (!res.isConfirmed) return;

                    let entrada = document.getElementById("horaEntrada").value;
                    let salida = document.getElementById("horaSalida").value;
                    if (entrada && entrada.length === 8)
                        entrada = entrada.slice(0, 5);
                    if (salida && salida.length === 8)
                        salida = salida.slice(0, 5);

                    // build URL
                    const url = (routes.updateHorasUrlBase || "").replace(
                        "{id}",
                        props.asignacion_id
                    );
                    if (!url) {
                        console.error("updateHorasUrlBase no definido");
                        return;
                    }

                    fetch(url, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrfToken,
                        },
                        body: JSON.stringify({ entrada, salida }),
                    })
                        .then(async (response) => {
                            const ct =
                                response.headers.get("content-type") || "";
                            const data = ct.includes("application/json")
                                ? await response.json()
                                : {};
                            if (response.ok && data.ok) {
                                calendar.refetchEvents();
                                setTimeout(
                                    () =>
                                        actualizarResumenAsistencia(
                                            routes.resumenUrl
                                        ),
                                    200
                                );
                            } else {
                                let errorMsg =
                                    data.message ||
                                    "Ha ocurrido un error inesperado.";
                                if (data.errors)
                                    errorMsg = Object.values(data.errors)
                                        .flat()
                                        .join("<br>");
                                Swal.fire({
                                    icon: "error",
                                    title: "Error al guardar",
                                    html: errorMsg,
                                });
                            }
                        })
                        .catch((err) => {
                            console.error("Error en Fetch:", err);
                            Swal.fire({
                                icon: "error",
                                title: "Error de conexión",
                                text: "No se pudo actualizar la asignación. Inténtalo nuevamente.",
                            });
                        });
                });
            },

            // Guardar preferencia de vista/fecha por instancia
            datesSet: function (info) {
                let fechaActual = info.startStr;
                if (calendar.view.type === "dayGridMonth") {
                    const middleDate = new Date(info.start);
                    middleDate.setDate(middleDate.getDate() + 15);
                    fechaActual = middleDate.toISOString().split("T")[0];
                }
                localStorage.setItem(storageKeyPrefix + "fecha", fechaActual);
                localStorage.setItem(
                    storageKeyPrefix + "vista",
                    calendar.view.type
                );
            },
        });

        // pinta y primer resumen
        calendar.render();
        actualizarResumenAsistencia(routes.resumenUrl);
    }

    // Inicializar todos los calendarios de la página
    document.addEventListener("DOMContentLoaded", function () {
        qsAll(".fc-calendario").forEach(initCalendarOn);
    });
})();
