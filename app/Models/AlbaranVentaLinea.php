<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlbaranVentaLinea extends Model
{
    protected $table = 'albaranes_venta_lineas';

    protected $fillable = [
        'albaran_id',
        'pedido_linea_id',
        'producto_base_id',
        'cantidad_kg',
        'precio_unitario',
    ];

    // Relaciones
    public function albaran()
    {
        return $this->belongsTo(AlbaranVenta::class, 'albaran_id');
    }

    public function pedidoLinea()
    {
        return $this->belongsTo(PedidoAlmacenVentaLinea::class, 'pedido_linea_id');
    }

    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class, 'producto_base_id');
    }

    public function albaranesVentaProductos()
    {
        return $this->hasMany(AlbaranVentaProducto::class, 'albaran_linea_id');
    }
}
