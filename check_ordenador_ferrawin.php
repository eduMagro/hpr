<?php
/**
 * Verificar si CONTROL guarda historial de planillas por usuario
 */

require 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';

use FerrawinSync\Database;
use FerrawinSync\Config;

Config::load();

try {
    $pdo = Database::getConnection();

    // Ver TODOS los registros de CONTROL para entender su función
    echo "=== TODOS los registros en CONTROL ===\n";
    $sql = "SELECT * FROM CONTROL ORDER BY ZFECHA DESC, ZUNICO DESC";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID:{$row['ZUNICO']} | Usuario: " . trim($row['ZUSER']) .
             " | Codigo: " . trim($row['ZCODIGO']) .
             " | Fecha: {$row['ZFECHA']}\n";
    }

    // Ver si hay alguna planilla 2024002497 en ORD_HEAD
    echo "\n=== Buscando planilla 2024002497 ===\n";
    $sql = "SELECT * FROM ORD_HEAD WHERE ZCONTA + ZCODIGO = '2024002497' OR ZCODIGO = '002497'";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "Encontrada: {$row['ZCONTA']}-{$row['ZCODIGO']} - {$row['ZNOMBRE']}\n";
    } else {
        echo "No encontrada con ese formato\n";
        $sql = "SELECT TOP 1 * FROM ORD_HEAD WHERE ZCODIGO LIKE '%2497%'";
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo "Posible match: {$row['ZCONTA']}-{$row['ZCODIGO']} - {$row['ZNOMBRE']}\n";
        }
    }

    // Ver si hay tablas de producción que guarden usuario
    echo "\n=== PROD_OPTH (opciones producción) - ejemplo ===\n";
    $sql = "SELECT TOP 3 * FROM PROD_OPTH ORDER BY ZUNICO DESC";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        foreach ($row as $key => $value) {
            if ($value !== null && trim($value) !== '') {
                echo "  {$key}: " . trim($value) . "\n";
            }
        }
        echo "  ---\n";
    }

    // Buscar si PROD_HEAD tiene relación con planillas
    echo "\n=== PROD_HEAD (cabecera producción) - estructura ===\n";
    $sql = "SELECT COLUMN_NAME, DATA_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = 'PROD_HEAD'
            ORDER BY ORDINAL_POSITION";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch()) {
        echo "  - {$row->COLUMN_NAME}: {$row->DATA_TYPE}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
