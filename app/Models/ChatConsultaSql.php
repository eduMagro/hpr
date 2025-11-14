<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatConsultaSql extends Model
{
    protected $table = 'chat_consultas_sql';

    protected $fillable = [
        'mensaje_id',
        'user_id',
        'consulta_sql',
        'consulta_natural',
        'resultados',
        'filas_afectadas',
        'exitosa',
        'error',
    ];

    protected $casts = [
        'resultados' => 'array',
        'exitosa' => 'boolean',
    ];

    /**
     * Relación con el mensaje
     */
    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(ChatMensaje::class, 'mensaje_id');
    }

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
