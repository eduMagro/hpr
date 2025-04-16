<x-app-layout>
    <x-slot name="title">Planificación - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Planificación de Salidas') }}
        </h2>
    </x-slot>

    <!-- Contenedor de las dos columnas -->
    <div class="py-6">
        <div class="mb-4 pl-4 flex gap-2">
            <button id="ver-todas" class="bg-gray-700 text-white px-3 py-1 rounded">Ver todas las obras</button>
            <button id="ver-con-salidas" class="bg-blue-600 text-white px-3 py-1 rounded">Obras con salida
                asociada</button>

            <a href="{{ route('salidas.create') }}"
                class="bg-green-600 text-white py-2 px-4 rounded-lg shadow-lg hover:bg-green-700 transition duration-300">
                Crear Nueva Salida
            </a>
            <button id="toggle-fullscreen" class="bg-black text-white px-3 py-1 rounded">
                Pantalla completa
            </button>
        </div>

        <!-- 📅 Calendario (Derecha) -->
        <div class="w-full bg-white">
            <div id="calendario" class="w-full h-screen"></div>

        </div>
    </div>


    <!-- FullCalendar -->
    <!-- FullCalendar Scheduler con marca de agua -->
    <!-- ✅ FullCalendar Scheduler completo con vista resourceTimelineWeek -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    {{-- TOOLTIP --}}
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

    <style>
        .fullscreen-calendario body,
        .fullscreen-calendario html {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        .fullscreen-calendario #calendario {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 40;
            height: 100vh !important;
            background-color: white;
        }

        .fullscreen-calendario #toggle-fullscreen {
            display: block !important;
        }

        /* Ocultamos encabezados y filtros, pero NO la toolbar de FullCalendar */
        .fullscreen-calendario header,
        .fullscreen-calendario .mb-4:not(.fc-toolbar) {
            display: none !important;
        }
    </style>

    <script>
        document.getElementById('toggle-fullscreen').addEventListener('click', () => {
            document.body.classList.toggle('fullscreen-calendario');
            calendar.updateSize();

            const btn = document.getElementById('toggle-fullscreen');
            if (document.body.classList.contains('fullscreen-calendario')) {
                btn.textContent = 'Salir pantalla completa';
            } else {
                btn.textContent = 'Pantalla completa';
            }
        });
    </script>
    <script>
        let calendar; // hacemos la variable accesible desde fuera

        const todasLasObras = @json($todasLasObras);
        const obrasConSalidas = @json($obrasConSalidasResources);

        const eventos = @json($eventos);

        function crearCalendario(resources) {
            if (calendar) {
                calendar.destroy(); // 🔥 destruye el anterior antes de renderizar el nuevo
            }
            const vistaGuardada = localStorage.getItem('ultimaVistaCalendario') || 'resourceTimelineDay';
            calendar = new FullCalendar.Calendar(document.getElementById('calendario'), {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                locale: 'es',
                initialView: vistaGuardada,
                viewDidMount: function(view) {
                    localStorage.setItem('ultimaVistaCalendario', view.view.type);
                },
                eventMinHeight: 30,
                slotMinTime: "06:00:00",
                slotMaxTime: "20:00:00",
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
                    }
                },
                editable: true,
                resources: resources,
                resourceAreaColumns: [{
                        field: 'title',
                        headerContent: 'Obra'
                    },
                    {
                        field: 'cliente',
                        headerContent: 'Cliente'
                    }
                ],
                events: eventos,
                eventClick: function(info) {
                    const tipo = info.event.extendedProps.tipo;
                    const id = info.event.id;

                    if (tipo === 'planilla') {
                        const id = info.event.id;
                        const url = `{{ url('/planillas') }}/${id}`;
                        window.open(url,
                            '_blank'); // o cambia a window.location.href = url para abrir en misma pestaña
                    }
                    if (tipo === 'salida') {
                        const url = `{{ url('/salidas') }}/${id}`;
                        window.open(url,
                            '_blank');
                    }
                },
                eventContent: function(arg) {
                    const bg = arg.event.backgroundColor || '#9CA3AF';
                    return {
                        html: `
                    <div style="background-color:${bg}; color:white;" class="rounded px-2 py-1 text-xs font-semibold">
                        ${arg.event.title}
                    </div>
                `
                    };
                },
                eventDidMount: function(info) {
                    const props = info.event.extendedProps;

                    if (props.tipo === 'salida') {
                        // 🔹 Tooltip con empresa + comentario
                        let contenidoTooltip = '';

                        if (props.empresa) {
                            contenidoTooltip += `🚛 ${props.empresa}<br>`;
                        }

                        if (props.comentario) {
                            contenidoTooltip += `📝 ${props.comentario}`;
                        }

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

                        // 🖱 Clic derecho para editar comentario
                        info.el.addEventListener('contextmenu', function(e) {
                            e.preventDefault();

                            Swal.fire({
                                title: 'Añadir comentario',
                                input: 'textarea',
                                inputLabel: 'Comentario para la salida',
                                inputPlaceholder: 'Escribe tu comentario aquí...',
                                inputValue: props.comentario || '',
                                showCancelButton: true,
                                confirmButtonText: 'Guardar'
                            }).then(result => {
                                if (result.isConfirmed) {
                                    const comentario = result.value;

                                    fetch(`{{ url('/planificacion/comentario') }}/${info.event.id}`, {
                                            method: 'PUT',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector(
                                                    'meta[name="csrf-token"]').content
                                            },
                                            body: JSON.stringify({
                                                comentario
                                            })
                                        })
                                        .then(res => {
                                            if (!res.ok) throw new Error(
                                                "No se pudo guardar el comentario");
                                            return res.json();
                                        })
                                        .then(() => {
                                            Swal.fire('✅ Comentario guardado');
                                            location
                                        .reload(); // 👈 Recarga para actualizar el tooltip
                                        })
                                        .catch(err => {
                                            Swal.fire('❌ Error', err.message, 'error');
                                        });
                                }
                            });
                        });
                    }
                },
                eventDrop: function(info) {
                    const tipo = info.event.extendedProps.tipo;
                    const nuevaFecha = info.event.start.toISOString();
                    const id = info.event.id;

                    fetch(`{{ url('/planificacion') }}/${id}`, {
                            method: 'PUT',
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute("content")
                            },
                            body: JSON.stringify({
                                fecha: nuevaFecha,
                                tipo: tipo
                            })
                        })
                        .then(response => {
                            if (!response.ok) throw new Error("No se pudo actualizar la fecha.");
                            return response.json();
                        })
                        .then(() => {
                            console.log("✅ Fecha actualizada");
                        })
                        .catch(error => {
                            console.error("❌ Error en Fetch:", error);
                            info.revert();
                        });
                }
            });
            calendar.render();
        }

        document.addEventListener('DOMContentLoaded', function() {
            crearCalendario(obrasConSalidas); // 👈 inicia con obras que tienen salidas

            document.getElementById('ver-todas').addEventListener('click', () => {
                crearCalendario(
                    todasLasObras); // 👈 al hacer clic, recarga todo con todas las obras activas
            });

            document.getElementById('ver-con-salidas').addEventListener('click', () => {
                crearCalendario(obrasConSalidas); // 👈 vuelve a mostrar solo las que tienen salidas
            });

        });
    </script>

</x-app-layout>
