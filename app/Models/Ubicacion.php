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

    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    

    // Relación con la tabla 'entradas'
    public function entradas()
    {
        return $this->hasMany(Entrada::class);
    }
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
