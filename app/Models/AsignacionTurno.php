<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionTurno extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_turnos'; // Asegúrate de que coincide con la tabla en la BD
    protected $fillable = ['user_id', 'obra_id', 'turno_id', 'estado', 'maquina_id', 'entrada', 'salida', 'fecha'];

    /**
     * Relación con el turno (cada asignación pertenece a un turno).
     */
    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }
    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    /**
     * Relación con el usuario (cada asignación pertenece a un usuario).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
