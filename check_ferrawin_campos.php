<?php
/**
 * Comparar datos de FerraWin vs lo que guardamos en Manager
 */

require 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use FerrawinSync\Database;
use FerrawinSync\Config;
use App\Models\PlanillaEntidad;
use App\Models\Planilla;

Config::load();
$pdo = Database::getConnection();

echo "=== Comparando datos FerraWin vs Manager ===\n\n";

// Tomar una planilla que tengamos importada con ensamblaje
$planilla = Planilla::where('codigo', '2025-008437')->first();

if (!$planilla) {
    echo "Planilla no encontrada en Manager\n";
    exit;
}

echo "Planilla: {$planilla->codigo}\n\n";

// Datos en FerraWin
[$zconta, $zcodigo] = explode('-', $planilla->codigo);

echo "=== DATOS EN FERRAWIN (ORD_DET + ORD_BAR) ===\n";
$sql = "
    SELECT TOP 3
        od.ZCODLIN as linea,
        od.ZMARCA as marca,
        od.ZSITUACION as situacion,
        od.ZCANTIDAD as cantidad,
        od.ZMEMBERS as miembros
    FROM ORD_DET od
    WHERE od.ZCONTA = ? AND od.ZCODIGO = ?
    ORDER BY od.ZCODLIN
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$zconta, $zcodigo]);

while ($entidad = $stmt->fetch()) {
    echo "\nEntidad Linea {$entidad->linea}:\n";
    echo "  MARCA: " . trim($entidad->marca) . "\n";
    echo "  SITUACION: " . trim($entidad->situacion) . "\n";
    echo "  CANTIDAD: {$entidad->cantidad}\n";

    // Obtener barras de esta entidad
    $sqlBarras = "
        SELECT ZELEMENTO, ZCANTIDAD, ZDIAMETRO, ZLONGTESTD, ZNUMBEND, ZFIGURA, ZPESOTESTD
        FROM ORD_BAR
        WHERE ZCONTA = ? AND ZCODIGO = ? AND ZCODLIN = ?
        ORDER BY ZELEMENTO
    ";
    $stmtB = $pdo->prepare($sqlBarras);
    $stmtB->execute([$zconta, $zcodigo, $entidad->linea]);

    echo "  Elementos:\n";
    while ($bar = $stmtB->fetch()) {
        $tipo = (int)$bar->ZNUMBEND > 0 ? 'ESTRIBO' : 'BARRA';
        echo "    {$tipo}: {$bar->ZCANTIDAD}x Ø{$bar->ZDIAMETRO}, L={$bar->ZLONGTESTD}mm, dobleces={$bar->ZNUMBEND}\n";
        if (!empty(trim($bar->ZFIGURA))) {
            echo "      Dimensiones: " . trim($bar->ZFIGURA) . "\n";
        }
    }
}

echo "\n\n=== DATOS EN MANAGER (planilla_entidades) ===\n";
$entidadesManager = $planilla->entidades()->limit(3)->get();

foreach ($entidadesManager as $ent) {
    echo "\nEntidad Linea {$ent->linea}:\n";
    echo "  MARCA: {$ent->marca}\n";
    echo "  SITUACION: {$ent->situacion}\n";
    echo "  CANTIDAD: {$ent->cantidad}\n";
    echo "  COTAS: {$ent->cotas}\n";
    echo "  LONGITUD_ENSAMBLAJE: {$ent->longitud_ensamblaje}mm\n";

    $comp = $ent->composicion;
    if (!empty($comp['barras'])) {
        echo "  Barras (" . count($comp['barras']) . "):\n";
        foreach (array_slice($comp['barras'], 0, 3) as $b) {
            echo "    {$b['cantidad']}x Ø{$b['diametro']}, L={$b['longitud']}mm\n";
        }
    }
    if (!empty($comp['estribos'])) {
        echo "  Estribos (" . count($comp['estribos']) . "):\n";
        foreach (array_slice($comp['estribos'], 0, 3) as $e) {
            echo "    {$e['cantidad']}x Ø{$e['diametro']}, L={$e['longitud']}mm, dobleces=" . ($e['dobleces'] ?? 0) . "\n";
        }
    }
}

exit;

// Obtener una planilla con datos de ensamblaje
$sql = "
    SELECT TOP 1 oh.ZCONTA, oh.ZCODIGO
    FROM ORD_HEAD oh
    INNER JOIN PROD_DETO pd ON oh.ZCONTA = pd.ZCONTA AND oh.ZCODIGO = pd.ZCODPLA
    WHERE oh.ZFECHA >= DATEADD(month, -3, GETDATE())
    ORDER BY oh.ZFECHA DESC
";
$stmt = $pdo->query($sql);
$planilla = $stmt->fetch();

if (!$planilla) {
    echo "No se encontraron planillas con ensamblaje\n";
    exit;
}

$zconta = $planilla->ZCONTA;
$zcodigo = $planilla->ZCODIGO;
echo "Planilla: {$zconta}-{$zcodigo}\n\n";

// Ver columnas de PROD_DETI
echo "=== Columnas de PROD_DETI ===\n";
$stmt = $pdo->query("SELECT TOP 1 * FROM PROD_DETI WHERE ZCONTA = '{$zconta}' AND ZCODPLA = '{$zcodigo}'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    foreach ($row as $col => $val) {
        $preview = is_string($val) ? substr($val, 0, 100) : $val;
        if (strlen($val) > 100) $preview .= '...';
        echo "  {$col}: {$preview}\n";
    }
}

echo "\n=== Columnas de PROD_DETO ===\n";
$stmt = $pdo->query("SELECT TOP 1 * FROM PROD_DETO WHERE ZCONTA = '{$zconta}' AND ZCODPLA = '{$zcodigo}'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    foreach ($row as $col => $val) {
        $preview = is_string($val) ? substr($val, 0, 100) : $val;
        if (strlen($val) > 100) $preview .= '...';
        echo "  {$col}: {$preview}\n";
    }
}

// Buscar campos que contengan @t (el formato de instrucciones)
echo "\n=== Buscando campos con formato @t ===\n";

// En ORD_DET
$stmt = $pdo->query("SELECT TOP 1 * FROM ORD_DET WHERE ZCONTA = '{$zconta}' AND ZCODIGO = '{$zcodigo}'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    foreach ($row as $col => $val) {
        if (is_string($val) && strpos($val, '@t') !== false) {
            echo "  ORD_DET.{$col}: {$val}\n";
        }
    }
}

// En PROD_DETI
$stmt = $pdo->query("SELECT * FROM PROD_DETI WHERE ZCONTA = '{$zconta}' AND ZCODPLA = '{$zcodigo}'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    foreach ($row as $col => $val) {
        if (is_string($val) && strpos($val, '@t') !== false) {
            echo "  PROD_DETI.{$col} (linea {$row['ZCODLIN']}): " . substr($val, 0, 200) . "...\n";
        }
    }
}

// Buscar otras tablas de producción
echo "\n=== Otras tablas de producción ===\n";
$stmt = $pdo->query("SELECT name FROM sys.tables WHERE name LIKE '%PROD%' OR name LIKE '%ENS%' ORDER BY name");
while ($row = $stmt->fetch()) {
    echo "  - {$row->name}\n";
}
