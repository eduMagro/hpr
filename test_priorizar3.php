<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ProduccionController;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Models\Maquina;

// Buscar una obra con fecha de entrega MUY temprana que esté en posición > 2
echo "=== BUSCANDO OBRA CON FECHA TEMPRANA ===" . PHP_EOL;

$planilla = Planilla::whereNotNull('fecha_estimada_entrega')
    ->whereNotNull('obra_id')
    ->where('estado', '!=', 'completada')
    ->orderBy('fecha_estimada_entrega')
    ->first();

if (!$planilla) {
    echo "No hay planillas con fecha" . PHP_EOL;
    exit;
}

$obraId = $planilla->obra_id;
echo "Planilla con fecha más temprana: {$planilla->codigo} (entrega: {$planilla->fecha_estimada_entrega})" . PHP_EOL;
echo "Obra: {$obraId} - {$planilla->obra->obra}" . PHP_EOL . PHP_EOL;

// Ver posición actual
$posActual = OrdenPlanilla::where('planilla_id', $planilla->id)->with('maquina')->first();
if ($posActual) {
    echo "Posición actual: {$posActual->maquina->codigo} Pos {$posActual->posicion}" . PHP_EOL;

    // Ver posición 1 de esa máquina
    $pos1 = OrdenPlanilla::where('maquina_id', $posActual->maquina_id)
        ->where('posicion', 1)
        ->with('planilla')
        ->first();
    if ($pos1) {
        echo "Posición 1: {$pos1->planilla->codigo} (entrega: {$pos1->planilla->fecha_estimada_entrega})" . PHP_EOL;

        // Comparar fechas
        $fechaPriorizada = $planilla->fecha_estimada_entrega;
        $fechaPos1 = $pos1->planilla->fecha_estimada_entrega;
        echo PHP_EOL . "Comparación de fechas:" . PHP_EOL;
        echo "  Obra priorizada: {$fechaPriorizada}" . PHP_EOL;
        echo "  Posición 1: {$fechaPos1}" . PHP_EOL;
        echo "  ¿Debe suplantar? " . ($fechaPriorizada < $fechaPos1 ? "SÍ (fecha anterior)" : "NO (fecha posterior o igual)") . PHP_EOL;
    }
}

// Ejecutar con suplantar_primera = true
echo PHP_EOL . "=== EJECUTANDO CON SUPLANTAR_PRIMERA = TRUE ===" . PHP_EOL;
$request = Request::create('/api/produccion/priorizar-obras', 'POST', [
    'obras' => [$obraId],
    'suplantar_primera' => true
]);

$controller = app()->make(ProduccionController::class);
$response = $controller->priorizarObras($request);
$data = json_decode($response->getContent(), true);
echo "Resultado: " . ($data['success'] ? 'OK' : 'ERROR') . " - " . $data['message'] . PHP_EOL;

// Ver posición después
$posActual = OrdenPlanilla::where('planilla_id', $planilla->id)->with('maquina')->first();
if ($posActual) {
    echo PHP_EOL . "Posición DESPUÉS: {$posActual->maquina->codigo} Pos {$posActual->posicion}" . PHP_EOL;
}
