<?php
/**
 * Script de prueba para importar una planilla y verificar dibujo_data
 */

require 'C:/xampp/htdocs/ferrawin-sync/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use FerrawinSync\Database;
use FerrawinSync\Config;
use FerrawinSync\FerrawinQuery;
use App\Services\FerrawinSync\FerrawinBulkImportService;
use App\Models\Planilla;
use App\Models\PlanillaEntidad;

Config::load();

echo "=== Probando importación con datos de dibujo ===\n\n";

// Obtener planillas recientes con ensamblaje
$codigos = FerrawinQuery::getCodigosPlanillas([
    'dias_atras' => 30,
    'limite' => 10,
]);

// Buscar una que no exista ya en la base de datos
$codigosExistentes = Planilla::whereIn('codigo', $codigos)->pluck('codigo')->toArray();
$codigosNuevos = array_diff($codigos, $codigosExistentes);

if (empty($codigosNuevos)) {
    echo "No hay planillas nuevas para importar. Verificando una existente...\n";

    // Verificar una planilla existente que ya tenga entidades
    $planillaConEntidades = Planilla::whereHas('entidades')->first();
    if ($planillaConEntidades) {
        $entidad = $planillaConEntidades->entidades()->first();
        echo "\nPlanilla: {$planillaConEntidades->codigo}\n";
        echo "Entidad: {$entidad->marca} - {$entidad->situacion}\n";
        echo "Dibujo Data: " . ($entidad->dibujo_data ? json_encode($entidad->dibujo_data, JSON_PRETTY_PRINT) : 'NULL') . "\n";
    }
    exit;
}

$codigoImportar = array_values($codigosNuevos)[0];
echo "Importando planilla: {$codigoImportar}\n\n";

// Obtener datos
$datos = FerrawinQuery::getDatosPlanilla($codigoImportar);
$planillaData = FerrawinQuery::formatearParaApiConEnsamblajes($datos, $codigoImportar);

// Mostrar datos de zobjeto encontrados
echo "=== Datos de ZOBJETO encontrados ===\n";
foreach ($planillaData['entidades'] ?? [] as $entidad) {
    echo "\nEntidad Linea {$entidad['linea']} - {$entidad['marca']}:\n";
    foreach ($entidad['composicion']['barras'] ?? [] as $i => $barra) {
        $tieneZobjeto = !empty($barra['zobjeto']);
        echo "  Barra {$i}: zobjeto=" . ($tieneZobjeto ? 'SI (' . strlen($barra['zobjeto']) . ' bytes)' : 'NO') . "\n";
    }
    foreach ($entidad['composicion']['estribos'] ?? [] as $i => $estribo) {
        $tieneZobjeto = !empty($estribo['zobjeto']);
        echo "  Estribo {$i}: zobjeto=" . ($tieneZobjeto ? 'SI (' . strlen($estribo['zobjeto']) . ' bytes)' : 'NO') . "\n";
    }
}

// Importar
echo "\n=== Importando planilla ===\n";
$service = app(FerrawinBulkImportService::class);

try {
    $resultado = $service->importar([$planillaData]);
    print_r($resultado);

    // Verificar dibujo_data
    echo "\n=== Verificando dibujo_data guardado ===\n";
    $planilla = Planilla::where('codigo', $codigoImportar)->first();
    if ($planilla) {
        $entidades = PlanillaEntidad::where('planilla_id', $planilla->id)->limit(2)->get();
        foreach ($entidades as $entidad) {
            echo "\nEntidad {$entidad->marca}:\n";
            if ($entidad->dibujo_data) {
                echo "  dibujo_data: " . json_encode($entidad->dibujo_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo "  dibujo_data: NULL\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
