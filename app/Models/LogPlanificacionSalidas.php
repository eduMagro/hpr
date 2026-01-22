<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LogPlanificacionSalidas extends Model
{
    protected $table = 'logs_planificacion_salidas';

    protected $fillable = [
        'user_id',
        'accion',
        'descripcion',
        'detalles',
        'datos_reversion',
        'planilla_id',
        'salida_id',
        'revertido',
        'revertido_at',
        'revertido_por',
    ];

    protected $casts = [
        'detalles' => 'array',
        'datos_reversion' => 'array',
        'revertido' => 'boolean',
        'revertido_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function revertidoPorUser()
    {
        return $this->belongsTo(User::class, 'revertido_por');
    }

    public function planilla()
    {
        return $this->belongsTo(Planilla::class);
    }

    public function salida()
    {
        return $this->belongsTo(Salida::class);
    }

    /**
     * Registrar una acción en el log
     */
    public static function registrar(string $accion, string $descripcion, array $detalles = [], array $extras = [], array $datosReversion = []): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'descripcion' => $descripcion,
            'detalles' => $detalles,
            'datos_reversion' => !empty($datosReversion) ? $datosReversion : null,
            'planilla_id' => $extras['planilla_id'] ?? null,
            'salida_id' => $extras['salida_id'] ?? null,
        ]);
    }

    /**
     * Verificar si esta acción puede ser revertida
     */
    public function puedeRevertirse(): bool
    {
        if ($this->revertido) {
            return false;
        }

        if (empty($this->datos_reversion)) {
            return false;
        }

        // Solo se puede revertir la última acción no revertida
        $ultimaAccion = self::where('revertido', false)
            ->whereNotNull('datos_reversion')
            ->orderBy('id', 'desc')
            ->first();

        return $ultimaAccion && $ultimaAccion->id === $this->id;
    }

    /**
     * Revertir esta acción
     */
    public function revertir(): bool
    {
        if (!$this->puedeRevertirse()) {
            return false;
        }

        $datos = $this->datos_reversion;

        DB::beginTransaction();
        try {
            switch ($this->accion) {
                case 'mover_planilla':
                    $this->revertirMoverPlanilla($datos);
                    break;

                case 'mover_salida':
                    $this->revertirMoverSalida($datos);
                    break;

                default:
                    throw new \Exception("Acción '{$this->accion}' no soporta reversión");
            }

            $this->update([
                'revertido' => true,
                'revertido_at' => now(),
                'revertido_por' => auth()->id(),
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Revertir movimiento de planilla
     */
    private function revertirMoverPlanilla(array $datos): void
    {
        if (!empty($datos['planilla_id']) && !empty($datos['fecha_anterior'])) {
            Planilla::where('id', $datos['planilla_id'])->update([
                'fecha_estimada_entrega' => $datos['fecha_anterior'],
            ]);
        }
    }

    /**
     * Revertir movimiento de salida
     */
    private function revertirMoverSalida(array $datos): void
    {
        if (!empty($datos['salida_id']) && !empty($datos['fecha_anterior'])) {
            Salida::where('id', $datos['salida_id'])->update([
                'fecha_salida' => $datos['fecha_anterior'],
            ]);
        }
    }
}
