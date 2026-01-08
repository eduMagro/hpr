<?php
/**
 * Verificar datos reales disponibles
 */

require 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use FerrawinSync\Database;
use FerrawinSync\Config;
use App\Models\Planilla;

Config::load();
$pdo = Database::getConnection();

echo "=== Planillas con elementos en FerraWin por año ===\n\n";

$sql = "
    SELECT
        oh.ZCONTA as año,
        COUNT(DISTINCT oh.ZCONTA + '-' + oh.ZCODIGO) as planillas_con_header,
        COUNT(DISTINCT ob.ZCONTA + '-' + ob.ZCODIGO) as planillas_con_elementos
    FROM ORD_HEAD oh
    LEFT JOIN ORD_BAR ob ON oh.ZCONTA = ob.ZCONTA AND oh.ZCODIGO = ob.ZCODIGO
    WHERE oh.ZCONTA != '    '
    GROUP BY oh.ZCONTA
    ORDER BY oh.ZCONTA
";

$stmt = $pdo->query($sql);
$total_con_elementos = 0;

while ($row = $stmt->fetch()) {
    echo "{$row->año}: {$row->planillas_con_header} headers, {$row->planillas_con_elementos} con elementos\n";
    $total_con_elementos += $row->planillas_con_elementos;
}

echo "\nTotal planillas con elementos: {$total_con_elementos}\n";

echo "\n=== Planillas ya importadas en Manager por año ===\n";

$porAño = Planilla::selectRaw('YEAR(fecha) as año, COUNT(*) as total')
    ->groupBy(\Illuminate\Support\Facades\DB::raw('YEAR(fecha)'))
    ->orderBy('año')
    ->get();

$totalManager = 0;
foreach ($porAño as $row) {
    echo "{$row->año}: {$row->total}\n";
    $totalManager += $row->total;
}

echo "\nTotal en Manager: {$totalManager}\n";

// Verificar planillas únicas con elementos
echo "\n=== Conteo exacto de planillas únicas con elementos ===\n";
$sql = "SELECT COUNT(DISTINCT ZCONTA + '-' + ZCODIGO) as total FROM ORD_BAR WHERE ZCONTA != '    '";
$stmt = $pdo->query($sql);
echo "Planillas únicas en ORD_BAR: " . $stmt->fetch()->total . "\n";
