<?php

namespace App\Logging;

use Monolog\Handler\RotatingFileHandler;

class WeeklyRotation
{
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                // Cambiar formato a semanal: laravel-2026-W03.log (año-semana)
                if (method_exists($handler, 'setFilenameFormat')) {
                    $handler->setFilenameFormat('{filename}-{date}', 'Y-\WW');
                }
            }
        }
    }
}
