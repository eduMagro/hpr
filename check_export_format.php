<?php
/**
 * Investigar cómo se genera el formato @tBARRA para dibujos
 * Comparando con los datos que tenemos en ORD_BAR y ORD_DET
 */

require 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use FerrawinSync\Database;
use FerrawinSync\Config;

Config::load();
$pdo = Database::getConnection();

echo "=== Analizando datos para reconstruir coordenadas de dibujo ===\n\n";

$zconta = '2025';
$zcodigo = '008437';

// Obtener una entidad de ensamblaje (con múltiples barras y estribos)
$sql = "
    SELECT od.ZCODLIN, od.ZMARCA, od.ZSITUACION, od.ZCANTIDAD as cant_entidad, od.ZMEMBERS
    FROM ORD_DET od
    WHERE od.ZCONTA = ? AND od.ZCODIGO = ?
    ORDER BY od.ZCODLIN
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$zconta, $zcodigo]);

while ($entidad = $stmt->fetch()) {
    echo "=== Entidad Linea {$entidad->ZCODLIN} ===\n";
    echo "MARCA: " . trim($entidad->ZMARCA) . " | SITUACION: " . trim($entidad->ZSITUACION) . "\n";
    echo "CANTIDAD: {$entidad->cant_entidad} | MEMBERS: {$entidad->ZMEMBERS}\n\n";

    // Obtener todos los elementos (barras/estribos) de esta entidad
    $sqlBars = "
        SELECT
            ZELEMENTO,
            ZMARCA,
            ZCANTIDAD,
            ZDIAMETRO,
            ZLONGTESTD,
            ZNUMBEND,
            ZFIGURA,
            ZPESOTESTD,
            ZSTRBENT,
            ZOBJETO
        FROM ORD_BAR
        WHERE ZCONTA = ? AND ZCODIGO = ? AND ZCODLIN = ?
        ORDER BY ZELEMENTO
    ";
    $stmtB = $pdo->prepare($sqlBars);
    $stmtB->execute([$zconta, $zcodigo, $entidad->ZCODLIN]);

    $barras = [];
    $estribos = [];

    while ($bar = $stmtB->fetch()) {
        $tipo = (int)$bar->ZNUMBEND > 0 ? 'ESTRIBO' : 'BARRA';
        $elemento = [
            'elemento' => trim($bar->ZELEMENTO),
            'marca' => trim($bar->ZMARCA),
            'cantidad' => (int)$bar->ZCANTIDAD,
            'diametro' => (int)$bar->ZDIAMETRO,
            'longitud' => (float)$bar->ZLONGTESTD,
            'dobleces' => (int)$bar->ZNUMBEND,
            'figura' => trim($bar->ZFIGURA),
            'peso' => (float)$bar->ZPESOTESTD,
        ];

        // Intentar extraer coordenadas del ZOBJETO
        $obj = $bar->ZOBJETO;
        // Buscar patrones como H=xxx G=yyy
        if (preg_match('/H[^\d]*(\d+)/i', $obj, $m)) {
            $elemento['obj_h'] = $m[1];
        }
        if (preg_match('/G[^\d]*(\d+)/i', $obj, $m)) {
            $elemento['obj_g'] = $m[1];
        }

        if ($tipo === 'BARRA') {
            $barras[] = $elemento;
        } else {
            $estribos[] = $elemento;
        }
    }

    // Mostrar barras
    if (!empty($barras)) {
        echo "BARRAS (" . count($barras) . "):\n";
        foreach ($barras as $b) {
            echo "  #{$b['elemento']}: {$b['cantidad']}x Ø{$b['diametro']}, L={$b['longitud']}mm";
            if (!empty($b['figura'])) echo ", Fig: {$b['figura']}";
            if (isset($b['obj_h'])) echo ", H={$b['obj_h']}";
            if (isset($b['obj_g'])) echo ", G={$b['obj_g']}";
            echo "\n";
        }
    }

    // Mostrar estribos
    if (!empty($estribos)) {
        echo "ESTRIBOS (" . count($estribos) . "):\n";
        foreach ($estribos as $e) {
            echo "  #{$e['elemento']}: {$e['cantidad']}x Ø{$e['diametro']}, L={$e['longitud']}mm, dobleces={$e['dobleces']}";
            if (!empty($e['figura'])) echo ", Fig: {$e['figura']}";
            if (isset($e['obj_h'])) echo ", H={$e['obj_h']}";
            if (isset($e['obj_g'])) echo ", G={$e['obj_g']}";
            echo "\n";
        }
    }

    echo "\n" . str_repeat('-', 60) . "\n\n";

    // Solo mostrar primeras 3 entidades
    static $count = 0;
    if (++$count >= 3) break;
}

// Ahora analizar ZOBJETO más detalladamente
echo "\n=== Análisis detallado de ZOBJETO (1 elemento) ===\n";
$sql = "SELECT TOP 1 ZELEMENTO, ZOBJETO FROM ORD_BAR WHERE ZCONTA = ? AND ZCODIGO = ? AND ZNUMBEND > 0";
$stmt = $pdo->prepare($sql);
$stmt->execute([$zconta, $zcodigo]);
$row = $stmt->fetch();

if ($row) {
    $obj = $row->ZOBJETO;
    echo "Elemento: {$row->ZELEMENTO}\n";
    echo "Longitud ZOBJETO: " . strlen($obj) . " bytes\n\n";

    // Decodificar caracteres especiales
    // É (195 137) parece ser separador de campo
    // Ê (195 138) parece ser separador de registro

    // Reemplazar caracteres especiales
    $decoded = str_replace(
        ["\xC3\x89", "\xC3\x8A", "\xC2\xB0", "\xC2\xB1", "\xC2\xB2", "\xC2\xB3"],
        ['=', '|', '°', '±', '²', '³'],
        $obj
    );

    echo "ZOBJETO decodificado:\n";
    $parts = explode('|', $decoded);
    foreach ($parts as $i => $part) {
        if (!empty(trim($part))) {
            echo "  Part $i: " . trim($part) . "\n";
        }
    }
}

// Buscar si existe una tabla con las coordenadas ya calculadas
echo "\n=== Buscando tablas de exportación/dibujo ===\n";
$sql = "SELECT name FROM sys.tables WHERE name LIKE '%EXPORT%' OR name LIKE '%DRAW%' OR name LIKE '%COORD%' OR name LIKE '%GRAPH%' ORDER BY name";
$stmt = $pdo->query($sql);
$found = false;
while ($row = $stmt->fetch()) {
    echo "  - {$row->name}\n";
    $found = true;
}
if (!$found) echo "  (ninguna encontrada)\n";
