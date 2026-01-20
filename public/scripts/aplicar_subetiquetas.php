<?php
/**
 * Script para aplicar política de subetiquetas a planillas existentes
 * Usa SubEtiquetaService para mantener consistencia con el resto del sistema
 *
 * Uso: https://tudominio.com/scripts/aplicar_subetiquetas.php?codigos=2026-252,2026-253
 *
 * Parámetros:
 *   - codigos: Códigos de planillas separados por coma (ej: 2026-252,2026-253)
 *   - dry_run: Si es "1", solo muestra qué se haría sin hacer cambios
 */

set_time_limit(300);
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

echo "=== APLICAR SUBETIQUETAS A PLANILLAS ===\n\n";

// Obtener parámetros
$codigosParam = $_GET['codigos'] ?? '';
$dryRun = ($_GET['dry_run'] ?? '0') === '1';

if (empty($codigosParam)) {
    echo "❌ Error: Debes especificar códigos de planillas\n\n";
    echo "Uso:\n";
    echo "  ?codigos=2026-252,2026-253\n";
    echo "  ?codigos=2026-252&dry_run=1  (solo ver qué se haría)\n\n";
    echo "Ejemplos:\n";
    echo "  aplicar_subetiquetas.php?codigos=2026-252\n";
    echo "  aplicar_subetiquetas.php?codigos=2026-252,2026-253,2026-254\n";
    echo "  aplicar_subetiquetas.php?codigos=2026-252&dry_run=1\n";
    exit;
}

$codigos = array_map('trim', explode(',', $codigosParam));

if ($dryRun) {
    echo "🔍 MODO DRY-RUN: No se harán cambios\n\n";
}

echo "Planillas a procesar: " . implode(', ', $codigos) . "\n\n";
flush();

$subEtiquetaService = new SubEtiquetaService();

$totalElementos = 0;
$totalSubsCreadas = 0;

foreach ($codigos as $codigo) {
    // Normalizar código
    $codigoNormalizado = normalizarCodigo($codigo);

    $planilla = Planilla::where('codigo', $codigoNormalizado)
        ->orWhere('codigo', $codigo)
        ->first();

    if (!$planilla) {
        echo "❌ Planilla no encontrada: {$codigo}\n";
        continue;
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📋 Planilla: {$planilla->codigo}\n";
    echo "   Obra: " . ($planilla->obra->nombre ?? 'N/A') . "\n";
    flush();

    // Obtener elementos sin subetiqueta de esta planilla
    $elementosSinSub = Elemento::where('planilla_id', $planilla->id)
        ->whereNull('etiqueta_sub_id')
        ->whereNotNull('etiqueta_id')
        ->get();

    if ($elementosSinSub->isEmpty()) {
        echo "   ✅ Todos los elementos ya tienen subetiqueta\n\n";
        continue;
    }

    echo "   Elementos sin subetiqueta: {$elementosSinSub->count()}\n";
    flush();

    if ($dryRun) {
        // Mostrar desglose por máquina
        $porMaquina = $elementosSinSub->groupBy(function ($e) {
            return $e->maquina_id ?? $e->maquina_id_2 ?? $e->maquina_id_3 ?? 0;
        });

        foreach ($porMaquina as $maquinaId => $elementos) {
            if ($maquinaId) {
                $maquina = Maquina::find($maquinaId);
                $tipo = $maquina ? ($maquina->tipo_material ?? 'desconocido') : 'N/A';
                echo "      - Máquina {$maquinaId} ({$tipo}): {$elementos->count()} elementos\n";
            } else {
                echo "      - Sin máquina: {$elementos->count()} elementos\n";
            }
        }

        $totalElementos += $elementosSinSub->count();
        echo "\n";
        continue;
    }

    // Procesar elementos usando SubEtiquetaService
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
                // Usar el servicio para reubicar según tipo de material
                [$subDestino, $subOriginal] = $subEtiquetaService->reubicarSegunTipoMaterial($elemento, $maquinaReal);

                if ($subDestino && $subDestino !== $subOriginal) {
                    $subsCreadas++;
                }
            } catch (\Exception $e) {
                $errores++;
                echo "      ⚠️ Error en elemento {$elemento->id}: {$e->getMessage()}\n";
            }
        }

        DB::commit();

        echo "   ✅ Procesados {$elementosSinSub->count()} elementos\n";
        echo "   ✅ Subetiquetas asignadas/creadas: {$subsCreadas}\n";
        if ($errores > 0) {
            echo "   ⚠️ Errores: {$errores}\n";
        }

        $totalElementos += $elementosSinSub->count();
        $totalSubsCreadas += $subsCreadas;

    } catch (\Exception $e) {
        DB::rollBack();
        echo "   ❌ Error general: {$e->getMessage()}\n";
    }

    echo "\n";
    flush();
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "=== RESUMEN ===\n\n";

if ($dryRun) {
    echo "🔍 Se procesarían {$totalElementos} elementos\n";
} else {
    echo "✅ Procesados {$totalElementos} elementos\n";
    echo "✅ Subetiquetas asignadas: {$totalSubsCreadas}\n";
}

echo "\n=== COMPLETADO ===\n";

// ============ FUNCIONES ============

function normalizarCodigo(string $codigo): string
{
    if (preg_match('/^(\d{4})-(\d+)$/', $codigo, $matches)) {
        return $matches[1] . '-' . str_pad($matches[2], 6, '0', STR_PAD_LEFT);
    }
    return $codigo;
}

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
