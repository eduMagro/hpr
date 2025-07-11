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
use App\Models\VacacionesSolicitud;
use App\Models\Categoria;
use App\Models\AsignacionTurno;
use App\Models\Movimiento;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Obra;
use App\Models\Empresa;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Session;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session as FacadeSession;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class ProfileController extends Controller
{
    public function exportarUsuarios()
    {
        return Excel::download(new UsersExport, 'usuarios.xlsx');
    }
    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('nombre_completo')) {
            $filtros[] = 'Nombre: <strong>' . $request->nombre_completo . '</strong>';
        }

        if ($request->filled('email')) {
            $filtros[] = 'Email: <strong>' . $request->email . '</strong>';
        }
        if ($request->filled('movil_personal')) {
            $filtros[] = 'Móvil Personal: <strong>' . $request->movil_personal . '</strong>';
        }
        if ($request->filled('movil_empresa')) {
            $filtros[] = 'Móvil Empresa: <strong>' . $request->movil_empresa . '</strong>';
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
                'nombre_completo' => 'Nombre',
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

        // Sanitiza la URL de ordenamiento
        $url = request()->fullUrlWithQuery([
            'sort' => $columna,
            'order' => $nextOrder
        ]);

        return '<a href="' . e($url) . '" class="text-white text-decoration-none">'
            . e($titulo) . ' <i class="' . $icon . '"></i></a>';
    }

    public function aplicarFiltros(Request $request)
    {
        $ordenables = [
            'id' => 'users.id',
            'nombre_completo' => DB::raw("CONCAT_WS(' ', name, primer_apellido, segundo_apellido)"),
            'email' => 'users.email',
            'dni' => 'users.dni',
            'empresa' => 'empresa_id',
            'rol' => 'users.rol',
            'categoria' => 'categoria_id',
            'maquina_id' => 'maquina_id',
            'turno' => 'users.turno',
            'estado' => 'estado',
        ];

        $query = User::query()->select('users.*');

        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        if ($request->filled('nombre_completo')) {
            $valor = $request->input('nombre_completo');
            $query->whereRaw("CONCAT_WS(' ', name, primer_apellido, segundo_apellido) LIKE ?", ["%{$valor}%"]);
        }

        if ($request->filled('email')) {
            $query->where('users.email', 'like', '%' . $request->input('email') . '%');
        }

        if ($request->filled('movil_personal')) {
            $query->where('users.movil_personal', 'like', '%' . $request->input('movil_personal') . '%');
        }

        if ($request->filled('movil_empresa')) {
            $query->where('users.movil_empresa', 'like', '%' . $request->input('movil_empresa') . '%');
        }

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('rol')) {
            $query->where('users.rol', $request->input('rol'));
        }

        if ($request->filled('maquina_id')) {
            $query->where('maquina_id', $request->input('maquina_id'));
        }

        if ($request->filled('turno')) {
            $query->where('users.turno', $request->input('turno'));
        }

        // Aplicar ordenamiento seguro
        $sort = $request->input('sort');
        $order = strtolower($request->input('order', 'asc'));

        if (isset($ordenables[$sort]) && in_array($order, ['asc', 'desc'])) {
            $query->orderBy($ordenables[$sort], $order);
        } else {
            $query->orderBy('users.created_at', 'desc');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $usuariosConectados = DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count();
        $obras = Obra::where('estado', 'activa')->get();
        $obrasHierrosPacoReyes = Obra::where('estado', 'activa')
            ->whereHas('cliente', function ($q) {
                $q->where('empresa', 'Hierros Paco Reyes');
            })
            ->select('id', 'obra')
            ->get();

        $categorias = Categoria::orderBy('nombre')->get();
        $empresas = Empresa::orderBy('nombre')->get();
        $maquinas = Maquina::orderBy('nombre')->get();
        $roles = User::distinct()->pluck('rol')->filter()->sort();
        $turnos = User::distinct()->pluck('turno')->filter()->sort();
        $totalSolicitudesPendientes = VacacionesSolicitud::where('estado', 'pendiente')->count();
        $user = auth()->user();

        $inicioAño = Carbon::now()->startOfYear();
        $diasVacaciones = $user->asignacionesTurnos
            ->where('estado', 'vacaciones')
            ->where('fecha', '>=', $inicioAño)
            ->count();

        // Obtener todos los nombres de turnos válidos (mañana, tarde, noche, festivo, etc.)
        $turnosValidos = Turno::pluck('nombre')->toArray();

        // Fecha de inicio (1 de enero del año actual)
        $inicioAño = Carbon::now()->startOfYear();

        $faltasInjustificadas = $user->asignacionesTurnos
            ->where('estado', 'falta_injustificada')
            ->where('fecha', '>=', $inicioAño)
            ->count();

        $faltasJustificadas = $user->asignacionesTurnos
            ->where('estado', 'falta_justificada')
            ->where('fecha', '>=', $inicioAño)
            ->count();

        $diasBaja = AsignacionTurno::where('user_id', $user->id)
            ->where('estado', 'baja')
            ->where('fecha', '>=', $inicioAño)
            ->count();

        $ordenables = [
            'id' => $this->getOrdenamiento('id', 'ID'),
            'nombre_completo' => $this->getOrdenamiento('nombre_completo', 'Nombre'),
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

        // Filtrado por "estado de conexión" en colección
        if ($request->filled('estado')) {
            if ($request->estado === 'activo') {
                $usuarios = $usuarios->filter(fn($u) => $u->isOnline());
            } elseif ($request->estado === 'inactivo') {
                $usuarios = $usuarios->filter(fn($u) => !$u->isOnline());
            }
        }

        // Paginar manualmente la colección
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

        $eventosFichajes = $this->getEventosFichajes($user);
        $coloresTurnos = $this->getColoresTurnosYEstado();

        $asignaciones = AsignacionTurno::where('user_id', $user->id)
            ->with('turno')
            ->get();

        $eventosAsignaciones = $asignaciones->flatMap(function ($asig) use ($coloresTurnos) {
            $eventos = [];

            // Turno (si existe)
            if ($asig->turno) {
                $nombre = $asig->turno->nombre;
                $color = $coloresTurnos[$nombre] ?? ['bg' => '#708090', 'border' => '#505d6e', 'text' => '#FFFFFF'];

                $eventos[] = [
                    'title' => ucfirst($nombre),
                    'start' => $asig->fecha,
                    'allDay' => true,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                ];
            }

            // Estado (si distinto de "activo")
            if ($asig->estado && strtolower($asig->estado) !== 'activo') {
                $estado = strtolower($asig->estado);
                $color = $coloresTurnos[$estado] ?? ['bg' => '#6b7280', 'border' => '#4b5563', 'text' => '#FFFFFF'];

                $eventos[] = [
                    'title' => ucfirst($estado),
                    'start' => $asig->fecha,
                    'allDay' => true,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                ];
            }

            return $eventos;
        });


        // Festivos comunes
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

        // Solicitudes pendientes y denegadas
        $solicitudesVacaciones = VacacionesSolicitud::where('user_id', $user->id)
            ->whereIn('estado', ['pendiente', 'denegada'])
            ->get()
            ->flatMap(function ($solicitud) {
                if ($solicitud->estado === 'pendiente') {
                    $color = '#fcdde8';
                    $textColor = 'black';
                    $title = 'V. pendiente';
                } else {
                    $color = '#000000';
                    $textColor = 'white';
                    $title = 'V. denegadas';
                }

                return collect(CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin)->toArray())
                    ->map(function ($fecha) use ($title, $color, $textColor) {
                        return [
                            'title' => $title,
                            'start' => $fecha->toDateString(),
                            'end' => $fecha->copy()->addDay()->toDateString(),
                            'allDay' => true,
                            'backgroundColor' => $color,
                            'borderColor' => $color,
                            'textColor' => $textColor,
                        ];
                    });
            })->values();

        // Merge final de eventos
        $eventos = array_merge(
            $eventosFichajes->toArray(),
            $eventosAsignaciones->toArray(),
            $festivos,
            $solicitudesVacaciones->toArray()
        );


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
            'ordenables',
            'faltasInjustificadas',
            'faltasJustificadas',
            'diasBaja',
            'totalSolicitudesPendientes',
            'diasVacaciones',
            'obrasHierrosPacoReyes'
        ));
    }

    public function show($id)
    {
        $user = User::with(['asignacionesTurnos.turno'])->findOrFail($id);
        $inicioAño = Carbon::now()->startOfYear();
        $turnos = Turno::all();

        $resumen = $this->getResumenAsistencia($user);

        return view('User.show', compact(
            'user',
            'turnos',
            'resumen'
        ));
    }

    protected function getColoresTurnosYEstado(): array
    {
        // Colores base para turnos
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
            ]
        ];

        // Estados manuales
        $estados = [
            'vacaciones' => [
                'bg' => '#f87171',
                'border' => '#dc2626',
                'text' => '#FFFFFF'
            ],
            'baja' => [
                'bg' => '#a855f7',
                'border' => '#9333ea',
                'text' => '#FFFFFF'
            ],
            'festivo' => [
                'bg' => '#ff0000', // Rojo para festivos
                'border' => '#b91c1c',
                'text' => 'white'
            ],
            'falta_justificada' => [
                'bg' => '#808080',
                'border' => '#4b5563',
                'text' => '#FFFFFF'
            ],
            'falta_injustificada' => [
                'bg' => '#000000',
                'border' => '#000000',
                'text' => '#FFFFFF'
            ]
        ];

        // Obtener todos los turnos desde la base de datos
        $turnos = Turno::all();

        // Asignar colores a los turnos registrados, con claves en minúsculas
        $turnosColoreados = $turnos->mapWithKeys(function ($turno) use ($coloresBase) {
            $clave = strtolower($turno->nombre);
            return [
                $clave => $coloresBase[$clave] ?? [
                    'bg' => '#708090',
                    'border' => $this->darkenColor('#708090'),
                    'text' => '#FFFFFF'
                ]
            ];
        });

        return array_merge($turnosColoreados->toArray(), $estados);
    }

    protected function getEventosTurnos($user)
    {
        $coloresTurnos = $this->getColoresTurnosYEstado();

        return $user->asignacionesTurnos->flatMap(function ($asignacion) use ($coloresTurnos) {
            $eventos = [];

            $fecha = Carbon::parse($asignacion->fecha)->toIso8601String();

            // 🎯 Evento de turno (si existe)
            if ($asignacion->turno) {
                $turnoNombre = $asignacion->turno->nombre;
                $colorTurno = $coloresTurnos[$turnoNombre] ?? [
                    'bg' => '#0275d8',
                    'border' => '#025aa5',
                    'text' => '#FFFFFF'
                ];

                $eventos[] = [
                    'title' => ucfirst($turnoNombre),
                    'start' => $fecha,
                    'backgroundColor' => $colorTurno['bg'],
                    'borderColor' => $colorTurno['border'],
                    'textColor' => $colorTurno['text'],
                    'allDay' => true,
                ];
            }

            // 🎯 Evento de estado (si no es "activo")
            if ($asignacion->estado && strtolower($asignacion->estado) !== 'activo') {
                $estadoNombre = ucfirst($asignacion->estado);
                $colorEstado = $coloresTurnos[$asignacion->estado] ?? [
                    'bg' => '#f87171',
                    'border' => '#dc2626',
                    'textColor' => 'white',
                    'text' => '#FFFFFF'
                ];

                $eventos[] = [
                    'title' => $estadoNombre,
                    'start' => $fecha,
                    'backgroundColor' => $colorEstado['bg'],
                    'borderColor' => $colorEstado['border'],
                    'textColor' => $colorEstado['text'],
                    'allDay' => true,
                ];
            }

            return $eventos;
        });
    }

    protected function getEventosFichajes($user)
    {
        return $user->asignacionesTurnos->flatMap(function ($asignacion) {
            $eventos = [];

            if ($asignacion->entrada && strlen($asignacion->entrada) >= 5) {
                $startEntrada = Carbon::parse("{$asignacion->fecha} {$asignacion->entrada}")
                    ->setTimezone('Europe/Madrid');

                $eventos[] = [
                    'id' => 'entrada-' . $asignacion->id,
                    'title' => 'Entrada',
                    'start' => $startEntrada->toIso8601String(),
                    'end' => $startEntrada->copy()->addMinutes(1)->toIso8601String(),
                    'color' => '#28a745', // Verde
                    'textColor' => '#ffffff',
                    'allDay' => false,
                    'display' => 'auto',
                    'extendedProps' => [
                        'tipo' => 'entrada',
                        'asignacion_id' => $asignacion->id,
                        'fecha' => $asignacion->fecha,
                        'entrada' => $asignacion->entrada,
                        'salida' => $asignacion->salida,
                    ],
                ];
            }

            if ($asignacion->salida && strlen($asignacion->salida) >= 5) {
                $startSalida = Carbon::parse("{$asignacion->fecha} {$asignacion->salida}")
                    ->setTimezone('Europe/Madrid');

                $eventos[] = [
                    'id' => 'salida-' . $asignacion->id,
                    'title' => 'Salida',
                    'start' => $startSalida->toIso8601String(),
                    'end' => $startSalida->copy()->addMinutes(1)->toIso8601String(),
                    'color' => '#dc3545', // Rojo
                    'textColor' => '#ffffff',
                    'allDay' => false,
                    'display' => 'auto',
                    'extendedProps' => [
                        'tipo' => 'salida',
                        'asignacion_id' => $asignacion->id,
                        'fecha' => $asignacion->fecha,
                        'entrada' => $asignacion->entrada,
                        'salida' => $asignacion->salida,
                    ],
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
        if (strlen($hex) !== 6) {
            return '#000000'; // Fallback a negro si formato inválido
        }
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
        $sesiones = Session::where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($sesion) {
                return [
                    'id' => $sesion->id,
                    'ip_address' => $sesion->ip_address,
                    'user_agent' => $sesion->user_agent,
                    'ultima_actividad' => \Carbon\Carbon::createFromTimestamp($sesion->last_activity)->format('d/m/Y H:i:s'),
                    'actual' => $sesion->id === FacadeSession::getId(), // comparado con el del admin, puede omitirse
                ];
            });
        return view('profile.edit', compact('user', 'sesiones'));
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


    public function subirImagen(Request $request)
    {
        $request->validate([
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $user = auth()->user();

        if ($request->hasFile('imagen')) {
            $archivo = $request->file('imagen');

            $manager = new ImageManager(new GdDriver());
            $imagen = $manager->read($archivo)->cover(300, 300)->toJpeg(85);
            $nombreArchivo = 'perfil_' . $user->id . '_' . uniqid() . '.jpg';

            // ✅ Usa explícitamente el disco "public"
            Storage::disk('public')->put("perfiles/{$nombreArchivo}", $imagen->toString());

            // ✅ También en exists y delete:
            if ($user->imagen && Storage::disk('public')->exists("perfiles/{$user->imagen}")) {
                Storage::disk('public')->delete("perfiles/{$user->imagen}");
            }

            // Borrar anterior si existe
            if ($user->imagen && Storage::exists("public/perfiles/{$user->imagen}")) {
                Storage::delete("public/perfiles/{$user->imagen}");
            }

            $user->imagen = $nombreArchivo;
            $user->save();
        }

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }
    public function actualizarUsuario(Request $request, $id)
    {

        try {
            // Validar los datos con mensajes personalizados
            $request->validate([
                'name' => 'required|string|max:50',
                'primer_apellido' => 'nullable|string|max:100',
                'segundo_apellido' => 'nullable|string|max:100',
                'email' => 'required|email|max:255|unique:users,email,' . $id,
                'movil_personal' => 'nullable|string|max:255',
                'movil_empresa' => 'nullable|string|max:255',
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

                'primer_apellido.string' => 'El primer apellido debe ser texto.',
                'primer_apellido.max' => 'El primer apellido no puede superar los 100 caracteres.',
                'segundo_apellido.string' => 'El segundo apellido debe ser texto.',
                'segundo_apellido.max' => 'El segundo apellido no puede superar los 100 caracteres.',

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
            // 💡 Normalización
            $nombre = ucfirst(mb_strtolower($request->name));
            $apellido1 = $request->primer_apellido ? ucfirst(mb_strtolower($request->primer_apellido)) : null;
            $apellido2 = $request->segundo_apellido ? ucfirst(mb_strtolower($request->segundo_apellido)) : null;
            $email = strtolower($request->email);
            $movil_personal = $request->movil_personal ? str_replace(' ', '', $request->movil_personal) : null;
            $movil_empresa = $request->movil_empresa ? str_replace(' ', '', $request->movil_empresa) : null;
            $dni = $request->dni ? strtoupper($request->dni) : null;

            $resultado = $usuario->update([
                'name' => $nombre,
                'primer_apellido' => $apellido1,
                'segundo_apellido' => $apellido2,
                'email' => $email,
                'movil_personal' => $movil_personal,
                'movil_empresa' => $movil_empresa,
                'dni' => $dni,
                'empresa_id' => $request->empresa_id,
                'rol' => $request->rol,
                'categoria_id' => $request->categoria_id,
                'maquina_id' => $request->maquina_id,
                'turno' => $request->turno,
                'updated_by' => auth()->id(),
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
            Log::error('Error de validación al actualizar usuario ID ' . $id, [
                'errores' => $e->errors(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Excepción al actualizar usuario ID ' . $id, [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }

    public function generarTurnos(User $user)
    {
        // Obtener los IDs de los turnos
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId = Turno::where('nombre', 'noche')->value('id');

        $obraId = request()->input('obra_id');

        // Definir el inicio y fin del año actual
        $inicio = Carbon::now()->addDay()->startOfDay();
        $fin = Carbon::now()->endOfYear();

        // Obtener festivos (nacionales, autonómicos y locales)
        $festivos = $this->getFestivos();
        $festivosArray = collect($festivos)->pluck('start')->toArray();

        $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
            ->where('estado', 'vacaciones')
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
            $esViernes = $fecha->dayOfWeek == Carbon::FRIDAY;
            $fechaStr = $fecha->toDateString();

            // Saltar solo sábados, domingos y festivos
            if (
                in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) ||
                in_array($fechaStr, $festivosArray)
            ) {
                if ($user->turno == 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            $asignacion = AsignacionTurno::where('user_id', $user->id)
                ->whereDate('fecha', $fechaStr)
                ->first();
            // ⛔ Saltar si el turno existente tiene asignado un turno con nombre 'festivo'
            if ($asignacion && optional($asignacion->turno)->nombre === 'festivo') {
                if ($user->turno === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            if ($asignacion) {
                $asignacion->update([
                    'turno_id'   => $turnoAsignado,
                    'maquina_id' => $user->maquina_id,
                    'obra_id'    => $obraId,
                ]);
            } else {
                AsignacionTurno::create([
                    'user_id'    => $user->id,
                    'fecha'      => $fechaStr,
                    'turno_id'   => $turnoAsignado,
                    'maquina_id' => $user->maquina_id,
                    'obra_id'    => $obraId,
                    'estado'     => 'activo',
                ]);
            }

            if ($user->turno == 'diurno' && $esViernes) {
                $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
            }
        }


        return redirect()->back()->with('success', "Turnos generados correctamente para {$user->name}, excluyendo los festivos.");
    }
    public function eventosTurnos(User $user)
    {
        $colores = $this->getColoresTurnosYEstado();
        $user->load('asignacionesTurnos.turno');

        $eventos = collect();

        // 1. Turnos (primero)
        foreach ($user->asignacionesTurnos as $asig) {
            if ($asig->turno) {
                $nombre = $asig->turno->nombre;
                $color = $colores[$nombre] ?? ['bg' => '#1d4ed8', 'border' => '#1e40af', 'text' => '#ffffff'];

                $eventos->push([
                    'title' => ucfirst($nombre),
                    'start' => $asig->fecha,
                    'allDay' => true,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                ]);
            }
        }

        // 2. Estados (después de turnos)
        foreach ($user->asignacionesTurnos as $asig) {
            if ($asig->estado && strtolower($asig->estado) !== 'activo') {
                $nombre = strtolower($asig->estado);
                $color = $colores[$nombre] ?? ['bg' => '#6b7280', 'border' => '#4b5563', 'text' => '#ffffff'];

                $eventos->push([
                    'title' => ucfirst($nombre),
                    'start' => $asig->fecha,
                    'allDay' => true,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                ]);
            }
        }

        // 3. Fichajes
        $eventos = $eventos->merge($this->getEventosFichajes($user));

        // 4. Festivos
        $eventos = $eventos->merge($this->getFestivos());

        // 5. Vacaciones
        $vacaciones = VacacionesSolicitud::where('user_id', $user->id)
            ->whereIn('estado', ['pendiente', 'denegada'])
            ->get()
            ->flatMap(function ($solicitud) {
                $title = $solicitud->estado === 'pendiente' ? 'V. pendiente' : 'V. denegadas';
                $color = $solicitud->estado === 'pendiente' ? '#fcdde8' : '#000000';
                $textColor = $solicitud->estado === 'pendiente' ? 'black' : 'white';

                return collect(CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin)->toArray())
                    ->map(function ($fecha) use ($title, $color, $textColor) {
                        return [
                            'title' => $title,
                            'start' => $fecha->toDateString(),
                            'end' => $fecha->copy()->addDay()->toDateString(),
                            'allDay' => true,
                            'backgroundColor' => $color,
                            'borderColor' => $color,
                            'textColor' => $textColor,
                        ];
                    });
            })->values();

        $eventos = $eventos->merge($vacaciones);

        return response()->json($eventos);
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
                'editable' => true,
                'allDay' => true
            ],
            [
                'title' => 'Feria Los Palacios y Vfca',
                'start' => date('Y') . '-09-25',
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'editable' => true,
                'allDay' => true
            ]
        ]);

        // Combinar festivos nacionales, autonómicos y locales
        return $festivos->merge($festivosLocales)->values()->toArray();
    }
    public function cerrarSesionesDeUsuario(User $user)
    {

        $cerradas = Session::where('user_id', $user->id)->delete();

        $user->forceFill(['remember_token' => null])->save();

        return back()->with('success', "🛑 Se han cerrado $cerradas sesión(es) activas del usuario {$user->nombre_completo}.");
    }
    public function despedirUsuario(Request $request, User $user)
    {
        DB::transaction(function () use ($user) {

            /* —————————  SEGURIDAD ————————— */
            // Desactivar la cuenta
            $user->update([
                'estado'      => 'despedido',      // o tu campo equivalente
                'fecha_baja'  => now(),
            ]);

            // Cerrar todas las sesiones guardadas (si usas una tabla custom)
            Session::where('user_id', $user->id)->delete();
            $user->forceFill(['remember_token' => null])->save();

            // Revocar tokens API (Sanctum/Passport)
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            /* —————————  PLANIFICACIÓN ————————— */
            // Borrar o reasignar turnos futuros
            AsignacionTurno::where('user_id', $user->id)
                ->whereDate('fecha', '>=', today())
                ->delete();
        });

        return redirect()->route('users.index')->with('success', '👋 Usuario despedido correctamente.');
    }
    private function getResumenAsistencia(User $user): array
    {
        $inicioAño = Carbon::now()->startOfYear();

        $conteos = AsignacionTurno::select('estado', DB::raw('count(*) as total'))
            ->where('user_id', $user->id)
            ->where('fecha', '>=', $inicioAño)
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'diasVacaciones'        => $conteos['vacaciones'] ?? 0,
            'faltasInjustificadas'  => $conteos['injustificada'] ?? 0,
            'faltasJustificadas'    => $conteos['justificada'] ?? 0,
            'diasBaja'              => $conteos['baja'] ?? 0,
        ];
    }

    public function resumenAsistencia(User $user)
    {
        return response()->json($this->getResumenAsistencia($user));
    }
}
