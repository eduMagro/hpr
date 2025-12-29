<?php

namespace App\Console\Commands;

use App\Services\FerrawinSync\FerrawinSyncService;
use App\Notifications\FerrawinSyncCompletedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SyncFerrawin extends Command
{
    protected $signature = 'sync:ferrawin
                            {--dias=7 : Días hacia atrás para buscar planillas}
                            {--solo-nuevas : Solo sincronizar planillas nuevas (no actualizaciones)}
                            {--sin-email : No enviar notificación por email}
                            {--force : Forzar sincronización aunque ya se haya ejecutado hoy}';

    protected $description = 'Sincroniza planillas desde FerraWin a Manager';

    public function handle(FerrawinSyncService $syncService): int
    {
        $dias = (int) $this->option('dias');
        $soloNuevas = (bool) $this->option('solo-nuevas');
        $sinEmail = (bool) $this->option('sin-email');

        $this->info("=== Sincronización FerraWin → Manager ===");
        $this->info("Buscando planillas de los últimos {$dias} días...");
        $this->newLine();

        try {
            $resultado = $syncService->sincronizar($dias, $soloNuevas);

            $this->mostrarResultado($resultado);

            if (!$sinEmail && config('ferrawin.sync.notificar_email', true)) {
                $this->enviarNotificacion($resultado);
            }

            return $resultado->esExitosa() ? Command::SUCCESS : Command::FAILURE;

        } catch (\Throwable $e) {
            $this->error("Error crítico: {$e->getMessage()}");
            Log::channel('ferrawin_sync')->error("Error en comando sync:ferrawin", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    protected function mostrarResultado($resultado): void
    {
        $this->newLine();

        if ($resultado->esExitosa()) {
            $this->info("✅ " . match($resultado->estado) {
                'completado' => 'Sincronización completada exitosamente',
                'sin_cambios' => 'No hay cambios pendientes',
                'sin_datos' => 'No se encontraron planillas en FerraWin',
                default => 'Proceso finalizado',
            });
        } else {
            $this->error("❌ Error: " . ($resultado->error ?? 'Error desconocido'));
        }

        $this->newLine();
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Planillas encontradas', $resultado->stats['planillas_encontradas']],
                ['Planillas nuevas', $resultado->stats['planillas_nuevas']],
                ['Planillas actualizadas', $resultado->stats['planillas_actualizadas']],
                ['Sincronizadas correctamente', $resultado->stats['planillas_sincronizadas']],
                ['Fallidas', $resultado->stats['planillas_fallidas']],
                ['Elementos creados', $resultado->stats['elementos_creados']],
                ['Duración', "{$resultado->duracion} seg"],
            ]
        );

        if (!empty($resultado->stats['errores'])) {
            $this->newLine();
            $this->warn("⚠️ Errores:");
            foreach ($resultado->stats['errores'] as $error) {
                $this->line("   - {$error}");
            }
        }

        if (!empty($resultado->stats['advertencias'])) {
            $this->newLine();
            $this->comment("ℹ️ Advertencias:");
            foreach ($resultado->stats['advertencias'] as $adv) {
                $this->line("   - {$adv}");
            }
        }
    }

    protected function enviarNotificacion($resultado): void
    {
        try {
            $emails = config('ferrawin.sync.emails_notificacion', []);

            if (empty($emails)) {
                $emails = [config('mail.from.address')];
            }

            Notification::route('mail', $emails)
                ->notify(new FerrawinSyncCompletedNotification($resultado));

            $this->info("📧 Notificación enviada a: " . implode(', ', $emails));

        } catch (\Throwable $e) {
            $this->warn("⚠️ No se pudo enviar el email: {$e->getMessage()}");
        }
    }
}
