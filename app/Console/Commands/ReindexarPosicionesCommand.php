<?php

namespace App\Console\Commands;

use App\Models\OrdenPlanilla;
use App\Models\Maquina;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReindexarPosicionesCommand extends Command
{
    protected $signature = 'orden-planillas:reindexar
                            {--maquina= : ID de máquina específica (opcional)}
                            {--dry-run : Solo mostrar qué se haría}';

    protected $description = 'Reindexar posiciones de orden_planillas para que sean consecutivas desde 1';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $maquinaId = $this->option('maquina');

        if ($dryRun) {
            $this->warn('Modo dry-run: no se haran cambios');
        }

        // Obtener IDs de máquinas que tienen ordenes
        $maquinaIds = OrdenPlanilla::select('maquina_id')
            ->distinct()
            ->pluck('maquina_id');

        if ($maquinaId) {
            $maquinaIds = $maquinaIds->filter(fn($id) => $id == $maquinaId);
        }

        $maquinas = Maquina::whereIn('id', $maquinaIds)->get()->keyBy('id');

        $this->info("Procesando {$maquinaIds->count()} maquinas...");

        $totalCorregidas = 0;

        foreach ($maquinaIds as $maqId) {
            $maquina = $maquinas->get($maqId);
            $nombreMaquina = $maquina ? "{$maquina->nombre} ({$maquina->codigo})" : "Maquina ID: {$maqId}";

            $ordenes = OrdenPlanilla::where('maquina_id', $maqId)
                ->orderBy('posicion')
                ->get();

            if ($ordenes->isEmpty()) continue;

            $primeraPos = $ordenes->first()->posicion;
            $ultimaPos = $ordenes->last()->posicion;
            $necesitaReindexar = $primeraPos != 1;

            // También verificar si hay huecos
            if (!$necesitaReindexar) {
                $posiciones = $ordenes->pluck('posicion')->toArray();
                for ($i = 0; $i < count($posiciones); $i++) {
                    if ($posiciones[$i] != $i + 1) {
                        $necesitaReindexar = true;
                        break;
                    }
                }
            }

            if (!$necesitaReindexar) {
                continue;
            }

            $this->line("  {$nombreMaquina}: {$ordenes->count()} planillas, posiciones {$primeraPos}-{$ultimaPos}");

            if (!$dryRun) {
                DB::transaction(function () use ($ordenes) {
                    $nuevaPos = 1;
                    foreach ($ordenes as $orden) {
                        if ($orden->posicion != $nuevaPos) {
                            $orden->update(['posicion' => $nuevaPos]);
                        }
                        $nuevaPos++;
                    }
                });
                $this->info("     -> Reindexado: posiciones 1-{$ordenes->count()}");
            } else {
                $this->info("     -> Se reindexaria a posiciones 1-{$ordenes->count()}");
            }

            $totalCorregidas++;
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Se corregirian {$totalCorregidas} maquinas");
        } else {
            $this->info("{$totalCorregidas} maquinas reindexadas correctamente");
        }

        return 0;
    }
}
