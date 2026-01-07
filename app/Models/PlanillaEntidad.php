<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa una entidad/ensamblaje de una planilla.
 *
 * Las entidades son los elementos estructurales como pilares, vigas, punzonamientos,
 * etc. que se componen de barras longitudinales y estribos transversales.
 */
class PlanillaEntidad extends Model
{
    use HasFactory;

    protected $table = 'planilla_entidades';

    protected $fillable = [
        'planilla_id',
        'linea',
        'marca',
        'situacion',
        'cantidad',
        'miembros',
        'modelo',
        'cotas',
        'longitud_ensamblaje',
        'peso_total',
        'total_barras',
        'total_estribos',
        'composicion',
        'distribucion',
        'dibujo_data',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'miembros' => 'integer',
        'longitud_ensamblaje' => 'decimal:2',
        'peso_total' => 'decimal:2',
        'total_barras' => 'integer',
        'total_estribos' => 'integer',
        'composicion' => 'array',
        'distribucion' => 'array',
        'dibujo_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con la planilla.
     */
    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    /**
     * Relación con las etiquetas de ensamblaje.
     * Cada entidad puede tener múltiples etiquetas (una por cada unidad).
     */
    public function etiquetasEnsamblaje()
    {
        return $this->hasMany(EtiquetaEnsamblaje::class, 'planilla_entidad_id');
    }

    /**
     * Relación con los elementos que componen esta entidad.
     */
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'planilla_entidad_id');
    }

    /**
     * Obtiene las barras de la composición.
     */
    public function getBarrasAttribute(): array
    {
        return $this->composicion['barras'] ?? [];
    }

    /**
     * Obtiene los estribos de la composición.
     */
    public function getEstribosAttribute(): array
    {
        return $this->composicion['estribos'] ?? [];
    }

    /**
     * Obtiene la armadura longitudinal de la distribución.
     */
    public function getArmaduraLongitudinalAttribute(): array
    {
        return $this->distribucion['armadura_longitudinal'] ?? [];
    }

    /**
     * Obtiene la armadura transversal de la distribución.
     */
    public function getArmaduraTransversalAttribute(): array
    {
        return $this->distribucion['armadura_transversal'] ?? [];
    }

    /**
     * Descripción completa de la entidad.
     */
    public function getDescripcionCompletaAttribute(): string
    {
        return trim("{$this->marca} - {$this->situacion}");
    }

    /**
     * Resumen textual de la distribución para mostrar en UI.
     */
    public function getResumenDistribucionAttribute(): string
    {
        $partes = [];

        // Barras
        if ($this->total_barras > 0) {
            $barras = $this->armadura_longitudinal;
            $diametros = array_unique(array_column($barras, 'diametro'));
            sort($diametros);
            $partes[] = $this->total_barras . ' barras (Ø' . implode(', Ø', $diametros) . ')';
        }

        // Estribos
        if ($this->total_estribos > 0) {
            $estribos = $this->armadura_transversal;
            if (!empty($estribos)) {
                $primer = $estribos[0];
                $sep = $primer['separacion_aprox_cm'] ?? 0;
                $partes[] = "{$primer['cantidad']} estribos Ø{$primer['diametro']}" . ($sep ? " c/{$sep}cm" : '');
            }
        }

        return implode(' + ', $partes) ?: 'Sin distribución';
    }

    /**
     * Relación con las órdenes de ensamblaje.
     */
    public function ordenesEnsamblaje()
    {
        return $this->hasMany(OrdenPlanillaEnsamblaje::class, 'planilla_entidad_id');
    }

    /**
     * Verifica si la entidad tiene todos sus elementos fabricados.
     * Una entidad está "lista" cuando todos sus elementos tienen estado 'fabricado' o 'completado'.
     */
    public function listaParaEnsamblaje(): bool
    {
        $totalElementos = $this->elementos()->count();

        // Si no tiene elementos asignados, no está lista
        if ($totalElementos === 0) {
            return false;
        }

        $elementosFabricados = $this->elementos()
            ->whereIn('estado', ['fabricado', 'completado'])
            ->count();

        return $elementosFabricados >= $totalElementos;
    }

    /**
     * Obtiene el número de elementos fabricados vs total.
     */
    public function getElementosFabricadosAttribute(): array
    {
        $total = $this->elementos()->count();
        $fabricados = $this->elementos()
            ->whereIn('estado', ['fabricado', 'completado'])
            ->count();

        return [
            'fabricados' => $fabricados,
            'total' => $total,
            'porcentaje' => $total > 0 ? round(($fabricados / $total) * 100, 1) : 0,
            'listo' => $total > 0 && $fabricados >= $total,
        ];
    }

    /**
     * Scope: Solo entidades listas para ensamblaje.
     */
    public function scopeListasParaEnsamblaje($query)
    {
        return $query->whereHas('elementos', function ($q) {
            $q->whereIn('estado', ['fabricado', 'completado']);
        })->whereDoesntHave('elementos', function ($q) {
            $q->whereNotIn('estado', ['fabricado', 'completado']);
        });
    }

    /**
     * Scope: Entidades con al menos un elemento fabricado.
     */
    public function scopeConElementosFabricados($query)
    {
        return $query->whereHas('elementos', function ($q) {
            $q->whereIn('estado', ['fabricado', 'completado']);
        });
    }

    /**
     * Scope: Entidades sin orden de ensamblaje asignada.
     */
    public function scopeSinOrdenEnsamblaje($query, int $maquinaId)
    {
        return $query->whereDoesntHave('ordenesEnsamblaje', function ($q) use ($maquinaId) {
            $q->where('maquina_id', $maquinaId);
        });
    }
}
