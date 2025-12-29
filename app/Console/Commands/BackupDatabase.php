<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    /**
     * El nombre y firma del comando.
     *
     * @var string
     */
    protected $signature = 'backup:database
                            {--hourly : Crear backup horario (retención 24 horas)}
                            {--daily : Crear backup diario (retención 7 días)}
                            {--keep-hours=24 : Horas de retención para backups horarios}
                            {--keep-days=7 : Días de retención para backups diarios}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Crea un backup de la base de datos MySQL';

    /**
     * Directorio base de backups
     */
    protected string $backupDir;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->backupDir = storage_path('backups/database');
    }

    /**
     * Ejecutar el comando.
     */
    public function handle(): int
    {
        $isHourly = $this->option('hourly');
        $isDaily = $this->option('daily');

        // Por defecto es horario
        if (!$isHourly && !$isDaily) {
            $isHourly = true;
        }

        $type = $isHourly ? 'hourly' : 'daily';
        $this->info("Iniciando backup de base de datos ({$type})...");

        try {
            // Crear directorio si no existe
            $this->ensureBackupDirectoryExists($type);

            // Crear el backup
            $filename = $this->createBackup($type);

            // Limpiar backups antiguos
            $this->cleanOldBackups($type);

            $this->info("✓ Backup completado: {$filename}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ Error en backup: " . $e->getMessage());

            // Log del error
            \Log::error('Backup de base de datos fallido', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Asegura que el directorio de backup existe
     */
    protected function ensureBackupDirectoryExists(string $type): void
    {
        $dir = "{$this->backupDir}/{$type}";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            $this->line("Directorio creado: {$dir}");
        }
    }

    /**
     * Crea el backup de la base de datos
     */
    protected function createBackup(string $type): string
    {
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Formato del nombre según tipo
        if ($type === 'hourly') {
            // Para horarios: YYYYMMDD_HH
            $timestamp = Carbon::now()->format('Ymd_H');
        } else {
            // Para diarios: YYYYMMDD
            $timestamp = Carbon::now()->format('Ymd');
        }

        // En Windows no usamos compresión gzip
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $extension = $isWindows ? '.sql' : '.sql.gz';

        $filename = "{$dbName}_{$type}_{$timestamp}{$extension}";
        $filepath = "{$this->backupDir}/{$type}/{$filename}";

        // Construir comando mysqldump
        if ($isWindows) {
            $command = $this->buildWindowsCommand($dbHost, $dbPort, $dbUser, $dbPass, $dbName, $filepath);
        } else {
            // Linux/Mac: con compresión gzip
            if (empty($dbPass)) {
                $command = sprintf(
                    'mysqldump -h %s -P %s -u %s %s 2>/dev/null | gzip > %s',
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbName),
                    escapeshellarg($filepath)
                );
            } else {
                $command = sprintf(
                    'mysqldump -h %s -P %s -u %s -p%s %s 2>/dev/null | gzip > %s',
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbPass),
                    escapeshellarg($dbName),
                    escapeshellarg($filepath)
                );
            }
        }

        $this->line("Ejecutando mysqldump...");
        $this->line("Filepath: {$filepath}");

        // Usar shell_exec para mejor compatibilidad
        $output = shell_exec($command . ' 2>&1');

        // Verificar que el archivo se creó (dar tiempo a que se escriba)
        usleep(500000); // 0.5 segundos

        if (!file_exists($filepath)) {
            $this->line("Output: " . ($output ?: 'sin output'));
            throw new \RuntimeException("El archivo de backup no se creó");
        }

        // Verificar tamaño mínimo (mayor a 100 bytes)
        $size = filesize($filepath);
        if ($size < 100) {
            throw new \RuntimeException("El backup parece vacío o corrupto (tamaño: {$size} bytes)");
        }

        $sizeFormatted = $this->formatBytes($size);
        $this->line("Tamaño del backup: {$sizeFormatted}");

        return $filename;
    }

    /**
     * Construye el comando para Windows
     */
    protected function buildWindowsCommand(
        string $host,
        string $port,
        string $user,
        string $pass,
        string $dbName,
        string $filepath
    ): string {
        // Buscar mysqldump en rutas comunes de XAMPP
        $mysqldumpPaths = [
            'C:/xampp/mysql/bin/mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'mysqldump',
        ];

        $mysqldumpPath = 'mysqldump';
        foreach ($mysqldumpPaths as $path) {
            if (file_exists($path)) {
                $mysqldumpPath = $path;
                break;
            }
        }

        // Convertir rutas de Windows a formato Unix para compatibilidad
        $filepath = str_replace('\\', '/', $filepath);

        // Construir el comando dependiendo si hay password o no
        if (empty($pass)) {
            return sprintf(
                '"%s" -h %s -P %s -u %s %s > "%s"',
                $mysqldumpPath,
                $host,
                $port,
                $user,
                $dbName,
                $filepath
            );
        }

        return sprintf(
            '"%s" -h %s -P %s -u %s -p%s %s > "%s"',
            $mysqldumpPath,
            $host,
            $port,
            $user,
            $pass,
            $dbName,
            $filepath
        );
    }

    /**
     * Limpia backups antiguos según retención configurada
     */
    protected function cleanOldBackups(string $type): void
    {
        $dir = "{$this->backupDir}/{$type}";

        if ($type === 'hourly') {
            $retentionHours = (int) $this->option('keep-hours');
            $cutoffTime = Carbon::now()->subHours($retentionHours);
        } else {
            $retentionDays = (int) $this->option('keep-days');
            $cutoffTime = Carbon::now()->subDays($retentionDays);
        }

        $deleted = 0;
        $files = glob("{$dir}/*.sql*");

        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(filemtime($file));

            if ($fileTime->lt($cutoffTime)) {
                unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->line("Eliminados {$deleted} backups antiguos");
        }
    }

    /**
     * Formatea bytes a formato legible
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
