<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\File;
use App\Models\Planilla;
use App\Models\Elemento;

class SyncMonitor extends Component
{
    public bool $isOpen = false;
    public array $logs = [];
    public array $errors = [];
    public int $totalPlanillas = 0;
    public int $totalElementos = 0;
    public int $batchesOk = 0;
    public int $batchesError = 0;
    public string $currentProgress = '0/0';
    public string $currentYear = '-';
    public string $lastUpdate = '-';
    public bool $isRunning = false;
    public string $activeTab = 'logs'; // 'logs' o 'errors'

    // Directorio de logs de ferrawin-sync
    protected string $logsDir = 'C:\\xampp\\htdocs\\ferrawin-sync\\logs';

    public function mount()
    {
        $this->refresh();
    }

    public function open()
    {
        $this->isOpen = true;
        $this->refresh();
    }

    public function close()
    {
        $this->isOpen = false;
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function refresh()
    {
        // Contar planillas y elementos en DB
        $this->totalPlanillas = Planilla::count();
        $this->totalElementos = Elemento::count();

        // Leer logs
        $this->readLogs();

        $this->lastUpdate = now()->format('H:i:s');
    }

    protected function readLogs()
    {
        if (!File::isDirectory($this->logsDir)) {
            $this->logs = ['No se encontró el directorio de logs'];
            $this->errors = [];
            $this->isRunning = false;
            return;
        }

        // Obtener todos los archivos de log ordenados por fecha (más reciente primero)
        $logFiles = collect(File::files($this->logsDir))
            ->filter(fn($f) => str_starts_with($f->getFilename(), 'sync-'))
            ->sortByDesc(fn($f) => $f->getMTime())
            ->values();

        if ($logFiles->isEmpty()) {
            $this->logs = ['No hay archivos de log'];
            $this->errors = [];
            return;
        }

        // Leer contenido de los últimos archivos (máximo 3 para rendimiento)
        $content = '';
        foreach ($logFiles->take(3) as $file) {
            $content .= File::get($file->getPathname()) . "\n";
        }

        $lines = explode("\n", $content);
        $lines = array_filter($lines, fn($l) => trim($l) !== '');
        $lines = array_values($lines);

        // Ordenar líneas por timestamp
        usort($lines, function($a, $b) {
            $timeA = '';
            $timeB = '';
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $a, $m)) {
                $timeA = $m[1];
            }
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $b, $m)) {
                $timeB = $m[1];
            }
            return $timeA <=> $timeB;
        });

        // Obtener últimas 50 líneas para mostrar
        $this->logs = array_slice($lines, -50);

        // Contar batches OK y Error
        $this->batchesOk = substr_count($content, 'Batch OK');
        $this->batchesError = substr_count($content, 'Error en batch');

        // Extraer errores con contexto
        $this->errors = [];
        foreach ($lines as $i => $line) {
            if (str_contains($line, 'ERROR')) {
                // Extraer timestamp
                $timestamp = '';
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $timeMatch)) {
                    $timestamp = $timeMatch[1];
                }

                // Clasificar tipo de error
                $tipo = 'Error';
                $descripcion = $line;

                if (str_contains($line, 'cURL error 28') || str_contains($line, 'timed out')) {
                    $tipo = 'Timeout';
                    $descripcion = 'El servidor tardó más de 2 minutos en responder';
                } elseif (str_contains($line, '403') || str_contains($line, 'Token')) {
                    $tipo = 'Autenticación';
                    $descripcion = 'Token inválido o expirado';
                } elseif (str_contains($line, '500')) {
                    $tipo = 'Error servidor';
                    // Extraer mensaje de error
                    if (preg_match('/"error":"([^"]+)"/', $line, $errorMatch)) {
                        $descripcion = $errorMatch[1];
                    } elseif (preg_match('/"message":\s*"([^"]+)"/', $line, $msgMatch)) {
                        $descripcion = $msgMatch[1];
                    }
                }

                // Buscar qué planilla se estaba procesando (línea anterior o siguiente)
                $planilla = '-';
                for ($j = max(0, $i - 3); $j <= min(count($lines) - 1, $i + 3); $j++) {
                    if (preg_match('/Preparando (\d{4}-\d+)/', $lines[$j], $planillaMatch)) {
                        $planilla = $planillaMatch[1];
                        break;
                    }
                }

                $this->errors[] = [
                    'timestamp' => $timestamp,
                    'tipo' => $tipo,
                    'descripcion' => $descripcion,
                    'planilla_cerca' => $planilla,
                    'raw' => substr($line, 0, 300),
                ];
            }
        }

        // Invertir para mostrar los más recientes primero
        $this->errors = array_reverse($this->errors);

        // Buscar progreso actual (última línea con [X/Y])
        $allLines = array_reverse($lines);
        foreach ($allLines as $line) {
            if (preg_match('/\[(\d+)\/(\d+)\]/', $line, $matches)) {
                $this->currentProgress = "{$matches[1]}/{$matches[2]}";

                // Extraer año de la planilla
                if (preg_match('/(\d{4})-\d+/', $line, $yearMatch)) {
                    $this->currentYear = $yearMatch[1];
                }
                break;
            }
        }

        // Verificar si el proceso está corriendo (última línea reciente)
        $lastLine = end($lines);
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $lastLine, $timeMatch)) {
            $lastTime = strtotime($timeMatch[1]);
            $this->isRunning = (time() - $lastTime) < 300; // Activo si última entrada < 5 min
        }
    }

    public function render()
    {
        return view('livewire.sync-monitor');
    }
}
