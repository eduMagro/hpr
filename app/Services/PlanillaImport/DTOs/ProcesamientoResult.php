<?php

namespace App\Services\PlanillaImport\DTOs;

use App\Models\Planilla;

/**
 * Resultado del procesamiento de una planilla individual.
 */
class ProcesamientoResult
{
    public function __construct(
        public readonly Planilla $planilla,
        public readonly int $elementosCreados,
        public readonly int $etiquetasCreadas
    ) {}
}
