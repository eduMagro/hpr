<?php

namespace App\Services;

use App\Models\OrdenPlanilla;
use App\Models\Elemento;
use App\Models\Planilla;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
     * La posición se calcula según la fecha_estimada_entrega:
     * - Planillas con fecha anterior van primero
     * - Planillas con misma fecha se ordenan por hora
     * - Si no tiene fecha, va al final
     *
     * IMPORTANTE: Debe ejecutarse DESPUÉS de AsignarMaquinaService.
     *
     * @param int $planillaId
     * @return int Número de registros creados
     */
    public function crearOrdenParaPlanilla(int $planillaId): int
    {
        Log::channel('planilla_import')->info("[OrdenPlanilla] Iniciando creacion de orden para planilla {$planillaId}");

        // Obtener la planilla con su fecha de entrega
        $planilla = Planilla::find($planillaId);
        if (!$planilla) {
            Log::channel('planilla_import')->error("[OrdenPlanilla] Planilla {$planillaId} no encontrada");
            return 0;
        }

        $fechaEntregaNueva = $planilla->fecha_estimada_entrega;
        // Asegurar que sea Carbon (a veces llega como string en formato d/m/Y H:i)
        if ($fechaEntregaNueva && is_string($fechaEntregaNueva)) {
            try {
                $fechaEntregaNueva = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $fechaEntregaNueva);
            } catch (\Exception $e) {
                $fechaEntregaNueva = \Carbon\Carbon::parse($fechaEntregaNueva);
            }
        }
        Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId} - Fecha entrega: " . ($fechaEntregaNueva ? $fechaEntregaNueva->format('Y-m-d H:i') : 'SIN FECHA'));

        // Obtener todas las máquinas únicas asignadas a elementos de esta planilla
        $maquinasUsadas = Elemento::where('planilla_id', $planillaId)
            ->whereNotNull('maquina_id')
            ->distinct()
            ->pluck('maquina_id')
            ->filter()
            ->toArray();

        Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId} usa maquinas: " . json_encode($maquinasUsadas));

        if (empty($maquinasUsadas)) {
            Log::channel('planilla_import')->warning("[OrdenPlanilla] Planilla {$planillaId}: no tiene elementos con maquina asignada");
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
                Log::channel('planilla_import')->debug("[OrdenPlanilla] Planilla {$planillaId} + Maquina {$maquinaId}: ya existe, omitiendo");
                continue;
            }

            // Calcular la posición correcta según fecha_estimada_entrega
            $nuevaPosicion = $this->calcularPosicionPorFecha($maquinaId, $fechaEntregaNueva);

            Log::channel('planilla_import')->debug("[OrdenPlanilla] Maquina {$maquinaId}: asignando posicion={$nuevaPosicion} a planilla {$planillaId}");

            // Crear el registro
            $ordenPlanilla = OrdenPlanilla::create([
                'planilla_id' => $planillaId,
                'maquina_id' => $maquinaId,
                'posicion' => $nuevaPosicion,
            ]);

            // Actualizar elementos con este orden_planilla_id
            $elementosActualizados = Elemento::where('planilla_id', $planillaId)
                ->where('maquina_id', $maquinaId)
                ->update(['orden_planilla_id' => $ordenPlanilla->id]);

            Log::channel('planilla_import')->debug("[OrdenPlanilla] Actualizados {$elementosActualizados} elementos con orden_planilla_id={$ordenPlanilla->id}");

            $registrosCreados++;
        }

        if ($registrosDuplicados > 0) {
            Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId}: {$registrosDuplicados} registros ya existian");
        }

        Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId}: creados {$registrosCreados} de " . count($maquinasUsadas) . " registros orden_planillas");

        return $registrosCreados;
    }

    /**
     * Calcula la posición correcta para una planilla en una máquina según su fecha de entrega.
     *
     * Lógica:
     * - Obtiene todas las planillas de la máquina ordenadas por fecha_estimada_entrega
     * - Encuentra la posición donde debe insertarse la nueva planilla
     * - Desplaza las posiciones posteriores para hacer hueco
     *
     * @param int $maquinaId
     * @param \Carbon\Carbon|null $fechaEntrega
     * @return int La posición calculada
     */
    private function calcularPosicionPorFecha(int $maquinaId, $fechaEntrega): int
    {
        // Si no tiene fecha de entrega, va al final
        if (!$fechaEntrega) {
            $ultimaPosicion = OrdenPlanilla::where('maquina_id', $maquinaId)->max('posicion') ?? 0;
            Log::channel('planilla_import')->debug("[OrdenPlanilla] Maquina {$maquinaId}: sin fecha, asignando al final posicion=" . ($ultimaPosicion + 1));
            return $ultimaPosicion + 1;
        }

        // Obtener todas las órdenes de esta máquina con la fecha de entrega de su planilla
        $ordenesExistentes = OrdenPlanilla::where('maquina_id', $maquinaId)
            ->join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
            ->select('orden_planillas.id', 'orden_planillas.posicion', 'planillas.fecha_estimada_entrega')
            ->orderBy('orden_planillas.posicion')
            ->get();

        if ($ordenesExistentes->isEmpty()) {
            Log::channel('planilla_import')->debug("[OrdenPlanilla] Maquina {$maquinaId}: primera planilla, posicion=1");
            return 1; // Primera planilla de esta máquina
        }

        // Buscar la posición donde insertar según la fecha
        $posicionInsertar = null;

        foreach ($ordenesExistentes as $orden) {
            $fechaExistente = $orden->fecha_estimada_entrega;
            // Asegurar que sea Carbon (el join no aplica casts de Eloquent, puede venir en formato d/m/Y H:i)
            if ($fechaExistente && is_string($fechaExistente)) {
                try {
                    $fechaExistente = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $fechaExistente);
                } catch (\Exception $e) {
                    $fechaExistente = \Carbon\Carbon::parse($fechaExistente);
                }
            }

            // Si la planilla existente no tiene fecha, la nueva (que sí tiene) va antes
            if (!$fechaExistente) {
                $posicionInsertar = $orden->posicion;
                Log::channel('planilla_import')->debug("[OrdenPlanilla] Maquina {$maquinaId}: encontrada planilla sin fecha en posicion {$orden->posicion}, insertando antes");
                break;
            }

            // Si la fecha de la nueva es anterior, insertar aquí
            if ($fechaEntrega->lt($fechaExistente)) {
                $posicionInsertar = $orden->posicion;
                Log::channel('planilla_import')->debug("[OrdenPlanilla] Maquina {$maquinaId}: fecha {$fechaEntrega->format('Y-m-d H:i')} < {$fechaExistente->format('Y-m-d H:i')}, insertando en posicion {$orden->posicion}");
                break;
            }
        }

        // Si no encontró posición, va al final
        if ($posicionInsertar === null) {
            $ultimaPosicion = $ordenesExistentes->max('posicion');
            Log::channel('planilla_import')->debug("[OrdenPlanilla] Maquina {$maquinaId}: fecha mas tardia, asignando al final posicion=" . ($ultimaPosicion + 1));
            return $ultimaPosicion + 1;
        }

        // Desplazar las posiciones >= posicionInsertar para hacer hueco
        Log::channel('planilla_import')->debug("[OrdenPlanilla] Maquina {$maquinaId}: desplazando posiciones >= {$posicionInsertar}");

        OrdenPlanilla::where('maquina_id', $maquinaId)
            ->where('posicion', '>=', $posicionInsertar)
            ->orderBy('posicion', 'desc') // Importante: de mayor a menor para evitar colisiones
            ->each(function ($orden) {
                $orden->posicion = $orden->posicion + 1;
                $orden->save();
            });

        return $posicionInsertar;
    }

    /**
     * Elimina registros de orden para una planilla.
     *
     * @param int $planillaId
     * @return int Número de registros eliminados
     */
    public function eliminarOrdenDePlanilla(int $planillaId): int
    {
        Log::channel('planilla_import')->info("[OrdenPlanilla] Iniciando eliminacion de orden para planilla {$planillaId}");

        // Obtener información antes de eliminar
        $registros = OrdenPlanilla::where('planilla_id', $planillaId)->get();
        $count = $registros->count();

        if ($count === 0) {
            Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId}: no tiene registros de orden para eliminar");
            return 0;
        }

        $maquinasAfectadas = $registros->pluck('maquina_id')->unique()->toArray();
        Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId}: eliminando {$count} registros de maquinas: " . json_encode($maquinasAfectadas));

        // Limpiar orden_planilla_id de todos los elementos de esta planilla
        Elemento::where('planilla_id', $planillaId)
            ->update(['orden_planilla_id' => null]);

        OrdenPlanilla::where('planilla_id', $planillaId)->delete();

        Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId}: eliminados {$count} registros correctamente");

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
        Log::channel('planilla_import')->debug("[OrdenPlanilla] Obteniendo maquinas afectadas por planillas: " . json_encode($planillaIds));

        if (empty($planillaIds)) {
            Log::channel('planilla_import')->debug("[OrdenPlanilla] No hay planillas para consultar");
            return [];
        }

        $maquinas = OrdenPlanilla::query()
            ->whereIn('planilla_id', $planillaIds)
            ->distinct()
            ->pluck('maquina_id')
            ->map(fn($v) => (int) $v)
            ->all();

        Log::channel('planilla_import')->debug("[OrdenPlanilla] Maquinas afectadas encontradas: " . json_encode($maquinas));

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
        Log::channel('planilla_import')->info("[OrdenPlanilla] Iniciando recalculo de orden para maquina {$maquinaId}");

        $ordenes = OrdenPlanilla::query()
            ->where('maquina_id', $maquinaId)
            ->orderBy('posicion', 'asc')
            ->get(['id', 'planilla_id', 'posicion']);

        $totalRegistros = $ordenes->count();

        if ($totalRegistros === 0) {
            Log::channel('planilla_import')->info("[OrdenPlanilla] Maquina {$maquinaId}: no tiene planillas asignadas");
            return 0;
        }

        Log::channel('planilla_import')->debug("[OrdenPlanilla] Maquina {$maquinaId}: procesando {$totalRegistros} planillas");

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
            Log::channel('planilla_import')->info("[OrdenPlanilla] Maquina {$maquinaId}: recalculados {$actualizados} de {$totalRegistros} registros");
            Log::channel('planilla_import')->debug("[OrdenPlanilla] Cambios realizados: " . json_encode($cambios));
        } else {
            Log::channel('planilla_import')->info("[OrdenPlanilla] Maquina {$maquinaId}: secuencia ya estaba correcta (1..{$totalRegistros})");
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

        Log::channel('planilla_import')->info("[OrdenPlanilla] Iniciando recalculo multiple para " . count($maquinaIds) . " maquinas: " . json_encode($maquinaIds));

        $resultados = [];
        $totalActualizados = 0;

        foreach ($maquinaIds as $maquinaId) {
            $actualizados = $this->recalcularOrdenDeMaquina((int)$maquinaId);
            $resultados[(int)$maquinaId] = $actualizados;
            $totalActualizados += $actualizados;
        }

        Log::channel('planilla_import')->info("[OrdenPlanilla] Recalculo multiple completado: {$totalActualizados} registros actualizados en total");

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
        Log::channel('planilla_import')->info("[OrdenPlanilla] Iniciando sincronizacion para planilla {$planillaId}");

        // Obtener la planilla para tener su fecha de entrega
        $planilla = Planilla::find($planillaId);
        $fechaEntrega = $planilla ? $planilla->fecha_estimada_entrega : null;

        // 1. Máquinas actualmente en uso por elementos
        $maquinasActuales = Elemento::where('planilla_id', $planillaId)
            ->whereNotNull('maquina_id')
            ->distinct()
            ->pluck('maquina_id')
            ->toArray();

        Log::channel('planilla_import')->debug("[OrdenPlanilla] Planilla {$planillaId} - Maquinas en elementos: " . json_encode($maquinasActuales));

        // 2. Máquinas registradas en orden_planillas
        $maquinasRegistradas = OrdenPlanilla::where('planilla_id', $planillaId)
            ->pluck('maquina_id')
            ->toArray();

        Log::channel('planilla_import')->debug("[OrdenPlanilla] Planilla {$planillaId} - Maquinas en orden_planillas: " . json_encode($maquinasRegistradas));

        // 3. Eliminar máquinas que ya no se usan
        $maquinasAEliminar = array_diff($maquinasRegistradas, $maquinasActuales);
        $eliminados = 0;

        if (!empty($maquinasAEliminar)) {
            Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId} - Eliminando maquinas obsoletas: " . json_encode($maquinasAEliminar));

            // Primero limpiar los elementos
            $elementosLimpiados = Elemento::where('planilla_id', $planillaId)
                ->whereIn('maquina_id', $maquinasAEliminar)
                ->update(['orden_planilla_id' => null]);

            // Luego eliminar los registros de orden_planillas
            $eliminados = OrdenPlanilla::where('planilla_id', $planillaId)
                ->whereIn('maquina_id', $maquinasAEliminar)
                ->delete();

            Log::channel('planilla_import')->debug("[OrdenPlanilla] Eliminados {$eliminados} registros obsoletos, limpiados {$elementosLimpiados} elementos");
        }

        // 4. Crear máquinas nuevas con posición calculada por fecha
        $maquinasACrear = array_diff($maquinasActuales, $maquinasRegistradas);
        $creados = 0;

        if (!empty($maquinasACrear)) {
            Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId} - Creando maquinas nuevas: " . json_encode($maquinasACrear));
        }

        foreach ($maquinasACrear as $maquinaId) {
            // Usar el nuevo método de cálculo por fecha
            $nuevaPosicion = $this->calcularPosicionPorFecha($maquinaId, $fechaEntrega);

            $ordenPlanilla = OrdenPlanilla::create([
                'planilla_id' => $planillaId,
                'maquina_id' => $maquinaId,
                'posicion' => $nuevaPosicion,
            ]);

            // Actualizar elementos con este orden_planilla_id
            $elementosActualizados = Elemento::where('planilla_id', $planillaId)
                ->where('maquina_id', $maquinaId)
                ->update(['orden_planilla_id' => $ordenPlanilla->id]);

            Log::channel('planilla_import')->debug("[OrdenPlanilla] Maquina {$maquinaId}: creada en posicion {$nuevaPosicion}, actualizados {$elementosActualizados} elementos");
            $creados++;
        }

        Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId} sincronizada: +{$creados} -{$eliminados} registros (total actual: " . count($maquinasActuales) . " maquinas)");

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
        Log::channel('planilla_import')->info("[OrdenPlanilla] Verificando integridad de planilla {$planillaId}");

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

        Log::channel('planilla_import')->debug("[OrdenPlanilla] Planilla {$planillaId} - Maquinas en elementos: " . count($maquinasEnElementos));
        Log::channel('planilla_import')->debug("[OrdenPlanilla] Planilla {$planillaId} - Maquinas en orden: " . count($maquinasEnOrden));

        if (!$esConsistente) {
            Log::channel('planilla_import')->warning("[OrdenPlanilla] Planilla {$planillaId} - INCONSISTENCIA DETECTADA:");

            if (!empty($faltantes)) {
                Log::channel('planilla_import')->warning("   Faltantes en orden_planillas: " . json_encode(array_values($faltantes)));
            }

            if (!empty($sobrantes)) {
                Log::channel('planilla_import')->warning("   Sobrantes en orden_planillas: " . json_encode(array_values($sobrantes)));
            }
        } else {
            Log::channel('planilla_import')->info("[OrdenPlanilla] Planilla {$planillaId} - Integridad correcta");
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
