<?php

namespace App\Servicios\Etiquetas\DTOs;

class ActualizarEtiquetaDatos
{
    public function __construct(
        public string $etiquetaSubId,
        public int $maquinaId,
        public int $longitudSeleccionada,
        public int $operario1Id,
        public ?int $operario2Id,
        public array $opciones = []
    ) {}
}
