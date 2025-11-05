<?php

namespace App\Services\PlanillaImport\DTOs;

/**
 * Resultado de la validación de un archivo Excel.
 */
class ValidacionResult
{
    public function __construct(
        protected bool $valido,
        protected array $errores = [],
        protected array $advertencias = []
    ) {}

    public function esValido(): bool
    {
        return $this->valido;
    }

    public function errores(): array
    {
        return $this->errores;
    }

    public function advertencias(): array
    {
        return $this->advertencias;
    }

    public function tieneAdvertencias(): bool
    {
        return !empty($this->advertencias);
    }
}
