<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecioMaterialExcepcion extends Model
{
    protected $table = 'precios_material_excepciones';

    protected $fillable = [
        'distribuidor_id',
        'fabricante_id',
        'formato_codigo',
        'incremento',
        'notas',
        'activo',
    ];

    protected $casts = [
        'incremento' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * Relación con el distribuidor
     */
    public function distribuidor(): BelongsTo
    {
        return $this->belongsTo(Distribuidor::class, 'distribuidor_id');
    }

    /**
     * Relación con el fabricante
     */
    public function fabricante(): BelongsTo
    {
        return $this->belongsTo(Fabricante::class, 'fabricante_id');
    }

    /**
     * Scope: Solo activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Busca una excepción para un distribuidor, fabricante y formato.
     * Prioridad: 1) distribuidor+fabricante específico, 2) solo fabricante (distribuidor=null)
     */
    public static function buscar(?int $distribuidorId, ?int $fabricanteId, string $formatoCodigo): ?self
    {
        if (!$fabricanteId) {
            return null;
        }

        // Primero buscar excepción específica para distribuidor+fabricante
        if ($distribuidorId) {
            $especifica = static::activos()
                ->where('distribuidor_id', $distribuidorId)
                ->where('fabricante_id', $fabricanteId)
                ->where('formato_codigo', $formatoCodigo)
                ->first();

            if ($especifica) {
                return $especifica;
            }
        }

        // Si no hay específica, buscar excepción general del fabricante (distribuidor=null)
        return static::activos()
            ->whereNull('distribuidor_id')
            ->where('fabricante_id', $fabricanteId)
            ->where('formato_codigo', $formatoCodigo)
            ->first();
    }

    /**
     * Scope: Excepciones por fabricante (sin distribuidor específico)
     */
    public function scopePorFabricante($query)
    {
        return $query->whereNull('distribuidor_id');
    }

    /**
     * Scope: Excepciones específicas (con distribuidor)
     */
    public function scopeEspecificas($query)
    {
        return $query->whereNotNull('distribuidor_id');
    }
}
