<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;

class AsignacionTurnoController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'fecha' => 'required|date',
                'tipo' => 'required|exists:turnos,nombre', // Asegurar que el turno existe
            ]);

            $user = User::findOrFail($request->user_id);
            $turno = Turno::where('nombre', $request->tipo)->firstOrFail(); // Obtener el turno por nombre

            // Buscar si ya existe un turno asignado en esa fecha
            $asignacion = AsignacionTurno::where('user_id', $user->id)
                ->where('fecha', $request->fecha)
                ->first();

            if ($asignacion) {
                // Si el turno existente es "vacaciones", y el nuevo turno NO es "vacaciones", devolver el día
                if ($asignacion->turno->nombre === 'vacaciones' && $turno->nombre !== 'vacaciones') {
                    $user->increment('dias_vacaciones'); // Devolver el día de vacaciones
                }

                // Si el nuevo turno es "vacaciones" y el usuario tiene días disponibles, restar uno
                if ($turno->nombre === 'vacaciones') {
                    if ($user->dias_vacaciones <= 0) {
                        return response()->json(['error' => 'No tiene más días de vacaciones disponibles.'], 400);
                    }
                    $user->decrement('dias_vacaciones'); // Restar un día de vacaciones
                }

                // Actualizar el turno
                $asignacion->update([
                    'turno_id' => $turno->id,
                ]);
                return response()->json(['success' => 'Turno actualizado correctamente.']);
            } else {
                // Si no existe, crear un nuevo registro
                AsignacionTurno::create([
                    'user_id' => $user->id,
                    'turno_id' => $turno->id,
                    'fecha' => $request->fecha,
                ]);
                return response()->json(['success' => 'Turno registrado correctamente.']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar el turno: ' . $e->getMessage()], 500);
        }
    }
}
