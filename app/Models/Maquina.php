<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
    use HasFactory;

    protected $table = 'maquinas';
    protected $fillable = [
        'codigo', 'nombre'
    ];
    public function productos()
    {
        return $this->hasMany(Producto::class, 'maquina_id');
    }
    
   
}
