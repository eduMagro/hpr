<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones';
    protected $fillable = [
        'codigo', 'descripcion'
    ];

    // Relación con la tabla 'entradas'
    public function entradas()
    {
        return $this->hasMany(Entrada::class);
    }
}
