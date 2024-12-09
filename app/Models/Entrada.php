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
        'albaran',
        'users_id',
        'otros',
        // 'producto_id',
        // 'ubicacion_id',
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

    /**
     * Relación con la tabla 'users'
     * Una entrada pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');  // Relación con User
    }

    /**
     * Relación con la tabla 'productos'
     * Una entrada puede tener muchos productos
     */
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'entrada_productos');  // Relación uno a muchos
    }

}




