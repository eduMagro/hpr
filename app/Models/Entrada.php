<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    use HasFactory;

    protected $table = "entradas";

    protected $fillable = [
        'nombre_material', 'descripcion_material', 'ubicacion_id', 'user_id'
    ];

    // Relación con la tabla 'ubicaciones'
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }
    

    // Relación con la tabla 'users'
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
}
