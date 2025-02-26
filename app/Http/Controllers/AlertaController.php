<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AlertaController extends Controller
{

    private function aplicarFiltros($query)
    {
        $usuario = Auth::user();

        // Aplicar orden por fecha de creación descendente para que la más reciente sea la primera
        $query->orderBy('id', 'desc');

        // Filtrar por destinatario según la categoría del usuario (excepto administradores)
        if ($usuario->categoria !== 'administrador') {
            $query->where('destinatario', $usuario->categoria);
        } elseif (request()->filled('destinatario') && request('destinatario') !== 'todos') {
            // Si es administrador y ha seleccionado un destinatario específico
            $query->where('destinatario', request('destinatario'));
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
        $perPage = request('per_page', 10); // Valor por defecto: 10
        return $query->paginate($perPage);
    }


    public function index()
    {
        try {
            DB::beginTransaction();
    
            // Verificar si el usuario está autenticado
            $usuario = Auth::user();
            if (!$usuario) {
                return redirect()->route('login')->with('swal_error', 'Debe iniciar sesión para ver las alertas.');
            }
    
            // Obtener alertas con filtros aplicados
            $query = Alerta::orderBy('created_at', 'desc');
            $alertas = $this->aplicarFiltros($query);
    
            // Obtener IDs de alertas que el usuario NO ha leído aún
            $alertasNoLeidas = $query->whereDoesntHave('usuariosQueLeen', function ($q) use ($usuario) {
                $q->where('user_id', $usuario->id);
            })->get();
    
            // Registrar la lectura para este usuario
            foreach ($alertasNoLeidas as $alerta) {
                $alerta->usuariosQueLeen()->attach($usuario->id, ['leida_en' => now()]);
            }
    
            DB::commit();
    
            return view('alertas.index', compact('alertas', 'alertasNoLeidas'));
        } catch (\Exception $e) {
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
            return response()->json(['cantidad' => 0]); // Si no está autenticado, devolver 0
        }

        $query = Alerta::where('leida', false);

        // 🔹 Si NO es administrador, filtrar por destinatario
        if ($usuario->categoria !== 'administrador') {
            $query->where('destinatario', $usuario->categoria);
        }

        // Contar alertas sin leer (según el filtro aplicado)
        $cantidad = $query->count();

        return response()->json(['cantidad' => $cantidad]);
    }

    public function store(Request $request)
    {
        // Validar los datos de la alerta
        $request->validate([
            'mensaje' => 'required|string|max:255',
            'destinatario' => 'required|in:desarrollador,administracion,mecanico', // Ajusta según los destinatarios válidos
            'user_id_2' => 'nullable|exists:users,id' // Validación para asegurar que es un usuario válido
        ]);
        // Verificar si la sesión tiene el valor esperado
        $companeroId = session()->get('companero_id', null);


        try {
            // Crear una nueva alerta
            Alerta::create([
                'mensaje' => $request->mensaje,
                'destinatario' => $request->destinatario,
                'user_id_1' => Auth::id(), // Usuario que crea la alerta
                'user_id_2' => session()->get('compañero_id', null),
                'leida' => false, // Se marca como no leída por defecto
            ]);

            return redirect()->back()->with('swal_success', 'Alerta creada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('swal_error', 'Hubo un error al crear la alerta.');
        }
    }
}
