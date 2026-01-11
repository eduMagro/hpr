// public/js/calendario/calendario.js
(function () {
    const qsAll = (sel, ctx = document) =>
        Array.from(ctx.querySelectorAll(sel));

    // Esperar a que FullCalendar esté disponible (máximo 5 segundos)
    function waitForFullCalendar(callback, maxAttempts = 50) {
        let attempts = 0;
        const check = () => {
            if (typeof FullCalendar !== "undefined") {
                callback();
            } else if (attempts < maxAttempts) {
                attempts++;
                setTimeout(check, 100);
            } else {
                console.error("FullCalendar no se cargó después de 5 segundos.");
            }
        };
        check();
    }
    const addDaysStr = (d, days) => {
        const x = new Date(d);
        x.setDate(x.getDate() + days);
        return x.toISOString().split("T")[0];
    };
    const addOneDayStr = (d) => addDaysStr(d, 1);

    function normalizeDailyEvents(events) {
        // Cada evento es individual por día - sin fusionar días consecutivos
        // NO añadimos 'end' para que FullCalendar los trate como eventos de un solo día
        return events.map((ev) => {
            const startISO = ev.startStr || ev.start || ev.startTime || ev.startDate;
            // Extraer solo la fecha (YYYY-MM-DD) sin hora
            let startStr;
            if (typeof startISO === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(startISO)) {
                // Ya es formato YYYY-MM-DD
                startStr = startISO;
            } else {
                const startDate = new Date(startISO);
                startStr = startDate.toISOString().split("T")[0];
            }

            // Devolver evento sin 'end' para que sea de un solo día
            const normalized = {
                ...ev,
                start: startStr,
                allDay: true,
            };
            // Eliminar 'end' si existe para evitar que se extienda a varios días
            delete normalized.end;
            delete normalized.endStr;
            return normalized;
        });
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
        
        let {
            fechaIncorporacion = null,
            diasVacacionesAsignados = 0,
        } = cfg;

        if (typeof fechaIncorporacion === 'undefined') fechaIncorporacion = null; // Safety check
        
        console.log('🔧 Config Calendario:', { userId, permissions, fechaIncorporacion, diasVacacionesAsignados });

        // Estado de selección "clic-clic"
        let startClick = null;
        let hoverDayEvs = [];

        // Datos de vacaciones para actualización dinámica
        let vacationData = null;

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

        function clearVacationBadges() {
            // Limpiar modal INFERIOR
            const modal = document.getElementById('vacation-bottom-modal');
            if (modal) {
                modal.classList.remove('translate-y-0');
                modal.classList.add('translate-y-full');
            }
            vacationData = null;
        }

        // Actualiza el modal con solo el botón de cancelar
        function updateVacationModal(diasSeleccionados) {
            const modal = document.getElementById('vacation-bottom-modal');
            const content = document.getElementById('vacation-bottom-content');
            if (!modal || !content) return;

            modal.classList.remove('translate-y-full');
            modal.classList.add('translate-y-0');

            content.innerHTML = `
                <div class="flex items-center gap-3 text-xs sm:text-sm">
                    <span class="text-amber-300">Selecciona dia final</span>
                    <button id="btn-cancelar-seleccion" style="background:#ef4444;color:white;padding:4px 12px;border-radius:6px;font-weight:600;font-size:12px;display:flex;align-items:center;gap:4px;border:none;cursor:pointer;">
                        Cancelar
                    </button>
                </div>
            `;

            // Añadir listener al botón cancelar
            const btnCancelar = document.getElementById('btn-cancelar-seleccion');
            if (btnCancelar) {
                btnCancelar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    startClick = null;
                    clearTempHighlight(window.calendar, false);
                });
            }
        }

        function clearTempHighlight(calendar, keepBadges = false) {
            if (!keepBadges) clearVacationBadges();
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

        // Cuenta días laborables (excluye fines de semana, festivos y vacaciones)
        function contarDiasLaborables(aStr, bStr, calendar) {
            const days = eachDayStr(aStr, bStr);
            const eventos = calendar.getEvents();

            // Crear set de fechas a excluir (festivos y vacaciones)
            const fechasExcluidas = new Set();
            eventos.forEach(ev => {
                const id = ev.id || '';
                const estado = ev.extendedProps?.estado || '';
                // Excluir festivos y vacaciones ya asignadas
                if (id.startsWith('festivo-') || estado === 'vacaciones') {
                    const fechaEvento = ev.startStr?.split('T')[0] || ev.start?.toISOString().split('T')[0];
                    if (fechaEvento) fechasExcluidas.add(fechaEvento);
                }
            });

            let count = 0;
            days.forEach(dayStr => {
                const date = new Date(dayStr);
                const diaSemana = date.getDay(); // 0=domingo, 6=sábado

                // Excluir fines de semana
                if (diaSemana === 0 || diaSemana === 6) return;

                // Excluir festivos y vacaciones
                if (fechasExcluidas.has(dayStr)) return;

                count++;
            });

            return count;
        }

        // pilla el día anterior al siguiente en string YYYY-MM-DD
        const addOneDayStr = (d) => {
            const x = new Date(d);
            x.setDate(x.getDate() + 1);
            return x.toISOString().split("T")[0];
        };

        function updateTempHighlight(calendar, startStr, hoverStr, isHover = true) {
            const forward = startStr <= hoverStr;
            const days = eachDayStr(startStr, hoverStr);
            const first = days[0];
            const last = days[days.length - 1];

            clearTempHighlight(calendar, isHover);

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

            // Validar días disponibles antes de enviar
            const diasSeleccionados = contarDiasLaborables(fechaInicio, fechaFin, calendar);

            // Obtener datos de vacaciones frescos para validar
            const baseUrl = routes.vacationDataUrl || `/usuarios/${userId}/vacation-data`;
            const fetchUrl = `${baseUrl}?fecha=${fechaInicio}`;

            let disponiblesTotal = 0;

            try {
                const response = await fetch(fetchUrl);
                if (!response.ok) throw new Error('Error al obtener datos');
                const data = await response.json();

                const fechaInc = data.fecha_incorporacion ? new Date(data.fecha_incorporacion) : null;
                const clickDate = new Date(fechaInicio);
                const clickYear = clickDate.getFullYear();
                const previousYear = clickYear - 1;

                const isGracePeriod = clickDate.getMonth() <= 2; // enero, febrero, marzo
                let disponiblesAnterior = 0;
                let disponiblesActual = 0;

                // Incluir días de solicitudes pendientes
                const diasSolicitadosAnterior = data.dias_solicitados_anterior || 0;
                const diasSolicitadosActual = data.dias_solicitados_actual || 0;
                const diasSolicitadosPeriodoGracia = data.dias_solicitados_periodo_gracia || 0;
                const diasSolicitadosPostGracia = data.dias_solicitados_post_gracia || 0;

                if (fechaInc && fechaInc < new Date(clickYear, 0, 1)) {
                    // Usuario incorporado antes de este año
                    const diasUsadosAnterior = (data.dias_asignados_anterior || 0) + diasSolicitadosAnterior;
                    const diasUsadosPeriodoGracia = (data.dias_usados_periodo_gracia || 0) + diasSolicitadosPeriodoGracia;
                    const diasUsadosPostGracia = (data.dias_usados_post_gracia || 0) + diasSolicitadosPostGracia;

                    const generadasAnterior = 22;
                    const saldoAnterior = Math.max(0, generadasAnterior - diasUsadosAnterior);

                    if (isGracePeriod) {
                        disponiblesAnterior = Math.max(0, saldoAnterior - diasUsadosPeriodoGracia);
                        const excesoSobreAnterior = Math.max(0, diasUsadosPeriodoGracia - saldoAnterior);
                        disponiblesActual = 22 - excesoSobreAnterior - diasUsadosPostGracia;
                        disponiblesTotal = disponiblesAnterior + disponiblesActual;
                    } else {
                        const excesoSobreAnterior = Math.max(0, diasUsadosPeriodoGracia - saldoAnterior);
                        disponiblesTotal = 22 - excesoSobreAnterior - diasUsadosPostGracia;
                    }
                } else {
                    // Usuario incorporado este año - cálculo proporcional PROGRESIVO
                    // Los días se activan proporcionalmente hasta la fecha de la solicitud
                    const diasUsadosEsteAnio = (data.dias_asignados_actual || 0) + diasSolicitadosActual;

                    if (fechaInc) {
                        const inicioAnio = new Date(clickYear, 0, 1);
                        const finDeAnio = new Date(clickYear, 11, 31);
                        const diasTotalesAnio = Math.ceil((finDeAnio - inicioAnio) / (1000 * 60 * 60 * 24)) + 1;

                        // Días desde incorporación hasta la fecha solicitada
                        const diasHastaFechaSolicitada = Math.max(0, Math.ceil((clickDate - fechaInc) / (1000 * 60 * 60 * 24)) + 1);
                        // Días que le corresponderían en todo el año
                        const diasDesdeIncorporacionHastaFinAnio = Math.ceil((finDeAnio - fechaInc) / (1000 * 60 * 60 * 24)) + 1;
                        const generadasTotalesAnio = Math.floor((diasDesdeIncorporacionHastaFinAnio / diasTotalesAnio) * 22);

                        // Días activados hasta la fecha solicitada (proporcional)
                        const proporcionTrabajada = Math.min(1, diasHastaFechaSolicitada / diasDesdeIncorporacionHastaFinAnio);
                        const generadasHastaFecha = Math.floor(generadasTotalesAnio * proporcionTrabajada);

                        disponiblesTotal = generadasHastaFecha - diasUsadosEsteAnio;
                    } else {
                        disponiblesTotal = 22 - diasUsadosEsteAnio;
                    }
                    disponiblesActual = Math.max(0, disponiblesTotal);
                }

            } catch (error) {
                console.error('Error obteniendo datos de vacaciones:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron verificar los días disponibles. Inténtalo de nuevo.',
                    confirmButtonColor: '#1e3a5f',
                });
                return;
            }

            const restantes = disponiblesTotal - diasSeleccionados;

            if (restantes < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Días insuficientes',
                    html: `
                        <p class="text-gray-600 mb-2">No tienes suficientes días de vacaciones disponibles.</p>
                        <p class="text-gray-600">Disponibles: <strong>${disponiblesTotal}</strong></p>
                        <p class="text-gray-600">Solicitados: <strong>${diasSeleccionados}</strong></p>
                        <p class="text-red-600 font-semibold mt-2">Te faltan ${Math.abs(restantes)} día(s)</p>
                    `,
                    confirmButtonColor: '#1e3a5f',
                });
                return;
            }

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

            const esMismoDia = fechaInicio === fechaFin;
            const mensajeFecha = esMismoDia
                ? `<p class="mb-2">${fechaInicio}</p>`
                : `<p class="mb-2">Desde: ${fechaInicio} — Hasta: ${fechaFin}</p>`;

            // Si es un solo día, buscar horas existentes en los eventos del calendario
            let entradaExistente = '';
            let salidaExistente = '';
            if (esMismoDia) {
                const eventos = calendar.getEvents();
                eventos.forEach(ev => {
                    const props = ev.extendedProps || {};
                    if (props.fecha === fechaInicio || (ev.startStr && ev.startStr.startsWith(fechaInicio))) {
                        if (props.entrada && !entradaExistente) {
                            entradaExistente = props.entrada.substring(0, 5);
                        }
                        if (props.salida && !salidaExistente) {
                            salidaExistente = props.salida.substring(0, 5);
                        }
                    }
                });
            }

            const { value: formData, isConfirmed } = await Swal.fire({
                title: null,
                html: `
                    <div style="text-align: left; overflow-x: hidden;">
                        <!-- Header con fecha -->
                        <div style="background: linear-gradient(135deg, #1e3a5f 0%, #111827 100%); color: white; margin: -20px -20px 20px -20px; padding: 20px; border-radius: 8px 8px 0 0;">
                            <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600;">📅 Gestionar Asignación</h3>
                            <p style="margin: 0; font-size: 14px; opacity: 0.9;">
                                ${esMismoDia ? fechaInicio : `${fechaInicio} → ${fechaFin}`}
                            </p>
                        </div>

                        <!-- Selector de turno/estado -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                Turno o Estado
                            </label>
                            <select id="tipo-dia" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: white; cursor: pointer; transition: border-color 0.2s;">
                                <option value="">⏱️ Solo actualizar horas</option>
                                <option value="eliminarTurnoEstado">🗑️ Eliminar turno</option>
                                ${opcionesTurnos}
                                <option disabled style="font-size: 8px;">─────────</option>
                                <option value="eliminarEstado">🗑️ Eliminar estado</option>
                                <option value="curso">🎓 Cursos</option>
                                <option value="vacaciones">🏖️ Vacaciones</option>
                                <option value="baja">🤒 Baja</option>
                                <option value="justificada">✅ Justificada</option>
                                <option value="injustificada">❌ Injustificada</option>
                            </select>
                        </div>

                        <!-- Campos de hora -->
                        <div style="background: #f9fafb; border-radius: 8px; padding: 16px; border: 1px solid #e5e7eb;">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 12px;">
                                Horario de fichaje
                            </label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div>
                                    <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #059669; margin-bottom: 6px; font-weight: 500;">
                                        <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block;"></span>
                                        Entrada
                                    </label>
                                    <input type="time" id="hora-entrada" value="${entradaExistente}"
                                        style="width: 100%; padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; font-family: monospace; transition: border-color 0.2s;">
                                </div>
                                <div>
                                    <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #dc2626; margin-bottom: 6px; font-weight: 500;">
                                        <span style="width: 8px; height: 8px; background: #ef4444; border-radius: 50%; display: inline-block;"></span>
                                        Salida
                                    </label>
                                    <input type="time" id="hora-salida" value="${salidaExistente}"
                                        style="width: 100%; padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; font-family: monospace; transition: border-color 0.2s;">
                                </div>
                            </div>
                            ${!esMismoDia ? `
                                <p style="margin: 12px 0 0 0; font-size: 12px; color: #6b7280; display: flex; align-items: center; gap: 6px;">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Deja vacío para mantener las horas actuales
                                </p>
                            ` : ''}
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: "Guardar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#1e3a5f",
                cancelButtonColor: "#6b7280",
                width: 420,
                padding: "20px",
                customClass: {
                    popup: 'swal-calendario-popup',
                    confirmButton: 'swal-btn-confirm',
                    cancelButton: 'swal-btn-cancel'
                },
                preConfirm: () => {
                    return {
                        tipo: document.getElementById("tipo-dia").value,
                        entrada: document.getElementById("hora-entrada").value || null,
                        salida: document.getElementById("hora-salida").value || null,
                    };
                },
                didOpen: () => {
                    // Añadir efectos hover a los inputs
                    const inputs = document.querySelectorAll('#hora-entrada, #hora-salida, #tipo-dia');
                    inputs.forEach(input => {
                        input.addEventListener('focus', () => input.style.borderColor = '#3b82f6');
                        input.addEventListener('blur', () => input.style.borderColor = '#e5e7eb');
                    });
                }
            });

            if (!isConfirmed || !formData) return;

            const tipoSeleccionado = formData.tipo;
            const horaEntrada = formData.entrada;
            const horaSalida = formData.salida;

            // --- Solo actualizar horas (sin cambiar turno/estado) ---
            if (!tipoSeleccionado) {
                // Validar que al menos una hora esté especificada
                if (!horaEntrada && !horaSalida) {
                    Swal.fire("Aviso", "Debes especificar al menos una hora para actualizar.", "warning");
                    return;
                }

                const body = {
                    user_id: userId,
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin,
                    tipo: "soloHoras",
                };
                if (horaEntrada) body.entrada = horaEntrada;
                if (horaSalida) body.salida = horaSalida;

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
                            Swal.fire("Actualizado", "Horas actualizadas correctamente.", "success");
                        } else {
                            Swal.fire("Error", data.error || "No se pudieron actualizar las horas.", "error");
                        }
                    })
                    .catch((err) => {
                        console.error("Error:", err);
                        Swal.fire("Error", "Ocurrió un problema al actualizar las horas.", "error");
                    });
                return;
            }

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

            // Añadir horas solo si se han especificado
            if (horaEntrada) body.entrada = horaEntrada;
            if (horaSalida) body.salida = horaSalida;

            // Si es vacaciones y hay días del año anterior disponibles, preguntar si usarlos primero
            if (tipoSeleccionado === 'vacaciones' && vacationData && vacationData.disponiblesAnterior > 0) {
                const fechaInicioDate = new Date(fechaInicio);
                const mes = fechaInicioDate.getMonth(); // 0=enero, 1=febrero, 2=marzo

                // Solo preguntar si estamos en período de gracia (enero-marzo)
                if (mes <= 2) {
                    const anioActual = fechaInicioDate.getFullYear();
                    const anioAnterior = anioActual - 1;
                    const diasSeleccionados = contarDiasLaborables(fechaInicio, fechaFin, calendar);

                    const { isConfirmed: usarAnterior } = await Swal.fire({
                        title: 'Días del año anterior',
                        html: `
                            <p class="text-sm text-gray-600 mb-4">Tienes <strong>${vacationData.disponiblesAnterior} días</strong> del año ${anioAnterior} que caducan el 31 de marzo.</p>
                            <p class="text-sm text-gray-600 mb-4">Estás solicitando <strong>${diasSeleccionados} días</strong> de vacaciones.</p>
                            <p class="text-sm text-gray-600 mb-4">¿Quieres usar primero los días del año ${anioAnterior}?</p>
                            ${diasSeleccionados > vacationData.disponiblesAnterior ?
                                `<p class="text-xs text-blue-600 mt-2"><em>Se asignarán ${Math.min(diasSeleccionados, vacationData.disponiblesAnterior)} días al ${anioAnterior} y ${diasSeleccionados - vacationData.disponiblesAnterior} días al ${anioActual}.</em></p>` :
                                ''}
                        `,
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: `Sí, usar días de ${anioAnterior}`,
                        denyButtonText: `No, usar solo ${anioActual}`,
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#1e3a5f',
                        denyButtonColor: '#6b7280',
                    });

                    if (Swal.DismissReason && Swal.DismissReason.cancel) {
                        // Usuario canceló
                    }

                    // Si el usuario confirma, usar año anterior primero
                    if (usarAnterior === true) {
                        body.usar_anterior_primero = true;
                        body.dias_disponibles_anterior = vacationData.disponiblesAnterior;
                        body.anio_anterior = anioAnterior;
                    } else if (usarAnterior === false) {
                        // Usuario eligió "No", usar solo año actual
                        body.anio_cargo = anioActual;
                    } else {
                        // Usuario canceló
                        return;
                    }
                }
            }

            // Validar que no se excedan los días disponibles de vacaciones
            if (tipoSeleccionado === 'vacaciones' && vacationData) {
                const diasSeleccionados = contarDiasLaborables(fechaInicio, fechaFin, calendar);
                const restantes = vacationData.disponiblesTotal - diasSeleccionados;

                if (restantes < 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Días insuficientes',
                        html: `
                            <p class="text-gray-600 mb-2">No tienes suficientes días de vacaciones disponibles.</p>
                            <p class="text-gray-600">Disponibles: <strong>${vacationData.disponiblesTotal}</strong></p>
                            <p class="text-gray-600">Solicitados: <strong>${diasSeleccionados}</strong></p>
                            <p class="text-red-600 font-semibold mt-2">Te faltan ${Math.abs(restantes)} día(s)</p>
                        `,
                        confirmButtonColor: '#1e3a5f',
                    });
                    return;
                }
            }

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
            displayEventTime: false, // La hora ya está en el título
            displayEventEnd: false,
            eventDisplay: 'block', // Mostrar eventos timed como bloques
            nextDayThreshold: '00:00:00', // Eventos que terminan a medianoche no pasan al día siguiente
            forceEventDuration: true, // Forzar duración por defecto
            defaultAllDayEventDuration: { days: 1 }, // Duración por defecto de 1 día
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

                // Cargar eventos normales Y solicitudes pendientes en paralelo
                const eventosPromise = fetch(routes.eventosUrl).then(r => r.json());
                // Usar solicitudesPendientesUrl (users.show) o misSolicitudesPendientesUrl (mi-perfil)
                const solicitudesUrl = routes.solicitudesPendientesUrl || routes.misSolicitudesPendientesUrl;
                const solicitudesPromise = solicitudesUrl
                    ? fetch(solicitudesUrl).then(r => r.json()).catch(() => [])
                    : Promise.resolve([]);

                Promise.all([eventosPromise, solicitudesPromise])
                    .then(([events, solicitudes]) => {
                        console.log('📅 Eventos recibidos del servidor:', events.length);
                        console.log('📋 Solicitudes pendientes:', solicitudes.length);

                        // Separar eventos allDay de eventos con hora (fichajes)
                        const allDayEvents = events.filter(ev => ev.allDay !== false);
                        const timedEvents = events.filter(ev => ev.allDay === false);

                        console.log('📅 Eventos allDay:', allDayEvents.length, 'Eventos con hora:', timedEvents.length);

                        // Normalizar solo los eventos allDay
                        const normalized = normalizeDailyEvents(allDayEvents);

                        // Verificar que no hay eventos con end multi-día
                        normalized.forEach(ev => {
                            if (ev.end) {
                                console.warn('⚠️ Evento con end:', ev.id, ev.start, ev.end);
                            }
                        });

                        // Obtener fechas de festivos de los eventos cargados
                        const fechasFestivos = new Set();
                        events.forEach(ev => {
                            if (ev.id?.startsWith('festivo-') || (ev.title && ev.backgroundColor === '#ff2800')) {
                                const fechaFestivo = ev.start?.split?.('T')?.[0] || ev.start;
                                if (fechaFestivo) fechasFestivos.add(fechaFestivo);
                            }
                        });

                        // Convertir solicitudes pendientes en eventos del calendario
                        // Excluir fines de semana y festivos
                        const eventosSolicitudes = [];
                        solicitudes.forEach(sol => {
                            // Crear un evento para cada día LABORABLE del rango de la solicitud
                            const inicio = new Date(sol.fecha_inicio);
                            const fin = new Date(sol.fecha_fin);
                            for (let d = new Date(inicio); d <= fin; d.setDate(d.getDate() + 1)) {
                                const diaSemana = d.getDay(); // 0=domingo, 6=sábado
                                // Saltar fines de semana
                                if (diaSemana === 0 || diaSemana === 6) continue;

                                const fechaStr = d.toISOString().split('T')[0];
                                // Saltar festivos
                                if (fechasFestivos.has(fechaStr)) continue;

                                eventosSolicitudes.push({
                                    id: `solicitud-${sol.id}-${fechaStr}`,
                                    title: 'V. pendiente',
                                    start: fechaStr,
                                    allDay: true,
                                    backgroundColor: '#fcdde8', // rosa
                                    borderColor: '#fcdde8',
                                    textColor: 'black',
                                    extendedProps: {
                                        tipo: 'solicitud_pendiente',
                                        solicitud_id: sol.id,
                                        fecha_inicio: sol.fecha_inicio,
                                        fecha_fin: sol.fecha_fin,
                                        fecha: fechaStr,
                                    },
                                });
                            }
                        });

                        // Combinar todos los tipos de eventos
                        const final = [...normalized, ...timedEvents, ...eventosSolicitudes];
                        console.log('📅 Total eventos a renderizar:', final.length);
                        success(final);
                    })
                    .catch(failure);
            },

            // Clic-clic para rango en ambos roles
            dateClick: function (info) {
                const clicked = info.dateStr;

                if (!startClick) {
                    // --- PRIMER CLIC ---
                    startClick = clicked;
                    updateTempHighlight(calendar, clicked, clicked, false); // false para que SI limpie/actualice el modal

                    // Mostrar inmediatamente el modal con instrucciones de selección
                    const modal = document.getElementById('vacation-bottom-modal');
                    const content = document.getElementById('vacation-bottom-content');
                    
                    if (modal && content) {
                        modal.classList.remove('translate-y-full');
                        modal.classList.add('translate-y-0');
                        
                        // Mostrar mensaje de selección activa con botón de cancelar - COMPACTO
                        content.innerHTML = `
                            <div class="flex items-center gap-3 text-xs sm:text-sm">
                                <span class="text-amber-300">Selecciona día final</span>
                                <button id="btn-cancelar-seleccion" style="background:#ef4444;color:white;padding:4px 12px;border-radius:6px;font-weight:600;font-size:12px;display:flex;align-items:center;gap:4px;border:none;cursor:pointer;">
                                    ✕ Cancelar
                                </button>
                            </div>
                        `;
                        
                        // Agregar event listener al botón de cancelar
                        const btnCancelar = document.getElementById('btn-cancelar-seleccion');
                        if (btnCancelar) {
                            btnCancelar.addEventListener('click', function(e) {
                                e.stopPropagation();
                                startClick = null;
                                clearTempHighlight(calendar, false);
                            });
                        }
                    }

                    // AJAX Fetch para datos frescos de vacaciones (solo en el primer clic)
                    // Enviar la fecha clickeada para que el backend calcule relativo a esa fecha
                    const baseUrl = routes.vacationDataUrl || `/api/usuarios/${userId}/vacation-data`;
                    const fetchUrl = `${baseUrl}?fecha=${clicked}`;
                    fetch(fetchUrl)
                        .then(r => {
                            if (!r.ok) {
                                throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                            }
                            const contentType = r.headers.get('content-type') || '';
                            if (!contentType.includes('application/json')) {
                                throw new Error('La respuesta no es JSON');
                            }
                            return r.json();
                        })
                        .then(data => {
                            if (data.error) throw new Error(data.error);
                            
                            fechaIncorporacion = data.fecha_incorporacion;
                            diasVacacionesAsignados = data.dias_asignados;

                            // Botón cancelar con estilo inline para máximo contraste
                            const cancelarBtnHtml = `
                                <button id="btn-cancelar-seleccion" style="background:#ef4444;color:white;padding:4px 12px;border-radius:6px;font-weight:600;font-size:12px;display:flex;align-items:center;gap:4px;border:none;cursor:pointer;">
                                    Cancelar
                                </button>
                            `;
                            
                            if (fechaIncorporacion) {
                                const incorpDate = new Date(fechaIncorporacion);
                                const clickDate = new Date(clicked);
                                
                                if (clickDate >= incorpDate) {
                                    const clickYear = clickDate.getFullYear();
                                    const clickMonth = clickDate.getMonth(); // 0-indexed (0=enero, 2=marzo)
                                    
                                    // Detectar período de gracia: 1 enero - 31 marzo
                                    const isGracePeriod = clickMonth <= 2; // enero, febrero, marzo
                                    const previousYear = clickYear - 1;
                                    
                                    if (modal && content) {
                                        modal.classList.remove('translate-y-full');
                                        modal.classList.add('translate-y-0');
                                        
                                        // Calcular vacaciones GENERADAS del año anterior (hasta 31 dic)
                                        const endOfPrevYear = new Date(previousYear, 11, 31);
                                        let generadasAnterior = 0;
                                        
                                        if (incorpDate < new Date(clickYear, 0, 1)) {
                                            // La persona ya trabajaba antes de este año
                                            const prevYearStart = incorpDate > new Date(previousYear, 0, 1) ? incorpDate : new Date(previousYear, 0, 1);
                                            const diffTimePrev = Math.max(0, endOfPrevYear - prevYearStart);
                                            const diffDaysPrev = Math.ceil(diffTimePrev / (1000 * 60 * 60 * 24)) + 1;
                                            generadasAnterior = Math.floor(Math.min((diffDaysPrev / 30) * 2.5, 22)); // Truncado, Max 22 días
                                        }
                                        
                                        // Días usados del año anterior (en fechas del año anterior) + solicitados
                                        const diasSolicitadosAnterior = data.dias_solicitados_anterior || 0;
                                        const diasSolicitadosPeriodoGracia = data.dias_solicitados_periodo_gracia || 0;
                                        const diasSolicitadosPostGracia = data.dias_solicitados_post_gracia || 0;

                                        const usadasAnteriorDirec = (data.dias_asignados_anterior || 0) + diasSolicitadosAnterior;

                                        // Saldo del año anterior AL FINALIZAR el año anterior
                                        const saldoAnteriorAlFinalizar = generadasAnterior - usadasAnteriorDirec;

                                        // Días usados durante el período de gracia (1 ene - 31 mar del año actual) + solicitados
                                        const usadasPeriodoGracia = (data.dias_usados_periodo_gracia || 0) + diasSolicitadosPeriodoGracia;

                                        // Días usados después del período de gracia (1 abril en adelante) + solicitados
                                        const usadasPostGracia = (data.dias_usados_post_gracia || 0) + diasSolicitadosPostGracia;

                                        if (isGracePeriod && incorpDate < new Date(clickYear, 0, 1)) {
                                            // === PERÍODO DE GRACIA (1 ene - 31 mar) ===
                                            // Saldo del año anterior (nunca negativo)
                                            const saldoAnteriorPositivo = Math.max(0, saldoAnteriorAlFinalizar);

                                            // Cuántas del año anterior quedan después de descontar las del período de gracia
                                            const disponiblesAnterior = Math.max(0, saldoAnteriorPositivo - usadasPeriodoGracia);

                                            // Si usó más que las del año anterior, el exceso viene del año actual
                                            const excesoSobreAnterior = Math.max(0, usadasPeriodoGracia - saldoAnteriorPositivo);

                                            // Si entró antes de este año, tiene los 22 días completos
                                            const generadasActual = 22;

                                            // Disponibles del año actual = generadas - exceso - post gracia ya usadas
                                            const disponiblesActual = generadasActual - excesoSobreAnterior - usadasPostGracia;
                                            const disponiblesTotal = disponiblesAnterior + disponiblesActual;

                                            // Guardar datos para actualización dinámica
                                            vacationData = {
                                                disponiblesTotal,
                                                disponiblesAnterior,
                                                previousYear,
                                                clickYear,
                                                colorBase: 'text-emerald-400'
                                            };

                                            // Mostrar modal inicial
                                            updateVacationModal(0);
                                        } else if (!isGracePeriod && incorpDate < new Date(clickYear, 0, 1)) {
                                            // === DESPUÉS DEL PERÍODO DE GRACIA (1 abril en adelante) ===
                                            const usadasDelAnteriorEnGracia = Math.min(usadasPeriodoGracia, Math.max(0, saldoAnteriorAlFinalizar));
                                            const excesoSobreAnterior = Math.max(0, usadasPeriodoGracia - Math.max(0, saldoAnteriorAlFinalizar));
                                            const perdidas = Math.max(0, saldoAnteriorAlFinalizar - usadasPeriodoGracia);

                                            // Si entró antes de este año, tiene los 22 días completos
                                            const generadasActual = 22;
                                            const usadasTotalActual = excesoSobreAnterior + usadasPostGracia;
                                            const disponiblesActual = generadasActual - usadasTotalActual;

                                            // Guardar datos para actualización dinámica
                                            vacationData = {
                                                disponiblesTotal: disponiblesActual,
                                                disponiblesAnterior: 0,
                                                previousYear,
                                                clickYear,
                                                colorBase: 'text-green-400',
                                                perdidas
                                            };

                                            // Mostrar modal inicial
                                            updateVacationModal(0);
                                        } else {
                                            // === PERSONA INCORPORADA ESTE AÑO: cálculo proporcional PROGRESIVO ===
                                            // Incluir días solicitados pendientes
                                            const diasSolicitadosActual = data.dias_solicitados_actual || 0;
                                            const diasUsadosEsteAnio = (data.dias_asignados_actual || 0) + diasSolicitadosActual;

                                            const inicioAnio = new Date(clickYear, 0, 1);
                                            const finDeAnio = new Date(clickYear, 11, 31);
                                            const diasTotalesAnio = Math.ceil((finDeAnio - inicioAnio) / (1000 * 60 * 60 * 24)) + 1;

                                            // Días desde incorporación hasta la fecha clickeada
                                            const diasHastaFechaClickeada = Math.max(0, Math.ceil((clickDate - incorpDate) / (1000 * 60 * 60 * 24)) + 1);
                                            // Días que le corresponderían en todo el año
                                            const diasDesdeIncorporacionHastaFinAnio = Math.ceil((finDeAnio - incorpDate) / (1000 * 60 * 60 * 24)) + 1;
                                            const generadasTotalesAnio = Math.floor((diasDesdeIncorporacionHastaFinAnio / diasTotalesAnio) * 22);

                                            // Días activados hasta la fecha clickeada (proporcional)
                                            const proporcionTrabajada = Math.min(1, diasHastaFechaClickeada / diasDesdeIncorporacionHastaFinAnio);
                                            const generadasHastaFecha = Math.floor(generadasTotalesAnio * proporcionTrabajada);

                                            const disponibles = generadasHastaFecha - diasUsadosEsteAnio;

                                            // Guardar datos para actualización dinámica
                                            vacationData = {
                                                disponiblesTotal: Math.max(0, disponibles),
                                                disponiblesAnterior: 0,
                                                previousYear: clickDate.getFullYear() - 1,
                                                clickYear: clickDate.getFullYear(),
                                                colorBase: 'text-green-400',
                                                generadasHastaFecha,
                                                generadasTotalesAnio
                                            };

                                            // Mostrar modal inicial
                                            updateVacationModal(0);
                                        }
                                    }
                                }
                            } else {
                                // Mostrar mensaje cuando no hay fecha de incorporación
                                if (modal && content) {
                                    modal.classList.remove('translate-y-full');
                                    modal.classList.add('translate-y-0');

                                    content.innerHTML = `
                                        <div class="flex items-center gap-4 text-sm">
                                            <span class="text-yellow-400">
                                                Falta configurar tu fecha de incorporacion
                                            </span>
                                            ${cancelarBtnHtml}
                                        </div>
                                    `;

                                    const btnCancelar = document.getElementById('btn-cancelar-seleccion');
                                    if (btnCancelar) {
                                        btnCancelar.addEventListener('click', function(e) {
                                            e.stopPropagation();
                                            startClick = null;
                                            clearTempHighlight(calendar, false);
                                        });
                                    }
                                }
                            }
                        })
                        .catch(e => console.error("Error fetching vacation data:", e));
                    
                    return;
                }

                // --- SEGUNDO CLIC ---
                const startStr = clicked < startClick ? clicked : startClick;
                const endStr = clicked < startClick ? startClick : clicked;
                
                // Limpiamos todo antes de la acción
                clearTempHighlight(calendar, false);
                const tempStart = startClick;
                startClick = null;

                if (clicked === tempStart) {
                    // Un solo día
                    if (permissions.canAssignStates || permissions.canAssignShifts) {
                        registrarEventoOficina(clicked, clicked, calendar);
                    } else if (permissions.canRequestVacations) {
                        pedirVacaciones(clicked, clicked, calendar);
                    }
                } else {
                    // Rango
                    if (permissions.canAssignStates || permissions.canAssignShifts) {
                        registrarEventoOficina(startStr, endStr, calendar);
                    } else if (permissions.canRequestVacations) {
                        pedirVacaciones(startStr, endStr, calendar);
                    }
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

            // Click en evento para mostrar tooltip con detalles
            eventClick: function (info) {
                const event = info.event;
                const props = event.extendedProps || {};

                // Ignorar eventos de fondo (selección de rango)
                if (event.display === 'background' || props.__tempHover) return;

                // Ignorar festivos
                if (event.id?.startsWith('festivo-')) return;

                // --- SOLICITUDES PENDIENTES: mostrar modal de gestión ---
                if (props.tipo === 'solicitud_pendiente') {
                    const solicitudId = props.solicitud_id;
                    const fechaInicio = props.fecha_inicio;
                    const fechaFin = props.fecha_fin;
                    const fechaActual = props.fecha;

                    // Verificar si se puede eliminar (solo en mi-perfil, no en users.show de otros)
                    const puedeEliminar = !!routes.eliminarSolicitudUrl;

                    // Si no puede eliminar, mostrar solo modal informativo
                    if (!puedeEliminar) {
                        const esMismoDiaInfo = fechaInicio === fechaFin;
                        Swal.fire({
                            title: 'Solicitud de Vacaciones Pendiente',
                            html: `
                                <div style="text-align: left;">
                                    <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px;">
                                        <p style="margin: 0; font-size: 14px; color: #92400e;">
                                            <strong>Estado:</strong> Pendiente de aprobacion
                                        </p>
                                        <p style="margin: 4px 0 0 0; font-size: 13px; color: #92400e;">
                                            ${esMismoDiaInfo ? `Fecha: ${fechaInicio}` : `Del ${fechaInicio} al ${fechaFin}`}
                                        </p>
                                    </div>
                                </div>
                            `,
                            confirmButtonText: 'Cerrar',
                            confirmButtonColor: '#3b82f6',
                            width: 400,
                        });
                        return;
                    }

                    // Obtener festivos del calendario
                    const eventosCal = calendar.getEvents();
                    const festivosCal = new Set();
                    eventosCal.forEach(ev => {
                        if (ev.id?.startsWith('festivo-')) {
                            const fechaFestivo = ev.startStr?.split('T')[0] || ev.start?.toISOString().split('T')[0];
                            if (fechaFestivo) festivosCal.add(fechaFestivo);
                        }
                    });

                    // Calcular solo los días LABORABLES del rango (sin fines de semana ni festivos)
                    const dias = [];
                    const inicio = new Date(fechaInicio);
                    const fin = new Date(fechaFin);
                    for (let d = new Date(inicio); d <= fin; d.setDate(d.getDate() + 1)) {
                        const diaSemana = d.getDay(); // 0=domingo, 6=sábado
                        // Saltar fines de semana
                        if (diaSemana === 0 || diaSemana === 6) continue;

                        const fechaStr = d.toISOString().split('T')[0];
                        // Saltar festivos
                        if (festivosCal.has(fechaStr)) continue;

                        dias.push(fechaStr);
                    }

                    const esMismoDia = dias.length === 1;

                    // Generar checkboxes para cada día laborable
                    const checkboxesHtml = dias.map(dia => {
                        const esActual = dia === fechaActual;
                        return `
                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-100 cursor-pointer ${esActual ? 'bg-amber-50 border border-amber-200' : ''}">
                                <input type="checkbox" name="dias_eliminar" value="${dia}" class="w-4 h-4 text-red-600 rounded">
                                <span class="text-sm ${esActual ? 'font-semibold' : ''}">${dia}</span>
                                ${esActual ? '<span class="text-xs text-amber-600">(seleccionado)</span>' : ''}
                            </label>
                        `;
                    }).join('');

                    Swal.fire({
                        title: 'Solicitud de Vacaciones Pendiente',
                        html: `
                            <div style="text-align: left;">
                                <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                                    <p style="margin: 0; font-size: 14px; color: #92400e;">
                                        <strong>Estado:</strong> Pendiente de aprobacion
                                    </p>
                                    <p style="margin: 4px 0 0 0; font-size: 13px; color: #92400e;">
                                        ${esMismoDia ? `Fecha: ${fechaInicio}` : `Del ${fechaInicio} al ${fechaFin}`}
                                    </p>
                                </div>

                                ${!esMismoDia ? `
                                <div style="margin-bottom: 16px;">
                                    <p style="font-size: 13px; color: #4b5563; margin-bottom: 8px;">
                                        Selecciona los dias que quieres eliminar de la solicitud:
                                    </p>
                                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px;">
                                        ${checkboxesHtml}
                                    </div>
                                </div>
                                ` : ''}

                                <p style="font-size: 12px; color: #6b7280; margin-top: 12px;">
                                    ${esMismoDia
                                        ? 'Pulsa "Eliminar solicitud" para cancelar esta peticion.'
                                        : 'Pulsa "Eliminar seleccionados" para quitar los dias marcados, o "Eliminar toda" para cancelar la solicitud completa.'
                                    }
                                </p>
                            </div>
                        `,
                        showCancelButton: true,
                        showDenyButton: !esMismoDia,
                        confirmButtonText: esMismoDia ? 'Eliminar solicitud' : 'Eliminar seleccionados',
                        denyButtonText: 'Eliminar toda',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#ef4444',
                        denyButtonColor: '#dc2626',
                        width: 450,
                        preConfirm: () => {
                            if (esMismoDia) {
                                return { action: 'eliminar_todo' };
                            }
                            const checkboxes = document.querySelectorAll('input[name="dias_eliminar"]:checked');
                            const diasSeleccionados = Array.from(checkboxes).map(cb => cb.value);
                            if (diasSeleccionados.length === 0) {
                                Swal.showValidationMessage('Selecciona al menos un dia para eliminar');
                                return false;
                            }
                            return { action: 'eliminar_dias', dias: diasSeleccionados };
                        },
                    }).then(async (result) => {
                        if (result.isDenied) {
                            // Eliminar toda la solicitud
                            const confirmacion = await Swal.fire({
                                title: 'Confirmar eliminacion',
                                text: 'Se eliminara toda la solicitud de vacaciones. Esta accion no se puede deshacer.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Si, eliminar',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#ef4444',
                            });

                            if (confirmacion.isConfirmed) {
                                fetch(`${routes.eliminarSolicitudUrl}/${solicitudId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Eliminada', data.message, 'success');
                                        smartRefetch(calendar);
                                    } else {
                                        Swal.fire('Error', data.error || 'No se pudo eliminar la solicitud.', 'error');
                                    }
                                })
                                .catch(err => {
                                    console.error('Error:', err);
                                    Swal.fire('Error', 'Ocurrio un problema al eliminar la solicitud.', 'error');
                                });
                            }
                        } else if (result.isConfirmed) {
                            const { action, dias: diasEliminar } = result.value;

                            if (action === 'eliminar_todo' || (diasEliminar && diasEliminar.length === dias.length)) {
                                // Eliminar toda la solicitud
                                fetch(`${routes.eliminarSolicitudUrl}/${solicitudId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Eliminada', data.message, 'success');
                                        smartRefetch(calendar);
                                    } else {
                                        Swal.fire('Error', data.error || 'No se pudo eliminar la solicitud.', 'error');
                                    }
                                })
                                .catch(err => {
                                    console.error('Error:', err);
                                    Swal.fire('Error', 'Ocurrio un problema al eliminar la solicitud.', 'error');
                                });
                            } else if (diasEliminar && diasEliminar.length > 0) {
                                // Eliminar días específicos
                                fetch(routes.eliminarDiasSolicitudUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                    body: JSON.stringify({
                                        solicitud_id: solicitudId,
                                        fechas_eliminar: diasEliminar,
                                    }),
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Modificada', data.message, 'success');
                                        smartRefetch(calendar);
                                    } else {
                                        Swal.fire('Error', data.error || 'No se pudo modificar la solicitud.', 'error');
                                    }
                                })
                                .catch(err => {
                                    console.error('Error:', err);
                                    Swal.fire('Error', 'Ocurrio un problema al modificar la solicitud.', 'error');
                                });
                            }
                        }
                    });

                    return; // Salir, no mostrar tooltip normal
                }

                // Eliminar tooltip existente
                const existente = document.getElementById('evento-tooltip');
                if (existente) existente.remove();

                const obraNombre = props.obra_nombre || null;
                const entrada = props.entrada ? props.entrada.substring(0, 5) : null;
                const salida = props.salida ? props.salida.substring(0, 5) : null;

                // Si no hay datos que mostrar, no hacer nada
                if (!obraNombre && !entrada && !salida) return;

                // Crear tooltip
                const tooltip = document.createElement('div');
                tooltip.id = 'evento-tooltip';
                tooltip.style.cssText = `
                    position: fixed;
                    z-index: 9999;
                    background: #1f2937;
                    color: white;
                    padding: 8px 12px;
                    border-radius: 6px;
                    font-size: 12px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    max-width: 250px;
                    pointer-events: none;
                `;

                let html = '';
                if (obraNombre) {
                    html += `<div style="margin-bottom: 4px;"><strong>📍 Obra:</strong> ${obraNombre}</div>`;
                }
                if (entrada || salida) {
                    html += `<div><strong>🕐 Horario:</strong> `;
                    if (entrada) html += entrada;
                    if (entrada && salida) html += ' - ';
                    if (salida) html += salida;
                    html += `</div>`;
                }
                tooltip.innerHTML = html;

                document.body.appendChild(tooltip);

                // Posicionar cerca del evento
                const rect = info.el.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();

                let top = rect.bottom + 5;
                let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);

                // Ajustar si se sale de pantalla
                if (left < 10) left = 10;
                if (left + tooltipRect.width > window.innerWidth - 10) {
                    left = window.innerWidth - tooltipRect.width - 10;
                }
                if (top + tooltipRect.height > window.innerHeight - 10) {
                    top = rect.top - tooltipRect.height - 5;
                }

                tooltip.style.top = top + 'px';
                tooltip.style.left = left + 'px';

                // Cerrar al hacer clic en cualquier lugar
                const cerrarTooltip = (e) => {
                    if (!tooltip.contains(e.target)) {
                        tooltip.remove();
                        document.removeEventListener('click', cerrarTooltip);
                    }
                };
                setTimeout(() => document.addEventListener('click', cerrarTooltip), 10);

                // Auto-cerrar después de 3 segundos
                setTimeout(() => {
                    if (document.getElementById('evento-tooltip')) {
                        tooltip.remove();
                        document.removeEventListener('click', cerrarTooltip);
                    }
                }, 3000);
            },
        });

        // Cancelar rango con ESC
        document.addEventListener("keydown", (ev) => {
            if (ev.key === "Escape" && startClick) {
                startClick = null;
                clearTempHighlight(calendar);
                // clearVacationBadges() is already called inside clearTempHighlight
            }
        });

        let rafId = null;
        function bindHoverCells() {
            const cells = el.querySelectorAll(".fc-daygrid-day");
            cells.forEach((cell) => {
                cell.addEventListener("mouseenter", () => {
                    if (!startClick) return;
                    const day = cell.getAttribute("data-date");
                    if (day) {
                        updateTempHighlight(calendar, startClick, day, true);
                        // Calcular días laborables (sin fines de semana, festivos ni vacaciones)
                        const diasSeleccionados = contarDiasLaborables(startClick, day, calendar);
                        updateVacationModal(diasSeleccionados);
                    }
                });
            });

            // Si el cursor sale de la tabla de días, restauramos el highlight solo del primer día
            const table = el.querySelector('.fc-scrollgrid-sync-table');
            if (table) {
                table.addEventListener('mouseleave', () => {
                    if (startClick) {
                        updateTempHighlight(calendar, startClick, startClick, true);
                        // Calcular si el día inicial es laborable
                        const diasSeleccionados = contarDiasLaborables(startClick, startClick, calendar);
                        updateVacationModal(diasSeleccionados);
                    }
                });
            }
        }

        calendar.render();
        bindHoverCells();
        actualizarResumenAsistencia(routes.resumenUrl);

        calendar.on("datesSet", bindHoverCells);

        // Exponer calendario globalmente para poder refrescar desde otros scripts
        window.calendar = calendar;
    }

    // Función para inicializar calendarios que no han sido inicializados
    function initCalendars() {
        const calendarios = qsAll(".fc-calendario");
        if (calendarios.length === 0) return;

        // Esperar a que FullCalendar esté disponible
        waitForFullCalendar(() => {
            calendarios.forEach((el) => {
                // Solo inicializar si no tiene ya un calendario
                if (!el.classList.contains("fc-initialized")) {
                    el.classList.add("fc-initialized");
                    initCalendarOn(el);
                }
            });
        });
    }

    // Inicializar en carga inicial
    if (document.readyState === 'loading') {
        document.addEventListener("DOMContentLoaded", initCalendars);
    } else {
        // DOM ya está listo
        initCalendars();
    }

    // Reinicializar después de navegación Livewire (SPA)
    document.addEventListener("livewire:navigated", initCalendars);

    // Recargar eventos cuando se sube un justificante
    document.addEventListener("livewire:initialized", () => {
        Livewire.on("justificante-guardado", () => {
            if (window.calendar) {
                window.calendar.refetchEvents();
            }
        });
    });
})();