<?php

namespace App\Services;

use App\Models\OrdenPlanilla;
use App\Models\Elemento;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar el orden de planillas por máquina.
 * 
 * Responsabilidades:
 * - Crear registros de orden para planillas nuevas
 * - Recalcular posiciones tras eliminaciones
 * - Mantener secuencias coherentes por máquina
 */
class OrdenPlanillaService
{
    /**
     * Crea entradas en orden_planillas para todas las máquinas usadas en una planilla.
     * 
     * IMPORTANTE: Debe ejecutarse DESPUÉS de AsignarMaquinaService.
     *
     * @param int $planillaId
     * @return int Número de registros creados
     */
    public function crearOrdenParaPlanilla(int $planillaId): int
    {
        // Obtener todas las máquinas únicas asignadas a elementos de esta planilla
        $maquinasUsadas = Elemento::where('planilla_id', $planillaId)
            ->whereNotNull('maquina_id')
            ->distinct()
            ->pluck('maquina_id')
            ->filter()
            ->toArray();

        if (empty($maquinasUsadas)) {
            Log::info("⚠️ Planilla {$planillaId}: no tiene elementos con máquina asignada");
            return 0;
        }

        $registrosCreados = 0;

        foreach ($maquinasUsadas as $maquinaId) {
            // Verificar si ya existe el registro
            $existe = OrdenPlanilla::where('planilla_id', $planillaId)
                ->where('maquina_id', $maquinaId)
                ->exists();

            if ($existe) {
                continue; // Ya existe, no duplicar
            }

            // Obtener la última posición para esta máquina
            $ultimaPosicion = OrdenPlanilla::where('maquina_id', $maquinaId)
                ->max('posicion') ?? 0;

            // Crear el registro
            OrdenPlanilla::create([
                'planilla_id' => $planillaId,
                'maquina_id' => $maquinaId,
                'posicion' => $ultimaPosicion + 1,
            ]);

            $registrosCreados++;
        }

        Log::info("✅ Planilla {$planillaId}: creados {$registrosCreados} registros orden_planillas");

        return $registrosCreados;
    }

    /**
     * Elimina registros de orden para una planilla.
     *
     * @param int $planillaId
     * @return int Número de registros eliminados
     */
    public function eliminarOrdenDePlanilla(int $planillaId): int
    {
        $count = OrdenPlanilla::where('planilla_id', $planillaId)->count();
        OrdenPlanilla::where('planilla_id', $planillaId)->delete();

        return $count;
    }

    /**
     * Obtiene las máquinas afectadas por un conjunto de planillas.
     *
     * @param array $planillaIds
     * @return array IDs de máquinas
     */
    public function obtenerMaquinasAfectadas(array $planillaIds): array
    {
        if (empty($planillaIds)) {
            return [];
        }

        return OrdenPlanilla::query()
            ->whereIn('planilla_id', $planillaIds)
            ->distinct()
            ->pluck('maquina_id')
            ->map(fn($v) => (int) $v)
            ->all();
    }

    /**
     * Recalcula posiciones para una máquina específica (compacta a 1..N).
     *
     * @param int $maquinaId
     * @return int Número de registros actualizados
     */
    public function recalcularOrdenDeMaquina(int $maquinaId): int
    {
        $ordenes = OrdenPlanilla::query()
            ->where('maquina_id', $maquinaId)
            ->orderBy('posicion', 'asc')
            ->get(['id', 'posicion']);

        $actualizados = 0;
        $nuevaPosicion = 1;

        foreach ($ordenes as $fila) {
            if ((int) $fila->posicion !== $nuevaPosicion) {
                OrdenPlanilla::where('id', $fila->id)
                    ->update(['posicion' => $nuevaPosicion]);
                $actualizados++;
            }
            $nuevaPosicion++;
        }

        if ($actualizados > 0) {
            Log::info("🔧 Máquina {$maquinaId}: recalculados {$actualizados} registros");
        }

        return $actualizados;
    }

    /**
     * Recalcula el orden para múltiples máquinas.
     *
     * @param array $maquinaIds
     * @return array ['maquina_id' => registros_actualizados]
     */
    public function recalcularOrdenDeMaquinas(array $maquinaIds): array
    {
        $maquinaIds = array_values(array_unique(array_filter($maquinaIds, fn($x) => !is_null($x))));

        $resultados = [];

        foreach ($maquinaIds as $maquinaId) {
            $resultados[(int)$maquinaId] = $this->recalcularOrdenDeMaquina((int)$maquinaId);
        }

        return $resultados;
    }

    /**
     * Sincroniza orden_planillas con los elementos actuales de una planilla.
     * Útil después de reasignaciones masivas de máquinas.
     *
     * @param int $planillaId
     * @return array ['creados' => int, 'eliminados' => int]
     */
    public function sincronizarOrdenDePlanilla(int $planillaId): array
    {
        // 1. Máquinas actualmente en uso por elementos
        $maquinasActuales = Elemento::where('planilla_id', $planillaId)
            ->whereNotNull('maquina_id')
            ->distinct()
            ->pluck('maquina_id')
            ->toArray();

        // 2. Máquinas registradas en orden_planillas
        $maquinasRegistradas = OrdenPlanilla::where('planilla_id', $planillaId)
            ->pluck('maquina_id')
            ->toArray();

        // 3. Eliminar máquinas que ya no se usan
        $maquinasAEliminar = array_diff($maquinasRegistradas, $maquinasActuales);
        $eliminados = 0;

        if (!empty($maquinasAEliminar)) {
            $eliminados = OrdenPlanilla::where('planilla_id', $planillaId)
                ->whereIn('maquina_id', $maquinasAEliminar)
                ->delete();
        }

        // 4. Crear máquinas nuevas
        $maquinasACrear = array_diff($maquinasActuales, $maquinasRegistradas);
        $creados = 0;

        foreach ($maquinasACrear as $maquinaId) {
            $ultimaPosicion = OrdenPlanilla::where('maquina_id', $maquinaId)
                ->max('posicion') ?? 0;

            OrdenPlanilla::create([
                'planilla_id' => $planillaId,
                'maquina_id' => $maquinaId,
                'posicion' => $ultimaPosicion + 1,
            ]);

            $creados++;
        }

        Log::info("🔄 Planilla {$planillaId} sincronizada: +{$creados} -{$eliminados} registros");

        return [
            'creados' => $creados,
            'eliminados' => $eliminados,
        ];
    }

    /**
     * Verifica la integridad de orden_planillas para una planilla.
     * Útil para debugging.
     *
     * @param int $planillaId
     * @return array Reporte de inconsistencias
     */
    public function verificarIntegridad(int $planillaId): array
    {
        $maquinasEnElementos = Elemento::where('planilla_id', $planillaId)
            ->whereNotNull('maquina_id')
            ->distinct()
            ->pluck('maquina_id')
            ->sort()
            ->values()
            ->toArray();

        $maquinasEnOrden = OrdenPlanilla::where('planilla_id', $planillaId)
            ->pluck('maquina_id')
            ->sort()
            ->values()
            ->toArray();

        $faltantes = array_diff($maquinasEnElementos, $maquinasEnOrden);
        $sobrantes = array_diff($maquinasEnOrden, $maquinasEnElementos);

        return [
            'es_consistente' => empty($faltantes) && empty($sobrantes),
            'maquinas_en_elementos' => $maquinasEnElementos,
            'maquinas_en_orden' => $maquinasEnOrden,
            'faltantes_en_orden' => array_values($faltantes),
            'sobrantes_en_orden' => array_values($sobrantes),
        ];
    }
}
