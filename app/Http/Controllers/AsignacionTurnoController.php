<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Turno;
use App\Models\Obra;
use App\Models\AsignacionTurno;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AsignacionTurnoController extends Controller
{

    public function fichar(Request $request)
    {
        Log::info('📩 Datos recibidos en store()', $request->all());

        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'tipo' => 'required|in:entrada,salida',
                'latitud' => 'required|numeric',
                'longitud' => 'required|numeric',
                'obra_id' => 'required|exists:obras,id',
            ], [
                'latitud.required' => 'La latitud es requerida.',
                'latitud.numeric' => 'La latitud debe ser un número',
                'longitud.required' => 'La longitud es requerida.',
                'longitud.numeric' => 'La longitud debe ser un número.',
            ]);

            $user = User::findOrFail($request->user_id);

            if ($user->rol !== 'operario') {
                return response()->json(['error' => 'No tienes permisos para fichar.'], 403);
            }

            $fechaHoy = now()->toDateString();
            $horaActual = now();

            $obra = Obra::findOrFail($request->obra_id);
            $distancia = $this->calcularDistancia(
                $request->latitud,
                $request->longitud,
                $obra->latitud,
                $obra->longitud
            );
            Log::info('Distancia hasta la nave', ['distancia' => $distancia]);

            if ($distancia > $obra->distancia) {
                return response()->json(['error' => 'No puedes fichar fuera de la nave de trabajo.'], 403);
            }

            // Buscar asignación de turno para hoy
            $asignacionTurno = $user->asignacionesTurnos()->where('fecha', $fechaHoy)->first();

            if (!$asignacionTurno) {
                return response()->json(['error' => 'No tienes un turno asignado para hoy.'], 403);
            }

            $turnoNombre = strtolower($asignacionTurno->turno->nombre);
            $warning = null;

            if ($request->tipo === 'entrada') {
                if ($asignacionTurno->entrada) {
                    return response()->json(['error' => 'Ya has registrado una entrada hoy.'], 403);
                }

                if (!$this->validarHoraEntrada($turnoNombre, $horaActual)) {
                    $warning = 'Has fichado entrada fuera de tu horario de turno.';
                }

                $asignacionTurno->update(['entrada' => $horaActual]);
            } elseif ($request->tipo === 'salida') {
                if (!$asignacionTurno->entrada) {
                    return response()->json(['error' => 'No puedes registrar una salida sin haber registrado entrada.'], 403);
                }

                if ($asignacionTurno->salida) {
                    return response()->json(['error' => 'Ya has registrado una salida hoy.'], 403);
                }

                if (!$this->validarHoraSalida($turnoNombre, $horaActual)) {
                    $warning = 'Has fichado salida fuera de tu horario de turno.';
                }

                $asignacionTurno->update(['salida' => $horaActual]);
            }

            return response()->json([
                'success' => 'Fichaje registrado correctamente.',
                'warning' => $warning
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar el fichaje: ' . $e->getMessage()], 500);
        }
    }
    private function validarHoraEntrada($turno, $horaActual)
    {
        $hora = $horaActual->format('H:i');

        return match ($turno) {
            'mañana' => $hora >= '05:45' && $hora <= '06:30',
            'tarde' => $hora >= '13:45' && $hora <= '14:30',
            'noche' => $hora >= '21:45' && $hora <= '22:30',
            default => false,
        };
    }

    private function validarHoraSalida($turno, $horaActual)
    {
        $hora = $horaActual->format('H:i');

        return match ($turno) {
            'mañana' => $hora >= '13:45' && $hora <= '14:30',
            'tarde' => $hora >= '21:45' && $hora <= '22:30',
            'noche' => $hora >= '05:45' && $hora <= '06:30',
            default => false,
        };
    }

    /**
     * Calcula la distancia en metros entre dos puntos geográficos usando la fórmula de Haversine.
     */
    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $radioTierra = 6371000; // Radio de la Tierra en metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $radioTierra * $c; // Distancia en metros
    }

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
            // Antes de comenzar la iteración en el método store
            if ($turno->nombre === 'vacaciones') {
                $vacacionesExistentes = AsignacionTurno::where('user_id', $user->id)
                    ->whereBetween('fecha', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
                    ->whereHas('turno', function ($query) {
                        $query->where('nombre', 'vacaciones');
                    })
                    ->count();

                if ($vacacionesExistentes > 0) {
                    return response()->json([
                        'error' => 'Ya existen días asignados como vacaciones en el rango seleccionado.'
                    ], 400);
                }
            }

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

            return response()->json(['success' => 'Asignación completada.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar el turno: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            // Validar los datos de entrada
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ], [
                'user_id.required' => 'El usuario es obligatorio.',
                'user_id.exists' => 'El usuario seleccionado no existe.',
                'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
                'fecha_fin.required' => 'La fecha fin es obligatoria.',
                'fecha_fin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha de inicio.',
            ]);

            $user = User::findOrFail($request->user_id);

            // Obtener asignaciones que sean de tipo "vacaciones"
            $vacacionesTurno = Turno::where('nombre', 'vacaciones')->first();

            $asignacionesVacaciones = AsignacionTurno::where('user_id', $user->id)
                ->where('turno_id', $vacacionesTurno->id)
                ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])
                ->count();

            // Devolver los días de vacaciones al usuario
            if ($asignacionesVacaciones > 0) {
                $user->increment('dias_vacaciones', $asignacionesVacaciones);
            }

            // Eliminar las asignaciones
            AsignacionTurno::where('user_id', $user->id)
                ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])
                ->delete();

            return response()->json(['success' => 'Turnos eliminados correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar los turnos: ' . $e->getMessage()], 500);
        }
    }
}
