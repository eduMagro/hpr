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
     * OPTIMIZADO: Usa bulk insert para mejor rendimiento.
     */
    public function generarParaPlanilla(Planilla $planilla): Collection
    {
        $now = now();
        $etiquetasInsert = [];

        // Cargar entidades con conteo de etiquetas existentes
        $entidades = $planilla->entidades()
            ->withCount('etiquetasEnsamblaje')
            ->get();

        foreach ($entidades as $entidad) {
            $cantidad = $entidad->cantidad ?? 1;
            $etiquetasExistentes = $entidad->etiquetas_ensamblaje_count ?? 0;

            if ($etiquetasExistentes >= $cantidad) {
                continue; // Ya tiene todas las etiquetas
            }

            // Preparar datos para bulk insert
            $marca = $entidad->marca ? mb_substr($entidad->marca, 0, 50) : null;
            $situacion = $entidad->situacion ? mb_substr($entidad->situacion, 0, 100) : null;
            $pesoUnitario = $entidad->peso_total ? ($entidad->peso_total / $cantidad) : null;

            for ($i = $etiquetasExistentes + 1; $i <= $cantidad; $i++) {
                $etiquetasInsert[] = [
                    'codigo' => $this->generarCodigoRapido($entidad, $i, $cantidad),
                    'planilla_id' => $entidad->planilla_id,
                    'planilla_entidad_id' => $entidad->id,
                    'numero_unidad' => $i,
                    'total_unidades' => $cantidad,
                    'estado' => EtiquetaEnsamblaje::ESTADO_PENDIENTE,
                    'marca' => $marca,
                    'situacion' => $situacion,
                    'longitud' => $entidad->longitud_ensamblaje,
                    'peso' => $pesoUnitario,
                    'impresa' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Bulk insert en chunks de 100 para evitar límites de MySQL
        if (!empty($etiquetasInsert)) {
            foreach (array_chunk($etiquetasInsert, 100) as $chunk) {
                EtiquetaEnsamblaje::insert($chunk);
            }
        }

        // Retornar las etiquetas creadas
        return $planilla->etiquetasEnsamblaje()->get();
    }

    /**
     * Genera código de etiqueta de forma rápida (sin cargar modelo completo).
     */
    protected function generarCodigoRapido(PlanillaEntidad $entidad, int $numeroUnidad, int $totalUnidades): string
    {
        $marca = strtoupper($entidad->marca ?? 'SM');
        $marca = preg_replace('/\s+/', '', $marca);
        $marca = substr($marca, 0, 20);

        return sprintf('ENS-%s-%d-%d/%d', $marca, $entidad->id, $numeroUnidad, $totalUnidades);
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

        // Preparar datos para bulk insert
        $now = now();
        $marca = $entidad->marca ? mb_substr($entidad->marca, 0, 50) : null;
        $situacion = $entidad->situacion ? mb_substr($entidad->situacion, 0, 100) : null;
        $pesoUnitario = $entidad->peso_total ? ($entidad->peso_total / $cantidad) : null;
        $etiquetasInsert = [];

        for ($i = $etiquetasExistentes + 1; $i <= $cantidad; $i++) {
            $etiquetasInsert[] = [
                'codigo' => $this->generarCodigoRapido($entidad, $i, $cantidad),
                'planilla_id' => $entidad->planilla_id,
                'planilla_entidad_id' => $entidad->id,
                'numero_unidad' => $i,
                'total_unidades' => $cantidad,
                'estado' => EtiquetaEnsamblaje::ESTADO_PENDIENTE,
                'marca' => $marca,
                'situacion' => $situacion,
                'longitud' => $entidad->longitud_ensamblaje,
                'peso' => $pesoUnitario,
                'impresa' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Bulk insert
        if (!empty($etiquetasInsert)) {
            EtiquetaEnsamblaje::insert($etiquetasInsert);
        }

        return $entidad->etiquetasEnsamblaje()->get();
    }

    /**
     * Crea una etiqueta individual para una unidad específica de una entidad.
     * @deprecated Usar generarParaEntidad con bulk insert
     */
    protected function crearEtiqueta(PlanillaEntidad $entidad, int $numeroUnidad, int $totalUnidades): EtiquetaEnsamblaje
    {
        $codigo = EtiquetaEnsamblaje::generarCodigo($entidad, $numeroUnidad, $totalUnidades);

        // Truncar campos para evitar "Data too long" error
        // etiquetas_ensamblaje.marca: varchar(50), planilla_entidades.marca: varchar(100)
        // etiquetas_ensamblaje.situacion: varchar(100), planilla_entidades.situacion: varchar(255)
        $marca = $entidad->marca ? mb_substr($entidad->marca, 0, 50) : null;
        $situacion = $entidad->situacion ? mb_substr($entidad->situacion, 0, 100) : null;

        return EtiquetaEnsamblaje::create([
            'codigo' => $codigo,
            'planilla_id' => $entidad->planilla_id,
            'planilla_entidad_id' => $entidad->id,
            'numero_unidad' => $numeroUnidad,
            'total_unidades' => $totalUnidades,
            'estado' => EtiquetaEnsamblaje::ESTADO_PENDIENTE,
            'marca' => $marca,
            'situacion' => $situacion,
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
