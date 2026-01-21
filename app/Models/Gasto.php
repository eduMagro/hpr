<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    protected $fillable = [
        'fecha_pedido',
        'fecha_llegada',
        'nave_id',
        'obra_id',
        'proveedor',
        'maquina_id',
        'motivo',
        'coste',
        'factura_id',
        'observaciones',
    ];

    public function nave()
    {
        return $this->belongsTo(Obra::class, 'nave_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }
}
