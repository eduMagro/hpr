<?php

namespace App\Servicios\Etiquetas\Servicios;

use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Servicios\Etiquetas\Base\ServicioEtiquetaBase;
use App\Servicios\Etiquetas\Contratos\EtiquetaServicio;
use App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos;
use App\Servicios\Etiquetas\Resultados\ActualizarEtiquetaResultado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SoldadoraEtiquetaServicio extends ServicioEtiquetaBase implements EtiquetaServicio
{
    public function actualizar(ActualizarEtiquetaDatos $datos): ActualizarEtiquetaResultado
    {
        return DB::transaction(function () use ($datos) {
            /** @var Maquina $maquina */
            $maquina = Maquina::findOrFail($datos->maquinaId);

            $etiqueta = Etiqueta::with('elementos.planilla')
                ->where('etiqueta_sub_id', $datos->etiquetaSubId)
                ->lockForUpdate()
                ->firstOrFail();

            $planilla = $etiqueta->planilla_id ? Planilla::find($etiqueta->planilla_id) : null;

            $warnings = [];
            $productosAfectados = [];

            // Elementos de la etiqueta en esta máquina (primaria o secundaria)
            $elementosEnMaquina = $etiqueta->elementos()
                ->where(function ($q) use ($maquina) {
                    $q->where('maquina_id', $maquina->id)
                        ->orWhere('maquina_id_2', $maquina->id);
                })
                ->get();

            switch ($etiqueta->estado) {
                case 'fabricada':
                case 'ensamblada':
                    // Inicia fase de soldadura
                    $etiqueta->fecha_inicio_soldadura = now();
                    $etiqueta->estado = 'soldando';
                    $etiqueta->soldador1_id = $datos->operario1Id;
                    $etiqueta->soldador2_id = $datos->operario2Id;
                    $etiqueta->save();
                    break;

                case 'soldando':
                    // Completa elementos en esta máquina y cierra soldadura
                    foreach ($elementosEnMaquina as $el) {
                        $el->estado = 'completado';
                        $el->users_id = $datos->operario1Id;
                        $el->users_id_2 = $datos->operario2Id;
                        $el->save();
                    }

                    $todosCompletados = $etiqueta->elementos()
                        ->where('estado', '!=', 'completado')
                        ->doesntExist();

                    if ($todosCompletados) {
                        $etiqueta->estado = 'completada';
                        $etiqueta->fecha_finalizacion_soldadura = now();
                        $etiqueta->fecha_finalizacion = now();
                        $etiqueta->save();
                    } else {
                        // Aún quedan en otras máquinas
                        $etiqueta->fecha_finalizacion_soldadura = now();
                        $etiqueta->save();
                    }
                    break;

                case 'completada':
                    throw new RuntimeException('Etiqueta ya completada.');

                default:
                    break;
            }

            // Si planilla queda sin pendientes en esta máquina, reordenar cola
            if ($planilla) {
                $quedanPendientesEnEstaMaquina = Elemento::where('planilla_id', $planilla->id)
                    ->where(function ($q) use ($maquina) {
                        $q->where('maquina_id', $maquina->id)
                            ->orWhere('maquina_id_2', $maquina->id);
                    })
                    ->where(function ($q) {
                        $q->whereNull('estado')->orWhere('estado', '!=', 'completado');
                    })
                    ->exists();

                if (!$quedanPendientesEnEstaMaquina) {
                    DB::transaction(function () use ($planilla, $maquina) {
                        $registro = OrdenPlanilla::where('planilla_id', $planilla->id)
                            ->where('maquina_id', $maquina->id)
                            ->lockForUpdate()
                            ->first();
                        if ($registro) {
                            $pos = $registro->posicion;
                            $registro->delete();
                            OrdenPlanilla::where('maquina_id', $maquina->id)
                                ->where('posicion', '>', $pos)
                                ->decrement('posicion');
                        }
                    });
                }
            }

            $etiqueta->refresh();
            return new ActualizarEtiquetaResultado(
                etiqueta: $etiqueta,
                warnings: $warnings,
                productosAfectados: $productosAfectados,
                metricas: [
                    'maquina_id' => $maquina->id,
                ]
            );
        });
    }
}
