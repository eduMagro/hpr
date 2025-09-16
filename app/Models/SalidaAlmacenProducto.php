<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidaAlmacenProducto extends Model
{
    use HasFactory;

    protected $table = 'salidas_almacen_productos';

    protected $fillable = [
        'salida_almacen_id',
        'producto_id',
        'cantidad',
        'peso_kg',
        'observaciones',
    ];

    public function salida()
    {
        return $this->belongsTo(SalidaAlmacen::class, 'salida_almacen_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
