<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use HasFactory;

    protected $table = 'movimientos';

    protected $fillable = [
        'tipo',
        'producto_id',
        'producto_base_id',
        'paquete_id',
        'pedido_id',
        'pedido_producto_id',
        'salida_id',
        'salida_almacen_id',
        'ubicacion_origen',
        'ubicacion_destino',
        'maquina_origen',
        'maquina_destino',
        'estado',
        'prioridad',
        'descripcion',
        'nave_id',
        'fecha_solicitud',
        'fecha_ejecucion',
        'solicitado_por',
        'ejecutado_por',
    ];

    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    public function productoBase()
    {
        return $this->belongsTo(ProductoBase::class, 'producto_base_id');
    }
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
    public function pedidoProducto()
    {
        return $this->belongsTo(PedidoProducto::class, 'pedido_producto_id');
    }

    public function salida()
    {
        return $this->belongsTo(Salida::class);
    }
    public function salidaAlmacen()
    {
        return $this->belongsTo(SalidaAlmacen::class);
    }


    public function ubicacionOrigen()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_origen');
    }

    public function ubicacionDestino()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_destino');
    }
    // En Movimiento.php
    public function maquinaDestino()
    {
        return $this->belongsTo(Maquina::class, 'maquina_destino');
    }

    // Nueva relación para maquina_origen
    public function maquinaOrigen()
    {
        return $this->belongsTo(Maquina::class, 'maquina_origen');
    }

    public function solicitadoPor()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function ejecutadoPor()
    {
        return $this->belongsTo(User::class, 'ejecutado_por');
    }

    public function nave()
    {
        return $this->belongsTo(Obra::class, 'nave_id');
    }
}
