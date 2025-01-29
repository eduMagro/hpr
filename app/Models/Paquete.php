<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paquete extends Model
{
    use HasFactory;

    protected $table = 'paquetes'; // Nombre de la tabla en la BD

    protected $fillable = [
        'ubicacion_id',
        'planilla_id'
    ];
    public function getIdPqAttribute()
    {
        return 'PQ' . $this->id;
    }
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    /**
     * Relación uno a muchos con Etiqueta (Un paquete puede tener muchas etiquetas)
     */
    public function etiquetas()
    {
        return $this->hasMany(Etiqueta::class, 'paquete_id');
    }

    /**
     * Relación uno a uno con Ubicación (Un paquete tiene una única ubicación)
     */
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }

    /**
     * Relación muchos a uno con Salida (Varios paquetes pueden pertenecer a una salida)
     */
    // public function salida()
    // {
    //     return $this->belongsTo(Salida::class, 'salida_id');
    // }
}
