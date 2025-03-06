<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\User;
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
        if ($usuario->categoria !== 'programador') {
            $query->where(function ($q) use ($usuario) {
                $q->where('destino', $usuario->rol)
                    ->orWhere('destinatario', $usuario->categoria);
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
        try {
            DB::beginTransaction();

            $usuario = Auth::user();
            if (!$usuario) {
                return redirect()->route('login')->with('error', 'Debe iniciar sesión para ver las alertas.');
            }

            // Obtener todos los roles y categorías únicas desde la tabla users
            $roles = User::distinct()->pluck('rol')->filter()->values();
            $categorias = User::distinct()->pluck('categoria')->filter()->values();

            // Obtener TODAS las alertas no leídas antes de aplicar paginación
            $alertasNoLeidas = Alerta::whereDoesntHave('usuariosQueLeen', function ($q) use ($usuario) {
                $q->where('user_id', $usuario->id);
            })
                ->when($usuario->categoria !== 'programador', function ($q) use ($usuario) {
                    $q->where(function ($query) use ($usuario) {
                        $query->where('destino', $usuario->rol)
                            ->orWhere('destinatario', $usuario->categoria);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            // Registrar la lectura SOLO para las alertas que cumplen la condición de destinatario o destino
            foreach ($alertasNoLeidas as $alerta) {
                if (!$alerta->usuariosQueLeen()->where('user_id', $usuario->id)->exists()) {
                    $alerta->usuariosQueLeen()->attach($usuario->id, ['leida_en' => now()]);
                }
            }

            // Aplicar filtros y obtener alertas paginadas
            $query = Alerta::orderBy('created_at', 'desc');
            $alertas = $this->aplicarFiltros($query);

            DB::commit();

            return view('alertas.index', compact('alertas', 'alertasNoLeidas', 'roles', 'categorias'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('alertas.index')->with('error', 'Ocurrió un error al cargar las alertas.');
        }
    }

    /**
     * Devuelve la cantidad de alertas sin leer (para mostrar la exclamación en la navbar).
     */
    public function alertasSinLeer()
    {
        $usuario = Auth::user();
        if (!$usuario) {
            return response()->json(['cantidad' => 0]);
        }

        // Buscar alertas que el usuario NO ha leído
        $cantidad = Alerta::whereDoesntHave('usuariosQueLeen', function ($q) use ($usuario) {
            $q->where('user_id', $usuario->id);
        })
            ->when($usuario->categoria !== 'programador', function ($q) use ($usuario) {
                $q->where(function ($subQuery) use ($usuario) {
                    $subQuery->where('destino', $usuario->rol)  // Coincide con el rol del usuario
                        ->orWhere('destinatario', $usuario->categoria); // Coincide con la categoría del usuario
                });
            })
            ->count();

        return response()->json(['cantidad' => $cantidad]);
    }
    public function store(Request $request)
    {
        // Validar los datos de la alerta asegurando que SOLO uno (rol o categoría) sea seleccionado
        $request->validate([
            'mensaje' => 'required|string',
            'rol' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    if (!empty($value) && !empty($request->categoria)) {
                        $fail('No puedes seleccionar tanto destino como destinatario. Debes elegir solo uno.');
                    }
                }
            ],
            'categoria' => [
                'nullable',
                'string',
            ],
            'user_id_2' => 'nullable|exists:users,id'
        ], [
            'mensaje.required' => 'El mensaje es obligatorio.',
            'rol.max' => 'Máximo 255 caracteres',
            'categoria.max' => 'Máximo 255 caracteres',
            'user_id_2.exists' => 'El usuario 2 no existe',
        ]);

        // Verificar que al menos uno (destino o destinatario) esté presente
        if (empty($request->rol) && empty($request->categoria)) {

            return redirect()->back()->with('error', 'Debes seleccionar un destino o un destinatario.');
        }

        try {
            // Crear una nueva alerta
            Alerta::create([
                'mensaje' => $request->mensaje,
                'destino' => $request->rol,
                'destinatario' => $request->categoria,
                'user_id_1' => Auth::id(),
                'user_id_2' => session()->get('companero_id', null),
                'leida' => false,
            ]);

            return redirect()->back()->with('success', 'Alerta creada correctamente.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error inesperado: ' . $e->getMessage());
        }
    }
}
