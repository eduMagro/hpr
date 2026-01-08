<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $fillable = [
        'titulo',
        'descripcion',
        'comentario',
        'estado',
        'prioridad',
        'user_id',
        'asignado_a'
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function asignado()
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }
}
