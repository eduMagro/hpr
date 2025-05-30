<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProduccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function trabajadores()
    {
        $estadoProduccionMaquinas = Maquina::selectRaw('maquinas.*, (
            SELECT COUNT(*) FROM elementos
            WHERE elementos.maquina_id_2 = maquinas.id
        ) as elementos_ensambladora')
            ->withCount(['elementos as elementos_count' => function ($query) {
                $query->where('estado', '!=', 'fabricado');
            }])
            ->get()
            ->mapWithKeys(function ($maquina) {
                $inProduction = $maquina->tipo === 'ensambladora'
                    ? $maquina->elementos_ensambladora > 0
                    : $maquina->elementos_count > 0;

                return [
                    $maquina->id => [
                        'nombre' => $maquina->nombre,
                        'codigo' => $maquina->codigo,
                        'en_produccion' => $inProduction,
                    ]
                ];
            });
        $maquinas = Maquina::orderBy('id')->get(['id', 'nombre', 'codigo'])->map(function ($maquina) {
            return [
                'id' => str_pad($maquina->id, 3, '0', STR_PAD_LEFT),
                'title' => $maquina->nombre,
                'codigo' => strtolower($maquina->codigo)
            ];
        });

        $trabajadores = User::with(['asignacionesTurnos.turno:id,hora_entrada,hora_salida', 'categoria', 'maquina'])
            ->where('rol', 'operario')
            ->whereNotNull('maquina_id') // aquí antes usabas `especialidad`
            ->get();

        $fechaHoy = Carbon::today()->subWeek();
        $fechaLimite = $fechaHoy->copy()->addDays(40);


        $eventos = [];

        foreach ($trabajadores as $trabajador) {
            foreach ($trabajador->asignacionesTurnos as $asignacionTurno) {
                // Ignorar turnos de vacaciones
                if ($asignacionTurno->turno_id == 10) {
                    continue;
                }
                $fechaTurno = Carbon::parse($asignacionTurno->fecha);

                if ($fechaTurno->between($fechaHoy, $fechaLimite)) {
                    $turno = $asignacionTurno->turno;

                    $horaEntrada = $turno?->hora_entrada ?? '08:00:00';
                    $horaSalida = $turno?->hora_salida ?? '16:00:00';

                    if ($horaEntrada === '22:00:00' && $horaSalida === '06:00:00') {
                        $start = Carbon::parse($asignacionTurno->fecha)->subDay()->format('Y-m-d') . 'T22:00:00';
                        $end = $asignacionTurno->fecha . 'T06:00:00';
                    } elseif ($horaEntrada === '06:00:00') {
                        $start = $asignacionTurno->fecha . 'T06:00:00';
                        $end = $asignacionTurno->fecha . 'T14:00:00';
                    } elseif ($horaEntrada === '14:00:00') {
                        $start = $asignacionTurno->fecha . 'T14:00:00';
                        $end = $asignacionTurno->fecha . 'T22:00:00';
                    } else {
                        $start = $asignacionTurno->fecha . 'T' . $horaEntrada;
                        $end = $asignacionTurno->fecha . 'T' . $horaSalida;
                    }

                    $maquinaId = $asignacionTurno->maquina_id ?? $trabajador->maquina_id;
                    $resourceId = $maquinaId ? str_pad($maquinaId, 3, '0', STR_PAD_LEFT) : null;

                    // 🕓 Formatear entrada y salida reales
                    $entrada = $asignacionTurno->entrada
                        ? Carbon::parse($asignacionTurno->entrada)->format('H:i')
                        : null;

                    $salida = $asignacionTurno->salida
                        ? Carbon::parse($asignacionTurno->salida)->format('H:i')
                        : null;

                    $estado = $asignacionTurno->estado ?? 'activo';

                    $mostrarEstado = $estado !== 'activo';

                    // Evita crear el evento normal si el estado no es 'activo'
                    if (!$mostrarEstado || in_array($estado, ['vacaciones', 'baja', 'justificada', 'injustificada'])) {

                        $entrada = $mostrarEstado
                            ? ucfirst($estado)
                            : ($asignacionTurno->entrada ? Carbon::parse($asignacionTurno->entrada)->format('H:i') : null);

                        $salida = $mostrarEstado
                            ? null
                            : ($asignacionTurno->salida ? Carbon::parse($asignacionTurno->salida)->format('H:i') : null);

                        $color = match ($estado) {
                            'vacaciones'      => ['bg' => '#f87171', 'border' => '#dc2626'],
                            'baja'            => ['bg' => '#FF8C00', 'border' => '#FF6600'],
                            'justificada'     => ['bg' => '#32CD32', 'border' => '#228B22'],
                            'injustificada'   => ['bg' => '#DC143C', 'border' => '#B22222'],
                            default           => ['bg' => '#3788d8', 'border' => '#3276b1'],
                        };

                        $eventos[] = [
                            'id' => 'turno-' . $asignacionTurno->id,
                            'title' => $trabajador->name,
                            'start' => $start,
                            'end' => $end,
                            'resourceId' => $resourceId,
                            'user_id' => $trabajador->id,
                            'backgroundColor' => $color['bg'],
                            'borderColor' => $color['border'],
                            'textColor' => '#ffffff',
                            'extendedProps' => [
                                'user_id' => $trabajador->id,
                                'categoria_id' => $trabajador->categoria_id,
                                'categoria_nombre' => $trabajador->categoria?->nombre,
                                'especialidad_nombre' => $trabajador->maquina?->nombre,
                                'entrada' => $entrada,
                                'salida' => $salida,
                            ],
                            'maquina_id' => $trabajador->maquina_id
                        ];
                    }
                }
            }
        }

        $trabajadoresEventos = $eventos;

        $fechaActual = Carbon::today();
        $ordenTurnos = [3 => 0, 1 => 1, 2 => 2]; // Noche, Mañana, Tarde

        $operariosTrabajando = User::where('rol', 'operario')
            ->whereHas('asignacionesTurnos', function ($query) use ($fechaActual) {
                $query->whereDate('fecha', $fechaActual)
                    ->where('turno_id', '<>', 10); // Excluir vacaciones
            })
            ->with(['asignacionesTurnos' => function ($query) use ($fechaActual) {
                $query->whereDate('fecha', $fechaActual)
                    ->where('turno_id', '<>', 10);
            }])
            ->get()
            ->sortBy(function ($user) use ($ordenTurnos) {
                $turnoId = $user->asignacionesTurnos->first()?->turno_id ?? 999;
                return $ordenTurnos[$turnoId] ?? 999; // Prioridad personalizada
            });

        $idsConEventos = collect($eventos)->pluck('trabajador.id')->unique();
        $trabajadoresSinEvento = $trabajadores->filter(fn($t) => !$idsConEventos->contains($t->id));

        // Log::info('Trabajadores sin eventos:', $trabajadoresSinEvento->pluck('name')->toArray());

        return view('produccion.trabajadores', compact('maquinas', 'trabajadoresEventos', 'operariosTrabajando', 'estadoProduccionMaquinas'));
    }

    public function actualizarPuesto(Request $request, $id)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',

            'turno_id' => 'nullable|exists:turnos,id', // por si lo sigues enviando
        ]);

        $asignacion = AsignacionTurno::findOrFail($id);

        $asignacion->update([
            'maquina_id' => $request->maquina_id,

            'turno_id' => $request->turno_id, // o puedes omitirlo si usas lógica automática
        ]);

        return response()->json(['message' => 'Actualización exitosa']);
    }

    //---------------------------------------------------------- MAQUINAS
    public function maquinas()
    {
        // ================================
        // 🔹 1. MÁQUINAS DISPONIBLES
        // ================================
        $maquinas = Maquina::whereNotNull('tipo')
            ->orderBy('id')
            ->get();
        $resources = $maquinas->map(function ($m) {
            return [
                'id' => $m->id,
                'title' => match ($m->estado) {
                    'activa' => '🟢 ' . $m->nombre,
                    'averiada' => '🔴 ' . $m->nombre,
                    'mantenimiento' => '🛠️ ' . $m->nombre,
                    'pausa' => '⏸️ ' . $m->nombre,
                    default => ' ' . $m->nombre,
                },
            ];
        });

        // ================================
        // 🔹 2. ELEMENTOS ACTIVOS
        // ================================
        $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina'])
            ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando']))
            ->where(fn($q) => $q->whereNull('estado')->orWhere('estado', '<>', 'fabricado'))
            ->get();

        // ================================
        // ✅ SECCIÓN 1: CALENDARIO DE COLA DE TRABAJO
        // ================================

        $elementosAgrupados = $elementos->groupBy(function ($elem) {
            $tipo = optional($elem->maquina)->tipo;
            return match ($tipo) {
                'ensambladora' => $elem->maquina_id_2,
                'soldadora'    => $elem->maquina_id_3 ?? $elem->maquina_id,
                default        => $elem->maquina_id,
            };
        })->filter(fn($_, $key) => !is_null($key));

        $planillasEventos = collect();
        $colasMaquinas = [];

        foreach ($maquinas as $m) {
            $ultimaPlanillaFabricando = Planilla::whereHas('elementos', fn($q) => $q->where('maquina_id', $m->id))
                ->where('estado', 'fabricando')
                ->orderByDesc('fecha_inicio')
                ->first();

            $colasMaquinas[$m->id] = isset($ultimaPlanillaFabricando->fecha_inicio)
                ? Carbon::createFromFormat('d/m/Y H:i', $ultimaPlanillaFabricando->fecha_inicio)
                : Carbon::now();
        }

        $erroresPlanillas = [];
        foreach ($elementosAgrupados as $maquinaId => $elementosGrupo) {
            $planillasPorMaquina = $elementosGrupo->groupBy('planilla_id');

            foreach ($planillasPorMaquina as $planillaId => $grupo) {
                $planilla = $grupo->first()->planilla;
                if (!$planilla || !$planilla->fecha_estimada_entrega) continue;

                $duracionSegundos = count($grupo) * 20 * 60; // 20 minutos por elemento

                if (!isset($colasMaquinas[$maquinaId])) {
                    $codigoPlanilla = $planilla->codigo_limpio ?? $planilla->id;
                    $erroresPlanillas[] = "⚠️ Planilla {$codigoPlanilla} asignada a máquina ID {$maquinaId} no tiene cola inicializada.";
                    continue;
                }

                $fechaInicio = $colasMaquinas[$maquinaId]->copy();
                $fechaFin = $fechaInicio->copy()->addSeconds($duracionSegundos);

                if ($planilla->estado === 'pendiente' && $colasMaquinas[$maquinaId]->equalTo(now())) {
                    $fechaInicio = now()->copy();
                    $fechaFin = $fechaInicio->copy()->addSeconds($duracionSegundos);
                }

                if ($planilla->estado === 'fabricando' && is_null($planilla->fecha_finalizacion)) {
                    $fechaFin = now()->copy()->addMinutes(1);
                }

                if ($planilla->estado === 'pendiente' && $fechaInicio->lt(now())) {
                    $fechaInicio = now()->copy();
                    $fechaFin = $fechaInicio->copy()->addSeconds($duracionSegundos);
                }

                $colasMaquinas[$maquinaId] = $fechaFin;

                $planillasEventos->push([
                    'id' => 'planilla-' . $planilla->id,
                    'title' => $planilla->codigo_limpio ?? 'Planilla #' . $planilla->id,
                    'start' => $fechaInicio->toIso8601String(),
                    'end' => $fechaFin->toIso8601String(),
                    'resourceId' => $maquinaId,
                    'backgroundColor' => $planilla->estado === 'pendiente' ? '#facc15' : '#60a5fa',
                    'extendedProps' => [
                        'obra' => optional($planilla->obra)->obra ?? '—',
                        'estado' => $planilla->estado,
                        'duracion_min' => round($duracionSegundos / 60, 2),
                    ],
                ]);
            }
        }

        // ================================
        // ✅ SECCIÓN 2: GRÁFICAS POR TURNO (CHART.JS)
        // ================================

        $turnosDefinidos = Turno::all();
        $cargaPorMaquinaTurnoConFechas = [];
        $elementosParaGraficas = Elemento::with(['planilla', 'planilla.obra'])
            ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando', 'completada']))
            ->get();

        foreach ($elementosParaGraficas as $e) {
            $planilla = $e->planilla;
            $maquinaId = $e->maquina_id;
            if (!$planilla || !$maquinaId) continue;

            $inicioPlanilla = $planilla->fecha_inicio_estimada ?? $planilla->created_at;
            $tiempoEnSegundos = is_numeric($e->tiempo_fabricacion) ? (float) $e->tiempo_fabricacion : 0;
            $fechaEstimada = Carbon::parse($inicioPlanilla)->addSeconds($tiempoEnSegundos);
            $horaEstimada = $fechaEstimada->format('H:i');

            $turnoDetectado = $turnosDefinidos->first(function ($t) use ($horaEstimada) {
                return $horaEstimada >= $t->hora_entrada && $horaEstimada < $t->hora_salida;
            });

            $nombreTurno = $turnoDetectado->nombre ?? 'mañana';

            if (!isset($cargaPorMaquinaTurnoConFechas[$maquinaId][$nombreTurno])) {
                $cargaPorMaquinaTurnoConFechas[$maquinaId][$nombreTurno] = [];
            }

            $cargaPorMaquinaTurnoConFechas[$maquinaId][$nombreTurno][] = [
                'peso' => $e->peso,
                'estado' => $e->estado,
                'fecha' => $fechaEstimada->toDateString(),
            ];
        }

        $turnos = ['mañana', 'tarde', 'noche'];
        $cargaPorMaquinaTurno = [];
        foreach ($maquinas as $maquina) {
            $cargaPorMaquinaTurno[$maquina->id] = array_fill_keys($turnos, [
                'esperado' => 0,
                'fabricado' => 0
            ]);

            foreach ($turnos as $turno) {
                $items = $cargaPorMaquinaTurnoConFechas[$maquina->id][$turno] ?? [];
                foreach ($items as $dato) {
                    $cargaPorMaquinaTurno[$maquina->id][$turno]['esperado'] += $dato['peso'];
                    if ($dato['estado'] === 'fabricado') {
                        $cargaPorMaquinaTurno[$maquina->id][$turno]['fabricado'] += $dato['peso'];
                    }
                }
            }
        }

        return view('produccion.maquinas', [
            'maquinas' => $maquinas,
            'planillasEventos' => $planillasEventos,
            'cargaPorMaquinaTurno' => $cargaPorMaquinaTurno,
            'erroresPlanillas' => $erroresPlanillas,
            'cargaPorMaquinaTurnoConFechas' => $cargaPorMaquinaTurnoConFechas,
            'resources' => $resources,
        ]);
    }

    public function reordenarPlanillas(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:planillas,id',
            'nueva_maquina_id' => 'required|integer|exists:maquinas,id',
            'nueva_fecha_inicio' => 'required|date',
        ]);

        $planilla = Planilla::findOrFail($request->id);
        $planilla->elementos()->update(['maquina_id' => $request->nueva_maquina_id]);

        $planilla->fecha_inicio = Carbon::parse($request->nueva_fecha_inicio);
        $planilla->estado = 'pendiente'; // opcional: reiniciar estado
        $planilla->save();

        return response()->json(['success' => true]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
