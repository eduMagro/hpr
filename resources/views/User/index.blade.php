<x-app-layout>
    <x-slot name="title">Usuarios - {{ config('app.name') }}</x-slot>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <x-menu.usuarios :totalSolicitudesPendientes="$totalSolicitudesPendientes ?? 0" />
    @if (Auth::check() && Auth::user()->rol == 'oficina')
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <script>
            const obrasHierrosPacoReyes = @json($obrasHierrosPacoReyes);
        </script>
        <!-- TABLA DE USUARIOS -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg mt-4">
            <table class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border">{!! $ordenables['id'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['nombre_completo'] !!}</th>
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
                            <th class="p-1 border">
                                <x-tabla.input name="id" :value="request('id')" />
                            </th> <!-- ID: sin filtro directo -->
                            <th class="p-1 border">
                                <x-tabla.input name="nombre_completo" :value="request('nombre_completo')" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.input name="email" :value="request('email')" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.input name="movil_personal" :value="request('movil_personal')" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.input name="movil_empresa" :value="request('movil_empresa')" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.input name="dni" :value="request('dni')" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.select name="empresa_id" :options="$empresas->pluck('nombre', 'id')" :selected="request('empresa_id')" empty="Todas" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.select name="rol" :options="collect($roles)->mapWithKeys(fn($r) => [$r => ucfirst($r)])" :selected="request('rol')" empty="Todos" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.select name="categoria_id" :options="$categorias->pluck('nombre', 'id')" :selected="request('categoria_id')"
                                    empty="Todas" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.select name="maquina_id" :options="$maquinas->pluck('nombre', 'id')" :selected="request('maquina')"
                                    empty="Todas" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.select name="turno" :options="collect($turnos)->mapWithKeys(fn($t) => [$t => ucfirst($t)])" :selected="request('turno')" empty="Todos" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.select name="estado" :options="['activo' => 'Activo', 'inactivo' => 'Inactivo']" :selected="request('estado')" empty="Todos" />
                            </th>

                            <th class="p-1 border"></th>

                            <x-tabla.botones-filtro ruta="users.index" rutaExportar="usuarios.exportar" />

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
                                    <span
                                        x-text="`${usuario.name} ${usuario.primer_apellido} ${usuario.segundo_apellido}`"></span>
                                </template>

                                <template x-if="editando">
                                    <div class="flex flex-row gap-1">
                                        <x-tabla.input x-model="usuario.name" placeholder="Nombre" />
                                        <x-tabla.input x-model="usuario.primer_apellido" placeholder="Apellido 1" />
                                        <x-tabla.input x-model="usuario.segundo_apellido" placeholder="Apellido 2"
                                            @keydown.enter.stop="guardarCambios(usuario)" />
                                    </div>
                                </template>
                            </td>

                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.email"></span>
                                </template>
                                <x-tabla.input x-show="editando" x-model="usuario.email"
                                    @keydown.enter.stop="guardarCambios(usuario)" />
                            </td>

                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.movil_personal"></span>
                                </template>
                                <x-tabla.input x-show="editando" x-model="usuario.movil_personal"
                                    @keydown.enter.stop="guardarCambios(usuario)" />
                            </td>

                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.movil_empresa"></span>
                                </template>
                                <x-tabla.input x-show="editando" x-model="usuario.movil_empresa"
                                    @keydown.enter.stop="guardarCambios(usuario)" />
                            </td>

                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.dni"></span>
                                </template>
                                <x-tabla.input x-show="editando" x-model="usuario.dni"
                                    @keydown.enter.stop="guardarCambios(usuario)" />
                            </td>

                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.empresa?.nombre ?? 'Sin empresa'"></span>
                                </template>
                                <x-tabla.select-edicion x-show="editando" x-model="usuario.empresa_id"
                                    @keydown.enter.stop="guardarCambios(usuario)">
                                    <option value="">Selecciona empresa</option>
                                    @foreach ($empresas as $empresa)
                                        <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                                    @endforeach
                                </x-tabla.select-edicion>
                            </td>

                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.rol"></span>
                                </template>
                                <x-tabla.select-edicion x-show="editando" x-model="usuario.rol">
                                    <option value="">Selecciona rol</option>
                                    <option value="oficina">Oficina</option>
                                    <option value="operario">Operario</option>
                                    <option value="visitante">Visitante</option>
                                </x-tabla.select-edicion>
                            </td>

                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.categoria?.nombre ?? 'Sin asignar'"></span>
                                </template>
                                <x-tabla.select-edicion x-show="editando" x-model="usuario.categoria_id">
                                    <option value="">Selecciona cat.</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}">{{ ucfirst($categoria->nombre) }}
                                        </option>
                                    @endforeach
                                </x-tabla.select-edicion>
                            </td>

                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="usuario.maquina?.nombre ?? 'Sin asignar'"></span>
                                </template>
                                <x-tabla.select-edicion x-show="editando" x-model="usuario.maquina_id">
                                    <option value="">Selecciona máq.</option>
                                    @foreach ($maquinas as $maquina)
                                        <option value="{{ $maquina->id }}">{{ $maquina->nombre ?? 'N/A' }}</option>
                                    @endforeach
                                </x-tabla.select-edicion>
                            </td>

                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="usuario.turno ? usuario.turno.charAt(0).toUpperCase() + usuario.turno.slice(1) : 'N/A'"></span>
                                </template>
                                <x-tabla.select-edicion x-show="editando" x-model="usuario.turno">
                                    <option value="">Selecciona turno</option>
                                    <option value="nocturno">Nocturno</option>
                                    <option value="diurno">Diurno</option>
                                    <option value="mañana">Mañana</option>
                                </x-tabla.select-edicion>
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
                                    <input type="hidden" id="obra_id_input_{{ $user->id }}" name="obra_id">

                                    <button type="button"
                                        class="w-full bg-gray-500 hover:bg-gray-600 text-white text-xs px-2 py-1 rounded"
                                        onclick="confirmarGenerarTurnos({{ $user->id }}, obrasHierrosPacoReyes)">
                                        Turnos
                                    </button>
                                </form>
                            </td>

                            <td class="px-2 py-2 border text-xs font-bold">
                                <div class="flex items-center space-x-2 justify-center">
                                    <!-- Mostrar solo en modo edición -->
                                    <x-tabla.boton-guardar x-show="editando"
                                        @click="guardarCambios(usuario); editando = false" />
                                    <x-tabla.boton-cancelar-edicion @click="editando = false" x-show="editando" />

                                    <!-- Mostrar solo cuando NO está en modo edición -->
                                    <template x-if="!editando">
                                        <div class="flex items-center space-x-2">
                                            <x-tabla.boton-editar @click="editando = true" x-show="!editando" />
                                            <x-tabla.boton-ver :href="route('users.show', $user->id)" target="_blank"
                                                rel="noopener noreferrer" />

                                            <a href="{{ route('users.edit', $user->id) }}" title="Configuración"
                                                class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    viewBox="0 0 24 24" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M11.983 2c.529 0 .96.388 1.025.912l.118.998a7.97 7.97 0 0 1 1.575.645l.892-.516a1.033 1.033 0 0 1 1.4.375l.503.87a1.03 1.03 0 0 1-.208 1.286l-.76.625c.063.32.104.648.123.982l.994.168a1.032 1.032 0 0 1 .873 1.017v1.003a1.032 1.032 0 0 1-.873 1.017l-.994.168a8.114 8.114 0 0 1-.123.982l.76.625c.361.296.463.808.208 1.286l-.503.87a1.033 1.033 0 0 1-1.4.375l-.892-.516a7.968 7.968 0 0 1-1.575.645l-.118.998a1.032 1.032 0 0 1-1.025.912h-1.002a1.032 1.032 0 0 1-1.025-.912l-.118-.998a7.97 7.97 0 0 1-1.575-.645l-.892.516a1.033 1.033 0 0 1-1.4-.375l-.503-.87a1.03 1.03 0 0 1 .208-1.286l.76-.625a8.114 8.114 0 0 1-.123-.982l-.994-.168a1.032 1.032 0 0 1-.873-1.017v-1.003a1.032 1.032 0 0 1 .873-1.017l.994-.168c.019-.334.06-.662.123-.982l-.76-.625a1.03 1.03 0 0 1-.208-1.286l.503-.87a1.033 1.033 0 0 1 1.4-.375l.892.516c.494-.29 1.02-.52 1.575-.645l.118-.998A1.032 1.032 0 0 1 10.981 2h1.002zm-1.232 10a2.25 2.25 0 1 0 4.5 0 2.25 2.25 0 0 0-4.5 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </a>

                                        </div>
                                    </template>
                                </div>
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
        <x-tabla.paginacion :paginador="$registrosUsuarios" />
    @else
        {{-- ------------------------------- FICHAJE MODO OPERARIO -------------------------------- --}}

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

            <div class="bg-white rounded-2xl shadow-lg p-6 max-w-4xl mx-auto mb-8 border border-gray-200">
                <!-- Encabezado compacto -->
                <div class="flex gap-6 border-b pb-6 items-center">
                    <!-- Avatar con botón de cambiar foto -->
                    <div class="relative flex-shrink-0 mx-auto md:mx-0">
                        @if (auth()->user()->ruta_imagen)
                            <img src="{{ auth()->user()->ruta_imagen }}" alt="Foto de perfil"
                                class="w-24 h-24 rounded-full object-cover ring-4 ring-blue-500 shadow-lg">
                        @else
                            <div
                                class="w-24 h-24 bg-gradient-to-br from-gray-300 to-gray-400 rounded-full flex items-center justify-center text-3xl font-bold text-gray-700 shadow-inner ring-4 ring-blue-500">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif

                        <!-- Botón cambiar foto sobre la imagen -->
                        <form method="POST" action="{{ route('usuario.subirImagen') }}"
                            enctype="multipart/form-data" class="absolute bottom-0 right-0">
                            @csrf
                            <label
                                class="flex items-center justify-center bg-white border border-gray-300 rounded-full p-1 shadow-md cursor-pointer hover:bg-gray-50">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M4 3a2 2 0 00-2 2v3.586A1.5 1.5 0 003.5 10H4v6a2 2 0 002 2h8a2 2 0 002-2v-6h.5A1.5 1.5 0 0018 8.586V5a2 2 0 00-2-2H4zm3 3a1 1 0 112 0 1 1 0 01-2 0zm2 4a2 2 0 114 0 2 2 0 01-4 0z" />
                                </svg>
                                <input type="file" name="imagen" accept="image/*" class="hidden"
                                    onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>

                    <!-- Datos principales -->
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight">
                            {{ auth()->user()->nombre_completo }}
                        </h2>
                        <p class="text-sm text-gray-500 mb-2">Ficha del trabajador</p>

                    </div>
                </div>

                <!-- Contenido en dos columnas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Información del usuario -->
                    <div>

                        <div>📧 <span class="text-gray-700">{{ auth()->user()->email }}</span></div>
                        <div>📞 <span class="text-gray-700">{{ auth()->user()->telefono ?? 'Sin teléfono' }}</span>
                        </div>
                        <p><strong>Categoría:</strong> <span
                                class="text-gray-600">{{ $user->categoria->nombre ?? 'N/A' }}</span></p>
                        <p><strong>Especialidad:</strong> <span
                                class="text-gray-600">{{ $user->maquina->nombre ?? 'N/A' }}</span></p>
                    </div>

                    <!-- Resumen de asistencias -->
                    <div>

                        <div class="bg-gray-100 p-3 rounded-lg mb-4">
                            <p><strong>Vacaciones asignadas:</strong> {{ $resumen['diasVacaciones'] }}</p>
                            <p><strong>Faltas injustificadas:</strong> {{ $resumen['faltasInjustificadas'] }}</p>
                            <p><strong>Faltas justificadas:</strong> {{ $resumen['faltasJustificadas'] }}</p>
                            <p><strong>Días de baja:</strong> {{ $resumen['diasBaja'] }}</p>
                        </div>

                        <!-- Resumen de horas -->
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">⏱️ Horas trabajadas (mes actual)</h3>
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <p><strong>Horas trabajadas:</strong>
                                {{ number_format($horasMensuales['horas_trabajadas'], 2, ',', '.') }} h
                            </p>
                            <p><strong>Horas que debería llevar hasta hoy:</strong>
                                {{ number_format($horasMensuales['horas_deberia_llevar'], 2, ',', '.') }} h
                            </p>
                            <p><strong>Horas planificadas del mes:</strong>
                                {{ number_format($horasMensuales['horas_planificadas_mes'], 2, ',', '.') }} h
                            </p>
                            <p class="text-red-600 font-semibold">
                                ⚠️ Días con errores de fichaje: {{ $horasMensuales['dias_con_errores'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Descargar nóminas -->
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">📥 Descargar mis nóminas</h3>
                    <form action="{{ route('nominas.descargarMes') }}" method="GET"
                        class="flex flex-wrap items-center gap-3 max-w-md">
                        @csrf
                        <input type="month" name="mes_anio" id="mes_anio" required
                            class="w-auto px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 
           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
           hover:border-gray-400 transition-colors duration-200" />

                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                            📥
                        </button>
                    </form>
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

                    // 🔹 Obtener eventos actuales cargados en el calendario
                    const eventosActuales = calendar.getEvents();
                    const fechasBloqueadas = eventosActuales
                        .filter(e => e.title === 'V. pendiente' || e.title === 'V. denegadas' || e
                            .title === 'Vacaciones')
                        .map(e => moment(e.start).format('YYYY-MM-DD'));

                    // 🔹 Buscar conflictos
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
                events: '{{ route('users.eventos-turnos', $user->id) }}'
            });

            calendar.render();
        });
        // ---------------------------------------------------- REGISTRAR FICHAJE
        function registrarFichaje(tipo) {
            const boton = event.currentTarget;
            const textoOriginal = boton.querySelector('.texto').textContent;

            boton.disabled = true;
            boton.querySelector('.texto').textContent = 'Obteniendo ubicación…';
            boton.classList.add('opacity-50', 'cursor-not-allowed');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const latitud = position.coords.latitude;
                    const longitud = position.coords.longitude;

                    // Ya tenemos coordenadas rápidas
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
                            fetch("{{ url('/fichar') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },
                                    body: JSON.stringify({
                                        user_id: "{{ auth()->id() }}",
                                        tipo: tipo,
                                        latitud: latitud,
                                        longitud: longitud,
                                    })
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: data.success,
                                            text: `📍 Lugar: ${data.obra_nombre}`,
                                            showConfirmButton: false,
                                            timer: 3000
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: data.error
                                        });
                                    }
                                })
                                .catch(err => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'No se pudo comunicar con el servidor'
                                    });
                                });
                        }
                    });

                    boton.disabled = false;
                    boton.querySelector('.texto').textContent = textoOriginal;
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                },
                function(error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de ubicación',
                        text: `${error.message}`
                    });
                    boton.disabled = false;
                    boton.querySelector('.texto').textContent = textoOriginal;
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                }, {
                    enableHighAccuracy: false, // 💡 más rápido
                    timeout: 8000, // 💡 máximo 8 segundos
                    maximumAge: 60000 // 💡 usar cache si tiene <1 min
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
        function confirmarGenerarTurnos(userId, obras) {
            let usuarioTurno = document.getElementById("usuario_turno_" + userId).value;

            // Generar HTML del select con las obras
            let opcionesObra = obras.map(
                (obra) => `<option value="${obra.id}">${obra.obra}</option>`
            ).join("");

            let selectHtml = `
        <label for="select-obra">Selecciona la obra asignada:</label>
        <select id="select-obra" class="swal2-select" style="margin-top: 1em;">
            ${opcionesObra}
        </select>
    `;

            Swal.fire({
                title: "¿Estás seguro?",
                html: `
            <p class="mb-2">⚠️ Esta acción generará turnos hasta final de año y reemplazará los actuales (excepto vacaciones y festivos).</p>
            ${selectHtml}
        `,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, continuar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                preConfirm: () => {
                    const obraId = document.getElementById("select-obra").value;
                    if (!obraId) {
                        Swal.showValidationMessage("Debes seleccionar una obra");
                    }
                    return obraId;
                }
            }).then((respuestaConfirmacion) => {
                if (!respuestaConfirmacion.isConfirmed) return;

                const obraId = respuestaConfirmacion.value;
                document.getElementById("obra_id_input_" + userId).value = obraId;

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
                        document.getElementById("turno_inicio_" + userId).value =
                            result.isConfirmed ? "mañana" : "tarde";

                        document.getElementById("form-generar-turnos-" + userId).submit();
                    });
                } else {
                    document.getElementById("form-generar-turnos-" + userId).submit();
                }
            });
        }
    </script>
</x-app-layout>
