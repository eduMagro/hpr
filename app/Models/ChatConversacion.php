<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversacion extends Model
{
    protected $table = 'chat_conversaciones';

    protected $fillable = [
        'user_id',
        'titulo',
        'ultima_actividad',
    ];

    protected $casts = [
        'ultima_actividad' => 'datetime',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con los mensajes
     */
    public function mensajes(): HasMany
    {
        return $this->hasMany(ChatMensaje::class, 'conversacion_id')->orderBy('created_at', 'asc');
    }

    /**
     * Actualiza la última actividad de la conversación
     */
    public function actualizarActividad(): void
    {
        $this->update(['ultima_actividad' => now()]);
    }

    /**
     * Genera un título automático si no tiene
     */
    public function generarTituloAutomatico(): void
    {
        if (!$this->titulo && $this->mensajes()->count() > 0) {
            $primerMensaje = $this->mensajes()->where('role', 'user')->first();
            if ($primerMensaje) {
                $titulo = substr($primerMensaje->contenido, 0, 50);
                if (strlen($primerMensaje->contenido) > 50) {
                    $titulo .= '...';
                }
                $this->update(['titulo' => $titulo]);
            }
        }
    }
}
