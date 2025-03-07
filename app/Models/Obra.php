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
        'distancia'
    ];

    public function planillas()
    {
        return $this->hasMany(Planilla::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
