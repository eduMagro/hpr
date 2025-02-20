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
        return view('user.show', compact('fichajes'));
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
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tipo' => 'required|in:entrada,salida',
            'fecha_hora' => 'required|date',
        ]);

        RegistroFichaje::create([
            'user_id' => $request->user_id,
            'tipo' => $request->tipo,
            'fecha_hora' => $request->fecha_hora,
        ]);

        return redirect()->route('registros-fichajes.index')
            ->with('success', 'Fichaje registrado correctamente.');
    }

    /**
     * Mostrar un registro de fichaje específico.
     */
    public function show($id)
    {
        $fichaje = RegistroFichaje::with('user')->findOrFail($id);
        return view('registros_fichajes.show', compact('fichaje'));
    }

    /**
     * Eliminar un fichaje.
     */
    public function destroy($id)
    {
        $fichaje = RegistroFichaje::findOrFail($id);
        $fichaje->delete();

        return redirect()->route('registros-fichajes.index')
            ->with('success', 'Fichaje eliminado correctamente.');
    }
}
