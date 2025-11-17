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
                    'estado'      => 'pendiente',
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

        $fechaCorte = Carbon::today()->subDays(3); // 👈 fecha de corte hace 7 días

        // Base: planillas en estado pendiente o fabricando
        $base = Planilla::query()->whereIn('estado', ['pendiente', 'fabricando']);
        if (!empty($planillaIds)) {
            $base->whereIn('id', $planillaIds);
        }
        // Métrica: cuántas omitimos por fecha (futuras o sin fecha)
        $omitidasPorFecha = (clone $base)
            ->where(function ($q) use ($fechaCorte) {
                $q->whereNull('fecha_estimada_entrega')
                    ->orWhereDate('fecha_estimada_entrega', '>', $fechaCorte);
            })
            ->count();

        // Planillas candidatas
        $planillas = (clone $base)
            ->whereNotNull('fecha_estimada_entrega')
            ->whereDate('fecha_estimada_entrega', '<=', $fechaCorte)
            ->get();

        foreach ($planillas as $planilla) {
            $subetiquetas = Etiqueta::where('planilla_id', $planilla->id)
                ->whereNotNull('etiqueta_sub_id')
                ->distinct()
                ->pluck('etiqueta_sub_id');

            foreach ($subetiquetas as $subId) {
                $res = $this->completarSubetiqueta($subId);

                if ($res['success']) {
                    $ok++;
                } else {
                    $fail++;
                    $errores[] = [
                        'etiqueta_sub_id' => $subId,
                        'planilla_id'     => $planilla->id,
                        'error'           => $res['message'] ?? 'Error desconocido',
                    ];
                }
            }
        }

        return [
            'success'         => $fail === 0,
            'procesadas_ok'   => $ok,
            'omitidas_fecha'  => $omitidasPorFecha,
            'fallidas'        => $fail,
            'errores'         => $errores,
        ];
    }

    public function completarSubetiqueta(string $etiquetaSubId): array
    {
        try {
            DB::transaction(function () use ($etiquetaSubId) {
                $etiquetas = Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)->get();

                if ($etiquetas->isEmpty()) {
                    throw new \Exception("No se encontraron etiquetas para la subetiqueta $etiquetaSubId");
                }

                $planillaId = $etiquetas->first()->planilla_id;
                $pesoTotal = $etiquetas->sum('peso');

                $codigoPaquete = Paquete::generarCodigo();

                $paquete = Paquete::create([
                    'codigo'          => $codigoPaquete,
                    'planilla_id'     => $planillaId,
                    'etiqueta_sub_id' => $etiquetaSubId,
                    'peso'            => $pesoTotal,
                    'estado'          => 'pendiente',
                ]);

                // Actualizar etiquetas
                Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)->update([
                    'estado'     => 'completada',
                    'paquete_id' => $paquete->id,
                ]);

                // Actualizar elementos
                Elemento::where('etiqueta_sub_id', $etiquetaSubId)->update([
                    'estado' => 'completado',
                ]);

                // Eliminar orden de la planilla en cada máquina
                $ordenes = OrdenPlanilla::lockForUpdate()
                    ->where('planilla_id', $planillaId)
                    ->get()
                    ->groupBy('maquina_id');

                foreach ($ordenes as $maquinaId => $ordenesMaquina) {
                    $posiciones = $ordenesMaquina->pluck('posicion')->sort()->values();

                    foreach ($posiciones as $pos) {
                        OrdenPlanilla::where('maquina_id', $maquinaId)
                            ->where('posicion', '>', $pos)
                            ->decrement('posicion');
                    }

                    OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->where('planilla_id', $planillaId)
                        ->delete();
                }

                // Si ya no quedan más subetiquetas pendientes, marcamos la planilla como completada
                $subetiquetasPendientes = Etiqueta::where('planilla_id', $planillaId)
                    ->whereNotNull('etiqueta_sub_id')
                    ->where('estado', '!=', 'completada')
                    ->exists();

                if (!$subetiquetasPendientes) {
                    Planilla::where('id', $planillaId)->update(['estado' => 'completada']);
                }
            });

            return [
                'success' => true,
                'message' => "Subetiqueta $etiquetaSubId completada.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
