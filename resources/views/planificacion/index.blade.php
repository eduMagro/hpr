<x-app-layout>
    <x-slot name="title">Planificación - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Planificación de Salidas') }}
        </h2>
    </x-slot>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <!-- Contenedor de las dos columnas -->
    <div class="w-full">
        <!-- Acciones visibles en escritorio -->
        <div class="hidden sm:flex sm:mt-0 w-full">

            <a href="{{ route('salidas.create') }}"
                class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-none transition">
                Crear Nueva Salida
            </a>

        </div>
        <!-- Botonera responsive para planificación (solo en móvil) -->
        <div class="sm:hidden relative" x-data="{ open: false }">
            <!-- Botón que abre el menú -->
            <button @click="open = !open"
                class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                Opciones
            </button>

            <!-- Menú desplegable estilizado -->
            <div x-show="open" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95" @click.away="open = false"
                class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                x-cloak>


                <a href="{{ route('salidas.create') }}"
                    class="block px-4 py-3 text-blue-700 hover:bg-blue-50 hover:text-blue-900 transition text-sm font-medium">
                    ➕ Crear Nueva Salida
                </a>
            </div>
        </div>

        <!-- Acciones visibles en escritorio -->

        <!-- 📅 Calendario (Derecha) -->



        <div class="w-full bg-white">
            <div class="mb-6 flex flex-col md:flex-row gap-4 justify-center">
                <!-- Resumen Semanal -->
                <div class="max-w-sm bg-blue-50 border border-blue-200 rounded-md p-3 shadow-sm text-sm">
                    <h3 class="text-base font-semibold text-blue-700 mb-1 text-center">Resumen semanal</h3>
                    <div class="flex items-center justify-center gap-3">
                        <p id="resumen-semanal-peso">📦 0 kg</p>
                        <p id="resumen-semanal-longitud">📏 0 m</p>
                        <p id="resumen-semanal-diametro"></p>
                    </div>
                </div>

                <!-- Resumen Mensual -->
                <div class="max-w-sm bg-blue-50 border border-blue-200 rounded-md p-3 shadow-sm text-sm">
                    <h3 class="text-base font-semibold text-blue-700 mb-1 text-center">Resumen mensual</h3>
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
        let calendar;

        document.addEventListener('DOMContentLoaded', function() {
            console.log("🔥 DOM listo, iniciando calendario...");
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
                            tipo: 'resources'
                        };
                    }
                },
                events: {
                    url: '{{ url('/planificacion') }}',
                    method: 'GET',
                    extraParams: function() {
                        return {
                            tipo: 'events'
                        };
                    }
                },

                datesSet: function(info) {
                    let fechaActual;

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
                        fechaActual = info.startStr;
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
                    let html =
                        `<div style="background-color:${bg}; color:white;" class="rounded px-2 py-1 text-xs font-semibold">${arg.event.title}`;
                    if (props.tipo === 'planilla') {
                        html +=
                            `<br><span class="text-[10px] font-normal">🧱 ${Number(props.pesoTotal).toLocaleString()} kg</span>`;
                        html +=
                            `<br><span class="text-[10px] font-normal">📏 ${Number(props.longitudTotal).toLocaleString()} m</span>`;
                        if (props.diametroMedio !== null) {
                            html +=
                                `<br><span class="text-[10px] font-normal">⌀ ${props.diametroMedio} mm</span>`;
                        }
                    }
                    html += `</div>`;
                    return {
                        html
                    };
                },
                eventDidMount: function(info) {
                    const props = info.event.extendedProps;
                    if (props.tipo === 'planilla') {
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
                    }
                    if (props.tipo === 'salida') {
                        let contenidoTooltip = '';
                        if (props.empresa) contenidoTooltip += `🚛 ${props.empresa}<br>`;
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
                    }
                },
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
                            console.log("✅ Fecha actualizada");
                            calendar.refetchEvents();
                            calendar.refetchResources();
                        })
                        .catch(err => {
                            console.error("❌ Error:", err);
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
            console.log("🚀 Cargando obras con salida automáticamente");
            calendar.refetchResources();
            calendar.refetchEvents();
        }

        function actualizarTotales(fecha) {
            console.log("🔄 Actualizando totales para la fecha:", fecha);

            fetch(`/planificacion/totales?fecha=${fecha}`)
                .then(res => res.json())
                .then(data => {
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

                    console.log("✅ Totales actualizados correctamente.");
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
    </style>
</x-app-layout>
