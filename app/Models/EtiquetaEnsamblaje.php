<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtiquetaEnsamblaje extends Model
{
    use HasFactory;

    protected $table = 'etiquetas_ensamblaje';

    protected $fillable = [
        'codigo',
        'planilla_id',
        'planilla_entidad_id',
        'numero_unidad',
        'total_unidades',
        'estado',
        'operario_id',
        'fecha_inicio',
        'fecha_fin',
        'marca',
        'situacion',
        'longitud',
        'peso',
        'impresa',
        'fecha_impresion',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'fecha_impresion' => 'datetime',
        'impresa' => 'boolean',
        'longitud' => 'decimal:3',
        'peso' => 'decimal:3',
    ];

    /**
     * Estados posibles
     */
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_COMPLETADA = 'completada';

    /**
     * Relación con la planilla
     */
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
    }

    /**
     * Relación con la entidad (pilar, viga, etc.)
     */
    public function entidad(): BelongsTo
    {
        return $this->belongsTo(PlanillaEntidad::class, 'planilla_entidad_id');
    }

    /**
     * Relación con el operario asignado
     */
    public function operario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operario_id');
    }

    /**
     * Relación con los elementos asignados a esta etiqueta de ensamblaje
     */
    public function elementos()
    {
        return $this->hasMany(Elemento::class, 'etiqueta_ensamblaje_id');
    }

    /**
     * Genera el código de etiqueta
     * Formato: ENS-{MARCA}-{ID_ENTIDAD}-{N}/{TOTAL}
     */
    public static function generarCodigo(PlanillaEntidad $entidad, int $numeroUnidad, int $totalUnidades): string
    {
        // Sanitizar marca: quitar espacios, truncar a 20 chars
        $marca = strtoupper($entidad->marca ?? 'SM');
        $marca = preg_replace('/\s+/', '', $marca); // Quitar espacios
        $marca = substr($marca, 0, 20); // Truncar a 20 caracteres

        return sprintf(
            'ENS-%s-%d-%d/%d',
            $marca,
            $entidad->id,
            $numeroUnidad,
            $totalUnidades
        );
    }

    /**
     * Indica si puede iniciar el ensamblaje
     */
    public function puedeIniciar(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Indica si puede completar el ensamblaje
     */
    public function puedeCompletar(): bool
    {
        return $this->estado === self::ESTADO_EN_PROCESO;
    }

    /**
     * Inicia el ensamblaje
     */
    public function iniciar(?int $operarioId = null): bool
    {
        if (!$this->puedeIniciar()) {
            return false;
        }

        $this->update([
            'estado' => self::ESTADO_EN_PROCESO,
            'operario_id' => $operarioId,
            'fecha_inicio' => now(),
        ]);

        return true;
    }

    /**
     * Completa el ensamblaje
     */
    public function completar(): bool
    {
        if (!$this->puedeCompletar()) {
            return false;
        }

        $this->update([
            'estado' => self::ESTADO_COMPLETADA,
            'fecha_fin' => now(),
        ]);

        return true;
    }

    /**
     * Marca como impresa
     */
    public function marcarImpresa(): void
    {
        $this->update([
            'impresa' => true,
            'fecha_impresion' => now(),
        ]);
    }

    /**
     * Scope: Solo pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope: Solo en proceso
     */
    public function scopeEnProceso($query)
    {
        return $query->where('estado', self::ESTADO_EN_PROCESO);
    }

    /**
     * Scope: Solo completadas
     */
    public function scopeCompletadas($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    /**
     * Obtener el texto de unidad (ej: "1/3")
     */
    public function getUnidadTextoAttribute(): string
    {
        return "{$this->numero_unidad}/{$this->total_unidades}";
    }

    /**
     * Obtener distribución de la entidad padre
     */
    public function getDistribucionAttribute(): array
    {
        return $this->entidad?->distribucion ?? [];
    }
}
