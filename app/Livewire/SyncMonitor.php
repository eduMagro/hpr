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
    public bool $isPausing = false; // Indica que se solicitó pausa y está esperando
    public string $activeTab = 'logs'; // 'logs' o 'errors'

    // Directorio de logs de ferrawin-sync (se configura en mount)
    protected string $logsDir;
    protected string $syncDir;

    /**
     * Detecta si estamos en Windows o Linux.
     */
    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Obtiene las rutas según el sistema operativo.
     */
    protected function configurarRutas(): void
    {
        if ($this->isWindows()) {
            $this->syncDir = 'C:\\xampp\\htdocs\\ferrawin-sync';
            $this->logsDir = $this->syncDir . '\\logs';
        } else {
            // Linux - producción
            $this->syncDir = '/var/www/ferrawin-sync';
            $this->logsDir = $this->syncDir . '/logs';
        }
    }

    // Para continuar sincronización
    public ?string $ultimaPlanilla = null;
    public ?string $ultimoAño = null;

    // Para el modal de confirmación de año
    public bool $showYearConfirm = false;
    public ?string $selectedYear = null;
    public int $yearPlanillasCount = 0;
    public ?string $yearLastPlanilla = null;

    // Entorno actual (detectado automáticamente)
    public string $currentTarget = 'local';

    // Destino de sincronización seleccionado por el usuario
    public string $syncTarget = 'local';

    /**
     * Se ejecuta en CADA request de Livewire (no solo mount).
     */
    public function boot()
    {
        $this->configurarRutas();
    }

    public function mount()
    {
        $this->currentTarget = $this->detectarTarget();
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

        // Leer logs (actualiza $this->isRunning)
        $this->readLogs();

        // Si estaba pausando y ya no está corriendo, resetear el flag
        if ($this->isPausing && !$this->isRunning) {
            $this->isPausing = false;
        }

        // Detectar última planilla para poder continuar
        $this->detectarUltimaPlanilla();

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

        // Verificar si el proceso está corriendo
        // Método 1: Verificar si existe el archivo PID (más confiable)
        $sep = DIRECTORY_SEPARATOR;
        $pidFile = $this->syncDir . $sep . 'sync.pid';
        if (file_exists($pidFile)) {
            $this->isRunning = true;
        } else {
            // Método 2: Fallback - verificar si última entrada es reciente Y no es "completada"
            $lastLine = end($lines);
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $lastLine, $timeMatch)) {
                $lastTime = strtotime($timeMatch[1]);
                $isRecent = (time() - $lastTime) < 120; // 2 minutos
                $isCompleted = str_contains($lastLine, 'completada') || str_contains($lastLine, 'completado');
                $this->isRunning = $isRecent && !$isCompleted;
            } else {
                $this->isRunning = false;
            }
        }
    }

    /**
     * Detecta la última planilla que se estaba procesando.
     * Busca en los logs la última línea con "Preparando XXXX-YYYYYY"
     */
    protected function detectarUltimaPlanilla(): void
    {
        if (!File::isDirectory($this->logsDir)) {
            return;
        }

        // Obtener el archivo de log más reciente
        $logFiles = collect(File::files($this->logsDir))
            ->filter(fn($f) => str_starts_with($f->getFilename(), 'sync-'))
            ->sortByDesc(fn($f) => $f->getMTime())
            ->values();

        if ($logFiles->isEmpty()) {
            return;
        }

        // Leer el archivo más reciente
        $content = File::get($logFiles->first()->getPathname());
        $lines = array_reverse(explode("\n", $content));

        // Buscar la última planilla que se estaba preparando
        foreach ($lines as $line) {
            if (preg_match('/Preparando (\d{4})-(\d+)/', $line, $matches)) {
                $this->ultimoAño = $matches[1];
                $this->ultimaPlanilla = "{$matches[1]}-{$matches[2]}";
                return;
            }
        }
    }

    /**
     * Abre el modal de confirmación mostrando info del año seleccionado.
     */
    public function seleccionarAño(string $año)
    {
        if ($this->isRunning) {
            session()->flash('error', 'Ya hay una sincronización en ejecución');
            return;
        }

        $this->selectedYear = $año;

        // Obtener estadísticas del año desde la BD
        $stats = Planilla::selectRaw("
                COUNT(*) as total,
                MAX(codigo) as ultima
            ")
            ->whereRaw("codigo LIKE ?", ["{$año}-%"])
            ->first();

        $this->yearPlanillasCount = $stats->total ?? 0;
        $this->yearLastPlanilla = $stats->ultima;

        $this->showYearConfirm = true;
    }

    /**
     * Cierra el modal de confirmación de año.
     */
    public function cerrarYearConfirm()
    {
        $this->showYearConfirm = false;
        $this->selectedYear = null;
    }

    /**
     * Inicia sincronización desde cero para el año seleccionado.
     */
    public function confirmarSyncCompleta()
    {
        if (!$this->selectedYear) return;

        $this->ejecutarSync($this->selectedYear, null);
        $this->showYearConfirm = false;
    }

    /**
     * Continúa sincronización desde la última planilla del año.
     */
    public function confirmarSyncContinuar()
    {
        if (!$this->selectedYear || !$this->yearLastPlanilla) return;

        $this->ejecutarSync($this->selectedYear, $this->yearLastPlanilla);
        $this->showYearConfirm = false;
    }

    /**
     * Ejecuta la sincronización con los parámetros especificados.
     */
    protected function ejecutarSync(string $año, ?string $desdeCodigo = null)
    {
        // Limpiar archivo de pausa si existe
        $sep = $this->isWindows() ? '\\' : '/';
        $pauseFile = $this->syncDir . $sep . 'sync.pause';
        if (file_exists($pauseFile)) {
            unlink($pauseFile);
        }

        // Usar el target seleccionado por el usuario
        $target = $this->syncTarget;

        // Construir argumentos
        $args = "--año={$año} --target={$target}";
        $targetLabel = $target === 'production' ? 'PRODUCCIÓN' : 'LOCAL';
        $mensaje = "Sincronización {$año} iniciada → {$targetLabel}";

        if ($desdeCodigo) {
            $args .= " --desde-codigo={$desdeCodigo}";
            $mensaje = "Sincronización {$año} continuando desde {$desdeCodigo} [{$targetLabel}]";
        }

        // Log para debug
        \Log::info("SyncMonitor: Iniciando sync", ['args' => $args, 'target' => $target, 'os' => PHP_OS]);

        if ($this->isWindows()) {
            $this->ejecutarSyncWindows($args);
        } else {
            $this->ejecutarSyncLinux($args);
        }

        session()->flash('message', $mensaje);
        $this->isRunning = true;
    }

    /**
     * Ejecuta la sincronización en Windows usando VBScript.
     */
    protected function ejecutarSyncWindows(string $args): void
    {
        $phpPath = 'C:\\xampp\\php\\php.exe';
        $scriptPath = $this->syncDir . '\\sync-optimizado.php';

        // Crear archivo batch para ejecutar
        $batchFile = $this->syncDir . '\\run-sync.bat';
        $batchContent = "@echo off\r\n";
        $batchContent .= "cd /d \"{$this->syncDir}\"\r\n";
        $batchContent .= "\"{$phpPath}\" \"{$scriptPath}\" {$args}\r\n";

        if (file_put_contents($batchFile, $batchContent) === false) {
            \Log::error("SyncMonitor: No se pudo crear el archivo batch");
            session()->flash('error', 'Error: No se pudo crear el archivo de ejecución');
            return;
        }

        // Ejecutar en background SIN ventana visible usando VBScript
        $vbsFile = $this->syncDir . '\\run-sync.vbs';
        $vbsContent = 'Set WshShell = CreateObject("WScript.Shell")' . "\r\n";
        $vbsContent .= 'WshShell.Run """' . $phpPath . '"" ""' . $scriptPath . '"" ' . $args . '", 0, False' . "\r\n";
        file_put_contents($vbsFile, $vbsContent);

        $cmd = "wscript //nologo \"{$vbsFile}\"";
        exec($cmd, $output, $returnCode);

        \Log::info("SyncMonitor: Windows - Proceso iniciado", ['returnCode' => $returnCode]);
    }

    /**
     * Ejecuta la sincronización en Linux usando nohup.
     */
    protected function ejecutarSyncLinux(string $args): void
    {
        $scriptPath = $this->syncDir . '/sync-optimizado.php';
        $logFile = $this->logsDir . '/sync-' . date('Y-m-d') . '.log';

        // Asegurar que el directorio de logs existe
        if (!is_dir($this->logsDir)) {
            mkdir($this->logsDir, 0755, true);
        }

        // Ejecutar en background con nohup
        // El output va al archivo de log del script
        $cmd = sprintf(
            'cd %s && nohup php %s %s >> %s 2>&1 &',
            escapeshellarg($this->syncDir),
            escapeshellarg($scriptPath),
            $args,
            escapeshellarg($logFile)
        );

        exec($cmd, $output, $returnCode);

        \Log::info("SyncMonitor: Linux - Proceso iniciado", [
            'cmd' => $cmd,
            'returnCode' => $returnCode
        ]);
    }

    /**
     * Detecta si estamos en local o producción.
     */
    protected function detectarTarget(): string
    {
        // Método 1: Usar APP_ENV de Laravel
        $env = config('app.env', 'local');

        if ($env === 'production') {
            return 'production';
        }

        // Método 2: Verificar por dominio de producción específico
        $host = request()->getHost();
        if (str_contains($host, 'hierrospacoreyes.es')) {
            return 'production';
        }

        // Por defecto: local
        return 'local';
    }

    /**
     * @deprecated Usar seleccionarAño() en su lugar
     */
    public function iniciarSyncCompleta(string $año = '2025')
    {
        $this->seleccionarAño($año);
    }

    /**
     * Pausa la sincronización en ejecución.
     * Crea un archivo de señal que el script sync.php detectará.
     */
    public function pausarSync()
    {
        if (!$this->isRunning) {
            session()->flash('error', 'No hay sincronización en ejecución');
            return;
        }

        $sep = $this->isWindows() ? '\\' : '/';
        $pauseFile = $this->syncDir . $sep . 'sync.pause';

        // Crear archivo de pausa
        file_put_contents($pauseFile, date('Y-m-d H:i:s'));

        // Activar indicador de pausando
        $this->isPausing = true;

        session()->flash('message', 'Señal de pausa enviada. Esperando que la sincronización se detenga...');

        // Forzar refresh para actualizar estado
        $this->refresh();
    }

    /**
     * Continúa la sincronización desde la última planilla procesada.
     */
    public function continuarSync()
    {
        if ($this->isRunning) {
            session()->flash('error', 'Ya hay una sincronización en ejecución');
            return;
        }

        if (!$this->ultimaPlanilla || !$this->ultimoAño) {
            session()->flash('error', 'No se pudo detectar la última planilla. Inicia una nueva sincronización.');
            return;
        }

        // Usar el método centralizado de ejecución
        $this->ejecutarSync($this->ultimoAño, $this->ultimaPlanilla);
    }

    public function render()
    {
        return view('livewire.sync-monitor');
    }
}
