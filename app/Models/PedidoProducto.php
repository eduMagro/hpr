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
        'observaciones',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class);
    }
}
