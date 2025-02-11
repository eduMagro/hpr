<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class StockInsuficiente extends Notification
{
    use Queueable;

    protected $mensaje;

    public function __construct($mensaje)
    {
        $this->mensaje = $mensaje;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast']; // Guarda en la DB y envía en tiempo real
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'mensaje' => $this->mensaje,
            'sonido' => true // Agrega un indicador para el sonido
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'mensaje' => $this->mensaje
        ];
    }
}
