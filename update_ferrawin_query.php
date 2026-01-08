<?php
/**
 * Script para actualizar FerrawinQuery.php y añadir ZOBJETO
 */

$filePath = 'C:/xampp/htdocs/ferrawin-sync/src/FerrawinQuery.php';
$content = file_get_contents($filePath);

// 1. Añadir ZOBJETO al SELECT de la consulta
$oldSelect = "ob.ZCODMODELO as figura
                FROM ORD_BAR ob";
$newSelect = "ob.ZCODMODELO as figura,
                    ob.ZOBJETO as zobjeto
                FROM ORD_BAR ob";

$content = str_replace($oldSelect, $newSelect, $content);

// 2. Añadir zobjeto al elementoFormateado (para barras y estribos)
$oldFormat = "'figura' => trim(\$elem->figura ?? ''),
                ];";
$newFormat = "'figura' => trim(\$elem->figura ?? ''),
                    'zobjeto' => \$elem->zobjeto ?? null,
                ];";

$content = str_replace($oldFormat, $newFormat, $content);

file_put_contents($filePath, $content);

echo "FerrawinQuery.php actualizado correctamente\n";

// Verificar cambios
$verifyContent = file_get_contents($filePath);
if (strpos($verifyContent, 'ob.ZOBJETO as zobjeto') !== false) {
    echo "✓ ZOBJETO añadido al SELECT\n";
} else {
    echo "✗ ZOBJETO NO encontrado en SELECT\n";
}

if (strpos($verifyContent, "'zobjeto' => \$elem->zobjeto") !== false) {
    echo "✓ zobjeto añadido al array\n";
} else {
    echo "✗ zobjeto NO encontrado en array\n";
}
