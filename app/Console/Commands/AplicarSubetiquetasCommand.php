<?php

namespace App\Console\Commands;

use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Services\SubEtiquetaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AplicarSubetiquetasCommand extends Command
{
    protected $signature = 'planillas:aplicar-subetiquetas
                            {codigos* : Códigos de planillas (ej: 2026-252 2026-253)}
                            {--dry-run : Solo mostrar qué se haría sin hacer cambios}';

    protected $description = 'Aplicar política de subetiquetas a planillas existentes usando SubEtiquetaService';

    protected SubEtiquetaService $subEtiquetaService;

    public function __construct(SubEtiquetaService $subEtiquetaService)
    {
        parent::__construct();
        $this->subEtiquetaService = $subEtiquetaService;
    }

    public function handle()
    {
        $codigos = $this->argument('codigos');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 Modo dry-run: no se harán cambios');
        }

        $this->info("Procesando " . count($codigos) . " planilla(s)...\n");

        $totalElementos = 0;
        $totalSubsCreadas = 0;

        foreach ($codigos as $codigo) {
            $codigoNormalizado = $this->normalizarCodigo($codigo);

            $planilla = Planilla::where('codigo', $codigoNormalizado)
                ->orWhere('codigo', $codigo)
                ->first();

            if (!$planilla) {
                $this->error("❌ Planilla no encontrada: {$codigo}");
                continue;
            }

            $this->info("📋 Planilla: {$planilla->codigo}");
            $this->line("   Obra: " . ($planilla->obra->nombre ?? 'N/A'));

            // Obtener elementos sin subetiqueta
            $elementosSinSub = Elemento::where('planilla_id', $planilla->id)
                ->whereNull('etiqueta_sub_id')
                ->whereNotNull('etiqueta_id')
                ->get();

            if ($elementosSinSub->isEmpty()) {
                $this->info("   ✅ Todos los elementos ya tienen subetiqueta\n");
                continue;
            }

            $this->line("   Elementos sin subetiqueta: {$elementosSinSub->count()}");

            if ($dryRun) {
                $porMaquina = $elementosSinSub->groupBy(function ($e) {
                    return $e->maquina_id ?? $e->maquina_id_2 ?? $e->maquina_id_3 ?? 0;
                });

                foreach ($porMaquina as $maquinaId => $elementos) {
                    if ($maquinaId) {
                        $maquina = Maquina::find($maquinaId);
                        $tipo = $maquina ? ($maquina->tipo_material ?? 'desconocido') : 'N/A';
                        $this->line("      - Máquina {$maquinaId} ({$tipo}): {$elementos->count()} elementos");
                    } else {
                        $this->line("      - Sin máquina: {$elementos->count()} elementos");
                    }
                }

                $totalElementos += $elementosSinSub->count();
                $this->newLine();
                continue;
            }

            // Procesar elementos
            $subsCreadas = 0;
            $errores = 0;

            DB::beginTransaction();
            try {
                foreach ($elementosSinSub as $elemento) {
                    $maquinaReal = $elemento->maquina_id ?? $elemento->maquina_id_2 ?? $elemento->maquina_id_3;

                    if (!$maquinaReal) {
                        // Sin máquina: crear subetiqueta individual
                        $padre = Etiqueta::find($elemento->etiqueta_id);
                        if ($padre) {
                            $subId = Etiqueta::generarCodigoSubEtiqueta($padre->codigo);
                            $subRowId = $this->asegurarFilaSub($subId, $padre);

                            $elemento->update([
                                'etiqueta_sub_id' => $subId,
                                'etiqueta_id' => $subRowId,
                            ]);
                            $subsCreadas++;
                        }
                        continue;
                    }

                    try {
                        [$subDestino, $subOriginal] = $this->subEtiquetaService->reubicarSegunTipoMaterial($elemento, $maquinaReal);

                        if ($subDestino && $subDestino !== $subOriginal) {
                            $subsCreadas++;
                        }
                    } catch (\Exception $e) {
                        $errores++;
                        $this->warn("      ⚠️ Error en elemento {$elemento->id}: {$e->getMessage()}");
                    }
                }

                DB::commit();

                $this->info("   ✅ Procesados {$elementosSinSub->count()} elementos");
                $this->info("   ✅ Subetiquetas asignadas: {$subsCreadas}");
                if ($errores > 0) {
                    $this->warn("   ⚠️ Errores: {$errores}");
                }

                $totalElementos += $elementosSinSub->count();
                $totalSubsCreadas += $subsCreadas;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("   ❌ Error: {$e->getMessage()}");
            }

            $this->newLine();
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("📊 Se procesarían {$totalElementos} elementos");
        } else {
            $this->info("📊 Procesados {$totalElementos} elementos, {$totalSubsCreadas} subetiquetas asignadas");
        }

        return 0;
    }

    protected function normalizarCodigo(string $codigo): string
    {
        if (preg_match('/^(\d{4})-(\d+)$/', $codigo, $matches)) {
            return $matches[1] . '-' . str_pad($matches[2], 6, '0', STR_PAD_LEFT);
        }
        return $codigo;
    }

    protected function asegurarFilaSub(string $subId, Etiqueta $padre): int
    {
        $existente = Etiqueta::withTrashed()->where('etiqueta_sub_id', $subId)->first();

        if ($existente) {
            if ($existente->trashed()) {
                $existente->restore();
            }
            return $existente->id;
        }

        $sub = Etiqueta::create([
            'codigo' => $padre->codigo,
            'etiqueta_sub_id' => $subId,
            'planilla_id' => $padre->planilla_id,
            'nombre' => $padre->nombre,
            'estado' => 'pendiente',
            'peso' => 0,
        ]);

        return $sub->id;
    }
}
