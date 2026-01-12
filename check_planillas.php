<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$codigos = file('codigos_planillas_normalizados.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$total = count($codigos);
$existentes = App\Models\Planilla::whereIn('codigo', $codigos)->count();
$yaAprobadas = App\Models\Planilla::whereIn('codigo', $codigos)->where('aprobada', true)->count();
$pendientes = $existentes - $yaAprobadas;

echo "Codigos en Excel: $total\n";
echo "Existen en DB: $existentes\n";
echo "Ya aprobadas: $yaAprobadas\n";
echo "Pendientes de aprobar: $pendientes\n";
