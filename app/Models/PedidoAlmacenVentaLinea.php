<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoAlmacenVentaLinea extends Model
{
    protected $table = 'pedidos_almacen_venta_lineas';

    protected $fillable = [
        'pedido_almacen_venta_id',
        'producto_base_id',
        'unidad_medida',
        'cantidad_solicitada',
        'cantidad_servida',
        'cantidad_pendiente',
        'kg_por_bulto_override',
        'precio_unitario',
        'notas',
    ];

    // Relaciones
    public function pedido()
    {
        return $this->belongsTo(PedidoAlmacenVenta::class, 'pedido_almacen_venta_id');
    }

    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class, 'producto_base_id');
    }
    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function albaranesLineas()
    {
        return $this->hasMany(AlbaranVentaLinea::class, 'pedido_linea_id');
    }

    public function getCantidadServidaCalculadaAttribute()
    {
        return $this->albaranesLineas->sum('cantidad_kg');
    }
    public function getEstadoDinamicoAttribute()
    {
        if ($this->cantidad_servida_calculada <= 0) {
            return 'pendiente';
        }

        if ($this->cantidad_servida_calculada < $this->cantidad_solicitada) {
            return 'parcial';
        }

        return 'completada';
    }
}
