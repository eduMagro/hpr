<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Maquina;
use App\Models\Turno;
use App\Models\Festivo;
use App\Models\Categoria;
use App\Models\AsignacionTurno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Obra;
use App\Models\Empresa;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Nomina;

class ProfileController extends Controller
{

    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('name')) {
            $filtros[] = 'Nombre: <strong>' . $request->name . '</strong>';
        }

        if ($request->filled('email')) {
            $filtros[] = 'Email: <strong>' . $request->email . '</strong>';
        }

        if ($request->filled('dni')) {
            $filtros[] = 'DNI: <strong>' . $request->dni . '</strong>';
        }

        if ($request->filled('empresa')) {
            $filtros[] = 'Empresa: <strong>' . $request->empresa . '</strong>';
        }

        if ($request->filled('rol')) {
            $filtros[] = 'Rol: <strong>' . $request->rol . '</strong>';
        }

        if ($request->filled('categoria_id')) {
            $nombreCategoria = Categoria::find($request->categoria_id)?->nombre ?? 'Desconocida';
            $filtros[] = 'Categoría: <strong>' . $nombreCategoria . '</strong>';
        }

        if ($request->filled('maquina_id')) {
            $maquina = Maquina::find($request->maquina_id);
            $filtros[] = 'Máquina: <strong>' . ($maquina->nombre ?? 'ID ' . $request->maquina_id) . '</strong>';
        }


        if ($request->filled('turno')) {
            $filtros[] = 'Turno: <strong>' . $request->turno . '</strong>';
        }

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst($request->estado) . '</strong>';
        }


        if ($request->filled('sort')) {
            $sorts = [
                'nombre' => 'Nombre',
                'email' => 'Email',
                'dni' => 'DNI',
                'empresa' => 'Empresa',
                'rol' => 'Rol',
                'categoria' => 'Categoría',
                'maquina' => 'Máquina',
                'turno' => 'Turno',
                'estado' => 'Estado',
            ];
            $orden = $request->order == 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . ($sorts[$request->sort] ?? $request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . $request->per_page . '</strong> registros por página';
        }

        return $filtros;
    }
    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down')
            : 'fas fa-sort';

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="text-white text-decoration-none">' .
            $titulo . ' <i class="' . $icon . '"></i></a>';
    }

    public function aplicarFiltros(Request $request)
    {
        // Iniciar la consulta de usuarios
        $query = User::query()->select('users.*');

        // Aplicar filtros si están presentes en la solicitud
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }
        // Filtrar por nombre
        if ($request->filled('name')) {
            $query->where('users.name', 'like', '%' . $request->input('name') . '%');
        }

        // Filtrar por email
        if ($request->filled('email')) {
            $query->where('users.email', 'like', '%' . $request->input('email') . '%');
        }
        // Filtrar por empresa
        if ($request->filled('empresa')) {
            $query->where('users.empresa', 'like', '%' . $request->input('empresa') . '%');
        }

        // Filtrar por rol
        if ($request->filled('rol')) {
            $query->where('users.rol', $request->input('rol'));
        }

        // Filtrar por máquina (maquina_id)
        if ($request->filled('maquina_id')) {
            $query->where('maquina_id', $request->input('maquina_id'));
        }



        // Obtener la fecha de hoy
        $hoy = Carbon::today()->toDateString();
        // Filtrar por turno de hoy si se selecciona un turno
        // if ($request->filled('turno')) {
        //     $query->whereHas('asignacionesTurnos', function ($q) use ($request, $hoy) {
        //         $q->where('fecha', $hoy)->whereHas('turno', function ($t) use ($request) {
        //             $t->where('nombre', $request->input('turno'));
        //         });
        //     });
        // }
        if ($request->filled('turno')) {
            $query->where('users.turno', $request->input('turno'));
        }

        // Ordenar resultados
        $sortBy = $request->input('sort_by', 'users.created_at');
        $order = $request->input('order', 'desc');
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        return $query;
    }

    public function index(Request $request)
    {
        $usuariosConectados = DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count();
        $obras = Obra::where('estado', 'activa')->get();
        $categorias = Categoria::orderBy('nombre')->get();
        $empresas = Empresa::orderBy('nombre')->get();
        $maquinas = Maquina::orderBy('nombre')->get(); // Puedes usar 'codigo' si prefieres
        $roles = User::distinct()->pluck('rol')->filter()->sort();
        $turnos = User::distinct()->pluck('turno')->filter()->sort();

        $ordenables = [
            'id' => $this->getOrdenamiento('id', 'ID'),
            'name' => $this->getOrdenamiento('name', 'Nombre'),
            'email' => $this->getOrdenamiento('email', 'Email'),
            'dni' => $this->getOrdenamiento('dni', 'DNI'),
            'empresa' => $this->getOrdenamiento('empresa', 'Empresa'),
            'rol' => $this->getOrdenamiento('rol', 'Rol'),
            'categoria' => $this->getOrdenamiento('categoria', 'Categoría'),
            'maquina_id' => $this->getOrdenamiento('maquina_id', 'Máquina'),
            'turno' => $this->getOrdenamiento('turno', 'Turno'),
            'estado' => $this->getOrdenamiento('estado', 'Estado'),
        ];

        $hoy = Carbon::today()->toDateString();
        $turnosHoy = AsignacionTurno::where('fecha', $hoy)
            ->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->pluck('turnos.nombre')->unique()->sort();

        // Obtener usuarios según filtros (sin paginar aún)
        $usuarios = $this->aplicarFiltros($request)->with('categoria', 'empresa', 'maquina')->get();

        // ✅ Filtrado por "estado de conexión" en colección
        if ($request->filled('estado')) {
            if ($request->estado === 'activo') {
                $usuarios = $usuarios->filter(fn($u) => $u->isOnline());
            } elseif ($request->estado === 'inactivo') {
                $usuarios = $usuarios->filter(fn($u) => !$u->isOnline());
            }
        }

        // ✅ Paginar manualmente la colección
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;
        $registrosUsuarios = new LengthAwarePaginator(
            $usuarios->slice($offset, $perPage)->values(),
            $usuarios->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $filtrosActivos = $this->filtrosActivos($request);
        $user = auth()->user();
        $coloresTurnos = $this->getColoresTurnos();

        $eventosFichajes = $this->getEventosFichajes($user);
        $eventosTurnos = $this->getEventosTurnos($user);
        $festivos = Festivo::select('fecha', 'titulo')->get()->map(function ($festivo) {
            return [
                'title' => $festivo->titulo,
                'start' => $festivo->fecha,
                'backgroundColor' => '#ff2800',
                'borderColor' => '#b22222',
                'textColor' => 'white',
                'allDay' => true,
                'editable' => true
            ];
        })->toArray();

        $eventos = array_merge($eventosFichajes->toArray(), $eventosTurnos->toArray(), $festivos);

        return view('User.index', compact(
            'registrosUsuarios',
            'usuariosConectados',
            'obras',
            'user',
            'empresas',
            'eventos',
            'coloresTurnos',
            'categorias',
            'maquinas',
            'roles',
            'turnos',
            'turnosHoy',
            'filtrosActivos',
            'ordenables'
        ));
    }

    public function show($id)
    {
        $user = User::with(['asignacionesTurnos.turno'])->findOrFail($id);

        // Fecha de inicio (1 de enero del año actual)
        $inicioAño = Carbon::now()->startOfYear();

        // **Calculamos ciertas variables**
        $faltasInjustificadas = $user->asignacionesTurnos->where('turno.nombre', 'falta_injustificada')
            ->where('fecha', '>=', $inicioAño)->count();

        $faltasJustificadas = $user->asignacionesTurnos->where('turno.nombre', 'falta_justificada')
            ->where('fecha', '>=', $inicioAño)->count();

        $diasBaja = $user->asignacionesTurnos->where('turno.nombre', 'baja')
            ->where('fecha', '>=', $inicioAño)->count();

        // **Obtener todos los turnos de la base de datos**
        $turnos = Turno::all();
        $coloresTurnos = $this->getColoresTurnos();

        // **Eventos de fichajes (entradas y salidas)**
        $eventosFichajes = $this->getEventosFichajes($user);
        // dd($eventosFichajes);
        $eventosTurnos = $this->getEventosTurnos($user);

        // **Obtener festivos reutilizando el método**
        $festivos = $this->getFestivos();

        // **Combinar eventos (Fichajes, Turnos y Festivos)**
        $eventos = $eventosFichajes->merge($eventosTurnos)->merge($festivos);

        return view('User.show', compact(
            'user',
            'eventos',
            'coloresTurnos',
            'turnos',
            'faltasInjustificadas',
            'faltasJustificadas',
            'diasBaja'
        ));
    }

    protected function getColoresTurnos()
    {
        // Definir colores base para cada tipo de turno
        $coloresBase = [
            'mañana' => [
                'bg' => '#008000',
                'border' => $this->darkenColor('#008000'),
                'text' => '#FFFFFF'
            ],
            'tarde' => [
                'bg' => '#0000FF',
                'border' => $this->darkenColor('#0000FF'),
                'text' => '#FFFFFF'
            ],
            'noche' => [
                'bg' => '#FFFF00',
                'border' => $this->darkenColor('#FFFF00'),
                'text' => '#000000'
            ],
            'baja' => [
                'bg' => '#D3D3D3',
                'border' => $this->darkenColor('#D3D3D3'),
                'text' => '#000000'
            ],
            'vacaciones' => [
                'bg' => '#FFC0CB', // Rosa claro
                'border' => $this->darkenColor('#FFC0CB'),
                'text' => '#000000' // Texto negro para mejor visibilidad
            ],

            'falta_justificada' => [
                'bg' => '#808080',
                'border' => $this->darkenColor('#808080'),
                'text' => '#FFFFFF'
            ],
            'falta_injustificada' => [
                'bg' => '#000000',
                'border' => $this->darkenColor('#000000'),
                'text' => '#FFFFFF'
            ],
            'festivo' => [
                'bg' => '#ff0000',
                'border' => '#b91c1c',
                'text' => '#FFFFFF'
            ],
        ];

        // Obtener turnos desde la base de datos y asignar colores
        $turnos = Turno::all();

        $coloresAsignados = $turnos->mapWithKeys(function ($turno) use ($coloresBase) {
            return [
                $turno->nombre => $coloresBase[$turno->nombre] ?? [
                    'bg' => '#708090', // Gris oscuro si el turno no está en la lista base
                    'border' => $this->darkenColor('#708090'),
                    'text' => '#FFFFFF'
                ]
            ];
        });

        return $coloresAsignados;
    }
    protected function getEventosTurnos($user)
    {
        $coloresTurnos = $this->getColoresTurnos(); // Obtener colores predefinidos

        return $user->asignacionesTurnos->map(function ($asignacion) use ($coloresTurnos) {
            $color = $coloresTurnos[$asignacion->turno->nombre] ?? [
                'bg' => '#808080', // Gris por defecto si no está en la lista
                'border' => '#606060',
                'text' => '#FFFFFF'
            ];

            return [
                'title' => ucfirst($asignacion->turno->nombre),
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => $color['bg'],
                'borderColor' => $color['border'],
                'textColor' => $color['text'], // Usar el color de texto asignado
                'allDay' => true
            ];
        });
    }
    protected function getEventosFichajes($user)
    {
        return $user->asignacionesTurnos->flatMap(function ($asignacion) {
            $eventos = [];

            if ($asignacion->entrada) {
                $eventos[] = [
                    'title' => 'Entrada',
                    'start' => Carbon::parse($asignacion->entrada)->toIso8601String(),
                    'color' => '#28a745', // Verde
                    'textColor' => '#ffffff',
                    'allDay' => false
                ];
            }

            if ($asignacion->salida) {
                $eventos[] = [
                    'title' => 'Salida',
                    'start' => Carbon::parse($asignacion->salida)->toIso8601String(),
                    'color' => '#dc3545', // Rojo
                    'textColor' => '#ffffff',
                    'allDay' => false
                ];
            }

            return $eventos;
        });
    }

    /**
     * Función para oscurecer un color en hexadecimal.
     */
    private function darkenColor($hex, $percent = 20)
    {
        $hex = str_replace("#", "", $hex);
        $rgb = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];

        foreach ($rgb as &$value) {
            $value = max(0, min(255, $value - ($value * ($percent / 100))));
        }

        return sprintf("#%02X%02X%02X", $rgb[0], $rgb[1], $rgb[2]);
    }
    /**
     * Display the user's profile form.
     */
    public function edit($id)
    {
        // Obtener el usuario autenticado
        $authUser = auth()->user();

        // Verificar si el usuario autenticado es administrador
        if ($authUser->rol !== 'oficina') {
            return redirect()->route('dashboard')->with('abort', 'No tienes permiso para editar perfiles.');
        }

        // Buscar el usuario que se quiere editar
        $user = User::findOrFail($id);

        return view('profile.edit', compact('user'));
    }
    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, $id): RedirectResponse
    {
        // Obtener el usuario autenticado
        $authUser = auth()->user();

        // Verificar si el usuario autenticado es administrador
        if ($authUser->rol !== 'oficina') {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para actualizar perfiles.');
        }

        // Buscar el usuario que se quiere actualizar
        $user = User::findOrFail($id);
        $user->fill($request->validated());

        if ($request->filled('email') && $user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->filled('categoria')) {
            $user->categoria = $request->input('categoria');
        }

        $user->save();

        return Redirect::route('profile.edit', ['id' => $id])->with('status', 'profile-updated');
    }
    public function actualizarUsuario(Request $request, $id)
    {

        try {
            // Validar los datos con mensajes personalizados
            $request->validate([
                'name' => 'required|string|max:50',
                'email' => 'required|email|max:255|unique:users,email,' . $id,
                'dni' => [
                    'nullable',
                    'string',
                    'size:9',
                    'regex:/^([0-9]{8}[A-Z]|[XYZ][0-9]{7}[A-Z])$/',
                    'unique:users,dni,' . $id,
                ],
                'empresa_id' => 'nullable|exists:empresas,id',
                'rol' => 'required|string|max:50',
                'categoria_id' => 'nullable|exists:categorias,id',
                'maquina_id' => 'nullable|exists:maquinas,id',
                'turno' => 'nullable|string|in:nocturno,diurno,mañana,flexible',
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'name.string' => 'El nombre debe ser un texto válido.',
                'name.max' => 'El nombre no puede superar los 50 caracteres.',

                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Debe ingresar un correo electrónico válido.',
                'email.max' => 'El correo no puede superar los 50 caracteres.',
                'email.unique' => 'Este correo ya está registrado en otro usuario.',

                'empresa_id.exists' => 'La empresa seleccionada no existe.',

                'rol.required' => 'El rol es obligatorio.',
                'rol.string' => 'El rol debe ser un texto válido.',
                'rol.max' => 'El rol no puede superar los 50 caracteres.',

                'categoria_id.exists' => 'La categoría no existe.',

                'turno.string' => 'El turno debe ser un texto válido.',
                'turno.in' => 'El turno debe ser "mañana", "tarde", "noche" o "flexible".',
            ]);

            // Buscar el usuario
            $usuario = User::find($id);
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            $resultado = $usuario->update([
                'name' => $request->name,
                'email' => $request->email,
                'dni' => $request->dni,
                'empresa_id' => $request->empresa_id,
                'rol' => $request->rol,
                'categoria_id' => $request->categoria_id,
                'maquina_id' => $request->maquina_id,
                'turno' => $request->turno,
            ]);

            if (!$resultado) {
                return response()->json(['error' => 'No se pudo actualizar el usuario.'], 500);
            }
            // Actualizar asignaciones_turno desde hoy hasta fin de año
            AsignacionTurno::where('user_id', $usuario->id)
                ->whereDate('fecha', '>=', Carbon::today())
                ->whereDate('fecha', '<=', Carbon::createFromDate(null, 12, 31))
                ->where('turno_id', '!=', 10)
                ->update(['maquina_id' => $usuario->maquina_id]);


            return response()->json(['success' => 'Usuario actualizado correctamente.']);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }

    public function generarTurnos(User $user)
    {
        // Obtener los IDs de los turnos
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId = Turno::where('nombre', 'noche')->value('id');
        $turnoVacacionesId = Turno::where('nombre', 'vacaciones')->value('id'); // Obtener dinámicamente el ID de vacaciones

        // Definir el inicio y fin del año actual
        $inicio = Carbon::now()->addDay()->startOfDay();
        $fin = Carbon::now()->endOfYear();

        $festivos = Festivo::select('fecha', 'titulo')->get()->map(function ($festivo) {
            return [
                'title' => $festivo->titulo,
                'start' => $festivo->fecha,
                'backgroundColor' => '#ff2800', // Rojo Ferrari
                'borderColor' => '#b22222',
                'textColor' => 'white',
                'allDay' => true,
                'editable' => true // ✅ Esto permite moverlo
            ];
        })->toArray();

        $festivosArray = collect($festivos)->pluck('start')->toArray();
        // Obtener los días donde ya hay una asignación de vacaciones (turno_id = 10)
        $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
            ->where('turno_id', $turnoVacacionesId)
            ->pluck('fecha')
            ->toArray();

        // Determinar el turno inicial según el tipo de turno del usuario
        if ($user->turno == 'diurno') {
            $turnoInicial = request()->input('turno_inicio');
            if (!in_array($turnoInicial, ['mañana', 'tarde'])) {
                return redirect()->back()->with('error', 'Debe seleccionar un turno válido para comenzar (mañana o tarde).');
            }
            $turnoAsignado = ($turnoInicial == 'mañana') ? $turnoMañanaId : $turnoTardeId;
        } elseif ($user->turno == 'nocturno') {
            $turnoAsignado = $turnoNocheId;
        } elseif ($user->turno == 'mañana') {
            $turnoAsignado = $turnoMañanaId;
        } else {
            return redirect()->back()->with('error', 'El usuario no tiene un turno asignado.');
        }


        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
                // Guardar el turno del viernes antes de saltarlo si es festivo o vacaciones
                $esViernes = $fecha->dayOfWeek == Carbon::FRIDAY;

                // Excluir sábados, domingos, festivos y días con turno de vacaciones
                if (
                    in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) ||
                    in_array($fecha->toDateString(), $festivosArray) ||
                    in_array($fecha->toDateString(), $diasVacaciones)
                ) {
                    // Si es viernes, cambiar turno aunque no se registre en la base de datos
                    if ($user->turno == 'diurno' && $esViernes) {
                        $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                    }
                    continue;
                }

                // Registrar el turno en la base de datos
                AsignacionTurno::updateOrCreate(
                    ['user_id' => $user->id, 'fecha' => $fecha->toDateString()],
                    [
                        'turno_id' => $turnoAsignado,
                        'maquina_id' => $user->maquina_id, // o $user->maquina_id si lo renombraste así
                    ]
                );

                // Si es viernes, cambiar turno para la próxima semana
                if ($user->turno == 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
            }
        }

        return redirect()->back()->with('success', "Turnos generados correctamente para {$user->name}, excluyendo los festivos.");
    }


    public function destroy(Request $request, $id)
    {
        try {

            // Buscar el usuario a eliminar
            $user = User::findOrFail($id);

            // Validar la contraseña del administrador
            if (!Hash::check($request->password, $user->password)) {
                return back()->withErrors(['userDeletion.password' => 'La contraseña proporcionada es incorrecta.']);
            }

            // Eliminar usuario
            $user->delete();

            return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('error', 'Error al eliminar el usuario: ' . $e->getMessage());
        }
    }

    private function getFestivos()
    {
        $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/" . date('Y') . "/ES");

        if ($response->failed()) {
            return []; // Si la API falla, devolvemos un array vacío
        }

        $festivos = collect($response->json())->filter(function ($holiday) {
            // Si no tiene 'counties', es un festivo NACIONAL
            if (!isset($holiday['counties'])) {
                return true;
            }
            // Si el festivo pertenece a Andalucía
            return in_array('ES-AN', $holiday['counties']);
        })->map(function ($holiday) {
            return [
                'title' => $holiday['localName'], // Nombre del festivo
                'start' => Carbon::parse($holiday['date'])->toDateString(), // Fecha formateada correctamente
                'backgroundColor' => '#ff0000', // Rojo para festivos
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'allDay' => true
            ];
        });

        // Añadir festivos locales de Los Palacios y Villafranca
        $festivosLocales = collect([
            [
                'title' => 'Festividad de Nuestra Señora de las Nieves',
                'start' => date('Y') . '-08-05',
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'allDay' => true
            ],
            [
                'title' => 'Feria Los Palacios y Vfca',
                'start' => date('Y') . '-09-25',
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'allDay' => true
            ]
        ]);

        // Combinar festivos nacionales, autonómicos y locales
        return $festivos->merge($festivosLocales)->values()->toArray();
    }
}
