<x-app-layout>
    <x-slot name="title">Usuarios - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __(auth()->user()->name) }}
            </h2>
        </div>
    </x-slot>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @if (Auth::check() && Auth::user()->rol == 'oficina')
        <div class="w-full" x-data="{ open: false }">
            <!-- CONTENEDOR CON X-DATA -->
            <div class="sm:hidden relative" x-data="{ open: false }">
                <!-- Botón que abre el menú -->
                <button @click="open = !open"
                    class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                    Opciones
                </button>

                <!-- Menú desplegable estilizado -->
                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    @click.away="open = false"
                    class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                    x-cloak>

                    <a href="{{ route('register') }}"
                        class="block px-2 py-3 text-blue-700 hover:bg-blue-50 hover:text-blue-900 transition text-sm font-medium">
                        📋 Registrar Usuario
                    </a>

                    <a href="{{ route('vacaciones.index') }}"
                        class="relative block px-2 py-3 text-blue-700 hover:bg-blue-50 hover:text-blue-900 transition text-sm font-medium">
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
                        class="block px-2 py-3 text-blue-700 hover:bg-blue-50 hover:text-blue-900 transition text-sm font-medium">
                        ⏱️ Registros
                    </a>
                </div>
            </div>


            <!-- Acciones visibles en escritorio -->
            <div class="hidden sm:flex sm:mt-0 w-full">
                <a href="{{ route('register') }}"
                    class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-none first:rounded-l-lg last:rounded-r-lg transition">
                    📋 Registrar Usuario
                </a>

                <a href="{{ route('vacaciones.index') }}"
                    class="relative flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-none transition">
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
                    class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-none last:rounded-r-lg transition">
                    ⏱️ Registros Entrada y Salida
                </a>
            </div>

        </div>

        @if (count($filtrosActivos))
            <div class="alert alert-info text-sm mt-2 mb-4 shadow-sm">
                <strong>Filtros aplicados:</strong> {!! implode(', ', $filtrosActivos) !!}
            </div>
        @endif

        <!-- TABLA DE USUARIOS -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg mt-4">
            <table class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border">{!! $ordenables['id'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['name'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['email'] !!}</th>
                        <th class="p-2 border">Móvil Personal</th>
                        <th class="p-2 border">Móvil Empresa</th>
                        <th class="p-2 border">{!! $ordenables['dni'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['empresa'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['rol'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['categoria'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['maquina_id'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['turno'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['estado'] !!}</th>
                        <th class="p-2 border"></th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                    <tr class="text-center text-xs uppercase">
                        <form method="GET" action="{{ route('users.index') }}">
                            <th class="p-1 border"></th> <!-- ID: sin filtro directo -->
                            <th class="p-1 border">
                                <input type="text" name="name" value="{{ request('name') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="email" value="{{ request('email') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="movil_personal" value="{{ request('movil_personal') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="movil_empresa" value="{{ request('movil_empresa') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="dni" value="{{ request('dni') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <select name="empresa_id" class="form-control form-control-sm">
                                    <option value="">Todas</option>
                                    @foreach ($empresas as $empresa)
                                        <option value="{{ $empresa->id }}"
                                            {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                            {{ $empresa->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="p-1 border">
                                <select name="rol" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol }}"
                                            {{ request('rol') == $rol ? 'selected' : '' }}>
                                            {{ ucfirst($rol) }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="p-1 border">
                                <select name="categoria_id" class="form-control form-control-sm">
                                    <option value="">Todas</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}"
                                            {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                            {{ $categoria->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="p-1 border">
                                <select name="maquina_id" class="form-control form-control-sm">
                                    <option value="">Todas</option>
                                    @foreach ($maquinas as $maquina)
                                        <option value="{{ $maquina->id }}"
                                            {{ request('maquina') == $maquina->id ? 'selected' : '' }}>
                                            {{ $maquina->nombre }}
                                        </option>
                                    @endforeach

                                </select>
                            </th>
                            <th class="p-1 border">
                                <select name="turno" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    @foreach ($turnos as $turno)
                                        <option value="{{ $turno }}"
                                            {{ request('turno') == $turno ? 'selected' : '' }}>
                                            {{ ucfirst($turno) }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="p-1 border">
                                <select name="estado" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>
                                        Activo
                                    </option>
                                    <option value="inactivo" {{ request('estado') == 'inactivo' ? 'selected' : '' }}>
                                        Inactivo</option>
                                </select>
                            </th>
                            <th class="p-1 border"></th>
                            <th class="p-1 border text-center">
                                <button type="submit" class="btn btn-sm btn-info px-2">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('users.index') }}" class="btn btn-sm btn-warning px-2">
                                    <i class="fas fa-undo"></i>
                                </a>
                            </th>
                        </form>
                    </tr>

                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($registrosUsuarios as $user)
                        {{-- <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer"
                                x-data="{ editando: false, usuario: @js($user) }" @dblclick="editando = true"> --}}
                        <tr tabindex="0" x-data="{
                            editando: false,
                            usuario: @js($user),
                            original: JSON.parse(JSON.stringify(@js($user)))
                        }"
                            @dblclick="if(!$event.target.closest('input')) {
                                      if(!editando) {
                                        editando = true;
                                      } else {
                                        planilla = JSON.parse(JSON.stringify(original));
                                        editando = false;
                                      }
                                    }"
                            @keydown.enter.stop="guardarCambios(user); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                            <td class="px-2 py-3 text-center border" x-text="usuario.id"></td>

                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.name"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="usuario.name"
                                    class="form-control form-control-sm"
                                    @keydown.enter.stop="guardarCambios(usuario)">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.email"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="usuario.email"
                                    class="form-control form-control-sm"
                                    @keydown.enter.stop="guardarCambios(usuario)">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.movil_personal"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="usuario.movil_personal"
                                    class="form-control form-control-sm"
                                    @keydown.enter.stop="guardarCambios(usuario)">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.movil_empresa"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="usuario.movil_empresa"
                                    class="form-control form-control-sm"
                                    @keydown.enter.stop="guardarCambios(usuario)">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.dni"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="usuario.dni"
                                    class="form-control form-control-sm"
                                    @keydown.enter.stop="guardarCambios(usuario)">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.empresa?.nombre ?? 'Sin empresa'"></span>
                                </template>
                                <select x-show="editando" x-model="usuario.empresa_id"
                                    class="form-control form-control-sm"
                                    @keydown.enter.stop="guardarCambios(usuario)">
                                    <option value="">Selecciona empresa</option>
                                    @foreach ($empresas as $empresa)
                                        <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.rol"></span>
                                </template>
                                <select x-show="editando" x-model="usuario.rol" class="form-control form-control-sm">
                                    <option value="">Selecciona rol</option>
                                    <option value="oficina">Oficina</option>
                                    <option value="operario">Operario</option>
                                    <option value="visitante">Visitante</option>
                                </select>
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <!-- Mostrar nombre de categoría si no está editando -->
                                <template x-if="!editando">
                                    <span x-text="usuario.categoria?.nombre ?? 'Sin asignar'"></span>
                                </template>

                                <!-- Select editable con Alpine -->
                                <select x-show="editando" x-model="usuario.categoria_id"
                                    class="form-control form-control-sm">
                                    <option value="">Selecciona cat.</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}">
                                            {{ ucfirst($categoria->nombre) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <template x-if="!editando">
                                        <span x-text="usuario.maquina?.nombre ?? 'Sin asignar'"></span>
                                    </template>
                                </template>
                                <select x-show="editando" x-model="usuario.maquina_id"
                                    class="form-control form-control-sm">
                                    <option value="">Selecciona máq.</option>
                                    @foreach ($maquinas as $maquina)
                                        <option value="{{ $maquina->id }}">{{ $maquina->nombre ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="usuario.turno ? usuario.turno.charAt(0).toUpperCase() + usuario.turno.slice(1) : 'N/A'"></span>
                                </template>
                                <select x-show="editando" x-model="usuario.turno"
                                    class="form-control form-control-sm">
                                    <option value="">Selecciona turno</option>
                                    <option value="nocturno">Nocturno</option>
                                    <option value="diurno">Diurno</option>
                                    <option value="mañana">Mañana</option>
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
                                    id="form-generar-turnos-{{ $user->id }}">
                                    @csrf
                                    <input type="hidden" name="turno_inicio" id="turno_inicio_{{ $user->id }}">
                                    <input type="hidden" id="usuario_turno_{{ $user->id }}"
                                        value="{{ $user->turno }}">
                                    <button type="button"
                                        class="w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-1 px-1 rounded"
                                        onclick="confirmarGenerarTurnos({{ $user->id }})">
                                        Generar Turnos
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 border flex flex-row gap-2 justify-center items-center text-center">
                                <template x-if="editando">
                                    <button @click="guardarCambios(usuario); editando = false"
                                        class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded shadow">
                                        Guardar
                                    </button>
                                </template>
                                <a href="{{ route('users.show', $user->id) }}"
                                    class="text-green-500 hover:underline">Ver</a>
                                <span> | </span>
                                <a href="{{ route('users.edit', $user->id) }}"
                                    class="text-green-500 hover:underline">Editar</a>
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

            <div class="bg-white p-6 rounded-lg shadow-lg max-w-3xl mx-auto mb-6 border border-gray-200">
                <!-- Encabezado con avatar -->
                <div class="flex items-center space-x-4 border-b pb-4 mb-4">
                    <div
                        class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center text-2xl font-bold text-gray-700">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold">{{ auth()->user()->name }}</h3>
                        <p class="text-gray-500 text-sm">{{ auth()->user()->rol }}</p>
                    </div>
                </div>

                <!-- Contenido en dos columnas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Información del usuario -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Información</h3>
                        <p><strong>Email:</strong> <span class="text-gray-600">{{ auth()->user()->email }}</span></p>
                        <p><strong>Categoría:</strong> <span
                                class="text-gray-600">{{ auth()->user()->categoria->nombre ?? 'N/A' }}</span></p>
                        <p><strong>Especialidad:</strong> <span
                                class="text-gray-600">{{ auth()->user()->maquina->nombre ?? 'N/A' }}</span></p>
                        <p id="vacaciones-restantes"
                            class="mt-3 p-2 bg-blue-100 text-blue-700 rounded-md text-center">
                            Vacaciones asignadas: {{ $diasVacaciones }}
                        </p>
                    </div>

                    <!-- Resumen de asistencias -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Asistencias</h3>
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <p><strong>Faltas injustificadas:</strong> <span
                                    class="text-red-600">{{ $faltasInjustificadas }}</span></p>
                            <p><strong>Faltas justificadas:</strong> <span
                                    class="text-green-600">{{ $faltasJustificadas }}</span></p>
                            <p><strong>Días de baja:</strong> <span class="text-purple-600">{{ $diasBaja }}</span>
                            </p>
                        </div>
                    </div>
                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendario');

            // Eventos y colores desde Laravel
            const eventosDesdeLaravel = {!! json_encode($eventos) !!};
            const coloresTurnos = {!! json_encode($coloresTurnos) !!};

            // Fechas bloqueadas por vacaciones ya solicitadas o aprobadas
            const fechasBloqueadas = eventosDesdeLaravel
                .filter(e => e.title === 'Solicitud pendiente' || e.title === 'Vacaciones')
                .map(e => e.start);

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                selectable: true,
                selectMirror: true,
                select: function(info) {
                    const fechaInicio = moment(info.startStr);
                    const fechaFin = moment(info.endStr).subtract(1, 'day'); // endStr es exclusivo

                    const rangoSeleccionado = [];
                    let actual = fechaInicio.clone();
                    while (actual.isSameOrBefore(fechaFin)) {
                        rangoSeleccionado.push(actual.format('YYYY-MM-DD'));
                        actual.add(1, 'day');
                    }

                    const conflicto = rangoSeleccionado.find(fecha => fechasBloqueadas.includes(fecha));
                    if (conflicto) {
                        Swal.fire('🚫 No permitido',
                            `Ya hay una solicitud o vacaciones el día ${conflicto}.`, 'warning');
                        return;
                    }

                    const fechaInicioFormato = fechaInicio.format('YYYY-MM-DD');
                    const fechaFinFormato = fechaFin.format('YYYY-MM-DD');

                    Swal.fire({
                        title: 'Solicitar vacaciones',
                        html: `
                        <p>📅 Del <b>${fechaInicioFormato}</b> al <b>${fechaFinFormato}</b></p>
                    `,
                        showCancelButton: true,
                        confirmButtonText: 'Enviar solicitud',
                        cancelButtonText: 'Cancelar'
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch("{{ route('vacaciones.solicitar') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },
                                    body: JSON.stringify({
                                        fecha_inicio: fechaInicioFormato,
                                        fecha_fin: fechaFinFormato
                                    })
                                })
                                .then(async res => {
                                    const text = await res.text();
                                    try {
                                        return JSON.parse(text);
                                    } catch (e) {
                                        console.error(
                                            "Respuesta no válida del servidor:",
                                            text);
                                        throw new Error(
                                            "Error inesperado del servidor");
                                    }
                                })
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire("✅ Solicitud enviada", data.success,
                                            "success").then(() => location.reload());
                                    } else {
                                        Swal.fire("❌ Error", data.error ||
                                            "Error inesperado", "error");
                                    }
                                })
                                .catch(err => {
                                    Swal.fire("❌ Error", err.message, "error");
                                });
                        }
                    });
                },
                editable: false,
                events: eventosDesdeLaravel
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

                            fetch("{{ url('/fichar') }}", {

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
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error("❌ Error en la solicitud fetch:", error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error de conexión',
                                        text: 'No se pudo comunicar con el servidor.',
                                    }).then(() => {
                                        window.location.reload();
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

        function guardarCambios(usuario) {
            fetch(`{{ route('usuarios.actualizar', '') }}/${usuario.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(usuario)
                })
                .then(async response => {
                    const data = await response.json();

                    if (!response.ok) {
                        // Aquí sí muestra el motivo del fallo
                        let mensaje = data.error || 'Error desconocido';
                        if (typeof mensaje === 'object') {
                            mensaje = Object.values(mensaje).flat().join('\n');
                        }

                        throw new Error(mensaje);
                    }

                    // Si todo fue bien
                    Swal.fire({
                        icon: "success",
                        title: "Usuario actualizado",
                        text: "Los cambios se han guardado correctamente.",
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(error => {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: error.message || 'No se pudo actualizar el usuario. Inténtalo nuevamente.',
                    });
                });
        }

        // ---------------------------------------------------- GENERAR TURNOS SWEETALERT

        function confirmarGenerarTurnos(userId) {
            let usuarioTurno = document.getElementById("usuario_turno_" + userId).value;

            if (usuarioTurno === "diurno") {
                Swal.fire({
                    title: "Selecciona el turno inicial",
                    text: "¿Con qué turno quieres comenzar para el turno diurno?",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Mañana",
                    cancelButtonText: "Tarde",
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33"
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById("turno_inicio_" + userId).value = "mañana";
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        document.getElementById("turno_inicio_" + userId).value = "tarde";
                    } else {
                        return;
                    }

                    document.getElementById("form-generar-turnos-" + userId).submit();
                });
            } else {
                // Si el turno no es diurno, simplemente envía el formulario sin preguntar
                document.getElementById("form-generar-turnos-" + userId).submit();
            }
        }
    </script>
</x-app-layout>
