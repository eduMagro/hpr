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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AsignacionesTurnosExport;
use App\Models\Festivo;
use Carbon\CarbonPeriod;

class AsignacionTurnoController extends Controller
{
    private function escapeLike(string $value): string
    {
        // Escapa \ % _ para LIKE
        $value = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
        return "%{$value}%";
    }

    public function aplicarFiltros($query, Request $request)
    {
        // ID exacto
        if ($request->filled('id')) {
            $query->where('user_id', $request->input('id'));
        }

        // Empleado: name + apellidos (contains)
        if ($request->filled('empleado')) {
            $like = $this->escapeLike($request->empleado);
            $query->whereHas('user', function ($q) use ($like) {
                $q->whereRaw(
                    "CONCAT_WS(' ', COALESCE(name,''), COALESCE(primer_apellido,''), COALESCE(segundo_apellido,'')) LIKE ? ESCAPE '\\\\'",
                    [$like]
                );
            });
        }

        // Rango de fechas inclusivo
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $ini = Carbon::parse($request->fecha_inicio)->startOfDay();
            $fin = Carbon::parse($request->fecha_fin)->endOfDay();
            $query->whereBetween('fecha', [$ini, $fin]);
        } elseif ($request->filled('fecha_inicio')) {
            $ini = Carbon::parse($request->fecha_inicio)->startOfDay();
            $query->where('fecha', '>=', $ini);
        } elseif ($request->filled('fecha_fin')) {
            $fin = Carbon::parse($request->fecha_fin)->endOfDay();
            $query->where('fecha', '<=', $fin);
        }

        // Obra (contains por nombre/columna 'obra')
        if ($request->filled('obra')) {
            $like = $this->escapeLike($request->obra);
            $query->whereHas('obra', function ($q) use ($like) {
                $q->whereRaw("obra LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Turno (contains por 'nombre')
        if ($request->filled('turno')) {
            $like = $this->escapeLike($request->turno);
            $query->whereHas('turno', function ($q) use ($like) {
                $q->whereRaw("nombre LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Máquina (contains por 'nombre')
        if ($request->filled('maquina')) {
            $like = $this->escapeLike($request->maquina);
            $query->whereHas('maquina', function ($q) use ($like) {
                $q->whereRaw("nombre LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Entrada / Salida (TIME → CAST a CHAR antes del LIKE)
        if ($request->filled('entrada')) {
            $like = $this->escapeLike($request->entrada);
            $query->whereRaw("CAST(entrada AS CHAR) LIKE ? ESCAPE '\\\\'", [$like]);
        }
        if ($request->filled('salida')) {
            $like = $this->escapeLike($request->salida);
            $query->whereRaw("CAST(salida AS CHAR) LIKE ? ESCAPE '\\\\'", [$like]);
        }

        return $query;
    }

    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('id')) {
            $filtros[] = 'ID Empleado: <strong>' . e($request->id) . '</strong>';
        }
        if ($request->filled('empleado')) {
            $filtros[] = 'Empleado: <strong>' . e($request->empleado) . '</strong>';
        }
        if ($request->filled('fecha_inicio') || $request->filled('fecha_fin')) {
            $rango = ($request->fecha_inicio ?? '—') . ' a ' . ($request->fecha_fin ?? '—');
            $filtros[] = 'Fecha: <strong>' . e($rango) . '</strong>';
        }
        if ($request->filled('obra')) {
            $filtros[] = 'Obra: <strong>' . e($request->obra) . '</strong>';
        }
        if ($request->filled('turno')) {
            $filtros[] = 'Turno: <strong>' . e($request->turno) . '</strong>';
        }
        if ($request->filled('maquina')) {
            $filtros[] = 'Máquina: <strong>' . e($request->maquina) . '</strong>';
        }
        if ($request->filled('entrada')) {
            $filtros[] = 'Entrada: <strong>' . e($request->entrada) . '</strong>';
        }
        if ($request->filled('salida')) {
            $filtros[] = 'Salida: <strong>' . e($request->salida) . '</strong>';
        }

        // 🧭 Unifica el nombre del parámetro de orden
        $sort   = $request->input('sort');
        $order  = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        if ($sort) {
            $filtros[] = 'Ordenado por <strong>' . e($sort) . '</strong> en <strong>' . ($order === 'asc' ? 'ascendente' : 'descendente') . '</strong>';
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . e($request->per_page) . '</strong> registros por página';
        }

        return $filtros;
    }

    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort  = request('sort');
        $currentOrder = request('order'); // ← usamos 'order' para ser coherentes
        $isSorted     = $currentSort === $columna;
        $nextOrder    = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? '▲' : '▼')
            : '⇅';

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . e($url) . '" class="inline-flex items-center space-x-1">' .
            '<span>' . e($titulo) . '</span><span class="text-xs">' . $icon . '</span></a>';
    }

    private function aplicarOrdenamiento($query, Request $request)
    {
        $sort  = $request->input('sort', 'fecha');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Mapea a columnas totalmente calificadas para evitar ambigüedad tras joins
        $map = [
            'user_id'    => 'asignaciones_turnos.user_id',
            'fecha'      => 'asignaciones_turnos.fecha',
            'obra_id'    => 'asignaciones_turnos.obra_id',
            'turno_id'   => 'asignaciones_turnos.turno_id',
            'maquina_id' => 'asignaciones_turnos.maquina_id',
            'entrada'    => 'asignaciones_turnos.entrada',
            'salida'     => 'asignaciones_turnos.salida',
        ];

        if (!array_key_exists($sort, $map)) {
            $sort = 'fecha';
        }

        // 1) Borra órdenes previos
        $query->reorder($map[$sort], $order);

        // 2) (Opcional) añade **orden secundario** estable
        //    - si ordenas por fecha, mantén el orden por turno (mañana/tarde/noche) como desempate
        if ($sort === 'fecha') {
            $query->orderByRaw("FIELD(turnos.nombre, 'mañana', 'tarde', 'noche')");
        }

        // 3) (Opcional) último desempate siempre por ID para estabilidad
        $query->orderBy('asignaciones_turnos.id', 'asc');

        return $query;
    }



    public function index(Request $request)
    {
        // 1. QUERY BASE (filtros normales con empleado)
        $query = AsignacionTurno::with(['user', 'turno', 'maquina', 'obra'])
            ->whereDate('fecha', '<=', Carbon::tomorrow())
            ->where('estado', 'activo')
            ->whereHas('turno', fn($q) => $q->where('nombre', '!=', 'vacaciones'))
            ->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->select('asignaciones_turnos.*');



        // aplicar filtros
        $query = $this->aplicarFiltros($query, $request);

        // aplicar ordenamiento separado
        $query = $this->aplicarOrdenamiento($query, $request);
        $ordenables = [
            'user_id'    => $this->getOrdenamiento('user_id', 'ID Empleado'),
            'fecha'      => $this->getOrdenamiento('fecha', 'Fecha'),
            'obra_id'    => $this->getOrdenamiento('obra_id', 'Lugar'),
            'turno_id'   => $this->getOrdenamiento('turno_id', 'Turno'),
            'maquina_id' => $this->getOrdenamiento('maquina_id', 'Máquina'),
            'entrada'    => $this->getOrdenamiento('entrada', 'Entrada'),
            'salida'     => $this->getOrdenamiento('salida', 'Salida'),
        ];

        $perPage = $request->input('per_page', 15);
        $asignaciones = $query->paginate($perPage)->withQueryString();
        // 🔹 Filtros activos para mostrarlos en la vista
        $filtrosActivos = $this->filtrosActivos($request);

        // 🔹 Turnos para los select
        $turnos = Turno::where('nombre', '!=', 'festivo')->orderBy('nombre')->get();

        // 2. Estadísticas del trabajador (cuando se filtra por nombre)
        $asignacionesFiltradas = (clone $query)->get();

        $diasAsignados = 0;
        $diasFichados = 0;
        $diasPuntuales = 0;
        $diasImpuntuales = 0;
        $diasSeVaAntes = 0;
        $diasSinFichaje = 0;

        foreach ($asignacionesFiltradas as $asignacion) {
            $esperadaEntrada = $asignacion->turno->hora_inicio ?? null;
            $esperadaSalida = $asignacion->turno->hora_fin ?? null;

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
                $esperadaEntrada = $asignacion->turno->hora_inicio ?? null;
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
            'totalSolicitudesPendientes',
            'filtrosActivos',
            'ordenables',
        ));
    }

    public function fichar(Request $request)
    {

        try {
            /* 1) Validación y permisos ------------------------------------------------ */
            $request->validate([
                'user_id'  => 'required|exists:users,id',
                'tipo'     => 'required|in:entrada,salida',
                'latitud'  => 'required|numeric',
                'longitud' => 'required|numeric',
            ]);
            Log::info('Coordenadas recibidas', [
                'latitud'  => $request->latitud,
                'longitud' => $request->longitud,
            ]);

            $user = User::findOrFail($request->user_id);
            if ($user->rol !== 'operario') {
                return response()->json(['error' => 'No tienes permisos para fichar.'], 403);
            }

            /* 2) Obra cercana --------------------------------------------------------- */
            $obraEncontrada = $this->buscarObraCercana($request->latitud, $request->longitud);
            if (!$obraEncontrada) {
                return response()->json(['error' => 'No estás dentro de ninguna zona de trabajo.'], 403);
            }

            /* 3) Hora actual + detección de turno/fecha ------------------------------ */
            $ahora = now();
            $horaActual = $ahora->format('H:i:s');

            [$turnoDetectado, $fechaTurnoDetectado] = $this->detectarTurnoYFecha($ahora);
            if (!$turnoDetectado || !$fechaTurnoDetectado) {
                return response()->json(['error' => 'No se pudo determinar el turno para esta hora.'], 403);
            }

            $turnoModelo = Turno::where('nombre', $turnoDetectado)->first();
            if (!$turnoModelo) {
                return response()->json(['error' => "No existe configurado el turno '{$turnoDetectado}'."], 500);
            }

            /* 4) Rama por tipo de fichaje -------------------------------------------- */
            $warning = null;

            if ($request->tipo === 'entrada') {
                // ======= ENTRADA ======================================================
                // Buscar asignación del día detectado o crearla
                $asignacion = $user->asignacionesTurnos()
                    ->whereDate('fecha', $fechaTurnoDetectado)
                    ->with('turno')
                    ->first();

                if (!$asignacion) {
                    $asignacion = $user->asignacionesTurnos()->create([
                        'fecha'      => $fechaTurnoDetectado,
                        'turno_id'   => $turnoModelo->id,
                        'estado'     => 'activo',
                        'maquina_id' => $user->maquina_id ?? null,
                        'obra_id'    => null,
                    ]);

                    // (Opcional) notificación a programadores
                    try {
                        $programadores = User::whereHas('departamentos', fn($q) => $q->where('nombre', 'Programador'))->get();
                        $alerta = Alerta::create([
                            'mensaje'   => "🆕 Turno creado automáticamente ({$turnoDetectado}) para {$user->nombre_completo} en {$fechaTurnoDetectado}.",
                            'tipo'      => 'Info Turnos',
                            'leida'     => false,
                        ]);
                        foreach ($programadores as $p) {
                            AlertaLeida::firstOrCreate(['alerta_id' => $alerta->id, 'user_id' => $p->id]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('No se pudo notificar creación asignación: ' . $e->getMessage());
                    }
                } else {
                    // Si existe pero con otro turno, lo corregimos automáticamente
                    if (strtolower($asignacion->turno->nombre) !== strtolower($turnoDetectado)) {
                        $asignacion->turno_id = $turnoModelo->id;
                        $asignacion->save();

                        try {
                            $programadores = User::whereHas('departamentos', fn($q) => $q->where('nombre', 'Programador'))->get();
                            $alerta = Alerta::create([
                                'mensaje'   => "🔁 Corregido turno a '{$turnoDetectado}' para {$user->nombre_completo} en {$fechaTurnoDetectado}.",
                                'tipo'      => 'Info Turnos',
                                'leida'     => false,
                            ]);
                            foreach ($programadores as $p) {
                                AlertaLeida::firstOrCreate(['alerta_id' => $alerta->id, 'user_id' => $p->id]);
                            }
                        } catch (\Throwable $e) {
                            Log::error('No se pudo notificar corrección turno: ' . $e->getMessage());
                        }
                    }
                }

                // Validación de horario (entrada)
                if (!$this->validarHoraEntrada($turnoDetectado, $horaActual)) {
                    $warning = 'Has fichado entrada fuera de tu horario.';
                }

                // Guardar entrada y obra
                $asignacion->update([
                    'entrada' => $horaActual,
                    'obra_id' => $obraEncontrada->id,
                ]);

                return response()->json([
                    'success'     => 'Entrada registrada.',
                    'warning'     => $warning,
                    'obra_nombre' => $obraEncontrada->obra,
                ]);
            }

            // ======= SALIDA ==========================================================
            // Intentar cerrar la asignación abierta más razonable (últimas 36h)
            $asignacion = $this->buscarAsignacionAbiertaParaSalida($user, $ahora);

            // Fallback: usar el día detectado si no hay abierta
            if (!$asignacion) {
                $asignacion = $user->asignacionesTurnos()
                    ->whereDate('fecha', $fechaTurnoDetectado)
                    ->orderByDesc('id')
                    ->first();
            }

            if (!$asignacion) {
                // Si aun así no hay, crearla para no perder el fichaje
                $asignacion = $user->asignacionesTurnos()->create([
                    'fecha'      => $fechaTurnoDetectado,
                    'turno_id'   => $turnoModelo->id,
                    'estado'     => 'activo',
                    'maquina_id' => $user->maquina_id ?? null,
                    'obra_id'    => null,
                ]);
                $warning = 'No había una asignación abierta; se ha creado una nueva para registrar la salida.';
            }

            if (!$asignacion->entrada) {
                $warning = 'Estás registrando una salida sin haber fichado entrada.';
            }

            if (!$this->validarHoraSalida($turnoDetectado, $horaActual)) {
                $warning = 'Has fichado salida fuera de tu horario.';
            }

            $asignacion->update([
                'salida'  => $horaActual,
                'obra_id' => $obraEncontrada->id,
            ]);

            return response()->json([
                'success'     => 'Salida registrada.',
                'warning'     => $warning,
                'obra_nombre' => $obraEncontrada->obra,
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Error en fichaje', ['exception' => $e]);
            return response()->json(['error' => 'Error al registrar el fichaje: ' . $e->getMessage()], 500);
        }
    }

    /* ===================== HELPERS ===================== */

    /**
     * Detecta el turno y la fecha a la que debe imputarse.
     * - Noche: 19:00–23:59 → fecha +1 ; 00:00–03:59 → fecha actual (sigue la noche previa)
     * - Mañana: 04:00–11:59 → fecha actual
     * - Tarde: 12:00–18:59 → fecha actual
     */
    private function detectarTurnoYFecha(Carbon $ahora): array
    {
        $hora   = Carbon::createFromFormat('H:i:s', $ahora->format('H:i:s'));
        $fecha  = $ahora->toDateString();
        $fechaS = $ahora->copy()->addDay()->toDateString();

        if ($hora->between(Carbon::createFromTime(19, 0), Carbon::createFromTime(23, 59))) {
            return ['noche', $fechaS];
        }
        if ($hora->between(Carbon::createFromTime(0, 0), Carbon::createFromTime(3, 59))) {
            return ['noche', $fecha];
        }
        if ($hora->between(Carbon::createFromTime(4, 0), Carbon::createFromTime(11, 59))) {
            return ['mañana', $fecha];
        }
        if ($hora->between(Carbon::createFromTime(12, 0), Carbon::createFromTime(18, 59))) {
            return ['tarde', $fecha];
        }
        return [null, null];
    }

    /**
     * Devuelve la obra activa más cercana dentro de su radio permitido.
     */
    private function buscarObraCercana(float $lat, float $lon): ?Obra
    {
        $obras = Obra::where('estado', 'activa')->get();

        $mejor = null;
        $distMin = null;

        foreach ($obras as $obra) {
            $dist = $this->calcularDistancia($lat, $lon, $obra->latitud, $obra->longitud);
            if ($dist <= $obra->distancia) {
                if (is_null($distMin) || $dist < $distMin) {
                    $distMin = $dist;
                    $mejor = $obra;
                }
            }
        }

        return $mejor;
    }

    /**
     * Para SALIDA: intenta encontrar la asignación abierta más razonable
     * en las últimas 36h (entrada no nula y salida nula).
     */
    private function buscarAsignacionAbiertaParaSalida(User $user, Carbon $ahora): ?AsignacionTurno
    {
        $desde = $ahora->copy()->subHours(36)->toDateString();
        $hasta = $ahora->toDateString();

        return $user->asignacionesTurnos()
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereNotNull('entrada')
            ->whereNull('salida')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();
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

            $tipo        = $request->tipo;
            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin    = Carbon::parse($request->fecha_fin);

            // 🔹 NUEVO COMPORTAMIENTO PARA FESTIVOS
            if ($tipo === 'festivo') {
                $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
                $titulo  = $request->filled('titulo') ? $request->titulo : 'Festivo';

                $filas = collect($periodo)->map(function ($fecha) use ($titulo) {
                    return [
                        'titulo'     => $titulo,
                        'fecha'      => $fecha->toDateString(),
                        'anio'       => (int) $fecha->format('Y'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });

                // Evita duplicados: upsert por (anio, fecha) y actualiza título si cambia
                Festivo::upsert(
                    $filas->toArray(),
                    ['anio', 'fecha'],
                    ['titulo', 'updated_at']
                );

                return response()->json([
                    'success' => "Se han registrado {$filas->count()} día(s) festivo(s) en la tabla.",
                ]);
            }


            // ====== A partir de aquí, el flujo normal para turnos/estados/vacaciones ======

            $turnosValidos = Turno::pluck('nombre')->toArray();
            $esTurno = in_array($tipo, $turnosValidos);
            $turno = $esTurno ? Turno::where('nombre', $tipo)->first() : null;

            // Ahora los festivos vienen de tu tabla
            $festivos = collect($this->getFestivos())->pluck('start')->toArray();

            // Solo el usuario seleccionado (ya no “todos” en caso de festivo)
            $usuarios = collect([User::findOrFail($request->user_id)]);

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

    public function actualizarHoras(Request $request, $id)
    {
        try {
            $request->validate(
                [
                    'entrada' => 'nullable|date_format:H:i',
                    'salida'  => 'nullable|date_format:H:i',
                ],
                [
                    'entrada.date_format' => 'El campo entrada debe tener el formato HH:mm (por ejemplo 08:30).',
                    'salida.date_format'  => 'El campo salida debe tener el formato HH:mm (por ejemplo 17:45).',
                ]
            );

            $asignacion = AsignacionTurno::findOrFail($id);
            $asignacion->entrada = $request->entrada;
            $asignacion->salida  = $request->salida;
            $asignacion->save();

            return response()->json([
                'ok'      => true,
                'entrada' => $request->entrada,
                'salida'  => $request->salida,
                'message' => 'Horas actualizadas correctamente'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación
            return response()->json([
                'ok'      => false,
                'message' => 'Datos no válidos',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Cualquier otro error
            return response()->json([
                'ok'      => false,
                'message' => 'Error al actualizar horas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::debug('Request completo', $request->all());

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
        Log::debug('🪵 asignarObra payload:', $request->all()); // 👈 Log de entrada
        // 👇 Forzar null si viene cadena vacía
        if ($request->obra_id === '') {
            $request->merge(['obra_id' => null]);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha' => 'required|date',
            'obra_id' => ['nullable', 'integer', 'exists:obras,id']

        ]);
        $turnoMontajeId = Turno::where('nombre', 'montaje')->firstOrFail()->id;

        // Buscar asignación de turno para ese día
        $asignacion = AsignacionTurno::where('user_id', $validated['user_id'])
            ->where('fecha', $validated['fecha'])
            ->first();

        // Si no existe, se crea
        if (!$asignacion) {
            $asignacion = new AsignacionTurno();
            $asignacion->user_id = $validated['user_id'];
            $asignacion->fecha = $validated['fecha'];

            $asignacion->turno_id = $turnoMontajeId;
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
        Log::debug('🪵 asignarObra multiple payload:', $request->all()); // 👈 Log de entrada
        // 👇 Forzar null si viene cadena vacía
        if ($request->obra_id === '') {
            $request->merge(['obra_id' => null]);
        }

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'obra_id' => ['nullable', 'integer', 'exists:obras,id'],

            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $turnoMontajeId = Turno::where('nombre', 'montaje')->firstOrFail()->id; // 👈 AÑADIDO

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
                        'turno_id' => $turnoMontajeId,
                    ]
                );
                $fecha->addDay();
            }
        }

        return response()->json(['success' => true]);
    }


    public function updateObra(Request $request, $id)
    {
        Log::debug('🪵 Actualizar obra
         :', $request->all()); // 👈 Log de entrada
        // 🛠️ Corregimos obra_id si llega como string vacío
        if (in_array($request->obra_id, ['', 'sin-obra', 'null', null], true)) {
            $request->merge(['obra_id' => null]);
        }


        $request->validate([
            'obra_id' => 'nullable|exists:obras,id',
            'fecha' => 'required|date',
        ]);

        $asignacion = AsignacionTurno::findOrFail($id);

        $existeOtra = AsignacionTurno::where('user_id', $asignacion->user_id)
            ->where('fecha', $request->fecha)
            ->where('id', '!=', $asignacion->id)
            ->first();

        if ($existeOtra) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe otra asignación para este usuario en esa fecha.'
            ]);
        }

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

    public function export(Request $request)
    {
        // 💡 Usa la misma query base que index()
        $query = AsignacionTurno::with(['user', 'turno', 'maquina', 'obra'])
            ->whereDate('fecha', '<=', Carbon::tomorrow())
            ->where('estado', 'activo')
            ->whereHas('turno', fn($q) => $q->where('nombre', '!=', 'vacaciones'))
            ->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->orderBy('fecha', 'desc')
            ->orderByRaw("FIELD(turnos.nombre, 'mañana', 'tarde', 'noche')")
            ->orderBy('asignaciones_turnos.id')
            ->select('asignaciones_turnos.*');

        // 🔍 aplica los mismos filtros
        $query = $this->aplicarFiltros($query, $request);

        // 🔀 aplica el mismo orden dinámico
        $query = $this->aplicarOrdenamiento($query, $request);

        // 🔥 ejecuta la query
        $asignaciones = $query->get();

        return Excel::download(
            new AsignacionesTurnosExport($asignaciones),
            'Registros entrada y salida.xlsx'
        );
    }
}
