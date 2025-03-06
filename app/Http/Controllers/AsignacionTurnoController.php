<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use Carbon\Carbon;

class AsignacionTurnoController extends Controller
{
    public function store(Request $request)
    {
        try {

            $request->validate([
                'user_id'     => 'required|exists:users,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin'   => 'required|date|after_or_equal:fecha_inicio',
                'tipo'        => 'required|exists:turnos,nombre',
            ], [
                'user_id.required'     => 'El usuario es obligatorio.',
                'user_id.exists'       => 'El usuario seleccionado no existe.',
                'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
                'fecha_inicio.date'    => 'La fecha de inicio debe ser una fecha válida.',
                'fecha_fin.required'   => 'La fecha fin es obligatoria.',
                'fecha_fin.date'       => 'La fecha fin debe ser una fecha válida.',
                'fecha_fin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha de inicio.',
                'tipo.required'        => 'El tipo de turno es obligatorio.',
                'tipo.exists'          => 'El tipo de turno seleccionado no existe.',
            ]);

            $user  = User::findOrFail($request->user_id);
            $turno = Turno::where('nombre', $request->tipo)->firstOrFail();

            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin    = Carbon::parse($request->fecha_fin);
            $currentDate = $fechaInicio->copy();

            // Iterar desde la fecha de inicio hasta la fecha fin (incluyendo ésta última)
            while ($currentDate->lte($fechaFin)) {
                // Omitir sábados y domingos, si así se requiere
                if (in_array($currentDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                    $currentDate->addDay();
                    continue;
                }

                $dateStr = $currentDate->toDateString();
                $asignacion = AsignacionTurno::where('user_id', $user->id)
                    ->where('fecha', $dateStr)
                    ->first();

                if ($asignacion) {
                    // Si el turno actual es "vacaciones" y se cambia a otro, se devuelve el día
                    if ($asignacion->turno->nombre === 'vacaciones' && $turno->nombre !== 'vacaciones') {
                        $user->increment('dias_vacaciones');
                    }
                    // Si el nuevo turno es "vacaciones", verificar disponibilidad y restar un día
                    if ($turno->nombre === 'vacaciones') {
                        if ($user->dias_vacaciones <= 0) {
                            return response()->json(['error' => "No tiene más días de vacaciones disponibles para la fecha {$dateStr}."], 400);
                        }
                        $user->decrement('dias_vacaciones');
                    }
                    $asignacion->update([
                        'turno_id' => $turno->id,
                    ]);
                } else {
                    // Si se asigna un turno de vacaciones, verificar disponibilidad
                    if ($turno->nombre === 'vacaciones') {
                        if ($user->dias_vacaciones <= 0) {
                            return response()->json(['error' => "No tiene más días de vacaciones disponibles para la fecha {$dateStr}."], 400);
                        }
                        $user->decrement('dias_vacaciones');
                    }
                    AsignacionTurno::create([
                        'user_id'  => $user->id,
                        'turno_id' => $turno->id,
                        'fecha'    => $dateStr,
                    ]);
                }

                $currentDate->addDay();
            }

            return response()->json(['success' => 'Asignación completada con éxito.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar el turno: ' . $e->getMessage()], 500);
        }
    }
}
