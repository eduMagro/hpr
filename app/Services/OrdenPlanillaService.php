<?php

namespace App\Services;

use App\Models\OrdenPlanilla;
use App\Models\Elemento;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar el orden de planillas por mÃ¡quina.
 * 
 * Responsabilidades:
 * - Crear registros de orden para planillas nuevas
 * - Recalcular posiciones tras eliminaciones
 * - Mantener secuencias coherentes por mÃ¡quina
 */
class OrdenPlanillaService
{
    /**
     * Crea entradas en orden_planillas para todas las mÃ¡quinas usadas en una planilla.
     * 
     * IMPORTANTE: Debe ejecutarse DESPUÃ‰S de AsignarMaquinaService.
     *
     * @param int $planillaId
     * @return int NÃºmero de registros creados
     */
    public function crearOrdenParaPlanilla(int $planillaId): int
    {
        Log::channel('planilla_import')->info("ðŸ“‹ [OrdenPlanilla] Iniciando creaciÃ³n de orden para planilla {$planillaId}");

        // Obtener todas las mÃ¡quinas Ãºnicas asignadas a elementos de esta planilla
        $maquinasUsadas = Elemento::where('planilla_id', $planillaId)
            ->whereNotNull('maquina_id')
            ->distinct()
            ->pluck('maquina_id')
            ->filter()
            ->toArray();

        Log::channel('planilla_import')->info("ðŸ” [OrdenPlanilla] Planilla {$planillaId} usa mÃ¡quinas: " . json_encode($maquinasUsadas));

        if (empty($maquinasUsadas)) {
            Log::channel('planilla_import')->warning("âš ï¸ [OrdenPlanilla] Planilla {$planillaId}: no tiene elementos con mÃ¡quina asignada");
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
                Log::channel('planilla_import')->debug("â­ï¸ [OrdenPlanilla] Planilla {$planillaId} + MÃ¡quina {$maquinaId}: ya existe, omitiendo");
                continue; // Ya existe, no duplicar
            }

            // Obtener la Ãºltima posiciÃ³n para esta mÃ¡quina
            $ultimaPosicion = OrdenPlanilla::where('maquina_id', $maquinaId)
                ->max('posicion') ?? 0;

            $nuevaPosicion = $ultimaPosicion + 1;

            Log::channel('planilla_import')->debug("âž• [OrdenPlanilla] MÃ¡quina {$maquinaId}: Ãºltima posiciÃ³n={$ultimaPosicion}, asignando posiciÃ³n={$nuevaPosicion} a planilla {$planillaId}");

            // Crear el registro
            $ordenPlanilla = OrdenPlanilla::create([
                'planilla_id' => $planillaId,
                'maquina_id' => $maquinaId,
                'posicion' => $nuevaPosicion,
            ]);

            // ✅ Actualizar elementos con este orden_planilla_id
            $elementosActualizados = Elemento::where('planilla_id', $planillaId)
                ->where('maquina_id', $maquinaId)
                ->update(['orden_planilla_id' => $ordenPlanilla->id]);

            Log::channel('planilla_import')->debug("🔗 [OrdenPlanilla] Actualizados {$elementosActualizados} elementos con orden_planilla_id={$ordenPlanilla->id}");

            $registrosCreados++;
        }

        if ($registrosDuplicados > 0) {
            Log::channel('planilla_import')->info("ðŸ”„ [OrdenPlanilla] Planilla {$planillaId}: {$registrosDuplicados} registros ya existÃ­an");
        }

        Log::channel('planilla_import')->info("âœ… [OrdenPlanilla] Planilla {$planillaId}: creados {$registrosCreados} de " . count($maquinasUsadas) . " registros orden_planillas");

        return $registrosCreados;
    }

    /**
     * Elimina registros de orden para una planilla.
     *
     * @param int $planillaId
     * @return int NÃºmero de registros eliminados
     */
    public function eliminarOrdenDePlanilla(int $planillaId): int
    {
        Log::channel('planilla_import')->info("ðŸ—‘ï¸ [OrdenPlanilla] Iniciando eliminaciÃ³n de orden para planilla {$planillaId}");

        // Obtener informaciÃ³n antes de eliminar
        $registros = OrdenPlanilla::where('planilla_id', $planillaId)->get();
        $count = $registros->count();

        if ($count === 0) {
            Log::channel('planilla_import')->info("â„¹ï¸ [OrdenPlanilla] Planilla {$planillaId}: no tiene registros de orden para eliminar");
            return 0;
        }

        $maquinasAfectadas = $registros->pluck('maquina_id')->unique()->toArray();
        Log::channel('planilla_import')->info("ðŸ“Š [OrdenPlanilla] Planilla {$planillaId}: eliminando {$count} registros de mÃ¡quinas: " . json_encode($maquinasAfectadas));

        // ✅ Limpiar orden_planilla_id de todos los elementos de esta planilla
        Elemento::where('planilla_id', $planillaId)
            ->update(['orden_planilla_id' => null]);

        OrdenPlanilla::where('planilla_id', $planillaId)->delete();

        Log::channel('planilla_import')->info("âœ… [OrdenPlanilla] Planilla {$planillaId}: eliminados {$count} registros correctamente");

        return $count;
    }

    /**
     * Obtiene las mÃ¡quinas afectadas por un conjunto de planillas.
     *
     * @param array $planillaIds
     * @return array IDs de mÃ¡quinas
     */
    public function obtenerMaquinasAfectadas(array $planillaIds): array
    {
        Log::channel('planilla_import')->debug("ðŸ”Ž [OrdenPlanilla] Obteniendo mÃ¡quinas afectadas por planillas: " . json_encode($planillaIds));

        if (empty($planillaIds)) {
            Log::channel('planilla_import')->debug("â„¹ï¸ [OrdenPlanilla] No hay planillas para consultar");
            return [];
        }

        $maquinas = OrdenPlanilla::query()
            ->whereIn('planilla_id', $planillaIds)
            ->distinct()
            ->pluck('maquina_id')
            ->map(fn($v) => (int) $v)
            ->all();

        Log::channel('planilla_import')->debug("ðŸ“‹ [OrdenPlanilla] MÃ¡quinas afectadas encontradas: " . json_encode($maquinas));

        return $maquinas;
    }

    /**
     * Recalcula posiciones para una mÃ¡quina especÃ­fica (compacta a 1..N).
     *
     * @param int $maquinaId
     * @return int NÃºmero de registros actualizados
     */
    public function recalcularOrdenDeMaquina(int $maquinaId): int
    {
        Log::channel('planilla_import')->info("ðŸ”§ [OrdenPlanilla] Iniciando recÃ¡lculo de orden para mÃ¡quina {$maquinaId}");

        $ordenes = OrdenPlanilla::query()
            ->where('maquina_id', $maquinaId)
            ->orderBy('posicion', 'asc')
            ->get(['id', 'planilla_id', 'posicion']);

        $totalRegistros = $ordenes->count();

        if ($totalRegistros === 0) {
            Log::channel('planilla_import')->info("â„¹ï¸ [OrdenPlanilla] MÃ¡quina {$maquinaId}: no tiene planillas asignadas");
            return 0;
        }

        Log::channel('planilla_import')->debug("ðŸ“Š [OrdenPlanilla] MÃ¡quina {$maquinaId}: procesando {$totalRegistros} planillas");

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
            Log::channel('planilla_import')->info("ðŸ”§ [OrdenPlanilla] MÃ¡quina {$maquinaId}: recalculados {$actualizados} de {$totalRegistros} registros");
            Log::channel('planilla_import')->debug("ðŸ“ [OrdenPlanilla] Cambios realizados: " . json_encode($cambios));
        } else {
            Log::channel('planilla_import')->info("âœ“ [OrdenPlanilla] MÃ¡quina {$maquinaId}: secuencia ya estaba correcta (1..{$totalRegistros})");
        }

        return $actualizados;
    }

    /**
     * Recalcula el orden para mÃºltiples mÃ¡quinas.
     *
     * @param array $maquinaIds
     * @return array ['maquina_id' => registros_actualizados]
     */
    public function recalcularOrdenDeMaquinas(array $maquinaIds): array
    {
        $maquinaIds = array_values(array_unique(array_filter($maquinaIds, fn($x) => !is_null($x))));

        Log::channel('planilla_import')->info("ðŸ”§ [OrdenPlanilla] Iniciando recÃ¡lculo mÃºltiple para " . count($maquinaIds) . " mÃ¡quinas: " . json_encode($maquinaIds));

        $resultados = [];
        $totalActualizados = 0;

        foreach ($maquinaIds as $maquinaId) {
            $actualizados = $this->recalcularOrdenDeMaquina((int)$maquinaId);
            $resultados[(int)$maquinaId] = $actualizados;
            $totalActualizados += $actualizados;
        }

        Log::channel('planilla_import')->info("âœ… [OrdenPlanilla] RecÃ¡lculo mÃºltiple completado: {$totalActualizados} registros actualizados en total");

        return $resultados;
    }

    /**
     * Sincroniza orden_planillas con los elementos actuales de una planilla.
     * Ãštil despuÃ©s de reasignaciones masivas de mÃ¡quinas.
     *
     * @param int $planillaId
     * @return array ['creados' => int, 'eliminados' => int]
     */
    public function sincronizarOrdenDePlanilla(int $planillaId): array
    {
        Log::channel('planilla_import')->info("ðŸ”„ [OrdenPlanilla] Iniciando sincronizaciÃ³n para planilla {$planillaId}");

        // 1. MÃ¡quinas actualmente en uso por elementos
        $maquinasActuales = Elemento::where('planilla_id', $planillaId)
            ->whereNotNull('maquina_id')
            ->distinct()
            ->pluck('maquina_id')
            ->toArray();

        Log::channel('planilla_import')->debug("ðŸ“Š [OrdenPlanilla] Planilla {$planillaId} - MÃ¡quinas en elementos: " . json_encode($maquinasActuales));

        // 2. MÃ¡quinas registradas en orden_planillas
        $maquinasRegistradas = OrdenPlanilla::where('planilla_id', $planillaId)
            ->pluck('maquina_id')
            ->toArray();

        Log::channel('planilla_import')->debug("ðŸ“Š [OrdenPlanilla] Planilla {$planillaId} - MÃ¡quinas en orden_planillas: " . json_encode($maquinasRegistradas));

        // 3. Eliminar mÃ¡quinas que ya no se usan
        $maquinasAEliminar = array_diff($maquinasRegistradas, $maquinasActuales);
        $eliminados = 0;

        if (!empty($maquinasAEliminar)) {
            Log::channel('planilla_import')->info("ðŸ—‘ï¸ [OrdenPlanilla] Planilla {$planillaId} - Eliminando mÃ¡quinas obsoletas: " . json_encode($maquinasAEliminar));

            // ✅ Primero limpiar los elementos
            $elementosLimpiados = Elemento::where('planilla_id', $planillaId)
                ->whereIn('maquina_id', $maquinasAEliminar)
                ->update(['orden_planilla_id' => null]);

            // Luego eliminar los registros de orden_planillas
            $eliminados = OrdenPlanilla::where('planilla_id', $planillaId)
                ->whereIn('maquina_id', $maquinasAEliminar)
                ->delete();

            Log::channel('planilla_import')->debug("âœ“ [OrdenPlanilla] Eliminados {$eliminados} registros obsoletos, limpiados {$elementosLimpiados} elementos");
        }

        // 4. Crear mÃ¡quinas nuevas
        $maquinasACrear = array_diff($maquinasActuales, $maquinasRegistradas);
        $creados = 0;

        if (!empty($maquinasACrear)) {
            Log::channel('planilla_import')->info("âž• [OrdenPlanilla] Planilla {$planillaId} - Creando mÃ¡quinas nuevas: " . json_encode($maquinasACrear));
        }

        foreach ($maquinasACrear as $maquinaId) {
            $ultimaPosicion = OrdenPlanilla::where('maquina_id', $maquinaId)
                ->max('posicion') ?? 0;

            $nuevaPosicion = $ultimaPosicion + 1;

            $ordenPlanilla = OrdenPlanilla::create([
                'planilla_id' => $planillaId,
                'maquina_id' => $maquinaId,
                'posicion' => $nuevaPosicion,
            ]);

            // ✅ Actualizar elementos con este orden_planilla_id
            $elementosActualizados = Elemento::where('planilla_id', $planillaId)
                ->where('maquina_id', $maquinaId)
                ->update(['orden_planilla_id' => $ordenPlanilla->id]);


            Log::channel('planilla_import')->debug("âœ“ [OrdenPlanilla] MÃ¡quina {$maquinaId}: creada en posición {$nuevaPosicion}, actualizados {$elementosActualizados} elementos");
            $creados++;
        }

        Log::channel('planilla_import')->info("âœ… [OrdenPlanilla] Planilla {$planillaId} sincronizada: +{$creados} -{$eliminados} registros (total actual: " . count($maquinasActuales) . " mÃ¡quinas)");

        return [
            'creados' => $creados,
            'eliminados' => $eliminados,
        ];
    }

    /**
     * Verifica la integridad de orden_planillas para una planilla.
     * Ãštil para debugging.
     *
     * @param int $planillaId
     * @return array Reporte de inconsistencias
     */
    public function verificarIntegridad(int $planillaId): array
    {
        Log::channel('planilla_import')->info("ðŸ” [OrdenPlanilla] Verificando integridad de planilla {$planillaId}");

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

        Log::channel('planilla_import')->debug("ðŸ“Š [OrdenPlanilla] Planilla {$planillaId} - MÃ¡quinas en elementos: " . count($maquinasEnElementos));
        Log::channel('planilla_import')->debug("ðŸ“Š [OrdenPlanilla] Planilla {$planillaId} - MÃ¡quinas en orden: " . count($maquinasEnOrden));

        if (!$esConsistente) {
            Log::channel('planilla_import')->warning("âš ï¸ [OrdenPlanilla] Planilla {$planillaId} - INCONSISTENCIA DETECTADA:");

            if (!empty($faltantes)) {
                Log::channel('planilla_import')->warning("   âŒ Faltantes en orden_planillas: " . json_encode(array_values($faltantes)));
            }

            if (!empty($sobrantes)) {
                Log::channel('planilla_import')->warning("   âŒ Sobrantes en orden_planillas: " . json_encode(array_values($sobrantes)));
            }
        } else {
            Log::channel('planilla_import')->info("âœ… [OrdenPlanilla] Planilla {$planillaId} - Integridad correcta");
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
