<?php
/**
 * Verificar estructura de ZCONTA y ZCODIGO
 */

require 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';

use FerrawinSync\Database;
use FerrawinSync\Config;

Config::load();
$pdo = Database::getConnection();

echo "=== Verificando estructura ZCONTA/ZCODIGO ===\n\n";

// Ver valores únicos de ZCONTA en ORD_HEAD
echo "ZCONTA valores únicos en ORD_HEAD:\n";
$sql = "SELECT DISTINCT ZCONTA, COUNT(*) as total FROM ORD_HEAD GROUP BY ZCONTA ORDER BY ZCONTA";
$stmt = $pdo->query($sql);
while ($row = $stmt->fetch()) {
    echo "  '{$row->ZCONTA}' -> {$row->total} registros\n";
}

echo "\nZCONTA valores únicos en ORD_BAR:\n";
$sql = "SELECT DISTINCT ZCONTA, COUNT(*) as total FROM ORD_BAR GROUP BY ZCONTA ORDER BY ZCONTA";
$stmt = $pdo->query($sql);
while ($row = $stmt->fetch()) {
    echo "  '{$row->ZCONTA}' -> {$row->total} registros\n";
}

// Ver ejemplo de planilla 2019 en detalle
echo "\n=== Ejemplo planilla 2019 en ORD_HEAD ===\n";
$sql = "SELECT TOP 5 ZCONTA, ZCODIGO, LEN(ZCONTA) as len_zconta, LEN(ZCODIGO) as len_zcodigo
        FROM ORD_HEAD WHERE YEAR(ZFECHA) = 2019 ORDER BY ZFECHA DESC";
$stmt = $pdo->query($sql);
while ($row = $stmt->fetch()) {
    echo "  ZCONTA='{$row->ZCONTA}' (len={$row->len_zconta}), ZCODIGO='{$row->ZCODIGO}' (len={$row->len_zcodigo})\n";
}

echo "\n=== Ejemplo barras 2019 en ORD_BAR ===\n";
$sql = "SELECT TOP 5 ZCONTA, ZCODIGO, LEN(ZCONTA) as len_zconta, LEN(ZCODIGO) as len_zcodigo
        FROM ORD_BAR WHERE ZCONTA LIKE '2019%' ORDER BY ZCONTA, ZCODIGO";
$stmt = $pdo->query($sql);
$found = false;
while ($row = $stmt->fetch()) {
    $found = true;
    echo "  ZCONTA='{$row->ZCONTA}' (len={$row->len_zconta}), ZCODIGO='{$row->ZCODIGO}' (len={$row->len_zcodigo})\n";
}
if (!$found) {
    echo "  NO HAY REGISTROS CON ZCONTA LIKE '2019%'\n";
}

// Ver si hay algún patrón diferente
echo "\n=== Buscando barras del 2019 por fecha ===\n";
$sql = "
    SELECT TOP 10 ob.ZCONTA, ob.ZCODIGO, oh.ZFECHA
    FROM ORD_BAR ob
    JOIN ORD_HEAD oh ON ob.ZCONTA = oh.ZCONTA AND ob.ZCODIGO = oh.ZCODIGO
    WHERE YEAR(oh.ZFECHA) = 2019
";
$stmt = $pdo->query($sql);
$found = false;
while ($row = $stmt->fetch()) {
    $found = true;
    echo "  ZCONTA='{$row->ZCONTA}', ZCODIGO='{$row->ZCODIGO}', FECHA={$row->ZFECHA}\n";
}
if (!$found) {
    echo "  NO HAY BARRAS PARA PLANILLAS DE 2019 (join)\n";
}

// Probar la concatenación original
echo "\n=== Verificando cómo se construye el código ===\n";
$sql = "SELECT TOP 5 ZCONTA + '-' + ZCODIGO as codigo FROM ORD_HEAD WHERE YEAR(ZFECHA) = 2019 ORDER BY ZFECHA DESC";
$stmt = $pdo->query($sql);
while ($row = $stmt->fetch()) {
    echo "  Código: '{$row->codigo}'\n";
}
