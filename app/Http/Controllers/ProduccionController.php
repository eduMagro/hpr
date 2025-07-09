<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Models\Planilla;
use App\Models\OrdenPlanilla;
use App\Models\Elemento;
use App\Models\Empresa;
use App\Models\Obra;
use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

function toCarbon($valor, $format = 'd/m/Y H:i')
{
    if ($valor instanceof Carbon) return $valor;
    if (empty($valor)) return null;

    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $valor)) {
        return Carbon::parse($valor);
    }

    return Carbon::createFromFormat(strlen($valor) === 19 ? 'd/m/Y H:i:s' : $format, $valor);
}
class ProduccionController extends Controller
{
    //---------------------------------------------------------- PLANIFICACION TRABAJADORES ALMACEN
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
        $obraIds = $trabajadores
            ->flatMap(fn($t) => $t->asignacionesTurnos)
            ->filter(fn($a) => $a->estado === 'activo' && $a->obra_id)
            ->pluck('obra_id')
            ->unique()
            ->values();

        $coloresPorObra = [];
        $paleta = ['#60a5fa', '#34d399', '#fbbf24', '#f472b6', '#a78bfa', '#fb7185', '#10b981', '#f97316', '#06b6d4', '#c084fc'];

        foreach ($obraIds as $index => $obraId) {
            $coloresPorObra[$obraId] = [
                'bg' => $paleta[$index % count($paleta)],
                'border' => $paleta[$index % count($paleta)],
            ];
        }


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

                        if (in_array($estado, ['vacaciones', 'baja', 'justificada', 'injustificada'])) {
                            $color = match ($estado) {
                                'vacaciones'      => ['bg' => '#f87171', 'border' => '#dc2626'],
                                'baja'            => ['bg' => '#FF8C00', 'border' => '#FF6600'],
                                'justificada'     => ['bg' => '#32CD32', 'border' => '#228B22'],
                                'injustificada'   => ['bg' => '#DC143C', 'border' => '#B22222'],
                            };
                        } else {
                            $obraId = $asignacionTurno->obra_id;
                            $color = $coloresPorObra[$obraId] ?? ['bg' => '#9ca3af', 'border' => '#6b7280']; // gris por defecto
                        }

                        $eventos[] = [
                            'id' => 'turno-' . $asignacionTurno->id,
                            'title' => $trabajador->nombre_completo,
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
                                'foto' => $trabajador->ruta_imagen,
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

        $registroFichajes = collect($eventos)
            ->filter(fn($e) => isset($e['extendedProps']['entrada']) || isset($e['extendedProps']['salida']))
            ->mapWithKeys(function ($evento) {
                return [
                    $evento['user_id'] => [
                        'entrada' => $evento['extendedProps']['entrada'] ?? null,
                        'salida' => $evento['extendedProps']['salida'] ?? null,
                    ]
                ];
            });

        // Log::info('Trabajadores sin eventos:', $trabajadoresSinEvento->pluck('name')->toArray());

        return view('produccion.trabajadores', compact('maquinas', 'trabajadoresEventos', 'operariosTrabajando', 'estadoProduccionMaquinas', 'registroFichajes'));
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

        // Agrupar primero por planilla
        $planillasAgrupadas = $elementos->groupBy(function ($e) {
            $tipo = optional($e->maquina)->tipo;

            $maquinaId = match ($tipo) {
                'ensambladora' => $e->maquina_id_2,
                'soldadora'    => $e->maquina_id_3 ?? $e->maquina_id,
                default        => $e->maquina_id,
            };

            return $e->planilla_id . '-' . $maquinaId;
        })->map(function ($grupo) {
            $primerElemento = $grupo->first();
            $tipo = optional($primerElemento->maquina)->tipo;

            $maquinaId = match ($tipo) {
                'ensambladora' => $primerElemento->maquina_id_2,
                'soldadora'    => $primerElemento->maquina_id_3 ?? $primerElemento->maquina_id,
                default        => $primerElemento->maquina_id,
            };

            return [
                'planilla' => $primerElemento->planilla,
                'elementos' => $grupo,
                'maquina_id' => $maquinaId,
            ];
        })->filter(fn($data) => !is_null($data['maquina_id']));


        $planillasEventos = collect();
        $colasMaquinas = [];

        foreach ($maquinas as $m) {
            $ultimaPlanillaFabricando = Planilla::whereHas('elementos', fn($q) => $q->where('maquina_id', $m->id))
                ->where('estado', 'fabricando')
                ->orderByDesc('fecha_inicio')
                ->first();

            $colasMaquinas[$m->id] = optional($ultimaPlanillaFabricando)->fecha_inicio
                ? toCarbon($ultimaPlanillaFabricando->fecha_inicio)
                : Carbon::now();
        }

        $erroresPlanillas = [];
        $ordenes = OrdenPlanilla::orderBy('posicion')
            ->get()
            ->groupBy('maquina_id')
            ->map(function ($ordenesMaquina) {
                return $ordenesMaquina->pluck('planilla_id')->all();
            });

        foreach ($planillasAgrupadas as $planillaId => $data) {
            $planilla = $data['planilla'];
            $grupo = $data['elementos'];
            $maquinaId = $data['maquina_id'];

            if (!$planilla || !$planilla->fecha_estimada_entrega) continue;

            $ordenPlanillas = $ordenes[$maquinaId] ?? [];

            // Solo seguimos si está en la cola de esta máquina o no hay orden definida
            if (!empty($ordenPlanillas) && !in_array($planilla->id, $ordenPlanillas)) {
                continue;
            }

            $duracionSegundos = max(count($grupo) * 20 * 60, 60); // mínimo 1 minuto
            $fechaInicio = $colasMaquinas[$maquinaId]->copy();
            $fechaFin = $fechaInicio->copy()->addSeconds($duracionSegundos);
            $colasMaquinas[$maquinaId] = $fechaFin;

            $esPrimera = !empty($ordenPlanillas) && $ordenPlanillas[0] === $planilla->id;

            // Solo si está fabricando en esta máquina y es la primera
            $mostrarProgreso = $esPrimera;

            // Calcular progreso solo si se va a mostrar
            $progreso = null;
            if ($mostrarProgreso) {
                $completados = $grupo->where('estado', 'fabricado')->count();
                $total = $grupo->count();
                $progreso = $total > 0 ? round(($completados / $total) * 100) : 0;
            }


            $planillasEventos->push([
                'id' => 'planilla-' . $planilla->id,
                'title' => $planilla->codigo_limpio ?? 'Planilla #' . $planilla->id,
                'codigo' => $planilla->codigo_limpio ?? 'Planilla #' . $planilla->id,
                'start' => $fechaInicio->toIso8601String(),
                'end' => $fechaFin->toIso8601String(),
                'resourceId' => $maquinaId,
                'backgroundColor' => '#facc15',
                'extendedProps' => [
                    'obra' => optional($planilla->obra)->obra ?? '—',
                    'estado' => $planilla->estado,
                    'duracion_horas' => round($duracionSegundos / 3600, 2),
                    'progreso' => $progreso,
                ],
            ]);
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

        $tablaPlanillas = collect();

        $group = $planillasEventos->groupBy('codigo');   // “PLA-001”, etc.

        foreach ($group as $codigo => $events) {
            $ultimoFin = Carbon::parse(
                $events->max('end')                     // ISO-8601 de FullCalendar
            );

            // Id de planilla sale de “planilla-123”
            $planillaId = (int) Str::after($events->first()['id'], 'planilla-');
            $planilla   = Planilla::find($planillaId);

            // Puede que aún no exista fecha_estimada_entrega
            $entrega = null;
            if ($planilla && $planilla->fecha_estimada_entrega) {
                try {
                    $entrega = toCarbon($planilla->fecha_estimada_entrega);
                } catch (\Exception $e) {
                    // Puedes registrar el error o marcarla como inválida si quieres
                    $entrega = null;
                }
            }


            $onTime = $entrega ? $ultimoFin->lte($entrega) : null;

            $tablaPlanillas->push([
                'codigo'            => $codigo,
                'planilla_id'       => $planillaId,
                'fin_programado'    => $ultimoFin->format('d/m/Y H:i'),
                'entrega_estimada'  => $entrega?->format('d/m/Y H:i') ?? '—',
                'estado'            => $onTime === null ? 'Sin fecha'
                    : ($onTime ? '🟢 En tiempo' : '🔴 Retraso'),
            ]);
        }
        return view('produccion.maquinas', [
            'maquinas' => $maquinas,
            'planillasEventos' => $planillasEventos,
            'tablaPlanillas'             => $tablaPlanillas,
            'cargaPorMaquinaTurno' => $cargaPorMaquinaTurno,
            'erroresPlanillas' => $erroresPlanillas,
            'cargaPorMaquinaTurnoConFechas' => $cargaPorMaquinaTurnoConFechas,
            'resources' => $resources,
        ]);
    }

    public function reordenarPlanillas(Request $request)
    {
        $request->validate([
            'id'          => 'required|integer|exists:planillas,id',
            'maquina_id'  => 'required|integer|exists:maquinas,id',
            'nueva_posicion' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($request) {

                /* 1️⃣  Fila exacta planilla+máquina */
                $orden = OrdenPlanilla::lockForUpdate()
                    ->where('planilla_id', $request->id)
                    ->where('maquina_id', $request->maquina_id)   // 👈 ahora filtra también por máquina
                    ->firstOrFail();

                $maquinaId = $orden->maquina_id;
                $posActual = $orden->posicion;
                $posNueva  = $request->nueva_posicion;

                /* 2️⃣  Regla posición-1 */
                if ($posNueva == 1) {
                    $planillaPos1 = OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->where('posicion', 1)
                        ->first()?->planilla;

                    if ($planillaPos1 && $planillaPos1->estado === 'fabricando') {
                        throw new \Exception('No se puede ocupar la posición 1: hay una planilla fabricando.');
                    }
                }

                /* 3️⃣  Límites y nada-que-hacer */
                $maxPos = OrdenPlanilla::where('maquina_id', $maquinaId)->max('posicion');
                $posNueva = min($posNueva, $maxPos);
                if ($posNueva == $posActual) return;

                /* 4️⃣  Reacomodo */
                if ($posNueva < $posActual) {
                    OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->whereBetween('posicion', [$posNueva, $posActual - 1])
                        ->increment('posicion');
                } else {
                    OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->whereBetween('posicion', [$posActual + 1, $posNueva])
                        ->decrement('posicion');
                }

                /* 5️⃣  Actualiza la fila correcta */
                $orden->update(['posicion' => $posNueva]);
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }


    //---------------------------------------------------------- PLANIFICACION TRABAJADORES OBRA
    public function trabajadoresObra()
    {
        // 1. Obtener las dos empresas por nombre
        $hprServicios = Empresa::where('nombre', 'HPR Servicios en Obra S.L.')->firstOrFail();
        $hpr = Empresa::where('nombre', 'Hierros Paco Reyes S.L.')->firstOrFail();

        // 2. Obtener los trabajadores de cada empresa
        $trabajadoresServicios = User::with(['asignacionesTurnos.turno', 'categoria', 'maquina'])
            ->where('empresa_id', $hprServicios->id)
            ->where('rol', 'operario')
            ->get();

        $trabajadoresHpr = User::with(['asignacionesTurnos.turno', 'categoria', 'maquina'])
            ->where('empresa_id', $hpr->id)
            ->where('rol', 'operario')
            ->get();

        // 3. Obras activas de tipo montaje
        $obrasActivas = Obra::where('tipo', 'montaje')->get();

        // 4. TODAS las obras (para usarlas donde necesites)
        $todasLasObras = Obra::orderBy('obra')->get();

        $resources = $obrasActivas->map(fn($obra) => [
            'id' => $obra->id,
            'title' => $obra->obra,
            'codigo' => $obra->cod_obra,
        ]);

        // 5. Generar eventos
        $eventos = [];

        foreach ($trabajadoresServicios as $trabajador) {
            foreach ($trabajador->asignacionesTurnos as $asignacion) {
                $turno = $asignacion->turno;
                if (!$turno || !$asignacion->obra_id) continue;

                $horaEntrada = $turno->hora_entrada ?? '08:00:00';
                $horaSalida  = $turno->hora_salida ?? '16:00:00';

                $eventos[] = [
                    'id' => 'turno-' . $asignacion->id,
                    'title' => $trabajador->nombre_completo,
                    'start' => $asignacion->fecha . 'T' . $horaEntrada,
                    'end' => $asignacion->fecha . 'T' . $horaSalida,
                    'resourceId' => $asignacion->obra_id,
                    'extendedProps' => [
                        'user_id' => $trabajador->id,
                        'empresa' => 'HPR Servicios',
                        'categoria_nombre' => $trabajador->categoria?->nombre,
                        'especialidad_nombre' => $trabajador->maquina?->nombre,
                    ],
                ];
            }
        }

        foreach ($trabajadoresHpr as $trabajador) {
            foreach ($trabajador->asignacionesTurnos as $asignacion) {
                $turno = $asignacion->turno;
                if (!$turno || !$asignacion->obra_id) continue;

                $horaEntrada = $turno->hora_entrada ?? '08:00:00';
                $horaSalida  = $turno->hora_salida ?? '16:00:00';

                $eventos[] = [
                    'id' => 'turno-' . $asignacion->id,
                    'title' => $trabajador->nombre_completo,
                    'start' => $asignacion->fecha . 'T' . $horaEntrada,
                    'end' => $asignacion->fecha . 'T' . $horaSalida,
                    'resourceId' => $asignacion->obra_id,
                    'extendedProps' => [
                        'user_id' => $trabajador->id,
                        'empresa' => 'Hierros Paco Reyes',
                        'categoria_nombre' => $trabajador->categoria?->nombre,
                        'especialidad_nombre' => $trabajador->maquina?->nombre,
                    ],
                ];
            }
        }

        // 6. Retornar vista con todas las variables
        return view('produccion.trabajadoresObra', [
            'trabajadoresServicios' => $trabajadoresServicios,
            'trabajadoresHpr' => $trabajadoresHpr,
            'resources' => $resources,
            'eventos' => $eventos,
            'obras' => $todasLasObras, // ← esta es la nueva variable con todas las obras
        ]);
    }
    public function eventosObra(Request $request)
    {
        $inicio = $request->query('start');
        $fin = $request->query('end');

        if (!$inicio || !$fin) {
            return response()->json(['error' => 'Faltan fechas'], 400);
        }

        $asignaciones = AsignacionTurno::with(['user.categoria', 'user.maquina', 'obra'])
            ->whereBetween('fecha', [$inicio, $fin])
            ->whereNotNull('obra_id')
            ->get();

        $eventos = $asignaciones->map(function ($asignacion) {
            return [
                'id' => 'turno-' . $asignacion->id,
                'title' => $asignacion->user?->nombre_completo ?? 'Desconocido',
                'start' => $asignacion->fecha . 'T06:00:00',
                'end' => $asignacion->fecha . 'T14:00:00',
                'resourceId' => $asignacion->obra_id,
                'extendedProps' => [
                    'user_id' => $asignacion->user_id,
                    'categoria_nombre' => $asignacion->user?->categoria?->nombre,
                    'especialidad_nombre' => $asignacion->user?->maquina?->nombre
                ]
            ];
        });

        return response()->json($eventos);
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
