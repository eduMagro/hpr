<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\AlertaLeida;
use App\Models\User;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AlertaController extends Controller
{
    private function aplicarFiltros($query)
    {
        $usuario = Auth::user();

        // Aplicar orden por fecha de creación descendente
        $query->orderBy('id', 'desc');

        // Filtrar por destino (rol) o destinatario (categoría), excepto administradores
        if ($usuario->name !== 'Eduardo Magro Lemus') {
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


    public function index()
    {
        $user = Auth::user();

        $alertas = Alerta::where(function ($query) use ($user) {
            $query->where('user_id_1', $user->id)
                ->orWhere('user_id_2', $user->id)
                ->orWhere('destino', $user->rol)
                ->orWhere('destinatario', $user->categoria_id)
                ->orWhere('destinatario_id', $user->id);
        })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Obtener registros de alertas leídas por este usuario
        $leidas = AlertaLeida::where('user_id', $user->id)->get()->keyBy('alerta_id');
        $alertasLeidas = $leidas->mapWithKeys(fn($r) => [$r->alerta_id => $r->leida_en])->all();

        $roles = User::distinct()->pluck('rol')->filter()->values();
        $categorias = Categoria::distinct()->pluck('nombre')->filter()->values();
        $usuarios = User::orderBy('name')->get();

        return view('alertas.index', compact('alertas', 'user', 'roles', 'categorias', 'usuarios', 'alertasLeidas'));
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
        $user = auth()->user();

        // Obtener IDs de alertas entrantes para este usuario
        $alertasLeidas = AlertaLeida::where('user_id', $user->id)
            ->whereNull('leida_en')
            ->pluck('alerta_id');

        // Verificar si esas alertas son realmente para él (rol, categoría o id)
        $cantidad = Alerta::whereIn('id', $alertasLeidas)
            ->where(function ($q) use ($user) {
                $q->where('destinatario_id', $user->id)
                    ->orWhere('destino', $user->rol)
                    ->orWhere('destinatario', $user->categoria);
            })
            ->count();

        return response()->json(['cantidad' => $cantidad]);
    }

    public function store(Request $request)
    {
        // Validar que se proporcione solo un destino: rol, categoría o destinatario_id
        $request->validate([
            'mensaje' => 'required|string',
            'rol' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    if (!empty($value) && (!empty($request->categoria) || !empty($request->destinatario_id))) {
                        $fail('No puedes seleccionar más de un destino. Elige solo uno entre rol, categoría o destinatario específico.');
                    }
                }
            ],
            'categoria' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    if (!empty($value) && (!empty($request->rol) || !empty($request->destinatario_id))) {
                        $fail('No puedes seleccionar más de un destino. Elige solo uno entre rol, categoría o destinatario específico.');
                    }
                }
            ],
            'destinatario_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request) {
                    if (!empty($value) && (!empty($request->rol) || !empty($request->categoria))) {
                        $fail('No puedes seleccionar un destinatario específico y además otro destino (rol o categoría).');
                    }
                }
            ],
        ], [
            'mensaje.required' => 'El mensaje es obligatorio.',
            'rol.string' => 'El rol debe ser una cadena de caracteres.',
            'categoria.string' => 'La categoría debe ser una cadena de caracteres.',
            'destinatario_id.integer' => 'El destinatario debe ser un número entero.',
            'destinatario_id.exists' => 'El destinatario seleccionado no existe en la base de datos.',
        ]);

        // Verificar que se haya seleccionado al menos un destino
        if (empty($request->rol) && empty($request->categoria) && empty($request->destinatario_id)) {
            return redirect()->back()->with(['error' => 'Debes elegir un destino: rol, categoría o destinatario específico.'], 500);
        }

        try {
            // Preparar los datos base de la alerta
            $data = [
                'mensaje'   => $request->mensaje,
                'user_id_1' => Auth::id(),
                'user_id_2' => session()->get('companero_id', null),
                'leida'     => false,
            ];

            // Dependiendo del destino seleccionado, asignar los campos correspondientes.
            if (!empty($request->rol)) {
                $data['destino'] = $request->rol;
                $data['destinatario'] = null;
                $data['destinatario_id'] = null;
            } elseif (!empty($request->categoria)) {
                $data['destinatario'] = $request->categoria;
                $data['destino'] = null;
                $data['destinatario_id'] = null;
            } elseif (!empty($request->destinatario_id)) {
                $data['destinatario_id'] = $request->destinatario_id;
                $data['destino'] = null;
                $data['destinatario'] = null;
            }

            $alerta = Alerta::create($data); // Guardamos la alerta y obtenemos su instancia

            $usuariosDestino = collect(); // Colección vacía por defecto

            // Determinar a quién se envía la alerta
            if (!empty($request->rol)) {
                $usuariosDestino = User::where('rol', $request->rol)->get();
            } elseif (!empty($request->categoria)) {
                $usuariosDestino = User::where('categoria_id', $request->categoria)->get();
            } elseif (!empty($request->destinatario_id)) {
                $usuariosDestino = User::where('id', $request->destinatario_id)->get();
            }

            // Crear registros en la tabla alertas_leidas
            foreach ($usuariosDestino as $usuario) {
                AlertaLeida::create([
                    'alerta_id' => $alerta->id,
                    'user_id' => $usuario->id,
                    'leida_en' => null,
                ]);
            }
            return redirect()->back()->with('success', 'Alerta enviada correctamente.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return redirect()->back()->with(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }
}
