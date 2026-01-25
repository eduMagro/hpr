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

class DobladoraEtiquetaServicio extends ServicioEtiquetaBase implements EtiquetaServicio
{
    /**
     * Verifica si la etiqueta puede marcarse como completada.
     * Considera estado de la etiqueta y estado2 si tiene maquina_id_2.
     */
    private function puedeCompletarEtiqueta(Etiqueta $etiqueta): bool
    {
        $estadosCompletados = ['fabricado', 'completado', 'fabricada', 'completada'];

        // Verificar estado principal de la etiqueta
        if (!in_array($etiqueta->estado, $estadosCompletados)) {
            return false;
        }

        // Si hay elementos con maquina_id_2, verificar que el estado2 de la ETIQUETA esté completado
        $tieneElementosConMaquina2 = $etiqueta->elementos()
            ->whereNotNull('maquina_id_2')
            ->exists();

        if ($tieneElementosConMaquina2 && !in_array($etiqueta->estado2, $estadosCompletados)) {
            return false;
        }

        return true;
    }

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

            // Elementos de la etiqueta asignados a esta dobladora
            $elementosEnMaquina = $etiqueta->elementos()
                ->where(function ($q) use ($maquina) {
                    $q->where('maquina_id', $maquina->id)
                        ->orWhere('maquina_id_2', $maquina->id);
                })
                ->get();

            // Determinar si esta máquina es secundaria para estos elementos
            $esSecundaria = $elementosEnMaquina->first()?->maquina_id_2 === $maquina->id;

            // Si es máquina secundaria, usar flujo basado en etiqueta->estado2
            if ($esSecundaria) {
                $estado2Actual = $etiqueta->estado2 ?? 'pendiente';

                switch ($estado2Actual) {
                    case 'pendiente':
                        // Iniciar doblado: pendiente -> doblando
                        $etiqueta->estado2 = 'doblando';
                        $etiqueta->fecha_inicio_soldadura = now();
                        // Máquina secundaria → operario2
                        $etiqueta->operario2_id = auth()->id();
                        $etiqueta->save();
                        break;

                    case 'doblando':
                        // Completar doblado: doblando -> completada
                        $etiqueta->estado2 = 'completada';
                        $etiqueta->fecha_finalizacion_soldadura = now();

                        // Verificar si la etiqueta principal también está completa
                        if ($this->puedeCompletarEtiqueta($etiqueta)) {
                            $etiqueta->estado = 'completada';
                            $etiqueta->fecha_finalizacion = now();
                        }
                        $etiqueta->save();
                        break;

                    case 'completada':
                        throw new RuntimeException('Etiqueta ya completada en esta máquina.');
                }
            } else {
                // Flujo normal para máquina principal
                switch ($etiqueta->estado) {
                    case 'fabricada':
                    case 'parcialmente completada':
                        // Inicia fase de doblado
                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'doblando';
                        // Máquina principal → operario1
                        $etiqueta->operario1_id = $datos->operario1Id ?? auth()->id();
                        $etiqueta->save();
                        break;

                    case 'doblando':
                        // Completar doblado en máquina principal
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
            }

            // NOTA: No eliminamos automáticamente la planilla de la cola.
            // El usuario debe hacer clic en "Completar planilla" manualmente.

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
