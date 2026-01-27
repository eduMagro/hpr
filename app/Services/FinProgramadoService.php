<?php

namespace App\Services;

use App\Models\Elemento;
use App\Models\Festivo;
use App\Models\Maquina;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinProgramadoService
{
    private array $festivosSet = [];
    private ?Collection $turnosActivos = null;

    /**
     * Calcula el fin programado para un conjunto de elementos
     * Usa la MISMA lógica que ProduccionController::generarEventosMaquinas
     * OPTIMIZADO: Pre-carga todos los elementos para evitar N+1 queries
     */
    public function calcularFinProgramadoElementos(array $elementosIds, ?Carbon $fechaEntrega = null): array
    {
        if (empty($elementosIds)) {
            return [
                'fin_programado' => null,
                'fin_programado_str' => null,
                'hay_retraso' => false,
                'detalles' => [],
            ];
        }

        $this->cargarFestivos();
        $this->cargarTurnos();

        // Obtener elementos con sus planillas
        $elementos = Elemento::with(['planilla'])
            ->whereIn('id', $elementosIds)
            ->pendiente()
            ->get();

        if ($elementos->isEmpty()) {
            return [
                'fin_programado' => null,
                'fin_programado_str' => null,
                'hay_retraso' => false,
                'detalles' => [],
            ];
        }

        // Agrupar por máquina
        $elementosPorMaquina = $elementos->groupBy('maquina_id');
        $maquinaIds = $elementosPorMaquina->keys()->filter()->toArray();

        if (empty($maquinaIds)) {
            return [
                'fin_programado' => null,
                'hay_retraso' => false,
                'detalles' => [],
            ];
        }

        // Calcular colas iniciales (como en ProduccionController)
        $colasMaquinas = $this->calcularColasIniciales($maquinaIds);

        // Obtener órdenes de cada máquina
        $ordenes = OrdenPlanilla::whereIn('maquina_id', $maquinaIds)
            ->orderBy('maquina_id')
            ->orderBy('posicion')
            ->get()
            ->groupBy('maquina_id');

        // OPTIMIZACIÓN: Pre-cargar TODOS los elementos pendientes de las planillas y máquinas involucradas
        $planillasIdsOrdenes = $ordenes->flatten()->pluck('planilla_id')->unique()->toArray();
        $todosElementosPendientes = Elemento::whereIn('planilla_id', $planillasIdsOrdenes)
            ->whereIn('maquina_id', $maquinaIds)
            ->pendiente()
            ->get()
            ->groupBy(fn($e) => $e->planilla_id . '_' . $e->maquina_id);

        $finMasTardio = null;
        $detalles = [];

        // Planillas que nos interesan
        $planillasIds = $elementos->pluck('planilla_id')->unique()->toArray();

        foreach ($maquinaIds as $maquinaId) {
            $ordenesMaquina = $ordenes->get($maquinaId, collect());

            if ($ordenesMaquina->isEmpty()) {
                continue;
            }

            $inicioCola = $colasMaquinas[$maquinaId] ?? Carbon::now();
            $primeraOrdenId = $ordenesMaquina->first()->id ?? null;

            foreach ($ordenesMaquina as $orden) {
                // OPTIMIZACIÓN: Usar elementos pre-cargados en vez de query
                $cacheKey = $orden->planilla_id . '_' . $maquinaId;
                $elementosPlanilla = $todosElementosPendientes->get($cacheKey, collect());

                if ($elementosPlanilla->isEmpty()) {
                    continue;
                }

                // Calcular duración
                $duracionSegundos = $elementosPlanilla->sum(function ($e) {
                    $tiempoFab = (float) ($e->tiempo_fabricacion ?? 1200);
                    $tiempoAmarrado = 1200; // 20 minutos
                    return $tiempoFab + $tiempoAmarrado;
                });
                $duracionSegundos = max($duracionSegundos, 3600); // mínimo 1 hora

                // Determinar fecha de inicio
                $esPrimerEvento = ($orden->id === $primeraOrdenId);

                if ($esPrimerEvento) {
                    // Primer evento: usar now() o fecha de inicio de fabricación
                    $fechaInicioFabricacion = $this->obtenerFechaInicioFabricacion($orden->planilla_id, $maquinaId);
                    $fechaInicio = $fechaInicioFabricacion ?? Carbon::now();
                } else {
                    // Resto: usar el fin del evento anterior
                    $fechaInicio = $inicioCola->copy();
                }

                // Generar tramos laborales
                $tramos = $this->generarTramosLaborales($fechaInicio, $duracionSegundos);

                if (empty($tramos)) {
                    continue;
                }

                $ultimoTramo = end($tramos);
                $fechaFinReal = $ultimoTramo['end'] instanceof Carbon
                    ? $ultimoTramo['end']->copy()
                    : Carbon::parse($ultimoTramo['end']);

                // Si esta planilla está en nuestra lista de interés
                if (in_array($orden->planilla_id, $planillasIds)) {
                    $detalles[] = [
                        'planilla_id' => $orden->planilla_id,
                        'maquina_id' => $maquinaId,
                        'fin_programado' => $fechaFinReal->format('d/m/Y H:i'),
                        'fin_programado_carbon' => $fechaFinReal,
                    ];

                    if (!$finMasTardio || $fechaFinReal->gt($finMasTardio)) {
                        $finMasTardio = $fechaFinReal->copy();
                    }
                }

                // Actualizar cursor para siguiente planilla
                $inicioCola = $fechaFinReal->copy();
            }
        }

        $hayRetraso = false;
        if ($finMasTardio && $fechaEntrega) {
            // Comparar con fin del día de entrega
            $fechaEntregaFin = $fechaEntrega->copy()->endOfDay();
            $hayRetraso = $finMasTardio->gt($fechaEntregaFin);
        }

        return [
            'fin_programado' => $finMasTardio,
            'fin_programado_str' => $finMasTardio?->format('d/m/Y H:i'),
            'hay_retraso' => $hayRetraso,
            'detalles' => $detalles,
        ];
    }

    /**
     * Verifica si una fecha de entrega es alcanzable para un grupo de elementos
     */
    public function verificarFechaEntrega(array $elementosIds, Carbon $nuevaFechaEntrega): array
    {
        $resultado = $this->calcularFinProgramadoElementos($elementosIds, $nuevaFechaEntrega);

        return [
            'es_alcanzable' => !$resultado['hay_retraso'],
            'fin_programado' => $resultado['fin_programado_str'],
            'fecha_entrega' => $nuevaFechaEntrega->format('d/m/Y'),
            'mensaje' => $resultado['hay_retraso']
                ? "⚠️ La fecha de entrega ({$nuevaFechaEntrega->format('d/m/Y')}) es anterior al fin de fabricación programado ({$resultado['fin_programado_str']})"
                : "✅ La fecha de entrega es alcanzable",
        ];
    }

    /**
     * Calcula las colas iniciales de cada máquina
     * (igual que ProduccionController)
     *
     * Nota: Las "subetiquetas" son registros en la tabla "etiquetas"
     * relacionadas via etiqueta_sub_id en elementos
     */
    private function calcularColasIniciales(array $maquinaIds): array
    {
        $fechasInicioPorMaquina = collect();

        // Obtener fecha_inicio más antigua de etiquetas fabricando por máquina
        // Las etiquetas se relacionan con elementos via etiqueta_sub_id
        try {
            $fechasInicioPorMaquina = DB::table('etiquetas')
                ->join('elementos', 'etiquetas.etiqueta_sub_id', '=', 'elementos.etiqueta_sub_id')
                ->whereIn('elementos.maquina_id', $maquinaIds)
                ->where('etiquetas.estado', 'fabricando')
                ->whereNotNull('etiquetas.fecha_inicio')
                ->whereNull('etiquetas.deleted_at')
                ->select('elementos.maquina_id', DB::raw('MIN(etiquetas.fecha_inicio) as fecha_inicio'))
                ->groupBy('elementos.maquina_id')
                ->pluck('fecha_inicio', 'maquina_id');
        } catch (\Throwable $e) {
            // Si hay error, usar fechas actuales
            Log::warning('FinProgramadoService: No se pudo consultar etiquetas', [
                'error' => $e->getMessage(),
            ]);
            $fechasInicioPorMaquina = collect();
        }

        $colasMaquinas = [];
        $now = Carbon::now();
        $maxFecha = $now->copy()->addYear();
        $minFecha = $now->copy()->subYears(2);

        foreach ($maquinaIds as $maquinaId) {
            $fechaInicio = $fechasInicioPorMaquina[$maquinaId] ?? null;

            if ($fechaInicio) {
                try {
                    $fechaInicioCola = Carbon::parse($fechaInicio);
                    // Validar que no sea una fecha inválida
                    if ($fechaInicioCola->gt($maxFecha) || $fechaInicioCola->lt($minFecha)) {
                        $fechaInicioCola = $now->copy();
                    }
                } catch (\Throwable $e) {
                    $fechaInicioCola = $now->copy();
                }
            } else {
                $fechaInicioCola = $now->copy();
            }

            $colasMaquinas[$maquinaId] = $fechaInicioCola;
        }

        return $colasMaquinas;
    }

    /**
     * Obtiene la fecha de inicio de fabricación para una planilla en una máquina
     */
    private function obtenerFechaInicioFabricacion(int $planillaId, int $maquinaId): ?Carbon
    {
        try {
            $fechaInicio = DB::table('etiquetas')
                ->join('elementos', 'etiquetas.etiqueta_sub_id', '=', 'elementos.etiqueta_sub_id')
                ->where('elementos.planilla_id', $planillaId)
                ->where('elementos.maquina_id', $maquinaId)
                ->whereIn('etiquetas.estado', ['fabricando', 'fabricado'])
                ->whereNotNull('etiquetas.fecha_inicio')
                ->whereNull('etiquetas.deleted_at')
                ->min('etiquetas.fecha_inicio');

            if ($fechaInicio) {
                $fecha = Carbon::parse($fechaInicio);
                $minFecha = Carbon::now()->subYears(2);
                if ($fecha->gt($minFecha)) {
                    return $fecha;
                }
            }
        } catch (\Throwable $e) {
            // Si hay error, retornar null
            Log::warning('FinProgramadoService: No se pudo consultar etiquetas para fecha inicio', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Inicializa el servicio cargando festivos y turnos
     * Útil para ProduccionController que necesita usar los métodos públicos
     * IMPORTANTE: Siempre recarga turnos para obtener el estado actual de la BD
     */
    public function init(): self
    {
        $this->cargarFestivos();
        // Forzar recarga de turnos para obtener estado actual
        $this->recargarTurnos();
        return $this;
    }

    /**
     * Carga los festivos en memoria
     */
    public function cargarFestivos(): void
    {
        if (!empty($this->festivosSet)) {
            return;
        }

        $anioActual = now()->year;
        $festivos = Festivo::whereIn('anio', [$anioActual, $anioActual + 1])
            ->pluck('fecha')
            ->map(fn($f) => $f->toDateString());

        $this->festivosSet = array_flip($festivos->all());
    }

    /**
     * Carga los turnos activos en memoria (con cache)
     */
    public function cargarTurnos(): void
    {
        if ($this->turnosActivos !== null) {
            return;
        }

        $this->turnosActivos = Turno::where('activo', true)->get();
    }

    /**
     * Fuerza la recarga de turnos desde la base de datos
     * Útil después de que el usuario ha cambiado el estado de un turno
     */
    public function recargarTurnos(): void
    {
        $this->turnosActivos = Turno::where('activo', true)->get();
    }

    /**
     * Genera tramos laborales respetando turnos y festivos
     * FUENTE ÚNICA DE VERDAD - usado por ProduccionController
     */
    public function generarTramosLaborales(Carbon $inicio, int $durSeg): array
    {
        $tramos = [];
        $restante = max(0, (int) $durSeg);

        // Asegurar timezone correcta
        $appTz = config('app.timezone') ?: 'Europe/Madrid';
        $inicio = $inicio->copy()->setTimezone($appTz);

        // Verificar si el inicio está dentro de horario laborable
        // IMPORTANTE: también verificar segmentos del día anterior que se extiendan al día actual
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
            $inicio = $this->siguienteLaborableInicio($inicio);
        }

        $cursor = $inicio->copy();
        $iter = 0;
        $iterMax = 10000;

        while ($restante > 0) {
            if (++$iter > $iterMax) {
                Log::error('FinProgramadoService: iteraciones excedidas', [
                    'cursor' => $cursor->toIso8601String(),
                    'restante' => $restante,
                ]);
                break;
            }

            // Obtener segmentos laborables del día basados en turnos activos
            $diaActual = $cursor->copy()->startOfDay();
            $segmentosHoy = $this->obtenerSegmentosLaborablesDia($cursor);
            $segmentosAyer = $this->obtenerSegmentosLaborablesDia($cursor->copy()->subDay());

            // Combinar segmentos y filtrar solo los que sean relevantes para el cursor actual
            $segmentos = collect($segmentosAyer)
                ->merge($segmentosHoy)
                ->filter(fn($seg) => $cursor->lt($seg['fin']))
                ->values()
                ->all();

            // Saltar no laborables completos SOLO si no tienen segmentos
            if ($this->esNoLaborable($cursor) && empty($segmentosHoy)) {
                $cursor = $this->siguienteLaborableInicio($cursor);
                continue;
            }

            if (empty($segmentos)) {
                // Si no hay segmentos (no hay turnos con horarios definidos), usar 24h
                $limiteDia = $cursor->copy()->startOfDay()->addDay();
                $tsLimite = (int) $limiteDia->getTimestamp();
                $tsCursor = (int) $cursor->getTimestamp();
                $capacidad = max(0, $tsLimite - $tsCursor);
                $consume = min($restante, $capacidad);

                if ($consume > 0) {
                    $start = $cursor->copy();
                    $end = $cursor->copy()->addSeconds($consume);
                    $tramos[] = ['start' => $start, 'end' => $end];

                    $restante -= $consume;
                    $cursor = $end->copy();
                }

                // Si queda trabajo y llegamos al final del día → siguiente laborable
                if ($restante > 0 && (int)$cursor->getTimestamp() >= $tsLimite) {
                    $cursor = $this->siguienteLaborableInicio($cursor);
                }

                // Protección adicional: si el cursor no avanzó, forzar avance
                if ($consume == 0) {
                    $cursor->addDay()->startOfDay();
                }
            } else {
                // Hay turnos activos - consumir solo durante los segmentos laborables
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

                    // Obtener turno del segmento para verificar configuración
                    $turnoSegmento = $segmento['turno'] ?? null;

                    // Si es sábado y el turno no trabaja sábados, saltar al siguiente día laborable
                    if ($cursor->isSaturday() && $turnoSegmento && !$turnoSegmento->trabajaSabado()) {
                        break;
                    }

                    // Si es domingo y el turno no trabaja domingos, saltar al siguiente día laborable
                    if ($cursor->isSunday() && $turnoSegmento && !$turnoSegmento->trabajaDomingo()) {
                        break;
                    }

                    // Calcular cuánto podemos consumir de este segmento
                    $capacidadSeg = max(0, $cursor->diffInSeconds($finSeg, false));
                    $consume = min($restante, $capacidadSeg);

                    if ($consume > 0) {
                        $start = $cursor->copy();
                        $end = $cursor->copy()->addSeconds($consume);
                        $tramos[] = ['start' => $start, 'end' => $end];

                        $restante -= $consume;
                        $cursor = $end->copy();
                    }

                    // Si no queda más tiempo, salir
                    if ($restante <= 0) {
                        break;
                    }
                }

                // Si aún queda tiempo después de procesar todos los segmentos del día
                if ($restante > 0) {
                    // Verificar si el cursor ya avanzó a un día diferente al que empezamos
                    $diaDelCursor = $cursor->copy()->startOfDay();

                    if ($diaDelCursor->gt($diaActual)) {
                        // El cursor ya está en otro día (por turno nocturno)
                        // Quedarnos en este día para procesar sus segmentos
                        continue;
                    }

                    // El cursor sigue en el mismo día, avanzar al siguiente
                    $siguienteDia = $diaActual->copy()->addDay();
                    $cursor = $siguienteDia->copy();

                    // Saltar días no laborables
                    $diasSaltados = 0;
                    while ($diasSaltados < 365) {
                        $segmentosDelDia = $this->obtenerSegmentosLaborablesDia($cursor);

                        if ($this->esNoLaborable($cursor) && empty($segmentosDelDia)) {
                            $cursor->addDay()->startOfDay();
                            $diasSaltados++;
                        } else {
                            break;
                        }
                    }

                    if ($diasSaltados >= 365) {
                        Log::error('FinProgramadoService: bucle infinito detectado buscando día laborable');
                        break;
                    }
                }
            }
        }

        return $tramos;
    }

    /**
     * Obtiene los segmentos laborables de un día según turnos activos
     * FUENTE ÚNICA DE VERDAD - usado por ProduccionController
     */
    public function obtenerSegmentosLaborablesDia(Carbon $dia): array
    {
        if ($this->turnosActivos === null || $this->turnosActivos->isEmpty()) {
            return [];
        }

        $segmentos = [];
        $dayOfWeek = $dia->dayOfWeek;

        $appTz = config('app.timezone') ?: 'Europe/Madrid';

        foreach ($this->turnosActivos as $turno) {
            if (empty($turno->hora_inicio) || empty($turno->hora_fin)) {
                continue;
            }

            // Verificar si el turno trabaja en este día usando la configuración
            if (!$turno->trabajaEnDia($dayOfWeek)) {
                continue;
            }

            // Extraer hora y minuto directamente del string para evitar problemas de timezone
            // hora_inicio es formato "HH:MM:SS" o "HH:MM"
            $partesInicio = explode(':', $turno->hora_inicio);
            $partesFin = explode(':', $turno->hora_fin);

            $horaInicioNum = (int) ($partesInicio[0] ?? 0);
            $minInicioNum = (int) ($partesInicio[1] ?? 0);
            $horaFinNum = (int) ($partesFin[0] ?? 0);
            $minFinNum = (int) ($partesFin[1] ?? 0);

            $inicio = $dia->copy()->setTimezone($appTz)->setTime($horaInicioNum, $minInicioNum, 0);
            $fin = $dia->copy()->setTimezone($appTz)->setTime($horaFinNum, $minFinNum, 0);

            // Si el turno termina al día siguiente (offset_dias_fin = 1)
            $esNocturno = ($turno->offset_dias_fin ?? 0) == 1;
            if ($esNocturno) {
                $fin->addDay();
            }

            // Si fin es antes que inicio, significa que cruza medianoche
            if ($fin->lte($inicio)) {
                $fin->addDay();
                $esNocturno = true;
            }

            // Si es turno nocturno que termina al día siguiente, verificar si ese día es laborable
            if ($esNocturno) {
                $diaSiguiente = $fin->dayOfWeek;

                // Si el día siguiente es sábado y el turno no trabaja sábados, NO incluir este turno
                if ($diaSiguiente == Carbon::SATURDAY && !$turno->trabajaSabado()) {
                    continue;
                }

                // Si el día siguiente es domingo y el turno no trabaja domingos, NO incluir este turno
                if ($diaSiguiente == Carbon::SUNDAY && !$turno->trabajaDomingo()) {
                    continue;
                }
            }

            $segmentos[] = [
                'inicio' => $inicio,
                'fin' => $fin,
                'turno' => $turno,
            ];
        }

        // Ordenar por hora de inicio
        usort($segmentos, fn($a, $b) => $a['inicio']->timestamp <=> $b['inicio']->timestamp);

        return $segmentos;
    }

    /**
     * Verifica si un día es no laborable
     * FUENTE ÚNICA DE VERDAD - usado por ProduccionController
     *
     * Un día es no laborable si:
     * - Es festivo, O
     * - Es fin de semana Y ningún turno trabaja ese día
     */
    public function esNoLaborable(Carbon $dia): bool
    {
        // Siempre es no laborable si es festivo
        if (isset($this->festivosSet[$dia->toDateString()])) {
            return true;
        }

        // Si no es fin de semana, es laborable (no festivo)
        if (!$dia->isWeekend()) {
            return false;
        }

        // Es fin de semana: verificar si algún turno trabaja este día
        if ($this->turnosActivos !== null && $this->turnosActivos->isNotEmpty()) {
            foreach ($this->turnosActivos as $turno) {
                if ($turno->trabajaEnDia($dia->dayOfWeek)) {
                    return false; // Al menos un turno trabaja este día
                }
            }
        }

        // Ningún turno trabaja en este día de fin de semana
        return true;
    }

    /**
     * Obtiene el siguiente momento laborable
     * FUENTE ÚNICA DE VERDAD - usado por ProduccionController
     */
    public function siguienteLaborableInicio(Carbon $dt): Carbon
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
                        return $x->lt($seg['inicio']) ? $seg['inicio']->copy() : $x->copy();
                    }
                }
            }

            // Si es día laborable (no festivo, no fin de semana)
            if (!$this->esNoLaborable($x)) {
                // Obtener segmentos del día
                $segmentos = $this->obtenerSegmentosLaborablesDia($x);

                if (!empty($segmentos)) {
                    // Si el cursor está antes del primer segmento, ir al primer segmento
                    $primerSegmento = $segmentos[0];
                    if ($x->lt($primerSegmento['inicio'])) {
                        return $primerSegmento['inicio']->copy();
                    }

                    // Buscar un segmento donde el cursor esté antes o dentro
                    foreach ($segmentos as $seg) {
                        if ($x->lt($seg['fin'])) {
                            return $x->lt($seg['inicio']) ? $seg['inicio']->copy() : $x->copy();
                        }
                    }

                    // Si llegamos aquí, el cursor está después de todos los segmentos del día
                    // Avanzar al siguiente día
                } else {
                    // No hay segmentos (no hay turnos activos), retornar el día a las 00:00
                    return $x->copy()->startOfDay();
                }
            }

            // Avanzar al siguiente día a las 00:00
            $x->addDay()->startOfDay();
            $iter++;
        }

        // Fallback
        return $x;
    }

    /**
     * Simula el adelanto de fabricación para un conjunto de elementos
     * OPTIMIZADO: Pre-carga todos los datos necesarios para evitar queries redundantes
     *
     * @param array $elementosIds - IDs de elementos del evento
     * @param Carbon $fechaEntrega - Fecha de entrega objetivo
     * @return array
     */
    public function simularAdelanto(array $elementosIds, Carbon $fechaEntrega): array
    {
        if (empty($elementosIds)) {
            return [
                'necesita_adelanto' => false,
                'ordenes_a_adelantar' => [],
                'colaterales' => [],
                'mensaje' => 'No hay elementos para analizar',
            ];
        }

        $this->cargarFestivos();
        $this->cargarTurnos();

        // Obtener elementos con sus planillas y obras
        $elementos = Elemento::with(['planilla.obra'])
            ->whereIn('id', $elementosIds)
            ->pendiente()
            ->get();

        if ($elementos->isEmpty()) {
            return [
                'necesita_adelanto' => false,
                'ordenes_a_adelantar' => [],
                'colaterales' => [],
                'mensaje' => 'No hay elementos pendientes',
            ];
        }

        // Agrupar por máquina
        $elementosPorMaquina = $elementos->groupBy('maquina_id');
        $maquinaIds = $elementosPorMaquina->keys()->filter()->toArray();

        if (empty($maquinaIds)) {
            return [
                'necesita_adelanto' => false,
                'ordenes_a_adelantar' => [],
                'colaterales' => [],
                'mensaje' => 'Elementos sin máquina asignada',
            ];
        }

        // Planillas involucradas (del evento que estamos moviendo)
        $planillasIds = $elementos->pluck('planilla_id')->unique()->toArray();

        // OPTIMIZACIÓN: Pre-cargar planillas y máquinas de una sola vez
        $planillasCache = Planilla::with('obra')
            ->whereIn('id', $planillasIds)
            ->get()
            ->keyBy('id');

        $maquinasCache = Maquina::whereIn('id', $maquinaIds)
            ->get()
            ->keyBy('id');

        // Obtener órdenes actuales
        $ordenes = OrdenPlanilla::whereIn('maquina_id', $maquinaIds)
            ->orderBy('maquina_id')
            ->orderBy('posicion')
            ->get()
            ->groupBy('maquina_id');

        // Calcular fin programado actual para cada planilla/máquina
        $finProgramadoActual = $this->calcularFinProgramadoElementos($elementosIds, $fechaEntrega);

        // Identificar qué ordenes están fuera de tiempo
        $ordenesConRetraso = [];
        $fechaEntregaFin = $fechaEntrega->copy()->endOfDay();

        foreach ($finProgramadoActual['detalles'] as $detalle) {
            if ($detalle['fin_programado_carbon']->gt($fechaEntregaFin)) {
                $ordenesConRetraso[] = [
                    'planilla_id' => $detalle['planilla_id'],
                    'maquina_id' => $detalle['maquina_id'],
                    'fin_actual' => $detalle['fin_programado'],
                    'fin_carbon' => $detalle['fin_programado_carbon'],
                ];
            }
        }

        if (empty($ordenesConRetraso)) {
            return [
                'necesita_adelanto' => false,
                'ordenes_a_adelantar' => [],
                'colaterales' => [],
                'mensaje' => 'Todas las planillas llegarán a tiempo',
                'fin_programado' => $finProgramadoActual['fin_programado_str'],
            ];
        }

        // Para cada orden con retraso, calcular posición óptima
        $ordenesAAdelantar = [];
        $cambiosPropuestos = []; // maquina_id => [cambios]

        $razonesNoAdelanto = []; // Guardar razones por las que no se puede adelantar

        // OPTIMIZACIÓN: Pre-cargar TODOS los datos necesarios UNA sola vez
        $todasPlanillasIds = $ordenes->flatten()->pluck('planilla_id')->unique()->toArray();
        $todosElementosCache = Elemento::whereIn('planilla_id', $todasPlanillasIds)
            ->whereIn('maquina_id', $maquinaIds)
            ->pendiente()
            ->get()
            ->groupBy(fn($e) => $e->maquina_id . '_' . $e->planilla_id);

        $colasCache = $this->calcularColasIniciales($maquinaIds);

        // Pre-calcular duraciones para todas las planillas/máquinas
        $duracionesGlobalCache = [];
        foreach ($maquinaIds as $mid) {
            $duracionesGlobalCache[$mid] = [];
            $ordenesMaq = $ordenes->get($mid, collect());
            foreach ($ordenesMaq as $ord) {
                $key = $mid . '_' . $ord->planilla_id;
                $elementosPlanilla = $todosElementosCache->get($key, collect());
                if ($elementosPlanilla->isEmpty()) {
                    $duracionesGlobalCache[$mid][$ord->planilla_id] = 0;
                    continue;
                }
                $duracion = $elementosPlanilla->sum(function ($e) {
                    return (float) ($e->tiempo_fabricacion ?? 1200) + 1200;
                });
                $duracionesGlobalCache[$mid][$ord->planilla_id] = max($duracion, 3600);
            }
        }

        foreach ($ordenesConRetraso as $ordenRetraso) {
            $maquinaId = $ordenRetraso['maquina_id'];
            $planillaId = $ordenRetraso['planilla_id'];

            $ordenesMaquina = $ordenes->get($maquinaId, collect());
            $ordenActual = $ordenesMaquina->firstWhere('planilla_id', $planillaId);

            if (!$ordenActual) {
                continue;
            }

            $posicionActual = $ordenActual->posicion;

            // Buscar posición óptima usando datos pre-cargados
            $resultado = $this->buscarPosicionOptimaRapida(
                $maquinaId,
                $planillaId,
                $fechaEntrega,
                $ordenesMaquina,
                $colasCache[$maquinaId] ?? Carbon::now(),
                $duracionesGlobalCache[$maquinaId] ?? []
            );

            $posicionOptima = $resultado['posicion'];

            if ($posicionOptima !== null && $posicionOptima < $posicionActual) {
                // OPTIMIZACIÓN: Usar cache en vez de queries individuales
                $planilla = $planillasCache->get($planillaId);
                $maquina = $maquinasCache->get($maquinaId);

                $ordenesAAdelantar[] = [
                    'planilla_id' => $planillaId,
                    'planilla_codigo' => $planilla->codigo ?? 'N/A',
                    'obra' => optional($planilla?->obra)->obra ?? 'Sin obra',
                    'maquina_id' => $maquinaId,
                    'maquina_nombre' => $maquina->nombre ?? 'Máquina ' . $maquinaId,
                    'posicion_actual' => $posicionActual,
                    'posicion_nueva' => $posicionOptima,
                    'fin_actual' => $ordenRetraso['fin_actual'],
                ];

                // Registrar cambio propuesto
                if (!isset($cambiosPropuestos[$maquinaId])) {
                    $cambiosPropuestos[$maquinaId] = [];
                }
                $cambiosPropuestos[$maquinaId][] = [
                    'planilla_id' => $planillaId,
                    'de' => $posicionActual,
                    'a' => $posicionOptima,
                ];
            } else if ($resultado['razon']) {
                // Guardar la razón por la que no se puede adelantar
                $planilla = $planillasCache->get($planillaId);
                $maquina = $maquinasCache->get($maquinaId);
                $razonesNoAdelanto[] = [
                    'planilla_id' => $planillaId,
                    'maquina_id' => $maquinaId,
                    'planilla' => $planilla->codigo ?? 'Planilla ' . $planillaId,
                    'maquina' => $maquina->nombre ?? 'Máquina ' . $maquinaId,
                    'razon' => $resultado['razon'],
                    'fin_minimo' => $resultado['fin_minimo'],
                ];
            }
        }

        if (empty($ordenesAAdelantar)) {
            // Agrupar razones por máquina para evitar mensajes repetitivos
            $razonesPorMaquina = [];
            foreach ($razonesNoAdelanto as $r) {
                $maquinaId = $r['maquina_id'];
                if (!isset($razonesPorMaquina[$maquinaId])) {
                    $razonesPorMaquina[$maquinaId] = [
                        'maquina_id' => $maquinaId,
                        'maquina' => $r['maquina'],
                        'planillas' => [],
                        'fin_minimo' => $r['fin_minimo'],
                        'peor_razon' => $r['razon'],
                    ];
                }
                $razonesPorMaquina[$maquinaId]['planillas'][] = $r['planilla_id'];

                // Guardar el peor fin_minimo (el más tardío)
                if ($r['fin_minimo'] && (!$razonesPorMaquina[$maquinaId]['fin_minimo'] ||
                    $r['fin_minimo'] > $razonesPorMaquina[$maquinaId]['fin_minimo'])) {
                    $razonesPorMaquina[$maquinaId]['fin_minimo'] = $r['fin_minimo'];
                    $razonesPorMaquina[$maquinaId]['peor_razon'] = $r['razon'];
                }
            }

            // Construir mensaje consolidado (sin mostrar número de planillas para mayor claridad)
            $mensajeDetallado = 'No se encontró una posición que mejore los tiempos.';
            if (!empty($razonesPorMaquina)) {
                $detalles = [];
                foreach ($razonesPorMaquina as $info) {
                    $detalles[] = "• {$info['maquina']}: {$info['peor_razon']}";
                }
                $mensajeDetallado = implode("\n", $detalles);
            }

            // Convertir razones agrupadas para el frontend
            $razonesAgrupadas = array_values(array_map(function($info) {
                return [
                    'maquina_id' => $info['maquina_id'],
                    'maquina' => $info['maquina'],
                    'planillas_ids' => $info['planillas'],
                    'fin_minimo' => $info['fin_minimo'],
                    'razon' => $info['peor_razon'],
                ];
            }, $razonesPorMaquina));

            return [
                'necesita_adelanto' => false,
                'ordenes_a_adelantar' => [],
                'colaterales' => [],
                'mensaje' => $mensajeDetallado,
                'razones' => $razonesAgrupadas,
            ];
        }

        // Detectar colaterales: otras planillas que se retrasarían
        $colaterales = $this->detectarColaterales($cambiosPropuestos, $ordenes, $planillasIds);

        return [
            'necesita_adelanto' => true,
            'ordenes_a_adelantar' => $ordenesAAdelantar,
            'colaterales' => $colaterales,
            'fecha_entrega' => $fechaEntrega->format('d/m/Y'),
            'mensaje' => count($ordenesAAdelantar) . ' orden(es) necesitan adelantarse',
        ];
    }

    /**
     * Busca la posición óptima para que una planilla llegue a tiempo
     * OPTIMIZADO: Usa búsqueda binaria y pre-carga datos para evitar queries
     *
     * @return array ['posicion' => int|null, 'razon' => string, 'fin_minimo' => string|null]
     */
    private function buscarPosicionOptima(
        int $maquinaId,
        int $planillaId,
        Carbon $fechaEntrega,
        Collection $ordenesMaquina
    ): array {
        $ordenActual = $ordenesMaquina->firstWhere('planilla_id', $planillaId);
        if (!$ordenActual) {
            return [
                'posicion' => null,
                'razon' => 'La planilla no tiene orden de fabricación asignada en esta máquina',
                'fin_minimo' => null,
            ];
        }

        $posicionActual = $ordenActual->posicion;
        $fechaEntregaFin = $fechaEntrega->copy()->endOfDay();

        // Si solo hay 1 posición, no hay nada que optimizar
        if ($posicionActual <= 1) {
            return [
                'posicion' => null,
                'razon' => 'La planilla ya está en la primera posición de la cola, no se puede adelantar más',
                'fin_minimo' => null,
            ];
        }

        // OPTIMIZACIÓN: Pre-cargar todos los datos necesarios UNA sola vez
        $planillasIds = $ordenesMaquina->pluck('planilla_id')->toArray();
        $elementosCache = Elemento::whereIn('planilla_id', $planillasIds)
            ->where('maquina_id', $maquinaId)
            ->pendiente()
            ->get()
            ->groupBy('planilla_id');

        $colasCache = $this->calcularColasIniciales([$maquinaId]);
        $inicioCola = $colasCache[$maquinaId] ?? Carbon::now();

        // Pre-calcular duraciones para cada planilla (evita recalcular en cada iteración)
        $duracionesCache = [];
        foreach ($planillasIds as $pid) {
            $elementosPlanilla = $elementosCache->get($pid, collect());
            if ($elementosPlanilla->isEmpty()) {
                $duracionesCache[$pid] = 0;
                continue;
            }
            $duracion = $elementosPlanilla->sum(function ($e) {
                return (float) ($e->tiempo_fabricacion ?? 1200) + 1200;
            });
            $duracionesCache[$pid] = max($duracion, 3600);
        }

        // Calcular el fin mínimo posible (si se pone en posición 1)
        $finMinimo = $this->simularFinRapido(
            $maquinaId,
            $planillaId,
            1,
            $ordenesMaquina,
            $inicioCola,
            $duracionesCache
        );

        // Si incluso en posición 1 no llega a tiempo, no hay solución
        if ($finMinimo && $finMinimo->gt($fechaEntregaFin)) {
            // Calcular el retraso de forma clara
            $diasRetraso = (int) $fechaEntregaFin->diffInDays($finMinimo, false);
            $horasRetraso = (int) abs($fechaEntregaFin->diffInHours($finMinimo) % 24);

            $textoRetraso = '';
            if ($diasRetraso > 0) {
                $textoRetraso = "{$diasRetraso} día(s)";
                if ($horasRetraso > 0) {
                    $textoRetraso .= " y {$horasRetraso} hora(s)";
                }
            } else {
                $textoRetraso = "{$horasRetraso} hora(s)";
            }

            return [
                'posicion' => null,
                'razon' => "Incluso en primera posición, terminaría el {$finMinimo->format('d/m/Y H:i')} " .
                          "(retraso de {$textoRetraso} vs entrega {$fechaEntrega->format('d/m/Y')})",
                'fin_minimo' => $finMinimo->format('d/m/Y H:i'),
            ];
        }

        // BÚSQUEDA BINARIA: encontrar la posición más alta que cumple
        $izq = 1;
        $der = $posicionActual - 1;
        $mejorPosicion = null;

        while ($izq <= $der) {
            $mid = (int) floor(($izq + $der) / 2);

            $finSimulado = $this->simularFinRapido(
                $maquinaId,
                $planillaId,
                $mid,
                $ordenesMaquina,
                $inicioCola,
                $duracionesCache
            );

            if ($finSimulado && $finSimulado->lte($fechaEntregaFin)) {
                // Esta posición funciona, buscar si hay una más alta
                $mejorPosicion = $mid;
                $izq = $mid + 1;
            } else {
                // No funciona, necesitamos posición más baja (más adelanto)
                $der = $mid - 1;
            }
        }

        if ($mejorPosicion === null) {
            return [
                'posicion' => null,
                'razon' => 'No se encontró una posición que permita entregar a tiempo',
                'fin_minimo' => $finMinimo?->format('d/m/Y H:i'),
            ];
        }

        return [
            'posicion' => $mejorPosicion,
            'razon' => null,
            'fin_minimo' => null,
        ];
    }

    /**
     * Versión ultra-rápida de buscarPosicionOptima que recibe datos pre-cargados
     * OPTIMIZADO: Sin ninguna query de base de datos
     */
    private function buscarPosicionOptimaRapida(
        int $maquinaId,
        int $planillaId,
        Carbon $fechaEntrega,
        Collection $ordenesMaquina,
        Carbon $inicioCola,
        array $duracionesCache
    ): array {
        $ordenActual = $ordenesMaquina->firstWhere('planilla_id', $planillaId);
        if (!$ordenActual) {
            return [
                'posicion' => null,
                'razon' => 'La planilla no tiene orden de fabricación asignada en esta máquina',
                'fin_minimo' => null,
            ];
        }

        $posicionActual = $ordenActual->posicion;
        $fechaEntregaFin = $fechaEntrega->copy()->endOfDay();

        // Si solo hay 1 posición, no hay nada que optimizar
        if ($posicionActual <= 1) {
            return [
                'posicion' => null,
                'razon' => 'La planilla ya está en la primera posición de la cola, no se puede adelantar más',
                'fin_minimo' => null,
            ];
        }

        // Calcular el fin mínimo posible (si se pone en posición 1)
        $finMinimo = $this->simularFinRapido(
            $maquinaId,
            $planillaId,
            1,
            $ordenesMaquina,
            $inicioCola,
            $duracionesCache
        );

        // Si incluso en posición 1 no llega a tiempo, no hay solución
        if ($finMinimo && $finMinimo->gt($fechaEntregaFin)) {
            $diasRetraso = (int) $fechaEntregaFin->diffInDays($finMinimo, false);
            $horasRetraso = (int) abs($fechaEntregaFin->diffInHours($finMinimo) % 24);

            $textoRetraso = '';
            if ($diasRetraso > 0) {
                $textoRetraso = "{$diasRetraso} día(s)";
                if ($horasRetraso > 0) {
                    $textoRetraso .= " y {$horasRetraso} hora(s)";
                }
            } else {
                $textoRetraso = "{$horasRetraso} hora(s)";
            }

            return [
                'posicion' => null,
                'razon' => "Incluso en primera posición, terminaría el {$finMinimo->format('d/m/Y H:i')} " .
                          "(retraso de {$textoRetraso} vs entrega {$fechaEntrega->format('d/m/Y')})",
                'fin_minimo' => $finMinimo->format('d/m/Y H:i'),
            ];
        }

        // BÚSQUEDA BINARIA: encontrar la posición más alta que cumple
        $izq = 1;
        $der = $posicionActual - 1;
        $mejorPosicion = null;

        while ($izq <= $der) {
            $mid = (int) floor(($izq + $der) / 2);

            $finSimulado = $this->simularFinRapido(
                $maquinaId,
                $planillaId,
                $mid,
                $ordenesMaquina,
                $inicioCola,
                $duracionesCache
            );

            if ($finSimulado && $finSimulado->lte($fechaEntregaFin)) {
                $mejorPosicion = $mid;
                $izq = $mid + 1;
            } else {
                $der = $mid - 1;
            }
        }

        if ($mejorPosicion === null) {
            return [
                'posicion' => null,
                'razon' => 'No se encontró una posición que permita entregar a tiempo',
                'fin_minimo' => $finMinimo?->format('d/m/Y H:i'),
            ];
        }

        return [
            'posicion' => $mejorPosicion,
            'razon' => null,
            'fin_minimo' => null,
        ];
    }

    /**
     * Simula el fin programado de forma rápida usando datos pre-cargados
     * OPTIMIZADO: Sin queries de base de datos
     */
    private function simularFinRapido(
        int $maquinaId,
        int $planillaId,
        int $nuevaPosicion,
        Collection $ordenesMaquina,
        Carbon $inicioCola,
        array $duracionesCache
    ): ?Carbon {
        $posicionOriginal = $ordenesMaquina->firstWhere('planilla_id', $planillaId)?->posicion;
        if (!$posicionOriginal) {
            return null;
        }

        // Reordenar virtualmente las órdenes
        $ordenesSimuladas = $ordenesMaquina->map(function ($o) use ($planillaId, $nuevaPosicion, $posicionOriginal) {
            $pos = $o->posicion;
            if ($o->planilla_id == $planillaId) {
                $pos = $nuevaPosicion;
            } elseif ($o->posicion >= $nuevaPosicion && $o->posicion < $posicionOriginal) {
                $pos = $o->posicion + 1;
            }
            return ['planilla_id' => $o->planilla_id, 'posicion' => $pos];
        })->sortBy('posicion')->values();

        // Simular la cola hasta llegar a la planilla objetivo
        $cursor = $inicioCola->copy();
        $esPrimero = true;

        foreach ($ordenesSimuladas as $orden) {
            $duracion = $duracionesCache[$orden['planilla_id']] ?? 0;

            if ($duracion <= 0) {
                continue;
            }

            if ($esPrimero) {
                $fechaInicioFab = $this->obtenerFechaInicioFabricacion($orden['planilla_id'], $maquinaId);
                $fechaInicio = $fechaInicioFab ?? Carbon::now();
                $esPrimero = false;
            } else {
                $fechaInicio = $cursor->copy();
            }

            $tramos = $this->generarTramosLaborales($fechaInicio, $duracion);

            if (empty($tramos)) {
                continue;
            }

            $ultimoTramo = end($tramos);
            $fechaFin = $ultimoTramo['end'] instanceof Carbon
                ? $ultimoTramo['end']->copy()
                : Carbon::parse($ultimoTramo['end']);

            if ($orden['planilla_id'] == $planillaId) {
                return $fechaFin;
            }

            $cursor = $fechaFin->copy();
        }

        return null;
    }

    /**
     * Detecta planillas colaterales que se retrasarían con los cambios propuestos
     */
    private function detectarColaterales(
        array $cambiosPropuestos,
        Collection $ordenesPorMaquina,
        array $planillasExcluir
    ): array {
        $colaterales = [];

        foreach ($cambiosPropuestos as $maquinaId => $cambios) {
            $ordenesMaquina = $ordenesPorMaquina->get($maquinaId, collect());

            foreach ($cambios as $cambio) {
                // Las planillas entre posicion_nueva y posicion_actual se retrasarán
                $posDesde = $cambio['a']; // nueva posición
                $posHasta = $cambio['de'] - 1; // posición actual - 1

                $afectadas = $ordenesMaquina->filter(function ($o) use ($posDesde, $posHasta, $planillasExcluir) {
                    return $o->posicion >= $posDesde
                        && $o->posicion <= $posHasta
                        && !in_array($o->planilla_id, $planillasExcluir);
                });

                foreach ($afectadas as $afectada) {
                    $planilla = Planilla::with('obra')->find($afectada->planilla_id);
                    if (!$planilla) continue;

                    // Verificar si esta planilla tiene fecha de entrega y si se verá afectada
                    $elementosAfectados = Elemento::where('planilla_id', $afectada->planilla_id)
                        ->where('maquina_id', $maquinaId)
                        ->pendiente()
                        ->get();

                    if ($elementosAfectados->isEmpty()) continue;

                    $fechaEntregaRaw = $planilla->getRawOriginal('fecha_estimada_entrega');
                    $fechaEntregaPlanilla = $fechaEntregaRaw ? Carbon::parse($fechaEntregaRaw) : null;

                    // Calcular nuevo fin si se retrasa una posición
                    // (simplificación: asumimos que se retrasa)
                    $colaterales[] = [
                        'planilla_id' => $afectada->planilla_id,
                        'planilla_codigo' => $planilla->codigo ?? 'N/A',
                        'obra' => optional($planilla->obra)->obra ?? 'Sin obra',
                        'maquina_id' => $maquinaId,
                        'maquina_nombre' => Maquina::find($maquinaId)->nombre ?? 'Máquina ' . $maquinaId,
                        'posicion_actual' => $afectada->posicion,
                        'fecha_entrega' => $fechaEntregaPlanilla ? $fechaEntregaPlanilla->format('d/m/Y') : 'Sin fecha',
                    ];
                }
            }
        }

        // Eliminar duplicados
        return collect($colaterales)->unique('planilla_id')->values()->toArray();
    }

    /**
     * Ejecuta el adelanto de las órdenes especificadas
     *
     * @param array $ordenesAAdelantar - Array con planilla_id, maquina_id, posicion_nueva
     * @return array
     */
    public function ejecutarAdelanto(array $ordenesAAdelantar): array
    {
        $resultados = [];
        $maquinasAfectadas = [];

        Log::info('[ejecutarAdelanto] Iniciando con ' . count($ordenesAAdelantar) . ' órdenes', $ordenesAAdelantar);

        DB::beginTransaction();
        try {
            foreach ($ordenesAAdelantar as $orden) {
                $planillaId = $orden['planilla_id'];
                $maquinaId = $orden['maquina_id'];
                $nuevaPosicion = $orden['posicion_nueva'];

                Log::info("[ejecutarAdelanto] Procesando planilla {$planillaId} en maquina {$maquinaId} a posicion {$nuevaPosicion}");

                // Obtener orden actual
                $ordenActual = OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $maquinaId)
                    ->first();

                if (!$ordenActual) {
                    Log::warning("[ejecutarAdelanto] Orden no encontrada para planilla {$planillaId} en maquina {$maquinaId}");
                    $resultados[] = [
                        'planilla_id' => $planillaId,
                        'success' => false,
                        'mensaje' => "Orden no encontrada para planilla {$planillaId} en máquina {$maquinaId}",
                    ];
                    continue;
                }

                $posicionAnterior = $ordenActual->posicion;

                if ($nuevaPosicion >= $posicionAnterior) {
                    Log::warning("[ejecutarAdelanto] Posición nueva ({$nuevaPosicion}) no es menor que actual ({$posicionAnterior}) para planilla {$planillaId}");
                    $resultados[] = [
                        'planilla_id' => $planillaId,
                        'success' => false,
                        'mensaje' => "La nueva posición ({$nuevaPosicion}) no es menor que la actual ({$posicionAnterior})",
                    ];
                    continue;
                }

                // Desplazar las órdenes entre nuevaPosicion y posicionAnterior-1
                OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->where('posicion', '>=', $nuevaPosicion)
                    ->where('posicion', '<', $posicionAnterior)
                    ->increment('posicion');

                // Mover la orden a la nueva posición
                $ordenActual->posicion = $nuevaPosicion;
                $ordenActual->save();

                // Registrar máquina afectada para consolidación posterior
                $maquinasAfectadas[$maquinaId] = true;

                $resultados[] = [
                    'planilla_id' => $planillaId,
                    'maquina_id' => $maquinaId,
                    'success' => true,
                    'posicion_anterior' => $posicionAnterior,
                    'posicion_nueva' => $nuevaPosicion,
                ];

                Log::info('Adelanto ejecutado', [
                    'planilla_id' => $planillaId,
                    'maquina_id' => $maquinaId,
                    'posicion_anterior' => $posicionAnterior,
                    'posicion_nueva' => $nuevaPosicion,
                ]);
            }

            // Consolidar posiciones adyacentes en todas las máquinas afectadas
            foreach (array_keys($maquinasAfectadas) as $maquinaId) {
                $this->consolidarPosicionesAdyacentes($maquinaId);
            }

            DB::commit();

            return [
                'success' => true,
                'resultados' => $resultados,
                'mensaje' => 'Adelanto ejecutado correctamente',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al ejecutar adelanto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'resultados' => [],
                'mensaje' => 'Error al ejecutar el adelanto: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Consolida posiciones adyacentes de la misma planilla en una máquina
     * Si hay dos registros consecutivos para la misma planilla, elimina el duplicado
     */
    private function consolidarPosicionesAdyacentes(int $maquinaId): void
    {
        $ordenes = OrdenPlanilla::where('maquina_id', $maquinaId)
            ->orderBy('posicion')
            ->get();

        if ($ordenes->count() < 2) {
            return;
        }

        $ordenesAEliminar = [];
        $ordenAnterior = null;

        foreach ($ordenes as $orden) {
            if ($ordenAnterior && $ordenAnterior->planilla_id === $orden->planilla_id) {
                // Esta orden es de la misma planilla que la anterior, marcar para eliminar
                $ordenesAEliminar[] = $orden->id;
            } else {
                $ordenAnterior = $orden;
            }
        }

        if (!empty($ordenesAEliminar)) {
            OrdenPlanilla::whereIn('id', $ordenesAEliminar)->delete();
            $this->recompactarPosiciones($maquinaId);

            Log::info('[FinProgramadoService] Consolidadas posiciones adyacentes', [
                'maquina_id' => $maquinaId,
                'ordenes_eliminadas' => count($ordenesAEliminar),
            ]);
        }
    }

    /**
     * Recompacta las posiciones de una máquina para que sean consecutivas (1, 2, 3, ...)
     */
    private function recompactarPosiciones(int $maquinaId): void
    {
        $ordenes = OrdenPlanilla::where('maquina_id', $maquinaId)
            ->orderBy('posicion')
            ->get();

        $posicion = 1;
        foreach ($ordenes as $orden) {
            if ($orden->posicion !== $posicion) {
                $orden->posicion = $posicion;
                $orden->save();
            }
            $posicion++;
        }
    }

    /**
     * Verifica si es posible retrasar la fabricación de los elementos dados
     *
     * @param array $elementosIds
     * @return array
     */
    public function verificarPosibilidadRetraso(array $elementosIds): array
    {
        if (empty($elementosIds)) {
            return [
                'puede_retrasar' => false,
                'planillas_afectadas' => [],
                'mensaje' => 'No hay elementos para analizar',
            ];
        }

        // Obtener elementos con sus planillas y máquinas
        $elementos = Elemento::with(['planilla.obra'])
            ->whereIn('id', $elementosIds)
            ->pendiente()
            ->get();

        if ($elementos->isEmpty()) {
            return [
                'puede_retrasar' => false,
                'planillas_afectadas' => [],
                'mensaje' => 'No hay elementos pendientes',
            ];
        }

        // Agrupar por planilla y máquina
        $planillasIds = $elementos->pluck('planilla_id')->unique()->toArray();
        $maquinaIds = $elementos->pluck('maquina_id')->filter()->unique()->toArray();

        if (empty($maquinaIds)) {
            return [
                'puede_retrasar' => false,
                'planillas_afectadas' => [],
                'mensaje' => 'Los elementos no tienen máquina asignada',
            ];
        }

        // Verificar si las planillas tienen órdenes en máquinas
        $ordenes = OrdenPlanilla::whereIn('planilla_id', $planillasIds)
            ->whereIn('maquina_id', $maquinaIds)
            ->with(['planilla.obra', 'maquina'])
            ->get();

        if ($ordenes->isEmpty()) {
            return [
                'puede_retrasar' => false,
                'planillas_afectadas' => [],
                'mensaje' => 'Las planillas no tienen órdenes de fabricación asignadas',
            ];
        }

        // Verificar que no estén en primera posición (ya no se pueden retrasar más en términos relativos)
        // Pero sí se pueden mover al final
        $planillasAfectadas = [];
        foreach ($ordenes as $orden) {
            $totalOrdenes = OrdenPlanilla::where('maquina_id', $orden->maquina_id)->count();

            // Solo tiene sentido retrasar si no está ya en la última posición
            if ($orden->posicion < $totalOrdenes) {
                $planillasAfectadas[] = [
                    'planilla_id' => $orden->planilla_id,
                    'planilla_codigo' => $orden->planilla->codigo ?? 'N/A',
                    'obra' => $orden->planilla->obra->obra ?? 'Sin obra',
                    'maquina_id' => $orden->maquina_id,
                    'maquina_nombre' => $orden->maquina->nombre ?? 'Máquina ' . $orden->maquina_id,
                    'posicion_actual' => $orden->posicion,
                    'total_posiciones' => $totalOrdenes,
                ];
            }
        }

        return [
            'puede_retrasar' => !empty($planillasAfectadas),
            'planillas_afectadas' => $planillasAfectadas,
            'mensaje' => empty($planillasAfectadas)
                ? 'Las planillas ya están al final de la cola'
                : count($planillasAfectadas) . ' planilla(s) pueden retrasarse',
        ];
    }

    /**
     * Simula el retraso de fabricación mostrando qué planillas se beneficiarían
     *
     * @param array $elementosIds
     * @return array
     */
    public function simularRetraso(array $elementosIds): array
    {
        $verificacion = $this->verificarPosibilidadRetraso($elementosIds);

        if (!$verificacion['puede_retrasar']) {
            return [
                'puede_retrasar' => false,
                'ordenes_a_retrasar' => [],
                'beneficiados' => [],
                'mensaje' => $verificacion['mensaje'],
            ];
        }

        $ordenesARetrasar = [];
        $beneficiados = [];

        foreach ($verificacion['planillas_afectadas'] as $info) {
            $maquinaId = $info['maquina_id'];
            $posicionActual = $info['posicion_actual'];
            $totalPosiciones = $info['total_posiciones'];

            // La nueva posición será la última
            $nuevaPosicion = $totalPosiciones;

            $ordenesARetrasar[] = [
                'planilla_id' => $info['planilla_id'],
                'planilla_codigo' => $info['planilla_codigo'],
                'obra' => $info['obra'],
                'maquina_id' => $maquinaId,
                'maquina_nombre' => $info['maquina_nombre'],
                'posicion_actual' => $posicionActual,
                'posicion_nueva' => $nuevaPosicion,
            ];

            // Obtener las planillas que se beneficiarían (las que están detrás y subirán)
            $ordenesBeneficiadas = OrdenPlanilla::where('maquina_id', $maquinaId)
                ->where('posicion', '>', $posicionActual)
                ->with(['planilla.obra'])
                ->orderBy('posicion')
                ->get();

            foreach ($ordenesBeneficiadas as $ordenBenef) {
                $beneficiados[] = [
                    'planilla_id' => $ordenBenef->planilla_id,
                    'planilla_codigo' => $ordenBenef->planilla->codigo ?? 'N/A',
                    'obra' => $ordenBenef->planilla->obra->obra ?? 'Sin obra',
                    'maquina_nombre' => $info['maquina_nombre'],
                    'posicion_actual' => $ordenBenef->posicion,
                    'posicion_nueva' => $ordenBenef->posicion - 1,
                ];
            }
        }

        // Eliminar duplicados en beneficiados
        $beneficiados = collect($beneficiados)->unique('planilla_id')->values()->toArray();

        return [
            'puede_retrasar' => true,
            'ordenes_a_retrasar' => $ordenesARetrasar,
            'beneficiados' => $beneficiados,
            'mensaje' => count($ordenesARetrasar) . ' orden(es) se moverán al final de la cola',
        ];
    }

    /**
     * Ejecuta el retraso de las órdenes especificadas (moverlas al final de la cola)
     *
     * @param array $elementosIds - IDs de elementos cuyas planillas se retrasarán
     * @return array
     */
    public function ejecutarRetraso(array $elementosIds): array
    {
        $simulacion = $this->simularRetraso($elementosIds);

        if (!$simulacion['puede_retrasar']) {
            return [
                'success' => false,
                'resultados' => [],
                'mensaje' => $simulacion['mensaje'],
            ];
        }

        $resultados = [];
        $maquinasAfectadas = [];

        Log::info('[ejecutarRetraso] Iniciando con ' . count($simulacion['ordenes_a_retrasar']) . ' órdenes');

        DB::beginTransaction();
        try {
            foreach ($simulacion['ordenes_a_retrasar'] as $ordenInfo) {
                $planillaId = $ordenInfo['planilla_id'];
                $maquinaId = $ordenInfo['maquina_id'];
                $posicionActual = $ordenInfo['posicion_actual'];
                $nuevaPosicion = $ordenInfo['posicion_nueva'];

                Log::info("[ejecutarRetraso] Procesando planilla {$planillaId} en maquina {$maquinaId}: pos {$posicionActual} -> {$nuevaPosicion}");

                // Obtener orden actual
                $ordenActual = OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $maquinaId)
                    ->first();

                if (!$ordenActual) {
                    Log::warning("[ejecutarRetraso] Orden no encontrada para planilla {$planillaId} en maquina {$maquinaId}");
                    $resultados[] = [
                        'planilla_id' => $planillaId,
                        'success' => false,
                        'mensaje' => "Orden no encontrada",
                    ];
                    continue;
                }

                // Desplazar las órdenes que están detrás hacia arriba
                OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->where('posicion', '>', $posicionActual)
                    ->where('posicion', '<=', $nuevaPosicion)
                    ->decrement('posicion');

                // Mover la orden a la nueva posición (al final)
                $ordenActual->posicion = $nuevaPosicion;
                $ordenActual->save();

                // Registrar máquina afectada para consolidación
                $maquinasAfectadas[$maquinaId] = true;

                $resultados[] = [
                    'planilla_id' => $planillaId,
                    'maquina_id' => $maquinaId,
                    'success' => true,
                    'posicion_anterior' => $posicionActual,
                    'posicion_nueva' => $nuevaPosicion,
                ];

                Log::info('Retraso ejecutado', [
                    'planilla_id' => $planillaId,
                    'maquina_id' => $maquinaId,
                    'posicion_anterior' => $posicionActual,
                    'posicion_nueva' => $nuevaPosicion,
                ]);
            }

            // Consolidar posiciones adyacentes
            foreach (array_keys($maquinasAfectadas) as $maquinaId) {
                $this->consolidarPosicionesAdyacentes($maquinaId);
            }

            DB::commit();

            return [
                'success' => true,
                'resultados' => $resultados,
                'mensaje' => 'Retraso ejecutado correctamente. ' . count($resultados) . ' planilla(s) movidas al final de la cola.',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al ejecutar retraso', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'resultados' => [],
                'mensaje' => 'Error al ejecutar el retraso: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Flag para simular turnos extra en sábados
     */
    private bool $simularSabado = false;
    private ?array $turnoSabadoSimulado = null;
    private array $fechasSabadoSimuladas = []; // Fechas específicas de sábados a simular
    private array $sabadosConTurnos = []; // Nuevo: sábados con múltiples turnos { "2026-01-25": [{hora_inicio, hora_fin}, ...] }

    /**
     * Habilita la simulación de trabajo en sábado con un turno específico (método legado)
     *
     * @param string $horaInicio Hora de inicio del turno (ej: "08:00")
     * @param string $horaFin Hora de fin del turno (ej: "14:00")
     * @param array $fechasEspecificas Array de fechas Carbon específicas (opcional, si vacío = todos los sábados)
     */
    public function habilitarSimulacionSabado(string $horaInicio = '08:00', string $horaFin = '14:00', array $fechasEspecificas = []): self
    {
        $this->simularSabado = true;
        $this->turnoSabadoSimulado = [
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
        ];
        $this->fechasSabadoSimuladas = $fechasEspecificas;
        $this->sabadosConTurnos = [];

        return $this;
    }

    /**
     * Habilita la simulación de sábados con múltiples turnos por fecha
     * Nuevo formato más flexible que permite seleccionar turnos específicos por sábado
     *
     * @param array $sabadosConTurnos Array asociativo { "2026-01-25": [{hora_inicio, hora_fin, nombre}, ...], ... }
     */
    public function habilitarSimulacionSabadoConTurnos(array $sabadosConTurnos): self
    {
        $this->simularSabado = true;
        $this->turnoSabadoSimulado = null; // No usar turno único
        $this->fechasSabadoSimuladas = [];
        $this->sabadosConTurnos = $sabadosConTurnos;

        return $this;
    }

    /**
     * Deshabilita la simulación de sábado
     */
    public function deshabilitarSimulacionSabado(): self
    {
        $this->simularSabado = false;
        $this->turnoSabadoSimulado = null;
        $this->fechasSabadoSimuladas = [];
        $this->sabadosConTurnos = [];
        return $this;
    }

    /**
     * Verifica si una fecha específica es un sábado simulado
     */
    private function esSabadoSimulado(Carbon $dia): bool
    {
        if (!$this->simularSabado) {
            return false;
        }

        if (!$dia->isSaturday()) {
            return false;
        }

        $fechaStr = $dia->toDateString();

        // Si hay sabadosConTurnos definidos, verificar si esta fecha está en la lista
        if (!empty($this->sabadosConTurnos)) {
            return isset($this->sabadosConTurnos[$fechaStr]);
        }

        // Método legado: Si no hay fechas específicas, todos los sábados están simulados
        if (empty($this->fechasSabadoSimuladas)) {
            return true;
        }

        // Verificar si esta fecha está en la lista de fechas específicas
        foreach ($this->fechasSabadoSimuladas as $fechaSimulada) {
            if ($fechaSimulada->toDateString() === $fechaStr) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene los turnos simulados para un sábado específico
     * @return array Array de turnos [{hora_inicio, hora_fin, nombre}, ...]
     */
    private function obtenerTurnosSimuladosSabado(Carbon $dia): array
    {
        $fechaStr = $dia->toDateString();

        // Si hay sabadosConTurnos, usar esos
        if (!empty($this->sabadosConTurnos) && isset($this->sabadosConTurnos[$fechaStr])) {
            return $this->sabadosConTurnos[$fechaStr];
        }

        // Método legado: devolver turno único si está definido
        if ($this->turnoSabadoSimulado) {
            return [$this->turnoSabadoSimulado];
        }

        return [];
    }

    /**
     * Obtiene los segmentos laborables de un día, incluyendo sábado simulado si está activo
     * OVERRIDE del método original cuando hay simulación activa
     */
    public function obtenerSegmentosLaborablesDiaConSimulacion(Carbon $dia): array
    {
        // Si no es un sábado simulado, usar método normal
        if (!$this->esSabadoSimulado($dia)) {
            return $this->obtenerSegmentosLaborablesDia($dia);
        }

        // Es un sábado simulado - obtener segmentos normales primero
        $segmentosNormales = $this->obtenerSegmentosLaborablesDia($dia);

        // Obtener los turnos simulados para este sábado
        $turnosSimulados = $this->obtenerTurnosSimuladosSabado($dia);

        if (empty($turnosSimulados)) {
            return $segmentosNormales;
        }

        // Crear segmentos para cada turno simulado
        foreach ($turnosSimulados as $turno) {
            $horaInicio = Carbon::parse($turno['hora_inicio']);
            $horaFin = Carbon::parse($turno['hora_fin']);

            $inicio = $dia->copy()->setTime($horaInicio->hour, $horaInicio->minute, 0);
            $fin = $dia->copy()->setTime($horaFin->hour, $horaFin->minute, 0);

            // Manejar turnos nocturnos (ej: 22:00 a 06:00)
            if ($horaFin->lt($horaInicio)) {
                $fin->addDay();
            }

            $segmentosNormales[] = [
                'inicio' => $inicio,
                'fin' => $fin,
                'turno' => null, // Turno simulado
            ];
        }

        // Ordenar por hora de inicio
        usort($segmentosNormales, fn($a, $b) => $a['inicio']->timestamp <=> $b['inicio']->timestamp);

        return $segmentosNormales;
    }

    /**
     * Genera tramos laborales CON simulación de sábado activa
     * Versión modificada de generarTramosLaborales que usa la simulación
     */
    public function generarTramosLaboralesConSimulacion(Carbon $inicio, int $durSeg): array
    {
        $tramos = [];
        $restante = max(0, (int) $durSeg);

        // Verificar si el inicio está dentro de horario laborable
        $segmentosInicio = $this->obtenerSegmentosLaborablesDiaConSimulacion($inicio);
        $segmentosDiaAnterior = $this->obtenerSegmentosLaborablesDiaConSimulacion($inicio->copy()->subDay());
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
            $inicio = $this->siguienteLaborableInicioConSimulacion($inicio);
        }

        $cursor = $inicio->copy();
        $iter = 0;
        $iterMax = 10000;

        while ($restante > 0) {
            if (++$iter > $iterMax) {
                Log::error('FinProgramadoService: iteraciones excedidas (simulación)', [
                    'cursor' => $cursor->toIso8601String(),
                    'restante' => $restante,
                ]);
                break;
            }

            $diaActual = $cursor->copy()->startOfDay();
            $segmentosHoy = $this->obtenerSegmentosLaborablesDiaConSimulacion($cursor);
            $segmentosAyer = $this->obtenerSegmentosLaborablesDiaConSimulacion($cursor->copy()->subDay());

            $segmentos = collect($segmentosAyer)
                ->merge($segmentosHoy)
                ->filter(fn($seg) => $cursor->lt($seg['fin']))
                ->values()
                ->all();

            // Saltar no laborables
            if ($this->esNoLaborableConSimulacion($cursor) && empty($segmentosHoy)) {
                $cursor = $this->siguienteLaborableInicioConSimulacion($cursor);
                continue;
            }

            if (empty($segmentos)) {
                $limiteDia = $cursor->copy()->startOfDay()->addDay();
                $tsLimite = (int) $limiteDia->getTimestamp();
                $tsCursor = (int) $cursor->getTimestamp();
                $capacidad = max(0, $tsLimite - $tsCursor);
                $consume = min($restante, $capacidad);

                if ($consume > 0) {
                    $start = $cursor->copy();
                    $end = $cursor->copy()->addSeconds($consume);
                    $tramos[] = ['start' => $start, 'end' => $end];

                    $restante -= $consume;
                    $cursor = $end->copy();
                }

                if ($restante > 0 && (int)$cursor->getTimestamp() >= $tsLimite) {
                    $cursor = $this->siguienteLaborableInicioConSimulacion($cursor);
                }

                if ($consume == 0) {
                    $cursor->addDay()->startOfDay();
                }
            } else {
                foreach ($segmentos as $segmento) {
                    $inicioSeg = $segmento['inicio'];
                    $finSeg = $segmento['fin'];

                    if ($cursor->gte($finSeg)) {
                        continue;
                    }

                    if ($cursor->lt($inicioSeg)) {
                        $cursor = $inicioSeg->copy();
                    }

                    $capacidadSeg = max(0, $cursor->diffInSeconds($finSeg, false));
                    $consume = min($restante, $capacidadSeg);

                    if ($consume > 0) {
                        $start = $cursor->copy();
                        $end = $cursor->copy()->addSeconds($consume);
                        $tramos[] = ['start' => $start, 'end' => $end];

                        $restante -= $consume;
                        $cursor = $end->copy();
                    }

                    if ($restante <= 0) {
                        break;
                    }
                }

                if ($restante > 0) {
                    $diaDelCursor = $cursor->copy()->startOfDay();

                    if ($diaDelCursor->gt($diaActual)) {
                        continue;
                    }

                    $siguienteDia = $diaActual->copy()->addDay();
                    $cursor = $siguienteDia->copy();

                    $diasSaltados = 0;
                    while ($diasSaltados < 365) {
                        $segmentosDelDia = $this->obtenerSegmentosLaborablesDiaConSimulacion($cursor);

                        if ($this->esNoLaborableConSimulacion($cursor) && empty($segmentosDelDia)) {
                            $cursor->addDay()->startOfDay();
                            $diasSaltados++;
                        } else {
                            break;
                        }
                    }

                    if ($diasSaltados >= 365) {
                        Log::error('FinProgramadoService: bucle infinito (simulación)');
                        break;
                    }
                }
            }
        }

        return $tramos;
    }

    /**
     * Verifica si un día es no laborable, considerando simulación de sábado
     */
    public function esNoLaborableConSimulacion(Carbon $dia): bool
    {
        // Siempre es no laborable si es festivo
        if (isset($this->festivosSet[$dia->toDateString()])) {
            return true;
        }

        // Si es un sábado simulado, NO es no laborable
        if ($this->esSabadoSimulado($dia)) {
            return false;
        }

        // Si no es fin de semana, es laborable
        if (!$dia->isWeekend()) {
            return false;
        }

        // Para domingo u otros fines de semana sin simulación
        if ($this->turnosActivos !== null && $this->turnosActivos->isNotEmpty()) {
            foreach ($this->turnosActivos as $turno) {
                if ($turno->trabajaEnDia($dia->dayOfWeek)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Siguiente laborable con simulación de sábado
     */
    public function siguienteLaborableInicioConSimulacion(Carbon $dt): Carbon
    {
        $x = $dt->copy();
        $maxIter = 365;
        $iter = 0;

        while ($iter < $maxIter) {
            // Sábado con simulación (verificar si este sábado específico está simulado)
            if ($this->esSabadoSimulado($x)) {
                $segmentosSabado = $this->obtenerSegmentosLaborablesDiaConSimulacion($x);
                if (!empty($segmentosSabado)) {
                    $primerSegmento = $segmentosSabado[0];
                    if ($x->lt($primerSegmento['inicio'])) {
                        return $primerSegmento['inicio']->copy();
                    }
                    foreach ($segmentosSabado as $seg) {
                        if ($x->lt($seg['fin'])) {
                            return $x->lt($seg['inicio']) ? $seg['inicio']->copy() : $x->copy();
                        }
                    }
                }
            }

            // Domingo
            if ($x->dayOfWeek === Carbon::SUNDAY) {
                $segmentosDomingo = $this->obtenerSegmentosLaborablesDiaConSimulacion($x);
                foreach ($segmentosDomingo as $seg) {
                    if ($seg['inicio']->dayOfWeek === Carbon::SUNDAY && $x->lt($seg['fin'])) {
                        return $x->lt($seg['inicio']) ? $seg['inicio']->copy() : $x->copy();
                    }
                }
            }

            if (!$this->esNoLaborableConSimulacion($x)) {
                $segmentos = $this->obtenerSegmentosLaborablesDiaConSimulacion($x);

                if (!empty($segmentos)) {
                    $primerSegmento = $segmentos[0];
                    if ($x->lt($primerSegmento['inicio'])) {
                        return $primerSegmento['inicio']->copy();
                    }

                    foreach ($segmentos as $seg) {
                        if ($x->lt($seg['fin'])) {
                            return $x->lt($seg['inicio']) ? $seg['inicio']->copy() : $x->copy();
                        }
                    }
                } else {
                    return $x->copy()->startOfDay();
                }
            }

            $x->addDay()->startOfDay();
            $iter++;
        }

        return $x;
    }

    /**
     * Calcula el fin programado CON simulación de turno sábado
     * Similar a calcularFinProgramadoElementos pero usando la simulación
     */
    public function calcularFinProgramadoConSimulacionSabado(array $elementosIds, ?Carbon $fechaEntrega = null): array
    {
        if (empty($elementosIds)) {
            return [
                'fin_programado' => null,
                'hay_retraso' => false,
                'detalles' => [],
            ];
        }

        $this->cargarFestivos();
        $this->cargarTurnos();

        $elementos = Elemento::with(['planilla'])
            ->whereIn('id', $elementosIds)
            ->pendiente()
            ->get();

        if ($elementos->isEmpty()) {
            return [
                'fin_programado' => null,
                'hay_retraso' => false,
                'detalles' => [],
            ];
        }

        $elementosPorMaquina = $elementos->groupBy('maquina_id');
        $maquinaIds = $elementosPorMaquina->keys()->filter()->toArray();

        if (empty($maquinaIds)) {
            return [
                'fin_programado' => null,
                'hay_retraso' => false,
                'detalles' => [],
            ];
        }

        $colasMaquinas = $this->calcularColasIniciales($maquinaIds);

        $ordenes = OrdenPlanilla::whereIn('maquina_id', $maquinaIds)
            ->orderBy('maquina_id')
            ->orderBy('posicion')
            ->get()
            ->groupBy('maquina_id');

        $planillasIdsOrdenes = $ordenes->flatten()->pluck('planilla_id')->unique()->toArray();
        $todosElementosPendientes = Elemento::whereIn('planilla_id', $planillasIdsOrdenes)
            ->whereIn('maquina_id', $maquinaIds)
            ->pendiente()
            ->get()
            ->groupBy(fn($e) => $e->planilla_id . '_' . $e->maquina_id);

        $finMasTardio = null;
        $detalles = [];
        $planillasIds = $elementos->pluck('planilla_id')->unique()->toArray();

        foreach ($maquinaIds as $maquinaId) {
            $ordenesMaquina = $ordenes->get($maquinaId, collect());

            if ($ordenesMaquina->isEmpty()) {
                continue;
            }

            $inicioCola = $colasMaquinas[$maquinaId] ?? Carbon::now();
            $primeraOrdenId = $ordenesMaquina->first()->id ?? null;

            foreach ($ordenesMaquina as $orden) {
                $cacheKey = $orden->planilla_id . '_' . $maquinaId;
                $elementosPlanilla = $todosElementosPendientes->get($cacheKey, collect());

                if ($elementosPlanilla->isEmpty()) {
                    continue;
                }

                $duracionSegundos = $elementosPlanilla->sum(function ($e) {
                    $tiempoFab = (float) ($e->tiempo_fabricacion ?? 1200);
                    $tiempoAmarrado = 1200;
                    return $tiempoFab + $tiempoAmarrado;
                });
                $duracionSegundos = max($duracionSegundos, 3600);

                $esPrimerEvento = ($orden->id === $primeraOrdenId);

                if ($esPrimerEvento) {
                    $fechaInicioFabricacion = $this->obtenerFechaInicioFabricacion($orden->planilla_id, $maquinaId);
                    $fechaInicio = $fechaInicioFabricacion ?? Carbon::now();
                } else {
                    $fechaInicio = $inicioCola->copy();
                }

                // Usar generación de tramos CON simulación
                $tramos = $this->generarTramosLaboralesConSimulacion($fechaInicio, $duracionSegundos);

                if (empty($tramos)) {
                    continue;
                }

                $ultimoTramo = end($tramos);
                $fechaFinReal = $ultimoTramo['end'] instanceof Carbon
                    ? $ultimoTramo['end']->copy()
                    : Carbon::parse($ultimoTramo['end']);

                if (in_array($orden->planilla_id, $planillasIds)) {
                    $detalles[] = [
                        'planilla_id' => $orden->planilla_id,
                        'maquina_id' => $maquinaId,
                        'fin_programado' => $fechaFinReal->format('d/m/Y H:i'),
                        'fin_programado_carbon' => $fechaFinReal,
                    ];

                    if (!$finMasTardio || $fechaFinReal->gt($finMasTardio)) {
                        $finMasTardio = $fechaFinReal->copy();
                    }
                }

                $inicioCola = $fechaFinReal->copy();
            }
        }

        $hayRetraso = false;
        if ($finMasTardio && $fechaEntrega) {
            $fechaEntregaFin = $fechaEntrega->copy()->endOfDay();
            $hayRetraso = $finMasTardio->gt($fechaEntregaFin);
        }

        return [
            'fin_programado' => $finMasTardio,
            'fin_programado_str' => $finMasTardio?->format('d/m/Y H:i'),
            'hay_retraso' => $hayRetraso,
            'detalles' => $detalles,
        ];
    }

    // ==================================================================================
    // MÉTODOS PARA ELEMENTOS CON FECHA_ENTREGA PROPIA
    // ==================================================================================

    /**
     * Separa elementos específicos en su propia posición de cola.
     * Se usa cuando elementos con fecha_entrega propia comparten orden_planilla_id
     * con otros elementos que no tienen fecha propia.
     */
    public function separarElementosEnNuevaPosicion(array $elementosIds, Carbon $nuevaFechaEntrega): array
    {
        if (empty($elementosIds)) {
            return ['success' => false, 'mensaje' => 'No hay elementos para separar', 'ordenes_creadas' => []];
        }

        $elementos = Elemento::with(['planilla'])
            ->whereIn('id', $elementosIds)
            ->whereNotNull('fecha_entrega')
            ->get();

        if ($elementos->isEmpty()) {
            return ['success' => false, 'mensaje' => 'No hay elementos con fecha_entrega propia', 'ordenes_creadas' => []];
        }

        $ordenesCreadas = [];
        $maquinasAfectadas = [];

        DB::beginTransaction();
        try {
            $grupos = $elementos->groupBy(fn($e) => $e->planilla_id . '-' . $e->maquina_id);

            foreach ($grupos as $key => $elementosGrupo) {
                $primerElemento = $elementosGrupo->first();
                $planillaId = $primerElemento->planilla_id;
                $maquinaId = $primerElemento->maquina_id;

                if (!$maquinaId) continue;

                $ordenActualId = $primerElemento->orden_planilla_id;

                if (!$ordenActualId) {
                    $nuevaOrden = $this->crearNuevaOrdenParaElementos($planillaId, $maquinaId, $nuevaFechaEntrega, $elementosGrupo->pluck('id')->toArray());
                    $ordenesCreadas[] = $nuevaOrden;
                    $maquinasAfectadas[$maquinaId] = true;
                    continue;
                }

                // Verificar si hay otros elementos sin fecha_entrega propia
                $otrosElementosEnOrden = Elemento::where('orden_planilla_id', $ordenActualId)
                    ->whereNotIn('id', $elementosGrupo->pluck('id')->toArray())
                    ->whereNull('fecha_entrega')
                    ->exists();

                if ($otrosElementosEnOrden) {
                    Log::info('[separarElementos] Separando elementos con fecha propia', [
                        'elementos_ids' => $elementosGrupo->pluck('id')->toArray(),
                        'orden_original_id' => $ordenActualId,
                    ]);
                    $nuevaOrden = $this->crearNuevaOrdenParaElementos($planillaId, $maquinaId, $nuevaFechaEntrega, $elementosGrupo->pluck('id')->toArray());
                    $ordenesCreadas[] = $nuevaOrden;
                    $maquinasAfectadas[$maquinaId] = true;
                } else {
                    $ordenesCreadas[] = [
                        'orden_planilla_id' => $ordenActualId,
                        'planilla_id' => $planillaId,
                        'maquina_id' => $maquinaId,
                        'elementos_ids' => $elementosGrupo->pluck('id')->toArray(),
                        'creada' => false,
                    ];
                }
            }

            DB::commit();
            return ['success' => true, 'mensaje' => count($ordenesCreadas) . ' grupo(s) procesados', 'ordenes_creadas' => $ordenesCreadas, 'maquinas_afectadas' => array_keys($maquinasAfectadas)];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[separarElementos] Error: ' . $e->getMessage());
            return ['success' => false, 'mensaje' => 'Error: ' . $e->getMessage(), 'ordenes_creadas' => []];
        }
    }

    private function crearNuevaOrdenParaElementos(int $planillaId, int $maquinaId, Carbon $fechaEntrega, array $elementosIds): array
    {
        $nuevaPosicion = $this->calcularPosicionPorFechaElementos($maquinaId, $fechaEntrega);

        OrdenPlanilla::where('maquina_id', $maquinaId)
            ->where('posicion', '>=', $nuevaPosicion)
            ->increment('posicion');

        $nuevaOrden = OrdenPlanilla::create([
            'planilla_id' => $planillaId,
            'maquina_id' => $maquinaId,
            'posicion' => $nuevaPosicion,
        ]);

        Elemento::whereIn('id', $elementosIds)->update(['orden_planilla_id' => $nuevaOrden->id]);

        Log::info('[crearNuevaOrden] Orden creada', [
            'orden_id' => $nuevaOrden->id,
            'planilla_id' => $planillaId,
            'maquina_id' => $maquinaId,
            'posicion' => $nuevaPosicion,
        ]);

        return [
            'orden_planilla_id' => $nuevaOrden->id,
            'planilla_id' => $planillaId,
            'maquina_id' => $maquinaId,
            'posicion' => $nuevaPosicion,
            'elementos_ids' => $elementosIds,
            'creada' => true,
        ];
    }

    private function calcularPosicionPorFechaElementos(int $maquinaId, Carbon $fechaEntrega): int
    {
        $ordenes = OrdenPlanilla::where('maquina_id', $maquinaId)
            ->join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
            ->orderBy('orden_planillas.posicion')
            ->select('orden_planillas.*', 'planillas.fecha_estimada_entrega')
            ->get();

        if ($ordenes->isEmpty()) return 1;

        foreach ($ordenes as $orden) {
            $fechaOrden = $orden->fecha_estimada_entrega ? Carbon::parse($orden->fecha_estimada_entrega) : null;
            if ($fechaOrden && $fechaEntrega->lt($fechaOrden)) {
                return $orden->posicion;
            }
        }

        return $ordenes->max('posicion') + 1;
    }

    public function verificarPosibilidadRetrasoElementos(array $elementosIds): array
    {
        if (empty($elementosIds)) {
            return ['puede_retrasar' => false, 'ordenes_afectadas' => [], 'mensaje' => 'No hay elementos'];
        }

        $elementos = Elemento::with(['planilla.obra'])
            ->whereIn('id', $elementosIds)
            ->whereNotNull('fecha_entrega')
            ->pendiente()
            ->get();

        if ($elementos->isEmpty()) {
            return ['puede_retrasar' => false, 'ordenes_afectadas' => [], 'mensaje' => 'No hay elementos pendientes con fecha_entrega propia'];
        }

        $ordenIds = $elementos->pluck('orden_planilla_id')->filter()->unique()->toArray();

        if (empty($ordenIds)) {
            return ['puede_retrasar' => false, 'ordenes_afectadas' => [], 'mensaje' => 'Sin órdenes asignadas'];
        }

        $ordenes = OrdenPlanilla::whereIn('id', $ordenIds)->with(['planilla.obra', 'maquina'])->get();

        $ordenesAfectadas = [];
        foreach ($ordenes as $orden) {
            $totalOrdenes = OrdenPlanilla::where('maquina_id', $orden->maquina_id)->count();

            if ($orden->posicion < $totalOrdenes) {
                $elementosEnOrden = $elementos->where('orden_planilla_id', $orden->id);
                $ordenesAfectadas[] = [
                    'orden_planilla_id' => $orden->id,
                    'planilla_id' => $orden->planilla_id,
                    'planilla_codigo' => $orden->planilla->codigo ?? 'N/A',
                    'obra' => $orden->planilla->obra->obra ?? 'Sin obra',
                    'maquina_id' => $orden->maquina_id,
                    'maquina_nombre' => $orden->maquina->nombre ?? 'Máquina ' . $orden->maquina_id,
                    'posicion_actual' => $orden->posicion,
                    'total_posiciones' => $totalOrdenes,
                    'elementos_count' => $elementosEnOrden->count(),
                    'elementos_ids' => $elementosEnOrden->pluck('id')->toArray(),
                ];
            }
        }

        return [
            'puede_retrasar' => !empty($ordenesAfectadas),
            'ordenes_afectadas' => $ordenesAfectadas,
            'mensaje' => empty($ordenesAfectadas) ? 'Ya están al final' : count($ordenesAfectadas) . ' orden(es) pueden retrasarse',
        ];
    }

    public function simularRetrasoElementos(array $elementosIds): array
    {
        $verificacion = $this->verificarPosibilidadRetrasoElementos($elementosIds);

        if (!$verificacion['puede_retrasar']) {
            return ['puede_retrasar' => false, 'ordenes_a_retrasar' => [], 'beneficiados' => [], 'mensaje' => $verificacion['mensaje']];
        }

        $ordenesARetrasar = [];
        $beneficiados = [];

        foreach ($verificacion['ordenes_afectadas'] as $info) {
            $ordenesARetrasar[] = [
                'orden_planilla_id' => $info['orden_planilla_id'],
                'planilla_id' => $info['planilla_id'],
                'planilla_codigo' => $info['planilla_codigo'],
                'obra' => $info['obra'],
                'maquina_id' => $info['maquina_id'],
                'maquina_nombre' => $info['maquina_nombre'],
                'posicion_actual' => $info['posicion_actual'],
                'posicion_nueva' => $info['total_posiciones'],
                'elementos_count' => $info['elementos_count'],
            ];

            $ordenesBeneficiadas = OrdenPlanilla::where('maquina_id', $info['maquina_id'])
                ->where('posicion', '>', $info['posicion_actual'])
                ->with(['planilla.obra'])
                ->get();

            foreach ($ordenesBeneficiadas as $ob) {
                $beneficiados[] = [
                    'planilla_id' => $ob->planilla_id,
                    'planilla_codigo' => $ob->planilla->codigo ?? 'N/A',
                    'obra' => $ob->planilla->obra->obra ?? 'Sin obra',
                    'maquina_nombre' => $info['maquina_nombre'],
                    'posicion_actual' => $ob->posicion,
                    'posicion_nueva' => $ob->posicion - 1,
                ];
            }
        }

        return [
            'puede_retrasar' => true,
            'ordenes_a_retrasar' => $ordenesARetrasar,
            'beneficiados' => collect($beneficiados)->unique('planilla_id')->values()->toArray(),
            'mensaje' => count($ordenesARetrasar) . ' orden(es) de elementos se moverán al final',
            'es_elementos_con_fecha_propia' => true,
        ];
    }

    public function ejecutarRetrasoElementos(array $elementosIds, Carbon $nuevaFechaEntrega): array
    {
        $separacion = $this->separarElementosEnNuevaPosicion($elementosIds, $nuevaFechaEntrega);

        if (!$separacion['success']) {
            return ['success' => false, 'resultados' => [], 'mensaje' => $separacion['mensaje']];
        }

        $simulacion = $this->simularRetrasoElementos($elementosIds);

        if (!$simulacion['puede_retrasar']) {
            return ['success' => true, 'resultados' => [], 'mensaje' => 'Elementos separados. ' . $simulacion['mensaje']];
        }

        $resultados = [];

        DB::beginTransaction();
        try {
            foreach ($simulacion['ordenes_a_retrasar'] as $info) {
                $ordenActual = OrdenPlanilla::find($info['orden_planilla_id']);
                if (!$ordenActual) continue;

                OrdenPlanilla::where('maquina_id', $info['maquina_id'])
                    ->where('posicion', '>', $info['posicion_actual'])
                    ->where('posicion', '<=', $info['posicion_nueva'])
                    ->decrement('posicion');

                $ordenActual->posicion = $info['posicion_nueva'];
                $ordenActual->save();

                $resultados[] = [
                    'orden_planilla_id' => $info['orden_planilla_id'],
                    'success' => true,
                    'posicion_anterior' => $info['posicion_actual'],
                    'posicion_nueva' => $info['posicion_nueva'],
                ];
            }

            DB::commit();
            return ['success' => true, 'resultados' => $resultados, 'mensaje' => count($resultados) . ' orden(es) retrasadas'];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'resultados' => [], 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }

    public function ejecutarAdelantoElementos(array $elementosIds, Carbon $nuevaFechaEntrega): array
    {
        $separacion = $this->separarElementosEnNuevaPosicion($elementosIds, $nuevaFechaEntrega);

        if (!$separacion['success']) {
            return ['success' => false, 'resultados' => [], 'mensaje' => $separacion['mensaje']];
        }

        $resultados = [];
        $maquinasAfectadas = [];

        DB::beginTransaction();
        try {
            foreach ($separacion['ordenes_creadas'] as $info) {
                $ordenActual = OrdenPlanilla::find($info['orden_planilla_id']);
                if (!$ordenActual) continue;

                $posicionActual = $ordenActual->posicion;
                $nuevaPosicion = $this->calcularPosicionPorFechaElementos($info['maquina_id'], $nuevaFechaEntrega);

                if ($nuevaPosicion >= $posicionActual) {
                    $resultados[] = ['orden_planilla_id' => $info['orden_planilla_id'], 'success' => true, 'mensaje' => 'Ya está en posición correcta'];
                    continue;
                }

                OrdenPlanilla::where('maquina_id', $info['maquina_id'])
                    ->where('posicion', '>=', $nuevaPosicion)
                    ->where('posicion', '<', $posicionActual)
                    ->where('id', '!=', $info['orden_planilla_id'])
                    ->increment('posicion');

                $ordenActual->posicion = $nuevaPosicion;
                $ordenActual->save();
                $maquinasAfectadas[$info['maquina_id']] = true;

                $resultados[] = [
                    'orden_planilla_id' => $info['orden_planilla_id'],
                    'success' => true,
                    'posicion_anterior' => $posicionActual,
                    'posicion_nueva' => $nuevaPosicion,
                ];
            }

            foreach (array_keys($maquinasAfectadas) as $maquinaId) {
                $this->recompactarPosicionesMaquinaElementos($maquinaId);
            }

            DB::commit();
            return ['success' => true, 'resultados' => $resultados, 'mensaje' => count($resultados) . ' orden(es) adelantadas'];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'resultados' => [], 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }

    private function recompactarPosicionesMaquinaElementos(int $maquinaId): void
    {
        $ordenes = OrdenPlanilla::where('maquina_id', $maquinaId)->orderBy('posicion')->get();
        $posicion = 1;
        foreach ($ordenes as $orden) {
            if ($orden->posicion !== $posicion) {
                $orden->update(['posicion' => $posicion]);
            }
            $posicion++;
        }
    }
}
