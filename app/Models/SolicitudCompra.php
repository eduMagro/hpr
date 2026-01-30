<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudCompra extends Model
{
    use HasFactory;

    protected $table = 'solicitud_compras';

    protected $fillable = [
        'user_id',
        'descripcion',
        'estado',
        'encargado_id',
        'fecha_aprobacion',
        'token_qr',
    ];

    protected $casts = [
        'fecha_aprobacion' => 'datetime',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function encargado()
    {
        return $this->belongsTo(User::class, 'encargado_id');
    }

    // Generar URL del QR (usando la ruta de la API)
    public function getUrlQrAttribute()
    {
        if (!$this->token_qr)
            return null;
        return 'https://app.hierrospacoreyes.es/laapi/' . $this->token_qr;
    }
}
