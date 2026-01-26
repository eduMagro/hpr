<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Paquete;
use App\Models\OrdenPlanilla;
use Illuminate\Support\Facades\DB;

class CompletarPlanillasInconsistentesCommand extends Command
{
    protected $signature = 'planillas:completar-inconsistentes
                            {--fecha-corte= : Fecha de corte (YYYY-MM-DD), por defecto hoy}
                            {--solo-completadas : Solo procesar planillas ya marcadas como completadas}
                            {--dry-run : Simular sin hacer cambios}';

    protected $description = 'Completa planillas con etiquetas pendientes y elimina sus orden_planillas';

    public function handle()
    {
        $fechaCorte = $this->option('fecha-corte')
            ? \Carbon\Carbon::parse($this->option('fecha-corte'))->endOfDay()
            : \Carbon\Carbon::today()->endOfDay();

        $soloCompletadas = $this->option('solo-completadas');
        $dryRun = $this->option('dry-run');

        $this->info("Fecha de corte: {$fechaCorte->format('Y-m-d')}");
        $this->info($dryRun ? '⚠️  MODO SIMULACIÓN - No se harán cambios' : '🔄 Ejecutando cambios reales');
        $this->newLine();

        // Obtener planillas candidatas
        $query = Planilla::query()
            ->whereNotNull('fecha_estimada_entrega')
            ->whereDate('fecha_estimada_entrega', '<=', $fechaCorte);

        if ($soloCompletadas) {
            $query->where('estado', 'completada');
        } else {
            $query->whereIn('estado', ['pendiente', 'fabricando', 'completada']);
        }

        $planillas = $query->get();

        $this->info("Planillas a procesar: {$planillas->count()}");
        $this->newLine();

        if ($planillas->isEmpty()) {
            $this->warn('No hay planillas para procesar.');
            return 0;
        }

        $bar = $this->output->createProgressBar($planillas->count());
        $bar->start();

        $stats = [
            'planillas_ok' => 0,
            'planillas_fail' => 0,
            'etiquetas_actualizadas' => 0,
            'ordenes_eliminadas' => 0,
            'paquetes_creados' => 0,
        ];

        foreach ($planillas as $planilla) {
            try {
                if (!$dryRun) {
                    DB::transaction(function () use ($planilla, &$stats) {
                        $this->procesarPlanilla($planilla, $stats);
                    });
                } else {
                    // En dry-run, solo contar
                    $etiquetasPendientes = Etiqueta::where('planilla_id', $planilla->id)
                        ->where('estado', '!=', 'completada')
                        ->count();
                    $ordenes = OrdenPlanilla::where('planilla_id', $planilla->id)->count();

                    if ($etiquetasPendientes > 0 || $ordenes > 0) {
                        $stats['etiquetas_actualizadas'] += $etiquetasPendientes;
                        $stats['ordenes_eliminadas'] += $ordenes;
                        $stats['planillas_ok']++;
                    }
                }

                $stats['planillas_ok']++;
            } catch (\Throwable $e) {
                $stats['planillas_fail']++;
                $this->newLine();
                $this->error("Error en planilla {$planilla->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Planillas procesadas: {$stats['planillas_ok']}");
        $this->info("❌ Planillas fallidas: {$stats['planillas_fail']}");
        $this->info("📝 Etiquetas actualizadas: {$stats['etiquetas_actualizadas']}");
        $this->info("🗑️  Órdenes eliminadas: {$stats['ordenes_eliminadas']}");
        $this->info("📦 Paquetes creados: {$stats['paquetes_creados']}");

        return 0;
    }

    private function procesarPlanilla(Planilla $planilla, array &$stats): void
    {
        // 1. Obtener subetiquetas únicas
        $subetiquetas = Etiqueta::where('planilla_id', $planilla->id)
            ->whereNotNull('etiqueta_sub_id')
            ->distinct()
            ->pluck('etiqueta_sub_id');

        foreach ($subetiquetas as $subId) {
            $etiquetas = Etiqueta::where('etiqueta_sub_id', $subId)->get();

            if ($etiquetas->isEmpty()) continue;

            // Verificar si ya tienen paquete
            $paqueteId = $etiquetas->whereNotNull('paquete_id')->pluck('paquete_id')->first();

            if (!$paqueteId) {
                // Crear paquete
                $paquete = Paquete::crearConCodigoUnico([
                    'planilla_id' => $planilla->id,
                    'peso'        => $etiquetas->sum('peso'),
                    'estado'      => 'pendiente',
                ]);
                $paqueteId = $paquete->id;
                $stats['paquetes_creados']++;
            }

            // Actualizar etiquetas
            $updated = Etiqueta::where('etiqueta_sub_id', $subId)
                ->where('estado', '!=', 'completada')
                ->update([
                    'estado'     => 'completada',
                    'paquete_id' => $paqueteId,
                ]);

            $stats['etiquetas_actualizadas'] += $updated;
        }

        // 2. Actualizar etiquetas sin subetiqueta
        $updated = Etiqueta::where('planilla_id', $planilla->id)
            ->whereNull('etiqueta_sub_id')
            ->where('estado', '!=', 'completada')
            ->update(['estado' => 'completada']);

        $stats['etiquetas_actualizadas'] += $updated;

        // 3. Eliminar de orden_planillas
        $maquinasAfectadas = OrdenPlanilla::where('planilla_id', $planilla->id)
            ->pluck('maquina_id')
            ->unique()
            ->toArray();

        $deleted = OrdenPlanilla::where('planilla_id', $planilla->id)->delete();
        $stats['ordenes_eliminadas'] += $deleted;

        // 4. Reindexar posiciones
        foreach ($maquinasAfectadas as $maquinaId) {
            $ordenes = OrdenPlanilla::where('maquina_id', $maquinaId)
                ->orderBy('posicion')
                ->get();

            foreach ($ordenes as $index => $orden) {
                if ($orden->posicion !== $index + 1) {
                    $orden->update(['posicion' => $index + 1]);
                }
            }
        }

        // 5. Marcar planilla como completada
        if ($planilla->estado !== 'completada') {
            $planilla->update(['estado' => 'completada']);
        }
    }
}
