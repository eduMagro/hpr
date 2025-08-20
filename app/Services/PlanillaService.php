<?php

namespace App\Services;

use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\OrdenPlanilla;
use App\Models\Paquete;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PlanillaService
{
    public function completarPlanilla(int $planillaId): array
    {
        try {
            DB::transaction(function () use ($planillaId) {
                Log::info("Completando planilla {$planillaId} creando un paquete por cada etiqueta");

                // Obtener las etiquetas válidas de la planilla
                $etiquetas = Etiqueta::where('planilla_id', $planillaId)
                    ->whereNotNull('etiqueta_sub_id')
                    ->get();

                foreach ($etiquetas as $etiqueta) {
                    // Generar código único para el paquete
                    $codigoPaquete = Paquete::generarCodigo();

                    // Crear paquete individual con el peso de la etiqueta
                    $paquete = Paquete::create([
                        'codigo'      => $codigoPaquete,
                        'planilla_id' => $planillaId,
                        'peso'        => $etiqueta->peso,
                        'estado'      => 'completado',
                    ]);

                    // Actualizar la etiqueta: marcar como completada y asignar paquete
                    $etiqueta->update([
                        'estado'     => 'completada',
                        'paquete_id' => $paquete->id,
                    ]);

                    // Actualizar sus elementos
                    foreach ($etiqueta->elementos as $elemento) {
                        $elemento->update([
                            'estado' => 'completado',
                        ]);
                    }

                    Log::info("Etiqueta {$etiqueta->id} completada y asignada al paquete {$paquete->codigo}");
                }

                // Marcar planilla como completada
                Planilla::where('id', $planillaId)->update(['estado' => 'completada']);

                // ✅ Reajustar colas
                $ordenes = OrdenPlanilla::lockForUpdate()
                    ->where('planilla_id', $planillaId)
                    ->get();

                $porMaquina = $ordenes->groupBy('maquina_id');

                foreach ($porMaquina as $maquinaId => $ordenesDeMaquina) {
                    $posiciones = $ordenesDeMaquina->pluck('posicion')->sort()->values();

                    foreach ($posiciones as $pos) {
                        OrdenPlanilla::where('maquina_id', $maquinaId)
                            ->where('posicion', '>', $pos)
                            ->decrement('posicion');
                    }

                    OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->where('planilla_id', $planillaId)
                        ->delete();
                }

                Log::info("Planilla {$planillaId} completada y todas las etiquetas empaquetadas.");
            });

            return [
                'success' => true,
                'message' => 'Se crearon paquetes individuales y se completó la planilla correctamente.'
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }


    public function completarTodasPlanillas(?array $planillaIds = null): array
    {
        $ok = 0;
        $fail = 0;
        $omitidasPorFecha = 0;
        $errores = [];

        $fechaLimite = Carbon::now()->subDays(5)->startOfDay(); // hoy - 5 días

        $query = Planilla::query()
            ->where('estado', 'pendiente')   // ✅ solo pendientes
            ->orderBy('id');

        // Si pasas IDs, limita a esos (pero siguen siendo "pendientes")
        if ($planillaIds && count($planillaIds)) {
            $query->whereIn('id', $planillaIds);
        }

        $query->chunkById(100, function ($planillas) use (&$ok, &$fail, &$omitidasPorFecha, &$errores, $fechaLimite) {
            foreach ($planillas as $p) {

                $res = $this->completarPlanilla($p->id);

                if ($res['success']) {
                    $ok++;
                } else {
                    $fail++;
                    $errores[] = [
                        'planilla_id' => $p->id,
                        'error'       => $res['message'] ?? 'Error desconocido',
                    ];
                }
            }
        });

        return [
            'success'          => $fail === 0,
            'procesadas_ok'    => $ok,
            'omitidas_fecha'   => $omitidasPorFecha,
            'fallidas'         => $fail,
            'errores'          => $errores,
        ];
    }
}
