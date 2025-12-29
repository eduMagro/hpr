<?php

namespace App\Services;

use App\Models\EtiquetaEnsamblaje;
use App\Models\Planilla;
use App\Models\PlanillaEntidad;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EtiquetaEnsamblajeService
{
    /**
     * Genera etiquetas de ensamblaje para todas las entidades de una planilla.
     * Crea una etiqueta por cada unidad de cada entidad.
     */
    public function generarParaPlanilla(Planilla $planilla): Collection
    {
        $etiquetasCreadas = collect();

        DB::transaction(function () use ($planilla, &$etiquetasCreadas) {
            foreach ($planilla->entidades as $entidad) {
                $etiquetas = $this->generarParaEntidad($entidad);
                $etiquetasCreadas = $etiquetasCreadas->merge($etiquetas);
            }
        });

        return $etiquetasCreadas;
    }

    /**
     * Genera etiquetas de ensamblaje para una entidad específica.
     * Crea una etiqueta por cada unidad (cantidad).
     */
    public function generarParaEntidad(PlanillaEntidad $entidad): Collection
    {
        $etiquetasCreadas = collect();
        $cantidad = $entidad->cantidad ?? 1;

        // Verificar si ya existen etiquetas para esta entidad
        $etiquetasExistentes = $entidad->etiquetasEnsamblaje()->count();

        if ($etiquetasExistentes >= $cantidad) {
            // Ya están todas las etiquetas generadas
            return $entidad->etiquetasEnsamblaje;
        }

        // Generar las etiquetas faltantes
        for ($i = $etiquetasExistentes + 1; $i <= $cantidad; $i++) {
            $etiqueta = $this->crearEtiqueta($entidad, $i, $cantidad);
            $etiquetasCreadas->push($etiqueta);
        }

        return $etiquetasCreadas;
    }

    /**
     * Crea una etiqueta individual para una unidad específica de una entidad.
     */
    protected function crearEtiqueta(PlanillaEntidad $entidad, int $numeroUnidad, int $totalUnidades): EtiquetaEnsamblaje
    {
        $codigo = EtiquetaEnsamblaje::generarCodigo($entidad, $numeroUnidad, $totalUnidades);

        return EtiquetaEnsamblaje::create([
            'codigo' => $codigo,
            'planilla_id' => $entidad->planilla_id,
            'planilla_entidad_id' => $entidad->id,
            'numero_unidad' => $numeroUnidad,
            'total_unidades' => $totalUnidades,
            'estado' => EtiquetaEnsamblaje::ESTADO_PENDIENTE,
            'marca' => $entidad->marca,
            'situacion' => $entidad->situacion,
            'longitud' => $entidad->longitud_ensamblaje,
            'peso' => $entidad->peso_total ? ($entidad->peso_total / $totalUnidades) : null,
        ]);
    }

    /**
     * Regenera las etiquetas de una planilla (elimina las existentes y crea nuevas).
     * Solo elimina las que están en estado pendiente.
     */
    public function regenerarParaPlanilla(Planilla $planilla): Collection
    {
        DB::transaction(function () use ($planilla) {
            // Solo eliminar etiquetas pendientes
            $planilla->etiquetasEnsamblaje()
                ->where('estado', EtiquetaEnsamblaje::ESTADO_PENDIENTE)
                ->delete();
        });

        return $this->generarParaPlanilla($planilla);
    }

    /**
     * Obtiene estadísticas de etiquetas de ensamblaje para una planilla.
     */
    public function obtenerEstadisticas(Planilla $planilla): array
    {
        $etiquetas = $planilla->etiquetasEnsamblaje;

        return [
            'total' => $etiquetas->count(),
            'pendientes' => $etiquetas->where('estado', EtiquetaEnsamblaje::ESTADO_PENDIENTE)->count(),
            'en_proceso' => $etiquetas->where('estado', EtiquetaEnsamblaje::ESTADO_EN_PROCESO)->count(),
            'completadas' => $etiquetas->where('estado', EtiquetaEnsamblaje::ESTADO_COMPLETADA)->count(),
            'impresas' => $etiquetas->where('impresa', true)->count(),
        ];
    }

    /**
     * Inicia el ensamblaje de una etiqueta.
     */
    public function iniciarEnsamblaje(EtiquetaEnsamblaje $etiqueta, ?int $operarioId = null): bool
    {
        return $etiqueta->iniciar($operarioId);
    }

    /**
     * Completa el ensamblaje de una etiqueta.
     */
    public function completarEnsamblaje(EtiquetaEnsamblaje $etiqueta): bool
    {
        return $etiqueta->completar();
    }

    /**
     * Obtiene etiquetas pendientes de una planilla agrupadas por entidad.
     */
    public function obtenerPendientesPorEntidad(Planilla $planilla): Collection
    {
        return $planilla->etiquetasEnsamblaje()
            ->with('entidad')
            ->where('estado', EtiquetaEnsamblaje::ESTADO_PENDIENTE)
            ->get()
            ->groupBy('planilla_entidad_id');
    }
}
