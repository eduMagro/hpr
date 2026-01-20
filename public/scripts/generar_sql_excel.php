<?php
/**
 * Genera SQL de actualización desde el Excel Maestro
 * Ejecutar en local y copiar el SQL resultante a producción
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '2G');
ini_set('max_execution_time', 600);

require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private $startRow = 0;
    private $endRow = 0;

    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        if ($row >= $this->startRow && $row < $this->endRow) {
            return true;
        }
        return false;
    }
}

function excelToDate($excelDate)
{
    if (!$excelDate || !is_numeric($excelDate)) {
        return null;
    }
    $unixDate = ($excelDate - 25569) * 86400;
    return date('Y-m-d H:i:s', $unixDate);
}

function normalizarCodigo($codigo)
{
    $codigo = trim(str_replace(' ', '', $codigo));
    if (!preg_match('/^(\d{4})-(\d+)$/', $codigo, $matches)) {
        return null;
    }
    return $matches[1] . '-' . str_pad($matches[2], 6, '0', STR_PAD_LEFT);
}

$archivo = __DIR__ . '/../../excelMaestro.xlsx';

if (!file_exists($archivo)) {
    die("Archivo no encontrado: $archivo");
}

echo "<h2>Generando SQL desde Excel Maestro</h2><pre>";

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

echo "Total filas en Excel: $totalRows\n";

$planillas = [];
$chunkSize = 5000;
$startRow = 8;
$chunkFilter = new ChunkReadFilter();

for ($currentRow = $startRow; $currentRow <= $totalRows; $currentRow += $chunkSize) {
    $chunkFilter->setRows($currentRow, $chunkSize);

    $reader = new Xlsx();
    $reader->setReadDataOnly(true);
    $reader->setLoadSheetsOnly(['PLANILLAS']);
    $reader->setReadFilter($chunkFilter);

    $spreadsheet = $reader->load($archivo);
    $sheet = $spreadsheet->getActiveSheet();

    $endRow = min($currentRow + $chunkSize - 1, $totalRows);

    for ($row = $currentRow; $row <= $endRow; $row++) {
        $numPlanilla = trim($sheet->getCellByColumnAndRow(9, $row)->getValue() ?? '');
        $fechaAprobacion = $sheet->getCellByColumnAndRow(13, $row)->getValue();
        $fechaEntrega = $sheet->getCellByColumnAndRow(15, $row)->getValue();
        $fechaInicio = $sheet->getCellByColumnAndRow(17, $row)->getValue();
        $fechaFin = $sheet->getCellByColumnAndRow(18, $row)->getValue();

        if (empty($numPlanilla)) continue;

        $codigo = normalizarCodigo($numPlanilla);
        if (!$codigo) continue;

        if (!isset($planillas[$codigo])) {
            $planillas[$codigo] = [
                'fecha_aprobacion' => null,
                'fecha_entrega' => null,
                'fecha_inicio' => null,
                'fecha_fin' => null,
            ];
        }

        if ($fechaAprobacion && is_numeric($fechaAprobacion) && !$planillas[$codigo]['fecha_aprobacion']) {
            $planillas[$codigo]['fecha_aprobacion'] = excelToDate($fechaAprobacion);
        }
        if ($fechaEntrega && is_numeric($fechaEntrega) && !$planillas[$codigo]['fecha_entrega']) {
            $planillas[$codigo]['fecha_entrega'] = excelToDate($fechaEntrega);
        }
        if ($fechaInicio && is_numeric($fechaInicio) && !$planillas[$codigo]['fecha_inicio']) {
            $planillas[$codigo]['fecha_inicio'] = excelToDate($fechaInicio);
        }
        if ($fechaFin && is_numeric($fechaFin) && !$planillas[$codigo]['fecha_fin']) {
            $planillas[$codigo]['fecha_fin'] = excelToDate($fechaFin);
        }
    }

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet, $sheet);
    gc_collect_cycles();

    echo "Procesadas filas $currentRow - $endRow\n";
    flush();
}

echo "\nPlanillas únicas encontradas: " . count($planillas) . "\n";

// Generar SQL
$sql = "-- SQL generado desde Excel Maestro\n";
$sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Total planillas: " . count($planillas) . "\n\n";

$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

$sql .= "-- Actualizar fechas de planillas\n";
foreach ($planillas as $codigo => $datos) {
    $sets = [];

    if ($datos['fecha_aprobacion']) {
        $sets[] = "aprobada_at = '{$datos['fecha_aprobacion']}'";
        $sets[] = "aprobada = 1";
    }
    if ($datos['fecha_entrega']) {
        $sets[] = "fecha_estimada_entrega = '{$datos['fecha_entrega']}'";
    }
    if ($datos['fecha_inicio']) {
        $sets[] = "fecha_inicio = '{$datos['fecha_inicio']}'";
    }
    if ($datos['fecha_fin']) {
        $sets[] = "fecha_finalizacion = '{$datos['fecha_fin']}'";
    }

    if (!empty($sets)) {
        $sql .= "UPDATE planillas SET " . implode(', ', $sets) . " WHERE codigo = '$codigo';\n";
    }
}

$sql .= "\n-- Marcar como completadas y revisadas las que tienen fecha_inicio\n";
$sql .= "UPDATE planillas SET estado = 'completada', revisada = 1 WHERE fecha_inicio IS NOT NULL AND estado != 'completada';\n";

$sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

// Guardar archivo
$filename = 'update_planillas_' . date('Y-m-d_His') . '.sql';
$filepath = __DIR__ . '/' . $filename;
file_put_contents($filepath, $sql);

echo "\n<b>Archivo generado:</b> $filename\n";
echo "<b>Tamaño:</b> " . round(strlen($sql) / 1024, 2) . " KB\n";
echo "<b>Planillas con datos:</b> " . count($planillas) . "\n";
echo "\n<a href='/manager/public/scripts/$filename' download>Descargar archivo SQL</a>\n";
echo "</pre>";
