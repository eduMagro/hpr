<x-app-layout>
    <x-slot name="title">Detalles de {{ $user->name }}</x-slot>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @php
        $rutaActual = request()->route()->getName();
    @endphp
    @if (Auth::check() && Auth::user()->rol == 'oficina')
        <div class="w-full" x-data="{ open: false }">
            <!-- Menú móvil -->
            <div class="sm:hidden relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                    Opciones
                </button>

                <div x-show="open" x-transition @click.away="open = false"
                    class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                    x-cloak>

                    <a href="{{ route('users.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('users.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📋 Usuarios
                    </a>

                    <a href="{{ route('register') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('register') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📋 Registrar Usuario
                    </a>

                    <a href="{{ route('vacaciones.index') }}"
                        class="relative block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('vacaciones.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🌴 Vacaciones
                        @isset($totalSolicitudesPendientes)
                            @if ($totalSolicitudesPendientes > 0)
                                <span
                                    class="absolute top-2 right-4 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                    {{ $totalSolicitudesPendientes }}
                                </span>
                            @endif
                        @endisset
                    </a>

                    <a href="{{ route('asignaciones-turnos.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        ⏱️ Registros
                    </a>
                </div>
            </div>

            <!-- Menú escritorio -->
            <div class="hidden sm:flex sm:mt-0 w-full">
                <a href="{{ route('users.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ request()->routeIs('users.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📋 Usuarios
                </a>

                <a href="{{ route('register') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ request()->routeIs('register') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📋 Registrar Usuario
                </a>

                <a href="{{ route('vacaciones.index') }}"
                    class="relative flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ request()->routeIs('vacaciones.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🌴 Vacaciones
                    @isset($totalSolicitudesPendientes)
                        @if ($totalSolicitudesPendientes > 0)
                            <span
                                class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow">
                                {{ $totalSolicitudesPendientes }}
                            </span>
                        @endif
                    @endisset
                </a>

                <a href="{{ route('asignaciones-turnos.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    ⏱️ Registros Entrada y Salida
                </a>
            </div>
        </div>

    @endif

    <div class="container mx-auto px-4 py-6">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-3xl mx-auto mb-6 border border-gray-200">
            <!-- Encabezado con avatar -->
            <div class="flex items-center space-x-4 border-b pb-4 mb-4">
                <div
                    class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center text-2xl font-bold text-gray-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-xl font-semibold">{{ $user->nombre_completo }}</h3>
                    <p class="text-gray-500 text-sm">{{ $user->rol }}</p>
                </div>
            </div>

            <!-- Contenido en dos columnas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Información del usuario -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Información</h3>
                    <p><strong>Email:</strong> <span class="text-gray-600">{{ $user->email }}</span></p>
                    <p><strong>Categoría:</strong> <span
                            class="text-gray-600">{{ $user->categoria->nombre ?? 'N/A' }}</span></p>
                    <p><strong>Especialidad:</strong> <span
                            class="text-gray-600">{{ $user->maquina->nombre ?? 'N/A' }}</span></p>


                </div>

                <!-- Resumen de asistencias -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Asistencias</h3>
                    <div id="resumen-asistencia" class="bg-gray-100 p-3 rounded-lg">
                        <p><strong>Vacaciones asignadas: </strong> {{ $resumen['diasVacaciones'] }}</p>
                        <p><strong>Faltas injustificadas: </strong> {{ $resumen['faltasInjustificadas'] }}</p>
                        <p><strong>Faltas justificadas: </strong> {{ $resumen['faltasJustificadas'] }}</p>
                        <p><strong>Días de baja: </strong> {{ $resumen['diasBaja'] }}</p>
                    </div>
                </div>

            </div>
        </div>


        <div class="bg-white rounded-lg shadow-lg">
            <div id="calendario"></div>
        </div>
    </div>

    <!-- Cargar FullCalendar con prioridad -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>
    <!-- JS separado -->
    <script src="{{ asset('js/usuario-detalle.js') }}"></script>

    <script>
        function actualizarResumenAsistencia() {
            fetch("{{ route('users.resumen-asistencia', ['user' => $user->id]) }}")
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    const div = document.getElementById('resumen-asistencia');
                    if (div) {
                        div.style.opacity = 0.5;
                        div.innerHTML = `
                            <p><strong>Vacaciones asignadas: </strong> ${data.diasVacaciones}</p>
                            <p><strong>Faltas injustificadas: </strong> ${data.faltasInjustificadas}</p>
                            <p><strong>Faltas justificadas: </strong> ${data.faltasJustificadas}</p>
                            <p><strong>Días de baja: </strong> ${data.diasBaja}</p>
                        `;
                        setTimeout(() => div.style.opacity = 1, 200);
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar asistencias:', error);
                });
        }
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendario');
            actualizarResumenAsistencia();
            // ✅ Recuperar vista y fecha guardadas
            const vistaGuardada = localStorage.getItem('ultimaVistaCalendario') || 'dayGridMonth';
            const fechaGuardada = localStorage.getItem('fechaCalendario');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,
                locale: 'es',
                height: 'auto',
                selectable: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                // ✅ Guardar la vista y fecha actual cada vez que cambia
                datesSet: function(info) {
                    let fechaActual = info.startStr;

                    if (calendar.view.type === 'dayGridMonth') {
                        const middleDate = new Date(info.start);
                        middleDate.setDate(middleDate.getDate() + 15); // Aproximadamente mitad del mes
                        fechaActual = middleDate.toISOString().split('T')[0];
                    }

                    localStorage.setItem('fechaCalendario', fechaActual);
                    localStorage.setItem('ultimaVistaCalendario', calendar.view.type);
                },
                events: '{{ route('users.eventos-turnos', $user->id) }}',
                select: function(info) {
                    // Obtener fechas inicial y final
                    let fechaInicio = info.startStr;
                    let fechaFinObj = new Date(info.end);
                    fechaFinObj.setDate(fechaFinObj.getDate()); // Ajuste para que sea inclusiva
                    let fechaFin = fechaFinObj.toISOString().split('T')[0];

                    // Generar HTML condicionalmente según si se selecciona un solo día o un rango
                    let mensajeFecha = fechaInicio === fechaFin ?
                        `<p>${fechaInicio}</p>` :
                        `<p>Desde: ${fechaInicio}</p><p>Hasta: ${fechaFin}</p>`;

                    Swal.fire({
                        title: "Selecciona un turno",
                        html: `
                    ${mensajeFecha}
                  <select id="tipo-dia" class="swal2-select">
  <option value="eliminarTurnoEstado">🗑 Eliminar Turno</option>


    @foreach ($turnos as $turno)
        <option value="{{ $turno->nombre }}">{{ ucfirst($turno->nombre) }}</option>
    @endforeach
<option value="eliminarEstado">🗑 Eliminar Estado</option>
    <option value="vacaciones">🏖 Vacaciones</option>
    <option value="baja">🤒 Baja</option>
    <option value="justificada">✅ Falta Justificada</option>
    <option value="injustificada">❌ Falta Injustificada</option>
</select>

                `,
                        showCancelButton: true,
                        confirmButtonText: "Registrar",
                        cancelButtonText: "Cancelar",
                        preConfirm: () => {
                            return document.getElementById("tipo-dia").value;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let tipoSeleccionado = result.value;

                            if (tipoSeleccionado === "eliminarTurnoEstado" ||
                                tipoSeleccionado === "eliminarEstado") {
                                let eventosEnRango = calendar.getEvents().filter(event => {
                                    let eventDate = event.startStr;
                                    return eventDate >= fechaInicio && eventDate <=
                                        fechaFin;
                                });

                                // Detectar si es tipo "festivo" (todos los eventos en rango con título "Festivo")
                                let todosSonFestivo = eventosEnRango.length > 0 &&
                                    eventosEnRango.every(e => e.title?.toLowerCase() ===
                                        "festivo");

                                let body = {
                                    fecha_inicio: fechaInicio,
                                    fecha_fin: fechaFin
                                };

                                if (todosSonFestivo) {
                                    body.tipo_turno =
                                        "festivo"; // eliminar de todos los usuarios
                                } else {
                                    body.user_id =
                                        "{{ $user->id }}"; // solo del usuario actual

                                    // ✅ Para diferenciar si es eliminar turno o eliminar estado
                                    body.tipo =
                                        tipoSeleccionado; // directamente lo que viene del select

                                }

                                fetch("{{ route('asignaciones-turnos.destroy') }}", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                        },
                                        body: JSON.stringify(body)
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {

                                            eventosEnRango.forEach(event => event.remove());
                                            calendar.refetchEvents();
                                            setTimeout(actualizarResumenAsistencia, 200);
                                        } else {
                                            Swal.fire({
                                                title: "Error",
                                                text: data.error,
                                                icon: "error"
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error:", error);
                                        Swal.fire({
                                            title: "Error",
                                            text: "Ocurrió un problema al eliminar los turnos.",
                                            icon: "error"
                                        });
                                    });

                            } else {
                                // Petición para asignar turno o estado
                                fetch("{{ route('asignaciones-turnos.store') }}", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                        },
                                        body: JSON.stringify({
                                            user_id: "{{ $user->id }}",
                                            fecha_inicio: fechaInicio,
                                            fecha_fin: fechaFin,
                                            tipo: tipoSeleccionado
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            // ✅ Recarga completa de la página
                                            calendar.refetchEvents();
                                            setTimeout(actualizarResumenAsistencia, 200);
                                        } else {
                                            Swal.fire({
                                                title: "Error",
                                                text: data.error,
                                                icon: "error"
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error:", error);
                                        Swal.fire({
                                            title: "Error",
                                            text: "Ocurrió un problema al registrar los turnos.",
                                            icon: "error"
                                        });
                                    });
                            }

                        }
                    });
                }
            });

            calendar.render();
        });
    </script>
</x-app-layout>
