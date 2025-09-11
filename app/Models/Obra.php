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
        'ancho_m',
        'largo_m',
        'estado',
        'tipo'
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
    public function maquinas()
    {
        return $this->hasMany(Maquina::class);
    }
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
    public function localizaciones()
    {
        return $this->hasMany(Localizacion::class, 'nave_id');
    }

    public function getEsNavePacoReyesAttribute(): bool
    {
        $empresa = strtoupper(trim($this->cliente->empresa ?? ''));
        return str_contains($empresa, 'PACO REYES');
    }
}
