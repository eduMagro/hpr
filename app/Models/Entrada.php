<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    use HasFactory;

    // El nombre de la tabla asociada
    protected $table = "entradas";

    // Los campos que son asignables masivamente
    protected $fillable = [
        'user_id',
        'ubicacion_id',
        'fecha',  // O la fecha que necesites
    ];

    /**
     * Relación con la tabla 'ubicaciones'
     * Una entrada pertenece a una ubicación
     */
// En el modelo Entrada

public function ubicacion()
{
    return $this->belongsTo(Ubicacion::class);  // Relación con Ubicacion
}

public function user()
{
    return $this->belongsTo(User::class);  // Relación con User
}

public function producto()
{
    return $this->belongsTo(Producto::class);  // Relación con Producto
}

}




