<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroFichaje;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegistroFichajeController extends Controller
{
    /**
     * Mostrar lista de fichajes.
     */
    public function index()
    {
        $fichajes = RegistroFichaje::with('user')->latest()->paginate(10);
        return view('users.show', compact('fichajes'));
    }

    /**
     * Mostrar formulario para crear un nuevo fichaje.
     */
    public function create()
    {
        $usuarios = User::all(); // Obtener todos los usuarios para el formulario
        return view('registros_fichajes.create', compact('usuarios'));
    }

    /**
     * Guardar un nuevo fichaje en la base de datos.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'tipo' => 'required|in:entrada,salida',
            ]);
    
            // Verificar si el usuario ya tiene un registro de fichaje hoy
            $fichaje = RegistroFichaje::where('user_id', $request->user_id)
                ->whereDate('entrada', now()->toDateString())
                ->first();
    
            if ($request->tipo === 'entrada') {
                if ($fichaje) {
                    return redirect()->route('users.index')->with('error', 'Ya has registrado una entrada hoy.');
                }
                RegistroFichaje::create([
                    'user_id' => $request->user_id,
                    'entrada' => now(), // Se almacena la hora de entrada
                    'salida' => null
                ]);
            } elseif ($request->tipo === 'salida') {
                if (!$fichaje) {
                    return redirect()->route('users.index')->with('error', 'No puedes registrar una salida sin haber registrado entrada.');
                }
                $fichaje->update(['salida' => now()]); // Se actualiza la hora de salida
            }
    
            return redirect()->route('users.index')->with('success', 'Fichaje registrado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Error al registrar el fichaje: ' . $e->getMessage());
        }
    }
    

    /**
     * Mostrar un registro de fichaje específico.
     */
    public function show($id)
    {
        $fichaje = RegistroFichaje::with('user')->findOrFail($id);
        return view('registros_fichaje.show', compact('fichaje'));
    }

    /**
     * Eliminar un fichaje.
     */
    public function destroy($id)
    {
        $fichaje = RegistroFichaje::findOrFail($id);
        $fichaje->delete();

        return redirect()->route('registros-fichaje.index')
            ->with('success', 'Fichaje eliminado correctamente.');
    }
}
