<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AlertaController extends Controller
{
    public function index()
    {
        try {
            DB::beginTransaction();

            // Obtener el usuario autenticado
            $usuario = Auth::user();
            if (!$usuario) {
                return redirect()->route('login')->with('swal_error', 'Debe iniciar sesión para ver las alertas.');
            }

            // Filtrar alertas según la categoría del usuario
            $query = Alerta::orderBy('created_at', 'desc');

            if ($usuario->categoria === 'administracion') {
                $query->where('destinatario', 'administracion');
            } elseif ($usuario->categoria === 'mecanico') {
                $query->where('destinatario', 'mecanico');
            } elseif ($usuario->categoria === 'desarrollador') { // Nueva categoría
                $query->where('destinatario', 'desarrollador');
            }

            // Obtener alertas paginadas
            $alertas = $query->paginate(10);

            // Clonar la consulta para obtener alertas no leídas antes de marcarlas como leídas
            $alertasNoLeidas = (clone $query)->where('leida', false)->get();

            // Marcar todas las alertas como leídas
            Alerta::whereIn('id', $alertasNoLeidas->pluck('id'))->update(['leida' => true]);

            DB::commit();

            return view('alertas.index', compact('alertas', 'alertasNoLeidas'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('alertas.index')->with('swal_error', 'Ocurrió un error al cargar las alertas.');
        }
    }

    /**
     * Devuelve la cantidad de alertas sin leer (para mostrar la exclamación en la navbar).
     */
    public function alertasSinLeer()
    {
        // Obtener el usuario autenticado
        $usuario = Auth::user();
        if (!$usuario) {
            return response()->json(['cantidad' => 0]); // Si no está autenticado, devolver 0
        }

        // Definir la consulta base
        $query = Alerta::where('leida', 0);

        // Filtrar alertas según la categoría del usuario
        if ($usuario->categoria === 'administracion') {
            $query->where('destinatario', 'administracion');
        } elseif ($usuario->categoria === 'mecanico') {
            $query->where('destinatario', 'mecanico');
        } elseif ($usuario->categoria === 'desarrollador') { // Nueva categoría
            $query->where('destinatario', 'desarrollador');
        }

        // Contar las alertas sin leer filtradas
        $alertasSinLeer = $query->count();

        return response()->json(['cantidad' => $alertasSinLeer]);
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
                'user_id_2' => session()->get('compañero_id', null), // Sin tilde en 'compañero'
                'leida' => false, // Se marca como no leída por defecto
            ]);

            return redirect()->back()->with('swal_success', 'Alerta creada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('swal_error', 'Hubo un error al crear la alerta.');
        }
    }
}
