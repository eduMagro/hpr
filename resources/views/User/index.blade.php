<x-app-layout>
    <x-slot name="title">Usuarios - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __(auth()->user()->name) }}
        </h2>
        @if (Auth::check() && Auth::user()->categoria == 'administrador')
            <p class="text-green-600">Usuarios conectados:
                <strong>{{ $usuariosConectados }}</strong>
            </p>
        @endif
    </x-slot>
    @if (Auth::check() && Auth::user()->categoria == 'administrador')
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center w-full gap-4 p-4">
                <select id="obraSeleccionada" class="w-full py-2 px-4 border rounded-md">
                    @foreach ($obras as $obra)
                        <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-between items-center w-full gap-4 p-4">
                <button onclick="registrarFichaje('entrada')"
                    class="w-full py-2 px-4 bg-green-600 text-white rounded-md">
                    Entrada
                </button>
                <button onclick="registrarFichaje('salida')" class="w-full py-2 px-4 bg-red-600 text-white rounded-md">
                    Salida
                </button>
            </div>

            <div class="mb-4 flex items-center space-x-4">
                <a href="{{ route('register') }}" class="btn btn-primary">Registrar Usuario</a>
                <a href="{{ route('vacaciones.index') }}" class="btn btn-primary">Mostrar Vacaciones Globales</a>
                <form action="{{ route('generar-turnos') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Generar Turnos
                    </button>
                </form>

            </div>

            <!-- FORMULARIO DE BUSQUEDA -->
            <form method="GET" action="{{ route('users.index') }}" class="form-inline mt-3 mb-3">
                <input type="text" name="name" class="form-control mb-3" placeholder="Buscar por nombre"
                    value="{{ request('name') }}">
                <button type="submit" class="btn btn-info ml-2">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </form>
            <!-- Tabla de usuarios con edición en línea -->
            <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="w-full border border-gray-300 rounded-lg">
                    <thead class="bg-blue-500 text-white">
                        <tr class="text-left text-sm uppercase">
                            <th class="py-3 px-2 border text-center">ID</th>
                            <th class="py-3 px-2 border text-center">Nombre</th>
                            <th class="py-3 px-2 border text-center">Email</th>
                            <th class="py-3 px-2 border text-center">Rol</th>
                            <th class="py-3 px-2 border text-center">Categoría</th>
                            <th class="py-3 px-2 border text-center">Especialidad</th>
                            <th class="py-3 px-2 border text-center">Turno</th>
                            <th class="py-3 px-2 border text-center">Estado</th>
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
                                        <option value="administrador">Admninistrador</option>
                                        <option value="administracion">Dept. Administración</option>
                                        <option value="oficial 1">Oficial 1ª</option>
                                        <option value="oficial 2">Oficial 2ª</option>
                                        <option value="oficial 3">Oficial 3ª</option>
                                        <option value="gruista">Gruista</option>
                                        <option value="camionero">Camionero</option>
                                    </select>
                                </td>

                                <td class="px-2 py-3 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="usuario.especialidad"></span>
                                    </template>
                                    <select x-show="editando" x-model="usuario.especialidad" class="form-input w-full">
                                        <option value="">Selecciona esp.</option>
                                        <option value="administrador">MSR20</option>
                                        <option value="administracion">SL28</option>
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
                                    <x-boton-eliminar :action="route('users.destroy', $user->id)" />
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
                {{ $registrosUsuarios->links() }}
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
            <button onclick="registrarFichaje('entrada')" class="w-full py-2 px-4 bg-green-600 text-white rounded-md">
                Entrada
            </button>
            <button onclick="registrarFichaje('salida')" class="w-full py-2 px-4 bg-red-600 text-white rounded-md">
                Salida
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
        <div class="mt-6 bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold mb-2">Calendario de Fichajes</h3>
            <div id="calendario"></div>
        </div>
    @endif
    </div>

    <!-- Cargar FullCalendar con prioridad -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>
    <script>
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
    </script>

    <script src="//unpkg.com/alpinejs" defer></script>
    <script>
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
    </script>

    <script>
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
    </script>
</x-app-layout>
