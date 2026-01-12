<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Services\PlanillaImport\CodigoEtiqueta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReorganizarEtiquetasFerrawin extends Command
{
    protected $signature = 'ferrawin:reorganizar-etiquetas
                            {--dry-run : Simular sin hacer cambios}
                            {--planilla= : ID de planilla específica}
                            {--limit= : Limitar número de planillas}';

    protected $description = 'Reorganiza etiquetas de planillas importadas desde FerraWin agrupando por descripción+marca';

    protected CodigoEtiqueta $codigoService;

    public function __construct(CodigoEtiqueta $codigoService)
    {
        parent::__construct();
        $this->codigoService = $codigoService;
    }

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $planillaId = $this->option('planilla');
        $limit = $this->option('limit');

        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se harán cambios');
        }

        // Obtener planillas a procesar (FerraWin planillas tienen fecha_creacion_ferrawin)
        $query = Planilla::whereNotNull('fecha_creacion_ferrawin')
            ->whereHas('elementos');

        if ($planillaId) {
            $query->where('id', $planillaId);
        }

        if ($limit) {
            $query->limit((int)$limit);
        }

        $planillas = $query->get();

        $this->info("📦 Planillas a procesar: {$planillas->count()}");

        if ($planillas->isEmpty()) {
            $this->warn('No hay planillas FerraWin para procesar');
            return 0;
        }

        $totalPlanillas = 0;
        $totalElementosReorganizados = 0;
        $totalEtiquetasCreadas = 0;
        $totalEtiquetasEliminadas = 0;

        $bar = $this->output->createProgressBar($planillas->count());
        $bar->start();

        foreach ($planillas as $planilla) {
            try {
                $resultado = $this->procesarPlanilla($planilla, $dryRun);

                $totalPlanillas++;
                $totalElementosReorganizados += $resultado['elementos_movidos'];
                $totalEtiquetasCreadas += $resultado['etiquetas_creadas'];
                $totalEtiquetasEliminadas += $resultado['etiquetas_eliminadas'];

            } catch (\Exception $e) {
                $this->error("\n❌ Error en planilla {$planilla->codigo}: {$e->getMessage()}");
                Log::error("Error reorganizando planilla {$planilla->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=== RESUMEN ===");
        $this->info("Planillas procesadas: {$totalPlanillas}");
        $this->info("Elementos reorganizados: {$totalElementosReorganizados}");
        $this->info("Etiquetas creadas: {$totalEtiquetasCreadas}");
        $this->info("Etiquetas eliminadas: {$totalEtiquetasEliminadas}");

        if ($dryRun) {
            $this->warn("\n⚠️ Ejecuta sin --dry-run para aplicar los cambios");
        }

        return 0;
    }

    protected function procesarPlanilla(Planilla $planilla, bool $dryRun): array
    {
        $resultado = [
            'elementos_movidos' => 0,
            'etiquetas_creadas' => 0,
            'etiquetas_eliminadas' => 0,
        ];

        // Normalizar texto igual que ExcelReader
        $normalizar = fn($t) => mb_strtoupper(
            preg_replace('/\s+/u', ' ', trim((string)$t)),
            'UTF-8'
        ) ?: '—SIN VALOR—';

        // Obtener todos los elementos de la planilla con sus datos
        $elementos = Elemento::where('planilla_id', $planilla->id)->get();

        if ($elementos->isEmpty()) {
            return $resultado;
        }

        // Agrupar elementos por DESCRIPCIÓN + MARCA
        $grupos = [];
        foreach ($elementos as $elemento) {
            // Obtener descripción de la etiqueta actual o del elemento
            $etiquetaActual = $elemento->etiqueta;
            $descripcion = $normalizar($etiquetaActual->nombre ?? $elemento->marca ?? '');
            $marca = $normalizar($elemento->marca ?? '');

            $claveGrupo = $descripcion . '|' . $marca;

            if (!isset($grupos[$claveGrupo])) {
                $grupos[$claveGrupo] = [
                    'descripcion' => $descripcion,
                    'marca' => $marca,
                    'elementos' => [],
                ];
            }
            $grupos[$claveGrupo]['elementos'][] = $elemento;
        }

        // Si solo hay un grupo y coincide con las etiquetas actuales, no hay nada que hacer
        $etiquetasActuales = Etiqueta::where('planilla_id', $planilla->id)
            ->whereNull('etiqueta_sub_id')
            ->count();

        if (count($grupos) === $etiquetasActuales && count($grupos) === 1) {
            // Probablemente ya está bien organizado
            return $resultado;
        }

        if ($dryRun) {
            Log::info("[DRY-RUN] Planilla {$planilla->codigo}: " . count($grupos) . " grupos detectados (actual: {$etiquetasActuales} etiquetas)");
            $resultado['etiquetas_creadas'] = max(0, count($grupos) - $etiquetasActuales);
            $resultado['elementos_movidos'] = $elementos->count();
            return $resultado;
        }

        // Procesar en transacción
        DB::transaction(function () use ($planilla, $grupos, $normalizar, &$resultado) {
            // Crear mapa de grupo -> etiqueta padre
            $mapaEtiquetas = [];

            foreach ($grupos as $claveGrupo => $grupo) {
                // Buscar si ya existe una etiqueta con este nombre
                $nombreEtiqueta = $grupo['descripcion'] !== '—SIN VALOR—'
                    ? $grupo['descripcion']
                    : ($grupo['marca'] !== '—SIN VALOR—' ? $grupo['marca'] : 'Sin nombre');

                // Intentar reusar etiqueta existente con el mismo nombre
                $etiquetaExistente = Etiqueta::where('planilla_id', $planilla->id)
                    ->whereNull('etiqueta_sub_id')
                    ->whereRaw('UPPER(nombre) = ?', [mb_strtoupper($nombreEtiqueta, 'UTF-8')])
                    ->first();

                if ($etiquetaExistente) {
                    $mapaEtiquetas[$claveGrupo] = $etiquetaExistente;
                } else {
                    // Crear nueva etiqueta padre
                    $codigoPadre = $this->codigoService->generarCodigoPadre();

                    $nuevaEtiqueta = Etiqueta::create([
                        'codigo' => $codigoPadre,
                        'planilla_id' => $planilla->id,
                        'nombre' => $nombreEtiqueta,
                        'estado' => 'pendiente',
                        'peso' => 0,
                    ]);

                    $mapaEtiquetas[$claveGrupo] = $nuevaEtiqueta;
                    $resultado['etiquetas_creadas']++;
                }
            }

            // Reasignar elementos a sus nuevas etiquetas
            foreach ($grupos as $claveGrupo => $grupo) {
                $etiquetaPadre = $mapaEtiquetas[$claveGrupo];

                foreach ($grupo['elementos'] as $elemento) {
                    if ($elemento->etiqueta_id !== $etiquetaPadre->id) {
                        $elemento->etiqueta_id = $etiquetaPadre->id;
                        $elemento->save();
                        $resultado['elementos_movidos']++;
                    }
                }
            }

            // Recalcular pesos de etiquetas
            foreach ($mapaEtiquetas as $etiqueta) {
                $pesoTotal = Elemento::where('etiqueta_id', $etiqueta->id)->sum('peso');
                $etiqueta->peso = $pesoTotal;
                $etiqueta->save();
            }

            // Eliminar etiquetas huérfanas (sin elementos)
            $etiquetasHuerfanas = Etiqueta::where('planilla_id', $planilla->id)
                ->whereNull('etiqueta_sub_id')
                ->whereDoesntHave('elementos')
                ->get();

            foreach ($etiquetasHuerfanas as $huerfana) {
                // También eliminar subetiquetas asociadas
                Etiqueta::where('codigo', $huerfana->codigo)
                    ->whereNotNull('etiqueta_sub_id')
                    ->delete();

                $huerfana->delete();
                $resultado['etiquetas_eliminadas']++;
            }
        });

        return $resultado;
    }
}
