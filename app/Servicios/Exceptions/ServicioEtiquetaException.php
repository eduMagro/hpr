<?php

namespace App\Servicios\Exceptions;

use Exception;
use Illuminate\Http\Request;

class ServicioEtiquetaException extends Exception
{
    protected array $context;
    protected int $status;

    public function __construct(string $message, array $context = [], int $status = 400)
    {
        parent::__construct($message, $status);
        $this->context = $context;
        $this->status  = $status;
    }

    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(), // 👈 clave unificada
                'details' => $this->context,
            ], $this->status);
        }
    }
}
