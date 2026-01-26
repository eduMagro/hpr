<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Orden de trabajo para ensamblaje.
 *
 * Gestiona la cola de entidades a ensamblar en una máquina ensambladora.
 * Solo se añaden entidades cuyos elementos ya están todos fabricados.
 */
class OrdenPlanillaEnsamblaje extends Model
{
    use HasFactory;

    protected $table = 'orden_planillas_ensamblaje';

    protected $fillable = [
        'maquina_id',
        'planilla_entidad_id',
        'posicion',
        'estado',
        'prioridad',
        'notas',
        'asignado_por',
        'fecha_asignacion',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'posicion' => 'integer',
        'prioridad' => 'integer',
        'fecha_asignacion' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    /**
     * Estados posibles
     */
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_COMPLETADA = 'completada';
    const ESTADO_PAUSADA = 'pausada';

    /**
     * Relación con la máquina
     */
    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    /**
     * Relación con la entidad
     */
    public function entidad(): BelongsTo
    {
        return $this->belongsTo(PlanillaEntidad::class, 'planilla_entidad_id');
    }

    /**
     * Relación con el usuario que asignó
     */
    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por');
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
     * Scope: Ordenados por posición
     */
    public function scopeOrdenados($query)
    {
        return $query->orderBy('posicion', 'asc');
    }

    /**
     * Scope: Para una máquina específica
     */
    public function scopeParaMaquina($query, int $maquinaId)
    {
        return $query->where('maquina_id', $maquinaId);
    }

    /**
     * Verifica si la entidad tiene todos sus elementos fabricados
     */
    public function elementosListos(): bool
    {
        $entidad = $this->entidad;
        if (!$entidad) {
            return false;
        }

        // Contar elementos de la entidad
        $totalElementos = Elemento::where('planilla_entidad_id', $entidad->id)->count();

        if ($totalElementos === 0) {
            return false;
        }

        // Contar elementos fabricados
        $elementosFabricados = Elemento::where('planilla_entidad_id', $entidad->id)
            ->where('elaborado', 1)
            ->count();

        return $elementosFabricados >= $totalElementos;
    }

    /**
     * Obtiene el porcentaje de elementos fabricados
     */
    public function getPorcentajeFabricadoAttribute(): float
    {
        $entidad = $this->entidad;
        if (!$entidad) {
            return 0;
        }

        $totalElementos = Elemento::where('planilla_entidad_id', $entidad->id)->count();

        if ($totalElementos === 0) {
            return 0;
        }

        $elementosFabricados = Elemento::where('planilla_entidad_id', $entidad->id)
            ->where('elaborado', 1)
            ->count();

        return round(($elementosFabricados / $totalElementos) * 100, 1);
    }

    /**
     * Inicia el ensamblaje
     */
    public function iniciar(): bool
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            return false;
        }

        $this->update([
            'estado' => self::ESTADO_EN_PROCESO,
            'fecha_inicio' => now(),
        ]);

        return true;
    }

    /**
     * Completa el ensamblaje
     */
    public function completar(): bool
    {
        if ($this->estado !== self::ESTADO_EN_PROCESO) {
            return false;
        }

        $this->update([
            'estado' => self::ESTADO_COMPLETADA,
            'fecha_fin' => now(),
        ]);

        return true;
    }

    /**
     * Pausa el ensamblaje
     */
    public function pausar(): bool
    {
        if ($this->estado !== self::ESTADO_EN_PROCESO) {
            return false;
        }

        $this->update([
            'estado' => self::ESTADO_PAUSADA,
        ]);

        return true;
    }

    /**
     * Obtiene la siguiente posición disponible para una máquina
     */
    public static function siguientePosicion(int $maquinaId): int
    {
        return (self::where('maquina_id', $maquinaId)->max('posicion') ?? 0) + 1;
    }
}
