// public/js/calendario/calendario.js
(function () {
    if (typeof FullCalendar === "undefined") {
        console.error("FullCalendar no está cargado.");
        return;
    }

    const qsAll = (sel, ctx = document) =>
        Array.from(ctx.querySelectorAll(sel));
    const addDaysStr = (d, days) => {
        const x = new Date(d);
        x.setDate(x.getDate() + days);
        return x.toISOString().split("T")[0];
    };
    const addOneDayStr = (d) => addDaysStr(d, 1);

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
            // usa una “clave de grupo” estable si existe; si no, cae a título+tipo/estado/turno
            const grupo = p.grupo || "";
            return [
                grupo || ev.title || "",
                p.tipo || p.estado || p.turno || "",
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
                    last.extendedProps.asignacion_id = null;
                    last.extendedProps.merged = true;
                }
            } else {
                merged.push({ ...ev, __key: keyOf(ev) });
            }
        }
        return merged.map(({ __key, ...rest }) => rest);
    }

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
                    <p><strong>Días de baja: </strong> ${data.diasBaja}</p>`;
                setTimeout(() => (div.style.opacity = 1), 200);
            })
            .catch((e) => console.error("Error resumen asistencia:", e));
    }

    // --- Refetch con debounce (global) ---
    let refetchTimer = null;
    function smartRefetch(calendar, extraCb) {
        clearTimeout(refetchTimer);
        refetchTimer = setTimeout(() => {
            calendar.refetchEvents();
            if (typeof extraCb === "function") extraCb();
        }, 120); // ajusta (80–200ms)
    }

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
            enableListMonth = true,
            permissions = {
                canRequestVacations: false,
                canEditHours: false,
                canAssignShifts: false,
                canAssignStates: false,
            },
            turnos = [], // opcional
            userId = null,
        } = cfg;

        // Estado de selección “clic-clic”
        let startClick = null;
        let hoverDayEvs = [];

        function ensureTempEvents(calendar) {
            if (hoverRangeEv && hoverStartEv && hoverEndEv) return;
            calendar.batchRendering(() => {
                hoverRangeEv = calendar.addEvent({
                    start: null,
                    end: null,
                    display: "background",
                    overlap: true,
                    classNames: ["bg-select-range"],
                    __tempHover: true,
                });
                hoverStartEv = calendar.addEvent({
                    start: null,
                    end: null,
                    display: "background",
                    overlap: true,
                    classNames: ["bg-select-endpoint"],
                    __tempHover: true,
                });
                hoverEndEv = calendar.addEvent({
                    start: null,
                    end: null,
                    display: "background",
                    overlap: true,
                    classNames: ["bg-select-endpoint"],
                    __tempHover: true,
                });
            });
        }

        function clearTempHighlight(calendar) {
            if (!hoverDayEvs.length) return;
            calendar.batchRendering(() =>
                hoverDayEvs.forEach((ev) => ev.remove())
            );
            hoverDayEvs = [];
        }

        function eachDayStr(aStr, bStr) {
            const days = [];
            let a = new Date(aStr),
                b = new Date(bStr);
            if (a > b) [a, b] = [b, a];
            for (let d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) {
                days.push(d.toISOString().split("T")[0]);
            }
            return days;
        }

        // pilla el día anterior al siguiente en string YYYY-MM-DD
        const addOneDayStr = (d) => {
            const x = new Date(d);
            x.setDate(x.getDate() + 1);
            return x.toISOString().split("T")[0];
        };

        function updateTempHighlight(calendar, startStr, hoverStr) {
            const forward = startStr <= hoverStr;
            const days = eachDayStr(startStr, hoverStr);
            const first = days[0];
            const last = days[days.length - 1];

            clearTempHighlight(calendar);

            calendar.batchRendering(() => {
                days.forEach((d) => {
                    const isFirst = d === first;
                    const isLast = d === last;
                    const classes = [];

                    if (isFirst || isLast) {
                        classes.push("bg-select-endpoint");
                        if (isFirst)
                            classes.push(
                                forward
                                    ? "bg-select-endpoint-left"
                                    : "bg-select-endpoint-right"
                            );
                        if (isLast)
                            classes.push(
                                forward
                                    ? "bg-select-endpoint-right"
                                    : "bg-select-endpoint-left"
                            );
                    } else {
                        classes.push("bg-select-range");
                    }

                    const ev = calendar.addEvent({
                        start: d,
                        end: addOneDayStr(d),
                        display: "background",
                        overlap: true,
                        classNames: classes,
                        __tempHover: true,
                    });
                    hoverDayEvs.push(ev);
                });
            });
        }

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

        // --- Acciones por rol ---
        async function pedirVacaciones(fechaInicio, fechaFin, calendar) {
            const msg =
                fechaInicio === fechaFin
                    ? `<p>${fechaInicio}</p>`
                    : `<p>Desde: ${fechaInicio}</p><p>Hasta: ${fechaFin}</p>`;
            const { isConfirmed } = await Swal.fire({
                title: "Solicitar vacaciones",
                html: `${msg}<p class="mt-2 text-sm text-gray-600">Se enviará una solicitud para revisión.</p>`,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Enviar solicitud",
                cancelButtonText: "Cancelar",
            });
            if (!isConfirmed) return;
            if (!routes.vacacionesStoreUrl) {
                Swal.fire(
                    "Error",
                    "Ruta de solicitud de vacaciones no configurada.",
                    "error"
                );
                return;
            }
            fetch(routes.vacacionesStoreUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin,
                }),
            })
                .then(async (res) => {
                    const ct = res.headers.get("content-type") || "";
                    const data = ct.includes("application/json")
                        ? await res.json()
                        : {};
                    if (!res.ok || data.error)
                        throw new Error(data.error || `HTTP ${res.status}`);
                    Swal.fire(
                        "Solicitud enviada",
                        data.success || "Tu solicitud ha sido registrada.",
                        "success"
                    ).then(() => {
                        smartRefetch(calendar, () =>
                            actualizarResumenAsistencia(routes.resumenUrl)
                        );
                    });
                })
                .catch((err) =>
                    Swal.fire(
                        "Error",
                        err.message || "No se pudo enviar la solicitud.",
                        "error"
                    )
                );
        }

        async function registrarEventoOficina(fechaInicio, fechaFin, calendar) {
            const opcionesTurnos = (turnos || [])
                .map((t) => `<option value="${t.nombre}">${t.nombre}</option>`)
                .join("");

            const mensajeFecha =
                fechaInicio === fechaFin
                    ? `<p>${fechaInicio}</p>`
                    : `<p>Desde: ${fechaInicio}</p><p>Hasta: ${fechaFin}</p>`;

            const { value: tipoSeleccionado, isConfirmed } = await Swal.fire({
                title: "Selecciona un turno o estado",
                html: `
            ${mensajeFecha}
            <select id="tipo-dia" class="swal2-select">
                <option value="eliminarTurnoEstado">🗑 Eliminar Turno</option>
                ${opcionesTurnos}
                <option value="eliminarEstado">🗑 Eliminar Estado</option>
                <option value="curso">🎓 Realizando Cursos</option>
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

            // --- Lógica de eliminación ---
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

                const body = {
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin,
                    user_id: userId,
                    tipo: tipoSeleccionado,
                };

                fetch(
                    routes.destroyUrl ||
                    "{{ route('asignaciones-turnos.destroy') }}",
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrfToken,
                        },
                        body: JSON.stringify(body),
                    }
                )
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.success) {
                            smartRefetch(calendar, () =>
                                actualizarResumenAsistencia(routes.resumenUrl)
                            );

                            Swal.fire("Eliminado", data.success, "success");
                        } else {
                            Swal.fire(
                                "Error",
                                data.error || "No se pudo eliminar el turno.",
                                "error"
                            );
                        }
                    })
                    .catch((err) => {
                        console.error("Error:", err);
                        Swal.fire(
                            "Error",
                            "Ocurrió un problema al eliminar los turnos.",
                            "error"
                        );
                    });

                return;
            }

            // --- Lógica de asignación nueva ---
            const body = {
                user_id: userId,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
                tipo: tipoSeleccionado,
            };

            fetch(routes.storeUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify(body),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        smartRefetch(calendar, () =>
                            actualizarResumenAsistencia(routes.resumenUrl)
                        );

                        Swal.fire("Registrado", data.success, "success");
                    } else {
                        Swal.fire(
                            "Error",
                            data.error || "No se pudo registrar el evento.",
                            "error"
                        );
                    }
                })
                .catch((err) => {
                    console.error("Error:", err);
                    Swal.fire(
                        "Error",
                        "Ocurrió un problema al registrar el turno.",
                        "error"
                    );
                });
        }

        const rightButtons = enableListMonth
            ? "dayGridMonth,listMonth"
            : "dayGridMonth";

        const calendar = new FullCalendar.Calendar(el, {
            locale,
            initialView: vistaGuardada,
            initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,
            firstDay: 1,
            height: "auto",
            selectable: false, // drag-select desactivado
            selectMirror: false,
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
                    success([]);
                    return;
                }
                fetch(routes.eventosUrl)
                    .then((r) => r.json())
                    .then((events) => success(mergeDailyEvents(events)))
                    .catch(failure);
            },

            // Clic-clic para rango en ambos roles
            dateClick: function (info) {
                const clicked = info.dateStr;

                if (!startClick) {
                    startClick = clicked;
                    updateTempHighlight(calendar, clicked, clicked); // pinta sólo ese día
                    return;
                }

                // ✅ segundo clic en el mismo día → seleccionar 1 solo día
                if (clicked === startClick) {
                    const startStr = clicked;
                    const endStr = clicked;
                    clearTempHighlight(calendar);
                    startClick = null;

                    if (
                        permissions.canAssignStates ||
                        permissions.canAssignShifts
                    ) {
                        registrarEventoOficina(startStr, endStr, calendar);
                    } else if (permissions.canRequestVacations) {
                        pedirVacaciones(startStr, endStr, calendar);
                    }
                    return;
                }

                // rango normal (días distintos)
                const startStr = clicked < startClick ? clicked : startClick;
                const endStr = clicked < startClick ? startClick : clicked;

                clearTempHighlight(calendar);
                startClick = null;

                if (
                    permissions.canAssignStates ||
                    permissions.canAssignShifts
                ) {
                    registrarEventoOficina(startStr, endStr, calendar);
                } else if (permissions.canRequestVacations) {
                    pedirVacaciones(startStr, endStr, calendar);
                }
            },

            datesSet: function (info) {
                let fechaActual = info.startStr;
                if (calendar.view.type === "dayGridMonth") {
                    const mid = new Date(info.start);
                    mid.setDate(mid.getDate() + 15);
                    fechaActual = mid.toISOString().split("T")[0];
                }
                localStorage.setItem(storageKeyPrefix + "fecha", fechaActual);
                localStorage.setItem(
                    storageKeyPrefix + "vista",
                    calendar.view.type
                );
            },
        });

        // Cancelar rango con ESC
        document.addEventListener("keydown", (ev) => {
            if (ev.key === "Escape" && startClick) {
                startClick = null;
                clearTempHighlight(calendar);
            }
        });

        let rafId = null;
        function bindHoverCells() {
            // re-bind cada vez que cambia el mes/vista
            const cells = el.querySelectorAll(".fc-daygrid-day");
            cells.forEach((cell) => {
                cell.addEventListener("mouseenter", () => {
                    if (!startClick) return;
                    const day = cell.getAttribute("data-date");
                    if (day) updateTempHighlight(calendar, startClick, day);
                });
            });
        }

        calendar.render();
        bindHoverCells();
        actualizarResumenAsistencia(routes.resumenUrl);

        calendar.on("datesSet", bindHoverCells);
    }

    document.addEventListener("DOMContentLoaded", function () {
        qsAll(".fc-calendario").forEach(initCalendarOn);
    });
})();