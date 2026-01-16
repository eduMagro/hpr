<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenteInforme extends Model
{
    use HasFactory;

    protected $table = 'asistente_informes';

    protected $fillable = [
        'user_id',
        'mensaje_id',
        'tipo',
        'titulo',
        'parametros',
        'datos',
        'resumen',
        'archivo_pdf',
        'expira_at',
    ];

    protected $casts = [
        'parametros' => 'array',
        'datos' => 'array',
        'resumen' => 'array',
        'expira_at' => 'datetime',
    ];

    /**
     * Tipos de informes disponibles
     */
    public const TIPOS = [
        'stock_general' => 'Stock General',
        'stock_critico' => 'Stock Crítico',
        'produccion_diaria' => 'Producción Diaria',
        'produccion_semanal' => 'Producción Semanal',
        'consumo_maquinas' => 'Consumo por Máquinas',
        'peso_obra' => 'Kilos por Obra',
        'planilleros' => 'Producción por Planillero',
        'planillas_pendientes' => 'Planillas Pendientes',
    ];

    /**
     * Usuario propietario del informe
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mensaje de chat asociado (opcional)
     */
    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(ChatMensaje::class, 'mensaje_id');
    }

    /**
     * Verifica si el informe ha expirado
     */
    public function haExpirado(): bool
    {
        return $this->expira_at && $this->expira_at->isPast();
    }

    /**
     * Verifica si el PDF existe
     */
    public function tienePdf(): bool
    {
        return $this->archivo_pdf && file_exists(storage_path('app/' . $this->archivo_pdf));
    }

    /**
     * Obtiene la ruta completa del PDF
     */
    public function getRutaPdf(): ?string
    {
        if (!$this->tienePdf()) {
            return null;
        }
        return storage_path('app/' . $this->archivo_pdf);
    }

    /**
     * Scope para informes no expirados
     */
    public function scopeVigentes($query)
    {
        return $query->where('expira_at', '>', now());
    }

    /**
     * Scope para informes del usuario
     */
    public function scopeDelUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
