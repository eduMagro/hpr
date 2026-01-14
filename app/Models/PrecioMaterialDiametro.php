<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecioMaterialDiametro extends Model
{
    protected $table = 'precios_material_diametros';

    protected $fillable = [
        'diametro',
        'incremento',
        'activo',
    ];

    protected $casts = [
        'diametro' => 'integer',
        'incremento' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * Scope: Solo activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtiene el incremento para un diámetro específico.
     */
    public static function getIncremento(int $diametro): float
    {
        $regla = static::activos()->where('diametro', $diametro)->first();
        return $regla ? (float) $regla->incremento : 0.0;
    }
}
