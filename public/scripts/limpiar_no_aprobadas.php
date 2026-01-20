<?php
/**
 * Script para eliminar planillas NO aprobadas de orden_planillas
 * y reindexar posiciones
 *
 * Ejecutar via: https://tudominio.com/scripts/limpiar_no_aprobadas.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdenPlanilla;
use App\Models\Maquina;
use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

echo "=== LIMPIAR PLANILLAS NO APROBADAS DE ORDEN_PLANILLAS ===\n\n";

// 1. Contar estado actual
$total = OrdenPlanilla::count();
$noAprobadas = OrdenPlanilla::join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
    ->where('planillas.aprobada', false)
    ->count();
$aprobadas = $total - $noAprobadas;

echo "Estado actual:\n";
echo "  Total en orden_planillas: $total\n";
echo "  Aprobadas: $aprobadas\n";
echo "  NO aprobadas (a eliminar): $noAprobadas\n\n";

if ($noAprobadas == 0) {
    echo "No hay planillas no aprobadas para eliminar.\n";
    exit;
}

// 2. Eliminar no aprobadas en lotes
echo "Eliminando $noAprobadas registros...\n";

$idsEliminar = OrdenPlanilla::join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
    ->where('planillas.aprobada', false)
    ->pluck('orden_planillas.id');

$eliminados = 0;
foreach ($idsEliminar->chunk(500) as $chunk) {
    $deleted = OrdenPlanilla::whereIn('id', $chunk)->delete();
    $eliminados += $deleted;
    echo "  Eliminados: $eliminados\n";
}

echo "\nTotal eliminados: $eliminados\n\n";

// 3. Reindexar posiciones
echo "=== REINDEXANDO POSICIONES ===\n\n";

$maquinaIds = OrdenPlanilla::select('maquina_id')->distinct()->pluck('maquina_id');
$maquinas = Maquina::whereIn('id', $maquinaIds)->get()->keyBy('id');

$totalReindexadas = 0;

foreach ($maquinaIds as $maqId) {
    $maquina = $maquinas->get($maqId);
    $nombre = $maquina ? "{$maquina->nombre} ({$maquina->codigo})" : "Maquina ID: $maqId";

    $ordenes = OrdenPlanilla::where('maquina_id', $maqId)
        ->orderBy('posicion')
        ->get();

    if ($ordenes->isEmpty()) continue;

    $primeraPos = $ordenes->first()->posicion;

    // Verificar si necesita reindexar
    $necesita = $primeraPos != 1;
    if (!$necesita) {
        $posiciones = $ordenes->pluck('posicion')->toArray();
        for ($i = 0; $i < count($posiciones); $i++) {
            if ($posiciones[$i] != $i + 1) {
                $necesita = true;
                break;
            }
        }
    }

    if (!$necesita) continue;

    echo "$nombre: {$ordenes->count()} planillas\n";

    DB::transaction(function () use ($ordenes) {
        $nuevaPos = 1;
        foreach ($ordenes as $orden) {
            if ($orden->posicion != $nuevaPos) {
                $orden->update(['posicion' => $nuevaPos]);
            }
            $nuevaPos++;
        }
    });

    echo "  -> Reindexado: posiciones 1-{$ordenes->count()}\n";
    $totalReindexadas++;
}

echo "\n$totalReindexadas maquinas reindexadas\n\n";

// 4. Estado final
echo "=== ESTADO FINAL ===\n\n";

$porMaquina = OrdenPlanilla::join('maquinas', 'orden_planillas.maquina_id', '=', 'maquinas.id')
    ->selectRaw('maquinas.codigo, COUNT(*) as total')
    ->groupBy('maquinas.codigo')
    ->orderByDesc('total')
    ->get();

echo "Planillas por maquina:\n";
foreach ($porMaquina as $m) {
    echo "  {$m->codigo}: {$m->total}\n";
}
echo "\nTotal: " . OrdenPlanilla::count() . "\n";

echo "\n=== COMPLETADO ===\n";
