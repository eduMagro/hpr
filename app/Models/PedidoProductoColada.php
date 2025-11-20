<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoProductoColada extends Model
{
    protected $table = 'pedido_producto_coladas';

    protected $fillable = [
        'pedido_producto_id',
        'colada',
        'bulto',
        'user_id',
    ];

    public function pedidoProducto()
    {
        return $this->belongsTo(PedidoProducto::class, 'pedido_producto_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

