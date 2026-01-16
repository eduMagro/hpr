<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Turno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'turnos';

    /**
     * Mapeo de Carbon dayOfWeek a nombre de día
     * Carbon: 0=Domingo, 1=Lunes, ..., 6=Sábado
     */
    public const DIAS_SEMANA = [
        0 => 'domingo',
        1 => 'lunes',
        2 => 'martes',
        3 => 'miercoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sabado',
    ];

    /**
     * Días laborables por defecto (cuando dias_semana es null)
     */
    public const DIAS_DEFAULT = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

    protected $fillable = [
        'nombre',
        'hora_inicio',
        'hora_fin',
        'offset_dias_inicio',
        'offset_dias_fin',
        'activo',
        'orden',
        'color',
        'dias_semana',
        // Campos legacy (mantener compatibilidad)
        'hora_entrada',
        'entrada_offset',
        'hora_salida',
        'salida_offset',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
        'offset_dias_inicio' => 'integer',
        'offset_dias_fin' => 'integer',
        'dias_semana' => 'array',
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionTurno::class, 'turno_id');
    }

    /**
     * Scope para obtener solo turnos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar turnos
     */
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden')->orderBy('hora_inicio');
    }

    /**
     * Obtiene los días en que trabaja este turno
     *
     * @return array Array de nombres de días (ej: ['lunes', 'martes', ...])
     */
    public function getDiasTrabajo(): array
    {
        return $this->dias_semana ?? self::DIAS_DEFAULT;
    }

    /**
     * Verifica si el turno trabaja en un día específico
     *
     * @param int $dayOfWeek Día de la semana según Carbon (0=Domingo, 6=Sábado)
     * @return bool
     */
    public function trabajaEnDia(int $dayOfWeek): bool
    {
        $nombreDia = self::DIAS_SEMANA[$dayOfWeek] ?? null;
        if (!$nombreDia) {
            return false;
        }

        return in_array($nombreDia, $this->getDiasTrabajo());
    }

    /**
     * Verifica si el turno trabaja los sábados
     *
     * @return bool
     */
    public function trabajaSabado(): bool
    {
        return in_array('sabado', $this->getDiasTrabajo());
    }

    /**
     * Verifica si el turno trabaja los domingos
     *
     * @return bool
     */
    public function trabajaDomingo(): bool
    {
        return in_array('domingo', $this->getDiasTrabajo());
    }

    /**
     * Verifica si el turno trabaja en fin de semana (sábado o domingo)
     *
     * @return bool
     */
    public function trabajaFinDeSemana(): bool
    {
        return $this->trabajaSabado() || $this->trabajaDomingo();
    }
}
