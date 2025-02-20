<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    use HasFactory;

    protected $table = 'turnos'; // Asegúrate de que coincide con la tabla en la BD
    protected $fillable = ['nombre', 'hora_entrada', 'hora_salida'];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionTurno::class);
    }
}
