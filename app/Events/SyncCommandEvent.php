<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Evento para enviar comandos de sincronización al cliente Windows.
 *
 * Este evento se dispara desde producción (Laravel) y es recibido por
 * el cliente sync-listener.php en Windows via Pusher.
 */
class SyncCommandEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * El comando a ejecutar: 'start', 'pause', 'status'
     */
    public string $command;

    /**
     * Parámetros adicionales del comando
     */
    public array $params;

    /**
     * ID único de la solicitud para correlacionar respuestas
     */
    public string $requestId;

    /**
     * Timestamp de cuando se creó el comando
     */
    public string $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(string $command, array $params = [])
    {
        $this->command = $command;
        $this->params = $params;
        $this->requestId = Str::uuid()->toString();
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
        return 'sync.command';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'command' => $this->command,
            'params' => $this->params,
            'requestId' => $this->requestId,
            'timestamp' => $this->timestamp,
        ];
    }
}
