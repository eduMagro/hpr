<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalidaCliente extends Model
{
    protected $table = 'salida_cliente';

    protected $fillable = [
        'salida_id',
        'cliente_id',
        'horas_paralizacion',
        'importe_paralizacion',
        'horas_grua',
        'importe_grua',
        'horas_almacen',
        'importe'
    ];

    public function salida()
    {
        return $this->belongsTo(Salida::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }
}
