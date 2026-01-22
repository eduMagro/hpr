<?php

/**
 * Script para corregir clientes y obras duplicadas por normalización de códigos.
 *
 * Problema: La importación manual normalizaba códigos ("0042" -> "42"),
 * pero Ferrawin busca por "0042" exacto y crea duplicados.
 *
 * Solución:
 * 1. Identificar pares duplicados (normalizado vs con ceros)
 * 2. Migrar todas las referencias de la duplicada a la original
 * 3. Eliminar los registros duplicados
 *
 * USO:
 *   php fix_duplicados.php --analizar    (solo muestra duplicados)
 *   php fix_duplicados.php --ejecutar    (corrige los datos)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Obra;
use App\Models\Cliente;

// Tablas que tienen cliente_id (referencia a clientes)
$tablasConClienteId = [
    'obras',
    'planillas',
    'salida_cliente',
];

// Tablas que tienen obra_id
$tablasConObraId = [
    'asignaciones_turnos',
    'eventos_ficticios_obra',
    'maquinas',
    'pedido_productos',
    'planillas',
    'productos',
    'salida_cliente',
    'salidas',
];

$modo = $argv[1] ?? '--analizar';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  CORRECCIÓN DE DUPLICADOS POR NORMALIZACIÓN DE CÓDIGOS        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// PARTE 1: ANÁLISIS DE CLIENTES
// ============================================================================

echo "┌────────────────────────────────────────────────────────────────┐\n";
echo "│  ANÁLISIS DE CLIENTES DUPLICADOS                              │\n";
echo "└────────────────────────────────────────────────────────────────┘\n\n";

$clientesConCeros = Cliente::whereRaw("codigo REGEXP '^0+[0-9]+'")->get();
echo "📊 Clientes con ceros a la izquierda: " . $clientesConCeros->count() . "\n";

$duplicadosClientes = [];
foreach ($clientesConCeros as $clienteConCeros) {
    $codNormalizado = ltrim($clienteConCeros->codigo, '0');
    $clienteOriginal = Cliente::where('codigo', $codNormalizado)->first();

    if ($clienteOriginal && $clienteOriginal->id !== $clienteConCeros->id) {
        $duplicadosClientes[] = [
            'original' => $clienteOriginal,
            'duplicada' => $clienteConCeros,
        ];
    }
}

echo "🔍 Pares duplicados de clientes: " . count($duplicadosClientes) . "\n\n";

if (!empty($duplicadosClientes)) {
    foreach ($duplicadosClientes as $i => $par) {
        $original = $par['original'];
        $duplicada = $par['duplicada'];

        echo "┌─ CLIENTE #" . ($i + 1) . " " . str_repeat("─", 53) . "\n";
        echo "│  ORIGINAL:  ID={$original->id}, codigo='{$original->codigo}', empresa='{$original->empresa}'\n";
        echo "│  DUPLICADO: ID={$duplicada->id}, codigo='{$duplicada->codigo}', empresa='{$duplicada->empresa}'\n";

        // Contar referencias
        $totalRef = 0;
        echo "│  Referencias a migrar:\n";
        foreach ($tablasConClienteId as $tabla) {
            try {
                $count = DB::table($tabla)->where('cliente_id', $duplicada->id)->count();
                if ($count > 0) {
                    echo "│    • {$tabla}: {$count}\n";
                    $totalRef += $count;
                }
            } catch (\Exception $e) {}
        }
        if ($totalRef === 0) echo "│    (ninguna)\n";
        echo "└" . str_repeat("─", 65) . "\n\n";
    }
}

// ============================================================================
// PARTE 2: ANÁLISIS DE OBRAS
// ============================================================================

echo "\n";
echo "┌────────────────────────────────────────────────────────────────┐\n";
echo "│  ANÁLISIS DE OBRAS DUPLICADAS                                 │\n";
echo "└────────────────────────────────────────────────────────────────┘\n\n";

$obrasConCeros = Obra::whereRaw("cod_obra REGEXP '^0+[0-9]+'")->withTrashed()->get();
echo "📊 Obras con ceros a la izquierda: " . $obrasConCeros->count() . "\n";

$duplicadosObras = [];
foreach ($obrasConCeros as $obraConCeros) {
    $codNormalizado = ltrim($obraConCeros->cod_obra, '0');
    $obraOriginal = Obra::where('cod_obra', $codNormalizado)->withTrashed()->first();

    if ($obraOriginal && $obraOriginal->id !== $obraConCeros->id) {
        $duplicadosObras[] = [
            'original' => $obraOriginal,
            'duplicada' => $obraConCeros,
        ];
    }
}

echo "🔍 Pares duplicados de obras: " . count($duplicadosObras) . "\n\n";

if (!empty($duplicadosObras)) {
    foreach ($duplicadosObras as $i => $par) {
        $original = $par['original'];
        $duplicada = $par['duplicada'];

        echo "┌─ OBRA #" . ($i + 1) . " " . str_repeat("─", 56) . "\n";
        echo "│  ORIGINAL:  ID={$original->id}, cod_obra='{$original->cod_obra}', nombre='{$original->obra}'\n";
        echo "│  DUPLICADA: ID={$duplicada->id}, cod_obra='{$duplicada->cod_obra}', nombre='{$duplicada->obra}'\n";

        // Contar referencias
        $totalRef = 0;
        echo "│  Referencias a migrar:\n";
        foreach ($tablasConObraId as $tabla) {
            try {
                $count = DB::table($tabla)->where('obra_id', $duplicada->id)->count();
                if ($count > 0) {
                    echo "│    • {$tabla}: {$count}\n";
                    $totalRef += $count;
                }
            } catch (\Exception $e) {}
        }
        if ($totalRef === 0) echo "│    (ninguna)\n";
        echo "└" . str_repeat("─", 65) . "\n\n";
    }
}

// ============================================================================
// RESUMEN
// ============================================================================

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN                                                       ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";
printf("║  Clientes duplicados a corregir: %-28s ║\n", count($duplicadosClientes));
printf("║  Obras duplicadas a corregir:    %-28s ║\n", count($duplicadosObras));
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

if (empty($duplicadosClientes) && empty($duplicadosObras)) {
    echo "✅ No hay duplicados que corregir.\n\n";
    exit(0);
}

// ============================================================================
// MODO ANÁLISIS - TERMINAR AQUÍ
// ============================================================================

if ($modo === '--analizar') {
    echo "ℹ️  Modo ANÁLISIS - No se han realizado cambios.\n";
    echo "   Para ejecutar la corrección: php fix_duplicados.php --ejecutar\n\n";
    exit(0);
}

// ============================================================================
// MODO EJECUTAR
// ============================================================================

if ($modo !== '--ejecutar') {
    echo "❌ Modo no reconocido: {$modo}\n";
    echo "   Usa --analizar o --ejecutar\n\n";
    exit(1);
}

echo "⚠️  MODO EJECUCIÓN\n";
echo "   Se van a migrar las referencias y eliminar duplicados.\n";
echo "   ¿Continuar? (escribe 'SI' para confirmar): ";

$confirmacion = trim(fgets(STDIN));
if ($confirmacion !== 'SI') {
    echo "\n❌ Operación cancelada.\n\n";
    exit(0);
}

echo "\n🔄 Iniciando migración...\n\n";

DB::beginTransaction();

try {
    // MIGRAR CLIENTES
    if (!empty($duplicadosClientes)) {
        echo "═══ MIGRANDO CLIENTES ═══\n\n";

        foreach ($duplicadosClientes as $i => $par) {
            $original = $par['original'];
            $duplicada = $par['duplicada'];

            echo "Cliente #" . ($i + 1) . ": '{$duplicada->empresa}' (ID {$duplicada->id} → {$original->id})\n";

            foreach ($tablasConClienteId as $tabla) {
                try {
                    $updated = DB::table($tabla)
                        ->where('cliente_id', $duplicada->id)
                        ->update(['cliente_id' => $original->id]);

                    if ($updated > 0) {
                        echo "  ✓ {$tabla}: {$updated} registros\n";
                    }
                } catch (\Exception $e) {
                    echo "  ⚠ {$tabla}: " . $e->getMessage() . "\n";
                }
            }

            $duplicada->forceDelete();
            echo "  🗑️ Cliente eliminado\n\n";
        }
    }

    // MIGRAR OBRAS
    if (!empty($duplicadosObras)) {
        echo "═══ MIGRANDO OBRAS ═══\n\n";

        foreach ($duplicadosObras as $i => $par) {
            $original = $par['original'];
            $duplicada = $par['duplicada'];

            echo "Obra #" . ($i + 1) . ": '{$duplicada->obra}' (ID {$duplicada->id} → {$original->id})\n";

            foreach ($tablasConObraId as $tabla) {
                try {
                    $updated = DB::table($tabla)
                        ->where('obra_id', $duplicada->id)
                        ->update(['obra_id' => $original->id]);

                    if ($updated > 0) {
                        echo "  ✓ {$tabla}: {$updated} registros\n";
                    }
                } catch (\Exception $e) {
                    echo "  ⚠ {$tabla}: " . $e->getMessage() . "\n";
                }
            }

            $duplicada->forceDelete();
            echo "  🗑️ Obra eliminada\n\n";
        }
    }

    DB::commit();

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ MIGRACIÓN COMPLETADA EXITOSAMENTE                         ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    echo "Resumen:\n";
    echo "  • Clientes migrados y eliminados: " . count($duplicadosClientes) . "\n";
    echo "  • Obras migradas y eliminadas: " . count($duplicadosObras) . "\n\n";

} catch (\Exception $e) {
    DB::rollBack();

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ ERROR - SE HA REVERTIDO LA OPERACIÓN                      ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}
