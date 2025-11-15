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
    private function escapeLike(string $value): string
    {
        $value = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
        return "%{$value}%";
    }

    public function exportarUsuarios(Request $request)
    {
        $usuariosFiltrados = $this->aplicarFiltros($request)
            ->with(['empresa', 'categoria'])
            ->get();


        return Excel::download(new UsersExport($usuariosFiltrados), 'usuarios-app.xlsx');
    }
    // Limpieza del duplicado en filtrosActivos()
    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('id'))              $filtros[] = 'ID: <strong>' . e($request->id) . '</strong>';
        if ($request->filled('nombre_completo')) $filtros[] = 'Nombre: <strong>' . e($request->nombre_completo) . '</strong>';
        if ($request->filled('email'))           $filtros[] = 'Email: <strong>' . e($request->email) . '</strong>';
        if ($request->filled('movil_personal'))  $filtros[] = 'Móvil Personal: <strong>' . e($request->movil_personal) . '</strong>';
        if ($request->filled('movil_empresa'))   $filtros[] = 'Móvil Empresa: <strong>' . e($request->movil_empresa) . '</strong>';
        if ($request->filled('numero_corto'))    $filtros[] = 'Nº Corporativo: <strong>' . e($request->numero_corto) . '</strong>';
        if ($request->filled('dni'))             $filtros[] = 'DNI: <strong>' . e($request->dni) . '</strong>';

        if ($request->filled('empresa_id')) {
            $empresa = \App\Models\Empresa::find($request->empresa_id);
            $filtros[] = 'Empresa: <strong>' . e($empresa->nombre ?? ('ID ' . $request->empresa_id)) . '</strong>';
        } elseif ($request->filled('empresa')) {
            $filtros[] = 'Empresa: <strong>' . e($request->empresa) . '</strong>';
        }

        if ($request->filled('categoria_id')) {
            $nombreCategoria = Categoria::find($request->categoria_id)?->nombre ?? 'Desconocida';
            $filtros[] = 'Categoría: <strong>' . e($nombreCategoria) . '</strong>';
        } elseif ($request->filled('categoria')) {
            $filtros[] = 'Categoría: <strong>' . e($request->categoria) . '</strong>';
        }

        if ($request->filled('maquina_id')) {
            $maquina = Maquina::find($request->maquina_id);
            $filtros[] = 'Máquina: <strong>' . e($maquina->nombre ?? ('ID ' . $request->maquina_id)) . '</strong>';
        } elseif ($request->filled('maquina')) {
            $filtros[] = 'Máquina: <strong>' . e($request->maquina) . '</strong>';
        }

        if ($request->filled('turno'))  $filtros[] = 'Turno: <strong>' . e($request->turno) . '</strong>';
        if ($request->filled('rol'))    $filtros[] = 'Rol: <strong>' . e($request->rol) . '</strong>';

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst(e($request->estado)) . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'nombre_completo' => 'Nombre',
                'email'           => 'Email',
                'dni'             => 'DNI',
                'empresa'         => 'Empresa',
                'rol'             => 'Rol',
                'categoria'       => 'Categoría',
                'maquina_id'      => 'Máquina',
                'turno'           => 'Turno',
                'estado'          => 'Estado',
                'numero_corto'    => 'Nº Corporativo',
                'id'              => 'ID',
            ];
            $orden = strtolower($request->order) === 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . e($sorts[$request->sort] ?? $request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . e($request->per_page) . '</strong> registros por página';
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
        // Mapa de orden seguro (añadimos nombres "bonitos" cuando haga falta join)
        $ordenables = [
            'id'              => 'users.id',
            'nombre_completo' => DB::raw("CONCAT_WS(' ', users.name, users.primer_apellido, users.segundo_apellido)"),
            'email'           => 'users.email',
            'numero_corto'    => DB::raw('CAST(users.numero_corto AS UNSIGNED)'),
            'dni'             => 'users.dni',
            // Por defecto ordena por ID; si quieres ordenar por nombre de empresa/categoría, haremos join abajo
            'empresa'         => 'empresas.nombre',
            'rol'             => 'users.rol',
            'categoria'       => 'categorias.nombre',
            'maquina_id'      => 'users.maquina_id',
            'turno'           => 'users.turno',
            'estado'          => 'users.estado', // si existiera columna; tu "estado" de conexión va en colección
        ];

        $query = User::query()->select('users.*');

        // 🔹 FILTROS

        if ($request->filled('id')) {
            $query->where('users.id', $request->id);
        }

        if ($request->filled('nombre_completo')) {
            $like = $this->escapeLike($request->input('nombre_completo'));
            $query->whereRaw(
                "CONCAT_WS(' ', users.name, users.primer_apellido, users.segundo_apellido) LIKE ? ESCAPE '\\\\'",
                [$like]
            );
        }

        if ($request->filled('email')) {
            $query->where('users.email', 'like', $this->escapeLike($request->input('email')));
        }

        if ($request->filled('movil_personal')) {
            $query->where('users.movil_personal', 'like', $this->escapeLike($request->input('movil_personal')));
        }

        if ($request->filled('movil_empresa')) {
            $query->where('users.movil_empresa', 'like', $this->escapeLike($request->input('movil_empresa')));
        }

        if ($request->filled('numero_corto')) {
            // aunque sea numérico, permitimos contains
            $query->whereRaw('CAST(users.numero_corto AS CHAR) LIKE ? ESCAPE \'\\\\\'', [$this->escapeLike($request->input('numero_corto'))]);
        }

        if ($request->filled('dni')) {
            $query->where('users.dni', 'like', $this->escapeLike($request->input('dni')));
        }

        // Empresa: por ID exacto…
        if ($request->filled('empresa_id')) {
            $query->where('users.empresa_id', $request->empresa_id);
        }
        // …o por nombre contains (param 'empresa')
        if ($request->filled('empresa')) {
            $like = $this->escapeLike($request->empresa);
            $query->whereHas('empresa', function ($q) use ($like) {
                $q->whereRaw("nombre LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Categoría: por ID…
        if ($request->filled('categoria_id')) {
            $query->where('users.categoria_id', $request->categoria_id);
        }
        // …o por nombre (param 'categoria')
        if ($request->filled('categoria')) {
            $like = $this->escapeLike($request->categoria);
            $query->whereHas('categoria', function ($q) use ($like) {
                $q->whereRaw("nombre LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Máquina: por ID exacto
        if ($request->filled('maquina_id')) {
            $query->where('users.maquina_id', $request->input('maquina_id'));
        }
        // (Opcional) por nombre
        if ($request->filled('maquina')) {
            $like = $this->escapeLike($request->maquina);
            $query->whereHas('maquina', function ($q) use ($like) {
                $q->whereRaw("nombre LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Turno y rol con contains (cámbialo a '=' si deben ser exactos)
        if ($request->filled('turno')) {
            $query->where('users.turno', 'like', $this->escapeLike($request->input('turno')));
        }
        if ($request->filled('rol')) {
            $query->where('users.rol', 'like', $this->escapeLike($request->input('rol')));
        }

        // 🔹 ORDENAMIENTO
        $sort  = $request->input('sort');
        $order = in_array(strtolower($request->input('order', 'asc')), ['asc', 'desc']) ? strtolower($request->input('order', 'asc')) : 'asc';

        // Si el orden implica campos de tablas relacionadas, hacemos join (sin romper el select users.*)
        if ($sort === 'empresa') {
            $query->leftJoin('empresas', 'empresas.id', '=', 'users.empresa_id');
        }
        if ($sort === 'categoria') {
            $query->leftJoin('categorias', 'categorias.id', '=', 'users.categoria_id');
        }

        if (isset($ordenables[$sort])) {
            $query->orderBy($ordenables[$sort], $order);
        } else {
            // orden por defecto
            $query->orderBy('users.created_at', 'desc');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $auth = auth()->user();

        if (!$auth) {
            return redirect()->route('login');
        }

        // si no es oficina, lo llevamos a su propia ficha
        if ($auth->rol !== 'oficina') {
            return redirect()->route('users.show', $auth->id);
        }

        // A partir de aquí, solo oficina: tabla de usuarios
        $usuariosConectados = DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count();

        $obras = Obra::where('estado', 'activa')
            ->where(function ($query) {
                $query->where('tipo', 'montaje')
                    ->orWhereHas('cliente', function ($q) {
                        $q->where('empresa', 'like', '%Hierros Paco Reyes%');
                    });
            })
            ->select('id', 'obra')
            ->get();

        $obrasHierrosPacoReyes = Obra::where('estado', 'activa')
            ->whereHas('cliente', function ($q) {
                $q->where('empresa', 'like', '%Hierros Paco Reyes%');
            })
            ->select('id', 'obra')
            ->get();

        $categorias = Categoria::orderBy('nombre')->get();
        $empresas   = Empresa::orderBy('nombre')->get();
        $maquinas   = Maquina::orderBy('nombre')->get();
        $roles      = User::distinct()->pluck('rol')->filter()->sort();
        $turnos     = User::distinct()->pluck('turno')->filter()->sort();
        $totalSolicitudesPendientes = VacacionesSolicitud::where('estado', 'pendiente')->count();

        $ordenables = [
            'id'              => $this->getOrdenamiento('id', 'ID'),
            'nombre_completo' => $this->getOrdenamiento('nombre_completo', 'Nombre'),
            'email'           => $this->getOrdenamiento('email', 'Email'),
            'numero_corto'    => $this->getOrdenamiento('numero_corto', 'Nº Corporativo'),
            'dni'             => $this->getOrdenamiento('dni', 'DNI'),
            'empresa'         => $this->getOrdenamiento('empresa', 'Empresa'),
            'rol'             => $this->getOrdenamiento('rol', 'Rol'),
            'categoria'       => $this->getOrdenamiento('categoria', 'Categoría'),
            'maquina_id'      => $this->getOrdenamiento('maquina_id', 'Máquina'),
            'turno'           => $this->getOrdenamiento('turno', 'Turno'),
            'estado'          => $this->getOrdenamiento('estado', 'Estado'),
        ];

        // Obtener usuarios según filtros (sin paginar aún)
        $usuarios = $this->aplicarFiltros($request)->with('categoria', 'empresa', 'maquina')->get();

        // Filtrado por estado de conexión en colección
        if ($request->filled('estado')) {
            if ($request->estado === 'activo') {
                $usuarios = $usuarios->filter(fn($u) => $u->isOnline());
            } elseif ($request->estado === 'inactivo') {
                $usuarios = $usuarios->filter(fn($u) => !$u->isOnline());
            }
        }

        // Paginar manualmente la colección
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage     = $request->input('per_page', 10);
        $offset      = ($currentPage - 1) * $perPage;

        $registrosUsuarios = new LengthAwarePaginator(
            $usuarios->slice($offset, $perPage)->values(),
            $usuarios->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $filtrosActivos = $this->filtrosActivos($request);

        // OJO: aquí NO pasamos 'user', 'resumen', ni 'horasMensuales'
        return view('User.index', compact(
            'registrosUsuarios',
            'usuariosConectados',
            'obras',
            'empresas',
            'categorias',
            'maquinas',
            'roles',
            'turnos',
            'filtrosActivos',
            'ordenables',
            'totalSolicitudesPendientes',
            'obrasHierrosPacoReyes'
        ));
    }



    public function show($id)
    {
        $auth = auth()->user();

        if ($auth->rol !== 'oficina' && (int)$auth->id !== (int)$id) {
            abort(403);
        }

        $user = User::with(['asignacionesTurnos.turno'])->findOrFail($id);
        $turnos = Turno::all();

        $resumen = $this->getResumenAsistencia($user);
        $horasMensuales = $this->getHorasMensuales($user);

        $esOficina = $auth->rol === 'oficina';

        $config = [
            'userId'   => $user->id,
            'locale'   => 'es',
            'csrfToken' => csrf_token(),
            'routes'   => [
                'eventosUrl'          => route('users.verEventos-turnos', $user->id),
                'resumenUrl'          => route('users.verResumen-asistencia', ['user' => $user->id]),
                'vacacionesStoreUrl'  => route('vacaciones.solicitar'),
                // añade más rutas si luego habilitas “asignar turnos/estados”
            ],
            'enableListMonth' => true,
            'mobileBreakpoint' => 768,
            'permissions' => [
                // Oficina puede gestionar/editar; Operario solo pedir vacaciones
                'canRequestVacations' => !$esOficina,
                'canEditHours'        => $esOficina,
                'canAssignShifts'     => false,   // déjalo en false si aún no lo usas
                'canAssignStates'     => false,   // idem
            ],
        ];

        return view('User.show', compact(
            'user',
            'turnos',
            'resumen',
            'horasMensuales',
            'config'
        ));
    }

    private function getHorasMensuales(User $user): array
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $hoy       = Carbon::now()->toDateString();
        $finMes    = Carbon::now()->endOfMonth();

        // Todas las asignaciones activas del mes
        $asignacionesMes = $user->asignacionesTurnos()
            ->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->where('estado', 'activo')
            ->get();

        $horasTrabajadas     = 0;
        $diasConErrores      = 0;
        $diasHastaHoy        = 0;
        $totalAsignacionesMes = $asignacionesMes->count(); // todas las asignaciones activas del mes

        foreach ($asignacionesMes as $asignacion) {
            // Solo para horas hasta hoy
            if ($asignacion->fecha <= $hoy) {
                $diasHastaHoy++;
            }

            $horaEntrada = $asignacion->entrada ? Carbon::parse($asignacion->entrada) : null;
            $horaSalida  = $asignacion->salida  ? Carbon::parse($asignacion->salida)  : null;

            if ($horaEntrada && $horaSalida) {
                $horasDia = $horaSalida->diffInMinutes($horaEntrada) / 60;
                if ($horasDia < 8) {
                    $horasDia = 8;
                }
                $horasTrabajadas += $horasDia;
            } else {
                // 👉 Sólo contar error si la fecha ya pasó o es hoy
                if ($asignacion->fecha < $hoy) {
                    $diasConErrores++;
                }
            }
        }

        // Horas que debería llevar hasta hoy
        $horasDeberiaLlevar = ($diasHastaHoy) * 8;

        // Horas planificadas en el mes completo (todas las asignaciones activas × 8)
        $horasPlanificadasMes = $totalAsignacionesMes * 8;

        return [
            'horas_trabajadas'       => $horasTrabajadas,
            'horas_deberia_llevar'   => $horasDeberiaLlevar,
            'dias_con_errores'       => $diasConErrores,
            'horas_planificadas_mes' => $horasPlanificadasMes,
        ];
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

            $fecha = Carbon::parse($asignacion->fecha)->toDateString(); // mejor solo fecha para allDay

            // 🎯 Evento de turno
            if ($asignacion->turno) {
                $turnoNombre = $asignacion->turno->nombre;
                $colorTurno = $coloresTurnos[$turnoNombre] ?? [
                    'bg' => '#0275d8',
                    'border' => '#025aa5',
                    'text' => '#FFFFFF'
                ];

                $eventos[] = [
                    'id' => 'turno-' . $asignacion->id,
                    'title' => ucfirst($turnoNombre),
                    'start' => $fecha,
                    'backgroundColor' => $colorTurno['bg'],
                    'borderColor' => $colorTurno['border'],
                    'textColor' => $colorTurno['text'],
                    'allDay' => true,
                    'extendedProps' => [
                        'asignacion_id' => $asignacion->id,
                        'fecha'         => $asignacion->fecha,
                        'entrada'       => $asignacion->entrada,
                        'salida'        => $asignacion->salida,
                        'es_turno'      => true
                    ],
                ];
            }

            // 🎯 Evento de estado
            if ($asignacion->estado && strtolower($asignacion->estado) !== 'activo') {
                $estadoNombre = ucfirst($asignacion->estado);
                $colorEstado = $coloresTurnos[$asignacion->estado] ?? [
                    'bg' => '#f87171',
                    'border' => '#dc2626',
                    'text' => '#FFFFFF'
                ];

                $eventos[] = [
                    'id' => 'estado-' . $asignacion->id,
                    'title' => $estadoNombre,
                    'start' => $fecha,
                    'backgroundColor' => $colorEstado['bg'],
                    'borderColor' => $colorEstado['border'],
                    'textColor' => $colorEstado['text'],
                    'allDay' => true,
                    'extendedProps' => [
                        'asignacion_id' => $asignacion->id,
                        'fecha'         => $asignacion->fecha,
                        'entrada'       => $asignacion->entrada,
                        'salida'        => $asignacion->salida,
                        'es_turno'      => false
                    ],
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

        // 🔒 Verificar si el usuario autenticado es programador
        if ($authUser->rol !== 'programador') {
            return redirect()->route('dashboard')->with('abort', 'No tienes permiso para editar perfiles. Solo el departamento de programador puede editar usuarios.');
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

        // 🔒 Verificar si el usuario autenticado es programador
        if ($authUser->rol !== 'programador') {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para actualizar perfiles. Solo el departamento de programador puede editar DNI y email.');
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
            // 🔒 Verificar que el usuario autenticado es programador para editar DNI y email
            $authUser = auth()->user();
            $isProgramador = $authUser->rol === 'programador';

            // ✅ Validar los datos con mensajes personalizados
            $validationRules = [
                'name'             => 'required|string|max:50',
                'primer_apellido'  => 'nullable|string|max:100',
                'segundo_apellido' => 'nullable|string|max:100',
                'movil_personal'   => 'nullable|string|max:255',
                'movil_empresa'    => 'nullable|string|max:255',
                'numero_corto'     => [
                    'nullable',
                    'digits:4', // exactamente 4 dígitos
                    'unique:users,numero_corto,' . $id,
                ],
                'empresa_id'   => 'nullable|exists:empresas,id',
                'rol'          => 'required|string|max:50',
                'categoria_id' => 'nullable|exists:categorias,id',
                'maquina_id'   => 'nullable|exists:maquinas,id',
                'turno'        => 'nullable|string|in:nocturno,diurno,mañana,flexible',
            ];

            // Solo programador puede editar email y dni
            if ($isProgramador) {
                $validationRules['email'] = 'required|email|max:255|unique:users,email,' . $id;
                $validationRules['dni'] = [
                    'nullable',
                    'string',
                    'size:9',
                    'regex:/^([0-9]{8}[A-Z]|[XYZ][0-9]{7}[A-Z])$/',
                    'unique:users,dni,' . $id,
                ];
            }

            $request->validate($validationRules, [
                'numero_corto.digits' => 'El número corporativo debe tener exactamente 4 cifras.',
                'numero_corto.unique' => 'Este número corporativo ya está asignado a otro usuario.',
            ]);

            // 🔎 Buscar el usuario
            $usuario = User::find($id);
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }

            // 💡 Normalización
            $nombre          = ucfirst(mb_strtolower($request->name));
            $apellido1       = $request->primer_apellido ? ucfirst(mb_strtolower($request->primer_apellido)) : null;
            $apellido2       = $request->segundo_apellido ? ucfirst(mb_strtolower($request->segundo_apellido)) : null;
            $movil_personal  = $request->movil_personal ? str_replace(' ', '', $request->movil_personal) : null;
            $movil_empresa   = $request->movil_empresa ? str_replace(' ', '', $request->movil_empresa) : null;
            $numero_corto    = $request->numero_corto ? str_pad(preg_replace('/\D/', '', $request->numero_corto), 4, '0', STR_PAD_LEFT) : null;

            // Preparar datos de actualización
            $datosActualizar = [
                'name'            => $nombre,
                'primer_apellido' => $apellido1,
                'segundo_apellido' => $apellido2,
                'movil_personal'  => $movil_personal,
                'movil_empresa'   => $movil_empresa,
                'numero_corto'    => $numero_corto,
                'empresa_id'      => $request->empresa_id,
                'rol'             => $request->rol,
                'categoria_id'    => $request->categoria_id,
                'maquina_id'      => $request->maquina_id,
                'turno'           => $request->turno,
                'updated_by'      => auth()->id(),
            ];

            // Solo programador puede actualizar email y dni
            if ($isProgramador) {
                $datosActualizar['email'] = strtolower($request->email);
                $datosActualizar['dni'] = $request->dni ? strtoupper($request->dni) : null;
            }

            // ✅ Actualización
            $resultado = $usuario->update($datosActualizar);

            if (!$resultado) {
                return response()->json(['error' => 'No se pudo actualizar el usuario.'], 500);
            }

            // 🔄 Actualizar asignaciones_turno desde hoy hasta fin de año
            AsignacionTurno::where('user_id', $usuario->id)
                ->whereDate('fecha', '>=', Carbon::today())
                ->whereDate('fecha', '<=', Carbon::createFromDate(null, 12, 31))
                ->where('turno_id', '!=', 10)
                ->update(['maquina_id' => $usuario->maquina_id]);

            return response()->json(['success' => 'Usuario actualizado correctamente.']);
        } catch (ValidationException $e) {
            Log::error('Error de validación al actualizar usuario ID ' . $id, [
                'errores' => $e->errors(),
                'input'   => $request->all()
            ]);
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Excepción al actualizar usuario ID ' . $id, [
                'mensaje' => $e->getMessage(),
                'linea'   => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace'   => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }

    public function generarTurnos(User $user)
    {
        // IDs de turnos
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId  = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId  = Turno::where('nombre', 'noche')->value('id');

        $obraId = request()->input('obra_id');

        // Rango: desde mañana hasta fin de año
        $inicio = Carbon::now()->addDay()->startOfDay();
        $fin    = Carbon::now()->endOfYear();

        // ✅ Festivos desde tu BD por rango (rápido y sin API)
        $festivosArray = Festivo::whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->all();

        // (opcional) si quieres evitar también los días ya marcados como vacaciones:
        $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
            ->where('estado', 'vacaciones')
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->all();

        // Turno inicial según configuración del usuario
        if ($user->turno == 'diurno') {
            $turnoInicial = request()->input('turno_inicio');
            if (!in_array($turnoInicial, ['mañana', 'tarde'])) {
                return redirect()->back()->with('error', 'Debe seleccionar un turno válido para comenzar (mañana o tarde).');
            }
            $turnoAsignado = $turnoInicial === 'mañana' ? $turnoMañanaId : $turnoTardeId;
        } elseif ($user->turno == 'nocturno') {
            $turnoAsignado = $turnoNocheId;
        } elseif ($user->turno == 'mañana') {
            $turnoAsignado = $turnoMañanaId;
        } else {
            return redirect()->back()->with('error', 'El usuario no tiene un turno asignado.');
        }

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $fechaStr  = $fecha->toDateString();
            $esViernes = $fecha->dayOfWeek === Carbon::FRIDAY;

            // ⛔ Saltar sábados, domingos y festivos (desde BD)
            $esNoLaborable =
                in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
                || in_array($fechaStr, $festivosArray, true)
                || in_array($fechaStr, $diasVacaciones ?? [], true); // (opcional) evita vacaciones

            if ($esNoLaborable) {
                // Mantén la rotación de viernes aunque se salte el día
                if ($user->turno === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            $asignacion = AsignacionTurno::where('user_id', $user->id)
                ->whereDate('fecha', $fechaStr)
                ->first();

            // ⛔ No sobrescribir si ese día se marcó con turno 'festivo'
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

            // Rotación de viernes (para diurno)
            if ($user->turno === 'diurno' && $esViernes) {
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
            if ($asig->estado && strtolower($asig->estado) !== 'activo') {
                $nombre = strtolower($asig->estado);
                $color = $colores[$nombre] ?? ['bg' => '#6b7280', 'border' => '#4b5563', 'text' => '#ffffff'];

                $eventos->push([
                    'id' => 'estado-' . $asig->id,
                    'title' => ucfirst($nombre),
                    'start' => $asig->fecha,
                    'allDay' => true,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                    'extendedProps' => [
                        'asignacion_id' => $asig->id,
                        'fecha'         => $asig->fecha,
                        'entrada'       => $asig->entrada,
                        'salida'        => $asig->salida,
                        'es_turno'      => false
                    ],
                ]);
            }
        }

        // 2. Estados (después de turnos)
        foreach ($user->asignacionesTurnos as $asig) {
            if ($asig->turno) {
                $nombre = $asig->turno->nombre;
                $color = $colores[$nombre] ?? ['bg' => '#1d4ed8', 'border' => '#1e40af', 'text' => '#ffffff'];

                $eventos->push([
                    'id' => 'turno-' . $asig->id,
                    'title' => ucfirst($nombre),
                    'start' => $asig->fecha,
                    'allDay' => true,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                    'extendedProps' => [
                        'asignacion_id' => $asig->id,
                        'fecha'         => $asig->fecha,
                        'entrada'       => $asig->entrada,
                        'salida'        => $asig->salida,
                        'es_turno'      => true
                    ],
                ]);
            }
        }

        // 3. Fichajes
        $eventos = $eventos->merge($this->getEventosFichajes($user));

        // 4. Festivos
        $eventos = $eventos->merge(Festivo::eventosCalendario());

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

    public function generarTurnosCalendario(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'maquina_id' => 'required|exists:maquinas,id',
            'fecha_inicio' => 'required|date',
            'alcance' => 'required|in:un_dia,resto_año',
            'turno_inicio' => 'nullable|in:mañana,tarde',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // IDs de turnos
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId  = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId  = Turno::where('nombre', 'noche')->value('id');

        // Determinar rango de fechas
        $inicio = Carbon::parse($validated['fecha_inicio'])->startOfDay();
        if ($validated['alcance'] === 'un_dia') {
            $fin = $inicio->copy();
        } else {
            $fin = Carbon::parse($validated['fecha_inicio'])->endOfYear();
        }

        // Obtener festivos del rango
        $festivosArray = Festivo::whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->all();

        // Obtener días de vacaciones
        $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
            ->where('estado', 'vacaciones')
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->all();

        // Determinar turno inicial
        if ($user->turno == 'diurno') {
            $turnoInicial = $validated['turno_inicio'] ?? 'mañana';
            if (!in_array($turnoInicial, ['mañana', 'tarde'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe seleccionar un turno válido para comenzar (mañana o tarde).'
                ], 400);
            }
            $turnoAsignado = $turnoInicial === 'mañana' ? $turnoMañanaId : $turnoTardeId;
        } elseif ($user->turno == 'nocturno') {
            $turnoAsignado = $turnoNocheId;
        } elseif ($user->turno == 'mañana') {
            $turnoAsignado = $turnoMañanaId;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene un turno asignado.'
            ], 400);
        }

        $turnosCreados = 0;

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $fechaStr  = $fecha->toDateString();
            $esViernes = $fecha->dayOfWeek === Carbon::FRIDAY;

            // Saltar sábados, domingos, festivos y vacaciones
            $esNoLaborable =
                in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
                || in_array($fechaStr, $festivosArray, true)
                || in_array($fechaStr, $diasVacaciones, true);

            if ($esNoLaborable) {
                // Mantener la rotación de viernes aunque se salte el día
                if ($user->turno === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            $asignacion = AsignacionTurno::where('user_id', $user->id)
                ->whereDate('fecha', $fechaStr)
                ->first();

            // No sobrescribir si ese día se marcó con turno 'festivo'
            if ($asignacion && optional($asignacion->turno)->nombre === 'festivo') {
                if ($user->turno === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            if ($asignacion) {
                $asignacion->update([
                    'turno_id'   => $turnoAsignado,
                    'maquina_id' => $validated['maquina_id'],
                ]);
            } else {
                AsignacionTurno::create([
                    'user_id'    => $user->id,
                    'fecha'      => $fechaStr,
                    'turno_id'   => $turnoAsignado,
                    'maquina_id' => $validated['maquina_id'],
                    'estado'     => 'activo',
                ]);
            }

            $turnosCreados++;

            // Rotación de viernes (para diurno)
            if ($user->turno === 'diurno' && $esViernes) {
                $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Turnos generados correctamente',
            'turnos_creados' => $turnosCreados,
        ]);
    }
}
