<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Turno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'turnos';
    protected $fillable = ['nombre', 'hora_entrada', 'entrada_offset', 'hora_salida', 'salida_offset',];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionTurno::class, 'turno_id');
    }
}
