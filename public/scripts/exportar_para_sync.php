<?php
/**
 * PASO 1: Ejecutar en PRODUCCIÓN desde el navegador
 * Descarga un archivo SQL con las tablas necesarias para el sync
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// Cargar Laravel
$basePath = dirname(dirname(__DIR__)); // public/scripts -> public -> raíz
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$config = [
    'host' => config('database.connections.mysql.host'),
    'database' => config('database.connections.mysql.database'),
    'username' => config('database.connections.mysql.username'),
    'password' => config('database.connections.mysql.password'),
];

// Tablas a exportar
$tablas = ['maquinas', 'planillas', 'elementos', 'etiquetas', 'paquetes', 'orden_planillas'];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Preparar archivo SQL
    $sql = "-- Exportación para sincronización de planillas\n";
    $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Base de datos: {$config['database']}\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql .= "SET NAMES utf8mb4;\n\n";

    foreach ($tablas as $tabla) {
        // Estructura de la tabla
        $stmt = $pdo->query("SHOW CREATE TABLE `$tabla`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql .= "-- Tabla: $tabla\n";
        $sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
        $sql .= $row['Create Table'] . ";\n\n";

        // Contar registros
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `$tabla`");
        $totalRows = $countStmt->fetchColumn();

        if ($totalRows > 0) {
            // Obtener columnas
            $stmt = $pdo->query("SELECT * FROM `$tabla` LIMIT 1");
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
            $columns = array_keys($sample);
            $columnList = '`' . implode('`, `', $columns) . '`';

            // Exportar en lotes de 1000
            $batchSize = 1000;
            $offset = 0;

            while ($offset < $totalRows) {
                $stmt = $pdo->query("SELECT * FROM `$tabla` LIMIT $batchSize OFFSET $offset");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($rows) > 0) {
                    $values = [];
                    foreach ($rows as $row) {
                        $rowValues = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $rowValues[] = 'NULL';
                            } else {
                                $rowValues[] = $pdo->quote($value);
                            }
                        }
                        $values[] = '(' . implode(', ', $rowValues) . ')';
                    }
                    $sql .= "INSERT INTO `$tabla` ($columnList) VALUES\n" . implode(",\n", $values) . ";\n";
                }

                $offset += $batchSize;
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Descargar archivo
    $filename = 'backup_produccion_' . date('Y-m-d_His') . '.sql';

    // Limpiar cualquier output previo
    if (ob_get_level()) ob_end_clean();

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    echo $sql;
    exit;

} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<pre>" . $e->getMessage() . "\n\n" . $e->getTraceAsString() . "</pre>";
}
