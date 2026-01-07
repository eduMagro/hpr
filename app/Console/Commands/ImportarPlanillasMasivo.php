<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FerrawinSync\FerrawinBulkImportService;
use App\Models\Planilla;
// FerrawinSync classes are loaded dynamically via require_once
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportarPlanillasMasivo extends Command
{
    protected $signature = 'ferrawin:importar-masivo
                            {--autoload= : Ruta al autoloader de ferrawin-sync}
                            {--año= : Año específico a importar (ej: 2024)}
                            {--desde= : Fecha inicio (Y-m-d)}
                            {--hasta= : Fecha fin (Y-m-d)}
                            {--batch=50 : Planillas por lote}
                            {--dry-run : Solo mostrar estadísticas sin importar}
                            {--skip-existing : Saltar planillas que ya existen (más rápido)}
                            {--force : Forzar reimportación de planillas existentes}';

    protected $description = 'Importa masivamente planillas desde FerraWin a Manager';

    protected FerrawinBulkImportService $importService;
    protected int $totalImportadas = 0;
    protected int $totalOmitidas = 0;
    protected int $totalErrores = 0;
    protected array $errores = [];

    public function handle(): int
    {
        // Cargar autoloader de ferrawin-sync
        $autoloadPath = $this->option('autoload') ?: 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            $this->error("No se encuentra el autoloader de ferrawin-sync en: {$autoloadPath}");
            return 1;
        }
        require_once $autoloadPath;

        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║       IMPORTACIÓN MASIVA DE PLANILLAS FERRAWIN            ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->info('');

        // Inicializar conexión FerraWin
        try {
            \FerrawinSync\Config::load();
            \FerrawinSync\Database::getConnection();
            $this->info('✓ Conexión a FerraWin establecida');
        } catch (\Exception $e) {
            $this->error('✗ Error conectando a FerraWin: ' . $e->getMessage());
            return 1;
        }

        // Obtener estadísticas
        $this->info('');
        $this->info('Obteniendo estadísticas de FerraWin...');
        $stats = \FerrawinSync\FerrawinQuery::getEstadisticas();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total planillas en FerraWin', number_format($stats['total_planillas'])],
                ['Total elementos', number_format($stats['total_elementos'])],
                ['Primera planilla', $stats['fecha_primera'] ?? 'N/A'],
                ['Última planilla', $stats['fecha_ultima'] ?? 'N/A'],
            ]
        );

        // Mostrar planillas por año
        $this->info('');
        $this->info('Planillas por año:');
        $tableData = [];
        foreach ($stats['por_año'] as $row) {
            $tableData[] = [$row->año, number_format($row->planillas)];
        }
        $this->table(['Año', 'Planillas'], $tableData);

        // Planillas ya importadas
        $importadas = Planilla::count();
        $this->info('');
        $this->info("Planillas ya importadas en Manager: " . number_format($importadas));

        // Determinar rango de importación
        $opciones = $this->construirOpciones();

        if ($this->option('dry-run')) {
            return $this->ejecutarDryRun($opciones);
        }

        // Confirmar antes de importar
        if (!$this->confirmarImportacion($opciones)) {
            $this->info('Importación cancelada.');
            return 0;
        }

        // Ejecutar importación
        return $this->ejecutarImportacion($opciones);
    }

    protected function construirOpciones(): array
    {
        $opciones = [];

        if ($this->option('año')) {
            $opciones['año'] = (int) $this->option('año');
        } elseif ($this->option('desde')) {
            $opciones['fecha_desde'] = $this->option('desde');
            $opciones['fecha_hasta'] = $this->option('hasta') ?? date('Y-m-d');
        }

        return $opciones;
    }

    protected function ejecutarDryRun(array $opciones): int
    {
        $this->info('');
        $this->warn('═══ MODO DRY-RUN (sin cambios) ═══');
        $this->info('');

        // Obtener códigos según opciones
        $codigos = \FerrawinSync\FerrawinQuery::getCodigosPlanillas($opciones);
        $total = count($codigos);

        $this->info("Planillas encontradas: " . number_format($total));

        // Verificar cuántas ya existen
        $existentes = Planilla::whereIn('codigo', $codigos)->pluck('codigo')->toArray();
        $nuevas = count($codigos) - count($existentes);

        $this->info("Ya importadas: " . number_format(count($existentes)));
        $this->info("Por importar: " . number_format($nuevas));

        // Estimar tiempo
        $batchSize = (int) $this->option('batch');
        $tiempoEstimadoMin = ceil(($nuevas / $batchSize) * 2); // ~2 min por batch

        $this->info('');
        $this->info("Configuración de importación:");
        $this->info("  - Tamaño de lote: {$batchSize}");
        $this->info("  - Lotes estimados: " . ceil($nuevas / $batchSize));
        $this->info("  - Tiempo estimado: ~{$tiempoEstimadoMin} minutos");

        return 0;
    }

    protected function confirmarImportacion(array $opciones): bool
    {
        $rangoTexto = 'todas las planillas';
        if (isset($opciones['año'])) {
            $rangoTexto = "planillas del año {$opciones['año']}";
        } elseif (isset($opciones['fecha_desde'])) {
            $rangoTexto = "planillas desde {$opciones['fecha_desde']} hasta {$opciones['fecha_hasta']}";
        }

        $this->info('');
        return $this->confirm("¿Deseas importar {$rangoTexto}?", true);
    }

    protected function ejecutarImportacion(array $opciones): int
    {
        $this->importService = app(FerrawinBulkImportService::class);
        $batchSize = (int) $this->option('batch');
        $skipExisting = $this->option('skip-existing');

        $inicio = microtime(true);

        // Si no hay opciones específicas, importar año por año
        if (empty($opciones)) {
            return $this->importarPorAnios($batchSize, $skipExisting);
        }

        // Importar según opciones
        $codigos = \FerrawinSync\FerrawinQuery::getCodigosPlanillas($opciones);
        $this->procesarCodigos($codigos, $batchSize, $skipExisting);

        return $this->mostrarResumen($inicio);
    }

    protected function importarPorAnios(int $batchSize, bool $skipExisting): int
    {
        $stats = \FerrawinSync\FerrawinQuery::getEstadisticas();
        $inicio = microtime(true);

        // Ordenar años de más antiguo a más reciente
        $anios = collect($stats['por_año'])->pluck('año')->sort()->values()->toArray();

        $this->info('');
        $this->info('Importando por años: ' . implode(', ', $anios));
        $this->info('');

        foreach ($anios as $anio) {
            $this->info("═══ Año {$anio} ═══");

            $codigos = \FerrawinSync\FerrawinQuery::getCodigosPlanillas(['año' => $anio]);
            $this->procesarCodigos($codigos, $batchSize, $skipExisting, $anio);

            $this->info('');
        }

        return $this->mostrarResumen($inicio);
    }

    protected function procesarCodigos(array $codigos, int $batchSize, bool $skipExisting, ?int $anio = null): void
    {
        $total = count($codigos);

        if ($total === 0) {
            $this->warn('No se encontraron planillas para importar.');
            return;
        }

        // Filtrar existentes si se solicita
        if ($skipExisting) {
            $existentes = Planilla::whereIn('codigo', $codigos)->pluck('codigo')->toArray();
            $codigos = array_diff($codigos, $existentes);
            $this->totalOmitidas += count($existentes);

            if (count($existentes) > 0) {
                $this->line("  Omitiendo " . count($existentes) . " planillas ya importadas");
            }
        }

        $porImportar = count($codigos);
        if ($porImportar === 0) {
            $this->info("  Todas las planillas ya están importadas.");
            return;
        }

        $this->info("  Importando {$porImportar} planillas en lotes de {$batchSize}...");

        // Crear barra de progreso
        $bar = $this->output->createProgressBar($porImportar);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $bar->setMessage('Iniciando...');
        $bar->start();

        // Procesar en lotes
        $chunks = array_chunk($codigos, $batchSize);
        $loteActual = 0;

        foreach ($chunks as $chunk) {
            $loteActual++;
            $bar->setMessage("Lote {$loteActual}/" . count($chunks));

            try {
                $resultado = $this->importarLote($chunk);

                $this->totalImportadas += $resultado['planillas_creadas'];
                $this->totalOmitidas += $resultado['planillas_omitidas'];

                $bar->advance(count($chunk));

            } catch (\Exception $e) {
                $this->totalErrores++;
                $this->errores[] = [
                    'lote' => $loteActual,
                    'error' => $e->getMessage(),
                ];

                Log::channel('ferrawin_sync')->error("Error en lote {$loteActual}", [
                    'error' => $e->getMessage(),
                    'codigos' => $chunk,
                ]);

                $bar->advance(count($chunk));
            }

            // Liberar memoria
            gc_collect_cycles();
        }

        $bar->setMessage('Completado');
        $bar->finish();
        $this->line('');
    }

    protected function importarLote(array $codigos): array
    {
        $planillasData = [];

        foreach ($codigos as $codigo) {
            try {
                $datos = \FerrawinSync\FerrawinQuery::getDatosPlanilla($codigo);

                if (empty($datos)) {
                    continue;
                }

                $planillaData = \FerrawinSync\FerrawinQuery::formatearParaApiConEnsamblajes($datos, $codigo);

                if (!empty($planillaData)) {
                    $planillasData[] = $planillaData;
                }
            } catch (\Exception $e) {
                Log::channel('ferrawin_sync')->warning("Error obteniendo planilla {$codigo}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($planillasData)) {
            return [
                'planillas_creadas' => 0,
                'planillas_omitidas' => count($codigos),
            ];
        }

        return $this->importService->importar($planillasData);
    }

    protected function mostrarResumen(float $inicio): int
    {
        $duracion = round(microtime(true) - $inicio, 1);
        $minutos = floor($duracion / 60);
        $segundos = $duracion % 60;

        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║                    RESUMEN DE IMPORTACIÓN                 ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->info('');

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Planillas importadas', number_format($this->totalImportadas)],
                ['Planillas omitidas (ya existían)', number_format($this->totalOmitidas)],
                ['Errores', number_format($this->totalErrores)],
                ['Tiempo total', "{$minutos}m {$segundos}s"],
            ]
        );

        if (!empty($this->errores)) {
            $this->warn('');
            $this->warn('Errores encontrados:');
            foreach (array_slice($this->errores, 0, 5) as $error) {
                $this->error("  Lote {$error['lote']}: {$error['error']}");
            }
            if (count($this->errores) > 5) {
                $this->warn('  ... y ' . (count($this->errores) - 5) . ' errores más');
            }
        }

        // Total en base de datos ahora
        $totalEnBD = Planilla::count();
        $this->info('');
        $this->info("Total planillas en Manager: " . number_format($totalEnBD));

        return $this->totalErrores > 0 ? 1 : 0;
    }
}
