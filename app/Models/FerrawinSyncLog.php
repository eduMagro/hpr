<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para registrar logs de sincronización FerraWin.
 *
 * @property int $id
 * @property \Carbon\Carbon $fecha_ejecucion
 * @property string $estado
 * @property int $planillas_encontradas
 * @property int $planillas_nuevas
 * @property int $planillas_actualizadas
 * @property int $planillas_sincronizadas
 * @property int $planillas_fallidas
 * @property int $elementos_creados
 * @property string|null $errores
 * @property string|null $advertencias
 * @property float $duracion_segundos
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FerrawinSyncLog extends Model
{
    protected $table = 'ferrawin_sync_logs';

    protected $fillable = [
        'fecha_ejecucion',
        'estado',
        'planillas_encontradas',
        'planillas_nuevas',
        'planillas_actualizadas',
        'planillas_sincronizadas',
        'planillas_fallidas',
        'elementos_creados',
        'errores',
        'advertencias',
        'duracion_segundos',
    ];

    protected $casts = [
        'fecha_ejecucion' => 'datetime',
        'planillas_encontradas' => 'integer',
        'planillas_nuevas' => 'integer',
        'planillas_actualizadas' => 'integer',
        'planillas_sincronizadas' => 'integer',
        'planillas_fallidas' => 'integer',
        'elementos_creados' => 'integer',
        'duracion_segundos' => 'float',
    ];

    /**
     * Obtiene los errores como array.
     */
    public function getErroresArrayAttribute(): array
    {
        return $this->errores ? json_decode($this->errores, true) : [];
    }

    /**
     * Obtiene las advertencias como array.
     */
    public function getAdvertenciasArrayAttribute(): array
    {
        return $this->advertencias ? json_decode($this->advertencias, true) : [];
    }

    /**
     * Indica si la ejecución fue exitosa.
     */
    public function esExitosa(): bool
    {
        return in_array($this->estado, ['completado', 'sin_cambios', 'sin_datos']);
    }

    /**
     * Scope para obtener solo ejecuciones exitosas.
     */
    public function scopeExitosas($query)
    {
        return $query->whereIn('estado', ['completado', 'sin_cambios', 'sin_datos']);
    }

    /**
     * Scope para obtener solo ejecuciones con errores.
     */
    public function scopeConErrores($query)
    {
        return $query->where('estado', 'error')
            ->orWhere('planillas_fallidas', '>', 0);
    }

    /**
     * Obtiene la última ejecución.
     */
    public static function ultima(): ?self
    {
        return static::latest('fecha_ejecucion')->first();
    }

    /**
     * Obtiene estadísticas de las últimas N ejecuciones.
     */
    public static function estadisticas(int $ultimas = 30): array
    {
        $logs = static::latest('fecha_ejecucion')
            ->limit($ultimas)
            ->get();

        if ($logs->isEmpty()) {
            return [
                'total_ejecuciones' => 0,
                'exitosas' => 0,
                'con_errores' => 0,
                'planillas_sincronizadas_total' => 0,
                'elementos_creados_total' => 0,
                'duracion_promedio' => 0,
            ];
        }

        return [
            'total_ejecuciones' => $logs->count(),
            'exitosas' => $logs->filter(fn($l) => $l->esExitosa())->count(),
            'con_errores' => $logs->filter(fn($l) => !$l->esExitosa() || $l->planillas_fallidas > 0)->count(),
            'planillas_sincronizadas_total' => $logs->sum('planillas_sincronizadas'),
            'elementos_creados_total' => $logs->sum('elementos_creados'),
            'duracion_promedio' => round($logs->avg('duracion_segundos'), 2),
        ];
    }
}
