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
        return $this->belongsTo(EmpresaTransporte::class);
    }

    /**
     * Relación: Una salida tiene muchas planillas.
     */
    public function planillas()
    {
        return $this->belongsToMany(Planilla::class, 'salida_planilla');
    }
}
