<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Models\OrdenPlanilla;
use App\Models\Paquete;
use App\Services\PlanillaImport\PlanillaProcessor;
use App\Services\PlanillaImport\CodigoEtiqueta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Filtro para leer Excel en chunks y ahorrar memoria
 */
class ChunkReadFilter implements IReadFilter
{
    private $startRow = 1;
    private $endRow = 1;

    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize - 1;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        // Columnas necesarias: I(9), K(11), M(13), O(15), P(16), Q(17), R(18)
        // I=Planilla, K=Diámetro, M=Fecha Aprobación, O=Fecha Entrega, P=Zona, Q=Fecha Inicio, R=Fecha Fin
        if ($row >= $this->startRow && $row <= $this->endRow) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnAddress);
            return in_array($col, [9, 11, 13, 15, 16, 17, 18]);
        }
        return false;
    }
}

class AsignarMaquinasExcelCommand extends Command
{
    protected $signature = 'planillas:sincronizar-excel
                            {archivo? : Ruta al archivo Excel. Por defecto: excelMaestro.xlsx}
                            {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}
                            {--solo-maquinas : Solo asignar máquinas, sin actualizar fechas ni completar}
                            {--solo-pendientes : Solo procesar planillas pendientes (por defecto procesa todas)}
                            {--desde=8 : Fila inicial del Excel (por defecto 8)}
                            {--hasta= : Fila final del Excel (por defecto: todas)}';

    protected $description = 'Sincroniza planillas desde Excel: asigna máquinas, actualiza fechas y completa planillas fabricadas';

    // Mapeo de zonas del Excel a códigos de máquina en BD
    protected array $mapeoZonas = [
        // Máquinas principales
        'SL28'      => 'SL28',
        'MSR20'     => 'MSR20',
        'MS16'      => 'MS16',
        'F12'       => 'F12',
        'PS12'      => 'PS12',
        'CM'        => 'CM',
        'TWIN'      => 'TWIN',
        'PILOTERA'  => 'PILOTERA',
        'BAMTEC'    => 'BAMTEC',
        'C.CORTE'   => 'C.CORTE',
        // Variaciones y typos
        'msr20'     => 'MSR20',
        'MSR'       => 'MSR20',
        'MSR2'      => 'MSR20',
        'MSR0'      => 'MSR20',
        'MS1'       => 'MS16',
        'MS15'      => 'MS16',
        'M16'       => 'MS16',
        'MS166'     => 'MS16',
        'MS216'     => 'MS16',
        'STAFF 12'  => 'F12',
        'PR12'      => 'PS12',
        'PS121'     => 'PS12',
        'PS212'     => 'PS12',
        'FPS12'     => 'PS12',
        'F123'      => 'F12',
        'MF12'      => 'F12',
        'MANUAL'    => 'CM',
        'MANUEL'    => 'CM',
        'MANUIAL'   => 'CM',
        'BANTEC'    => 'BAMTEC',
        'TWINMASTER'=> 'TWIN',
        'SL28/'     => 'SL28',
        // Zonas combinadas - usar la primera máquina
        'MSR20-SL28'     => 'MSR20',
        'SL28-MSR20'     => 'SL28',
        'MS16-SL28'      => 'MS16',
        'SL28-MS16'      => 'SL28',
        'MSR20-MANUAL'   => 'MSR20',
        'MANUAL-MSR20'   => 'CM',
        'MSR20-MS16'     => 'MSR20',
        'MS16-MSR20'     => 'MS16',
        'MSR20-F12'      => 'MSR20',
        'F12-MSR20'      => 'F12',
        'MSR20-PS12'     => 'MSR20',
        'PS12-MSR20'     => 'PS12',
        'MSR20-PS12 '    => 'MSR20', // Con espacio al final
        'SL28-PS12'      => 'SL28',
        'PS12-SL28'      => 'PS12',
        'MS16-MANUAL'    => 'MS16',
        'MANUAL-MS16'    => 'CM',
        'MANUAL-SL28'    => 'CM',
        'SL28-MANUAL'    => 'SL28',
        'MANUAL-PS12'    => 'CM',
        'PS12-MS16'      => 'PS12',
        'MS16-PS12'      => 'MS16',
        'BAMTEC-MSR20'   => 'BAMTEC',
        'MSR20-BAMTEC'   => 'MSR20',
        'BAMTEC-SL28'    => 'BAMTEC',
        'MS16-PILOTERA'  => 'MS16',
        'PILOTERA-MANUAL'=> 'CM',
        // Combinaciones triples y otras
        'MSR20-SL28-PS12'    => 'MSR20',
        'SL28-MSR20-MS16'    => 'SL28',
        'MSR20-MS16-SL28'    => 'MSR20',
        'MS16-SL28-MSR20'    => 'MS16',
        'MSR20-SL28-MANUAL'  => 'MSR20',
        'MANUAL-SL28-ROBOTMASTER' => 'CM',
        'C.CORTE-MANUAL'     => 'CM',
    ];

    // Zonas a ignorar (notas, comentarios, etc.)
    protected array $zonasIgnorar = [
        'IDEA', 'IDEA5', 'IDA', 'ISAGA', 'FABRICA', 'FERBECOR', 'LOCO',
        'MALLAZOS', 'MALLAZO', 'MALLAZOS Y ALAMBRE', 'MALLAZOS ENTREGADOS',
        'MALLAZOS Y CELOSIAS', 'MALLAS Y CELOSIAS', 'ALAMBRE', 'ALMBRE',
        'CELOSIA', 'CELOSIAS', 'CELOSIAS DE 8',
        'NO HACER', 'NO DAR', 'NO FABRICAR SOLO FACTURARLO', 'NO ELABORAR UES',
        'NO HACER BARRAS - SÓLO CARCASAS', 'SIN ESTRIBOS',
        'PTE CONFIRMAR', 'PREGUNTAR A PAZO', 'EN OBRA', 'JUNTO A ESCALERAS',
        'SON SOLO DOS PIEZAS', 'APUNTAR COLADAS', 'REPARAR PILOTE',
        'PESAR ESTOS PAQUETES', 'FALTA POR DAR', 'YA ENVÍADA',
        'ATENCION MEDIDAS INTERIORES', 'MITAD PLANILLA',
        'SACAR PATES GRADUALMENTE', 'MANUEL BARBA',
        '2 MALLAS', '6 MALLAZOS', '20MALLAZOS', '37 MALLAS 15 X 15 X 6',
        '28 PAÑOS 20X20X5', '23 MALLAZOS DE 20*20*5', '160 PAñOS 20X20X5',
        '30 CELOSIAS DE 8', '6 CELOSIAS DE 8', '3 CELOSIAS DE 8',
        '3 ROLLOS DE ALAMBRE', '10 ROLLOS DE ALAMBRE',
        '4 ROLLOS / ANTONIO TORRES 681 00 10 06',
        'PREV SYNTAX LINE 28', '*', 'P', '25', '32', 'RM60',
        'MSNUSL', 'MSR_MANUAL', 'MA-SL28-MSR20', 'PS12_2', 'PS12_B',
        'OJO, HAY COSAS FUERA DE PLANILLAS PARA ELABORAR. PREGUNTAR AL MAQUINA',
        '22029-NOSA-RC-FND-L-S-5501', 'SOLO DAR POSICION 11A - 161 UDS',
        'PILOTERA', 'BAMTEC', 'C.CORTE', // Máquinas que no existen en BD
        'MANUAL', // Ya mapeado a CM pero aparece como texto
        'MANUAL PREGUNTAR SEBAS', 'JOSé YA TIENE ALGO CORTADO PREGUNTAR',
        'SOLO DAR POSICION 11A - 20 UDS', 'MSR20-PS12',
    ];

    protected array $maquinasCache = [];

    public function handle()
    {
        // Aumentar límite de memoria para archivos Excel grandes
        ini_set('memory_limit', '2G');

        $archivo = $this->argument('archivo') ?? 'excelMaestro.xlsx';
        $dryRun = $this->option('dry-run');
        $soloMaquinas = $this->option('solo-maquinas');
        $soloPendientes = $this->option('solo-pendientes');
        $filaDesde = (int) $this->option('desde');
        $filaHasta = $this->option('hasta') ? (int) $this->option('hasta') : null;

        if (!file_exists($archivo)) {
            $this->error("Archivo no encontrado: {$archivo}");
            return 1;
        }

        $this->info("Leyendo archivo: {$archivo}");
        $this->info($dryRun ? '🔍 Modo dry-run: no se realizarán cambios' : '⚡ Modo ejecución: se aplicarán cambios');
        $this->info($soloPendientes ? '📋 Solo planillas pendientes (sin fecha_finalizacion)' : '📋 Procesando TODAS las planillas (pendientes y completadas)');
        if ($filaHasta) {
            $this->info("📊 Rango de filas: {$filaDesde} - {$filaHasta}");
        }

        // Cargar máquinas en caché
        $this->cargarMaquinas();

        // Leer Excel (ahora incluye fechas)
        $datosExcel = $this->leerExcel($archivo, $filaDesde, $filaHasta);
        $this->info("Total combinaciones planilla+diámetro en Excel: " . count($datosExcel['asignaciones']));
        $this->info("Total planillas únicas con datos: " . count($datosExcel['planillas']));

        if ($dryRun) {
            $this->mostrarResumen($datosExcel['asignaciones']);
            $this->mostrarResumenFechas($datosExcel['planillas']);
            return 0;
        }

        // Paso 1: Actualizar fechas de planillas
        $this->info("\n📋 PASO 1: Actualizando fechas de planillas...");
        $this->actualizarFechasPlanillas($datosExcel['planillas']);

        // Paso 2: Asignar maquina_id a elementos
        $this->info("\n📋 PASO 2: Asignando maquina_id a elementos...");
        $planillasAfectadas = $this->asignarMaquinas($datosExcel['asignaciones'], $soloPendientes);

        // Paso 3: Completar planillas que tienen fecha de inicio en Excel
        $this->info("\n📋 PASO 3: Completando planillas fabricadas...");
        $this->completarPlanillasFabricadas($datosExcel['planillas']);

        if ($soloMaquinas) {
            $this->info("\n⏭️ Omitiendo subetiquetas y órdenes (--solo-maquinas)");
            $this->info("\n✅ Proceso completado");
            return 0;
        }

        // Paso 4: Aplicar política de subetiquetas (solo pendientes)
        $this->info("\n📋 PASO 4: Aplicando política de subetiquetas...");
        $this->aplicarPoliticaSubetiquetas($planillasAfectadas);

        // Paso 5: Crear órdenes de planilla en colas (solo pendientes)
        $this->info("\n📋 PASO 5: Creando órdenes de planilla en colas...");
        $this->crearOrdenesPlanilla($planillasAfectadas);

        $this->info("\n✅ Proceso completado");
        return 0;
    }

    protected function cargarMaquinas(): void
    {
        $maquinas = Maquina::all();
        foreach ($maquinas as $maq) {
            $this->maquinasCache[strtoupper($maq->codigo)] = $maq;
        }
        $this->info("Máquinas cargadas: " . count($this->maquinasCache));
    }

    protected function leerExcel(string $archivo, int $filaDesde = 8, ?int $filaHasta = null): array
    {
        $asignaciones = [];
        $planillas = []; // Datos de fechas por planilla
        $chunkSize = 5000;
        $chunkFilter = new ChunkReadFilter();

        // Primero obtener el total de filas (lectura rápida solo de estructura)
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['PLANILLAS']);
        $worksheetInfo = $reader->listWorksheetInfo($archivo);
        $totalRows = 0;
        foreach ($worksheetInfo as $info) {
            if ($info['worksheetName'] === 'PLANILLAS') {
                $totalRows = $info['totalRows'];
                break;
            }
        }

        // Aplicar límites de rango
        $startRow = max($filaDesde, 8); // Mínimo fila 8 (donde empiezan los datos)
        $endRowLimit = $filaHasta ? min($filaHasta, $totalRows) : $totalRows;

        $this->info("Total filas en Excel: {$totalRows}");
        $this->info("Procesando filas: {$startRow} - {$endRowLimit}");

        $filasAProcesar = $endRowLimit - $startRow + 1;
        $bar = $this->output->createProgressBar($filasAProcesar);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% Leyendo Excel...');
        $bar->start();

        // Leer en chunks
        for ($currentRow = $startRow; $currentRow <= $endRowLimit; $currentRow += $chunkSize) {
            $chunkFilter->setRows($currentRow, $chunkSize);

            $reader = new Xlsx();
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(['PLANILLAS']);
            $reader->setReadFilter($chunkFilter);

            $spreadsheet = $reader->load($archivo);
            $sheet = $spreadsheet->getActiveSheet();

            $endRow = min($currentRow + $chunkSize - 1, $endRowLimit);

            for ($row = $currentRow; $row <= $endRow; $row++) {
                $numPlanilla = trim($sheet->getCellByColumnAndRow(9, $row)->getValue() ?? '');
                $diametro = $sheet->getCellByColumnAndRow(11, $row)->getValue();
                $fechaAprobacion = $sheet->getCellByColumnAndRow(13, $row)->getValue();
                $fechaEntrega = $sheet->getCellByColumnAndRow(15, $row)->getValue();
                $zona = trim($sheet->getCellByColumnAndRow(16, $row)->getValue() ?? '');
                $fechaInicio = $sheet->getCellByColumnAndRow(17, $row)->getValue();
                $fechaFin = $sheet->getCellByColumnAndRow(18, $row)->getValue();

                $bar->advance();

                if (empty($numPlanilla)) {
                    continue;
                }

                // Normalizar código planilla
                $numPlanilla = str_replace(' ', '', $numPlanilla);
                if (!preg_match('/^(\d{4})-(\d+)$/', $numPlanilla, $matches)) {
                    continue;
                }
                $codigoBD = $matches[1] . '-' . str_pad($matches[2], 6, '0', STR_PAD_LEFT);

                // Guardar datos de fechas por planilla (tomamos la primera ocurrencia con datos)
                if (!isset($planillas[$codigoBD])) {
                    $planillas[$codigoBD] = [
                        'codigo' => $codigoBD,
                        'fecha_aprobacion' => null,
                        'fecha_entrega' => null,
                        'fecha_inicio' => null,
                        'fecha_fin' => null,
                    ];
                }

                // Actualizar fechas si tienen valor (pueden venir de diferentes filas)
                if ($fechaAprobacion && is_numeric($fechaAprobacion) && !$planillas[$codigoBD]['fecha_aprobacion']) {
                    $planillas[$codigoBD]['fecha_aprobacion'] = $this->excelToDate($fechaAprobacion);
                }
                if ($fechaEntrega && is_numeric($fechaEntrega) && !$planillas[$codigoBD]['fecha_entrega']) {
                    $planillas[$codigoBD]['fecha_entrega'] = $this->excelToDate($fechaEntrega);
                }
                if ($fechaInicio && is_numeric($fechaInicio) && !$planillas[$codigoBD]['fecha_inicio']) {
                    $planillas[$codigoBD]['fecha_inicio'] = $this->excelToDate($fechaInicio);
                }
                if ($fechaFin && is_numeric($fechaFin) && !$planillas[$codigoBD]['fecha_fin']) {
                    $planillas[$codigoBD]['fecha_fin'] = $this->excelToDate($fechaFin);
                }

                // Procesar asignación de máquina solo si hay zona válida
                if (empty($zona) || $zona === 'SIN ZONA') {
                    continue;
                }

                // Ignorar zonas que son notas/comentarios
                if (in_array($zona, $this->zonasIgnorar)) {
                    continue;
                }

                // Normalizar diámetro
                $diametro = (int) str_replace(['Ø', 'ø'], '', $diametro);
                if ($diametro <= 0) {
                    continue;
                }

                // Mapear zona a código de máquina
                $codigoMaquina = $this->mapeoZonas[$zona] ?? strtoupper($zona);

                // Solo incluir si la máquina existe en BD
                if (!isset($this->maquinasCache[$codigoMaquina])) {
                    continue;
                }

                $key = "{$codigoBD}|{$diametro}";
                if (!isset($asignaciones[$key])) {
                    $asignaciones[$key] = [
                        'codigo_planilla' => $codigoBD,
                        'diametro' => $diametro,
                        'zona_excel' => $zona,
                        'codigo_maquina' => $codigoMaquina,
                    ];
                }
            }

            // Liberar memoria
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);
            gc_collect_cycles();
        }

        $bar->finish();
        $this->newLine();

        return [
            'asignaciones' => $asignaciones,
            'planillas' => $planillas,
        ];
    }

    /**
     * Convierte número de fecha Excel a Carbon
     */
    protected function excelToDate($excelDate): ?Carbon
    {
        if (!$excelDate || !is_numeric($excelDate)) {
            return null;
        }
        try {
            $dateTime = ExcelDate::excelToDateTimeObject($excelDate);
            return Carbon::instance($dateTime);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function mostrarResumen(array $asignaciones): void
    {
        // Agrupar por máquina
        $porMaquina = [];
        foreach ($asignaciones as $asig) {
            $maq = $asig['codigo_maquina'];
            $porMaquina[$maq] = ($porMaquina[$maq] ?? 0) + 1;
        }

        $this->newLine();
        $this->info('📊 Resumen de asignaciones por máquina:');
        foreach ($porMaquina as $maq => $count) {
            $existe = isset($this->maquinasCache[$maq]) ? '✓' : '✗';
            $this->line("  {$existe} {$maq}: {$count} combinaciones planilla+diámetro");
        }

        // Contar elementos que se actualizarían
        $this->newLine();
        $this->info('Calculando elementos afectados...');

        $totalElementos = 0;
        $elementosPorMaquina = [];

        foreach ($asignaciones as $asig) {
            $planilla = Planilla::where('codigo', $asig['codigo_planilla'])->first();
            if (!$planilla) continue;

            $count = Elemento::where('planilla_id', $planilla->id)
                ->where('diametro', $asig['diametro'])
                ->count();

            $totalElementos += $count;
            $maq = $asig['codigo_maquina'];
            $elementosPorMaquina[$maq] = ($elementosPorMaquina[$maq] ?? 0) + $count;
        }

        $this->info("📦 Total elementos que se asignarían: {$totalElementos}");
        foreach ($elementosPorMaquina as $maq => $count) {
            $this->line("  {$maq}: {$count} elementos");
        }
    }

    protected function asignarMaquinas(array $asignaciones, bool $soloPendientes = false): array
    {
        $planillasAfectadas = [];
        $planillasPendientesAfectadas = [];
        $stats = [
            'elementos_actualizados' => 0,
            'planillas_no_encontradas' => 0,
            'planillas_omitidas_completadas' => 0,
            'maquinas_no_encontradas' => [],
        ];

        $bar = $this->output->createProgressBar(count($asignaciones));
        $bar->start();

        foreach ($asignaciones as $asig) {
            $bar->advance();

            // Buscar planilla
            $planilla = Planilla::where('codigo', $asig['codigo_planilla'])->first();
            if (!$planilla) {
                $stats['planillas_no_encontradas']++;
                continue;
            }

            // Omitir planillas completadas solo si se especifica --solo-pendientes
            if ($soloPendientes && $planilla->fecha_finalizacion) {
                $stats['planillas_omitidas_completadas']++;
                continue;
            }

            // Buscar máquina
            $maquina = $this->maquinasCache[$asig['codigo_maquina']] ?? null;
            if (!$maquina) {
                $stats['maquinas_no_encontradas'][$asig['codigo_maquina']] = true;
                continue;
            }

            // Actualizar elementos
            $updated = Elemento::where('planilla_id', $planilla->id)
                ->where('diametro', $asig['diametro'])
                ->update(['maquina_id' => $maquina->id]);

            $stats['elementos_actualizados'] += $updated;

            // Registrar planilla afectada
            $planillasAfectadas[$planilla->id] = $planilla;

            // Solo las pendientes necesitan subetiquetas y orden_planillas
            if (!$planilla->fecha_finalizacion) {
                $planillasPendientesAfectadas[$planilla->id] = $planilla;
            }
        }

        $bar->finish();
        $this->newLine();

        $this->info("  ✓ Elementos actualizados: {$stats['elementos_actualizados']}");
        $this->info("  ✓ Planillas afectadas (total): " . count($planillasAfectadas));
        $this->info("  ✓ Planillas pendientes (para subetiquetas/órdenes): " . count($planillasPendientesAfectadas));
        if ($stats['planillas_omitidas_completadas'] > 0) {
            $this->info("  ℹ Planillas omitidas (ya completadas): {$stats['planillas_omitidas_completadas']}");
        }
        if ($stats['planillas_no_encontradas'] > 0) {
            $this->warn("  ⚠ Planillas no encontradas en BD: {$stats['planillas_no_encontradas']}");
        }
        if (!empty($stats['maquinas_no_encontradas'])) {
            $this->warn("  ⚠ Máquinas no encontradas: " . implode(', ', array_keys($stats['maquinas_no_encontradas'])));
        }

        // Devolver solo las pendientes para el procesamiento posterior
        return $planillasPendientesAfectadas;
    }

    protected function aplicarPoliticaSubetiquetas(array $planillas): void
    {
        if (empty($planillas)) {
            $this->info("  No hay planillas para procesar");
            return;
        }

        // Usar el PlanillaProcessor del sistema
        $codigoService = app(CodigoEtiqueta::class);
        $processor = new PlanillaProcessor($codigoService);

        $bar = $this->output->createProgressBar(count($planillas));
        $bar->start();

        $procesadas = 0;
        $errores = 0;

        foreach ($planillas as $planilla) {
            try {
                DB::transaction(function () use ($processor, $planilla) {
                    $processor->aplicarPoliticaSubetiquetasPostAsignacion($planilla);
                });
                $procesadas++;
            } catch (\Exception $e) {
                $errores++;
                $this->newLine();
                $this->error("  Error en planilla {$planilla->codigo}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("  ✓ Planillas procesadas: {$procesadas}");
        if ($errores > 0) {
            $this->warn("  ⚠ Errores: {$errores}");
        }
    }

    protected function crearOrdenesPlanilla(array $planillas): void
    {
        if (empty($planillas)) {
            return;
        }

        $bar = $this->output->createProgressBar(count($planillas));
        $bar->start();

        $stats = [
            'creadas' => 0,
            'eliminadas' => 0,
            'sin_cambios' => 0,
        ];

        foreach ($planillas as $planilla) {
            DB::transaction(function () use ($planilla, &$stats) {
                // 1. Obtener máquinas actuales de los elementos de esta planilla
                $maquinasActuales = Elemento::where('planilla_id', $planilla->id)
                    ->whereNotNull('maquina_id')
                    ->distinct()
                    ->pluck('maquina_id')
                    ->toArray();

                // 2. Obtener órdenes existentes de esta planilla
                $ordenesExistentes = OrdenPlanilla::where('planilla_id', $planilla->id)
                    ->pluck('maquina_id')
                    ->toArray();

                // 3. Eliminar órdenes de máquinas que ya no tienen elementos de esta planilla
                $maquinasAEliminar = array_diff($ordenesExistentes, $maquinasActuales);
                if (!empty($maquinasAEliminar)) {
                    foreach ($maquinasAEliminar as $maquinaId) {
                        OrdenPlanilla::where('planilla_id', $planilla->id)
                            ->where('maquina_id', $maquinaId)
                            ->delete();
                        $stats['eliminadas']++;

                        // Reordenar posiciones de esa máquina
                        $this->reordenarPosicionesMaquina($maquinaId);
                    }
                }

                // 4. Crear órdenes para máquinas nuevas (posición temporal)
                $maquinasACrear = array_diff($maquinasActuales, $ordenesExistentes);
                foreach ($maquinasACrear as $maquinaId) {
                    $ultimaPosicion = OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->max('posicion') ?? -1;

                    OrdenPlanilla::create([
                        'planilla_id' => $planilla->id,
                        'maquina_id' => $maquinaId,
                        'posicion' => $ultimaPosicion + 1,
                    ]);
                    $stats['creadas']++;

                    // Reordenar por fecha de entrega después de crear
                    $this->reordenarPosicionesMaquina($maquinaId);
                }

                // 5. Contar las que no cambiaron
                $sinCambios = count(array_intersect($maquinasActuales, $ordenesExistentes));
                $stats['sin_cambios'] += $sinCambios;
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("  ✓ Órdenes creadas: {$stats['creadas']}");
        $this->info("  ✓ Órdenes eliminadas: {$stats['eliminadas']}");
        $this->info("  ℹ Órdenes sin cambios: {$stats['sin_cambios']}");
    }

    /**
     * Reordena las posiciones de las órdenes de una máquina por fecha de entrega
     * Ordena por fecha_estimada_entrega ASC (más urgentes primero)
     * Dentro del mismo día, ordena por hora (el campo es datetime)
     */
    protected function reordenarPosicionesMaquina(int $maquinaId): void
    {
        // Obtener órdenes ordenadas por fecha+hora de entrega de la planilla
        $ordenes = OrdenPlanilla::where('orden_planillas.maquina_id', $maquinaId)
            ->join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
            ->orderBy('planillas.fecha_estimada_entrega', 'asc') // Ordena por fecha y hora
            ->orderBy('planillas.id', 'asc') // Desempate final si coinciden fecha+hora
            ->select('orden_planillas.*')
            ->get();

        foreach ($ordenes as $index => $orden) {
            if ($orden->posicion !== $index) {
                $orden->posicion = $index;
                $orden->save();
            }
        }
    }

    /**
     * Muestra resumen de fechas para dry-run
     */
    protected function mostrarResumenFechas(array $planillas): void
    {
        $conAprobacion = 0;
        $conEntrega = 0;
        $conInicio = 0;
        $conFin = 0;

        foreach ($planillas as $p) {
            if ($p['fecha_aprobacion']) $conAprobacion++;
            if ($p['fecha_entrega']) $conEntrega++;
            if ($p['fecha_inicio']) $conInicio++;
            if ($p['fecha_fin']) $conFin++;
        }

        $this->newLine();
        $this->info('📅 Resumen de fechas en Excel:');
        $this->line("  Planillas con fecha aprobación: {$conAprobacion}");
        $this->line("  Planillas con fecha entrega: {$conEntrega}");
        $this->line("  Planillas con fecha inicio (a completar): {$conInicio}");
        $this->line("  Planillas con fecha fin: {$conFin}");
    }

    /**
     * Actualiza fechas de planillas desde el Excel
     */
    protected function actualizarFechasPlanillas(array $planillasExcel): void
    {
        $stats = [
            'actualizadas' => 0,
            'no_encontradas' => 0,
            'sin_cambios' => 0,
        ];

        $bar = $this->output->createProgressBar(count($planillasExcel));
        $bar->start();

        foreach ($planillasExcel as $datos) {
            $bar->advance();

            $planilla = Planilla::where('codigo', $datos['codigo'])->first();
            if (!$planilla) {
                $stats['no_encontradas']++;
                continue;
            }

            $cambios = false;

            // Actualizar aprobada_at siempre que venga en Excel (sobrescribe valor existente)
            if ($datos['fecha_aprobacion']) {
                $planilla->aprobada_at = $datos['fecha_aprobacion'];
                $planilla->aprobada = true;
                $cambios = true;
            }

            // Actualizar fecha_estimada_entrega si viene en Excel
            if ($datos['fecha_entrega']) {
                $planilla->fecha_estimada_entrega = $datos['fecha_entrega'];
                $cambios = true;
            }

            // Actualizar fecha_inicio si hay fecha_inicio en Excel
            if ($datos['fecha_inicio']) {
                $planilla->fecha_inicio = $datos['fecha_inicio'];
                $cambios = true;
            }

            // Actualizar fecha_finalizacion si hay fecha_fin en Excel
            if ($datos['fecha_fin']) {
                $planilla->fecha_finalizacion = $datos['fecha_fin'];
                $cambios = true;
            }

            if ($cambios) {
                $planilla->save();
                $stats['actualizadas']++;
            } else {
                $stats['sin_cambios']++;
            }
        }

        $bar->finish();
        $this->newLine();

        $this->info("  ✓ Planillas actualizadas: {$stats['actualizadas']}");
        $this->info("  ℹ Sin cambios: {$stats['sin_cambios']}");
        if ($stats['no_encontradas'] > 0) {
            $this->warn("  ⚠ No encontradas en BD: {$stats['no_encontradas']}");
        }
    }

    /**
     * Completa planillas que tienen fecha de inicio en Excel (ya fabricadas)
     */
    protected function completarPlanillasFabricadas(array $planillasExcel): void
    {
        // Filtrar solo las que tienen fecha_inicio (fueron fabricadas)
        $aCompletar = array_filter($planillasExcel, fn($p) => $p['fecha_inicio'] !== null);

        if (empty($aCompletar)) {
            $this->info("  No hay planillas con fecha de inicio para completar");
            return;
        }

        $this->info("  Planillas con fecha inicio (fabricadas): " . count($aCompletar));

        $bar = $this->output->createProgressBar(count($aCompletar));
        $bar->start();

        $stats = [
            'completadas' => 0,
            'ya_completadas' => 0,
            'no_encontradas' => 0,
            'errores' => 0,
        ];

        foreach ($aCompletar as $datos) {
            $bar->advance();

            $planilla = Planilla::where('codigo', $datos['codigo'])->first();
            if (!$planilla) {
                $stats['no_encontradas']++;
                continue;
            }

            // Si ya está completada, saltar
            if ($planilla->estado === 'completada') {
                $stats['ya_completadas']++;
                continue;
            }

            try {
                $this->completarPlanillaDirecta($planilla);
                $stats['completadas']++;
            } catch (\Exception $e) {
                $stats['errores']++;
            }
        }

        $bar->finish();
        $this->newLine();

        $this->info("  ✓ Planillas completadas: {$stats['completadas']}");
        $this->info("  ℹ Ya estaban completadas: {$stats['ya_completadas']}");
        if ($stats['no_encontradas'] > 0) {
            $this->warn("  ⚠ No encontradas: {$stats['no_encontradas']}");
        }
        if ($stats['errores'] > 0) {
            $this->error("  ✗ Errores: {$stats['errores']}");
        }
    }

    /**
     * Completa una planilla de forma directa (crea paquetes, marca etiquetas, etc.)
     */
    protected function completarPlanillaDirecta(Planilla $planilla): void
    {
        DB::transaction(function () use ($planilla) {
            // Marcar todas las etiquetas como completadas y crear paquetes
            $subetiquetas = Etiqueta::where('planilla_id', $planilla->id)
                ->whereNotNull('etiqueta_sub_id')
                ->where('estado', '!=', 'completada')
                ->distinct()
                ->pluck('etiqueta_sub_id');

            foreach ($subetiquetas as $subId) {
                $etiquetas = Etiqueta::where('etiqueta_sub_id', $subId)->get();

                if ($etiquetas->isEmpty()) {
                    continue;
                }

                // Verificar si ya existe un paquete para esta subetiqueta
                $etiquetaConPaquete = $etiquetas->first(fn($e) => $e->paquete_id !== null);
                $paquete = $etiquetaConPaquete?->paquete;

                if (!$paquete) {
                    // Solo crear paquete si no existe
                    $pesoTotal = $etiquetas->sum('peso');
                    $paquete = Paquete::create([
                        'codigo'      => Paquete::generarCodigo(),
                        'planilla_id' => $planilla->id,
                        'peso'        => $pesoTotal,
                        'estado'      => 'pendiente',
                    ]);
                }

                Etiqueta::where('etiqueta_sub_id', $subId)->update([
                    'estado'     => 'completada',
                    'paquete_id' => $paquete->id,
                ]);

                Elemento::where('etiqueta_sub_id', $subId)->update([
                    'estado' => 'completado',
                ]);
            }

            // Marcar elementos restantes como completados
            Elemento::where('planilla_id', $planilla->id)
                ->where('estado', '!=', 'completado')
                ->update(['estado' => 'completado']);

            // Marcar planilla como completada y revisada
            $planilla->estado = 'completada';
            $planilla->revisada = true;
            $planilla->save();

            // Eliminar de las colas de producción y reindexar posiciones
            $maquinasAfectadas = OrdenPlanilla::where('planilla_id', $planilla->id)
                ->pluck('maquina_id')
                ->toArray();

            OrdenPlanilla::where('planilla_id', $planilla->id)->delete();

            // Reindexar posiciones de cada máquina afectada
            foreach ($maquinasAfectadas as $maquinaId) {
                $this->reordenarPosicionesMaquina($maquinaId);
            }
        });
    }
}
