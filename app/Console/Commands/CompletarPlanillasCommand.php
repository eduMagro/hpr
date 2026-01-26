<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\OrdenPlanilla;
use App\Models\Paquete;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CompletarPlanillasCommand extends Command
{
    protected $signature = 'planillas:completar
                            {--fecha= : Fecha de corte (formato Y-m-d). Por defecto: hoy}
                            {--chunk=100 : Cantidad de planillas a procesar por lote}
                            {--dry-run : Solo mostrar cuántas planillas se procesarían}
                            {--con-fecha-fin : Completar planillas que ya tienen fecha_finalizacion (importadas del Excel)}';

    protected $description = 'Completa todas las planillas con fecha estimada de entrega <= fecha de corte';

    public function handle()
    {
        $fechaCorteStr = $this->option('fecha');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');
        $conFechaFin = $this->option('con-fecha-fin');

        // Modo 1: Completar planillas que ya tienen fecha_finalizacion (importadas del Excel)
        if ($conFechaFin) {
            return $this->completarConFechaFinalizacion($chunkSize, $dryRun);
        }

        // Modo 2: Completar por fecha de corte (comportamiento original)
        $fechaCorte = $fechaCorteStr
            ? Carbon::parse($fechaCorteStr)->endOfDay()
            : Carbon::today()->endOfDay();

        $this->info("Fecha de corte: {$fechaCorte->format('Y-m-d')}");
        $this->info("Tamaño de lote: {$chunkSize}");

        // Contar planillas candidatas
        $totalPlanillas = Planilla::whereIn('estado', ['pendiente', 'fabricando'])
            ->whereNotNull('fecha_estimada_entrega')
            ->whereDate('fecha_estimada_entrega', '<=', $fechaCorte)
            ->count();

        $this->info("Planillas a procesar: {$totalPlanillas}");

        if ($dryRun) {
            $this->warn('Modo dry-run: no se realizaron cambios.');
            return 0;
        }

        if ($totalPlanillas === 0) {
            $this->info('No hay planillas para procesar.');
            return 0;
        }

        $bar = $this->output->createProgressBar($totalPlanillas);
        $bar->start();

        $ok = 0;
        $fail = 0;
        $errores = [];

        Planilla::whereIn('estado', ['pendiente', 'fabricando'])
            ->whereNotNull('fecha_estimada_entrega')
            ->whereDate('fecha_estimada_entrega', '<=', $fechaCorte)
            ->chunk($chunkSize, function ($planillas) use (&$ok, &$fail, &$errores, $bar) {
                foreach ($planillas as $planilla) {
                    try {
                        $this->completarPlanillaDirecta($planilla);
                        $ok++;
                    } catch (\Exception $e) {
                        $fail++;
                        $errores[] = [
                            'planilla_id' => $planilla->id,
                            'codigo' => $planilla->codigo,
                            'error' => $e->getMessage(),
                        ];
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("Completadas: {$ok}");
        if ($fail > 0) {
            $this->error("Fallidas: {$fail}");
            foreach (array_slice($errores, 0, 10) as $error) {
                $this->error("  - {$error['codigo']}: {$error['error']}");
            }
            if (count($errores) > 10) {
                $this->error("  ... y " . (count($errores) - 10) . " errores más");
            }
        }

        return $fail > 0 ? 1 : 0;
    }

    /**
     * Completa planillas que ya tienen fecha_finalizacion (importadas del Excel)
     * Estas son planillas que ya fueron fabricadas pero no están marcadas como completadas
     */
    protected function completarConFechaFinalizacion(int $chunkSize, bool $dryRun): int
    {
        $this->info("🏭 Modo: Completar planillas con fecha_finalizacion (ya fabricadas)");
        $this->info("Tamaño de lote: {$chunkSize}");

        // Contar planillas candidatas: tienen fecha_finalizacion pero estado no es 'completada'
        $totalPlanillas = Planilla::whereIn('estado', ['pendiente', 'fabricando', 'fabricada'])
            ->whereNotNull('fecha_finalizacion')
            ->count();

        $this->info("Planillas a completar: {$totalPlanillas}");

        if ($dryRun) {
            // Mostrar algunas de ejemplo
            $ejemplos = Planilla::whereIn('estado', ['pendiente', 'fabricando', 'fabricada'])
                ->whereNotNull('fecha_finalizacion')
                ->limit(10)
                ->get(['codigo', 'estado', 'fecha_finalizacion']);

            $this->newLine();
            $this->info("Ejemplos de planillas que se completarían:");
            foreach ($ejemplos as $p) {
                $this->line("  - {$p->codigo} | Estado: {$p->estado} | Fin: {$p->fecha_finalizacion}");
            }

            $this->warn('Modo dry-run: no se realizaron cambios.');
            return 0;
        }

        if ($totalPlanillas === 0) {
            $this->info('No hay planillas para procesar.');
            return 0;
        }

        $bar = $this->output->createProgressBar($totalPlanillas);
        $bar->start();

        $ok = 0;
        $fail = 0;
        $errores = [];

        Planilla::whereIn('estado', ['pendiente', 'fabricando', 'fabricada'])
            ->whereNotNull('fecha_finalizacion')
            ->chunk($chunkSize, function ($planillas) use (&$ok, &$fail, &$errores, $bar) {
                foreach ($planillas as $planilla) {
                    try {
                        $this->completarPlanillaDirecta($planilla);
                        $ok++;
                    } catch (\Exception $e) {
                        $fail++;
                        $errores[] = [
                            'planilla_id' => $planilla->id,
                            'codigo' => $planilla->codigo,
                            'error' => $e->getMessage(),
                        ];
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("Completadas: {$ok}");
        if ($fail > 0) {
            $this->error("Fallidas: {$fail}");
            foreach (array_slice($errores, 0, 10) as $error) {
                $this->error("  - {$error['codigo']}: {$error['error']}");
            }
            if (count($errores) > 10) {
                $this->error("  ... y " . (count($errores) - 10) . " errores más");
            }
        }

        return $fail > 0 ? 1 : 0;
    }

    /**
     * Completa una planilla de forma directa (sin procesar subetiquetas individualmente)
     */
    protected function completarPlanillaDirecta(Planilla $planilla): void
    {
        DB::transaction(function () use ($planilla) {
            // Marcar todas las etiquetas como completadas y crear paquetes
            $subetiquetas = Etiqueta::where('planilla_id', $planilla->id)
                ->whereNotNull('etiqueta_sub_id')
                ->where('estado', '!=', 'completada')
                ->distinct()
                ->pluck('etiqueta_sub_id');

            foreach ($subetiquetas as $subId) {
                $etiquetas = Etiqueta::where('etiqueta_sub_id', $subId)->get();

                if ($etiquetas->isEmpty()) {
                    continue;
                }

                // Verificar si ya existe un paquete para esta subetiqueta (buscando a través de etiquetas)
                $etiquetaConPaquete = $etiquetas->first(fn($e) => $e->paquete_id !== null);
                $paquete = $etiquetaConPaquete?->paquete;

                if (!$paquete) {
                    // Solo crear paquete si no existe
                    $pesoTotal = $etiquetas->sum('peso');
                    $paquete = Paquete::crearConCodigoUnico([
                        'planilla_id' => $planilla->id,
                        'peso'        => $pesoTotal,
                        'estado'      => 'pendiente',
                    ]);
                }

                Etiqueta::where('etiqueta_sub_id', $subId)->update([
                    'estado'     => 'completada',
                    'paquete_id' => $paquete->id,
                ]);
            }

            // Marcar planilla como completada
            $planilla->estado = 'completada';
            $planilla->save();

            // Eliminar de las colas de producción y reindexar posiciones
            $maquinasAfectadas = OrdenPlanilla::where('planilla_id', $planilla->id)
                ->pluck('maquina_id')
                ->toArray();

            OrdenPlanilla::where('planilla_id', $planilla->id)->delete();

            // Reindexar posiciones de cada máquina afectada
            foreach ($maquinasAfectadas as $maquinaId) {
                $this->reindexarPosicionesMaquina($maquinaId);
            }
        });
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
