<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Turno;
use App\Models\Obra;
use App\Models\AsignacionTurno;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AsignacionTurnoController extends Controller
{
    public function index(Request $request)
    {
        $query = AsignacionTurno::with(['user', 'turno', 'maquina'])
            ->whereDate('fecha', '<=', Carbon::yesterday())
            ->whereHas('turno', fn($q) => $q->where('nombre', '!=', 'vacaciones'));

        if ($request->filled('trabajador')) {
            $query->whereHas('user', fn($q) =>
            $q->where('name', 'like', '%' . $request->trabajador . '%'));
        }

        // Ordenar por fecha y turno lógico (por nombre o campo `orden`)
        $query->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->orderBy('fecha', 'desc')
            ->orderByRaw("FIELD(turnos.nombre, 'mañana', 'tarde', 'noche')")
            ->select('asignaciones_turnos.*');

        $asignaciones = $query->paginate(15)->withQueryString();

        // ✅ Contadores de puntualidad
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
        Log::info('📩 Datos recibidos en fichar()', $request->all());

        try {
            // Validación
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'tipo' => 'required|in:entrada,salida',
                'latitud' => 'required|numeric',
                'longitud' => 'required|numeric',
                'obra_id' => 'required|exists:obras,id',
            ]);

            $user = User::findOrFail($request->user_id);
            if ($user->rol !== 'operario') {
                return response()->json(['error' => 'No tienes permisos para fichar.'], 403);
            }

            $ahora = now();                     // datetime completo
            $horaActual = $ahora->format('H:i:s'); // solo la hora como string

            // Buscar turno correspondiente
            $asignacionTurno = $user->asignacionesTurnos()
                ->whereIn('fecha', [
                    $ahora->toDateString(),
                    $ahora->copy()->subDay()->toDateString(),
                    $ahora->copy()->addDay()->toDateString()
                ])
                ->with('turno')
                ->get()
                ->first(function ($asignacion) use ($ahora, $request) {
                    $turno = strtolower($asignacion->turno->nombre ?? '');

                    if ($turno !== 'noche') {
                        return $asignacion->fecha === $ahora->toDateString();
                    }

                    if ($request->tipo === 'entrada') {
                        // Entre 20:00 y 23:59 → turno del día siguiente
                        if ($ahora->hour >= 20 && $ahora->hour <= 23) {
                            return $asignacion->fecha === $ahora->copy()->addDay()->toDateString();
                        }
                        // Entre 00:00 y 06:00 → turno del mismo día
                        if ($ahora->hour < 6) {
                            return $asignacion->fecha === $ahora->toDateString();
                        }
                    } else {
                        // SALIDA: si antes de 06:00 → pertenece al turno del día anterior
                        if ($ahora->hour < 6) {
                            return $asignacion->fecha === $ahora->copy()->subDay()->toDateString();
                        }
                        return $asignacion->fecha === $ahora->toDateString();
                    }

                    return false;
                });

            if (!$asignacionTurno) {
                return response()->json(['error' => 'No tienes un turno asignado para este día laboral.'], 403);
            }

            $turnoNombre = strtolower($asignacionTurno->turno->nombre);
            $fechaLogica = $asignacionTurno->fecha;

            // Comprobación de ubicación
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

            $warning = null;

            // Fichaje de entrada
            if ($request->tipo === 'entrada') {
                if ($asignacionTurno->entrada) {
                    return response()->json(['error' => 'Ya has registrado una entrada.'], 403);
                }

                if (!$this->validarHoraEntrada($turnoNombre, $horaActual)) {
                    $warning = 'Has fichado entrada fuera de tu horario.';
                }

                $asignacionTurno->update(['entrada' => $horaActual]);
            }

            // Fichaje de salida
            elseif ($request->tipo === 'salida') {
                if (!$asignacionTurno->entrada) {
                    return response()->json(['error' => 'No puedes registrar una salida sin haber fichado entrada.'], 403);
                }

                if ($asignacionTurno->salida) {
                    return response()->json(['error' => 'Ya has registrado una salida.'], 403);
                }

                if (!$this->validarHoraSalida($turnoNombre, $horaActual)) {
                    $warning = 'Has fichado salida fuera de tu horario.';
                }

                $asignacionTurno->update(['salida' => $horaActual]);
            }

            return response()->json([
                'success' => 'Fichaje registrado correctamente.',
                'warning' => $warning
            ]);
        } catch (\Exception $e) {
            Log::error('Error en fichaje', ['exception' => $e]);
            return response()->json(['error' => 'Error al registrar el fichaje: ' . $e->getMessage()], 500);
        }
    }


    private function validarHoraEntrada($turno, $horaActual)
    {
        // Convertir string a objeto Carbon
        $hora = Carbon::createFromFormat('H:i:s', $horaActual)->format('H:i');

        return match ($turno) {
            'noche' => $hora >= '21:45' && $hora <= '22:30',
            'mañana' => $hora >= '05:45' && $hora <= '06:30',
            'tarde' => $hora >= '13:45' && $hora <= '14:30',
            default => false,
        };
    }

    private function validarHoraSalida($turno, $horaActual)
    {
        $hora = Carbon::createFromFormat('H:i:s', $horaActual)->format('H:i');

        return match ($turno) {
            'noche' => $hora >= '05:45' && $hora <= '06:30',
            'mañana' => $hora >= '13:45' && $hora <= '14:30',
            'tarde' => $hora >= '21:45' && $hora <= '22:30',
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
                'tipo'         => 'required|string',
            ]);

            if (in_array($request->tipo, ['eliminarEstado', 'eliminarTurnoEstado'])) {
                return response()->json(['error' => 'Esta operación debe gestionarse por otro método.'], 400);
            }

            $tipo = $request->tipo;
            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin = Carbon::parse($request->fecha_fin);

            $turnosValidos = Turno::pluck('nombre')->toArray();
            $esTurno = in_array($tipo, $turnosValidos);
            $turno = $esTurno ? Turno::where('nombre', $tipo)->first() : null;

            // 📅 Obtener array de fechas festivas
            $festivos = collect($this->getFestivos())->pluck('start')->toArray();

            $usuarios = ($tipo === 'festivo')
                ? User::all()
                : collect([User::findOrFail($request->user_id)]);

            foreach ($usuarios as $user) {
                $maquinaId = $user->maquina?->id;
                $currentDate = $fechaInicio->copy();

                while ($currentDate->lte($fechaFin)) {
                    $dateStr = $currentDate->toDateString();

                    // ⛔ Evitar solo si es estado "vacaciones" y cae en fin de semana o festivo
                    // 💡 Solo para vacaciones, controlar el tope
                    if ($tipo === 'vacaciones') {
                        $inicioAño = Carbon::now()->startOfYear();
                        $yaDisfrutados = $user->asignacionesTurnos()
                            ->where('estado', 'vacaciones')
                            ->where('fecha', '>=', $inicioAño)
                            ->count();

                        $diasSolicitados = 0;

                        $tempDate = $fechaInicio->copy();
                        while ($tempDate->lte($fechaFin)) {
                            $dateStr = $tempDate->toDateString();

                            if (
                                !in_array($tempDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) &&
                                !in_array($dateStr, $festivos)
                            ) {
                                $asignacion = AsignacionTurno::where('user_id', $user->id)
                                    ->whereDate('fecha', $dateStr)
                                    ->first();

                                if (!$asignacion || $asignacion->estado !== 'vacaciones') {
                                    $diasSolicitados++;
                                }
                            }

                            $tempDate->addDay();
                        }

                        $totalPermitido = $user->vacaciones_totales ?? 22;

                        if (($yaDisfrutados + $diasSolicitados) > $totalPermitido) {
                            return response()->json([
                                'error' => "El usuario {$user->name} ya tiene {$yaDisfrutados} días asignados y está intentando asignar {$diasSolicitados} más. Su tope es de {$totalPermitido} días de vacaciones anuales."
                            ], 400);
                        }
                    }


                    $asignacion = AsignacionTurno::where('user_id', $user->id)
                        ->whereDate('fecha', $dateStr)
                        ->first();

                    $estadoNuevo = $esTurno ? 'activo' : $tipo;

                    if ($asignacion) {
                        if (!$esTurno && $asignacion->estado !== $estadoNuevo) {
                            $asignacion->update(['estado' => $estadoNuevo]);
                        } elseif ($esTurno && $asignacion->turno_id !== $turno->id) {
                            $asignacion->update([
                                'turno_id'   => $turno->id,
                                'maquina_id' => $maquinaId,
                            ]);
                        }
                    } else {
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


    private function getFestivos()
    {
        $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/" . date('Y') . "/ES");

        if ($response->failed()) {
            return []; // Si la API falla, devolvemos un array vacío
        }

        $festivos = collect($response->json())->filter(function ($holiday) {
            // Si no tiene 'counties', es un festivo NACIONAL
            if (!isset($holiday['counties'])) {
                return true;
            }
            // Si el festivo pertenece a Andalucía
            return in_array('ES-AN', $holiday['counties']);
        })->map(function ($holiday) {
            return [
                'title' => $holiday['localName'], // Nombre del festivo
                'start' => Carbon::parse($holiday['date'])->toDateString(), // Fecha formateada correctamente
                'backgroundColor' => '#ff0000', // Rojo para festivos
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'allDay' => true
            ];
        });

        // Añadir festivos locales de Los Palacios y Villafranca
        $festivosLocales = collect([
            [
                'title' => 'Festividad de Nuestra Señora de las Nieves',
                'start' => date('Y') . '-08-05',
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'editable' => true,
                'allDay' => true
            ],
            [
                'title' => 'Feria Los Palacios y Vfca',
                'start' => date('Y') . '-09-25',
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'editable' => true,
                'allDay' => true
            ]
        ]);

        // Combinar festivos nacionales, autonómicos y locales
        return $festivos->merge($festivosLocales)->values()->toArray();
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

            foreach ($asignaciones as $asignacion) {
                if ($tipo === 'eliminarTurnoEstado') {
                    $asignacion->delete();
                } elseif ($tipo === 'eliminarEstado') {
                    $asignacion->update([
                        'estado' => null,
                    ]);
                }
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
