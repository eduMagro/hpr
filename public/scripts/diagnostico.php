<?php
/**
 * Script de diagnóstico - muestra información de rutas
 */

echo "<h2>Diagnóstico de rutas</h2>";
echo "<pre>";

echo "1. __DIR__: " . __DIR__ . "\n";
echo "2. DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "3. dirname(DOCUMENT_ROOT): " . dirname($_SERVER['DOCUMENT_ROOT']) . "\n";

$possiblePaths = [
    __DIR__ . '/../..',
    __DIR__ . '/..',
    dirname($_SERVER['DOCUMENT_ROOT']),
];

echo "\n4. Rutas posibles:\n";
foreach ($possiblePaths as $i => $path) {
    $real = @realpath($path) ?: 'NO EXISTE';
    $hasVendor = file_exists($path . '/vendor/autoload.php') ? 'SÍ' : 'NO';
    $hasBootstrap = file_exists($path . '/bootstrap/app.php') ? 'SÍ' : 'NO';
    echo "   [$i] $path\n";
    echo "       Realpath: $real\n";
    echo "       vendor/autoload.php: $hasVendor\n";
    echo "       bootstrap/app.php: $hasBootstrap\n\n";
}

echo "\n5. Contenido de __DIR__/../..:\n";
$dir = @realpath(__DIR__ . '/../..');
if ($dir && is_dir($dir)) {
    $files = @scandir($dir);
    if ($files) {
        foreach (array_slice($files, 0, 20) as $f) {
            echo "   - $f\n";
        }
    }
} else {
    echo "   No se puede leer\n";
}

echo "</pre>";
