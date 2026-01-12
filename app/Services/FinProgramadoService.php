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
     */
    public function calcularFinProgramadoElementos(array $elementosIds, ?Carbon $fechaEntrega = null): array
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

        // Obtener elementos con sus planillas
        $elementos = Elemento::with(['planilla'])
            ->whereIn('id', $elementosIds)
            ->where('estado', 'pendiente')
            ->get();

        if ($elementos->isEmpty()) {
            return [
                'fin_programado' => null,
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
                // Obtener elementos pendientes de esta planilla en esta máquina
                $elementosPlanilla = Elemento::where('planilla_id', $orden->planilla_id)
                    ->where('maquina_id', $maquinaId)
                    ->where('estado', 'pendiente')
                    ->get();

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
     * Carga los festivos en memoria
     */
    private function cargarFestivos(): void
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
     * Carga los turnos activos en memoria
     */
    private function cargarTurnos(): void
    {
        if ($this->turnosActivos !== null) {
            return;
        }

        $this->turnosActivos = Turno::where('activo', true)->get();
    }

    /**
     * Genera tramos laborales respetando turnos y festivos
     * (Simplificado pero respeta la lógica principal)
     */
    private function generarTramosLaborales(Carbon $inicio, int $durSeg): array
    {
        $tramos = [];
        $restante = max(0, $durSeg);

        // Verificar si el inicio está en horario laborable
        $segmentosInicio = $this->obtenerSegmentosLaborablesDia($inicio);
        $dentroDeSegmento = false;

        foreach ($segmentosInicio as $seg) {
            if ($inicio->gte($seg['inicio']) && $inicio->lt($seg['fin'])) {
                $dentroDeSegmento = true;
                break;
            }
        }

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

            // Obtener segmentos del día actual y del anterior (para turnos nocturnos)
            $segmentosHoy = $this->obtenerSegmentosLaborablesDia($cursor);
            $segmentosAyer = $this->obtenerSegmentosLaborablesDia($cursor->copy()->subDay());

            $segmentos = collect($segmentosAyer)
                ->merge($segmentosHoy)
                ->filter(fn($seg) => $cursor->lt($seg['fin']))
                ->sortBy(fn($seg) => $seg['inicio']->timestamp)
                ->values()
                ->all();

            // Saltar días no laborables sin segmentos
            if ($this->esNoLaborable($cursor) && empty($segmentosHoy)) {
                $cursor = $this->siguienteLaborableInicio($cursor);
                continue;
            }

            if (empty($segmentos)) {
                // Sin turnos definidos, avanzar al siguiente día
                $cursor = $this->siguienteLaborableInicio($cursor->addDay()->startOfDay());
                continue;
            }

            foreach ($segmentos as $segmento) {
                if ($restante <= 0) break;

                $inicioSeg = $segmento['inicio'];
                $finSeg = $segmento['fin'];

                if ($cursor->gte($finSeg)) continue;
                if ($cursor->lt($inicioSeg)) {
                    $cursor = $inicioSeg->copy();
                }

                // Corte viernes a las 22:00
                if ($cursor->dayOfWeek === Carbon::FRIDAY) {
                    $finViernesTarde = $cursor->copy()->setTime(22, 0, 0);
                    if ($cursor->gte($finViernesTarde)) {
                        // Ya pasaron las 22:00 del viernes, saltar al siguiente día laborable
                        break;
                    }
                    if ($finSeg->gt($finViernesTarde)) {
                        $finSeg = $finViernesTarde;
                    }
                }

                // No trabajar sábados
                if ($cursor->isSaturday()) {
                    $cursor = $this->siguienteLaborableInicio($cursor);
                    continue 2; // Salir del foreach de segmentos
                }

                $capacidad = max(0, $finSeg->timestamp - $cursor->timestamp);
                $consume = min($restante, $capacidad);

                if ($consume > 0) {
                    $tramos[] = [
                        'start' => $cursor->copy(),
                        'end' => $cursor->copy()->addSeconds($consume),
                    ];
                    $restante -= $consume;
                    $cursor->addSeconds($consume);
                }
            }

            // Si queda trabajo, ir al siguiente día laborable
            if ($restante > 0) {
                $cursor = $this->siguienteLaborableInicio($cursor->addDay()->startOfDay());
            }
        }

        return $tramos;
    }

    /**
     * Obtiene los segmentos laborables de un día según turnos activos
     */
    private function obtenerSegmentosLaborablesDia(Carbon $dia): array
    {
        if ($this->turnosActivos === null || $this->turnosActivos->isEmpty()) {
            return [];
        }

        $segmentos = [];
        $diaStr = $dia->copy()->startOfDay();

        foreach ($this->turnosActivos as $turno) {
            if (empty($turno->hora_inicio) || empty($turno->hora_fin)) {
                continue;
            }

            $inicio = $diaStr->copy()->setTimeFromTimeString($turno->hora_inicio);
            $fin = $diaStr->copy()->setTimeFromTimeString($turno->hora_fin);

            // Si el fin es menor que el inicio, el turno cruza medianoche
            if ($fin->lte($inicio)) {
                $fin->addDay();
            }

            $segmentos[] = [
                'inicio' => $inicio,
                'fin' => $fin,
            ];
        }

        // Ordenar por hora de inicio
        usort($segmentos, fn($a, $b) => $a['inicio']->timestamp <=> $b['inicio']->timestamp);

        return $segmentos;
    }

    /**
     * Verifica si un día es no laborable
     */
    private function esNoLaborable(Carbon $dia): bool
    {
        // Sábado siempre es no laborable
        if ($dia->isSaturday()) {
            return true;
        }

        // Festivos
        if (isset($this->festivosSet[$dia->toDateString()])) {
            return true;
        }

        // Domingo puede tener turno noche
        if ($dia->isSunday()) {
            $segmentos = $this->obtenerSegmentosLaborablesDia($dia);
            return empty($segmentos);
        }

        return false;
    }

    /**
     * Obtiene el siguiente momento laborable
     */
    private function siguienteLaborableInicio(Carbon $dt): Carbon
    {
        $x = $dt->copy();
        $maxIter = 365;
        $iter = 0;

        while ($iter++ < $maxIter) {
            // Saltar sábados siempre
            if ($x->isSaturday()) {
                $x->addDay()->startOfDay();
                continue;
            }

            // Verificar festivos
            if (isset($this->festivosSet[$x->toDateString()])) {
                $x->addDay()->startOfDay();
                continue;
            }

            $segmentos = $this->obtenerSegmentosLaborablesDia($x);

            if (!empty($segmentos)) {
                foreach ($segmentos as $seg) {
                    if ($x->lt($seg['fin'])) {
                        return $x->lt($seg['inicio']) ? $seg['inicio']->copy() : $x->copy();
                    }
                }
            }

            $x->addDay()->startOfDay();
        }

        return $x;
    }

    /**
     * Simula el adelanto de fabricación para un conjunto de elementos
     *
     * Identifica qué ordenes de planilla necesitan adelantarse,
     * calcula la posición óptima, y detecta colaterales.
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

        // Obtener elementos con sus planillas
        $elementos = Elemento::with(['planilla.obra'])
            ->whereIn('id', $elementosIds)
            ->where('estado', 'pendiente')
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

        // Planillas involucradas
        $planillasIds = $elementos->pluck('planilla_id')->unique()->toArray();

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

        foreach ($ordenesConRetraso as $ordenRetraso) {
            $maquinaId = $ordenRetraso['maquina_id'];
            $planillaId = $ordenRetraso['planilla_id'];

            $ordenesMaquina = $ordenes->get($maquinaId, collect());
            $ordenActual = $ordenesMaquina->firstWhere('planilla_id', $planillaId);

            if (!$ordenActual) {
                continue;
            }

            $posicionActual = $ordenActual->posicion;

            // Buscar posición óptima mediante búsqueda binaria
            $posicionOptima = $this->buscarPosicionOptima(
                $maquinaId,
                $planillaId,
                $fechaEntrega,
                $ordenesMaquina
            );

            if ($posicionOptima !== null && $posicionOptima < $posicionActual) {
                $planilla = Planilla::with('obra')->find($planillaId);

                $ordenesAAdelantar[] = [
                    'planilla_id' => $planillaId,
                    'planilla_codigo' => $planilla->codigo ?? 'N/A',
                    'obra' => optional($planilla->obra)->obra ?? 'Sin obra',
                    'maquina_id' => $maquinaId,
                    'maquina_nombre' => Maquina::find($maquinaId)->nombre ?? 'Máquina ' . $maquinaId,
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
            }
        }

        if (empty($ordenesAAdelantar)) {
            return [
                'necesita_adelanto' => false,
                'ordenes_a_adelantar' => [],
                'colaterales' => [],
                'mensaje' => 'No se encontró una posición que mejore los tiempos',
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
     * Busca desde la posición actual hacia atrás para encontrar la posición
     * más alta (menos adelanto) que aún permita entregar a tiempo
     */
    private function buscarPosicionOptima(
        int $maquinaId,
        int $planillaId,
        Carbon $fechaEntrega,
        Collection $ordenesMaquina
    ): ?int {
        $ordenActual = $ordenesMaquina->firstWhere('planilla_id', $planillaId);
        if (!$ordenActual) {
            return null;
        }

        $posicionActual = $ordenActual->posicion;
        $fechaEntregaFin = $fechaEntrega->copy()->endOfDay();

        // Buscar desde posición actual-1 hacia atrás hasta encontrar
        // la posición más alta que permita entregar a tiempo
        $mejorPosicion = null;

        for ($posPrueba = $posicionActual - 1; $posPrueba >= 1; $posPrueba--) {
            // Simular el orden con la planilla en esta posición
            $finSimulado = $this->simularFinEnPosicion(
                $maquinaId,
                $planillaId,
                $posPrueba,
                $ordenesMaquina
            );

            if ($finSimulado && $finSimulado->lte($fechaEntregaFin)) {
                // Esta posición funciona - es la más alta que cumple
                $mejorPosicion = $posPrueba;
                break; // Encontramos la posición más alta que funciona
            }
        }

        return $mejorPosicion;
    }

    /**
     * Simula el fin programado si la planilla estuviera en una posición específica
     */
    private function simularFinEnPosicion(
        int $maquinaId,
        int $planillaId,
        int $nuevaPosicion,
        Collection $ordenesMaquina
    ): ?Carbon {
        // Reordenar: sacar planilla de su posición y meterla en nuevaPosicion
        $ordenesSimuladas = $ordenesMaquina->map(function ($o) use ($planillaId, $nuevaPosicion) {
            $orden = clone $o;
            if ($orden->planilla_id == $planillaId) {
                $orden->posicion = $nuevaPosicion;
            } elseif ($orden->posicion >= $nuevaPosicion && $orden->posicion < $ordenesMaquina->firstWhere('planilla_id', $planillaId)->posicion) {
                $orden->posicion = $orden->posicion + 1;
            }
            return $orden;
        })->sortBy('posicion')->values();

        // Calcular fin para la planilla objetivo
        $colasMaquinas = $this->calcularColasIniciales([$maquinaId]);
        $inicioCola = $colasMaquinas[$maquinaId] ?? Carbon::now();

        foreach ($ordenesSimuladas as $idx => $orden) {
            $elementosPlanilla = Elemento::where('planilla_id', $orden->planilla_id)
                ->where('maquina_id', $maquinaId)
                ->where('estado', 'pendiente')
                ->get();

            if ($elementosPlanilla->isEmpty()) {
                continue;
            }

            $duracionSegundos = $elementosPlanilla->sum(function ($e) {
                $tiempoFab = (float) ($e->tiempo_fabricacion ?? 1200);
                $tiempoAmarrado = 1200;
                return $tiempoFab + $tiempoAmarrado;
            });
            $duracionSegundos = max($duracionSegundos, 3600);

            $esPrimerEvento = ($idx === 0);

            if ($esPrimerEvento) {
                $fechaInicioFabricacion = $this->obtenerFechaInicioFabricacion($orden->planilla_id, $maquinaId);
                $fechaInicio = $fechaInicioFabricacion ?? Carbon::now();
            } else {
                $fechaInicio = $inicioCola->copy();
            }

            $tramos = $this->generarTramosLaborales($fechaInicio, $duracionSegundos);

            if (empty($tramos)) {
                continue;
            }

            $ultimoTramo = end($tramos);
            $fechaFinReal = $ultimoTramo['end'] instanceof Carbon
                ? $ultimoTramo['end']->copy()
                : Carbon::parse($ultimoTramo['end']);

            if ($orden->planilla_id == $planillaId) {
                return $fechaFinReal;
            }

            $inicioCola = $fechaFinReal->copy();
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
                        ->where('estado', 'pendiente')
                        ->get();

                    if ($elementosAfectados->isEmpty()) continue;

                    $fechaEntregaPlanilla = $planilla->fecha_estimada_entrega;

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

        DB::beginTransaction();
        try {
            foreach ($ordenesAAdelantar as $orden) {
                $planillaId = $orden['planilla_id'];
                $maquinaId = $orden['maquina_id'];
                $nuevaPosicion = $orden['posicion_nueva'];

                // Obtener orden actual
                $ordenActual = OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $maquinaId)
                    ->first();

                if (!$ordenActual) {
                    $resultados[] = [
                        'planilla_id' => $planillaId,
                        'success' => false,
                        'mensaje' => 'Orden no encontrada',
                    ];
                    continue;
                }

                $posicionAnterior = $ordenActual->posicion;

                if ($nuevaPosicion >= $posicionAnterior) {
                    $resultados[] = [
                        'planilla_id' => $planillaId,
                        'success' => false,
                        'mensaje' => 'La nueva posición no es menor que la actual',
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
}
