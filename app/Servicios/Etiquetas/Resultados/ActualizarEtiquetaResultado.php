<?php

namespace App\Servicios\Etiquetas\Resultados;

use App\Models\Etiqueta;

class ActualizarEtiquetaResultado
{
    /** @param array<int, array{producto_id:int, stock_final:float}> $productosAfectados */
    public function __construct(
        public Etiqueta $etiqueta,
        public array $warnings = [],
        public array $productosAfectados = [],
        public array $metricas = []
    ) {}

    public function toArray(): array
    {
        return [
            'etiqueta_id'        => $this->etiqueta->id,
            'warnings'           => $this->warnings,
            'productosAfectados' => $this->productosAfectados,
            'metricas'           => $this->metricas,
        ];
    }
}
