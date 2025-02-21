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

            $user = User::findOrFail($request->user_id);
            $fechaHoy = now()->toDateString();

            // Obtener el turno del trabajador para el día actual
            $asignacionTurno = $user->asignacionesTurnos()->where('fecha', $fechaHoy)->first();

            if (!$asignacionTurno) {
                return redirect()->route('users.index')->with('error', 'No tienes un turno asignado para hoy.');
            }

            $turnoNombre = strtolower($asignacionTurno->turno->nombre); // Convertir a minúsculas

            // Verificar si el usuario ya tiene un registro de fichaje hoy
            $fichaje = RegistroFichaje::where('user_id', $request->user_id)
                ->whereDate('entrada', $fechaHoy)
                ->first();

            // Validar fichaje según el turno asignado
            if ($request->tipo === 'entrada') {
                if ($fichaje) {
                    return redirect()->route('users.index')->with('error', 'Ya has registrado una entrada hoy.');
                }

                if (!$this->validarHoraEntrada($turnoNombre, now())) {
                    return redirect()->route('users.index')->with('error', 'No puedes fichar fuera de tu horario de turno.');
                }

                RegistroFichaje::create([
                    'user_id' => $request->user_id,
                    'entrada' => now(),
                    'salida' => null
                ]);
            } elseif ($request->tipo === 'salida') {
                if (!$fichaje) {
                    return redirect()->route('users.index')->with('error', 'No puedes registrar una salida sin haber registrado entrada.');
                }

                if (!$this->validarHoraSalida($turnoNombre, now())) {
                    return redirect()->route('users.index')->with('error', 'No puedes fichar salida fuera de tu horario de turno.');
                }

                $fichaje->update(['salida' => now()]);
            }

            return redirect()->route('users.index')->with('success', 'Fichaje registrado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Error al registrar el fichaje: ' . $e->getMessage());
        }
    }

    private function validarHoraEntrada($turno, $horaActual)
    {
        $hora = $horaActual->format('H:i');

        return match ($turno) {
            'mañana' => $hora >= '05:45' && $hora <= '08:30',
            'tarde' => $hora >= '13:45' && $hora <= '16:30',
            'noche' => $hora >= '11:45' && $hora <= '23:59' || $hora >= '00:00' && $hora <= '02:30',
            default => false,
        };
    }

    private function validarHoraSalida($turno, $horaActual)
    {
        $hora = $horaActual->format('H:i');

        return match ($turno) {
            'mañana' => $hora >= '14:00' && $hora <= '15:00',
            'tarde' => $hora >= '22:00' && $hora <= '23:00',
            'noche' => $hora >= '06:00' && $hora <= '07:30',
            default => false,
        };
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
