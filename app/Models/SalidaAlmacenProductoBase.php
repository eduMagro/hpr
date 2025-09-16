<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalidaAlmacenProductoBase extends Model
{
    protected $table = 'salidas_almacen_productos_base';

    protected $fillable = [
        'salida_almacen_id',
        'producto_base_id',
        'peso_objetivo_kg',     // NULL si el objetivo va por unidades
        'unidades_objetivo',    // NULL si el objetivo va por peso
    ];

    public function salida()
    {
        return $this->belongsTo(SalidaAlmacen::class, 'salida_almacen_id');
    }

    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class, 'producto_base_id');
    }
}
