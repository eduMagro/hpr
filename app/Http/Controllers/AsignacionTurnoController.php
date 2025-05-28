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
    public function index(Request $request)
    {
        $query = AsignacionTurno::with(['user', 'turno', 'maquina'])
            ->whereDate('fecha', '<=', Carbon::yesterday())
            ->whereHas('turno', fn($q) => $q->where('nombre', '!=', 'vacaciones'));

        if ($request->filled('trabajador')) {
            $query->whereHas(
                'user',
                fn($q) =>
                $q->where('name', 'like', '%' . $request->trabajador . '%')
            );
        }

        $asignaciones = $query->orderBy('fecha', 'desc')->paginate(15);

        // Contadores de puntualidad
        $diasPuntuales = 0;
        $diasImpuntuales = 0;
        $diasSinFichaje = 0;

        foreach ($asignaciones as $asignacion) {
            $esperada = $asignacion->turno->hora_entrada ?? null;
            $real = $asignacion->entrada;

            if ($esperada && $real) {
                $puntual = Carbon::parse($real)->lte(Carbon::parse($esperada));
                $puntual ? $diasPuntuales++ : $diasImpuntuales++;
            } elseif ($esperada && !$real) {
                $diasSinFichaje++;
            }
        }

        $diasTrabajados = $asignaciones->count();

        return view('asignaciones-turnos.index', compact(
            'asignaciones',
            'diasTrabajados',
            'diasPuntuales',
            'diasImpuntuales',
            'diasSinFichaje'
        ));
    }

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
                'user_id'      => 'required|exists:users,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
                'tipo'         => 'required|string', // No validar en tabla turnos aquí
            ]);

            $turno = Turno::where('nombre', $request->tipo)->first();

            // Obtener nombres válidos de turnos desde la tabla 'turnos'
            $turnosValidos = Turno::pluck('nombre')->toArray();

            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin = Carbon::parse($request->fecha_fin);

            $usuarios = $turno && $turno->nombre === 'festivo'
                ? User::all()
                : collect([User::findOrFail($request->user_id)]);

            foreach ($usuarios as $user) {
                $maquinaId = $user->maquina?->id;

                $currentDate = $fechaInicio->copy();

                while ($currentDate->lte($fechaFin)) {
                    if (in_array($currentDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                        $currentDate->addDay();
                        continue;
                    }

                    $dateStr = $currentDate->toDateString();

                    $asignacion = AsignacionTurno::where('user_id', $user->id)
                        ->whereDate('fecha', $dateStr)
                        ->first();

                    // Si el turno no existe o no está en la lista válida, se marca estado vacaciones
                    $esVacaciones = !$turno || !in_array($turno->nombre, $turnosValidos);

                    if ($asignacion) {
                        // Si sobrescribe vacaciones por turno válido, devolver días de vacaciones
                        if ($asignacion->estado === 'vacaciones' && !$esVacaciones) {
                            $user->increment('dias_vacaciones');
                        }

                        if ($esVacaciones) {
                            if ($user->dias_vacaciones <= 0) {
                                return response()->json([
                                    'error' => "El usuario {$user->name} no tiene más días de vacaciones disponibles para la fecha {$dateStr}."
                                ], 400);
                            }
                            $user->decrement('dias_vacaciones');
                            $asignacion->update([
                                'estado'     => 'vacaciones',
                                'turno_id'   => null,
                                'maquina_id' => $maquinaId,
                            ]);
                        } else {
                            $asignacion->update([
                                'estado'     => 'activo',
                                'turno_id'   => $turno->id,
                                'maquina_id' => $maquinaId,
                            ]);
                        }
                    } else {
                        // Nuevo registro
                        if ($esVacaciones) {
                            if ($user->dias_vacaciones <= 0) {
                                return response()->json([
                                    'error' => "El usuario {$user->name} no tiene más días de vacaciones disponibles para la fecha {$dateStr}."
                                ], 400);
                            }
                            $user->decrement('dias_vacaciones');

                            AsignacionTurno::create([
                                'user_id'    => $user->id,
                                'fecha'      => $dateStr,
                                'estado'     => 'vacaciones',
                                'turno_id'   => null,
                                'maquina_id' => $maquinaId,
                            ]);
                        } else {
                            AsignacionTurno::create([
                                'user_id'    => $user->id,
                                'fecha'      => $dateStr,
                                'estado'     => 'activo',
                                'turno_id'   => $turno->id,
                                'maquina_id' => $maquinaId,
                            ]);
                        }
                    }

                    $currentDate->addDay();
                }
            }

            return response()->json(['success' => 'Asignación completada.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar el turno: ' . $e->getMessage()], 500);
        }
    }



    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            ]);

            // Si se especifica tipo_turno y es "festivo", borramos para todos los usuarios
            if ($request->filled('tipo_turno') && $request->tipo_turno === 'festivo') {
                $turno = Turno::where('nombre', 'festivo')->first();

                if (!$turno) {
                    return response()->json(['error' => 'El turno festivo no está configurado.'], 400);
                }

                AsignacionTurno::where('turno_id', $turno->id)
                    ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])
                    ->delete();

                return response()->json(['success' => 'Turnos festivos eliminados para todos los usuarios.']);
            }

            // Si NO es festivo, validamos user_id y actuamos solo sobre ese usuario
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $user = User::findOrFail($request->user_id);
            $turnoVacaciones = Turno::where('nombre', 'vacaciones')->first();

            $asignaciones = AsignacionTurno::where('user_id', $user->id)
                ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])
                ->get();

            $vacacionesContadas = $turnoVacaciones
                ? $asignaciones->where('turno_id', $turnoVacaciones->id)->count()
                : 0;

            if ($vacacionesContadas > 0) {
                $user->increment('dias_vacaciones', $vacacionesContadas);
            }

            AsignacionTurno::whereIn('id', $asignaciones->pluck('id'))->delete();

            return response()->json(['success' => 'Turnos del usuario eliminados correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar los turnos: ' . $e->getMessage()], 500);
        }
    }
}
