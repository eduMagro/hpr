<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
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
        if ($usuario->especialidad !== 'programador') {
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
        // Obtener todos los roles y categorías únicas desde la tabla users
        $roles = User::distinct()->pluck('rol')->filter()->values();
        $categorias = Categoria::distinct()->pluck('nombre')->filter()->values();
        // Obtener todos los usuarios para el select del modal (puedes ajustar el orden o el filtro según necesites)
        $usuarios = User::orderBy('name')->get();
        $alertas = Alerta::where(function ($query) use ($user) {
            $query->where('user_id_1', $user->id)                      // Alerta directa
                ->orWhere('user_id_2', $user->id)                    // Mismo rol
                ->orWhere('destino', $user->rol)                    // Mismo rol
                ->orWhere('destinatario', $user->categoria_id)     // Misma categoría
                ->orWhere('destinatario_id', $user->id);           // Alerta personal
        })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('alertas.index', compact('alertas', 'user', 'roles', 'categorias', 'usuarios'));
    }

    /**
     * Devuelve la cantidad de alertas sin leer (para mostrar la exclamación en la navbar).
     */
    // public function alertasSinLeer()
    // {
    //     $usuario = Auth::user();
    //     if (!$usuario) {
    //         return response()->json(['cantidad' => 0]);
    //     }

    //     // Buscar alertas que el usuario NO ha leído
    //     $cantidad = Alerta::whereDoesntHave('usuariosQueLeen', function ($q) use ($usuario) {
    //         $q->where('user_id', $usuario->id);
    //     })
    //         ->when($usuario->especialidad !== 'programador', function ($q) use ($usuario) {
    //             $q->where(function ($subQuery) use ($usuario) {
    //                 $subQuery->where('destino', $usuario->rol)  // Coincide con el rol del usuario
    //                     ->orWhere('destinatario', $usuario->categoria) // Coincide con la categoría del usuario
    //                     ->orWhere('destinatario_id', $usuario->id); // Coincide con la categoría del usuario
    //             });
    //         })
    //         ->count();

    //     return response()->json(['cantidad' => $cantidad]);
    // }
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

            // Crear la alerta con los datos preparados
            Alerta::create($data);

            return redirect()->back()->with('success', 'Alerta enviada correctamente.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return redirect()->back()->with(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }
}
