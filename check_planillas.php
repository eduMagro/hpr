<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$planillas = App\Models\Planilla::whereIn('codigo', ['2025-008634', '2025-008600', '2025-008586', '2025-008442', '2025-008437'])
    ->with('cliente', 'obra')
    ->withCount(['entidades', 'elementos'])
    ->get();

foreach ($planillas as $p) {
    echo $p->codigo . ' | ' . ($p->cliente->empresa ?? 'N/A') . ' | ' . ($p->obra->obra ?? 'N/A') . PHP_EOL;
    echo '  Entidades: ' . $p->entidades_count . ', Elementos: ' . $p->elementos_count . PHP_EOL;
}
