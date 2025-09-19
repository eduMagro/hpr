<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalidaAlmacenPedidoVentaProductoBase extends Model
{
    use HasFactory;

    // Tabla pivote entre una salida y una línea de pedido (producto_base objetivo)
    protected $table = 'salidas_almacen_pedido_venta_productos_base';

    protected $fillable = [
        'salida_almacen_id',
        'pedido_venta_almacen_producto_base_id',
        'producto_base_id',      // opcionalmente denormalizado (útil para filtros)
        'kg_entregados',         // NULL si se trabaja por unidades
        'unidades_entregadas',   // NULL si se trabaja por kg
        'observaciones',
    ];

    protected $casts = [
        'kg_entregados'       => 'decimal:3',
        'unidades_entregadas' => 'integer',
    ];

    // 🔗 Relaciones
    public function salida()
    {
        return $this->belongsTo(SalidaAlmacen::class, 'salida_almacen_id');
    }

    public function lineaPedido()
    {
        return $this->belongsTo(
            PedidoVentaAlmacenProductoBase::class,
            'pedido_venta_almacen_producto_base_id'
        );
    }

    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class, 'producto_base_id');
    }

    // 🔎 Accesos útiles
    public function esPorPeso(): bool
    {
        return !is_null($this->kg_entregados);
    }

    public function esPorUnidades(): bool
    {
        return !is_null($this->unidades_entregadas);
    }
}
