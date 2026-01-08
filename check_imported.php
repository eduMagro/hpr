<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Planillas importadas por año ===\n";

$results = DB::select("
    SELECT
        SUBSTRING(codigo, 1, 4) as año,
        COUNT(*) as total
    FROM planillas
    WHERE deleted_at IS NULL
    GROUP BY SUBSTRING(codigo, 1, 4)
    ORDER BY año
");

$total = 0;
foreach ($results as $row) {
    echo "{$row->año}: {$row->total}\n";
    $total += $row->total;
}

echo "\nTotal: {$total}\n";

// Ver algunos ejemplos de cada año
echo "\n=== Ejemplos de planillas importadas ===\n";
$ejemplos = DB::select("
    SELECT codigo, nombre, peso_total
    FROM planillas
    WHERE deleted_at IS NULL
    ORDER BY codigo DESC
    LIMIT 10
");

foreach ($ejemplos as $row) {
    echo "{$row->codigo}: {$row->nombre} ({$row->peso_total} kg)\n";
}
