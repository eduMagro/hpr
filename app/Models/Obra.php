<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Obra extends Model
{
    use HasFactory;

    protected $table = 'obras';

    protected $fillable = [
        'obra',
        'cod_obra',
        'cliente_id',
        'ciudad',
        'direccion',
        'completada',
        'latitud',
        'longitud',
        'distancia',
        'estado'
    ];

    public function planillas()
    {
        return $this->hasMany(Planilla::class);
    }
    public function salidaClientes()
    {
        return $this->hasMany(SalidaCliente::class, 'obra_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
    public function salidas()
    {
        return $this->belongsToMany(Salida::class, 'salida_cliente', 'obra_id', 'salida_id');
    }
}
