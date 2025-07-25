<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salida extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_salida',
        'codigo_sage',
        'user_id',
        'camion_id',
        'empresa_id',
        'horas_paralizacion',
        'importe_paralizacion',
        'horas_grua',
        'importe_grua',
        'horas_almacen',
        'importe',
        'estado',
        'fecha_salida',
        'observaciones'
    ];
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación: Una salida pertenece a un camión.
     */
    public function camion()
    {
        return $this->belongsTo(Camion::class);
    }

    /**
     * Relación: Una salida pertenece a una empresa de transporte.
     */
    public function empresaTransporte()
    {
        return $this->belongsTo(EmpresaTransporte::class, 'empresa_id');
    }

    /**
     * Relación: Una salida tiene muchos paquetes.
     */
    public function paquetes()
    {
        return $this->belongsToMany(Paquete::class, 'salidas_paquetes', 'salida_id', 'paquete_id');
    }
    public function salidasPaquetes()
    {
        return $this->hasMany(SalidaPaquete::class);
    }
    public function salidaClientes()
    {
        return $this->hasMany(SalidaCliente::class);
    }
    public function obras()
    {
        return $this->belongsToMany(Obra::class, 'salida_cliente', 'salida_id', 'obra_id');
    }
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }
}
