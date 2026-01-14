<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\PrecioMaterialService;

class Obra extends Model
{
    use HasFactory, SoftDeletes;

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

    public function getEsAlmacenAttribute(): bool
    {
        return preg_match('/almac.*n/i', $this->obra ?? '') > 0;
    }

    public function getEsNaveAAttribute(): bool
    {
        return stripos($this->obra ?? '', 'nave a') !== false;
    }

    public function getEsNaveBAttribute(): bool
    {
        return stripos($this->obra ?? '', 'nave b') !== false;
    }

    /**
     * Calcula el coste total de material de la obra (dinámico).
     * Suma el coste de todos los elementos de las planillas de la obra.
     *
     * @return array{coste_total: float, elementos_count: int, errores_count: int}
     */
    public function getCosteMaterialTotal(): array
    {
        $service = app(PrecioMaterialService::class);
        return $service->calcularCosteObra($this);
    }

    /**
     * Accessor para obtener solo el valor del coste total de material.
     */
    public function getCosteMaterialTotalAttribute(): float
    {
        return $this->getCosteMaterialTotal()['coste_total'];
    }
}
