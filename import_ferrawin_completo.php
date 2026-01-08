<?php
require 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use FerrawinSync\Database;
use FerrawinSync\FerrawinQuery;
use FerrawinSync\Config;
use App\Services\FerrawinSync\FerrawinBulkImportService;

$cantidad = (int)($argv[1] ?? 5);

echo "=== Importando {$cantidad} planillas con ensamblaje desde FerraWin ===\n\n";

try {
    Config::load();
    $pdo = Database::getConnection();
    echo "[OK] Conectado a FerraWin\n\n";

    // Buscar planillas con ensamblaje, excluyendo las ya importadas
    $sql = "
        SELECT DISTINCT TOP ({$cantidad})
            oh.ZCONTA + '-' + oh.ZCODIGO as codigo
        FROM ORD_HEAD oh
        INNER JOIN PROD_DETO pd ON oh.ZCONTA = pd.ZCONTA AND oh.ZCODIGO = pd.ZCODPLA
        WHERE oh.ZFECHA >= DATEADD(year, -1, GETDATE())
        AND oh.ZCONTA + '-' + oh.ZCODIGO NOT IN ('2025-008693', '2025-008690', '2025-008660', '2025-008658', '2025-008654')
        ORDER BY codigo DESC
    ";

    $stmt = $pdo->query($sql);
    $codigos = [];
    while ($row = $stmt->fetch()) {
        $codigos[] = $row->codigo;
    }

    if (empty($codigos)) {
        echo "[!] No se encontraron más planillas\n";
        exit(1);
    }

    echo "Planillas a importar:\n";
    foreach ($codigos as $c) echo "  - {$c}\n";
    echo "\n";

    $planillasData = [];
    foreach ($codigos as $codigo) {
        echo "Procesando {$codigo}... ";
        $datos = FerrawinQuery::getDatosPlanilla($codigo);
        if (empty($datos)) { echo "sin datos\n"; continue; }

        $formateada = FerrawinQuery::formatearParaApiConEnsamblajes($datos, $codigo);
        if (!empty($formateada)) {
            $planillasData[] = $formateada;
            echo count($formateada['elementos'] ?? []) . " elementos\n";
        }
    }

    echo "\nImportando...\n";
    $resultado = app(FerrawinBulkImportService::class)->importar($planillasData);

    echo "\n=== Completado ===\n";
    echo "Planillas: {$resultado['planillas_creadas']}, Elementos: {$resultado['elementos_creados']}, Entidades: {$resultado['entidades_creadas']}\n";

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
