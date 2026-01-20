<?php
/**
 * Script web para aplicar subetiquetas a TODOS los elementos
 * Se auto-recarga si detecta que está cerca del timeout
 *
 * Ejecutar desde: /scripts/aplicar_subetiquetas_v2.php
 *
 * Parámetros:
 *   ?dry_run=1  - Solo ver qué se haría
 *   ?limit=100  - Planillas por lote (default: 100)
 *   ?stop=1     - Detener el proceso automático
 */

// Configurar tiempo máximo (10 minutos)
set_time_limit(600);
ini_set('memory_limit', '512M');

// Tiempo máximo antes de auto-recarga (segundos)
$maxExecutionTime = 120; // 2 minutos por seguridad
$startTime = microtime(true);

// Forzar output inmediato
header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Buffering: no');
header('Cache-Control: no-cache');

// Desactivar todos los buffers
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

// Parámetros
$dryRun = ($_GET['dry_run'] ?? '0') === '1';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$stop = ($_GET['stop'] ?? '0') === '1';

// HTML inicial
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Aplicar Subetiquetas</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#eee;padding:20px;} .ok{color:#4ade80;} .warn{color:#fbbf24;} .err{color:#f87171;}</style>";
echo "</head><body><pre>";

echo "=== APLICAR SUBETIQUETAS - MODO AUTOMÁTICO ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
if ($stop) {
    echo "\n<span class='warn'>⏹️ Proceso detenido manualmente.</span>\n";
    echo "\n<a href='?'>▶️ Reanudar proceso</a>\n";
    echo "</pre></body></html>";
    exit;
}
if ($dryRun) {
    echo "<span class='warn'>*** MODO DRY-RUN: No se harán cambios ***</span>\n";
}
echo "\n";
flush();

// Bootstrap Laravel
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Etiqueta;
use Illuminate\Support\Facades\DB;

// Contar pendientes inicial
$totalPendientes = DB::table('elementos')
    ->whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->count();

if ($totalPendientes === 0) {
    echo "<span class='ok'>✅ ¡COMPLETADO! Todos los elementos tienen subetiqueta.</span>\n";
    echo "</pre></body></html>";
    exit;
}

$totalPlanillas = DB::table('elementos')
    ->whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->whereNotNull('planilla_id')
    ->distinct()
    ->count('planilla_id');

echo "Elementos pendientes: <strong>{$totalPendientes}</strong>\n";
echo "Planillas pendientes: <strong>{$totalPlanillas}</strong>\n";
echo "Procesando en lotes de {$limit} planillas...\n";
echo "<a href='?stop=1' style='color:#f87171;'>⏹️ Detener proceso</a>\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
flush();

$totalElementosProcesados = 0;
$totalSubsCreadas = 0;
$totalErrores = 0;
$lotesCompletados = 0;

// Procesar en loop hasta terminar o timeout
while (true) {
    $elapsedTime = microtime(true) - $startTime;

    // Verificar si estamos cerca del timeout
    if ($elapsedTime > $maxExecutionTime) {
        echo "\n<span class='warn'>⏱️ Tiempo límite alcanzado ({$maxExecutionTime}s). Auto-recargando...</span>\n";
        echo "\nElementos procesados en esta sesión: {$totalElementosProcesados}\n";
        echo "Subetiquetas creadas en esta sesión: {$totalSubsCreadas}\n";

        // Auto-recarga
        $url = "?limit={$limit}" . ($dryRun ? '&dry_run=1' : '');
        echo "\n<script>setTimeout(function(){ window.location.href='{$url}'; }, 1000);</script>";
        echo "<noscript><meta http-equiv='refresh' content='1;url={$url}'></noscript>";
        echo "\n<a href='{$url}'>🔄 Click aquí si no recarga automáticamente</a>\n";
        echo "</pre></body></html>";
        exit;
    }

    // Obtener lote de planillas
    $planillaIds = DB::table('elementos')
        ->whereNull('etiqueta_sub_id')
        ->whereNotNull('etiqueta_id')
        ->whereNotNull('planilla_id')
        ->distinct()
        ->orderBy('planilla_id')
        ->limit($limit)
        ->pluck('planilla_id')
        ->toArray();

    if (empty($planillaIds)) {
        break; // No hay más pendientes
    }

    $cantLote = count($planillaIds);
    $lotesCompletados++;

    echo "📦 <strong>Lote #{$lotesCompletados}</strong> - {$cantLote} planillas\n";
    flush();

    foreach ($planillaIds as $index => $planillaId) {
        $planilla = Planilla::find($planillaId);
        if (!$planilla) continue;

        $num = $index + 1;

        // Obtener elementos sin subetiqueta de esta planilla
        $elementos = Elemento::where('planilla_id', $planillaId)
            ->whereNull('etiqueta_sub_id')
            ->whereNotNull('etiqueta_id')
            ->get();

        $cantElementos = $elementos->count();
        if ($cantElementos === 0) continue;

        echo "  [{$num}/{$cantLote}] {$planilla->codigo} - {$cantElementos} elem";
        flush();

        if ($dryRun) {
            echo " <span class='warn'>[dry-run]</span>\n";
            $totalElementosProcesados += $cantElementos;
            continue;
        }

        // Procesar elementos
        $subsEnPlanilla = 0;
        $erroresEnPlanilla = 0;

        DB::beginTransaction();
        try {
            foreach ($elementos as $elemento) {
                // Buscar etiqueta padre (incluyendo soft-deleted)
                $padre = Etiqueta::withTrashed()->find($elemento->etiqueta_id);

                if (!$padre) {
                    $erroresEnPlanilla++;
                    continue;
                }

                // Si la etiqueta padre está eliminada, restaurarla
                if ($padre->trashed()) {
                    $padre->restore();
                }

                // Crear subetiqueta
                $subId = Etiqueta::generarCodigoSubEtiqueta($padre->codigo);
                $subRowId = asegurarFilaSub($subId, $padre);

                $elemento->etiqueta_sub_id = $subId;
                $elemento->etiqueta_id = $subRowId;
                $elemento->save();
                $subsEnPlanilla++;
            }

            DB::commit();
            $totalElementosProcesados += $cantElementos;
            $totalSubsCreadas += $subsEnPlanilla;

            echo " <span class='ok'>✓ {$subsEnPlanilla} subs</span>";
            if ($erroresEnPlanilla > 0) {
                echo " <span class='err'>({$erroresEnPlanilla} err)</span>";
                $totalErrores += $erroresEnPlanilla;
            }
            echo "\n";

        } catch (\Exception $e) {
            DB::rollBack();
            $totalErrores++;
            echo " <span class='err'>❌ " . substr($e->getMessage(), 0, 40) . "</span>\n";
        }

        flush();
    }

    // Liberar memoria
    gc_collect_cycles();

    // Mostrar progreso
    $pendientesAhora = DB::table('elementos')
        ->whereNull('etiqueta_sub_id')
        ->whereNotNull('etiqueta_id')
        ->count();

    $elapsed = round(microtime(true) - $startTime, 1);
    $memoria = round(memory_get_usage() / 1024 / 1024, 1);

    echo "\n   📊 Pendientes: {$pendientesAhora} | Tiempo: {$elapsed}s | Mem: {$memoria}MB\n\n";
    flush();

    if ($pendientesAhora === 0) {
        break;
    }
}

// Resumen final
$elapsed = round(microtime(true) - $startTime, 1);
$pendientesFinales = DB::table('elementos')
    ->whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->count();

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "=== RESUMEN FINAL ===\n\n";
echo "Tiempo: {$elapsed} segundos\n";
echo "Lotes completados: {$lotesCompletados}\n";
echo "Elementos procesados: {$totalElementosProcesados}\n";

if (!$dryRun) {
    echo "Subetiquetas creadas: <span class='ok'>{$totalSubsCreadas}</span>\n";
}

if ($totalErrores > 0) {
    echo "Errores: <span class='err'>{$totalErrores}</span>\n";
}

echo "\nElementos pendientes: ";
if ($pendientesFinales === 0) {
    echo "<span class='ok'>0 - ¡COMPLETADO!</span>\n";
    echo "\n<span class='ok'>✅ ¡Todos los elementos tienen subetiqueta asignada!</span>\n";
} else {
    echo "<span class='warn'>{$pendientesFinales}</span>\n";
    $url = "?limit={$limit}" . ($dryRun ? '&dry_run=1' : '');
    echo "\n<a href='{$url}'>🔄 Continuar procesando</a>\n";
}

echo "</pre></body></html>";

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
