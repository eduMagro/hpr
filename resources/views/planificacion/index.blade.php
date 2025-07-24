<x-app-layout>
    <x-slot name="title">Planificación - {{ config('app.name') }}</x-slot>

    <div class="w-full">
        <x-menu.salidas />

        <div class="w-full bg-white pt-4">
            <div class="mb-6 flex flex-col md:flex-row gap-4 justify-center">
                <!-- Resumen Semanal -->
                <div class="max-w-sm bg-blue-50 border border-blue-200 rounded-md p-3 shadow-sm text-sm">
                    <h3 class="text-base font-semibold text-blue-700 mb-1 text-center">
                        Resumen semanal <span id="resumen-semanal-fecha" class="text-gray-600 text-sm"></span>
                    </h3>

                    <div class="flex items-center justify-center gap-3">
                        <p id="resumen-semanal-peso">📦 0 kg</p>
                        <p id="resumen-semanal-longitud">📏 0 m</p>
                        <p id="resumen-semanal-diametro"></p>
                    </div>
                </div>

                <!-- Resumen Mensual -->
                <div class="max-w-sm bg-blue-50 border border-blue-200 rounded-md p-3 shadow-sm text-sm">
                    <h3 class="text-base font-semibold text-blue-700 mb-1 text-center">
                        Resumen mensual <span id="resumen-mensual-fecha" class="text-gray-600 text-sm"></span>
                    </h3>

                    <div class="flex items-center justify-center gap-3">
                        <p id="resumen-mensual-peso">📦 0 kg</p>
                        <p id="resumen-mensual-longitud">📏 0 m</p>
                        <p id="resumen-mensual-diametro"></p>
                    </div>
                </div>
            </div>

            <div id="calendario" class="w-full h-screen"></div>
        </div>
    </div>

    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>

    {{-- TOOLTIP --}}
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <script>
        // Hacemos disponible la lista de camiones al JS
        window.camiones = @json($camiones);
    </script>

    <script>
        let calendar;

        document.addEventListener('DOMContentLoaded', function() {
            crearCalendario();

            // 👉 Llamamos a la misma función que el botón para que cargue como si hubieras hecho click
            cargarObrasConSalidas();

            // Dejas el listener por si quieres pulsarlo manualmente después
            document.getElementById('ver-con-salidas').addEventListener('click', cargarObrasConSalidas);

            // También puedes dejar el de "ver todas" si quieres recargar todo
            const btnTodas = document.getElementById('ver-todas');
            if (btnTodas) {
                btnTodas.addEventListener('click', () => {
                    calendar.refetchResources();
                    calendar.refetchEvents();
                });
            }
        });
        let currentViewType = 'resourceTimelineDay'; // valor por defecto
        function crearCalendario() {
            if (calendar) {
                calendar.destroy();
            }
            const vistasValidas = ['resourceTimelineDay', 'resourceTimelineWeek', 'dayGridMonth'];

            let vistaGuardada = localStorage.getItem('ultimaVistaCalendario');
            if (!vistasValidas.includes(vistaGuardada)) {
                vistaGuardada = 'resourceTimelineDay';
            }

            const fechaGuardada = localStorage.getItem('fechaCalendario');

            calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                locale: 'es',
                initialView: vistaGuardada,
                initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,
                slotMinTime: "06:00:00",
                slotMaxTime: "18:00:00",
                extraParams: function() {
                    return {
                        tipo: 'events',
                        viewType: calendar.view.type // 👈 pasamos el tipo de vista
                    };
                },
                resources: {
                    url: '{{ url('/planificacion') }}',
                    method: 'GET',
                    extraParams: function() {
                        return {
                            tipo: 'resources',
                            viewType: currentViewType // 👈 usamos la variable
                        };
                    }
                },
                events: {
                    url: '{{ url('/planificacion') }}',
                    method: 'GET',
                    extraParams: function() {
                        return {
                            tipo: 'events',
                            viewType: currentViewType // 👈 usamos la variable
                        };
                    }
                },
                datesSet: function(info) {
                    let fechaActual;
                    currentViewType = calendar.view.type;
                    if (calendar.view.type === 'dayGridMonth') {
                        // Para la vista mensual, calculamos una fecha del medio del mes
                        const middleDate = new Date(info.start);
                        middleDate.setDate(middleDate.getDate() + 15);
                        fechaActual = middleDate.toISOString().split('T')[0];
                    } else if (calendar.view.type === 'resourceTimelineWeek') {
                        // Para la vista semanal, calculamos una fecha del medio de la semana
                        const middleDate = new Date(info.start);
                        const daysToAdd = Math.floor((info.end - info.start) / (1000 * 60 * 60 * 24) / 2);
                        middleDate.setDate(middleDate.getDate() + daysToAdd);
                        fechaActual = middleDate.toISOString().split('T')[0];
                    } else {
                        // Para vista diaria usamos directamente la fecha
                        fechaActual = info.startStr.split('T')[0];
                    }

                    // Guardar vista y fecha en localStorage
                    localStorage.setItem('fechaCalendario', fechaActual);
                    localStorage.setItem('ultimaVistaCalendario', calendar.view.type);

                    // Refrescar events y resources
                    calendar.refetchResources();
                    calendar.refetchEvents();

                    // ✅ Llamar siempre a actualizarTotales
                    actualizarTotales(fechaActual);
                },
                eventMinHeight: 30,
                firstDay: 1,
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'resourceTimelineDay,resourceTimelineWeek,dayGridMonth'
                },
                buttonText: {
                    today: 'Hoy',
                    day: 'Día',
                    week: 'Semana',
                    month: 'Mes'
                },
                views: {
                    resourceTimelineWeek: {
                        slotDuration: {
                            days: 1
                        },
                        slotLabelFormat: {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'short'
                        }
                    },
                },
                editable: true,
                resourceAreaColumns: [{
                        field: 'cod_obra',
                        headerContent: 'Código'
                    },
                    {
                        field: 'title',
                        headerContent: 'Obra'
                    },
                    {
                        field: 'cliente',
                        headerContent: 'Cliente'
                    }
                ],
                resourceOrder: 'orderIndex',
                eventClick: function(info) {
                    const tipo = info.event.extendedProps.tipo;
                    if (tipo === 'planilla') {
                        const planillasIds = info.event.extendedProps.planillas_ids;
                        window.location.href =
                            `{{ url('/salidas/create') }}?planillas=${planillasIds.join(',')}`;
                    }
                    if (tipo === 'salida') {
                        window.open(`{{ url('/salidas') }}/${info.event.id}`, '_blank');
                    }
                },
                eventContent: function(arg) {
                    const bg = arg.event.backgroundColor || '#9CA3AF';
                    const props = arg.event.extendedProps;

                    let html = `
      <div style="background-color:${bg}; color:white;" class="rounded px-2 py-1 text-xs font-semibold">
        ${arg.event.title}
    `;

                    // 👉 datos de la planilla
                    if (props.tipo === 'planilla') {
                        html +=
                            `<br><span class="text-[10px] font-normal">🧱 ${Number(props.pesoTotal).toLocaleString()} kg</span>`;
                        html +=
                            `<br><span class="text-[10px] font-normal">📏 ${Number(props.longitudTotal).toLocaleString()} m</span>`;
                        if (props.diametroMedio !== null) {
                            html +=
                                `<br><span class="text-[10px] font-normal">⌀ ${props.diametroMedio} mm</span>`;
                        }

                        // 👉 si tiene salidas asociadas, las listamos
                        if (props.tieneSalidas && Array.isArray(props.salidas_codigos) && props.salidas_codigos
                            .length > 0) {
                            html += `<div class="mt-1 text-[10px] font-normal bg-yellow-400 text-black rounded px-1 py-0.5 inline-block">
                        🔗 Salidas: ${props.salidas_codigos.join(', ')}
                     </div>`;
                        }
                    }

                    html += `</div>`;
                    return {
                        html
                    };
                },

                eventDidMount: function(info) { // 🔹 abre 1 (A)
                    const props = info.event.extendedProps;

                    // 👉 Tooltip planilla
                    if (props.tipo === 'planilla') { // 🔹 abre 2 (B)
                        let contenidoTooltip = `
                        ✅ Fabricados: ${Number(props.fabricadosKg).toLocaleString()} kg<br>
                        🔄 Fabricando: ${Number(props.fabricandoKg).toLocaleString()} kg<br>
                        ⏳ Pendientes: ${Number(props.pendientesKg).toLocaleString()} kg
                    `;
                        tippy(info.el, {
                            content: contenidoTooltip,
                            allowHTML: true,
                            theme: 'light-border',
                            placement: 'top',
                            animation: 'shift-away',
                            arrow: true,
                        });

                        // 👉 Clic derecho para opciones
                        info.el.addEventListener('contextmenu', function(e) { // 🔹 abre 3 (C)
                            e.preventDefault();

                            const planillasIds = info.event.extendedProps.planillas_ids || [];

                            Swal.fire({ // 🔹 abre 4 (D)
                                title: '📋 Opciones de planilla',
                                html: `
                    <button id="crear-salida-btn"
                        class="swal2-confirm swal2-styled"
                        style="background:#3b82f6; margin:5px;">
                        🚛 Crear salida
                    </button>
                `,
                                showConfirmButton: false,
                                showCancelButton: true,
                                cancelButtonText: 'Cerrar',
                                didOpen: () => { // 🔹 abre 5 (E)
                                    const btnCrearSalida = document.getElementById(
                                        'crear-salida-btn');
                                    if (btnCrearSalida) { // 🔹 abre 6 (F)
                                        btnCrearSalida.addEventListener('click', () => {
                                            Swal.close();

                                            // Generamos dinámicamente las opciones de camiones
                                            let optionsHtml = camiones.map(
                                                camion => {
                                                    let empresa = camion
                                                        .empresa_transporte
                                                        ?.nombre ??
                                                        'Sin empresa';
                                                    return `<option value="${camion.id}">${camion.modelo} - ${empresa}</option>`;
                                                }).join('');

                                            // ejemplo de creación de un <select> dinámico para el Swal
                                            let opcionesCamiones = window.camiones
                                                .map(c => {
                                                    let empresa = c
                                                        .empresa_transporte ? c
                                                        .empresa_transporte
                                                        .nombre : 'Sin empresa';
                                                    return `<option value="${c.id}">${c.modelo} (${empresa})</option>`;
                                                }).join('');

                                            Swal.fire({
                                                title: 'Selecciona un camión',
                                                html: `
        <select id="swalCamion" class="swal2-select" style="width:100%;padding:5px;">
            ${opcionesCamiones}
        </select>
    `,
                                                preConfirm: () => {
                                                    const seleccionado =
                                                        document
                                                        .getElementById(
                                                            'swalCamion'
                                                        ).value;
                                                    return seleccionado;
                                                },
                                                showCancelButton: true,
                                                confirmButtonText: 'Confirmar'
                                            }).then(result => {
                                                if (result.isConfirmed) {
                                                    const camionId = result
                                                        .value;

                                                    // Llamada AJAX
                                                    fetch('/planificacion/crear-salida-desde-calendario', {
                                                            method: 'POST',
                                                            headers: {
                                                                'Content-Type': 'application/json',
                                                                'X-CSRF-TOKEN': document
                                                                    .querySelector(
                                                                        'meta[name="csrf-token"]'
                                                                    )
                                                                    .content
                                                            },
                                                            body: JSON
                                                                .stringify({
                                                                    planillas_ids: planillasIds,
                                                                    camion_id: camionId
                                                                })
                                                        })
                                                        .then(res => res
                                                            .json())
                                                        .then(data => {
                                                            if (data
                                                                .success
                                                            ) {
                                                                Swal.fire(
                                                                    '✅',
                                                                    data
                                                                    .message,
                                                                    'success'
                                                                );
                                                                calendar
                                                                    .refetchEvents();
                                                                calendar
                                                                    .refetchResources();
                                                            } else {
                                                                Swal.fire(
                                                                    '⚠️',
                                                                    data
                                                                    .message,
                                                                    'warning'
                                                                );
                                                            }
                                                        })
                                                        .catch(err => {
                                                            console
                                                                .error(
                                                                    err
                                                                );
                                                            Swal.fire(
                                                                '❌',
                                                                'Hubo un problema al crear la salida.',
                                                                'error'
                                                            );
                                                        });
                                                }
                                            });
                                        });

                                    } // 🔹 cierra 6 (F)
                                } // 🔹 cierra 5 (E)
                            }); // 🔹 cierra 4 (D)
                        }); // 🔹 cierra 3 (C)
                    } // 🔹 cierra 2 (B)

                    // 👉 Tooltip y clic derecho para salidas
                    if (props.tipo === 'salida') { // 🔹 abre 14 (N)
                        let contenidoTooltip = '';
                        const camion = props.camion ? ` (${props.camion})` : '';
                        contenidoTooltip += `🚛 ${props.empresa}${camion}<br>`;
                        if (props.comentario) contenidoTooltip += `📝 ${props.comentario}`;
                        if (contenidoTooltip) {
                            tippy(info.el, {
                                content: contenidoTooltip,
                                allowHTML: true,
                                theme: 'light-border',
                                placement: 'top',
                                animation: 'shift-away',
                                arrow: true,
                            });
                        }

                        info.el.addEventListener('contextmenu', function(e) { // 🔹 abre 15 (O)
                            e.preventDefault();
                            Swal.fire({
                                title: '✏️ Agregar comentario',
                                input: 'textarea',
                                inputLabel: 'Escribe el comentario',
                                inputValue: props.comentario || '',
                                inputPlaceholder: 'Escribe aquí…',
                                showCancelButton: true,
                                confirmButtonText: '💾 Guardar',
                                cancelButtonText: 'Cancelar',
                                inputAttributes: {
                                    maxlength: 1000,
                                    style: 'min-height:100px'
                                }
                            }).then((result) => { // 🔹 abre 16 (P)
                                if (result.isConfirmed) { // 🔹 abre 17 (Q)
                                    fetch(`/planificacion/comentario/${info.event.id}`, {
                                            method: 'PUT',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector(
                                                    'meta[name="csrf-token"]').content
                                            },
                                            body: JSON.stringify({
                                                comentario: result.value
                                            })
                                        })
                                        .then(res => res.json())
                                        .then(data => { // 🔹 abre 18 (R)
                                            if (data.success) { // 🔹 abre 19 (S)

                                                calendar.refetchEvents();
                                            }
                                        }) // 🔹 cierra 18 (R)
                                        .catch(err => { // 🔹 abre 20 (T)
                                            console.error(err);
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: 'Ocurrió un error al guardar'
                                            });
                                        }); // 🔹 cierra 20 (T)
                                } // 🔹 cierra 17 (Q)
                            }); // 🔹 cierra 16 (P)
                        }); // 🔹 cierra 15 (O)
                    } // 🔹 cierra 14 (N)
                }, // 🔹 cierra 1 (A)

                eventDrop: function(info) {
                    const tipo = info.event.extendedProps.tipo;
                    const nuevaFecha = info.event.start.toISOString();
                    const id = info.event.id;
                    const planillasIds = info.event.extendedProps.planillas_ids;

                    fetch(`{{ url('/planificacion') }}/${id}`, {
                            method: 'PUT',
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute("content")
                            },
                            body: JSON.stringify({
                                fecha: nuevaFecha,
                                tipo: tipo,
                                planillas_ids: planillasIds
                            })
                        })
                        .then(res => {
                            if (!res.ok) throw new Error("No se pudo actualizar la fecha.");
                            return res.json();
                        })
                        .then(() => {
                            calendar.refetchEvents();
                            calendar.refetchResources();
                        })
                        .catch(err => {
                            console.error("Error:", err);
                            info.revert();
                        });
                },
                dateClick: function(info) {
                    const vistaActual = calendar.view.type;
                    if (vistaActual === 'resourceTimelineWeek' || vistaActual === 'dayGridMonth') {
                        calendar.changeView('resourceTimelineDay', info.dateStr);
                    }
                },
            });

            calendar.render();
        }

        // 👉 Función para recargar solo obras con salida
        function cargarObrasConSalidas() {
            calendar.refetchResources();
            calendar.refetchEvents();
        }

        function actualizarTotales(fecha) {
            // 👉 Convertir fecha a objeto Date
            const dateObj = new Date(fecha);
            // 👉 Obtener nombre de mes y año en español
            const opciones = {
                year: 'numeric',
                month: 'long'
            };
            let mesTexto = dateObj.toLocaleDateString('es-ES', opciones);
            // Capitalizar primera letra
            mesTexto = mesTexto.charAt(0).toUpperCase() + mesTexto.slice(1);

            // ✨ Mostrar el mes en texto con primera mayúscula
            document.querySelector('#resumen-mensual-fecha').textContent = `(${mesTexto})`;

            fetch(`/planificacion/totales?fecha=${fecha}`)
                .then(res => res.json())
                .then(data => {
                    // ✅ Resumen semanal
                    document.querySelector('#resumen-semanal-peso').textContent =
                        `📦 ${Number(data.semana.peso).toLocaleString()} kg`;
                    document.querySelector('#resumen-semanal-longitud').textContent =
                        `📏 ${Number(data.semana.longitud).toLocaleString()} m`;
                    // ✅ Resumen semanal
                    document.querySelector('#resumen-semanal-peso').textContent =
                        `📦 ${Number(data.semana.peso).toLocaleString()} kg`;
                    document.querySelector('#resumen-semanal-longitud').textContent =
                        `📏 ${Number(data.semana.longitud).toLocaleString()} m`;

                    const diametroSemanalEl = document.querySelector('#resumen-semanal-diametro');
                    diametroSemanalEl.textContent = ''; // limpiar primero
                    if (data.semana.diametro !== null && !isNaN(data.semana.diametro)) {
                        diametroSemanalEl.textContent =
                            `⌀ ${Number(data.semana.diametro).toFixed(2)} mm`;
                    }

                    // ✅ Resumen mensual
                    document.querySelector('#resumen-mensual-peso').textContent =
                        `📦 ${Number(data.mes.peso).toLocaleString()} kg`;
                    document.querySelector('#resumen-mensual-longitud').textContent =
                        `📏 ${Number(data.mes.longitud).toLocaleString()} m`;

                    const diametroMensualEl = document.querySelector('#resumen-mensual-diametro');
                    diametroMensualEl.textContent = ''; // limpiar primero
                    if (data.mes.diametro !== null && !isNaN(data.mes.diametro)) {
                        diametroMensualEl.textContent =
                            `⌀ ${Number(data.mes.diametro).toFixed(2)} mm`;
                    }

                })
                .catch(err => {
                    console.error("❌ Error al actualizar los totales:", err);
                });
        }
    </script>
    <style>
        /* ejemplo: la tercera columna (index empieza en 1) */
        .fc .fc-datagrid-cell:nth-child(1) {
            width: 70px;
            /* tu ancho deseado */
            max-width: 150px;
            white-space: nowrap;
            /* para que no haga salto de línea */
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fc-toolbar-title {
            background: linear-gradient(90deg, #60a5fa, #3b82f6);
            /* degradado azul */
            color: white;
            padding: 0.3em 0.8em;
            border-radius: 0.5em;
            text-transform: capitalize;
            font-size: 1.8rem;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            display: inline-block;
            /* para que el fondo se ajuste */
        }
    </style>
</x-app-layout>
