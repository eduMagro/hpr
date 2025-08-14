<?php

namespace App\Services;

use App\Models\Planilla;
use App\Models\Maquina;
use App\Models\Etiqueta;
use App\Models\OrdenPlanilla;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlanillaColaService
{
    public function retirarPlanillaDeColaSiNoQuedanEtiquetasEnMaquina(Planilla|int $planilla, Maquina|int $maquina): void
    {
        $planillaId = $planilla instanceof Planilla ? $planilla->id : $planilla;
        $maquinaId  = $maquina  instanceof Maquina  ? $maquina->id  : $maquina;

        // ¿Quedan etiquetas “activas” de esa planilla en esa máquina?
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

        if ($quedan) return;

        DB::transaction(function () use ($planillaId, $maquinaId) {
            $registro = OrdenPlanilla::where('maquina_id', $maquinaId)
                ->where('planilla_id', $planillaId)
                ->lockForUpdate()
                ->first();

            if (!$registro) return;

            $tabla = $registro->getTable();
            $colOrden = Schema::hasColumn($tabla, 'posicion') ? 'posicion'
                : (Schema::hasColumn($tabla, 'orden') ? 'orden' : null);

            $posOriginal = $colOrden ? (int) $registro->{$colOrden} : null;

            $registro->delete();

            if ($colOrden && $posOriginal !== null) {
                OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->where($colOrden, '>', $posOriginal)
                    ->decrement($colOrden);
            } else {
                // Fallback: reindexar todo empezando en 1
                $campo = $colOrden ?? 'id';
                $resto = OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->orderBy($campo)
                    ->lockForUpdate()
                    ->get();

                $i = 1;
                foreach ($resto as $o) {
                    if (Schema::hasColumn($o->getTable(), 'posicion')) $o->posicion = $i;
                    if (Schema::hasColumn($o->getTable(), 'orden'))    $o->orden    = $i;
                    $o->save();
                    $i++;
                }
            }
        });
    }
}
