<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    // Definir los campos que se pueden asignar masivamente
    protected $fillable = [
        'empresa',
        'codigo',
        'contacto1_nombre',
        'contacto1_telefono',
        'contacto1_email',
        'contacto2_nombre',
        'contacto2_telefono',
        'contacto2_email',
        'direccion',
        'ciudad',
        'provincia',
        'pais',
        'cif_nif',
        'activo',
    ];

    // Relación con obras (un cliente puede tener muchas obras)
    public function obras()
    {
        return $this->hasMany(Obra::class, 'cliente_id');
    }
    public function salidas()
    {
        return $this->belongsToMany(Salida::class, 'salida_cliente')
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

    // Método para saber si el cliente está activo
    public function isActive()
    {
        return $this->activo == 1;
    }
}
