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
        'proveedor_id',
        'maquina_id',
        'motivo_id',
        'coste',
        'codigo_factura',
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

    public function proveedor()
    {
        return $this->belongsTo(GastoProveedor::class, 'proveedor_id');
    }

    public function motivo()
    {
        return $this->belongsTo(GastoMotivo::class, 'motivo_id');
    }
}
