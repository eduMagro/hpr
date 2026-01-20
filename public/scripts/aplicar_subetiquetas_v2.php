<?php
/**
 * Script web para aplicar subetiquetas
 * Ejecutar desde: /scripts/aplicar_subetiquetas_v2.php
 *
 * Parámetros:
 *   ?dry_run=1  - Solo ver qué se haría
 *   ?limit=50   - Limitar planillas por lote
 */

// Forzar output inmediato
header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');
header('Cache-Control: no-cache');

// Desactivar todos los buffers
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

echo "=== INICIANDO SCRIPT ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
flush();

// Bootstrap Laravel
echo "Cargando Laravel...\n";
flush();

require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Services\SubEtiquetaService;
use Illuminate\Support\Facades\DB;

echo "Laravel cargado OK\n\n";
flush();

// Parámetros
$dryRun = ($_GET['dry_run'] ?? '0') === '1';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

if ($dryRun) {
    echo "*** MODO DRY-RUN: No se harán cambios ***\n\n";
}

// Configurar tiempo
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "Contando planillas pendientes...\n";
flush();

$totalPlanillas = DB::table('elementos')
    ->whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->whereNotNull('planilla_id')
    ->distinct()
    ->count('planilla_id');

$totalElementos = DB::table('elementos')
    ->whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->count();

echo "Planillas pendientes: {$totalPlanillas}\n";
echo "Elementos sin subetiqueta: {$totalElementos}\n\n";
flush();

if ($totalPlanillas === 0) {
    echo "✅ Todo completado. No hay planillas pendientes.\n";
    exit;
}

echo "Procesando en lotes de {$limit} planillas...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
flush();

$subEtiquetaService = new SubEtiquetaService();
$procesadas = 0;
$elementosProcesados = 0;
$subetiquetasCreadas = 0;
$errores = 0;
$startTime = microtime(true);

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

$total = count($planillaIds);
echo "Planillas en este lote: {$total}\n\n";
flush();

foreach ($planillaIds as $index => $planillaId) {
    $planilla = Planilla::find($planillaId);
    if (!$planilla) continue;

    $num = $index + 1;
    echo "[{$num}/{$total}] {$planilla->codigo}";
    flush();

    // Contar elementos de esta planilla
    $elementos = Elemento::where('planilla_id', $planillaId)
        ->whereNull('etiqueta_sub_id')
        ->whereNotNull('etiqueta_id')
        ->get();

    $cantElementos = $elementos->count();
    echo " - {$cantElementos} elementos";
    flush();

    if ($cantElementos === 0) {
        echo " ✓ (ya procesada)\n";
        flush();
        continue;
    }

    if ($dryRun) {
        echo " [dry-run]\n";
        $elementosProcesados += $cantElementos;
        flush();
        continue;
    }

    // Procesar elementos
    $subsEnPlanilla = 0;
    $sinMaquina = 0;
    $sinPadre = 0;
    $sinTipoMaterial = 0;

    DB::beginTransaction();
    try {
        foreach ($elementos as $elemento) {
            $subIdAntes = $elemento->etiqueta_sub_id;
            $maquinaReal = $elemento->maquina_id ?? $elemento->maquina_id_2 ?? $elemento->maquina_id_3;

            // Buscar etiqueta padre
            $padre = Etiqueta::find($elemento->etiqueta_id);

            if (!$padre) {
                $sinPadre++;
                continue;
            }

            if (!$maquinaReal) {
                $sinMaquina++;
                // Sin máquina: crear subetiqueta individual
                $subId = Etiqueta::generarCodigoSubEtiqueta($padre->codigo);
                $subRowId = asegurarFilaSub($subId, $padre);
                $elemento->etiqueta_sub_id = $subId;
                $elemento->etiqueta_id = $subRowId;
                $elemento->save();
                $subsEnPlanilla++;
                continue;
            }

            // Con máquina: verificar tipo_material y asignar según eso
            $maquina = \App\Models\Maquina::find($maquinaReal);
            if (!$maquina) {
                $sinMaquina++;
                continue;
            }

            $tipoMaterial = strtolower((string)($maquina->tipo_material ?? ''));

            // Crear subetiqueta directamente según tipo de material
            // BARRA = 1 elemento por sub, ENCARRETADO = agrupar
            $subId = Etiqueta::generarCodigoSubEtiqueta($padre->codigo);
            $subRowId = asegurarFilaSub($subId, $padre);

            $elemento->etiqueta_sub_id = $subId;
            $elemento->etiqueta_id = $subRowId;
            $elemento->save();
            $subsEnPlanilla++;
        }

        DB::commit();
        $elementosProcesados += $cantElementos;
        $subetiquetasCreadas += $subsEnPlanilla;

        $info = "{$subsEnPlanilla} subs";
        if ($sinMaquina > 0) $info .= ", {$sinMaquina} sin máq";
        if ($sinPadre > 0) $info .= ", {$sinPadre} sin etiq padre";
        echo " ✓ ({$info})\n";

        // Mostrar progreso cada 10 planillas
        if ($procesadas % 10 === 0) {
            $memoria = round(memory_get_usage() / 1024 / 1024, 1);
            echo "   [Mem: {$memoria}MB]\n";
        }

    } catch (\Exception $e) {
        DB::rollBack();
        $errores++;
        echo " ❌ Error: " . substr($e->getMessage(), 0, 40) . "\n";
    }

    flush();
    $procesadas++;

    // Liberar memoria cada 10 planillas
    if ($procesadas % 10 === 0) {
        gc_collect_cycles();
    }
}

$elapsed = round(microtime(true) - $startTime, 1);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "=== RESUMEN ===\n\n";
echo "Tiempo: {$elapsed} segundos\n";
echo "Planillas procesadas: {$procesadas}\n";
echo "Elementos procesados: {$elementosProcesados}\n";

if (!$dryRun) {
    echo "Subetiquetas asignadas: {$subetiquetasCreadas}\n";
}

if ($errores > 0) {
    echo "Errores: {$errores}\n";
}

// Verificar pendientes
$pendientes = DB::table('elementos')
    ->whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->count();

echo "\nElementos pendientes: {$pendientes}\n";

// Debug: verificar datos
echo "\n--- DEBUG ---\n";

// Verificar si hay elementos donde etiqueta_id no apunta a una etiqueta válida
$elementosSinEtiquetaValida = DB::table('elementos')
    ->whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->whereNotExists(function ($q) {
        $q->select(DB::raw(1))
            ->from('etiquetas')
            ->whereColumn('etiquetas.id', 'elementos.etiqueta_id');
    })
    ->count();

echo "Elementos con etiqueta_id inválido: {$elementosSinEtiquetaValida}\n";

// Ver un ejemplo de elemento pendiente
$ejemploElemento = Elemento::whereNull('etiqueta_sub_id')
    ->whereNotNull('etiqueta_id')
    ->first();

if ($ejemploElemento) {
    echo "Ejemplo elemento pendiente:\n";
    echo "  - Elemento ID: {$ejemploElemento->id}\n";
    echo "  - etiqueta_id: " . ($ejemploElemento->etiqueta_id ?? 'NULL') . "\n";
    echo "  - etiqueta_sub_id: " . ($ejemploElemento->etiqueta_sub_id ?? 'NULL') . "\n";
    echo "  - maquina_id: " . ($ejemploElemento->maquina_id ?? 'NULL') . "\n";
    echo "  - planilla_id: " . ($ejemploElemento->planilla_id ?? 'NULL') . "\n";

    // Verificar si su etiqueta existe
    $etiqueta = Etiqueta::find($ejemploElemento->etiqueta_id);
    if ($etiqueta) {
        echo "  - Etiqueta encontrada: codigo={$etiqueta->codigo}, sub_id=" . ($etiqueta->etiqueta_sub_id ?? 'NULL') . "\n";
    } else {
        echo "  - ❌ Etiqueta NO encontrada con ID: {$ejemploElemento->etiqueta_id}\n";
    }
}

if ($pendientes > 0) {
    echo "\n⚠️ Recarga la página para procesar el siguiente lote.\n";
} else {
    echo "\n✅ ¡COMPLETADO! Todos los elementos tienen subetiqueta.\n";
}

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
