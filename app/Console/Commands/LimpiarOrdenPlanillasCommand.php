<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use Illuminate\Support\Facades\DB;

class LimpiarOrdenPlanillasCommand extends Command
{
    protected $signature = 'orden-planillas:limpiar
                            {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}';

    protected $description = 'Elimina planillas completadas de orden_planillas y reindexar posiciones';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '🔍 Modo dry-run: no se realizarán cambios' : '⚡ Modo ejecución: se aplicarán cambios');

        // Buscar registros de orden_planillas cuya planilla está completada
        $registrosAEliminar = OrdenPlanilla::whereHas('planilla', function ($query) {
            $query->where('estado', 'completada');
        })->get();

        $this->info("Registros a eliminar: " . $registrosAEliminar->count());

        if ($registrosAEliminar->isEmpty()) {
            $this->info("✓ No hay registros para limpiar");
            return 0;
        }

        // Obtener máquinas afectadas antes de eliminar
        $maquinasAfectadas = $registrosAEliminar->pluck('maquina_id')->unique()->toArray();
        $this->info("Máquinas afectadas: " . count($maquinasAfectadas));

        if ($dryRun) {
            $this->info("\nDetalle de lo que se eliminaría:");
            $porMaquina = $registrosAEliminar->groupBy('maquina_id');
            foreach ($porMaquina as $maquinaId => $registros) {
                $this->line("  - Máquina ID $maquinaId: {$registros->count()} registros");
            }
            $this->warn("\nModo dry-run: no se realizaron cambios.");
            return 0;
        }

        // Eliminar registros
        $this->info("\n📋 Eliminando registros...");
        $eliminados = OrdenPlanilla::whereHas('planilla', function ($query) {
            $query->where('estado', 'completada');
        })->delete();

        $this->info("✓ Eliminados: $eliminados registros");

        // Reindexar posiciones por máquina
        $this->info("\n📋 Reindexando posiciones...");
        $bar = $this->output->createProgressBar(count($maquinasAfectadas));
        $bar->start();

        foreach ($maquinasAfectadas as $maquinaId) {
            $this->reindexarPosicionesMaquina($maquinaId);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Posiciones reindexadas en " . count($maquinasAfectadas) . " máquinas");
        $this->info("\n✅ Limpieza completada");

        return 0;
    }

    /**
     * Reindexar posiciones de órdenes de una máquina por fecha de entrega
     */
    protected function reindexarPosicionesMaquina(int $maquinaId): void
    {
        $ordenes = OrdenPlanilla::where('orden_planillas.maquina_id', $maquinaId)
            ->join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
            ->orderBy('planillas.fecha_estimada_entrega', 'asc')
            ->orderBy('planillas.id', 'asc')
            ->select('orden_planillas.*')
            ->get();

        foreach ($ordenes as $index => $orden) {
            if ($orden->posicion !== $index) {
                $orden->posicion = $index;
                $orden->save();
            }
        }
    }
}
