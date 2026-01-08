<?php
/**
 * Buscar donde están las instrucciones de dibujo @tBARRA, @tESTRIBO en FerraWin
 */

require 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use FerrawinSync\Database;
use FerrawinSync\Config;

Config::load();
$pdo = Database::getConnection();

echo "=== Buscando campos con instrucciones de dibujo @t ===\n\n";

// Planilla de ejemplo
$zconta = '2025';
$zcodigo = '008437';

// Buscar en todas las tablas relacionadas
$tablas = ['ORD_HEAD', 'ORD_DET', 'ORD_BAR', 'PROD_HEAD', 'PROD_DETO', 'PROD_DETI'];

foreach ($tablas as $tabla) {
    echo "=== {$tabla} ===\n";

    try {
        // Determinar la columna de código según la tabla
        $colCodigo = in_array($tabla, ['PROD_HEAD', 'PROD_DETO', 'PROD_DETI']) ? 'ZCODPLA' : 'ZCODIGO';

        $sql = "SELECT TOP 1 * FROM {$tabla} WHERE ZCONTA = ? AND {$colCodigo} = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$zconta, $zcodigo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            foreach ($row as $col => $val) {
                if (is_string($val) && (strpos($val, '@t') !== false || strpos($val, '@n') !== false || strpos($val, '@x') !== false)) {
                    echo "  {$col}:\n";
                    // Mostrar primeros 500 chars
                    $preview = substr($val, 0, 500);
                    echo "    " . str_replace("\n", "\n    ", $preview) . "\n";
                    if (strlen($val) > 500) echo "    ... (truncado, total " . strlen($val) . " chars)\n";
                    echo "\n";
                }
            }
        } else {
            echo "  (sin datos)\n";
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Buscar específicamente en ORD_DET todos los campos
echo "=== Todos los campos de ORD_DET (primera entidad) ===\n";
$sql = "SELECT TOP 1 * FROM ORD_DET WHERE ZCONTA = ? AND ZCODIGO = ? ORDER BY ZCODLIN";
$stmt = $pdo->prepare($sql);
$stmt->execute([$zconta, $zcodigo]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    foreach ($row as $col => $val) {
        $val = is_string($val) ? trim($val) : $val;
        if (!empty($val) || $val === 0 || $val === '0') {
            $preview = is_string($val) && strlen($val) > 100 ? substr($val, 0, 100) . '...' : $val;
            echo "  {$col}: {$preview}\n";
        }
    }
}

// Buscar en PROD_DETI que parece tener instrucciones
echo "\n=== Campos de PROD_DETI (detalle producción) ===\n";
$sql = "SELECT TOP 3 * FROM PROD_DETI WHERE ZCONTA = ? AND ZCODPLA = ? ORDER BY ZCODLIN";
$stmt = $pdo->prepare($sql);
$stmt->execute([$zconta, $zcodigo]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "\n-- Linea {$row['ZCODLIN']} --\n";
    foreach ($row as $col => $val) {
        if (is_string($val) && (strpos($val, '@') !== false)) {
            echo "  {$col}: " . substr($val, 0, 300) . (strlen($val) > 300 ? '...' : '') . "\n";
        }
    }
}

// Ver estructura de PROD_DETI
echo "\n=== Estructura de PROD_DETI ===\n";
$sql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'PROD_DETI'
        ORDER BY ORDINAL_POSITION";
$stmt = $pdo->query($sql);
while ($col = $stmt->fetch()) {
    $len = $col->CHARACTER_MAXIMUM_LENGTH ? "({$col->CHARACTER_MAXIMUM_LENGTH})" : '';
    echo "  {$col->COLUMN_NAME}: {$col->DATA_TYPE}{$len}\n";
}
