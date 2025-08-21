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

                // ✅ Generar el código de paquete
                $codigoPaquete = Paquete::generarCodigo();
                Log::info("Entrando a completar planilla {$planillaId} con código {$codigoPaquete}");
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

                Log::info("Ajustando cola para planilla {$planillaId}");

                foreach ($porMaquina as $maquinaId => $ordenesDeMaquina) {
                    Log::info("Maquina {$maquinaId} - ordenes: " . $ordenesDeMaquina->count());

                    $posiciones = $ordenesDeMaquina->pluck('posicion')->sort()->values();

                    foreach ($posiciones as $pos) {
                        Log::info("Decrementando posiciones mayores a {$pos} en máquina {$maquinaId}");

                        OrdenPlanilla::where('maquina_id', $maquinaId)
                            ->where('posicion', '>', $pos)
                            ->decrement('posicion');
                    }

                    Log::info("Eliminando orden de planilla {$planillaId} en máquina {$maquinaId}");

                    OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->where('planilla_id', $planillaId)
                        ->delete();
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

    public function completarTodasPlanillas(?array $planillaIds = null): array
    {
        $ok = 0;
        $fail = 0;
        $errores = [];

        $hoy = Carbon::today();

        // Base: solo pendientes (y opcionalmente acotadas por IDs)
        $base = Planilla::query()->where('estado', 'pendiente');
        if (!empty($planillaIds)) {
            $base->whereIn('id', $planillaIds);
        }

        // Métrica: cuántas omitimos por fecha (futuras o sin fecha)
        $omitidasPorFecha = (clone $base)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_estimada_entrega')
                    ->orWhereDate('fecha_estimada_entrega', '>', $hoy);
            })
            ->count();

        // Candidatas a procesar: con fecha <= hoy
        $query = (clone $base)
            ->whereNotNull('fecha_estimada_entrega')
            ->whereDate('fecha_estimada_entrega', '<=', $hoy)
            ->orderBy('id');

        $query->chunkById(100, function ($planillas) use (&$ok, &$fail, &$errores) {
            foreach ($planillas as $p) {
                $res = $this->completarPlanilla($p->id);

                if (is_array($res) && !empty($res['success'])) {
                    $ok++;
                } else {
                    $fail++;
                    $errores[] = [
                        'planilla_id' => $p->id,
                        'error'       => is_array($res) ? ($res['message'] ?? 'Error desconocido') : 'Respuesta inválida',
                    ];
                }
            }
        });

        return [
            'success'         => $fail === 0,
            'procesadas_ok'   => $ok,
            'omitidas_fecha'  => $omitidasPorFecha,
            'fallidas'        => $fail,
            'errores'         => $errores,
        ];
    }
}
