<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccionAsistente extends Model
{
    use HasFactory;

    protected $table = 'acciones_asistente';

    protected $fillable = [
        'user_id',
        'conversacion_id',
        'accion',
        'parametros',
        'resultado',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'parametros' => 'array',
        'resultado' => 'array',
    ];

    /**
     * Usuario que ejecutó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Conversación donde se ejecutó (opcional)
     */
    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(ChatConversacion::class, 'conversacion_id');
    }

    /**
     * Verifica si la acción fue exitosa
     */
    public function fueExitosa(): bool
    {
        return ($this->resultado['success'] ?? false) === true;
    }

    /**
     * Scope para acciones del usuario
     */
    public function scopeDelUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para acciones recientes
     */
    public function scopeRecientes($query, int $dias = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }

    /**
     * Scope por tipo de acción
     */
    public function scopeDeAccion($query, string $accion)
    {
        return $query->where('accion', $accion);
    }
}
