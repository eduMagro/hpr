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
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use App\Servicios\Turnos\TurnoMapper;
use App\Services\OperarioService;

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

        if ($request->filled('id'))
            $filtros[] = 'ID: <strong>' . e($request->id) . '</strong>';
        if ($request->filled('nombre_completo'))
            $filtros[] = 'Nombre: <strong>' . e($request->nombre_completo) . '</strong>';
        if ($request->filled('email'))
            $filtros[] = 'Email: <strong>' . e($request->email) . '</strong>';
        if ($request->filled('movil_personal'))
            $filtros[] = 'Móvil Personal: <strong>' . e($request->movil_personal) . '</strong>';
        if ($request->filled('movil_empresa'))
            $filtros[] = 'Móvil Empresa: <strong>' . e($request->movil_empresa) . '</strong>';
        if ($request->filled('numero_corto'))
            $filtros[] = 'Nº Corporativo: <strong>' . e($request->numero_corto) . '</strong>';
        if ($request->filled('dni'))
            $filtros[] = 'DNI: <strong>' . e($request->dni) . '</strong>';

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

        if ($request->filled('rol'))
            $filtros[] = 'Rol: <strong>' . e($request->rol) . '</strong>';

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst(e($request->estado)) . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'nombre_completo' => 'Nombre',
                'email' => 'Email',
                'dni' => 'DNI',
                'empresa' => 'Empresa',
                'rol' => 'Rol',
                'categoria' => 'Categoría',
                'maquina_id' => 'Máquina',
                'estado' => 'Estado',
                'numero_corto' => 'Nº Corporativo',
                'id' => 'ID',
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
            'id' => 'users.id',
            'nombre_completo' => DB::raw("CONCAT_WS(' ', users.name, users.primer_apellido, users.segundo_apellido)"),
            'email' => 'users.email',
            'numero_corto' => DB::raw('CAST(users.numero_corto AS UNSIGNED)'),
            'dni' => 'users.dni',
            // Por defecto ordena por ID; si quieres ordenar por nombre de empresa/categoría, haremos join abajo
            'empresa' => 'empresas.nombre',
            'rol' => 'users.rol',
            'categoria' => 'categorias.nombre',
            'maquina_id' => 'users.maquina_id',
            'estado' => 'users.estado', // si existiera columna; tu "estado" de conexión va en colección
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

        // Rol con contains (cámbialo a '=' si debe ser exacto)
        if ($request->filled('rol')) {
            $query->where('users.rol', 'like', $this->escapeLike($request->input('rol')));
        }

        // 🔹 ORDENAMIENTO
        $sort = $request->input('sort');
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
        $empresas = Empresa::orderBy('nombre')->get();
        $maquinas = Maquina::orderBy('nombre')->get();
        $roles = User::distinct()->pluck('rol')->filter()->sort();
        $totalSolicitudesPendientes = VacacionesSolicitud::where('estado', 'pendiente')->count();

        $ordenables = [
            'id' => $this->getOrdenamiento('id', 'ID'),
            'nombre_completo' => $this->getOrdenamiento('nombre_completo', 'Nombre'),
            'email' => $this->getOrdenamiento('email', 'Email'),
            'numero_corto' => $this->getOrdenamiento('numero_corto', 'Nº Corporativo'),
            'dni' => $this->getOrdenamiento('dni', 'DNI'),
            'empresa' => $this->getOrdenamiento('empresa', 'Empresa'),
            'rol' => $this->getOrdenamiento('rol', 'Rol'),
            'categoria' => $this->getOrdenamiento('categoria', 'Categoría'),
            'maquina_id' => $this->getOrdenamiento('maquina_id', 'Máquina'),
            'estado' => $this->getOrdenamiento('estado', 'Estado'),
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
        $perPage = $request->input('per_page', 10);
        $offset = ($currentPage - 1) * $perPage;

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
            'filtrosActivos',
            'ordenables',
            'totalSolicitudesPendientes',
            'obrasHierrosPacoReyes'
        ));
    }



    public function show($user)
    {
        $auth = auth()->user();
        $id = $user instanceof User ? $user->id : $user;

        if ($auth->rol !== 'oficina' && (int) $auth->id !== (int) $id) {
            return back()->with('error', 'No tienes permiso para ver este perfil.');
        }

        $user = User::with(['asignacionesTurnos.turno', 'tallas'])->findOrFail($id);
        $turnos = Turno::all();

        $resumen = $this->getResumenAsistencia($user);
        $resumenVacaciones = $this->getResumenVacaciones($user);
        $horasMensuales = $this->getHorasMensuales($user);

        $esOficina = $auth->rol === 'oficina';

        $config = [
            'userId' => $user->id,
            'locale' => 'es',
            'csrfToken' => csrf_token(),
            'routes' => [
                'eventosUrl' => route('users.verEventos-turnos', $user->id),
                'resumenUrl' => route('users.verResumen-asistencia', ['user' => $user->id]),
                'vacacionesStoreUrl' => route('vacaciones.solicitar'),
                'storeUrl' => route('asignaciones-turnos.store'),
                'destroyUrl' => route('asignaciones-turnos.destroy'),
                'vacationDataUrl' => route('usuarios.getVacationData', ['user' => $user->id]),
                'solicitudesPendientesUrl' => route('vacaciones.verSolicitudesPendientesUsuario', ['user' => $user->id]),
            ],
            'enableListMonth' => true,
            'mobileBreakpoint' => 768,
            'permissions' => [
                'canRequestVacations' => !$esOficina,
                'canEditHours' => $esOficina,
                'canAssignShifts' => $esOficina,
                'canAssignStates' => $esOficina,
            ],
            'turnos' => $turnos->map(fn($t) => ['nombre' => $t->nombre])->values()->toArray(),
            'fechaIncorporacion' => $user->fecha_incorporacion_efectiva ? $user->fecha_incorporacion_efectiva->format('Y-m-d') : null,
            'diasVacacionesAsignados' => $user->asignacionesTurnos()
                ->where('estado', 'vacaciones')
                // ->whereYear('fecha', now()->year) // Opcional: si solo cuentan las del año en curso
                ->count(),
        ];

        return view('User.show', compact(
            'user',
            'turnos',
            'resumen',
            'resumenVacaciones',
            'horasMensuales',
            'config'
        ));
    }

    private function getHorasMensuales(User $user): array
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $hoy = Carbon::now()->toDateString();
        $finMes = Carbon::now()->endOfMonth();

        // Todas las asignaciones activas del mes
        $asignacionesMes = $user->asignacionesTurnos()
            ->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->where('estado', 'activo')
            ->get();

        $horasTrabajadas = 0;
        $diasConErrores = 0;
        $diasHastaHoy = 0;
        $totalAsignacionesMes = $asignacionesMes->count(); // todas las asignaciones activas del mes

        foreach ($asignacionesMes as $asignacion) {
            // Solo para horas hasta hoy
            if ($asignacion->fecha <= $hoy) {
                $diasHastaHoy++;
            }

            $horaEntrada = $asignacion->entrada ? Carbon::parse($asignacion->entrada) : null;
            $horaSalida = $asignacion->salida ? Carbon::parse($asignacion->salida) : null;

            // Primera jornada
            if ($horaEntrada && $horaSalida) {
                $horasDia = $horaSalida->diffInMinutes($horaEntrada) / 60;
                $horasTrabajadas += $horasDia;
            } else {
                // Solo contar error si la fecha ya pasó
                if ($asignacion->fecha < $hoy) {
                    $diasConErrores++;
                }
            }

            // Segunda jornada (turno partido)
            $horaEntrada2 = $asignacion->entrada2 ? Carbon::parse($asignacion->entrada2) : null;
            $horaSalida2 = $asignacion->salida2 ? Carbon::parse($asignacion->salida2) : null;

            if ($horaEntrada2 && $horaSalida2) {
                $horasJornada2 = $horaSalida2->diffInMinutes($horaEntrada2) / 60;
                $horasTrabajadas += $horasJornada2;
            }

            // Mínimo 8 horas si hay al menos una jornada completa
            if (($horaEntrada && $horaSalida) || ($horaEntrada2 && $horaSalida2)) {
                // Si el total del día es menor a 8, ajustar a 8
                $horasTotalDia = 0;
                if ($horaEntrada && $horaSalida) {
                    $horasTotalDia += $horaSalida->diffInMinutes($horaEntrada) / 60;
                }
                if ($horaEntrada2 && $horaSalida2) {
                    $horasTotalDia += $horaSalida2->diffInMinutes($horaEntrada2) / 60;
                }
                // Si el total es menor a 8, restar lo calculado y sumar 8
                if ($horasTotalDia > 0 && $horasTotalDia < 8) {
                    $horasTrabajadas = $horasTrabajadas - $horasTotalDia + 8;
                }
            }
        }

        // Horas que debería llevar hasta hoy
        $horasDeberiaLlevar = ($diasHastaHoy) * 8;

        // Horas planificadas en el mes completo (todas las asignaciones activas × 8)
        $horasPlanificadasMes = $totalAsignacionesMes * 8;

        return [
            'horas_trabajadas' => $horasTrabajadas,
            'horas_deberia_llevar' => $horasDeberiaLlevar,
            'dias_con_errores' => $diasConErrores,
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
                        'fecha' => $asignacion->fecha,
                        'entrada' => $asignacion->entrada,
                        'salida' => $asignacion->salida,
                        'es_turno' => true
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
                        'fecha' => $asignacion->fecha,
                        'entrada' => $asignacion->entrada,
                        'salida' => $asignacion->salida,
                        'es_turno' => false
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

            // Extraer solo la fecha (YYYY-MM-DD) para evitar "double time specification"
            $soloFecha = Carbon::parse($asignacion->fecha)->format('Y-m-d');

            // Indicador de revisión
            $esRevisado = !empty($asignacion->revisado_at);
            $sufijo = $esRevisado ? ' R' : '';

            // Props comunes para todos los fichajes de esta asignación
            $propsComunes = [
                'asignacion_id' => $asignacion->id,
                'fecha' => $soloFecha,
                'entrada' => $asignacion->entrada,
                'salida' => $asignacion->salida,
                'entrada2' => $asignacion->entrada2,
                'salida2' => $asignacion->salida2,
                'revisado' => $esRevisado,
            ];

            // === PRIMERA JORNADA ===
            if ($asignacion->entrada && strlen($asignacion->entrada) >= 5) {
                try {
                    $horaEntrada = substr(trim($asignacion->entrada), 0, 8);
                    $startEntrada = Carbon::createFromFormat('Y-m-d H:i:s', "{$soloFecha} {$horaEntrada}", 'Europe/Madrid')
                        ?? Carbon::createFromFormat('Y-m-d H:i', "{$soloFecha} " . substr($horaEntrada, 0, 5), 'Europe/Madrid');

                    if ($startEntrada) {
                        $eventos[] = [
                            'id' => 'entrada-' . $asignacion->id,
                            'title' => '🟢 ' . substr($horaEntrada, 0, 5) . $sufijo,
                            'start' => $startEntrada->toIso8601String(),
                            'end' => $startEntrada->copy()->addMinutes(1)->toIso8601String(),
                            'color' => '#28a745',
                            'textColor' => '#ffffff',
                            'allDay' => false,
                            'display' => 'auto',
                            'extendedProps' => array_merge($propsComunes, ['tipo' => 'entrada', 'jornada' => 1]),
                        ];
                    }
                } catch (\Exception $e) {
                    // Ignorar registros con formato inválido
                }
            }

            if ($asignacion->salida && strlen($asignacion->salida) >= 5) {
                try {
                    $horaSalida = substr(trim($asignacion->salida), 0, 8);
                    $startSalida = Carbon::createFromFormat('Y-m-d H:i:s', "{$soloFecha} {$horaSalida}", 'Europe/Madrid')
                        ?? Carbon::createFromFormat('Y-m-d H:i', "{$soloFecha} " . substr($horaSalida, 0, 5), 'Europe/Madrid');

                    if ($startSalida) {
                        $eventos[] = [
                            'id' => 'salida-' . $asignacion->id,
                            'title' => '🔴 ' . substr($horaSalida, 0, 5) . $sufijo,
                            'start' => $startSalida->toIso8601String(),
                            'end' => $startSalida->copy()->addMinutes(1)->toIso8601String(),
                            'color' => '#dc3545',
                            'textColor' => '#ffffff',
                            'allDay' => false,
                            'display' => 'auto',
                            'extendedProps' => array_merge($propsComunes, ['tipo' => 'salida', 'jornada' => 1]),
                        ];
                    }
                } catch (\Exception $e) {
                    // Ignorar registros con formato inválido
                }
            }

            // === SEGUNDA JORNADA (TURNO PARTIDO) ===
            if ($asignacion->entrada2 && strlen($asignacion->entrada2) >= 5) {
                try {
                    $horaEntrada2 = substr(trim($asignacion->entrada2), 0, 8);
                    $startEntrada2 = Carbon::createFromFormat('Y-m-d H:i:s', "{$soloFecha} {$horaEntrada2}", 'Europe/Madrid')
                        ?? Carbon::createFromFormat('Y-m-d H:i', "{$soloFecha} " . substr($horaEntrada2, 0, 5), 'Europe/Madrid');

                    if ($startEntrada2) {
                        $eventos[] = [
                            'id' => 'entrada2-' . $asignacion->id,
                            'title' => '🟢 ' . substr($horaEntrada2, 0, 5) . $sufijo . ' (2ª)',
                            'start' => $startEntrada2->toIso8601String(),
                            'end' => $startEntrada2->copy()->addMinutes(1)->toIso8601String(),
                            'color' => '#22c55e', // Verde más claro para segunda jornada
                            'textColor' => '#ffffff',
                            'allDay' => false,
                            'display' => 'auto',
                            'extendedProps' => array_merge($propsComunes, ['tipo' => 'entrada2', 'jornada' => 2]),
                        ];
                    }
                } catch (\Exception $e) {
                    // Ignorar registros con formato inválido
                }
            }

            if ($asignacion->salida2 && strlen($asignacion->salida2) >= 5) {
                try {
                    $horaSalida2 = substr(trim($asignacion->salida2), 0, 8);
                    $startSalida2 = Carbon::createFromFormat('Y-m-d H:i:s', "{$soloFecha} {$horaSalida2}", 'Europe/Madrid')
                        ?? Carbon::createFromFormat('Y-m-d H:i', "{$soloFecha} " . substr($horaSalida2, 0, 5), 'Europe/Madrid');

                    if ($startSalida2) {
                        $eventos[] = [
                            'id' => 'salida2-' . $asignacion->id,
                            'title' => '🔴 ' . substr($horaSalida2, 0, 5) . $sufijo . ' (2ª)',
                            'start' => $startSalida2->toIso8601String(),
                            'end' => $startSalida2->copy()->addMinutes(1)->toIso8601String(),
                            'color' => '#ef4444', // Rojo más claro para segunda jornada
                            'textColor' => '#ffffff',
                            'allDay' => false,
                            'display' => 'auto',
                            'extendedProps' => array_merge($propsComunes, ['tipo' => 'salida2', 'jornada' => 2]),
                        ];
                    }
                } catch (\Exception $e) {
                    // Ignorar registros con formato inválido
                }
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

        // Buscar el usuario que se quiere editar
        $user = User::findOrFail($id);

        // 🔒 Verificar si el usuario autenticado pertenece al departamento de programador (solo para nota informativa)
        $esProgramador = $authUser->departamentos()->where('nombre', 'Programador')->exists();

        $sesiones = Session::where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($sesion) {
                $lastActivity = \Carbon\Carbon::createFromTimestamp($sesion->last_activity);
                return [
                    'id' => $sesion->id,
                    'ip_address' => $sesion->ip_address,
                    'user_agent' => $sesion->user_agent,
                    'dispositivo' => $this->parseUserAgent($sesion->user_agent),
                    'ultima_actividad' => $lastActivity->format('d/m/Y H:i'),
                    'tiempo_relativo' => $lastActivity->diffForHumans(),
                    'actual' => $sesion->id === FacadeSession::getId(),
                ];
            });
        return view('profile.edit', compact('user', 'sesiones'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request, $id)
    {
        // Obtener el usuario autenticado
        $authUser = auth()->user();

        // 🔒 Verificar si el usuario autenticado pertenece al departamento de programador
        $esProgramador = $authUser->departamentos()->where('nombre', 'Programador')->exists();

        // Buscar el usuario que se quiere actualizar
        $user = User::findOrFail($id);

        // 🔒 Si NO es programador y está intentando CAMBIAR DNI o email, denegar
        if (!$esProgramador) {
            $emailCambiado = $request->has('email') && $request->email !== $user->email;
            $dniCambiado = $request->has('dni') && $request->dni !== $user->dni;

            if ($emailCambiado || $dniCambiado) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para editar DNI y email. Solo el departamento de Programador puede editar estos campos.'
                    ], 403);
                }
                return redirect()->route('dashboard')->with('error', 'No tienes permiso para editar DNI y email. Solo el departamento de Programador puede editar estos campos.');
            }
        }

        // Validación inline para peticiones JSON
        if ($request->wantsJson()) {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'primer_apellido' => 'nullable|string|max:255',
                'segundo_apellido' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:users,email,' . $id,
                'movil_personal' => 'nullable|string|max:20',
                'movil_empresa' => 'nullable|string|max:20',
                'numero_corto' => 'nullable|string|max:4',
                'dni' => 'nullable|string|max:20',
                'empresa_id' => 'nullable|exists:empresas,id',
                'rol' => 'nullable|string|in:operario,oficina,transportista,visitante',
                'categoria_id' => 'nullable|exists:categorias,id',
                'maquina_id' => 'nullable|exists:maquinas,id',
            ]);

            // Si NO es programador, excluir email y dni del update para no sobrescribirlos con null
            if (!$esProgramador) {
                unset($validated['email'], $validated['dni']);
            }

            $user->fill($validated);
        } else {
            // Usar ProfileUpdateRequest para formularios normales
            $validated = $request->validate((new ProfileUpdateRequest())->rules());

            // Si NO es programador, excluir email y dni del update para no sobrescribirlos con null
            if (!$esProgramador) {
                unset($validated['email'], $validated['dni']);
            }

            $user->fill($validated);
        }

        if ($request->filled('email') && $user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Responder según el tipo de petición
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'user' => $user
            ]);
        }

        return Redirect::route('profile.edit', ['id' => $id])->with('status', 'profile-updated');
    }

    public function subirImagen(Request $request)
    {
        $request->validate([
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        // Si se pasa user_id, usar ese usuario (para oficina editando otro perfil)
        // Si no, usar el usuario autenticado
        $userId = $request->input('user_id', auth()->id());
        $user = User::find($userId);

        // Verificar permisos: solo el propio usuario o usuarios de oficina/admin pueden cambiar la foto
        $authUser = auth()->user();
        if ($authUser->id != $userId && !in_array($authUser->rol, ['oficina', 'admin', 'administrador'])) {
            return back()->with('error', 'No tienes permisos para cambiar esta foto.');
        }

        if ($request->hasFile('imagen')) {
            $archivo = $request->file('imagen');

            // Borrar anterior si existe
            if ($user->imagen) {
                Storage::disk('public')->delete("perfiles/{$user->imagen}");
            }

            $driver = null;
            if (extension_loaded('gd')) {
                $driver = new GdDriver();
            } elseif (extension_loaded('imagick')) {
                $driver = new ImagickDriver();
            }

            if ($driver) {
                // Normalizar y optimizar imagen de perfil (300x300 JPG)
                $manager = new ImageManager($driver);
                $imagen = $manager->read($archivo)->cover(300, 300)->toJpeg(85);
                $nombreArchivo = 'perfil_' . $user->id . '_' . uniqid() . '.jpg';
                Storage::disk('public')->put("perfiles/{$nombreArchivo}", $imagen->toString());
            } else {
                // Fallback sin drivers de imagen: guardar el archivo tal cual.
                $ext = strtolower($archivo->getClientOriginalExtension() ?: 'jpg');
                $nombreArchivo = 'perfil_' . $user->id . '_' . uniqid() . '.' . $ext;
                Storage::disk('public')->putFileAs('perfiles', $archivo, $nombreArchivo);
            }

            $user->imagen = $nombreArchivo;
            $user->save();
        }

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }

    public function actualizarUsuario(Request $request, $id)
    {
        try {
            // 🔒 Verificar que el usuario autenticado pertenece al departamento de programador para editar DNI y email
            $authUser = auth()->user();
            $isProgramador = $authUser->departamentos()->where('nombre', 'Programador')->exists();

            // ✅ Validar los datos con mensajes personalizados
            $validationRules = [
                'name' => 'required|string|max:50',
                'primer_apellido' => 'nullable|string|max:100',
                'segundo_apellido' => 'nullable|string|max:100',
                'movil_personal' => 'nullable|string|max:255',
                'movil_empresa' => 'nullable|string|max:255',
                'numero_corto' => [
                    'nullable',
                    'digits:4', // exactamente 4 dígitos
                    'unique:users,numero_corto,' . $id,
                ],
                'empresa_id' => 'nullable|exists:empresas,id',
                'rol' => 'required|string|max:50',
                'categoria_id' => 'nullable|exists:categorias,id',
                'maquina_id' => 'nullable|exists:maquinas,id',
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
            $nombre = ucfirst(mb_strtolower($request->name));
            $apellido1 = $request->primer_apellido ? ucfirst(mb_strtolower($request->primer_apellido)) : null;
            $apellido2 = $request->segundo_apellido ? ucfirst(mb_strtolower($request->segundo_apellido)) : null;
            $movil_personal = $request->movil_personal ? str_replace(' ', '', $request->movil_personal) : null;
            $movil_empresa = $request->movil_empresa ? str_replace(' ', '', $request->movil_empresa) : null;
            $numero_corto = $request->numero_corto ? str_pad(preg_replace('/\D/', '', $request->numero_corto), 4, '0', STR_PAD_LEFT) : null;

            // Preparar datos de actualización
            $datosActualizar = [
                'name' => $nombre,
                'primer_apellido' => $apellido1,
                'segundo_apellido' => $apellido2,
                'movil_personal' => $movil_personal,
                'movil_empresa' => $movil_empresa,
                'numero_corto' => $numero_corto,
                'empresa_id' => $request->empresa_id,
                'rol' => $request->rol,
                'categoria_id' => $request->categoria_id,
                'maquina_id' => $request->maquina_id,
                'updated_by' => auth()->id(),
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
        // IDs de turnos
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId = Turno::where('nombre', 'noche')->value('id');

        $obraId = request()->input('obra_id');
        $tipoTurno = request()->input('tipo_turno'); // diurno, nocturno, mañana

        // Rango: desde mañana hasta fin de año
        $inicio = Carbon::now()->addDay()->startOfDay();
        $fin = Carbon::now()->endOfYear();

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

        // Turno inicial según selección del usuario
        if ($tipoTurno == 'diurno') {
            $turnoInicial = request()->input('turno_inicio');
            if (!in_array($turnoInicial, ['mañana', 'tarde'])) {
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Debe seleccionar un turno válido para comenzar (mañana o tarde).'], 400);
                }
                return redirect()->back()->with('error', 'Debe seleccionar un turno válido para comenzar (mañana o tarde).');
            }
            $turnoAsignado = $turnoInicial === 'mañana' ? $turnoMañanaId : $turnoTardeId;
        } elseif ($tipoTurno == 'nocturno') {
            $turnoAsignado = $turnoNocheId;
        } elseif ($tipoTurno == 'mañana') {
            $turnoAsignado = $turnoMañanaId;
        } else {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Debe seleccionar un tipo de turno válido.'], 400);
            }
            return redirect()->back()->with('error', 'Debe seleccionar un tipo de turno válido.');
        }

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $fechaStr = $fecha->toDateString();
            $esViernes = $fecha->dayOfWeek === Carbon::FRIDAY;

            // ⛔ Saltar sábados, domingos y festivos (desde BD)
            $esNoLaborable =
                in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
                || in_array($fechaStr, $festivosArray, true)
                || in_array($fechaStr, $diasVacaciones ?? [], true); // (opcional) evita vacaciones

            if ($esNoLaborable) {
                // Mantén la rotación de viernes aunque se salte el día
                if ($tipoTurno === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            $asignacion = AsignacionTurno::where('user_id', $user->id)
                ->whereDate('fecha', $fechaStr)
                ->first();

            // ⛔ No sobrescribir si ese día se marcó con turno 'festivo'
            if ($asignacion && optional($asignacion->turno)->nombre === 'festivo') {
                if ($tipoTurno === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            if ($asignacion) {
                $asignacion->update([
                    'turno_id' => $turnoAsignado,
                    'maquina_id' => $user->maquina_id,
                    'obra_id' => $obraId,
                ]);
            } else {
                AsignacionTurno::create([
                    'user_id' => $user->id,
                    'fecha' => $fechaStr,
                    'turno_id' => $turnoAsignado,
                    'maquina_id' => $user->maquina_id,
                    'obra_id' => $obraId,
                    'estado' => 'activo',
                ]);
            }

            // Rotación de viernes (para diurno)
            if ($tipoTurno === 'diurno' && $esViernes) {
                $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
            }
        }

        // Si es petición AJAX, devolver JSON
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Turnos generados correctamente para {$user->name}, excluyendo los festivos."
            ]);
        }

        return redirect()->back()->with('success', "Turnos generados correctamente para {$user->name}, excluyendo los festivos.");
    }

    public function eventosTurnos(User $user)
    {
        $colores = $this->getColoresTurnosYEstado();
        $user->load('asignacionesTurnos.turno', 'asignacionesTurnos.obra');

        $eventos = collect();

        // 1. Estados (vacaciones, baja, etc.)
        foreach ($user->asignacionesTurnos as $asig) {
            if ($asig->estado && strtolower($asig->estado) !== 'activo') {
                $nombre = strtolower($asig->estado);
                $color = $colores[$nombre] ?? ['bg' => '#6b7280', 'border' => '#4b5563', 'text' => '#ffffff'];

                // Formatear fecha como string YYYY-MM-DD para evitar problemas de timezone
                $fechaStr = Carbon::parse($asig->fecha)->format('Y-m-d');

                $eventos->push([
                    'id' => 'estado-' . $asig->id,
                    'title' => ucfirst($nombre),
                    'start' => $fechaStr,
                    'allDay' => true,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                    'extendedProps' => [
                        'asignacion_id' => $asig->id,
                        'fecha' => $fechaStr,
                        'entrada' => $asig->entrada,
                        'salida' => $asig->salida,
                        'entrada2' => $asig->entrada2,
                        'salida2' => $asig->salida2,
                        'es_turno' => false,
                        'obra_id' => $asig->obra_id,
                        'obra_nombre' => $asig->obra?->obra,
                    ],
                ]);
            }
        }

        // 2. Turnos (mañana, tarde, noche)
        foreach ($user->asignacionesTurnos as $asig) {
            if ($asig->turno) {
                $nombre = $asig->turno->nombre;
                $color = $colores[$nombre] ?? ['bg' => '#1d4ed8', 'border' => '#1e40af', 'text' => '#ffffff'];

                // Formatear fecha como string YYYY-MM-DD para evitar problemas de timezone
                $fechaStr = Carbon::parse($asig->fecha)->format('Y-m-d');

                $eventos->push([
                    'id' => 'turno-' . $asig->id,
                    'title' => ucfirst($nombre),
                    'start' => $fechaStr,
                    'allDay' => true,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                    'extendedProps' => [
                        'asignacion_id' => $asig->id,
                        'fecha' => $fechaStr,
                        'entrada' => $asig->entrada,
                        'salida' => $asig->salida,
                        'entrada2' => $asig->entrada2,
                        'salida2' => $asig->salida2,
                        'es_turno' => true,
                        'obra_id' => $asig->obra_id,
                        'obra_nombre' => $asig->obra?->obra,
                    ],
                ]);
            }
        }

        // 3. Fichajes (entrada/salida con hora)
        $eventos = $eventos->merge($this->getEventosFichajes($user));

        // 4. Festivos
        $eventos = $eventos->merge(Festivo::eventosCalendario());

        // 5. Vacaciones denegadas (las pendientes se cargan desde el frontend para permitir gestión interactiva)
        $vacaciones = VacacionesSolicitud::where('user_id', $user->id)
            ->where('estado', 'denegada')
            ->get()
            ->flatMap(function ($solicitud) {
                return collect(CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin)->toArray())
                    ->map(function ($fecha) use ($solicitud) {
                        $fechaStr = $fecha->format('Y-m-d');
                        return [
                            'id' => 'vac-' . $solicitud->id . '-' . $fechaStr,
                            'title' => 'V. denegadas',
                            'start' => $fechaStr,
                            'allDay' => true,
                            'backgroundColor' => '#000000',
                            'borderColor' => '#000000',
                            'textColor' => 'white',
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

    /**
     * Parsea el User-Agent para mostrar información amigable del dispositivo
     */
    private function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'navegador' => 'Navegador desconocido',
                'sistema' => 'Sistema desconocido',
                'dispositivo' => null,
                'icono' => 'desktop',
            ];
        }

        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($userAgent);

        // Detectar si es bot/crawler
        if ($agent->isRobot()) {
            return [
                'navegador' => $agent->robot() ?: 'Bot',
                'sistema' => 'Robot/Crawler',
                'dispositivo' => null,
                'icono' => 'bot',
            ];
        }

        // === NAVEGADOR ===
        $browser = $agent->browser();
        if (!$browser || $browser === 'Webkit' || $browser === 'WebKit') {
            if (str_contains($userAgent, 'Edg/') || str_contains($userAgent, 'Edge/')) {
                $browser = 'Edge';
            } elseif (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera')) {
                $browser = 'Opera';
            } elseif (str_contains($userAgent, 'Chrome/')) {
                $browser = 'Chrome';
            } elseif (str_contains($userAgent, 'Firefox/')) {
                $browser = 'Firefox';
            } elseif (str_contains($userAgent, 'Safari/')) {
                $browser = 'Safari';
            } else {
                $browser = 'Navegador';
            }
        }

        $browserVersion = $agent->version($browser);
        if (!$browserVersion && preg_match('/' . preg_quote($browser, '/') . '[\/\s](\d+)/i', $userAgent, $matches)) {
            $browserVersion = $matches[1];
        }

        $navegador = $browser;
        if ($browserVersion) {
            $versionMajor = explode('.', (string) $browserVersion)[0];
            if (is_numeric($versionMajor) && $versionMajor < 200) {
                $navegador .= ' ' . $versionMajor;
            }
        }

        // === SISTEMA OPERATIVO ===
        $platform = $agent->platform();
        if (!$platform || $platform === 'Webkit' || $platform === 'WebKit') {
            if (str_contains($userAgent, 'Windows')) {
                $platform = 'Windows';
            } elseif (str_contains($userAgent, 'Android')) {
                $platform = 'Android';
            } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
                $platform = str_contains($userAgent, 'iPad') ? 'iPadOS' : 'iOS';
            } elseif (str_contains($userAgent, 'Mac OS X') || str_contains($userAgent, 'Macintosh')) {
                $platform = 'macOS';
            } elseif (str_contains($userAgent, 'Linux')) {
                $platform = 'Linux';
            } else {
                $platform = 'Sistema';
            }
        }

        if ($platform === 'AndroidOS')
            $platform = 'Android';
        elseif ($platform === 'OS X')
            $platform = 'macOS';

        $platformVersion = $agent->version($platform);
        $sistema = $platform;
        if ($platformVersion && !str_contains($platform, 'Windows')) {
            $versionMajor = explode('.', (string) $platformVersion)[0];
            if (is_numeric($versionMajor) && $versionMajor < 50) {
                $sistema .= ' ' . $versionMajor;
            }
        }

        // === DISPOSITIVO ===
        $dispositivo = $agent->device();
        if ($dispositivo === 'Webkit' || $dispositivo === 'WebKit' || $dispositivo === false) {
            $dispositivo = null;
        }

        // === ICONO ===
        $icono = 'desktop';
        if ($agent->isPhone() || str_contains($userAgent, 'Mobile')) {
            $icono = 'mobile';
        } elseif ($agent->isTablet() || str_contains($userAgent, 'Tablet')) {
            $icono = 'tablet';
        }

        return compact('navegador', 'sistema', 'dispositivo', 'icono');
    }

    public function cerrarSesionesDeUsuario(User $user)
    {
        $cerradas = Session::where('user_id', $user->id)->delete();

        $user->forceFill(['remember_token' => null])->save();

        return back()->with('success', "Se han cerrado $cerradas sesión(es) activas del usuario {$user->nombre_completo}.");
    }

    /**
     * Cerrar una sesión específica de un usuario (desde panel de admin)
     */
    public function cerrarSesionDeUsuario(User $user, string $sessionId)
    {
        $eliminada = Session::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        if ($eliminada) {
            return back()->with('success', 'Sesión cerrada correctamente.');
        }

        return back()->with('error', 'No se encontró la sesión.');
    }

    public function despedirUsuario(Request $request, User $user)
    {
        DB::transaction(function () use ($user) {

            /* —————————  SEGURIDAD ————————— */
            // Desactivar la cuenta
            $user->update([
                'estado' => 'despedido',      // o tu campo equivalente
                'fecha_baja' => now(),
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
            'diasVacaciones' => $conteos['vacaciones'] ?? 0,
            'faltasInjustificadas' => $conteos['injustificada'] ?? 0,
            'faltasJustificadas' => $conteos['justificada'] ?? 0,
            'diasBaja' => $conteos['baja'] ?? 0,
        ];
    }

    private function getResumenVacaciones(User $user): array
    {
        $añoActual = Carbon::now()->year;
        $fechaIncorporacion = $user->fecha_incorporacion_efectiva;

        // Días de vacaciones por convenio (por defecto 22)
        $diasPorAño = 22;

        // Calcular días que corresponden este año según fecha de incorporación
        if ($fechaIncorporacion && $fechaIncorporacion->year == $añoActual) {
            // Si se incorporó este año, calcular proporcionalmente
            $diasDelAño = Carbon::now()->isLeapYear() ? 366 : 365;
            $diasTrabajados = $fechaIncorporacion->diffInDays(Carbon::now()->endOfYear()) + 1;
            $diasCorresponden = (int) round(($diasTrabajados / $diasDelAño) * $diasPorAño);
        } else {
            $diasCorresponden = $diasPorAño;
        }

        // Días de vacaciones usados este año
        $diasUsados = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $añoActual)
            ->count();

        // Días restantes
        $diasRestantes = max(0, $diasCorresponden - $diasUsados);

        // Solicitudes de vacaciones pendientes
        $solicitudesPendientes = VacacionesSolicitud::where('user_id', $user->id)
            ->where('estado', 'pendiente')
            ->count();

        // Días en solicitudes pendientes (suma de días de todas las solicitudes pendientes)
        $diasEnSolicitudesPendientes = VacacionesSolicitud::where('user_id', $user->id)
            ->where('estado', 'pendiente')
            ->get()
            ->sum(function ($solicitud) {
                return Carbon::parse($solicitud->fecha_inicio)
                    ->diffInDays(Carbon::parse($solicitud->fecha_fin)) + 1;
            });

        return [
            'diasCorresponden' => $diasCorresponden,
            'diasUsados' => $diasUsados,
            'diasRestantes' => $diasRestantes,
            'solicitudesPendientes' => $solicitudesPendientes,
            'diasEnSolicitudesPendientes' => $diasEnSolicitudesPendientes,
            'añoActual' => $añoActual,
        ];
    }

    public function resumenAsistencia(User $user)
    {
        return response()->json($this->getResumenAsistencia($user));
    }

    public function getOperarios(OperarioService $operarioService)
    {
        $operarios = $operarioService->getTodosOperarios();

        return response()->json($operarios);
    }

    /**
     * Obtiene operarios agrupados por empresa para el diálogo de generar turnos.
     * Respuesta dinámica: se adapta automáticamente cuando se añaden nuevas empresas.
     */
    public function getOperariosAgrupados(Request $request, OperarioService $operarioService)
    {
        $fecha = $request->input('fecha');
        $maquinaId = $request->input('maquina_id');

        // El servicio devuelve todos los datos necesarios en una sola llamada optimizada
        $datos = $operarioService->getDatosParaGenerarTurnos($fecha, (int) $maquinaId);

        return response()->json($datos);
    }

    public function generarTurnosCalendario(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'maquina_id' => 'required|exists:maquinas,id',
            'fecha_inicio' => 'required|date',
            'alcance' => 'required|in:un_dia,dos_semanas,resto_año',
            'turno_inicio' => 'nullable|in:mañana,tarde',
            'turno_detectado' => 'required|in:mañana,tarde,noche,diurno',
        ]);

        $user = User::with(['categoria'])->findOrFail($validated['user_id']);

        // Obtener la máquina y su obra_id
        $maquina = Maquina::findOrFail($validated['maquina_id']);
        $obraId = $maquina->obra_id;

        // Obtener colores basados en obra_id (mismo sistema que actualizarPuesto)
        $coloresEventos = [
            1 => ['bg' => '#93C5FD', 'border' => '#60A5FA'], // azul claro
            2 => ['bg' => '#6EE7B7', 'border' => '#34D399'], // verde claro
            3 => ['bg' => '#FDBA74', 'border' => '#F59E0B'], // naranja claro
        ];
        $colorEvento = $coloresEventos[$obraId] ?? ['bg' => '#3b82f6', 'border' => '#3b82f6'];

        // IDs de turnos
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId = Turno::where('nombre', 'noche')->value('id');

        // Determinar rango de fechas
        $inicio = Carbon::parse($validated['fecha_inicio'])->startOfDay();
        if ($validated['alcance'] === 'un_dia') {
            $fin = $inicio->copy();
        } elseif ($validated['alcance'] === 'dos_semanas') {
            // Calcular el viernes de la semana siguiente
            // Primero obtenemos el viernes de esta semana, luego sumamos una semana
            $fin = $inicio->copy()->modify('friday this week')->addWeek();
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

        // Determinar turno inicial según turno_detectado
        $turnoDetectado = $validated['turno_detectado'];

        if ($turnoDetectado === 'diurno') {
            // Para diurno, usar turno_inicio si viene, sino mañana por defecto
            $turnoInicial = $validated['turno_inicio'] ?? 'mañana';
            $turnoAsignado = $turnoInicial === 'mañana' ? $turnoMañanaId : $turnoTardeId;
        } else {
            $turnoAsignado = match ($turnoDetectado) {
                'mañana' => $turnoMañanaId,
                'tarde' => $turnoTardeId,
                'noche' => $turnoNocheId,
                default => $turnoMañanaId,
            };
        }

        $turnosCreados = 0;
        $eventosCreados = [];

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $fechaStr = $fecha->toDateString();
            $esViernes = $fecha->dayOfWeek === Carbon::FRIDAY;

            // Saltar sábados, domingos, festivos y vacaciones
            $esNoLaborable =
                in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
                || in_array($fechaStr, $festivosArray, true)
                || in_array($fechaStr, $diasVacaciones, true);

            if ($esNoLaborable) {
                // Mantener la rotación de viernes aunque se salte el día
                if ($turnoDetectado === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            $asignacion = AsignacionTurno::where('user_id', $user->id)
                ->whereDate('fecha', $fechaStr)
                ->first();

            // No sobrescribir si ese día se marcó con turno 'festivo'
            if ($asignacion && optional($asignacion->turno)->nombre === 'festivo') {
                if ($turnoDetectado === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            if ($asignacion) {
                $asignacion->update([
                    'turno_id' => $turnoAsignado,
                    'maquina_id' => $validated['maquina_id'],
                    'obra_id' => $obraId,
                ]);
                $asignacionActualizada = $asignacion->fresh(['turno', 'obra']);
            } else {
                $asignacionActualizada = AsignacionTurno::create([
                    'user_id' => $user->id,
                    'fecha' => $fechaStr,
                    'turno_id' => $turnoAsignado,
                    'maquina_id' => $validated['maquina_id'],
                    'obra_id' => $obraId,
                    'estado' => 'activo',
                ]);
                $asignacionActualizada->load(['turno', 'obra']);
            }

            // Mapeo visual usando TurnoMapper
            $turnoModel = $asignacionActualizada->turno;
            $slot = TurnoMapper::getSlotParaTurnoModel($turnoModel, $fechaStr);

            // Agregar evento creado a la lista (estructura normalizada)
            $eventosCreados[] = [
                'id' => 'turno-' . $asignacionActualizada->id,
                'title' => $user->name . ' ' . ($user->primer_apellido ?? ''),
                'start' => $slot['start'],
                'end' => $slot['end'],
                'resourceId' => $validated['maquina_id'],
                'backgroundColor' => $colorEvento['bg'],
                'borderColor' => $colorEvento['border'],
                'textColor' => '#000000',
                'extendedProps' => [
                    'user_id' => $user->id,
                    'categoria_nombre' => $user->categoria->nombre ?? null,
                    'turno' => $turnoModel->nombre ?? null,
                    'entrada' => $asignacionActualizada->entrada,
                    'salida' => $asignacionActualizada->salida,
                    'foto' => $user->ruta_imagen,
                    'es_festivo' => false,
                ],
            ];

            $turnosCreados++;

            // Rotación de viernes (para diurno)
            if ($turnoDetectado === 'diurno' && $esViernes) {
                $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Turnos generados correctamente',
            'turnos_creados' => $turnosCreados,
            'eventos' => $eventosCreados,
        ]);
    }
    /**
     * Obtener datos de vacaciones de un usuario para cálculo frontend
     * Incluye desglose por año para el período de gracia (1 enero - 31 marzo)
     * @param User $user
     * @param Request $request - Puede incluir ?fecha=YYYY-MM-DD para calcular relativo a esa fecha
     */
    public function getVacationData(User $user, Request $request)
    {
        // Solo oficina o el propio usuario
        if (auth()->user()->rol !== 'oficina' && auth()->id() !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Usar la fecha clickeada si se proporciona, sino la fecha actual
        $fechaReferencia = $request->input('fecha') ? Carbon::parse($request->input('fecha')) : Carbon::now();
        $clickYear = (int) $fechaReferencia->format('Y');
        $previousYear = $clickYear - 1;

        // === CON VACACIONES FUTURAS (todas las del año) ===

        // Días de vacaciones usados en total (SIN LÍMITE de fecha para contar las futuras también)
        $diasAsignadosTotal = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->count();

        // Días usados del año anterior al clickado (todas las vacaciones del año anterior)
        $diasAsignadosAnterior = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $previousYear)
            ->count();

        // Días usados del año clickado (TODAS las vacaciones del año, incluyendo futuras)
        $diasAsignadosActual = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $clickYear)
            ->count();

        // Días usados durante el período de gracia del año clickado (1 ene - 31 mar)
        // TODAS las del período, incluyendo futuras si las hay
        $diasUsadosPeriodoGracia = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $clickYear)
            ->whereMonth('fecha', '<=', 3) // enero, febrero, marzo
            ->count();

        // Días usados después del período de gracia (1 abril en adelante)
        $diasUsadosPostGracia = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $clickYear)
            ->whereMonth('fecha', '>', 3) // abril en adelante
            ->count();

        // === SIN VACACIONES FUTURAS (solo hasta la fecha clickeada) ===

        // Días usados hasta la fecha clickeada
        $diasUsadosHastaFecha = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereDate('fecha', '<=', $fechaReferencia->toDateString())
            ->count();

        // Días usados del periodo de gracia hasta la fecha clickeada
        $diasUsadosPeriodoGraciaHastaFecha = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $clickYear)
            ->whereMonth('fecha', '<=', 3)
            ->whereDate('fecha', '<=', $fechaReferencia->toDateString())
            ->count();

        // Días usados post-gracia hasta la fecha clickeada
        $diasUsadosPostGraciaHastaFecha = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $clickYear)
            ->whereMonth('fecha', '>', 3)
            ->whereDate('fecha', '<=', $fechaReferencia->toDateString())
            ->count();

        // === VACACIONES FUTURAS (después de la fecha clickeada) ===
        // Cuenta TODAS las vacaciones posteriores a la fecha clickeada, de cualquier año
        $diasVacacionesFuturas = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereDate('fecha', '>', $fechaReferencia->toDateString())
            ->count();

        return response()->json([
            'fecha_incorporacion' => $user->fecha_incorporacion_efectiva ? $user->fecha_incorporacion_efectiva->format('Y-m-d') : null,
            'dias_asignados' => $diasAsignadosTotal,
            'dias_asignados_anterior' => $diasAsignadosAnterior,
            'dias_asignados_actual' => $diasAsignadosActual,
            'dias_usados_periodo_gracia' => $diasUsadosPeriodoGracia,
            'dias_usados_post_gracia' => $diasUsadosPostGracia,
            // Sin vacaciones futuras
            'dias_usados_hasta_fecha' => $diasUsadosHastaFecha,
            'dias_usados_periodo_gracia_hasta_fecha' => $diasUsadosPeriodoGraciaHastaFecha,
            'dias_usados_post_gracia_hasta_fecha' => $diasUsadosPostGraciaHastaFecha,
            // Nuevo: vacaciones futuras para mostrar en el frontend
            'dias_vacaciones_futuras' => $diasVacacionesFuturas,
            'year_anterior' => $previousYear,
            'year_actual' => $clickYear,
            'fecha_referencia' => $fechaReferencia->toDateString(),
        ]);
    }

    /**
     * Obtener fichajes de un usuario para un rango de fechas
     * Usado por el sistema de solicitud de revisión de fichajes
     */
    public function getFichajesRango(Request $request, $userId)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        // Solo el propio usuario o usuarios con rol oficina pueden ver esto
        $user = User::findOrFail($userId);
        if (Auth::id() != $userId && Auth::user()->rol !== 'oficina') {
            return response()->json(['error' => 'No tienes permisos.'], 403);
        }

        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

        $fichajes = [];

        foreach ($periodo as $fecha) {
            // Saltar fines de semana
            if ($fecha->isWeekend()) {
                continue;
            }

            $fechaStr = $fecha->toDateString();

            $asignacion = AsignacionTurno::with('turno')
                ->where('user_id', $userId)
                ->whereDate('fecha', $fechaStr)
                ->first();

            $fichajes[] = [
                'fecha' => $fechaStr,
                'turno' => $asignacion?->turno?->nombre ?? null,
                'entrada' => $asignacion?->entrada ? substr($asignacion->entrada, 0, 5) : null,
                'salida' => $asignacion?->salida ? substr($asignacion->salida, 0, 5) : null,
                'entrada2' => $asignacion?->entrada2 ? substr($asignacion->entrada2, 0, 5) : null,
                'salida2' => $asignacion?->salida2 ? substr($asignacion->salida2, 0, 5) : null,
                'completo' => $asignacion && $asignacion->entrada && $asignacion->salida,
            ];
        }

        return response()->json([
            'user_id' => $userId,
            'user_nombre' => $user->nombre_completo,
            'fichajes' => $fichajes,
        ]);
    }
}
