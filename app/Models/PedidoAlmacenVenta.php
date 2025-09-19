<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoAlmacenVenta extends Model
{
    protected $table = 'pedidos_almacen_venta';

    protected $fillable = [
        'cliente_id',
        'codigo',
        'estado',
        'fecha',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(ClienteAlmacen::class, 'cliente_id');
    }

    public function lineas()
    {
        return $this->hasMany(PedidoAlmacenVentaLinea::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
