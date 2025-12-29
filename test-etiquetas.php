<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\EtiquetaEnsamblajeService;
use App\Models\Planilla;

$service = app(EtiquetaEnsamblajeService::class);
$planilla = Planilla::find(6389);

if ($planilla) {
    echo "Planilla: {$planilla->codigo}" . PHP_EOL;
    echo "Entidades: " . $planilla->entidades->count() . PHP_EOL;
    echo "Total unidades: " . $planilla->entidades->sum('cantidad') . PHP_EOL;

    $etiquetas = $service->generarParaPlanilla($planilla);
    echo "Etiquetas generadas: " . $etiquetas->count() . PHP_EOL;

    foreach ($etiquetas as $et) {
        echo "  - {$et->codigo} ({$et->marca} {$et->situacion})" . PHP_EOL;
    }
} else {
    echo "Planilla 6389 no encontrada" . PHP_EOL;
}
