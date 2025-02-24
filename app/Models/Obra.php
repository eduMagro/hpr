<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Obra extends Model
{
    use HasFactory;

    protected $table = 'obras';

    protected $fillable = [
        'obra', 
        'cod_obra', 
        'cliente', 
        'cod_cliente', 
        'latitud', 
        'longitud', 
        'distancia'
    ];

    public function planillas()
    {
        return $this->hasMany(Planilla::class);
    }
}
