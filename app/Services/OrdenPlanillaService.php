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
        Log::channel('planilla_import')->info("📋 [OrdenPlanilla] Iniciando creación de orden para planilla {$planillaId}");

        // Obtener todas las máquinas únicas asignadas a elementos de esta planilla
        $maquinasUsadas = Elemento::where('planilla_id', $planillaId)
            ->whereNotNull('maquina_id')
            ->distinct()
            ->pluck('maquina_id')
            ->filter()
            ->toArray();

        Log::channel('planilla_import')->info("🔍 [OrdenPlanilla] Planilla {$planillaId} usa máquinas: " . json_encode($maquinasUsadas));

        if (empty($maquinasUsadas)) {
            Log::channel('planilla_import')->warning("⚠️ [OrdenPlanilla] Planilla {$planillaId}: no tiene elementos con máquina asignada");
            return 0;
        }

        $registrosCreados = 0;
        $registrosDuplicados = 0;

        foreach ($maquinasUsadas as $maquinaId) {
            // Verificar si ya existe el registro
            $existe = OrdenPlanilla::where('planilla_id', $planillaId)
                ->where('maquina_id', $maquinaId)
                ->exists();

            if ($existe) {
                $registrosDuplicados++;
                Log::channel('planilla_import')->debug("⏭️ [OrdenPlanilla] Planilla {$planillaId} + Máquina {$maquinaId}: ya existe, omitiendo");
                continue; // Ya existe, no duplicar
            }

            // Obtener la última posición para esta máquina
            $ultimaPosicion = OrdenPlanilla::where('maquina_id', $maquinaId)
                ->max('posicion') ?? 0;

            $nuevaPosicion = $ultimaPosicion + 1;

            Log::channel('planilla_import')->debug("➕ [OrdenPlanilla] Máquina {$maquinaId}: última posición={$ultimaPosicion}, asignando posición={$nuevaPosicion} a planilla {$planillaId}");

            // Crear el registro
            OrdenPlanilla::create([
                'planilla_id' => $planillaId,
                'maquina_id' => $maquinaId,
                'posicion' => $nuevaPosicion,
            ]);

            $registrosCreados++;
        }

        if ($registrosDuplicados > 0) {
            Log::channel('planilla_import')->info("🔄 [OrdenPlanilla] Planilla {$planillaId}: {$registrosDuplicados} registros ya existían");
        }

        Log::channel('planilla_import')->info("✅ [OrdenPlanilla] Planilla {$planillaId}: creados {$registrosCreados} de " . count($maquinasUsadas) . " registros orden_planillas");

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
        Log::channel('planilla_import')->info("🗑️ [OrdenPlanilla] Iniciando eliminación de orden para planilla {$planillaId}");

        // Obtener información antes de eliminar
        $registros = OrdenPlanilla::where('planilla_id', $planillaId)->get();
        $count = $registros->count();

        if ($count === 0) {
            Log::channel('planilla_import')->info("ℹ️ [OrdenPlanilla] Planilla {$planillaId}: no tiene registros de orden para eliminar");
            return 0;
        }

        $maquinasAfectadas = $registros->pluck('maquina_id')->unique()->toArray();
        Log::channel('planilla_import')->info("📊 [OrdenPlanilla] Planilla {$planillaId}: eliminando {$count} registros de máquinas: " . json_encode($maquinasAfectadas));

        OrdenPlanilla::where('planilla_id', $planillaId)->delete();

        Log::channel('planilla_import')->info("✅ [OrdenPlanilla] Planilla {$planillaId}: eliminados {$count} registros correctamente");

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
        Log::channel('planilla_import')->debug("🔎 [OrdenPlanilla] Obteniendo máquinas afectadas por planillas: " . json_encode($planillaIds));

        if (empty($planillaIds)) {
            Log::channel('planilla_import')->debug("ℹ️ [OrdenPlanilla] No hay planillas para consultar");
            return [];
        }

        $maquinas = OrdenPlanilla::query()
            ->whereIn('planilla_id', $planillaIds)
            ->distinct()
            ->pluck('maquina_id')
            ->map(fn($v) => (int) $v)
            ->all();

        Log::channel('planilla_import')->debug("📋 [OrdenPlanilla] Máquinas afectadas encontradas: " . json_encode($maquinas));

        return $maquinas;
    }

    /**
     * Recalcula posiciones para una máquina específica (compacta a 1..N).
     *
     * @param int $maquinaId
     * @return int Número de registros actualizados
     */
    public function recalcularOrdenDeMaquina(int $maquinaId): int
    {
        Log::channel('planilla_import')->info("🔧 [OrdenPlanilla] Iniciando recálculo de orden para máquina {$maquinaId}");

        $ordenes = OrdenPlanilla::query()
            ->where('maquina_id', $maquinaId)
            ->orderBy('posicion', 'asc')
            ->get(['id', 'planilla_id', 'posicion']);

        $totalRegistros = $ordenes->count();

        if ($totalRegistros === 0) {
            Log::channel('planilla_import')->info("ℹ️ [OrdenPlanilla] Máquina {$maquinaId}: no tiene planillas asignadas");
            return 0;
        }

        Log::channel('planilla_import')->debug("📊 [OrdenPlanilla] Máquina {$maquinaId}: procesando {$totalRegistros} planillas");

        $actualizados = 0;
        $nuevaPosicion = 1;
        $cambios = [];

        foreach ($ordenes as $fila) {
            if ((int) $fila->posicion !== $nuevaPosicion) {
                $posicionAnterior = $fila->posicion;
                OrdenPlanilla::where('id', $fila->id)
                    ->update(['posicion' => $nuevaPosicion]);

                $cambios[] = [
                    'planilla_id' => $fila->planilla_id,
                    'posicion_anterior' => $posicionAnterior,
                    'posicion_nueva' => $nuevaPosicion
                ];

                $actualizados++;
            }
            $nuevaPosicion++;
        }

        if ($actualizados > 0) {
            Log::channel('planilla_import')->info("🔧 [OrdenPlanilla] Máquina {$maquinaId}: recalculados {$actualizados} de {$totalRegistros} registros");
            Log::channel('planilla_import')->debug("📝 [OrdenPlanilla] Cambios realizados: " . json_encode($cambios));
        } else {
            Log::channel('planilla_import')->info("✓ [OrdenPlanilla] Máquina {$maquinaId}: secuencia ya estaba correcta (1..{$totalRegistros})");
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

        Log::channel('planilla_import')->info("🔧 [OrdenPlanilla] Iniciando recálculo múltiple para " . count($maquinaIds) . " máquinas: " . json_encode($maquinaIds));

        $resultados = [];
        $totalActualizados = 0;

        foreach ($maquinaIds as $maquinaId) {
            $actualizados = $this->recalcularOrdenDeMaquina((int)$maquinaId);
            $resultados[(int)$maquinaId] = $actualizados;
            $totalActualizados += $actualizados;
        }

        Log::channel('planilla_import')->info("✅ [OrdenPlanilla] Recálculo múltiple completado: {$totalActualizados} registros actualizados en total");

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
        Log::channel('planilla_import')->info("🔄 [OrdenPlanilla] Iniciando sincronización para planilla {$planillaId}");

        // 1. Máquinas actualmente en uso por elementos
        $maquinasActuales = Elemento::where('planilla_id', $planillaId)
            ->whereNotNull('maquina_id')
            ->distinct()
            ->pluck('maquina_id')
            ->toArray();

        Log::channel('planilla_import')->debug("📊 [OrdenPlanilla] Planilla {$planillaId} - Máquinas en elementos: " . json_encode($maquinasActuales));

        // 2. Máquinas registradas en orden_planillas
        $maquinasRegistradas = OrdenPlanilla::where('planilla_id', $planillaId)
            ->pluck('maquina_id')
            ->toArray();

        Log::channel('planilla_import')->debug("📊 [OrdenPlanilla] Planilla {$planillaId} - Máquinas en orden_planillas: " . json_encode($maquinasRegistradas));

        // 3. Eliminar máquinas que ya no se usan
        $maquinasAEliminar = array_diff($maquinasRegistradas, $maquinasActuales);
        $eliminados = 0;

        if (!empty($maquinasAEliminar)) {
            Log::channel('planilla_import')->info("🗑️ [OrdenPlanilla] Planilla {$planillaId} - Eliminando máquinas obsoletas: " . json_encode($maquinasAEliminar));

            $eliminados = OrdenPlanilla::where('planilla_id', $planillaId)
                ->whereIn('maquina_id', $maquinasAEliminar)
                ->delete();

            Log::channel('planilla_import')->debug("✓ [OrdenPlanilla] Eliminados {$eliminados} registros obsoletos");
        }

        // 4. Crear máquinas nuevas
        $maquinasACrear = array_diff($maquinasActuales, $maquinasRegistradas);
        $creados = 0;

        if (!empty($maquinasACrear)) {
            Log::channel('planilla_import')->info("➕ [OrdenPlanilla] Planilla {$planillaId} - Creando máquinas nuevas: " . json_encode($maquinasACrear));
        }

        foreach ($maquinasACrear as $maquinaId) {
            $ultimaPosicion = OrdenPlanilla::where('maquina_id', $maquinaId)
                ->max('posicion') ?? 0;

            $nuevaPosicion = $ultimaPosicion + 1;

            OrdenPlanilla::create([
                'planilla_id' => $planillaId,
                'maquina_id' => $maquinaId,
                'posicion' => $nuevaPosicion,
            ]);

            Log::channel('planilla_import')->debug("✓ [OrdenPlanilla] Máquina {$maquinaId}: creada en posición {$nuevaPosicion}");
            $creados++;
        }

        Log::channel('planilla_import')->info("✅ [OrdenPlanilla] Planilla {$planillaId} sincronizada: +{$creados} -{$eliminados} registros (total actual: " . count($maquinasActuales) . " máquinas)");

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
        Log::channel('planilla_import')->info("🔍 [OrdenPlanilla] Verificando integridad de planilla {$planillaId}");

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

        $esConsistente = empty($faltantes) && empty($sobrantes);

        Log::channel('planilla_import')->debug("📊 [OrdenPlanilla] Planilla {$planillaId} - Máquinas en elementos: " . count($maquinasEnElementos));
        Log::channel('planilla_import')->debug("📊 [OrdenPlanilla] Planilla {$planillaId} - Máquinas en orden: " . count($maquinasEnOrden));

        if (!$esConsistente) {
            Log::channel('planilla_import')->warning("⚠️ [OrdenPlanilla] Planilla {$planillaId} - INCONSISTENCIA DETECTADA:");

            if (!empty($faltantes)) {
                Log::channel('planilla_import')->warning("   ❌ Faltantes en orden_planillas: " . json_encode(array_values($faltantes)));
            }

            if (!empty($sobrantes)) {
                Log::channel('planilla_import')->warning("   ❌ Sobrantes en orden_planillas: " . json_encode(array_values($sobrantes)));
            }
        } else {
            Log::channel('planilla_import')->info("✅ [OrdenPlanilla] Planilla {$planillaId} - Integridad correcta");
        }

        return [
            'es_consistente' => $esConsistente,
            'maquinas_en_elementos' => $maquinasEnElementos,
            'maquinas_en_orden' => $maquinasEnOrden,
            'faltantes_en_orden' => array_values($faltantes),
            'sobrantes_en_orden' => array_values($sobrantes),
        ];
    }
}
