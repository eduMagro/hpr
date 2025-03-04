<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camion extends Model
{
    use HasFactory;

    protected $table = 'camiones';

    protected $fillable = [
        'empresa_id',
        'matricula',
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
        return $this->belongsTo(EmpresaTransporte::class);
    }

    /**
     * Relación: Un camión tiene muchas salidas.
     */
    public function salidas()
    {
        return $this->hasMany(Salida::class);
    }
}
