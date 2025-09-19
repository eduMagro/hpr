<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlbaranVentaProducto extends Model
{
    protected $table = 'albaranes_venta_productos';

    protected $fillable = [
        'salida_almacen_id',
        'albaran_linea_id',
        'producto_id',
        'peso_kg',
        'cantidad',
    ];

    /* ============================
       Relaciones
    ============================ */

    public function salida()
    {
        return $this->belongsTo(SalidaAlmacen::class, 'salida_almacen_id');
    }

    public function linea()
    {
        return $this->belongsTo(AlbaranVentaLinea::class, 'albaran_linea_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
