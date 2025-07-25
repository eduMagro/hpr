<?php

namespace App\Services;

use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\OrdenPlanilla;
use App\Models\Paquete;
use Illuminate\Support\Facades\DB;

class PlanillaService
{
    public function completarPlanilla(int $planillaId): array
    {
        try {
            DB::transaction(function () use ($planillaId) {

                // ✅ Generar el código de paquete
                $codigoPaquete = Paquete::generarCodigo();

                // ✅ Calcular el peso total de las etiquetas de la planilla
                $pesoTotal = Etiqueta::where('planilla_id', $planillaId)->sum('peso');

                // ✅ Generar código del paquete
                $codigoPaquete = Paquete::generarCodigo();

                // ✅ Crear el paquete con el peso total
                $paquete = Paquete::create([
                    'codigo'      => $codigoPaquete,
                    'planilla_id' => $planillaId,
                    'peso'        => $pesoTotal,
                    'estado'      => 'completado',
                ]);

                // ✅ Marcar planilla como completada
                Planilla::where('id', $planillaId)->update(['estado' => 'completada']);

                // ✅ Marcar etiquetas como completadas y asignar paquete_id
                Etiqueta::where('planilla_id', $planillaId)
                    ->whereNotNull('etiqueta_sub_id')
                    ->update([
                        'estado'     => 'completada',
                        'paquete_id' => $paquete->id,
                    ]);

                // ✅ Marcar todos sus elementos como completados
                Elemento::where('planilla_id', $planillaId)->update(['estado' => 'completado']);

                // ✅ Reajustar colas
                $ordenes = OrdenPlanilla::lockForUpdate()
                    ->where('planilla_id', $planillaId)
                    ->get();

                $porMaquina = $ordenes->groupBy('maquina_id');

                foreach ($porMaquina as $maquinaId => $ordenesDeMaquina) {
                    $posiciones = $ordenesDeMaquina->pluck('posicion')->sort()->values();

                    OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->where('planilla_id', $planillaId)
                        ->delete();

                    foreach ($posiciones as $pos) {
                        OrdenPlanilla::where('maquina_id', $maquinaId)
                            ->where('posicion', '>', $pos)
                            ->decrement('posicion');
                    }
                }
            });

            return [
                'success' => true,
                'message' => 'Planilla completada, paquete creado y etiquetas actualizadas correctamente.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
