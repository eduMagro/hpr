<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidencia extends Model
{
    protected $fillable = [
        'maquina_id',
        'user_id',
        'titulo',
        'descripcion',
        'fotos',
        'estado',
        'prioridad',
        'fecha_reporte',
        'fecha_resolucion',
        'resolucion',
        'resuelto_por',
        'coste',
    ];

    protected $casts = [
        'fotos' => 'array',
        'fecha_reporte' => 'datetime',
        'fecha_resolucion' => 'datetime',
        'coste' => 'decimal:2',
    ];

    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    public function gasto()
    {
        return $this->hasOne(Gasto::class);
    }
}
