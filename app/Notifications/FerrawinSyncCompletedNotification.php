<?php

namespace App\Notifications;

use App\Services\FerrawinSync\FerrawinSyncResult;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FerrawinSyncCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected FerrawinSyncResult $resultado
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting($this->getGreeting());

        $mail->line("**Fecha:** " . now()->format('d/m/Y H:i:s'));
        $mail->line("**Duración:** {$this->resultado->duracion} segundos");
        $mail->line("");

        $mail->line("### Estadísticas");
        $mail->line("- Planillas encontradas: **{$this->resultado->stats['planillas_encontradas']}**");
        $mail->line("- Planillas nuevas: **{$this->resultado->stats['planillas_nuevas']}**");
        $mail->line("- Planillas actualizadas: **{$this->resultado->stats['planillas_actualizadas']}**");
        $mail->line("- Sincronizadas correctamente: **{$this->resultado->stats['planillas_sincronizadas']}**");
        $mail->line("- Fallidas: **{$this->resultado->stats['planillas_fallidas']}**");
        $mail->line("- Elementos creados: **{$this->resultado->stats['elementos_creados']}**");

        if (!empty($this->resultado->stats['errores'])) {
            $mail->line("");
            $mail->line("### Errores");
            foreach ($this->resultado->stats['errores'] as $error) {
                $mail->line("- {$error}");
            }
        }

        if (!empty($this->resultado->stats['advertencias'])) {
            $mail->line("");
            $mail->line("### Advertencias");
            foreach (array_slice($this->resultado->stats['advertencias'], 0, 10) as $adv) {
                $mail->line("- {$adv}");
            }
            if (count($this->resultado->stats['advertencias']) > 10) {
                $mail->line("- ... y " . (count($this->resultado->stats['advertencias']) - 10) . " más");
            }
        }

        $mail->action('Ver en Manager', url('/planillas'));

        return $mail;
    }

    protected function getSubject(): string
    {
        $icono = $this->resultado->esExitosa() ? '✅' : '❌';
        $estado = match($this->resultado->estado) {
            'completado' => 'Completada',
            'sin_cambios' => 'Sin cambios',
            'sin_datos' => 'Sin datos',
            'error' => 'Error',
            default => 'Finalizada',
        };

        $planillas = $this->resultado->stats['planillas_sincronizadas'];

        return "{$icono} Sync FerraWin: {$estado} ({$planillas} planillas)";
    }

    protected function getGreeting(): string
    {
        if ($this->resultado->esExitosa()) {
            return $this->resultado->stats['planillas_sincronizadas'] > 0
                ? '✅ Sincronización completada exitosamente'
                : 'ℹ️ No había nuevas planillas para sincronizar';
        }

        return '❌ La sincronización encontró errores';
    }

    public function toArray(object $notifiable): array
    {
        return $this->resultado->toArray();
    }
}
