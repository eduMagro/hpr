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
            // 🧾 Validación básica de los datos recibidos
            $request->validate([
                'user_id'      => 'required|exists:users,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
                'tipo'         => 'required|string',
            ]);

            // 🚫 Tipos que deben ir al método destroy
            if (in_array($request->tipo, ['eliminarEstado', 'eliminarTurnoEstado'])) {
                return response()->json(['error' => 'Esta operación debe gestionarse por otro método.'], 400);
            }

            $tipo = $request->tipo;
            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin = Carbon::parse($request->fecha_fin);

            $turnosValidos = Turno::pluck('nombre')->toArray();
            $esTurno = in_array($tipo, $turnosValidos);
            $turno = $esTurno ? Turno::where('nombre', $tipo)->first() : null;

            // 👥 Usuarios afectados
            $usuarios = ($tipo === 'festivo')
                ? User::all()
                : collect([User::findOrFail($request->user_id)]);

            foreach ($usuarios as $user) {
                $maquinaId = $user->maquina?->id;
                $currentDate = $fechaInicio->copy();

                while ($currentDate->lte($fechaFin)) {
                    $dateStr = $currentDate->toDateString();


                    $asignacion = AsignacionTurno::where('user_id', $user->id)
                        ->whereDate('fecha', $dateStr)
                        ->first();

                    $estadoNuevo = $esTurno ? 'activo' : $tipo;
                    //Evitar festivos y fines de semana
                    if (
                        $estadoNuevo === 'vacaciones' &&
                        (
                            in_array($currentDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) ||
                            ($asignacion && optional($asignacion->turno)->nombre === 'festivo')
                        )
                    ) {
                        $currentDate->addDay();
                        continue;
                    }

                    // 🧮 Si se va a asignar vacaciones y aún no estaba asignado como tal
                    $debeRestarVacaciones = (
                        $estadoNuevo === 'vacaciones' &&
                        (!$asignacion || $asignacion->estado !== 'vacaciones')
                    );

                    if ($debeRestarVacaciones) {
                        if ($user->dias_vacaciones <= 0) {
                            return response()->json([
                                'error' => "El usuario {$user->name} no tiene más días de vacaciones disponibles para la fecha {$dateStr}."
                            ], 400);
                        }

                        $user->decrement('dias_vacaciones');
                    }

                    // ☑️ Si ya hay asignación, actualizamos
                    if ($asignacion) {
                        if (!$esTurno && $asignacion->estado !== $estadoNuevo) {
                            $asignacion->update(['estado' => $estadoNuevo]);
                        } elseif ($esTurno && $asignacion->turno_id !== $turno->id) {
                            $asignacion->update([
                                'turno_id'   => $turno->id,
                                'maquina_id' => $maquinaId,
                            ]);
                        }
                    }
                    // ➕ Si no hay asignación, creamos una nueva
                    else {
                        AsignacionTurno::create([
                            'user_id'    => $user->id,
                            'fecha'      => $dateStr,
                            'estado'     => $estadoNuevo,
                            'turno_id'   => $esTurno ? $turno->id : null,
                            'maquina_id' => $esTurno ? $maquinaId : null,
                        ]);
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

            $request->validate([
                'user_id' => 'required|exists:users,id',
                'tipo'    => 'required|in:eliminarTurnoEstado,eliminarEstado',
            ]);

            $tipo = trim($request->tipo); // ✅ evitar errores por espacios
            Log::debug('Tipo recibido en destroy:', ['tipo' => $tipo]);

            $user = User::findOrFail($request->user_id);

            $asignaciones = AsignacionTurno::where('user_id', $user->id)
                ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])
                ->get();

            $diasVacacionesASumar = 0;

            foreach ($asignaciones as $asignacion) {
                if ($asignacion->estado === 'vacaciones') {
                    $diasVacacionesASumar++;
                }

                if ($tipo === 'eliminarTurnoEstado') {
                    $asignacion->delete();
                } elseif ($tipo === 'eliminarEstado') {
                    $asignacion->update([
                        'estado' => null,
                    ]);
                }
            }


            if ($diasVacacionesASumar > 0) {
                $user->increment('dias_vacaciones', $diasVacacionesASumar);
            }

            return response()->json([
                'success' => $tipo === 'eliminarTurnoEstado'
                    ? 'Turnos eliminados correctamente.'
                    : 'Estado eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar los turnos: ' . $e->getMessage()], 500);
        }
    }
}
