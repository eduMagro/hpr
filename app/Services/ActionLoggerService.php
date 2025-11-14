<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ActionLoggerService
{
    protected $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs/actions');
        $this->ensureDirectoryExists();
    }

    /**
     * Asegurar que el directorio de logs existe
     */
    protected function ensureDirectoryExists()
    {
        if (!File::exists($this->logPath)) {
            File::makeDirectory($this->logPath, 0755, true);
        }
    }

    /**
     * Obtener el nombre del archivo CSV para la semana actual
     */
    protected function getWeeklyFileName($module)
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek()->format('Y-m-d');
        $endOfWeek = $now->copy()->endOfWeek()->format('Y-m-d');

        return "{$module}_{$startOfWeek}_to_{$endOfWeek}.csv";
    }

    /**
     * Registrar una acción en el CSV
     */
    public function log($module, $action, $details = [])
    {
        try {
            $fileName = $this->getWeeklyFileName($module);
            $filePath = $this->logPath . '/' . $fileName;

            $user = Auth::user();
            $timestamp = Carbon::now()->format('Y-m-d H:i:s');

            // Preparar los datos de la fila
            $row = [
                'timestamp' => $timestamp,
                'usuario' => $user ? $user->nombre_completo : 'Sistema',
                'email' => $user ? $user->email : 'N/A',
                'accion' => $action,
            ];

            // Agregar detalles adicionales como columnas dinámicas
            foreach ($details as $key => $value) {
                $row[$key] = is_array($value) ? json_encode($value) : $value;
            }

            // Verificar si el archivo existe para escribir encabezados
            $fileExists = File::exists($filePath);

            // Intentar abrir archivo con reintentos
            $maxRetries = 3;
            $retryDelay = 100000; // 100ms en microsegundos
            $handle = false;

            for ($i = 0; $i < $maxRetries; $i++) {
                $handle = @fopen($filePath, 'a');
                if ($handle !== false) {
                    break;
                }
                usleep($retryDelay);
            }

            if ($handle) {
                // Intentar obtener un bloqueo exclusivo con timeout
                if (flock($handle, LOCK_EX)) {
                    // Si el archivo es nuevo, escribir encabezados
                    if (!$fileExists || filesize($filePath) === 0) {
                        fputcsv($handle, array_keys($row));
                    }

                    // Escribir la fila de datos
                    fputcsv($handle, $row);

                    // Liberar el bloqueo
                    flock($handle, LOCK_UN);
                }

                fclose($handle);
            } else {
                // Si no se pudo abrir, registrar en el log de Laravel como fallback
                \Log::warning("No se pudo escribir en el log de acciones: {$module}/{$action}", [
                    'details' => $details,
                    'user' => $user ? $user->email : 'Sistema'
                ]);
            }
        } catch (\Exception $e) {
            // En caso de error, registrar en el log de Laravel
            \Log::error("Error al escribir log de acción: {$e->getMessage()}", [
                'module' => $module,
                'action' => $action,
                'details' => $details
            ]);
        }
    }

    /**
     * Logging específico para Planificación
     */
    public function logPlanificacion($action, $details = [])
    {
        $this->log('planificacion', $action, $details);
    }

    /**
     * Logging específico para Gestionar Salidas
     */
    public function logGestionarSalidas($action, $details = [])
    {
        $this->log('gestionar_salidas', $action, $details);
    }

    /**
     * Logging específico para Máquinas
     */
    public function logMaquinas($action, $details = [])
    {
        $this->log('maquinas', $action, $details);
    }

    /**
     * Obtener logs de una semana específica
     */
    public function getWeeklyLogs($module, $startDate)
    {
        $start = Carbon::parse($startDate)->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $fileName = "{$module}_{$start->format('Y-m-d')}_to_{$end->format('Y-m-d')}.csv";
        $filePath = $this->logPath . '/' . $fileName;

        if (!File::exists($filePath)) {
            return [];
        }

        $logs = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== false) {
                $logs[] = array_combine($headers, $data);
            }

            fclose($handle);
        }

        return $logs;
    }

    /**
     * Listar todos los archivos de logs de un módulo
     */
    public function listModuleLogs($module)
    {
        $files = File::glob($this->logPath . "/{$module}_*.csv");

        return collect($files)->map(function ($file) {
            return [
                'path' => $file,
                'name' => basename($file),
                'size' => File::size($file),
                'modified' => Carbon::createFromTimestamp(File::lastModified($file))
            ];
        })->sortByDesc('modified')->values()->all();
    }
}
