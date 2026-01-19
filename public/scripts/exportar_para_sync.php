<?php
/**
 * PASO 1: Ejecutar en PRODUCCIÓN desde el navegador
 * URL: https://tudominio.com/scripts/exportar_para_sync.php
 *
 * Descarga un archivo SQL con las tablas necesarias para el sync
 */

// Configuración - ajusta estos valores
$config = [
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
];

// Cargar Laravel para usar env()
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
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
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tablas as $tabla) {
        echo "Exportando tabla: $tabla<br>";
        flush();

        // Estructura de la tabla
        $stmt = $pdo->query("SHOW CREATE TABLE `$tabla`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql .= "-- Tabla: $tabla\n";
        $sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
        $sql .= $row['Create Table'] . ";\n\n";

        // Datos de la tabla
        $stmt = $pdo->query("SELECT * FROM `$tabla`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';

            // Insertar en lotes de 500
            $chunks = array_chunk($rows, 500);
            foreach ($chunks as $chunk) {
                $values = [];
                foreach ($chunk as $row) {
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
            $sql .= "\n";
        }

        echo "- $tabla: " . count($rows) . " registros<br>";
        flush();
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Descargar archivo
    $filename = 'backup_produccion_' . date('Y-m-d_His') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    echo $sql;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
