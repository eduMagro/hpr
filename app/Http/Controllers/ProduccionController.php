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

    try {
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $valor)) {
            return Carbon::parse($valor);
        }

        $result = Carbon::createFromFormat(strlen($valor) === 19 ? 'd/m/Y H:i:s' : $format, $valor);

        // createFromFormat devuelve false si falla
        if ($result === false) {
            Log::warning('toCarbon: formato inválido', ['valor' => $valor, 'format' => $format]);
            return null;
        }

        return $result;
    } catch (\Throwable $e) {
        Log::warning('toCarbon: excepción', ['valor' => $valor, 'error' => $e->getMessage()]);
        return null;
    }
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

    /**
     * Calcula las horas de trabajo por máquina, día y turno
     * Reutiliza la lógica de generarEventosMaquinas para obtener la distribución real
     * Devuelve: ['maquina_id' => ['2025-01-15' => ['total' => 8, 'mañana' => 4, 'tarde' => 4, 'noche' => 0], ...], ...]
     */
    private function calcularCargaTrabajoPorDia(): array
    {
        // Obtener los eventos de máquinas (reutilizando la lógica existente)
        $eventosData = $this->obtenerEventosMaquinasParaCarga();

        if (empty($eventosData)) {
            return [];
        }

        // Definir rangos de turnos
        $turnos = [
            'mañana' => ['inicio' => 6, 'fin' => 14],
            'tarde' => ['inicio' => 14, 'fin' => 22],
            'noche' => ['inicio' => 22, 'fin' => 6], // cruza medianoche
        ];

        $resultado = [];

        foreach ($eventosData as $evento) {
            $maquinaId = $evento['resourceId'] ?? null;
            if (!$maquinaId) continue;

            $start = Carbon::parse($evento['start']);
            $end = Carbon::parse($evento['end']);

            // Formatear ID de máquina con padding
            $maquinaIdFormateado = str_pad($maquinaId, 3, '0', STR_PAD_LEFT);

            // Distribuir las horas del evento por día y turno
            $cursor = $start->copy();
            while ($cursor->lt($end)) {
                $hora = $cursor->hour;

                // Determinar turno y fecha correcta
                // El turno de noche (22:00-06:00) pertenece al día SIGUIENTE
                // Ej: domingo 22:00 - lunes 06:00 = turno de noche del LUNES
                $turnoActual = 'mañana';
                $fechaStr = $cursor->format('Y-m-d');

                if ($hora >= 14 && $hora < 22) {
                    $turnoActual = 'tarde';
                } elseif ($hora >= 22) {
                    // 22:00-23:59 → turno de noche del día SIGUIENTE
                    $turnoActual = 'noche';
                    $fechaStr = $cursor->copy()->addDay()->format('Y-m-d');
                } elseif ($hora < 6) {
                    // 00:00-05:59 → turno de noche del día ACTUAL
                    $turnoActual = 'noche';
                }

                // Inicializar estructura si no existe
                if (!isset($resultado[$maquinaIdFormateado])) {
                    $resultado[$maquinaIdFormateado] = [];
                }
                if (!isset($resultado[$maquinaIdFormateado][$fechaStr])) {
                    $resultado[$maquinaIdFormateado][$fechaStr] = [
                        'total' => 0,
                        'mañana' => 0,
                        'tarde' => 0,
                        'noche' => 0,
                        'planillas' => [],
                    ];
                }

                // Calcular cuánto tiempo queda en este turno
                $finTurno = match($turnoActual) {
                    'mañana' => $cursor->copy()->setTime(14, 0),
                    'tarde' => $cursor->copy()->setTime(22, 0),
                    'noche' => $hora >= 22
                        ? $cursor->copy()->addDay()->setTime(6, 0)
                        : $cursor->copy()->setTime(6, 0),
                };

                $finSegmento = $finTurno->lt($end) ? $finTurno : $end;
                $horasSegmento = $cursor->diffInMinutes($finSegmento) / 60;

                // Acumular horas
                $resultado[$maquinaIdFormateado][$fechaStr][$turnoActual] += round($horasSegmento, 1);
                $resultado[$maquinaIdFormateado][$fechaStr]['total'] += round($horasSegmento, 1);

                // Guardar planilla
                if (!empty($evento['extendedProps']['planilla_id'])) {
                    $resultado[$maquinaIdFormateado][$fechaStr]['planillas'][$evento['extendedProps']['planilla_id']] = true;
                }

                $cursor = $finSegmento->copy();
            }
        }

        // Convertir planillas a conteo
        foreach ($resultado as $maquinaId => &$dias) {
            foreach ($dias as $fecha => &$data) {
                $data['num_planillas'] = count($data['planillas']);
                unset($data['planillas']);
            }
        }

        return $resultado;
    }

    /**
     * Obtiene los eventos de máquinas para calcular carga (versión simplificada)
     */
    private function obtenerEventosMaquinasParaCarga(): array
    {
        $primerasGruas = Maquina::where('tipo', 'grua')
            ->whereNotNull('obra_id')
            ->selectRaw('MIN(id) as id')
            ->groupBy('obra_id')
            ->pluck('id');

        $maquinas = Maquina::where(function ($q) use ($primerasGruas) {
                $q->where('tipo', '<>', 'grua')
                  ->orWhereIn('id', $primerasGruas);
            })
            ->orderByRaw('CASE WHEN obra_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('obra_id')
            ->orderBy('tipo')
            ->get();

        $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina', 'maquina_2', 'maquina_3'])
            ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando']))
            ->get();

        $maquinaReal = function ($e) {
            $tipo1 = optional($e->maquina)->tipo;
            $tipo2 = optional($e->maquina_2)->tipo;

            if ($tipo1 === 'ensambladora') return $e->maquina_id_2;
            if ($tipo1 === 'soldadora') return $e->maquina_id_3 ?? $e->maquina_id;
            if ($tipo1 === 'dobladora_manual') return $e->maquina_id;
            if ($tipo2 === 'dobladora_manual') return $e->maquina_id_2;
            return $e->maquina_id;
        };

        $planillasAgrupadas = $elementos
            ->groupBy(function ($e) use ($maquinaReal) {
                return $e->planilla_id . '-' . $maquinaReal($e);
            })
            ->map(function ($grupo) use ($maquinaReal) {
                $primero = $grupo->first();
                return [
                    'planilla' => $primero->planilla,
                    'elementos' => $grupo,
                    'maquina_id' => $maquinaReal($primero),
                ];
            })
            ->filter(fn($data) => !is_null($data['maquina_id']));

        // Calcular colas
        $colasMaquinas = [];
        foreach ($maquinas as $m) {
            $colasMaquinas[$m->id] = Carbon::now();
        }

        // Obtener órdenes
        $ordenes = OrdenPlanilla::orderBy('posicion')
            ->get()
            ->groupBy('maquina_id')
            ->map(fn($ordenesMaquina) => $ordenesMaquina->map(fn($o) => [
                'id' => $o->id,
                'planilla_id' => $o->planilla_id,
                'posicion' => $o->posicion
            ])->all());

        // Generar eventos
        try {
            $eventos = $this->generarEventosMaquinas($planillasAgrupadas, $ordenes, $colasMaquinas);
            return $eventos instanceof Collection ? $eventos->toArray() : (array) $eventos;
        } catch (\Throwable $e) {
            Log::error('Error en obtenerEventosMaquinasParaCarga', ['msg' => $e->getMessage()]);
            return [];
        }
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
                    'orden' => $index,   // ✅ AHORA SÍ EXISTE
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
            'asignacionesTurnos.turno:id,nombre,hora_inicio,hora_fin',
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

                    $fechaStr = $fechaTurno->format('Y-m-d');

                    // Determinar si es turno nocturno (cruza medianoche)
                    $hIni = (int) substr($horaEntrada, 0, 2);
                    $hFin = (int) substr($horaSalida, 0, 2);
                    $esNocturno = $hFin < $hIni;

                    /**
                     * MAPEO VISUAL DE TURNOS PARA EL CALENDARIO
                     * ==========================================
                     * El turno de noche del LUNES = trabajador trabaja DOMINGO 22:00 - LUNES 06:00
                     * La asignación en BD tiene fecha = LUNES
                     *
                     * Para que el turno de noche aparezca PRIMERO (izquierda) en el calendario,
                     * usamos horas ficticias que FullCalendar ordena cronológicamente:
                     *
                     * - NOCHE (real 22:00-06:00) → slot visual 00:00-08:00
                     * - MAÑANA (real 06:00-14:00) → slot visual 08:00-16:00
                     * - TARDE (real 14:00-22:00) → slot visual 16:00-24:00
                     *
                     * IMPORTANTE: Este mapeo debe coincidir con:
                     * - resources/js/modules/calendario-trabajadores/calendar.js
                     * - calcularCargaTrabajoPorDia() en este mismo controlador
                     */
                    if ($esNocturno) {
                        // Turno nocturno: slot 00:00-08:00
                        $start = $fechaStr . 'T00:00:00';
                        $end = $fechaStr . 'T08:00:00';
                    } elseif ($hIni < 14) {
                        // Turno mañana: slot 08:00-16:00
                        $start = $fechaStr . 'T08:00:00';
                        $end = $fechaStr . 'T16:00:00';
                    } else {
                        // Turno tarde: slot 16:00-24:00
                        $start = $fechaStr . 'T16:00:00';
                        $end = $fechaStr . 'T23:59:59';
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
                                'turno_id' => $turno?->id,
                                'turno_nombre' => $turno?->nombre,
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

        // ✅ Calcular carga de trabajo por máquina y día
        $cargaTrabajo = $this->calcularCargaTrabajoPorDia();

        // ✅ Obtener turnos para configuración dinámica del calendario
        $turnos = Turno::activos()->ordenados()->get(['id', 'nombre', 'hora_inicio', 'hora_fin', 'color'])->map(function ($turno) {
            return [
                'id' => $turno->id,
                'nombre' => $turno->nombre,
                'hora_inicio' => $turno->hora_inicio,
                'hora_fin' => $turno->hora_fin,
                'color' => $turno->color ?? '#e5e7eb',
            ];
        });

        return view('produccion.trabajadores', compact('maquinas', 'trabajadoresEventos', 'operariosTrabajando', 'estadoProduccionMaquinas', 'registroFichajes', 'cargaTrabajo', 'turnos'));
    }

    public function actualizarPuesto(Request $request, $id)
    {
        // Validar datos
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
            'turno_id'   => 'nullable|exists:turnos,id',
            'start'      => 'nullable|string',
        ]);

        // Buscar la asignación
        $asignacion = AsignacionTurno::findOrFail($id);

        // Si se proporciona una nueva fecha, extraerla del start
        $nuevaFecha = null;
        if ($request->start) {
            $nuevaFecha = Carbon::parse($request->start)->format('Y-m-d');
        }

        // Verificar si ya existe una asignación para este usuario en la fecha destino
        if ($nuevaFecha && $nuevaFecha !== $asignacion->fecha->format('Y-m-d')) {
            $existeAsignacion = AsignacionTurno::where('user_id', $asignacion->user_id)
                ->where('fecha', $nuevaFecha)
                ->where('id', '!=', $asignacion->id)
                ->exists();

            if ($existeAsignacion) {
                return response()->json([
                    'message' => 'El trabajador ya tiene una asignación para esta fecha',
                    'error' => 'duplicado',
                ], 422);
            }
        }

        // Buscar la máquina para obtener su obra_id
        $maquina = Maquina::findOrFail($request->maquina_id);

        // Preparar datos para actualizar
        $datosActualizar = [
            'maquina_id' => $request->maquina_id,
            'turno_id'   => $request->turno_id,
            'obra_id'    => $maquina->obra_id,
        ];

        // Actualizar fecha si cambió
        if ($nuevaFecha) {
            $datosActualizar['fecha'] = $nuevaFecha;
        }

        $asignacion->update($datosActualizar);

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

        // 🔹 1. MÁQUINAS DISPONIBLES - TODAS excepto grúas + primera grúa de cada obra
        $primerasGruas = Maquina::where('tipo', 'grua')
            ->whereNotNull('obra_id')
            ->selectRaw('MIN(id) as id')
            ->groupBy('obra_id')
            ->pluck('id');

        $maquinas = Maquina::where(function ($q) use ($primerasGruas) {
                $q->where('tipo', '<>', 'grua')
                  ->orWhereIn('id', $primerasGruas);
            })
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


        // 🔹 3. Calcular colas iniciales de cada máquina (optimizado - sin N+1)
        $maquinaIds = $maquinas->pluck('id')->toArray();

        // Una sola consulta para obtener las últimas planillas fabricando por máquina
        $ultimasPlanillasPorMaquina = \DB::table('planillas')
            ->select('elementos.maquina_id', \DB::raw('MAX(planillas.fecha_inicio) as fecha_inicio'))
            ->join('elementos', 'elementos.planilla_id', '=', 'planillas.id')
            ->where('planillas.estado', 'fabricando')
            ->whereIn('elementos.maquina_id', $maquinaIds)
            ->groupBy('elementos.maquina_id')
            ->pluck('fecha_inicio', 'maquina_id');

        $colasMaquinas = [];
        $now = Carbon::now();
        $maxFecha = $now->copy()->addYear();
        $minFecha = $now->copy()->subYears(2);

        foreach ($maquinas as $m) {
            $fechaInicioCola = null;

            if (isset($ultimasPlanillasPorMaquina[$m->id])) {
                $fechaInicioCola = toCarbon($ultimasPlanillasPorMaquina[$m->id]);
            }

            // Si toCarbon devolvió null o no hay fecha_inicio, usar now()
            if (!$fechaInicioCola instanceof Carbon) {
                $fechaInicioCola = $now->copy();
            }

            // Validar que la fecha no esté demasiado lejos en el futuro o pasado
            if ($fechaInicioCola->lt($minFecha) || $fechaInicioCola->gt($maxFecha)) {
                $fechaInicioCola = $now->copy();
            }

            $colasMaquinas[$m->id] = $fechaInicioCola;
        }

        // 🔹 4. Obtener ordenes desde la tabla orden_planillas (SIN reordenar)
        $ordenes = OrdenPlanilla::orderBy('posicion')
            ->get()
            ->groupBy('maquina_id')
            ->map(fn($ordenesMaquina) => $ordenesMaquina->map(fn($o) => [
                'id' => $o->id,
                'planilla_id' => $o->planilla_id,
                'posicion' => $o->posicion
            ])->all());

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
        $turnosLista = Turno::orderBy('orden')->orderBy('hora_inicio')->get(); // Turnos completos con estado activo

        $initialDate = $this->calcularInitialDate();
        $fechaMaximaCalendario = $this->calcularFechaMaximaCalendario($initialDate);


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
            'turnosLista'         => $turnosLista,
            // Devolvemos también los filtros para reflejarlos en la vista/JS
            'filtro_fecha_inicio'              => $fechaInicio,
            'filtro_fecha_fin'                 => $fechaFin,
            'filtro_turno'                     => $turnoFiltro,
            'initialDate'                     => $initialDate,
            'fechaMaximaCalendario'           => $fechaMaximaCalendario,
        ]);
    }

    /**
     * Obtener recursos (máquinas) para el calendario de forma dinámica
     */
    public function obtenerRecursos(Request $request)
    {
        try {
            $primerasGruas = Maquina::where('tipo', 'grua')
                ->whereNotNull('obra_id')
                ->selectRaw('MIN(id) as id')
                ->groupBy('obra_id')
                ->pluck('id');

            $maquinas = Maquina::where(function ($q) use ($primerasGruas) {
                    $q->where('tipo', '<>', 'grua')
                      ->orWhereIn('id', $primerasGruas);
                })
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

            // Log deshabilitado para rendimiento
            // Log::info('✅ obtenerRecursos: devolviendo ' . count($resources) . ' máquinas');

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

        // 1. Obtener máquinas (necesarias para las colas) + primera grúa de cada obra
        $primerasGruas = Maquina::where('tipo', 'grua')
            ->whereNotNull('obra_id')
            ->selectRaw('MIN(id) as id')
            ->groupBy('obra_id')
            ->pluck('id');

        $maquinas = Maquina::where(function ($q) use ($primerasGruas) {
                $q->where('tipo', '<>', 'grua')
                  ->orWhereIn('id', $primerasGruas);
            })
            ->orderByRaw('CASE WHEN obra_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('obra_id')
            ->orderBy('tipo')
            ->get();

        // 2. Elementos activos
        $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina', 'maquina_2', 'maquina_3'])
            ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando']))
            ->get();

        // Log deshabilitado para rendimiento
        // Log::info('🔍 DEBUG obtenerEventos: Elementos obtenidos', ['count' => $elementos->count()]);

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

        // Log deshabilitado para rendimiento
        // Log::info('🔍 DEBUG obtenerEventos: Planillas agrupadas', ['count' => $planillasAgrupadas->count()]);

        // 3. Calcular colas iniciales de cada máquina (optimizado - sin N+1)
        $maquinaIds = $maquinas->pluck('id')->toArray();

        // Una sola consulta para obtener las últimas planillas fabricando por máquina
        $ultimasPlanillasPorMaquina = \DB::table('planillas')
            ->select('elementos.maquina_id', \DB::raw('MAX(planillas.fecha_inicio) as fecha_inicio'))
            ->join('elementos', 'elementos.planilla_id', '=', 'planillas.id')
            ->where('planillas.estado', 'fabricando')
            ->whereIn('elementos.maquina_id', $maquinaIds)
            ->groupBy('elementos.maquina_id')
            ->pluck('fecha_inicio', 'maquina_id');

        $colasMaquinas = [];
        $now = Carbon::now();
        $maxFecha = $now->copy()->addYear();
        $minFecha = $now->copy()->subYears(2);

        foreach ($maquinas as $m) {
            $fechaInicioCola = null;

            if (isset($ultimasPlanillasPorMaquina[$m->id])) {
                $fechaInicioCola = toCarbon($ultimasPlanillasPorMaquina[$m->id]);
            }

            // Si toCarbon devolvió null o no hay fecha_inicio, usar now()
            if (!$fechaInicioCola instanceof Carbon) {
                $fechaInicioCola = $now->copy();
            }

            // Validar que la fecha no esté demasiado lejos en el futuro o pasado
            if ($fechaInicioCola->lt($minFecha) || $fechaInicioCola->gt($maxFecha)) {
                $fechaInicioCola = $now->copy();
            }

            $colasMaquinas[$m->id] = $fechaInicioCola;
        }

        // 4. Obtener ordenes desde la tabla orden_planillas
        $ordenes = OrdenPlanilla::orderBy('posicion')
            ->get()
            ->groupBy('maquina_id')
            ->map(fn($ordenesMaquina) => $ordenesMaquina->map(fn($o) => [
                'id' => $o->id,
                'planilla_id' => $o->planilla_id,
                'posicion' => $o->posicion
            ])->all());

        // 5. Generar eventos usando el mismo método que maquinas()
        try {
            $planillasEventos = $this->generarEventosMaquinas($planillasAgrupadas, $ordenes, $colasMaquinas);

            // Convertir Collection a array para asegurar formato JSON correcto
            $eventosArray = $planillasEventos->values()->all();

            // Log deshabilitado para rendimiento
            // Log::info('✅ obtenerEventos: devolviendo ' . count($eventosArray) . ' eventos');

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
     * Calcula la fecha máxima del calendario basándose en el último fin programado
     */
    private function calcularFechaMaximaCalendario(string $initialDate): array
    {
        try {
            $fechaInicio = Carbon::parse($initialDate)->startOfDay();

            // Obtener todas las máquinas de producción
            $maquinaIds = Maquina::whereNotIn('tipo', ['grua', 'soldadora', 'ensambladora'])
                ->whereNotNull('tipo')
                ->where('estado', '!=', 'inactiva')
                ->pluck('id')
                ->toArray();

            if (empty($maquinaIds)) {
                // Mínimo 15 días si no hay máquinas
                return [
                    'fecha' => $fechaInicio->copy()->addDays(15)->toDateString(),
                    'dias' => 15,
                    'horas' => 360
                ];
            }

            // Obtener festivos
            $festivosSet = $this->obtenerFestivosSet();

            // Calcular el fin programado más lejano
            $fechaMaxima = $fechaInicio->copy();

            $ordenes = OrdenPlanilla::whereIn('maquina_id', $maquinaIds)
                ->orderBy('maquina_id')
                ->orderBy('posicion')
                ->get()
                ->groupBy('maquina_id');

            foreach ($ordenes as $maquinaId => $ordenesEnMaquina) {
                $cursorTiempo = now();

                foreach ($ordenesEnMaquina as $orden) {
                    // Obtener elementos pendientes de esta planilla en esta máquina
                    $tiempoSegundos = Elemento::where('planilla_id', $orden->planilla_id)
                        ->where('maquina_id', $maquinaId)
                        ->where('estado', 'pendiente')
                        ->sum('tiempo_fabricacion');

                    if ($tiempoSegundos <= 0) {
                        continue;
                    }

                    // Calcular fin programado usando tramos laborales
                    $tramos = $this->generarTramosLaborales($cursorTiempo, $tiempoSegundos, $festivosSet);

                    if (!empty($tramos)) {
                        $ultimoTramo = end($tramos);
                        $finProgramado = $ultimoTramo['end'] instanceof Carbon
                            ? $ultimoTramo['end']->copy()
                            : Carbon::parse($ultimoTramo['end']);

                        // Actualizar cursor para la siguiente planilla
                        $cursorTiempo = $finProgramado->copy();

                        // Actualizar fecha máxima si este fin es más lejano
                        if ($finProgramado->gt($fechaMaxima)) {
                            $fechaMaxima = $finProgramado->copy();
                        }
                    }
                }
            }

            // Añadir un día de margen
            $fechaMaxima = $fechaMaxima->addDay()->endOfDay();

            // Calcular días y horas desde el inicio
            $diasTotales = (int) ceil($fechaInicio->diffInDays($fechaMaxima));
            $diasTotales = max($diasTotales, 15); // Mínimo 15 días
            $horasTotales = $diasTotales * 24;

            // Log deshabilitado para rendimiento
            // Log::info('📅 Fecha máxima calendario calculada', [...]);

            return [
                'fecha' => $fechaMaxima->toDateString(),
                'dias' => $diasTotales,
                'horas' => $horasTotales
            ];
        } catch (\Exception $e) {
            Log::error('Error calculando fecha máxima calendario', [
                'error' => $e->getMessage()
            ]);

            // Fallback: 15 días
            return [
                'fecha' => Carbon::parse($initialDate)->addDays(15)->toDateString(),
                'dias' => 15,
                'horas' => 360
            ];
        }
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
    private function calcularPlanificadoYRealPorTurno($maquinas, ?string $fechaInicio = null, ?string $fechaFin = null, ?string $turnoFiltro = null): array
    {
        $turnosDefinidos = Turno::all(); // nombre, hora_inicio, hora_fin (HH:MM)

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

        $elementos = Elemento::with(['planilla', 'planilla.obra', 'maquina', 'maquina_2', 'maquina_3', 'etiquetaRelacion'])
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
            // 🌙 Caso especial: domingo - verificar turno noche (22:00)
            if ($x->dayOfWeek === Carbon::SUNDAY) {
                $segmentosDomingo = $this->obtenerSegmentosLaborablesDia($x);

                // Si hay segmentos en domingo (turno noche 22:00-06:00 lunes)
                if (!empty($segmentosDomingo)) {
                    $primerSegmento = $segmentosDomingo[0];
                    // Si el cursor está antes del turno noche (antes de las 22:00)
                    if ($x->lt($primerSegmento['inicio'])) {
                        return $primerSegmento['inicio']; // Domingo 22:00
                    }
                    // Si está dentro del turno noche, continuar desde ahí
                    if ($x->lt($primerSegmento['fin'])) {
                        return $x;
                    }
                }
            }

            // Si es día laborable (no festivo, no sábado)
            // Domingo se considera "no laborable" por isWeekend() pero ya lo manejamos arriba
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
        $esViernes = $dia->dayOfWeek === Carbon::FRIDAY;

        // 🚫 Sábado: NO generar ningún segmento
        if ($esSabado) {
            return $segmentos; // Array vacío
        }

        foreach ($turnosActivos as $turno) {
            if (!$turno->hora_inicio || !$turno->hora_fin) {
                continue;
            }

            // 🌙 Domingo: SOLO turno noche (offset_dias_inicio < 0)
            // El turno noche del lunes empieza el domingo a las 22:00
            if ($esDomingo && $turno->offset_dias_inicio >= 0) {
                continue; // Saltar turnos mañana/tarde del domingo
            }

            $horaInicio = \Carbon\Carbon::parse($turno->hora_inicio);
            $horaFin = \Carbon\Carbon::parse($turno->hora_fin);

            // 🛑 Viernes: NO generar turno noche (que iría al sábado)
            // El viernes termina a las 22:00 con el fin del turno tarde
            if ($esViernes && $horaInicio->hour >= 22) {
                continue; // Saltar turno noche del viernes
            }

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

            // 🛑 Viernes: Si es el último turno (tarde, que termina a las 22:00)
            // ajustar para que termine 1 segundo antes (21:59:59) para que NO ocupe la fila de las 22:00
            if ($esViernes && $horaFin->hour == 22 && $horaFin->minute == 0) {
                $fin->subSecond(); // 22:00:00 → 21:59:59
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

                    // ✅ CORTE VIERNES: Si estamos en viernes y el segmento pasa de las 22:00 (fin turno tarde)
                    // limitar el finSeg a las 22:00 del viernes para que NO continúe al sábado
                    if ($cursor->dayOfWeek === Carbon::FRIDAY) {
                        $finViernesTarde = $cursor->copy()->setTime(22, 0, 0);

                        // Si el segmento termina después de las 22:00 del viernes, cortarlo ahí
                        if ($finSeg->gt($finViernesTarde) && $cursor->lt($finViernesTarde)) {
                            // Log deshabilitado para rendimiento
                            // Log::info('✂️ CORTE VIERNES: Limitando segmento a las 22:00', [...]);
                            $finSeg = $finViernesTarde;
                        }
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

                    // ✅ Si llegamos a las 22:00 del viernes, FORZAR salida (fin de semana)
                    if ($cursor->dayOfWeek === Carbon::FRIDAY && $cursor->hour >= 22) {
                        // Log deshabilitado para rendimiento
                        // Log::info('🛑 FIN SEMANA: Deteniendo en viernes 22:00', [...]);
                        // Salir del bucle - el evento terminará en viernes 22:00
                        break 2; // Salir tanto del foreach como del while
                    }

                    // Si no queda más tiempo, salir
                    if ($restante <= 0) {
                        break;
                    }
                }

                // Si aún queda tiempo después de procesar todos los segmentos del día
                if ($restante > 0) {
                    // 🛑 CORTE FIN DE SEMANA: Si acabamos de procesar el viernes, DETENER
                    // NO continuar al sábado/domingo, el evento quedará incompleto
                    if ($diaActual->dayOfWeek === Carbon::FRIDAY) {
                        // Log deshabilitado para rendimiento
                        // Log::info('🛑 FIN SEMANA: Viernes procesado, deteniendo evento', [...]);
                        break; // Salir del while - el evento termina el viernes
                    }

                    // ✅ MEJORA: Verificar si hay continuidad entre el último segmento de hoy y el primero de mañana
                    $ultimoSegmentoHoy = end($segmentos);

                    // El día siguiente es +1 al día que acabamos de procesar
                    $siguienteDia = $diaActual->copy()->addDay();

                    $segmentosSiguienteDia = $this->obtenerSegmentosLaborablesDia($siguienteDia);

                    // Log deshabilitado para rendimiento
                    // Log::info('🔍 TRAMOS: Verificando continuidad', [...]);

                    // Si hay segmentos mañana y el último segmento de hoy conecta con el primero de mañana
                    if (!empty($segmentosSiguienteDia)) {
                        $primerSegmentoManana = $segmentosSiguienteDia[0];

                        // Verificar si son continuos (ej: turno noche 22:00 hoy → 06:00 mañana)
                        if (
                            $ultimoSegmentoHoy &&
                            $ultimoSegmentoHoy['fin']->equalTo($primerSegmentoManana['inicio'])
                        ) {
                            // Log deshabilitado para rendimiento
                            // Log::info('✅ TRAMOS: Continuidad detectada, NO cortando');
                            // ✅ Son continuos, avanzar al día siguiente SIN crear corte
                            // Mover el cursor al día siguiente para continuar procesando
                            $cursor = $siguienteDia->copy()->startOfDay();
                            continue;
                        }
                        // else: Sin continuidad, continuar normalmente (no necesita log)
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

        // 🔹 MÁQUINAS DISPONIBLES + primera grúa de cada obra
        $primerasGruas = Maquina::where('tipo', 'grua')
            ->whereNotNull('obra_id')
            ->selectRaw('MIN(id) as id')
            ->groupBy('obra_id')
            ->pluck('id');

        $maquinas = Maquina::whereNotNull('tipo')
            ->where(function ($q) use ($primerasGruas) {
                $q->where('tipo', '<>', 'grua')
                  ->orWhereIn('id', $primerasGruas);
            })
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
        // Solo pedir confirmación si NO se ha elegido crear nueva posición NI usar la existente
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
            DB::transaction(function () use ($planillaId, $maqOrigen, $maqDestino, $posNueva, $compatibles, $subsetIds, $forzar, $crearNuevaPosicion) {
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
                // ⚠️ SOLO si NO se acaba de crear con la posición correcta
                if (!$crearNuevaPosicion) {
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

        // Optimizado - sin N+1
        $ultimasPlanillasPorMaquina = \DB::table('planillas')
            ->select('elementos.maquina_id', \DB::raw('MAX(planillas.fecha_inicio) as fecha_inicio'))
            ->join('elementos', 'elementos.planilla_id', '=', 'planillas.id')
            ->where('planillas.estado', 'fabricando')
            ->whereIn('elementos.maquina_id', $maquinaIds)
            ->groupBy('elementos.maquina_id')
            ->pluck('fecha_inicio', 'maquina_id');

        $colasMaquinas = [];
        $now = Carbon::now();
        $maxFecha = $now->copy()->addYear();

        foreach ($maquinas as $m) {
            $fechaInicioCola = isset($ultimasPlanillasPorMaquina[$m->id])
                ? toCarbon($ultimasPlanillasPorMaquina[$m->id])
                : $now->copy();

            if (!$fechaInicioCola instanceof Carbon || $fechaInicioCola->gt($maxFecha)) {
                $fechaInicioCola = $now->copy();
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
                ->whereNotIn('estado', ['fabricando', 'fabricado']) // Excluir elementos ya en proceso o fabricados
                ->select('id', 'codigo', 'diametro', 'peso', 'dimensiones', 'maquina_id', 'barras')
                ->with('maquina:id,nombre')
                ->orderBy('maquina_id')
                ->get();
        } else {
            // Comportamiento original para compatibilidad
            $ids = explode(',', $request->ids);
            $elementos = Elemento::whereIn('id', $ids)
                ->whereNotIn('estado', ['fabricando', 'fabricado']) // Excluir elementos ya en proceso o fabricados
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

        // Log deshabilitado para rendimiento
        // Log::info('🔍 DEBUG: Planillas agrupadas', [
        //     'count' => $planillasAgrupadasCol->count(),
        //     'ordenes_count' => is_array($ordenes) ? count($ordenes) : 0
        // ]);

        // 3) Índice estable
        $agrupadasIndex = $planillasAgrupadasCol
            ->values()
            ->mapWithKeys(function ($data) {
                $planilla   = Arr::get($data, 'planilla');
                $planillaId = is_object($planilla) ? ($planilla->id ?? null) : Arr::get($planilla, 'id');
                $maquinaId  = Arr::get($data, 'maquina_id');
                if (!$planillaId || !$maquinaId) {
                    Log::warning('🔍 DEBUG: Datos inválidos en agrupadas', [
                        'planillaId' => $planillaId,
                        'maquinaId' => $maquinaId
                    ]);
                    return [];
                }
                return ["{$planillaId}-{$maquinaId}" => $data];
            });

        // Log deshabilitado para rendimiento
        // Log::info('🔍 DEBUG: Índice creado', ['count' => $agrupadasIndex->count()]);

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

            // Safeguard: validar que inicioCola no esté demasiado lejos en el futuro o en el pasado
            $maxFecha = Carbon::now()->addYear();
            $minFecha = Carbon::now()->subYears(2);
            if ($inicioCola->gt($maxFecha)) {
                Log::error('EVT: inicioCola inesperadamente lejana', [
                    'maquinaId' => $maquinaId,
                    'inicioCola' => $inicioCola->toIso8601String(),
                ]);
                $inicioCola = Carbon::now();
            } elseif ($inicioCola->lt($minFecha)) {
                Log::error('EVT: inicioCola inesperadamente antigua (posible fecha inválida)', [
                    'maquinaId' => $maquinaId,
                    'inicioCola' => $inicioCola->toIso8601String(),
                ]);
                $inicioCola = Carbon::now();
            }

            $primeraOrden = $planillasOrdenadas[0] ?? null;
            $primeraId = is_array($primeraOrden) ? ($primeraOrden['planilla_id'] ?? null) : $primeraOrden;
            // ID del orden_planilla en posición 1 (para determinar qué evento específico es el primero)
            $primeraOrdenId = is_array($primeraOrden) ? ($primeraOrden['id'] ?? null) : null;

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
                        $subGrupos = $grupo->groupBy(function ($elem) {
                            return $elem->orden_planilla_id ?? 'sin_orden';
                        });
                    }

                    // Si no hay orden_planilla_id, tratar todo el grupo como un solo evento
                    if ($subGrupos->isEmpty()) {
                        $subGrupos = collect(['unico' => $grupo]);
                    }

                    // CONSOLIDAR: Si hay múltiples subgrupos para la misma planilla/máquina, unificarlos
                    if ($subGrupos->count() > 1) {
                        Log::warning('🔄 EVENTOS: Múltiples orden_planilla_id detectados, consolidando', [
                            'planilla' => $planilla->codigo_limpio,
                            'maquina_id' => $maquinaId,
                            'num_subgrupos' => $subGrupos->count(),
                            'subgrupos_keys' => $subGrupos->keys()->toArray()
                        ]);

                        // Usar el primer orden_planilla_id y consolidar todos los elementos
                        $primerOrdenKey = $subGrupos->keys()->first();
                        $todosElementos = $subGrupos->flatten();
                        $subGrupos = collect([$primerOrdenKey => $todosElementos]);
                    }

                    // Log deshabilitado para rendimiento (se ejecuta por cada planilla)
                    // Log::info('🔍 EVENTOS: Generando eventos', [...]);

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
                        $duracionSegundos = $subGrupo->sum(function ($elemento) {
                            $tiempoFabricacion = (float)($elemento->tiempo_fabricacion ?? 1200);
                            $tiempoAmarrado = 1200; // 20 minutos por elemento
                            return $tiempoFabricacion + $tiempoAmarrado;
                        });
                        $duracionSegundos = max($duracionSegundos, 3600); // mínimo 1 hora

                        // Buscar la fecha_inicio más antigua de las etiquetas fabricando/completadas
                        $fechaInicioMasAntigua = null;
                        $minFechaValida = Carbon::now()->subYears(2);
                        if ($elementosFabricandoOCompletados->isNotEmpty()) {
                            $fechasInicio = collect();
                            foreach ($elementosFabricandoOCompletados as $elem) {
                                $etiqueta = $elem->subetiquetas->first();
                                if ($etiqueta && !empty($etiqueta->fecha_inicio)) {
                                    $fecha = toCarbon($etiqueta->fecha_inicio);
                                    // Solo agregar fechas válidas (no null y no demasiado antiguas como 1970)
                                    if ($fecha instanceof Carbon && $fecha->gt($minFechaValida)) {
                                        $fechasInicio->push($fecha);
                                    }
                                }
                            }
                            $fechaInicioMasAntigua = $fechasInicio->isNotEmpty() ? $fechasInicio->min() : null;
                        }

                        // Determinar fecha de inicio y duración
                        // 🔒 ANTI-SOLAPAMIENTO: SOLO el evento en posición 1 puede usar su fecha real de inicio
                        // Todos los demás DEBEN usar inicioCola para evitar solapamiento
                        // Comparamos el id del registro orden_planillas actual con el id del primero en la cola
                        // Si no hay orden_id (datos antiguos), comparamos por planilla_id + posición 1
                        $esPrimerEvento = false;
                        if ($ordenId !== null && $primeraOrdenId !== null) {
                            $esPrimerEvento = ((int)$ordenId === (int)$primeraOrdenId);
                        } elseif ($posicion !== null && (int)$posicion === 1) {
                            // Fallback: si es posición 1, es el primer evento
                            $esPrimerEvento = true;
                        }

                        if ($esPrimerEvento) {
                            // 🎯 PRIMER EVENTO: fecha de inicio basada en estado de la etiqueta
                            if ($fechaInicioMasAntigua) {
                                // Hay elementos fabricando: usar la fecha_inicio de la etiqueta más antigua
                                $fechaInicio = $fechaInicioMasAntigua;

                                // Calcular duración: desde inicio real hasta now() + tiempo pendientes
                                $duracionPendientes = $elementosPendientes->sum(function ($elemento) {
                                    $tiempoFabricacion = (float)($elemento->tiempo_fabricacion ?? 1200);
                                    $tiempoAmarrado = 1200;
                                    return $tiempoFabricacion + $tiempoAmarrado;
                                });

                                // Calcular dónde debería terminar el evento
                                $finEstimado = Carbon::now()->addSeconds($duracionPendientes);

                                // La duración es desde el inicio real hasta el fin estimado
                                $duracionSegundos = max($fechaInicio->diffInSeconds($finEstimado), 3600);

                                // Log deshabilitado para rendimiento
                                // Log::info('🎯 Primer evento FABRICANDO - usando fecha etiqueta', [...]);
                            } else {
                                // Todos los elementos están pendientes: usar now()
                                $fechaInicio = Carbon::now();

                                // Duración = suma de tiempos de fabricación de TODOS los elementos
                                $duracionSegundos = $subGrupo->sum(function ($elemento) {
                                    $tiempoFabricacion = (float)($elemento->tiempo_fabricacion ?? 1200);
                                    $tiempoAmarrado = 1200;
                                    return $tiempoFabricacion + $tiempoAmarrado;
                                });
                                $duracionSegundos = max($duracionSegundos, 3600);

                                // Log deshabilitado para rendimiento
                                // Log::info('🎯 Primer evento PENDIENTE - usando now()', [...]);
                            }
                        } else {
                            // Caso normal: usar SIEMPRE inicioCola para evitar solapamiento
                            // Esto incluye planillas con elementos fabricando que NO sean la primera
                            $fechaInicio = $inicioCola->copy();

                            // Duración = suma de tiempos de fabricación de TODOS los elementos
                            $duracionSegundos = $subGrupo->sum(function ($elemento) {
                                $tiempoFabricacion = (float)($elemento->tiempo_fabricacion ?? 1200);
                                $tiempoAmarrado = 1200;
                                return $tiempoFabricacion + $tiempoAmarrado;
                            });
                            $duracionSegundos = max($duracionSegundos, 3600);

                            if ($fechaInicioMasAntigua) {
                                Log::warning('⚠️ Evento con elementos fabricando NO es el primero en cola', [
                                    'planilla' => $planilla->codigo_limpio,
                                    'orden_planilla_id' => $ordenKey,
                                    'fecha_real_ignorada' => $fechaInicioMasAntigua->toIso8601String(),
                                    'usando_inicioCola' => $inicioCola->toIso8601String(),
                                    'es_primer_evento' => $esPrimerEvento
                                ]);
                            }
                        }

                        // Log deshabilitado para rendimiento
                        // Log::info('🔍 DEBUG ANTES generarTramosLaborales', [...]);

                        $tramos = $this->generarTramosLaborales($fechaInicio, $duracionSegundos, $festivosSet);

                        if (empty($tramos)) {
                            Log::warning('EVT H1: sin tramos', ['planillaId' => $planillaId, 'maquinaId' => $maquinaId, 'ordenKey' => $ordenKey]);
                            continue;
                        }

                        $primerTramoTemp = $tramos[0]['start'] instanceof Carbon ? $tramos[0]['start'] : Carbon::parse($tramos[0]['start']);
                        $ultimoTramoTemp = end($tramos)['end'] instanceof Carbon ? end($tramos)['end'] : Carbon::parse(end($tramos)['end']);
                        $duracionRealSegundos = $primerTramoTemp->diffInSeconds($ultimoTramoTemp);

                        // Log deshabilitado para rendimiento
                        // Log::info('📍 TRAMOS generados', [...]);

                        $ultimoTramo  = end($tramos);
                        $fechaFinReal = $ultimoTramo['end'] instanceof Carbon
                            ? $ultimoTramo['end']->copy()
                            : Carbon::parse($ultimoTramo['end']);

                        // Progreso (calculado por sub-grupo, solo para el evento en posición 1)
                        $progreso = null;
                        if ($esPrimerEvento) {
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

                        // Log deshabilitado para rendimiento
                        // Log::info('✅ EVENTOS: Creando evento', [...]);

                        // Formatear fechas con offset de timezone (ISO8601 completo)
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
                        // 🔒 IMPORTANTE: Usar eventoFin (fin real del último tramo) en lugar de fechaFinReal
                        // para garantizar que el siguiente evento empiece exactamente donde termina este
                        $inicioCola = $eventoFin->copy();

                        // Log deshabilitado para rendimiento
                        // Log::info('🔄 COLA ACTUALIZADA después de evento', [...]);
                    } // fin foreach subGrupos

                    // Avanza cola (solo si eventoFin está definida)
                    // Ya se actualiza dentro del foreach, pero mantenemos esto por compatibilidad
                    if (isset($eventoFin)) {
                        $inicioCola = $eventoFin->copy();
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

            // Optimizado - sin N+1
            $maquinaIds = $maquinas->pluck('id')->toArray();
            $ultimasPlanillasPorMaquina = \DB::table('planillas')
                ->select('elementos.maquina_id', \DB::raw('MAX(planillas.fecha_inicio) as fecha_inicio'))
                ->join('elementos', 'elementos.planilla_id', '=', 'planillas.id')
                ->where('planillas.estado', 'fabricando')
                ->whereIn('elementos.maquina_id', $maquinaIds)
                ->groupBy('elementos.maquina_id')
                ->pluck('fecha_inicio', 'maquina_id');

            $colasMaquinas = [];
            $now = Carbon::now();
            $maxFecha = $now->copy()->addYear();

            foreach ($maquinas as $m) {
                $fechaInicioCola = isset($ultimasPlanillasPorMaquina[$m->id])
                    ? toCarbon($ultimasPlanillasPorMaquina[$m->id])
                    : $now->copy();

                if (!$fechaInicioCola instanceof Carbon || $fechaInicioCola->gt($maxFecha)) {
                    $fechaInicioCola = $now->copy();
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
                $fecha = \Carbon\Carbon::parse($asignacion->fecha)->format('Y-m-d');

                return [
                    'id'         => 'turno-' . $asignacion->id,
                    'title'      => $asignacion->user?->nombre_completo ?? 'Desconocido',
                    'start'      => $fecha . 'T06:00:00',
                    'end'        => $fecha . 'T14:00:00',
                    'resourceId' => $asignacion->obra_id ?? 'sin-obra',
                    'extendedProps' => [
                        'user_id' => $asignacion->user_id,
                        'estado'  => $asignacion->estado,
                        'turno'   => $asignacion->turno?->nombre,
                        'foto'    => $asignacion->user?->ruta_imagen,
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
                    // Si el formato no incluye hora (solo fecha), establecer las 7:00 AM como hora límite de entrega
                    if (in_array($fmt, ['Y-m-d', 'd/m/Y'])) {
                        $dt->setTime(7, 0, 0);
                    }
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
        $primerasGruas = Maquina::where('tipo', 'grua')
            ->whereNotNull('obra_id')
            ->selectRaw('MIN(id) as id')
            ->groupBy('obra_id')
            ->pluck('id');

        $maquinas = Maquina::query()
            ->where(function ($q) use ($primerasGruas) {
                $q->where('tipo', '!=', 'grua')
                  ->orWhereIn('id', $primerasGruas);
            })
            ->orderByRaw('CASE WHEN obra_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('obra_id')
            ->orderBy('tipo')
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
                // TODOS los elementos para calcular la carga total (igual que el calendario)
                $elementosTodos = Elemento::with(['planilla', 'maquina'])
                    ->where('maquina_id', $maquina->id)
                    ->whereHas('planilla', fn($q) => $q->whereIn('estado', ['pendiente', 'fabricando', 'programada']))
                    ->whereIn('estado', ['pendiente', 'fabricando']) // Pendientes y fabricando para la carga
                    ->get();

                // Solo elementos PENDIENTES se pueden mover
                $elementos = $elementosTodos->where('estado', 'pendiente');

                // Calcular tiempo total de TODOS los elementos (igual que el calendario)
                $tiempoAmarradoPorElemento = 1200; // 20 minutos en segundos
                $tiempoTotal = $elementosTodos->sum(function($elem) use ($tiempoAmarradoPorElemento) {
                    return ($elem->tiempo_fabricacion ?? 1200) + $tiempoAmarradoPorElemento;
                });

                \Log::info("⚖️ BALANCEO: Carga de máquina", [
                    'maquina' => $maquina->nombre,
                    'elementos_totales' => $elementosTodos->count(),
                    'elementos_movibles' => $elementos->count(),
                    'tiempo_horas' => round($tiempoTotal / 3600, 2),
                    'elementos_sample' => $elementos->take(3)->pluck('codigo')->toArray()
                ]);

                $cargasMaquinas[$maquina->id] = [
                    'maquina' => $maquina,
                    'tiempo_segundos' => $tiempoTotal,
                    'tiempo_horas' => round($tiempoTotal / 3600, 2),
                    'cantidad_elementos' => $elementosTodos->count(), // Total para mostrar en gráfico
                ];

                $elementosPorMaquina[$maquina->id] = $elementos; // Solo pendientes para mover
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
                $maquinasGrupo = collect($cargasMaquinas)->filter(function ($carga) use ($todosLosTipos) {
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

                        $maquinasDelGrupo = $maquinas->filter(function ($maq) use ($todosLosTipos, $tiposPrincipales, $esManual) {
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

                            $maquinasCompatibles = array_filter($maquinasCompatibles, function ($maq) use ($elemento, $tiempoPromedioGrupo, $limiteMaximo) {
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
                            usort($maquinasCompatibles, function ($a, $b) use ($elemento, $tiempoPromedioGrupo) {
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

                            $tiempoElementoTotal = ($elemento->tiempo_fabricacion ?? 1200) + 1200; // + 20 min amarrado
                            $elementosAMover[] = [
                                'elemento_id' => $elemento->id,
                                'codigo' => $elemento->codigo,
                                'marca' => $elemento->marca,
                                'diametro' => $elemento->diametro,
                                'peso' => $elemento->peso,
                                'tiempo_fabricacion' => $tiempoElementoTotal,
                                'tiempo_horas' => round($tiempoElementoTotal / 3600, 2),
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
            $resumenMaquinas = collect($cargasMaquinas)->map(function ($carga) {
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

            $procesados = 0;
            $errores = [];

            foreach ($movimientos as $mov) {
                try {
                    $elemento = Elemento::find($mov['elemento_id']);

                    if (!$elemento) {
                        $errores[] = "Elemento {$mov['elemento_id']} no encontrado";
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
                    $elemento->maquina_id = $mov['maquina_nueva_id'];
                    $elemento->orden_planilla_id = $ordenPlanillaDestino->id;
                    $elemento->save();

                    Log::info('⚖️ BALANCEO: Elemento movido', [
                        'elemento' => $elemento->codigo,
                        'planilla' => $planillaId,
                        'maquina_origen' => $maquinaAnterior,
                        'maquina_destino' => $mov['maquina_nueva_id'],
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
                } catch (\Exception $e) {
                    $errores[] = "Error moviendo elemento {$mov['elemento_id']}: " . $e->getMessage();
                    Log::error('Error moviendo elemento', [
                        'elemento_id' => $mov['elemento_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            Log::info('⚖️ BALANCEO: Redistribución completada', [
                'procesados' => $procesados,
                'errores' => count($errores)
            ]);

            return response()->json([
                'success' => true,
                'procesados' => $procesados,
                'total' => count($movimientos),
                'errores' => $errores,
                'message' => "Balanceo aplicado: $procesados elementos redistribuidos"
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
     * Obtener resumen del calendario de producción
     */
    public function obtenerResumen()
    {
        try {
            // Obtener festivos para cálculo de tramos
            $festivosSet = $this->obtenerFestivosSet();

            // 1. Obtener todas las máquinas de producción (excluir grúas, soldadoras y ensambladoras)
            $maquinas = Maquina::whereNotIn('tipo', ['grua', 'soldadora', 'ensambladora'])
                ->whereNotNull('tipo')
                ->where('estado', '!=', 'inactiva')
                ->get();

            // 2. Calcular datos por máquina
            $datosMaquinas = [];
            $planillasConRetraso = [];
            $planillasRevisadas = 0;
            $planillasNoRevisadas = 0;
            $planillasAnalizadas = collect();

            foreach ($maquinas as $maquina) {
                // Obtener órdenes de planillas para esta máquina
                $ordenesEnMaquina = DB::table('orden_planillas')
                    ->join('planillas', 'planillas.id', '=', 'orden_planillas.planilla_id')
                    ->where('orden_planillas.maquina_id', $maquina->id)
                    ->select('orden_planillas.*', 'planillas.fecha_estimada_entrega', 'planillas.codigo', 'planillas.revisada', 'planillas.obra_id', 'planillas.descripcion', 'planillas.seccion', 'planillas.ensamblado')
                    ->orderBy('orden_planillas.posicion')
                    ->get();

                // Calcular kilos y tiempo totales para esta máquina
                $kilosTotales = 0;
                $tiempoTotalSegundos = 0;
                $cursorTiempo = now();

                foreach ($ordenesEnMaquina as $orden) {
                    // Obtener elementos pendientes de esta planilla en esta máquina
                    $elementos = Elemento::where('planilla_id', $orden->planilla_id)
                        ->where('maquina_id', $maquina->id)
                        ->where('estado', 'pendiente')
                        ->get();

                    if ($elementos->isEmpty()) {
                        continue;
                    }

                    // Sumar kilos y tiempo
                    $kilosPlanilla = $elementos->sum('peso');
                    $tiempoPlanilla = $elementos->sum('tiempo_fabricacion');

                    $kilosTotales += $kilosPlanilla;
                    $tiempoTotalSegundos += $tiempoPlanilla;

                    // Calcular fin programado usando tramos laborales
                    $tramos = $this->generarTramosLaborales($cursorTiempo, $tiempoPlanilla, $festivosSet);

                    if (!empty($tramos)) {
                        $ultimoTramo = end($tramos);
                        $finProgramado = $ultimoTramo['end'] instanceof Carbon
                            ? $ultimoTramo['end']->copy()
                            : Carbon::parse($ultimoTramo['end']);

                        // Actualizar cursor para la siguiente planilla
                        $cursorTiempo = $finProgramado->copy();

                        // Verificar si está fuera de tiempo
                        $fechaEntrega = Carbon::parse($orden->fecha_estimada_entrega);
                        if ($finProgramado->gt($fechaEntrega)) {
                            // Solo agregar si no se ha analizado esta planilla
                            if (!$planillasAnalizadas->contains($orden->planilla_id)) {
                                $diasRetraso = (int) ceil($finProgramado->floatDiffInDays($fechaEntrega));
                                $planillasConRetraso[] = [
                                    'planilla_codigo' => $orden->codigo,
                                    'planilla_id' => $orden->planilla_id,
                                    'planilla_descripcion' => $orden->descripcion,
                                    'planilla_seccion' => $orden->seccion,
                                    'planilla_ensamblado' => $orden->ensamblado,
                                    'obra_id' => $orden->obra_id,
                                    'maquina_codigo' => $maquina->codigo,
                                    'fecha_entrega' => $fechaEntrega->format('d/m/Y'),
                                    'fin_programado' => $finProgramado->format('d/m/Y H:i'),
                                    'dias_retraso' => $diasRetraso,
                                ];
                            }
                        }
                    }

                    // Contar revisadas/no revisadas (solo una vez por planilla)
                    if (!$planillasAnalizadas->contains($orden->planilla_id)) {
                        $planillasAnalizadas->push($orden->planilla_id);
                        if ($orden->revisada) {
                            $planillasRevisadas++;
                        } else {
                            $planillasNoRevisadas++;
                        }
                    }
                }

                // Convertir tiempo a formato legible
                $horas = floor($tiempoTotalSegundos / 3600);
                $minutos = floor(($tiempoTotalSegundos % 3600) / 60);

                $datosMaquinas[] = [
                    'id' => $maquina->id,
                    'codigo' => $maquina->codigo,
                    'nombre' => $maquina->nombre ?? $maquina->codigo,
                    'tipo' => $maquina->tipo,
                    'kilos_totales' => round($kilosTotales, 2),
                    'kilos_formateado' => number_format($kilosTotales, 2, ',', '.') . ' kg',
                    'tiempo_total_segundos' => $tiempoTotalSegundos,
                    'tiempo_formateado' => $horas . 'h ' . $minutos . 'min',
                    'planillas_en_cola' => $ordenesEnMaquina->count(),
                ];
            }

            // Ordenar máquinas por kilos (descendente)
            usort($datosMaquinas, function ($a, $b) {
                return $b['kilos_totales'] <=> $a['kilos_totales'];
            });

            // Agrupar planillas con retraso por cliente, obra y fecha de entrega
            $clientesAgrupados = [];
            foreach ($planillasConRetraso as $planilla) {
                $obra = Obra::with('cliente')->find($planilla['obra_id']);
                $clienteId = $obra && $obra->cliente ? $obra->cliente->id : 0;
                $clienteCodigo = $obra && $obra->cliente ? $obra->cliente->codigo : '-';
                $clienteNombre = $obra && $obra->cliente ? $obra->cliente->empresa : 'Sin cliente';
                $obraId = $obra ? $obra->id : 0;
                $obraCodigo = $obra ? $obra->cod_obra : '-';
                $obraNombre = $obra ? $obra->obra : 'Sin obra';
                $fechaEntrega = $planilla['fecha_entrega']; // dd/mm/yyyy

                // Inicializar cliente si no existe
                if (!isset($clientesAgrupados[$clienteId])) {
                    $clientesAgrupados[$clienteId] = [
                        'cliente_id' => $clienteId,
                        'cliente_codigo' => $clienteCodigo,
                        'cliente_nombre' => $clienteNombre,
                        'obras' => [],
                    ];
                }

                // Inicializar obra si no existe
                if (!isset($clientesAgrupados[$clienteId]['obras'][$obraId])) {
                    $clientesAgrupados[$clienteId]['obras'][$obraId] = [
                        'obra_id' => $obraId,
                        'obra_codigo' => $obraCodigo,
                        'obra_nombre' => $obraNombre,
                        'fechas' => [],
                    ];
                }

                // Inicializar fecha si no existe
                if (!isset($clientesAgrupados[$clienteId]['obras'][$obraId]['fechas'][$fechaEntrega])) {
                    $clientesAgrupados[$clienteId]['obras'][$obraId]['fechas'][$fechaEntrega] = [
                        'fecha_entrega' => $fechaEntrega,
                        'planillas' => [],
                    ];
                }

                // Agregar planilla a la fecha correspondiente
                $clientesAgrupados[$clienteId]['obras'][$obraId]['fechas'][$fechaEntrega]['planillas'][] = [
                    'planilla_codigo' => $planilla['planilla_codigo'],
                    'descripcion' => $planilla['planilla_descripcion'],
                    'seccion' => $planilla['planilla_seccion'],
                    'ensamblado' => $planilla['planilla_ensamblado'],
                    'maquina_codigo' => $planilla['maquina_codigo'],
                    'fecha_entrega' => $planilla['fecha_entrega'],
                    'fin_programado' => $planilla['fin_programado'],
                    'dias_retraso' => $planilla['dias_retraso'],
                ];
            }

            // Convertir arrays asociativos a arrays indexados y ordenar fechas
            $clientesAgrupados = array_values($clientesAgrupados);
            foreach ($clientesAgrupados as &$cliente) {
                $cliente['obras'] = array_values($cliente['obras']);
                foreach ($cliente['obras'] as &$obra) {
                    // Ordenar fechas cronológicamente
                    $fechas = $obra['fechas'];
                    uasort($fechas, function ($a, $b) {
                        $dateA = \DateTime::createFromFormat('d/m/Y', $a['fecha_entrega']);
                        $dateB = \DateTime::createFromFormat('d/m/Y', $b['fecha_entrega']);
                        return $dateA <=> $dateB;
                    });
                    $obra['fechas'] = array_values($fechas);
                }
            }

            return response()->json([
                'success' => true,
                'resumen' => [
                    'planillas_revisadas' => $planillasRevisadas,
                    'planillas_no_revisadas' => $planillasNoRevisadas,
                    'total_planillas' => $planillasRevisadas + $planillasNoRevisadas,
                    'planillas_con_retraso' => count($planillasConRetraso),
                ],
                'clientes_con_retraso' => $clientesAgrupados,
                'maquinas' => $datosMaquinas,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en obtenerResumen:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen: ' . $e->getMessage()
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
        ]);

        DB::beginTransaction();

        try {
            $redistribuciones = $request->input('redistribuciones');
            $elementosMovidos = 0;

            // Rastrear planillas afectadas por máquina
            $planillasAfectadas = []; // [planilla_id => ['maquina_anterior' => id, 'maquina_nueva' => id]]

            foreach ($redistribuciones as $redistribucion) {
                $elemento = Elemento::find($redistribucion['elemento_id']);
                $nuevaMaquinaId = $redistribucion['nueva_maquina_id'];

                if (!$elemento) continue;

                $maquinaAnterior = $elemento->maquina_id;
                $planillaId = $elemento->planilla_id;

                // Actualizar máquina del elemento
                $elemento->maquina_id = $nuevaMaquinaId;
                $elemento->save();

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

            DB::commit();

            return response()->json([
                'success' => true,
                'elementos_movidos' => $elementosMovidos,
                'planillas_actualizadas' => count($planillasAfectadas),
                'message' => "Se redistribuyeron {$elementosMovidos} elementos de " . count($planillasAfectadas) . " planillas exitosamente"
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
     * Obtener obras con sus fechas de entrega agrupadas
     */
    public function obrasConPlanillasActivas()
    {
        // Obtener planillas activas con sus obras y fechas de entrega
        $planillas = Planilla::with('obra:id,obra,cod_obra')
            ->whereIn('estado', ['pendiente', 'fabricando'])
            ->whereHas('ordenProduccion')
            ->whereNotNull('obra_id')
            ->select('id', 'codigo', 'obra_id', 'fecha_estimada_entrega')
            ->get();

        // Agrupar por obra y fecha de entrega
        $agrupaciones = $planillas->groupBy(function ($planilla) {
            $fecha = $planilla->fecha_estimada_entrega;
            if (!$fecha) return $planilla->obra_id . '|sin_fecha';

            // Normalizar fecha (solo día, sin hora)
            try {
                $fechaCarbon = $fecha instanceof \Carbon\Carbon
                    ? $fecha
                    : \Carbon\Carbon::parse($fecha);
                return $planilla->obra_id . '|' . $fechaCarbon->format('Y-m-d');
            } catch (\Exception $e) {
                return $planilla->obra_id . '|sin_fecha';
            }
        })->map(function ($grupo, $key) {
            $parts = explode('|', $key);
            $obraId = $parts[0];
            $fecha = $parts[1] ?? 'sin_fecha';
            $primeraplanilla = $grupo->first();
            $obra = $primeraplanilla->obra;

            // Obtener los códigos limpios de las planillas
            $codigosLimpios = $grupo->map(fn($p) => $p->codigo_limpio)->filter()->values();

            return [
                'obra_id' => (int) $obraId,
                'cod_obra' => $obra->cod_obra ?? '—',
                'obra' => $obra->obra ?? '—',
                'fecha_entrega' => $fecha,
                'fecha_entrega_formatted' => $fecha === 'sin_fecha'
                    ? 'Sin fecha'
                    : \Carbon\Carbon::parse($fecha)->format('d/m/Y'),
                'planillas_count' => $grupo->count(),
                'planillas_ids' => $grupo->pluck('id')->values(),
                'planillas_codigos' => $codigosLimpios,
            ];
        })->values()->sortBy([
            ['fecha_entrega', 'asc'],
            ['cod_obra', 'asc'],
        ])->values();

        return response()->json($agrupaciones);
    }

    /**
     * Priorizar planillas específicas (por obra + fecha de entrega)
     */
    public function priorizarObra(Request $request)
    {
        $request->validate([
            'obra_id' => 'required|integer|exists:obras,id',
            'planillas_ids' => 'required|array|min:1',
            'planillas_ids.*' => 'integer|exists:planillas,id',
            'parar_fabricando' => 'nullable|boolean',
        ]);

        $obraId = (int) $request->obra_id;
        $planillasIds = collect($request->planillas_ids)->map(fn($id) => (int) $id);
        $obra = Obra::find($obraId);
        $pararFabricando = (bool) $request->parar_fabricando;

        DB::beginTransaction();

        try {
            // Obtener las OrdenPlanilla de las planillas específicas
            $ordenesObra = OrdenPlanilla::whereIn('planilla_id', $planillasIds)->get();

            if ($ordenesObra->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay planillas activas de esta obra en la cola de producción'
                ], 404);
            }

            // Agrupar por máquina
            $ordenesPorMaquina = $ordenesObra->groupBy('maquina_id');
            $maquinasAfectadas = 0;
            $planillasMovidas = 0;
            $planillasParadas = 0;

            foreach ($ordenesPorMaquina as $maquinaId => $ordenesDeObra) {
                // Si se debe parar las planillas fabricando en posición 1
                if ($pararFabricando) {
                    $ordenPosicion1 = OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->where('posicion', 1)
                        ->with('planilla')
                        ->first();

                    if ($ordenPosicion1 && $ordenPosicion1->planilla && $ordenPosicion1->planilla->estado === 'fabricando') {
                        // Verificar que no sea una de las planillas que vamos a priorizar
                        if (!$planillasIds->contains($ordenPosicion1->planilla_id)) {
                            $ordenPosicion1->planilla->estado = 'pendiente';
                            $ordenPosicion1->planilla->save();
                            $planillasParadas++;

                            Log::info('⏸️ Planilla en posición 1 detenida', [
                                'planilla_id' => $ordenPosicion1->planilla_id,
                                'maquina_id' => $maquinaId,
                            ]);
                        }
                    }
                }

                // Obtener todas las órdenes de esta máquina ordenadas por posición
                $todasLasOrdenes = OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->orderBy('posicion')
                    ->get();

                // Separar: planillas de la obra vs resto
                $idsOrdenesObra = $ordenesDeObra->pluck('id')->toArray();
                $ordenesObraPrioritarias = $todasLasOrdenes->whereIn('id', $idsOrdenesObra)->values();
                $ordenesResto = $todasLasOrdenes->whereNotIn('id', $idsOrdenesObra)->values();

                // Reasignar posiciones: primero las de la obra, luego el resto
                $posicion = 1;

                foreach ($ordenesObraPrioritarias as $orden) {
                    if ($orden->posicion !== $posicion) {
                        $orden->posicion = $posicion;
                        $orden->save();
                        $planillasMovidas++;
                    }
                    $posicion++;
                }

                foreach ($ordenesResto as $orden) {
                    if ($orden->posicion !== $posicion) {
                        $orden->posicion = $posicion;
                        $orden->save();
                    }
                    $posicion++;
                }

                $maquinasAfectadas++;
            }

            DB::commit();

            Log::info('✅ Obra priorizada', [
                'obra_id' => $obraId,
                'obra' => $obra->obra,
                'maquinas_afectadas' => $maquinasAfectadas,
                'planillas_movidas' => $planillasMovidas,
                'planillas_paradas' => $planillasParadas,
                'parar_fabricando' => $pararFabricando,
                'user_id' => auth()->id(),
            ]);

            $mensaje = "Obra '{$obra->cod_obra}' priorizada. {$ordenesObra->count()} planillas movidas al inicio en {$maquinasAfectadas} máquinas.";
            if ($planillasParadas > 0) {
                $mensaje .= " Se detuvieron {$planillasParadas} planillas que estaban fabricando.";
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'maquinas_afectadas' => $maquinasAfectadas,
                'planillas_movidas' => $ordenesObra->count(),
                'planillas_paradas' => $planillasParadas,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al priorizar obra:', [
                'obra_id' => $obraId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al priorizar obra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Priorizar múltiples obras con sus fechas de entrega (hasta 5 posiciones)
     */
    public function priorizarObras(Request $request)
    {
        $request->validate([
            'prioridades' => 'required|array|min:1|max:5',
            'prioridades.*.obra_id' => 'required|integer|exists:obras,id',
            'prioridades.*.planillas_ids' => 'required|array|min:1',
            'prioridades.*.planillas_ids.*' => 'integer|exists:planillas,id',
            'parar_fabricando' => 'nullable|boolean',
        ]);

        $prioridades = $request->prioridades;
        $pararFabricando = (bool) $request->parar_fabricando;

        DB::beginTransaction();

        try {
            // Recopilar todas las planillas a priorizar en orden
            $todasLasPlanillasOrdenadas = [];
            foreach ($prioridades as $prioridad) {
                foreach ($prioridad['planillas_ids'] as $planillaId) {
                    $todasLasPlanillasOrdenadas[] = (int) $planillaId;
                }
            }

            // Obtener todas las OrdenPlanilla de las planillas seleccionadas
            $ordenesAPriorizar = OrdenPlanilla::whereIn('planilla_id', $todasLasPlanillasOrdenadas)->get();

            if ($ordenesAPriorizar->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay planillas activas en la cola de producción'
                ], 404);
            }

            // Agrupar por máquina
            $ordenesPorMaquina = $ordenesAPriorizar->groupBy('maquina_id');
            $maquinasAfectadas = 0;
            $planillasMovidas = 0;
            $planillasParadas = 0;

            foreach ($ordenesPorMaquina as $maquinaId => $ordenesDeSeleccion) {
                // Si se debe parar las planillas fabricando
                if ($pararFabricando) {
                    $ordenesEnFabricacion = OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->whereHas('planilla', fn($q) => $q->where('estado', 'fabricando'))
                        ->with('planilla')
                        ->get();

                    foreach ($ordenesEnFabricacion as $ordenFab) {
                        // Solo parar si no está en las planillas a priorizar
                        if (!in_array($ordenFab->planilla_id, $todasLasPlanillasOrdenadas)) {
                            $ordenFab->planilla->estado = 'pendiente';
                            $ordenFab->planilla->save();
                            $planillasParadas++;

                            Log::info('⏸️ Planilla detenida por priorización múltiple', [
                                'planilla_id' => $ordenFab->planilla_id,
                                'maquina_id' => $maquinaId,
                            ]);
                        }
                    }
                }

                // Obtener todas las órdenes de esta máquina ordenadas por posición
                $todasLasOrdenes = OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->orderBy('posicion')
                    ->get();

                // Separar: planillas priorizadas vs resto
                $idsOrdenesPriorizadas = $ordenesDeSeleccion->pluck('id')->toArray();

                // Ordenar las priorizadas según el orden global que vino del frontend
                $ordenesPriorizadas = collect();
                foreach ($todasLasPlanillasOrdenadas as $planillaId) {
                    $orden = $ordenesDeSeleccion->firstWhere('planilla_id', $planillaId);
                    if ($orden) {
                        $ordenesPriorizadas->push($orden);
                    }
                }

                $ordenesResto = $todasLasOrdenes->whereNotIn('id', $idsOrdenesPriorizadas)->values();

                // Reasignar posiciones: primero las priorizadas en orden, luego el resto
                $posicion = 1;

                foreach ($ordenesPriorizadas as $orden) {
                    if ($orden->posicion !== $posicion) {
                        $orden->posicion = $posicion;
                        $orden->save();
                        $planillasMovidas++;
                    }
                    $posicion++;
                }

                foreach ($ordenesResto as $orden) {
                    if ($orden->posicion !== $posicion) {
                        $orden->posicion = $posicion;
                        $orden->save();
                    }
                    $posicion++;
                }

                $maquinasAfectadas++;
            }

            DB::commit();

            // Preparar mensaje de resumen
            $obrasInfo = [];
            foreach ($prioridades as $idx => $prioridad) {
                $obra = Obra::find($prioridad['obra_id']);
                $obrasInfo[] = ($idx + 1) . ". {$obra->cod_obra}";
            }

            Log::info('✅ Múltiples obras priorizadas', [
                'obras' => $obrasInfo,
                'maquinas_afectadas' => $maquinasAfectadas,
                'planillas_movidas' => $planillasMovidas,
                'planillas_paradas' => $planillasParadas,
                'user_id' => auth()->id(),
            ]);

            $mensaje = "Prioridades aplicadas: " . implode(', ', $obrasInfo) . ".<br>";
            $mensaje .= "{$ordenesAPriorizar->count()} planillas reordenadas en {$maquinasAfectadas} máquinas.";
            if ($planillasParadas > 0) {
                $mensaje .= "<br>Se detuvieron {$planillasParadas} planillas que estaban fabricando.";
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'maquinas_afectadas' => $maquinasAfectadas,
                'planillas_movidas' => $ordenesAPriorizar->count(),
                'planillas_paradas' => $planillasParadas,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al priorizar múltiples obras:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al priorizar obras: ' . $e->getMessage()
            ], 500);
        }
    }
}
