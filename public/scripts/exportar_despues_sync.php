<?php
/**
 * Exportar datos después del sync para importar en producción
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '1G');
ini_set('max_execution_time', 600);

// Conexión directa sin Laravel (evita problemas con sessions)
$config = [
    'host' => '127.0.0.1',
    'database' => 'sync_temp',
    'username' => 'root',
    'password' => '',
];

// Tablas a exportar
$tablas = ['planillas', 'elementos', 'etiquetas', 'paquetes', 'orden_planillas'];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h2>Exportando datos de sync_temp</h2><pre>";

    $sql = "-- Datos actualizados después del sync\n";
    $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Base de datos origen: sync_temp\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql .= "SET NAMES utf8mb4;\n\n";

    // TRUNCATE
    $sql .= "-- Limpiar tablas\n";
    $sql .= "TRUNCATE TABLE `orden_planillas`;\n";
    $sql .= "TRUNCATE TABLE `paquetes`;\n";
    $sql .= "TRUNCATE TABLE `etiquetas`;\n";
    $sql .= "TRUNCATE TABLE `elementos`;\n";
    $sql .= "TRUNCATE TABLE `planillas`;\n\n";

    foreach ($tablas as $tabla) {
        echo "Exportando: $tabla... ";
        flush();

        // Contar registros
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `$tabla`");
        $totalRows = $countStmt->fetchColumn();
        echo "$totalRows registros\n";
        flush();

        if ($totalRows > 0) {
            // Obtener columnas
            $stmt = $pdo->query("SELECT * FROM `$tabla` LIMIT 1");
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
            $columns = array_keys($sample);
            $columnList = '`' . implode('`, `', $columns) . '`';

            $sql .= "-- Tabla: $tabla ($totalRows registros)\n";

            // Exportar en lotes
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

    // Guardar archivo
    $filename = 'datos_sync_' . date('Y-m-d_His') . '.sql';
    $filepath = __DIR__ . '/' . $filename;
    file_put_contents($filepath, $sql);

    echo "\n<b>Archivo generado:</b> $filename\n";
    echo "<b>Tamaño:</b> " . round(strlen($sql) / 1024 / 1024, 2) . " MB\n";
    echo "\n<a href='/manager/public/scripts/$filename' download>Descargar archivo SQL</a>\n";
    echo "</pre>";

} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<pre>" . $e->getMessage() . "\n\n" . $e->getTraceAsString() . "</pre>";
}
