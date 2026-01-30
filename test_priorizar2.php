<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ProduccionController;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Models\Maquina;

// Buscar una obra para priorizar que tenga planillas en posiciones > 2
echo "=== BUSCANDO OBRA PARA PRIORIZAR ===" . PHP_EOL;

$ordenPlanilla = OrdenPlanilla::where('posicion', '>', 3)
    ->whereHas('planilla', function($q) {
        $q->whereNotNull('fecha_estimada_entrega')
          ->whereNotNull('obra_id');
    })
    ->with(['planilla.obra', 'maquina'])
    ->first();

if (!$ordenPlanilla) {
    echo "No hay planillas en posiciones > 3" . PHP_EOL;
    exit;
}

$obraId = $ordenPlanilla->planilla->obra_id;
$obraNombre = $ordenPlanilla->planilla->obra->obra;
$maquinaId = $ordenPlanilla->maquina_id;
$maquinaCodigo = $ordenPlanilla->maquina->codigo;

echo "Obra a priorizar: {$obraId} - {$obraNombre}" . PHP_EOL;
echo "Máquina: {$maquinaCodigo}" . PHP_EOL . PHP_EOL;

// Ver posición actual de esa obra
echo "=== POSICIONES ANTES ===" . PHP_EOL;
$planillasObra = Planilla::where('obra_id', $obraId)->pluck('id');
$posiciones = OrdenPlanilla::whereIn('planilla_id', $planillasObra)
    ->with(['planilla', 'maquina'])
    ->orderBy('maquina_id')
    ->get();

foreach ($posiciones as $p) {
    echo "  {$p->maquina->codigo} Pos {$p->posicion}: {$p->planilla->codigo} (entrega: {$p->planilla->fecha_estimada_entrega})" . PHP_EOL;
}

// Ver posición 1 de esas máquinas
echo PHP_EOL . "=== POSICION 1 EN ESAS MAQUINAS ===" . PHP_EOL;
$maquinaIds = $posiciones->pluck('maquina_id')->unique();
foreach ($maquinaIds as $mId) {
    $pos1 = OrdenPlanilla::where('maquina_id', $mId)
        ->where('posicion', 1)
        ->with('planilla')
        ->first();
    if ($pos1) {
        $maq = Maquina::find($mId);
        echo "  {$maq->codigo} Pos 1: {$pos1->planilla->codigo} (entrega: {$pos1->planilla->fecha_estimada_entrega})" . PHP_EOL;
    }
}

// Ejecutar priorización SIN suplantar
echo PHP_EOL . "=== EJECUTANDO PRIORIZACIÓN (sin suplantar) ===" . PHP_EOL;
$request = Request::create('/api/produccion/priorizar-obras', 'POST', [
    'obras' => [$obraId],
    'suplantar_primera' => false
]);

$controller = app()->make(ProduccionController::class);
$response = $controller->priorizarObras($request);
$data = json_decode($response->getContent(), true);
echo "Resultado: " . ($data['success'] ? 'OK' : 'ERROR') . " - " . $data['message'] . PHP_EOL;

// Ver posiciones después
echo PHP_EOL . "=== POSICIONES DESPUES ===" . PHP_EOL;
$posiciones = OrdenPlanilla::whereIn('planilla_id', $planillasObra)
    ->with(['planilla', 'maquina'])
    ->orderBy('maquina_id')
    ->get();

foreach ($posiciones as $p) {
    echo "  {$p->maquina->codigo} Pos {$p->posicion}: {$p->planilla->codigo}" . PHP_EOL;
}
