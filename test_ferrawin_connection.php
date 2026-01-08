<?php
/**
 * Script para probar la conexión a FerraWin y verificar datos
 */

require 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';

use FerrawinSync\Database;
use FerrawinSync\Config;
use FerrawinSync\FerrawinQuery;

Config::load();

echo "=== Prueba de conexión a FerraWin ===\n\n";

try {
    $pdo = Database::getConnection();
    echo "Conexion OK\n\n";

    // Probar una consulta simple
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ORD_BAR");
    $total = $stmt->fetch()->total;
    echo "Total elementos en ORD_BAR: {$total}\n\n";

    // Probar con una planilla específica del 2019
    $codigoTest = '2019-001000';
    echo "Probando planilla: {$codigoTest}\n";

    $partes = explode('-', $codigoTest, 2);
    [$zconta, $zcodigo] = $partes;

    // Consulta directa
    $sql = "SELECT COUNT(*) as total FROM ORD_BAR WHERE ZCONTA = ? AND ZCODIGO = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$zconta, $zcodigo]);
    $count = $stmt->fetch()->total;
    echo "Elementos en ORD_BAR para {$codigoTest}: {$count}\n\n";

    // Verificar si existe en ORD_HEAD
    $sql = "SELECT TOP 1 * FROM ORD_HEAD WHERE ZCONTA = ? AND ZCODIGO = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$zconta, $zcodigo]);
    $head = $stmt->fetch();
    echo "ORD_HEAD encontrado: " . ($head ? 'SI' : 'NO') . "\n";

    if ($head) {
        echo "  - ZFECHA: " . ($head->ZFECHA ?? 'NULL') . "\n";
        echo "  - ZNOMBRE: " . ($head->ZNOMBRE ?? 'NULL') . "\n";
    }

    // Probar con getDatosPlanilla
    echo "\n=== Usando getDatosPlanilla ===\n";
    $datos = FerrawinQuery::getDatosPlanilla($codigoTest);
    echo "Elementos devueltos: " . count($datos) . "\n";

    // Probar con una planilla que sabemos que existe en 2018
    $codigo2018 = '2018-000001';
    echo "\n=== Probando planilla 2018 ===\n";
    $partes = explode('-', $codigo2018, 2);
    [$zconta2, $zcodigo2] = $partes;

    $sql = "SELECT COUNT(*) as total FROM ORD_BAR WHERE ZCONTA = ? AND ZCODIGO = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$zconta2, $zcodigo2]);
    $count2 = $stmt->fetch()->total;
    echo "Elementos en ORD_BAR para {$codigo2018}: {$count2}\n";

    // Obtener lista de planillas 2019 y verificar si tienen elementos
    echo "\n=== Verificando planillas 2019 ===\n";
    $sql = "
        SELECT TOP 10
            oh.ZCONTA + '-' + oh.ZCODIGO as codigo,
            (SELECT COUNT(*) FROM ORD_BAR ob WHERE ob.ZCONTA = oh.ZCONTA AND ob.ZCODIGO = oh.ZCODIGO) as elementos
        FROM ORD_HEAD oh
        WHERE YEAR(oh.ZFECHA) = 2019
        ORDER BY oh.ZFECHA DESC
    ";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch()) {
        echo "{$row->codigo}: {$row->elementos} elementos\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
