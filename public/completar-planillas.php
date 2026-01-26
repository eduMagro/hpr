<?php
/**
 * Script para completar planillas inconsistentes
 * Acceder via: /completar-planillas.php?token=SECRETO&fecha=2026-01-01
 *
 * Parámetros:
 * - token: Token de seguridad (requerido)
 * - fecha: Fecha de corte YYYY-MM-DD (opcional, default: hoy)
 * - dry: 1 para simular sin cambios (opcional)
 * - solo_completadas: 1 para solo procesar las ya completadas (opcional)
 */

// Token de seguridad - CAMBIAR POR UNO PROPIO
define('TOKEN_SECRETO', 'completar2025seguro');

// Verificar token
if (!isset($_GET['token']) || $_GET['token'] !== TOKEN_SECRETO) {
    http_response_code(403);
    die('Acceso denegado. Token inválido.');
}

// Configuración para ejecución larga
set_time_limit(0);
ini_set('memory_limit', '512M');
ignore_user_abort(true);

// Headers para streaming
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // Desactivar buffering de nginx

// Función para output inmediato
function output($msg, $type = 'info') {
    $colors = [
        'info' => '#333',
        'success' => '#28a745',
        'error' => '#dc3545',
        'warning' => '#ffc107',
    ];
    $color = $colors[$type] ?? '#333';
    echo "<div style='color:{$color};font-family:monospace;margin:2px 0;'>{$msg}</div>";
    if (ob_get_level()) ob_flush();
    flush();
}

// Cargar Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Paquete;
use App\Models\OrdenPlanilla;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Parámetros
$fechaCorteStr = $_GET['fecha'] ?? null;
$fechaCorte = $fechaCorteStr
    ? Carbon::parse($fechaCorteStr)->endOfDay()
    : Carbon::today()->endOfDay();
$dryRun = isset($_GET['dry']) && $_GET['dry'] == '1';
$soloCompletadas = isset($_GET['solo_completadas']) && $_GET['solo_completadas'] == '1';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Completar Planillas</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .stats { background: #e9ecef; padding: 15px; border-radius: 4px; margin-top: 20px; }
        .progress { height: 30px; background: #e9ecef; border-radius: 4px; overflow: hidden; margin: 10px 0; }
        .progress-bar { height: 100%; background: #007bff; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        #log { max-height: 400px; overflow-y: auto; background: #1e1e1e; color: #ddd; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔄 Completar Planillas Inconsistentes</h1>
    <p><strong>Fecha de corte:</strong> <?= $fechaCorte->format('d/m/Y') ?></p>
    <p><strong>Modo:</strong> <?= $dryRun ? '⚠️ SIMULACIÓN (no se harán cambios)' : '✅ EJECUCIÓN REAL' ?></p>
    <p><strong>Filtro:</strong> <?= $soloCompletadas ? 'Solo planillas completadas' : 'Todas (pendiente, fabricando, completada)' ?></p>

    <div class="progress">
        <div class="progress-bar" id="progressBar" style="width: 0%">0%</div>
    </div>

    <div id="log">
<?php

// Obtener planillas candidatas
$query = Planilla::query()
    ->whereNotNull('fecha_estimada_entrega')
    ->whereDate('fecha_estimada_entrega', '<=', $fechaCorte);

if ($soloCompletadas) {
    $query->where('estado', 'completada');
} else {
    $query->whereIn('estado', ['pendiente', 'fabricando', 'completada']);
}

$planillas = $query->get();
$total = $planillas->count();

output("📋 Planillas a procesar: {$total}");

if ($total === 0) {
    output("⚠️ No hay planillas para procesar.", 'warning');
    echo '</div></div></body></html>';
    exit;
}

$stats = [
    'planillas_ok' => 0,
    'planillas_fail' => 0,
    'etiquetas_actualizadas' => 0,
    'ordenes_eliminadas' => 0,
    'paquetes_creados' => 0,
];

$errores = [];
$procesadas = 0;

foreach ($planillas as $planilla) {
    $procesadas++;
    $porcentaje = round(($procesadas / $total) * 100);

    // Actualizar barra de progreso
    echo "<script>document.getElementById('progressBar').style.width='{$porcentaje}%';document.getElementById('progressBar').textContent='{$porcentaje}%';</script>";
    if (ob_get_level()) ob_flush();
    flush();

    try {
        if ($dryRun) {
            // Modo simulación
            $etiquetasPendientes = Etiqueta::where('planilla_id', $planilla->id)
                ->where('estado', '!=', 'completada')
                ->count();
            $ordenes = OrdenPlanilla::where('planilla_id', $planilla->id)->count();

            $stats['etiquetas_actualizadas'] += $etiquetasPendientes;
            $stats['ordenes_eliminadas'] += $ordenes;
            $stats['planillas_ok']++;
        } else {
            // Ejecución real
            DB::transaction(function () use ($planilla, &$stats) {
                // 1. Obtener subetiquetas únicas
                $subetiquetas = Etiqueta::where('planilla_id', $planilla->id)
                    ->whereNotNull('etiqueta_sub_id')
                    ->distinct()
                    ->pluck('etiqueta_sub_id');

                foreach ($subetiquetas as $subId) {
                    $etiquetas = Etiqueta::where('etiqueta_sub_id', $subId)->get();
                    if ($etiquetas->isEmpty()) continue;

                    // Verificar si ya tienen paquete
                    $paqueteId = $etiquetas->whereNotNull('paquete_id')->pluck('paquete_id')->first();

                    if (!$paqueteId) {
                        $paquete = Paquete::crearConCodigoUnico([
                            'planilla_id' => $planilla->id,
                            'peso'        => $etiquetas->sum('peso'),
                            'estado'      => 'pendiente',
                        ]);
                        $paqueteId = $paquete->id;
                        $stats['paquetes_creados']++;
                    }

                    // Actualizar etiquetas
                    $updated = Etiqueta::where('etiqueta_sub_id', $subId)
                        ->where('estado', '!=', 'completada')
                        ->update([
                            'estado'     => 'completada',
                            'paquete_id' => $paqueteId,
                        ]);
                    $stats['etiquetas_actualizadas'] += $updated;
                }

                // 2. Actualizar etiquetas sin subetiqueta
                $updated = Etiqueta::where('planilla_id', $planilla->id)
                    ->whereNull('etiqueta_sub_id')
                    ->where('estado', '!=', 'completada')
                    ->update(['estado' => 'completada']);
                $stats['etiquetas_actualizadas'] += $updated;

                // 3. Eliminar de orden_planillas
                $maquinasAfectadas = OrdenPlanilla::where('planilla_id', $planilla->id)
                    ->pluck('maquina_id')
                    ->unique()
                    ->toArray();

                $deleted = OrdenPlanilla::where('planilla_id', $planilla->id)->delete();
                $stats['ordenes_eliminadas'] += $deleted;

                // 4. Reindexar posiciones
                foreach ($maquinasAfectadas as $maquinaId) {
                    $ordenes = OrdenPlanilla::where('maquina_id', $maquinaId)
                        ->orderBy('posicion')
                        ->get();
                    foreach ($ordenes as $index => $orden) {
                        if ($orden->posicion !== $index + 1) {
                            $orden->update(['posicion' => $index + 1]);
                        }
                    }
                }

                // 5. Marcar planilla como completada
                if ($planilla->estado !== 'completada') {
                    $planilla->update(['estado' => 'completada']);
                }
            });

            $stats['planillas_ok']++;
        }

        // Log cada 100 planillas
        if ($procesadas % 100 === 0) {
            output("✓ Procesadas: {$procesadas}/{$total}", 'success');
        }

    } catch (\Throwable $e) {
        $stats['planillas_fail']++;
        if (count($errores) < 10) {
            $errores[] = "Planilla {$planilla->id}: " . $e->getMessage();
        }
    }
}

output("", 'info');
output("═══════════════════════════════════════", 'info');
output("✅ PROCESO COMPLETADO", 'success');
output("═══════════════════════════════════════", 'info');

?>
    </div>

    <div class="stats">
        <h3>📊 Estadísticas</h3>
        <p>✅ Planillas procesadas: <strong><?= $stats['planillas_ok'] ?></strong></p>
        <p>❌ Planillas fallidas: <strong><?= $stats['planillas_fail'] ?></strong></p>
        <p>📝 Etiquetas actualizadas: <strong><?= number_format($stats['etiquetas_actualizadas']) ?></strong></p>
        <p>🗑️ Órdenes eliminadas: <strong><?= number_format($stats['ordenes_eliminadas']) ?></strong></p>
        <p>📦 Paquetes creados: <strong><?= number_format($stats['paquetes_creados']) ?></strong></p>

        <?php if (!empty($errores)): ?>
        <h4 style="color: #dc3545;">Primeros errores:</h4>
        <ul>
            <?php foreach ($errores as $error): ?>
            <li style="font-size: 12px;"><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
