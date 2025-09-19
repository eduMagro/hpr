<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoBase extends Model
{
    protected $table = 'productos_base';

    protected $fillable = [
        'tipo',
        'diametro',
        'longitud',
        'descripcion',
    ];

    public function pedidos()
    {
        return $this->belongsToMany(Pedido::class, 'pedido_productos')
            ->withPivot('cantidad', 'observaciones')
            ->withTimestamps();
    }
    public function pedidosAlmacen()
    {
        return $this->belongsToMany(PedidoAlmacen::class, 'pedido_almacen_producto_base')
            ->withPivot(['cantidad_kg', 'cantidad_bultos', 'estado'])
            ->withTimestamps();
    }
}
