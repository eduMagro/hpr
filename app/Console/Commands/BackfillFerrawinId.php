<?php

namespace App\Console\Commands;

use App\Models\Elemento;
use App\Models\Planilla;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando para rellenar ferrawin_id en elementos existentes.
 *
 * Genera ferrawin_id aproximados basándose en:
 * - fila (ZCODLIN): viene del campo 'fila' del elemento
 * - elemento (ZELEMENTO): se genera secuencialmente dentro de cada fila
 *
 * Formato: "{fila}-{secuencia}" (ej: "001-01", "001-02", "002-01")
 *
 * NOTA: Este backfill es aproximado. Los ferrawin_id generados pueden no
 * coincidir exactamente con los de FerraWin. En la siguiente sincronización,
 * los elementos pendientes que no coincidan se eliminarán y recrearán con
 * los ferrawin_id correctos de FerraWin.
 */
class BackfillFerrawinId extends Command
{
    protected $signature = 'ferrawin:backfill-id
                            {--dry-run : Simular sin hacer cambios}
                            {--planilla= : Solo procesar una planilla específica (código)}';

    protected $description = 'Rellena ferrawin_id aproximados para elementos existentes sin este campo';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $codigoPlanilla = $this->option('planilla');

        $this->info("=== Backfill de ferrawin_id ===");

        if ($dryRun) {
            $this->warn("MODO DRY-RUN: No se harán cambios");
        }

        // Query base
        $query = Elemento::whereNull('ferrawin_id');

        if ($codigoPlanilla) {
            $planilla = Planilla::where('codigo', $codigoPlanilla)->first();
            if (!$planilla) {
                $this->error("Planilla {$codigoPlanilla} no encontrada");
                return 1;
            }
            $query->where('planilla_id', $planilla->id);
            $this->info("Procesando solo planilla: {$codigoPlanilla}");
        }

        $totalSinFerrawinId = $query->count();
        $this->info("Elementos sin ferrawin_id: {$totalSinFerrawinId}");

        if ($totalSinFerrawinId === 0) {
            $this->info("No hay elementos que procesar");
            return 0;
        }

        // Agrupar por planilla_id
        $planillaIds = (clone $query)->distinct()->pluck('planilla_id');
        $this->info("Planillas a procesar: " . $planillaIds->count());

        $bar = $this->output->createProgressBar($planillaIds->count());
        $bar->start();

        $totalActualizados = 0;

        foreach ($planillaIds as $planillaId) {
            if (!$dryRun) {
                $actualizados = $this->procesarPlanilla($planillaId);
                $totalActualizados += $actualizados;
            } else {
                // En dry-run, solo contar
                $count = Elemento::where('planilla_id', $planillaId)
                    ->whereNull('ferrawin_id')
                    ->count();
                $totalActualizados += $count;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Elementos actualizados: {$totalActualizados}");

        if ($dryRun) {
            $this->warn("(Simulación - ejecutar sin --dry-run para aplicar cambios)");
        }

        return 0;
    }

    /**
     * Procesa una planilla y genera ferrawin_id para sus elementos.
     */
    protected function procesarPlanilla(int $planillaId): int
    {
        // Obtener elementos sin ferrawin_id agrupados por fila
        $elementos = Elemento::where('planilla_id', $planillaId)
            ->whereNull('ferrawin_id')
            ->orderBy('fila')
            ->orderBy('id')
            ->get();

        if ($elementos->isEmpty()) {
            return 0;
        }

        $actualizados = 0;
        $contadoresPorFila = [];

        DB::beginTransaction();

        try {
            foreach ($elementos as $elemento) {
                $fila = trim($elemento->fila ?? '');

                // Si no hay fila, usar 'X' como prefijo
                if (empty($fila)) {
                    $fila = 'X';
                }

                // Inicializar contador para esta fila
                if (!isset($contadoresPorFila[$fila])) {
                    $contadoresPorFila[$fila] = 0;
                }

                $contadoresPorFila[$fila]++;

                // Generar ferrawin_id: "{fila}-{secuencia}"
                $secuencia = str_pad($contadoresPorFila[$fila], 2, '0', STR_PAD_LEFT);
                $ferrawinId = "{$fila}-{$secuencia}";

                $elemento->ferrawin_id = $ferrawinId;
                $elemento->save();

                $actualizados++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error procesando planilla {$planillaId}: " . $e->getMessage());
            return 0;
        }

        return $actualizados;
    }
}
