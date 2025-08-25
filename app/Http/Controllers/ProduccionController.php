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
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use App\Models\Festivo;
use Throwable;


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
    private function obtenerColores(): array
    {
        $coloresMaquinas = [
            1 => '#2563EB', // azul intenso
            2 => '#059669', // verde intenso
            3 => '#D97706', // naranja intenso
        ];

        // 🎨 Colores para eventos (tonos pastel)
        $coloresEventos = [
            1 => ['bg' => '#93C5FD', 'border' => '#60A5FA'], // azul claro
            2 => ['bg' => '#6EE7B7', 'border' => '#34D399'], // verde claro
            3 => ['bg' => '#FDBA74', 'border' => '#F59E0B'], // naranja claro
        ];


        return [
            'maquinas' => $coloresMaquinas,
            'eventos'  => $coloresEventos,
        ];
    }

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

        $colores = $this->obtenerColores();
        $coloresMaquinas = $colores['maquinas'];
        $coloresEventos  = $colores['eventos'];

        // ✅ Pintar las máquinas
        $maquinas = Maquina::orderBy('id')
            ->get(['id', 'nombre', 'codigo', 'obra_id'])
            ->map(function ($maquina) use ($coloresMaquinas) {
                $color = $coloresMaquinas[$maquina->obra_id] ?? '#6c757d';
                return [
                    'id' => str_pad($maquina->id, 3, '0', STR_PAD_LEFT),
                    'title' => $maquina->codigo,
                    'extendedProps' => [
                        'backgroundColor' => $color,
                        'obra_id' => $maquina->obra_id,
                    ]
                ];
            });
        // 👇 Aquí añadimos el recurso especial
        $maquinas->push([
            'id' => 'SIN',
            'title' => 'N/A',
            'extendedProps' => [
                'backgroundColor' => '#9ca3af',
                'obra_id' => null,
            ]
        ]);

        $trabajadores = User::with([
            'asignacionesTurnos.turno:id,hora_entrada,hora_salida',
            'asignacionesTurnos.obra.cliente',
            'categoria',
            'maquina'
        ])
            ->where('rol', 'operario')
            ->whereHas('asignacionesTurnos', function ($q) {
                $q->whereHas('obra.cliente', function ($q) {
                    $q->whereRaw('LOWER(empresa) LIKE ?', ['%hierros paco reyes%']);
                });
            })
            ->get();


        $obraIds = $trabajadores
            ->flatMap(fn($t) => $t->asignacionesTurnos)
            ->filter(fn($a) => $a->estado === 'activo' && $a->obra_id)
            ->pluck('obra_id')
            ->unique()
            ->values();

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
                        $start = $asignacionTurno->fecha . 'T00:00:00';
                        $end   = $asignacionTurno->fecha . 'T06:00:00';
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
                    $resourceId = $maquinaId ? str_pad($maquinaId, 3, '0', STR_PAD_LEFT) : 'SIN';

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
                            $color = $coloresEventos[$obraId] ?? ['bg' => '#d1d5db', 'border' => '#9ca3af'];
                        }

                        $eventos[] = [
                            'id' => 'turno-' . $asignacionTurno->id,
                            'title' => $trabajador->nombre_completo,
                            'start' => $start,
                            'end' => $end,
                            'resourceId' => $resourceId,
                            'user_id' => $trabajador->id,
                            'backgroundColor' => $color['bg'], // tono más claro
                            'borderColor' => $color['border'],
                            'textColor' => '#000000',
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
        $resourceIds = $maquinas->pluck('id')->toArray();
        $festivosEventos = collect(Festivo::eventosCalendario())
            ->map(function ($e) use ($resourceIds) {
                $start = \Carbon\Carbon::parse($e['start'])->startOfDay();
                $end   = $start->copy()->addDay(); // end exclusivo

                return [
                    'id'              => $e['id'],
                    'title'           => $e['title'],
                    'start'           => $start->toIso8601String(),
                    'end'             => $end->toIso8601String(),
                    'allDay'          => true,
                    'resourceIds'     => $resourceIds,   // se ve en todas las máquinas
                    'backgroundColor' => '#ff0000',
                    'borderColor'     => '#b91c1c',
                    'textColor'       => '#ffffff',      // contraste alto
                    'editable'        => true,
                    'classNames'      => ['evento-festivo'],
                    'extendedProps' => array_merge($e['extendedProps'] ?? [], [
                        'es_festivo' => true,
                        'festivo_id' => $e['extendedProps']['festivo_id'] ?? null, // ✅ numérico
                        'entrada'    => null,
                        'salida'     => null,
                    ]),
                ];
            })
            ->toArray();

        $trabajadoresEventos = array_merge($eventos, $festivosEventos);


        return view('produccion.trabajadores', compact('maquinas', 'trabajadoresEventos', 'operariosTrabajando', 'estadoProduccionMaquinas', 'registroFichajes'));
    }

    public function actualizarPuesto(Request $request, $id)
    {
        // Validar datos
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
            'turno_id'   => 'nullable|exists:turnos,id',
        ]);

        // Buscar la máquina para obtener su obra_id
        $maquina = Maquina::findOrFail($request->maquina_id);

        // Buscar la asignación
        $asignacion = AsignacionTurno::findOrFail($id);

        // Actualizar datos incluyendo obra_id de la máquina
        $asignacion->update([
            'maquina_id' => $request->maquina_id,
            'turno_id'   => $request->turno_id,
            'obra_id'    => $maquina->obra_id, // 👈 se asigna automáticamente
        ]);

        $colores = $this->obtenerColores();
        $coloresEventos = $colores['eventos'];

        $color = $coloresEventos[$maquina->obra_id] ?? ['bg' => '#d1d5db', 'border' => '#9ca3af'];

        return response()->json([
            'message'       => 'Actualización exitosa',
            'color'         => $color['bg'],
            'borderColor'   => $color['border'],
            'nuevo_obra_id' => $maquina->obra_id,
        ]);
    }

    //---------------------------------------------------------- MAQUINAS
    public function maquinas()
    {
        // 🔹 1. MÁQUINAS DISPONIBLES
        $maquinas = Maquina::whereNotNull('tipo')
            ->orderBy('obra_id')   // primero ordena por obra
            ->orderBy('id')        // luego por id dentro de cada obra
            ->get();
        $coloresPorObra = [
            1 => '#1d4ed8', // azul
            2 => '#16a34a', // verde
            3 => '#b91c1c', // rojo
            4 => '#f59e0b', // amarillo
            // etc...
        ];

        $resources = $maquinas->map(function ($m) use ($coloresPorObra) {
            // asignar color según obra_id, si no hay usa un gris por defecto
            $color = $coloresPorObra[$m->obra_id] ?? '#6b7280'; // gris

            return [
                'id' => $m->id,
                'title' => match ($m->estado) {
                    'activa' => '🟢 ' . $m->nombre,
                    'averiada' => '🔴 ' . $m->nombre,
                    'mantenimiento' => '🛠️ ' . $m->nombre,
                    'pausa' => '⏸️ ' . $m->nombre,
                    default => ' ' . $m->nombre,
                },
                'eventBackgroundColor' => $color,
                'eventBorderColor' => $color,
                'eventTextColor' => '#ffffff', // texto blanco para contraste
                'obra_id' => $m->obra_id, // por si quieres usarlo en tooltips
            ];
        });

        // 🔹 2. ELEMENTOS ACTIVOS
        $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina'])
            ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando']))
            ->where(fn($q) => $q->whereNull('estado')->orWhere('estado', '<>', 'fabricado'))
            ->get();

        // Agrupamos primero por planilla + máquina
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

        // 🔹 Calcular colas iniciales de cada máquina
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

        // 🔹 Obtener ordenes desde la tabla orden_planillas
        $ordenes = OrdenPlanilla::orderBy('posicion')
            ->get()
            ->groupBy('maquina_id')
            ->map(fn($ordenesMaquina) => $ordenesMaquina->pluck('planilla_id')->all());



        // ✅ Generar eventos a partir de los datos calculados
        try {
            $planillasEventos = $this->generarEventosMaquinas($planillasAgrupadas, $ordenes, $colasMaquinas);
        } catch (\Throwable $e) {
            Log::error('❌ generarEventosMaquinas', ['msg' => $e->getMessage(), 'file' => $e->getFile() . ':' . $e->getLine()]);
            abort(500, $e->getMessage()); // te lo pinta en el navegador
        }


        // ✅ Resto de cálculos de gráficas y tabla
        $erroresPlanillas = [];
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

            $turnoDetectado = $turnosDefinidos->first(fn($t) => $horaEstimada >= $t->hora_entrada && $horaEstimada < $t->hora_salida);
            $nombreTurno = $turnoDetectado->nombre ?? 'mañana';

            $cargaPorMaquinaTurnoConFechas[$maquinaId][$nombreTurno][] = [
                'peso' => $e->peso,
                'estado' => $e->estado,
                'fecha' => $fechaEstimada->toDateString(),
            ];
        }

        $turnos = ['mañana', 'tarde', 'noche'];
        $cargaPorMaquinaTurno = [];
        foreach ($maquinas as $maquina) {
            $cargaPorMaquinaTurno[$maquina->id] = array_fill_keys($turnos, ['esperado' => 0, 'fabricado' => 0]);
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

        // Obtener todas las planillas en posición 1 que estén fabricando
        $planillasEnFabricacion = OrdenPlanilla::where('posicion', 1)
            ->whereHas('planilla', fn($q) => $q->where('estado', 'fabricando'))
            ->with('planilla')
            ->get();

        // Obtener la más antigua
        $fechaInicio = $planillasEnFabricacion
            ->filter(fn($op) => $op->planilla && $op->planilla->fecha_inicio)
            ->sortBy('planilla.fecha_inicio')
            ->first()
            ?->planilla->fecha_inicio ?? now();

        return view('produccion.maquinas', [
            'maquinas' => $maquinas,
            'planillasEventos' => $planillasEventos,
            'cargaPorMaquinaTurno' => $cargaPorMaquinaTurno,
            'erroresPlanillas' => $erroresPlanillas,
            'cargaPorMaquinaTurnoConFechas' => $cargaPorMaquinaTurnoConFechas,
            'resources' => $resources,
            'fechaInicioCalendario' => Carbon::parse($fechaInicio)->toDateString(),
        ]);
    }

    //---------------------------------------------------------- GENERAR EVENTOS MAQUINAS
    private function generarEventosMaquinas($planillasAgrupadas, $ordenes, $colasMaquinas)
    {


        $planillasEventos = collect();

        // 1) Festivos
        try {
            $festivoFechas = collect(Festivo::eventosCalendario())
                ->map(fn($e) => Carbon::parse($e['start'])->toDateString())
                ->unique()
                ->values();
        } catch (\Throwable $e) {
            Log::error('EVT B: Festivos no disponibles', ['err' => $e->getMessage()]);
            $festivoFechas = collect();
        }
        $festivosSet = array_flip($festivoFechas->all());


        // 2) Normaliza
        $planillasAgrupadasCol = collect($planillasAgrupadas);
        if ($ordenes instanceof Collection) $ordenes = $ordenes->all();

        // 3) Índice estable
        $agrupadasIndex = $planillasAgrupadasCol
            ->values()
            ->mapWithKeys(function ($data) {
                $planilla   = Arr::get($data, 'planilla');
                $planillaId = is_object($planilla) ? ($planilla->id ?? null) : Arr::get($planilla, 'id');
                $maquinaId  = Arr::get($data, 'maquina_id');
                if (!$planillaId || !$maquinaId) return [];
                return ["{$planillaId}-{$maquinaId}" => $data];
            });



        if ($agrupadasIndex->isEmpty()) {
            Log::warning('EVT E: índice vacío, devuelvo 0 eventos');
            return $planillasEventos->values();
        }

        // 4) Recorre máquinas
        $numMaquinas = is_array($ordenes) ? count($ordenes) : 0;


        foreach ($ordenes as $maquinaId => $planillasOrdenadas) {

            if ($planillasOrdenadas instanceof Collection) {
                $planillasOrdenadas = $planillasOrdenadas->values()->all();
            } elseif (!is_array($planillasOrdenadas)) {
                $planillasOrdenadas = (array) $planillasOrdenadas;
            }


            // Cola (Carbon)
            $inicioCola = $colasMaquinas[$maquinaId] ?? Carbon::now();
            if (!$inicioCola instanceof Carbon) {
                try {
                    $inicioCola = Carbon::parse($inicioCola);
                } catch (\Throwable $e) {
                    $inicioCola = Carbon::now();
                }
            }

            $primeraId = $planillasOrdenadas[0] ?? null;

            foreach ($planillasOrdenadas as $planillaId) {
                $clave = "{$planillaId}-{$maquinaId}";

                try {
                    if (!$agrupadasIndex->has($clave)) {

                        continue;
                    }

                    $data     = $agrupadasIndex->get($clave);
                    $planilla = Arr::get($data, 'planilla');
                    $grupo    = Arr::get($data, 'elementos');

                    if (!$planilla || !$planilla->fecha_estimada_entrega) {

                        continue;
                    }

                    $grupoCount       = $grupo instanceof Collection ? $grupo->count() : (is_countable($grupo) ? count($grupo) : 0);
                    $duracionSegundos = max($grupoCount * 20 * 60, 60);
                    $fechaInicio      = $inicioCola->copy();


                    $tramos = $this->generarTramosLaborales($fechaInicio, $duracionSegundos, $festivosSet);

                    if (empty($tramos)) {
                        Log::warning('EVT H1: sin tramos', ['planillaId' => $planillaId, 'maquinaId' => $maquinaId]);
                        continue;
                    }

                    $ultimoTramo  = end($tramos);
                    $fechaFinReal = $ultimoTramo['end'] instanceof Carbon
                        ? $ultimoTramo['end']->copy()
                        : Carbon::parse($ultimoTramo['end']);


                    // Progreso
                    $progreso = null;
                    if ($primeraId !== null && $primeraId === $planilla->id) {
                        if ($grupo instanceof Collection) {
                            $completados = $grupo->where('estado', 'fabricado')->count();
                            $total       = $grupo->count();
                        } else {
                            $completados = 0;
                            $total       = $grupoCount;
                        }
                        $progreso = $total > 0 ? round(($completados / $total) * 100) : 0;
                    }

                    $appTz = config('app.timezone') ?: 'Europe/Madrid';

                    /* ...dentro del bucle de planillas... */

                    // Fin real ya lo tienes:
                    $fechaFinReal = ($ultimoTramo['end'] instanceof Carbon ? $ultimoTramo['end'] : Carbon::parse($ultimoTramo['end']))
                        ->copy()->setTimezone($appTz);

                    // Fecha de entrega (ahora robusta)
                    $fechaEntrega = $this->parseFechaEntregaFlexible($planilla->fecha_estimada_entrega, $appTz);

                    // Semáforo (rojo si fin real supera entrega)
                    $backgroundColor = ($fechaEntrega && $fechaFinReal->gt($fechaEntrega)) ? '#ef4444' : '#22c55e';

                    // Eventos por tramo
                    foreach ($tramos as $i => $t) {
                        $tStart = $t['start'] instanceof Carbon ? $t['start'] : Carbon::parse($t['start']);
                        $tEnd   = $t['end']   instanceof Carbon ? $t['end']   : Carbon::parse($t['end']);

                        $planillasEventos->push([
                            'id'              => 'planilla-' . $planilla->id . '-seg' . ($i + 1),
                            'title'           => $planilla->codigo_limpio ?? ('Planilla #' . $planilla->id),
                            'codigo'          => $planilla->codigo_limpio ?? ('Planilla #' . $planilla->id),
                            'start'           => $tStart->toIso8601String(),
                            'end'             => $tEnd->toIso8601String(),
                            'resourceId'      => $maquinaId,
                            'backgroundColor' => $backgroundColor,
                            'extendedProps'   => [
                                'obra'            => optional($planilla->obra)->obra ?? '—',
                                'estado'          => $planilla->estado,
                                'duracion_horas'  => round($duracionSegundos / 3600, 2),
                                'progreso'        => $progreso,
                                'fecha_entrega'   => $fechaEntrega?->format('d/m/Y H:i') ?? '—',
                                'fin_programado'  => $fechaFinReal->format('d/m/Y H:i'),
                            ],
                        ]);
                    }

                    // Avanza cola
                    $inicioCola = $fechaFinReal->copy();
                } catch (\Throwable $e) {
                    Log::error('EVT X: excepción en bucle planilla', [
                        'clave' => $clave,
                        'err'   => $e->getMessage(),
                        'file'  => $e->getFile() . ':' . $e->getLine(),
                    ]);
                    // si quieres ver el error en pantalla mientras debug:
                    abort(500, "Error en generarEventosMaquinas (clave {$clave}): " . $e->getMessage());
                }
            }

            $colasMaquinas[$maquinaId] = $inicioCola;
        }


        return $planillasEventos->values();
    }


    /** ¿Es no laborable? (festivo o fin de semana) */
    private function esNoLaborable(Carbon $dia, array $festivosSet): bool
    {
        return isset($festivosSet[$dia->toDateString()]) || $dia->isWeekend();
    }

    /** 00:00 del siguiente día laborable a partir de $dt. */
    private function siguienteLaborableInicio(Carbon $dt, array $festivosSet): Carbon
    {
        $x = $dt->copy()->startOfDay();
        while ($this->esNoLaborable($x, $festivosSet)) {
            $x->addDay();
        }
        return $x;
    }

    /**
     * Divide una duración (segundos) en tramos [start,end) exclusivamente en días laborables.
     * Si el inicio cae en no laborable, arranca en el siguiente laborable 00:00.
     * Consume hasta 24h del día laborable y continúa en el siguiente laborable.
     */
    private function generarTramosLaborales(Carbon $inicio, int $durSeg, array $festivosSet): array
    {


        $tramos   = [];
        $restante = max(0, (int) $durSeg);

        // Si el inicio cae en no laborable -> al siguiente laborable 00:00
        if ($this->esNoLaborable($inicio, $festivosSet)) {
            $inicio = $this->siguienteLaborableInicio($inicio, $festivosSet);
        }

        $cursor  = $inicio->copy();
        $iter    = 0;
        $iterMax = 10000; // salvavidas

        while ($restante > 0) {
            if (++$iter > $iterMax) {
                Log::error('TRAMOS TX: iteraciones excedidas, posible bucle', [
                    'cursor'   => $cursor->toIso8601String(),
                    'restante' => $restante,
                ]);
                break;
            }

            // Saltar no laborables completos
            if ($this->esNoLaborable($cursor, $festivosSet)) {
                $cursor = $this->siguienteLaborableInicio($cursor, $festivosSet);

                continue;
            }

            // Límite exclusivo del día = 00:00 del día siguiente
            $limiteDia = $cursor->copy()->startOfDay()->addDay();

            // Capacidad del día (en segundos) usando timestamps (evita rarezas DST)
            $tsCursor   = (int) $cursor->getTimestamp();
            $tsLimite   = (int) $limiteDia->getTimestamp();
            $capacidad  = $tsLimite - $tsCursor; // >= 0

            if ($capacidad <= 0) {
                // avanzar primero al límite del día y luego al próximo laborable
                if ($tsCursor < $tsLimite) {
                    $cursor = $limiteDia->copy();
                }
                $cursor = $this->siguienteLaborableInicio($cursor, $festivosSet);
                Log::warning('TRAMOS T3: capacidad <= 0, salto a próximo laborable', ['cursor' => $cursor->toIso8601String()]);
                continue;
            }

            $consume = min($restante, $capacidad);

            // tramo
            $start = $cursor->copy();
            $end   = $cursor->copy()->addSeconds($consume);
            $tramos[] = ['start' => $start, 'end' => $end];

            // avanzar
            $restante -= $consume;
            $cursor    = $end;

            // si queda trabajo y estamos en/tras el límite del día → próximo laborable
            $tsCursor = (int) $cursor->getTimestamp();
            if ($restante > 0 && $tsCursor >= $tsLimite) {
                $cursor = $this->siguienteLaborableInicio($cursor, $festivosSet);
            }
        }


        return $tramos;
    }

    //---------------------------------------------------------- REORDENAR PLANILLAS
    public function reordenarPlanillas(Request $request)
    {
        $request->validate([
            'id'             => 'required|integer|exists:planillas,id',
            'maquina_id'     => 'required|integer|exists:maquinas,id',
            'nueva_posicion' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $planillaId = $request->id;
                $maquinaNueva = $request->maquina_id;
                $posNueva = $request->nueva_posicion;

                // 🧠 Buscar orden actual de esa planilla (en cualquier máquina)
                $ordenActual = OrdenPlanilla::lockForUpdate()
                    ->where('planilla_id', $planillaId)
                    ->first();

                $maquinaAnterior = $ordenActual?->maquina_id;
                $posAnterior = $ordenActual?->posicion;

                // 🚫 Verificación si se quiere poner en posición 1
                if ($posNueva == 1) {
                    $planillaPos1 = OrdenPlanilla::where('maquina_id', $maquinaNueva)
                        ->where('posicion', 1)
                        ->first()?->planilla;

                    if ($planillaPos1 && $planillaPos1->estado === 'fabricando') {
                        throw new \Exception('No se puede ocupar la posición 1: hay una planilla fabricando.');
                    }
                }

                // 🔄 Reasignar elementos a la nueva máquina
                Elemento::where('planilla_id', $planillaId)
                    ->update(['maquina_id' => $maquinaNueva]);

                // ✅ Crear o recuperar fila en nueva máquina
                $ordenNuevo = OrdenPlanilla::firstOrCreate(
                    ['planilla_id' => $planillaId, 'maquina_id' => $maquinaNueva],
                    ['posicion' => 9999] // temporal
                );

                $posActual = $ordenNuevo->posicion;

                // 🧹 Si cambió de máquina...
                if ($maquinaAnterior && $maquinaAnterior != $maquinaNueva) {
                    // ✅ Si la planilla estaba en posición 1 → reorganizar
                    if ($posAnterior == 1) {
                        OrdenPlanilla::where('maquina_id', $maquinaAnterior)
                            ->where('posicion', '>', 1)
                            ->decrement('posicion');
                    }

                    // ✅ Eliminar fila anterior
                    OrdenPlanilla::where('planilla_id', $planillaId)
                        ->where('maquina_id', $maquinaAnterior)
                        ->delete();
                }

                // 📌 Calcular límite superior
                $maxPos = OrdenPlanilla::where('maquina_id', $maquinaNueva)->max('posicion');
                $posNueva = min($posNueva, $maxPos);

                if ($posNueva == $posActual) return;

                // 🔁 Reacomodo
                if ($posNueva < $posActual) {
                    OrdenPlanilla::where('maquina_id', $maquinaNueva)
                        ->whereBetween('posicion', [$posNueva, $posActual - 1])
                        ->increment('posicion');
                } else {
                    OrdenPlanilla::where('maquina_id', $maquinaNueva)
                        ->whereBetween('posicion', [$posActual + 1, $posNueva])
                        ->decrement('posicion');
                }

                // ✅ Actualiza posición final
                $ordenNuevo->update(['posicion' => $posNueva]);
            });


            // 🔄 Recalcular eventos
            $maquinas = Maquina::whereNotNull('tipo')->orderBy('id')->get();

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

            $ordenes = OrdenPlanilla::orderBy('posicion')
                ->get()
                ->groupBy('maquina_id')
                ->map(fn($ordenesMaquina) => $ordenesMaquina->pluck('planilla_id')->all());

            $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina'])
                ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando']))
                ->where(fn($q) => $q->whereNull('estado')->orWhere('estado', '<>', 'fabricado'))
                ->get();

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

            $planillasEventos = $this->generarEventosMaquinas($planillasAgrupadas, $ordenes, $colasMaquinas);

            return response()->json([
                'success' => true,
                'message' => 'Planilla reordenada correctamente.',
                'eventos' => $planillasEventos,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al reordenar planilla: ' . $e->getMessage(), [
                'request' => $request->all(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
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
                        'foto' => $trabajador->ruta_imagen,
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
                        'foto' => $trabajador->ruta_imagen,
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
    //---------------------------------------------------------- EVENTOS OBRA
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

    private function parseFechaEntregaFlexible($valor, string $tz = null): ?Carbon
    {
        $tz = $tz ?: (config('app.timezone') ?: 'Europe/Madrid');

        try {
            if ($valor instanceof Carbon) return $valor->copy()->setTimezone($tz);
            if ($valor instanceof \DateTimeInterface) return Carbon::instance($valor)->setTimezone($tz);
            if (is_int($valor)) return Carbon::createFromTimestamp($valor, $tz);
            if (!is_string($valor) || trim($valor) === '') return null;

            $s = trim($valor);

            // intenta formatos comunes primero
            $formatos = [
                'd/m/Y H:i:s',
                'd/m/Y H:i',
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                'Y-m-d',
                'd/m/Y',
            ];

            foreach ($formatos as $fmt) {
                try {
                    $dt = Carbon::createFromFormat($fmt, $s, $tz);
                    // si el formato no incluye segundos, Carbon rellena a :00 (ok)
                    // si no incluye hora (solo fecha), lo dejamos tal cual (puedes usar ->endOfDay() si te interesa)
                    return $dt;
                } catch (\Throwable $e) {
                    // sigue probando
                }
            }

            // último intento: parse libre
            return Carbon::parse($s, $tz);
        } catch (\Throwable $e) {
            Log::warning('parseFechaEntregaFlexible falló', ['valor' => $valor, 'err' => $e->getMessage()]);
            return null;
        }
    }
}
