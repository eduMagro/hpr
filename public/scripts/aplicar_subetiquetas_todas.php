<?php
/**
 * Script para aplicar subetiquetas a TODAS las planillas que tengan elementos sin asignar
 * OPTIMIZADO para bajo consumo de memoria
 *
 * Uso: https://tudominio.com/scripts/aplicar_subetiquetas_todas.php
 *
 * Parámetros:
 *   - dry_run: Si es "1", solo muestra qué se haría sin hacer cambios
 *   - limit: Número máximo de planillas a procesar (por defecto: todas)
 *   - offset: Saltar las primeras N planillas (para continuar si se interrumpe)
 */

set_time_limit(1800); // 30 minutos
ini_set('memory_limit', '256M');

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

echo "=== APLICAR SUBETIQUETAS A TODAS LAS PLANILLAS ===\n";
echo "Memoria inicial: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n\n";

$dryRun = ($_GET['dry_run'] ?? '0') === '1';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($dryRun) {
    echo "🔍 MODO DRY-RUN: No se harán cambios\n\n";
}

// 1. Contar planillas afectadas (query ligera)
echo "Contando planillas con elementos sin subetiqueta...\n";
flush();

$totalPlanillas = DB::table('elementos')
    ->whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->whereNotNull('planilla_id')
    ->distinct()
    ->count('planilla_id');

echo "Total planillas afectadas: {$totalPlanillas}\n";

if ($totalPlanillas === 0) {
    echo "✅ Todas las planillas ya tienen subetiquetas asignadas\n";
    exit;
}

// Contar elementos
$totalElementosSinSub = DB::table('elementos')
    ->whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->count();

echo "Total elementos sin subetiqueta: {$totalElementosSinSub}\n\n";

if ($offset > 0) {
    echo "⏭️ Saltando las primeras {$offset} planillas\n\n";
}

if ($limit) {
    echo "⚠️ Limitando a {$limit} planillas\n\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
flush();

// 2. Obtener IDs de planillas afectadas (solo IDs, no modelos completos)
$planillaIds = DB::table('elementos')
    ->whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->whereNotNull('planilla_id')
    ->distinct()
    ->orderBy('planilla_id')
    ->when($offset > 0, fn($q) => $q->skip($offset))
    ->when($limit, fn($q) => $q->take($limit))
    ->pluck('planilla_id')
    ->toArray();

$totalAProcesar = count($planillaIds);
echo "Planillas a procesar: {$totalAProcesar}\n\n";
flush();

$subEtiquetaService = new SubEtiquetaService();

$procesadas = 0;
$totalElementos = 0;
$totalSubsCreadas = 0;
$totalErrores = 0;

// 3. Procesar una planilla a la vez para ahorrar memoria
foreach ($planillaIds as $planillaId) {
    $procesadas++;

    // Cargar solo datos básicos de la planilla
    $planillaData = DB::table('planillas')
        ->where('id', $planillaId)
        ->select('id', 'codigo')
        ->first();

    if (!$planillaData) {
        continue;
    }

    // Obtener nombre de obra
    $obraNombre = DB::table('planillas')
        ->join('obras', 'planillas.obra_id', '=', 'obras.id')
        ->where('planillas.id', $planillaId)
        ->value('obras.obra') ?? 'Sin obra';

    echo "[{$procesadas}/{$totalAProcesar}] 📋 {$planillaData->codigo} - {$obraNombre}\n";
    flush();

    // Contar elementos sin subetiqueta de esta planilla
    $cantidadSinSub = DB::table('elementos')
        ->where('planilla_id', $planillaId)
        ->whereNull('etiqueta_sub_id')
        ->whereNotNull('etiqueta_id')
        ->count();

    if ($cantidadSinSub === 0) {
        echo "   ✅ Ya procesada\n";
        continue;
    }

    echo "   Elementos sin sub: {$cantidadSinSub}\n";

    if ($dryRun) {
        $totalElementos += $cantidadSinSub;
        continue;
    }

    // Procesar en lotes de 100 elementos
    $subsCreadas = 0;
    $errores = 0;
    $loteSize = 100;
    $procesadosLote = 0;

    do {
        // Obtener lote de elementos
        $elementosLote = Elemento::where('planilla_id', $planillaId)
            ->whereNull('etiqueta_sub_id')
            ->whereNotNull('etiqueta_id')
            ->limit($loteSize)
            ->get();

        if ($elementosLote->isEmpty()) {
            break;
        }

        DB::beginTransaction();
        try {
            foreach ($elementosLote as $elemento) {
                $maquinaReal = $elemento->maquina_id_2 ?? $elemento->maquina_id;

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
                }
            }

            DB::commit();
            $procesadosLote += $elementosLote->count();

        } catch (\Exception $e) {
            DB::rollBack();
            echo "   ❌ Error en lote: " . substr($e->getMessage(), 0, 80) . "\n";
            $errores++;
            break;
        }

        // Liberar memoria
        unset($elementosLote);

    } while (true);

    echo "   ✅ {$subsCreadas} subetiquetas";
    if ($errores > 0) {
        echo " ({$errores} errores)";
    }
    echo "\n";

    $totalElementos += $procesadosLote;
    $totalSubsCreadas += $subsCreadas;
    $totalErrores += $errores;

    // Liberar memoria cada 10 planillas
    if ($procesadas % 10 === 0) {
        gc_collect_cycles();
        echo "   [Memoria: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB]\n";
    }

    flush();
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "=== RESUMEN FINAL ===\n\n";

echo "Planillas procesadas: {$procesadas}\n";

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

echo "\nMemoria final: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
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
