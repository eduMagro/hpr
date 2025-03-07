<x-app-layout>
    <x-slot name="title">Usuarios - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __(auth()->user()->name) }}
        </h2>
        @if (Auth::check() && Auth::user()->rol == 'oficina')
            <p class="text-green-600">Usuarios conectados:
                <strong>{{ $usuariosConectados }}</strong>
            </p>
        @endif
    </x-slot>
    @if (Auth::check() && Auth::user()->rol == 'oficina')
        <div class="container mx-auto px-4 py-6">
            <div class="mb-4 flex items-center space-x-4">
                <a href="{{ route('register') }}" class="btn btn-primary">Registrar Usuario</a>
                <a href="{{ route('vacaciones.index') }}" class="btn btn-primary">Mostrar Vacaciones Globales</a>
                <form action="{{ route('generar-turnos') }}" method="POST" class="form-cargando">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-cargando">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        <span class="texto">Generar Turnos Globales</span>
                    </button>
                </form>
            </div>
            <button class="btn btn-secondary" type="button" data-bs-toggle="collapse"
                data-bs-target="#filtrosBusqueda">
                🔍 Filtros Avanzados
            </button>
            <!-- FORMULARIO DE FILTROS -->
            <div id="filtrosBusqueda" class="collapse">
                <form method="GET" action="{{ route('users.index') }}" class="card card-body shadow-sm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <!-- Filtro: Nombre -->
                            <input type="text" name="name" class="form-control" placeholder="Buscar por nombre"
                                value="{{ request('name') }}">
                        </div>
                        <div class="col-md-3">
                            <!-- Filtro: Email -->
                            <input type="text" name="email" class="form-control" placeholder="Buscar por email"
                                value="{{ request('email') }}">
                        </div>
                        <div class="col-md-3">
                            <!-- Filtro: Rol -->
                            <select name="rol" class="form-control">
                                <option value="">-- Filtrar por Rol --</option>
                                @foreach ($roles as $nombre)
                                    <option value="{{ $nombre }}"
                                        {{ request('rol') == $nombre ? 'selected' : '' }}>
                                        {{ ucfirst($nombre) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <!-- Filtro: Categoría -->
                            <select name="categoria" class="form-control">
                                <option value="">-- Filtrar por Categoría --</option>
                                @foreach ($categorias as $nombre)
                                    <option value="{{ $nombre }}"
                                        {{ request('categoria') == $nombre ? 'selected' : '' }}>
                                        {{ ucfirst($nombre) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <!-- Filtro: Especialidad -->
                            <select name="especialidad" class="form-control">
                                <option value="">-- Filtrar por Especialidad --</option>
                                @foreach ($especialidades as $nombre)
                                    <option value="{{ $nombre }}"
                                        {{ request('especialidad') == $nombre ? 'selected' : '' }}>
                                        {{ ucfirst($nombre) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <!-- Filtro: Turno -->
                            <select name="turno" class="form-control">
                                <option value="">-- Filtrar por Turno Actual --</option>
                                @foreach ($turnosHoy as $turno)
                                    <option value="{{ $turno }}"
                                        {{ request('turno') == $turno ? 'selected' : '' }}>
                                        {{ ucfirst($turno) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <!-- Filtro: Estado -->
                            <select name="estado" class="form-control">
                                <option value="">-- Filtrar por Estado --</option>
                                <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo
                                </option>
                                <option value="inactivo" {{ request('estado') == 'inactivo' ? 'selected' : '' }}>
                                    Inactivo
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <!-- Filtro: Número de registros a mostrar -->
                            <select name="per_page" class="form-control">
                                <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10 registros
                                </option>
                                <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25 registros
                                </option>
                                <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 registros
                                </option>
                                <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100
                                    registros
                                </option>
                            </select>
                        </div>
                        <!-- Botones -->
                        <div class="col-12 d-flex justify-content-between">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-warning">
                                <i class="fas fa-undo"></i> Resetear Filtros
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- TABLA DE USUARIOS -->
            <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg mt-4">
                <table class="w-full border border-gray-300 rounded-lg">
                    <thead class="bg-blue-500 text-white">
                        <tr class="text-left text-sm uppercase">
                            <th class="py-3 px-2 border text-center">
                                <a
                                    href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'order' => request('order') === 'asc' ? 'desc' : 'asc']) }}">
                                    ID <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th class="py-3 px-2 border text-center">Nombre</th>
                            <th class="py-3 px-2 border text-center">Email</th>
                            <th class="py-3 px-2 border text-center">Rol</th>
                            <th class="py-3 px-2 border text-center">Categoría</th>
                            <th class="py-3 px-2 border text-center">Especialidad</th>
                            <th class="py-3 px-2 border text-center">Turno</th>
                            <th class="py-3 px-2 border text-center">Estado</th>
                            <th class="py-3 px-2 border text-center">Generar Turnos</th>
                            <th class="py-3 px-2 border text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse ($registrosUsuarios as $user)
                            <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer"
                                x-data="{ editando: false, usuario: @js($user) }">

                                <td class="px-2 py-3 text-center border" x-text="usuario.id"></td>

                                <td class="px-2 py-3 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="usuario.name"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="usuario.name"
                                        class="form-input w-full">
                                </td>

                                <td class="px-2 py-3 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="usuario.email"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="usuario.email"
                                        class="form-input w-full">
                                </td>

                                <td class="px-2 py-3 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="usuario.rol"></span>
                                    </template>
                                    <select x-show="editando" x-model="usuario.rol" class="form-input w-full">
                                        <option value="">Selecciona rol</option>
                                        <option value="oficina">Oficina</option>
                                        <option value="operario">Operario</option>
                                        <option value="visitante">Visitante</option>
                                    </select>
                                </td>

                                <td class="px-2 py-3 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="usuario.categoria"></span>
                                    </template>
                                    <select x-show="editando" x-model="usuario.categoria" class="form-input w-full">
                                        <option value="">Selecciona cat.</option>
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria }}"
                                                {{ request('categoria') == $categoria ? 'selected' : '' }}>
                                                {{ ucfirst($categoria) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="px-2 py-3 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="usuario.especialidad"></span>
                                    </template>
                                    <select x-show="editando" x-model="usuario.especialidad"
                                        class="form-input w-full">
                                        <option value="">Selecciona esp.</option>
                                        @foreach ($especialidades as $nombre)
                                            <option value="{{ $nombre }}"
                                                {{ request('especialidad') == $nombre ? 'selected' : '' }}>
                                                {{ ucfirst($nombre) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="px-2 py-3 text-center border">
                                    <template x-if="!editando">
                                        <span
                                            x-text="usuario.turno ? usuario.turno.charAt(0).toUpperCase() + usuario.turno.slice(1) : 'N/A'"></span>
                                    </template>
                                    <select x-show="editando" x-model="usuario.turno" class="form-input w-full">
                                        <option value="">Selecciona turno</option>
                                        <option value="nocturno">Nocturno</option>
                                        <option value="diurno">Diurno</option>
                                        <option value="flexible">Flexible</option>
                                    </select>
                                </td>
                                <td class="px-2 py-3 text-center border">
                                    @if ($user->isOnline())
                                        <span class="text-green-600">En línea</span>
                                    @else
                                        <span class="text-gray-500">Desconectado</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-center border">
                                    <form action="{{ route('profile.generar.turnos', $user->id) }}" method="POST"
                                        id="form-generar-turnos">
                                        @csrf
                                        <button type="submit"
                                            class="w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-1 px-1 rounded"
                                            onclick="confirmarGenerarTurnos()">
                                            Generar Turnos
                                        </button>
                                    </form>

                                </td>
                                <td class="py-3 border flex flex-row gap-2 justify-center items-center text-center">
                                    <a href="{{ route('users.show', $user->id) }}"
                                        class="text-green-500 hover:underline">Ver</a>
                                    <span> | </span>
                                    <button @click.stop="editando = !editando">
                                        <span x-show="!editando">✏️</span>
                                        <span x-show="editando" class="mr-2">✖</span>
                                        <span x-show="editando" @click.stop="guardarCambios(usuario)">✅</span>
                                    </button>
                                    <span> | </span>
                                    <a href="{{ route('users.edit', $user->id) }}"
                                        class="text-green-500 hover:underline">Ajustes</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-gray-500">No hay usuarios disponibles.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-center">
                {{ $registrosUsuarios->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}

            </div>
        </div>
    @else
        {{-- ------------------------------- FICHAJE MODO OPERARIO -------------------------------- --}}
        <div class="flex justify-between items-center w-full gap-4 p-4">
            <select id="obraSeleccionada" class="w-full py-2 px-4 border rounded-md">
                @foreach ($obras as $obra)
                    <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex justify-between items-center w-full gap-4 p-4">
            <button onclick="registrarFichaje('entrada')"
                class="w-full py-2 px-4 bg-green-600 text-white rounded-md btn-cargando">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Entrada</span>
            </button>

            <button onclick="registrarFichaje('salida')"
                class="w-full py-2 px-4 bg-red-600 text-white rounded-md btn-cargando">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Salida</span>
            </button>
        </div>

        <div class="container mx-auto px-4 py-6">
            {{-- ------------------------------- FICHA MODO OPERARIO -------------------------------- --}}
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold mb-2">Información del Usuario</h3>
                <p><strong>Nombre:</strong> {{ auth()->user()->name }}</p>
                <p><strong>Correo:</strong> {{ auth()->user()->email }}</p>
                <p><strong>Puesto:</strong> {{ auth()->user()->rol }}</p>
                <p><strong>Categoría:</strong> {{ auth()->user()->categoria }}</p>
                <p><strong>Especialidad:</strong> {{ auth()->user()->especialidad }}</p>
                <p><strong>Días de vacaciones restantes:</strong> {{ auth()->user()->dias_vacaciones }}</p>
            </div>
        </div>
        {{-- ------------------------------- CALENDARIO MODO OPERARIO -------------------------------- --}}
        <div class="bg-white rounded-lg shadow-lg">
            <div id="calendario"></div>
        </div>
    @endif
    </div>

    <!-- Cargar FullCalendar con prioridad -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>
    <script>
        // ---------------------------------------------------- CALENDARIO 
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendario');

            // Cargar eventos directamente desde Laravel
            var eventosDesdeLaravel = {!! json_encode($eventos) !!};
            var coloresTurnos = {!! json_encode($coloresTurnos) !!}; // Colores desde el backend

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                selectable: false, // ❌ Desactivar selección
                editable: false, // ❌ Desactivar edición
                events: eventosDesdeLaravel // ✅ Solo mostrar los turnos asignados
            });

            calendar.render();
        });
        // ---------------------------------------------------- REGISTRAR FICHAJE
        function registrarFichaje(tipo) {

            let obraId = document.getElementById("obraSeleccionada").value;

            if (!navigator.geolocation) {
                console.error("❌ Geolocalización no soportada en este navegador.");
                Swal.fire({
                    icon: 'error',
                    title: 'Geolocalización no disponible',
                    text: '⚠️ Tu navegador no soporta geolocalización.',
                });
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    console.log("🟢 Callback ejecutado. Datos de posición:", position);

                    let latitud = position?.coords?.latitude;
                    let longitud = position?.coords?.longitude;

                    console.log(`📍 Coordenadas obtenidas: Latitud ${latitud}, Longitud ${longitud}`);

                    // 🔍 Verificar si latitud y longitud son undefined
                    if (latitud === undefined || longitud === undefined) {
                        console.error("❌ Error: La API no devolvió coordenadas.");
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de ubicación',
                            text: 'No se pudieron obtener las coordenadas. Intenta nuevamente.',
                        });
                        return;
                    }

                    Swal.fire({
                        title: 'Confirmar Fichaje',
                        text: `¿Quieres registrar una ${tipo}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, fichar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            console.log("🟢 Enviando datos al backend...");

                            fetch("{{ route('registros-fichaje.store') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },
                                    body: JSON.stringify({
                                        user_id: "{{ auth()->id() }}",
                                        tipo: tipo,
                                        latitud: latitud, // ✅ Ahora enviamos correctamente latitud
                                        longitud: longitud, // ✅ Ahora enviamos correctamente longitud
                                        obra_id: obraId
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    console.log("📩 Respuesta del servidor:", data);

                                    if (data.success) {
                                        let mensaje = data.success;

                                        if (data.warning) {
                                            mensaje += "\n⚠️ " + data.warning;
                                        }

                                        Swal.fire({
                                            title: "Fichaje registrado",
                                            text: mensaje,
                                            icon: data.warning ? "warning" : "success",
                                            showConfirmButton: true
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        let errorMessage = data.error;
                                        if (data.messages) {
                                            errorMessage = data.messages.join("\n");
                                        }
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: errorMessage,
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error("❌ Error en la solicitud fetch:", error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error de conexión',
                                        text: 'No se pudo comunicar con el servidor.',
                                    });
                                });
                        }
                    });
                },
                function(error) {
                    console.error(`⚠️ Error de geolocalización: ${error.message}`);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de ubicación',
                        text: `⚠️ No se pudo obtener la ubicación: ${error.message}`,
                    });
                }, {
                    enableHighAccuracy: true
                }
            );
        }
        // ---------------------------------------------------- GUARDAR CAMBIOS
        function guardarCambios(usuario) {
            fetch(`/actualizar-usuario/${usuario.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(usuario)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Usuario actualizado",
                            text: "Los cambios se han guardado correctamente.",
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        let errores = Object.values(data.error).flat().join('\n'); // Convierte el objeto en texto
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de validación',
                            text: errores
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        text: "No se pudo actualizar el usuario. Inténtalo nuevamente.",
                        confirmButtonText: "OK"
                    });
                });
        }
        // ---------------------------------------------------- GENERAR TURNOS SWEETALERT
        function confirmarGenerarTurnos() {
            Swal.fire({
                title: "Selecciona el turno inicial",
                text: "¿Cuál es su primer turno?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Mañana",
                cancelButtonText: "Tarde",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById("turno_inicio").value = "mañana";
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    document.getElementById("turno_inicio").value = "tarde";
                } else {
                    return;
                }

                // Enviar el formulario con la opción seleccionada
                document.getElementById("form-generar-turnos").submit();
            });
        }
    </script>
</x-app-layout>
