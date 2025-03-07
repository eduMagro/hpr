<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones';
    protected $fillable = [
        'codigo',
        'nombre',
        'almacen',
        'sector',
        'ubicacion',
        'descripcion'
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
        return $this->hasMany(Producto::class, 'ubicacion_id');
    }
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'ubicacion_id');
    }
    public function etiquetas()
    {
        return $this->hasMany(Etiqueta::class, 'ubicacion_id');
    }
    public function paquetes()
    {
        return $this->hasMany(Paquete::class, 'ubicacion_id');
    }
}
