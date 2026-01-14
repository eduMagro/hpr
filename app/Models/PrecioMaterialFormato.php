<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecioMaterialFormato extends Model
{
    protected $table = 'precios_material_formatos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'longitud_min',
        'longitud_max',
        'es_encarretado',
        'incremento',
        'activo',
    ];

    protected $casts = [
        'longitud_min' => 'decimal:2',
        'longitud_max' => 'decimal:2',
        'es_encarretado' => 'boolean',
        'incremento' => 'decimal:2',
        'activo' => 'boolean',
    ];

    // Constantes para códigos de formato
    const ESTANDAR_12M = 'estandar_12m';
    const LARGO_ESPECIAL = 'largo_especial';
    const CORTO_6M = 'corto_6m';
    const ENCARRETADO = 'encarretado';

    /**
     * Scope: Solo activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Determina el formato basado en la longitud y si es encarretado.
     */
    public static function determinarFormato(?float $longitud, bool $esEncarretado = false): ?self
    {
        if ($esEncarretado) {
            return static::activos()->where('codigo', self::ENCARRETADO)->first();
        }

        if ($longitud === null) {
            return static::activos()->where('codigo', self::ESTANDAR_12M)->first();
        }

        // Buscar formato que coincida con la longitud
        return static::activos()
            ->where('es_encarretado', false)
            ->where(function ($query) use ($longitud) {
                $query->where(function ($q) use ($longitud) {
                    $q->whereNotNull('longitud_min')
                      ->whereNotNull('longitud_max')
                      ->where('longitud_min', '<=', $longitud)
                      ->where('longitud_max', '>=', $longitud);
                })->orWhere(function ($q) use ($longitud) {
                    $q->whereNull('longitud_min')
                      ->whereNotNull('longitud_max')
                      ->where('longitud_max', '>=', $longitud);
                })->orWhere(function ($q) use ($longitud) {
                    $q->whereNotNull('longitud_min')
                      ->whereNull('longitud_max')
                      ->where('longitud_min', '<=', $longitud);
                });
            })
            ->first() ?? static::activos()->where('codigo', self::ESTANDAR_12M)->first();
    }
}
