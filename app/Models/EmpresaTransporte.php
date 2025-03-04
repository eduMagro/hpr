<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpresaTransporte extends Model
{
    use HasFactory;

    protected $table = 'empresas_transporte';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email'
    ];

    /**
     * Relación: Una empresa tiene muchos camiones.
     */
    public function camiones()
    {
        return $this->hasMany(Camion::class);
    }

    /**
     * Relación: Una empresa tiene muchas salidas.
     */
    public function salidas()
    {
        return $this->hasMany(Salida::class);
    }
}
