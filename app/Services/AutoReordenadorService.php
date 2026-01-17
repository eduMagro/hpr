<?php

namespace App\Services;

use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Maquina;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoReordenadorService
{
    public function __construct(
        private FinProgramadoService $finProgramadoService
    ) {}

    /**
     * SIMULA el reordenamiento sin aplicar cambios
     * Retorna preview de lo que cambiaría
     */
    public function simularReordenamiento(array $planillasConFechas): array
    {
        $cambiosPropuestos = [];
        $alertasRetraso = [];

        // 1. Identificar máquinas afectadas por las planillas modificadas
        $planillasIds = collect($planillasConFechas)->pluck('id')->toArray();
        $maquinaIds = OrdenPlanilla::whereIn('planilla_id', $planillasIds)
            ->distinct()
            ->pluck('maquina_id')
            ->toArray();

        if (empty($maquinaIds)) {
            return [
                'success' => true,
                'cambios_propuestos' => [],
                'alertas_retraso' => [],
                'total_cambios' => 0,
                'total_retrasos' => 0,
                'mensaje' => 'No hay máquinas afectadas',
            ];
        }

        // 2. Por cada máquina, simular nuevo orden
        foreach ($maquinaIds as $maquinaId) {
            $resultado = $this->simularReordenMaquina($maquinaId, $planillasConFechas);
            $cambiosPropuestos = array_merge($cambiosPropuestos, $resultado['cambios']);
            $alertasRetraso = array_merge($alertasRetraso, $resultado['retrasos']);
        }

        return [
            'success' => true,
            'cambios_propuestos' => $cambiosPropuestos,
            'alertas_retraso' => $alertasRetraso,
            'total_cambios' => count($cambiosPropuestos),
            'total_retrasos' => count($alertasRetraso),
        ];
    }

    /**
     * Simula reordenamiento de UNA máquina
     */
    private function simularReordenMaquina(int $maquinaId, array $nuevasFechas): array
    {
        $maquina = Maquina::find($maquinaId);
        $cambios = [];
        $retrasos = [];

        if (!$maquina) {
            return ['cambios' => [], 'retrasos' => []];
        }

        // Obtener órdenes actuales con planillas
        $ordenes = OrdenPlanilla::where('maquina_id', $maquinaId)
            ->with(['planilla' => function($q) {
                $q->select('id', 'codigo', 'fecha_estimada_entrega', 'estado', 'peso_total', 'revisada');
            }])
            ->get();

        if ($ordenes->isEmpty()) {
            return ['cambios' => [], 'retrasos' => []];
        }

        // Aplicar nuevas fechas temporalmente para ordenar
        $ordenesConFechas = $ordenes->map(function($orden) use ($nuevasFechas) {
            $nuevaFecha = collect($nuevasFechas)
                ->firstWhere('id', $orden->planilla_id);

            // Usar fecha actual de la planilla como base
            $fechaEntrega = $orden->planilla->fecha_estimada_entrega;

            // Si viene nueva fecha, aplicarla
            if ($nuevaFecha && !empty($nuevaFecha['fecha_estimada_entrega'])) {
                $fechaEntrega = Carbon::parse($nuevaFecha['fecha_estimada_entrega']);

                // Si viene hora separada, combinarla
                if (!empty($nuevaFecha['hora_entrega'])) {
                    $hora = Carbon::parse($nuevaFecha['hora_entrega']);
                    $fechaEntrega->setTime($hora->hour, $hora->minute, 0);
                }
            }

            return [
                'orden' => $orden,
                'fecha_entrega' => $fechaEntrega,
                'estado' => $orden->planilla->estado ?? 'pendiente',
                'peso' => $orden->planilla->peso_total ?? 0,
                'posicion_actual' => $orden->posicion,
            ];
        });

        // Ordenar TODAS las planillas por: fecha+hora ASC, peso DESC, ID
        $ordenesOrdenadas = $ordenesConFechas->sortBy([
            fn($o) => $o['fecha_entrega'] ?? Carbon::maxValue(),
            fn($o) => -$o['peso'],
            fn($o) => $o['orden']->planilla_id,
        ])->values();

        // Detectar cambios de posición
        foreach ($ordenesOrdenadas as $index => $item) {
            $nuevaPosicion = $index + 1;
            if ($item['posicion_actual'] !== $nuevaPosicion) {
                $planilla = $item['orden']->planilla;
                $cambios[] = [
                    'planilla_id' => $item['orden']->planilla_id,
                    'planilla_codigo' => $planilla->codigo_limpio ?? $planilla->codigo ?? ('Planilla ' . $planilla->id),
                    'maquina_id' => $maquinaId,
                    'maquina_nombre' => $maquina->nombre ?? ('Máquina ' . $maquinaId),
                    'posicion_actual' => $item['posicion_actual'],
                    'posicion_nueva' => $nuevaPosicion,
                    'fecha_entrega' => $item['fecha_entrega']?->format('d/m/Y'),
                ];
            }
        }

        // Calcular fin_programado simulado para detectar retrasos
        // Usamos el servicio existente que ya carga festivos y turnos
        foreach ($ordenesOrdenadas as $item) {
            $elementosIds = Elemento::where('planilla_id', $item['orden']->planilla_id)
                ->where('maquina_id', $maquinaId)
                ->where('estado', 'pendiente')
                ->pluck('id')
                ->toArray();

            if (empty($elementosIds)) {
                continue;
            }

            $resultado = $this->finProgramadoService->calcularFinProgramadoElementos(
                $elementosIds,
                $item['fecha_entrega']
            );

            if ($resultado['hay_retraso']) {
                $planilla = $item['orden']->planilla;
                $diasRetraso = 0;
                if ($resultado['fin_programado'] && $item['fecha_entrega']) {
                    $diasRetraso = $resultado['fin_programado']->diffInDays($item['fecha_entrega']);
                }

                $retrasos[] = [
                    'planilla_id' => $item['orden']->planilla_id,
                    'planilla_codigo' => $planilla->codigo_limpio ?? $planilla->codigo ?? ('Planilla ' . $planilla->id),
                    'maquina_nombre' => $maquina->nombre ?? ('Máquina ' . $maquinaId),
                    'fecha_entrega' => $item['fecha_entrega']?->format('d/m/Y'),
                    'fin_programado' => $resultado['fin_programado_str'],
                    'dias_retraso' => $diasRetraso,
                ];
            }
        }

        return ['cambios' => $cambios, 'retrasos' => $retrasos];
    }

    /**
     * APLICA el reordenamiento confirmado
     */
    public function aplicarReordenamiento(array $planillasConFechas): array
    {
        return DB::transaction(function() use ($planillasConFechas) {
            $cambiosAplicados = 0;
            $planillasActualizadas = 0;

            // 1. Actualizar fechas en planillas (preservando hora existente si no viene nueva)
            foreach ($planillasConFechas as $datos) {
                if (!empty($datos['fecha_estimada_entrega'])) {
                    $planilla = Planilla::find($datos['id']);
                    if (!$planilla) continue;

                    $fechaNueva = Carbon::parse($datos['fecha_estimada_entrega']);

                    // Si viene hora separada, usarla
                    if (!empty($datos['hora_entrega'])) {
                        $hora = Carbon::parse($datos['hora_entrega']);
                        $fechaNueva->setTime($hora->hour, $hora->minute, 0);
                    }
                    // Si no viene hora, preservar la hora existente de la planilla
                    elseif ($planilla->getRawOriginal('fecha_estimada_entrega')) {
                        $horaExistente = Carbon::parse($planilla->getRawOriginal('fecha_estimada_entrega'));
                        // Solo preservar si no es medianoche (hora por defecto)
                        if ($horaExistente->hour !== 0 || $horaExistente->minute !== 0) {
                            $fechaNueva->setTime($horaExistente->hour, $horaExistente->minute, 0);
                        } else {
                            $fechaNueva->setTime(7, 0, 0); // Hora por defecto
                        }
                    } else {
                        $fechaNueva->setTime(7, 0, 0); // Hora por defecto
                    }

                    $planilla->fecha_estimada_entrega = $fechaNueva;
                    $planilla->save();
                    $planillasActualizadas++;
                }
            }

            // 2. Identificar máquinas afectadas
            $planillasIds = collect($planillasConFechas)->pluck('id')->toArray();
            $maquinaIds = OrdenPlanilla::whereIn('planilla_id', $planillasIds)
                ->distinct()
                ->pluck('maquina_id')
                ->toArray();

            // 3. Reordenar cada máquina
            foreach ($maquinaIds as $maquinaId) {
                $cambiosAplicados += $this->reordenarColaMaquina($maquinaId);
            }

            Log::info('AutoReordenadorService: Reordenamiento aplicado', [
                'planillas_actualizadas' => $planillasActualizadas,
                'cambios_posicion' => $cambiosAplicados,
                'maquinas_afectadas' => count($maquinaIds),
            ]);

            return [
                'success' => true,
                'cambios_aplicados' => $cambiosAplicados,
                'planillas_actualizadas' => $planillasActualizadas,
                'message' => "Fechas actualizadas ({$planillasActualizadas}) y {$cambiosAplicados} planillas reordenadas",
            ];
        });
    }

    /**
     * Reordena la cola de una máquina por fecha y hora de entrega
     * Ordena todas las planillas (revisadas y no revisadas)
     */
    private function reordenarColaMaquina(int $maquinaId): int
    {
        $ordenes = OrdenPlanilla::where('maquina_id', $maquinaId)
            ->with(['planilla' => function($q) {
                $q->select('id', 'fecha_estimada_entrega', 'estado', 'peso_total', 'revisada');
            }])
            ->get();

        if ($ordenes->isEmpty()) {
            return 0;
        }

        // Ordenar TODAS las planillas por criterios de prioridad
        $ordenesOrdenadas = $ordenes->sortBy([
            // 1. Fecha y hora de entrega (más urgentes primero)
            fn($o) => $o->planilla->fecha_estimada_entrega ?? Carbon::maxValue(),
            // 2. Peso (mayor peso primero para optimizar setup)
            fn($o) => -($o->planilla->peso_total ?? 0),
            // 3. FIFO (ID más bajo primero)
            fn($o) => $o->planilla_id,
        ])->values();

        // Reasignar posiciones
        $cambios = 0;
        foreach ($ordenesOrdenadas as $index => $orden) {
            $nuevaPosicion = $index + 1;
            if ($orden->posicion !== $nuevaPosicion) {
                $orden->posicion = $nuevaPosicion;
                $orden->save();
                $cambios++;
            }
        }

        return $cambios;
    }
}
