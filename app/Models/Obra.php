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

    // Colección: todas las naves PACO REYES
    public static function getNavesPacoReyes()
    {
        return self::whereHas('cliente', function ($q) {
            $q->where('empresa', 'like', '%PACO REYES%');
        })->get();
    }

    public function scopeDeClienteYObra($query, string $clienteNombre, string $obraNombre)
    {
        return $query->whereHas('cliente', function ($q) use ($clienteNombre) {
            $q->where('empresa', 'like', "%{$clienteNombre}%");
        })
            ->where('obra', 'like', "%{$obraNombre}%");
    }
    public static function buscarDeCliente(string $clienteNombre, string $obraNombre): ?self
    {
        return self::deClienteYObra($clienteNombre, $obraNombre)->first();
    }

    public function paquetes()
    {
        return $this->hasMany(Paquete::class, 'nave_id');
    }
    public function entradas()
    {
        return $this->hasMany(Entrada::class, 'nave_id');
    }
}
