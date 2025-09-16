<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidaAlmacenCliente extends Model
{
    use HasFactory;

    protected $table = 'salidas_almacen_clientes';

    protected $fillable = [
        'salida_almacen_id',
        'cliente_id',
        'obra_id',
        'horas_paralizacion',
        'importe_paralizacion',
        'horas_grua',
        'importe_grua',
        'horas_almacen',
        'importe',
    ];

    protected $casts = [
        'horas_paralizacion'   => 'decimal:2',
        'importe_paralizacion' => 'decimal:2',
        'horas_grua'           => 'decimal:2',
        'importe_grua'         => 'decimal:2',
        'horas_almacen'        => 'decimal:2',
        'importe'              => 'decimal:2',
    ];

    // Relaciones
    public function salidaAlmacen()
    {
        return $this->belongsTo(SalidaAlmacen::class, 'salida_almacen_id');
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
