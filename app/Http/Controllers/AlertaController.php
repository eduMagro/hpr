<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\AlertaLeida;
use App\Models\User;
use App\Models\Departamento;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AlertaController extends Controller
{
    private function aplicarFiltros($query)
    {
        $usuario = Auth::user();

        // Aplicar orden por fecha de creación descendente
        $query->orderBy('id', 'desc');

        // Filtrar por destino (rol) o destinatario (categoría), excepto administradores
        if ($usuario->nombre_completo !== 'antes eduardo magro lemus, ahora ninguno') {
            $query->where(function ($q) use ($usuario) {
                $q->where('destino', $usuario->rol)
                    ->orWhere('destinatario', $usuario->categoria)
                    ->orWhere('destinatario_id', $usuario->id);
            });
        } elseif (request()->filled('categoria') && request('categoria') !== 'todos') {
            // Si el administrador ha seleccionado un destinatario específico
            $query->where('destinatario', request('categoria'));
        } elseif (request()->filled('rol')) {
            // Si el administrador ha seleccionado un destino específico
            $query->where('destino', request('rol'));
        }

        // Filtrar por ID de la alerta
        if (request()->filled('alerta_id')) {
            $query->where('id', request('alerta_id'));
        }

        // Filtrar por Usuario 1
        if (request()->filled('usuario1')) {
            $query->whereHas('usuario1', function ($q) {
                $q->where('name', 'like', '%' . request('usuario1') . '%');
            });
        }

        // Filtrar por Usuario 2
        if (request()->filled('usuario2')) {
            $query->whereHas('usuario2', function ($q) {
                $q->where('name', 'like', '%' . request('usuario2') . '%');
            });
        }

        // Filtrar por mensaje
        if (request()->filled('mensaje')) {
            $query->where('mensaje', 'like', '%' . request('mensaje') . '%');
        }

        // Filtrar por fecha de creación
        if (request()->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', request('fecha_inicio'));
        }

        if (request()->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', request('fecha_fin'));
        }

        // Filtrar por cantidad de registros por página
        $perPage = request('per_page', 10);
        return $query->paginate($perPage);
    }
    private function filtrosActivosAlertas(Request $request): array
    {
        $filtros = [];

        if ($request->filled('alerta_id')) {
            $filtros[] = 'ID: <strong>' . $request->alerta_id . '</strong>';
        }

        if ($request->filled('emisor')) {
            $filtros[] = 'Emisor: <strong>' . $request->emisor . '</strong>';
        }

        if ($request->filled('receptor')) {
            $filtros[] = 'Receptor: <strong>' . $request->receptor . '</strong>';
        }

        if ($request->filled('destino')) {
            $filtros[] = 'Destino: <strong>' . $request->destino . '</strong>';
        }

        if ($request->filled('destinatario')) {
            $filtros[] = 'Destinatario: <strong>' . $request->destinatario . '</strong>';
        }

        if ($request->filled('mensaje')) {
            $filtros[] = 'Mensaje contiene: <strong>' . $request->mensaje . '</strong>';
        }
        if ($request->filled('tipo')) {
            $filtros[] = 'Tipo contiene: <strong>' . $request->tipo . '</strong>';
        }

        if ($request->filled('fecha_creada')) {
            $filtros[] = 'Fecha creada: <strong>' . $request->fecha_creada . '</strong>';
        }

        if ($request->filled('fecha_actualizada')) {
            $filtros[] = 'Fecha actualizada: <strong>' . $request->fecha_actualizada . '</strong>';
        }

        if ($request->filled('sort')) {
            $orden = $request->order === 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . $request->sort . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page_todas')) {
            $filtros[] = 'Mostrando <strong>' . $request->per_page_todas . '</strong> registros por página';
        }

        return $filtros;
    }

    private function getOrdenamientoAlertas(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = '';
        if ($isSorted) {
            $icon = $currentOrder === 'asc' ? '▲' : '▼';
        } else {
            $icon = '⇅';
        }

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="inline-flex items-center space-x-1">' .
            '<span>' . $titulo . '</span><span class="text-xs">' . $icon . '</span></a>';
    }

    private function aplicarFiltrosAlertas($query, Request $request)
    {
        if ($request->filled('alerta_id')) {
            $query->where('id', $request->alerta_id);
        }

        if ($request->filled('emisor')) {
            $query->whereHas('usuario1', function ($q) use ($request) {
                $q->where(DB::raw("CONCAT(users.name, ' ', users.primer_apellido, ' ', users.segundo_apellido)"), 'like', '%' . $request->emisor . '%');
            });
        }

        if ($request->filled('receptor')) {
            $query->whereHas('usuario2', function ($q) use ($request) {
                $q->where(DB::raw("CONCAT(users.name, ' ', users.primer_apellido, ' ', users.segundo_apellido)"), 'like', '%' . $request->receptor . '%');
            });
        }

        if ($request->filled('destino')) {
            $query->where('destino', 'like', '%' . $request->destino . '%');
        }

        if ($request->filled('destinatario')) {
            $query->where('destinatario', 'like', '%' . $request->destinatario . '%');
        }

        if ($request->filled('mensaje')) {
            $query->where('mensaje', 'like', '%' . $request->mensaje . '%');
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', 'like', '%' . $request->tipo . '%');
        }

        if ($request->filled('fecha_creada')) {
            $query->whereDate('created_at', Carbon::parse($request->fecha_creada)->format('Y-m-d'));
        }

        if ($request->filled('fecha_actualizada')) {
            $query->whereDate('updated_at', Carbon::parse($request->fecha_actualizada)->format('Y-m-d'));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $categoriaNombre = optional($user->categoriaRelacion)->nombre ?? $user->categoria;
        $perPage = $request->input('per_page', 10); // valor por defecto 10
        $perPageTodas = $request->input('per_page_todas', 20); // por defecto 20
        $alertas = Alerta::whereHas('leidas', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->orWhere('user_id_1', $user->id) // permite ver las enviadas por él mismo
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);


        // Clasificar cada alerta y añadir mensajes
        $alertas->getCollection()->transform(function ($alerta) use ($user, $categoriaNombre) {
            $esEmisor = $alerta->user_id_1 === $user->id;

            $esParaUsuario   = $alerta->destinatario_id === $user->id;
            $esParaRol       = $alerta->destino === $user->rol;
            $esParaCategoria = $alerta->destinatario === $categoriaNombre;

            $esEntrante = $esParaUsuario || (!$esEmisor && ($esParaRol || $esParaCategoria));

            $alerta->tipo = $esEntrante ? 'entrante' : 'saliente';

            // Añadir versión corta y completa del mensaje
            $alerta->mensaje_completo = $alerta->mensaje;
            $alerta->mensaje_corto = Str::words($alerta->mensaje, 4, '...');

            return $alerta;
        });

        $leidas = AlertaLeida::where('user_id', $user->id)->get()->keyBy('alerta_id');
        $alertasLeidas = $leidas->mapWithKeys(fn($r) => [$r->alerta_id => $r->leida_en])->all();
$tiposAlerta = Alerta::distinct()->pluck('tipo')->filter()->values();

        $roles = User::distinct()->pluck('rol')->filter()->values();
        $categorias = Categoria::distinct()->pluck('nombre')->filter()->values();
        $usuarios = User::orderBy('name')->get();
        $todasLasAlertas = collect();
        $esAdministrador = $user->esAdminDepartamento();
$ordenablesAlertas = [];
        if ($esAdministrador) {
            $query = Alerta::with(['usuario1', 'usuario2', 'destinatarioUser']);
            $query = $this->aplicarFiltrosAlertas($query, $request);

            // ordenamiento
            $sortBy = $request->input('sort', 'created_at');
            $order  = $request->input('order', 'desc');
            $query->orderBy($sortBy, $order);

            $todasLasAlertas = $query->paginate($perPageTodas, ['*'], 'todas_alertas_page')
                ->appends($request->except('todas_alertas_page'));

            // texto filtros activos
            $filtrosActivos = $this->filtrosActivosAlertas($request);
            $ordenablesAlertas = [
                'id'            => $this->getOrdenamientoAlertas('id', 'ID'),
                'user_id_1'     => $this->getOrdenamientoAlertas('user_id_1', 'Emisor'),
                'user_id_2'     => $this->getOrdenamientoAlertas('user_id_2', 'Compañero'),
                'destino'       => $this->getOrdenamientoAlertas('destino', 'Destino'),
                'destinatario'  => $this->getOrdenamientoAlertas('destinatario', 'Destinatario'),
                'tipo'          => $this->getOrdenamientoAlertas('tipo', 'Tipo'),
                'created_at'    => $this->getOrdenamientoAlertas('created_at', 'Creada'),
                'updated_at'    => $this->getOrdenamientoAlertas('updated_at', 'Actualizada'),
            ];
        } else {
            $todasLasAlertas = collect();
            $filtrosActivos = [];
        }

        return view('alertas.index', compact(
            'alertas',
            'user',
            'roles',
            'categorias',
            'usuarios',
            'alertasLeidas',
            'todasLasAlertas',
            'esAdministrador',
            'perPage',
            'perPageTodas',
            'filtrosActivos',
            'ordenablesAlertas',
            'tiposAlerta'
        ));
    }

    public function marcarLeidas(Request $request)
    {
        $userId = Auth::id();
        $ids = $request->input('alerta_ids', []);

        if (!empty($ids)) {
            AlertaLeida::where('user_id', $userId)
                ->whereNull('leida_en')
                ->whereIn('alerta_id', $ids)
                ->update(['leida_en' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function sinLeer()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                Log::warning('🔐 Usuario no autenticado al acceder a alertas/sin-leer');
                return response()->json(['error' => 'No autenticado'], 401);
            }

            $alertasEntrantes = Alerta::where(function ($q) use ($user) {
                $q->where('destinatario_id', $user->id)
                    ->orWhere('destino', $user->rol)
                    ->orWhere('destinatario', $user->categoria);
            })->pluck('id');

            // Log::info('🔎 Alertas entrantes encontradas', ['total' => $alertasEntrantes->count()]);

            $alertasLeidas = AlertaLeida::where('user_id', $user->id)
                ->whereNull('leida_en')
                ->whereIn('alerta_id', $alertasEntrantes)
                ->count();

            // Log::info('📬 Alertas sin leer', ['cantidad' => $alertasLeidas]);

            return response()->json(['cantidad' => $alertasLeidas]);
        } catch (\Throwable $e) {
            Log::error('❌ Error en sinLeer()', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'mensaje' => config('app.debug') ? $e->getMessage() : 'Ocurrió un error'
            ], 500);
        }
    }

    public function show($id)
    {
        // Solo para evitar el error por ahora
        return redirect()->route('alertas.index');
    }


    public function update(Request $request, $id)
    {
        $alerta = Alerta::findOrFail($id);

        $validated = $request->validate([
            'mensaje' => 'required|string|max:1000',
        ]);

        $alerta->mensaje = $validated['mensaje'];
        $alerta->save();

        return response()->json(['success' => true]);
    }
    public function destroy($id)
    {
        $alerta = Alerta::findOrFail($id);
        $alerta->delete();

        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            $esOficina = $user->rol === 'oficina';
            $usuariosDestino = collect(); // colección vacía
            $alerta = null;

            // 🔹 CASO 1: ENVÍO DIRECTO A DEPARTAMENTOS (API o JS)
            if ($request->has('enviar_a_departamentos')) {
                $departamentosRaw = $request->input('enviar_a_departamentos');

                if (is_string($departamentosRaw)) {
                    $departamentos = array_map('trim', explode(',', $departamentosRaw));
                } elseif (is_array($departamentosRaw)) {
                    $departamentos = array_map('trim', $departamentosRaw);
                } else {
                    $departamentos = [];
                }

                $request->validate([
                    'mensaje' => 'required|string',
                ]);

                $departamentosPermitidos = Departamento::pluck('nombre')->toArray();
                $departamentos = array_filter(
                    $departamentos,
                    fn($nombre) => in_array($nombre, $departamentosPermitidos)
                );

                $usuariosDestino = User::whereHas('departamentos', function ($q) use ($departamentos) {
                    $q->whereIn('nombre', $departamentos);
                })->get();

                if ($usuariosDestino->isEmpty()) {
                    throw new Exception('No hay usuarios en los departamentos seleccionados.');
                }

                $alerta = Alerta::create([
                    'mensaje'   => $request->mensaje,
                    'user_id_1' => $user->id,
                    'user_id_2' => session()->get('companero_id', null),
                    'leida'     => false,
                ]);

                foreach ($usuariosDestino as $usuario) {
                    AlertaLeida::firstOrCreate([
                        'alerta_id' => $alerta->id,
                        'user_id'   => $usuario->id,
                    ], [
                        'leida_en' => null,
                    ]);
                }

                return $request->wantsJson()
                    ? response()->json(['success' => true])
                    : redirect()->back()->with('success', 'Alerta enviada correctamente.');
            }

            // 🔹 CASO 2: USUARIO OFICINA CON FORMULARIO MANUAL
            if ($esOficina) {
                $request->validate([
                    'mensaje' => 'required|string',
                    'rol' => [
                        'nullable',
                        'string',
                        function ($attribute, $value, $fail) use ($request) {
                            if (!empty($value) && (!empty($request->categoria) || !empty($request->destinatario_id))) {
                                $fail('No puedes seleccionar más de un destino.');
                            }
                        }
                    ],
                    'categoria' => [
                        'nullable',
                        'string',
                        function ($attribute, $value, $fail) use ($request) {
                            if (!empty($value) && (!empty($request->rol) || !empty($request->destinatario_id))) {
                                $fail('No puedes seleccionar más de un destino.');
                            }
                        }
                    ],
                    'destinatario_id' => [
                        'nullable',
                        'integer',
                        'exists:users,id',
                        function ($attribute, $value, $fail) use ($request) {
                            if (!empty($value) && (!empty($request->rol) || !empty($request->categoria))) {
                                $fail('No puedes seleccionar un destinatario y otro destino.');
                            }
                        }
                    ],
                ]);

                if (empty($request->rol) && empty($request->categoria) && empty($request->destinatario_id)) {
                    throw new \Exception('Debes elegir un destino: rol, categoría o destinatario específico.');
                }

                $data = [
                    'mensaje'   => $request->mensaje,
                    'user_id_1' => $user->id,
                    'user_id_2' => session()->get('companero_id', null),
                    'leida'     => false,
                ];

                if (!empty($request->rol)) {
                    $data['destino'] = $request->rol;
                    $usuariosDestino = User::where('rol', $request->rol)->get();
                } elseif (!empty($request->categoria)) {
                    $data['destinatario'] = $request->categoria;
                    $usuariosDestino = User::where('categoria_id', $request->categoria)->get();
                } elseif (!empty($request->destinatario_id)) {
                    $data['destinatario_id'] = $request->destinatario_id;
                    $usuariosDestino = User::where('id', $request->destinatario_id)->get();
                }

                $alerta = Alerta::create($data);
            }

            // 🔹 CASO 3: USUARIO OPERARIO ENVÍA MENSAJE Y LLEGA A DEPARTAMENTOS POR DEFECTO
            if (!$esOficina && !$request->has('enviar_a_departamentos')) {
                $usuariosDestino = User::whereHas('departamentos', function ($q) {
                    $q->whereIn('nombre', 'Programador');
                })->get();

                $alerta = Alerta::create([
                    'mensaje'   => $request->mensaje,
                    'user_id_1' => $user->id,
                    'user_id_2' => session()->get('companero_id', null),
                    'leida'     => false,
                ]);
            }

            foreach ($usuariosDestino as $destinatario) {
                AlertaLeida::create([
                    'alerta_id' => $alerta->id,
                    'user_id'   => $destinatario->id,
                    'leida_en'  => null,
                ]);
            }

            return $request->wantsJson()
                ? response()->json(['success' => true])
                : redirect()->back()->with('success', 'Alerta enviada correctamente.');
        } catch (\Throwable $e) {
            // Log del error para el programador
            Log::error('❌ Error en envío de alerta: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
