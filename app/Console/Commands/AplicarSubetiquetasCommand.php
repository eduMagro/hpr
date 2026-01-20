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
                            {codigos?* : Códigos de planillas (ej: 2026-252 2026-253)}
                            {--all : Procesar TODAS las planillas con elementos sin subetiqueta}
                            {--limit= : Limitar a N planillas (solo con --all)}
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
        $processAll = $this->option('all');
        $limit = $this->option('limit') ? (int)$this->option('limit') : null;

        if ($dryRun) {
            $this->warn('🔍 Modo dry-run: no se harán cambios');
        }

        // Si se usa --all, obtener todas las planillas con elementos sin subetiqueta
        if ($processAll) {
            return $this->procesarTodas($dryRun, $limit);
        }

        if (empty($codigos)) {
            $this->error('Debe especificar códigos de planillas o usar --all');
            return 1;
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

    /**
     * Procesar TODAS las planillas con elementos sin subetiqueta
     * Optimizado para evitar timeouts y manejar grandes volúmenes
     */
    protected function procesarTodas(bool $dryRun, ?int $limit): int
    {
        // Configurar para larga ejecución
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $this->info('=== PROCESAR TODAS LAS PLANILLAS PENDIENTES ===');
        $this->newLine();

        // Contar totales
        $totalPlanillas = DB::table('elementos')
            ->whereNull('etiqueta_sub_id')
            ->whereNotNull('etiqueta_id')
            ->whereNotNull('planilla_id')
            ->distinct()
            ->count('planilla_id');

        $totalElementosSinSub = DB::table('elementos')
            ->whereNull('etiqueta_sub_id')
            ->whereNotNull('etiqueta_id')
            ->count();

        $this->info("Planillas pendientes: {$totalPlanillas}");
        $this->info("Elementos sin subetiqueta: {$totalElementosSinSub}");
        $this->newLine();

        if ($totalPlanillas === 0) {
            $this->info('✅ Todas las planillas ya tienen subetiquetas asignadas');
            return 0;
        }

        // Usar limit por defecto de 500 si no se especifica (para evitar timeouts)
        $batchSize = $limit ?? 500;
        $this->info("Procesando en lotes de {$batchSize} planillas");
        $this->newLine();

        $totalElementos = 0;
        $totalSubsCreadas = 0;
        $totalErrores = 0;
        $planillasProcesadas = 0;
        $startTime = microtime(true);

        // Procesar en lotes para evitar cargar todo en memoria
        while (true) {
            // Obtener siguiente lote de planillas pendientes
            $planillaIds = DB::table('elementos')
                ->whereNull('etiqueta_sub_id')
                ->whereNotNull('etiqueta_id')
                ->whereNotNull('planilla_id')
                ->distinct()
                ->orderBy('planilla_id')
                ->limit($batchSize)
                ->pluck('planilla_id')
                ->toArray();

            if (empty($planillaIds)) {
                break; // No hay más planillas pendientes
            }

            $loteActual = count($planillaIds);
            $this->line("📦 Procesando lote de {$loteActual} planillas...");

            foreach ($planillaIds as $index => $planillaId) {
                $planilla = Planilla::find($planillaId);
                if (!$planilla) {
                    continue;
                }

                // Mostrar progreso cada 10 planillas
                if ($index % 10 === 0) {
                    $memoria = round(memory_get_usage() / 1024 / 1024, 1);
                    $this->line("   [{$index}/{$loteActual}] {$planilla->codigo} (Mem: {$memoria}MB)");
                }

                // Procesar elementos en chunks de 50 para evitar memory issues
                $chunkSize = 50;
                $offset = 0;

                while (true) {
                    $elementosChunk = Elemento::where('planilla_id', $planillaId)
                        ->whereNull('etiqueta_sub_id')
                        ->whereNotNull('etiqueta_id')
                        ->skip($offset)
                        ->take($chunkSize)
                        ->get();

                    if ($elementosChunk->isEmpty()) {
                        break;
                    }

                    if ($dryRun) {
                        $totalElementos += $elementosChunk->count();
                        $offset += $chunkSize;
                        continue;
                    }

                    // Procesar chunk con transacción
                    DB::beginTransaction();
                    try {
                        foreach ($elementosChunk as $elemento) {
                            $this->procesarElemento($elemento, $totalSubsCreadas, $totalErrores);
                        }
                        DB::commit();
                        $totalElementos += $elementosChunk->count();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $totalErrores++;
                        $this->error("   ❌ Error en planilla {$planilla->codigo}: " . substr($e->getMessage(), 0, 50));
                    }

                    $offset += $chunkSize;

                    // Liberar memoria
                    unset($elementosChunk);
                }

                $planillasProcesadas++;

                // Liberar memoria cada 20 planillas
                if ($planillasProcesadas % 20 === 0) {
                    gc_collect_cycles();
                    DB::connection()->getPdo()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                }
            }

            // Liberar memoria entre lotes
            unset($planillaIds);
            gc_collect_cycles();

            // Si se especificó --limit, solo procesar un lote
            if ($limit) {
                break;
            }

            // Recalcular pendientes
            $pendientes = DB::table('elementos')
                ->whereNull('etiqueta_sub_id')
                ->whereNotNull('etiqueta_id')
                ->whereNotNull('planilla_id')
                ->distinct()
                ->count('planilla_id');

            if ($pendientes > 0) {
                $this->newLine();
                $this->info("✓ Lote completado. Quedan {$pendientes} planillas pendientes.");
                $this->newLine();
            }
        }

        $elapsed = round(microtime(true) - $startTime, 1);
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('=== RESUMEN FINAL ===');
        $this->newLine();

        $this->info("Tiempo total: {$elapsed} segundos");
        $this->info("Planillas procesadas: {$planillasProcesadas}");

        if ($dryRun) {
            $this->info("Elementos a procesar: {$totalElementos}");
            $this->newLine();
            $this->warn('🔍 Ejecuta sin --dry-run para aplicar cambios');
        } else {
            $this->info("Elementos procesados: {$totalElementos}");
            $this->info("Subetiquetas asignadas: {$totalSubsCreadas}");
            if ($totalErrores > 0) {
                $this->warn("Errores: {$totalErrores}");
            }
        }

        // Verificar si quedan pendientes
        $pendientesFinales = DB::table('elementos')
            ->whereNull('etiqueta_sub_id')
            ->whereNotNull('etiqueta_id')
            ->count();

        if ($pendientesFinales > 0 && !$dryRun) {
            $this->newLine();
            $this->warn("⚠️ Quedan {$pendientesFinales} elementos pendientes. Ejecuta el comando de nuevo.");
        } elseif ($pendientesFinales === 0 && !$dryRun) {
            $this->newLine();
            $this->info('✅ ¡Todos los elementos tienen subetiqueta asignada!');
        }

        return 0;
    }

    /**
     * Procesa un elemento individual asignándole subetiqueta
     */
    protected function procesarElemento(Elemento $elemento, int &$subsCreadas, int &$errores): void
    {
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
            return;
        }

        try {
            [$subDestino, $subOriginal] = $this->subEtiquetaService->reubicarSegunTipoMaterial($elemento, $maquinaReal);

            if ($subDestino) {
                $subsCreadas++;
            }
        } catch (\Exception $e) {
            $errores++;
        }
    }
}
