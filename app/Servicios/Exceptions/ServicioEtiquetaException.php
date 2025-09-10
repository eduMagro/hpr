<?php

namespace App\Servicios\Exceptions;

use RuntimeException;
use Throwable;

class ServicioEtiquetaException extends RuntimeException
{
    /** Contexto extra para la UI/logs */
    public array $context;

    public function __construct(
        string $message,
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
}
