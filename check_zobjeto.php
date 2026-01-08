<?php
/**
 * Investigar el campo ZOBJETO y buscar instrucciones de dibujo
 */

require 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use FerrawinSync\Database;
use FerrawinSync\Config;

Config::load();
$pdo = Database::getConnection();

echo "=== Investigando ZOBJETO en ORD_DET ===\n\n";

$zconta = '2025';
$zcodigo = '008437';

// Ver ZOBJETO completo de una entidad
$sql = "SELECT ZCODLIN, ZMARCA, ZSITUACION, ZOBJETO, ZMEMBERS FROM ORD_DET WHERE ZCONTA = ? AND ZCODIGO = ? ORDER BY ZCODLIN";
$stmt = $pdo->prepare($sql);
$stmt->execute([$zconta, $zcodigo]);

$count = 0;
while (($row = $stmt->fetch()) && $count < 2) {
    $count++;
    echo "=== Entidad Linea {$row->ZCODLIN} ===\n";
    echo "MARCA: " . trim($row->ZMARCA) . "\n";
    echo "SITUACION: " . trim($row->ZSITUACION) . "\n";
    echo "ZMEMBERS: {$row->ZMEMBERS}\n";
    echo "\nZOBJETO (hex dump primeros 200 bytes):\n";

    $obj = $row->ZOBJETO;
    for ($i = 0; $i < min(200, strlen($obj)); $i++) {
        $char = $obj[$i];
        $ord = ord($char);
        if ($ord >= 32 && $ord < 127) {
            echo $char;
        } else {
            echo "[{$ord}]";
        }
    }
    echo "\n\n";
}

// Buscar en otras tablas que podrían tener instrucciones de dibujo
echo "=== Buscando tablas con posibles instrucciones de dibujo ===\n";
$sql = "SELECT name FROM sys.tables WHERE name LIKE '%BAR%' OR name LIKE '%ETI%' OR name LIKE '%DRA%' OR name LIKE '%FIG%' ORDER BY name";
$stmt = $pdo->query($sql);
while ($row = $stmt->fetch()) {
    echo "  - {$row->name}\n";
}

// Ver si hay campo con @ en ORD_BAR
echo "\n=== ORD_BAR - todos los campos de un elemento ===\n";
$sql = "SELECT * FROM ORD_BAR WHERE ZCONTA = ? AND ZCODIGO = ? ORDER BY ZCODLIN, ZELEMENTO";
$stmt = $pdo->prepare($sql);
$stmt->execute([$zconta, $zcodigo]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    foreach ($row as $col => $val) {
        $val = is_string($val) ? trim($val) : $val;
        if ($val !== '' && $val !== null) {
            if (is_string($val) && strlen($val) > 100) {
                echo "  {$col}: " . substr($val, 0, 100) . "... (total " . strlen($val) . " chars)\n";
            } else {
                echo "  {$col}: {$val}\n";
            }
        }
    }
}

// Ver estructura de ORD_BAR
echo "\n=== Estructura de ORD_BAR ===\n";
$sql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'ORD_BAR'
        ORDER BY ORDINAL_POSITION";
$stmt = $pdo->query($sql);
while ($col = $stmt->fetch()) {
    $len = $col->CHARACTER_MAXIMUM_LENGTH ? "({$col->CHARACTER_MAXIMUM_LENGTH})" : '';
    echo "  {$col->COLUMN_NAME}: {$col->DATA_TYPE}{$len}\n";
}

// Buscar ZFIGURA que mencioné antes
echo "\n=== ZFIGURA en ORD_BAR (dimensiones del estribo) ===\n";
$sql = "SELECT TOP 5 ZCODLIN, ZELEMENTO, ZDIAMETRO, ZLONGTESTD, ZNUMBEND, ZFIGURA
        FROM ORD_BAR
        WHERE ZCONTA = ? AND ZCODIGO = ? AND ZNUMBEND > 0
        ORDER BY ZCODLIN, ZELEMENTO";
$stmt = $pdo->prepare($sql);
$stmt->execute([$zconta, $zcodigo]);

while ($row = $stmt->fetch()) {
    echo "  Linea {$row->ZCODLIN}, Elem {$row->ZELEMENTO}: Ø{$row->ZDIAMETRO}, L={$row->ZLONGTESTD}, dobleces={$row->ZNUMBEND}\n";
    echo "    ZFIGURA: " . trim($row->ZFIGURA) . "\n";
}
