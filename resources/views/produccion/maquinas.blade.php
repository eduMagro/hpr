<x-app-layout>
    <x-slot name="title">Planificación por Máquina</x-slot>
    <x-menu.planillas />
    <div class="py-6">
        @if (!empty($erroresPlanillas))
            <div class="mb-4 bg-yellow-100 text-yellow-800 p-4 rounded shadow">
                <h3 class="font-semibold">Advertencias de planificación:</h3>
                <ul class="list-disc pl-5 text-sm mt-2">
                    @foreach ($erroresPlanillas as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-4">
            <div id="calendario" class="h-[80vh] w-full"></div>
        </div>
    </div>

    <div class="mt-6 mb-4 flex flex-col sm:flex-row items-center gap-4 px-4">
        <label class="text-sm">
            Desde:
            <input type="date" id="fechaInicio" class="border rounded px-2 py-1 ml-1"
                value="{{ now()->subDays(7)->toDateString() }}">
        </label>
        <label class="text-sm">
            Hasta:
            <input type="date" id="fechaFin" class="border rounded px-2 py-1 ml-1"
                value="{{ now()->toDateString() }}">
        </label>

        <label class="text-sm">
            Turno:
            <select id="turnoFiltro" class="border rounded text-sm px-2 py-1 ml-1">
                <option value="">Todos</option>
                <option value="mañana">Mañana</option>
                <option value="tarde">Tarde</option>
                <option value="noche">Noche</option>
            </select>
        </label>

        <button id="filtrarFechas"
            class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition text-sm">
            Aplicar Filtro
        </button>

        <p id="rango-aplicado" class="text-sm text-gray-600 mt-2 px-4"></p>
    </div>

    <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($maquinas as $maquina)
            <div class="bg-white shadow rounded-lg p-3">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-sm font-semibold">{{ $maquina->nombre }}</h3>
                    <select data-id="{{ $maquina->id }}" data-nombre="{{ $maquina->nombre }}"
                        class="estado-maquina border rounded text-sm px-2 py-1">
                        <option value="activa" {{ $maquina->estado === 'activa' ? 'selected' : '' }}>🟢 Activa</option>
                        <option value="averiada" {{ $maquina->estado === 'averiada' ? 'selected' : '' }}>🔴 Averiada
                        </option>
                        <option value="mantenimiento" {{ $maquina->estado === 'mantenimiento' ? 'selected' : '' }}>🛠️
                            Mantenimiento</option>
                        <option value="pausa" {{ $maquina->estado === 'pausa' ? 'selected' : '' }}>⏸️ Pausa</option>
                    </select>
                </div>
                <canvas id="grafico-maquina-{{ $maquina->id }}" class="mx-auto h-[180px]"></canvas>
            </div> {{-- <- CIERRA aquí la tarjeta --}}
        @endforeach
    </div>


    <style>
        canvas {
            height: 180px !important;
            max-height: 180px !important;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const maquinas = @json($resources);
            const planillas = @json($planillasEventos);
            const cargaTurnoResumen = @json($cargaTurnoResumen);
            const planDetallado = @json($planDetallado);
            const realDetallado = @json($realDetallado);

            // ------- FullCalendar -------
            const calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                initialView: 'resourceTimeGridDay',
                views: {
                    resourceTimeGridDay: {
                        type: 'resourceTimeGrid',
                        duration: {
                            days: 1
                        },
                        slotMinTime: '00:00:00',
                        slotMaxTime: '24:00:00',
                        slotDuration: '01:00:00',
                        slotLabelFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        },
                        buttonText: '1 día'
                    },
                    resourceTimeGridFiveDays: {
                        type: 'resourceTimeGrid',
                        duration: {
                            days: 1
                        },
                        slotMinTime: '00:00:00',
                        slotMaxTime: '120:00:00',
                        slotDuration: '01:00:00',
                        slotLabelFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        },
                        dayHeaderFormat: {
                            weekday: 'short',
                            day: 'numeric',
                            month: 'short'
                        },
                        buttonText: '5 días'
                    },
                    resourceTimeGrid30Days: {
                        type: 'resourceTimeGrid',
                        duration: {
                            days: 1
                        },
                        slotMinTime: '00:00:00',
                        slotMaxTime: '120:00:00',
                        slotDuration: '01:00:00',
                        slotLabelFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        },
                        dayHeaderFormat: {
                            weekday: 'short',
                            day: 'numeric',
                            month: 'short'
                        },
                        buttonText: '5 días'
                    }
                },
                locale: 'es',
                timeZone: 'Europe/Madrid',
                initialDate: "{{ $initialDate }}",
                resources: maquinas,
                events: planillas,
                height: 'auto',
                scrollTime: '06:00:00',
                editable: true,
                eventResizableFromStart: false,
                eventDurationEditable: false,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'resourceTimeGridDay,resourceTimeGridFiveDays'
                },

                // 🎨 NUEVO: Asignar clases a las franjas horarias según turno
                slotLaneClassNames: function(arg) {
                    const hour = arg.date.getHours();

                    // Turno mañana: 6-14 (6:00 hasta 13:59)
                    if (hour > 6 && hour <= 14) {
                        return ['turno-manana'];
                    }
                    // Turno tarde: 14-22 (14:00 hasta 21:59)
                    if (hour >= 14 && hour <= 22) {
                        return ['turno-tarde'];
                    }
                    // Turno noche: 22-6 (22:00 hasta 5:59)
                    if (hour >= 22 || hour <= 6) {
                        return ['turno-noche'];
                    }

                    return [];
                },
                // 🏷️ NUEVO: Personalizar etiquetas de horas para mostrar turno
                slotLabelContent: function(arg) {
                    const hour = arg.date.getHours();
                    const timeText = arg.text;

                    let turnoIcon = '';

                    // Primera hora de cada turno: añadir etiqueta
                    if (hour === 7) {
                        turnoIcon = '<span class="turno-label">☀️ Mañana</span>';
                    } else if (hour === 15) {
                        turnoIcon = '<span class="turno-label">🌤️ Tarde</span>';
                    } else if (hour === 23) {
                        turnoIcon = '<span class="turno-label">🌙 Noche</span>';
                    }

                    return {
                        html: `<div class="slot-label-wrapper">${timeText}${turnoIcon}</div>`
                    };
                },

                eventClick: async function(info) {
                    const codigos = info.event.extendedProps.codigos_elementos;
                    const elementosId = info.event.extendedProps.elementos_id;

                    if (!Array.isArray(codigos) || codigos.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sin elementos',
                            text: 'Este evento no tiene elementos asociados.',
                        });
                        return;
                    }

                    // Obtener datos de elementos via AJAX
                    try {
                        const response = await fetch(
                            `/elementos/por-codigos?codigos=${codigos.join(',')}`);
                        const elementos = await response.json();

                        // Construir HTML para el modal
                        const elementosHTML = elementos.map(el => `
            <div class="border-b py-2">
                <strong>${el.codigo}</strong> - ${el.descripcion}
                <br><small>Diámetro: ${el.diametro} | Peso: ${el.peso}kg</small>
            </div>
        `).join('');

                        // Mostrar modal con SweetAlert
                        Swal.fire({
                            title: `Elementos de ${info.event.title}`,
                            html: `<div class="text-left max-h-96 overflow-y-auto">${elementosHTML}</div>`,
                            width: '600px',
                            showCloseButton: true,
                            confirmButtonText: 'Ver en tabla',
                            showCancelButton: true,
                            cancelButtonText: 'Cerrar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Si confirma, redirigir al listado completo
                                window.location.href = `/elementos?codigo=${codigos.join(',')}`;
                            }
                        });

                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudieron cargar los elementos'
                        });
                    }
                },
                eventContent: function(arg) {
                    const progreso = arg.event.extendedProps.progreso;

                    if (typeof progreso === 'number') {
                        return {
                            html: `
                        <div class="w-full px-1 py-0.5 text-xs font-semibold text-white">
                            <div class="mb-0.5 truncate" title="${arg.event.title}">${arg.event.title}</div>
                            <div class="w-full h-2 bg-gray-300 rounded overflow-hidden">
                                <div class="h-2 bg-blue-500 rounded" style="width: ${progreso}%; min-width: 1px;"></div>
                            </div>
                        </div>`
                        };
                    }

                    return {
                        html: `
                    <div class="truncate w-full text-xs font-semibold text-white px-2 py-1 rounded"
                         style="background-color: ${arg.event.backgroundColor};"
                         title="${arg.event.title}">
                        ${arg.event.title}
                    </div>`
                    };
                },
                eventDrop: async function(info) {
                    const planillaId = info.event.id.split('-')[1];
                    const codigoPlanilla = info.event.extendedProps.codigo ?? info.event.title;
                    const maquinaOrigenId = info.oldResource?.id ?? info.event.getResources()[0]?.id;
                    const maquinaDestinoId = info.newResource?.id ?? info.event.getResources()[0]?.id;
                    const elementosId = info.event.extendedProps.elementos_id || [];

                    const resultado = await Swal.fire({
                        title: '¿Reordenar planilla?',
                        html: `¿Quieres mover la planilla <strong>${codigoPlanilla}</strong> ${maquinaOrigenId !== maquinaDestinoId ? 'a otra máquina' : 'en la misma máquina'}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, reordenar',
                        cancelButtonText: 'Cancelar'
                    });

                    if (!resultado.isConfirmed) {
                        info.revert();
                        return;
                    }

                    const eventosOrdenados = calendar.getEvents()
                        .filter(ev => ev.getResources().some(r => r.id == maquinaDestinoId))
                        .sort((a, b) => a.start - b.start);
                    const nuevaPosicion = eventosOrdenados.findIndex(ev => ev.id === info.event.id) + 1;
                    const mismaMaquina = String(maquinaOrigenId) === String(maquinaDestinoId);

                    const payload = {
                        id: planillaId,
                        maquina_id: maquinaDestinoId,
                        maquina_origen_id: maquinaOrigenId,
                        misma_maquina: mismaMaquina,
                        nueva_posicion: nuevaPosicion,
                        elementos_id: elementosId,
                    };

                    try {
                        const res = await fetch('/planillas/reordenar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();

                        if (!res.ok || !data.success) throw {
                            response: {
                                data
                            }
                        };

                        // 🔄 Actualizar solo los eventos afectados sin recargar
                        actualizarEventosSinRecargar(data.eventos);

                        // Mostrar mensaje de éxito breve
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Planilla reordenada'
                        });

                    } catch (e) {
                        const data = e.response?.data;

                        if (data?.requiresConfirmation) {
                            const diametros = data.diametros.join(', ');
                            const cantidad = data.diametros.length;

                            const confirmacion = await Swal.fire({
                                title: 'Elementos fuera de rango',
                                html: `Hay <strong>${cantidad}</strong> ${cantidad === 1 ? 'elemento' : 'elementos'} con diámetros incompatibles: <strong>${diametros}</strong>.<br><br>¿Quieres mover solo los elementos compatibles?`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, mover válidos',
                                cancelButtonText: 'Cancelar',
                            });

                            if (confirmacion.isConfirmed) {
                                try {
                                    const resForzado = await fetch('/planillas/reordenar', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]').content
                                        },
                                        body: JSON.stringify({
                                            ...payload,
                                            forzar_movimiento: true,
                                            elementos_id: data.elementos
                                        })
                                    });

                                    const dataForzado = await resForzado.json();

                                    if (!resForzado.ok || !dataForzado.success) throw dataForzado;

                                    // 🔄 Actualizar eventos sin recargar
                                    actualizarEventosSinRecargar(dataForzado.eventos);

                                    await Swal.fire({
                                        icon: 'success',
                                        title: 'Movimiento parcial realizado',
                                        text: 'Se movieron solo los elementos compatibles.',
                                        timer: 2000,
                                        showConfirmButton: false,
                                    });

                                } catch (errorFinal) {
                                    await mostrarError('Error al mover elementos', errorFinal.message ||
                                        'Error desconocido');
                                    info.revert();
                                }
                            } else {
                                await mostrarInfo('Movimiento cancelado',
                                    'No se realizó ningún cambio.');
                                info.revert();
                            }

                        } else {
                            await mostrarError('Error inesperado', data?.message ?? e.message);
                            info.revert();
                        }
                    }

                    // 🆕 Nueva función para actualizar eventos sin recargar la página
                    function actualizarEventosSinRecargar(eventosNuevos) {
                        // Limpiar tooltips
                        document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());

                        if (!eventosNuevos || !Array.isArray(eventosNuevos)) {
                            console.warn('No se recibieron eventos para actualizar');
                            return;
                        }

                        console.log(`🔄 Actualizando ${eventosNuevos.length} eventos sin recargar`);

                        // Identificar qué máquinas están siendo actualizadas (convertir a string para comparación)
                        const maquinasAfectadas = [...new Set(eventosNuevos.map(e => String(e
                            .resourceId)))];
                        console.log('🔧 Máquinas afectadas:', maquinasAfectadas);

                        // Eliminar SOLO los eventos de las máquinas afectadas
                        const eventosEliminados = [];
                        calendar.getEvents().forEach(evento => {
                            const recursos = evento.getResources();

                            // Verificar si algún recurso del evento pertenece a las máquinas afectadas
                            const perteneceAMaquinaAfectada = recursos.some(recurso =>
                                maquinasAfectadas.includes(String(recurso.id))
                            );

                            if (perteneceAMaquinaAfectada) {
                                eventosEliminados.push(evento.id);
                                evento.remove();
                            }
                        });

                        console.log(`🗑️ Eventos eliminados: ${eventosEliminados.length}`,
                            eventosEliminados);

                        // Agregar los eventos actualizados de las máquinas afectadas
                        eventosNuevos.forEach(eventoData => {
                            calendar.addEvent(eventoData);
                        });

                        console.log(`➕ Eventos agregados: ${eventosNuevos.length}`);
                        console.log('✅ Eventos actualizados correctamente');
                    }

                    async function mostrarError(titulo, mensaje) {
                        document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());
                        await Swal.fire({
                            icon: 'error',
                            title: titulo,
                            text: mensaje,
                        });
                    }

                    async function mostrarInfo(titulo, mensaje) {
                        document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());
                        await Swal.fire({
                            icon: 'info',
                            title: titulo,
                            text: mensaje,
                        });
                    }
                },
                eventDidMount: function(info) {
                    const props = info.event.extendedProps;
                    const tooltip = document.createElement('div');
                    tooltip.className = 'fc-tooltip';
                    tooltip.innerHTML = `
                <div class="bg-gray-900 text-white text-xs rounded px-2 py-1 shadow-md max-w-xs">
                    <strong>${info.event.title}</strong><br>
                    Obra: ${props.obra}<br>
                    Estado producción: ${props.estado}<br>
                    Fin programado: <span class="text-yellow-300">${props.fin_programado}</span><br>
                    Fecha estimada entrega: <span class="text-green-300">${props.fecha_entrega}</span>
                </div>`;
                    tooltip.style.position = 'absolute';
                    tooltip.style.zIndex = 9999;
                    tooltip.style.display = 'none';
                    document.body.appendChild(tooltip);

                    info.el.addEventListener('mouseenter', function(e) {
                        tooltip.style.left = e.pageX + 10 + 'px';
                        tooltip.style.top = e.pageY + 10 + 'px';
                        tooltip.style.display = 'block';
                    });
                    info.el.addEventListener('mousemove', function(e) {
                        tooltip.style.left = e.pageX + 10 + 'px';
                        tooltip.style.top = e.pageY + 10 + 'px';
                    });
                    info.el.addEventListener('mouseleave', function() {
                        tooltip.style.display = 'none';
                    });
                }
            });

            calendar.render();
            window.calendar = calendar;

            // ------- Charts (Planificado vs Real) -------
            const charts = {};
            const $desde = document.getElementById('fechaInicio');
            const $hasta = document.getElementById('fechaFin');
            const $turno = document.getElementById('turnoFiltro');
            const $rango = document.getElementById('rango-aplicado');

            const parseDate = (val) => {
                if (!val) return null;
                const d = new Date(val);
                return isNaN(d.getTime()) ? null : d;
            };

            // Crear charts iniciales
            Object.entries(cargaTurnoResumen).forEach(([maquinaId, turnos]) => {
                const canvas = document.getElementById(`grafico-maquina-${maquinaId}`);
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                const labels = ["Mañana", "Tarde", "Noche"];
                const planificado = labels.map(t => (turnos[t.toLowerCase()]?.planificado ?? 0));
                const real = labels.map(t => (turnos[t.toLowerCase()]?.real ?? 0));

                charts[maquinaId] = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                                label: 'Planificado (kg)',
                                data: planificado,
                                backgroundColor: 'rgba(255, 159, 64, 0.6)',
                                borderColor: 'rgba(255, 159, 64, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Real (kg)',
                                data: real,
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            title: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Kg'
                                }
                            }
                        }
                    }
                });
            });

            // Texto inicial del rango
            const pintarTextoRango = () => {
                const turnoTxt = $turno?.value ?
                    $turno.options[$turno.selectedIndex].text :
                    'Todos';
                $rango.textContent =
                    `Mostrando datos desde ${$desde.value} hasta ${$hasta.value} · Turno: ${turnoTxt}`;
            };
            pintarTextoRango();

            // Función única para aplicar filtro (por botón o por cambios)
            const aplicarFiltro = (ev) => {
                ev?.preventDefault?.();

                const desde = parseDate($desde.value);
                const hasta = parseDate($hasta.value);
                if (!desde || !hasta) {
                    $rango.textContent = 'Selecciona un rango de fechas válido.';
                    return;
                }
                // incluir día completo
                hasta.setHours(23, 59, 59, 999);

                const turnoSel = ($turno?.value || '').toLowerCase(); // '', 'mañana','tarde','noche'
                pintarTextoRango();

                Object.entries(planDetallado).forEach(([maquinaId, turnosPlan]) => {
                    const etiquetas = turnoSel ? [turnoSel] : ['mañana', 'tarde', 'noche'];

                    const planificado = etiquetas.map(t =>
                        (turnosPlan[t] || [])
                        .filter(e => {
                            const f = parseDate(e.fecha);
                            return f && f >= desde && f <= hasta;
                        })
                        .reduce((suma, e) => suma + (Number(e.peso) || 0), 0)
                    );

                    const turnosReal = realDetallado[maquinaId] || {};
                    const real = etiquetas.map(t =>
                        (turnosReal[t] || [])
                        .filter(e => {
                            const f = parseDate(e.fecha);
                            return f && f >= desde && f <= hasta;
                        })
                        .reduce((suma, e) => suma + (Number(e.peso) || 0), 0)
                    );

                    if (charts[maquinaId]) {
                        charts[maquinaId].data.labels = etiquetas.map(s => s.charAt(0).toUpperCase() + s
                            .slice(1));
                        charts[maquinaId].data.datasets[0].data = planificado;
                        charts[maquinaId].data.datasets[1].data = real;
                        charts[maquinaId].update();
                    }
                });
            };

            // Click en botón
            document.getElementById('filtrarFechas').addEventListener('click', aplicarFiltro);

            // Reactividad instantánea (sin pulsar botón)
            ['change', 'input'].forEach(evt => {
                $desde.addEventListener(evt, aplicarFiltro);
                $hasta.addEventListener(evt, aplicarFiltro);
                $turno?.addEventListener(evt, aplicarFiltro);
            });
        }); // <-- cierre correcto de DOMContentLoaded
    </script>
    <style>
        canvas {
            height: 180px !important;
            max-height: 180px !important;
        }

        /* 🎨 ESTILOS PARA TURNOS */

        /* Turno Mañana (6-14h) - Color amarillo suave */
        .turno-manana {
            background-color: rgba(255, 243, 205, 0.4) !important;
        }

        /* Turno Tarde (14-22h) - Color naranja suave */
        .turno-tarde {
            background-color: rgba(255, 224, 178, 0.4) !important;
        }

        /* Turno Noche (22-6h) - Color azul oscuro suave */
        .turno-noche {
            background-color: rgba(197, 202, 233, 0.4) !important;
        }

        /* Líneas divisorias entre turnos */
        .fc-timegrid-slot[data-time="06:00:00"],
        .fc-timegrid-slot[data-time="14:00:00"],
        .fc-timegrid-slot[data-time="22:00:00"] {
            border-top: 3px solid #e74c3c !important;
        }

        /* Etiquetas de turno */
        .turno-label {
            display: block;
            font-size: 9px;
            font-weight: bold;
            color: #2c3e50;

            <script>
                document.querySelectorAll('.estado-maquina').forEach(select => {
                    select.addEventListener('change', async function() {
                        const maquinaId = this.dataset.id;
                        const nuevoEstado = this.value;

                        try {
                            const res = await fetch(`/maquinas/${maquinaId}/cambiar-estado`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .content
                                },
                                body: JSON.stringify({
                                    estado: nuevoEstado
                                })
                            });

                            const text = await res.text();

                            if (!res.ok) throw new Error(text);

                            const data = JSON.parse(text);

                            if (data.success) {
                                console.log(`✅ Máquina ${maquinaId} actualizada a estado "${nuevoEstado}"`);

                                // 🔁 Actualizar el título del resource en FullCalendar
                                const calendar = window.calendar; // asegúrate que tu instancia esté global

                                const estado = data.estado;
                                const colores = {
                                    activa: '🟢',
                                    averiada: '🔴',
                                    mantenimiento: '🛠️',
                                    pausa: '⏸️'
                                };
                                const icono = colores[estado] ?? ' ';
                                const nombreMaquina = select.dataset.nombre ?? `Máquina ${maquinaId}`;
                                const nuevoTitulo = `${icono} ${nombreMaquina}`;

                                calendar.getResourceById(maquinaId)?.setProp('title', nuevoTitulo);
                            }

                        } catch (e) {
                            console.error('Error en respuesta:', e);
                            Swal.fire({
                                title: 'Error',
                                html: `<pre style="white-space:pre-wrap;text-align:left;">${e.message}</pre>`,
                                icon: 'error'
                            });
                        }
                    });
                });
            </script><style>.fc-tooltip {
                pointer-events: none;
                transition: opacity 0.1s ease-in-out;
            }

            .fc-event {
                min-width: 50px !important;
            }
    </style>
</x-app-layout>
