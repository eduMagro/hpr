<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroFichaje extends Model
{
    use HasFactory;

    protected $table = 'registros_fichaje'; // Nombre de la tabla en la BD

    protected $fillable = [
        'user_id',
        'entrada', // 'entrada' o 'salida'
        'salida', // 'entrada' o 'salida
    ];

    /**
     * Relación con el modelo User.
     * Un registro de fichaje pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
