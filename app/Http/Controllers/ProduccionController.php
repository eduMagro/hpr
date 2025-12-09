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
use App\Models\EventoFicticioObra;
use App\Models\TrabajadorFicticio;
use App\Models\SnapshotProduccion;
use App\Models\Etiqueta;
use Throwable;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use App\Services\SubEtiquetaService;

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
        $maquinas = Maquina::orderByRaw('CASE WHEN obra_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('obra_id')
            ->orderBy('tipo')
            ->get(['id', 'nombre', 'codigo', 'obra_id', 'tipo'])
            ->map(function ($maquina, $index) use ($coloresMaquinas) {
                $color = $coloresMaquinas[$maquina->obra_id] ?? '#6c757d';
                return [
                    'id' => str_pad($maquina->id, 3, '0', STR_PAD_LEFT),
                    'title' => $maquina->codigo,
                    'orden' => $index,
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
            'orden' => 9999,
            'extendedProps' => [
                'backgroundColor' => '#9ca3af',
                'obra_id' => null,
            ]
        ]);
        log::info('maquinas', $maquinas->toArray());
        $trabajadores = User::with([
            'asignacionesTurnos.turno:id,hora_inicio,hora_fin',
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

                    $horaEntrada = $turno?->hora_inicio ?? '08:00:00';
                    $horaSalida = $turno?->hora_fin ?? '16:00:00';

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

    /**
     * 🔄 Endpoint para obtener actualizaciones en tiempo real del calendario
     */
    public function obtenerActualizaciones(Request $request)
    {
        $ultimaActualizacion = $request->input('timestamp');

        // Convertir timestamp a Carbon
        try {
            $desde = $ultimaActualizacion ? Carbon::parse($ultimaActualizacion) : Carbon::now()->subMinutes(1);
        } catch (\Exception $e) {
            $desde = Carbon::now()->subMinutes(1);
        }

        // Obtener planillas que cambiaron desde el último timestamp
        $planillasActualizadas = Planilla::where('updated_at', '>', $desde)
            ->whereIn('estado', ['pendiente', 'fabricando', 'completada'])
            ->with(['elementos' => function ($q) {
                $q->select('id', 'planilla_id', 'estado', 'maquina_id', 'tiempo_fabricacion');
            }, 'obra'])
            ->get();

        // Obtener festivos para cálculo de fin programado
        $festivosSet = $this->obtenerFestivosSet();

        // Obtener todas las órdenes de planillas para calcular posición en cola
        $ordenesEnCola = DB::table('orden_planillas')
            ->orderBy('maquina_id')
            ->orderBy('posicion')
            ->get()
            ->groupBy('maquina_id');

        $actualizaciones = [];

        foreach ($planillasActualizadas as $planilla) {
            // Agrupar elementos por máquina
            $elementosPorMaquina = $planilla->elementos->groupBy('maquina_id');

            foreach ($elementosPorMaquina as $maquinaId => $elementos) {
                $completados = $elementos->where('estado', 'fabricado')->count();
                $total = $elementos->count();
                $progreso = $total > 0 ? round(($completados / $total) * 100) : 0;

                // 🕐 Calcular fin programado real
                $finProgramado = $this->calcularFinProgramado($planilla->id, $maquinaId, $ordenesEnCola, $festivosSet);

                // 📅 Parsear fecha de entrega
                $fechaEntrega = $this->parseFechaEntregaFlexible($planilla->fecha_estimada_entrega);

                // ⚠️ Determinar si tiene retraso
                $tieneRetraso = false;
                if ($fechaEntrega && $finProgramado) {
                    $tieneRetraso = $finProgramado->gt($fechaEntrega);
                }

                Log::info('📊 POLLING: Calculando actualización', [
                    'planilla_id' => $planilla->id,
                    'maquina_id' => $maquinaId,
                    'revisada' => $planilla->revisada,
                    'fecha_entrega' => $fechaEntrega ? $fechaEntrega->format('d/m/Y H:i') : null,
                    'fin_programado' => $finProgramado ? $finProgramado->format('d/m/Y H:i') : null,
                    'tiene_retraso' => $tieneRetraso,
                ]);

                $actualizaciones[] = [
                    'planilla_id' => $planilla->id,
                    'maquina_id' => $maquinaId,
                    'codigo' => $planilla->codigo_limpio ?? $planilla->codigo,
                    'estado' => $planilla->estado,
                    'progreso' => $progreso,
                    'revisada' => (bool)$planilla->revisada,
                    'completado' => $completados === $total && $total > 0,
                    'elementos_completados' => $completados,
                    'elementos_total' => $total,
                    'obra' => optional($planilla->obra)->obra ?? '—',
                    'fecha_entrega' => $fechaEntrega ? $fechaEntrega->format('d/m/Y H:i') : null,
                    'fin_programado' => $finProgramado ? $finProgramado->format('d/m/Y H:i') : null,
                    'tiene_retraso' => $tieneRetraso,
                ];
            }
        }

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'actualizaciones' => $actualizaciones,
            'total' => count($actualizaciones),
            'success' => true,
        ]);
    }

    //---------------------------------------------------------- MAQUINAS
    public function maquinas(Request $request)
    {
        // 🔹 Filtros del formulario (opcionales)
        $fechaInicio = $request->input('fechaInicio'); // 'YYYY-MM-DD'
        $fechaFin    = $request->input('fechaFin');    // 'YYYY-MM-DD'
        $turnoFiltro = $request->input('turno');       // 'mañana' | 'tarde' | 'noche' | null

        // 🔹 1. MÁQUINAS DISPONIBLES - TODAS excepto grúas
        $maquinas = Maquina::where('tipo', '<>', 'grua')
            ->orderByRaw('CASE WHEN obra_id IS NULL THEN 1 ELSE 0 END')  // NULL al final
            ->orderBy('obra_id')   // primero ordena por obra
            ->orderBy('tipo')      // luego por tipo dentro de cada obra
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
        })->values();

        // 🔹 2. ELEMENTOS ACTIVOS (OPTIMIZADO: una sola consulta para calendario y cálculos)
        $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina', 'maquina_2', 'maquina_3', 'etiquetaRelacion'])
            ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando', 'completada']))
            ->get();

        // Filtrar solo pendiente/fabricando para el calendario
        $elementosCalendario = $elementos->filter(fn($e) => in_array($e->planilla?->estado, ['pendiente', 'fabricando']));
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

        $planillasAgrupadas = $elementosCalendario
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


        // 🔹 3. Calcular colas iniciales de cada máquina (OPTIMIZADO: una sola query)
        $maquinaIds = $maquinas->pluck('id')->all();
        $ultimasPlanillasPorMaquina = DB::table('planillas')
            ->join('elementos', 'planillas.id', '=', 'elementos.planilla_id')
            ->whereIn('elementos.maquina_id', $maquinaIds)
            ->where('planillas.estado', 'fabricando')
            ->whereNull('planillas.deleted_at')
            ->whereNull('elementos.deleted_at')
            ->select('elementos.maquina_id', DB::raw('MAX(planillas.fecha_inicio) as fecha_inicio'))
            ->groupBy('elementos.maquina_id')
            ->pluck('fecha_inicio', 'maquina_id');

        $colasMaquinas = [];
        $maxFecha = Carbon::now()->addYear();
        foreach ($maquinas as $m) {
            $fechaInicioRaw = $ultimasPlanillasPorMaquina[$m->id] ?? null;
            $fechaInicioCola = $fechaInicioRaw ? toCarbon($fechaInicioRaw) : Carbon::now();

            if ($fechaInicioCola->gt($maxFecha)) {
                $fechaInicioCola = Carbon::now();
            }

            $colasMaquinas[$m->id] = $fechaInicioCola;
        }

        // 🔹 4. Obtener ordenes desde la tabla orden_planillas (SIN reordenar)
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

        // 🔹 Planificado vs Real (OPTIMIZADO: reutiliza elementos ya cargados)
        [$cargaTurnoResumen, $planDetallado, $realDetallado] =
            $this->calcularPlanificadoYRealPorTurno($maquinas, $fechaInicio ?? null, $fechaFin ?? null, $turnoFiltro ?? null, $elementos);


        // 🔹 7. Fecha de inicio del calendario (OPTIMIZADO: reutiliza calcularInitialDate)
        $initialDate = $this->calcularInitialDate();
        $fechaInicioCalendario = Carbon::parse($initialDate)->toDateString();
        $turnosLista = Turno::orderBy('orden')->orderBy('hora_inicio')->get();

        // 🆕 Configuración del calendario (horas visibles y días a mostrar)
        $horasCalculadas = 168; // Mínimo 7 días = 168 horas
        $diasCalculados = 7;

        try {
            // Calcular fecha máxima basándose en el fin programado más alto de los eventos
            $fechaMaxima = null;
            foreach ($planillasEventos as $evento) {
                if (!empty($evento['end'])) {
                    try {
                        $endCarbon = Carbon::parse($evento['end']);
                        if (!$fechaMaxima || $endCarbon->gt($fechaMaxima)) {
                            $fechaMaxima = $endCarbon;
                        }
                    } catch (\Exception $e) {
                        // Ignorar eventos con fechas inválidas
                    }
                }
            }

            // Calcular horas desde la fecha inicial hasta la fecha máxima
            if ($fechaMaxima && $initialDate) {
                $fechaInicial = Carbon::parse($initialDate)->startOfDay();
                $horasCalculadas = max(168, $fechaInicial->diffInHours($fechaMaxima) + 24); // +24 horas de margen
                $diasCalculados = max(7, (int) ceil($horasCalculadas / 24));
            }
        } catch (\Exception $e) {
            Log::error('❌ Error calculando fechaMaximaCalendario', ['error' => $e->getMessage()]);
        }

        $fechaMaximaCalendario = [
            'horas' => (int) $horasCalculadas,
            'dias' => (int) $diasCalculados,
        ];

        // 🆕 Preparar datos de máquinas para JavaScript
        $maquinasParaJS = $maquinas->map(function ($m) {
            return [
                'id' => $m->id,
                'nombre' => $m->nombre,
                'codigo' => $m->codigo,
                'obra_id' => $m->obra_id,
                'diametro_min' => $m->diametro_min,
                'diametro_max' => $m->diametro_max,
            ];
        });

        return view('produccion.maquinas', [
            'maquinas'                         => $maquinas,
            'maquinasParaJS' => $maquinasParaJS,
            'planillasEventos'                 => $planillasEventos,
            'cargaTurnoResumen' => $cargaTurnoResumen, // { maquina_id: { turno: {planificado,real} } }
            'planDetallado'     => $planDetallado,     // { maquina_id: { turno: [ {peso,fecha} ] } }
            'realDetallado'     => $realDetallado,     // { maquina_id: { turno: [ {peso,fecha} ] } }
            'resources'                        => $resources,
            'fechaInicioCalendario'            => $fechaInicioCalendario,
            'fechaMaximaCalendario'            => $fechaMaximaCalendario,
            'turnosLista'         => $turnosLista,
            // Devolvemos también los filtros para reflejarlos en la vista/JS
            'filtro_fecha_inicio'              => $fechaInicio,
            'filtro_fecha_fin'                 => $fechaFin,
            'filtro_turno'                     => $turnoFiltro,
            'initialDate'                     => $initialDate,
        ]);
    }

    /**
     * Obtener recursos (máquinas) para el calendario de forma dinámica
     */
    public function obtenerRecursos(Request $request)
    {
        try {
            $maquinas = Maquina::where('tipo', '<>', 'grua')
                ->orderByRaw('CASE WHEN obra_id IS NULL THEN 1 ELSE 0 END')
                ->orderBy('obra_id')
                ->orderBy('tipo')
                ->get();

            $coloresPorObra = [
                1 => '#1d4ed8',
                2 => '#16a34a',
                3 => '#b91c1c',
                4 => '#f59e0b',
            ];

            $resources = $maquinas->map(function ($m) use ($coloresPorObra) {
                $color = $coloresPorObra[$m->obra_id] ?? '#6b7280';

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
                    'eventTextColor' => '#ffffff',
                    'obra_id' => $m->obra_id,
                ];
            })->values()->all();

            Log::info('✅ obtenerRecursos: devolviendo ' . count($resources) . ' máquinas');

            return response()->json($resources);
        } catch (\Throwable $e) {
            Log::error('❌ obtenerRecursos error', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine()
            ]);

            return response()->json([]);
        }
    }

    /**
     * Obtener eventos (planillas) para el calendario de forma dinámica
     */
    public function obtenerEventos(Request $request)
    {
        // Este método devuelve los mismos eventos que el método maquinas()
        // pero en formato JSON para actualización dinámica

        // Reutilizar exactamente la misma lógica que maquinas()

        // 1. Obtener máquinas (necesarias para las colas)
        $maquinas = Maquina::where('tipo', '<>', 'grua')
            ->orderByRaw('CASE WHEN obra_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('obra_id')
            ->orderBy('tipo')
            ->get();

        // 2. Elementos activos
        $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina', 'maquina_2', 'maquina_3'])
            ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando']))
            ->get();

        $maquinaReal = function ($e) {
            $tipo1 = optional($e->maquina)->tipo;
            $tipo2 = optional($e->maquina_2)->tipo;
            $tipo3 = optional($e->maquina_3)->tipo;

            if ($tipo1 === 'ensambladora') return $e->maquina_id_2;
            if ($tipo1 === 'soldadora') return $e->maquina_id_3 ?? $e->maquina_id;
            if ($tipo1 === 'dobladora_manual') return $e->maquina_id;
            if ($tipo2 === 'dobladora_manual') return $e->maquina_id_2;

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

        // 3. Calcular colas iniciales de cada máquina
        $colasMaquinas = [];
        foreach ($maquinas as $m) {
            $ultimaPlanillaFabricando = Planilla::whereHas('elementos', fn($q) => $q->where('maquina_id', $m->id))
                ->where('estado', 'fabricando')
                ->orderByDesc('fecha_inicio')
                ->first();

            $fechaInicioCola = optional($ultimaPlanillaFabricando)->fecha_inicio
                ? toCarbon($ultimaPlanillaFabricando->fecha_inicio)
                : Carbon::now();

            // Validar que la fecha no esté demasiado lejos en el futuro
            $maxFecha = Carbon::now()->addYear();
            if ($fechaInicioCola->gt($maxFecha)) {
                Log::warning('COLA MAQUINA: fecha_inicio demasiado lejana, usando now()', [
                    'maquina_id' => $m->id,
                    'fecha_inicio' => $fechaInicioCola->toIso8601String(),
                    'planilla_id' => optional($ultimaPlanillaFabricando)->id,
                ]);
                $fechaInicioCola = Carbon::now();
            }

            $colasMaquinas[$m->id] = $fechaInicioCola;
        }

        // 4. Obtener ordenes desde la tabla orden_planillas
        $ordenes = OrdenPlanilla::orderBy('posicion')
            ->get()
            ->groupBy('maquina_id')
            ->map(fn($ordenesMaquina) => $ordenesMaquina->pluck('planilla_id')->all());

        // 5. Generar eventos usando el mismo método que maquinas()
        try {
            $planillasEventos = $this->generarEventosMaquinas($planillasAgrupadas, $ordenes, $colasMaquinas);

            // Convertir Collection a array para asegurar formato JSON correcto
            $eventosArray = $planillasEventos->values()->all();

            Log::info('✅ obtenerEventos: devolviendo ' . count($eventosArray) . ' eventos');

            return response()->json($eventosArray);
        } catch (\Throwable $e) {
            Log::error('❌ obtenerEventos::generarEventosMaquinas', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Devolver array vacío en caso de error para que el calendario no falle
            return response()->json([]);
        }
    }

    private function calcularInitialDate(): string
    {
        try {
            $planillasPrimeraPos = OrdenPlanilla::with(['planilla:id,estado,fecha_inicio'])
                ->where('posicion', 1)
                ->get()
                ->pluck('planilla')
                ->filter();

            $fabricando = $planillasPrimeraPos->filter(
                fn($p) => $p && strcasecmp((string)$p->estado, 'fabricando') === 0
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
        } catch (\Exception $e) {
            Log::error('❌ Error en calcularInitialDate', [
                'error' => $e->getMessage()
            ]);
        }

        // Fallback: fecha actual en formato ISO
        return now()->format('Y-m-d H:i:s');
    }
    /**
     * 🔧 Obtiene la fecha real de finalización según el tipo de máquina
     * Busca en los campos de etiqueta correspondientes
     */
    private function obtenerFechaRealElemento(Elemento $e): ?\Carbon\Carbon
    {
        $etiqueta = $e->etiquetaRelacion;
        if (!$etiqueta) {
            // Fallback al updated_at si está fabricado
            return $e->estado === 'fabricado' ? \Carbon\Carbon::parse($e->updated_at) : null;
        }

        $tipoMaquina = optional($e->maquina)->tipo;

        // Según el tipo de máquina, usar los campos correspondientes
        $fechaFin = match ($tipoMaquina) {
            'ensambladora' => $etiqueta->fecha_finalizacion_ensamblado,
            'soldadora'    => $etiqueta->fecha_finalizacion_soldadura,
            default        => $etiqueta->fecha_finalizacion, // dobladora/cortadora
        };

        if ($fechaFin) {
            return $fechaFin instanceof \Carbon\Carbon ? $fechaFin : \Carbon\Carbon::parse($fechaFin);
        }

        // Fallback: si está fabricado pero no tiene fecha, usar updated_at
        return $e->estado === 'fabricado' ? \Carbon\Carbon::parse($e->updated_at) : null;
    }

    /**
     * Calcula por máquina y turno:
     *  - Planificado: por hora estimada de fin (inicio estimado o created_at + tiempo_fabricacion)
     *  - Real: por hora real de fin usando campos de etiquetas según tipo de máquina
     *
     * Devuelve:
     *  - planResumido[mq][turno] = {planificado, real}
     *  - planDetalladoConFechas[mq][turno] = [ {peso, fecha} ]  (planificado, para filtrar en cliente)
     *  - realDetalladoConFechas[mq][turno] = [ {peso, fecha} ]  (real, para filtrar en cliente)
     */
    private function calcularPlanificadoYRealPorTurno($maquinas, ?string $fechaInicio = null, ?string $fechaFin = null, ?string $turnoFiltro = null, $elementosPreCargados = null): array
    {
        static $turnosDefinidos = null;
        if ($turnosDefinidos === null) {
            $turnosDefinidos = Turno::all(); // nombre, hora_inicio, hora_fin (HH:MM)
        }

        $resolverMaquinaElemento = function (Elemento $e) {
            $tipo = optional($e->maquina)->tipo;
            return match ($tipo) {
                'ensambladora' => $e->maquina_id_2,
                'soldadora'    => $e->maquina_id_3 ?? $e->maquina_id,
                default        => $e->maquina_id,
            };
        };

        $estaEnTurno = function (string $horaHHmm, $turno) {
            $ini = $turno->hora_inicio; // 'HH:MM'
            $fin = $turno->hora_fin;  // 'HH:MM'
            if ($fin >= $ini) {
                return ($horaHHmm >= $ini && $horaHHmm < $fin);
            }
            // nocturno (22:00–06:00)
            return ($horaHHmm >= $ini || $horaHHmm < $fin);
        };

        // OPTIMIZADO: Reutilizar elementos pre-cargados si están disponibles
        $elementos = $elementosPreCargados ?? Elemento::with(['planilla', 'planilla.obra', 'maquina', 'maquina_2', 'maquina_3', 'etiquetaRelacion'])
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

            if ($turnoTmp && $turnoTmp->hora_fin < $turnoTmp->hora_inicio && $horaPlan < $turnoTmp->hora_fin) {
                $fechaPlan = \Carbon\Carbon::parse($fechaPlan)->subDay()->toDateString();
            }

            $turnoPlan = optional($turnoTmp)->nombre ?? 'mañana';

            if ((!$fechaInicio || $fechaPlan >= $fechaInicio) && (!$fechaFin || $fechaPlan <= $fechaFin)) {
                if (!$turnoFiltro || $turnoFiltro === $turnoPlan) {
                    $planDetalladoConFechas[$mqId][$turnoPlan][] = ['peso' => $peso, 'fecha' => $fechaPlan];
                }
            }


            // -------- REAL --------
            // 🔧 Usar la nueva función que obtiene fecha real desde etiquetas
            $finRealC = $this->obtenerFechaRealElemento($e);

            if ($finRealC) {
                $horaReal = $finRealC->format('H:i');
                $fechaReal = $finRealC->toDateString();

                $turnoTmp = $turnosDefinidos->first(fn($t) => $estaEnTurno($horaReal, $t));
                if ($turnoTmp && $turnoTmp->hora_fin < $turnoTmp->hora_inicio && $horaReal < $turnoTmp->hora_fin) {
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

    /** Obtener festivos como array asociativo [fecha => true] */
    private function obtenerFestivosSet(): array
    {
        try {
            $festivoFechas = collect(Festivo::eventosCalendario())
                ->map(fn($e) => Carbon::parse($e['start'])->toDateString())
                ->unique()
                ->values();
        } catch (\Throwable $e) {
            Log::error('Festivos no disponibles', ['err' => $e->getMessage()]);
            $festivoFechas = collect();
        }

        return array_flip($festivoFechas->all());
    }

    /**
     * Calcular fin programado de una planilla en una máquina específica
     * Considera la cola de trabajo y usa tramos laborales
     */
    private function calcularFinProgramado($planillaId, $maquinaId, $ordenesEnCola, $festivosSet)
    {
        // Obtener órdenes de esta máquina
        $ordenesMaquina = $ordenesEnCola->get($maquinaId, collect());

        if ($ordenesMaquina->isEmpty()) {
            // Si no está en cola, no podemos calcular
            return null;
        }

        // Iniciar desde ahora
        $cursor = now();

        // Procesar todas las planillas en orden hasta llegar a la que buscamos
        foreach ($ordenesMaquina as $orden) {
            // Obtener elementos de esta planilla-máquina
            $elementos = Elemento::where('planilla_id', $orden->planilla_id)
                ->where('maquina_id', $maquinaId)
                ->where('estado', 'pendiente')
                ->get();

            if ($elementos->isEmpty()) {
                continue;
            }

            // Sumar tiempo de fabricación
            $tiempoSegundos = $elementos->sum('tiempo_fabricacion');

            // Calcular tramos laborales
            $tramos = $this->generarTramosLaborales($cursor, $tiempoSegundos, $festivosSet);

            if (empty($tramos)) {
                continue;
            }

            // El fin es el end del último tramo
            $ultimoTramo = end($tramos);
            $finCalculado = $ultimoTramo['end'];

            // Si esta es la planilla que buscamos, devolver el fin
            if ($orden->planilla_id == $planillaId) {
                return $finCalculado;
            }

            // Avanzar el cursor para la siguiente planilla
            $cursor = $finCalculado;
        }

        return null;
    }

    /** ¿Es no laborable? (festivo o fin de semana) */
    private function esNoLaborable(Carbon $dia, array $festivosSet): bool
    {
        return isset($festivosSet[$dia->toDateString()]) || $dia->isWeekend();
    }

    /** Siguiente momento laborable a partir de $dt */
    private function siguienteLaborableInicio(Carbon $dt, array $festivosSet): Carbon
    {
        $x = $dt->copy();
        $maxIter = 365;
        $iter = 0;

        while ($iter < $maxIter) {
            // Caso especial: domingo - puede tener turnos nocturnos que empiezan ese día
            if ($x->dayOfWeek === Carbon::SUNDAY) {
                $segmentosDomingo = $this->obtenerSegmentosLaborablesDia($x);

                // Buscar si hay algún segmento que empiece el domingo (ej: turno noche 22:00)
                foreach ($segmentosDomingo as $seg) {
                    // Si el segmento empieza el domingo y el cursor está antes
                    if ($seg['inicio']->dayOfWeek === Carbon::SUNDAY && $x->lt($seg['fin'])) {
                        return $x->lt($seg['inicio']) ? $seg['inicio'] : $x;
                    }
                }
            }

            // Si es día laborable (no festivo, no fin de semana)
            if (!$this->esNoLaborable($x, $festivosSet)) {
                // Obtener segmentos del día
                $segmentos = $this->obtenerSegmentosLaborablesDia($x);

                if (!empty($segmentos)) {
                    // Si el cursor está antes del primer segmento, ir al primer segmento
                    $primerSegmento = $segmentos[0];
                    if ($x->lt($primerSegmento['inicio'])) {
                        return $primerSegmento['inicio'];
                    }

                    // Buscar un segmento donde el cursor esté antes o dentro
                    foreach ($segmentos as $seg) {
                        if ($x->lt($seg['fin'])) {
                            return $x->lt($seg['inicio']) ? $seg['inicio'] : $x;
                        }
                    }

                    // Si llegamos aquí, el cursor está después de todos los segmentos del día
                    // Avanzar al siguiente día
                } else {
                    // No hay segmentos (no hay turnos activos), retornar el día a las 00:00
                    return $x->startOfDay();
                }
            }

            // Avanzar al siguiente día a las 00:00
            $x->addDay()->startOfDay();
            $iter++;
        }

        // Fallback
        return $x;
    }

    // Cache de turnos activos para evitar queries repetidas
    private $turnosActivosCache = null;

    /**
     * Obtener turnos activos (con cache)
     */
    private function obtenerTurnosActivos()
    {
        if ($this->turnosActivosCache === null) {
            $this->turnosActivosCache = Turno::activos()->ordenados()->get();
        }
        return $this->turnosActivosCache;
    }

    /**
     * Obtener segmentos laborables del día basados en turnos activos
     */
    private function obtenerSegmentosLaborablesDia(Carbon $dia): array
    {
        $turnosActivos = $this->obtenerTurnosActivos();
        $segmentos = [];
        $esDomingo = $dia->dayOfWeek === Carbon::SUNDAY;
        $esSabado = $dia->dayOfWeek === Carbon::SATURDAY;

        foreach ($turnosActivos as $turno) {
            if (!$turno->hora_inicio || !$turno->hora_fin) {
                continue;
            }

            // Para domingo: solo turnos que empiezan el domingo (offset_inicio < 0)
            // esto significa turnos nocturnos que técnicamente son del lunes
            if ($esDomingo && $turno->offset_dias_inicio >= 0) {
                continue; // Saltar turnos normales (mañana, tarde) del domingo
            }

            // Para sábado: no generar ningún segmento
            if ($esSabado) {
                continue;
            }

            $horaInicio = \Carbon\Carbon::parse($turno->hora_inicio);
            $horaFin = \Carbon\Carbon::parse($turno->hora_fin);

            $inicio = $dia->copy()->setTime($horaInicio->hour, $horaInicio->minute, 0);
            $fin = $dia->copy()->setTime($horaFin->hour, $horaFin->minute, 0);

            // Si el turno termina al día siguiente (offset_dias_fin = 1)
            if ($turno->offset_dias_fin == 1) {
                $fin->addDay();
            }

            // Si fin es antes que inicio, significa que cruza medianoche
            if ($fin->lte($inicio)) {
                $fin->addDay();
            }

            $segmentos[] = ['inicio' => $inicio, 'fin' => $fin];
        }

        return $segmentos;
    }

    /**
     * Divide una duración (segundos) en tramos [start,end) usando turnos activos.
     * Si el inicio cae en no laborable, arranca en el siguiente laborable.
     * Consume solo las horas de los turnos activos.
     */
    private function generarTramosLaborales(Carbon $inicio, int $durSeg, array $festivosSet): array
    {
        $tramos   = [];
        $restante = max(0, (int) $durSeg);

        // Verificar si el inicio está dentro de horario laborable
        // IMPORTANTE: también verificar segmentos del día anterior que se extiendan al día actual
        // (como el turno de noche 22:00-06:00)
        $segmentosInicio = $this->obtenerSegmentosLaborablesDia($inicio);
        $segmentosDiaAnterior = $this->obtenerSegmentosLaborablesDia($inicio->copy()->subDay());
        $todosSegmentos = array_merge($segmentosDiaAnterior, $segmentosInicio);
        $dentroDeSegmento = false;

        foreach ($todosSegmentos as $seg) {
            if ($inicio->gte($seg['inicio']) && $inicio->lt($seg['fin'])) {
                $dentroDeSegmento = true;
                break;
            }
        }

        // Si el inicio NO está dentro de un segmento laborable, mover al siguiente
        if (!$dentroDeSegmento) {
            // Si es fin de semana/festivo O fuera de horario de turnos
            if ($this->esNoLaborable($inicio, $festivosSet) || empty($segmentosInicio)) {
                $inicio = $this->siguienteLaborableInicio($inicio, $festivosSet);
            } else {
                // Está en día laborable pero fuera de horario de turnos
                // Buscar el próximo segmento del mismo día o siguiente
                $inicio = $this->siguienteLaborableInicio($inicio, $festivosSet);
            }
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

            // Obtener segmentos laborables del día basados en turnos activos
            // IMPORTANTE: también incluir segmentos del día anterior que se extiendan a hoy
            $diaActual = $cursor->copy()->startOfDay(); // Guardar referencia al día que estamos procesando
            $segmentosHoy = $this->obtenerSegmentosLaborablesDia($cursor);
            $segmentosAyer = $this->obtenerSegmentosLaborablesDia($cursor->copy()->subDay());

            // Combinar segmentos y filtrar solo los que sean relevantes para el cursor actual
            $segmentos = collect($segmentosAyer)
                ->merge($segmentosHoy)
                ->filter(fn($seg) => $cursor->lt($seg['fin'])) // Solo segmentos que aún no han terminado
                ->values()
                ->all();

            // Saltar no laborables completos SOLO si no tienen segmentos
            // (Esto permite que domingo tenga segmentos nocturnos)
            if ($this->esNoLaborable($cursor, $festivosSet) && empty($segmentosHoy)) {
                $cursor = $this->siguienteLaborableInicio($cursor, $festivosSet);
                continue;
            }

            if (empty($segmentos)) {
                // Si no hay segmentos (no hay turnos con horarios definidos), usar 24h
                $limiteDia = $cursor->copy()->startOfDay()->addDay();
                $tsLimite  = (int) $limiteDia->getTimestamp();
                $tsCursor  = (int) $cursor->getTimestamp();
                $capacidad = max(0, $tsLimite - $tsCursor);
                $consume   = min($restante, $capacidad);

                if ($consume > 0) {
                    $start = $cursor->copy();
                    $end   = $cursor->copy()->addSeconds($consume);
                    $tramos[] = ['start' => $start, 'end' => $end];

                    $restante -= $consume;
                    $cursor    = $end->copy(); // Hacer copia para evitar referencias compartidas
                }

                // Si queda trabajo y llegamos al final del día → siguiente laborable
                if ($restante > 0 && (int)$cursor->getTimestamp() >= $tsLimite) {
                    $cursor = $this->siguienteLaborableInicio($cursor, $festivosSet);
                }

                // Protección adicional: si el cursor no avanzó, forzar avance
                if ($consume == 0) {
                    $cursor->addDay()->startOfDay();
                }
            } else {
                // Hay turnos activos - consumir solo durante los segmentos laborables
                $consumidoEnEsteDia = false;

                foreach ($segmentos as $segmento) {
                    $inicioSeg = $segmento['inicio'];
                    $finSeg = $segmento['fin'];

                    // Si el cursor está después de este segmento, continuar con el siguiente
                    if ($cursor->gte($finSeg)) {
                        continue;
                    }

                    // Si el cursor está antes del segmento, moverlo al inicio
                    if ($cursor->lt($inicioSeg)) {
                        $cursor = $inicioSeg->copy();
                    }

                    // Calcular cuánto podemos consumir de este segmento
                    $capacidadSeg = max(0, $cursor->diffInSeconds($finSeg, false));
                    $consume = min($restante, $capacidadSeg);

                    if ($consume > 0) {
                        $start = $cursor->copy();
                        $end = $cursor->copy()->addSeconds($consume);
                        $tramos[] = ['start' => $start, 'end' => $end];

                        $restante -= $consume;
                        $cursor = $end->copy(); // Hacer copia para evitar referencias compartidas
                        $consumidoEnEsteDia = true;
                    }

                    // Si no queda más tiempo, salir
                    if ($restante <= 0) {
                        break;
                    }
                }

                // Si aún queda tiempo después de procesar todos los segmentos del día
                if ($restante > 0) {
                    // ✅ MEJORA: Verificar si hay continuidad entre el último segmento de hoy y el primero de mañana
                    $ultimoSegmentoHoy = end($segmentos);

                    // El día siguiente es +1 al día que acabamos de procesar
                    $siguienteDia = $diaActual->copy()->addDay();

                    $segmentosSiguienteDia = $this->obtenerSegmentosLaborablesDia($siguienteDia);

                    // Si hay segmentos mañana y el último segmento de hoy conecta con el primero de mañana
                    if (!empty($segmentosSiguienteDia)) {
                        $primerSegmentoManana = $segmentosSiguienteDia[0];

                        // Verificar si son continuos (ej: turno noche 22:00 hoy → 06:00 mañana)
                        if ($ultimoSegmentoHoy &&
                            $ultimoSegmentoHoy['fin']->equalTo($primerSegmentoManana['inicio'])) {
                            // Son continuos, avanzar al día siguiente SIN crear corte
                            $cursor = $siguienteDia->copy()->startOfDay();
                            continue;
                        }
                    }

                    // No hay continuidad, avanzar al día siguiente normalmente
                    $cursor = $siguienteDia->copy()->startOfDay();

                    // Saltar días no laborables (pero verificar si tienen segmentos antes)
                    $diasSaltados = 0;
                    while ($diasSaltados < 365) {
                        $segmentosDelDia = $this->obtenerSegmentosLaborablesDia($cursor);

                        // Si no es laborable Y no tiene segmentos, saltar
                        if ($this->esNoLaborable($cursor, $festivosSet) && empty($segmentosDelDia)) {
                            $cursor->addDay();
                            $diasSaltados++;
                        } else {
                            // Es laborable o tiene segmentos (ej: domingo con turno noche)
                            break;
                        }
                    }

                    if ($diasSaltados >= 365) {
                        Log::error('TRAMOS: bucle infinito detectado buscando día laborable');
                        break;
                    }
                }
            }
        }

        return $tramos;
    }

    //---------------------------------------------------------- CARGAS MAQUINAS
    public function cargasMaquinas(Request $request)
    {
        // 🔹 Filtros del formulario (opcionales)
        $fechaInicio = $request->input('fechaInicio'); // 'YYYY-MM-DD'
        $fechaFin    = $request->input('fechaFin');    // 'YYYY-MM-DD'
        $turnoFiltro = $request->input('turno');       // 'mañana' | 'tarde' | 'noche' | null

        // 🔹 MÁQUINAS DISPONIBLES
        $maquinas = Maquina::whereNotNull('tipo')
            ->where('tipo', '<>', 'grua')
            ->orderByRaw('CASE WHEN obra_id IS NULL THEN 1 ELSE 0 END')  // NULL al final
            ->orderBy('obra_id')   // primero ordena por obra
            ->orderBy('tipo')      // luego por tipo dentro de cada obra
            ->get();

        // 🔹 Calcular cargas por turno
        $turnosLista = Turno::all();
        [$cargaTurnoResumen, $planDetallado, $realDetallado] =
            $this->calcularPlanificadoYRealPorTurno($maquinas, $fechaInicio, $fechaFin, $turnoFiltro);

        return view('produccion.cargas-maquinas', [
            'maquinas'           => $maquinas,
            'cargaTurnoResumen'  => $cargaTurnoResumen,
            'planDetallado'      => $planDetallado,
            'realDetallado'      => $realDetallado,
            'turnosLista'        => $turnosLista,
            'filtro_fecha_inicio' => $fechaInicio,
            'filtro_fecha_fin'   => $fechaFin,
            'filtro_turno'       => $turnoFiltro,
        ]);
    }

    //---------------------------------------------------------- REORDENAR PLANILLAS
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
            'crear_nueva_posicion' => 'sometimes|boolean',
            'usar_posicion_existente' => 'sometimes|boolean',
        ]);

        $planillaId   = (int) $request->id;
        $maqDestino   = (int) $request->maquina_id;
        $maqOrigen    = (int) $request->maquina_origen_id;
        $posNueva     = (int) $request->nueva_posicion;
        $forzar       = (bool) $request->boolean('forzar_movimiento');
        $subsetIds    = collect($request->input('elementos_id', []))->map(fn($v) => (int)$v);
        $crearNuevaPosicion = $request->boolean('crear_nueva_posicion', false);
        $usarPosicionExistente = $request->boolean('usar_posicion_existente', false);

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

        // 🔍 Verificar si ya existen elementos de esta planilla en otra posición de la máquina destino
        $ordenExistente = OrdenPlanilla::where('planilla_id', $planillaId)
            ->where('maquina_id', $maqDestino)
            ->first();

        // Si existe un orden en la máquina destino y no es el mismo que el origen, verificar si realmente hay elementos allí
        if ($ordenExistente && $maqOrigen !== $maqDestino && !$crearNuevaPosicion && !$usarPosicionExistente) {
            // Verificar si realmente hay elementos de esta planilla en esa máquina
            $elementosExistentes = Elemento::where('planilla_id', $planillaId)
                ->where('maquina_id', $maqDestino)
                ->exists();

            if ($elementosExistentes) {
                Log::warning("⚠️ Ya existen elementos de esta planilla en otra posición de la máquina destino");
                return response()->json([
                    'success' => false,
                    'requiresNuevaPosicionConfirmation' => true,
                    'message' => "Ya hay elementos de esta planilla en la posición {$ordenExistente->posicion} de esta máquina. ¿Quieres crear una nueva posición o mover a la posición existente?",
                    'posicion_existente' => $ordenExistente->posicion,
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($planillaId, $maqOrigen, $maqDestino, $posNueva, $compatibles, $subsetIds, $forzar, $crearNuevaPosicion, $usarPosicionExistente) {
                // 3) Movimiento (parcial si venía forzado)
                if ($compatibles->isNotEmpty()) {
                    // Usar SubEtiquetaService para reubicar subetiquetas correctamente
                    $subEtiquetaService = app(SubEtiquetaService::class);

                    foreach ($compatibles as $elemento) {
                        // Actualizar maquina_id
                        $elemento->maquina_id = $maqDestino;
                        $elemento->save();

                        // Reubicar subetiqueta: MSR20 agrupa con hermanos, resto un elemento por sub
                        $subEtiquetaService->reubicarParaProduccion($elemento, $maqDestino);
                    }

                    Log::info("➡️ Elementos actualizados a máquina destino con reubicación de subetiquetas", [
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

                // Si el usuario quiere usar la posición existente, simplemente usar el ordenDestino que ya existe
                if ($usarPosicionExistente && $ordenDestino) {
                    Log::info("✅ Usando posición existente", [
                        'orden_id' => $ordenDestino->id,
                        'posicion' => $ordenDestino->posicion,
                    ]);
                    // No hacer nada más con el orden, ya existe y se reutiliza
                } else {
                    // Si el usuario quiere crear una nueva posición, siempre crear un nuevo OrdenPlanilla
                    if ($crearNuevaPosicion && $ordenDestino) {
                        // Ya existe uno, pero el usuario quiere crear una nueva posición
                        // En este caso, NO reutilizamos el existente, creamos uno nuevo
                        $ordenDestino = null;
                    }

                    if (!$ordenDestino) {
                        // 🆕 Si se está creando una nueva posición, insertarla en la posición deseada
                        // y desplazar las demás. Si no, añadirla al final.
                        if ($crearNuevaPosicion) {
                            // Desplazar posiciones >= $posNueva
                            OrdenPlanilla::where('maquina_id', $maqDestino)
                                ->where('posicion', '>=', $posNueva)
                                ->increment('posicion');

                            $ordenDestino = OrdenPlanilla::create([
                                'planilla_id' => $planillaId,
                                'maquina_id'  => $maqDestino,
                                'posicion'    => $posNueva,
                            ]);
                            Log::info("➕ Orden creado en nueva posición", ['posicion' => $posNueva, 'crear_nueva' => true]);
                        } else {
                            $maxPos = (int) (OrdenPlanilla::where('maquina_id', $maqDestino)->max('posicion') ?? 0);
                            $ordenDestino = OrdenPlanilla::create([
                                'planilla_id' => $planillaId,
                                'maquina_id'  => $maqDestino,
                                'posicion'    => $maxPos + 1,
                            ]);
                            Log::info("➕ Orden creado al final", ['posicion' => $maxPos + 1, 'crear_nueva' => false]);
                        }
                    }
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
                // ⚠️ SOLO si NO se acaba de crear con la posición correcta y NO se está usando posición existente
                if (!$crearNuevaPosicion && !$usarPosicionExistente) {
                    $this->reordenarPosicionEnMaquina($maqDestino, $planillaId, $posNueva);
                }
            });

            // 🔄 Obtener eventos actualizados de ambas máquinas
            $maquinasAfectadas = array_unique([$maqOrigen, $maqDestino]);
            $eventosActualizados = $this->obtenerEventosDeMaquinas($maquinasAfectadas);

            Log::info("✅ Planilla reordenada correctamente", [
                'planilla_id' => $planillaId,
                'eventos_actualizados' => count($eventosActualizados),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Planilla reordenada correctamente.',
                'eventos' => $eventosActualizados, // 👈 Eventos actualizados
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
    /**
     * Obtener eventos de planillas para máquinas específicas
     */
    private function obtenerEventosDeMaquinas(array $maquinaIds)
    {
        $maquinas = Maquina::whereIn('id', $maquinaIds)
            ->whereNotNull('tipo')
            ->orderByRaw('CASE WHEN obra_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('obra_id')
            ->orderBy('tipo')
            ->get();

        $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina', 'subetiquetas'])
            ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando']))
            ->where(fn($q) => $q->whereNull('estado')->orWhere('estado', '<>', 'fabricado'))
            ->where(function ($q) use ($maquinaIds) {
                $q->whereIn('maquina_id', $maquinaIds)
                    ->orWhereIn('maquina_id_2', $maquinaIds)
                    ->orWhereIn('maquina_id_3', $maquinaIds);
            })
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

            $fechaInicioCola = optional($ultimaPlanillaFabricando)->fecha_inicio
                ? toCarbon($ultimaPlanillaFabricando->fecha_inicio)
                : now();

            // Validar que la fecha no esté demasiado lejos en el futuro
            $maxFecha = Carbon::now()->addYear();
            if ($fechaInicioCola->gt($maxFecha)) {
                Log::warning('COLA MAQUINA: fecha_inicio demasiado lejana, usando now()', [
                    'maquina_id' => $m->id,
                    'fecha_inicio' => $fechaInicioCola->toIso8601String(),
                    'planilla_id' => optional($ultimaPlanillaFabricando)->id,
                ]);
                $fechaInicioCola = Carbon::now();
            }

            $colasMaquinas[$m->id] = $fechaInicioCola;
        }

        $ordenes = OrdenPlanilla::whereIn('maquina_id', $maquinaIds)
            ->orderBy('posicion')
            ->get()
            ->groupBy('maquina_id')
            ->map(fn($ordenesMaquina) => $ordenesMaquina->pluck('planilla_id')->all());

        return $this->generarEventosMaquinas($planillasAgrupadas, $ordenes, $colasMaquinas);
    }
    /** Reordena sólo en la misma máquina, sin validar nada */
    private function soloReordenarEnMismaMaquina($maquinaId, $planillaId, $posNueva)
    {
        Log::info("🔁 Movimiento en misma máquina (sin validación)", [
            'maquina' => $maquinaId,
            'planilla' => $planillaId,
            'nueva_pos' => $posNueva,
        ]);

        $this->reordenarPosicionEnMaquina($maquinaId, $planillaId, $posNueva);

        // 🔄 Obtener eventos actualizados de la máquina
        $eventosActualizados = $this->obtenerEventosDeMaquinas([$maquinaId]);

        return response()->json([
            'success' => true,
            'message' => 'Planilla reordenada en la misma máquina.',
            'eventos' => $eventosActualizados, // 👈 Eventos actualizados
        ]);
    }

    public function porIds(Request $request)
    {
        // Si se proporciona planilla_id, obtener todos los elementos de la planilla
        if ($request->has('planilla_id')) {
            $elementos = Elemento::where('planilla_id', $request->planilla_id)
                ->select('id', 'codigo', 'diametro', 'peso', 'dimensiones', 'maquina_id', 'barras')
                ->with('maquina:id,nombre')
                ->orderBy('maquina_id')
                ->get();
        } else {
            // Comportamiento original para compatibilidad
            $ids = explode(',', $request->ids);
            $elementos = Elemento::whereIn('id', $ids)
                ->select('id', 'codigo', 'diametro', 'peso', 'dimensiones', 'maquina_id', 'barras')
                ->with('maquina:id,nombre')
                ->orderBy('maquina_id')
                ->get();
        }

        return response()->json($elementos);
    }

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

            // Safeguard: validar que inicioCola no esté demasiado lejos en el futuro
            $maxFecha = Carbon::now()->addYear();
            if ($inicioCola->gt($maxFecha)) {
                Log::error('EVT: inicioCola inesperadamente lejana (debería haberse validado antes)', [
                    'maquinaId' => $maquinaId,
                    'inicioCola' => $inicioCola->toIso8601String(),
                ]);
                $inicioCola = Carbon::now();
            }

            $primeraOrden = $planillasOrdenadas[0] ?? null;
            $primeraId = is_array($primeraOrden) ? ($primeraOrden['planilla_id'] ?? null) : $primeraOrden;

            foreach ($planillasOrdenadas as $ordenData) {
                // Soporte para ambos formatos: array con datos o solo ID
                $planillaId = is_array($ordenData) ? ($ordenData['planilla_id'] ?? null) : $ordenData;
                $posicion = is_array($ordenData) ? ($ordenData['posicion'] ?? null) : null;
                $ordenId = is_array($ordenData) ? ($ordenData['id'] ?? null) : null;

                if (!$planillaId) continue;

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

                    // Sub-agrupar elementos por orden_planilla_id para crear eventos independientes
                    $subGrupos = collect();
                    if ($grupo instanceof Collection) {
                        $subGrupos = $grupo->groupBy(function($elem) {
                            return $elem->orden_planilla_id ?? 'sin_orden';
                        });
                    }

                    // Si no hay orden_planilla_id, tratar todo el grupo como un solo evento
                    if ($subGrupos->isEmpty()) {
                        $subGrupos = collect(['unico' => $grupo]);
                    }

                    // CONSOLIDAR: Si hay múltiples subgrupos para la misma planilla/máquina, unificarlos
                    if ($subGrupos->count() > 1) {
                        // Usar el primer orden_planilla_id y consolidar todos los elementos
                        $primerOrdenKey = $subGrupos->keys()->first();
                        $todosElementos = $subGrupos->flatten();
                        $subGrupos = collect([$primerOrdenKey => $todosElementos]);
                    }

                    // Procesar cada sub-grupo como un evento independiente
                    foreach ($subGrupos as $ordenKey => $subGrupo) {
                        $grupoCount = $subGrupo->count();

                        // Separar elementos por estado dentro de este sub-grupo
                        $elementosPendientes = collect();
                        $elementosFabricandoOCompletados = collect();

                        foreach ($subGrupo as $elem) {
                            if ($elem->estado === 'pendiente') {
                                $elementosPendientes->push($elem);
                            } else {
                                $elementosFabricandoOCompletados->push($elem);
                            }
                        }

                        // Calcular duración: tiempo_fabricacion del elemento + 20 min de amarrado por elemento
                        $duracionSegundos = $subGrupo->sum(function($elemento) {
                            $tiempoFabricacion = (float)($elemento->tiempo_fabricacion ?? 1200);
                            $tiempoAmarrado = 1200; // 20 minutos por elemento
                            return $tiempoFabricacion + $tiempoAmarrado;
                        });
                        $duracionSegundos = max($duracionSegundos, 3600); // mínimo 1 hora

                        // Buscar la fecha_inicio más antigua de las etiquetas fabricando/completadas
                        $fechaInicioMasAntigua = null;
                        if ($elementosFabricandoOCompletados->isNotEmpty()) {
                            $fechasInicio = collect();
                            foreach ($elementosFabricandoOCompletados as $elem) {
                                $etiqueta = $elem->subetiquetas->first();
                                if ($etiqueta && !empty($etiqueta->fecha_inicio)) {
                                    $fechasInicio->push(toCarbon($etiqueta->fecha_inicio));
                                }
                            }
                            $fechaInicioMasAntigua = $fechasInicio->min();
                        }

                        // Determinar fecha de inicio y duración
                        if ($fechaInicioMasAntigua) {
                            // Si hay algún elemento fabricando/completado, usar su fecha de inicio
                            $fechaInicio = $fechaInicioMasAntigua;

                            // Duración = now() + tiempo de elementos pendientes + amarrado
                            $duracionPendientes = $elementosPendientes->sum(function($elemento) {
                                $tiempoFabricacion = (float)($elemento->tiempo_fabricacion ?? 1200);
                                $tiempoAmarrado = 1200;
                                return $tiempoFabricacion + $tiempoAmarrado;
                            });

                            // El evento se alarga hasta now() + tiempo pendiente
                            $tiempoTranscurrido = $fechaInicio->diffInSeconds(Carbon::now());
                            $duracionSegundos = $tiempoTranscurrido + $duracionPendientes;
                            $duracionSegundos = max($duracionSegundos, 3600);
                        } else {
                            // Para sub-grupos completamente pendientes: usar siempre inicioCola
                            // Esto asegura continuidad entre eventos consecutivos
                            $fechaInicio = $inicioCola->copy();
                        }

                        $tramos = $this->generarTramosLaborales($fechaInicio, $duracionSegundos, $festivosSet);

                        if (empty($tramos)) {
                            Log::warning('EVT H1: sin tramos', ['planillaId' => $planillaId, 'maquinaId' => $maquinaId, 'ordenKey' => $ordenKey]);
                            continue;
                        }

                        $ultimoTramo  = end($tramos);
                        $fechaFinReal = $ultimoTramo['end'] instanceof Carbon
                            ? $ultimoTramo['end']->copy()
                            : Carbon::parse($ultimoTramo['end']);

                        // Progreso (calculado por sub-grupo)
                        $progreso = null;
                        if ($primeraId !== null && $primeraId === $planilla->id) {
                            $completados = $subGrupo->where('estado', 'fabricado')->count();
                            $total = $subGrupo->count();
                            $progreso = $total > 0 ? round(($completados / $total) * 100) : 0;
                        }

                        $appTz = config('app.timezone') ?: 'Europe/Madrid';

                        // Fin real ya lo tienes:
                        $fechaFinReal = ($ultimoTramo['end'] instanceof Carbon ? $ultimoTramo['end'] : Carbon::parse($ultimoTramo['end']))
                            ->copy()->setTimezone($appTz);

                        // Fecha de entrega (ahora robusta)
                        $fechaEntrega = $this->parseFechaEntregaFlexible($planilla->fecha_estimada_entrega, $appTz);

                        // ⚠️ SISTEMA DE REVISIÓN: Si no está revisada → GRIS
                        if (!$planilla->revisada) {
                            $backgroundColor = '#9e9e9e'; // Gris para planillas sin revisar
                        } else {
                            // Semáforo (rojo si fin real supera entrega)
                            $backgroundColor = ($fechaEntrega && $fechaFinReal->gt($fechaEntrega)) ? '#ef4444' : '#22c55e';
                        }

                        // COMPACTAR: Crear UN SOLO evento que abarque todos los tramos
                        $primerTramo = reset($tramos);
                        $ultimoTramo = end($tramos);

                        $eventoInicio = $primerTramo['start'] instanceof Carbon ? $primerTramo['start'] : Carbon::parse($primerTramo['start']);
                        $eventoFin = $ultimoTramo['end'] instanceof Carbon ? $ultimoTramo['end'] : Carbon::parse($ultimoTramo['end']);

                        // Título del evento con advertencia si no está revisada
                        $tituloEvento = $planilla->codigo_limpio ?? ('Planilla #' . $planilla->id);
                        if (!$planilla->revisada) {
                            $tituloEvento = '⚠️ ' . $tituloEvento . ' (SIN REVISAR)';
                        }

                        // ID único compacto (sin segmento)
                        $eventoId = 'planilla-' . $planilla->id . '-maq' . $maquinaId . '-orden' . $ordenKey;
                        if (isset($ordenId) && $ordenId !== null) {
                            $eventoId .= '-ord' . $ordenId;
                        }

                        $planillasEventos->push([
                            'id'              => $eventoId,
                            'title'           => $tituloEvento,
                            'codigo'          => $planilla->codigo_limpio ?? ('Planilla #' . $planilla->id),
                            'start'           => $eventoInicio->toIso8601String(),
                            'end'             => $eventoFin->toIso8601String(),
                            'resourceId'      => $maquinaId,
                            'backgroundColor' => $backgroundColor,
                            'borderColor'     => !$planilla->revisada ? '#757575' : null,
                            'classNames'      => !$planilla->revisada ? ['evento-sin-revisar'] : ['evento-revisado'],
                            'extendedProps' => [
                                'planilla_id'    => $planilla->id,
                                'obra'           => optional($planilla->obra)->obra ?? '—',
                                'cod_obra'       => optional($planilla->obra)->cod_obra ?? '—',
                                'cliente'        => optional($planilla->obra->cliente)->empresa ?? '—',
                                'cod_cliente'    => optional($planilla->obra->cliente)->codigo ?? '—',
                                'codigo_planilla' => $planilla->codigo_limpio ?? ('Planilla #' . $planilla->id),
                                'estado'         => $planilla->estado,
                                'duracion_horas' => round($duracionSegundos / 3600, 2),
                                'progreso'       => $progreso,
                                    'fecha_entrega'  => $fechaEntrega?->format('d/m/Y H:i') ?? '—',
                                    'fin_programado' => $fechaFinReal->format('d/m/Y H:i'),
                                    'codigos_elementos' => $subGrupo->pluck('codigo')->values(),
                                    'elementos_id'      => $subGrupo->pluck('id')->values(),
                                    'revisada'          => $planilla->revisada,
                                    'revisada_por'      => optional($planilla->revisor)->name,
                                    'revisada_at'       => $planilla->revisada_at?->format('d/m/Y H:i'),
                                ],
                        ]);

                        // Actualizar inicioCola para el siguiente sub-grupo
                        $inicioCola = $fechaFinReal->copy();
                    } // fin foreach subGrupos

                    // Avanza cola (solo si fechaFinReal está definida)
                    if (isset($fechaFinReal)) {
                        $inicioCola = $fechaFinReal->copy();
                    }
                } catch (\Throwable $e) {
                    Log::error('EVT X: excepción en bucle planilla', [
                        'clave' => $clave,
                        'err'   => $e->getMessage(),
                        'file'  => $e->getFile() . ':' . $e->getLine(),
                    ]);
                    // Continuar con la siguiente planilla en lugar de abortar
                    continue;
                }
            }

            $colasMaquinas[$maquinaId] = $inicioCola;
        }


        return $planillasEventos->values();
    }
    //---------------------------------------------------------- REORDENAR PLANILLAS
    //---------------------------------------------------------- REORDENAR PLANILLAS
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
                ->orderByRaw('CASE WHEN obra_id IS NULL THEN 1 ELSE 0 END')
                ->orderBy('obra_id')
                ->orderBy('tipo')
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

                $fechaInicioCola = optional($ultimaPlanillaFabricando)->fecha_inicio
                    ? toCarbon($ultimaPlanillaFabricando->fecha_inicio)
                    : now();

                // Validar que la fecha no esté demasiado lejos en el futuro
                $maxFecha = Carbon::now()->addYear();
                if ($fechaInicioCola->gt($maxFecha)) {
                    Log::warning('COLA MAQUINA: fecha_inicio demasiado lejana, usando now()', [
                        'maquina_id' => $m->id,
                        'fecha_inicio' => $fechaInicioCola->toIso8601String(),
                        'planilla_id' => optional($ultimaPlanillaFabricando)->id,
                    ]);
                    $fechaInicioCola = Carbon::now();
                }

                $colasMaquinas[$m->id] = $fechaInicioCola;
            }

            $ordenes = OrdenPlanilla::orderBy('posicion')
                ->get()
                ->groupBy('maquina_id')
                ->map(fn($ordenesMaquina) => $ordenesMaquina->map(fn($orden) => [
                    'planilla_id' => $orden->planilla_id,
                    'posicion' => $orden->posicion,
                    'id' => $orden->id
                ])->all());

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

                $horaEntrada = $turno->hora_inicio ?? '08:00:00';
                $horaSalida  = $turno->hora_fin ?? '16:00:00';

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

                $horaEntrada = $turno->hora_inicio ?? '08:00:00';
                $horaSalida  = $turno->hora_fin ?? '16:00:00';

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

        $trabajadoresFicticios = TrabajadorFicticio::orderBy('nombre')->get();

        return view('produccion.trabajadoresObra', [
            'trabajadoresServicios' => $trabajadoresServicios,
            'trabajadoresHpr'       => $trabajadoresHpr,
            'trabajadoresFicticios' => $trabajadoresFicticios,
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

        $asignaciones = AsignacionTurno::with(['user.categoria', 'user.maquina', 'obra', 'turno'])
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
                $fechaStr = Carbon::parse($asignacion->fecha)->format('Y-m-d');

                return [
                    'id'         => 'turno-' . $asignacion->id,
                    'title'      => $asignacion->user?->nombre_completo ?? 'Desconocido',
                    'start'      => $fechaStr . 'T06:00:00',
                    'end'        => $fechaStr . 'T14:00:00',
                    'resourceId' => $asignacion->obra_id ?? 'sin-obra',
                    'extendedProps' => [
                        'user_id' => $asignacion->user_id,
                        'estado'  => $asignacion->estado,
                        'turno'   => $asignacion->turno?->nombre,
                    ],
                    'backgroundColor' => $color,
                    'borderColor'     => $color,
                    'textColor'       => $color === '#ef4444' ? '#ffffff' : null,
                ];
            })->values()->toArray();

        // Obtener IDs de obras activas (tipo montaje) + sin-obra
        $obrasActivas = Obra::where('tipo', 'montaje')->pluck('id')->toArray();
        $resourceIds = array_merge(['sin-obra'], array_map('strval', $obrasActivas));

        // Añadir festivos
        $festivosEventos = collect(Festivo::eventosCalendario())
            ->filter(function ($e) use ($inicio, $fin) {
                $fechaFestivo = $e['start'];
                return $fechaFestivo >= substr($inicio, 0, 10) && $fechaFestivo <= substr($fin, 0, 10);
            })
            ->map(function ($e) use ($resourceIds) {
                $start = Carbon::parse($e['start'])->startOfDay();
                $end   = $start->copy()->addDay();

                return [
                    'id'              => $e['id'],
                    'title'           => $e['title'],
                    'start'           => $start->toIso8601String(),
                    'end'             => $end->toIso8601String(),
                    'allDay'          => true,
                    'resourceIds'     => $resourceIds,
                    'backgroundColor' => '#ff0000',
                    'borderColor'     => '#b91c1c',
                    'textColor'       => '#ffffff',
                    'editable'        => false,
                    'classNames'      => ['evento-festivo'],
                    'extendedProps'   => [
                        'es_festivo' => true,
                        'festivo_id' => $e['extendedProps']['festivo_id'] ?? null,
                    ],
                ];
            })
            ->values()
            ->toArray();

        // Añadir eventos ficticios
        $eventosFicticios = EventoFicticioObra::with('trabajadorFicticio')
            ->whereBetween('fecha', [substr($inicio, 0, 10), substr($fin, 0, 10)])
            ->get()
            ->map(function ($ef) {
                return [
                    'id'              => 'ficticio-' . $ef->id,
                    'title'           => $ef->trabajadorFicticio?->nombre ?? 'Ficticio',
                    'start'           => $ef->fecha->format('Y-m-d') . 'T06:00:00',
                    'end'             => $ef->fecha->format('Y-m-d') . 'T14:00:00',
                    'resourceId'      => $ef->obra_id ?? 'sin-obra',
                    'backgroundColor' => '#9ca3af',
                    'borderColor'     => '#6b7280',
                    'textColor'       => '#ffffff',
                    'extendedProps'   => [
                        'es_ficticio'            => true,
                        'ficticio_id'            => $ef->id,
                        'trabajador_ficticio_id' => $ef->trabajador_ficticio_id,
                    ],
                ];
            })
            ->toArray();

        return response()->json(array_merge($eventos, $festivosEventos, $eventosFicticios));
    }

    /**
     * Almacena un nuevo trabajador ficticio
     */
    public function storeTrabajadorFicticio(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $trabajador = TrabajadorFicticio::create($validated);

        return response()->json([
            'success'    => true,
            'message'    => 'Trabajador ficticio creado correctamente',
            'trabajador' => $trabajador,
        ]);
    }

    /**
     * Elimina un trabajador ficticio y sus eventos asociados
     */
    public function destroyTrabajadorFicticio($id)
    {
        $trabajador = TrabajadorFicticio::findOrFail($id);

        // Eliminar eventos asociados
        EventoFicticioObra::where('trabajador_ficticio_id', $id)->delete();

        $trabajador->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trabajador ficticio eliminado correctamente',
        ]);
    }

    /**
     * Almacena eventos ficticios de obra (individual o múltiple con rango de fechas)
     */
    public function storeEventoFicticio(Request $request)
    {
        // Asignación múltiple (selección + rango de fechas)
        if ($request->has('trabajador_ficticio_ids')) {
            $request->validate([
                'trabajador_ficticio_ids'   => 'required|array',
                'trabajador_ficticio_ids.*' => 'exists:trabajadores_ficticios,id',
                'obra_id'                   => 'nullable',
                'fecha_inicio'              => 'required|date',
                'fecha_fin'                 => 'required|date|after_or_equal:fecha_inicio',
            ]);

            $obraId = $request->obra_id === 'sin-obra' ? null : $request->obra_id;
            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin = Carbon::parse($request->fecha_fin);
            $eventosCreados = [];

            foreach ($request->trabajador_ficticio_ids as $trabajadorFicticioId) {
                $fecha = $fechaInicio->copy();
                while ($fecha->lte($fechaFin)) {
                    // Verificar si ya existe evento para este trabajador en esta fecha y obra
                    $existe = EventoFicticioObra::where('trabajador_ficticio_id', $trabajadorFicticioId)
                        ->where('fecha', $fecha->toDateString())
                        ->where('obra_id', $obraId)
                        ->exists();

                    if (!$existe) {
                        $evento = EventoFicticioObra::create([
                            'trabajador_ficticio_id' => $trabajadorFicticioId,
                            'fecha'                  => $fecha->toDateString(),
                            'obra_id'                => $obraId,
                        ]);
                        $eventosCreados[] = $evento->id;
                    }
                    $fecha->addDay();
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($eventosCreados) . ' eventos ficticios creados',
                'eventos_creados' => $eventosCreados,
            ]);
        }

        // Asignación individual (drag & drop)
        $validated = $request->validate([
            'trabajador_ficticio_id' => 'required|exists:trabajadores_ficticios,id',
            'fecha'                  => 'required|date',
            'obra_id'                => 'nullable',
        ]);

        $obraId = $request->obra_id === 'sin-obra' ? null : $request->obra_id;

        $evento = EventoFicticioObra::create([
            'trabajador_ficticio_id' => $validated['trabajador_ficticio_id'],
            'fecha'                  => $validated['fecha'],
            'obra_id'                => $obraId,
        ]);
        $evento->load('trabajadorFicticio');

        return response()->json([
            'success' => true,
            'message' => 'Evento ficticio creado correctamente',
            'evento'  => [
                'id'              => 'ficticio-' . $evento->id,
                'title'           => $evento->trabajadorFicticio?->nombre ?? 'Ficticio',
                'start'           => $evento->fecha->format('Y-m-d') . 'T06:00:00',
                'end'             => $evento->fecha->format('Y-m-d') . 'T14:00:00',
                'resourceId'      => $evento->obra_id ?? 'sin-obra',
                'backgroundColor' => '#9ca3af',
                'borderColor'     => '#6b7280',
                'textColor'       => '#ffffff',
                'extendedProps'   => [
                    'es_ficticio'            => true,
                    'ficticio_id'            => $evento->id,
                    'trabajador_ficticio_id' => $evento->trabajador_ficticio_id,
                ],
            ],
        ]);
    }

    /**
     * Actualiza un evento ficticio (fecha y/o obra)
     */
    public function updateEventoFicticio(Request $request, $id)
    {
        $request->validate([
            'fecha'   => 'required|date',
            'obra_id' => 'nullable',
        ]);

        $evento = EventoFicticioObra::findOrFail($id);

        $obraId = $request->obra_id;
        if ($obraId === 'sin-obra' || $obraId === '' || $obraId === null) {
            $obraId = null;
        } else {
            $obraId = (int) $obraId;
        }

        $evento->update([
            'fecha'   => $request->fecha,
            'obra_id' => $obraId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Evento ficticio actualizado correctamente',
        ]);
    }

    /**
     * Elimina un evento ficticio de obra
     */
    public function destroyEventoFicticio($id)
    {
        $evento = EventoFicticioObra::findOrFail($id);
        $evento->delete();

        return response()->json([
            'success' => true,
            'message' => 'Evento ficticio eliminado correctamente',
        ]);
    }

    /**
     * Mover eventos ficticios a otra obra
     */
    public function moverEventosFicticios(Request $request)
    {
        $request->validate([
            'evento_ids'   => 'required|array',
            'evento_ids.*' => 'integer',
            'obra_id'      => 'nullable',
        ]);

        $ids = collect($request->evento_ids)->map(fn($id) => (int) $id)->filter()->values()->toArray();

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No se proporcionaron IDs válidos.'
            ], 400);
        }

        $obraId = $request->obra_id;
        if ($obraId === 'sin-obra' || $obraId === '' || $obraId === null) {
            $obraId = null;
        } else {
            $obraId = (int) $obraId;
        }

        $actualizados = EventoFicticioObra::whereIn('id', $ids)->update([
            'obra_id' => $obraId
        ]);

        return response()->json([
            'success' => true,
            'message' => "Se movieron {$actualizados} eventos ficticios correctamente.",
            'actualizados' => $actualizados
        ]);
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
                /** @var SubEtiquetaService $subEtiquetaService */
                $subEtiquetaService = app(SubEtiquetaService::class);

                foreach ($data['elementos_updates'] as $e) {
                    // estado anterior
                    $before = DB::table('elementos')->where('id', $e['id'])->first();
                    $maquinaAnterior = $before ? $before->maquina_id : null;
                    $nuevaMaquinaId = (int) $e['maquina_id'];

                    DB::table('elementos')
                        ->where('id', $e['id'])
                        ->update([
                            'maquina_id' => $nuevaMaquinaId,
                            'orden_planilla_id' => $e['orden_planilla_id'],
                            'updated_at' => now(),
                        ]);

                    // 🏷️ Si cambió de máquina, reubicar subetiquetas
                    if ($maquinaAnterior !== null && (int) $maquinaAnterior !== $nuevaMaquinaId) {
                        $elemento = Elemento::find($e['id']);
                        if ($elemento) {
                            $subEtiquetaService->reubicarParaProduccion($elemento, $nuevaMaquinaId);
                        }
                    }

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

    /**
     * Analizar planillas con retraso y sugerir redistribución óptima
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function optimizarAnalisis()
    {
        try {
            Log::info('🔍 OPTIMIZAR: Iniciando análisis simple');

            // 1. Obtener todas las máquinas disponibles (excluir grúas, soldadoras y ensambladoras)
            $maquinas = Maquina::whereNotIn('tipo', ['grua', 'soldadora', 'ensambladora'])
                ->whereNotNull('tipo')
                ->where('estado', '!=', 'inactiva')
                ->get()
                ->keyBy('id');

            Log::info('🔍 OPTIMIZAR: Máquinas cargadas', ['total' => $maquinas->count()]);

            // 2. Obtener todas las planillas en cola con sus elementos pendientes
            $ordenesConPlanillas = DB::table('orden_planillas')
                ->join('planillas', 'planillas.id', '=', 'orden_planillas.planilla_id')
                ->select('orden_planillas.*', 'planillas.fecha_estimada_entrega', 'planillas.codigo')
                ->where('planillas.revisada', true)
                ->orderBy('orden_planillas.maquina_id')
                ->orderBy('orden_planillas.posicion')
                ->get();

            Log::info('🔍 OPTIMIZAR: Órdenes en cola', ['total' => $ordenesConPlanillas->count()]);

            // 3. Calcular tiempo acumulado por máquina y detectar retrasos
            $cargaMaquinas = [];
            $planillasConRetraso = [];
            $elementosAMover = [];

            // Obtener festivos para cálculo de tramos
            $festivosSet = $this->obtenerFestivosSet();

            // Agrupar órdenes por máquina para procesar en orden
            $ordenesPorMaquina = $ordenesConPlanillas->groupBy('maquina_id');

            foreach ($ordenesPorMaquina as $maquinaId => $ordenesEnMaquina) {
                // Inicializar cursor de tiempo para esta máquina
                $cargaMaquinas[$maquinaId] = now();

                Log::info('🔍 OPTIMIZAR: Procesando máquina', [
                    'maquina_id' => $maquinaId,
                    'ordenes_en_cola' => $ordenesEnMaquina->count(),
                ]);

                foreach ($ordenesEnMaquina as $orden) {
                    $planillaId = $orden->planilla_id;

                    // Obtener elementos pendientes de esta planilla en esta máquina
                    $elementos = Elemento::where('planilla_id', $planillaId)
                        ->where('maquina_id', $maquinaId)
                        ->where('estado', 'pendiente')
                        ->get();

                    if ($elementos->isEmpty()) {
                        Log::warning('🔍 OPTIMIZAR: Planilla sin elementos pendientes', [
                            'planilla_id' => $planillaId,
                            'maquina_id' => $maquinaId,
                        ]);
                        continue;
                    }

                    // Calcular tiempo total de TODOS los elementos de esta planilla en esta máquina
                    $tiempoSegundos = $elementos->sum('tiempo_fabricacion');

                    Log::info('🔍 OPTIMIZAR: Calculando tramos', [
                        'planilla_id' => $planillaId,
                        'inicio' => $cargaMaquinas[$maquinaId]->format('d/m/Y H:i'),
                        'tiempo_segundos' => $tiempoSegundos,
                        'tiempo_horas' => round($tiempoSegundos / 3600, 2),
                    ]);

                    // ✅ USAR TRAMOS LABORALES como el calendario
                    $tramos = $this->generarTramosLaborales($cargaMaquinas[$maquinaId], $tiempoSegundos, $festivosSet);

                    if (!empty($tramos)) {
                        $ultimoTramo = end($tramos);
                        $finProgramado = $ultimoTramo['end'] instanceof Carbon
                            ? $ultimoTramo['end']->copy()
                            : Carbon::parse($ultimoTramo['end']);

                        Log::info('🔍 OPTIMIZAR: Tramos generados', [
                            'planilla_id' => $planillaId,
                            'num_tramos' => count($tramos),
                            'fin_programado' => $finProgramado->format('d/m/Y H:i'),
                        ]);

                        // Actualizar cursor para siguiente planilla
                        $cargaMaquinas[$maquinaId] = $finProgramado;
                    } else {
                        Log::warning('🔍 OPTIMIZAR: Sin tramos, usando suma lineal', [
                            'planilla_id' => $planillaId,
                        ]);
                        // Si no hay tramos, suma linealmente (fallback)
                        $finProgramado = $cargaMaquinas[$maquinaId]->copy()->addSeconds($tiempoSegundos);
                        $cargaMaquinas[$maquinaId] = $finProgramado;
                    }

                    // Parsear fecha de entrega
                    $fechaEntrega = $this->parseFechaEntregaFlexible($orden->fecha_estimada_entrega);

                    Log::info('🔍 OPTIMIZAR: Analizando planilla', [
                        'planilla_id' => $planillaId,
                        'codigo' => $orden->codigo,
                        'maquina_id' => $maquinaId,
                        'fecha_entrega' => $fechaEntrega?->format('d/m/Y H:i'),
                        'fin_programado' => $finProgramado->format('d/m/Y H:i'),
                        'tiene_retraso' => $fechaEntrega ? $finProgramado->gt($fechaEntrega) : false,
                    ]);

                    // Verificar retraso
                    if ($fechaEntrega && $finProgramado->gt($fechaEntrega)) {
                        if (!in_array($planillaId, $planillasConRetraso)) {
                            $planillasConRetraso[] = $planillaId;
                        }

                        Log::info('🚨 OPTIMIZAR: RETRASO DETECTADO', [
                            'planilla_id' => $planillaId,
                            'codigo' => $orden->codigo,
                            'fecha_entrega' => $fechaEntrega->format('d/m/Y H:i'),
                            'fin_programado' => $finProgramado->format('d/m/Y H:i'),
                            'retraso_horas' => $finProgramado->diffInHours($fechaEntrega),
                        ]);

                        // Analizar cada elemento para encontrar máquinas compatibles
                        foreach ($elementos as $elemento) {
                            $maquinasCompatibles = $this->encontrarMaquinasCompatiblesSimple(
                                $elemento,
                                $maquinas,
                                $maquinaId
                            );

                            if (count($maquinasCompatibles) > 0) {
                                $maquinaSugerida = $maquinasCompatibles[0];

                                $elementosAMover[] = [
                                    'id' => $elemento->id,
                                    'codigo' => $elemento->codigo,
                                    'planilla_codigo' => $orden->codigo ?? 'N/A',
                                    'planilla_id' => $planillaId,
                                    'diametro' => $elemento->diametro,
                                    'tipo_material' => $maquinas[$maquinaId]->tipo_material ?? null,
                                    'peso' => $elemento->peso,
                                    'maquina_actual_id' => $maquinaId,
                                    'maquina_actual_nombre' => $maquinas[$maquinaId]->nombre ?? 'N/A',
                                    'fecha_entrega' => $fechaEntrega->toIso8601String(),
                                    'fin_programado' => $finProgramado->toIso8601String(),
                                    'maquina_destino_sugerida' => $maquinaSugerida['id'],
                                    'maquinas_compatibles' => $maquinasCompatibles,
                                ];
                            }
                        }
                    }
                }
            }

            // 4. Contar máquinas disponibles
            $maquinasDisponibles = $maquinas->count();

            return response()->json([
                'planillas_retraso' => count($planillasConRetraso),
                'elementos' => $elementosAMover,
                'maquinas_disponibles' => $maquinasDisponibles,
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error en optimizarAnalisis:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error al analizar planillas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analizar y sugerir balanceo de carga entre máquinas
     * Redistribuye elementos para igualar el tiempo de trabajo entre máquinas
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function balancearCargaAnalisis()
    {
        try {
            Log::info('⚖️ BALANCEO: Iniciando análisis de carga');

            // 1. Obtener todas las máquinas disponibles (excluir grúas)
            $maquinas = Maquina::whereNotNull('tipo')
                ->where('tipo', '<>', 'grua')
                ->where('tipo', '<>', 'soldadora')
                ->where('tipo', '<>', 'ensambladora')
                ->get();

            if ($maquinas->isEmpty()) {
                return response()->json([
                    'elementos' => [],
                    'mensaje' => 'No hay máquinas disponibles'
                ]);
            }

            // 2. Calcular carga actual de cada máquina (en segundos)
            $cargasMaquinas = [];
            $elementosPorMaquina = [];

            foreach ($maquinas as $maquina) {
                // SOLO elementos PENDIENTES (no fabricando ni completados)
                $elementos = Elemento::with(['planilla', 'maquina'])
                    ->where('maquina_id', $maquina->id)
                    ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando', 'programada']))
                    ->where('estado', 'pendiente') // SOLO pendientes se pueden mover
                    ->get();

                $tiempoTotal = $elementos->sum('tiempo_fabricacion');

                \Log::info("⚖️ BALANCEO: Carga de máquina", [
                    'maquina' => $maquina->nombre,
                    'elementos_movibles' => $elementos->count(),
                    'tiempo_horas' => round($tiempoTotal / 3600, 2),
                    'elementos_sample' => $elementos->take(3)->pluck('codigo')->toArray()
                ]);

                $cargasMaquinas[$maquina->id] = [
                    'maquina' => $maquina,
                    'tiempo_segundos' => $tiempoTotal,
                    'tiempo_horas' => round($tiempoTotal / 3600, 2),
                    'cantidad_elementos' => $elementos->count(),
                ];

                $elementosPorMaquina[$maquina->id] = $elementos;
            }

            // 3. NUEVO: Agrupar máquinas por tipo para balanceo
            // Las cortadoras manuales pueden redistribuir a cortadoras-dobladoras si están sobrecargadas
            $gruposTipos = [
                'cortadora_dobladora' => [
                    'tipos_principales' => ['cortadora_dobladora', 'cortadora dobladora'],
                    'tipos_compatibles' => ['cortadora_manual', 'cortadora manual'] // Pueden recibir de manuales
                ],
                'estribadora' => [
                    'tipos_principales' => ['estribadora'],
                    'tipos_compatibles' => []
                ],
            ];

            Log::info('⚖️ BALANCEO: Cargas calculadas (por tipo)', [
                'detalle_cargas' => collect($cargasMaquinas)->map(fn($c) => [
                    'nombre' => $c['maquina']->nombre,
                    'tipo' => $c['maquina']->tipo,
                    'horas' => $c['tiempo_horas'],
                    'elementos' => $c['cantidad_elementos']
                ])->values()
            ]);

            // 5. Sugerir movimientos para balancear
            $elementosAMover = [];
            $umbralDesbalance = 0.10; // 10% de tolerancia

            foreach ($gruposTipos as $nombreGrupo => $configuracion) {
                $tiposPrincipales = $configuracion['tipos_principales'];
                $tiposCompatibles = $configuracion['tipos_compatibles'];
                $todosLosTipos = array_merge($tiposPrincipales, $tiposCompatibles);

                // Filtrar máquinas de este grupo (principales + compatibles)
                $maquinasGrupo = collect($cargasMaquinas)->filter(function($carga) use ($todosLosTipos) {
                    return in_array($carga['maquina']->tipo, $todosLosTipos);
                });

                if ($maquinasGrupo->count() < 2) {
                    Log::info("⚖️ BALANCEO: Grupo '{$nombreGrupo}' tiene menos de 2 máquinas, omitiendo");
                    continue;
                }

                // Calcular promedio SOLO de este grupo
                $tiempoTotalGrupo = $maquinasGrupo->sum('tiempo_segundos');
                $tiempoPromedioGrupo = $tiempoTotalGrupo / $maquinasGrupo->count();
                $umbralGrupo = $tiempoPromedioGrupo * $umbralDesbalance;

                Log::info("⚖️ BALANCEO: Analizando grupo '{$nombreGrupo}'", [
                    'maquinas' => $maquinasGrupo->count(),
                    'tiempo_promedio' => round($tiempoPromedioGrupo / 3600, 2) . 'h',
                    'umbral' => round($umbralGrupo / 3600, 2) . 'h'
                ]);

                // Identificar sobrecargadas y subcargadas EN ESTE GRUPO
                $sobrecargadasGrupo = $maquinasGrupo
                    ->filter(fn($carga) => $carga['tiempo_segundos'] > ($tiempoPromedioGrupo + $umbralGrupo))
                    ->sortByDesc('tiempo_segundos');

                $subcargadasGrupo = $maquinasGrupo
                    ->filter(fn($carga) => $carga['tiempo_segundos'] < ($tiempoPromedioGrupo + $umbralGrupo))
                    ->sortBy('tiempo_segundos');

                Log::info("⚖️ BALANCEO: Desbalance en grupo '{$nombreGrupo}'", [
                    'sobrecargadas' => $sobrecargadasGrupo->map(fn($c) => [
                        'nombre' => $c['maquina']->nombre,
                        'horas' => $c['tiempo_horas']
                    ])->values(),
                    'subcargadas' => $subcargadasGrupo->map(fn($c) => [
                        'nombre' => $c['maquina']->nombre,
                        'horas' => $c['tiempo_horas']
                    ])->values()
                ]);

                // Procesar cada máquina sobrecargada DEL GRUPO
                foreach ($sobrecargadasGrupo as $idSobrecargada => $cargaSobrecargada) {
                    $cargaActualMaquina = $cargaSobrecargada['tiempo_segundos'];

                    if ($cargaActualMaquina <= $tiempoPromedioGrupo) continue;

                    $elementos = $elementosPorMaquina[$idSobrecargada]
                        ->sortByDesc('tiempo_fabricacion'); // Empezar con los más grandes

                    foreach ($elementos as $elemento) {
                        // Continuar hasta que la carga esté cerca del promedio del grupo (permitir hasta 20% más)
                        if ($cargaActualMaquina <= ($tiempoPromedioGrupo * 1.2)) break;

                        // Buscar máquina compatible EN EL MISMO GRUPO
                        // Si es cortadora manual, puede mover a cortadoras-dobladoras (principales)
                        // Si es cortadora-dobladora, NO puede mover a manuales
                        $tipoMaquinaOrigen = $cargaSobrecargada['maquina']->tipo;
                        $esManual = in_array($tipoMaquinaOrigen, ['cortadora_manual', 'cortadora manual']);

                        $maquinasDelGrupo = $maquinas->filter(function($maq) use ($todosLosTipos, $tiposPrincipales, $esManual) {
                            // Si origen es manual, solo puede mover a principales (cortadoras-dobladoras)
                            if ($esManual) {
                                return in_array($maq->tipo, $tiposPrincipales);
                            }
                            // Si origen es principal, solo puede mover dentro de principales
                            return in_array($maq->tipo, $todosLosTipos);
                        });

                        $maquinasCompatibles = $this->encontrarMaquinasCompatiblesParaBalanceo(
                            $elemento,
                            $maquinasDelGrupo,
                            $idSobrecargada,
                            $cargasMaquinas,
                            $tiempoPromedioGrupo
                        );

                        \Log::info("⚖️ BALANCEO: Evaluando elemento", [
                            'elemento_id' => $elemento->id,
                            'codigo' => $elemento->codigo,
                            'tiempo_horas' => round($elemento->tiempo_fabricacion / 3600, 2),
                            'maquinas_compatibles_iniciales' => count($maquinasCompatibles),
                            'grupo' => $nombreGrupo
                        ]);

                        if (!empty($maquinasCompatibles)) {
                            // Filtrar máquinas que no se sobrecargarían con este elemento
                            // RELAJAMOS los límites: permitir hasta 100% por encima del promedio del grupo
                            $limiteMaximo = $tiempoPromedioGrupo * 2.0; // Hasta el doble del promedio del grupo

                            $maquinasCompatibles = array_filter($maquinasCompatibles, function($maq) use ($elemento, $tiempoPromedioGrupo, $limiteMaximo) {
                                $nuevaCarga = $maq['carga_actual'] + $elemento->tiempo_fabricacion;

                                // Solo verificar que no exceda el doble del promedio del grupo
                                $pasa = $nuevaCarga <= $limiteMaximo;

                            if (!$pasa) {
                                \Log::info("⚖️ BALANCEO: Máquina rechazada por límite", [
                                    'maquina' => $maq['nombre'],
                                    'carga_actual_h' => round($maq['carga_actual'] / 3600, 2),
                                    'nueva_carga_h' => round($nuevaCarga / 3600, 2),
                                    'limite_h' => round($limiteMaximo / 3600, 2)
                                ]);
                            }

                            return $pasa;
                        });

                        if (empty($maquinasCompatibles)) {
                            \Log::info("⚖️ BALANCEO: Ninguna máquina compatible después de filtrar");
                            continue;
                        }

                            // Ordenar por menor carga actual (prioridad simple)
                            usort($maquinasCompatibles, function($a, $b) use ($elemento, $tiempoPromedioGrupo) {
                                // Prioridad: Menor desviación después de agregar
                                $desviacionA = abs(($a['carga_actual'] + $elemento->tiempo_fabricacion) - $tiempoPromedioGrupo);
                                $desviacionB = abs(($b['carga_actual'] + $elemento->tiempo_fabricacion) - $tiempoPromedioGrupo);

                                return $desviacionA <=> $desviacionB;
                            });

                            $mejorMaquina = $maquinasCompatibles[0];

                            // Verificar que el movimiento realmente mejora el balance DEL GRUPO
                            $desviacionActual = abs($cargaSobrecargada['tiempo_segundos'] - $tiempoPromedioGrupo) +
                                              abs($cargasMaquinas[$mejorMaquina['id']]['tiempo_segundos'] - $tiempoPromedioGrupo);

                            $nuevaDesviacionOrigen = abs(($cargaSobrecargada['tiempo_segundos'] - $elemento->tiempo_fabricacion) - $tiempoPromedioGrupo);
                            $nuevaDesviacionDestino = abs(($cargasMaquinas[$mejorMaquina['id']]['tiempo_segundos'] + $elemento->tiempo_fabricacion) - $tiempoPromedioGrupo);
                            $nuevaDesviacion = $nuevaDesviacionOrigen + $nuevaDesviacionDestino;

                            \Log::info("⚖️ BALANCEO: Verificando mejora", [
                                'elemento' => $elemento->codigo,
                                'origen' => $cargaSobrecargada['maquina']->nombre,
                                'destino' => $mejorMaquina['nombre'],
                                'desviacion_actual' => round($desviacionActual / 3600, 2) . 'h',
                                'nueva_desviacion' => round($nuevaDesviacion / 3600, 2) . 'h',
                                'mejora' => $nuevaDesviacion < $desviacionActual ? 'SÍ' : 'NO'
                            ]);

                            // RELAJADO: Aceptar si mejora aunque sea mínimamente, o si reduce la carga de la sobrecargada significativamente
                            $reduceCargaSobrecargada = $cargaSobrecargada['tiempo_segundos'] > ($tiempoPromedioGrupo * 1.3);

                            if ($nuevaDesviacion >= $desviacionActual && !$reduceCargaSobrecargada) {
                                \Log::info("⚖️ BALANCEO: Movimiento rechazado - no mejora el balance");
                                continue;
                            }

                        $elementosAMover[] = [
                            'elemento_id' => $elemento->id,
                            'codigo' => $elemento->codigo,
                            'marca' => $elemento->marca,
                            'diametro' => $elemento->diametro,
                            'peso' => $elemento->peso,
                            'tiempo_fabricacion' => $elemento->tiempo_fabricacion,
                            'tiempo_horas' => round($elemento->tiempo_fabricacion / 3600, 2),
                            'planilla_id' => $elemento->planilla_id,
                            'planilla_codigo' => optional($elemento->planilla)->codigo_limpio,
                            'maquina_actual_id' => $idSobrecargada,
                            'maquina_actual_nombre' => $cargaSobrecargada['maquina']->nombre,
                            'maquina_nueva_id' => $mejorMaquina['id'],
                            'maquina_nueva_nombre' => $mejorMaquina['nombre'],
                            'razon' => "Balancear: {$cargaSobrecargada['maquina']->nombre} ({$cargaSobrecargada['tiempo_horas']}h) → {$mejorMaquina['nombre']} ({$mejorMaquina['carga_horas']}h)",
                        ];

                        // Actualizar cargas simuladas
                        $cargasMaquinas[$idSobrecargada]['tiempo_segundos'] -= $elemento->tiempo_fabricacion;
                        $cargasMaquinas[$idSobrecargada]['tiempo_horas'] = round($cargasMaquinas[$idSobrecargada]['tiempo_segundos'] / 3600, 2);

                        $cargasMaquinas[$mejorMaquina['id']]['tiempo_segundos'] += $elemento->tiempo_fabricacion;
                        $cargasMaquinas[$mejorMaquina['id']]['tiempo_horas'] = round($cargasMaquinas[$mejorMaquina['id']]['tiempo_segundos'] / 3600, 2);

                        // Actualizar la carga actual de la máquina sobrecargada para el próximo ciclo
                        $cargaActualMaquina -= $elemento->tiempo_fabricacion;

                            \Log::info("⚖️ BALANCEO: Elemento agregado para mover", [
                                'elemento' => $elemento->codigo,
                                'origen' => $cargaSobrecargada['maquina']->nombre,
                                'destino' => $mejorMaquina['nombre'],
                                'nueva_carga_origen_h' => round($cargaActualMaquina / 3600, 2),
                                'nueva_carga_destino_h' => round($cargasMaquinas[$mejorMaquina['id']]['tiempo_segundos'] / 3600, 2)
                            ]);
                        }
                    }
                }
            } // Fin del foreach de grupos

            // 6. Preparar resumen
            $resumenMaquinas = collect($cargasMaquinas)->map(function($carga) {
                return [
                    'id' => $carga['maquina']->id,
                    'nombre' => $carga['maquina']->nombre,
                    'tipo' => $carga['maquina']->tipo,
                    'tiempo_horas' => $carga['tiempo_horas'],
                    'cantidad_elementos' => $carga['cantidad_elementos'],
                ];
            })->values();

            Log::info('⚖️ BALANCEO: Análisis completado', [
                'elementos_a_mover' => count($elementosAMover),
                'maquinas_analizadas' => $maquinas->count(),
            ]);

            // Calcular promedio global solo para el resumen
            $tiempoTotalGlobal = collect($cargasMaquinas)->sum('tiempo_segundos');
            $tiempoPromedioGlobal = $maquinas->count() > 0 ? $tiempoTotalGlobal / $maquinas->count() : 0;

            return response()->json([
                'elementos' => $elementosAMover,
                'resumen_original' => $resumenMaquinas,
                'tiempo_promedio_horas' => round($tiempoPromedioGlobal / 3600, 2),
                'total_elementos' => count($elementosAMover),
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error en balancearCargaAnalisis:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error al analizar balanceo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Encontrar máquinas compatibles para balanceo
     */
    private function encontrarMaquinasCompatiblesParaBalanceo($elemento, $maquinas, $maquinaActualId, $cargasActuales, $tiempoPromedio)
    {
        $compatibles = [];

        foreach ($maquinas as $maquina) {
            if ($maquina->id == $maquinaActualId) continue;

            // Verificar compatibilidad básica
            $esCompatible = $this->verificarCompatibilidadBasica($elemento, $maquina);

            if ($esCompatible) {
                $cargaActual = $cargasActuales[$maquina->id]['tiempo_segundos'];
                $nuevaCarga = $cargaActual + $elemento->tiempo_fabricacion;
                $diferenciaPromedio = abs($nuevaCarga - $tiempoPromedio);

                $compatibles[] = [
                    'id' => $maquina->id,
                    'nombre' => $maquina->nombre,
                    'carga_actual' => $cargaActual,
                    'carga_horas' => round($cargaActual / 3600, 2),
                    'nueva_carga' => $nuevaCarga,
                    'diferencia_promedio' => $diferenciaPromedio,
                ];
            }
        }

        // Ordenar por menor diferencia con el promedio después de agregar
        usort($compatibles, fn($a, $b) => $a['diferencia_promedio'] <=> $b['diferencia_promedio']);

        return $compatibles;
    }

    /**
     * Verificar compatibilidad básica entre elemento y máquina
     * (El tipo ya se verifica al filtrar por grupos)
     */
    private function verificarCompatibilidadBasica($elemento, $maquina)
    {
        // Verificar diámetro mínimo
        if ($maquina->diametro_minimo && $elemento->diametro < $maquina->diametro_minimo) {
            return false;
        }

        // Verificar diámetro máximo
        if ($maquina->diametro_maximo && $elemento->diametro > $maquina->diametro_maximo) {
            return false;
        }

        return true;
    }

    /**
     * Aplicar el balanceo de carga sugerido
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function aplicarBalanceoCarga(Request $request)
    {
        try {
            $movimientos = $request->input('movimientos', []);
            $incluirFabricando = $request->boolean('incluir_fabricando', false);

            if (empty($movimientos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay movimientos para aplicar'
                ], 400);
            }

            Log::info('⚖️ BALANCEO: Aplicando redistribución', [
                'total_movimientos' => count($movimientos)
            ]);

            DB::beginTransaction();

            // Crear snapshot antes de la operación
            $this->crearSnapshotProduccion('balancear_carga');

            // Si no incluir fabricando, obtener IDs de planillas en posición 1 y fabricando
            $planillasExcluidas = [];
            if (!$incluirFabricando) {
                $planillasExcluidas = OrdenPlanilla::where('posicion', 1)
                    ->whereHas('planilla', fn($q) => $q->where('estado', 'fabricando'))
                    ->pluck('planilla_id')
                    ->toArray();
            }

            $procesados = 0;
            $omitidos = 0;
            $errores = [];
            $planillasAfectadas = [];

            foreach ($movimientos as $mov) {
                try {
                    $elemento = Elemento::find($mov['elemento_id']);

                    if (!$elemento) {
                        $errores[] = "Elemento {$mov['elemento_id']} no encontrado";
                        continue;
                    }

                    // Saltar elementos de planillas en posición 1 y fabricando
                    if (in_array($elemento->planilla_id, $planillasExcluidas)) {
                        $omitidos++;
                        continue;
                    }

                    // Guardar máquina anterior
                    $maquinaAnterior = $elemento->maquina_id;
                    $planillaId = $elemento->planilla_id;

                    // 1. Buscar o crear OrdenPlanilla en la máquina destino
                    $maxPosicion = OrdenPlanilla::where('maquina_id', $mov['maquina_nueva_id'])->max('posicion');

                    $ordenPlanillaDestino = OrdenPlanilla::firstOrCreate([
                        'planilla_id' => $planillaId,
                        'maquina_id' => $mov['maquina_nueva_id']
                    ], [
                        'posicion' => ($maxPosicion ?? 0) + 1
                    ]);

                    // 2. Actualizar elemento con nueva máquina y orden_planilla_id
                    $nuevaMaquinaId = (int) $mov['maquina_nueva_id'];
                    $elemento->maquina_id = $nuevaMaquinaId;
                    $elemento->orden_planilla_id = $ordenPlanillaDestino->id;
                    $elemento->save();

                    // 🏷️ Reubicar subetiquetas usando SubEtiquetaService
                    /** @var SubEtiquetaService $subEtiquetaService */
                    $subEtiquetaService = app(SubEtiquetaService::class);
                    $subEtiquetaService->reubicarParaProduccion($elemento, $nuevaMaquinaId);

                    Log::info('⚖️ BALANCEO: Elemento movido', [
                        'elemento' => $elemento->codigo,
                        'planilla' => $planillaId,
                        'maquina_origen' => $maquinaAnterior,
                        'maquina_destino' => $nuevaMaquinaId,
                        'orden_planilla_id' => $ordenPlanillaDestino->id
                    ]);

                    // 3. Si la OrdenPlanilla origen quedó vacía, eliminarla
                    if ($maquinaAnterior) {
                        $ordenPlanillaOrigen = OrdenPlanilla::where('planilla_id', $planillaId)
                            ->where('maquina_id', $maquinaAnterior)
                            ->first();

                        if ($ordenPlanillaOrigen) {
                            $elementosRestantes = Elemento::where('orden_planilla_id', $ordenPlanillaOrigen->id)
                                ->whereNotIn('estado', ['completado', 'fabricado'])
                                ->count();

                            if ($elementosRestantes == 0) {
                                $ordenPlanillaOrigen->delete();
                                Log::info('⚖️ BALANCEO: OrdenPlanilla vacía eliminada', [
                                    'orden_planilla_id' => $ordenPlanillaOrigen->id,
                                    'maquina' => $maquinaAnterior
                                ]);
                            }
                        }
                    }

                    $procesados++;

                    // Registrar planilla afectada
                    if ($planillaId && !in_array($planillaId, $planillasAfectadas)) {
                        $planillasAfectadas[] = $planillaId;
                    }

                } catch (\Exception $e) {
                    $errores[] = "Error moviendo elemento {$mov['elemento_id']}: " . $e->getMessage();
                    Log::error('Error moviendo elemento', [
                        'elemento_id' => $mov['elemento_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Marcar planillas afectadas como no revisadas
            if (!empty($planillasAfectadas)) {
                Planilla::whereIn('id', $planillasAfectadas)
                    ->update(['revisada' => 0]);

                Log::info('📋 Planillas marcadas como no revisadas (balancear)', [
                    'planillas_ids' => $planillasAfectadas,
                ]);
            }

            DB::commit();

            Log::info('⚖️ BALANCEO: Redistribución completada', [
                'procesados' => $procesados,
                'omitidos' => $omitidos,
                'errores' => count($errores)
            ]);

            $mensaje = "Balanceo aplicado: $procesados elementos redistribuidos";
            if ($omitidos > 0) {
                $mensaje .= " ({$omitidos} omitidos por estar fabricando)";
            }

            return response()->json([
                'success' => true,
                'procesados' => $procesados,
                'omitidos' => $omitidos,
                'total' => count($movimientos),
                'errores' => $errores,
                'message' => $mensaje
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error en aplicarBalanceoCarga:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al aplicar balanceo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar orden de planilla en máquina
     */
    private function actualizarOrdenPlanilla($planillaId, $maquinaId, $maquinaAnteriorId = null)
    {
        // Verificar si ya existe orden para esta planilla en la nueva máquina
        $ordenExistente = OrdenPlanilla::where('planilla_id', $planillaId)
            ->where('maquina_id', $maquinaId)
            ->first();

        if (!$ordenExistente) {
            // Crear nueva orden al final de la cola
            $maxPosicion = OrdenPlanilla::where('maquina_id', $maquinaId)->max('posicion') ?? 0;

            OrdenPlanilla::create([
                'planilla_id' => $planillaId,
                'maquina_id' => $maquinaId,
                'posicion' => $maxPosicion + 1,
            ]);
        }

        // Si había máquina anterior, verificar si aún tiene elementos
        if ($maquinaAnteriorId) {
            $elementosRestantes = Elemento::where('planilla_id', $planillaId)
                ->where('maquina_id', $maquinaAnteriorId)
                ->count();

            if ($elementosRestantes == 0) {
                // Eliminar orden de la máquina anterior si ya no tiene elementos
                OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $maquinaAnteriorId)
                    ->delete();
            }
        }
    }

    /**
     * Encontrar máquinas compatibles con un elemento (versión simple)
     *
     * @param \App\Models\Elemento $elemento
     * @param \Illuminate\Support\Collection $maquinas
     * @param int $maquinaActualId
     * @return array
     */
    private function encontrarMaquinasCompatiblesSimple($elemento, $maquinas, $maquinaActualId)
    {
        $compatibles = [];

        foreach ($maquinas as $maquina) {
            // No incluir la máquina actual
            if ($maquina->id == $maquinaActualId) continue;

            // ❌ Excluir soldadoras y ensambladoras
            if (in_array($maquina->tipo, ['soldadora', 'ensambladora'])) {
                continue;
            }

            // Verificar compatibilidad de tipo de material
            if ($maquina->tipo_material && $elemento->diametro) {
                // Verificar si el tipo de material es compatible
                $maquinaActual = $maquinas->get($maquinaActualId);
                if ($maquinaActual && $maquinaActual->tipo_material !== $maquina->tipo_material) {
                    continue;
                }

                // Verificar rango de diámetro
                if ($maquina->diametro_min && $elemento->diametro < $maquina->diametro_min) {
                    continue;
                }

                if ($maquina->diametro_max && $elemento->diametro > $maquina->diametro_max) {
                    continue;
                }
            }

            $compatibles[] = [
                'id' => $maquina->id,
                'nombre' => $maquina->nombre,
                'carga_horas' => 0, // No calculamos carga aquí
            ];
        }

        return $compatibles;
    }

    /**
     * Encontrar máquinas compatibles con un elemento
     *
     * @param \App\Models\Elemento $elemento
     * @param \Illuminate\Support\Collection $maquinas
     * @param array $cargaMaquinas
     * @param int $maquinaActualId
     * @return array
     */
    private function encontrarMaquinasCompatibles($elemento, $maquinas, $cargaMaquinas, $maquinaActualId)
    {
        $compatibles = [];

        foreach ($maquinas as $maquina) {
            // No incluir la máquina actual
            if ($maquina->id == $maquinaActualId) continue;

            // ❌ Excluir soldadoras y ensambladoras
            if (in_array($maquina->tipo, ['soldadora', 'ensambladora'])) {
                continue;
            }

            // Verificar compatibilidad de tipo de material
            if ($maquina->tipo_material && $elemento->diametro) {
                // Verificar si el tipo de material es compatible
                $maquinaActual = $maquinas->get($maquinaActualId);
                if ($maquinaActual && $maquinaActual->tipo_material !== $maquina->tipo_material) {
                    continue;
                }

                // Verificar rango de diámetro
                if ($maquina->diametro_min && $elemento->diametro < $maquina->diametro_min) {
                    continue;
                }

                if ($maquina->diametro_max && $elemento->diametro > $maquina->diametro_max) {
                    continue;
                }
            }

            // Calcular carga relativa
            $carga = $cargaMaquinas[$maquina->id]['tiempo_total_segundos'] ?? 0;
            $cargaHoras = round($carga / 3600, 1);

            $compatibles[] = [
                'id' => $maquina->id,
                'nombre' => $maquina->nombre,
                'carga_horas' => $cargaHoras,
            ];
        }

        return $compatibles;
    }

    /**
     * Aplicar redistribución optimizada
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function optimizarAplicar(Request $request)
    {
        $request->validate([
            'redistribuciones' => 'required|array',
            'redistribuciones.*.elemento_id' => 'required|integer|exists:elementos,id',
            'redistribuciones.*.nueva_maquina_id' => 'required|integer|exists:maquinas,id',
            'incluir_fabricando' => 'boolean',
        ]);

        $incluirFabricando = $request->boolean('incluir_fabricando', false);

        DB::beginTransaction();

        try {
            // Crear snapshot antes de la operación
            $this->crearSnapshotProduccion('optimizar_planillas');

            // Si no incluir fabricando, obtener IDs de planillas en posición 1 y fabricando
            $planillasExcluidas = [];
            if (!$incluirFabricando) {
                $planillasExcluidas = OrdenPlanilla::where('posicion', 1)
                    ->whereHas('planilla', fn($q) => $q->where('estado', 'fabricando'))
                    ->pluck('planilla_id')
                    ->toArray();
            }

            $redistribuciones = $request->input('redistribuciones');
            $elementosMovidos = 0;
            $elementosOmitidos = 0;

            // Rastrear planillas afectadas por máquina
            $planillasAfectadas = []; // [planilla_id => ['maquina_anterior' => id, 'maquina_nueva' => id]]

            /** @var SubEtiquetaService $subEtiquetaService */
            $subEtiquetaService = app(SubEtiquetaService::class);

            foreach ($redistribuciones as $redistribucion) {
                $elemento = Elemento::find($redistribucion['elemento_id']);
                $nuevaMaquinaId = (int) $redistribucion['nueva_maquina_id'];

                if (!$elemento) continue;

                $maquinaAnterior = $elemento->maquina_id;
                $planillaId = $elemento->planilla_id;

                // Saltar elementos de planillas en posición 1 y fabricando
                if (in_array($planillaId, $planillasExcluidas)) {
                    $elementosOmitidos++;
                    continue;
                }

                // Actualizar máquina del elemento
                $elemento->maquina_id = $nuevaMaquinaId;
                $elemento->save();

                // 🏷️ Reubicar subetiquetas usando SubEtiquetaService
                $subEtiquetaService->reubicarParaProduccion($elemento, $nuevaMaquinaId);

                $elementosMovidos++;

                // Registrar planilla afectada
                if (!isset($planillasAfectadas[$planillaId])) {
                    $planillasAfectadas[$planillaId] = [];
                }

                if (!isset($planillasAfectadas[$planillaId][$maquinaAnterior])) {
                    $planillasAfectadas[$planillaId][$maquinaAnterior] = [];
                }

                $planillasAfectadas[$planillaId][$maquinaAnterior][] = $nuevaMaquinaId;

                Log::info('Elemento redistribuido por optimización', [
                    'elemento_id' => $elemento->id,
                    'codigo' => $elemento->codigo,
                    'planilla_id' => $planillaId,
                    'maquina_anterior' => $maquinaAnterior,
                    'nueva_maquina' => $nuevaMaquinaId,
                    'user_id' => auth()->id(),
                ]);
            }

            // 🔄 Actualizar orden_planillas
            foreach ($planillasAfectadas as $planillaId => $maquinas) {
                foreach ($maquinas as $maquinaAnterior => $maquinasNuevas) {
                    // Verificar si quedan elementos en la máquina anterior
                    $elementosRestantes = Elemento::where('planilla_id', $planillaId)
                        ->where('maquina_id', $maquinaAnterior)
                        ->count();

                    if ($elementosRestantes === 0) {
                        // 🗑️ No quedan elementos, borrar de orden_planillas
                        DB::table('orden_planillas')
                            ->where('planilla_id', $planillaId)
                            ->where('maquina_id', $maquinaAnterior)
                            ->delete();

                        Log::info('✅ Registro eliminado de orden_planillas (sin elementos)', [
                            'planilla_id' => $planillaId,
                            'maquina_id' => $maquinaAnterior,
                        ]);
                    }

                    // 🆕 Para cada máquina nueva, verificar/crear registro
                    foreach (array_unique($maquinasNuevas) as $maquinaNueva) {
                        $existeOrden = DB::table('orden_planillas')
                            ->where('planilla_id', $planillaId)
                            ->where('maquina_id', $maquinaNueva)
                            ->exists();

                        if (!$existeOrden) {
                            // Obtener la última posición en esta máquina
                            $ultimaPosicion = DB::table('orden_planillas')
                                ->where('maquina_id', $maquinaNueva)
                                ->max('posicion') ?? 0;

                            // Insertar nuevo registro al final de la cola
                            DB::table('orden_planillas')->insert([
                                'planilla_id' => $planillaId,
                                'maquina_id' => $maquinaNueva,
                                'posicion' => $ultimaPosicion + 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            Log::info('✅ Registro creado en orden_planillas', [
                                'planilla_id' => $planillaId,
                                'maquina_id' => $maquinaNueva,
                                'posicion' => $ultimaPosicion + 1,
                            ]);
                        } else {
                            Log::info('ℹ️ Registro ya existe en orden_planillas', [
                                'planilla_id' => $planillaId,
                                'maquina_id' => $maquinaNueva,
                            ]);
                        }
                    }
                }
            }

            // Marcar planillas afectadas como no revisadas
            if (!empty($planillasAfectadas)) {
                Planilla::whereIn('id', array_keys($planillasAfectadas))
                    ->update(['revisada' => 0]);

                Log::info('📋 Planillas marcadas como no revisadas (optimizar)', [
                    'planillas_ids' => array_keys($planillasAfectadas),
                ]);
            }

            DB::commit();

            $mensaje = "Se redistribuyeron {$elementosMovidos} elementos";
            if ($elementosOmitidos > 0) {
                $mensaje .= " ({$elementosOmitidos} omitidos por estar fabricando)";
            }

            return response()->json([
                'success' => true,
                'elementos_movidos' => $elementosMovidos,
                'elementos_omitidos' => $elementosOmitidos,
                'planillas_actualizadas' => count($planillasAfectadas),
                'message' => $mensaje
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error en optimizarAplicar:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error al aplicar optimización',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear snapshot del estado actual de producción
     */
    private function crearSnapshotProduccion(string $tipoOperacion): SnapshotProduccion
    {
        // Capturar estado actual de orden_planillas
        $ordenPlanillasData = DB::table('orden_planillas')
            ->select('id', 'planilla_id', 'maquina_id', 'posicion')
            ->get()
            ->toArray();

        // Capturar estado actual de elementos (solo campos relevantes para producción)
        $elementosData = Elemento::whereNotIn('estado', ['completado', 'fabricado'])
            ->select('id', 'maquina_id', 'orden_planilla_id', 'etiqueta_id', 'etiqueta_sub_id')
            ->get()
            ->toArray();

        // Crear snapshot
        $snapshot = SnapshotProduccion::create([
            'tipo_operacion' => $tipoOperacion,
            'user_id' => auth()->id(),
            'orden_planillas_data' => $ordenPlanillasData,
            'elementos_data' => $elementosData,
        ]);

        Log::info("📸 Snapshot creado para operación: {$tipoOperacion}", [
            'snapshot_id' => $snapshot->id,
            'orden_planillas_count' => count($ordenPlanillasData),
            'elementos_count' => count($elementosData),
        ]);

        return $snapshot;
    }

    /**
     * Obtener el último snapshot disponible
     */
    public function obtenerUltimoSnapshot()
    {
        $snapshot = SnapshotProduccion::latest()->first();

        if (!$snapshot) {
            return response()->json([
                'success' => false,
                'message' => 'No hay cambios para deshacer'
            ]);
        }

        return response()->json([
            'success' => true,
            'snapshot' => [
                'id' => $snapshot->id,
                'tipo_operacion' => $snapshot->tipo_operacion,
                'created_at' => $snapshot->created_at->format('d/m/Y H:i:s'),
                'user' => $snapshot->user?->name ?? 'Sistema',
            ]
        ]);
    }

    /**
     * Restaurar el último snapshot (deshacer última operación)
     */
    public function restaurarSnapshot(Request $request)
    {
        $snapshotId = $request->input('snapshot_id');

        $snapshot = $snapshotId
            ? SnapshotProduccion::find($snapshotId)
            : SnapshotProduccion::latest()->first();

        if (!$snapshot) {
            return response()->json([
                'success' => false,
                'message' => 'No hay snapshot para restaurar'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $ordenPlanillasData = $snapshot->orden_planillas_data;
            $elementosData = $snapshot->elementos_data;

            // 1. Restaurar orden_planillas
            // Primero eliminamos todos los registros actuales
            DB::table('orden_planillas')->delete();

            // Luego insertamos los del snapshot
            foreach ($ordenPlanillasData as $op) {
                DB::table('orden_planillas')->insert([
                    'id' => $op['id'],
                    'planilla_id' => $op['planilla_id'],
                    'maquina_id' => $op['maquina_id'],
                    'posicion' => $op['posicion'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 2. Restaurar elementos
            foreach ($elementosData as $elem) {
                Elemento::where('id', $elem['id'])->update([
                    'maquina_id' => $elem['maquina_id'],
                    'orden_planilla_id' => $elem['orden_planilla_id'],
                    'etiqueta_id' => $elem['etiqueta_id'],
                    'etiqueta_sub_id' => $elem['etiqueta_sub_id'],
                ]);
            }

            DB::commit();

            // Eliminar el snapshot usado
            $tipoOperacion = $snapshot->tipo_operacion;
            $snapshot->delete();

            Log::info("⏪ Snapshot restaurado: {$tipoOperacion}", [
                'snapshot_id' => $snapshot->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Operación '{$tipoOperacion}' deshecha correctamente"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al restaurar snapshot', [
                'snapshot_id' => $snapshot->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Priorizar obras - mover planillas de obras seleccionadas al principio de la cola
     */
    public function priorizarObras(Request $request)
    {
        $request->validate([
            'obras' => 'required|array|min:1|max:5',
            'obras.*' => 'required|integer|exists:obras,id',
            'incluir_fabricando' => 'boolean',
        ]);

        $obrasIds = $request->input('obras');
        $incluirFabricando = $request->boolean('incluir_fabricando', false);

        DB::beginTransaction();

        try {
            // Crear snapshot antes de la operación
            $this->crearSnapshotProduccion('priorizar_obras');

            $maquinas = Maquina::pluck('id');
            $cambiosRealizados = 0;
            $omitidos = 0;
            $planillasAfectadas = [];

            foreach ($maquinas as $maquinaId) {
                // Obtener todas las orden_planillas de esta máquina ordenadas por posición actual
                $ordenPlanillas = OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->with('planilla')
                    ->orderBy('posicion')
                    ->get();

                if ($ordenPlanillas->isEmpty()) continue;

                // Identificar la planilla en posición 1 si está fabricando (para no moverla)
                $planillaFabricandoPos1 = null;
                if (!$incluirFabricando) {
                    $primeraOp = $ordenPlanillas->first();
                    if ($primeraOp && $primeraOp->posicion === 1 && $primeraOp->planilla?->estado === 'fabricando') {
                        $planillaFabricandoPos1 = $primeraOp;
                    }
                }

                // Separar en priorizadas y no priorizadas (excluyendo la fabricando en pos 1)
                $priorizadas = collect();
                $noPriorizadas = collect();

                foreach ($ordenPlanillas as $op) {
                    // Si es la planilla fabricando en posición 1, no la movemos
                    if ($planillaFabricandoPos1 && $op->id === $planillaFabricandoPos1->id) {
                        continue;
                    }

                    $obraId = $op->planilla?->obra_id;

                    if ($obraId && in_array($obraId, $obrasIds)) {
                        // Guardar con prioridad según el orden en el array de obras
                        $prioridad = array_search($obraId, $obrasIds);
                        $priorizadas->push(['op' => $op, 'prioridad' => $prioridad]);
                    } else {
                        $noPriorizadas->push($op);
                    }
                }

                // Ordenar priorizadas por el orden de selección del usuario y extraer solo el objeto
                $priorizadas = $priorizadas->sortBy('prioridad')->pluck('op');

                // Combinar: primero las priorizadas, luego las demás
                $nuevoOrden = $priorizadas->merge($noPriorizadas);

                // Si hay planilla fabricando en pos 1, empezamos desde posición 2
                $posicionInicial = $planillaFabricandoPos1 ? 2 : 1;
                if ($planillaFabricandoPos1) {
                    $omitidos++;
                }

                // Actualizar posiciones
                $posicion = $posicionInicial;
                foreach ($nuevoOrden as $op) {
                    if ($op->posicion !== $posicion) {
                        $op->posicion = $posicion;
                        $op->save();
                        $cambiosRealizados++;

                        // Registrar planilla afectada
                        if ($op->planilla_id && !in_array($op->planilla_id, $planillasAfectadas)) {
                            $planillasAfectadas[] = $op->planilla_id;
                        }
                    }
                    $posicion++;
                }
            }

            // Marcar planillas afectadas como no revisadas
            if (!empty($planillasAfectadas)) {
                Planilla::whereIn('id', $planillasAfectadas)
                    ->update(['revisada' => 0]);

                Log::info('📋 Planillas marcadas como no revisadas (priorizar)', [
                    'planillas_ids' => $planillasAfectadas,
                ]);
            }

            DB::commit();

            Log::info('🎯 Obras priorizadas', [
                'obras_ids' => $obrasIds,
                'cambios' => $cambiosRealizados,
                'omitidos' => $omitidos,
                'user_id' => auth()->id(),
            ]);

            $mensaje = "Priorización completada. {$cambiosRealizados} cambios realizados.";
            if ($omitidos > 0) {
                $mensaje .= " ({$omitidos} planillas en fabricación no afectadas)";
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'cambios' => $cambiosRealizados,
                'omitidos' => $omitidos
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al priorizar obras', [
                'obras_ids' => $obrasIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al priorizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener obras con planillas activas para el modal de priorización
     * Agrupa por obra y fecha de entrega
     */
    public function obrasConPlanillasActivas()
    {
        // Obtener planillas activas (pendiente o fabricando) que estén en orden_planillas
        $planillas = Planilla::whereIn('estado', ['pendiente', 'fabricando'])
            ->whereHas('ordenProduccion')
            ->with(['obra:id,obra,cod_obra'])
            ->select('id', 'codigo', 'obra_id', 'fecha_estimada_entrega', 'estado')
            ->get();

        // Agrupar por obra_id y fecha_estimada_entrega
        $agrupaciones = [];

        foreach ($planillas as $planilla) {
            if (!$planilla->obra_id) continue;

            $fechaEntrega = $planilla->getRawOriginal('fecha_estimada_entrega')
                ? Carbon::parse($planilla->getRawOriginal('fecha_estimada_entrega'))->format('Y-m-d')
                : 'sin-fecha';

            $key = $planilla->obra_id . '_' . $fechaEntrega;

            if (!isset($agrupaciones[$key])) {
                $agrupaciones[$key] = [
                    'obra_id' => $planilla->obra_id,
                    'cod_obra' => $planilla->obra?->cod_obra ?? 'N/A',
                    'obra' => $planilla->obra?->obra ?? 'Sin obra',
                    'fecha_entrega' => $fechaEntrega,
                    'fecha_entrega_formatted' => $fechaEntrega !== 'sin-fecha'
                        ? Carbon::parse($fechaEntrega)->format('d/m/Y')
                        : 'Sin fecha',
                    'planillas_count' => 0,
                    'planillas_codigos' => [],
                ];
            }

            $agrupaciones[$key]['planillas_count']++;
            $agrupaciones[$key]['planillas_codigos'][] = $planilla->codigo;
        }

        // Ordenar por obra y luego por fecha de entrega
        $resultado = collect($agrupaciones)->sortBy([
            ['cod_obra', 'asc'],
            ['fecha_entrega', 'asc'],
        ])->values()->toArray();

        return response()->json($resultado);
    }

    /**
     * Obtener resumen del estado de producción
     * Muestra estadísticas y planillas con retraso agrupadas por cliente/obra/fecha
     */
    public function obtenerResumen()
    {
        try {
            // Estadísticas generales de planillas en producción
            $planillasEnProduccion = Planilla::whereIn('estado', ['pendiente', 'fabricando'])
                ->whereHas('ordenProduccion')
                ->with(['obra.cliente', 'ordenProduccion.maquina', 'elementos'])
                ->get();

            $planillasRevisadas = $planillasEnProduccion->where('revisada', 1)->count();
            $planillasNoRevisadas = $planillasEnProduccion->where('revisada', 0)->count();
            $totalPlanillas = $planillasEnProduccion->count();

            // Identificar planillas con retraso
            // Una planilla tiene retraso si su fecha_fin_programada > fecha_estimada_entrega
            $hoy = Carbon::today();
            $planillasConRetraso = [];

            foreach ($planillasEnProduccion as $planilla) {
                $fechaEntrega = $planilla->getRawOriginal('fecha_estimada_entrega');
                if (!$fechaEntrega) continue;

                $fechaEntregaCarbon = Carbon::parse($fechaEntrega);

                // Obtener la orden_planilla para saber la máquina y calcular fecha fin programada
                $ordenPlanilla = $planilla->ordenProduccion;
                if (!$ordenPlanilla) continue;

                // Calcular fecha fin programada basada en posición y tiempo estimado
                // Simplificación: usar fecha_entrega como referencia
                $fechaFinProgramada = null;

                // Si tenemos elementos con tiempos, calcular
                $tiempoTotalSegundos = $planilla->elementos
                    ->whereNotNull('tiempo_fabricacion')
                    ->sum('tiempo_fabricacion');

                if ($tiempoTotalSegundos > 0 && $ordenPlanilla->maquina_id) {
                    // Calcular basado en posición en cola usando la relación cargada
                    $maquina = $ordenPlanilla->maquina;
                    if ($maquina) {
                        // Simplificar: usar posición como estimación de días
                        $diasTrabajo = max(1, ceil($ordenPlanilla->posicion / 2));
                        $fechaFinProgramada = Carbon::today()->addWeekdays($diasTrabajo);
                    }
                }

                // Si no pudimos calcular, usar una estimación simple
                if (!$fechaFinProgramada) {
                    $fechaFinProgramada = Carbon::today()->addDays($ordenPlanilla->posicion);
                }

                // Verificar si hay retraso
                if ($fechaFinProgramada->gt($fechaEntregaCarbon)) {
                    $diasRetraso = $fechaFinProgramada->diffInDays($fechaEntregaCarbon);

                    $planillasConRetraso[] = [
                        'planilla_id' => $planilla->id,
                        'planilla_codigo' => $planilla->codigo,
                        'seccion' => $planilla->seccion ?? '',
                        'descripcion' => $planilla->descripcion ?? '',
                        'ensamblado' => $planilla->ensamblado ?? '',
                        'obra_id' => $planilla->obra_id,
                        'cliente_id' => $planilla->obra?->cliente_id,
                        'cliente_nombre' => $planilla->obra?->cliente?->empresa ?? 'Sin cliente',
                        'obra_codigo' => $planilla->obra?->cod_obra ?? 'N/A',
                        'obra_nombre' => $planilla->obra?->obra ?? 'Sin obra',
                        'fecha_entrega' => $fechaEntregaCarbon->format('d/m/Y'),
                        'fecha_entrega_raw' => $fechaEntregaCarbon->format('Y-m-d'),
                        'fin_programado' => $fechaFinProgramada->format('d/m/Y'),
                        'dias_retraso' => $diasRetraso,
                        'maquina_id' => $ordenPlanilla->maquina_id,
                        'maquina_codigo' => $ordenPlanilla->maquina->codigo ?? 'N/A',
                        'posicion' => $ordenPlanilla->posicion,
                    ];
                }
            }

            // Agrupar por cliente > obra > fecha_entrega
            $clientesConRetraso = [];

            foreach ($planillasConRetraso as $planilla) {
                $clienteId = $planilla['cliente_id'] ?? 0;
                $obraId = $planilla['obra_id'] ?? 0;
                $fechaEntrega = $planilla['fecha_entrega_raw'];

                // Inicializar cliente si no existe
                if (!isset($clientesConRetraso[$clienteId])) {
                    $clientesConRetraso[$clienteId] = [
                        'cliente_id' => $clienteId,
                        'cliente_nombre' => $planilla['cliente_nombre'],
                        'obras' => [],
                    ];
                }

                // Inicializar obra si no existe
                if (!isset($clientesConRetraso[$clienteId]['obras'][$obraId])) {
                    $clientesConRetraso[$clienteId]['obras'][$obraId] = [
                        'obra_id' => $obraId,
                        'obra_codigo' => $planilla['obra_codigo'],
                        'obra_nombre' => $planilla['obra_nombre'],
                        'fechas' => [],
                    ];
                }

                // Inicializar fecha si no existe
                if (!isset($clientesConRetraso[$clienteId]['obras'][$obraId]['fechas'][$fechaEntrega])) {
                    $clientesConRetraso[$clienteId]['obras'][$obraId]['fechas'][$fechaEntrega] = [
                        'fecha_entrega' => $planilla['fecha_entrega'],
                        'planillas' => [],
                    ];
                }

                // Agregar planilla
                $clientesConRetraso[$clienteId]['obras'][$obraId]['fechas'][$fechaEntrega]['planillas'][] = [
                    'planilla_id' => $planilla['planilla_id'],
                    'planilla_codigo' => $planilla['planilla_codigo'],
                    'seccion' => $planilla['seccion'],
                    'descripcion' => $planilla['descripcion'],
                    'ensamblado' => $planilla['ensamblado'],
                    'maquina_codigo' => $planilla['maquina_codigo'],
                    'fin_programado' => $planilla['fin_programado'],
                    'dias_retraso' => $planilla['dias_retraso'],
                ];
            }

            // Convertir a arrays indexados para JSON
            $clientesArray = [];
            foreach ($clientesConRetraso as $cliente) {
                $obrasArray = [];
                foreach ($cliente['obras'] as $obra) {
                    $fechasArray = array_values($obra['fechas']);
                    $obrasArray[] = [
                        'obra_id' => $obra['obra_id'],
                        'obra_codigo' => $obra['obra_codigo'],
                        'obra_nombre' => $obra['obra_nombre'],
                        'fechas' => $fechasArray,
                    ];
                }
                $clientesArray[] = [
                    'cliente_id' => $cliente['cliente_id'],
                    'cliente_nombre' => $cliente['cliente_nombre'],
                    'obras' => $obrasArray,
                ];
            }

            // Obtener resumen de máquinas
            $maquinas = Maquina::with(['ordenPlanillas.planilla.elementos'])->get();
            $maquinasResumen = [];

            foreach ($maquinas as $maquina) {
                $planillasEnCola = $maquina->ordenPlanillas->count();
                $kilosTotales = 0;
                $tiempoTotalSegundos = 0;

                foreach ($maquina->ordenPlanillas as $op) {
                    if ($op->planilla) {
                        $kilosTotales += $op->planilla->elementos->sum('peso') / 1000; // convertir a kg
                        $tiempoTotalSegundos += $op->planilla->elementos->sum('tiempo_fabricacion');
                    }
                }

                $horas = floor($tiempoTotalSegundos / 3600);
                $minutos = floor(($tiempoTotalSegundos % 3600) / 60);

                $maquinasResumen[] = [
                    'id' => $maquina->id,
                    'codigo' => $maquina->codigo,
                    'tipo' => $maquina->tipo ?? '',
                    'planillas_en_cola' => $planillasEnCola,
                    'kilos_totales' => round($kilosTotales, 2),
                    'kilos_formateado' => number_format($kilosTotales, 0, ',', '.') . ' kg',
                    'tiempo_total_segundos' => $tiempoTotalSegundos,
                    'tiempo_formateado' => $horas . 'h ' . $minutos . 'm',
                ];
            }

            return response()->json([
                'success' => true,
                'resumen' => [
                    'planillas_revisadas' => $planillasRevisadas,
                    'planillas_no_revisadas' => $planillasNoRevisadas,
                    'total_planillas' => $totalPlanillas,
                    'planillas_con_retraso' => count($planillasConRetraso),
                ],
                'clientes_con_retraso' => $clientesArray,
                'maquinas' => $maquinasResumen,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de producción', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen: ' . $e->getMessage()
            ], 500);
        }
    }
}
