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

                Log::info("Entrando a completar planilla {$planillaId}");

                // ✅ Calcular el peso total de las etiquetas de la planilla
                $pesoTotal = Etiqueta::where('planilla_id', $planillaId)->sum('peso');

                // ✅ Crear el paquete con código único (evita duplicados)
                $paquete = Paquete::crearConCodigoUnico([
                    'planilla_id' => $planillaId,
                    'peso'        => $pesoTotal,
                    'estado'      => 'pendiente',
                ]);

                Log::info("Paquete creado con código {$paquete->codigo}");

                // ✅ Marcar planilla como completada
                Planilla::where('id', $planillaId)->update(['estado' => 'completada']);

                // ✅ Marcar etiquetas como completadas y asignar paquete_id
                Etiqueta::where('planilla_id', $planillaId)
                    ->whereNotNull('etiqueta_sub_id')
                    ->update([
                        'estado'     => 'completada',
                        'paquete_id' => $paquete->id,
                    ]);

                // ✅ Reajustar colas (optimizado)
                $maquinasAfectadas = OrdenPlanilla::where('planilla_id', $planillaId)
                    ->pluck('maquina_id')
                    ->unique()
                    ->toArray();

                Log::info("Ajustando cola para planilla {$planillaId}");

                OrdenPlanilla::where('planilla_id', $planillaId)->delete();

                // Reindexar posiciones de cada máquina afectada
                foreach ($maquinasAfectadas as $maquinaId) {
                    Log::info("Reindexando posiciones en máquina {$maquinaId}");
                    DB::statement('SET @pos := 0');
                    DB::statement('
                        UPDATE orden_planillas
                        SET posicion = (@pos := @pos + 1)
                        WHERE maquina_id = ?
                        ORDER BY posicion
                    ', [$maquinaId]);
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

    public function completarTodasPlanillas(?array $planillaIds = null, ?string $fechaCorteStr = null): array
    {
        // Sin límite de tiempo para procesar muchas planillas
        set_time_limit(0);

        // Aumentar timeout de bloqueo de MySQL (5 minutos)
        DB::statement('SET SESSION innodb_lock_wait_timeout = 300');

        $ok = 0;
        $fail = 0;
        $errores = [];
        $omitidasPorFecha = 0;

        // Base: planillas en cualquier estado (incluye completadas para corregir inconsistencias)
        $base = Planilla::query()->whereIn('estado', ['pendiente', 'fabricando', 'completada']);

        // Si hay planillas específicas, procesarlas sin filtro de fecha
        if (!empty($planillaIds)) {
            $base->whereIn('id', $planillaIds);
            $planillas = $base->get();
        } else {
            // Si no hay planillas específicas, usar fecha de corte
            $fechaCorte = $fechaCorteStr
                ? Carbon::parse($fechaCorteStr)->endOfDay()
                : Carbon::today()->endOfDay();

            // Métrica: cuántas omitimos por fecha (futuras o sin fecha)
            $omitidasPorFecha = (clone $base)
                ->where(function ($q) use ($fechaCorte) {
                    $q->whereNull('fecha_estimada_entrega')
                        ->orWhereDate('fecha_estimada_entrega', '>', $fechaCorte);
                })
                ->count();

            // Planillas candidatas por fecha
            $planillas = (clone $base)
                ->whereNotNull('fecha_estimada_entrega')
                ->whereDate('fecha_estimada_entrega', '<=', $fechaCorte)
                ->get();
        }

        $planillasCompletadas = 0;

        foreach ($planillas as $planilla) {
            $subetiquetas = Etiqueta::where('planilla_id', $planilla->id)
                ->whereNotNull('etiqueta_sub_id')
                ->distinct()
                ->pluck('etiqueta_sub_id');

            $planillaProcesada = false;

            foreach ($subetiquetas as $subId) {
                $res = $this->completarSubetiqueta($subId);

                if ($res['success']) {
                    $ok++;
                    $planillaProcesada = true;
                } else {
                    $fail++;
                    $errores[] = [
                        'etiqueta_sub_id' => $subId,
                        'planilla_id'     => $planilla->id,
                        'error'           => $res['message'] ?? 'Error desconocido',
                    ];
                }
            }

            // Si no tiene subetiquetas o ya se procesaron, completar la planilla directamente
            if ($subetiquetas->isEmpty() || !$planillaProcesada) {
                // Actualizar todas las etiquetas de la planilla a completada
                Etiqueta::where('planilla_id', $planilla->id)
                    ->where('estado', '!=', 'completada')
                    ->update(['estado' => 'completada']);

                // Eliminar de orden_planillas
                $maquinasAfectadas = OrdenPlanilla::where('planilla_id', $planilla->id)
                    ->pluck('maquina_id')
                    ->unique()
                    ->toArray();

                OrdenPlanilla::where('planilla_id', $planilla->id)->delete();

                // Reindexar posiciones de cada máquina afectada (optimizado con una sola consulta)
                foreach ($maquinasAfectadas as $maquinaId) {
                    DB::statement('SET @pos := 0');
                    DB::statement('
                        UPDATE orden_planillas
                        SET posicion = (@pos := @pos + 1)
                        WHERE maquina_id = ?
                        ORDER BY posicion
                    ', [$maquinaId]);
                }

                // Marcar planilla como completada
                $planilla->update(['estado' => 'completada']);
                $planillaProcesada = true;
            }

            if ($planillaProcesada) {
                $planillasCompletadas++;
            }
        }

        return [
            'success'              => $fail === 0,
            'procesadas_ok'        => $ok,
            'planillas_completadas' => $planillasCompletadas,
            'omitidas_fecha'       => $omitidasPorFecha,
            'fallidas'             => $fail,
            'errores'              => $errores,
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

                // Verificar si alguna etiqueta ya tiene paquete asignado
                $paqueteExistenteId = $etiquetas->whereNotNull('paquete_id')->pluck('paquete_id')->first();

                if ($paqueteExistenteId) {
                    $paquete = Paquete::find($paqueteExistenteId);
                }

                if (empty($paquete)) {
                    $paquete = Paquete::crearConCodigoUnico([
                        'planilla_id' => $planillaId,
                        'peso'        => $pesoTotal,
                        'estado'      => 'pendiente',
                    ]);
                }

                // Actualizar etiquetas
                Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)->update([
                    'estado'     => 'completada',
                    'paquete_id' => $paquete->id,
                ]);

                // Actualizar elementos - marcar como elaborados
                Elemento::where('etiqueta_sub_id', $etiquetaSubId)->update([
                    'elaborado' => 1,
                ]);

                // Eliminar orden de la planilla y reindexar posiciones
                $maquinasAfectadas = OrdenPlanilla::lockForUpdate()
                    ->where('planilla_id', $planillaId)
                    ->pluck('maquina_id')
                    ->unique()
                    ->toArray();

                OrdenPlanilla::where('planilla_id', $planillaId)->delete();

                // Reindexar posiciones de cada máquina afectada (optimizado)
                foreach ($maquinasAfectadas as $maquinaId) {
                    DB::statement('SET @pos := 0');
                    DB::statement('
                        UPDATE orden_planillas
                        SET posicion = (@pos := @pos + 1)
                        WHERE maquina_id = ?
                        ORDER BY posicion
                    ', [$maquinaId]);
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
