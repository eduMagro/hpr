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

            switch ($etiqueta->estado) {
                case 'fabricada':
                case 'ensamblada':
                    // Inicia fase de soldadura
                    $etiqueta->fecha_inicio_soldadura = now();
                    $etiqueta->estado = 'soldando';
                    $etiqueta->save();
                    break;

                case 'soldando':
                    // Completar soldadura
                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion_soldadura = now();
                    $etiqueta->fecha_finalizacion = now();
                    $etiqueta->save();
                    break;

                case 'completada':
                    throw new RuntimeException('Etiqueta ya completada.');

                default:
                    break;
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
