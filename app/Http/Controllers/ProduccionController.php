<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Models\Planilla;
use App\Models\Localizacion;
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
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;

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
        $maquinas = Maquina::orderBy('obra_id')
            ->orderBy('codigo')
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
        log::info('maquinas', $maquinas->toArray());
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
    public function maquinas(Request $request)
    {
        // 🔹 Filtros del formulario (opcionales)
        $fechaInicio = $request->input('fechaInicio'); // 'YYYY-MM-DD'
        $fechaFin    = $request->input('fechaFin');    // 'YYYY-MM-DD'
        $turnoFiltro = $request->input('turno');       // 'mañana' | 'tarde' | 'noche' | null

        // 🔹 1. MÁQUINAS DISPONIBLES
        $maquinas = Maquina::whereNotNull('tipo')
            ->where('tipo', '<>', 'grua')
            ->orderBy('obra_id')   // primero ordena por obra
            // luego por id dentro de cada obra
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

        // 🔹 2. ELEMENTOS ACTIVOS (para eventos del calendario)
        $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina', 'maquina_2', 'maquina_3'])
            ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando']))
            ->get();
        $maquinaReal = function ($e) {
            $tipo1 = optional($e->maquina)->tipo;      // según maquina_id
            $tipo2 = optional($e->maquina_2)->tipo;    // según maquina_id_2
            $tipo3 = optional($e->maquina_3)->tipo;    // según maquina_id_3

            // Ensambladora: planificamos en la maquina_id_2
            if ($tipo1 === 'ensambladora') {
                return $e->maquina_id_2;
            }

            // Soldadora: prioriza maquina_id_3 si existe
            if ($tipo1 === 'soldadora') {
                return $e->maquina_id_3 ?? $e->maquina_id;
            }

            // Dobladora manual en primaria
            if ($tipo1 === 'dobladora_manual') {
                return $e->maquina_id;
            }

            // Dobladora manual en secundaria (ej. etiquetas "pates" que derivamos a dobladora)
            if ($tipo2 === 'dobladora_manual') {
                return $e->maquina_id_2;
            }

            // Caso general
            return $e->maquina_id;
        };

        $planillasAgrupadas = $elementos
            ->groupBy(function ($e) use ($maquinaReal) {
                $maquinaId = $maquinaReal($e);
                return $e->planilla_id . '-' . $maquinaId;
            })
            ->map(function ($grupo) use ($maquinaReal) {
                $primero   = $grupo->first();
                $maquinaId = $maquinaReal($primero);

                return [
                    'planilla'   => $primero->planilla,
                    'elementos'  => $grupo,
                    'maquina_id' => $maquinaId,
                ];
            })
            ->filter(fn($data) => !is_null($data['maquina_id']));


        // 🔹 3. Calcular colas iniciales de cada máquina
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

        // 🔹 4. Obtener ordenes desde la tabla orden_planillas
        $ordenes = OrdenPlanilla::orderBy('posicion')
            ->get()
            ->groupBy('maquina_id')
            ->map(fn($ordenesMaquina) => $ordenesMaquina->pluck('planilla_id')->all());

        // 🔹 5. Generar eventos del calendario
        try {
            $planillasEventos = $this->generarEventosMaquinas($planillasAgrupadas, $ordenes, $colasMaquinas);
        } catch (\Throwable $e) {
            Log::error('❌ generarEventosMaquinas', ['msg' => $e->getMessage(), 'file' => $e->getFile() . ':' . $e->getLine()]);
            abort(500, $e->getMessage());
        }

        // 🔹 Planificado vs Real (con filtros opcionales que ya recoges por Request si quieres)
        [$cargaTurnoResumen, $planDetallado, $realDetallado] =
            $this->calcularPlanificadoYRealPorTurno($maquinas, $fechaInicio ?? null, $fechaFin ?? null, $turnoFiltro ?? null);


        // 🔹 7. Fecha de inicio del calendario (la más antigua en fabricación)
        $planillasEnFabricacion = OrdenPlanilla::where('posicion', 1)
            ->whereHas('planilla', fn($q) => $q->where('estado', 'fabricando'))
            ->with('planilla')
            ->get();

        $planillaMasAntigua = $planillasEnFabricacion
            ->filter(fn($op) => $op->planilla && $op->planilla->fecha_inicio)
            ->sortBy('planilla.fecha_inicio')
            ->first();

        $fechaInicioBruta = $planillaMasAntigua?->planilla?->fecha_inicio;

        $fechaCarbon = null;
        try {
            $fechaCarbon = is_string($fechaInicioBruta)
                ? Carbon::createFromFormat('d/m/Y H:i', $fechaInicioBruta)
                : $fechaInicioBruta;
        } catch (\Exception $e) {
            Log::error('❌ Error al convertir fecha_inicio', [
                'valor' => $fechaInicioBruta,
                'error' => $e->getMessage()
            ]);
        }

        $fechaInicioCalendario = $fechaCarbon?->toDateString() ?? now()->toDateString();
        $turnosLista = Turno::orderBy('hora_entrada')->pluck('nombre'); // ej.: mañana, tarde, noche

        $initialDate = $this->calcularInitialDate();

        return view('produccion.maquinas', [
            'maquinas'                         => $maquinas,
            'planillasEventos'                 => $planillasEventos,
            'cargaTurnoResumen' => $cargaTurnoResumen, // { maquina_id: { turno: {planificado,real} } }
            'planDetallado'     => $planDetallado,     // { maquina_id: { turno: [ {peso,fecha} ] } }
            'realDetallado'     => $realDetallado,     // { maquina_id: { turno: [ {peso,fecha} ] } }
            'resources'                        => $resources,
            'fechaInicioCalendario'            => $fechaInicioCalendario,
            'turnosLista'         => $turnosLista,
            // Devolvemos también los filtros para reflejarlos en la vista/JS
            'filtro_fecha_inicio'              => $fechaInicio,
            'filtro_fecha_fin'                 => $fechaFin,
            'filtro_turno'                     => $turnoFiltro,
            'initialDate'                     => $initialDate,
        ]);
    }

    private function calcularInitialDate(): string
    {
        $planillasPrimeraPos = OrdenPlanilla::with(['planilla:id,estado,fecha_inicio'])
            ->where('posicion', 1)
            ->get()
            ->pluck('planilla')
            ->filter();

        $fabricando = $planillasPrimeraPos->filter(
            fn($p) => strcasecmp((string)$p->estado, 'fabricando') === 0
        );

        if ($fabricando->isNotEmpty()) {
            $minFecha = $fabricando
                ->pluck('fecha_inicio')
                ->filter()
                ->min();

            if ($minFecha) {
                try {
                    // ⚡ Forzar formato europeo: "d/m/Y H:i"
                    return Carbon::createFromFormat('d/m/Y H:i', $minFecha)
                        ->toDateTimeString(); // "YYYY-MM-DD HH:MM:SS"
                } catch (\Exception $e) {
                    // Si falla, como fallback intentamos parsear normal (YYYY-MM-DD HH:MM:SS)
                    return Carbon::parse($minFecha)->toDateTimeString();
                }
            }
        }

        return now()->toDateString();
    }
    /**
     * Calcula por máquina y turno:
     *  - Planificado: por hora estimada de fin (inicio estimado o created_at + tiempo_fabricacion)
     *  - Real: por hora real de fin (fecha_fin/fecha_fin_real); si no hay, intenta fallbacks
     *
     * Devuelve:
     *  - planResumido[mq][turno] = {planificado, real}
     *  - planDetalladoConFechas[mq][turno] = [ {peso, fecha} ]  (planificado, para filtrar en cliente)
     *  - realDetalladoConFechas[mq][turno] = [ {peso, fecha} ]  (real, para filtrar en cliente)
     */
    private function calcularPlanificadoYRealPorTurno($maquinas, ?string $fechaInicio = null, ?string $fechaFin = null, ?string $turnoFiltro = null): array
    {
        $turnosDefinidos = Turno::all(); // nombre, hora_entrada, hora_salida (HH:MM)

        $resolverMaquinaElemento = function (Elemento $e) {
            $tipo = optional($e->maquina)->tipo;
            return match ($tipo) {
                'ensambladora' => $e->maquina_id_2,
                'soldadora'    => $e->maquina_id_3 ?? $e->maquina_id,
                default        => $e->maquina_id,
            };
        };

        $estaEnTurno = function (string $horaHHmm, $turno) {
            $ini = $turno->hora_entrada; // 'HH:MM'
            $fin = $turno->hora_salida;  // 'HH:MM'
            if ($fin >= $ini) {
                return ($horaHHmm >= $ini && $horaHHmm < $fin);
            }
            // nocturno (22:00–06:00)
            return ($horaHHmm >= $ini || $horaHHmm < $fin);
        };

        $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina'])
            ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando', 'completada']))
            ->get();

        // estructuras de salida
        $planDetalladoConFechas = []; // por máquina-turno (planificado)
        $realDetalladoConFechas = []; // por máquina-turno (real)

        foreach ($elementos as $e) {
            $planilla  = $e->planilla;
            $mqId      = $resolverMaquinaElemento($e);
            if (!$planilla || !$mqId) continue;

            $peso = (float) ($e->peso ?? 0);

            // -------- PLANIFICADO --------
            $inicioPlan = $planilla->fecha_inicio_estimada ?? $planilla->created_at;
            $secs = is_numeric($e->tiempo_fabricacion) ? (float) $e->tiempo_fabricacion : 0;
            $finPlanificado = \Carbon\Carbon::parse($inicioPlan)->addSeconds($secs);
            $horaPlan = $finPlanificado->format('H:i');

            // Primero ajustamos la fecha si es turno nocturno
            $turnoTmp = $turnosDefinidos->first(fn($t) => $estaEnTurno($horaPlan, $t));
            $fechaPlan = $finPlanificado->toDateString();

            if ($turnoTmp && $turnoTmp->hora_salida < $turnoTmp->hora_entrada && $horaPlan < $turnoTmp->hora_salida) {
                $fechaPlan = \Carbon\Carbon::parse($fechaPlan)->subDay()->toDateString();
            }

            $turnoPlan = optional($turnoTmp)->nombre ?? 'mañana';

            if ((!$fechaInicio || $fechaPlan >= $fechaInicio) && (!$fechaFin || $fechaPlan <= $fechaFin)) {
                if (!$turnoFiltro || $turnoFiltro === $turnoPlan) {
                    $planDetalladoConFechas[$mqId][$turnoPlan][] = ['peso' => $peso, 'fecha' => $fechaPlan];
                }
            }


            // -------- REAL --------
            $finReal = $e->fecha_fin
                ?? $e->fecha_fin_real
                ?? ($e->estado === 'fabricado' ? $e->updated_at : null);

            if ($finReal) {
                $finRealC = $finReal instanceof \Carbon\Carbon ? $finReal : \Carbon\Carbon::parse($finReal);
                $horaReal = $finRealC->format('H:i');
                $fechaReal = $finRealC->toDateString();

                $turnoTmp = $turnosDefinidos->first(fn($t) => $estaEnTurno($horaReal, $t));
                if ($turnoTmp && $turnoTmp->hora_salida < $turnoTmp->hora_entrada && $horaReal < $turnoTmp->hora_salida) {
                    $fechaReal = \Carbon\Carbon::parse($fechaReal)->subDay()->toDateString();
                }

                $turnoReal = optional($turnoTmp)->nombre ?? 'mañana';

                if ((!$fechaInicio || $fechaReal >= $fechaInicio) && (!$fechaFin || $fechaReal <= $fechaFin)) {
                    if (!$turnoFiltro || $turnoFiltro === $turnoReal) {
                        $realDetalladoConFechas[$mqId][$turnoReal][] = ['peso' => $peso, 'fecha' => $fechaReal];
                    }
                }
            }
        }

        // acumular por turno
        $turnos = ['mañana', 'tarde', 'noche'];
        $planResumido = [];
        foreach ($maquinas as $m) {
            $planResumido[$m->id] = [];
            foreach ($turnos as $t) {
                if ($turnoFiltro && $turnoFiltro !== $t) continue;

                $planificados = $planDetalladoConFechas[$m->id][$t] ?? [];
                $reales       = $realDetalladoConFechas[$m->id][$t] ?? [];

                $planResumido[$m->id][$t] = [
                    'planificado' => array_sum(array_column($planificados, 'peso')),
                    'real'        => array_sum(array_column($reales, 'peso')),
                ];
            }
        }

        return [$planResumido, $planDetalladoConFechas, $realDetalladoConFechas];
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
                    $duracionSegundos = max($grupoCount * 20 * 60, 3600); // mínimo 1 hora

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
                            $total       = $grupoCount; // ya calculado antes
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
                            'extendedProps' => [
                                'obra'           => optional($planilla->obra)->obra ?? '—',
                                'estado'         => $planilla->estado,
                                'duracion_horas' => round($duracionSegundos / 3600, 2),
                                'progreso'       => $progreso,
                                'fecha_entrega'  => $fechaEntrega?->format('d/m/Y H:i') ?? '—',
                                'fin_programado' => $fechaFinReal->format('d/m/Y H:i'),
                                'codigos_elementos' => $grupo->pluck('codigo')->values(),   // ya lo tienes
                                'elementos_id'      => $grupo->pluck('id')->values(),       // AÑADE ESTO
                            ],

                        ]);
                        // dd($planillasAgrupadas);
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
            'id'                => 'required|integer|exists:planillas,id',
            'maquina_id'        => 'required|integer|exists:maquinas,id',
            'maquina_origen_id' => 'required|integer|exists:maquinas,id',
            'nueva_posicion'    => 'required|integer|min:1',
            'forzar_movimiento' => 'sometimes|boolean',
            'elementos_id'      => 'sometimes|array',
            'elementos_id.*'    => 'integer|exists:elementos,id',
        ]);

        $planillaId   = (int) $request->id;
        $maqDestino   = (int) $request->maquina_id;
        $maqOrigen    = (int) $request->maquina_origen_id;
        $posNueva     = (int) $request->nueva_posicion;
        $forzar       = (bool) $request->boolean('forzar_movimiento');
        $subsetIds    = collect($request->input('elementos_id', []))->map(fn($v) => (int)$v);

        Log::info("➡️ ReordenarPlanillas iniciado", [
            'planilla_id'       => $planillaId,
            'maquina_destino'   => $maqDestino,
            'maquina_origen'    => $maqOrigen,
            'nueva_posicion'    => $posNueva,
            'forzar_movimiento' => $forzar,
            'elementos_id'      => $subsetIds->values(),
        ]);

        // 1) MISMA MÁQUINA → sólo reordenar, NADA de validar
        if ($maqOrigen === $maqDestino) {
            return $this->soloReordenarEnMismaMaquina($maqDestino, $planillaId, $posNueva);
        }

        // 2) Cambio de máquina → validar SÓLO el subset del evento
        if ($subsetIds->isEmpty()) {
            // Sin subset no sabemos qué querías mover: mejor pedirlo
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron elementos del evento (elementos_id).',
            ], 422);
        }

        $maquina = Maquina::findOrFail($maqDestino);
        $elementos = Elemento::whereIn('id', $subsetIds)->get();

        [$compatibles, $incompatibles, $diametrosIncompatibles] = $this->partirPorCompatibilidadPhp($elementos, $maquina);

        Log::info("🔍 Compatibilidad subset", [
            'compatibles'   => $compatibles->pluck('id')->values(),
            'incompatibles' => $incompatibles->pluck('id')->values(),
            'diametros'     => $diametrosIncompatibles->values(),
        ]);

        if ($incompatibles->isNotEmpty() && !$forzar) {
            Log::warning("⚠️ Mezcla detectada: requiere confirmación parcial");
            return response()->json([
                'success' => false,
                'requiresConfirmation' => true,
                'message' => 'Hay elementos con diámetros incompatibles. ¿Quieres mover sólo los compatibles?',
                'diametros' => $diametrosIncompatibles->values(),
                // devolvemos los que SÍ se pueden mover (como esperas en el front)
                'elementos' => $compatibles->pluck('id')->values(),
            ], 422);
        }

        try {
            DB::transaction(function () use ($planillaId, $maqOrigen, $maqDestino, $posNueva, $compatibles, $subsetIds, $forzar) {
                // 3) Movimiento (parcial si venía forzado)
                if ($compatibles->isNotEmpty()) {
                    Elemento::whereIn('id', $compatibles->pluck('id'))->update(['maquina_id' => $maqDestino]);
                    Log::info("➡️ Elementos actualizados a máquina destino", [
                        'destino' => $maqDestino,
                        'ids'     => $compatibles->pluck('id')->values(),
                    ]);
                } else {
                    // No hay ninguno compatible
                    throw new \Exception('No se pudo mover ningún elemento compatible a la máquina destino.');
                }

                // 4) Gestionar colas (sacar de origen, meter en destino si hace falta)
                // Si ya existe orden en destino, lo usamos; si no, lo creamos al final y luego reordenamos.
                $ordenDestino = OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $maqDestino)
                    ->first();

                if (!$ordenDestino) {
                    $maxPos = (int) (OrdenPlanilla::where('maquina_id', $maqDestino)->max('posicion') ?? 0);
                    $ordenDestino = OrdenPlanilla::create([
                        'planilla_id' => $planillaId,
                        'maquina_id'  => $maqDestino,
                        'posicion'    => $maxPos + 1,
                    ]);
                    Log::info("➕ Orden creado en máquina destino", ['posicion' => $maxPos + 1]);
                }

                // En el origen, si no quedan elementos (o si tu regla es sacarla siempre en cambio de máquina):
                // aquí puedes decidir si eliminar el orden de origen o no.
                // Si deseas mantener una sola cola por planilla, elimina del origen:
                $ordenOrigen = OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $maqOrigen)
                    ->first();
                if ($ordenOrigen) {
                    // comprueba si aún quedan elementos en origen (opcional)
                    $quedanEnOrigen = Elemento::where('planilla_id', $planillaId)
                        ->where('maquina_id', $maqOrigen)
                        ->exists();

                    if (!$quedanEnOrigen) {
                        $posAnterior = $ordenOrigen->posicion;
                        OrdenPlanilla::where('maquina_id', $maqOrigen)
                            ->where('posicion', '>', $posAnterior)
                            ->decrement('posicion');
                        $ordenOrigen->delete();
                        Log::info("🗑️ Orden eliminado de máquina origen y posiciones recompactadas", [
                            'maquina' => $maqOrigen,
                            'pos'     => $posAnterior,
                        ]);
                    }
                }

                // 5) Reordenar en destino a la posición deseada
                $this->reordenarPosicionEnMaquina($maqDestino, $planillaId, $posNueva);
            });

            return response()->json([
                'success'  => true,
                'message'  => 'Planilla reordenada correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al reordenar planilla: ' . $e->getMessage(), [
                'request' => $request->all(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 422);
        }
    }

    /** Reordena sólo en la misma máquina, sin validar nada */
    private function soloReordenarEnMismaMaquina(int $maquinaId, int $planillaId, int $posNueva)
    {
        try {
            DB::transaction(function () use ($maquinaId, $planillaId, $posNueva) {
                $ordenActual = OrdenPlanilla::lockForUpdate()
                    ->where('planilla_id', $planillaId)
                    ->where('maquina_id', $maquinaId)
                    ->first();

                if (!$ordenActual) {
                    // Si no hay orden en esa máquina, lo creamos al final
                    $maxPos = (int) (OrdenPlanilla::where('maquina_id', $maquinaId)->max('posicion') ?? 0);
                    $ordenActual = OrdenPlanilla::create([
                        'planilla_id' => $planillaId,
                        'maquina_id'  => $maquinaId,
                        'posicion'    => $maxPos + 1,
                    ]);
                }

                $posActual = (int) $ordenActual->posicion;
                if ($posNueva === $posActual) return;

                if ($posNueva < $posActual) {
                    OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->whereBetween('posicion', [$posNueva, $posActual - 1])
                        ->increment('posicion');
                } else {
                    OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->whereBetween('posicion', [$posActual + 1, $posNueva])
                        ->decrement('posicion');
                }

                $ordenActual->update(['posicion' => $posNueva]);
                Log::info("✅ Reordenado en misma máquina", [
                    'maquina'  => $maquinaId,
                    'posicion' => $posNueva
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Reordenado en la misma máquina.']);
        } catch (\Throwable $e) {
            Log::error('❌ Error reordenar en misma máquina: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 422);
        }
    }

    /** Reordena la posición de la planilla en una máquina dada */
    private function reordenarPosicionEnMaquina(int $maquinaId, int $planillaId, int $posNueva): void
    {
        $orden = OrdenPlanilla::lockForUpdate()
            ->where('maquina_id', $maquinaId)
            ->where('planilla_id', $planillaId)
            ->first();

        if (!$orden) {
            $maxPos = (int) (OrdenPlanilla::where('maquina_id', $maquinaId)->max('posicion') ?? 0);
            $orden = OrdenPlanilla::create([
                'maquina_id'  => $maquinaId,
                'planilla_id' => $planillaId,
                'posicion'    => $maxPos + 1,
            ]);
        }

        $posActual = (int) $orden->posicion;
        if ($posNueva === $posActual) return;

        if ($posNueva < $posActual) {
            OrdenPlanilla::where('maquina_id', $maquinaId)
                ->whereBetween('posicion', [$posNueva, $posActual - 1])
                ->increment('posicion');
        } else {
            OrdenPlanilla::where('maquina_id', $maquinaId)
                ->whereBetween('posicion', [$posActual + 1, $posNueva])
                ->decrement('posicion');
        }

        $orden->update(['posicion' => $posNueva]);
    }

    /**
     * Partir compatibilidad en PHP (numérico) para evitar problemas de casteo SQL.
     * Devuelve [compatibles, incompatibles, diametrosIncompatibles]
     */
    private function partirPorCompatibilidadPhp(\Illuminate\Support\Collection $elementos, Maquina $maquina): array
    {
        $min = is_null($maquina->diametro_min) ? null : (float)$maquina->diametro_min;
        $max = is_null($maquina->diametro_max) ? null : (float)$maquina->diametro_max;

        $compatibles = collect();
        $incompatibles = collect();
        $diametrosIncompatibles = collect();

        foreach ($elementos as $e) {
            $d = (float) $e->diametro; // asegura comparación numérica
            $okMin = is_null($min) || $d >= $min;
            $okMax = is_null($max) || $d <= $max;

            if ($okMin && $okMax) {
                $compatibles->push($e);
            } else {
                $incompatibles->push($e);
                $diametrosIncompatibles->push(number_format($d, 2));
            }
        }

        return [$compatibles, $incompatibles, $diametrosIncompatibles->unique()];
    }

    public function eventosPlanillas()
    {
        try {
            // 🔄 Cargar datos igual que en maquinas()
            $maquinas = Maquina::whereNotNull('tipo')
                ->orderBy('obra_id')
                ->orderBy('id')
                ->get();

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

            $colasMaquinas = [];
            foreach ($maquinas as $m) {
                $ultimaPlanillaFabricando = Planilla::whereHas('elementos', fn($q) => $q->where('maquina_id', $m->id))
                    ->where('estado', 'fabricando')
                    ->orderByDesc('fecha_inicio')
                    ->first();

                $colasMaquinas[$m->id] = optional($ultimaPlanillaFabricando)->fecha_inicio
                    ? toCarbon($ultimaPlanillaFabricando->fecha_inicio)
                    : now();
            }

            $ordenes = OrdenPlanilla::orderBy('posicion')
                ->get()
                ->groupBy('maquina_id')
                ->map(fn($ordenesMaquina) => $ordenesMaquina->pluck('planilla_id')->all());

            $eventos = $this->generarEventosMaquinas($planillasAgrupadas, $ordenes, $colasMaquinas);

            return response()->json($eventos);
        } catch (\Throwable $e) {
            Log::error('❌ Error al cargar eventos planillas', ['err' => $e->getMessage()]);
            return response()->json([], 500);
        }
    }

    //---------------------------------------------------------- PLANIFICACION TRABAJADORES OBRA
    public function trabajadoresObra()
    {
        $hprServicios = Empresa::where('nombre', 'HPR Servicios en Obra S.L.')->firstOrFail();
        $hpr = Empresa::where('nombre', 'Hierros Paco Reyes S.L.')->firstOrFail();

        $trabajadoresServicios = User::with(['asignacionesTurnos.turno', 'categoria', 'maquina'])
            ->where('empresa_id', $hprServicios->id)
            ->where('rol', 'operario')
            ->get()
            ->sortBy('nombre_completo')
            ->values(); // <- opcional para reindexar

        $trabajadoresHpr = User::with(['asignacionesTurnos.turno', 'categoria', 'maquina'])
            ->where('empresa_id', $hpr->id)
            ->where('rol', 'operario')
            ->get()
            ->sortBy('nombre_completo')
            ->values();


        $obrasActivas = Obra::where('tipo', 'montaje')->get();
        $todasLasObras = Obra::orderBy('obra')->get();

        $resources = $obrasActivas->map(fn($obra) => [
            'id'     => $obra->id,
            'title'  => $obra->obra,
            'codigo' => $obra->cod_obra,
        ]);
        $resources->prepend([
            'id'     => 'sin-obra',
            'title'  => 'Sin obra',
            'codigo' => '—'
        ]);

        $colorPorEstado = function (?string $estado) {
            $estado = $estado ? mb_strtolower($estado) : null;
            return match ($estado) {
                'vacaciones' => '#f472b6',
                'curso'      => '#ef4444',
                default      => null,
            };
        };

        $eventos = [];

        foreach ($trabajadoresServicios as $trabajador) {
            foreach ($trabajador->asignacionesTurnos as $asignacion) {
                $turno = $asignacion->turno;
                if (!$turno) continue;

                $horaEntrada = $turno->hora_entrada ?? '08:00:00';
                $horaSalida  = $turno->hora_salida ?? '16:00:00';

                $color = $colorPorEstado($asignacion->estado);

                $evento = [
                    'id'         => 'turno-' . $asignacion->id,
                    'title'      => $trabajador->nombre_completo,
                    'start'      => $asignacion->fecha . 'T' . $horaEntrada,
                    'end'        => $asignacion->fecha . 'T' . $horaSalida,
                    'resourceId' => $asignacion->obra_id ?? 'sin-obra',
                    'extendedProps' => [
                        'user_id'             => $trabajador->id,
                        'empresa'             => 'HPR Servicios',
                        'categoria_nombre'    => $trabajador->categoria?->nombre,
                        'especialidad_nombre' => $trabajador->maquina?->nombre,
                        'foto'                => $trabajador->ruta_imagen,
                        'estado'              => $asignacion->estado,
                    ],
                ];

                if ($color) {
                    $evento['backgroundColor'] = $color;
                    $evento['borderColor'] = $color;
                    if ($color === '#ef4444') $evento['textColor'] = '#ffffff';
                }

                $eventos[] = $evento;
            }
        }

        foreach ($trabajadoresHpr as $trabajador) {
            foreach ($trabajador->asignacionesTurnos as $asignacion) {
                $turno = $asignacion->turno;
                if (!$turno || !$asignacion->obra_id) continue; // ⚠️ Solo si tiene obra

                $horaEntrada = $turno->hora_entrada ?? '08:00:00';
                $horaSalida  = $turno->hora_salida ?? '16:00:00';

                $color = $colorPorEstado($asignacion->estado);

                $evento = [
                    'id'         => 'turno-' . $asignacion->id,
                    'title'      => $trabajador->nombre_completo,
                    'start'      => $asignacion->fecha . 'T' . $horaEntrada,
                    'end'        => $asignacion->fecha . 'T' . $horaSalida,
                    'resourceId' => $asignacion->obra_id,
                    'extendedProps' => [
                        'user_id'             => $trabajador->id,
                        'empresa'             => 'Hierros Paco Reyes',
                        'categoria_nombre'    => $trabajador->categoria?->nombre,
                        'especialidad_nombre' => $trabajador->maquina?->nombre,
                        'foto'                => $trabajador->ruta_imagen,
                        'estado'              => $asignacion->estado,
                    ],
                ];

                if ($color) {
                    $evento['backgroundColor'] = $color;
                    $evento['borderColor'] = $color;
                    if ($color === '#ef4444') $evento['textColor'] = '#ffffff';
                }

                $eventos[] = $evento;
            }
        }

        return view('produccion.trabajadoresObra', [
            'trabajadoresServicios' => $trabajadoresServicios,
            'trabajadoresHpr'       => $trabajadoresHpr,
            'resources'             => $resources,
            'eventos'               => $eventos,
            'obras'                 => $todasLasObras,
        ]);
    }


    //---------------------------------------------------------- EVENTOS OBRA
    public function eventosObra(Request $request)
    {
        $inicio = $request->query('start');
        $fin    = $request->query('end');

        if (!$inicio || !$fin) {
            return response()->json(['error' => 'Faltan fechas'], 400);
        }

        // ID de la empresa "HPR Servicios en Obra S.L."
        $empresaServiciosId = Empresa::where('nombre', 'HPR Servicios en Obra S.L.')->value('id');

        $asignaciones = AsignacionTurno::with(['user.categoria', 'user.maquina', 'obra'])
            ->whereBetween('fecha', [$inicio, $fin])
            ->get();

        $colorPorEstado = function (?string $estado) {
            $estado = $estado ? mb_strtolower($estado) : null;
            return match ($estado) {
                'vacaciones' => '#f472b6',
                'curso'      => '#ef4444',
                default      => null,
            };
        };

        $eventos = $asignaciones
            ->filter(function ($asignacion) use ($empresaServiciosId) {
                return $asignacion->obra_id || $asignacion->user?->empresa_id === $empresaServiciosId;
            })
            ->map(function ($asignacion) use ($colorPorEstado) {
                $color = $colorPorEstado($asignacion->estado);

                return [
                    'id'         => 'turno-' . $asignacion->id,
                    'title'      => $asignacion->user?->nombre_completo ?? 'Desconocido',
                    'start'      => $asignacion->fecha . 'T06:00:00',
                    'end'        => $asignacion->fecha . 'T14:00:00',
                    'resourceId' => $asignacion->obra_id ?? 'sin-obra',
                    'extendedProps' => [
                        'user_id' => $asignacion->user_id,
                        'estado'  => $asignacion->estado,
                    ],
                    'backgroundColor' => $color,
                    'borderColor'     => $color,
                    'textColor'       => $color === '#ef4444' ? '#ffffff' : null,
                ];
            })->values();

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

    public function verOrdenesPlanillas(Request $request)
    {
        $maquinas = Maquina::query()
            ->where('tipo', '!=', 'grua')
            ->orderBy('nombre')
            ->get();

        $localizacionMaquinas = Localizacion::query()
            ->get(['maquina_id', 'nave_id']);

        $ordenPlanillas = OrdenPlanilla::query()
            ->orderBy('posicion')
            ->get();

        $planillaIds = $ordenPlanillas->pluck('planilla_id')->unique();

        $planillas = Planilla::query()
            ->whereIn('id', $planillaIds)
            ->get();


        $elementos = Elemento::query()
            ->get();

        $obras = Obra::query()->get();

        return view('produccion.ordenesPlanillas', compact('maquinas', 'localizacionMaquinas', 'ordenPlanillas', 'planillas', 'elementos', 'obras'));
    }

    public function guardar(Request $request)
    {
        $data = $request->validate([
            'elementos_updates' => 'array',
            'elementos_updates.*.id' => 'required|integer',
            'elementos_updates.*.maquina_id' => 'required|integer',
            'elementos_updates.*.orden_planilla_id' => 'required|integer',

            'orden_planillas' => 'required|array',
            'orden_planillas.create' => 'array',
            'orden_planillas.create.*.id' => 'nullable|integer',
            'orden_planillas.create.*.maquina_id' => 'required|integer',
            'orden_planillas.create.*.planilla_id' => 'required|integer',
            'orden_planillas.create.*.posicion' => 'required|integer',

            'orden_planillas.update' => 'array',
            'orden_planillas.update.*.id' => 'required|integer',
            'orden_planillas.update.*.maquina_id' => 'required|integer',
            'orden_planillas.update.*.posicion' => 'required|integer',

            'orden_planillas.delete' => 'array',
            'orden_planillas.delete.*' => 'integer',
        ]);

        $respetarIdsCliente = true;

        // Datos comunes de auditoría
        $tx    = (string) Str::uuid();
        $actor = optional(auth()->user())->only(['id', 'name', 'email']) ?? ['id' => null, 'name' => 'guest', 'email' => null];
        $ip    = $request->ip();

        $audit = []; // aquí acumulamos y luego se escriben tras el commit

        DB::beginTransaction();

        try {
            $audit[] = [
                'event' => 'BEGIN',
                'tx'    => $tx,
                'actor' => $actor,
                'ip'    => $ip,
                'at'    => now()->toDateTimeString(),
                'payload_sizes' => [
                    'elementos_updates' => isset($data['elementos_updates']) ? count($data['elementos_updates']) : 0,
                    'op_create' => isset($data['orden_planillas']['create']) ? count($data['orden_planillas']['create']) : 0,
                    'op_update' => isset($data['orden_planillas']['update']) ? count($data['orden_planillas']['update']) : 0,
                    'op_delete' => isset($data['orden_planillas']['delete']) ? count($data['orden_planillas']['delete']) : 0,
                ],
            ];

            // 1) ELEMENTOS: UPDATE
            if (!empty($data['elementos_updates'])) {
                foreach ($data['elementos_updates'] as $e) {
                    // estado anterior
                    $before = DB::table('elementos')->where('id', $e['id'])->first();

                    DB::table('elementos')
                        ->where('id', $e['id'])
                        ->update([
                            'maquina_id' => $e['maquina_id'],
                            'orden_planilla_id' => $e['orden_planilla_id'],
                            'updated_at' => now(),
                        ]);

                    $audit[] = [
                        'event'  => 'elemento.update',
                        'tx'     => $tx,
                        'actor'  => $actor,
                        'ip'     => $ip,
                        'at'     => now()->toDateTimeString(),
                        'id'     => $e['id'],
                        'before' => $before ? (array) $before : null,
                        'after'  => [
                            'id' => $e['id'],
                            'maquina_id' => $e['maquina_id'],
                            'orden_planilla_id' => $e['orden_planilla_id'],
                        ],
                    ];
                }
            }

            $idMap = [];

            // 2) ORDEN_PLANILLAS: CREATE
            if (!empty($data['orden_planillas']['create'])) {
                foreach ($data['orden_planillas']['create'] as $op) {
                    if ($respetarIdsCliente && !empty($op['id'])) {
                        DB::table('orden_planillas')->insert([
                            'id' => $op['id'],
                            'maquina_id' => $op['maquina_id'],
                            'planilla_id' => $op['planilla_id'],
                            'posicion' => $op['posicion'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $audit[] = [
                            'event' => 'op.create',
                            'tx'    => $tx,
                            'actor' => $actor,
                            'ip'    => $ip,
                            'at'    => now()->toDateTimeString(),
                            'id'    => (int) $op['id'],
                            'data'  => $op,
                            'respetar_id_cliente' => true,
                        ];
                    } else {
                        $newId = DB::table('orden_planillas')->insertGetId([
                            'maquina_id' => $op['maquina_id'],
                            'planilla_id' => $op['planilla_id'],
                            'posicion' => $op['posicion'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        if (!empty($op['id'])) {
                            $idMap[(string)$op['id']] = $newId;
                        }

                        $audit[] = [
                            'event' => 'op.create',
                            'tx'    => $tx,
                            'actor' => $actor,
                            'ip'    => $ip,
                            'at'    => now()->toDateTimeString(),
                            'id'    => (int) $newId,
                            'data'  => $op,
                            'respetar_id_cliente' => false,
                            'temp_to_real' => !empty($op['id']) ? [(string)$op['id'] => $newId] : null,
                        ];
                    }
                }
            }

            // 2b) Reasignar elementos con IDs temporales si aplica
            if (!$respetarIdsCliente && !empty($idMap)) {
                foreach ($idMap as $tempId => $realId) {
                    $affected = DB::table('elementos')
                        ->where('orden_planilla_id', (int)$tempId)
                        ->update(['orden_planilla_id' => (int)$realId]);

                    $audit[] = [
                        'event' => 'elemento.rewire_op_id',
                        'tx'    => $tx,
                        'actor' => $actor,
                        'ip'    => $ip,
                        'at'    => now()->toDateTimeString(),
                        'temp_to_real' => [(int)$tempId => (int)$realId],
                        'affected' => $affected,
                    ];
                }
            }

            // 3) ORDEN_PLANILLAS: UPDATE
            if (!empty($data['orden_planillas']['update'])) {
                foreach ($data['orden_planillas']['update'] as $op) {
                    $before = DB::table('orden_planillas')->where('id', $op['id'])->first();

                    DB::table('orden_planillas')
                        ->where('id', $op['id'])
                        ->update([
                            'maquina_id' => $op['maquina_id'],
                            'posicion' => $op['posicion'],
                            'updated_at' => now(),
                        ]);

                    $audit[] = [
                        'event'  => 'op.update',
                        'tx'     => $tx,
                        'actor'  => $actor,
                        'ip'     => $ip,
                        'at'     => now()->toDateTimeString(),
                        'id'     => $op['id'],
                        'before' => $before ? (array) $before : null,
                        'after'  => [
                            'id' => $op['id'],
                            'maquina_id' => $op['maquina_id'],
                            'posicion' => $op['posicion'],
                        ],
                    ];
                }
            }

            // 4) ORDEN_PLANILLAS: DELETE
            if (!empty($data['orden_planillas']['delete'])) {
                $idsEliminar = $data['orden_planillas']['delete'];

                // Capturamos los registros antes de eliminar
                $beforeRows = DB::table('orden_planillas')
                    ->whereIn('id', $idsEliminar)
                    ->get()
                    ->map(fn($r) => (array) $r)
                    ->all();

                DB::table('orden_planillas')->whereIn('id', $idsEliminar)->delete();

                $audit[] = [
                    'event'  => 'op.delete',
                    'tx'     => $tx,
                    'actor'  => $actor,
                    'ip'     => $ip,
                    'at'     => now()->toDateTimeString(),
                    'ids'    => $idsEliminar,
                    'before' => $beforeRows, // para auditoría
                ];
            }

            DB::commit();

            // Solo si hay commit, escribimos en el canal "ordenamiento"
            DB::afterCommit(function () use ($audit, $tx) {
                Log::channel('ordenamiento')->info('TX COMMIT ' . $tx, ['size' => count($audit)]);
                foreach ($audit as $entry) {
                    Log::channel('ordenamiento')->info($entry['event'], $entry);
                }
            });

            return response()->json([
                'success' => true,
                'orden_planillas_id_map' => $respetarIdsCliente ? (object)[] : $idMap,
                'message' => 'Movimiento(s) registrado(s) correctamente.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            // Registramos el fallo (en el canal también) con el mismo tx
            Log::channel('ordenamiento')->error('TX ROLLBACK ' . $tx, [
                'actor' => $actor,
                'ip'    => $ip,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
