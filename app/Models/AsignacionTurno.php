<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionTurno extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_turnos'; // Asegúrate de que coincide con la tabla en la BD
    protected $fillable = ['user_id', 'turno_id', 'fecha', 'asignacion_manual'];
 
    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
