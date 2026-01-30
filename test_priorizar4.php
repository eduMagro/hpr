<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ProduccionController;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Models\Maquina;

// Buscar una máquina donde la posición 2 tenga fecha ANTERIOR a posición 1
echo "=== BUSCANDO CASO DONDE POSICIÓN 2 TIENE FECHA ANTERIOR A POSICIÓN 1 ===" . PHP_EOL;

$maquinas = Maquina::all();
$casoEncontrado = false;

foreach ($maquinas as $maquina) {
    $pos1 = OrdenPlanilla::where('maquina_id', $maquina->id)
        ->where('posicion', 1)
        ->with('planilla')
        ->first();

    $pos2 = OrdenPlanilla::where('maquina_id', $maquina->id)
        ->where('posicion', 2)
        ->with('planilla')
        ->first();

    if ($pos1 && $pos2 && $pos1->planilla && $pos2->planilla) {
        $fecha1 = $pos1->planilla->fecha_estimada_entrega;
        $fecha2 = $pos2->planilla->fecha_estimada_entrega;

        if ($fecha1 && $fecha2 && $fecha2 < $fecha1) {
            echo "Máquina: {$maquina->codigo}" . PHP_EOL;
            echo "  Pos 1: {$pos1->planilla->codigo} (entrega: {$fecha1}) - Obra: {$pos1->planilla->obra_id}" . PHP_EOL;
            echo "  Pos 2: {$pos2->planilla->codigo} (entrega: {$fecha2}) - Obra: {$pos2->planilla->obra_id}" . PHP_EOL;
            echo "  *** Pos 2 tiene fecha ANTERIOR a Pos 1 ***" . PHP_EOL . PHP_EOL;

            // Priorizar la obra de posición 2 con suplantar_primera = true
            $obraId = $pos2->planilla->obra_id;

            echo "=== EJECUTANDO PRIORIZACIÓN (suplantar_primera=true) ===" . PHP_EOL;
            $request = Request::create('/api/produccion/priorizar-obras', 'POST', [
                'obras' => [$obraId],
                'suplantar_primera' => true
            ]);

            $controller = app()->make(ProduccionController::class);
            $response = $controller->priorizarObras($request);
            $data = json_decode($response->getContent(), true);
            echo "Resultado: " . ($data['success'] ? 'OK' : 'ERROR') . " - " . $data['message'] . PHP_EOL;

            // Ver posición después
            echo PHP_EOL . "=== DESPUÉS ===" . PHP_EOL;
            $pos1New = OrdenPlanilla::where('maquina_id', $maquina->id)
                ->where('posicion', 1)
                ->with('planilla')
                ->first();
            $pos2New = OrdenPlanilla::where('maquina_id', $maquina->id)
                ->where('posicion', 2)
                ->with('planilla')
                ->first();

            if ($pos1New) echo "  Pos 1: {$pos1New->planilla->codigo}" . PHP_EOL;
            if ($pos2New) echo "  Pos 2: {$pos2New->planilla->codigo}" . PHP_EOL;

            $casoEncontrado = true;
            break;
        }
    }
}

if (!$casoEncontrado) {
    echo "No se encontró ningún caso donde Pos 2 tenga fecha anterior a Pos 1" . PHP_EOL;
    echo "La priorización ya ha ordenado todo correctamente" . PHP_EOL;
}
