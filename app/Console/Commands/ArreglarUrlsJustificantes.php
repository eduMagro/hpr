<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\AsignacionTurno;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArreglarUrlsJustificantes extends Command
{
    protected $signature = 'alertas:arreglar-justificantes {--dry-run : Mostrar cambios sin aplicarlos}';
    protected $description = 'Actualiza las URLs de justificantes en alertas existentes para usar la ruta segura';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Modo simulación (dry-run) - No se aplicarán cambios');
        }

        // Buscar alertas con enlaces a justificantes (tipo justificante o que contengan /storage/documentos y justificantes)
        $alertas = Alerta::where(function ($q) {
            $q->where('tipo', 'justificante')
              ->orWhere('mensaje', 'like', '%/storage/documentos/%justificantes%');
        })->get();

        $this->info("Encontradas {$alertas->count()} alertas con justificantes");

        $actualizadas = 0;
        $errores = 0;

        foreach ($alertas as $alerta) {
            // Extraer la ruta del justificante del mensaje
            // Patrón: href="...storage/documentos/Usuario_123/justificantes_asistencia/2026-01-20_123456.pdf"
            if (preg_match('/href="[^"]*\/storage\/([^"]+)"/', $alerta->mensaje, $matches)) {
                $rutaStorage = $matches[1]; // documentos/Usuario_123/justificantes_asistencia/2026-01-20_123456.pdf

                // Buscar la asignación por la ruta del justificante
                $asignacion = AsignacionTurno::where('justificante_ruta', $rutaStorage)->first();

                if (!$asignacion) {
                    // Intentar buscar con ruta parcial (por si la ruta tiene variaciones)
                    $asignacion = AsignacionTurno::where('justificante_ruta', 'like', '%' . basename($rutaStorage))->first();
                }

                if ($asignacion) {
                    // Construir la nueva URL
                    $nuevaUrl = route('asignaciones-turnos.justificante', $asignacion->id);

                    // Reemplazar en el mensaje
                    $nuevoMensaje = preg_replace(
                        '/href="[^"]*\/storage\/[^"]+"/',
                        'href="' . $nuevaUrl . '"',
                        $alerta->mensaje
                    );

                    if ($nuevoMensaje !== $alerta->mensaje) {
                        if ($dryRun) {
                            $this->line("  [Alerta #{$alerta->id}] Se actualizaría:");
                            $this->line("    Antes: " . substr($alerta->mensaje, 0, 100) . '...');
                            $this->line("    Después: " . substr($nuevoMensaje, 0, 100) . '...');
                        } else {
                            $alerta->mensaje = $nuevoMensaje;
                            $alerta->save();
                            $this->info("  ✓ Alerta #{$alerta->id} actualizada (asignación #{$asignacion->id})");
                        }
                        $actualizadas++;
                    }
                } else {
                    $this->warn("  ⚠ Alerta #{$alerta->id}: No se encontró asignación para ruta: {$rutaStorage}");
                    $errores++;
                }
            } else {
                $this->warn("  ⚠ Alerta #{$alerta->id}: No se pudo extraer URL del mensaje");
                $errores++;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("📊 Resumen (simulación):");
            $this->info("   - Se actualizarían: {$actualizadas}");
            $this->info("   - Errores/no encontradas: {$errores}");
            $this->newLine();
            $this->info("Ejecuta sin --dry-run para aplicar los cambios.");
        } else {
            $this->info("📊 Resumen:");
            $this->info("   - Actualizadas: {$actualizadas}");
            $this->info("   - Errores/no encontradas: {$errores}");
        }

        return Command::SUCCESS;
    }
}
