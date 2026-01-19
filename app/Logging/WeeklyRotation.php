<?php

namespace App\Logging;

use Monolog\Handler\RotatingFileHandler;

class WeeklyRotation
{
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                // Cambiar formato a mensual: laravel-2026-01.log (año-mes)
                // Nota: Monolog solo soporta Y-m-d (diario), Y-m (mensual), Y (anual)
                // No soporta rotación semanal, por lo que usamos mensual
                if (method_exists($handler, 'setFilenameFormat')) {
                    $handler->setFilenameFormat('{filename}-{date}', 'Y-m');
                }
            }
        }
    }
}
