<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salida extends Model
{
    use HasFactory;

    protected $fillable = [
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
    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'salida_cliente')
            ->withPivot(
                'horas_paralizacion',
                'importe_paralizacion',
                'horas_grua',
                'importe_grua',
                'horas_almacen',
                'importe'
            )
            ->withTimestamps();
    }


    public function planillas()
    {
        return $this->belongsToMany(Planilla::class, 'salidas_paquetes', 'salida_id', 'planilla_id');
    }
}
