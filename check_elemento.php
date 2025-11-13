<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Consultar el elemento 143006
$elemento = DB::table('elementos')->where('id', 143006)->first();

if ($elemento) {
    echo "=== ELEMENTO 143006 ===\n";
    echo "ID: {$elemento->id}\n";
    echo "Código: {$elemento->codigo}\n";
    echo "Figura: {$elemento->figura}\n";
    echo "Dimensiones: " . ($elemento->dimensiones ?: 'NULL/VACÍO') . "\n";
    echo "Longitud dimensiones: " . strlen($elemento->dimensiones ?? '') . "\n";
    echo "\n";
} else {
    echo "❌ No se encontró el elemento 143006\n";
}

// Buscar un elemento que SÍ tenga dimensiones para comparar
$elementoConDimensiones = DB::table('elementos')
    ->whereNotNull('dimensiones')
    ->where('dimensiones', '!=', '')
    ->first();

if ($elementoConDimensiones) {
    echo "=== EJEMPLO DE ELEMENTO CON DIMENSIONES ===\n";
    echo "ID: {$elementoConDimensiones->id}\n";
    echo "Código: {$elementoConDimensiones->codigo}\n";
    echo "Dimensiones: {$elementoConDimensiones->dimensiones}\n";
    echo "\n";
}
