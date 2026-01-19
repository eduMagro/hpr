<?php
/**
 * Test de carga de Laravel paso a paso
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de carga Laravel</h2>";
echo "<pre>";

$basePath = '/var/www/vhosts/hierrospacoreyes.es/app.hierrospacoreyes.es';

echo "1. Base path: $basePath\n";

echo "2. Cargando autoload...\n";
flush();

try {
    require $basePath . '/vendor/autoload.php';
    echo "   OK - Autoload cargado\n";
} catch (Exception $e) {
    die("   ERROR en autoload: " . $e->getMessage());
}

echo "3. Cargando bootstrap/app.php...\n";
flush();

try {
    $app = require_once $basePath . '/bootstrap/app.php';
    echo "   OK - App creada\n";
} catch (Exception $e) {
    die("   ERROR en bootstrap: " . $e->getMessage());
}

echo "4. Iniciando kernel...\n";
flush();

try {
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    echo "   OK - Kernel iniciado\n";
} catch (Exception $e) {
    die("   ERROR en kernel: " . $e->getMessage());
}

echo "5. Probando config...\n";
flush();

try {
    $host = config('database.connections.mysql.host');
    $db = config('database.connections.mysql.database');
    echo "   OK - Host: $host, DB: $db\n";
} catch (Exception $e) {
    die("   ERROR en config: " . $e->getMessage());
}

echo "\n¡Todo OK! Laravel cargado correctamente.\n";
echo "</pre>";
