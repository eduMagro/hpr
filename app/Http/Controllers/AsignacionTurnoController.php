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
                // Si ya existe, actualizar el turno
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
