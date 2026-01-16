<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
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
    protected ?string $logsDir = null;
    protected ?string $syncDir = null;

    /**
     * Detecta si estamos en Windows o Linux.
     */
    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Verifica si estamos en un entorno donde el sync puede ejecutarse.
     * El sync solo puede ejecutarse en local (Windows) donde ferrawin-sync está instalado.
     */
    protected function isLocalSyncEnvironment(): bool
    {
        // Solo Windows (local) tiene ferrawin-sync instalado
        return $this->isWindows();
    }

    /**
     * Obtiene las rutas según el sistema operativo.
     * En producción (Linux), no configuramos rutas porque ferrawin-sync no existe ahí.
     */
    protected function configurarRutas(): void
    {
        if ($this->isWindows()) {
            $this->syncDir = 'C:\\xampp\\htdocs\\ferrawin-sync';
            $this->logsDir = $this->syncDir . '\\logs';
        } else {
            // Linux/Producción - ferrawin-sync no existe aquí
            // Dejamos las rutas como null para evitar errores de open_basedir
            $this->syncDir = null;
            $this->logsDir = null;
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

    // Target de la sincronización en ejecución (detectado del log)
    public ?string $runningTarget = null;

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
        // Contar planillas y elementos en DB (cacheado 30 segundos)
        $this->totalPlanillas = Cache::remember('sync_monitor_planillas_count', 30, fn() => Planilla::count());
        $this->totalElementos = Cache::remember('sync_monitor_elementos_count', 30, fn() => Elemento::count());

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
        // En producción, no hay logs locales que leer
        if (!$this->logsDir || !File::isDirectory($this->logsDir)) {
            $this->logs = $this->isLocalSyncEnvironment()
                ? ['No se encontró el directorio de logs']
                : ['La sincronización se ejecuta desde el entorno local'];
            $this->errors = [];
            $this->isRunning = false;
            return;
        }

        // Obtener el archivo de log más reciente (solo 1, no 3)
        $logFiles = collect(File::files($this->logsDir))
            ->filter(fn($f) => str_starts_with($f->getFilename(), 'sync-'))
            ->sortByDesc(fn($f) => $f->getMTime())
            ->values();

        if ($logFiles->isEmpty()) {
            $this->logs = ['No hay archivos de log'];
            $this->errors = [];
            return;
        }

        // Leer solo las últimas 200 líneas del archivo más reciente (eficiente)
        $logFile = $logFiles->first()->getPathname();
        $lines = $this->readLastLines($logFile, 200);

        if (empty($lines)) {
            $this->logs = ['Archivo de log vacío'];
            $this->errors = [];
            return;
        }

        // Las líneas ya vienen en orden cronológico (no necesitan usort)
        // Obtener últimas 50 líneas para mostrar
        $this->logs = array_slice($lines, -50);

        // Contar batches OK y Error (solo en las líneas leídas)
        $content = implode("\n", $lines);
        $this->batchesOk = substr_count($content, 'Batch OK');
        $this->batchesError = substr_count($content, 'Error en batch');

        // Extraer errores (solo de las líneas leídas, máximo 20 errores)
        $this->errors = [];
        $errorCount = 0;
        $maxErrors = 20;

        foreach ($lines as $i => $line) {
            if ($errorCount >= $maxErrors) break;

            if (str_contains($line, 'ERROR')) {
                $errorCount++;

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
                    if (preg_match('/"error":"([^"]+)"/', $line, $errorMatch)) {
                        $descripcion = $errorMatch[1];
                    } elseif (preg_match('/"message":\s*"([^"]+)"/', $line, $msgMatch)) {
                        $descripcion = $msgMatch[1];
                    }
                }

                // Buscar qué planilla se estaba procesando (contexto cercano)
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

        // Buscar progreso actual (desde el final)
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (preg_match('/\[(\d+)\/(\d+)\]/', $lines[$i], $matches)) {
                $this->currentProgress = "{$matches[1]}/{$matches[2]}";
                if (preg_match('/(\d{4})-\d+/', $lines[$i], $yearMatch)) {
                    $this->currentYear = $yearMatch[1];
                }
                break;
            }
        }

        // Verificar si el proceso está corriendo (archivo PID)
        $pidFile = $this->syncDir . DIRECTORY_SEPARATOR . 'sync.pid';
        if (file_exists($pidFile)) {
            $this->isRunning = true;
        } else {
            // Fallback: verificar si última entrada es reciente
            $lastLine = end($lines);
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $lastLine, $timeMatch)) {
                $lastTime = strtotime($timeMatch[1]);
                $isRecent = (time() - $lastTime) < 120;
                $isCompleted = str_contains($lastLine, 'completada') || str_contains($lastLine, 'completado');
                $this->isRunning = $isRecent && !$isCompleted;
            } else {
                $this->isRunning = false;
            }
        }

        // Detectar el target de la sincronización en ejecución desde los logs
        // Busca línea como: "Target: local (http://...)" o "Target: production (http://...)"
        $this->runningTarget = null;
        foreach ($lines as $line) {
            if (preg_match('/Target:\s*(local|production)\s*\(/i', $line, $targetMatch)) {
                $this->runningTarget = strtolower($targetMatch[1]);
            }
        }
    }

    /**
     * Lee las últimas N líneas de un archivo de forma eficiente.
     * No carga todo el archivo en memoria.
     */
    protected function readLastLines(string $filepath, int $numLines): array
    {
        if (!file_exists($filepath)) {
            return [];
        }

        $file = new \SplFileObject($filepath, 'r');
        $file->seek(PHP_INT_MAX); // Ir al final
        $totalLines = $file->key();

        if ($totalLines === 0) {
            return [];
        }

        $startLine = max(0, $totalLines - $numLines);
        $lines = [];

        $file->seek($startLine);
        while (!$file->eof()) {
            $line = $file->fgets();
            if (trim($line) !== '') {
                $lines[] = trim($line);
            }
        }

        return $lines;
    }

    /**
     * Detecta la última planilla que se estaba procesando.
     * Busca en los logs la última línea con "Preparando XXXX-YYYYYY"
     */
    protected function detectarUltimaPlanilla(): void
    {
        // En producción no tenemos acceso a los logs
        if (!$this->logsDir || !File::isDirectory($this->logsDir)) {
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

        // Leer solo las últimas 100 líneas (eficiente)
        $lines = $this->readLastLines($logFiles->first()->getPathname(), 100);

        // Buscar la última planilla que se estaba preparando (desde el final)
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (preg_match('/Preparando (\d{4})-(\d+)/', $lines[$i], $matches)) {
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

        if ($año === 'todos' || $año === 'nuevas') {
            // Estadísticas de TODA la base de datos
            $stats = Planilla::selectRaw("
                    COUNT(*) as total,
                    MAX(codigo) as ultima
                ")
                ->first();
        } else {
            // Obtener estadísticas del año desde la BD
            $stats = Planilla::selectRaw("
                    COUNT(*) as total,
                    MAX(codigo) as ultima
                ")
                ->whereRaw("codigo LIKE ?", ["{$año}-%"])
                ->first();
        }

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
        // Verificar que estamos en un entorno donde se puede ejecutar sync
        if (!$this->syncDir || !$this->isLocalSyncEnvironment()) {
            session()->flash('error', 'La sincronización solo puede ejecutarse desde el entorno local');
            return;
        }

        // Limpiar archivo de pausa si existe
        $sep = $this->isWindows() ? '\\' : '/';
        $pauseFile = $this->syncDir . $sep . 'sync.pause';
        if (file_exists($pauseFile)) {
            unlink($pauseFile);
        }

        // Usar el target seleccionado por el usuario
        $target = $this->syncTarget;
        $targetLabel = $target === 'production' ? 'PRODUCCIÓN' : 'LOCAL';

        // Construir argumentos
        if ($año === 'todos') {
            $args = "--todos --target={$target}";
            $mensaje = "Sincronización COMPLETA iniciada → {$targetLabel}";
        } elseif ($año === 'nuevas') {
            $args = "--nuevas --target={$target}";
            $mensaje = "Sincronización de NUEVAS planillas iniciada → {$targetLabel}";
        } else {
            $args = "--anio={$año} --target={$target}";
            $mensaje = "Sincronización {$año} iniciada → {$targetLabel}";

            if ($desdeCodigo) {
                $args .= " --desde-codigo={$desdeCodigo}";
                $mensaje = "Sincronización {$año} continuando desde {$desdeCodigo} [{$targetLabel}]";
            }
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

        // Verificar que estamos en un entorno donde se puede ejecutar sync
        if (!$this->syncDir || !$this->isLocalSyncEnvironment()) {
            session()->flash('error', 'La sincronización solo puede controlarse desde el entorno local');
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
     * Abre el modal de confirmación para elegir destino.
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

        // Abrir modal de confirmación con el año detectado
        $this->seleccionarAño($this->ultimoAño);
    }

    /**
     * Elimina todos los archivos de log.
     */
    public function limpiarLogs()
    {
        if (!$this->logsDir || !File::isDirectory($this->logsDir)) {
            session()->flash('error', 'No se puede acceder al directorio de logs');
            return;
        }

        if ($this->isRunning) {
            session()->flash('error', 'No se pueden eliminar logs mientras hay una sincronización en ejecución');
            return;
        }

        $logFiles = File::files($this->logsDir);
        $eliminados = 0;

        foreach ($logFiles as $file) {
            if (str_starts_with($file->getFilename(), 'sync-')) {
                File::delete($file->getPathname());
                $eliminados++;
            }
        }

        // Limpiar estado
        $this->logs = ['Logs eliminados'];
        $this->errors = [];
        $this->batchesOk = 0;
        $this->batchesError = 0;
        $this->currentProgress = '0/0';

        session()->flash('message', "Se eliminaron {$eliminados} archivos de log");
    }

    public function render()
    {
        return view('livewire.sync-monitor');
    }
}
