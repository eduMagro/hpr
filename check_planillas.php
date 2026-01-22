<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sinObra = App\Models\Planilla::whereNull('obra_id')->orWhere('obra_id', 0)->count();
$sinCliente = App\Models\Planilla::whereNull('cliente_id')->orWhere('cliente_id', 0)->count();

echo "=== RESUMEN ===\n";
echo "Planillas sin obra: $sinObra\n";
echo "Planillas sin cliente: $sinCliente\n";

echo "\n=== DETALLE PLANILLAS SIN OBRA (max 30) ===\n";
App\Models\Planilla::whereNull('obra_id')->orWhere('obra_id', 0)
    ->select('id', 'codigo', 'obra_id', 'cliente_id')
    ->limit(30)
    ->get()
    ->each(function($p) {
        echo $p->id . ' | ' . $p->codigo . ' | obra_id:' . ($p->obra_id ?? 'NULL') . ' | cliente_id:' . ($p->cliente_id ?? 'NULL') . "\n";
    });

echo "\n=== OBRAS DISPONIBLES (para referencia) ===\n";
App\Models\Obra::select('id', 'cod_obra', 'obra', 'cliente_id')
    ->limit(10)
    ->get()
    ->each(function($o) {
        echo $o->id . ' | ' . $o->cod_obra . ' | ' . $o->obra . "\n";
    });
