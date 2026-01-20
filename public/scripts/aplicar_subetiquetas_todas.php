<?php
/**
 * Script para aplicar subetiquetas a TODAS las planillas que tengan elementos sin asignar
 *
 * Uso: https://tudominio.com/scripts/aplicar_subetiquetas_todas.php
 *
 * Parámetros:
 *   - dry_run: Si es "1", solo muestra qué se haría sin hacer cambios
 *   - limit: Número máximo de planillas a procesar (por defecto: todas)
 */

set_time_limit(600);
ini_set('memory_limit', '512M');

ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Services\SubEtiquetaService;
use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');

echo "=== APLICAR SUBETIQUETAS A TODAS LAS PLANILLAS ===\n\n";

$dryRun = ($_GET['dry_run'] ?? '0') === '1';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;

if ($dryRun) {
    echo "🔍 MODO DRY-RUN: No se harán cambios\n\n";
}

// 1. Buscar planillas con elementos sin subetiqueta
echo "Buscando planillas con elementos sin subetiqueta...\n";
flush();

$planillaIds = Elemento::whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->whereNotNull('planilla_id')
    ->distinct()
    ->pluck('planilla_id');

echo "Encontradas: {$planillaIds->count()} planillas\n\n";

if ($planillaIds->isEmpty()) {
    echo "✅ Todas las planillas ya tienen subetiquetas asignadas\n";
    exit;
}

// Aplicar límite si se especifica
if ($limit) {
    $planillaIds = $planillaIds->take($limit);
    echo "⚠️ Limitando a {$limit} planillas\n\n";
}

// Cargar planillas con sus datos
$planillas = Planilla::whereIn('id', $planillaIds)
    ->with('obra')
    ->orderBy('codigo')
    ->get();

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
flush();

$subEtiquetaService = new SubEtiquetaService();

$totalPlanillas = 0;
$totalElementos = 0;
$totalSubsCreadas = 0;
$totalErrores = 0;

foreach ($planillas as $planilla) {
    $totalPlanillas++;

    echo "\n[{$totalPlanillas}/{$planillas->count()}] 📋 {$planilla->codigo}";
    echo " - " . ($planilla->obra->nombre ?? 'Sin obra') . "\n";
    flush();

    // Obtener elementos sin subetiqueta de esta planilla
    $elementosSinSub = Elemento::where('planilla_id', $planilla->id)
        ->whereNull('etiqueta_sub_id')
        ->whereNotNull('etiqueta_id')
        ->get();

    if ($elementosSinSub->isEmpty()) {
        echo "   ✅ Ya procesada\n";
        continue;
    }

    echo "   Elementos sin sub: {$elementosSinSub->count()}\n";

    if ($dryRun) {
        // Mostrar desglose por máquina
        $porMaquina = $elementosSinSub->groupBy(function ($e) {
            return $e->maquina_id ?? $e->maquina_id_2 ?? $e->maquina_id_3 ?? 0;
        });

        foreach ($porMaquina as $maquinaId => $elementos) {
            if ($maquinaId) {
                $maquina = Maquina::find($maquinaId);
                $nombre = $maquina ? $maquina->codigo : "ID:{$maquinaId}";
                $tipo = $maquina ? ($maquina->tipo_material ?? '?') : '?';
                echo "      - {$nombre} ({$tipo}): {$elementos->count()}\n";
            } else {
                echo "      - Sin máquina: {$elementos->count()}\n";
            }
        }

        $totalElementos += $elementosSinSub->count();
        continue;
    }

    // Procesar elementos
    $subsCreadas = 0;
    $errores = 0;

    DB::beginTransaction();
    try {
        foreach ($elementosSinSub as $elemento) {
            $maquinaReal = $elemento->maquina_id ?? $elemento->maquina_id_2 ?? $elemento->maquina_id_3;

            if (!$maquinaReal) {
                // Sin máquina: crear subetiqueta individual
                $padre = Etiqueta::find($elemento->etiqueta_id);
                if ($padre) {
                    $subId = Etiqueta::generarCodigoSubEtiqueta($padre->codigo);
                    $subRowId = asegurarFilaSub($subId, $padre);

                    $elemento->update([
                        'etiqueta_sub_id' => $subId,
                        'etiqueta_id' => $subRowId,
                    ]);
                    $subsCreadas++;
                }
                continue;
            }

            try {
                [$subDestino, $subOriginal] = $subEtiquetaService->reubicarSegunTipoMaterial($elemento, $maquinaReal);

                if ($subDestino) {
                    $subsCreadas++;
                }
            } catch (\Exception $e) {
                $errores++;
                // No mostrar cada error para no saturar la salida
            }
        }

        DB::commit();

        echo "   ✅ {$subsCreadas} subetiquetas asignadas";
        if ($errores > 0) {
            echo " ({$errores} errores)";
        }
        echo "\n";

        $totalElementos += $elementosSinSub->count();
        $totalSubsCreadas += $subsCreadas;
        $totalErrores += $errores;

    } catch (\Exception $e) {
        DB::rollBack();
        echo "   ❌ Error: " . substr($e->getMessage(), 0, 100) . "\n";
        $totalErrores++;
    }

    flush();
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "=== RESUMEN FINAL ===\n\n";

echo "Planillas procesadas: {$totalPlanillas}\n";

if ($dryRun) {
    echo "Elementos a procesar: {$totalElementos}\n";
    echo "\n🔍 Ejecuta sin dry_run para aplicar cambios\n";
} else {
    echo "Elementos procesados: {$totalElementos}\n";
    echo "Subetiquetas asignadas: {$totalSubsCreadas}\n";
    if ($totalErrores > 0) {
        echo "Errores: {$totalErrores}\n";
    }
}

echo "\n=== COMPLETADO ===\n";

// ============ FUNCIONES ============

function asegurarFilaSub(string $subId, Etiqueta $padre): int
{
    $existente = Etiqueta::withTrashed()->where('etiqueta_sub_id', $subId)->first();

    if ($existente) {
        if ($existente->trashed()) {
            $existente->restore();
        }
        return $existente->id;
    }

    $sub = Etiqueta::create([
        'codigo' => $padre->codigo,
        'etiqueta_sub_id' => $subId,
        'planilla_id' => $padre->planilla_id,
        'nombre' => $padre->nombre,
        'estado' => 'pendiente',
        'peso' => 0,
    ]);

    return $sub->id;
}
