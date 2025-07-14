<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Turno;
use App\Models\Obra;
use App\Models\Alerta;
use App\Models\AlertaLeida;
use App\Models\AsignacionTurno;
use App\Models\VacacionesSolicitud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Builder; // ✅ Correcto

class AsignacionTurnoController extends Controller
{
    public function aplicarFiltros($query, Request $request)
    {
        // 🔹 Filtro por ID
        if ($request->filled('id')) {
            $query->where('user_id', $request->input('id'));
        }
        if ($request->filled('empleado')) {
            $query->whereHas('user', function ($q) use ($request) {
                $valor = $request->empleado;
                $q->whereRaw("CONCAT_WS(' ', name, primer_apellido, segundo_apellido) LIKE ?", ["%{$valor}%"]);
            });
        }


        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        } elseif ($request->filled('fecha_inicio')) {
            $query->where('fecha', '>=', $request->fecha_inicio);
        } elseif ($request->filled('fecha_fin')) {
            $query->where('fecha', '<=', $request->fecha_fin);
        }

        if ($request->filled('obra')) {
            $query->whereHas(
                'obra',
                fn($q) =>
                $q->where('obra', 'like', '%' . $request->obra . '%')
            );
        }

        if ($request->filled('turno')) {
            $query->whereHas(
                'turno',
                fn($q) =>
                $q->where('nombre', 'like', '%' . $request->turno . '%')
            );
        }

        if ($request->filled('maquina')) {
            $query->whereHas(
                'maquina',
                fn($q) =>
                $q->where('nombre', 'like', '%' . $request->maquina . '%')
            );
        }

        if ($request->filled('entrada')) {
            $query->where('entrada', 'like', '%' . $request->entrada . '%');
        }

        if ($request->filled('salida')) {
            $query->where('salida', 'like', '%' . $request->salida . '%');
        }

        if ($request->filled('sort') && in_array($request->sort, ['user_id', 'fecha', 'turno_id', 'maquina_id'])) {
            $direction = $request->direction === 'asc' ? 'asc' : 'desc';
            $query->orderBy($request->sort, $direction);
        }

        return $query;
    }

    public function index(Request $request)
    {
        // 1. QUERY BASE (filtros normales con empleado)
        $query = AsignacionTurno::with(['user', 'turno', 'maquina'])
            ->whereDate('fecha', '<=', Carbon::tomorrow())
            ->where('estado', 'activo') // ✅ Solo registros activos
            ->whereHas('turno', fn($q) => $q->where('nombre', '!=', 'vacaciones'))
            ->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->orderBy('fecha', 'desc')
            ->orderByRaw("FIELD(turnos.nombre, 'mañana', 'tarde', 'noche')")
            ->orderBy('asignaciones_turnos.id') // 🟡 Orden estable
            ->select('asignaciones_turnos.*');

        $query = $this->aplicarFiltros($query, $request);
        $perPage = $request->input('per_page', 15);
        $asignaciones = $query->paginate($perPage)->withQueryString();
        $turnos = Turno::where('nombre', '!=', 'festivo')
            ->orderBy('nombre')
            ->get();

        // 2. Estadísticas del trabajador (cuando se filtra por nombre)
        $asignacionesFiltradas = (clone $query)->get();

        $diasAsignados = 0;
        $diasFichados = 0;
        $diasPuntuales = 0;
        $diasImpuntuales = 0;
        $diasSeVaAntes = 0;
        $diasSinFichaje = 0;

        foreach ($asignacionesFiltradas as $asignacion) {
            $esperadaEntrada = $asignacion->turno->hora_entrada ?? null;
            $esperadaSalida = $asignacion->turno->hora_salida ?? null;

            $realEntrada = $asignacion->entrada;
            $realSalida = $asignacion->salida;

            if ($esperadaEntrada) {
                $diasAsignados++;

                if ($realEntrada) {
                    $diasFichados++;

                    $llegaTemprano = Carbon::parse($realEntrada)->lte(Carbon::parse($esperadaEntrada));
                    $seVaTarde = $realSalida && $esperadaSalida
                        ? Carbon::parse($realSalida)->gte(Carbon::parse($esperadaSalida))
                        : false;
                    $seVaAntes = $realSalida && $esperadaSalida
                        ? Carbon::parse($realSalida)->lt(Carbon::parse($esperadaSalida))
                        : false;

                    if ($llegaTemprano && $seVaTarde) {
                        $diasPuntuales++;
                    } elseif (!$llegaTemprano && $seVaTarde) {
                        $diasImpuntuales++;
                    } elseif ($llegaTemprano && $seVaAntes) {
                        $diasSeVaAntes++;
                    }
                } else {
                    $diasSinFichaje++;
                }
            }
        }

        // 3. Ranking por minutos adelantados (solo mes actual)
        $requestSinEmpleado = $request->except('empleado');
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        $queryRanking = AsignacionTurno::with(['user', 'turno'])
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->whereHas('turno', fn($q) => $q->where('nombre', '!=', 'vacaciones'))
            ->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->orderBy('fecha', 'desc')
            ->orderByRaw("FIELD(turnos.nombre, 'mañana', 'tarde', 'noche')")
            ->select('asignaciones_turnos.*');

        $queryRanking = $this->aplicarFiltros($queryRanking, new \Illuminate\Http\Request($requestSinEmpleado));
        $asignacionesRanking = $queryRanking->get();

        $estadisticasPuntualidad = [];
        $asignacionesPorUsuario = $asignacionesRanking->groupBy('user_id');

        foreach ($asignacionesPorUsuario as $userId => $asignacionesUsuario) {
            $minutosAdelanto = 0;
            $minutosRetraso = 0;
            $diasAdelantado = 0;

            foreach ($asignacionesUsuario as $asignacion) {
                $esperadaEntrada = $asignacion->turno->hora_entrada ?? null;
                $realEntrada = $asignacion->entrada;

                if ($esperadaEntrada && $realEntrada) {
                    $esperada = Carbon::parse($asignacion->fecha . ' ' . $esperadaEntrada);
                    $real = Carbon::parse($asignacion->fecha . ' ' . $realEntrada);

                    if ($real->lt($esperada)) {
                        $minutos = $real->diffInMinutes($esperada);
                        $minutosAdelanto += $minutos;
                        $diasAdelantado++;
                    } elseif ($real->gt($esperada)) {
                        $minutos = $esperada->diffInMinutes($real);
                        $minutosRetraso += $minutos;
                    }
                }
            }

            $minutosNetos = $minutosAdelanto - $minutosRetraso;

            if ($minutosNetos > 0) {
                $estadisticasPuntualidad[] = [
                    'usuario' => $asignacionesUsuario->first()->user,
                    'minutos_adelanto' => $minutosNetos,
                    'dias_adelantado' => $diasAdelantado
                ];
            }
        }


        $estadisticasPuntualidad = collect($estadisticasPuntualidad)
            ->sortByDesc('minutos_adelanto')
            ->take(3)
            ->values();
        $totalSolicitudesPendientes = VacacionesSolicitud::where('estado', 'pendiente')->count();
        return view('asignaciones-turnos.index', compact(
            'asignaciones',
            'diasAsignados',
            'diasFichados',
            'diasPuntuales',
            'diasImpuntuales',
            'diasSeVaAntes',
            'diasSinFichaje',
            'estadisticasPuntualidad',
            'turnos',
            'totalSolicitudesPendientes'
        ));
    }

    public function fichar(Request $request)
    {
        Log::info('📩 Datos recibidos en fichar()', $request->all());

        try {
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

            $ahora = now();
            $horaActual = $ahora->format('H:i:s');
            $horaComparar = $ahora->format('H:i');
            $fecha = $ahora->toDateString();
            $fechaAnterior = $ahora->copy()->subDay()->toDateString();
            $fechaSiguiente = $ahora->copy()->addDay()->toDateString();

            // 🔍 Buscar asignaciones posibles
            $asignaciones = $user->asignacionesTurnos()
                ->whereIn('fecha', [$fechaAnterior, $fecha, $fechaSiguiente])
                ->with('turno')
                ->get();

            // 🧠 Detectar turno lógico según hora real (con 2h de margen)
            $turnoDetectado = null;
            $fechaTurnoDetectado = null;

            $hora = Carbon::createFromFormat('H:i:s', $horaActual);

            if ($hora->between(Carbon::createFromTime(4, 0), Carbon::createFromTime(7, 59))) {
                $turnoDetectado = 'mañana';
                $fechaTurnoDetectado = $fecha;
            } elseif ($hora->between(Carbon::createFromTime(12, 0), Carbon::createFromTime(15, 59))) {
                $turnoDetectado = 'tarde';
                $fechaTurnoDetectado = $fecha;
            } elseif (
                $hora->between(Carbon::createFromTime(20, 0), Carbon::createFromTime(23, 59)) ||
                $hora->between(Carbon::createFromTime(0, 0), Carbon::createFromTime(5, 59))
            ) {
                $turnoDetectado = 'noche';
                $fechaTurnoDetectado = $hora->hour >= 20 ? $fechaSiguiente : $fecha;
            }

            if (!$turnoDetectado || !$fechaTurnoDetectado) {
                return response()->json(['error' => 'No se pudo determinar el turno para esta hora.'], 403);
            }

            // 🧾 Buscar la asignación correcta
            $asignacionTurno = $asignaciones->first(function ($a) use ($turnoDetectado, $fechaTurnoDetectado) {
                return strtolower($a->turno->nombre ?? '') === $turnoDetectado && $a->fecha === $fechaTurnoDetectado;
            });

            if (!$asignacionTurno) {
                return response()->json(['error' => 'No tienes un turno asignado para esta hora.'], 403);
            }

            // ✅ Actualizar turno si no coincide
            if (strtolower($asignacionTurno->turno->nombre) !== $turnoDetectado) {
                $nuevoTurno = Turno::where('nombre', $turnoDetectado)->first();
                if ($nuevoTurno) {
                    $asignacionTurno->turno_id = $nuevoTurno->id;
                    $asignacionTurno->save();
                    Log::info("🔁 Turno actualizado automáticamente a '{$turnoDetectado}' para user_id {$user->id}");

                    // Enviar alerta
                    try {
                        $programadores = User::whereHas('departamentos', fn($q) => $q->where('nombre', 'Programador'))->get();
                        $alerta = Alerta::create([
                            'mensaje' => "🔁 Se corrigió automáticamente el turno de {$user->name} a '{$turnoDetectado}' para la fecha {$fechaTurnoDetectado}.",
                            'user_id_1' => $user->id,
                            'user_id_2' => null,
                            'leida' => false,
                        ]);
                        foreach ($programadores as $p) {
                            AlertaLeida::firstOrCreate([
                                'alerta_id' => $alerta->id,
                                'user_id' => $p->id,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('❌ No se pudo enviar alerta al programador: ' . $e->getMessage());
                    }
                }
            }

            // 📍 Verificar ubicación
            $obra = Obra::findOrFail($request->obra_id);
            $distancia = $this->calcularDistancia($request->latitud, $request->longitud, $obra->latitud, $obra->longitud);
            Log::info('📍 Distancia hasta la obra', ['distancia' => $distancia]);
            // if ($distancia > $obra->distancia) {
            //     return response()->json(['error' => 'No puedes fichar fuera de la nave de trabajo.'], 403);
            // }

            // 🕒 Guardar fichaje
            $warning = null;

            if ($request->tipo === 'entrada') {
                if (!$this->validarHoraEntrada($turnoDetectado, $horaActual)) {
                    $warning = 'Has fichado entrada fuera de tu horario.';
                }
                $asignacionTurno->update([
                    'entrada' => $horaActual,
                    'obra_id' => $request->obra_id,
                ]);
            } else {
                if (!$asignacionTurno->entrada) {
                    $warning = 'Estás registrando una salida sin haber fichado entrada.';
                }
                if (!$this->validarHoraSalida($turnoDetectado, $horaActual)) {
                    $warning = 'Has fichado salida fuera de tu horario.';
                }
                $asignacionTurno->update([
                    'salida' => $horaActual,
                    'obra_id' => $request->obra_id,
                ]);
            }

            return response()->json([
                'success' => 'Fichaje registrado correctamente.',
                'warning' => $warning
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error en fichaje', ['exception' => $e]);
            return response()->json(['error' => 'Error al registrar el fichaje: ' . $e->getMessage()], 500);
        }
    }


    private function validarHoraEntrada($turno, $horaActual)
    {
        try {
            $hora = Carbon::createFromFormat('H:i:s', $horaActual)->format('H:i');
        } catch (\Exception $e) {
            Log::error("Formato inválido en horaActual (entrada): $horaActual");
            return false;
        }

        return match ($turno) {
            'noche' => $hora >= '21:45' || $hora <= '06:30',
            'mañana' => $hora >= '05:45' && $hora <= '06:30',
            'tarde' => $hora >= '13:45' && $hora <= '14:30',
            default => false,
        };
    }

    private function validarHoraSalida($turno, $horaActual)
    {
        try {
            $hora = Carbon::createFromFormat('H:i:s', $horaActual)->format('H:i');
        } catch (\Exception $e) {
            Log::error("Formato inválido en horaActual (salida): $horaActual");
            return false;
        }

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
                'entrada'      => 'nullable|date_format:H:i',
                'salida'       => 'nullable|date_format:H:i',
                'obra_id'      => 'nullable|exists:obras,id',
                'maquina_id'   => 'nullable|exists:maquinas,id',
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

            $festivos = collect($this->getFestivos())->pluck('start')->toArray();

            if ($tipo === 'festivo') {
                $usuarios = User::all(); // ✅ Se aplica a todos
            } else {
                $usuarios = collect([User::findOrFail($request->user_id)]); // ✅ Solo al usuario seleccionado
            }


            foreach ($usuarios as $user) {
                $maquinaAsignada = $request->maquina_id ?? $user->maquina?->id;
                $currentDate = $fechaInicio->copy();

                $diasSolicitados = 0;
                if ($tipo === 'vacaciones') {
                    $inicioAño = Carbon::now()->startOfYear();
                    $yaDisfrutados = $user->asignacionesTurnos()
                        ->where('estado', 'vacaciones')
                        ->where('fecha', '>=', $inicioAño)
                        ->count();

                    $tempDate = $fechaInicio->copy();
                    while ($tempDate->lte($fechaFin)) {
                        $tempStr = $tempDate->toDateString();
                        if (
                            !in_array($tempDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) &&
                            !in_array($tempStr, $festivos)
                        ) {
                            $asignacion = AsignacionTurno::where('user_id', $user->id)
                                ->whereDate('fecha', $tempStr)
                                ->first();

                            if (!$asignacion || $asignacion->estado !== 'vacaciones') {
                                $diasSolicitados++;
                            }
                        }
                        $tempDate->addDay();
                    }

                    $totalPermitido = $user->vacaciones_totales ?? 22;

                    if (($yaDisfrutados + $diasSolicitados) > $totalPermitido) {
                        $msg = "El usuario {$user->name} ya tiene {$yaDisfrutados} días y quiere añadir {$diasSolicitados}. Máximo: {$totalPermitido}.";
                        return response()->json(['error' => $msg], 400);
                    }

                    // ✅ Crear alerta personalizada si se asignan vacaciones
                    $alerta = Alerta::create([
                        'user_id_1'       => auth()->id(),
                        'destinatario_id' => $user->id,
                        'mensaje'         => "{$user->nombre_completo}, Se te han asignado vacaciones del {$fechaInicio->format('Y-m-d')} al {$fechaFin->format('Y-m-d')}.",
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    // ✅ Registrar como alerta pendiente de leer
                    AlertaLeida::create([
                        'alerta_id' => $alerta->id,
                        'user_id'   => $user->id,
                        'leida_en'  => null,
                    ]);
                }

                while ($currentDate->lte($fechaFin)) {
                    $dateStr = $currentDate->toDateString();

                    if (
                        $tipo === 'vacaciones' &&
                        (in_array($currentDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) ||
                            in_array($dateStr, $festivos))
                    ) {
                        $currentDate->addDay();
                        continue;
                    }

                    $asignacion = AsignacionTurno::where('user_id', $user->id)
                        ->whereDate('fecha', $dateStr)
                        ->first();

                    $estadoNuevo = $esTurno ? 'activo' : $tipo;

                    $datos = [];

                    if ($esTurno || $tipo !== 'activo') {
                        $datos['estado'] = $estadoNuevo;
                        if ($esTurno) {
                            $datos['turno_id'] = $turno->id;
                        }
                        $datos['maquina_id'] = $request->maquina_id ?? $asignacion->maquina_id ?? $user->maquina_id;
                    }

                    if ($request->has('entrada')) {
                        $datos['entrada'] = $request->entrada;
                    }
                    if ($request->has('salida')) {
                        $datos['salida'] = $request->salida;
                    }
                    if ($request->has('obra_id')) {
                        $datos['obra_id'] = $request->obra_id;
                    }

                    if ($asignacion) {
                        $asignacion->update($datos);
                    } else {
                        AsignacionTurno::create(array_merge($datos, [
                            'user_id' => $user->id,
                            'fecha'   => $dateStr,
                        ]));
                    }

                    $currentDate->addDay();
                }
            }

            return response()->json(['success' => 'Asignación completada.']);
        } catch (\Exception $e) {
            Log::error('❌ Error en store fusionado: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error al registrar la asignación.'], 500);
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

    public function update(Request $request, $id)
    {
        try {
            $asignacion = AsignacionTurno::findOrFail($id);

            // Validar los campos que puedes editar en línea
            $validated = $request->validate([
                'fecha' => 'nullable|date',
                'entrada' => 'nullable|date_format:H:i',
                'salida' => 'nullable|date_format:H:i',
                'maquina_id' => 'nullable|exists:maquinas,id',
                'obra_id' => 'nullable|exists:obras,id',
            ]);

            $asignacion->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Asignación actualizada correctamente.'
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error actualizando asignación', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la asignación.',
                'errors' => ['exception' => $e->getMessage()]
            ], 500);
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

            foreach ($asignaciones as $asignacion) {
                if ($tipo === 'eliminarTurnoEstado') {
                    $asignacion->delete();
                } elseif ($tipo === 'eliminarEstado') {
                    $nuevoEstado = $asignacion->turno_id ? 'activo' : null;

                    $asignacion->update([
                        'estado' => $nuevoEstado,
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

    public function show($id)
    {
        abort(404); // o simplemente return response('No disponible', 404);
    }

    public function asignarObra(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha' => 'required|date',
            'obra_id' => 'required|exists:obras,id'
        ]);

        // Buscar asignación de turno para ese día
        $asignacion = AsignacionTurno::where('user_id', $validated['user_id'])
            ->where('fecha', $validated['fecha'])
            ->first();

        // Si no existe, se crea
        if (!$asignacion) {
            $asignacion = new AsignacionTurno();
            $asignacion->user_id = $validated['user_id'];
            $asignacion->fecha = $validated['fecha'];

            // Si tienes un turno por defecto, por ejemplo el de mañana (ID = 1)
            $asignacion->turno_id = 1;
        }

        // Asignar o actualizar obra
        $asignacion->obra_id = $validated['obra_id'];
        $asignacion->save();

        $user = $asignacion->user()->with('categoria', 'maquina')->first();
        $turno = $asignacion->turno;

        return response()->json([
            'success' => true,
            'message' => '✅ Obra asignada correctamente',
            'asignacion' => $asignacion,
            'user' => $user,
            'turno' => $turno,
            'fecha' => $validated['fecha'],
            'obra_id' => $validated['obra_id']
        ]);
    }

    public function asignarObraMultiple(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'obra_id' => 'required|exists:obras,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);

        foreach ($request->user_ids as $userId) {
            $fecha = $fechaInicio->copy();
            while ($fecha->lte($fechaFin)) {
                AsignacionTurno::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'fecha' => $fecha->toDateString(),
                    ],
                    [
                        'obra_id' => $request->obra_id,
                        'turno_id' => 1 // Puedes adaptarlo si quieres que elija otro turno
                    ]
                );
                $fecha->addDay();
            }
        }

        return response()->json(['success' => true]);
    }
    public function updateObra(Request $request, $id)
    {
        $request->validate([
            'obra_id' => 'required|exists:obras,id',
            'fecha' => 'required|date',
        ]);

        $asignacion = AsignacionTurno::findOrFail($id);

        // Verificamos si ya existe otra asignación con mismo user y nueva fecha (evitamos duplicados)
        $existeOtra = AsignacionTurno::where('user_id', $asignacion->user_id)
            ->where('fecha', $request->fecha)
            ->where('id', '!=', $asignacion->id)
            ->first();

        if ($existeOtra) {
            return response()->json([
                'success' => false,
                'message' => '❌ Ya existe otra asignación para este usuario en esa fecha.'
            ]);
        }

        // Si no hay conflicto, actualizamos la asignación original
        $asignacion->obra_id = $request->obra_id;
        $asignacion->fecha = $request->fecha;
        $asignacion->save();

        return response()->json(['success' => true]);
    }
    public function repetirSemana(Request $request)
    {
        $request->validate([
            'fecha_actual' => 'required|date',
        ]);

        $inicioSemana = Carbon::parse($request->fecha_actual)->startOfWeek();
        $inicioAnterior = $inicioSemana->copy()->subWeek();
        $finAnterior = $inicioAnterior->copy()->endOfWeek();

        $asignaciones = AsignacionTurno::whereBetween('fecha', [$inicioAnterior, $finAnterior])->get();

        foreach ($asignaciones as $asignacion) {
            $nuevaFecha = Carbon::parse($asignacion->fecha)->addWeek();
            // Verifica si ya existe para evitar duplicados
            $existe = AsignacionTurno::where('user_id', $asignacion->user_id)
                ->whereDate('fecha', $nuevaFecha)
                ->exists();

            if (!$existe) {
                AsignacionTurno::create([
                    'user_id' => $asignacion->user_id,
                    'obra_id' => $asignacion->obra_id,
                    'fecha' => $nuevaFecha,
                    'estado' => $asignacion->estado,
                    'turno_id' => $asignacion->turno_id,
                    'maquina_id' => $asignacion->maquina_id,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }



    public function quitarObra($id)
    {
        $asignacion = AsignacionTurno::find($id);

        if (!$asignacion) {
            return response()->json([
                'success' => false,
                'message' => '❌ Asignación no encontrada.'
            ]);
        }

        $asignacion->delete();

        return response()->json([
            'success' => true,
            'message' => '🗑️ Asignación eliminada correctamente.'
        ]);
    }
}
