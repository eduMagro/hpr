<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
    use HasFactory;

    protected $table = 'maquinas';
    protected $fillable = [
        'codigo',
        'nombre',
        'diametro_min',
        'diametro_max',
        'peso_min',
        'peso_max',
    ];

    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    

    public function productos()
    {
        return $this->hasMany(Producto::class, 'maquina_id');
    }
    
   
}
