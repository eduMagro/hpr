<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento para recibir actualizaciones de estado desde el cliente Windows.
 *
 * Este evento se dispara desde sync-listener.php en Windows y es recibido
 * por el SyncMonitor en producción via Pusher.
 */
class SyncStatusEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Estado de la sincronización: 'running', 'paused', 'completed', 'error', 'idle'
     */
    public string $status;

    /**
     * Progreso actual (ej: "150/500")
     */
    public ?string $progress;

    /**
     * Año que se está sincronizando
     */
    public ?string $year;

    /**
     * Target de sincronización: 'local' o 'production'
     */
    public ?string $target;

    /**
     * Última planilla procesada
     */
    public ?string $lastPlanilla;

    /**
     * Mensaje adicional o descripción del error
     */
    public ?string $message;

    /**
     * ID de la solicitud que originó esta actualización (para correlación)
     */
    public ?string $requestId;

    /**
     * Timestamp de la actualización
     */
    public string $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $status,
        ?string $progress = null,
        ?string $year = null,
        ?string $target = null,
        ?string $lastPlanilla = null,
        ?string $message = null,
        ?string $requestId = null
    ) {
        $this->status = $status;
        $this->progress = $progress;
        $this->year = $year;
        $this->target = $target;
        $this->lastPlanilla = $lastPlanilla;
        $this->message = $message;
        $this->requestId = $requestId;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sync-control'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sync.status';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'status' => $this->status,
            'progress' => $this->progress,
            'year' => $this->year,
            'target' => $this->target,
            'lastPlanilla' => $this->lastPlanilla,
            'message' => $this->message,
            'requestId' => $this->requestId,
            'timestamp' => $this->timestamp,
        ];
    }
}
