// public/js/usersJs/calendario.js
(function () {
    if (typeof FullCalendar === "undefined") {
        console.error("FullCalendar no está cargado.");
        return;
    }

    // Utils
    const qsAll = (sel, ctx = document) =>
        Array.from(ctx.querySelectorAll(sel));
    const addDaysStr = (yyyy_mm_dd, days) => {
        const d = new Date(yyyy_mm_dd);
        d.setDate(d.getDate() + days);
        return d.toISOString().split("T")[0];
    };
    const addOneDayStr = (yyyy_mm_dd) => addDaysStr(yyyy_mm_dd, 1);

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
                    last.extendedProps.asignacion_id = null;
                    last.extendedProps.merged = true;
                }
            } else {
                merged.push({ ...ev, __key: keyOf(ev) });
            }
        }
        return merged.map(({ __key, ...rest }) => rest);
    }

    // Actualiza el resumen
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
            mobileBreakpoint = 768, // ya no se usa para limitar selección
            permissions = {
                canRequestVacations: false,
                canEditHours: false,
                canAssignShifts: false,
                canAssignStates: false,
            },
        } = cfg;

        // Estado de selección por dos clics (desktop y móvil)
        let startClick = null; // 'YYYY-MM-DD' del primer clic
        let hoverBg = null; // background event temporal para sombrear

        // Helpers de highlight
        function clearTempHighlight() {
            if (hoverBg) {
                hoverBg.remove();
                hoverBg = null;
            }
        }
        function showTempHighlight(cal, startStr, endStr) {
            clearTempHighlight();
            // endStr es inclusivo → FC espera end exclusivo
            hoverBg = cal.addEvent({
                start: startStr,
                end: addOneDayStr(endStr),
                display: "background",
                overlap: false,
                classNames: ["bg-selection-temp"],
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

        async function solicitarVacaciones(fechaInicio, fechaFin) {
            const mensajeFecha =
                fechaInicio === fechaFin
                    ? `<p>${fechaInicio}</p>`
                    : `<p>Desde: ${fechaInicio}</p><p>Hasta: ${fechaFin}</p>`;

            const { isConfirmed } = await Swal.fire({
                title: "Solicitar vacaciones",
                html: `${mensajeFecha}<p class="mt-2 text-sm text-gray-600">Se enviará una solicitud para revisión.</p>`,
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
                        calendar.refetchEvents();
                        setTimeout(
                            () =>
                                actualizarResumenAsistencia(routes.resumenUrl),
                            200
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

        const rightButtons = enableListMonth
            ? "dayGridMonth,listMonth"
            : "dayGridMonth";

        const calendar = new FullCalendar.Calendar(el, {
            locale,
            initialView: vistaGuardada,
            initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,
            firstDay: 1,
            height: "auto",
            selectable: false, // 🔒 desactivamos drag-select
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

            // ✅ Selección SIEMPRE por dos clics (primer día y último día)
            dateClick: function (info) {
                if (!permissions.canRequestVacations) return;

                const clicked = info.dateStr;

                // 1º clic → guardar inicio y sombrear ese día
                if (!startClick) {
                    startClick = clicked;
                    showTempHighlight(calendar, clicked, clicked);
                    return;
                }

                // 2º clic → calcular rango [min, max], limpiar highlight y solicitar
                const startStr = clicked < startClick ? clicked : startClick;
                const endStr = clicked < startClick ? startClick : clicked;

                clearTempHighlight();
                const s = startStr;
                const e = endStr; // por claridad
                startClick = null;

                solicitarVacaciones(s, e);
            },

            // (opcional) mover mes mantiene preferencia
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

        // Permite cancelar la selección con ESC
        document.addEventListener("keydown", (ev) => {
            if (ev.key === "Escape" && startClick) {
                startClick = null;
                clearTempHighlight();
            }
        });

        calendar.render();
        actualizarResumenAsistencia(routes.resumenUrl);
    }

    document.addEventListener("DOMContentLoaded", function () {
        qsAll(".fc-calendario").forEach(initCalendarOn);
    });
})();
