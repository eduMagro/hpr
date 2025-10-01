<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoProducto extends Model
{
    protected $table = 'pedido_productos';

    protected $fillable = [
        'pedido_id',
        'pedido_global_id',
        'producto_base_id',
        'cantidad',
        'fecha_estimada_entrega',
        'estado',
        'observaciones',
    ];
    protected $casts = [
        'fecha_estimada_entrega' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class);
    }

    public function pedidoGlobal()
    {
        return $this->belongsTo(PedidoGlobal::class, 'pedido_global_id');
    }
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'pedido_producto_id');
    }

    public function entradas()
    {
        return $this->hasMany(Entrada::class, 'pedido_producto_id');
    }

    public function getTipoAttribute()
    {
        return $this->productoBase?->tipo ?? '—';
    }

    public function getDiametroAttribute()
    {
        return $this->productoBase?->diametro ?? '—';
    }

    public function getLongitudAttribute()
    {
        return $this->productoBase?->longitud ?? '—';
    }

    public function getFechaEstimadaEntregaFormateadaAttribute(): ?string
    {
        return $this->fecha_estimada_entrega
            ? $this->fecha_estimada_entrega->format('d-m-Y')
            : null;
    }
}
