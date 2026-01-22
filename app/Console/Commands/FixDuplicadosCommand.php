<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Obra;
use App\Models\Cliente;

class FixDuplicadosCommand extends Command
{
    protected $signature = 'fix:duplicados {--ejecutar : Ejecutar la corrección (sin este flag solo analiza)}';

    protected $description = 'Corrige clientes y obras duplicadas por normalización de códigos (ceros a la izquierda)';

    protected $tablasConClienteId = [
        'obras',
        'planillas',
        'salida_cliente',
    ];

    protected $tablasConObraId = [
        'asignaciones_turnos',
        'eventos_ficticios_obra',
        'maquinas',
        'pedido_productos',
        'planillas',
        'productos',
        'salida_cliente',
        'salidas',
    ];

    public function handle()
    {
        $ejecutar = $this->option('ejecutar');

        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║  CORRECCIÓN DE DUPLICADOS POR NORMALIZACIÓN DE CÓDIGOS        ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Analizar clientes
        $duplicadosClientes = $this->analizarClientes();

        // Analizar obras
        $duplicadosObras = $this->analizarObras();

        // Resumen
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║  RESUMEN                                                       ║');
        $this->info('╠════════════════════════════════════════════════════════════════╣');
        $this->info(sprintf('║  Clientes duplicados a corregir: %-28s ║', count($duplicadosClientes)));
        $this->info(sprintf('║  Obras duplicadas a corregir:    %-28s ║', count($duplicadosObras)));
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->info('');

        if (empty($duplicadosClientes) && empty($duplicadosObras)) {
            $this->info('✅ No hay duplicados que corregir.');
            return 0;
        }

        if (!$ejecutar) {
            $this->warn('ℹ️  Modo ANÁLISIS - No se han realizado cambios.');
            $this->warn('   Para ejecutar: php artisan fix:duplicados --ejecutar');
            return 0;
        }

        // Confirmar ejecución
        if (!$this->confirm('⚠️  ¿Confirmas que deseas migrar las referencias y eliminar los duplicados?')) {
            $this->error('Operación cancelada.');
            return 1;
        }

        // Ejecutar migración
        $this->ejecutarMigracion($duplicadosClientes, $duplicadosObras);

        return 0;
    }

    protected function analizarClientes(): array
    {
        $this->info('┌────────────────────────────────────────────────────────────────┐');
        $this->info('│  ANÁLISIS DE CLIENTES DUPLICADOS                              │');
        $this->info('└────────────────────────────────────────────────────────────────┘');
        $this->info('');

        $clientesConCeros = Cliente::whereRaw("codigo REGEXP '^0+[0-9]+'")->get();
        $this->info("📊 Clientes con ceros a la izquierda: " . $clientesConCeros->count());

        $duplicados = [];
        foreach ($clientesConCeros as $clienteConCeros) {
            $codNormalizado = ltrim($clienteConCeros->codigo, '0');
            $clienteOriginal = Cliente::where('codigo', $codNormalizado)->first();

            if ($clienteOriginal && $clienteOriginal->id !== $clienteConCeros->id) {
                $duplicados[] = [
                    'original' => $clienteOriginal,
                    'duplicada' => $clienteConCeros,
                ];
            }
        }

        $this->info("🔍 Pares duplicados de clientes: " . count($duplicados));
        $this->info('');

        foreach ($duplicados as $i => $par) {
            $original = $par['original'];
            $duplicada = $par['duplicada'];

            $this->line("┌─ CLIENTE #" . ($i + 1) . " " . str_repeat("─", 53));
            $this->line("│  ORIGINAL:  ID={$original->id}, codigo='{$original->codigo}', empresa='{$original->empresa}'");
            $this->line("│  DUPLICADO: ID={$duplicada->id}, codigo='{$duplicada->codigo}', empresa='{$duplicada->empresa}'");

            $totalRef = 0;
            $this->line("│  Referencias a migrar:");
            foreach ($this->tablasConClienteId as $tabla) {
                try {
                    $count = DB::table($tabla)->where('cliente_id', $duplicada->id)->count();
                    if ($count > 0) {
                        $this->line("│    • {$tabla}: {$count}");
                        $totalRef += $count;
                    }
                } catch (\Exception $e) {}
            }
            if ($totalRef === 0) $this->line("│    (ninguna)");
            $this->line("└" . str_repeat("─", 65));
            $this->line('');
        }

        return $duplicados;
    }

    protected function analizarObras(): array
    {
        $this->info('┌────────────────────────────────────────────────────────────────┐');
        $this->info('│  ANÁLISIS DE OBRAS DUPLICADAS                                 │');
        $this->info('└────────────────────────────────────────────────────────────────┘');
        $this->info('');

        $obrasConCeros = Obra::whereRaw("cod_obra REGEXP '^0+[0-9]+'")->withTrashed()->get();
        $this->info("📊 Obras con ceros a la izquierda: " . $obrasConCeros->count());

        $duplicados = [];
        foreach ($obrasConCeros as $obraConCeros) {
            $codNormalizado = ltrim($obraConCeros->cod_obra, '0');
            $obraOriginal = Obra::where('cod_obra', $codNormalizado)->withTrashed()->first();

            if ($obraOriginal && $obraOriginal->id !== $obraConCeros->id) {
                $duplicados[] = [
                    'original' => $obraOriginal,
                    'duplicada' => $obraConCeros,
                ];
            }
        }

        $this->info("🔍 Pares duplicados de obras: " . count($duplicados));
        $this->info('');

        foreach ($duplicados as $i => $par) {
            $original = $par['original'];
            $duplicada = $par['duplicada'];

            $this->line("┌─ OBRA #" . ($i + 1) . " " . str_repeat("─", 56));
            $this->line("│  ORIGINAL:  ID={$original->id}, cod_obra='{$original->cod_obra}', nombre='{$original->obra}'");
            $this->line("│  DUPLICADA: ID={$duplicada->id}, cod_obra='{$duplicada->cod_obra}', nombre='{$duplicada->obra}'");

            $totalRef = 0;
            $this->line("│  Referencias a migrar:");
            foreach ($this->tablasConObraId as $tabla) {
                try {
                    $count = DB::table($tabla)->where('obra_id', $duplicada->id)->count();
                    if ($count > 0) {
                        $this->line("│    • {$tabla}: {$count}");
                        $totalRef += $count;
                    }
                } catch (\Exception $e) {}
            }
            if ($totalRef === 0) $this->line("│    (ninguna)");
            $this->line("└" . str_repeat("─", 65));
            $this->line('');
        }

        return $duplicados;
    }

    protected function ejecutarMigracion(array $duplicadosClientes, array $duplicadosObras): void
    {
        $this->info('');
        $this->info('🔄 Iniciando migración...');
        $this->info('');

        DB::beginTransaction();

        try {
            // Migrar clientes
            if (!empty($duplicadosClientes)) {
                $this->info('═══ MIGRANDO CLIENTES ═══');
                $this->info('');

                foreach ($duplicadosClientes as $i => $par) {
                    $original = $par['original'];
                    $duplicada = $par['duplicada'];

                    $this->line("Cliente #" . ($i + 1) . ": '{$duplicada->empresa}' (ID {$duplicada->id} → {$original->id})");

                    foreach ($this->tablasConClienteId as $tabla) {
                        try {
                            $updated = DB::table($tabla)
                                ->where('cliente_id', $duplicada->id)
                                ->update(['cliente_id' => $original->id]);

                            if ($updated > 0) {
                                $this->info("  ✓ {$tabla}: {$updated} registros");
                            }
                        } catch (\Exception $e) {
                            $this->warn("  ⚠ {$tabla}: " . $e->getMessage());
                        }
                    }

                    $duplicada->forceDelete();
                    $this->info("  🗑️ Cliente eliminado");
                    $this->line('');
                }
            }

            // Migrar obras
            if (!empty($duplicadosObras)) {
                $this->info('═══ MIGRANDO OBRAS ═══');
                $this->info('');

                foreach ($duplicadosObras as $i => $par) {
                    $original = $par['original'];
                    $duplicada = $par['duplicada'];

                    $this->line("Obra #" . ($i + 1) . ": '{$duplicada->obra}' (ID {$duplicada->id} → {$original->id})");

                    foreach ($this->tablasConObraId as $tabla) {
                        try {
                            $updated = DB::table($tabla)
                                ->where('obra_id', $duplicada->id)
                                ->update(['obra_id' => $original->id]);

                            if ($updated > 0) {
                                $this->info("  ✓ {$tabla}: {$updated} registros");
                            }
                        } catch (\Exception $e) {
                            $this->warn("  ⚠ {$tabla}: " . $e->getMessage());
                        }
                    }

                    $duplicada->forceDelete();
                    $this->info("  🗑️ Obra eliminada");
                    $this->line('');
                }
            }

            DB::commit();

            $this->info('');
            $this->info('╔════════════════════════════════════════════════════════════════╗');
            $this->info('║  ✅ MIGRACIÓN COMPLETADA EXITOSAMENTE                         ║');
            $this->info('╚════════════════════════════════════════════════════════════════╝');
            $this->info('');
            $this->info("  • Clientes migrados y eliminados: " . count($duplicadosClientes));
            $this->info("  • Obras migradas y eliminadas: " . count($duplicadosObras));
            $this->info('');

        } catch (\Exception $e) {
            DB::rollBack();

            $this->error('');
            $this->error('╔════════════════════════════════════════════════════════════════╗');
            $this->error('║  ❌ ERROR - SE HA REVERTIDO LA OPERACIÓN                      ║');
            $this->error('╚════════════════════════════════════════════════════════════════╝');
            $this->error('');
            $this->error("Error: " . $e->getMessage());

            throw $e;
        }
    }
}
