<?php

namespace App\Console\Commands;

use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\PlanillaEntidad;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VincularElementosEntidades extends Command
{
    protected $signature = 'elementos:vincular-entidades
                            {--planilla= : Código de planilla específica}
                            {--dry-run : Solo mostrar qué se haría, sin modificar}';

    protected $description = 'Vincula elementos existentes con sus entidades de ensamblaje usando el campo fila/linea';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $codigoPlanilla = $this->option('planilla');

        if ($dryRun) {
            $this->warn('Modo dry-run: no se realizarán cambios');
        }

        // Obtener planillas con entidades
        $query = Planilla::whereHas('entidades');

        if ($codigoPlanilla) {
            $query->where('codigo', $codigoPlanilla);
        }

        $planillas = $query->get();

        if ($planillas->isEmpty()) {
            $this->error('No se encontraron planillas con entidades');
            return 1;
        }

        $this->info("Procesando {$planillas->count()} planillas...");

        $totalVinculados = 0;
        $totalNoVinculados = 0;

        $this->withProgressBar($planillas, function ($planilla) use ($dryRun, &$totalVinculados, &$totalNoVinculados) {
            $resultado = $this->procesarPlanilla($planilla, $dryRun);
            $totalVinculados += $resultado['vinculados'];
            $totalNoVinculados += $resultado['no_vinculados'];
        });

        $this->newLine(2);
        $this->info("Resumen:");
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Planillas procesadas', $planillas->count()],
                ['Elementos vinculados', $totalVinculados],
                ['Elementos sin entidad', $totalNoVinculados],
            ]
        );

        return 0;
    }

    private function procesarPlanilla(Planilla $planilla, bool $dryRun): array
    {
        $entidades = $planilla->entidades;
        $elementos = $planilla->elementos()->whereNull('planilla_entidad_id')->get();

        $vinculados = 0;
        $noVinculados = 0;

        // Crear mapa de linea -> entidad_id
        $mapaEntidades = [];
        foreach ($entidades as $entidad) {
            // Normalizar linea: quitar ceros del inicio
            $lineaNormalizada = ltrim($entidad->linea, '0');
            if (empty($lineaNormalizada)) {
                $lineaNormalizada = '0';
            }
            $mapaEntidades[$lineaNormalizada] = $entidad->id;
        }

        foreach ($elementos as $elemento) {
            $filaNormalizada = (string)$elemento->fila;

            if (isset($mapaEntidades[$filaNormalizada])) {
                if (!$dryRun) {
                    $elemento->update([
                        'planilla_entidad_id' => $mapaEntidades[$filaNormalizada]
                    ]);
                }
                $vinculados++;
            } else {
                $noVinculados++;
            }
        }

        return [
            'vinculados' => $vinculados,
            'no_vinculados' => $noVinculados,
        ];
    }
}
