<?php
/**
 * PASO 3: Ejecutar en LOCAL después del comando artisan
 * URL: http://localhost/manager/scripts/exportar_despues_sync.php
 *
 * Exporta solo los DATOS (sin estructura) para importar en producción
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$config = [
    'host' => config('database.connections.mysql.host'),
    'database' => config('database.connections.mysql.database'),
    'username' => config('database.connections.mysql.username'),
    'password' => config('database.connections.mysql.password'),
];

// Tablas a exportar (en orden para evitar problemas de FK)
$tablas = ['planillas', 'elementos', 'etiquetas', 'paquetes', 'orden_planillas'];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = "-- Datos actualizados después del sync\n";
    $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- IMPORTANTE: Ejecutar en producción después de truncar las tablas\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql .= "SET NAMES utf8mb4;\n\n";

    // Primero los TRUNCATE
    $sql .= "-- Limpiar tablas (orden inverso por FK)\n";
    $sql .= "TRUNCATE TABLE `orden_planillas`;\n";
    $sql .= "TRUNCATE TABLE `paquetes`;\n";
    $sql .= "TRUNCATE TABLE `etiquetas`;\n";
    $sql .= "TRUNCATE TABLE `elementos`;\n";
    $sql .= "TRUNCATE TABLE `planillas`;\n\n";

    foreach ($tablas as $tabla) {
        echo "Exportando tabla: $tabla<br>";
        flush();

        $stmt = $pdo->query("SELECT * FROM `$tabla`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';

            $sql .= "-- Tabla: $tabla (" . count($rows) . " registros)\n";

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
    $filename = 'datos_sync_' . date('Y-m-d_His') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    echo $sql;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
