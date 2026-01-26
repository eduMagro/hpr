<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiUsuario extends Model
{
    protected $table = 'epis_usuario';

    protected $fillable = [
        'user_id',
        'epi_id',
        'cantidad',
        'entregado_en',
        'devuelto_en',
        'notas',
        'firmado',
        'firmado_dia',
        'firma_ruta',
    ];

    protected $casts = [
        'entregado_en' => 'datetime',
        'devuelto_en' => 'datetime',
        'firmado_dia' => 'datetime',
        'firmado' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'epi_id');
    }
}

