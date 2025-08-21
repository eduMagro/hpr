<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoProducto extends Model
{
    protected $table = 'pedido_productos';

    protected $fillable = [
        'pedido_id',
        'producto_base_id',
        'cantidad',
        'fecha_estimada_entrega',
        'estado',
        'observaciones',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'pedido_producto_id');
    }

    public function entradas()
    {
        return $this->hasMany(Entrada::class, 'pedido_producto_id');
    }

    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class, 'producto_base_id');
    }
}
