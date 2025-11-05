<?php

namespace App\Services;

use App\Models\Planilla;
use App\Models\Maquina;
use App\Models\Etiqueta;
use App\Models\OrdenPlanilla;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PlanillaColaService
{
    /**
     * 🔹 Retira la planilla de la cola si TODAS las etiquetas están
     * completadas y paquetizadas.
     */
    public function retirarSiPlanillaCompletamentePaquetizadaYCompletada(Planilla|int $planilla, Maquina|int $maquina): void
    {
        $planillaId = $planilla instanceof Planilla ? $planilla->id : $planilla;
        $maquinaId  = $maquina  instanceof Maquina  ? $maquina->id  : $maquina;

        // ▶️ Buscar etiquetas que bloquean la retirada
        $bloqueantes = Etiqueta::where('planilla_id', $planillaId)
            ->where(function ($q) {
                $q->whereNull('paquete_id')
                    ->orWhere('estado', '!=', 'completada');
            })
            ->whereHas('elementos', fn($q) => $q->where(function ($qq) use ($maquinaId) {
                $qq->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_id_2', $maquinaId)
                    ->orWhere('maquina_id_3', $maquinaId);
            }))
            ->get(['id', 'etiqueta_sub_id', 'estado', 'paquete_id']);

        if ($bloqueantes->isNotEmpty()) {
            // 🧩 Mostrar siempre algo: etiqueta_sub_id o ID como fallback
            $detalles = $bloqueantes->map(function ($e) {
                $codigo = $e->etiqueta_sub_id ?: "ID:{$e->id}";
                $estado = $e->estado ?? '??';
                $paquete = $e->paquete_id ?? 'NULL';
                return "({$codigo} | estado: {$estado} | paquete: {$paquete})";
            })->implode(', ');

            Log::debug("[PlanillaColaService] ❌ Planilla {$planillaId} no se retira de la máquina {$maquinaId}: etiquetas bloqueantes → {$detalles}");
            return;
        }

        Log::info("[PlanillaColaService] ✅ Todas las etiquetas de la planilla {$planillaId} están completadas y paquetizadas. Procediendo a eliminar de cola.");
        $this->eliminarDeColaYReordenar($planillaId, $maquinaId);
    }

    /**
     * 🔧 Elimina la planilla de la cola y reordena posiciones
     */
    private function eliminarDeColaYReordenar(int $planillaId, int $maquinaId): void
    {
        DB::transaction(function () use ($planillaId, $maquinaId) {
            $registro = OrdenPlanilla::where('maquina_id', $maquinaId)
                ->where('planilla_id', $planillaId)
                ->lockForUpdate()
                ->first();

            if (!$registro) {
                Log::warning("[PlanillaColaService] ⚠️ No se encontró el registro en orden_planillas para planilla {$planillaId} y máquina {$maquinaId}.");
                return;
            }

            $tabla = $registro->getTable();
            $colOrden = Schema::hasColumn($tabla, 'posicion')
                ? 'posicion'
                : (Schema::hasColumn($tabla, 'orden') ? 'orden' : null);

            $posOriginal = $colOrden ? (int) $registro->{$colOrden} : null;

            Log::info("[PlanillaColaService] 🗑️ Eliminando planilla {$planillaId} de máquina {$maquinaId} (posición actual: {$posOriginal})");

            $registro->delete();

            if ($colOrden && $posOriginal !== null) {
                $afectadas = OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->where($colOrden, '>', $posOriginal)
                    ->orderBy($colOrden)
                    ->lockForUpdate()
                    ->get();

                foreach ($afectadas as $o) {
                    $valorAnterior = $o->{$colOrden};
                    $o->{$colOrden} = $valorAnterior - 1;
                    $o->save();
                }
            } else {
                Log::warning("[PlanillaColaService] ❗ No se detectó columna 'posicion' o 'orden' en tabla {$tabla}. No se reindexó nada.");
            }
        });
    }

    /**
     * 🟠 Regla antigua: retira planilla si no quedan etiquetas activas en la máquina
     */
    public function retirarPlanillaDeColaSiNoQuedanEtiquetasEnMaquina(Planilla|int $planilla, Maquina|int $maquina): void
    {
        $planillaId = $planilla instanceof Planilla ? $planilla->id : $planilla;
        $maquinaId  = $maquina  instanceof Maquina  ? $maquina->id  : $maquina;

        $estadosActivos = ['pendiente', 'fabricando', 'ensamblando', 'soldando', 'parcialmente_completada'];

        $quedan = Etiqueta::query()
            ->where('planilla_id', $planillaId)
            ->whereIn('estado', $estadosActivos)
            ->whereHas('elementos', fn($q) => $q->where(function ($qq) use ($maquinaId) {
                $qq->where('maquina_id', $maquinaId)
                    ->orWhere('maquina_id_2', $maquinaId)
                    ->orWhere('maquina_id_3', $maquinaId);
            }))
            ->exists();

        if ($quedan) {
            Log::debug("[PlanillaColaService] ⛔ Aún quedan etiquetas activas de planilla {$planillaId} en máquina {$maquinaId}.");
            return;
        }

        Log::info("[PlanillaColaService] ✅ No quedan etiquetas activas. Eliminando planilla {$planillaId} de máquina {$maquinaId}.");
        $this->eliminarDeColaYReordenar($planillaId, $maquinaId);
    }
}
