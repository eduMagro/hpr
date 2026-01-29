<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AsignarPaquetesASalidasCommand extends Command
{
    protected $signature = 'paquetes:asignar-salidas
                            {--dry-run : Mostrar qué se haría sin ejecutar cambios}
                            {--limit=0 : Limitar número de paquetes a procesar (0 = todos)}';

    protected $description = 'Asigna paquetes huérfanos a salidas según obra_id + fecha_entrega + nave_id';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info($dryRun ? '🔍 MODO DRY-RUN (no se harán cambios)' : '🚀 Ejecutando asignación...');
        $this->newLine();

        // 1. Contar paquetes sin salida
        $totalSinSalida = DB::table('paquetes')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('salidas_paquetes')
                    ->whereColumn('salidas_paquetes.paquete_id', 'paquetes.id');
            })
            ->whereNull('deleted_at')
            ->count();

        $this->info("📦 Paquetes sin salida: {$totalSinSalida}");

        if ($totalSinSalida === 0) {
            $this->info('✅ No hay paquetes pendientes de asignar.');
            return 0;
        }

        // 2. Obtener paquetes agrupados por (obra_id, fecha_entrega, nave_id)
        // Consulta simplificada: usa fecha de planilla directamente
        // (la mayoría de paquetes no tienen fecha_entrega en elementos)
        $query = "
            SELECT
                p.id as paquete_id,
                p.codigo as paquete_codigo,
                p.peso,
                p.maquina_id,
                pl.obra_id,
                DATE(pl.fecha_estimada_entrega) as fecha_entrega,
                COALESCE(m.obra_id, p.nave_id, 1) as nave_id
            FROM paquetes p
            INNER JOIN planillas pl ON pl.id = p.planilla_id
            LEFT JOIN maquinas m ON m.id = p.maquina_id
            WHERE p.deleted_at IS NULL
              AND pl.obra_id IS NOT NULL
              AND pl.fecha_estimada_entrega IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM salidas_paquetes sp WHERE sp.paquete_id = p.id
              )
            ORDER BY fecha_entrega, pl.obra_id, nave_id
        ";

        if ($limit > 0) {
            $query .= " LIMIT {$limit}";
        }

        $paquetes = DB::select($query);
        $this->info("📋 Paquetes a procesar: " . count($paquetes));
        $this->newLine();

        if (count($paquetes) === 0) {
            $this->warn('⚠️  No se encontraron paquetes válidos (puede que falten datos de planilla/fecha).');
            return 0;
        }

        // 3. Agrupar por (obra_id, fecha_entrega, nave_id)
        $grupos = [];
        foreach ($paquetes as $p) {
            $key = "{$p->obra_id}|{$p->fecha_entrega}|{$p->nave_id}";
            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'obra_id' => $p->obra_id,
                    'fecha_entrega' => $p->fecha_entrega,
                    'nave_id' => $p->nave_id,
                    'paquetes' => [],
                    'peso_total' => 0,
                ];
            }
            $grupos[$key]['paquetes'][] = $p;
            $grupos[$key]['peso_total'] += $p->peso ?? 0;
        }

        $this->info("📊 Grupos únicos (obra+fecha+nave): " . count($grupos));
        $this->newLine();

        if ($dryRun) {
            $this->mostrarResumenDryRun($grupos);
            return 0;
        }

        // 4. Procesar cada grupo
        $bar = $this->output->createProgressBar(count($grupos));
        $bar->start();

        $salidasCreadas = 0;
        $paquetesAsignados = 0;

        DB::beginTransaction();
        try {
            foreach ($grupos as $grupo) {
                $resultado = $this->procesarGrupo($grupo);
                $salidasCreadas += $resultado['salidas_creadas'];
                $paquetesAsignados += $resultado['paquetes_asignados'];
                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Proceso completado:");
            $this->info("   - Salidas creadas: {$salidasCreadas}");
            $this->info("   - Paquetes asignados: {$paquetesAsignados}");

        } catch (\Throwable $e) {
            DB::rollBack();
            $bar->finish();
            $this->newLine();
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function procesarGrupo(array $grupo): array
    {
        $obraId = $grupo['obra_id'];
        $fechaEntrega = $grupo['fecha_entrega'];
        $naveId = $grupo['nave_id'];
        $paquetes = $grupo['paquetes'];

        $salidasCreadas = 0;
        $paquetesAsignados = 0;
        $limitePeso = 28000; // 28 toneladas

        // Obtener salidas existentes para este grupo
        $salidasExistentes = DB::table('salidas')
            ->where('obra_id', $obraId)
            ->whereDate('fecha_salida', $fechaEntrega)
            ->where('nave_id', $naveId)
            ->where('estado', '!=', 'completada')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($s) {
                $s->peso_actual = DB::table('salidas_paquetes')
                    ->join('paquetes', 'paquetes.id', '=', 'salidas_paquetes.paquete_id')
                    ->where('salidas_paquetes.salida_id', $s->id)
                    ->sum('paquetes.peso') ?? 0;
                return $s;
            })
            ->toArray();

        foreach ($paquetes as $paquete) {
            $pesoPaquete = $paquete->peso ?? 0;
            $salidaAsignada = null;

            // Buscar salida con espacio
            foreach ($salidasExistentes as &$salida) {
                if (($salida->peso_actual + $pesoPaquete) <= $limitePeso) {
                    $salidaAsignada = $salida;
                    $salida->peso_actual += $pesoPaquete;
                    break;
                }
            }
            unset($salida);

            // Si no hay salida disponible, crear una nueva
            if (!$salidaAsignada) {
                $salidaId = $this->crearSalida($obraId, $fechaEntrega, $naveId);
                $nuevaSalida = (object)[
                    'id' => $salidaId,
                    'peso_actual' => $pesoPaquete,
                ];
                $salidasExistentes[] = $nuevaSalida;
                $salidaAsignada = $nuevaSalida;
                $salidasCreadas++;
            }

            // Asignar paquete a salida
            DB::table('salidas_paquetes')->insert([
                'salida_id' => $salidaAsignada->id,
                'paquete_id' => $paquete->paquete_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Actualizar estado del paquete
            DB::table('paquetes')
                ->where('id', $paquete->paquete_id)
                ->update(['estado' => 'asignado_a_salida']);

            $paquetesAsignados++;
        }

        return [
            'salidas_creadas' => $salidasCreadas,
            'paquetes_asignados' => $paquetesAsignados,
        ];
    }

    private function crearSalida(int $obraId, string $fechaSalida, int $naveId): int
    {
        $sufNave = $naveId === 2 ? 'B' : 'A';
        $year = substr(date('Y'), 2);

        $salidaId = DB::table('salidas')->insertGetId([
            'obra_id' => $obraId,
            'fecha_salida' => $fechaSalida,
            'nave_id' => $naveId,
            'estado' => 'pendiente',
            'user_id' => 1, // Sistema
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Generar código: ASA26/0001 o ASB26/0001
        $codigoSalida = "AS{$sufNave}{$year}/" . str_pad($salidaId, 4, '0', STR_PAD_LEFT);

        DB::table('salidas')
            ->where('id', $salidaId)
            ->update(['codigo_salida' => $codigoSalida]);

        return $salidaId;
    }

    private function mostrarResumenDryRun(array $grupos): void
    {
        $this->table(
            ['Obra ID', 'Fecha Entrega', 'Nave', 'Paquetes', 'Peso Total (kg)'],
            collect($grupos)->map(fn($g) => [
                $g['obra_id'],
                $g['fecha_entrega'],
                $g['nave_id'] == 2 ? 'Nave B' : 'Nave A',
                count($g['paquetes']),
                number_format($g['peso_total'], 2),
            ])->take(50)->toArray()
        );

        if (count($grupos) > 50) {
            $this->info("... y " . (count($grupos) - 50) . " grupos más");
        }

        $this->newLine();
        $this->info("💡 Ejecuta sin --dry-run para aplicar los cambios");
    }
}
