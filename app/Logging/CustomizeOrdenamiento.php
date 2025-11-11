<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;

class CustomizeOrdenamiento
{
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof RotatingFileHandler) {

                // (Opcional) Asegura que exista el directorio destino
                $this->ensureLogDir($handler);

                // Cambia el formato del nombre de archivo: op_YYYY-MM-DD.log
                // ⚠️ Nota: este método existe en Monolog 2.x. En Monolog 3.x lo han eliminado.
                if (method_exists($handler, 'setFilenameFormat')) {
                    $handler->setFilenameFormat('{filename}_{date}', 'Y-m-d');
                }
                // Si tu proyecto usa Monolog 3 y no existe el método, deja el guion por defecto (op-YYYY-MM-DD.log)
                // o cambia el nombre base a 'op' y acepta el guion.

                // Formato de cada línea del log
                $formatter = new LineFormatter(
                    "[%datetime%] %level_name% %message% %context%\n",
                    'Y-m-d H:i:s',
                    true,
                    true
                );
                $handler->setFormatter($formatter);
            }
        }
    }

    private function ensureLogDir(RotatingFileHandler $handler): void
    {
        // En Monolog 2/3 ->getUrl() devuelve la ruta base (op.log) desde la que rota
        $file = method_exists($handler, 'getUrl') ? $handler->getUrl() : null;
        if ($file) {
            $dir = \dirname($file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }
}
