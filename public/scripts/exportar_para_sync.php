<?php
/**
 * PASO 1: Ejecutar en PRODUCCIÓN desde el navegador
 * URL: https://tudominio.com/scripts/exportar_para_sync.php
 *
 * Descarga un archivo SQL con las tablas necesarias para el sync
 */

// Buscar la raíz del proyecto Laravel
$basePath = null;
$possiblePaths = [
    __DIR__ . '/../..',           // public/scripts -> raíz
    __DIR__ . '/..',              // Si scripts está en la raíz del public
    dirname($_SERVER['DOCUMENT_ROOT']), // Un nivel arriba del document root
];

foreach ($possiblePaths as $path) {
    if (file_exists($path . '/vendor/autoload.php') && file_exists($path . '/bootstrap/app.php')) {
        $basePath = realpath($path);
        break;
    }
}

if (!$basePath) {
    die('Error: No se pudo encontrar la raíz del proyecto Laravel. Rutas probadas: ' . implode(', ', $possiblePaths));
}

// Cargar Laravel
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
