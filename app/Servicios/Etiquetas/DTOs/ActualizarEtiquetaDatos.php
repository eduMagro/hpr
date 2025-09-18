<?php

namespace App\Servicios\Etiquetas\DTOs;

use Illuminate\Support\Facades\Log;

class ActualizarEtiquetaDatos
{
    public function __construct(
        public string $etiquetaSubId,
        public int $maquinaId,
        public int $longitudSeleccionada,
        public int $operario1Id,
        public ?int $operario2Id,
        public array $opciones = []
    ) {
        Log::info("Creado DTO ActualizarEtiquetaDatos", [
            'etiquetaSubId'        => $this->etiquetaSubId,
            'maquinaId'            => $this->maquinaId,
            'longitudSeleccionada' => $this->longitudSeleccionada,
            'operario1Id'          => $this->operario1Id,
            'operario2Id'          => $this->operario2Id,
            'opciones'             => $this->opciones,
        ]);
    }
}
