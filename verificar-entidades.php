<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Ver últimas planillas
echo "=== Últimas 5 planillas ===" . PHP_EOL;
$ultimas = App\Models\Planilla::orderByDesc('id')->take(5)->get(['id', 'codigo', 'created_at']);
foreach ($ultimas as $p) {
    $entCount = App\Models\PlanillaEntidad::where('planilla_id', $p->id)->count();
    echo "ID {$p->id}: {$p->codigo} ({$p->created_at}) - Entidades: {$entCount}" . PHP_EOL;
}

// Total de entidades
echo PHP_EOL . "Total entidades en BD: " . App\Models\PlanillaEntidad::count() . PHP_EOL;
