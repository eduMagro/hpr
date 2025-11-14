<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Camion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'camiones';

    protected $fillable = [
        'empresa_id',
        'capacidad',
        'modelo',
        'año',
        'estado'
    ];

    /**
     * Relación: Un camión pertenece a una empresa de transporte.
     */
    public function empresaTransporte()
    {
        return $this->belongsTo(EmpresaTransporte::class, 'empresa_id');
    }

    /**
     * Relación: Un camión tiene muchas salidas.
     */
    public function salidas()
    {
        return $this->hasMany(Salida::class, 'camion_id');
    }
}
