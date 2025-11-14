<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Paquete;

echo "=== TEST DE CARGA DE PAQUETE CON DIMENSIONES ===\n\n";

// Buscar un paquete que tenga etiquetas y elementos
$paquete = Paquete::with(['etiquetas.elementos'])
    ->whereHas('etiquetas.elementos')
    ->first();

if (!$paquete) {
    echo "❌ No se encontró ningún paquete con elementos\n";
    exit;
}

echo "📦 Paquete ID: {$paquete->id}\n";
echo "📦 Paquete Código: {$paquete->codigo}\n";
echo "📦 Total Etiquetas: " . $paquete->etiquetas->count() . "\n\n";

foreach ($paquete->etiquetas as $etiqueta) {
    echo "🏷️ Etiqueta ID: {$etiqueta->id}\n";
    echo "🏷️ Etiqueta Nombre: {$etiqueta->nombre}\n";
    echo "🏷️ Total Elementos: " . $etiqueta->elementos->count() . "\n";

    foreach ($etiqueta->elementos as $elemento) {
        echo "  🔧 Elemento ID: {$elemento->id}\n";
        echo "  🔧 Código: {$elemento->codigo}\n";
        echo "  🔧 Dimensiones: " . ($elemento->dimensiones ?: 'NULL/VACÍO') . "\n";
        echo "  🔧 Tipo: " . gettype($elemento->dimensiones) . "\n";
        echo "  🔧 IsNull: " . (is_null($elemento->dimensiones) ? 'SÍ' : 'NO') . "\n";
        echo "  🔧 Empty: " . (empty($elemento->dimensiones) ? 'SÍ' : 'NO') . "\n";
        echo "\n";

        // Solo mostrar primeros 3 elementos
        if ($etiqueta->elementos->search($elemento) >= 2) {
            echo "  ... (mostrando solo primeros 3 elementos)\n\n";
            break;
        }
    }
}

echo "\n=== JSON ENCODE TEST ===\n";
$json = json_encode([
    'id' => $paquete->id,
    'etiquetas' => $paquete->etiquetas->map(function($e) {
        return [
            'id' => $e->id,
            'elementos' => $e->elementos->map(function($el) {
                return [
                    'id' => $el->id,
                    'dimensiones' => $el->dimensiones,
                ];
            })
        ];
    })
]);

echo $json . "\n";
